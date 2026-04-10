<?php declare(strict_types=1);

namespace PayCal\Domain\Security;

/**
 * Server-side metadata correlation policy.
 *
 * Denies correlation by default and only allows explicit, reviewed contexts.
 */
final class MetadataCorrelationPolicy
{
  /** @var array<string, array<int, string>> */
  private const ALLOWED_CONTEXTS = [
    'self-service-earnings' => ['site_metadata:financial_payload'],
    'self-service-calendar' => ['site_metadata:financial_payload'],
    'security-incident' => [
      'site_metadata:financial_payload',
      'user_profile:session_metadata',
      'user_profile:credential_metadata',
    ],
    'fraud-investigation' => [
      'site_metadata:financial_payload',
      'user_profile:session_metadata',
      'user_profile:credential_metadata',
    ],
    'regulatory-legal-hold' => [
      'site_metadata:financial_payload',
      'user_profile:session_metadata',
      'user_profile:credential_metadata',
    ],
  ];

  /**
   * Checks whether a metadata class-pair correlation is allowed for a context.
   */
  public static function allows(string $context, string $leftClass, string $rightClass): bool
  {
    $normalizedContext = strtolower(trim($context));
    if ($normalizedContext === '') {
      return false;
    }

    $normalizedLeft = strtolower(trim($leftClass));
    $normalizedRight = strtolower(trim($rightClass));

    $pair = $normalizedLeft . ':' . $normalizedRight;
    $reverse = $normalizedRight . ':' . $normalizedLeft;

    $allowed = self::ALLOWED_CONTEXTS[$normalizedContext] ?? [];
    return in_array($pair, $allowed, true) || in_array($reverse, $allowed, true);
  }
}
