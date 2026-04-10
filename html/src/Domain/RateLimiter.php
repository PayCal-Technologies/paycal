<?php declare(strict_types=1);

namespace PayCal\Domain;
use PayCal\Domain\Enums\FormTTL;
use PayCal\Domain\Config\SystemConfig;

/**
 * RateLimiter.php
 *
 * Redis-backed fixed-window rate limiting utilities.
 *
 * Why this exists:
 * - Provide shared anti-abuse controls for auth, API, and mutation routes.
 * - Keep per-endpoint limits centralized and easy to audit.
 * - Avoid controller-level duplication of counter and TTL behavior.
 */

/**
 * Static per-minute limiter helpers for user and IP dimensions.
 *
 * Internal guarantees:
 * - Keys are namespaced by endpoint + subject + minute bucket.
 * - First-hit TTL bounds memory growth for old windows.
 * - Return shape is uniform (`allowed`, `remaining`) across all checks.
 */
final class RateLimiter
{
  // Rate limit constants (requests per minute)
  private const LIMIT_LOGIN = 10;
  private const LIMIT_CALENDAR = 120;
  private const LIMIT_GENERAL = 300;
  private const LIMIT_TELEMETRY = 90;
  private const LIMIT_IP_CALENDAR = 240;
  private const LIMIT_IP_GENERAL = 600;
  private const WINDOW_TTL_SECONDS = 70;

  /**
   * Check if user has exceeded login rate limit.
   *
   * @return array{allowed: bool, remaining: int}
   */
  public static function checkLoginLimit(string $userUUID): array
  {
    return self::checkLimit($userUUID, 'login', self::LIMIT_LOGIN);
  }

  /**
   * Check if user has exceeded calendar mutation rate limit.
   *
   * @return array{allowed: bool, remaining: int}
   */
  public static function checkCalendarLimit(string $userUUID): array
  {
    return self::checkLimit($userUUID, 'calendar', self::LIMIT_CALENDAR);
  }

  /**
   * Check if user has exceeded general API rate limit.
   *
   * @return array{allowed: bool, remaining: int}
   */
  public static function checkGeneralLimit(string $userUUID): array
  {
    return self::checkLimit($userUUID, 'general', self::LIMIT_GENERAL);
  }

  /**
   * Check if user has exceeded telemetry event rate limit.
   *
   * @return array{allowed: bool, remaining: int}
   */
  public static function checkTelemetryLimit(string $userUUID): array
  {
    return self::checkLimit($userUUID, 'telemetry', self::LIMIT_TELEMETRY);
  }

  /**
   * Check per-route account recovery rate limits with explicit windows.
   *
   * @return array{allowed: bool, remaining: int, limit: int, window_seconds: int, reset_at: int}
   */
  public static function checkRecoveryEndpointLimit(string $route, string $clientIP, string $txnId = ''): array
  {
    $routeKey = strtolower(trim($route));

    /** @var array<string, array{config: string, fallback: int, window: int, withTxn: bool}> $policies */
    $policies = [
      'start' => ['config' => 'account_recovery_max_starts_per_day', 'fallback' => 5, 'window' => 86400, 'withTxn' => false],
      'resend' => ['config' => 'account_recovery_max_resends_per_hour', 'fallback' => 5, 'window' => 3600, 'withTxn' => true],
      'verify-email' => ['config' => 'account_recovery_max_verify_attempts', 'fallback' => 5, 'window' => 3600, 'withTxn' => true],
      'proof-payload' => ['config' => 'account_recovery_max_verify_attempts', 'fallback' => 5, 'window' => 3600, 'withTxn' => true],
      'prove-key' => ['config' => 'account_recovery_max_verify_attempts', 'fallback' => 5, 'window' => 3600, 'withTxn' => true],
      'bootstrap' => ['config' => 'account_recovery_max_verify_attempts', 'fallback' => 5, 'window' => 3600, 'withTxn' => true],
      'register-passkey-start' => ['config' => 'account_recovery_max_verify_attempts', 'fallback' => 5, 'window' => 3600, 'withTxn' => true],
      'register-passkey-finish' => ['config' => 'account_recovery_max_verify_attempts', 'fallback' => 5, 'window' => 3600, 'withTxn' => true],
      'complete' => ['config' => 'account_recovery_max_verify_attempts', 'fallback' => 5, 'window' => 3600, 'withTxn' => true],
      'cancel' => ['config' => 'account_recovery_max_verify_attempts', 'fallback' => 5, 'window' => 3600, 'withTxn' => true],
    ];

    $policy = $policies[$routeKey] ?? ['config' => 'account_recovery_max_verify_attempts', 'fallback' => 5, 'window' => 3600, 'withTxn' => false];

    $configuredLimit = SystemConfig::get($policy['config']);
    $limit = is_numeric($configuredLimit)
      ? max(1, (int) $configuredLimit)
      : $policy['fallback'];

    $subject = 'ip:' . hash('sha256', $clientIP);
    if ($policy['withTxn'] && $txnId !== '') {
      $subject .= ':txn:' . substr(hash('sha256', $txnId), 0, 16);
    }

    return self::checkWindowLimit(
      $subject,
      'recovery:' . $routeKey,
      $limit,
      $policy['window']
    );
  }

  /**
   * Check if client IP has exceeded calendar mutation limit.
   *
   * @return array{allowed: bool, remaining: int}
   */
  public static function checkIPCalendarLimit(string $clientIP): array
  {
    return self::checkLimit('ip:' . md5($clientIP), 'calendar', self::LIMIT_IP_CALENDAR);
  }

  /**
   * Check if client IP has exceeded general API limit.
   *
   * @return array{allowed: bool, remaining: int}
   */
  public static function checkIPGeneralLimit(string $clientIP): array
  {
    return self::checkLimit('ip:' . md5($clientIP), 'general', self::LIMIT_IP_GENERAL);
  }

  /**
   * Generic rate limit check.
   *
   * @param string $userUUID     The authenticated user UUID
   * @param string $endpoint     Endpoint category (login, calendar, general)
   * @param int    $limit        Maximum requests per minute
   *
   * @return array{allowed: bool, remaining: int}
   */
  private static function checkLimit(string $userUUID, string $endpoint, int $limit): array
  {
    // Calculate current minute window (e.g., 1609459200 for Unix epoch minute)
    $currentMinute = (int) floor(time() / FormTTL::ONE_MIN->value);

    // Build Redis key: ratelimit:endpoint:userUUID:minute
    $key = "ratelimit:{$endpoint}:{$userUUID}:{$currentMinute}";

    // Increment counter for this minute
    $count = Database::incr($key);

    // Set expiration to prevent stale keys (70 seconds = 1 minute buffer)
    if (1 === $count) {
      Database::expire($key, self::WINDOW_TTL_SECONDS);
    }

    // Calculate remaining requests
    $remaining = max(0, $limit - $count);

    return [
        'allowed' => $count <= $limit,
        'remaining' => $remaining,
    ];
  }

  /**
   * Generic rate limit check for arbitrary window sizes.
   *
   * @return array{allowed: bool, remaining: int, limit: int, window_seconds: int, reset_at: int}
   */
  private static function checkWindowLimit(string $subjectKey, string $endpoint, int $limit, int $windowSeconds): array
  {
    $safeWindow = max(1, $windowSeconds);
    $bucket = (int) floor(time() / $safeWindow);
    $key = "ratelimit:{$endpoint}:{$subjectKey}:{$bucket}";

    $count = Database::incr($key);
    if (1 === $count) {
      Database::expire($key, $safeWindow + 10);
    }

    $remaining = max(0, $limit - $count);

    return [
      'allowed' => $count <= $limit,
      'remaining' => $remaining,
      'limit' => $limit,
      'window_seconds' => $safeWindow,
      'reset_at' => ($bucket + 1) * $safeWindow,
    ];
  }

  /**
   * Clear rate limit counters (admin/testing use only).
   */
  public static function clearLimit(string $userUUID, string $endpoint): bool
  {
    $currentMinute = (int) floor(time() / FormTTL::ONE_MIN->value);
    $key = "ratelimit:{$endpoint}:{$userUUID}:{$currentMinute}";

    $deleted = Database::unlink($key);
    return $deleted > 0;
  }
}
