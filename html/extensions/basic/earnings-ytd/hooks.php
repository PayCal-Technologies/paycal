<?php declare(strict_types=1);

namespace PayCal\Extensions\Basic\EarningsYtd;

/**
 * Baseline YTD renderer used when no private override is active.
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

    $id = self::payloadString($payload, '__EARNINGS_YTD_ID__');
    if ($id === '') {
      $id = 'basic-ytd';
    }

    $label = self::escape(self::payloadString($payload, '__EARNINGS_YTD_ARIA_LABEL__'));
    $regular = self::escape(self::payloadString($payload, '__REGULAR_HOURS__'));
    $overtime = self::escape(self::payloadString($payload, '__OVERTIME_HOURS__'));
    $hoursLabel = self::escape(self::payloadString($payload, '__HOURS__'));

    $regularLabel = self::escape(self::payloadString($payload, '__REGULAR__'));
    $overtimeLabel = self::escape(self::payloadString($payload, '__OVERTIME__'));
    $grossLabel = self::escape(self::payloadString($payload, '__GROSS_LABEL__'));
    $deductionsLabel = self::escape(self::payloadString($payload, '__EARNINGS_TOTAL_DEDUCTIONS__'));
    $netLabel = self::escape(self::payloadString($payload, '__NET_LABEL__'));

    $gross = self::currency(self::payloadString($payload, '__GROSS__'));
    $deductions = self::currency(self::payloadString($payload, '__TOTAL_DEDUCTIONS__'));
    $net = self::currency(self::payloadString($payload, '__NET__'));

    return '<section id="' . self::escape($id) . '" class="earnings_ytd_basic" role="region" aria-label="' . $label . '">'
      . '<h3 class="earnings_panel_subtitle">Year to Date Snapshot (Basic)</h3>'
      . '<dl class="earnings_ytd_basic_list">'
      . '<div class="earnings_ytd_basic_row"><dt>' . $regularLabel . '</dt><dd>' . $regular . ' ' . $hoursLabel . '</dd></div>'
      . '<div class="earnings_ytd_basic_row"><dt>' . $overtimeLabel . '</dt><dd>' . $overtime . ' ' . $hoursLabel . '</dd></div>'
      . '<div class="earnings_ytd_basic_row"><dt>' . $grossLabel . '</dt><dd>' . $gross . '</dd></div>'
      . '<div class="earnings_ytd_basic_row"><dt>' . $deductionsLabel . '</dt><dd>' . $deductions . '</dd></div>'
      . '<div class="earnings_ytd_basic_row"><dt><strong>' . $netLabel . '</strong></dt><dd><strong>' . $net . '</strong></dd></div>'
      . '</dl>'
      . '</section>';
  }

  /**
   * Read one scalar placeholder value from payload.
   *
   * @param array<string, mixed> $payload
   */
  private static function payloadString(array $payload, string $key): string
  {
    $value = $payload[$key] ?? '';
    return is_scalar($value) ? (string) $value : '';
  }

  /**
   * Normalize raw amount string to display currency format.
   */
  private static function currency(string $amount): string
  {
    $normalized = trim($amount);
    if ($normalized === '') {
      $normalized = '0.00';
    }

    return '$' . self::escape($normalized);
  }

  /**
   * Escape user-visible content before injecting into HTML.
   */
  private static function escape(string $value): string
  {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
  }
}
