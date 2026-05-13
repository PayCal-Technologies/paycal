<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>

/* ── Page-level layout ────────────────────────────────────────── */
#main:has(#organizations-grid) {
  display: flex;
  flex-direction: column;
  gap: clamp(1rem, 2vw, 1.6rem);
}

#main:has(#organizations-grid) > .f_column.w100 {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

#main:has(#organizations-grid) > .organizations_top_columns,
#main:has(#organizations-grid) > .organizations_separator,
#main:has(#organizations-grid) > section.panel {
  width: min(80vw, 1240px);
  margin-left: auto;
  margin-right: auto;
}

#main:has(#organizations-grid) section.panel {
  margin-bottom: 0;
  padding: clamp(1rem, 2vw, 1.5rem);
}

/* ── Grid header (title + action button row) ─────────────────── */
.grid_header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.grid_header h2,
.organizations_section_header h2 {
  margin: 0;
  font-size: clamp(1.15rem, 2vw, 1.4rem);
  font-weight: 700;
  line-height: 1.2;
}

.grid_header h1 {
  margin: 0;
  font-size: clamp(1.15rem, 2vw, 1.4rem);
  font-weight: 700;
  line-height: 1.2;
}

.organizations_create_btn {
  flex-shrink: 0;
  padding: 0.55rem 1.25rem;
  font-size: var(--font-sm, 0.9rem);
  font-weight: 600;
  white-space: nowrap;
}

.organizations_header_actions {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
}

.organizations_help_button {
  width: 2.1rem;
  height: 2.1rem;
  border-radius: 999px;
  border: 2px solid color-mix(in srgb, var(--border, rgba(255, 255, 255, 0.22)) 82%, transparent);
  background: color-mix(in srgb, var(--surface, #1e2633) 88%, var(--bg, #0f141c) 12%);
  color: var(--text, #e7edf7);
  font-size: var(--font-sm);
  font-weight: 800;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--text, #e7edf7) 12%, transparent);
}

.organizations_help_button:hover,
.organizations_help_button:focus-visible {
  border-color: color-mix(in srgb, var(--color-primary, #33b5ff) 72%, var(--border, rgba(255, 255, 255, 0.22)));
  color: color-mix(in srgb, var(--color-primary, #33b5ff) 82%, var(--text, #e7edf7));
  transform: translateY(-1px);
}

.organizations_help_button:focus-visible {
  outline: 2px solid color-mix(in srgb, var(--color-primary, #33b5ff) 82%, #ffffff);
  outline-offset: 2px;
}

/* ── Datagrid container ──────────────────────────────────────── */
#organizations-grid.datagrid_container {
  min-height: 18rem;
  border-radius: var(--radius-sm, 6px);
  overflow: hidden;
}

#organizations-grid .datagrid_body {
  min-height: 18rem;
}

/* ── Empty-state placeholder ─────────────────────────────────── */
.organizations_grid_placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 16rem;
  padding: 2rem;
}

.organizations_grid_placeholder p {
  font-size: clamp(0.95rem, 1.5vw, 1.15rem);
  color: var(--text-muted, #888);
  font-weight: 500;
  letter-spacing: 0.01em;
  text-align: center;
}

.organizations_separator {
  width: 100%;
  height: 2px;
  border-radius: 999px;
  background: linear-gradient(90deg, transparent 0%, var(--border, rgba(255, 255, 255, 0.22)) 12%, var(--border, rgba(255, 255, 255, 0.22)) 88%, transparent 100%);
  margin: 0.25rem 0 0.5rem;
}

.organizations_hub_panel {
  width: 100%;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: clamp(1rem, 2vw, 1.5rem);
}

.organizations_header_panel,
.organizations_grid_panel,
.organizations_live_requests_panel {
  border: 1px solid color-mix(in srgb, var(--panel-border, #2a2a2a) 88%, var(--color-primary, #4a9eff));
  border-radius: 12px;
  background:
    linear-gradient(180deg, color-mix(in srgb, var(--color-primary, #4a9eff) 6%, transparent), transparent 30%),
    var(--panel-bg, #151515);
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--panel-border, #2a2a2a) 24%, transparent);
}

.organizations_dev_gated_panel {
  border: 2px solid #a020f0 !important;
}

.organizations_live_requests_panel .organizations_section_header h2 {
  display: inline-flex;
  align-items: center;
}

.organizations_live_requests_dot {
  display: inline-block;
  inline-size: 0.56rem;
  block-size: 0.56rem;
  margin-left: 0.44rem;
  border-radius: 999px;
  background: var(--color-notification-danger, color-mix(in srgb, var(--color-danger, #d64545) 88%, #ff837a));
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--panel-bg, #151515) 68%, transparent);
}

#main:has(#organizations-grid) section#organizations-hub.panel {
  border: 0;
  background: transparent;
  box-shadow: none;
  padding: 0;
}

.organizations_top_columns {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 1rem;
  align-items: start;
}

.organizations_top_column_primary {
  gap: 1rem;
}

.organizations_top_column_live {
  position: static;
}

.organizations_top_column_live .organizations_stack {
  max-height: min(62vh, 38rem);
  overflow: auto;
}

.organizations_hub_callout {
  margin: 0;
  padding: 0.85rem 1rem;
  border: 1px solid var(--border, rgba(255, 255, 255, 0.18));
  border-radius: var(--radius-sm, 6px);
  background: color-mix(in srgb, var(--surface, #1e2633) 88%, #5aaef2 12%);
  font-size: clamp(0.95rem, 1.2vw, 1.05rem);
  font-weight: 600;
  line-height: 1.4;
  color: var(--text, #e7edf7);
}

.organizations_premium_connect_panel {
  margin: 0;
  padding: clamp(0.95rem, 1.6vw, 1.2rem);
  border: 1px solid color-mix(in srgb, var(--color-primary, #4a9eff) 34%, var(--border, rgba(255, 255, 255, 0.22)));
  border-radius: 12px;
  background: color-mix(in srgb, var(--surface, #1e2633) 90%, var(--color-primary, #4a9eff) 10%);
}

.organizations_premium_connect_panel .organizations_hub_callout {
  border: 0;
  padding: 0;
  background: transparent;
  box-shadow: none;
}

.organizations_browser_panel {
  margin: 0;
  padding: clamp(0.9rem, 1.5vw, 1.2rem);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.organizations_current_panel {
  display: grid;
  gap: 1rem;
  margin: 0;
  padding: clamp(0.9rem, 1.5vw, 1.2rem);
  border: 1px solid color-mix(in srgb, var(--border, rgba(255, 255, 255, 0.22)) 84%, transparent);
  border-radius: 12px;
  background: color-mix(in srgb, var(--surface, #1e2633) 94%, var(--bg, #0f141c) 6%);
  box-shadow: var(--shadow-lg);
}

.organizations_current_header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.8rem;
  flex-wrap: wrap;
}

.organizations_membership_consent_dialog {
  max-width: min(38rem, 92vw);
}

.organizations_membership_consent_content {
  gap: 0.8rem;
}

.organizations_membership_consent_action {
  margin: 0;
  padding: 0.75rem 0.85rem;
  border-radius: 8px;
  border: 1px solid color-mix(in srgb, var(--border, rgba(255, 255, 255, 0.22)) 80%, transparent);
  background: color-mix(in srgb, var(--surface, #1e2633) 86%, var(--bg, #0f141c) 14%);
  font-weight: 600;
}

.organizations_membership_consent_ack_label {
  display: flex;
  align-items: flex-start;
  gap: 0.55rem;
  font-weight: 600;
}

.organizations_current_title,
.organizations_current_summary,
.organizations_current_guidance,
.organizations_current_status {
  margin: 0;
}

.organizations_current_actions {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  flex-wrap: wrap;
}

.organizations_current_meta_grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.55rem 1rem;
}

.organizations_current_meta_grid p {
  margin: 0;
}

.organizations_current_details_grid {
  display: grid;
  grid-template-columns: minmax(9rem, 12rem) minmax(0, 1fr);
  gap: 0.65rem 1rem;
  margin: 0 0 1rem;
}

.organizations_current_details_grid dt,
.organizations_current_details_grid dd {
  margin: 0;
}

.organizations_current_details_grid dt {
  font-weight: 700;
}

.organizations_browser_search_form {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 0.55rem;
}

.organizations_browser_search_form input[type="search"] {
  min-width: 0;
}

.organizations_browser_grid_layout {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}

.organizations_browser_grid_block {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.organizations_browser_grid_block h3 {
  margin: 0;
  font-size: 0.95rem;
}

#organizations-browser-grid .datagrid_body,
#organizations-browser-recent-grid .datagrid_body {
  min-height: 14rem;
}

.organizations_browser_cards {
  display: grid;
  gap: 0.65rem;
}

.organizations_browser_card {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 0.7rem;
  align-items: stretch;
  border: 1px solid color-mix(in srgb, var(--border, rgba(255, 255, 255, 0.22)) 86%, transparent);
  border-radius: 10px;
  background: color-mix(in srgb, var(--surface, #1e2633) 92%, var(--bg, #0f141c) 8%);
  padding: 0.8rem 0.85rem;
}

.organizations_browser_data_grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  column-gap: 0.9rem;
  row-gap: 0.45rem;
  min-width: 0;
}

.organizations_browser_cell {
  margin: 0;
}

.organizations_browser_span_full {
  grid-column: 1 / -1;
}

.organizations_browser_cell_label {
  text-align: left;
}

.organizations_browser_cell_value {
  text-align: right;
}

.organizations_browser_name {
  font-weight: 600;
  font-size: var(--font-sm);
}

.organizations_browser_location {
  color: var(--text-muted, #a3aab8);
  font-size: 0.84rem;
}

.organizations_browser_row_action {
  width: 100%;
  max-width: none;
}

.organizations_browser_owner_email {
  font-size: 0.85rem;
}

.organizations_browser_industry {
  font-size: 0.9rem;
}

.organizations_browser_employees,
.organizations_browser_support,
.organizations_browser_website,
.organizations_browser_website a {
  font-size: 0.84rem;
}

.organizations_browser_employees,
.organizations_browser_support,
.organizations_browser_website {
  color: var(--text-muted, #a3aab8);
}

.organizations_browser_website a {
  color: var(--color-primary, #33b5ff);
  text-decoration: none;
}

.organizations_browser_website a:hover,
.organizations_browser_website a:focus-visible {
  text-decoration: underline;
}

.organizations_browser_card_footer {
  grid-column: 1 / -1;
}

.organizations_top_caution {
  margin: 0;
  border: 2px solid color-mix(in srgb, var(--color-warning, #ef6c00) 72%, var(--panel-border, #2a2a2a));
  border-left-width: 8px;
  border-radius: var(--radius-article, var(--border-radius));
  background:
    linear-gradient(180deg, color-mix(in srgb, var(--color-warning, #ef6c00) 14%, transparent), transparent),
    color-mix(in srgb, var(--color-warning, #ef6c00) 18%, var(--panel-bg, #151515));
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.24), inset 0 0 0 1px color-mix(in srgb, var(--color-warning, #ef6c00) 22%, transparent);
}

.organizations_top_caution_title {
  margin: 0 0 0.35rem;
  font-size: clamp(0.98rem, 1.25vw, 1.08rem);
  font-weight: 700;
  letter-spacing: 0.01em;
  color: color-mix(in srgb, var(--color-warning, #ef6c00) 90%, var(--text, #f5f5f5));
}

.organizations_top_caution p {
  margin: 0;
  line-height: 1.45;
  color: color-mix(in srgb, var(--text, #f5f5f5) 92%, var(--color-warning, #ef6c00));
}

.hover_help_tooltip {
  position: fixed;
  z-index: 2147483640;
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

.organizations_definitions_panel {
  width: 100%;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.organizations_definitions_grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: clamp(0.65rem, 1.2vw, 1rem);
}

.organizations_definition_card {
  border: 1px solid var(--border, rgba(255, 255, 255, 0.18));
  border-radius: var(--radius-sm, 6px);
  padding: 0.8rem 0.9rem;
  background: color-mix(in srgb, var(--surface, #1e2633) 92%, var(--bg, #0f141c) 8%);
}

.organizations_definition_card h3 {
  margin: 0 0 0.45rem;
  font-size: 0.98rem;
}

.organizations_definition_list {
  margin: 0;
  display: grid;
  gap: 0.35rem;
}

.organizations_definition_list dt {
  margin: 0;
  font-weight: 700;
  text-transform: lowercase;
}

.organizations_definition_list dd {
  margin: 0;
  color: var(--text-muted, #a3aab8);
  line-height: 1.35;
}

.organizations_definitions_dialog {
  width: min(92vw, 58rem);
}

.organizations_definitions_dialog_header {
  border-bottom: 1px solid color-mix(in srgb, var(--panel-border, #2a2a2a) 82%, transparent);
}

.organizations_definitions_dialog_content {
  gap: 0.8rem;
}

/* ── Old named-panel margin overrides (kept for any reuse) ───── */
.organizations_intro_panel,
.organizations_discovery_panel,
.organizations_request_panel,
.organizations_member_panel,
.organizations_personal_panel,
.organizations_grid_panel {
  margin-bottom: 0;
}

section.panel.w50 {
  padding: calc(var(--pad-md) + var(--pad-sm, 0.35rem));
}

#main:has(#organizations_personal_form) {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: clamp(0.35rem, 0.7vw, 0.65rem);
  align-items: start;
}

#main:has(#organizations_personal_form) > section.panel {
  width: 100%;
  margin: 0;
}

.organizations_personal_panel {
  width: min(100%, 64rem);
}

.organizations_intro_panel h1,
.organizations_list_title,
.organizations_discovery_panel h2,
.organizations_personal_panel h2 {
  margin-bottom: var(--mar-xs);
}

.organizations_list_title {
  font-size: 1.4rem;
  font-weight: 700;
}

.organizations_create_form {
  display: grid;
  gap: 0.9rem;
}

.organizations_summary_pairs {
  display: grid;
  gap: 0.5rem;
  margin: 0.2rem 0 0.45rem;
}

.organizations_summary_pairs .item_pair {
  margin: 0;
  padding: 0.1rem 0;
}

#main:has(#panel-personal-info) {
  display: flex;
  flex-direction: column;
  gap: clamp(0.5rem, 1vw, 0.85rem);
}

#main:has(#panel-personal-info) > section.panel {
  width: min(80vw, 1200px);
  margin-left: auto;
  margin-right: auto;
}

.organizations_dual_panels {
  width: 100%;
  margin: 0;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--gap-md);
}

.organizations_dual_panels > section.panel {
  width: 100%;
  margin: 0;
}

#panel-personal-info.profile_lead_panel {
  width: min(80vw, 1200px);
}

#edit_details_status {
  position: fixed;
  left: 50%;
  top: 1rem;
  bottom: auto;
  transform: translateX(-50%);
  z-index: 1400;
  width: min(90vw, 32rem);
  padding: 0.75rem 1rem;
  margin: 0;
  border-radius: 8px;
  font-size: var(--font-sm);
  min-height: 2.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  box-sizing: border-box;
  border: 1px solid transparent;
  background: transparent;
  color: var(--text-muted, #999);
  transition: all 0.2s ease;
}

#edit_details_status:empty {
  display: none;
}

#edit_details_status.success {
  background: rgba(76, 175, 80, 0.15);
  color: #4caf50;
  border-color: rgba(76, 175, 80, 0.3);
}

#edit_details_status.error {
  background: rgba(244, 67, 54, 0.15);
  color: #f44336;
  border-color: rgba(244, 67, 54, 0.3);
}

#panel-personal-info .profile_personal_info_grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.6rem 1.25rem;
  align-items: start;
}

#panel-personal-info .item_pair {
  margin: 0;
  padding: 0.14rem 0;
  align-items: center;
}

#panel-personal-info .item_label {
  flex: 0 0 31%;
  max-width: 31%;
  line-height: 1.25;
  padding-top: 0;
}

#panel-personal-info .item_value {
  display: flex;
  flex-direction: column;
  gap: 0.22rem;
}

#panel-personal-info .item_value input,
#panel-personal-info .item_value select,
#panel-personal-info .item_value textarea,
#panel-personal-info .currency_finder_search,
#panel-personal-info .timezone_finder_search {
  min-height: 2.3rem;
  margin: 0;
  padding: 0.42rem 0.62rem;
  box-sizing: border-box;
}

#panel-personal-info .item_value .status_text {
  min-height: 1rem;
  margin: 0.06rem 0 0;
  line-height: 1.2;
}

#panel-personal-info .profile_personal_info_actions {
  margin-top: 0.45rem;
}

#panel-billing .organizations_section_header {
  justify-content: flex-start;
  margin-bottom: 1.25rem;
}

#panel-billing .organizations_section_header h2 {
  text-align: left;
  margin: 0;
  letter-spacing: 0.01em;
}

#panel-billing {
  --billing-premium-pill-bg: color-mix(in srgb, var(--color-primary, #00bcd4) 22%, var(--panel-bg, #151515));
  --billing-premium-pill-border: color-mix(in srgb, var(--color-primary, #00bcd4) 62%, var(--panel-border, #2a2a2a));
  --billing-premium-pill-text: color-mix(in srgb, var(--color-text, #f5f5f5) 96%, #ffffff);
  --billing-chip-bg: color-mix(in srgb, var(--color-primary, #00bcd4) 16%, var(--panel-bg, #151515));
  --billing-chip-border: color-mix(in srgb, var(--color-primary, #00bcd4) 58%, var(--panel-border, #2a2a2a));
  --billing-chip-text: color-mix(in srgb, var(--color-text, #f5f5f5) 95%, #ffffff);
  padding: 1.9rem 2rem 2rem;
  gap: 1.5rem;
}

#panel-billing > .help_text {
  margin: 0 0 1.55rem;
  text-align: center;
  justify-self: center;
  width: min(100%, 52rem);
}

.billing_shell {
  display: grid;
  gap: 1.9rem;
}

.organizations_hierarchy_intro {
  margin: 0;
  color: color-mix(in srgb, var(--text-muted, #a3aab8) 84%, var(--text, #e7edf7));
  line-height: 1.55;
}

.organizations_hierarchy_consequence_strip {
  display: grid;
  gap: 1rem;
  padding: 1.15rem 1.25rem;
  border: 1px solid color-mix(in srgb, var(--color-primary, #4a9eff) 25%, var(--border, rgba(255, 255, 255, 0.22)));
  border-radius: 12px;
  background:
    linear-gradient(180deg, color-mix(in srgb, var(--color-primary, #4a9eff) 10%, transparent), transparent 42%),
    color-mix(in srgb, var(--surface, #1e2633) 84%, var(--color-primary, #4a9eff) 16%);
}

.organizations_hierarchy_section_panel {
  margin: 0;
  padding: 0.9rem 0.95rem;
  border: 1px solid color-mix(in srgb, var(--border, rgba(255, 255, 255, 0.22)) 82%, transparent);
  border-radius: 10px;
  background: color-mix(in srgb, var(--surface, #1e2633) 90%, var(--bg, #0f141c) 10%);
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-primary, #4a9eff) 12%, transparent);
  display: grid;
  gap: 0.6rem;
}

.organizations_hierarchy_consequence_strip p,
.organizations_hierarchy_permission_line,
.organizations_hierarchy_consequence_line {
  margin: 0;
}

.organizations_hierarchy_section_title {
  margin: 0;
  font-weight: 800;
  letter-spacing: 0.02em;
  color: color-mix(in srgb, var(--text, #e7edf7) 94%, #ffffff 6%);
}

.organizations_hierarchy_section_title_roles {
  margin-top: 0.3rem;
  color: color-mix(in srgb, var(--color-primary, #4a9eff) 82%, var(--text, #e7edf7));
}

.organizations_hierarchy_list {
  margin: 0;
  padding: 0;
  list-style: none;
  display: grid;
  gap: 0.65rem;
}

.organizations_hierarchy_list li {
  position: relative;
  margin: 0;
  padding-left: 1.25rem;
  line-height: 1.5;
}

.organizations_hierarchy_list li::before {
  content: "◆";
  position: absolute;
  left: 0;
  top: 0.1rem;
  color: color-mix(in srgb, var(--color-primary, #4a9eff) 78%, var(--text, #e7edf7));
  font-size: 0.72rem;
}

.organizations_hierarchy_roles_list li {
  padding-bottom: 0.15rem;
}

.organizations_hierarchy_roles_list li strong {
  display: inline-block;
  margin-bottom: 0.18rem;
  color: color-mix(in srgb, var(--color-primary, #4a9eff) 80%, var(--text, #e7edf7));
}

.organizations_hierarchy_grid {
  align-items: stretch;
}

.organizations_hierarchy_table_wrap {
  overflow-x: auto;
}

.organizations_hierarchy_table {
  width: 100%;
  min-width: 760px;
  border-collapse: collapse;
  border: 1px solid var(--border, rgba(255, 255, 255, 0.18));
  border-radius: 12px;
  overflow: hidden;
  background: color-mix(in srgb, var(--surface, #1e2633) 92%, var(--bg, #0f141c) 8%);
}

.organizations_hierarchy_table th,
.organizations_hierarchy_table td {
  padding: 0.8rem 0.9rem;
  border-bottom: 1px solid color-mix(in srgb, var(--border, rgba(255, 255, 255, 0.18)) 86%, transparent);
  text-align: center;
  vertical-align: middle;
}

.organizations_hierarchy_table thead th {
  background: color-mix(in srgb, var(--surface, #1e2633) 76%, var(--color-primary, #4a9eff) 24%);
  font-size: 0.86rem;
  letter-spacing: 0.04em;
}

.organizations_hierarchy_table tbody th {
  text-align: left;
  font-weight: 600;
  color: var(--panel-text, #f5f5f5);
  background: color-mix(in srgb, var(--surface, #1e2633) 94%, transparent);
}

.organizations_hierarchy_table tbody td {
  font-weight: 700;
}

.organizations_hierarchy_table tbody tr:last-child th,
.organizations_hierarchy_table tbody tr:last-child td {
  border-bottom: 0;
}

.organizations_hierarchy_permission_line {
  font-weight: 600;
  line-height: 1.45;
}

.organizations_hierarchy_consequence_line {
  margin-top: 0.7rem;
  color: var(--text-muted, #a3aab8);
  line-height: 1.45;
}

/* Preserve semantic hidden state for billing views even with explicit display rules. */
#panel-billing [hidden] {
  display: none !important;
}

.billing_columns {
  display: grid;
  grid-template-columns: minmax(320px, 1.1fr) minmax(260px, 0.9fr);
  gap: 2.75rem;
  width: min(100%, 920px);
  margin: 0 auto;
}

#billing_premium_view .billing_columns {
  padding: 1.45rem 1.6rem 1.55rem;
  border: 1px solid color-mix(in srgb, var(--panel-border, #2a2a2a) 88%, #4a4a4a);
  border-radius: 16px;
  background: color-mix(in srgb, var(--panel-bg, #151515) 92%, #20242b);
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--panel-border, #2a2a2a) 28%, transparent);
}

.billing_column {
  display: grid;
  gap: 1rem;
  align-content: start;
  min-width: 0;
}

#panel-billing .billing_column_main {
  gap: 1.45rem;
  justify-items: start;
}

#billing_downgrade_zone {
  margin-top: 0.25rem;
  display: grid;
  gap: 0.95rem;
  width: 80%;
  max-width: 36rem;
  padding: 1.15rem 1.25rem 1.2rem;
  border: 1px solid color-mix(in srgb, var(--color-danger, #c62828) 50%, var(--panel-border, #2a2a2a));
  border-radius: 14px;
  background: color-mix(in srgb, var(--color-danger, #c62828) 8%, var(--panel-bg, #151515));
  justify-self: center;
  margin-inline: auto;
  justify-items: center;
  text-align: center;
  align-self: center;
}

#billing_downgrade_zone .danger_confirm_pill {
  width: 100%;
  justify-items: center;
}

#billing_downgrade_zone .danger_confirm_pill > span {
  line-height: 1.4;
}

#billing_downgrade_status {
  margin: 0;
  min-height: 0;
}

#billing_downgrade_status:empty {
  display: none;
}

#panel-billing .billing_column_main #billing_downgrade_zone .btn_delete {
  justify-self: center;
  margin-inline: auto;
}

#billing_downgrade_zone #billing_downgrade_confirm {
  display: inline-flex;
  justify-self: center;
  align-self: center;
  margin-inline: auto;
}

#billing_premium_view > #billing_downgrade_zone {
  width: min(80%, 34rem);
  justify-self: center;
}

.billing_organizations_link {
  margin: 0.35rem auto 0;
  font-size: 0.92rem;
  line-height: 1.45;
  text-align: center;
  justify-self: center;
}

.billing_organizations_link a {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: min(100%, 30rem);
  padding: 0.9rem 1.1rem;
  border-radius: 12px;
  border: 1px solid color-mix(in srgb, var(--color-success, #2e7d32) 58%, var(--panel-border, #2a2a2a));
  background: color-mix(in srgb, var(--color-success, #2e7d32) 14%, var(--panel-bg, #151515));
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-success, #2e7d32) 18%, transparent);
  color: color-mix(in srgb, var(--color-success, #2e7d32) 72%, #ffffff);
  font-weight: 700;
  text-decoration: none;
  transition: background-color 140ms ease, border-color 140ms ease, color 140ms ease, transform 140ms ease, box-shadow 140ms ease;
}

.billing_organizations_link a:hover,
.billing_organizations_link a:focus-visible {
  background: color-mix(in srgb, var(--color-success, #2e7d32) 24%, var(--panel-bg, #151515));
  border-color: color-mix(in srgb, var(--color-success, #2e7d32) 74%, #9ccc65);
  color: color-mix(in srgb, var(--color-success, #2e7d32) 52%, #ffffff);
  box-shadow: 0 10px 22px color-mix(in srgb, var(--color-success, #2e7d32) 18%, transparent);
  transform: translateY(-1px);
}

.billing_organizations_link a:focus-visible {
  outline: 3px solid color-mix(in srgb, var(--color-success, #2e7d32) 52%, #ffffff);
  outline-offset: 2px;
}

.billing_column h3 {
  margin: 0.15rem 0 0.25rem;
  font-size: clamp(1.03rem, 1.15vw, 1.2rem);
  color: var(--text, #f5f5f5);
}

.billing_plan_value {
  margin: 0;
  font-size: clamp(1.2rem, 1.35vw, 1.5rem);
  text-align: left;
  justify-self: start;
}

.billing_plan_value_free strong {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 999px;
  letter-spacing: 0.02em;
  color: var(--billing-premium-pill-text);
  background: var(--billing-premium-pill-bg);
  border: 1px solid var(--billing-premium-pill-border);
  box-shadow: 0 0 0 1px color-mix(in srgb, var(--billing-premium-pill-border) 28%, transparent), 0 6px 14px rgba(94, 195, 255, 0.16);
}

.billing_column .help_text {
  margin: 0;
  line-height: 1.55;
}

.billing_value_list {
  margin: 0;
  padding: 0;
  list-style: none;
  display: grid;
  gap: 0.5rem;
}

.billing_value_list li {
  margin: 0;
  display: grid;
  grid-template-columns: 0.75rem minmax(0, 1fr);
  align-items: start;
  column-gap: 0.6rem;
}

.billing_value_list li::before {
  content: "";
  width: 0.45rem;
  height: 0.45rem;
  margin-top: 0.34rem;
  border-radius: 2px;
  transform: rotate(45deg);
  background: color-mix(in srgb, var(--theme-signature-color, #3fa8ff) 78%, #ffffff);
  box-shadow: 0 0 0 1px color-mix(in srgb, var(--theme-signature-color, #3fa8ff) 42%, transparent);
}

.billing_value_list li > span {
  display: block;
  line-height: 1.5;
}

.billing_member_since {
  margin: 0;
  color: var(--text, #f5f5f5);
  justify-self: start;
  text-align: left;
}

.billing_cancel_notice {
  margin: 0.5rem 0 0 0;
  color: var(--text-muted, #b0b0b0);
  font-size: 0.9rem;
  font-style: italic;
  justify-self: start;
  text-align: left;
}

.billing_column .status_text {
  min-height: 1.2rem;
  margin: 0;
}

#panel-billing .billing_column_main .btn,
#panel-billing .billing_column_main .status_text {
  margin-top: 0.35rem;
}

#panel-billing .billing_column_main .btn + .btn {
  margin-top: 0.65rem;
}

#panel-billing .billing_column_main .btn:focus-visible {
  outline: 3px solid var(--color-focus-ring, #80deea);
  outline-offset: 2px;
}

#panel-billing .btn {
  width: auto;
  display: inline-flex;
  justify-content: center;
  min-width: 10rem;
}

#panel-billing .billing_column_main .btn {
  justify-self: start;
  align-self: start;
  width: auto !important;
}

#panel-billing .badge {
  display: inline-flex;
  align-items: center;
  margin-left: 0.6rem;
  padding: 0.12rem 0.5rem;
  border-radius: 999px;
  border: 1px solid var(--panel-border, #2a2a2a);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  line-height: 1.2;
}

#panel-billing .badge_past-due,
#panel-billing .badge_unpaid {
  color: color-mix(in srgb, var(--color-warning, #ef6c00) 90%, #000000);
  background: color-mix(in srgb, var(--color-warning, #ef6c00) 22%, var(--panel-bg, #151515));
  border-color: color-mix(in srgb, var(--color-warning, #ef6c00) 60%, var(--panel-border, #2a2a2a));
}

#panel-billing .badge_canceled,
#panel-billing .badge_incomplete-expired {
  color: color-mix(in srgb, var(--color-danger, #c62828) 94%, #ffffff);
  background: color-mix(in srgb, var(--color-danger, #c62828) 20%, var(--panel-bg, #151515));
  border-color: color-mix(in srgb, var(--color-danger, #c62828) 56%, var(--panel-border, #2a2a2a));
}

#panel-danger-zone {
  padding-inline: calc(var(--pad-md) + 1rem);
  border-color: color-mix(in srgb, #ff3b3b 75%, var(--panel-border, #2a2a2a));
  background:
    linear-gradient(180deg, color-mix(in srgb, #ff3b3b 18%, transparent), transparent),
    var(--panel-bg);
  box-shadow: inset 0 0 0 1px color-mix(in srgb, #ff3b3b 32%, transparent);
}

#panel-danger-zone .organizations_section_header {
  margin-bottom: 0.95rem;
}

#panel-danger-zone h2,
#panel-danger-zone h3 {
  color: #ff6a6a;
}

#panel-danger-zone h2 {
  text-shadow: 0 0 12px rgba(255, 59, 59, 0.22);
}

.danger_zone_intro {
  margin: 0;
  max-width: none;
  width: 100%;
  font-size: calc(var(--font-md) + 0.08rem);
  line-height: 1.45;
}

.danger_zone_actions {
  display: grid;
  gap: 0.95rem;
  font-size: var(--font-md);
  color: var(--text, #f5f5f5);
}

.danger_zone_row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.6rem;
  align-items: start;
  padding: 0.9rem 0;
  border: 0;
  border-radius: 0;
  background: transparent;
}

.danger_zone_text h3 {
  margin: 0 0 0.28rem;
  font-size: var(--font-md);
}

.danger_zone_text .help_text {
  margin: 0;
  color: var(--text, #f5f5f5);
  font-size: calc(var(--font-md) + 0.08rem);
  line-height: 1.45;
}

.danger_zone_controls {
  display: grid;
  justify-items: start;
  align-items: start;
  gap: 0.45rem;
}

.danger_confirm_pill {
  display: grid;
  gap: 0.45rem;
  width: min(100%, 540px);
  padding: 0;
  border-radius: 0;
  border: 0;
  background: transparent;
  justify-items: start;
}

.danger_confirm_pill > span {
  font-size: var(--font-md);
  color: var(--text, #f5f5f5);
  padding: 0;
}

.danger_confirm_pill code {
  font-family: inherit;
  font-size: inherit;
  letter-spacing: 0.01em;
  background: transparent;
  border: 0;
  padding: 0;
  color: color-mix(in srgb, #ffffff 92%, #ff8f8f);
}

.danger_confirm_pill input[type="text"] {
  width: 100%;
  min-width: 0;
  padding: 0.42rem 0.58rem;
  font-size: var(--font-md);
  border-radius: 6px;
  border: 1px solid color-mix(in srgb, #ff4f4f 48%, var(--input-border, #3a3a3a));
  background: color-mix(in srgb, var(--panel-bg, #151515) 88%, #111);
  color: var(--text, #f5f5f5);
  text-transform: uppercase;
}

.danger_confirm_pill input[type="text"]:focus-visible {
  outline: 2px solid color-mix(in srgb, #ff4f4f 78%, #ffffff);
  outline-offset: 1px;
}

#danger_delete_data_confirm,
#danger_delete_account_confirm,
#billing_downgrade_confirm {
  min-width: 8rem;
  min-height: 2.3rem;
  padding: 0.45rem 0.85rem;
  box-sizing: border-box;
  justify-self: start;
}

#panel-danger-zone .btn_delete,
#billing_downgrade_zone .btn_delete {
  background: #c91515;
  border-color: #ff5252;
  color: #ffffff;
}

#panel-danger-zone .btn_delete:hover,
#panel-danger-zone .btn_delete:focus-visible,
#billing_downgrade_zone .btn_delete:hover,
#billing_downgrade_zone .btn_delete:focus-visible {
  background: #e02020;
  border-color: #ff7a7a;
}

.danger_confirm_pill form {
  margin: 0;
  display: grid;
  gap: 0.45rem;
  width: 100%;
}

#danger_zone_status {
  margin: 0.55rem 0 0;
  padding: 0.8rem 1rem;
  border-radius: 10px;
  border: 1px solid color-mix(in srgb, #4ea1ff 65%, var(--panel-border, #2a2a2a));
  background:
    linear-gradient(180deg, color-mix(in srgb, #4ea1ff 28%, transparent), transparent),
    color-mix(in srgb, #155db8 72%, var(--panel-bg, #151515));
  color: #ffffff;
  font-size: calc(var(--font-md) + 0.02rem);
  line-height: 1.4;
  box-shadow: inset 0 0 0 1px color-mix(in srgb, #6db5ff 30%, transparent);
}

#danger_zone_status:empty {
  display: none;
}

.organizations_create_actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-top: 0.45rem;
}

.organizations_disclaimer {
  margin-top: var(--mar-sm);
  padding: var(--pad-md);
  border: 1px solid var(--input-border);
  border-radius: var(--radius-article, var(--border-radius));
  background: var(--panel-bg);
}

.organizations_discovery_panel {
  display: grid;
  gap: 0.9rem;
}

.organizations_discovery_panel .organizations_discovery_section {
  padding: 0.8rem;
  border: 1px solid color-mix(in srgb, var(--input-border, #3a3a3a) 85%, transparent);
  border-radius: var(--radius-article, var(--border-radius));
  background: color-mix(in srgb, var(--panel-bg) 92%, var(--color-surface-strong, #1e1e1e));
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--panel-border, #2a2a2a) 35%, transparent);
}

.organizations_discovery_panel .organizations_discovery_section h3 {
  margin: 0 0 0.55rem;
  font-size: 0.98rem;
  letter-spacing: 0.01em;
}

.organizations_discovery_panel .organizations_discovery_section .organizations_create_actions {
  margin-top: 0.45rem;
}

.organizations_disclaimer_alert {
  margin-top: 0.2rem;
  border: 1px solid color-mix(in srgb, var(--status-error-border, #ff5f5f) 72%, var(--input-border, #3a3a3a));
  border-left: 5px solid var(--status-error-border, #ff5f5f);
  background:
    linear-gradient(180deg, color-mix(in srgb, var(--status-error-border, #ff5f5f) 13%, transparent), transparent),
    color-mix(in srgb, var(--status-error-bg, #3b1e1e) 78%, var(--panel-bg, #151515));
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.28), inset 0 0 0 1px color-mix(in srgb, var(--status-error-border, #ff5f5f) 26%, transparent);
}

.organizations_disclaimer_alert p {
  margin: 0.42rem 0 0;
  color: color-mix(in srgb, var(--status-error-text, #ffd8d8) 90%, var(--text, #f5f5f5));
}

.organizations_disclaimer_alert h3 {
  margin: 0.2rem 0 0;
  font-size: 0.95rem;
  letter-spacing: 0.01em;
  color: var(--status-error-text, #ffd8d8);
}

.organizations_disclaimer_alert h3:first-child {
  margin-top: 0;
}

.organizations_global_warning {
  margin-top: var(--mar-md);
  clear: both;
}

.organizations_disclaimer_alert p:first-child {
  margin-top: 0;
}

.organizations_disclaimer_alert strong {
  color: var(--status-error-text, #ffd8d8);
}

.organizations_disclaimer p,
.organizations_dialog_notice {
  margin: 0.45rem 0 0;
}

.organizations_disclaimer p:first-child {
  margin-top: 0;
}

.organizations_personal_pay_heading {
  margin: var(--mar-sm) 0 var(--mar-xs);
}

.organizations_pp_control_strip {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0;
}

.organizations_pp_control {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  padding: 0.55rem 0.65rem 0.65rem;
  border-left: 1px solid var(--fore-dark, #2a2a2a);
  background: var(--back-light, rgba(255, 255, 255, 0.04));
}

.organizations_pp_control:first-child {
  border-left: 0;
}

.organizations_pp_control label {
  display: block;
  text-align: center;
  font-size: var(--font-sm);
  font-weight: 700;
}

.organizations_pp_control_label {
  display: block;
  text-align: center;
  font-size: var(--font-sm);
  font-weight: 700;
}

.organizations_pp_control select,
.organizations_pp_control input {
  width: 100%;
}

.organizations_grace_radio_group {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-start;
  gap: 0.35rem;
}

.organizations_grace_radio_group .radio + label {
  flex: 1 1 5.5rem;
  padding: 0.45rem 0.35rem;
  border-left: 0;
  font-size: var(--font-sm);
  white-space: nowrap;
}

.organizations_grace_radio_group .radio + label:first-of-type {
  border-left: 0;
}

#organizations_personal_form .pay_period_preview_compact {
  margin-top: var(--mar-sm);
}

#organizations_editor_preview.pay_period_preview_compact {
  margin-top: var(--mar-sm);
}

#organizations_personal_preview.organizations_preview_box,
#organizations_editor_preview.organizations_preview_box {
  border: 0;
  background: transparent;
  padding: 0;
  min-height: 0;
}

#organizations_personal_form .pp_three_week,
#organizations_editor_preview .pp_three_week {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
  margin-bottom: var(--mar-xs);
  font-size: 0.72rem;
}

#organizations_personal_form .pp_stripbar,
#organizations_editor_preview .pp_stripbar {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 0;
  margin-bottom: 0.25rem;
}

#organizations_personal_form .pp_three_week th,
#organizations_personal_form .pp_three_week td,
#organizations_editor_preview .pp_three_week th,
#organizations_editor_preview .pp_three_week td {
  border: 1px solid var(--fore-dark, #2a2a2a);
  text-align: center;
  padding: 0.2rem 0.1rem;
  position: relative;
}

#organizations_personal_form .pp_day_head,
#organizations_editor_preview .pp_day_head {
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--fore-dark, #2a2a2a);
  border-left-width: 0;
  background: var(--btn-selected-back, rgba(255, 255, 255, 0.08));
  font-weight: 600;
  padding: 0.2rem 0.1rem;
}

#organizations_personal_form .pp_day_head:first-child {
  border-left-width: 1px;
}

#organizations_editor_preview .pp_day_head:first-child {
  border-left-width: 1px;
}

#organizations_personal_form .pp_day_cell,
#organizations_editor_preview .pp_day_cell {
  background: var(--back-light, rgba(255, 255, 255, 0.04));
  cursor: pointer;
}

#organizations_personal_form .pp_day_number,
#organizations_editor_preview .pp_day_number {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.7rem;
  min-height: 1.7rem;
  padding: 0 0.2rem;
  border: 1px solid transparent;
  border-radius: 999px;
}

#organizations_personal_form .pp_day_cell:hover,
#organizations_personal_form .pp_day_cell:focus-visible,
#organizations_editor_preview .pp_day_cell:hover,
#organizations_editor_preview .pp_day_cell:focus-visible {
  outline: 2px solid var(--focus, #7aa2ff);
  outline-offset: -2px;
}

#organizations_personal_form .pp_month_label,
#organizations_editor_preview .pp_month_label {
  text-align: center;
  font-size: var(--font-md);
  margin-bottom: 0.75rem;
  margin-top: 0.5rem;
}

#organizations_personal_form .pp_preview_summary,
#organizations_editor_preview .pp_preview_summary {
  display: flex;
  justify-content: center;
  gap: 1.5rem;
  flex-wrap: wrap;
  text-align: center;
  font-size: var(--font-sm);
  margin-top: 0.75rem;
}

#organizations_personal_form .pp_preview_summary_item {
  white-space: nowrap;
}

#organizations_editor_preview .pp_preview_summary_item {
  white-space: nowrap;
}

#organizations_personal_form .pp_in_period,
#organizations_editor_preview .pp_in_period {
  border-top-color: #171717;
  border-bottom-color: #171717;
}

#organizations_personal_form .pp_in_p1,
#organizations_editor_preview .pp_in_p1 {
  background: rgba(47, 125, 50, 0.32);
}

#organizations_personal_form .pp_in_p2,
#organizations_editor_preview .pp_in_p2 {
  background: rgba(62, 116, 182, 0.30);
}

#organizations_personal_form .pp_ribbon_start_p1,
#organizations_personal_form .pp_ribbon_start_p2,
#organizations_editor_preview .pp_ribbon_start_p1,
#organizations_editor_preview .pp_ribbon_start_p2 {
  border-left: 2px solid #171717;
  border-top-left-radius: 9px;
  border-bottom-left-radius: 9px;
}

#organizations_personal_form .pp_ribbon_end_p1,
#organizations_personal_form .pp_ribbon_end_p2,
#organizations_editor_preview .pp_ribbon_end_p1,
#organizations_editor_preview .pp_ribbon_end_p2 {
  border-right: 2px solid #171717;
  border-top-right-radius: 9px;
  border-bottom-right-radius: 9px;
}

#organizations_personal_form .pp_badge,
#organizations_editor_preview .pp_badge {
  position: absolute;
  top: 1px;
  left: 3px;
  font-size: 0.58rem;
  color: #111;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 8px;
  padding: 0 0.3rem;
}

#organizations_personal_form .pp_today,
#organizations_editor_preview .pp_today {
  outline: 0;
}

#organizations_personal_form .pp_today .pp_day_number,
#organizations_editor_preview .pp_today .pp_day_number {
  border-color: color-mix(in srgb, var(--fore, #cfd7ff) 55%, transparent);
  color: inherit;
}

#organizations_personal_form .pp_grace_day,
#organizations_editor_preview .pp_grace_day {
  border-style: dashed;
  border-width: 2px;
}

#organizations_personal_form .pp_grace_day + .pp_grace_day,
#organizations_editor_preview .pp_grace_day + .pp_grace_day {
  border-left-width: 0;
}

#organizations_personal_form .pp_grace_1,
#organizations_editor_preview .pp_grace_1 {
  border-color: #2ecb5f;
}

#organizations_personal_form .pp_grace_2,
#organizations_editor_preview .pp_grace_2 {
  border-color: #ffd43b;
}

#organizations_personal_form .pp_grace_3,
#organizations_editor_preview .pp_grace_3 {
  border-color: #ff4d4f;
}

#organizations_personal_form .pp_grace_p1,
#organizations_editor_preview .pp_grace_p1 {
  box-shadow: inset 0 0 0 1px rgba(47, 125, 50, 0.85);
}

#organizations_personal_form .pp_grace_p2,
#organizations_editor_preview .pp_grace_p2 {
  box-shadow: inset 0 0 0 1px rgba(62, 116, 182, 0.9);
}

.organizations_live_toast {
  position: fixed;
  left: 50%;
  top: auto;
  bottom: calc(env(safe-area-inset-bottom, 0px) + 1rem);
  transform: translateX(-50%) translateY(0.5rem);
  opacity: 0;
  pointer-events: none;
  z-index: 11000;
  min-width: min(92vw, 26rem);
  max-width: min(92vw, 40rem);
  min-height: 3rem;
  padding: 0.75rem 1rem 0.75rem 1.15rem;
  border-radius: var(--radius-panel, var(--border-radius));
  border: 1px solid color-mix(in srgb, var(--panel-border) 72%, transparent);
  border-left-width: 4px;
  background:
    linear-gradient(180deg, color-mix(in srgb, var(--panel-text) 4%, transparent), transparent),
    var(--panel-bg);
  color: var(--panel-text, var(--text));
  backdrop-filter: blur(10px);
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.32), 0 0 0 1px color-mix(in srgb, var(--panel-border) 30%, transparent);
  transition: opacity 160ms ease, transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease, background-color 160ms ease;
  font-size: var(--font-md);
  font-weight: 600;
  letter-spacing: 0.01em;
}

#organizations_dialog_live_toast {
  z-index: 2147483600;
}

#organizations_live_toast {
  z-index: 2147483500;
}

.organizations_live_toast::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0.6rem;
  width: 0.45rem;
  height: 0.45rem;
  border-radius: 999px;
  transform: translateY(-50%);
  background: currentColor;
  opacity: 0.82;
  box-shadow: 0 0 0 0.2rem color-mix(in srgb, currentColor 18%, transparent);
}

.organizations_live_toast_show {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
  box-shadow: 0 14px 36px rgba(0, 0, 0, 0.38), 0 0 0 1px color-mix(in srgb, var(--panel-border) 34%, transparent);
}

.organizations_live_toast_save {
  background:
    linear-gradient(180deg, color-mix(in srgb, var(--status-success-border) 10%, transparent), transparent),
    var(--status-success-bg);
  color: var(--status-success-text);
  border-color: color-mix(in srgb, var(--status-success-border) 45%, var(--panel-border));
  border-left-color: var(--status-success-border);
}

.organizations_live_toast_error {
  background:
    linear-gradient(180deg, color-mix(in srgb, var(--status-error-border) 10%, transparent), transparent),
    var(--status-error-bg);
  color: var(--status-error-text);
  border-color: color-mix(in srgb, var(--status-error-border) 48%, var(--panel-border));
  border-left-color: var(--status-error-border);
}

.organizations_grid_host .datagrid_row_content {
  cursor: pointer;
}

.organizations_grid_host .datagrid_header_content,
.organizations_grid_host .datagrid_row_content {
  grid-template-columns: minmax(13rem, 2.2fr) repeat(3, minmax(4.5rem, 1fr)) minmax(6.5rem, auto);
}

.organizations_grid_host .datagrid_heading_actions,
.organizations_grid_host .datagrid_item_actions {
  display: flex;
  justify-content: flex-end;
}

.organizations_grid_host .datagrid_row .datagrid_item:first-child {
  display: flex;
  align-items: center;
  overflow: visible;
  text-overflow: clip;
}

.organizations_notification_dot {
  --notification-dot-color: var(--color-notification-danger, color-mix(in srgb, var(--color-danger, #d64545) 88%, #ff837a));
  display: inline-block;
  inline-size: 0.58rem;
  block-size: 0.58rem;
  min-inline-size: 0.58rem;
  min-block-size: 0.58rem;
  margin-left: 0.42rem;
  border-radius: 999px;
  background: var(--notification-dot-color);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--panel-bg) 68%, transparent);
}

.organizations_delete_pill {
  margin-left: 0;
  padding: 0.12rem 0.5rem;
  border: 1px solid var(--input-border);
  border-radius: 999px;
  background: transparent;
  color: var(--text-muted);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.01em;
}

.organizations_delete_pill:hover,
.organizations_delete_pill:focus-visible {
  color: var(--color-danger);
  border-color: var(--color-danger);
}

.organizations_delete_pill_confirm,
.organizations_delete_pill_confirm:hover,
.organizations_delete_pill_confirm:focus-visible {
  color: #fff;
  border-color: var(--color-danger);
  background: var(--color-danger);
}

.organizations_grid_host .organizations_row_premium_locked .datagrid_item {
  opacity: 0.95;
}

.organizations_premium_chip {
  display: inline-flex;
  align-items: center;
  margin-left: 0.5rem;
  padding: 0.1rem 0.45rem;
  border: 1px solid var(--billing-chip-border);
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.01em;
  color: var(--billing-chip-text);
  background: var(--billing-chip-bg);
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--billing-chip-border) 22%, transparent);
  vertical-align: middle;
}

.organizations_dialog {
  position: fixed;
  width: 100vw;
  max-width: 100vw;
  height: 100dvh;
  max-height: 100dvh;
  top: 0 !important;
  left: 0 !important;
  transform: none !important;
  margin: 0 !important;
  border-radius: 0;
  --dialog-edge-top-size: 0;
  --dialog-edge-bottom-size: 0;
  --dialog-edge-left-size: 0;
  --dialog-edge-right-size: 0;
  --dialog-max-width: 100vw;
  --dialog-max-height: 100dvh;
  overflow: hidden;
}

.organizations_dialog[open] {
  display: grid !important;
}

.organizations_dialog form {
  display: grid;
  grid-template-rows: auto minmax(0, 1fr) auto;
  height: 100%;
  min-height: 0;
}

.organizations_dialog .modal_header,
.organizations_dialog .modal_content,
.organizations_dialog .modal_footer {
  padding: calc(var(--pad-md, 1rem) * 0.5);
}

.organizations_dialog .modal_header {
  flex: 0 0 auto;
  margin: 0 !important;
  padding: 0.2rem 0.45rem !important;
  align-items: center;
  min-height: 0;
}

.organizations_dialog .modal_header .btn_close,
.organizations_dialog .modal_header .modal_close,
.organizations_dialog .modal_header [data-dialog-close] {
  display: none !important;
}

.organizations_dialog_header {
  width: 100%;
}

.organizations_dialog_header .modal_title {
  margin: 0;
}

.organizations_dialog_header .tablist_container {
  margin-top: 0;
}

.organizations_dialog_header_spacer {
  min-width: 0;
}

.organizations_dialog_subtitle {
  margin: 0.35rem 0 0;
}

.organizations_dialog_content {
  flex: 0 0 auto;
  min-height: 0;
  max-height: none;
  margin: 0 !important;
  padding-top: 0.1rem !important;
  display: block;
  overflow: auto;
  padding-bottom: max(3.5rem, calc(env(safe-area-inset-bottom, 0px) + 2.5rem));
}

.organizations_editor_grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.05rem;
}

.organizations_editor_card {
  border: 1px solid var(--panel-border, #2a2a2a);
  border-radius: 12px;
  background: var(--panel-bg, #151515);
  padding: calc(var(--pad-md) + var(--pad-sm, 0.35rem));
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--panel-border, #2a2a2a) 24%, transparent);
}

.organizations_editor_panel {
  border-color: color-mix(in srgb, var(--panel-border, #2a2a2a) 88%, var(--color-primary, #4a9eff));
  background:
    linear-gradient(180deg, color-mix(in srgb, var(--color-primary, #4a9eff) 6%, transparent), transparent 28%),
    var(--panel-bg, #151515);
}

.organizations_editor_panel .organizations_section_header {
  padding-bottom: 0.55rem;
  margin-bottom: 0.55rem;
  border-bottom: 1px solid color-mix(in srgb, var(--panel-border, #2a2a2a) 76%, transparent);
}

.organizations_panel_audit_timeline {
  box-shadow:
    inset 0 0 0 1px color-mix(in srgb, var(--panel-border, #2a2a2a) 24%, transparent),
    0 0 0 1px color-mix(in srgb, var(--color-primary, #4a9eff) 14%, transparent);
}

.organizations_editor_card h3 {
  margin-top: 0;
  margin-bottom: var(--mar-sm);
  font-size: 0.92rem !important;
  line-height: 1.2;
}

.organizations_editor_card_full {
  grid-column: 1 / -1;
}

.organizations_panel_sites_discovery,
.organizations_panel_pay_period,
.organizations_panel_contact_directory,
.organizations_panel_org_details {
  grid-column: 1 / -1;
}

.organizations_danger_zone_panel {
  border: 1px solid color-mix(in srgb, var(--color-danger, #c62828) 52%, var(--panel-border, #2a2a2a));
  border-left-width: 6px;
  border-radius: 12px;
  background: color-mix(in srgb, var(--color-danger, #c62828) 8%, var(--panel-bg, #151515));
}

.organizations_danger_zone_panel h3 {
  color: color-mix(in srgb, var(--color-danger, #ff6b6b) 82%, var(--panel-text, #f5f5f5));
  letter-spacing: 0.03em;
}

.organizations_danger_zone_disclaimer {
  margin: 0.45rem 0 0;
  font-size: 0.84rem;
  line-height: 1.35;
  color: color-mix(in srgb, var(--text-muted, #a3aab8) 84%, var(--color-danger, #ff8a80));
}

.organizations_transfer_selected_member {
  margin-top: 0.5rem;
}

.organizations_transfer_selected_member.organizations_empty {
  display: none;
}

.organizations_transfer_selected_member_row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.8rem;
  padding: 0.7rem 0.85rem;
  border: 1px solid color-mix(in srgb, var(--color-danger, #c62828) 30%, var(--panel-border, #2a2a2a));
  border-radius: 10px;
  background: color-mix(in srgb, var(--panel-bg, #151515) 86%, var(--color-danger, #c62828) 14%);
}

.organizations_transfer_selected_member_text {
  display: grid;
  gap: 0.2rem;
  min-width: 0;
}

.organizations_transfer_selected_member_text strong,
.organizations_transfer_selected_member_text span {
  overflow-wrap: anywhere;
}

.organizations_transfer_selected_member_text span {
  font-size: 0.84rem;
  color: var(--text-muted, #a3aab8);
}

.organizations_transfer_confirmation_container {
  margin-top: 0.8rem;
  padding: 0.7rem 0.85rem;
  border: 1px solid color-mix(in srgb, var(--status-warning-border, #ef6c00) 45%, var(--panel-border, #2a2a2a));
  border-radius: 10px;
  background: color-mix(in srgb, var(--status-warning-bg, #4e2f00) 50%, var(--panel-bg, #151515));
}

.organizations_transfer_confirmation_container.organizations_empty {
  display: none;
}

.organizations_owner_summary_card {
  border: 1px solid color-mix(in srgb, var(--color-primary, #4a9eff) 32%, var(--panel-border, #2a2a2a));
  background: color-mix(in srgb, var(--color-primary, #4a9eff) 6%, var(--panel-bg, #151515));
}

.organizations_owner_summary_grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.9rem;
}

.organizations_owner_summary_item {
  min-width: 0;
}

.organizations_owner_summary_item span,
.organizations_owner_summary_item strong {
  display: block;
  overflow-wrap: anywhere;
}

.organizations_owner_summary_item span {
  margin-bottom: 0.25rem;
  font-size: 0.78rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--text-muted, #a3aab8);
}

.organizations_owner_summary_item strong {
  font-size: 1rem;
  color: var(--panel-text, #f5f5f5);
}

@media (max-width: 900px) {
  .organizations_owner_summary_grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 560px) {
  .organizations_owner_summary_grid {
    grid-template-columns: 1fr;
  }
}

.organizations_transfer_confirmation_label {
  display: block;
  margin-bottom: 0.4rem;
  color: color-mix(in srgb, var(--status-warning-text, #ffd7a1) 88%, var(--panel-text, #f5f5f5));
  font-size: 0.88rem;
  font-weight: 600;
  letter-spacing: 0.02em;
}

#organizations_transfer_confirmation {
  display: block;
  width: 100%;
  padding: 0.5rem 0.65rem;
  margin-bottom: 0.35rem;
  border: 1px solid color-mix(in srgb, var(--status-warning-border, #ef6c00) 50%, var(--border-default, #333));
  border-radius: 6px;
  background: color-mix(in srgb, var(--panel-bg, #151515) 92%, var(--status-warning-bg, #4e2f00));
  color: var(--text-default, #f5f5f5);
  font-size: 0.92rem;
  line-height: 1.4;
}

#organizations_transfer_confirmation:focus {
  outline: 1px solid color-mix(in srgb, var(--focus-ring, #4a9eff) 70%, var(--status-warning-border, #ef6c00));
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--focus-ring, #4a9eff) 20%, transparent);
}

.organizations_editor_pp_controls {
  grid-template-columns: repeat(3, minmax(0, 1fr));
  margin-bottom: var(--mar-sm);
}

.organizations_section_header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: var(--mar-md);
}

.organizations_section_header h3,
.organizations_section_header p {
  margin: 0;
}

.organizations_field_grid {
  display: grid;
  grid-template-columns: 31% minmax(0, 1fr);
  gap: 0.6rem 1.25rem;
  align-items: center;
}

.organizations_destructive_hint {
  grid-column: 1 / -1;
  margin: -0.1rem 0 0.35rem;
  padding: 0.55rem 0.7rem;
  border: 1px solid color-mix(in srgb, var(--status-warning-border, #ef6c00) 45%, var(--panel-border, #2a2a2a));
  border-left-width: 4px;
  border-radius: 8px;
  background: color-mix(in srgb, var(--status-warning-bg, #4e2f00) 72%, var(--panel-bg, #151515));
  color: color-mix(in srgb, var(--status-warning-text, #ffd7a1) 92%, var(--panel-text, #f5f5f5));
  font-size: var(--font-sm, 0.9rem);
  line-height: 1.35;
}

.organizations_details_columns {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
  margin-bottom: 0.65rem;
}

.organizations_details_panel {
  border: 0;
  border-radius: 0;
  background: transparent;
  padding: 0;
  margin-bottom: 0.8rem;
}

.organizations_details_column {
  display: grid;
  align-content: start;
  gap: 0.6rem;
  min-width: 0;
}

.organizations_details_column_title {
  margin: 0;
  font-size: 0.9rem;
  letter-spacing: 0.01em;
  color: var(--text-muted);
}

.organizations_field_grid label {
  font-size: var(--font-sm);
  text-align: right;
}

.organizations_field_grid input,
.organizations_field_grid select,
.organizations_field_grid textarea,
.organizations_inline_form input,
.organizations_inline_form select {
  width: 100%;
}

.organizations_field_grid input:disabled,
.organizations_field_grid select:disabled,
.organizations_field_grid textarea:disabled,
.organizations_field_locked {
  opacity: 0.58;
  cursor: not-allowed;
  background: color-mix(in srgb, var(--panel-bg, #151515) 92%, #8a8f98 8%);
  color: var(--text-muted, #a3aab8);
}

.organizations_field_grid textarea {
  resize: vertical;
  min-height: 4.5rem;
  font-family: inherit;
}

.organizations_readonly_field {
  padding: 0.75rem 0.9rem;
  border: 1px solid transparent;
  border-radius: 4px;
  background: transparent;
  color: var(--text, rgba(255, 255, 255, 0.85));
  font-size: 0.9375rem;
  line-height: 1.5;
  min-height: 2.5rem;
  display: flex;
  align-items: center;
  word-break: break-word;
}

.organizations_readonly_field:empty::before {
  content: '−';
  color: var(--text-muted, rgba(255, 255, 255, 0.4));
  font-size: 1.2em;
}

.organizations_contact_directory_header {
  margin-top: 0.55rem;
  margin-bottom: 0.45rem;
}

.organizations_domain_policy_toggle {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  min-height: 2.1rem;
}

.organizations_domain_policy_toggle span {
  font-size: var(--font-sm);
  color: var(--text-muted);
  line-height: 1.3;
}

.organizations_domain_policy_status {
  margin: 0.25rem 0 0.25rem;
}

.organizations_contact_directory_grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(22rem, 1fr));
  gap: 0.85rem;
  margin-bottom: 0.85rem;
}

.organizations_contact_card {
  border: 1px solid var(--panel-border);
  border-radius: var(--radius-article, var(--border-radius));
  background: color-mix(in srgb, var(--panel-bg) 94%, transparent);
  padding: 0.6rem;
  display: grid;
  grid-template-columns: 52px minmax(0, 1fr) auto;
  align-items: center;
  gap: 0.45rem;
}

.organizations_contact_directory_custom_grid {
  margin-top: 0.15rem;
}

.organizations_contact_image_popover {
  position: fixed;
  z-index: 1200;
  width: min(94vw, 24rem);
  padding: 0.7rem;
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  background: var(--panel-bg);
  box-shadow: 0 0.35rem 1rem rgba(0, 0, 0, 0.35);
}

.organizations_contact_image_popover.hidden {
  display: none;
}

.organizations_contact_image_popover_title {
  margin: 0 0 0.45rem;
  font-size: var(--font-sm);
  font-weight: 700;
}

.organizations_contact_image_dropzone {
  border: 1px dashed var(--panel-border);
  border-radius: var(--radius-sm, 6px);
  min-height: 3.2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 0.45rem;
  margin-bottom: 0.5rem;
  font-size: var(--font-sm);
  color: var(--text-muted);
  cursor: pointer;
}

.organizations_contact_image_dropzone.is_dragover {
  border-color: var(--theme-signature-color, var(--color-primary));
  color: var(--text);
}

.organizations_contact_image_popover_actions {
  display: flex;
  gap: 0.45rem;
  justify-content: flex-end;
}

.organizations_contact_card h4 {
  margin: 0;
  font-size: 0.88rem;
  color: var(--text-muted);
  letter-spacing: 0.01em;
}

.organizations_contact_card h5 {
  margin: 0;
  font-size: 0.88rem;
  color: var(--text-muted);
  letter-spacing: 0.01em;
}

.organizations_contact_card_avatar {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  border: 1px solid var(--panel-border);
  background: color-mix(in srgb, var(--panel-bg) 82%, #000 18%);
  object-fit: cover;
  grid-column: 1;
  grid-row: 1;
  align-self: center;
  cursor: pointer;
}

.organizations_contact_card_avatar:hover,
.organizations_contact_card_avatar:focus-visible {
  outline: 2px solid var(--theme-signature-color, var(--color-primary));
  outline-offset: 1px;
}

.organizations_contact_image_input {
  width: 100%;
}

.organizations_contact_card input {
  width: 100%;
}

.organizations_contact_card .organizations_contact_role_input {
  grid-column: 2;
  grid-row: 1;
  align-self: center;
}

.organizations_contact_card .organizations_contact_card_menu {
  grid-column: 3;
  grid-row: 1;
  position: relative;
  justify-self: end;
  align-self: center;
}

.organizations_contact_card .organizations_contact_card_menu_toggle {
  min-width: 2.2rem;
  height: 2.35rem;
  padding: 0 0.62rem;
  line-height: 1;
  font-size: 1.25rem;
  border: 1px solid transparent;
  border-radius: var(--radius-sm, 6px);
  background: transparent;
  box-shadow: none;
}

.organizations_contact_card .organizations_contact_card_menu_toggle:hover,
.organizations_contact_card .organizations_contact_card_menu_toggle:active,
.organizations_contact_card .organizations_contact_card_menu_toggle:focus-visible {
  background: var(--input-bg, color-mix(in srgb, var(--panel-bg) 88%, #000 12%));
  border-color: var(--panel-border);
}

.organizations_contact_card .organizations_contact_card_menu_delete {
  position: absolute;
  top: calc(100% + 0.2rem);
  right: 0;
  z-index: 4;
  white-space: nowrap;
  min-height: 2.1rem;
  padding: 0.68rem 1.08rem;
  font-size: var(--font-sm, 0.86rem);
  line-height: 1.1;
  border: 0;
  box-shadow: none;
}

.organizations_contact_card .organizations_contact_card_menu_delete:hover,
.organizations_contact_card .organizations_contact_card_menu_delete:active,
.organizations_contact_card .organizations_contact_card_menu_delete:focus-visible {
  background: var(--input-bg, color-mix(in srgb, var(--panel-bg) 88%, #000 12%));
}

.organizations_contact_card .organizations_contact_card_menu_delete[hidden] {
  display: none;
}

.organizations_contact_card .organizations_contact_body_input {
  grid-column: 1 / -1;
}

.organizations_contact_card .organizations_contact_card_menu_delete.is_confirming {
  border: 1px solid #b10000;
  background: #d11a1a;
  color: #ffffff;
  font-weight: 700;
}

.organizations_contact_card .organizations_contact_card_menu_delete.is_confirming:hover,
.organizations_contact_card .organizations_contact_card_menu_delete.is_confirming:active,
.organizations_contact_card .organizations_contact_card_menu_delete.is_confirming:focus-visible {
  border-color: #8f0000;
  background: #b91414;
  color: #ffffff;
}

.organizations_preview_box {
  margin-top: var(--mar-sm);
  border: 1px solid var(--panel-border);
  border-radius: var(--radius-article, var(--border-radius));
  background: rgba(255, 255, 255, 0.03);
  padding: var(--pad-md);
  min-height: 5.5rem;
}

.organizations_preview_title {
  font-weight: 700;
  margin-bottom: 0.35rem;
}

.organizations_preview_line,
.organizations_preview_meta {
  font-size: var(--font-sm);
}

.organizations_live_toast.organizations_live_toast_visible {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}

.organizations_preview_meta {
  margin-top: 0.45rem;
  color: var(--text-muted);
}

.organizations_inline_form {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 0.6rem;
  margin-bottom: var(--mar-sm);
}

.organizations_request_access_controls {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 0.5rem;
  align-items: start;
}

.organizations_access_level_pillbox {
  display: inline-flex;
  gap: 2px;
  padding: 2px;
  border: 1px solid var(--border, rgba(255, 255, 255, 0.22));
  border-radius: 999px;
  background: color-mix(in srgb, var(--border, rgba(255, 255, 255, 0.22)) 35%, transparent);
}

.organizations_access_level_pillbox .pill {
  flex: 1 1 auto;
  min-width: 8rem;
  border: 0;
  border-radius: 999px;
  padding: 0.5rem 1rem;
  font-weight: 600;
  font-size: 0.875rem;
  white-space: nowrap;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
  background: transparent;
  color: var(--text, rgba(255, 255, 255, 0.6));
}

.organizations_access_level_pillbox .pill:hover {
  background-color: color-mix(in srgb, var(--color-primary, #4a9eff) 25%, transparent);
  color: var(--text, rgba(255, 255, 255, 0.9));
}

.organizations_access_level_pillbox .pill_selected {
  background-color: var(--color-primary, #4a9eff);
  color: white;
}

.organizations_scope_grid {
  margin-bottom: var(--mar-sm);
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.35rem 0.8rem;
}

.organizations_scope_grid label {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: var(--font-sm);
}

.organizations_stack {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.organizations_stack_row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  border-bottom: 1px solid var(--panel-border);
  padding-bottom: 0.55rem;
}

.organizations_stack_row:last-child {
  border-bottom: 0;
  padding-bottom: 0;
}

.organizations_stack_row_audit {
  align-items: stretch;
}

.organizations_stack_row_hint {
  opacity: 0.82;
}

.organizations_stack_row_compact {
  align-items: center;
  gap: 0.55rem;
  padding-bottom: 0.4rem;
}

.organizations_stack_text {
  display: flex;
  flex-direction: column;
  gap: 0.18rem;
}

.organizations_invite_compact_line {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.45rem;
}

.organizations_invite_compact_meta {
  color: var(--text-muted);
  font-size: var(--font-xs, 0.82rem);
  letter-spacing: 0.01em;
}

.organizations_stack_text span {
  color: var(--text-muted);
  font-size: var(--font-sm);
}

.organizations_actions_row {
  display: flex;
  justify-content: flex-end;
  margin-top: var(--mar-sm);
}

.organizations_empty {
  color: var(--text-muted);
}

.organizations_dialog_footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.65rem;
  position: static;
  flex: 0 0 auto;
  z-index: 40;
  background-color: var(--panel-bg, #151b24);
  background-image: none;
  border-top: 1px solid var(--panel-border);
  padding-top: 0.55rem;
}

/* ── Tab Panels ──────────────────────────────────────────────── */
.organizations_dialog_content[role="tabpanel"] {
  display: block;
  height: auto;
}

.organizations_members_panel_hidden {
  display: none !important;
}

.organizations_members_panel_hidden.is-visible {
  display: flex !important;
  flex-direction: column;
}

/* ── Members Content Layout ──────────────────────────────────── */
.organizations_members_content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.organizations_members_section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.organizations_section_header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.organizations_section_header h3 {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  flex-shrink: 0;
}

/* ── Access Requests List ────────────────────────────────────── */
.organizations_requests_stack {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.organizations_requests_stack.organizations_empty p {
  color: var(--text-muted);
  font-size: var(--font-sm);
  margin: 0;
  padding: 1rem;
  text-align: center;
}

.organizations_access_request_row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.85rem 1rem;
  border: 1px solid var(--border, rgba(255, 255, 255, 0.18));
  border-radius: var(--radius-sm, 6px);
  background: rgba(255, 255, 255, 0.02);
}

.organizations_request_info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}

.organizations_request_email {
  font-size: var(--font-sm);
  color: var(--text-muted);
}

.organizations_request_actions {
  display: flex;
  gap: 0.5rem;
}

.organizations_request_actions button {
  padding: 0.45rem 0.85rem;
  font-size: var(--font-xs, 0.8rem);
  white-space: nowrap;
}

/* ── Member List Table ───────────────────────────────────────── */
.members_list_controls {
  display: flex;
  gap: 0.75rem;
  flex: 1;
  max-width: 24rem;
}

.member_search_input,
.members_list_controls select {
  padding: 0.45rem 0.65rem;
  font-size: var(--font-sm);
  border: 1px solid var(--border, rgba(255, 255, 255, 0.18));
  border-radius: var(--radius-sm, 6px);
  background: var(--input-bg, transparent);
  color: var(--text);
  flex: 1;
}

.member_search_input::placeholder {
  color: var(--text-muted);
}

.organizations_members_table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--font-sm);
  background: transparent;
}

.organizations_members_table thead {
  border-bottom: 2px solid var(--border, rgba(255, 255, 255, 0.18));
}

.organizations_members_table th {
  padding: 0.75rem;
  text-align: left;
  font-weight: 700;
  color: var(--text);
  cursor: default;
}

.organizations_members_table th[role="button"] {
  cursor: pointer;
  user-select: none;
}

.organizations_members_table tbody tr {
  border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.12));
  transition: background 0.15s ease;
}

.organizations_members_table tbody tr:hover {
  background: rgba(255, 255, 255, 0.04);
}

.organizations_members_table td {
  padding: 0.85rem 0.75rem;
  vertical-align: middle;
}

.organizations_members_table .member_name {
  font-weight: 600;
  color: var(--text);
}

.organizations_members_table .member_email {
  color: var(--text-muted);
  font-size: var(--font-xs, 0.8rem);
}

.organizations_members_table .member_role_badge {
  display: inline-block;
  padding: 0.25rem 0.65rem;
  border-radius: 12px;
  font-size: var(--font-xs, 0.8rem);
  font-weight: 600;
  background: rgba(90, 174, 242, 0.15);
  color: var(--primary, #5aaef2);
}

.organizations_members_table .member_status_badge {
  display: inline-block;
  padding: 0.25rem 0.65rem;
  border-radius: 12px;
  font-size: var(--font-xs, 0.8rem);
  font-weight: 600;
}

.organizations_members_table .member_status_badge.status_active {
  background: rgba(76, 175, 80, 0.15);
  color: #4caf50;
}

.organizations_members_table .member_status_badge.status_pending {
  background: rgba(255, 152, 0, 0.15);
  color: #ff9800;
}

.organizations_members_table .members_empty {
  text-align: center;
  color: var(--text-muted);
}

.organizations_members_table .members_empty td {
  padding: 2rem 0.75rem;
}

.organizations_member_actions {
  display: flex;
  gap: 0.35rem;
}

.organizations_member_actions button {
  padding: 0.4rem 0.65rem;
  font-size: var(--font-xs, 0.8rem);
  background: transparent;
  border: 1px solid var(--border, rgba(255, 255, 255, 0.18));
  border-radius: var(--radius-sm, 4px);
  color: var(--text);
  cursor: pointer;
  transition: all 0.15s ease;
}

.organizations_member_actions button:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: var(--border-hover, rgba(255, 255, 255, 0.25));
}

/* ── Invite Form ─────────────────────────────────────────────── */
.organizations_members_invite_form {
  display: grid;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid var(--border, rgba(255, 255, 255, 0.18));
  border-radius: var(--radius-sm, 6px);
  background: rgba(255, 255, 255, 0.02);
}

.organizations_members_invite_form .form_group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.organizations_members_invite_form label {
  font-size: var(--font-sm);
  font-weight: 600;
  color: var(--text);
}

.organizations_members_invite_form input {
  padding: 0.55rem 0.75rem;
  border: 1px solid var(--border, rgba(255, 255, 255, 0.18));
  border-radius: var(--radius-sm, 6px);
  background: var(--input-bg, transparent);
  color: var(--text);
  font-size: var(--font-sm);
}

.organizations_members_invite_form input::placeholder {
  color: var(--text-muted);
}

.organizations_members_invite_form .form_actions {
  display: flex;
  gap: 0.5rem;
}

.organizations_members_invite_form .btn {
  padding: 0.55rem 1.15rem;
  font-size: var(--font-sm);
  font-weight: 600;
}

.organizations_members_invites_block {
  display: grid;
  gap: 0.65rem;
  padding: 0.85rem 1rem;
  border: 1px solid var(--border, rgba(255, 255, 255, 0.18));
  border-radius: var(--radius-sm, 6px);
  background: rgba(255, 255, 255, 0.02);
}

.organizations_members_invites_grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
}

.organizations_members_invites_listbody {
  min-height: 14rem;
  max-height: 14rem;
  overflow: auto;
  padding-right: 0.15rem;
  scrollbar-gutter: stable;
}

.organizations_members_invites_history_grid .datagrid_body {
  min-height: 14rem;
  max-height: 14rem;
  overflow: auto;
}

.organizations_history_timestamp_field {
  position: relative;
  display: inline-flex;
  align-items: center;
  max-width: 100%;
}

.organizations_history_timestamp_cell {
  position: relative;
  overflow: visible;
  text-overflow: clip;
  z-index: 4;
}

.organizations_history_timestamp_trigger {
  appearance: none;
  border: 0;
  padding: 0;
  margin: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;
  text-decoration: underline;
  text-decoration-style: dotted;
  text-underline-offset: 2px;
}

.organizations_history_timestamp_trigger:hover,
.organizations_history_timestamp_trigger:focus-visible {
  color: color-mix(in srgb, var(--color-primary, #33b5ff) 78%, var(--text, #e7edf7));
}

.organizations_history_timestamp_trigger:focus-visible {
  outline: 2px solid color-mix(in srgb, var(--color-primary, #33b5ff) 82%, #ffffff);
  outline-offset: 2px;
  border-radius: 4px;
}

.organizations_history_timestamp_popover {
  position: fixed;
  z-index: 2147483000;
  top: calc(100% + 0.38rem);
  right: 0;
  min-width: min(20rem, 68vw);
  max-width: min(24rem, 80vw);
  padding: 0.72rem 0.86rem;
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid color-mix(in srgb, var(--panel-border, #2a2a2a) 92%, #5f6b80 8%);
  background-color: #151a22 !important;
  background-image: none !important;
  backdrop-filter: none !important;
  -webkit-backdrop-filter: none !important;
  box-shadow: 0 14px 30px rgba(0, 0, 0, 0.42);
  opacity: 1;
  mix-blend-mode: normal;
  isolation: isolate;
  backface-visibility: hidden;
  transform: translateZ(0);
}

.organizations_history_timestamp_popover::before {
  content: '';
  position: absolute;
  inset: 0;
  background: #151a22;
  z-index: 0;
}

.organizations_history_timestamp_popover > * {
  position: relative;
  z-index: 1;
}

.organizations_history_timestamp_popover_title {
  margin: 0 0 0.3rem;
  font-size: var(--font-sm, 0.9rem);
  font-weight: 700;
  color: var(--text, #e7edf7);
}

.organizations_history_timestamp_popover_row {
  display: flex;
  justify-content: space-between;
  width: 100%;
  gap: 0.65rem;
  margin-top: 0.2rem;
  font-size: var(--font-md, 0.96rem);
  line-height: 1.28;
}

.organizations_history_timestamp_popover_label {
  color: var(--text-muted, #9ea6b4);
  white-space: nowrap;
}

.organizations_history_timestamp_popover_value {
  color: var(--text, #e7edf7);
  text-align: right;
}

@media (max-width: 720px) {
  .organizations_history_timestamp_popover {
    right: auto;
    left: 0;
    min-width: min(20rem, 82vw);
  }

  .organizations_history_timestamp_popover_row {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.12rem;
  }

  .organizations_history_timestamp_popover_value {
    text-align: left;
  }
}

.organizations_audit_details_popover_container {
  font-size: 0.95rem;
  line-height: 1.5;
}

.organizations_audit_details_popover_field {
  margin-bottom: 0.5rem;
}

.organizations_audit_details_popover_field strong {
  font-weight: 700;
  color: var(--text, #e7edf7);
}

.organizations_audit_details_popover_divider {
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  padding-top: 0.5rem;
  margin-top: 0.75rem;
  margin-bottom: 0.5rem;
}

.organizations_audit_details_popover_details_item {
  font-size: 0.9rem;
  margin-top: 0.25rem;
}

.organizations_audit_grid .datagrid_body {
  min-height: 16rem;
}

.organizations_members_subsection_header {
  margin-bottom: 0;
}

.organizations_members_subsection_header h4 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
}

.organizations_members_import_card {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding: 1.25rem;
  border: 1px solid var(--border, rgba(255, 255, 255, 0.18));
  border-radius: var(--radius-sm, 8px);
  background: color-mix(in srgb, var(--surface, #1f2530) 92%, #5aaef2 8%);
}

.organizations_members_import_card h4 {
  margin: 0;
  font-size: 1.02rem;
}

.organizations_members_import_intro {
  margin: 0;
  line-height: 1.55;
  max-width: 72ch;
}

.organizations_members_import_stepflow {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 0.65rem 0.8rem;
  border-radius: var(--radius-sm, 8px);
  border: 1px solid var(--border, rgba(255, 255, 255, 0.16));
  background: color-mix(in srgb, var(--surface, #1f2530) 95%, #0f1622 5%);
}

.organizations_members_import_stepflow_item {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 4.5rem;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  border: 1px solid color-mix(in srgb, var(--primary, #5aaef2) 70%, transparent);
  background: color-mix(in srgb, var(--primary, #5aaef2) 18%, transparent);
  color: color-mix(in srgb, var(--text, #e7edf7) 88%, #ffffff);
  font-size: var(--font-xs, 0.8rem);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.organizations_members_import_stepflow_sep {
  color: var(--text-muted);
  font-weight: 700;
  font-size: var(--font-sm);
}

.organizations_members_import_layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 1rem;
}

.organizations_members_import_column {
  display: grid;
  gap: 0.6rem;
  padding: 0.95rem;
  border-radius: var(--radius-sm, 8px);
  border: 1px solid var(--border, rgba(255, 255, 255, 0.16));
  background: color-mix(in srgb, var(--surface, #1f2530) 94%, #10151e 6%);
}

.organizations_members_import_output_title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
}

.organizations_members_import_textarea_group {
  display: grid;
  gap: 0.45rem;
  margin: 0;
}

.organizations_members_import_textarea_group label {
  font-size: var(--font-sm);
  font-weight: 600;
}

.organizations_members_import_textarea_group textarea {
  width: 100%;
  min-height: 13rem;
}

.organizations_members_import_summary_card {
  display: grid;
  gap: 0.85rem;
  padding: 1rem;
  border: 1px solid var(--border, rgba(255, 255, 255, 0.16));
  border-radius: var(--radius-sm, 8px);
  background: color-mix(in srgb, var(--surface, #1f2530) 95%, #0c1018 5%);
}

.organizations_members_import_summary_title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
}

.organizations_members_import_metrics {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.55rem;
}

.organizations_members_import_metric {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.65rem;
  padding: 0.5rem 0.6rem;
  border-radius: 6px;
  border: 1px solid var(--border, rgba(255, 255, 255, 0.12));
  background: color-mix(in srgb, var(--surface, #1f2530) 90%, #121927 10%);
  font-size: var(--font-sm);
}

.organizations_members_import_metric_label {
  color: var(--text-muted);
}

.organizations_members_import_metric_value {
  font-weight: 700;
}

.organizations_members_import_summary_hint {
  margin: 0;
  color: var(--text-muted);
  font-size: var(--font-sm);
}

.organizations_members_import_status {
  margin-top: 0;
  padding: 0.65rem 0.75rem;
}

.organizations_members_import_actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

.organizations_members_import_actions_row {
  align-items: center;
}

.organizations_members_import_code_input {
  width: 8.5rem;
  min-width: 7.5rem;
}

.form_status_message {
  font-size: var(--font-sm);
  padding: 0.5rem;
  border-radius: var(--radius-sm, 4px);
  margin: -0.5rem 0 0;
  min-height: 1.25rem;
  display: flex;
  align-items: center;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.form_status_message.is-visible {
  opacity: 1;
}

.form_status_message.success {
  background: rgba(76, 175, 80, 0.15);
  color: #4caf50;
}

.form_status_message.error {
  background: rgba(244, 67, 54, 0.15);
  color: #f44336;
}

@media (max-width: 920px) {
  .organizations_top_columns {
    grid-template-columns: minmax(0, 1fr);
  }

  .organizations_top_column_live {
    position: static;
  }

  .organizations_top_column_live .organizations_stack {
    max-height: none;
  }

  .organizations_members_invites_grid {
    grid-template-columns: 1fr;
  }

  .organizations_members_import_layout {
    grid-template-columns: 1fr;
  }

  .organizations_members_import_actions_row {
    align-items: stretch;
  }

  .organizations_members_import_actions_row .btn,
  .organizations_members_import_code_input {
    width: 100%;
  }

  .organizations_browser_grid_layout {
    grid-template-columns: minmax(0, 1fr);
  }

  .organizations_browser_card {
    grid-template-columns: minmax(0, 1fr);
  }

  .organizations_browser_data_grid {
    grid-template-columns: minmax(0, 1fr);
    row-gap: 0.35rem;
  }

  .organizations_browser_cell_value {
    text-align: left;
  }
}

@media (max-width: 900px) {
  #main:has(#panel-personal-info) > section.panel,
  #panel-personal-info.profile_lead_panel {
    width: 100%;
  }

  .organizations_dual_panels {
    width: 100%;
    grid-template-columns: 1fr;
  }

  .organizations_definitions_grid {
    grid-template-columns: 1fr;
  }

  #panel-personal-info .item_pair .item_label {
    margin-top: 0;
    margin-bottom: 0.2rem;
    padding-top: 0;
  }

  #panel-personal-info .item_pair .item_value {
    margin-left: 0;
  }

  #main:has(#organizations_personal_form) {
    grid-template-columns: 1fr;
    gap: 0.45rem;
  }

  .organizations_intro_panel,
  .organizations_discovery_panel,
  .organizations_request_panel,
  .organizations_member_panel,
  .organizations_global_warning,
  .organizations_personal_panel,
  .organizations_grid_panel {
    width: 100%;
  }

  .organizations_editor_grid {
    grid-template-columns: 1fr;
  }

  .organizations_details_columns {
    grid-template-columns: 1fr;
  }

  .organizations_contact_directory_grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.7rem;
  }

  .organizations_contact_card {
    min-width: 0;
  }

  .organizations_pp_control_strip {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .organizations_editor_pp_controls {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .organizations_editor_card_full {
    grid-column: auto;
  }

  .billing_columns {
    grid-template-columns: 1fr;
    width: 100%;
  }

  #billing_premium_view .billing_columns {
    padding: 1rem;
  }

  #panel-billing {
    padding: 1.2rem 1rem 1.35rem;
  }

  #billing_downgrade_zone {
    width: 100%;
    max-width: none;
  }

  .danger_zone_row {
    grid-template-columns: 1fr;
  }

  .danger_zone_controls {
    justify-items: stretch;
  }

  .danger_confirm_pill {
    flex-wrap: wrap;
    border-radius: 16px;
  }
}

@media (min-width: 901px) and (max-width: 1500px) {
  #main:has(#organizations_personal_form) {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 720px) {
  .organizations_dialog {
    width: 100vw;
    height: 100vh;
  }

  .organizations_dialog_content {
    scroll-snap-type: y proximity;
    overscroll-behavior-y: contain;
    -webkit-overflow-scrolling: touch;
  }

  .organizations_dialog .modal_content {
    padding-left: 0;
    padding-right: 0;
  }

  .organizations_editor_grid {
    gap: 0.6rem;
  }

  .organizations_editor_card,
  .organizations_editor_card_full {
    grid-column: 1 / -1;
    width: 100%;
    margin: 0;
    border-radius: 0;
    border-left: 0;
    border-right: 0;
    padding-left: 0.85rem;
    padding-right: 0.85rem;
    min-height: calc(100dvh - 11.5rem);
    scroll-snap-align: start;
    scroll-snap-stop: always;
  }

  .organizations_editor_panel {
    border-left: 1px solid var(--panel-border, #2a2a2a);
    border-right: 1px solid var(--panel-border, #2a2a2a);
    border-radius: 10px;
  }

  .organizations_details_panel {
    border: 0;
    border-radius: 0;
  }

  .organizations_field_grid {
    grid-template-columns: 1fr;
  }

  .organizations_contact_directory_grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.6rem;
  }

  .organizations_contact_card {
    min-width: 0;
  }

  .organizations_inline_form,
  .organizations_create_actions,
  .organizations_section_header,
  .organizations_stack_row,
  .organizations_dialog_footer {
    grid-template-columns: 1fr;
    flex-direction: column;
    align-items: stretch;
  }

  .organizations_scope_grid {
    grid-template-columns: 1fr;
  }

  .organizations_current_header,
  .organizations_current_actions {
    flex-direction: column;
    align-items: stretch;
  }

  .organizations_current_meta_grid,
  .organizations_current_details_grid {
    grid-template-columns: 1fr;
  }

  .organizations_pp_control_strip {
    grid-template-columns: 1fr;
  }

  .organizations_editor_pp_controls {
    grid-template-columns: 1fr;
  }

  .organizations_pp_control {
    border-left: 0;
    border-top: 1px solid var(--fore-dark, #2a2a2a);
  }

  .organizations_pp_control:first-child {
    border-top: 0;
  }

  .organizations_members_import_metrics {
    grid-template-columns: 1fr;
  }
}

/* ── Currency Finder ─────────────────────────────────────────────────────── */

.currency_finder {
  position: relative;
  display: block;
}

.currency_finder_search {
  width: 100%;
  padding: 0.35rem 0.6rem;
  background: var(--panel-input-bg, var(--color-surface-strong, #1a1a1a));
  color: var(--panel-text, var(--fore, #e0e0e0));
  border: 1px solid var(--panel-border, var(--fore-dark, #2a2a2a));
  border-radius: var(--radius-input, 4px);
  font-size: var(--font-sm, 0.875rem);
  outline: none;
  box-sizing: border-box;
}

.currency_finder_search:focus {
  border-color: var(--color-primary, #4d8ef0);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-primary, #4d8ef0) 25%, transparent);
}

.currency_finder_list {
  position: absolute;
  z-index: 200;
  top: calc(100% + 2px);
  left: 0;
  right: 0;
  margin: 0;
  padding: 0.25rem 0;
  list-style: none;
  background: var(--color-surface-strong, #1e1e1e);
  border: 1px solid var(--panel-border, var(--fore-dark, #2a2a2a));
  border-radius: var(--radius-panel, 6px);
  max-height: 240px;
  overflow-y: auto;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.45);
}

.currency_finder_item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.65rem;
  cursor: pointer;
  font-size: var(--font-sm, 0.875rem);
  color: var(--fore, #e0e0e0);
  border-radius: 3px;
  margin: 0 0.2rem;
}

.currency_finder_item:hover,
.currency_finder_item_active {
  background: var(--hover, rgba(255, 255, 255, 0.07));
  color: var(--fore, #e0e0e0);
}

.currency_finder_code {
  font-family: monospace;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-primary, #4d8ef0);
  min-width: 2.8rem;
  flex-shrink: 0;
}

.currency_finder_symbol {
  font-size: 0.78rem;
  color: var(--fore-muted, var(--text-muted, #888));
  min-width: 1.6rem;
  flex-shrink: 0;
  text-align: center;
}

.currency_finder_name {
  flex: 1;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  color: var(--fore, #e0e0e0);
}

/* ── Timezone Finder ─────────────────────────────────────────────────────── */

.timezone_finder {
  position: relative;
  display: block;
}

.timezone_finder_search {
  width: 100%;
  padding: 0.35rem 0.6rem;
  background: var(--panel-input-bg, var(--color-surface-strong, #1a1a1a));
  color: var(--panel-text, var(--fore, #e0e0e0));
  border: 1px solid var(--panel-border, var(--fore-dark, #2a2a2a));
  border-radius: var(--radius-input, 4px);
  font-size: var(--font-sm, 0.875rem);
  outline: none;
  box-sizing: border-box;
}

.timezone_finder_search:focus {
  border-color: var(--color-primary, #4d8ef0);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-primary, #4d8ef0) 25%, transparent);
}

.timezone_finder_list {
  position: absolute;
  z-index: 200;
  top: calc(100% + 2px);
  left: 0;
  right: 0;
  margin: 0;
  padding: 0.25rem 0;
  list-style: none;
  background: var(--color-surface-strong, #1e1e1e);
  border: 1px solid var(--panel-border, var(--fore-dark, #2a2a2a));
  border-radius: var(--radius-panel, 6px);
  max-height: 240px;
  overflow-y: auto;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.45);
}

.timezone_finder_item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.65rem;
  cursor: pointer;
  font-size: var(--font-sm, 0.875rem);
  color: var(--fore, #e0e0e0);
  border-radius: 3px;
  margin: 0 0.2rem;
}

.timezone_finder_item:hover,
.timezone_finder_item_active {
  background: var(--hover, rgba(255, 255, 255, 0.07));
  color: var(--fore, #e0e0e0);
}

.timezone_finder_name {
  flex: 1;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  color: var(--fore, #e0e0e0);
}

.timezone_finder_offset {
  flex-shrink: 0;
  font-family: monospace;
  font-size: 0.78rem;
  color: var(--color-primary, #4d8ef0);
}

.timezone_finder_abbr {
  flex-shrink: 0;
  font-size: 0.76rem;
  color: var(--fore-muted, var(--text-muted, #888));
  min-width: 3.5rem;
  text-align: right;
}

/* Profile-aligned cadence overrides for organizations page wrapper rhythm. */
#main:has(#organizations-grid) {
  gap: var(--gap-md);
}

#main:has(#organizations-grid) > .organizations_top_columns,
#main:has(#organizations-grid) > section#organizations-hub.panel {
  gap: var(--gap-md);
}

#main:has(#organizations-grid) section.panel .organizations_section_header {
  margin: 0 0 0.6rem;
  align-items: center;
}

#main:has(#organizations-grid) section.panel .organizations_section_header h2 {
  margin: 0;
  line-height: 1.25;
}

#main:has(#organizations-grid) section.panel > .help_text,
#main:has(#organizations-grid) section.panel > p.help_text,
#main:has(#organizations-grid) section.panel > .organizations_hub_callout {
  margin-top: 0.35rem;
  margin-bottom: 0;
}

#main:has(#organizations-grid) .organizations_dual_panels {
  gap: var(--gap-md);
}
