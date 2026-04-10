<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
/**
 * PayCal - Phantom Wing Error Reporting Panel Styles
 * 
 * Styles for the client-side error reporting and debugging panel
 * Uses PayCal theme CSS variables for consistent styling
 */

.pw-error-table {
  width: 100%;
  border-collapse: collapse;
  background: var(--panel-bg);
  font-family: var(--monospace);
  font-size: var(--font-sm);
  color: var(--panel-text);
}

.pw-error-table thead {
  border-bottom: calc(var(--border-size) * 2) solid var(--panel-border);
  background: var(--panel-head-bg);
}

.pw-error-table th {
  padding: var(--pad-xs) var(--pad-sm);
  font-weight: bold;
  text-align: left;
  color: var(--panel-head-text);
}

.pw-error-table .pw-col-type {
  width: 80px;
}

.pw-error-table .pw-col-location {
  width: 150px;
}

.pw-error-table .pw-col-message {
  width: auto;
}

.pw-error-table .pw-col-count {
  width: 60px;
}

.pw-error-table td {
  padding: var(--pad-xs) var(--pad-sm);
  border-bottom: var(--border-size) solid var(--panel-border);
  word-break: break-word;
}

.pw-error-table tbody tr.severity-high {
  background: var(--color-danger);
  opacity: 0.15;
}

.pw-error-table tbody tr.severity-high:hover {
  opacity: 0.25;
}

.pw-error-table tbody tr.severity-medium {
  background: var(--color-primary);
  opacity: 0.15;
}

.pw-error-table tbody tr.severity-medium:hover {
  opacity: 0.25;
}

.pw-error-table tbody tr.severity-low {
  background: var(--panel-bg);
}

.pw-error-table tbody tr.severity-low:hover {
  background: var(--panel-head-bg);
}

.pw-error-table tfoot {
  border-top: calc(var(--border-size) * 2) solid var(--panel-border);
  background: var(--panel-head-bg);
  font-weight: bold;
}

.pw-error-table tfoot td {
  padding: var(--pad-sm);
  border: none;
}

.pw-error-table .pw-cell-bold {
  font-weight: bold;
}

.pw-error-table .pw-cell-center {
  text-align: center;
}

.pw-metrics-section {
  display: flex;
  flex-direction: column;
  gap: var(--gap-md);
}

.pw-metrics-title {
  margin: 0;
  font-size: var(--font-lg);
  color: var(--panel-head-text);
}

.pw-metrics-content {
  display: flex;
  flex-direction: column;
}

.pw-metrics-empty {
  margin: 0;
  font-size: var(--font-sm);
  color: var(--color-text-muted);
}

.pw-dashboard-footer {
  margin-top: var(--mar-sm);
  padding-top: var(--pad-sm);
  border-top: var(--border-size) solid var(--panel-border);
  font-size: var(--font-xs);
}

.pw-dashboard-footer p {
  margin: 0;
  color: var(--color-text-muted);
}

.pw-dashboard-footer code {
  padding: var(--pad-xs) calc(var(--pad-xs) * 1.5);
  border-radius: calc(var(--border-radius) / 3);
  background: var(--panel-bg);
  font-family: var(--monospace);
  color: var(--color-primary);
}

.pw-error-summary {
  margin-bottom: var(--mar-sm);
  padding: var(--pad-sm) var(--pad-md);
  border-left: calc(var(--border-size) * 4) solid var(--color-danger);
  background: var(--panel-bg);
  font-family: var(--monospace);
  font-size: var(--font-xs);
  line-height: var(--line-height);
  white-space: pre-wrap;
  word-break: break-word;
  color: var(--panel-text);
}

.pw-error-divider {
  height: var(--border-size);
  margin: var(--mar-sm) 0;
  background: var(--panel-border);
}

.pw-telemetry-section {
  margin-top: var(--mar-sm);
  padding-top: var(--pad-sm);
  border-top: var(--border-size) solid var(--panel-border);
}

.pw-telemetry-title {
  margin: 0 0 var(--gap-sm, 0.5rem) 0;
  font-size: var(--font-md);
  color: var(--panel-head-text);
}

.pw-telemetry-row {
  display: flex;
  justify-content: space-between;
  gap: var(--gap-md);
  padding: 2px 0;
  font-size: var(--font-sm);
}

.pw-telemetry-key {
  color: var(--color-text-muted);
  font-family: var(--monospace);
  word-break: break-all;
}

.pw-telemetry-count {
  color: var(--panel-text);
  font-weight: 600;
  white-space: nowrap;
}
