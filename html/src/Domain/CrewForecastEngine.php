<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * CrewForecastEngine.php
 *
 * Purpose: Pure-calculation engine that projects labor costs, overtime exposure,
 *          per-diem obligations, and estimated tax for a single worker or a
 *          collection of workers (crew) over a ForecastWindow.
 *
 * Developer notes:
 * - All monetary arithmetic uses integer cents to eliminate float rounding risk.
 * - OT stacking follows the Alberta Employment Standards Act model by default:
 *   daily threshold applied first, weekly cap applied second (OtRule::Both).
 * - Tax estimates are marginal-rate projections only; they are NOT CRA-
 *   authoritative payroll deductions and must be labelled ESTIMATE in the UI.
 * - This class has zero I/O dependencies and must remain stateless and pure.
 *   Do not add Redis, Database, or HTTP calls here.
 * - FlatDay workers receive no OT regardless of hours in the rotation.
 *
 * Architectural role:
 * - Core domain service. Consumed by ForecastController and any API endpoint
 *   that serves crew cost projections.
 * - Encapsulates all forecasting arithmetic outside the HTTP layer.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Stateless crew labor cost forecast calculator.
 *
 * Usage:
 *   $result = CrewForecastEngine::forecastWorker($rateCard, $rotation, $window);
 *   $crew   = CrewForecastEngine::forecastCrew($pairs, $window);
 */
final class CrewForecastEngine
{
  /**
   * Province code → full province name expected by Taxes::__construct().
   *
   * @var array<string, string>
   */
  private const PROVINCE_NAMES = [
    'AB' => 'Alberta',
    'BC' => 'British Columbia',
    'SK' => 'Saskatchewan',
    'MB' => 'Manitoba',
    'ON' => 'Ontario',
    'QC' => 'Quebec',
    'NS' => 'Nova Scotia',
    'NB' => 'New Brunswick',
    'NL' => 'Newfoundland',
    'PE' => 'Prince Edward Island',
    'YT' => 'Yukon',
    'NT' => 'Northwest Territories',
    'NU' => 'Nunavut',
  ];

  /**
   * Forecast labor cost for a single worker over the given window.
   *
   * @param WorkerRateCard   $rateCard The worker's compensation parameters.
   * @param RotationTemplate $rotation The worker's work/rest rotation.
   * @param ForecastWindow   $window   The date range to forecast over.
   *
   * @return ForecastResult Immutable breakdown of projected earnings and tax.
   */
  public static function forecastWorker(
    WorkerRateCard   $rateCard,
    RotationTemplate $rotation,
    ForecastWindow   $window
  ): ForecastResult {
    $workDays = $rotation->workDaysInWindow($window);

    if ($rateCard->rateType === RateType::FlatDay) {
      return self::forecastFlatDay($rateCard, $workDays, $window);
    }

    if ($rateCard->rateType === RateType::Daily) {
      return self::forecastDailyRate($rateCard, $workDays, $window);
    }

    // Hourly: compute regular and OT hours across work days.
    return self::forecastHourly($rateCard, $rotation, $workDays, $window);
  }

  /**
   * Forecast labor costs for a crew (multiple worker + rotation pairs).
   *
   * @param array<array{rateCard: WorkerRateCard, rotation: RotationTemplate}> $crewPairs
   * @param ForecastWindow $window
   *
   * @return ForecastResult Aggregated crew cost breakdown.
   */
  public static function forecastCrew(array $crewPairs, ForecastWindow $window): ForecastResult
  {
    $results = [];
    foreach ($crewPairs as $pair) {
      $results[] = self::forecastWorker($pair['rateCard'], $pair['rotation'], $window);
    }

    if (empty($results)) {
      return new ForecastResult(0.0, 0.0, 0, 0, 0, 0, 0, 0);
    }

    return ForecastResult::aggregate($results);
  }

  // -------------------------------------------------------------------------
  // Private calculation methods
  // -------------------------------------------------------------------------

  /**
   * Forecast for a flat-day-rate worker (no OT, no hourly math).
   */
  private static function forecastFlatDay(
    WorkerRateCard $rateCard,
    int            $workDays,
    ForecastWindow $window
  ): ForecastResult {
    $regularPayCents       = $rateCard->wageRateCents * $workDays;
    $perDiemTotalCents     = $rateCard->perDiemCents * $workDays;
    $sitePremiumTotalCents = 0; // site premium is hourly; not applicable to flat-day
    $estimatedTaxCents     = self::estimateTax($rateCard, $regularPayCents + $perDiemTotalCents, $window);

    return new ForecastResult(
      regularHours:          (float) ($workDays * 8), // 8 h/day convention for display
      otHours:               0.0,
      onsiteDays:            $workDays,
      regularPayCents:       $regularPayCents,
      otPayCents:            0,
      perDiemTotalCents:     $perDiemTotalCents,
      sitePremiumTotalCents: $sitePremiumTotalCents,
      estimatedTaxCents:     $estimatedTaxCents
    );
  }

  /**
   * Forecast for a daily-rate worker (OT based on actual hours exceeding daily/weekly caps).
   */
  private static function forecastDailyRate(
    WorkerRateCard $rateCard,
    int            $workDays,
    ForecastWindow $window
  ): ForecastResult {
    // Treat daily rate as covering one standard work day; no OT by convention.
    $regularPayCents       = $rateCard->wageRateCents * $workDays;
    $perDiemTotalCents     = $rateCard->perDiemCents * $workDays;
    $sitePremiumTotalCents = 0;
    $estimatedTaxCents     = self::estimateTax($rateCard, $regularPayCents + $perDiemTotalCents, $window);

    return new ForecastResult(
      regularHours:          (float) $workDays,
      otHours:               0.0,
      onsiteDays:            $workDays,
      regularPayCents:       $regularPayCents,
      otPayCents:            0,
      perDiemTotalCents:     $perDiemTotalCents,
      sitePremiumTotalCents: $sitePremiumTotalCents,
      estimatedTaxCents:     $estimatedTaxCents
    );
  }

  /**
   * Forecast for an hourly worker, applying daily and/or weekly OT rules.
   *
   * Alberta ESA OT stacking model (OtRule::Both):
   * 1. Cap regular hours at otThresholdDailyHours per day; remainder is daily OT.
   * 2. Sum regular hours across the week; once the weekly regular bucket exceeds
   *    otThresholdWeeklyHours, all further "regular" hours become weekly OT.
   *
   * For OtRule::Daily  — only step 1 applies.
   * For OtRule::Weekly — only step 2 applies.
   * For OtRule::None   — all hours are regular.
   */
  private static function forecastHourly(
    WorkerRateCard   $rateCard,
    RotationTemplate $rotation,
    int              $workDays,
    ForecastWindow   $window
  ): ForecastResult {
    $dailyHours    = $rotation->hoursPerDay;
    $otRule        = $rateCard->otRule;
    $dailyThresh   = $rateCard->otThresholdDailyHours;
    $weeklyThresh  = $rateCard->otThresholdWeeklyHours;

    $totalRegular = 0.0;
    $totalOt      = 0.0;

    // Walk day-by-day through the window, tracking weekly regular accumulation.
    $weeklyRegular = 0.0;
    $current = $window->start->setTime(0, 0, 0);
    $end     = $window->end->setTime(0, 0, 0);
    $dayOfWeek = (int) $current->format('N'); // 1=Mon … 7=Sun

    while ($current < $end) {
      // Reset weekly bucket on Monday.
      if ((int) $current->format('N') === 1 && $current > $window->start->setTime(0, 0, 0)) {
        $weeklyRegular = 0.0;
      }

      if ($rotation->isWorkDay($current)) {
        [$dayRegular, $dayOt] = self::splitHours(
          $dailyHours,
          $otRule,
          $dailyThresh,
          $weeklyThresh,
          $weeklyRegular
        );
        $weeklyRegular += $dayRegular;
        $totalRegular  += $dayRegular;
        $totalOt       += $dayOt;
      }

      $current = $current->modify('+1 day');
    }

    $effectiveHourly    = $rateCard->wageRateCents + $rateCard->sitePremiumCents;
    $regularPayCents    = (int) round($totalRegular * $effectiveHourly);
    $otRatePerHour      = (int) round($effectiveHourly * $rateCard->otMultiplierBasisPoints / 10000);
    $otPayCents         = (int) round($totalOt * $otRatePerHour);

    // Site premium is already folded into effectiveHourly for pay; separate tracking for reporting.
    $sitePremiumTotalCents = (int) round(($totalRegular + $totalOt) * $rateCard->sitePremiumCents);

    $perDiemTotalCents  = $rateCard->perDiemCents * $workDays;
    $grossCents         = $regularPayCents + $otPayCents + $perDiemTotalCents;
    $estimatedTaxCents  = self::estimateTax($rateCard, $grossCents, $window);

    return new ForecastResult(
      regularHours:          $totalRegular,
      otHours:               $totalOt,
      onsiteDays:            $workDays,
      regularPayCents:       $regularPayCents,
      otPayCents:            $otPayCents,
      perDiemTotalCents:     $perDiemTotalCents,
      sitePremiumTotalCents: $sitePremiumTotalCents,
      estimatedTaxCents:     $estimatedTaxCents
    );
  }

  /**
   * Split a single day's hours into regular and overtime buckets.
   *
   * @return array{0: float, 1: float} [regular, ot]
   */
  private static function splitHours(
    float  $dayHours,
    OtRule $rule,
    float  $dailyThresh,
    float  $weeklyThresh,
    float  $weeklyRegularSoFar
  ): array {
    if ($rule === OtRule::None) {
      return [$dayHours, 0.0];
    }

    $regular = $dayHours;
    $ot      = 0.0;

    // Step 1: daily cap.
    if ($rule === OtRule::Daily || $rule === OtRule::Both) {
      if ($regular > $dailyThresh) {
        $ot      = $regular - $dailyThresh;
        $regular = $dailyThresh;
      }
    }

    // Step 2: weekly cap (applied to the "regular" portion that survived step 1).
    if ($rule === OtRule::Weekly || $rule === OtRule::Both) {
      $weeklyCapRemaining = max(0.0, $weeklyThresh - $weeklyRegularSoFar);
      if ($regular > $weeklyCapRemaining) {
        $additionalOt = $regular - $weeklyCapRemaining;
        $regular     -= $additionalOt;
        $ot          += $additionalOt;
      }
    }

    return [$regular, $ot];
  }

  /**
   * Estimate income tax on a gross amount using the existing Taxes domain class.
   *
   * Strategy: annualise the window gross, calculate full-year tax, prorate to
   * window fraction. This approximates marginal-rate tax without requiring
   * exact YTD context.
   *
   * IMPORTANT: result is an ESTIMATE only — not CRA-authoritative.
   *
   * @param WorkerRateCard $rateCard The worker (for province lookup).
   * @param int            $grossCents Gross earnings in cents for the window.
   * @param ForecastWindow $window The forecast window (used for annualisation).
   *
   * @return int Estimated total deductions in cents.
   */
  private static function estimateTax(
    WorkerRateCard $rateCard,
    int            $grossCents,
    ForecastWindow $window
  ): int {
    if ($grossCents <= 0) {
      return 0;
    }

    $windowDays    = max(1, $window->days());
    $annualCents   = (int) round($grossCents * (365.0 / $windowDays));
    $provinceName  = self::PROVINCE_NAMES[$rateCard->taxRegion] ?? 'Alberta';

    $taxes           = new Taxes($provinceName);
    $annualTaxData   = $taxes->calculateTaxesCents($annualCents);
    $annualTotal     = $annualTaxData['totalDeductions'];

    // Prorate annual tax back to the window fraction.
    return (int) round($annualTotal * ($windowDays / 365.0));
  }
}
