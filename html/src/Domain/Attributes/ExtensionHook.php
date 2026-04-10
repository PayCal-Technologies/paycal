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

