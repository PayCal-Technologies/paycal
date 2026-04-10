<?php declare(strict_types=1);

namespace PayCal\Domain\Encryption;

/**
 * CryptoVersions.php
 *
 * Purpose: Define the CryptoVersions class for PayCal\Domain\Encryption.
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
 * Class CryptoVersions.
 *
 * Centralizes version constants for encryption algorithms and key derivation.
 * These versions are immutable after deployment and support forward compatibility.
 */
class CryptoVersions
{
  /**
   * Encryption Algorithm: AES-256-GCM
   * Current algorithm for data encryption.
   * Provides authenticated encryption (covers both confidentiality and authenticity).
   */
  public const ALGORITHM_VERSION = 1;
  public const ALGORITHM_NAME = 'AES-256-GCM';

  /**
   * Key Derivation Function: PBKDF2-SHA256
   * Used to derive encryption keys from user passwords/secrets.
   * Iterations tuned to resist brute force attacks.
   */
  public const KDF_VERSION = 1;
  public const KDF_NAME = 'PBKDF2-SHA256';
  public const KDF_ITERATIONS = 600000;  // OWASP recommendation as of 2024
  public const KDF_HASH_ALGO = 'sha256';

  /**
   * Envelope Format Schema Version
   * Incremented when the envelope structure changes.
   */
  public const ENVELOPE_VERSION = 1;

  /**
   * Gets the current supported algorithm version.
   *
   * @return int Algorithm version number
   */
  public static function getAlgorithmVersion(): int
  {
    return self::ALGORITHM_VERSION;
  }

  /**
   * Gets the algorithm name for the current version.
   *
   * @return string Algorithm name
   */
  public static function getAlgorithmName(): string
  {
    return self::ALGORITHM_NAME;
  }

  /**
   * Gets the current supported KDF version.
   *
   * @return int KDF version number
   */
  public static function getKdfVersion(): int
  {
    return self::KDF_VERSION;
  }

  /**
   * Gets the KDF name for the current version.
   *
   * @return string KDF name
   */
  public static function getKdfName(): string
  {
    return self::KDF_NAME;
  }

  /**
   * Gets the current supported envelope format version.
   *
   * @return int Envelope version number
   */
  public static function getEnvelopeVersion(): int
  {
    return self::ENVELOPE_VERSION;
  }

  /**
   * Checks if an algorithm version is supported.
   * This allows graceful degradation if newer versions are used.
   *
   * @param int $version Algorithm version to check
   *
   * @return bool True if supported, false otherwise
   */
  public static function isSupportedAlgorithmVersion(int $version): bool
  {
    // Currently only support version 1
    // Future: implement array of supported versions
    return self::ALGORITHM_VERSION === $version;
  }

  /**
   * Checks if a KDF version is supported.
   *
   * @param int $version KDF version to check
   *
   * @return bool True if supported, false otherwise
   */
  public static function isSupportedKdfVersion(int $version): bool
  {
    // Currently only support version 1
    // Future: implement array of supported versions
    return self::KDF_VERSION === $version;
  }

  /**
   * Checks if an envelope format version is supported.
   *
   * @param int $version Envelope version to check
   *
   * @return bool True if supported, false otherwise
   */
  public static function isSupportedEnvelopeVersion(int $version): bool
  {
    // Currently only support version 1
    // Future: implement array of supported versions
    return self::ENVELOPE_VERSION === $version;
  }

  /**
   * Gets all current version information as an array.
   * Useful for client-side reporting and telemetry.
   *
   * @return array<string, array<string, int|string>> Version information
   */
  public static function getVersionInfo(): array
  {
    return [
        'algorithm' => [
            'version' => self::ALGORITHM_VERSION,
            'name' => self::ALGORITHM_NAME,
        ],
        'kdf' => [
            'version' => self::KDF_VERSION,
            'name' => self::KDF_NAME,
            'iterations' => self::KDF_ITERATIONS,
            'hashAlgo' => self::KDF_HASH_ALGO,
        ],
        'envelope' => [
            'version' => self::ENVELOPE_VERSION,
        ],
    ];
  }
}
