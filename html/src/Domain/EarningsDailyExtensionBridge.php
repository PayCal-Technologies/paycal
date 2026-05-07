<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * EarningsDailyExtensionBridge.php
 *
 * Purpose: Bridge core daily earnings payload rendering into optional private
 * extension implementations while preserving a stable core contract.
 *
 * Developer notes:
 * - Keep extension dispatch behavior deterministic so core earnings pages do
 *   not depend on extension-specific internals.
 * - Normalization should protect the core payload contract when extensions are
 *   present or absent.
 *
 * Architectural role:
 * - Reusable domain bridge that isolates extension hook loading from core
 *   daily earnings flows.
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
 * Isolates core earnings daily payload from private extension implementation.
 */
final class EarningsDailyExtensionBridge
{
  /**
   * @param array<string, array<string, string>> $payload
   * @return array<string, array<string, string>>|null
   */
  public static function render(int $year, array $payload): ?array
  {
    $overrideRendered = self::renderViaHooksClass(
      dirname(__DIR__, 2) . '/extensions/overrides/earnings-daily/hooks.php',
      'PayCal\\Extensions\\Overrides\\EarningsDaily\\Hooks',
      $year,
      $payload
    );

    if (is_array($overrideRendered)) {
      return self::normalizePayload($overrideRendered);
    }

    $basicRendered = self::renderViaHooksClass(
      dirname(__DIR__, 2) . '/extensions/basic/earnings-daily/hooks.php',
      'PayCal\\Extensions\\Basic\\EarningsDaily\\Hooks',
      $year,
      $payload
    );

    return is_array($basicRendered) ? self::normalizePayload($basicRendered) : null;
  }

  /**
   * @param array<string, array<string, string>> $payload
   * @return array<string, array<string, string>>|null
   */
  private static function renderViaHooksClass(string $hooksFile, string $hooksClass, int $year, array $payload): ?array
  {
    if (!class_exists($hooksClass) && is_file($hooksFile)) {
      require_once $hooksFile;
    }

    if (!class_exists($hooksClass)) {
      return null;
    }

    $result = $hooksClass::render([
      'year' => $year,
      'payload' => $payload,
      'mode' => 'private',
    ]);

    return is_array($result) ? self::normalizePayload($result) : null;
  }

  /**
   * @param array<mixed, mixed> $payload
   * @return array<string, array<string, string>>
   */
  private static function normalizePayload(array $payload): array
  {
    $normalized = [];
    foreach ($payload as $date => $row) {
      if (!is_array($row)) {
        continue;
      }

      $dateKey = (string) $date;
      $normalizedRow = [];
      foreach ($row as $field => $value) {
        $normalizedRow[(string) $field] = is_scalar($value) ? (string) $value : '';
      }

      $normalized[$dateKey] = $normalizedRow;
    }

    return $normalized;
  }
}
