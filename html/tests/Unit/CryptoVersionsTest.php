<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\CryptoVersions;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 *
 */
#[Group('unit')]
final class CryptoVersionsTest extends TestCase
{
  public function testVersionConstants(): void
  {
    $this->assertSame(1, CryptoVersions::getAlgorithmVersion());
    $this->assertSame('AES-256-GCM', CryptoVersions::getAlgorithmName());
    $this->assertSame(1, CryptoVersions::getKdfVersion());
    $this->assertSame('PBKDF2-SHA256', CryptoVersions::getKdfName());
    $this->assertSame(1, CryptoVersions::getEnvelopeVersion());
  }

  public function testSupportedVersions(): void
  {
    $this->assertTrue(CryptoVersions::isSupportedAlgorithmVersion(1));
    $this->assertFalse(CryptoVersions::isSupportedAlgorithmVersion(2));
    $this->assertTrue(CryptoVersions::isSupportedKdfVersion(1));
    $this->assertFalse(CryptoVersions::isSupportedKdfVersion(2));
    $this->assertTrue(CryptoVersions::isSupportedEnvelopeVersion(1));
    $this->assertFalse(CryptoVersions::isSupportedEnvelopeVersion(2));
  }
}
