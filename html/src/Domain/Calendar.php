<?php declare(strict_types=1);

namespace PayCal\Domain;
use PayCal\Domain\Enums\PayFrequency;

/**
 * Calendar.php
 *
 * Purpose: Calendar date/grid utility for month navigation, day labeling, and
 * pay-period-aware calendar scaffolding used across calendar views.
 *
 * Developer notes:
 * - This class provides calendar structure, not persisted work-entry business
 *   rules; keep storage logic elsewhere.
 * - Grid shape, weekday labeling, and date-boundary behavior are UI contracts
 *   consumed by calendar rendering code and APIs.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */
/**
 * Calendar grid and navigation helper.
 *
 * Responsibilities:
 * - Normalize month/year inputs into safe calendar ranges.
 * - Generate month-grid dates and weekday labels.
 * - Support shared calendar navigation logic across pages and APIs.
 */
class Calendar
{
  private const MIN_MONTH = 1;
  private const MAX_MONTH = 12;
  private const MIN_YEAR = 1;
  private const MAX_YEAR = 9999;
  private const GRID_DAYS = 42;
  private const WEEK_DAYS = 7;

  private int $year;

  private int $month;

  private int $weekStart;

  /**
   * Initializes a new instance.
   */
  public function __construct(int $year, int $month, int $weekStart = 0, ?User $user = null)
  {
    $_ = $user;

    if ($month < self::MIN_MONTH || $month > self::MAX_MONTH) {
      throw new InvalidArgumentException('Month must be between ' . self::MIN_MONTH . ' and ' . self::MAX_MONTH . '.');
    }
    if ($year < self::MIN_YEAR || $year > self::MAX_YEAR) {
      throw new InvalidArgumentException('Year must be between ' . self::MIN_YEAR . ' and ' . self::MAX_YEAR . '.');
    }
    $this->year = $year;
    $this->month = $month;
    $this->weekStart = $weekStart;
  }

  /**
   * Handles fromDate operation.
   */
  public static function fromDate(\DateTime $date, int $weekStart = 0, ?User $user = null): self
  {
    return new self(intval($date->format('Y')), intval($date->format('m')), $weekStart, $user);
  }

  /**
   * Handles getFirstDay operation.
   */
  public function getFirstDay(): \DateTime
  {
    return (new \DateTime())->setDate($this->year, $this->month, 1)->setTime(0, 0);
  }

  /**
   * Handles getLastDay operation.
   */
  public function getLastDay(): \DateTime
  {
    return (clone $this->getFirstDay())->modify('last day of this month');
  }

  /**
   * Handles getPreviousMonthDate operation.
   */
  public function getPreviousMonthDate(): string
  {
    return $this->getFirstDay()->modify('-1 month')->format('Y-m-01');
  }

  /**
   * Handles getNextMonthDate operation.
   */
  public function getNextMonthDate(): string
  {
    return $this->getFirstDay()->modify('+1 month')->format('Y-m-01');
  }

  /**
   * Provides navigation links for previous and next months.
   *
   * @param string $direction "prev" for previous month, "next" for next month
   *
   * @return string The date string for the navigation link
   */
  public function getNavigation(string $direction): string
  {
    return match ($direction) {
      'prev' => $this->getPreviousMonthDate(),
      'next' => $this->getNextMonthDate(),
      default => $this->getFirstDay()->format('Y-m-d')
    };
  }

  /**
   * Generates a list of date IDs (YYYY-MM-DD) for the entire calendar grid,
   * including leading and trailing days from adjacent months to complete weeks.
   *
   * @return \Generator<int, string>
   */
  public function getDayIDs(): \Generator
  {
    $firstOfMonth = $this->getFirstDay();
    $firstWeekday = ((int) $firstOfMonth->format('w') + 7 - $this->weekStart) % 7;
    $startDate = (clone $firstOfMonth)->modify("- {$firstWeekday} days");

    for ($i = 0; $i < self::GRID_DAYS; ++$i) {
      yield (clone $startDate)->modify("+ {$i} days")->format('Y-m-d');
    }
  }

  /**
   * @return list<array{short:string,long:string}>
   */
  public function generateWeekDayLabels(): array
  {
    $labels = [];

    // Sunday start (0) to Saturday (6)
    for ($i = 0; $i < self::WEEK_DAYS; $i++) {
      $date = new \DateTimeImmutable("Sunday +{$i} days");

      $labels[] = [
        'short' => $date->format('D'),   // Mon
        'long' => $date->format('l'),    // Monday
      ];
    }

    return $labels;
  }

  /**
   * Handles getCurrentPayPeriods operation.
   */
  public static function getCurrentPayPeriods(?User $user = null): PayPeriods
  {
    $user = $user ?? User::current();
    $tz = $user->timezone ?? 'America/Edmonton';
    $zone = new \DateTimeZone($tz);
    $now = new \DateTimeImmutable('now', $zone);
    $scheduled = PayPeriodGenerator::resolveForDate($user, $now);
    if (null !== $scheduled) {
      return $scheduled;
    }

    $frequency = PayPeriodGenerator::resolveFrequency($user);
    $anchor = $user->pay_anchor ?? 'Monday';
    $epoch = null;
    if ($frequency === PayFrequency::BIWEEKLY && !empty($user->pay_epoch)) {
      $epoch = new \DateTimeImmutable($user->pay_epoch, $zone);
    }

    return PayPeriods::fromDate($now, $frequency, $anchor, $epoch, $tz);
  }
}


