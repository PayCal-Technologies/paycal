<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
/**
 * PayCal - Admin AST Graph Styles
 *
 * Purpose: Layout and presentation for the AST dependency graph page.
 * Location: html/css/admin-ast/index.php
 * Date: April 14, 2026
 */

.ast_page {
  padding: 0;
  margin: 0;
}

.ast_toolbar {
  display: flex;
  align-items: center;
  gap: .5rem;
  padding: .5rem 1rem;
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
}

.ast_toolbar h1 {
  font-size: 1rem;
  margin: 0;
  white-space: nowrap;
}

.ast_toolbar input[type="text"] {
  flex: 1;
  min-width: 0;
}

.ast_view_btn.is-active {
  background: color-mix(in srgb, var(--color-primary) 22%, var(--color-surface) 78%);
  border-color: color-mix(in srgb, var(--color-primary) 55%, var(--color-border) 45%);
}

.ast_view_pill {
  display: inline-flex;
  align-items: center;
  padding: .14rem;
  border: 1px solid color-mix(in srgb, var(--color-border) 80%, #000000 20%);
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-surface) 90%, #000000 10%);
  gap: .12rem;
}

.ast_view_pill .ast_view_btn {
  border-radius: 999px;
  border-color: transparent;
  min-width: 5.7rem;
}

.ast_view_pill .ast_view_btn:not(.is-active) {
  background: transparent;
}

.ast_view_pill .ast_view_btn.is-active {
  box-shadow: 0 1px 5px rgba(0, 0, 0, 0.22);
}

.ast_stats {
  white-space: nowrap;
  font-size: .85rem;
  opacity: .7;
}

.ast_diag_controls {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  margin-left: .25rem;
  padding-left: .4rem;
  border-left: 1px solid color-mix(in srgb, var(--color-border) 80%, transparent 20%);
}

.ast_diag_toggle {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  font-size: .74rem;
  opacity: .88;
}

.ast_diag_toggle input[type="checkbox"] {
  margin: 0;
}

.ast_focus_hops_select {
  border: 1px solid var(--color-border);
  border-radius: .35rem;
  background: color-mix(in srgb, var(--color-surface) 88%, #000000 12%);
  color: inherit;
  font: inherit;
  padding: .12rem .26rem;
}

.ast_cycle_clear_btn {
  padding: .15rem .42rem;
  font-size: .7rem;
}

.ast_color_legend {
  position: relative;
  flex: 0 0 auto;
}

.ast_color_legend_summary {
  width: 1.45rem;
  height: 1.45rem;
  border-radius: 999px;
  border: 1px solid var(--color-border);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: .78rem;
  font-weight: 700;
  cursor: pointer;
  user-select: none;
  background: color-mix(in srgb, var(--color-surface) 88%, #000000 12%);
}

.ast_color_legend_summary::-webkit-details-marker {
  display: none;
}

.ast_color_legend_panel {
  position: absolute;
  right: 0;
  top: calc(100% + .35rem);
  width: 18rem;
  max-width: min(90vw, 20rem);
  padding: .55rem .65rem;
  border: 1px solid var(--color-border);
  border-radius: .5rem;
  background: color-mix(in srgb, var(--color-surface) 92%, #000000 8%);
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.24);
  z-index: 8;
}

.ast_color_legend_hint {
  margin: 0 0 .45rem;
  font-size: .72rem;
  opacity: .85;
}

.ast_color_legend_list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: .3rem;
}

.ast_color_legend_list li {
  display: block;
  font-size: .75rem;
}

.ast_filter_option {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  cursor: pointer;
}

.ast_filter_option input[type="checkbox"] {
  margin: 0;
  inline-size: .85rem;
  block-size: .85rem;
}

.ast_filter_option_static {
  cursor: default;
}

.ast_filter_selected_hint {
  margin-left: .25rem;
  opacity: .7;
  font-size: .67rem;
}

.ast_color_swatch {
  width: .72rem;
  height: .72rem;
  border-radius: 999px;
  border: 1px solid color-mix(in srgb, #ffffff 20%, transparent 80%);
  display: inline-block;
}

.ast_color_swatch_controller { background: #1565c0; }
.ast_color_swatch_model { background: #2e7d32; }
.ast_color_swatch_service { background: #e65100; }
.ast_color_swatch_middleware { background: #6a1b9a; }
.ast_color_swatch_other { background: #546e7a; }
.ast_color_swatch_selected { background: #d81b60; }

.ast_metrics_panel {
  margin: .65rem 1rem;
  padding: .65rem;
}

.ast_metrics_head h2 {
  margin: 0;
  font-size: .98rem;
}

.ast_metrics_head p {
  margin: .2rem 0 .55rem;
  font-size: .78rem;
  opacity: .75;
}

.ast_metrics_grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: .45rem;
}

.ast_metric_card {
  padding: .5rem .55rem;
}

.ast_metric_card h3 {
  margin: 0;
  font-size: .72rem;
  opacity: .78;
}

.ast_metric_value {
  margin: .25rem 0 0;
  font-size: 1rem;
  font-weight: 700;
}

.ast_metric_delta_panel {
  margin-top: .55rem;
  padding: .55rem;
}

.ast_metric_delta_panel h3 {
  margin: 0 0 .3rem;
  font-size: .78rem;
}

.ast_metric_delta {
  margin: 0;
  white-space: pre-wrap;
  font-size: .74rem;
  opacity: .88;
}

.ast_canvas_wrap {
  position: relative;
  width: 100%;
  height: calc(100vh - 8rem);
  min-height: 400px;
}

.ast_canvas_wrap canvas {
  display: block;
  width: 100%;
  height: 100%;
}

.ast_canvas_wrap canvas:focus-visible {
  outline: 3px solid color-mix(in srgb, var(--color-primary) 70%, #ffffff 30%);
  outline-offset: -3px;
}

.ast_toolbar button:focus-visible,
.ast_toolbar input:focus-visible {
  outline: 2px solid color-mix(in srgb, var(--color-primary) 70%, #ffffff 30%);
  outline-offset: 2px;
}

.ast_node_info {
  border-top: 1px solid var(--color-border);
  min-height: 3rem;
  font-family: monospace;
  font-size: .85rem;
}

.ast_node_info_overlay {
  position: absolute;
  right: .9rem;
  bottom: .9rem;
  width: min(42rem, calc(100% - 1.8rem));
  min-width: 20rem;
  min-height: 8rem;
  max-width: calc(100% - 1rem);
  max-height: calc(100% - 1rem);
  overflow: hidden;
  resize: both;
  display: flex;
  flex-direction: column;
  border: 1px solid var(--color-border);
  border-radius: .6rem;
  background: color-mix(in srgb, var(--color-surface) 88%, #000000 12%);
  backdrop-filter: blur(1.5px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.28);
  z-index: 4;
}

.ast_node_info_overlay:not(.is-collapsed) {
  cursor: grab;
}

.ast_node_info_overlay.is-dragging {
  cursor: grabbing;
}

.ast_node_overlay_head {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: .6rem;
  padding: .45rem .55rem;
  border-bottom: 1px solid var(--color-border);
  cursor: pointer;
  user-select: none;
  transition: background-color .15s ease;
}

.ast_node_overlay_head:hover {
  background: color-mix(in srgb, var(--color-text) 7%, transparent 93%);
}

.ast_node_overlay_title {
  font-size: .78rem;
  text-transform: uppercase;
  letter-spacing: .04em;
  opacity: .85;
}

.ast_node_overlay_chevron {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1rem;
  height: 1rem;
  font-size: .95rem;
  line-height: 1;
  opacity: .92;
  transform-origin: center;
  transition: transform .15s ease;
}

.ast_node_overlay_head[data-state='closed'] .ast_node_overlay_chevron {
  transform: rotate(-90deg);
}

.ast_node_info_content {
  flex: 1 1 auto;
  min-height: 0;
  overflow: auto;
}

.ast_node_overlay_resize_grip {
  position: absolute;
  right: .22rem;
  bottom: .22rem;
  width: 1rem;
  height: 1rem;
  border: 0;
  border-radius: .18rem;
  pointer-events: none;
  background:
    repeating-linear-gradient(135deg,
      color-mix(in srgb, var(--color-text) 55%, transparent 45%) 0,
      color-mix(in srgb, var(--color-text) 55%, transparent 45%) 1px,
      transparent 1px,
      transparent 3px);
  opacity: .75;
}

.ast_node_info_overlay.is-collapsed {
  width: 2.1rem !important;
  min-width: 2.1rem;
  max-width: 2.1rem;
  height: auto !important;
  min-height: 0;
  max-height: none;
  resize: none;
}

.ast_node_info_overlay.is-collapsed .ast_node_overlay_head {
  justify-content: center;
  gap: 0;
  padding: .45rem;
  border-bottom: 0;
}

.ast_node_info_overlay.is-collapsed .ast_node_overlay_title {
  display: none;
}

.ast_node_info_overlay.is-collapsed .ast_node_overlay_resize_grip {
  display: none;
}

.ast_node_info_content[hidden] {
  display: none;
}

.ast_detail_grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-top: .5rem;
}

.ast_detail_grid table {
  width: 100%;
  font-size: .8rem;
  border-collapse: collapse;
}

.ast_detail_grid th {
  text-align: left;
  padding: .2rem .4rem;
  border-bottom: 1px solid var(--color-border);
}

.ast_detail_grid td {
  padding: .2rem .4rem;
}

.ast_detail_grid .ast_empty {
  opacity: .5;
}

.ast_detail_attr_table {
  width: 100%;
  font-size: .8rem;
  border-collapse: collapse;
  margin-top: .35rem;
}

.ast_detail_attr_table th {
  text-align: left;
  width: 11rem;
  padding: .2rem .4rem;
  border-bottom: 1px solid var(--color-border);
  opacity: .82;
}

.ast_detail_attr_table td {
  padding: .2rem .4rem;
  border-bottom: 1px solid color-mix(in srgb, var(--color-border) 70%, transparent 30%);
}

.ast_detail_section {
  margin-top: .4rem;
}

.ast_detail_section h4 {
  margin: 0 0 .2rem;
  font-size: .76rem;
  opacity: .9;
  text-transform: uppercase;
  letter-spacing: .03em;
}

.ast_issue_list {
  margin: 0;
  padding-left: 1rem;
}

.ast_issue_list li {
  margin: 0;
  color: #ffb2b2;
}

.ast_detail_inline_actions {
  margin-top: .3rem;
}

@media (max-width: 900px) {
  .ast_metrics_panel {
    margin: .5rem;
    padding: .5rem;
  }

  .ast_metrics_grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .ast_node_info_overlay {
    right: .5rem;
    bottom: .5rem;
    width: calc(100% - 1rem);
    max-height: calc(100% - 1rem);
  }
}
