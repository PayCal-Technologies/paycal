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
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
/**
 * Extension diagnostics attribute.
 *
 * Responsibilities:
 * - Mark diagnostics seams discoverable via reflection/runtime metadata.
 */
final class ExtensionDiagnostics
{
  /**
   * @param string $diagnosticType Type of diagnostic data accessed (e.g., 'manifests', 'listeners')
   */
  public function __construct(public readonly string $diagnosticType)
  {
  }
}

