<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Attributes\ExtensionHook;

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
  /**
   * Handles renderFromHookBusAuto operation.
   */
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

