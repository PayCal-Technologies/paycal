<?php declare(strict_types=1);

namespace PayCal\Domain;

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
