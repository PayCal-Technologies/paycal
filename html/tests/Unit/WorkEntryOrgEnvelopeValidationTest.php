<?php declare(strict_types=1);

use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\WorkEntry;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('unit')]
final class WorkEntryOrgEnvelopeValidationTest extends TestCase
{
  private bool $originalStrictEnvelope = false;

  protected function setUp(): void
  {
    parent::setUp();
    $this->originalStrictEnvelope = (bool) SystemConfig::get('org_shared_encryption_enforce_strict_envelope');
  }

  protected function tearDown(): void
  {
    SystemConfig::set('org_shared_encryption_enforce_strict_envelope', $this->originalStrictEnvelope);
    parent::tearDown();
  }

  #[Test]
  public function personalEnvelopePassesWhenStrictModeEnabled(): void
  {
    SystemConfig::set('org_shared_encryption_enforce_strict_envelope', true);

    $envelope = [
      'ciphertext' => base64_encode('ciphertext'),
      'nonce' => base64_encode('nonce-value'),
      'aad' => 'site-aad',
    ];

    $result = WorkEntry::validateEncryptedBlob(base64_encode((string) json_encode($envelope)));

    $this->assertTrue($result['valid']);
    $this->assertSame('', $result['error']);
  }

  #[Test]
  public function organizationEnvelopeRequiresMetadataFieldsInStrictMode(): void
  {
    SystemConfig::set('org_shared_encryption_enforce_strict_envelope', true);

    $envelope = [
      'ciphertext' => base64_encode('ciphertext'),
      'nonce' => base64_encode('nonce-value'),
      'aad' => 'site-aad',
      'meta' => [
        'encryption_mode' => 'organization',
        'org_id' => 'ORG123',
        'segment' => 'current_period',
        'key_version' => 'v2',
        // intentionally missing dek_id and needs_rewrap
      ],
    ];

    $result = WorkEntry::validateEncryptedBlob(base64_encode((string) json_encode($envelope)));

    $this->assertFalse($result['valid']);
    $this->assertStringStartsWith('missing_org_meta_', $result['error']);
  }

  #[Test]
  public function organizationEnvelopeWithRequiredMetadataPassesInStrictMode(): void
  {
    SystemConfig::set('org_shared_encryption_enforce_strict_envelope', true);

    $envelope = [
      'ciphertext' => base64_encode('ciphertext'),
      'nonce' => base64_encode('nonce-value'),
      'aad' => 'site-aad',
      'meta' => [
        'encryption_mode' => 'organization',
        'org_id' => 'ORG123',
        'segment' => 'current_period',
        'key_version' => 'v2',
        'dek_id' => 'dek-123',
        'needs_rewrap' => 'false',
      ],
    ];

    $result = WorkEntry::validateEncryptedBlob(base64_encode((string) json_encode($envelope)));

    $this->assertTrue($result['valid']);
    $this->assertSame('', $result['error']);
  }

  #[Test]
  public function invalidEncryptionModeFailsInStrictMode(): void
  {
    SystemConfig::set('org_shared_encryption_enforce_strict_envelope', true);

    $envelope = [
      'ciphertext' => base64_encode('ciphertext'),
      'nonce' => base64_encode('nonce-value'),
      'aad' => 'site-aad',
      'meta' => [
        'encryption_mode' => 'unknown-mode',
      ],
    ];

    $result = WorkEntry::validateEncryptedBlob(base64_encode((string) json_encode($envelope)));

    $this->assertFalse($result['valid']);
    $this->assertSame('invalid_encryption_mode', $result['error']);
  }
}
