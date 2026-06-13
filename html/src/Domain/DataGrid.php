<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * DataGrid.php
 *
 * Purpose: Server-side datagrid builder: manages columns, rows, pager metadata,
 *          and serialization for grid-based UI components.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

class DataGrid
{
  private const MAX_COLUMN_CLASS_COUNT = 10;
  private const MIN_MONTH = 1;
  private const MAX_MONTH = 12;

  private string $id;

  /** @var array<int, array<string, mixed>> */
  private array $columns;

  /** @var array<int, array<string, mixed>> */
  private array $rows;

  /** @var array<string, mixed> */
  private array $meta;

  /**
   * Handles toString operation.
   */
  private static function toString(mixed $value, string $default = ''): string
  {
    return is_scalar($value) ? (string) $value : $default;
  }

  /**
   * Handles toInt operation.
   */
  private static function toInt(mixed $value, int $default = 0): int
  {
    return is_numeric($value) ? (int) $value : $default;
  }

  /**
   * Handles toFloat operation.
   */
  private static function toFloat(mixed $value, float $default = 0.0): float
  {
    return is_numeric($value) ? (float) $value : $default;
  }

  /**
   * Resolve request locale for number formatting.
   */
  private static function numberLocale(): string
  {
    if (defined('USER_LOCALE') && is_string(USER_LOCALE) && USER_LOCALE !== '') {
      return USER_LOCALE;
    }

    return 'en_US';
  }

  /**
   * Format compact numeric values for grid cell display.
   */
  private static function formatCompactNumber(float $value): string
  {
    if (class_exists('\\NumberFormatter')) {
      $formatter = new \NumberFormatter(self::numberLocale(), \NumberFormatter::DECIMAL);
      $formatter->setAttribute(\NumberFormatter::GROUPING_USED, 1);
      $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
      $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 2);
      $formatted = $formatter->format($value);
      if (is_string($formatted)) {
        return $formatted;
      }
    }

    return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  private static function listAssoc(mixed $value): array
  {
    if (!is_array($value)) {
      return [];
    }

    $out = [];
    foreach ($value as $item) {
      if (!is_array($item)) {
        continue;
      }

      $assoc = [];
      foreach ($item as $k => $v) {
        $assoc[(string) $k] = $v;
      }
      $out[] = $assoc;
    }

    return $out;
  }

  /**
   * Build CSS grid track list for column layout (fills container width).
   */
  private function buildColumnTemplate(bool $hasRowActions): string
  {
    $columnCount = count($this->columns);
    if (0 === $columnCount) {
      return $hasRowActions ? 'max-content' : 'minmax(0, 1fr)';
    }

    $tracks = [];
    $hasExplicitWidth = false;

    foreach ($this->columns as $column) {
      $width = self::toString($column['width'] ?? '');
      if ('' !== $width) {
        $hasExplicitWidth = true;
        $tracks[] = $width;
      } else {
        $tracks[] = 'minmax(0, 1fr)';
      }
    }

    if ($hasRowActions) {
      $tracks[] = 'max-content';
    }

    if (!$hasExplicitWidth) {
      $flexTrack = 'minmax(0, 1fr)';
      $flexTracks = array_fill(0, $columnCount, $flexTrack);

      return implode(' ', $flexTracks).($hasRowActions ? ' max-content' : '');
    }

    return implode(' ', $tracks);
  }

  /**
   * @param array<string, mixed> $column
   */
  private function columnClassSuffix(array $column): string
  {
    $columnKey = self::toString($column['key'] ?? '');

    return '' !== $columnKey
      ? preg_replace('/[^a-z0-9]+/', '_', strtolower($columnKey)) ?? ''
      : '';
  }

  /**
   * @param array<string, mixed> $column
   */
  private function headingClass(array $column): string
  {
    $class = 'datagrid_heading';
    $suffix = $this->columnClassSuffix($column);
    if ('' !== $suffix) {
      $class .= ' datagrid_col_'.$suffix;
    }

    $columnAlign = self::toString($column['align'] ?? '');
    if ('' !== $columnAlign) {
      $class .= ' datagrid_align_'.$columnAlign;
    }

    return $class;
  }

  /**
   * @param array<string, mixed> $column
   */
  private function itemClass(array $column): string
  {
    $class = 'datagrid_item';
    $suffix = $this->columnClassSuffix($column);
    if ('' !== $suffix) {
      $class .= ' datagrid_col_'.$suffix;
    }

    $columnAlign = self::toString($column['align'] ?? '');
    if ('' !== $columnAlign) {
      $class .= ' datagrid_align_'.$columnAlign;
    }

    if (!empty($column['noEllipsis'])) {
      $class .= ' datagrid_no_ellipsis';
    }

    return $class;
  }

  private function resolvePager(mixed $pager): ?PagerInterface
  {
    return $pager instanceof PagerInterface ? $pager : null;
  }

  /**
   * @return array{start: int, end: int, total: int, totalPages: int}|null
   */
  private function paginationMetrics(?PagerInterface $pager): ?array
  {
    if (null === $pager) {
      return null;
    }

    $total = $pager->getTotal();
    $page = $pager->getPage();
    $pageSize = max(1, $pager->getPageSize());
    $start = 0 === $total ? 0 : (($page - 1) * $pageSize) + 1;
    $end = min($page * $pageSize, $total);

    return [
      'start' => $start,
      'end' => $end,
      'total' => $total,
      'totalPages' => $pager->getTotalPages(),
    ];
  }

  /**
   * @return list<int|string>
   */
  private function buildPageNumberWindow(int $currentPage, int $totalPages): array
  {
    if ($totalPages <= 1) {
      return $totalPages > 0 ? [1] : [];
    }

    if ($totalPages <= 7) {
      $pages = [];
      for ($i = 1; $i <= $totalPages; $i++) {
        $pages[] = $i;
      }

      return $pages;
    }

    $pages = [1];
    $left = max(2, $currentPage - 1);
    $right = min($totalPages - 1, $currentPage + 1);

    if ($left > 2) {
      $pages[] = '…';
    }

    for ($i = $left; $i <= $right; $i++) {
      $pages[] = $i;
    }

    if ($right < $totalPages - 1) {
      $pages[] = '…';
    }

    $pages[] = $totalPages;

    return $pages;
  }

  /**
   * @param array<string, string> $i18n
   */
  private function renderPaginationBar(?PagerInterface $pager, array $i18n, string $position): string
  {
    if (null === $pager) {
      return '';
    }

    $metrics = $this->paginationMetrics($pager);
    if (null === $metrics) {
      return '';
    }

    $itemLabel = self::toString($this->meta['itemLabel'] ?? 'items', 'items');
    $paginationAria = self::toString($i18n['DATAGRID_PAGINATION_ARIA'] ?? 'Grid pagination', 'Grid pagination');
    $previousLabel = self::toString($i18n['PREVIOUS'] ?? 'Previous', 'Previous');
    $nextLabel = self::toString($i18n['NEXT'] ?? 'Next', 'Next');

    ob_start();
    ?>
    <nav class="datagrid_pager datagrid_pagination_<?php echo $this->escape($position); ?>" role="navigation" aria-label="<?php echo $this->escape($paginationAria); ?>">
      <p class="datagrid_pagination_info">
        <?php if (0 === $metrics['total']) { ?>
          <?php echo $this->escape(sprintf('No %s found', $itemLabel)); ?>
        <?php } else { ?>
          <?php echo $this->escape(sprintf('Showing %d–%d of %d %s', $metrics['start'], $metrics['end'], $metrics['total'], $itemLabel)); ?>
        <?php } ?>
      </p>
      <?php if ($pager->hasPagination()) { ?>
        <div class="datagrid_pagination_nav">
          <button type="button" class="datagrid_pagination_btn" data-direction="prev"<?php echo $pager->hasPrev() ? '' : ' disabled'; ?>>
            ← <?php echo $this->escape($previousLabel); ?>
          </button>
          <?php foreach ($this->buildPageNumberWindow($pager->getPage(), $metrics['totalPages']) as $pageNumber) {
            if ('…' === $pageNumber) { ?>
              <span class="datagrid_pagination_ellipsis" aria-hidden="true">…</span>
            <?php continue;
            }

            $pageInt = self::toInt($pageNumber, 1);
            $isCurrent = $pageInt === $pager->getPage();
            $pageClass = 'datagrid_pagination_btn datagrid_pagination_page'.($isCurrent ? ' datagrid_pagination_page_active' : '');
          ?>
            <button
              type="button"
              class="<?php echo $this->escape($pageClass); ?>"
              data-page="<?php echo $pageInt; ?>"
              <?php echo $isCurrent ? 'aria-current="page"' : ''; ?>
              <?php echo $isCurrent ? 'disabled' : ''; ?>
            ><?php echo $pageInt; ?></button>
          <?php } ?>
          <button type="button" class="datagrid_pagination_btn" data-direction="next"<?php echo $pager->hasNext() ? '' : ' disabled'; ?>>
            <?php echo $this->escape($nextLabel); ?> →
          </button>
        </div>
      <?php } ?>
    </nav>
    <?php

    return (string) ob_get_clean();
  }

  /** @param array<string, mixed> $config */
  public function __construct(array $config)
  {
    $this->id = self::toString($config['id'] ?? 'datagrid', 'datagrid');
    $this->columns = self::listAssoc($config['columns'] ?? []);
    $this->rows = self::listAssoc($config['rows'] ?? []);

    $metaRaw = $config['meta'] ?? [];
    $meta = [];
    if (is_array($metaRaw)) {
      foreach ($metaRaw as $k => $v) {
        $meta[(string) $k] = $v;
      }
    }

    $this->meta = array_merge([
        'page' => 1,
        'totalPages' => 1,
        'search' => '',
        'sort' => '',
        'direction' => 'asc',
      ], $meta);
  }

  /**
   * Render the table for the grid (stub for test).
   *
   * @param null|mixed $pager
   */
  public function table($pager = null): string
  {
    $layout = self::toString($this->meta['layout'] ?? 'auto', 'auto');
    
    // For month layout, use dedicated renderer
    if ('month' === $layout) {
      return $this->renderMonth($pager);
    }
    
    // Standard table layout
    return $this->renderTable($pager);
  }

  /**
   * Render standard table grid layout.
   *
   * @param null|mixed $pager
   */
  private function renderTable($pager = null): string
  {
    $page = self::toInt($this->meta['page'] ?? 1, 1);
    $rows = $this->rows;
    $i18n = [];
    $i18nKeys = [
      'DATAGRID_DATA_GRID',
      'DATAGRID_CALENDAR_MONTH_NAVIGATION',
      'DATAGRID_PAGINATION_ARIA',
      'ACTION',
      'SEARCH',
      'PREVIOUS',
      'NEXT',
      'DATAGRID_NO_ENTRIES_FOUND',
      'DATAGRID_FULLSCREEN_ENTER',
      'DATAGRID_FULLSCREEN_EXIT',
    ];
    foreach ($i18nKeys as $key) {
      $i18n[$key] = Strings::i18n($key);
    }

    $pagerInterface = null;

    if (null !== $pager) {
      if (is_array($pager) && isset($pager['page'])) {
        $page = self::toInt($pager['page'], 1);
      } elseif (is_object($pager) && method_exists($pager, 'getPage')) {
        $page = self::toInt($pager->getPage(), 1);
      }

      if (is_object($pager) && method_exists($pager, 'getRows')) {
        $pagerRows = $pager->getRows();
        if (is_array($pagerRows)) {
          $rows = self::listAssoc($pagerRows);
        }
      }

      $pagerInterface = $this->resolvePager($pager);
    }

    $paginationMetrics = $this->paginationMetrics($pagerInterface);
    $rowActions = self::listAssoc($this->meta['rowActions'] ?? []);
    $controls = self::listAssoc($this->meta['controls'] ?? []);
    $controlsTrailingHtml = self::toString($this->meta['controlsTrailingHtml'] ?? '', '');
    $columnCount = count($this->columns);
    $totalColumnCount = $columnCount + (!empty($rowActions) ? 1 : 0);
    $rowCount = count($rows);
    $columnClass = 'datagrid_cols_'.max(1, min(self::MAX_COLUMN_CLASS_COUNT, $totalColumnCount));

    // Use layout class instead of inline styles
    $layout = self::toString($this->meta['layout'] ?? 'auto', 'auto');
    $layoutClass = 'datagrid_layout_'.$layout;
    $chromeClass = !empty($this->meta['noChrome']) ? ' datagrid_no_chrome' : '';
    $descriptionId = trim(self::toString($this->meta['descriptionId'] ?? ''));
    $rowActionsHeaderLabel = self::toString($this->meta['rowActionsHeaderLabel'] ?? 'Actions', 'Actions');
    
    // Apply rowAdapter if provided (optional row data transformation)
    $rowAdapter = $this->meta['rowAdapter'] ?? null;
    if (is_callable($rowAdapter)) {
      $rows = array_map(function(array $row) use ($rowAdapter): array {
        $adapted = $rowAdapter($row);
        if (!is_array($adapted)) {
          return $row;
        }

        $normalized = [];
        foreach ($adapted as $k => $v) {
          $normalized[(string) $k] = $v;
        }

        return $normalized;
      }, $rows);
    }

    $columnResizeEnabled = !empty($this->meta['columnResizeEnabled']);
    $virtualScrollEnabled = !empty($this->meta['virtualScrollEnabled']);
    $columnTemplate = $this->buildColumnTemplate(!empty($rowActions));
    $paginationAttrs = '';
    if (null !== $paginationMetrics) {
      $paginationAttrs = ' data-total-pages="'.$paginationMetrics['totalPages'].'"'
        .' data-pagination-start="'.$paginationMetrics['start'].'"'
        .' data-pagination-end="'.$paginationMetrics['end'].'"'
        .' data-pagination-total="'.$paginationMetrics['total'].'"';
    }

    ob_start();
    ?>
    <div id="<?php echo $this->escape($this->id); ?>" class="datagrid <?php echo $this->escape($columnClass); ?> <?php echo $this->escape($layoutClass.$chromeClass); ?>" data-grid="<?php echo $this->escape($this->id); ?>" data-page="<?php echo $page; ?>" data-year="<?php echo $this->escape(self::toString($this->meta['year'] ?? date('Y'))); ?>" data-month="<?php echo $this->escape(self::toString($this->meta['month'] ?? date('m'))); ?>" data-autofocus="<?php echo $this->escape(self::toString($this->meta['autofocus'] ?? 'current')); ?>" data-date-label-position="<?php echo $this->escape(self::toString($this->meta['dateLabelPosition'] ?? 'left')); ?>" data-work-entry-position="<?php echo $this->escape(self::toString($this->meta['workEntryPosition'] ?? 'left')); ?>" data-column-template="<?php echo $this->escape($columnTemplate); ?>"<?php echo $paginationAttrs; ?><?php echo $virtualScrollEnabled ? ' data-virtualize="1"' : ''; ?><?php echo $columnResizeEnabled ? ' data-column-resize="1"' : ''; ?> role="region" aria-label="<?php echo $this->escape(self::toString($this->meta['title'] ?? $this->id, $i18n['DATAGRID_DATA_GRID'])); ?>"<?php echo '' !== $descriptionId ? ' aria-describedby="' . $this->escape($descriptionId) . '"' : ''; ?>>
      <div class="datagrid_controls" role="navigation" aria-label="<?php echo $this->escape($i18n['DATAGRID_CALENDAR_MONTH_NAVIGATION']); ?>">
        <?php foreach ($controls as $control) {
          $controlType = self::toString($control['type'] ?? 'secondary', 'secondary');
          $controlClass = 'datagrid_control';
          if ('primary' === $controlType) {
            $controlClass .= ' datagrid_control_primary';
          }
        ?>
          <button
            type="button"
            class="<?php echo $this->escape($controlClass); ?>"
            data-action="<?php echo $this->escape(self::toString($control['action'] ?? '')); ?>"
          >
            <?php echo $this->escape(self::toString($control['label'] ?? $i18n['ACTION'], $i18n['ACTION'])); ?>
          </button>
        <?php } ?>

        <div class="datagrid_controls_end">
          <?php if (!empty($this->meta['searchEnabled'])) { ?>
            <input
              type="search"
              class="datagrid_search"
              placeholder="<?php echo $this->escape(self::toString($this->meta['searchPlaceholder'] ?? $i18n['SEARCH'], $i18n['SEARCH'])); ?>"
              value="<?php echo $this->escape(self::toString($this->meta['search'] ?? '')); ?>"
            >
          <?php } ?>
          <button
            type="button"
            class="datagrid_icon_button datagrid_fullscreen_toggle"
            data-action="toggle-fullscreen"
            aria-expanded="false"
            aria-label="<?php echo $this->escape($i18n['DATAGRID_FULLSCREEN_ENTER']); ?>"
            data-label-enter="<?php echo $this->escape($i18n['DATAGRID_FULLSCREEN_ENTER']); ?>"
            data-label-exit="<?php echo $this->escape($i18n['DATAGRID_FULLSCREEN_EXIT']); ?>"
          >
            <span class="datagrid_fullscreen_icon datagrid_fullscreen_icon_expand" aria-hidden="true">⤢</span>
            <span class="datagrid_fullscreen_icon datagrid_fullscreen_icon_collapse" aria-hidden="true">⤡</span>
          </button>
        </div>
      </div>

      <?php echo $this->renderPaginationBar($pagerInterface, $i18n, 'top'); ?>

      <div class="datagrid_table" role="grid" aria-colcount="<?php echo $totalColumnCount; ?>" aria-rowcount="<?php echo $rowCount + 1; ?>">
        <div class="datagrid_header_row" role="rowgroup">
          <div class="datagrid_header_content" role="row">
            <?php foreach ($this->columns as $columnIndex => $column) {
              $isSortable = !empty($column['sortable']);
              $columnKey = self::toString($column['key'] ?? '');
              $columnLabel = self::toString($column['label'] ?? '');
              $columnHeaderId = $this->id.'_col_'.($columnIndex + 1);
              $headingClass = $this->headingClass($column);
            ?>
              <div class="<?php echo $this->escape($headingClass); ?>" role="columnheader" id="<?php echo $this->escape($columnHeaderId); ?>" data-col-key="<?php echo $this->escape($columnKey); ?>">
                <?php if ($isSortable) { ?>
                  <button type="button" class="datagrid_sort" data-column="<?php echo $this->escape($columnKey); ?>">
                    <?php echo $this->escape($columnLabel); ?>
                  </button>
                <?php } else { ?>
                  <?php echo $this->escape($columnLabel); ?>
                <?php } ?>
                <?php if ($columnResizeEnabled && '' !== $columnKey) { ?>
                  <span class="datagrid_col_resize" data-resize-col="<?php echo $this->escape($columnKey); ?>" aria-hidden="true" tabindex="-1"></span>
                <?php } ?>
              </div>
            <?php } ?>
            <?php if (!empty($rowActions)) { ?>
              <div class="datagrid_heading datagrid_heading_actions datagrid_col_actions" role="columnheader" id="<?php echo $this->escape($this->id.'_col_actions'); ?>"><?php echo $this->escape($rowActionsHeaderLabel); ?></div>
            <?php } ?>
          </div>
        </div>

        <div class="datagrid_body" role="rowgroup">
          <?php if (empty($rows)) { ?>
            <div class="datagrid_row datagrid_row_empty" role="row">
              <div class="datagrid_row_content">
                <div class="datagrid_item datagrid_empty" role="gridcell" aria-colspan="<?php echo $totalColumnCount; ?>">
                  <span role="status" aria-live="polite"><?php echo $i18n['DATAGRID_NO_ENTRIES_FOUND']; ?></span>
                </div>
              </div>
            </div>
          <?php } ?>

          <?php foreach ($rows as $row) {
            $rowColorRaw = self::toString($row['site_color'] ?? '');
            $rowColor = strtoupper($rowColorRaw);
            $rowColorAttr = ('' !== $rowColor && preg_match('/^#[0-9A-Fa-f]{6}$/', $rowColor))
              ? ' data-color="' . $this->escape($rowColor) . '"'
              : '';
          ?>
            <div class="datagrid_row" role="row" tabindex="0" data-id="<?php echo $this->escape(self::toString($row['id'] ?? '')); ?>"<?php echo $rowColorAttr; ?>>
              <div class="datagrid_row_content">
                <?php foreach ($this->columns as $columnIndex => $column) {
                  $columnKey = self::toString($column['key'] ?? '');
                  $value = ('' !== $columnKey) ? ($row[$columnKey] ?? '') : '';
                  $columnHeaderId = $this->id.'_col_'.($columnIndex + 1);
                  $itemClass = $this->itemClass($column);

                  // Apply compute function if provided
                  $compute = $column['compute'] ?? null;
                  if (is_callable($compute)) {
                    $value = $compute($row, $column);
                  }
                ?>
                  <div class="<?php echo $this->escape($itemClass); ?>" role="gridcell" aria-labelledby="<?php echo $this->escape($columnHeaderId); ?>" data-col-key="<?php echo $this->escape($columnKey); ?>">
                    <?php echo $this->escape(self::toString($value)); ?>
                  </div>
                <?php } ?>

                <?php if (!empty($rowActions)) { ?>
                  <div class="datagrid_item datagrid_item_actions" role="gridcell" aria-labelledby="<?php echo $this->escape($this->id.'_col_actions'); ?>">
                    <div class="datagrid_actions">
                      <?php foreach ($rowActions as $action) {
                        $actionName = self::toString($action['action'] ?? 'action', 'action');
                        $actionClass = 'datagrid_action';
                        if ('delete' === $actionName) {
                          $actionClass .= ' datagrid_action_danger';
                        }
                      ?>
                        <button
                          type="button"
                          class="<?php echo $this->escape($actionClass); ?>"
                          data-action="<?php echo $this->escape($actionName); ?>"
                          data-id="<?php echo $this->escape(self::toString($row['id'] ?? '')); ?>"
                          aria-label="<?php echo $this->escape($actionName); ?>"
                        >
                          <?php echo $this->escape(self::toString($action['label'] ?? '')); ?>
                        </button>
                      <?php } ?>
                    </div>
                  </div>
                <?php } ?>
              </div>
            </div>
          <?php } ?>
        </div>
      </div>

      <?php echo $this->renderPaginationBar($pagerInterface, $i18n, 'bottom'); ?>
    </div>
    <?php

    return (string) ob_get_clean();
  }

  /**
   * Render month calendar layout (7-column grid).
   * Organizes rows into a proper calendar month view.
   *
   * @param null|mixed $pager
   */
  private function renderMonth($pager = null): string
  {
    $page = self::toInt($this->meta['page'] ?? 1, 1);
    $rows = $this->rows;
    $i18n = [];
    $i18nKeys = ['PREVIOUS', 'NEXT', 'ACTION', 'DATAGRID_NO_ENTRIES_FOUND'];
    foreach ($i18nKeys as $key) {
      $i18n[$key] = Strings::i18n($key);
    }

    if (null !== $pager) {
      if (is_array($pager) && isset($pager['page'])) {
        $page = self::toInt($pager['page'], 1);
      } elseif (is_object($pager) && method_exists($pager, 'getPage')) {
        $page = self::toInt($pager->getPage(), 1);
      }

      if (is_object($pager) && method_exists($pager, 'getRows')) {
        $pagerRows = $pager->getRows();
        if (is_array($pagerRows)) {
          $rows = self::listAssoc($pagerRows);
        }
      }
    }

    $controls = self::listAssoc($this->meta['controls'] ?? []);
    $controlsTrailingHtml = self::toString($this->meta['controlsTrailingHtml'] ?? '', '');

    // Get positioning from config (set at DataGrid initialization based on context needs)
    $dateLabelPosition = self::toString($this->meta['dateLabelPosition'] ?? 'left', 'left');
    $workEntryPosition = self::toString($this->meta['workEntryPosition'] ?? 'left', 'left');
    
    // Map position values to CSS class suffixes (middle -> center)
    $dateLabelClass = ('middle' === $dateLabelPosition) ? 'center' : $dateLabelPosition;
    $workEntryClass = ('middle' === $workEntryPosition) ? 'center' : $workEntryPosition;
    
    // Apply rowAdapter if provided
    $rowAdapter = $this->meta['rowAdapter'] ?? null;
    if (is_callable($rowAdapter)) {
      $rows = array_map(function(array $row) use ($rowAdapter): array {
        $adapted = $rowAdapter($row);
        if (!is_array($adapted)) {
          return $row;
        }

        $normalized = [];
        foreach ($adapted as $k => $v) {
          $normalized[(string) $k] = $v;
        }

        return $normalized;
      }, $rows);
    }

    // Build a map of dates for quick lookup
    $dateMap = [];
    foreach ($rows as $row) {
      $dateId = self::toString($row['id'] ?? '');
      if ('' !== $dateId) {
        $dateMap[$dateId] = $row;
      }
    }

    ob_start();
    
    // Calculate previous/next month for navigation
    $year = self::toInt($this->meta['year'] ?? date('Y'), (int) date('Y'));
    $month = self::toInt($this->meta['month'] ?? date('m'), (int) date('m'));
    $nextMonth = $month + 1;
    $nextYear = $year;
    $prevMonth = $month - 1;
    $prevYear = $year;
    
    if ($nextMonth > self::MAX_MONTH) {
      $nextMonth = self::MIN_MONTH;
      $nextYear = $year + 1;
    }
    if ($prevMonth < self::MIN_MONTH) {
      $prevMonth = self::MAX_MONTH;
      $prevYear = $year - 1;
    }
    
    // Format month names
    $currentMonthName = (new \DateTime("$year-$month-01"))->format('F Y');
    $currentMonthValue = sprintf('%04d-%02d', $year, $month);
    $chromeClass = !empty($this->meta['noChrome']) ? ' datagrid_no_chrome' : '';
    $descriptionId = trim(self::toString($this->meta['descriptionId'] ?? 'calendar-grid-instructions'));
    
    ?>
    <div id="<?php echo $this->escape($this->id); ?>" class="datagrid datagrid_layout_month<?php echo $this->escape($chromeClass); ?>" data-grid="<?php echo $this->escape($this->id); ?>" data-page="<?php echo $page; ?>" data-year="<?php echo $this->escape((string) $year); ?>" data-month="<?php echo $this->escape((string) $month); ?>" data-autofocus="<?php echo $this->escape(self::toString($this->meta['autofocus'] ?? 'today', 'today')); ?>" data-date-label-position="<?php echo $this->escape(self::toString($this->meta['dateLabelPosition'] ?? 'left', 'left')); ?>" data-work-entry-position="<?php echo $this->escape(self::toString($this->meta['workEntryPosition'] ?? 'left', 'left')); ?>" data-lockboundary="<?php echo $this->escape(self::toString($this->meta['lockBoundary'] ?? '')); ?>">
      <div class="datagrid_controls">
        <button
          type="button"
          class="datagrid_control"
          data-action="prev-month"
          data-month="<?php echo $prevMonth; ?>"
          data-year="<?php echo $prevYear; ?>"
          aria-label="Previous month ([ or Page Up)"
          aria-keyshortcuts="[ PageUp"
          accesskey="["
        >
          ← <?php echo $i18n['PREVIOUS']; ?>
        </button>
        <button
          type="button"
          id="cal_picker_button"
          class="calendar-v2-month-title"
          data-action="open-month-picker"
          data-year="<?php echo $year; ?>"
          data-month="<?php echo $month; ?>"
          aria-label="<?php echo htmlspecialchars($currentMonthName, ENT_QUOTES, 'UTF-8'); ?>"
          aria-keyshortcuts="ALT+\\"
          accesskey="\\"
        ><?php echo htmlspecialchars($currentMonthName, ENT_QUOTES, 'UTF-8'); ?></button>
        <button
          type="button"
          class="datagrid_control"
          data-action="next-month"
          data-month="<?php echo $nextMonth; ?>"
          data-year="<?php echo $nextYear; ?>"
          aria-label="Next month (] or Page Down)"
          aria-keyshortcuts="] PageDown"
          accesskey="]"
        >
          <?php echo $i18n['NEXT']; ?> →
        </button>
        <?php foreach ($controls as $control) {
          $controlType = self::toString($control['type'] ?? 'secondary', 'secondary');
          $controlClass = 'datagrid_control';
          if ('primary' === $controlType) {
            $controlClass .= ' datagrid_control_primary';
          }
        ?>
          <button
            type="button"
            class="<?php echo $this->escape($controlClass); ?>"
            data-action="<?php echo $this->escape(self::toString($control['action'] ?? '')); ?>"
          >
            <?php echo $this->escape(self::toString($control['label'] ?? $i18n['ACTION'], $i18n['ACTION'])); ?>
          </button>
        <?php } ?>
        <?php if ($controlsTrailingHtml !== '') { ?>
          <div class="datagrid_controls_trailing"><?php echo $controlsTrailingHtml; ?></div>
        <?php } ?>
      </div>

      <!-- Weekday Headers (Sun-Sat) -->
      <div class="calendar-v2-weekday-headers" aria-hidden="true">
        <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName) { ?>
          <div class="calendar-v2-weekday-header"><?php echo htmlspecialchars($dayName, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>
      </div>

      <!-- Month calendar grid with 7 columns (Sun-Sat) -->
      <?php $monthRowCount = (int) ceil(max(count($rows), 1) / 7); ?>
      <div class="datagrid_month_grid" role="grid" aria-labelledby="cal_picker_button" aria-describedby="<?php echo $this->escape($descriptionId); ?>" aria-colcount="7" aria-rowcount="<?php echo $monthRowCount; ?>">
        <?php 
        $today = date('Y-m-d');
        $lockBoundary = self::toString($this->meta['lockBoundary'] ?? '');
        $dateAriaFormat = self::toString($this->meta['dateAriaFormat'] ?? 'number', 'number');
        if ($dateAriaFormat !== 'number' && $dateAriaFormat !== 'short' && $dateAriaFormat !== 'long') {
          $dateAriaFormat = 'number';
        }
        foreach ($rows as $index => $row) {
          if ($index % 7 === 0) {
            echo '<div class="datagrid_month_row" role="row">';
          }

          $dateId = self::toString($row['id'] ?? '');
          if ('' === $dateId) {
            if ($index % 7 === 6 || $index === count($rows) - 1) {
              echo '</div>';
            }
            continue;
          }

          $isToday = ($dateId === $today);
          $isAdjacent = !empty($row['adjacent']);
          $isLocked = ('' !== $lockBoundary && $dateId < $lockBoundary);
          
          $cellClasses = 'datagrid_month_cell';
          if ($isToday) {
            $cellClasses .= ' datagrid_month_cell_today';
          }
          if ($isAdjacent) {
            $cellClasses .= ' datagrid_month_cell_adjacent';
          }
          if ($isLocked) {
            $cellClasses .= ' datagrid_month_cell_locked';
          }
          
          // Prepare work entries data for JavaScript
          $workEntries = is_array($row['work_entries'] ?? null) ? $row['work_entries'] : [];
          $workEntriesEncoded = json_encode($workEntries);
          $workEntriesJson = htmlspecialchars($workEntriesEncoded !== false ? $workEntriesEncoded : '[]', ENT_QUOTES, 'UTF-8');
          $dateAriaLabel = Strings::formatDateAria($dateId, $dateAriaFormat);
        ?>
          <div class="<?php echo $this->escape($cellClasses); ?>"<?php echo $isToday ? ' aria-current="date"' : ''; ?><?php echo $isLocked ? ' aria-disabled="true"' : ''; ?> data-id="<?php echo $this->escape($dateId); ?>" data-date="<?php echo $this->escape($dateId); ?>" data-date-aria="<?php echo $this->escape($dateAriaLabel); ?>" data-locked="<?php echo $isLocked ? '1' : '0'; ?>" data-work-entries="<?php echo $workEntriesJson; ?>">
            <div class="datagrid_month_cell_header datagrid_month_cell_header_<?php echo $this->escape($dateLabelClass); ?>" aria-hidden="true">
              <?php 
              try {
                $dt = new \DateTime($dateId);
                echo htmlspecialchars($dt->format('d'), ENT_QUOTES, 'UTF-8');
              } catch (\Exception $e) {
                echo htmlspecialchars($dateId, ENT_QUOTES, 'UTF-8');
              }
              ?>
            </div>
            <div class="datagrid_month_cell_content">
              <?php 
              // Display work entries if available
              $workEntries = self::listAssoc($row['work_entries'] ?? []);
              if (!empty($workEntries)) {
                foreach ($workEntries as $entry) {
                  // Hide only encrypted placeholders with no displayable hour fields.
                  // If explicit values exist, render immediately on first paint.
                  $hasEncryptedBlob = isset($entry['encrypted_blob'])
                    && is_string($entry['encrypted_blob'])
                    && '' !== trim($entry['encrypted_blob']);
                  $hasExplicitHours = isset($entry['hours']) || isset($entry['h'])
                    || isset($entry['regular_hours']) || isset($entry['regular']) || isset($entry['r'])
                    || isset($entry['overtime_hours']) || isset($entry['overtime']) || isset($entry['o'])
                    || isset($entry['living_out_allowance']) || isset($entry['living_out']) || isset($entry['loa']) || isset($entry['l'])
                    || isset($entry['travel_hours']) || isset($entry['travel']) || isset($entry['t']);
                  $isEncryptedPlaceholder = $hasEncryptedBlob && !$hasExplicitHours;

                  $siteName = self::toString($entry['site_name'] ?? $entry['n'] ?? '');
                  $regularRaw = $entry['regular_hours'] ?? $entry['regular'] ?? $entry['r'] ?? null;
                  $overtimeRaw = $entry['overtime_hours'] ?? $entry['overtime'] ?? $entry['o'] ?? null;
                  $hoursRaw = $entry['hours'] ?? $entry['h'] ?? 0;
                  $regularHours = self::toFloat($regularRaw ?? ((null === $overtimeRaw) ? $hoursRaw : 0));
                  $overtimeHours = self::toFloat($overtimeRaw ?? 0);
                  $livingOut = self::toFloat($entry['living_out_allowance'] ?? $entry['living_out'] ?? $entry['loa'] ?? $entry['l'] ?? 0);
                  $travelHours = self::toFloat($entry['travel_hours'] ?? $entry['travel'] ?? $entry['t'] ?? 0);
                  $siteNameForAria = '' !== trim($siteName) ? $siteName : 'Work entry';
                  
                  // Format hours to 2 decimal places, always show including zeros
                  $formatHours = static function ($h): string {
                    $num = (float) $h;
                    return self::formatCompactNumber($num);
                  };
                  $spokenMetrics = $isEncryptedPlaceholder
                    ? ['Encrypted work details are unavailable in this view']
                    : [
                        sprintf('%s regular hours', $formatHours($regularHours)),
                        sprintf('%s overtime hours', $formatHours($overtimeHours)),
                        sprintf('%s living out allowance', $formatHours($livingOut)),
                        sprintf('%s travel hours', $formatHours($travelHours)),
                      ];
                  $spokenSummary = AriaEcho::cadence($spokenMetrics, ', ');
                  $entryAria = AriaEcho::cadence(sprintf('%s on %s. %s.', $siteNameForAria, $dateAriaLabel, $spokenSummary));
              ?>
                  <div class="work work_<?php echo $this->escape($workEntryClass); ?>" aria-label="<?php echo $this->escape($entryAria); ?>">
                    <strong><?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></strong><br />
                    <?php 
                    $fields = $isEncryptedPlaceholder
                      ? ['--', '--', '--', '--']
                      : [
                          $formatHours($regularHours),
                          $formatHours($overtimeHours),
                          $formatHours($livingOut),
                          $formatHours($travelHours),
                        ];

                    echo implode('&nbsp;/&nbsp;', $fields);
                    ?>
                  </div>
              <?php
                }
              }
              // No fallback display for empty cells - leave content area blank
              ?>
            </div>
          </div>

          <?php if ($index % 7 === 6 || $index === count($rows) - 1) { ?>
            </div>
          <?php } ?>
        <?php } ?>

        <?php if (empty($rows)) { ?>
          <div class="datagrid_empty" role="status" aria-live="polite"><?php echo $i18n['DATAGRID_NO_ENTRIES_FOUND']; ?></div>
        <?php } ?>
      </div>
    </div>
    <?php

    return (string) ob_get_clean();
  }

  /**
   * Add a column to the grid.

   */
  public function addColumn(
    string $key,
    string $label,
    bool $sortable = false,
    ?string $width = null,
    ?string $align = null,
    bool $noEllipsis = false,
  ): void {
    $column = [
        'key' => $key,
        'label' => $label,
        'sortable' => $sortable,
    ];
    if (null !== $width) {
      $column['width'] = $width;
    }
    if (null !== $align) {
      $column['align'] = $align;
    }
    if ($noEllipsis) {
      $column['noEllipsis'] = true;
    }
    $this->columns[] = $column;
  }

  /**
   * Enable user-resizable columns (widths persist in localStorage via dg_col_*_w_* classes).
   */
  public function enableColumnResize(): void
  {
    $this->meta['columnResizeEnabled'] = true;
  }

  /**
   * Enable client-side row windowing for large single-page result sets.
   */
  public function enableVirtualScroll(): void
  {
    $this->meta['virtualScrollEnabled'] = true;
  }

  /**
   * Add a row action to the grid.
   */
  public function addRowAction(string $action, string $label): void
  {
    $rowActions = self::listAssoc($this->meta['rowActions'] ?? []);
    $rowActions[] = ['action' => $action, 'label' => $label];
    $this->meta['rowActions'] = $rowActions;
  }

  /**
   * Set row actions header label (empty string supported for blank heading).
   */
  public function setRowActionsHeaderLabel(string $label): void
  {
    $this->meta['rowActionsHeaderLabel'] = $label;
  }

  /**
   * Add a control to the grid.
    * @param array<string, mixed> $control
   */
  public function addControl(array $control): void
  {
    $controls = self::listAssoc($this->meta['controls'] ?? []);
    $normalized = [];
    foreach ($control as $k => $v) {
      $normalized[(string) $k] = $v;
    }
    $controls[] = $normalized;
    $this->meta['controls'] = $controls;
  }

  /**
   * Enable search with optional placeholder.
   */
  public function enableSearch(?string $placeholder = null): void
  {
    $this->meta['searchEnabled'] = true;
    $this->meta['searchPlaceholder'] = $placeholder ?? 'Search…';
  }

  /**
   * Enable sorting.
   */
  public function enableSorting(): void
  {
    $this->meta['sortingEnabled'] = true;
  }

  /**
   * Set item label (singular/plural).
   */
  public function setItemLabel(string $label): void
  {
    $this->meta['itemLabel'] = $label;
  }

  /**
   * Toggle a minimal visual style for contexts that need less framing.
   */
  public function setNoChrome(bool $enabled = true): void
  {
    $this->meta['noChrome'] = $enabled;
  }

  /**
   * Set the search input value.
   */
  public function setSearchValue(string $value): void
  {
    $this->meta['search'] = $value;
  }

  // ...existing code...
  /**
   * Static factory method to create a DataGrid instance.
   */
  public static function create(string $id, string $title): self
  {
    $config = [
        'id' => $id,
        'meta' => ['title' => $title],
        'columns' => [],
        'rows' => [],
    ];

    return new self($config);
  }

  /**
   * Handles render operation.
   */
  public function render(): string
  {
    return $this->table();
  }

  /**
   * Handles escape operation.
   */
  private function escape(string $value): string
  {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
}


