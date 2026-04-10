<?php
header('Content-Type: text/css; charset=utf-8');

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

/* Admin language editor layout hardening */
.admin-language-editor-panel,
.admin-platform-metrics-panel {
  position: relative;
  z-index: 0;
  clear: both;
}

.admin-language-editor-panel {
  display: grid;
  grid-template-columns: minmax(230px, 320px) minmax(0, 1fr);
  gap: var(--gap-md);
  align-items: start;
  isolation: isolate;
  overflow: hidden;
  margin-bottom: var(--mar-lg);
}

.admin-language-editor-panel--standalone {
  flex: 1 1 100%;
  width: 100%;
  max-width: none;
  min-width: 0;
  min-height: 34rem;
}

#main > section.panel.admin-language-editor-panel--standalone {
  flex: 1 1 100%;
  width: 100%;
  min-width: 0;
}

.admin-language-editor-panel .sidebar {
  position: static;
  top: auto;
  flex: 0 0 auto;
  width: auto;
  min-width: 0;
  height: auto;
  padding: var(--pad-sm);
}

.admin-language-editor-panel .content {
  flex: 1 1 auto;
  width: auto;
  min-width: 0;
  padding: var(--pad-sm);
}

.admin-language-editor-panel #content_shared {
  display: flex;
  flex-direction: column;
  gap: var(--gap-sm);
  min-width: 0;
}

.admin-language-editor-panel .vertical_tabs {
  max-height: 26rem;
  overflow: auto;
  padding-right: 0.25rem;
}

.admin-language-editor-panel .vertical_tabs .tab_label {
  text-align: left;
  white-space: normal;
}

.admin-language-editor-panel .language_textarea {
  width: 100%;
  min-height: 22rem;
  max-width: 100%;
  box-sizing: border-box;
  overflow: auto;
  resize: vertical;
}

.admin-language-editor-panel--standalone .language_textarea {
  min-height: 30rem;
}

.admin-platform-metrics-panel {
  margin-top: var(--mar-md);
}

@media (max-width: 900px) {
  .admin-language-editor-panel {
    grid-template-columns: 1fr;
  }

  .admin-language-editor-panel .vertical_tabs {
    max-height: 16rem;
  }
}

/* Admin Card Grid System */
.admin-card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: var(--gap-md);
  margin: var(--mar-sm) 0;
}

.admin-card {
  background: var(--panel-bg);
  color: var(--panel-text);
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  box-shadow: inset 0 1px 0 color-mix(in srgb, var(--panel-text) 10%, transparent);
  display: flex;
  flex-direction: column;
  transition: all 0.2s ease;
}

.admin-card:hover {
  box-shadow: 0 0 0 1px color-mix(in srgb, var(--color-primary) 35%, transparent);
  transform: translateY(-2px);
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

.admin-card-footer .btn {
  margin: 0;
}

/* Testing Tools */
.testing_tool_item {
  background: var(--panel-bg);
  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius);
  padding: var(--mar-md);
  margin-bottom: var(--mar-md);
}

.testing_tool_item h3 {
  margin-top: 0;
  color: var(--text-color);
}

.testing_tool_item p {
  margin: var(--mar-sm) 0;
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
  transform: translateY(-1px);
}

.btn_warning:disabled {
  background: #FFE082;
  cursor: not-allowed;
}

/* Text Muted */
.text-muted {
  color: var(--panel-text);
  opacity: 0.8;
  font-size: 0.95rem;
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
  width: 100%;
  max-width: 900px;
  padding: clamp(0.75rem, 2vw, 1.25rem);
  margin: clamp(0.75rem, 2vw, 1rem) auto;
  border-radius: var(--border-radius);
  background: var(--panel-bg);
  color: var(--panel-text);
  border: 1px solid color-mix(in srgb, var(--panel-border) 70%, var(--panel-bg));
  box-shadow: inset 0 1px 0 color-mix(in srgb, var(--panel-text) 16%, white), 0 4px 12px color-mix(in srgb, var(--panel-text) 12%, black);
  backdrop-filter: blur(10px);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.admin_panel_title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
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
  border: 1px solid var(--border-color);
  border-radius: 4px;
  background-color: var(--panel-bg);
  color: var(--text-primary);
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
  color: var(--text-primary);
}

/* Textarea focus state */
.test_results_textarea:focus {
  outline: none;
  border-color: var(--primary-color);
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
  border: 1px solid var(--border-color);
  border-radius: 4px;
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
  background: var(--elevated-bg, var(--panel-bg));
  border: 1px solid var(--border-color);
  border-radius: 4px;
  padding: var(--pad-md);
  text-align: center;
}

.metric_value {
  font-size: 1.75rem;
  font-weight: 600;
  color: var(--primary-color);
  margin-bottom: var(--mar-xs);
}

.metric_label {
  font-size: 0.875rem;
  color: var(--text-secondary);
  font-weight: 500;
}

/* Error Output Display */
.error_output {
  background: var(--panel-bg);
  border: 1px solid var(--danger-color);
  border-radius: 4px;
  padding: var(--pad-md);
  color: var(--danger-color);
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 0.875rem;
  line-height: 1.5;
  overflow-x: auto;
}

/* Status Messages */
.status {
  padding: var(--pad-md);
  border-radius: 4px;
  margin: var(--mar-md) 0;
  font-weight: 500;
}

.status.centered {
  text-align: center;
}

.status.success {
  background-color: rgba(var(--success-rgb, 34, 197, 94), 0.1);
  color: var(--success-color, #22c55e);
  border: 1px solid var(--success-color, #22c55e);
}

.status.error {
  background-color: rgba(var(--danger-rgb, 239, 68, 68), 0.1);
  color: var(--danger-color, #ef4444);
  border: 1px solid var(--danger-color, #ef4444);
}

.status.info {
  background-color: rgba(0, 0, 0, 0.05);
  color: var(--text-primary);
  border: 1px solid var(--border-color);
}

.hidden {
  display: none;
}

.final_result_callout {
  margin: var(--mar-md) 0;
  padding: var(--pad-md);
  border-radius: 6px;
  border: 1px solid var(--border-color);
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
  border-color: var(--success-color, #22c55e);
  background: rgba(var(--success-rgb, 34, 197, 94), 0.12);
}

.final_result_callout.error {
  border-color: var(--danger-color, #ef4444);
  background: rgba(var(--danger-rgb, 239, 68, 68), 0.12);
}

.final_result_callout.info {
  border-color: var(--border-color);
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
  border: 1px solid var(--border-color);
  box-shadow: 0 8px 24px color-mix(in srgb, var(--panel-text) 16%, transparent);
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
