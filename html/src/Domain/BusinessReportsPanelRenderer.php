<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Builds the business reports analytics panel HTML for SSR shell and loading placeholders.
 */
final class BusinessReportsPanelRenderer
{
  /**
   * TODO: Document loadingSkeleton.
   */
  public function loadingSkeleton(int $year = 0): string
  {
    $yearLabel = $year > 0 ? (string) $year : '';
    $ariaLabel = $yearLabel !== ''
      ? sprintf(Strings::i18n('BUSINESS_REPORTS_LOADING_ANALYTICS_YEAR'), $yearLabel)
      : Strings::i18n('BUSINESS_REPORTS_LOADING_ANALYTICS');

    return '<div class="earnings_team_panel business_reports_panel_skeleton" data-reports-panel-loading="1"'
      . ' aria-busy="true" aria-label="' . htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8') . '">'
      . $this->buildYearRowSkeleton()
      . $this->buildExecSnapshotSkeleton()
      . $this->buildChartFigureSkeleton([55, 80, 40, 95, 65, 50, 75, 30, 88, 60, 45, 70])
      . $this->buildChartFigureSkeleton([60, 75, 90, 80, 70, 55, 65, 85, 75, 60, 70, 80])
      . $this->buildMemberGridSkeleton()
      . '</div>';
  }

  /**
   * TODO: Document buildYearRowSkeleton.
   */
  private function buildYearRowSkeleton(): string
  {
    return '<div class="earnings_team_year_row business_reports_skeleton_year_row" aria-hidden="true">'
      . '<span class="sk-line sk-box reports_sk_year_chip"></span>'
      . '<span class="sk-line sk-box reports_sk_year_chip"></span>'
      . '<span class="sk-line sk-box reports_sk_export_btn"></span>'
      . '</div>';
  }

  /**
   * TODO: Document buildExecSnapshotSkeleton.
   */
  private function buildExecSnapshotSkeleton(): string
  {
    $items = '';
    for ($i = 0; $i < 5; $i++) {
      $items .= '<div class="et_skeleton_exec_item">'
        . '<span class="sk-line sk-box reports_sk_exec_value"></span>'
        . '<span class="sk-line sk-line--sm"></span>'
        . '<span class="sk-line sk-line--md reports_sk_exec_sub"></span>'
        . '</div>';
    }

    return '<div class="et_skeleton_exec" aria-hidden="true">' . $items . '</div>';
  }

  /**
   * @param list<int> $barHeights
   */
  private function buildChartFigureSkeleton(array $barHeights): string
  {
    $bars = '';
    foreach ($barHeights as $height) {
      $height = max(30, min(95, (int) $height));
      $bars .= '<span class="sk-chart-bar sk-box reports_sk_bar reports_sk_bar_h_' . $height . '"></span>';
    }

    return '<div class="et_skeleton_figure" aria-hidden="true">'
      . '<div class="et_skeleton_header">'
      . '<span class="sk-line sk-line--sm reports_sk_figure_title"></span>'
      . '<span class="sk-line sk-line--md reports_sk_figure_subtitle"></span>'
      . '</div>'
      . '<div class="et_skeleton_body">'
      . '<div class="et_skeleton_bars">' . $bars . '</div>'
      . '</div>'
      . '</div>';
  }

  /**
   * TODO: Document buildMemberGridSkeleton.
   */
  private function buildMemberGridSkeleton(): string
  {
    $header = '<div class="earnings_team_grid_header">'
      . str_repeat('<span><span class="sk-line sk-line--md reports_sk_grid_head_cell"></span></span>', 5)
      . '</div>';

    $rows = '';
    for ($i = 0; $i < 5; $i++) {
      $rows .= '<div class="et_skeleton_grid_row">'
        . '<span class="sk-line sk-line--lg"></span>'
        . str_repeat('<span class="sk-line sk-line--sm"></span>', 4)
        . '</div>';
    }

    return '<div class="earnings_team_grid business_reports_skeleton_grid" aria-hidden="true">'
      . $header
      . '<div class="et_skeleton_grid_rows">' . $rows . '</div>'
      . '</div>';
  }
}
