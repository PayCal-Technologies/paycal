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
  font-size: 10px;
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
  font-size: 14px;
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
  font-size: 14px;
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
  padding: 0;
  border: none;
  background: none;
  font-size: inherit;
  font-weight: inherit;
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

.datagrid_row_content:focus-visible {
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
  font-size: 10px;
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

/* Sites-specific grid column widths */
#sites-active .datagrid_header_content,
#sites-active .datagrid_row_content,
#sites-archived .datagrid_header_content,
#sites-archived .datagrid_row_content {
  grid-template-columns: 3fr 1fr 1fr 0.8fr 1.5fr 0.8fr auto;
}
