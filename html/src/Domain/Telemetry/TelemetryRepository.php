<?php declare(strict_types=1);

namespace PayCal\Domain\Telemetry;

use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;

/**
 * TelemetryRepository.php
 *
 * Purpose: Redis-backed telemetry read/write helpers with stream-authorization guards
 * for event counters and metrics surfaced by diagnostics endpoints.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain\Telemetry
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */
final class TelemetryRepository
{
  /** @return array{allowed: bool, reason: string} */
  public static function authorize(TelemetryAccessToken $token, string $stream): array
  {
    if (!$token->allowsStream($stream)) {
      return ['allowed' => false, 'reason' => 'telemetry_access_denied'];
    }

    return ['allowed' => true, 'reason' => 'ok'];
  }

  /** @return array{allowed: bool, reason: string} */
  public static function guardCrossStreamJoin(
    TelemetryAccessToken $token,
    string $leftStream,
    string $rightStream
  ): array {
    $left = strtolower(trim($leftStream));
    $right = strtolower(trim($rightStream));

    if ($left !== $right) {
      return ['allowed' => false, 'reason' => 'cross_stream_join_denied'];
    }

    return self::authorize($token, $left);
  }

  public static function incrementEventCounter(string $eventType): void
  {
    $counterKey = Keys::TELEMETRY . ':' . $eventType;
    $count = Database::incr($counterKey);
    // Set a 24-hour TTL on first creation so orphaned event counters do not
    // accumulate in keyspace forever.  Mirrors the RateLimiter pattern.
    if (1 === $count) {
      Database::expire($counterKey, 86400);
    }
  }

  /**
   * @param array<int, string> $eventTypes
   * @return array{allowed: bool, reason: string, events: array<string, int>}
   */
  public static function fetchWhitelistedEventCounts(
    TelemetryAccessToken $token,
    array $eventTypes,
    string $dayBucket
  ): array {
    $authorization = self::authorize($token, 'product');
    if (!$authorization['allowed']) {
      return ['allowed' => false, 'reason' => $authorization['reason'], 'events' => []];
    }

    if (count($eventTypes) > 20) {
      throw new \RuntimeException('Telemetry events exceed maximum allowed types (20)');
    }

    $events = [];
    foreach ($eventTypes as $eventType) {
      $type = strtolower(trim($eventType));
      if ($type === '') {
        continue;
      }

      $key = Keys::TELEMETRY . ':event:' . $type . ':' . $dayBucket;
      $count = (int) (Database::get($key) ?: 0);
      if ($count > 0) {
        $events[$type] = $count;
      }
    }

    return ['allowed' => true, 'reason' => 'ok', 'events' => $events];
  }

  /**
   * @param array<int, string> $types
   * @return array{allowed: bool, reason: string, counters: array<string, int>}
   */
  public static function fetchEncryptionClientCounters(
    TelemetryAccessToken $token,
    string $schema,
    array $types
  ): array {
    $authorization = self::authorize($token, 'security');
    if (!$authorization['allowed']) {
      return ['allowed' => false, 'reason' => $authorization['reason'], 'counters' => []];
    }

    if (count($types) > 50) {
      throw new \RuntimeException('Encryption telemetry types exceed maximum allowed set (50)');
    }

    $counters = [];
    foreach ($types as $type) {
      $eventType = strtolower(trim($type));
      if ($eventType === '') {
        continue;
      }

      $key = "telemetry:encryption:{$schema}:client:{$eventType}";
      $counters[$eventType] = (int) (Database::get($key) ?: 0);
    }

    return ['allowed' => true, 'reason' => 'ok', 'counters' => $counters];
  }
}
