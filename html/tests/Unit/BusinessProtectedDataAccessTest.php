<?php declare(strict_types=1);

use PayCal\Domain\BusinessDiscoveryService;
use PayCal\Domain\BusinessProtectedDataAccess;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Infrastructure\Business\BusinessEncryptionService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('unit')]
#[Group('redis-write')]
final class BusinessProtectedDataAccessTest extends TestCase
{
  private string $suffix = '';
  private string $businessId = '';
  private string $ownerUUID = '';
  private string $memberUUID = '';
  private string $siteId = '';
  private string $consentId = '';
  private string $credentialId = '';
  private string $dekId = '';
  private bool $originalOrgEncryptionEnabled = false;

  protected function setUp(): void
  {
    parent::setUp();

    $this->suffix = bin2hex(random_bytes(4));
    $this->businessId = 'biz_gate_' . $this->suffix;
    $this->ownerUUID = 'owner_gate_' . $this->suffix;
    $this->memberUUID = 'member_gate_' . $this->suffix;
    $this->siteId = 'site_gate_' . $this->suffix;
    $this->consentId = 'consent_gate_' . $this->suffix;
    $this->credentialId = 'cred_gate_' . $this->suffix;
    $this->dekId = 'dek_gate_' . $this->suffix;
    $this->originalOrgEncryptionEnabled = (bool) SystemConfig::get('business_shared_encryption_enabled');
    SystemConfig::set('business_shared_encryption_enabled', true);

    $this->seedBusiness();
    $this->seedConsentAndWrap();
    $this->seedWorkEntry($this->siteId);
  }

  protected function tearDown(): void
  {
    foreach (Database::scanKeys('*' . $this->suffix . '*') as $key) {
      Database::unlink((string) $key);
    }

    SystemConfig::set('business_shared_encryption_enabled', $this->originalOrgEncryptionEnabled);
    parent::tearDown();
  }

  #[Test]
  public function readMemberWorkAllowsOnlyWhenMembershipConsentWrapAndEnvelopeMatch(): void
  {
    $result = (new BusinessProtectedDataAccess())->readMemberWork(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      2026,
    );

    $this->assertTrue($result['success']);
    $this->assertCount(1, $result['data']['entries']);
    $this->assertSame($this->consentId, $result['data']['consent_id']);
    $this->assertSame($this->dekId, $result['data']['dek_id']);
  }

  #[Test]
  public function readMemberWorkAllowsActiveSelfScopedMemberWithConsent(): void
  {
    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->memberUUID, [
      'role' => 'member',
      'scopes' => 'work.read,work.scope.self',
    ]);

    $result = (new BusinessProtectedDataAccess())->readMemberWork(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      2026,
    );

    $this->assertTrue($result['success']);
    $this->assertCount(1, $result['data']['entries']);
  }

  #[Test]
  public function readMemberWorkDeniesInactiveTargetMembership(): void
  {
    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->memberUUID, [
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_REVOKED,
    ]);

    $result = (new BusinessProtectedDataAccess())->readMemberWork(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      2026,
    );

    $this->assertFalse($result['success']);
    $this->assertSame('target_membership_inactive', $result['reason']);
  }

  #[Test]
  public function readMemberWorkDeniesMissingConsentOrWrapBeforeFetchingEntries(): void
  {
    Database::unlink(Keys::businessConsent($this->consentId));
    Database::unlink(Keys::businessDekWrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v1',
      $this->memberUUID,
      $this->credentialId,
    ));

    $result = (new BusinessProtectedDataAccess())->readMemberWork(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      2026,
    );

    $this->assertFalse($result['success']);
    $this->assertSame('missing_consent_or_wrap', $result['reason']);
  }

  #[Test]
  public function readMemberWorkFiltersEntriesOutsideBusinessSiteIndex(): void
  {
    Database::unlink(Keys::WORK . ':' . $this->memberUUID . ':2026-01-02:' . $this->siteId);
    $this->seedWorkEntry('personal_site_' . $this->suffix);

    $result = (new BusinessProtectedDataAccess())->readMemberWork(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      2026,
    );

    $this->assertTrue($result['success']);
    $this->assertSame([], $result['data']['entries']);
  }

  #[Test]
  public function readMemberWorkFiltersEntriesWithoutMatchingEnvelopeWrap(): void
  {
    Database::hset(Keys::WORK . ':' . $this->memberUUID . ':2026-01-02:' . $this->siteId, [
      'encrypted_blob' => $this->encryptedBlob('different-dek'),
    ]);

    $result = (new BusinessProtectedDataAccess())->readMemberWork(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      2026,
    );

    $this->assertTrue($result['success']);
    $this->assertSame([], $result['data']['entries']);
  }

  #[Test]
  public function readMemberWorkAllowsOwnerSelfReadForLegacyBusinessLinkedPlaintextWork(): void
  {
    $legacyOwnerUUID = 'legacy_owner_gate_' . $this->suffix;
    $legacySiteId = 'legacy_site_gate_' . $this->suffix;
    Database::hset(Keys::SITE . ':' . $legacyOwnerUUID . ':' . $legacySiteId, [
      'business_id' => $this->businessId,
      'organization_id' => $this->businessId,
      'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_LINKED,
    ]);
    Database::sadd(Keys::BUSINESS_SITE . ':' . $this->businessId, $legacyOwnerUUID . ':' . $legacySiteId);
    Database::hset(Keys::WORK . ':' . $this->ownerUUID . ':2026-01-03:' . $legacySiteId, [
      'date' => '2026-01-03',
      'site_id' => $legacySiteId,
      'site_name' => 'Legacy Business Site',
      'regular_hours' => '4',
      'gross' => '200',
      'net' => '160',
    ]);

    $result = (new BusinessProtectedDataAccess())->readMemberWork(
      $this->ownerUUID,
      $this->businessId,
      $this->ownerUUID,
      2026,
    );

    $this->assertTrue($result['success']);
    $this->assertCount(1, $result['data']['entries']);
    $this->assertArrayHasKey(
      Keys::WORK . ':' . $this->ownerUUID . ':2026-01-03:' . $legacySiteId,
      $result['data']['entries'],
    );
  }

  private function seedBusiness(): void
  {
    Database::hset(Keys::BUSINESS . ':' . $this->businessId, [
      'business_id' => $this->businessId,
      'owner_uuid' => $this->ownerUUID,
      'status' => 'active',
    ]);

    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->ownerUUID, [
      'business_id' => $this->businessId,
      'user_uuid' => $this->ownerUUID,
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
      'role' => 'owner',
    ]);

    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->memberUUID, [
      'business_id' => $this->businessId,
      'user_uuid' => $this->memberUUID,
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
      'role' => 'contributor',
      'scopes' => 'work.read,work.scope.business',
    ]);

    Database::hset(Keys::SITE . ':' . $this->ownerUUID . ':' . $this->siteId, [
      'site_id' => $this->siteId,
      'site_name' => 'Business Site',
      'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_BUSINESS,
      'business_managed' => '1',
      'business_id' => $this->businessId,
    ]);

    Database::sadd(Keys::BUSINESS_SITE . ':' . $this->businessId, $this->ownerUUID . ':' . $this->siteId);
  }

  private function seedConsentAndWrap(): void
  {
    Database::hset(Keys::businessConsent($this->consentId), [
      'consent_id' => $this->consentId,
      'business_id' => $this->businessId,
      'user_uuid' => $this->memberUUID,
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
      'accepted_at' => date('c'),
    ]);
    Database::sadd(Keys::businessConsentsByBusiness($this->businessId), $this->consentId);
    Database::sadd(Keys::businessConsentsByUser($this->memberUUID), $this->consentId);

    $store = (new BusinessEncryptionService())->storeBusinessDekWrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v1',
      $this->memberUUID,
      $this->credentialId,
      'wrapped-dek',
      $this->consentId,
      'hkdf-passkey-v1',
      $this->dekId,
    );

    $this->assertTrue($store['success']);
  }

  private function seedWorkEntry(string $siteId): void
  {
    Database::hset(Keys::WORK . ':' . $this->memberUUID . ':2026-01-02:' . $siteId, [
      'date' => '2026-01-02',
      'site_id' => $siteId,
      'site_name' => 'Business Site',
      'site_owner_uuid' => $this->ownerUUID,
      'regular_hours' => '8',
      'gross' => '400',
      'net' => '320',
      'encrypted_blob' => $this->encryptedBlob($this->dekId),
    ]);
  }

  private function encryptedBlob(string $dekId): string
  {
    return base64_encode((string) json_encode([
      'ciphertext' => base64_encode('ciphertext'),
      'nonce' => base64_encode('nonce'),
      'aad' => 'aad',
      'meta' => [
        'encryption_mode' => 'business',
        'business_id' => $this->businessId,
        'segment' => BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
        'key_version' => 'v1',
        'dek_id' => $dekId,
        'needs_rewrap' => 'false',
      ],
    ]));
  }
}
