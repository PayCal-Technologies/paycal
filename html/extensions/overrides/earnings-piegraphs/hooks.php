<?php declare(strict_types=1);

namespace PayCal\Extensions\Overrides\EarningsPieGraphs;

/**
 * Private pie graphs renderer hook.
 */
final class Hooks
{
  /**
   * @param array<string, mixed> $context
   */
  public static function render(array $context): string
  {
    $yearCandidate = $context['year'] ?? null;
    $year = (is_int($yearCandidate) || is_string($yearCandidate)) ? (int) $yearCandidate : (int) date('Y');

    $payloadRaw = $context['payload'] ?? [];
    $payload = is_array($payloadRaw) ? $payloadRaw : [];

    $panelTitle = self::esc(self::payloadText($payload, 'panel_title', 'Earnings Composition'));
    $ytdTitle = self::esc(self::payloadText($payload, 'ytd_title', 'YTD Composition'));
    $monthlyTitle = self::esc(self::payloadText($payload, 'monthly_title', 'Monthly Composition'));
    $yearAttr = (string) $year;

    return '<section class="panel w100 earnings_panel earnings_piegraphs_panel" id="earnings_piegraphs_panel_' . $yearAttr . '" data-earnings-piegraphs-year="' . $yearAttr . '">'
      . '<h2 class="earnings_panel_title">' . $panelTitle . '</h2>'
      . '<div class="earnings_piegraphs_grid">'
      . '<article class="panel earnings_panel earnings_piegraphs_card">'
      . '<h3 class="earnings_piegraphs_card_title">' . $ytdTitle . '</h3>'
      . '<div class="earnings_piegraphs_month_controls_spacer" aria-hidden="true"></div>'
      . '<svg id="earnings_piegraphs_ytd_svg_' . $yearAttr . '" class="earnings_piegraphs_svg" viewBox="0 0 240 240" role="img" aria-label="' . $ytdTitle . ' ' . $yearAttr . '"></svg>'
      . '<div id="earnings_piegraphs_ytd_legend_' . $yearAttr . '" class="earnings_piegraphs_legend" aria-live="polite"></div>'
      . '</article>'
      . '<article class="panel earnings_panel earnings_piegraphs_card">'
      . '<h3 class="earnings_piegraphs_card_title">' . $monthlyTitle . '</h3>'
      . '<div class="earnings_piegraphs_month_controls">'
      . '<select id="earnings_piegraphs_month_select_' . $yearAttr . '" class="earnings_piegraphs_month_select" data-earnings-piegraphs-year="' . $yearAttr . '"></select>'
      . '</div>'
      . '<svg id="earnings_piegraphs_month_svg_' . $yearAttr . '" class="earnings_piegraphs_svg" viewBox="0 0 240 240" role="img" aria-label="' . $monthlyTitle . ' ' . $yearAttr . '"></svg>'
      . '<div id="earnings_piegraphs_month_legend_' . $yearAttr . '" class="earnings_piegraphs_legend" aria-live="polite"></div>'
      . '</article>'
      . '</div>'
      . '</section>';
  }

  private static function esc(string $value): string
  {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
  }

  /**
   * @param array<string, mixed> $payload
   */
  private static function payloadText(array $payload, string $key, string $default): string
  {
    if (!array_key_exists($key, $payload)) {
      return $default;
    }

    $value = $payload[$key];
    return is_scalar($value) ? (string) $value : $default;
  }
}
