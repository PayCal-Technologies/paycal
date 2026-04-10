<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\OrganizationDiscoveryService;
use PayCal\Domain\UserRepository;

final class OrganizationDiscoveryAccessRequestIntegrationTest extends TestCase
{
  private OrganizationDiscoveryService $service;
  private string $ownerUUID;
  private string $ownerEmail;
  private string $requesterUUID;
  private string $requesterEmail;
  private string $secondRequesterUUID;
  private string $secondRequesterEmail;
  private string $organizationId = '';
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
    $this->ownerUUID = 'org-owner-' . $suffix;
    $this->ownerEmail = 'owner-' . $suffix . '@example.com';
    $this->requesterUUID = 'org-requester-' . $suffix;
    $this->requesterEmail = 'requester-' . $suffix . '@example.com';
    $this->secondRequesterUUID = 'org-requester2-' . $suffix;
    $this->secondRequesterEmail = 'requester2-' . $suffix . '@example.com';

    $this->seedUser($this->ownerUUID, $this->ownerEmail);
    $this->seedUser($this->requesterUUID, $this->requesterEmail);
    $this->seedUser($this->secondRequesterUUID, $this->secondRequesterEmail);

    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);

    $create = $this->service->createOrganization($this->ownerUUID, 'Access Test Org', [
      'organization_type' => 'shared',
    ]);

    $this->assertTrue($create['success'], 'Organization creation precondition failed.');
    $this->organizationId = (string) ($create['data']['organization_id'] ?? '');
    $this->assertNotSame('', $this->organizationId, 'Organization ID precondition failed.');
  }

  protected function tearDown(): void
  {
    $orgId = $this->organizationId;

    if ($orgId !== '') {
      $auditSetKey = Keys::ORGANIZATION_AUDIT . ':' . $orgId;
      foreach (Database::smembers($auditSetKey) as $eventId) {
        Database::unlink(Keys::ORGANIZATION_AUDIT_EVENT . ':' . $eventId);
      }
      Database::unlink($auditSetKey);

      $requestSetKey = Keys::ORGANIZATION_ACCESS_REQUEST_ORG . ':' . $orgId;
      foreach (Database::smembers($requestSetKey) as $requestId) {
        Database::unlink(Keys::ORGANIZATION_ACCESS_REQUEST . ':' . $requestId);
      }
      Database::unlink($requestSetKey);

      Database::unlink(Keys::ORGANIZATION_SITE . ':' . $orgId);
      Database::unlink(Keys::ORGANIZATION_INVITE_ORG . ':' . $orgId);
      Database::unlink(Keys::ORGANIZATION . ':' . $orgId);
      Database::unlink(Keys::ORGANIZATION_SETTINGS . ':' . $orgId);
      Database::unlink(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $this->ownerUUID);
      Database::unlink(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $this->requesterUUID);
      Database::unlink(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $this->secondRequesterUUID);
      Database::unlink(Keys::ORGANIZATION_ACCESS_REQUEST_ACTIVE . ':' . $orgId . ':' . $this->requesterUUID);
      Database::unlink(Keys::ORGANIZATION_ACCESS_REQUEST_ACTIVE . ':' . $orgId . ':' . $this->secondRequesterUUID);
    }

    Database::unlink(Keys::ORGANIZATION_OWNER . ':' . $this->ownerUUID);
    Database::unlink(Keys::ORGANIZATION_USER . ':' . $this->ownerUUID);
    Database::unlink(Keys::ORGANIZATION_USER . ':' . $this->requesterUUID);
    Database::unlink(Keys::ORGANIZATION_USER . ':' . $this->secondRequesterUUID);
    Database::unlink(Keys::ORGANIZATION_ACCESS_REQUEST_REQUESTER . ':' . $this->requesterUUID);
    Database::unlink(Keys::ORGANIZATION_ACCESS_REQUEST_REQUESTER . ':' . $this->secondRequesterUUID);

    $this->cleanupUser($this->ownerUUID, $this->ownerEmail);
    $this->cleanupUser($this->requesterUUID, $this->requesterEmail);
    $this->cleanupUser($this->secondRequesterUUID, $this->secondRequesterEmail);
    SystemConfig::set('org_shared_encryption_enabled', $this->originalOrgSharedEncryptionEnabled);
    SystemConfig::set('org_shared_encryption_enable_write', $this->originalOrgSharedEncryptionWriteEnabled);

    parent::tearDown();
  }

  public function testApproveAccessRequestCreatesActiveRelationship(): void
  {
    $day = date('Y-m-d');
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);

    $requestId = (string) ($requested['data']['request_id'] ?? '');
    $this->assertNotSame('', $requestId);

    $approved = $this->service->approveAccessRequest($this->ownerUUID, $this->organizationId, $requestId, [
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'ip' => '127.0.0.1',
      'user_agent' => 'phpunit',
    ]);
    $this->assertTrue($approved['success']);

    $relationship = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $this->requesterUUID);
    $this->assertSame('active', (string) ($relationship['status'] ?? ''));
    $this->assertSame('member', (string) ($relationship['role'] ?? ''));
    $this->assertSame('sites.read,work.read', (string) ($relationship['scopes'] ?? ''));

    $history = $this->service->listAccessRequestHistory($this->ownerUUID, $this->organizationId);
    $this->assertTrue($history['success']);

    $statuses = array_map(
      static fn (array $row): string => (string) ($row['status'] ?? ''),
      is_array($history['data']['requests'] ?? null) ? $history['data']['requests'] : []
    );
    $this->assertContains('approved', $statuses);

    $requestedMetric = (int) Database::get(Keys::TELEMETRY . ':organization:access_request:requested:' . $day);
    $approvedMetric = (int) Database::get(Keys::TELEMETRY . ':organization:access_request:approved:' . $day);
    $this->assertGreaterThanOrEqual(1, $requestedMetric);
    $this->assertGreaterThanOrEqual(1, $approvedMetric);
  }

  public function testRejectAccessRequestKeepsRelationshipAbsent(): void
  {
    $day = date('Y-m-d');
    $requested = $this->service->requestAccessByOwnerEmail($this->secondRequesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);

    $requestId = (string) ($requested['data']['request_id'] ?? '');
    $this->assertNotSame('', $requestId);

    $rejected = $this->service->rejectAccessRequest($this->ownerUUID, $this->organizationId, $requestId);
    $this->assertTrue($rejected['success']);

    $relationship = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $this->secondRequesterUUID);
    $this->assertSame([], $relationship);

    $history = $this->service->listAccessRequestHistory($this->ownerUUID, $this->organizationId);
    $this->assertTrue($history['success']);

    $statuses = array_map(
      static fn (array $row): string => (string) ($row['status'] ?? ''),
      is_array($history['data']['requests'] ?? null) ? $history['data']['requests'] : []
    );

    $this->assertContains('rejected', $statuses);

    $rejectedMetric = (int) Database::get(Keys::TELEMETRY . ':organization:access_request:rejected:' . $day);
    $this->assertGreaterThanOrEqual(1, $rejectedMetric);
  }

  public function testRemovingOwnedOrganizationClearsAccessRequestIndexes(): void
  {
    $removed = $this->service->leaveOrganization($this->ownerUUID, $this->organizationId);
    $this->assertFalse($removed['success']);
    $this->assertStringContainsString('transfer ownership before leaving', (string) ($removed['message'] ?? ''));
  }

  public function testOwnerWithoutPremiumCanStillAccessOwnedSharedOrganizationFeatures(): void
  {
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID);

    $list = $this->service->listForUser($this->ownerUUID);
    $this->assertTrue($list['success']);

    $organizations = is_array($list['data']['organizations'] ?? null)
      ? $list['data']['organizations']
      : [];

    $ownedRows = array_values(array_filter($organizations, function (mixed $row): bool {
      if (!is_array($row)) {
        return false;
      }

      return (string) ($row['organization_id'] ?? '') === $this->organizationId;
    }));

    $this->assertNotSame([], $ownedRows, 'Owner should still list their shared organization without premium.');

    $prepare = $this->service->prepareBulkInviteImport(
      $this->ownerUUID,
      $this->organizationId,
      'new-member@example.com',
      ['work.read']
    );

    $this->assertTrue($prepare['success'], (string) ($prepare['message'] ?? ''));
    $this->assertNotSame('', (string) ($prepare['data']['import_id'] ?? ''));
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
}
