<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * EarningsPieGraphsExtensionBridge.php
 *
 * Purpose: Bridge pie-graph rendering for earnings analytics into optional
 * extension-provided implementations.
 *
 * Developer notes:
 * - Pie-graph rendering is an extension concern and should not leak extension
 *   assumptions back into the core earnings domain.
 * - Keep hook dispatch semantics stable so callers can treat this bridge as an
 *   optional renderer.
 *
 * Architectural role:
 * - Reusable domain bridge that isolates pie-graph extension dispatch from
 *   core earnings workflows.
 * - Encapsulates optional extension wiring outside the HTTP layer.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @subpackage Extensions
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * Isolates core Earnings domain from private pie graphs extension implementation.
 */
final class EarningsPieGraphsExtensionBridge
{
  /**
   * @param array<string, mixed> $payload
   */
  public static function render(int $year, array $payload): ?string
  {
    $hookResults = ExtensionHookBridge::dispatch('earnings.piegraphs.render', [
      'year' => $year,
      'payload' => $payload,
      'mode' => 'private',
    ]);

    foreach ($hookResults as $candidateHtml) {
      if (is_string($candidateHtml) && trim($candidateHtml) !== '') {
        return $candidateHtml;
      }
    }

    return null;
  }
}
