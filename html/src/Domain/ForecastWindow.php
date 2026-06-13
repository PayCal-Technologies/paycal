<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * ForecastWindow.php
 *
 * Purpose: Immutable value object representing a named or custom date range
 *          used as the forecasting horizon for crew labor cost projections.
 *
 * Developer notes:
 * - All dates are resolved at construction time to avoid mutation risk.
 * - `project_total` requires an explicit end date; all other windows derive
 *   end dates automatically from the supplied anchor date.
 * - This class has zero I/O dependencies and must remain a pure value object.
 *
 * Architectural role:
 * - Domain value object consumed exclusively by CrewForecastEngine.
 * - Encapsulates date-boundary logic outside the HTTP and persistence layers.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Named forecast windows.
 */
enum ForecastWindowType: string
{
  case NextPayPeriod  = 'next_pay_period';
  case Next30Days     = 'next_30_days';
  case Quarter        = 'quarter';
  case YearProjection = 'year_projection';
  case ProjectTotal   = 'project_total';
}

/**
 * Immutable date-range value object for a forecast horizon.
 *
 * Construct via the named factory methods rather than directly.
 */
final class ForecastWindow
{
  public readonly ForecastWindowType $type;
  public readonly \DateTimeImmutable $start;
  public readonly \DateTimeImmutable $end;

  /**
   * TODO: Document __construct.
   */
  private function __construct(
    ForecastWindowType $type,
    \DateTimeImmutable $start,
    \DateTimeImmutable $end
  ) {
    if ($end <= $start) {
      throw new InvalidArgumentException('ForecastWindow end must be after start.');
    }
    $this->type  = $type;
    $this->start = $start;
    $this->end   = $end;
  }

  /**
   * Window covering the next pay period (one pay-period length from anchor).
   *
   * @param \DateTimeImmutable $anchor     First day of the upcoming pay period.
   * @param int                $periodDays Number of days in the pay period (7, 14, etc.).
   */
  public static function nextPayPeriod(
    \DateTimeImmutable $anchor,
    int $periodDays = 14
  ): self {
    if ($periodDays < 1) {
      throw new InvalidArgumentException('periodDays must be at least 1.');
    }
    return new self(
      ForecastWindowType::NextPayPeriod,
      $anchor,
      $anchor->modify("+{$periodDays} days")
    );
  }

  /**
   * Window covering the next 30 calendar days from anchor.
   *
   * @param \DateTimeImmutable $anchor Start date (defaults to today).
   */
  public static function next30Days(\DateTimeImmutable $anchor): self
  {
    return new self(
      ForecastWindowType::Next30Days,
      $anchor,
      $anchor->modify('+30 days')
    );
  }

  /**
   * Window covering the next 91 calendar days (~1 quarter) from anchor.
   *
   * @param \DateTimeImmutable $anchor Start date.
   */
  public static function quarter(\DateTimeImmutable $anchor): self
  {
    return new self(
      ForecastWindowType::Quarter,
      $anchor,
      $anchor->modify('+91 days')
    );
  }

  /**
   * Window from anchor through the end of the calendar year (exclusive end).
   */
  public static function yearProjection(\DateTimeImmutable $anchor): self
  {
    $yearEnd = new \DateTimeImmutable($anchor->format('Y') . '-12-31');
    $end = $yearEnd->modify('+1 day');
    if ($end <= $anchor) {
      $end = $anchor->modify('+1 day');
    }

    return new self(ForecastWindowType::YearProjection, $anchor, $end);
  }

  /**
   * Custom project window with an explicit end date (e.g. shutdown completion).
   *
   * @param \DateTimeImmutable $start Project start date.
   * @param \DateTimeImmutable $end   Project end date (must be after start).
   */
  public static function projectTotal(
    \DateTimeImmutable $start,
    \DateTimeImmutable $end
  ): self {
    return new self(ForecastWindowType::ProjectTotal, $start, $end);
  }

  /**
   * Number of calendar days in this window (inclusive of start, exclusive of end).
   */
  public function days(): int
  {
    return (int) $this->start->diff($this->end)->days;
  }

  /**
   * Human-readable label for display.
   */
  public function label(): string
  {
    return match ($this->type) {
      ForecastWindowType::NextPayPeriod => 'Next Pay Period',
      ForecastWindowType::Next30Days    => 'Next 30 Days',
      ForecastWindowType::Quarter       => 'Quarter',
      ForecastWindowType::YearProjection => 'Year Projection',
      ForecastWindowType::ProjectTotal  => 'Project Total',
    };
  }
}
