<?php declare(strict_types=1);

namespace PayCal\Domain\Attributes;

/**
 * ExtensionDiagnostics.php
 *
 * Purpose: Extension diagnostics metadata attribute for methods exposing
 * extension-runtime diagnostic surfaces.
 *
 * Developer notes:
 * - Diagnostic labels should remain stable for admin/debug tooling.
 *
 * Architectural role:
 * - Reusable attribute metadata consumed by reflection-driven extension
 *   diagnostics discovery.
 * - Encapsulates declarative diagnostics tagging outside the HTTP layer.
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
final class ExtensionDiagnostics
{
  /**
   * @param string $diagnosticType Type of diagnostic data accessed (e.g., 'manifests', 'listeners')
   */
  public function __construct(public readonly string $diagnosticType)
  {
  }
}

