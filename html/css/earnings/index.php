<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
/**
 * PayCal - Earnings Page Styles
 * 
 * Extracted from main CSS index.php
 * Date: March 1, 2026
 */

/* EARNINGS */
.pp:hover {
  background-color: var(--color-text);
  color: var(--color-text-inverse);
  transition: background-color var(--short-transition) ease;
}

#earnings_line_graph {
  fill: var(--color-text);
  stroke: var(--color-primary-active);
}
.birdsview span,
.birdsview_header span {
  text-align: end;
}

.birdsview span:first-of-type,
.birdsview_transactions span:first-of-type {
  width: 24%;
}

.birdsview_transactions span {
  padding: var(--pad-sm);
  text-align: end;
  text-wrap: nowrap;
}

.earnings_panel {
  width: 100%;
}

.earnings_async_slot {
  width: 100%;
  min-height: 8rem;
  padding: 0.85rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 6px);
  background: var(--elevated-surface, var(--surface));
  box-sizing: border-box;
}

.earnings_async_slot[data-earnings-slot="ytd"] {
  min-height: 11rem;
}

.earnings_async_slot[data-earnings-slot="payperiods"] {
  min-height: 10rem;
  container-type: inline-size;
}

.earnings_async_slot[data-earnings-slot="monthly"] {
  min-height: 14rem;
}

.earnings_panel_title {
  margin: 0 0 0.5rem;
}

.earnings-graph-container {
  width: 100%;
}

.earnings-graph-container svg {
  display: block;
  width: 100%;
  max-width: 100%;
}

.earnings-chart-touch-hint {
  display: none;
  margin: 0.3rem 0 0;
  color: var(--color-text-muted);
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  line-height: 1.25;
  text-align: center;
  text-transform: uppercase;
}

.earnings-chart-touch-hint:not([hidden]) {
  display: block;
}

.earnings_metrics_split {
  width: 100%;
  display: flex;
  flex-wrap: wrap;
  gap: var(--gap-md, 1rem);
  align-items: flex-start;
}

.earnings_metrics_column {
  flex: 1 1 30rem;
  min-width: min(30rem, 100%);
}

.earnings_export_actions {
  margin: 0 0 0.6rem;
}

.earnings_export_note {
  margin: -0.25rem 0 0.75rem;
  color: var(--text-muted, #6b7280);
  font-size: 0.875rem;
}

.hover_help_tooltip {
  position: fixed;
  z-index: 1400;
  bottom: 1.5rem;
  right: 1.5rem;
  max-width: min(32rem, calc(100vw - 2rem));
  padding: 1.25rem 1.75rem;
  border: 1px solid var(--panel-border);
  border-radius: 12px;
  background: var(--back, #101010);
  color: var(--fore, #f5f5f5);
  box-shadow: var(--shadow-lg);
  font-size: 1.125rem;
  line-height: 1.5;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.12s ease;
}

.hover_help_tooltip.is-visible {
  opacity: 1;
}

#daily_earnings,
[id^="daily_earnings_"] {
  display: block;
  margin: var(--mar-lg);
  white-space: nowrap;
  overflow-x: auto;
  overflow-y: hidden;
}

#daily_earnings .earnings_daily_datagrid,
[id^="daily_earnings_"] .earnings_daily_datagrid {
  --datagrid_cols: 4;
  --grid-template-columns: minmax(120px, 1.2fr) repeat(3, minmax(110px, 1fr));
  min-width: max-content;
}

#daily_earnings .earnings_daily_datagrid.datagrid_cols_11,
[id^="daily_earnings_"] .earnings_daily_datagrid.datagrid_cols_11 {
  --datagrid_cols: 11;
  --grid-template-columns:
    minmax(130px, 1.2fr)
    minmax(170px, 2fr)
    minmax(90px, 0.9fr)
    minmax(90px, 0.9fr)
    minmax(90px, 0.9fr)
    minmax(95px, 0.95fr)
    minmax(90px, 0.9fr)
    minmax(90px, 0.9fr)
    minmax(100px, 1fr)
    minmax(95px, 0.95fr)
    minmax(100px, 1fr);
}

#daily_earnings .earnings_daily_datagrid .datagrid_header_row,
[id^="daily_earnings_"] .earnings_daily_datagrid .datagrid_header_row {
  position: sticky;
  top: 0;
  z-index: 1;
}

#daily_earnings .earnings_daily_datagrid .datagrid_item,
[id^="daily_earnings_"] .earnings_daily_datagrid .datagrid_item,
#daily_earnings .earnings_daily_datagrid .datagrid_heading,
[id^="daily_earnings_"] .earnings_daily_datagrid .datagrid_heading {
  text-align: right;
  white-space: nowrap;
}

#daily_earnings .earnings_daily_datagrid .datagrid_item:nth-child(1),
[id^="daily_earnings_"] .earnings_daily_datagrid .datagrid_item:nth-child(1),
#daily_earnings .earnings_daily_datagrid .datagrid_heading:nth-child(1),
[id^="daily_earnings_"] .earnings_daily_datagrid .datagrid_heading:nth-child(1),
#daily_earnings .earnings_daily_datagrid.datagrid_cols_11 .datagrid_item:nth-child(2),
[id^="daily_earnings_"] .earnings_daily_datagrid.datagrid_cols_11 .datagrid_item:nth-child(2),
#daily_earnings .earnings_daily_datagrid.datagrid_cols_11 .datagrid_heading:nth-child(2),
[id^="daily_earnings_"] .earnings_daily_datagrid.datagrid_cols_11 .datagrid_heading:nth-child(2) {
  text-align: left;
}

.earnings_ytd_layout {
  width: 100%;
}

.earnings_ytd_split {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--gap-md, 1rem);
  width: 100%;
  align-items: stretch;
}

.earnings_ytd_card {
  margin: 0;
  padding: 0.65rem;
  width: 100%;
}

.earnings_ytd_datagrid {
  width: 100%;
}

.earnings_ytd_datagrid .datagrid_item:nth-child(2),
.earnings_ytd_datagrid .datagrid_heading:nth-child(2) {
  text-align: right;
}

@media (max-width: 740px) {
  .earnings_ytd_split {
    grid-template-columns: 1fr;
  }
}

.earnings_ytd_basic {
  width: 100%;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 6px);
  padding: var(--pad-md, 0.75rem);
  background: var(--surface);
}

.earnings_ytd_basic_list {
  margin: 0;
  padding: 0;
}

.earnings_report_pairs {
  display: grid;
  gap: 0.28rem;
}

.earnings_report_pair {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 0.75rem;
  align-items: baseline;
  width: 100%;
  margin: 0;
  padding: 0.18rem 0;
}

.earnings_report_pair .item_label,
.earnings_report_pair .item_value {
  min-width: 0;
  margin: 0;
  padding: 0;
}

.earnings_report_pair .item_label {
  color: var(--color-text-muted);
  font-size: 0.82rem;
  font-weight: 700;
  line-height: 1.25;
  text-align: left;
}

.earnings_report_pair .item_value {
  justify-self: end;
  color: var(--color-text);
  font-size: 0.82rem;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  line-height: 1.25;
  text-align: right;
  overflow-wrap: anywhere;
}

.earnings_report_pair--strong .item_label,
.earnings_report_pair--strong .item_value {
  color: var(--color-text);
  font-weight: 900;
}

.earnings_ext_compare_notice {
  margin: 0 0 0.5rem;
  font-size: 0.9rem;
  color: var(--color-text-muted);
}

.earnings_ext_compare_grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: var(--gap-md, 1rem);
}

.earnings_ext_compare_panel {
  margin: 0;
}

.earnings_ext_compare_title {
  margin: 0 0 0.5rem;
}

.earnings_hi_panel {
  width: 100%;
}

.earnings_hi_subtitle {
  margin: 0 0 0.85rem;
  color: var(--color-text-muted);
  font-size: 0.95rem;
  opacity: 0.75;
}

.earnings_hi_grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(15rem, 100%), 1fr));
  gap: 0.28rem 1rem;
}

.earnings_hi_card {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 0.75rem;
  align-items: baseline;
  margin: 0;
  padding: 0.18rem 0;
}

.earnings_hi_card .item_label,
.earnings_hi_card .item_value {
  min-width: 0;
  margin: 0;
  padding: 0;
}

.earnings_hi_card .item_label {
  color: var(--color-text-muted);
  font-size: 0.82rem;
  font-weight: 700;
  line-height: 1.25;
  text-align: left;
}

.earnings_hi_card .item_value {
  justify-self: end;
  color: var(--color-text);
  font-size: 0.82rem;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  line-height: 1.25;
  text-align: right;
  overflow-wrap: anywhere;
}

.earnings_hi_note {
  margin: 0.75rem 0 0;
  font-size: 0.88rem;
  color: var(--color-text-muted);
}

.earnings_piegraphs_grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 0.9rem;
}

.earnings_piegraphs_panel {
  --earnings-piegraphs-spot-color: var(--theme-signature-color, var(--color-primary, #3a86ff));
  --earnings-piegraphs-color-gross: color-mix(in srgb, var(--earnings-piegraphs-spot-color) 65%, #000000 35%);
  --earnings-piegraphs-color-net: color-mix(in srgb, var(--earnings-piegraphs-spot-color) 52%, #ffffff 48%);
  --earnings-piegraphs-color-deductions: color-mix(in srgb, var(--color-warning, #ef6c00) 28%, #ffffff 72%);
  --earnings-piegraphs-controls-block-height: 2.9rem;
}

.earnings_piegraphs_card {
  padding: 0.75rem;
}

.earnings_piegraphs_card_title {
  margin: 0 0 0.4rem;
}

.earnings_piegraphs_month_controls,
.earnings_piegraphs_month_controls_spacer {
  min-height: var(--earnings-piegraphs-controls-block-height);
}

.earnings_piegraphs_month_select {
  width: 100%;
  margin-bottom: 0.5rem;
}

.earnings_piegraphs_svg {
  width: 100%;
  max-width: 260px;
  height: auto;
  display: block;
  margin: 0 auto;
}

.earnings_piegraphs_cutout {
  stroke: var(--border, rgba(255, 255, 255, 0.18));
  stroke-width: 1.5;
}

.earnings_piegraphs_total {
  font-size: 0.95rem;
  font-weight: 600;
  fill: var(--color-text);
}

.earnings_piegraphs_legend {
  margin-top: 0.5rem;
  display: grid;
  gap: 0.35rem;
}

.earnings_piegraphs_legend_row {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.93rem;
  border-radius: 6px;
  padding: 0.15rem 0.2rem;
  transition: background-color var(--short-transition) ease, color var(--short-transition) ease;
}

.earnings_piegraphs_legend_dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  display: inline-block;
}

.earnings_piegraphs_legend_dot_gross {
  background: var(--earnings-piegraphs-color-gross, #1f4f8b);
}

.earnings_piegraphs_legend_dot_deductions {
  background: var(--earnings-piegraphs-color-deductions, #426789);
}

.earnings_piegraphs_legend_dot_net {
  background: var(--earnings-piegraphs-color-net, #74a8d9);
}

.earnings_piegraphs_legend_label {
  color: var(--color-text-muted);
}

.earnings_piegraphs_legend_value {
  text-align: right;
  white-space: nowrap;
}

.earnings_piegraphs_slice {
  transition: opacity var(--short-transition) ease, transform var(--short-transition) ease;
  transform-origin: center;
  transform-box: fill-box;
  cursor: pointer;
}

.earnings_piegraphs_slice.is-hovered {
  opacity: 0.92;
  transform: scale(1.035);
}

.earnings_piegraphs_legend_row.is-hovered {
  background: var(--hover-bg, rgba(255, 255, 255, 0.1));
}

.earnings_piegraphs_empty {
  margin: 0;
  color: var(--color-text-muted);
  font-size: 0.85rem;
}

.earnings_monthly_datagrid,
[data-grid^="earnings-monthly-"],
[data-grid^="member-reports-monthly-"] {
  width: min(96rem, 100%);
}

@media (max-width: 719px) {
  #daily_earnings,
  [id^="daily_earnings_"] {
    margin: var(--gap-sm, 0.5rem) 0;
    white-space: normal;
    overflow: visible;
  }

  #daily_earnings .earnings_daily_datagrid,
  [id^="daily_earnings_"] .earnings_daily_datagrid,
  [data-grid^="earnings-daily-"],
  [data-grid^="member-reports-daily-"],
  [data-grid^="earnings-monthly-"],
  [data-grid^="member-reports-monthly-"] {
    width: 100%;
    min-width: 0;
    max-width: 100%;
  }

  [data-grid^="earnings-daily-"] .datagrid_table,
  [data-grid^="member-reports-daily-"] .datagrid_table,
  [data-grid^="earnings-monthly-"] .datagrid_table,
  [data-grid^="member-reports-monthly-"] .datagrid_table,
  [data-grid^="earnings-daily-"] .datagrid_body,
  [data-grid^="member-reports-daily-"] .datagrid_body,
  [data-grid^="earnings-monthly-"] .datagrid_body,
  [data-grid^="member-reports-monthly-"] .datagrid_body {
    width: 100%;
  }

  [data-grid^="earnings-daily-"] .datagrid_body,
  [data-grid^="member-reports-daily-"] .datagrid_body,
  [data-grid^="earnings-monthly-"] .datagrid_body,
  [data-grid^="member-reports-monthly-"] .datagrid_body {
    display: grid;
    gap: 0.62rem;
  }

  [data-grid^="earnings-daily-"] .datagrid_row,
  [data-grid^="member-reports-daily-"] .datagrid_row,
  [data-grid^="earnings-monthly-"] .datagrid_row,
  [data-grid^="member-reports-monthly-"] .datagrid_row {
    width: 100%;
  }

  [data-grid^="earnings-daily-"] .datagrid_row_content,
  [data-grid^="member-reports-daily-"] .datagrid_row_content,
  [data-grid^="earnings-monthly-"] .datagrid_row_content,
  [data-grid^="member-reports-monthly-"] .datagrid_row_content {
    display: grid;
    gap: 0.16rem;
    width: 100%;
    padding: 0.62rem 0.68rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm, 6px);
    background: var(--surface);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--panel-border) 12%, transparent);
  }

  [data-grid^="earnings-daily-"] .datagrid_item,
  [data-grid^="member-reports-daily-"] .datagrid_item,
  [data-grid^="earnings-monthly-"] .datagrid_item,
  [data-grid^="member-reports-monthly-"] .datagrid_item {
    display: grid;
    grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
    align-items: baseline;
    justify-content: stretch;
    gap: 0.7rem;
    width: 100%;
    padding: 0.13rem 0;
    color: var(--color-text);
    font-variant-numeric: tabular-nums;
    text-align: right;
    white-space: normal;
    overflow-wrap: anywhere;
  }

  [data-grid^="earnings-monthly-"] .datagrid_item::before,
  [data-grid^="member-reports-monthly-"] .datagrid_item::before,
  [data-grid^="earnings-daily-"] .datagrid_item::before,
  [data-grid^="member-reports-daily-"] .datagrid_item::before {
    content: attr(data-col-label);
    color: var(--color-text-muted);
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    line-height: 1.2;
    text-align: left;
    text-transform: uppercase;
  }

  [data-grid^="earnings-daily-"] .datagrid_col_date,
  [data-grid^="member-reports-daily-"] .datagrid_col_date,
  [data-grid^="earnings-monthly-"] .datagrid_col_month,
  [data-grid^="member-reports-monthly-"] .datagrid_col_month {
    margin-bottom: 0.14rem;
    padding-bottom: 0.28rem;
    border-bottom: 1px solid color-mix(in srgb, var(--panel-border) 28%, transparent);
    font-weight: 900;
  }
}

.pay-period-cards {
  --pay-period-carousel-control-size: 2.35rem;
  --pay-period-carousel-marker-size: 0.72rem;
  --pay-period-carousel-gap: 0.75rem;
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: 100%;
  gap: var(--pay-period-carousel-gap);
  width: 100%;
  overflow-x: auto;
  overscroll-behavior-inline: contain;
  scroll-snap-type: x mandatory;
  scroll-behavior: smooth;
  scroll-padding-inline: 0;
  scrollbar-gutter: stable both-edges;
  padding: 0.85rem;
  margin: 0;
  align-items: stretch;
  scroll-marker-group: after;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 6px);
  background: var(--surface);
}

.pay-period-card {
  display: inline-flex;
  flex-direction: column;
  inline-size: 100%;
  min-inline-size: 100%;
  max-inline-size: 100%;
  gap: 0.4rem;
  padding: 0.65rem;
  border: none;
  border-radius: 0;
  background: transparent;
  scroll-snap-align: start;
  scroll-snap-stop: always;
}

@container (min-width: 980px) {
  .pay-period-cards {
    grid-auto-columns: calc((100% - var(--pay-period-carousel-gap)) / 2);
  }

  .pay-period-card {
    min-inline-size: 0;
  }
}

.pay-period-cards::scroll-button(left),
.pay-period-cards::scroll-button(right) {
  inline-size: var(--pay-period-carousel-control-size);
  block-size: var(--pay-period-carousel-control-size);
  border: 1px solid var(--border);
  border-radius: 999px;
  background: var(--surface);
  color: var(--color-text);
  cursor: pointer;
  transition: background-color 150ms ease, border-color 150ms ease, opacity 150ms ease;
}

.pay-period-cards::scroll-button(left) {
  content: "\2039";
  margin-inline: auto 0.5rem;
}

.pay-period-cards::scroll-button(right) {
  content: "\203A";
  margin-inline: 0.5rem auto;
}

.pay-period-cards::scroll-button(left):hover,
.pay-period-cards::scroll-button(right):hover {
  background: color-mix(in srgb, var(--surface, #1e2633) 68%, var(--primary, #0a84ff) 32%);
  border-color: color-mix(in srgb, var(--border, rgba(255, 255, 255, 0.2)) 40%, var(--primary, #0a84ff) 60%);
}

.pay-period-cards::scroll-button(left):disabled,
.pay-period-cards::scroll-button(right):disabled {
  opacity: 0.45;
  cursor: default;
}

.pay-period-cards > .pay-period-card::scroll-marker {
  content: "";
  inline-size: var(--pay-period-carousel-marker-size);
  block-size: var(--pay-period-carousel-marker-size);
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-text-muted) 55%, transparent);
  border: 1px solid color-mix(in srgb, var(--border, rgba(255, 255, 255, 0.2)) 70%, transparent);
}

.pay-period-cards > .pay-period-card::scroll-marker:target-current {
  background: var(--primary, #0a84ff);
  border-color: var(--primary, #0a84ff);
}

.pay-period-cards::scroll-marker-group {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  margin-block-start: 0.35rem;
}

.pay-period-card_title {
  margin: 0;
  font-size: 0.95rem;
}

.pay-period-card_exports {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin-top: 0.15rem;
  padding: 0.45rem 0;
  border-top: 1px solid var(--border, rgba(255, 255, 255, 0.2));
  border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.2));
}

.pay-period-card_exports .paycal_export_btn {
  padding: 0.15rem 0.35rem;
}

.pay-period-card_hours,
.pay-period-card_totals {
  display: grid;
  gap: 0.2rem;
}

.pay-period-card_row {
  width: 100%;
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.85rem;
}

.pay-period-card_label {
  text-align: left;
}

.pay-period-card_value {
  margin-left: auto;
  text-align: right;
  white-space: nowrap;
}

.pay-period-card_totals {
  margin-top: 0.15rem;
  padding-top: 0.35rem;
  border-top: 1px solid var(--border, rgba(255, 255, 255, 0.2));
}

.pay-period-card_sites {
  margin-top: 0.15rem;
  padding-top: 0.35rem;
  border-top: 1px solid var(--border, rgba(255, 255, 255, 0.2));
}

.pay-period-card_sites ul {
  margin: 0.25rem 0 0;
  padding-left: 1.1rem;
}

.pay-period-card_rates {
  display: grid;
  gap: 0.2rem;
  margin-top: 0.15rem;
  padding-top: 0.35rem;
  border-top: 1px solid var(--border, rgba(255, 255, 255, 0.2));
}

.pay-period-empty {
  margin: 0;
  color: var(--color-text-muted);
}

@media (max-width: 900px) {
  .earnings_metrics_column {
    flex-basis: 100%;
    min-width: 100%;
  }
}

@media (prefers-reduced-motion: reduce) {
  .pay-period-cards {
    scroll-behavior: auto;
  }
}

/* Earnings SVG helpers (avoid inline styles) */
.earnings-crosshair {
  cursor: crosshair;
}

svg[data-compact-chart="true"] .earnings-crosshair {
  cursor: pointer;
  touch-action: none;
}

.svg-hidden {
  visibility: hidden;
}

/* =========================================================
   Forecast Tab
   ========================================================= */

.forecast_intro {
  margin: 0 0 0.9rem;
  font-size: 0.9rem;
  color: var(--text-muted, var(--color-text-muted));
}

.forecast_setup_notice {
  margin: 0.5rem 0;
  color: var(--text-muted, var(--color-text-muted));
  font-style: italic;
}

.forecast_setup_notice a {
  color: var(--primary, var(--color-primary));
  text-decoration: underline;
}

.forecast-datagrid {
  --datagrid_cols: 4;
  --grid-template-columns: minmax(140px, 1.6fr) repeat(3, minmax(110px, 1fr));
  margin: 0 0 1rem;
}

.forecast_gross {
  font-weight: 600;
}

.forecast_net {
  font-weight: 700;
  color: var(--primary, var(--color-primary));
}

.forecast_tax {
  color: var(--text-muted, var(--color-text-muted));
}

.forecast_estimate_disclaimer {
  margin: 0.5rem 0 0;
  font-size: 0.8rem;
  color: var(--text-muted, var(--color-text-muted));
  font-style: italic;
  max-width: 54rem;
}

.forecast-workspace__header {
  margin-bottom: 1rem;
}

.forecast-workspace__title {
  margin: 0 0 0.35rem;
  font-size: 1.25rem;
  font-weight: 700;
}

.forecast-workspace__subtitle {
  margin: 0 0 0.75rem;
  color: var(--text-muted, var(--color-text-muted));
  font-size: 0.9rem;
  max-width: 52rem;
}

.forecast-workspace__badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.forecast-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.15rem 0.55rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.forecast-badge--estimate {
  background: color-mix(in srgb, var(--primary, #3b82f6) 18%, transparent);
  color: var(--primary, #3b82f6);
}

.forecast-badge--caution {
  background: color-mix(in srgb, var(--warning, #f59e0b) 16%, transparent);
  color: var(--warning, #d97706);
}

.forecast-workspace__grid {
  display: grid;
  gap: 1rem;
}

.forecast-workspace__summary {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
}

.forecast-summary-card {
  border: 1px solid var(--border-muted, rgba(255, 255, 255, 0.08));
  border-radius: 0.65rem;
  padding: 0.85rem 1rem;
  background: var(--bg-elevated, rgba(255, 255, 255, 0.03));
  transition: box-shadow 180ms ease;
}

.forecast-summary-card__title {
  margin: 0 0 0.35rem;
  font-size: 0.85rem;
  color: var(--text-muted, var(--color-text-muted));
  font-weight: 600;
}

.forecast-summary-card__net {
  margin: 0 0 0.5rem;
  font-size: 1.45rem;
  font-weight: 800;
  color: var(--primary, #3b82f6);
}

.forecast-summary-card__meta {
  margin: 0;
  display: grid;
  gap: 0.25rem;
  font-size: 0.8rem;
}

.forecast-summary-card__meta div {
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;
}

.forecast-summary-card__meta dt {
  color: var(--text-muted, var(--color-text-muted));
}

.forecast-summary-card__meta dd {
  margin: 0;
  font-weight: 600;
}

.forecast-summary-card__progress {
  margin-top: 0.75rem;
  display: grid;
  gap: 0.35rem;
}

.forecast-summary-card__progress-label {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  font-size: 0.76rem;
  color: var(--text-muted, var(--color-text-muted));
}

.forecast-summary-card__progress-bar {
  width: 100%;
  height: 0.42rem;
  overflow: hidden;
  appearance: none;
  border: 0;
  border-radius: 999px;
  background: color-mix(in srgb, var(--border-muted, rgba(255, 255, 255, 0.18)) 70%, transparent);
}

.forecast-summary-card__progress-bar::-webkit-progress-bar {
  border-radius: 999px;
  background: color-mix(in srgb, var(--border-muted, rgba(255, 255, 255, 0.18)) 70%, transparent);
}

.forecast-summary-card__progress-bar::-webkit-progress-value {
  border-radius: 999px;
  background: linear-gradient(90deg, var(--primary, #3b82f6), var(--color-success, #22c55e));
}

.forecast-summary-card__progress-bar::-moz-progress-bar {
  border-radius: 999px;
  background: linear-gradient(90deg, var(--primary, #3b82f6), var(--color-success, #22c55e));
}

.forecast-workspace__main,
.forecast-workspace__aside {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

@media (min-width: 960px) {
  .forecast-workspace__grid {
    grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
    grid-template-areas:
      "summary summary"
      "main aside";
  }

  .forecast-workspace__summary { grid-area: summary; }
  .forecast-workspace__main { grid-area: main; }
  .forecast-workspace__aside { grid-area: aside; }
  .forecast_estimate_disclaimer { grid-column: 1 / -1; }
}

@media (max-width: 959px) {
  .forecast-workspace__summary {
    grid-template-columns: 1fr;
  }
}

.forecast-panel__heading {
  margin: 0 0 0.65rem;
  font-size: 1rem;
  font-weight: 700;
}

.forecast-calculator,
.forecast-scenarios,
.forecast-assumptions,
.forecast-timeline {
  border: 1px solid var(--border-muted, rgba(255, 255, 255, 0.08));
  border-radius: 0.65rem;
  padding: 0.85rem 1rem;
  background: var(--bg-elevated, rgba(255, 255, 255, 0.02));
}

.forecast-calc__form {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.65rem 0.85rem;
}

.forecast-calc__field label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.8rem;
  color: var(--text-muted, var(--color-text-muted));
}

.forecast-calc__field input,
.forecast-calc__field select {
  width: 100%;
}

.forecast-calc__actions {
  grid-column: 1 / -1;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.forecast-scenarios__cards {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.5rem;
}

.forecast-scenario-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.25rem;
  padding: 0.65rem 0.75rem;
  border: 1px solid var(--border-muted, rgba(255, 255, 255, 0.1));
  border-radius: 0.55rem;
  background: transparent;
  color: inherit;
  cursor: pointer;
  text-align: left;
}

.forecast-scenario-card:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

.forecast-scenario-card--active {
  border-color: var(--primary, #3b82f6);
  background: color-mix(in srgb, var(--primary, #3b82f6) 12%, transparent);
}

.forecast-scenario-card__label {
  font-size: 0.8rem;
  color: var(--text-muted, var(--color-text-muted));
}

.forecast-scenario-card__net {
  font-size: 1rem;
  font-weight: 700;
  color: var(--primary, #3b82f6);
}

.forecast-assumptions-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
}

.forecast-assumptions-table th,
.forecast-assumptions-table td {
  padding: 0.35rem 0.4rem;
  border-bottom: 1px solid var(--border-muted, rgba(255, 255, 255, 0.06));
  text-align: left;
  vertical-align: top;
}

.forecast-source-badge {
  display: inline-block;
  padding: 0.1rem 0.4rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 600;
}

.forecast-source-badge--saved { color: #22c55e; background: rgba(34, 197, 94, 0.12); }
.forecast-source-badge--scheduled { color: #38bdf8; background: rgba(56, 189, 248, 0.12); }
.forecast-source-badge--temporary { color: #f59e0b; background: rgba(245, 158, 11, 0.12); }
.forecast-source-badge--estimated { color: #a78bfa; background: rgba(167, 139, 250, 0.12); }
.forecast-source-badge--missing { color: #94a3b8; background: rgba(148, 163, 184, 0.12); }

.forecast-timeline__segment {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 0.25rem 0.75rem;
  margin-bottom: 0.65rem;
}

.forecast-timeline__bar-wrap {
  grid-column: 1 / -1;
  height: 0.55rem;
  border-radius: 999px;
  background: var(--bg-muted, rgba(255, 255, 255, 0.06));
  overflow: hidden;
}

.forecast-timeline__bar {
  height: 100%;
  border-radius: 999px;
  background: var(--primary, #3b82f6);
  transition: width 520ms cubic-bezier(0.34, 1.56, 0.64, 1);
  animation: forecastTimelineFill 620ms ease-out both;
}

.forecast-timeline__bar--w-1 { width: 8.33%; }
.forecast-timeline__bar--w-2 { width: 16.66%; }
.forecast-timeline__bar--w-3 { width: 25%; }
.forecast-timeline__bar--w-4 { width: 33.33%; }
.forecast-timeline__bar--w-5 { width: 41.66%; }
.forecast-timeline__bar--w-6 { width: 50%; }
.forecast-timeline__bar--w-7 { width: 58.33%; }
.forecast-timeline__bar--w-8 { width: 66.66%; }
.forecast-timeline__bar--w-9 { width: 75%; }
.forecast-timeline__bar--w-10 { width: 83.33%; }
.forecast-timeline__bar--w-11 { width: 91.66%; }
.forecast-timeline__bar--w-12 { width: 100%; }

.forecast-timeline__label {
  font-size: 0.82rem;
}

.forecast-timeline__value {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--primary, #3b82f6);
}

@media (max-width: 640px) {
  .forecast-calc__form,
  .forecast-scenarios__cards {
    grid-template-columns: 1fr;
  }
}

.svg-visible {
  visibility: visible;
}
/* SITES PAGE - EARNINGS ANALYTICS PANEL */
.site_name_bold {
  flex: 1;
  font-weight: bold;
}

.site_archived_badge {
  font-size: 0.8em;
  color: var(--text-muted);
}

.site_earnings_amount {
  font-weight: bold;
  color: var(--primary);
}

.site_earnings_bar {
  height: 8px;
  margin-bottom: var(--mar-xs);
  border-radius: 4px;
  background: var(--bg-muted);
  overflow: hidden;
}

.site_earnings_bar_fill {
  width: 0%;
  height: 100%;
  background: var(--primary);
  transform-origin: left center;
  transition: width 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
  animation: siteEarningsFillConfirm 680ms ease-out both;
}

/* Width data attributes for earnings bars */
.site_earnings_bar_fill[data-width="0"] { width: 0%; }
.site_earnings_bar_fill[data-width="1"] { width: 1%; }
.site_earnings_bar_fill[data-width="2"] { width: 2%; }
.site_earnings_bar_fill[data-width="3"] { width: 3%; }
.site_earnings_bar_fill[data-width="4"] { width: 4%; }
.site_earnings_bar_fill[data-width="5"] { width: 5%; }
.site_earnings_bar_fill[data-width="10"] { width: 10%; }
.site_earnings_bar_fill[data-width="15"] { width: 15%; }
.site_earnings_bar_fill[data-width="20"] { width: 20%; }
.site_earnings_bar_fill[data-width="25"] { width: 25%; }
.site_earnings_bar_fill[data-width="30"] { width: 30%; }
.site_earnings_bar_fill[data-width="35"] { width: 35%; }
.site_earnings_bar_fill[data-width="40"] { width: 40%; }
.site_earnings_bar_fill[data-width="45"] { width: 45%; }
.site_earnings_bar_fill[data-width="50"] { width: 50%; }
.site_earnings_bar_fill[data-width="55"] { width: 55%; }
.site_earnings_bar_fill[data-width="60"] { width: 60%; }
.site_earnings_bar_fill[data-width="65"] { width: 65%; }
.site_earnings_bar_fill[data-width="70"] { width: 70%; }
.site_earnings_bar_fill[data-width="75"] { width: 75%; }
.site_earnings_bar_fill[data-width="80"] { width: 80%; }
.site_earnings_bar_fill[data-width="85"] { width: 85%; }
.site_earnings_bar_fill[data-width="90"] { width: 90%; }
.site_earnings_bar_fill[data-width="95"] { width: 95%; }
.site_earnings_bar_fill[data-width="100"] { width: 100%; }

@keyframes siteEarningsFillConfirm {
  0% {
    transform: scaleX(0.96);
    opacity: 0.82;
  }
  70% {
    transform: scaleX(1.015);
    opacity: 1;
  }
  100% {
    transform: scaleX(1);
  }
}

@keyframes forecastTimelineFill {
  from {
    transform: scaleX(0.92);
    opacity: 0.76;
  }
  to {
    transform: scaleX(1);
    opacity: 1;
  }
}

.totals_summary {
  padding: var(--pad-md);
  border-radius: var(--border-radius);
  background: var(--bg-highlight);
}
.totals_label_large {
  font-size: 1.1em;
  font-weight: bold;
}

.totals_amount_large {
  font-size: 1.1em;
  font-weight: bold;
  color: var(--primary);
}
.totals_stat_label {
  color: var(--text-muted);
}

.totals_stat_value {
  font-weight: bold;
}

/* ============================================================================
   EARNINGS VIEW TABS — My Earnings / Business Reports toggle
   ============================================================================ */

.earnings_view_tabs {
  display: flex;
  align-items: stretch;
  justify-content: space-between;
  gap: 0;
  margin: 0 0 0.75rem;
  border-bottom: 2px solid var(--border);
  padding-bottom: 0;
}

.earnings_view_tabs_links {
  display: flex;
  align-items: stretch;
  gap: 0;
}

/* Org select in tab bar */
.earnings_tab_org_form {
  display: flex;
  align-items: center;
  padding: 0 0 4px;
  margin-bottom: -2px;
}

.earnings_tab_org_select {
  padding: 0.25rem 0.5rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 4px);
  background: var(--surface);
  color: var(--color-text);
  font-size: var(--font-sm, 0.84rem);
  cursor: pointer;
  max-width: 22rem;
}

.earnings_view_tab {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.5rem 1.1rem;
  font-size: var(--font-md, 0.9rem);
  font-weight: 500;
  color: var(--color-text-muted);
  text-decoration: none;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  border-radius: var(--radius-sm, 4px) var(--radius-sm, 4px) 0 0;
  transition: color 0.12s ease, border-color 0.12s ease, background-color 0.12s ease;
}

.earnings_view_tab:hover {
  color: var(--color-text);
  background-color: color-mix(in srgb, var(--color-primary) 8%, transparent);
}

.earnings_view_tab.active {
  color: var(--color-primary);
  border-bottom-color: var(--color-primary);
  font-weight: 600;
}

/* ============================================================================
   TEAM EARNINGS PANEL
   ============================================================================ */

.earnings_team_panel {
  width: 100%;
}

.earnings_team_panel [data-report-module][hidden] {
  display: none !important;
}

.earnings_team_year_row {
  display: flex;
  gap: 0.4rem;
  margin-bottom: 0.75rem;
}

.earnings_team_year_link {
  padding: 0.25rem 0.65rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 4px);
  font-size: var(--font-sm, 0.85rem);
  color: var(--color-text-muted);
  text-decoration: none;
  transition: background-color 0.1s ease, color 0.1s ease;
}

.earnings_team_year_link:hover {
  background-color: color-mix(in srgb, var(--color-primary) 10%, transparent);
  color: var(--color-text);
}

.earnings_team_year_link.active {
  background-color: var(--color-primary);
  color: var(--color-text-on-primary, #fff);
  border-color: var(--color-primary);
  font-weight: 600;
}

/* .earnings_team_org_selector removed — select is now in the tab bar */

.earnings_team_grid {
  width: 100%;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 6px);
  overflow: hidden;
}

.earnings_team_grid_header,
.earnings_team_grid_row,
.earnings_team_grid_totals {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
  gap: 0;
}

.earnings_team_grid_header {
  background: var(--elevated-surface, var(--surface));
  border-bottom: 1px solid var(--border);
}

.earnings_team_grid_header span {
  padding: 0.55rem 0.85rem;
  font-size: var(--font-sm, 0.82rem);
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.earnings_team_grid_header span:nth-child(n+3) {
  text-align: right;
}

.earnings_team_grid_row {
  border-bottom: 1px solid var(--border);
}

.earnings_team_grid_row:last-of-type {
  border-bottom: none;
}

.earnings_team_grid_row:hover {
  background-color: color-mix(in srgb, var(--color-primary) 5%, transparent);
}

.earnings_team_grid_row span {
  padding: 0.5rem 0.85rem;
  font-size: var(--font-sm, 0.88rem);
}

.earnings_team_grid_totals {
  border-top: 2px solid var(--border);
  background: var(--elevated-surface, var(--surface));
}

.earnings_team_grid_totals span {
  padding: 0.55rem 0.85rem;
  font-size: var(--font-sm, 0.88rem);
  font-weight: 600;
}

.earnings_team_num {
  text-align: right;
}

.earnings_team_role {
  color: var(--color-text-muted);
  font-size: var(--font-sm, 0.83rem);
}

.earnings_team_empty {
  color: var(--color-text-muted);
  font-style: italic;
  padding: 1rem 0;
}

.earnings_site_diag {
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 6px);
  background: var(--surface);
  padding: 0.75rem 0.85rem;
  margin: 0 0 0.85rem;
}

.earnings_site_diag--warning {
  border-color: color-mix(in srgb, #f97316 50%, var(--border));
  background: color-mix(in srgb, #f97316 6%, var(--surface));
}

.earnings_site_diag_header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.earnings_site_diag_title {
  margin: 0;
  font-size: var(--font-md, 0.93rem);
}

.earnings_site_diag_count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: 0.2rem 0.55rem;
  font-size: var(--font-sm, 0.78rem);
  font-weight: 600;
  color: var(--color-text-muted);
}

.earnings_site_diag--warning .earnings_site_diag_count {
  border-color: color-mix(in srgb, #f97316 45%, var(--border));
  color: #f97316;
}

.earnings_site_diag_summary {
  margin: 0.45rem 0 0;
  color: var(--color-text-muted);
  font-size: var(--font-sm, 0.85rem);
}

.earnings_site_diag_details {
  margin-top: 0.6rem;
}

.earnings_site_diag_details > summary {
  cursor: pointer;
  color: var(--color-primary);
  font-size: var(--font-sm, 0.84rem);
  font-weight: 600;
}

.earnings_site_diag_table_wrap {
  margin-top: 0.55rem;
  overflow-x: auto;
}

.earnings_site_diag_table {
  width: 100%;
  min-width: 32rem;
  border-collapse: collapse;
}

.earnings_site_diag_table th,
.earnings_site_diag_table td {
  border: 1px solid var(--border);
  padding: 0.38rem 0.45rem;
  text-align: left;
  font-size: var(--font-sm, 0.8rem);
  vertical-align: top;
}

.earnings_site_diag_table thead th {
  background: var(--elevated-surface, var(--surface));
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.earnings_site_resolve_summary {
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 6px);
  background: var(--surface);
  padding: 0.75rem 0.85rem;
  margin: 0 0 0.85rem;
}

.earnings_site_resolve_summary--warning {
  border-color: color-mix(in srgb, #f97316 50%, var(--border));
  background: color-mix(in srgb, #f97316 6%, var(--surface));
}

.earnings_site_resolve_summary_header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.75rem;
}

.earnings_site_resolve_summary_title {
  margin: 0;
  font-size: var(--font-md, 0.93rem);
}

.earnings_site_resolve_summary_subtitle {
  color: var(--color-text-muted);
  font-size: var(--font-sm, 0.8rem);
}

.earnings_site_resolve_summary_grid {
  margin: 0.6rem 0 0;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.45rem;
}

.earnings_site_resolve_stat {
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 5px);
  padding: 0.45rem 0.5rem;
  background: var(--elevated-surface, var(--surface));
}

.earnings_site_resolve_stat--warning {
  border-color: color-mix(in srgb, #f97316 45%, var(--border));
  background: color-mix(in srgb, #f97316 6%, var(--surface));
}

.earnings_site_resolve_stat dt {
  margin: 0;
  font-size: var(--font-sm, 0.74rem);
  color: var(--color-text-muted);
}

.earnings_site_resolve_stat dd {
  margin: 0.2rem 0 0;
  font-size: var(--font-md, 0.95rem);
  font-weight: 700;
  color: var(--color-text);
}

.earnings_site_resolve_summary_note {
  margin: 0.55rem 0 0;
  color: var(--color-text-muted);
  font-size: var(--font-sm, 0.82rem);
}

.earnings_site_resolve_summary_note--warning {
  color: #f97316;
  font-weight: 600;
}

@media (max-width: 860px) {
  .earnings_site_resolve_summary_grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

/* ============================================================================
   TEAM EARNINGS EMPTY-STATE / SKELETON
   ============================================================================ */

.et_empty_state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.6rem;
  padding: 2rem 1.5rem 1rem;
  text-align: center;
}

.et_empty_guard {
  margin: 0 auto 0.8rem;
  max-width: 56rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 6px);
  padding: 0.7rem 0.85rem;
  font-size: var(--font-sm, 0.84rem);
  line-height: 1.45;
}

.et_empty_guard--warning {
  border-color: color-mix(in srgb, #f97316 50%, var(--border));
  background: color-mix(in srgb, #f97316 6%, var(--surface));
  color: #f97316;
}

.et_empty_icon {
  font-size: 2.4rem;
  line-height: 1;
  opacity: 0.55;
  margin-bottom: 0.2rem;
  user-select: none;
  aria-hidden: true;
}

.et_empty_title {
  font-size: var(--font-lg, 1.05rem);
  font-weight: 600;
  color: var(--color-text);
  margin: 0;
}

.et_empty_body {
  font-size: var(--font-sm, 0.88rem);
  color: var(--color-text-muted);
  max-width: 38ch;
  line-height: 1.55;
  margin: 0;
}

.et_empty_steps {
  margin: 0.75rem 0 0;
  padding: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  text-align: left;
  width: 100%;
  max-width: 30rem;
}

.et_empty_steps li {
  display: flex;
  align-items: flex-start;
  gap: 0.55rem;
  font-size: var(--font-sm, 0.84rem);
  color: var(--color-text-muted);
  line-height: 1.45;
}

.et_empty_steps li::before {
  content: "→";
  color: var(--color-primary);
  font-weight: 700;
  flex-shrink: 0;
  line-height: 1.45;
}

/* Skeleton wrapper inside an earnings_ytd_figure */
.et_skeleton_figure {
  display: flex;
  flex-direction: column;
  gap: 0;
  margin: 0 0 var(--gap-md, 1.25rem);
  min-height: 11.5rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 6px);
  overflow: hidden;
  opacity: 0.65;
  box-sizing: border-box;
}

.et_skeleton_header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 1.5rem 0.75rem;
  border-bottom: 1px solid var(--border);
  background: var(--elevated-surface, var(--surface));
}

.et_skeleton_body {
  padding: 1.25rem 1.75rem 1.5rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}

/* Skeleton exec snapshot strip */
.et_skeleton_exec {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 0.5rem;
  min-height: 5.5rem;
  padding: 1rem 0 1.25rem;
  box-sizing: border-box;
}

.et_skeleton_exec_item {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  padding: 0.6rem 0.8rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 4px);
}

/* Bar chart skeleton */
.et_skeleton_bars {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  height: 120px;
  padding: 0 0 0.25rem;
}

/* Line chart skeleton */
.et_skeleton_line_path {
  display: block;
  width: 100%;
  height: 100px;
  position: relative;
}

/* Skeleton member grid rows */
.et_skeleton_grid_rows {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.et_skeleton_grid_row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
  gap: 0.5rem;
  padding: 0.55rem 0;
  border-bottom: 1px solid var(--border);
}

/* Business reports loading skeleton (SSR shell before analytics render) */
.business_reports_panel_skeleton {
  opacity: 0.85;
}

.business_reports_skeleton_year_row {
  align-items: center;
}

.reports_sk_year_chip {
  height: 1.85rem;
  width: 3.25rem;
  border-radius: var(--radius-sm, 4px);
}

.reports_sk_export_btn {
  height: 1.85rem;
  width: 7.5rem;
  margin-left: auto;
}

.reports_sk_bar {
  flex: 1;
  opacity: 0.55;
}

.reports_sk_exec_value {
  height: 1.8rem;
  margin-bottom: 0.15rem;
}

.reports_sk_exec_sub {
  height: 0.6em;
}

.reports_sk_figure_title {
  height: 1em;
  width: 9rem;
}

.reports_sk_figure_title_wide {
  width: 11rem;
}

.reports_sk_figure_title_narrow {
  width: 8rem;
}

.reports_sk_figure_subtitle {
  height: 0.75em;
  flex: 1;
}

.reports_sk_caption_line {
  height: 0.65em;
  margin-top: 0.4rem;
}

.reports_sk_bar_primary {
  opacity: 0.6;
}

.reports_sk_bar_secondary {
  opacity: 0.5;
}

.reports_sk_bar {
  flex: 1;
  opacity: 0.55;
}

.reports_sk_bar_h_30 { height: 30%; }
.reports_sk_bar_h_35 { height: 35%; }
.reports_sk_bar_h_40 { height: 40%; }
.reports_sk_bar_h_45 { height: 45%; }
.reports_sk_bar_h_50 { height: 50%; }
.reports_sk_bar_h_55 { height: 55%; }
.reports_sk_bar_h_60 { height: 60%; }
.reports_sk_bar_h_65 { height: 65%; }
.reports_sk_bar_h_70 { height: 70%; }
.reports_sk_bar_h_75 { height: 75%; }
.reports_sk_bar_h_80 { height: 80%; }
.reports_sk_bar_h_85 { height: 85%; }
.reports_sk_bar_h_88 { height: 88%; }
.reports_sk_bar_h_90 { height: 90%; }
.reports_sk_bar_h_95 { height: 95%; }

.reports_sk_grid_head_cell {
  height: 0.75em;
  display: inline-block;
  width: 60%;
}

/* ============================================================================
   END TEAM EARNINGS EMPTY-STATE / SKELETON
   ============================================================================ */

/* Clickable row affordance */
.earnings_team_grid_row--clickable {
  cursor: pointer;
  outline: none;
}

.earnings_team_grid_row--clickable:hover {
  background-color: color-mix(in srgb, var(--color-primary) 8%, transparent);
}

.earnings_team_grid_row--clickable:focus-visible {
  background-color: color-mix(in srgb, var(--color-primary) 8%, transparent);
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: -2px;
}

.earnings_team_grid_row--clickable:active {
  background-color: color-mix(in srgb, var(--color-primary) 15%, transparent);
}

@media (max-width: 640px) {
  .earnings_team_grid_header,
  .earnings_team_grid_row,
  .earnings_team_grid_totals {
    grid-template-columns: 2fr 1fr 1fr;
  }

  /* Hide Role and Regular Hrs columns on small screens */
  .earnings_team_grid_header span:nth-child(2),
  .earnings_team_grid_row span:nth-child(2),
  .earnings_team_grid_totals span:nth-child(2),
  .earnings_team_grid_header span:nth-child(3),
  .earnings_team_grid_row span:nth-child(3),
  .earnings_team_grid_totals span:nth-child(3) {
    display: none;
  }
}

/* ============================================================================
   YTD ORG CHART
   ============================================================================ */

.earnings_ytd_figure {
  display: flex;
  flex-direction: column;
  gap: 0;
  margin: 0 0 var(--gap-md, 1.25rem);
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 6px);
  box-shadow: 0 2px 8px color-mix(in srgb, black 8%, transparent),
              0 1px 2px color-mix(in srgb, black 5%, transparent);
  overflow: hidden;
}

.earnings_ytd_figure.business_reports_module--insufficient-history .earnings_ytd_body {
  display: none;
}

.business_reports_insufficient_history {
  margin: 0;
  padding: 1rem 1.5rem;
  color: var(--color-text-muted);
  font-size: var(--font-sm, 0.86rem);
}

.earnings_ytd_header {
  display: flex;
  align-items: baseline;
  gap: 0.6rem;
  padding: 0.85rem 1.5rem 0.75rem;
  border-bottom: 1px solid var(--border);
  background: var(--elevated-surface, var(--surface));
}

.earnings_ytd_title {
  font-size: var(--font-md, 0.95rem);
  font-weight: 600;
  color: var(--color-text);
}

.earnings_ytd_subtitle {
  font-size: var(--font-sm, 0.83rem);
  color: var(--color-text-muted);
}

.earnings_ytd_body {
  display: flex;
  flex-direction: row;
  align-items: stretch;
  gap: 0;
  padding-block: 1.5rem;
  padding-inline: var(--page-edge-inner-inline);
}

.earnings_ytd_svg {
  flex: 1 1 0;
  min-width: 0;
  height: auto;
  display: block;
  overflow: visible;
}

/* Grid */
.ytd_grid {
  stroke: var(--border);
  stroke-width: 1;
}

.ytd_grid--vert {
  stroke-dasharray: 2 4;
  opacity: 0.4;
}

/* Axis border lines */
.ytd_axis_border {
  stroke: var(--border);
  stroke-width: 1.5;
}

/* Axis labels */
.ytd_axis_label {
  fill: var(--color-text-muted);
  font-size: 10px;
  font-family: inherit;
}

.ytd_axis_label--left  { text-anchor: end; }
.ytd_axis_label--right { text-anchor: start; }
.ytd_axis_label--x     { text-anchor: middle; dominant-baseline: hanging; }

/* Series lines */
.ytd_line {
  stroke-width: 2.5;
  stroke-linejoin: round;
  stroke-linecap: round;
}

.ytd_line--gross    { stroke: var(--color-primary); }
.ytd_line--net      { stroke: #22c55e; }
.ytd_line--avg      { stroke: #06b6d4; stroke-width: 2; stroke-dasharray: 5 3; }
.ytd_line--cum      { stroke: #0ea5e9; stroke-width: 2; stroke-dasharray: 8 4; }
.ytd_line--reg      { stroke: #f59e0b; stroke-width: 2; }
.ytd_line--ot       { stroke: #ef4444; stroke-width: 2; }
.ytd_line--ot_ratio { stroke: #f97316; stroke-width: 2; stroke-dasharray: 4 3; }
.ytd_line--util     { stroke: #22c55e; stroke-width: 2.5; }

/* Data point dots */
.ytd_dot--gross    { fill: var(--color-primary); }
.ytd_dot--net      { fill: #22c55e; }
.ytd_dot--avg      { fill: #06b6d4; }
.ytd_dot--cum      { fill: #0ea5e9; }
.ytd_dot--reg      { fill: #f59e0b; }
.ytd_dot--ot       { fill: #ef4444; }
.ytd_dot--ot_ratio { fill: #f97316; }
.ytd_dot--util     { fill: #22c55e; }

/* Bar fills */
.ytd_bar--reg       { fill: #f59e0b; opacity: 0.8; }
.ytd_bar--ot        { fill: #ef4444; opacity: 0.85; }
.ytd_bar--headcount { fill: var(--color-primary); opacity: 0.6; }

/* Horizontal rank bars */
.ytd_rank_bar   { fill: var(--color-primary); opacity: 0.65; }
.ytd_rank_name  { font-size: 11px; font-family: inherit; }
.ytd_rank_value { fill: var(--color-text-muted, #6b7280); font-size: 10px; font-family: inherit; text-anchor: start; }

/* Percentage right axis */
.ytd_axis_label--pct  { fill: #94a3b8; }
.ytd_axis_border--pct { stroke: #94a3b8; opacity: 0.4; }

/* Axis tag marks ($ h # %) */
.ytd_axis_tag { font-weight: 700; font-size: 9px; }

/* Series toggle — scoped to each panel's .earnings_ytd_body */
.earnings_ytd_body[data-hide-gross]       [data-series="gross"]       { display: none; }
.earnings_ytd_body[data-hide-net]         [data-series="net"]         { display: none; }
.earnings_ytd_body[data-hide-avg]         [data-series="avg"]         { display: none; }
.earnings_ytd_body[data-hide-cum]         [data-series="cum"]         { display: none; }
.earnings_ytd_body[data-hide-reg]         [data-series="reg"]         { display: none; }
.earnings_ytd_body[data-hide-ot]          [data-series="ot"]          { display: none; }
.earnings_ytd_body[data-hide-ot_ratio]    [data-series="ot_ratio"]    { display: none; }
.earnings_ytd_body[data-hide-headcount]   [data-series="headcount"]   { display: none; }
.earnings_ytd_body[data-hide-utilization] [data-series="utilization"] { display: none; }

/* Controls / legend */
.earnings_ytd_controls {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  flex-shrink: 0;
  width: 10.5rem;
  padding-left: 1.5rem;
  margin-left: 1.5rem;
  border-left: 1px solid var(--border);
  justify-content: center;
}

.ytd_legend_item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.28rem 0.65rem 0.28rem 0.5rem;
  font-size: var(--font-sm, 0.83rem);
  color: var(--color-text-muted);
  background: color-mix(in srgb, var(--surface) 80%, transparent);
  border: 1px solid var(--border);
  border-radius: 999px;
  box-shadow: 0 1px 3px color-mix(in srgb, black 10%, transparent),
              0 1px 1px color-mix(in srgb, black 6%, transparent);
  cursor: pointer;
  user-select: none;
  transition: opacity 0.15s, box-shadow 0.15s, background 0.15s;
}

.ytd_legend_item:hover {
  background: var(--surface);
  box-shadow: 0 2px 6px color-mix(in srgb, black 14%, transparent),
              0 1px 2px color-mix(in srgb, black 8%, transparent);
}

/* Dim the pill when its series is hidden */
.ytd_legend_item:has(.ytd_legend_checkbox:not(:checked)) {
  opacity: 0.4;
  box-shadow: none;
}

/* Visually hide the checkbox (keep accessible) */
.ytd_legend_checkbox {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
  pointer-events: none;
}

/* Colour swatch — filled by per-series rules below */
.ytd_legend_swatch {
  display: inline-block;
  width: 18px;
  height: 3px;
  border-radius: 2px;
  flex-shrink: 0;
}

.ytd_legend_item--gross        .ytd_legend_swatch { background: var(--color-primary); }
.ytd_legend_item--net          .ytd_legend_swatch { background: #22c55e; }
.ytd_legend_item--avg          .ytd_legend_swatch { background: #06b6d4; }
.ytd_legend_item--cum          .ytd_legend_swatch { background: #0ea5e9; }
.ytd_legend_item--total        .ytd_legend_swatch { background: #8b5cf6; }
.ytd_legend_item--reg          .ytd_legend_swatch { background: #f59e0b; }
.ytd_legend_item--ot           .ytd_legend_swatch { background: #ef4444; }
.ytd_legend_item--ot_ratio     .ytd_legend_swatch { background: #f97316; }
.ytd_legend_item--headcount    .ytd_legend_swatch { background: var(--color-primary); }
.ytd_legend_item--utilization  .ytd_legend_swatch { background: #22c55e; }

.ytd_legend_item small {
  opacity: 0.7;
}

/* Rank bar SVG fills the full body width */
.earnings_ytd_body--rank {
  padding-right: 1.75rem;
}

.earnings_ytd_svg--rank {
  width: 100%;
}

/* ============================================================================
   TEAM MEMBER BREAKDOWN DIALOG
   ============================================================================ */

.earnings_team_member_dialog {
  width: min(calc(100vw - 2rem), 52rem);
}

.earnings_team_member_dialog_content {
  flex-direction: column;
  gap: 0;
  padding: var(--pad-md);
  overflow-y: auto;
  max-height: calc(100dvh - 12rem);
}

.earnings_breakdown_meta {
  margin-bottom: 0.85rem;
}

.earnings_breakdown_role {
  display: inline-block;
  padding: 0.2rem 0.6rem;
  background: color-mix(in srgb, var(--color-primary) 12%, transparent);
  color: var(--color-primary);
  border-radius: var(--radius-sm, 4px);
  font-size: var(--font-sm, 0.83rem);
  font-weight: 600;
}

.earnings_breakdown_empty {
  color: var(--color-text-muted);
  font-style: italic;
}

.earnings_breakdown_grid {
  width: 100%;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 6px);
  overflow: hidden;
}

.earnings_breakdown_header,
.earnings_breakdown_row,
.earnings_breakdown_totals {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
}

.earnings_breakdown_header {
  background: var(--elevated-surface, var(--surface));
  border-bottom: 1px solid var(--border);
}

.earnings_breakdown_header span {
  padding: 0.5rem 0.75rem;
  font-size: var(--font-sm, 0.82rem);
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.earnings_breakdown_header span:nth-child(n+2) {
  text-align: right;
}

.earnings_breakdown_row {
  border-bottom: 1px solid var(--border);
}

.earnings_breakdown_row:last-of-type {
  border-bottom: none;
}

.earnings_breakdown_row span {
  padding: 0.45rem 0.75rem;
  font-size: var(--font-sm, 0.88rem);
}

.earnings_breakdown_totals {
  border-top: 2px solid var(--border);
  background: var(--elevated-surface, var(--surface));
}

.earnings_breakdown_totals span {
  padding: 0.5rem 0.75rem;
  font-size: var(--font-sm, 0.88rem);
  font-weight: 600;
}

.earnings_breakdown_num {
  text-align: right;
}

/* ═══ Intelligence Row (Forecast + Workforce Health) ═══════════════════════ */
.et_intel_row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr));
  gap: var(--gap-md, 1rem);
  margin-bottom: var(--gap-md, 1.25rem);
}
.et_intel_card {
  display: flex;
  flex-direction: column;
  margin: 0;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 6px);
  box-shadow: 0 1px 4px color-mix(in srgb, var(--shadow, #000) 6%, transparent);
  overflow: hidden;
}
.et_intel_header {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 0.6rem;
  padding: 0.85rem 1.5rem 0.75rem;
  border-bottom: 1px solid var(--border);
  background: var(--elevated-surface, var(--surface));
}
.et_intel_title {
  font-size: var(--font-md, 0.95rem);
  font-weight: 600;
  color: var(--color-text);
}
.et_intel_subtitle {
  font-size: var(--font-sm, 0.83rem);
  color: var(--color-text-muted);
}

/* ── Forecast card ───────────────────────────────────────────────────────── */

/* Hero: Forecast Year End — most visually dominant element */
.et_forecast_hero {
  padding: 1.25rem 1.5rem 1rem;
  border-bottom: 1px solid var(--border);
}
.et_forecast_hero_amount {
  font-size: 2rem;
  font-weight: 800;
  color: var(--color-primary);
  letter-spacing: -0.02em;
  line-height: 1.1;
}
.et_forecast_hero_label {
  font-size: var(--font-sm, 0.8rem);
  color: var(--color-text-muted);
  margin-top: 0.2rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-weight: 600;
}

/* Assessment verdict strip */
.et_forecast_verdict {
  display: flex;
  align-items: baseline;
  gap: 0.6rem;
  padding: 0.6rem 1.5rem;
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
}
.et_forecast_verdict--ok {
  background: color-mix(in srgb, #22c55e 6%, transparent);
}
.et_forecast_verdict--over {
  background: color-mix(in srgb, #ef4444 6%, transparent);
}
.et_forecast_verdict_label {
  font-size: var(--font-sm, 0.87rem);
  font-weight: 700;
}
.et_forecast_verdict--ok .et_forecast_verdict_label   { color: #22c55e; }
.et_forecast_verdict--over .et_forecast_verdict_label { color: #ef4444; }
.et_forecast_verdict_detail {
  font-size: var(--font-sm, 0.82rem);
  color: var(--color-text-muted);
}

.et_forecast_dl {
  margin: 0;
  padding: 0.75rem 1.5rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0;
}
.et_forecast_row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 0.5rem;
  padding: 0.4rem 0;
  border-bottom: 1px solid color-mix(in srgb, var(--border) 40%, transparent);
}
.et_forecast_row:last-child {
  border-bottom: none;
}
.et_forecast_row--muted .et_forecast_label,
.et_forecast_row--muted .et_forecast_value {
  color: var(--color-text-muted);
  font-size: var(--font-sm, 0.85rem);
}
.et_forecast_label {
  font-size: var(--font-sm, 0.87rem);
  color: var(--color-text-muted);
}
.et_forecast_value {
  font-size: var(--font-sm, 0.87rem);
  font-weight: 500;
  color: var(--color-text);
  white-space: nowrap;
}

/* ── Health badge ────────────────────────────────────────────────────────── */
.et_health_badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.2rem 0.65rem;
  border-radius: 999px;
  font-size: var(--font-sm, 0.83rem);
  font-weight: 700;
  border: 1px solid transparent;
}
.et_health_badge--normal, .et_health_badge--healthy {
  background: color-mix(in srgb, #22c55e 12%, transparent);
  color: #22c55e;
  border-color: color-mix(in srgb, #22c55e 25%, transparent);
}
.et_health_badge--watch {
  background: color-mix(in srgb, #f59e0b 12%, transparent);
  color: #f59e0b;
  border-color: color-mix(in srgb, #f59e0b 25%, transparent);
}
.et_health_badge--concern {
  background: color-mix(in srgb, #f97316 12%, transparent);
  color: #f97316;
  border-color: color-mix(in srgb, #f97316 25%, transparent);
}
.et_health_badge--risk {
  background: color-mix(in srgb, #ef4444 12%, transparent);
  color: #ef4444;
  border-color: color-mix(in srgb, #ef4444 25%, transparent);
}

/* ── Health score row (numeric score + badge side by side) ───────────────── */
.et_health_score_row {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 1.1rem 1.5rem 0.9rem;
  border-bottom: 1px solid var(--border);
}
.et_health_score_num {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
  letter-spacing: -0.02em;
}
.et_health_score_num--normal, .et_health_score_num--healthy { color: #22c55e; }
.et_health_score_num--watch   { color: #f59e0b; }
.et_health_score_num--concern { color: #f97316; }
.et_health_score_num--risk    { color: #ef4444; }
.et_health_score_denom {
  font-size: 0.95rem;
  font-weight: 500;
  opacity: 0.55;
}

/* ── Health risks cause list ─────────────────────────────────────────────── */
.et_health_risks {
  margin: 0;
  padding: 0.65rem 1.5rem 0.65rem 1.5rem;
  list-style: none;
  border-bottom: 1px solid var(--border);
  background: color-mix(in srgb, #ef4444 4%, transparent);
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.et_health_risks li {
  font-size: var(--font-sm, 0.83rem);
  color: #ef4444;
  padding-left: 1.1em;
  position: relative;
}
.et_health_risks li::before {
  content: '→';
  position: absolute;
  left: 0;
  opacity: 0.7;
}

/* ── Workforce Health metric rows ────────────────────────────────────────── */
.et_health_dl {
  margin: 0;
  padding: 0.75rem 1.5rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0;
}
.et_health_row {
  display: grid;
  grid-template-columns: 1.4em 1fr auto;
  align-items: center;
  gap: 0.5rem;
  padding: 0.38rem 0;
  border-bottom: 1px solid color-mix(in srgb, var(--border) 40%, transparent);
}
.et_health_row:last-child {
  border-bottom: none;
}
.et_health_row--meta {
  opacity: 0.75;
}
.et_health_emoji {
  font-size: 0.85rem;
  line-height: 1;
  display: flex;
  align-items: center;
}
.et_health_label {
  font-size: var(--font-sm, 0.85rem);
  color: var(--color-text-muted);
}
.et_health_value {
  font-size: var(--font-sm, 0.85rem);
  font-weight: 600;
  color: var(--color-text);
  white-space: nowrap;
  text-align: right;
  display: flex;
  align-items: baseline;
  gap: 0.35rem;
  flex-direction: row;
  justify-content: flex-end;
}
.et_health_trend {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--color-text-muted);
  white-space: nowrap;
}

/* ═══ Variance & Trend Alerts — per-category panels with side-by-side cards ═ */
.et_alerts_figure {
  overflow: visible;
}
.et_alerts_cards {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 0.75rem;
  padding: 1rem 1.25rem 1.25rem;
  align-items: stretch;
}
.et_alerts_group {
  /* kept for any legacy reference; not used in current markup */
}
/* Individual alert card */
.et_alert_card {
  flex: 1 1 15rem;
  min-width: 14rem;
  max-width: 28rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-left: 3px solid var(--border);
  border-radius: var(--radius-sm, 6px);
  padding: 0.85rem 1rem 0.9rem;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  box-shadow: 0 1px 3px color-mix(in srgb, var(--shadow, #000) 5%, transparent);
}
.et_alert_card--critical { border-left-color: #ef4444; }
.et_alert_card--warning  { border-left-color: #f97316; }
.et_alert_card--notice   { border-left-color: #f59e0b; }
.et_alert_card--normal   { border-left-color: var(--border); }
.et_alert_card--positive { border-left-color: #22c55e; }
/* Card header */
.et_alert_card_header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}
.et_alert_card_title {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--color-text-muted);
}
/* Severity badge */
.et_alert_sev {
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  border: 1px solid transparent;
  white-space: nowrap;
}
.et_alert_sev--critical { background: color-mix(in srgb, #ef4444 12%, transparent); color: #ef4444; border-color: color-mix(in srgb, #ef4444 30%, transparent); }
.et_alert_sev--warning  { background: color-mix(in srgb, #f97316 12%, transparent); color: #f97316; border-color: color-mix(in srgb, #f97316 30%, transparent); }
.et_alert_sev--notice   { background: color-mix(in srgb, #f59e0b 12%, transparent); color: #f59e0b; border-color: color-mix(in srgb, #f59e0b 30%, transparent); }
.et_alert_sev--normal   { background: color-mix(in srgb, #22c55e 10%, transparent); color: #22c55e; border-color: color-mix(in srgb, #22c55e 25%, transparent); }
.et_alert_sev--positive { background: color-mix(in srgb, #22c55e 10%, transparent); color: #22c55e; border-color: color-mix(in srgb, #22c55e 25%, transparent); }
/* Change headline — the main number */
.et_alert_card_change {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--color-text);
  margin: 0;
  line-height: 1.15;
}
.et_alert_card--positive .et_alert_card_change {
  font-size: 1rem;
  color: #22c55e;
}
/* Before → after context line */
.et_alert_card_context {
  font-size: var(--font-sm, 0.82rem);
  color: var(--color-text-muted);
  margin: 0;
  font-variant-numeric: tabular-nums;
}
/* Cause explanation */
.et_alert_card_cause {
  font-size: var(--font-sm, 0.84rem);
  color: var(--color-text);
  margin: 0.35rem 0 0;
  line-height: 1.45;
}
/* Recommended action */
.et_alert_card_rec {
  font-size: var(--font-sm, 0.82rem);
  color: var(--color-primary);
  margin: 0.45rem 0 0;
  display: flex;
  align-items: flex-start;
  gap: 0.3rem;
  line-height: 1.35;
}
.et_alert_card_rec_arrow {
  flex-shrink: 0;
  margin-top: 0.05em;
}

/* Named worker callout */
.et_alert_names {
  margin: 0.5rem 0 0;
  padding: 0.5rem 0.65rem;
  background: color-mix(in srgb, var(--border) 30%, transparent);
  border-radius: var(--radius-sm, 5px);
  border-left: 2px solid color-mix(in srgb, var(--border) 70%, transparent);
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}
.et_alert_names_item {
  font-size: var(--font-sm, 0.81rem);
  color: var(--color-text-muted);
  font-style: italic;
  line-height: 1.35;
}

/* ═══ Cost Drivers panel ════════════════════════════════════════════════════ */
.et_cost_figure {
  overflow: hidden;
}
.et_cost_body {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));
  gap: 0;
  padding: 0;
}
.et_cost_col {
  padding: 1rem 1.5rem;
  border-right: 1px solid var(--border);
}
.et_cost_col:last-child {
  border-right: none;
}
.et_cost_col_title {
  margin: 0 0 0.6rem;
  font-size: var(--font-sm, 0.8rem);
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.et_cost_row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 0.5rem;
  padding: 0.35rem 0;
  border-bottom: 1px solid color-mix(in srgb, var(--border) 35%, transparent);
}
.et_cost_row:last-child {
  border-bottom: none;
}
.et_cost_name {
  font-size: var(--font-sm, 0.85rem);
  color: var(--color-text-muted);
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.et_cost_amount {
  font-size: var(--font-sm, 0.85rem);
  font-weight: 600;
  color: var(--color-text);
  white-space: nowrap;
  text-align: right;
}
.et_cost_amount--impact {
  color: #ef4444;
}

/* ═══ Site payroll cost chart ════════════════════════════════════════════════ */
/* ═══ Budget Status Panel ════════════════════════════════════════════════════ */
.et_budget_figure {
  overflow: hidden;
}
.et_budget_badge {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
  letter-spacing: 0.04em;
}
.et_budget_badge--ok       { background: color-mix(in srgb, #22c55e 12%, transparent); color: #22c55e; }
.et_budget_badge--warning  { background: color-mix(in srgb, #f97316 12%, transparent); color: #f97316; }
.et_budget_badge--critical { background: color-mix(in srgb, #ef4444 12%, transparent); color: #ef4444; }
.et_budget_body {
  padding: 1rem 1.5rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.et_budget_stat_row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 2.5rem;
}
.et_budget_stat {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}
.et_budget_stat_value {
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--color-text);
  letter-spacing: -0.01em;
}
.et_budget_stat_value--ok       { color: #22c55e; }
.et_budget_stat_value--warning  { color: #f97316; }
.et_budget_stat_value--critical { color: #ef4444; }
.et_budget_stat_label {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: var(--color-text-muted);
}
.et_budget_bar_wrap {
  position: relative;
}
.et_budget_bar_svg {
  display: block;
  width: 100%;
  height: 14px;
  border-radius: 999px;
  overflow: hidden;
}
.et_budget_bar_bg   { fill: color-mix(in srgb, var(--border) 60%, transparent); }
.et_budget_bar_fill--ok       { fill: #22c55e; }
.et_budget_bar_fill--warning  { fill: #f97316; }
.et_budget_bar_fill--critical { fill: #ef4444; }
.et_budget_bar_marker { fill: color-mix(in srgb, var(--color-text-muted) 40%, transparent); }
.et_budget_bar_marker--crit   { fill: color-mix(in srgb, #ef4444 50%, transparent); }
.et_budget_bar_labels {
  display: flex;
  justify-content: space-between;
  margin-top: 0.15rem;
}
.et_budget_bar_label_warn,
.et_budget_bar_label_crit {
  font-size: 0.68rem;
  color: var(--color-text-muted);
}
.et_budget_hint {
  margin: 0;
  font-size: var(--font-sm, 0.82rem);
  color: var(--color-text-muted);
}

.ytd_site_bar {
  fill: #8b5cf6;
  opacity: 0.65;
}

/* ═══ Executive Snapshot Bar ════════════════════════════════════════════════ */
.et_exec_snapshot {
  display: flex;
  flex-wrap: wrap;
  gap: 0;
  margin-bottom: 0.85rem;
  background: var(--elevated-surface, var(--surface));
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 6px);
  overflow: hidden;
}
.et_exec_snapshot_item {
  flex: 1 1 10rem;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.15rem;
  padding: 0.9rem 1.25rem;
  border-right: 1px solid var(--border);
}
.et_exec_snapshot_item:last-child {
  border-right: none;
}
.et_exec_snapshot_item--primary {
  border-left: 3px solid var(--color-primary);
}
.et_exec_snapshot_value {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--color-text);
  line-height: 1.1;
  letter-spacing: -0.01em;
}
.et_exec_snapshot_value--risk     { color: #ef4444; }
.et_exec_snapshot_value--positive { color: #22c55e; }
.et_exec_snapshot_value--ok       { color: #22c55e; }
.et_exec_snapshot_value--normal   { color: #22c55e; }
.et_exec_snapshot_value--watch    { color: #f59e0b; }
.et_exec_snapshot_value--concern  { color: #f97316; }
.et_exec_snapshot_value_denom {
  font-size: 0.8rem;
  font-weight: 500;
  opacity: 0.6;
}
.et_exec_snapshot_label {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: var(--color-text-muted);
  overflow-wrap: normal;
  word-break: normal;
  white-space: nowrap;
}
.et_exec_snapshot_sub {
  font-size: var(--font-sm, 0.8rem);
  color: var(--color-text-muted);
  overflow-wrap: normal;
  word-break: normal;
}
.et_exec_snapshot_sub--ok   { color: #22c55e; font-weight: 600; }
.et_exec_snapshot_sub--over { color: #ef4444; font-weight: 600; }
.et_exec_snapshot_sub--muted { opacity: 0.75; }

/* ═══ Health Score Trend ════════════════════════════════════════════════════ */
.et_health_score_meta {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.25rem;
}
.et_health_score_trend {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  font-weight: 500;
}

/* ═══ Payroll Composition ═══════════════════════════════════════════════════ */
.et_composition_figure {
  overflow: hidden;
}
.et_composition_body {
  padding: 1rem 1.5rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.et_comp_bar_svg {
  display: block;
  width: 100%;
  height: 18px;
  border-radius: 999px;
  overflow: hidden;
}
.et_comp_bar_rect--reg   { fill: var(--color-primary); }
.et_comp_bar_rect--ot    { fill: #f97316; }
.et_comp_bar_rect--loa   { fill: #8b5cf6; }
.et_comp_bar_rect--other { fill: color-mix(in srgb, var(--border) 80%, #888); }
.et_comp_legend {
  margin: 0;
  padding: 0;
  list-style: none;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 1.5rem;
}
.et_comp_legend_item {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}
.et_comp_swatch {
  width: 10px;
  height: 10px;
  border-radius: 2px;
  flex-shrink: 0;
}
.et_comp_swatch--reg   { background: var(--color-primary); }
.et_comp_swatch--ot    { background: #f97316; }
.et_comp_swatch--loa   { background: #8b5cf6; }
.et_comp_swatch--other { background: color-mix(in srgb, var(--border) 80%, transparent); }
.et_comp_legend_label {
  font-size: var(--font-sm, 0.84rem);
  color: var(--color-text-muted);
}
.et_comp_legend_pct {
  font-size: var(--font-sm, 0.84rem);
  font-weight: 700;
  color: var(--color-text);
}
.et_comp_legend_amt {
  font-size: var(--font-sm, 0.82rem);
  color: var(--color-text-muted);
}

/* ═══ Risk Register ═════════════════════════════════════════════════════════ */
.et_risk_figure {
  overflow: hidden;
}
.et_risk_body {
  display: flex;
  flex-direction: column;
  gap: 0;
}
.et_risk_item {
  display: grid;
  grid-template-columns: 1.8rem 1fr;
  gap: 0.5rem;
  align-items: start;
  padding: 0.85rem 1.25rem;
  border-bottom: 1px solid color-mix(in srgb, var(--border) 50%, transparent);
  border-left: 3px solid transparent;
}
.et_risk_item:last-child {
  border-bottom: none;
}
.et_risk_item--critical { border-left-color: #ef4444; background: color-mix(in srgb, #ef4444 3%, transparent); }
.et_risk_item--warning  { border-left-color: #f97316; background: color-mix(in srgb, #f97316 3%, transparent); }
.et_risk_item--notice   { border-left-color: #f59e0b; }
.et_risk_icon {
  font-size: 0.95rem;
  line-height: 1.6;
}
.et_risk_content {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}
.et_risk_title_row {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  flex-wrap: wrap;
}
.et_risk_title {
  font-size: var(--font-sm, 0.87rem);
  font-weight: 700;
  color: var(--color-text);
}
.et_risk_sev {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
}
.et_risk_sev--critical { background: color-mix(in srgb, #ef4444 12%, transparent); color: #ef4444; }
.et_risk_sev--warning  { background: color-mix(in srgb, #f97316 12%, transparent); color: #f97316; }
.et_risk_sev--notice   { background: color-mix(in srgb, #f59e0b 12%, transparent); color: #f59e0b; }
.et_risk_cause {
  margin: 0;
  font-size: var(--font-sm, 0.84rem);
  color: var(--color-text-muted);
  line-height: 1.4;
}
.et_risk_rec {
  margin: 0.15rem 0 0;
  font-size: var(--font-sm, 0.82rem);
  color: var(--color-primary);
  line-height: 1.35;
}

.et_risk_meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem 0.7rem;
  margin: 0.45rem 0 0;
}

.et_risk_meta div {
  display: inline-flex;
  align-items: baseline;
  gap: 0.25rem;
}

.et_risk_meta dt,
.et_risk_meta dd {
  margin: 0;
  font-size: 0.74rem;
  line-height: 1.3;
}

.et_risk_meta dt {
  color: var(--color-text-muted);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.et_risk_meta dd {
  color: var(--color-text);
}

/* ═══ Recommendations ═══════════════════════════════════════════════════════ */
.et_rec_figure {
  overflow: hidden;
}
.et_rec_list {
  margin: 0;
  padding: 0.75rem 1.25rem 1rem;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  counter-reset: none;
}
.et_rec_item {
  display: flex;
  align-items: baseline;
  gap: 0.75rem;
  padding: 0.6rem 0.85rem;
  border-radius: var(--radius-sm, 5px);
  border-left: 3px solid var(--border);
  background: var(--surface);
}
.et_rec_item--critical { border-left-color: #ef4444; }
.et_rec_item--warning  { border-left-color: #f97316; }
.et_rec_item--notice   { border-left-color: #f59e0b; }
.et_rec_item--normal   { border-left-color: var(--border); }
.et_rec_item--positive { border-left-color: #22c55e; }
.et_rec_num {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--color-text-muted);
  min-width: 1.1rem;
  flex-shrink: 0;
}
.et_rec_content {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}
.et_rec_text {
  font-size: var(--font-sm, 0.87rem);
  color: var(--color-text);
  line-height: 1.4;
}
.et_rec_source {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  font-style: italic;
}

/* ── Team Export Buttons ── */
.et_export_group {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  margin-left: auto;
  flex-shrink: 0;
}

.et_export_btn {
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.03em;
  padding: 0.2rem 0.5rem;
  border: 1px solid color-mix(in srgb, var(--color-primary, #4a9eff) 45%, var(--border, #333));
  border-radius: 4px;
  background: transparent;
  color: var(--color-primary, #4a9eff);
  cursor: pointer;
  line-height: 1.4;
  transition: background 0.15s, color 0.15s;
}

.et_export_btn:hover:not(:disabled) {
  background: color-mix(in srgb, var(--color-primary, #4a9eff) 15%, transparent);
}

.et_export_btn:disabled {
  opacity: 0.45;
  cursor: default;
}

.et_export_btn--report {
  font-size: 0.78rem;
  padding: 0.25rem 0.75rem;
  margin-left: auto;
}

/* ── Reports Print Dialog ── */
.reports_print_toolbar {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  margin: 0 0 0.75rem;
}

.reports_print_button {
  min-height: 2.25rem;
}

.reports_print_dialog {
  width: min(94vw, 34rem);
  max-height: min(86vh, 42rem);
  color: var(--color-text, #f5f7fb);
}

.reports_print_form {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.reports_print_content {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.reports_print_desc {
  margin: 0;
  color: var(--color-text-muted, #aab4c8);
  line-height: 1.45;
}

.reports_print_modes {
  display: grid;
  gap: 0.6rem;
  margin: 0;
  padding: 0;
  border: 0;
}

.reports_print_mode {
  display: grid;
  grid-template-columns: 1.1rem 1fr;
  gap: 0.7rem;
  align-items: start;
  padding: 0.75rem;
  border: 1px solid var(--border, #2a3446);
  border-radius: var(--radius-sm, 5px);
  background: var(--surface, rgba(255, 255, 255, 0.04));
  cursor: pointer;
}

.reports_print_mode:has(input:checked) {
  border-color: var(--color-primary, #4a9eff);
  background: color-mix(in srgb, var(--color-primary, #4a9eff) 12%, transparent);
}

.reports_print_mode input {
  margin-top: 0.15rem;
  accent-color: var(--color-primary, #4a9eff);
}

.reports_print_mode_body {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  min-width: 0;
}

.reports_print_mode_title {
  font-weight: 700;
  color: var(--color-text, #f5f7fb);
}

.reports_print_mode_desc {
  color: var(--color-text-muted, #aab4c8);
  line-height: 1.35;
  font-size: var(--font-sm, 0.87rem);
}

/* ── Print / PDF Export Layout ── */
@media print {
  /* Hide all chrome */
  #page_header,
  #page_footer,
  .page_nav,
  .earnings_tab_row,
  .earnings_tab_org_select,
  .earnings_team_year_row,
  .et_export_group,
  .et_export_btn,
  .reports_print_toolbar,
  .reports_print_dialog,
  [data-group-export-format],
  [data-team-export-format] { display: none !important; }

  /* Show only the team panel */
  body {
    background: #fff;
    color: #111;
    font-family: var(--serif, Georgia, "Times New Roman", Times, serif);
    font-size: 10pt;
  }

  #main { margin: 0 !important; padding: 0 !important; }

  .earnings_team_panel {
    display: block !important;
    padding: 1.5cm 1.5cm 2cm;
  }

  /* Page title */
  .earnings_team_panel::before {
    content: "Payroll Intelligence Report";
    display: block;
    font-size: 18pt;
    font-weight: 700;
    margin-bottom: 0.25cm;
    border-bottom: 2pt solid #111;
    padding-bottom: 0.25cm;
  }

  /* Each figure is a print section */
  .earnings_ytd_figure {
    break-inside: avoid;
    border: 0.5pt solid #ccc;
    border-radius: 0;
    margin: 0.5cm 0;
    padding: 0.4cm;
    background: #fff !important;
    box-shadow: none !important;
  }

  .earnings_ytd_header {
    border-bottom: 0.5pt solid #ccc;
    margin-bottom: 0.3cm;
    padding-bottom: 0.2cm;
  }

  .earnings_ytd_title {
    font-size: 11pt;
    font-weight: 700;
    color: #111;
  }

  .earnings_ytd_subtitle {
    font-size: 8pt;
    color: #555;
  }

  /* SVG charts: allow, constrain width */
  .earnings_ytd_svg { width: 100% !important; max-width: 100%; }

  /* Exec snapshot: horizontal row */
  .et_exec_snapshot {
    display: flex !important;
    flex-wrap: wrap;
    gap: 0.4cm;
    background: #f8f8f8 !important;
    border: 0.5pt solid #ccc !important;
    padding: 0.4cm !important;
  }

  .et_exec_snapshot_value { color: #111 !important; }
  .et_exec_snapshot_value--risk { color: #c00 !important; }
  .et_exec_snapshot_value--positive { color: #060 !important; }

  html:not([data-print-mode="color"]) .et_exec_snapshot_value--risk,
  html:not([data-print-mode="color"]) .et_exec_snapshot_value--positive,
  html:not([data-print-mode="color"]) .et_exec_snapshot_value--normal,
  html:not([data-print-mode="color"]) .et_exec_snapshot_value--watch,
  html:not([data-print-mode="color"]) .et_exec_snapshot_value--concern,
  html:not([data-print-mode="color"]) .et_exec_snapshot_sub--ok,
  html:not([data-print-mode="color"]) .et_exec_snapshot_sub--over,
  html:not([data-print-mode="color"]) .et_health_score_num--normal,
  html:not([data-print-mode="color"]) .et_health_score_num--healthy,
  html:not([data-print-mode="color"]) .et_health_score_num--watch,
  html:not([data-print-mode="color"]) .et_health_score_num--concern,
  html:not([data-print-mode="color"]) .et_health_score_num--risk,
  html:not([data-print-mode="color"]) .et_budget_stat_value--ok,
  html:not([data-print-mode="color"]) .et_budget_stat_value--warning,
  html:not([data-print-mode="color"]) .et_budget_stat_value--critical,
  html:not([data-print-mode="color"]) .et_forecast_verdict--ok .et_forecast_verdict_label,
  html:not([data-print-mode="color"]) .et_forecast_verdict--over .et_forecast_verdict_label {
    color: #000000 !important;
  }

  html:not([data-print-mode="color"]) .et_health_badge,
  html:not([data-print-mode="color"]) .et_alert_sev,
  html:not([data-print-mode="color"]) .et_risk_sev,
  html:not([data-print-mode="color"]) .et_budget_badge {
    background: #ffffff !important;
    border-color: #444444 !important;
    color: #000000 !important;
  }

  html:not([data-print-mode="color"]) .et_alert_card--critical,
  html:not([data-print-mode="color"]) .et_alert_card--warning,
  html:not([data-print-mode="color"]) .et_alert_card--notice,
  html:not([data-print-mode="color"]) .et_alert_card--normal,
  html:not([data-print-mode="color"]) .et_alert_card--positive,
  html:not([data-print-mode="color"]) .et_risk_item--critical,
  html:not([data-print-mode="color"]) .et_risk_item--warning,
  html:not([data-print-mode="color"]) .et_risk_item--notice,
  html:not([data-print-mode="color"]) .et_rec_item--critical,
  html:not([data-print-mode="color"]) .et_rec_item--warning,
  html:not([data-print-mode="color"]) .et_rec_item--notice,
  html:not([data-print-mode="color"]) .et_rec_item--positive {
    background: #ffffff !important;
    border-left-color: #000000 !important;
  }

  html:not([data-print-mode="color"]) .et_budget_bar_fill--ok,
  html:not([data-print-mode="color"]) .et_budget_bar_fill--warning,
  html:not([data-print-mode="color"]) .et_budget_bar_fill--critical,
  html:not([data-print-mode="color"]) .et_comp_bar_rect--reg,
  html:not([data-print-mode="color"]) .et_comp_bar_rect--ot,
  html:not([data-print-mode="color"]) .et_comp_bar_rect--loa,
  html:not([data-print-mode="color"]) .et_comp_bar_rect--other {
    fill: #d9d9d9 !important;
    stroke: #000000 !important;
    stroke-width: 0.08 !important;
    opacity: 1 !important;
  }

  html[data-print-mode="grayscale"] .et_budget_bar_fill--ok,
  html[data-print-mode="grayscale"] .et_comp_bar_rect--reg {
    fill: #b8b8b8 !important;
  }

  html[data-print-mode="grayscale"] .et_budget_bar_fill--warning,
  html[data-print-mode="grayscale"] .et_comp_bar_rect--ot {
    fill: #8f8f8f !important;
  }

  html[data-print-mode="grayscale"] .et_budget_bar_fill--critical,
  html[data-print-mode="grayscale"] .et_comp_bar_rect--loa,
  html[data-print-mode="grayscale"] .et_comp_bar_rect--other {
    fill: #cfcfcf !important;
  }

  html[data-print-mode="bw"] .et_budget_bar_fill--ok,
  html[data-print-mode="bw"] .et_budget_bar_fill--warning,
  html[data-print-mode="bw"] .et_budget_bar_fill--critical,
  html[data-print-mode="bw"] .et_comp_bar_rect--reg,
  html[data-print-mode="bw"] .et_comp_bar_rect--ot,
  html[data-print-mode="bw"] .et_comp_bar_rect--loa,
  html[data-print-mode="bw"] .et_comp_bar_rect--other {
    fill: #ffffff !important;
    stroke: #000000 !important;
  }

  /* Risk/rec items */
  .et_risk_item, .et_rec_item {
    break-inside: avoid;
    border-left-width: 2pt !important;
    margin-bottom: 0.2cm;
    background: #fff !important;
  }

  /* Budget figure */
  .et_budget_bar_svg { width: 100% !important; }
  .et_budget_stat_row { display: flex !important; flex-wrap: wrap; gap: 0.5cm; }

  /* Intel row */
  .et_intel_row { display: flex !important; flex-wrap: wrap; gap: 0.4cm; }
  .et_intel_card { flex: 1 1 40%; border: 0.5pt solid #ccc !important; background: #fff !important; }

  /* Cost drivers */
  .et_cost_body { display: flex !important; flex-wrap: wrap; gap: 0.4cm; }
  .et_cost_col { flex: 1 1 28%; }

  /* Hide elements that don't print well */
  .earnings_team_empty,
  .et_intel_card--health .et_health_emoji { display: none; }
}
