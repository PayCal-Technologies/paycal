<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Builds personal earnings forecast view-models from profile defaults, scheduled
 * work, YTD context, and optional calculator overrides.
 *
 * Calculator overrides are never persisted to the user profile.
 * All monetary math uses integer cents.
 */
final class ForecastProjectionService
{
  /** @var array<string, string> */
  private const PROVINCE_CODES = [
    'AB' => 'Alberta',
    'BC' => 'British Columbia',
    'SK' => 'Saskatchewan',
    'MB' => 'Manitoba',
    'ON' => 'Ontario',
    'QC' => 'Quebec',
    'NS' => 'Nova Scotia',
    'NB' => 'New Brunswick',
    'NL' => 'Newfoundland',
    'PE' => 'Prince Edward Island',
    'YT' => 'Yukon',
    'NT' => 'Northwest Territories',
    'NU' => 'Nunavut',
  ];

  /**
   * @param array<string, mixed> $calculatorOverrides
   * @return array<string, mixed>
   */
  public function buildState(
    User $user,
    ForecastScenario $activeScenario = ForecastScenario::Normal,
    array $calculatorOverrides = [],
    ?\DateTimeImmutable $asOf = null,
  ): array {
    $today = ($asOf ?? new \DateTimeImmutable('today'))->setTime(0, 0, 0);
    $profileDefaults = $this->buildProfileDefaults($user);
    $scheduledPeriods = $this->buildScheduledContext($user, $today);
    $ytdContext = $this->buildYtdContext($user, (int) $today->format('Y'));

    $effective = $this->mergeEffectiveInputs(
      $profileDefaults,
      $scheduledPeriods,
      $ytdContext,
      $calculatorOverrides,
      $activeScenario,
    );

    $canCalculate = is_int($effective['wage_rate_cents']) && $effective['wage_rate_cents'] > 0;
    $projectionResult = $canCalculate
      ? $this->buildProjectionResult($effective, $today)
      : $this->emptyProjectionResult();

    $assumptionSources = $this->buildAssumptionSources(
      $profileDefaults,
      $scheduledPeriods,
      $ytdContext,
      $calculatorOverrides,
    );

    $confidence = $this->resolveOverallConfidence($assumptionSources, $ytdContext, $scheduledPeriods);

    return [
      'forecast_state' => [
        'profile_defaults'      => $profileDefaults,
        'scheduled_periods'     => $scheduledPeriods,
        'ytd_context'           => $ytdContext,
        'calculator_overrides'  => $calculatorOverrides,
        'active_scenario'       => $activeScenario->value,
        'projection_result'     => $projectionResult,
        'assumption_sources'    => $assumptionSources,
        'confidence'            => $confidence->value,
      ],
      'can_calculate'   => $canCalculate,
      'setup_required'  => !$canCalculate,
    ];
  }

  /**
   * @param array<string, mixed> $overrides
   * @return array<string, mixed>
   */
  public function preview(
    User $user,
    array $overrides = [],
    ForecastScenario $scenario = ForecastScenario::Normal,
    ?\DateTimeImmutable $asOf = null,
  ): array {
    return $this->buildState($user, $scenario, $overrides, $asOf);
  }

  /**
   * @return array<string, mixed>
   */
  private function buildProfileDefaults(User $user): array
  {
    $rateRaw = self::floatValue($user->pay_rate, 0.0);
    $wageRateCents = $rateRaw > 0.0 ? (int) round($rateRaw * 100) : null;

    $hoursRaw = self::floatValue($user->default_hours, 8.0);
    $hoursPerDay = ($hoursRaw > 0.0 && $hoursRaw <= 24.0) ? $hoursRaw : 8.0;
    $regularHoursWeek = $hoursPerDay * 5.0;

    $loaRaw = self::floatValue($user->default_living_out_allowance, 0.0);
    $loaCents = (int) round($loaRaw * 100);

    $province = strtoupper(trim($user->province !== '' ? $user->province : 'AB'));
    if (!array_key_exists($province, self::PROVINCE_CODES)) {
      $province = 'AB';
    }

    $payFrequency = strtolower(trim(self::stringValue($user->pay_frequency, 'biweekly')));
    $payPeriodDays = match ($payFrequency) {
      'weekly'      => 7,
      'biweekly'    => 14,
      'semimonthly' => 15,
      'monthly'     => 30,
      default       => 14,
    };

    $anchorRaw = trim($user->pay_period_start);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $anchorRaw)) {
      $anchorDate = $anchorRaw;
    } else {
      $anchorDate = (new \DateTimeImmutable('monday this week'))->format('Y-m-d');
    }

    return [
      'wage_rate_cents'      => $wageRateCents,
      'regular_hours_week'   => $regularHoursWeek,
      'overtime_hours_week'  => 0.0,
      'loa_per_day_cents'    => $loaCents,
      'travel_hours_week'    => 0.0,
      'province'             => $province,
      'pay_frequency'        => $payFrequency,
      'pay_period_days'      => $payPeriodDays,
      'anchor_date'          => $anchorDate,
      'hours_per_day'        => $hoursPerDay,
      'rotation_work_days'   => 5,
      'rotation_rest_days'   => 2,
    ];
  }

  /**
   * @return array<string, mixed>
   */
  private function buildScheduledContext(User $user, \DateTimeImmutable $today): array
  {
    $end = $today->modify('+60 days');
    $userUUID = $user->user_uuid !== '' ? $user->user_uuid : User::currentUUID();

    $totalRegular = 0.0;
    $totalOvertime = 0.0;
    $totalTravel = 0.0;
    $totalLoaCents = 0;
    $wageSamples = [];
    $dayCount = 0;

    foreach (Work::getWorkInRange($today, $end, $userUUID) as $row) {
      $dateRaw = trim(self::stringValue($row['date'] ?? ''));
      if ($dateRaw === '' || $dateRaw < $today->format('Y-m-d')) {
        continue;
      }

      $regular = self::floatValue($row['regular_hours'] ?? $row['r'] ?? 0.0, 0.0);
      $overtime = self::floatValue($row['overtime_hours'] ?? $row['o'] ?? 0.0, 0.0);
      $travel = self::floatValue($row['travel_hours'] ?? $row['t'] ?? 0.0, 0.0);
      $loa = self::floatValue($row['living_out_allowance'] ?? $row['l'] ?? 0.0, 0.0);
      $wageNumeric = self::floatValue($row['wage'] ?? $row['w'] ?? 0.0, 0.0);
      $wage = $wageNumeric > 0.0 ? $wageNumeric : null;

      $totalRegular += max(0.0, $regular);
      $totalOvertime += max(0.0, $overtime);
      $totalTravel += max(0.0, $travel);
      $totalLoaCents += Money::dollarsToCents((string) max(0.0, $loa));
      if ($wage !== null && $wage > 0.0) {
        $wageSamples[] = (int) round($wage * 100);
      }
      ++$dayCount;
    }

    $weeks = max(1.0, $dayCount / 5.0);

    return [
      'future_work_days'     => $dayCount,
      'regular_hours_week'   => $dayCount > 0 ? round($totalRegular / $weeks, 2) : null,
      'overtime_hours_week'  => $dayCount > 0 ? round($totalOvertime / $weeks, 2) : null,
      'travel_hours_week'    => $dayCount > 0 ? round($totalTravel / $weeks, 2) : null,
      'loa_per_day_cents'    => $dayCount > 0 ? (int) round($totalLoaCents / max(1, $dayCount)) : null,
      'wage_rate_cents'      => $wageSamples !== [] ? (int) round(array_sum($wageSamples) / count($wageSamples)) : null,
      'has_scheduled_work'   => $dayCount > 0,
    ];
  }

  /**
   * @return array<string, mixed>
   */
  private function buildYtdContext(User $user, int $year): array
  {
    $start = new \DateTimeImmutable("{$year}-01-01");
    $today = new \DateTimeImmutable('today');
    $end = $today < new \DateTimeImmutable("{$year}-12-31") ? $today : new \DateTimeImmutable("{$year}-12-31");

    $userUUID = $user->user_uuid !== '' ? $user->user_uuid : User::currentUUID();
    $totals = Earnings::getTotalsForRange($start, $end, $userUUID);

    $grossCents = $totals['totals']['grossCents'];
    $hoursTotal = $totals['hours']['total'];
    $daysWorked = $totals['days'];

    return [
      'year'              => $year,
      'gross_cents'       => $grossCents,
      'net_cents'         => $totals['totals']['netCents'],
      'hours_total'       => $hoursTotal,
      'days_worked'       => $daysWorked,
      'avg_weekly_hours'  => $daysWorked > 0 ? round($hoursTotal / max(1.0, $daysWorked / 5.0), 2) : null,
    ];
  }

  /**
   * @param array<string, mixed> $profile
   * @param array<string, mixed> $scheduled
   * @param array<string, mixed> $ytd
   * @param array<string, mixed> $overrides
   * @return array<string, mixed>
   */
  private function mergeEffectiveInputs(
    array $profile,
    array $scheduled,
    array $ytd,
    array $overrides,
    ForecastScenario $scenario,
  ): array {
    $pick = function (string $field, mixed $profileVal, mixed $scheduledVal = null, mixed $inferredVal = null) use ($overrides): array {
      if (array_key_exists($field, $overrides) && $overrides[$field] !== null && $overrides[$field] !== '') {
        return ['value' => $overrides[$field], 'source' => ForecastAssumptionSource::Temporary];
      }
      if ($scheduledVal !== null) {
        return ['value' => $scheduledVal, 'source' => ForecastAssumptionSource::Scheduled];
      }
      if ($profileVal !== null) {
        return ['value' => $profileVal, 'source' => ForecastAssumptionSource::Saved];
      }
      if ($inferredVal !== null) {
        return ['value' => $inferredVal, 'source' => ForecastAssumptionSource::Estimated];
      }

      return ['value' => null, 'source' => ForecastAssumptionSource::Missing];
    };

    $wage = $pick('wage_rate_cents', $profile['wage_rate_cents'], $scheduled['wage_rate_cents'] ?? null);
    $regularWeek = $pick(
      'regular_hours_week',
      $profile['regular_hours_week'],
      $scheduled['regular_hours_week'] ?? null,
      $ytd['avg_weekly_hours'] ?? null,
    );
    $otWeek = $pick(
      'overtime_hours_week',
      $profile['overtime_hours_week'],
      $scheduled['overtime_hours_week'] ?? null,
    );
    $loa = $pick(
      'loa_per_day_cents',
      $profile['loa_per_day_cents'],
      $scheduled['loa_per_day_cents'] ?? null,
    );
    $travel = $pick(
      'travel_hours_week',
      $profile['travel_hours_week'],
      $scheduled['travel_hours_week'] ?? null,
    );
    $province = $pick('province', $profile['province']);
    $payFrequency = $pick('pay_frequency', $profile['pay_frequency']);
    $anchor = $pick('anchor_date', $profile['anchor_date']);
    $ytdGross = $pick('ytd_gross_cents', $ytd['gross_cents']);

    $effective = [
      'wage_rate_cents'      => self::nullableInt($wage['value']),
      'regular_hours_week'   => self::floatValue($regularWeek['value'], 8.0 * 5.0),
      'overtime_hours_week'  => self::floatValue($otWeek['value'], 0.0),
      'loa_per_day_cents'    => self::intValue($loa['value'], 0),
      'travel_hours_week'    => self::floatValue($travel['value'], 0.0),
      'province'             => strtoupper(self::stringValue($province['value'], 'AB')),
      'pay_frequency'        => strtolower(self::stringValue($payFrequency['value'], 'biweekly')),
      'pay_period_days'      => self::intValue($profile['pay_period_days'] ?? 14, 14),
      'anchor_date'          => self::stringValue($anchor['value'], (new \DateTimeImmutable('monday this week'))->format('Y-m-d')),
      'hours_per_day'        => self::floatValue($profile['hours_per_day'] ?? 8.0, 8.0),
      'rotation_work_days'   => self::intValue($profile['rotation_work_days'] ?? 5, 5),
      'rotation_rest_days'   => self::intValue($profile['rotation_rest_days'] ?? 2, 2),
      'ytd_gross_cents'      => self::intValue($ytdGross['value'], 0),
      'field_sources'        => [
        'wage_rate_cents'     => $wage['source']->value,
        'regular_hours_week'  => $regularWeek['source']->value,
        'overtime_hours_week' => $otWeek['source']->value,
        'loa_per_day_cents'   => $loa['source']->value,
        'travel_hours_week'   => $travel['source']->value,
        'province'            => $province['source']->value,
        'pay_frequency'       => $payFrequency['source']->value,
        'anchor_date'         => $anchor['source']->value,
        'ytd_gross_cents'     => $ytdGross['source']->value,
      ],
    ];

    return $this->applyScenarioModifiers($effective, $scenario);
  }

  /**
   * @param array<string, mixed> $effective
   * @return array<string, mixed>
   */
  private function applyScenarioModifiers(array $effective, ForecastScenario $scenario): array
  {
    if ($scenario === ForecastScenario::Conservative) {
      $effective['regular_hours_week'] = max(0.0, self::floatValue($effective['regular_hours_week'] ?? 0.0, 0.0) * 0.9);
      $effective['overtime_hours_week'] = 0.0;
    } elseif ($scenario === ForecastScenario::Overtime) {
      $effective['overtime_hours_week'] = max(self::floatValue($effective['overtime_hours_week'] ?? 0.0, 0.0), 4.0);
    }

    return $effective;
  }

  /**
   * @param array<string, mixed> $effective
   * @return array<string, mixed>
   */
  private function buildProjectionResult(array $effective, \DateTimeImmutable $today): array
  {
    $wageCents = self::intValue($effective['wage_rate_cents'] ?? 0, 0);
    if ($wageCents <= 0) {
      return $this->emptyProjectionResult();
    }

    $card = new WorkerRateCard(
      wageRateCents:          $wageCents,
      rateType:               RateType::Hourly,
      otRule:                 OtRule::Both,
      otThresholdDailyHours:  8.0,
      otThresholdWeeklyHours: 44.0,
      otMultiplierBasisPoints: 15000,
      perDiemCents:           self::intValue($effective['loa_per_day_cents'] ?? 0, 0),
      sitePremiumCents:       0,
      taxRegion:              self::stringValue($effective['province'] ?? 'AB', 'AB'),
    );

    $anchorDate = new \DateTimeImmutable(self::stringValue($effective['anchor_date'] ?? 'today', 'today'));
    $hoursPerDay = self::floatValue($effective['hours_per_day'] ?? 8.0, 8.0);
    $otHoursWeek = self::floatValue($effective['overtime_hours_week'] ?? 0.0, 0.0);
    if ($otHoursWeek > 0.0) {
      $hoursPerDay = min(24.0, $hoursPerDay + ($otHoursWeek / 5.0));
    }

    $rotation = new RotationTemplate(
      self::intValue($effective['rotation_work_days'] ?? 5, 5),
      self::intValue($effective['rotation_rest_days'] ?? 2, 2),
      $hoursPerDay,
      $anchorDate,
      '5/2',
    );

    $payPeriodDays = self::intValue($effective['pay_period_days'] ?? 14, 14);
    $windows = [
      'next_paycheck'    => ForecastWindow::nextPayPeriod($today, $payPeriodDays),
      'next_30_days'     => ForecastWindow::next30Days($today),
      'year_projection'  => ForecastWindow::yearProjection($today),
    ];

    $cards = [];
    $timeline = [];
    foreach ($windows as $key => $window) {
      $result = CrewForecastEngine::forecastWorker($card, $rotation, $window);
      $travelPayCents = (int) round((self::floatValue($effective['travel_hours_week'] ?? 0.0, 0.0) / 5.0)
        * $window->days() * $wageCents);
      $grossCents = $result->estimatedGrossCents + $travelPayCents;
      $taxCents = $this->estimateTaxWithYtd(
        self::stringValue($effective['province'] ?? 'AB', 'AB'),
        $grossCents,
        $window,
        self::intValue($effective['ytd_gross_cents'] ?? 0, 0),
      );
      $netCents = max(0, $grossCents - $taxCents);
      $hours = $result->regularHours + $result->otHours
        + (self::floatValue($effective['travel_hours_week'] ?? 0.0, 0.0) / 5.0) * $window->days();

      $cards[$key] = [
        'gross_cents'  => $grossCents,
        'tax_cents'    => $taxCents,
        'net_cents'    => $netCents,
        'hours'        => round($hours, 1),
        'window_days'  => $window->days(),
        'window_start' => $window->start->format('Y-m-d'),
        'window_end'   => $window->end->modify('-1 day')->format('Y-m-d'),
      ];

      $timeline[] = [
        'id'           => $key,
        'label_key'    => match ($key) {
          'next_paycheck'   => 'EARNINGS_FORECAST_NEXT_PAYCHECK',
          'next_30_days'    => 'EARNINGS_FORECAST_NEXT_30_DAYS',
          default           => 'EARNINGS_FORECAST_YEAR_PROJECTION',
        },
        'net_cents'    => $netCents,
        'gross_cents'  => $grossCents,
        'hours'        => round($hours, 1),
        'window_start' => $window->start->format('Y-m-d'),
        'window_end'   => $window->end->modify('-1 day')->format('Y-m-d'),
      ];
    }

    $maxNet = max(1, ...array_column($cards, 'net_cents'));
    foreach ($timeline as &$segment) {
      $segment['net_pct'] = (int) round(((int) $segment['net_cents'] / $maxNet) * 100);
    }
    unset($segment);

    return [
      'summary_cards' => $cards,
      'timeline'      => $timeline,
      'scenarios'     => $this->buildScenarioComparison($effective, $today, false),
    ];
  }

  /**
   * @param array<string, mixed> $effective
   * @return array<string, array<string, mixed>>
   */
  private function buildScenarioComparison(
    array $effective,
    \DateTimeImmutable $today,
    bool $includeCustomFromEffective = true,
  ): array {
    $out = [];
    foreach ([ForecastScenario::Conservative, ForecastScenario::Normal, ForecastScenario::Overtime] as $scenario) {
      $modified = $this->applyScenarioModifiers($effective, $scenario);
      $card = $this->buildSummaryCardsOnly($modified, $today)['next_30_days'] ?? [
        'net_cents' => 0,
        'gross_cents' => 0,
        'hours' => 0.0,
      ];
      $out[$scenario->value] = [
        'net_cents'   => self::intValue($card['net_cents'] ?? 0, 0),
        'gross_cents' => self::intValue($card['gross_cents'] ?? 0, 0),
        'hours'       => self::floatValue($card['hours'] ?? 0.0, 0.0),
      ];
    }

    if ($includeCustomFromEffective) {
      $customCard = $this->buildSummaryCardsOnly($effective, $today)['next_30_days'] ?? [
        'net_cents' => 0,
        'gross_cents' => 0,
        'hours' => 0.0,
      ];
    $out[ForecastScenario::Custom->value] = [
      'net_cents'   => self::intValue($customCard['net_cents'] ?? 0, 0),
      'gross_cents' => self::intValue($customCard['gross_cents'] ?? 0, 0),
      'hours'       => self::floatValue($customCard['hours'] ?? 0.0, 0.0),
    ];
    }

    return $out;
  }

  /**
   * @param array<string, mixed> $effective
   * @return array<string, array<string, mixed>>
   */
  private function buildSummaryCardsOnly(array $effective, \DateTimeImmutable $today): array
  {
    $wageCents = self::intValue($effective['wage_rate_cents'] ?? 0, 0);
    if ($wageCents <= 0) {
      return [];
    }

    $card = new WorkerRateCard(
      wageRateCents:          $wageCents,
      rateType:               RateType::Hourly,
      otRule:                 OtRule::Both,
      otThresholdDailyHours:  8.0,
      otThresholdWeeklyHours: 44.0,
      otMultiplierBasisPoints: 15000,
      perDiemCents:           self::intValue($effective['loa_per_day_cents'] ?? 0, 0),
      sitePremiumCents:       0,
      taxRegion:              self::stringValue($effective['province'] ?? 'AB', 'AB'),
    );

    $anchorDate = new \DateTimeImmutable(self::stringValue($effective['anchor_date'] ?? 'today', 'today'));
    $hoursPerDay = self::floatValue($effective['hours_per_day'] ?? 8.0, 8.0);
    $otHoursWeek = self::floatValue($effective['overtime_hours_week'] ?? 0.0, 0.0);
    if ($otHoursWeek > 0.0) {
      $hoursPerDay = min(24.0, $hoursPerDay + ($otHoursWeek / 5.0));
    }

    $rotation = new RotationTemplate(
      self::intValue($effective['rotation_work_days'] ?? 5, 5),
      self::intValue($effective['rotation_rest_days'] ?? 2, 2),
      $hoursPerDay,
      $anchorDate,
      '5/2',
    );

    $payPeriodDays = self::intValue($effective['pay_period_days'] ?? 14, 14);
    $windows = [
      'next_paycheck'   => ForecastWindow::nextPayPeriod($today, $payPeriodDays),
      'next_30_days'    => ForecastWindow::next30Days($today),
      'year_projection' => ForecastWindow::yearProjection($today),
    ];

    $cards = [];
    foreach ($windows as $key => $window) {
      $result = CrewForecastEngine::forecastWorker($card, $rotation, $window);
      $travelPayCents = (int) round((self::floatValue($effective['travel_hours_week'] ?? 0.0, 0.0) / 5.0)
        * $window->days() * $wageCents);
      $grossCents = $result->estimatedGrossCents + $travelPayCents;
      $taxCents = $this->estimateTaxWithYtd(
        self::stringValue($effective['province'] ?? 'AB', 'AB'),
        $grossCents,
        $window,
        self::intValue($effective['ytd_gross_cents'] ?? 0, 0),
      );
      $netCents = max(0, $grossCents - $taxCents);
      $hours = $result->regularHours + $result->otHours
        + (self::floatValue($effective['travel_hours_week'] ?? 0.0, 0.0) / 5.0) * $window->days();

      $cards[$key] = [
        'gross_cents' => $grossCents,
        'tax_cents'   => $taxCents,
        'net_cents'   => $netCents,
        'hours'       => round($hours, 1),
      ];
    }

    return $cards;
  }

  /**
   * @return array<string, mixed>
   */
  private function emptyProjectionResult(): array
  {
    return [
      'summary_cards' => [
        'next_paycheck'   => ['gross_cents' => 0, 'tax_cents' => 0, 'net_cents' => 0, 'hours' => 0.0, 'window_days' => 0],
        'next_30_days'    => ['gross_cents' => 0, 'tax_cents' => 0, 'net_cents' => 0, 'hours' => 0.0, 'window_days' => 0],
        'year_projection' => ['gross_cents' => 0, 'tax_cents' => 0, 'net_cents' => 0, 'hours' => 0.0, 'window_days' => 0],
      ],
      'timeline'  => [],
      'scenarios' => [],
    ];
  }

  private function estimateTaxWithYtd(
    string $provinceCode,
    int $grossCents,
    ForecastWindow $window,
    int $ytdGrossCents,
  ): int {
    if ($grossCents <= 0) {
      return 0;
    }

    $provinceName = self::PROVINCE_CODES[strtoupper($provinceCode)] ?? 'Alberta';
    $windowDays = max(1, $window->days());
    $annualizedWindow = (int) round($grossCents * (365.0 / $windowDays));
    $annualCents = max($annualizedWindow, $ytdGrossCents + $annualizedWindow);

    $taxes = new Taxes($provinceName);
    $annualTax = $taxes->calculateTaxesCents($annualCents);
    $annualTotal = (int) $annualTax['totalDeductions'];

    return (int) round($annualTotal * ($windowDays / 365.0));
  }

  /**
   * @param array<string, mixed> $profile
   * @param array<string, mixed> $scheduled
   * @param array<string, mixed> $ytd
   * @param array<string, mixed> $overrides
   * @return list<array{field: string, value: string|null, source: string}>
   */
  private function buildAssumptionSources(
    array $profile,
    array $scheduled,
    array $ytd,
    array $overrides,
  ): array {
    $merged = $this->mergeEffectiveInputs($profile, $scheduled, $ytd, $overrides, ForecastScenario::Normal);
    $sources = (array) ($merged['field_sources'] ?? []);

    $rows = [];
    foreach ([
      'wage_rate_cents'     => 'EARNINGS_FORECAST_ASSUMP_WAGE',
      'regular_hours_week'  => 'EARNINGS_FORECAST_ASSUMP_REG_HRS',
      'overtime_hours_week' => 'EARNINGS_FORECAST_ASSUMP_OT_HRS',
      'loa_per_day_cents'   => 'EARNINGS_FORECAST_ASSUMP_LOA',
      'travel_hours_week'   => 'EARNINGS_FORECAST_ASSUMP_TRAVEL',
      'province'            => 'EARNINGS_FORECAST_ASSUMP_PROVINCE',
      'pay_frequency'       => 'EARNINGS_FORECAST_ASSUMP_PAY_FREQ',
      'anchor_date'         => 'EARNINGS_FORECAST_ASSUMP_ANCHOR',
      'ytd_gross_cents'     => 'EARNINGS_FORECAST_ASSUMP_YTD_GROSS',
    ] as $field => $labelKey) {
      $value = $merged[$field] ?? null;
      $display = match ($field) {
        'wage_rate_cents', 'loa_per_day_cents', 'ytd_gross_cents' => is_numeric($value)
          ? Money::centsToDollars(self::intValue($value, 0)) : null,
        'regular_hours_week', 'overtime_hours_week', 'travel_hours_week' => is_numeric($value)
          ? self::stringValue($value) : null,
        default => is_scalar($value) ? self::stringValue($value) : null,
      };

      $rows[] = [
        'field'      => $field,
        'label_key'  => $labelKey,
        'value'      => $display,
        'source'     => self::stringValue($sources[$field] ?? ForecastAssumptionSource::Missing->value),
      ];
    }

    return $rows;
  }

  /**
   * @param list<array{field: string, value: string|null, source: string}> $assumptions
   * @param array<string, mixed> $ytd
   * @param array<string, mixed> $scheduled
   */
  private function resolveOverallConfidence(
    array $assumptions,
    array $ytd,
    array $scheduled,
  ): ForecastConfidence {
    $missingCount = 0;
    foreach ($assumptions as $row) {
      if ($row['source'] === ForecastAssumptionSource::Missing->value) {
        ++$missingCount;
      }
    }

    if ($missingCount >= 2) {
      return ForecastConfidence::Low;
    }

    if (self::boolValue($scheduled['has_scheduled_work'] ?? false) && self::intValue($ytd['days_worked'] ?? 0) >= 10) {
      return ForecastConfidence::High;
    }

    if (self::intValue($ytd['days_worked'] ?? 0) >= 5) {
      return ForecastConfidence::Medium;
    }

    return ForecastConfidence::Low;
  }

  private static function intValue(mixed $value, int $default = 0): int
  {
    return is_numeric($value) ? (int) $value : $default;
  }

  private static function floatValue(mixed $value, float $default = 0.0): float
  {
    return is_numeric($value) ? (float) $value : $default;
  }

  private static function stringValue(mixed $value, string $default = ''): string
  {
    if (is_string($value)) {
      return $value;
    }

    return is_scalar($value) ? (string) $value : $default;
  }

  private static function boolValue(mixed $value): bool
  {
    return $value === true || $value === 1 || $value === '1';
  }

  private static function nullableInt(mixed $value): ?int
  {
    return is_numeric($value) ? (int) $value : null;
  }
}
