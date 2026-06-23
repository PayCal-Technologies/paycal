<?php declare(strict_types=1);

namespace PayCal\Observability\Sink;

use PayCal\Observability\DiagnosticEvent;
use PayCal\Observability\TraceGateDecision;

/**
 * NullSink — default production sink; O(1) no-op.
 *
 * TraceGate returns sink_none before Argus invokes sinks in most prod paths.
 * This sink exists so the registry always has a safe fallback.
 */
final class NullSink implements DiagnosticSinkInterface
{
  private static int $suppressedCount = 0;

  /**
   * Return the registry identifier for the no-op sink.
   */
  public function id(): string
  {
    return 'none';
  }

  /**
   * Count the suppressed event without writing it anywhere.
   */
  public function write(DiagnosticEvent $event, TraceGateDecision $decision): void
  {
    self::$suppressedCount++;
  }

  /**
   * Return the number of events suppressed by this sink.
   */
  public static function suppressedCount(): int
  {
    return self::$suppressedCount;
  }

  /**
   * Reset the suppressed event count for isolated tests.
   */
  public static function resetSuppressedCountForTests(): void
  {
    self::$suppressedCount = 0;
  }
}
