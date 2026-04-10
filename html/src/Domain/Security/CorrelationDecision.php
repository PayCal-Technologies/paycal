<?php declare(strict_types=1);

namespace PayCal\Domain\Security;

/**
 * Class CorrelationDecision
 *
 * Immutable policy-evaluation result returned by CorrelationBroker.
 * Carries allow/deny state, reason code, and any denied correlation pairs.
 */
final readonly class CorrelationDecision
{
  /** @var array<int, string> */
  private array $deniedPairs;

  /**
   * @param array<int, string> $deniedPairs
   */
  public function __construct(
    private bool $allowed,
    private string $reasonCode,
    array $deniedPairs = []
  ) {
    $this->deniedPairs = array_values(array_unique(array_map(static fn (mixed $pair): string => strtolower(trim((string) $pair)), $deniedPairs)));
  }

  /** Returns true when the requested correlation is permitted. */
  public function allowed(): bool
  {
    return $this->allowed;
  }

  /** Returns a machine-readable reason code for the decision. */
  public function reasonCode(): string
  {
    return $this->reasonCode;
  }

  /** @return array<int, string> */
  public function deniedPairs(): array
  {
    return $this->deniedPairs;
  }

  /** @return array<string, mixed> */
  public function toArray(): array
  {
    return [
      'allowed' => $this->allowed,
      'reason' => $this->reasonCode,
      'denied_pairs' => $this->deniedPairs,
    ];
  }
}
