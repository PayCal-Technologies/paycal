/**
 * PayCal Lens page-scoped performance factory (external, CSP-safe).
 * Boot config is supplied via inline script or data-lens-perf-boot on #business-workspace.
 */
(() => {
  'use strict';

  if (typeof window.PayCalLensPerformance !== 'undefined') {
    return;
  }

  window.PayCalLensPerformance = {
    create(scope, options) {
      const opts = options || {};
      const enabled = opts.enabled !== false;
      const prefix = '[PayCal Lens][' + scope + ']';
      const records = [];
      const active = new Map();
      const pageOriginMs = typeof opts.page_load_origin_ms === 'number' ? opts.page_load_origin_ms : null;
      const moduleStartMs = performance.now();

      const now = () => performance.now();

      const pushRecord = (label, durationMs, meta) => {
        records.push({
          label: String(label || 'unknown'),
          duration_ms: Math.max(0, durationMs),
          type: 'timer',
          ranked: !(meta && meta.ranked === false),
          meta: meta || null,
        });
      };

      const api = {
        prefix,
        isEnabled() {
          return enabled;
        },
        mark(label, meta) {
          if (!enabled) {
            return;
          }
          pushRecord(label, 0, meta);
        },
        start(label) {
          if (!enabled) {
            return;
          }
          active.set(String(label), now());
        },
        end(label, meta) {
          if (!enabled) {
            return;
          }
          const key = String(label);
          const started = active.get(key);
          if (typeof started !== 'number') {
            return;
          }
          pushRecord(key, now() - started, meta);
          active.delete(key);
        },
        async measure(label, fn, meta) {
          if (!enabled) {
            return fn();
          }
          api.start(label);
          try {
            return await fn();
          } finally {
            api.end(label, meta);
          }
        },
        measureSync(label, fn, meta) {
          if (!enabled) {
            return fn();
          }
          api.start(label);
          try {
            return fn();
          } finally {
            api.end(label, meta);
          }
        },
        markSsrPainted() {
          if (!enabled) {
            return;
          }
          pushRecord('SSR DOM painted', moduleStartMs, {
            grid_present: !!document.getElementById('businesses-members-grid'),
            ranked: false,
          });
        },
        markHydrationComplete() {
          if (!enabled) {
            return;
          }
          const hydrationMs = now() - moduleStartMs;
          pushRecord('initialize (total)', hydrationMs, {
            page_origin_ms: pageOriginMs,
          });
          pushRecord('SSR painted → JS hydration complete', hydrationMs, {
            page_origin_ms: pageOriginMs,
            ranked: false,
          });
        },
        summarize(title) {
          if (!enabled) {
            return [];
          }

          const timers = records
            .filter((record) => record.type === 'timer' && record.duration_ms > 0 && record.ranked !== false)
            .sort((a, b) => b.duration_ms - a.duration_ms);
          const top3 = timers.slice(0, 3);
          const heading = prefix + ' ' + (title || 'Performance Summary');

          console.groupCollapsed(heading);
          if (top3.length === 0) {
            console.log(prefix, 'No ranked timings recorded yet.');
          } else {
            top3.forEach((record, index) => {
              console.log('  ' + (index + 1) + '. ' + record.label + ' — ' + Math.round(record.duration_ms) + 'ms');
            });
          }
          console.log(
            'Top 3 slowest paths:',
            top3.map((record) => record.label + ' (' + Math.round(record.duration_ms) + 'ms)').join(', ') || 'n/a',
          );
          if (timers.length) {
            console.table(timers.map((record) => ({
              path: record.label,
              ms: Math.round(record.duration_ms),
            })));
          }
          console.groupEnd();

          return top3;
        },
        records() {
          return records.slice();
        },
      };

      return api;
    },
  };
})();
