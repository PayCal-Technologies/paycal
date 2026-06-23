<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\BusinessMemberReportsService;
use PayCal\Domain\Taxes;
use PayCal\Domain\User;
use PayCal\Tests\Support\ForensicTaxSupport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Forensic tests documenting tax integration gaps in member reports.
 */
final class BusinessMemberReportsTaxForensicTest extends TestCase
{
  private const YEAR = 2026;

  /** @return array<string, mixed> */
  private function grossOnlySnapshot(float $gross): array
  {
    return ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-06-15', 'gross' => $gross, 'regular_hours' => 40.0],
    ]);
  }

  #[Test]
  public function forensicYtdRendererInvokesTaxEngineOnAnnualGross(): void
  {
    $snapshot = $this->grossOnlySnapshot(65000.0);
    $user = new User();
    $user->province = 'AB';
    $html = (string) ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'renderMemberYearToDateSummary',
      self::YEAR,
      $snapshot,
      $user,
    );
    $expected = (new Taxes('AB', self::YEAR))->calculateTaxesCents(6500000)['totalDeductions'] / 100;
    $formatted = number_format($expected, 2, '.', ',');
    $this->assertStringContainsString($formatted, $html);
  }

  #[Test]
  public function forensicYtdShowsNonZeroTotalDeductionsForGrossOnlyData(): void
  {
    $snapshot = $this->grossOnlySnapshot(50000.0);
    $html = (string) ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'renderMemberYearToDateSummary',
      self::YEAR,
      $snapshot,
      null,
    );
    $tax = (new Taxes('Alberta', self::YEAR))->calculateTaxesCents(5000000)['totalDeductions'];
    $this->assertGreaterThan(0, $tax);
    $this->assertDoesNotMatchRegularExpression('/Total Deductions.*\$0\.00/si', $html);
  }

  #[Test]
  public function forensicMonthlyRendererInvokesTaxEngineViaYtdDelta(): void
  {
    $source = (string) file_get_contents(
      dirname(__DIR__, 3) . '/src/Domain/BusinessMemberReportsService.php',
    );
    $monthlyMethod = $this->extractMethodBody($source, 'renderMemberMonthlyViewStrip');
    $this->assertStringContainsString('calculateTaxesCents', $monthlyMethod);
    $this->assertStringNotContainsString('$gross - $net', $monthlyMethod);
  }

  #[Test]
  public function forensicYtdRendererDoesInvokeTaxEngine(): void
  {
    $source = (string) file_get_contents(
      dirname(__DIR__, 3) . '/src/Domain/BusinessMemberReportsService.php',
    );
    $ytdMethod = $this->extractMethodBody($source, 'renderMemberYearToDateSummary');
    $this->assertStringContainsString('calculateTaxesCents', $ytdMethod);
  }

  #[Test]
  public function forensicDailyPayloadInvokesTaxEngineWhenStoredTaxMissing(): void
  {
    $source = (string) file_get_contents(
      dirname(__DIR__, 3) . '/src/Domain/BusinessMemberReportsService.php',
    );
    $dailyMethod = $this->extractMethodBody($source, 'buildMemberDailyPayload');
    $this->assertStringContainsString('calculateTaxesCents', $dailyMethod);
    $this->assertStringContainsString('storedTax <= 0', $dailyMethod);
  }

  #[Test]
  public function forensicProvinceCodesMappedBeforeTaxesConstructor(): void
  {
    $source = (string) file_get_contents(
      dirname(__DIR__, 3) . '/src/Domain/BusinessMemberReportsService.php',
    );
    $this->assertStringContainsString('PROVINCE_NAMES', $source);
    $this->assertStringContainsString('resolveProvinceName', $source);
    $this->assertStringContainsString("'AB' => 'Alberta'", $source);
  }

  #[Test]
  public function forensicAbProvinceCodeFallsBackToAlbertaInYtdPath(): void
  {
    $snapshot = $this->grossOnlySnapshot(40000.0);
    $user = new User();
    $user->province = 'AB';
    $htmlAb = (string) ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'renderMemberYearToDateSummary',
      self::YEAR,
      $snapshot,
      $user,
    );
    $user->province = 'Alberta';
    $htmlFull = (string) ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'renderMemberYearToDateSummary',
      self::YEAR,
      $snapshot,
      $user,
    );
    $this->assertSame($htmlAb, $htmlFull);
  }

  #[Test]
  public function forensicMonthlyYtdDeltaAlignsWithYtdAnnualTaxForSingleMonth(): void
  {
    $snapshot = $this->grossOnlySnapshot(5500.0);
    $monthly = ForensicTaxSupport::computeYtdDeltaMonthlyDeductions($snapshot, self::YEAR)['2026-06'];
    $ytdTax = (new Taxes('Alberta', self::YEAR))->calculateTaxesCents(550000)['totalDeductions'];
    $this->assertSame($ytdTax, $monthly['deductions']);
    $this->assertGreaterThan(0, $monthly['deductions']);
  }

  #[Test]
  public function forensicCollectMemberBusinessWorkReadsTaxAndNetAliases(): void
  {
    $source = (string) file_get_contents(
      dirname(__DIR__, 3) . '/src/Domain/BusinessMemberReportsService.php',
    );
    $collectMethod = $this->extractMethodBody($source, 'collectMemberBusinessWork');
    $this->assertStringContainsString("entry['tax']", $collectMethod);
    $this->assertStringContainsString("entry['tx']", $collectMethod);
    $this->assertStringContainsString('entryGross - $entryTax', $collectMethod);
  }

  #[Test]
  public function forensicServiceSourceContainsMonthlySectionId(): void
  {
    $source = (string) file_get_contents(
      dirname(__DIR__, 3) . '/src/Domain/BusinessMemberReportsService.php',
    );
    $this->assertStringContainsString('member_reports_monthly_', $source);
    $this->assertStringContainsString('renderMemberMonthlyViewStrip', $source);
  }

  #[Test]
  public function forensicGetMemberReportsSectionHtmlRejectsInvalidSection(): void
  {
    $service = new BusinessMemberReportsService();
    $result = $service->getMemberReportsSectionHtml('actor', 'biz', 'member', 'invalid', self::YEAR);
    $this->assertFalse($result['success']);
  }

  #[Test]
  public function forensicEmptyYearDataYtdReturnsNoDataMessage(): void
  {
    $emptySnapshot = ['years' => [], 'by_year' => [], 'gross_by_year_cents' => []];
    $html = (string) ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'renderMemberYearToDateSummary',
      self::YEAR,
      $emptySnapshot,
      null,
    );
    $this->assertStringContainsString('earnings_async_status', $html);
  }

  #[Test]
  public function forensicDailyPayloadEmptyWhenNoDailyEntries(): void
  {
    $snapshot = ['years' => [self::YEAR], 'by_year' => [self::YEAR => ['daily_entries' => []]], 'gross_by_year_cents' => []];
    $payload = ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'buildMemberDailyPayload',
      self::YEAR,
      $snapshot,
      null,
    );
    $this->assertSame([], $payload);
  }

  #[Test]
  public function forensicDailyPayloadSortedByDate(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-07-20', 'gross' => 100.0],
      ['date' => '2026-07-05', 'gross' => 200.0],
    ]);
    $payload = ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'buildMemberDailyPayload',
      self::YEAR,
      $snapshot,
      null,
    );
    $this->assertSame(['2026-07-05', '2026-07-20'], array_keys($payload));
  }

  #[Test]
  public function forensicMonthlyRendererShowsEngineDeductionsForGrossOnlyData(): void
  {
    $snapshot = $this->grossOnlySnapshot(10000.0);
    $expected = ForensicTaxSupport::computeYtdDeltaMonthlyDeductions($snapshot, self::YEAR)['2026-06'];
    $html = (string) ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'renderMemberMonthlyViewStrip',
      self::YEAR,
      $snapshot,
    );
    $this->assertGreaterThan(0, $expected['deductions']);
    $this->assertStringContainsString(
      ForensicTaxSupport::formatDataGridCurrency($expected['deductions'] / 100),
      $html,
    );
    $this->assertStringContainsString(ForensicTaxSupport::formatDataGridCurrency(10000.0), $html);
    $this->assertStringNotContainsString(
      ForensicTaxSupport::formatDataGridCurrency(10000.0) . '</div><div class="datagrid_item" role="gridcell">' . ForensicTaxSupport::formatDataGridCurrency(10000.0),
      $html,
    );
  }

  #[Test]
  public function forensicYtdFederalAndProvincialRenderedSeparately(): void
  {
    $snapshot = $this->grossOnlySnapshot(80000.0);
    $html = (string) ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'renderMemberYearToDateSummary',
      self::YEAR,
      $snapshot,
      null,
    );
    $tax = (new Taxes('Alberta', self::YEAR))->calculateTaxesCents(8000000);
    $this->assertStringContainsString(number_format($tax['federal'] / 100, 2, '.', ','), $html);
    $this->assertStringContainsString(number_format($tax['provincial'] / 100, 2, '.', ','), $html);
  }

  #[Test]
  public function forensicEarningsGetTotalsForRangeHasTaxFallbackInSource(): void
  {
    $source = (string) file_get_contents(
      dirname(__DIR__, 3) . '/src/Domain/Earnings.php',
    );
    $this->assertStringContainsString('rowTaxCents <= 0 && $rowGrossCents > 0', $source);
    $this->assertStringContainsString("new Taxes('Alberta'", $source);
  }

  #[Test]
  public function forensicPayPeriodSnapshotUsesTaxEngineWhenStoredTaxMissing(): void
  {
    $snapshot = $this->grossOnlySnapshot(3200.0);
    $user = new User();
    $user->province = 'AB';
    $totals = ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'getSnapshotTotalsForRange',
      $snapshot,
      new \DateTimeImmutable('2026-06-01'),
      new \DateTimeImmutable('2026-06-30'),
      $user,
      self::YEAR,
    );
    $this->assertGreaterThan(0, $totals['totals']['taxCents']);
    $this->assertSame(
      $totals['totals']['grossCents'] - $totals['totals']['taxCents'],
      $totals['totals']['netCents'],
    );
  }

  private function extractMethodBody(string $source, string $methodName): string
  {
    $pattern = '/function\s+' . preg_quote($methodName, '/') . '\s*\([^)]*\)[^{]*\{/';
    if (!preg_match($pattern, $source, $match, PREG_OFFSET_CAPTURE)) {
      return '';
    }
    $start = $match[0][1] + strlen($match[0][0]);
    $depth = 1;
    $length = strlen($source);
    for ($i = $start; $i < $length; ++$i) {
      if ($source[$i] === '{') {
        ++$depth;
      } elseif ($source[$i] === '}') {
        --$depth;
        if ($depth === 0) {
          return substr($source, $start, $i - $start);
        }
      }
    }

    return '';
  }
}
