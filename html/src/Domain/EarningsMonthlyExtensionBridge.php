<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * EarningsMonthlyExtensionBridge.php
 *
 * Purpose: Bridge monthly earnings rendering from core HTML output into
 * optional private extension implementations.
 *
 * Developer notes:
 * - Keep core monthly rendering usable without extensions and preserve stable
 *   fallback behavior when extension hooks are absent.
 * - Extension returns should remain bounded to the monthly rendering contract.
 *
 * Architectural role:
 * - Reusable domain bridge that isolates monthly-extension loading from core
 *   earnings rendering flows.
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
 * Isolates core earnings monthly renderer from private extension implementation.
 */
final class EarningsMonthlyExtensionBridge
{
  /**
   * Render monthly HTML through private extension hooks.
   */
  public static function render(int $year, string $coreHtml): ?string
  {
    $overrideRendered = self::renderViaHooksClass(
      dirname(__DIR__, 2) . '/extensions/overrides/earnings-monthly/hooks.php',
      'PayCal\\Extensions\\Overrides\\EarningsMonthly\\Hooks',
      $year,
      $coreHtml
    );

    if (is_string($overrideRendered) && trim($overrideRendered) !== '') {
      return $overrideRendered;
    }

    return self::renderViaHooksClass(
      dirname(__DIR__, 2) . '/extensions/basic/earnings-monthly/hooks.php',
      'PayCal\\Extensions\\Basic\\EarningsMonthly\\Hooks',
      $year,
      $coreHtml
    );
  }

  /**
   * Load hooks class file when needed and invoke render.
   */
  private static function renderViaHooksClass(string $hooksFile, string $hooksClass, int $year, string $coreHtml): ?string
  {
    if (!class_exists($hooksClass) && is_file($hooksFile)) {
      require_once $hooksFile;
    }

    if (!class_exists($hooksClass)) {
      return null;
    }

    return $hooksClass::render([
      'year' => $year,
      'html' => $coreHtml,
      'mode' => 'private',
    ]);
  }
}
