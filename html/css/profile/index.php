<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>

/* Profile page panel layout */
#main:has(#panel-billing) {
  display: flex;
  flex-direction: column;
  gap: clamp(1rem, 2vw, 1.6rem);
}

#main:has(#panel-billing) > section.panel {
  width: min(80vw, 1240px);
  margin-left: auto;
  margin-right: auto;
  padding: clamp(1rem, 2vw, 1.5rem);
}

/* Billing first-paint guardrails */
#panel-billing[data-billing-hint="premium"] #billing_free_view {
  display: none;
}

#panel-billing[data-billing-hint="free"] #billing_premium_view {
  display: none;
}

#panel-billing[data-billing-hydrated="false"] #billing_free_view,
#panel-billing[data-billing-hydrated="false"] #billing_premium_view {
  display: none;
}

/* Personal Info layout */
#panel-personal-info .profile_personal_info_grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem 1.25rem;
  align-items: start;
}

#panel-personal-info .item_pair {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-start;
  gap: 0.4rem 1rem;
  align-items: center;
  margin: 0;
  padding: 0.08rem 0;
  min-width: 0;
}

#panel-personal-info .item_label {
  display: flex;
  align-items: center;
  line-height: 1.3;
  padding-top: 0;
  margin: 0;
  font-weight: 700;
  align-self: center;
  text-align: right;
  flex: 0 1 8.5rem;
  min-width: 6.5rem;
  min-height: 2.3rem;
  white-space: normal;
  overflow-wrap: anywhere;
}

#panel-personal-info .item_value {
  display: grid;
  gap: 0.25rem;
  width: 100%;
  max-width: 25rem;
  flex: 1 1 14rem;
  min-width: 0;
}

#panel-personal-info {
  position: relative;
}

#panel-personal-info:has(.currency_finder[aria-expanded="true"]),
#panel-personal-info:has(.timezone_finder[aria-expanded="true"]) {
  z-index: 1600;
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

#panel-personal-info #edit_details_email:read-only {
  cursor: pointer;
  opacity: 1;
  transition: border-color 140ms ease, background-color 140ms ease, box-shadow 140ms ease;
}

#panel-personal-info #edit_details_email:read-only:hover {
  border-color: color-mix(in srgb, var(--theme-signature-color, #3fa8ff) 60%, var(--input-border, #3a3a3a));
  background: color-mix(in srgb, var(--theme-signature-color, #3fa8ff) 10%, var(--panel-input-bg, #20262f));
  box-shadow: 0 0 0 1px color-mix(in srgb, var(--theme-signature-color, #3fa8ff) 24%, transparent);
}

#panel-personal-info .item_value .status_text {
  min-height: 1rem;
  margin: 0.05rem 0 0;
  line-height: 1.2;
}

#panel-personal-info .profile_personal_info_actions {
  grid-column: 1 / -1;
}

/* Pay period control strip */
#panel-pay-period .organizations_pp_control_strip {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0;
}

#panel-pay-period .organizations_pp_control {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  padding: 0.55rem 0.65rem 0.65rem;
  border-left: 1px solid var(--fore-dark, #2a2a2a);
  background: var(--back-light, rgba(255, 255, 255, 0.04));
}

#panel-pay-period .organizations_pp_control:first-child {
  border-left: 0;
}

#panel-pay-period .organizations_pp_control label,
#panel-pay-period .organizations_pp_control_label {
  display: block;
  text-align: center;
  font-size: var(--font-sm);
  font-weight: 700;
}

#panel-pay-period .organizations_pp_control select,
#panel-pay-period .organizations_pp_control input {
  width: 100%;
}

#panel-pay-period .organizations_grace_radio_group {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-start;
  gap: 0.35rem;
}

#panel-pay-period .organizations_grace_radio_group .radio + label {
  flex: 1 1 5.5rem;
  padding: 0.45rem 0.35rem;
  border-left: 0;
  font-size: var(--font-sm);
  white-space: nowrap;
}

#panel-pay-period #organizations_personal_form .pay_period_preview_compact {
  margin-top: var(--mar-sm, 0.8rem);
}

#panel-pay-period #organizations_personal_preview.organizations_preview_box {
  border: 0;
  background: transparent;
  padding: 0;
  min-height: 0;
}

#panel-pay-period #organizations_personal_form .pp_three_week {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
  margin: 0 0 var(--mar-xs, 0.55rem);
  font-size: 0.74rem;
}

#panel-pay-period #organizations_personal_form .pp_stripbar {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 0;
  margin-bottom: 0.4rem;
}

#panel-pay-period #organizations_personal_form .pp_day_head {
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  border: 1px solid var(--fore-dark, #2a2a2a);
  border-left-width: 0;
  background: var(--btn-selected-back, rgba(255, 255, 255, 0.08));
  font-weight: 700;
  padding: 0.3rem 0.15rem;
}

#panel-pay-period #organizations_personal_form .pp_day_head:first-child {
  border-left-width: 1px;
}

#panel-pay-period #organizations_personal_form .pp_three_week th,
#panel-pay-period #organizations_personal_form .pp_three_week td {
  border: 1px solid var(--fore-dark, #2a2a2a);
  text-align: center;
  vertical-align: middle;
  padding: 0.24rem 0.12rem;
  position: relative;
}

#panel-pay-period #organizations_personal_form .pp_month_label {
  text-align: center;
  font-size: var(--font-lg, 1.2rem);
  font-weight: 700;
  margin: 0.6rem 0 1rem;
  padding: 0.3rem 0;
}

#panel-pay-period #organizations_personal_form .pp_preview_summary {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1.9rem;
  flex-wrap: wrap;
  text-align: center;
  font-size: var(--font-sm);
  margin-top: 1rem;
  padding: 0.35rem 0;
}

#panel-pay-period #organizations_personal_form .pp_preview_summary_item {
  white-space: nowrap;
  padding: 0.15rem 0.4rem;
}

/* Subscription layout */
#panel-billing .organizations_section_header h2 {
  margin: 0;
}

#panel-billing > .help_text {
  text-align: center;
  margin: 0.35rem 0 0;
}

#panel-billing .billing_shell {
  margin-top: 0.75rem;
}

#panel-billing .billing_columns {
  display: grid;
  grid-template-columns: 1.1fr 1fr;
  gap: clamp(0.9rem, 1.7vw, 1.5rem);
  align-items: start;
}

#panel-billing .billing_column {
  display: grid;
  gap: 0.6rem;
}

#panel-billing .billing_column_main {
  justify-items: start;
}

#panel-billing .billing_plan_value .billing_member_since {
  font-weight: 400;
  opacity: 0.78;
}

#panel-billing .billing_renewal_date {
  margin: -0.3rem 0 0;
  font-size: 0.88rem;
  opacity: 0.72;
}

#panel-billing .billing_datetime_anchor {
  position: relative;
  display: inline-flex;
  align-items: center;
}

#panel-billing .billing_datetime_trigger {
  appearance: none;
  border: 1px solid color-mix(in srgb, var(--color-border, var(--border, #2a2a2a)) 78%, transparent);
  border-radius: var(--radius-control, 8px);
  padding: 0.1rem 0.42rem;
  line-height: 1.2;
  background: color-mix(in srgb, var(--color-surface, var(--panel-bg, #151515)) 92%, transparent);
  color: var(--color-text, var(--text, #f5f5f5));
  cursor: pointer;
  font: inherit;
}

#panel-billing .billing_datetime_trigger:hover {
  background: color-mix(in srgb, var(--color-hover, #2a2a2a) 72%, var(--color-surface, var(--panel-bg, #151515)));
}

#panel-billing .billing_datetime_trigger:focus-visible {
  outline: 3px solid var(--color-focus-ring, #80deea);
  outline-offset: 2px;
}

#panel-billing .billing_datetime_popover {
  position: absolute;
  top: calc(100% + 0.45rem);
  left: 0;
  z-index: 25;
  min-width: min(22rem, calc(100vw - 2rem));
  max-width: min(30rem, calc(100vw - 2rem));
  border-radius: var(--radius-dialog, 10px);
  border: 1px solid var(--color-border, var(--border, #2a2a2a));
  background: var(--dialog-bg, var(--panel-bg, #151515));
  color: var(--dialog-text, var(--color-text, var(--text, #f5f5f5)));
  box-shadow: var(--dialog-shadow, var(--shadow-md, 0 8px 18px rgba(0, 0, 0, 0.28)));
  padding: 0.65rem 0.75rem;
  display: grid;
  gap: 0.55rem;
}

#panel-billing .billing_datetime_popover_title {
  font-size: 0.84rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  color: var(--color-text-muted, var(--text-muted, #b6c2ca));
}

#panel-billing .billing_datetime_popover_rows {
  display: grid;
  gap: 0.42rem;
}

#panel-billing .billing_datetime_popover_row {
  display: grid;
  grid-template-columns: minmax(7.8rem, auto) 1fr;
  gap: 0.5rem;
  align-items: start;
  font-size: 0.83rem;
}

#panel-billing .billing_datetime_popover_label {
  color: var(--color-text-muted, var(--text-muted, #b6c2ca));
  font-weight: 600;
}

#panel-billing .billing_datetime_popover_value {
  color: var(--color-text, var(--text, #f5f5f5));
  word-break: break-word;
}

@media (max-width: 768px) {
  #panel-billing .billing_datetime_popover {
    left: auto;
    right: 0;
  }
}

#panel-billing .billing_column_side {
  justify-items: center;
  text-align: center;
}

#panel-billing .billing_column h3 {
  margin: 0.1rem 0 0.2rem;
}

/* Subscription benefits: two-column, centered, custom bullets */
#panel-billing .billing_value_list {
  margin: 0;
  padding: 0;
  list-style: none;
  display: grid;
  grid-template-columns: repeat(2, minmax(13.5rem, 1fr));
  gap: 0.65rem 1rem;
  width: 100%;
}

#panel-billing .billing_value_list li {
  margin: 0;
  display: grid;
  grid-template-columns: 0.8rem minmax(0, 1fr);
  align-items: start;
  gap: 0.6rem;
  text-align: left;
}

#panel-billing .billing_value_list li::before {
  content: "";
  width: 0.46rem;
  height: 0.46rem;
  margin-top: 0.35rem;
  border-radius: 2px;
  transform: rotate(45deg);
  background: color-mix(in srgb, var(--theme-signature-color, #3fa8ff) 78%, #ffffff);
  box-shadow: 0 0 0 1px color-mix(in srgb, var(--theme-signature-color, #3fa8ff) 44%, transparent);
}

#panel-billing .billing_value_list li > span {
  display: block;
  line-height: 1.45;
}

/* CTA link */
#panel-billing .billing_organizations_link {
  margin: 0.6rem 0 0;
}

#panel-billing .billing_organizations_link a {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 0.85rem;
  border-radius: 8px;
  border: 1px solid color-mix(in srgb, var(--theme-signature-color, #3fa8ff) 50%, var(--border, #2a2a2a));
  background: color-mix(in srgb, var(--theme-signature-color, #3fa8ff) 14%, var(--panel-bg, #151515));
  color: var(--text, #f5f5f5);
  font-weight: 700;
  text-decoration: none;
  transition: background-color 140ms ease, border-color 140ms ease, transform 140ms ease, box-shadow 140ms ease;
}

#panel-billing .billing_organizations_link a:hover,
#panel-billing .billing_organizations_link a:focus-visible {
  background: color-mix(in srgb, var(--theme-signature-color, #3fa8ff) 24%, var(--panel-bg, #151515));
  border-color: color-mix(in srgb, var(--theme-signature-color, #3fa8ff) 72%, #9ccc65);
  box-shadow: 0 8px 18px color-mix(in srgb, var(--theme-signature-color, #3fa8ff) 20%, transparent);
  transform: translateY(-1px);
}

#panel-billing .billing_organizations_link a:focus-visible {
  outline: 3px solid color-mix(in srgb, var(--theme-signature-color, #3fa8ff) 52%, #ffffff);
  outline-offset: 2px;
}

/* Downgrade zone */
#billing_downgrade_zone {
  margin-top: 1.2rem;
  padding-top: 1rem;
  border-top: 1px solid color-mix(in srgb, #ff4f4f 28%, var(--border, #2a2a2a));
  display: grid;
  gap: 0.6rem;
}

#billing_downgrade_zone > .help_text {
  text-align: center;
  font-size: 0.88rem;
  opacity: 0.82;
}

#billing_downgrade_zone .danger_confirm_pill {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  justify-content: center;
  flex-wrap: wrap;
}

#billing_downgrade_zone .danger_confirm_pill > span {
  white-space: nowrap;
}

#billing_downgrade_zone .danger_confirm_pill input[type="text"] {
  flex: 0 1 22rem;
}

#billing_downgrade_zone .danger_confirm_pill .btn_delete {
  flex: 0 0 auto;
  width: auto;
}

#panel-danger-zone h2,
#panel-danger-zone h3 {
  color: #ff6a6a;
}

#panel-danger-zone h2 {
  text-shadow: 0 0 12px rgba(255, 59, 59, 0.22);
}

#panel-danger-zone .danger_zone_intro {
  text-align: center;
  margin: 0.35rem 0 0;
}

#panel-danger-zone .danger_zone_actions {
  display: grid;
  gap: 1rem;
  margin-top: 0.8rem;
}

#panel-danger-zone .danger_zone_row {
  padding-top: 0.9rem;
  border-top: 1px solid color-mix(in srgb, #ff4f4f 22%, var(--border, #2a2a2a));
  display: grid;
  gap: 0.5rem;
}

#panel-danger-zone .danger_zone_text .help_text {
  text-align: center;
  font-size: 0.88rem;
  opacity: 0.82;
}

#panel-danger-zone .danger_confirm_pill {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  justify-content: center;
  flex-wrap: wrap;
}

#panel-danger-zone .danger_confirm_pill > span {
  white-space: nowrap;
}

#panel-danger-zone .danger_confirm_pill input[type="text"] {
  flex: 0 1 22rem;
}

#panel-danger-zone .danger_confirm_pill .btn_delete {
  flex: 0 0 auto;
  width: auto;
}

#panel-danger-zone .danger_confirm_pill form {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex: 0 1 auto;
}

#panel-danger-zone .danger_confirm_pill form input[type="text"] {
  flex: 0 1 22rem;
  min-width: 14rem;
}

.danger_confirm_pill input[type="text"] {
  border: 1px solid color-mix(in srgb, #ff4f4f 48%, var(--input-border, #3a3a3a));
}

.danger_confirm_pill input[type="text"]:focus-visible {
  outline: 2px solid color-mix(in srgb, #ff4f4f 78%, #ffffff);
  outline-offset: 1px;
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

/* Mobile: full-width panels and one-column benefits */
@media (max-width: 900px) {
  #main:has(#panel-billing) > section.panel {
    width: 100%;
    margin-left: 0;
    margin-right: 0;
  }

  #panel-personal-info .profile_personal_info_grid {
    grid-template-columns: 1fr;
  }

  #panel-personal-info .item_pair {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.35rem;
    align-items: start;
    justify-content: initial;
  }

  #panel-personal-info .item_value {
    width: 100%;
    flex: 0 0 auto;
  }

  #panel-personal-info .item_label {
    padding-top: 0;
    text-align: left;
    white-space: normal;
  }

  #panel-billing .billing_columns {
    grid-template-columns: 1fr;
  }

  #panel-billing .billing_value_list {
    grid-template-columns: 1fr;
  }

  #panel-pay-period .organizations_pp_control_strip {
    grid-template-columns: 1fr;
  }

  #panel-pay-period .organizations_pp_control {
    border-left: 0;
    border-top: 1px solid var(--fore-dark, #2a2a2a);
  }

  #panel-pay-period .organizations_pp_control:first-child {
    border-top: 0;
  }
}
