<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Attributes\ExtensionCapability;

/**
 * Class BillingProvider
 *
 * Resolves and normalises the active billing provider at runtime.
 * Defers to the extension capability bus so the provider can be changed via
 * a configuration extension without modifying core code.
 */
final class BillingProvider
{
  public const PUBLIC_TOGGLE = 'public-toggle';
  public const STRIPE = 'stripe';

  #[ExtensionCapability('billing.provider')]
  /**
   * Handles current operation.
   */
  public static function current(): string
  {
    $value = ExtensionCapabilityBridge::value('billing.provider', self::PUBLIC_TOGGLE);
    $normalized = is_scalar($value) ? strtolower(trim((string) $value)) : '';

    return $normalized === self::STRIPE ? self::STRIPE : self::PUBLIC_TOGGLE;
  }

  /**
   * Handles isStripe operation.
   */
  public static function isStripe(): bool
  {
    return self::current() === self::STRIPE;
  }
}
