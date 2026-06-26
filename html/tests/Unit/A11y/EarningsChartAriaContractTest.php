<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class EarningsChartAriaContractTest extends TestCase
{
  private function projectRoot(): string
  {
    return dirname(__DIR__, 4);
  }

  private function htmlRoot(): string
  {
    return $this->projectRoot() . '/html';
  }

  #[Test]
  public function earningsTrendChartExposesImgRoleWithScreenReaderSummaryNodes(): void
  {
    $earnings = (string) file_get_contents($this->htmlRoot() . '/src/Domain/Earnings.php');
    $memberReports = (string) file_get_contents($this->htmlRoot() . '/src/Domain/BusinessMemberReportsService.php');

    $this->assertStringContainsString('role="img" aria-labelledby="earnings_line_graph_{$year}_title"', $earnings);
    $this->assertStringContainsString('aria-describedby="earnings_line_graph_{$year}_desc earnings_line_graph_{$year}_status"', $earnings);
    $this->assertStringContainsString('id="earnings_line_graph_{$year}_status" role="status" aria-live="polite"', $earnings);
    $this->assertStringContainsString('focusable="false"', $earnings);

    $this->assertStringContainsString('role="img" aria-labelledby="{$graphId}_title"', $memberReports);
    $this->assertStringContainsString('aria-describedby="{$graphId}_desc {$graphId}_status"', $memberReports);
    $this->assertStringContainsString('id="{$graphId}_status" role="status" aria-live="polite"', $memberReports);
  }

  #[Test]
  public function pieGraphMarkupLinksChartsToLegendsAndMonthSelect(): void
  {
    $pieHooks = (string) file_get_contents($this->htmlRoot() . '/extensions/overrides/earnings-piegraphs/hooks.php');
    $memberReports = (string) file_get_contents($this->htmlRoot() . '/src/Domain/BusinessMemberReportsService.php');

    $this->assertStringContainsString('role="img"', $pieHooks);
    $this->assertStringContainsString('aria-describedby="', $pieHooks);
    $this->assertStringContainsString('aria-label="', $pieHooks);
    $this->assertStringContainsString('earnings_piegraphs_month_select_', $pieHooks);
    $this->assertStringContainsString('focusable="false"', $pieHooks);

    $this->assertStringContainsString("Strings::i18n('EARNINGS_PIEGRAPHS_MONTH_SELECT_ARIA')", $memberReports);
    $this->assertStringContainsString('member_reports_piegraphs_ytd_legend_', $memberReports);
    $this->assertStringContainsString('aria-describedby="', $memberReports);
  }

  #[Test]
  public function pieGraphCoreHidesDecorativeElementsAndSyncsLegendDescription(): void
  {
    $pieGraphCore = (string) file_get_contents($this->htmlRoot() . '/js/earnings/pie-graph-core.js');

    $this->assertStringContainsString('function markPieGraphDecorative(el)', $pieGraphCore);
    $this->assertStringContainsString('function syncPieGraphSvgDescription(svgEl, legendEl)', $pieGraphCore);
    $this->assertStringContainsString("el.setAttribute('aria-hidden', 'true')", $pieGraphCore);
    $this->assertStringContainsString('markPieGraphDecorative(cutout)', $pieGraphCore);
    $this->assertStringContainsString('aria-hidden="true"></span>', $pieGraphCore);
    $this->assertStringContainsString("svgEl.setAttribute('aria-describedby', legendId)", $pieGraphCore);
  }

  #[Test]
  public function trendChartRendererMarksDecorativeSvgAndAnnouncesLiveStatus(): void
  {
    $trendChart = (string) file_get_contents($this->htmlRoot() . '/js/earnings/trend-chart.js');
    $earningsJs = (string) file_get_contents($this->htmlRoot() . '/js/earnings/index.php');

    $this->assertStringContainsString('function markEarningsChartDecorative(el)', $trendChart);
    $this->assertStringContainsString('announceEarningsGraphStatus', $trendChart);
    $this->assertStringContainsString('statusNode.textContent', $trendChart);
    $this->assertStringContainsString('descNode.textContent', $trendChart);
    $this->assertStringContainsString('markEarningsChartDecorative(defs)', $trendChart);
    $this->assertStringContainsString('EARNINGS_TREND_TOUCH_HINT', $trendChart);

    $this->assertStringContainsString("Render::jsStaticURL('js/earnings/trend-chart.js')", $earningsJs);
    $this->assertStringContainsString('drawLineGraph(data, svgID', $earningsJs);
    $this->assertStringContainsString('EARNINGS_TREND_TOUCH_HINT', $earningsJs);
  }

  #[Test]
  public function earningsDailyGridUsesGridSemanticsNotImgRole(): void
  {
    $earningsJs = (string) file_get_contents($this->htmlRoot() . '/js/earnings/index.php');

    $this->assertStringContainsString("setAttribute('role', 'grid')", $earningsJs);
    $this->assertStringContainsString("setAttribute('role', 'gridcell')", $earningsJs);
    $this->assertStringContainsString("setAttribute('role', 'columnheader')", $earningsJs);
    $this->assertStringContainsString('daily_earnings_${year}_sr_status', $earningsJs);
    $this->assertStringContainsString("setAttribute('aria-live', 'polite')", $earningsJs);
  }

  #[Test]
  public function earningsTrendTouchHintKeyExistsInAllLocales(): void
  {
    $locales = ['en', 'de', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'];
    foreach ($locales as $locale) {
      $strings = (string) file_get_contents($this->projectRoot() . '/strings/' . $locale . '.txt');
      $this->assertStringContainsString('EARNINGS_TREND_TOUCH_HINT', $strings, $locale);
      $this->assertStringContainsString('EARNINGS_PIEGRAPHS_MONTH_SELECT_ARIA', $strings, $locale);
    }
  }
}
