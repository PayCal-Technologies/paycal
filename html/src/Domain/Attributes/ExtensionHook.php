<?php declare(strict_types=1);

namespace PayCal\Domain\Attributes;

/**
 * ExtensionHook.php
 *
 * Purpose: Extension hook metadata attribute for declaring named dispatch
 * points in the extension event bus.
 *
 * Developer notes:
 * - Hook names form integration contracts for extension listeners.
 *
 * Architectural role:
 * - Reusable attribute metadata consumed by hook discovery and extension-event
 *   dispatch registration.
 * - Encapsulates declarative hook tagging outside the HTTP layer.
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
/**
 * Extension hook attribute.
 *
 * Responsibilities:
 * - Bind method metadata to a named extension hook identifier.
 */
final class ExtensionHook
{
  /**
   * @param string $name Hook identifier (e.g. 'earnings.ytd.rendered').
   */
  public function __construct(
    public string $name
  ) {
  }
}

