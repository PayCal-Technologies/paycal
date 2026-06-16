<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Renders the forecast workspace shell (header + mount point) for SSR.
 * Interactive panels hydrate via forecast-calculator.js.
 */
final class ForecastWorkspaceRenderer
{
  /**
   * @param array<string, mixed> $initialState
   */
  public static function renderShell(
    array $initialState,
    string $workspaceId = 'forecast_workspace',
    bool $isMemberView = false,
  ): string {
    $titleKey = $isMemberView ? 'EARNINGS_FORECAST_TITLE' : 'EARNINGS_FORECAST_TITLE';
    $subtitleKey = $isMemberView ? 'EARNINGS_FORECAST_INTRO' : 'EARNINGS_FORECAST_INTRO_SELF';

    $title = htmlspecialchars(Strings::i18n($titleKey), ENT_QUOTES, 'UTF-8');
    $subtitle = htmlspecialchars(Strings::i18n($subtitleKey), ENT_QUOTES, 'UTF-8');
    $badgeEstimate = htmlspecialchars(Strings::i18n('EARNINGS_FORECAST_BADGE_ESTIMATE'), ENT_QUOTES, 'UTF-8');
    $badgeNotCra = htmlspecialchars(Strings::i18n('EARNINGS_FORECAST_BADGE_NOT_CRA'), ENT_QUOTES, 'UTF-8');
    $aria = htmlspecialchars(Strings::i18n('EARNINGS_FORECAST_WORKSPACE_ARIA'), ENT_QUOTES, 'UTF-8');
    $loading = htmlspecialchars(Strings::i18n('EARNINGS_FORECAST_LOADING'), ENT_QUOTES, 'UTF-8');

    $stateJson = htmlspecialchars(
      json_encode($initialState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
      ENT_QUOTES,
      'UTF-8',
    );

    $safeId = htmlspecialchars($workspaceId, ENT_QUOTES, 'UTF-8');

    if (($initialState['setup_required'] ?? false) === true) {
      $setupLink = '<a href="/settings/account/">'
        . htmlspecialchars(Strings::i18n('EARNINGS_FORECAST_SETUP_LINK'), ENT_QUOTES, 'UTF-8')
        . '</a>';
      $setupNotice = str_replace(
        '{link}',
        $setupLink,
        Strings::i18n('EARNINGS_FORECAST_SETUP_NOTICE'),
      );

      return '<div class="forecast-workspace forecast-workspace--setup" id="' . $safeId . '" data-forecast-workspace="1">'
        . '<header class="forecast-workspace__header">'
        . '<h2 class="forecast-workspace__title">' . $title . '</h2>'
        . '<p class="forecast-workspace__subtitle">' . $subtitle . '</p>'
        . '<div class="forecast-workspace__badges">'
        . '<span class="forecast-badge forecast-badge--estimate">' . $badgeEstimate . '</span>'
        . '<span class="forecast-badge forecast-badge--caution">' . $badgeNotCra . '</span>'
        . '</div>'
        . '</header>'
        . '<p class="forecast_setup_notice">' . $setupNotice . '</p>'
        . '</div>';
    }

    return '<div class="forecast-workspace" id="' . $safeId . '" data-forecast-workspace="1" '
      . 'data-forecast-state="' . $stateJson . '" aria-label="' . $aria . '">'
      . '<header class="forecast-workspace__header">'
      . '<h2 class="forecast-workspace__title">' . $title . '</h2>'
      . '<p class="forecast-workspace__subtitle">' . $subtitle . '</p>'
      . '<div class="forecast-workspace__badges">'
      . '<span class="forecast-badge forecast-badge--estimate">' . $badgeEstimate . '</span>'
      . '<span class="forecast-badge forecast-badge--caution">' . $badgeNotCra . '</span>'
      . '</div>'
      . '</header>'
      . '<p class="forecast-workspace__loading" role="status">' . $loading . '</p>'
      . '<div class="forecast-workspace__mount" data-forecast-mount="1"></div>'
      . '<p id="forecast_summary_sr" class="visually_hidden" role="status" aria-live="polite" aria-atomic="true"></p>'
      . '</div>';
  }
}
