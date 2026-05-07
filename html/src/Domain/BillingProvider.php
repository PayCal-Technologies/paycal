<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Attributes\ExtensionCapability;

/**
 * BillingProvider.php
 *
 * Purpose: Resolve the active billing provider from extension capabilities so
 * commercial behavior can switch without hardcoding provider policy in callers.
 *
 * Developer notes:
 * - Provider resolution affects checkout, UI affordances, and subscription
 *   behavior across the app.
 * - Keep normalization deterministic and narrowly scoped to provider selection.
 *
 * Architectural role:
 * - Reusable domain resolver that exposes the currently active billing mode to
 *   callers that should not know extension capability internals.
 * - Encapsulates provider selection outside the HTTP layer.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

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
  public static function current(): string
  {
    $value = ExtensionCapabilityBridge::value('billing.provider', self::PUBLIC_TOGGLE);
    $normalized = is_scalar($value) ? strtolower(trim((string) $value)) : '';

    return $normalized === self::STRIPE ? self::STRIPE : self::PUBLIC_TOGGLE;
  }

  public static function isStripe(): bool
  {
    return self::current() === self::STRIPE;
  }
}
