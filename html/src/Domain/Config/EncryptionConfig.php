<?php declare(strict_types=1);

namespace PayCal\Domain\Config;

/**
 * EncryptionConfig.php
 *
 * Purpose: Define the EncryptionConfig class for PayCal\Domain\Config.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Config
 * @package    PayCal\Domain\Config
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

class EncryptionConfig
{
  /**
   * @var array<string, mixed>
   */
  public array $flags = [];

  private static ?bool $cryptoEnabled = null;

  private static ?bool $cryptoRequired = null;

  /**
   * @param array<string, mixed> $flags
   */
  public function __construct(array $flags = [])
  {
    $this->flags = $flags;
  }

  /**
   * Handles setEnabled operation.
   */
  public static function setEnabled(bool $enabled): void
  {
    self::$cryptoEnabled = $enabled;
  }

  /**
   * Handles setRequired operation.
   */
  public static function setRequired(bool $required): void
  {
    self::$cryptoRequired = $required;
  }

  /**
   * Handles reset operation.
   */
  public static function reset(): void
  {
    self::$cryptoEnabled = false;
    self::$cryptoRequired = false;
  }

  /**
   * @param array<string, mixed> $flags
   */
  public function setFlags(array $flags): void
  {
    $this->flags = $flags;
  }

  /**
   * @return array<string, mixed>
   */
  public function getFlags(): array
  {
    return $this->flags;
  }

    /**
     * @return array<string, bool>
     */
    public static function getConfig(): array
    {
      $enabled = self::$cryptoEnabled ?? \PayCal\Domain\Config\Environment::encryptionEnabled();
      $required = self::$cryptoRequired ?? false;

        return [
        'crypto_enabled' => $enabled,
        'crypto_required' => $required,
        ];
    }

    /**
     * Handles isEnabled operation.
     */
    public static function isEnabled(): bool
    {
      return self::$cryptoEnabled ?? \PayCal\Domain\Config\Environment::encryptionEnabled();
    }

    /**
     * Handles isRequired operation.
     */
    public static function isRequired(): bool
    {
      return self::$cryptoRequired ?? false;
    }
}

