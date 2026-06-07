<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\CrewForecastEngine;
use PayCal\Domain\ForecastResult;
use PayCal\Domain\ForecastWindow;
use PayCal\Domain\ForecastWindowType;
use PayCal\Domain\InvalidArgumentException;
use PayCal\Domain\OtRule;
use PayCal\Domain\RateType;
use PayCal\Domain\RotationTemplate;
use PayCal\Domain\WorkerRateCard;

/**
 * CrewForecastEngineTest.php
 *
 * Purpose: Comprehensive unit tests for the Crew Forecasting domain layer
 *          (ForecastWindow, WorkerRateCard, RotationTemplate, ForecastResult,
 *          and CrewForecastEngine) using representative Alberta O&G scenarios.
 *
 * These tests have zero I/O dependencies — no Redis, no Database, no config.php.
 * All scenarios are derived from the Crew Forecasting action plan (2026-05-27).
 *
 * Covered scenarios:
 * - 14/7 welder  @ $52/hr, daily 1.5× OT after 8 h/day, 30-day window
 * - 20/10 operator @ $48/hr, daily + weekly OT, 30-day window
 * - 14/14 safety @ $650/day flat rate, 30-day window
 * - Crew aggregate (all three workers combined)
 * - ForecastWindow named constructors and validation
 * - WorkerRateCard validation guards
 * - RotationTemplate cycle math and isWorkDay
 * - ForecastResult aggregation
 *
 * @internal
 */
#[Group('unit')]
final class CrewForecastEngineTest extends TestCase
{
  // =========================================================================
  // Helpers
  // =========================================================================

  /** Monday 2026-01-05 — first day of a 14/7 work block in our test anchor. */
  private function anchor(): \DateTimeImmutable
  {
    return new \DateTimeImmutable('2026-01-05');
  }

  private function window30(): ForecastWindow
  {
    return ForecastWindow::next30Days($this->anchor());
  }

  // =========================================================================
  // ForecastWindow
  // =========================================================================

  #[Test]
  public function forecastWindowNextPayPeriodHas14Days(): void
  {
    $w = ForecastWindow::nextPayPeriod($this->anchor(), 14);
    $this->assertSame(14, $w->days());
    $this->assertSame(ForecastWindowType::NextPayPeriod, $w->type);
  }

  #[Test]
  public function forecastWindowNext30DaysHas30Days(): void
  {
    $this->assertSame(30, $this->window30()->days());
  }

  #[Test]
  public function forecastWindowQuarterHas91Days(): void
  {
    $w = ForecastWindow::quarter($this->anchor());
    $this->assertSame(91, $w->days());
  }

  #[Test]
  public function forecastWindowProjectTotalUsesExplicitEnd(): void
  {
    $start = new \DateTimeImmutable('2026-01-01');
    $end   = new \DateTimeImmutable('2026-06-30');
    $w     = ForecastWindow::projectTotal($start, $end);
    $this->assertSame(ForecastWindowType::ProjectTotal, $w->type);
    $this->assertSame(180, $w->days());
  }

  #[Test]
  public function forecastWindowRejectsEndBeforeStart(): void
  {
    $this->expectException(InvalidArgumentException::class);
    ForecastWindow::projectTotal(
      new \DateTimeImmutable('2026-06-01'),
      new \DateTimeImmutable('2026-01-01')
    );
  }

  #[Test]
  public function forecastWindowLabelIsHumanReadable(): void
  {
    $this->assertSame('Next 30 Days', $this->window30()->label());
    $this->assertSame('Quarter', ForecastWindow::quarter($this->anchor())->label());
  }

  // =========================================================================
  // WorkerRateCard
  // =========================================================================

  #[Test]
  public function workerRateCardDefaultsToAlbertaBothOt(): void
  {
    $card = new WorkerRateCard(5200); // $52/hr in cents
    $this->assertSame('AB', $card->taxRegion);
    $this->assertSame(OtRule::Both, $card->otRule);
    $this->assertSame(RateType::Hourly, $card->rateType);
    $this->assertSame(15000, $card->otMultiplierBasisPoints);
  }

  #[Test]
  public function workerRateCardRejectsNegativeWage(): void
  {
    $this->expectException(InvalidArgumentException::class);
    new WorkerRateCard(-100);
  }

  #[Test]
  public function workerRateCardRejectsSubOneMultiplier(): void
  {
    $this->expectException(InvalidArgumentException::class);
    new WorkerRateCard(5200, otMultiplierBasisPoints: 9000); // < 10000 (1.0×)
  }

  #[Test]
  public function workerRateCardEffectiveHourlyIncludesSitePremium(): void
  {
    $card = new WorkerRateCard(5200, sitePremiumCents: 200);
    $this->assertSame(5400, $card->effectiveHourlyRateCents());
  }

  #[Test]
  public function workerRateCardFlatDayReturnsNullForEffectiveHourly(): void
  {
    $card = new WorkerRateCard(65000, rateType: RateType::FlatDay);
    $this->assertNull($card->effectiveHourlyRateCents());
  }

  // =========================================================================
  // RotationTemplate
  // =========================================================================

  #[Test]
  public function rotationTemplate14x7CycleIs21Days(): void
  {
    $r = RotationTemplate::fromPattern('14/7', $this->anchor());
    $this->assertSame(21, $r->cycleDays());
    $this->assertSame(8.0, $r->hoursPerDay);
    $this->assertSame('14/7', $r->label);
  }

  #[Test]
  public function rotationTemplate20x10CycleIs30Days(): void
  {
    $r = RotationTemplate::fromPattern('20/10', $this->anchor());
    $this->assertSame(30, $r->cycleDays());
    $this->assertSame(10.0, $r->hoursPerDay);
  }

  #[Test]
  public function rotationTemplateAnchorDayIsWorkDay(): void
  {
    $r = RotationTemplate::fromPattern('14/7', $this->anchor());
    $this->assertTrue($r->isWorkDay($this->anchor()));
  }

  #[Test]
  public function rotationTemplateDayAfterWorkBlockIsRestDay(): void
  {
    // Anchor = 2026-01-05; 14 work days → first rest day = 2026-01-19.
    $r        = RotationTemplate::fromPattern('14/7', $this->anchor());
    $restDay  = new \DateTimeImmutable('2026-01-19');
    $this->assertFalse($r->isWorkDay($restDay));
  }

  #[Test]
  public function rotationTemplate14x7WorkDaysIn30DayWindow(): void
  {
    // 14/7 cycle = 21 days. In 30 days from anchor (day 0–29):
    // Cycle 1: days 0–13 work (14), days 14–20 rest (7) = 14 work days in first 21.
    // Days 21–29: days 21–29 are in next work block (9 more work days).
    // Total = 23 work days.
    $r = RotationTemplate::fromPattern('14/7', $this->anchor());
    $this->assertSame(23, $r->workDaysInWindow($this->window30()));
  }

  #[Test]
  public function rotationTemplate20x10WorkDaysIn30DayWindow(): void
  {
    // 20/10 cycle = 30 days. In 30 days from anchor: days 0–19 work = 20 work days.
    $r = RotationTemplate::fromPattern('20/10', $this->anchor());
    $this->assertSame(20, $r->workDaysInWindow($this->window30()));
  }

  #[Test]
  public function rotationTemplateRejectsUnknownPattern(): void
  {
    $this->expectException(InvalidArgumentException::class);
    RotationTemplate::fromPattern('99/1', $this->anchor());
  }

  // =========================================================================
  // CrewForecastEngine — flat-day worker (14/14 safety)
  // =========================================================================

  #[Test]
  public function forecastFlatDaySafetyIn30Days(): void
  {
    // Safety: $650/day flat rate, 14/14 rotation → 14 work days in first 28 of 30.
    // Window = 30 days. 14/14 cycle = 28 days.
    // Days 0–13 work, 14–27 rest, 28–29 work (2 days in second cycle).
    // Total work days = 14 + 2 = 16.
    $card     = new WorkerRateCard(65000, rateType: RateType::FlatDay);
    $rotation = RotationTemplate::fromPattern('14/14', $this->anchor());
    $window   = $this->window30();

    $result = CrewForecastEngine::forecastWorker($card, $rotation, $window);

    $expectedWorkDays       = $rotation->workDaysInWindow($window);
    $expectedRegularPayCents = 65000 * $expectedWorkDays;

    $this->assertSame($expectedWorkDays, $result->onsiteDays);
    $this->assertSame($expectedRegularPayCents, $result->regularPayCents);
    $this->assertSame(0, $result->otPayCents);
    $this->assertSame(0.0, $result->otHours);
    $this->assertSame(0, $result->perDiemTotalCents);
  }

  #[Test]
  public function forecastFlatDayWithPerDiem(): void
  {
    // $650/day + $75/day per diem.
    $card     = new WorkerRateCard(65000, rateType: RateType::FlatDay, perDiemCents: 7500);
    $rotation = RotationTemplate::fromPattern('14/14', $this->anchor());
    $window   = $this->window30();

    $result        = CrewForecastEngine::forecastWorker($card, $rotation, $window);
    $workDays      = $rotation->workDaysInWindow($window);
    $expectedPerDiem = 7500 * $workDays;

    $this->assertSame($expectedPerDiem, $result->perDiemTotalCents);
    $this->assertSame(65000 * $workDays + $expectedPerDiem, $result->estimatedGrossCents);
  }

  // =========================================================================
  // CrewForecastEngine — hourly worker, daily OT only (welder 14/7)
  // =========================================================================

  #[Test]
  public function forecastHourlyWelderDailyOtIn30Days(): void
  {
    // Welder: $52/hr = 5200 cents, 14/7 rotation (8 h/day), daily OT after 8 h.
    // 8 h/day = daily threshold → no OT hours (exactly at threshold).
    $card     = new WorkerRateCard(5200, otRule: OtRule::Daily);
    $rotation = RotationTemplate::fromPattern('14/7', $this->anchor()); // 8 h/day
    $window   = $this->window30();

    $result   = CrewForecastEngine::forecastWorker($card, $rotation, $window);
    $workDays = $rotation->workDaysInWindow($window); // 23

    $expectedRegularHours = (float) ($workDays * 8);
    $expectedRegularPay   = (int) round($expectedRegularHours * 5200);

    $this->assertSame($expectedRegularHours, $result->regularHours);
    $this->assertSame(0.0, $result->otHours);
    $this->assertSame($expectedRegularPay, $result->regularPayCents);
    $this->assertSame(0, $result->otPayCents);
  }

  #[Test]
  public function forecastHourlyWithOtWhenDailyHoursExceedThreshold(): void
  {
    // Worker at 10 h/day with daily OT after 8 h → 2 OT hours/day.
    $card     = new WorkerRateCard(5200, otRule: OtRule::Daily);
    $rotation = new RotationTemplate(14, 7, 10.0, $this->anchor(), '14/7-10h');
    $window   = $this->window30();

    $result   = CrewForecastEngine::forecastWorker($card, $rotation, $window);
    $workDays = $rotation->workDaysInWindow($window); // 23

    $this->assertSame((float) ($workDays * 8), $result->regularHours);
    $this->assertSame((float) ($workDays * 2), $result->otHours);
    $this->assertGreaterThan($result->regularPayCents, $result->estimatedGrossCents);
  }

  // =========================================================================
  // CrewForecastEngine — hourly worker, both OT rules (operator 20/10)
  // =========================================================================

  #[Test]
  public function forecastHourlyOperatorBothOtIn30Days(): void
  {
    // Operator: $48/hr = 4800 cents, 20/10 rotation (10 h/day), daily+weekly OT.
    // The 20-day work block spans 3 calendar weeks (anchor = Monday 2026-01-05):
    //   Week 1 (Jan 5-11, 7 work days): Mon–Fri = 40h weekly regular; Sat = 4h regular
    //     + 4h weekly OT + 2h daily OT; Sun = 0h regular + 8h weekly OT + 2h daily OT.
    //     Week 1 regular = 44h, OT = 26h.
    //   Week 2 (Jan 12-18, 7 work days): same pattern → regular = 44h, OT = 26h.
    //   Week 3 (Jan 19-24, 6 work days Mon–Sat): Mon–Fri = 40h regular + 10h daily OT;
    //     Sat = 4h regular + 6h OT (4h weekly + 2h daily).
    //     Week 3 regular = 44h, OT = 16h.
    // Totals: regular = 132h, OT = 68h, total hours = 200 (20 days × 10h ✓).
    $card     = new WorkerRateCard(4800, otRule: OtRule::Both);
    $rotation = RotationTemplate::fromPattern('20/10', $this->anchor()); // 10 h/day
    $window   = $this->window30();

    $result = CrewForecastEngine::forecastWorker($card, $rotation, $window);

    $this->assertSame(132.0, $result->regularHours);
    $this->assertSame(68.0, $result->otHours);

    $effectiveHourly = 4800;
    $otRate          = (int) round($effectiveHourly * 15000 / 10000); // 7200
    $expectedRegPay  = (int) round(132.0 * $effectiveHourly);        // 633600
    $expectedOtPay   = (int) round(68.0 * $otRate);                  // 489600

    $this->assertSame($expectedRegPay, $result->regularPayCents);
    $this->assertSame($expectedOtPay, $result->otPayCents);
  }

  #[Test]
  public function forecastWeeklyOtTriggersAfter44RegularHours(): void
  {
    // Worker: 9 h/day, OtRule::Weekly (no daily cap), weekly thresh = 44.
    // Days 1–4: 9 h/day = 36 h regular. Day 5: 8 h regular (44−36), 1 h OT.
    // Day 6+: all OT under weekly rule.
    $card = new WorkerRateCard(
      5000,
      otRule:                  OtRule::Weekly,
      otThresholdWeeklyHours:  44.0
    );
    // Simple 7-on / 0-off rotation to keep 7 consecutive work days in the window.
    $rotation = new RotationTemplate(7, 1, 9.0, $this->anchor(), '7/1');
    $window   = ForecastWindow::nextPayPeriod($this->anchor(), 7);

    $result = CrewForecastEngine::forecastWorker($card, $rotation, $window);

    // 7 days × 9 h = 63 total hours. Regular = 44; OT = 19.
    $this->assertSame(44.0, $result->regularHours);
    $this->assertSame(19.0, $result->otHours);
  }

  // =========================================================================
  // CrewForecastEngine — crew aggregate
  // =========================================================================

  #[Test]
  public function forecastCrewAggregatesThreeWorkers(): void
  {
    $anchor = $this->anchor();
    $window = $this->window30();

    $welder = [
      'rateCard' => new WorkerRateCard(5200, otRule: OtRule::Daily),
      'rotation' => RotationTemplate::fromPattern('14/7', $anchor),
    ];
    $operator = [
      'rateCard' => new WorkerRateCard(4800, otRule: OtRule::Both),
      'rotation' => RotationTemplate::fromPattern('20/10', $anchor),
    ];
    $safety = [
      'rateCard' => new WorkerRateCard(65000, rateType: RateType::FlatDay),
      'rotation' => RotationTemplate::fromPattern('14/14', $anchor),
    ];

    $crew   = CrewForecastEngine::forecastCrew([$welder, $operator, $safety], $window);
    $ind1   = CrewForecastEngine::forecastWorker($welder['rateCard'], $welder['rotation'], $window);
    $ind2   = CrewForecastEngine::forecastWorker($operator['rateCard'], $operator['rotation'], $window);
    $ind3   = CrewForecastEngine::forecastWorker($safety['rateCard'], $safety['rotation'], $window);

    $this->assertSame(
      $ind1->estimatedGrossCents + $ind2->estimatedGrossCents + $ind3->estimatedGrossCents,
      $crew->estimatedGrossCents
    );
    $this->assertSame(
      $ind1->otHours + $ind2->otHours + $ind3->otHours,
      $crew->otHours
    );
  }

  #[Test]
  public function forecastEmptyCrewReturnsZeroResult(): void
  {
    $result = CrewForecastEngine::forecastCrew([], $this->window30());
    $this->assertSame(0, $result->estimatedGrossCents);
    $this->assertSame(0.0, $result->otHours);
    $this->assertSame(0, $result->estimatedTaxCents);
  }

  // =========================================================================
  // ForecastResult
  // =========================================================================

  #[Test]
  public function forecastResultGrossIsSumOfComponents(): void
  {
    $r = new ForecastResult(80.0, 10.0, 10, 416000, 78000, 10000, 5000, 80000);
    // gross = 416000 + 78000 + 10000 + 5000 = 509000
    $this->assertSame(509000, $r->estimatedGrossCents);
  }

  #[Test]
  public function forecastResultNetIsGrossMinusTax(): void
  {
    $r = new ForecastResult(80.0, 0.0, 10, 400000, 0, 0, 0, 80000);
    $this->assertSame(320000, $r->estimatedNetCents);
  }

  #[Test]
  public function forecastResultNetClampsToZeroWhenTaxExceedsGross(): void
  {
    $r = new ForecastResult(1.0, 0.0, 1, 100, 0, 0, 0, 9999999);
    $this->assertSame(0, $r->estimatedNetCents);
  }

  #[Test]
  public function forecastResultDollarConversionsAreCorrect(): void
  {
    $r = new ForecastResult(0.0, 0.0, 0, 500000, 0, 0, 0, 100000);
    $this->assertEqualsWithDelta(5000.0, $r->estimatedGrossDollars(), 0.001);
    $this->assertEqualsWithDelta(4000.0, $r->estimatedNetDollars(), 0.001);
  }

  #[Test]
  public function forecastResultAggregateIsCorrect(): void
  {
    $a = new ForecastResult(40.0, 5.0, 5, 200000, 36000, 5000, 2000, 40000);
    $b = new ForecastResult(40.0, 5.0, 5, 200000, 36000, 5000, 2000, 40000);
    $agg = ForecastResult::aggregate([$a, $b]);

    $this->assertSame(80.0, $agg->regularHours);
    $this->assertSame(10.0, $agg->otHours);
    $this->assertSame(10, $agg->onsiteDays);
    $this->assertSame(400000, $agg->regularPayCents);
    $this->assertSame(72000, $agg->otPayCents);
    $this->assertSame(80000, $agg->estimatedTaxCents);
  }

  // =========================================================================
  // Tax estimation sanity checks
  // =========================================================================

  #[Test]
  public function forecastTaxEstimateIsPositiveForHighEarner(): void
  {
    // Welder earning ~$18,000/month should have non-trivial tax estimate.
    $card     = new WorkerRateCard(5200, otRule: OtRule::Daily);
    $rotation = RotationTemplate::fromPattern('14/7', $this->anchor());
    $window   = $this->window30();

    $result = CrewForecastEngine::forecastWorker($card, $rotation, $window);

    $this->assertGreaterThan(0, $result->estimatedTaxCents);
    $this->assertGreaterThan($result->estimatedTaxCents, $result->estimatedGrossCents);
  }

  #[Test]
  public function forecastEstimatedNetIsLessThanGross(): void
  {
    $card     = new WorkerRateCard(4800, otRule: OtRule::Both);
    $rotation = RotationTemplate::fromPattern('20/10', $this->anchor());
    $window   = $this->window30();

    $result = CrewForecastEngine::forecastWorker($card, $rotation, $window);

    $this->assertLessThan($result->estimatedGrossCents, $result->estimatedNetCents);
  }
}
