<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
/**
 * PayCal - Calendar Page Styles
 * 
 * Extracted from main CSS index.php
 * Date: March 1, 2026
 */

/* CALENDAR DATE PICKER & MODAL */
#modal_cal_picker {
  width: min(980px, 90vw);
  max-width: 90vw;
  max-height: 90vh;
  padding: 0;
  font-family: var(--sans-serif);
  font-weight: 700;
  overflow: hidden;
}

#month_nav_prev:hover, #month_nav_next:hover, #cal_picker_button:hover,
#month_nav_prev:focus, #month_nav_next:focus, #cal_picker_button:focus,
#month_nav_prev:active, #month_nav_next:active, #cal_picker_button:active
{
  background-color: var(--panel-text);
  color: var(--panel-bg);
  transition: background-color var(--short-transition) ease;
}

#month_nav_prev:focus-visible, #month_nav_next:focus-visible, #cal_picker_button:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

#cal_menu_left {
  display: flex;
  flex-direction: column;
  flex-grow: 1;
  align-items: stretch;
  justify-content: flex-start;
  gap: var(--gap-sm, 0.5rem);
  flex: 1;
  width: 20%;
  height: 100%;
  padding: var(--pad-sm, 0.5rem);
  border-right: var(--border-size) solid var(--border-inset-color);
}

#cal_year_input.date_picker_year_input {
  width: 100%;
  margin: 0;
  padding: var(--pad-sm);
  border: var(--border-size) solid var(--button-border);
  border-radius: var(--radius-control, var(--border-radius));
  background-color: var(--panel-bg);
  color: var(--panel-text);
}

#cal_menu_right {
  display: grid;
  flex: 2;
  grid-template-columns: repeat(3, 1fr);
  grid-template-rows: repeat(4, 1fr);
  gap: 2px;
  padding: 1px;
}

button.cal_menu_years, button.cal_menu_months {
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background-color: transparent;
  text-align: center;
  cursor: pointer;
}

button.cal_menu_years {
  align-items: center;
  width: 100%;
  padding: var(--pad-sm);
  text-align: center;
  color: var(--color-text);
}

button.cal_menu_months {
  padding: var(--pad-sm);
  text-align: center;
  color: var(--color-text);
}

button.cal_menu_years:hover, button.cal_menu_months:hover {
  background-color: var(--color-text);
  color: var(--color-text-inverse);
  transition: background-color var(--short-transition) ease;
}

.date_picker_shortcuts {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.75rem;
  width: 100%;
  margin-top: 0.5rem;
  font-size: var(--font-sm);
  color: var(--color-text);
  opacity: 0.8;
}

#modal_cal_picker .modal_footer {
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

#modal_cal_picker .date_picker_actions {
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
}

#modal_cal_picker .date_picker_actions .btn {
  flex: 0 0 auto;
  min-width: 7.5rem;
  margin: 0;
}

#modal_cal_picker .date_picker_shortcuts {
  width: 100%;
  margin-top: 0;
}

.date_picker_shortcuts span {
  white-space: nowrap;
}

.calendar_user_selector {
  margin-bottom: var(--mar-md, 0.75rem);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: nowrap;
  width: 100%;
}

.calendar_user_selector_form {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
  flex: 1 1 auto;
}

.calendar_user_selector #calendar_user_lookup {
  min-width: 0;
  width: 100%;
}

.calendar_user_clear {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  min-width: 2.25rem;
  padding: 0;
  white-space: nowrap;
}

@media (max-width: 720px) {
  .calendar_user_selector {
    flex-wrap: wrap;
  }

  .calendar_user_selector_form {
    width: 100%;
  }
}

.date_picker_shortcuts kbd {
  font-family: var(--monospace);
}

button.cal_menu_selected {
  border: var(--border-size) solid var(--button-primary-bg);
  background-color: var(--button-primary-bg);
  font-weight: bold;
  color: var(--button-primary-text);
  box-shadow: inset 0 0 0 1px var(--button-primary-text);
}

button.cal_menu_selected:hover,
button.cal_menu_selected:focus,
button.cal_menu_selected:active {
  color: var(--button-primary-text);
  background-color: var(--button-primary-bg);
}

button.cal_menu_selected:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

/* CALENDAR */
.week {
  display: flex;
  max-width: 100%;
  margin: 0;
  padding: 0;
}
.calendar_day:hover {
  background: var(--calendar-day-hover, var(--button-primary-bg));
  color: var(--button-primary-text);
  box-shadow: var(--cal-day-hover-glow, 0 0 1px 5px rgba(128, 128, 128, 1));
  cursor: pointer;
}

.calendar_header {
  display: block;
  flex: 1;
  width: 14.2857%;
  max-width: 14.2857%;
  height: 2rem;
  max-height: 2rem;
  margin-top: var(--mar-sm);
  padding: 0 0 0 var(--pad-sm);
  border: none;
  background-color: var(--color-surface-strong);
  font-family: var(--sans-serif);
  font-size: var(--font-sm);
  font-weight: 400;
  text-align: center;
  white-space: nowrap;
  color: var(--color-text);
}

.day_label {
  display: none;
  margin: 0;
  padding: 0;
  font-family: var(--sans-serif);
  font-size: var(--font-sm);
  font-weight: 400;
  color: var(--color-text);
  visibility: hidden;
}

.day_number {
  display: block;
  margin: 0;
  padding: var(--pad-xs);
  font-family: var(--sans-serif);
  font-size: var(--font-md);
  font-weight: 700;
  color: var(--cal-day-fore);
}
.cal_day:focus-visible {
  border: 2px solid var(--color-focus-ring);
  outline: 2px solid var(--color-focus-ring);
}

.work {
  display: block;
  width: auto;
  max-width: 100%;
  margin: 1px 0 0 0;
  padding: 0px;
  border: var(--border-size) solid var(--calendar-border);
  border-radius: var(--radius-cell, var(--border-radius));
  background: var(--work-back);
  font-family: var(--sans-serif);
  font-size: var(--font-sm);
  font-weight: 400;
  line-height: var(--font-lg);
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--work-fore);
  overflow-x: hidden;
  animation: slide var(--zero-transition) forwards;
}

.work_row {
  display: flex;
  flex-direction: row;
  justify-content: space-evenly;
  margin: 0 var(--mar-md);
}

.work_row span { margin: 0 var(--mar-md); }

#calendar_day_context_menu {
  position: absolute;
  top: var(--pad-xs);
  left: var(--pad-xs);
  z-index: 9000;
  display: block;
  width: 12rem;
  margin: 0;
  padding: var(--pad-sm);
  border-radius: var(--radius-panel, var(--border-radius));
  background-color: var(--panel-bg);
  backdrop-filter: blur(var(--blur-size));
  box-shadow: 0 0.1rem 0.1rem rgba(0, 0, 0, 0.5);
}

#calendar_day_context_menu.hidden {
  display: none;
}

.calendar_day.context-menu-anchor {
  position: relative;
}

#calendar_day_context_menu.context-menu-align-right {
  right: var(--pad-xs);
  left: auto;
}

#calendar_day_context_menu.context-menu-align-top {
  top: auto;
  bottom: var(--pad-xs);
  transform: translateY(calc(-100% - var(--pad-xs)));
}

#calendar_day_context_menu_head {
  justify-content: center;
}

#calendar_day_context_menu ul {
  display: flex;
  flex-direction: column;
  width: 100%;
  min-width: 100%;
  max-width: 100%;
  margin: 0;
  padding: 0;
  list-style: none;
}

#calendar_day_context_menu li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  min-width: 100%;
  max-width: 100%;
  margin: var(--mar-xs) 0;
  padding: var(--pad-sm) 0 0 var(--pad-sm);
  cursor: pointer;
}

#calendar_day_context_menu li:not([aria-disabled="true"]):hover,
#calendar_day_context_menu li:not([aria-disabled="true"]):focus {
  border-radius: var(--radius-cell, var(--border-radius));
  background-color: var(--button-primary-bg);
  color: var(--button-primary-text);
  transition: background-color var(--short-transition) ease;
}

#calendar_day_context_menu li:not([aria-disabled="true"]):focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: -2px;
}

#calendar_day_context_menu li[aria-disabled="true"] {
  opacity: 0.55;
  cursor: not-allowed;
}

#calendar_day_context_menu kbd {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  min-width: 3.5rem;
  justify-content: flex-end;
  padding: 0.1rem 0.3rem;
  border: none;
  background-color: transparent;
  font-family: var(--monospace);
  color: var(--color-text);
}

#calendar_day_context_menu li:not([aria-disabled="true"]):focus kbd {
  color: var(--color-bg);
}

#calendar_day_context_menu .calendar_shortcut_mod {
  display: inline-flex;
  align-items: center;
}

#calendar_day_context_menu .calendar_shortcut_icon {
  width: 1rem;
  height: 1rem;
}

#calendar_day_context_menu .calendar_shortcut_icon_mac,
#calendar_day_context_menu .calendar_shortcut_icon_win {
  display: none;
}

#calendar_day_context_menu .calendar_shortcut_sep {
  opacity: 0.7;
}

#calendar_day_context_menu .calendar_shortcut_key {
  line-height: 1;
}

[data-os="mac"] #calendar_day_context_menu .calendar_shortcut[data-shortcut-modifier="primary"] .calendar_shortcut_icon_mac,
[data-os="ios"] #calendar_day_context_menu .calendar_shortcut[data-shortcut-modifier="primary"] .calendar_shortcut_icon_mac {
  display: inline-block;
}

[data-os="win"] #calendar_day_context_menu .calendar_shortcut[data-shortcut-modifier="primary"] .calendar_shortcut_icon_win,
[data-os="linux"] #calendar_day_context_menu .calendar_shortcut[data-shortcut-modifier="primary"] .calendar_shortcut_icon_win,
[data-os="android"] #calendar_day_context_menu .calendar_shortcut[data-shortcut-modifier="primary"] .calendar_shortcut_icon_win,
[data-os="unknown"] #calendar_day_context_menu .calendar_shortcut[data-shortcut-modifier="primary"] .calendar_shortcut_icon_win {
  display: inline-block;
}

[data-os="ios"] #calendar_day_context_menu .calendar_shortcut,
[data-os="android"] #calendar_day_context_menu .calendar_shortcut {
  display: none;
}

.calendar-debug-ok {
  background: #0f0;
}

.calendar-debug-error {
  background: #f00;
}

/* CALENDAR DAY CELLS */
.calendar_day {
  display: flex;
  flex-direction: column;
  width: 14.2857%;
  max-width: 14.2857%;
  min-height: calc(100dvh / 10);
  margin: var(--cal-day-margin, var(--mar-sm) var(--gap-md));
  padding: var(--cal-day-padding, var(--pad-sm));
  border: var(--border-size) solid var(--calendar-border);
  background-color: var(--calendar-day-bg);
  cursor: pointer;
}

/* CALENDAR POSITIONING RULES */
/* Date label position support */
.calendar_day.date-label-left .day_label,
.calendar_day.date-label-left .day_number {
  align-self: flex-start;
  text-align: left;
}

.calendar_day.date-label-middle .day_label,
.calendar_day.date-label-middle .day_number {
  align-self: center;
  text-align: center;
}

.calendar_day.date-label-right .day_label,
.calendar_day.date-label-right .day_number {
  align-self: flex-end;
  text-align: right;
}

/* Work entry position support */
.calendar_day.work-entry-left .work {
  align-self: flex-start;
  text-align: left;
}

.calendar_day.work-entry-middle .work {
  align-self: center;
  text-align: center;
}

.calendar_day.work-entry-right .work {
  align-self: flex-end;
  text-align: right;
}

/* CALENDAR RESPONSIVE - Mobile */
@media (max-width: 720px) {
  .calendar_day {
    width: 100%;
    max-width: 100%;
    min-height: calc(100dvh / 8);
    margin: 0;
    padding: 0;
    font-size: var(--font-sm);
  }

  .calendar_header {
    width: 20%;
    max-width: 20%;
    height: 1.5rem;
    max-height: 1.5rem;
    margin-top: 1px;
    padding: 0 0 0 2px;
    font-size: var(--font-xs);
  }

  .day_number {
    padding: 2px;
    font-size: var(--font-sm);
  }

  .work {
    font-size: var(--font-xs);
    line-height: 1.2;
    margin: 1px 0 0 0;
  }

  .work_row {
    margin: 0 2px;
    gap: 2px;
  }

  .work_row span {
    margin: 0 2px;
    font-size: var(--font-xs);
  }
}

/* CALENDAR RESPONSIVE - Very Small Screens */
@media (max-width: 480px) {
  .calendar_day {
    width: 100%;
    max-width: 100%;
    min-height: calc(100dvh / 6);
    margin: 0;
    padding: 0;
    font-size: var(--font-xs);
    line-height: 1.2;
    overflow: hidden;
  }

  .calendar_header {
    width: 25%;
    max-width: 25%;
    height: 1.25rem;
    max-height: 1.25rem;
    margin: 0;
    padding: 0 0 0 1px;
    font-size: 0.65rem;
  }

  .day_number {
    padding: 1px;
    font-size: 0.65rem;
    font-weight: 700;
  }

  .work {
    font-size: 0.6rem;
    margin: 0;
    padding: 1px;
    line-height: 1.1;
    white-space: normal;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .work_row {
    margin: 0;
    padding: 0;
    flex-direction: column;
    gap: 1px;
  }

  .work_row span {
    margin: 0;
    padding: 0;
    font-size: 0.6rem;
  }

  .week {
    margin: 0;
    padding: 0;
  }
}

/* Calendar picker indicator */
::-webkit-calendar-picker-indicator {
  filter: invert(1);
}
/* =========================================================================
   CALENDAR V2 - MONTH GRID LAYOUT
   ========================================================================= */

/**
 * PayCal - Calendar v2 Month View Styles
 * 
 * Professional month calendar display with proper week layout,
 * day headers (Sun-Sat), and work entry metrics.
 * 
 * Date: March 3, 2026
 */

.datagrid_layout_month .datagrid_controls {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
  flex-wrap: wrap;
  align-items: center;
}

.calendar-v2-month-title {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-text, var(--panel-text));
  background-color: transparent;
  border: none;
  margin: 0 12px;
  flex: 0 0 auto;
}

.datagrid_layout_month .datagrid_control {
  padding: 8px 12px;
  border: 1px solid var(--btn-border);
  border-radius: 4px;
  background-color: var(--button-bg);
  color: var(--button-text);
  font-size: 13px;
  cursor: pointer;
  transition: background-color 120ms ease;
  font-weight: 500;
}

.datagrid_layout_month .datagrid_control:hover {
  background-color: var(--button-bg-hover);
}

.datagrid_layout_month .datagrid_control:focus {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: 2px;
}

/* =========================================================================
   MONTH CALENDAR CONTAINER
   ========================================================================= */
.datagrid_layout_month .datagrid_controls {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}
.calendar-v2-nav-button:hover {
  background-color: var(--button-bg-hover);
}

/* =========================================================================
   WEEKDAY HEADERS
   ========================================================================= */

.calendar-v2-weekday-headers {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 0;
  width: 100%;
  min-width: 0;
  box-sizing: border-box;
  margin-bottom: 0;
  background: var(--panel-border);
  border: 1px solid var(--panel-border);
  border-bottom: none;
}

.calendar-v2-weekday-headers.hidden {
  display: none;
}

.calendar-v2-weekday-header {
  padding: 12px 8px;
  min-width: 0;
  box-sizing: border-box;
  background: var(--panel-head-back);
  color: var(--panel-head-fore);
  text-align: center;
  font-weight: 600;
  font-size: 13px;
  border-right: 1px solid var(--panel-border);
}

.calendar-v2-weekday-header:last-child {
  border-right: none;
}

/* =========================================================================
   MONTH CALENDAR GRID
   ========================================================================= */

.datagrid_month_grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 0;
  width: 100%;
  min-width: 0;
  box-sizing: border-box;
  background: var(--panel-border);
  border: 1px solid var(--panel-border);
  border-top: none;
  border-radius: 0 0 4px 4px;
  overflow: hidden;
}

/* ARIA role="row" wrappers must span all 7 columns and replicate the inner
   grid so cells layout identically to being direct grid children. */
.datagrid_month_row {
  grid-column: 1 / -1;
  display: flex;
  gap: 0;
  width: 100%;
  min-width: 0;
  box-sizing: border-box;
}

/* =========================================================================
   MONTH CALENDAR CELLS
   ========================================================================= */

.datagrid_month_cell {
  position: relative;
  display: flex;
  flex-direction: column;
  flex: 1 1 0;
  width: calc(100% / 7);
  max-width: calc(100% / 7);
  min-width: 0;
  min-height: 140px;
  padding: 12px;
  box-sizing: border-box;
  border: 1px solid var(--panel-border);
  background: var(--panel-bg);
  color: var(--panel-text);
  cursor: pointer;
  transition: background-color 120ms ease, box-shadow 120ms ease;
}

.datagrid_month_cell:hover {
  background: var(--calendar-day-hover, color-mix(in srgb, var(--button-primary-bg) 12%, var(--panel-bg)));
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--calendar-border, var(--panel-border)) 72%, transparent);
}

.datagrid_month_grid .datagrid_month_cell:focus,
.datagrid_month_grid .datagrid_month_cell:focus-visible {
  outline: none !important;
  outline-offset: 0 !important;
  /* Inset ring avoids reflow while keeping all four sides visible at grid edges. */
  box-shadow: inset 0 0 0 1px var(--color-focus-ring, #0096d6) !important;
  background: var(--calendar-day-focus, color-mix(in srgb, var(--panel-bg) 88%, var(--panel-text) 12%));
}

.datagrid_month_cell.datagrid_month_cell_today .datagrid_month_cell_header {
  font-weight: 700;
}

.datagrid_month_cell.datagrid_month_cell_adjacent {
  background: color-mix(in srgb, var(--calendar-day-bg, var(--panel-bg)) 82%, var(--panel-bg));
  opacity: 0.7;
}

/* Locked calendar cell (historical record locking) */
.datagrid_month_cell.datagrid_month_cell_locked {
  position: relative;
  background: color-mix(in srgb, var(--calendar-day-bg, var(--panel-bg)) 88%, var(--panel-bg));
  cursor: not-allowed;
  pointer-events: none;
}

.datagrid_month_cell.datagrid_month_cell_locked::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 48px;
  height: 48px;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2'%3E%3Crect x='3' y='11' width='18' height='11' rx='2' ry='2'/%3E%3Cpath d='M7 11V7a5 5 0 0 1 10 0v4'/%3E%3C/svg%3E");
  background-size: contain;
  background-repeat: no-repeat;
  opacity: 0.12;
  z-index: 0;
}

.datagrid_month_cell.datagrid_month_cell_locked .datagrid_month_cell_content {
  opacity: 0.5;
  z-index: 1;
  position: relative;
}

.datagrid_month_cell.datagrid_month_cell_locked:hover {
  background: color-mix(in srgb, var(--calendar-day-bg, var(--panel-bg)) 88%, var(--panel-bg));
  box-shadow: none;
}

/* Day number header */
.datagrid_month_cell_header {
  margin-bottom: 10px;
  padding-bottom: 8px;
  min-width: 0;
  border-bottom: none;
  font-weight: 600;
  font-size: 14px;
  color: var(--panel-head-fore);
  transition: color 120ms ease;
}

/* Content area with metrics */
.datagrid_month_cell_content {
  font-size: 11px;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
  overflow-y: auto;
  overflow-x: hidden;
}

.datagrid_month_cell_content::-webkit-scrollbar {
  width: 4px;
}

.datagrid_month_cell_content::-webkit-scrollbar-track {
  background: transparent;
}

.datagrid_month_cell_content::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.1);
  border-radius: 2px;
}

.datagrid_month_cell_content::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.2);
}

/* Metric display (Label: Value) */
/* Entries count badge */
.datagrid_month_value.entries-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 10px;
  background: rgba(0, 188, 212, 0.15);
  color: var(--color-primary);
  font-size: 10px;
  font-weight: 700;
}

/* Hours badge */
.datagrid_month_value.hours-badge {
  background: rgba(76, 175, 80, 0.15);
  color: #4caf50;
}

/* Empty state */
.datagrid_layout_month .datagrid_empty {
  grid-column: 1 / -1;
  padding: 48px 24px;
  text-align: center;
  color: var(--panel-text);
  opacity: 0.5;
  font-size: 14px;
}

/* =========================================================================
   MODAL DIALOG (Calendar v2)
   ========================================================================= */

.calendar_modal {
  --dialog-max-width: 65vw;
  --dialog-max-height: 90vh;
}

.calendar_modal_header {
  justify-content: center;
}

.calendar_modal_header_actions {
  display: flex;
  align-items: center;
  gap: var(--gap-sm);
  margin-left: auto;
}

.calendar_modal_header_add {
  flex: 0 0 auto;
}

.calendar_modal_header_add:hover {
  opacity: 0.9;
}

.calendar_modal_header h2 {
  margin: 0;
  font-size: var(--font-lg);
  color: var(--modal-head-fore);
  text-align: center;
  width: 100%;
  padding: 0 2.75rem;
}

.calendar_modal_close {
  left: var(--pad-sm) !important;
  right: auto !important;
}

.calendar_modal_close:hover {
  color: var(--color-primary);
}

.calendar_modal_body {
  padding: var(--pad-md);
  overflow-y: auto;
}

.calendar_modal_footer {
  display: flex;
  gap: var(--gap-sm);
  justify-content: flex-end;
}

.calendar_modal_action {
  flex: 0 0 auto;
  min-width: 7.5rem;
}

.calendar_modal_action:hover {
  opacity: 0.95;
}

.calendar_modal_action.calendar_modal_action_save:hover {
  opacity: 0.9;
}

.form-field label {
  font-weight: 600;
  font-size: 13px;
  color: var(--panel-head-fore);
}

.form-field select,
.form-field input {
  padding: 8px;
  border: 1px solid var(--panel-border);
  border-radius: 4px;
  background: var(--panel-bg);
  color: var(--panel-text);
  font-size: 13px;
  font-family: inherit;
}

.form-field select:focus,
.form-field input:focus {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: -1px;
}

.form-btn:hover {
  background: var(--button-bg-hover);
  opacity: 0.9;
}

.form-btn-submit:hover {
  opacity: 0.8;
}

.success-message p {
  margin: 6px 0;
  font-size: 13px;
}

.success-message p:first-child {
  font-weight: 600;
  color: var(--color-primary);
  margin-bottom: 12px;
}

.success-message em {
  font-weight: 600;
  font-style: normal;
  color: var(--panel-head-fore);
}

/* Work Entry Table */
.work-entries-table thead {
  background: var(--panel-head-back);
  color: var(--panel-head-fore);
}

.work-entries-table th {
  padding: 12px 8px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  border-bottom: 2px solid var(--panel-border);
}

.work-entries-table td {
  padding: 10px 8px;
  border-bottom: 1px solid var(--panel-border);
  vertical-align: middle;
}

.work-entries-table tr:hover {
  background: rgba(0, 188, 212, 0.05);
}

.work-entries-table input[type="text"],
.work-entries-table input[type="number"],
.work-entries-table select {
  width: 100%;
  padding: 6px 8px;
  background: var(--panel-bg);
  color: var(--panel-text);
  border: 1px solid var(--panel-border);
  border-radius: 4px;
  font-size: 13px;
}

.work-entries-table input:focus,
.work-entries-table select:focus {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: 1px;
}

/* Table column widths */
.work-entries-table th.th-site {
  width: 30%;
}

.work-entries-table th.th-regular,
.work-entries-table th.th-overtime,
.work-entries-table th.th-loa,
.work-entries-table th.th-travel {
  width: 15%;
}

.work-entries-table th.th-action {
  width: 10%;
}
.work-entry-delete:hover {
  opacity: 0.8;
}
.add-entry-button:hover {
  opacity: 0.9;
}

/* Work Entry Display (read-only) */
/* =========================================================================
   RESPONSIVE - MOBILE
   ========================================================================= */

@media (max-width: 768px) {
  #calendar-modal {
    max-width: 95%;
    width: 95%;
    max-height: 95vh;
  }
  
  /* Stack table rows vertically on mobile */
  .work-entries-table,
  .work-entries-table thead,
  .work-entries-table tbody,
  .work-entries-table tr,
  .work-entries-table td,
  .work-entries-table th {
    display: block;
    width: 100%;
  }
  
  .work-entries-table thead {
    display: none; /* Hide header on mobile */
  }
  
  .work-entries-table tr {
    margin-bottom: 16px;
    border: 1px solid var(--panel-border);
    border-radius: 4px;
    padding: 12px;
    background: rgba(0, 0, 0, 0.05);
  }
  
  .work-entries-table td {
    border: none;
    padding: 8px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .work-entries-table td:before {
    content: attr(data-label);
    font-weight: 600;
    margin-right: 10px;
    color: var(--panel-head-fore);
    flex-shrink: 0;
  }
  
  .work-entries-table input[type="text"],
  .work-entries-table input[type="number"],
  .work-entries-table select {
    max-width: 60%;
  }
}

/* =========================================================================
   RESPONSIVE
   ========================================================================= */

@media (max-width: 1024px) {
  #calendar-modal {
    max-width: 90%;
    width: 90%;
  }
  .datagrid_month_cell_content {
    font-size: 10px;
    gap: 4px;
  }
}

@media (max-width: 768px) {
  .datagrid_month_grid {
    gap: 0;
    border-radius: 0;
  }
  .datagrid_month_cell:nth-child(7n) {
    border-right: none;
  }
}

@media (max-width: 450px) {
  .calendar-v2-weekday-headers {
    display: none;
  }

  .datagrid_month_grid {
    display: block;
    border-top: 1px solid var(--panel-border);
    border-radius: 4px;
    overflow: visible;
  }

  .datagrid_month_row {
    display: block;
  }

  .datagrid_month_cell {
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 100%;
    min-height: 0;
    margin: 0 0 0.35rem 0;
    padding: 0.45rem;
    border: 1px solid var(--panel-border);
    border-radius: 4px;
  }

  .datagrid_month_cell:last-child {
    margin-bottom: 0;
  }

  .calendar-v2-weekday-header {
    padding: 8px 4px;
    font-size: 11px;
  }

  .datagrid_month_cell_header {
    font-size: 12px;
    margin-bottom: 6px;
  }

  .datagrid_month_cell_content {
    font-size: 9px;
    gap: 3px;
  }
}

/* Force final modal sizing/layout (overrides earlier duplicate blocks) */
#calendar-modal {
  width: 65vw;
  max-width: 65vw;
}

@media (max-width: 1024px) {
  #calendar-modal {
    width: 90vw;
    max-width: 90vw;
  }
}

@media (max-width: 768px) {
  #calendar-modal {
    width: 95vw;
    max-width: 95vw;
  }
}

/* =========================================================================
   WORK ENTRIES IN MONTH VIEW
   ========================================================================= */

.datagrid_month_cell_content .work {
  font-size: 11px;
  line-height: 1.4;
  padding: 4px 0;
  margin: 4px 0 0 0;
  color: inherit;
  display: block;
  width: 100%;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.datagrid_month_cell_content .work strong {
  display: inline;
  font-weight: 600;
  margin-right: 0.25rem;
  color: var(--panel-text);
}

.datagrid_month_cell_content .work br {
  display: none;
}

/* Responsive: show line breaks on smaller screens */
@media (max-width: 1024px) {
  .datagrid_month_cell_content .work {
    font-size: 10px;
  }
}

/* =========================================================================
   CALENDAR V2 MONTH VIEW STYLES (Grid-based calendar)
   ========================================================================= */

/* MONTH CALENDAR CONTAINER */
.datagrid_controls {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
  flex-wrap: wrap;
  align-items: center;
}

.datagrid_controls.hidden {
  display: none;
}

.calendar-v2-nav-button:hover {
  background-color: var(--button-bg-hover);
}

/* WEEKDAY HEADERS */

.calendar-v2-weekday-headers {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 0;
  margin-bottom: 0;
  background: var(--panel-border);
  border: 1px solid var(--panel-border);
  border-bottom: none;
}

.calendar-v2-weekday-header {
  padding: 12px 8px;
  background: var(--panel-head-back);
  color: var(--panel-head-fore);
  text-align: center;
  font-weight: 600;
  font-size: 13px;
  border-right: 1px solid var(--panel-border);
}

.calendar-v2-weekday-header:last-child {
  border-right: none;
}

/* MONTH CALENDAR GRID */

.datagrid_month_grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 0;
  width: 100%;
  background: var(--panel-border);
  border: 1px solid var(--panel-border);
  border-top: none;
  border-radius: 0 0 4px 4px;
  overflow: hidden;
}

/* MONTH CALENDAR CELLS */
.datagrid_month_cell:hover {
  background: var(--calendar-day-hover, color-mix(in srgb, var(--button-primary-bg) 12%, var(--panel-bg)));
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--calendar-border, var(--panel-border)) 72%, transparent);
}

.datagrid_month_grid .datagrid_month_cell:focus,
.datagrid_month_grid .datagrid_month_cell:focus-visible {
  outline: none !important;
  outline-offset: 0 !important;
  /* Inset ring avoids reflow while keeping all four sides visible at grid edges. */
  box-shadow: inset 0 0 0 1px var(--color-focus-ring, #0096d6) !important;
  background: var(--calendar-day-focus, color-mix(in srgb, var(--panel-bg) 88%, var(--panel-text) 12%));
}

.datagrid_month_cell.datagrid_month_cell_today .datagrid_month_cell_header {
  font-weight: 700;
}

.datagrid_month_cell.datagrid_month_cell_adjacent {
  background: color-mix(in srgb, var(--calendar-day-bg, var(--panel-bg)) 82%, var(--panel-bg));
  opacity: 0.7;
}

/* Day number header */
/* Content area with metrics */
.datagrid_month_cell_content {
  font-size: 11px;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
  overflow-y: auto;
  overflow-x: hidden;
}

.datagrid_month_cell_content::-webkit-scrollbar {
  width: 4px;
}

.datagrid_month_cell_content::-webkit-scrollbar-track {
  background: transparent;
}

.datagrid_month_cell_content::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.1);
  border-radius: 2px;
}

.datagrid_month_cell_content::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.2);
}

/* Metric display (Label: Value) */
/* Entries count badge */
.datagrid_month_value.entries-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 10px;
  background: rgba(0, 188, 212, 0.15);
  color: var(--color-primary);
  font-size: 10px;
  font-weight: 700;
}

/* Hours badge */
.datagrid_month_value.hours-badge {
  background: rgba(76, 175, 80, 0.15);
  color: #4caf50;
}

/* Empty state */
.datagrid_layout_month .datagrid_empty {
  grid-column: 1 / -1;
  padding: 48px 24px;
  text-align: center;
  color: var(--panel-text);
  opacity: 0.5;
  font-size: 14px;
}

/* RESPONSIVE */

@media (max-width: 1024px) {
  .datagrid_month_cell {
    min-height: 120px;
    padding: 10px;
  }

  .datagrid_month_cell_header {
    font-size: 13px;
    margin-bottom: 8px;
  }

  .datagrid_month_cell_content {
    font-size: 10px;
    gap: 4px;
  }
}

@media (max-width: 768px) {
  .datagrid_month_grid {
    gap: 0;
    border-radius: 0;
  }
  .datagrid_month_cell:nth-child(7n) {
    border-right: none;
  }
}

@media (max-width: 480px) {
  .calendar-v2-weekday-headers {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
  }

  .calendar-v2-weekday-header {
    padding: 8px 4px;
    font-size: 11px;
  }

  .datagrid_month_cell_header {
    font-size: 12px;
    margin-bottom: 6px;
  }

  .datagrid_month_cell_content {
    font-size: 9px;
    gap: 3px;
  }
}

/* =========================================================================
   USER POSITIONING PREFERENCES
   ========================================================================= */

/* Date label positioning */
.datagrid_month_cell_header_left {
  text-align: left;
}

.datagrid_month_cell_header_center {
  text-align: center;
}

.datagrid_month_cell_header_right {
  text-align: right;
}

/* Work entry positioning */
.work_left {
  text-align: left;
}

.work_center {
  text-align: center;
}

.work_right {
  text-align: right;
}

/* Unlock Panel */
#paycal-unlock-panel {
  position: fixed;
  top: 12px;
  right: 12px;
  z-index: 10001;
  background: #fff;
  color: #111;
  border: 1px solid #ccc;
  border-radius: 8px;
  padding: 12px;
  max-width: 320px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.calendar-save-error {
  background: rgba(198, 40, 40, 0.12);
  border-left-color: var(--color-danger);
  margin-bottom: 12px;
}

  /* Final authority for date picker footer layout. */
  #modal_cal_picker .modal_footer {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    gap: 0.5rem !important;
  }

  #modal_cal_picker .date_picker_actions {
    display: flex !important;
    flex-direction: row !important;
    justify-content: center !important;
    align-items: center !important;
    flex-wrap: nowrap !important;
    gap: 0.5rem !important;
    width: 100% !important;
  }

  #modal_cal_picker .date_picker_actions .btn {
    flex: 0 0 auto !important;
    width: auto !important;
    min-width: 7.5rem;
    margin: 0 !important;
  }

  #modal_cal_picker .date_picker_shortcuts {
    margin-top: 0 !important;
    width: 100% !important;
    justify-content: center !important;
  }

