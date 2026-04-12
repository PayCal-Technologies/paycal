<?php declare(strict_types=1);

namespace PayCal\Extensions\Overrides\EarningsYtd;

use PayCal\Domain\Render;

/**
 * Private YTD renderer hook implementation.
 *
 * Uses template-based rendering to preserve richer canonical paycal.app
 * presentation while keeping Core and basic package decoupled.
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

    return Render::template('earnings-year-to-date', self::stringMap($payload));
  }

  /**
   * @param array<string, mixed> $payload
   * @return array<string, string>
   */
  private static function stringMap(array $payload): array
  {
    $mapped = [];
    foreach ($payload as $key => $value) {
      // Type narrowing for key - payload keys are typed as strings but runtime may vary
      $strKey = (string) $key;
      $mapped[$strKey] = is_scalar($value) ? (string) $value : '';
    }

    return $mapped;
  }
}
