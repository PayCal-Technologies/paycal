<?php declare(strict_types=1);

namespace PayCal\Domain;

use DateTimeImmutable;

/**
 * Worked example of a 14/7 rotation and a 20/10 rotation.
 *
 * Figures come from RotationTemplate, OvertimeRules, VacationPayEngine,
 * and Taxes. The wage, allowance, and travel hours are example inputs.
 */
final class RotationPayExplainer
{
  public const EXAMPLE_WAGE_CENTS = 4000;
  public const EXAMPLE_LOA_CENTS = 15000;
  public const EXAMPLE_TRAVEL_HOURS_EACH_WAY = 8.0;
  public const EXAMPLE_PROVINCE = 'AB';
  public const EXAMPLE_YEAR = 2026;

  /**
   * @return array<string, mixed>
   */
  public static function comparison(): array
  {
    $start = new DateTimeImmutable('2026-01-05');
    $end = $start->modify('+365 days');
    $window = ForecastWindow::projectTotal($start, $end);

    return [
      'label' => 'Example',
      'province' => self::EXAMPLE_PROVINCE,
      'year' => self::EXAMPLE_YEAR,
      'wage_cents' => self::EXAMPLE_WAGE_CENTS,
      'loa_cents_per_onsite_day' => self::EXAMPLE_LOA_CENTS,
      'travel_hours_each_way' => self::EXAMPLE_TRAVEL_HOURS_EACH_WAY,
      'window_start' => $start->format('Y-m-d'),
      'window_end_exclusive' => $end->format('Y-m-d'),
      'assumptions' => [
        'The window is 365 days starting Monday 5 January 2026, the first day of a work block.',
        '14/7 uses the app rotation of 14 work days, 7 rest days, and 8 hours on each work day.',
        '20/10 uses the app rotation of 20 work days, 10 rest days, and 10 hours on each work day.',
        'Overtime uses the Alberta rule: the greater of hours over 8 in a day or hours over 44 in a week, at 1.5 times.',
        'Weeks are Sunday through Saturday, the same week Work uses when it recalculates stored hours.',
        'Living-out allowance is an example $150 for each on-site day. It is not a statutory amount.',
        'Travel is an example 8 hours each way, once per hitch, paid at the regular rate and not put through the overtime rule.',
        'Tax is the Alberta 2026 Taxes estimate, annualized from this window the same way CrewForecastEngine estimates tax. It is not a CRA payroll deduction.',
        'Vacation pay is the Alberta minimum 4% accrual on regular wage and travel pay, excluding overtime. It is shown separately and is not inside the net figure. The allowance is not included in that accrual.',
      ],
      'rotations' => [
        '14/7' => self::project('14/7', $window),
        '20/10' => self::project('20/10', $window),
      ],
    ];
  }

  /**
   * @return array<string, int|float|string>
   */
  private static function project(string $pattern, ForecastWindow $window): array
  {
    $rotation = RotationTemplate::fromPattern($pattern, $window->start);
    $dailyHours = [];
    $cursor = $window->start;
    $weekKey = $cursor->modify('sunday this week')->format('Y-m-d');
    if ($cursor->format('w') === '0') {
      $weekKey = $cursor->format('Y-m-d');
    }
    $weeks = [];
    $onsiteDays = 0;
    $hitches = 0;
    $previousWasWork = false;

    while ($cursor < $window->end) {
      $thisWeek = $cursor->modify('sunday this week')->format('Y-m-d');
      if ($cursor->format('w') === '0') {
        $thisWeek = $cursor->format('Y-m-d');
      }
      if ($thisWeek !== $weekKey) {
        $weeks[] = $dailyHours;
        $dailyHours = [];
        $weekKey = $thisWeek;
      }

      $isWork = $rotation->isWorkDay($cursor);
      if ($isWork && !$previousWasWork) {
        $hitches++;
      }
      $previousWasWork = $isWork;
      if ($isWork) {
        $onsiteDays++;
      }
      $index = (int) $cursor->format('w');
      $dailyHours[$index] = ($dailyHours[$index] ?? 0.0) + ($isWork ? $rotation->hoursPerDay : 0.0);
      $cursor = $cursor->modify('+1 day');
    }
    if ($dailyHours !== []) {
      $weeks[] = $dailyHours;
    }

    $regular = 0.0;
    $overtime = 0.0;
    foreach ($weeks as $week) {
      $hours = [];
      for ($day = 0; $day < 7; $day++) {
        $hours[] = (float) ($week[$day] ?? 0.0);
      }
      $allocated = OvertimeRules::allocateWeek($hours, self::EXAMPLE_PROVINCE);
      $regular += $allocated['regular'];
      $overtime += $allocated['overtime'];
    }

    $wage = self::EXAMPLE_WAGE_CENTS;
    $regularPay = (int) round($regular * $wage);
    $overtimePay = (int) round($overtime * $wage * 1.5);
    $travelHours = $hitches * self::EXAMPLE_TRAVEL_HOURS_EACH_WAY * 2;
    $travelPay = (int) round($travelHours * $wage);
    $loaPay = $onsiteDays * self::EXAMPLE_LOA_CENTS;
    $gross = $regularPay + $overtimePay + $travelPay + $loaPay;
    $tax = self::estimateTax($gross, $window);
    $vacationBase = $regularPay + $travelPay;
    $vacation = VacationPayEngine::accrualCents(self::EXAMPLE_PROVINCE, $vacationBase, 0.0, self::EXAMPLE_YEAR);

    return [
      'pattern' => $pattern,
      'hours_per_work_day' => $rotation->hoursPerDay,
      'onsite_days' => $onsiteDays,
      'hitches' => $hitches,
      'regular_hours' => round($regular, 2),
      'overtime_hours' => round($overtime, 2),
      'travel_hours' => round($travelHours, 2),
      'regular_pay_cents' => $regularPay,
      'overtime_pay_cents' => $overtimePay,
      'travel_pay_cents' => $travelPay,
      'loa_cents' => $loaPay,
      'gross_cents' => $gross,
      'estimated_tax_cents' => $tax,
      'estimated_net_cents' => $gross - $tax,
      'vacation_accrual_cents' => $vacation,
    ];
  }

  /** Estimate income tax for the illustrated rotation window. */
  private static function estimateTax(int $grossCents, ForecastWindow $window): int
  {
    if ($grossCents <= 0) {
      return 0;
    }

    $windowDays = max(1, $window->days());
    $annualCents = (int) round($grossCents * (365.0 / $windowDays));
    $taxes = new Taxes('Alberta', self::EXAMPLE_YEAR);
    $annual = $taxes->calculateTaxesCents($annualCents);

    return (int) round(((int) $annual['totalDeductions']) * ($windowDays / 365.0));
  }
}
