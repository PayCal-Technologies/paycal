<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Config\EncryptionConfig;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 *
 */
#[Group('unit')]
final class EncryptionConfigTest extends TestCase
{
  public function testDefaultFlags(): void
  {
    EncryptionConfig::reset();
    $this->assertFalse(EncryptionConfig::isEnabled());
    $this->assertFalse(EncryptionConfig::isRequired());
  }

  public function testSetFlags(): void
  {
    EncryptionConfig::setEnabled(true);
    EncryptionConfig::setRequired(true);
    $this->assertTrue(EncryptionConfig::isEnabled());
    $this->assertTrue(EncryptionConfig::isRequired());
    EncryptionConfig::reset();
  }
}
