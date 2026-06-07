<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * ForecastResult.php
 *
 * Purpose: Immutable value object carrying the complete labor-cost breakdown
 *          produced by CrewForecastEngine for a single worker or an aggregated
 *          crew over a ForecastWindow.
 *
 * Developer notes:
 * - All monetary values are in cents (integer) to avoid float rounding errors.
 * - estimated_tax and estimated_net are ESTIMATES only; they must never be
 *   presented as CRA-authoritative payroll figures in the UI.
 * - The object is intentionally plain-data (no methods beyond conversion
 *   helpers) to make it easy to serialise for API responses.
 *
 * Architectural role:
 * - Domain value object returned by CrewForecastEngine.
 * - Consumed by controllers and view helpers to render forecast tables.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Immutable labor cost forecast breakdown.
 *
 * All monetary fields are in integer cents.
 */
final class ForecastResult
{
  /** Regular (non-OT) hours worked. */
  public readonly float $regularHours;

  /** Overtime hours worked. */
  public readonly float $otHours;

  /** Number of on-site calendar days (for per-diem calculation). */
  public readonly int $onsiteDays;

  /** Regular pay in cents. */
  public readonly int $regularPayCents;

  /** Overtime premium pay in cents (the incremental cost above base for OT hours). */
  public readonly int $otPayCents;

  /** Per diem total in cents. */
  public readonly int $perDiemTotalCents;

  /** Site premium total in cents. */
  public readonly int $sitePremiumTotalCents;

  /** Gross earnings in cents (regular + OT + per diem + site premium). */
  public readonly int $estimatedGrossCents;

  /**
   * Estimated combined income tax in cents (federal + provincial marginal rate stub).
   * Labelled ESTIMATE — not CRA-authoritative.
   */
  public readonly int $estimatedTaxCents;

  /**
   * Estimated net pay in cents (gross − estimated tax).
   * Labelled ESTIMATE — not CRA-authoritative.
   */
  public readonly int $estimatedNetCents;

  /**
   * @param float $regularHours
   * @param float $otHours
   * @param int   $onsiteDays
   * @param int   $regularPayCents
   * @param int   $otPayCents
   * @param int   $perDiemTotalCents
   * @param int   $sitePremiumTotalCents
   * @param int   $estimatedTaxCents
   */
  public function __construct(
    float $regularHours,
    float $otHours,
    int   $onsiteDays,
    int   $regularPayCents,
    int   $otPayCents,
    int   $perDiemTotalCents,
    int   $sitePremiumTotalCents,
    int   $estimatedTaxCents
  ) {
    $this->regularHours          = $regularHours;
    $this->otHours               = $otHours;
    $this->onsiteDays            = $onsiteDays;
    $this->regularPayCents       = $regularPayCents;
    $this->otPayCents            = $otPayCents;
    $this->perDiemTotalCents     = $perDiemTotalCents;
    $this->sitePremiumTotalCents = $sitePremiumTotalCents;
    $this->estimatedGrossCents   = $regularPayCents + $otPayCents
                                   + $perDiemTotalCents + $sitePremiumTotalCents;
    $this->estimatedTaxCents     = $estimatedTaxCents;
    $this->estimatedNetCents     = max(0, $this->estimatedGrossCents - $estimatedTaxCents);
  }

  /**
   * Return gross as a dollar float (for display only; use cents for calculations).
   */
  public function estimatedGrossDollars(): float
  {
    return round($this->estimatedGrossCents / 100, 2);
  }

  /**
   * Return estimated net as a dollar float (for display only).
   */
  public function estimatedNetDollars(): float
  {
    return round($this->estimatedNetCents / 100, 2);
  }

  /**
   * Aggregate multiple ForecastResult objects into a crew total.
   *
   * @param ForecastResult[] $results
   */
  public static function aggregate(array $results): self
  {
    $regularHours          = 0.0;
    $otHours               = 0.0;
    $onsiteDays            = 0;
    $regularPayCents       = 0;
    $otPayCents            = 0;
    $perDiemTotalCents     = 0;
    $sitePremiumTotalCents = 0;
    $estimatedTaxCents     = 0;

    foreach ($results as $r) {
      $regularHours          += $r->regularHours;
      $otHours               += $r->otHours;
      $onsiteDays            += $r->onsiteDays;
      $regularPayCents       += $r->regularPayCents;
      $otPayCents            += $r->otPayCents;
      $perDiemTotalCents     += $r->perDiemTotalCents;
      $sitePremiumTotalCents += $r->sitePremiumTotalCents;
      $estimatedTaxCents     += $r->estimatedTaxCents;
    }

    return new self(
      $regularHours,
      $otHours,
      $onsiteDays,
      $regularPayCents,
      $otPayCents,
      $perDiemTotalCents,
      $sitePremiumTotalCents,
      $estimatedTaxCents
    );
  }
}
