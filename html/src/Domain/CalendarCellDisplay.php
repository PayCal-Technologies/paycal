<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Calendar cell decoration helpers (pay-period ribbon classes, work-entry badges).
 */
final class CalendarCellDisplay
{
  /**
   * Work-entry badge visibility from user preferences.
   *
   * @return array{hours: bool, regular: bool, overtime: bool, living_out: bool, travel: bool}
   */
  public static function workEntryFieldPrefs(User $user): array
  {
    return [
      'hours' => $user->calendar_work_entry_fields_hours,
      'regular' => $user->calendar_work_entry_fields_regular,
      'overtime' => $user->calendar_work_entry_fields_overtime,
      'living_out' => $user->calendar_work_entry_fields_living_out,
      'travel' => $user->calendar_work_entry_fields_travel,
    ];
  }

  /**
   * Normalize work-entry badge prefs from grid meta (defaults all enabled).
   *
   * @param array<string, mixed>|null $meta
   * @return array{hours: bool, regular: bool, overtime: bool, living_out: bool, travel: bool}
   */
  public static function workEntryFieldPrefsFromMeta(?array $meta = null): array
  {
    $defaults = [
      'hours' => true,
      'regular' => true,
      'overtime' => true,
      'living_out' => true,
      'travel' => true,
    ];

    if ($meta === null) {
      return $defaults;
    }

    return [
      'hours' => array_key_exists('hours', $meta) ? (bool) $meta['hours'] : $defaults['hours'],
      'regular' => array_key_exists('regular', $meta) ? (bool) $meta['regular'] : $defaults['regular'],
      'overtime' => array_key_exists('overtime', $meta) ? (bool) $meta['overtime'] : $defaults['overtime'],
      'living_out' => array_key_exists('living_out', $meta) ? (bool) $meta['living_out'] : $defaults['living_out'],
      'travel' => array_key_exists('travel', $meta) ? (bool) $meta['travel'] : $defaults['travel'],
    ];
  }

  /**
   * Build visible work-entry badge values and spoken metrics for a calendar cell.
   *
   * @param array{hours?: bool, regular?: bool, overtime?: bool, living_out?: bool, travel?: bool} $fieldPrefs
   * @param callable(float): string|null $formatValue
   * @return array{fields: list<string>, spokenMetrics: list<string>}
   */
  public static function buildWorkEntryDisplayFields(
    float $regularHours,
    float $overtimeHours,
    float $livingOut,
    float $travelHours,
    array $fieldPrefs,
    bool $isEncryptedPlaceholder = false,
    ?callable $formatValue = null,
    ?float $totalHours = null,
  ): array {
    $format = $formatValue ?? static function (float $value): string {
      $formatted = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
      return $formatted !== '' ? $formatted : '0';
    };

    if ($isEncryptedPlaceholder) {
      $enabledCount = count(array_filter([
        !empty($fieldPrefs['hours']),
        !empty($fieldPrefs['regular']),
        !empty($fieldPrefs['overtime']),
        !empty($fieldPrefs['living_out']),
        !empty($fieldPrefs['travel']),
      ]));

      return [
        'fields' => $enabledCount > 0 ? array_fill(0, $enabledCount, '--') : [],
        'spokenMetrics' => [],
      ];
    }

    $fields = [];
    $spokenMetrics = [];

    if (!empty($fieldPrefs['hours'])) {
      $value = $format($totalHours ?? ($regularHours + $overtimeHours));
      $fields[] = $value;
      $spokenMetrics[] = sprintf('%s total hours', $value);
    }
    if (!empty($fieldPrefs['regular'])) {
      $value = $format($regularHours);
      $fields[] = $value;
      $spokenMetrics[] = sprintf('%s regular hours', $value);
    }
    if (!empty($fieldPrefs['overtime'])) {
      $value = $format($overtimeHours);
      $fields[] = $value;
      $spokenMetrics[] = sprintf('%s overtime hours', $value);
    }
    if (!empty($fieldPrefs['living_out'])) {
      $value = $format($livingOut);
      $fields[] = $value;
      $spokenMetrics[] = sprintf('%s living out allowance', $value);
    }
    if (!empty($fieldPrefs['travel'])) {
      $value = $format($travelHours);
      $fields[] = $value;
      $spokenMetrics[] = sprintf('%s travel hours', $value);
    }

    return [
      'fields' => $fields,
      'spokenMetrics' => $spokenMetrics,
    ];
  }

  /**
   * CSS classes for pay-period boundary highlighting on a calendar day cell.
   */
  public static function payPeriodClasses(User $user, string $dateYmd, ?string $prevDateYmd = null, ?string $nextDateYmd = null): string
  {
    if (!$user->calendar_highlight_pay_period || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateYmd)) {
      return '';
    }

    try {
      $zone = new \DateTimeZone($user->timezone ?: 'America/Edmonton');
      $date = new \DateTimeImmutable($dateYmd, $zone);
      $period = PayPeriodGenerator::resolveForDate($user, $date);
      if ($period === null) {
        return '';
      }

      $info = $period->getPayPeriodForDate($date);
      $start = $info['start']->format('Y-m-d');
      $end = $info['end']->format('Y-m-d');

      $classes = ['calendar_pp_in_period'];
      $prevInPeriod = $prevDateYmd !== null && self::isDateInSamePeriod($user, $dateYmd, $prevDateYmd);
      $nextInPeriod = $nextDateYmd !== null && self::isDateInSamePeriod($user, $dateYmd, $nextDateYmd);

      if (!$prevInPeriod) {
        $classes[] = 'calendar_pp_start';
      }
      if (!$nextInPeriod) {
        $classes[] = 'calendar_pp_end';
      }
      if ($dateYmd === $start) {
        $classes[] = 'calendar_pp_period_start';
      }
      if ($dateYmd === $end) {
        $classes[] = 'calendar_pp_period_end';
      }

      return implode(' ', $classes);
    } catch (\Throwable) {
      return '';
    }
  }

  /**
   * Is date in same period.
   */
  private static function isDateInSamePeriod(User $user, string $dateYmd, string $otherYmd): bool
  {
    if ($dateYmd === $otherYmd) {
      return true;
    }

    try {
      $zone = new \DateTimeZone($user->timezone ?: 'America/Edmonton');
      $period = PayPeriodGenerator::resolveForDate($user, new \DateTimeImmutable($dateYmd, $zone));
      if ($period === null) {
        return false;
      }

      $other = new \DateTimeImmutable($otherYmd, $zone);
      $resolved = PayPeriodGenerator::resolveForDate($user, $other);
      if ($resolved === null) {
        return false;
      }

      $a = $period->getPayPeriodForDate(new \DateTimeImmutable($dateYmd, $zone));
      $b = $resolved->getPayPeriodForDate($other);

      return $a['start']->format('Y-m-d') === $b['start']->format('Y-m-d');
    } catch (\Throwable) {
      return false;
    }
  }
}
