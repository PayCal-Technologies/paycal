<?php declare(strict_types=1);

namespace PayCal\Domain\Encryption;

/**
 * EncryptionConfig.php
 *
 * Purpose: Runtime feature flag manager for encryption: controls crypto_enabled and crypto_required gates loaded from environment configuration.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain\Encryption
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Class EncryptionConfig.
 *
 * Centralizes encryption feature flag management.
 * Flags are loaded from environment or defaults and can be queried at runtime.
 *
 * Feature Flags:
 * - crypto_enabled: Master switch; allow encryption operations (default: false)
 * - crypto_required: Enforce encrypted reads/writes; reject plaintext (default: false)
 */
class EncryptionConfig
{
  /**
   * Whether encryption operations are allowed.
   */
  private static ?bool $cryptoEnabled = null;

  /**
   * Whether encryption is required (rejects plaintext).
   */
  private static ?bool $cryptoRequired = null;

  /**
   * Checks if encryption is enabled.
   * When false, encryption operations should be skipped.
   * When true, encryption can proceed if crypto_required allows it.
   *
   * @return bool True if encryption is enabled
   */
  public static function isEnabled(): bool
  {
    if (null === self::$cryptoEnabled) {
      self::$cryptoEnabled = self::getEnvFlag('CRYPTO_ENABLED', false);
    }

    return self::$cryptoEnabled;
  }

  /**
   * Checks if encryption is required.
   * When true, plaintext operations are rejected.
   * Must have crypto_enabled=true to take effect.
   *
   * @return bool True if encryption is required
   */
  public static function isRequired(): bool
  {
    if (null === self::$cryptoRequired) {
      self::$cryptoRequired = self::getEnvFlag('CRYPTO_REQUIRED', false);
    }

    return self::$cryptoRequired;
  }

  /**
   * Sets the encryption enabled flag at runtime.
   * Useful for testing or dynamic configuration.
   *
   * @param bool $enabled Whether encryption is enabled
   */
  public static function setEnabled(bool $enabled): void
  {
    self::$cryptoEnabled = $enabled;
  }

  /**
   * Sets the encryption required flag at runtime.
   * Useful for testing or dynamic configuration.
   *
   * @param bool $required Whether encryption is required
   */
  public static function setRequired(bool $required): void
  {
    self::$cryptoRequired = $required;
  }

  /**
   * Resets flags to null so they reload from environment.
   * Useful for testing.
   */
  public static function reset(): void
  {
    self::$cryptoEnabled = null;
    self::$cryptoRequired = null;
  }

  /**
   * Gets all configuration as an array.
   * Useful for debugging and telemetry.
   *
   * @return array<string, bool> Configuration state
   */
  public static function getConfig(): array
  {
    return [
        'crypto_enabled' => self::isEnabled(),
        'crypto_required' => self::isRequired(),
    ];
  }

  /**
   * Reads a boolean flag from environment variables.
   *
   * @param string $envVar  Environment variable name
   * @param bool   $default Default value if not set
   *
   * @return bool Flag value
   */
  private static function getEnvFlag(string $envVar, bool $default): bool
  {
    $value = getenv($envVar);

    if (false === $value) {
      return $default;
    }

    // Handle various truthy values
    $truthy = ['1', 'true', 'True', 'TRUE', 'yes', 'Yes', 'YES', 'on'];

    return in_array($value, $truthy, true);
  }
}
