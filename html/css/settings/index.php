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
  --settings-control-height: 2.75rem;
  --settings-form-label-gap: var(--gap-sm);
  --settings-form-row-gap: var(--gap-lg);
  --settings-form-section-gap: var(--gap-lg);
  --settings-card-padding-block: var(--gap-lg);
  --settings-card-padding-inline: var(--gap-lg);
  --settings-pill-gap: var(--gap-sm);
  display: flex;
  flex-direction: column;
  flex-wrap: nowrap;
  align-items: stretch;
  gap: var(--settings-form-section-gap);
  width: 100%;
}

.settings_workspace {
  width: 100%;
  max-width: var(--app-content-width, 100%);
  margin-inline: 0;
}

/* SETTINGS JUMP NAV (legacy — kept for backwards-compatible styles) */
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

/* SETTINGS SUB-PAGE SHELL */
.settings_context_header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--gap-sm) var(--gap-md);
  margin: 0 0 var(--gap-sm);
  padding: 0 0 var(--gap-xs);
  border-bottom: 1px solid color-mix(in srgb, var(--border) 75%, transparent);
}

.settings_context_title {
  margin: 0;
  flex-shrink: 0;
  font-size: clamp(1.05rem, 1.8vw, 1.25rem);
  font-weight: 700;
  line-height: 1.25;
  letter-spacing: 0.04rem;
}

.settings_context_separator {
  flex-shrink: 0;
  align-self: stretch;
  width: 1px;
  min-height: 1.35rem;
  background: var(--border);
}

.settings_subnav {
  flex: 1 1 auto;
  min-width: 0;
  display: flex;
  justify-content: center;
}

.settings_subnav_tabs {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0;
}

.settings_subnav_tab {
  display: inline-flex;
  align-items: center;
  min-height: 0;
  padding: 0.4rem 0.65rem;
  background-color: transparent;
  text-decoration: none;
  color: color-mix(in srgb, var(--panel-text) 78%, transparent);
  border-bottom: 2px solid transparent;
  border-radius: 0;
  margin: 0 0 -2px;
  font-size: var(--font-sm);
  font-weight: 500;
  line-height: 1.2;
  transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.settings_subnav_tab:hover,
.settings_subnav_tab:active,
.settings_subnav_tab:focus-visible {
  background-color: color-mix(in srgb, var(--color-accent, #3b82f6) 16%, var(--panel-bg, transparent));
  border-bottom-color: color-mix(in srgb, var(--color-accent, #3b82f6) 56%, transparent);
  color: var(--panel-text);
}

.settings_subnav_tab:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

.settings_subnav_tab--active,
.settings_subnav_tab[aria-current='page'] {
  border-bottom-color: var(--color-accent, currentColor);
  background-color: transparent;
  color: var(--panel-text);
  font-weight: 600;
}

.settings_subnav_tab--active:hover,
.settings_subnav_tab--active:active,
.settings_subnav_tab--active:focus-visible,
.settings_subnav_tab[aria-current='page']:hover,
.settings_subnav_tab[aria-current='page']:active,
.settings_subnav_tab[aria-current='page']:focus-visible {
  background-color: color-mix(in srgb, var(--color-accent, #3b82f6) 18%, var(--panel-bg, transparent));
  border-bottom-color: var(--color-accent, currentColor);
  color: var(--panel-text);
}

.settings_page_content {
  display: flex;
  flex-direction: column;
  gap: var(--settings-form-section-gap);
}

.settings_workspace--account .settings_page_content {
  gap: calc(var(--settings-form-section-gap) * 1.35);
}

.settings_card_group {
  margin-bottom: 0;
  box-shadow: var(--depth-panel-shadow, none);
}

.settings_card_group .settings_card_title,
.settings_card_group h2.settings_card_title,
.settings_page_content h2.settings_card_title,
.settings_page_content > section.panel > h2,
.settings_page_content > section.panel .businesses_section_header h2 {
  margin: 0 0 var(--gap-sm);
  font-size: clamp(1.05rem, 1.8vw, 1.2rem);
  letter-spacing: 0.04rem;
  line-height: 1.25;
}

.settings_card_group .help_text {
  margin: 0 0 var(--gap-lg);
  font-size: var(--font-sm);
  line-height: 1.45;
  color: color-mix(in srgb, var(--panel-text) 82%, transparent);
  text-align: left;
}

.settings_card_group > form > .settings_card_title,
.settings_card_group > form > h2.settings_card_title {
  margin-bottom: var(--gap-md);
}

.settings_workspace--account #panel-account > form > .settings_card_title {
  margin-bottom: var(--gap-sm);
}

.settings_card_group.settings_card_group--basic {
  border-color: var(--border);
}

.settings_diagnostics_links {
  margin: 0.75rem 0 0;
  padding: 0;
  list-style: none;
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem 1rem;
}

.settings_diagnostics_links a {
  color: inherit;
}

.settings_voice_preview_btn {
  margin-top: var(--gap-sm);
}

.settings_card_title {
  margin-top: 0;
  margin-bottom: 0;
}

.settings_early_access_panel {
  gap: var(--gap-md);
}

.settings_early_access_card {
  display: grid;
  grid-template-columns: minmax(11rem, 14rem) minmax(0, 1fr);
  gap: var(--gap-md);
  padding: var(--settings-card-padding-block) var(--settings-card-padding-inline);
  border: 1px solid var(--panel-border);
  border-radius: 8px;
  background: color-mix(in srgb, var(--panel-bg) 94%, var(--panel-border));
}

.settings_early_access_card_header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: var(--gap-md);
}

.settings_early_access_card_title {
  margin: 0;
  font-size: 1rem;
  line-height: 1.3;
}

.settings_early_access_switch_column {
  display: flex;
  align-items: flex-start;
  padding-inline-end: var(--gap-md);
  border-inline-end: 1px solid var(--panel-border);
}

.settings_early_access_body {
  display: grid;
  gap: var(--gap-sm);
  min-width: 0;
}

.settings_early_access_sentence,
.settings_early_access_metadata {
  margin: 0;
}

.settings_early_access_metadata {
  color: var(--text-muted);
  font-size: 0.9rem;
}

.settings_early_access_actions {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: var(--gap-sm) var(--gap-md);
}

.settings_early_access_details summary,
.settings_early_access_feedback_link {
  appearance: none;
  padding: 0;
  border: 0;
  background: transparent;
  color: inherit;
  cursor: pointer;
  font: inherit;
  font-weight: 600;
  text-decoration: underline;
  text-underline-offset: 0.2em;
}

.settings_early_access_details p {
  margin: var(--gap-sm) 0 0;
}

.settings_switch {
  display: inline-flex;
  align-items: center;
  gap: var(--gap-sm);
  min-height: var(--settings-control-height);
  cursor: pointer;
}

.settings_switch input {
  position: absolute;
  inline-size: 1px;
  block-size: 1px;
  opacity: 0;
}

.settings_switch_track {
  position: relative;
  inline-size: 3rem;
  block-size: 1.65rem;
  flex: 0 0 auto;
  border: 1px solid var(--panel-border);
  border-radius: 999px;
  background: color-mix(in srgb, var(--panel-bg) 78%, var(--text-muted));
  transition: background 160ms ease, border-color 160ms ease;
}

.settings_switch_track::after {
  content: "";
  position: absolute;
  inset-block-start: 0.2rem;
  inset-inline-start: 0.2rem;
  inline-size: 1.15rem;
  block-size: 1.15rem;
  border-radius: 50%;
  background: var(--panel-bg);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.28);
  transition: inset-inline-start 160ms ease;
}

.settings_switch input:checked + .settings_switch_track {
  border-color: var(--accent, #3b82f6);
  background: var(--accent, #3b82f6);
}

.settings_switch input:checked + .settings_switch_track::after {
  inset-inline-start: calc(100% - 1.35rem);
}

.settings_switch input:focus-visible + .settings_switch_track {
  outline: 2px solid var(--focus-ring, var(--accent, #3b82f6));
  outline-offset: 3px;
}

.settings_switch input:disabled + .settings_switch_track,
.settings_switch input:disabled ~ .settings_switch_label {
  cursor: not-allowed;
  opacity: 0.62;
}

.settings_early_access_status {
  margin: 0;
}

@media (max-width: 720px) {
  .settings_early_access_card {
    grid-template-columns: 1fr;
  }

  .settings_early_access_switch_column {
    padding-inline-end: 0;
    padding-block-end: var(--gap-md);
    border-inline-end: 0;
    border-block-end: 1px solid var(--panel-border);
  }
}

.settings_dashboard_grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: var(--gap-md);
}

.settings_dashboard_card {
  display: flex;
  flex-direction: column;
  gap: var(--gap-sm);
  min-height: 12rem;
  padding: var(--pad-md);
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  background: color-mix(in srgb, var(--panel-bg) 94%, var(--panel-border));
  box-shadow: var(--depth-surface-shadow, none);
}

.settings_dashboard_card h3 {
  margin: 0;
  font-size: 1rem;
  line-height: 1.25;
}

.settings_dashboard_card p {
  flex: 1;
  margin: 0;
  color: color-mix(in srgb, var(--panel-text) 82%, transparent);
  line-height: 1.45;
}

#main section.panel form {
  display: flex;
  flex-direction: column;
  gap: var(--settings-form-row-gap);
  min-width: 0;
}

#main section.panel form > br {
  display: none;
}

/* Stacked label-above-control rows */
#main section.panel form > .flex.f_baseline.w100 {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--settings-form-label-gap) var(--gap-sm);
  align-items: start;
  margin: 0;
  padding: var(--gap-sm) 0;
}

#main section.panel form > .flex.f_baseline.w100 > label,
#main section.panel form > .flex.f_baseline.w100 > .w25 {
  grid-column: 1 / -1;
  flex: none;
  width: 100%;
  max-width: 100%;
  margin: 0;
  padding: 0;
  font-size: var(--font-sm);
  font-weight: 600;
  line-height: 1.35;
  text-align: left;
  white-space: normal;
}

#main section.panel form > .flex.f_baseline.w100 > .w75,
#main section.panel form > .flex.f_baseline.w100 > .flex.f_baseline.w75,
#main section.panel form > .flex.f_baseline.w100 > input.w75,
#main section.panel form > .flex.f_baseline.w100 > select.w75,
#main section.panel form > .flex.f_baseline.w100 > textarea.w75 {
  grid-column: 1 / -1;
  flex: none;
  width: 100%;
  max-width: 100%;
  min-width: 0;
  margin: 0;
}

#main section.panel form > .flex.f_baseline.w100 > .w75,
#main section.panel form > .flex.f_baseline.w100 > .flex.f_baseline.w75 {
  display: flex;
  flex-direction: column;
  gap: var(--gap-sm);
}

#main section.panel form > .flex.f_baseline.w100 > select.w50 {
  grid-column: 1;
}

#main section.panel form > .flex.f_baseline.w100 > select.w25,
#main section.panel form > .flex.f_baseline.w100 > .w25:not(label) {
  grid-column: 2;
}

#main section.panel form > .flex.f_baseline.w100 > .flex.w75 {
  display: flex;
  flex-wrap: wrap;
  gap: var(--gap-sm);
  align-items: center;
}

#main section.panel form > .settings_theme_mode_row {
  display: grid;
  grid-template-columns: minmax(14rem, 1fr) minmax(7rem, 12rem);
  gap: var(--gap-sm);
  align-items: end;
}

.settings_theme_header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--gap-md);
  margin-bottom: var(--gap-sm);
}

.settings_theme_header .settings_card_title {
  margin: 0;
}

.settings_mode_toggle {
  display: inline-flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.2rem;
  padding: 0.2rem;
  border: 1px solid var(--panel-border, currentColor);
  border-radius: 999px;
  background: color-mix(in srgb, var(--panel-bg, #111) 88%, var(--panel-text, #fff));
  max-width: 100%;
  flex: 0 0 auto;
}

.settings_mode_option {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  width: 5.35rem;
  min-width: 0;
  min-height: 2rem;
  padding: 0.35rem 0.55rem;
  border: 0;
  border-radius: 999px;
  background: transparent;
  color: var(--panel-text, currentColor);
  cursor: pointer;
  font: inherit;
  font-size: var(--font-xs, 0.75rem);
  font-weight: 800;
  line-height: 1;
  white-space: nowrap;
}

.settings_mode_option span {
  overflow: hidden;
  text-overflow: ellipsis;
}

.settings_mode_option svg {
  width: 1rem;
  height: 1rem;
  flex: 0 0 auto;
}

.settings_mode_option.is-selected {
  background: var(--color-primary, #4a9eff);
  color: var(--color-on-primary, #fff);
}

.settings_mode_option:focus-visible {
  outline: 2px solid var(--color-focus-ring, #4a9eff);
  outline-offset: 2px;
}

.settings_theme_grid {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: 0.45rem;
  margin-bottom: var(--gap-md);
}

.settings_theme_card {
  display: flex;
  position: relative;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  min-width: 0;
  aspect-ratio: 1;
  padding: 0.35rem 0.2rem;
  border: 1px solid color-mix(in srgb, var(--panel-border, currentColor) 78%, transparent);
  border-radius: var(--radius-control, 6px);
  background: var(--theme-card-bg, color-mix(in srgb, var(--panel-bg, #111) 92%, var(--color-primary, #4a9eff)));
  color: var(--theme-card-text, var(--panel-text, currentColor));
  container-type: inline-size;
  cursor: pointer;
  font: inherit;
  text-align: center;
  box-shadow: var(--depth-control-shadow, none);
  transition: border-color 0.12s ease, box-shadow 0.12s ease, background-color 0.12s ease, color 0.12s ease;
}

.settings_theme_card::after {
  content: attr(data-label);
  position: absolute;
  left: 50%;
  bottom: calc(100% + 0.45rem);
  z-index: 4;
  max-width: 12rem;
  padding: 0.3rem 0.45rem;
  border: 1px solid var(--panel-border, currentColor);
  border-radius: 5px;
  background: var(--panel-bg, #111);
  color: var(--panel-text, #fff);
  font-size: var(--font-xs, 0.75rem);
  font-weight: 700;
  line-height: 1.2;
  opacity: 0;
  pointer-events: none;
  text-align: center;
  transform: translateX(-50%);
  transition: opacity 0.1s ease;
  white-space: nowrap;
}

.settings_theme_card:hover {
  z-index: 2;
  border-color: var(--theme-card-accent, var(--color-primary, #4a9eff));
  box-shadow: var(--depth-control-shadow-hover, var(--depth-control-shadow, none));
}

.settings_theme_card:hover::after,
.settings_theme_card:focus-visible::after {
  opacity: 1;
  transform: translateX(-50%);
}

.settings_theme_card:focus-visible {
  outline: 2px solid var(--color-focus-ring, #4a9eff);
  outline-offset: 2px;
}

.settings_theme_card.is-selected {
  border-color: var(--theme-card-accent, var(--color-primary, #4a9eff));
  box-shadow: var(--depth-control-shadow-hover, none), 0 0 0 2px color-mix(in srgb, var(--theme-card-accent, var(--color-primary, #4a9eff)) 55%, transparent);
}

.settings_theme_card_icon {
  display: grid;
  place-items: center;
  width: clamp(1rem, 34%, 1.55rem);
  aspect-ratio: 1;
  color: var(--theme-card-accent, var(--color-primary, #4a9eff));
}

.settings_theme_card_icon svg {
  width: 100%;
  height: 100%;
}

.settings_theme_card_name {
  max-width: 100%;
  color: inherit;
  font-size: clamp(0.56rem, 0.58vw, 0.72rem);
  font-weight: 800;
  line-height: 1.15;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@container (max-width: 4.75rem) {
  .settings_theme_card_name {
    display: none;
  }
}

.settings_theme_selected_label {
  margin: calc(var(--gap-sm) * -0.35) 0 var(--gap-md);
  color: color-mix(in srgb, var(--panel-text, #fff) 78%, transparent);
  font-size: var(--font-xs, 0.75rem);
  font-weight: 700;
  line-height: 1.25;
}

.settings_theme_selected_label span {
  color: var(--color-primary, #4a9eff);
}

.settings_theme_card[data-theme-value="paycal_blue"] { --theme-card-bg: #121b2d; --theme-card-text: #f4f8ff; --theme-card-accent: #6fb7ff; }
.settings_theme_card[data-theme-value="paycal_black"] { --theme-card-bg: #111111; --theme-card-text: #f7f7f7; --theme-card-accent: #ffffff; }
.settings_theme_card[data-theme-value="paycal_red"] { --theme-card-bg: #2a1111; --theme-card-text: #fff3f3; --theme-card-accent: #ff6b6b; }
.settings_theme_card[data-theme-value="paycal_green"] { --theme-card-bg: #102417; --theme-card-text: #effff4; --theme-card-accent: #61d394; }
.settings_theme_card[data-theme-value="paycal_white"] { --theme-card-bg: #272727; --theme-card-text: #f7f7f7; --theme-card-accent: #d8d8d8; }
.settings_theme_card[data-theme-value="beos"] { --theme-card-bg: #2d2610; --theme-card-text: #fff6d5; --theme-card-accent: #f0c83d; }
.settings_theme_card[data-theme-value="haiku"] { --theme-card-bg: #2a230d; --theme-card-text: #fff6cc; --theme-card-accent: #d39700; }
.settings_theme_card[data-theme-value="zeta"] { --theme-card-bg: #24314d; --theme-card-text: #f6f1dc; --theme-card-accent: #f0c84b; }
.settings_theme_card[data-theme-value="debian"] { --theme-card-bg: #2b1020; --theme-card-text: #ffeef8; --theme-card-accent: #d70a53; }
.settings_theme_card[data-theme-value="fedora"] { --theme-card-bg: #10203d; --theme-card-text: #edf5ff; --theme-card-accent: #51a2da; }
.settings_theme_card[data-theme-value="mint"] { --theme-card-bg: #12301f; --theme-card-text: #f0fff6; --theme-card-accent: #8ad65e; }
.settings_theme_card[data-theme-value="linux"] { --theme-card-bg: #2b1b10; --theme-card-text: #fff6ed; --theme-card-accent: #e95420; }
.settings_theme_card[data-theme-value="system7"] { --theme-card-bg: #252532; --theme-card-text: #f0f0f0; --theme-card-accent: #9a9ad8; }
.settings_theme_card[data-theme-value="system8"] { --theme-card-bg: #1f2936; --theme-card-text: #edf4ff; --theme-card-accent: #8db7e8; }
.settings_theme_card[data-theme-value="macos9"] { --theme-card-bg: #1e2a39; --theme-card-text: #eef6ff; --theme-card-accent: #7fb5f0; }
.settings_theme_card[data-theme-value="macos"] { --theme-card-bg: #18283a; --theme-card-text: #eff7ff; --theme-card-accent: #66b5ff; }
.settings_theme_card[data-theme-value="bluejeans"] { --theme-card-bg: #1d3658; --theme-card-text: #edf5ff; --theme-card-accent: #7fb3ff; }
.settings_theme_card[data-theme-value="garden"] { --theme-card-bg: #16321f; --theme-card-text: #f0fff4; --theme-card-accent: #a3d977; }
.settings_theme_card[data-theme-value="retro"] { --theme-card-bg: #2b2423; --theme-card-text: #f3ece8; --theme-card-accent: #c95a54; }
.settings_theme_card[data-theme-value="arcade"] { --theme-card-bg: #10101c; --theme-card-text: #f7f2ff; --theme-card-accent: #ffcf33; }
.settings_theme_card[data-theme-value="c64"] { --theme-card-bg: #161b55; --theme-card-text: #eef1ff; --theme-card-accent: #8aa4ff; }
.settings_theme_card[data-theme-value="amiga"] { --theme-card-bg: #211d2b; --theme-card-text: #f7f2ff; --theme-card-accent: #ff7ab6; }
.settings_theme_card[data-theme-value="workbench"] { --theme-card-bg: #202a3d; --theme-card-text: #eef4ff; --theme-card-accent: #88a8df; }
.settings_theme_card[data-theme-value="nextstep"] { --theme-card-bg: #202020; --theme-card-text: #f4f4f4; --theme-card-accent: #ffb020; }
.settings_theme_card[data-theme-value="openstep"] { --theme-card-bg: #24282d; --theme-card-text: #f4f4f0; --theme-card-accent: #9fb6d8; }
.settings_theme_card[data-theme-value="solaris"] { --theme-card-bg: #172220; --theme-card-text: #eefcf8; --theme-card-accent: #ffb84d; }
.settings_theme_card[data-theme-value="terminal"] { --theme-card-bg: #0b120b; --theme-card-text: #e8ffe8; --theme-card-accent: #63ff63; }
.settings_theme_card[data-theme-value="irix"] { --theme-card-bg: #171b2f; --theme-card-text: #eef5ff; --theme-card-accent: #61d7c8; }
.settings_theme_card[data-theme-value="os2_warp"] { --theme-card-bg: #101a35; --theme-card-text: #f0f5ff; --theme-card-accent: #7aa7ff; }
.settings_theme_card[data-theme-value="palm_os"] { --theme-card-bg: #1d2924; --theme-card-text: #edf8f0; --theme-card-accent: #a7d7a2; }
.settings_theme_card[data-theme-value="cyberdeck"] { --theme-card-bg: #111820; --theme-card-text: #eef8ff; --theme-card-accent: #ffb84d; }
.settings_theme_card[data-theme-value="solarpunk"] { --theme-card-bg: #1d2c18; --theme-card-text: #f3ffe8; --theme-card-accent: #d4c95d; }
.settings_theme_card[data-theme-value="space_odyssey"] { --theme-card-bg: #171f2b; --theme-card-text: #f2f5fb; --theme-card-accent: #d8dde8; }
.settings_theme_card[data-theme-value="akira"] { --theme-card-bg: #2a1116; --theme-card-text: #fff0f2; --theme-card-accent: #ff304f; }
.settings_theme_card[data-theme-value="alien"] { --theme-card-bg: #101a14; --theme-card-text: #edf8ef; --theme-card-accent: #9bd47c; }
.settings_theme_card[data-theme-value="blade_runner"] { --theme-card-bg: #201421; --theme-card-text: #fff2f7; --theme-card-accent: #ff9f1c; }
.settings_theme_card[data-theme-value="dune"] { --theme-card-bg: #2c2115; --theme-card-text: #fff4df; --theme-card-accent: #d69a4b; }
.settings_theme_card[data-theme-value="star_trek"] { --theme-card-bg: #111d36; --theme-card-text: #f0f6ff; --theme-card-accent: #f2c94c; }
.settings_theme_card[data-theme-value="star_wars"] { --theme-card-bg: #141414; --theme-card-text: #f8f6e8; --theme-card-accent: #ffe66d; }
.settings_theme_card[data-theme-value="fifth_element"] { --theme-card-bg: #182430; --theme-card-text: #f4fbff; --theme-card-accent: #ff7a1a; }
.settings_theme_card[data-theme-value="matrix"] { --theme-card-bg: #071207; --theme-card-text: #eaffea; --theme-card-accent: #00ff66; }
.settings_theme_card[data-theme-value="tron"] { --theme-card-bg: #071827; --theme-card-text: #eaf8ff; --theme-card-accent: #00d8ff; }
.settings_theme_card[data-theme-value="vaporwave"] { --theme-card-bg: #1f1235; --theme-card-text: #fff0ff; --theme-card-accent: #ff6ad5; }
.settings_theme_card[data-theme-value="win10"] { --theme-card-bg: #10233f; --theme-card-text: #f2f8ff; --theme-card-accent: #3b82f6; }
.settings_theme_card[data-theme-value="win11"] { --theme-card-bg: #191f2b; --theme-card-text: #f3f7ff; --theme-card-accent: #61cdff; }
.settings_theme_card[data-theme-value="win95"] { --theme-card-bg: #2f3338; --theme-card-text: #f2f2f2; --theme-card-accent: #8ea8ff; }
.settings_theme_card[data-theme-value="win98"] { --theme-card-bg: #302f2d; --theme-card-text: #f4f1ea; --theme-card-accent: #73a8ff; }
.settings_theme_card[data-theme-value="winxp"] { --theme-card-bg: #1b4f9c; --theme-card-text: #f4f8ff; --theme-card-accent: #7bd15a; }

.settings_theme_mode_field {
  display: flex;
  flex-direction: column;
  gap: var(--settings-form-label-gap);
  min-width: 0;
}

.settings_theme_mode_field > select {
  width: 100%;
  min-width: 0;
}

.settings_theme_mode_label {
  margin: 0;
  font-size: var(--font-sm);
  font-weight: 600;
  line-height: 1.35;
  white-space: nowrap;
}

.settings_accent_picker {
  display: block;
  width: 100%;
  overflow: visible;
}

.settings_accent_swatches {
  display: grid;
  grid-template-columns: repeat(24, minmax(0, 1fr));
  gap: 0.45rem;
  align-items: center;
  padding-top: 0.15rem;
  isolation: isolate;
}

.settings_accent_swatch {
  display: block;
  position: relative;
  width: 100%;
  aspect-ratio: 1;
  min-height: 0;
  border: 2px solid transparent;
  border-radius: 5px;
  padding: 0;
  cursor: pointer;
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--panel-text, currentColor) 18%, transparent);
  transition: transform 0.1s ease, border-color 0.1s ease, box-shadow 0.1s ease;
}

.settings_accent_swatch:hover {
  z-index: 1;
  transform: scale(1.2);
}

.settings_accent_swatch::after {
  content: attr(data-label);
  position: absolute;
  left: 50%;
  bottom: calc(100% + 0.45rem);
  z-index: 2;
  max-width: 12rem;
  padding: 0.3rem 0.45rem;
  border: 1px solid var(--panel-border, currentColor);
  border-radius: 5px;
  background: var(--panel-bg, #111);
  color: var(--panel-text, #fff);
  font-size: var(--font-xs, 0.75rem);
  font-weight: 600;
  line-height: 1.2;
  opacity: 0;
  pointer-events: none;
  text-align: center;
  transform: translateX(-50%);
  transition: opacity 0.1s ease;
  white-space: nowrap;
}

.settings_accent_swatch:hover::after,
.settings_accent_swatch:focus-visible::after {
  opacity: 1;
  transform: translateX(-50%);
}

.settings_accent_swatch:focus-visible {
  outline: 2px solid var(--color-primary, #4a9eff);
  outline-offset: 2px;
}

.settings_accent_swatch.is-selected {
  border-color: #fff;
  box-shadow: 0 0 0 2px var(--color-primary, #4a9eff);
}

.settings_accent_preview {
  margin-top: var(--gap-sm);
}

.settings_accent_preview_window {
  overflow: hidden;
  border: 1px solid color-mix(in srgb, var(--accent-color, var(--color-accent, #3b82f6)) 42%, var(--panel-border, #333));
  border-radius: var(--radius-panel, 8px);
  background: var(--panel-bg, #171b1f);
  box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent-color, var(--color-accent, #3b82f6)) 12%, transparent);
}

.settings_accent_preview_titlebar {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--gap-sm);
  padding: 0.45rem 0.6rem;
  background: color-mix(in srgb, var(--accent-color, var(--color-accent, #3b82f6)) 18%, var(--panel-head-bg, #222));
  color: var(--panel-text, #fff);
  font-size: var(--font-xs, 0.75rem);
  font-weight: 700;
}

.settings_accent_preview_titlebar span {
  color: var(--accent-color, var(--color-accent, #3b82f6));
  font-size: var(--font-sm);
  line-height: 1.2;
}

.settings_accent_preview_body {
  display: grid;
  grid-template-columns: minmax(5rem, 0.8fr) minmax(6rem, 0.7fr) minmax(8rem, 1.25fr) auto;
  gap: var(--gap-sm);
  align-items: stretch;
  padding: var(--gap-sm);
}

.settings_accent_preview_calendar,
.settings_accent_preview_example,
.settings_accent_preview_report,
.settings_accent_preview_controls {
  min-width: 0;
  border: 1px solid color-mix(in srgb, var(--accent-color, var(--color-accent, #3b82f6)) 28%, var(--panel-border, #333));
  border-radius: var(--radius-control, 6px);
  background: color-mix(in srgb, var(--accent-color, var(--color-accent, #3b82f6)) 8%, var(--panel-bg, #171b1f));
}

.settings_accent_preview_calendar {
  display: grid;
  grid-template-rows: 1fr auto;
  padding: 0.55rem;
}

.settings_accent_preview_day {
  display: grid;
  place-items: center;
  min-height: 3rem;
  border-radius: var(--radius-cell, 6px);
  background: color-mix(in srgb, var(--accent-color, var(--color-accent, #3b82f6)) 28%, var(--calendar-day-bg, transparent));
  color: var(--panel-text, #fff);
  font-size: 1.45rem;
  font-weight: 800;
}

.settings_accent_preview_shift {
  margin-top: 0.45rem;
  padding: 0.25rem 0.4rem;
  border-inline-start: 3px solid var(--accent-color, var(--color-accent, #3b82f6));
  background: color-mix(in srgb, var(--accent-color, var(--color-accent, #3b82f6)) 18%, transparent);
  font-size: var(--font-xs, 0.75rem);
  font-weight: 700;
}

.settings_accent_preview_example {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  flex-wrap: wrap;
  padding: 0.55rem;
}

.settings_accent_preview_example span {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 2.4rem;
  min-height: 1.8rem;
  padding: 0.25rem 0.45rem;
  border: 1px solid color-mix(in srgb, var(--accent-color, var(--color-accent, #3b82f6)) 55%, var(--panel-border, #333));
  border-radius: 999px;
  background: color-mix(in srgb, var(--accent-color, var(--color-accent, #3b82f6)) 18%, transparent);
  color: var(--panel-text, #fff);
  font-size: var(--font-xs, 0.75rem);
  font-weight: 800;
}

.settings_accent_preview_report {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 0.35rem;
  padding: 0.65rem;
}

.settings_accent_preview_report_title {
  color: color-mix(in srgb, var(--panel-text, #fff) 72%, transparent);
  font-size: var(--font-xs, 0.75rem);
  font-weight: 700;
}

.settings_accent_preview_report_value {
  color: var(--accent-color, var(--color-accent, #3b82f6));
  font-size: 1.2rem;
  font-weight: 800;
}

.settings_accent_preview_bar {
  height: 0.45rem;
  overflow: hidden;
  border-radius: 99px;
  background: color-mix(in srgb, var(--panel-text, #fff) 12%, transparent);
}

.settings_accent_preview_bar span {
  display: block;
  width: 68%;
  height: 100%;
  border-radius: inherit;
  background: var(--accent-color, var(--color-accent, #3b82f6));
}

.settings_accent_preview_controls {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 0.45rem;
  padding: 0.55rem;
}

.settings_accent_preview_button {
  min-height: 2rem;
  padding: 0.35rem 0.7rem;
  border: 1px solid color-mix(in srgb, var(--accent-color, var(--color-accent, #3b82f6)) 70%, transparent);
  border-radius: var(--radius-button, 6px);
  background: var(--accent-color, var(--color-accent, #3b82f6));
  color: var(--accent-contrast-color, #fff);
  font: inherit;
  font-weight: 800;
}

.settings_accent_preview_pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 2rem;
  padding: 0.3rem 0.65rem;
  border: 1px solid color-mix(in srgb, var(--accent-color, var(--color-accent, #3b82f6)) 55%, var(--panel-border, #333));
  border-radius: 99px;
  background: color-mix(in srgb, var(--accent-color, var(--color-accent, #3b82f6)) 18%, transparent);
  color: var(--panel-text, #fff);
  font-size: var(--font-xs, 0.75rem);
  font-weight: 700;
}

#main section.panel form input:not([type='radio']):not([type='checkbox']):not([type='range']):not([type='hidden']),
#main section.panel form select,
#main section.panel form textarea,
#main section.panel form .currency_finder_search,
#main section.panel form .timezone_finder_search {
  min-height: var(--settings-control-height);
  margin: 0;
  padding: 0.5rem 0.75rem;
  text-align: left;
  box-sizing: border-box;
  border-radius: var(--radius-input, var(--border-radius));
}

#main section.panel form input:not([type='radio']):not([type='checkbox']):not([type='range']):focus-visible,
#main section.panel form select:focus-visible,
#main section.panel form textarea:focus-visible,
#main section.panel form .currency_finder_search:focus-visible,
#main section.panel form .timezone_finder_search:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

.item_pair {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--settings-form-label-gap);
  align-items: start;
  justify-content: initial;
  padding: var(--gap-sm) 0;
}

.item_pair .item_label {
  flex: none;
  max-width: 100%;
  width: 100%;
  margin: 0;
  font-size: var(--font-sm);
  font-weight: 600;
  line-height: 1.35;
  text-align: left;
}

.item_pair .item_value {
  flex: none;
  width: 100%;
  min-width: 0;
  display: grid;
  gap: var(--gap-sm);
}

.item_pair .item_value input,
.item_pair .item_value select,
.item_pair .item_value textarea {
  width: 100%;
  min-height: var(--settings-control-height);
  margin: 0;
  padding: 0.5rem 0.75rem;
  text-align: left;
  box-sizing: border-box;
}

#panel-account .recovery_email_input_row {
  display: flex;
  align-items: center;
  gap: var(--gap-sm);
  flex-wrap: nowrap;
}

#panel-account .recovery_email_input_row #recovery_email_input {
  flex: 1 1 auto;
  min-width: 0;
}

#panel-account .recovery_email_input_row #recovery_email_send_btn {
  flex: 0 0 auto;
  margin-top: 0;
  white-space: nowrap;
}

#panel-account .recovery_email_input_row #recovery_email_send_btn.is-working {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
}

#panel-account .recovery_email_input_row #recovery_email_send_btn.is-working::after {
  content: '';
  width: 0.95rem;
  height: 0.95rem;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: settingsBusySpin 700ms linear infinite;
}

#panel-account .account_details_grid {
  display: flex;
  flex-direction: column;
  gap: var(--gap-md);
  width: 100%;
}

#panel-account .details_column {
  display: grid;
  gap: 0.75rem;
  min-width: 0;
  padding: var(--pad-md);
  border: 1px solid color-mix(in srgb, var(--panel-border, currentColor) 78%, transparent);
  border-radius: var(--radius-panel, var(--border-radius));
  background: color-mix(in srgb, var(--panel-bg, #111) 94%, var(--panel-border, #333));
  box-sizing: border-box;
}

#panel-account .details_column_profile,
#panel-account .details_column_employment {
  grid-template-columns: repeat(2, minmax(0, 1fr));
  column-gap: var(--gap-md);
}

#panel-account .details_column_tax {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

#panel-account .details_column_title {
  font-size: 1rem;
  line-height: 1.2;
  margin: 0;
  color: var(--text);
}

#panel-account .details_column > .settings_card_title,
#panel-account .details_column > .details_column_title {
  grid-column: 1 / -1;
  margin-bottom: 0.1rem;
}

#panel-account .details_column .item_pair {
  padding: 0;
}

#panel-account #recovery_email_input_section,
#panel-account #recovery_email_verify_section,
#panel-account .details_column_tax .item_pair:last-child {
  grid-column: 1 / -1;
}

#main section.panel form > .flex.f_baseline.w100 > .w25 {
  flex: none;
  max-width: 100%;
}

#main section.panel form > .flex.f_baseline.w100 > .w75 {
  flex: none;
  width: 100%;
  min-width: 0;
}

#panel-calendar .radio_group,
#panel-style .radio_group {
  justify-content: space-between;
}

#panel-audio .radio_group {
  justify-content: flex-start;
  flex-wrap: wrap;
  gap: var(--settings-pill-gap) var(--gap-md);
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
#panel-debugging .radio_group.pill_group,
#main section.panel.settings_card_group .radio_group.pill_group,
#main section.panel .radio_group.pill_group {
  display: flex;
  flex-wrap: wrap;
  gap: var(--settings-pill-gap);
  margin-top: var(--gap-xs);
  padding: 3px;
  border: 1px solid var(--panel-border);
  border-radius: 999px;
  background: color-mix(in srgb, var(--panel-border) 35%, transparent);
  box-shadow: var(--depth-track-shadow, none);
}

#panel-calendar .calendar_badge_pills {
  display: flex;
  flex-wrap: wrap;
  gap: var(--settings-pill-gap);
  align-items: flex-start;
  margin-top: var(--gap-xs);
  padding: var(--pad-sm);
  border: 1px solid var(--panel-border);
  border-radius: var(--radius-control, var(--border-radius));
  background: color-mix(in srgb, var(--panel-border) 18%, transparent);
  box-sizing: border-box;
}

#panel-calendar .calendar_badge_pills .work_entry_field + label {
  flex: 0 0 auto;
  width: auto;
  min-width: 0;
  min-height: var(--settings-control-height);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 999px;
  padding: var(--pad-sm) var(--pad-md);
  font-weight: 600;
  white-space: nowrap;
  line-height: 1.2;
  text-align: center;
  cursor: pointer;
  background-color: color-mix(in srgb, var(--panel-border) 32%, transparent);
  color: var(--button-text, inherit);
  box-shadow: var(--depth-control-shadow, none);
  transition: var(--depth-interaction-transition, var(--short-transition) all ease);
}

.settings_toast_position_picker {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  grid-template-areas:
    "full-top full-top full-top"
    "top-left top-center top-right"
    "bottom-left bottom-center bottom-right"
    "full-bottom full-bottom full-bottom";
  gap: 0.35rem;
  margin-top: var(--gap-xs);
  padding: var(--pad-sm);
  border: 1px solid var(--panel-border);
  border-radius: var(--radius-panel, var(--border-radius));
  background: color-mix(in srgb, var(--panel-border) 18%, transparent);
  box-sizing: border-box;
}

.settings_toast_position_radio {
  position: absolute;
  left: -9999px;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  border: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
}

.settings_toast_position_option {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 0;
  min-height: var(--settings-control-height);
  padding: var(--pad-sm) var(--pad-md);
  border: 0;
  border-radius: 999px;
  background-color: color-mix(in srgb, var(--panel-border) 32%, transparent);
  color: var(--button-text, inherit);
  cursor: pointer;
  font-weight: 600;
  line-height: 1.2;
  text-align: center;
  white-space: normal;
  box-shadow: var(--depth-control-shadow, none);
  transition: var(--depth-interaction-transition, var(--short-transition) all ease);
}

.settings_toast_position_option--full_top { grid-area: full-top; }
.settings_toast_position_option--top_left { grid-area: top-left; }
.settings_toast_position_option--top_center { grid-area: top-center; }
.settings_toast_position_option--top_right { grid-area: top-right; }
.settings_toast_position_option--bottom_left { grid-area: bottom-left; }
.settings_toast_position_option--bottom_center { grid-area: bottom-center; }
.settings_toast_position_option--bottom_right { grid-area: bottom-right; }
.settings_toast_position_option--full_bottom { grid-area: full-bottom; }

.settings_toast_position_radio:hover + .settings_toast_position_option,
.settings_toast_position_radio:focus + .settings_toast_position_option,
.settings_toast_position_option:hover {
  border-color: var(--button-border-active);
  background-color: color-mix(in srgb, var(--button-bg-hover, var(--button-bg)) 72%, transparent);
  color: var(--button-text-hover, var(--button-text));
}

.settings_toast_position_radio:focus-visible + .settings_toast_position_option {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

.settings_toast_position_radio:checked + .settings_toast_position_option,
.settings_toast_position_radio:active + .settings_toast_position_option {
  border-color: var(--btn-selected-border, var(--button-border-active));
  background-color: var(--btn-selected-back, var(--button-bg-active));
  color: var(--btn-selected-fore, var(--button-text));
}

#panel-style .radio_group.pill_group .radio + label,
#panel-calendar .radio_group.pill_group .radio + label,
#panel-debugging .radio_group.pill_group .radio + label,
#main section.panel.settings_card_group .radio_group.pill_group .radio + label,
#main section.panel .radio_group.pill_group .radio + label {
  flex: 1 1 0;
  min-width: 7.5rem;
  min-height: var(--settings-control-height);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 999px;
  padding: var(--pad-sm) var(--pad-md);
  font-weight: 600;
  white-space: normal;
  line-height: 1.2;
  text-align: center;
  box-shadow: var(--depth-control-shadow, none);
  transition: var(--depth-interaction-transition, var(--short-transition) all ease);
}

#panel-style .radio_group.pill_group .radio:hover + label,
#panel-style .radio_group.pill_group .radio:focus + label,
#panel-calendar .radio_group.pill_group .radio:hover + label,
#panel-calendar .radio_group.pill_group .radio:focus + label,
#panel-calendar .calendar_badge_pills .work_entry_field:hover + label,
#panel-calendar .calendar_badge_pills .work_entry_field:focus + label,
#panel-debugging .radio_group.pill_group .radio:hover + label,
#panel-debugging .radio_group.pill_group .radio:focus + label,
#main section.panel.settings_card_group .radio_group.pill_group .radio:hover + label,
#main section.panel.settings_card_group .radio_group.pill_group .radio:focus + label,
#main section.panel .radio_group.pill_group .radio:hover + label,
#main section.panel .radio_group.pill_group .radio:focus + label {
  background-color: color-mix(in srgb, var(--btn-selected-back) 55%, transparent);
  border-color: transparent;
  color: var(--btn-selected-fore, var(--button-text));
  box-shadow: var(--depth-control-shadow-hover, var(--depth-control-shadow, none));
}

#panel-style .radio_group.pill_group input[type="radio"]:checked + label,
#panel-style .radio_group.pill_group .radio:active + label,
#panel-calendar .radio_group.pill_group input[type="radio"]:checked + label,
#panel-calendar .radio_group.pill_group .radio:active + label,
#panel-calendar .calendar_badge_pills .work_entry_field:checked + label,
#panel-calendar .calendar_badge_pills .work_entry_field:active + label,
#panel-debugging .radio_group.pill_group input[type="radio"]:checked + label,
#panel-debugging .radio_group.pill_group .radio:active + label,
#main section.panel.settings_card_group .radio_group.pill_group input[type="radio"]:checked + label,
#main section.panel.settings_card_group .radio_group.pill_group .radio:active + label,
#main section.panel .radio_group.pill_group input[type="radio"]:checked + label,
#main section.panel .radio_group.pill_group .radio:active + label {
  border-bottom: 0;
  border-color: transparent;
  background-color: var(--btn-selected-back, var(--button-bg-active));
  color: var(--btn-selected-fore, var(--button-text));
  box-shadow: var(--depth-control-shadow-active, var(--depth-control-shadow, none));
}

#panel-calendar .radio_group.calendar_long_pills .radio + label {
  min-width: 12rem;
  text-wrap: balance;
}

.details_column_tax .item_pair {
  gap: 0.45rem;
}

.details_column_tax > .settings_card_title {
  margin-bottom: 0.1rem;
}

.details_column_tax .item_label {
  color: color-mix(in srgb, var(--panel-text, #fff) 82%, transparent);
  font-size: var(--font-xs, 0.75rem);
  letter-spacing: 0;
}

.settings_tax_pill_group {
  width: min(100%, 15rem);
  margin-top: 0;
}

#main section.panel .settings_tax_pill_group.radio_group.pill_group .radio + label {
  min-width: 0;
  min-height: 2.25rem;
  padding: 0.45rem 0.75rem;
}

.details_column_tax #edit_details_reserve_name {
  min-height: 2.55rem;
}

#panel-calendar .calendar_badge_pills .work_entry_field:focus + label,
#panel-calendar .calendar_badge_pills .work_entry_field:focus-visible + label {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

#panel-account form,
#panel-account-locale form {
  display: flex;
  flex-direction: column;
  gap: var(--settings-form-row-gap);
}

#panel-account .account_actions {
  display: flex;
  flex-direction: column;
  gap: var(--gap-sm);
  width: 100%;
  padding-top: var(--pad-sm);
}

/* Account summary — left-aligned stacked fields */
#panel-account form > .flex.f_baseline.w100 {
  align-items: stretch;
}

#panel-account form > .flex.f_baseline.w100 > .w75,
#panel-account form > .flex.f_baseline.w100 > .flex.f_baseline.w75 {
  display: block;
  justify-content: initial;
  text-align: left;
}

#panel-account #edit_details_email,
#panel-account #edit_details_full_name,
#panel-account #edit_details_phone,
#panel-account #edit_details_province,
#panel-account #edit_details_timezone_picker {
  width: 100%;
  text-align: left;
}

#panel-account #edit_details_email {
  flex: 1 1 auto;
  min-width: 0;
  width: auto;
}

#panel-account .email_change_link,
#panel-account .email_change_link_disabled {
  flex: 0 0 auto;
  white-space: nowrap;
}

#panel-account .item_value.flex.f_baseline {
  align-items: center;
  gap: var(--gap-sm);
}

#panel-account #edit_details_email:disabled {
  border: 0;
  box-shadow: none;
  background: transparent;
}

#panel-account .details_column_profile .item_value input:not(#edit_details_phone),
#panel-account .details_column_profile .item_value select,
#panel-account .details_column_employment .item_value input,
#panel-account .details_column_employment .item_value select,
#panel-account #recovery_email_input,
#panel-account #recovery_email_code_input {
  padding-inline: 0.9rem;
}

#panel-account .personal_phone_input_shell {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  align-items: center;
  width: 100%;
  min-height: var(--settings-control-height);
  border: 1px solid var(--panel-border);
  border-radius: var(--radius-input, var(--border-radius));
  background: var(--panel-input-bg, var(--panel-bg));
  box-sizing: border-box;
  overflow: hidden;
}

#panel-account .personal_phone_country_code {
  padding-inline-start: 0.9rem;
  padding-inline-end: 0.45rem;
  font-weight: 700;
  color: color-mix(in srgb, var(--panel-text, #f5f5f5) 82%, transparent);
  pointer-events: none;
}

#panel-account .personal_phone_input_shell #edit_details_phone {
  min-width: 0;
  min-height: calc(var(--settings-control-height) - 2px);
  padding-inline: 0.35rem 0.9rem;
  border: 0;
  border-radius: 0;
  background: transparent;
  box-shadow: none;
}

#panel-account .account_actions .btn {
  width: 100%;
}

#panel-businesses form {
  display: flex;
  flex-direction: column;
  gap: var(--settings-form-row-gap);
}

.businesses_grid {
  display: grid;
  gap: var(--gap-sm);
}

.businesses_block {
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  padding: var(--pad-sm);
  display: flex;
  flex-direction: column;
  gap: var(--gap-xs);
}

.businesses_row {
  display: flex;
  gap: var(--gap-sm);
  align-items: center;
}

.businesses_row_compact input,
.businesses_row_compact select {
  flex: 1 1 auto;
}

.businesses_heading_row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--gap-sm);
}

.businesses_scope_grid {
  margin-top: var(--mar-xs);
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.35rem 0.75rem;
}

.businesses_scope_grid label {
  display: flex;
  gap: 0.4rem;
  align-items: center;
  font-size: var(--font-sm);
}

.businesses_defaults_grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.4rem 0.75rem;
  align-items: center;
}

.businesses_defaults_grid label {
  font-size: var(--font-sm);
}

.businesses_list {
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

.businesses_empty {
  opacity: 0.8;
}

.businesses_invite_row,
.businesses_discovery_row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.5rem;
  border-bottom: 1px solid var(--panel-border);
  padding: 0.35rem 0;
}

.businesses_invite_row:last-child,
.businesses_discovery_row:last-child {
  border-bottom: 0;
}

.businesses_audit_row {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  border-bottom: 1px solid var(--panel-border);
  padding: 0.35rem 0;
}

.businesses_audit_row:last-child {
  border-bottom: 0;
}

.businesses_discovery_actions {
  display: flex;
  gap: 0.35rem;
  align-items: center;
}

.businesses_meta {
  font-size: var(--font-sm);
  opacity: 0.9;
}

.businesses_actions {
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
  gap: var(--gap-md);
  align-items: center;
  min-height: var(--settings-control-height);
  padding-block: var(--gap-xs);
}

.security_slider_edge {
  font-size: var(--font-sm);
  opacity: 0.85;
  white-space: nowrap;
}

.security_slider_row_compact {
  grid-template-columns: minmax(3.8rem, auto) minmax(0, 1fr) minmax(3.8rem, auto);
  gap: var(--gap-sm);
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
.security_slider_row input[type='range'],
.proximity_slider_wrap input[type='range'] {
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
  box-shadow: var(--depth-track-shadow, none);
  transition: var(--depth-interaction-transition, box-shadow 0.12s ease);
}

.security_slider_row input[type='range']::-webkit-slider-runnable-track,
.proximity_slider_wrap input[type='range']::-webkit-slider-runnable-track {
  height: var(--slider-track-height);
  border-radius: 999px;
  background: transparent;
}

.security_slider_row input[type='range']::-webkit-slider-thumb,
.proximity_slider_wrap input[type='range']::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: var(--slider-thumb-size);
  height: var(--slider-thumb-size);
  margin-top: calc((var(--slider-track-height) - var(--slider-thumb-size)) / 2);
  border: 2px solid var(--slider-thumb-border);
  border-radius: 50%;
  background: var(--slider-thumb-bg);
  box-shadow: var(--depth-control-shadow, 0 1px 4px color-mix(in srgb, var(--panel-head-text) 26%, black));
}

.security_slider_row input[type='range']::-moz-range-track,
.proximity_slider_wrap input[type='range']::-moz-range-track {
  height: var(--slider-track-height);
  border-radius: 999px;
  background: var(--slider-track-bg);
}

.security_slider_row input[type='range']::-moz-range-progress,
.proximity_slider_wrap input[type='range']::-moz-range-progress {
  height: var(--slider-track-height);
  border-radius: 999px;
  background: var(--slider-fill-bg);
}

.security_slider_row input[type='range']::-moz-range-thumb,
.proximity_slider_wrap input[type='range']::-moz-range-thumb {
  width: var(--slider-thumb-size);
  height: var(--slider-thumb-size);
  border: 2px solid var(--slider-thumb-border);
  border-radius: 50%;
  background: var(--slider-thumb-bg);
  box-shadow: var(--depth-control-shadow, 0 1px 4px color-mix(in srgb, var(--panel-head-text) 26%, black));
}

.security_slider_row input[type='range']:hover,
.proximity_slider_wrap input[type='range']:hover {
  --slider-fill-bg: color-mix(in srgb, var(--color-primary) 82%, white);
  box-shadow: var(--depth-control-shadow-hover, var(--depth-track-shadow, none));
}

.security_slider_row input[type='range']:focus-visible,
.proximity_slider_wrap input[type='range']:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
  box-shadow: var(--depth-focus-shadow, var(--depth-control-shadow-hover, none));
}

.security_slider_row input[type='range']:disabled,
.proximity_slider_wrap input[type='range']:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.security_level_value {
  margin-top: var(--gap-sm);
  font-weight: 700;
  color: var(--color-primary);
}

/* Proximity distance slider layout */
.proximity_slider_wrap {
  display: flex;
  align-items: center;
  gap: var(--gap-md);
  min-height: var(--settings-control-height);
  padding-block: var(--gap-xs);
}

.proximity_slider_wrap input[type='range'] {
  --slider-track-height: 0.5rem;
  --slider-thumb-size: 1.1rem;
  --slider-track-bg: color-mix(in srgb, var(--panel-border) 48%, var(--panel-bg));
  --slider-fill-bg: var(--color-primary);
  --slider-thumb-bg: #ffffff;
  --slider-thumb-border: color-mix(in srgb, var(--color-primary) 65%, black);
  flex: 1;
}

.overlay_collapse_row {
  display: flex;
  align-items: center;
  gap: var(--gap-md);
  margin-top: var(--gap-sm);
  padding-top: var(--gap-sm);
}

.overlay_collapse_label {
  flex: 0 0 auto;
  min-width: 5rem;
  font-size: var(--font-sm);
  color: var(--fore-muted, rgba(128, 128, 128, 0.85));
}

.overlay_collapse_row .proximity_slider_wrap {
  flex: 1;
  min-width: 0;
}

#voice_volume_output,
#nav_proximity_px_output,
#text_slider_value,
#spacing_slider_value,
#help_popup_timeout_seconds_output,
#overlay_sidebar_timeout_seconds_output,
#toast_font_size_output {
  min-width: 4.5rem;
  text-align: right;
  font-size: var(--font-sm);
  font-variant-numeric: tabular-nums;
  color: var(--color-primary);
  font-weight: 600;
  white-space: nowrap;
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
  gap: var(--settings-form-row-gap);
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
  box-shadow: var(--depth-dialog-shadow, var(--shadow-lg));
  font-size: 1.125rem;
  line-height: 1.5;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.12s ease;
}

.hover_help_tooltip.is-visible {
  opacity: 1;
}

.settings_recovery_key_card {
  margin-top: 1rem;
  padding: 1rem;
  border: 1px solid var(--border-color, rgba(255, 255, 255, 0.12));
  border-radius: var(--radius-sm, 0.25rem);
  background: color-mix(in srgb, var(--surface-color, #20252b) 86%, #000 14%);
}

.settings_recovery_key_header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.settings_recovery_key_title {
  margin: 0 0 0.35rem;
  color: var(--heading-color, #57b8ff);
  font-size: 1rem;
}

.settings_recovery_key_text {
  margin: 0;
  max-width: 42rem;
  line-height: 1.45;
  opacity: 0.92;
}

.settings_recovery_key_meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
  gap: 0.75rem;
  margin: 1rem 0 0;
}

.settings_recovery_key_meta div {
  display: grid;
  grid-template-columns: minmax(6.5rem, 38%) minmax(0, 1fr);
  gap: 0.35rem;
  align-items: baseline;
  padding: 0.75rem;
  border: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));
  border-radius: var(--radius-sm, 0.25rem);
}

.settings_recovery_key_meta dt {
  margin: 0;
  font-size: 0.82rem;
  opacity: 0.75;
}

.settings_recovery_key_meta dd {
  display: block;
  min-width: 0;
  margin: 0;
  font-weight: 700;
  text-align: left;
  word-break: break-word;
}

.settings_recovery_key_meta dd.is-active {
  color: #7bd88f;
}

.settings_recovery_key_meta dd.is-missing {
  color: #f1c86d;
}

.settings_recovery_key_actions {
  margin-top: 0.9rem;
  display: flex;
  justify-content: flex-start;
}

.settings_recovery_code_once {
  margin-top: 0.9rem;
  padding: 0.75rem;
  border: 1px solid color-mix(in srgb, var(--border-color, rgba(255, 255, 255, 0.14)) 82%, #57b8ff 18%);
  border-radius: var(--radius-sm, 0.25rem);
  background: color-mix(in srgb, var(--surface-color, #20252b) 78%, #57b8ff 8%);
}

.settings_recovery_code_once_help,
.settings_recovery_code_once_status {
  margin: 0;
  line-height: 1.4;
}

.settings_recovery_code_once_value {
  display: block;
  margin-top: 0.55rem;
  padding: 0.7rem 0.75rem;
  border: 1px solid var(--border-color, rgba(255, 255, 255, 0.12));
  border-radius: var(--radius-sm, 0.25rem);
  background: color-mix(in srgb, var(--surface-color, #20252b) 72%, #000 28%);
  color: var(--text-color, #fff);
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
  font-size: 1.05rem;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0;
  overflow-wrap: anywhere;
}

.settings_recovery_code_once_actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.55rem;
  margin-top: 0.65rem;
}

.settings_recovery_code_once_status {
  min-height: 1.4em;
  margin-top: 0.45rem;
  font-weight: 600;
}

.passkey_action_status {
  margin-top: 0.65rem;
  min-height: 1.25rem;
}

.status_message_callout {
  display: none;
  width: 100%;
  padding: 0.6rem 0.75rem;
  border: 1px solid transparent;
  border-radius: 0.55rem;
  font-weight: 600;
  line-height: 1.35;
  text-align: left;
}

.status_message_callout.is-visible {
  display: block;
}

.status_message_callout.is-info {
  color: #0f4f87;
  border-color: #85b8e5;
  background: #e8f3ff;
}

.status_message_callout.is-success {
  color: #1f5f34;
  border-color: #7bcf9b;
  background: #e8f8ee;
}

.status_message_callout.is-warning {
  color: #7a4a00;
  border-color: #f0c27d;
  background: #fff4e5;
}

.status_message_callout.is-error {
  color: #7a1f1f;
  border-color: #e2a8a8;
  background: #fdeeee;
}

@keyframes settingsBusySpin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes settingsPasskeyCheckMorph {
  from {
    opacity: 0;
    clip-path: inset(0 100% 0 0);
  }
  to {
    opacity: 1;
    clip-path: inset(0 0 0 0);
  }
}

.passkey_security_summary {
  margin-top: 0;
  margin-bottom: 0.75rem;
}

.passkey_empty_state {
  margin-top: 0.75rem;
  padding: 1.25rem 1rem;
  border: 1px solid var(--border-color, rgba(255, 255, 255, 0.12));
  border-radius: var(--radius-sm, 0.25rem);
  background: color-mix(in srgb, var(--surface-color, #20252b) 86%, #000 14%);
  box-shadow: var(--depth-surface-shadow, none);
  text-align: center;
}

.passkey_empty_state_title {
  margin: 0 0 0.35rem;
  font-size: 1.05rem;
  font-weight: 700;
}

.passkey_empty_state_text {
  margin: 0 0 0.9rem;
  opacity: 0.9;
  line-height: 1.45;
}

.passkey_card_grid {
  margin-top: 0.6rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(16rem, 100%), 1fr));
  gap: 0.75rem;
}

/* Passkey cards use the shared depth harness (--depth-surface-shadow / --depth-control-shadow-hover).
   Gamification glow, emblem, and lift layers gate on html[data-depth] (flat/low = minimal). */
.passkey_card {
  --passkey-card-accent: var(--accent-color, #4d8ef0);
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
  min-height: 100%;
  padding: 2rem 1rem 1rem;
  border: 1px solid var(--border-color, rgba(255, 255, 255, 0.12));
  border-radius: var(--radius-sm, 0.25rem);
  background: color-mix(in srgb, var(--surface-color, #20252b) 86%, #000 14%);
  box-shadow: var(--depth-surface-shadow, none);
  transition: var(--depth-interaction-transition, border-color 0.12s ease, box-shadow 0.12s ease, background-color 0.12s ease, transform 0.12s ease);
  overflow: visible;
}

.passkey_card > * {
  position: relative;
  z-index: 1;
}

.passkey_card::before,
.passkey_card::after {
  content: '';
  position: absolute;
  pointer-events: none;
  border-radius: inherit;
}

.passkey_card::before {
  inset: -1px;
  z-index: 0;
  opacity: 0;
  border: 1px solid color-mix(in srgb, var(--passkey-card-accent) 20%, transparent);
}

.passkey_card::after {
  inset: 0;
  z-index: 0;
  opacity: 0;
  background:
    radial-gradient(circle at 0.55rem 0.55rem, color-mix(in srgb, var(--passkey-card-accent) 52%, transparent) 0 0.18rem, transparent 0.19rem),
    radial-gradient(circle at calc(100% - 0.55rem) 0.55rem, color-mix(in srgb, var(--passkey-card-accent) 52%, transparent) 0 0.18rem, transparent 0.19rem),
    radial-gradient(circle at 0.55rem calc(100% - 0.55rem), color-mix(in srgb, var(--passkey-card-accent) 38%, transparent) 0 0.16rem, transparent 0.17rem),
    radial-gradient(circle at calc(100% - 0.55rem) calc(100% - 0.55rem), color-mix(in srgb, var(--passkey-card-accent) 38%, transparent) 0 0.16rem, transparent 0.17rem),
    radial-gradient(circle at 50% 0, color-mix(in srgb, var(--passkey-card-accent) 62%, transparent) 0 0.22rem, transparent 0.23rem),
    radial-gradient(circle at 50% 100%, color-mix(in srgb, var(--passkey-card-accent) 48%, transparent) 0 0.18rem, transparent 0.19rem);
}

.passkey_card--current {
  --passkey-card-accent: var(--color-primary, var(--accent-color, #4d8ef0));
}

.passkey_card--security-key,
.passkey_card--recovery {
  --passkey-card-accent: var(--accent-color, var(--color-primary, #4d8ef0));
}

html[data-depth="standard"] .passkey_card,
html[data-depth="high"] .passkey_card {
  border-color: color-mix(in srgb, var(--border-color, rgba(255, 255, 255, 0.12)) 72%, var(--passkey-card-accent) 28%);
  background:
    radial-gradient(ellipse 120% 80% at 50% -10%, color-mix(in srgb, var(--passkey-card-accent) 16%, transparent) 0%, transparent 58%),
    color-mix(in srgb, var(--surface-color, #20252b) 86%, #000 14%);
  box-shadow:
    var(--depth-surface-shadow, none),
    0 0 0 1px color-mix(in srgb, var(--passkey-card-accent) 14%, var(--border-color, rgba(255, 255, 255, 0.12)) 86%),
    inset 0 1px 0 color-mix(in srgb, var(--panel-text, #fff) 8%, transparent);
}

html[data-depth="standard"] .passkey_card::before,
html[data-depth="high"] .passkey_card::before {
  opacity: 1;
  box-shadow:
    0 0 0 1px color-mix(in srgb, var(--border-color, rgba(255, 255, 255, 0.12)) 55%, transparent),
    0 0 16px color-mix(in srgb, var(--passkey-card-accent) 14%, transparent);
}

html[data-depth="standard"] .passkey_card::after,
html[data-depth="high"] .passkey_card::after {
  opacity: 0.9;
}

html[data-depth="high"] .passkey_card {
  box-shadow:
    var(--depth-surface-shadow, none),
    0 0 0 1px color-mix(in srgb, var(--passkey-card-accent) 18%, var(--border-color, rgba(255, 255, 255, 0.12)) 82%),
    0 0 22px color-mix(in srgb, var(--passkey-card-accent) 18%, transparent),
    inset 0 1px 0 color-mix(in srgb, var(--panel-text, #fff) 10%, transparent);
}

html[data-depth="high"] .passkey_card::before {
  box-shadow:
    0 0 0 1px color-mix(in srgb, var(--border-color, rgba(255, 255, 255, 0.12)) 55%, transparent),
    0 0 24px color-mix(in srgb, var(--passkey-card-accent) 22%, transparent);
}

html[data-depth="high"] .passkey_card--current {
  box-shadow:
    var(--depth-surface-shadow, none),
    0 0 0 1px color-mix(in srgb, var(--passkey-card-accent) 22%, var(--border-color, rgba(255, 255, 255, 0.12)) 78%),
    0 0 28px color-mix(in srgb, var(--passkey-card-accent) 26%, transparent),
    inset 0 1px 0 color-mix(in srgb, var(--panel-text, #fff) 10%, transparent);
}

.passkey_card_emblem {
  position: absolute;
  top: -0.72rem;
  left: 50%;
  z-index: 2;
  display: grid;
  place-items: center;
  width: 2.45rem;
  height: 2.45rem;
  border: 1px solid color-mix(in srgb, var(--passkey-card-accent) 48%, var(--border-color, rgba(255, 255, 255, 0.12)));
  border-radius: 50%;
  background:
    radial-gradient(circle at 35% 28%, color-mix(in srgb, var(--passkey-card-accent) 32%, #fff 8%), transparent 58%),
    radial-gradient(circle at 50% 62%, color-mix(in srgb, #000 18%, transparent) 0%, transparent 68%),
    color-mix(in srgb, var(--passkey-card-accent) 16%, var(--surface-color, #20252b));
  box-shadow:
    0 0 0 3px color-mix(in srgb, var(--surface-color, #20252b) 92%, transparent),
    0 0 0 4px color-mix(in srgb, var(--passkey-card-accent) 16%, transparent),
    inset 0 1px 3px color-mix(in srgb, #000 22%, transparent);
  color: var(--passkey-card-accent);
  transform: translateX(-50%);
}

html[data-depth="standard"] .passkey_card_emblem,
html[data-depth="high"] .passkey_card_emblem {
  box-shadow:
    0 0 0 3px color-mix(in srgb, var(--surface-color, #20252b) 92%, transparent),
    0 0 0 4px color-mix(in srgb, var(--passkey-card-accent) 20%, transparent),
    0 0 14px color-mix(in srgb, var(--passkey-card-accent) 28%, transparent),
    inset 0 1px 3px color-mix(in srgb, #000 22%, transparent);
}

html[data-depth="high"] .passkey_card_emblem {
  box-shadow:
    0 0 0 3px color-mix(in srgb, var(--surface-color, #20252b) 92%, transparent),
    0 0 0 4px color-mix(in srgb, var(--passkey-card-accent) 24%, transparent),
    0 0 20px color-mix(in srgb, var(--passkey-card-accent) 34%, transparent),
    inset 0 1px 3px color-mix(in srgb, #000 22%, transparent);
}

.passkey_card_emblem svg {
  width: 1.05rem;
  height: 1.05rem;
}

.passkey_card:hover,
.passkey_card:focus-within {
  box-shadow: var(--depth-control-shadow-hover, var(--depth-surface-shadow, none));
}

html[data-depth="standard"] .passkey_card:hover,
html[data-depth="standard"] .passkey_card:focus-within,
html[data-depth="high"] .passkey_card:hover,
html[data-depth="high"] .passkey_card:focus-within {
  border-color: color-mix(in srgb, var(--passkey-card-accent) 42%, var(--border-color, rgba(255, 255, 255, 0.12)) 58%);
}

.passkey_card_header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.5rem;
}

.passkey_card_title {
  margin: 0;
  min-width: 0;
  color: var(--heading-color, inherit);
  font-size: 1.02rem;
  font-weight: 700;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

.passkey_card_title.is-editable {
  cursor: text;
  padding: 0.1rem 0.25rem;
  border-radius: var(--radius-sm, 0.25rem);
  transition: background-color 0.15s ease;
}

.passkey_card_title.is-editable:hover {
  background-color: var(--back-light, rgba(255, 255, 255, 0.04));
}

.passkey_card_title.is-editable:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 1px;
  background-color: var(--back-light, rgba(255, 255, 255, 0.06));
}

.passkey_card_menu_wrap {
  position: relative;
  flex: 0 0 auto;
}

.passkey_card_header:has(.passkey_card_menu:not([hidden])) {
  z-index: 5;
}

.passkey_card_menu_trigger {
  min-width: 2rem;
  min-height: 2rem;
  padding: 0;
  border: 1px solid var(--border-color, rgba(255, 255, 255, 0.14));
  border-radius: var(--radius-sm, 0.25rem);
  background: transparent;
  color: inherit;
  font-size: 1.1rem;
  line-height: 1;
  cursor: pointer;
  box-shadow: var(--depth-control-shadow, none);
  transition: var(--depth-interaction-transition, border-color 0.12s ease, box-shadow 0.12s ease, background-color 0.12s ease);
}

.passkey_card_menu_trigger:hover,
.passkey_card_menu_trigger:focus-visible {
  border-color: var(--button-border-active, rgba(255, 255, 255, 0.28));
  background: color-mix(in srgb, var(--surface-color, #20252b) 70%, #fff 8%);
  box-shadow: var(--depth-control-shadow-hover, var(--depth-control-shadow, none));
}

.passkey_card_menu_trigger:focus-visible {
  outline: none;
  box-shadow: var(--depth-focus-shadow, var(--depth-control-shadow-hover, var(--depth-control-shadow, none)));
}

.passkey_card_menu {
  position: absolute;
  top: calc(100% + 0.25rem);
  right: 0;
  z-index: 6;
  min-width: 8.5rem;
  padding: 0.25rem;
  border: 1px solid var(--border-color, rgba(255, 255, 255, 0.14));
  border-radius: var(--radius-sm, 0.25rem);
  background-color: color-mix(in srgb, var(--surface-color, #20252b) 96%, #000 4%);
  box-shadow: var(--depth-dialog-shadow, var(--depth-panel-shadow, 0 8px 24px rgba(0, 0, 0, 0.28)));
}

.passkey_card_menu[hidden] {
  display: none;
}

.passkey_card_menu_item {
  display: block;
  width: 100%;
  padding: 0.45rem 0.55rem;
  border: 0;
  border-radius: calc(var(--radius-sm, 0.25rem) - 0.05rem);
  background: transparent;
  color: inherit;
  text-align: left;
  font: inherit;
  cursor: pointer;
}

.passkey_card_menu_item:hover,
.passkey_card_menu_item:focus-visible {
  background: color-mix(in srgb, var(--accent-color, #4d8ef0) 16%, transparent);
}

.passkey_card_menu_item.is-destructive {
  color: color-mix(in srgb, var(--danger-color, #e06c6c) 88%, #fff 12%);
}

.passkey_card_subtitle {
  margin: 0;
  font-size: 0.84rem;
  opacity: 0.82;
  line-height: 1.35;
}

.passkey_card_status {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  gap: 0.4rem;
  margin: 0;
  font-size: 0.82rem;
  line-height: 1.4;
  text-align: center;
  color: color-mix(in srgb, var(--heading-color, #fff) 76%, transparent);
}

.passkey_card_status::before {
  content: '';
  flex: 0 0 0.88rem;
  width: 0.88rem;
  height: 0.88rem;
  margin-top: 0.08rem;
  border-radius: 50%;
  background-color: color-mix(in srgb, var(--passkey-card-accent, var(--accent-color, #4d8ef0)) 82%, var(--heading-color, #fff) 18%);
  box-shadow: 0 0 0 1px color-mix(in srgb, var(--passkey-card-accent, var(--accent-color, #4d8ef0)) 28%, transparent);
  mask: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="black" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>') center / 68% no-repeat;
  -webkit-mask: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="black" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>') center / 68% no-repeat;
}

html[data-depth="standard"] .passkey_card_status::before,
html[data-depth="high"] .passkey_card_status::before {
  box-shadow:
    0 0 0 1px color-mix(in srgb, var(--passkey-card-accent, var(--accent-color, #4d8ef0)) 32%, transparent),
    0 0 8px color-mix(in srgb, var(--passkey-card-accent, var(--accent-color, #4d8ef0)) 28%, transparent);
}

.passkey_card_badges {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.35rem;
}

.passkey_card_badge {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  border-radius: 999px;
  border: 1px solid color-mix(in srgb, var(--border-color, rgba(255, 255, 255, 0.14)) 80%, var(--accent-color, #4d8ef0) 20%);
  background: linear-gradient(
    180deg,
    color-mix(in srgb, var(--accent-color, #4d8ef0) 16%, transparent),
    color-mix(in srgb, var(--accent-color, #4d8ef0) 8%, transparent)
  );
  font-size: 0.72rem;
  font-weight: 600;
  line-height: 1.2;
  box-shadow: inset 0 1px 0 color-mix(in srgb, var(--panel-text, #fff) 10%, transparent);
}

.passkey_card_badge.is-accent {
  border-color: color-mix(in srgb, var(--passkey-card-accent, var(--accent-color, #4d8ef0)) 58%, transparent);
  background: linear-gradient(
    180deg,
    color-mix(in srgb, var(--passkey-card-accent, var(--accent-color, #4d8ef0)) 28%, transparent),
    color-mix(in srgb, var(--passkey-card-accent, var(--accent-color, #4d8ef0)) 12%, transparent)
  );
  color: color-mix(in srgb, var(--passkey-card-accent, var(--accent-color, #4d8ef0)) 90%, #fff 10%);
}

.passkey_card_badge.is-recovery {
  border-color: color-mix(in srgb, var(--accent-color, var(--color-primary, #4d8ef0)) 48%, transparent);
  background: linear-gradient(
    180deg,
    color-mix(in srgb, var(--accent-color, var(--color-primary, #4d8ef0)) 22%, transparent),
    color-mix(in srgb, var(--accent-color, var(--color-primary, #4d8ef0)) 10%, transparent)
  );
}

.passkey_card_badge.is-warn {
  border-color: color-mix(in srgb, var(--heading-color, #fff) 28%, var(--border-color, rgba(255, 255, 255, 0.14)) 72%);
  background: linear-gradient(
    180deg,
    color-mix(in srgb, var(--heading-color, #fff) 12%, transparent),
    color-mix(in srgb, var(--heading-color, #fff) 5%, transparent)
  );
}

.passkey_card_badge.is-recent {
  border-color: color-mix(in srgb, var(--color-primary, var(--accent-color, #4d8ef0)) 48%, transparent);
  background: linear-gradient(
    180deg,
    color-mix(in srgb, var(--color-primary, var(--accent-color, #4d8ef0)) 22%, transparent),
    color-mix(in srgb, var(--color-primary, var(--accent-color, #4d8ef0)) 10%, transparent)
  );
  color: color-mix(in srgb, var(--color-primary, var(--accent-color, #4d8ef0)) 88%, #fff 12%);
}

.passkey_card_badge.is-security {
  border-color: color-mix(in srgb, var(--accent-color, var(--color-primary, #4d8ef0)) 48%, transparent);
  background: linear-gradient(
    180deg,
    color-mix(in srgb, var(--accent-color, var(--color-primary, #4d8ef0)) 20%, transparent),
    color-mix(in srgb, var(--accent-color, var(--color-primary, #4d8ef0)) 9%, transparent)
  );
  color: color-mix(in srgb, var(--accent-color, var(--color-primary, #4d8ef0)) 86%, #fff 14%);
}

.passkey_card_badge.is-muted {
  opacity: 0.88;
}

.passkey_card_meta {
  display: grid;
  gap: 0;
  margin: 0.15rem 0 0;
  padding: 0.55rem 0.65rem 0.5rem;
  border: 1px solid color-mix(in srgb, var(--border-color, rgba(255, 255, 255, 0.12)) 72%, #000 28%);
  border-radius: calc(var(--radius-sm, 0.25rem) - 0.05rem);
  background: color-mix(in srgb, #000 34%, var(--surface-color, #20252b) 66%);
  box-shadow:
    inset 0 1px 5px color-mix(in srgb, #000 42%, transparent),
    inset 0 0 0 1px color-mix(in srgb, var(--panel-text, #fff) 4%, transparent);
  font-size: 0.8rem;
  line-height: 1.35;
}

.passkey_card_meta::before {
  content: '';
  display: block;
  height: 1px;
  margin: -0.55rem -0.65rem 0.45rem;
  background:
    linear-gradient(
      90deg,
      transparent,
      color-mix(in srgb, var(--passkey-card-accent, var(--accent-color, #4d8ef0)) 28%, var(--border-color, rgba(255, 255, 255, 0.12)) 72%) 18%,
      color-mix(in srgb, var(--passkey-card-accent, var(--accent-color, #4d8ef0)) 42%, transparent) 50%,
      color-mix(in srgb, var(--passkey-card-accent, var(--accent-color, #4d8ef0)) 28%, var(--border-color, rgba(255, 255, 255, 0.12)) 72%) 82%,
      transparent
    );
}

.passkey_card_meta > div {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  align-items: flex-start;
  padding-block: 0.35rem;
}

.passkey_card_meta > div + div {
  border-top: 1px solid color-mix(in srgb, var(--border-color, rgba(255, 255, 255, 0.12)) 78%, transparent);
}

.passkey_card_meta dt {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-weight: 600;
  color: color-mix(in srgb, var(--heading-color, #fff) 62%, transparent);
}
.passkey_card_meta_row--added dt::before,
.passkey_card_meta_row--last-used dt::before {
  content: '';
  flex: 0 0 0.82rem;
  width: 0.82rem;
  height: 0.82rem;
  background-color: currentColor;
  opacity: 0.68;
}

.passkey_card_meta_row--added dt::before {
  mask: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="black" d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM9 14H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2z"/></svg>') center / contain no-repeat;
  -webkit-mask: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="black" d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM9 14H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2z"/></svg>') center / contain no-repeat;
}

.passkey_card_meta_row--last-used dt::before {
  mask: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="black" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>') center / contain no-repeat;
  -webkit-mask: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="black" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>') center / contain no-repeat;
}

.passkey_card_meta dd {
  margin: 0;
  color: color-mix(in srgb, var(--heading-color, #fff) 84%, transparent);
}

.passkey_card_add {
  --passkey-card-accent: var(--accent-color, var(--color-primary, #4d8ef0));
  position: relative;
  align-items: center;
  justify-content: flex-start;
  text-align: center;
  padding-top: 2.65rem;
  border-style: dashed;
  background: color-mix(in srgb, var(--surface-color, #20252b) 92%, #fff 8%);
  cursor: pointer;
}

.passkey_card_add:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

.passkey_card_add[aria-disabled="true"] {
  cursor: not-allowed;
  opacity: 0.72;
}

html[data-depth="standard"] .passkey_card_add,
html[data-depth="high"] .passkey_card_add {
  border-color: color-mix(in srgb, var(--passkey-card-accent) 42%, var(--border-color, rgba(255, 255, 255, 0.12)) 58%);
  background:
    radial-gradient(ellipse 110% 75% at 50% -5%, color-mix(in srgb, var(--passkey-card-accent) 16%, transparent) 0%, transparent 58%),
    color-mix(in srgb, var(--surface-color, #20252b) 92%, #fff 8%);
  box-shadow:
    var(--depth-surface-shadow, none),
    0 0 0 1px color-mix(in srgb, var(--passkey-card-accent) 12%, var(--border-color, rgba(255, 255, 255, 0.12)) 88%),
    inset 0 1px 0 color-mix(in srgb, var(--panel-text, #fff) 6%, transparent);
}

html[data-depth="standard"] .passkey_card_add::before,
html[data-depth="high"] .passkey_card_add::before {
  opacity: 1;
  border-style: dashed;
  border-color: color-mix(in srgb, var(--passkey-card-accent) 28%, transparent);
  box-shadow:
    0 0 0 1px color-mix(in srgb, var(--border-color, rgba(255, 255, 255, 0.12)) 50%, transparent),
    0 0 18px color-mix(in srgb, var(--passkey-card-accent) 16%, transparent);
}

html[data-depth="high"] .passkey_card_add::before {
  box-shadow:
    0 0 0 1px color-mix(in srgb, var(--border-color, rgba(255, 255, 255, 0.12)) 50%, transparent),
    0 0 26px color-mix(in srgb, var(--passkey-card-accent) 24%, transparent);
}

html[data-depth="standard"] .passkey_card_add::after,
html[data-depth="high"] .passkey_card_add::after {
  opacity: 0.85;
  background:
    radial-gradient(circle at 50% 0, color-mix(in srgb, var(--passkey-card-accent) 68%, transparent) 0 0.24rem, transparent 0.25rem),
    radial-gradient(circle at 50% 100%, color-mix(in srgb, var(--passkey-card-accent) 52%, transparent) 0 0.2rem, transparent 0.21rem),
    radial-gradient(circle at 0.55rem 0.55rem, color-mix(in srgb, var(--passkey-card-accent) 42%, transparent) 0 0.16rem, transparent 0.17rem),
    radial-gradient(circle at calc(100% - 0.55rem) 0.55rem, color-mix(in srgb, var(--passkey-card-accent) 42%, transparent) 0 0.16rem, transparent 0.17rem);
}

.passkey_card_add:hover,
.passkey_card_add:focus-within {
  box-shadow: var(--depth-control-shadow-hover, var(--depth-surface-shadow, none));
}

html[data-depth="flat"] .passkey_card:hover,
html[data-depth="flat"] .passkey_card:focus-within,
html[data-depth="flat"] .passkey_card_add:hover,
html[data-depth="flat"] .passkey_card_add:focus-within {
  box-shadow: none;
  transform: none;
}

html[data-depth="low"] .passkey_card:hover,
html[data-depth="low"] .passkey_card:focus-within,
html[data-depth="low"] .passkey_card_add:hover,
html[data-depth="low"] .passkey_card_add:focus-within {
  transform: none;
}

.passkey_card_add_icon {
  position: absolute;
  top: -0.82rem;
  left: 50%;
  z-index: 2;
  display: grid;
  place-items: center;
  width: 2.7rem;
  height: 2.7rem;
  border: 1px dashed color-mix(in srgb, var(--passkey-card-accent) 58%, var(--border-color, rgba(255, 255, 255, 0.14)));
  border-radius: 50%;
  background:
    linear-gradient(180deg, color-mix(in srgb, var(--passkey-card-accent) 62%, transparent), color-mix(in srgb, var(--passkey-card-accent) 62%, transparent)) center -0.22rem / 1px 0.3rem no-repeat,
    linear-gradient(180deg, color-mix(in srgb, var(--passkey-card-accent) 62%, transparent), color-mix(in srgb, var(--passkey-card-accent) 62%, transparent)) center calc(100% + 0.22rem) / 1px 0.3rem no-repeat,
    linear-gradient(90deg, color-mix(in srgb, var(--passkey-card-accent) 62%, transparent), color-mix(in srgb, var(--passkey-card-accent) 62%, transparent)) -0.22rem center / 0.3rem 1px no-repeat,
    linear-gradient(90deg, color-mix(in srgb, var(--passkey-card-accent) 62%, transparent), color-mix(in srgb, var(--passkey-card-accent) 62%, transparent)) calc(100% + 0.22rem) center / 0.3rem 1px no-repeat,
    radial-gradient(circle at 35% 28%, color-mix(in srgb, var(--passkey-card-accent) 34%, #fff 8%), transparent 58%),
    radial-gradient(circle at 50% 68%, color-mix(in srgb, #000 16%, transparent) 0%, transparent 66%),
    color-mix(in srgb, var(--surface-color, #20252b) 74%, var(--passkey-card-accent) 26%);
  box-shadow:
    0 0 0 3px color-mix(in srgb, var(--surface-color, #20252b) 92%, transparent),
    0 0 0 4px color-mix(in srgb, var(--passkey-card-accent) 14%, transparent);
  font-size: 1.55rem;
  font-weight: 700;
  line-height: 1;
  color: var(--passkey-card-accent);
  transform: translateX(-50%);
}

html[data-depth="standard"] .passkey_card_add_icon,
html[data-depth="high"] .passkey_card_add_icon {
  box-shadow:
    0 0 0 3px color-mix(in srgb, var(--surface-color, #20252b) 92%, transparent),
    0 0 0 4px color-mix(in srgb, var(--passkey-card-accent) 18%, transparent),
    0 0 16px color-mix(in srgb, var(--passkey-card-accent) 28%, transparent);
}

html[data-depth="high"] .passkey_card_add_icon {
  box-shadow:
    0 0 0 3px color-mix(in srgb, var(--surface-color, #20252b) 92%, transparent),
    0 0 0 4px color-mix(in srgb, var(--passkey-card-accent) 22%, transparent),
    0 0 22px color-mix(in srgb, var(--passkey-card-accent) 34%, transparent);
}

html[data-depth="standard"] .passkey_card_add:hover,
html[data-depth="high"] .passkey_card_add:hover,
html[data-depth="standard"] .passkey_card_add:focus-within,
html[data-depth="high"] .passkey_card_add:focus-within {
}

.passkey_card_add_title {
  margin: 0;
  font-size: 1.02rem;
  font-weight: 700;
  color: var(--heading-color, inherit);
}

.passkey_card_add_text {
  margin: 0;
  max-width: 16rem;
  font-size: 0.86rem;
  line-height: 1.4;
  color: color-mix(in srgb, var(--heading-color, #fff) 72%, transparent);
}

.passkey_card_add .btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-top: auto;
  padding-inline: 1rem;
  pointer-events: none;
}

.passkey_card_add_btn_icon {
  display: grid;
  place-items: center;
  flex: 0 0 1.35rem;
  width: 1.35rem;
  height: 1.35rem;
  border: 1px solid color-mix(in srgb, currentColor 38%, transparent);
  border-radius: 50%;
  font-size: 0.95rem;
  font-weight: 700;
  line-height: 1;
}

.passkey_card_add_btn_label {
  line-height: 1.2;
}

.passkey_card_add.is-working .btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
}

.passkey_card_add.is-working .btn::after {
  content: '';
  width: 0.95rem;
  height: 0.95rem;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: settingsBusySpin 700ms linear infinite;
}

.passkey_card_add.is-success .btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
}

.passkey_card_add.is-success .btn::after {
  content: '';
  width: 0.9rem;
  height: 0.5rem;
  border-left: 2px solid currentColor;
  border-bottom: 2px solid currentColor;
  transform: rotate(-45deg);
  animation: settingsPasskeyCheckMorph 420ms ease-out both;
}

@media (max-width: 980px) {
  .passkey_card_grid {
    grid-template-columns: repeat(auto-fill, minmax(min(12rem, 100%), 1fr));
  }
}

@media (max-width: 560px) {
  .passkey_card_grid {
    grid-template-columns: minmax(0, 1fr);
  }
}

@media (prefers-reduced-motion: reduce) {
  .passkey_card,
  .passkey_card_add {
    transition: border-color 0.01ms ease, box-shadow 0.01ms ease, background-color 0.01ms ease;
  }

  html[data-depth="standard"] .passkey_card:hover,
  html[data-depth="standard"] .passkey_card:focus-within,
  html[data-depth="high"] .passkey_card:hover,
  html[data-depth="high"] .passkey_card:focus-within,
  html[data-depth="standard"] .passkey_card_add:hover,
  html[data-depth="high"] .passkey_card_add:hover,
  html[data-depth="standard"] .passkey_card_add:focus-within,
  html[data-depth="high"] .passkey_card_add:focus-within {
    transform: none;
  }
}

.passkey_credential_detail {
  font-size: 0.88em;
  opacity: 0.85;
}

.federated_provider_list {
  margin-top: 0.75rem;
  display: grid;
  gap: 0.55rem;
}

.federated_provider_row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 0.75rem;
}

.federated_provider_label {
  min-width: 0;
  overflow-wrap: anywhere;
  font-weight: 600;
}

@media (max-width: 560px) {
  .federated_provider_row {
    grid-template-columns: 1fr;
  }
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
  box-shadow: var(--depth-surface-shadow, none);
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
  font-size: var(--font-sm);
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

.settings_data_consent_panel {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.settings_data_consent_section {
  margin-top: 0;
}

.settings_data_consent_card_column .settings_data_consent_section + .settings_data_consent_section {
  margin-top: var(--gap-sm);
}

.settings_data_consent_section h3 {
  margin: 0 0 var(--gap-sm);
  font-size: 1rem;
  line-height: 1.25;
}

.settings_data_consent_counts {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin: 0;
  padding: 0;
}

.settings_data_consent_count {
  --consent-state-color: var(--panel-border);
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  min-height: 2rem;
  padding: 0.25rem 0.65rem;
  border: 1px solid color-mix(in srgb, var(--consent-state-color) 28%, var(--panel-border));
  border-radius: var(--border-radius);
  background: color-mix(in srgb, var(--consent-state-color) 9%, var(--panel-bg));
  font-size: var(--font-sm);
  box-shadow: var(--depth-control-shadow, none);
}

.settings_data_consent_count.is-active {
  --consent-state-color: var(--color-green, #22863a);
}

.settings_data_consent_count.is-waiting {
  --consent-state-color: var(--color-yellow, #b08800);
}

.settings_data_consent_count.is-setup {
  --consent-state-color: var(--color-blue, #1f6feb);
}

.settings_data_consent_count.is-revoked {
  --consent-state-color: var(--color-red, #a62929);
}

.settings_data_consent_list {
  display: flex;
  flex-direction: column;
  gap: var(--gap-sm);
  margin: 0;
  padding: 0;
  list-style: none;
}

.settings_data_consent_card_area {
  display: block;
}

.settings_data_consent_card_column {
  width: min(100%, 60rem);
}

.settings_data_consent_item {
  --consent-state-color: var(--panel-border);
  position: relative;
  display: grid;
  grid-template-columns: minmax(0, 1fr) max-content;
  gap: var(--gap-md);
  align-items: center;
  min-height: 0;
  padding: var(--pad-sm) var(--pad-md);
  overflow: hidden;
  border: 1px solid color-mix(in srgb, var(--consent-state-color) 30%, var(--panel-border));
  border-radius: var(--border-radius);
  background: color-mix(in srgb, var(--consent-state-color) 6%, var(--panel-bg));
  box-shadow: var(--depth-surface-shadow, inset 0 1px 0 color-mix(in srgb, var(--panel-text) 7%, transparent));
}

.settings_data_consent_item::before {
  content: '';
  position: absolute;
  inset: 0 auto 0 0;
  width: 4px;
  background: color-mix(in srgb, var(--consent-state-color) 76%, var(--panel-border));
}

.settings_data_consent_item.is-active {
  --consent-state-color: var(--color-green, #22863a);
}

.settings_data_consent_item.is-waiting {
  --consent-state-color: var(--color-yellow, #b08800);
}

.settings_data_consent_item.is-setup {
  --consent-state-color: var(--color-blue, #1f6feb);
}

.settings_data_consent_item.is-revoked {
  --consent-state-color: var(--color-red, #a62929);
}

.settings_data_consent_card_header {
  display: flex;
  flex-wrap: wrap;
  gap: var(--gap-sm);
  align-items: flex-start;
  justify-content: flex-start;
  min-width: 0;
}

.settings_data_consent_card_header strong {
  min-width: 0;
  max-width: 100%;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

.settings_data_consent_body p,
.settings_data_consent_empty {
  margin: 0.25rem 0 0;
  color: color-mix(in srgb, var(--panel-text) 82%, transparent);
}

.settings_data_consent_controls {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.45rem;
  justify-content: flex-end;
  margin-top: 0;
  min-width: 0;
}

.settings_data_consent_pill {
  --consent-state-color: var(--panel-border);
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  min-height: 2rem;
  padding: 0.25rem 0.6rem;
  border: 1px solid color-mix(in srgb, var(--consent-state-color) 48%, var(--panel-border));
  border-radius: var(--border-radius);
  background: color-mix(in srgb, var(--consent-state-color) 15%, var(--panel-bg));
  color: color-mix(in srgb, var(--consent-state-color) 68%, var(--panel-text));
  font-size: var(--font-sm);
  font-weight: 800;
  line-height: 1.2;
  white-space: nowrap;
}

.settings_data_consent_pill::before {
  content: '';
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 999px;
  background: currentColor;
  flex: 0 0 auto;
}

.settings_data_consent_empty_state,
.settings_data_consent_past {
  padding: var(--pad-sm);
  border: 1px solid color-mix(in srgb, var(--panel-border) 68%, transparent);
  border-radius: var(--border-radius);
  background: color-mix(in srgb, var(--panel-bg) 96%, var(--panel-border));
}

.settings_data_consent_empty_state h3,
.settings_data_consent_empty_state p {
  margin: 0;
}

.settings_data_consent_empty_state p {
  margin-top: 0.3rem;
  color: color-mix(in srgb, var(--panel-text) 80%, transparent);
}

.settings_data_consent_past summary {
  display: grid;
  grid-template-columns: max-content minmax(0, 1fr) max-content;
  gap: var(--gap-sm);
  align-items: center;
  cursor: pointer;
}

.settings_data_consent_past summary small {
  color: color-mix(in srgb, var(--panel-text) 74%, transparent);
}

.settings_data_consent_past summary strong {
  min-width: 2rem;
  min-height: 2rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  background: var(--panel-bg);
}

.settings_data_consent_pill.is-active {
  --consent-state-color: var(--color-green, #22863a);
}

.settings_data_consent_pill.is-waiting {
  --consent-state-color: var(--color-yellow, #b08800);
}

.settings_data_consent_pill.is-setup {
  --consent-state-color: var(--color-blue, #1f6feb);
}

.settings_data_consent_pill.is-revoked {
  --consent-state-color: var(--color-red, #a62929);
}

.settings_data_consent_action.is-revoke {
  border-color: color-mix(in srgb, var(--color-red, #a62929) 45%, var(--panel-border));
  color: color-mix(in srgb, var(--color-red, #a62929) 70%, var(--panel-text));
}

.settings_data_consent_action.is-revoke:hover,
.settings_data_consent_action.is-revoke:focus-visible {
  background: color-mix(in srgb, var(--color-red, #a62929) 16%, var(--panel-bg));
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
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.25rem;
  margin-bottom: var(--gap-sm);
  font-size: 0.9rem;
}

#panel-data-portability .data_portability_meta > div {
  display: grid;
  grid-template-columns: minmax(7.5rem, 38%) minmax(0, 1fr);
  gap: 0.35rem;
  align-items: baseline;
}

#panel-data-portability .data_portability_meta strong {
  color: color-mix(in srgb, var(--panel-text, #fff) 82%, transparent);
  font-size: var(--font-xs, 0.75rem);
  letter-spacing: 0;
}

#panel-data-portability .data_portability_meta span {
  display: block;
  min-width: 0;
  text-align: left;
  word-break: break-word;
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
  font-size: var(--font-sm);
  color: color-mix(in srgb, var(--panel-text) 72%, transparent);
  margin: 0;
  line-height: 1.35;
}

.code_input {
  max-width: 150px;
}

.mt_8 {
  margin-top: 8px;
}

#main > section.panel {
  width: var(--app-content-width, 100%);
  margin-left: 0;
  margin-right: 0;
  padding: var(--settings-card-padding-block) var(--settings-card-padding-inline);
  flex: 0 0 auto;
  min-width: 0;
  box-shadow: var(--depth-panel-shadow, none);
}

#main > section.panel.settings_card_group + section.panel.settings_card_group {
  margin-top: 0;
}

/* Tablet and down: hide sidebar, let panels reflow based on available width */
@media (max-width: 900px) {
  .settings_workspace {
    max-width: 100%;
  }

  #main > section.panel {
    width: min(92vw, 1240px);
    min-width: 0;
    padding: var(--gap-md) var(--gap-lg);
  }

  .settings_dashboard_grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .settings_data_consent_controls {
    justify-content: flex-start;
  }

  #panel-account .account_details_grid {
    gap: var(--gap-md);
  }

  #main section.panel form > .flex.f_baseline.w100 > select.w50,
  #main section.panel form > .flex.f_baseline.w100 > select.w25,
  #main section.panel form > .flex.f_baseline.w100 > .w25:not(label) {
    grid-column: 1 / -1;
  }

  #panel-calendar .radio_group,
  #panel-style .radio_group,
  #panel-audio .radio_group,
  #panel-calendar .calendar_badge_pills {
    flex-wrap: wrap;
    gap: var(--gap-sm);
  }

  #panel-calendar .radio_group .radio + label,
  #panel-style .radio_group .radio + label,
  #panel-audio .radio_group .radio + label,
  #panel-calendar .calendar_badge_pills .work_entry_field + label {
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

  .businesses_row {
    flex-wrap: wrap;
  }

  .businesses_row_compact .btn {
    width: 100%;
  }

  .businesses_discovery_row {
    flex-direction: column;
    align-items: stretch;
  }

  .businesses_discovery_actions {
    width: 100%;
  }

  .businesses_discovery_actions .btn {
    width: 100%;
  }

  .businesses_scope_grid,
  .businesses_defaults_grid {
    grid-template-columns: 1fr;
  }

  #panel-data-portability .data_portability_grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 1100px) {
  .settings_theme_grid {
    grid-template-columns: repeat(10, minmax(0, 1fr));
  }

  .settings_accent_swatches {
    grid-template-columns: repeat(20, minmax(0, 1fr));
  }
}

@media (max-width: 860px) {
  .settings_theme_grid {
    grid-template-columns: repeat(8, minmax(0, 1fr));
  }

  .settings_accent_swatches {
    grid-template-columns: repeat(16, minmax(0, 1fr));
  }
}

@media (max-width: 620px) {
  .settings_theme_grid {
    grid-template-columns: repeat(6, minmax(0, 1fr));
  }

  .settings_accent_swatches {
    grid-template-columns: repeat(12, minmax(0, 1fr));
  }

  .settings_theme_header {
    flex-wrap: wrap;
  }

  .settings_mode_toggle {
    width: 100%;
  }

  .settings_mode_option {
    flex: 1 1 0;
    width: auto;
  }
}

/* Mobile: single-column panels and stacked form rows */
@media (max-width: 768px) {
  #main {
    gap: var(--gap-md);
  }

  #main > section.panel {
    width: 100%;
    min-width: 0;
    max-width: 100%;
    padding: var(--gap-md);
    margin-left: 0;
    margin-right: 0;
    overflow-wrap: anywhere;
  }

  .settings_jump_nav {
    padding: var(--gap-xs) var(--gap-sm);
    gap: var(--gap-xs);
  }

  .settings_context_header {
    flex-direction: column;
    align-items: center;
    gap: var(--gap-xs);
    margin-bottom: var(--gap-xs);
  }

  .settings_context_separator {
    display: none;
  }

  .settings_subnav {
    width: 100%;
  }

  .settings_subnav_tabs {
    width: 100%;
    justify-content: center;
    gap: 0;
  }

  .settings_subnav_tab {
    padding: 0.4rem 0.65rem;
    font-size: 0.8125rem;
  }

  #main section.panel form {
    gap: var(--gap-md);
    min-width: 0;
    max-width: 100%;
  }

  #main section.panel form > br {
    display: none;
  }

  #main section.panel form > .flex.f_baseline.w100 {
    gap: var(--settings-form-label-gap);
    padding: var(--gap-xs) 0;
  }

  #main section.panel form > .flex.f_baseline.w100 > label.w25 {
    font-weight: 600;
    margin-top: 0;
    text-align: left;
    white-space: normal;
  }

  #main section.panel form > .flex.f_baseline.w100 > .w75 > .radio_group,
  #main section.panel form > .flex.f_baseline.w100 > .w75 > .radio_group.pill_group,
  #main section.panel form > .flex.f_baseline.w100 > .w75 > .calendar_badge_pills,
  #main section.panel form > .flex.f_baseline.w100 > .w75 > .security_slider_row,
  #main section.panel form > .flex.f_baseline.w100 > .w75 > .proximity_slider_wrap,
  #main section.panel form > .flex.f_baseline.w100 > .w75 > .settings_accent_swatches,
  #main section.panel form > .flex.f_baseline.w100 > .w75 > select,
  #main section.panel form > .flex.f_baseline.w100 > .w75 > input,
  #main section.panel form > .flex.f_baseline.w100 > .w75 > textarea {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
  }

  .settings_accent_preview_body {
    grid-template-columns: minmax(0, 1fr);
  }

  #main section.panel form > .settings_theme_mode_row {
    grid-template-columns: minmax(0, 1fr);
  }

  .settings_theme_header {
    align-items: flex-start;
  }

  .settings_mode_toggle {
    flex-shrink: 1;
  }

  .item_pair {
    gap: var(--settings-form-label-gap);
    padding: var(--gap-xs) 0;
  }

  .item_pair .item_label {
    margin-top: 0;
  }

  .item_pair .item_value {
    margin-left: 0;
    width: 100%;
  }

  #panel-style .radio_group.pill_group,
  #panel-calendar .radio_group.pill_group,
  #panel-calendar .calendar_badge_pills,
  #panel-debugging .radio_group.pill_group,
  #main section.panel.settings_card_group .radio_group.pill_group,
  #main section.panel .radio_group.pill_group,
  #panel-audio .radio_group {
    width: 100%;
    min-width: 0;
  }

  #panel-style .radio_group.pill_group .radio + label,
  #panel-calendar .radio_group.pill_group .radio + label,
  #panel-debugging .radio_group.pill_group .radio + label,
  #main section.panel.settings_card_group .radio_group.pill_group .radio + label,
  #main section.panel .radio_group.pill_group .radio + label {
    min-width: 0;
    flex: 1 1 0;
  }

  #panel-calendar .calendar_badge_pills .work_entry_field + label {
    flex: 0 0 auto;
    min-width: 0;
  }

  .overlay_collapse_row {
    flex-direction: column;
    align-items: stretch;
    gap: var(--gap-sm);
    margin-top: var(--gap-sm);
    padding-top: var(--gap-sm);
  }

  .overlay_collapse_label {
    min-width: 0;
  }

  #panel-security form {
    gap: var(--gap-md);
  }

  #panel-security .security_level_card {
    margin-top: var(--gap-sm);
    padding: var(--gap-sm) var(--gap-md);
  }

  #panel-security .security_datagrid_table th,
  #panel-security .security_datagrid_table td {
    padding: 0.4rem 0.35rem;
    font-size: 0.85em;
  }

  /* Hide tab list on mobile */
  /* Ensure tab content takes full width on mobile */
  #main dialog {
    width: 90vw;
    height: auto;
    max-height: 80dvh;
  }

  #panel-account .account_details_grid {
    gap: var(--gap-md);
  }

  #panel-account .details_column_profile,
  #panel-account .details_column_employment,
  #panel-account .details_column_tax {
    grid-template-columns: minmax(0, 1fr);
  }

  .security_slider_row {
    grid-template-columns: auto 1fr auto;
    gap: var(--gap-sm);
  }

  .security_slider_row_compact .security_slider_edge {
    text-align: center;
    font-size: 0.85em;
  }
}

@media (max-width: 420px) {
  #main {
    box-sizing: border-box;
    padding-inline: 5px;
  }

  .settings_accent_swatches {
    grid-template-columns: repeat(8, minmax(0, 1fr));
  }

  .settings_theme_grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 520px) {
  #panel-account .account_details_grid {
    gap: 0.85rem;
  }

  #panel-account .details_column {
    gap: 0.55rem;
    padding: 0.72rem;
  }

  #panel-account .details_column_title {
    font-size: 0.95rem;
    line-height: 1.15;
  }

  #panel-account .details_column_profile .item_pair,
  #panel-account .details_column_employment .item_pair {
    gap: 0.22rem;
    padding: 0;
  }

  #panel-account .details_column_profile .item_label,
  #panel-account .details_column_employment .item_label {
    font-size: 0.76rem;
    line-height: 1.15;
  }

  #panel-account .details_column_profile .item_value,
  #panel-account .details_column_employment .item_value {
    gap: 0.25rem;
  }

  #panel-account .details_column_profile .item_value input,
  #panel-account .details_column_profile .item_value select,
  #panel-account .details_column_employment .item_value input,
  #panel-account .details_column_employment .item_value select {
    min-height: 2.18rem;
    padding-block: 0.36rem;
    padding-inline: 0.82rem;
  }

  #panel-account .item_value.flex.f_baseline {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 0.35rem;
  }

  #panel-account #edit_details_email:disabled {
    min-height: auto;
    padding: 0.1rem 0;
  }

  #panel-account .email_change_link,
  #panel-account .email_change_link_disabled {
    margin-left: 0;
    padding: 0.2rem 0.4rem;
    font-size: 0.76rem;
  }

  #panel-account #recovery_email_status {
    gap: 0.2rem;
  }

  #panel-account #recovery_email_status_display {
    line-height: 1.25;
  }

  #panel-account #recovery_email_input_section,
  #panel-account #recovery_email_verify_section {
    margin-top: -0.2rem;
  }

  #panel-account #recovery_email_input_section > .item_label,
  #panel-account #recovery_email_verify_section > .item_label {
    display: none;
  }

  #panel-account .recovery_email_input_row {
    gap: 0.3rem;
  }

  #panel-account #recovery_email_verify_section > .item_value {
    display: grid;
    grid-template-columns: minmax(5.8rem, 0.8fr) minmax(7rem, 1fr);
    gap: 0.3rem;
    align-items: center;
  }

  #panel-account #recovery_email_code_input {
    min-width: 0;
    width: 100%;
  }

  #panel-account .recovery_email_input_row #recovery_email_send_btn,
  #panel-account #recovery_email_verify_btn {
    min-height: 2.18rem;
    padding: 0.35rem 0.45rem;
    margin-top: 0;
  }

  #panel-account .recovery_email_input_row #recovery_email_send_btn {
    padding-inline: 0.62rem;
  }

  #panel-account #recovery_email_verify_status,
  #panel-account #recovery_email_expiry_timer,
  #panel-account #recovery_email_code_error {
    grid-column: 1 / -1;
  }

  #panel-account .status_message,
  #panel-account .status_text.compact_hint {
    min-height: 0;
    line-height: 1.2;
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

.account_activity_session_meta_label {
  color: color-mix(in srgb, var(--panel-text, #fff) 82%, transparent);
  font-size: var(--font-xs, 0.75rem);
  letter-spacing: 0;
}

.account_activity_session_meta_value {
  display: block;
  justify-items: start;
  text-align: left;
  margin: 0;
  word-break: break-word;
  color: var(--panel-text, var(--text, #fff));
}

.account_activity_sessions {
  display: grid;
  gap: var(--gap-sm);
  margin-top: var(--gap-sm);
}

.account_activity_session_item {
  display: grid;
  gap: 0.55rem;
  border: 1px solid var(--line, rgba(255, 255, 255, 0.1));
  border-radius: 10px;
  padding: 0.6rem 0.75rem;
  background: rgba(255, 255, 255, 0.02);
}

.account_activity_session_title {
  display: block;
  min-width: 0;
  color: var(--panel-text, var(--text, #fff));
  font-size: var(--font-sm);
  line-height: 1.25;
  overflow-wrap: anywhere;
}

.account_activity_session_item_current {
  border-color: rgba(64, 201, 132, 0.7);
  box-shadow: inset 0 0 0 1px rgba(64, 201, 132, 0.35);
}

.account_activity_session_meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 13rem), 1fr));
  gap: 0.45rem 0.65rem;
  font-size: var(--font-sm);
}

.account_activity_session_meta_segment {
  grid-template-columns: minmax(5.5rem, 42%) minmax(0, 1fr);
  gap: 0.3rem;
  align-items: baseline;
  min-width: 0;
  padding: 0;
}

.account_activity_timestamp_field {
  position: relative;
  display: inline-block;
  max-width: 100%;
  text-align: left;
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
  text-align: left;
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
  box-shadow: var(--depth-dialog-shadow, 0 16px 36px rgba(0, 0, 0, 0.34));
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

/* Profile uses businesses finder JS, so mirror finder styles here. */
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
  min-height: var(--settings-control-height, 2.75rem);
  padding: 0.5rem 0.75rem;
  background: var(--panel-input-bg, var(--color-surface-strong, #1a1a1a));
  color: var(--panel-text, var(--fore, #e0e0e0));
  border: 1px solid var(--panel-border, var(--fore-dark, #2a2a2a));
  border-radius: var(--radius-input, var(--border-radius));
  font-size: var(--font-sm, 0.875rem);
  outline: none;
  box-sizing: border-box;
  text-align: left;
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
  font-family: var(--monospace, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace);
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
  font-family: var(--monospace, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace);
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
  .account_activity_session_meta {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 520px) {
  .account_activity_session_meta {
    grid-template-columns: 1fr;
  }
}

.settings_sessions_list,
.settings_export_history_list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.settings_sessions_list li,
.settings_export_history_list li {
  padding: 0.65rem 0;
  border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
}

.settings_sessions_list li.is_current {
  font-weight: 600;
}

.settings_recovery_key_badge {
  display: inline-block;
  margin: 0;
  padding: 0.35rem 0.65rem;
  border-radius: var(--radius-sm, 0.25rem);
  background: color-mix(in srgb, var(--accent-color, #4d8ef0) 18%, transparent);
  color: var(--fore, #e0e0e0);
}

.settings_recovery_key_badge.is-visible {
  display: inline-block;
}

.settings_export_sections_fieldset {
  border: 0;
  margin: 0 0 1rem;
  padding: 0;
}

.settings_card_group--danger {
  border-color: color-mix(in srgb, var(--danger-color, #c0392b) 35%, var(--border-color, rgba(255, 255, 255, 0.08)));
  box-shadow: var(--depth-panel-shadow, none), 0 0 0 1px color-mix(in srgb, var(--danger-color, #c0392b) 18%, transparent);
}

.settings_diagnostics_argus_link_row {
  margin-top: 1rem;
}

.subscription_command_center {
  max-width: var(--app-content-width, 100%);
  margin-inline: 0;
}

.subscription_plan_card {
  border: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));
  border-radius: var(--radius-md, 6px);
  background: var(--surface, rgba(255, 255, 255, 0.04));
}

.subscription_plan_card {
  padding: 1rem;
  border-color: color-mix(in srgb, var(--color-primary, #4d8ef0) 38%, var(--border-color, rgba(255, 255, 255, 0.1)));
}

#panel-billing [hidden] {
  display: none !important;
}

.business-upgrade-status {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  margin: 0.85rem 0 0;
  padding: 0.55rem 0.75rem;
  border: 1px solid rgba(129, 92, 255, 0.35);
  border-radius: var(--radius-sm, 4px);
  background:
    linear-gradient(90deg, rgba(129, 92, 255, 0.10), rgba(255, 78, 138, 0.10), rgba(255, 217, 102, 0.10), rgba(82, 213, 255, 0.10));
  color: color-mix(in srgb, var(--panel-text, #f4f4f4) 88%, #9f7cff);
  font-size: var(--font-sm, 0.875rem);
  font-weight: 700;
  line-height: 1.2;
  text-align: center;
}

.business-upgrade-status[hidden] {
  display: none;
}

.business-upgrade-status.is-highlighted {
  animation: businessStatusLandingGlow 2.2s ease-out both;
}

#business-upgrade-wormhole {
  --upgrade-target-x: 50vw;
  --upgrade-target-y: 50vh;
  --upgrade-target-width: 16rem;
  --upgrade-target-height: 2.75rem;
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 9999;
  overflow: clip;
  opacity: 0;
  background:
    radial-gradient(ellipse at top, rgba(22, 39, 54, 0.24), transparent 55%),
    radial-gradient(ellipse at bottom, rgba(11, 16, 24, 0.22), transparent 58%);
  contain: layout paint style;
  mix-blend-mode: screen;
}

.wormhole-strand,
.wormhole-core,
.wormhole-flash {
  position: absolute;
  left: 50%;
  top: 50%;
  width: 142vmax;
  height: 142vmax;
  border-radius: 50%;
  pointer-events: none;
  transform: translate(-50%, -50%) scale(1);
  transform-origin: center;
  opacity: 0;
  will-change: transform, opacity, filter;
}

.wormhole-strand {
  -webkit-mask-image:
    radial-gradient(circle, transparent 0 18%, #000 28% 43%, transparent 56%),
    conic-gradient(from 0deg, transparent 0 18deg, #000 24deg 56deg, transparent 72deg 138deg, #000 150deg 178deg, transparent 196deg 360deg);
  mask-image:
    radial-gradient(circle, transparent 0 18%, #000 28% 43%, transparent 56%),
    conic-gradient(from 0deg, transparent 0 18deg, #000 24deg 56deg, transparent 72deg 138deg, #000 150deg 178deg, transparent 196deg 360deg);
  -webkit-mask-composite: source-in;
  mask-composite: intersect;
}

.strand-1 {
  background:
    conic-gradient(from 22deg, transparent, rgba(91, 182, 255, 0.50), transparent 21%, rgba(139, 92, 246, 0.46), transparent 44%, rgba(45, 212, 191, 0.32), transparent 70%);
  filter: blur(13px) saturate(1.22);
}

.strand-2 {
  background:
    conic-gradient(from 188deg, transparent, rgba(245, 196, 92, 0.34), transparent 18%, rgba(56, 189, 248, 0.42), transparent 42%, rgba(167, 139, 250, 0.28), transparent 76%);
  filter: blur(20px) saturate(1.16);
  width: 128vmax;
  height: 128vmax;
}

.strand-3 {
  background:
    conic-gradient(from 308deg, transparent, rgba(34, 211, 238, 0.40), transparent 15%, rgba(17, 24, 39, 0.20), transparent 30%, rgba(196, 181, 253, 0.40), transparent 64%, rgba(250, 204, 21, 0.20), transparent 82%);
  filter: blur(9px) saturate(1.32);
  width: 116vmax;
  height: 116vmax;
}

.strand-4 {
  background:
    radial-gradient(circle at 22% 48%, rgba(59, 130, 246, 0.34), transparent 15%),
    radial-gradient(circle at 78% 42%, rgba(45, 212, 191, 0.28), transparent 14%),
    radial-gradient(circle at 50% 18%, rgba(245, 196, 92, 0.22), transparent 16%),
    conic-gradient(from 90deg, transparent, rgba(15, 23, 42, 0.32), transparent 28%, rgba(139, 92, 246, 0.24), transparent 62%);
  filter: blur(28px) saturate(1.08);
  width: 150vmax;
  height: 150vmax;
}

.wormhole-core {
  left: var(--upgrade-target-x, 50vw);
  top: var(--upgrade-target-y, 50vh);
  width: max(var(--upgrade-target-width, 16rem), 14rem);
  height: max(var(--upgrade-target-height, 3rem), 3rem);
  background:
    radial-gradient(ellipse at center, rgba(255, 255, 255, 0.55), rgba(125, 211, 252, 0.30) 20%, rgba(139, 92, 246, 0.20) 46%, transparent 72%);
  filter: blur(8px) saturate(1.2);
  transform: translate(-50%, -50%) scale(0.1);
}

.wormhole-flash {
  left: var(--upgrade-target-x, 50vw);
  top: var(--upgrade-target-y, 50vh);
  width: max(var(--upgrade-target-width, 16rem), 12rem);
  height: max(var(--upgrade-target-height, 3rem), 3rem);
  border-radius: 999px;
  background:
    radial-gradient(ellipse at center, rgba(255, 255, 255, 0.42), rgba(245, 196, 92, 0.18) 32%, transparent 72%);
  filter: blur(4px);
  transform: translate(-50%, -50%) scale(0.25);
}

body.business-upgrade-celebrate #business-upgrade-wormhole {
  animation: businessWormholeOverlay 6s ease-out forwards;
}

body.business-upgrade-celebrate .strand-1 {
  animation: businessWormholeStrandOne 6s cubic-bezier(0.22, 0.86, 0.23, 1) forwards;
}

body.business-upgrade-celebrate .strand-2 {
  animation: businessWormholeStrandTwo 6s cubic-bezier(0.18, 0.78, 0.26, 1) forwards;
}

body.business-upgrade-celebrate .strand-3 {
  animation: businessWormholeStrandThree 6s cubic-bezier(0.2, 0.82, 0.22, 1) forwards;
}

body.business-upgrade-celebrate .strand-4 {
  animation: businessWormholeCloud 6s ease-out forwards;
}

body.business-upgrade-celebrate .wormhole-core {
  animation: businessWormholeCore 6s ease-out forwards;
}

body.business-upgrade-celebrate .wormhole-flash {
  animation: businessWormholeFlash 6s ease-out forwards;
}

@keyframes businessWormholeOverlay {
  0% {
    opacity: 0;
  }

  18% {
    opacity: 1;
  }

  78% {
    opacity: 0.92;
  }

  100% {
    opacity: 0;
  }
}

@keyframes businessWormholeStrandOne {
  0% {
    opacity: 0;
    transform: translate(-50%, -50%) scale(1.18) rotate(0deg);
  }

  24% {
    opacity: 0.78;
  }

  68% {
    opacity: 0.62;
    transform: translate(-50%, -50%) scale(0.72) rotate(420deg);
  }

  100% {
    opacity: 0;
    transform:
      translate3d(
        calc(var(--upgrade-target-x, 50vw) - 50vw),
        calc(var(--upgrade-target-y, 50vh) - 50vh),
        0
      )
      translate(-50%, -50%)
      scale(0.08)
      rotate(620deg);
  }
}

@keyframes businessWormholeStrandTwo {
  0% {
    opacity: 0;
    transform: translate(-50%, -50%) scale(1.05) rotate(0deg);
  }

  20% {
    opacity: 0.58;
  }

  70% {
    opacity: 0.50;
    transform: translate(-50%, -50%) scale(0.66) rotate(-360deg);
  }

  100% {
    opacity: 0;
    transform:
      translate3d(
        calc(var(--upgrade-target-x, 50vw) - 50vw),
        calc(var(--upgrade-target-y, 50vh) - 50vh),
        0
      )
      translate(-50%, -50%)
      scale(0.10)
      rotate(-560deg);
  }
}

@keyframes businessWormholeStrandThree {
  0% {
    opacity: 0;
    transform: translate(-50%, -50%) scale(0.96) rotate(0deg);
  }

  18% {
    opacity: 0.70;
  }

  72% {
    opacity: 0.52;
    transform: translate(-50%, -50%) scale(0.58) rotate(540deg);
  }

  100% {
    opacity: 0;
    transform:
      translate3d(
        calc(var(--upgrade-target-x, 50vw) - 50vw),
        calc(var(--upgrade-target-y, 50vh) - 50vh),
        0
      )
      translate(-50%, -50%)
      scale(0.09)
      rotate(760deg);
  }
}

@keyframes businessWormholeCloud {
  0% {
    opacity: 0;
    transform: translate(-50%, -50%) scale(1.15) rotate(0deg);
  }

  26% {
    opacity: 0.46;
  }

  72% {
    opacity: 0.30;
    transform: translate(-50%, -50%) scale(0.78) rotate(180deg);
  }

  100% {
    opacity: 0;
    transform:
      translate3d(
        calc(var(--upgrade-target-x, 50vw) - 50vw),
        calc(var(--upgrade-target-y, 50vh) - 50vh),
        0
      )
      translate(-50%, -50%)
      scale(0.18)
      rotate(260deg);
  }
}

@keyframes businessWormholeCore {
  0%,
  62% {
    opacity: 0;
    transform: translate(-50%, -50%) scale(0.1);
  }

  76% {
    opacity: 0.62;
    transform: translate(-50%, -50%) scale(2.8);
  }

  100% {
    opacity: 0;
    transform: translate(-50%, -50%) scale(0.86);
  }
}

@keyframes businessWormholeFlash {
  0%,
  76% {
    opacity: 0;
    transform: translate(-50%, -50%) scale(0.35);
  }

  86% {
    opacity: 0.44;
    transform: translate(-50%, -50%) scale(1.08);
  }

  100% {
    opacity: 0;
    transform: translate(-50%, -50%) scale(1.34);
  }
}

@keyframes businessStatusLandingGlow {
  0% {
    border-color: rgba(129, 92, 255, 0.35);
    box-shadow: none;
  }

  30% {
    border-color: rgba(82, 213, 255, 0.76);
    background:
      linear-gradient(90deg, rgba(129, 92, 255, 0.16), rgba(82, 213, 255, 0.14), rgba(245, 196, 92, 0.12));
    box-shadow:
      0 0 24px rgba(129, 92, 255, 0.32),
      0 0 46px rgba(82, 213, 255, 0.22),
      inset 0 0 22px rgba(255, 255, 255, 0.11);
  }

  100% {
    border-color: rgba(129, 92, 255, 0.35);
    background:
      linear-gradient(90deg, rgba(129, 92, 255, 0.10), rgba(255, 78, 138, 0.10), rgba(255, 217, 102, 0.10), rgba(82, 213, 255, 0.10));
    box-shadow: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  body.business-upgrade-celebrate #business-upgrade-wormhole {
    animation: businessWormholeReducedMotion 2.2s ease-out forwards;
  }

  body.business-upgrade-celebrate .wormhole-strand,
  body.business-upgrade-celebrate .wormhole-core,
  body.business-upgrade-celebrate .wormhole-flash {
    animation: none;
    opacity: 0;
  }

  .business-upgrade-status.is-highlighted {
    animation: businessStatusLandingGlow 1.6s ease-out both;
  }

  @keyframes businessWormholeReducedMotion {
    0%,
    100% {
      opacity: 0;
    }

    30%,
    70% {
      opacity: 0.7;
    }
  }
}

.subscription_kicker {
  margin: 0 0 0.45rem;
  color: var(--color-primary, #4d8ef0);
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
}

.subscription_plan_card h3 {
  margin: 0 0 0.25rem;
  font-size: 1.4rem;
}

.subscription_status_line {
  margin: 0 0 0.75rem;
  font-weight: 700;
}

.subscription_facts {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.5rem;
  margin: 0.75rem 0 0;
}

.subscription_facts div {
  display: grid;
  grid-template-columns: minmax(5.5rem, 38%) minmax(0, 1fr);
  gap: 0.35rem;
  align-items: baseline;
  padding: 0.6rem;
  border-radius: var(--radius-sm, 4px);
  background: rgba(255, 255, 255, 0.035);
}

.subscription_facts dt {
  margin: 0;
  color: var(--fore-muted, var(--text-muted, #aaa));
  font-size: 0.78rem;
}

.subscription_facts dd {
  display: block;
  min-width: 0;
  margin: 0;
  font-weight: 700;
  text-align: left;
  word-break: break-word;
}

.subscription_actions {
  margin-top: 0.85rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

.subscription_downgrade_dialog {
  width: min(92vw, 480px);
  padding: 0;
  border: 1px solid color-mix(in srgb, var(--danger-color, #c0392b) 46%, var(--border-color, rgba(255, 255, 255, 0.1)));
  border-radius: var(--radius-md, 6px);
  background: var(--panel-bg, #1e2227);
  color: var(--panel-text, #f4f4f4);
  box-shadow: var(--depth-dialog-shadow, 0 24px 80px rgba(0, 0, 0, 0.42));
}

.subscription_downgrade_dialog::backdrop {
  background: rgba(0, 0, 0, 0.58);
}

.subscription_downgrade_dialog form {
  margin: 0;
  padding: 1rem;
}

.subscription_downgrade_dialog h3 {
  margin: 0 0 0.55rem;
  color: var(--danger-color, #ff6b6b);
  font-size: 1rem;
}

.subscription_downgrade_dialog p {
  margin: 0;
  color: color-mix(in srgb, var(--panel-text, #f4f4f4) 82%, transparent);
  line-height: 1.45;
}

.subscription_dialog_actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.6rem;
  margin-top: 1rem;
}

.subscription_danger_zone {
  margin-top: 0.85rem;
  border: 1px solid color-mix(in srgb, var(--danger-color, #c0392b) 42%, transparent);
  border-radius: var(--radius-md, 6px);
  background: color-mix(in srgb, var(--danger-color, #c0392b) 10%, transparent);
}

.subscription_danger_zone summary {
  padding: 0.75rem 1rem;
  cursor: pointer;
  color: var(--danger-color, #ff6b6b);
  font-weight: 700;
}

.subscription_danger_body {
  padding: 0 1rem 1rem;
}

.subscription_danger_body h3 {
  margin: 0 0 0.45rem;
}

@media (max-width: 640px) {
  .settings_dashboard_grid {
    grid-template-columns: 1fr;
  }

  .settings_dashboard_card {
    min-height: 0;
  }

  .settings_data_consent_item .btn {
    width: 100%;
    justify-content: center;
  }

  .settings_data_consent_item {
    grid-template-columns: minmax(0, 1fr);
    align-items: stretch;
  }

  .settings_data_consent_controls {
    width: 100%;
    align-items: stretch;
    justify-content: flex-start;
  }

  .subscription_facts {
    grid-template-columns: 1fr;
  }

  .subscription_actions {
    display: grid;
  }

  .subscription_command_center .btn {
    width: 100%;
    justify-content: center;
  }
}
