<?php declare(strict_types=1);

namespace PayCal\Extensions\Overrides\EarningsHistoricalIntelligence;

use PayCal\Domain\Render;

/**
 * Private historical intelligence renderer for earnings.
 */
final class Hooks
{
  /**
   * @param array<string, mixed> $context
   */
  public static function render(array $context): string
  {
    $payloadRaw = $context['payload'] ?? [];
    $payload = is_array($payloadRaw) ? $payloadRaw : [];

    return Render::template('earnings-historical-intelligence', self::stringMap($payload));
  }

  /**
   * @param array<string, mixed> $payload
   * @return array<string, string>
   */
  private static function stringMap(array $payload): array
  {
    $mapped = [];
    foreach ($payload as $key => $value) {
      $strKey = (string) $key;
      $mapped[$strKey] = is_scalar($value) ? (string) $value : '';
    }

    return $mapped;
  }
}
