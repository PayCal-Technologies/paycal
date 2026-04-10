<?php declare(strict_types=1);

namespace PayCal\Domain\Attributes;

/**
 * ExtensionCapability.php
 *
 * Purpose: Extension capability metadata attribute used to tag methods that
 * expose or consume extension-provided capabilities.
 *
 * Developer notes:
 * - Capability names are contract keys between core and extension runtime.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
/**
 * Extension capability attribute.
 *
 * Responsibilities:
 * - Declare a capability name for reflection/runtime capability discovery.
 */
final class ExtensionCapability
{
  /**
   * @param string $name Capability identifier (e.g. 'billing.provider').
   */
  public function __construct(
    public string $name
  ) {
  }
}

