<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\BusinessReportsPanelRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BusinessReportsPanelRendererTest extends TestCase
{
  #[Test]
  public function loadingSkeletonMatchesReportsPanelStructure(): void
  {
    $renderer = new BusinessReportsPanelRenderer();
    $html = $renderer->loadingSkeleton(2026);

    $this->assertStringContainsString('earnings_team_panel', $html);
    $this->assertStringContainsString('business_reports_panel_skeleton', $html);
    $this->assertStringContainsString('data-reports-panel-loading="1"', $html);
    $this->assertStringContainsString('aria-busy="true"', $html);
    $this->assertStringContainsString('et_skeleton_exec', $html);
    $this->assertStringContainsString('et_skeleton_figure', $html);
    $this->assertStringContainsString('et_skeleton_grid_row', $html);
    $this->assertStringContainsString('reports_sk_bar_h_', $html);
    $this->assertStringContainsString(
      sprintf(\PayCal\Domain\Strings::i18n('BUSINESS_REPORTS_LOADING_ANALYTICS_YEAR'), '2026'),
      $html,
    );
    $this->assertStringNotContainsString('style=', $html);

    $projectRoot = dirname(__DIR__, 4);
    $hiTemplate = (string) file_get_contents($projectRoot . '/templates/earnings-historical-intelligence.php');
    $this->assertStringContainsString('__HI_LABEL_YEARS_OBSERVED__', $hiTemplate);
    $this->assertStringNotContainsString('>Years Observed<', $hiTemplate);
  }
}
