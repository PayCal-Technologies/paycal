<?php declare(strict_types=1);

use PayCal\Domain\Config\SiteColorPalette;

require __DIR__ . '/../businesses/index.php';
require __DIR__ . '/subpages.css.php';

?>

.business_context_header {
  position: relative;
  z-index: 30;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.65rem 1rem;
  margin: 0 0 0.85rem;
  padding: 0;
  border-bottom: 2px solid var(--border);
}

.business_context_name {
  margin: 0;
  flex-shrink: 0;
  font-size: clamp(1rem, 1.6vw, 1.15rem);
  font-weight: 700;
  line-height: 1.2;
}

.business_context_separator {
  flex-shrink: 0;
  align-self: stretch;
  width: 1px;
  min-height: 1.35rem;
  background: var(--border);
}

.business_subnav {
  flex: 1 1 auto;
  min-width: 0;
}

.business_subnav_tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0;
}

.business_subnav_tab {
  display: inline-flex;
  align-items: center;
  padding: 0.4rem 0.65rem;
  text-decoration: none;
  color: inherit;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  font-size: 0.9rem;
  line-height: 1.2;
}

.business_subnav_tab--active,
.business_subnav_tab[aria-current='page'] {
  border-bottom-color: var(--color-accent, currentColor);
  font-weight: 600;
}

.business_subnav_tab.is-hidden-reserved,
.business_subnav_tab.hidden {
  visibility: hidden;
  pointer-events: none;
}

.business_context_actions {
  display: inline-flex;
  align-items: center;
  justify-content: flex-end;
  min-width: 0;
}

#main:has(#business-workspace.business_members) .business_context_header {
  display: grid;
  grid-template-columns: auto auto minmax(0, 1fr) auto;
  align-items: center;
  column-gap: 1rem;
  row-gap: 0.35rem;
}

#main:has(#business-workspace.business_members) .business_subnav {
  min-width: 0;
}

#business-workspace.business_members,
#business-workspace.business_sites,
#business-workspace.business_groups {
  --business-page-text: var(--panel-text, var(--color-text, #f4f8ff));
  --business-page-muted: #c8d2e2;
  --business-page-muted-strong: #dce6f5;
  --business-page-border: color-mix(in srgb, var(--panel-border, #6b7280) 76%, transparent);
  --business-page-elevated-bg: #171d24;
  --business-page-primary-bg: #0b63ce;
  --business-page-primary-bg-hover: #0d70e8;
  --business-page-primary-text: #ffffff;
  --business-page-secondary-bg: color-mix(in srgb, var(--panel-bg, #151b24) 78%, #ffffff 8%);
  --business-page-secondary-bg-hover: color-mix(in srgb, var(--panel-bg, #151b24) 68%, #ffffff 14%);
  --business-page-secondary-text: #f4f8ff;
}

#business-workspace.business_members .btn,
#business-workspace.business_sites .btn,
#business-workspace.business_groups .btn {
  letter-spacing: 0;
}

#business-workspace.business_members .btn_primary,
#business-workspace.business_sites .btn_primary,
#business-workspace.business_groups .btn_primary {
  border-color: color-mix(in srgb, var(--business-page-primary-bg) 82%, #ffffff 18%);
  background-color: var(--business-page-primary-bg);
  color: var(--business-page-primary-text);
}

#business-workspace.business_members .btn_primary:hover,
#business-workspace.business_members .btn_primary:focus-visible,
#business-workspace.business_sites .btn_primary:hover,
#business-workspace.business_sites .btn_primary:focus-visible,
#business-workspace.business_groups .btn_primary:hover,
#business-workspace.business_groups .btn_primary:focus-visible {
  background-color: var(--business-page-primary-bg-hover);
  color: var(--business-page-primary-text);
}

#business-workspace.business_members .btn_secondary,
#business-workspace.business_sites .btn_secondary,
#business-workspace.business_groups .btn_secondary {
  border-color: var(--business-page-border);
  background-color: var(--business-page-secondary-bg);
  color: var(--business-page-secondary-text);
}

#business-workspace.business_members .btn_secondary:hover,
#business-workspace.business_members .btn_secondary:focus-visible,
#business-workspace.business_sites .btn_secondary:hover,
#business-workspace.business_sites .btn_secondary:focus-visible,
#business-workspace.business_groups .btn_secondary:hover,
#business-workspace.business_groups .btn_secondary:focus-visible {
  background-color: var(--business-page-secondary-bg-hover);
  color: #ffffff;
}

/* Data-heavy subpages use the full #main content width at every breakpoint. */
#main:has(#business-workspace.business_sites),
#main:has(#business-workspace.business_groups),
#main:has(#business-workspace.business_members),
#main:has(#business-workspace.business_reports) {
  --businesses-content-width: 100%;
}

#main:has(#business-workspace.business_sites) .business_context_header,
#main:has(#business-workspace.business_sites) #business-workspace,
#main:has(#business-workspace.business_groups) .business_context_header,
#main:has(#business-workspace.business_groups) #business-workspace,
#main:has(#business-workspace.business_members) .business_context_header,
#main:has(#business-workspace.business_members) #business-workspace,
#main:has(#business-workspace.business_reports) .business_context_header,
#main:has(#business-workspace.business_reports) #business-workspace {
  width: 100%;
  max-width: none;
  margin-left: 0;
  margin-right: 0;
}

#business-workspace.business_reports .business_reports_org_form,
#business-workspace.business_reports .business_reports_panel_shell,
#business-workspace.business_reports .earnings_team_panel {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

#business-workspace.business_reports .business_reports_toolbar {
  position: sticky;
  top: 0.5rem;
  z-index: 5;
  display: grid;
  gap: 0.6rem;
  margin-bottom: clamp(0.75rem, 1.5vw, 1rem);
  padding: 0.75rem;
  border: 1px solid var(--panel-border, rgba(255, 255, 255, 0.14));
  border-radius: 0.45rem;
  background: color-mix(in srgb, var(--panel-bg, #1e2633) 96%, transparent);
  box-shadow: 0 0.35rem 1rem color-mix(in srgb, #000 18%, transparent);
}

#business-workspace.business_reports .business_reports_tabs,
#business-workspace.business_reports .business_reports_filters {
  display: flex;
  flex-wrap: wrap;
  align-items: end;
  gap: 0.45rem;
}

#business-workspace.business_reports .business_reports_tab {
  min-height: 2rem;
  padding: 0.35rem 0.7rem;
  border: 1px solid var(--border, rgba(255, 255, 255, 0.18));
  border-radius: 0.35rem;
  background: var(--surface, transparent);
  color: var(--color-text);
  font: inherit;
  font-size: 0.86rem;
  cursor: pointer;
}

#business-workspace.business_reports .business_reports_tab.active {
  border-color: var(--color-primary);
  background: color-mix(in srgb, var(--color-primary) 16%, transparent);
  color: var(--color-text);
  font-weight: 700;
}

#business-workspace.business_reports .business_reports_filters label {
  display: grid;
  gap: 0.2rem;
  min-width: min(10rem, 100%);
}

#business-workspace.business_reports .business_reports_filters label > span,
#business-workspace.business_reports .business_reports_named_view span {
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

#business-workspace.business_reports .business_reports_filters select,
#business-workspace.business_reports .business_reports_named_view input {
  min-width: 0;
  width: 100%;
}

#business-workspace.business_reports .business_reports_exception_toggle {
  display: inline-flex;
  grid-template-columns: auto 1fr;
  align-items: center;
  min-width: auto;
  min-height: 2rem;
  padding-inline: 0.25rem;
}

#business-workspace.business_reports .business_reports_exception_toggle span {
  text-transform: none;
  letter-spacing: 0;
  font-size: 0.84rem;
}

#business-workspace.business_reports .business_reports_customize_drawer,
#business-workspace.business_reports .business_reports_export_drawer {
  display: grid;
  gap: 0.7rem;
  margin-bottom: clamp(0.75rem, 1.5vw, 1rem);
  padding: 0.85rem;
  border: 1px solid var(--panel-border, rgba(255, 255, 255, 0.14));
  border-radius: 0.45rem;
  background: var(--panel-bg, rgba(25, 31, 38, 0.92));
}

#business-workspace.business_reports .business_reports_customize_drawer[hidden],
#business-workspace.business_reports .business_reports_export_drawer[hidden] {
  display: none;
}

#business-workspace.business_reports .business_reports_drawer_header,
#business-workspace.business_reports .business_reports_drawer_actions,
#business-workspace.business_reports .business_reports_preset_row,
#business-workspace.business_reports .business_reports_export_actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.45rem;
}

#business-workspace.business_reports .business_reports_drawer_header {
  justify-content: space-between;
}

#business-workspace.business_reports .business_reports_drawer_header h2 {
  margin: 0;
  font-size: 0.92rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

#business-workspace.business_reports .business_reports_named_view {
  display: grid;
  gap: 0.25rem;
  max-width: 22rem;
}

#business-workspace.business_reports .business_reports_module_list {
  display: grid;
  gap: 0.4rem;
}

#business-workspace.business_reports .business_reports_module_item {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto auto;
  align-items: center;
  gap: 0.35rem;
  padding: 0.45rem;
  border: 1px solid var(--border, rgba(255, 255, 255, 0.14));
  border-radius: 0.35rem;
}

#business-workspace.business_reports .business_reports_panel_shell .et_reports_panel_row:empty,
#business-workspace.business_reports .business_reports_panel_shell .et_intel_row:empty {
  display: none;
}

#business-workspace.business_reports .et_reports_panel_row {
  display: grid;
  grid-template-columns: 1fr;
  gap: clamp(1rem, 2vw, 1.25rem);
  margin-bottom: clamp(1rem, 2vw, 1.25rem);
}

#business-workspace.business_reports .et_reports_panel_row > .earnings_ytd_figure {
  margin-bottom: 0;
  min-width: 0;
}

@media (min-width: 960px) {
  #business-workspace.business_reports .et_reports_panel_row {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: stretch;
  }

  #business-workspace.business_reports .et_reports_panel_row > :only-child {
    grid-column: 1 / -1;
  }
}

#business-workspace.business_reports .earnings_ytd_figure .earnings_ytd_svg {
  min-width: 0;
}

.business_payroll_package_builder {
  display: grid;
  gap: 0.75rem;
  width: 100%;
  box-sizing: border-box;
  margin-bottom: clamp(1rem, 2vw, 1.25rem);
  padding: 0.9rem 1rem;
  border: 1px solid var(--panel-border, rgba(255, 255, 255, 0.16));
  border-radius: 0.45rem;
  background: var(--panel-bg, rgba(25, 31, 38, 0.92));
}

.business_payroll_package_builder[hidden] {
  display: none;
}

.business_payroll_package_header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem 1rem;
}

.business_payroll_package_header h2 {
  margin: 0;
  font-size: 0.95rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.business_payroll_package_header p,
.business_payroll_package_notice,
.business_payroll_package_status {
  margin: 0;
  color: var(--fore-muted, var(--text-muted, #b8c0cc));
  font-size: 0.82rem;
  line-height: 1.4;
}

.business_payroll_package_controls {
  display: flex;
  flex-wrap: wrap;
  align-items: end;
  gap: 0.55rem 0.75rem;
}

.business_payroll_package_controls label {
  display: grid;
  gap: 0.25rem;
  min-width: min(13rem, 100%);
}

.business_payroll_package_controls label span {
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.business_payroll_package_controls input,
.business_payroll_package_controls select {
  width: 100%;
  min-width: 0;
  box-sizing: border-box;
}

.business_payroll_package_notice {
  color: var(--warning-foreground, #f1c76b);
  align-self: center;
}

.business_payroll_package_summary {
  display: grid;
  gap: 0.6rem;
  padding-top: 0.65rem;
  border-top: 1px solid var(--panel-border, rgba(255, 255, 255, 0.14));
}

.business_payroll_package_summary[hidden] {
  display: none;
}

.business_payroll_package_summary h3 {
  margin: 0;
  font-size: 0.82rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.business_payroll_package_summary dl {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
  gap: 0.45rem 0.75rem;
  margin: 0;
}

.business_payroll_package_summary dl > div {
  min-width: 0;
}

.business_payroll_package_summary dt,
.business_payroll_package_summary dd {
  margin: 0;
  font-size: 0.82rem;
  line-height: 1.35;
}

.business_payroll_package_summary dt {
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
  font-weight: 700;
}

.business_payroll_package_summary dd {
  overflow-wrap: anywhere;
}

.business_payroll_package_actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
}

#business-workspace.business_dashboard .business_dashboard_metrics {
  margin-top: 0;
}

.business_dashboard_metrics_grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}

.business_dashboard_metric_card {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  padding: 0.85rem 0.95rem;
  border: 1px solid var(--border-subtle, rgba(255, 255, 255, 0.12));
  border-radius: 0.55rem;
  background: color-mix(in srgb, var(--surface-elevated, #fff) 8%, transparent);
  text-decoration: none;
  color: inherit;
  transition: border-color 0.15s ease, background-color 0.15s ease;
}

.business_dashboard_metric_card:hover,
.business_dashboard_metric_card:focus-visible {
  border-color: color-mix(in srgb, var(--color-primary, #4a9eff) 35%, var(--border, rgba(255, 255, 255, 0.18)));
  background: color-mix(in srgb, var(--color-primary, #4a9eff) 8%, var(--surface, #1e2633) 92%);
  color: var(--color-text);
}

.business_dashboard_metric_card:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

.business_dashboard_metric_label {
  font-size: 0.82rem;
  font-weight: 600;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
}

.business_dashboard_metric_value {
  display: inline-block;
  min-inline-size: 4ch;
  font-size: clamp(1.05rem, 1.6vw, 1.35rem);
  font-weight: 650;
  line-height: 1.25;
  color: var(--color-text);
  font-variant-numeric: tabular-nums;
}

.business_dashboard_metric_card:hover .business_dashboard_metric_label,
.business_dashboard_metric_card:focus-visible .business_dashboard_metric_label {
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
}

.business_dashboard_metric_card:hover .business_dashboard_metric_value,
.business_dashboard_metric_card:focus-visible .business_dashboard_metric_value {
  color: var(--color-text);
}

.business_dashboard_metric_card--muted .business_dashboard_metric_value {
  font-size: 1rem;
  font-weight: 600;
  color: var(--fore-muted, var(--text-muted, #b8c0cc));
}

.businesses_datagrid_skeleton_cell {
  min-height: 0.85rem;
  overflow: hidden;
}

@media (min-width: 768px) {
  .business_dashboard_metrics_grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (min-width: 1024px) {
  #business-workspace.business_dashboard .business_dashboard_metrics {
    padding: clamp(1.15rem, 2.2vw, 1.65rem) clamp(1.15rem, 2.4vw, 1.75rem);
  }
}

.business_sites_panels {
  display: grid;
  gap: 0.8rem;
  margin-top: 0.8rem;
}

#business-workspace.business_sites .businesses_panel_sites_discovery {
  display: grid;
  gap: 0.65rem;
  width: 100%;
  max-width: none;
  min-width: 0;
  color: var(--business-page-text, var(--panel-text, #f4f8ff));
}

.business_sites_grid_shell {
  margin-bottom: 1.5rem;
}

.business_sites_datagrid {
  width: 100%;
  max-width: none;
  min-width: 0;
  min-height: 12rem;
}

#business-workspace.business_sites .businesses_sites_assigned_panel {
  display: grid;
  gap: 1rem;
  width: 100%;
  max-width: none;
  min-width: 0;
  color: var(--business-page-text, var(--panel-text, #f4f8ff));
}

.business_sites_tabs {
  margin-bottom: 0.75rem;
}

/* Business sites DataGrid: Site | Entries | Gross Pay | Wage | Last worked | Budget | Used | Archive */
[data-grid="business-sites-active"],
[data-grid="business-sites-archived"] {
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
  font-size: 0.9em;
}

.business_sites_mobile_cards .datagrid_controls,
.business_sites_mobile_cards .datagrid_toolbar_search_pagination,
.business_sites_mobile_cards .datagrid_column_strip {
  margin-bottom: 0.75rem;
}

.business_sites_mobile_cards .datagrid_controls,
.business_sites_mobile_cards .datagrid_toolbar_search_pagination {
  grid-column: 1;
  min-width: 0;
}

.business_sites_mobile_cards .datagrid_column_strip {
  grid-column: 2;
  align-self: start;
  white-space: nowrap;
}

.business_sites_mobile_cards .datagrid_table,
.business_sites_mobile_cards .datagrid_pagination_bottom {
  grid-column: 1 / -1;
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

.business_sites_mobile_cards .datagrid_body .datagrid_row:not(:last-child) > .datagrid_row_content {
  border-bottom: var(--border-size, 1px) solid rgba(255, 255, 255, 0.06);
  border-bottom-color: color-mix(in srgb, var(--panel-border, currentColor) 28%, transparent);
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

/* Site color row wedge + inline chip (mirrors personal /sites grid). */
<?php
foreach (SiteColorPalette::pickerPalette() as $pc) {
  $h = strtoupper($pc['hex']);
  echo "[data-grid^=\"business-sites-\"] .datagrid_row[data-color=\"{$h}\"] { border-left: 5px solid {$h}; }\n";
}
?>

.business_sites_site_name_cell {
  display: inline-flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.3rem;
  max-width: 100%;
}

.business_sites_site_name_primary {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  min-width: 0;
}

.business_sites_site_name_text {
  min-width: 0;
  font-weight: 700;
  color: var(--business-page-text, var(--text-primary, #f5f5f5));
}

.business_sites_ownership_symbol {
  flex-shrink: 0;
  width: 0.55rem;
  height: 0.55rem;
  background: #2dd4bf;
  border: 1px solid color-mix(in srgb, #2dd4bf 70%, #0f766e);
}

.business_sites_ownership_symbol--business {
  border-radius: 50%;
}

.business_sites_ownership_symbol--personal {
  border-radius: 2px;
}

.business_sites_ownership_symbol--shared {
  border-radius: 1px;
  transform: rotate(45deg);
}

.business_sites_ownership_status {
  display: inline-block;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  font-size: 0.82rem;
  font-weight: 600;
  line-height: 1.2;
}

.business_sites_ownership_status--personal {
  background: #14532d;
  color: #86efac;
}

.business_sites_ownership_status--business {
  background: #1e3a5f;
  color: #93c5fd;
}

.business_sites_ownership_status--shared {
  background: #422006;
  color: #fdba74;
}

[data-grid="business-sites-active"] .datagrid_col_entries,
[data-grid="business-sites-active"] .datagrid_col_work_gross,
[data-grid="business-sites-active"] .datagrid_col_wage,
[data-grid="business-sites-active"] .datagrid_col_last_worked,
[data-grid="business-sites-active"] .datagrid_col_budget_amount,
[data-grid="business-sites-active"] .datagrid_col_budget_used,
[data-grid="business-sites-archived"] .datagrid_col_entries,
[data-grid="business-sites-archived"] .datagrid_col_work_gross,
[data-grid="business-sites-archived"] .datagrid_col_wage,
[data-grid="business-sites-archived"] .datagrid_col_last_worked,
[data-grid="business-sites-archived"] .datagrid_col_budget_amount,
[data-grid="business-sites-archived"] .datagrid_col_budget_used {
  font-size: 0.82rem;
  font-variant-numeric: tabular-nums;
}

[data-grid="business-sites-active"] .datagrid_col_work_gross,
[data-grid="business-sites-archived"] .datagrid_col_work_gross {
  color: #3b82f6;
  font-weight: 600;
}

[data-grid="business-sites-active"] .datagrid_col_budget_amount,
[data-grid="business-sites-archived"] .datagrid_col_budget_amount {
  color: #22d3ee;
  font-weight: 600;
}

.business_sites_used_cell {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  width: 100%;
  max-width: 8rem;
}

.business_sites_used_cell--empty {
  color: var(--text-muted, #888);
}

.business_sites_used_meter {
  flex: 1 1 auto;
  width: 100%;
  height: 0.35rem;
  min-width: 3rem;
  border: none;
  border-radius: 999px;
  background: color-mix(in srgb, var(--panel-border, #2a2a2a) 85%, transparent);
  overflow: hidden;
}

.business_sites_used_meter::-webkit-meter-bar {
  background: color-mix(in srgb, var(--panel-border, #2a2a2a) 85%, transparent);
  border: none;
  border-radius: 999px;
  height: 0.35rem;
}

.business_sites_used_meter::-webkit-meter-optimum-value,
.business_sites_used_meter::-webkit-meter-suboptimum-value,
.business_sites_used_meter::-webkit-meter-even-less-good-value {
  background: #2dd4bf;
  border-radius: 999px;
}

.business_sites_used_meter::-moz-meter-bar {
  background: color-mix(in srgb, var(--panel-border, #2a2a2a) 85%, transparent);
  border: none;
  border-radius: 999px;
  height: 0.35rem;
}

.business_sites_used_meter::-moz-meter-optimum::-moz-meter-bar {
  background: #2dd4bf;
}

.business_sites_used_pct {
  flex: 0 0 auto;
  font-size: 0.82rem;
  color: var(--text-muted, #aaa);
}

[data-grid="business-sites-active"] .datagrid_heading_actions,
[data-grid="business-sites-active"] .datagrid_item_actions,
[data-grid="business-sites-archived"] .datagrid_heading_actions,
[data-grid="business-sites-archived"] .datagrid_item_actions {
  text-align: right;
}

[data-grid="business-sites-active"] .datagrid_heading_actions,
[data-grid="business-sites-archived"] .datagrid_heading_actions {
  color: transparent;
  user-select: none;
}

.business_payroll_panels {
  display: grid;
  gap: 0.8rem;
  margin-top: 0.8rem;
}

.business_payroll_section {
  display: grid;
  gap: 0.55rem;
  min-width: 0;
  padding: 0.8rem 0.9rem;
  border: 1px solid var(--panel-border, #2a2a2a);
  border-radius: 8px;
  background: var(--panel-bg, #151515);
}

.business_payroll_section_header {
  padding-bottom: 0.4rem;
  border-bottom: 1px solid color-mix(in srgb, var(--panel-border, #2a2a2a) 72%, transparent);
}

.business_payroll_section_header h3 {
  margin: 0;
  color: var(--color-primary, #4a9eff);
  font-size: 0.92rem;
  line-height: 1.2;
}

.business_payroll_section .help_text {
  margin: 0;
}

.business_audit_panels {
  display: grid;
  gap: 0.8rem;
  margin-top: 0.8rem;
}

.business_audit_summary,
.business_audit_section,
.business_audit_soc_stub {
  display: grid;
  gap: 0.65rem;
  min-width: 0;
}

.business_audit_section {
  padding: 0.8rem 0.9rem;
  border: 1px solid var(--panel-border, #2a2a2a);
  border-radius: 8px;
  background: var(--panel-bg, #151515);
  color: var(--business-page-text, var(--panel-text, #f4f8ff));
}

.business_audit_soc_stub {
  margin-top: 1.5rem;
}

.business_audit_soc_link {
  margin: 0.75rem 0 0;
}

.business_members_grid_shell {
  width: 100%;
}

.business_members_datagrid {
  width: 100%;
  max-width: 100%;
  overflow-x: auto;
}

.business_members_metrics_bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.35rem;
  margin-bottom: 0.65rem;
  padding: 0.38rem 0.5rem;
  border: 1px solid color-mix(in srgb, var(--panel-border, #2a2a2a) 70%, transparent);
  border-radius: 8px;
  background: color-mix(in srgb, var(--panel-bg, #151515) 88%, transparent);
  font-size: 0.82rem;
  letter-spacing: 0;
}

.business_members_metric_chip {
  display: inline-flex;
  align-items: baseline;
  gap: 0.3rem;
  padding: 0.12rem 0.38rem;
  border-radius: 999px;
}

.business_members_metric_label {
  color: var(--business-page-muted, var(--fore-muted, var(--text-muted, #c8d2e2)));
  font-weight: 600;
  text-transform: none;
  letter-spacing: 0;
}

.business_members_metric_value {
  font-weight: 650;
  color: var(--panel-head-fore, inherit);
}

.business_members_security_legend {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  min-width: 0;
  padding: 0.12rem 0.38rem;
  border-radius: 999px;
  color: var(--business-page-muted, var(--fore-muted, var(--text-muted, #c8d2e2)));
}

.business_members_security_legend_text {
  display: inline-flex;
  align-items: baseline;
  gap: 0.35rem;
  min-width: 0;
}

.business_members_security_legend_label {
  color: var(--panel-head-fore, inherit);
  font-weight: 650;
  white-space: nowrap;
}

.business_members_security_legend_help {
  color: var(--business-page-muted, var(--fore-muted, var(--text-muted, #c8d2e2)));
  font-size: 0.82rem;
  white-space: nowrap;
}

.business_members_metric_divider {
  display: none;
}

.business_members_metric_chip_pending,
.business_members_metric_chip_pending_static {
  border: 0;
  background: transparent;
  padding: 0;
  font: inherit;
  color: inherit;
  cursor: default;
}

button.business_members_metric_chip_pending:not(:disabled) {
  cursor: pointer;
  border-radius: 6px;
  padding: 0.1rem 0.35rem;
  margin: -0.1rem -0.35rem;
}

button.business_members_metric_chip_pending:not(:disabled):hover,
button.business_members_metric_chip_pending:not(:disabled):focus-visible {
  background: color-mix(in srgb, var(--color-primary, #33b5ff) 14%, transparent);
  outline: 2px solid color-mix(in srgb, var(--color-primary, #33b5ff) 55%, transparent);
  outline-offset: 1px;
}

button.business_members_metric_chip_pending:disabled {
  cursor: default;
}

.business_members_info_button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.65rem;
  height: 1.65rem;
  min-width: 1.65rem;
  max-width: 1.65rem;
  padding: 0;
  border: 1px solid var(--panel-border, #2a2a2a);
  border-radius: 50%;
  background: var(--button-secondary-bg, transparent);
  color: var(--panel-head-fore, inherit);
  font: inherit;
  font-weight: 800;
  line-height: 1;
  cursor: pointer;
}

.business_members_info_button:hover,
.business_members_info_button:focus-visible {
  background: color-mix(in srgb, var(--color-primary, #33b5ff) 14%, transparent);
  outline: 2px solid color-mix(in srgb, var(--color-primary, #33b5ff) 55%, transparent);
  outline-offset: 2px;
}

.business_members_info_dialog {
  width: min(calc(100vw - 1rem), 78rem);
  max-width: calc(100vw - 1rem);
  max-height: min(calc(100dvh - 1rem), 48rem);
  left: 50%;
  transform: translateX(-50%);
}

.business_members_info_dialog .modal_header .btn_close,
.business_members_info_dialog .modal_header [data-dialog-close] {
  background: transparent;
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
  border-color: transparent;
}

.business_members_info_dialog .modal_header .btn_close:hover,
.business_members_info_dialog .modal_header .btn_close:focus-visible,
.business_members_info_dialog .modal_header [data-dialog-close]:hover,
.business_members_info_dialog .modal_header [data-dialog-close]:focus-visible {
  background: color-mix(in srgb, var(--panel-text, #fff) 10%, transparent);
  color: var(--panel-text, #fff);
  border-color: color-mix(in srgb, var(--panel-text, #fff) 18%, transparent);
}

.business_members_info_dialog_content {
  display: block;
  overflow-y: auto;
  min-height: 0;
  font-family: var(--font-family-base, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif);
  letter-spacing: 0;
}

.business_members_info_dialog_grid {
  display: grid;
  grid-template-columns: minmax(17rem, 0.7fr) minmax(24rem, 1.3fr);
  gap: 0.85rem;
  align-items: start;
}

.business_members_info_panel {
  min-width: 0;
  border: 1px solid color-mix(in srgb, var(--panel-border, #2a2a2a) 72%, transparent);
  border-radius: 8px;
  background: color-mix(in srgb, var(--panel-bg, #151515) 88%, transparent);
  padding: 0.85rem;
}

.business_members_info_panel h3,
.business_members_role_card h4 {
  margin: 0;
  color: var(--panel-head-fore, inherit);
}

.business_members_info_terms {
  display: grid;
  gap: 0.45rem;
  margin: 0.7rem 0 0;
}

.business_members_info_terms > div {
  display: grid;
  grid-template-columns: 6.25rem minmax(0, 1fr);
  gap: 0.65rem;
  align-items: baseline;
}

.business_members_info_terms dt,
.business_members_role_card h4 {
  font-weight: 800;
}

.business_members_info_terms dd,
.business_members_role_card p {
  margin: 0;
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
  line-height: 1.35;
  letter-spacing: 0;
}

.business_members_role_cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));
  gap: 0.55rem;
  margin-top: 0.7rem;
}

.business_members_role_card {
  min-width: 0;
  border: 1px solid color-mix(in srgb, var(--panel-border, #2a2a2a) 62%, transparent);
  border-radius: 8px;
  background: color-mix(in srgb, var(--panel-bg, #151515) 78%, var(--dialog-back, #111) 22%);
  padding: 0.7rem;
}

.business_members_role_card header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.6rem;
  margin-bottom: 0.4rem;
}

.business_members_role_badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: max-content;
  padding: 0.12rem 0.45rem;
  border: 1px solid color-mix(in srgb, var(--color-primary, #33b5ff) 42%, transparent);
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-primary, #33b5ff) 11%, transparent);
  color: var(--panel-head-fore, inherit);
  font-size: 0.82rem;
  font-weight: 750;
  line-height: 1.2;
}

.business_members_role_card p {
  font-size: 0.86rem;
}

.business_members_role_limit {
  margin-top: 0.25rem !important;
  color: color-mix(in srgb, var(--fore-muted, #9aa3b2) 82%, var(--color-warning, #f6a23a) 18%) !important;
}

.business_members_role_matrix {
  display: grid;
  grid-template-columns: 1fr;
  margin-top: 0.7rem;
  border: 1px solid color-mix(in srgb, var(--panel-border, #2a2a2a) 62%, transparent);
  border-radius: 8px;
  overflow: hidden;
  font-size: 0.82rem;
}

.business_members_role_matrix > div {
  display: grid;
  grid-template-columns: 1.3fr 1fr 0.8fr 1fr;
  gap: 0.5rem;
  padding: 0.42rem 0.55rem;
  border-top: 1px solid color-mix(in srgb, var(--panel-border, #2a2a2a) 50%, transparent);
}

.business_members_role_matrix > div:first-child {
  border-top: 0;
}

.business_members_role_matrix_header {
  background: color-mix(in srgb, var(--panel-bg, #151515) 70%, var(--dialog-back, #111) 30%);
  color: var(--panel-head-fore, inherit);
  font-weight: 800;
  text-transform: uppercase;
  font-size: 0.82rem;
}

.business_members_pending_details {
  margin-bottom: 0.75rem;
  border: 1px solid var(--panel-border, #2a2a2a);
  border-radius: 8px;
  background: var(--panel-bg, #151515);
  min-height: 2.7rem;
}

.business_members_pending_details.is-empty {
  visibility: hidden;
  pointer-events: none;
}

.business_members_pending_details > summary {
  list-style: none;
  cursor: pointer;
  padding: 0.65rem 0.85rem;
  font-size: 0.88rem;
  font-weight: 650;
}

.business_members_pending_details > summary::-webkit-details-marker {
  display: none;
}

.business_members_pending_summary {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.business_members_pending_summary::before {
  content: '▸';
  display: inline-block;
  font-size: 0.82rem;
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
  transition: transform 0.15s ease;
}

.business_members_pending_details[open] > .business_members_pending_summary::before {
  transform: rotate(90deg);
}

.business_members_pending_details[open] > :not(summary) {
  padding: 0 0.85rem 0.75rem;
}

.business_members_pending_list {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
}

.business_members_pending_row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.65rem 0.75rem;
  border: 1px solid var(--border, rgba(255, 255, 255, 0.14));
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.02);
}

.business_members_pending_info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  min-width: 0;
  flex: 1;
}

.business_members_pending_type {
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
}

.business_members_pending_email {
  font-size: 0.86rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.business_members_pending_meta {
  font-size: 0.82rem;
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
}

.business_members_pending_actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  flex-shrink: 0;
}

.business_members_pending_actions .btn {
  padding: 0.35rem 0.65rem;
  font-size: 0.82rem;
  white-space: nowrap;
}

.business_members_pending_empty {
  margin: 0;
  padding: 0.5rem 0;
  font-size: 0.82rem;
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
}

.business_members_bulk_toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem 1.25rem;
  margin-bottom: 0.85rem;
  padding: 0.75rem 0.9rem;
  border: 1px solid var(--panel-border, #2a2a2a);
  border-radius: 10px;
  background: var(--panel-bg, #151515);
}

.business_members_bulk_toolbar_compact {
  flex-wrap: nowrap;
  gap: 0.45rem 0.55rem;
  margin-bottom: 0;
  padding: 0;
  border: none;
  border-radius: 0;
  background: transparent;
  min-height: 2rem;
  align-items: center;
}

.business_members_bulk_toolbar_compact .btn_compact {
  padding: 0.28rem 0.55rem;
  font-size: 0.82rem;
  min-height: 1.75rem;
}

.business_members_selection_badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0;
  border: 0;
  border-radius: 0;
  background: transparent;
  font-size: 0.82rem;
  font-weight: 700;
  white-space: nowrap;
}

.business_members_selection_badge_icon {
  font-size: 0.82rem;
  opacity: 0.8;
}

.business_members_bulk_divider {
  display: none;
}

.business_members_row_select {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  min-width: 40px;
  padding: 0.35rem 0.25rem;
}

.business_members_row_checkbox,
.business_members_select_all_checkbox {
  margin: 0;
  width: 1rem;
  height: 1rem;
  cursor: pointer;
}

.business_members_header_select {
  display: flex;
  align-items: center;
  justify-content: center;
}

.business_member_details_cell {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
  white-space: normal;
}

.business_member_details_cell_with_icon {
  display: grid;
  grid-template-columns: 1.25rem minmax(0, 1fr);
  align-items: center;
  column-gap: 0.45rem;
}

.business_member_details_stack {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
}

.business_member_details_name {
  font-weight: 650;
  font-size: 0.86rem;
  line-height: 1.25;
  overflow: visible;
  text-overflow: clip;
  white-space: normal;
  overflow-wrap: anywhere;
}

.business_member_details_email {
  font-size: 0.82rem;
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
  overflow: visible;
  text-overflow: clip;
  white-space: normal;
  overflow-wrap: anywhere;
}

.business_member_details_meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.15rem;
  font-size: 0.82rem;
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
}

.business_member_data_access_icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  width: 1.2rem;
  height: 1.2rem;
  color: var(--color-warning, #f5c451);
  vertical-align: middle;
}

.business_member_data_access_icon svg {
  display: block;
  width: 100%;
  height: 100%;
}

.business_member_data_access_icon.is-active {
  width: 1.35rem;
  height: 1.35rem;
  color: var(--business-member-verified-color, #22c55e);
  filter: drop-shadow(0 0 0.25rem color-mix(in srgb, var(--business-member-verified-color, #22c55e) 55%, transparent));
}

.business_member_data_access_icon.is-waiting,
.business_member_data_access_icon.is-setup {
  color: var(--color-warning, #f5c451);
}

.business_member_data_access_icon.is-revoked,
.business_member_data_access_icon.is-unavailable {
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
}

.business_member_data_access_icon_shield,
.business_member_data_access_icon_wrench {
  fill: none;
}

.business_member_data_access_icon_verified_shield {
  fill: currentColor;
  stroke: color-mix(in srgb, currentColor 82%, white);
  stroke-width: 1.35;
}

.business_member_data_access_icon_verified_check {
  fill: none;
  stroke: var(--panel-bg, #101418);
  stroke-width: 2.6;
}

.business_members_security_legend_icon {
  width: 1rem;
  height: 1rem;
}

.business_member_details_item {
  white-space: normal;
  overflow: visible;
  min-width: 0;
}

.business_member_hours_item,
.business_member_earnings_item {
  display: grid;
  justify-items: end;
  align-items: center;
  white-space: normal;
  overflow: visible;
  min-width: 0;
  text-align: right;
}

.business_member_joined_item {
  white-space: nowrap;
  min-width: 0;
}

.business_member_hours_cell,
.business_member_earnings_cell {
  display: grid;
  justify-items: end;
  justify-self: end;
  gap: 0.1rem;
  line-height: 1.25;
  text-align: right;
  width: max-content;
  max-width: 100%;
}

[data-grid="business-members"] .datagrid_item_actions {
  display: grid;
  justify-items: end;
  align-items: center;
  overflow: visible;
}

[data-grid="business-members"] .business_member_row_menu {
  justify-self: end;
}

.business_member_hours_primary,
.business_member_earnings_primary {
  font-weight: 650;
}

.business_member_hours_subline,
.business_member_earnings_trend {
  font-size: 0.82rem;
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
}

.business_member_row_menu {
  position: relative;
  justify-content: center;
  z-index: 40;
}

.business_member_row_menu_toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  padding: 0;
  border: 1px solid var(--btn-border);
  border-radius: 6px;
  background: var(--button-bg, #fff);
  color: inherit;
  cursor: pointer;
}

.business_member_row_menu_toggle:hover,
.business_member_row_menu_toggle:focus-visible {
  border-color: var(--color-primary, #4a9eff);
}

.business_member_row_menu_icon {
  font-size: 1rem;
  line-height: 1;
}

.business_member_row_menu_panel {
  position: absolute;
  top: calc(100% + 4px);
  right: 0;
  z-index: 80;
  display: flex;
  flex-direction: column;
  min-width: 10rem;
  padding: 0.35rem 0;
  border: 1px solid var(--business-page-border, var(--btn-border));
  border-radius: 8px;
  background: var(--business-page-elevated-bg, #171d24);
  color: var(--business-page-text, #f4f8ff);
  box-shadow: var(--depth-dialog-shadow, 0 14px 34px rgba(0, 0, 0, 0.52));
  isolation: isolate;
}

[data-grid="business-members"] .datagrid_row.business_member_row_menu_open {
  position: relative;
  z-index: 70;
}

.business_member_row_menu_panel[hidden] {
  display: none !important;
}

.business_member_row_menu_item {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  align-items: center;
  column-gap: 0.75rem;
  width: 100%;
  padding: 0.45rem 0.75rem;
  border: none;
  background: none;
  color: inherit;
  font-size: 0.82rem;
  text-align: left;
  cursor: pointer;
}

.business_member_row_menu_item:hover:not(:disabled),
.business_member_row_menu_item:focus-visible:not(:disabled) {
  background: rgba(0, 188, 212, 0.12);
}

.business_member_row_menu_item:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.business_member_row_menu_item_danger {
  color: var(--color-danger, #e05252);
}

.business_member_row_menu_item_has_submenu {
  grid-template-columns: minmax(0, 1fr) auto;
}

.business_member_row_menu_item_has_submenu::after {
  content: "›";
  grid-column: 2;
  align-self: center;
  justify-self: end;
  line-height: 1;
  color: var(--business-page-muted, var(--fore-muted, var(--text-muted, #c8d2e2)));
}

.business_member_row_submenu {
  position: absolute;
  top: 0;
  right: calc(100% + 0.35rem);
  z-index: 90;
  display: grid;
  gap: 0.2rem;
  min-width: 14rem;
  max-width: min(20rem, calc(100vw - 2rem));
  max-height: min(18rem, calc(100dvh - 4rem));
  overflow-y: auto;
  overscroll-behavior: contain;
  padding: 0.35rem;
  border: 1px solid var(--business-page-border, var(--btn-border));
  border-radius: 8px;
  background: var(--business-page-elevated-bg, #171d24);
  color: var(--business-page-text, #f4f8ff);
  box-shadow: var(--depth-dialog-shadow, 0 14px 34px rgba(0, 0, 0, 0.55));
  isolation: isolate;
}

.business_member_row_submenu[hidden] {
  display: none !important;
}

.business_member_row_submenu_item {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 0.5rem;
  align-items: center;
  width: 100%;
  padding: 0.45rem 0.55rem;
  border: 0;
  border-radius: 0.35rem;
  background: transparent;
  color: var(--business-page-text, inherit);
  font-size: 0.82rem;
  text-align: left;
  text-decoration: none;
  cursor: pointer;
  box-sizing: border-box;
}

.business_member_row_submenu_item:hover,
.business_member_row_submenu_item:focus-visible {
  background: color-mix(in srgb, var(--color-primary, #33b5ff) 14%, transparent);
}

.business_member_row_submenu_meta,
.business_member_row_submenu_note {
  color: var(--business-page-muted, var(--fore-muted, var(--text-muted, #c8d2e2)));
  font-size: 0.82rem;
}

.business_member_row_submenu_note {
  margin: 0;
  padding: 0.45rem 0.55rem;
}

.business_member_row_submenu_link {
  border-top: 1px solid color-mix(in srgb, var(--panel-border, currentColor) 30%, transparent);
  margin-top: 0.2rem;
  color: #ffffff;
  background: var(--business-page-primary-bg, #0b63ce);
  font-weight: 650;
}

.business_member_row_submenu_link:hover,
.business_member_row_submenu_link:focus-visible {
  background: var(--business-page-primary-bg-hover, #0d70e8);
  color: #ffffff;
}

[data-grid="business-members"] .datagrid_header_row {
  position: sticky;
  top: 0;
  z-index: 1;
}

[data-grid="business-members"] .datagrid_col_select {
  width: 40px;
  min-width: 40px;
  max-width: 40px;
}

[data-grid="business-members"] .datagrid_header_content,
[data-grid="business-members"] .datagrid_row_content {
  grid-template-columns:
    40px
    minmax(12rem, 2fr)
    minmax(4.5rem, 0.75fr)
    minmax(5.5rem, 0.85fr)
    minmax(4.5rem, 0.7fr)
    minmax(4.5rem, 0.7fr)
    2.5rem;
}

[data-grid="business-members"] .datagrid_toolbar_search_pagination {
  display: grid;
  grid-template-columns: minmax(14rem, 24rem) minmax(0, 1fr) auto;
  grid-template-areas:
    "search filters filters"
    ". page pager";
  align-items: center;
  gap: 0.45rem 0.65rem;
  padding-inline: 0.65rem;
  margin-bottom: 0.55rem;
  box-sizing: border-box;
  letter-spacing: 0;
}

[data-grid="business-members"] .datagrid_toolbar_start {
  grid-area: search;
  min-width: 0;
  max-width: none;
  padding: 2px;
  min-height: 2rem;
  visibility: hidden;
  pointer-events: none;
}

.business_members_bulk_toolbar_compact.is-active {
  visibility: visible;
  pointer-events: auto;
}

[data-grid="business-members"] .datagrid_toolbar_start .datagrid_search {
  width: 100%;
  flex: none;
  box-sizing: border-box;
}

.business_members_bulk_toolbar_mount {
  display: contents;
}

[data-grid="business-members"] .business_members_toolbar_filters {
  grid-area: filters;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.45rem;
  min-height: 2rem;
  min-width: 0;
}

[data-grid="business-members"] .business_members_toolbar_filters[hidden] {
  display: none;
}

[data-grid="business-members"] .business_members_toolbar_filters.is-inactive {
  visibility: hidden;
  pointer-events: none;
}

[data-grid="business-members"] .datagrid_column_menu {
  flex: 0 0 auto;
}

[data-grid="business-members"] .datagrid_column_menu_toggle {
  letter-spacing: 0;
}

[data-grid="business-members"] .datagrid_toolbar_center {
  grid-area: page;
  justify-self: end;
  text-align: right;
}

[data-grid="business-members"] .datagrid_toolbar_end {
  grid-area: pager;
  justify-self: end;
}

[data-grid="business-members"] .business_members_toolbar_bulk {
  grid-area: filters;
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 0.45rem 0.55rem;
  min-height: 2rem;
  min-width: 0;
}

.business_members_report_control {
  position: relative;
  display: inline-flex;
  align-items: center;
  flex: 0 0 auto;
}

.business_members_bulk_group_control {
  position: relative;
  display: inline-flex;
  align-items: center;
  flex: 0 0 auto;
}

.business_members_bulk_group_menu {
  top: calc(100% + 0.35rem);
  right: auto;
  left: 0;
}

.business_members_bulk_group_menu .business_member_row_submenu_item[aria-disabled="true"] {
  opacity: 0.55;
  pointer-events: none;
}

.business_members_report_panel {
  position: fixed;
  z-index: 1000;
  inset: 0.75rem;
  transform: none;
  display: none;
  grid-template-columns: minmax(20rem, 1fr) minmax(18rem, 0.8fr);
  grid-auto-rows: min-content;
  gap: 0.8rem 1rem;
  inline-size: auto;
  block-size: auto;
  max-inline-size: none;
  max-block-size: none;
  overflow: hidden;
  padding: 0.9rem;
  border: 1px solid var(--panel-border, rgba(255, 255, 255, 0.18));
  border-radius: 0.45rem;
  background: var(--panel-bg, #1f252d);
  color: var(--color-text);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.38);
}

.business_members_report_panel input,
.business_members_report_panel select,
.business_members_report_panel button {
  letter-spacing: 0 !important;
}

.business_members_report_panel[open] {
  display: grid;
}

.business_members_report_panel::backdrop {
  background: rgba(0, 0, 0, 0.52);
}

.business_members_report_dialog_header {
  display: flex;
  grid-column: 1 / -1;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  min-width: 0;
}

.business_members_report_dialog_header h4 {
  margin: 0;
  font-size: 1rem;
  line-height: 1.25;
}

.business_members_report_close {
  flex: 0 0 auto;
  opacity: 0.82;
}

.business_members_report_selected {
  display: grid;
  grid-column: 1;
  grid-row: 2 / span 8;
  align-content: start;
  gap: 0.4rem;
  min-width: 0;
  min-height: 0;
  overflow: auto;
  padding-right: 0.2rem;
}

.business_members_report_settings {
  display: grid;
  grid-column: 2;
  align-content: start;
  gap: 0.75rem;
  min-width: 0;
}

.business_members_report_section_title {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.75rem;
  margin: 0;
  color: var(--panel-head-fore, inherit);
  font-size: 0.86rem;
  font-weight: 700;
  letter-spacing: 0;
  line-height: 1.25;
}

.business_members_report_section_count {
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
  font-size: 0.82rem;
  font-weight: 600;
}

.business_members_report_selected label {
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
  font-size: 0.82rem;
  font-weight: 600;
  letter-spacing: 0;
  text-transform: none;
}

.business_members_report_member_pills,
.business_members_report_member_add_results {
  display: grid;
  gap: 0.35rem;
  align-content: flex-start;
  min-height: 2.25rem;
  max-height: min(34dvh, 18rem);
  overflow: auto;
  padding: 0.35rem;
  border: 1px solid color-mix(in srgb, var(--panel-border, rgba(255, 255, 255, 0.18)) 70%, transparent);
  border-radius: 0.4rem;
  background: color-mix(in srgb, var(--panel-bg, #1f252d) 80%, #000 20%);
}

.business_members_report_member_pill {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 0.35rem;
  width: 100%;
  max-width: none;
  min-height: 2rem;
  padding: 0.28rem 0.4rem;
  border: 1px solid color-mix(in srgb, var(--color-primary, #33b5ff) 35%, var(--panel-border, rgba(255, 255, 255, 0.18)));
  border-radius: 0.4rem;
  background: color-mix(in srgb, var(--color-primary, #33b5ff) 12%, transparent);
  font-size: 0.82rem;
  line-height: 1.2;
  box-sizing: border-box;
}

.business_members_report_member_pill_name {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.business_members_report_member_remove,
.business_members_report_member_add {
  display: inline-grid;
  align-items: center;
  justify-content: center;
  min-width: 1.15rem;
  height: 1.15rem;
  padding: 0 0.3rem;
  border: 0;
  border-radius: 999px;
  background: color-mix(in srgb, var(--panel-text, #fff) 10%, transparent);
  color: inherit;
  cursor: pointer;
}

.business_members_report_member_empty {
  margin: 0;
  color: var(--fore-muted, var(--text-muted, #b8c0cc));
  font-size: 0.82rem;
}

.business_members_report_field {
  display: grid;
  gap: 0.25rem;
}

.business_members_report_field label {
  font-size: 0.82rem;
  font-weight: 600;
  letter-spacing: 0;
  text-transform: none;
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
}

.business_members_report_field select,
.business_members_report_field input {
  width: 100%;
  min-height: 2.35rem;
  min-width: 0;
  letter-spacing: 0;
  box-sizing: border-box;
}

.business_members_report_field_help {
  margin: 0;
  color: var(--fore-muted, var(--text-muted, #b8c0cc));
  font-size: 0.82rem;
  line-height: 1.35;
}

.business_members_report_status {
  min-height: 1rem;
  margin: 0;
  font-size: 0.82rem;
  line-height: 1.35;
  color: var(--fore-muted, var(--text-muted, #b8c0cc));
}

.business_members_report_privacy_notice {
  margin: 0;
  padding: 0.55rem 0.65rem;
  border: 1px solid color-mix(in srgb, var(--warning-foreground, #f1c76b) 40%, var(--panel-border, rgba(255, 255, 255, 0.18)));
  border-radius: 0.4rem;
  background: color-mix(in srgb, var(--warning-foreground, #f1c76b) 10%, transparent);
  font-size: 0.82rem;
  line-height: 1.35;
  color: var(--warning-foreground, #f1c76b);
}

.business_members_report_summary {
  display: grid;
  gap: 0.55rem;
  align-content: start;
  min-height: 0;
  overflow: auto;
  padding-top: 0.25rem;
  border-top: 1px solid var(--panel-border, rgba(255, 255, 255, 0.14));
}

.business_members_report_summary[hidden] {
  display: none;
}

.business_members_report_summary h4 {
  margin: 0;
  font-size: 0.82rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.business_members_report_summary dl {
  display: grid;
  gap: 0.25rem;
  margin: 0;
}

.business_members_report_summary dl > div {
  display: grid;
  grid-template-columns: minmax(5rem, 0.6fr) minmax(0, 1fr);
  gap: 0.5rem;
  align-items: baseline;
}

.business_members_report_summary dt,
.business_members_report_summary dd {
  margin: 0;
  font-size: 0.82rem;
  line-height: 1.35;
}

.business_members_report_summary dt {
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
  font-weight: 700;
}

.business_members_report_summary dd {
  min-width: 0;
  color: var(--color-text);
  overflow-wrap: anywhere;
}

.business_members_report_summary_actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.business_members_group_choice {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  justify-items: start;
  gap: 0.5rem;
}

.business_members_group_choice_meta {
  color: var(--fore-muted, var(--text-muted, #9aa3b2));
  font-size: 0.82rem;
}

.business_groups_panel {
  display: grid;
  gap: 1rem;
  width: 100%;
  max-width: none;
  min-width: 0;
  color: var(--business-page-text, var(--panel-text, #f4f8ff));
}

.business_groups_panel .help_text,
.business_groups_panel #business_groups_status_message {
  color: var(--business-page-muted, #c8d2e2);
  letter-spacing: 0;
}

.business_groups_form {
  display: grid;
  grid-template-columns: minmax(12rem, 1fr) minmax(12rem, 1fr) minmax(8rem, 0.5fr) auto;
  gap: 0.75rem;
  align-items: end;
  padding: 0.75rem;
  border: 1px solid color-mix(in srgb, var(--panel-border, rgba(255, 255, 255, 0.18)) 70%, transparent);
  border-radius: 0.45rem;
  background: color-mix(in srgb, var(--panel-bg, #1f252d) 82%, #000 18%);
}

.business_groups_form[hidden] {
  display: none;
}

.business_groups_field {
  display: grid;
  gap: 0.25rem;
}

.business_groups_field span {
  color: var(--business-page-muted-strong, #dce6f5);
  font-size: 0.82rem;
  font-weight: 600;
  letter-spacing: 0;
}

.business_groups_field input,
.business_groups_field select {
  min-height: 2.25rem;
  letter-spacing: 0;
  border-color: var(--business-page-border, var(--button-border));
  background-color: color-mix(in srgb, var(--panel-bg, #151b24) 78%, #000000 22%);
  color: var(--business-page-text, #f4f8ff);
}

.business_groups_field input::placeholder {
  color: var(--business-page-muted, #c8d2e2);
}

.business_groups_form_actions {
  display: flex;
  gap: 0.4rem;
  align-items: center;
}

.business_groups_list {
  display: grid;
  gap: 0.55rem;
}

.business_groups_datagrid {
  width: 100%;
  max-width: none;
  min-width: 0;
  min-height: 12rem;
}

.business_status_legend_row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem 1rem;
  margin: 0 0 0.85rem;
}

.business_status_legend_row > .tabs {
  flex: 0 0 auto;
  width: auto;
  margin: 0;
}

.business_status_action_group {
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  gap: 0.4rem;
  padding: 0.35rem;
  border: var(--border-size) solid var(--panel-border);
  border-radius: var(--border-radius);
  background-color: var(--panel-bg);
  box-shadow: var(--depth-control-shadow, 0 0.25rem 0.25rem rgba(0, 0, 0, 0.75));
}

.business_status_action_group > .tabs {
  width: auto;
  margin: 0;
  padding: 0;
  border: 0;
  background: transparent;
  box-shadow: none;
  backdrop-filter: none;
  overflow: visible;
}

.business_status_action_group .tab,
.business_status_action_group .business_status_add_button {
  min-height: 2.25rem;
  padding: 0.45rem 0.7rem;
  line-height: 1.2;
}

.business_status_action_group .tab {
  border: 1px solid var(--business-page-border, var(--button-border));
  border-radius: 0.4rem;
  background: var(--business-page-secondary-bg, color-mix(in srgb, var(--panel-bg, #151b24) 78%, #ffffff 8%));
  color: var(--business-page-secondary-text, #f4f8ff);
}

.business_status_action_group .tab[aria-selected="true"],
.business_status_action_group .tab.active {
  border-color: color-mix(in srgb, var(--business-page-primary-bg, #0b63ce) 44%, var(--business-page-border, var(--panel-border, #6b7280)) 56%);
  background: color-mix(in srgb, var(--business-page-primary-bg, #0b63ce) 20%, var(--business-page-secondary-bg, var(--panel-bg, #151b24)) 80%);
  color: #ffffff;
  box-shadow: inset 0 -2px 0 color-mix(in srgb, var(--business-page-primary-bg-hover, #0d70e8) 56%, transparent);
}

.business_status_action_group .tab:hover,
.business_status_action_group .tab:focus-visible {
  border-color: color-mix(in srgb, var(--business-page-primary-bg-hover, #0d70e8) 60%, #ffffff 40%);
  color: #ffffff;
}

.business_status_add_button {
  padding-inline: 0.8rem;
  color: var(--business-page-primary-text, #ffffff);
  white-space: nowrap;
}

.business_status_add_button:hover,
.business_status_add_button:focus-visible {
  color: var(--business-page-primary-text, #ffffff);
}

.business_status_legend_row .business_groups_type_legend,
.business_status_legend_row .business_sites_ownership_legend {
  flex: 1 1 16rem;
  justify-content: flex-end;
}

.business_groups_type_legend {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem 0.85rem;
  margin: 0;
  color: var(--business-page-muted, var(--text-muted, #c8d2e2));
  font-size: var(--font-sm, 0.875rem);
  line-height: 1.35;
}

.business_groups_type_legend_item {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  color: var(--business-page-text, #f4f8ff);
  font-weight: 600;
}

.business_groups_type_legend_hint {
  flex: 1 1 12rem;
  color: var(--business-page-muted, var(--text-muted, #c8d2e2));
}

.business_groups_type_symbol {
  display: inline-block;
  flex: 0 0 auto;
  width: 0.55rem;
  height: 0.55rem;
  background: #2dd4bf;
  border: 1px solid color-mix(in srgb, #2dd4bf 70%, #0f766e);
}

.business_groups_type_symbol--manual {
  border-radius: 50%;
}

.business_groups_type_symbol--archived {
  border-radius: 1px;
  background: transparent;
}

.business_groups_type_symbol--smart {
  border-radius: 1px;
  transform: rotate(45deg);
}

[data-grid="business-groups-active"],
[data-grid="business-groups-archived"] {
  display: grid;
  grid-template-columns: minmax(14rem, 1fr) auto;
  align-items: start;
  gap: 0 0.75rem;
  width: 100%;
  max-width: none;
  --grid-template-columns:
    minmax(14rem, 3fr)
    minmax(5rem, 0.7fr)
    minmax(5rem, 0.7fr)
    minmax(5rem, 0.8fr)
    minmax(6rem, 1fr)
    minmax(10rem, 1fr)
    minmax(5.5rem, max-content);
  font-size: 0.9em;
}

.business_groups_mobile_cards .datagrid_controls,
.business_groups_mobile_cards .datagrid_toolbar_search_pagination,
.business_groups_mobile_cards .datagrid_column_strip {
  margin-bottom: 0.75rem;
}

.business_groups_mobile_cards .datagrid_controls,
.business_groups_mobile_cards .datagrid_toolbar_search_pagination {
  grid-column: 1;
  min-width: 0;
}

.business_groups_mobile_cards .datagrid_column_strip {
  grid-column: 2;
  align-self: start;
  white-space: nowrap;
}

.business_groups_mobile_cards .datagrid_table,
.business_groups_mobile_cards .datagrid_pagination_bottom {
  grid-column: 1 / -1;
}

[data-grid="business-groups-active"] .datagrid_header_content,
[data-grid="business-groups-active"] .datagrid_row_content,
[data-grid="business-groups-archived"] .datagrid_header_content,
[data-grid="business-groups-archived"] .datagrid_row_content {
  grid-template-columns:
    minmax(14rem, 3fr)
    minmax(5rem, 0.7fr)
    minmax(5rem, 0.7fr)
    minmax(5rem, 0.8fr)
    minmax(6rem, 1fr)
    minmax(10rem, 1fr)
    minmax(5.5rem, max-content);
}

[data-grid="business-groups-active"] .datagrid_item,
[data-grid="business-groups-active"] .datagrid_heading,
[data-grid="business-groups-archived"] .datagrid_item,
[data-grid="business-groups-archived"] .datagrid_heading {
  min-width: 0;
  padding: 0.4rem 0.5rem;
}

.business_groups_mobile_cards .datagrid_row {
  cursor: pointer;
}

.business_groups_mobile_cards .datagrid_body .datagrid_row:not(:last-child) > .datagrid_row_content {
  border-bottom: var(--border-size, 1px) solid rgba(255, 255, 255, 0.06);
  border-bottom-color: color-mix(in srgb, var(--panel-border, currentColor) 28%, transparent);
}

.business_groups_mobile_cards .datagrid_row:hover > .datagrid_row_content {
  background: rgba(0, 188, 212, 0.12);
}

.business_groups_name_cell {
  display: inline-flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.25rem;
  max-width: 100%;
}

.business_groups_name_text {
  min-width: 0;
  font-weight: 700;
  color: var(--business-page-text, #f4f8ff);
}

.business_groups_type_tag {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  color: var(--business-page-muted-strong, #dce6f5);
  background: color-mix(in srgb, var(--business-page-elevated-bg, #171d24) 70%, #2dd4bf 12%);
  font-size: 0.82rem;
  font-weight: 600;
  line-height: 1.2;
}

.business_groups_type_tag--archived {
  color: var(--business-page-muted, #c8d2e2);
  background: color-mix(in srgb, var(--business-page-elevated-bg, #171d24) 78%, #ffffff 8%);
}

.business_groups_type_tag--smart {
  color: #f0fdfa;
  background: color-mix(in srgb, var(--business-page-elevated-bg, #171d24) 70%, #2dd4bf 18%);
}

.business_groups_description_text {
  color: var(--business-page-muted, var(--fore-muted, var(--text-muted, #c8d2e2)));
  font-size: 0.82rem;
  line-height: 1.3;
  white-space: normal;
  overflow-wrap: anywhere;
}

.business_groups_mobile_cards .datagrid_col_member_count,
.business_groups_mobile_cards .datagrid_col_site_count,
.business_groups_mobile_cards .datagrid_col_hours,
.business_groups_mobile_cards .datagrid_col_work_gross {
  font-size: 0.82rem;
  font-variant-numeric: tabular-nums;
}

.business_groups_mobile_cards .datagrid_col_work_gross {
  color: #3b82f6;
  font-weight: 600;
}

.business_groups_mobile_cards .datagrid_col_updated_at {
  white-space: nowrap;
  overflow: visible;
  text-overflow: clip;
}

.business_groups_mobile_cards .datagrid_heading_actions,
.business_groups_mobile_cards .datagrid_item_actions {
  text-align: right;
}

.business_groups_mobile_cards .datagrid_action {
  padding: 0.35rem 0.55rem;
  border: 1px solid var(--business-page-border, var(--button-border));
  border-radius: 0.35rem;
  background: var(--business-page-secondary-bg, color-mix(in srgb, var(--panel-bg, #151b24) 78%, #ffffff 8%));
  color: var(--business-page-secondary-text, #f4f8ff);
  font-size: 0.82rem;
  line-height: 1.1;
}

.business_groups_mobile_cards .datagrid_action:hover,
.business_groups_mobile_cards .datagrid_action:focus-visible {
  background: var(--business-page-secondary-bg-hover, color-mix(in srgb, var(--panel-bg, #151b24) 68%, #ffffff 14%));
  color: #ffffff;
}

.business_groups_mobile_cards .datagrid_heading_actions {
  color: transparent;
  user-select: none;
}

.business_group_editor_dialog {
  width: min(42rem, calc(100vw - 2rem));
  max-width: min(42rem, calc(100vw - 2rem));
  max-height: calc(100dvh - 2rem);
  border: 1px solid var(--business-page-border, var(--panel-border, #2a2a2a));
  border-radius: 0.5rem;
  background: var(--business-page-elevated-bg, var(--panel-bg, #171d24));
  color: var(--business-page-text, var(--panel-text, #f4f8ff));
  overflow: hidden;
}

.business_group_editor_dialog[open] {
  display: block;
}

.business_group_editor_dialog::backdrop {
  background: rgba(0, 0, 0, 0.72);
  backdrop-filter: blur(2px);
}

.business_group_editor_form {
  display: grid;
  grid-template-rows: auto auto auto;
  min-width: 0;
}

.business_group_editor_dialog .modal_header {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  min-height: 3.75rem;
  margin: 0;
  padding: 0.75rem 1rem;
}

.business_group_editor_dialog .modal_title {
  justify-self: start;
  margin: 0;
  padding: 0;
  text-align: left !important;
}

.business_group_editor_content {
  display: block;
  padding: 0;
  overflow: visible;
}

.business_group_editor_col {
  display: grid;
  grid-auto-rows: max-content;
  align-content: start;
  gap: 1rem;
  padding: 1rem;
}

.business_group_editor_col_main {
  border-right: 0;
}

.business_group_editor_col_heading {
  margin: 0 0 0.35rem;
  padding-bottom: 0.55rem;
  border-bottom: 1px solid var(--business-page-border, var(--panel-border, #2a2a2a));
  color: var(--business-page-muted, var(--text-muted, #c8d2e2));
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0;
  text-transform: uppercase;
}

.business_group_editor_dialog .modal_footer {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  align-items: center;
  justify-items: center;
  gap: 0.6rem;
  margin: 0;
  padding: 0.8rem 1rem 1rem;
}

.business_group_editor_dialog .business_groups_form_actions {
  justify-content: center;
  gap: 0.75rem;
  width: auto;
}

.business_group_editor_dialog textarea {
  min-height: 6rem;
  resize: vertical;
}

.business_group_confirm_content {
  padding: 1rem;
}

.business_group_confirm_content p {
  margin: 0;
  color: var(--business-page-text, var(--panel-text, #f4f8ff));
  font-size: 0.95rem;
  line-height: 1.45;
  letter-spacing: 0;
}

.business_group_confirm_dialog .modal_footer {
  grid-template-columns: minmax(0, 1fr);
  justify-items: center;
}

.business_group_confirm_dialog .modal_content {
  border-top: 1px solid var(--business-page-border, var(--panel-border, #2a2a2a));
  border-bottom: 1px solid var(--business-page-border, var(--panel-border, #2a2a2a));
}

.business_group_modal_open .signal_panel_close {
  display: none !important;
}

.business_groups_meta,
.business_groups_empty {
  color: var(--business-page-muted, var(--fore-muted, var(--text-muted, #c8d2e2)));
  font-size: 0.82rem;
  line-height: 1.35;
  letter-spacing: 0;
}

[data-grid="business-members"] .datagrid_toolbar_center {
  flex: 0 1 auto;
}

[data-grid="business-members"] .datagrid_row:hover > .datagrid_row_content {
  background: rgba(0, 188, 212, 0.12);
}

[data-grid="business-members"] .datagrid_body .datagrid_row:not(:last-child) > .datagrid_row_content {
  border-bottom: var(--border-size, 1px) solid rgba(255, 255, 255, 0.06);
  border-bottom-color: color-mix(in srgb, var(--panel-border, currentColor) 28%, transparent);
}

[data-grid="business-members"] .datagrid_row:hover .business_members_row_select {
  background: rgba(0, 188, 212, 0.08);
}

[data-grid="business-members"] .datagrid_heading,
[data-grid="business-members"] .datagrid_item {
  padding-left: 5px;
  padding-right: 5px;
  min-width: 0;
}

#business-workspace.business_members {
  gap: clamp(1.25rem, 2.5vw, 2rem);
}

.business_reports_panel_shell {
  width: 100%;
}

.business_reports_panel_shell .earnings_team_panel {
  width: 100%;
}

#business-workspace.business_reports {
  gap: clamp(1.25rem, 2.5vw, 2rem);
}

#businesses_editor_inline_mount {
  padding: 0;
}

#businesses_editor_inline_mount #businesses_editor_form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.businesses_exec_summary_members_link {
  margin: 0.75rem 0 0;
  grid-column: 1 / -1;
}

.businesses_hub_panel .businesses_hub_callout {
  margin: 0 0 1rem;
}

.businesses_hub_panel .businesses_request_panel {
  margin-top: 0;
}

.businesses_payroll_panel .businesses_section_header {
  margin-bottom: 0.5rem;
}

.business_sites_ownership_legend {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem 0.85rem;
  margin: 0;
}

.business_sites_ownership_legend_item {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: var(--font-sm, 0.875rem);
}

.business_sites_ownership_legend_hint {
  flex: 1 1 12rem;
  color: var(--text-muted, #888);
  font-size: var(--font-sm, 0.875rem);
}

.businesses_site_status_badge {
  display: inline-block;
  padding: 0.15rem 0.55rem;
  border-radius: 12px;
  font-size: 0.82rem;
  font-weight: 600;
  background: rgba(158, 158, 158, 0.15);
  color: var(--text-muted, #888);
}

.dialog_fullscreen {
  width: min(calc(100vw - 1rem), 100vw);
  max-width: calc(100vw - 1rem);
  height: min(calc(100dvh - 1rem), 100dvh);
  max-height: calc(100dvh - 1rem);
  left: 50%;
  transform: translateX(-50%);
}

.businesses_member_reports_dialog_content {
  flex-direction: column;
  gap: 0;
  padding: var(--pad-md);
  overflow-y: auto;
  min-height: 0;
}

.businesses_member_reports_dialog_body {
  width: 100%;
}

.businesses_member_reports_dialog_body .earnings_member_reports_view {
  width: 100%;
}

.businesses_member_reports_dialog_body .member_reports_year_tabs {
  margin-bottom: var(--pad-md);
}

.businesses_member_row_clickable {
  cursor: pointer;
}

.businesses_member_row_clickable:focus-visible {
  outline: 2px solid var(--focus, var(--color-focus-ring, #0096d6));
  outline-offset: -2px;
}

.businesses_member_revoke_dialog_message {
  margin: 0;
  font-size: 1.05rem;
}

.business_details_profile_panel .businesses_details_columns {
  margin-bottom: 0.65rem;
}

.business_details_notes_row {
  grid-column: 1 / -1;
  margin-top: 0.6rem;
  width: 100%;
  max-width: none;
  box-sizing: border-box;
  grid-template-columns: 1fr;
}

.business_details_notes_row label {
  grid-column: 1 / -1;
  text-align: left;
}

.business_details_notes_row textarea {
  grid-column: 1 / -1;
  width: 100%;
  box-sizing: border-box;
}

.business_details_actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.45rem;
  margin-top: 1rem;
  padding-top: 0.85rem;
  border-top: 1px solid color-mix(in srgb, var(--panel-border, #2a2a2a) 76%, transparent);
}

.business_details_actions .help_text {
  margin: 0;
  text-align: right;
}

@media (min-width: 1280px) {
  #business-workspace.business_details .business_details_profile_panel .businesses_details_column .businesses_field_grid {
    max-width: min(56rem, 100%);
  }
}

/* ==========================================================================
   Business workspace mobile layouts (<= 768px)
   Full-width cards, stacked toolbar, compact stats grid. Desktop datagrid
   rules above are untouched above 768px.
   ========================================================================== */
@media (max-width: 768px) {
  #business-workspace.business_payroll {
    gap: 0.5rem;
  }

  #business-workspace.business_payroll .business_payroll_panels {
    gap: 0.55rem;
    margin-top: 0.45rem;
  }

  #business-workspace.business_payroll .business_payroll_section {
    gap: 0.45rem;
    padding: 0.65rem 0.75rem;
    border-radius: 6px;
  }

  #business-workspace.business_payroll .business_payroll_section_header {
    padding-bottom: 0.35rem;
  }

  #business-workspace.business_payroll .businesses_field_grid {
    gap: 0.35rem;
  }

  #business-workspace.business_payroll .businesses_pp_control_strip {
    gap: 0.55rem;
  }

  #business-workspace.business_audit {
    gap: 0.55rem;
  }

  #business-workspace.business_audit .business_audit_panels {
    gap: 0.55rem;
    margin-top: 0.55rem;
  }

  #business-workspace.business_audit .business_audit_summary,
  #business-workspace.business_audit .business_audit_section,
  #business-workspace.business_audit .business_audit_soc_stub {
    gap: 0.45rem;
    padding: 0.65rem 0.75rem;
    border-radius: 6px;
  }

  #business-workspace.business_audit .businesses_section_header {
    gap: 0.45rem;
    align-items: start;
  }

  #business-workspace.business_sites .business_sites_panels {
    gap: 0.55rem;
    margin-top: 0.55rem;
  }

  #business-workspace.business_sites .businesses_panel_sites_discovery {
    gap: 0.45rem;
    padding: 0.65rem 0.75rem;
    border-radius: 6px;
  }

  .business_sites_mobile_cards,
  .business_groups_mobile_cards {
    grid-template-columns: minmax(0, 1fr);
  }

  .business_sites_mobile_cards .datagrid_controls,
  .business_sites_mobile_cards .datagrid_toolbar_search_pagination,
  .business_sites_mobile_cards .datagrid_column_strip,
  .business_groups_mobile_cards .datagrid_controls,
  .business_groups_mobile_cards .datagrid_toolbar_search_pagination,
  .business_groups_mobile_cards .datagrid_column_strip {
    grid-column: 1;
    white-space: normal;
  }

  #business-workspace.business_sites {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    gap: 0.5rem;
  }

  #business-workspace.business_sites .businesses_sites_assigned_panel,
  #business-workspace.business_sites .business_sites_datagrid,
  .business_sites_mobile_cards,
  .business_sites_mobile_cards .datagrid_table,
  .business_sites_mobile_cards .datagrid_body {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
  }

  #business-workspace.business_sites .businesses_sites_assigned_panel {
    padding-inline: 0.55rem;
  }

  #business-workspace.business_sites .business_status_legend_row {
    align-items: stretch;
    gap: 0.45rem;
  }

  #business-workspace.business_sites .business_status_action_group {
    width: 100%;
    justify-content: flex-start;
  }

  #business-workspace.business_sites .business_sites_ownership_legend {
    justify-content: flex-start;
  }

  .business_sites_mobile_cards .datagrid_controls {
    display: grid;
    gap: 0.35rem;
  }

  .business_sites_mobile_cards .datagrid_search {
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
  }

  .business_sites_mobile_cards .datagrid_column_strip {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
    margin-bottom: 0.45rem;
    padding: 0.35rem 0.45rem;
  }

  .business_sites_mobile_cards .datagrid_column_toggle {
    min-width: 0;
  }

  .business_sites_mobile_cards .datagrid_header_row {
    display: none;
  }

  .business_sites_mobile_cards .datagrid_body {
    display: grid;
    gap: 0.45rem;
  }

  .business_sites_mobile_cards .datagrid_row {
    width: 100%;
    max-width: 100%;
    margin: 0;
    border: 1px solid var(--business-page-border, var(--border, rgba(255, 255, 255, 0.12)));
    border-radius: var(--radius-cell, 4px);
    background: color-mix(in srgb, var(--panel-bg, #151b24) 88%, #ffffff 4%);
    box-sizing: border-box;
    overflow: hidden;
  }

  .business_sites_mobile_cards .datagrid_row_content {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-template-areas:
      "site site"
      "entries gross"
      "wage last"
      "budget used"
      "actions actions";
    gap: 0.45rem;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    padding: 0.65rem;
    box-sizing: border-box;
  }

  .business_sites_mobile_cards .datagrid_item {
    min-width: 0;
    padding: 0;
    text-align: left;
    white-space: normal;
    overflow-wrap: anywhere;
  }

  .business_sites_mobile_cards .datagrid_col_site_name {
    grid-area: site;
  }

  .business_sites_mobile_cards .datagrid_col_entries {
    grid-area: entries;
  }

  .business_sites_mobile_cards .datagrid_col_work_gross {
    grid-area: gross;
  }

  .business_sites_mobile_cards .datagrid_col_wage {
    grid-area: wage;
  }

  .business_sites_mobile_cards .datagrid_col_last_worked {
    grid-area: last;
  }

  .business_sites_mobile_cards .datagrid_col_budget_amount {
    grid-area: budget;
  }

  .business_sites_mobile_cards .datagrid_col_budget_used {
    grid-area: used;
  }

  .business_sites_mobile_cards .datagrid_item_actions {
    grid-area: actions;
    justify-self: end;
    align-self: end;
  }

  .business_sites_mobile_cards .datagrid_col_entries:not(.datagrid_col_hidden),
  .business_sites_mobile_cards .datagrid_col_work_gross:not(.datagrid_col_hidden),
  .business_sites_mobile_cards .datagrid_col_wage:not(.datagrid_col_hidden),
  .business_sites_mobile_cards .datagrid_col_last_worked:not(.datagrid_col_hidden),
  .business_sites_mobile_cards .datagrid_col_budget_amount:not(.datagrid_col_hidden),
  .business_sites_mobile_cards .datagrid_col_budget_used:not(.datagrid_col_hidden) {
    display: grid;
    gap: 0.18rem;
    padding: 0.4rem 0.45rem;
    border-radius: 4px;
    background: color-mix(in srgb, var(--business-page-elevated-bg, #171d24) 88%, #ffffff 4%);
  }

  .business_sites_mobile_cards .datagrid_col_entries::before,
  .business_sites_mobile_cards .datagrid_col_work_gross::before,
  .business_sites_mobile_cards .datagrid_col_wage::before,
  .business_sites_mobile_cards .datagrid_col_last_worked::before,
  .business_sites_mobile_cards .datagrid_col_budget_amount::before,
  .business_sites_mobile_cards .datagrid_col_budget_used::before {
    content: attr(data-col-label);
    color: var(--business-page-muted, var(--text-muted, #c8d2e2));
    font-size: 0.68rem;
    font-weight: 700;
    line-height: 1.2;
    text-transform: uppercase;
  }

  .business_sites_mobile_cards .business_sites_site_name_cell {
    display: grid;
    gap: 0.25rem;
    justify-items: start;
    text-align: left;
  }

  .business_sites_mobile_cards .business_sites_site_name_primary {
    max-width: 100%;
    min-width: 0;
  }

  .business_sites_mobile_cards .business_sites_site_name_text {
    max-width: 100%;
    white-space: normal;
    overflow-wrap: anywhere;
    line-height: 1.25;
  }

  .business_sites_mobile_cards .business_sites_used_cell {
    max-width: 100%;
  }

  .business_sites_mobile_cards .datagrid_action {
    min-width: 2rem;
    min-height: 2rem;
    padding: 0.25rem 0.45rem;
  }

  #business-workspace.business_groups {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    gap: 0.5rem;
  }

  #business-workspace.business_groups .business_groups_panel,
  #business-workspace.business_groups .business_groups_datagrid,
  .business_groups_mobile_cards,
  .business_groups_mobile_cards .datagrid_table,
  .business_groups_mobile_cards .datagrid_body {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
  }

  #business-workspace.business_groups .business_groups_panel {
    padding-inline: 0.55rem;
  }

  #business-workspace.business_groups .business_status_legend_row {
    align-items: stretch;
    gap: 0.45rem;
  }

  #business-workspace.business_groups .business_status_action_group {
    width: 100%;
    justify-content: flex-start;
  }

  .business_groups_mobile_cards .datagrid_controls {
    display: grid;
    gap: 0.35rem;
  }

  .business_groups_mobile_cards .datagrid_search {
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
  }

  .business_groups_mobile_cards .datagrid_column_strip {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
    margin-bottom: 0.45rem;
    padding: 0.35rem 0.45rem;
  }

  .business_groups_mobile_cards .datagrid_column_toggle {
    min-width: 0;
  }

  .business_groups_mobile_cards .datagrid_header_row {
    display: none;
  }

  .business_groups_mobile_cards .datagrid_body {
    display: grid;
    gap: 0.45rem;
  }

  .business_groups_mobile_cards .datagrid_row {
    width: 100%;
    max-width: 100%;
    margin: 0;
    border: 1px solid var(--business-page-border, var(--border, rgba(255, 255, 255, 0.12)));
    border-radius: var(--radius-cell, 4px);
    background: color-mix(in srgb, var(--panel-bg, #151b24) 88%, #ffffff 4%);
    box-sizing: border-box;
    overflow: hidden;
  }

  .business_groups_mobile_cards .datagrid_row_content {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-template-areas:
      "name name"
      "members sites"
      "hours gross"
      "updated actions";
    gap: 0.45rem;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    padding: 0.65rem;
    box-sizing: border-box;
  }

  .business_groups_mobile_cards .datagrid_item {
    min-width: 0;
    padding: 0;
    text-align: left;
    white-space: normal;
    overflow-wrap: anywhere;
  }

  .business_groups_mobile_cards .datagrid_col_name {
    grid-area: name;
  }

  .business_groups_mobile_cards .datagrid_col_member_count {
    grid-area: members;
  }

  .business_groups_mobile_cards .datagrid_col_site_count {
    grid-area: sites;
  }

  .business_groups_mobile_cards .datagrid_col_hours {
    grid-area: hours;
  }

  .business_groups_mobile_cards .datagrid_col_work_gross {
    grid-area: gross;
  }

  .business_groups_mobile_cards .datagrid_col_updated_at {
    grid-area: updated;
  }

  .business_groups_mobile_cards .datagrid_item_actions {
    grid-area: actions;
    justify-self: end;
    align-self: end;
  }

  .business_groups_mobile_cards .datagrid_col_member_count:not(.datagrid_col_hidden),
  .business_groups_mobile_cards .datagrid_col_site_count:not(.datagrid_col_hidden),
  .business_groups_mobile_cards .datagrid_col_hours:not(.datagrid_col_hidden),
  .business_groups_mobile_cards .datagrid_col_work_gross:not(.datagrid_col_hidden),
  .business_groups_mobile_cards .datagrid_col_updated_at:not(.datagrid_col_hidden) {
    display: grid;
    gap: 0.18rem;
    padding: 0.4rem 0.45rem;
    border-radius: 4px;
    background: color-mix(in srgb, var(--business-page-elevated-bg, #171d24) 88%, #ffffff 4%);
  }

  .business_groups_mobile_cards .datagrid_col_member_count::before,
  .business_groups_mobile_cards .datagrid_col_site_count::before,
  .business_groups_mobile_cards .datagrid_col_hours::before,
  .business_groups_mobile_cards .datagrid_col_work_gross::before,
  .business_groups_mobile_cards .datagrid_col_updated_at::before {
    content: attr(data-col-label);
    color: var(--business-page-muted, var(--text-muted, #c8d2e2));
    font-size: 0.68rem;
    font-weight: 700;
    line-height: 1.2;
    text-transform: uppercase;
  }

  .business_groups_mobile_cards .business_groups_name_cell {
    display: grid;
    gap: 0.25rem;
    justify-items: start;
    text-align: left;
  }

  .business_groups_mobile_cards .business_groups_name_text {
    max-width: 100%;
    white-space: normal;
    overflow-wrap: anywhere;
    line-height: 1.25;
  }

  .business_groups_mobile_cards .datagrid_action {
    min-width: 2rem;
    min-height: 2rem;
    padding: 0.25rem 0.45rem;
  }

  #business-workspace.business_members {
    gap: 0.5rem;
    width: 100%;
    max-width: 100%;
    min-width: 0;
  }

  .business_members_grid_shell,
  .business_members_datagrid,
  [data-grid="business-members"] {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
  }

  .business_members_datagrid {
    overflow-x: hidden;
  }

  .business_members_metrics_bar {
    gap: 0.2rem 0.4rem;
    margin-bottom: 0.4rem;
    padding: 0.35rem 0.45rem;
    font-size: 0.82rem;
  }

  #main:has(#business-workspace.business_members) .business_context_header {
    grid-template-columns: minmax(0, 1fr) auto;
    column-gap: 0.65rem;
    align-items: start;
  }

  #main:has(#business-workspace.business_members) .business_context_name {
    grid-column: 1;
    grid-row: 1;
    min-width: 0;
  }

  #main:has(#business-workspace.business_members) .business_context_separator {
    display: none;
  }

  #main:has(#business-workspace.business_members) .business_subnav {
    grid-column: 1 / -1;
    grid-row: 2;
    width: 100%;
  }

  #main:has(#business-workspace.business_members) .business_context_actions {
    grid-column: 2;
    grid-row: 1;
    align-self: start;
    justify-self: end;
  }

  .business_members_info_button {
    width: 1.85rem;
    height: 1.85rem;
    min-width: 1.85rem;
    max-width: 1.85rem;
  }

  .business_members_info_dialog_grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .business_members_info_terms > div {
    grid-template-columns: minmax(0, 1fr);
    gap: 0.2rem;
  }

  .business_members_metric_divider {
    display: none;
  }

  .business_members_pending_row {
    flex-direction: column;
    align-items: stretch;
  }

  .business_members_pending_actions {
    justify-content: flex-end;
  }

  [data-grid="business-members"] .datagrid_column_strip {
    margin-bottom: 0.35rem;
    padding: 0.35rem 0.45rem;
    gap: 0.35rem;
  }

  [data-grid="business-members"] .datagrid_toolbar_search_pagination {
    grid-template-columns: minmax(0, 1fr);
    grid-template-areas:
      "search"
      "filters"
      "page"
      "pager";
    align-items: stretch;
    gap: 0.35rem;
    padding-inline: 0.45rem;
    margin-bottom: 0.45rem;
  }

  [data-grid="business-members"] .datagrid_toolbar_start,
  [data-grid="business-members"] .datagrid_toolbar_center,
  [data-grid="business-members"] .datagrid_toolbar_end {
    width: 100%;
    min-width: 0;
    max-width: 100%;
  }

  [data-grid="business-members"] .business_members_toolbar_filters {
    width: 100%;
    align-items: stretch;
  }

  [data-grid="business-members"] .datagrid_column_menu {
    align-self: stretch;
  }

  [data-grid="business-members"] .datagrid_column_menu_toggle {
    width: 100%;
  }

  [data-grid="business-members"] .business_members_toolbar_bulk {
    gap: 0.3rem 0.4rem;
    width: 100%;
    min-width: 0;
  }

  .business_members_report_control {
    position: static;
    display: grid;
    width: 100%;
    align-items: stretch;
  }

  .business_members_report_panel {
    position: fixed;
    inset: 0.5rem;
    transform: none;
    grid-template-columns: minmax(0, 1fr);
    inline-size: auto;
    block-size: auto;
    min-inline-size: 0;
    max-inline-size: none;
    max-block-size: none;
    margin: 0;
    overflow: auto;
  }

  .business_members_report_selected {
    grid-column: auto;
    grid-row: auto;
    overflow: visible;
    padding-right: 0;
  }

  .business_members_report_settings {
    grid-column: auto;
  }

  .business_members_report_toggle {
    justify-self: start;
  }

  [data-grid="business-members"] .datagrid_toolbar_center {
    justify-self: start;
    text-align: left;
  }

  [data-grid="business-members"] .datagrid_toolbar_end {
    justify-content: flex-start;
    justify-self: start;
    margin-left: 0;
    flex-wrap: wrap;
    gap: 0.25rem;
  }

  [data-grid="business-members"] .datagrid_toolbar_start .datagrid_search {
    min-width: 0;
  }

  [data-grid="business-members"] .datagrid_page_info {
    white-space: normal;
    font-size: 0.82rem;
    line-height: 1.3;
  }

  .business_members_bulk_toolbar_compact {
    flex-wrap: wrap;
    gap: 0.3rem 0.4rem;
    width: 100%;
    min-width: 0;
  }

  .business_members_bulk_divider {
    display: none;
  }

  [data-grid="business-members"] .datagrid_row {
    width: 100%;
    max-width: 100%;
    border: 1px solid var(--border, rgba(255, 255, 255, 0.12));
    border-radius: var(--radius-cell, 4px);
    margin: 0.25rem 0;
    box-sizing: border-box;
  }

  #businesses-members-grid,
  #businesses-members-grid .datagrid,
  #businesses-members-grid .datagrid_table,
  #businesses-members-grid .datagrid_body {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
  }

  [data-grid="business-members"] .datagrid_row_content {
    display: grid;
    grid-template-columns: 1.25rem minmax(0, 1fr) 2rem;
    grid-template-rows: auto auto auto;
    gap: 0.2rem 0.5rem;
    padding: 0.55rem 0.6rem;
    align-items: start;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
  }

  [data-grid="business-members"] .datagrid_col_select,
  [data-grid="business-members"] .business_members_row_select {
    grid-column: 1;
    grid-row: 1;
    width: auto;
    min-width: 0;
    max-width: none;
    padding: 0.1rem 0.25rem 0 0;
    justify-content: flex-start;
    align-self: start;
  }

  [data-grid="business-members"] .business_member_details_item {
    grid-column: 2;
    grid-row: 1;
    display: block;
    padding: 0;
    justify-content: flex-start;
    text-align: left;
    white-space: normal;
    overflow: visible;
    text-overflow: unset;
    min-width: 0;
  }

  [data-grid="business-members"] .business_member_details_cell {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 0.35rem;
    align-items: start;
    min-width: 0;
  }

  [data-grid="business-members"] .business_member_details_stack {
    min-width: 0;
    max-width: 100%;
  }

  [data-grid="business-members"] .business_member_details_cell:not(.business_member_details_cell_with_icon) {
    grid-template-columns: minmax(0, 1fr);
  }

  [data-grid="business-members"] .business_member_details_name {
    display: block;
    font-size: 0.82rem;
    line-height: 1.2;
    max-width: 100%;
    white-space: normal;
    overflow-wrap: normal;
    word-break: normal;
    hyphens: manual;
  }

  [data-grid="business-members"] .business_member_details_email,
  [data-grid="business-members"] .business_member_details_meta {
    display: block;
    font-size: 0.82rem;
    line-height: 1.25;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  [data-grid="business-members"] .datagrid_item_actions,
  [data-grid="business-members"] .business_member_row_menu {
    grid-column: 3;
    grid-row: 1;
    justify-content: flex-end;
    align-self: start;
    padding: 0;
    min-width: 0;
  }

  [data-grid="business-members"] .business_member_row_menu_toggle {
    inline-size: 2rem;
    min-inline-size: 2rem;
  }

  [data-grid="business-members"] .datagrid_col_last_active_at {
    display: none;
  }

  [data-grid="business-members"] .business_member_joined_item,
  [data-grid="business-members"] .business_member_hours_item,
  [data-grid="business-members"] .business_member_earnings_item {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    gap: 0;
    padding: 0.1rem 0 0;
    min-width: 0;
    white-space: normal;
    overflow: visible;
    text-overflow: unset;
    font-size: 0.82rem;
    line-height: 1.2;
  }

  [data-grid="business-members"] .business_member_joined_item {
    grid-column: 2 / -1;
    grid-row: 2;
    flex-direction: row;
    align-items: baseline;
    gap: 0.35rem;
    padding-top: 0.05rem;
  }

  [data-grid="business-members"] .business_member_hours_item {
    grid-column: 2;
    grid-row: 3;
    align-items: flex-start;
    text-align: left;
  }

  [data-grid="business-members"] .business_member_earnings_item {
    grid-column: 3;
    grid-row: 3;
    align-items: flex-end;
    text-align: right;
  }

  [data-grid="business-members"] .business_member_hours_item,
  [data-grid="business-members"] .business_member_earnings_item {
    padding-top: 0.25rem;
  }

  [data-grid="business-members"] .business_member_joined_item::before,
  [data-grid="business-members"] .business_member_hours_item::before,
  [data-grid="business-members"] .business_member_earnings_item::before {
    content: attr(data-col-label);
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--fore-muted, var(--text-muted, #9aa3b2));
    text-transform: uppercase;
    letter-spacing: 0.02em;
    flex-shrink: 0;
  }

  [data-grid="business-members"] .business_member_hours_item::before,
  [data-grid="business-members"] .business_member_earnings_item::before {
    margin-bottom: 0.1rem;
  }

  [data-grid="business-members"] .business_member_hours_cell,
  [data-grid="business-members"] .business_member_earnings_cell {
    gap: 0;
    align-items: inherit;
    text-align: inherit;
    min-width: 0;
  }

  [data-grid="business-members"] .business_member_hours_subline,
  [data-grid="business-members"] .business_member_earnings_trend {
    font-size: 0.82rem;
    line-height: 1.15;
  }

  [data-grid="business-members"] .business_member_hours_primary,
  [data-grid="business-members"] .business_member_earnings_primary {
    font-size: 0.82rem;
    line-height: 1.2;
  }

  [data-grid="business-members"] .datagrid_heading,
  [data-grid="business-members"] .datagrid_item {
    padding-left: 0;
    padding-right: 0;
  }
}

@media (horizontal-viewport-segments: 2) and (max-width: 900px) {
  body[class*='page-business'] .business_context_header {
    position: sticky;
    top: 0;
    z-index: 35;
    background: var(--color-bg);
  }

  body[class*='page-business'] .business_workspace {
    width: 100%;
    max-width: 100%;
    min-width: 0;
  }

  .business_workspace.business_dashboard .business_dashboard_metrics_grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .business_workspace.business_details .businesses_details_columns {
    grid-template-columns: minmax(0, 1fr);
  }

  .business_group_editor_dialog,
  .dialog_fullscreen {
    width: min(env(viewport-segment-width 1 0, calc(100vw - 1rem)), calc(100vw - 1rem));
    max-width: min(env(viewport-segment-width 1 0, calc(100vw - 1rem)), calc(100vw - 1rem));
  }
}

@media (vertical-viewport-segments: 2) and (max-width: 900px) {
  body[class*='page-business'] .business_context_header {
    position: sticky;
    top: 0;
    z-index: 35;
    background: var(--color-bg);
  }

  .business_group_editor_dialog,
  .dialog_fullscreen {
    max-height: calc(env(viewport-segment-height 0 1, 90dvh) - max(0.5rem, env(safe-area-inset-bottom, 0px)));
  }
}
