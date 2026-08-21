<?php declare(strict_types=1); ?>
/* Site editor component. */

/* Orphaned Work Recovery Styles                                             */
/* ========================================================================== */
.modal_orphaned_work .modal_content {
  padding-bottom: 4rem;
}

/* Site editor dialogs: full-window layout with stable header/body/footer rows. */
.sites_modal_create,
.sites_modal_edit {
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

.sites_modal_create[open],
.sites_modal_edit[open] {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  grid-template-rows: minmax(0, 1fr);
}

.sites_modal_create > form,
.sites_modal_edit > form {
  grid-column: 1;
  grid-row: 1;
  display: grid;
  grid-template-rows: auto minmax(0, 1fr) auto;
  min-width: 0;
  min-height: 0;
}

.site_editor_owner_disclaimer {
  margin: 0 clamp(1rem, 2vw, 1.5rem) clamp(0.6rem, 1vw, 0.85rem);
  padding: 0.75rem 1rem;
  border: 1px solid color-mix(in srgb, var(--panel-border) 82%, var(--accent-color, var(--sites-accent-default)) 18%);
  border-left: 3px solid var(--accent-color, var(--color-accent, var(--sites-accent-default)));
  border-radius: var(--radius-control, 6px);
  background: color-mix(in srgb, var(--panel-bg) 86%, var(--accent-color, var(--sites-accent-default)) 14%);
  color: var(--panel-text);
}

.site_editor_owner_disclaimer_label {
  display: block;
  margin-bottom: 0.2rem;
  color: color-mix(in srgb, var(--panel-text) 72%, var(--panel-bg));
  font-size: 0.78rem;
  font-weight: 800;
  text-transform: uppercase;
}

.site_editor_owner_disclaimer_text {
  font-weight: 800;
}

.sites_modal_create .modal_title,
.sites_modal_edit .modal_title {
  grid-column: 1;
  justify-self: start;
  margin: 0;
  padding: 0;
  text-align: left !important;
}

.sites_modal_create .modal_header .btn_close,
.sites_modal_edit .modal_header .btn_close {
  grid-column: 2;
  justify-self: end;
}

.sites_modal_create .modal_content,
.sites_modal_edit .modal_content {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  align-items: stretch;
  gap: 0;
  padding: 0;
  overflow: hidden;
  min-height: 0;
}

.sites_modal_create .modal_content {
  display: block;
  align-content: start;
  gap: 0.9rem;
  padding: 1.25rem 1rem 7rem;
  overflow-y: auto;
  scroll-padding-bottom: 7rem;
}

.sites_modal_create .site_create_workspace {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  width: min(100%, 68rem);
  margin: 0 auto;
}

.sites_modal_create .site_create_card {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 1rem;
  min-width: 0;
  padding: 1rem;
  border: 1px solid var(--panel-border);
  border-radius: 8px;
  background: color-mix(in srgb, var(--panel-bg) 96%, var(--panel-text) 4%);
  transition:
    background-color 140ms ease,
    border-color 140ms ease,
    box-shadow 140ms ease;
}

.sites_modal_create .site_create_card:focus-within {
  background: color-mix(in srgb, var(--color-primary) 8%, var(--panel-bg));
  box-shadow: inset 3px 0 0 var(--color-focus-ring);
}

.sites_modal_create .site_create_primary_grid,
.sites_modal_create .site_create_field_grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 0.75rem;
  min-width: 0;
}

.sites_modal_create .site_create_field_grid {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.sites_modal_create .site_create_section,
.sites_modal_create .site_editor_custom_fields {
  min-width: 0;
  padding-top: 0.85rem;
  border-top: 1px solid color-mix(in srgb, var(--panel-border) 72%, transparent);
}

.sites_modal_create .site_create_section_title,
.sites_modal_create .site_editor_subheading {
  margin: 0 0 0.65rem;
  padding: 0;
  border: 0;
  color: var(--panel-head-fore);
  font-size: 0.78rem;
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: 0;
  text-transform: none;
}

.sites_modal_create .item_pair {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  align-items: start;
  gap: 0.35rem;
  min-width: 0;
  min-height: 0;
  width: 100%;
  margin: 0;
  padding: 0;
}

.sites_modal_create .item_label {
  margin: 0;
  padding: 0;
  color: var(--panel-head-fore);
  font-size: 0.75rem;
  font-weight: 700;
  line-height: 1.2;
  text-align: left;
}

.sites_modal_create .item_value {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 0.3rem;
  min-width: 0;
  width: 100%;
  padding: 0;
}

.sites_modal_create input:not([type="color"]),
.sites_modal_create select {
  width: 100%;
  min-width: 0;
  min-height: 2.5rem;
  padding: 0.55rem 0.65rem;
  border: 1px solid var(--panel-border);
  border-radius: 4px;
  background: var(--panel-bg);
  color: var(--panel-text);
  font-family: inherit;
  font-size: 0.85rem;
  line-height: 1.3;
  box-sizing: border-box;
  transition:
    border-color 120ms ease,
    box-shadow 120ms ease,
    background-color 120ms ease;
}

.sites_modal_create input:not([type="color"]):focus,
.sites_modal_create select:focus {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: 1px;
}

.sites_modal_create .site_editor_custom_fields {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 12rem), 1fr));
  gap: 0.75rem;
  align-items: end;
}

.sites_modal_create .site_editor_custom_fields .site_editor_subheading {
  grid-column: 1 / -1;
}

.sites_modal_create .site_editor_binary_pill {
  justify-self: start;
}

.sites_modal_create .modal_footer,
.sites_modal_edit .modal_footer {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  justify-items: center;
  gap: 0.6rem;
  margin: 0;
  padding: 0.8rem 1rem 1rem;
}

.sites_modal_create .modal_footer {
  justify-items: center;
}

.sites_modal_create .modal_footer .status_message {
  width: min(100%, 68rem);
}

.sites_modal_create .site_create_footer_actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  width: min(100%, 68rem);
}

.sites_modal_create .modal_footer > .flex,
.sites_modal_edit .modal_footer > .flex {
  justify-content: center;
  gap: 0.75rem;
  width: 100%;
}

.sites_modal_confirm_delete,
.sites_modal_finality_delete,
.sites_modal_archived_work {
  width: min(42rem, calc(100vw - 2rem));
  max-width: min(42rem, calc(100vw - 2rem));
  max-height: calc(100dvh - 2rem);
  margin: auto;
  border: 1px solid var(--business-page-border, var(--panel-border));
  border-radius: 0.5rem;
  background: var(--business-page-elevated-bg, var(--panel-bg));
  color: var(--panel-text);
  overflow: hidden;
}

.sites_modal_archived_work {
  width: min(56rem, calc(100vw - 2rem));
  max-width: min(56rem, calc(100vw - 2rem));
}

.sites_modal_confirm_delete[open],
.sites_modal_finality_delete[open],
.sites_modal_archived_work[open] {
  display: grid;
  grid-template-rows: auto minmax(0, 1fr) auto;
}

.sites_modal_confirm_delete .modal_title,
.sites_modal_finality_delete .modal_title,
.sites_modal_archived_work .modal_title {
  grid-column: 1;
  justify-self: start;
  margin: 0;
  padding: 0;
  text-align: left !important;
}

.sites_modal_confirm_delete .modal_header .btn_close,
.sites_modal_finality_delete .modal_header .btn_close,
.sites_modal_archived_work .modal_header .btn_close {
  grid-column: 2;
  justify-self: end;
}

.sites_modal_confirm_delete .modal_content,
.sites_modal_finality_delete .modal_content,
.sites_modal_archived_work .modal_content {
  display: block;
  min-height: 0;
  padding: 1rem;
  overflow: auto;
  border-top: 1px solid var(--business-page-border, var(--panel-border));
  border-bottom: 1px solid var(--business-page-border, var(--panel-border));
}

.sites_modal_confirm_delete .modal_content p,
.sites_modal_finality_delete .modal_content p {
  margin: 0;
  color: var(--panel-text);
  font-size: 0.95rem;
  line-height: 1.45;
  letter-spacing: 0;
}

.sites_modal_cleanup .modal_content {
  display: grid;
  gap: 0.85rem;
}

.site_cleanup_summary {
  font-weight: 800;
}

.site_cleanup_option {
  display: grid;
  gap: 0.65rem;
  padding: 0.85rem;
  border: 1px solid color-mix(in srgb, var(--panel-border) 80%, transparent);
  border-radius: 8px;
  background: color-mix(in srgb, var(--panel-bg) 92%, var(--panel-text) 8%);
}

.site_cleanup_option_primary {
  border-left: 3px solid var(--color-primary);
}

.site_cleanup_option_danger {
  border-left: 3px solid var(--error);
}

.site_cleanup_option_title {
  margin: 0;
  color: var(--panel-head-fore);
  font-size: 0.9rem;
  font-weight: 800;
  line-height: 1.2;
}

.site_cleanup_option_copy {
  margin: 0;
  color: var(--text-muted, var(--sites-muted-default));
  font-size: 0.85rem;
  line-height: 1.4;
}

.site_cleanup_transfer_row {
  display: grid;
  gap: 0.35rem;
}

.site_cleanup_target_controls {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 0.5rem;
  align-items: stretch;
}

.site_cleanup_target_select {
  min-width: 0;
  width: 100%;
}

.site_cleanup_create_target {
  min-width: 2.6rem;
  padding-inline: 0.8rem;
  font-size: 1.1rem;
  font-weight: 800;
}

.site_cleanup_transfer_submit,
.site_cleanup_option > .btn {
  justify-self: end;
}

.sites_modal_confirm_delete .modal_footer,
.sites_modal_finality_delete .modal_footer,
.sites_modal_archived_work .modal_footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  margin: 0;
  padding: 0.8rem 1rem 1rem;
}

.site_dialog_footer_actions {
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
  border-right: 1px solid var(--panel-border);
}

.edit_site_col_heading {
  margin: 0 0 0.35rem;
  padding-bottom: 0.55rem;
  border-bottom: 1px solid var(--panel-border);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.09em;
  text-transform: uppercase;
  color: var(--text-muted, var(--sites-muted-default));
}

.site_editor_subheading {
  margin: 0;
  padding-top: 0.25rem;
  border-top: 1px solid var(--panel-border);
  color: var(--text-muted, var(--sites-muted-default));
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

/* ── Replace flex pair with fixed-label grid — kills label-length fighting ── */
.sites_modal_edit .item_pair {
  display: grid;
  grid-template-columns: 180px minmax(0, 1fr);
  align-items: center;
  gap: 1rem;
  min-height: 3.5rem;
  padding: 0;
  width: 100%;
}

.sites_modal_edit .item_label {
  text-align: right;
  padding: 0;
  font-size: 0.88rem;
  line-height: 1.25;
}

.sites_modal_edit .item_value {
  display: grid;
  gap: 0.3rem;
  min-width: 0;
  padding: 0;
}

/* ── Uniform control heights ─────────────────────────────────────────────── */
.sites_modal_edit input:not([type="color"]),
.sites_modal_edit select {
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
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius, 6px);
  background: color-mix(in srgb, var(--panel-bg) 60%, transparent);
  color: var(--text-muted, var(--sites-muted-default));
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
  box-shadow: 0 2px 8px color-mix(in srgb, black 10%, transparent);
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
  background: var(--sites-warning-hover);
}

.modal_orphaned_work .modal_header .btn_close {
  z-index: 10;
}

@media (max-width: 768px) {
  .sites_modal_create .modal_content,
  .sites_modal_edit .modal_content {
    grid-template-columns: minmax(0, 1fr);
    gap: 0;
    overflow-y: auto;
  }

  .sites_modal_create .modal_content {
    display: block;
    padding: 0.75rem 0.75rem 7rem;
    scroll-padding-bottom: 7rem;
  }

  .sites_modal_create .site_create_card {
    padding: 0.75rem;
  }

  .sites_modal_create .site_create_field_grid,
  .sites_modal_create .site_editor_custom_fields {
    grid-template-columns: minmax(0, 1fr);
  }

  .sites_modal_create .site_create_footer_actions {
    flex-wrap: wrap;
  }

  .edit_site_col {
    gap: 1.35rem;
    padding: 1.25rem 1rem 1.5rem;
    overflow: visible;
  }

  .edit_site_col_basic {
    border-right: 0;
    border-bottom: 1px solid var(--panel-border);
  }

  .sites_modal_create .item_pair,
  .sites_modal_edit .item_pair {
    grid-template-columns: minmax(0, 1fr);
    align-items: stretch;
    gap: 0.55rem;
    min-height: 0;
    padding: 0 0 0.15rem;
  }

  .sites_modal_create .item_label,
  .sites_modal_edit .item_label {
    display: block;
    text-align: left;
    line-height: 1.35;
    margin: 0;
  }

  .sites_modal_create input:not([type="color"]),
  .sites_modal_create select,
  .sites_modal_edit input:not([type="color"]),
  .sites_modal_edit select {
    min-height: 3rem;
  }

  .item_pair_color {
    gap: 0.7rem;
    padding-top: 0.15rem;
    padding-bottom: 1.4rem;
    margin-bottom: 0.4rem;
    border-bottom: 1px solid color-mix(in srgb, var(--panel-border) 72%, transparent);
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
    grid-template-columns: repeat(5, 2rem);
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
  .sites_modal_create .modal_content,
  .sites_modal_edit .modal_content {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-content: start;
    gap: 0;
    overflow-y: auto;
  }

  .sites_modal_create .modal_content {
    display: block;
    padding-bottom: 7rem;
    scroll-padding-bottom: 7rem;
  }

  .sites_modal_create .site_create_field_grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
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
    border-bottom: 1px solid var(--panel-border);
  }

  .sites_modal_edit .item_pair.item_pair_color {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: start;
    gap: 0.7rem;
    min-height: auto;
    padding-bottom: 1.5rem;
    margin-bottom: 1.1rem;
  }

  .sites_modal_edit .item_pair.item_pair_color .item_label,
  .sites_modal_edit .item_pair.item_pair_color .item_value {
    display: grid;
    gap: 0.35rem;
    width: 100%;
    text-align: left;
  }

  .sites_modal_edit .item_pair.item_pair_color .item_value {
    margin-top: 0;
  }

  .site_color_picker,
  .site_color_swatches {
    width: 100%;
  }

  .site_color_swatches {
    display: grid;
    grid-template-columns: repeat(5, 2rem);
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

  @media (max-width: 340px) {
    .site_color_swatches {
      grid-template-columns: repeat(auto-fill, minmax(2rem, 2rem));
    }
  }

  .edit_site_col_advanced {
    clear: both;
    padding-top: 1.5rem;
  }
}

@media (max-width: 768px) {
  .sites_modal_create .site_create_field_grid,
  .sites_modal_create .site_editor_custom_fields {
    grid-template-columns: minmax(0, 1fr);
  }
}

@media (max-width: 420px) {
  .sites_modal_create .modal_header,
  .sites_modal_edit .modal_header {
    min-height: 3.5rem;
    padding: 0.65rem 0.85rem;
  }

  .edit_site_col {
    gap: 1.25rem;
    padding: 1.15rem 0.85rem 1.35rem;
  }

  .sites_modal_create .modal_footer,
  .sites_modal_edit .modal_footer {
    padding: 0.75rem 0.85rem 0.9rem;
  }
}

.delete_message_danger {
  color: var(--error);
}

/* ── Per-swatch color rules (CSP-safe: no inline styles) ───────────────────── */
<?php
foreach (\PayCal\Domain\Config\SiteColorPalette::pickerPalette() as $swIdx => $swColor) {
  $swHex = $swColor['hex'];
  echo ".site_color_swatch[data-idx=\"{$swIdx}\"] { background: {$swHex}; }\n";
}?>
