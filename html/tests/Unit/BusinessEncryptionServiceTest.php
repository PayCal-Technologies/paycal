<?php declare(strict_types=1);

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Database;
use PayCal\Domain\BusinessDiscoveryService;
use PayCal\Infrastructure\Business\BusinessEncryptionService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('unit')]
#[Group('redis-write')]
final class BusinessEncryptionServiceTest extends TestCase
{
  private string $businessId = '';
  private string $userUUID = '';
  private string $consentId = '';
  private string $credentialId = '';

  private function businessUnwrapDeniedCounterKey(string $reason): string
  {
    $schema = SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;

    return "telemetry:encryption:{$schema}:business:unwrap_denied_{$reason}";
  }

  private function clearBusinessUnwrapDeniedTelemetryCounters(): void
  {
    $schema = SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;
    foreach (Database::scanKeys("telemetry:encryption:{$schema}:business:unwrap_denied_*") as $key) {
      Database::unlink((string) $key);
    }
  }

  protected function setUp(): void
  {
    parent::setUp();

    $suffix = bin2hex(random_bytes(4));
    $this->businessId = 'business_enc_' . $suffix;
    $this->userUUID = 'user_enc_' . $suffix;
    $this->consentId = 'consent_' . $suffix;
    $this->credentialId = 'cred_' . $suffix;
    $this->clearBusinessUnwrapDeniedTelemetryCounters();

    Database::hset(Keys::BUSINESS . ':' . $this->businessId, [
      'business_id' => $this->businessId,
      'owner_uuid' => $this->userUUID,
      'status' => 'active',
    ]);

    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->userUUID, [
      'business_id' => $this->businessId,
      'user_uuid' => $this->userUUID,
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
      'role' => 'member',
    ]);

    Database::hset(Keys::businessConsent($this->consentId), [
      'consent_id' => $this->consentId,
      'business_id' => $this->businessId,
      'user_uuid' => $this->userUUID,
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
      'accepted_at' => date('c'),
    ]);
  }

  protected function tearDown(): void
  {
    Database::unlink(Keys::BUSINESS . ':' . $this->businessId);
    Database::unlink(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->userUUID);
    Database::unlink(Keys::businessConsent($this->consentId));

    Database::unlink(
      Keys::businessDekWrap(
        $this->businessId,
        BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
        'v1',
        $this->userUUID,
        $this->credentialId
      )
    );

    $this->clearBusinessUnwrapDeniedTelemetryCounters();

    parent::tearDown();
  }

  #[Test]
  public function storeBusinessDekWrapRejectsMissingConsentBinding(): void
  {
    $service = new BusinessEncryptionService();

    $result = $service->storeBusinessDekWrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v1',
      $this->userUUID,
      $this->credentialId,
      'wrapped-value',
      ''
    );

    $this->assertFalse($result['success']);
    $this->assertSame('A valid active consent binding is required for wrap creation.', $result['message']);
  }

  #[Test]
  public function storeAndResolveWrapSucceedsWithValidConsentBinding(): void
  {
    $service = new BusinessEncryptionService();

    $store = $service->storeBusinessDekWrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v1',
      $this->userUUID,
      $this->credentialId,
      'wrapped-value',
      $this->consentId
    );

    $this->assertTrue($store['success']);

    $resolve = $service->resolveActiveWrapForUnwrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v1',
      $this->userUUID,
      $this->credentialId,
      $this->consentId
    );

    $this->assertTrue($resolve['success']);
    $this->assertSame('wrapped-value', $resolve['data']['wrapped_dek']);
    $this->assertSame($this->consentId, $resolve['data']['consent_id']);
  }

  #[Test]
  public function resolveWrapFailsWhenMembershipNotActive(): void
  {
    $service = new BusinessEncryptionService();

    $store = $service->storeBusinessDekWrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v1',
      $this->userUUID,
      $this->credentialId,
      'wrapped-value',
      $this->consentId
    );
    $this->assertTrue($store['success']);

    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->userUUID, [
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_REVOKED,
    ]);

    $resolve = $service->resolveActiveWrapForUnwrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v1',
      $this->userUUID,
      $this->credentialId,
      $this->consentId
    );

    $this->assertFalse($resolve['success']);
    $this->assertSame('Membership is not active; unwrap denied.', $resolve['message']);
    $this->assertSame('1', (string) Database::get($this->businessUnwrapDeniedCounterKey('inactive_membership')));
  }

  #[Test]
  public function resolveWrapFailsWhenConsentMismatchesWrapBinding(): void
  {
    $service = new BusinessEncryptionService();

    $store = $service->storeBusinessDekWrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v1',
      $this->userUUID,
      $this->credentialId,
      'wrapped-value',
      $this->consentId
    );
    $this->assertTrue($store['success']);

    $resolve = $service->resolveActiveWrapForUnwrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v1',
      $this->userUUID,
      $this->credentialId,
      'different-consent-id'
    );

    $this->assertFalse($resolve['success']);
    $this->assertSame('Provided consent_id does not match wrap binding.', $resolve['message']);
    $this->assertSame('1', (string) Database::get($this->businessUnwrapDeniedCounterKey('no_consent')));
  }

  #[Test]
  public function resolveWrapFailsWhenWrapMissingEmitsTelemetry(): void
  {
    $service = new BusinessEncryptionService();

    $resolve = $service->resolveActiveWrapForUnwrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v9-missing',
      $this->userUUID,
      $this->credentialId,
      $this->consentId
    );

    $this->assertFalse($resolve['success']);
    $this->assertSame('Business DEK wrap not found.', $resolve['message']);
    $this->assertSame('1', (string) Database::get($this->businessUnwrapDeniedCounterKey('missing_wrap')));
  }

  #[Test]
  public function resolveWrapFailsAfterExplicitWrapRevocation(): void
  {
    $service = new BusinessEncryptionService();

    $store = $service->storeBusinessDekWrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v1',
      $this->userUUID,
      $this->credentialId,
      'wrapped-value',
      $this->consentId
    );
    $this->assertTrue($store['success']);

    $revoke = $service->revokeWrapsForMembership($this->businessId, $this->userUUID, 'test_revocation');
    $this->assertTrue($revoke['success']);
    $this->assertSame(1, $revoke['data']['revoked_wrap_count']);

    $resolve = $service->resolveActiveWrapForUnwrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v1',
      $this->userUUID,
      $this->credentialId,
      $this->consentId
    );

    $this->assertFalse($resolve['success']);
    $this->assertSame('Business DEK wrap is inactive.', $resolve['message']);
    $this->assertSame('1', (string) Database::get($this->businessUnwrapDeniedCounterKey('inactive_membership')));
  }
}
