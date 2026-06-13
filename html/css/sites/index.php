<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
/**
 * PayCal - Sites Page Styles
 * 
 * Extracted from main CSS index.php
 * Date: March 1, 2026
 */

/* Profile-style page wrapper contract for sites layout. */
#main:has(.sites_main_container) {
  display: flex;
  flex-direction: column;
  gap: clamp(1rem, 2vw, 1.6rem);
}

#main:has(.sites_main_container) > .sites_main_container {
  width: min(80vw, 1240px);
  margin-left: auto;
  margin-right: auto;
  flex-direction: column;
  align-items: flex-start;
  gap: clamp(0.9rem, 1.8vw, 1.45rem);
}

#main:has(.sites_main_container) > .sites_main_container > .f_column {
  width: 100%;
  gap: clamp(0.9rem, 1.4vw, 1.2rem);
}

#main:has(.sites_main_container) section.panel {
  padding: clamp(1rem, 2vw, 1.5rem);
}

/* SITES GRID */
.sites_grid .list_item {
  padding: var(--pad-xs);
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.sites_grid_body .list_item.row_hover {
  background-color: rgba(0, 188, 212, 0.15);
}

/* ========================================================================== */
/* Orphaned Work Recovery Styles                                             */
/* ========================================================================== */
.modal_orphaned_work .modal_content {
  padding-bottom: 4rem;
}

/* Edit Site dialog: two-column, full-width, full-height layout */
#modal_edit_site {
  --dialog-max-width: min(96vw, 1200px);
  --dialog-max-height: 96dvh;
  top: max(0.5rem, 2dvh);
}

#modal_edit_site .modal_content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  align-items: stretch;
  padding: 0;
  overflow: hidden;
  min-height: 0;
}

.edit_site_col {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1.5rem;
  overflow-y: auto;
  min-height: 0;
}

.edit_site_col_basic {
  border-right: 1px solid var(--panel-border, #2a2a2a);
}

.edit_site_col_heading {
  margin: 0 0 0.5rem;
  padding-bottom: 0.4rem;
  border-bottom: 1px solid var(--panel-border, #2a2a2a);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.09em;
  text-transform: uppercase;
  color: var(--text-muted, #888);
}

/* ── Replace flex pair with fixed-label grid — kills label-length fighting ── */
#modal_edit_site .item_pair {
  display: grid;
  grid-template-columns: 180px minmax(0, 1fr);
  align-items: center;
  gap: 1rem;
  min-height: 3.25rem;
  padding: 0;
  width: 100%;
}

#modal_edit_site .item_label {
  text-align: right;
  padding: 0;
  font-size: 0.88rem;
}

#modal_edit_site .item_value {
  padding: 0;
}

/* ── Uniform control heights ─────────────────────────────────────────────── */
#modal_edit_site input:not([type="color"]),
#modal_edit_site select {
  height: 2.75rem;
  width: 100%;
  box-sizing: border-box;
}

/* ── Read-only field display ──────────────────────────────────────────────── */
.site_field_readonly {
  display: flex;
  align-items: center;
  height: 2.75rem;
  padding: 0 0.75rem;
  border: 1px solid var(--panel-border, #2a2a2a);
  border-radius: var(--border-radius, 6px);
  background: color-mix(in srgb, var(--panel-bg) 60%, transparent);
  color: var(--text-muted, #888);
  font-size: 0.9rem;
  box-sizing: border-box;
  width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ── Site color left-border wedge on grid rows (CSP-safe attribute selectors) ── */
<?php
foreach (\PayCal\Domain\Config\SiteColorPalette::pickerPalette() as $pc) {
  $h = strtoupper($pc['hex']);
  echo "[data-grid^=\"sites-\"] .datagrid_row[data-color=\"{$h}\"] { border-left: 5px solid {$h}; }\n";
}
?>

/* ── Custom swatch color picker ───────────────────────────────────────────── */
.item_pair_color {
  align-items: start;
  padding-top: 0.25rem;
}

.item_label_color {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  padding-top: 0.15rem;
}

.site_color_name {
  font-size: 0.9rem;
  color: var(--text-muted, #888);
  font-style: italic;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.site_color_picker {
  display: block;
}

.site_color_swatches {
  display: grid;
  grid-template-columns: repeat(5, 2rem);
  gap: 0.3rem;
}

.site_color_swatch {
  display: block;
  width: 2rem;
  height: 2rem;
  border-radius: 5px;
  border: 2px solid transparent;
  cursor: pointer;
  padding: 0;
  transition: transform 0.1s ease, border-color 0.1s ease;
}

.site_color_swatch:hover {
  transform: scale(1.2);
  z-index: 1;
  position: relative;
}

.site_color_swatch:focus-visible {
  outline: 2px solid var(--color-primary, #4a9eff);
  outline-offset: 2px;
}

.site_color_swatch.is-selected {
  border-color: #fff;
  box-shadow: 0 0 0 2px var(--color-primary, #4a9eff);
}

.orphaned_group_card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--gap-md);
  padding: var(--mar-md);
  border: 1px solid var(--panel-border);
  border-radius: var(--radius-panel, var(--border-radius));
  background: var(--panel-bg);
  transition: all 0.2s ease;
}

.orphaned_group_card:hover {
  border-color: var(--color-primary);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.orphaned_group_card + .orphaned_group_card {
  margin-top: 0;
  border-top: 1px solid var(--panel-border);
}

.orphaned_group_info {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: var(--gap-sm);
}

.orphaned_group_name {
  margin: 0;
  font-size: 1.1em;
  font-weight: 600;
  color: var(--text-color);
}

.orphaned_group_stats {
  display: flex;
  flex-wrap: wrap;
  gap: var(--gap-md);
  font-size: 0.9em;
  color: var(--text-muted);
}

.orphaned_stat {
  white-space: nowrap;
}

.orphaned_stat:not(:last-child)::after {
  content: '◆';
  margin-left: var(--gap-md);
  opacity: 0.5;
}
.btn_warning:hover {
  background: #FFB300;
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
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3), 0 0 60px rgba(88, 166, 255, 0.35);
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

.modal_orphaned_work .modal_header .btn_close {
  z-index: 10;
}

/* ── Sites DataGrid column layout ───────────────────────────────────────
   Source of truth for --grid-template-columns.
   Tracks: Name | Wage | LOA | Travel | Province | Entries | Actions
   Includes the actions track so header and body share the same track count.
   data-grid values come from DataGrid::create("sites-{$status}") where
   $status = 'active' | 'archived'.
   ──────────────────────────────────────────────────────────────────────── */
[data-grid="sites-active"],
[data-grid="sites-archived"] {
  --grid-template-columns:
    minmax(14rem, 3fr)
    minmax(5rem, 1fr)
    minmax(5rem, 1fr)
    minmax(5rem, 1fr)
    minmax(8rem, 1.5fr)
    minmax(4rem, 0.75fr)
    minmax(3rem, max-content);
  font-size: 0.9em;
}

[data-grid="sites-active"] .datagrid_item,
[data-grid="sites-active"] .datagrid_heading,
[data-grid="sites-archived"] .datagrid_item,
[data-grid="sites-archived"] .datagrid_heading {
  min-width: 0;
  padding: 0.4rem 0.5rem;
}

/* Explicit column template: Name | Wage | LOA | Travel | Province | Entries | Budget | Action */
[data-grid="sites-active"] .datagrid_header_content,
[data-grid="sites-active"] .datagrid_row_content,
[data-grid="sites-archived"] .datagrid_header_content,
[data-grid="sites-archived"] .datagrid_row_content {
  grid-template-columns:
    minmax(10rem, 3fr)
    minmax(4.5rem, 1fr)
    minmax(4rem, 1fr)
    minmax(4rem, 1fr)
    minmax(6rem, 1.4fr)
    minmax(3.5rem, 0.7fr)
    minmax(5rem, 1fr)
    2.5rem;
}

[data-grid="sites-active"] .datagrid_heading,
[data-grid="sites-archived"] .datagrid_heading {
  padding: 0.5rem 0.5rem 0.6rem 0.5rem;
}

/* Tighter numeric cells */
[data-grid="sites-active"] .datagrid_col_wage,
[data-grid="sites-active"] .datagrid_col_living_out_allowance,
[data-grid="sites-active"] .datagrid_col_travel_hours,
[data-grid="sites-active"] .datagrid_col_entries,
[data-grid="sites-active"] .datagrid_col_budget_amount,
[data-grid="sites-archived"] .datagrid_col_wage,
[data-grid="sites-archived"] .datagrid_col_living_out_allowance,
[data-grid="sites-archived"] .datagrid_col_travel_hours,
[data-grid="sites-archived"] .datagrid_col_entries,
[data-grid="sites-archived"] .datagrid_col_budget_amount {
  font-size: 0.82rem;
  font-variant-numeric: tabular-nums;
}

/* Budget column: accent colour when set, muted when empty */
[data-grid="sites-active"] .datagrid_col_budget_amount:not(:empty),
[data-grid="sites-archived"] .datagrid_col_budget_amount:not(:empty) {
  color: var(--color-primary, #4a9eff);
  font-weight: 600;
}

[data-grid="sites-active"] .datagrid_heading_actions,
[data-grid="sites-active"] .datagrid_item_actions,
[data-grid="sites-archived"] .datagrid_heading_actions,
[data-grid="sites-archived"] .datagrid_item_actions {
  text-align: right;
}

[data-grid="sites-active"] .datagrid_heading_actions,
[data-grid="sites-archived"] .datagrid_heading_actions {
  color: transparent;
  user-select: none;
}

/* Sites earnings analytics spacing */
.sites_main_container {
  gap: var(--mar-md);
}

/* Spacing between tabs and tab-disclaimer within merged panels */
#sites_list_panel .tabs,
#sites_earnings_panel .tabs {
  margin-bottom: 0.6rem;
}

/* Spacing between tab-disclaimer and tab content within merged panels */
#sites_list_panel .tab-disclaimer,
#sites_earnings_panel .tab-disclaimer {
  margin-bottom: 1rem;
  margin-top: 0;
}

/* Ensure tab content divs have spacing from above */
#sites_list_panel > .tab-content,
#sites_earnings_panel > #site_earnings_container {
  margin-top: 0.5rem;
}

@media (max-width: 750px) {
  #main:has(.sites_main_container) > .sites_main_container {
    width: min(92vw, 1240px);
    flex-direction: column;
  }

  #main:has(.sites_main_container) > .sites_main_container > .f_column {
    width: 100%;
  }
}

@media (max-width: 600px) {
  #main:has(.sites_main_container) > .sites_main_container {
    width: 100%;
    gap: clamp(0.5rem, 1.5vw, 0.8rem);
  }

  #main:has(.sites_main_container) section.panel {
    padding: clamp(0.6rem, 1.5vw, 0.9rem);
  }

  #sites_list_panel .tabs,
  #sites_earnings_panel .tabs {
    margin-bottom: 0.3rem;
  }

  #sites_list_panel .tab-disclaimer,
  #sites_earnings_panel .tab-disclaimer {
    margin-bottom: 0.6rem;
    font-size: 0.9em;
  }

  #sites_list_panel > .tab-content,
  #sites_earnings_panel > #site_earnings_container {
    margin-top: 0.25rem;
  }
}

@media (max-width: 450px) {
  #main:has(.sites_main_container) > .sites_main_container {
    width: 100%;
    gap: 0.4rem;
  }

  #main:has(.sites_main_container) section.panel {
    padding: 0.5rem;
  }

  #sites_list_panel .tabs,
  #sites_earnings_panel .tabs {
    margin-bottom: 0.2rem;
    gap: 0.25rem;
  }

  #sites_list_panel .tab-disclaimer,
  #sites_earnings_panel .tab-disclaimer {
    margin-bottom: 0.4rem;
    font-size: 0.85em;
    line-height: 1.3;
  }

  #sites_list_panel > .tab-content,
  #sites_earnings_panel > #site_earnings_container {
    margin-top: 0.15rem;
  }
}

/* ==========================================================================
   Sites DataGrid — Mobile card layout  (<= 719 px)
   Each row becomes a labelled card. ::before labels use the datagrid_col_*
   classes added by DataGrid::renderTable() so they stay in sync with the
   column definitions in SitesController without extra markup.
   ========================================================================== */
@media (max-width: 719px) {
  /* Card frame */
  [data-grid="sites-active"] .datagrid_row,
  [data-grid="sites-archived"] .datagrid_row {
    border: 1px solid var(--border, rgba(255,255,255,0.12));
    border-radius: var(--radius-cell, 4px);
    margin: 0.35rem 0;
  }

  /* Override the generic card row padding */
  [data-grid="sites-active"] .datagrid_row_content,
  [data-grid="sites-archived"] .datagrid_row_content {
    padding: 0.6rem 0.75rem;
  }

  /* Column label styling applied via ::before on every data cell */
  [data-grid="sites-active"] .datagrid_item::before,
  [data-grid="sites-archived"] .datagrid_item::before {
    content: "";
    font-size: var(--font-sm);
    font-weight: 600;
    color: var(--text-muted);
    flex-shrink: 0;
  }

  /* Per-column labels — content property overrides the empty default above */
  [data-grid="sites-active"] .datagrid_col_site_name::before,
  [data-grid="sites-archived"] .datagrid_col_site_name::before { content: "Name"; }

  [data-grid="sites-active"] .datagrid_col_wage::before,
  [data-grid="sites-archived"] .datagrid_col_wage::before { content: "Wage"; }

  [data-grid="sites-active"] .datagrid_col_living_out_allowance::before,
  [data-grid="sites-archived"] .datagrid_col_living_out_allowance::before { content: "LOA"; }

  [data-grid="sites-active"] .datagrid_col_travel_hours::before,
  [data-grid="sites-archived"] .datagrid_col_travel_hours::before { content: "Travel"; }

  [data-grid="sites-active"] .datagrid_col_province::before,
  [data-grid="sites-archived"] .datagrid_col_province::before { content: "Province"; }

  [data-grid="sites-active"] .datagrid_col_entries::before,
  [data-grid="sites-archived"] .datagrid_col_entries::before { content: "Entries"; }

  /* Actions row: no label, push button right, add a divider */
  [data-grid="sites-active"] .datagrid_item_actions::before,
  [data-grid="sites-archived"] .datagrid_item_actions::before { content: none; }

  [data-grid="sites-active"] .datagrid_item_actions,
  [data-grid="sites-archived"] .datagrid_item_actions {
    justify-content: flex-end;
    padding-top: 0.5rem;
    margin-top: 0.25rem;
    border-top: 1px solid var(--border, rgba(255,255,255,0.1));
  }

  /* Name cell: headline of the card, full width, allow wrap */
  [data-grid="sites-active"] .datagrid_col_site_name,
  [data-grid="sites-archived"] .datagrid_col_site_name {
    white-space: normal;
    overflow: visible;
    text-overflow: unset;
    font-weight: 600;
    font-size: 1.05em;
    padding-bottom: 0.35rem;
    border-bottom: 1px solid var(--border, rgba(255,255,255,0.08));
    margin-bottom: 0.2rem;
    /* Name row: label on left, value on right like other rows */
  }
}

#site_earnings_container {
  display: grid;
  gap: 0.85rem;
}

#site_earnings_list,
#site_earnings_totals,
#site_earnings_empty {
  margin-top: 0.35rem;
}

.sites_earnings_skeleton_wrap {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  padding: 0.75rem 0.5rem 0.5rem;
  width: 100%;
}

.sites_earnings_skeleton_head {
  display: flex;
  gap: 0.4rem;
  align-items: center;
  margin-bottom: 0.1rem;
}

.sites_sk_line_head_title {
  height: 0.9em;
  width: 6rem;
}

.sites_sk_line_head_subtitle {
  height: 0.7em;
  flex: 1;
  opacity: 0.5;
}

.sites_earnings_skeleton_bars {
  display: flex;
  align-items: flex-end;
  gap: 6px;
  height: 90px;
}

.sites_sk_bar {
  flex: 1;
  opacity: 0.55;
}

.sites_sk_bar_h_35 { height: 35%; }
.sites_sk_bar_h_40 { height: 40%; }
.sites_sk_bar_h_45 { height: 45%; }
.sites_sk_bar_h_50 { height: 50%; }
.sites_sk_bar_h_55 { height: 55%; }
.sites_sk_bar_h_60 { height: 60%; }
.sites_sk_bar_h_65 { height: 65%; }
.sites_sk_bar_h_70 { height: 70%; }
.sites_sk_bar_h_75 { height: 75%; }
.sites_sk_bar_h_80 { height: 80%; }
.sites_sk_bar_h_85 { height: 85%; }
.sites_sk_bar_h_95 { height: 95%; }

.sites_earnings_skeleton_rows {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  margin-top: 0.25rem;
}

.sites_earnings_skeleton_row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 0.4rem;
}

.site_earnings_totals_datagrid {
  width: 100%;
  --grid-template-columns: minmax(8.5rem, 1.2fr) minmax(5.5rem, 0.9fr) minmax(4.5rem, 0.8fr) minmax(4rem, 0.7fr) minmax(7rem, 1fr);
}

.site_earnings_totals_datagrid .datagrid_heading,
.site_earnings_totals_datagrid .datagrid_item {
  text-align: right;
}

.site_earnings_row {
  padding: 0.85rem;
  border: 1px solid var(--panel-border);
  border-radius: var(--radius-panel, var(--border-radius));
  background: var(--panel-bg);
}

.site_earnings_row + .site_earnings_row {
  margin-top: 0.6rem;
}

.site_earnings_header {
  margin-bottom: 0.45rem;
}

.site_earnings_details {
  margin-top: 0.45rem;
  line-height: 1.45;
}

/* Sites main container (two-panel layout) */
/* Tab disclaimer */
/* Modal styles for Sites page */
.delete_message_danger {
  color: var(--error);
}

#sites-grid-active .datagrid_empty,
#sites-grid-archived .datagrid_empty {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 10rem;
  text-align: center;
  font-size: 1.6rem;
  font-weight: 700;
}

/* ── Org Planning section: right/advanced column of Edit Site dialog ── */
#edit_site_org_planning {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.edit_site_org_planning_body {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.edit_site_org_planning_empty {
  margin: 2rem 0 0;
  padding: 1rem 1.25rem;
  border: 1px dashed color-mix(in srgb, var(--panel-border, #2a2a2a) 70%, transparent);
  border-radius: var(--border-radius, 6px);
  font-size: 0.83rem;
  line-height: 1.5;
  color: var(--text-muted, #888);
  text-align: center;
}

/* ── Per-swatch color rules (CSP-safe: no inline styles) ───────────────────── */
<?php
foreach (\PayCal\Domain\Config\SiteColorPalette::pickerPalette() as $swIdx => $swColor) {
  $swHex = $swColor['hex'];
  echo ".site_color_swatch[data-idx=\"{$swIdx}\"] { background: {$swHex}; }\n";
}
?>
