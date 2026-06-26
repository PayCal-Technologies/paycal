<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * Builds per-member earnings reports for business workspace coordinators.
 *
 * @phpstan-type MemberDailyEntry array<string, string>
 * @phpstan-type MemberMonthBreakdown array{reg_hours: float, ot_hours: float, gross: float, net: float}
 * @phpstan-type MemberYearAccumulator array{
 *   reg_hours: float,
 *   ot_hours: float,
 *   gross: float,
 *   net: float,
 *   gross_by_date: array<string, float>,
 *   active_months: array<string, true>,
 *   monthly_breakdown: array<string, MemberMonthBreakdown>,
 *   daily_entries: array<string, MemberDailyEntry>
 * }
 * @phpstan-type MemberWorkSnapshot array{
 *   years: list<int>,
 *   by_year: array<int, array{
 *     reg_hours: float,
 *     ot_hours: float,
 *     gross: float,
 *     net: float,
 *     gross_by_date: array<string, float>,
 *     active_months: array<string, true>,
 *     months: list<array{month: string, label: string, reg_hours: float, ot_hours: float, gross: float, net: float}>,
 *     daily_entries: array<string, MemberDailyEntry>
 *   }>,
 *   gross_by_year_cents: array<int, int>
 * }
 */
final class BusinessMemberReportsService
{
  private const GRAPH_ID_PREFIX = 'member_reports_line_graph_';
  private const TAB_ID_PREFIX = 'member_reports_tab-';

  /** @var array<string, string> */
  private const PROVINCE_NAMES = [
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
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function getMemberBreakdown(
    string $actorUUID,
    string $businessId,
    string $memberUUID,
    int $year,
  ): array {
    return $this->getMemberEarningsView($actorUUID, $businessId, $memberUUID, $year);
  }

  /**
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function getMemberBreakdownJson(
    string $actorUUID,
    string $businessId,
    string $memberUUID,
    int $year,
  ): array {
    $context = $this->resolveAccessContext($actorUUID, $businessId, $memberUUID, $year);
    if (!$context['success']) {
      return $context;
    }

    /** @var array{member_name: string, member_role: string, work_snapshot: MemberWorkSnapshot, year: int} $data */
    $data = $context['data'];
    $workSnapshot = $data['work_snapshot'];
    $activeYear = $data['year'];
    $yearData = $workSnapshot['by_year'][$activeYear] ?? null;
    if (!is_array($yearData)) {
      $yearData = [
        'reg_hours' => 0.0,
        'ot_hours' => 0.0,
        'gross' => 0.0,
        'net' => 0.0,
        'months' => [],
      ];
    }

    return [
      'success' => true,
      'message' => '[Business] Member earnings breakdown loaded.',
      'data' => [
        'name' => $data['member_name'],
        'role' => $data['member_role'],
        'uuid' => $memberUUID,
        'year' => $activeYear,
        'reg_hours' => round((float) $yearData['reg_hours'], 2),
        'ot_hours' => round((float) $yearData['ot_hours'], 2),
        'gross' => round((float) $yearData['gross'], 2),
        'net' => round((float) $yearData['net'], 2),
        'months' => $yearData['months'],
      ],
    ];
  }

  /**
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function getMemberEarningsView(
    string $actorUUID,
    string $businessId,
    string $memberUUID,
    int $year,
  ): array {
    $context = $this->resolveAccessContext($actorUUID, $businessId, $memberUUID, $year);
    if (!$context['success']) {
      return $context;
    }

    /** @var array{member_name: string, member_role: string, member_user: ?User, work_snapshot: MemberWorkSnapshot, year: int} $data */
    $data = $context['data'];
    $workSnapshot = $data['work_snapshot'];
    $years = $workSnapshot['years'];
    $activeYear = $data['year'];
    $html = $this->renderMemberEarningsViewHtml(
      $years,
      $activeYear,
      $workSnapshot,
      $businessId,
      $memberUUID,
      SubscriptionRepository::isPremiumActive($actorUUID),
    );

    return [
      'success' => true,
      'message' => '[Business] Member earnings reports view loaded.',
      'data' => [
        'member' => [
          'uuid' => $memberUUID,
          'name' => $data['member_name'],
          'role' => $data['member_role'],
          'year' => $activeYear,
        ],
        'years' => $years,
        'default_year' => $activeYear,
        'html' => $html,
        'gross_by_date' => $workSnapshot['by_year'][$activeYear]['gross_by_date'] ?? [],
      ],
    ];
  }

  /**
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function getMemberReportsSectionHtml(
    string $actorUUID,
    string $businessId,
    string $memberUUID,
    string $section,
    int $year,
  ): array {
    $context = $this->resolveAccessContext($actorUUID, $businessId, $memberUUID, $year);
    if (!$context['success']) {
      return $context;
    }

    /** @var array{member_name: string, member_role: string, member_user: ?User, work_snapshot: MemberWorkSnapshot, year: int} $data */
    $data = $context['data'];
    $workSnapshot = $data['work_snapshot'];
    $memberUser = $data['member_user'];
    $activeYear = $data['year'];

    $html = match (strtolower(trim($section))) {
      'ytd' => $this->renderMemberYearToDateSummary($activeYear, $workSnapshot, $memberUser),
      'payperiods' => $this->renderMemberPayPeriodComparison($activeYear, $workSnapshot, $memberUser),
      'monthly' => $this->renderMemberMonthlyViewStrip($activeYear, $workSnapshot, $memberUser),
      default => '',
    };

    return [
      'success' => true,
      'message' => '[Business] Member reports section loaded.',
      'data' => ['html' => $html],
    ];
  }

  /**
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function getMemberReportsGrossYear(
    string $actorUUID,
    string $businessId,
    string $memberUUID,
    int $year,
  ): array {
    $context = $this->resolveAccessContext($actorUUID, $businessId, $memberUUID, $year);
    if (!$context['success']) {
      return $context;
    }

    /** @var array{member_name: string, member_role: string, member_user: ?User, work_snapshot: MemberWorkSnapshot, year: int} $data */
    $data = $context['data'];
    $workSnapshot = $data['work_snapshot'];
    $activeYear = $data['year'];

    return [
      'success' => true,
      'message' => '[Business] Member gross trend loaded.',
      'data' => [
        'gross_by_date' => $workSnapshot['by_year'][$activeYear]['gross_by_date'] ?? [],
      ],
    ];
  }

  /**
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function getMemberReportsDailyYear(
    string $actorUUID,
    string $businessId,
    string $memberUUID,
    int $year,
  ): array {
    $context = $this->resolveAccessContext($actorUUID, $businessId, $memberUUID, $year);
    if (!$context['success']) {
      return $context;
    }

    /** @var array{member_name: string, member_role: string, member_user: ?User, work_snapshot: MemberWorkSnapshot, year: int} $data */
    $data = $context['data'];
    $workSnapshot = $data['work_snapshot'];
    $activeYear = $data['year'];
    $memberUser = $data['member_user'];

    return [
      'success' => true,
      'message' => '[Business] Member daily earnings loaded.',
      'data' => $this->buildMemberDailyPayload($activeYear, $workSnapshot, $memberUser),
    ];
  }

  /**
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function resolveMemberForecastAccess(
    string $actorUUID,
    string $businessId,
    string $memberUUID,
  ): array {
    $context = $this->resolveAccessContext($actorUUID, $businessId, $memberUUID, (int) date('Y'));
    if (!$context['success']) {
      return $context;
    }

    if (!SubscriptionRepository::isPremiumActive($actorUUID)) {
      return [
        'success' => false,
        'message' => 'Premium subscription required for Forecast.',
        'data' => [],
      ];
    }

    /** @var array<string, mixed> $data */
    $data = $context['data'];
    $memberUser = $data['member_user'] ?? null;
    if (!$memberUser instanceof User) {
      return [
        'success' => false,
        'message' => 'Member not found for this business.',
        'data' => [],
      ];
    }

    return [
      'success' => true,
      'message' => '[Business] Member forecast access granted.',
      'data' => ['member_user' => $memberUser],
    ];
  }

  /**
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function getMemberReportsForecast(
    string $actorUUID,
    string $businessId,
    string $memberUUID,
  ): array {
    $context = $this->resolveMemberForecastAccess($actorUUID, $businessId, $memberUUID);
    if (!$context['success']) {
      return $context;
    }

    /** @var array<string, mixed> $data */
    $data = $context['data'];
    $memberUser = $data['member_user'] ?? null;
    $html = '';
    $state = ['setup_required' => true, 'can_calculate' => false];
    if ($memberUser instanceof User) {
      $state = Earnings::getInstance()->buildForecastStateForUser($memberUser);
      $html = ForecastWorkspaceRenderer::renderShell(
        $state,
        'member_reports_forecast_content',
        true,
      );
    }

    return [
      'success' => true,
      'message' => '[Business] Member forecast loaded.',
      'data' => ['html' => $html, 'state' => $state],
    ];
  }

  /**
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function resolveAccessContext(
    string $actorUUID,
    string $businessId,
    string $memberUUID,
    int $year,
  ): array {
    $actorUUID = trim($actorUUID);
    $businessId = trim($businessId);
    $memberUUID = trim($memberUUID);

    if ($actorUUID === '' || $businessId === '' || $memberUUID === '') {
      return [
        'success' => false,
        'message' => Strings::i18n('BUSINESSES_SELECT_FIRST'),
        'data' => [],
      ];
    }

    if ($year < 2000 || $year > 2100) {
      $year = (int) date('Y');
    }

    $discovery = new BusinessDiscoveryService();
    $access = $discovery->listConnections($actorUUID, $businessId);
    if (!$access['success']) {
      return [
        'success' => false,
        'message' => $access['message'] !== '' ? $access['message'] : 'Unable to load member reports.',
        'data' => [],
      ];
    }

    $memberContext = $this->resolveMemberFromAccess($access, $memberUUID);
    if ($memberContext === null) {
      return [
        'success' => false,
        'message' => 'Member not found for this business.',
        'data' => [],
      ];
    }

    $memberUser = User::getByUUID($memberUUID);
    $protectedRead = (new BusinessProtectedDataAccess())->readMemberWork(
      $actorUUID,
      $businessId,
      $memberUUID,
      null,
      null,
      true,
      'business.member.reports',
    );
    if (!$protectedRead['success']) {
      return [
        'success' => false,
        'message' => $protectedRead['message'],
        'data' => ['reason' => $protectedRead['reason']],
      ];
    }

    $protectedEntries = $protectedRead['data']['entries'] ?? [];
    $workSnapshot = $this->collectMemberBusinessWork(
      $this->normalizeProtectedEntries($protectedEntries),
    );
    $years = $workSnapshot['years'];
    if ($years === []) {
      $years = [$year];
    }

    if (!in_array($year, $years, true)) {
      $year = $years[count($years) - 1];
    }

    return [
      'success' => true,
      'message' => '[Business] Member reports access granted.',
      'data' => [
        'member_name' => $memberContext['name'],
        'member_role' => $memberContext['role'],
        'member_user' => $memberUser,
        'work_snapshot' => $workSnapshot,
        'year' => $year,
      ],
    ];
  }

  /**
   * @param array{success: bool, message: string, data: array<string, mixed>} $access
   * @return array{name: string, role: string}|null
   */
  private function resolveMemberFromAccess(array $access, string $memberUUID): ?array
  {
    $members = is_array($access['data']['members'] ?? null) ? $access['data']['members'] : [];
    foreach ($members as $member) {
      if (!is_array($member)) {
        continue;
      }

      $candidateUUID = isset($member['user_uuid']) && is_scalar($member['user_uuid'])
        ? trim((string) $member['user_uuid'])
        : (isset($member['uuid']) && is_scalar($member['uuid']) ? trim((string) $member['uuid']) : '');
      if ($candidateUUID !== $memberUUID) {
        continue;
      }

      $memberName = isset($member['full_name']) && is_scalar($member['full_name'])
        ? trim((string) $member['full_name'])
        : '';
      if ($memberName === '') {
        $memberName = isset($member['email']) && is_scalar($member['email'])
          ? trim((string) $member['email'])
          : $memberUUID;
      }

      $memberRole = isset($member['role']) && is_scalar($member['role'])
        ? strtolower(trim((string) $member['role']))
        : 'member';

      return [
        'name' => $memberName,
        'role' => $memberRole,
      ];
    }

    return null;
  }

  /**
   * @param array<string, array<string, string>> $workEntries
   * @return MemberWorkSnapshot
   */
  private function collectMemberBusinessWork(array $workEntries): array
  {
    /** @var array<int, MemberYearAccumulator> $byYear */
    $byYear = [];
    /** @var array<int, int> $grossByYearCents */
    $grossByYearCents = [];

    foreach ($workEntries as $workKey => $entry) {
      $keyParts = explode(':', $workKey);
      $isArchived = isset($keyParts[1]) && $keyParts[1] === 'archived';
      $siteIdFromKey = $isArchived ? (string) ($keyParts[4] ?? '') : (string) ($keyParts[3] ?? '');
      $date = $isArchived
        ? (isset($keyParts[3]) && strlen((string) $keyParts[3]) >= 10 ? (string) $keyParts[3] : (string) ($entry['date'] ?? ''))
        : (isset($keyParts[2]) && strlen((string) $keyParts[2]) >= 10 ? (string) $keyParts[2] : (string) ($entry['date'] ?? ''));

      if ($date === '' || strlen($date) < 4) {
        continue;
      }

      $entryYear = (int) substr($date, 0, 4);
      if ($entryYear < 2000 || $entryYear > 2100) {
        continue;
      }

      if (!isset($byYear[$entryYear])) {
        $byYear[$entryYear] = [
          'reg_hours' => 0.0,
          'ot_hours' => 0.0,
          'gross' => 0.0,
          'net' => 0.0,
          'gross_by_date' => [],
          'active_months' => [],
          'monthly_breakdown' => [],
          'daily_entries' => [],
        ];
      }

      $month = strlen($date) >= 7 ? substr($date, 0, 7) : 'unknown';
      $entryReg = (float) ($entry['regular_hours'] ?? $entry['r'] ?? 0);
      $entryOt = (float) ($entry['overtime_hours'] ?? $entry['o'] ?? 0);
      $entryTravel = (float) ($entry['travel_hours'] ?? $entry['t'] ?? 0);
      $entryHours = (float) ($entry['hours'] ?? $entry['h'] ?? ($entryReg + $entryOt + $entryTravel));
      $entryGross = (float) ($entry['gross'] ?? $entry['g'] ?? 0);
      $entryTax = (float) ($entry['tax'] ?? $entry['tx'] ?? 0);
      $entryNet = (float) ($entry['net'] ?? ($entryGross - $entryTax));
      $entryLoa = (float) ($entry['living_out_allowance'] ?? $entry['l'] ?? 0);
      $entryWage = (float) ($entry['wage'] ?? $entry['w'] ?? 0);
      $siteName = trim((string) ($entry['site_name'] ?? ''));

      $byYear[$entryYear]['reg_hours'] += $entryReg;
      $byYear[$entryYear]['ot_hours'] += $entryOt;
      $byYear[$entryYear]['gross'] += $entryGross;
      $byYear[$entryYear]['net'] += $entryNet;

      if (!isset($byYear[$entryYear]['gross_by_date'][$date])) {
        $byYear[$entryYear]['gross_by_date'][$date] = 0.0;
      }
      $byYear[$entryYear]['gross_by_date'][$date] += $entryGross;

      if ($month !== 'unknown') {
        $byYear[$entryYear]['active_months'][$month] = true;
      }

      if (!isset($byYear[$entryYear]['monthly_breakdown'][$month])) {
        $byYear[$entryYear]['monthly_breakdown'][$month] = [
          'reg_hours' => 0.0,
          'ot_hours' => 0.0,
          'gross' => 0.0,
          'net' => 0.0,
        ];
      }

      $byYear[$entryYear]['monthly_breakdown'][$month]['reg_hours'] += $entryReg;
      $byYear[$entryYear]['monthly_breakdown'][$month]['ot_hours'] += $entryOt;
      $byYear[$entryYear]['monthly_breakdown'][$month]['gross'] += $entryGross;
      $byYear[$entryYear]['monthly_breakdown'][$month]['net'] += $entryNet;

      if (!isset($byYear[$entryYear]['daily_entries'][$date])) {
        $byYear[$entryYear]['daily_entries'][$date] = [
          'site_name' => $siteName,
          'wage' => number_format($entryWage, 2, '.', ''),
          'hours' => number_format($entryHours, 2, '.', ''),
          'regular_hours' => number_format($entryReg, 2, '.', ''),
          'overtime_hours' => number_format($entryOt, 2, '.', ''),
          'travel_hours' => number_format($entryTravel, 2, '.', ''),
          'living_out_allowance' => number_format($entryLoa, 2, '.', ''),
          'gross' => number_format($entryGross, 2, '.', ''),
          'tax' => number_format($entryTax, 2, '.', ''),
          'deductions' => number_format($entryTax, 2, '.', ''),
          'net' => number_format($entryNet, 2, '.', ''),
        ];
      } else {
        $existing = &$byYear[$entryYear]['daily_entries'][$date];
        $existing['site_name'] = $existing['site_name'] !== '' ? $existing['site_name'] . ', ' . $siteName : $siteName;
        $existing['hours'] = number_format((float) $existing['hours'] + $entryHours, 2, '.', '');
        $existing['regular_hours'] = number_format((float) $existing['regular_hours'] + $entryReg, 2, '.', '');
        $existing['overtime_hours'] = number_format((float) $existing['overtime_hours'] + $entryOt, 2, '.', '');
        $existing['travel_hours'] = number_format((float) $existing['travel_hours'] + $entryTravel, 2, '.', '');
        $existing['living_out_allowance'] = number_format((float) $existing['living_out_allowance'] + $entryLoa, 2, '.', '');
        $existing['gross'] = number_format((float) $existing['gross'] + $entryGross, 2, '.', '');
        $existing['tax'] = number_format((float) $existing['tax'] + $entryTax, 2, '.', '');
        $existing['deductions'] = $existing['tax'];
        $existing['net'] = number_format((float) $existing['net'] + $entryNet, 2, '.', '');
      }
    }

    $years = array_keys($byYear);
    sort($years);
    /** @var list<int> $years */
    $years = array_map(static fn (int|string $yearValue): int => (int) $yearValue, $years);

    $normalizedByYear = [];
    foreach ($byYear as $entryYear => $values) {
      $months = [];
      ksort($values['monthly_breakdown']);
      foreach ($values['monthly_breakdown'] as $yearMonth => $monthValues) {
        $dateTime = \DateTimeImmutable::createFromFormat('Y-m', (string) $yearMonth);
        $months[] = [
          'month' => (string) $yearMonth,
          'label' => $dateTime !== false
            ? Strings::formatLocalizedShortMonthYear((int) $dateTime->format('Y'), (int) $dateTime->format('n'))
            : (string) $yearMonth,
          'reg_hours' => round((float) $monthValues['reg_hours'], 2),
          'ot_hours' => round((float) $monthValues['ot_hours'], 2),
          'gross' => round((float) $monthValues['gross'], 2),
          'net' => round((float) $monthValues['net'], 2),
        ];
      }

      $grossByDate = [];
      foreach ($values['gross_by_date'] as $dateKey => $grossValue) {
        $grossByDate[(string) $dateKey] = round((float) $grossValue, 2);
      }
      ksort($grossByDate);

      ksort($values['daily_entries']);

      $normalizedByYear[(int) $entryYear] = [
        'reg_hours' => round((float) $values['reg_hours'], 2),
        'ot_hours' => round((float) $values['ot_hours'], 2),
        'gross' => round((float) $values['gross'], 2),
        'net' => round((float) $values['net'], 2),
        'gross_by_date' => $grossByDate,
        'active_months' => $values['active_months'],
        'months' => $months,
        'daily_entries' => $values['daily_entries'],
      ];

      $grossByYearCents[(int) $entryYear] = Money::dollarsToCents((string) $values['gross']);
    }

    return [
      'years' => $years,
      'by_year' => $normalizedByYear,
      'gross_by_year_cents' => $grossByYearCents,
    ];
  }

  /**
   * @param mixed $entries
   * @return array<string, array<string, string>>
   */
  private function normalizeProtectedEntries(mixed $entries): array
  {
    if (!is_array($entries)) {
      return [];
    }

    $normalized = [];
    foreach ($entries as $workKey => $entry) {
      if (!is_string($workKey) || !is_array($entry)) {
        continue;
      }

      $row = [];
      foreach ($entry as $key => $value) {
        if (is_string($key) && is_scalar($value)) {
          $row[$key] = (string) $value;
        }
      }
      $normalized[$workKey] = $row;
    }

    return $normalized;
  }

  /**
   * @param list<int> $years
   * @param MemberWorkSnapshot $workSnapshot
   */
  private function renderMemberEarningsViewHtml(
    array $years,
    int $activeYear,
    array $workSnapshot,
    string $businessId,
    string $memberUUID,
    bool $hasPremiumReporting,
  ): string {
    if ($years === []) {
      $years = [$activeYear];
    }

    $years = array_values(array_unique(array_map(static fn (int $year): int => $year, $years)));
    sort($years);

    $myEarningsLabel = htmlspecialchars(Strings::i18n('EARNINGS_MY_EARNINGS'), ENT_QUOTES, 'UTF-8');
    $businessIdAttr = htmlspecialchars($businessId, ENT_QUOTES, 'UTF-8');
    $memberUuidAttr = htmlspecialchars($memberUUID, ENT_QUOTES, 'UTF-8');
    $premiumAttr = $hasPremiumReporting ? '1' : '0';

    $yearTabsAria = htmlspecialchars(Strings::i18n('MEMBER_REPORTS_YEAR_TABS_ARIA'), ENT_QUOTES, 'UTF-8');
    $tabs = "<ul class='tabs member_reports_year_tabs' role='tablist' aria-label='{$yearTabsAria}'>\n";
    $contents = "<section class='f_column w100 tab-content member_reports_tab_content'>\n";

    foreach ($years as $year) {
      $isActive = $year === $activeYear;
      $activeClass = $isActive ? ' active' : '';
      $ariaSelected = $isActive ? "aria-selected='true'" : "aria-selected='false'";
      $tabIndex = $isActive ? '0' : '-1';
      $tabTarget = self::TAB_ID_PREFIX . $year;
      $tabBtnId = self::TAB_ID_PREFIX . 'btn-' . $year;
      $tabs .= "<li id='{$tabBtnId}' data-tab-target='{$tabTarget}' class='tab{$activeClass}' role='tab' {$ariaSelected} tabindex='{$tabIndex}' aria-controls='{$tabTarget}'>{$year}</li>\n";

      $yearToDate = htmlspecialchars(Strings::i18n('YEAR_TO_DATE'), ENT_QUOTES, 'UTF-8');
      $payPeriods = htmlspecialchars(Strings::i18n('PAY_PERIODS'), ENT_QUOTES, 'UTF-8');
      $monthly = htmlspecialchars(Strings::i18n('MONTHLY'), ENT_QUOTES, 'UTF-8');
      $daily = htmlspecialchars(Strings::i18n('DAILY'), ENT_QUOTES, 'UTF-8');
      $earningsTrend = htmlspecialchars(Strings::i18n('EARNINGS_TREND'), ENT_QUOTES, 'UTF-8');
      $browserConvenienceExportsNote = $hasPremiumReporting
        ? '<p class="earnings_export_note">' . htmlspecialchars('CSV/TXT are browser convenience exports.', ENT_QUOTES, 'UTF-8') . '</p>'
        : '';
      $lineGraphTitle = htmlspecialchars(Strings::i18n('EARNINGS_TREND_CHART_FOR') . ' ' . $year, ENT_QUOTES, 'UTF-8');
      $lineGraphDesc = htmlspecialchars(Strings::i18n('EARNINGS_TREND_CHART_DESC') . ' ' . $year . '.', ENT_QUOTES, 'UTF-8');
      $lineGraphStatus = htmlspecialchars(Strings::i18n('EARNINGS_TREND_CHART_LOADING_FOR') . ' ' . $year . '.', ENT_QUOTES, 'UTF-8');
      $dailyGridInstructions = htmlspecialchars(Strings::i18n('EARNINGS_DAILY_GRID_INSTRUCTIONS_FOR') . ' ' . $year, ENT_QUOTES, 'UTF-8');
      $dailyGridContext = htmlspecialchars(Strings::i18n('EARNINGS_DAILY_GRID_CONTEXT_FOR') . ' ' . $year, ENT_QUOTES, 'UTF-8');
      $memberEarningsForYear = $this->formatI18n('EARNINGS_MEMBER_EARNINGS_FOR_YEAR', ['year' => (string) $year]);
      $ytdExportAria = $this->formatI18n('EARNINGS_YTD_EXPORT_FORMATS_FOR', ['year' => (string) $year]);
      $monthlyExportAria = $this->formatI18n('EARNINGS_MONTHLY_EXPORT_FORMATS_FOR', ['year' => (string) $year]);
      $dailyExportAria = $this->formatI18n('EARNINGS_DAILY_EXPORT_FORMATS_FOR', ['year' => (string) $year]);
      $graphId = self::GRAPH_ID_PREFIX . $year;
      $historicalIntelligenceHtml = $this->renderHistoricalIntelligence($year, $workSnapshot);
      $pieGraphsHtml = $this->renderMemberPieGraphsPanel($year);
      $ytdExportButtons = $this->renderMemberExportButtons('yearly', $year, $hasPremiumReporting);
      $monthlyExportButtons = $this->renderMemberExportButtons('monthly', $year, $hasPremiumReporting);
      $dailyExportButtons = $this->renderMemberExportButtons('daily', $year, $hasPremiumReporting);

      $contents .= <<<HTML
<div id="{$tabTarget}" data-tab-content="{$tabTarget}" class="f_column{$activeClass}" role="tabpanel" aria-labelledby="{$tabBtnId}" aria-label="{$memberEarningsForYear}">
  <section class="panel w100 earnings_panel">
    <h2 class="earnings_panel_title">{$earningsTrend}</h2>
    <div class="earnings-graph-container">
      <div class="visually_hidden">
        <p id="{$graphId}_title">{$lineGraphTitle}</p>
        <p id="{$graphId}_desc">{$lineGraphDesc}</p>
        <p id="{$graphId}_status" role="status" aria-live="polite" aria-atomic="true">{$lineGraphStatus}</p>
      </div>
      <svg id="{$graphId}" width="100%" height="300" role="img" aria-labelledby="{$graphId}_title" aria-describedby="{$graphId}_desc {$graphId}_status" focusable="false"></svg>
    </div>
  </section>

  <section class="panel w100 earnings_panel">
    {$historicalIntelligenceHtml}
  </section>

  {$pieGraphsHtml}

  <section class="panel w100 earnings_panel">
    <h2 class="earnings_panel_title">{$yearToDate}</h2>
    <div class="earnings_export_actions" role="group" aria-label="{$ytdExportAria}">
      {$ytdExportButtons}
    </div>
    {$browserConvenienceExportsNote}
    <div id="member_reports_ytd_{$year}" class="earnings_async_slot" data-earnings-slot="ytd" data-earnings-year="{$year}">{$this->buildAsyncSkeletonGrid(3, 5)}</div>
  </section>

  <section class="panel w100 earnings_panel">
    <h2 class="earnings_panel_title">{$payPeriods}</h2>
    <div id="member_reports_pay_periods_{$year}" class="earnings_async_slot" data-earnings-slot="payperiods" data-earnings-year="{$year}">{$this->buildAsyncSkeletonGrid(4, 4)}</div>
  </section>

  <section class="panel w100 earnings_panel">
    <h2 class="earnings_panel_title">{$monthly}</h2>
    <div class="earnings_export_actions" role="group" aria-label="{$monthlyExportAria}">
      {$monthlyExportButtons}
    </div>
    {$browserConvenienceExportsNote}
    <div id="member_reports_monthly_{$year}" class="earnings_async_slot" data-earnings-slot="monthly" data-earnings-year="{$year}">{$this->buildAsyncSkeletonGrid(11, 6)}</div>
  </section>

  <section class="panel w100 earnings_panel">
    <h2 class="earnings_panel_title">{$daily}</h2>
    <div class="earnings_export_actions" role="group" aria-label="{$dailyExportAria}">
      {$dailyExportButtons}
    </div>
    {$browserConvenienceExportsNote}
    <div class="visually_hidden">
      <p id="member_reports_daily_{$year}_sr_instructions">{$dailyGridInstructions}</p>
      <p id="member_reports_daily_{$year}_sr_context">{$dailyGridContext}</p>
      <p id="member_reports_daily_{$year}_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
    </div>
    <div id="member_reports_daily_earnings_{$year}" class="daily-earnings-section"></div>
  </section>
</div>
HTML;
    }

    if ($hasPremiumReporting) {
      $forecastTarget = self::TAB_ID_PREFIX . 'forecast';
      $forecastTabBtnId = self::TAB_ID_PREFIX . 'btn-forecast';
      $forecastLabel = htmlspecialchars(Strings::i18n('EARNINGS_FORECAST'), ENT_QUOTES, 'UTF-8');
      $forecastAria = htmlspecialchars(Strings::i18n('EARNINGS_MEMBER_EARNINGS_FORECAST_ARIA'), ENT_QUOTES, 'UTF-8');
      $tabs .= "<li id='{$forecastTabBtnId}' data-tab-target='{$forecastTarget}' class='tab' role='tab' aria-selected='false' tabindex='-1' aria-controls='{$forecastTarget}'>{$forecastLabel}</li>\n";
      $contents .= <<<HTML
<div id="{$forecastTarget}" data-tab-content="{$forecastTarget}" class="f_column" role="tabpanel" aria-labelledby="{$forecastTabBtnId}" aria-label="{$forecastAria}">
  <section class="panel w100 earnings_panel forecast-panel-shell">
    <div id="member_reports_forecast_content" class="earnings_async_slot" data-forecast-async="1">{$this->buildAsyncSkeletonGrid(4, 3)}</div>
  </section>
</div>
HTML;
    }

    $tabs .= "</ul>\n";
    $contents .= "</section>\n";

    return <<<HTML
<div class="earnings_member_reports_view" data-member-reports-root="1" data-member-reports-premium="{$premiumAttr}" data-member-reports-business-id="{$businessIdAttr}" data-member-reports-member-uuid="{$memberUuidAttr}">
  <nav class="earnings_view_tabs" aria-label="{$myEarningsLabel}">
    <div class="earnings_view_tabs_links">
      <span class="earnings_view_tab active" aria-current="page">{$myEarningsLabel}</span>
    </div>
  </nav>
  <section class="w100" data-earnings-mode="lazy" data-member-reports-years="{$activeYear}">
    {$tabs}
    {$contents}
  </section>
</div>
HTML;
  }

  private function renderMemberExportButtons(string $scope, int $year, bool $hasPremiumReporting): string
  {
    $labels = [
      'csv' => Strings::i18n('CSV'),
      'txt' => Strings::i18n('TXT'),
      'xlsx' => 'XLSX',
      'pdf' => Strings::i18n('PDF'),
    ];
    $formats = $hasPremiumReporting ? ['csv', 'txt', 'xlsx', 'pdf'] : ['pdf'];
    $scopeAttr = htmlspecialchars($scope, ENT_QUOTES, 'UTF-8');
    $yearAttr = htmlspecialchars((string) $year, ENT_QUOTES, 'UTF-8');

    $buttons = [];
    foreach ($formats as $format) {
      $formatAttr = htmlspecialchars($format, ENT_QUOTES, 'UTF-8');
      $label = htmlspecialchars($labels[$format], ENT_QUOTES, 'UTF-8');
      $buttons[] = '<button type="button" class="paycal_export_btn" data-member-export-scope="' . $scopeAttr . '" data-member-export-format="' . $formatAttr . '" data-member-export-year="' . $yearAttr . '">' . $label . '</button>';
    }

    return implode(' &sdot; ', $buttons);
  }

  private function renderMemberPieGraphsPanel(int $year): string
  {
    $yearAttr = (string) $year;
    $panelTitle = htmlspecialchars(Strings::i18n('EARNINGS_COMPOSITION_PANEL_TITLE'), ENT_QUOTES, 'UTF-8');
    $ytdTitle = htmlspecialchars(Strings::i18n('EARNINGS_YTD_COMPOSITION'), ENT_QUOTES, 'UTF-8');
    $monthlyTitle = htmlspecialchars(Strings::i18n('EARNINGS_MONTHLY_COMPOSITION'), ENT_QUOTES, 'UTF-8');
    $monthLabel = htmlspecialchars(Strings::i18n('EARNINGS_PIEGRAPHS_MONTH_SELECT_ARIA'), ENT_QUOTES, 'UTF-8');
    $ytdLegendId = 'member_reports_piegraphs_ytd_legend_' . $yearAttr;
    $monthLegendId = 'member_reports_piegraphs_month_legend_' . $yearAttr;

    return '<section class="panel w100 earnings_panel earnings_piegraphs_panel" id="member_reports_piegraphs_panel_' . $yearAttr . '" data-earnings-piegraphs-year="' . $yearAttr . '">'
      . '<h2 class="earnings_panel_title">' . $panelTitle . '</h2>'
      . '<div class="earnings_piegraphs_grid">'
      . '<article class="panel earnings_panel earnings_piegraphs_card">'
      . '<h3 class="earnings_piegraphs_card_title">' . $ytdTitle . '</h3>'
      . '<div class="earnings_piegraphs_month_controls_spacer" aria-hidden="true"></div>'
      . '<svg id="member_reports_piegraphs_ytd_svg_' . $yearAttr . '" class="earnings_piegraphs_svg" viewBox="0 0 240 240" role="img" aria-label="' . $ytdTitle . ' ' . $yearAttr . '" aria-describedby="' . $ytdLegendId . '" focusable="false"></svg>'
      . '<div id="' . $ytdLegendId . '" class="earnings_piegraphs_legend" aria-live="polite"></div>'
      . '</article>'
      . '<article class="panel earnings_panel earnings_piegraphs_card">'
      . '<h3 class="earnings_piegraphs_card_title">' . $monthlyTitle . '</h3>'
      . '<div class="earnings_piegraphs_month_controls">'
      . '<select id="member_reports_piegraphs_month_select_' . $yearAttr . '" class="earnings_piegraphs_month_select" aria-label="' . $monthLabel . '"></select>'
      . '</div>'
      . '<svg id="member_reports_piegraphs_month_svg_' . $yearAttr . '" class="earnings_piegraphs_svg" viewBox="0 0 240 240" role="img" aria-label="' . $monthlyTitle . ' ' . $yearAttr . '" aria-describedby="' . $monthLegendId . '" focusable="false"></svg>'
      . '<div id="' . $monthLegendId . '" class="earnings_piegraphs_legend" aria-live="polite"></div>'
      . '</article>'
      . '</div>'
      . '</section>';
  }

  /**
   * @param MemberWorkSnapshot $workSnapshot
   */
  private function renderMemberYearToDateSummary(int $year, array $workSnapshot, ?User $memberUser): string
  {
    $yearData = $workSnapshot['by_year'][$year] ?? null;
    if (!is_array($yearData)) {
      return '<p class="earnings_async_status">' . htmlspecialchars(Strings::i18n('EARNINGS_ASYNC_NO_DATA'), ENT_QUOTES, 'UTF-8') . '</p>';
    }

    $grossIncomeCents = Money::dollarsToCents((string) $yearData['gross']);
    $regularHours = $yearData['reg_hours'];
    $overtimeHours = $yearData['ot_hours'];

    $tax = new Taxes($this->resolveProvinceName($memberUser), $year);
    $t = $tax->calculateTaxesCents($grossIncomeCents);
    $netCents = $grossIncomeCents - (int) $t['totalDeductions'];

    $render = [
      '__HOURS__' => Strings::i18n('HOURS'),
      '__EARNINGS_YTD_ID__' => "member-{$year}-YTD",
      '__EARNINGS_YTD_ARIA_LABEL__' => Strings::i18n('EARNINGS_YTD_ARIA_LABEL'),
      '__EARNINGS_METRIC__' => Strings::i18n('EARNINGS_METRIC'),
      '__EARNINGS_VALUE__' => Strings::i18n('EARNINGS_VALUE'),
      '__REGULAR__' => Strings::i18n('REGULAR'),
      '__OVERTIME__' => Strings::i18n('OVERTIME'),
      '__GROSS_LABEL__' => Strings::i18n('GROSS'),
      '__FEDERAL_TAX_LABEL__' => Strings::i18n('FEDERAL_TAX'),
      '__PROVINCIAL_TAX_LABEL__' => Strings::i18n('PROVINCIAL_TAX'),
      '__EARNINGS_TOTAL_TAX__' => Strings::i18n('EARNINGS_TOTAL_TAX'),
      '__EARNINGS_EI__' => Strings::i18n('EARNINGS_EI'),
      '__EARNINGS_CPP__' => Strings::i18n('EARNINGS_CPP'),
      '__EARNINGS_OAS__' => Strings::i18n('EARNINGS_OAS'),
      '__EARNINGS_TOTAL_DEDUCTIONS__' => Strings::i18n('EARNINGS_TOTAL_DEDUCTIONS'),
      '__NET_LABEL__' => Strings::i18n('NET'),
      '__GROSS__' => $this->formatNumberLocalized($grossIncomeCents / 100, 2),
      '__FEDERAL_TAX__' => $this->formatNumberLocalized($t['federal'] / 100, 2),
      '__PROVINCIAL_TAX__' => $this->formatNumberLocalized($t['provincial'] / 100, 2),
      '__TOTAL_TAX__' => $this->formatNumberLocalized($t['incomeTax'] / 100, 2),
      '__EI__' => $this->formatNumberLocalized($t['employment_insurance'] / 100, 2),
      '__CPP__' => $this->formatNumberLocalized($t['canada_pension_plan'] / 100, 2),
      '__OAS__' => $this->formatNumberLocalized($t['old_age_security'] / 100, 2),
      '__TOTAL_DEDUCTIONS__' => $this->formatNumberLocalized($t['totalDeductions'] / 100, 2),
      '__NET__' => $this->formatNumberLocalized($netCents / 100, 2),
      '__REGULAR_HOURS__' => $this->formatNumberLocalized($regularHours, 2),
      '__OVERTIME_HOURS__' => $this->formatNumberLocalized($overtimeHours, 2),
    ];

    $hookRendered = EarningsYtdExtensionBridge::renderWithMode($year, $render, 'override');
    if (is_string($hookRendered) && trim($hookRendered) !== '') {
      return $hookRendered;
    }

    return Render::template('earnings-year-to-date', $render);
  }

  /**
   * @param MemberWorkSnapshot $workSnapshot
   */
  private function renderMemberMonthlyViewStrip(int $year, array $workSnapshot, ?User $memberUser = null): string
  {
    $tax = new Taxes($this->resolveProvinceName($memberUser), $year);
    $yearData = is_array($workSnapshot['by_year'][$year] ?? null) ? $workSnapshot['by_year'][$year] : [];
    $grossByDate = is_array($yearData['gross_by_date'] ?? null) ? $yearData['gross_by_date'] : [];

    /** @var array<string, array{reg: float, ot: float}> $hoursByMonth */
    $hoursByMonth = [];
    foreach (is_array($yearData['months'] ?? null) ? $yearData['months'] : [] as $row) {
      $monthKey = $row['month'];
      if ($monthKey === '') {
        continue;
      }
      $hoursByMonth[$monthKey] = [
        'reg' => (float) $row['reg_hours'],
        'ot' => (float) $row['ot_hours'],
      ];
    }

    $prevGrossCents = 0;
    $prevFederalTax = 0;
    $prevProvincialTax = 0;
    $prevEmploymentInsurance = 0;
    $prevCanadaPensionPlan = 0;
    $prevOldAgeSecurity = 0;

    $rows = [];
    for ($month = 1; $month <= 12; ++$month) {
      $monthKey = sprintf('%04d-%02d', $year, $month);

      $ytdGrossCents = 0;
      foreach ($grossByDate as $date => $gross) {
        if (strncmp((string) $date, $monthKey, 7) <= 0) {
          $ytdGrossCents += Money::dollarsToCents((string) $gross);
        }
      }

      $taxes = $tax->calculateTaxesCents($ytdGrossCents);
      $monthGrossCents = $ytdGrossCents - $prevGrossCents;
      $monthFederalCents = (int) $taxes['federal'] - $prevFederalTax;
      $monthProvincialCents = (int) $taxes['provincial'] - $prevProvincialTax;
      $monthEiCents = (int) $taxes['employment_insurance'] - $prevEmploymentInsurance;
      $monthCppCents = (int) $taxes['canada_pension_plan'] - $prevCanadaPensionPlan;
      $monthOasCents = (int) $taxes['old_age_security'] - $prevOldAgeSecurity;
      $previousTaxTotalCents = $prevFederalTax
        + $prevProvincialTax
        + $prevEmploymentInsurance
        + $prevCanadaPensionPlan
        + $prevOldAgeSecurity;
      $monthTotalTaxCents = (int) $taxes['totalDeductions'] - $previousTaxTotalCents;
      $monthNetCents = $monthGrossCents - $monthTotalTaxCents;

      $hours = $hoursByMonth[$monthKey] ?? ['reg' => 0.0, 'ot' => 0.0];

      $rows[] = [
        'id' => $monthKey,
        'month' => Strings::formatLocalizedShortMonth($year, $month),
        'regular_hours' => $this->formatNumberLocalized($hours['reg'], 2),
        'overtime_hours' => $this->formatNumberLocalized($hours['ot'], 2),
        'gross' => '$' . $this->formatNumberLocalized($monthGrossCents / 100, 2),
        'federal_tax' => '$' . $this->formatNumberLocalized(max(0, $monthFederalCents) / 100, 2),
        'provincial_tax' => '$' . $this->formatNumberLocalized(max(0, $monthProvincialCents) / 100, 2),
        'ei' => '$' . $this->formatNumberLocalized(max(0, $monthEiCents) / 100, 2),
        'cpp' => '$' . $this->formatNumberLocalized(max(0, $monthCppCents) / 100, 2),
        'oas' => '$' . $this->formatNumberLocalized(max(0, $monthOasCents) / 100, 2),
        'total_deductions' => '$' . $this->formatNumberLocalized(max(0, $monthTotalTaxCents) / 100, 2),
        'net' => '$' . $this->formatNumberLocalized($monthNetCents / 100, 2),
      ];

      $prevGrossCents = $ytdGrossCents;
      $prevFederalTax = (int) $taxes['federal'];
      $prevProvincialTax = (int) $taxes['provincial'];
      $prevEmploymentInsurance = (int) $taxes['employment_insurance'];
      $prevCanadaPensionPlan = (int) $taxes['canada_pension_plan'];
      $prevOldAgeSecurity = (int) $taxes['old_age_security'];
    }

    return (new DataGrid([
      'id' => 'member-reports-monthly-' . $year,
      'columns' => self::memberMonthlyColumns(),
      'rows' => $rows,
      'meta' => [
        'layout' => 'auto',
        'page' => 1,
        'totalPages' => 1,
        'title' => $this->formatI18nPlain('EARNINGS_MONTHLY_GRID_ARIA_FOR', ['year' => (string) $year]),
      ],
    ]))->table();
  }

  /**
   * @return list<array<string, scalar>>
   */
  private static function memberMonthlyColumns(): array
  {
    return [
      ['key' => 'month', 'label' => Strings::i18n('EARNINGS_MONTH')],
      ['key' => 'regular_hours', 'label' => Strings::i18n('REGULAR'), 'align' => 'right'],
      ['key' => 'overtime_hours', 'label' => Strings::i18n('OVERTIME'), 'align' => 'right'],
      ['key' => 'gross', 'label' => Strings::i18n('GROSS'), 'align' => 'right'],
      ['key' => 'federal_tax', 'label' => Strings::i18n('FEDERAL_TAX'), 'align' => 'right'],
      ['key' => 'provincial_tax', 'label' => Strings::i18n('PROVINCIAL_TAX'), 'align' => 'right'],
      ['key' => 'ei', 'label' => Strings::i18n('EARNINGS_EI'), 'align' => 'right'],
      ['key' => 'cpp', 'label' => Strings::i18n('EARNINGS_CPP'), 'align' => 'right'],
      ['key' => 'oas', 'label' => Strings::i18n('EARNINGS_OAS'), 'align' => 'right'],
      ['key' => 'total_deductions', 'label' => Strings::i18n('EARNINGS_TOTAL_DEDUCTIONS'), 'align' => 'right'],
      ['key' => 'net', 'label' => Strings::i18n('NET'), 'align' => 'right'],
    ];
  }

  /**
   * @param MemberWorkSnapshot $workSnapshot
   */
  private function renderMemberPayPeriodComparison(int $year, array $workSnapshot, ?User $memberUser): string
  {
    if (!$memberUser instanceof User) {
      return '<p class="pay-period-empty">' . htmlspecialchars(Strings::i18n('EARNINGS_NO_LOGGED_HOURS_IN_YEAR_PAY_PERIODS'), ENT_QUOTES, 'UTF-8') . '</p>';
    }

    $yearStart = new \DateTimeImmutable(sprintf('%d-01-01', $year));
    $yearEnd = new \DateTimeImmutable(sprintf('%d-12-31', $year));
    $period = Calendar::getCurrentPayPeriods($memberUser);

    while ($period->start() > $yearStart) {
      $previousPeriod = $period->previous();
      if ($previousPeriod->start() >= $period->start()) {
        break;
      }
      $period = $previousPeriod;
    }

    while ($period->endInclusive() < $yearStart) {
      $nextPeriod = $period->next();
      if ($nextPeriod->start() <= $period->start()) {
        break;
      }
      $period = $nextPeriod;
    }

    $cards = [];
    while ($period->start() <= $yearEnd || $period->endInclusive() <= $yearEnd) {
      $totals = $this->getSnapshotTotalsForRange(
        $workSnapshot,
        $period->start(),
        $period->endInclusive(),
        $memberUser,
        $year,
      );
      if ($totals['hours']['total'] <= 0.00001) {
        $nextPeriod = $period->next();
        if ($nextPeriod->start() <= $period->start()) {
          break;
        }
        $period = $nextPeriod;
        continue;
      }

      $cards[] = $this->renderMemberPayPeriodCard($period, $totals);

      $nextPeriod = $period->next();
      if ($nextPeriod->start() <= $period->start()) {
        break;
      }
      if ($nextPeriod->start() > $yearEnd && $nextPeriod->endInclusive() > $yearEnd) {
        break;
      }
      $period = $nextPeriod;
    }

    if ($cards === []) {
      return '<p class="pay-period-empty">' . htmlspecialchars(Strings::i18n('EARNINGS_NO_LOGGED_HOURS_IN_YEAR_PAY_PERIODS'), ENT_QUOTES, 'UTF-8') . '</p>';
    }

    return '<div class="pay-period-cards" aria-label="' . htmlspecialchars(Strings::i18n('EARNINGS_PAY_PERIODS_CAROUSEL_FOR') . ' ' . $year, ENT_QUOTES, 'UTF-8') . '">' . implode('', $cards) . '</div>';
  }

  /**
   * @param array{
   *   hours: array{regular: float, overtime: float, travel: float, total: float},
   *   totals: array{grossCents: int, taxCents: int, netCents: int}
   * } $totals
   */
  private function renderMemberPayPeriodCard(PayPeriods $pp, array $totals): string
  {
    $startDate = Strings::formatLocalizedMediumDate($pp->start());
    $endDate = Strings::formatLocalizedMediumDate($pp->endInclusive());
    $regularHours = $this->formatNumberLocalized($totals['hours']['regular'], 2);
    $overtimeHours = $this->formatNumberLocalized($totals['hours']['overtime'], 2);
    $travelHours = $this->formatNumberLocalized($totals['hours']['travel'], 2);
    $totalHours = $this->formatNumberLocalized($totals['hours']['total'], 2);
    $grossDisplay = $this->formatCurrencyCentsLocalized((int) $totals['totals']['grossCents']);
    $taxDisplay = $this->formatCurrencyCentsLocalized((int) $totals['totals']['taxCents']);
    $netDisplay = $this->formatCurrencyCentsLocalized((int) $totals['totals']['netCents']);
    $payPeriodAria = $this->formatI18n('EARNINGS_PAY_PERIOD_ARIA_FMT', ['start' => $startDate, 'end' => $endDate]);
    $regularHoursLabel = htmlspecialchars(Strings::i18n('REGULAR_HOURS'), ENT_QUOTES, 'UTF-8');
    $otHoursLabel = htmlspecialchars(Strings::i18n('EARNINGS_BREAKDOWN_OT_HOURS_LABEL'), ENT_QUOTES, 'UTF-8');
    $travelHoursLabel = htmlspecialchars(Strings::i18n('TRAVEL_HOURS'), ENT_QUOTES, 'UTF-8');
    $totalHoursLabel = htmlspecialchars(Strings::i18n('EARNINGS_TOTAL_HOURS_LABEL'), ENT_QUOTES, 'UTF-8');
    $grossLabel = htmlspecialchars(Strings::i18n('GROSS'), ENT_QUOTES, 'UTF-8');
    $deductionsLabel = htmlspecialchars(Strings::i18n('DEDUCTIONS'), ENT_QUOTES, 'UTF-8');
    $netLabel = htmlspecialchars(Strings::i18n('NET'), ENT_QUOTES, 'UTF-8');

    return <<<HTML
<article class="pay-period-card" aria-label="{$payPeriodAria}">
  <header class="pay-period-card_header">
    <h3 class="pay-period-card_title">{$startDate} – {$endDate}</h3>
  </header>
  <div class="pay-period-card_body">
    <div class="pay-period-card_row"><span class="pay-period-card_label">{$regularHoursLabel}</span><span class="pay-period-card_value">{$regularHours}</span></div>
    <div class="pay-period-card_row"><span class="pay-period-card_label">{$otHoursLabel}</span><span class="pay-period-card_value">{$overtimeHours}</span></div>
    <div class="pay-period-card_row"><span class="pay-period-card_label">{$travelHoursLabel}</span><span class="pay-period-card_value">{$travelHours}</span></div>
    <div class="pay-period-card_row"><span class="pay-period-card_label">{$totalHoursLabel}</span><span class="pay-period-card_value">{$totalHours}</span></div>
    <div class="pay-period-card_row"><span class="pay-period-card_label">{$grossLabel}</span><span class="pay-period-card_value">{$grossDisplay}</span></div>
    <div class="pay-period-card_row"><span class="pay-period-card_label">{$deductionsLabel}</span><span class="pay-period-card_value">{$taxDisplay}</span></div>
    <div class="pay-period-card_row"><span class="pay-period-card_label"><strong>{$netLabel}</strong></span><span class="pay-period-card_value"><strong>{$netDisplay}</strong></span></div>
  </div>
</article>
HTML;
  }

  /**
   * @param MemberWorkSnapshot $workSnapshot
   * @return array{
   *   hours: array{regular: float, overtime: float, travel: float, total: float},
   *   totals: array{grossCents: int, taxCents: int, netCents: int}
   * }
   */
  private function getSnapshotTotalsForRange(
    array $workSnapshot,
    \DateTimeInterface $start,
    \DateTimeInterface $endInclusive,
    ?User $memberUser = null,
    ?int $taxYear = null,
  ): array {
    $startKey = $start->format('Y-m-d');
    $endKey = $endInclusive->format('Y-m-d');
    $regularHours = 0.0;
    $overtimeHours = 0.0;
    $travelHours = 0.0;
    $grossCents = 0;
    $taxCents = 0;
    $netCents = 0;

    /** @var array<int, Taxes> $taxByYear */
    $taxByYear = [];

    foreach ($workSnapshot['by_year'] as $yearData) {
      foreach ($yearData['daily_entries'] as $date => $entry) {
        $dateKey = (string) $date;
        if ($dateKey < $startKey || $dateKey > $endKey) {
          continue;
        }
        $regularHours += (float) ($entry['regular_hours'] ?? 0);
        $overtimeHours += (float) ($entry['overtime_hours'] ?? 0);
        $travelHours += (float) ($entry['travel_hours'] ?? 0);
        $rowGrossCents = Money::dollarsToCents((string) ($entry['gross'] ?? 0));
        $grossCents += $rowGrossCents;

        $storedRowTaxCents = Money::dollarsToCents((string) ($entry['tax'] ?? $entry['deductions'] ?? 0));
        $rowTaxCents = $storedRowTaxCents;
        $taxWasComputed = false;
        if ($rowTaxCents <= 0 && $rowGrossCents > 0) {
          $rowYear = (int) substr($dateKey, 0, 4);
          if ($rowYear < 2000 || $rowYear > 2100) {
            $rowYear = $taxYear ?? (int) date('Y');
          }
          if (!isset($taxByYear[$rowYear])) {
            $taxByYear[$rowYear] = new Taxes($this->resolveProvinceName($memberUser), $rowYear);
          }
          $rowTaxCents = (int) $taxByYear[$rowYear]->calculateTaxesCents($rowGrossCents)['totalDeductions'];
          $taxWasComputed = true;
        }
        $taxCents += $rowTaxCents;

        $rowNetCents = Money::dollarsToCents((string) ($entry['net'] ?? 0));
        if ($taxWasComputed || ($storedRowTaxCents <= 0 && $rowGrossCents > 0 && $rowNetCents >= $rowGrossCents)) {
          $rowNetCents = max(0, $rowGrossCents - $rowTaxCents);
        }
        $netCents += $rowNetCents;
      }
    }

    if ($netCents <= 0 && $grossCents > 0) {
      $netCents = max(0, $grossCents - $taxCents);
    }

    return [
      'hours' => [
        'regular' => $regularHours,
        'overtime' => $overtimeHours,
        'travel' => $travelHours,
        'total' => $regularHours + $overtimeHours + $travelHours,
      ],
      'totals' => [
        'grossCents' => $grossCents,
        'taxCents' => $taxCents,
        'netCents' => $netCents,
      ],
    ];
  }

  /**
   * @param MemberWorkSnapshot $workSnapshot
   * @return array<string, array<string, string>>
   */
  private function buildMemberDailyPayload(int $year, array $workSnapshot, ?User $memberUser): array
  {
    $yearBucket = $workSnapshot['by_year'][$year] ?? null;
    if ($yearBucket === null) {
      return [];
    }
    $entries = $yearBucket['daily_entries'];
    if ($entries === []) {
      return [];
    }

    $tax = new Taxes($this->resolveProvinceName($memberUser), $year);
    $payload = [];
    foreach ($entries as $dateKey => $entry) {
      $grossCents = Money::dollarsToCents((string) ($entry['gross'] ?? 0));
      $storedTax = (float) ($entry['tax'] ?? $entry['deductions'] ?? 0);
      $taxWasComputed = false;
      if ($storedTax <= 0.0 && $grossCents > 0) {
        $calculated = $tax->calculateTaxesCents($grossCents);
        $storedTax = ((int) $calculated['totalDeductions']) / 100;
        $taxWasComputed = true;
      }
      $netValue = (float) ($entry['net'] ?? 0);
      if ($taxWasComputed) {
        $netValue = ($grossCents - (int) round($storedTax * 100)) / 100;
      } elseif ($netValue <= 0.0 && $grossCents > 0) {
        $netValue = ($grossCents - Money::dollarsToCents((string) $storedTax)) / 100;
      }

      $payload[(string) $dateKey] = [
        'site_name' => (string) ($entry['site_name'] ?? ''),
        'wage' => (string) ($entry['wage'] ?? '0.00'),
        'hours' => (string) ($entry['hours'] ?? '0.00'),
        'regular_hours' => (string) ($entry['regular_hours'] ?? '0.00'),
        'overtime_hours' => (string) ($entry['overtime_hours'] ?? '0.00'),
        'travel_hours' => (string) ($entry['travel_hours'] ?? '0.00'),
        'living_out_allowance' => (string) ($entry['living_out_allowance'] ?? '0.00'),
        'gross' => (string) ($entry['gross'] ?? '0.00'),
        'tax' => number_format($storedTax, 2, '.', ''),
        'deductions' => number_format($storedTax, 2, '.', ''),
        'net' => number_format($netValue, 2, '.', ''),
      ];
    }

    ksort($payload);

    return $payload;
  }

  /**
   * @param MemberWorkSnapshot $workSnapshot
   */
  private function renderHistoricalIntelligence(int $year, array $workSnapshot): string
  {
    $payload = $this->buildHistoricalIntelligencePayload($year, $workSnapshot);
    $privateRendered = EarningsHistoricalIntelligenceBridge::render($year, $payload);
    if (is_string($privateRendered) && trim($privateRendered) !== '') {
      return $privateRendered;
    }

    return Render::template('earnings-historical-intelligence', $payload);
  }

  /**
   * @param MemberWorkSnapshot $workSnapshot
   * @return array<string, string>
   */
  private function buildHistoricalIntelligencePayload(int $year, array $workSnapshot): array
  {
    $availableYears = $workSnapshot['years'];
    $normalizedYears = array_values(array_unique(array_map(static fn (mixed $candidate): int => (int) $candidate, $availableYears)));
    sort($normalizedYears);
    $normalizedYears = array_values(array_filter($normalizedYears, static fn (int $candidate): bool => $candidate <= $year));

    if ($normalizedYears === []) {
      $normalizedYears = [$year];
    }

    $grossByYear = [];
    foreach ($normalizedYears as $candidateYear) {
      $grossByYear[$candidateYear] = (int) ($workSnapshot['gross_by_year_cents'][$candidateYear] ?? 0);
    }

    $currentGrossCents = (int) ($grossByYear[$year] ?? 0);
    $priorGrossCents = (int) ($grossByYear[$year - 1] ?? 0);
    $yoyPercent = null;
    if ($priorGrossCents > 0) {
      $yoyPercent = (($currentGrossCents - $priorGrossCents) / $priorGrossCents) * 100.0;
    }

    $trailingWindowYears = array_values(array_filter(
      [$year - 2, $year - 1, $year],
      static fn (int $candidate): bool => array_key_exists($candidate, $grossByYear),
    ));
    $trailingGross = array_map(static fn (int $candidate): int => (int) $grossByYear[$candidate], $trailingWindowYears);
    $trailingAverageCents = $trailingGross === []
      ? 0
      : (int) round(array_sum($trailingGross) / max(1, count($trailingGross)));

    $peakYear = $year;
    $peakGrossCents = $currentGrossCents;
    foreach ($grossByYear as $candidateYear => $candidateGrossCents) {
      if ($candidateGrossCents > $peakGrossCents) {
        $peakYear = (int) $candidateYear;
        $peakGrossCents = (int) $candidateGrossCents;
      }
    }

    $stabilityIndex = null;
    if (count($trailingGross) >= 2) {
      $maxGross = (float) max($trailingGross);
      $minGross = (float) min($trailingGross);
      if ($maxGross > 0.0) {
        $stabilityIndex = max(0.0, min(100.0, 100.0 - (($maxGross - $minGross) / $maxGross) * 100.0));
      }
    }

    $activeMonths = $workSnapshot['by_year'][$year]['active_months'] ?? [];
    $regime = $trailingAverageCents > 0 && $currentGrossCents >= $trailingAverageCents
      ? Strings::i18n('EARNINGS_HI_REGIME_ABOVE')
      : Strings::i18n('EARNINGS_HI_REGIME_BELOW');
    $notAvailable = Strings::i18n('EARNINGS_HI_NOT_AVAILABLE');
    $stabilityOf = Strings::i18n('EARNINGS_HI_STABILITY_OF');

    return [
      '__HI_ID__' => sprintf('member-reports-hi-%d', $year),
      '__HI_ARIA_LABEL__' => $this->formatI18nPlain('EARNINGS_HI_ARIA_MEMBER', ['year' => (string) $year]),
      '__HI_TITLE__' => htmlspecialchars(Strings::i18n('EARNINGS_HI_TITLE'), ENT_QUOTES, 'UTF-8'),
      '__HI_SUBTITLE__' => $this->formatI18nPlain('EARNINGS_HI_SUBTITLE_BUSINESS', ['year' => (string) $year]),
      '__HI_LABEL_YEARS_OBSERVED__' => htmlspecialchars(Strings::i18n('EARNINGS_HI_YEARS_OBSERVED'), ENT_QUOTES, 'UTF-8'),
      '__HI_LABEL_ACTIVE_MONTHS__' => htmlspecialchars(Strings::i18n('EARNINGS_HI_ACTIVE_MONTHS'), ENT_QUOTES, 'UTF-8'),
      '__HI_LABEL_TRAILING_BASELINE__' => htmlspecialchars(Strings::i18n('EARNINGS_HI_TRAILING_BASELINE'), ENT_QUOTES, 'UTF-8'),
      '__HI_LABEL_YOY_SIGNAL__' => htmlspecialchars(Strings::i18n('EARNINGS_HI_YOY_SIGNAL'), ENT_QUOTES, 'UTF-8'),
      '__HI_LABEL_REGIME__' => htmlspecialchars(Strings::i18n('EARNINGS_HI_REGIME'), ENT_QUOTES, 'UTF-8'),
      '__HI_LABEL_PEAK_YEAR__' => htmlspecialchars(Strings::i18n('EARNINGS_HI_PEAK_YEAR'), ENT_QUOTES, 'UTF-8'),
      '__HI_LABEL_STABILITY_INDEX__' => htmlspecialchars(Strings::i18n('EARNINGS_HI_STABILITY_INDEX'), ENT_QUOTES, 'UTF-8'),
      '__HI_YEARS_OBSERVED__' => $this->formatNumberLocalized(count($grossByYear), 0),
      '__HI_ACTIVE_MONTHS__' => $this->formatNumberLocalized(count($activeMonths), 0),
      '__HI_TRAILING_BASELINE__' => $this->formatCurrencyCentsLocalized($trailingAverageCents),
      '__HI_YOY_SIGNAL__' => $yoyPercent === null ? $notAvailable : $this->formatSignedPercentLocalized($yoyPercent, 1),
      '__HI_REGIME__' => htmlspecialchars($regime, ENT_QUOTES, 'UTF-8'),
      '__HI_PEAK_YEAR__' => (string) $peakYear,
      '__HI_PEAK_GROSS__' => $this->formatCurrencyCentsLocalized($peakGrossCents),
      '__HI_STABILITY_INDEX__' => $stabilityIndex === null
        ? $notAvailable
        : $this->formatNumberLocalized($stabilityIndex, 1) . $stabilityOf,
      '__HI_NOTE__' => htmlspecialchars(Strings::i18n('EARNINGS_HI_NOTE_BUSINESS'), ENT_QUOTES, 'UTF-8'),
    ];
  }

  private function resolveProvinceName(?User $memberUser): string
  {
    if (!$memberUser instanceof User) {
      return 'Alberta';
    }

    $candidate = trim($memberUser->province);
    if ($candidate === '') {
      return 'Alberta';
    }

    $upper = strtoupper($candidate);
    if (isset(self::PROVINCE_NAMES[$upper])) {
      return self::PROVINCE_NAMES[$upper];
    }

    return $candidate;
  }

  private function buildAsyncSkeletonGrid(int $cols, int $rows): string
  {
    // CSP forbids inline styles: layout comes from .sk-grid / .sk-grid--cols-N
    // utility classes defined in css/common (supports 1-12 columns).
    $cols = max(1, min(12, $cols));
    $cell = '<span class="sk-line"></span>';
    $row = '<div class="skeleton sk-grid sk-grid--cols-' . $cols . '">' . str_repeat($cell, $cols) . '</div>';

    return '<div aria-hidden="true">' . str_repeat($row, $rows) . '</div>';
  }

  /**
   * @param array<string, scalar|null> $params
   */
  private function formatI18nPlain(string $key, array $params = []): string
  {
    $label = Strings::i18n($key);
    foreach ($params as $paramKey => $paramValue) {
      $label = str_replace('{' . $paramKey . '}', (string) $paramValue, $label);
    }

    return htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
  }

  /**
   * @param array<string, scalar|null> $params
   */
  private function formatI18n(string $key, array $params = []): string
  {
    $label = Strings::i18n($key);
    foreach ($params as $paramKey => $paramValue) {
      $label = str_replace('{' . $paramKey . '}', (string) $paramValue, $label);
    }

    return htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
  }

  private function formatNumberLocalized(int|float $value, int $fractionDigits = 0): string
  {
    if (class_exists('\\NumberFormatter')) {
      $formatter = new \NumberFormatter($this->numberLocale(), \NumberFormatter::DECIMAL);
      $formatter->setAttribute(\NumberFormatter::GROUPING_USED, 1);
      $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $fractionDigits);
      $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $fractionDigits);
      $formatted = $formatter->format($value);
      if (is_string($formatted)) {
        return $formatted;
      }
    }

    return number_format((float) $value, $fractionDigits, '.', ',');
  }

  private function numberLocale(): string
  {
    if (defined('USER_LOCALE')) {
      $locale = trim((string) USER_LOCALE);
      if ($locale !== '') {
        return $locale;
      }
    }

    return 'en_US';
  }

  private function formatCurrencyCentsLocalized(int $cents): string
  {
    return '$' . $this->formatNumberLocalized($cents / 100, 2);
  }

  private function formatSignedPercentLocalized(float $value, int $fractionDigits = 1): string
  {
    $sign = $value > 0 ? '+' : ($value < 0 ? '-' : '');

    return $sign . $this->formatNumberLocalized(abs($value), $fractionDigits) . '%';
  }
}
