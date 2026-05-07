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
  padding: 0.85rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 6px);
  background: var(--elevated-surface, var(--surface));
}

.earnings_async_slot[data-earnings-slot="payperiods"] {
  container-type: inline-size;
}

.earnings_panel_title {
  margin: 0 0 0.5rem;
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
  transform: translateY(2px);
  transition: opacity 0.12s ease, transform 0.12s ease;
}

.hover_help_tooltip.is-visible {
  opacity: 1;
  transform: translateY(0);
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
}

.earnings_ytd_basic_row {
  display: flex;
  justify-content: space-between;
  gap: var(--gap-sm, 0.5rem);
  padding: 0.2rem 0;
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
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.7rem;
}

.earnings_hi_card {
  border: 1px solid var(--border);
  border-radius: var(--radius-sm, 6px);
  padding: 0.55rem 0.7rem;
  background: var(--surface);
}

.earnings_hi_card h3 {
  margin: 0 0 0.3rem;
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

.earnings_hi_card p {
  margin: 0;
  font-weight: 600;
  color: var(--color-text);
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

.earnings_monthly_datagrid {
  width: min(96rem, 100%);
}

.earnings_monthly_datagrid .datagrid_item:nth-child(2),
.earnings_monthly_datagrid .datagrid_item:nth-child(3),
.earnings_monthly_datagrid .datagrid_item:nth-child(4),
.earnings_monthly_datagrid .datagrid_item:nth-child(5),
.earnings_monthly_datagrid .datagrid_item:nth-child(6),
.earnings_monthly_datagrid .datagrid_item:nth-child(7),
.earnings_monthly_datagrid .datagrid_item:nth-child(8),
.earnings_monthly_datagrid .datagrid_item:nth-child(9),
.earnings_monthly_datagrid .datagrid_heading:nth-child(2),
.earnings_monthly_datagrid .datagrid_heading:nth-child(3),
.earnings_monthly_datagrid .datagrid_heading:nth-child(4),
.earnings_monthly_datagrid .datagrid_heading:nth-child(5),
.earnings_monthly_datagrid .datagrid_heading:nth-child(6),
.earnings_monthly_datagrid .datagrid_heading:nth-child(7),
.earnings_monthly_datagrid .datagrid_heading:nth-child(8),
.earnings_monthly_datagrid .datagrid_heading:nth-child(9) {
  text-align: right;
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

.svg-hidden {
  visibility: hidden;
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
  transition: width 0.3s ease;
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
