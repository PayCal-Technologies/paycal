<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\BusinessMemberReportsService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BusinessMemberReportsServiceTest extends TestCase
{
  #[Test]
  public function getMemberBreakdownRejectsMissingIdentifiers(): void
  {
    $service = new BusinessMemberReportsService();
    $result = $service->getMemberBreakdown('', 'business-123', 'member-abc', 2026);

    $this->assertFalse($result['success']);
    $this->assertSame([], $result['data']);
  }

  #[Test]
  public function getMemberEarningsViewRejectsMissingIdentifiers(): void
  {
    $service = new BusinessMemberReportsService();
    $result = $service->getMemberEarningsView('actor-1', '', 'member-abc', 2026);

    $this->assertFalse($result['success']);
    $this->assertSame([], $result['data']);
  }

  #[Test]
  public function getMemberEarningsViewDelegatesThroughGetMemberBreakdown(): void
  {
    $service = new BusinessMemberReportsService();
    $breakdown = $service->getMemberBreakdown('actor-1', 'business-123', 'member-abc', 2026);
    $view = $service->getMemberEarningsView('actor-1', 'business-123', 'member-abc', 2026);

    $this->assertSame($breakdown['success'], $view['success']);
    $this->assertSame($breakdown['message'], $view['message']);
  }

  #[Test]
  public function getMemberReportsSectionHtmlRejectsMissingIdentifiers(): void
  {
    $service = new BusinessMemberReportsService();
    $result = $service->getMemberReportsSectionHtml('actor-1', '', 'member-abc', 'ytd', 2026);

    $this->assertFalse($result['success']);
    $this->assertSame([], $result['data']);
  }

  #[Test]
  public function getMemberReportsGrossYearRejectsMissingIdentifiers(): void
  {
    $service = new BusinessMemberReportsService();
    $result = $service->getMemberReportsGrossYear('actor-1', 'business-123', '', 2026);

    $this->assertFalse($result['success']);
    $this->assertSame([], $result['data']);
  }

  #[Test]
  public function getMemberReportsDailyYearRejectsMissingIdentifiers(): void
  {
    $service = new BusinessMemberReportsService();
    $result = $service->getMemberReportsDailyYear('', 'business-123', 'member-abc', 2026);

    $this->assertFalse($result['success']);
    $this->assertSame([], $result['data']);
  }

  #[Test]
  public function getMemberReportsForecastRejectsMissingIdentifiers(): void
  {
    $service = new BusinessMemberReportsService();
    $result = $service->getMemberReportsForecast('actor-1', '', 'member-abc');

    $this->assertFalse($result['success']);
    $this->assertSame([], $result['data']);
  }

  #[Test]
  public function memberEarningsViewHtmlIncludesFullReportsSections(): void
  {
    $serviceSource = (string) file_get_contents(
      dirname(__DIR__, 3) . '/src/Domain/BusinessMemberReportsService.php',
    );

    $this->assertStringContainsString('member_reports_line_graph_', $serviceSource);
    $this->assertStringContainsString('member-reports-hi-', $serviceSource);
    $this->assertStringContainsString('member_reports_piegraphs_panel_', $serviceSource);
    $this->assertStringContainsString('member_reports_ytd_', $serviceSource);
    $this->assertStringContainsString('member_reports_pay_periods_', $serviceSource);
    $this->assertStringContainsString('member_reports_monthly_', $serviceSource);
    $this->assertStringContainsString('member_reports_daily_earnings_', $serviceSource);
    $this->assertStringContainsString('renderMemberExportButtons', $serviceSource);
    $this->assertStringContainsString('data-member-export-scope="', $serviceSource);
    $this->assertStringContainsString('data-member-reports-premium="{$premiumAttr}"', $serviceSource);
    $this->assertStringContainsString("self::TAB_ID_PREFIX . 'forecast'", $serviceSource);
    $this->assertStringContainsString('member_reports_forecast_content', $serviceSource);
    $this->assertStringContainsString('earnings_view_tabs', $serviceSource);
  }

  #[Test]
  public function memberReportsBrowserConvenienceExportsRequirePremiumFlag(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $memberReportsJs = (string) file_get_contents(
      $projectRoot . '/html/js/earnings/member-reports-view.js',
    );

    $this->assertStringContainsString('root.dataset.memberReportsPremium', $memberReportsJs);
    $this->assertStringContainsString('Premium subscription required for this export format.', $memberReportsJs);
  }
}
