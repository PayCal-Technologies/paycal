<?php declare(strict_types=1);

use PayCal\Domain\OvertimeRules;
use PayCal\Domain\PublicPayTools;
use PayCal\Domain\RotationPayExplainer;
use PayCal\Domain\StatHolidayEngine;
use PayCal\Domain\VacationPayEngine;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class PayRulesEngineTest extends TestCase
{
  #[Test]
  public function albertaUsesTheGreaterOfDailyAndWeeklyOvertime(): void
  {
    $oneLongDay = OvertimeRules::allocateWeek([12, 0, 0, 0, 0, 0, 0], 'AB');
    $this->assertSame(8.0, $oneLongDay['regular']);
    $this->assertSame(4.0, $oneLongDay['overtime']);

    $sevenEights = OvertimeRules::allocateWeek([8, 8, 8, 8, 8, 8, 8], 'Alberta');
    $this->assertSame(44.0, $sevenEights['regular']);
    $this->assertSame(12.0, $sevenEights['overtime']);
  }

  #[Test]
  public function ontarioHasNoDailyOvertime(): void
  {
    $week = OvertimeRules::allocateWeek([10, 10, 10, 10, 10, 0, 0], 'ON');
    $this->assertSame(44.0, $week['regular']);
    $this->assertSame(6.0, $week['overtime']);
    $this->assertSame(0.0, $week['doubletime']);
  }

  #[Test]
  public function britishColumbiaPaysDoubleTimeAfterTwelveHours(): void
  {
    $day = OvertimeRules::allocateWeek([13, 0, 0, 0, 0, 0, 0], 'BC');
    $this->assertSame(8.0, $day['regular']);
    $this->assertSame(4.0, $day['overtime']);
    $this->assertSame(1.0, $day['doubletime']);
  }

  #[Test]
  public function saskatchewanReducesTheWeeklyThresholdInAHolidayWeek(): void
  {
    $normal = OvertimeRules::allocateWeek([8, 8, 8, 8, 8, 0, 0], 'SK');
    $holiday = OvertimeRules::allocateWeek([8, 8, 8, 8, 8, 0, 0], 'SK', 1);
    $this->assertSame(40.0, $normal['regular']);
    $this->assertSame(0.0, $normal['overtime']);
    $this->assertSame(32.0, $holiday['regular']);
    $this->assertSame(8.0, $holiday['overtime']);
  }

  #[Test]
  public function everyJurisdictionHasASourceAndAnEffectiveDate(): void
  {
    foreach (OvertimeRules::jurisdictionCodes() as $code) {
      $rule = OvertimeRules::forCode($code);
      $this->assertNotSame('', (string) $rule['source_url'], $code);
      $this->assertSame('2026-10-20', $rule['reviewed_on'], $code);
      $this->assertGreaterThan(0, (float) $rule['multiplier'], $code);
    }
    $this->assertCount(14, OvertimeRules::jurisdictionCodes());
  }

  #[Test]
  public function vacationTiersMatchTheCheckedOfficialRules(): void
  {
    $alberta = VacationPayEngine::tier('AB', 4, 2026);
    $this->assertSame('4%', $alberta['label']);
    $this->assertSame('6%', VacationPayEngine::tier('AB', 5, 2026)['label']);
    $this->assertSame(400, VacationPayEngine::accrualCents('AB', 10000, 1, 2026));

    $saskatchewan = VacationPayEngine::tier('SK', 9, 2026);
    $this->assertSame('3/52', $saskatchewan['label']);
    $this->assertSame(300, VacationPayEngine::accrualCents('SK', 5200, 1, 2026));
    $this->assertSame('4/52', VacationPayEngine::tier('SK', 10, 2026)['label']);

    $this->assertSame('6%', VacationPayEngine::tier('QC', 3, 2026)['label']);
    $this->assertSame('8%', VacationPayEngine::tier('CA', 10, 2026)['label']);
    $this->assertTrue(VacationPayEngine::rule('CA', 2026)['verified']);
    $this->assertSame('4%', VacationPayEngine::tier('YT', 20, 2026)['label']);
    $this->assertSame('4%', VacationPayEngine::tier('NS', 6.99, 2026)['label']);
    $this->assertSame('6%', VacationPayEngine::tier('NS', 7, 2026)['label']);
    $this->assertSame('4%', VacationPayEngine::tier('PE', 4.99, 2026)['label']);
    $this->assertSame('6%', VacationPayEngine::tier('PE', 5, 2026)['label']);
    $this->assertSame('6%', VacationPayEngine::tier('PE', 5.01, 2026)['label']);
    $this->assertSame('4%', VacationPayEngine::tier('PE', 6, 2025)['label']);
    $this->assertTrue(VacationPayEngine::rule('ON', 2026)['verified']);
  }

  #[Test]
  public function reviewedOvertimeReferencesUseCurrentThresholdsAndRateBases(): void
  {
    $this->assertSame(44, OvertimeRules::forCode('PE')['weekly_hours']);
    foreach (['NB', 'NL'] as $code) {
      $this->assertSame('minimum_wage', OvertimeRules::forCode($code)['rate_basis']);
      $this->assertStringNotContainsString('times the regular rate', OvertimeRules::summary($code));
    }
    $cheque = PublicPayTools::nextCheque(['province' => 'MB', 'wage' => 25, 'regular_hours' => 40, 'overtime_hours' => 10, 'year' => 2026]);
    $this->assertSame(4000, $cheque['vacation_pay_cents']);
  }

  #[Test]
  public function statutoryHolidayPayUsesTheCheckedFormulas(): void
  {
    $this->assertSame(20000, StatHolidayEngine::holidayPayCents('AB', 400000, 20));
    $this->assertSame(17000, StatHolidayEngine::holidayPayCents('SK', 340000, 0));
    $this->assertSame(5000, StatHolidayEngine::holidayPayCents('ON', 100000, 0));
    $this->assertSame(50000, StatHolidayEngine::workedHolidayCents('AB', 20000, 8, 2500));
    $this->assertTrue(StatHolidayEngine::isHoliday('BC', '2026-08-03'));
    $this->assertTrue(StatHolidayEngine::isHoliday('SK', '2026-08-03'));
    $this->assertCount(9, StatHolidayEngine::holidays('MB', 2026));
    $this->assertNotSame('', (string) StatHolidayEngine::rule('MB')['source_url']);
  }

  #[Test]
  public function nextChequeUsesTheTaxEngineAndKeepsVacationSeparate(): void
  {
    $result = PublicPayTools::nextCheque([
      'province' => 'AB',
      'year' => 2026,
      'periods' => 26,
      'wage' => 40,
      'regular_hours' => 80,
      'overtime_hours' => 0,
      'travel_hours' => 0,
      'allowance' => 0,
      'years_of_service' => 1,
    ]);

    $this->assertSame(320000, $result['gross_cents']);
    $this->assertSame(12800, $result['vacation_pay_cents']);
    $this->assertGreaterThan(0, $result['tax_cents']);
    $this->assertSame($result['gross_cents'] - $result['tax_cents'], $result['net_cents']);
    $this->assertArrayHasKey('federal', $result['deductions']);
  }

  #[Test]
  public function rotationExampleUsesTheAlbertaRuleAndLabelsItselfAsAnExample(): void
  {
    $comparison = RotationPayExplainer::comparison();
    $this->assertSame('Example', $comparison['label']);
    $short = $comparison['rotations']['14/7'];
    $long = $comparison['rotations']['20/10'];
    $this->assertGreaterThan(0, $short['onsite_days']);
    $this->assertGreaterThan($short['overtime_hours'], $long['overtime_hours']);
    $this->assertSame($short['gross_cents'] - $short['estimated_tax_cents'], $short['estimated_net_cents']);
    $this->assertGreaterThan(0, $short['vacation_accrual_cents']);
    $this->assertStringContainsString('example', strtolower(implode(' ', $comparison['assumptions'])));
  }
  #[Test]
  public function doubleTimeSurvivesNormalizationAndSnapshots(): void
  {
    $row = \PayCal\Domain\WorkEntry::normalizeWorkEntryPayload(['hours' => 13, 'regular_hours' => 8, 'overtime_hours' => 5, 'doubletime_hours' => 1, 'doubletime_amount' => 80]);
    $this->assertSame(1, $row['doubletime_hours']);
    $this->assertSame(80, $row['doubletime_amount']);
    $money = \PayCal\Domain\WorkEntry::calculateEarningsSnapshot(8, 5, 0, 0, 40, 0, 1);
    $this->assertSame(640.0, $money['gross']);
    $this->assertSame(320.0, $money['overtime_amount']);
  }

  #[Test]
  public function forecastsUseJurisdictionRulesAndWholeWeeks(): void
  {
    class_exists(\PayCal\Domain\WorkerRateCard::class);
    $start = new \DateTimeImmutable('2026-10-04');
    $rotation = new \PayCal\Domain\RotationTemplate(14, 7, 13, $start);
    $card = new \PayCal\Domain\WorkerRateCard(4000, otRule: \PayCal\Domain\OtRule::Jurisdiction, taxRegion: 'BC');
    $day = \PayCal\Domain\CrewForecastEngine::forecastWorker($card, $rotation, \PayCal\Domain\ForecastWindow::projectTotal($start, $start->modify('+1 day')));
    $this->assertSame(8.0, $day->regularHours);
    $this->assertSame(5.0, $day->otHours);
    $this->assertSame(64000, $day->estimatedGrossCents);
    $alberta = new \PayCal\Domain\WorkerRateCard(4000, otRule: \PayCal\Domain\OtRule::Jurisdiction, taxRegion: 'AB');
    $eights = new \PayCal\Domain\RotationTemplate(14, 7, 8, $start);
    $saturday = $start->modify('+6 days');
    $lastDay = \PayCal\Domain\CrewForecastEngine::forecastWorker($alberta, $eights, \PayCal\Domain\ForecastWindow::projectTotal($saturday, $saturday->modify('+1 day')));
    $this->assertSame(0.0, $lastDay->regularHours);
    $this->assertSame(8.0, $lastDay->otHours);
    $whole = \PayCal\Domain\CrewForecastEngine::forecastWorker($alberta, $eights, \PayCal\Domain\ForecastWindow::projectTotal($start, $start->modify('+7 days')));
    $this->assertSame(44.0, $whole->regularHours);
    $this->assertSame(12.0, $whole->otHours);
  }

  #[Test]
  public function saskatchewanHolidayWorkDoesNotConsumeTheWeeklyThreshold(): void
  {
    $week = OvertimeRules::allocateDatedWeek([0, 8, 8, 8, 8, 8, 0], 'SK', new \DateTimeImmutable('2026-10-11'));
    $this->assertSame(32.0, $week['regular']);
    $this->assertSame(8.0, $week['overtime']);
    $this->assertSame(8.0, $week['daily'][1]['overtime']);
    $this->assertSame(8.0, $week['daily'][5]['regular']);
  }
  #[Test]
  public function everyCurrentJurisdictionHasReviewedRulesAndHolidayNames(): void
  {
    $counts = ['CA' => 10, 'AB' => 9, 'BC' => 11, 'MB' => 9, 'NB' => 8, 'NL' => 6, 'NS' => 6, 'NT' => 11, 'NU' => 11, 'ON' => 9, 'PE' => 8, 'QC' => 8, 'SK' => 10, 'YT' => 11];
    foreach ($counts as $code => $count) {
      $this->assertTrue(OvertimeRules::forCode($code)['verified'], $code);
      $this->assertTrue(VacationPayEngine::rule($code, 2026)['verified'], $code);
      $this->assertTrue(StatHolidayEngine::rule($code)['verified'], $code);
      $this->assertCount($count, StatHolidayEngine::holidays($code, 2026), $code);
    }
    $this->assertSame(20000, StatHolidayEngine::holidayPayCents('MB', 400000));
    $this->assertSame(18000, StatHolidayEngine::holidayPayCents('MB', 400000, 0, 18000));
    $this->assertSame(20000, StatHolidayEngine::holidayPayCents('YT', 200000));
    $this->assertSame(40000, StatHolidayEngine::workedHolidayCents('QC', 20000, 8, 2500));
    $this->assertSame(40000, StatHolidayEngine::workedHolidayCents('NL', 20000, 8, 2500));
    foreach (['NT', 'NU', 'YT', 'CA'] as $code) {
      $week = OvertimeRules::allocateDatedWeek([0, 0, 8, 8, 8, 8, 8], $code, new \DateTimeImmutable('2026-10-11'), ['2026-10-12']);
      $this->assertSame(32.0, $week['regular'], $code);
      $this->assertSame(8.0, $week['overtime'], $code);
    }
  }

  #[Test]
  public function minimumWageOvertimeFloorsAndPeiChangeUseTheirEffectiveDates(): void
  {
    $date = new \DateTimeImmutable('2026-10-20');
    $this->assertSame(2385, OvertimeRules::hourlyOvertimeRateCents('NB', 2000, $date));
    $this->assertSame(3000, OvertimeRules::hourlyOvertimeRateCents('NB', 3000, $date));
    $this->assertSame(2453, OvertimeRules::hourlyOvertimeRateCents('NL', 1635, $date));
    $this->assertSame(2400, OvertimeRules::hourlyOvertimeRateCents('NL', 1600, new \DateTimeImmutable('2026-03-31')));
    $this->assertSame(48.0, OvertimeRules::allocateWeek([10, 10, 10, 10, 10, 0, 0], 'PE', 0, new \DateTimeImmutable('2026-06-01'))['regular']);
    $this->assertSame(44.0, OvertimeRules::allocateWeek([10, 10, 10, 10, 10, 0, 0], 'PE', 0, $date)['regular']);
    $snapshot = \PayCal\Domain\WorkEntry::calculateEarningsSnapshot(44, 6, 0, 0, 20, 0, 0, 23.85);
    $this->assertSame(1023.1, $snapshot['gross']);
  }
}
