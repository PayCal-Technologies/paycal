import { createSecurityTimers } from '../../../js/core/security-timers.js';

describe('createSecurityTimers', () => {
  test('uses the shorter account/calendar TTL for DEK idle timeout', () => {
    const timers = createSecurityTimers({
      form_ttl_settings_seconds: 300,
      form_ttl_calendar_seconds: 1800,
    });

    expect(timers.getDekIdleTimeoutMs()).toBe(300 * 1000);
  });

  test('activity resets remaining windows', () => {
    const timers = createSecurityTimers({
      session_timeout_seconds: 900,
      form_ttl_settings_seconds: 300,
      form_ttl_calendar_seconds: 1800,
    });

    const startedAt = Date.now();
    jest.spyOn(Date, 'now').mockReturnValue(startedAt);
    expect(timers.getRemainingSeconds('account')).toBe(300);

    Date.now.mockReturnValue(startedAt + 120 * 1000);
    expect(timers.getRemainingSeconds('account')).toBe(180);

    timers.recordActivity();
    Date.now.mockReturnValue(startedAt + 120 * 1000);
    expect(timers.getRemainingSeconds('account')).toBe(300);

    Date.now.mockRestore();
  });

  test('dek zeroize expires account and calendar windows immediately', () => {
    const timers = createSecurityTimers({
      form_ttl_settings_seconds: 300,
      form_ttl_calendar_seconds: 1800,
    });

    expect(timers.getRemainingSeconds('calendar')).toBe(1800);
    timers.notifyDekZeroized('idle_timeout');
    expect(timers.getRemainingSeconds('account')).toBe(0);
    expect(timers.getRemainingSeconds('calendar')).toBe(0);
    expect(timers.isDekLocked()).toBe(true);

    timers.notifyDekUnlocked();
    expect(timers.isDekLocked()).toBe(false);
    expect(timers.getRemainingSeconds('calendar')).toBe(1800);
  });
});
