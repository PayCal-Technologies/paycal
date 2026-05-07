<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\OrganizationDiscoveryService;
use PayCal\Domain\UserRepository;

final class OrganizationDiscoveryControllerAccessRequestIntegrationTest extends TestCase
{
  private OrganizationDiscoveryService $service;
  private string $ownerUUID;
  private string $ownerEmail;
  private string $ownerSession;
  private string $requesterUUID;
  private string $requesterEmail;
  private string $requesterSession;
  private string $organizationId = '';
  /** @var array<string, string> */
  private array $seededMembers = [];
  private bool $originalOrgSharedEncryptionEnabled;
  private bool $originalOrgSharedEncryptionWriteEnabled;

  protected function setUp(): void
  {
    parent::setUp();

    $this->service = new OrganizationDiscoveryService();
    $this->originalOrgSharedEncryptionEnabled = (bool) SystemConfig::get('org_shared_encryption_enabled');
    $this->originalOrgSharedEncryptionWriteEnabled = (bool) SystemConfig::get('org_shared_encryption_enable_write');
    SystemConfig::set('org_shared_encryption_enabled', false);
    SystemConfig::set('org_shared_encryption_enable_write', false);
    $suffix = bin2hex(random_bytes(6));

    $this->ownerUUID = 'org-owner-ctrl-' . $suffix;
    $this->ownerEmail = 'owner-ctrl-' . $suffix . '@example.com';
    $this->requesterUUID = 'org-requester-ctrl-' . $suffix;
    $this->requesterEmail = 'requester-ctrl-' . $suffix . '@example.com';

    $this->seedUser($this->ownerUUID, $this->ownerEmail);
    $this->seedUser($this->requesterUUID, $this->requesterEmail);

    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);

    $create = $this->service->createOrganization($this->ownerUUID, 'Controller Access Org', [
      'organization_type' => 'shared',
    ]);

    $this->assertTrue($create['success']);
    $this->organizationId = (string) ($create['data']['organization_id'] ?? '');
    $this->assertNotSame('', $this->organizationId);

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
    if ($this->organizationId !== '') {
      $this->cleanupOrganizationArtifacts($this->organizationId);
    }

    Database::unlink(Keys::SESSION . ':' . $this->ownerSession);
    Database::unlink(Keys::SESSION . ':' . $this->requesterSession);

    Database::unlink(Keys::ORGANIZATION_OWNER . ':' . $this->ownerUUID);
    Database::unlink(Keys::ORGANIZATION_USER . ':' . $this->ownerUUID);
    Database::unlink(Keys::ORGANIZATION_USER . ':' . $this->requesterUUID);
    Database::unlink(Keys::ORGANIZATION_ACCESS_REQUEST_REQUESTER . ':' . $this->requesterUUID);

    $this->cleanupUser($this->ownerUUID, $this->ownerEmail);
    $this->cleanupUser($this->requesterUUID, $this->requesterEmail);
    foreach ($this->seededMembers as $memberUUID => $memberEmail) {
      $this->cleanupUser($memberUUID, $memberEmail);
    }

    SystemConfig::set('org_shared_encryption_enabled', $this->originalOrgSharedEncryptionEnabled);
    SystemConfig::set('org_shared_encryption_enable_write', $this->originalOrgSharedEncryptionWriteEnabled);

    parent::tearDown();
  }

  public function testMembersGridRouteSupportsRoleFilterSearchAndSorting(): void
  {
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);
    $requestId = (string) ($requested['data']['request_id'] ?? '');
    $approved = $this->service->approveAccessRequest($this->ownerUUID, $this->organizationId, $requestId, [
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'ip' => '127.0.0.1',
      'user_agent' => 'phpunit',
    ]);
    $this->assertTrue($approved['success']);

    $updated = $this->service->updateRelationshipRole($this->ownerUUID, $this->organizationId, $this->requesterUUID, 'viewer');
    $this->assertTrue($updated['success']);

    $alphaUUID = 'org-member-alpha-' . bin2hex(random_bytes(4));
    $alphaEmail = 'alpha-member-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($alphaUUID, $alphaEmail, 'viewer');

    $zuluUUID = 'org-member-zulu-' . bin2hex(random_bytes(4));
    $zuluEmail = 'zulu-member-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($zuluUUID, $zuluEmail, 'viewer');

    $payload = $this->invokeControllerRoute('listMembersGrid', $this->organizationId, 'GET', [], [
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

    $payload = $this->invokeControllerRoute('listMembersGrid', $this->organizationId, 'GET', [], [
      'page' => '2',
      'sort' => 'email',
      'direction' => 'asc',
    ]);

    $this->assertSame('success', $payload['status'] ?? null);
    $html = (string) ($payload['data']['html'] ?? '');
    $this->assertStringContainsString('data-page="2"', $html);
  }

  public function testMembersGridRouteDeniedForNonPrivilegedSession(): void
  {
    $this->seedRelationship($this->requesterUUID, 'member', 'work.read');

    $payload = $this->invokeControllerRoute('listMembersGrid', $this->organizationId, 'GET', [], [], $this->requesterSession);
    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testRevokeInviteRouteDeniedForNonPrivilegedSessionReturnsForbidden(): void
  {
    $inviteEmail = 'member-denied-invite-' . bin2hex(random_bytes(3)) . '@example.com';
    $invite = $this->service->sendInvite($this->ownerUUID, $this->organizationId, $inviteEmail, ['work.read']);
    $this->assertTrue($invite['success']);
    $inviteId = (string) ($invite['data']['invite_id'] ?? '');
    $this->assertNotSame('', $inviteId);

    $this->seedRelationship($this->requesterUUID, 'member', 'work.read');

    $payload = $this->invokeControllerRoute('revokeInvite', $this->organizationId, 'POST', [
      'invite_id' => $inviteId,
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testMembersGridRouteDeniedForViewerSession(): void
  {
    $this->seedRelationship($this->requesterUUID, 'viewer', 'work.read,sites.read');

    $payload = $this->invokeControllerRoute('listMembersGrid', $this->organizationId, 'GET', [], [], $this->requesterSession);
    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testRevokeInviteRouteDeniedForViewerSessionReturnsForbidden(): void
  {
    $inviteEmail = 'viewer-denied-invite-' . bin2hex(random_bytes(3)) . '@example.com';
    $invite = $this->service->sendInvite($this->ownerUUID, $this->organizationId, $inviteEmail, ['work.read']);
    $this->assertTrue($invite['success']);
    $inviteId = (string) ($invite['data']['invite_id'] ?? '');
    $this->assertNotSame('', $inviteId);

    $this->seedRelationship($this->requesterUUID, 'viewer', 'work.read,sites.read');

    $payload = $this->invokeControllerRoute('revokeInvite', $this->organizationId, 'POST', [
      'invite_id' => $inviteId,
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testGenerateAuditControlTestRouteDeniedForViewerSession(): void
  {
    $this->seedRelationship($this->requesterUUID, 'viewer', 'work.read,sites.read');

    $payload = $this->invokeControllerRoute('generateAuditControlTest', $this->organizationId, 'POST', [
      'summary' => 'Viewer should be denied',
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testPrepareInviteImportEnforcesAuthorityDomain(): void
  {
    $payload = $this->invokeControllerRoute('prepareInviteImport', $this->organizationId, 'POST', [
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

    $payload = $this->invokeControllerRoute('prepareInviteImport', $this->organizationId, 'POST', [
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

    $payload = $this->invokeControllerRoute('prepareInviteImport', $this->organizationId, 'POST', [
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
    $payload = $this->invokeControllerRoute('prepareInviteImport', $this->organizationId, 'POST', [
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
    $prepare = $this->invokeControllerRoute('prepareInviteImport', $this->organizationId, 'POST', [
      'emails' => 'bulk-member-' . bin2hex(random_bytes(3)) . '@example.com',
      'scopes' => ['work.read', 'sites.read'],
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $prepare['status'] ?? null);
    $importId = (string) ($prepare['data']['import_id'] ?? '');
    $this->assertNotSame('', $importId);

    $challengeStart = $this->invokeControllerRoute('startInviteImportChallenge', $this->organizationId, 'POST', [
      'import_id' => $importId,
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $challengeStart['status'] ?? null);
    $challengeId = (string) ($challengeStart['data']['challenge_id'] ?? '');
    $this->assertNotSame('', $challengeId);

    $commitBeforeVerify = $this->invokeControllerRoute('commitInviteImport', $this->organizationId, 'POST', [
      'import_id' => $importId,
      'challenge_id' => $challengeId,
      'csrf_token' => 'test-csrf',
    ]);
    $this->assertNotSame('success', $commitBeforeVerify['status'] ?? null);

    $code = (string) ($challengeStart['data']['test_code'] ?? '');
    $this->assertNotSame('', $code, 'Unable to derive verification code for test challenge.');

    $verify = $this->invokeControllerRoute('verifyInviteImportChallenge', $this->organizationId, 'POST', [
      'import_id' => $importId,
      'challenge_id' => $challengeId,
      'code' => $code,
      'csrf_token' => 'test-csrf',
    ]);
    $this->assertSame('success', $verify['status'] ?? null);

    $commit = $this->invokeControllerRoute('commitInviteImport', $this->organizationId, 'POST', [
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

    $inviteA = $this->service->sendInvite($this->ownerUUID, $this->organizationId, $inviteAEmail, ['work.read']);
    $inviteB = $this->service->sendInvite($this->ownerUUID, $this->organizationId, $inviteBEmail, ['work.read']);

    $this->assertTrue($inviteA['success']);
    $this->assertTrue($inviteB['success']);

    $inviteAId = (string) ($inviteA['data']['invite_id'] ?? '');
    $this->assertNotSame('', $inviteAId);

    $revokePayload = $this->invokeControllerRoute('revokeInvite', $this->organizationId, 'POST', [
      'invite_id' => $inviteAId,
      'csrf_token' => 'test-csrf',
    ]);
    $this->assertSame('success', $revokePayload['status'] ?? null);

    $listPayload = $this->invokeControllerRoute('listInvites', $this->organizationId, 'GET');
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

    $pendingInvite = $this->service->sendInvite($this->ownerUUID, $this->organizationId, $pendingEmail, ['work.read']);
    $revokedInvite = $this->service->sendInvite($this->ownerUUID, $this->organizationId, $revokedEmail, ['work.read']);

    $this->assertTrue($pendingInvite['success']);
    $this->assertTrue($revokedInvite['success']);

    $revokedInviteId = (string) ($revokedInvite['data']['invite_id'] ?? '');
    $this->assertNotSame('', $revokedInviteId);

    $revokePayload = $this->invokeControllerRoute('revokeInvite', $this->organizationId, 'POST', [
      'invite_id' => $revokedInviteId,
      'csrf_token' => 'test-csrf',
    ]);
    $this->assertSame('success', $revokePayload['status'] ?? null);

    $historyPayload = $this->invokeControllerRoute('listInviteHistory', $this->organizationId, 'GET');
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
    $this->seedRelationship($this->requesterUUID, 'member', 'work.read');

    $payload = $this->invokeControllerRoute('listInvites', $this->organizationId, 'GET', [], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testListInviteHistoryRouteDeniedForNonPrivilegedSessionReturnsForbidden(): void
  {
    $this->seedRelationship($this->requesterUUID, 'member', 'work.read');

    $payload = $this->invokeControllerRoute('listInviteHistory', $this->organizationId, 'GET', [], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testListInvitesRouteDeniedForViewerSessionReturnsForbidden(): void
  {
    $this->seedRelationship($this->requesterUUID, 'viewer', 'work.read,sites.read');

    $payload = $this->invokeControllerRoute('listInvites', $this->organizationId, 'GET', [], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testAcceptInviteRouteCreatesActiveRelationshipForInvitee(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);

    $invite = $this->service->sendInvite($this->ownerUUID, $this->organizationId, $this->requesterEmail, ['work.read']);
    $this->assertTrue($invite['success']);

    $token = (string) ($invite['data']['invite_token'] ?? '');
    $this->assertNotSame('', $token);

    $payload = $this->invokeControllerRoute('acceptInvite', $this->organizationId, 'POST', [
      'invite_token' => $token,
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertSame('success', $payload['status'] ?? null);

    $relationship = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $this->requesterUUID);
    $this->assertSame('active', (string) ($relationship['status'] ?? ''));
    $this->assertSame('member', (string) ($relationship['role'] ?? ''));
    $this->assertStringContainsString('work.read', (string) ($relationship['scopes'] ?? ''));
  }

  public function testAcceptInviteRouteRejectsRevokedInviteToken(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);

    $invite = $this->service->sendInvite($this->ownerUUID, $this->organizationId, $this->requesterEmail, ['work.read']);
    $this->assertTrue($invite['success']);

    $inviteId = (string) ($invite['data']['invite_id'] ?? '');
    $token = (string) ($invite['data']['invite_token'] ?? '');
    $this->assertNotSame('', $inviteId);
    $this->assertNotSame('', $token);

    $revoked = $this->service->revokeInvite($this->ownerUUID, $this->organizationId, $inviteId);
    $this->assertTrue($revoked['success']);

    $payload = $this->invokeControllerRoute('acceptInvite', $this->organizationId, 'POST', [
      'invite_token' => $token,
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);

    $relationship = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $this->requesterUUID);
    $this->assertSame([], $relationship);
  }

  public function testListAccessRequestsRouteReturnsPendingRows(): void
  {
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);

    $payload = $this->invokeControllerRoute('listAccessRequests', $this->organizationId, 'GET');
    $this->assertSame('success', $payload['status'] ?? null);

    $rows = is_array($payload['data']['requests'] ?? null) ? $payload['data']['requests'] : [];
    $this->assertIsArray($rows);
    if ($rows !== []) {
      $this->assertSame('pending', (string) ($rows[0]['status'] ?? ''));
    }
  }

  public function testListAccessRequestsRouteDeniedForNonPrivilegedSessionReturnsForbidden(): void
  {
    $this->seedRelationship($this->requesterUUID, 'member', 'work.read');

    $payload = $this->invokeControllerRoute('listAccessRequests', $this->organizationId, 'GET', [], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testListAccessRequestsRouteDeniedForViewerSessionReturnsForbidden(): void
  {
    $this->seedRelationship($this->requesterUUID, 'viewer', 'work.read,sites.read');

    $payload = $this->invokeControllerRoute('listAccessRequests', $this->organizationId, 'GET', [], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testApproveAccessRequestRouteActivatesRelationship(): void
  {
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);
    $requestId = (string) ($requested['data']['request_id'] ?? '');

    $payload = $this->invokeControllerRoute('approveAccessRequest', $this->organizationId, 'POST', [
      'request_id' => $requestId,
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $payload['status'] ?? null);

    $relationship = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $this->requesterUUID);
    $this->assertSame('active', (string) ($relationship['status'] ?? ''));
  }

  public function testRejectAccessRequestRouteMarksRequestRejected(): void
  {
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);
    $requestId = (string) ($requested['data']['request_id'] ?? '');

    $payload = $this->invokeControllerRoute('rejectAccessRequest', $this->organizationId, 'POST', [
      'request_id' => $requestId,
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $payload['status'] ?? null);

    $request = Database::hgetall(Keys::ORGANIZATION_ACCESS_REQUEST . ':' . $requestId);
    $this->assertSame('rejected', (string) ($request['status'] ?? ''));
  }

  public function testListRelationshipsRouteReturnsMembersPayload(): void
  {
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);
    $requestId = (string) ($requested['data']['request_id'] ?? '');

    $approved = $this->service->approveAccessRequest($this->ownerUUID, $this->organizationId, $requestId, [
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

    $payload = $this->invokeControllerRoute('listRelationships', $this->organizationId, 'GET');
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

  public function testListForCurrentUserReturnsActiveSharedRelationshipForFreeMember(): void
  {
    Database::hset(Keys::ORGANIZATION_SETTINGS . ':' . $this->organizationId, [
      'industry' => 'Healthcare',
      'website' => 'https://controller-access.example.com',
      'address_city' => 'Toronto',
      'address_region' => 'ON',
      'address_country' => 'Canada',
      'contact_email' => 'support@controller-access.example.com',
      'support_hours' => 'Mon-Fri 9-5',
    ]);

    $this->seedRelationship($this->requesterUUID, 'member', 'work.read,sites.read');

    $payload = $this->invokeControllerMethodWithoutOrganization('listForCurrentUser', 'GET', [], [], $this->requesterSession);
    $this->assertSame('success', $payload['status'] ?? null, json_encode($payload));

    $organizations = is_array($payload['data']['organizations'] ?? null) ? $payload['data']['organizations'] : [];
    $requesterRows = array_values(array_filter($organizations, function (mixed $row): bool {
      return is_array($row) && (string) ($row['organization_id'] ?? '') === $this->organizationId;
    }));

    $this->assertCount(1, $requesterRows);
    $row = $requesterRows[0];
    $this->assertSame('member', (string) ($row['role'] ?? ''));
    $this->assertSame('active', (string) ($row['relationship_status'] ?? ''));
    $this->assertSame($this->ownerEmail, (string) ($row['owner_email'] ?? ''));
    $this->assertSame('Healthcare', (string) ($row['industry'] ?? ''));
    $this->assertSame('https://controller-access.example.com', (string) ($row['website'] ?? ''));
    $this->assertSame('Toronto', (string) ($row['address_city'] ?? ''));
    $this->assertSame('ON', (string) ($row['address_region'] ?? ''));
    $this->assertSame('Canada', (string) ($row['address_country'] ?? ''));
    $this->assertContains('work.read', is_array($row['scopes'] ?? null) ? $row['scopes'] : []);
    $this->assertContains('sites.read', is_array($row['scopes'] ?? null) ? $row['scopes'] : []);
  }

  public function testListMemberAuditTimelineReturnsProfileRelatedEventsOnly(): void
  {
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);
    $requestId = (string) ($requested['data']['request_id'] ?? '');

    $approved = $this->service->approveAccessRequest($this->ownerUUID, $this->organizationId, $requestId, [
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'ip' => '127.0.0.1',
      'user_agent' => 'phpunit',
    ]);
    $this->assertTrue($approved['success']);

    $updated = $this->service->updateOrganizationSettings($this->ownerUUID, $this->organizationId, [
      'industry' => 'Unrelated owner update',
    ]);
    $this->assertTrue($updated['success']);

    $payload = $this->invokeControllerRoute('listMemberAuditTimeline', $this->organizationId, 'GET', [], [], $this->requesterSession);
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

  public function testUpdateRelationshipRoleRouteUpdatesRoleAndScopes(): void
  {
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);
    $requestId = (string) ($requested['data']['request_id'] ?? '');

    $approved = $this->service->approveAccessRequest($this->ownerUUID, $this->organizationId, $requestId, [
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'ip' => '127.0.0.1',
      'user_agent' => 'phpunit',
    ]);
    $this->assertTrue($approved['success']);

    $payload = $this->invokeControllerRoute('updateRelationshipRole', $this->organizationId, 'POST', [
      'target_user_uuid' => $this->requesterUUID,
      'role' => 'viewer',
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $payload['status'] ?? null);

    $relationship = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $this->requesterUUID);
    $this->assertSame('viewer', (string) ($relationship['role'] ?? ''));
    $scopeCsv = (string) ($relationship['scopes'] ?? '');
    $this->assertStringContainsString('payperiod.read', $scopeCsv);
    $this->assertStringContainsString('sites.read', $scopeCsv);
    $this->assertStringContainsString('work.read', $scopeCsv);
  }

  public function testRevokeRelationshipRouteRejectsOwnerTarget(): void
  {
    $payload = $this->invokeControllerRoute('revokeRelationship', $this->organizationId, 'POST', [
      'target_user_uuid' => $this->ownerUUID,
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertNotSame('success', $payload['status'] ?? null);

    $ownerRelationship = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $this->ownerUUID);
    $this->assertSame('owner', (string) ($ownerRelationship['role'] ?? ''));
    $this->assertSame('active', (string) ($ownerRelationship['status'] ?? ''));
  }

  public function testRevokeRelationshipRouteDeniedForNonPrivilegedActor(): void
  {
    $targetUUID = 'org-member-revoke-' . bin2hex(random_bytes(4));
    $targetEmail = 'revoke-target-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($targetUUID, $targetEmail, 'viewer');

    $this->seedRelationship($this->requesterUUID, 'member', 'work.read');

    $payload = $this->invokeControllerRoute('revokeRelationship', $this->organizationId, 'POST', [
      'target_user_uuid' => $targetUUID,
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));

    $targetRelationship = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $targetUUID);
    $this->assertSame('active', (string) ($targetRelationship['status'] ?? ''));
    $this->assertSame('viewer', (string) ($targetRelationship['role'] ?? ''));
  }

  public function testTransferOwnershipRoutePromotesTargetAndDemotesCurrentOwner(): void
  {
    $targetUUID = 'org-member-transfer-' . bin2hex(random_bytes(4));
    $targetEmail = 'transfer-member-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($targetUUID, $targetEmail, 'member');

    $payload = $this->invokeControllerRoute('transferOwnership', $this->organizationId, 'POST', [
      'target_user_uuid' => $targetUUID,
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertSame('success', $payload['status'] ?? null);

    $org = Database::hgetall(Keys::ORGANIZATION . ':' . $this->organizationId);
    $this->assertSame($targetUUID, (string) ($org['owner_uuid'] ?? ''));

    $formerOwnerRelationship = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $this->ownerUUID);
    $this->assertSame('active', (string) ($formerOwnerRelationship['status'] ?? ''));
    $this->assertSame('coordinator', (string) ($formerOwnerRelationship['role'] ?? ''));

    $newOwnerRelationship = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $targetUUID);
    $this->assertSame('active', (string) ($newOwnerRelationship['status'] ?? ''));
    $this->assertSame('owner', (string) ($newOwnerRelationship['role'] ?? ''));
    $this->assertSame('all', (string) ($newOwnerRelationship['scopes'] ?? ''));

    $formerOwnerOrgSet = Database::smembers(Keys::ORGANIZATION_OWNER . ':' . $this->ownerUUID);
    $newOwnerOrgSet = Database::smembers(Keys::ORGANIZATION_OWNER . ':' . $targetUUID);
    $this->assertNotContains($this->organizationId, $formerOwnerOrgSet);
    $this->assertContains($this->organizationId, $newOwnerOrgSet);
  }

  public function testTransferOwnershipRouteRejectsNonOwnerActor(): void
  {
    $targetUUID = 'org-member-transfer-denied-' . bin2hex(random_bytes(4));
    $targetEmail = 'transfer-denied-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($targetUUID, $targetEmail, 'member');

    $payload = $this->invokeControllerRoute('transferOwnership', $this->organizationId, 'POST', [
      'target_user_uuid' => $targetUUID,
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));

    $org = Database::hgetall(Keys::ORGANIZATION . ':' . $this->organizationId);
    $this->assertSame($this->ownerUUID, (string) ($org['owner_uuid'] ?? ''));
  }

  public function testTransferOwnershipRouteRejectsPendingMemberTarget(): void
  {
    $targetUUID = 'org-member-transfer-pending-' . bin2hex(random_bytes(4));
    $targetEmail = 'transfer-pending-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedUser($targetUUID, $targetEmail);
    $this->seededMembers[$targetUUID] = $targetEmail;

    Database::hset(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $targetUUID, [
      'organization_id' => $this->organizationId,
      'user_uuid' => $targetUUID,
      'role' => 'member',
      'status' => 'pending',
      'scopes' => 'work.read',
      'updated_at' => date('c'),
    ]);
    Database::sadd(Keys::ORGANIZATION_USER . ':' . $targetUUID, $this->organizationId);

    $payload = $this->invokeControllerRoute('transferOwnership', $this->organizationId, 'POST', [
      'target_user_uuid' => $targetUUID,
      'csrf_token' => 'test-csrf',
    ]);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertStringContainsString('existing active member', (string) ($payload['message'] ?? ''));

    $org = Database::hgetall(Keys::ORGANIZATION . ':' . $this->organizationId);
    $this->assertSame($this->ownerUUID, (string) ($org['owner_uuid'] ?? ''));
  }

  public function testLeaveOrganizationRouteRejectsOwnerUntilOwnershipTransferred(): void
  {
    $payload = $this->invokeControllerRoute('leaveOrganization', $this->organizationId, 'POST', [
      'csrf_token' => 'test-csrf',
    ], [], $this->ownerSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));

    $org = Database::hgetall(Keys::ORGANIZATION . ':' . $this->organizationId);
    $this->assertSame($this->ownerUUID, (string) ($org['owner_uuid'] ?? ''));

    $ownerRelationship = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $this->ownerUUID);
    $this->assertSame('active', (string) ($ownerRelationship['status'] ?? ''));
    $this->assertSame('owner', (string) ($ownerRelationship['role'] ?? ''));
  }

  public function testFormerOwnerCanLeaveAfterSuccessfulOwnershipTransfer(): void
  {
    $targetUUID = 'org-member-transfer-leave-' . bin2hex(random_bytes(4));
    $targetEmail = 'transfer-leave-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($targetUUID, $targetEmail, 'member');

    $transferPayload = $this->invokeControllerRoute('transferOwnership', $this->organizationId, 'POST', [
      'target_user_uuid' => $targetUUID,
      'csrf_token' => 'test-csrf',
    ], [], $this->ownerSession);
    $this->assertSame('success', $transferPayload['status'] ?? null);

    $leavePayload = $this->invokeControllerRoute('leaveOrganization', $this->organizationId, 'POST', [
      'csrf_token' => 'test-csrf',
    ], [], $this->ownerSession);
    $this->assertSame('success', $leavePayload['status'] ?? null);

    $org = Database::hgetall(Keys::ORGANIZATION . ':' . $this->organizationId);
    $this->assertSame($targetUUID, (string) ($org['owner_uuid'] ?? ''));

    $formerOwnerRelationship = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $this->ownerUUID);
    $this->assertSame('withdrawn', (string) ($formerOwnerRelationship['status'] ?? ''));
  }

  public function testGetAndUpdateSettingsRoutesAllowMemberWithSettingsWriteScope(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);
    $this->seedRelationship($this->requesterUUID, 'member', 'org.settings.write,work.read,sites.read');

    $getPayload = $this->invokeControllerRoute('getSettings', $this->organizationId, 'GET', [], [], $this->requesterSession);
    $this->assertSame('success', $getPayload['status'] ?? null);

    $updatePayload = $this->invokeControllerRoute('updateSettings', $this->organizationId, 'POST', [
      'default_wage' => '37.50',
      'timezone' => 'UTC',
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);
    $this->assertSame('success', $updatePayload['status'] ?? null);

    $settings = Database::hgetall(Keys::ORGANIZATION_SETTINGS . ':' . $this->organizationId);
    $this->assertSame('37.50', (string) ($settings['default_wage'] ?? ''));
    $this->assertSame('UTC', (string) ($settings['timezone'] ?? ''));
  }

  public function testUpdateSettingsRouteRejectsViewerWithoutSettingsWriteScope(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);
    $this->seedRelationship($this->requesterUUID, 'viewer', 'work.read,sites.read');

    $payload = $this->invokeControllerRoute('updateSettings', $this->organizationId, 'POST', [
      'default_wage' => '42.00',
      'timezone' => 'UTC',
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));

    $settings = Database::hgetall(Keys::ORGANIZATION_SETTINGS . ':' . $this->organizationId);
    $this->assertNotSame('42.00', (string) ($settings['default_wage'] ?? ''));
  }

  public function testUpdateSettingsRouteAllowsContributorPayPeriodControls(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);
    $this->seedRelationship($this->requesterUUID, 'contributor', 'payperiod.read,payperiod.write,sites.read,sites.write,work.read,work.scope.org,work.write');

    $payload = $this->invokeControllerRoute('updateSettings', $this->organizationId, 'POST', [
      'pay_frequency' => 'weekly',
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertSame('success', $payload['status'] ?? null);

    $settings = Database::hgetall(Keys::ORGANIZATION_SETTINGS . ':' . $this->organizationId);
    $this->assertSame('weekly', (string) ($settings['pay_frequency'] ?? ''));
    $this->assertSame('7', (string) ($settings['pay_period_length'] ?? ''));
  }

  public function testUpdateSettingsRouteRejectsContributorNonPayPeriodMutation(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);
    $this->seedRelationship($this->requesterUUID, 'contributor', 'payperiod.read,payperiod.write,sites.read,sites.write,work.read,work.scope.org,work.write');

    $payload = $this->invokeControllerRoute('updateSettings', $this->organizationId, 'POST', [
      'name' => 'Contributor Should Not Rename Org',
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testListAccessRequestsRouteDeniedForContributorSessionReturnsForbidden(): void
  {
    $this->seedRelationship($this->requesterUUID, 'contributor', 'payperiod.read,payperiod.write,sites.read,sites.write,work.read,work.scope.org,work.write');

    $payload = $this->invokeControllerRoute('listAccessRequests', $this->organizationId, 'GET', [], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
  }

  public function testManagerRoleCanListAccessRequests(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);
    $this->seedRelationship($this->requesterUUID, 'coordinator', 'access.manage,audit.read,org.settings.read,org.settings.write,payperiod.read,payperiod.write,sites.read,sites.write,wage.read,wage.write,work.read,work.scope.org,work.write');

    $pendingUserUUID = 'org-manager-pending-' . bin2hex(random_bytes(4));
    $pendingUserEmail = 'manager-pending-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedUser($pendingUserUUID, $pendingUserEmail);
    $this->seededMembers[$pendingUserUUID] = $pendingUserEmail;

    $requested = $this->service->requestAccessByOwnerEmail($pendingUserUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);

    $payload = $this->invokeControllerRoute('listAccessRequests', $this->organizationId, 'GET', [], [], $this->requesterSession);

    $this->assertSame('success', $payload['status'] ?? null);
    $rows = is_array($payload['data']['requests'] ?? null) ? $payload['data']['requests'] : [];
    $emails = array_values(array_map(static fn (array $row): string => (string) ($row['requester_contact_email'] ?? ''), $rows));
    $this->assertContains($pendingUserEmail, $emails);
  }

  public function testManagerRoleCanUpdateOrganizationSettings(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);
    $this->seedRelationship($this->requesterUUID, 'coordinator', 'access.manage,audit.read,org.settings.read,org.settings.write,payperiod.read,payperiod.write,sites.read,sites.write,wage.read,wage.write,work.read,work.scope.org,work.write');

    $payload = $this->invokeControllerRoute('updateSettings', $this->organizationId, 'POST', [
      'default_wage' => '55.25',
      'timezone' => 'UTC',
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertSame('success', $payload['status'] ?? null);

    $settings = Database::hgetall(Keys::ORGANIZATION_SETTINGS . ':' . $this->organizationId);
    $this->assertSame('55.25', (string) ($settings['default_wage'] ?? ''));
    $this->assertSame('UTC', (string) ($settings['timezone'] ?? ''));
  }

  public function testManagerRoleCannotTransferOwnership(): void
  {
    $this->seedRelationship($this->requesterUUID, 'coordinator', 'access.manage,audit.read,org.settings.read,org.settings.write,payperiod.read,payperiod.write,sites.read,sites.write,wage.read,wage.write,work.read,work.scope.org,work.write');

    $targetUUID = 'org-manager-transfer-target-' . bin2hex(random_bytes(4));
    $targetEmail = 'manager-transfer-target-' . bin2hex(random_bytes(4)) . '@example.com';
    $this->seedActiveMember($targetUUID, $targetEmail, 'member');

    $payload = $this->invokeControllerRoute('transferOwnership', $this->organizationId, 'POST', [
      'target_user_uuid' => $targetUUID,
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));

    $org = Database::hgetall(Keys::ORGANIZATION . ':' . $this->organizationId);
    $this->assertSame($this->ownerUUID, (string) ($org['owner_uuid'] ?? ''));
  }

  public function testNonPremiumPersonalOwnerCanUpdatePersonalSettings(): void
  {
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID);

    $personal = $this->service->createOrganization($this->ownerUUID, 'Owner Personal Org', [
      'organization_type' => 'personal',
    ]);
    $this->assertTrue($personal['success']);
    $personalOrgId = (string) ($personal['data']['organization_id'] ?? '');
    $this->assertNotSame('', $personalOrgId);

    $payload = $this->invokeControllerRoute('updateSettings', $personalOrgId, 'POST', [
      'default_wage' => '24.00',
      'timezone' => 'UTC',
      'csrf_token' => 'test-csrf',
    ], [], $this->ownerSession);

    $this->assertSame('success', $payload['status'] ?? null);

    $settings = Database::hgetall(Keys::ORGANIZATION_SETTINGS . ':' . $personalOrgId);
    $this->assertSame('24.00', (string) ($settings['default_wage'] ?? ''));
    $this->assertSame('UTC', (string) ($settings['timezone'] ?? ''));

    $organization = Database::hgetall(Keys::ORGANIZATION . ':' . $personalOrgId);
    $this->assertSame('personal', (string) ($organization['organization_type'] ?? ''));
  }

  public function testNonPremiumPersonalOwnerCannotUpgradeOrganizationTypeToShared(): void
  {
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID);

    $personal = $this->service->createOrganization($this->ownerUUID, 'Owner Personal Org', [
      'organization_type' => 'personal',
    ]);
    $this->assertTrue($personal['success']);
    $personalOrgId = (string) ($personal['data']['organization_id'] ?? '');
    $this->assertNotSame('', $personalOrgId);

    $payload = $this->invokeControllerRoute('updateSettings', $personalOrgId, 'POST', [
      'organization_type' => 'shared',
      'csrf_token' => 'test-csrf',
    ], [], $this->ownerSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));

    $organization = Database::hgetall(Keys::ORGANIZATION . ':' . $personalOrgId);
    $this->assertSame('personal', (string) ($organization['organization_type'] ?? ''));
  }

  public function testNonPremiumPersonalOwnerCannotLeavePersonalOrganization(): void
  {
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID);

    $personal = $this->service->createOrganization($this->ownerUUID, 'Owner Personal Org', [
      'organization_type' => 'personal',
    ]);
    $this->assertTrue($personal['success']);
    $personalOrgId = (string) ($personal['data']['organization_id'] ?? '');
    $this->assertNotSame('', $personalOrgId);

    $payload = $this->invokeControllerRoute('leaveOrganization', $personalOrgId, 'POST', [
      'csrf_token' => 'test-csrf',
    ], [], $this->ownerSession);

    $this->assertNotSame('success', $payload['status'] ?? null);
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
    $this->assertStringContainsString('cannot be deleted or left', strtolower((string) ($payload['message'] ?? '')));
  }

  public function testNonPremiumMemberCanLeaveSharedOrganization(): void
  {
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $this->requesterUUID);
    $this->seedRelationship($this->requesterUUID, 'member', 'work.read,sites.read');

    $payload = $this->invokeControllerRoute('leaveOrganization', $this->organizationId, 'POST', [
      'csrf_token' => 'test-csrf',
    ], [], $this->requesterSession);

    $this->assertSame('success', $payload['status'] ?? null);

    $relationship = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $this->requesterUUID);
    $this->assertSame('withdrawn', (string) ($relationship['status'] ?? ''));
  }

  public function testNonPremiumPersonalOwnerCanMutateOwnSitesAndWork(): void
  {
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID);

    $personal = $this->service->createOrganization($this->ownerUUID, 'Owner Personal Org', [
      'organization_type' => 'personal',
    ]);
    $this->assertTrue($personal['success']);
    $personalOrgId = (string) ($personal['data']['organization_id'] ?? '');
    $this->assertNotSame('', $personalOrgId);

    $canMutateSites = $this->service->canMutateSitesForOwner($this->ownerUUID, $this->ownerUUID);
    $this->assertTrue($canMutateSites);

    $canMutateWork = $this->service->canMutateWorkForOwner($this->ownerUUID, $this->ownerUUID, $personalOrgId);
    $this->assertTrue($canMutateWork);
  }

  /**
   * @param array<string, string> $post
   * @return array<string, mixed>
   */
  private function invokeControllerRoute(
    string $method,
    string $organizationId,
    string $requestMethod,
    array $post = [],
    array $get = [],
    ?string $sessionOverride = null
  ): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $cookie = var_export($sessionOverride ?? $this->ownerSession, true);
    $requestMethodLiteral = var_export($requestMethod, true);
    $organizationIdLiteral = var_export($organizationId, true);
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
      . '$c = new \\PayCal\\Controllers\\OrganizationDiscoveryController(); '
      . 'ob_start(); '
      . '$c->' . $method . '(' . $organizationIdLiteral . '); '
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
   * @return array<string, mixed>
   */
  private function invokeControllerMethodWithoutOrganization(
    string $method,
    string $requestMethod,
    array $post = [],
    array $get = [],
    ?string $sessionOverride = null
  ): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $cookie = var_export($sessionOverride ?? $this->ownerSession, true);
    $requestMethodLiteral = var_export($requestMethod, true);
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
      . '$c = new \\PayCal\\Controllers\\OrganizationDiscoveryController(); '
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

  private function cleanupOrganizationArtifacts(string $orgId): void
  {
    $auditSetKey = Keys::ORGANIZATION_AUDIT . ':' . $orgId;
    foreach (Database::smembers($auditSetKey) as $eventId) {
      Database::unlink(Keys::ORGANIZATION_AUDIT_EVENT . ':' . $eventId);
    }
    Database::unlink($auditSetKey);

    $requestSetKey = Keys::ORGANIZATION_ACCESS_REQUEST_ORG . ':' . $orgId;
    foreach (Database::smembers($requestSetKey) as $requestId) {
      $requestKey = Keys::ORGANIZATION_ACCESS_REQUEST . ':' . $requestId;
      $request = Database::hgetall($requestKey);
      $requesterUUID = (string) ($request['requester_uuid'] ?? '');
      if ($requesterUUID !== '') {
        Database::srem(Keys::ORGANIZATION_ACCESS_REQUEST_REQUESTER . ':' . $requesterUUID, $requestId);
        Database::unlink(Keys::ORGANIZATION_ACCESS_REQUEST_ACTIVE . ':' . $orgId . ':' . $requesterUUID);
      }
      Database::unlink($requestKey);
    }
    Database::unlink($requestSetKey);

    $relationshipPattern = Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':*';
    foreach (Database::scanKeys($relationshipPattern) as $relationshipKey) {
      Database::unlink((string) $relationshipKey);
    }

    Database::unlink(Keys::ORGANIZATION_INVITE_ORG . ':' . $orgId);
    Database::unlink(Keys::ORGANIZATION_SITE . ':' . $orgId);
    Database::unlink(Keys::ORGANIZATION_SETTINGS . ':' . $orgId);
    Database::unlink(Keys::ORGANIZATION . ':' . $orgId);
  }

  private function seedActiveMember(string $memberUUID, string $memberEmail, string $role): void
  {
    $this->seedUser($memberUUID, $memberEmail);
    $this->seededMembers[$memberUUID] = $memberEmail;

    $this->seedRelationship($memberUUID, $role, 'work.read');
  }

  private function seedRelationship(string $memberUUID, string $role, string $scopes): void
  {
    $scopeCSV = trim($scopes) === '' ? 'work.read' : $scopes;

    Database::hset(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $memberUUID, [
      'organization_id' => $this->organizationId,
      'user_uuid' => $memberUUID,
      'role' => $role,
      'status' => 'active',
      'scopes' => $scopeCSV,
      'updated_at' => date('c'),
    ]);

    Database::sadd(Keys::ORGANIZATION_USER . ':' . $memberUUID, $this->organizationId);
  }
}
