<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Public next-cheque estimate.
 *
 * Tax uses Taxes and the same annualize-and-prorate approach as
 * CrewForecastEngine. Overtime hours are entered by the visitor.
 * Vacation pay is an accrual and is not subtracted from the cheque.
 */
final class PublicPayTools
{
  /** @var array<string, string> */
  public const SLUGS = [
    'alberta' => 'AB',
    'british-columbia' => 'BC',
    'manitoba' => 'MB',
    'new-brunswick' => 'NB',
    'newfoundland-and-labrador' => 'NL',
    'nova-scotia' => 'NS',
    'northwest-territories' => 'NT',
    'nunavut' => 'NU',
    'ontario' => 'ON',
    'prince-edward-island' => 'PE',
    'quebec' => 'QC',
    'saskatchewan' => 'SK',
    'yukon' => 'YT',
  ];

  /** @var array<int, int> */
  private const PERIODS = [
    52 => 52,
    26 => 26,
    24 => 24,
    12 => 12,
  ];

  /**
   * @param array<string, mixed> $input
   * @return array<string, mixed>
   */
  public static function nextCheque(array $input): array
  {
    $province = strtoupper(trim(PayRuleData::text($input['province'] ?? 'AB')));
    if (!in_array($province, self::SLUGS, true)) {
      $province = 'AB';
    }
    $year = (int) PayRuleData::number($input['year'] ?? (int) date('Y'));
    if ($year < 2020) {
      $year = 2020;
    }
    if ($year > 2026) {
      $year = 2026;
    }
    $dateInput = PayRuleData::text($input['pay_date'] ?? '');
    $payDate = $dateInput === ''
      ? new \DateTimeImmutable($year === (int) date('Y') ? date('Y-m-d') : $year . '-12-31')
      : \DateTimeImmutable::createFromFormat('!Y-m-d', $dateInput);
    if ($payDate === false || ($dateInput !== '' && $payDate->format('Y-m-d') !== $dateInput) || (int) $payDate->format('Y') < 2020 || (int) $payDate->format('Y') > 2026) {
      throw new \InvalidArgumentException('Choose a valid pay date from 2020 through 2026.');
    }
    $year = (int) $payDate->format('Y');
    $periods = (int) PayRuleData::number($input['periods'] ?? 26);
    if (!isset(self::PERIODS[$periods])) {
      $periods = 26;
    }

    $wageCents = self::moneyToCents($input['wage'] ?? 0);
    $regularHours = self::hours($input['regular_hours'] ?? 0);
    $overtimeHours = self::hours($input['overtime_hours'] ?? 0);
    $doubletimeHours = self::hours($input['doubletime_hours'] ?? 0);
    $travelHours = self::hours($input['travel_hours'] ?? 0);
    $allowanceCents = self::moneyToCents($input['allowance'] ?? 0);
    $years = self::hours($input['years_of_service'] ?? 0);

    $regularPay = (int) round($regularHours * $wageCents);
    $minimumOtRate = OvertimeRules::hourlyOvertimeRateCents($province, $wageCents, $payDate);
    $overtimeRate = max($minimumOtRate, self::moneyToCents($input['overtime_rate'] ?? 0));
    $overtimePay = (int) round($overtimeHours * $overtimeRate);
    $doubletimePay = (int) round($doubletimeHours * $wageCents * 2);
    $travelPay = (int) round($travelHours * $wageCents);
    $gross = $regularPay + $overtimePay + $doubletimePay + $travelPay + $allowanceCents;

    $annualGross = $gross * $periods;
    $provinceName = self::provinceName($province);
    $taxes = new Taxes($provinceName, $year);
    $annual = $taxes->calculateTaxesCents($annualGross);
    $deductions = [];
    foreach ($annual as $key => $cents) {
      $deductions[$key] = (int) round(((int) $cents) / $periods);
    }
    $tax = $deductions['totalDeductions'];
    $net = $gross - $tax;

    $vacationBase = $regularPay + $travelPay + (in_array($province, ['AB', 'MB'], true) ? 0 : $overtimePay + $doubletimePay);
    $vacationYear = $province === 'PE' && $payDate->format('Y-m-d') < '2026-06-30' && $year === 2026 ? 2025 : $year;
    $vacationRule = VacationPayEngine::rule($province, $vacationYear);
    $vacationTier = VacationPayEngine::tier($province, $years, $vacationYear);
    $vacation = VacationPayEngine::accrualCents($province, $vacationBase, $years, $vacationYear);
    $overtime = OvertimeRules::resolve($province);

    return [
      'province' => $province,
      'province_name' => PayRuleData::text($overtime['name'] ?? $provinceName),
      'year' => $year,
      'pay_date' => $payDate->format('Y-m-d'),
      'overtime_rate_cents' => $overtimeRate,
      'periods' => $periods,
      'wage_cents' => $wageCents,
      'regular_hours' => $regularHours,
      'overtime_hours' => $overtimeHours,
      'doubletime_hours' => $doubletimeHours,
      'travel_hours' => $travelHours,
      'allowance_cents' => $allowanceCents,
      'regular_pay_cents' => $regularPay,
      'overtime_pay_cents' => $overtimePay,
      'doubletime_pay_cents' => $doubletimePay,
      'travel_pay_cents' => $travelPay,
      'gross_cents' => $gross,
      'deductions' => $deductions,
      'tax_cents' => $tax,
      'net_cents' => $net,
      'vacation_pay_cents' => $vacation,
      'vacation_label' => $vacationTier['label'],
      'vacation_verified' => (bool) ($vacationRule['verified'] ?? false),
      'overtime_summary' => OvertimeRules::summary($province),
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function jurisdiction(string $code, int $year = 2026): array
  {
    $overtime = OvertimeRules::resolve($code);
    $resolved = PayRuleData::text($overtime['code']);
    $vacation = VacationPayEngine::rule($resolved, $year);
    $holiday = StatHolidayEngine::rule($resolved);
    $slug = '';
    foreach (self::SLUGS as $candidate => $province) {
      if ($province === $resolved) {
        $slug = $candidate;
        break;
      }
    }

    return [
      'code' => $resolved,
      'slug' => $slug,
      'name' => PayRuleData::text($overtime['name'] ?? $resolved),
      'year' => $year,
      'overtime' => $overtime,
      'overtime_summary' => OvertimeRules::summary($resolved),
      'vacation' => $vacation,
      'vacation_tiers' => $vacation['tiers'] ?? [],
      'holiday' => $holiday,
      'holidays' => StatHolidayEngine::holidays($resolved, $year),
    ];
  }

  /** Resolve a public jurisdiction slug to its rule code. */
  public static function codeForSlug(string $slug): ?string
  {
    return self::SLUGS[$slug] ?? null;
  }

  /** Validate a public money input and convert it to cents. */
  private static function moneyToCents(mixed $value): int
  {
    $amount = PayRuleData::number($value);
    if ($amount < 0) {
      $amount = 0;
    }
    if ($amount > 100000) {
      $amount = 100000;
    }

    return (int) round($amount * 100);
  }

  /** Validate a public hours input as a nonnegative finite value. */
  private static function hours(mixed $value): float
  {
    $hours = PayRuleData::number($value);
    if ($hours < 0) {
      return 0.0;
    }
    if ($hours > 1000) {
      return 1000.0;
    }

    return $hours;
  }

  /** Return the public display name for a jurisdiction code. */
  private static function provinceName(string $code): string
  {
    $names = [
      'AB' => 'Alberta',
      'BC' => 'British Columbia',
      'MB' => 'Manitoba',
      'NB' => 'New Brunswick',
      'NL' => 'Newfoundland and Labrador',
      'NS' => 'Nova Scotia',
      'NT' => 'Northwest Territories',
      'NU' => 'Nunavut',
      'ON' => 'Ontario',
      'PE' => 'Prince Edward Island',
      'QC' => 'Quebec',
      'SK' => 'Saskatchewan',
      'YT' => 'Yukon',
    ];

    return $names[$code] ?? 'Alberta';
  }
}
