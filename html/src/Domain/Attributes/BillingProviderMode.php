<?php declare(strict_types=1);

namespace PayCal\Domain\Attributes;

/**
 * BillingProviderMode.php
 *
 * Purpose: Billing-provider gating metadata attribute for controller methods
 * that should only run under selected provider modes.
 *
 * Developer notes:
 * - Provider labels are compatibility-sensitive and must match runtime
 *   provider resolution values.
 *
 * Architectural role:
 * - Reusable attribute metadata consumed by controller dispatch and policy
 *   checks for provider-specific routes.
 * - Encapsulates declarative provider-mode constraints outside the HTTP layer.
 *
 * @category   Attributes
 * @package    PayCal\Domain\Attributes
 * @subpackage Metadata
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class BillingProviderMode
{
  /**
   * @param array<int, string> $providers
   */
  public function __construct(
    public array $providers
  ) {
  }
}
