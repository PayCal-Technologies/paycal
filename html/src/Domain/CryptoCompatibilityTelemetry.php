<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\SystemConfig;

/**
 * Report-only counters for encrypted-data compatibility paths.
 */
final class CryptoCompatibilityTelemetry
{
  public const SOURCE_PERSONAL_CURRENT = 'personal_current';
  public const SOURCE_PERSONAL_LEGACY = 'personal_legacy';
  public const SOURCE_BUSINESS_CURRENT = 'business_current';

  /** @var array<string, true> */
  private const ALLOWED_EVENTS = [
    'wrapper_missing:personal_current' => true,
    'wrapper_missing:business_current' => true,
    'wrapper_present:personal_current' => true,
    'wrapper_present:personal_legacy' => true,
    'wrapper_present:business_current' => true,
    'legacy_wrapper_blocked:personal_legacy' => true,
    'unwrap_attempt:personal_current' => true,
    'unwrap_attempt:personal_legacy' => true,
    'unwrap_attempt:business_current' => true,
    'unwrap_failure:personal_current' => true,
    'unwrap_failure:personal_legacy' => true,
    'unwrap_failure:business_current' => true,
    'unwrap_success:personal_current' => true,
    'unwrap_success:personal_legacy' => true,
    'unwrap_success:business_current' => true,
    'plaintext_fallback:annual_summary' => true,
    'plaintext_fallback:daily_year' => true,
    'plaintext_fallback:export_csv' => true,
    'plaintext_fallback:gross_year' => true,
    'plaintext_fallback:health_insights_months' => true,
    'plaintext_fallback:render_earnings_year' => true,
    'plaintext_fallback:work_totals_range' => true,
  ];

  /**
   * Record that an expected encrypted-data wrapper was absent.
   */
  public static function wrapperMissing(string $source): void
  {
    self::increment('wrapper_missing', $source);
  }

  /**
   * Record that an encrypted-data wrapper was present for a source.
   */
  public static function wrapperPresent(string $source): void
  {
    self::increment('wrapper_present', $source);
  }

  /**
   * Record that legacy personal wrapper handling was intentionally blocked.
   */
  public static function legacyWrapperBlocked(): void
  {
    self::increment('legacy_wrapper_blocked', self::SOURCE_PERSONAL_LEGACY);
  }

  /**
   * Record an attempted encrypted-data unwrap for compatibility reporting.
   */
  public static function unwrapAttempt(string $source): void
  {
    self::increment('unwrap_attempt', $source);
  }

  /**
   * Record a failed encrypted-data unwrap for compatibility reporting.
   */
  public static function unwrapFailure(string $source): void
  {
    self::increment('unwrap_failure', $source);
  }

  /**
   * Record a successful encrypted-data unwrap for compatibility reporting.
   */
  public static function unwrapSuccess(string $source): void
  {
    self::increment('unwrap_success', $source);
  }

  /**
   * Record use of a plaintext compatibility fallback path.
   */
  public static function plaintextFallback(string $source): void
  {
    self::increment('plaintext_fallback', $source);
  }

  /**
   * Increment a whitelisted compatibility telemetry counter.
   */
  private static function increment(string $event, string $source): void
  {
    $counter = $event . ':' . $source;
    if (!isset(self::ALLOWED_EVENTS[$counter])) {
      return;
    }

    try {
      $schema = SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;
      Database::incr("telemetry:encryption:{$schema}:compat:{$counter}");
    } catch (\Throwable) {
    }
  }
}
