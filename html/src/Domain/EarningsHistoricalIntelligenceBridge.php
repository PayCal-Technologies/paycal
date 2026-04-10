<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Isolates core Earnings domain from private historical intelligence extension implementation.
 */
final class EarningsHistoricalIntelligenceBridge
{
  /**
   * @param array<string, string> $payload
   */
  public static function render(int $year, array $payload): ?string
  {
    $overrideRendered = self::renderViaHooksClass(
      dirname(__DIR__, 2) . '/extensions/overrides/earnings-historical-intelligence/hooks.php',
      'PayCal\\Extensions\\Overrides\\EarningsHistoricalIntelligence\\Hooks',
      $year,
      $payload
    );

    if (is_string($overrideRendered) && trim($overrideRendered) !== '') {
      return $overrideRendered;
    }

    return self::renderViaHooksClass(
      dirname(__DIR__, 2) . '/extensions/basic/earnings-historical-intelligence/hooks.php',
      'PayCal\\Extensions\\Basic\\EarningsHistoricalIntelligence\\Hooks',
      $year,
      $payload
    );
  }

  /**
   * @param array<string, string> $payload
   */
  private static function renderViaHooksClass(string $hooksFile, string $hooksClass, int $year, array $payload): ?string
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
      'mode' => 'private',
    ]);
  }
}
