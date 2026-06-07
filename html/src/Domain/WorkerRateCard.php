<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * WorkerRateCard.php
 *
 * Purpose: Immutable value object representing a single worker's compensation
 *          parameters used as the atomic input unit for crew forecasting.
 *
 * Developer notes:
 * - All monetary values are stored in cents (integer) to avoid float rounding.
 * - OT rules model Alberta Employment Standards Act defaults; union_code is a
 *   free-form label only — full CBA logic is out of scope for v1.
 * - This class has zero I/O dependencies and must remain a pure value object.
 *
 * Architectural role:
 * - Domain value object consumed by CrewForecastEngine.
 * - Encapsulates per-worker compensation contract outside the HTTP/persistence layers.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * How the base wage is denominated.
 */
enum RateType: string
{
  case Hourly  = 'hourly';
  case Daily   = 'daily';
  case FlatDay = 'flat_day'; // fixed day-rate, no OT applies
}

/**
 * Which overtime rule governs this worker.
 *
 * Alberta ESA defaults:
 * - Daily:  1.5× after 8 h/day
 * - Weekly: 1.5× after 44 h/week
 * - Both:   daily threshold applied first, weekly cap applied second
 * - None:   salaried / flat-rate with no OT
 */
enum OtRule: string
{
  case Daily   = 'daily';
  case Weekly  = 'weekly';
  case Both    = 'both';
  case None    = 'none';
}

/**
 * Immutable compensation parameters for a single worker.
 */
final class WorkerRateCard
{
  /** Base wage in cents per hour (or per day when rateType is Daily/FlatDay). */
  public readonly int $wageRateCents;

  /** How the wage is denominated. */
  public readonly RateType $rateType;

  /** Which OT rule applies. Ignored when rateType is FlatDay. */
  public readonly OtRule $otRule;

  /** Daily OT threshold in hours (default 8). */
  public readonly float $otThresholdDailyHours;

  /** Weekly OT threshold in hours (default 44, Alberta ESA). */
  public readonly float $otThresholdWeeklyHours;

  /** OT pay multiplier expressed as basis points (10000 = 1.0×, so 15000 = 1.5×). */
  public readonly int $otMultiplierBasisPoints;

  /** Per diem in cents per calendar day the worker is on-site. */
  public readonly int $perDiemCents;

  /** Site premium in cents per hour (added to base for OT calculation base). */
  public readonly int $sitePremiumCents;

  /** Two-letter province code for tax estimation (e.g. 'AB'). */
  public readonly string $taxRegion;

  /** Free-form union/trade label; not used in v1 calculations. */
  public readonly string $unionCode;

  /**
   * @param int    $wageRateCents            Base wage in cents (per hour or per day).
   * @param RateType $rateType               How the wage is denominated.
   * @param OtRule $otRule                   OT rule governing this worker.
   * @param float  $otThresholdDailyHours    Daily OT threshold (default 8.0).
   * @param float  $otThresholdWeeklyHours   Weekly OT threshold (default 44.0).
   * @param int    $otMultiplierBasisPoints  OT multiplier in basis points (default 15000 = 1.5×; 10000 = 1.0×).
   * @param int    $perDiemCents             Daily per diem in cents.
   * @param int    $sitePremiumCents         Hourly site premium in cents.
   * @param string $taxRegion                Province code (e.g. 'AB').
   * @param string $unionCode                Free-form union label (optional).
   */
  public function __construct(
    int      $wageRateCents,
    RateType $rateType                  = RateType::Hourly,
    OtRule   $otRule                    = OtRule::Both,
    float    $otThresholdDailyHours     = 8.0,
    float    $otThresholdWeeklyHours    = 44.0,
    int      $otMultiplierBasisPoints   = 15000,
    int      $perDiemCents             = 0,
    int      $sitePremiumCents         = 0,
    string   $taxRegion                = 'AB',
    string   $unionCode                = ''
  ) {
    if ($wageRateCents < 0) {
      throw new InvalidArgumentException('wageRateCents must not be negative.');
    }
    if ($otThresholdDailyHours <= 0.0) {
      throw new InvalidArgumentException('otThresholdDailyHours must be positive.');
    }
    if ($otThresholdWeeklyHours <= 0.0) {
      throw new InvalidArgumentException('otThresholdWeeklyHours must be positive.');
    }
    if ($otMultiplierBasisPoints < 10000) {
      throw new InvalidArgumentException('otMultiplierBasisPoints must be >= 10000 (1.0×).');
    }
    if ($perDiemCents < 0) {
      throw new InvalidArgumentException('perDiemCents must not be negative.');
    }
    if ($sitePremiumCents < 0) {
      throw new InvalidArgumentException('sitePremiumCents must not be negative.');
    }

    $this->wageRateCents          = $wageRateCents;
    $this->rateType               = $rateType;
    $this->otRule                 = $otRule;
    $this->otThresholdDailyHours  = $otThresholdDailyHours;
    $this->otThresholdWeeklyHours = $otThresholdWeeklyHours;
    $this->otMultiplierBasisPoints = $otMultiplierBasisPoints;
    $this->perDiemCents           = $perDiemCents;
    $this->sitePremiumCents       = $sitePremiumCents;
    $this->taxRegion              = strtoupper($taxRegion);
    $this->unionCode              = $unionCode;
  }

  /**
   * Effective hourly rate in cents (wage + site premium).
   * Returns null for FlatDay workers where an hourly rate makes no sense.
   */
  public function effectiveHourlyRateCents(): ?int
  {
    if ($this->rateType === RateType::FlatDay) {
      return null;
    }
    if ($this->rateType === RateType::Daily) {
      // Treat a "daily" rate as 8 standard hours for effective-rate display only.
      return (int) round($this->wageRateCents / 8.0);
    }
    return $this->wageRateCents + $this->sitePremiumCents;
  }
}
