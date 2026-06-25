/**
 * PayCalCore - Activity-based security timeout tracking with expiry callbacks.
 *
 * Single source of truth for session / account / calendar idle deadlines.
 * Activity resets all TTL windows; expiry handlers perform signout or DEK lock.
 */

const SESSION_WARNING_SECONDS = 60;
const CRYPTO_IDLE_TIMEOUT_DEFAULT_MS = 5 * 60 * 1000;
const CRYPTO_IDLE_TIMEOUT_MIN_MS = 60 * 1000;
const CRYPTO_IDLE_TIMEOUT_MAX_MS = 24 * 60 * 60 * 1000;

const ACTIVITY_EVENTS = ['pointerdown', 'keydown', 'touchstart', 'focus'];

const toPositiveSeconds = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
};

export function createSecurityTimers(initialConfig = {}, options = {}) {
  const now = typeof options.now === 'function' ? options.now : () => Date.now();
  const scheduleTick = typeof options.scheduleTick === 'function'
    ? options.scheduleTick
    : (fn, delayMs) => setTimeout(fn, delayMs);
  const clearTick = typeof options.clearTick === 'function'
    ? options.clearTick
    : (id) => clearTimeout(id);

  let lastActivityAt = now();
  let dekLocked = false;
  let activityBound = false;
  let sessionWarningShown = false;
  let ticking = false;
  let tickTimerId = 0;

  /** @type {Record<string, boolean>} */
  const expiredNotified = {
    session: false,
    account: false,
    calendar: false,
  };

  /** @type {Partial<Record<string, () => void>>} */
  const onExpireHandlers = {};

  let timeouts = {
    session: toPositiveSeconds(initialConfig.session_timeout_seconds),
    account: toPositiveSeconds(initialConfig.form_ttl_settings_seconds),
    calendar: toPositiveSeconds(initialConfig.form_ttl_calendar_seconds),
    general: toPositiveSeconds(initialConfig.form_ttl_general_seconds),
  };

  const dispatch = (name, detail = {}) => {
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent(name, { detail }));
    }
  };

  const resetExpiredFlags = () => {
    expiredNotified.session = false;
    expiredNotified.account = false;
    expiredNotified.calendar = false;
  };

  const recordActivity = () => {
    lastActivityAt = now();
    sessionWarningShown = false;
    resetExpiredFlags();
    dekLocked = false;
    dispatch('paycal:security-activity', { at: lastActivityAt });
  };

  const getRemainingSeconds = (kind) => {
    if (dekLocked && (kind === 'account' || kind === 'calendar')) {
      return 0;
    }

    const total = timeouts[kind] || 0;
    if (total <= 0) {
      return 0;
    }

    const elapsed = Math.floor((now() - lastActivityAt) / 1000);
    return Math.max(0, total - elapsed);
  };

  const getDekIdleTimeoutMs = () => {
    const accountSec = timeouts.account || 0;
    const calendarSec = timeouts.calendar || 0;

    let dekSec = 0;
    if (accountSec > 0 && calendarSec > 0) {
      dekSec = Math.min(accountSec, calendarSec);
    } else if (accountSec > 0) {
      dekSec = accountSec;
    } else if (calendarSec > 0) {
      dekSec = calendarSec;
    } else {
      dekSec = Math.floor(CRYPTO_IDLE_TIMEOUT_DEFAULT_MS / 1000);
    }

    return Math.max(
      CRYPTO_IDLE_TIMEOUT_MIN_MS,
      Math.min(CRYPTO_IDLE_TIMEOUT_MAX_MS, dekSec * 1000)
    );
  };

  const updateTimeouts = (next = {}) => {
    timeouts = {
      session: toPositiveSeconds(next.session_timeout_seconds ?? timeouts.session),
      account: toPositiveSeconds(next.form_ttl_settings_seconds ?? timeouts.account),
      calendar: toPositiveSeconds(next.form_ttl_calendar_seconds ?? timeouts.calendar),
      general: toPositiveSeconds(next.form_ttl_general_seconds ?? timeouts.general),
    };
    recordActivity();
  };

  const notifyDekUnlocked = () => {
    dekLocked = false;
    recordActivity();
    dispatch('paycal:crypto-dek-unlocked');
  };

  const notifyDekZeroized = (reason = 'unknown') => {
    dekLocked = true;
    expiredNotified.account = true;
    expiredNotified.calendar = true;
    dispatch('paycal:crypto-dek-zeroized', {
      reason: String(reason || 'unknown'),
    });
    dispatch('paycal:security-timers-tick');
  };

  const fireExpiry = (kind) => {
    if (expiredNotified[kind]) {
      return;
    }
    expiredNotified[kind] = true;

    if (kind === 'account' || kind === 'calendar') {
      dekLocked = true;
    }

    dispatch('paycal:security-timers-tick');

    const handler = onExpireHandlers[kind];
    if (typeof handler === 'function') {
      handler();
    }
  };

  const evaluateDeadlines = () => {
    const sessionRemaining = getRemainingSeconds('session');
    if (
      !sessionWarningShown
      && timeouts.session > 0
      && sessionRemaining > 0
      && sessionRemaining <= SESSION_WARNING_SECONDS
      && typeof onExpireHandlers.sessionWarning === 'function'
    ) {
      sessionWarningShown = true;
      onExpireHandlers.sessionWarning();
    }

    ['session', 'account', 'calendar'].forEach((kind) => {
      const total = timeouts[kind] || 0;
      if (total <= 0 || expiredNotified[kind]) {
        return;
      }
      if (getRemainingSeconds(kind) <= 0) {
        fireExpiry(kind);
      }
    });
  };

  const scheduleNextTick = () => {
    if (!ticking) {
      return;
    }
    tickTimerId = scheduleTick(() => {
      evaluateDeadlines();
      dispatch('paycal:security-timers-tick');
      scheduleNextTick();
    }, 1000);
  };

  const start = () => {
    if (ticking) {
      return;
    }
    ticking = true;
    evaluateDeadlines();
    scheduleNextTick();
  };

  const stop = () => {
    ticking = false;
    if (tickTimerId) {
      clearTick(tickTimerId);
      tickTimerId = 0;
    }
  };

  const bindActivity = () => {
    if (activityBound || typeof document === 'undefined') {
      return;
    }

    ACTIVITY_EVENTS.forEach((eventName) => {
      document.addEventListener(eventName, recordActivity, { passive: true });
    });

    let mousemoveScheduled = false;
    document.addEventListener('mousemove', () => {
      if (mousemoveScheduled) {
        return;
      }
      mousemoveScheduled = true;
      window.requestAnimationFrame(() => {
        mousemoveScheduled = false;
        recordActivity();
      });
    }, { passive: true });

    activityBound = true;
  };

  const setOnExpire = (kind, handler) => {
    onExpireHandlers[kind] = typeof handler === 'function' ? handler : undefined;
  };

  return {
    SESSION_WARNING_SECONDS,
    recordActivity,
    getRemainingSeconds,
    getDekIdleTimeoutMs,
    updateTimeouts,
    notifyDekUnlocked,
    notifyDekZeroized,
    bindActivity,
    isDekLocked: () => dekLocked,
    setOnExpire,
    start,
    stop,
    clearSessionWarningShown: () => {
      sessionWarningShown = false;
    },
  };
}

export default createSecurityTimers;
