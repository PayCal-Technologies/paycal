<?php declare(strict_types=1);

namespace PayCal\Observability;

/**
 * Per-request diagnostic event budget — fail closed when exceeded.
 */
final class ArgusEventBudget
{
  private static int $emitted = 0;

  /**
   * Reset the per-request emission counter for isolated tests.
   */
  public static function resetForTests(): void
  {
    self::$emitted = 0;
  }

  /**
   * Return whether another diagnostic event may be emitted.
   */
  public static function allow(): bool
  {
    $limits = TraceGatePolicy::captureLimits();
    $max = ConfigValue::int($limits['max_events_per_request'] ?? null, 100);
    if ($max <= 0) {
      return true;
    }

    return self::$emitted < $max;
  }

  /**
   * Count a diagnostic event emission against the request budget.
   */
  public static function recordEmission(): void
  {
    self::$emitted++;
  }

  /**
   * Return the number of diagnostic events emitted in this request.
   */
  public static function emittedCount(): int
  {
    return self::$emitted;
  }
}
