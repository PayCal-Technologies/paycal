import assert from 'node:assert/strict';
import { createSecurityTimers } from '../html/js/core/security-timers.js';

const run = () => {
  let nowMs = 1_000_000;
  const scheduled = [];

  const timers = createSecurityTimers({
    session_timeout_seconds: 900,
    form_ttl_settings_seconds: 300,
    form_ttl_calendar_seconds: 1800,
  }, {
    now: () => nowMs,
    scheduleTick: (fn) => {
      scheduled.push(fn);
      return scheduled.length;
    },
    clearTick: () => {},
  });

  assert.equal(timers.getRemainingSeconds('session'), 900);
  assert.equal(timers.getRemainingSeconds('account'), 300);
  assert.equal(timers.getRemainingSeconds('calendar'), 1800);

  nowMs += 120_000;
  assert.equal(timers.getRemainingSeconds('session'), 780);
  assert.equal(timers.getRemainingSeconds('account'), 180);

  timers.recordActivity();
  assert.equal(timers.getRemainingSeconds('account'), 300);

  let accountExpired = false;
  let sessionWarningOpened = false;
  timers.setOnExpire('account', () => {
    accountExpired = true;
  });
  timers.setOnExpire('sessionWarning', () => {
    sessionWarningOpened = true;
  });
  timers.start();

  nowMs += 300_000;
  scheduled.at(-1)?.();
  assert.equal(accountExpired, true);
  assert.equal(timers.getRemainingSeconds('account'), 0);
  assert.equal(timers.getRemainingSeconds('calendar'), 0);
  assert.equal(timers.isDekLocked(), true);

  timers.notifyDekUnlocked();
  assert.equal(timers.isDekLocked(), false);
  assert.equal(timers.getRemainingSeconds('calendar'), 1800);

  nowMs += 60_000;
  scheduled.at(-1)?.();
  assert.equal(sessionWarningOpened, false);

  nowMs += 780_000;
  scheduled.at(-1)?.();
  assert.equal(sessionWarningOpened, true);

  timers.stop();
};

run();
console.log('security-timers.mjs: ok');
