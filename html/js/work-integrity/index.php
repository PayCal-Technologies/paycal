<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * PayCal Work-Entry Client-Side Integrity Monitor
 *
 * Purpose:
 *   Runs arithmetic and range checks on work-entry objects immediately
 *   after decryption (observation) and immediately before encryption
 *   (prevention). Anomalies are routed into PhantomWing for collated
 *   in-memory reporting; they surface in the browser console at page
 *   load. Nothing is transmitted unless PW network-debug telemetry is
 *   enabled and the k-anonymity threshold is met.
 *
 * Usage (from any IIFE or module that cannot import directly):
 *   window.PayCalWorkIntegrity?.check(entry, { context: 'decrypt'|'save', date: '2026-06-06' });
 *
 * Why here:
 *   calendar.js is a classic IIFE and cannot import ES modules. Loading
 *   this file as a module from header.php exposes the public API on
 *   window.PayCalWorkIntegrity so the IIFE can call it via optional-chain
 *   without any coupling to the module loading order.
 */

require_once '../../config.php';

CORS::handleORIGIN();
Javascript::renderModuleContentType('application/javascript');

Javascript::renderDocBlock();

?>

const _pwModuleUrl = '<?php echo Render::jsModuleURL('phantomwing'); ?>';
let _pwModulePromise = null;

function _resolvePW() {
  if (!_pwModulePromise) {
    _pwModulePromise = import(_pwModuleUrl).then((module) => module.default).catch(() => null);
  }

  return _pwModulePromise;
}

function _emitIntegrity(level, msg, label, date, entry, rule) {
  void _resolvePW().then((PW) => {
    if (!PW) {
      if (level === 'error') {
        console.error(msg);
      } else {
        console.warn(msg);
      }
      return;
    }

    if (level === 'error') {
      PW.error(msg);
    } else {
      PW.warn(msg);
    }

    PW.report('integrity', `work_entry.${rule.id}`, {
      context: label,
      date,
      site_id: String(entry.site_id || ''),
    });
  });
}

// ============================================================================
// INTEGRITY RULES
// ============================================================================

/**
 * Each rule: { id, level ('warn'|'error'), test(entry) → bool, msg(entry) → string }
 *
 * Rules intentionally avoid throwing — failures must never propagate into
 * the crypto or save paths that call this module.
 */
const _wi_checks = [
  {
    id: 'hours_negative',
    level: 'error',
    test: e => ['hours', 'regular_hours', 'overtime_hours', 'travel_hours'].some(
      f => typeof e[f] === 'number' && !Number.isNaN(e[f]) && e[f] < 0
    ),
    msg: e => `Negative hours field on site "${e.site_id || '?'}"`,
  },
  {
    id: 'hours_not_finite',
    level: 'error',
    test: e => ['hours', 'regular_hours', 'overtime_hours', 'travel_hours', 'living_out_allowance'].some(
      f => e[f] != null && !Number.isFinite(Number(e[f]))
    ),
    msg: e => `Non-finite hours value on site "${e.site_id || '?'}"`,
  },
  {
    id: 'ot_exceeds_total',
    level: 'error',
    test: e => (Number(e.overtime_hours) || 0) > (Number(e.hours) || 0) + 0.01,
    msg: e => `OT hours (${e.overtime_hours}) exceed total hours (${e.hours}) on site "${e.site_id || '?'}"`,
  },
  {
    id: 'hours_arithmetic',
    level: 'warn',
    test: e => Math.abs(
      (Number(e.regular_hours) || 0) + (Number(e.overtime_hours) || 0) - (Number(e.hours) || 0)
    ) > 0.11,
    msg: e => `Hours arithmetic mismatch: reg(${e.regular_hours}) + ot(${e.overtime_hours}) ≠ total(${e.hours})`,
  },
  {
    id: 'hours_exceed_day',
    level: 'warn',
    test: e => (Number(e.hours) || 0) > 24,
    msg: e => `Total hours (${e.hours}) exceed 24h on site "${e.site_id || '?'}"`,
  },
  {
    id: 'zero_activity',
    level: 'warn',
    test: e => (Number(e.hours) || 0) === 0
            && (Number(e.living_out_allowance) || 0) === 0
            && (Number(e.travel_hours) || 0) === 0,
    msg: e => `Entry has zero hours, zero LOA, and zero travel on site "${e.site_id || '?'}"`,
  },
];

// ============================================================================
// PUBLIC API
// ============================================================================

/**
 * Check a single work-entry object for data integrity anomalies.
 *
 * @param {Object} entry - Decrypted or pre-encrypt work entry.
 * @param {Object} ctx   - { context: 'decrypt'|'save', date?: string }
 */
function check(entry, ctx = {}) {
  if (!entry || typeof entry !== 'object') return;

  const label = String(ctx.context || 'unknown');
  const date  = String(ctx.date || entry.date || '?');

  for (const rule of _wi_checks) {
    try {
      if (!rule.test(entry)) continue;

      const msg = `[WorkIntegrity:${label}] ${rule.msg(entry)} — date: ${date}`;
      _emitIntegrity(rule.level, msg, label, date, entry, rule);
    } catch {
      // Integrity checks must never throw into the caller.
    }
  }
}

window.PayCalWorkIntegrity = { check };
