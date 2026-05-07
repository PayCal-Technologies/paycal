<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Cache;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;

/**
 * EarningsCacheService.php
 *
 * Purpose: Manage short-lived earnings response caches that reduce repeated
 * decryption and recomputation for user-scoped reporting requests.
 *
 * Developer notes:
 * - Cache key schema and TTL choices affect correctness as much as performance.
 * - Keep scope isolation strict so cached earnings data never crosses user or
 *   session boundaries.
 *
 * Architectural role:
 * - Infrastructure cache adapter for earnings payload reuse across reporting
 *   endpoints and rendering flows.
 * - Encapsulates cache-key and TTL policy outside the HTTP and Domain layers.
 * - Moved from Domain to Infrastructure/Cache to enforce dependency rules:
 *   Domain must not depend on persistence or I/O services.
 *
 * @category   Infrastructure
 * @package    PayCal\Infrastructure\Cache
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * Cache helper for expensive earnings API responses.
 *
 * Scope: short-lived, per-user, per-session caches to avoid repeated
 * encrypted payload decryption on each Earnings page load.
 */
final class EarningsCacheService
{
  private const DEFAULT_TTL_SECONDS = 90;
  private const CACHE_SCHEMA = 'v3';

  /**
   * @param array<string, mixed> $payload
   */
  public static function putYearPayload(
    string $userUUID,
    string $scope,
    int $year,
    string $sessionHash,
    array $payload,
    int $ttlSeconds = self::DEFAULT_TTL_SECONDS
  ): void {
    if ($userUUID === '' || $sessionHash === '') {
      return;
    }

    $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) {
      return;
    }

    Database::set(self::yearKey($userUUID, $scope, $year, $sessionHash), $encoded, max(1, $ttlSeconds));
  }

  /**
   * @return null|array<string, mixed>
   */
  public static function getYearPayload(string $userUUID, string $scope, int $year, string $sessionHash): ?array
  {
    if ($userUUID === '' || $sessionHash === '') {
      return null;
    }

    $raw = Database::get(self::yearKey($userUUID, $scope, $year, $sessionHash));
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      return null;
    }

    /** @var array<string, mixed> $decoded */
    return $decoded;
  }

  /**
   * Handles invalidateForUser operation.
   */
  public static function invalidateForUser(string $userUUID): int
  {
    if ($userUUID === '') {
      return 0;
    }

    $deleted = 0;
    foreach (Database::scanKeys(Keys::EARNING . ':' . $userUUID . ':cache:*') as $key) {
      $deleted += Database::unlink($key);
    }

    return $deleted;
  }

  /**
   * Handles yearKey operation.
   */
  private static function yearKey(string $userUUID, string $scope, int $year, string $sessionHash): string
  {
    return Keys::EARNING
      . ':' . $userUUID
      . ':cache:' . strtolower($scope)
      . ':schema:' . self::CACHE_SCHEMA
      . ':year:' . $year
      . ':session:' . $sessionHash;
  }
}
