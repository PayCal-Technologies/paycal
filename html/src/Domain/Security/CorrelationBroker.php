<?php declare(strict_types=1);

namespace PayCal\Domain\Security;

/**
 * Class CorrelationBroker
 *
 * Evaluates and composes metadata correlation decisions by checking the
 * current context against MetadataCorrelationPolicy.  Provides both a raw
 * decision (evaluate) and a structured merged payload (compose) for use by
 * controller and domain code that must join site/financial data.
 */
final class CorrelationBroker
{
  /**
   * Evaluates whether all requested correlation pairs are permitted in the given context.
   *
   * @return CorrelationDecision Allowed if all pairs pass policy; denied with denied-pair list otherwise.
   */
  public static function evaluate(CorrelationContext $context): CorrelationDecision
  {
    $deniedPairs = [];

    foreach ($context->correlationPairsRequested() as $pair) {
      [$leftClass, $rightClass] = explode(':', $pair, 2);
      if (!MetadataCorrelationPolicy::allows($context->contextName(), $leftClass, $rightClass)) {
        $deniedPairs[] = $pair;
      }
    }

    if ($deniedPairs !== []) {
      return new CorrelationDecision(false, 'metadata_correlation_denied', $deniedPairs);
    }

    return new CorrelationDecision(true, 'correlation_allowed');
  }

  /**
   * @param array<string, mixed> $leftPayload
   * @param array<string, mixed> $rightPayload
   *
   * @return array<string, mixed>
   */
  public static function compose(
    array $leftPayload,
    array $rightPayload,
    string $leftClass,
    string $rightClass,
    CorrelationContext $context
  ): array {
    $pair = strtolower(trim($leftClass)) . ':' . strtolower(trim($rightClass));

    $evaluationContext = new CorrelationContext(
      $context->contextName(),
      $context->userUUID(),
      $context->privilegeLevel(),
      $context->purposeCode(),
      [$pair],
      $context->auditReasonCode()
    );

    $decision = self::evaluate($evaluationContext);
    if (!$decision->allowed()) {
      return [
        'status' => 'denied',
        'decision' => $decision->toArray(),
      ];
    }

    return [
      'status' => 'success',
      'decision' => $decision->toArray(),
      'data' => [
        'left' => $leftPayload,
        'right' => $rightPayload,
      ],
    ];
  }
}
