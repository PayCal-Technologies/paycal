<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Resilience;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Infrastructure\Telemetry\SecurityLog;

/**
 * RedisReliabilityService.php
 *
 * Purpose: Centralize Redis mutation-safety policy, breaker state, and alert
 * throttling for tier-0 reliability enforcement across write paths.
 *
 * Developer notes:
 * - This class encodes infrastructure guardrails that protect data integrity.
 * - Threshold semantics and freeze behavior should remain deterministic and
 *   operationally legible.
 *
 * Architectural role:
 * - Infrastructure reliability service for mutation gating and infrastructure
 *   guardrail decisions around Redis-backed persistence.
 * - Encapsulates reliability policy outside the HTTP layer.
 *
 * @category   Infrastructure
 * @package    PayCal\Infrastructure\Resilience
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * RedisReliabilityService
 *
 * Tier-0 Redis reliability controller for mutation safety.
 *
 * Why this exists:
 * - Provide a central breaker/freeze decision point used by write paths.
 * - Detect quota, churn, and memory pressure before user-facing data integrity degrades.
 * - Emit throttled reliability alerts without spamming operators.
 *
 * Internal guarantees:
 * - State is persisted in Redis with explicit defaults and normalization.
 * - Policy thresholds are centralized and type-safe on readback.
 * - Mutation gate decisions are deterministic for a given sampled state.
 */
final class RedisReliabilityService
{
  private const STATE_KEY = Keys::SYSTEM . ':redis:tier0:state';
  private const SAMPLE_KEY = Keys::SYSTEM . ':redis:tier0:sample';
  private const SAMPLE_TTL_SECONDS = 3600;
  private const ALERT_THROTTLE_PREFIX = Keys::SYSTEM . ':redis:tier0:alert:last:';
  private const ALERT_THROTTLE_SECONDS = 300;

  /** @return array<string, string> */
  private static function defaultState(): array
  {
    return [
      'mutation_freeze' => '0',
      'breaker_state' => 'closed',
      'breaker_opened_at' => '0',
      'breaker_reason' => '',
      'failure_count' => '0',
      'success_count' => '0',
      'updated_at' => (string) time(),
    ];
  }

  /**
   * @return array{
   *   breaker_failure_threshold: int,
   *   breaker_open_seconds: int,
   *   tier0_key_count_quotas: array<string, int>,
   *   monitor_patterns: array<string, string>,
   *   tier0_namespaces: array<int, string>,
   *   reserved_memory_percent: int,
   *   alert_thresholds: array{
   *     quota_warning_percent: float,
   *     quota_elevated_percent: float,
   *     quota_critical_percent: float,
   *     memory_warning_percent: float,
   *     memory_critical_percent: float,
   *     tier0_churn_warning_per_minute: float,
   *     tier0_churn_critical_per_minute: float,
   *     eviction_critical_per_minute: float
   *   }
   * }
   */
  private static function policy(): array
  {
    return [
      'breaker_failure_threshold' => 5,
      'breaker_open_seconds' => 30,
      'tier0_key_count_quotas' => [
        'session' => 200000,
        'user' => 300000,
        'user:kek' => 300000,
        'ratelimit' => 800000,
      ],
      'monitor_patterns' => [
        'session' => 'session:*',
        'user' => 'user:*',
        'user:kek' => 'user:kek:*',
        'ratelimit' => 'ratelimit:*',
        'work' => 'work:*',
        'site' => 'site:*',
        'telemetry' => 'telemetry:*',
        'cache' => 'cache:*',
      ],
      'tier0_namespaces' => ['session', 'user', 'user:kek', 'ratelimit'],
      'reserved_memory_percent' => 20,
      'alert_thresholds' => [
        'quota_warning_percent' => 80.0,
        'quota_elevated_percent' => 90.0,
        'quota_critical_percent' => 95.0,
        'memory_warning_percent' => 80.0,
        'memory_critical_percent' => 90.0,
        'tier0_churn_warning_per_minute' => 500.0,
        'tier0_churn_critical_per_minute' => 1000.0,
        'eviction_critical_per_minute' => 0.0,
      ],
    ];
  }

  /**
   * Handles asInt operation.
   */
  private static function asInt(mixed $value, int $default = 0): int
  {
    if (is_int($value)) {
      return $value;
    }

    if (is_float($value)) {
      return (int) $value;
    }

    if (is_string($value) && is_numeric($value)) {
      return (int) $value;
    }

    return $default;
  }

  /**
   * Handles asFloat operation.
   */
  private static function asFloat(mixed $value, float $default = 0.0): float
  {
    if (is_int($value) || is_float($value)) {
      return (float) $value;
    }

    if (is_string($value) && is_numeric($value)) {
      return (float) $value;
    }

    return $default;
  }

  /**
   * Handles asString operation.
   */
  private static function asString(mixed $value, string $default = ''): string
  {
    if (is_scalar($value)) {
      return (string) $value;
    }

    return $default;
  }

  /**
  * @param array{
  *   quota_warning_percent: float,
  *   quota_elevated_percent: float,
  *   quota_critical_percent: float,
  *   memory_warning_percent: float,
  *   memory_critical_percent: float,
  *   tier0_churn_warning_per_minute: float,
  *   tier0_churn_critical_per_minute: float,
  *   eviction_critical_per_minute: float
  * } $thresholds
  * @param array<string, array{current: int, quota: int, percent: float}> $quotaStatus
   * @param array<string, float> $churnPerMinute
   * @param array<int, string> $tier0Namespaces
  * @return array<int, array{id: string, code: string, severity: string, namespace?: string, value: float, threshold: float, message: string}>
   */
  public static function evaluateAlerts(
    array $thresholds,
    array $quotaStatus,
    array $churnPerMinute,
    array $tier0Namespaces,
    float $memoryPercent,
    float $evictionRatePerMinute
  ): array {
    $alerts = [];

    foreach ($quotaStatus as $namespace => $row) {
      $percent = $row['percent'];
      $severity = 'warning';
      $threshold = 0.0;
      $triggered = false;

      if ($percent >= $thresholds['quota_critical_percent']) {
        $severity = 'critical';
        $threshold = $thresholds['quota_critical_percent'];
        $triggered = true;
      } elseif ($percent >= $thresholds['quota_elevated_percent']) {
        $severity = 'elevated';
        $threshold = $thresholds['quota_elevated_percent'];
        $triggered = true;
      } elseif ($percent >= $thresholds['quota_warning_percent']) {
        $severity = 'warning';
        $threshold = $thresholds['quota_warning_percent'];
        $triggered = true;
      }

      if (!$triggered) {
        continue;
      }

      $alerts[] = [
        'id' => 'quota:' . (string) $namespace,
        'code' => 'QUOTA_PRESSURE',
        'severity' => $severity,
        'namespace' => (string) $namespace,
        'value' => round($percent, 2),
        'threshold' => $threshold,
        'message' => 'Namespace quota pressure: ' . (string) $namespace . ' at ' . round($percent, 2) . '%.',
      ];
    }

    if ($memoryPercent >= $thresholds['memory_critical_percent']) {
      $alerts[] = [
        'id' => 'memory:critical',
        'code' => 'MEMORY_PRESSURE',
        'severity' => 'critical',
        'value' => round($memoryPercent, 2),
        'threshold' => $thresholds['memory_critical_percent'],
        'message' => 'Redis memory pressure is critical at ' . round($memoryPercent, 2) . '%.',
      ];
    } elseif ($memoryPercent >= $thresholds['memory_warning_percent']) {
      $alerts[] = [
        'id' => 'memory:warning',
        'code' => 'MEMORY_PRESSURE',
        'severity' => 'warning',
        'value' => round($memoryPercent, 2),
        'threshold' => $thresholds['memory_warning_percent'],
        'message' => 'Redis memory pressure warning at ' . round($memoryPercent, 2) . '%.',
      ];
    }

    if ($evictionRatePerMinute > $thresholds['eviction_critical_per_minute']) {
      $alerts[] = [
        'id' => 'eviction:critical',
        'code' => 'EVICTION_DETECTED',
        'severity' => 'critical',
        'value' => round($evictionRatePerMinute, 2),
        'threshold' => $thresholds['eviction_critical_per_minute'],
        'message' => 'Redis evictions detected at ' . round($evictionRatePerMinute, 2) . ' keys/min.',
      ];
    }

    foreach ($tier0Namespaces as $namespace) {
      $name = (string) $namespace;
      if ($name === '') {
        continue;
      }

      $churn = $churnPerMinute[$name] ?? 0.0;
      $severity = 'warning';
      $threshold = 0.0;
      $triggered = false;

      if ($churn >= $thresholds['tier0_churn_critical_per_minute']) {
        $severity = 'critical';
        $threshold = $thresholds['tier0_churn_critical_per_minute'];
        $triggered = true;
      } elseif ($churn >= $thresholds['tier0_churn_warning_per_minute']) {
        $severity = 'warning';
        $threshold = $thresholds['tier0_churn_warning_per_minute'];
        $triggered = true;
      }

      if (!$triggered) {
        continue;
      }

      $alerts[] = [
        'id' => 'churn:' . $name,
        'code' => 'TIER0_CHURN_SPIKE',
        'severity' => $severity,
        'namespace' => $name,
        'value' => round($churn, 2),
        'threshold' => $threshold,
        'message' => 'Tier-0 churn spike: ' . $name . ' at ' . round($churn, 2) . ' changes/min.',
      ];
    }

    return $alerts;
  }

  /**
  * @param array<int, array{id: string, code: string, severity: string, namespace?: string, value: float, threshold: float, message: string}> $alerts
   */
  private static function emitAlertHooks(array $alerts): void
  {
    foreach ($alerts as $alert) {
      $id = $alert['id'];
      $severity = $alert['severity'];
      $message = $alert['message'];
      $code = $alert['code'];

      $throttleKey = self::ALERT_THROTTLE_PREFIX . $severity . ':' . $id;
      $alreadySent = Database::get($throttleKey) !== '';
      if ($alreadySent) {
        continue;
      }

      SecurityLog::log('redis_tier0_alert', [
        'code' => $code,
        'severity' => $severity,
        'message' => $message,
          'namespace' => self::asString($alert['namespace'] ?? null),
          'value' => self::asString($alert['value']),
          'threshold' => self::asString($alert['threshold']),
      ]);

      Database::set($throttleKey, (string) time(), self::ALERT_THROTTLE_SECONDS);
    }
  }

  /**
   * @return array<string, string>
   */
  public static function getState(): array
  {
    $state = Database::hgetall(self::STATE_KEY);
    if ($state === []) {
      $defaults = self::defaultState();
      /** @var array<string, string> $defaultsString */
      $defaultsString = array_map(static fn ($v) => (string) $v, $defaults);
      Database::hset(self::STATE_KEY, $defaultsString);
      return $defaultsString;
    }

    $defaults = self::defaultState();
    foreach ($defaults as $field => $value) {
      if (!array_key_exists($field, $state)) {
        $state[$field] = (string) $value;
      }
    }

    return $state;
  }

  /** @param array<string, string> $state */
  private static function saveState(array $state): void
  {
    $state['updated_at'] = (string) time();
    Database::hset(self::STATE_KEY, array_map(static fn ($v) => (string) $v, $state));
  }

  /**
   * Handles setMutationFreeze operation.
   */
  public static function setMutationFreeze(bool $enabled, string $reason = ''): void
  {
    $state = self::getState();
    $state['mutation_freeze'] = $enabled ? '1' : '0';
    if ($reason !== '') {
      $state['breaker_reason'] = $reason;
    }
    self::saveState($state);
  }

  /**
   * Handles openCircuitBreaker operation.
   */
  public static function openCircuitBreaker(string $reason): void
  {
    $state = self::getState();
    $state['breaker_state'] = 'open';
    $state['breaker_opened_at'] = (string) time();
    $state['breaker_reason'] = $reason;
    self::saveState($state);
  }

  /**
   * Handles resetCircuitBreaker operation.
   */
  public static function resetCircuitBreaker(): void
  {
    $state = self::getState();
    $state['breaker_state'] = 'closed';
    $state['breaker_opened_at'] = '0';
    $state['breaker_reason'] = '';
    $state['failure_count'] = '0';
    self::saveState($state);
  }

  /**
   * Handles recordMutationFailure operation.
   */
  public static function recordMutationFailure(string $reason): void
  {
    $policy = self::policy();
    $state = self::getState();

    $failures = (int) ($state['failure_count'] ?? '0') + 1;
    $state['failure_count'] = (string) $failures;

    if ($failures >= (int) $policy['breaker_failure_threshold']) {
      $state['breaker_state'] = 'open';
      $state['breaker_opened_at'] = (string) time();
      $state['breaker_reason'] = $reason;
    }

    self::saveState($state);
  }

  /**
   * Handles recordMutationSuccess operation.
   */
  public static function recordMutationSuccess(): void
  {
    $state = self::getState();
    $state['success_count'] = (string) ((int) ($state['success_count'] ?? '0') + 1);

    if (($state['breaker_state'] ?? 'closed') === 'half-open') {
      $state['breaker_state'] = 'closed';
      $state['breaker_opened_at'] = '0';
      $state['breaker_reason'] = '';
      $state['failure_count'] = '0';
    }

    self::saveState($state);
  }

  /**
   * @return array{allowed: bool, code: string, message: string, breaker_state: string, freeze: bool}
   */
  public static function allowMutations(): array
  {
    $policy = self::policy();
    $state = self::getState();

    $freeze = ($state['mutation_freeze'] ?? '0') === '1';
    if ($freeze) {
      return [
        'allowed' => false,
        'code' => 'MUTATION_FREEZE',
        'message' => 'Mutations are temporarily frozen by administrator policy.',
        'breaker_state' => (string) ($state['breaker_state'] ?? 'closed'),
        'freeze' => true,
      ];
    }

    $breaker = (string) ($state['breaker_state'] ?? 'closed');
    if ($breaker === 'open') {
      $openedAt = (int) ($state['breaker_opened_at'] ?? '0');
      $elapsed = time() - $openedAt;
      if ($elapsed >= (int) $policy['breaker_open_seconds']) {
        $state['breaker_state'] = 'half-open';
        self::saveState($state);
        $breaker = 'half-open';
      } else {
        return [
          'allowed' => false,
          'code' => 'REDIS_BREAKER_OPEN',
          'message' => 'Redis write reliability guard is open. Retry shortly.',
          'breaker_state' => 'open',
          'freeze' => false,
        ];
      }
    }

    return [
      'allowed' => true,
      'code' => 'OK',
      'message' => 'Mutation allowed.',
      'breaker_state' => $breaker,
      'freeze' => false,
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function getSnapshot(): array
  {
    $state = self::getState();
    $policy = self::policy();
    $info = Database::info();

    $usedMemory = self::asInt($info['used_memory'] ?? null);
    $maxMemory = self::asInt($info['maxmemory'] ?? null);
    $evictedKeys = self::asInt($info['evicted_keys'] ?? null);

    $namespaceCounts = [];
    foreach ($policy['monitor_patterns'] as $namespace => $pattern) {
      $namespaceCounts[(string) $namespace] = count(Database::scanKeys((string) $pattern));
    }

    $sampleRaw = Database::get(self::SAMPLE_KEY);
    $sample = $sampleRaw !== '' ? json_decode($sampleRaw, true) : null;
    $previousTs = is_array($sample) ? self::asInt($sample['ts'] ?? null) : 0;
    $previousCounts = is_array($sample) && is_array($sample['counts'] ?? null)
      ? $sample['counts']
      : [];
    $previousEvicted = is_array($sample) ? self::asInt($sample['evicted_keys'] ?? null) : 0;

    $now = time();
    $minutes = max(1.0 / 60.0, ($now - $previousTs) / 60.0);
    $churnPerMinute = [];
    foreach ($namespaceCounts as $name => $count) {
      $prev = self::asInt($previousCounts[$name] ?? null, $count);
      $churnPerMinute[$name] = round(abs($count - $prev) / $minutes, 2);
    }

    $evictionRatePerMinute = round(max(0, $evictedKeys - $previousEvicted) / $minutes, 2);

    Database::set(
      self::SAMPLE_KEY,
      (string) json_encode([
        'ts' => $now,
        'counts' => $namespaceCounts,
        'evicted_keys' => $evictedKeys,
      ]),
      self::SAMPLE_TTL_SECONDS
    );

    $quotaStatus = [];
    foreach ($policy['tier0_key_count_quotas'] as $namespace => $quota) {
      $current = self::asInt($namespaceCounts[$namespace] ?? null);
      $pct = $quota > 0 ? round(($current / $quota) * 100, 2) : 0.0;
      $quotaStatus[$namespace] = [
        'current' => $current,
        'quota' => (int) $quota,
        'percent' => $pct,
      ];
    }

    $alerts = self::evaluateAlerts(
      $policy['alert_thresholds'],
      $quotaStatus,
      $churnPerMinute,
      $policy['tier0_namespaces'],
      $maxMemory > 0 ? round(($usedMemory / $maxMemory) * 100, 2) : 0.0,
      $evictionRatePerMinute
    );
    self::emitAlertHooks($alerts);

    return [
      'timestamp' => date('c', $now),
      'state' => [
        'mutation_freeze' => ($state['mutation_freeze'] ?? '0') === '1',
        'breaker_state' => (string) ($state['breaker_state'] ?? 'closed'),
        'breaker_opened_at' => self::asInt($state['breaker_opened_at'] ?? null),
        'breaker_reason' => self::asString($state['breaker_reason'] ?? null),
        'failure_count' => self::asInt($state['failure_count'] ?? null),
        'success_count' => self::asInt($state['success_count'] ?? null),
      ],
      'policy' => $policy,
      'redis' => [
        'used_memory_mb' => round($usedMemory / 1048576, 2),
        'max_memory_mb' => round($maxMemory / 1048576, 2),
        'memory_percent' => $maxMemory > 0 ? round(($usedMemory / $maxMemory) * 100, 2) : 0.0,
        'evicted_keys' => $evictedKeys,
        'evicted_delta' => max(0, $evictedKeys - $previousEvicted),
        'eviction_rate_per_minute' => $evictionRatePerMinute,
        'connected_clients' => self::asInt($info['connected_clients'] ?? null),
        'instantaneous_ops_per_sec' => self::asInt($info['instantaneous_ops_per_sec'] ?? null),
        'used_cpu_sys' => self::asFloat($info['used_cpu_sys'] ?? null),
        'used_cpu_user' => self::asFloat($info['used_cpu_user'] ?? null),
      ],
      'tier0_quotas' => $quotaStatus,
      'namespace_counts' => $namespaceCounts,
      'churn_per_minute' => $churnPerMinute,
      'alerts' => $alerts,
    ];
  }
}
