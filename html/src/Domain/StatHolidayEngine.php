<?php declare(strict_types=1);

namespace PayCal\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

/**
 * Statutory holiday lists and general holiday-pay formulas.
 *
 * Eligibility is described and not decided. Dated holidays are returned
 * only for years present in StatHolidayData.json.
 */
final class StatHolidayEngine
{
  /** @var array<string, mixed>|null */
  private static ?array $data = null;

  /** Prevent construction of the static holiday service. */
  private function __construct()
  {
  }

  /**
   * @return array<string, mixed>
   */
  public static function data(): array
  {
    if (self::$data === null) {
      $decoded = json_decode((string) file_get_contents(__DIR__ . '/StatHolidayData.json'), true);
      if (!is_array($decoded) || !isset($decoded['rules']) || !is_array($decoded['rules'])) {
        throw new RuntimeException('Statutory holiday data is missing or invalid.');
      }
      self::$data = PayRuleData::object($decoded);
    }

    return self::$data;
  }

  /**
   * @return array<string, mixed>
   */
  public static function rule(string $code): array
  {
    $normalized = self::code($code);
    $rules = PayRuleData::entries(self::data()['rules']);
    if (!isset($rules[$normalized]) || !is_array($rules[$normalized])) {
      throw new InvalidArgumentException('Unknown statutory-holiday jurisdiction: ' . $code);
    }

    $rule = PayRuleData::object($rules[$normalized]);
    $rule['code'] = $normalized;

    return $rule;
  }

  /**
   * @return list<array{date: string, name: string}>
   */
  public static function holidays(string $code, int $year): array
  {
    $normalized = self::code($code);
    $allYears = PayRuleData::entries(self::data()['holidays']);
    $byYear = $allYears[(string) $year] ?? [];
    if (!is_array($byYear)) {
      return [];
    }
    $rows = $byYear[$normalized] ?? [];
    if (!is_array($rows)) {
      return [];
    }

    $holidays = [];
    foreach ($rows as $row) {
      if (!is_array($row) || (!isset($row['date']) || !isset($row['name']))) {
        continue;
      }
      $holidays[] = [
        'date' => PayRuleData::text($row['date']),
        'name' => PayRuleData::text($row['name']),
      ];
    }

    return $holidays;
  }

  /**
   * @return list<array{date: string, name: string}>
   */
  public static function between(string $code, DateTimeImmutable $start, DateTimeImmutable $end): array
  {
    $found = [];
    $startDate = $start->format('Y-m-d');
    $endDate = $end->format('Y-m-d');
    $year = (int) $start->format('Y');
    $lastYear = (int) $end->format('Y');
    for (; $year <= $lastYear; $year++) {
      foreach (self::holidays($code, $year) as $holiday) {
        if ($holiday['date'] >= $startDate && $holiday['date'] < $endDate) {
          $found[] = $holiday;
        }
      }
    }

    return $found;
  }

  /** Check the published holiday calendar without inferring employee eligibility. */
  public static function isHoliday(string $code, string $date): bool
  {
    $year = (int) substr($date, 0, 4);
    foreach (self::holidays($code, $year) as $holiday) {
      if ($holiday['date'] === $date) {
        return true;
      }
    }

    return false;
  }

  /**
   * General holiday pay for a day not described as hours worked.
   *
   * average_daily uses eligible wages divided by days worked.
   * wages_over_20 divides eligible wages by 20.
   * five_percent is 5% of eligible wages.
   */
  public static function holidayPayCents(string $code, int $eligibleWagesCents, int $daysWorked = 0, ?int $regularDayPayCents = null): int
  {
    if ($eligibleWagesCents < 0 || $daysWorked < 0 || ($regularDayPayCents !== null && $regularDayPayCents < 0)) {
      throw new InvalidArgumentException('Holiday pay inputs cannot be negative.');
    }

    $method = PayRuleData::text(self::rule($code)['pay_method'] ?? '');

    return match ($method) {
      'average_daily' => $daysWorked > 0 ? (int) round($eligibleWagesCents / $daysWorked) : 0,
      'wages_over_20' => (int) round($eligibleWagesCents / 20),
      'five_percent' => (int) round($eligibleWagesCents * 5 / 100),
      'regular_day_or_five_percent' => $regularDayPayCents ?? (int) round($eligibleWagesCents * 5 / 100),
      'regular_day_or_ten_percent' => $regularDayPayCents ?? (int) round($eligibleWagesCents / 10),
      'regular_day_or_average' => $regularDayPayCents ?? ($daysWorked > 0 ? (int) round($eligibleWagesCents / $daysWorked) : throw new InvalidArgumentException('Normal day pay or the statutory average inputs are required.')),
      default => throw new RuntimeException('Unknown holiday pay method: ' . $method),
    };
  }

  /**
   * Premium for hours worked on the holiday, plus holiday pay when the rule stacks it.
   */
  public static function workedHolidayCents(
    string $code,
    int $holidayPayCents,
    float $hoursWorked,
    int $hourlyRateCents
  ): int {
    if ($holidayPayCents < 0 || $hoursWorked < 0 || $hourlyRateCents < 0) {
      throw new InvalidArgumentException('Worked-holiday inputs cannot be negative.');
    }

    $rule = self::rule($code);
    $multiplier = ((int) PayRuleData::number($rule['worked_multiplier_basis_points'] ?? 15000)) / 10000;
    $doubleAfter = $rule['double_after_hours'] ?? null;
    $premium = 0;
    if ($doubleAfter !== null) {
      $doubleAfter = PayRuleData::number($doubleAfter);
      $timeAndHalfHours = min($hoursWorked, $doubleAfter);
      $doubleHours = max(0.0, $hoursWorked - $doubleAfter);
      $premium = (int) round(($timeAndHalfHours * $multiplier + $doubleHours * 2) * $hourlyRateCents);
    } else {
      $premium = (int) round($hoursWorked * $multiplier * $hourlyRateCents);
    }

    if (!empty($rule['stacks_holiday_pay'])) {
      return $premium + $holidayPayCents;
    }

    return $premium;
  }

  /** Normalize and validate the holiday jurisdiction code. */
  private static function code(string $code): string
  {
    $rule = OvertimeRules::resolve($code);
    $resolved = PayRuleData::text($rule['code'] ?? '');
    if ($resolved === '' || $resolved === OvertimeRules::LEGACY) {
      throw new InvalidArgumentException('Unknown statutory-holiday jurisdiction: ' . $code);
    }

    return $resolved;
  }
}
