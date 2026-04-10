<?php declare(strict_types=1);

namespace PayCal\Domain\Encryption;

use PayCal\Domain\Database;
use PayCal\Domain\Log;

/**
 * ClientCapabilities.php
 *
 * Purpose: Define the ClientCapabilities class for PayCal\Domain\Encryption.
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
 * Class ClientCapabilities.
 *
 * Manages detection and caching of client capabilities.
 * Stores capability reports from clients to determine if encryption is possible.
 *
 * Capability Report Structure:
 * {
 *   "webCryptoSupported": bool,
 *   "aesGcmSupported": bool,
 *   "pbkdf2Supported": bool,
 *   "userAgent": string,
 *   "timestamp": int (milliseconds)
 * }
 */
class ClientCapabilities
{
  /**
   * Redis key prefix for capability records.
   */
  private const REDIS_KEY_PREFIX = 'client_capabilities';

  /**
   * How long to cache capability detection (in seconds).
   * Clients won't be re-probed unless cache expires.
   */
  private const CAPABILITY_CACHE_TTL = 2592000;  // 30 days

  /**
   * Validates a capability report structure.
   *
    * @param array<string, mixed> $report Capability report to validate
   *
   * @return bool True if valid, false otherwise
   */
  public static function isValidReport(array $report): bool
  {
    $requiredKeys = ['webCryptoSupported', 'aesGcmSupported', 'pbkdf2Supported', 'userAgent', 'timestamp'];

    foreach ($requiredKeys as $key) {
      if (!isset($report[$key])) {
        return false;
      }
    }

    // Validate boolean fields
    if (!is_bool($report['webCryptoSupported'])
        || !is_bool($report['aesGcmSupported'])
        || !is_bool($report['pbkdf2Supported'])) {
      return false;
    }

    // Validate string fields
    if (!is_string($report['userAgent']) || empty($report['userAgent'])) {
      return false;
    }

    // Validate timestamp is reasonable (milliseconds, not in the future beyond 1 hour)
    if (!is_int($report['timestamp']) || $report['timestamp'] < 0) {
      return false;
    }
    $nowMs = (int) ceil(microtime(true) * 1000);
    $maxFutureTime = $nowMs + 3600 * 1000;  // 1 hour grace in ms
    if ($report['timestamp'] > $maxFutureTime) {
      return false;
    }

    return true;
  }

  /**
   * Stores a client capability report in Redis.
   * Associates capabilities with the currently authenticated user.
   *
   * @param string $userId User ID (from session)
    * @param array<string, mixed> $report Capability report to store
   *
   * @throws \InvalidArgumentException If report is invalid
   * @throws \Exception                On Redis error
   */
  public static function store(string $userId, array $report): void
  {
    if (!self::isValidReport($report)) {
      throw new \InvalidArgumentException('Invalid capability report');
    }

    $redisKey = self::getRedisKey($userId);
    $json = json_encode($report);

    if (false === $json) {
      throw new \Exception('Failed to encode capability report as JSON');
    }

    try {
      Database::set($redisKey, $json, self::CAPABILITY_CACHE_TTL);
    } catch (\Exception $e) {
      Log::error('[ClientCapabilities] Failed to store capabilities: '.$e->getMessage());

      throw $e;
    }
  }

  /**
   * Retrieves a stored capability report for a user.
   *
   * @param string $userId User ID
   *
   * @return null|array<string, mixed> Capability report or null if not cached
   *
   * @throws \Exception On Redis error
   */
  public static function retrieve(string $userId): ?array
  {
    $redisKey = self::getRedisKey($userId);

    try {
      $json = Database::get($redisKey);

      if (!$json) {
        return null;
      }

      $report = json_decode($json, true);

      if (!is_array($report)) {
        return null;
      }

      $normalized = [];
      foreach ($report as $k => $v) {
        $normalized[(string) $k] = $v;
      }

      return $normalized;
    } catch (\Exception $e) {
      Log::warning('[ClientCapabilities] Failed to retrieve capabilities: '.$e->getMessage());

      return null;
    }
  }

  /**
   * Checks if a user's client supports encryption.
   * Requires all three: WebCrypto, AES-GCM, and PBKDF2.
   *
   * @param string $userId User ID
   *
   * @return bool True if encryption is supported, false otherwise
   */
  public static function supportsEncryption(string $userId): bool
  {
    $report = self::retrieve($userId);

    if (!$report) {
      return false;
    }

        return ($report['webCryptoSupported'] ?? false) === true
          && ($report['aesGcmSupported'] ?? false) === true
          && ($report['pbkdf2Supported'] ?? false) === true;
  }

  /**
   * Clears cached capabilities for a user.
   * Forces re-detection on next operation.
   *
   * @param string $userId User ID
   */
  public static function clear(string $userId): void
  {
    $redisKey = self::getRedisKey($userId);

    try {
      Database::del($redisKey);
    } catch (\Exception $e) {
      Log::warning('[ClientCapabilities] Failed to clear capabilities: '.$e->getMessage());
    }
  }

  /**
   * Gets acceptable capability report for a client without any crypto support.
   * Used for initializing unsupported clients.
   *
   * @return array<string, bool|string|int> Minimal capability report
   */
  public static function getMinimalReport(): array
  {
    $userAgentRaw = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $userAgent = is_scalar($userAgentRaw) ? (string) $userAgentRaw : 'Unknown';

    return [
        'webCryptoSupported' => false,
        'aesGcmSupported' => false,
        'pbkdf2Supported' => false,
        'userAgent' => $userAgent,
        'timestamp' => (int) ceil(microtime(true) * 1000),
    ];
  }

  /**
   * Constructs the Redis key for a user's capabilities.
   *
   * @param string $userId User ID
   *
   * @return string Redis key
   */
  private static function getRedisKey(string $userId): string
  {
    return self::REDIS_KEY_PREFIX.':'.$userId;
  }
}
