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
 * Shared DataGrid component used across multiple pages (Sites, Businesses, etc.)
 * 
 * Extracted from main CSS index.php
 * Date: March 1, 2026
 */

/* DATAGRID */
.datagrid {
  font-family: var(--monospace);
  font-size: 0.6rem;
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
.datagrid_cols_12 { --datagrid_cols: 12; }

.datagrid_container {
  min-height: 12rem;
}

.datagrid_container > .datagrid_body {
  min-height: inherit;
}

.datagrid_container_loading {
  min-height: 15rem;
}

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

.datagrid_search {
  flex: 1;
  min-width: 200px;
  padding: 8px 12px;
  border: 1px solid var(--btn-border);
  border-radius: 4px;
  font-size: 0.8rem;
}

.datagrid_toolbar_search_pagination {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.datagrid_toolbar_search_pagination .datagrid_toolbar_start {
  flex: 0 1 280px;
  min-width: 200px;
}

.datagrid_toolbar_search_pagination .datagrid_toolbar_center {
  flex: 1 1 auto;
  text-align: center;
}

.datagrid_toolbar_search_pagination .datagrid_toolbar_end {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  gap: 6px;
  margin-left: auto;
}

.datagrid_toolbar_search_pagination .datagrid_search {
  width: 100%;
  flex: none;
}

.datagrid_toolbar_search_pagination .datagrid_page_info {
  font-size: 0.8rem;
  white-space: nowrap;
}

.datagrid_column_strip {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  margin: 0 0 10px;
  padding: 8px 10px;
  border: 1px solid var(--btn-border);
  border-radius: 6px;
  background: var(--panel-head-back, rgba(0, 0, 0, 0.03));
}

.datagrid_column_menu {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.datagrid_column_menu_toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.28rem 0.55rem;
  font-size: 0.76rem;
  white-space: nowrap;
}

.datagrid_column_menu_panel {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  z-index: 5;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-width: 11rem;
  max-width: min(18rem, 90vw);
  padding: 0.55rem 0.65rem;
  border: 1px solid var(--btn-border);
  border-radius: 8px;
  background: var(--panel-bg, #fff);
  box-shadow: var(--depth-surface-shadow, 0 8px 24px rgba(0, 0, 0, 0.16));
}

.datagrid_column_menu_panel[hidden] {
  display: none !important;
}

.datagrid_column_menu_panel .datagrid_column_toggle {
  display: flex;
  width: 100%;
  border-radius: 6px;
}

.datagrid_column_toggle {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  border: 1px solid var(--btn-border);
  border-radius: 999px;
  background: var(--button-bg, #fff);
  font-size: 0.72rem;
  cursor: pointer;
  user-select: none;
}

.datagrid_column_toggle_input {
  margin: 0;
}

.datagrid_column_toggle_label {
  white-space: nowrap;
}

.datagrid_col_hidden {
  display: none !important;
}

.datagrid_loading {
  pointer-events: none;
}

.datagrid_skeleton_toolbar {
  display: grid;
  grid-template-columns: minmax(10rem, 18rem) minmax(6rem, 1fr) 2.4rem 2.4rem;
  gap: 12px;
  align-items: center;
  min-height: 2.5rem;
  margin-bottom: 12px;
}

.datagrid_skeleton_search,
.datagrid_skeleton_page,
.datagrid_skeleton_button,
.datagrid_skeleton_heading,
.datagrid_skeleton_cell {
  min-height: 0.85rem;
}

.datagrid_skeleton_button {
  min-height: 2rem;
}

.datagrid_skeleton_heading,
.datagrid_skeleton_cell {
  margin: 6px 8px;
}

.datagrid_skeleton_row .datagrid_row_content {
  min-height: 30px;
}

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
  background: var(--panel-head-back);
  font-weight: 600;
  color: var(--panel-head-fore);
}

.datagrid_header_content {
  display: grid;
  grid-template-columns: var(--grid-template-columns, repeat(var(--datagrid_cols, 1), 1fr));
  align-items: center;
}

.datagrid_heading {
  padding: 6px 8px;
  font-size: var(--font-md);
  font-weight: 600;
  white-space: nowrap;
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

/* Content wrapper - holds all field items, is tabbable and outlinable */
.datagrid_row_content {
  display: grid;
  grid-template-columns: var(--grid-template-columns, repeat(var(--datagrid_cols, 1), 1fr));
  align-items: center;
  outline: none;
  cursor: pointer;
}

.datagrid_row_content:focus-visible,
.datagrid_row:focus-visible > .datagrid_row_content,
.datagrid_row_keyboard_active > .datagrid_row_content {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: -2px;
}

.datagrid_item {
  padding: 6px 8px;
  font-size: var(--font-sm);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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

.datagrid_pagination,
.datagrid_pagination_top,
.datagrid_pagination_bottom {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 12px;
}

.datagrid_pagination_btn {
  padding: 6px 12px;
  border: 1px solid var(--btn-border);
  border-radius: 4px;
  background: var(--button-bg, #fff);
  font-size: 0.8rem;
  cursor: pointer;
}

.datagrid_pagination_btn_icon {
  min-width: 2.25rem;
  padding: 6px 10px;
  font-size: 0.95rem;
  line-height: 1;
}

.datagrid_pagination_top,
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
