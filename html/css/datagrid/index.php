<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
/**
 * PayCal - DataGrid Component Styles
 * Shared DataGrid component used across multiple pages (Sites, Organizations, etc.)
 * 
 * Extracted from main CSS index.php
 * Date: March 1, 2026
 */

/* DATAGRID */
.datagrid {
  font-family: var(--monospace);
  font-size: 0.6rem;
  width: 100%;
}

.datagrid_cols_1 { --datagrid_cols: 1; }
.datagrid_cols_2 { --datagrid_cols: 2; }
.datagrid_cols_3 { --datagrid_cols: 3; }
.datagrid_cols_4 { --datagrid_cols: 4; }
.datagrid_cols_5 { --datagrid_cols: 5; }
.datagrid_cols_6 { --datagrid_cols: 6; }
.datagrid_cols_7 { --datagrid_cols: 7; }
.datagrid_cols_8 { --datagrid_cols: 8; }
.datagrid_cols_9 { --datagrid_cols: 9; }
.datagrid_cols_10 { --datagrid_cols: 10; }
.datagrid_cols_11 { --datagrid_cols: 11; }

.datagrid_controls {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.datagrid_control {
  padding: 8px 12px;
  border: 1px solid transparent;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color var(--short-transition) ease, color var(--short-transition) ease;
}

.datagrid_control_primary {
  background-color: var(--button-primary-bg);
  color: var(--button-primary-text);
}

.datagrid_control_primary:hover,
.datagrid_control_primary:focus {
  background-color: var(--color-primary-hover);
}

.datagrid_control_primary:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

.datagrid_controls_end {
  display: flex;
  flex: 1;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.datagrid_search {
  flex: 1;
  min-width: 200px;
  padding: 8px 12px;
  border: 1px solid var(--btn-border);
  border-radius: 4px;
  font-size: 0.8rem;
}

.datagrid_icon_button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  width: 2rem;
  height: 2rem;
  padding: 0;
  border: 1px solid var(--btn-border);
  border-radius: 4px;
  background: var(--button-bg, transparent);
  color: inherit;
  font-size: 0.95rem;
  line-height: 1;
  cursor: pointer;
  opacity: 0.82;
  transition: background-color var(--short-transition) ease, color var(--short-transition) ease, transform var(--short-transition) ease;
}

.datagrid_icon_button:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

.datagrid_fullscreen_icon_collapse {
  display: none;
}

.datagrid_fullscreen_toggle[aria-expanded="true"] .datagrid_fullscreen_icon_expand {
  display: none;
}

.datagrid_fullscreen_toggle[aria-expanded="true"] .datagrid_fullscreen_icon_collapse {
  display: inline;
}

.datagrid.datagrid_fullscreen {
  position: fixed;
  inset: 0;
  z-index: 10100;
  box-sizing: border-box;
  width: 100%;
  max-width: none;
  height: 100%;
  margin: 0;
  padding: 12px;
  overflow: auto;
  background: var(--panel-bg, var(--bg-primary, #fff));
}

body.datagrid_fullscreen_active {
  overflow: hidden;
}

/* Fullscreen: hide sidebar shell so the grid owns the viewport. */
body.datagrid_fullscreen_active #page_header.nav_component--header,
body.datagrid_fullscreen_active .sidebar_toggle_accessible {
  display: none !important;
}

body.datagrid_fullscreen_active[data-nav-primary-position='left'] #main,
body.datagrid_fullscreen_active[data-nav-primary-position='left'] #page_footer,
body.datagrid_fullscreen_active[data-nav-primary-position='right'] #main,
body.datagrid_fullscreen_active[data-nav-primary-position='right'] #page_footer {
  margin-left: 0;
  margin-right: 0;
  width: 100%;
  max-width: 100%;
}

@media (min-width: 720px) {
  .datagrid.datagrid_fullscreen[data-virtualize="1"] .datagrid_table .datagrid_body.datagrid_virtual_scroll {
    max-height: calc(100vh - 180px);
  }
}

/* Resize-enabled grids: flex row layout so width classes + column visibility coexist (desktop). */
@media (min-width: 720px) {
  .datagrid[data-column-resize="1"] .datagrid_header_content,
  .datagrid[data-column-resize="1"] .datagrid_row_content {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
  }

  .datagrid[data-column-resize="1"] .datagrid_heading,
  .datagrid[data-column-resize="1"] .datagrid_item {
    flex: 1 1 0;
    min-width: 48px;
  }

  .datagrid[data-column-resize="1"] .datagrid_heading_actions,
  .datagrid[data-column-resize="1"] .datagrid_col_actions {
    flex: 0 0 auto;
  }
}

/* Sites grid default column flex weights (overridden per-column by dg_col_*_w_* classes). */
.datagrid[data-grid^="sites-"] .datagrid_col_site_name { flex: 3 1 14rem; min-width: 14rem; }
.datagrid[data-grid^="sites-"] .datagrid_col_wage { flex: 1 1 5rem; min-width: 5rem; }
.datagrid[data-grid^="sites-"] .datagrid_col_living_out_allowance { flex: 1 1 5rem; min-width: 5rem; }
.datagrid[data-grid^="sites-"] .datagrid_col_travel_hours { flex: 1 1 5rem; min-width: 5rem; }
.datagrid[data-grid^="sites-"] .datagrid_col_province { flex: 1 1 8rem; min-width: 8rem; }
.datagrid[data-grid^="sites-"] .datagrid_col_entries { flex: 0.75 1 4rem; min-width: 4rem; }
.datagrid[data-grid^="sites-"] .datagrid_col_budget_amount { flex: 1 1 6rem; min-width: 6rem; }

/* Table container */
.datagrid_table {
  display: grid;
  grid-template-columns: 1fr;
  width: 100%;
  row-gap: 0;
}

/* Column count helpers */
.datagrid_header_row {
  display: grid;
  grid-column: 1 / -1;
  grid-template-columns: 1fr auto;
  align-items: center;
  gap: 0;
  border-bottom: var(--border-bottom);
  background: color-mix(in srgb, var(--panel-head-back) 76%, var(--panel-border, var(--color-text)) 24%);
  box-shadow:
    0 1px 0 color-mix(in srgb, var(--panel-border, var(--color-text)) 52%, transparent),
    inset 0 -1px 0 color-mix(in srgb, var(--panel-border, var(--color-text)) 20%, transparent);
  font-weight: 700;
  color: var(--panel-head-fore);
}

.datagrid_header_content {
  display: grid;
  grid-template-columns: var(--grid-template-columns, repeat(var(--datagrid_cols, 1), minmax(0, 1fr)));
  align-items: center;
  width: 100%;
  min-width: 0;
}

.datagrid_heading {
  position: relative;
  padding: 6px 8px;
  font-size: var(--font-md);
  font-weight: 700;
  white-space: nowrap;
  min-width: 0;
}

.datagrid_heading button.datagrid_sort {
  display: block;
  width: 100%;
  padding: 0;
  border: none;
  background: none;
  font-size: inherit;
  font-weight: inherit;
  text-align: inherit;
  text-decoration: none;
  color: inherit;
  cursor: pointer;
}

.datagrid_row {
  display: grid;
  grid-column: 1 / -1;
  grid-template-columns: 1fr auto;
  align-items: center;
  gap: 0;
  min-height: 30px;
  background: transparent;
  transition: background-color 120ms ease;
}

.datagrid_row:hover > .datagrid_row_content {
  background: rgba(0, 188, 212, 0.15);
}

.datagrid_row.datagrid_row_current > .datagrid_row_content {
  background: rgba(0, 188, 212, 0.2);
}

.datagrid_row.datagrid_row_current:hover > .datagrid_row_content {
  background: rgba(0, 188, 212, 0.28);
}

/* Content wrapper - holds all field items, is tabbable and outlinable */
.datagrid_row_content {
  display: grid;
  grid-template-columns: var(--grid-template-columns, repeat(var(--datagrid_cols, 1), minmax(0, 1fr)));
  align-items: center;
  width: 100%;
  min-width: 0;
  outline: none;
  cursor: pointer;
}

.datagrid_row_content:focus-visible,
.datagrid_row:focus-visible > .datagrid_row_content {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: -2px;
}

.datagrid_item {
  padding: 6px 8px;
  font-size: var(--font-sm);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  min-width: 0;
}

/* Columns that should show full text (wrap within cell), never ellipsis */
.datagrid_item.datagrid_no_ellipsis {
  white-space: normal;
  overflow: visible;
  text-overflow: unset;
  overflow-wrap: anywhere;
}

/* Column resize handles (desktop only; disabled in mobile card layout) */
.datagrid[data-column-resize="1"] .datagrid_heading[data-col-key] {
  padding-right: 12px;
  border-right: 1px solid color-mix(in srgb, var(--panel-border, var(--color-text)) 70%, transparent);
}

.datagrid_col_resize {
  position: absolute;
  top: 0;
  right: -1px;
  width: 10px;
  height: 100%;
  cursor: col-resize;
  touch-action: none;
  user-select: none;
  z-index: 1;
}

.datagrid[data-column-resize="1"] .datagrid_col_resize::before {
  content: '';
  position: absolute;
  top: 12%;
  bottom: 12%;
  right: 4px;
  width: 2px;
  background: color-mix(in srgb, var(--panel-border, var(--color-text)) 84%, transparent);
  border-radius: 1px;
  box-shadow: -3px 0 0 color-mix(in srgb, var(--panel-border, var(--color-text)) 84%, transparent);
  pointer-events: none;
  transition: opacity 120ms ease;
}

.datagrid_col_resize:hover {
  background: var(--color-focus-ring, #0096d6);
  opacity: 0.68;
}

.datagrid_col_resize.datagrid_col_resize_active {
  background: var(--color-focus-ring, #0096d6);
  opacity: 0.85;
}

.datagrid_col_resize:hover::before,
.datagrid_col_resize.datagrid_col_resize_active::before {
  opacity: 0;
}

/* Column text alignment */
.datagrid_align_right  { text-align: right; }
.datagrid_align_center { text-align: center; }
.datagrid_align_left   { text-align: left; }

/* Header actions column - empty for alignment */
/* Shared action cell */
.datagrid_actions {
  display: flex;
  align-items: center;
  gap: 6px;
}
.datagrid_action:hover {
  opacity: 1;
}
.datagrid_action_danger:hover {
  color: #ff4444;
}

.add_member_results {
  max-height: 200px;
  margin-top: 8px;
  overflow-y: auto;
}

.add-member-result-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px;
  border-bottom: 1px solid #eee;
  cursor: pointer;
}

.member_item_row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  margin: 4px 0;
}

/* JS-generated message styles */
.loading-message {
  text-align: center;
  color: var(--text-muted);
}

.archived-summary-card {
  margin-bottom: var(--mar-md);
  padding: var(--pad-md);
  border-radius: var(--radius-cell, var(--border-radius));
  background: var(--bg-highlight);
}
.datagrid_icon_button:hover {
  opacity: 1;
  color: var(--color-primary);
  transform: scale(1.05);
}

.datagrid_icon_button_danger:hover {
  color: var(--color-danger);
}
.datagrid_popover_item:last-child {
  border-bottom: none;
  border-radius: 0 0 4px 4px;
}

.datagrid_popover_item:first-child {
  border-radius: 4px 4px 0 0;
}

.datagrid_popover_item:hover {
  background: rgba(0, 188, 212, 0.15);
}

.datagrid_popover_item_danger:hover {
  background: rgba(198, 40, 40, 0.15);
  color: var(--color-danger);
}

.datagrid_pagination_top,
.datagrid_pagination_bottom {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin: 8px 0;
}

.datagrid_pagination_top {
  margin-top: 0;
}

.datagrid_pagination_bottom {
  margin-bottom: 0;
}

.datagrid_pagination_info {
  margin: 0;
  font-size: 0.75rem;
  color: var(--panel-text);
}

.datagrid_pagination_nav {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
}

.datagrid_pagination_btn {
  padding: 6px 10px;
  border: 1px solid var(--btn-border);
  border-radius: 4px;
  background: var(--button-bg, transparent);
  color: inherit;
  font-size: 0.75rem;
  cursor: pointer;
}

.datagrid_pagination_btn:hover:not(:disabled) {
  background-color: var(--button-bg-active);
}

.datagrid_pagination_btn:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

.datagrid_pagination_btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.datagrid_pagination_page_active {
  font-weight: 700;
  border-color: var(--color-primary, #0096d6);
}

.datagrid_pagination_ellipsis {
  padding: 0 4px;
  font-size: 0.75rem;
  color: var(--panel-text);
  opacity: 0.7;
}

/* Virtual row windowing (desktop; disabled on mobile card layout). */
@media (min-width: 720px) {
  .datagrid[data-virtualize="1"] .datagrid_table .datagrid_body.datagrid_virtual_scroll {
    max-height: min(60vh, 720px);
    overflow-y: auto;
    overflow-x: hidden;
  }

  .datagrid_virtual_phantom {
    height: 30px;
    pointer-events: none;
    visibility: hidden;
  }
}
.datagrid_empty {
  padding: 12px;
  font-size: 0.6rem;
  text-align: center;
  color: var(--panel-text);
  opacity: 0.6;
}

/* Optional low-chrome variant for dense surfaces where borders feel heavy. */
.datagrid.datagrid_no_chrome .datagrid_header_row {
  border-bottom: 0;
  background: transparent;
  box-shadow: none;
}

.datagrid.datagrid_no_chrome[data-column-resize="1"] .datagrid_heading[data-col-key] {
  border-right: 0;
}

.datagrid.datagrid_no_chrome .datagrid_row:hover > .datagrid_row_content {
  background: transparent;
}

/* ==========================================================================
   Mobile card layout  (<= 719 px)
   Hides the column header row visually (kept for screen-reader labelledby)
   and stacks each row's cells as labelled flex rows inside a card.
   Page-specific ::before labels are defined in the page CSS file.
   ========================================================================== */
@media (max-width: 719px) {
  .datagrid[data-column-resize="1"] .datagrid_col_resize {
    display: none;
  }

  /* Visually hide the header row; keep it accessible for aria-labelledby. */
  .datagrid .datagrid_header_row {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }

  /* Switch the content wrapper from grid to vertical flex. */
  .datagrid .datagrid_row_content {
    display: flex;
    flex-direction: column;
    padding: 0.5rem;
  }

  /* Each cell becomes a horizontal label / value pair. */
  .datagrid .datagrid_item {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.2rem 0;
    white-space: normal;
    overflow: visible;
    text-overflow: unset;
  }
}

<?php
/** @var list<string> $dgResizeColumnKeys */
$dgResizeColumnKeys = [
  'full_name',
  'email',
  'role',
  'status',
  'joined_at',
  'ytd_gross',
  'total_hours',
  'reg_hours',
  'ot_hours',
  'trailing_baseline',
  'site_name',
  'wage',
  'living_out_allowance',
  'travel_hours',
  'province',
  'entries',
  'budget_amount',
];
$dgResizeMinPx = 48;
$dgResizeMaxPx = 800;
$dgResizeSnapPx = 8;

echo "/* DataGrid column resize width classes (8px snap; toggled by JS). */\n";
foreach ($dgResizeColumnKeys as $dgColumnKey) {
  $dgClassKey = preg_replace('/[^a-z0-9]+/', '_', strtolower($dgColumnKey)) ?? '';
  if ($dgClassKey === '') {
    continue;
  }

  for ($dgWidthPx = $dgResizeMinPx; $dgWidthPx <= $dgResizeMaxPx; $dgWidthPx += $dgResizeSnapPx) {
    printf(
      ".datagrid.dg_col_%s_w_%d .datagrid_col_%s { flex: 0 0 %dpx; width: %dpx; max-width: %dpx; min-width: %dpx; }\n",
      $dgClassKey,
      $dgWidthPx,
      $dgClassKey,
      $dgWidthPx,
      $dgWidthPx,
      $dgWidthPx,
      $dgWidthPx,
    );
  }
}
?>
