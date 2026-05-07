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
 *
 * Architectural role:
 * - Reusable attribute metadata consumed by reflection-based capability
 *   discovery in extension-aware runtime paths.
 * - Encapsulates declarative capability tagging outside the HTTP layer.
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

