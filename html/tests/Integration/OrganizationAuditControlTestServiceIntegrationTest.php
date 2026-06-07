<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Infrastructure\Audit\OrganizationAuditControlTestService;
use PayCal\Domain\OrganizationDiscoveryService;
use PayCal\Infrastructure\Audit\SystemAuditRepository;
use PayCal\Domain\UserRepository;

final class OrganizationAuditControlTestServiceIntegrationTest extends TestCase
{
  private OrganizationDiscoveryService $organizationService;
  private string $ownerUUID;
  private string $ownerEmail;
  private string $viewerUUID;
  private string $viewerEmail;
  private string $organizationId = '';

  protected function setUp(): void
  {
    parent::setUp();

    $suffix = bin2hex(random_bytes(6));
    $this->ownerUUID = 'org-audit-owner-' . $suffix;
    $this->ownerEmail = 'org-audit-owner-' . $suffix . '@example.com';
    $this->viewerUUID = 'org-audit-viewer-' . $suffix;
    $this->viewerEmail = 'org-audit-viewer-' . $suffix . '@example.com';
    $this->organizationService = new OrganizationDiscoveryService();

    $this->seedUser($this->ownerUUID, $this->ownerEmail);
    $this->seedUser($this->viewerUUID, $this->viewerEmail);

    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);

    $create = $this->organizationService->createOrganization($this->ownerUUID, 'Audit Control Test Org', [
      'organization_type' => 'shared',
    ]);

    $this->assertTrue($create['success']);
    $this->organizationId = (string) ($create['data']['organization_id'] ?? '');
    $this->assertNotSame('', $this->organizationId);

    $this->organizationService->appendOrganizationAuditEvent(
      $this->organizationId,
      'organization.audit.bootstrap',
      $this->ownerUUID,
      ['seed' => '1']
    );
  }

  protected function tearDown(): void
  {
    if ($this->organizationId !== '') {
      foreach (Database::smembers(Keys::ORGANIZATION_AUDIT . ':' . $this->organizationId) as $eventId) {
        Database::unlink(Keys::ORGANIZATION_AUDIT_EVENT . ':' . $eventId);
      }
      Database::unlink(Keys::ORGANIZATION_AUDIT . ':' . $this->organizationId);
      foreach (Database::smembers(Keys::organizationAuditControlTestIndex($this->organizationId)) as $testId) {
        Database::unlink(Keys::organizationAuditControlTest($testId));
      }
      Database::unlink(Keys::organizationAuditControlTestIndex($this->organizationId));
      Database::unlink(Keys::ORGANIZATION . ':' . $this->organizationId);
      Database::unlink(Keys::ORGANIZATION_SETTINGS . ':' . $this->organizationId);
      Database::unlink(Keys::ORGANIZATION_USER . ':' . $this->ownerUUID);
      Database::unlink(Keys::ORGANIZATION_USER . ':' . $this->viewerUUID);
      Database::unlink(Keys::ORGANIZATION_OWNER . ':' . $this->ownerUUID);
      Database::unlink(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $this->ownerUUID);
      Database::unlink(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $this->viewerUUID);
    }

    Database::unlink(Keys::SYSTEM_AUDIT_GCS_CHAIN_TIP);
    Database::del('system:audit:ledger:*');
    Database::del('system:audit:anchor:*');
    Database::unlink(Keys::SYSTEM_AUDIT_BLOCKCHAIN . ':anchor_queue');
    foreach (Database::smembers(Keys::SYSTEM_AUDIT) as $eventId) {
      Database::unlink(Keys::SYSTEM_AUDIT_EVENT . ':' . $eventId);
    }
    Database::unlink(Keys::SYSTEM_AUDIT);
    Database::del('system:audit:event:*');

    $this->cleanupUser($this->ownerUUID, $this->ownerEmail);
    $this->cleanupUser($this->viewerUUID, $this->viewerEmail);

    parent::tearDown();
  }

  public function testOwnerGeneratedErrorTestWritesRedisWatcherAndGcsMetadata(): void
  {
    $service = new OrganizationAuditControlTestService(
      organizationService: $this->organizationService,
      alertArtifactUploader: static function (array $verificationResult, array $webhookResult, string $timestampIso8601, array $previousChainTip): array {
        TestCase::assertSame('manual_organization_audit_control_test', (string) ($verificationResult['reason'] ?? ''));
        TestCase::assertSame('manual_control_test', (string) ($webhookResult['error'] ?? ''));
        TestCase::assertIsArray($previousChainTip);

        return [
          'uploaded' => true,
          'object_path' => 'soc2/audit-ledger/alerts/2026/04/alert-test.json',
          'object_hash' => 'abc123hash',
          'http_code' => 200,
          'error' => '',
          'attempts' => 1,
        ];
      }
    );

    $result = $service->generateErrorTest($this->ownerUUID, $this->organizationId, [
      'summary' => 'PHPUnit manual org alert test',
      'source' => 'phpunit',
    ]);

    $this->assertTrue($result['success'], (string) ($result['message'] ?? ''));
    $data = is_array($result['data'] ?? null) ? $result['data'] : [];
    $testId = (string) ($data['test_id'] ?? '');
    $systemAuditEventId = (string) ($data['system_audit_event_id'] ?? '');

    $this->assertNotSame('', $testId);
    $this->assertNotSame('', $systemAuditEventId);
    $this->assertSame('soc2/audit-ledger/alerts/2026/04/alert-test.json', (string) ($data['gcs']['object_path'] ?? ''));
    $this->assertTrue((bool) ($data['gcs']['uploaded'] ?? false));

    $redisRecord = Database::hgetall(Keys::organizationAuditControlTest($testId));
    $this->assertSame($this->organizationId, (string) ($redisRecord['organization_id'] ?? ''));
    $this->assertSame('1', (string) ($redisRecord['gcs_uploaded'] ?? ''));

    $systemAuditEvent = Database::hgetall(Keys::SYSTEM_AUDIT_EVENT . ':' . $systemAuditEventId);
    $this->assertSame('organization.audit_control_test.error_generated', (string) ($systemAuditEvent['event_type'] ?? ''));
    $this->assertNotSame('', (string) ($systemAuditEvent['ledger_block_hash'] ?? ''));

    $proof = SystemAuditRepository::proofForEvent($systemAuditEventId);
    $this->assertTrue((bool) ($proof['has_proof'] ?? false));
    $this->assertNotSame('', (string) ($proof['block_hash'] ?? ''));

    $organizationAuditIds = Database::smembers(Keys::ORGANIZATION_AUDIT . ':' . $this->organizationId);
    $foundControlTestEvent = false;
    foreach ($organizationAuditIds as $eventId) {
      $event = Database::hgetall(Keys::ORGANIZATION_AUDIT_EVENT . ':' . $eventId);
      if ((string) ($event['event_type'] ?? '') === 'audit.control_test.error_generated') {
        $foundControlTestEvent = true;
        break;
      }
    }
    $this->assertTrue($foundControlTestEvent);

    $chainTip = (string) Database::get(Keys::SYSTEM_AUDIT_GCS_CHAIN_TIP);
    $this->assertStringContainsString('soc2/audit-ledger/alerts/2026/04/alert-test.json', $chainTip);
  }

  public function testViewerCannotGenerateAuditControlTest(): void
  {
    Database::sadd(Keys::ORGANIZATION_USER . ':' . $this->viewerUUID, $this->organizationId);
    Database::hset(Keys::ORGANIZATION_RELATIONSHIP . ':' . $this->organizationId . ':' . $this->viewerUUID, [
      'role' => 'viewer',
      'status' => 'active',
      'scopes' => 'work.read,sites.read',
      'created_at' => date('c'),
      'accepted_at' => date('c'),
    ]);

    $service = new OrganizationAuditControlTestService(
      organizationService: $this->organizationService,
      alertArtifactUploader: static function (): array {
        TestCase::fail('Viewer path should not reach the GCS uploader.');
      }
    );

    $result = $service->generateErrorTest($this->viewerUUID, $this->organizationId, [
      'summary' => 'Should be denied',
      'source' => 'phpunit',
    ]);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('permission', strtolower((string) ($result['message'] ?? '')));
  }

  private function seedUser(string $userUUID, string $email): void
  {
    Database::hset(Keys::USER . ':' . $userUUID, [
      'user_uuid' => $userUUID,
      'email' => $email,
      'full_name' => 'Audit Test User',
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
