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
  width: var(--app-content-width, 100%);
  margin-left: 0;
  margin-right: 0;
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

#sites_list_panel,
#sites_earnings_panel {
  width: 100%;
  max-width: none;
}

#sites_list_panel {
  --business-page-border: color-mix(in srgb, var(--panel-border, #6b7280) 76%, transparent);
  --business-page-primary-bg: #0b63ce;
  --business-page-primary-bg-hover: #0d70e8;
  --business-page-primary-text: #ffffff;
  --business-page-secondary-bg: color-mix(in srgb, var(--panel-bg, #151b24) 78%, #ffffff 8%);
  --business-page-secondary-bg-hover: color-mix(in srgb, var(--panel-bg, #151b24) 68%, #ffffff 14%);
  --business-page-secondary-text: #f4f8ff;
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

/* Site editor dialogs: full-window layout with stable header/body/footer rows. */
#modal_create_site,
#modal_edit_site {
  position: fixed;
  inset: 0;
  top: 0;
  left: 0;
  transform: none;
  width: 100vw;
  max-width: 100vw;
  height: 100dvh;
  max-height: 100dvh;
  margin: 0;
  border: 0;
  border-radius: 0;
  overflow: hidden;
}

#modal_create_site[open],
#modal_edit_site[open] {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  grid-template-rows: minmax(0, 1fr);
}

#modal_create_site > form,
#modal_edit_site > form {
  grid-column: 1;
  grid-row: 1;
  display: grid;
  grid-template-rows: auto minmax(0, 1fr) auto;
  min-width: 0;
  min-height: 0;
}

#modal_create_site .modal_header,
#modal_edit_site .modal_header {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  min-height: 3.75rem;
  margin: 0;
  padding: 0.75rem 1rem;
}

#modal_create_site .modal_title,
#modal_edit_site .modal_title {
  grid-column: 1;
  justify-self: start;
  margin: 0;
  padding: 0;
  text-align: left !important;
}

#modal_create_site .modal_header .btn_close,
#modal_edit_site .modal_header .btn_close {
  grid-column: 2;
  justify-self: end;
}

#modal_create_site .modal_content,
#modal_edit_site .modal_content {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  align-items: stretch;
  gap: 0;
  padding: 0;
  overflow: hidden;
  min-height: 0;
}

#modal_create_site .modal_content {
  align-content: start;
  gap: 0.9rem;
  padding: 1.25rem;
  overflow-y: auto;
}

#modal_create_site .modal_footer,
#modal_edit_site .modal_footer {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  justify-items: center;
  gap: 0.6rem;
  margin: 0;
  padding: 0.8rem 1rem 1rem;
}

#modal_create_site .modal_footer > .flex,
#modal_edit_site .modal_footer > .flex {
  justify-content: center;
  gap: 0.75rem;
  width: 100%;
}

#modal_confirm_delete_site,
#modal_finality_delete,
#modal_archived_work {
  width: min(42rem, calc(100vw - 2rem));
  max-width: min(42rem, calc(100vw - 2rem));
  max-height: calc(100dvh - 2rem);
  margin: auto;
  border: 1px solid var(--business-page-border, var(--panel-border, #2a2a2a));
  border-radius: 0.5rem;
  background: var(--business-page-elevated-bg, var(--panel-bg, #171d24));
  color: var(--panel-text, #f4f8ff);
  overflow: hidden;
}

#modal_archived_work {
  width: min(56rem, calc(100vw - 2rem));
  max-width: min(56rem, calc(100vw - 2rem));
}

#modal_confirm_delete_site[open],
#modal_finality_delete[open],
#modal_archived_work[open] {
  display: grid;
  grid-template-rows: auto minmax(0, 1fr) auto;
}

#modal_confirm_delete_site .modal_header,
#modal_finality_delete .modal_header,
#modal_archived_work .modal_header {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  min-height: 3.75rem;
  margin: 0;
  padding: 0.75rem 1rem;
}

#modal_confirm_delete_site .modal_title,
#modal_finality_delete .modal_title,
#modal_archived_work .modal_title {
  grid-column: 1;
  justify-self: start;
  margin: 0;
  padding: 0;
  text-align: left !important;
}

#modal_confirm_delete_site .modal_header .btn_close,
#modal_finality_delete .modal_header .btn_close,
#modal_archived_work .modal_header .btn_close {
  grid-column: 2;
  justify-self: end;
}

#modal_confirm_delete_site .modal_content,
#modal_finality_delete .modal_content,
#modal_archived_work .modal_content {
  display: block;
  min-height: 0;
  padding: 1rem;
  overflow: auto;
  border-top: 1px solid var(--business-page-border, var(--panel-border, #2a2a2a));
  border-bottom: 1px solid var(--business-page-border, var(--panel-border, #2a2a2a));
}

#modal_confirm_delete_site .modal_content p,
#modal_finality_delete .modal_content p {
  margin: 0;
  color: var(--panel-text, #f4f8ff);
  font-size: 0.95rem;
  line-height: 1.45;
  letter-spacing: 0;
}

#modal_confirm_delete_site .modal_footer,
#modal_finality_delete .modal_footer,
#modal_archived_work .modal_footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  margin: 0;
  padding: 0.8rem 1rem 1rem;
}

#modal_confirm_delete_site .modal_footer > .flex,
#modal_finality_delete .modal_footer > .flex,
#modal_archived_work .modal_footer > .flex {
  justify-content: center;
  gap: 0.75rem;
  width: 100%;
}

.edit_site_col {
  display: grid;
  grid-auto-rows: max-content;
  align-content: start;
  gap: 1rem;
  padding: clamp(1rem, 2.2vw, 1.75rem);
  overflow-y: auto;
  min-height: 0;
}

.edit_site_col_basic {
  border-right: 1px solid var(--panel-border, #2a2a2a);
}

.edit_site_col_heading {
  margin: 0 0 0.35rem;
  padding-bottom: 0.55rem;
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
  min-height: 3.5rem;
  padding: 0;
  width: 100%;
}

#modal_edit_site .item_label {
  text-align: right;
  padding: 0;
  font-size: 0.88rem;
  line-height: 1.25;
}

#modal_edit_site .item_value {
  display: grid;
  gap: 0.3rem;
  min-width: 0;
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
  padding-top: 0.4rem;
  padding-bottom: 0.4rem;
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
  width: 100%;
  overflow: visible;
}

.site_color_swatches {
  display: grid;
  grid-template-columns: repeat(5, 2rem);
  gap: 0.45rem;
  align-items: center;
  padding-top: 0.15rem;
  isolation: isolate;
}

.site_color_swatch {
  display: block;
  width: 2rem;
  height: 2rem;
  border-radius: 5px;
  border: 2px solid transparent;
  cursor: pointer;
  padding: 0;
  transition: border-color 0.1s ease;
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
  transition: opacity 0.12s ease;
}

.hover_help_tooltip.is-visible {
  opacity: 1;
}

.modal_orphaned_work .modal_header .btn_close {
  z-index: 10;
}

/* ── Sites DataGrid column layout ───────────────────────────────────────
   Source of truth for --grid-template-columns.
   Tracks: Site | Entries | Gross Pay | Wage | Last worked | Budget | Used | Actions
   Matches the Business Sites summary grid while keeping planning columns optional.
   ──────────────────────────────────────────────────────────────────────── */
[data-grid="sites-active"],
[data-grid="sites-archived"] {
  --grid-template-columns:
    minmax(14rem, 3fr)
    minmax(4rem, 0.75fr)
    minmax(6rem, 1fr)
    minmax(5rem, 0.85fr)
    minmax(7rem, 1fr)
    minmax(6rem, 1fr)
    minmax(7rem, 1.1fr)
    minmax(5.5rem, max-content);
  font-size: 0.9em;
}

[data-grid="sites-active"] .datagrid_item,
[data-grid="sites-active"] .datagrid_heading,
[data-grid="sites-archived"] .datagrid_item,
[data-grid="sites-archived"] .datagrid_heading {
  min-width: 0;
  padding: 0.4rem 0.5rem;
}

/* Explicit column template: Site | Entries | Gross Pay | Wage | Last worked | Budget | Used | Action */
[data-grid="sites-active"] .datagrid_header_content,
[data-grid="sites-active"] .datagrid_row_content,
[data-grid="sites-archived"] .datagrid_header_content,
[data-grid="sites-archived"] .datagrid_row_content {
  grid-template-columns:
    minmax(14rem, 3fr)
    minmax(4rem, 0.75fr)
    minmax(6rem, 1fr)
    minmax(5rem, 0.85fr)
    minmax(7rem, 1fr)
    minmax(6rem, 1fr)
    minmax(7rem, 1.1fr)
    minmax(5.5rem, max-content);
}

[data-grid="sites-active"] .datagrid_heading,
[data-grid="sites-archived"] .datagrid_heading {
  padding: 0.5rem 0.5rem 0.6rem 0.5rem;
  font-weight: 600;
}

[data-grid^="sites-"] .datagrid_body .datagrid_row:not(:last-child) > .datagrid_row_content {
  border-bottom: var(--border-size, 1px) solid rgba(255, 255, 255, 0.06);
  border-bottom-color: color-mix(in srgb, var(--panel-border, currentColor) 28%, transparent);
}

/* Tighter numeric data cells (headings stay uniform above) */
[data-grid="sites-active"] .datagrid_item.datagrid_col_wage,
[data-grid="sites-active"] .datagrid_item.datagrid_col_living_out_allowance,
[data-grid="sites-active"] .datagrid_item.datagrid_col_travel_hours,
[data-grid="sites-active"] .datagrid_item.datagrid_col_entries,
[data-grid="sites-active"] .datagrid_item.datagrid_col_work_gross,
[data-grid="sites-active"] .datagrid_item.datagrid_col_last_worked,
[data-grid="sites-active"] .datagrid_item.datagrid_col_budget_amount,
[data-grid="sites-active"] .datagrid_item.datagrid_col_budget_used,
[data-grid="sites-archived"] .datagrid_item.datagrid_col_wage,
[data-grid="sites-archived"] .datagrid_item.datagrid_col_living_out_allowance,
[data-grid="sites-archived"] .datagrid_item.datagrid_col_travel_hours,
[data-grid="sites-archived"] .datagrid_item.datagrid_col_entries,
[data-grid="sites-archived"] .datagrid_item.datagrid_col_work_gross,
[data-grid="sites-archived"] .datagrid_item.datagrid_col_last_worked,
[data-grid="sites-archived"] .datagrid_item.datagrid_col_budget_amount,
[data-grid="sites-archived"] .datagrid_item.datagrid_col_budget_used {
  font-size: 0.82rem;
  font-variant-numeric: tabular-nums;
}

[data-grid="sites-active"] .business_sites_site_name_cell,
[data-grid="sites-archived"] .business_sites_site_name_cell {
  display: inline-grid;
  gap: 0.2rem;
  min-width: 0;
}

[data-grid="sites-active"] .business_sites_site_name_primary,
[data-grid="sites-archived"] .business_sites_site_name_primary {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  min-width: 0;
}

[data-grid="sites-active"] .business_sites_site_name_text,
[data-grid="sites-archived"] .business_sites_site_name_text {
  min-width: 0;
  font-weight: 700;
  color: var(--color-text, #f4f8ff);
  overflow: hidden;
  text-overflow: ellipsis;
}

[data-grid="sites-active"] .business_sites_ownership_symbol,
[data-grid="sites-archived"] .business_sites_ownership_symbol {
  flex: 0 0 auto;
  display: inline-block;
  width: 0.45rem;
  height: 0.45rem;
  background: #2dd4bf;
}

[data-grid="sites-active"] .business_sites_ownership_symbol--personal,
[data-grid="sites-archived"] .business_sites_ownership_symbol--personal {
  border-radius: 2px;
}

[data-grid="sites-active"] .business_sites_ownership_status,
[data-grid="sites-archived"] .business_sites_ownership_status {
  display: inline-flex;
  width: fit-content;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  font-size: 0.82rem;
  font-weight: 600;
  line-height: 1.2;
}

[data-grid="sites-active"] .business_sites_ownership_status--personal,
[data-grid="sites-archived"] .business_sites_ownership_status--personal {
  background: color-mix(in srgb, #22c55e 72%, transparent);
  color: #ffffff;
}

/* Budget data cells: accent colour when set, muted when empty */
[data-grid="sites-active"] .datagrid_item.datagrid_col_budget_amount:not(:empty),
[data-grid="sites-archived"] .datagrid_item.datagrid_col_budget_amount:not(:empty) {
  color: var(--color-primary, #4a9eff);
  font-weight: 600;
}

[data-grid="sites-active"] .datagrid_col_ownership,
[data-grid="sites-archived"] .datagrid_col_ownership {
  color: var(--text-muted, #888);
  font-size: 0.8rem;
}

.site_budget_used_cell {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 0.45rem;
  width: 100%;
}

.site_budget_progress {
  width: 100%;
  height: 0.45rem;
  min-width: 3rem;
  overflow: hidden;
}

.site_budget_used_value {
  font-variant-numeric: tabular-nums;
}

[data-grid="sites-active"] .datagrid_col_site_name,
[data-grid="sites-archived"] .datagrid_col_site_name {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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

/* Business /business/sites/ assigned grid (loaded via sites.css on that page). */
[data-grid="business-sites-active"],
[data-grid="business-sites-archived"] {
  width: 100%;
  max-width: none;
  --grid-template-columns:
    minmax(14rem, 3fr)
    minmax(4rem, 0.75fr)
    minmax(6rem, 1fr)
    minmax(5rem, 0.85fr)
    minmax(7rem, 1fr)
    minmax(6rem, 1fr)
    minmax(7rem, 1.1fr)
    minmax(5.5rem, max-content);
  font-size: 0.9em;
}

[data-grid="business-sites-active"] .datagrid_header_content,
[data-grid="business-sites-active"] .datagrid_row_content,
[data-grid="business-sites-archived"] .datagrid_header_content,
[data-grid="business-sites-archived"] .datagrid_row_content {
  grid-template-columns:
    minmax(14rem, 3fr)
    minmax(4rem, 0.75fr)
    minmax(6rem, 1fr)
    minmax(5rem, 0.85fr)
    minmax(7rem, 1fr)
    minmax(6rem, 1fr)
    minmax(7rem, 1.1fr)
    minmax(5.5rem, max-content);
}

[data-grid="business-sites-active"] .datagrid_item,
[data-grid="business-sites-active"] .datagrid_heading,
[data-grid="business-sites-archived"] .datagrid_item,
[data-grid="business-sites-archived"] .datagrid_heading {
  min-width: 0;
  padding: 0.4rem 0.5rem;
}

[data-grid="business-sites-active"] .datagrid_col_site_name,
[data-grid="business-sites-archived"] .datagrid_col_site_name,
[data-grid="business-sites-active"] .datagrid_col_budget_used,
[data-grid="business-sites-archived"] .datagrid_col_budget_used {
  white-space: normal;
  overflow: visible;
  text-overflow: clip;
  line-height: 1.25;
}

/* Personal sites scope note */
.sites_personal_scope_note {
  display: block;
  margin-top: 0.35rem;
  color: var(--text-muted, #888);
  font-size: var(--font-sm, 0.875rem);
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
    max-width: 100%;
    gap: 5px;
  }

  #main:has(.sites_main_container) > .sites_main_container > .f_column + .f_column {
    margin-top: 0.85rem;
  }

  #main:has(.sites_main_container) section.panel {
    width: 100%;
    max-width: 100%;
    padding: 5px;
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
    gap: 5px;
  }

  #main:has(.sites_main_container) > .sites_main_container > .f_column + .f_column {
    margin-top: 12px;
  }

  #main:has(.sites_main_container) section.panel {
    padding: 5px;
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
   Sites DataGrid — responsive financial summary cards
   ========================================================================== */
@media (max-width: 899px) {
  #sites_list_panel .tab-disclaimer {
    line-height: 1.35;
  }

  [data-grid="sites-active"] .datagrid_controls,
  [data-grid="sites-archived"] .datagrid_controls {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 0.45rem;
    margin-bottom: 0.55rem;
  }

  [data-grid="sites-active"] .datagrid_search,
  [data-grid="sites-archived"] .datagrid_search {
    min-width: 0;
    width: 100%;
  }

  [data-grid="sites-active"] .datagrid_column_strip,
  [data-grid="sites-archived"] .datagrid_column_strip {
    margin: 0 0 0.65rem;
  }

  [data-grid="sites-active"] .datagrid_header_row,
  [data-grid="sites-archived"] .datagrid_header_row {
    display: none;
  }

  [data-grid="sites-active"] .datagrid_body,
  [data-grid="sites-archived"] .datagrid_body {
    display: grid;
    gap: 0.75rem;
  }

  [data-grid="sites-active"] .datagrid_row,
  [data-grid="sites-archived"] .datagrid_row {
    position: relative;
    border: 1px solid var(--border, rgba(255,255,255,0.12));
    border-left-width: 0.4rem;
    border-radius: 6px;
    margin: 0;
    background: color-mix(in srgb, var(--panel-bg, #151515) 88%, var(--color-primary, #4a9eff) 12%);
    overflow: hidden;
  }

  [data-grid="sites-active"] .datagrid_row_content,
  [data-grid="sites-archived"] .datagrid_row_content {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr)) auto;
    grid-template-areas:
      "site site action"
      "entries gross gross"
      "wage last last"
      "budget budget budget"
      "used used used";
    gap: 0.7rem;
    align-items: stretch;
    padding: 0.85rem 0.85rem 0.95rem 1rem;
  }

  [data-grid="sites-active"] .datagrid_item::before,
  [data-grid="sites-archived"] .datagrid_item::before {
    display: block;
    margin-bottom: 0.2rem;
    content: attr(data-col-label);
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--text-muted);
  }

  [data-grid="sites-active"] .datagrid_col_site_name,
  [data-grid="sites-archived"] .datagrid_col_site_name {
    grid-area: site;
    min-width: 0;
    padding: 0 2.8rem 0 0;
    font-size: 1rem;
    font-weight: 800;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  [data-grid="sites-active"] .datagrid_col_site_name::before,
  [data-grid="sites-archived"] .datagrid_col_site_name::before {
    content: none;
  }

  [data-grid="sites-active"] .datagrid_col_ownership,
  [data-grid="sites-archived"] .datagrid_col_ownership {
    grid-area: ownership;
    justify-self: start;
    width: fit-content;
    max-width: 100%;
    padding: 0.2rem 0.55rem;
    border: 1px solid color-mix(in srgb, var(--panel-border, #555) 70%, transparent);
    border-radius: 999px;
    background: color-mix(in srgb, var(--panel-bg, #151515) 70%, var(--button-bg, #333) 30%);
    color: var(--text-muted, #aaa);
    font-size: 0.72rem;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  [data-grid="sites-active"] .datagrid_col_ownership::before,
  [data-grid="sites-archived"] .datagrid_col_ownership::before {
    content: none;
  }

  [data-grid="sites-active"] .datagrid_item.datagrid_col_living_out_allowance,
  [data-grid="sites-active"] .datagrid_item.datagrid_col_travel_hours,
  [data-grid="sites-active"] .datagrid_item.datagrid_col_province,
  [data-grid="sites-archived"] .datagrid_item.datagrid_col_living_out_allowance,
  [data-grid="sites-archived"] .datagrid_item.datagrid_col_travel_hours,
  [data-grid="sites-archived"] .datagrid_item.datagrid_col_province {
    display: none;
  }

  [data-grid="sites-active"] .datagrid_col_entries,
  [data-grid="sites-active"] .datagrid_col_work_gross,
  [data-grid="sites-active"] .datagrid_item.datagrid_col_wage,
  [data-grid="sites-active"] .datagrid_item.datagrid_col_last_worked,
  [data-grid="sites-archived"] .datagrid_col_entries,
  [data-grid="sites-archived"] .datagrid_col_work_gross,
  [data-grid="sites-archived"] .datagrid_item.datagrid_col_wage,
  [data-grid="sites-archived"] .datagrid_item.datagrid_col_last_worked {
    min-width: 0;
    padding: 0.55rem 0.65rem;
    border: 1px solid color-mix(in srgb, var(--panel-border, #555) 78%, transparent);
    border-radius: 6px;
    background: color-mix(in srgb, var(--panel-bg, #151515) 82%, var(--button-bg, #333) 18%);
    font-size: 1rem;
    font-weight: 800;
    text-align: left;
  }

  [data-grid="sites-active"] .datagrid_col_entries,
  [data-grid="sites-archived"] .datagrid_col_entries {
    grid-area: entries;
  }

  [data-grid="sites-active"] .datagrid_col_work_gross,
  [data-grid="sites-archived"] .datagrid_col_work_gross {
    grid-area: gross;
  }

  [data-grid="sites-active"] .datagrid_item.datagrid_col_wage,
  [data-grid="sites-archived"] .datagrid_item.datagrid_col_wage {
    grid-area: wage;
  }

  [data-grid="sites-active"] .datagrid_item.datagrid_col_last_worked,
  [data-grid="sites-archived"] .datagrid_item.datagrid_col_last_worked {
    grid-area: last;
  }

  [data-grid="sites-active"] .datagrid_col_budget_amount,
  [data-grid="sites-archived"] .datagrid_col_budget_amount {
    grid-area: budget;
    padding: 0.2rem 0 0;
    color: var(--color-primary, #4a9eff);
    font-size: 0.98rem;
    font-weight: 800;
    text-align: left;
  }

  [data-grid="sites-active"] .datagrid_col_budget_amount:empty,
  [data-grid="sites-archived"] .datagrid_col_budget_amount:empty {
    display: none;
  }

  [data-grid="sites-active"] .datagrid_col_budget_used,
  [data-grid="sites-archived"] .datagrid_col_budget_used {
    grid-area: used;
    padding: 0;
  }

  [data-grid="sites-active"] .datagrid_col_budget_used:empty,
  [data-grid="sites-archived"] .datagrid_col_budget_used:empty {
    display: none;
  }

  [data-grid="sites-active"] .datagrid_col_budget_used::before,
  [data-grid="sites-archived"] .datagrid_col_budget_used::before {
    content: none;
  }

  [data-grid="sites-active"] .datagrid_item_actions::before,
  [data-grid="sites-archived"] .datagrid_item_actions::before { content: none; }

  [data-grid="sites-active"] .datagrid_item_actions,
  [data-grid="sites-archived"] .datagrid_item_actions {
    grid-area: action;
    align-self: start;
    justify-self: end;
    padding: 0;
    margin: 0;
    border: 0;
    text-align: right;
  }

  [data-grid="sites-active"] .datagrid_actions,
  [data-grid="sites-archived"] .datagrid_actions {
    justify-content: flex-end;
  }

  [data-grid="sites-active"] .datagrid_action,
  [data-grid="sites-archived"] .datagrid_action {
    width: 2.15rem;
    min-width: 2.15rem;
    height: 2.15rem;
    padding: 0;
    border-radius: 999px;
    font-size: 1rem;
  }
}

@media (max-width: 600px) {
  #sites_list_panel .tabs {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  [data-grid="sites-active"] .datagrid_controls,
  [data-grid="sites-archived"] .datagrid_controls {
    grid-template-columns: 1fr;
  }

  [data-grid="sites-active"] .datagrid_control,
  [data-grid="sites-active"] .datagrid_search,
  [data-grid="sites-archived"] .datagrid_control,
  [data-grid="sites-archived"] .datagrid_search {
    width: 100%;
  }
}

@media (max-width: 420px) {
  [data-grid="sites-active"] .datagrid_row_content,
  [data-grid="sites-archived"] .datagrid_row_content {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-template-areas:
      "site action"
      "entries gross"
      "wage last"
      "budget budget"
      "used used";
    gap: 0.55rem;
    padding: 0.75rem;
  }

  [data-grid="sites-active"] .datagrid_col_entries,
  [data-grid="sites-active"] .datagrid_col_work_gross,
  [data-grid="sites-active"] .datagrid_item.datagrid_col_wage,
  [data-grid="sites-active"] .datagrid_item.datagrid_col_last_worked,
  [data-grid="sites-archived"] .datagrid_col_entries,
  [data-grid="sites-archived"] .datagrid_col_work_gross,
  [data-grid="sites-archived"] .datagrid_item.datagrid_col_wage,
  [data-grid="sites-archived"] .datagrid_item.datagrid_col_last_worked {
    padding: 0.5rem;
    font-size: 0.95rem;
  }

  [data-grid="sites-active"] .datagrid_item::before,
  [data-grid="sites-archived"] .datagrid_item::before {
    font-size: 0.62rem;
  }
}

@media (max-width: 768px) {
  #modal_create_site .modal_content,
  #modal_edit_site .modal_content {
    grid-template-columns: minmax(0, 1fr);
    gap: 0;
    overflow-y: auto;
  }

  .edit_site_col {
    gap: 1.35rem;
    padding: 1.25rem 1rem 1.5rem;
    overflow: visible;
  }

  .edit_site_col_basic {
    border-right: 0;
    border-bottom: 1px solid var(--panel-border, #2a2a2a);
  }

  #modal_create_site .item_pair,
  #modal_edit_site .item_pair {
    grid-template-columns: minmax(0, 1fr);
    align-items: stretch;
    gap: 0.55rem;
    min-height: 0;
    padding: 0 0 0.15rem;
  }

  #modal_create_site .item_label,
  #modal_edit_site .item_label {
    display: block;
    text-align: left;
    line-height: 1.35;
    margin: 0;
  }

  #modal_create_site input:not([type="color"]),
  #modal_create_site select,
  #modal_edit_site input:not([type="color"]),
  #modal_edit_site select {
    min-height: 3rem;
  }

  .item_pair_color {
    gap: 0.7rem;
    padding-top: 0.15rem;
    padding-bottom: 1.4rem;
    margin-bottom: 0.4rem;
    border-bottom: 1px solid color-mix(in srgb, var(--panel-border, #2a2a2a) 72%, transparent);
  }

  .item_label_color {
    display: flex !important;
    flex-direction: column;
    gap: 0.25rem;
  }

  .site_color_name {
    display: block;
    min-height: 1.1rem;
    line-height: 1.25;
  }

  .site_color_swatches {
    grid-template-columns: repeat(auto-fill, minmax(2rem, 2rem));
    gap: 0.65rem;
    padding-top: 0.25rem;
    padding-bottom: 0.35rem;
    overflow: visible;
  }

  .site_color_swatch:hover {
    transform: none;
  }

  .site_color_swatch.is-selected {
    position: relative;
    z-index: 1;
  }

  .edit_site_col_advanced {
    padding-top: 1.65rem;
  }
}

@media (max-width: 1100px) {
  #modal_create_site .modal_content,
  #modal_edit_site .modal_content {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-content: start;
    gap: 0;
    overflow-y: auto;
  }

  .edit_site_col {
    display: grid;
    grid-auto-rows: max-content;
    align-content: start;
    width: 100%;
    max-width: 100%;
    min-height: auto;
    overflow: visible;
  }

  .edit_site_col_basic {
    border-right: 0;
    border-bottom: 1px solid var(--panel-border, #2a2a2a);
  }

  #modal_edit_site .item_pair.item_pair_color {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: start;
    gap: 0.7rem;
    min-height: auto;
    padding-bottom: 1.5rem;
    margin-bottom: 1.1rem;
  }

  #modal_edit_site .item_pair.item_pair_color .item_label,
  #modal_edit_site .item_pair.item_pair_color .item_value {
    display: grid;
    gap: 0.35rem;
    width: 100%;
    text-align: left;
  }

  #modal_edit_site .item_pair.item_pair_color .item_value {
    margin-top: 0;
  }

  .site_color_picker,
  .site_color_swatches {
    width: 100%;
  }

  .site_color_swatches {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(2rem, 2rem));
    align-items: flex-start;
    gap: 0.65rem;
    padding: 0;
    overflow: visible;
  }

  .site_color_swatch,
  .site_color_swatch:hover {
    position: static;
    transform: none;
  }

  .edit_site_col_advanced {
    clear: both;
    padding-top: 1.5rem;
  }
}

@media (max-width: 420px) {
  #modal_create_site .modal_header,
  #modal_edit_site .modal_header {
    min-height: 3.5rem;
    padding: 0.65rem 0.85rem;
  }

  .edit_site_col {
    gap: 1.25rem;
    padding: 1.15rem 0.85rem 1.35rem;
  }

  #modal_create_site .modal_footer,
  #modal_edit_site .modal_footer {
    padding: 0.75rem 0.85rem 0.9rem;
  }
}

#site_earnings_container {
  display: grid;
  gap: 0.85rem;
  min-height: 16rem;
}

#site_earnings_loading,
#site_earnings_list,
#site_earnings_totals,
#site_earnings_empty {
  grid-area: 1 / 1;
  margin-top: 0.35rem;
}

.site_earnings_state {
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
}

.site_earnings_state.is-active {
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
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

.site_earnings_totals_summary {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 0.55rem;
  width: 100%;
}

.site_earnings_total_item {
  display: grid;
  gap: 0.25rem;
  min-width: 0;
  padding: 0.55rem 0.65rem;
  border: 1px solid color-mix(in srgb, var(--panel-border, #555) 78%, transparent);
  border-radius: 6px;
  background: color-mix(in srgb, var(--panel-bg, #151515) 82%, var(--button-bg, #333) 18%);
}

.site_earnings_total_label {
  color: var(--text-muted, #888);
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  line-height: 1.2;
  text-transform: uppercase;
}

.site_earnings_total_value {
  min-width: 0;
  font-size: 0.92rem;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  line-height: 1.35;
  overflow-wrap: anywhere;
}

.site_earnings_row {
  display: grid;
  gap: 0.8rem;
  padding: 0.95rem;
  border: 1px solid var(--panel-border);
  border-left: 0.4rem solid var(--color-primary, #4a9eff);
  border-radius: 6px;
  background: color-mix(in srgb, var(--panel-bg, #151515) 88%, var(--color-primary, #4a9eff) 12%);
}

.site_earnings_row + .site_earnings_row {
  margin-top: 0.75rem;
}

.site_earnings_header {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: start;
  gap: 0.85rem;
  margin: 0;
}

.site_earnings_title_group {
  display: grid;
  gap: 0.35rem;
  min-width: 0;
}

.site_earnings_title {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 1rem;
  font-weight: 800;
  line-height: 1.25;
}

.site_earnings_amount {
  font-size: 1rem;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}

.site_archived_badge {
  justify-self: start;
  width: fit-content;
  max-width: 100%;
  padding: 0.2rem 0.55rem;
  border: 1px solid color-mix(in srgb, var(--panel-border, #555) 70%, transparent);
  border-radius: 999px;
  background: color-mix(in srgb, var(--panel-bg, #151515) 70%, var(--button-bg, #333) 30%);
  color: var(--text-muted, #aaa);
  font-size: 0.72rem;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.site_earnings_details {
  margin-top: 0.45rem;
  line-height: 1.45;
}

.site_earnings_metrics {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(5rem, 0.8fr);
  gap: 0.65rem;
}

.site_earnings_metric {
  display: grid;
  gap: 0.25rem;
  min-width: 0;
  padding: 0.55rem 0.65rem;
  border: 1px solid color-mix(in srgb, var(--panel-border, #555) 78%, transparent);
  border-radius: 6px;
  background: color-mix(in srgb, var(--panel-bg, #151515) 82%, var(--button-bg, #333) 18%);
}

.site_earnings_metric_label,
.site_earnings_budget_header {
  color: var(--text-muted, #888);
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.site_earnings_metric_value {
  min-width: 0;
  font-size: 0.92rem;
  font-weight: 800;
  line-height: 1.35;
  overflow-wrap: anywhere;
}

.site_earnings_budget {
  display: grid;
  gap: 0.35rem;
}

.site_earnings_budget_header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.65rem;
}

.site_earnings_bar {
  width: 100%;
  height: 0.45rem;
  overflow: hidden;
  border-radius: 999px;
  background: color-mix(in srgb, var(--panel-border, #555) 45%, transparent);
}

.site_earnings_bar_fill {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: var(--color-primary, #4a9eff);
}

.site_earnings_bar_fill[data-width="0"] { width: 0%; }
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

@media (max-width: 600px) {
  .site_earnings_totals_summary {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .site_earnings_total_item_primary {
    grid-column: 1 / -1;
  }

  .site_earnings_row {
    padding: 0.9rem;
  }

  .site_earnings_metrics {
    grid-template-columns: minmax(0, 1fr) minmax(4.5rem, 0.55fr);
  }
}

@media (max-width: 420px) {
  .site_earnings_header {
    grid-template-columns: minmax(0, 1fr);
    gap: 0.45rem;
  }

  .site_earnings_amount {
    justify-self: start;
  }

  .site_earnings_metrics {
    grid-template-columns: minmax(0, 1fr);
  }
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

/* Personal Sites controls: mirror the compact Groups page pattern. */
#sites_list_panel .sites_status_row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem 1rem;
  margin: 0 0 1rem;
}

#sites_list_panel .sites_status_action_group {
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  gap: 0.4rem;
  padding: 0.35rem;
  border: var(--border-size) solid var(--panel-border);
  border-radius: var(--border-radius);
  background-color: var(--panel-bg);
  box-shadow: 0 0.25rem 0.25rem rgba(0, 0, 0, 0.75);
}

#sites_list_panel .sites_status_action_group > .tabs {
  width: auto;
  margin: 0;
  padding: 0;
  border: 0;
  background: transparent;
  box-shadow: none;
  backdrop-filter: none;
  overflow: visible;
}

#sites_list_panel .sites_status_action_group .tab,
#sites_list_panel .sites_status_action_group .sites_status_add_button {
  min-height: 2.25rem;
  padding: 0.45rem 0.7rem;
  line-height: 1.2;
}

#sites_list_panel .sites_status_action_group .tab {
  border: 1px solid var(--business-page-border, var(--button-border));
  border-radius: 0.4rem;
  background: var(--business-page-secondary-bg, color-mix(in srgb, var(--panel-bg, #151b24) 78%, #ffffff 8%));
  color: var(--business-page-secondary-text, #f4f8ff);
}

#sites_list_panel .sites_status_action_group .tab[aria-selected="true"],
#sites_list_panel .sites_status_action_group .tab.active {
  border-color: color-mix(in srgb, var(--business-page-primary-bg, #0b63ce) 44%, var(--business-page-border, var(--panel-border, #6b7280)) 56%);
  background: color-mix(in srgb, var(--business-page-primary-bg, #0b63ce) 20%, var(--business-page-secondary-bg, var(--panel-bg, #151b24)) 80%);
  color: #ffffff;
  box-shadow: inset 0 -2px 0 color-mix(in srgb, var(--business-page-primary-bg-hover, #0d70e8) 56%, transparent);
}

#sites_list_panel .sites_status_action_group .tab:hover,
#sites_list_panel .sites_status_action_group .tab:focus-visible {
  border-color: color-mix(in srgb, var(--business-page-primary-bg-hover, #0d70e8) 60%, #ffffff 40%);
  color: #ffffff;
}

#sites_list_panel .sites_status_action_group .sites_status_add_button {
  border-color: color-mix(in srgb, var(--business-page-primary-bg, #0b63ce) 82%, #ffffff 18%);
  background-color: var(--business-page-primary-bg, #0b63ce);
  color: var(--business-page-primary-text, #ffffff);
  letter-spacing: 0;
  white-space: nowrap;
}

#sites_list_panel .sites_status_add_button:hover,
#sites_list_panel .sites_status_add_button:focus-visible {
  background-color: var(--business-page-primary-bg-hover, #0d70e8);
  color: var(--business-page-primary-text, #ffffff);
}

#sites_list_panel .sites_ownership_legend {
  display: flex;
  flex: 1 1 16rem;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: 0.5rem 0.85rem;
  margin: 0;
}

#sites_list_panel .sites_ownership_legend_item {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  color: var(--color-text, #f4f8ff);
  font-size: var(--font-sm, 0.875rem);
  font-weight: 600;
}

#sites_list_panel [data-grid="sites-active"],
#sites_list_panel [data-grid="sites-archived"] {
  display: grid;
  grid-template-columns: minmax(14rem, 1fr) auto;
  align-items: start;
  gap: 0 0.75rem;
  width: 100%;
  max-width: none;
  --grid-template-columns:
    minmax(14rem, 3fr)
    minmax(4rem, 0.75fr)
    minmax(6rem, 1fr)
    minmax(5rem, 0.85fr)
    minmax(7rem, 1fr)
    minmax(6rem, 1fr)
    minmax(7rem, 1.1fr)
    minmax(5.5rem, max-content);
}

#sites_list_panel [data-grid^="sites-"] .datagrid_controls,
#sites_list_panel [data-grid^="sites-"] .datagrid_column_strip {
  margin-bottom: 0.75rem;
}

#sites_list_panel [data-grid^="sites-"] .datagrid_controls {
  grid-column: 1;
  min-width: 0;
}

#sites_list_panel [data-grid^="sites-"] .datagrid_search {
  flex: 1 1 auto;
  width: 100%;
  min-width: 0;
}

#sites_list_panel [data-grid^="sites-"] .datagrid_column_strip {
  grid-column: 2;
  align-self: start;
  justify-self: end;
  white-space: nowrap;
}

#sites_list_panel [data-grid^="sites-"] .datagrid_table,
#sites_list_panel [data-grid^="sites-"] .datagrid_pagination_bottom {
  grid-column: 1 / -1;
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
