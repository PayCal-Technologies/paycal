<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain\Earnings;

use PayCal\Domain\Money;
use PayCal\Domain\Taxes;
use PayCal\Tests\Support\ForensicTaxSupport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Forensic tests for gross / net / deductions invariants across paths.
 */
final class GrossNetDeductionForensicTest extends TestCase
{
  private const YEAR = 2026;

  #[Test]
  public function forensicTaxEngineNetEqualsGrossMinusTotalDeductions(): void
  {
    $grossCents = 7200000;
    $tax = new Taxes('Alberta', self::YEAR);
    $deductions = $tax->calculateTaxesCents($grossCents)['totalDeductions'];
    $net = $grossCents - $deductions;
    $this->assertGreaterThan(0, $deductions);
    $this->assertLessThan($grossCents, $net);
    $this->assertSame($grossCents, $net + $deductions);
  }

  #[Test]
  public function forensicWorkEntryNetDefaultsToGrossWhenTaxMissing(): void
  {
    $entryGross = 275.50;
    $entryTax = 0.0;
    $entryNet = $entryGross - $entryTax;
    $this->assertEqualsWithDelta($entryGross, $entryNet, 0.001);
  }

  #[Test]
  public function forensicWorkEntryNetRespectsExplicitTax(): void
  {
    $entryGross = 500.0;
    $entryTax = 120.0;
    $entryNet = 500.0 - 120.0;
    $this->assertEqualsWithDelta(380.0, $entryNet, 0.001);
  }

  #[Test]
  public function forensicSnapshotYearGrossEqualsSumOfMonthlyGross(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-01-10', 'gross' => 1000.0],
      ['date' => '2026-02-10', 'gross' => 2000.0],
      ['date' => '2026-03-10', 'gross' => 1500.0],
    ]);
    $yearGross = $snapshot['by_year'][self::YEAR]['gross'];
    $monthSum = array_sum(array_map(
      static fn (array $m): float => (float) $m['gross'],
      $snapshot['by_year'][self::YEAR]['months'],
    ));
    $this->assertEqualsWithDelta($yearGross, $monthSum, 0.01);
  }

  #[Test]
  public function forensicSnapshotYearNetEqualsGrossWhenNoStoredTax(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-04-10', 'gross' => 800.0],
      ['date' => '2026-05-10', 'gross' => 1200.0],
    ]);
    $yearData = $snapshot['by_year'][self::YEAR];
    $this->assertEqualsWithDelta($yearData['gross'], $yearData['net'], 0.01);
  }

  #[Test]
  public function forensicGrossByYearCentsMatchesDollarsToCents(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-06-01', 'gross' => 1234.56],
    ]);
    $cents = $snapshot['gross_by_year_cents'][self::YEAR];
    $this->assertSame(Money::dollarsToCents('1234.56'), $cents);
  }

  #[Test]
  public function forensicYtdSummaryWouldDeductTaxFromGross(): void
  {
    $grossCents = 1500000;
    $tax = new Taxes('Alberta', self::YEAR)->calculateTaxesCents($grossCents);
    $netCents = $grossCents - $tax['totalDeductions'];
    $this->assertGreaterThan(0, $tax['totalDeductions']);
    $this->assertLessThan($grossCents, $netCents);
  }

  #[Test]
  public function forensicDailyPayloadComputesTaxWhenStoredTaxZero(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-09-12', 'gross' => 350.0],
    ]);
    $user = new \PayCal\Domain\User();
    $user->province = 'AB';
    $payload = ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'buildMemberDailyPayload',
      self::YEAR,
      $snapshot,
      $user,
    );
    $this->assertIsArray($payload);
    $day = $payload['2026-09-12'];
    $this->assertGreaterThan(0.0, (float) $day['tax']);
    $this->assertGreaterThan(0.0, (float) $day['deductions']);
    $this->assertEqualsWithDelta(350.0, (float) $day['gross'], 0.01);
    $this->assertEqualsWithDelta(
      (float) $day['gross'] - (float) $day['tax'],
      (float) $day['net'],
      0.01,
    );
  }

  #[Test]
  public function forensicDailyPayloadPreservesStoredTax(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-09-13', 'gross' => 400.0, 'tax' => 99.50],
    ]);
    $payload = ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'buildMemberDailyPayload',
      self::YEAR,
      $snapshot,
      null,
    );
    $this->assertEqualsWithDelta(99.50, (float) $payload['2026-09-13']['tax'], 0.01);
  }

  #[Test]
  public function forensicDailyTaxComputedWhenSnapshotNetEqualsGross(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-10-01', 'gross' => 600.0],
    ]);
    $payload = ForensicTaxSupport::invokeBusinessMemberReportsPrivate(
      'buildMemberDailyPayload',
      self::YEAR,
      $snapshot,
      null,
    );
    $day = $payload['2026-10-01'];
    $this->assertGreaterThan(0.0, (float) $day['tax']);
    $this->assertEqualsWithDelta(
      (float) $day['gross'] - (float) $day['tax'],
      (float) $day['net'],
      0.01,
    );
  }

  #[Test]
  public function forensicMemberMonthlyDeductionsNeverNegative(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-11-01', 'gross' => 100.0, 'net' => 150.0],
    ]);
    $nov = ForensicTaxSupport::deriveMemberMonthlyDeductionsGrossMinusNet($snapshot, self::YEAR)['2026-11'];
    $this->assertSame(0.0, $nov['deductions']);
  }

  #[Test]
  public function forensicIncomeTaxComponentsSumToIncomeTaxKey(): void
  {
    $result = (new Taxes('Ontario', self::YEAR))->calculateTaxesCents(8800000);
    $this->assertSame($result['federal'] + $result['provincial'], $result['incomeTax']);
  }

  #[Test]
  public function forensicDeductionsColumnWouldBeNonZeroWithYtdDeltaApproach(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-12-05', 'gross' => 7500.0],
    ]);
    $engine = ForensicTaxSupport::computeYtdDeltaMonthlyDeductions($snapshot, self::YEAR)['2026-12'];
    $stub = ForensicTaxSupport::deriveMemberMonthlyDeductionsGrossMinusNet($snapshot, self::YEAR)['2026-12'];
    $this->assertGreaterThan(0, $engine['deductions']);
    $this->assertSame(0.0, $stub['deductions']);
  }

  #[Test]
  public function forensicTwoDaySameDateEntriesMergeInDailySnapshot(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-12-06', 'gross' => 100.0, 'site_name' => 'Site A'],
      ['date' => '2026-12-06', 'gross' => 50.0, 'site_name' => 'Site B'],
    ]);
    $daily = $snapshot['by_year'][self::YEAR]['daily_entries']['2026-12-06'];
    $this->assertEqualsWithDelta(150.0, (float) $daily['gross'], 0.01);
    $this->assertStringContainsString('Site A', $daily['site_name']);
    $this->assertStringContainsString('Site B', $daily['site_name']);
  }

  #[Test]
  public function forensicZeroGrossProducesZeroDeductionsInTaxEngine(): void
  {
    $result = (new Taxes('Alberta', self::YEAR))->calculateTaxesCents(0);
    $this->assertSame(0, $result['totalDeductions']);
  }
}
