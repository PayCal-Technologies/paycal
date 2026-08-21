<?php declare(strict_types=1); ?>
/* Route-owned list-choice-controls calendar-fields. */
/* WORK ENTRY FIELD TAGS */
.work_entry_tags {
  display: flex;
  flex-wrap: wrap;
  gap: var(--gap-md);
  align-items: baseline;
  margin: var(--mar-sm);
}
.work_entry_field {
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
.work_entry_field + label {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: var(--pad-xs) var(--pad-md);
  border: 0;
  border-radius: var(--settings-selected-radius, var(--radius-control, var(--border-radius)));
  background-color: transparent;
  color: var(--button-text, inherit);
  cursor: pointer;
  transition: var(--short-transition) all ease;
  font-size: var(--font-sm);
  white-space: nowrap;
  font-weight: 500;
}
.work_entry_field:hover + label {
  background-color: var(--color-hover, color-mix(in srgb, white 8%, transparent));
  color: var(--button-text, inherit);
}
.work_entry_field:checked + label {
  border-bottom: var(--border-bottom);
  border-color: var(--button-border-active);
  background-color: var(--btn-selected-back, var(--button-bg-active));
  color: var(--btn-selected-fore, var(--button-text));
}
.work_entry_field:focus + label {
  outline: none;
}
.calendar_badge_pills {
  display: flex;
  flex-wrap: wrap;
  gap: var(--calendar-badge-pill-gap, var(--settings-pill-gap, var(--gap-xs)));
  align-items: flex-start;
  margin-top: var(--calendar-badge-pill-margin-top, var(--gap-xs));
  padding: var(--calendar-badge-pill-padding, var(--pad-sm));
  border: 1px solid var(--calendar-badge-pill-border, var(--panel-border));
  border-radius: var(--calendar-badge-pill-radius, var(--radius-control, var(--border-radius)));
  background: var(--calendar-badge-pill-bg, color-mix(in srgb, var(--panel-border) 18%, transparent));
  box-sizing: border-box;
}
.calendar_badge_pills .work_entry_field + label {
  flex: 0 0 auto;
  width: auto;
  min-width: 0;
  min-height: var(--calendar-badge-pill-item-min-height, var(--settings-control-height, auto));
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 999px;
  padding: var(--calendar-badge-pill-item-padding, var(--pad-sm) var(--pad-md));
  font-weight: 600;
  white-space: nowrap;
  line-height: 1.2;
  text-align: center;
  cursor: pointer;
  background-color: var(--calendar-badge-pill-item-bg, color-mix(in srgb, var(--panel-border) 32%, transparent));
  color: var(--calendar-badge-pill-item-text, var(--button-text, inherit));
  box-shadow: var(--calendar-badge-pill-item-shadow, var(--depth-control-shadow, none));
  transition: var(--calendar-badge-pill-item-transition, var(--depth-interaction-transition, var(--short-transition) all ease));
}
.calendar_badge_pills :is(.work_entry_field:hover,.work_entry_field:focus) + label {
  background-color: var(--calendar-badge-pill-item-hover-bg, color-mix(in srgb, var(--btn-selected-back) 55%, transparent));
  border-color: transparent;
  color: var(--calendar-badge-pill-item-hover-text, var(--btn-selected-fore, var(--button-text)));
  box-shadow: var(--calendar-badge-pill-item-hover-shadow, var(--depth-control-shadow-hover, var(--depth-control-shadow, none)));
}
.calendar_badge_pills :is(.work_entry_field:checked,.work_entry_field:active) + label {
  border-bottom: 0;
  border-color: transparent;
  background-color: var(--calendar-badge-pill-item-active-bg, var(--btn-selected-back, var(--button-bg-active)));
  color: var(--calendar-badge-pill-item-active-text, var(--btn-selected-fore, var(--button-text)));
  box-shadow: var(--calendar-badge-pill-item-active-shadow, var(--depth-control-shadow-active, var(--depth-control-shadow, none)));
}
.calendar_badge_pills :is(.work_entry_field:focus,.work_entry_field:focus-visible) + label {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: 2px;
}
.calendar_badge_fields_editor {
  display: grid;
  gap: var(--calendar-badge-editor-gap, var(--gap-sm));
  margin-top: var(--calendar-badge-editor-margin-top, var(--gap-xs));
  padding: var(--calendar-badge-editor-padding, var(--pad-sm));
  border: 1px solid var(--calendar-badge-editor-border, var(--panel-border));
  border-radius: var(--calendar-badge-editor-radius, var(--radius-control, var(--border-radius)));
  background: var(--calendar-badge-editor-bg, color-mix(in srgb, var(--panel-border) 14%, transparent));
  box-sizing: border-box;
}
.calendar_badge_field_row,
.calendar_optional_field_row {
  display: grid;
  grid-template-columns: var(--calendar-badge-field-row-template, minmax(12rem, auto) minmax(8rem, 1fr) minmax(8rem, 12rem) minmax(2.5rem, auto));
  align-items: center;
  gap: var(--calendar-badge-field-row-gap, var(--gap-sm));
}
.calendar_badge_field_row > .visually_hidden,
.calendar_optional_field_row > .visually_hidden {
  position: absolute !important;
}
.calendar_badge_field_state,
.calendar_optional_field_state {
  display: inline-flex;
  align-items: stretch;
  width: max-content;
  min-width: var(--calendar-badge-field-state-min-width, 12rem);
  min-inline-size: 0;
  margin: 0;
  padding: 0;
  border: 2px solid var(--calendar-badge-field-state-border, color-mix(in srgb, var(--panel-text) 35%, var(--panel-border)));
  border-radius: 999px;
  background: var(--calendar-badge-field-state-bg, color-mix(in srgb, var(--panel-bg) 86%, var(--panel-text) 14%));
  box-shadow: var(--calendar-badge-field-state-shadow, 0 0 0 1px color-mix(in srgb, var(--panel-bg) 72%, transparent), inset 0 1px 0 color-mix(in srgb, var(--panel-text) 12%, transparent));
  overflow: hidden;
}
.calendar_badge_field_legend {
  width: 1px;
  height: 1px;
  margin: -1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  clip-path: inset(50%);
  white-space: nowrap;
}
.calendar_badge_field_enabled + label,
.calendar_optional_field_enabled + label,
.calendar_badge_state_static {
  flex: 1 1 0;
  min-height: var(--calendar-badge-segment-min-height, var(--settings-control-height));
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 0;
  padding: var(--calendar-badge-segment-padding, var(--pad-sm) 0.85rem);
  border-left: 1px solid var(--calendar-badge-segment-border, color-mix(in srgb, var(--panel-text) 22%, transparent));
  background-color: var(--calendar-badge-segment-bg, color-mix(in srgb, var(--panel-bg) 92%, var(--panel-text) 8%));
  color: var(--calendar-badge-segment-text, color-mix(in srgb, var(--panel-text) 72%, var(--panel-bg)));
  font-weight: var(--calendar-badge-segment-weight, 700);
  line-height: 1.2;
  text-align: center;
  white-space: nowrap;
}
.calendar_badge_field_enabled + label,
.calendar_optional_field_enabled + label {
  cursor: pointer;
}
.calendar_badge_field_state .calendar_badge_field_enabled:first-child + label,
.calendar_optional_field_state .calendar_optional_field_enabled:first-child + label,
.calendar_badge_state_static:first-child {
  border-left: 0;
}
.calendar_badge_field_enabled:focus-visible + label,
.calendar_optional_field_enabled:focus-visible + label {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: -2px;
}
.calendar_badge_field_state_locked {
  pointer-events: none;
}
.calendar_badge_fields_editor :is(.calendar_optional_field_label, .calendar_optional_field_type) {
  width: 100%;
  height: var(--settings-control-height);
  min-width: 0;
  min-height: var(--settings-control-height);
  min-block-size: var(--settings-control-height);
  margin: 0;
  padding: var(--pad-sm) var(--pad-md);
  border: 1px solid var(--panel-border);
  border-radius: var(--radius-control, var(--border-radius));
  background: var(--input-bg, var(--panel-bg));
  box-shadow: none;
  color: var(--panel-text);
  font-family: inherit;
  font-size: inherit;
  font-weight: var(--calendar-badge-display-weight, 700);
  line-height: inherit;
  text-align: left;
  box-sizing: border-box;
}
.calendar_badge_field_name_cell {
  min-width: 0;
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  align-items: center;
  gap: var(--gap-xs);
}
.calendar_badge_field_row_add {
  padding-top: var(--gap-xs);
  border-top: 1px solid color-mix(in srgb, var(--panel-border) 72%, transparent);
}
.calendar_badge_add_button {
  width: 100%;
  min-width: var(--calendar-badge-add-button-min-width, 12rem);
  min-height: var(--settings-control-height);
  padding: var(--pad-sm) 0.95rem;
  border: 2px dashed color-mix(in srgb, var(--accent-color, var(--color-accent, var(--settings-accent-fallback))) 58%, var(--panel-text) 18%);
  border-radius: 999px;
  background: color-mix(in srgb, var(--accent-color, var(--color-accent, var(--settings-accent-fallback))) 18%, var(--panel-bg) 82%);
  color: var(--panel-text);
  font: inherit;
  font-weight: 800;
  line-height: 1.2;
  text-align: center;
  cursor: pointer;
  box-sizing: border-box;
  box-shadow: inset 0 1px 0 color-mix(in srgb, var(--panel-text) 14%, transparent);
}
.calendar_badge_add_button:hover,
.calendar_badge_add_button:focus-visible {
  border-style: solid;
  background: color-mix(in srgb, var(--accent-color, var(--color-accent, var(--settings-accent-fallback))) 30%, var(--panel-bg) 70%);
  outline: 2px solid var(--color-focus-ring);
  outline-offset: 2px;
}
.calendar_badge_field_action_spacer,
.calendar_optional_field_delete {
  width: var(--settings-control-height);
  min-width: var(--settings-control-height);
  height: var(--settings-control-height);
  justify-self: center;
}
.calendar_badge_field_display,
.calendar_badge_field_type_display {
  min-height: var(--settings-control-height);
  display: inline-flex;
  align-items: center;
  gap: var(--gap-xs);
  min-width: 0;
  padding: var(--pad-sm) var(--pad-md);
  border: 1px solid var(--panel-border);
  border-radius: var(--radius-control, var(--border-radius));
  background: var(--input-bg, var(--panel-bg));
  color: var(--panel-text);
  font-weight: var(--calendar-badge-display-weight, 700);
  box-sizing: border-box;
}
@media (max-width: 640px) {
  .calendar_badge_field_row,
.calendar_optional_field_row {
    --calendar-badge-field-row-template: minmax(0, 1fr);
  }

  .calendar_badge_field_state,
.calendar_optional_field_state,
.calendar_badge_add_button {
    width: 100%;
    min-width: 0;
  }

  .calendar_badge_field_action_spacer {
    display: none;
  }

  .calendar_optional_field_delete {
    justify-self: stretch;
    width: 100%;
  }
}
