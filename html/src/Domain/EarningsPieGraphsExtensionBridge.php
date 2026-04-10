<?php declare(strict_types=1);

namespace PayCal\Domain;

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
