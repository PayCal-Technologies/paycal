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
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
/**
 * Billing provider mode attribute.
 *
 * Responsibilities:
 * - Declare allowed billing providers for a method-level route/action.
 */
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
