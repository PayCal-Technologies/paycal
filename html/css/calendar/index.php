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

/* Full-bleed calendar app surface — sole exception to #main page-edge inset */
body.page-calendar #page_header {
  margin-bottom: 0;
}

body.page-calendar #main {
  padding-inline: 0;
  padding-block: 0;
  margin-top: 0;
}

body.page-calendar #calendar-v2-root.calendar_full_bleed.panel {
  margin: 0;
  padding: 0;
  width: 100%;
  max-width: 100%;
  border: none;
  border-radius: 0;
  box-shadow: none;
}

/* Work-entry typography — floor stays legible; scales with user --font-* tokens */
:root {
  --cal-work-entry-font-size-min: 0.8125rem;
  --cal-work-entry-font-size: max(var(--cal-work-entry-font-size-min), var(--font-sm));
  --cal-work-entry-line-height: 1.35;
  --cal-work-entry-badge-font-size-min: 0.75rem;
  --cal-work-entry-badge-font-size: max(var(--cal-work-entry-badge-font-size-min), var(--font-xs));
}

.calendar_control_strip {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  flex-wrap: nowrap;
  align-self: stretch;
  gap: 0.25rem;
  width: 100%;
  min-width: 0;
  margin: 0;
  padding: 0.22rem 0.35rem;
  box-sizing: border-box;
  background: var(--panel-bg);
  border-bottom: 1px solid var(--panel-border);
  overflow-x: auto;
  overflow-y: hidden;
  white-space: nowrap;
}

.calendar_control_strip > * {
  flex: 0 0 auto;
}

.calendar_view_pills {
  display: inline-flex;
  flex: 0 0 auto;
  flex-wrap: nowrap;
  width: auto;
  margin: 0;
  padding: 0;
  gap: 0;
  border: 1px inset var(--border-inset-color, var(--panel-border));
  border-radius: 999px;
  background: color-mix(in srgb, var(--panel-bg) 90%, var(--border-inset-color, var(--panel-border)));
  box-shadow:
    inset 0 1px 0 color-mix(in srgb, var(--panel-text, white) 10%, transparent),
    inset 0 -1px 0 color-mix(in srgb, black 18%, transparent);
  overflow: hidden;
}

.calendar_range_controls {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  flex-wrap: nowrap;
  gap: 0;
  margin-left: auto;
  min-width: 0;
  border: 1px inset var(--border-inset-color, var(--panel-border));
  border-radius: 999px;
  background: color-mix(in srgb, var(--panel-bg) 90%, var(--border-inset-color, var(--panel-border)));
  box-shadow:
    inset 0 1px 0 color-mix(in srgb, var(--panel-text, white) 10%, transparent),
    inset 0 -1px 0 color-mix(in srgb, black 18%, transparent);
  overflow: hidden;
  white-space: nowrap;
}

.calendar_range_controls[hidden] {
  display: none;
}

.calendar_range_picker {
  flex: 0 1 auto;
  min-width: 8rem;
  max-width: 22rem;
  margin: 0;
  min-height: 2.05rem;
  padding: 0.28rem 0.8rem;
  overflow: hidden;
  border: 0;
  border-radius: 0;
  background: transparent;
  color: var(--button-text);
  font-family: var(--sans-serif);
  font-size: 1rem;
  font-weight: 700;
  line-height: 1.2;
  text-overflow: ellipsis;
  white-space: nowrap;
  cursor: pointer;
}

.calendar_range_button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  width: 2.1rem;
  min-width: 2.1rem;
  min-height: 2.05rem;
  margin: 0;
  padding: 0;
  border: 0;
  border-left: 1px solid color-mix(in srgb, var(--panel-border) 70%, transparent);
  border-radius: 0;
  background: transparent;
  color: var(--button-text);
  font-family: var(--sans-serif);
  font-size: 1rem;
  font-weight: 800;
  line-height: 1;
  cursor: pointer;
}

.calendar_range_picker:hover,
.calendar_range_picker:focus-visible,
.calendar_range_button:hover,
.calendar_range_button:focus-visible {
  background-color: color-mix(in srgb, var(--btn-selected-back, var(--button-bg-hover)) 45%, transparent);
  color: var(--button-text-hover, var(--button-text));
}

.calendar_range_picker:focus-visible,
.calendar_range_button:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

.calendar_view_pills .radio + label {
  flex: 1 1 0;
  min-width: 0;
  margin: 0;
  padding: 0.28rem 0.7rem;
  border: 0;
  border-radius: 999px;
  font-weight: 600;
  line-height: 1.2;
  white-space: nowrap;
  text-align: center;
  transition: background-color var(--short-transition, 0.1s) ease, color var(--short-transition, 0.1s) ease;
}

.calendar_view_pills .radio:hover + label,
.calendar_view_pills .radio:focus-visible + label {
  border: 0;
  background-color: color-mix(in srgb, var(--btn-selected-back, var(--button-bg-active)) 45%, transparent);
  color: var(--btn-selected-fore, var(--button-text));
}

.calendar_view_pills input[type="radio"]:checked + label,
.calendar_view_pills .radio:active + label {
  border: 0;
  border-bottom: 0;
  background-color: var(--btn-selected-back, var(--button-bg-active));
  color: var(--btn-selected-fore, var(--button-text));
}

.calendar_view_pills:focus-within {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 1px;
  border-radius: 999px;
}

.calendar_view_panel.hidden {
  display: none;
}

.calendar_anchor_picker_content {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
}

.calendar_anchor_picker_label {
  font-weight: 600;
}

.calendar_anchor_picker_input {
  width: 100%;
  max-width: 18rem;
}

.calendar_payperiod_picker_list {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  max-height: min(60vh, 24rem);
  overflow: auto;
  padding: 0.75rem 1rem;
}

.calendar_payperiod_picker_option {
  display: block;
  width: 100%;
  text-align: start;
  padding: 0.55rem 0.75rem;
  border: 1px inset var(--border-inset-color, var(--panel-border));
  border-radius: var(--radius-control, var(--border-radius));
  background: color-mix(in srgb, var(--panel-bg) 92%, var(--border-inset-color, var(--panel-border)));
  color: var(--button-text, var(--color-text));
  cursor: pointer;
}

.calendar_payperiod_picker_option.cal_menu_selected,
.calendar_payperiod_picker_option:hover,
.calendar_payperiod_picker_option:focus-visible {
  background-color: var(--btn-selected-back, var(--button-bg-active));
  color: var(--btn-selected-fore, var(--button-text));
}

.calendar-v2-view-heading {
  cursor: default;
}

#calendar-payperiod-grid .datagrid_month_row .datagrid_month_cell,
#calendar-week-grid .datagrid_month_row .datagrid_month_cell {
  min-height: 6.5rem;
}

@media (max-width: 768px) {
  body.page-calendar #main {
    padding-inline: 0 !important;
    padding-block: 0 !important;
    margin-top: 0 !important;
  }

  body.page-calendar #page_header {
    margin-bottom: 0 !important;
  }

  body.page-calendar #calendar-v2-root.calendar_full_bleed.panel {
    padding: 0 !important;
    border: none !important;
  }

  .calendar_control_strip {
    gap: 0.25rem;
  }

  .calendar_view_pills .radio + label {
    padding: 0.25rem 0.55rem;
    font-size: 0.9rem;
  }

  .calendar_range_picker {
    min-width: 6.5rem;
    max-width: 14rem;
  }
}

/* CALENDAR DATE PICKER & MODAL */
#modal_cal_picker {
  width: min(1200px, 80vw);
  max-width: 80vw;
  max-height: 90vh;
  padding: 0;
  font-family: var(--sans-serif);
  font-weight: 700;
  overflow: hidden;
}

#modal_cal_picker .modal_header {
  padding: 0.9rem 1.25rem;
}

#modal_cal_picker .modal_content {
  padding: 1rem 1.1rem;
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
  gap: var(--gap-md, 0.85rem);
  flex: 1;
  width: 20%;
  height: 100%;
  padding: var(--pad-md, 0.9rem);
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
  gap: 0.5rem;
  padding: 0.55rem 0.75rem;
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
  padding: 0.55rem 0.7rem;
  border-radius: 0.55rem;
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
  gap: 1rem;
  width: 100%;
  margin-top: 0.75rem;
  font-size: var(--font-sm);
  color: var(--color-text);
  opacity: 0.8;
}

#modal_cal_picker .modal_footer {
  flex-direction: column;
  align-items: center;
  gap: 0.8rem;
}

#modal_cal_picker .date_picker_actions {
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
}

#modal_cal_picker .date_picker_actions .btn {
  flex: 0 0 auto;
  min-width: 8.25rem;
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

.calendar_user_lookup_wrap {
  position: relative;
  width: 100%;
  min-width: 0;
}

.calendar_user_lookup_wrap.has-clear #calendar_user_lookup {
  padding-right: 2.25rem;
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
  border: 2px solid var(--color-focus-ring, #0096d6);
  outline: 2px solid var(--color-focus-ring, #0096d6);
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
  font-size: var(--cal-work-entry-font-size);
  font-weight: 400;
  line-height: var(--cal-work-entry-line-height);
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--work-fore);
  overflow-x: hidden;
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

#calendar_day_context_menu [role="menuitem"] {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  min-width: 100%;
  max-width: 100%;
  margin: var(--mar-xs) 0;
  padding: var(--pad-sm) 0 0 var(--pad-sm);
  border: none;
  background: transparent;
  color: inherit;
  font: inherit;
  text-align: inherit;
  cursor: pointer;
}

#calendar_day_context_menu [role="menuitem"]:not([aria-disabled="true"]):hover,
#calendar_day_context_menu [role="menuitem"]:not([aria-disabled="true"]):focus {
  border-radius: var(--radius-cell, var(--border-radius));
  background-color: var(--button-primary-bg);
  color: var(--button-primary-text);
  transition: background-color var(--short-transition) ease;
}

#calendar_day_context_menu [role="menuitem"]:not([aria-disabled="true"]):focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: -2px;
}

#calendar_day_context_menu [role="menuitem"][aria-disabled="true"] {
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

#calendar_day_context_menu [role="menuitem"]:not([aria-disabled="true"]):focus kbd {
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
    font-size: var(--cal-work-entry-font-size);
    line-height: var(--cal-work-entry-line-height);
    margin: 1px 0 0 0;
  }

  .work_row {
    margin: 0 2px;
    gap: 2px;
  }

  .work_row span {
    margin: 0 2px;
    font-size: var(--cal-work-entry-font-size);
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
    font-size: var(--cal-work-entry-font-size);
    margin: 0;
    padding: 1px;
    line-height: var(--cal-work-entry-line-height);
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
    font-size: var(--cal-work-entry-font-size);
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
  gap: 0.35rem;
  margin-bottom: 12px;
  flex-wrap: nowrap;
  align-items: center;
  justify-content: flex-start;
  min-width: 0;
  overflow-x: auto;
}

.datagrid_layout_month .datagrid_controls_trailing {
  margin-left: auto;
  flex: 0 1 32rem;
  min-width: 16rem;
  display: flex;
  justify-content: flex-end;
}

.datagrid_layout_month .datagrid_controls_trailing .calendar_user_selector {
  margin: 0;
  width: 100%;
  max-width: 32rem;
  font-family: var(--sans-serif);
}

.datagrid_layout_month .datagrid_controls_trailing .calendar_user_selector_form {
  min-width: 0;
  font-family: var(--sans-serif);
}

.datagrid_layout_month .datagrid_controls_trailing #calendar_user_lookup {
  min-height: 2.25rem;
  height: 2.25rem;
  margin: 0;
  padding: 0 0.65rem;
  border-width: 1px;
  font-family: var(--sans-serif);
  font-size: 1rem;
  line-height: 1.2;
}

.calendar_user_clear_inline {
  position: absolute;
  top: 50%;
  right: 0.3rem;
  transform: translateY(-50%);
  width: 1.6rem;
  height: 1.6rem;
  min-width: 1.6rem;
  padding: 0;
  border: 0;
  border-radius: 999px;
  background: transparent;
  color: var(--text-muted, var(--color-text));
  box-shadow: none;
}

.calendar_user_clear_inline:hover,
.calendar_user_clear_inline:focus-visible {
  background: color-mix(in srgb, var(--button-bg-hover) 55%, transparent);
  color: var(--button-text-hover, var(--color-text));
}

.datagrid_layout_month .datagrid_controls_trailing .calendar_user_selector label {
  margin: 0;
  white-space: nowrap;
  font-family: var(--sans-serif);
}

.calendar-v2-month-title {
  font-family: var(--sans-serif);
  font-size: 1rem;
  line-height: 1.2;
  font-weight: 600;
  color: var(--color-text, var(--panel-text));
  background-color: transparent;
  border: none;
  margin: 0;
  flex: 0 1 auto;
  min-width: 8rem;
  max-width: 22rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.datagrid_layout_month .datagrid_control {
  font-family: var(--sans-serif);
  padding: 8px 12px;
  border: 1px solid var(--btn-border);
  border-radius: 4px;
  background-color: var(--button-bg);
  color: var(--button-text);
  font-size: 1rem;
  line-height: 1.2;
  cursor: pointer;
  transition: background-color 120ms ease;
  font-weight: 500;
}

.datagrid_layout_month .datagrid_control_icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  width: 2.25rem;
  min-width: 2.25rem;
  padding-inline: 0;
  font-weight: 800;
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
  flex-wrap: nowrap;
  align-items: center;
  justify-content: flex-start;
  gap: 0.35rem;
  margin-bottom: 12px;
  min-width: 0;
  overflow-x: auto;
}
.calendar-v2-nav-button:hover {
  background-color: var(--button-bg-hover);
}

@media (max-width: 900px) {
  .datagrid_layout_month .datagrid_controls {
    flex-wrap: nowrap;
    gap: 0.25rem;
  }

  .datagrid_layout_month .datagrid_controls_trailing {
    order: 4;
    margin-left: 0;
    min-width: 0;
    flex: 0 1 18rem;
    width: auto;
    justify-content: stretch;
  }

  .datagrid_layout_month .datagrid_controls_trailing .calendar_user_selector {
    max-width: 100%;
  }
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
  font-size: 0.75rem;
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
  min-height: 140px;
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

.datagrid_month_cell::after {
  content: '';
  position: absolute;
  inset: 2px;
  border-radius: 4px;
  pointer-events: none;
  opacity: 0;
  box-shadow: inset 0 0 0 2px transparent;
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

.datagrid_month_grid .datagrid_month_cell:focus-visible::after {
  animation: calendarFocusRipple 420ms ease-out;
  box-shadow: inset 0 0 0 2px var(--color-focus-ring, #0096d6);
}

.datagrid_month_cell.calendar_cell_save_pending {
  background: color-mix(in srgb, var(--color-primary, #0096d6) 7%, var(--panel-bg));
}

.datagrid_month_cell.calendar_cell_save_pending::after {
  opacity: 1;
  animation: calendarSavePending 1150ms ease-in-out infinite;
  box-shadow: inset 0 0 0 2px color-mix(in srgb, var(--color-primary, #0096d6) 55%, transparent);
}

.datagrid_month_cell.calendar_cell_save_committed::after {
  animation: calendarSaveCommitted 900ms cubic-bezier(0.2, 0.8, 0.2, 1);
  box-shadow: inset 0 0 0 2px color-mix(in srgb, var(--color-success, #2e7d32) 72%, transparent);
}

.datagrid_month_cell.calendar_cell_save_error::after {
  animation: calendarSaveErrorFlash 1200ms ease-out;
  box-shadow: inset 0 0 0 2px color-mix(in srgb, var(--color-danger, #c62828) 72%, transparent);
}

.datagrid_month_cell[data-selected="true"],
.datagrid_month_cell.datagrid_month_cell_shift_range {
  background: var(--calendar-day-selected, color-mix(in srgb, var(--button-primary-bg) 18%, var(--panel-bg)));
  z-index: 1;
}

.datagrid_month_cell[data-selected="true"]:hover,
.datagrid_month_cell.datagrid_month_cell_shift_range:hover {
  background: var(--calendar-day-selected, color-mix(in srgb, var(--button-primary-bg) 18%, var(--panel-bg)));
}

.datagrid_month_grid .datagrid_month_cell[data-selected="true"]:focus,
.datagrid_month_grid .datagrid_month_cell[data-selected="true"]:focus-visible,
.datagrid_month_grid .datagrid_month_cell.datagrid_month_cell_shift_range:focus,
.datagrid_month_grid .datagrid_month_cell.datagrid_month_cell_shift_range:focus-visible {
  background: var(--calendar-day-selected, color-mix(in srgb, var(--button-primary-bg) 18%, var(--panel-bg)));
}

.datagrid_month_row .datagrid_month_cell[data-selected="true"] + .datagrid_month_cell[data-selected="true"],
.datagrid_month_row .datagrid_month_cell.datagrid_month_cell_shift_range + .datagrid_month_cell.datagrid_month_cell_shift_range {
  border-left-color: transparent;
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}

.datagrid_month_row .datagrid_month_cell[data-selected="true"]:not([data-selected-end="true"]),
.datagrid_month_row .datagrid_month_cell.datagrid_month_cell_shift_range:not(.datagrid_month_cell_shift_range_end) {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

.datagrid_month_cell.calendar_pp_in_period {
  border-top: 3px double var(--accent-color, #4d8ef0);
}

.datagrid_month_cell.calendar_pp_period_start {
  border-top-width: 4px;
}

.datagrid_month_cell.calendar_pp_period_end {
  border-top-width: 4px;
}

.calendar_earnings_badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  margin-top: 0.25rem;
}

.calendar_earnings_badge {
  font-size: var(--cal-work-entry-badge-font-size);
  line-height: var(--cal-work-entry-line-height);
  padding: 0.1rem 0.35rem;
  border-radius: var(--radius-sm, 0.25rem);
  --earnings-badge-bg: color-mix(in srgb, var(--panel-bg) 70%, var(--accent-color, #4d8ef0));
  background: var(--earnings-badge-bg);
  color: var(--work-entry-fore, var(--work-fore));
  color: contrast-color(var(--earnings-badge-bg) vs #fff, #111);
}

.calendar_earnings_badge_gross {
  --earnings-badge-bg: color-mix(in srgb, var(--panel-bg) 70%, var(--accent-color, #4d8ef0));
}

.calendar_earnings_badge_net {
  --earnings-badge-bg: color-mix(in srgb, var(--panel-bg) 65%, var(--color-success, #2e7d32));
}

.calendar_earnings_badge_deductions {
  --earnings-badge-bg: color-mix(in srgb, var(--panel-bg) 65%, var(--earnings-piegraphs-color-deductions, #f2d2a6));
}

.datagrid_month_cell.datagrid_month_cell_today .datagrid_month_cell_header {
  font-weight: 700;
}

.datagrid_month_cell.datagrid_month_cell_adjacent {
  background: color-mix(in srgb, var(--calendar-day-bg, var(--panel-bg)) 82%, var(--panel-bg));
  opacity: 0.7;
}

.datagrid_month_cell.datagrid_month_cell_adjacent[data-selected="true"],
.datagrid_month_cell.datagrid_month_cell_adjacent.datagrid_month_cell_shift_range {
  opacity: 1;
  background: var(--calendar-day-selected, color-mix(in srgb, var(--button-primary-bg) 18%, var(--panel-bg)));
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

/* Locked cells use pointer-events: none so clicks cannot reach them; restore hover while Shift shows earnings tooltip. */
#calendar-v2-root.calendar_shift_tooltip_hover .datagrid_month_cell.datagrid_month_cell_locked {
  pointer-events: auto;
}

/* Day number header */
.datagrid_month_cell_header {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin-bottom: 10px;
  padding-bottom: 8px;
  min-width: 0;
  border-bottom: none;
  font-weight: 600;
  font-size: 0.8rem;
  color: var(--panel-head-fore);
  transition: color 120ms ease;
}

.datagrid_month_cell_day {
  display: block;
  flex: 0 0 auto;
  width: 100%;
}

/* Content area with metrics */
.datagrid_month_cell_content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
  overflow-y: auto;
  overflow-x: hidden;
}

.calendar_day_hover_tooltip {
  position: fixed;
  top: var(--calendar-hover-tooltip-top, 0px);
  left: var(--calendar-hover-tooltip-left, 0px);
  z-index: 12000;
  pointer-events: none;
  min-width: 140px;
  max-width: min(80vw, 240px);
  padding: 9px 11px;
  border: 1px solid color-mix(in srgb, var(--color-focus-ring, #0096d6) 45%, var(--panel-border));
  border-radius: 6px;
  background: color-mix(in srgb, var(--panel-bg) 80%, transparent);
  box-shadow: 0 14px 36px rgba(0, 0, 0, 0.42);
  color: var(--panel-text);
  font-size: 0.92rem;
  line-height: 1.2;
  font-weight: 700;
  letter-spacing: 0.01em;
}

.calendar_day_hover_tooltip .item_pair {
  display: flex;
  justify-content: flex-end;
  align-items: baseline;
  width: 100%;
}

.calendar_day_hover_tooltip .item_label,
.calendar_day_hover_tooltip .item_value {
  flex: 1;
  padding: 0;
}

.calendar_day_hover_tooltip .item_label {
  padding-right: 10px;
  text-align: left;
  white-space: nowrap;
}

.calendar_day_hover_tooltip .item_value {
  text-align: right;
  white-space: nowrap;
}

.calendar_day_hover_tooltip_line + .calendar_day_hover_tooltip_line {
  margin-top: 4px;
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
  --entries-badge-bg: color-mix(in srgb, var(--panel-bg) 85%, var(--color-primary, #00bcd4));
  background: var(--entries-badge-bg);
  color: var(--work-entry-fore, var(--work-fore));
  color: contrast-color(var(--entries-badge-bg) vs #fff, #111);
  font-size: var(--cal-work-entry-badge-font-size);
  font-weight: 700;
}

/* Hours badge */
.datagrid_month_value.hours-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 10px;
  --hours-badge-bg: color-mix(in srgb, var(--panel-bg) 85%, var(--color-success, #4caf50));
  background: var(--hours-badge-bg);
  color: var(--work-entry-fore, var(--work-fore));
  color: contrast-color(var(--hours-badge-bg) vs #fff, #111);
  font-size: var(--cal-work-entry-badge-font-size);
  font-weight: 700;
  flex: 0 0 auto;
}

/* Empty state */
.datagrid_layout_month .datagrid_empty {
  grid-column: 1 / -1;
  padding: 48px 24px;
  text-align: center;
  color: var(--panel-text);
  opacity: 0.5;
  font-size: 0.8rem;
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
  font-size: 0.75rem;
  color: var(--panel-head-fore);
}

.form-field select,
.form-field input {
  padding: 8px;
  border: 1px solid var(--panel-border);
  border-radius: 4px;
  background: var(--panel-bg);
  color: var(--panel-text);
  font-size: 0.75rem;
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
  font-size: 0.75rem;
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
  font-size: 0.75rem;
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
  font-size: 0.75rem;
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
  }

  .datagrid_month_row {
    display: block;
  }

  .datagrid_month_cell {
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 100%;
  }
}

/* Work-entry editor: reset shared dialog chrome and own the full viewport. */
#calendar-modal.calendar_modal {
  position: fixed;
  inset: 0;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  transform: none;
  width: 100vw;
  max-width: 100vw;
  min-width: 0;
  height: 100dvh;
  max-height: 100dvh;
  min-height: 0;
  margin: 0;
  padding: 0;
  border: 0;
  border-radius: 0;
  box-sizing: border-box;
  overflow: hidden;
}

#calendar-modal.calendar_modal[open] {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  grid-template-rows: auto minmax(0, 1fr) auto;
}

#calendar-modal.calendar_modal > * {
  grid-column: 1;
  min-width: 0;
  box-sizing: border-box;
}

#calendar-modal .calendar_modal_header {
  grid-row: 1;
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 1rem;
  min-height: 4rem;
  margin: 0;
  padding: 0.75rem 1rem;
}

#calendar-modal .calendar_modal_header h2 {
  width: auto;
  max-width: 100%;
  margin: 0;
  padding: 0;
  color: var(--modal-head-fore);
  font-size: var(--font-xl);
  line-height: 1.2;
  text-align: left;
  overflow-wrap: anywhere;
}

#calendar-modal .calendar_modal_close {
  grid-column: 2;
  justify-self: end;
  position: static !important;
  inset: auto !important;
  left: auto !important;
  top: auto !important;
  right: auto !important;
  bottom: auto !important;
  transform: none !important;
  flex: 0 0 auto;
  margin: 0;
}

#calendar-modal .calendar_modal_body {
  grid-row: 2;
  display: block;
  min-height: 0;
  padding: 1rem;
  overflow: auto;
}

#calendar-modal #calendar-modal-content,
#calendar-modal .work-entries-form {
  width: 100%;
  min-width: 0;
  max-width: 100%;
}

#calendar-modal .work-entries-form {
  overflow-x: auto;
}

#calendar-modal .work-entries-table {
  width: 100%;
  min-width: 48rem;
  table-layout: fixed;
  border-collapse: collapse;
}

#calendar-modal .work-entries-table th,
#calendar-modal .work-entries-table td {
  min-width: 0;
}

#calendar-modal .work-entries-table th.th-site {
  width: 28%;
}

#calendar-modal .work-entries-table th.th-regular,
#calendar-modal .work-entries-table th.th-overtime,
#calendar-modal .work-entries-table th.th-loa,
#calendar-modal .work-entries-table th.th-travel {
  width: 13%;
}

#calendar-modal .work-entries-table th.th-action {
  width: 20%;
}

#calendar-modal .calendar_modal_footer {
  grid-row: 3;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  margin: 0;
  padding: 0.75rem 1rem 1rem;
}

#calendar-modal .calendar_modal_footer_center {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  gap: var(--gap-sm);
}

#calendar-modal .work-entry-row-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.5rem;
  white-space: nowrap;
}

#calendar-modal .work-entry-add,
#calendar-modal .work-entry-delete {
  flex: 0 0 auto;
}

@media (max-width: 768px) {
  #calendar-modal.calendar_modal {
    width: 100vw;
    max-width: 100vw;
    height: 100dvh;
    max-height: 100dvh;
  }

  #calendar-modal .calendar_modal_header {
    min-height: 3.5rem;
    padding: 0.625rem 0.75rem;
  }

  #calendar-modal .calendar_modal_header h2 {
    font-size: var(--font-lg);
  }

  #calendar-modal .calendar_modal_body {
    padding: 0.75rem;
  }

  #calendar-modal .work-entries-form {
    overflow-x: visible;
  }

  #calendar-modal .work-entries-table {
    min-width: 0;
  }

  #calendar-modal .work-entries-table td.work-entry-row-actions {
    justify-content: flex-end;
    gap: 0.5rem;
  }

  #calendar-modal .calendar_modal_footer_center {
    flex-wrap: wrap;
  }
}

/* =========================================================================
   WORK ENTRIES IN MONTH VIEW
   ========================================================================= */

.datagrid_month_cell_content .work {
  font-size: var(--cal-work-entry-font-size);
  line-height: var(--cal-work-entry-line-height);
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
  color: inherit;
}

.datagrid_month_cell_content .work br {
  display: none;
}

.datagrid_month_cell.calendar_cell_save_committed .datagrid_month_cell_content .work {
  animation: calendarWorkEntryLockIn 520ms ease-out both;
}

/* Responsive: show line breaks on smaller screens */
@media (max-width: 1024px) {
  .datagrid_month_cell_content .work {
    font-size: var(--cal-work-entry-font-size);
  }
}

/* =========================================================================
   CALENDAR V2 MONTH VIEW STYLES (Grid-based calendar)
   ========================================================================= */

/* MONTH CALENDAR CONTAINER */
.datagrid_controls {
  display: flex;
  gap: 0.35rem;
  margin-bottom: 12px;
  flex-wrap: nowrap;
  align-items: center;
  min-width: 0;
  overflow-x: auto;
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
  font-size: 0.75rem;
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
  --entries-badge-bg: color-mix(in srgb, var(--panel-bg) 85%, var(--color-primary, #00bcd4));
  background: var(--entries-badge-bg);
  color: var(--work-entry-fore, var(--work-fore));
  color: contrast-color(var(--entries-badge-bg) vs #fff, #111);
  font-size: var(--cal-work-entry-badge-font-size);
  font-weight: 700;
}

/* Hours badge */
.datagrid_month_value.hours-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 10px;
  --hours-badge-bg: color-mix(in srgb, var(--panel-bg) 85%, var(--color-success, #4caf50));
  background: var(--hours-badge-bg);
  color: var(--work-entry-fore, var(--work-fore));
  color: contrast-color(var(--hours-badge-bg) vs #fff, #111);
  font-size: var(--cal-work-entry-badge-font-size);
  font-weight: 700;
  flex: 0 0 auto;
}

/* Empty state */
.datagrid_layout_month .datagrid_empty {
  grid-column: 1 / -1;
  padding: 48px 24px;
  text-align: center;
  color: var(--panel-text);
  opacity: 0.5;
  font-size: 0.8rem;
}

/* RESPONSIVE */

@media (max-width: 1024px) {
  .datagrid_month_cell {
    min-height: 120px;
    padding: 10px;
  }

  .datagrid_month_cell_header {
    font-size: 0.75rem;
    margin-bottom: 8px;
  }

  .datagrid_month_cell_content {
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

/* =========================================================================
   USER POSITIONING PREFERENCES
   ========================================================================= */

/* Weekday heading positioning */
.datagrid_day_heading_left .calendar-v2-weekday-header,
.datagrid_layout_month[data-day-heading-position="left"] .calendar-v2-weekday-header,
.calendar-v2-weekday-headers_left .calendar-v2-weekday-header {
  text-align: left;
}

.datagrid_day_heading_center .calendar-v2-weekday-header,
.datagrid_layout_month[data-day-heading-position="middle"] .calendar-v2-weekday-header,
.datagrid_layout_month[data-day-heading-position="center"] .calendar-v2-weekday-header,
.calendar-v2-weekday-headers_center .calendar-v2-weekday-header {
  text-align: center;
}

.datagrid_day_heading_right .calendar-v2-weekday-header,
.datagrid_layout_month[data-day-heading-position="right"] .calendar-v2-weekday-header,
.calendar-v2-weekday-headers_right .calendar-v2-weekday-header {
  text-align: right;
}

/* Date label positioning */
.datagrid_date_label_left .datagrid_month_cell_header,
.datagrid_layout_month[data-date-label-position="left"] .datagrid_month_cell_header,
.datagrid_month_cell_header_left {
  text-align: left;
  justify-content: flex-start;
  flex-direction: row;
}

.datagrid_date_label_left .datagrid_month_cell_day,
.datagrid_month_cell_day_left {
  text-align: left;
}

.datagrid_date_label_center .datagrid_month_cell_header,
.datagrid_layout_month[data-date-label-position="middle"] .datagrid_month_cell_header,
.datagrid_layout_month[data-date-label-position="center"] .datagrid_month_cell_header,
.datagrid_month_cell_header_center {
  text-align: center;
  justify-content: center;
  flex-direction: row;
}

.datagrid_date_label_center .datagrid_month_cell_day,
.datagrid_month_cell_day_center {
  text-align: center;
}

.datagrid_date_label_right .datagrid_month_cell_header,
.datagrid_layout_month[data-date-label-position="right"] .datagrid_month_cell_header,
.datagrid_month_cell_header_right {
  text-align: right;
  justify-content: flex-end;
  flex-direction: row;
}

.datagrid_date_label_right .datagrid_month_cell_day,
.datagrid_month_cell_day_right {
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

/* =========================================================================
   PHONE MODE — full-width vertical day list (must be last to win cascade)
   ========================================================================= */
@media (max-width: 450px) {
  .calendar-v2-weekday-headers {
    display: none;
  }

  .datagrid_month_grid {
    display: block;
    gap: 0;
    padding: 0;
    margin: 0;
    border: none;
    border-radius: 0;
  }

  .datagrid_month_row {
    display: block;
    gap: 0;
    margin: 0;
    padding: 0;
  }

  .datagrid_month_cell {
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 100%;
    height: calc(100dvh - 3.5rem);
    min-height: 0;
    margin: 0;
    padding: 1.25rem 1rem;
    border: none;
    border-bottom: 1px solid var(--panel-border);
    border-radius: 0;
    box-sizing: border-box;
  }

  .datagrid_month_cell:last-child {
    border-bottom: none;
  }

  .datagrid_month_cell_header {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
  }

  .datagrid_month_cell_content {
    gap: 4px;
    flex: 0 1 auto;
    max-height: min(48dvh, calc(100% - 5rem));
    overflow-y: auto;
  }

  .datagrid_month_cell > .calendar_earnings_badges {
    flex: 0 0 auto;
    margin-top: 0.25rem;
  }

  /* Strip controls bar bottom margin */
  .datagrid_layout_month .datagrid_controls {
    margin-bottom: 0;
  }
}

/* ── Site color tinted background on work entry blocks (CSP-safe) ───────── */
<?php
use PayCal\Domain\Config\SiteColorPalette;

foreach (SiteColorPalette::pickerPalette() as $pc) {
  $h = strtoupper($pc['hex']);
  echo ".work[data-site-color=\"{$h}\"] {\n";
  echo "  --work-site-tint: color-mix(in srgb, {$h} 22%, var(--work-tint-mix-base, var(--work-entry-back, var(--work-back, var(--color-surface-muted, #1e2330)))));\n";
  echo "  background: var(--work-site-tint);\n";
  echo "  border-left: 3px solid {$h};\n";
  echo "  color: var(--work-entry-fore, var(--work-fore));\n";
  echo "  color: contrast-color(var(--work-site-tint) vs #fff, #111);\n";
  echo "}\n";
}
?>

.work-entry-row:focus-within {
  background: color-mix(in srgb, var(--color-primary, #0096d6) 8%, transparent);
  box-shadow: inset 3px 0 0 var(--color-focus-ring, #0096d6);
}

body[data-runtime-risk-state="locked"] #calendar-v2-root #calendar-grid,
body[data-runtime-risk-state="terminated"] #calendar-v2-root #calendar-grid {
  pointer-events: none;
  filter: blur(2px) saturate(0.55);
  transform: scale(0.985);
  transform-origin: center;
  transition: filter 140ms ease, transform 140ms ease, opacity 140ms ease;
}

body[data-runtime-risk-state="locked"] #calendar-v2-root::after,
body[data-runtime-risk-state="terminated"] #calendar-v2-root::after {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    linear-gradient(90deg, transparent, color-mix(in srgb, var(--panel-text, #fff) 8%, transparent), transparent),
    color-mix(in srgb, var(--panel-bg, #111) 24%, transparent);
  animation: calendarVaultClose 260ms ease-out both;
  z-index: 30;
}

@keyframes calendarFocusRipple {
  0% {
    opacity: 0.95;
    transform: scale(0.97);
  }
  100% {
    opacity: 0;
    transform: scale(1.035);
  }
}

@keyframes calendarSavePending {
  0%, 100% {
    opacity: 0.45;
  }
  50% {
    opacity: 1;
  }
}

@keyframes calendarSaveCommitted {
  0% {
    opacity: 0;
    transform: scale(0.985);
  }
  35% {
    opacity: 1;
    transform: scale(1);
  }
  100% {
    opacity: 0;
    transform: scale(1.018);
  }
}

@keyframes calendarSaveErrorFlash {
  0%, 35% {
    opacity: 1;
  }
  100% {
    opacity: 0;
  }
}

@keyframes calendarWorkEntryLockIn {
  0% {
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-success, #2e7d32) 65%, transparent);
  }
  100% {
    box-shadow: inset 0 0 0 0 transparent;
  }
}

@keyframes calendarVaultClose {
  from {
    opacity: 0;
    transform: scaleX(1.04);
  }
  to {
    opacity: 1;
    transform: scaleX(1);
  }
}
