<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
?>
/**
 * PayCal - Settings Page Styles
 * 
 * Extracted from main CSS index.php
 * Date: March 1, 2026
 */

/* SETTINGS PAGE LAYOUT */
#main {
  --settings-selected-radius: 12px;
  display: flex;
  flex-direction: column;
  flex-wrap: nowrap;
  align-items: stretch;
  gap: clamp(1rem, 2vw, 1.6rem);
  width: 100%;
}

/* SETTINGS JUMP NAV */
.settings_jump_nav {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem 0.5rem;
  padding: 0.5rem 0.75rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 8px;
}

.settings_jump_link {
  font-size: 0.8125rem;
  padding: 0.25rem 0.625rem;
  border-radius: 99px;
  border: 1px solid var(--border);
  background: transparent;
  color: var(--text-muted);
  text-decoration: none;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
  white-space: nowrap;
}

.settings_jump_link:hover,
.settings_jump_link:focus-visible {
  background: var(--hover);
  color: var(--text);
  border-color: var(--primary);
  outline: none;
}

.item_pair {
  justify-content: space-between;
  align-items: center;
}

.item_pair .item_label {
  flex: 0 0 30%;
  max-width: 30%;
}

.item_pair .item_value {
  flex: 1 1 auto;
  min-width: 0;
}

.item_pair .item_value input,
.item_pair .item_value select,
.item_pair .item_value textarea {
  width: 100%;
}

#modal_edit_details .recovery_email_input_row {
  display: flex;
  align-items: center;
  gap: var(--gap-sm);
  flex-wrap: nowrap;
}

#modal_edit_details .recovery_email_input_row #recovery_email_input {
  flex: 1 1 auto;
  min-width: 0;
}

#modal_edit_details .recovery_email_input_row #recovery_email_send_btn {
  flex: 0 0 auto;
  margin-top: 0;
  white-space: nowrap;
}

#modal_edit_details .recovery_email_input_row #recovery_email_send_btn.is-working {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
}

#modal_edit_details .recovery_email_input_row #recovery_email_send_btn.is-working::after {
  content: '';
  width: 0.95rem;
  height: 0.95rem;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: settingsBusySpin 700ms linear infinite;
}

/* TWO-COLUMN LAYOUT FOR ACCOUNT DETAILS DIALOG */
.account_details_grid {
  display: flex;
  flex-direction: row;
  gap: var(--gap-lg);
  width: 100%;
}

.details_column {
  flex: 1 1 50%;
  display: flex;
  flex-direction: column;
  gap: var(--gap-md);
  min-width: 0;
}

.details_column.left_column {
  /* Left column gets equal space */
  flex: 1 1 48%;
}

.details_column.right_column {
  /* Right column gets equal space */
  flex: 1 1 48%;
}

#main section.panel form > .flex.f_baseline.w100 > .w25 {
  flex: 0 0 25%;
  max-width: 25%;
}

#main section.panel form > .flex.f_baseline.w100 > .w75 {
  flex: 1 1 0;
  width: auto;
  min-width: 0;
}

#panel-calendar .radio_group,
#panel-style .radio_group {
  justify-content: space-between;
}

#panel-audio .radio_group {
  justify-content: flex-start;
  flex-wrap: wrap;
  gap: var(--gap-xs) var(--gap-sm);
}

#panel-calendar .radio_group .radio + label,
#panel-style .radio_group .radio + label {
  flex: 1;
}

#panel-audio .radio_group .radio + label {
  flex: 0 1 auto;
  white-space: nowrap;
}

#panel-style .radio_group.pill_group,
#panel-calendar .radio_group.pill_group,
#panel-debugging .radio_group.pill_group {
  display: flex;
  flex-wrap: wrap;
  gap: var(--gap-xs);
  padding: 2px;
  border: 1px solid var(--panel-border);
  border-radius: 999px;
  background: color-mix(in srgb, var(--panel-border) 35%, transparent);
}

#panel-style .radio_group.pill_group .radio + label,
#panel-calendar .radio_group.pill_group .radio + label,
#panel-debugging .radio_group.pill_group .radio + label {
  flex: 1 1 0;
  min-width: 7.5rem;
  border: 0;
  border-radius: 999px;
  padding: var(--pad-xs) var(--pad-md);
  font-weight: 600;
  white-space: normal;
  line-height: 1.2;
  text-align: center;
  transition: var(--short-transition) all ease;
}

#panel-style .radio_group.pill_group .radio:hover + label,
#panel-style .radio_group.pill_group .radio:focus + label,
#panel-calendar .radio_group.pill_group .radio:hover + label,
#panel-calendar .radio_group.pill_group .radio:focus + label,
#panel-debugging .radio_group.pill_group .radio:hover + label,
#panel-debugging .radio_group.pill_group .radio:focus + label {
  background-color: color-mix(in srgb, var(--btn-selected-back) 55%, transparent);
  border-color: transparent;
  color: var(--btn-selected-fore, var(--button-text));
}

#panel-style .radio_group.pill_group input[type="radio"]:checked + label,
#panel-style .radio_group.pill_group .radio:active + label,
#panel-calendar .radio_group.pill_group input[type="radio"]:checked + label,
#panel-calendar .radio_group.pill_group .radio:active + label,
#panel-debugging .radio_group.pill_group input[type="radio"]:checked + label,
#panel-debugging .radio_group.pill_group .radio:active + label {
  border-bottom: 0;
  border-color: transparent;
  background-color: var(--btn-selected-back, var(--button-bg-active));
  color: var(--btn-selected-fore, var(--button-text));
}

#panel-calendar .radio_group.calendar_long_pills .radio + label {
  min-width: 12rem;
  text-wrap: balance;
}

#panel-calendar .work_entry_tags {
  justify-content: stretch;
  display: flex;
  flex-wrap: nowrap;
  gap: 0;
  padding: 2px;
  border: 1px solid var(--panel-border);
  border-radius: 999px;
  background: color-mix(in srgb, var(--panel-border) 35%, transparent);
  overflow: hidden;
}

#panel-calendar .work_entry_field + label {
  flex: 1 1 0;
  min-width: 0;
  margin: 0;
  border: 0;
  border-right: 1px solid color-mix(in srgb, var(--panel-border) 70%, transparent);
  border-radius: 0;
  background: transparent;
  text-align: center;
  line-height: 1.2;
  white-space: normal;
  padding: var(--pad-xs) var(--pad-sm);
}

#panel-calendar .work_entry_field + label:last-of-type {
  border-right: 0;
}

#panel-calendar .work_entry_field:hover + label {
  background-color: color-mix(in srgb, var(--btn-selected-back) 55%, transparent);
  color: var(--btn-selected-fore, var(--button-text));
}

#panel-calendar .work_entry_field:checked + label {
  border-bottom: 0;
  border-color: transparent;
  background-color: var(--btn-selected-back, var(--button-bg-active));
  color: var(--btn-selected-fore, var(--button-text));
}

#panel-calendar .work_entry_field:focus + label,
#panel-calendar .work_entry_field:focus-visible + label {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: -2px;
  position: relative;
  z-index: 1;
}

#panel-account .account_actions {
  display: flex;
  flex-direction: column;
  gap: var(--gap-sm);
  width: 100%;
  padding-top: var(--pad-sm);
}

/* Keep Account summary values visually aligned in one clean right edge. */
#panel-account form > .flex.f_baseline.w100 {
  align-items: center;
}

#panel-account form > .flex.f_baseline.w100 > .w75,
#panel-account form > .flex.f_baseline.w100 > .flex.f_baseline.w75 {
  display: flex;
  justify-content: flex-end;
  text-align: right;
}

#panel-account #label_email,
#panel-account #label_full_name,
#panel-account #label_phone,
#panel-account #label_province,
#panel-account #timezone_picker {
  width: 100%;
  text-align: right;
}

#panel-account #label_province:disabled,
#panel-account #timezone_picker:disabled {
  border: 0;
  box-shadow: none;
  background: transparent;
}

#panel-account .account_actions .btn {
  width: 100%;
}

#panel-organizations form {
  display: flex;
  flex-direction: column;
  gap: var(--gap-sm);
}

.organizations_grid {
  display: grid;
  gap: var(--gap-sm);
}

.organizations_block {
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  padding: var(--pad-sm);
  display: flex;
  flex-direction: column;
  gap: var(--gap-xs);
}

.organizations_row {
  display: flex;
  gap: var(--gap-sm);
  align-items: center;
}

.organizations_row_compact input,
.organizations_row_compact select {
  flex: 1 1 auto;
}

.organizations_heading_row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--gap-sm);
}

.organizations_scope_grid {
  margin-top: var(--mar-xs);
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.35rem 0.75rem;
}

.organizations_scope_grid label {
  display: flex;
  gap: 0.4rem;
  align-items: center;
  font-size: var(--font-sm);
}

.organizations_defaults_grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.4rem 0.75rem;
  align-items: center;
}

.organizations_defaults_grid label {
  font-size: var(--font-sm);
}

.organizations_list {
  border: 1px solid var(--panel-border);
  border-radius: 0.5rem;
  min-height: 3.5rem;
  max-height: 13rem;
  overflow: auto;
  padding: 0.45rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.organizations_empty {
  opacity: 0.8;
}

.organizations_invite_row,
.organizations_discovery_row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.5rem;
  border-bottom: 1px solid var(--panel-border);
  padding: 0.35rem 0;
}

.organizations_invite_row:last-child,
.organizations_discovery_row:last-child {
  border-bottom: 0;
}

.organizations_audit_row {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  border-bottom: 1px solid var(--panel-border);
  padding: 0.35rem 0;
}

.organizations_audit_row:last-child {
  border-bottom: 0;
}

.organizations_discovery_actions {
  display: flex;
  gap: 0.35rem;
  align-items: center;
}

.organizations_meta {
  font-size: var(--font-sm);
  opacity: 0.9;
}

.organizations_actions {
  display: flex;
  justify-content: flex-end;
  margin-top: var(--mar-xs);
}

.status_message_error {
  color: #d32f2f;
}

.status_message_muted {
  color: #666;
}

.status_message_info {
  color: #1976d2;
}

.status_message_success {
  color: #388e3c;
}

.recovery_key_status_callout {
  display: none;
  margin-top: var(--gap-xs);
  padding: 0.65rem 0.75rem;
  border-radius: 0.55rem;
  border: 1px solid transparent;
  font-weight: 600;
}

.recovery_key_status_callout.is-visible {
  display: block;
}

.recovery_key_status_callout.is-info {
  color: #0f4f87;
  border-color: #85b8e5;
  background: #e8f3ff;
}

.recovery_key_status_callout.is-success {
  color: #1f5f34;
  border-color: #7bcf9b;
  background: #e9f9ef;
}

.recovery_key_status_callout.is-error {
  color: #7a1f1f;
  border-color: #e5a5a5;
  background: #fff0f0;
}

.security_status_widget {
  border: 0;
  border-radius: 0;
  padding: 0.75rem;
  background: transparent;
}

.security_status_title {
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.security_status_row {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.25rem;
}

.security_status_label {
  opacity: 0.9;
}

.security_status_value {
  font-weight: 600;
}

.security_status_value.is-medium {
  color: #d17b0f;
}

.security_status_value.is-strong {
  color: #2f9d53;
}

.security_status_note {
  margin-top: 0.4rem;
  font-size: 0.92em;
  opacity: 0.92;
}

.security_level_card {
  margin-top: var(--gap-md);
  padding: calc(var(--pad-md) + 0.2rem);
  border: 0;
  border-radius: 0;
  background: transparent;
}

#panel-security > form > .help_text {
  margin: 0 0 var(--mar-sm);
  font-size: var(--font-sm);
  opacity: 0.88;
}

#panel-security .security_level_card {
  margin-top: var(--gap-sm);
  padding: var(--pad-sm) var(--pad-md);
}

.security_level_label {
  display: block;
  margin-bottom: var(--mar-sm);
  font-weight: 600;
}

#panel-security .security_level_label {
  margin-bottom: var(--mar-xs);
}

.security_slider_row {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: var(--gap-sm);
  align-items: center;
}

.security_slider_edge {
  font-size: var(--font-sm);
  opacity: 0.85;
  white-space: nowrap;
}

.security_slider_row_compact {
  grid-template-columns: minmax(3.8rem, auto) minmax(0, 1fr) minmax(3.8rem, auto);
  gap: var(--gap-xs);
}

.security_slider_row_compact .security_slider_edge {
  white-space: normal;
  line-height: 1.2;
}

.security_slider_row_compact .security_slider_edge:first-child {
  text-align: left;
}

.security_slider_row_compact .security_slider_edge:last-child {
  text-align: right;
}

#security_level_slider {
  width: 100%;
}

/* Shared CSS-only styling for settings range sliders. */
.security_slider_row input[type='range'] {
  --slider-track-height: 0.5rem;
  --slider-thumb-size: 1.1rem;
  --slider-track-bg: color-mix(in srgb, var(--panel-border) 48%, var(--panel-bg));
  --slider-fill-bg: var(--color-primary);
  --slider-thumb-bg: #ffffff;
  --slider-thumb-border: color-mix(in srgb, var(--color-primary) 65%, black);
  appearance: none;
  -webkit-appearance: none;
  width: 100%;
  height: var(--slider-thumb-size);
  border-radius: 999px;
  border: 0;
  outline: none;
  cursor: pointer;
  background: linear-gradient(var(--slider-track-bg), var(--slider-track-bg)) 0/100% 100% no-repeat;
  accent-color: var(--slider-fill-bg);
}

.security_slider_row input[type='range']::-webkit-slider-runnable-track {
  height: var(--slider-track-height);
  border-radius: 999px;
  background: transparent;
}

.security_slider_row input[type='range']::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: var(--slider-thumb-size);
  height: var(--slider-thumb-size);
  margin-top: calc((var(--slider-track-height) - var(--slider-thumb-size)) / 2);
  border: 2px solid var(--slider-thumb-border);
  border-radius: 50%;
  background: var(--slider-thumb-bg);
  box-shadow: 0 1px 4px color-mix(in srgb, var(--panel-head-text) 26%, black);
}

.security_slider_row input[type='range']::-moz-range-track {
  height: var(--slider-track-height);
  border-radius: 999px;
  background: var(--slider-track-bg);
}

.security_slider_row input[type='range']::-moz-range-progress {
  height: var(--slider-track-height);
  border-radius: 999px;
  background: var(--slider-fill-bg);
}

.security_slider_row input[type='range']::-moz-range-thumb {
  width: var(--slider-thumb-size);
  height: var(--slider-thumb-size);
  border: 2px solid var(--slider-thumb-border);
  border-radius: 50%;
  background: var(--slider-thumb-bg);
  box-shadow: 0 1px 4px color-mix(in srgb, var(--panel-head-text) 26%, black);
}

.security_slider_row input[type='range']:hover {
  --slider-fill-bg: color-mix(in srgb, var(--color-primary) 82%, white);
}

.security_slider_row input[type='range']:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

.security_slider_row input[type='range']:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.security_level_value {
  margin-top: var(--mar-sm);
  font-weight: 700;
  color: var(--color-primary);
}

#panel-security .security_level_value {
  margin-top: var(--mar-xs);
}

#panel-security #security_level_hint,
#panel-security #emergency_signout_hint {
  margin-top: var(--mar-xs);
  margin-bottom: 0;
  font-size: var(--font-sm);
}

.security_timeouts_table_wrap,
.security_advanced_table_wrap {
  margin-top: var(--gap-md);
}

.security_datagrid {
  width: 100%;
  border: 0;
  border-radius: 0;
  overflow: hidden;
}

.security_datagrid_table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}

.security_datagrid_table .security_col_activity {
  width: 42%;
}

.security_datagrid_table .security_col_timeout {
  width: 28%;
}

.security_datagrid_table .security_col_session {
  width: 30%;
}

.security_datagrid_row {
  border-top: 1px solid var(--panel-border);
}

.security_datagrid_row:first-child {
  border-top: 0;
}

.security_datagrid_table th,
.security_datagrid_table td {
  padding: 0.7rem 0.8rem;
  text-align: left;
  border-top: 1px solid var(--panel-border);
  vertical-align: middle;
}

#panel-security .security_datagrid_table th,
#panel-security .security_datagrid_table td {
  padding: 0.55rem 0.65rem;
}

.security_datagrid_table thead th {
  border-top: 0;
}

.security_datagrid_3col .security_datagrid_row {
  grid-template-columns: 1.35fr 0.9fr 1fr;
}

.security_datagrid_2col .security_datagrid_row {
  grid-template-columns: 1.35fr 1fr;
}

.security_datagrid_header {
  font-weight: 700;
  background: var(--back-light, rgba(255, 255, 255, 0.04));
}

.security_datagrid_row:hover {
  border-color: var(--panel-border);
}

.security_datagrid_table tbody tr.security_datagrid_row:hover th,
.security_datagrid_table tbody tr.security_datagrid_row:hover td {
  background: var(--back-light, rgba(255, 255, 255, 0.04));
}

.security_advanced {
  margin-top: var(--gap-lg);
}

.security_advanced > summary {
  cursor: pointer;
  font-weight: 600;
  user-select: none;
}

.security_advanced select {
  width: 100%;
}

#panel-security form {
  display: flex;
  flex-direction: column;
  gap: var(--gap-sm);
}

#panel-security .help_text {
  margin-top: var(--mar-xs);
  margin-bottom: var(--mar-sm);
}

#panel-debugging > form > .help_text {
  margin-top: 0;
  margin-bottom: var(--mar-xs);
}

#panel-debugging > form > .help_text:last-of-type {
  margin-bottom: var(--mar-lg);
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

.security_passkey_actions {
  margin-top: 0.6rem;
  display: flex;
  justify-content: center;
}

.security_passkey_actions #add_passkey_button.is-working {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
}

.security_passkey_actions #add_passkey_button.is-working::after {
  content: '';
  width: 0.95rem;
  height: 0.95rem;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: settingsBusySpin 700ms linear infinite;
}

.passkey_action_status {
  margin-top: 0.45rem;
  text-align: center;
  min-height: 1.25rem;
}

@keyframes settingsBusySpin {
  to {
    transform: rotate(360deg);
  }
}

.passkey_credentials_list {
  margin-top: 0.6rem;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.passkey_datagrid {
  width: 100%;
  border: 1px solid var(--fore-dark, #2b2b2b);
  border-radius: 8px;
  overflow: hidden;
  background: transparent;
}

.passkey_datagrid.datagrid_no_chrome {
  border: 0;
  border-radius: 0;
  overflow: visible;
}

.passkey_datagrid_row {
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  gap: 0.5rem;
  align-items: center;
  padding: 0.5rem 0.6rem;
  border-top: 1px solid var(--fore-dark, #2b2b2b);
  background: transparent;
}

.passkey_datagrid_row:hover {
  border-color: var(--button-border-active);
}

.passkey_datagrid_3col .passkey_datagrid_row {
  grid-template-columns: 1.3fr 1fr auto;
}

.passkey_datagrid_row:first-child {
  border-top: 0;
}

.passkey_datagrid.datagrid_no_chrome .passkey_datagrid_row {
  border-top: 0;
  padding-left: 0;
  padding-right: 0;
}

.passkey_datagrid.datagrid_no_chrome .passkey_datagrid_header {
  margin-bottom: 0.2rem;
  opacity: 0.85;
}

.passkey_datagrid_header {
  font-weight: 700;
  background: transparent;
}

.passkey_credential_name {
  font-weight: 600;
  cursor: text;
  padding: 0.15rem 0.3rem;
  border-radius: 4px;
  transition: background-color 0.15s ease;
}

.passkey_credential_name:hover {
  background-color: var(--back-light, rgba(255, 255, 255, 0.04));
}

.passkey_credential_name:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 1px;
  background-color: var(--back-light, rgba(255, 255, 255, 0.06));
}

.passkey_credential_detail {
  font-size: 0.88em;
  opacity: 0.85;
}

.section_separator {
  border: 0;
  border-top: 1px solid var(--fore-dark, #2b2b2b);
  margin: 1.5rem 0;
  opacity: 0.3;
}

.details_inset_section {
  margin-top: var(--gap-md);
  padding: var(--pad-sm);
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  background: var(--back-light, rgba(255, 255, 255, 0.04));
}

.modal_post_footer_sections {
  margin-top: var(--gap-sm);
  padding-top: var(--pad-sm);
  border-top: 1px solid var(--panel-border);
}

.modal_post_footer_sections .details_inset_section:first-child {
  margin-top: 0;
}

.details_inset_title {
  margin: 0 0 0.35rem;
  font-size: 1rem;
}

.details_inset_text {
  margin: 0;
  opacity: 0.92;
}

.details_inset_actions {
  margin-top: var(--gap-sm);
}

.details_inset_actions .btn {
  width: 100%;
}

.details_inset_danger {
  border-color: color-mix(in srgb, var(--color-red, #a62929) 45%, var(--panel-border));
}

#panel-data-portability .data_portability_grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--gap-md);
}

#panel-data-portability .data_portability_warning {
  margin: 0 0 var(--gap-sm) 0;
  padding: var(--pad-sm);
  border: 1px solid color-mix(in srgb, var(--color-red, #a62929) 45%, var(--panel-border));
  border-radius: var(--border-radius);
  background: color-mix(in srgb, var(--color-red, #a62929) 12%, var(--panel-bg));
}

#panel-data-portability .data_portability_column {
  padding: var(--pad-sm);
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  background: color-mix(in srgb, var(--panel-bg) 92%, var(--panel-border));
}

#panel-data-portability .data_portability_actions_row {
  display: flex;
  flex-wrap: wrap;
  gap: var(--gap-xs);
  margin-bottom: var(--gap-sm);
}

#panel-data-portability .data_portability_meta {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin-bottom: var(--gap-sm);
  font-size: 0.9rem;
}

#panel-data-portability .data_portability_textarea {
  width: 100%;
  min-height: 12rem;
  resize: vertical;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.85rem;
}

#panel-data-portability .data_portability_log_section {
  margin-top: var(--gap-md);
  padding: var(--pad-sm);
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  background: color-mix(in srgb, var(--panel-bg) 94%, var(--panel-border));
}

#panel-data-portability .data_portability_action_log {
  margin: 0;
  padding-left: 1.2rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  max-height: 14rem;
  overflow: auto;
}

#panel-data-portability .data_portability_action_log li {
  font-size: 0.9rem;
  line-height: 1.3;
}

#panel-data-portability .status_message {
  margin-bottom: var(--gap-sm);
}

.email_change_link {
  display: inline-block;
  margin-left: var(--gap-sm);
  padding: 0.25rem 0.5rem;
  border: none;
  border-radius: 3px;
  font-size: var(--font-sm);
  font-family: inherit;
  text-decoration: none;
  white-space: nowrap;
  color: var(--link-color, var(--fore));
  background: transparent;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.email_change_link:hover {
  background-color: var(--btn-selected-back, rgba(255, 255, 255, 0.1));
}

.email_change_link_disabled {
  display: inline-block;
  margin-left: var(--gap-sm);
  padding: 0.25rem 0.5rem;
  border-radius: 3px;
  font-size: var(--font-sm);
  white-space: nowrap;
  color: var(--fore-muted, rgba(128, 128, 128, 0.6));
  cursor: not-allowed;
  opacity: 0.6;
}

.compact_hint {
  font-size: 0.85em;
  color: #666;
  margin-bottom: 0.5rem;
}

.code_input {
  max-width: 150px;
}

.mt_8 {
  margin-top: 8px;
}

#main > section.panel {
  width: min(80vw, 1240px);
  margin-left: auto;
  margin-right: auto;
  padding: clamp(1rem, 2vw, 1.5rem);
  flex: 0 0 auto;
  min-width: 0;
}

/* Tablet and down: hide sidebar, let panels reflow based on available width */
@media (max-width: 900px) {
  #main > section.panel {
    width: min(92vw, 1240px);
    min-width: 0;
    padding: clamp(0.9rem, 1.9vw, 1.35rem);
  }

  /* Transition to single-column for account details grid */
  .account_details_grid {
    flex-direction: column;
    gap: var(--gap-md);
  }

  .details_column {
    flex: 0 0 100%;
    width: 100%;
  }

  .details_column.left_column,
  .details_column.right_column {
    flex: 0 0 100%;
  }

  #main section.panel form > .flex.f_baseline.w100 {
    flex-wrap: wrap;
    align-items: flex-start;
  }

  #main section.panel form > .flex.f_baseline.w100 > .w25,
  #main section.panel form > .flex.f_baseline.w100 > .w75 {
    flex: 1 1 100%;
    max-width: 100%;
    width: 100%;
  }

  #main section.panel form > .flex.f_baseline.w100 > .w75 {
    margin-left: var(--mar-lg);
    width: calc(100% - var(--mar-lg));
    max-width: calc(100% - var(--mar-lg));
  }

  #main section.panel form > .flex.f_baseline.w100 > .w25 {
    margin-top: var(--mar-xs);
  }

  .item_pair {
    flex-direction: column;
    align-items: stretch;
  }

  .item_pair .item_label {
    flex: 0 0 auto;
    max-width: 100%;
    width: 100%;
    text-align: left;
    margin-top: var(--mar-xs);
  }

  .item_pair .item_value {
    width: 100%;
    margin-left: var(--mar-lg);
  }

  #panel-calendar .radio_group,
  #panel-style .radio_group,
  #panel-audio .radio_group,
  #panel-calendar .work_entry_tags {
    flex-wrap: wrap;
    gap: var(--gap-sm);
  }

  #panel-calendar .radio_group .radio + label,
  #panel-style .radio_group .radio + label,
  #panel-audio .radio_group .radio + label,
  #panel-calendar .work_entry_field + label {
    white-space: nowrap;
  }

  #panel-calendar .radio_group .radio + label,
  #panel-style .radio_group .radio + label,
  #panel-audio .radio_group .radio + label {
    flex: 1 1 8.5rem;
  }

  #panel-style .radio_group.pill_group,
  #panel-debugging .radio_group.pill_group {
    flex-wrap: nowrap;
  }

  #panel-style .radio_group.pill_group .radio + label,
  #panel-calendar .radio_group.pill_group .radio + label,
  #panel-debugging .radio_group.pill_group .radio + label {
    flex: 1 1 0;
    white-space: normal;
    line-height: 1.2;
  }

  #panel-calendar .radio_group.pill_group {
    flex-wrap: wrap;
  }

  #panel-calendar .radio_group.pill_group .radio + label {
    min-width: 8rem;
  }

  #panel-calendar .radio_group.calendar_long_pills .radio + label {
    flex: 1 1 100%;
    min-width: 0;
  }

  #panel-calendar .work_entry_tags {
    gap: var(--gap-xs);
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    overflow: visible;
  }

  #panel-calendar .work_entry_field + label {
    flex: 1 1 calc(50% - var(--gap-xs));
    min-width: 9rem;
    border: 0;
    border-radius: 999px;
    text-align: center;
    white-space: normal;
    line-height: 1.2;
  }

  .organizations_row {
    flex-wrap: wrap;
  }

  .organizations_row_compact .btn {
    width: 100%;
  }

  .organizations_discovery_row {
    flex-direction: column;
    align-items: stretch;
  }

  .organizations_discovery_actions {
    width: 100%;
  }

  .organizations_discovery_actions .btn {
    width: 100%;
  }

  .organizations_scope_grid,
  .organizations_defaults_grid {
    grid-template-columns: 1fr;
  }

  #panel-data-portability .data_portability_grid {
    grid-template-columns: 1fr;
  }
}

/* Mobile: single-column panels and stacked label/value rows */
@media (max-width: 768px) {
  #main > section.panel {
    width: 100%;
    min-width: 0;
    padding: clamp(0.85rem, 1.8vw, 1.1rem);
  }

  /* Hide tab list on mobile */
  /* Ensure tab content takes full width on mobile */
  #main dialog {
    width: 90vw;
    height: auto;
    max-height: 80dvh;
  }

  /* Collapse 2-column layout to single column on mobile */
  .account_details_grid {
    flex-direction: column;
    gap: var(--gap-md);
  }

  .details_column {
    flex: 0 0 100%;
    width: 100%;
  }

  .details_column.left_column,
  .details_column.right_column {
    flex: 0 0 100%;
  }

  .security_slider_row {
    grid-template-columns: 1fr;
    gap: var(--gap-xs);
  }

  .security_slider_edge {
    text-align: center;
  }
}

/* Utility Classes for Display Management */

/* PAY PERIOD PREVIEW */
.pay_period_control_bar {
  display: grid;
  grid-template-columns: 1.3fr 1fr 1fr 1fr;
  gap: var(--gap-sm);
  padding: var(--gap-sm) 0;
  border-top: 1px solid var(--fore-dark, #333);
  border-bottom: 1px solid var(--fore-dark, #333);
}

.pay_period_control label {
  display: block;
  font-size: var(--font-sm);
  margin-bottom: 0.25rem;
}

.pay_period_preview_block {
  margin-top: var(--gap-sm);
}

.pay_period_preview_calendar {
  width: 100%;
}

.pay_period_preview_compact {
  margin-top: var(--gap-sm);
}

.pp_three_week {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
  margin-bottom: var(--gap-sm);
  font-size: 0.72rem;
}

.pp_three_week th,
.pp_three_week td {
  border: 1px solid var(--fore-dark, #2a2a2a);
  text-align: center;
  padding: 0.2rem 0.1rem;
  position: relative;
}

.pp_day_head {
  background: var(--btn-selected-back, rgba(255, 255, 255, 0.08));
  font-weight: 600;
}

.pp_day_cell {
  background: var(--back-light, rgba(255, 255, 255, 0.04));
}

.pp_month_label {
  text-align: center;
  font-size: var(--font-sm);
  margin-bottom: 0.25rem;
}

.pp_in_period {
  border-top-color: #171717;
  border-bottom-color: #171717;
}

.pp_in_p1 {
  background: rgba(47, 125, 50, 0.32);
}

.pp_in_p2 {
  background: rgba(62, 116, 182, 0.30);
}

.pp_ribbon_start_p1,
.pp_ribbon_start_p2 {
  border-left: 2px solid #171717;
  border-top-left-radius: 9px;
  border-bottom-left-radius: 9px;
}

.pp_ribbon_end_p1,
.pp_ribbon_end_p2 {
  border-right: 2px solid #171717;
  border-top-right-radius: 9px;
  border-bottom-right-radius: 9px;
}

.pp_badge {
  position: absolute;
  top: 1px;
  left: 3px;
  font-size: 0.58rem;
  color: #111;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 8px;
  padding: 0 0.3rem;
}

.pp_today {
  outline: 1px solid var(--fore);
}

.pp_grace_day {
  border-style: dashed;
  border-width: 2px;
}

.pp_grace_day + .pp_grace_day {
  border-left-width: 0;
}

.pp_grace_1 {
  border-color: #2ecb5f;
}

.pp_grace_2 {
  border-color: #ffd43b;
}

.pp_grace_3 {
  border-color: #ff4d4f;
}

.pp_grace_p1 {
  box-shadow: inset 0 0 0 1px rgba(47, 125, 50, 0.85);
}

.pp_grace_p2 {
  box-shadow: inset 0 0 0 1px rgba(62, 116, 182, 0.9);
}

@media (max-width: 880px) {
  .pay_period_control_bar {
    grid-template-columns: 1fr 1fr;
  }
}

#panel-audio h2 {
  text-align: left;
}

#voice_picker {
  position: relative;
}

#voice_picker .voice_picker_disabled_hint {
  display: none;
  margin-top: var(--gap-xs);
  font-size: var(--font-sm);
  color: var(--fore-muted, rgba(128, 128, 128, 0.85));
}

#voice_picker.is-disabled {
  opacity: 0.5;
  filter: grayscale(0.85);
}

#voice_picker.is-disabled,
#voice_picker.is-disabled * {
  cursor: not-allowed;
}

#voice_picker.is-disabled .radio_group .radio + label {
  border-style: dashed;
  opacity: 0.75;
}

#voice_picker.is-disabled .radio_group .radio + label:hover {
  background: transparent;
  box-shadow: none;
  transform: none;
}

#voice_picker.is-disabled .voice_picker_disabled_hint {
  display: block;
}

#main .radio_group .radio:active + label,
#main .radio_group input[type="radio"]:checked + label {
  border-radius: var(--settings-selected-radius);
}

#panel-account-activity .help_text {
  margin-top: 0;
}

.account_activity_grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--gap-md);
}

.account_activity_card {
  border: 1px solid var(--line, rgba(255, 255, 255, 0.12));
  border-radius: 12px;
  padding: var(--gap-sm);
  background: var(--back-light, rgba(255, 255, 255, 0.03));
}

.account_activity_card h3 {
  margin: 0 0 var(--gap-sm) 0;
  font-size: var(--font-md);
}

.account_activity_card_sessions {
  grid-column: 1 / -1;
}

.account_activity_list {
  display: grid;
  grid-template-columns: minmax(160px, 220px) 1fr;
  column-gap: var(--gap-sm);
  row-gap: 0.35rem;
  margin: 0;
}

.account_activity_list dt {
  color: var(--fore-muted, rgba(200, 200, 200, 0.85));
  font-size: var(--font-sm);
}

.account_activity_list dd {
  margin: 0;
  word-break: break-word;
}

.account_activity_sessions {
  display: grid;
  gap: var(--gap-sm);
}

.account_activity_session_item {
  border: 1px solid var(--line, rgba(255, 255, 255, 0.1));
  border-radius: 10px;
  padding: 0.6rem 0.75rem;
  background: rgba(255, 255, 255, 0.02);
}

.account_activity_session_item_current {
  border-color: rgba(64, 201, 132, 0.7);
  box-shadow: inset 0 0 0 1px rgba(64, 201, 132, 0.35);
}

.account_activity_session_meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem 0.75rem;
  font-size: var(--font-sm);
  color: var(--fore-muted, rgba(200, 200, 200, 0.85));
}

.account_activity_session_meta_segment {
  display: inline-flex;
  align-items: baseline;
  gap: 0.2rem;
}

.account_activity_session_meta_label {
  color: var(--fore-muted, rgba(200, 200, 200, 0.85));
}

.account_activity_timestamp_field {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.account_activity_timestamp_trigger {
  border: 0;
  background: transparent;
  color: inherit;
  text-decoration: underline;
  text-decoration-style: dotted;
  text-underline-offset: 0.14rem;
  cursor: pointer;
  padding: 0;
  font: inherit;
}

.account_activity_timestamp_trigger:hover,
.account_activity_timestamp_trigger:focus-visible {
  color: var(--fore, #fff);
}

.account_activity_timestamp_trigger:focus-visible {
  outline: 2px solid var(--line-focus, rgba(86, 180, 255, 0.9));
  outline-offset: 2px;
  border-radius: 4px;
}

.account_activity_timestamp_popover {
  position: fixed;
  z-index: 1001;
  min-width: 220px;
  max-width: min(92vw, 340px);
  padding: 0.55rem 0.65rem;
  border-radius: 10px;
  border: 1px solid var(--line, rgba(255, 255, 255, 0.2));
  background: var(--back, rgba(12, 16, 24, 0.98));
  color: var(--fore, #f5f7fb);
  box-shadow: 0 16px 36px rgba(0, 0, 0, 0.34);
  pointer-events: auto;
}

.account_activity_timestamp_popover_row {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 0.4rem 0.6rem;
  align-items: baseline;
  font-size: var(--font-sm);
  line-height: 1.45;
}

.account_activity_timestamp_popover_row + .account_activity_timestamp_popover_row {
  margin-top: 0.25rem;
}

.account_activity_timestamp_popover_label {
  color: var(--fore-muted, rgba(200, 200, 200, 0.9));
  white-space: nowrap;
}

.account_activity_timestamp_popover_value {
  color: var(--fore, #f5f7fb);
  font-variant-numeric: tabular-nums;
}

/* Profile uses organizations finder JS, so mirror finder styles here. */
.currency_finder,
.timezone_finder {
  position: relative;
  display: block;
}

.currency_finder[aria-expanded="true"],
.timezone_finder[aria-expanded="true"] {
  z-index: 1502;
}

.currency_finder_search,
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

.currency_finder_search:focus,
.timezone_finder_search:focus {
  border-color: var(--color-primary, #4d8ef0);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-primary, #4d8ef0) 25%, transparent);
}

.currency_finder_list,
.timezone_finder_list {
  position: absolute;
  z-index: 1503;
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

.currency_finder_item,
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
  list-style: none;
}

.currency_finder_item:hover,
.currency_finder_item_active,
.timezone_finder_item:hover,
.timezone_finder_item_active {
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

.currency_finder_name,
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

@media (max-width: 880px) {
  .account_activity_grid {
    grid-template-columns: 1fr;
  }

  .account_activity_card_sessions {
    grid-column: auto;
  }

  .account_activity_list {
    grid-template-columns: 1fr;
  }
}
