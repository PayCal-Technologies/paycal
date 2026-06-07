<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * RotationTemplate.php
 *
 * Purpose: Immutable value object representing a work/rest rotation schedule
 *          (e.g. 14 on / 7 off) anchored to a start date, used to enumerate
 *          which calendar days are worked within a ForecastWindow.
 *
 * Developer notes:
 * - Rotation cycles repeat indefinitely from the anchor date.
 * - The anchor date is the first day of a WORK block (not a rest block).
 * - This class has zero I/O dependencies and must remain a pure value object.
 * - Daily hours worked is a schedule-level concept (e.g. 10 h/day on a 20/10).
 *
 * Architectural role:
 * - Domain value object consumed by CrewForecastEngine.
 * - Encapsulates rotation schedule logic outside the HTTP/persistence layers.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Immutable work/rest rotation schedule anchored to a start date.
 *
 * Common industrial rotations:
 * - 14/7:  14 work days, 7 rest days, 8 h/day  (typical Alberta O&G)
 * - 20/10: 20 work days, 10 rest days, 10 h/day (common LNG/mining)
 * - 14/14: 14 work days, 14 rest days, 12 h/day (remote/fly-in fly-out)
 * - 7/7:    7 work days,  7 rest days, 12 h/day  (weekly rotations)
 */
final class RotationTemplate
{
  /** Number of consecutive work days per cycle. */
  public readonly int $workDays;

  /** Number of consecutive rest days per cycle. */
  public readonly int $restDays;

  /** Standard hours worked per work day. */
  public readonly float $hoursPerDay;

  /** Anchor date: first day of the first WORK block for this worker. */
  public readonly \DateTimeImmutable $anchorDate;

  /** Human-readable label, e.g. "14/7". */
  public readonly string $label;

  /**
   * @param int                $workDays    Consecutive work days per cycle (>= 1).
   * @param int                $restDays    Consecutive rest days per cycle (>= 1).
   * @param float              $hoursPerDay Standard hours worked per day (> 0).
   * @param \DateTimeImmutable $anchorDate  First day of the first work block.
   * @param string             $label       Human-readable label (auto-derived if empty).
   */
  public function __construct(
    int                $workDays,
    int                $restDays,
    float              $hoursPerDay,
    \DateTimeImmutable $anchorDate,
    string             $label = ''
  ) {
    if ($workDays < 1) {
      throw new InvalidArgumentException('workDays must be at least 1.');
    }
    if ($restDays < 1) {
      throw new InvalidArgumentException('restDays must be at least 1.');
    }
    if ($hoursPerDay <= 0.0 || $hoursPerDay > 24.0) {
      throw new InvalidArgumentException('hoursPerDay must be between 0 and 24.');
    }

    $this->workDays    = $workDays;
    $this->restDays    = $restDays;
    $this->hoursPerDay = $hoursPerDay;
    $this->anchorDate  = $anchorDate->setTime(0, 0, 0);
    $this->label       = $label !== '' ? $label : "{$workDays}/{$restDays}";
  }

  /**
   * Total cycle length in calendar days.
   */
  public function cycleDays(): int
  {
    return $this->workDays + $this->restDays;
  }

  /**
   * Determine whether a given date is a work day under this rotation.
   *
   * @param \DateTimeImmutable $date The calendar date to check.
   */
  public function isWorkDay(\DateTimeImmutable $date): bool
  {
    $dayOffset = (int) $this->anchorDate->diff($date->setTime(0, 0, 0))->days;
    // Handle dates before the anchor (negative offset wraps correctly).
    if ($date < $this->anchorDate) {
      $dayOffset = -$dayOffset;
    }
    $positionInCycle = (($dayOffset % $this->cycleDays()) + $this->cycleDays()) % $this->cycleDays();
    return $positionInCycle < $this->workDays;
  }

  /**
   * Count work days within a ForecastWindow (inclusive of start, exclusive of end).
   *
   * @param ForecastWindow $window The forecast date range.
   *
   * @return int Number of work days.
   */
  public function workDaysInWindow(ForecastWindow $window): int
  {
    $count   = 0;
    $current = $window->start->setTime(0, 0, 0);
    $end     = $window->end->setTime(0, 0, 0);

    while ($current < $end) {
      if ($this->isWorkDay($current)) {
        ++$count;
      }
      $current = $current->modify('+1 day');
    }

    return $count;
  }

  /**
   * Named constructor for common industrial rotations.
   *
   * @param string             $pattern    One of "14/7", "20/10", "14/14", "7/7".
   * @param \DateTimeImmutable $anchorDate First day of the first work block.
   *
   * @throws InvalidArgumentException for unrecognised patterns.
   */
  public static function fromPattern(string $pattern, \DateTimeImmutable $anchorDate): self
  {
    return match ($pattern) {
      '14/7'  => new self(14, 7, 8.0, $anchorDate, '14/7'),
      '20/10' => new self(20, 10, 10.0, $anchorDate, '20/10'),
      '14/14' => new self(14, 14, 12.0, $anchorDate, '14/14'),
      '7/7'   => new self(7, 7, 12.0, $anchorDate, '7/7'),
      default => throw new InvalidArgumentException("Unknown rotation pattern: {$pattern}"),
    };
  }
}
