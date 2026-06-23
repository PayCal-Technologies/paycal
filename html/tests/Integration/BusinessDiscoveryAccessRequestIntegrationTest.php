<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\BusinessDashboardMetrics;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\BusinessDiscoveryService;
use PayCal\Domain\UserRepository;

final class BusinessDiscoveryAccessRequestIntegrationTest extends TestCase
{
  private BusinessDiscoveryService $service;
  private string $ownerUUID;
  private string $ownerEmail;
  private string $requesterUUID;
  private string $requesterEmail;
  private string $secondRequesterUUID;
  private string $secondRequesterEmail;
  private string $businessId = '';
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
      'tier' => 'business',
      'status' => 'active',
    ]);

    $create = $this->service->createBusiness($this->ownerUUID, 'Access Test Org', [
      'business_type' => 'shared',
    ]);

    $this->assertTrue($create['success'], 'Business creation precondition failed.');
    $this->businessId = (string) ($create['data']['business_id'] ?? '');
    $this->assertNotSame('', $this->businessId, 'Business ID precondition failed.');
  }

  protected function tearDown(): void
  {
    $orgId = $this->businessId;

    if ($orgId !== '') {
      $auditSetKey = Keys::BUSINESS_AUDIT . ':' . $orgId;
      foreach (Database::smembers($auditSetKey) as $eventId) {
        Database::unlink(Keys::BUSINESS_AUDIT_EVENT . ':' . $eventId);
      }
      Database::unlink($auditSetKey);

      $requestSetKey = Keys::BUSINESS_ACCESS_REQUEST_BUSINESS . ':' . $orgId;
      foreach (Database::smembers($requestSetKey) as $requestId) {
        Database::unlink(Keys::BUSINESS_ACCESS_REQUEST . ':' . $requestId);
      }
      Database::unlink($requestSetKey);

      Database::unlink(Keys::BUSINESS_SITE . ':' . $orgId);
      Database::unlink(Keys::BUSINESS_INVITE_BUSINESS . ':' . $orgId);
      Database::unlink(Keys::BUSINESS_MEMBERS . ':' . $orgId);
      Database::unlink(Keys::BUSINESS_CONNECTIONS . ':' . $orgId);
      Database::unlink(Keys::BUSINESS_PENDING . ':' . $orgId);
      Database::unlink(Keys::BUSINESS . ':' . $orgId);
      Database::unlink(Keys::BUSINESS_SETTINGS . ':' . $orgId);
      Database::unlink(Keys::BUSINESS_CONNECTION . ':' . $orgId . ':' . $this->ownerUUID);
      Database::unlink(Keys::BUSINESS_CONNECTION . ':' . $orgId . ':' . $this->requesterUUID);
      Database::unlink(Keys::BUSINESS_CONNECTION . ':' . $orgId . ':' . $this->secondRequesterUUID);
      Database::unlink(Keys::BUSINESS_ACCESS_REQUEST_ACTIVE . ':' . $orgId . ':' . $this->requesterUUID);
      Database::unlink(Keys::BUSINESS_ACCESS_REQUEST_ACTIVE . ':' . $orgId . ':' . $this->secondRequesterUUID);
      foreach (Database::smembers(Keys::businessConsentsByBusiness($orgId)) as $consentId) {
        Database::unlink(Keys::businessConsent((string) $consentId));
      }
      Database::unlink(Keys::businessConsentsByBusiness($orgId));
      foreach (Database::scanKeys(Keys::BUSINESS_DEK_WRAP . ':' . $orgId . ':*') as $wrapKey) {
        Database::unlink((string) $wrapKey);
      }
    }

    Database::unlink(Keys::BUSINESS_OWNER . ':' . $this->ownerUUID);
    Database::unlink(Keys::BUSINESS_USER . ':' . $this->ownerUUID);
    Database::unlink(Keys::BUSINESS_USER . ':' . $this->requesterUUID);
    Database::unlink(Keys::BUSINESS_USER . ':' . $this->secondRequesterUUID);
    Database::unlink(Keys::BUSINESS_CONNECTIONS_USER . ':' . $this->ownerUUID);
    Database::unlink(Keys::BUSINESS_CONNECTIONS_USER . ':' . $this->requesterUUID);
    Database::unlink(Keys::BUSINESS_CONNECTIONS_USER . ':' . $this->secondRequesterUUID);
    Database::unlink(Keys::BUSINESS_ACCESS_REQUEST_REQUESTER . ':' . $this->requesterUUID);
    Database::unlink(Keys::BUSINESS_ACCESS_REQUEST_REQUESTER . ':' . $this->secondRequesterUUID);

    $this->cleanupUser($this->ownerUUID, $this->ownerEmail);
    $this->cleanupUser($this->requesterUUID, $this->requesterEmail);
    $this->cleanupUser($this->secondRequesterUUID, $this->secondRequesterEmail);
    SystemConfig::set('business_shared_encryption_enabled', $this->originalOrgSharedEncryptionEnabled);
    SystemConfig::set('business_shared_encryption_enable_write', $this->originalOrgSharedEncryptionWriteEnabled);

    parent::tearDown();
  }

  public function testApproveAccessRequestCreatesActiveConnection(): void
  {
    $day = date('Y-m-d');
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);

    $requestId = (string) ($requested['data']['request_id'] ?? '');
    $this->assertNotSame('', $requestId);

    $approved = $this->service->approveAccessRequest($this->ownerUUID, $this->businessId, $requestId, [
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'ip' => '127.0.0.1',
      'user_agent' => 'phpunit',
    ]);
    $this->assertTrue($approved['success']);

    $connection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->requesterUUID);
    $this->assertSame('active', (string) ($connection['status'] ?? ''));
    $this->assertSame('member', (string) ($connection['role'] ?? ''));
    $this->assertSame('sites.read,work.read,work.scope.self', (string) ($connection['scopes'] ?? ''));
    $this->assertSame('1', (string) ($connection['scope_preset_version'] ?? ''));
    $this->assertSame('2026-06-18', (string) ($connection['scope_policy_version'] ?? ''));
    $this->assertSame(1, Database::sismember(Keys::BUSINESS_MEMBERS . ':' . $this->businessId, $this->requesterUUID));
    $this->assertSame(1, Database::sismember(Keys::BUSINESS_USER . ':' . $this->requesterUUID, $this->businessId));
    $this->assertSame(1, Database::sismember(Keys::BUSINESS_CONNECTIONS . ':' . $this->businessId, $this->requesterUUID));
    $this->assertSame(1, Database::sismember(Keys::BUSINESS_CONNECTIONS_USER . ':' . $this->requesterUUID, $this->businessId));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_PENDING . ':' . $this->businessId, $this->requesterUUID));

    $history = $this->service->listAccessRequestHistory($this->ownerUUID, $this->businessId);
    $this->assertTrue($history['success']);

    $statuses = array_map(
      static fn (array $row): string => (string) ($row['status'] ?? ''),
      is_array($history['data']['requests'] ?? null) ? $history['data']['requests'] : []
    );
    $this->assertContains('approved', $statuses);

    $requestedMetric = (int) Database::get(Keys::TELEMETRY . ':business:access_request:requested:' . $day);
    $approvedMetric = (int) Database::get(Keys::TELEMETRY . ':business:access_request:approved:' . $day);
    $this->assertGreaterThanOrEqual(1, $requestedMetric);
    $this->assertGreaterThanOrEqual(1, $approvedMetric);
  }

  public function testApproveAccessRequestPreservesConsentDataForEncryptedActivation(): void
  {
    SystemConfig::set('business_shared_encryption_enabled', true);
    Database::hset(Keys::USER . ':' . $this->requesterUUID . ':passkey_wrapped_deks', [
      'cred-' . $this->requesterUUID => base64_encode('test-wrapped-dek'),
    ]);

    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);

    $requestId = (string) ($requested['data']['request_id'] ?? '');
    $this->assertNotSame('', $requestId);

    $approved = $this->service->approveAccessRequest($this->ownerUUID, $this->businessId, $requestId, [
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'ip' => '127.0.0.1',
      'user_agent' => 'phpunit',
    ]);

    $this->assertTrue($approved['success'], (string) ($approved['message'] ?? ''));

    $connection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->requesterUUID);
    $this->assertSame('active', (string) ($connection['status'] ?? ''));
    $this->assertNotSame('', (string) ($connection['consent_id'] ?? ''));
    $this->assertNotSame('', (string) ($connection['credential_id'] ?? ''));
    $this->assertSame('sites.read,work.read,work.scope.self', (string) ($connection['scopes'] ?? ''));
    $this->assertSame((string) ($approved['data']['consent_id'] ?? ''), (string) ($connection['consent_id'] ?? ''));
  }

  public function testApproveAccessRequestIsIdempotentWhenConnectionAlreadyActive(): void
  {
    $requested = $this->service->requestAccessByOwnerEmail($this->requesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);

    $requestId = (string) ($requested['data']['request_id'] ?? '');
    $this->assertNotSame('', $requestId);

    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->requesterUUID, [
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
      'role' => 'admin',
      'scopes' => 'all',
      'accepted_at' => date('c'),
    ]);

    $approved = $this->service->approveAccessRequest($this->ownerUUID, $this->businessId, $requestId, [
      'consent_acknowledged' => '1',
      'consent_version' => 'v1',
      'disclaimer_text' => 'Test consent acknowledged',
      'ip' => '127.0.0.1',
      'user_agent' => 'phpunit',
    ]);

    $this->assertTrue($approved['success'], (string) ($approved['message'] ?? ''));
    $request = Database::hgetall(Keys::BUSINESS_ACCESS_REQUEST . ':' . $requestId);
    $this->assertSame('approved', (string) ($request['status'] ?? ''));

    $connection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->requesterUUID);
    $this->assertSame('active', (string) ($connection['status'] ?? ''));
    $this->assertSame('admin', (string) ($connection['role'] ?? ''));
    $this->assertSame('all', (string) ($connection['scopes'] ?? ''));
  }

  public function testPendingConnectionIsIndexedButNotCountedAsActiveMember(): void
  {
    $method = new \ReflectionMethod(BusinessDiscoveryService::class, 'setConnection');
    $method->invoke($this->service, $this->businessId, $this->secondRequesterUUID, [
      'role' => 'member',
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_PENDING,
      'scopes' => 'sites.read,work.read',
      'created_at' => date('c'),
    ]);

    $connection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->secondRequesterUUID);
    $this->assertSame('pending', (string) ($connection['status'] ?? ''));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_MEMBERS . ':' . $this->businessId, $this->secondRequesterUUID));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_USER . ':' . $this->secondRequesterUUID, $this->businessId));
    $this->assertSame(1, Database::sismember(Keys::BUSINESS_CONNECTIONS . ':' . $this->businessId, $this->secondRequesterUUID));
    $this->assertSame(1, Database::sismember(Keys::BUSINESS_CONNECTIONS_USER . ':' . $this->secondRequesterUUID, $this->businessId));
    $this->assertSame(1, Database::sismember(Keys::BUSINESS_PENDING . ':' . $this->businessId, $this->secondRequesterUUID));

    $metrics = BusinessDashboardMetrics::forBusiness($this->businessId, true);
    $this->assertSame(1, $metrics['members']);
  }

  public function testListForUserFindsPendingConnectionWhenConnectionIndexIsMissing(): void
  {
    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->secondRequesterUUID, [
      'business_id' => $this->businessId,
      'user_uuid' => $this->secondRequesterUUID,
      'role' => 'member',
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_PENDING,
      'scopes' => 'sites.read,work.read',
      'created_at' => date('c'),
    ]);
    Database::srem(Keys::BUSINESS_CONNECTIONS . ':' . $this->businessId, $this->secondRequesterUUID);
    Database::srem(Keys::BUSINESS_CONNECTIONS_USER . ':' . $this->secondRequesterUUID, $this->businessId);
    Database::srem(Keys::BUSINESS_USER . ':' . $this->secondRequesterUUID, $this->businessId);

    $list = $this->service->listForUser($this->secondRequesterUUID);
    $this->assertTrue($list['success']);

    $businesses = is_array($list['data']['businesses'] ?? null)
      ? $list['data']['businesses']
      : [];

    $matchingRows = array_values(array_filter($businesses, function (mixed $row): bool {
      return is_array($row) && (string) ($row['business_id'] ?? '') === $this->businessId;
    }));

    $this->assertNotSame([], $matchingRows, 'Pending connections must render even when disposable indexes drift.');
    $this->assertSame('pending', (string) ($matchingRows[0]['connection_status'] ?? ''));
  }

  public function testRejectAccessRequestMarksPendingConnectionRejected(): void
  {
    $day = date('Y-m-d');
    $requested = $this->service->requestAccessByOwnerEmail($this->secondRequesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);

    $requestId = (string) ($requested['data']['request_id'] ?? '');
    $this->assertNotSame('', $requestId);

    $rejected = $this->service->rejectAccessRequest($this->ownerUUID, $this->businessId, $requestId);
    $this->assertTrue($rejected['success']);

    $connection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->secondRequesterUUID);
    $this->assertSame('rejected', (string) ($connection['status'] ?? ''));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_MEMBERS . ':' . $this->businessId, $this->secondRequesterUUID));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_USER . ':' . $this->secondRequesterUUID, $this->businessId));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_CONNECTIONS . ':' . $this->businessId, $this->secondRequesterUUID));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_CONNECTIONS_USER . ':' . $this->secondRequesterUUID, $this->businessId));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_PENDING . ':' . $this->businessId, $this->secondRequesterUUID));

    $history = $this->service->listAccessRequestHistory($this->ownerUUID, $this->businessId);
    $this->assertTrue($history['success']);

    $statuses = array_map(
      static fn (array $row): string => (string) ($row['status'] ?? ''),
      is_array($history['data']['requests'] ?? null) ? $history['data']['requests'] : []
    );

    $this->assertContains('rejected', $statuses);

    $rejectedMetric = (int) Database::get(Keys::TELEMETRY . ':business:access_request:rejected:' . $day);
    $this->assertGreaterThanOrEqual(1, $rejectedMetric);
  }

  public function testLeavingPendingConnectionCancelsAccessRequest(): void
  {
    $day = date('Y-m-d');
    $requested = $this->service->requestAccessByOwnerEmail($this->secondRequesterUUID, $this->ownerEmail);
    $this->assertTrue($requested['success']);

    $requestId = (string) ($requested['data']['request_id'] ?? '');
    $this->assertNotSame('', $requestId);

    $activeKey = Keys::BUSINESS_ACCESS_REQUEST_ACTIVE . ':' . $this->businessId . ':' . $this->secondRequesterUUID;
    $this->assertSame($requestId, (string) Database::get($activeKey));

    $left = $this->service->leaveBusiness($this->secondRequesterUUID, $this->businessId);
    $this->assertTrue($left['success']);
    $this->assertSame('Membership request canceled.', (string) ($left['message'] ?? ''));
    $this->assertSame($requestId, (string) ($left['data']['request_id'] ?? ''));

    $request = Database::hgetall(Keys::BUSINESS_ACCESS_REQUEST . ':' . $requestId);
    $this->assertSame('withdrawn', (string) ($request['status'] ?? ''));
    $this->assertSame($this->secondRequesterUUID, (string) ($request['withdrawn_by'] ?? ''));
    $this->assertNotSame('', (string) ($request['withdrawn_at'] ?? ''));
    $this->assertSame('', (string) Database::get($activeKey));

    $connection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->secondRequesterUUID);
    $this->assertSame('withdrawn', (string) ($connection['status'] ?? ''));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_MEMBERS . ':' . $this->businessId, $this->secondRequesterUUID));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_USER . ':' . $this->secondRequesterUUID, $this->businessId));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_CONNECTIONS . ':' . $this->businessId, $this->secondRequesterUUID));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_CONNECTIONS_USER . ':' . $this->secondRequesterUUID, $this->businessId));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_PENDING . ':' . $this->businessId, $this->secondRequesterUUID));

    $history = $this->service->listAccessRequestHistory($this->ownerUUID, $this->businessId);
    $this->assertTrue($history['success']);
    $statuses = array_map(
      static fn (array $row): string => (string) ($row['status'] ?? ''),
      is_array($history['data']['requests'] ?? null) ? $history['data']['requests'] : []
    );
    $this->assertContains('withdrawn', $statuses);

    $metrics = BusinessDashboardMetrics::forBusiness($this->businessId, true);
    $this->assertSame(0, $metrics['pending_requests']);

    $withdrawnMetric = (int) Database::get(Keys::TELEMETRY . ':business:access_request:withdrawn:' . $day);
    $this->assertGreaterThanOrEqual(1, $withdrawnMetric);
  }

  public function testRemovingOwnedOrganizationClearsAccessRequestIndexes(): void
  {
    $removed = $this->service->leaveBusiness($this->ownerUUID, $this->businessId);
    $this->assertFalse($removed['success']);
    $this->assertStringContainsString('transfer ownership before leaving', (string) ($removed['message'] ?? ''));
  }

  public function testOwnerWithoutPremiumCanStillAccessOwnedSharedOrganizationFeatures(): void
  {
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID);

    $list = $this->service->listForUser($this->ownerUUID);
    $this->assertTrue($list['success']);

    $businesses = is_array($list['data']['businesses'] ?? null)
      ? $list['data']['businesses']
      : [];

    $ownedRows = array_values(array_filter($businesses, function (mixed $row): bool {
      if (!is_array($row)) {
        return false;
      }

      return (string) ($row['business_id'] ?? '') === $this->businessId;
    }));

    $this->assertNotSame([], $ownedRows, 'Owner should still list their shared business without premium.');

    $prepare = $this->service->prepareBulkInviteImport(
      $this->ownerUUID,
      $this->businessId,
      'new-member@example.com',
      ['work.read']
    );

    $this->assertTrue($prepare['success'], (string) ($prepare['message'] ?? ''));
    $this->assertNotSame('', (string) ($prepare['data']['import_id'] ?? ''));
  }

  public function testOwnerCannotCreateSecondSharedOrganizationWhenSingletonPolicyEnabled(): void
  {
    $secondCreate = $this->service->createBusiness($this->ownerUUID, 'Second Shared Org', [
      'business_type' => 'shared',
    ]);

    $this->assertFalse($secondCreate['success']);
    $this->assertStringContainsString('Shared business already exists for this account', (string) ($secondCreate['message'] ?? ''));
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
    Database::unlink(Keys::USER . ':' . $userUUID . ':passkey_wrapped_deks');
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $userUUID);
    Database::unlink(Keys::businessConsentsByUser($userUUID));
    Database::unlink(Keys::EMAIL . ':' . $email);
    Database::unlink(Keys::EMAIL . $email);
  }
}
