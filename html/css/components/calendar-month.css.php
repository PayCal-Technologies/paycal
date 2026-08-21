<?php declare(strict_types=1); ?>
/* Route-owned data display styles: calendar. */

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
  background: var(--panel-head-bg, var(--panel-bg, var(--color-surface)));
  color: var(--calendar-weekday-header-text, var(--panel-text, var(--color-text)));
  text-align: center;
  font-weight: 600;
  font-size: 0.75rem;
  border-right: 1px solid var(--panel-border);
}

.calendar-v2-weekday-header:last-child {
  border-right: none;
}

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
.datagrid_month_row {
  grid-column: 1 / -1;
  display: flex;
  gap: 0;
  width: 100%;
  min-width: 0;
  min-height: 140px;
  box-sizing: border-box;
}

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
  background-color: var(--panel-bg);
  background-image: var(--calendar-heatmap-load-gradient, none);
  color: var(--panel-text);
  cursor: pointer;
  overflow: hidden;
  transition: background-color 120ms ease, background-image 120ms ease, box-shadow 120ms ease;
}

.datagrid_layout_month.datagrid_heatmap_enabled .datagrid_month_cell[class*="calendar_heatmap_level_"] {
  --calendar-heatmap-load-gradient:
    linear-gradient(
      to top,
      color-mix(in srgb, var(--calendar-heatmap-load-color) var(--calendar-heatmap-load-opacity), transparent) 0,
      color-mix(in srgb, var(--calendar-heatmap-load-color) var(--calendar-heatmap-load-opacity), transparent) var(--calendar-heatmap-load-core-height),
      color-mix(in srgb, var(--calendar-heatmap-load-color) var(--calendar-heatmap-load-fade-opacity), transparent) var(--calendar-heatmap-load-height),
      transparent calc(var(--calendar-heatmap-load-height) + 1px)
    );
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

.datagrid_month_cell_header,
.datagrid_month_cell_content,
.calendar_earnings_badges {
  position: relative;
  z-index: 1;
}

.datagrid_month_cell:hover {
  background-color: var(--calendar-day-hover, color-mix(in srgb, var(--button-primary-bg) 12%, var(--panel-bg)));
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--calendar-border, var(--panel-border)) 72%, transparent);
}

.datagrid_month_grid .datagrid_month_cell:focus,
.datagrid_month_grid .datagrid_month_cell:focus-visible {
  outline: none !important;
  outline-offset: 0 !important;
  /* Inset ring avoids reflow while keeping all four sides visible at grid edges. */
  box-shadow: inset 0 0 0 1px var(--color-focus-ring) !important;
  background: var(--calendar-day-focus, color-mix(in srgb, var(--panel-bg) 88%, var(--panel-text) 12%));
}

.datagrid_month_grid .datagrid_month_cell:focus-visible::after {
  animation: calendarFocusRipple 420ms ease-out;
  box-shadow: inset 0 0 0 2px var(--color-focus-ring);
}

.datagrid_month_cell.calendar_cell_save_pending {
  background-color: color-mix(in srgb, var(--color-primary) 7%, var(--panel-bg));
}

.datagrid_month_cell.calendar_cell_save_pending::after {
  opacity: 1;
  animation: calendarSavePending 1150ms ease-in-out infinite;
  box-shadow: inset 0 0 0 2px color-mix(in srgb, var(--color-primary) 55%, transparent);
}

.datagrid_month_cell.calendar_cell_save_committed::after {
  animation: calendarSaveCommitted 900ms cubic-bezier(0.2, 0.8, 0.2, 1);
  box-shadow: inset 0 0 0 2px color-mix(in srgb, var(--color-success) 72%, transparent);
}

.datagrid_month_cell.calendar_cell_save_error::after {
  animation: calendarSaveErrorFlash 1200ms ease-out;
  box-shadow: inset 0 0 0 2px color-mix(in srgb, var(--color-danger) 72%, transparent);
}

.datagrid_month_cell[data-selected="true"],
.datagrid_month_cell.datagrid_month_cell_shift_range {
  background-color: var(--calendar-day-selected, color-mix(in srgb, var(--button-primary-bg) 18%, var(--panel-bg)));
  z-index: 1;
}

.datagrid_month_cell[data-selected="true"]:hover,
.datagrid_month_cell.datagrid_month_cell_shift_range:hover {
  background-color: var(--calendar-day-selected, color-mix(in srgb, var(--button-primary-bg) 18%, var(--panel-bg)));
}

.datagrid_month_grid .datagrid_month_cell[data-selected="true"]:focus,
.datagrid_month_grid .datagrid_month_cell[data-selected="true"]:focus-visible,
.datagrid_month_grid .datagrid_month_cell.datagrid_month_cell_shift_range:focus,
.datagrid_month_grid .datagrid_month_cell.datagrid_month_cell_shift_range:focus-visible {
  background-color: var(--calendar-day-selected, color-mix(in srgb, var(--button-primary-bg) 18%, var(--panel-bg)));
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
  border-top: 3px double var(--accent-color, var(--calendar-pay-period-accent-fallback));
}

.datagrid_month_cell.calendar_pp_period_start {
  border-top-width: 4px;
}

.datagrid_month_cell.calendar_pp_period_end {
  border-top-width: 4px;
}

.datagrid_layout_month.datagrid_heatmap_enabled .datagrid_month_cell.calendar_heatmap_level_1 {
  --calendar-heatmap-load-color: var(--calendar-heatmap-level-1-accent);
  --calendar-heatmap-load-height: 6%;
  --calendar-heatmap-load-core-height: 2%;
  --calendar-heatmap-load-opacity: 8%;
  --calendar-heatmap-load-fade-opacity: 1.5%;
}

.datagrid_layout_month.datagrid_heatmap_enabled .datagrid_month_cell.calendar_heatmap_level_2 {
  --calendar-heatmap-load-color: var(--calendar-heatmap-level-2-accent);
  --calendar-heatmap-load-height: 15%;
  --calendar-heatmap-load-core-height: 6%;
  --calendar-heatmap-load-opacity: 10%;
  --calendar-heatmap-load-fade-opacity: 2.5%;
}

.datagrid_layout_month.datagrid_heatmap_enabled .datagrid_month_cell.calendar_heatmap_level_3 {
  --calendar-heatmap-load-color: var(--calendar-heatmap-level-3-accent);
  --calendar-heatmap-load-height: 31%;
  --calendar-heatmap-load-core-height: 14%;
  --calendar-heatmap-load-opacity: 12%;
  --calendar-heatmap-load-fade-opacity: 3.5%;
}

.datagrid_layout_month.datagrid_heatmap_enabled .datagrid_month_cell.calendar_heatmap_level_4 {
  --calendar-heatmap-load-color: var(--calendar-heatmap-level-4-accent);
  --calendar-heatmap-load-height: 57%;
  --calendar-heatmap-load-core-height: 28%;
  --calendar-heatmap-load-opacity: 14%;
  --calendar-heatmap-load-fade-opacity: 4.5%;
}

.datagrid_layout_month.datagrid_heatmap_enabled .datagrid_month_cell.calendar_heatmap_level_5 {
  --calendar-heatmap-load-color: var(--calendar-heatmap-level-5-accent);
  --calendar-heatmap-load-height: 100%;
  --calendar-heatmap-load-core-height: 56%;
  --calendar-heatmap-load-opacity: 16%;
  --calendar-heatmap-load-fade-opacity: 5.5%;
}

.datagrid_layout_month.datagrid_heatmap_enabled .datagrid_month_cell[class*="calendar_heatmap_level_"]:hover {
  --calendar-heatmap-load-opacity: 18%;
  --calendar-heatmap-load-fade-opacity: 7%;
}

.datagrid_layout_month.datagrid_heatmap_enabled .datagrid_month_cell.datagrid_month_cell_today[class*="calendar_heatmap_level_"] {
  box-shadow: inset 0 0 0 2px var(--calendar-heatmap-today-ring);
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
  --earnings-badge-bg: var(--calendar-earnings-badge-gross-bg);
  --earnings-badge-accent: var(--calendar-earnings-badge-gross-accent);
  border: 0;
  border-bottom: 3px solid color-mix(in srgb, var(--earnings-badge-accent) 82%, white 18%);
  background: var(--earnings-badge-bg);
  color: contrast-color(var(--earnings-badge-bg) vs white, var(--calendar-contrast-dark));
  font-weight: 800;
}

.calendar_earnings_badge_gross {
  --earnings-badge-bg: var(--calendar-earnings-badge-gross-bg);
  --earnings-badge-accent: var(--calendar-earnings-badge-gross-accent);
}

.calendar_earnings_badge_net {
  --earnings-badge-bg: var(--calendar-earnings-badge-net-bg);
  --earnings-badge-accent: var(--calendar-earnings-badge-net-accent);
}

.calendar_earnings_badge_deductions {
  --earnings-badge-bg: var(--calendar-earnings-badge-deductions-bg);
  --earnings-badge-accent: var(--calendar-earnings-badge-deductions-accent);
}

.datagrid_month_cell.datagrid_month_cell_today .datagrid_month_cell_header {
  font-weight: 700;
}

.datagrid_month_cell.datagrid_month_cell_adjacent {
  background-color: color-mix(in srgb, var(--calendar-day-bg, var(--panel-bg)) 82%, var(--panel-bg));
  opacity: 0.7;
}

.datagrid_month_cell.datagrid_month_cell_adjacent[data-selected="true"],
.datagrid_month_cell.datagrid_month_cell_adjacent.datagrid_month_cell_shift_range {
  opacity: 1;
  background-color: var(--calendar-day-selected, color-mix(in srgb, var(--button-primary-bg) 18%, var(--panel-bg)));
}
.datagrid_month_cell.datagrid_month_cell_locked {
  position: relative;
  background-color: color-mix(in srgb, var(--calendar-day-bg, var(--panel-bg)) 88%, var(--panel-bg));
  cursor: not-allowed;
  pointer-events: auto;
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
  background-color: color-mix(in srgb, var(--calendar-day-bg, var(--panel-bg)) 88%, var(--panel-bg));
  box-shadow: none;
}
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
  border: 1px solid color-mix(in srgb, var(--color-focus-ring) 45%, var(--panel-border));
  border-radius: 6px;
  background: color-mix(in srgb, var(--panel-bg) 80%, transparent);
  box-shadow: 0 14px 36px color-mix(in srgb, black 42%, transparent);
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
  background: color-mix(in srgb, black 10%, transparent);
  border-radius: 2px;
}

.datagrid_month_cell_content::-webkit-scrollbar-thumb:hover {
  background: color-mix(in srgb, black 20%, transparent);
}
.datagrid_month_value.entries-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 10px;
  --entries-badge-bg: var(--calendar-earnings-badge-entries-bg);
  background: var(--entries-badge-bg);
  color: contrast-color(var(--entries-badge-bg) vs white, var(--calendar-badge-contrast-text));
  font-size: var(--cal-work-entry-badge-font-size);
  font-weight: 700;
}
.datagrid_month_value.hours-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 10px;
  --hours-badge-bg: var(--calendar-earnings-badge-hours-bg);
  background: var(--hours-badge-bg);
  color: contrast-color(var(--hours-badge-bg) vs white, var(--calendar-badge-contrast-text));
  font-size: var(--cal-work-entry-badge-font-size);
  font-weight: 700;
  flex: 0 0 auto;
}
.datagrid_layout_month .datagrid_empty {
  grid-column: 1 / -1;
  padding: 48px 24px;
  text-align: center;
  color: var(--panel-text);
  opacity: 0.5;
  font-size: 0.8rem;
}

.datagrid_month_cell_content .work {
  --work-entry-chip-fore: var(--calendar-work-chip-fore-fallback, var(--work-fore));
  --work-entry-chip-strong-fore: var(--work-entry-chip-fore);
  --work-entry-chip-text-shadow: var(--calendar-work-chip-text-shadow, 0 1px 1px color-mix(in srgb, black 72%, transparent));
  font-size: var(--cal-work-entry-font-size);
  line-height: var(--cal-work-entry-line-height);
  padding: 4px 0 4px 1px;
  margin: 4px 0 0 0;
  color: var(--work-entry-chip-fore);
  display: block;
  width: 100%;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-weight: 800;
  letter-spacing: 0.02em;
  text-shadow: var(--work-entry-chip-text-shadow);
}

.datagrid_month_cell_content .work strong {
  display: inline;
  font-weight: 800;
  margin-right: 0.25rem;
  color: var(--work-entry-chip-strong-fore);
}

.datagrid_month_cell_content .work br {
  display: none;
}

.datagrid_month_cell.calendar_cell_save_committed .datagrid_month_cell_content .work {
  animation: calendarWorkEntryLockIn 520ms ease-out both;
}
@media (max-width: 1024px) {
  .datagrid_month_cell_content .work {
    font-size: var(--cal-work-entry-font-size);
  }
}
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
  background: var(--panel-head-bg, var(--panel-bg, var(--color-surface)));
  color: var(--calendar-weekday-header-text, var(--panel-text, var(--color-text)));
  text-align: center;
  font-weight: 600;
  font-size: 0.75rem;
  border-right: 1px solid var(--panel-border);
}

.calendar-v2-weekday-header:last-child {
  border-right: none;
}

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
.datagrid_month_cell:hover {
  background: var(--calendar-day-hover, color-mix(in srgb, var(--button-primary-bg) 12%, var(--panel-bg)));
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--calendar-border, var(--panel-border)) 72%, transparent);
}

.datagrid_month_grid .datagrid_month_cell:focus,
.datagrid_month_grid .datagrid_month_cell:focus-visible {
  outline: none !important;
  outline-offset: 0 !important;
  /* Inset ring avoids reflow while keeping all four sides visible at grid edges. */
  box-shadow: inset 0 0 0 1px var(--color-focus-ring) !important;
  background: var(--calendar-day-focus, color-mix(in srgb, var(--panel-bg) 88%, var(--panel-text) 12%));
}

.datagrid_month_cell.datagrid_month_cell_today .datagrid_month_cell_header {
  font-weight: 700;
}

.datagrid_month_cell.datagrid_month_cell_adjacent {
  background-color: color-mix(in srgb, var(--calendar-day-bg, var(--panel-bg)) 82%, var(--panel-bg));
  opacity: 0.7;
}
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
  background: color-mix(in srgb, black 10%, transparent);
  border-radius: 2px;
}

.datagrid_month_cell_content::-webkit-scrollbar-thumb:hover {
  background: color-mix(in srgb, black 20%, transparent);
}
.datagrid_month_value.entries-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 10px;
  --entries-badge-bg: var(--calendar-earnings-badge-entries-bg);
  background: var(--entries-badge-bg);
  color: contrast-color(var(--entries-badge-bg) vs white, var(--calendar-badge-contrast-text));
  font-size: var(--cal-work-entry-badge-font-size);
  font-weight: 700;
}
.datagrid_month_value.hours-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 10px;
  --hours-badge-bg: var(--calendar-earnings-badge-hours-bg);
  background: var(--hours-badge-bg);
  color: contrast-color(var(--hours-badge-bg) vs white, var(--calendar-badge-contrast-text));
  font-size: var(--cal-work-entry-badge-font-size);
  font-weight: 700;
  flex: 0 0 auto;
}
.datagrid_layout_month .datagrid_empty {
  grid-column: 1 / -1;
  padding: 48px 24px;
  text-align: center;
  color: var(--panel-text);
  opacity: 0.5;
  font-size: 0.8rem;
}

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
.work_left {
  text-align: left;
}

.work_center {
  text-align: center;
}

.work_right {
  text-align: right;
}

.calendar-save-error {
  background: var(--calendar-save-error-bg);
  border-left-color: var(--color-danger);
  margin-bottom: 12px;
}
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
  .datagrid_layout_month .datagrid_controls {
    margin-bottom: 0;
  }
}
@media (max-width: 1024px) {
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
