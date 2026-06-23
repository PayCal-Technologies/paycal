<?php declare(strict_types=1);

namespace PayCal\Observability;

/**
 * Policy outcome for a single diagnostic event evaluation.
 */
final class TraceGateDecision
{
  /**
   * Create a trace-gate decision.
   */
  public function __construct(
    public readonly bool $allowed,
    public readonly string $sink,
    public readonly string $reason,
    public readonly string $securityLogEvent = '',
  ) {
  }

  /**
   * Create a denied decision with the no-op sink.
   */
  public static function deny(string $reason): self
  {
    return new self(false, 'none', $reason);
  }
}
