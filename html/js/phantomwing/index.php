<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();
CORS::renderContentType('text/javascript');
?>
/**
 * ============================================================================
 * PHANTOM WING - JavaScript Error Reporting Daemon
 * ============================================================================
 *
 * INTERNAL MAINTAINER NOTES:
 * - This module is intentionally passive: it captures and collates, but does not
 *   interrupt user flow or throw from instrumentation paths.
 * - Collation keys are stable (`type:location:pattern`) so trend analysis and
 *   grouped dashboard rendering remain deterministic across sessions.
 * - Public API (`log`, `warn`, `error`, `report`, table/export helpers) is
 *   consumed by core modules and should remain backward compatible.
 * - Guardian integration is required for HTML panel rendering to preserve
 *   CSP-safe DOM insertion behavior.
 * 
 * OVERVIEW:
 * Phantom Wing is a comprehensive error capture and collation system that
 * monitors ALL sources of JavaScript errors and failures in the PayCal
 * frontend, silently collecting them during page execution and reporting
 * a clean summary once the page has fully loaded and settled.
 * 
 * WHAT IT CAPTURES:
 * =====================
 * 1. Console Calls (ALL types):
 *    - console.error() → Explicit errors
 *    - console.warn()  → Warnings and issues
 *    - console.log()   → General logs
 *    - console.info()  → Informational messages
 *    - console.debug() → Debug output
 * 
 * 2. Synchronous Errors:
 *    - Uncaught exceptions thrown during execution
 *    - Try/catch blocks that throw
 *    - Reference errors, Type errors, Syntax errors
 * 
 * 3. Asynchronous Errors:
 *    - Unhandled Promise rejections
 *    - Failed fetch() calls
 *    - setTimeout/setInterval errors
 *    - Async/await failures
 * 
 * 4. Network & API Errors:
 *    - HTTP error responses (4xx, 5xx)
 *    - Network failures
 *    - API endpoint failures
 *    - CORS errors
 * 
 * WHY IT'S IMPORTANT:
 * ====================
 * Without this system, a page with validation failures can generate 100+
 * console messages, making it impossible for developers to:
 * - Understand what actually failed
 * - Identify real problems vs spam
 * - Debug in production environments
 * 
 * Phantom Wing:
 * - Collates identical errors by type, source location, and pattern
 * - Silently collects during execution, reports after page settles
 * - Provides formatted table summary with error counts and locations
 * - Maintains full error details for inspection and export
 * - Offers multiple output formats: console table, HTML panel, JSON data
 * 
 * USAGE:
 * ========
 * Import this file before your application code:
 *   import * as PW from '/js/phantomwing/';
 * 
 * The system works automatically in the background. No explicit calls needed.
 * Just use console.error(), console.warn(), throw errors, reject promises,
 * and Phantom Wing will capture everything silently.
 * 
 * For explicit error logging with collation:
 *   PW.log('message');    // collated log
 *   PW.warn('message');   // collated warning
 *   PW.error('message');  // collated error
 *   PW.report('category', 'type', data); // telemetry event
 * 
 * Table and Export Functions:
 *   PW.formatErrorTable() → { columns, rows, totalRows, totalErrors }
 *   PW.generateErrorTableHTML() → HTML table markup
 *   PW.exportErrorData() → { timestamp, summary, errors } JSON
 *   PW.injectErrorPanel() → Display error panel in DOM (dev tools)
 *   PW.getState() → Raw state object for inspection
 * 
 * Final report is automatically generated on window.load event.
 * ============================================================================
 */

// ============================================================================
// ERROR STATE MANAGEMENT
// ============================================================================

const _pw_error_state = {
  errors: new Map(),      // key: "type:file:line:pattern", value: { count, message, stack }
  limit: 5,               // Show first 5 instances before collating
  summaryShown: new Set() // Tracks which keys have shown summary
};

const _pw_telemetry_state = {
  events: new Map(),      // key: "category:type", value: { count, sample }
  summaryShown: new Set()
};

const _pw_telemetry_delivery = {
  queue: [],
  flushTimer: null,
  lastFlushAt: 0,
  minBatchSize: 3,
  maxQueueSize: 120,
  baseDelayMs: 4000,
  jitterMs: 10000,
  maxHoldMs: 15000,
  consecutiveFailures: 0,
  disabledUntil: 0,
};

const _pw_sensitive_key_pattern = /(user|org|organization|site)[_-]?(uuid|id)$/i;
const _pw_uuid_pattern = /\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\b/gi;

// Store original console before we patch it
const _pw_original_console = {
  log: console.log,
  warn: console.warn,
  error: console.error,
  info: console.info,
  debug: console.debug,
  group: console.group,
  groupEnd: console.groupEnd,
  table: console.table
};

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Extract file and line number from JavaScript stack trace
 */
function _pw_extract_stack_location(stackStr) {
  if (!stackStr) return 'unknown:unknown:unknown';
  const lines = stackStr.split('\n');
  
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i];
    // Skip phantomwing internally
    if (line.includes('phantomwing')) continue;
    
    // Extract full URL with filename:line from various stack formats
    // Match patterns like: https://example.com/page?v=123:456:789 or /js/earnings:456:789
    const urlMatch = line.match(/(?:at\s+)?(?:\w+\s+)?(?:\()?([^\s:]+):(\d+):(\d+)/);
    if (urlMatch) {
      const fullUrl = urlMatch[1];
      const lineNum = urlMatch[2];
      return `${fullUrl}:${lineNum}`;
    }
  }
  
  return 'unknown:unknown:unknown';
}

function _pw_get_debug_settings() {
  const settings = (typeof window !== 'undefined' && window.PAYCAL_DEBUG_SETTINGS && typeof window.PAYCAL_DEBUG_SETTINGS === 'object')
    ? window.PAYCAL_DEBUG_SETTINGS
    : null;

  return {
    consoleEnabled: Boolean(settings?.consoleEnabled),
    fineGrainedEnabled: Boolean(settings?.fineGrainedEnabled),
    networkEnabled: Boolean(settings?.networkEnabled),
  };
}

function _pw_is_console_debug_enabled() {
  const settings = _pw_get_debug_settings();
  return settings.consoleEnabled || settings.fineGrainedEnabled;
}

function _pw_is_network_debug_enabled() {
  return _pw_get_debug_settings().networkEnabled;
}

function _pw_redact_text(input) {
  return String(input || '')
    .replace(_pw_uuid_pattern, '[REDACTED_UUID]')
    .replace(/\b(user_uuid|org_uuid|organization_uuid|site_uuid|user_id|org_id|organization_id|site_id)\s*[=:]\s*[^\s,;]+/gi, '$1=[REDACTED]');
}

function _pw_redact_value(value, key = '', seen = new WeakSet()) {
  if (_pw_sensitive_key_pattern.test(String(key || ''))) {
    return '[REDACTED]';
  }

  if (typeof value === 'string') {
    return _pw_redact_text(value);
  }

  if (value === null || typeof value !== 'object') {
    return value;
  }

  if (seen.has(value)) {
    return '[Circular]';
  }
  seen.add(value);

  if (Array.isArray(value)) {
    return value.map((item) => _pw_redact_value(item, '', seen));
  }

  const output = {};
  for (const [childKey, childValue] of Object.entries(value)) {
    output[childKey] = _pw_redact_value(childValue, childKey, seen);
  }

  return output;
}

function _pw_should_emit_console(type) {
  if (type === 'error') {
    return true;
  }

  return _pw_is_console_debug_enabled();
}

/**
 * Normalize error message by replacing variable data with placeholders
 */
function _pw_normalize_message(msg) {
  if (!msg) return 'unknown';
  
  return String(msg)
    .replace(/\d{4}-\d{2}-\d{2}/g, 'DATE')           // Dates
    .replace(/[0-9a-f]{8}-[0-9a-f-]{27}/gi, 'UUID')  // UUIDs
    .replace(/\b\d+\b/g, 'NUM')                      // Numbers
    .substring(0, 80); // Use first 80 chars as pattern
}

/**
 * Get caller location from current stack
 */
function _pw_get_caller() {
  const stack = new Error().stack || '';
  return _pw_extract_stack_location(stack);
}

// ============================================================================
// ERROR COLLATION LOGIC
// ============================================================================

/**
 * Collate an error into the error state
 */
function _pw_collate_error(type, message, stack) {
  if (type === 'log' || type === 'info' || type === 'debug') {
    return;
  }

  const safeMessage = _pw_redact_text(message);
  const location = _pw_extract_stack_location(stack);
  const pattern = _pw_normalize_message(safeMessage);
  const key = `${type}:${location}:${pattern}`;
  
  if (!_pw_error_state.errors.has(key)) {
    _pw_error_state.errors.set(key, {
      type,
      location,
      message: safeMessage,
      count: 0
    });
  }
  
  const entry = _pw_error_state.errors.get(key);
  entry.count++;
}

/**
 * Collate a telemetry event
 */
function _pw_collate_telemetry(category, type, data) {
  const key = `${category}:${type}`;
  const redactedData = _pw_redact_value(data);
  
  if (!_pw_telemetry_state.events.has(key)) {
    _pw_telemetry_state.events.set(key, {
      category,
      type,
      sample: redactedData,
      count: 0
    });
  }
  
  const entry = _pw_telemetry_state.events.get(key);
  entry.count++;
}

function _pw_safe_type(value) {
  return String(value || 'unknown')
    .toLowerCase()
    .replace(/[^a-z0-9_.:-]/g, '_')
    .slice(0, 64) || 'unknown';
}

function _pw_enqueue_telemetry(category, type, data) {
  if (!_pw_is_network_debug_enabled()) {
    return;
  }

  _pw_telemetry_delivery.queue.push({
    category: _pw_safe_type(category),
    type: _pw_safe_type(type),
    data: _pw_redact_value(data && typeof data === 'object' ? data : {}),
    at: Date.now(),
  });

  if (_pw_telemetry_delivery.queue.length > _pw_telemetry_delivery.maxQueueSize) {
    _pw_telemetry_delivery.queue.shift();
  }
}

function _pw_schedule_flush() {
  if (_pw_telemetry_delivery.flushTimer) {
    return;
  }

  const jitter = Math.floor(Math.random() * _pw_telemetry_delivery.jitterMs);
  const delay = _pw_telemetry_delivery.baseDelayMs + jitter;

  _pw_telemetry_delivery.flushTimer = setTimeout(() => {
    _pw_telemetry_delivery.flushTimer = null;
    void _pw_flush_telemetry_queue('timer');
  }, delay);
}

async function _pw_post_telemetry(type, fields) {
  if (!_pw_is_network_debug_enabled()) {
    return;
  }

  const now = Date.now();
  if (_pw_telemetry_delivery.disabledUntil > now) {
    return;
  }

  const path = String(window.location?.pathname || '');
  if (path.startsWith('/auth/')) {
    return;
  }

  try {
    const response = await fetch('/api/v1/encryption/telemetry', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
      },
      keepalive: true,
      body: JSON.stringify({ type, fields: _pw_redact_value(fields) }),
    });

    if (response.ok) {
      _pw_telemetry_delivery.consecutiveFailures = 0;
      return;
    }

    _pw_telemetry_delivery.consecutiveFailures += 1;
    if (response.status >= 500 || _pw_telemetry_delivery.consecutiveFailures >= 2) {
      _pw_telemetry_delivery.disabledUntil = now + 600000;
    }
  } catch {
    _pw_telemetry_delivery.consecutiveFailures += 1;
    if (_pw_telemetry_delivery.consecutiveFailures >= 2) {
      _pw_telemetry_delivery.disabledUntil = now + 600000;
    }
  }
}

async function _pw_flush_telemetry_queue(reason = 'timer') {
  const queue = _pw_telemetry_delivery.queue;
  if (queue.length === 0) {
    return;
  }

  const now = Date.now();
  const ageSinceLast = now - _pw_telemetry_delivery.lastFlushAt;
  if (reason === 'timer' && queue.length < _pw_telemetry_delivery.minBatchSize && ageSinceLast < _pw_telemetry_delivery.maxHoldMs) {
    _pw_schedule_flush();
    return;
  }

  _pw_telemetry_delivery.lastFlushAt = now;
  _pw_telemetry_delivery.queue = [];

  const grouped = new Map();
  for (const event of queue) {
    const key = `${event.category}:${event.type}`;
    if (!grouped.has(key)) {
      grouped.set(key, {
        category: event.category,
        type: event.type,
        count: 0,
      });
    }

    grouped.get(key).count += 1;
  }

  const hourBucket = Math.floor(now / 3600000);
  for (const entry of grouped.values()) {
    await _pw_post_telemetry(
      `pw.${entry.category}.${entry.type}`,
      {
        count: entry.count,
        bucket_hour: hourBucket,
        flush_reason: reason,
      }
    );
  }
}

// ============================================================================
// GLOBAL ERROR HANDLERS
// ============================================================================

/**
 * Intercept all console methods to silently collect output
 */
function _pw_patch_console() {
  ['error', 'warn', 'log', 'info', 'debug'].forEach(type => {
    console[type] = function(...args) {
      const sanitizedArgs = args.map((arg) => _pw_redact_value(arg));

      // 1. Call native console FIRST
      if (_pw_should_emit_console(type) && typeof _pw_original_console[type] === 'function') {
        _pw_original_console[type].apply(console, sanitizedArgs);
      }

      // 2. Collate silently
      const message = sanitizedArgs.map(arg =>
        typeof arg === 'string' ? arg : JSON.stringify(arg)
      ).join(' ');

      _pw_collate_error(type, message, new Error().stack);
    };
  });
}

/**
 * Catch uncaught synchronous errors
 */
function _pw_setup_error_handler() {
  window.addEventListener('error', (event) => {
    const message = event.message || 'Unknown error';
    const filename = event.filename || 'unknown';
    const lineno = event.lineno || 0;
    const stack = `at ${filename}:${lineno}`;

    _pw_collate_error('error', `[UNCAUGHT] ${message}`, stack);
  });
}

/**
 * Catch unhandled promise rejections
 */
function _pw_setup_rejection_handler() {
  window.addEventListener('unhandledrejection', (event) => {
    const reason = event.reason || 'Unknown rejection';
    const message = reason.message || String(reason);
    const stack = reason.stack || new Error().stack;
    
    _pw_collate_error('error', `[PROMISE] ${message}`, stack);
    
    // Prevent uncaught rejection warning
    event.preventDefault();
  });
}

/**
 * Intercept fetch to catch network and API errors
 */
function _pw_patch_fetch() {
  const originalFetch = window.fetch;
  
  window.fetch = function(...args) {
    return originalFetch.apply(this, args)
      .catch(error => {
        if (error && error.name === 'AbortError') {
          throw error;
        }
        _pw_collate_error('error', `[FETCH] ${error.message}`, error.stack);
        throw error;
      })
      .then(response => {
        if (!response.ok) {
          const errorMsg = `[API] ${response.status} ${response.statusText} - ${args[0]}`;
          _pw_collate_error('error', errorMsg, new Error().stack);
        }
        return response;
      });
  };
}

// ============================================================================
// PUBLIC API
// ============================================================================

/**
 * Log a message with collation (equivalent to console.log, but routed through system)
 */
function _pw_format_args(args) {
  return args
    .map((arg) => {
      const redacted = _pw_redact_value(arg);
      if (typeof redacted === 'string') {
        return redacted;
      }

      try {
        return JSON.stringify(redacted);
      } catch {
        return String(redacted);
      }
    })
    .join(' ');
}

function log(...args) {
  const message = _pw_format_args(args);
  if (_pw_should_emit_console('log') && typeof _pw_original_console.log === 'function') {
    _pw_original_console.log(message);
  }
  _pw_collate_error('log', message, _pw_get_caller());
}

/**
 * Log a warning with collation
 */
function warn(...args) {
  const message = _pw_format_args(args);
  if (_pw_should_emit_console('warn') && typeof _pw_original_console.warn === 'function') {
    _pw_original_console.warn(message);
  }
  _pw_collate_error('warn', message, _pw_get_caller());
}

/**
 * Log an error with collation
 */
function error(...args) {
  const safeMessage = _pw_format_args(args);
  if (typeof _pw_original_console.error === 'function') {
    _pw_original_console.error(safeMessage);
  }
  _pw_collate_error('error', safeMessage, _pw_get_caller());
}

/**
 * Report a telemetry event
 */
function report(category, type, data) {
  if (!_pw_is_network_debug_enabled()) {
    return;
  }

  _pw_collate_telemetry(category, type, data);
  _pw_enqueue_telemetry(category, type, data);
  _pw_schedule_flush();
}

/**
 * Manually reset collected errors (useful for testing)
 */
function reset() {
  _pw_error_state.errors.clear();
  _pw_error_state.summaryShown.clear();
  _pw_telemetry_state.events.clear();
  _pw_telemetry_state.summaryShown.clear();
  _pw_telemetry_delivery.queue = [];
  if (_pw_telemetry_delivery.flushTimer) {
    clearTimeout(_pw_telemetry_delivery.flushTimer);
    _pw_telemetry_delivery.flushTimer = null;
  }
}

/**
 * Get current error state (for inspection)
 */
function getState() {
  return {
    errors: Object.fromEntries(_pw_error_state.errors),
    telemetry: Object.fromEntries(_pw_telemetry_state.events)
  };
}

// ============================================================================
// ERROR TABLE / SUMMARY TABLE
// ============================================================================

/**
 * Column definition for error summary table
 * Based on MDN JavaScript Error properties and Phantom Wing metadata
 */
const _pw_error_columns = [
  { key: 'type', label: 'Type', width: '80px' },
  { key: 'location', label: 'Location', width: '150px' },
  { key: 'message', label: 'Error Message', width: 'auto' },
  { key: 'count', label: 'Count', width: '60px' }
];

/**
 * Format error data into a summary table structure
 * Returns array of formatted rows ready for display
 */
function formatErrorTable(columns = _pw_error_columns) {
  const rows = [];
  
  for (const [key, entry] of _pw_error_state.errors) {
    rows.push({
      type: entry.type.toUpperCase(),
      location: entry.location,
      message: entry.message,
      count: entry.count.toString(),
      severity: entry.type === 'error' ? 'high' : entry.type === 'warn' ? 'medium' : 'low'
    });
  }
  
  // Sort by count descending (most frequent first)
  rows.sort((a, b) => parseInt(b.count) - parseInt(a.count));
  
  return {
    columns,
    rows,
    totalRows: rows.length,
    totalErrors: rows.reduce((sum, r) => sum + parseInt(r.count), 0)
  };
}

/**
 * Generate HTML table markup for errors
 * Lightweight, accessible, sortable interface
 */
function generateErrorTableHTML() {
  const data = formatErrorTable();
  if (data.totalRows === 0) return '';
  
  let html = '<table class="pw-error-table" aria-label="Error Summary">';
  
  // Header
  html += '<thead><tr>';
  for (const col of data.columns) {
      html += `<th data-key="${col.key}" class="pw-col-${col.key}">${col.label}</th>`;
  }
  html += '</tr></thead>';
  
  // Body
  html += '<tbody>';
  for (const row of data.rows) {
    const severityClass = `severity-${row.severity}`;
    html += `<tr class="${severityClass}" data-count="${row.count}">`;
    for (const col of data.columns) {
      const value = row[col.key] || '';
      html += `<td data-key="${col.key}">${_pw_escape_html(value)}</td>`;
    }
    html += '</tr>';
  }
  html += '</tbody>';
  
  // Footer with totals
  html += '<tfoot><tr>';
  html += '<td colspan="2" class="pw-cell-bold">TOTALS</td>';
  html += `<td>${data.totalRows} unique location(s)</td>`;
  html += `<td class="pw-cell-bold pw-cell-center">${data.totalErrors}</td>`;
  html += '</tr></tfoot>';
  
  html += '</table>';
  
  return html;
}

/**
 * Export error data as JSON for programmatic use
 */
function exportErrorData() {
  const data = formatErrorTable();
  return {
    timestamp: new Date().toISOString(),
    summary: {
      totalErrors: data.totalErrors,
      uniqueLocations: data.totalRows,
      byType: _summarizeByType()
    },
    errors: data.rows
  };
}

/**
 * Generate summary statistics by error type
 */
function _summarizeByType() {
  const summary = {};
  for (const [key, entry] of _pw_error_state.errors) {
    if (!summary[entry.type]) {
      summary[entry.type] = { count: 0, locations: 0 };
    }
    summary[entry.type].count += entry.count;
    summary[entry.type].locations++;
  }
  return summary;
}

/**
 * Escape HTML special characters for safe display
 */
function _pw_escape_html(text) {
  if (!text) return '';
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return String(text).replace(/[&<>"']/g, c => map[c]);
}

/**
 * Generate executive summary text of all collected errors
 * Returns formatted text with line breaks
 */
function _pw_generate_error_summary() {
  if (_pw_error_state.errors.size === 0) {
    return 'No errors detected.';
  }
  
  const byType = {};
  for (const [key, entry] of _pw_error_state.errors) {
    if (!byType[entry.type]) {
      byType[entry.type] = [];
    }
    byType[entry.type].push({
      count: entry.count,
      message: entry.message,
      location: entry.location
    });
  }
  
  const totalErrors = Array.from(_pw_error_state.errors.values()).reduce((sum, e) => sum + e.count, 0);
  const parts = [];
  parts.push(`Total issues: ${totalErrors} across ${_pw_error_state.errors.size} grouped location(s).`);

  for (const [type, errors] of Object.entries(byType)) {
    const typeCount = errors.reduce((sum, e) => sum + e.count, 0);
    const top = errors
      .slice()
      .sort((a, b) => b.count - a.count)
      .slice(0, 3)
      .map(err => `${err.message.substring(0, 70)} (×${err.count})`)
      .join('; ');
    parts.push(`${type.toUpperCase()} ${typeCount}: ${top}`);
  }

  return parts.join('\n');
}

/**
 * Generate an HTML block for telemetry events collected this session.
 */
function generateTelemetryHTML() {
  if (_pw_telemetry_state.events.size === 0) return '';
  const rows = Array.from(_pw_telemetry_state.events.entries())
    .sort((a, b) => b[1].count - a[1].count)
    .map(([key, entry]) => {
      const safeKey = _pw_escape_html(key);
      const count = entry.count;
      return `<div class="pw-telemetry-row"><span class="pw-telemetry-key">${safeKey}</span><span class="pw-telemetry-count">×${count}</span></div>`;
    })
    .join('');
  return `
    <div class="pw-telemetry-section">
      <h3 class="pw-telemetry-title">Telemetry</h3>
      ${rows}
    </div>
  `;
}

/**
 * Render Phantom Wing diagnostics inside the Dashboard dialog
 */
function renderDashboardMetrics() {
  const metricsContent = document.getElementById('pw_metrics_content');
  if (!metricsContent) return;

  const telemetryHtml = generateTelemetryHTML();

  if (_pw_error_state.errors.size === 0) {
    window.Guardian.setHTML(metricsContent, `<p class="pw-metrics-empty">No issues detected.</p>${telemetryHtml}`);
    return;
  }

  const errorTableHtml = generateErrorTableHTML();
  if (!errorTableHtml) {
    window.Guardian.setHTML(metricsContent, `<p class="pw-metrics-empty">No grouped metrics available.</p>${telemetryHtml}`);
    return;
  }

  window.Guardian.setHTML(metricsContent, `
    <div class="pw-error-summary">
      ${_pw_generate_error_summary().split('\n').map(line => _pw_escape_html(line)).join('<br>')}
    </div>
    <div class="pw-error-divider"></div>
    ${errorTableHtml}
    <div class="pw-dashboard-footer">
      <p><strong>Data Export:</strong> Use <code>PW.exportErrorData()</code> to get JSON</p>
    </div>
    ${telemetryHtml}
  `);
}

/**
 * Backward-compatible alias: metrics are now rendered in Dashboard only.
 */
function injectErrorPanel() {
  renderDashboardMetrics();
}

// ============================================================================
// REPORT GENERATION
// ============================================================================

/**
 * Generate final error report after page load
 */
function _pw_generate_final_report() {
  const userAgent = String(navigator?.userAgent || '').toLowerCase();
  if (userAgent.includes('lightpanda')) {
    return;
  }

  if (!_pw_is_console_debug_enabled()) {
    return;
  }

  if (_pw_error_state.errors.size === 0) {
    try {
      renderDashboardMetrics();
    } catch (err) {
      _pw_original_console.log('[PHANTOM WING] Dashboard render skipped due to runtime limitations.');
    }
      _pw_original_console.log('[PHANTOM WING] All clear - no errors or warnings detected.');
    return;
  }

    _pw_original_console.log('[PHANTOM WING] Error Summary');
  
  // Display executive summary
  if (_pw_error_state.errors.size > 0) {
    const summaryText = _pw_generate_error_summary();
      _pw_original_console.log(summaryText);
  }

  // Display error summary table
  if (_pw_error_state.errors.size > 0) {
    const errorData = formatErrorTable();
    // Lightpanda can throw on console.table for structured rows; fall back to log.
    try {
      _pw_original_console.table(errorData.rows);
    } catch (err) {
      _pw_original_console.log('[PHANTOM WING] table fallback', JSON.stringify(errorData.rows));
    }
  }
  
  // Report telemetry if any
  if (_pw_telemetry_state.events.size > 0) {
    _pw_original_console.log('%cTelemetry Events', 'font-weight: bold; color: #666');
    
    const byCategory = {};
    for (const [key, entry] of _pw_telemetry_state.events) {
      if (!byCategory[entry.category]) byCategory[entry.category] = [];
      byCategory[entry.category].push(entry);
    }
    
    for (const [category, events] of Object.entries(byCategory)) {
      const totalCount = events.reduce((sum, e) => sum + e.count, 0);
      _pw_original_console.debug(`${category}: ${totalCount} event(s)`);
    }
  }

  try {
    renderDashboardMetrics();
  } catch (err) {
    _pw_original_console.log('[PHANTOM WING] Dashboard render skipped due to runtime limitations.');
  }
}

// ============================================================================
// INITIALIZATION
// ============================================================================

// Register all global handlers
_pw_patch_console();
_pw_setup_error_handler();
_pw_setup_rejection_handler();
_pw_patch_fetch();

// Generate report when page is fully loaded and settled
window.addEventListener('load', () => {
  setTimeout(() => {
    _pw_generate_final_report();
  }, 1000); // Give JS 1 second to finish all operations
});

document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'hidden') {
    void _pw_flush_telemetry_queue('hidden');
  }
});

window.addEventListener('beforeunload', () => {
  void _pw_flush_telemetry_queue('unload');
});

// ============================================================================
// DEFAULT EXPORT
// ============================================================================

export default {
  log,
  warn,
  error,
  report,
  reset,
  getState,
  formatErrorTable,
  generateErrorTableHTML,
  exportErrorData,
  injectErrorPanel,
  generateErrorSummary: _pw_generate_error_summary
};
