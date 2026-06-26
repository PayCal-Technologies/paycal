<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * Builds the business member groups datagrid HTML shared by the page and API.
 */
final class BusinessGroupsGridRenderer
{
  private const GRID_PAGE_SIZE = 10;
  private const GROUP_METRICS_CACHE_TTL_SECONDS = 300;

  /** @var array<string, array<string, array<string, array<string, string>>>|null> */
  private array $memberWorkCacheByBusinessId = [];

  /**
   * @param array<string, mixed> $options
   * @return array{success: bool, message: string, html: string, group_count: int, service_result?: array{success: bool, message: string, data: array<string, mixed>}}
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
        'group_count' => 0,
      ];
    }

    $result = (new BusinessGroupService())->listGroups($actorUUID, $businessId, false);
    if (!$result['success']) {
      $message = trim($result['message']) !== '' ? $result['message'] : Strings::i18n('BUSINESS_GROUPS_ACCESS_DENIED');

      return [
        'success' => false,
        'message' => $message,
        'html' => $this->emptyMessage($message),
        'group_count' => 0,
        'service_result' => $result,
      ];
    }

    $groups = is_array($result['data']['groups'] ?? null) ? $result['data']['groups'] : [];
    $html = $this->renderGroups($businessId, $groups, $options);

    return [
      'success' => true,
      'message' => Strings::i18n('BUSINESS_GROUPS_LOADED'),
      'html' => $html,
      'group_count' => count($groups),
    ];
  }

  /**
   * @param array<int, mixed> $groups
   * @param array<string, mixed> $options
   */
  public function renderGroups(string $businessId, array $groups, array $options = []): string
  {
    $search = $this->optionString($options, 'search');
    $sort = $this->optionString($options, 'sort', 'name');
    $direction = strtolower($this->optionString($options, 'direction', 'asc')) === 'desc' ? 'desc' : 'asc';
    $page = max(1, $this->optionInt($options, 'page', 1));
    $status = strtolower($this->optionString($options, 'status', 'active'));
    if (!in_array($status, ['active', 'archived'], true)) {
      $status = 'active';
    }

    $rows = [];
    foreach ($groups as $group) {
      if (!is_array($group)) {
        continue;
      }

      $groupStatus = strtolower(trim($this->scalarString($group['status'] ?? 'active', 'active')));
      if ($groupStatus !== $status) {
        continue;
      }

      $groupId = trim($this->scalarString($group['group_id'] ?? ''));
      $name = trim($this->scalarString($group['name'] ?? ''));
      if ($groupId === '' || $name === '') {
        continue;
      }

      $description = trim($this->scalarString($group['description'] ?? ''));
      $type = $this->normalizeGroupType($this->scalarString($group['type'] ?? 'manual', 'manual'));
      $memberCount = is_numeric($group['member_count'] ?? null) ? (int) $group['member_count'] : 0;
      $updatedAt = trim($this->scalarString($group['updated_at'] ?? ''));

      $rows[] = [
        'id' => $groupId,
        'name' => $this->formatNameCellHtml($name, $description, $type),
        'member_count' => (string) $memberCount,
        'site_count' => '—',
        'hours' => '—',
        'work_gross' => '—',
        'updated_at' => $this->formatDate($updatedAt),
        '_name' => $name,
        '_description' => $description,
        '_type' => $type,
        '_member_count' => $memberCount,
        '_site_count' => 0,
        '_hours' => 0.0,
        '_work_gross' => 0.0,
        '_updated_at' => $updatedAt,
        '_metrics_loaded' => false,
      ];
    }

    if ($search !== '') {
      $needle = mb_strtolower($search);
      $rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
        return mb_stripos((string) $row['_name'], $needle) !== false
          || mb_stripos((string) $row['_description'], $needle) !== false;
      }));
    }

    if (!in_array($sort, ['name', 'member_count', 'site_count', 'hours', 'work_gross', 'updated_at'], true)) {
      $sort = 'name';
    }

    $metricSort = in_array($sort, ['site_count', 'hours', 'work_gross'], true);
    if ($metricSort) {
      $rows = array_map(fn (array $row): array => $this->hydrateGroupMetricRow($businessId, $row), $rows);
    }

    usort($rows, static function (array $a, array $b) use ($sort, $direction): int {
      if (in_array($sort, ['member_count', 'site_count', 'hours', 'work_gross'], true)) {
        $key = match ($sort) {
          'member_count' => '_member_count',
          'site_count' => '_site_count',
          'hours' => '_hours',
          'work_gross' => '_work_gross',
        };
        $cmp = self::staticFloatValue($a[$key] ?? 0) <=> self::staticFloatValue($b[$key] ?? 0);
      } elseif ($sort === 'updated_at') {
        $cmp = strcmp(self::staticScalarString($a['_updated_at'] ?? ''), self::staticScalarString($b['_updated_at'] ?? ''));
      } else {
        $cmp = strcasecmp(self::staticScalarString($a['_name'] ?? ''), self::staticScalarString($b['_name'] ?? ''));
      }

      return $direction === 'desc' ? -$cmp : $cmp;
    });

    if (!$metricSort && $rows !== []) {
      $totalRows = count($rows);
      $totalPages = (int) ceil($totalRows / self::GRID_PAGE_SIZE);
      $page = max(1, min($page, $totalPages ?: 1));
      $startIndex = ($page - 1) * self::GRID_PAGE_SIZE;
      $endIndex = min($startIndex + self::GRID_PAGE_SIZE, $totalRows);
      for ($index = $startIndex; $index < $endIndex; $index++) {
        $rows[$index] = $this->hydrateGroupMetricRow($businessId, $rows[$index]);
      }
    }

    $gridId = 'business-groups-' . $status;
    $grid = DataGrid::create($gridId, Strings::i18n('BUSINESS_GROUPS_TITLE'));
    $grid->setClass('datagrid_mobile_cards business_groups_mobile_cards');
    $grid->enableSearch(Strings::i18n('BUSINESS_GROUPS_FILTER_PLACEHOLDER'));
    $grid->setControlsAriaLabel(Strings::i18n('BUSINESS_GROUPS_FILTER_ARIA'));
    $grid->setSearchValue($search);
    $grid->enableSorting();
    $grid->enableColumnVisibility();
    $grid->addColumn('name', Strings::i18n('BUSINESS_GROUPS_COL_GROUP'), true, 'minmax(14rem, 3fr)', null, true, false, true);
    $grid->addColumn('member_count', Strings::i18n('BUSINESS_GROUPS_COL_MEMBERS'), true, 'minmax(5rem, 0.7fr)', 'right', false);
    $grid->addColumn('site_count', Strings::i18n('BUSINESS_GROUPS_COL_SITES'), true, 'minmax(5rem, 0.7fr)', 'right', false);
    $grid->addColumn('hours', Strings::i18n('BUSINESS_GROUPS_COL_HOURS'), true, 'minmax(5rem, 0.8fr)', 'right');
    $grid->addColumn('work_gross', Strings::i18n('BUSINESS_GROUPS_COL_WORK_GROSS'), true, 'minmax(6rem, 1fr)', 'right');
    $grid->addColumn('updated_at', Strings::i18n('BUSINESS_GROUPS_COL_UPDATED'), true, 'minmax(10rem, 1fr)');
    if ($status === 'archived') {
      $grid->addRowAction(
        'restore-group',
        Strings::i18n('BUSINESS_GROUPS_RESTORE_ACTION'),
        Strings::i18n('BUSINESS_GROUPS_RESTORE_ARIA'),
        Strings::i18n('BUSINESS_GROUPS_RESTORE_TOOLTIP'),
      );
    } else {
      $grid->addRowAction(
        'archive-group',
        '📦',
        Strings::i18n('BUSINESS_GROUPS_ARCHIVE_ARIA'),
        Strings::i18n('BUSINESS_GROUPS_ARCHIVE_TOOLTIP'),
      );
    }
    $grid->setRowActionsHeaderLabel('');
    $grid->setItemLabel(Strings::i18n('BUSINESS_GROUPS_ITEM_LABEL'));

    $pager = ArrayPager::fromArray($rows, ['pageSize' => self::GRID_PAGE_SIZE]);
    $pager->setPage($page);
    $html = $grid->table($pager);

    $start = $pager->getTotal() === 0 ? 0 : (($pager->getPage() - 1) * $pager->getPageSize()) + 1;
    $end = min($pager->getPage() * $pager->getPageSize(), $pager->getTotal());
    $total = $pager->getTotal();

    $pattern = '/(<div\\s+id="' . preg_quote($gridId, '/') . '"[^>]*data-grid="' . preg_quote($gridId, '/') . '"[^>]*)>/';
    $replacement = '$1 data-search="' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8')
      . '" data-sort="' . htmlspecialchars($sort, ENT_QUOTES, 'UTF-8')
      . '" data-direction="' . htmlspecialchars($direction, ENT_QUOTES, 'UTF-8')
      . '" data-pagination-start="' . $start
      . '" data-pagination-end="' . $end
      . '" data-pagination-total="' . $total
      . '" data-total-pages="' . $pager->getTotalPages()
      . '">';

    return (string) preg_replace($pattern, $replacement, $html, 1);
  }

  /**
   * Render placeholder rows while group data is loading.
   */
  public function loadingSkeleton(): string
  {
    return DataGrid::loadingSkeleton(6, 4);
  }

  /**
   * Render the escaped empty-state message for the groups grid.
   */
  public function emptyMessage(string $message): string
  {
    return '<div class="datagrid_empty">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
  }

  /**
   * Build the escaped group name cell with type badge and optional description.
   */
  private function formatNameCellHtml(string $name, string $description, string $type): string
  {
    $type = $this->normalizeGroupType($type);
    $typeClass = $type === 'smart' ? 'business_groups_type_tag--smart' : 'business_groups_type_tag--manual';
    $typeSymbolClass = $type === 'smart' ? 'business_groups_type_symbol--smart' : 'business_groups_type_symbol--manual';
    $typeLabel = $type === 'smart' ? Strings::i18n('BUSINESS_GROUPS_TYPE_SMART') : Strings::i18n('BUSINESS_GROUPS_TYPE_MANUAL');
    $html = '<span class="business_groups_name_cell">'
      . '<span class="business_groups_name_text">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</span>'
      . '<span class="business_groups_type_tag ' . $typeClass . '">'
      . '<span class="business_groups_type_symbol ' . $typeSymbolClass . '" aria-hidden="true"></span>'
      . htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8')
      . '</span>';
    if ($description !== '') {
      $html .= '<span class="business_groups_description_text">' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    return $html . '</span>';
  }

  /**
   * @param array<string, mixed> $row
   * @return array<string, mixed>
   */
  private function hydrateGroupMetricRow(string $businessId, array $row): array
  {
    if (($row['_metrics_loaded'] ?? false) === true) {
      return $row;
    }

    $groupId = trim($this->scalarString($row['id'] ?? ''));
    $metrics = $groupId === '' ? null : $this->cachedGroupWorkMetrics($businessId, $groupId);
    if ($metrics === null) {
      $row['site_count'] = '—';
      $row['hours'] = '—';
      $row['work_gross'] = '—';
      $row['_metrics_loaded'] = true;

      return $row;
    }

    $row['site_count'] = (string) $metrics['site_count'];
    $row['hours'] = $this->formatHours($metrics['hours']);
    $row['work_gross'] = '$' . number_format($metrics['gross'], 0);
    $row['_site_count'] = $metrics['site_count'];
    $row['_hours'] = $metrics['hours'];
    $row['_work_gross'] = $metrics['gross'];
    $row['_metrics_loaded'] = true;

    return $row;
  }

  /**
   * @return array{site_count: int, hours: float, gross: float}|null
   */
  private function cachedGroupWorkMetrics(string $businessId, string $groupId): ?array
  {
    $cacheKey = $this->groupMetricsCacheKey($businessId, $groupId);
    $raw = Database::get($cacheKey);
    if ($raw !== '') {
      $decoded = json_decode($raw, true);
      $schema = is_array($decoded) ? ($decoded['schema'] ?? null) : null;
      if (is_array($decoded) && ($schema === 1 || $schema === '1')) {
        return [
          'site_count' => is_numeric($decoded['site_count'] ?? null) ? (int) $decoded['site_count'] : 0,
          'hours' => is_numeric($decoded['hours'] ?? null) ? (float) $decoded['hours'] : 0.0,
          'gross' => is_numeric($decoded['gross'] ?? null) ? (float) $decoded['gross'] : 0.0,
        ];
      }
    }

    $metrics = $this->groupWorkMetrics($businessId, $groupId);
    if ($metrics === null) {
      return null;
    }

    $payload = json_encode([
      'schema' => 1,
      'generated_at' => date('c'),
      'site_count' => $metrics['site_count'],
      'hours' => $metrics['hours'],
      'gross' => $metrics['gross'],
    ]);
    if (is_string($payload)) {
      Database::set($cacheKey, $payload, self::GROUP_METRICS_CACHE_TTL_SECONDS);
    }

    return $metrics;
  }

  /**
   * Return the Redis cache key for a group's computed work metrics.
   */
  private function groupMetricsCacheKey(string $businessId, string $groupId): string
  {
    return Keys::businessGroupMetricsCache($businessId, $groupId);
  }

  /**
   * Normalize unknown group type values to the manual fallback.
   */
  private function normalizeGroupType(string $type): string
  {
    $normalized = strtolower(trim($type));
    return in_array($normalized, ['manual', 'smart'], true) ? $normalized : 'manual';
  }

  /**
   * Format stored date strings for compact grid display.
   */
  private function formatDate(string $date): string
  {
    if ($date === '') {
      return '—';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
      return $date;
    }

    return date('M j, Y', $timestamp);
  }

  /**
   * @return array{site_count: int, hours: float, gross: float}|null
   */
  private function groupWorkMetrics(string $businessId, string $groupId): ?array
  {
    $memberWorkCache = $this->memberWorkCacheForBusiness($businessId);
    if ($memberWorkCache === null) {
      return null;
    }

    $memberUuids = array_values(array_filter(array_map(
      static fn (string $value): string => trim($value),
      Database::smembers(Keys::businessGroupMembers($businessId, $groupId)),
    )));
    if ($memberUuids === []) {
      return ['site_count' => 0, 'hours' => 0.0, 'gross' => 0.0];
    }

    $siteIds = [];
    $hours = 0.0;
    $gross = 0.0;
    foreach ($memberUuids as $memberUuid) {
      $entries = $memberWorkCache[$memberUuid] ?? [];
      foreach ($entries as $workKey => $entry) {
        $parts = explode(':', (string) $workKey);
        $isArchived = ($parts[1] ?? '') === 'archived';
        $siteId = $isArchived ? (string) ($parts[4] ?? '') : (string) ($parts[3] ?? '');
        if ($siteId !== '') {
          $siteIds[$siteId] = true;
        }

        $regular = $this->floatValue($entry['regular_hours'] ?? $entry['r'] ?? 0);
        $overtime = $this->floatValue($entry['overtime_hours'] ?? $entry['o'] ?? 0);
        $travel = $this->floatValue($entry['travel_hours'] ?? $entry['t'] ?? $entry['travel'] ?? 0);
        $entryHours = $this->floatValue($entry['hours'] ?? $entry['h'] ?? ($regular + $overtime + $travel));
        $hours += $entryHours;
        $gross += $this->floatValue($entry['gross'] ?? $entry['g'] ?? 0);
      }
    }

    return ['site_count' => count($siteIds), 'hours' => round($hours, 2), 'gross' => round($gross, 2)];
  }

  /**
   * @return array<string, array<string, array<string, string>>>|null
   */
  private function memberWorkCacheForBusiness(string $businessId): ?array
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return null;
    }

    if (!array_key_exists($businessId, $this->memberWorkCacheByBusinessId)) {
      $this->memberWorkCacheByBusinessId[$businessId] = BusinessWorkspaceCache::getMemberWork($businessId);
    }

    return $this->memberWorkCacheByBusinessId[$businessId];
  }

  /**
   * Format an hour total for the group metric cell.
   */
  private function formatHours(float $hours): string
  {
    if ($hours <= 0.0) {
      return '0h';
    }

    return rtrim(rtrim(number_format($hours, 2), '0'), '.') . 'h';
  }

  /**
   * Coerce numeric metric payload values into floats.
   */
  private function floatValue(mixed $value): float
  {
    return is_numeric($value) ? (float) $value : 0.0;
  }

  /** @param array<string, mixed> $options */
  private function optionString(array $options, string $key, string $default = ''): string
  {
    return isset($options[$key]) && is_scalar($options[$key]) ? trim((string) $options[$key]) : $default;
  }

  /** @param array<string, mixed> $options */
  private function optionInt(array $options, string $key, int $default = 0): int
  {
    return isset($options[$key]) && is_scalar($options[$key]) ? (int) $options[$key] : $default;
  }

  /**
   * Return scalar values as strings with a caller-provided fallback.
   */
  private function scalarString(mixed $value, string $default = ''): string
  {
    return is_scalar($value) ? (string) $value : $default;
  }

  /**
   * Static variant for coercing numeric sort payload values into floats.
   */
  private static function staticFloatValue(mixed $value): float
  {
    return is_numeric($value) ? (float) $value : 0.0;
  }

  /**
   * Static variant for coercing scalar sort payload values into strings.
   */
  private static function staticScalarString(mixed $value, string $default = ''): string
  {
    return is_scalar($value) ? (string) $value : $default;
  }
}
