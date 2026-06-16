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
  private const MAX_COLUMN_CLASS_COUNT = 12;
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
    if (defined('USER_LOCALE')) {
      $locale = trim((string) USER_LOCALE);
      if ($locale !== '') {
        return $locale;
      }
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
      'DATAGRID_COLUMN_VISIBILITY_ARIA',
      'ACTION',
      'SEARCH',
      'DATAGRID_NO_ENTRIES_FOUND',
    ];
    foreach ($i18nKeys as $key) {
      $i18n[$key] = Strings::i18n($key);
    }

    $pagerInstance = null;
    if (null !== $pager) {
      if (is_array($pager) && isset($pager['page'])) {
        $page = self::toInt($pager['page'], 1);
      } elseif (is_object($pager) && method_exists($pager, 'getPage')) {
        $page = self::toInt($pager->getPage(), 1);
      }

      if ($pager instanceof PagerInterface) {
        $pagerInstance = $pager;
      }

      if (is_object($pager) && method_exists($pager, 'getRows')) {
        $pagerRows = $pager->getRows();
        if (is_array($pagerRows)) {
          $rows = self::listAssoc($pagerRows);
        }
      }
    }

    $totalPages = $pagerInstance instanceof PagerInterface
      ? $pagerInstance->getTotalPages()
      : self::toInt($this->meta['totalPages'] ?? 1, 1);

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
    $toolbarLayout = self::toString($this->meta['toolbarLayout'] ?? '', '');
    $mergedSearchPaginationToolbar = 'search_pagination' === $toolbarLayout
      && !empty($this->meta['searchEnabled']);
    
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

    ob_start();
    ?>
    <?php $columnVisibilityEnabled = !empty($this->meta['columnVisibilityEnabled']); ?>
    <div id="<?php echo $this->escape($this->id); ?>" class="datagrid <?php echo $this->escape($columnClass); ?> <?php echo $this->escape($layoutClass.$chromeClass); ?>" data-grid="<?php echo $this->escape($this->id); ?>" data-page="<?php echo $page; ?>" data-total-pages="<?php echo $totalPages; ?>" data-year="<?php echo $this->escape(self::toString($this->meta['year'] ?? date('Y'))); ?>" data-month="<?php echo $this->escape(self::toString($this->meta['month'] ?? date('m'))); ?>" data-autofocus="<?php echo $this->escape(self::toString($this->meta['autofocus'] ?? 'current')); ?>" data-date-label-position="<?php echo $this->escape(self::toString($this->meta['dateLabelPosition'] ?? 'left')); ?>" data-work-entry-position="<?php echo $this->escape(self::toString($this->meta['workEntryPosition'] ?? 'left')); ?>"<?php echo $columnVisibilityEnabled ? ' data-column-visibility="1"' : ''; ?> role="region" aria-label="<?php echo $this->escape(self::toString($this->meta['title'] ?? $this->id, $i18n['DATAGRID_DATA_GRID'])); ?>"<?php echo '' !== $descriptionId ? ' aria-describedby="' . $this->escape($descriptionId) . '"' : ''; ?>>
      <?php if (!empty($controls) || (!empty($this->meta['searchEnabled']) && !$mergedSearchPaginationToolbar)) { ?>
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

        <?php if (!empty($this->meta['searchEnabled']) && !$mergedSearchPaginationToolbar) { ?>
          <input
            type="search"
            class="datagrid_search"
            placeholder="<?php echo $this->escape(self::toString($this->meta['searchPlaceholder'] ?? $i18n['SEARCH'], $i18n['SEARCH'])); ?>"
            value="<?php echo $this->escape(self::toString($this->meta['search'] ?? '')); ?>"
          >
        <?php } ?>
      </div>
      <?php } ?>

      <?php if ($mergedSearchPaginationToolbar) {
        echo $this->renderMergedSearchPaginationToolbar($pagerInstance);
      } else {
        echo $this->renderPagination($pagerInstance, 'top');
      } ?>

      <?php if ($columnVisibilityEnabled) {
        $toggleableColumns = array_values(array_filter(
          $this->columns,
          static fn (array $column): bool => !empty($column['toggleable']) && '' !== self::toString($column['key'] ?? ''),
        ));
        if ($toggleableColumns !== []) {
          $columnVisibilityMode = self::toString($this->meta['columnVisibilityMode'] ?? 'strip', 'strip');
          $columnVisibilityAria = $i18n['DATAGRID_COLUMN_VISIBILITY_ARIA'];
          if ('menu' === $columnVisibilityMode) {
            $menuId = $this->id . '_column_menu_panel';
      ?>
        <div class="datagrid_column_menu">
          <button
            type="button"
            class="datagrid_column_menu_toggle"
            aria-expanded="false"
            aria-controls="<?php echo $this->escape($menuId); ?>"
          >
            <?php echo $this->escape(Strings::i18n('VIEW')); ?>
          </button>
          <div
            id="<?php echo $this->escape($menuId); ?>"
            class="datagrid_column_menu_panel"
            role="group"
            aria-label="<?php echo $this->escape($columnVisibilityAria); ?>"
            hidden
          >
            <?php foreach ($toggleableColumns as $column) {
              $columnKey = self::toString($column['key'] ?? '');
              $columnLabel = self::toString($column['label'] ?? $columnKey);
              $defaultVisible = !array_key_exists('defaultVisible', $column) || !empty($column['defaultVisible']);
              $toggleId = $this->id . '_col_toggle_' . preg_replace('/[^a-z0-9]+/', '_', strtolower($columnKey));
            ?>
              <label class="datagrid_column_toggle" for="<?php echo $this->escape($toggleId); ?>">
                <input
                  type="checkbox"
                  class="datagrid_column_toggle_input"
                  id="<?php echo $this->escape($toggleId); ?>"
                  data-col-key="<?php echo $this->escape($columnKey); ?>"
                  <?php echo $defaultVisible ? 'checked' : ''; ?>
                >
                <span class="datagrid_column_toggle_label"><?php echo $this->escape($columnLabel); ?></span>
              </label>
            <?php } ?>
            <span class="datagrid_column_strip_status visually_hidden" role="status" aria-live="polite" aria-atomic="true"></span>
          </div>
        </div>
      <?php } else { ?>
        <div
          class="datagrid_column_strip"
          role="group"
          aria-label="<?php echo $this->escape($columnVisibilityAria); ?>"
        >
          <?php foreach ($toggleableColumns as $column) {
            $columnKey = self::toString($column['key'] ?? '');
            $columnLabel = self::toString($column['label'] ?? $columnKey);
            $defaultVisible = !array_key_exists('defaultVisible', $column) || !empty($column['defaultVisible']);
            $toggleId = $this->id . '_col_toggle_' . preg_replace('/[^a-z0-9]+/', '_', strtolower($columnKey));
          ?>
            <label class="datagrid_column_toggle" for="<?php echo $this->escape($toggleId); ?>">
              <input
                type="checkbox"
                class="datagrid_column_toggle_input"
                id="<?php echo $this->escape($toggleId); ?>"
                data-col-key="<?php echo $this->escape($columnKey); ?>"
                <?php echo $defaultVisible ? 'checked' : ''; ?>
              >
              <span class="datagrid_column_toggle_label"><?php echo $this->escape($columnLabel); ?></span>
            </label>
          <?php } ?>
          <span class="datagrid_column_strip_status visually_hidden" role="status" aria-live="polite" aria-atomic="true"></span>
        </div>
      <?php
          }
        }
      } ?>

      <div class="datagrid_table" role="grid" aria-colcount="<?php echo $totalColumnCount; ?>" aria-rowcount="<?php echo $rowCount + 1; ?>">
        <div class="datagrid_header_row" role="rowgroup">
          <div class="datagrid_header_content" role="row">
            <?php foreach ($this->columns as $columnIndex => $column) {
              $isSortable = !empty($column['sortable']);
              $columnKey = self::toString($column['key'] ?? '');
              $columnLabel = self::toString($column['label'] ?? '');
              $columnHeaderId = $this->id.'_col_'.($columnIndex + 1);
              $columnAlign = self::toString($column['align'] ?? '');
              $headingClass = 'datagrid_heading';
              if ('' !== $columnKey) {
                $headingClass .= ' datagrid_col_' . preg_replace('/[^a-z0-9]+/', '_', strtolower($columnKey));
              }
              if ('' !== $columnAlign) {
                $headingClass .= ' datagrid_align_' . $columnAlign;
              }
            ?>
              <div class="<?php echo $this->escape($headingClass); ?>" role="columnheader" id="<?php echo $this->escape($columnHeaderId); ?>" data-col-key="<?php echo $this->escape($columnKey); ?>">
                <?php if ($isSortable) { ?>
                  <button type="button" class="datagrid_sort" data-column="<?php echo $this->escape($columnKey); ?>">
                    <?php echo $this->escape($columnLabel); ?>
                  </button>
                <?php } else { ?>
                  <?php echo $this->escape($columnLabel); ?>
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
                  $columnLabel = self::toString($column['label'] ?? '');
                  $value = ('' !== $columnKey) ? ($row[$columnKey] ?? '') : '';
                  $columnHeaderId = $this->id.'_col_'.($columnIndex + 1);
                  $columnAlign = self::toString($column['align'] ?? '');
                  $itemClass = 'datagrid_item';
                  if ('' !== $columnKey) {
                    $itemClass .= ' datagrid_col_' . preg_replace('/[^a-z0-9]+/', '_', strtolower($columnKey));
                  }
                  if ('' !== $columnAlign) {
                    $itemClass .= ' datagrid_align_' . $columnAlign;
                  }

                  // Apply compute function if provided
                  $compute = $column['compute'] ?? null;
                  if (is_callable($compute)) {
                    $value = $compute($row, $column);
                  }
                ?>
                  <div class="<?php echo $this->escape($itemClass); ?>" role="gridcell" aria-labelledby="<?php echo $this->escape($columnHeaderId); ?>" data-col-key="<?php echo $this->escape($columnKey); ?>" data-col-label="<?php echo $this->escape($columnLabel); ?>">
                    <?php if (!empty($column['rawHtml'])) { ?>
                      <?php echo self::toString($value); ?>
                    <?php } else { ?>
                      <?php echo $this->escape(self::toString($value)); ?>
                    <?php } ?>
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

      <?php if (!$mergedSearchPaginationToolbar) {
        echo $this->renderPagination($pagerInstance, 'bottom');
      } ?>
    </div>
    <?php

    return (string) ob_get_clean();
  }

  /**
   * @return array{start: int, end: int, total: int, rangeLabel: string, previousLabel: string, nextLabel: string}|null
   */
  private function paginationContext(?PagerInterface $pager): ?array
  {
    if (!$pager instanceof PagerInterface) {
      return null;
    }

    $start = $pager->getTotal() === 0 ? 0 : (($pager->getPage() - 1) * $pager->getPageSize()) + 1;
    $end = min($pager->getPage() * $pager->getPageSize(), $pager->getTotal());
    $total = $pager->getTotal();
    $itemLabel = self::toString(
      $this->meta['itemLabel'] ?? Strings::i18n('DATAGRID_ITEM_LABEL_ENTRIES'),
      Strings::i18n('DATAGRID_ITEM_LABEL_ENTRIES'),
    );

    return [
      'start' => $start,
      'end' => $end,
      'total' => $total,
      'rangeLabel' => sprintf(Strings::i18n('DATAGRID_PAGINATION_RANGE'), $start, $end, $total, $itemLabel),
      'previousLabel' => Strings::i18n('PREVIOUS'),
      'nextLabel' => Strings::i18n('NEXT'),
    ];
  }

  /**
   * Render search, range info, and prev/next on a single toolbar row.
   */
  private function renderMergedSearchPaginationToolbar(?PagerInterface $pager): string
  {
    $i18nSearch = Strings::i18n('SEARCH');
    $pagination = $this->paginationContext($pager);
    $arrowsOnly = !empty($this->meta['paginationArrowsOnly']);

    ob_start();
    ?>
    <div
      class="datagrid_toolbar datagrid_toolbar_search_pagination"
      role="navigation"
      aria-label="<?php echo $this->escape(Strings::i18n('DATAGRID_DATA_GRID')); ?>"
      <?php if (null !== $pagination && $pager instanceof PagerInterface) { ?>
      data-pagination-start="<?php echo $pagination['start']; ?>"
      data-pagination-end="<?php echo $pagination['end']; ?>"
      data-pagination-total="<?php echo $pagination['total']; ?>"
      <?php } ?>
    >
      <div class="datagrid_toolbar_start">
        <input
          type="search"
          class="datagrid_search"
          placeholder="<?php echo $this->escape(self::toString($this->meta['searchPlaceholder'] ?? $i18nSearch, $i18nSearch)); ?>"
          value="<?php echo $this->escape(self::toString($this->meta['search'] ?? '')); ?>"
        >
      </div>
      <?php if (null !== $pagination && $pager instanceof PagerInterface) { ?>
      <div class="datagrid_toolbar_center">
        <span class="datagrid_page datagrid_page_info" role="status"><?php echo $this->escape($pagination['rangeLabel']); ?></span>
      </div>
      <div class="datagrid_toolbar_end datagrid_pagination" role="group" aria-label="<?php echo $this->escape(Strings::i18n('DATAGRID_DATA_GRID')); ?>">
        <button
          type="button"
          class="datagrid_pagination_btn<?php echo $arrowsOnly ? ' datagrid_pagination_btn_icon' : ''; ?>"
          data-direction="prev"
          aria-label="<?php echo $this->escape($pagination['previousLabel']); ?>"
          <?php echo $pager->hasPrev() ? '' : 'disabled'; ?>
        ><?php echo $arrowsOnly ? '←' : '← ' . $this->escape($pagination['previousLabel']); ?></button>
        <button
          type="button"
          class="datagrid_pagination_btn<?php echo $arrowsOnly ? ' datagrid_pagination_btn_icon' : ''; ?>"
          data-direction="next"
          aria-label="<?php echo $this->escape($pagination['nextLabel']); ?>"
          <?php echo $pager->hasNext() ? '' : 'disabled'; ?>
        ><?php echo $arrowsOnly ? '→' : $this->escape($pagination['nextLabel']) . ' →'; ?></button>
      </div>
      <?php } ?>
    </div>
    <?php

    return (string) ob_get_clean();
  }

  /**
   * Render prev/next pagination controls when the pager spans multiple pages.
   */
  private function renderPagination(?PagerInterface $pager, string $position): string
  {
    if (!$pager instanceof PagerInterface || !$pager->hasPagination()) {
      return '';
    }

    $pagination = $this->paginationContext($pager);
    if (null === $pagination) {
      return '';
    }

    $arrowsOnly = !empty($this->meta['paginationArrowsOnly']);

    ob_start();
    ?>
    <div
      class="datagrid_pagination datagrid_pagination_<?php echo $this->escape($position); ?>"
      role="navigation"
      aria-label="<?php echo $this->escape(Strings::i18n('DATAGRID_DATA_GRID')); ?>"
      data-pagination-start="<?php echo $pagination['start']; ?>"
      data-pagination-end="<?php echo $pagination['end']; ?>"
      data-pagination-total="<?php echo $pagination['total']; ?>"
    >
      <button
        type="button"
        class="datagrid_pagination_btn<?php echo $arrowsOnly ? ' datagrid_pagination_btn_icon' : ''; ?>"
        data-direction="prev"
        aria-label="<?php echo $this->escape($pagination['previousLabel']); ?>"
        <?php echo $pager->hasPrev() ? '' : 'disabled'; ?>
      ><?php echo $arrowsOnly ? '←' : '← ' . $this->escape($pagination['previousLabel']); ?></button>
      <span class="datagrid_page datagrid_page_info" role="status"><?php echo $this->escape($pagination['rangeLabel']); ?></span>
      <button
        type="button"
        class="datagrid_pagination_btn<?php echo $arrowsOnly ? ' datagrid_pagination_btn_icon' : ''; ?>"
        data-direction="next"
        aria-label="<?php echo $this->escape($pagination['nextLabel']); ?>"
        <?php echo $pager->hasNext() ? '' : 'disabled'; ?>
      ><?php echo $arrowsOnly ? '→' : $this->escape($pagination['nextLabel']) . ' →'; ?></button>
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
    $i18nKeys = [
      'PREVIOUS',
      'NEXT',
      'ACTION',
      'DATAGRID_NO_ENTRIES_FOUND',
      'CALENDAR_WORK_ENTRY_LABEL',
      'CALENDAR_ENCRYPTED_DETAILS_UNAVAILABLE',
    ];
    $monthLanguage = trim(self::toString($this->meta['language'] ?? '', ''));
    $monthLocaleTag = trim(self::toString($this->meta['locale'] ?? '', ''));
    foreach ($i18nKeys as $key) {
      $i18n[$key] = $monthLanguage !== ''
        ? Strings::i18n($key, $monthLanguage)
        : Strings::i18n($key);
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
    $dayNameFormat = self::toString($this->meta['dayNameFormat'] ?? 'short', 'short');
    if (!in_array($dayNameFormat, ['narrow', 'short', 'long'], true)) {
      $dayNameFormat = 'short';
    }
    $dayNamePosition = self::toString($this->meta['dayNamePosition'] ?? 'middle', 'middle');
    if ('center' === $dayNamePosition) {
      $dayNamePosition = 'middle';
    }
    if (!in_array($dayNamePosition, ['left', 'middle', 'right'], true)) {
      $dayNamePosition = 'middle';
    }
    
    // Map position values to CSS class suffixes (middle -> center)
    $dateLabelClass = ('middle' === $dateLabelPosition) ? 'center' : $dateLabelPosition;
    $workEntryClass = ('middle' === $workEntryPosition) ? 'center' : $workEntryPosition;
    $dayNamePositionClass = ('middle' === $dayNamePosition) ? 'center' : $dayNamePosition;
    $dateLabelPositionClass = in_array($dateLabelClass, ['left', 'center', 'right'], true) ? $dateLabelClass : 'left';
    $dayNamePositionClass = in_array($dayNamePositionClass, ['left', 'center', 'right'], true) ? $dayNamePositionClass : 'center';
    $workEntryFieldsRaw = $this->meta['workEntryFields'] ?? null;
    $workEntryFieldsMeta = null;
    if (is_array($workEntryFieldsRaw)) {
      $workEntryFieldsMeta = [];
      foreach ($workEntryFieldsRaw as $key => $value) {
        $workEntryFieldsMeta[(string) $key] = $value;
      }
    }
    $workEntryFieldPrefs = CalendarCellDisplay::workEntryFieldPrefsFromMeta($workEntryFieldsMeta);

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
    $currentMonthName = Strings::formatLocalizedMonthYear(
      $year,
      $month,
      $monthLocaleTag !== '' ? $monthLocaleTag : null,
    );
    $currentMonthValue = sprintf('%04d-%02d', $year, $month);
    $chromeClass = !empty($this->meta['noChrome']) ? ' datagrid_no_chrome' : '';
    $descriptionId = trim(self::toString($this->meta['descriptionId'] ?? 'calendar-grid-instructions'));
    $suppressMonthNavigation = !empty($this->meta['suppressMonthNavigation']);
    $headingText = self::toString($this->meta['headingText'] ?? '', '');
    $headingId = self::toString($this->meta['headingId'] ?? '', '');
    $beforeWeekdayHeadersHtml = self::toString($this->meta['beforeWeekdayHeadersHtml'] ?? '', '');
    $compactNavigationRaw = $this->meta['compactNavigation'] ?? null;
    $compactNavigation = is_array($compactNavigationRaw) ? $compactNavigationRaw : [];
    $compactNavMode = self::toString($compactNavigation['mode'] ?? '', '');
    $compactNavPrevAction = self::toString($compactNavigation['prevAction'] ?? 'prev-range', 'prev-range');
    $compactNavNextAction = self::toString($compactNavigation['nextAction'] ?? 'next-range', 'next-range');
    $compactNavPickerAction = self::toString($compactNavigation['pickerAction'] ?? 'open-range-picker', 'open-range-picker');
    $compactNavPickerId = self::toString($compactNavigation['pickerId'] ?? '', '');
    $compactNavPickerLabel = self::toString($compactNavigation['pickerLabel'] ?? '', '');
    $compactNavPickerAria = self::toString($compactNavigation['pickerAriaLabel'] ?? $compactNavPickerLabel, $compactNavPickerLabel);
    $compactNavPrevAnchor = self::toString($compactNavigation['prevAnchor'] ?? '', '');
    $compactNavNextAnchor = self::toString($compactNavigation['nextAnchor'] ?? '', '');
    $compactNavCurrentAnchor = self::toString($compactNavigation['currentAnchor'] ?? '', '');
    $compactNavPrevAria = self::toString($compactNavigation['prevAriaLabel'] ?? '', '');
    $compactNavNextAria = self::toString($compactNavigation['nextAriaLabel'] ?? '', '');
    $gridLabelledBy = $compactNavPickerId !== '' ? $compactNavPickerId : 'cal_picker_button';
    $gridRangeAnchorAttr = $compactNavCurrentAnchor !== ''
      ? ' data-range-anchor="' . $this->escape($compactNavCurrentAnchor) . '"'
      : '';
    $gridViewModeAttr = $compactNavMode !== ''
      ? ' data-view-mode="' . $this->escape($compactNavMode) . '"'
      : '';
    $hasControlStrip = $beforeWeekdayHeadersHtml !== ''
      || $compactNavigation !== []
      || !$suppressMonthNavigation
      || $headingText !== ''
      || $controls !== []
      || $controlsTrailingHtml !== '';
    
    ?>
    <div id="<?php echo $this->escape($this->id); ?>" class="datagrid datagrid_layout_month datagrid_date_label_<?php echo $this->escape($dateLabelPositionClass); ?> datagrid_day_heading_<?php echo $this->escape($dayNamePositionClass); ?><?php echo $this->escape($chromeClass); ?>" data-grid="<?php echo $this->escape($this->id); ?>" data-page="<?php echo $page; ?>" data-year="<?php echo $this->escape((string) $year); ?>" data-month="<?php echo $this->escape((string) $month); ?>" data-autofocus="<?php echo $this->escape(self::toString($this->meta['autofocus'] ?? 'today', 'today')); ?>" data-date-label-position="<?php echo $this->escape(self::toString($this->meta['dateLabelPosition'] ?? 'left', 'left')); ?>" data-day-heading-position="<?php echo $this->escape($dayNamePosition); ?>" data-work-entry-position="<?php echo $this->escape(self::toString($this->meta['workEntryPosition'] ?? 'left', 'left')); ?>" data-lockboundary="<?php echo $this->escape(self::toString($this->meta['lockBoundary'] ?? '')); ?>"<?php echo $gridRangeAnchorAttr . $gridViewModeAttr; ?>>
      <?php if ($hasControlStrip) { ?>
      <div class="datagrid_controls">
        <?php if ($beforeWeekdayHeadersHtml !== '') { ?>
          <?php echo $beforeWeekdayHeadersHtml; ?>
        <?php } ?>
        <?php if ($compactNavigation !== []) { ?>
        <button
          type="button"
          <?php if ($compactNavPickerId !== '') { ?>id="<?php echo $this->escape($compactNavPickerId); ?>" <?php } ?>
          class="calendar-v2-month-title"
          data-action="<?php echo $this->escape($compactNavPickerAction); ?>"
          data-anchor="<?php echo $this->escape($compactNavCurrentAnchor); ?>"
          aria-label="<?php echo htmlspecialchars($compactNavPickerAria, ENT_QUOTES, 'UTF-8'); ?>"
          aria-keyshortcuts="ALT+\\"
          accesskey="\\"
        ><?php echo htmlspecialchars($compactNavPickerLabel, ENT_QUOTES, 'UTF-8'); ?></button>
        <button
          type="button"
          class="datagrid_control datagrid_control_icon"
          data-action="<?php echo $this->escape($compactNavPrevAction); ?>"
          data-anchor="<?php echo $this->escape($compactNavPrevAnchor); ?>"
          aria-label="<?php echo $this->escape($compactNavPrevAria); ?>"
          aria-keyshortcuts="[ PageUp"
          accesskey="["
        >
          <span aria-hidden="true">&lt;</span>
        </button>
        <button
          type="button"
          class="datagrid_control datagrid_control_icon"
          data-action="<?php echo $this->escape($compactNavNextAction); ?>"
          data-anchor="<?php echo $this->escape($compactNavNextAnchor); ?>"
          aria-label="<?php echo $this->escape($compactNavNextAria); ?>"
          aria-keyshortcuts="] PageDown"
          accesskey="]"
        >
          <span aria-hidden="true">&gt;</span>
        </button>
        <?php } elseif (!$suppressMonthNavigation) { ?>
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
          class="datagrid_control datagrid_control_icon"
          data-action="prev-month"
          data-month="<?php echo $prevMonth; ?>"
          data-year="<?php echo $prevYear; ?>"
          aria-label="<?php echo $this->escape($monthLanguage !== ''
            ? Strings::i18n('DATAGRID_PREVIOUS_MONTH_ARIA', $monthLanguage)
            : Strings::i18n('DATAGRID_PREVIOUS_MONTH_ARIA')); ?>"
          aria-keyshortcuts="[ PageUp"
          accesskey="["
        >
          <span aria-hidden="true">&lt;</span>
        </button>
        <button
          type="button"
          class="datagrid_control datagrid_control_icon"
          data-action="next-month"
          data-month="<?php echo $nextMonth; ?>"
          data-year="<?php echo $nextYear; ?>"
          aria-label="<?php echo $this->escape($monthLanguage !== ''
            ? Strings::i18n('DATAGRID_NEXT_MONTH_ARIA', $monthLanguage)
            : Strings::i18n('DATAGRID_NEXT_MONTH_ARIA')); ?>"
          aria-keyshortcuts="] PageDown"
          accesskey="]"
        >
          <span aria-hidden="true">&gt;</span>
        </button>
        <?php } elseif ($headingText !== '') { ?>
        <div
          <?php if ($headingId !== '') { ?>id="<?php echo $this->escape($headingId); ?>" <?php } ?>
          class="calendar-v2-month-title calendar-v2-view-heading"
        ><?php echo htmlspecialchars($headingText, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>
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
      <?php } ?>

      <!-- Weekday Headers (Sun-Sat) -->
      <div class="calendar-v2-weekday-headers calendar-v2-weekday-headers_<?php echo $this->escape($dayNamePositionClass); ?>" aria-hidden="true">
        <?php foreach (Strings::generateWeekDayLabels($monthLocaleTag !== '' ? $monthLocaleTag : null) as $dayLabel) {
          $dayName = self::toString($dayLabel[$dayNameFormat], '');
          if ($dayName === '') {
            continue;
          }
        ?>
          <div class="calendar-v2-weekday-header"><?php echo htmlspecialchars($dayName, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>
      </div>

      <!-- Month calendar grid with 7 columns (Sun-Sat) -->
      <?php $monthRowCount = (int) ceil(max(count($rows), 1) / 7); ?>
      <div class="datagrid_month_grid" role="grid" aria-labelledby="<?php echo $this->escape($gridLabelledBy); ?>" aria-describedby="<?php echo $this->escape($descriptionId); ?>" aria-colcount="7" aria-rowcount="<?php echo $monthRowCount; ?>">
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

          $cellExtraClasses = self::toString($row['cell_extra_classes'] ?? '');
          if ($cellExtraClasses !== '') {
            $cellClasses .= ' ' . $cellExtraClasses;
          }
          
          // Prepare work entries data for JavaScript
          $workEntries = is_array($row['work_entries'] ?? null) ? $row['work_entries'] : [];
          $workEntriesEncoded = json_encode($workEntries);
          $workEntriesJson = htmlspecialchars($workEntriesEncoded !== false ? $workEntriesEncoded : '[]', ENT_QUOTES, 'UTF-8');
          $dateAriaLabel = Strings::formatDateAria($dateId, $dateAriaFormat);
          $formatHours = static function ($h): string {
            $num = (float) $h;
            return self::formatCompactNumber($num);
          };
          $totalHoursValue = self::toFloat($row['total_hours'] ?? 0);
        ?>
          <div class="<?php echo $this->escape($cellClasses); ?>"<?php echo $isToday ? ' aria-current="date"' : ''; ?><?php echo $isLocked ? ' aria-disabled="true"' : ''; ?> data-id="<?php echo $this->escape($dateId); ?>" data-date="<?php echo $this->escape($dateId); ?>" data-date-aria="<?php echo $this->escape($dateAriaLabel); ?>" data-locked="<?php echo $isLocked ? '1' : '0'; ?>" data-work-entries="<?php echo $workEntriesJson; ?>"<?php echo $totalHoursValue > 0 ? ' data-total-hours="' . $this->escape($formatHours($totalHoursValue)) . '"' : ''; ?>>
            <div class="datagrid_month_cell_header datagrid_month_cell_header_<?php echo $this->escape($dateLabelPositionClass); ?>" aria-hidden="true">
              <span class="datagrid_month_cell_day datagrid_month_cell_day_<?php echo $this->escape($dateLabelPositionClass); ?>"><?php
              try {
                $dt = new \DateTime($dateId);
                echo htmlspecialchars($dt->format('d'), ENT_QUOTES, 'UTF-8');
              } catch (\Exception $e) {
                echo htmlspecialchars($dateId, ENT_QUOTES, 'UTF-8');
              }
              ?></span>
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
                  $hoursRaw = $entry['hours'] ?? $entry['h'] ?? null;
                  $regularHours = self::toFloat($regularRaw ?? ((null === $overtimeRaw) ? ($hoursRaw ?? 0) : 0));
                  $overtimeHours = self::toFloat($overtimeRaw ?? 0);
                  $entryHours = self::toFloat($hoursRaw !== null ? $hoursRaw : ($regularHours + $overtimeHours));
                  $livingOut = self::toFloat($entry['living_out_allowance'] ?? $entry['living_out'] ?? $entry['loa'] ?? $entry['l'] ?? 0);
                  $travelHours = self::toFloat($entry['travel_hours'] ?? $entry['travel'] ?? $entry['t'] ?? 0);
                  $siteNameForAria = '' !== trim($siteName) ? $siteName : $i18n['CALENDAR_WORK_ENTRY_LABEL'];
                  
                  $displayFields = CalendarCellDisplay::buildWorkEntryDisplayFields(
                    $regularHours,
                    $overtimeHours,
                    $livingOut,
                    $travelHours,
                    $workEntryFieldPrefs,
                    $isEncryptedPlaceholder,
                    static fn(float $value): string => $formatHours($value),
                    $entryHours,
                  );
                  $fields = $displayFields['fields'];
                  $spokenMetrics = $isEncryptedPlaceholder
                    ? [$i18n['CALENDAR_ENCRYPTED_DETAILS_UNAVAILABLE']]
                    : $displayFields['spokenMetrics'];
                  $spokenSummary = AriaEcho::cadence($spokenMetrics, ', ');
                  $entryLead = $spokenSummary !== ''
                    ? sprintf('%s on %s. %s.', $siteNameForAria, $dateAriaLabel, $spokenSummary)
                    : sprintf('%s on %s.', $siteNameForAria, $dateAriaLabel);
                  $entryAria = AriaEcho::cadence($entryLead);
              ?>
                  <div class="work work_<?php echo $this->escape($workEntryClass); ?>" aria-label="<?php echo $this->escape($entryAria); ?>">
                    <strong><?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></strong><?php
                    if ($fields !== []) {
                      echo '<br />' . implode('&nbsp;/&nbsp;', $fields);
                    }
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
    bool $defaultVisible = true,
    bool $toggleable = true,
    bool $rawHtml = false,
  ): void {
    $column = [
        'key' => $key,
        'label' => $label,
        'sortable' => $sortable,
        'defaultVisible' => $defaultVisible,
        'toggleable' => $toggleable,
        'rawHtml' => $rawHtml,
    ];
    if (null !== $width) {
      $column['width'] = $width;
    }
    if (null !== $align) {
      $column['align'] = $align;
    }
    $this->columns[] = $column;
  }

  /**
   * Enable the column visibility control strip between toolbar and headers.
   */
  public function enableColumnVisibility(): void
  {
    $this->meta['columnVisibilityEnabled'] = true;
  }

  /**
   * Render column visibility as either a visible strip or a compact menu.
   */
  public function setColumnVisibilityMode(string $mode): void
  {
    $normalized = strtolower(trim($mode));
    $this->meta['columnVisibilityMode'] = 'menu' === $normalized ? 'menu' : 'strip';
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
    $this->meta['searchPlaceholder'] = $placeholder ?? Strings::i18n('DATAGRID_SEARCH_PLACEHOLDER');
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
   * Merge search and top pagination into one toolbar row.
   * Layout: search (left) | range info (center) | prev/next (right).
   */
  public function setToolbarLayout(string $layout): void
  {
    $this->meta['toolbarLayout'] = $layout;
  }

  /**
   * Render prev/next pagination as arrow-only icon buttons.
   */
  public function setPaginationArrowsOnly(bool $enabled = true): void
  {
    $this->meta['paginationArrowsOnly'] = $enabled;
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
