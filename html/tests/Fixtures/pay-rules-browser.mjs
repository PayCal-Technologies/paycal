import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
const source = fs.readFileSync(new URL('../../js/calendar/calendar.js', import.meta.url), 'utf8');
const extract = (name, async = false) => {
  const start = source.indexOf(`  ${async ? 'async ' : ''}function ${name}(`);
  assert.ok(start >= 0, `Missing runtime function ${name}`);
  const rest = source.slice(start + 2);
  const next = rest.search(/\n  (?:async )?function /);
  assert.ok(next > 0);
  return rest.slice(0, next);
};
const context = vm.createContext({
  console, window: {}, PayCalCryptoState: { hasDek: true },
  callCryptoWorker: async () => ({ regular_hours: 8, overtime_hours: 2, gross: 440 }),
  cryptoLog: () => {}, calendarDailyEarningsByDate: {},
  parseMoneyValue: value => Number(value) || 0,
  roundMoneyNumber: value => Math.round(value * 100) / 100,
  resolveOvertimeScale: () => 1.5, getSiteWageForEntry: () => 0,
  normalizeEntryCustomFields: () => ({}), normalizeEntryCustomFieldNotes: () => ({}),
  buildCustomFieldEarningsSnapshot: () => ({ custom_amount: 0, custom_deduction_amount: 0 }),
});
vm.runInContext([
  extract('buildWorkEntryEarningsSnapshot'),
  extract('computeCalendarTotals'),
  extract('decryptEntry', true),
].join('\n'), context);
const legacy = context.computeCalendarTotals([{ regular_hours: 10, overtime_hours: 0, wage: 40 }]);
assert.equal(legacy.regularTotal, 8);
const ontario = context.computeCalendarTotals([{ regular_hours: 10, overtime_hours: 0, wage: 40, earnings_snapshot_version: 3 }]);
assert.equal(ontario.regularTotal, 10);
assert.equal(ontario.overtimeTotal, 0);
assert.equal(ontario.grossTotal, 400);
const bc = context.buildWorkEntryEarningsSnapshot({ regular_hours: 8, overtime_hours: 5, doubletime_hours: 1, wage: 40 });
assert.equal(bc.gross, 640);
const nb = context.buildWorkEntryEarningsSnapshot({ regular_hours: 44, overtime_hours: 1, wage: 20, overtime_rate: 23.85 });
assert.equal(nb.gross, 903.85);
const reconciled = await context.decryptEntry({ encrypted_blob: 'fixture', earnings_snapshot_version: 3, regular_hours: 10, overtime_hours: 0, gross: 400 });
assert.equal(reconciled.regular_hours, 10);
assert.equal(reconciled.gross, 400);
const old = await context.decryptEntry({ encrypted_blob: 'fixture', earnings_snapshot_version: 2, regular_hours: 5 });
assert.equal(old.regular_hours, 8);
console.log('Browser earnings regressions passed');
