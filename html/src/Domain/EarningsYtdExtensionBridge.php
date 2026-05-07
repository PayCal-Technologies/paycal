<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Attributes\ExtensionHook;

/**
 * EarningsYtdExtensionBridge.php
 *
 * Purpose: Bridge year-to-date earnings rendering from core payloads into
 * optional extension paths and hook-based implementations.
 *
 * Developer notes:
 * - YTD rendering must preserve core payload expectations whether extensions
 *   are loaded from override, basic, or hook-driven paths.
 * - Keep extension-mode handling explicit so fallback behavior stays legible.
 *
 * Architectural role:
 * - Reusable domain bridge that isolates YTD extension dispatch and file-path
 *   lookup from core earnings flows.
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
 * Isolates core Earnings domain from concrete extension paths/classes.
 */
final class EarningsYtdExtensionBridge
{
  /**
   * @param array<string, string> $payload
   */
  public static function renderWithMode(int $year, array $payload, string $mode): ?string
  {
    if ($mode === 'basic') {
      return self::renderViaHooksClass(
        dirname(__DIR__, 2) . '/extensions/basic/earnings-ytd/hooks.php',
        'PayCal\\Extensions\\Basic\\EarningsYtd\\Hooks',
        $year,
        $payload,
        'basic'
      );
    }

    if ($mode === 'override') {
      return self::renderViaHooksClass(
        dirname(__DIR__, 2) . '/extensions/overrides/earnings-ytd/hooks.php',
        'PayCal\\Extensions\\Overrides\\EarningsYtd\\Hooks',
        $year,
        $payload,
        'override'
      );
    }

    return null;
  }

  /**
   * @param array<string, string> $payload
   */
  #[ExtensionHook('earnings.ytd.render')]
  public static function renderFromHookBusAuto(int $year, array $payload): ?string
  {
    /** @var array<int, mixed> $hookResults */
    $hookResults = ExtensionHookBridge::dispatch('earnings.ytd.render', [
      'year' => $year,
      'payload' => $payload,
      'mode' => 'auto',
    ]);

    foreach ($hookResults as $candidateHtml) {
      if (is_string($candidateHtml) && trim($candidateHtml) !== '') {
        return $candidateHtml;
      }
    }

    return null;
  }

  /**
   * @param array<string, string> $payload
   */
  private static function renderViaHooksClass(string $hooksFile, string $hooksClass, int $year, array $payload, string $mode): ?string
  {
    if (!class_exists($hooksClass) && is_file($hooksFile)) {
      require_once $hooksFile;
    }

    if (!class_exists($hooksClass)) {
      return null;
    }

    return $hooksClass::render([
      'year' => $year,
      'payload' => $payload,
      'mode' => $mode,
    ]);
  }
}

