<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\SiteStatus;

/**
 * Builds the business sites datagrid HTML shared by the API and SSR page shell.
 */
final class BusinessSitesGridRenderer
{
  private const GRID_PAGE_SIZE = 10;

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
   * Extract the ISO date segment from active or archived work entry keys.
   */
  private function workDateFromKey(string $workKey): string
  {
    $parts = explode(':', $workKey);
    $date = ($parts[1] ?? '') === 'archived'
      ? (string) ($parts[3] ?? '')
      : (string) ($parts[2] ?? '');

    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '';
  }

  /**
   * Format compact date labels for grid cells.
   */
  private function formatGridDate(string $date): string
  {
    if ($date === '') {
      return '—';
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? '—' : date('M j, Y', $timestamp);
  }

  /**
   * @param array<string, mixed> $options
   * @return array{
   *   success: bool,
   *   message: string,
   *   html: string,
   *   site_count: int,
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
        'site_count' => 0,
      ];
    }

    $service = new BusinessDiscoveryService();
    $result = $service->listBusinessSites($actorUUID, $businessId);

    if (!$result['success']) {
      $message = trim($result['message']) !== ''
        ? $result['message']
        : Strings::i18n('BUSINESS_SITES_LOAD_FAILED');

      return [
        'success' => false,
        'message' => $message,
        'html' => $this->emptyMessage($message),
        'site_count' => 0,
        'service_result' => $result,
      ];
    }

    $sites = is_array($result['data']['sites'] ?? null)
      ? $result['data']['sites']
      : [];
    $business = is_array($result['data']['business'] ?? null)
      ? $result['data']['business']
      : [];

    $html = $this->renderSites($sites, $business, $actorUUID, $businessId, $options);

    return [
      'success' => true,
      'message' => '[Business] Business sites grid rendered.',
      'html' => $html,
      'site_count' => count($sites),
    ];
  }

  /**
   * @param array<int, mixed> $sites
   * @param array<string, mixed> $business
   * @param array<string, mixed> $options
   */
  public function renderSites(array $sites, array $business, string $actorUUID, string $businessId, array $options = []): string
  {
    $search = $this->optionString($options, 'search');
    $sort = $this->optionString($options, 'sort', 'site_name');
    $direction = strtolower($this->optionString($options, 'direction', 'asc')) === 'desc' ? 'desc' : 'asc';
    $page = max(1, $this->optionInt($options, 'page', 1));
    $status = strtolower($this->optionString($options, 'status', SiteStatus::ACTIVE->value));
    if (!in_array($status, [SiteStatus::ACTIVE->value, SiteStatus::ARCHIVED->value, SiteStatus::INACTIVE->value], true)) {
      $status = SiteStatus::ACTIVE->value;
    }

    $businessOwnerUUID = isset($business['owner_uuid']) && is_scalar($business['owner_uuid'])
      ? trim((string) $business['owner_uuid'])
      : '';
    $discoveryService = new BusinessDiscoveryService();
    $canWrite = $discoveryService->canWriteBusinessSites($businessId, $actorUUID);

    $rows = [];
    foreach ($sites as $site) {
      if (!is_array($site)) {
        continue;
      }

      $siteOwnerUUID = isset($site['site_owner_uuid']) && is_scalar($site['site_owner_uuid'])
        ? trim((string) $site['site_owner_uuid'])
        : '';
      $siteId = isset($site['site_id']) && is_scalar($site['site_id'])
        ? trim((string) $site['site_id'])
        : '';
      if ($siteOwnerUUID === '' || $siteId === '') {
        continue;
      }

      $siteHash = is_array($site['site_data'] ?? null) ? $site['site_data'] : [];
      $siteStatus = strtolower(is_scalar($siteHash['status'] ?? null) ? (string) $siteHash['status'] : SiteStatus::ACTIVE->value);
      $settings = is_array($site['settings'] ?? null) ? $site['settings'] : [];
      $settingsStatus = strtolower(is_scalar($settings['site_status'] ?? null) ? (string) $settings['site_status'] : SiteStatus::ACTIVE->value);
      $effectiveStatus = $siteStatus === SiteStatus::ARCHIVED->value || $settingsStatus === SiteStatus::ARCHIVED->value
        ? SiteStatus::ARCHIVED->value
        : ($siteStatus === SiteStatus::INACTIVE->value ? SiteStatus::INACTIVE->value : SiteStatus::ACTIVE->value);

      if ($status === SiteStatus::ACTIVE->value && $effectiveStatus !== SiteStatus::ACTIVE->value) {
        continue;
      }
      if ($status === SiteStatus::ARCHIVED->value && $effectiveStatus !== SiteStatus::ARCHIVED->value) {
        continue;
      }

      $siteName = trim(is_scalar($siteHash['site_name'] ?? null) ? (string) $siteHash['site_name'] : (is_scalar($site['site_name'] ?? null) ? (string) $site['site_name'] : ''));
      if ($siteName === '') {
        $siteName = 'Unknown Site (' . $siteId . ')';
      }

      $siteRef = $siteOwnerUUID . ':' . $siteId;
      $budgetRaw = $settings['budget_amount'] ?? null;
      $budgetAmount = is_numeric($budgetRaw) ? (float) $budgetRaw : 0.0;
      $budgetDisplay = $budgetAmount > 0
        ? '$' . number_format($budgetAmount, 0)
        : '—';

      $ownershipMeta = $discoveryService->businessSiteOwnershipMeta(
        $actorUUID,
        $siteOwnerUUID,
        $businessOwnerUUID,
        $siteHash,
      );
      $ownershipScope = $ownershipMeta['ownership_scope'];
      $ownership = $ownershipMeta['ownership_label'];

      $siteColor = $this->normalizeSiteColor($siteHash, $site);
      $isBusinessManaged = BusinessDiscoveryService::isBusinessManagedSite($siteHash);
      $wage = is_numeric($siteHash['wage'] ?? null) ? (float) $siteHash['wage'] : 0.0;

      $rows[] = [
        'id' => $siteOwnerUUID . ':' . $siteId,
        'site_owner_uuid' => $siteOwnerUUID,
        'site_id' => $siteId,
        '_site_ref' => $siteRef,
        'site_name' => $this->formatSiteNameCellHtml($siteName, $ownershipScope, $isBusinessManaged),
        'site_color' => $siteColor,
        '_site_name' => $siteName,
        'ownership_label' => $ownership,
        'entries' => '0',
        'work_gross' => '$0',
        'wage' => $wage > 0 ? '$' . number_format($wage, 2) : '—',
        'last_worked' => '—',
        'budget_amount' => $budgetDisplay,
        'budget_used' => $this->formatBudgetUsedHtml(0.0, $budgetAmount),
        '_entries' => 0,
        '_work_gross' => 0.0,
        '_wage' => $wage,
        '_last_worked' => '',
        '_budget_amount' => $budgetAmount,
        '_budget_used_pct' => 0.0,
        '_metrics_loaded' => false,
      ];
    }

    if ($search !== '') {
      $needle = mb_strtolower($search);
      $rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
        foreach (['_site_name', 'ownership_label', 'budget_amount', 'work_gross', 'wage', 'last_worked'] as $field) {
          if (mb_stripos((string) $row[$field], $needle) !== false) {
            return true;
          }
        }

        return false;
      }));
    }

    $allowedSorts = [
      'site_name',
      'entries',
      'work_gross',
      'wage',
      'last_worked',
      'budget_amount',
      'budget_used',
    ];
    if (!in_array($sort, $allowedSorts, true)) {
      $sort = 'site_name';
    }

    $metricSort = in_array($sort, ['entries', 'work_gross', 'last_worked', 'budget_used'], true);
    if ($metricSort) {
      $rows = $this->hydrateSiteMetricRows($rows);
    }

    usort($rows, static function (array $a, array $b) use ($sort, $direction): int {
      if ('last_worked' === $sort) {
        $cmp = strcmp(self::scalarString($a['_last_worked'] ?? ''), self::scalarString($b['_last_worked'] ?? ''));
      } elseif (in_array($sort, ['entries', 'work_gross', 'wage', 'budget_amount', 'budget_used'], true)) {
        $key = match ($sort) {
          'entries' => '_entries',
          'work_gross' => '_work_gross',
          'wage' => '_wage',
          'budget_amount' => '_budget_amount',
          'budget_used' => '_budget_used_pct',
        };
        $cmp = self::numericFloat($a[$key] ?? 0) <=> self::numericFloat($b[$key] ?? 0);
      } else {
        $cmp = strcasecmp(self::scalarString($a['_site_name'] ?? ''), self::scalarString($b['_site_name'] ?? ''));
      }

      return $direction === 'desc' ? -$cmp : $cmp;
    });

    if (!$metricSort && $rows !== []) {
      $totalRows = count($rows);
      $totalPages = (int) ceil($totalRows / self::GRID_PAGE_SIZE);
      $page = max(1, min($page, $totalPages ?: 1));
      $startIndex = ($page - 1) * self::GRID_PAGE_SIZE;
      $endIndex = min($startIndex + self::GRID_PAGE_SIZE, $totalRows);
      $visibleRows = [];
      for ($index = $startIndex; $index < $endIndex; $index++) {
        $visibleRows[] = $rows[$index];
      }
      $visibleRows = $this->hydrateSiteMetricRows($visibleRows);
      foreach ($visibleRows as $offset => $hydratedRow) {
        $rows[$startIndex + $offset] = $hydratedRow;
      }
    }

    $grid = DataGrid::create('business-sites-' . $status, Strings::i18n('BUSINESS_SITES_TITLE'));
    $grid->setClass('datagrid_mobile_cards business_sites_mobile_cards');
    $grid->enableSearch(Strings::i18n('BUSINESS_SITES_FILTER_PLACEHOLDER'));
    $grid->setSearchValue($search);
    $grid->enableSorting();
    $grid->enableColumnVisibility();
    $grid->addColumn('site_name', Strings::i18n('SITE'), true, 'minmax(14rem, 3fr)', null, true, false, true);
    $grid->addColumn('entries', Strings::i18n('BUSINESS_SITES_GRID_COLUMN_ENTRIES'), true, 'minmax(4rem, 0.75fr)', 'right');
    $grid->addColumn('work_gross', Strings::i18n('BUSINESS_SITES_GRID_COLUMN_WORK_GROSS'), true, 'minmax(6rem, 1fr)', 'right');
    $grid->addColumn('wage', Strings::i18n('WAGE'), true, 'minmax(5rem, 0.85fr)', 'right', false);
    $grid->addColumn('last_worked', Strings::i18n('BUSINESS_SITES_GRID_COLUMN_LAST_WORKED'), true, 'minmax(7rem, 1fr)', 'right', false);
    $grid->addColumn('budget_amount', Strings::i18n('BUSINESS_SITES_GRID_COLUMN_BUDGET'), true, 'minmax(6rem, 1fr)', 'right');
    $grid->addColumn('budget_used', Strings::i18n('BUSINESS_SITES_GRID_COLUMN_USED'), true, 'minmax(7rem, 1.1fr)', 'right', true, true, true);
    if ($canWrite) {
      if ($status === SiteStatus::ARCHIVED->value) {
        $grid->addRowAction(
          'restore-site',
          Strings::i18n('SITES_RESTORE_ACTION'),
          Strings::i18n('SITES_RESTORE_ARIA'),
          Strings::i18n('SITES_RESTORE_TOOLTIP'),
        );
      } else {
        $grid->addRowAction(
          'archive-site',
          '📦',
          Strings::i18n('SITES_ARCHIVE_SITE'),
          Strings::i18n('SITES_ARCHIVE_SITE'),
        );
      }
    }
    $grid->setRowActionsHeaderLabel('');
    $grid->setItemLabel(Strings::i18n('BUSINESS_SITES_ITEM_LABEL'));

    $pager = ArrayPager::fromArray($rows, ['pageSize' => self::GRID_PAGE_SIZE]);
    $pager->setPage($page);
    $html = $grid->table($pager);

    $start = $pager->getTotal() === 0 ? 0 : (($pager->getPage() - 1) * $pager->getPageSize()) + 1;
    $end = min($pager->getPage() * $pager->getPageSize(), $pager->getTotal());
    $total = $pager->getTotal();

    $searchAttr = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
    $sortAttr = htmlspecialchars($sort, ENT_QUOTES, 'UTF-8');
    $directionAttr = htmlspecialchars($direction, ENT_QUOTES, 'UTF-8');
    $gridId = 'business-sites-' . $status;
    $pattern = '/(<div\\s+id="' . preg_quote($gridId, '/') . '"[^>]*data-grid="' . preg_quote($gridId, '/') . '"[^>]*)>/';
    $replacement = '$1 data-search="' . $searchAttr . '" data-sort="' . $sortAttr . '" data-direction="' . $directionAttr . '" data-pagination-start="' . $start . '" data-pagination-end="' . $end . '" data-pagination-total="' . $total . '" data-total-pages="' . $pager->getTotalPages() . '">';

    return (string) preg_replace($pattern, $replacement, $html, 1);
  }

  /**
   * Loading skeleton.
   */
  public function loadingSkeleton(): string
  {
    return DataGrid::loadingSkeleton(8, 4);
  }

  /**
   * Empty message.
   */
  public function emptyMessage(string $message): string
  {
    return '<div class="datagrid_empty">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
  }

  /**
   * Ownership status tag label.
   */
  private function ownershipStatusTagLabel(string $ownershipScope): string
  {
    return match ($ownershipScope) {
      BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_BUSINESS => Strings::i18n('BUSINESS_SITES_STATUS_TAG_BUSINESS'),
      BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_SHARED => Strings::i18n('BUSINESS_SITES_STATUS_TAG_SHARED'),
      default => Strings::i18n('BUSINESS_SITES_STATUS_TAG_PERSONAL'),
    };
  }

  /**
   * Format ownership symbol HTML.
   */
  private function formatOwnershipSymbolHtml(string $ownershipScope): string
  {
    $class = match ($ownershipScope) {
      BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_BUSINESS => 'business_sites_ownership_symbol--business',
      BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_SHARED => 'business_sites_ownership_symbol--shared',
      default => 'business_sites_ownership_symbol--personal',
    };

    return '<span class="business_sites_ownership_symbol ' . $class . '" aria-hidden="true"></span>';
  }

  /**
   * Format ownership status pill HTML.
   */
  private function formatOwnershipStatusPillHtml(string $ownershipScope): string
  {
    $symbolClass = match ($ownershipScope) {
      BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_BUSINESS => 'business_sites_ownership_status--business',
      BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_SHARED => 'business_sites_ownership_status--shared',
      default => 'business_sites_ownership_status--personal',
    };
    $label = $this->ownershipStatusTagLabel($ownershipScope);

    return '<span class="business_sites_ownership_status ' . $symbolClass . '">'
      . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
      . '</span>';
  }

  /**
   * Format budget used HTML.
   */
  private function formatBudgetUsedHtml(float $workGross, float $budgetAmount): string
  {
    if ($budgetAmount <= 0) {
      return '<span class="business_sites_used_cell business_sites_used_cell--empty">—</span>';
    }

    $pct = min(100.0, ($workGross / $budgetAmount) * 100);
    $pctValue = $pct > 0.0 && $pct < 0.1 ? 0.1 : round($pct, 1);
    $pctDisplay = $pct > 0.0 && $pct < 0.1 ? '<0.1%' : number_format($pct, 1) . '%';

    return '<div class="business_sites_used_cell">'
      . '<meter class="business_sites_used_meter" min="0" max="100" value="'
      . htmlspecialchars((string) $pctValue, ENT_QUOTES, 'UTF-8')
      . '" title="' . htmlspecialchars($pctDisplay, ENT_QUOTES, 'UTF-8') . '"></meter>'
      . '<span class="business_sites_used_pct">' . htmlspecialchars($pctDisplay, ENT_QUOTES, 'UTF-8') . '</span>'
      . '</div>';
  }

  /**
   * @param array<string, mixed> $siteHash
   * @param array<string, mixed> $site
   */
  private function normalizeSiteColor(array $siteHash, array $site = []): string
  {
    $raw = '';
    if (is_scalar($site['site_color'] ?? null)) {
      $raw = trim((string) $site['site_color']);
    } elseif (is_scalar($siteHash['site_color'] ?? null)) {
      $raw = trim((string) $siteHash['site_color']);
    }

    $color = strtoupper($raw);

    return ('' !== $color && preg_match('/^#[0-9A-F]{6}$/', $color)) ? $color : '';
  }

  /**
   * Format site name cell HTML.
   */
  private function formatSiteNameCellHtml(string $siteName, string $ownershipScope, bool $isBusinessManaged): string
  {
    $displayName = $isBusinessManaged ? '[EIC] ' . $siteName : $siteName;

    return '<span class="business_sites_site_name_cell">'
      . '<span class="business_sites_site_name_primary">'
      . $this->formatOwnershipSymbolHtml($ownershipScope)
      . '<span class="business_sites_site_name_text">' . htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') . '</span>'
      . '</span>'
      . $this->formatOwnershipStatusPillHtml($ownershipScope)
      . '</span>';
  }

  /**
   * @param list<array<string, mixed>> $rows
   * @return list<array<string, mixed>>
   */
  private function hydrateSiteMetricRows(array $rows): array
  {
    if ($rows === []) {
      return [];
    }

    $workMetrics = $this->batchSiteWorkMetrics($rows);
    foreach ($rows as &$row) {
      if (($row['_metrics_loaded'] ?? false) === true) {
        continue;
      }

      $siteRef = isset($row['_site_ref']) && is_scalar($row['_site_ref'])
        ? trim((string) $row['_site_ref'])
        : '';
      if ($siteRef === '') {
        $siteOwnerUUID = isset($row['site_owner_uuid']) && is_scalar($row['site_owner_uuid']) ? trim((string) $row['site_owner_uuid']) : '';
        $siteId = isset($row['site_id']) && is_scalar($row['site_id']) ? trim((string) $row['site_id']) : '';
        $siteRef = $siteOwnerUUID !== '' && $siteId !== '' ? $siteOwnerUUID . ':' . $siteId : '';
      }

      $metrics = $workMetrics[$siteRef] ?? ['count' => 0, 'gross' => 0.0, 'last_worked' => ''];
      $entries = (int) $metrics['count'];
      $workGross = (float) $metrics['gross'];
      $lastWorked = $metrics['last_worked'];
      $budgetAmount = is_numeric($row['_budget_amount'] ?? null) ? (float) $row['_budget_amount'] : 0.0;

      $row['entries'] = (string) $entries;
      $row['work_gross'] = '$' . number_format($workGross, 0);
      $row['last_worked'] = $this->formatGridDate($lastWorked);
      $row['budget_used'] = $this->formatBudgetUsedHtml($workGross, $budgetAmount);
      $row['_entries'] = $entries;
      $row['_work_gross'] = $workGross;
      $row['_last_worked'] = $lastWorked;
      $row['_budget_used_pct'] = $budgetAmount > 0 ? min(100.0, ($workGross / $budgetAmount) * 100) : 0.0;
      $row['_metrics_loaded'] = true;
    }
    unset($row);

    return $rows;
  }

  /**
   * Count work entries and sum gross pay per linked site (one SCAN pair per unique owner).
   *
   * @param array<int, mixed> $sites
   * @return array<string, array{count: int, gross: float, last_worked: string}> "ownerUUID:siteId" => metrics
   */
  private function batchSiteWorkMetrics(array $sites): array
  {
    /** @var array<string, array<string, true>> $siteIdsByOwner */
    $siteIdsByOwner = [];

    foreach ($sites as $site) {
      if (!is_array($site)) {
        continue;
      }

      $siteOwnerUUID = isset($site['site_owner_uuid']) && is_scalar($site['site_owner_uuid'])
        ? trim((string) $site['site_owner_uuid'])
        : '';
      $siteId = isset($site['site_id']) && is_scalar($site['site_id'])
        ? trim((string) $site['site_id'])
        : '';
      if ($siteOwnerUUID === '' || $siteId === '') {
        continue;
      }

      $siteIdsByOwner[$siteOwnerUUID][$siteId] = true;
    }

    $metrics = [];
    foreach ($siteIdsByOwner as $ownerUUID => $siteIds) {
      $workKeys = array_merge(
        Database::scanKeys(Keys::WORK . ':' . $ownerUUID . ':*'),
        Database::scanKeys(Keys::WORK . ':archived:' . $ownerUUID . ':*'),
      );
      if ($workKeys === []) {
        continue;
      }

      $entries = Database::pipelineHgetall($workKeys);
      foreach ($entries as $workKey => $entry) {
        $keyParts = explode(':', (string) $workKey);
        $isArchived = ($keyParts[1] ?? '') === 'archived';
        $siteIdFromKey = $isArchived ? (string) ($keyParts[4] ?? '') : (string) ($keyParts[3] ?? '');
        if ($siteIdFromKey === '' || !isset($siteIds[$siteIdFromKey])) {
          continue;
        }

        $ref = $ownerUUID . ':' . $siteIdFromKey;
        if (!isset($metrics[$ref])) {
          $metrics[$ref] = ['count' => 0, 'gross' => 0.0, 'last_worked' => ''];
        }

        $metrics[$ref]['count']++;
        $workDate = $this->workDateFromKey((string) $workKey);
        if ($workDate !== '' && strcmp($workDate, $metrics[$ref]['last_worked']) > 0) {
          $metrics[$ref]['last_worked'] = $workDate;
        }
        $grossRaw = $entry['gross'] ?? $entry['g'] ?? 0;
        $metrics[$ref]['gross'] += is_numeric($grossRaw) ? (float) $grossRaw : 0.0;
      }
    }

    return $metrics;
  }

  /**
   * Return a trimmed scalar string or the supplied fallback.
   */
  private static function scalarString(mixed $value, string $default = ''): string
  {
    return is_scalar($value) ? trim((string) $value) : $default;
  }

  /**
   * Return a numeric value as float or the supplied fallback.
   */
  private static function numericFloat(mixed $value, float $default = 0.0): float
  {
    return is_numeric($value) ? (float) $value : $default;
  }

}
