<?php declare(strict_types=1); ?>
/* Calendar work-entry component. */
/* =========================================================================
   MODAL DIALOG (Calendar v2)
   ========================================================================= */

.calendar_modal {
  --dialog-max-width: 65vw;
  --dialog-max-height: 90vh;
}

.calendar_modal_header {
  justify-content: center;
}

.calendar_modal_header_actions {
  display: flex;
  align-items: center;
  gap: var(--gap-sm);
  margin-left: auto;
}

.calendar_modal_header_add {
  flex: 0 0 auto;
}

.calendar_modal_header_add:hover {
  opacity: 0.9;
}

.calendar_modal_header h2 {
  margin: 0;
  font-size: var(--font-lg);
  color: var(--modal-head-fore);
  text-align: center;
  width: 100%;
  padding: 0 2.75rem;
}

.calendar_modal_close {
  left: var(--pad-sm) !important;
  right: auto !important;
}

.calendar_modal_close:hover {
  color: var(--color-primary);
}

.calendar_modal_body {
  padding: var(--pad-md);
  overflow-y: auto;
}

.calendar_modal_footer {
  display: flex;
  gap: var(--gap-sm);
  justify-content: flex-end;
}

.calendar_modal_action {
  flex: 0 0 auto;
  min-width: 7.5rem;
}

.calendar_modal_action:hover {
  opacity: 0.95;
}

.calendar_modal_action.calendar_modal_action_save:hover {
  opacity: 0.9;
}

.form-field label {
  font-weight: 600;
  font-size: 0.75rem;
  color: var(--panel-head-fore);
}

.form-field select,
.form-field input {
  padding: 8px;
  border: 1px solid var(--panel-border);
  border-radius: 4px;
  background: var(--panel-bg);
  color: var(--panel-text);
  font-size: 0.75rem;
  font-family: inherit;
}

.form-field select:focus,
.form-field input:focus {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: -1px;
}

.form-btn:hover {
  background: var(--button-bg-hover);
  opacity: 0.9;
}

.form-btn-submit:hover {
  opacity: 0.8;
}

.success-message p {
  margin: 6px 0;
  font-size: 0.75rem;
}

.success-message p:first-child {
  font-weight: 600;
  color: var(--color-primary);
  margin-bottom: 12px;
}

.success-message em {
  font-weight: 600;
  font-style: normal;
  color: var(--panel-head-fore);
}

/* Work Entry Table */
.work-entries-table thead {
  background: var(--panel-head-bg, var(--panel-bg, var(--color-surface)));
  color: var(--calendar-weekday-header-text, var(--panel-text, var(--color-text)));
}

.work-entries-table th {
  padding: 12px 8px;
  text-align: left;
  font-weight: 600;
  font-size: 0.75rem;
  border-bottom: 2px solid var(--panel-border);
}

.work-entries-table td {
  padding: 10px 8px;
  border-bottom: 1px solid var(--panel-border);
  vertical-align: middle;
}

.work-entries-table tr:hover {
  background: var(--calendar-table-row-hover-bg);
}

.work-entries-table input[type="text"],
.work-entries-table input[type="number"],
.work-entries-table select {
  width: 100%;
  padding: 6px 8px;
  background: var(--panel-bg);
  color: var(--panel-text);
  border: 1px solid var(--panel-border);
  border-radius: 4px;
  font-size: 0.75rem;
}

.work-entries-table input:focus,
.work-entries-table select:focus {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: 1px;
}

/* Table column widths */
.work-entries-table th.th-site {
  width: 30%;
}

.work-entries-table th.th-regular,
.work-entries-table th.th-overtime,
.work-entries-table th.th-loa,
.work-entries-table th.th-travel {
  width: 15%;
}

.work-entries-table th.th-action {
  width: 10%;
}
.work-entry-delete:hover {
  opacity: 0.8;
}
.add-entry-button:hover {
  opacity: 0.9;
}

/* Work Entry Display (read-only) */
/* =========================================================================
   RESPONSIVE - MOBILE
   ========================================================================= */

@media (max-width: 768px) {
  .calendar_modal {
    max-width: 95%;
    width: 95%;
    max-height: 95vh;
  }

  /* Stack table rows vertically on mobile */
  .work-entries-table,
  .work-entries-table thead,
  .work-entries-table tbody,
  .work-entries-table tr,
  .work-entries-table td,
  .work-entries-table th {
    display: block;
    width: 100%;
  }

  .work-entries-table thead {
    display: none; /* Hide header on mobile */
  }

  .work-entries-table tr {
    margin-bottom: 16px;
    border: 1px solid var(--panel-border);
    border-radius: 4px;
    padding: 12px;
    background: color-mix(in srgb, black 5%, transparent);
  }

  .work-entries-table td {
    border: none;
    padding: 8px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .work-entries-table td:before {
    content: attr(data-label);
    font-weight: 600;
    margin-right: 10px;
    color: var(--panel-head-fore);
    flex-shrink: 0;
  }

  .work-entries-table input[type="text"],
  .work-entries-table input[type="number"],
  .work-entries-table select {
    max-width: 60%;
  }
}

/* =========================================================================
   RESPONSIVE
   ========================================================================= */

@media (max-width: 1024px) {
  .calendar_modal {
    max-width: 90%;
    width: 90%;
  }
}

/* Work-entry editor: reset shared dialog chrome and own the full viewport. */
.calendar_modal.calendar_modal {
  position: fixed;
  inset: 0;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  transform: none;
  width: 100vw;
  max-width: 100vw;
  min-width: 0;
  height: 100dvh;
  max-height: 100dvh;
  min-height: 0;
  margin: 0;
  padding: 0;
  border: 0;
  border-radius: 0;
  box-sizing: border-box;
  overflow: hidden;
}

.calendar_modal.calendar_modal[open] {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  grid-template-rows: auto minmax(0, 1fr) auto;
}

.calendar_modal.calendar_modal > * {
  grid-column: 1;
  min-width: 0;
  box-sizing: border-box;
}

.calendar_modal .calendar_modal_header {
  grid-row: 1;
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 1rem;
  min-height: 4rem;
  margin: 0;
  padding: 0.75rem 1rem;
}

.calendar_modal .calendar_modal_header h2 {
  width: auto;
  max-width: 100%;
  margin: 0;
  padding: 0;
  color: var(--modal-head-fore);
  font-size: var(--font-xl);
  line-height: 1.2;
  text-align: left;
  overflow-wrap: anywhere;
}

.calendar_modal .calendar_modal_close {
  grid-column: 2;
  justify-self: end;
  position: static !important;
  inset: auto !important;
  left: auto !important;
  top: auto !important;
  right: auto !important;
  bottom: auto !important;
  transform: none !important;
  flex: 0 0 auto;
  margin: 0;
}

.calendar_modal .calendar_modal_body {
  grid-row: 2;
  display: block;
  min-height: 0;
  padding: 1rem 1rem 7rem;
  overflow: auto;
  scroll-padding-bottom: 7rem;
}

.calendar_modal .work-entries-form {
  display: flex;
  justify-content: center;
  width: 100%;
  overflow-x: visible;
}

.calendar_modal .work-entries-workspace {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 1rem;
  width: min(100%, 68rem);
  margin: 0 auto;
}

.calendar_modal .work-entries-list {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 1rem;
}

.calendar_modal .work-entry-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 1rem;
  padding: 1rem;
  border: 1px solid var(--panel-border);
  border-radius: 8px;
  background: color-mix(in srgb, var(--panel-bg) 96%, var(--panel-text) 4%);
}

.calendar_modal .work-entry-row:hover {
  background: color-mix(in srgb, var(--panel-bg) 92%, var(--panel-text) 8%);
}

.calendar_modal .work-entry-row-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  min-width: 0;
}

.calendar_modal .work-entry-eyebrow {
  color: var(--panel-head-fore);
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0;
  text-transform: uppercase;
}

.calendar_modal .work-entry-field {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 0.35rem;
  min-width: 0;
  margin: 0;
}

.calendar_modal .work-entry-field-label,
.calendar_modal .work-entry-hours-group legend {
  color: var(--panel-head-fore);
  font-size: 0.75rem;
  font-weight: 700;
  line-height: 1.2;
}

.calendar_modal .work-entry-site-control {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 2.5rem;
  gap: 0.5rem;
  align-items: stretch;
}

.calendar_modal .work-entry-site-control select,
.calendar_modal .work-entry-field input[type="text"],
.calendar_modal .work-entry-field input[type="number"],
.calendar_modal .entry-custom-input {
  width: 100%;
  min-width: 0;
  padding: 0.55rem 0.65rem;
  border: 1px solid var(--panel-border);
  border-radius: 4px;
  background: var(--panel-bg);
  color: var(--panel-text);
  font-family: inherit;
  font-size: 0.85rem;
  line-height: 1.3;
}

.calendar_modal .work-entry-site-control select:focus,
.calendar_modal .work-entry-field input:focus,
.calendar_modal .entry-custom-input:focus {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: 1px;
}

.calendar_modal .work-entry-site-option-content {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  min-width: 0;
}

.calendar_modal .work-entry-site-color-chit {
  flex: 0 0 auto;
  width: 0.38rem;
  height: 1.05rem;
  border: 0;
  border-radius: 2px;
  background: transparent;
  box-shadow: none;
}

.calendar_modal .work-entry-site-option-name {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.calendar_modal .work-entry-site-picker {
  display: none;
  position: relative;
  min-width: 0;
}

.calendar_modal .work-entry-site-control.has-custom-site-picker .entry-site-select {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
}

.calendar_modal .work-entry-site-control.has-custom-site-picker .work-entry-site-picker {
  display: block;
}

.calendar_modal .work-entry-site-picker-button,
.calendar_modal .work-entry-site-picker-option {
  width: 100%;
  min-width: 0;
  border: 1px solid var(--panel-border);
  background: var(--panel-bg);
  color: var(--panel-text);
  font-family: inherit;
  font-size: 0.85rem;
  line-height: 1.3;
  text-align: left;
}

.calendar_modal .work-entry-site-picker-button {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 2.45rem;
  padding: 0.55rem 2.2rem 0.55rem 0.65rem;
  border-radius: 4px;
  cursor: pointer;
}

.calendar_modal .work-entry-site-picker-button::after {
  content: '';
  position: absolute;
  right: 0.8rem;
  width: 0.55rem;
  height: 0.55rem;
  border-right: 2px solid currentColor;
  border-bottom: 2px solid currentColor;
  transform: rotate(45deg) translateY(-0.12rem);
}

.calendar_modal .work-entry-site-control.is-site-picker-open .work-entry-site-picker-button::after {
  transform: rotate(225deg) translate(-0.05rem, -0.05rem);
}

.calendar_modal .work-entry-site-picker-button:focus-visible {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: 1px;
}

.calendar_modal .work-entry-site-picker-menu {
  position: absolute;
  z-index: 40;
  top: calc(100% + 0.25rem);
  left: 0;
  right: 0;
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  max-height: min(22rem, 70vh);
  overflow-y: auto;
  padding: 0.25rem;
  border: 1px solid var(--panel-border);
  border-radius: 6px;
  background: var(--panel-bg);
  box-shadow: 0 0.9rem 2rem color-mix(in srgb, black 28%, transparent);
}

.calendar_modal .work-entry-site-picker-menu[hidden] {
  display: none;
}

.calendar_modal .work-entry-site-picker-option {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  min-height: 2.35rem;
  padding: 0.55rem 0.65rem;
  border-color: transparent;
  border-radius: 4px;
  cursor: pointer;
}

.calendar_modal .work-entry-site-picker-option[aria-selected="true"] {
  border-color: var(--color-focus-ring);
  background: color-mix(in srgb, var(--color-primary) 30%, var(--panel-bg));
}

.calendar_modal .work-entry-site-picker-option:hover,
.calendar_modal .work-entry-site-picker-option:focus-visible {
  background: color-mix(in srgb, var(--color-primary) 18%, var(--panel-bg));
  outline: none;
}

.calendar_modal .work-entry-site-picker-option--create {
  margin-top: 0.15rem;
  color: var(--panel-head-fore);
  font-weight: 700;
}

@supports (appearance: base-select) {
  .calendar_modal .entry-site-select,
  .calendar_modal .entry-site-select::picker(select) {
    appearance: base-select;
  }

  .calendar_modal .entry-site-select {
    align-items: center;
    cursor: pointer;
  }

  .calendar_modal .entry-site-select::picker(select) {
    border: 1px solid var(--panel-border);
    border-radius: 6px;
    background: var(--panel-bg);
    color: var(--panel-text);
    box-shadow: 0 0.9rem 2rem color-mix(in srgb, black 28%, transparent);
    max-block-size: min(22rem, 70vh);
    overflow-y: auto;
  }

  .calendar_modal .entry-site-select > button {
    display: flex;
    align-items: center;
    min-width: 0;
    width: 100%;
    padding: 0;
    border: 0;
    background: transparent;
    color: inherit;
    font: inherit;
    text-align: left;
  }

  .calendar_modal .entry-site-select selectedcontent,
  .calendar_modal .entry-site-select option,
  .calendar_modal .work-entry-site-option-content {
    display: flex;
    align-items: center;
    min-width: 0;
    gap: 0.55rem;
  }

  .calendar_modal .entry-site-select selectedcontent .work-entry-site-option-content {
    width: 100%;
  }

  .calendar_modal .entry-site-select option {
    padding: 0.65rem 0.75rem;
    min-block-size: 2.35rem;
  }

  .calendar_modal .entry-site-select option:checked,
  .calendar_modal .entry-site-select option:hover {
    background: color-mix(in srgb, var(--color-primary) 18%, var(--panel-bg));
  }

}

.calendar_modal .work-entry-quick-create-toggle {
  width: 2.5rem;
  min-width: 2.5rem;
  padding-inline: 0;
  font-size: 1.15rem;
  font-weight: 800;
}

.calendar_modal .work-entry-empty-sites {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem;
  border: 1px dashed color-mix(in srgb, var(--panel-border) 78%, var(--panel-text) 22%);
  border-radius: 6px;
  color: var(--panel-text);
  font-size: 0.8rem;
}

.calendar_modal .work-entry-quick-create {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 0.65rem;
  width: min(100%, 28rem);
  margin-top: 0.25rem;
  padding: 0.85rem;
  border: 1px solid var(--panel-border);
  border-radius: 8px;
  background: var(--panel-bg);
  box-shadow: 0 12px 32px color-mix(in srgb, black 18%, transparent);
}

.calendar_modal .work-entry-quick-create[hidden] {
  display: none;
}

.calendar_modal .work-entry-quick-create-title {
  color: var(--panel-head-fore);
  font-size: 0.9rem;
  font-weight: 800;
  line-height: 1.2;
}

.calendar_modal .work-entry-quick-create-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.5rem;
}

.calendar_modal .work-entry-hours-group {
  min-width: 0;
  margin: 0;
  padding: 0;
  border: 0;
}

.calendar_modal .work-entry-hours-group legend {
  margin-bottom: 0.5rem;
  padding: 0;
}

.calendar_modal .work-entry-hours-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
}

.calendar_modal .work-entry-lower-grid {
  display: grid;
  grid-template-columns: minmax(10rem, max-content);
  gap: 0.75rem;
  align-items: end;
}

.calendar_modal .work-entry-custom-fields-group {
  min-width: 0;
  padding-top: 0.85rem;
  border-top: 1px solid color-mix(in srgb, var(--panel-border) 72%, transparent);
}

.calendar_modal .work-entry-custom-fields-title {
  margin-bottom: 0.65rem;
  color: var(--panel-head-fore);
  font-size: 0.78rem;
  font-weight: 800;
  line-height: 1.2;
}

.calendar_modal .work-entry-custom-fields-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 12rem), 1fr));
  gap: 0.75rem;
  align-items: end;
}

.calendar_modal .work-entry-add-below {
  justify-self: start;
  min-height: 2.5rem;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--panel-border);
  border-radius: 6px;
  background: var(--panel-bg);
  color: var(--panel-text);
  font-weight: 700;
}

.calendar_modal .work-entry-add-below:hover {
  background: var(--button-bg-hover);
}

.calendar_modal .work-entries-table {
  width: 100%;
  min-width: 48rem;
  table-layout: fixed;
  border-collapse: collapse;
}

.calendar_modal .work-entries-table.has-custom-fields {
  min-width: 64rem;
}

.calendar_modal .work-entries-table th,
.calendar_modal .work-entries-table td {
  min-width: 0;
}

.calendar_modal .work-entries-table th:not(.th-site):not(.th-action) {
  text-align: center;
}

.calendar_modal .work-entries-table td:not(:first-child):not(.work-entry-row-actions) {
  text-align: center;
}

.calendar_modal .work-entries-table th.th-site {
  width: 28%;
}

.calendar_modal .work-entries-table th.th-regular,
.calendar_modal .work-entries-table th.th-overtime,
.calendar_modal .work-entries-table th.th-loa,
.calendar_modal .work-entries-table th.th-travel {
  width: 13%;
}

.calendar_modal .work-entries-table th.th-action {
  width: 20%;
}

.calendar_modal .work-entries-table.has-custom-fields th.th-site {
  width: 22%;
}

.calendar_modal .work-entries-table.has-custom-fields th.th-regular,
.calendar_modal .work-entries-table.has-custom-fields th.th-overtime,
.calendar_modal .work-entries-table.has-custom-fields th.th-loa,
.calendar_modal .work-entries-table.has-custom-fields th.th-travel,
.calendar_modal .work-entries-table.has-custom-fields th.th-custom {
  width: 10%;
}

.calendar_modal .work-entries-table.has-custom-fields th.th-action {
  width: 14%;
}

.calendar_modal .calendar_modal_footer {
  grid-row: 3;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  margin: 0;
  padding: 0.75rem 1rem 1rem;
}

.calendar_modal .calendar_modal_footer_center {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  width: min(100%, 68rem);
  gap: var(--gap-sm);
}

.calendar_modal .work-entry-row-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.5rem;
  white-space: nowrap;
}

.calendar_modal .work-entry-delete {
  padding: 0.35rem 0.5rem;
  border: 1px solid transparent;
  border-radius: 4px;
  background: transparent;
  color: color-mix(in srgb, var(--panel-text) 62%, transparent);
  font-size: 0.75rem;
  font-weight: 700;
  opacity: 0.72;
  cursor: pointer;
}

.calendar_modal .work-entry-delete:hover,
.calendar_modal .work-entry-delete:focus-visible {
  border-color: var(--panel-border);
  background: color-mix(in srgb, var(--panel-bg) 86%, var(--panel-text) 14%);
  color: var(--panel-text);
  opacity: 1;
}

.calendar_modal .entry-custom-field-stack {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  justify-items: stretch;
  gap: 0.25rem;
}

.calendar_modal .entry-custom-field-stack .work-entry-binary-pill {
  justify-self: start;
}

.calendar_modal .work-entry-binary-pill {
  --work-entry-binary-shell-bg: color-mix(in srgb, var(--panel-bg) 86%, var(--panel-text) 14%);
  --work-entry-binary-option-bg: color-mix(in srgb, var(--panel-bg) 92%, var(--panel-text) 8%);
  --work-entry-binary-yes-bg: color-mix(in srgb, var(--color-success) 70%, var(--btn-selected-back) 30%);
  --work-entry-binary-no-bg: color-mix(in srgb, var(--panel-text) 30%, var(--panel-bg) 70%);
  display: inline-flex;
  align-items: stretch;
  justify-content: center;
  width: max-content;
  min-width: 7.25rem;
  border: 2px solid color-mix(in srgb, var(--panel-text) 35%, var(--panel-border));
  border-radius: 999px;
  background: var(--work-entry-binary-shell-bg);
  box-shadow:
    0 0 0 1px color-mix(in srgb, var(--panel-bg) 72%, transparent),
    inset 0 1px 0 color-mix(in srgb, var(--panel-text) 12%, transparent);
  overflow: hidden;
}

.calendar_modal .work-entry-binary-option {
  flex: 1 1 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 3.25rem;
  min-height: 2rem;
  padding: 0.35rem 0.65rem;
  border-left: 1px solid color-mix(in srgb, var(--panel-text) 22%, transparent);
  background-color: var(--work-entry-binary-option-bg);
  color: color-mix(in srgb, var(--panel-text) 72%, var(--panel-bg));
  font-size: 0.75rem;
  font-weight: 800;
  line-height: 1.2;
  text-align: center;
  white-space: nowrap;
  cursor: pointer;
  text-shadow: none;
}

.calendar_modal .work-entry-binary-input:first-child + .work-entry-binary-option {
  border-left: 0;
}

.calendar_modal .work-entry-binary-input:checked + .work-entry-binary-option {
  box-shadow:
    inset 0 0 0 2px color-mix(in srgb, white 32%, transparent),
    inset 0 -2px 0 color-mix(in srgb, black 24%, transparent);
}

.calendar_modal .work-entry-binary-input[value="1"]:checked + .work-entry-binary-option {
  background-color: var(--work-entry-binary-yes-bg);
  color: contrast-color(var(--work-entry-binary-yes-bg) vs white, var(--calendar-contrast-dark));
}

.calendar_modal .work-entry-binary-input[value="0"]:checked + .work-entry-binary-option {
  background-color: var(--work-entry-binary-no-bg);
  color: contrast-color(var(--work-entry-binary-no-bg) vs white, var(--calendar-contrast-dark));
}

.calendar_modal .work-entry-binary-input:focus-visible + .work-entry-binary-option {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: -2px;
}

.calendar_modal .entry-custom-note-input {
  width: 100%;
  min-width: 0;
  padding: 5px 7px;
  border: 1px solid var(--panel-border);
  border-radius: 4px;
  background: var(--panel-bg);
  color: var(--panel-text);
  font-size: 0.72rem;
}

.calendar_modal .work-entry-add,
.calendar_modal .work-entry-delete {
  flex: 0 0 auto;
}

@media (max-width: 768px) {
  .calendar_modal.calendar_modal {
    width: 100vw;
    max-width: 100vw;
    height: 100dvh;
    max-height: 100dvh;
  }

  .calendar_modal .calendar_modal_header {
    min-height: 3.5rem;
    padding: 0.625rem 0.75rem;
  }

  .calendar_modal .calendar_modal_header h2 {
    font-size: var(--font-lg);
  }

  .calendar_modal .calendar_modal_body {
    padding: 0.75rem 0.75rem 7rem;
  }

  .calendar_modal .work-entries-form {
    overflow-x: visible;
  }

  .calendar_modal .work-entry-row {
    padding: 0.75rem;
  }

  .calendar_modal .work-entry-hours-grid,
  .calendar_modal .work-entry-lower-grid,
  .calendar_modal .work-entry-custom-fields-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .calendar_modal .work-entry-quick-create {
    width: 100%;
  }

  .calendar_modal .work-entries-table {
    min-width: 0;
  }

  .calendar_modal .work-entries-table td.work-entry-row-actions {
    justify-content: flex-end;
    gap: 0.5rem;
  }

  .calendar_modal .calendar_modal_footer_center {
    flex-wrap: wrap;
  }
}

/* ── Site color tinted background on work entry blocks (CSP-safe) ───────── */
<?php
foreach (\PayCal\Domain\Config\SiteColorPalette::pickerPalette() as $pc) {
  $h = strtoupper($pc['hex']);
  echo ".work[data-site-color=\"{$h}\"] {\n";
  echo "  --work-site-tint: color-mix(in srgb, {$h} 56%, var(--work-tint-mix-base, var(--work-entry-back, var(--work-back, var(--color-surface-muted)))));\n";
  echo "  background: var(--work-site-tint);\n";
  echo "  border-left: 3px solid color-mix(in srgb, {$h} 82%, white 18%);\n";
  echo "  --work-entry-chip-fore: var(--calendar-work-chip-fore-fallback, white);\n";
  echo "  --work-entry-chip-strong-fore: var(--calendar-work-chip-fore-fallback, white);\n";
  echo "  color: var(--work-entry-chip-fore);\n";
  echo "}\n";
  echo ".calendar_modal .work-entry-site-color-chit[data-site-color=\"{$h}\"] {\n";
  echo "  background: {$h};\n";
  echo "  border-color: color-mix(in srgb, {$h} 74%, white 26%);\n";
  echo "}\n";
}
?>

.work-entry-row:focus-within {
  background: color-mix(in srgb, var(--color-primary) 8%, transparent);
  box-shadow: inset 3px 0 0 var(--color-focus-ring);
}

body[data-runtime-risk-state="locked"] .calendar_v2_root .calendar_grid,
body[data-runtime-risk-state="terminated"] .calendar_v2_root .calendar_grid {
  pointer-events: none;
  filter: blur(2px) saturate(0.55);
  transform: scale(0.985);
  transform-origin: center;
  transition: filter 140ms ease, transform 140ms ease, opacity 140ms ease;
}

body[data-runtime-risk-state="locked"] .calendar_v2_root::after,
body[data-runtime-risk-state="terminated"] .calendar_v2_root::after {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    linear-gradient(90deg, transparent, color-mix(in srgb, var(--panel-text) 8%, transparent), transparent),
    color-mix(in srgb, var(--panel-bg) 24%, transparent);
  animation: calendarVaultClose 260ms ease-out both;
  z-index: 30;
}

@keyframes calendarFocusRipple {
  0% {
    opacity: 0.95;
    transform: scale(0.97);
  }
  100% {
    opacity: 0;
    transform: scale(1.035);
  }
}

@keyframes calendarSavePending {
  0%, 100% {
    opacity: 0.45;
  }
  50% {
    opacity: 1;
  }
}

@keyframes calendarSaveCommitted {
  0% {
    opacity: 0;
    transform: scale(0.985);
  }
  35% {
    opacity: 1;
    transform: scale(1);
  }
  100% {
    opacity: 0;
    transform: scale(1.018);
  }
}

@keyframes calendarSaveErrorFlash {
  0%, 35% {
    opacity: 1;
  }
  100% {
    opacity: 0;
  }
}

@keyframes calendarWorkEntryLockIn {
  0% {
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-success) 65%, transparent);
  }
  100% {
    box-shadow: inset 0 0 0 0 transparent;
  }
}

@keyframes calendarVaultClose {
  from {
    opacity: 0;
    transform: scaleX(1.04);
  }
  to {
    opacity: 1;
    transform: scaleX(1);
  }
}

.calendar_day_context_menu {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 9000;
  display: block;
  box-sizing: border-box;
  width: min(14rem, calc(100vw - 1rem));
  margin: 0;
  padding: 0.4rem;
  border: 1px solid color-mix(in srgb, var(--panel-border) 82%, transparent);
  border-radius: 6px;
  background-color: var(--panel-bg);
  backdrop-filter: blur(var(--blur-size));
  box-shadow:
    0 0.75rem 2rem color-mix(in srgb, black 38%, transparent),
    0 0.15rem 0.45rem color-mix(in srgb, black 30%, transparent);
  color: var(--panel-text);
  font-family: var(--sans-serif);
  font-size: 0.9rem;
  font-weight: 500;
  letter-spacing: 0;
  line-height: 1.25;
}

.calendar_day_context_menu.hidden {
  display: none;
}

.calendar_day_context_menu_head {
  display: block;
  margin: 0 0 0.25rem;
  padding: 0.4rem 0.65rem 0.6rem;
  border-bottom: 1px solid color-mix(in srgb, var(--panel-border) 72%, transparent);
  color: color-mix(in srgb, var(--panel-text) 78%, transparent);
  font-size: 0.78rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0;
  line-height: 1.2;
  text-align: center;
}

.calendar_day_context_menu ul {
  display: flex;
  flex-direction: column;
  width: 100%;
  min-width: 100%;
  max-width: 100%;
  margin: 0;
  padding: 0;
  list-style: none;
  gap: 0.1rem;
}

.calendar_day_context_menu li:nth-child(4),
.calendar_day_context_menu li:last-child {
  margin-top: 0.2rem;
  padding-top: 0.3rem;
  border-top: 1px solid color-mix(in srgb, var(--panel-border) 62%, transparent);
}

.calendar_day_context_menu [role="menuitem"] {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  width: 100%;
  min-width: 100%;
  max-width: 100%;
  min-height: 2.15rem;
  margin: 0;
  padding: 0.45rem 0.65rem;
  border: none;
  border-radius: 4px;
  background: transparent;
  color: inherit;
  font: inherit;
  font-weight: 600;
  letter-spacing: 0;
  line-height: 1.2;
  text-align: inherit;
  cursor: pointer;
}

.calendar_day_context_menu [role="menuitem"] > span:first-child {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.calendar_day_context_menu [role="menuitem"]:not([aria-disabled="true"]):hover,
.calendar_day_context_menu [role="menuitem"]:not([aria-disabled="true"]):focus {
  background-color: var(--button-primary-bg);
  color: var(--button-primary-text);
  transition: background-color var(--short-transition) ease;
}

.calendar_day_context_menu [role="menuitem"]:not([aria-disabled="true"]):focus-visible {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: -2px;
}

.calendar_day_context_menu [role="menuitem"][aria-disabled="true"] {
  opacity: 0.55;
  cursor: not-allowed;
}

.calendar_day_context_menu kbd {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  flex: 0 0 auto;
  min-width: 3.25rem;
  justify-content: flex-end;
  padding: 0;
  border: none;
  background-color: transparent;
  font-family: var(--monospace);
  font-size: 0.74rem;
  font-weight: 600;
  letter-spacing: 0;
  color: color-mix(in srgb, var(--panel-text) 72%, transparent);
}

.calendar_day_context_menu [role="menuitem"]:not([aria-disabled="true"]):focus kbd {
  color: var(--color-bg);
}

.calendar_day_context_menu .calendar_shortcut_mod {
  display: inline-flex;
  align-items: center;
}

.calendar_day_context_menu .calendar_shortcut_icon {
  width: 0.9rem;
  height: 0.9rem;
}

.calendar_day_context_menu .calendar_shortcut_icon_mac,
.calendar_day_context_menu .calendar_shortcut_icon_win {
  display: none;
}

.calendar_day_context_menu .calendar_shortcut_sep {
  opacity: 0.7;
}

.calendar_day_context_menu .calendar_shortcut_key {
  line-height: 1;
}

[data-os="mac"] .calendar_day_context_menu .calendar_shortcut[data-shortcut-modifier="primary"] .calendar_shortcut_icon_mac,
[data-os="ios"] .calendar_day_context_menu .calendar_shortcut[data-shortcut-modifier="primary"] .calendar_shortcut_icon_mac {
  display: inline-block;
}

[data-os="win"] .calendar_day_context_menu .calendar_shortcut[data-shortcut-modifier="primary"] .calendar_shortcut_icon_win,
[data-os="linux"] .calendar_day_context_menu .calendar_shortcut[data-shortcut-modifier="primary"] .calendar_shortcut_icon_win,
[data-os="android"] .calendar_day_context_menu .calendar_shortcut[data-shortcut-modifier="primary"] .calendar_shortcut_icon_win,
[data-os="unknown"] .calendar_day_context_menu .calendar_shortcut[data-shortcut-modifier="primary"] .calendar_shortcut_icon_win {
  display: inline-block;
}

[data-os="ios"] .calendar_day_context_menu .calendar_shortcut,
[data-os="android"] .calendar_day_context_menu .calendar_shortcut {
  display: none;
}
