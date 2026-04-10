<?php declare(strict_types=1);

namespace PayCal\Domain\Security;

/**
 * Class CorrelationContext
 *
 * Immutable value object describing the runtime context for a metadata
 * correlation request: who is asking, why, and which data-class pairs are
 * being joined.  Passed to CorrelationBroker and MetadataCorrelationPolicy
 * to produce a CorrelationDecision.
 */
final readonly class CorrelationContext
{
  /** @var array<string, true> */
  private const PRIVILEGED_CONTEXT_SET = [
    'security-incident' => true,
    'fraud-investigation' => true,
    'regulatory-legal-hold' => true,
  ];

  /** @var array<int, string> */
  private array $correlationPairsRequested;

  /**
   * @param array<int, string> $correlationPairsRequested
   */
  public function __construct(
    private string $contextName,
    private string $userUUID,
    private string $privilegeLevel,
    private string $purposeCode,
    array $correlationPairsRequested,
    private string $auditReasonCode
  ) {
    $this->correlationPairsRequested = self::normalizePairs($correlationPairsRequested);
  }

  /** Returns the correlation-policy context name. */
  public function contextName(): string
  {
    return $this->contextName;
  }

  /** Returns the UUID of the user initiating the correlation. */
  public function userUUID(): string
  {
    return $this->userUUID;
  }

  /** Returns the caller's privilege level (e.g. 'user', 'security-admin'). */
  public function privilegeLevel(): string
  {
    return $this->privilegeLevel;
  }

  /** Returns the purpose code identifying the business reason for the correlation. */
  public function purposeCode(): string
  {
    return $this->purposeCode;
  }

  /** @return array<int, string> */
  public function correlationPairsRequested(): array
  {
    return $this->correlationPairsRequested;
  }

  /** Returns the audit reason code associated with this correlation request. */
  public function auditReasonCode(): string
  {
    return $this->auditReasonCode;
  }

  /** Indicates whether this context has elevated investigative privileges. */
  public function isPrivilegedContext(): bool
  {
    return isset(self::PRIVILEGED_CONTEXT_SET[$this->contextName]);
  }

  /**
   * @param array<int, string> $pairs
   *
   * @return array<int, string>
   */
  private static function normalizePairs(array $pairs): array
  {
    $normalized = [];

    foreach ($pairs as $pair) {
      $value = strtolower(trim((string) $pair));
      if ($value === '' || !str_contains($value, ':')) {
        continue;
      }

      [$left, $right] = explode(':', $value, 2);
      $left = trim($left);
      $right = trim($right);
      if ($left === '' || $right === '') {
        continue;
      }

      $normalized[] = $left . ':' . $right;
    }

    return array_values(array_unique($normalized));
  }
}
