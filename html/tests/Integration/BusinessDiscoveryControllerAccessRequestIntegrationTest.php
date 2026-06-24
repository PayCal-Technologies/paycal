<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PayCal\Domain\BusinessGroupService;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\BusinessDiscoveryService;
use PayCal\Domain\UserRepository;

final class BusinessDiscoveryControllerAccessRequestIntegrationTest extends TestCase
{
  private BusinessDiscoveryService $service;
  private string $ownerUUID;
  private string $ownerEmail;
  private string $ownerSession;
  private string $requesterUUID;
  private string $requesterEmail;
  private string $requesterSession;
  private string $businessId = '';
  /** @var array<string, string> */
  private array $seededMembers = [];
  private bool $originalOrgSharedEncryptionEnabled;
  private bool $originalOrgSharedEncryptionWriteEnabled;

  protected function setUp(): void
  {
    parent::setUp();

    $this->service = new BusinessDiscoveryService();
    $this->originalOrgSharedEncryptionEnabled = (bool) SystemConfig::get('business_shared_encryption_enabled');
    $this->originalOrgSharedEncryptionWriteEnabled = (bool) SystemConfig::get('business_shared_encryption_enable_write');
    SystemConfig::set('business_shared_encryption_enabled', false);
    SystemConfig::set('business_shared_encryption_enable_write', false);
    $suffix = bin2hex(random_bytes(6));

    $this->ownerUUID = 'org-owner-ctrl-' . $suffix;
    $this->ownerEmail = 'owner-ctrl-' . $suffix . '@example.com';
    $this->requesterUUID = 'org-requester-ctrl-' . $suffix;
    $this->requesterEmail = 'requester-ctrl-' . $suffix . '@example.com';

    $this->seedUser($this->ownerUUID, $this->ownerEmail);
    $this->seedUser($this->requesterUUID, $this->requesterEmail);

    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID, [
      'tier' => 'business',
      'status' => 'active',
    ]);

    $create = $this->service->createBusiness($this->ownerUUID, 'Controller Access Org', [
      'business_type' => 'shared',
    ]);

    $this->assertTrue($create['success']);
    $this->businessId = (string) ($create['data']['business_id'] ?? '');
    $this->assertNotSame('', $this->businessId);

    $this->ownerSession = hash('sha256', bin2hex(random_bytes(24)));
    Database::hset(Keys::SESSION . ':' . $this->ownerSession, [
      'user_uuid' => $this->ownerUUID,
      'created_at' => date('c'),
    ]);
    Database::expire(Keys::SESSION . ':' . $this->ownerSession, 3600);

    $this->requesterSession = hash('sha256', bin2hex(random_bytes(24)));
    Database::hset(Keys::SESSION . ':' . $this->requesterSession, [
      'user_uuid' => $this->requesterUUID,
      'created_at' => date('c'),
    ]);
    Database::expire(Keys::SESSION . ':' . $this->requesterSession, 3600);
  }

  protected function tearDown(): void
  {
    if ($this->businessId !== '') {
      $this->cleanupBusinessArtifacts($this->businessId);
    }

    Database::unlink(Keys::SESSION . ':' . $this->ownerSession);
    Database::unlink(Keys::SESSION . ':' . $this->requesterSession);

    Database::unlink(Keys::BUSINESS_OWNER . ':' . $this->ownerUUID);
    Database::unlink(Keys::BUSINESS_USER . ':' . $this->ownerUUID);
    Database::unlink(Keys::BUSINESS_USER . ':' . $this->requesterUUID);
    Database::unlink(Keys::BUSINESS_CONNECTIONS_USER . ':' . $this->ownerUUID);
    Database::unlink(Keys::BUSINESS_CONNECTIONS_USER . ':' . $this->requesterUUID);
    Database::unlink(Keys::BUSINESS_ACCESS_REQUEST_REQUESTER . ':' . $this->requesterUUID);

    $this->cleanupUser($this->ownerUUID, $this->ownerEmail);
    $this->cleanupUser($this->requesterUUID, $this->requesterEmail);
    foreach ($this->seededMembers as $memberUUID => $memberEmail) {
      Database::unlink(Keys::BUSINESS_USER . ':' . $memberUUID);
      Database::unlink(Keys::BUSINESS_CONNECTIONS_USER . ':' . $memberUUID);
      $this->cleanupUser($memberUUID, $memberEmail);
    }

    SystemConfig::set('business_shared_encryption_enabled', $this->originalOrgSharedEncryptionEnabled);
    SystemConfig::set('business_shared_encryption_enable_write', $this->originalOrgSharedEncryptionWriteEnabled);

    parent::tearDown();
  }

  public function testMembersGridRouteSupportsRoleFilterSearchAndSorting(): void
  {
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);
    $requestId = (string) ($requested['data']['request_id'] ?? '');
    $approved = $this->service->approveAccessRequest($this->ownerUUID, $this->businessId, $requestId, [
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'ip' => '127.0.0.1',
      'user_agent' => 'phpunit',
    ]);
    $this->assertTrue($approved['success']);

    $updated = $this->service->updateConnectionRole($this->ownerUUID, $this->businessId, $this->requesterUUID, 'viewer');
    $this->assertTrue($updated['success']);

    $alphaUUID = 'org-member-alpha-' . bin2hex(random_bytes(4));
    $alphaEmail = 'alpha-member-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($alphaUUID, $alphaEmail, 'viewer');

    $zuluUUID = 'org-member-zulu-' . bin2hex(random_bytes(4));
    $zuluEmail = 'zulu-member-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($zuluUUID, $zuluEmail, 'viewer');

    $payload = $this->invokeControllerRoute('listMembersGrid', $this->businessId, 'GET', [], [
      'role' => 'viewer',
      'search' => 'member-',
      'sort' => 'email',
      'direction' => 'asc',
    ]);

    $this->assertSame('success', $payload['status'] ?? null, json_encode($payload));
    $html = (string) ($payload['data']['html'] ?? '');
    $this->assertStringContainsString($alphaEmail, $html);
    $this->assertStringContainsString($zuluEmail, $html);
    $this->assertStringNotContainsString($this->ownerEmail, $html);
    $this->assertLessThan(
      strpos($html, $zuluEmail),
      strpos($html, $alphaEmail),
      'Expected ascending email sort in rendered grid output.'
    );
  }

  public function testMembersGridRouteSupportsPaginationMetadata(): void
  {
    for ($i = 0; $i < 30; $i += 1) {
      $memberUUID = 'org-member-page-' . $i . '-' . bin2hex(random_bytes(3));
      $memberEmail = 'page-member-' . $i . '-' . bin2hex(random_bytes(2)) . '@example.com';
      $this->seedActiveMember($memberUUID, $memberEmail, 'viewer');
    }

    $payload = $this->invokeControllerRoute('listMembersGrid', $this->businessId, 'GET', [], [
      'page' => '2',
      'sort' => 'email',
      'direction' => 'asc',
    ]);

    $this->assertSame('success', $payload['status'] ?? null);
    $html = (string) ($payload['data']['html'] ?? '');
    $this->assertStringContainsString('data-page="1"', $html);
    $this->assertStringContainsString('data-total-pages="1"', $html);
    $this->assertStringContainsString('data-pagination-total="31"', $html);
  }

  public function testMembersGridRouteDeniedForNonPrivilegedSession(): void
  {
    $this->seedConnection($this->requesterUUID, 'member', 'work.read');

    $payload = $this->invokeControllerRoute('listMembersGrid', $this->businessId, 'GET', [], [], $this->requesterSession);
    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testRevokeInviteRouteDeniedForNonPrivilegedSessionReturnsForbidden(): void
  {
    $inviteEmail = 'member-denied-invite-' . bin2hex(random_bytes(3)) . '@example.com';
    $invite = $this->service->sendInvite($this->ownerUUID, $this->businessId, $inviteEmail, ['work.read']);
    $this->assertTrue($invite['success']);
    $inviteId = (string) ($invite['data']['invite_id'] ?? '');
    $this->assertNotSame('', $inviteId);

    $this->seedConnection($this->requesterUUID, 'member', 'work.read');

    $payload = $this->invokeControllerRoute('revokeInvite', $this->businessId, 'POST', [
      'invite_id' => $inviteId,
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testMembersGridRouteDeniedForViewerSession(): void
  {
    $this->seedConnection($this->requesterUUID, 'viewer', 'work.read,sites.read');

    $payload = $this->invokeControllerRoute('listMembersGrid', $this->businessId, 'GET', [], [], $this->requesterSession);
    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testRevokeInviteRouteDeniedForViewerSessionReturnsForbidden(): void
  {
    $inviteEmail = 'viewer-denied-invite-' . bin2hex(random_bytes(3)) . '@example.com';
    $invite = $this->service->sendInvite($this->ownerUUID, $this->businessId, $inviteEmail, ['work.read']);
    $this->assertTrue($invite['success']);
    $inviteId = (string) ($invite['data']['invite_id'] ?? '');
    $this->assertNotSame('', $inviteId);

    $this->seedConnection($this->requesterUUID, 'viewer', 'work.read,sites.read');

    $payload = $this->invokeControllerRoute('revokeInvite', $this->businessId, 'POST', [
      'invite_id' => $inviteId,
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testGenerateAuditControlTestRouteDeniedForViewerSession(): void
  {
    $this->seedConnection($this->requesterUUID, 'viewer', 'work.read,sites.read');

    $payload = $this->invokeControllerRoute('generateAuditControlTest', $this->businessId, 'POST', [
      'summary' => 'Viewer should be denied',
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testPrepareInviteImportEnforcesAuthorityDomain(): void
  {
    $payload = $this->invokeControllerRoute('prepareInviteImport', $this->businessId, 'POST', [
      'emails' => "allowed-user@example.com\noutside-user@outside.net",
      'scopes' => ['work.read', 'sites.read'],
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $payload['status'] ?? null);
    $summary = is_array($payload['data']['summary'] ?? null) ? $payload['data']['summary'] : [];
    $this->assertGreaterThanOrEqual(1, (int) ($summary['input_count'] ?? 0));
    $this->assertGreaterThanOrEqual(1, (int) ($summary['wrong_domain_count'] ?? 0) + (int) ($summary['invalid_count'] ?? 0));
    $this->assertNotSame('', (string) ($payload['data']['import_id'] ?? ''));
  }

  public function testPrepareInviteImportRequiresVerifiedActorEmail(): void
  {
    Database::hset(Keys::USER . ':' . $this->ownerUUID, [
      'email_verified' => '0',
    ]);

    $payload = $this->invokeControllerRoute('prepareInviteImport', $this->businessId, 'POST', [
      'emails' => 'allowed-user@example.com',
      'scopes' => ['work.read'],
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertNotSame('success', $payload['status'] ?? null);
  }

  public function testPrepareInviteImportPreservesMultilineInputCounts(): void
  {
    $chunks = [
      'import-a@example.com',
      'import-b@example.com',
      'import-c@example.com',
      'import-d@example.com',
      'import-e@example.com',
    ];

    $payload = $this->invokeControllerRoute('prepareInviteImport', $this->businessId, 'POST', [
      'emails' => implode("\n", $chunks),
      'emails_chunks' => $chunks,
      'scopes' => ['work.read'],
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $payload['status'] ?? null);
    $summary = is_array($payload['data']['summary'] ?? null) ? $payload['data']['summary'] : [];

    $this->assertSame(5, (int) ($summary['input_count'] ?? 0));
    $this->assertSame(5, (int) ($summary['accepted_count'] ?? 0));
    $this->assertSame(0, (int) ($summary['invalid_count'] ?? 0));
  }

  public function testPrepareInviteImportRejectsMalformedChunkPayload(): void
  {
    $payload = $this->invokeControllerRoute('prepareInviteImport', $this->businessId, 'POST', [
      'emails' => 'valid@example.com',
      'emails_chunks' => 'not-an-array',
      'scopes' => ['work.read'],
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertStringContainsString('Malformed import payload', (string) ($payload['message'] ?? ''));

    $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
    $malformed = is_array($data['malformed_fields'] ?? null) ? $data['malformed_fields'] : [];
    $this->assertContains('emails_chunks', $malformed);
  }

  public function testBulkInviteImportFlowRequiresVerifiedChallengeBeforeCommit(): void
  {
    $prepare = $this->invokeControllerRoute('prepareInviteImport', $this->businessId, 'POST', [
      'emails' => 'bulk-member-' . bin2hex(random_bytes(3)) . '@example.com',
      'scopes' => ['work.read', 'sites.read'],
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $prepare['status'] ?? null);
    $importId = (string) ($prepare['data']['import_id'] ?? '');
    $this->assertNotSame('', $importId);

    $challengeStart = $this->invokeControllerRoute('startInviteImportChallenge', $this->businessId, 'POST', [
      'import_id' => $importId,
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $challengeStart['status'] ?? null);
    $challengeId = (string) ($challengeStart['data']['challenge_id'] ?? '');
    $this->assertNotSame('', $challengeId);

    $commitBeforeVerify = $this->invokeControllerRoute('commitInviteImport', $this->businessId, 'POST', [
      'import_id' => $importId,
      'challenge_id' => $challengeId,
      'csrf_token' => 'test-csrf',
    ]);
    $this->assertNotSame('success', $commitBeforeVerify['status'] ?? null);

    $code = (string) ($challengeStart['data']['test_code'] ?? '');
    $this->assertNotSame('', $code, 'Unable to derive verification code for test challenge.');

    $verify = $this->invokeControllerRoute('verifyInviteImportChallenge', $this->businessId, 'POST', [
      'import_id' => $importId,
      'challenge_id' => $challengeId,
      'code' => $code,
      'csrf_token' => 'test-csrf',
    ]);
    $this->assertSame('success', $verify['status'] ?? null);

    $commit = $this->invokeControllerRoute('commitInviteImport', $this->businessId, 'POST', [
      'import_id' => $importId,
      'challenge_id' => $challengeId,
      'csrf_token' => 'test-csrf',
    ]);
    $this->assertSame('success', $commit['status'] ?? null);
    $this->assertSame(1, (int) ($commit['data']['success_count'] ?? 0));
  }

  public function testRevokeInviteRemovesRowFromPendingInvitesRoute(): void
  {
    $inviteAEmail = 'revoke-a-' . bin2hex(random_bytes(3)) . '@example.com';
    $inviteBEmail = 'revoke-b-' . bin2hex(random_bytes(3)) . '@example.com';

    $inviteA = $this->service->sendInvite($this->ownerUUID, $this->businessId, $inviteAEmail, ['work.read']);
    $inviteB = $this->service->sendInvite($this->ownerUUID, $this->businessId, $inviteBEmail, ['work.read']);

    $this->assertTrue($inviteA['success']);
    $this->assertTrue($inviteB['success']);

    $inviteAId = (string) ($inviteA['data']['invite_id'] ?? '');
    $this->assertNotSame('', $inviteAId);

    $revokePayload = $this->invokeControllerRoute('revokeInvite', $this->businessId, 'POST', [
      'invite_id' => $inviteAId,
      'csrf_token' => 'test-csrf',
    ]);
    $this->assertSame('success', $revokePayload['status'] ?? null);

    $listPayload = $this->invokeControllerRoute('listInvites', $this->businessId, 'GET');
    $this->assertSame('success', $listPayload['status'] ?? null);

    $rows = is_array($listPayload['data']['invites'] ?? null) ? $listPayload['data']['invites'] : [];
    $emails = array_values(array_map(static fn (array $row): string => (string) ($row['invitee_email'] ?? ''), $rows));
    $statuses = array_values(array_map(static fn (array $row): string => (string) ($row['status'] ?? ''), $rows));

    $this->assertContains($inviteBEmail, $emails);
    $this->assertNotContains($inviteAEmail, $emails);
    $this->assertNotContains('revoked', $statuses);
  }

  public function testInviteHistoryRouteReturnsRevokedInvitesOnly(): void
  {
    $pendingEmail = 'history-pending-' . bin2hex(random_bytes(3)) . '@example.com';
    $revokedEmail = 'history-revoked-' . bin2hex(random_bytes(3)) . '@example.com';

    $pendingInvite = $this->service->sendInvite($this->ownerUUID, $this->businessId, $pendingEmail, ['work.read']);
    $revokedInvite = $this->service->sendInvite($this->ownerUUID, $this->businessId, $revokedEmail, ['work.read']);

    $this->assertTrue($pendingInvite['success']);
    $this->assertTrue($revokedInvite['success']);

    $revokedInviteId = (string) ($revokedInvite['data']['invite_id'] ?? '');
    $this->assertNotSame('', $revokedInviteId);

    $revokePayload = $this->invokeControllerRoute('revokeInvite', $this->businessId, 'POST', [
      'invite_id' => $revokedInviteId,
      'csrf_token' => 'test-csrf',
    ]);
    $this->assertSame('success', $revokePayload['status'] ?? null);

    $historyPayload = $this->invokeControllerRoute('listInviteHistory', $this->businessId, 'GET');
    $this->assertSame('success', $historyPayload['status'] ?? null);

    $rows = is_array($historyPayload['data']['invites'] ?? null) ? $historyPayload['data']['invites'] : [];
    $emails = array_values(array_map(static fn (array $row): string => (string) ($row['invitee_email'] ?? ''), $rows));
    $statuses = array_values(array_map(static fn (array $row): string => (string) ($row['status'] ?? ''), $rows));

    $this->assertContains($revokedEmail, $emails);
    $this->assertNotContains($pendingEmail, $emails);
    $this->assertContains('revoked', $statuses);
    $this->assertNotContains('pending', $statuses);
  }

  public function testListInvitesRouteDeniedForNonPrivilegedSessionReturnsForbidden(): void
  {
    $this->seedConnection($this->requesterUUID, 'member', 'work.read');

    $payload = $this->invokeControllerRoute('listInvites', $this->businessId, 'GET', [], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testListInviteHistoryRouteDeniedForNonPrivilegedSessionReturnsForbidden(): void
  {
    $this->seedConnection($this->requesterUUID, 'member', 'work.read');

    $payload = $this->invokeControllerRoute('listInviteHistory', $this->businessId, 'GET', [], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testListInvitesRouteDeniedForViewerSessionReturnsForbidden(): void
  {
    $this->seedConnection($this->requesterUUID, 'viewer', 'work.read,sites.read');

    $payload = $this->invokeControllerRoute('listInvites', $this->businessId, 'GET', [], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testAcceptInviteRouteCreatesActiveConnectionForInvitee(): void
  {
    $invite = $this->service->sendInvite($this->ownerUUID, $this->businessId, $this->requesterEmail, ['work.read']);
    $this->assertTrue($invite['success']);

    $token = (string) ($invite['data']['invite_token'] ?? '');
    $this->assertNotSame('', $token);

    $payload = $this->invokeControllerRoute('acceptInvite', $this->businessId, 'POST', [
      'invite_token' => $token,
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertSame('success', $payload['status'] ?? null);

    $connection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->requesterUUID);
    $this->assertSame('active', (string) ($connection['status'] ?? ''));
    $this->assertSame('member', (string) ($connection['role'] ?? ''));
    $this->assertStringContainsString('work.read', (string) ($connection['scopes'] ?? ''));
  }

  public function testAcceptInviteRouteRejectsRevokedInviteToken(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);

    $invite = $this->service->sendInvite($this->ownerUUID, $this->businessId, $this->requesterEmail, ['work.read']);
    $this->assertTrue($invite['success']);

    $inviteId = (string) ($invite['data']['invite_id'] ?? '');
    $token = (string) ($invite['data']['invite_token'] ?? '');
    $this->assertNotSame('', $inviteId);
    $this->assertNotSame('', $token);

    $revoked = $this->service->revokeInvite($this->ownerUUID, $this->businessId, $inviteId);
    $this->assertTrue($revoked['success']);

    $payload = $this->invokeControllerRoute('acceptInvite', $this->businessId, 'POST', [
      'invite_token' => $token,
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);

    $connection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->requesterUUID);
    $this->assertSame('revoked', (string) ($connection['status'] ?? ''));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_MEMBERS . ':' . $this->businessId, $this->requesterUUID));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_USER . ':' . $this->requesterUUID, $this->businessId));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_CONNECTIONS . ':' . $this->businessId, $this->requesterUUID));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_CONNECTIONS_USER . ':' . $this->requesterUUID, $this->businessId));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_PENDING . ':' . $this->businessId, $this->requesterUUID));
  }

  public function testListAccessRequestsRouteReturnsPendingRows(): void
  {
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);

    $payload = $this->invokeControllerRoute('listAccessRequests', $this->businessId, 'GET');
    $this->assertSame('success', $payload['status'] ?? null);

    $rows = is_array($payload['data']['requests'] ?? null) ? $payload['data']['requests'] : [];
    $this->assertIsArray($rows);
    if ($rows !== []) {
      $this->assertSame('pending', (string) ($rows[0]['status'] ?? ''));
    }
  }

  public function testListAccessRequestsRouteDeniedForNonPrivilegedSessionReturnsForbidden(): void
  {
    $this->seedConnection($this->requesterUUID, 'member', 'work.read');

    $payload = $this->invokeControllerRoute('listAccessRequests', $this->businessId, 'GET', [], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testListAccessRequestsRouteDeniedForViewerSessionReturnsForbidden(): void
  {
    $this->seedConnection($this->requesterUUID, 'viewer', 'work.read,sites.read');

    $payload = $this->invokeControllerRoute('listAccessRequests', $this->businessId, 'GET', [], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testApproveAccessRequestRouteActivatesConnection(): void
  {
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);
    $requestId = (string) ($requested['data']['request_id'] ?? '');

    $payload = $this->invokeControllerRoute('approveAccessRequest', $this->businessId, 'POST', [
      'request_id' => $requestId,
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $payload['status'] ?? null);

    $connection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->requesterUUID);
    $this->assertSame('active', (string) ($connection['status'] ?? ''));
  }

  public function testRejectAccessRequestRouteMarksRequestRejected(): void
  {
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);
    $requestId = (string) ($requested['data']['request_id'] ?? '');

    $payload = $this->invokeControllerRoute('rejectAccessRequest', $this->businessId, 'POST', [
      'request_id' => $requestId,
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $payload['status'] ?? null);

    $request = Database::hgetall(Keys::BUSINESS_ACCESS_REQUEST . ':' . $requestId);
    $this->assertSame('rejected', (string) ($request['status'] ?? ''));
  }

  public function testListConnectionsRouteReturnsMembersPayload(): void
  {
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);
    $requestId = (string) ($requested['data']['request_id'] ?? '');

    $approved = $this->service->approveAccessRequest($this->ownerUUID, $this->businessId, $requestId, [
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'ip' => '127.0.0.1',
      'user_agent' => 'phpunit',
    ]);
    $this->assertTrue($approved['success']);

    $zetaUUID = 'org-member-zeta-' . bin2hex(random_bytes(4));
    $zetaEmail = 'zeta-member-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($zetaUUID, $zetaEmail, 'viewer');

    $alphaUUID = 'org-member-alpha-' . bin2hex(random_bytes(4));
    $alphaEmail = 'alpha-member-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($alphaUUID, $alphaEmail, 'viewer');

    $payload = $this->invokeControllerRoute('listConnections', $this->businessId, 'GET');
    $this->assertSame('success', $payload['status'] ?? null);

    $members = is_array($payload['data']['members'] ?? null) ? $payload['data']['members'] : [];
    $this->assertNotSame([], $members);

    $memberEmails = array_values(array_filter(array_map(static function (mixed $row): string {
      return is_array($row) ? (string) ($row['email'] ?? '') : '';
    }, $members), static fn (string $email): bool => $email !== ''));
    $sortedEmails = $memberEmails;
    usort($sortedEmails, static fn (string $left, string $right): int => strcasecmp($left, $right));
    $this->assertSame($sortedEmails, $memberEmails, 'Expected members payload to be deterministically sorted by display ordering.');

    $requesterRows = array_values(array_filter($members, function (mixed $row): bool {
      return is_array($row) && (string) ($row['user_uuid'] ?? '') === $this->requesterUUID;
    }));

    $this->assertNotSame([], $requesterRows);
    $this->assertSame($this->requesterEmail, (string) ($requesterRows[0]['email'] ?? ''));
  }

  public function testListForCurrentUserAllowsFreeMemberConnectionFeed(): void
  {
    Database::hset(Keys::BUSINESS_SETTINGS . ':' . $this->businessId, [
      'industry' => 'Healthcare',
      'website' => 'https://controller-access.example.com',
      'address_city' => 'Toronto',
      'address_region' => 'ON',
      'address_country' => 'Canada',
      'contact_email' => 'support@controller-access.example.com',
      'support_hours' => 'Mon-Fri 9-5',
    ]);

    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success'], (string) ($requested['message'] ?? ''));

    $payload = $this->invokeControllerMethodWithoutBusiness('listForCurrentUser', 'GET', [], [], $this->requesterSession);
    $this->assertSame('success', $payload['status'] ?? null, json_encode($payload));
    $this->assertSame(200, $payload['__http_code'] ?? null, json_encode($payload));

    $businesses = is_array($payload['data']['businesses'] ?? null)
      ? $payload['data']['businesses']
      : [];

    $matchingRows = array_values(array_filter($businesses, function (mixed $business): bool {
      return is_array($business) && (string) ($business['business_id'] ?? '') === $this->businessId;
    }));

    $this->assertNotSame([], $matchingRows);
    $this->assertSame('pending', (string) ($matchingRows[0]['connection_status'] ?? ''));
  }

  public function testListMemberAuditTimelineReturnsProfileRelatedEventsOnly(): void
  {
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);
    $requestId = (string) ($requested['data']['request_id'] ?? '');

    $approved = $this->service->approveAccessRequest($this->ownerUUID, $this->businessId, $requestId, [
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'ip' => '127.0.0.1',
      'user_agent' => 'phpunit',
    ]);
    $this->assertTrue($approved['success']);

    $updated = $this->service->updateBusinessSettings($this->ownerUUID, $this->businessId, [
      'industry' => 'Unrelated owner update',
    ]);
    $this->assertTrue($updated['success']);

    $payload = $this->invokeControllerRoute('listMemberAuditTimeline', $this->businessId, 'GET', [], [], $this->requesterSession);
    $this->assertSame('success', $payload['status'] ?? null, json_encode($payload));

    $events = is_array($payload['data']['events'] ?? null) ? $payload['data']['events'] : [];
    $this->assertNotSame([], $events);

    $eventTypes = array_values(array_map(static function (mixed $event): string {
      return is_array($event) && is_scalar($event['event_type'] ?? null)
        ? (string) $event['event_type']
        : '';
    }, $events));

    $this->assertContains('access.requested', $eventTypes);
    $this->assertContains('access.request.approved', $eventTypes);
    $this->assertNotContains('settings.updated', $eventTypes);
  }

  public function testRecordMemberReportsAuditHandlesBulkHundredMemberBatch(): void
  {
    $memberUuids = [];
    for ($index = 1; $index <= 100; $index++) {
      $memberUuids[] = sprintf('bulk-member-%03d-%s', $index, substr($this->businessId, -6));
    }

    $payload = $this->invokeControllerRoute('recordMemberReportsAudit', $this->businessId, 'POST', [
      'report_key' => 'bulk_member_reports',
      'report_scope' => 'yearly',
      'year' => '2026',
      'format' => 'zip',
      'delivery' => 'zip',
      'member_count' => '100',
      'succeeded' => '97',
      'failed' => '3',
      'duration_ms' => '12345',
      'generated_at' => '2026-06-18T12:00:00+00:00',
      'event_phase' => 'requested',
      'result' => 'requested',
      'reason' => '',
      'generation_path' => 'mixed_server_authorized_and_browser_convenience',
      'trust_level' => 'mixed_package_server_authorized_pdf_and_browser_convenience_csv',
      'member_uuids' => $memberUuids,
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $payload['status'] ?? null, json_encode($payload));
    $this->assertSame(200, $payload['__http_code'] ?? null, json_encode($payload));

    $event = $this->latestBusinessAuditEventOfType('business.member.report.export.requested');
    $this->assertSame($this->ownerUUID, (string) ($event['actor_uuid'] ?? ''));

    $details = json_decode((string) ($event['details'] ?? '{}'), true);
    $this->assertIsArray($details);
    $this->assertSame('bulk_member_reports', (string) ($details['report_key'] ?? ''));
    $this->assertSame('yearly', (string) ($details['report_scope'] ?? ''));
    $this->assertSame('2026', (string) ($details['year'] ?? ''));
    $this->assertSame('zip', (string) ($details['format'] ?? ''));
    $this->assertSame('100', (string) ($details['member_count'] ?? ''));
    $this->assertSame('', (string) ($details['succeeded'] ?? ''));
    $this->assertSame('', (string) ($details['failed'] ?? ''));
    $this->assertSame('requested', (string) ($details['result'] ?? ''));
    $this->assertSame('', (string) ($details['generation_path'] ?? ''));
    $this->assertSame('', (string) ($details['trust_level'] ?? ''));

    $recordedMembers = array_values(array_filter(explode(',', (string) ($details['member_uuids'] ?? ''))));
    $this->assertCount(100, $recordedMembers);
    $this->assertSame($memberUuids[0], $recordedMembers[0]);
    $this->assertSame($memberUuids[99], $recordedMembers[99]);
  }

  public function testRecordMemberReportsAuditRejectsClientCompletedEvent(): void
  {
    $payload = $this->invokeControllerRoute('recordMemberReportsAudit', $this->businessId, 'POST', [
      'report_key' => 'bulk_member_reports',
      'event_phase' => 'completed',
      'member_uuids' => ['client-forged-complete'],
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('error', $payload['status'] ?? null, json_encode($payload));
    $this->assertSame(403, $payload['__http_code'] ?? null, json_encode($payload));
    $this->assertStringContainsString('server export workflow', (string) ($payload['message'] ?? ''));
  }

  public function testUpdateConnectionRoleRouteUpdatesRoleAndScopes(): void
  {
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);
    $requestId = (string) ($requested['data']['request_id'] ?? '');

    $approved = $this->service->approveAccessRequest($this->ownerUUID, $this->businessId, $requestId, [
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'ip' => '127.0.0.1',
      'user_agent' => 'phpunit',
    ]);
    $this->assertTrue($approved['success']);

    $payload = $this->invokeControllerRoute('updateConnectionRole', $this->businessId, 'POST', [
      'target_user_uuid' => $this->requesterUUID,
      'role' => 'viewer',
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $payload['status'] ?? null);

    $connection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->requesterUUID);
    $this->assertSame('viewer', (string) ($connection['role'] ?? ''));
    $scopeCsv = (string) ($connection['scopes'] ?? '');
    $this->assertStringContainsString('payperiod.read', $scopeCsv);
    $this->assertStringContainsString('sites.read', $scopeCsv);
    $this->assertStringContainsString('work.read', $scopeCsv);
  }

  public function testRevokeConnectionRouteRejectsOwnerTarget(): void
  {
    $payload = $this->invokeControllerRoute('revokeConnection', $this->businessId, 'POST', [
      'target_user_uuid' => $this->ownerUUID,
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertNotSame('success', $payload['status'] ?? null);

    $ownerConnection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->ownerUUID);
    $this->assertSame('owner', (string) ($ownerConnection['role'] ?? ''));
    $this->assertSame('active', (string) ($ownerConnection['status'] ?? ''));
  }

  public function testRevokeConnectionRouteDeniedForNonPrivilegedActor(): void
  {
    $targetUUID = 'org-member-revoke-' . bin2hex(random_bytes(4));
    $targetEmail = 'revoke-target-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($targetUUID, $targetEmail, 'viewer');

    $this->seedConnection($this->requesterUUID, 'member', 'work.read');

    $payload = $this->invokeControllerRoute('revokeConnection', $this->businessId, 'POST', [
      'target_user_uuid' => $targetUUID,
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));

    $targetConnection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $targetUUID);
    $this->assertSame('active', (string) ($targetConnection['status'] ?? ''));
    $this->assertSame('viewer', (string) ($targetConnection['role'] ?? ''));
  }

  public function testTransferOwnershipRoutePromotesTargetAndDemotesCurrentOwner(): void
  {
    $targetUUID = 'org-member-transfer-' . bin2hex(random_bytes(4));
    $targetEmail = 'transfer-member-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($targetUUID, $targetEmail, 'member');

    $payload = $this->invokeControllerRoute('transferOwnership', $this->businessId, 'POST', [
      'target_user_uuid' => $targetUUID,
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $payload['status'] ?? null);

    $org = Database::hgetall(Keys::BUSINESS . ':' . $this->businessId);
    $this->assertSame($targetUUID, (string) ($org['owner_uuid'] ?? ''));

    $formerOwnerConnection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->ownerUUID);
    $this->assertSame('active', (string) ($formerOwnerConnection['status'] ?? ''));
    $this->assertSame('coordinator', (string) ($formerOwnerConnection['role'] ?? ''));

    $newOwnerConnection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $targetUUID);
    $this->assertSame('active', (string) ($newOwnerConnection['status'] ?? ''));
    $this->assertSame('owner', (string) ($newOwnerConnection['role'] ?? ''));
    $this->assertSame('all', (string) ($newOwnerConnection['scopes'] ?? ''));

    $formerOwnerOrgSet = Database::smembers(Keys::BUSINESS_OWNER . ':' . $this->ownerUUID);
    $newOwnerOrgSet = Database::smembers(Keys::BUSINESS_OWNER . ':' . $targetUUID);
    $this->assertNotContains($this->businessId, $formerOwnerOrgSet);
    $this->assertContains($this->businessId, $newOwnerOrgSet);
  }

  public function testTransferOwnershipRouteRejectsNonOwnerActor(): void
  {
    $targetUUID = 'org-member-transfer-denied-' . bin2hex(random_bytes(4));
    $targetEmail = 'transfer-denied-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($targetUUID, $targetEmail, 'member');

    $payload = $this->invokeControllerRoute('transferOwnership', $this->businessId, 'POST', [
      'target_user_uuid' => $targetUUID,
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));

    $org = Database::hgetall(Keys::BUSINESS . ':' . $this->businessId);
    $this->assertSame($this->ownerUUID, (string) ($org['owner_uuid'] ?? ''));
  }

  public function testTransferOwnershipRouteRejectsPendingMemberTarget(): void
  {
    $targetUUID = 'org-member-transfer-pending-' . bin2hex(random_bytes(4));
    $targetEmail = 'transfer-pending-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedUser($targetUUID, $targetEmail);
    $this->seededMembers[$targetUUID] = $targetEmail;

    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $targetUUID, [
      'business_id' => $this->businessId,
      'user_uuid' => $targetUUID,
      'role' => 'member',
      'status' => 'pending',
      'scopes' => 'work.read',
      'updated_at' => date('c'),
    ]);
    Database::sadd(Keys::BUSINESS_CONNECTIONS . ':' . $this->businessId, $targetUUID);
    Database::sadd(Keys::BUSINESS_CONNECTIONS_USER . ':' . $targetUUID, $this->businessId);
    Database::sadd(Keys::BUSINESS_PENDING . ':' . $this->businessId, $targetUUID);

    $payload = $this->invokeControllerRoute('transferOwnership', $this->businessId, 'POST', [
      'target_user_uuid' => $targetUUID,
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertStringContainsString('existing active member', (string) ($payload['message'] ?? ''));

    $org = Database::hgetall(Keys::BUSINESS . ':' . $this->businessId);
    $this->assertSame($this->ownerUUID, (string) ($org['owner_uuid'] ?? ''));
  }

  public function testLeaveBusinessRouteRejectsOwnerUntilOwnershipTransferred(): void
  {
    $payload = $this->invokeControllerRoute('leaveBusiness', $this->businessId, 'POST', [
      'csrf_token' => 'test-csrf',
    ], [], $this->ownerSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));

    $org = Database::hgetall(Keys::BUSINESS . ':' . $this->businessId);
    $this->assertSame($this->ownerUUID, (string) ($org['owner_uuid'] ?? ''));

    $ownerConnection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->ownerUUID);
    $this->assertSame('active', (string) ($ownerConnection['status'] ?? ''));
    $this->assertSame('owner', (string) ($ownerConnection['role'] ?? ''));
  }

  public function testFormerOwnerCanLeaveAfterSuccessfulOwnershipTransfer(): void
  {
    $targetUUID = 'org-member-transfer-leave-' . bin2hex(random_bytes(4));
    $targetEmail = 'transfer-leave-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($targetUUID, $targetEmail, 'member');

    $transferPayload = $this->invokeControllerRoute('transferOwnership', $this->businessId, 'POST', [
      'target_user_uuid' => $targetUUID,
      'csrf_token' => 'test-csrf',
    ], [], $this->ownerSession);
    $this->assertSame('success', $transferPayload['status'] ?? null);

    $leavePayload = $this->invokeControllerRoute('leaveBusiness', $this->businessId, 'POST', [
      'csrf_token' => 'test-csrf',
    ], [], $this->ownerSession);
    $this->assertSame('success', $leavePayload['status'] ?? null);

    $org = Database::hgetall(Keys::BUSINESS . ':' . $this->businessId);
    $this->assertSame($targetUUID, (string) ($org['owner_uuid'] ?? ''));

    $formerOwnerConnection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->ownerUUID);
    $this->assertSame('withdrawn', (string) ($formerOwnerConnection['status'] ?? ''));
  }

  public function testGetAndUpdateSettingsRoutesAllowMemberWithSettingsWriteScope(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);
    $this->seedConnection($this->requesterUUID, 'member', 'business.settings.write,work.read,sites.read');

    $getPayload = $this->invokeControllerRoute('getSettings', $this->businessId, 'GET', [], [], $this->requesterSession);
    $this->assertSame('success', $getPayload['status'] ?? null);

    $updatePayload = $this->invokeControllerRoute('updateSettings', $this->businessId, 'POST', [
      'default_wage' => '37.50',
      'timezone' => 'UTC',
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);
    $this->assertSame('success', $updatePayload['status'] ?? null);

    $settings = Database::hgetall(Keys::BUSINESS_SETTINGS . ':' . $this->businessId);
    $this->assertSame('37.50', (string) ($settings['default_wage'] ?? ''));
    $this->assertSame('UTC', (string) ($settings['timezone'] ?? ''));
  }

  public function testUpdateSettingsRouteRejectsViewerWithoutSettingsWriteScope(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);
    $this->seedConnection($this->requesterUUID, 'viewer', 'work.read,sites.read');

    $payload = $this->invokeControllerRoute('updateSettings', $this->businessId, 'POST', [
      'default_wage' => '42.00',
      'timezone' => 'UTC',
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));

    $settings = Database::hgetall(Keys::BUSINESS_SETTINGS . ':' . $this->businessId);
    $this->assertNotSame('42.00', (string) ($settings['default_wage'] ?? ''));
  }

  public function testUpdateSettingsRouteAllowsContributorPayPeriodControls(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);
    $this->seedConnection($this->requesterUUID, 'contributor', 'payperiod.read,payperiod.write,sites.read,sites.write,work.read,work.scope.business,work.write');

    $payload = $this->invokeControllerRoute('updateSettings', $this->businessId, 'POST', [
      'pay_frequency' => 'weekly',
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertSame('success', $payload['status'] ?? null);

    $settings = Database::hgetall(Keys::BUSINESS_SETTINGS . ':' . $this->businessId);
    $this->assertSame('weekly', (string) ($settings['pay_frequency'] ?? ''));
    $this->assertSame('7', (string) ($settings['pay_period_length'] ?? ''));
  }

  public function testUpdateSettingsRouteRejectsContributorNonPayPeriodMutation(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);
    $this->seedConnection($this->requesterUUID, 'contributor', 'payperiod.read,payperiod.write,sites.read,sites.write,work.read,work.scope.business,work.write');

    $payload = $this->invokeControllerRoute('updateSettings', $this->businessId, 'POST', [
      'name' => 'Contributor Should Not Rename Org',
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testListAccessRequestsRouteDeniedForContributorSessionReturnsForbidden(): void
  {
    $this->seedConnection($this->requesterUUID, 'contributor', 'payperiod.read,payperiod.write,sites.read,sites.write,work.read,work.scope.business,work.write');

    $payload = $this->invokeControllerRoute('listAccessRequests', $this->businessId, 'GET', [], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testManagerRoleCanListAccessRequests(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);
    $this->seedConnection($this->requesterUUID, 'coordinator', 'access.manage,audit.read,business.settings.read,business.settings.write,payperiod.read,payperiod.write,sites.read,sites.write,wage.read,wage.write,work.read,work.scope.business,work.write');

    $pendingUserUUID = 'org-manager-pending-' . bin2hex(random_bytes(4));
    $pendingUserEmail = 'manager-pending-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedUser($pendingUserUUID, $pendingUserEmail);
    $this->seededMembers[$pendingUserUUID] = $pendingUserEmail;

    $requested = $this->service->requestAccessByOwnerEmail($pendingUserUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);

    $payload = $this->invokeControllerRoute('listAccessRequests', $this->businessId, 'GET', [], [], $this->requesterSession);

    $this->assertSame('success', $payload['status'] ?? null);
    $rows = is_array($payload['data']['requests'] ?? null) ? $payload['data']['requests'] : [];
    $emails = array_values(array_map(static fn (array $row): string => (string) ($row['requester_contact_email'] ?? ''), $rows));
    $this->assertContains($pendingUserEmail, $emails);
  }

  public function testManagerRoleCanUpdateBusinessSettings(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);
    $this->seedConnection($this->requesterUUID, 'coordinator', 'access.manage,audit.read,business.settings.read,business.settings.write,payperiod.read,payperiod.write,sites.read,sites.write,wage.read,wage.write,work.read,work.scope.business,work.write');

    $payload = $this->invokeControllerRoute('updateSettings', $this->businessId, 'POST', [
      'default_wage' => '55.25',
      'timezone' => 'UTC',
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertSame('success', $payload['status'] ?? null);

    $settings = Database::hgetall(Keys::BUSINESS_SETTINGS . ':' . $this->businessId);
    $this->assertSame('55.25', (string) ($settings['default_wage'] ?? ''));
    $this->assertSame('UTC', (string) ($settings['timezone'] ?? ''));
  }

  public function testManagerRoleCannotTransferOwnership(): void
  {
    $this->seedConnection($this->requesterUUID, 'coordinator', 'access.manage,audit.read,business.settings.read,business.settings.write,payperiod.read,payperiod.write,sites.read,sites.write,wage.read,wage.write,work.read,work.scope.business,work.write');

    $targetUUID = 'org-manager-transfer-target-' . bin2hex(random_bytes(4));
    $targetEmail = 'manager-transfer-target-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($targetUUID, $targetEmail, 'member');

    $payload = $this->invokeControllerRoute('transferOwnership', $this->businessId, 'POST', [
      'target_user_uuid' => $targetUUID,
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));

    $org = Database::hgetall(Keys::BUSINESS . ':' . $this->businessId);
    $this->assertSame($this->ownerUUID, (string) ($org['owner_uuid'] ?? ''));
  }

  public function testNonPremiumPersonalOwnerCanUpdatePersonalSettings(): void
  {
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID);

    $personal = $this->service->createBusiness($this->ownerUUID, 'Owner Personal Org', [
      'business_type' => 'personal',
    ]);
    $this->assertTrue($personal['success']);
    $personalOrgId = (string) ($personal['data']['business_id'] ?? '');
    $this->assertNotSame('', $personalOrgId);

    $payload = $this->invokeControllerRoute('updateSettings', $personalOrgId, 'POST', [
      'default_wage' => '24.00',
      'timezone' => 'UTC',
      'csrf_token' => 'test-csrf',
    ], [], $this->ownerSession);

    $this->assertSame('success', $payload['status'] ?? null);

    $settings = Database::hgetall(Keys::BUSINESS_SETTINGS . ':' . $personalOrgId);
    $this->assertSame('24.00', (string) ($settings['default_wage'] ?? ''));
    $this->assertSame('UTC', (string) ($settings['timezone'] ?? ''));

    $business = Database::hgetall(Keys::BUSINESS . ':' . $personalOrgId);
    $this->assertSame('personal', (string) ($business['business_type'] ?? ''));
  }

  public function testNonPremiumPersonalOwnerCannotUpgradeBusinessTypeToShared(): void
  {
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID);

    $personal = $this->service->createBusiness($this->ownerUUID, 'Owner Personal Org', [
      'business_type' => 'personal',
    ]);
    $this->assertTrue($personal['success']);
    $personalOrgId = (string) ($personal['data']['business_id'] ?? '');
    $this->assertNotSame('', $personalOrgId);

    $payload = $this->invokeControllerRoute('updateSettings', $personalOrgId, 'POST', [
      'business_type' => 'shared',
      'csrf_token' => 'test-csrf',
    ], [], $this->ownerSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));

    $business = Database::hgetall(Keys::BUSINESS . ':' . $personalOrgId);
    $this->assertSame('personal', (string) ($business['business_type'] ?? ''));
  }

  public function testNonPremiumPersonalOwnerCannotLeavePersonalBusiness(): void
  {
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID);

    $personal = $this->service->createBusiness($this->ownerUUID, 'Owner Personal Org', [
      'business_type' => 'personal',
    ]);
    $this->assertTrue($personal['success']);
    $personalOrgId = (string) ($personal['data']['business_id'] ?? '');
    $this->assertNotSame('', $personalOrgId);

    $payload = $this->invokeControllerRoute('leaveBusiness', $personalOrgId, 'POST', [
      'csrf_token' => 'test-csrf',
    ], [], $this->ownerSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
    $this->assertStringContainsString('cannot be deleted or left', strtolower((string) ($payload['message'] ?? '')));
  }

  public function testNonPremiumMemberCanLeaveSharedBusiness(): void
  {
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID);
    $this->seedConnection($this->requesterUUID, 'member', 'work.read,sites.read');

    $payload = $this->invokeControllerRoute('leaveBusiness', $this->businessId, 'POST', [
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertSame('success', $payload['status'] ?? null);

    $connection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->requesterUUID);
    $this->assertSame('withdrawn', (string) ($connection['status'] ?? ''));
  }

  public function testBusinessPathMutationsRequireCsrfToken(): void
  {
    foreach (['markNotificationsRead', 'leaveBusiness'] as $route) {
      $payload = $this->invokeControllerRoute($route, $this->businessId, 'POST');

      $this->assertSame('error', $payload['status'] ?? null, $route);
      $this->assertSame(403, (int) ($payload['__http_code'] ?? 0), $route);
      $this->assertStringContainsString('CSRF', (string) ($payload['message'] ?? ''), $route);
    }
  }

  public function testPersonConnectionApproveRequiresCsrfToken(): void
  {
    $connectionId = 'missing-person-connection-' . bin2hex(random_bytes(4));
    $missingToken = $this->invokeControllerRoute('approvePersonConnection', $connectionId, 'POST');

    $this->assertSame('error', $missingToken['status'] ?? null, json_encode($missingToken));
    $this->assertSame(403, (int) ($missingToken['__http_code'] ?? 0));
    $this->assertStringContainsString('CSRF', (string) ($missingToken['message'] ?? ''));

    $validToken = $this->invokeControllerRoute('approvePersonConnection', $connectionId, 'POST', [
      'csrf_token' => 'test-csrf',
    ]);
    $this->assertSame('error', $validToken['status'] ?? null, json_encode($validToken));
    $this->assertStringNotContainsString('CSRF', (string) ($validToken['message'] ?? ''));
  }

  public function testNotificationsReadAllowsValidCsrfToken(): void
  {
    $payload = $this->invokeControllerRoute('markNotificationsRead', $this->businessId, 'POST', [
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $payload['status'] ?? null, json_encode($payload));
  }

  public function testBusinessGroupPathMutationsRequireCsrfToken(): void
  {
    $groupId = 'csrf-group-' . bin2hex(random_bytes(4));
    $routes = [
      'archiveBusinessGroup',
      'restoreBusinessGroup',
      'deleteBusinessGroup',
    ];

    foreach ($routes as $route) {
      $payload = $this->invokeControllerGroupRoute($route, $this->businessId, $groupId, 'POST');

      $this->assertSame('error', $payload['status'] ?? null, $route);
      $this->assertSame(403, (int) ($payload['__http_code'] ?? 0), $route);
      $this->assertStringContainsString('CSRF', (string) ($payload['message'] ?? ''), $route);
    }
  }

  public function testBusinessGroupArchiveAllowsValidCsrfToken(): void
  {
    $saved = (new BusinessGroupService())->saveGroup($this->ownerUUID, $this->businessId, [
      'name' => 'Controller CSRF Group',
      'description' => 'Regression fixture',
    ]);
    $this->assertTrue($saved['success'], (string) ($saved['message'] ?? ''));
    $group = is_array($saved['data']['group'] ?? null) ? $saved['data']['group'] : [];
    $groupId = (string) ($group['group_id'] ?? '');
    $this->assertNotSame('', $groupId);

    $payload = $this->invokeControllerGroupRoute('archiveBusinessGroup', $this->businessId, $groupId, 'POST', [
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $payload['status'] ?? null, json_encode($payload));
    $stored = Database::hgetall(Keys::businessGroup($this->businessId, $groupId));
    $this->assertSame('archived', (string) ($stored['status'] ?? ''));
  }

  public function testMemberReportJsonPostRoutesRequireCsrfToken(): void
  {
    $export = $this->invokeControllerMemberExportRoute(
      'exportMemberReport',
      $this->businessId,
      $this->requesterUUID,
      'pdf',
      'POST',
    );
    $this->assertSame('error', $export['status'] ?? null, json_encode($export));
    $this->assertSame(403, (int) ($export['__http_code'] ?? 0));
    $this->assertStringContainsString('CSRF', (string) ($export['message'] ?? ''));

    $forecast = $this->invokeControllerMemberForecastRoute(
      'postMemberReportsForecastPreview',
      $this->businessId,
      $this->requesterUUID,
      'POST',
    );
    $this->assertSame('error', $forecast['status'] ?? null, json_encode($forecast));
    $this->assertSame(403, (int) ($forecast['__http_code'] ?? 0));
    $this->assertStringContainsString('CSRF', (string) ($forecast['message'] ?? ''));
  }

  public function testAutoBootstrapBusinessEncryptionRequiresCsrfToken(): void
  {
    $payload = $this->invokeControllerMethodWithoutBusiness('autoBootstrapBusinessEncryption', 'POST');

    $this->assertSame('error', $payload['status'] ?? null, json_encode($payload));
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
    $this->assertStringContainsString('CSRF', (string) ($payload['message'] ?? ''));
  }

  public function testAutoBootstrapBusinessEncryptionAllowsValidCsrfHeader(): void
  {
    $throttleKey = Keys::TELEMETRY . ':business:dek:auto_bootstrap:user:' . $this->ownerUUID;
    Database::unlink($throttleKey);

    try {
      $payload = $this->invokeControllerMethodWithoutBusiness(
        'autoBootstrapBusinessEncryption',
        'POST',
        [],
        [],
        null,
        ['HTTP_X_CSRF_TOKEN' => 'test-csrf'],
      );

      $this->assertSame('success', $payload['status'] ?? null, json_encode($payload));
      $this->assertSame(200, (int) ($payload['__http_code'] ?? 0), json_encode($payload));
    } finally {
      Database::unlink($throttleKey);
    }
  }

  public function testNonPremiumPersonalOwnerCanMutateOwnSitesAndWork(): void
  {
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID);

    $personal = $this->service->createBusiness($this->ownerUUID, 'Owner Personal Org', [
      'business_type' => 'personal',
    ]);
    $this->assertTrue($personal['success']);
    $personalOrgId = (string) ($personal['data']['business_id'] ?? '');
    $this->assertNotSame('', $personalOrgId);

    $canMutateSites = $this->service->canMutateSitesForOwner($this->ownerUUID, $this->ownerUUID);
    $this->assertTrue($canMutateSites);

    $canMutateWork = $this->service->canMutateWorkForOwner($this->ownerUUID, $this->ownerUUID, $personalOrgId);
    $this->assertTrue($canMutateWork);
  }

  public function testWorkMutationRequiresExplicitScopeBoundary(): void
  {
    $targetUUID = 'org-work-target-' . bin2hex(random_bytes(4));
    $targetEmail = 'work-target-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($targetUUID, $targetEmail, 'member');

    $this->seedConnection($this->requesterUUID, 'member', 'work.read,work.write');
    $this->assertFalse($this->service->canMutateWorkForOwner($this->requesterUUID, $targetUUID, $this->businessId));
    $this->assertFalse($this->service->canMutateWorkForOwner($this->requesterUUID, $this->requesterUUID, $this->businessId));

    $this->seedConnection($this->requesterUUID, 'member', 'work.read,work.scope.self,work.write');
    $this->assertTrue($this->service->canMutateWorkForOwner($this->requesterUUID, $this->requesterUUID, $this->businessId));
    $this->assertFalse($this->service->canMutateWorkForOwner($this->requesterUUID, $targetUUID, $this->businessId));

    $this->seedConnection($this->requesterUUID, 'contributor', 'work.read,work.scope.business,work.write');
    $this->assertTrue($this->service->canMutateWorkForOwner($this->requesterUUID, $targetUUID, $this->businessId));

    $this->seedConnection($this->requesterUUID, 'coordinator', 'business.settings.write,work.write');
    $this->assertTrue($this->service->canMutateWorkForOwner($this->requesterUUID, $targetUUID, $this->businessId));
  }

  public function testBusinessSitePathMutationsRequireCsrfToken(): void
  {
    $siteId = 'S' . substr(bin2hex(random_bytes(8)), 0, 9);
    $routes = [
      'unlinkBusinessSite',
      'restoreBusinessSite',
      'archiveBusinessSite',
      'permanentDeleteBusinessSite',
    ];

    foreach ($routes as $route) {
      $payload = $this->invokeControllerSiteRoute($route, $this->businessId, $this->ownerUUID, $siteId, 'POST');

      $this->assertSame('error', $payload['status'] ?? null, $route);
      $this->assertSame(403, (int) ($payload['__http_code'] ?? 0), $route);
      $this->assertStringContainsString('CSRF', (string) ($payload['message'] ?? ''), $route);
    }
  }

  public function testBusinessSitePathUnlinkAllowsValidCsrfToken(): void
  {
    $create = $this->service->createBusinessSite($this->ownerUUID, $this->businessId, [
      'site_name' => 'Controller CSRF Site',
      'wage' => '45',
      'living_out_allowance' => '0',
      'travel_hours' => '0',
      'province' => 'AB',
    ]);
    $this->assertTrue($create['success'], (string) ($create['message'] ?? ''));
    $siteId = (string) ($create['data']['site_id'] ?? '');
    $this->assertNotSame('', $siteId);
    $siteRef = $this->ownerUUID . ':' . $siteId;

    try {
      $payload = $this->invokeControllerSiteRoute(
        'unlinkBusinessSite',
        $this->businessId,
        $this->ownerUUID,
        $siteId,
        'POST',
        ['csrf_token' => 'test-csrf'],
      );

      $this->assertSame('success', $payload['status'] ?? null, json_encode($payload));
      $this->assertSame(0, Database::sismember(Keys::BUSINESS_SITE . ':' . $this->businessId, $siteRef));
    } finally {
      Database::unlink(Keys::SITE . ':' . $this->ownerUUID . ':' . $siteId);
      Database::unlink(Keys::BUSINESS_SITE_SETTINGS . ':' . $this->businessId . ':' . $siteRef);
    }
  }

  /**
   * @return array<string, string>
   */
  private function latestBusinessAuditEventOfType(string $eventType): array
  {
    $matches = [];
    foreach (Database::smembers(Keys::BUSINESS_AUDIT . ':' . $this->businessId) as $eventId) {
      $event = Database::hgetall(Keys::BUSINESS_AUDIT_EVENT . ':' . $eventId);
      if ((string) ($event['event_type'] ?? '') === $eventType) {
        $matches[] = $event;
      }
    }

    $this->assertNotSame([], $matches, 'Expected audit event type ' . $eventType . ' for business ' . $this->businessId);
    usort($matches, static function (array $left, array $right): int {
      return strcmp((string) ($left['created_at'] ?? ''), (string) ($right['created_at'] ?? ''));
    });

    /** @var array<string, string> $latest */
    $latest = $matches[count($matches) - 1];

    return $latest;
  }

  /**
   * @param array<string, mixed> $post
   * @return array<string, mixed>
   */
  private function invokeControllerRoute(
    string $method,
    string $businessId,
    string $requestMethod,
    array $post = [],
    array $get = [],
    ?string $sessionOverride = null
  ): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $sessionHash = $sessionOverride ?? $this->ownerSession;
    if (strtoupper($requestMethod) === 'POST' && ($post['csrf_token'] ?? null) === 'test-csrf') {
      $post['csrf_token'] = $this->businessCsrfTokenForSession($sessionHash);
    }

    $cookie = var_export($sessionHash, true);
    $requestMethodLiteral = var_export($requestMethod, true);
    $businessIdLiteral = var_export($businessId, true);
    $postLiteral = var_export($post, true);
    $getLiteral = var_export($get, true);

    $script = 'if (!defined("PHPUNIT_COMPOSER_INSTALL")) { define("PHPUNIT_COMPOSER_INSTALL", "1"); } '
      . 'require ' . $bootstrap . '; '
      . '$_COOKIE["PAYCAL_AUTH"] = ' . $cookie . '; '
      . '$_SERVER["REQUEST_METHOD"] = ' . $requestMethodLiteral . '; '
      . '$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; '
      . '$_POST = ' . $postLiteral . '; '
      . '$_GET = ' . $getLiteral . '; '
      . '$_REQUEST = array_merge($_GET, $_POST); '
      . '$c = new \\PayCal\\Controllers\\BusinessDiscoveryController(); '
      . 'ob_start(); '
      . '$c->' . $method . '(' . $businessIdLiteral . '); '
      . '$out = ob_get_clean(); '
      . '$decoded = json_decode($out, true); '
      . 'if (is_array($decoded)) { $decoded["__http_code"] = (int) http_response_code(); echo json_encode($decoded); } else { echo $out; }';

    $cmd = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script);
    $output = shell_exec($cmd);

    $this->assertNotFalse($output, 'Controller subprocess call failed.');
    $payload = json_decode((string) $output, true);
    $this->assertIsArray($payload, 'Controller response was not valid JSON.');

    return $payload;
  }

  /**
   * @param array<string, mixed> $post
   * @return array<string, mixed>
   */
  private function invokeControllerSiteRoute(
    string $method,
    string $businessId,
    string $siteOwnerUUID,
    string $siteID,
    string $requestMethod,
    array $post = [],
    array $get = [],
    ?string $sessionOverride = null
  ): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $sessionHash = $sessionOverride ?? $this->ownerSession;
    if (strtoupper($requestMethod) === 'POST' && ($post['csrf_token'] ?? null) === 'test-csrf') {
      $post['csrf_token'] = $this->businessCsrfTokenForSession($sessionHash);
    }

    $cookie = var_export($sessionHash, true);
    $requestMethodLiteral = var_export($requestMethod, true);
    $businessIdLiteral = var_export($businessId, true);
    $siteOwnerLiteral = var_export($siteOwnerUUID, true);
    $siteIDLiteral = var_export($siteID, true);
    $postLiteral = var_export($post, true);
    $getLiteral = var_export($get, true);

    $script = 'if (!defined("PHPUNIT_COMPOSER_INSTALL")) { define("PHPUNIT_COMPOSER_INSTALL", "1"); } '
      . 'require ' . $bootstrap . '; '
      . '$_COOKIE["PAYCAL_AUTH"] = ' . $cookie . '; '
      . '$_SERVER["REQUEST_METHOD"] = ' . $requestMethodLiteral . '; '
      . '$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; '
      . '$_POST = ' . $postLiteral . '; '
      . '$_GET = ' . $getLiteral . '; '
      . '$_REQUEST = array_merge($_GET, $_POST); '
      . '$c = new \\PayCal\\Controllers\\BusinessDiscoveryController(); '
      . 'ob_start(); '
      . '$c->' . $method . '(' . $businessIdLiteral . ', ' . $siteOwnerLiteral . ', ' . $siteIDLiteral . '); '
      . '$out = ob_get_clean(); '
      . '$decoded = json_decode($out, true); '
      . 'if (is_array($decoded)) { $decoded["__http_code"] = (int) http_response_code(); echo json_encode($decoded); } else { echo $out; }';

    $cmd = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script);
    $output = shell_exec($cmd);

    $this->assertNotFalse($output, 'Controller subprocess call failed.');
    $payload = json_decode((string) $output, true);
    $this->assertIsArray($payload, 'Controller response was not valid JSON.');

    return $payload;
  }

  /**
   * @param array<string, mixed> $post
   * @return array<string, mixed>
   */
  private function invokeControllerGroupRoute(
    string $method,
    string $businessId,
    string $groupId,
    string $requestMethod,
    array $post = [],
    array $get = [],
    ?string $sessionOverride = null
  ): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $sessionHash = $sessionOverride ?? $this->ownerSession;
    if (strtoupper($requestMethod) === 'POST' && ($post['csrf_token'] ?? null) === 'test-csrf') {
      $post['csrf_token'] = $this->businessCsrfTokenForSession($sessionHash);
    }

    $cookie = var_export($sessionHash, true);
    $requestMethodLiteral = var_export($requestMethod, true);
    $businessIdLiteral = var_export($businessId, true);
    $groupIdLiteral = var_export($groupId, true);
    $postLiteral = var_export($post, true);
    $getLiteral = var_export($get, true);

    $script = 'if (!defined("PHPUNIT_COMPOSER_INSTALL")) { define("PHPUNIT_COMPOSER_INSTALL", "1"); } '
      . 'require ' . $bootstrap . '; '
      . '$_COOKIE["PAYCAL_AUTH"] = ' . $cookie . '; '
      . '$_SERVER["REQUEST_METHOD"] = ' . $requestMethodLiteral . '; '
      . '$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; '
      . '$_POST = ' . $postLiteral . '; '
      . '$_GET = ' . $getLiteral . '; '
      . '$_REQUEST = array_merge($_GET, $_POST); '
      . '$c = new \\PayCal\\Controllers\\BusinessDiscoveryController(); '
      . 'ob_start(); '
      . '$c->' . $method . '(' . $businessIdLiteral . ', ' . $groupIdLiteral . '); '
      . '$out = ob_get_clean(); '
      . '$decoded = json_decode($out, true); '
      . 'if (is_array($decoded)) { $decoded["__http_code"] = (int) http_response_code(); echo json_encode($decoded); } else { echo $out; }';

    $cmd = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script);
    $output = shell_exec($cmd);

    $this->assertNotFalse($output, 'Controller subprocess call failed.');
    $payload = json_decode((string) $output, true);
    $this->assertIsArray($payload, 'Controller response was not valid JSON.');

    return $payload;
  }

  /**
   * @param array<string, mixed> $post
   * @return array<string, mixed>
   */
  private function invokeControllerMemberExportRoute(
    string $method,
    string $businessId,
    string $memberUUID,
    string $format,
    string $requestMethod,
    array $post = [],
    array $get = [],
    ?string $sessionOverride = null
  ): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $sessionHash = $sessionOverride ?? $this->ownerSession;
    if (strtoupper($requestMethod) === 'POST' && ($post['csrf_token'] ?? null) === 'test-csrf') {
      $post['csrf_token'] = $this->businessCsrfTokenForSession($sessionHash);
    }

    $cookie = var_export($sessionHash, true);
    $requestMethodLiteral = var_export($requestMethod, true);
    $businessIdLiteral = var_export($businessId, true);
    $memberUUIDLiteral = var_export($memberUUID, true);
    $formatLiteral = var_export($format, true);
    $postLiteral = var_export($post, true);
    $getLiteral = var_export($get, true);

    $script = 'if (!defined("PHPUNIT_COMPOSER_INSTALL")) { define("PHPUNIT_COMPOSER_INSTALL", "1"); } '
      . 'require ' . $bootstrap . '; '
      . '$_COOKIE["PAYCAL_AUTH"] = ' . $cookie . '; '
      . '$_SERVER["REQUEST_METHOD"] = ' . $requestMethodLiteral . '; '
      . '$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; '
      . '$_POST = ' . $postLiteral . '; '
      . '$_GET = ' . $getLiteral . '; '
      . '$_REQUEST = array_merge($_GET, $_POST); '
      . '$c = new \\PayCal\\Controllers\\BusinessDiscoveryController(); '
      . 'ob_start(); '
      . '$c->' . $method . '(' . $businessIdLiteral . ', ' . $memberUUIDLiteral . ', ' . $formatLiteral . '); '
      . '$out = ob_get_clean(); '
      . '$decoded = json_decode($out, true); '
      . 'if (is_array($decoded)) { $decoded["__http_code"] = (int) http_response_code(); echo json_encode($decoded); } else { echo $out; }';

    $cmd = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script);
    $output = shell_exec($cmd);

    $this->assertNotFalse($output, 'Controller subprocess call failed.');
    $payload = json_decode((string) $output, true);
    $this->assertIsArray($payload, 'Controller response was not valid JSON.');

    return $payload;
  }

  /**
   * @param array<string, mixed> $post
   * @return array<string, mixed>
   */
  private function invokeControllerMemberForecastRoute(
    string $method,
    string $businessId,
    string $memberUUID,
    string $requestMethod,
    array $post = [],
    array $get = [],
    ?string $sessionOverride = null
  ): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $sessionHash = $sessionOverride ?? $this->ownerSession;
    if (strtoupper($requestMethod) === 'POST' && ($post['csrf_token'] ?? null) === 'test-csrf') {
      $post['csrf_token'] = $this->businessCsrfTokenForSession($sessionHash);
    }

    $cookie = var_export($sessionHash, true);
    $requestMethodLiteral = var_export($requestMethod, true);
    $businessIdLiteral = var_export($businessId, true);
    $memberUUIDLiteral = var_export($memberUUID, true);
    $postLiteral = var_export($post, true);
    $getLiteral = var_export($get, true);

    $script = 'if (!defined("PHPUNIT_COMPOSER_INSTALL")) { define("PHPUNIT_COMPOSER_INSTALL", "1"); } '
      . 'require ' . $bootstrap . '; '
      . '$_COOKIE["PAYCAL_AUTH"] = ' . $cookie . '; '
      . '$_SERVER["REQUEST_METHOD"] = ' . $requestMethodLiteral . '; '
      . '$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; '
      . '$_POST = ' . $postLiteral . '; '
      . '$_GET = ' . $getLiteral . '; '
      . '$_REQUEST = array_merge($_GET, $_POST); '
      . '$c = new \\PayCal\\Controllers\\BusinessDiscoveryController(); '
      . 'ob_start(); '
      . '$c->' . $method . '(' . $businessIdLiteral . ', ' . $memberUUIDLiteral . '); '
      . '$out = ob_get_clean(); '
      . '$decoded = json_decode($out, true); '
      . 'if (is_array($decoded)) { $decoded["__http_code"] = (int) http_response_code(); echo json_encode($decoded); } else { echo $out; }';

    $cmd = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script);
    $output = shell_exec($cmd);

    $this->assertNotFalse($output, 'Controller subprocess call failed.');
    $payload = json_decode((string) $output, true);
    $this->assertIsArray($payload, 'Controller response was not valid JSON.');

    return $payload;
  }

  /**
   * @param array<string, string> $post
   * @param array<string, string> $server
   * @return array<string, mixed>
   */
  private function invokeControllerMethodWithoutBusiness(
    string $method,
    string $requestMethod,
    array $post = [],
    array $get = [],
    ?string $sessionOverride = null,
    array $server = []
  ): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $sessionHash = $sessionOverride ?? $this->ownerSession;
    if (strtoupper($requestMethod) === 'POST') {
      $csrfNonce = null;
      if (($post['csrf_token'] ?? null) === 'test-csrf' || ($server['HTTP_X_CSRF_TOKEN'] ?? null) === 'test-csrf') {
        $csrfNonce = $this->businessCsrfTokenForSession($sessionHash);
      }
      if (($post['csrf_token'] ?? null) === 'test-csrf') {
        $post['csrf_token'] = (string) $csrfNonce;
      }
      if (($server['HTTP_X_CSRF_TOKEN'] ?? null) === 'test-csrf') {
        $server['HTTP_X_CSRF_TOKEN'] = (string) $csrfNonce;
      }
    }

    $cookie = var_export($sessionHash, true);
    $requestMethodLiteral = var_export($requestMethod, true);
    $postLiteral = var_export($post, true);
    $getLiteral = var_export($get, true);
    $serverLiteral = var_export($server, true);

    $script = 'if (!defined("PHPUNIT_COMPOSER_INSTALL")) { define("PHPUNIT_COMPOSER_INSTALL", "1"); } '
      . 'require ' . $bootstrap . '; '
      . '$_COOKIE["PAYCAL_AUTH"] = ' . $cookie . '; '
      . '$_SERVER["REQUEST_METHOD"] = ' . $requestMethodLiteral . '; '
      . '$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; '
      . '$_SERVER = array_merge($_SERVER, ' . $serverLiteral . '); '
      . '$_POST = ' . $postLiteral . '; '
      . '$_GET = ' . $getLiteral . '; '
      . '$_REQUEST = array_merge($_GET, $_POST); '
      . '$c = new \\PayCal\\Controllers\\BusinessDiscoveryController(); '
      . 'ob_start(); '
      . '$c->' . $method . '(); '
      . '$out = ob_get_clean(); '
      . '$decoded = json_decode($out, true); '
      . 'if (is_array($decoded)) { $decoded["__http_code"] = (int) http_response_code(); echo json_encode($decoded); } else { echo $out; }';

    $cmd = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script);
    $output = shell_exec($cmd);

    $this->assertNotFalse($output, 'Controller subprocess call failed.');
    $payload = json_decode((string) $output, true);
    $this->assertIsArray($payload, 'Controller response was not valid JSON.');

    return $payload;
  }

  private function businessCsrfTokenForSession(string $sessionHash): string
  {
    $userUUID = (string) Database::hget(Keys::SESSION . ':' . $sessionHash, 'user_uuid');
    $this->assertNotSame('', $userUUID, 'Expected session user for CSRF fixture.');

    $nonce = bin2hex(random_bytes(32));
    Database::set('user:' . $userUUID . ':csrf:businesses:' . $nonce, (string) time(), 3600);

    return $nonce;
  }

  private function seedUser(string $userUUID, string $email): void
  {
    Database::hset(Keys::USER . ':' . $userUUID, [
      'user_uuid' => $userUUID,
      'email' => $email,
      'full_name' => 'Integration Test User',
      'email_verified' => '1',
      'auth_level' => AuthLevel::USER->value,
    ]);

    UserRepository::setUserEmail($userUUID, $email);
  }

  private function cleanupUser(string $userUUID, string $email): void
  {
    Database::unlink(Keys::USER . ':' . $userUUID);
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $userUUID);
    Database::unlink(Keys::EMAIL . ':' . $email);
    Database::unlink(Keys::EMAIL . $email);
  }

  private function cleanupBusinessArtifacts(string $orgId): void
  {
    $auditSetKey = Keys::BUSINESS_AUDIT . ':' . $orgId;
    foreach (Database::smembers($auditSetKey) as $eventId) {
      Database::unlink(Keys::BUSINESS_AUDIT_EVENT . ':' . $eventId);
    }
    Database::unlink($auditSetKey);

    $requestSetKey = Keys::BUSINESS_ACCESS_REQUEST_BUSINESS . ':' . $orgId;
    foreach (Database::smembers($requestSetKey) as $requestId) {
      $requestKey = Keys::BUSINESS_ACCESS_REQUEST . ':' . $requestId;
      $request = Database::hgetall($requestKey);
      $requesterUUID = (string) ($request['requester_uuid'] ?? '');
      if ($requesterUUID !== '') {
        Database::srem(Keys::BUSINESS_ACCESS_REQUEST_REQUESTER . ':' . $requesterUUID, $requestId);
        Database::unlink(Keys::BUSINESS_ACCESS_REQUEST_ACTIVE . ':' . $orgId . ':' . $requesterUUID);
      }
      Database::unlink($requestKey);
    }
    Database::unlink($requestSetKey);

    $connectionPattern = Keys::BUSINESS_CONNECTION . ':' . $orgId . ':*';
    foreach (Database::scanKeys($connectionPattern) as $connectionKey) {
      Database::unlink((string) $connectionKey);
    }

    Database::unlink(Keys::BUSINESS_MEMBERS . ':' . $orgId);
    Database::unlink(Keys::BUSINESS_CONNECTIONS . ':' . $orgId);
    Database::unlink(Keys::BUSINESS_PENDING . ':' . $orgId);
    Database::unlink(Keys::BUSINESS_INVITE_BUSINESS . ':' . $orgId);
    Database::unlink(Keys::BUSINESS_SITE . ':' . $orgId);
    Database::unlink(Keys::BUSINESS_SETTINGS . ':' . $orgId);
    $groupSetKey = Keys::businessGroups($orgId);
    foreach (Database::smembers($groupSetKey) as $groupIdRaw) {
      $groupId = trim((string) $groupIdRaw);
      if ($groupId === '') {
        continue;
      }
      foreach (Database::smembers(Keys::businessGroupMembers($orgId, $groupId)) as $memberUUID) {
        Database::srem(Keys::businessMemberGroups($orgId, (string) $memberUUID), $groupId);
      }
      Database::unlink(Keys::businessGroup($orgId, $groupId));
      Database::unlink(Keys::businessGroupMembers($orgId, $groupId));
      Database::unlink(Keys::businessGroupMetricsCache($orgId, $groupId));
    }
    Database::unlink($groupSetKey);
    Database::unlink(Keys::BUSINESS . ':' . $orgId);
  }

  private function seedActiveMember(string $memberUUID, string $memberEmail, string $role): void
  {
    $this->seedUser($memberUUID, $memberEmail);
    $this->seededMembers[$memberUUID] = $memberEmail;

    $this->seedConnection($memberUUID, $role, 'work.read');
  }

  private function seedConnection(string $memberUUID, string $role, string $scopes): void
  {
    $scopeCSV = trim($scopes) === '' ? 'work.read' : $scopes;

    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $memberUUID, [
      'business_id' => $this->businessId,
      'user_uuid' => $memberUUID,
      'role' => $role,
      'status' => 'active',
      'scopes' => $scopeCSV,
      'updated_at' => date('c'),
    ]);

    Database::sadd(Keys::BUSINESS_MEMBERS . ':' . $this->businessId, $memberUUID);
    Database::sadd(Keys::BUSINESS_CONNECTIONS . ':' . $this->businessId, $memberUUID);
    Database::sadd(Keys::BUSINESS_USER . ':' . $memberUUID, $this->businessId);
    Database::sadd(Keys::BUSINESS_CONNECTIONS_USER . ':' . $memberUUID, $this->businessId);
  }
}
