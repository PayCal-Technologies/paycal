<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain\Earnings;

use PayCal\Domain\WorkEntry;
use PayCal\Tests\Support\ForensicTaxSupport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Forensic tests for work entry parsing, Redis key structure, and aggregation.
 */
final class WorkEntryAggregationForensicTest extends TestCase
{
  #[Test]
  public function forensicRedisWorkKeyFollowsWorkUuidDateSitePattern(): void
  {
    $key = ForensicTaxSupport::redisWorkKey('abc-uuid', '2026-03-15', 'S123456789');
    $this->assertSame('work:abc-uuid:2026-03-15:S123456789', $key);
  }

  #[Test]
  public function forensicRedisArchivedKeyInsertsArchivedSegment(): void
  {
    $key = ForensicTaxSupport::redisArchivedWorkKey('abc-uuid', '2026-03-15', 'S123456789');
    $this->assertSame('work:archived:abc-uuid:2026-03-15:S123456789', $key);
  }

  #[Test]
  public function forensicWorkKeyPartsIdentifyDateAndSite(): void
  {
    $key = 'work:member-1:2026-04-09:Sabcdef123';
    $parts = explode(':', $key);
    $this->assertSame('work', $parts[0]);
    $this->assertSame('member-1', $parts[1]);
    $this->assertSame('2026-04-09', $parts[2]);
    $this->assertSame('Sabcdef123', $parts[3]);
  }

  #[Test]
  public function forensicArchivedKeyPartsShiftIndices(): void
  {
    $key = 'work:archived:member-1:2026-04-09:Sabcdef123';
    $parts = explode(':', $key);
    $this->assertTrue(isset($parts[1]) && $parts[1] === 'archived');
    $this->assertSame('2026-04-09', $parts[3]);
    $this->assertSame('Sabcdef123', $parts[4]);
  }

  #[Test]
  public function forensicNormalizeInfersOvertimeFromHoursOverEight(): void
  {
    $normalized = WorkEntry::normalizeWorkEntryPayload(['h' => '10', 'd' => '2026-01-01', 's' => 'S123456789']);
    $this->assertEqualsWithDelta(8.0, (float) $normalized['regular_hours'], 0.0001);
    $this->assertEqualsWithDelta(2.0, (float) $normalized['overtime_hours'], 0.0001);
  }

  #[Test]
  public function forensicNormalizeMapsTxAliasToTax(): void
  {
    $normalized = WorkEntry::normalizeWorkEntryPayload([
      'g' => '500',
      'tx' => '75',
      'd' => '2026-02-01',
      's' => 'S123456789',
    ]);
    $this->assertArrayHasKey('tax', $normalized);
  }

  #[Test]
  public function forensicNormalizeMapsLegacyGrossAlias(): void
  {
    $normalized = WorkEntry::normalizeWorkEntryPayload([
      'g' => '275.50',
      'd' => '2026-02-02',
      's' => 'S123456789',
    ]);
    $this->assertSame('275.50', (string) ($normalized['gross'] ?? ''));
  }

  #[Test]
  public function forensicEmptyWorkProducesEmptySnapshot(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([]);
    $this->assertSame([], $snapshot['years']);
    $this->assertSame([], $snapshot['by_year']);
  }

  #[Test]
  public function forensicSingleEntryPopulatesYearIndex(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2025-06-15', 'gross' => 100.0],
    ]);
    $this->assertSame([2025], $snapshot['years']);
    $this->assertArrayHasKey(2025, $snapshot['by_year']);
  }

  #[Test]
  public function forensicMultipleYearsSortedInSnapshot(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-01-01', 'gross' => 100.0],
      ['date' => '2024-12-31', 'gross' => 200.0],
      ['date' => '2025-06-01', 'gross' => 300.0],
    ]);
    $this->assertSame([2024, 2025, 2026], $snapshot['years']);
  }

  #[Test]
  public function forensicInvalidDateSkippedInAggregation(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '', 'gross' => 999.0],
      ['date' => '2026-03-01', 'gross' => 100.0],
    ]);
    $this->assertEqualsWithDelta(100.0, $snapshot['by_year'][2026]['gross'], 0.01);
  }

  #[Test]
  public function forensicOutOfRangeYearSkipped(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '1999-01-01', 'gross' => 500.0],
      ['date' => '2026-01-01', 'gross' => 100.0],
    ]);
    $this->assertSame([2026], $snapshot['years']);
  }

  #[Test]
  public function forensicRegAndOtHoursRollUpByMonth(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-07-01', 'regular_hours' => 8.0, 'overtime_hours' => 2.0, 'gross' => 300.0],
      ['date' => '2026-07-15', 'regular_hours' => 7.0, 'overtime_hours' => 1.0, 'gross' => 250.0],
    ]);
    $july = $snapshot['by_year'][2026]['months'][0];
    $this->assertEqualsWithDelta(15.0, $july['reg_hours'], 0.01);
    $this->assertEqualsWithDelta(3.0, $july['ot_hours'], 0.01);
  }

  #[Test]
  public function forensicTravelHoursIncludedInTotalHoursField(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-08-01', 'regular_hours' => 8.0, 'travel_hours' => 1.5, 'gross' => 200.0],
    ]);
    $daily = $snapshot['by_year'][2026]['daily_entries']['2026-08-01'];
    $this->assertEqualsWithDelta(9.5, (float) $daily['hours'], 0.01);
  }

  #[Test]
  public function forensicLoaAndWageFormattedInDailyEntries(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-08-02', 'living_out_allowance' => 45.5, 'wage' => 32.75, 'gross' => 307.75],
    ]);
    $daily = $snapshot['by_year'][2026]['daily_entries']['2026-08-02'];
    $this->assertSame('45.50', $daily['living_out_allowance']);
    $this->assertSame('32.75', $daily['wage']);
  }

  #[Test]
  public function forensicMonthLabelFormattedAsMonYear(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-09-20', 'gross' => 100.0],
    ]);
    $month = $snapshot['by_year'][2026]['months'][0];
    $this->assertSame('2026-09', $month['month']);
    $this->assertSame('Sep 2026', $month['label']);
  }

  #[Test]
  public function forensicGrossByDateSortedChronologically(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-10-20', 'gross' => 100.0],
      ['date' => '2026-10-05', 'gross' => 50.0],
      ['date' => '2026-10-15', 'gross' => 75.0],
    ]);
    $dates = array_keys($snapshot['by_year'][2026]['gross_by_date']);
    $this->assertSame(['2026-10-05', '2026-10-15', '2026-10-20'], $dates);
  }

  #[Test]
  public function forensicDailyEntriesSortedByDateKey(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-11-30', 'gross' => 1.0],
      ['date' => '2026-11-01', 'gross' => 2.0],
    ]);
    $keys = array_keys($snapshot['by_year'][2026]['daily_entries']);
    $this->assertSame(['2026-11-01', '2026-11-30'], $keys);
  }

  #[Test]
  #[DataProvider('forensicHoursAliasProvider')]
  public function forensicHoursAliasesAggregateCorrectly(array $entry, float $expectedHours): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([$entry]);
    $daily = $snapshot['by_year'][2026]['daily_entries']['2026-12-01'];
    $this->assertEqualsWithDelta($expectedHours, (float) $daily['hours'], 0.01);
  }

  public static function forensicHoursAliasProvider(): array
  {
    return [
      'explicit hours' => [['date' => '2026-12-01', 'hours' => 10.0, 'gross' => 1.0], 10.0],
      'h alias' => [['date' => '2026-12-01', 'h' => 9.5, 'gross' => 1.0], 9.5],
      'sum r+o+t' => [['date' => '2026-12-01', 'r' => 8.0, 'o' => 1.0, 't' => 0.5, 'gross' => 1.0], 9.5],
    ];
  }

  #[Test]
  public function forensicWorkEntryValidateDateRejectsInvalid(): void
  {
    $this->assertFalse(WorkEntry::validateDate('2026-13-40'));
    $this->assertFalse(WorkEntry::validateDate('not-a-date'));
    $this->assertTrue(WorkEntry::validateDate('2026-06-08'));
  }

  #[Test]
  public function forensicWorkEntryValidateSiteIdRequiresSPrefix(): void
  {
    $this->assertTrue(WorkEntry::validateSiteId('S123456789'));
    $this->assertFalse(WorkEntry::validateSiteId('X123456789'));
    $this->assertFalse(WorkEntry::validateSiteId(''));
  }

  #[Test]
  public function forensicSnapshotRoundsGrossToTwoDecimals(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-01-01', 'gross' => 100.126],
      ['date' => '2026-01-02', 'gross' => 50.004],
    ]);
    $this->assertEqualsWithDelta(150.13, $snapshot['by_year'][2026]['gross'], 0.01);
  }

  #[Test]
  public function forensicTaxAliasTxAggregatesInSnapshot(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-02-01', 'gross' => 200.0, 'tx' => 40.0],
      ['date' => '2026-02-02', 'gross' => 200.0, 'tax' => 30.0],
    ]);
    $feb = ForensicTaxSupport::deriveMemberMonthlyDeductionsGrossMinusNet($snapshot, 2026)['2026-02'];
    $this->assertEqualsWithDelta(70.0, $feb['deductions'], 0.01);
  }

  #[Test]
  public function forensicNetImplicitFromGrossMinusTaxInSnapshot(): void
  {
    $snapshot = ForensicTaxSupport::buildWorkSnapshotFromEntries([
      ['date' => '2026-03-01', 'gross' => 1000.0, 'tax' => 200.0],
    ]);
    $yearData = $snapshot['by_year'][2026];
    $this->assertEqualsWithDelta(800.0, $yearData['net'], 0.01);
  }
}
