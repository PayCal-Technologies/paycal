<?php declare(strict_types=1);

namespace PayCal\Domain;
use PayCal\Domain\Enums\PayFrequency;

/**
 * PayPeriods.php
 *
 * Purpose: Deterministic pay period boundary calculator for all supported frequencies
 *          with timezone-aware, exclusive end dates.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain
 *
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Class PayPeriods.
 *
 * Provides deterministic pay period calculations with support for multiple frequencies
 * (weekly, biweekly, semimonthly, monthly). Handles timezone-aware date calculations
 * and maintains exclusive end dates for accurate period boundaries.
 */
final class PayPeriods
{
  private const ERR_UNHANDLED_PAY_FREQUENCY = 'ERR_UNHANDLED_PAY_FREQUENCY';
  private const ERR_TIMEZONE_MISMATCH = 'ERR_TIMEZONE_MISMATCH';

  private PayFrequency $frequency;

  /** @var \DateTimeImmutable Start of the period (inclusive, local midnight) */
  private \DateTimeImmutable $start;

  /** @var \DateTimeImmutable End of the period (exclusive, local midnight) */
  private \DateTimeImmutable $end;

  /** @var string IANA timezone identifier (e.g., "America/Edmonton") */
  private string $timezone;

  /** @var string Anchor weekday name (e.g., "Monday") */
  private string $anchor;

  /** @var null|\DateTimeImmutable Epoch start used to align biweekly cycles;
   * must itself be an anchor-day midnight
   */
  private ?\DateTimeImmutable $epoch;

  /**
   * @param PayFrequency           $frequency Pay frequency enum
   * @param \DateTimeImmutable      $start     Start inclusive, normalized to midnight in $timezone
   * @param \DateTimeImmutable      $end       Exclusive end, normalized to midnight in $timezone
   * @param string                 $timezone  IANA timezone string
   * @param string                 $anchor    Anchor weekday, case-insensitive ("Monday" .. "Sunday")
   * @param null|\DateTimeImmutable $epoch     Optional cycle epoch for biweekly alignment
   */
  private function __construct(
    PayFrequency $frequency,
    \DateTimeImmutable $start,
    \DateTimeImmutable $end,
    string $timezone,
    string $anchor,
    ?\DateTimeImmutable $epoch = null
  ) {
    $this->frequency = $frequency;
    $this->start = $start;
    $this->end = $end;
    $this->timezone = $timezone;
    $this->anchor = $anchor;
    $this->epoch = $epoch;
  }

  /**
   * Factory: create from a date within a pay period.
   *
   * Automatically determines the containing pay period based on the frequency,
   * anchor weekday, and optional epoch for biweekly alignment.
   *
   * @param \DateTimeInterface|string $date      Date to create period from (string or DateTimeInterface)
   * @param PayFrequency             $frequency Payment frequency (WEEKLY, BIWEEKLY, SEMIMONTHLY, or MONTHLY)
   * @param string                   $anchor    Anchor weekday for labeling/navigation (default 'Monday')
   * @param null|\DateTimeImmutable   $epoch     Optional epoch for biweekly navigation continuity
   * @param null|string              $tz        Timezone (defaults to current user's timezone or 'America/Edmonton')
   *
   * @return self Pay period instance containing the given date
   */
  public static function fromDate(
    \DateTimeInterface|string $date,
    PayFrequency $frequency,
    string $anchor = 'Monday',
    ?\DateTimeImmutable $epoch = null,
    ?string $tz = null
  ): self {
    if (null === $tz) {
      $tz = User::current()->timezone;
    }
    $zone = new \DateTimeZone($tz);

    // 1) Normalize input to \DateTimeImmutable in target timezone
    if (is_string($date)) {
      $dt = new \DateTimeImmutable($date, $zone);
    } else {
      $dt = \DateTimeImmutable::createFromInterface($date)->setTimezone($zone);
    }

    // 2) Snap to midnight in that timezone
    $dt = self::atMidnight($dt, $zone);

    $anchorNorm = self::normalizeAnchor($anchor);
    $epochNormalized = null;

    switch ($frequency) {
      case PayFrequency::WEEKLY:
        $start = self::weeklyStart($dt, $anchorNorm);
        $end = $start->modify('+7 days');

        break;

      case PayFrequency::BIWEEKLY:
        $epochNormalized = null !== $epoch
          ? self::alignEpochToAnchor(self::atMidnight($epoch, $zone), $anchorNorm)
          : self::deriveEpoch($dt, $anchorNorm, $zone);

        $start = self::biweeklyStart($dt, $anchorNorm, $epochNormalized);
        $end = $start->modify('+14 days');

        break;

      case PayFrequency::SEMIMONTHLY:
        [$start, $end] = self::semiMonthlySpan($dt);

        break;

      case PayFrequency::MONTHLY:
        $start = $dt->modify('first day of this month');
        $end = $start->modify('first day of next month');

        break;

      default:
        throw new \LogicException(Strings::i18n(self::ERR_UNHANDLED_PAY_FREQUENCY).': '.$frequency->name);
    }

    return new self($frequency, $start, $end, $tz, $anchorNorm, $epochNormalized);
  }

  /**
   * Handles getFrequency operation.
   */
  public function getFrequency(): PayFrequency
  {
    return $this->frequency;
  }

  /**
   * Factory: build directly from a [start, end) range.
   *
   * @param \DateTimeImmutable      $start     Start date (inclusive); normalized to midnight
   * @param \DateTimeImmutable      $end       End date (exclusive); normalized to midnight
   * @param PayFrequency           $frequency Pay frequency (WEEKLY, BIWEEKLY, SEMIMONTHLY, or MONTHLY)
   * @param string                 $anchor    Anchor weekday for labeling/navigation (default 'Monday')
   * @param null|\DateTimeImmutable $epoch     Optional epoch for biweekly navigation continuity
   *
   * @return self Pay period instance
   */
  public static function fromRange(
    \DateTimeImmutable $start,
    \DateTimeImmutable $end,
    PayFrequency $frequency,
    string $anchor = 'Monday',
    ?\DateTimeImmutable $epoch = null
  ): self {
    $tz = $start->getTimezone()->getName();
    if ($start->getTimezone()->getName() !== $end->getTimezone()->getName()) {
      throw new InvalidArgumentException(Strings::i18n(self::ERR_TIMEZONE_MISMATCH));
    }

    $zone = new \DateTimeZone($tz);
    $start = self::atMidnight($start, $zone);
    $end = self::atMidnight($end, $zone);
    if ($end <= $start) {
      throw new InvalidArgumentException('End must be after start');
    }

    return new self($frequency, $start, $end, $tz, self::normalizeAnchor($anchor), $epoch);
  }

  /**
   * Get start date (inclusive, at midnight).
   *
   * @return \DateTimeImmutable Period start date
   */
  public function start(): \DateTimeImmutable
  {
    return $this->start;
  }

  /**
   * Get end date (exclusive, at midnight).
   *
   * Note: Period does not include this date; it includes endInclusive() instead.
   *
   * @return \DateTimeImmutable Period end date (exclusive)
   */
  public function endExclusive(): \DateTimeImmutable
  {
    return $this->end;
  }

  /**
   * Get end date (inclusive, for display).
   *
   * Returns endExclusive() minus one day for readable period labels.
   *
   * @return \DateTimeImmutable Period end date (inclusive)
   */
  public function endInclusive(): \DateTimeImmutable
  {
    return $this->end->modify('-1 day');
  }

  /**
   * Get period label using given format.
   *
   * Uses inclusive-end date for readable display (e.g., "2025-01-01 → 2025-01-07").
   *
   * @param string $fmt Date format string for both ends (default "Y-m-d")
   *
   * @return string Formatted period label
   */
  public function label(string $fmt = 'Y-m-d'): string
  {
    return $this->start->format($fmt).' → '.$this->endInclusive()->format($fmt);
  }

  /**
   * Test if a date-time falls inside the period [start, end).
   *
   * @param \DateTimeImmutable $date Date to test (timezone-agnostic; converted to period's timezone)
   *
   * @return bool True if date is within the period
   */
  public function contains(\DateTimeImmutable $date): bool
  {
    $zone = new \DateTimeZone($this->timezone);
    $dt = self::atMidnight($date->setTimezone($zone), $zone);

    return ($dt >= $this->start) && ($dt < $this->end);
  }

  /**
   * Number of days in the pay period.
   *
   * @return int Number of days from start to end
   */
  public function lengthDays(): int
  {
    $seconds = $this->end->getTimestamp() - $this->start->getTimestamp();

    return (int) ($seconds / 86400);
  }

  /**
   * List of DateTime days within the period (each at local midnight).
   *
   * @return array<int,\DateTimeImmutable>
   */
  public function days(): array
  {
    $days = [];
    $cur = $this->start;
    while ($cur < $this->end) {
      $days[] = \DateTimeImmutable::createFromInterface($cur);
      $cur = $cur->modify('+1 day');
    }

    return $days;
  }

  /**
   * Get the next adjacent pay period with same configuration.
   *
   * Creates a new period immediately following this one,
   * maintaining the same frequency, anchor, and epoch settings.
   *
   * @return self Next pay period instance
   */
  public function next(): self
  {
    switch ($this->frequency) {
      case PayFrequency::WEEKLY:
        return self::fromDate($this->end, $this->frequency, $this->anchor, null, $this->timezone);

      case PayFrequency::SEMIMONTHLY:
      case PayFrequency::MONTHLY:
        return self::fromDate($this->end, $this->frequency, $this->anchor, null, $this->timezone);

      default:
      case PayFrequency::BIWEEKLY:
        return self::fromDate($this->end, $this->frequency, $this->anchor, $this->epoch, $this->timezone);
    }
  }

  /**
   * Get the previous adjacent pay period with same configuration.
   *
   * Creates a new period immediately preceding this one,
   * maintaining the same frequency, anchor, and epoch settings.
   *
   * @return self Previous pay period instance
   */
  public function previous(): self
  {
    $prevDate = $this->start->modify('-1 day');

    switch ($this->frequency) {
      case PayFrequency::WEEKLY:
        return self::fromDate($prevDate, $this->frequency, $this->anchor, null, $this->timezone);

      case PayFrequency::SEMIMONTHLY:
      case PayFrequency::MONTHLY:
        return self::fromDate($prevDate, $this->frequency, $this->anchor, null, $this->timezone);

      default:
      case PayFrequency::BIWEEKLY:
        return self::fromDate($prevDate, $this->frequency, $this->anchor, $this->epoch, $this->timezone);
    }
  }

  /**
   * Export as scalars for logging/JSON.
   *
   * @return array{
   *   frequency: string,
   *   timezone: string,
   *   anchor: string,
   *   start: string,
   *   end_exclusive: string,
   *   end_inclusive: string,
   *   length_days: int,
   *   epoch: null|string
   * }
   */
  public function toArray(): array
  {
    return [
        'frequency' => $this->frequency->value,
        'timezone' => $this->timezone,
        'anchor' => $this->anchor,
        'start' => $this->start->format('Y-m-d'),
        'end_exclusive' => $this->end->format('Y-m-d'),
        'end_inclusive' => $this->endInclusive()->format('Y-m-d'),
        'length_days' => $this->lengthDays(),
        'epoch' => $this->epoch ? $this->epoch->format('Y-m-d') : null,
    ];
  }

  /**
   * Compute 1-based pay period number for this instance.
   */
  public function getPayPeriodNumber(): int
  {
    $tz = new \DateTimeZone($this->timezone);

    $currentStart = $this->start
        ->setTime(0, 0)
        ->setTimezone($tz)
    ;

    $year = (int) $currentStart->format('Y');

    $anchorFY = $this->makeAnchorForYear($year);
    if ($currentStart < $anchorFY) {
      $anchorFY = $this->makeAnchorForYear($year - 1);
    }

    $diff = $anchorFY->diff($currentStart);
    $diffDays = $diff->days;
    if (false === $diffDays) {
      $diffDays = 0;
    }

    /** @var int $diffDays */
    $diffDays = (int) $diffDays;

    switch ($this->frequency) {
      case PayFrequency::WEEKLY:
        return (int) floor($diffDays / 7) + 1;

      case PayFrequency::BIWEEKLY:
        return (int) floor($diffDays / 14) + 1;

      case PayFrequency::MONTHLY:
        return $this->monthsBetween($anchorFY, $currentStart) + 1;

      case PayFrequency::SEMIMONTHLY:
        $months = $this->monthsBetween($anchorFY, $currentStart);
        $anchorHalf = ((int) $anchorFY->format('j') <= 15) ? 1 : 2;
        $currentHalf = ((int) $currentStart->format('j') <= 15) ? 1 : 2;
        $number = ($months * 2) + ($currentHalf - $anchorHalf) + 1;

        return max(1, $number);
    }

  }

  /**
   * Get the pay period (start/end/label/number) that contains the given date.
   * Returns an array with keys: start, end, label_short, label_full, number.
   *
   * @return array{start:\DateTimeImmutable, end:\DateTimeImmutable, label_short:string,
   * label_full:string, number:int}
   */
  public function getPayPeriodForDate(\DateTimeImmutable $date): array
  {
    $tz = new \DateTimeZone($this->timezone);
    $d = $date->setTime(0, 0)->setTimezone($tz);

    $anchorFY = $this->makeAnchorForYear((int) $d->format('Y'));
    if ($d < $anchorFY) {
      $anchorFY = $this->makeAnchorForYear(((int) $d->format('Y')) - 1);
    }

    $start = $anchorFY;
    $end = $anchorFY;

    switch ($this->frequency) {
      case PayFrequency::WEEKLY:
        $days = $anchorFY->diff($d)->days;
        $offset = (int) floor($days / 7);
        $start = $anchorFY->modify('+'.($offset * 7).' days');
        $end = $start->modify('+6 days');

        break;

      case PayFrequency::BIWEEKLY:
        $days = $anchorFY->diff($d)->days;
        $offset = (int) floor($days / 14);
        $start = $anchorFY->modify('+'.($offset * 14).' days');
        $end = $start->modify('+13 days');

        break;

      case PayFrequency::MONTHLY:
        $mOff = $this->monthsBetween($anchorFY, $d);
        $anchorDay = (int) $anchorFY->format('j');
        $targetY = (int) $anchorFY->modify('+'.$mOff.' months')->format('Y');
        $targetM = (int) $anchorFY->modify('+'.$mOff.' months')->format('n');
        $dim = (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $targetY, $targetM), $tz))->modify('last day of this month')->format('j');
        $start = new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $targetY, $targetM, min($anchorDay, $dim)), $tz);
        $nextY = (int) $start->modify('+1 month')->format('Y');
        $nextM = (int) $start->modify('+1 month')->format('n');
        $nextDim = (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $nextY, $nextM), $tz))->modify('last day of this month')->format('j');
        $next = new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $nextY, $nextM, min($anchorDay, $nextDim)), $tz);
        $end = $next->modify('-1 day');

        break;

      case PayFrequency::SEMIMONTHLY:
        // Split at 1–15 and 16–EOM. Anchor defines numbering order, not boundaries.
        $y = (int) $d->format('Y');
        $m = (int) $d->format('n');
        $eom = (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $y, $m), $tz))->modify('last day of this month')->format('j');
        if ((int) $d->format('j') <= 15) {
          $start = new \DateTimeImmutable(sprintf('%04d-%02d-01', $y, $m), $tz);
          $end = new \DateTimeImmutable(sprintf('%04d-%02d-15', $y, $m), $tz);
        } else {
          $start = new \DateTimeImmutable(sprintf('%04d-%02d-16', $y, $m), $tz);
          $end = new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $y, $m, $eom), $tz);
        }

        break;

      default:
        // Fallback: treat as biweekly to avoid nulls
        $days = $anchorFY->diff($d)->days;
        $offset = (int) floor($days / 14);
        $start = $anchorFY->modify('+'.($offset * 14).' days');
        $end = $start->modify('+13 days');

        break;
    }

    $number = $this->getPayPeriodNumberForDate($d);

    return [
        'start' => $start,
        'end' => $end,
        'label_short' => $start->format('M d').' → '.$end->format('M d, Y'),
        'label_full' => $start->format('Y-m-d').' to '.$end->format('Y-m-d'),
        'number' => $number,
    ];
  }

  /**
   * Compute the pay-period number for the fiscal year of the given date.
   */
  public function getPayPeriodNumberForDate(\DateTimeImmutable $date): int
  {
    $tz = new \DateTimeZone($this->timezone);
    $d = $date->setTime(0, 0)->setTimezone($tz);

    $anchorFY = $this->makeAnchorForYear((int) $d->format('Y'));
    if ($d < $anchorFY) {
      $anchorFY = $this->makeAnchorForYear(((int) $d->format('Y')) - 1);
    }

    switch ($this->frequency) {
      case PayFrequency::WEEKLY:
        return (int) floor($anchorFY->diff($d)->days / 7) + 1;

      case PayFrequency::BIWEEKLY:
        return (int) floor($anchorFY->diff($d)->days / 14) + 1;

      case PayFrequency::MONTHLY:
        return $this->monthsBetween($anchorFY, $d) + 1;

      case PayFrequency::SEMIMONTHLY:
        $m = $this->monthsBetween($anchorFY, $d);
        $anchorHalf = ((int) $anchorFY->format('j') <= 15) ? 1 : 2;
        $currentHalf = ((int) $d->format('j') <= 15) ? 1 : 2;
        $n = ($m * 2) + ($currentHalf - $anchorHalf) + 1;

        return max(1, $n);

      default:
        return 1;
    }
  }

  /**
   * Normalize a DateTime to local midnight.
   *
   * @param \DateTimeImmutable $dt Input datetime
   * @param \DateTimeZone      $tz Target timezone
   */
  private static function atMidnight(\DateTimeImmutable $dt, \DateTimeZone $tz): \DateTimeImmutable
  {
    return $dt->setTimezone($tz)->setTime(0, 0, 0);
  }

  /**
   * Normalize anchor string to canonical case.
   *
   * @param string $anchor Weekday name
   */
  private static function normalizeAnchor(string $anchor): string
  {
    $u = strtoupper($anchor);

    // TODO: Consider i18n support for localized weekday names.
    return match ($u) {
      'MONDAY' => 'Monday',
      'TUESDAY' => 'Tuesday',
      'WEDNESDAY' => 'Wednesday',
      'THURSDAY' => 'Thursday',
      'FRIDAY' => 'Friday',
      'SATURDAY' => 'Saturday',
      'SUNDAY' => 'Sunday',
      default => throw new InvalidArgumentException('Invalid anchor day: '.$anchor),
    };
  }

  /**
   * ISO-8601 day-of-week number (1=Mon..7=Sun).
   *
   * @param \DateTimeImmutable $dt Date
   */
  private static function isoDow(\DateTimeImmutable $dt): int
  {
    return (int) $dt->format('N');
  }

  /**
   * ISO-8601 day-of-week from anchor string (1=Mon..7=Sun).
   *
   * @param string $anchor Canonical anchor
   */
  private static function isoDowForAnchor(string $anchor): int
  {
    return match ($anchor) {
      'Monday' => 1,
      'Tuesday' => 2,
      'Wednesday' => 3,
      'Thursday' => 4,
      'Friday' => 5,
      'Saturday' => 6,
      'Sunday' => 7,
      default => 1,
    };
  }

  /**
   * Weekly start: same-week anchor day at or before the given date.
   *
   * @param \DateTimeImmutable $dt     Local midnight date
   * @param string            $anchor Canonical anchor
   */
  private static function weeklyStart(\DateTimeImmutable $dt, string $anchor): \DateTimeImmutable
  {
    $dow = self::isoDow($dt);
    $anchorDow = self::isoDowForAnchor($anchor);
    $delta = ($dow - $anchorDow + 7) % 7;

    return $dt->modify("-{$delta} days");
  }

  /**
   * Biweekly start aligned to an epoch that itself is an anchor-day midnight.
   *
   * @param \DateTimeImmutable $dt     Local midnight date in target TZ
   * @param string            $anchor Canonical anchor
   * @param \DateTimeImmutable $epoch  Anchor-aligned epoch
   */
  private static function biweeklyStart(\DateTimeImmutable $dt, string $anchor, \DateTimeImmutable $epoch): \DateTimeImmutable
  {
    $epoch = self::alignEpochToAnchor($epoch, $anchor);
    $days = intdiv($dt->getTimestamp() - $epoch->getTimestamp(), 86400);
    if ($dt < $epoch) {
      $cycles = intdiv($days - 13, 14); // floor toward -∞
    } else {
      $cycles = intdiv($days, 14);
    }

    return $epoch->modify(sprintf('+%d days', $cycles * 14));
  }

  /**
   * Derive a stable epoch on or before the given date, aligned to the anchor.
   *
   * @param \DateTimeImmutable $reference Reference date at local midnight
   * @param string            $anchor    Canonical anchor
   * @param \DateTimeZone      $tz        Target timezone
   */
  private static function deriveEpoch(\DateTimeImmutable $reference, string $anchor, \DateTimeZone $tz): \DateTimeImmutable
  {
    $base = self::atMidnight(new \DateTimeImmutable('1970-01-01', $tz), $tz);
    $firstAnchor = self::weeklyStart($base, $anchor);
    if ($firstAnchor < $base) {
      $firstAnchor = $firstAnchor->modify('+7 days');
    }

    return $firstAnchor;
  }

  /**
   * Align any epoch to the same or previous anchor weekday at local midnight.
   *
   * This prevents runtime failures when historical user settings contain a
   * non-anchor epoch date (for example, legacy settings drift).
   */
  private static function alignEpochToAnchor(\DateTimeImmutable $epoch, string $anchor): \DateTimeImmutable
  {
    $anchorDow = self::isoDowForAnchor($anchor);
    $epochDow = self::isoDow($epoch);
    if ($epochDow === $anchorDow) {
      return $epoch;
    }

    $delta = ($epochDow - $anchorDow + 7) % 7;
    return $epoch->modify("-{$delta} days");
  }

  /**
   * Semi-monthly span for the given date.
   * First half: 1..15  (end exclusive = 16)
   * Second half: 16..end-of-month  (end exclusive = first day of next month).
   *
   * @param \DateTimeImmutable $dt Local midnight date
   *
   * @return array{0:\DateTimeImmutable,1:\DateTimeImmutable}
   */
  private static function semiMonthlySpan(\DateTimeImmutable $dt): array
  {
    $day = (int) $dt->format('j');
    if ($day <= 15) {
      $start = $dt->modify('first day of this month');
      $end = $start->modify('+15 days');

      return [$start, $end];
    }
    $start = $dt->modify('first day of this month')->modify('+15 days');
    $end = $start->modify('first day of next month');

    return [$start, $end];
  }

  /**
   * Make the fiscal-year anchor instance for a target year using this anchor's month/day.
   * Anchor is interpreted in the class timezone and normalized to midnight.
   */
  private function makeAnchorForYear(int $targetYear): \DateTimeImmutable
  {
    $tz = new \DateTimeZone($this->timezone);
    $stored = new \DateTimeImmutable($this->anchor, $tz);
    $m = (int) $stored->format('n');
    $d = (int) $stored->format('j');

    return (new \DateTimeImmutable(
      sprintf('%04d-%02d-%02d', $targetYear, $m, $d),
      $tz
    ))->setTime(0, 0);
  }

  /**
   * Count whole calendar months from a to b ignoring day-of-month details beyond month boundaries.
   * Example: 2025-01-16 → 2025-03-01 = 2; 2025-01-16 → 2025-01-31 = 0.
   */
  private function monthsBetween(\DateTimeImmutable $a, \DateTimeImmutable $b): int
  {
    $ay = (int) $a->format('Y');
    $am = (int) $a->format('n');
    $by = (int) $b->format('Y');
    $bm = (int) $b->format('n');

    return (($by - $ay) * 12) + ($bm - $am);
  }
}

