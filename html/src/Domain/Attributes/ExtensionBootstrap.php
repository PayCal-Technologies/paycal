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
 *
 * Architectural role:
 * - Reusable attribute metadata consumed by extension bootstrap discovery and
 *   lifecycle orchestration.
 * - Encapsulates declarative bootstrap staging outside the HTTP layer.
 *
 * @category   Attributes
 * @package    PayCal\Domain\Attributes
 * @subpackage Metadata
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class ExtensionBootstrap
{
  /**
   * @param string $stage Bootstrap stage (e.g., 'early', 'runtime', 'late')
   */
  public function __construct(public readonly string $stage = 'runtime')
  {
  }
}

