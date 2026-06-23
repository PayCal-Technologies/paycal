<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__ . '/../../config.php';
if (headers_sent() === false) {
  header('Content-Type: text/css; charset=utf-8');
}

// Admin dashboard styles
?>

/* Settings-style layout contract for Admin page sections */
#main {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: var(--gap-md);
  width: 100%;
}

#main > section.panel,
#main > section.admin_panel {
  flex: 0 1 28%;
  min-width: 250px;
  width: auto;
  max-width: none;
  margin: 0;
}

@media (max-width: 900px) {
  #main > section.panel,
  #main > section.admin_panel {
    flex: 1 1 360px;
    min-width: 320px;
  }
}

@media (max-width: 768px) {
  #main > section.panel,
  #main > section.admin_panel {
    flex: 0 1 100%;
    min-width: 0;
  }
}

/* ── Language Editor ─────────────────────────────────────────────────── */

/* Force the language editor to span the full flex row in #main */
#main > section.lang-editor {
  flex: 1 1 100%;
  width: 100%;
  min-width: 0;
}

.lang-editor {
  display: flex;
  flex-direction: column;
  gap: var(--gap-sm);
  margin: var(--mar-sm) 0 var(--mar-lg) 0;
}

.lang-editor__header {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.lang-editor__header h2 {
  margin: 0 0 0.25rem;
}

.lang-editor__desc {
  font-size: 0.9rem;
  opacity: 0.8;
  margin: 0;
}

.lang-editor__tabs {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: var(--gap-xs);
  margin: 0;
  padding: 2px;
  width: 100%;
  box-sizing: border-box;
  border: 1px solid var(--panel-border);
  border-radius: var(--radius-panel, var(--border-radius));
  background: color-mix(in srgb, var(--panel-border) 25%, transparent);
}

/* Hide radio inputs completely — position:absolute still renders inline dots */
.lang-editor__tab-btn {
  flex: 0 0 auto;
  flex-grow: 0;
  border: 0;
  border-radius: 999px;
  padding: var(--pad-xs) var(--pad-md);
  font-weight: 600;
  white-space: nowrap;
  text-align: center;
  cursor: pointer;
  background: transparent;
  color: inherit;
  transition: var(--short-transition, 0.15s) all ease;
  user-select: none;
  font-size: inherit;
}

.lang-editor__tab-btn:hover {
  background-color: color-mix(in srgb, var(--btn-selected-back) 55%, transparent);
  color: var(--btn-selected-fore, var(--button-text));
}

.lang-editor__tab-btn[aria-selected="true"] {
  background-color: var(--btn-selected-back, var(--button-bg-active));
  color: var(--btn-selected-fore, var(--button-text));
}

.lang-editor__content {
  display: block;
  width: 100%;
  min-width: 0;
  box-sizing: border-box;
}

.lang-editor__content h2 {
  margin: 0 0 var(--gap-sm);
}

.lang-editor__content .btn {
  margin-top: var(--gap-sm);
}

.lang-editor__textarea {
  display: block;
  width: 100%;
  min-height: 48rem;
  max-width: none;
  box-sizing: border-box;
  resize: vertical;
  font-family: var(--font-mono, ui-monospace, monospace);
  font-size: 0.82rem;
  line-height: 1.6;
  background: var(--elevated, var(--panel-bg));
  color: var(--panel-text);
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  padding: 0.5rem 0.75rem;
  margin: 0;
}

@media (max-width: 900px) {
  .lang-editor__textarea {
    min-height: 28rem;
  }
}

/* ── Admin platform metrics ───────────────────────────────────────────── */
.admin-platform-metrics-panel {
  position: relative;
  z-index: 0;
  margin-top: var(--mar-md);
}

/* Admin Card Grid System */
.admin-card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: var(--gap-md);
  margin: var(--mar-sm) 0;
}

.admin-card {
  display: flex;
  flex-direction: column;
  transition: box-shadow 0.2s ease;
}

.admin-card:hover {
  box-shadow: 0 0 0 1px color-mix(in srgb, var(--color-primary) 35%, transparent);
}

.admin-card-header {
  padding: var(--pad-md);
  border-bottom: 1px solid var(--panel-border);
  background: transparent;
}

.admin-card-header h3 {
  margin: 0;
  font-size: 1.1rem;
  color: var(--panel-text);
  font-weight: 600;
  letter-spacing: -0.2px;
}

.admin-card-body {
  padding: var(--pad-md);
  flex-grow: 1;
}

.admin-card-body p {
  margin: 0;
  color: var(--panel-text);
  opacity: 0.85;
  font-size: 0.95rem;
  line-height: 1.5;
}

.admin-card-footer {
  padding: var(--pad-sm) var(--pad-md);
  border-top: 1px solid var(--panel-border);
  display: flex;
  gap: 0.75rem;
  align-items: center;
  flex-wrap: wrap;
}

/* ── GoldMaster admin tool ────────────────────────────────────────────── */
#main > section.goldmaster-admin-page {
  flex: 1 1 100%;
  width: 100%;
  max-width: none;
}

.goldmaster-admin-page {
  display: flex;
  flex-direction: column;
  gap: var(--gap-md);
}

.goldmaster-admin-page h1 {
  margin: 0;
  font-size: clamp(1.65rem, 2.4vw, 2.35rem);
}

.goldmaster-admin-page .text-muted {
  margin: 0.25rem 0 0;
  max-width: 56rem;
  line-height: 1.55;
}

.goldmaster-admin-grid {
  display: grid;
  grid-template-columns: minmax(22rem, 1fr) minmax(18rem, 0.7fr);
  gap: var(--gap-md);
  width: 100%;
}

.goldmaster-admin-grid > .admin-card {
  min-width: 0;
  width: auto;
  max-width: none;
}

.goldmaster-admin-page .admin-card-header h2 {
  margin: 0;
  font-size: 1.15rem;
}

.goldmaster-admin-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--gap-sm);
  align-items: center;
  justify-content: flex-end;
}

.goldmaster-dialog {
  width: min(calc(100vw - 2rem), 1180px);
  max-width: calc(100vw - 2rem);
  height: min(88vh, 760px);
  max-height: min(88vh, 760px);
  grid-template-rows: var(--dialog-edge-top-size, 0) auto minmax(0, 1fr) auto var(--dialog-edge-bottom-size, 0);
  box-sizing: border-box;
  padding: 0;
  overflow: hidden;
  background: var(--panel-bg);
  color: var(--panel-text);
  border: 1px solid var(--panel-border);
  border-radius: var(--radius-panel, var(--border-radius));
}

.goldmaster-dialog::backdrop {
  background: rgb(0 0 0 / 0.68);
  backdrop-filter: blur(3px);
}

.goldmaster-dialog .modal_header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--gap-md);
  margin: 0;
  padding: var(--pad-sm) var(--pad-md);
  border-bottom: 1px solid var(--panel-border);
  background: color-mix(in srgb, var(--panel-bg) 86%, var(--panel-border));
}

.goldmaster-dialog .modal_title {
  margin: 0;
  color: var(--color-primary);
  font-size: 1.35rem;
  line-height: 1.2;
}

.goldmaster-dialog .btn_close {
  min-width: 2.25rem;
  min-height: 2.25rem;
  border-radius: 999px;
  color: var(--panel-text);
  background: color-mix(in srgb, var(--panel-border) 35%, transparent);
}

.goldmaster-modal-content {
  display: grid;
  grid-template-rows: auto minmax(0, 1fr);
  gap: 0;
  min-height: 0;
  overflow: hidden;
  padding: 0;
  background: var(--dialog-back, var(--panel-bg));
}

.goldmaster-subtitle,
.goldmaster-intro,
.goldmaster-category-description,
.goldmaster-copy-status {
  color: var(--panel-text);
  opacity: 0.78;
  line-height: 1.45;
}

.goldmaster-subtitle {
  margin: 0.25rem 0 0;
  font-size: 0.9rem;
}

.goldmaster-intro {
  padding: 0.65rem var(--pad-md);
  border-bottom: 1px solid var(--panel-border);
  background: color-mix(in srgb, var(--panel-bg) 92%, var(--color-primary));
  font-size: 0.9rem;
}

.goldmaster-dialog-grid {
  display: grid;
  grid-template-columns: minmax(13rem, 0.7fr) minmax(25rem, 1.6fr) minmax(16rem, 0.9fr);
  gap: var(--gap-md);
  min-height: 0;
  padding: var(--pad-md);
  overflow: hidden;
}

.goldmaster-panel {
  min-width: 0;
  min-height: 0;
  overflow: auto;
  box-sizing: border-box;
  border: 1px solid var(--panel-border);
  border-radius: var(--radius-panel, var(--border-radius));
  background: color-mix(in srgb, var(--panel-bg) 92%, black);
  padding: var(--pad-md);
}

.goldmaster-panel h3,
.goldmaster-panel h4 {
  margin-top: 0;
  margin-bottom: var(--gap-sm);
  line-height: 1.25;
}

.goldmaster-panel h3 {
  font-size: 1.05rem;
}

.goldmaster-panel h4 {
  font-size: 0.92rem;
}

.goldmaster-category-list {
  display: grid;
  gap: var(--gap-sm);
}

.goldmaster-category-card {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 0.25rem var(--gap-sm);
  padding: var(--pad-sm);
  min-height: 0;
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  color: var(--panel-text);
  text-decoration: none;
  background: color-mix(in srgb, var(--panel-bg) 88%, var(--panel-border));
}

.goldmaster-category-card:hover,
.goldmaster-category-card:focus-visible,
.goldmaster-category-card.is-active {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 1px color-mix(in srgb, var(--color-primary) 40%, transparent);
}

.goldmaster-category-card.is-empty {
  pointer-events: none;
  opacity: 0.62;
}

.goldmaster-category-label,
.goldmaster-category-count,
.goldmaster-eyebrow,
.goldmaster-status,
.goldmaster-detail-list dt,
.goldmaster-meta-list dt {
  font-weight: 700;
}

.goldmaster-category-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.75rem;
  height: 1.75rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-primary) 22%, transparent);
}

.goldmaster-category-description {
  grid-column: 1 / -1;
  font-size: 0.82rem;
}

.goldmaster-detail-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--gap-md);
  margin-bottom: var(--gap-md);
}

.goldmaster-detail-header h3 {
  margin: 0.15rem 0 0;
  font-size: 1.12rem;
  line-height: 1.25;
}

.goldmaster-eyebrow {
  color: var(--color-primary);
  font-size: 0.82rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.goldmaster-status {
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
  border: 1px solid var(--panel-border);
  background: color-mix(in srgb, var(--panel-border) 35%, transparent);
}

.goldmaster-status-active {
  color: var(--success, #5ee08a);
  border-color: color-mix(in srgb, var(--success, #5ee08a) 45%, var(--panel-border));
}

.goldmaster-status-needs-review {
  color: var(--warning, #ffd166);
  border-color: color-mix(in srgb, var(--warning, #ffd166) 45%, var(--panel-border));
}

.goldmaster-status-deprecated,
.goldmaster-status-replaced {
  color: var(--danger, #ff6b6b);
  border-color: color-mix(in srgb, var(--danger, #ff6b6b) 45%, var(--panel-border));
}

.goldmaster-detail-list,
.goldmaster-meta-list {
  display: grid;
  gap: var(--gap-sm);
  margin: 0 0 var(--gap-md);
}

.goldmaster-detail-list div,
.goldmaster-meta-list div {
  display: grid;
  gap: 0.2rem;
}

.goldmaster-detail-list dt,
.goldmaster-meta-list dt {
  color: var(--panel-text);
  opacity: 0.75;
  font-size: 0.84rem;
}

.goldmaster-detail-list dd,
.goldmaster-meta-list dd {
  margin: 0;
  line-height: 1.45;
  overflow-wrap: anywhere;
}

.goldmaster-two-column {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--gap-md);
}

.goldmaster-list {
  display: grid;
  gap: 0.4rem;
  margin: 0;
  padding-left: 1.15rem;
  line-height: 1.45;
}

.goldmaster-file-preview {
  margin-top: var(--gap-md);
}

.goldmaster-file-preview pre {
  max-height: 20rem;
  overflow: auto;
  padding: var(--pad-md);
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  background: color-mix(in srgb, black 24%, var(--panel-bg));
  font-size: 0.82rem;
  line-height: 1.55;
}

.goldmaster-empty {
  display: grid;
  place-content: center;
  min-height: 18rem;
  text-align: center;
}

.goldmaster-dialog .modal_footer {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: var(--gap-sm);
  margin: 0;
  padding: var(--pad-sm) var(--pad-md);
  border-top: 1px solid var(--panel-border);
  background: color-mix(in srgb, var(--panel-bg) 88%, var(--panel-border));
}

.goldmaster-copy-status {
  min-width: 9rem;
  font-size: 0.85rem;
}

@media (max-width: 760px) {
  .goldmaster-admin-grid {
    grid-template-columns: 1fr;
  }

  .goldmaster-dialog {
    width: calc(100vw - 1rem);
    max-width: calc(100vw - 1rem);
    height: min(92vh, 780px);
    max-height: 92vh;
  }

  .goldmaster-dialog-grid {
    grid-template-columns: 1fr;
    overflow: auto;
  }

  .goldmaster-two-column {
    grid-template-columns: 1fr;
  }
}

/* Echo feedback admin */
.admin-feedback-page {
  display: flex;
  flex-direction: column;
  gap: var(--gap-md);
}

.admin-feedback-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--gap-md);
  flex-wrap: wrap;
}

.admin-feedback-header h1 {
  margin: 0;
}

.admin-feedback-filters {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
  gap: var(--gap-sm);
  align-items: end;
}

.admin-feedback-filters label,
.admin-feedback-detail label {
  display: flex;
  flex-direction: column;
  gap: var(--gap-xs);
  font-weight: 700;
}

.admin-feedback-filters input,
.admin-feedback-filters select,
.admin-feedback-detail input,
.admin-feedback-detail select,
.admin-feedback-detail textarea {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  background: var(--input-back, var(--panel-bg));
  color: var(--input-fore, var(--panel-text));
  padding: 0.5rem 0.6rem;
  font: inherit;
}

.admin-feedback-table {
  width: 100%;
  overflow: auto;
}

.admin-feedback-table table {
  width: 100%;
  min-width: 920px;
  border-collapse: collapse;
}

.admin-feedback-table th,
.admin-feedback-table td {
  padding: 0.55rem 0.65rem;
  border-bottom: 1px solid var(--panel-border);
  text-align: left;
  vertical-align: top;
}

.admin-feedback-table th {
  position: sticky;
  top: 0;
  background: var(--panel-bg);
  color: var(--panel-head-fore);
  z-index: 1;
}

.admin-feedback-detail {
  display: none;
}

.admin-feedback-detail:target {
  display: table-row;
}

.admin-feedback-severity {
  display: inline-flex;
  border-radius: var(--border-radius);
  padding: 0.15rem 0.45rem;
  border: 1px solid var(--panel-border);
  font-weight: 700;
}

.admin-feedback-severity-blocking,
.admin-feedback-severity-high {
  background: var(--danger-back);
  color: var(--danger-fore);
}

.admin-feedback-severity-medium {
  background: color-mix(in srgb, var(--warning-back, #fbbf24) 35%, transparent);
}

.admin-feedback-detail-grid {
  display: grid;
  grid-template-columns: minmax(18rem, 1fr) minmax(16rem, 0.75fr);
  gap: var(--gap-md);
}

.admin-feedback-detail-grid section {
  display: flex;
  flex-direction: column;
  gap: var(--gap-sm);
}

.admin-feedback-detail-grid h2 {
  margin: 0;
  font-size: var(--font-lg);
}

.admin-feedback-detail-grid dl {
  display: grid;
  grid-template-columns: minmax(7rem, 10rem) 1fr;
  gap: var(--gap-xs) var(--gap-sm);
  margin: 0;
}

.admin-feedback-detail-grid dt {
  color: var(--color-text-muted);
}

.admin-feedback-detail-grid dd {
  margin: 0;
  word-break: break-word;
}

.admin-feedback-json {
  grid-column: 1 / -1;
}

.admin-feedback-json pre {
  max-height: 20rem;
  overflow: auto;
  margin: 0;
  padding: var(--pad-sm);
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  background: color-mix(in srgb, var(--panel-bg) 88%, #000 12%);
  color: var(--panel-text);
}

@media (max-width: 800px) {
  .admin-feedback-detail-grid {
    grid-template-columns: 1fr;
  }
}

.admin-card-footer .btn {
  margin: 0;
}

.test_result {
  margin-left: var(--mar-md);
  font-weight: bold;
}

/* Warning Button */
.btn_warning {
  background: #FFC107;
  color: #000;
  border: none;
  font-weight: 600;
}

.btn_warning:hover {
  background: #FFB300;
  box-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
}

.btn_warning:disabled {
  background: #FFE082;
  cursor: not-allowed;
}

/* Test Result States */
.test_result.success {
  color: #28a745;
}

.test_result.error {
  color: #dc3545;
}

/* Admin Panel System */
.admin_panel {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.admin_panel_title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  border-bottom: 1px solid color-mix(in srgb, var(--panel-text) 12%, transparent);
  padding-bottom: 0.25rem;
}

.admin_row {
  display: grid;
  grid-template-columns: 180px 1fr;
  align-items: center;
  gap: 0.75rem;
}

.admin_label {
  font-size: 0.9rem;
  opacity: 0.9;
}

.admin_control {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.admin_control input[type="number"],
.admin_control input[type="text"],
.admin_control select {
  max-width: 200px;
}

.admin_footer {
  margin-top: 0.5rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

#modal_edit_user {
  width: min(92vw, 1160px);
  max-height: 88vh;
  overflow: auto;
}

#modal_edit_user .form_grid-2 {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

#modal_edit_user .form_col {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

#modal_edit_user .well {
  border-radius: 0.65rem;
  padding: 0.75rem;
  border: 1px solid color-mix(in srgb, var(--panel-text) 16%, transparent);
  background: linear-gradient(
    180deg,
    color-mix(in srgb, var(--panel-bg) 92%, black),
    color-mix(in srgb, var(--panel-bg) 97%, black)
  );
}

#modal_edit_user .form_col textarea {
  min-height: 10rem;
  resize: vertical;
}

.edit_user_dashboard_grid {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0;
  border-top: 1px solid rgba(255, 255, 255, 0.14);
  padding-top: 0.75rem;
}

.edit_user_session {
  margin-top: 0;
  border: 0;
  border-radius: 0.65rem;
  padding: 0.5rem 0.65rem;
  background: transparent;
}

.edit_user_session_title {
  margin: 0 0 0.5rem 0;
  font-size: 2rem;
  font-weight: 600;
  border-bottom: 1px solid rgba(255, 255, 255, 0.14);
  padding-bottom: 0.3rem;
}

.edit_user_session_row {
  display: flex;
  gap: 0.5rem;
  align-items: baseline;
  margin-bottom: 0.3rem;
  font-size: 0.95rem;
}

.edit_user_session_row::before {
  content: "\2022";
  opacity: 0.75;
}

.edit_user_session_row_sep {
  margin-top: 0.45rem;
  padding-top: 0.35rem;
  border-top: 1px solid rgba(255, 255, 255, 0.14);
}

.edit_user_session_label {
  min-width: 10.5rem;
  opacity: 0.8;
}

.edit_user_session_value {
  word-break: break-word;
  font-weight: 600;
}

.edit_user_security_dashboard {
  border-left: 1px solid rgba(255, 255, 255, 0.14);
}

.form_footer {
  grid-column: 1 / -1;
  margin-top: 0.25rem;
  padding-top: 0.8rem;
  border-top: 1px solid rgba(255, 255, 255, 0.14);
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.8rem;
}

.form_footer_left,
.form_footer_right {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  justify-content: center;
}

.form_footer_left.well,
.form_footer_right.well {
  padding: 0.45rem 0.55rem;
}

.form_footer_left .btn {
  min-width: 7rem;
  flex-grow: 0;
}

.delete_user_confirm_pill {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 193, 7, 0.55);
  background: rgba(255, 193, 7, 0.12);
  padding: 0.2rem 0.5rem;
}

.delete_user_confirm_text {
  font-size: 0.8rem;
  white-space: nowrap;
}

.delete_user_confirm_yes,
.delete_user_confirm_no {
  min-width: 3rem;
  flex-grow: 0;
}

@media (max-width: 960px) {
  #modal_edit_user .form_grid-2,
  .edit_user_dashboard_grid {
    grid-template-columns: 1fr;
  }

  .edit_user_security_dashboard {
    border-left: 0;
    border-top: 1px solid rgba(255, 255, 255, 0.14);
    margin-top: 0.5rem;
    padding-top: 0.75rem;
  }

  .form_footer {
    flex-direction: column;
    align-items: stretch;
  }

  .form_footer_left,
  .form_footer_right {
    justify-content: flex-start;
    flex-wrap: wrap;
  }
}

/* Test Dashboard Styles */

/* Test Results Section */
.test_results_section {
  display: flex;
  flex-direction: column;
  gap: var(--gap-md);
  margin: var(--mar-md) 0;
  width: 100%;
}

/* Test Results Textarea */
.test_results_textarea {
  width: 100%;
  min-height: 300px;
  max-height: 600px;
  padding: var(--pad-md);
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  background-color: var(--panel-bg);
  color: var(--panel-text);
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 0.875rem;
  line-height: 1.5;
  resize: vertical;
  overflow-y: auto;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
}

/* Textarea disabled state */
.test_results_textarea:disabled {
  opacity: 1;
  cursor: default;
  background-color: var(--panel-bg);
  color: var(--panel-text);
}

/* Textarea focus state */
.test_results_textarea:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05), 0 0 0 3px rgba(var(--primary-rgb), 0.1);
}

/* Test Runner Controls */
.test_runner_controls {
  display: flex;
  flex-wrap: wrap;
  gap: var(--gap-sm);
  margin: var(--mar-md) 0;
}

/* Test Spinner */
.spinner {
  display: flex;
  align-items: center;
  gap: var(--gap-sm);
  padding: var(--pad-md);
  background-color: var(--panel-bg);
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  margin: var(--mar-md) 0;
}

.spinner.hidden {
  display: none;
}

.spinner_icon {
  display: inline-block;
  animation: spin 1s linear infinite;
  font-size: 1.25rem;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Metrics Grid */
.metrics_grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: var(--gap-md);
  margin: var(--mar-md) 0;
}

.metric_card {
  background: var(--elevated, var(--panel-bg));
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  padding: var(--pad-md);
  text-align: center;
}

.metric_value {
  font-size: 1.75rem;
  font-weight: 600;
  color: var(--primary);
  margin-bottom: var(--mar-xs);
}

.metric_label {
  font-size: 0.875rem;
  color: var(--panel-text);
  opacity: 0.7;
  font-weight: 500;
}

/* Error Output Display */
.error_output {
  background: var(--panel-bg);
  border: 1px solid var(--danger);
  border-radius: var(--border-radius);
  padding: var(--pad-md);
  color: var(--danger);
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 0.875rem;
  line-height: 1.5;
  overflow-x: auto;
}

.final_result_callout {
  margin: var(--mar-md) 0;
  padding: var(--pad-md);
  border-radius: var(--border-radius);
  border: 1px solid var(--panel-border);
  background: color-mix(in srgb, var(--panel-bg) 88%, var(--panel-text) 12%);
}

.final_result_title {
  margin: 0 0 var(--mar-xs) 0;
  font-size: 0.95rem;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  opacity: 0.9;
}

.final_result_message {
  margin: 0;
  font-size: 0.95rem;
}

.final_result_callout.success {
  border-color: var(--success, #22c55e);
  background: rgba(var(--success-rgb, 34, 197, 94), 0.12);
}

.final_result_callout.error {
  border-color: var(--danger, #ef4444);
  background: rgba(var(--danger-rgb, 239, 68, 68), 0.12);
}

.final_result_callout.info {
  border-color: var(--panel-border);
  background: rgba(0, 0, 0, 0.05);
}

/* First-class Test Runner Panel */
#main > section.panel.test_runner_primary {
  flex: 0 0 100%;
  width: 100%;
  min-width: 0;
  position: sticky;
  top: 0;
  z-index: 30;
  margin: 0;
  padding: var(--pad-md);
}

.test_runner_shell {
  min-height: calc(100vh - 9rem);
  display: flex;
  flex-direction: column;
}

.test_runner_shell #results_container {
  flex: 1 1 auto;
  min-height: 0;
}

.test_runner_shell .test_results_textarea {
  min-height: calc(100vh - 22rem);
  max-height: none;
  height: 100%;
}

@media (max-width: 900px) {
  #main > section.panel.test_runner_primary {
    position: static;
    top: auto;
    z-index: auto;
  }

  .test_runner_shell {
    min-height: calc(100vh - 7rem);
  }

  .test_runner_shell .test_results_textarea {
    min-height: calc(100vh - 18rem);
  }
}

/* ── Site Color Palette panel ──────────────────────────────────────────────── */
.admin-palette-grid {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  margin-top: 0.75rem;
}

.admin-palette-row {
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 0.35rem;
}

.admin-palette-swatch {
  position: relative;
  border-radius: 6px;
  padding: 0.6rem 0.5rem 0.45rem;
  min-height: 3.5rem;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  gap: 0.1rem;
  overflow: hidden;
  border: 1px solid rgba(255,255,255,0.08);
  cursor: default;
}

.admin-palette-swatch-label {
  font-size: 0.72rem;
  font-weight: 600;
  color: rgba(255,255,255,0.92);
  text-shadow: 0 1px 2px rgba(0,0,0,0.6);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.2;
}

.admin-palette-swatch-hex {
  font-size: 0.65rem;
  font-family: monospace;
  color: rgba(255,255,255,0.7);
  text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}

.admin-flash {
  border-left: 4px solid var(--border);
}

.admin-flash--success {
  border-left-color: var(--color-success, #2da44e);
}

.admin-flash--danger {
  border-left-color: var(--color-danger, #cf222e);
}

.ledger-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(8rem, 1fr));
  gap: 0.75rem;
  margin: 1rem 0;
}

.ledger-summary-grid > div {
  border: 1px solid var(--border, #d0d7de);
  border-radius: 6px;
  padding: 0.85rem;
  background: var(--panel-bg, #fff);
}

.ledger-summary-grid strong {
  display: block;
  font-size: 1.6rem;
  line-height: 1;
}

.ledger-summary-grid span {
  display: block;
  margin-top: 0.35rem;
  color: var(--muted, #57606a);
  font-size: 0.85rem;
}

.ledger-table code {
  font-size: 0.82rem;
  white-space: nowrap;
}

.ledger-status {
  display: inline-flex;
  min-width: 6.5rem;
  justify-content: center;
  border-radius: 999px;
  padding: 0.2rem 0.55rem;
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
}

.ledger-status.status-clean {
  background: color-mix(in srgb, var(--color-success, #2da44e) 18%, transparent);
  color: var(--color-success, #2da44e);
}

.ledger-status.status-drift {
  background: color-mix(in srgb, var(--color-danger, #cf222e) 18%, transparent);
  color: var(--color-danger, #cf222e);
}

.ledger-status.status-missing {
  background: color-mix(in srgb, var(--color-warning, #bf8700) 18%, transparent);
  color: var(--color-warning, #9a6700);
}

@media (max-width: 760px) {
  .ledger-summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

<?php
/* ── Admin palette swatch colors (CSP-safe: no inline styles) ────────────── */
foreach (\PayCal\Domain\Config\SiteColorPalette::palette() as $swColor) {
  $swHex = $swColor['hex'];
  echo ".admin-palette-swatch[data-hex=\"{$swHex}\"] { background: {$swHex}; }\n";
}
?>
