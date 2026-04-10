<?php declare(strict_types=1);

namespace PayCal\Domain\Attributes;

/**
 * ExtensionBootstrap.php
 *
 * Purpose: Extension bootstrap metadata attribute for runtime-stage discovery
 * and deterministic extension lifecycle wiring.
 *
 * Developer notes:
 * - Bootstrap stage naming must remain stable for extension runtime reflection.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
/**
 * Extension bootstrap attribute.
 *
 * Responsibilities:
 * - Mark classes/methods participating in extension bootstrap stages.
 */
final class ExtensionBootstrap
{
  /**
   * @param string $stage Bootstrap stage (e.g., 'early', 'runtime', 'late')
   */
  public function __construct(public readonly string $stage = 'runtime')
  {
  }
}

