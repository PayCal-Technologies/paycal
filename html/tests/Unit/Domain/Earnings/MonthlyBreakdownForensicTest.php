<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain\Earnings;

use PayCal\Tests\Support\ForensicTaxSupport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Forensic tests for monthly breakdown deduction derivation paths.
 */
final class MonthlyBreakdownForensicTest extends TestCase
{
  private const YEAR = 2026;

  /** @return array<string, mixed> */
  private function snapshotWithMonthlyGross(float ...$monthlyGross): array
  {
    $entries = [];
    foreach ($monthlyGross as $index => $gross) {
      if ($gross <= 0) {
        continue;
      }
      $month = $index + 1;
      $entries[] = [
        'date' => sprintf('%d-%02d-15', self::YEAR, $month),
        'gross' => $gross,
        'regular_hours' => 8.0,
      ];
    }

    return ForensicTaxSupport::buildWorkSnapshotFromEntries($entries);
  }

  #[Test]
  public function forensicMemberMonthlyGrossMinusNetShowsZeroDeductionsWithoutStoredTax(): void
  {
    $snapshot = $this->snapshotWithMonthlyGross(5000.0, 5200.0, 4800.0);
    $months = ForensicTaxSupport::deriveMemberMonthlyDeductionsGrossMinusNet($snapshot, self::YEAR);

    $this->assertSame(0.0, $months['2026-01']['deductions']);
    $this->assertSame(0.0, $months['2026-02']['deductions']);
    $this->assertSame(0.0, $months['2026-03']['deductions']);
    $this->assertSame(5000.0, $months['2026-01']['net']);
  }

  #[Test]
  public function forensicMemberMonthlyRegressionNetEqualsGrossWhenTaxAbsent(): void
  {
    $snapshot = $this->snapshotWithMonthlyGross(3000.0);
    $jan = ForensicTaxSupport::deriveMemberMonthlyDeductionsGrossMinusNet($snapshot, self::YEAR)['2026-01'];
    $this->assertEqualsWithDelta($jan['gross'], $jan['net'], 0.001);
  }

  #[Test]
  public function forensicYtdDeltaProducesNonZeroDeductionsForGrossMonths(): void
  {
    $snapshot = $this->snapshotWithMonthlyGross(5000.0, 5200.0);
    $months = ForensicTaxSupport::computeYtdDeltaMonthlyDeductions($snapshot, self::YEAR);

    $this->assertGreaterThan(0, $months['2026-01']['deductions']);
    $this->assertGreaterThan(0, $months['2026-02']['deductions']);
  }

  #[Test]
  public function forensicYtdDeltaNetLessThanGrossForActiveMonths(): void
  {
    $snapshot = $this->snapshotWithMonthlyGross(6000.0);
    $jan = ForensicTaxSupport::computeYtdDeltaMonthlyDeductions($snapshot, self::YEAR)['2026-01'];
    $this->assertLessThan($jan['gross'], $jan['net']);
    $this->assertGreaterThan(0, $jan['deductions']);
    $this->assertSame($jan['gross'] - $jan['deductions'], $jan['net']);
  }

  #[Test]
  public function forensicYtdDeltaEmptyMonthsHaveZeroGrossAndDeductions(): void
  {
    $snapshot = $this->snapshotWithMonthlyGross(4000.0);
    $months = ForensicTaxSupport::computeYtdDeltaMonthlyDeductions($snapshot, self::YEAR);
    $this->assertSame(0, $months['2026-06']['gross']);
    $this->assertSame(0, $months['2026-06']['deductions']);
  }

  #[Test]
  public function forensicYtdDeltaCumulativeGrossIncreasesDeductions(): void
  {
    $snapshot = $this->snapshotWithMonthlyGross(3000.0, 3000.0, 3000.0);
    $months = ForensicTaxSupport::computeYtdDeltaMonthlyDeductions($snapshot, self::YEAR);
    $this->assertGreaterThanOrEqual(
      $months['2026-01']['deductions'],
      $months['2026-02']['deductions'],
    );
    $this->assertGreaterThanOrEqual(
      $months['2026-02']['deductions'],
      $months['2026-03']['deductions'],
    );
  }

  #[Test]
  public function forensicStoredTaxProducesNonZeroGrossMinusNetDeductions(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-04-10', 'gross' => 1000.0, 'tax' => 250.0, 'net' => 750.0],
    ]);
    $apr = ForensicTaxSupport::deriveMemberMonthlyDeductionsGrossMinusNet($snapshot, self::YEAR)['2026-04'];
    $this->assertEqualsWithDelta(250.0, $apr['deductions'], 0.01);
  }

  #[Test]
  public function forensicMultipleEntriesSameMonthSumGrossCorrectly(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-05-01', 'gross' => 200.0],
      ['date' => '2026-05-15', 'gross' => 300.0],
      ['date' => '2026-05-28', 'gross' => 500.0],
    ]);
    $may = ForensicTaxSupport::deriveMemberMonthlyDeductionsGrossMinusNet($snapshot, self::YEAR)['2026-05'];
    $this->assertEqualsWithDelta(1000.0, $may['gross'], 0.01);
  }

  #[Test]
  public function forensicMultipleEntriesSameMonthSumHoursInSnapshot(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-05-01', 'gross' => 200.0, 'regular_hours' => 4.0],
      ['date' => '2026-05-15', 'gross' => 300.0, 'regular_hours' => 6.0],
    ]);
    $yearData = $snapshot['by_year'][self::YEAR];
    $this->assertEqualsWithDelta(10.0, $yearData['reg_hours'], 0.01);
  }

  #[Test]
  public function forensicGapBetweenGrossMinusNetAndYtdDeltaIsDocumented(): void
  {
    $snapshot = $this->snapshotWithMonthlyGross(5500.0);
    $stub = ForensicTaxSupport::deriveMemberMonthlyDeductionsGrossMinusNet($snapshot, self::YEAR)['2026-01'];
    $engine = ForensicTaxSupport::computeYtdDeltaMonthlyDeductions($snapshot, self::YEAR)['2026-01'];

    $this->assertSame(0.0, $stub['deductions']);
    $this->assertGreaterThan(0, $engine['deductions']);
  }

  #[Test]
  public function forensicYtdDeltaJanuaryDeductionMatchesAnnualOnSingleMonth(): void
  {
    $snapshot = $this->snapshotWithMonthlyGross(6500.0);
    $jan = ForensicTaxSupport::computeYtdDeltaMonthlyDeductions($snapshot, self::YEAR)['2026-01'];
    $annual = (new \PayCal\Domain\Taxes('Alberta', self::YEAR))->calculateTaxesCents(650000)['totalDeductions'];
    $this->assertSame($annual, $jan['deductions']);
  }

  #[Test]
  public function forensicYtdDeltaFebruaryIsIncrementalNotFullYtd(): void
  {
    $snapshot = $this->snapshotWithMonthlyGross(5000.0, 5000.0);
    $months = ForensicTaxSupport::computeYtdDeltaMonthlyDeductions($snapshot, self::YEAR);
    $ytdTwoMonths = (new \PayCal\Domain\Taxes('Alberta', self::YEAR))->calculateTaxesCents(1000000)['totalDeductions'];
    $this->assertSame(
      $ytdTwoMonths - $months['2026-01']['deductions'],
      $months['2026-02']['deductions'],
    );
  }

  #[Test]
  public function forensicMemberMonthlyRendererOutputs12Rows(): void
  {
    $snapshot = $this->snapshotWithMonthlyGross(1000.0);
    $html = ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'renderMemberMonthlyViewStrip',
      self::YEAR,
      $snapshot,
    );
    $this->assertIsString($html);
    $this->assertStringContainsString('aria-rowcount="12"', (string) $html);
  }

  #[Test]
  public function forensicMemberMonthlyRendererContainsDeductionsColumnHeader(): void
  {
    $snapshot = $this->snapshotWithMonthlyGross(1000.0);
    $html = (string) ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'renderMemberMonthlyViewStrip',
      self::YEAR,
      $snapshot,
    );
    $this->assertStringContainsString('datagrid_cols_11', $html);
    $this->assertStringContainsString('aria-colcount="11"', $html);
  }

  #[Test]
  public function forensicMemberMonthlyHtmlShowsNonZeroDeductionsForGrossOnlyData(): void
  {
    $snapshot = $this->snapshotWithMonthlyGross(4500.0);
    $expected = ForensicTaxSupport::computeYtdDeltaMonthlyDeductions($snapshot, self::YEAR)['2026-01'];
    $html = (string) ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'renderMemberMonthlyViewStrip',
      self::YEAR,
      $snapshot,
    );
    $this->assertStringContainsString(
      ForensicTaxSupport::formatTemplateCurrency($expected['deductions'] / 100),
      $html,
    );
    $this->assertGreaterThan(0, $expected['deductions']);
  }

  #[Test]
  public function forensicEmptyWorkSnapshotYieldsZeroGrossAllMonths(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([]);
    $months = ForensicTaxSupport::deriveMemberMonthlyDeductionsGrossMinusNet($snapshot, self::YEAR);
    foreach ($months as $row) {
      $this->assertSame(0.0, $row['gross']);
      $this->assertSame(0.0, $row['deductions']);
    }
  }

  #[Test]
  public function forensicActiveMonthsTrackedInSnapshot(): void
  {
    $snapshot = $this->snapshotWithMonthlyGross(0.0, 0.0, 2500.0);
    $active = $snapshot['by_year'][self::YEAR]['active_months'] ?? [];
    $this->assertArrayHasKey('2026-03', $active);
    $this->assertArrayNotHasKey('2026-01', $active);
  }

  #[Test]
  public function forensicGrossByDateRollupMatchesMonthTotal(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-07-01', 'gross' => 100.0],
      ['date' => '2026-07-20', 'gross' => 200.0],
    ]);
    $julyGross = ForensicTaxSupport::deriveMemberMonthlyDeductionsGrossMinusNet($snapshot, self::YEAR)['2026-07']['gross'];
    $byDateSum = array_sum($snapshot['by_year'][self::YEAR]['gross_by_date']);
    $this->assertEqualsWithDelta(300.0, $julyGross, 0.01);
    $this->assertEqualsWithDelta(300.0, $byDateSum, 0.01);
  }

  #[Test]
  public function forensicYtdDeltaNetEqualsGrossMinusDeductionsInvariant(): void
  {
    $snapshot = $this->snapshotWithMonthlyGross(4000.0, 4500.0, 5000.0);
    $months = ForensicTaxSupport::computeYtdDeltaMonthlyDeductions($snapshot, self::YEAR);
    foreach (['2026-01', '2026-02', '2026-03'] as $key) {
      $this->assertSame(
        $months[$key]['gross'] - $months[$key]['deductions'],
        $months[$key]['net'],
      );
    }
  }

  #[Test]
  public function forensicCarolJohnsonJanJunMonthlyDeductionsMatchYtdDelta(): void
  {
    $carolMonthlyGross = [7040.0, 6720.0, 6800.0, 7440.0, 6480.0, 1680.0];
    $entries = [];
    foreach ($carolMonthlyGross as $index => $gross) {
      $entries[] = [
        'date' => sprintf('%d-%02d-15', self::YEAR, $index + 1),
        'gross' => $gross,
        'regular_hours' => 8.0,
      ];
    }
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries($entries);
    $expected = ForensicTaxSupport::computeYtdDeltaMonthlyDeductions($snapshot, self::YEAR, 'AB');

    $user = new \PayCal\Domain\User();
    $user->province = 'AB';
    $html = (string) ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'renderMemberMonthlyViewStrip',
      self::YEAR,
      $snapshot,
      $user,
    );

    $this->assertStringContainsString(ForensicTaxSupport::formatTemplateCurrency(7040.0), $html);
    $this->assertStringContainsString(
      ForensicTaxSupport::formatTemplateCurrency($expected['2026-01']['deductions'] / 100),
      $html,
    );
    $this->assertGreaterThan(0, $expected['2026-01']['deductions']);
    $this->assertGreaterThan(0, $expected['2026-06']['deductions']);
  }

  #[Test]
  public function forensicLegacyAliasFieldsAggregateCorrectly(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-08-05', 'g' => 150.0, 'r' => 7.5, 'o' => 0.5],
    ]);
    $aug = $snapshot['by_year'][self::YEAR]['months'][0];
    $this->assertEqualsWithDelta(150.0, $aug['gross'], 0.01);
    $this->assertEqualsWithDelta(7.5, $aug['reg_hours'], 0.01);
    $this->assertEqualsWithDelta(0.5, $aug['ot_hours'], 0.01);
  }
}
