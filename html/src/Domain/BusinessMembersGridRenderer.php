<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * Builds the business members datagrid HTML shared by the API and SSR page shell.
 */
final class BusinessMembersGridRenderer
{
  /**
   * @param array<string, mixed> $options
   */
  private function optionString(array $options, string $key, string $default = ''): string
  {
    if (!isset($options[$key]) || !is_scalar($options[$key])) {
      return $default;
    }

    return trim((string) $options[$key]);
  }

  /**
   * @param array<string, mixed> $options
   */
  private function optionInt(array $options, string $key, int $default = 0): int
  {
    if (!isset($options[$key]) || !is_scalar($options[$key])) {
      return $default;
    }

    return (int) $options[$key];
  }

  /**
   * @param array<int, mixed> $members
   * @return array{members: int, managers: int, sites: int, pending: int}
   */
  public static function summarizePageMetrics(array $members, string $businessId, bool $canViewAccessMetrics = true): array
  {
    $businessId = trim($businessId);
    $managerCount = 0;

    foreach ($members as $member) {
      if (!is_array($member)) {
        continue;
      }

      $role = isset($member['role']) && is_scalar($member['role'])
        ? strtolower(trim((string) $member['role']))
        : '';
      if ($role === 'coordinator') {
        $managerCount++;
      }
    }

    $dashboardMetrics = $businessId !== ''
      ? BusinessDashboardMetrics::forBusiness($businessId, $canViewAccessMetrics)
      : BusinessDashboardMetrics::emptyMetrics();

    $pendingInvites = $dashboardMetrics['pending_invites'];
    $pendingRequests = $dashboardMetrics['pending_requests'];
    $pending = 0;
    if ($canViewAccessMetrics) {
      $pending += is_int($pendingInvites) ? max(0, $pendingInvites) : 0;
      $pending += is_int($pendingRequests) ? max(0, $pendingRequests) : 0;
    }

    return [
      'members' => count($members),
      'managers' => $managerCount,
      'sites' => max(0, $dashboardMetrics['sites']),
      'pending' => $pending,
    ];
  }

  /**
   * @param array<string, mixed> $options
   * @return array{
   *   success: bool,
   *   message: string,
   *   html: string,
   *   member_count: int,
   *   metrics?: array{members: int, managers: int, sites: int, pending: int},
   *   service_result?: array{success: bool, message: string, data: array<string, mixed>}
   * }
   */
  public function renderForBusiness(string $actorUUID, string $businessId, array $options = []): array
  {
    $actorUUID = trim($actorUUID);
    $businessId = trim($businessId);

    if ($actorUUID === '' || $businessId === '') {
      return [
        'success' => false,
        'message' => Strings::i18n('BUSINESSES_SELECT_FIRST'),
        'html' => $this->emptyMessage(Strings::i18n('BUSINESSES_SELECT_FIRST')),
        'member_count' => 0,
        'metrics' => [
          'members' => 0,
          'managers' => 0,
          'sites' => 0,
          'pending' => 0,
        ],
      ];
    }

    $service = new BusinessDiscoveryService();
    $result = $service->listConnections($actorUUID, $businessId);

    if (!$result['success']) {
      $message = trim($result['message']) !== ''
        ? $result['message']
        : Strings::i18n('BUSINESSES_FAILED_LOAD_MEMBERS_GRID');

      return [
        'success' => false,
        'message' => $message,
        'html' => $this->emptyMessage($message),
        'member_count' => 0,
        'metrics' => [
          'members' => 0,
          'managers' => 0,
          'sites' => 0,
          'pending' => 0,
        ],
        'service_result' => $result,
      ];
    }

    $members = is_array($result['data']['members'] ?? null)
      ? $result['data']['members']
      : [];

    $html = $this->renderMembers($members, array_merge($options, [
      'actor_uuid' => $actorUUID,
      'business_id' => $businessId,
    ]));

    return [
      'success' => true,
      'message' => '[Business] Members grid rendered.',
      'html' => $html,
      'member_count' => count($members),
      'metrics' => self::summarizePageMetrics($members, $businessId),
    ];
  }

  /**
   * @param array<int, mixed> $members
   * @param array<string, mixed> $options
   */
  public function renderMembers(array $members, array $options = []): string
  {
    $search = $this->optionString($options, 'search');
    $sort = $this->optionString($options, 'sort', 'full_name');
    $direction = strtolower($this->optionString($options, 'direction', 'asc')) === 'desc' ? 'desc' : 'asc';
    $page = max(1, $this->optionInt($options, 'page', 1));
    $roleFilter = strtolower($this->optionString($options, 'role'));

    if ($roleFilter !== '') {
      $members = array_values(array_filter($members, static function (mixed $member) use ($roleFilter): bool {
        if (!is_array($member)) {
          return false;
        }

        $role = isset($member['role']) && is_scalar($member['role'])
          ? strtolower((string) $member['role'])
          : '';

        return $role === $roleFilter;
      }));
    }

    $businessId = $this->optionString($options, 'business_id');
    $actorUUID = $this->optionString($options, 'actor_uuid');
    $memberUuids = [];
    foreach ($members as $member) {
      if (!is_array($member)) {
        continue;
      }

      $memberUuid = isset($member['user_uuid']) && is_scalar($member['user_uuid'])
        ? trim((string) $member['user_uuid'])
        : (isset($member['uuid']) && is_scalar($member['uuid']) ? trim((string) $member['uuid']) : '');
      if ($memberUuid !== '') {
        $memberUuids[] = $memberUuid;
      }
    }

    $fresh = (bool) ($options['fresh'] ?? false);
    $financialCacheOnly = (bool) ($options['financial_cache_only'] ?? false);
    $financialSummary = new BusinessMembersFinancialSummary();
    $financialByMember = $businessId !== ''
      ? $financialSummary->forBusinessMembers(
        $businessId,
        $memberUuids,
        null,
        $fresh,
        false,
        $financialCacheOnly,
        $actorUUID,
      )
      : [];

    $lastActiveByMember = $this->lastActiveTimestampsForMembers($memberUuids);
    $activeConsentByMember = $businessId !== ''
      ? $this->activeBusinessConsentByMember($businessId, $memberUuids)
      : [];

    $rows = array_map(function (mixed $member) use ($financialByMember, $lastActiveByMember, $activeConsentByMember): array {
      if (!is_array($member)) {
        return [
          'id' => '',
          'full_name' => '',
          'email' => '',
          'role_slug' => '',
          'role' => '',
          'status' => '',
          'joined_at' => '',
          'joined_at_sort' => '',
          'last_active_at' => '',
          'last_active_at_sort' => '',
          'hours' => '',
          'hours_sort' => '',
          'earnings' => '',
          'earnings_sort' => '',
        ];
      }

      $joinedAt = '';
      foreach (['joined_at', 'owner_since', 'accepted_at', 'created_at'] as $field) {
        if (isset($member[$field]) && is_scalar($member[$field])) {
          $candidate = trim((string) $member[$field]);
          if ($candidate !== '') {
            $joinedAt = $candidate;
            break;
          }
        }
      }

      $roleSlug = isset($member['role']) && is_scalar($member['role'])
        ? strtolower(trim((string) $member['role']))
        : '';

      $memberUuid = isset($member['user_uuid']) && is_scalar($member['user_uuid'])
        ? (string) $member['user_uuid']
        : (isset($member['uuid']) && is_scalar($member['uuid']) ? (string) $member['uuid'] : '');
      $financial = is_array($financialByMember[$memberUuid] ?? null) ? $financialByMember[$memberUuid] : [];
      $connectionStatus = isset($member['status']) && is_scalar($member['status']) ? strtolower(trim((string) $member['status'])) : '';
      $hasActiveConsent = $memberUuid !== '' && ($activeConsentByMember[$memberUuid] ?? false);
      $dataAccess = $this->businessDataAccessStatus($connectionStatus, $hasActiveConsent);
      $lastActiveAt = $lastActiveByMember[$memberUuid] ?? 0;

      $totalHours = (float) ($financial['total_hours'] ?? 0);
      $regHours = (float) ($financial['reg_hours'] ?? 0);
      $otHours = (float) ($financial['ot_hours'] ?? 0);
      $ytdGross = (float) ($financial['ytd_gross'] ?? 0);
      $trailingBaseline = (float) ($financial['trailing_baseline'] ?? 0);

      return [
        'id' => $memberUuid,
        'full_name' => isset($member['full_name']) && is_scalar($member['full_name']) ? (string) $member['full_name'] : '',
        'email' => isset($member['email']) && is_scalar($member['email']) ? (string) $member['email'] : '',
        'role_slug' => $roleSlug,
        'role' => BusinessNav::roleDisplayLabel($roleSlug),
        'status' => isset($member['status']) && is_scalar($member['status']) ? (string) $member['status'] : '',
        'data_access_label' => $dataAccess['label'],
        'data_access_class' => $dataAccess['class'],
        'data_access_title' => $dataAccess['title'],
        'joined_at' => $this->formatJoinedDateLabel($joinedAt),
        'joined_at_sort' => $joinedAt,
        'last_active_at' => $this->formatLastActiveDateLabel($lastActiveAt),
        'last_active_at_sort' => $lastActiveAt > 0 ? sprintf('%020d', $lastActiveAt) : '',
        'hours' => $this->formatHoursPrimaryLine($totalHours),
        'hours_sort' => sprintf('%.2f', $totalHours),
        'hours_reg' => $regHours,
        'hours_ot' => $otHours,
        'earnings' => $this->formatEarningsCompact($ytdGross),
        'earnings_sort' => sprintf('%.2f', $ytdGross),
        'earnings_trend' => $this->formatEarningsTrend($ytdGross, $trailingBaseline),
        'ytd_gross_raw' => $ytdGross,
        'trailing_baseline_raw' => $trailingBaseline,
      ];
    }, $members);

    if ($search !== '') {
      $needle = mb_strtolower($search);
      $rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
        $haystacks = [
          $row['full_name'],
          $row['email'],
          $row['role'],
          $row['status'],
          $row['joined_at'],
          $row['joined_at_sort'],
          $row['last_active_at'],
          $row['last_active_at_sort'],
          $row['hours'],
          $row['hours_sort'],
          $row['earnings'],
          $row['earnings_sort'],
        ];

        foreach ($haystacks as $haystack) {
          if (mb_stripos($haystack, $needle) !== false) {
            return true;
          }
        }

        return false;
      }));
    }

    $sortFieldMap = [
      'full_name' => 'full_name',
      'email' => 'email',
      'role' => 'role',
      'status' => 'status',
      'joined_at' => 'joined_at_sort',
      'last_active_at' => 'last_active_at_sort',
      'hours' => 'hours_sort',
      'total_hours' => 'hours_sort',
      'earnings' => 'earnings_sort',
      'ytd_gross' => 'earnings_sort',
    ];
    $sortKey = $sortFieldMap[$sort] ?? 'full_name';

    $allowedSorts = array_keys($sortFieldMap);
    if (!in_array($sort, $allowedSorts, true)) {
      $sort = 'full_name';
      $sortKey = 'full_name';
    }

    usort($rows, static function (array $a, array $b) use ($sortKey, $direction): int {
      $aValue = $a[$sortKey];
      $bValue = $b[$sortKey];
      $comparison = strcasecmp((string) $aValue, (string) $bValue);

      return $direction === 'desc' ? -$comparison : $comparison;
    });

    $grid = DataGrid::create('business-members', Strings::i18n('BUSINESSES_MEMBERS_H3'));
    $grid->enableSearch(Strings::i18n('BUSINESSES_MEMBERS_FILTER_PLACEHOLDER'));
    $grid->setSearchValue($search);
    $grid->setToolbarLayout('search_pagination');
    $grid->setToolbarAfterStartHtml($this->membersToolbarSlotSkeleton());
    $grid->setPaginationArrowsOnly();
    $grid->enableSorting();
    $grid->enableColumnVisibility();
    $grid->addColumn('full_name', Strings::i18n('BUSINESSES_MEMBERS_COL_NAME_DETAILS'), true, null, null, true, false);
    $grid->addColumn('joined_at', Strings::i18n('BUSINESSES_MEMBERS_COL_JOINED'), true, null, null, true, true);
    $grid->addColumn('last_active_at', Strings::i18n('BUSINESSES_MEMBERS_COL_LAST_ACTIVE'), true, null, null, true, true);
    $grid->addColumn('hours', Strings::i18n('BUSINESSES_MEMBERS_COL_HOURS'), true, null, 'right', true, true);
    $grid->addColumn('earnings', Strings::i18n('BUSINESSES_MEMBERS_COL_EARNINGS'), true, null, 'right', true, true);
    $grid->addRowAction('revoke', Strings::i18n('BUSINESSES_REVOKE'));
    $grid->setRowActionsHeaderLabel('');
    $grid->setItemLabel(Strings::i18n('BUSINESSES_MEMBERS_ITEM_LABEL'));

    $pager = ArrayPager::fromArray($rows, [
      'pageSize' => max(1, count($rows)),
    ]);
    $pager->setPage(1);
    $html = $this->injectMemberRowEnhancements($grid->table($pager), array_values($pager->getRows()), $businessId);

    $start = $pager->getTotal() === 0 ? 0 : (($pager->getPage() - 1) * $pager->getPageSize()) + 1;
    $end = min($pager->getPage() * $pager->getPageSize(), $pager->getTotal());
    $total = $pager->getTotal();

    $searchAttr = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
    $sortAttr = htmlspecialchars($sort, ENT_QUOTES, 'UTF-8');
    $directionAttr = htmlspecialchars($direction, ENT_QUOTES, 'UTF-8');
    $pattern = '/(<div\\s+id="business-members"[^>]*data-grid="business-members"[^>]*)>/';
    $replacement = '$1 data-search="' . $searchAttr . '" data-sort="' . $sortAttr . '" data-direction="' . $directionAttr . '" data-pagination-start="' . $start . '" data-pagination-end="' . $end . '" data-pagination-total="' . $total . '" data-total-pages="' . $pager->getTotalPages() . '">';

    return (string) preg_replace($pattern, $replacement, $html, 1);
  }

  /**
   * Loading skeleton.
   */
  public function loadingSkeleton(): string
  {
    return DataGrid::loadingSkeleton(7, 4);
  }

  /**
   * Empty message.
   */
  public function emptyMessage(string $message): string
  {
    return '<div class="datagrid_empty">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
  }

  /**
   * Format joined date label.
   */
  private function formatJoinedDateLabel(string $raw): string
  {
    $raw = trim($raw);
    if ($raw === '') {
      return '';
    }

    $date = $this->parseJoinedTimestamp($raw);
    if (!$date instanceof \DateTimeImmutable) {
      return $raw;
    }

    $today = new \DateTimeImmutable('today');
    $joinedDay = $date->setTime(0, 0, 0);
    $dayDiff = (int) $today->diff($joinedDay)->format('%r%a');

    if ($dayDiff === 0) {
      return Strings::i18n('TODAY');
    }

    if ($dayDiff === -1) {
      return Strings::i18n('BUSINESSES_MEMBERS_JOINED_YESTERDAY');
    }

    if ($dayDiff < 0 && $dayDiff >= -6) {
      return sprintf(Strings::i18n('BUSINESSES_MEMBERS_JOINED_DAYS_AGO'), abs($dayDiff));
    }

    $currentYear = (int) $today->format('Y');
    $joinedYear = (int) $joinedDay->format('Y');
    if ($joinedYear === $currentYear) {
      return $this->formatLocalizedJoinDate($joinedDay, false);
    }

    return $this->formatLocalizedJoinDate($joinedDay, true);
  }

  /**
   * Format last active timestamp label.
   */
  private function formatLastActiveDateLabel(int $timestamp): string
  {
    if ($timestamp <= 0) {
      return Strings::i18n('NEVER');
    }

    $date = (new \DateTimeImmutable('@' . $timestamp))->setTimezone(new \DateTimeZone('UTC'));
    $today = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));
    $activeDay = $date->setTime(0, 0, 0);
    $dayDiff = (int) $today->diff($activeDay)->format('%r%a');

    if ($dayDiff === 0) {
      return Strings::i18n('TODAY');
    }

    if ($dayDiff === -1) {
      return Strings::i18n('BUSINESSES_MEMBERS_JOINED_YESTERDAY');
    }

    if ($dayDiff < 0 && $dayDiff >= -6) {
      return sprintf(Strings::i18n('BUSINESSES_MEMBERS_JOINED_DAYS_AGO'), abs($dayDiff));
    }

    $currentYear = (int) $today->format('Y');
    $activeYear = (int) $activeDay->format('Y');
    if ($activeYear === $currentYear) {
      return $this->formatLocalizedJoinDate($activeDay, false);
    }

    return $this->formatLocalizedJoinDate($activeDay, true);
  }

  /**
   * @param array<int, string> $memberUuids
   * @return array<string, int>
   */
  private function lastActiveTimestampsForMembers(array $memberUuids): array
  {
    $memberUuids = array_values(array_unique(array_filter(array_map(
      static fn (string $uuid): string => trim($uuid),
      $memberUuids,
    ))));
    if ($memberUuids === []) {
      return [];
    }

    $lastActive = array_fill_keys($memberUuids, 0);
    $userKeys = array_map(static fn (string $uuid): string => Keys::USER . ':' . $uuid, $memberUuids);
    $userHashes = Database::pipelineHgetall($userKeys);

    foreach ($memberUuids as $index => $uuid) {
      $userKey = $userKeys[$index];
      $hash = $userHashes[$userKey] ?? [];
      $lastActive[$uuid] = max(
        $lastActive[$uuid],
        $this->timestampFromStoredValue($hash['last_signin'] ?? ''),
      );
    }

    $memberSet = array_fill_keys($memberUuids, true);
    $sessionPrefix = Keys::SESSION . ':';
    $sessionKeys = array_values(array_filter(
      Database::scanKeys($sessionPrefix . '*'),
      static fn (string $key): bool => preg_match('/^' . preg_quote($sessionPrefix, '/') . '[a-f0-9]{64}$/', $key) === 1,
    ));
    $sessionHashes = Database::pipelineHgetall($sessionKeys);

    foreach ($sessionHashes as $session) {
      $uuid = trim($session['user_uuid'] ?? '');
      if ($uuid === '' || !isset($memberSet[$uuid])) {
        continue;
      }

      $activity = $session['last_activity'] ?? ($session['created_at'] ?? '');
      $lastActive[$uuid] = max($lastActive[$uuid], $this->timestampFromStoredValue($activity));
    }

    return $lastActive;
  }

  /**
   * Parse stored timestamp values from unix seconds or date strings.
   */
  private function timestampFromStoredValue(string $raw): int
  {
    $raw = trim($raw);
    if ($raw === '') {
      return 0;
    }

    if (ctype_digit($raw)) {
      return (int) $raw;
    }

    $date = $this->parseJoinedTimestamp($raw);

    return $date instanceof \DateTimeImmutable ? $date->getTimestamp() : 0;
  }

  /**
   * Resolve user locale.
   */
  private function resolveUserLocale(): string
  {
    if (defined('USER_LOCALE')) {
      $locale = trim((string) USER_LOCALE);
      if ($locale !== '') {
        return $locale;
      }
    }

    return 'en-CA';
  }

  /**
   * Format localized join date.
   */
  private function formatLocalizedJoinDate(\DateTimeImmutable $date, bool $includeYear): string
  {
    if (!class_exists('\IntlDateFormatter')) {
      return $includeYear ? $date->format('M j, Y') : $date->format('M j');
    }

    $pattern = $includeYear ? 'MMM d, yyyy' : 'MMM d';
    $formatter = new \IntlDateFormatter(
      $this->resolveUserLocale(),
      \IntlDateFormatter::NONE,
      \IntlDateFormatter::NONE,
      'UTC',
      \IntlDateFormatter::GREGORIAN,
      $pattern
    );
    $formatted = $formatter->format($date);
    if (is_string($formatted) && $formatted !== '') {
      return $formatted;
    }

    return $includeYear ? $date->format('M j, Y') : $date->format('M j');
  }

  /**
   * Parse joined timestamp.
   */
  private function parseJoinedTimestamp(string $raw): ?\DateTimeImmutable
  {
    $raw = trim($raw);
    if ($raw === '') {
      return null;
    }

    try {
      return new \DateTimeImmutable($raw);
    } catch (\Throwable) {
      // Fall through to legacy formats.
    }

    $legacyFormats = [
      'm/d/y H:i',
      'm/d/y',
      'Y-m-d H:i:s',
      'Y-m-d',
    ];

    foreach ($legacyFormats as $format) {
      $parsed = \DateTimeImmutable::createFromFormat($format, $raw);
      if ($parsed instanceof \DateTimeImmutable) {
        return $parsed;
      }
    }

    return null;
  }

  /**
   * Format hours primary line.
   */
  private function formatHoursPrimaryLine(float $totalHours): string
  {
    return $this->formatHoursCompact($totalHours);
  }

  /**
   * Format hours compact.
   */
  private function formatHoursCompact(float $hours): string
  {
    if (abs($hours - round($hours)) < 0.01) {
      return (int) round($hours) . 'h';
    }

    return rtrim(rtrim(number_format($hours, 1, '.', ''), '0'), '.') . 'h';
  }

  /**
   * Format hours subline.
   */
  private function formatHoursSubline(float $regHours, float $otHours): string
  {
    $reg = abs($regHours - round($regHours)) < 0.01
      ? (string) (int) round($regHours)
      : rtrim(rtrim(number_format($regHours, 1, '.', ''), '0'), '.');
    $ot = abs($otHours - round($otHours)) < 0.01
      ? (string) (int) round($otHours)
      : rtrim(rtrim(number_format($otHours, 1, '.', ''), '0'), '.');

    return sprintf(Strings::i18n('BUSINESSES_MEMBERS_HOURS_OT_SUBLINE'), $reg, $ot);
  }

  /**
   * Format earnings compact.
   */
  private function formatEarningsCompact(float $amount): string
  {
    if ($amount >= 1000000) {
      return '$' . rtrim(rtrim(number_format($amount / 1000000, 1, '.', ''), '0'), '.') . 'M';
    }

    if ($amount >= 1000) {
      return '$' . rtrim(rtrim(number_format($amount / 1000, 1, '.', ''), '0'), '.') . 'k';
    }

    if ($amount >= 100) {
      return '$' . number_format($amount, 0, '.', ',');
    }

    return '$' . number_format($amount, 2, '.', ',');
  }

  /**
   * Format earnings trend.
   */
  private function formatEarningsTrend(float $ytdGross, float $trailingBaseline): string
  {
    if ($trailingBaseline <= 0.0) {
      return '';
    }

    $deltaPct = (($ytdGross - $trailingBaseline) / $trailingBaseline) * 100;
    $arrow = $deltaPct >= 0 ? '↑' : '↓';

    return $arrow . ' ' . rtrim(rtrim(number_format(abs($deltaPct), 1, '.', ''), '0'), '.') . '%';
  }

  /**
   * @param list<string> $memberUUIDs
   * @return array<string, bool>
   */
  private function activeBusinessConsentByMember(string $businessId, array $memberUUIDs): array
  {
    $businessId = trim($businessId);
    if ($businessId === '' || $memberUUIDs === []) {
      return [];
    }

    $consentIdsByMember = [];
    $consentKeysById = [];
    foreach (array_values(array_unique($memberUUIDs)) as $memberUUID) {
      $memberUUID = trim($memberUUID);
      if ($memberUUID === '') {
        continue;
      }

      foreach (Database::smembers(Keys::businessConsentsByUser($memberUUID)) as $consentIdRaw) {
        $consentId = trim((string) $consentIdRaw);
        if ($consentId === '') {
          continue;
        }

        $consentIdsByMember[$memberUUID][] = $consentId;
        $consentKeysById[$consentId] = Keys::businessConsent($consentId);
      }
    }

    if ($consentKeysById === []) {
      return [];
    }

    $consentsByKey = Database::pipelineHgetall(array_values($consentKeysById));
    $consentsById = [];
    foreach ($consentKeysById as $consentId => $consentKey) {
      $consentsById[$consentId] = $consentsByKey[$consentKey] ?? [];
    }

    $activeByMember = [];
    foreach ($consentIdsByMember as $memberUUID => $consentIds) {
      foreach ($consentIds as $consentId) {
        $consent = $consentsById[$consentId] ?? [];
        if (
          $consent !== []
          && (string) ($consent['business_id'] ?? '') === $businessId
          && (string) ($consent['user_uuid'] ?? '') === $memberUUID
          && (string) ($consent['status'] ?? '') === BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE
        ) {
          $activeByMember[$memberUUID] = true;
          break;
        }
      }
    }

    return $activeByMember;
  }

  /** @return array{label: string, class: string, title: string} */
  private function businessDataAccessStatus(string $connectionStatus, bool $hasActiveConsent): array
  {
    $connectionStatus = strtolower(trim($connectionStatus));
    if ($connectionStatus === BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE && $hasActiveConsent) {
      return [
        'label' => Strings::i18n('SETTINGS_DATA_CONSENT_ACTIVE_PILL'),
        'class' => 'is-active',
        'title' => Strings::i18n('SETTINGS_DATA_CONSENT_ACTIVE_DESC'),
      ];
    }

    if ($connectionStatus === BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE) {
      return [
        'label' => Strings::i18n('SETTINGS_DATA_CONSENT_SETUP_PILL'),
        'class' => 'is-setup',
        'title' => Strings::i18n('BUSINESS_CONSENT_STATUS_MISSING_SETUP'),
      ];
    }

    if ($connectionStatus === BusinessDiscoveryService::MEMBERSHIP_STATE_PENDING || $connectionStatus === BusinessDiscoveryService::MEMBERSHIP_STATE_CONSENTED) {
      return [
        'label' => Strings::i18n('SETTINGS_DATA_CONSENT_WAITING_PILL'),
        'class' => 'is-waiting',
        'title' => Strings::i18n('BUSINESS_CONSENT_STATUS_PENDING'),
      ];
    }

    if ($connectionStatus === BusinessDiscoveryService::MEMBERSHIP_STATE_REVOKED) {
      return [
        'label' => Strings::i18n('SETTINGS_DATA_CONSENT_REVOKED_PILL'),
        'class' => 'is-revoked',
        'title' => Strings::i18n('BUSINESS_CONSENT_STATUS_REVOKED'),
      ];
    }

    return [
      'label' => Strings::i18n('SETTINGS_DATA_CONSENT_UNAVAILABLE_PILL'),
      'class' => 'is-unavailable',
      'title' => Strings::i18n('BUSINESS_CONSENT_STATUS_SKIPPED'),
    ];
  }

  /**
   * @param array<int, mixed> $rows
   */
  private function injectMemberRowEnhancements(string $html, array $rows, string $businessId): string
  {
    $html = $this->injectColumnGearMenu($html);
    $html = $this->injectMemberSelectionHeaderColumn($html);
    $html = $this->injectMemberDetailsCells($html, $rows, $businessId);
    $html = $this->injectJoinedDateCells($html, $rows);
    $html = $this->injectHoursCells($html, $rows);
    $html = $this->injectEarningsCells($html, $rows);
    $html = $this->injectMemberRowActionMenus($html, $rows);
    $html = $this->injectMemberSelectionCheckboxes($html, $rows);

    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $memberId = isset($row['id']) && is_scalar($row['id']) ? trim((string) $row['id']) : '';
      $memberName = isset($row['full_name']) && is_scalar($row['full_name']) ? trim((string) $row['full_name']) : '';
      $memberRole = isset($row['role_slug']) && is_scalar($row['role_slug'])
        ? strtolower(trim((string) $row['role_slug']))
        : '';
      if ($memberId === '') {
        continue;
      }

      $escapedMemberId = preg_quote(htmlspecialchars($memberId, ENT_QUOTES, 'UTF-8'), '/');
      $escapedMemberName = htmlspecialchars($memberName, ENT_QUOTES, 'UTF-8');
      $escapedMemberRole = htmlspecialchars($memberRole, ENT_QUOTES, 'UTF-8');
      $memberNameAttr = $memberName !== '' ? ' data-member-name="' . $escapedMemberName . '"' : '';
      $memberRoleAttr = $memberRole !== '' ? ' data-member-role="' . $escapedMemberRole . '"' : '';
      $ariaLabel = $memberName !== ''
        ? ' aria-label="' . htmlspecialchars(sprintf(Strings::i18n('BUSINESSES_MEMBERS_VIEW_REPORTS_ARIA'), $memberName), ENT_QUOTES, 'UTF-8') . '"'
        : '';
      $pattern = '/(<div class="datagrid_row)("[^>]*data-id="' . $escapedMemberId . '")/';
      $replacement = '$1 businesses_member_row businesses_member_row_clickable$2'
        . ' data-member-id="' . htmlspecialchars($memberId, ENT_QUOTES, 'UTF-8') . '"'
        . $memberNameAttr
        . $memberRoleAttr
        . $ariaLabel;
      $html = (string) preg_replace($pattern, $replacement, $html, 1);
    }

    return $html;
  }

  /**
   * Stable toolbar slots rendered with the grid so hydration does not move controls.
   */
  private function membersToolbarSlotSkeleton(): string
  {
    return '<div class="datagrid_toolbar_filters business_members_toolbar_filters"></div>'
      . '<div class="datagrid_toolbar_bulk business_members_toolbar_bulk"></div>';
  }

  /**
   * Inject column gear menu.
   */
  private function injectColumnGearMenu(string $html): string
  {
    $menuLabel = htmlspecialchars(Strings::i18n('BUSINESSES_MEMBERS_COLUMNS_GEAR'), ENT_QUOTES, 'UTF-8');
    $panelId = 'business_members_column_menu_panel';
    $panelIdAttr = htmlspecialchars($panelId, ENT_QUOTES, 'UTF-8');
    $replacement = '<div class="datagrid_column_menu">'
      . '<button type="button" class="btn btn_secondary datagrid_column_menu_toggle" aria-haspopup="dialog" aria-expanded="false" aria-controls="' . $panelIdAttr . '">'
      . $menuLabel
      . '</button>'
      . '<div id="' . $panelIdAttr . '" class="datagrid_column_menu_panel" role="dialog" aria-label="' . $menuLabel . '" hidden>'
      . '$1'
      . '<span class="datagrid_column_strip_status visually_hidden" role="status" aria-live="polite" aria-atomic="true"></span>'
      . '</div>'
      . '</div>';

    $pattern = '/<div\s+class="datagrid_column_strip"[^>]*>(.*?)<span class="datagrid_column_strip_status[^>]*><\/span>\s*<\/div>/s';
    $htmlWithoutColumnStrip = (string) preg_replace($pattern, '', $html, 1);
    $toolbarSlot = '<div class="datagrid_toolbar_filters business_members_toolbar_filters"></div>';
    $toolbarSlotWithMenu = '<div class="datagrid_toolbar_filters business_members_toolbar_filters">' . $replacement . '</div>';

    if (str_contains($htmlWithoutColumnStrip, $toolbarSlot)) {
      return str_replace($toolbarSlot, $toolbarSlotWithMenu, $htmlWithoutColumnStrip);
    }

    return (string) preg_replace($pattern, $replacement, $html, 1);
  }

  /**
   * Inject member selection header column.
   */
  private function injectMemberSelectionHeaderColumn(string $html): string
  {
    $selectLabel = htmlspecialchars(Strings::i18n('BUSINESSES_MEMBERS_SELECT_MEMBER'), ENT_QUOTES, 'UTF-8');
    $selectAllLabel = htmlspecialchars(Strings::i18n('BUSINESSES_MEMBERS_SELECT_ALL'), ENT_QUOTES, 'UTF-8');
    $headerCell = '<div class="datagrid_heading datagrid_col_select business_members_header_select" role="columnheader" aria-label="' . $selectLabel . '">'
      . '<input type="checkbox" class="business_members_select_all_checkbox" id="business_members_select_all_checkbox" aria-label="' . $selectAllLabel . '">'
      . '</div>';
    $pattern = '/(<div class="datagrid_header_content" role="row">)/';

    return (string) preg_replace($pattern, '$1' . $headerCell, $html, 1);
  }

  /**
   * @param array<int, mixed> $rows
   */
  private function injectMemberSelectionCheckboxes(string $html, array $rows): string
  {
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $memberId = isset($row['id']) && is_scalar($row['id']) ? trim((string) $row['id']) : '';
      $memberName = isset($row['full_name']) && is_scalar($row['full_name']) ? trim((string) $row['full_name']) : '';
      if ($memberId === '') {
        continue;
      }

      $escapedMemberId = preg_quote(htmlspecialchars($memberId, ENT_QUOTES, 'UTF-8'), '/');
      $checkboxLabel = $memberName !== ''
        ? htmlspecialchars(sprintf(Strings::i18n('BUSINESSES_MEMBERS_SELECT_NAMED'), $memberName), ENT_QUOTES, 'UTF-8')
        : htmlspecialchars(Strings::i18n('BUSINESSES_MEMBERS_SELECT_MEMBER'), ENT_QUOTES, 'UTF-8');
      $checkbox = '<div class="datagrid_item datagrid_col_select business_members_row_select" role="gridcell">'
        . '<input type="checkbox" class="business_members_row_checkbox" data-member-id="'
        . htmlspecialchars($memberId, ENT_QUOTES, 'UTF-8')
        . '" aria-label="' . $checkboxLabel . '">'
        . '</div>';
      $pattern = '/(<div class="datagrid_row"[^>]*data-id="' . $escapedMemberId . '"[^>]*>\s*<div class="datagrid_row_content">)/';
      $html = (string) preg_replace($pattern, '$1' . $checkbox, $html, 1);
    }

    return $html;
  }

  /**
   * @param array<int, mixed> $rows
   */
  private function injectMemberDetailsCells(string $html, array $rows, string $businessId): string
  {
    $businessId = trim($businessId);
    $escapedBusinessId = htmlspecialchars($businessId, ENT_QUOTES, 'UTF-8');
    $businessIdAttr = $businessId !== '' ? ' data-business-id="' . $escapedBusinessId . '"' : '';

    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $memberId = isset($row['id']) && is_scalar($row['id']) ? trim((string) $row['id']) : '';
      $fullName = isset($row['full_name']) && is_scalar($row['full_name']) ? trim((string) $row['full_name']) : '';
      $email = isset($row['email']) && is_scalar($row['email']) ? trim((string) $row['email']) : '';
      $role = isset($row['role_slug']) && is_scalar($row['role_slug'])
        ? strtolower(trim((string) $row['role_slug']))
        : '';
      $roleDisplay = BusinessNav::roleDisplayLabel($role);

      if ($memberId === '' || $fullName === '') {
        continue;
      }

      $escapedMemberId = preg_quote(htmlspecialchars($memberId, ENT_QUOTES, 'UTF-8'), '/');
      $escapedFullName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
      $escapedEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
      $escapedRoleDisplay = htmlspecialchars($roleDisplay, ENT_QUOTES, 'UTF-8');
      $escapedRole = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');
      $dataAccessClass = isset($row['data_access_class']) && is_scalar($row['data_access_class']) ? (string) $row['data_access_class'] : 'is-unavailable';
      $dataAccessTitle = isset($row['data_access_title']) && is_scalar($row['data_access_title']) ? (string) $row['data_access_title'] : '';
      $escapedDataAccessClass = htmlspecialchars($dataAccessClass, ENT_QUOTES, 'UTF-8');
      $dataAccessIcon = '';
      if ($dataAccessTitle !== '' && $dataAccessClass === 'is-active') {
        $verifiedLabel = trim(Strings::i18n('BUSINESSES_MEMBERS_SECURITY_VERIFIED'));
        $iconLabel = $verifiedLabel !== '' ? $verifiedLabel . ': ' . $dataAccessTitle : $dataAccessTitle;
        $dataAccessIcon = self::shieldCheckIconMarkup($iconLabel, 'business_member_data_access_icon ' . $escapedDataAccessClass);
      } elseif ($dataAccessTitle !== '' && $dataAccessClass === 'is-setup') {
        $dataAccessIcon = self::shieldWrenchIconMarkup($dataAccessTitle, 'business_member_data_access_icon ' . $escapedDataAccessClass);
      }

      $roleMarkup = '';
      if ($role !== '' && $roleDisplay !== '' && $roleDisplay !== '—') {
        $roleMarkup = '<span class="business_member_details_role businesses_member_role_cell_static" data-current-role="' . $escapedRole . '">' . $escapedRoleDisplay . '</span>';
      } else {
        $roleMarkup = '<span class="business_member_details_role">—</span>';
      }

      $detailsCellClass = $dataAccessIcon !== ''
        ? 'business_member_details_cell business_member_details_cell_with_icon'
        : 'business_member_details_cell';
      $detailsCell = '<div class="' . $detailsCellClass . '">'
        . $dataAccessIcon
        . '<div class="business_member_details_stack">'
        . '<span class="business_member_details_name">' . $escapedFullName . '</span>'
        . ($email !== ''
          ? '<span class="business_member_details_email" title="' . $escapedEmail . '">' . $escapedEmail . '</span>'
          : '')
        . '<span class="business_member_details_meta">'
        . $roleMarkup
        . '</span>'
        . '</div>'
        . '</div>';

      $pattern = '/(<div class="datagrid_row"[^>]*data-id="' . $escapedMemberId . '"[^>]*>.*?<div class="datagrid_item datagrid_col_full_name[^"]*" role="gridcell"[^>]*data-col-key="full_name"[^>]*>)[^<]*(<\/div>)/s';
      $html = (string) preg_replace_callback(
        $pattern,
        static fn (array $matches): string => preg_replace(
          '/class="datagrid_item datagrid_col_full_name/',
          'class="datagrid_item datagrid_col_full_name business_member_details_item',
          $matches[1],
          1,
        ) . $detailsCell . $matches[2],
        $html,
        1,
      );
    }

    return $html;
  }

  /**
   * @param array<int, mixed> $rows
   */
  private function injectJoinedDateCells(string $html, array $rows): string
  {
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $memberId = isset($row['id']) && is_scalar($row['id']) ? trim((string) $row['id']) : '';
      if ($memberId === '') {
        continue;
      }

      $joinedAtRaw = isset($row['joined_at_sort']) && is_scalar($row['joined_at_sort'])
        ? trim((string) $row['joined_at_sort'])
        : '';
      $joinedAtDisplay = isset($row['joined_at']) && is_scalar($row['joined_at'])
        ? trim((string) $row['joined_at'])
        : '';
      $escapedMemberId = preg_quote(htmlspecialchars($memberId, ENT_QUOTES, 'UTF-8'), '/');
      $rawAttr = $joinedAtRaw !== ''
        ? ' data-joined-at-raw="' . htmlspecialchars($joinedAtRaw, ENT_QUOTES, 'UTF-8') . '"'
        : '';
      $displayAttr = $joinedAtDisplay !== ''
        ? ' data-joined-display="' . htmlspecialchars($joinedAtDisplay, ENT_QUOTES, 'UTF-8') . '"'
        : '';

      $pattern = '/(<div class="datagrid_row"[^>]*data-id="' . $escapedMemberId . '"[^>]*>.*?<div class="datagrid_item datagrid_col_joined_at[^"]*" role="gridcell"[^>]*data-col-key="joined_at"[^>]*>)[^<]*(<\/div>)/s';
      $html = (string) preg_replace_callback(
        $pattern,
        static function (array $matches) use ($rawAttr, $displayAttr, $joinedAtDisplay): string {
          $joinedOpen = preg_replace('/>$/', '', $matches[1], 1) ?? $matches[1];
          $openTag = preg_replace(
            '/class="datagrid_item datagrid_col_joined_at/',
            'class="datagrid_item datagrid_col_joined_at business_member_joined_item',
            $joinedOpen,
            1,
          ) ?? $joinedOpen;
          $cellContent = htmlspecialchars($joinedAtDisplay, ENT_QUOTES, 'UTF-8');

          return $openTag . $rawAttr . $displayAttr . '>' . $cellContent . $matches[2];
        },
        $html,
        1,
      );
    }

    return $html;
  }

  /**
   * @param array<int, mixed> $rows
   */
  private function injectHoursCells(string $html, array $rows): string
  {
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $memberId = isset($row['id']) && is_scalar($row['id']) ? trim((string) $row['id']) : '';
      if ($memberId === '') {
        continue;
      }

      $regHours = is_numeric($row['hours_reg'] ?? null) ? (float) $row['hours_reg'] : 0.0;
      $otHours = is_numeric($row['hours_ot'] ?? null) ? (float) $row['hours_ot'] : 0.0;
      $primary = htmlspecialchars(is_scalar($row['hours'] ?? null) ? (string) $row['hours'] : '', ENT_QUOTES, 'UTF-8');
      $subline = htmlspecialchars($this->formatHoursSubline($regHours, $otHours), ENT_QUOTES, 'UTF-8');

      $hoursCell = '<div class="business_member_hours_cell">'
        . '<span class="business_member_hours_primary">' . $primary . '</span>'
        . '<span class="business_member_hours_subline">' . $subline . '</span>'
        . '</div>';

      $escapedMemberId = preg_quote(htmlspecialchars($memberId, ENT_QUOTES, 'UTF-8'), '/');
      $pattern = '/(<div class="datagrid_row"[^>]*data-id="' . $escapedMemberId . '"[^>]*>.*?<div class="datagrid_item datagrid_col_hours)([^"]*)(" role="gridcell"[^>]*>)([^<]*)(<\/div>)/s';
      $html = (string) preg_replace_callback(
        $pattern,
        static fn (array $matches): string => $matches[1] . ' business_member_hours_item' . $matches[3] . $hoursCell . $matches[5],
        $html,
        1,
      );
    }

    return $html;
  }

  /**
   * @param array<int, mixed> $rows
   */
  private function injectEarningsCells(string $html, array $rows): string
  {
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $memberId = isset($row['id']) && is_scalar($row['id']) ? trim((string) $row['id']) : '';
      if ($memberId === '') {
        continue;
      }

      $primary = htmlspecialchars(is_scalar($row['earnings'] ?? null) ? (string) $row['earnings'] : '', ENT_QUOTES, 'UTF-8');
      $trend = trim(is_scalar($row['earnings_trend'] ?? null) ? (string) $row['earnings_trend'] : '');
      $trendMarkup = $trend !== ''
        ? '<span class="business_member_earnings_trend">' . htmlspecialchars($trend, ENT_QUOTES, 'UTF-8') . '</span>'
        : '';

      $earningsCell = '<div class="business_member_earnings_cell">'
        . '<span class="business_member_earnings_primary">' . $primary . '</span>'
        . $trendMarkup
        . '</div>';

      $escapedMemberId = preg_quote(htmlspecialchars($memberId, ENT_QUOTES, 'UTF-8'), '/');
      $pattern = '/(<div class="datagrid_row"[^>]*data-id="' . $escapedMemberId . '"[^>]*>.*?<div class="datagrid_item datagrid_col_earnings)([^"]*)(" role="gridcell"[^>]*>)([^<]*)(<\/div>)/s';
      $html = (string) preg_replace_callback(
        $pattern,
        static fn (array $matches): string => $matches[1] . ' business_member_earnings_item' . $matches[3] . $earningsCell . $matches[5],
        $html,
        1,
      );
    }

    return $html;
  }

  /**
   * @param array<int, mixed> $rows
   */
  private function injectMemberRowActionMenus(string $html, array $rows): string
  {
    $editRoleLabel = htmlspecialchars(Strings::i18n('BUSINESSES_MEMBERS_ACTION_EDIT_ROLE'), ENT_QUOTES, 'UTF-8');
    $addToGroupLabel = htmlspecialchars(Strings::i18n('BUSINESS_GROUPS_ADD_TO_GROUP'), ENT_QUOTES, 'UTF-8');
    $suspendLabel = htmlspecialchars(Strings::i18n('BUSINESSES_MEMBERS_ACTION_SUSPEND'), ENT_QUOTES, 'UTF-8');
    $revokeLabel = htmlspecialchars(Strings::i18n('BUSINESSES_REVOKE'), ENT_QUOTES, 'UTF-8');
    $roleOptions = ['coordinator', 'contributor', 'viewer', 'member'];

    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $memberId = isset($row['id']) && is_scalar($row['id']) ? trim((string) $row['id']) : '';
      $memberName = isset($row['full_name']) && is_scalar($row['full_name']) ? trim((string) $row['full_name']) : '';
      $memberRole = isset($row['role_slug']) && is_scalar($row['role_slug'])
        ? strtolower(trim((string) $row['role_slug']))
        : '';
      if ($memberId === '') {
        continue;
      }

      $escapedMemberId = preg_quote(htmlspecialchars($memberId, ENT_QUOTES, 'UTF-8'), '/');
      $escapedMemberIdAttr = htmlspecialchars($memberId, ENT_QUOTES, 'UTF-8');
      $escapedMemberRoleAttr = htmlspecialchars($memberRole, ENT_QUOTES, 'UTF-8');
      $roleSubmenu = '<div class="business_member_row_submenu business_member_role_submenu" role="menu" hidden>';
      foreach ($roleOptions as $roleOption) {
        $roleOptionLabel = htmlspecialchars(BusinessNav::roleDisplayLabel($roleOption), ENT_QUOTES, 'UTF-8');
        $roleOptionAttr = htmlspecialchars($roleOption, ENT_QUOTES, 'UTF-8');
        $currentAttr = $roleOption === $memberRole ? ' aria-current="true" disabled' : '';
        $roleSubmenu .= '<button type="button" class="business_member_row_submenu_item business_member_role_menu_item" role="menuitem" data-role="' . $roleOptionAttr . '" data-member-id="' . $escapedMemberIdAttr . '"' . $currentAttr . '>'
          . '<span>' . $roleOptionLabel . '</span>'
          . '</button>';
      }
      if ($memberRole === 'owner') {
        $roleSubmenu .= '<p class="business_member_row_submenu_note">' . htmlspecialchars(Strings::i18n('BUSINESSES_MEMBERS_OWNER_ROLE_LOCKED'), ENT_QUOTES, 'UTF-8') . '</p>';
      }
      $roleSubmenu .= '</div>';
      $menuAria = htmlspecialchars(
        $memberName !== ''
          ? sprintf(Strings::i18n('BUSINESSES_MEMBERS_ROW_MENU_ARIA'), $memberName)
          : Strings::i18n('BUSINESSES_MEMBERS_ROW_MENU_ARIA_GENERIC'),
        ENT_QUOTES,
        'UTF-8',
      );

      $menu = '<div class="datagrid_actions business_member_row_menu">'
        . '<button type="button" class="business_member_row_menu_toggle" aria-haspopup="menu" aria-expanded="false" aria-label="' . $menuAria . '">'
        . '<span class="business_member_row_menu_icon" aria-hidden="true">&#8942;</span>'
        . '</button>'
        . '<div class="business_member_row_menu_panel" role="menu" hidden>'
        . '<button type="button" class="business_member_row_menu_item business_member_row_menu_item_has_submenu" role="menuitem" aria-haspopup="menu" aria-expanded="false" data-member-action="edit-role" data-member-id="' . $escapedMemberIdAttr . '" data-current-role="' . $escapedMemberRoleAttr . '">' . $editRoleLabel . '</button>'
        . $roleSubmenu
        . '<button type="button" class="business_member_row_menu_item business_member_row_menu_item_has_submenu" role="menuitem" aria-haspopup="menu" aria-expanded="false" data-member-action="add-to-group" data-member-id="' . $escapedMemberIdAttr . '">' . $addToGroupLabel . '</button>'
        . '<div class="business_member_row_submenu business_member_group_submenu" role="menu" hidden></div>'
        . '<button type="button" class="business_member_row_menu_item" role="menuitem" data-member-action="suspend" data-member-id="' . $escapedMemberIdAttr . '" disabled>' . $suspendLabel . '</button>'
        . '<button type="button" class="business_member_row_menu_item business_member_row_menu_item_danger datagrid_action" role="menuitem" data-member-action="revoke" data-action="revoke" data-id="' . $escapedMemberIdAttr . '" data-member-id="' . $escapedMemberIdAttr . '">' . $revokeLabel . '</button>'
        . '</div>'
        . '</div>';

      $pattern = '/(<div class="datagrid_row"[^>]*data-id="' . $escapedMemberId . '"[^>]*>.*?<div class="datagrid_item datagrid_item_actions" role="gridcell"[^>]*>)\s*<div class="datagrid_actions">.*?<\/div>(\s*<\/div>)/s';
      $html = (string) preg_replace_callback(
        $pattern,
        static function (array $matches) use ($menu): string {
          return $matches[1] . $menu . $matches[2];
        },
        $html,
        1,
      );
    }

    return $html;
  }

  /**
   * Render the compact shield+wrench security setup icon.
   */
  public static function shieldWrenchIconMarkup(string $label, string $class = 'business_member_data_access_icon', bool $decorative = false): string
  {
    $label = trim($label);
    if ($label === '') {
      return '';
    }

    $titleId = 'shield_wrench_' . substr(sha1($label . ':' . $class), 0, 12);
    $escapedTitleId = htmlspecialchars($titleId, ENT_QUOTES, 'UTF-8');
    $escapedLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    $escapedClass = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');

    $spanAttrs = $decorative
      ? ' aria-hidden="true"'
      : ' title="' . $escapedLabel . '" aria-label="' . $escapedLabel . '"';
    $svgAttrs = $decorative
      ? 'aria-hidden="true"'
      : 'role="img" aria-labelledby="' . $escapedTitleId . '"';
    $titleMarkup = $decorative ? '' : '<title id="' . $escapedTitleId . '">' . $escapedLabel . '</title>';

    return '<span class="' . $escapedClass . '"' . $spanAttrs . '>'
      . '<svg viewBox="0 0 24 24" ' . $svgAttrs . ' focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
      . $titleMarkup
      . '<path class="business_member_data_access_icon_shield" d="M12 3 5 6v5.5c0 4.1 2.7 7.8 7 9.5 4.3-1.7 7-5.4 7-9.5V6l-7-3Z"/>'
      . '<path class="business_member_data_access_icon_wrench" d="M14.8 8.2a2.7 2.7 0 0 0 3 3l-4.9 4.9-2.1-2.1 4-4Z"/>'
      . '<path class="business_member_data_access_icon_wrench" d="m9.7 15.1-1.9 1.9a1.1 1.1 0 0 0 1.6 1.6l1.9-1.9"/>'
      . '</svg>'
      . '</span>';
  }

  /**
   * Render the compact verified shield security icon.
   */
  public static function shieldCheckIconMarkup(string $label, string $class = 'business_member_data_access_icon', bool $decorative = false): string
  {
    $label = trim($label);
    if ($label === '') {
      return '';
    }

    $titleId = 'shield_check_' . substr(sha1($label . ':' . $class), 0, 12);
    $escapedTitleId = htmlspecialchars($titleId, ENT_QUOTES, 'UTF-8');
    $escapedLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    $escapedClass = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');

    $spanAttrs = $decorative
      ? ' aria-hidden="true"'
      : ' title="' . $escapedLabel . '" aria-label="' . $escapedLabel . '"';
    $svgAttrs = $decorative
      ? 'aria-hidden="true"'
      : 'role="img" aria-labelledby="' . $escapedTitleId . '"';
    $titleMarkup = $decorative ? '' : '<title id="' . $escapedTitleId . '">' . $escapedLabel . '</title>';

    return '<span class="' . $escapedClass . '"' . $spanAttrs . '>'
      . '<svg viewBox="0 0 24 24" ' . $svgAttrs . ' focusable="false" fill="none" stroke-linecap="round" stroke-linejoin="round">'
      . $titleMarkup
      . '<path class="business_member_data_access_icon_verified_shield" d="M12 2.8 4.8 5.9v5.4c0 4.4 2.8 8.2 7.2 9.9 4.4-1.7 7.2-5.5 7.2-9.9V5.9L12 2.8Z"/>'
      . '<path class="business_member_data_access_icon_verified_check" d="m8.4 12.2 2.4 2.4 4.9-5.1"/>'
      . '</svg>'
      . '</span>';
  }
}
