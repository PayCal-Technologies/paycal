<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Telemetry;

use PayCal\Infrastructure\Telemetry\ContactSupportTelemetry;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Telemetry\TelemetryAccessToken;

/**
 * Class TelemetryRepository
 *
 * Centralized telemetry read/write helpers with stream authorization guards
 * for counters and metrics used by diagnostics endpoints.
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

  /** Increments a global telemetry event counter for a named event type. */
  public static function incrementEventCounter(string $eventType): void
  {
    $counterKey = Keys::TELEMETRY . ':' . $eventType;
    Database::incr($counterKey);
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

  /**
   * @param array<int, string> $durationBuckets
   * @return array{allowed: bool, reason: string, metrics: array<string, int>}
   */
  public static function fetchSessionLifecycleMetrics(
    TelemetryAccessToken $token,
    string $dayBucket,
    array $durationBuckets
  ): array {
    $authorization = self::authorize($token, 'product');
    if (!$authorization['allowed']) {
      return ['allowed' => false, 'reason' => $authorization['reason'], 'metrics' => []];
    }

    if (count($durationBuckets) > 10) {
      throw new \RuntimeException('Session duration bucket set exceeds maximum allowed size (10)');
    }

    $metrics = [
      'logins_today' => (int) (Database::get(Keys::TELEMETRY . ':auth:login:' . $dayBucket) ?: 0),
      'logouts_today' => (int) (Database::get(Keys::TELEMETRY . ':auth:logout:' . $dayBucket) ?: 0),
    ];

    foreach ($durationBuckets as $bucket) {
      $name = strtolower(trim($bucket));
      if ($name === '') {
        continue;
      }

      $metrics['duration:' . $name] = (int) (Database::get(Keys::TELEMETRY . ':session:duration:' . $name) ?: 0);
    }

    return ['allowed' => true, 'reason' => 'ok', 'metrics' => $metrics];
  }

  /** @return array{allowed: bool, reason: string, metrics: array<string, mixed>} */
  public static function fetchContactSupportMetrics(TelemetryAccessToken $token): array
  {
    $authorization = self::authorize($token, 'product');
    if (!$authorization['allowed']) {
      return ['allowed' => false, 'reason' => $authorization['reason'], 'metrics' => []];
    }

    return [
      'allowed' => true,
      'reason' => 'ok',
      'metrics' => ContactSupportTelemetry::getMetricsSnapshot(),
    ];
  }

  /** @return array{allowed: bool, reason: string, metrics: array<string, mixed>} */
  public static function fetchScraperDefenseMetrics(TelemetryAccessToken $token): array
  {
    $authorization = self::authorize($token, 'product');
    if (!$authorization['allowed']) {
      return ['allowed' => false, 'reason' => $authorization['reason'], 'metrics' => []];
    }

    $prefix = Keys::TELEMETRY . ':scraper';
    $daySeries = self::readIntegerSeries($prefix . ':attempts:day:');
    $weekSeries = self::readIntegerSeries($prefix . ':attempts:week:');
    $monthSeries = self::readIntegerSeries($prefix . ':attempts:month:');
    $yearSeries = self::readIntegerSeries($prefix . ':attempts:year:');

    $netblockRows = [];
    $labelsKey = $prefix . ':netblock:labels';
    $countPrefix = $prefix . ':netblock:count:';
    foreach (Database::scanKeys($countPrefix . '*') as $countKey) {
      $slug = str_replace($countPrefix, '', $countKey);
      if ($slug === '') {
        continue;
      }

      $count = (int) (Database::get($countKey) ?: 0);
      if ($count <= 0) {
        continue;
      }

      $label = Database::hget($labelsKey, $slug);
      if ($label === '') {
        $label = 'Netblock ' . str_replace(['_', '-'], [':', '/'], $slug);
      }

      $netblockRows[] = [
        'name' => $label,
        'attempts' => $count,
      ];
    }

    usort(
      $netblockRows,
      /** @param array{name: string, attempts: int} $a @param array{name: string, attempts: int} $b */
      static function (array $a, array $b): int {
        return $b['attempts'] <=> $a['attempts'];
      }
    );

    $metrics = [
      'total_attempts' => (int) (Database::get($prefix . ':attempts:total') ?: 0),
      'avg_per_day' => self::averageSeries($daySeries),
      'avg_per_week' => self::averageSeries($weekSeries),
      'avg_per_month' => self::averageSeries($monthSeries),
      'avg_per_year' => self::averageSeries($yearSeries),
      'attempts_today' => (int) (Database::get($prefix . ':attempts:day:' . date('Y-m-d')) ?: 0),
      'attempts_this_week' => (int) (Database::get($prefix . ':attempts:week:' . date('o-\\WW')) ?: 0),
      'attempts_this_month' => (int) (Database::get($prefix . ':attempts:month:' . date('Y-m')) ?: 0),
      'attempts_this_year' => (int) (Database::get($prefix . ':attempts:year:' . date('Y')) ?: 0),
      'top_netblocks' => array_slice($netblockRows, 0, 10),
    ];

    return ['allowed' => true, 'reason' => 'ok', 'metrics' => $metrics];
  }

  /** @return array<int, int> */
  private static function readIntegerSeries(string $prefix): array
  {
    $series = [];
    foreach (Database::scanKeys($prefix . '*') as $key) {
      $series[] = (int) (Database::get($key) ?: 0);
    }

    return $series;
  }

  /** @param array<int, int> $series */
  private static function averageSeries(array $series): float
  {
    if ($series === []) {
      return 0.0;
    }

    return round(array_sum($series) / count($series), 2);
  }
}
