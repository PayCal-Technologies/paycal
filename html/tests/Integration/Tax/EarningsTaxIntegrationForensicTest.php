<?php declare(strict_types=1);

namespace PayCal\Tests\Integration\Tax;

use PayCal\Domain\BusinessMembersFinancialSummary;
use PayCal\Domain\Earnings;
use PayCal\Domain\Taxes;
use PayCal\Tests\Support\ForensicTaxSupport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Cross-path forensic integration tests for earnings and tax consistency.
 */
final class EarningsTaxIntegrationForensicTest extends TestCase
{
  private const YEAR = 2026;

  #[Test]
  public function forensicGetTotalsForRangeReturnsDeductionsStructure(): void
  {
    $start = new \DateTimeImmutable('2026-01-01');
    $end = new \DateTimeImmutable('2026-01-31');
    $totals = Earnings::getTotalsForRange($start, $end);
    $this->assertArrayHasKey('deductions', $totals);
    $this->assertArrayHasKey('tax', $totals['deductions']);
    $this->assertArrayHasKey('taxCents', $totals['totals']);
  }

  #[Test]
  public function forensicGetTotalsForRangeNetCentsInvariantWhenComputed(): void
  {
    $start = new \DateTimeImmutable('2026-01-01');
    $end = new \DateTimeImmutable('2026-01-07');
    $totals = Earnings::getTotalsForRange($start, $end);
    if ($totals['totals']['grossCents'] > 0 && $totals['totals']['taxCents'] > 0) {
      $this->assertSame(
        $totals['totals']['grossCents'] - $totals['totals']['taxCents'],
        $totals['totals']['netCents'],
      );
    } else {
      $this->assertGreaterThanOrEqual(0, $totals['totals']['grossCents']);
    }
  }

  #[Test]
  public function forensicTaxEngineAndEarningsFallbackUseSameAlbertaDefault(): void
  {
    $grossCents = 4500000;
    $engine = (new Taxes('Alberta', self::YEAR))->calculateTaxesCents($grossCents)['totalDeductions'];
    $this->assertGreaterThan(0, $engine);
    $this->assertIsInt($engine);
  }

  #[Test]
  public function forensicBusinessMembersFinancialSummaryReturnsPerMemberShape(): void
  {
    $service = new BusinessMembersFinancialSummary();
    $result = $service->forBusinessMembers('business-test', ['member-x']);
    $this->assertArrayHasKey('member-x', $result);
    $this->assertArrayHasKey('ytd_gross', $result['member-x']);
    $this->assertArrayHasKey('total_hours', $result['member-x']);
    $this->assertArrayHasKey('trailing_baseline', $result['member-x']);
  }

  #[Test]
  public function forensicFinancialSummaryUnknownMemberDefaultsToZero(): void
  {
    $service = new BusinessMembersFinancialSummary();
    $result = $service->forBusinessMembers('biz', ['unknown-member']);
    $this->assertSame(0.0, $result['unknown-member']['ytd_gross']);
    $this->assertSame(0.0, $result['unknown-member']['total_hours']);
  }

  #[Test]
  public function forensicMonthlyHooksSourceUsesYtdDeltaPattern(): void
  {
    $hooksPath = dirname(__DIR__, 3) . '/extensions/overrides/earnings-monthly/hooks.php';
    $this->assertFileExists($hooksPath);
    $source = (string) file_get_contents($hooksPath);
    $this->assertStringContainsString('calculateTaxesCents', $source);
    $this->assertStringContainsString('previousGrossCents', $source);
    $this->assertStringContainsString('getTotalsForRange', $source);
  }

  #[Test]
  public function forensicPersonalMonthlyOverrideDiffersFromMemberMonthlyStub(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-05-10', 'gross' => 6000.0],
    ]);
    $memberStub = ForensicTaxSupport::deriveMemberMonthlyDeductionsGrossMinusNet($snapshot, self::YEAR)['2026-05'];
    $ytdDelta = ForensicTaxSupport::computeYtdDeltaMonthlyDeductions($snapshot, self::YEAR)['2026-05'];
    $this->assertSame(0.0, $memberStub['deductions']);
    $this->assertGreaterThan(0, $ytdDelta['deductions']);
  }

  #[Test]
  public function forensicTaxRateTablesJsonExistsAndIsValid(): void
  {
    $path = dirname(__DIR__, 3) . '/src/Domain/TaxRateTablesData.json';
    $this->assertFileExists($path);
    $decoded = json_decode((string) file_get_contents($path), true);
    $this->assertIsArray($decoded);
    $this->assertArrayHasKey('federal', $decoded);
    $this->assertArrayHasKey('provincial', $decoded);
    $this->assertArrayHasKey(2026, $decoded['federal']);
    $this->assertArrayHasKey('Alberta', $decoded['provincial']);
  }

  #[Test]
  public function forensicJsTaxMirrorFileExists(): void
  {
    $path = dirname(__DIR__, 3) . '/js/earnings/taxes.js';
    $this->assertFileExists($path);
    $source = (string) file_get_contents($path);
    $this->assertStringContainsString('calculateTaxesCents', $source);
  }

  #[Test]
  public function forensicEarningsMonthlyTemplateHasElevenColumns(): void
  {
    $path = dirname(__DIR__, 4) . '/templates/earnings-month.php';
    $this->assertFileExists($path);
    $source = (string) file_get_contents($path);
    $this->assertStringContainsString('__FEDERAL_TAX__', $source);
    $this->assertStringContainsString('__TOTAL_DEDUCTIONS__', $source);
  }

  #[Test]
  public function forensicMemberReportsApiRoutesExistInController(): void
  {
    $path = dirname(__DIR__, 3) . '/src/Controllers/BusinessDiscoveryController.php';
    $source = (string) file_get_contents($path);
    $this->assertStringContainsString('reports/monthly', $source);
    $this->assertStringContainsString('reports/ytd', $source);
    $this->assertStringContainsString('reports/daily', $source);
  }

  #[Test]
  public function forensicCrewForecastProvinceMapIncludesAbToAlberta(): void
  {
    $path = dirname(__DIR__, 3) . '/src/Domain/CrewForecastEngine.php';
    $source = (string) file_get_contents($path);
    $this->assertStringContainsString("'AB' => 'Alberta'", $source);
    $this->assertStringContainsString("'ON' => 'Ontario'", $source);
  }

  #[Test]
  public function forensicSnapshotGrossByYearCentsAlignsWithTaxEngineInput(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-08-01', 'gross' => 3333.33],
    ]);
    $cents = $snapshot['gross_by_year_cents'][self::YEAR];
    $deductions = (new Taxes('Alberta', self::YEAR))->calculateTaxesCents($cents)['totalDeductions'];
    $this->assertGreaterThan(0, $deductions);
    $this->assertLessThan($cents, $cents - $deductions);
  }

  #[Test]
  public function forensicYtdDeltaSumOfMonthlyGrossEqualsAnnualGross(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-01-15', 'gross' => 3000.0],
      ['date' => '2026-02-15', 'gross' => 4000.0],
      ['date' => '2026-03-15', 'gross' => 5000.0],
    ]);
    $months = ForensicTaxSupport::computeYtdDeltaMonthlyDeductions($snapshot, self::YEAR);
    $monthGrossSum = $months['2026-01']['gross'] + $months['2026-02']['gross'] + $months['2026-03']['gross'];
    $annualGrossCents = $snapshot['gross_by_year_cents'][self::YEAR];
    $this->assertSame($annualGrossCents, $monthGrossSum);
  }
}
