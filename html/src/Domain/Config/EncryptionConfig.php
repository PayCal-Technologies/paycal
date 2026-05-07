<?php declare(strict_types=1);

namespace PayCal\Domain\Config;

/**
 * EncryptionConfig.php
 *
 * Purpose: Typed encryption configuration value object for flags, algorithm
 * options, and derived crypto-runtime settings.
 *
 * Developer notes:
 * - Encryption settings are compatibility-sensitive and should remain explicit
 *   so runtime crypto behavior is reviewable.
 * - Keep this focused on config representation, not operational logic.
 *
 * Architectural role:
 * - Reusable configuration value object consumed by encryption services and
 *   bootstrap paths.
 * - Encapsulates encryption configuration outside the HTTP layer.
 *
 * @category   Config
 * @package    PayCal\Domain\Config
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
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

