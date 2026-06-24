/**
 * PayCal - Calendar Grid UI
 * 
 * Monolithic client script for grid-based calendar.
 * Handles row interactions, dialogs, keyboard navigation, and event communication.
 * 
 * Date: March 3, 2026
 */

(function() {
  'use strict';

  const latin1FromBase64 = (value) => (
    window.PayCalBinaryCodec?.latin1FromBase64?.(value) ?? atob(String(value ?? ''))
  );
  const uniqueTruthy = (values) => (
    window.PayCalSetUtils?.uniqueTruthy?.(values)
      ?? values.filter((value, index, arr) => value && arr.indexOf(value) === index)
  );
  const calendarI18nHelper = window.PayCalCalendarI18n;

  // Immediate check that script loaded
  window.CALENDAR_SCRIPT_LOADED = true;
  
  // Initialize focused day variable (stores day when cell clicked, used for nav)
  window._CALENDAR_FOCUSED_DAY = null;
  window._CALENDAR_LAST_GRID_FOCUS_DATE = null;
  let modalAutofocusToken = 0;
  let activeContextMenuDateId = '';
  let activeContextMenuAnchorCell = null;
  let activeContextMenuOpenMode = 'keyboard';
  let gridKeyboardNavigationBound = false;
  let dayContextMenuMenuBound = false;
  let dayContextMenuKeyboardCaptureBound = false;
  let dayContextMenuDocumentBound = false;
  let calendarLockedForVerification = false;
  let calendarScreenMode = 'normal';
  let calendarShiftKeyHeld = false;
  let calendarShiftAnchorDateId = '';
  const calendarSiteWageMatrix = Object.create(null);
  const calendarSiteWageById = new Map();
  let calendarSiteCatalogPromise = null;
  const calendarDailyEarningsByDate = Object.create(null);
  const calendarDailyEarningsLoadedYears = new Set();
  const calendarDailyEarningsLoadingYears = new Set();
  let calendarHoverTooltipEl = null;
  let calendarHoverTooltipCell = null;
  // In-memory clipboard: never touches any storage API; cleared by zeroize and on page unload.
  let calendarClipboard = null;

  const calendarDebugChannels = (() => {
    const defaults = {
      core: false,
      crypto: false,
      modal: false,
      focus: false,
    };

    try {
      // Local-only explicit opt-in:
      // 1) window.PAYCAL_CAL_DEBUG = 'all' | 'core,crypto'
      // 2) localStorage.setItem('paycal_cal_debug', 'all' | 'core,crypto')
      const raw = (window.PAYCAL_CAL_DEBUG || localStorage.getItem('paycal_cal_debug') || '').toString().trim();
      if (!raw) {
        return defaults;
      }

      if (raw === '1' || raw === 'true' || raw === 'all') {
        return { core: true, crypto: true, modal: true, focus: true };
      }

      const channels = raw.split(',').map((item) => item.trim().toLowerCase()).filter(Boolean);
      for (const channel of channels) {
        if (Object.prototype.hasOwnProperty.call(defaults, channel)) {
          defaults[channel] = true;
        }
      }

      return defaults;
    } catch {
      return defaults;
    }
  })();

  /**
   * Channel-based debug logger.
   * Keeps verbose logs off in normal use unless a debug channel is explicitly enabled.
   */
  function calendarDebugLog(channel, ...args) {
    if (calendarDebugChannels[channel]) {
      console.log(...args);
    }
  }

  function coreLog(...args) {
    calendarDebugLog('core', ...args);
  }

  function cryptoLog(...args) {
    calendarDebugLog('crypto', ...args);
  }

  /**
   * Standard crypto error formatter so related failures read consistently in logs.
   */
  function cryptoError(prefix, error) {
    console.error(prefix, error?.message || String(error));
  }

  function modalLog(...args) {
    calendarDebugLog('modal', ...args);
  }

  function focusLog(...args) {
    calendarDebugLog('focus', ...args);
  }

  function calendarConsoleDebug(label, payload) {
    try {
      console.log(`[Calendar Debug] ${label}`, payload || {});
    } catch {
      console.log(`[Calendar Debug] ${label}`);
    }
  }

  if (!calendarI18nHelper || typeof calendarI18nHelper.get !== 'function') {
    throw new Error('PayCalCalendarI18n helper is required before calendar.js');
  }

  function calendarI18n(key, fallback = '') {
    return calendarI18nHelper.get(key, fallback);
  }

  function calendarUserLocale() {
    return typeof calendarI18nHelper.userLocale === 'function'
      ? calendarI18nHelper.userLocale()
      : undefined;
  }

  // Payroll column headings stay English in every UI locale.
  const WORK_ENTRY_COLUMN_HEADINGS = Object.freeze({
    site: 'Site',
    regular: 'Regular',
    overtime: 'Overtime',
    livingOutAllowance: 'Living Out Allowance',
    travel: 'Travel',
  });

  function calendarWorkDetailsLabel() {
    return calendarI18n('I_WORK_DETAILS', 'work details');
  }

  function formatCalendarLocaleDate(date, options) {
    return typeof calendarI18nHelper.formatDate === 'function'
      ? calendarI18nHelper.formatDate(date, options)
      : date.toLocaleDateString(calendarUserLocale(), options);
  }

  function calendarI18nFormat(key, fallback, params = {}) {
    return typeof calendarI18nHelper.format === 'function'
      ? calendarI18nHelper.format(key, fallback, params)
      : calendarI18n(key, fallback);
  }

  function buildEncryptedWorkUnlockMismatchMessage(dateId = '') {
    const fallback = dateId
      ? 'This passkey cannot decrypt existing PayCal work entries for {dateLabel}. Sign out and sign back in with a passkey that previously unlocked your calendar, then try again.'
      : 'This passkey cannot decrypt existing PayCal work entries. Sign out and sign back in with a passkey that previously unlocked your calendar, then try again.';
    const key = dateId
      ? 'CALENDAR_DECRYPT_FAILED_FOR_DATE'
      : 'CALENDAR_DECRYPT_FAILED_UNLOCK_MISMATCH';

    return calendarI18nFormat(key, fallback, {
      dateLabel: dateId ? formatStatusDateLabel(dateId) : '',
    });
  }

  function buildEncryptedWorkUnlockError(dateId = '', cause = null) {
    const error = new Error(buildEncryptedWorkUnlockMismatchMessage(dateId));
    error.code = 'paycal_decrypt_unlock_mismatch';
    if (cause) {
      error.cause = cause;
    }
    return error;
  }

  function isDelegatedCalendarViewActive() {
    const root = document.getElementById('calendar-v2-root');
    if (!root) {
      return false;
    }

    const actorUUID = (root.dataset.calendarActorUuid || '').trim();
    const selectedUUID = (root.dataset.calendarUserUuid || '').trim();
    return actorUUID !== '' && selectedUUID !== '' && actorUUID !== selectedUUID;
  }

  function applyResolvedPlatformToken() {
    const platform = window.PayCalCalendarPlatform;
    const token = platform && typeof platform.resolvePlatformToken === 'function'
      ? platform.resolvePlatformToken()
      : String(document.documentElement.dataset.os || 'win').trim().toLowerCase();
    document.documentElement.dataset.os = token;

    try {
      localStorage.setItem('platformResolved', token);
    } catch {
      // Ignore storage failures.
    }

    return token;
  }

  coreLog('✓ CALENDAR SCRIPT LOADED - IIFE executing');

  const Guardian = window.Guardian;
  if (!Guardian || typeof Guardian.setHTML !== 'function' || typeof Guardian.insertHTML !== 'function') {
    throw new Error('Guardian module is required before calendar.js');
  }

  const PayCalCryptoState = {
    hasDek: false,
    dekVersion: 1,
    cryptoVersion: 1,
    credentialId: null,
    userId: null,
    profileEncrypted: false,
  };

  let payCalCryptoWorker = null;
  let payCalCryptoWorkerReqId = 0;
  const payCalCryptoWorkerPending = new Map();
  const CRYPTO_WORKER_REQUEST_TIMEOUT_MS = 12000;
  let cryptoLifecycleZeroizeBound = false;
  let cryptoIdleTimer = null;
  let cryptoHiddenZeroizeTimer = null;
  let payCalDekEnsurePromise = null;
  const CRYPTO_IDLE_TIMEOUT_DEFAULT_MS = 5 * 60 * 1000;
  const CRYPTO_IDLE_TIMEOUT_MIN_MS = 60 * 1000;
  const CRYPTO_IDLE_TIMEOUT_MAX_MS = 30 * 60 * 1000;
  const CRYPTO_HIDDEN_ZEROIZE_DELAY_MS = 15 * 1000;
  function calendarWebAuthnUnsupportedMessage() {
    return calendarI18n(
      'CALENDAR_WEB_AUTHN_UNSUPPORTED',
      'This browser cannot use passkeys, so encrypted entries cannot be unlocked here. Use a WebAuthn-capable browser on a secure connection (HTTPS).'
    );
  }

  function isWebAuthnCapableBrowser() {
    const hasPublicKeyCredential = typeof window.PublicKeyCredential !== 'undefined';
    const hasCredentialsApi = typeof navigator.credentials !== 'undefined' && navigator.credentials !== null;
    const hasGet = hasCredentialsApi && typeof navigator.credentials.get === 'function';
    const hasCreate = hasCredentialsApi && typeof navigator.credentials.create === 'function';
    return window.isSecureContext && hasPublicKeyCredential && hasCredentialsApi && hasGet && hasCreate;
  }

  function resolveCryptoIdleTimeoutMs() {
    const sessionTimeoutSeconds = Number(window?.PayCalCore?.config?.session_timeout_seconds || 0);
    if (sessionTimeoutSeconds > 0) {
      const boundedFromSession = Math.max(
        CRYPTO_IDLE_TIMEOUT_MIN_MS,
        Math.min(CRYPTO_IDLE_TIMEOUT_MAX_MS, sessionTimeoutSeconds * 1000)
      );
      return boundedFromSession;
    }

    return CRYPTO_IDLE_TIMEOUT_DEFAULT_MS;
  }

  function clearCryptoIdleTimer() {
    if (cryptoIdleTimer !== null) {
      clearTimeout(cryptoIdleTimer);
      cryptoIdleTimer = null;
    }
  }

  function clearCryptoHiddenZeroizeTimer() {
    if (cryptoHiddenZeroizeTimer !== null) {
      clearTimeout(cryptoHiddenZeroizeTimer);
      cryptoHiddenZeroizeTimer = null;
    }
  }

  function scheduleHiddenTabZeroize() {
    clearCryptoHiddenZeroizeTimer();

    if (!PayCalCryptoState.hasDek) {
      return;
    }

    cryptoHiddenZeroizeTimer = setTimeout(() => {
      if (document.visibilityState === 'hidden') {
        void zeroizeCryptoState('visibility_hidden_delayed', { strict: true }).catch((err) => {
          cryptoLog('[CRYPTO] Strict hidden-tab zeroize failed', {
            reason: 'visibility_hidden_delayed',
            error: err?.message || String(err),
          });
        });
      }
      cryptoHiddenZeroizeTimer = null;
    }, CRYPTO_HIDDEN_ZEROIZE_DELAY_MS);
  }

  function armCryptoIdleTimer() {
    clearCryptoIdleTimer();

    if (!PayCalCryptoState.hasDek) {
      return;
    }

    const timeoutMs = resolveCryptoIdleTimeoutMs();
    cryptoIdleTimer = setTimeout(() => {
      void zeroizeCryptoState('idle_timeout', { strict: true }).catch((err) => {
        cryptoLog('[CRYPTO] Strict idle-timeout zeroize failed', {
          reason: 'idle_timeout',
          error: err?.message || String(err),
        });
      });
    }, timeoutMs);
  }

  function resetMainThreadCryptoState() {
    PayCalCryptoState.hasDek = false;
    PayCalCryptoState.dekVersion = 1;
    PayCalCryptoState.cryptoVersion = 1;
    PayCalCryptoState.credentialId = null;
    PayCalCryptoState.userId = null;
    PayCalCryptoState.profileEncrypted = false;
    delete window.PAYCAL_USER_PROFILE_ENCRYPTED;
    clearCryptoIdleTimer();
    clearCryptoHiddenZeroizeTimer();
  }

  function scrubSensitiveCalendarDom(reason) {
    try {
      const grid = document.getElementById('calendar-grid');
      if (grid) {
        const cells = grid.querySelectorAll('.datagrid_month_cell[data-work-entries]');
        cells.forEach((cell) => {
          cell.setAttribute('data-work-entries', '[]');
          updateCalendarDayTooltip(cell, []);
          const content = cell.querySelector('.datagrid_month_cell_content');
          if (content) {
            Guardian.setHTML(content, '');
          }
        });
      }

      const modal = document.getElementById('calendar-modal');
      if (modal) {
        modal.setAttribute('data-active-date', '');
        modal.setAttribute('data-last-grid-focus-date', '');

        const modalContent = modal.querySelector('#calendar-modal-content');
        if (modalContent) {
          Guardian.setHTML(modalContent, '');
        }

        if (modal.open) {
          if (window.PayCalCore && typeof window.PayCalCore.closeModal === 'function') {
            window.PayCalCore.closeModal('calendar-modal', calendarWorkDetailsLabel());
          } else {
            modal.close();
          }
        }
      }

      const menu = document.getElementById('calendar_day_context_menu');
      if (menu) {
        menu.classList.remove('is-open');
        menu.hidden = true;
      }

      calendarClipboard = null;
    } catch (err) {
      cryptoLog('[CRYPTO] DOM scrub warning', {
        reason,
        error: err?.message || String(err),
      });
    }
  }

  async function zeroizeCryptoState(reason, options = {}) {
    const strict = options && options.strict === true;
    let workerClearError = null;

    try {
      if (payCalCryptoWorker) {
        await callCryptoWorker('clear');
      }
    } catch (err) {
      workerClearError = err;
      cryptoLog('[CRYPTO] Zeroize worker-clear failed', {
        reason,
        error: err?.message || String(err),
      });
    }

    scrubSensitiveCalendarDom(reason);
    resetMainThreadCryptoState();

    if (strict && workerClearError) {
      throw workerClearError;
    }

    cryptoLog('[CRYPTO] Zeroized in-memory key state', { reason });
  }

  function bindCryptoLifecycleZeroization() {
    if (cryptoLifecycleZeroizeBound) {
      return;
    }

    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') {
        scheduleHiddenTabZeroize();
      } else if (document.visibilityState === 'visible') {
        clearCryptoHiddenZeroizeTimer();
        armCryptoIdleTimer();
      }
    });

    window.addEventListener('pagehide', () => {
      void zeroizeCryptoState('pagehide', { strict: true }).catch((err) => {
        cryptoLog('[CRYPTO] Strict pagehide zeroize failed', {
          reason: 'pagehide',
          error: err?.message || String(err),
        });
      });
    });

    const activityEvents = ['pointerdown', 'keydown', 'touchstart', 'focus'];
    activityEvents.forEach((eventName) => {
      document.addEventListener(eventName, () => {
        armCryptoIdleTimer();
      }, { passive: true });
    });

    cryptoLifecycleZeroizeBound = true;
  }

  function safeFingerprint(value) {
    if (!value || typeof value !== 'string') {
      return '';
    }

    let hash = 5381;
    for (let i = 0; i < value.length; i += 1) {
      hash = ((hash << 5) + hash) + value.charCodeAt(i);
      hash |= 0;
    }

    return `fp_${Math.abs(hash).toString(16)}`;
  }

  function envelopeMeta(base64Envelope) {
    if (!base64Envelope || typeof base64Envelope !== 'string') {
      return { present: false };
    }

    try {
      const parsed = JSON.parse(latin1FromBase64(base64Envelope));
      const nonce = parsed.nonce || parsed.iv || '';
      const ciphertext = parsed.ciphertext || parsed.ct || '';
      const aadSiteId = (parsed?.aad?.site_id ?? parsed?.site_id ?? parsed?.s ?? '').toString().trim();
      const aadSiteName = (parsed?.aad?.site_name ?? parsed?.site_name ?? parsed?.n ?? '').toString().trim();
      const aadBinding = [aadSiteId, aadSiteName].filter((part) => part !== '').join('|');

      return {
        present: true,
        envelopeVersion: parsed.version || parsed.v || null,
        nonceLen: typeof nonce === 'string' ? nonce.length : 0,
        ciphertextLen: typeof ciphertext === 'string' ? ciphertext.length : 0,
        siteBindingPresent: aadBinding !== '',
        siteBindingFingerprint: aadBinding !== '' ? safeFingerprint(aadBinding) : '',
        envelopeFingerprint: safeFingerprint(base64Envelope),
        rawLen: base64Envelope.length,
      };
    } catch (err) {
      return {
        present: true,
        parseError: err?.message || String(err),
        rawLen: base64Envelope.length,
      };
    }
  }

  function isCalendarEmailVerified() {
    const root = document.getElementById('calendar-v2-root');
    if (!root) {
      return true;
    }

    return String(root.dataset.emailVerified || '1') === '1';
  }

  function lockCalendarUntilEmailVerified() {
    const root = document.getElementById('calendar-v2-root');
    const grid = document.getElementById('calendar-grid');
    calendarConsoleDebug('lockCalendarUntilEmailVerified called', {
      hasRoot: !!root,
      hasGrid: !!grid,
    });
    if (!root || !grid) {
      return;
    }

    calendarLockedForVerification = true;
    root.classList.add('calendar_verification_locked');

    if (!root.querySelector('.calendar_verification_lock_message')) {
      const lock = document.createElement('div');
      lock.className = 'calendar_verification_lock_message';
      lock.textContent = calendarI18n(
        'CALENDAR_LOCKED_UNVERIFIED',
        'Calendar is locked until your email is verified. Use the banner above to verify your account.'
      );
      root.appendChild(lock);
    }

    calendarConsoleDebug('calendar locked for email verification', {
      rootClassList: root.className,
      locked: calendarLockedForVerification,
    });
  }

  function resolveAssetVersionFromScript() {
    const scripts = document.querySelectorAll('script[src*="/js/calendar/calendar.js"]');
    for (const script of scripts) {
      try {
        const src = script.getAttribute('src') || '';
        if (!src) {
          continue;
        }

        const url = new URL(src, window.location.origin);
        const version = url.searchParams.get('v');
        if (version) {
          return version;
        }
      } catch {
        // Ignore malformed script URLs and continue scanning.
      }
    }

    return '';
  }

  function ensureCryptoWorker() {
    if (payCalCryptoWorker) {
      return payCalCryptoWorker;
    }

    const workerVersion = resolveAssetVersionFromScript() || `dev-${Date.now()}`;
    const workerUrl = `/js/calendar/crypto-worker.js?v=${encodeURIComponent(workerVersion)}`;
    let workerScriptUrl = workerUrl;

    if (typeof window !== 'undefined' && window.trustedTypes) {
      try {
        const paycalPolicy = typeof window.trustedTypes.getPolicy === 'function'
          ? window.trustedTypes.getPolicy('paycal')
          : null;
        const defaultPolicy = typeof window.trustedTypes.getPolicy === 'function'
          ? window.trustedTypes.getPolicy('default')
          : null;
        const scriptUrlFactory = paycalPolicy?.createScriptURL || defaultPolicy?.createScriptURL;

        if (typeof scriptUrlFactory === 'function') {
          workerScriptUrl = scriptUrlFactory(workerUrl);
        }
      } catch (trustedTypeErr) {
        cryptoLog('[CRYPTO] Trusted Types worker URL conversion failed, using raw URL', {
          error: trustedTypeErr?.message || String(trustedTypeErr),
        });
      }
    }

    cryptoLog('[CRYPTO] Creating worker', { workerUrl, workerVersion });
    payCalCryptoWorker = new Worker(workerScriptUrl, { type: 'module' });
    payCalCryptoWorker.onmessage = (event) => {
      const payload = event.data || {};
      const pending = payCalCryptoWorkerPending.get(payload.id);
      if (!pending) {
        return;
      }

      payCalCryptoWorkerPending.delete(payload.id);
      if (pending.timeoutHandle) {
        clearTimeout(pending.timeoutHandle);
      }
      if (payload.ok) {
        pending.resolve(payload.result);
      } else {
        const errorMsg = payload.error || 'Crypto worker failure';
        const details = payload.details ? `\n${payload.details}` : '';
        const diag = payload.diagnostics ? `\nDiagnostics: ${JSON.stringify(payload.diagnostics)}` : '';
        pending.reject(new Error(errorMsg + details + diag));
      }
    };

    return payCalCryptoWorker;
  }

  function callCryptoWorker(action, payload = {}) {
    const worker = ensureCryptoWorker();
    const requestId = ++payCalCryptoWorkerReqId;

    return new Promise((resolve, reject) => {
      const timeoutHandle = setTimeout(() => {
        if (!payCalCryptoWorkerPending.has(requestId)) {
          return;
        }

        payCalCryptoWorkerPending.delete(requestId);
        reject(new Error(`[CRYPTO] Worker request timed out for action: ${action}`));
      }, CRYPTO_WORKER_REQUEST_TIMEOUT_MS);

      payCalCryptoWorkerPending.set(requestId, { resolve, reject, timeoutHandle });
      worker.postMessage({ id: requestId, action, payload });
    });
  }

  /**
   * Ensure the in-memory Data Encryption Key (DEK) is available before decrypting entries.
   *
   * Plain-language behavior:
   * 1) Load bootstrap data from server.
   * 2) Try passkey-based unwrap first (non-interactive when possible).
  * 3) Keep unlock flow passkey-only.
   * 4) Only generate a brand-new DEK for first-time setup with no existing wrappers.
   */
  async function ensurePayCalDEK(options = {}) {
    const interactive = options.interactive !== false;
    cryptoLog('[CRYPTO] ensurePayCalDEK called:', {
      hasDek: PayCalCryptoState.hasDek,
      hasCredentialId: !!PayCalCryptoState.credentialId,
      interactive,
      hasPendingEnsure: !!payCalDekEnsurePromise,
    });

    if (PayCalCryptoState.hasDek) {
      return true;
    }

    if (!isWebAuthnCapableBrowser()) {
      cryptoLog('[CRYPTO] Passkey unlock unavailable: browser lacks WebAuthn capability', {
        isSecureContext: !!window.isSecureContext,
        hasPublicKeyCredential: typeof window.PublicKeyCredential !== 'undefined',
        hasCredentialsApi: typeof navigator.credentials !== 'undefined' && navigator.credentials !== null,
      });
      return false;
    }

    if (payCalDekEnsurePromise) {
      return payCalDekEnsurePromise;
    }

    payCalDekEnsurePromise = (async () => {
      let bootstrapData = null;
      try {
        const response = await fetch('/api/v1/user/account/bootstrap', {
          method: 'GET',
          credentials: 'same-origin',
        });
        cryptoLog('[CRYPTO] Bootstrap fetch response status:', response.status);
        if (!response.ok) {
          cryptoLog('[CRYPTO] Bootstrap response not OK');
          return false;
        }

        const payload = await response.json();
        bootstrapData = (payload && typeof payload === 'object')
          ? (payload.data && typeof payload.data === 'object' ? payload.data : payload)
          : {};
        if (payload && payload._lens) {
          cryptoLog('[CRYPTO] Bootstrap Lens payload:', payload._lens);
        }
        PayCalCryptoState.userId = bootstrapData.userId || null;
        PayCalCryptoState.dekVersion = Number(bootstrapData.dekVersion || 1);
        PayCalCryptoState.cryptoVersion = Number(bootstrapData.cryptoVersion || 1);
        bootstrapData.wrappedCredentialCount = Number(bootstrapData.wrappedCredentialCount || 0);
        
        // Deterministic credential selection priority:
        // If backend returned a passkey wrapper, bootstrapData.credentialId is the
        // credential that owns that wrapper and must be used first.
        const credentialCandidates = bootstrapData.wrappedDekPasskey
          ? [
            bootstrapData.credentialId,
            bootstrapData.sessionCredentialId,
            PayCalCryptoState.credentialId,
          ]
          : [
            PayCalCryptoState.credentialId,
            bootstrapData.sessionCredentialId,
            bootstrapData.credentialId,
          ];

        const dedupedCandidates = uniqueTruthy(credentialCandidates);
        PayCalCryptoState.credentialId = dedupedCandidates[0] || null;

        if (PayCalCryptoState.credentialId) {
          cryptoLog('[CRYPTO] Credential ID selected for DEK unwrap', {
            selectedFp: safeFingerprint(PayCalCryptoState.credentialId),
            selectedLength: String(PayCalCryptoState.credentialId).length,
            sessionCredentialFp: safeFingerprint(bootstrapData.sessionCredentialId || ''),
            bootstrapCredentialFp: safeFingerprint(bootstrapData.credentialId || ''),
            candidateCount: dedupedCandidates.length,
          });
        }

        cryptoLog('[CRYPTO] Bootstrap unwrap context', {
          userIdPresent: !!PayCalCryptoState.userId,
          userIdFp: safeFingerprint(PayCalCryptoState.userId || ''),
          hasWrappedDekPasskey: !!bootstrapData.wrappedDekPasskey,
          wrappedCredentialCount: bootstrapData.wrappedCredentialCount,
          wrappedDekPasskeyMeta: envelopeMeta(bootstrapData.wrappedDekPasskey || ''),
          hasEncryptionSalt: !!bootstrapData.encryptionSalt,
          encryptionSaltLen: (bootstrapData.encryptionSalt || '').length,
        });
      } catch (err) {
        console.error('[CRYPTO] Bootstrap fetch failed:', err);
        return false;
      }

      if (!bootstrapData.encryptionSalt) {
        cryptoLog('[CRYPTO] No encryption salt in bootstrap response');
        return false;
      }

      const persistPasskeyWrap = async (wrappedEnvelope, credentialIdOverride = null) => {
        const credentialIdToStore = credentialIdOverride || PayCalCryptoState.credentialId;
        const response = await fetch('/api/v1/user/crypto/passkey-wrap', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({
            credentialId: credentialIdToStore,
            wrappedDekPasskey: wrappedEnvelope.wrappedDekPasskey,
            dekVersion: wrappedEnvelope.dekVersion,
            cryptoVersion: wrappedEnvelope.cryptoVersion,
          }),
        });

        if (!response.ok) {
          let responseBody = '';
          try {
            responseBody = await response.text();
          } catch {
            responseBody = '';
          }

          throw new Error(`Failed to persist credential-specific wrapped DEK (${response.status}): ${responseBody || 'no response body'}`);
        }
      };

      const tryPasskeyUnwrapFromEnvelope = async (wrappedEnvelopeBase64, sourceLabel) => {
      if (!wrappedEnvelopeBase64 || !PayCalCryptoState.credentialId) {
        return false;
      }

      try {
        await callCryptoWorker('unwrapWithPasskeyCredential', {
          wrappedDekPasskey: wrappedEnvelopeBase64,
          credentialId: PayCalCryptoState.credentialId,
          userId: PayCalCryptoState.userId,
          saltBase64: bootstrapData.encryptionSalt,
          dekVersion: PayCalCryptoState.dekVersion,
          cryptoVersion: PayCalCryptoState.cryptoVersion,
          derivationMode: 'credential-only',
        });

        PayCalCryptoState.hasDek = true;
        cryptoLog('[CRYPTO] Passkey unwrap succeeded from envelope source', { source: sourceLabel });

        // Normalize storage so future logins use credential-specific wrapper directly.
        try {
          const wrappedCanonical = await callCryptoWorker('wrapCurrentDekWithPasskeyCredential', {
            credentialId: PayCalCryptoState.credentialId,
            userId: PayCalCryptoState.userId,
            saltBase64: bootstrapData.encryptionSalt,
            derivationMode: 'credential-only',
          });
          await persistPasskeyWrap(wrappedCanonical, PayCalCryptoState.credentialId);
        } catch (persistErr) {
          cryptoLog('[CRYPTO] Unable to persist normalized passkey wrapper after compatibility unwrap', {
            source: sourceLabel,
            error: persistErr?.message || String(persistErr),
          });
        }

        return true;
      } catch (canonicalErr) {
        try {
          await callCryptoWorker('unwrapWithPasskeyCredential', {
            wrappedDekPasskey: wrappedEnvelopeBase64,
            credentialId: PayCalCryptoState.credentialId,
            userId: PayCalCryptoState.userId,
            saltBase64: bootstrapData.encryptionSalt,
            dekVersion: PayCalCryptoState.dekVersion,
            cryptoVersion: PayCalCryptoState.cryptoVersion,
            derivationMode: 'credential-user',
          });

          PayCalCryptoState.hasDek = true;
          cryptoLog('[CRYPTO] Legacy passkey unwrap succeeded from envelope source', { source: sourceLabel });

          try {
            const wrappedCanonical = await callCryptoWorker('wrapCurrentDekWithPasskeyCredential', {
              credentialId: PayCalCryptoState.credentialId,
              userId: PayCalCryptoState.userId,
              saltBase64: bootstrapData.encryptionSalt,
              derivationMode: 'credential-only',
            });
            await persistPasskeyWrap(wrappedCanonical, PayCalCryptoState.credentialId);
          } catch (persistErr) {
            cryptoLog('[CRYPTO] Unable to persist canonical wrapper after legacy compatibility unwrap', {
              source: sourceLabel,
              error: persistErr?.message || String(persistErr),
            });
          }

          return true;
        } catch (legacyErr) {
          cryptoLog('[CRYPTO] Compatibility passkey unwrap failed', {
            source: sourceLabel,
            canonicalError: canonicalErr?.message || String(canonicalErr),
            legacyError: legacyErr?.message || String(legacyErr),
          });
          return false;
        }
      }
    };

      if (!PayCalCryptoState.userId) {
        cryptoLog('[CRYPTO] Unlock unavailable: missing user context');
        return false;
      }

      if (PayCalCryptoState.credentialId && bootstrapData.wrappedDekPasskey) {
      try {
        cryptoLog('[CRYPTO] Attempting canonical unwrap', {
          derivationMode: 'credential-only',
          credentialFp: safeFingerprint(PayCalCryptoState.credentialId || ''),
          userFp: safeFingerprint(PayCalCryptoState.userId || ''),
        });
        await callCryptoWorker('unwrapWithPasskeyCredential', {
          wrappedDekPasskey: bootstrapData.wrappedDekPasskey,
          credentialId: PayCalCryptoState.credentialId,
          userId: PayCalCryptoState.userId,
          saltBase64: bootstrapData.encryptionSalt,
          dekVersion: PayCalCryptoState.dekVersion,
          cryptoVersion: PayCalCryptoState.cryptoVersion,
          derivationMode: 'credential-only',
        });
        PayCalCryptoState.hasDek = true;
        cryptoLog('[CRYPTO] Canonical unwrap succeeded');
        armCryptoIdleTimer();
        return true;
      } catch (err) {
        cryptoLog('[CRYPTO] Canonical unwrap failed, attempting legacy fallback:', {
          error: err?.message || String(err),
          stack: err?.stack || '',
        });

        try {
          cryptoLog('[CRYPTO] Attempting legacy unwrap', {
            derivationMode: 'credential-user',
            credentialFp: safeFingerprint(PayCalCryptoState.credentialId || ''),
            userFp: safeFingerprint(PayCalCryptoState.userId || ''),
          });
          await callCryptoWorker('unwrapWithPasskeyCredential', {
            wrappedDekPasskey: bootstrapData.wrappedDekPasskey,
            credentialId: PayCalCryptoState.credentialId,
            userId: PayCalCryptoState.userId,
            saltBase64: bootstrapData.encryptionSalt,
            dekVersion: PayCalCryptoState.dekVersion,
            cryptoVersion: PayCalCryptoState.cryptoVersion,
            derivationMode: 'credential-user',
          });

          // Legacy unwrap succeeded: immediately re-wrap with canonical derivation
          // so future logins are deterministic and userId-independent.
          const wrappedCanonical = await callCryptoWorker('wrapCurrentDekWithPasskeyCredential', {
            credentialId: PayCalCryptoState.credentialId,
            userId: PayCalCryptoState.userId,
            saltBase64: bootstrapData.encryptionSalt,
            derivationMode: 'credential-only',
          });
          await persistPasskeyWrap(wrappedCanonical);

          cryptoLog('[CRYPTO] Legacy unwrap succeeded and canonical migration persisted');

          PayCalCryptoState.hasDek = true;
          armCryptoIdleTimer();
          return true;
        } catch (legacyErr) {
          cryptoLog('[CRYPTO] Passkey unwrap failed after canonical+legacy attempts:', {
            error: legacyErr?.message || String(legacyErr),
            credentialFp: safeFingerprint(PayCalCryptoState.credentialId || ''),
            userFp: safeFingerprint(PayCalCryptoState.userId || ''),
          });
          return false;
        }
      }
    }

    if (PayCalCryptoState.credentialId && !bootstrapData.wrappedDekPasskey && bootstrapData.wrappedCredentialCount > 0) {
      cryptoLog('[CRYPTO] Existing passkey wrappers belong to other credentials; refusing DEK regeneration', {
        credentialFp: safeFingerprint(PayCalCryptoState.credentialId || ''),
        wrappedCredentialCount: bootstrapData.wrappedCredentialCount,
      });
      return false;
    }

    if (PayCalCryptoState.credentialId && !bootstrapData.wrappedDekPasskey) {
      cryptoLog('[CRYPTO] No existing DEK wrappers found; generating new passkey-backed DEK');
      // Continue to first-time DEK generation path below.
    }

    if (!PayCalCryptoState.credentialId) {
      cryptoLog('[CRYPTO] Passkey unlock unavailable: missing credential');
      return false;
    }

    // Hard safety guard: do not regenerate DEK when any wrapper already exists.
    if (bootstrapData.wrappedDekPasskey || bootstrapData.wrappedCredentialCount > 0) {
      throw new Error('[CRYPTO] DEK regeneration forbidden while wrapped DEK exists');
    }

    // Generate new DEK and wrap with active session credential
    // This runs only for first-time setup with no existing wrappers.
    cryptoLog('[CRYPTO] Generating new DEK', {
      hasExistingWrappedDek: !!bootstrapData.wrappedDekPasskey,
      credentialFp: safeFingerprint(PayCalCryptoState.credentialId || ''),
    });

      try {
        const wrapped = await callCryptoWorker('generateAndWrapWithPasskeyCredential', {
          credentialId: PayCalCryptoState.credentialId,
          userId: PayCalCryptoState.userId,
          saltBase64: bootstrapData.encryptionSalt,
          dekVersion: PayCalCryptoState.dekVersion,
          cryptoVersion: PayCalCryptoState.cryptoVersion,
        });

        await persistPasskeyWrap(wrapped);

        PayCalCryptoState.hasDek = true;
        PayCalCryptoState.dekVersion = Number(wrapped.dekVersion || 1);
        PayCalCryptoState.cryptoVersion = Number(wrapped.cryptoVersion || 1);
        armCryptoIdleTimer();
        return true;
      } catch (err) {
        cryptoError('[CRYPTO] Passkey bootstrap generation failed:', err);
        return false;
      }
    })();

    try {
      return await payCalDekEnsurePromise;
    } finally {
      payCalDekEnsurePromise = null;
    }
  }

  async function encryptEntry(entry) {
    if (!PayCalCryptoState.hasDek) {
      throw new Error('DEK unavailable');
    }

    return callCryptoWorker('encryptEntry', { entry });
  }

  async function ensureProfileEncrypted() {
    // Only encrypt profile once per DEK unlock
    if (PayCalCryptoState.profileEncrypted) {
      return true;
    }

    if (!PayCalCryptoState.hasDek) {
      return false;
    }

    try {
      const rawProfile = window.PAYCAL_USER_PROFILE_ENCRYPTED;
      if (!rawProfile || typeof rawProfile !== 'object') {
        return false;
      }

      // Call crypto-worker to encrypt profile
      await callCryptoWorker('encryptProfile', { profile: rawProfile });

      // Mark as encrypted so we don't repeat
      PayCalCryptoState.profileEncrypted = true;

      // Clear window references to prevent accidental plaintext access
      delete window.PAYCAL_USER_PROFILE_ENCRYPTED;
      
      // Profile is now stored in WebWorker memory, not in window
      return true;
    } catch (error) {
      console.error('[PROFILE] Encryption failed:', error);
      return false;
    }
  }

  async function wrapDEKWithPasskeyCredential(credentialId, saltBase64) {
    if (!PayCalCryptoState.hasDek) {
      throw new Error('[CRYPTO] DEK not available for wrapping');
    }

    if (!PayCalCryptoState.userId) {
      throw new Error('[CRYPTO] userId not available for wrapping');
    }

    try {
      const wrapped = await callCryptoWorker('wrapCurrentDekWithPasskeyCredential', {
        credentialId,
        userId: PayCalCryptoState.userId,
        saltBase64,
      });

      const uploadResponse = await fetch('/api/v1/user/crypto/passkey-wrap', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({
          credentialId,
          wrappedDekPasskey: wrapped.wrappedDekPasskey,
          dekVersion: wrapped.dekVersion,
          cryptoVersion: wrapped.cryptoVersion,
        }),
      });

      if (!uploadResponse.ok) {
        console.error('[CRYPTO] Failed to upload wrapped_dek_passkey:', uploadResponse.status);
        throw new Error('Failed to persist wrapped DEK passkey');
      }

      return true;
    } catch (err) {
      cryptoError('[CRYPTO] wrapDEKWithPasskeyCredential failed:', err);
      throw err;
    }
  }

  async function createRecoveryMaterial() {
    if (!PayCalCryptoState.hasDek) {
      const unlocked = await ensurePayCalDEK();
      if (!unlocked || !PayCalCryptoState.hasDek) {
        throw new Error('[CRYPTO] DEK not available. Open calendar once or authenticate with your passkey again.');
      }
    }

    return callCryptoWorker('generateRecoveryMaterial', {
      dekVersion: PayCalCryptoState.dekVersion,
      cryptoVersion: PayCalCryptoState.cryptoVersion,
    });
  }

  let plaintextWorkCapturePromise = null;

  async function fetchWithTimeout(url, options, timeoutMs = 10000) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);
    try {
      return await fetch(url, {
        ...options,
        signal: controller.signal,
      });
    } finally {
      clearTimeout(timer);
    }
  }

  async function fetchCalendarNonce() {
    const nonceResponse = await fetchWithTimeout('/api/v1/calendar/nonce', {
      method: 'GET',
      credentials: 'include',
      headers: { 'Accept': 'application/json' },
    });

    if (!nonceResponse.ok) {
      throw new Error(`Nonce HTTP ${nonceResponse.status}`);
    }

    const noncePayload = await nonceResponse.json();
    const csrfToken = noncePayload?.data?.nonce || noncePayload?.nonce || '';
    if (!csrfToken) {
      throw new Error('Missing csrf nonce');
    }

    return csrfToken;
  }

  function beginRuntimeAudit() {
    if (window.PayCalRuntimeIntegrity?.beginAudit) {
      return window.PayCalRuntimeIntegrity.beginAudit();
    }

    window.__PAYCAL_RUNTIME_AUDIT_IN_PROGRESS = true;
    return () => {
      window.__PAYCAL_RUNTIME_AUDIT_IN_PROGRESS = false;
    };
  }

  async function capturePlaintextWorkEntries(options = {}) {
    if (plaintextWorkCapturePromise) {
      return plaintextWorkCapturePromise;
    }

    plaintextWorkCapturePromise = (async () => {
      const endRuntimeAudit = beginRuntimeAudit();
      try {
        if (!PayCalCryptoState.hasDek) {
          return { status: 'skipped', reason: 'dek_unavailable', encrypted: 0 };
        }

        const batchLimit = Math.max(1, Math.min(100, Number(options.limit || 25)));
        const maxBatches = Math.max(1, Math.min(20, Number(options.maxBatches || 12)));
        let encryptedTotal = 0;
        let skippedTotal = 0;
        let failedTotal = 0;

      for (let batch = 0; batch < maxBatches; batch += 1) {
        const queueResponse = await fetchWithTimeout(
          `/api/v1/calendar/plaintext-capture?limit=${encodeURIComponent(String(batchLimit))}&include_archived=1`,
          {
            method: 'GET',
            credentials: 'include',
            headers: { 'Accept': 'application/json' },
          },
          15000
        );

        if (!queueResponse.ok) {
          throw new Error(`Plaintext capture queue HTTP ${queueResponse.status}`);
        }

        const queuePayload = await queueResponse.json();
        const queueData = queuePayload?.data || {};
        const entries = Array.isArray(queueData.entries) ? queueData.entries : [];
        if (entries.length === 0) {
          return {
            status: 'complete',
            encrypted: encryptedTotal,
            skipped: skippedTotal,
            failed: failedTotal,
          };
        }

        const finalized = [];
        for (const item of entries) {
          if (!item || typeof item !== 'object' || !item.key || !item.capture_token || !item.entry) {
            continue;
          }

          const encrypted = await encryptEntry(item.entry);
          finalized.push({
            key: String(item.key),
            capture_token: String(item.capture_token),
            encrypted_blob: encrypted.encrypted_blob,
          });
        }

        if (finalized.length === 0) {
          return {
            status: 'stalled',
            encrypted: encryptedTotal,
            skipped: skippedTotal,
            failed: failedTotal,
          };
        }

        const csrfToken = await fetchCalendarNonce();
        const finalizeResponse = await fetchWithTimeout('/api/v1/calendar/plaintext-capture', {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            csrf_token: csrfToken,
            entries: finalized,
          }),
        }, 20000);

        const finalizePayload = await finalizeResponse.json().catch(() => ({}));
        const finalizeData = finalizePayload?.data || {};
        encryptedTotal += Number(finalizeData.encrypted || 0);
        skippedTotal += Number(finalizeData.skipped || 0);
        failedTotal += Number(finalizeData.failed || 0);

        if (!finalizeResponse.ok || Number(finalizeData.failed || 0) > 0) {
          return {
            status: 'partial',
            encrypted: encryptedTotal,
            skipped: skippedTotal,
            failed: failedTotal,
          };
        }

        if (Number(queueData.remaining || 0) <= 0) {
          return {
            status: 'complete',
            encrypted: encryptedTotal,
            skipped: skippedTotal,
            failed: failedTotal,
          };
        }
      }

        return {
          status: 'limited',
          encrypted: encryptedTotal,
          skipped: skippedTotal,
          failed: failedTotal,
        };
      } finally {
        endRuntimeAudit();
      }
    })();

    try {
      const result = await plaintextWorkCapturePromise;
      cryptoLog('[CRYPTO] Plaintext work capture finished', result);
      return result;
    } catch (error) {
      cryptoLog('[CRYPTO] Plaintext work capture failed', { error: error?.message || String(error) });
      return { status: 'failed', reason: error?.message || String(error), encrypted: 0 };
    } finally {
      plaintextWorkCapturePromise = null;
    }
  }

  async function decryptEntry(entry) {
    if (!PayCalCryptoState.hasDek) {
      cryptoLog('[CRYPTO] decryptEntry called without DEK');
      return null;
    }
    if (!entry || !entry.encrypted_blob) {
      cryptoLog('[CRYPTO] decryptEntry: no encrypted_blob', { hasEntry: !!entry, keys: entry ? Object.keys(entry) : [] });
      return null;
    }

    try {
      const decrypted = await callCryptoWorker('decryptEntry', { entry });
      cryptoLog('[CRYPTO] decryptEntry succeeded', { blobLength: entry.encrypted_blob.length });
      return decrypted;
    } catch (err) {
      // Try to decode blob to extract DEK version for debugging
      let blobMeta = null;
      try {
        const blob = JSON.parse(latin1FromBase64(entry.encrypted_blob));
        blobMeta = {
          dekVersion: blob.dek_version || 'unknown',
          hasNonce: !!blob.nonce,
          hasCiphertext: !!blob.ciphertext,
          hasAad: !!blob.aad,
        };
      } catch {
        blobMeta = { decodeError: 'invalid_base64_or_json' };
      }

      console.error('[CRYPTO] decryptEntry failed', {
        error: err.message,
        blobLength: entry.encrypted_blob?.length,
        blobPrefix: entry.encrypted_blob?.substring(0, 50),
        blobMeta,
      });
      throw buildEncryptedWorkUnlockError('', err);
    }
  }

  function serverWeekDateIdsForDate(dateId) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(String(dateId || ''))) {
      return [];
    }

    const [year, month, day] = String(dateId).split('-').map((part) => Number(part));
    const date = new Date(Date.UTC(year, month - 1, day));
    if (
      date.getUTCFullYear() !== year
      || date.getUTCMonth() !== month - 1
      || date.getUTCDate() !== day
    ) {
      return [];
    }

    const weekStart = new Date(date.getTime() - (date.getUTCDay() * 86400000));
    return Array.from({ length: 7 }, (_, offset) => {
      const cursor = new Date(weekStart.getTime() + (offset * 86400000));
      const y = String(cursor.getUTCFullYear()).padStart(4, '0');
      const m = String(cursor.getUTCMonth() + 1).padStart(2, '0');
      const d = String(cursor.getUTCDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    });
  }

  async function assertVisibleEncryptedWeekDecryptable(activeDate) {
    if (!PayCalCryptoState.hasDek) {
      return;
    }

    const grid = document.getElementById('calendar-grid');
    if (!grid) {
      return;
    }

    const dateIds = serverWeekDateIdsForDate(activeDate);
    for (const dateId of dateIds) {
      const cell = grid.querySelector(`.datagrid_month_cell[data-id="${dateId}"]`);
      const raw = cell?.getAttribute('data-work-entries') || '[]';
      let parsedEntries = [];
      try {
        parsedEntries = JSON.parse(raw);
      } catch (parseError) {
        cryptoLog('[CRYPTO] Unable to parse visible entries before save', {
          dateId,
          error: parseError?.message || String(parseError),
        });
        parsedEntries = [];
      }

      for (const entry of Array.isArray(parsedEntries) ? parsedEntries : []) {
        if (!entry || typeof entry !== 'object' || typeof entry.encrypted_blob !== 'string' || entry.encrypted_blob.trim() === '') {
          continue;
        }

        try {
          await decryptEntry(entry);
        } catch (decryptError) {
          if (decryptError?.code === 'paycal_decrypt_unlock_mismatch') {
            throw buildEncryptedWorkUnlockError(dateId, decryptError);
          }
          throw decryptError;
        }
      }
    }
  }
  
  // =========================================================================
  // BOOT
  // =========================================================================

  
  function getCalendarGrids() {
    return Array.from(document.querySelectorAll('#calendar-v2-root .datagrid_layout_month'));
  }

  function getActiveCalendarGrid() {
    const activePanel = document.querySelector('#calendar-v2-root .calendar_view_panel:not(.hidden)');
    if (activePanel) {
      const activeGrid = activePanel.querySelector('.datagrid_layout_month');
      if (activeGrid) {
        return activeGrid;
      }
    }

    return document.getElementById('calendar-grid');
  }

  function mountCalendarViewToolbar() {
    const root = document.getElementById('calendar-v2-root');
    const activeView = root?.dataset.activeView || root?.dataset.defaultView || 'month';
    document.querySelectorAll('#calendar-v2-root .calendar_range_controls').forEach((controls) => {
      if (!(controls instanceof HTMLElement)) {
        return;
      }

      controls.hidden = controls.getAttribute('data-calendar-range-controls') !== activeView;
    });
  }

  function calendarRangeActionSelector(viewName, direction) {
    const normalized = ['month', 'week', 'pay_period'].includes(viewName) ? viewName : 'month';
    const actions = {
      month: direction === 'prev' ? 'prev-month' : 'next-month',
      week: direction === 'prev' ? 'prev-week' : 'next-week',
      pay_period: direction === 'prev' ? 'prev-pay-period' : 'next-pay-period',
    };

    return `.calendar_range_controls[data-calendar-range-controls="${normalized}"]:not([hidden]) [data-action="${actions[normalized]}"]`;
  }

  function initCalendarViewToggle() {
    const root = document.getElementById('calendar-v2-root');
    if (!root) {
      return;
    }

    const panels = {
      month: document.getElementById('calendar-view-month'),
      week: document.getElementById('calendar-view-week'),
      pay_period: document.getElementById('calendar-view-pay-period'),
    };

    const showView = (viewName) => {
      const normalized = ['month', 'week', 'pay_period'].includes(viewName) ? viewName : 'month';
      Object.entries(panels).forEach(([key, panel]) => {
        if (!(panel instanceof HTMLElement)) {
          return;
        }

        const isActive = key === normalized;
        panel.classList.toggle('hidden', !isActive);
        panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
      });

      root.dataset.activeView = normalized;
      mountCalendarViewToolbar();

      const url = new URL(window.location.href);
      url.searchParams.set('view', normalized);
      if (normalized === 'month') {
        url.searchParams.delete('week_start');
        url.searchParams.delete('pay_period_start');
      } else if (normalized === 'week') {
        url.searchParams.delete('pay_period_start');
        const weekPicker = document.getElementById('cal_week_picker_button');
        const weekAnchor = weekPicker?.getAttribute('data-anchor') || '';
        if (weekAnchor !== '') {
          url.searchParams.set('week_start', weekAnchor);
        }
      } else if (normalized === 'pay_period') {
        url.searchParams.delete('week_start');
        const payPeriodPicker = document.getElementById('cal_payperiod_picker_button');
        const payPeriodAnchor = payPeriodPicker?.getAttribute('data-anchor') || '';
        if (payPeriodAnchor !== '') {
          url.searchParams.set('pay_period_start', payPeriodAnchor);
        }
      }
      window.history.replaceState(window.history.state, '', url.pathname + url.search);

      const activeGrid = getActiveCalendarGrid();
      if (activeGrid) {
        refreshAllCalendarDayTooltips(activeGrid);
        activeGrid.querySelectorAll('.datagrid_month_cell[data-id]').forEach((cell) => {
          updateCalendarEarningsBadges(cell);
        });
      }
    };

    Array.from(document.querySelectorAll('input[name="calendar_view_mode"]')).forEach((radio) => {
      radio.addEventListener('change', () => {
        if (!(radio instanceof HTMLInputElement) || !radio.checked) {
          return;
        }

        showView(radio.value);
      });
    });

    const defaultView = root.dataset.defaultView || 'month';
    showView(defaultView);
  }

  /**
   * Initialize calendar grid on page load.
   */
  async function boot() {
    // Visible debug indicator
    coreLog('%c[Calendar Debug] Booting...', 'color: #0bc; font-weight: bold;');
    applyResolvedPlatformToken();
    
    const grids = getCalendarGrids();
    const grid = getActiveCalendarGrid() || document.getElementById('calendar-grid');
    calendarConsoleDebug('boot start', {
      path: window.location.pathname,
      hasGrid: !!grid,
      gridCount: grids.length,
      emailVerified: isCalendarEmailVerified(),
    });
        if (!isCalendarEmailVerified()) {
          lockCalendarUntilEmailVerified();
          coreLog('[Calendar] Locked: email verification required before editing.');
          calendarConsoleDebug('boot exit: email verification lock active', {
            hasGrid: !!grid,
          });
          return;
        }

    coreLog('%c[Calendar] Grid element found:', grid ? 'YES' : 'NO', grid);
    
    // Read user preferences from grid data attributes
    if (grid) {
      window.__CALENDAR_AUTOFOCUS = grid.dataset.autofocus || 'today';
      coreLog('[Calendar] Autofocus preference:', window.__CALENDAR_AUTOFOCUS);
    }
    
    const debugEl = document.getElementById('calendar-debug-indicator');
    
    if (grids.length > 0) {
      initCalendarViewToggle();

      grids.forEach((calendarGrid) => {
        attachGridCellHandlers(calendarGrid);
        attachDayContextMenuHandlers(calendarGrid);
        refreshAllCalendarDayTooltips(calendarGrid);
        calendarGrid.querySelectorAll('.datagrid_month_cell[data-id]').forEach((cell) => {
          updateCalendarEarningsBadges(cell);
        });
      });

      attachGridKeyboardNavigation();
      attachModalHandlers();
      attachDayContextMenuKeyboardCapture();
      void ensureCalendarSiteCatalog();

      const initialCellCount = grid ? grid.querySelectorAll('.datagrid_month_cell').length : 0;
      calendarConsoleDebug('grid handlers attached', {
        cellCount: initialCellCount,
        gridCount: grids.length,
      });

      if (grid) {
        ensureVisibleDailyEarningsLoaded(grid);
      }

      let hasDek = false;
      cryptoLog('[CRYPTO] Boot: attempting non-interactive DEK check...');
      calendarConsoleDebug('boot crypto phase start', {
        hasDekBefore: PayCalCryptoState.hasDek,
      });
      void (async () => {
        try {
          hasDek = await ensurePayCalDEK({ interactive: false });
          cryptoLog('[CRYPTO] Boot: ensurePayCalDEK returned:', hasDek, '| DEK state:', { hasDek: PayCalCryptoState.hasDek });
          calendarConsoleDebug('boot crypto ensurePayCalDEK resolved', {
            hasDek,
          });
        } catch (error) {
          cryptoLog('[CRYPTO] Boot: exception during DEK check', error);
          calendarConsoleDebug('boot crypto ensurePayCalDEK failed', {
            error: error?.message || String(error),
          });
        }

        if (hasDek) {
          cryptoLog('[CRYPTO] Boot: DEK available, encrypting profile');
          await ensureProfileEncrypted();
          cryptoLog('[CRYPTO] Boot: DEK available, hydrating grid');
          for (const calendarGrid of grids) {
            await hydrateEncryptedCalendarGrid(calendarGrid);
          }
          void capturePlaintextWorkEntries({ reason: 'calendar_boot' });
          cryptoLog('[CRYPTO] Boot: hydration complete');
          calendarConsoleDebug('boot crypto hydration complete', {
            hasDek: true,
          });
        } else {
          cryptoLog('[CRYPTO] Boot: DEK not available, hiding encrypted cells');
          grids.forEach((calendarGrid) => hideEncryptedCalendarGrid(calendarGrid));
          cryptoLog('[CRYPTO] Boot: hidden encrypted cells, unlock panel will show on access');
          calendarConsoleDebug('boot crypto unavailable; grid masked', {
            hasDek: false,
          });
        }
      })();
      
      const cellCount = initialCellCount;
      coreLog('%c[Calendar] Month grid initialized with ' + cellCount + ' cells', 'color: #0bc; font-weight: bold;');
      
      if (debugEl) {
        debugEl.textContent = '✓ Calendar.js booted (' + cellCount + ' cells)';
        debugEl.classList.remove('calendar-debug-error');
        debugEl.classList.add('calendar-debug-ok');
      }
      
      // Focus target day based on autofocus preference or URL parameter
      setTimeout(() => {
        const focusGrid = getActiveCalendarGrid() || grid;
        if (!focusGrid) {
          return;
        }

        const urlParams = new URLSearchParams(window.location.search);
        let targetDay = urlParams.get('day');
        const calendarRoot = document.getElementById('calendar-v2-root');
        const activeView = calendarRoot?.dataset.activeView || 'month';
        
        // If no URL parameter, use autofocus preference
        if (!targetDay) {
          const autofocusMode = window.__CALENDAR_AUTOFOCUS || 'today';
          const today = new Date();
          const todayYmd = String(today.getFullYear()) + '-'
            + String(today.getMonth() + 1).padStart(2, '0') + '-'
            + String(today.getDate()).padStart(2, '0');

          if (activeView === 'week' || activeView === 'pay_period') {
            targetDay = todayYmd;
            focusLog('[Calendar Focus] Active compact view, focusing today', { targetDay, activeView });
          } else if ('first' === autofocusMode) {
            const currentYear = parseInt(focusGrid.dataset.year || today.getFullYear(), 10);
            const currentMonth = parseInt(focusGrid.dataset.month || (today.getMonth() + 1), 10);
            // Focus first day of month
            targetDay = String(currentYear) + '-' + String(currentMonth).padStart(2, '0') + '-01';
            focusLog('[Calendar Focus] Autofocus mode: FIRST', { targetDay });
          } else if ('last' === autofocusMode) {
            const currentYear = parseInt(focusGrid.dataset.year || today.getFullYear(), 10);
            const currentMonth = parseInt(focusGrid.dataset.month || (today.getMonth() + 1), 10);
            // Focus last day of month
            const lastDay = new Date(currentYear, currentMonth, 0).getDate();
            targetDay = String(currentYear) + '-' + String(currentMonth).padStart(2, '0') + '-' + String(lastDay).padStart(2, '0');
            focusLog('[Calendar Focus] Autofocus mode: LAST', { targetDay, lastDay });
          } else {
            const currentYear = parseInt(focusGrid.dataset.year || today.getFullYear(), 10);
            const currentMonth = parseInt(focusGrid.dataset.month || (today.getMonth() + 1), 10);
            // Default 'today': focus today's date (if in displayed month) or first day
            if (today.getFullYear() === currentYear && (today.getMonth() + 1) === currentMonth) {
              const todayDate = String(today.getDate()).padStart(2, '0');
              targetDay = String(currentYear) + '-' + String(currentMonth).padStart(2, '0') + '-' + todayDate;
              focusLog('[Calendar Focus] Autofocus mode: TODAY (today)', { targetDay });
            } else {
              // Not in current month, fall back to first day
              targetDay = String(currentYear) + '-' + String(currentMonth).padStart(2, '0') + '-01';
              focusLog('[Calendar Focus] Autofocus mode: TODAY (not in current month, using first)', { targetDay });
            }
          }
        } else {
          focusLog('[Calendar Focus] Using URL parameter day:', targetDay);
        }
        
        if (targetDay) {
          focusTargetDay(targetDay, focusGrid);
        } else {
          focusLog('[Calendar Focus] No target day determined');
        }
      }, 100); // Small delay to ensure DOM is settled
    } else {
      console.error('[Calendar] Grid element #calendar-grid not found');
      if (debugEl) {
        debugEl.textContent = '✗ Grid not found';
        debugEl.classList.remove('calendar-debug-ok');
        debugEl.classList.add('calendar-debug-error');
      }
    }
  }

  async function hydrateEncryptedCalendarGrid(grid) {
    if (!grid || !PayCalCryptoState.hasDek) {
      return;
    }

    const perfStart = performance.now();
    const cells = grid.querySelectorAll('.datagrid_month_cell[data-work-entries]');
    const workEntryPosition = grid.dataset.workEntryPosition || 'left';

    const hasExplicitHours = (entry) => {
      if (!entry || typeof entry !== 'object') {
        return false;
      }

      return entry.hours !== undefined || entry.h !== undefined
        || entry.regular_hours !== undefined || entry.regular !== undefined || entry.r !== undefined
        || entry.overtime_hours !== undefined || entry.overtime !== undefined || entry.o !== undefined
        || entry.living_out_allowance !== undefined || entry.living_out !== undefined || entry.loa !== undefined || entry.l !== undefined
        || entry.travel_hours !== undefined || entry.travel !== undefined || entry.t !== undefined;
    };

    cryptoLog('[CRYPTO] Starting batch hydration', { cellCount: cells.length });

    // Phase 1: Collect all encrypted entries from all cells
    const batchEntries = [];
    const cellDataMap = new Map(); // Map cell ID to parsed entries
    
    for (const cell of cells) {
      const cellId = cell.getAttribute('data-id');
      const raw = cell.getAttribute('data-work-entries') || '[]';
      let parsed = [];
      
      try {
        parsed = JSON.parse(raw);
      } catch (error) {
        cryptoLog('[CRYPTO] Failed to parse data-work-entries JSON for cell', cellId, error);
        parsed = [];
      }

      cellDataMap.set(cellId, parsed);

      // Collect encrypted entries for batch processing
      for (const entry of Array.isArray(parsed) ? parsed : []) {
        if (entry && entry.encrypted_blob) {
          batchEntries.push({
            date: cellId,
            entry: entry,
          });
        }
      }
    }

    cryptoLog('[CRYPTO] Collected entries for batch decrypt', { 
      totalCells: cells.length,
      encryptedEntriesCount: batchEntries.length,
    });

    // Phase 2: Batch decrypt all entries in one worker call
    let batchResult = { results: {}, failures: [] };
    
    if (batchEntries.length > 0) {
      const decryptStart = performance.now();
      try {
        batchResult = await callCryptoWorker('decryptEntriesBatch', {
          entries: batchEntries,
        });
        const decryptDuration = performance.now() - decryptStart;
        cryptoLog('[CRYPTO] Batch decrypt completed', {
          successCount: Object.keys(batchResult.results).length,
          failureCount: batchResult.failures.length,
          durationMs: Math.round(decryptDuration),
          entriesPerMs: (batchEntries.length / decryptDuration).toFixed(2),
        });
      } catch (error) {
        console.error('[CRYPTO] Batch decrypt failed catastrophically', error);
        // Fall back to empty results
      }
    }

    // Phase 3: Apply decrypted results to DOM
    for (const cell of cells) {
      const cellId = cell.getAttribute('data-id');
      const parsedEntries = cellDataMap.get(cellId) || [];
      
      // Check if cell has any encrypted entries
      const hasEncryptedEntries = parsedEntries.some(e => e && e.encrypted_blob);
      const explicitEntries = parsedEntries.filter((entry) => hasExplicitHours(entry));
      
      let decrypted = [];
      if (hasEncryptedEntries) {
        // Prefer decrypted batch results, but preserve explicit fields when decrypt output is empty.
        const batchCellResults = Array.isArray(batchResult.results[cellId]) ? batchResult.results[cellId] : [];
        if (batchCellResults.length === 0 && explicitEntries.length === 0) {
          // Keep initial server-rendered content when we cannot decrypt and have no explicit fallback.
          continue;
        }

        decrypted = batchCellResults.length > 0 ? batchCellResults : explicitEntries;

        // Merge site_color from outer entry metadata (unencrypted) into each decrypted entry.
        // The encrypted blob doesn't carry site_color; it lives on the outer work entry hash.
        const outerEncrypted = parsedEntries.filter(e => e && e.encrypted_blob);
        decrypted = decrypted.map((dec, idx) => {
          const outer = outerEncrypted[idx];
          const mergedColor = (outer && outer.site_color) ? outer.site_color : null;
          if (mergedColor && !dec.site_color) {
            return Object.assign({}, dec, { site_color: mergedColor });
          }
          return dec;
        });
      } else {
        // Cell has only plaintext entries or is empty
        decrypted = parsedEntries;
      }
      
      cell.setAttribute('data-work-entries', JSON.stringify(decrypted));
      updateCalendarDayTooltip(cell, decrypted);
      const content = cell.querySelector('.datagrid_month_cell_content');
      if (content) {
        const dateAria = cell.getAttribute('data-date-aria') || cell.getAttribute('data-date') || cellId || '';
        Guardian.setHTML(content, renderWorkEntriesMarkup(decrypted, workEntryPosition, dateAria));
      }
    }

    const perfEnd = performance.now();
    const totalDuration = perfEnd - perfStart;

    // Phase 4: Auto-repair failures
    if (batchResult.failures.length > 0) {
      if (isDelegatedCalendarViewActive()) {
        cryptoLog('[CRYPTO] Delegated view active; skipping auto-repair for decrypt failures', {
          failureCount: batchResult.failures.length,
        });
        return;
      }

      const repairDates = batchResult.failures.filter((dateId) => {
        const entries = Array.isArray(cellDataMap.get(dateId)) ? cellDataMap.get(dateId) : [];
        const hasEncrypted = entries.some((entry) => entry && typeof entry === 'object' && !!entry.encrypted_blob);
        const hasRenderableEntry = entries.some((entry) => hasExplicitHours(entry));
        return hasEncrypted && !hasRenderableEntry;
      });

      cryptoLog('[CRYPTO] Hydration completed with decrypt failures', {
        failureCount: batchResult.failures.length,
        sampleCells: batchResult.failures.slice(0, 5),
        repairCandidateCount: repairDates.length,
        totalDurationMs: Math.round(totalDuration),
      });

      if (repairDates.length > 0) {
        cryptoLog('[CRYPTO] Triggering auto-repair for placeholder-only cells:', repairDates);
        const repairResult = await repairCorruptEntries(repairDates);
        cryptoLog('[CRYPTO] Repair result:', repairResult);
      }
    } else {
      cryptoLog('[CRYPTO] ✓ Batch hydration completed successfully', {
        totalDurationMs: Math.round(totalDuration),
        cellsProcessed: cells.length,
        encryptedEntries: batchEntries.length,
      });
    }
  }

  async function repairCorruptEntries(dates) {
    cryptoLog('[CRYPTO] repairCorruptEntries called', { dates, datesLength: dates?.length });
    
    if (!dates || dates.length === 0) {
      cryptoLog('[CRYPTO] No dates to repair, exiting early');
      return { status: 'skipped', reason: 'no_dates' };
    }

    // Client-side warning for large repair batches
    const maxAutoRepair = 10;
    if (dates.length > maxAutoRepair) {
      cryptoLog('[CRYPTO] Repair count exceeds safety threshold', {
        count: dates.length,
        max: maxAutoRepair,
        note: 'Backend will likely reject this request',
      });
    }

    cryptoLog('[CRYPTO] Auto-repairing corrupt encrypted entries', { dates, count: dates.length });

    try {
      const requestBody = new URLSearchParams({
        dates: JSON.stringify(dates),
      });
      cryptoLog('[CRYPTO] Repair request body:', requestBody.toString());
      
      const response = await fetch('/api/v1/calendar/repair-corrupt', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: requestBody,
      });

      cryptoLog('[CRYPTO] Repair response status:', response.status, response.statusText);
      
      if (!response.ok) {
        const errorText = await response.text();
        
        // Special handling for safety limit rejection
        if (response.status === 403) {
          console.error('[CRYPTO] Repair REJECTED by safety limit', {
            dates,
            count: dates.length,
            message: 'Too many corrupt entries - manual intervention required',
          });
          return { status: 'safety_limit_exceeded', httpStatus: 403, dates };
        }
        
        console.error('[CRYPTO] Repair request failed', { 
          status: response.status, 
          statusText: response.statusText,
          body: errorText,
        });
        return { status: 'error', httpStatus: response.status };
      }

      const result = await response.json();
      cryptoLog('[CRYPTO] Repair completed successfully', result);

      // Update the DOM to reflect the repair (clear the cells)
      dates.forEach((date) => {
        const cell = document.querySelector(`.datagrid_month_cell[data-id="${date}"]`);
        if (cell) {
          cell.setAttribute('data-work-entries', '[]');
          updateCalendarDayTooltip(cell, []);
          const content = cell.querySelector('.datagrid_month_cell_content');
          if (content) {
            content.replaceChildren();
          }
          cryptoLog('[CRYPTO] Cleared cell in DOM:', date);
        } else {
          cryptoLog('[CRYPTO] Could not find cell to clear:', date);
        }
      });
      
      return { status: 'success', result };
    } catch (error) {
      console.error('[CRYPTO] Failed to repair corrupt entries', {
        error: error.message,
        stack: error.stack,
        dates,
      });
      return { status: 'exception', error: error.message };
    }
  }

  function hideEncryptedCalendarGrid(grid) {
    cryptoLog('[CRYPTO] hideEncryptedCalendarGrid: clearing encrypted entries from view (DEK not available)');
    if (!grid) {
      return;
    }

    const cells = grid.querySelectorAll('.datagrid_month_cell[data-work-entries]');
    let clearedCount = 0;
    for (const cell of cells) {
      const raw = cell.getAttribute('data-work-entries') || '[]';
      let parsed = [];
      try {
        parsed = JSON.parse(raw);
      } catch {
        parsed = [];
      }

      const hasEncryptedEntries = Array.isArray(parsed)
        && parsed.some((entry) => entry && typeof entry === 'object' && !!entry.encrypted_blob);
      if (!hasEncryptedEntries) {
        continue;
      }

      // Keep server-rendered placeholder rows visible when encrypted entries exist.
      if (Array.isArray(parsed) && parsed.length > 0) {
        continue;
      }

      const content = cell.querySelector('.datagrid_month_cell_content');
      if (content) {
        if (isDelegatedCalendarViewActive()) {
          continue;
        }

        if ((content.textContent || '').trim() !== '' || content.children.length > 0) {
          continue;
        }

        cryptoLog('[CRYPTO] Clearing encrypted entries from cell', cell.getAttribute('data-id'));
        content.textContent = '';
        clearedCount++;
      }
    }
    cryptoLog('[CRYPTO] hideEncryptedCalendarGrid: cleared', clearedCount, 'cells');
  }

  /**
   * Focus a specific day in the calendar.
   * @param {string} dateStr - The date as 'YYYY-MM-DD' or just day number
   * @param {HTMLElement} grid - The calendar grid
   */
  function focusTargetDay(dateStr, grid) {
    focusLog('[Calendar Focus] focusTargetDay called with dateStr:', dateStr);
    
    let targetDate;
    
    // Check if dateStr is a full date (YYYY-MM-DD) or just a day number
    if (dateStr.includes('-')) {
      // Full date string
      targetDate = dateStr;
      focusLog('[Calendar Focus] Using full date string:', targetDate);
    } else {
      // Just day number - try to get month from URL or grid
      const month = new URLSearchParams(window.location.search).get('month');
      if (month) {
        const dayNum = parseInt(dateStr, 10);
        const dayPadded = String(dayNum).padStart(2, '0');
        const [year, monthNum] = month.split('-');
        targetDate = `${year}-${monthNum}-${dayPadded}`;
        focusLog('[Calendar Focus] Constructed date from URL month:', targetDate);
      } else {
        console.error('[Calendar Focus] Cannot determine date - no month in URL and dateStr is not full date');
        return;
      }
    }
    
    focusLog('[Calendar Focus] Looking for cell with data-id:', targetDate);
    
    const targetCell = grid.querySelector(`[data-id="${targetDate}"]`);
    focusLog('[Calendar Focus] Target cell found:', !!targetCell, targetCell);
    
    if (targetCell) {
      const allCells = grid.querySelectorAll('.datagrid_month_cell');
      focusLog('[Calendar Focus] Total cells in grid:', allCells.length);

      setGridCellFocusState(targetCell, true);
      focusLog('[Calendar Focus] ✓ Focused on day:', targetDate, 'stored:', window._CALENDAR_FOCUSED_DAY);
    } else {
      focusLog('[Calendar Focus] ✗ Target day not found in grid:', targetDate);
      // Fallback: focus first cell
      const firstCell = grid.querySelector('.datagrid_month_cell');
      if (firstCell) {
        setGridCellFocusState(firstCell, true);
        
        // Extract and store day from first cell's data-id
        const firstCellDate = firstCell.getAttribute('data-id');
        if (firstCellDate) {
          const fallbackDay = firstCellDate.split('-')[2];
          focusLog('[Calendar Focus] Fallback: focused on first cell, stored:', fallbackDay);
        } else {
          focusLog('[Calendar Focus] Fallback: focused on first cell (no data-id)');
        }
      }
    }
  }

  // =========================================================================
  // MONTH GRID NAVIGATION (for calendar v2)
  // =========================================================================

  /**
   * Keep one active/selected day cell for keyboard and screen-reader parity.
   * @param {HTMLElement} targetCell
   * @param {boolean} focusCell
   * @param {FocusOptions|undefined} focusOptions
   */
  function setGridCellFocusState(targetCell, focusCell = true, focusOptions) {
    if (!targetCell) return;

    const grid = targetCell.closest('.datagrid_month_grid');
    if (!grid) return;

    const previousFocusDate = window._CALENDAR_LAST_GRID_FOCUS_DATE || '';
    const targetDateId = targetCell.getAttribute('data-id') || '';
    if (!calendarShiftKeyHeld && previousFocusDate !== '' && targetDateId !== '' && targetDateId !== previousFocusDate) {
      clearShiftRangeSelection(grid);
    }

    const allCells = grid.querySelectorAll('.datagrid_month_cell');
    allCells.forEach((cell) => {
      const isSelected = cell === targetCell;
      cell.setAttribute('tabindex', isSelected ? '0' : '-1');
      cell.setAttribute('aria-selected', isSelected ? 'true' : 'false');
    });

    applyShiftRangeSelection(grid, targetCell);

    if (focusCell) {
      try {
        targetCell.focus(focusOptions || { preventScroll: true });
      } catch {
        targetCell.focus();
      }
    }

    const dateId = targetCell.getAttribute('data-id');
    if (dateId) {
      window._CALENDAR_LAST_GRID_FOCUS_DATE = dateId;
      window._CALENDAR_FOCUSED_DAY = dateId.split('-')[2];
    }
  }

  function clearShiftRangeSelection(grid) {
    if (!grid) {
      return;
    }

    grid.querySelectorAll('.datagrid_month_cell_shift_range, .datagrid_month_cell_shift_range_start, .datagrid_month_cell_shift_range_end, .datagrid_month_cell[data-selected="true"]').forEach((cell) => {
      cell.classList.remove('datagrid_month_cell_shift_range');
      cell.classList.remove('datagrid_month_cell_shift_range_start');
      cell.classList.remove('datagrid_month_cell_shift_range_end');
      cell.removeAttribute('data-selected');
      cell.removeAttribute('data-selected-start');
      cell.removeAttribute('data-selected-end');
    });

    refreshAllCalendarDayTooltips(grid);
  }

  function applyShiftRangeSelection(grid, targetCell) {
    if (!calendarShiftKeyHeld || !targetCell) {
      return;
    }

    clearShiftRangeSelection(grid);

    const cells = Array.from(grid.querySelectorAll('.datagrid_month_cell'));
    const currentIndex = cells.indexOf(targetCell);
    if (currentIndex < 0) {
      return;
    }

    let anchorIndex = -1;
    if (calendarShiftAnchorDateId !== '') {
      anchorIndex = cells.findIndex((cell) => cell.getAttribute('data-id') === calendarShiftAnchorDateId);
    }

    if (anchorIndex < 0) {
      anchorIndex = currentIndex;
      calendarShiftAnchorDateId = targetCell.getAttribute('data-id') || '';
    }

    const start = Math.min(anchorIndex, currentIndex);
    const end = Math.max(anchorIndex, currentIndex);
    for (let i = start; i <= end; i += 1) {
      const rangeCell = cells[i];
      if (rangeCell) {
        rangeCell.classList.add('datagrid_month_cell_shift_range');
        rangeCell.setAttribute('data-selected', 'true');
      }
    }

    if (cells[start]) {
      cells[start].classList.add('datagrid_month_cell_shift_range_start');
      cells[start].setAttribute('data-selected-start', 'true');
    }
    if (cells[end]) {
      cells[end].classList.add('datagrid_month_cell_shift_range_end');
      cells[end].setAttribute('data-selected-end', 'true');
    }

    refreshAllCalendarDayTooltips(grid);
  }

  function refreshShiftRangeSelectionOnActiveCell() {
    const grid = document.querySelector('#calendar-grid .datagrid_month_grid');
    if (!grid) {
      return;
    }

    const activeCell = grid.querySelector('.datagrid_month_cell[tabindex="0"]')
      || (document.activeElement && document.activeElement.closest ? document.activeElement.closest('.datagrid_month_cell') : null);
    applyShiftRangeSelection(grid, activeCell);
  }

  /**
   * Attach click and keyboard handlers to month grid cells.
   * @param {HTMLElement} grid - The calendar grid container
   */
  function attachGridCellHandlers(grid) {
    if (!grid) return;

    const cells = grid.querySelectorAll('.datagrid_month_cell');
    cells.forEach((cell, index) => {
      if (!cell) return;

      cell.addEventListener('click', handleGridCellClick);
      cell.addEventListener('keydown', handleGridCellKeydown);
      cell.addEventListener('focus', () => setGridCellFocusState(cell, false));
      cell.addEventListener('mouseenter', handleCalendarCellTooltipEnter);
      cell.addEventListener('mousemove', handleCalendarCellTooltipMove);
      cell.addEventListener('mouseleave', handleCalendarCellTooltipLeave);
      cell.addEventListener('focus', handleCalendarCellTooltipFocus);
      cell.addEventListener('blur', handleCalendarCellTooltipBlur);
      
      // Initialize single selected cell semantics.
      cell.setAttribute('tabindex', index === 0 ? '0' : '-1');
      cell.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
    });

    // Focus the first cell for initial keyboard navigation
    if (cells.length > 0) {
      setGridCellFocusState(cells[0], true);
    }
  }

  function ensureCalendarHoverTooltip() {
    if (calendarHoverTooltipEl) {
      return calendarHoverTooltipEl;
    }

    const tooltip = document.createElement('div');
    tooltip.id = 'calendar_day_hover_tooltip';
    tooltip.className = 'calendar_day_hover_tooltip hidden';
    tooltip.setAttribute('role', 'tooltip');
    tooltip.setAttribute('aria-hidden', 'true');
    document.body.appendChild(tooltip);
    calendarHoverTooltipEl = tooltip;
    return tooltip;
  }

  function setCalendarHoverTooltipText(text) {
    const tooltip = ensureCalendarHoverTooltip();
    tooltip.replaceChildren();

    const rows = String(text || '')
      .split('|')
      .map((part) => part.trim())
      .filter((part) => part !== '');

    rows.forEach((row) => {
      const separatorIndex = row.indexOf(':');
      if (separatorIndex === -1) {
        const line = document.createElement('div');
        line.className = 'calendar_day_hover_tooltip_line';
        line.textContent = row;
        tooltip.appendChild(line);
        return;
      }

      const label = row.slice(0, separatorIndex).trim();
      const value = row.slice(separatorIndex + 1).trim();

      const pair = document.createElement('div');
      pair.className = 'calendar_day_hover_tooltip_line item_pair';

      const labelEl = document.createElement('div');
      labelEl.className = 'calendar_day_hover_tooltip_label item_label';
      labelEl.textContent = `${label}:`;

      const valueEl = document.createElement('div');
      valueEl.className = 'calendar_day_hover_tooltip_value item_value';
      valueEl.textContent = value;

      pair.appendChild(labelEl);
      pair.appendChild(valueEl);
      tooltip.appendChild(pair);
    });
  }

  function positionCalendarHoverTooltip(clientX, clientY) {
    const tooltip = ensureCalendarHoverTooltip();
    const pad = 16;
    const offset = 18;

    tooltip.style.setProperty('--calendar-hover-tooltip-left', '0px');
    tooltip.style.setProperty('--calendar-hover-tooltip-top', '0px');

    const rect = tooltip.getBoundingClientRect();
    let left = clientX + offset;
    let top = clientY + offset;

    if (left + rect.width + pad > window.innerWidth) {
      left = Math.max(pad, clientX - rect.width - offset);
    }
    if (top + rect.height + pad > window.innerHeight) {
      top = Math.max(pad, clientY - rect.height - offset);
    }

    tooltip.style.setProperty('--calendar-hover-tooltip-left', `${left}px`);
    tooltip.style.setProperty('--calendar-hover-tooltip-top', `${top}px`);
  }

  function showCalendarHoverTooltip(cell, clientX, clientY) {
    if (!cell) {
      return;
    }

    const tooltipText = cell.getAttribute('data-tooltip') || '';
    if (tooltipText.trim() === '') {
      return;
    }

    setCalendarHoverTooltipText(tooltipText);
    const tooltip = ensureCalendarHoverTooltip();
    tooltip.classList.remove('hidden');
    tooltip.setAttribute('aria-hidden', 'false');
    calendarHoverTooltipCell = cell;
    positionCalendarHoverTooltip(clientX, clientY);
  }

  function hideCalendarHoverTooltip() {
    const tooltip = ensureCalendarHoverTooltip();
    tooltip.classList.add('hidden');
    tooltip.setAttribute('aria-hidden', 'true');
    calendarHoverTooltipCell = null;
  }

  function handleCalendarCellTooltipEnter(event) {
    const cell = event.currentTarget && event.currentTarget.closest
      ? event.currentTarget.closest('.datagrid_month_cell')
      : null;
    if (!cell) {
      return;
    }

    const x = Number.isFinite(event.clientX) ? event.clientX : 0;
    const y = Number.isFinite(event.clientY) ? event.clientY : 0;
    showCalendarHoverTooltip(cell, x, y);
  }

  function handleCalendarCellTooltipMove(event) {
    if (!calendarHoverTooltipCell) {
      return;
    }

    const x = Number.isFinite(event.clientX) ? event.clientX : 0;
    const y = Number.isFinite(event.clientY) ? event.clientY : 0;
    positionCalendarHoverTooltip(x, y);
  }

  function handleCalendarCellTooltipLeave() {
    hideCalendarHoverTooltip();
  }

  function handleCalendarCellTooltipFocus(event) {
    const cell = event.currentTarget && event.currentTarget.closest
      ? event.currentTarget.closest('.datagrid_month_cell')
      : null;
    if (!cell) {
      return;
    }

    const rect = cell.getBoundingClientRect();
    showCalendarHoverTooltip(cell, rect.left + 24, rect.top + 24);
  }

  function handleCalendarCellTooltipBlur() {
    hideCalendarHoverTooltip();
  }

  /**
   * Handle month grid cell click.
   * @param {MouseEvent} event - The click event
   */
  function handleGridCellClick(event) {
    const cell = event.currentTarget.closest('.datagrid_month_cell');
    if (!cell) {
      calendarConsoleDebug('cell click ignored: no calendar cell', {
        eventType: event?.type,
      });
      return;
    }

    const dateId = cell.getAttribute('data-id');
    calendarConsoleDebug('grid cell click', {
      dateId,
      classes: cell.className,
      ariaDisabled: cell.getAttribute('aria-disabled'),
      pointerEvents: window.getComputedStyle(cell).pointerEvents,
      calendarLockedForVerification,
      emailVerified: isCalendarEmailVerified(),
    });
    if (!dateId) {
      calendarConsoleDebug('cell click ignored: missing dateId', {
        text: cell.textContent,
      });
      return;
    }

    setGridCellFocusState(cell, true);
    coreLog('[Calendar Click] Stored focused day:', window._CALENDAR_FOCUSED_DAY, 'from:', dateId);

    // Check if date is locked before opening modal
    if (isDateLocked(dateId)) {
      calendarConsoleDebug('cell click blocked: date is locked', {
        dateId,
      });
      showLockedDateMessage(dateId);
      return;
    }

    logRowInteraction('click', dateId);
    openModalForDate(dateId);
  }

  /**
   * Handle month grid cell keyboard events.
   * Arrow keys for navigation, Enter to select, Escape to close.
   * @param {KeyboardEvent} event - The keyboard event
   */
  function handleGridCellKeydown(event) {
    const cell = event.currentTarget.closest('.datagrid_month_cell');
    if (!cell) return;

    const contextMenu = document.getElementById('calendar_day_context_menu');
    const contextMenuOpen = !!(contextMenu && !contextMenu.classList.contains('hidden'));
    if (contextMenuOpen) {
      if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        hideDayContextMenu();
        return;
      }

      // While the context menu is open, grid navigation keys must not move day focus.
      event.preventDefault();
      event.stopPropagation();
      event.stopImmediatePropagation();
      return;
    }

    const dateId = cell.getAttribute('data-id');
    if (!dateId) return;

    coreLog('[Calendar KeyDown]', event.key, 'on cell', cell.getAttribute('data-id'));

    const triggerRangeNavigation = (direction) => {
      const root = document.getElementById('calendar-v2-root');
      const activeView = root?.dataset.activeView || 'month';
      let selector = '';

      if (activeView === 'month') {
        selector = calendarRangeActionSelector('month', direction);
      } else if (activeView === 'week') {
        selector = calendarRangeActionSelector('week', direction);
      } else if (activeView === 'pay_period') {
        selector = calendarRangeActionSelector('pay_period', direction);
      } else {
        return;
      }

      const button = document.querySelector(selector);
      if (!button) {
        console.error('[Calendar Nav] Missing range navigation button', { direction, selector, activeView });
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      button.click();
    };

    if (event.key === 'Delete') {
      event.preventDefault();
      void deleteDayEntries(dateId);
      return;
    }

    if (event.key === 'ContextMenu' || (event.shiftKey && event.key === 'F10') || (event.ctrlKey && event.key === 'Enter')) {
      showDayContextMenu(event, dateId);
      return;
    }

    switch (event.key) {
      case 'Home':
        event.preventDefault();
        navigateGridCellHome(cell);
        break;
      case 'End':
        event.preventDefault();
        navigateGridCellEnd(cell);
        break;
      case 'ArrowUp':
        event.preventDefault();
        navigateGridCellUp(cell);
        break;
      case 'ArrowDown':
        event.preventDefault();
        navigateGridCellDown(cell);
        break;
      case 'ArrowLeft':
        event.preventDefault();
        navigateGridCellLeft(cell);
        break;
      case 'ArrowRight':
        event.preventDefault();
        navigateGridCellRight(cell);
        break;
      case 'PageUp':
        triggerRangeNavigation('prev');
        break;
      case 'PageDown':
        triggerRangeNavigation('next');
        break;
      case 'Enter':
        event.preventDefault();
        if (dateId) {
          coreLog('[Calendar] Entering date:', dateId);
          // Check if date is locked before opening modal
          if (isDateLocked(dateId)) {
            showLockedDateMessage(dateId);
            return;
          }
          logRowInteraction('enter', dateId);
          openModalForDate(dateId);
        }
        break;
      case 'Escape':
        event.preventDefault();
        clearShiftRangeSelection(cell.closest('.datagrid_month_grid'));
        calendarShiftAnchorDateId = '';
        closeModal();
        break;
    }
  }

  function attachDayContextMenuHandlers(grid) {
    const menu = document.getElementById('calendar_day_context_menu');
    if (!grid || !menu) {
      return;
    }

    bindDayContextMenuHandlers(menu);

    grid.querySelectorAll('.datagrid_month_cell').forEach(cell => {
      cell.addEventListener('contextmenu', function(event) {
        const dateId = this.getAttribute('data-id');
        if (!dateId) {
          return;
        }
        showDayContextMenu(event, dateId);
      });
    });

  }

  function bindDayContextMenuHandlers(menu) {
    if (!menu) {
      return;
    }

    const getMenuItems = (enabledOnly = false) => {
      const items = Array.from(menu.querySelectorAll('[role="menuitem"][data-action]'));
      if (!enabledOnly) {
        return items;
      }

      return items.filter((item) => item.getAttribute('aria-disabled') !== 'true');
    };

    const focusFirstEnabledItem = () => {
      const items = getMenuItems(true);
      if (items[0]) {
        items[0].focus();
      }
    };

    const focusLastEnabledItem = () => {
      const items = getMenuItems(true);
      if (items.length > 0) {
        items[items.length - 1].focus();
      }
    };

    if (!dayContextMenuMenuBound) {
      menu.querySelectorAll('[role="menuitem"][data-action]').forEach(item => {
        item.addEventListener('mouseenter', function() {
          if (menu.classList.contains('hidden')) {
            return;
          }

          if (this.getAttribute('aria-disabled') === 'true') {
            return;
          }

          if (document.activeElement !== this) {
            this.focus({ preventScroll: true });
          }
        });

        item.addEventListener('click', function(event) {
          if (this.getAttribute('aria-disabled') === 'true') {
            event.preventDefault();
            event.stopPropagation();
            return;
          }

          event.preventDefault();
          event.stopPropagation();
          const action = this.getAttribute('data-action') || '';
          void handleDayContextMenuAction(action);
        });

        item.addEventListener('keydown', function(event) {
          if (this.getAttribute('aria-disabled') === 'true' && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            return;
          }

          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            const action = this.getAttribute('data-action') || '';
            void handleDayContextMenuAction(action);
            return;
          }

          if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            hideDayContextMenu();
          }
        });
      });

      menu.addEventListener('keydown', function(event) {
        const items = getMenuItems(true);
        if (items.length === 0) {
          return;
        }

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
          event.preventDefault();
          event.stopPropagation();
          event.stopImmediatePropagation();
          const current = document.activeElement;
          const currentIndex = items.indexOf(current);
          const nextIndex = event.key === 'ArrowDown'
            ? (currentIndex + 1 + items.length) % items.length
            : (currentIndex - 1 + items.length) % items.length;
          items[nextIndex].focus();
          return;
        }

        if (event.key === 'Home' || event.key === 'End') {
          event.preventDefault();
          event.stopPropagation();
          event.stopImmediatePropagation();
          if (event.key === 'Home') {
            focusFirstEnabledItem();
          } else {
            focusLastEnabledItem();
          }
          return;
        }

        if (event.key === 'Tab') {
          // Let users leave the custom menu with standard Tab/Shift+Tab behavior.
          hideDayContextMenu();
        }
      });

      dayContextMenuMenuBound = true;
    }

    if (!dayContextMenuDocumentBound) {
      document.addEventListener('click', function() {
        hideDayContextMenu();
      });
      dayContextMenuDocumentBound = true;
    }
  }

  function attachDayContextMenuKeyboardCapture() {
    if (dayContextMenuKeyboardCaptureBound) {
      return;
    }
    dayContextMenuKeyboardCaptureBound = true;

    document.addEventListener('keydown', function(event) {
      const liveMenu = document.getElementById('calendar_day_context_menu');
      if (!liveMenu || liveMenu.classList.contains('hidden')) {
        return;
      }

      const items = Array.from(liveMenu.querySelectorAll('[role="menuitem"][data-action]')).filter((item) => item.getAttribute('aria-disabled') !== 'true');
      if (items.length === 0) {
        return;
      }

      const active = document.activeElement;
      const activeIndex = items.indexOf(active);
      const focusFirst = () => items[0].focus();
      const focusLast = () => items[items.length - 1].focus();

      if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        hideDayContextMenu();
        return;
      }

      if (event.key === 'Tab') {
        // Do not trap keyboard users inside the context menu.
        hideDayContextMenu();
        return;
      }

      if (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Home' || event.key === 'End') {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        if (event.key === 'Home') {
          focusFirst();
          return;
        }
        if (event.key === 'End') {
          focusLast();
          return;
        }

        if (activeIndex < 0) {
          if (event.key === 'ArrowUp') {
            focusLast();
            return;
          }
          focusFirst();
          return;
        }

        const nextIndex = event.key === 'ArrowDown'
          ? (activeIndex + 1) % items.length
          : (activeIndex - 1 + items.length) % items.length;
        items[nextIndex].focus();
      }
    }, true);
  }

  function getClipboardDayEntries() {
    return calendarClipboard ? [...calendarClipboard] : [];
  }

  function getDayContextMenuState(dateId) {
    const dayEntries = normalizeEntriesForSave(getDayEntriesFromCell(dateId));
    const clipboardEntries = getClipboardDayEntries();
    const locked = isDateLocked(dateId);

    return {
      open: !locked,
      copy: dayEntries.length > 0,
      paste: !locked && clipboardEntries.length > 0,
      delete: !locked && dayEntries.length > 0,
    };
  }

  function applyDayContextMenuState(menu, dateId) {
    const state = getDayContextMenuState(dateId);

    menu.querySelectorAll('[role="menuitem"][data-action]').forEach((item) => {
      const action = item.getAttribute('data-action') || '';
      const enabled = Boolean(state[action]);
      item.setAttribute('aria-disabled', enabled ? 'false' : 'true');
      item.classList.toggle('is-disabled', !enabled);
      item.setAttribute('tabindex', enabled ? '-1' : '-1');
    });
  }

  function showDayContextMenu(event, dateId) {
    const menu = document.getElementById('calendar_day_context_menu');
    const menuHead = document.getElementById('calendar_day_context_menu_head');
    const targetCell = document.querySelector(`.datagrid_month_cell[data-id="${dateId}"]`);

    if (!menu || !menuHead || !targetCell) {
      return;
    }

    bindDayContextMenuHandlers(menu);

    event.preventDefault();
    event.stopPropagation();

    activeContextMenuDateId = dateId;
    activeContextMenuOpenMode = event.type === 'contextmenu' ? 'pointer' : 'keyboard';
    menuHead.textContent = dateId;
    menu.classList.remove('hidden');
    applyDayContextMenuState(menu, dateId);

    const rect = targetCell.getBoundingClientRect();
    const pointerX = typeof event.clientX === 'number' && event.clientX > 0
      ? event.clientX
      : rect.left + Math.floor(rect.width / 2);
    const pointerY = typeof event.clientY === 'number' && event.clientY > 0
      ? event.clientY
      : rect.top + Math.floor(rect.height / 2);
    let x = pointerX;
    let y = pointerY;

    const menuWidth = menu.offsetWidth || 160;
    const menuHeight = menu.offsetHeight || 180;
    const maxX = window.innerWidth - menuWidth - 8;
    const maxY = window.innerHeight - menuHeight - 8;

    if (x > maxX) x = maxX;
    if (y > maxY) y = maxY;
    if (x < 8) x = 8;
    if (y < 8) y = 8;

    const currentAnchor = menu.parentElement;
    if (currentAnchor && currentAnchor.classList && currentAnchor.classList.contains('context-menu-anchor')) {
      currentAnchor.classList.remove('context-menu-anchor');
    }

    activeContextMenuAnchorCell = targetCell;
    targetCell.classList.add('context-menu-anchor');
    const pointerPrefersRight = pointerX >= rect.left + (rect.width / 2);
    const pointerPrefersTop = pointerY >= rect.top + (rect.height / 2);
    menu.classList.toggle('context-menu-align-right', x >= maxX || pointerPrefersRight);
    menu.classList.toggle('context-menu-align-top', y >= maxY || pointerPrefersTop);
    targetCell.appendChild(menu);

    const firstItem = Array.from(menu.querySelectorAll('[role="menuitem"][data-action]')).find((item) => item.getAttribute('aria-disabled') !== 'true');
    const openedWithPointer = activeContextMenuOpenMode === 'pointer';
    if (firstItem && !openedWithPointer) {
      firstItem.focus();
      return;
    }

    if (openedWithPointer) {
      targetCell.focus({ preventScroll: true });
    }
  }

  function hideDayContextMenu() {
    const menu = document.getElementById('calendar_day_context_menu');
    const anchorCell = activeContextMenuAnchorCell;
    activeContextMenuDateId = '';
    activeContextMenuAnchorCell = null;
    activeContextMenuOpenMode = 'keyboard';

    if (menu) {
      menu.classList.add('hidden');
      const currentAnchor = menu.parentElement;
      if (currentAnchor && currentAnchor.classList && currentAnchor.classList.contains('context-menu-anchor')) {
        currentAnchor.classList.remove('context-menu-anchor');
      }
    }

    if (anchorCell instanceof HTMLElement) {
      anchorCell.focus();
    }
  }

  async function handleDayContextMenuAction(action) {
    const dateId = activeContextMenuDateId;
    const menu = document.getElementById('calendar_day_context_menu');
    if (menu) {
      const item = menu.querySelector(`[role="menuitem"][data-action="${action}"]`);
      if (item && item.getAttribute('aria-disabled') === 'true') {
        return;
      }
    }

    hideDayContextMenu();
    if (!dateId) {
      return;
    }

    try {
      switch (action) {
        case 'open':
          openModalForDate(dateId);
          break;
        case 'copy':
          copyDayEntries(dateId);
          break;
        case 'paste':
          await pasteDayEntries(dateId);
          break;
        case 'delete':
          await deleteDayEntries(dateId);
          break;
        default:
          break;
      }
    } catch (error) {
      console.error('[Calendar Context Menu] Action failed', { action, dateId, error });
    }
  }

  function resolveShortcutTargetDate() {
    const active = document.activeElement;
    const focusedCell = active && active.closest ? active.closest('.datagrid_month_cell') : null;
    if (focusedCell) {
      const dateId = focusedCell.getAttribute('data-id');
      if (dateId) {
        return dateId;
      }
    }

    const tabbableCell = document.querySelector('.datagrid_month_cell[tabindex="0"]');
    if (tabbableCell) {
      const dateId = tabbableCell.getAttribute('data-id');
      if (dateId) {
        return dateId;
      }
    }

    return window._CALENDAR_LAST_GRID_FOCUS_DATE || '';
  }

  function getDayEntriesFromCell(dateId) {
    const cell = document.querySelector(`.datagrid_month_cell[data-id="${dateId}"]`);
    if (!cell) {
      return [];
    }

    try {
      const raw = cell.getAttribute('data-work-entries') || '[]';
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      coreLog('[Calendar Context Menu] Failed to parse day entries', { dateId, error });
      return [];
    }
  }

  function normalizeEntriesForSave(entries) {
    if (!Array.isArray(entries)) {
      return [];
    }

    return entries
      .map(entry => {
        const siteId = (entry?.site_id ?? entry?.s ?? '').toString().trim();
        const siteName = (entry?.site_name ?? entry?.n ?? '').toString().trim();
        const regular = parseFloat(entry?.regular_hours ?? entry?.r ?? 0) || 0;
        const overtime = parseFloat(entry?.overtime_hours ?? entry?.o ?? 0) || 0;
        const loa = parseFloat(entry?.living_out_allowance ?? entry?.l ?? 0) || 0;
        const travel = parseFloat(entry?.travel_hours ?? entry?.t ?? 0) || 0;
        const hours = parseFloat(entry?.hours ?? entry?.h ?? (regular + overtime)) || 0;

        if (!siteId || (hours + loa + travel) <= 0) {
          return null;
        }

        return {
          site_id: siteId,
          site_name: siteName,
          hours,
          regular_hours: regular,
          overtime_hours: overtime,
          living_out_allowance: loa,
          travel_hours: travel,
        };
      })
      .filter(Boolean);
  }

  function formatStatusDateLabel(dateId) {
    if (!dateId || typeof dateId !== 'string') {
      return 'selected date';
    }

    const parts = dateId.split('-');
    if (parts.length !== 3) {
      return dateId;
    }

    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10);
    const day = parseInt(parts[2], 10);
    if (!year || !month || !day) {
      return dateId;
    }

    const localDate = new Date(year, month - 1, day);
    if (Number.isNaN(localDate.getTime())) {
      return dateId;
    }

    return formatCalendarLocaleDate(localDate, {
      weekday: 'short',
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }

  /**
   * Check if a date cell is locked for editing.
   * @param {string} dateId - Date ID in YYYY-MM-DD format
   * @returns {boolean} - True if locked, false if editable
   */
  function isDateLocked(dateId) {
    const cell = document.querySelector(`.datagrid_month_cell[data-id="${dateId}"]`);
    // Check both the data attribute and CSS class for robustness
    return cell && (cell.getAttribute('data-locked') === '1' || cell.classList.contains('datagrid_month_cell_locked'));
  }

  function formatLockedDateMessage(dateLabel, daysRemaining = null) {
    if (typeof daysRemaining === 'number' && Number.isFinite(daysRemaining) && daysRemaining > 0) {
      const unit = daysRemaining === 1
        ? calendarI18n('CALENDAR_DAY_SINGULAR', 'day')
        : calendarI18n('CALENDAR_DAYS', 'days');
      return calendarI18nFormat(
        'CALENDAR_LOCKED_CANNOT_EDIT_GRACE',
        'Cannot edit {date} - this period is locked. You can edit entries for {days} more {unit}.',
        {
          date: dateLabel,
          days: String(daysRemaining),
          unit,
        }
      );
    }

    return calendarI18nFormat(
      'CALENDAR_LOCKED_CANNOT_EDIT',
      'Cannot edit {date} - this period is locked',
      { date: dateLabel }
    );
  }

  /**
   * Show status message for locked date.
   * @param {string} dateId - Date ID in YYYY-MM-DD format
   */
  function showLockedDateMessage(dateId) {
    const dateLabel = formatStatusDateLabel(dateId);
    const grid = document.getElementById('calendar-grid');
    let message = formatLockedDateMessage(dateLabel);

    // Try to show remaining edit window from lockBoundary
    if (grid) {
      const lockBoundary = grid.dataset.lockboundary || '';
      if (lockBoundary) {
        try {
          const today = new Date();
          const lockDate = new Date(lockBoundary + 'T00:00:00');
          const daysRemaining = Math.ceil((lockDate - today) / (1000 * 60 * 60 * 24));

          if (daysRemaining > 0) {
            message = formatLockedDateMessage(dateLabel, daysRemaining);
          } else {
            message = formatLockedDateMessage(dateLabel);
          }
        } catch {
          // Silently fall back to default message if date calculation fails
        }
      }
    }

    if (window.PayCalCore && typeof window.PayCalCore.updateStatusMessage === 'function') {
      PayCalCore.updateStatusMessage(message, 'error', 3000);
    }
  }

  function copyDayEntries(dateId) {
    const dateLabel = formatStatusDateLabel(dateId);
    const entries = normalizeEntriesForSave(getDayEntriesFromCell(dateId));
    if (entries.length === 0) {
      coreLog('[Calendar Context Menu] Copy aborted - day is blank', { dateId });
      PayCalCore.updateStatusMessage(calendarI18nFormat('CALENDAR_NO_ENTRIES_TO_COPY_ON', 'No entries to copy on {dateLabel}', { dateLabel }), 'info', 2000);
      return;
    }

    calendarClipboard = entries;
    coreLog('[Calendar Context Menu] Copied day entries', { dateId, count: entries.length });
    PayCalCore.updateStatusMessage(
      calendarI18nFormat('CALENDAR_COPIED_ENTRIES_FROM', 'Copied {count} entry/entries from {dateLabel}', {
        count: entries.length,
        dateLabel,
      }),
      'copy',
      3000
    );
  }

  async function pasteDayEntries(dateId) {
    const dateLabel = formatStatusDateLabel(dateId);
    coreLog('[Calendar Context Menu] Paste requested', { dateId });

    // Check if date is locked before pasting
    if (isDateLocked(dateId)) {
      showLockedDateMessage(dateId);
      return;
    }

    const entries = calendarClipboard ? [...calendarClipboard] : [];
    if (entries.length === 0) {
      coreLog('[Calendar Context Menu] Paste skipped - clipboard empty');
      PayCalCore.updateStatusMessage(calendarI18nFormat('CALENDAR_CLIPBOARD_EMPTY_FOR', 'Clipboard is empty for {dateLabel}', { dateLabel }), 'info', 2000);
      return;
    }

    try {
      PayCalCore.updateStatusMessage(calendarI18nFormat('CALENDAR_PASTING_TO', 'Pasting to {dateLabel}...', { dateLabel }), 'paste', 0);
      await saveEntriesForDate(dateId, entries);
      coreLog('[Calendar Context Menu] Pasted day entries', { dateId, count: entries.length });
      PayCalCore.updateStatusMessage(
        calendarI18nFormat('CALENDAR_PASTED_ENTRIES_TO', 'Pasted {count} entry/entries to {dateLabel}', {
          count: entries.length,
          dateLabel,
        }),
        'paste',
        3000
      );
    } catch (error) {
      console.error('[Calendar Context Menu] Paste failed during save', { dateId, error });
      PayCalCore.updateStatusMessage(
        calendarI18nFormat('CALENDAR_PASTE_FAILED_FOR', 'Paste failed for {dateLabel}: {error}', {
          dateLabel,
          error: error.message || calendarI18n('CALENDAR_UNKNOWN_ERROR', 'Unknown error'),
        }),
        'error',
        4000
      );
      throw error;
    }
  }

  async function deleteDayEntries(dateId) {
    const dateLabel = formatStatusDateLabel(dateId);

    // Check if date is locked before deleting
    if (isDateLocked(dateId)) {
      showLockedDateMessage(dateId);
      return;
    }

    PayCalCore.updateStatusMessage(calendarI18nFormat('CALENDAR_DELETING_FOR', 'Deleting {dateLabel}...', { dateLabel }), 'delete', 0);
    try {
      await saveEntriesForDate(dateId, []);
      PayCalCore.updateStatusMessage(calendarI18nFormat('CALENDAR_ENTRIES_DELETED_FOR', 'Entries deleted for {dateLabel}', { dateLabel }), 'delete', 3000);
    } catch (error) {
      console.error('[Calendar Delete] Delete failed', { dateId, error });
      PayCalCore.updateStatusMessage(
        calendarI18nFormat('CALENDAR_DELETE_FAILED_FOR', 'Delete failed for {dateLabel}: {error}', {
          dateLabel,
          error: error.message || calendarI18n('CALENDAR_UNKNOWN_ERROR', 'Unknown error'),
        }),
        'error',
        4000
      );
      throw error;
    }
  }


  /**
   * Navigate focus up one row (7 cells up).
   * @param {HTMLElement} currentCell - The current cell
   */
  function navigateGridCellUp(currentCell) {
    const grid = currentCell.closest('.datagrid_month_grid');
    if (!grid) return;

    const cells = Array.from(grid.querySelectorAll('.datagrid_month_cell'));
    const currentIndex = cells.indexOf(currentCell);
    if (currentIndex < 0) return;

    const targetIndex = currentIndex - 7;
    if (targetIndex >= 0 && cells[targetIndex]) {
      setGridCellFocusState(cells[targetIndex], true);
    }
  }

  /**
   * Navigate focus down one row (7 cells down).
   * @param {HTMLElement} currentCell - The current cell
   */
  function navigateGridCellDown(currentCell) {
    const grid = currentCell.closest('.datagrid_month_grid');
    if (!grid) return;

    const cells = Array.from(grid.querySelectorAll('.datagrid_month_cell'));
    const currentIndex = cells.indexOf(currentCell);
    if (currentIndex < 0) return;

    const targetIndex = currentIndex + 7;
    if (targetIndex < cells.length && cells[targetIndex]) {
      setGridCellFocusState(cells[targetIndex], true);
    }
  }

  /**
   * Navigate focus left one cell.
   * @param {HTMLElement} currentCell - The current cell
   */
  function navigateGridCellLeft(currentCell) {
    const grid = currentCell.closest('.datagrid_month_grid');
    if (!grid) return;

    const cells = Array.from(grid.querySelectorAll('.datagrid_month_cell'));
    const currentIndex = cells.indexOf(currentCell);
    if (currentIndex <= 0) return;

    const targetCell = cells[currentIndex - 1];
    if (targetCell) {
      setGridCellFocusState(targetCell, true);
    }
  }

  /**
   * Navigate focus right one cell.
   * @param {HTMLElement} currentCell - The current cell
   */
  function navigateGridCellRight(currentCell) {
    const grid = currentCell.closest('.datagrid_month_grid');
    if (!grid) return;

    const cells = Array.from(grid.querySelectorAll('.datagrid_month_cell'));
    const currentIndex = cells.indexOf(currentCell);
    if (currentIndex < 0 || currentIndex >= cells.length - 1) return;

    const targetCell = cells[currentIndex + 1];
    if (targetCell) {
      setGridCellFocusState(targetCell, true);
    }
  }

  /**
   * Move focus to first day cell in the active month grid.
   * @param {HTMLElement} currentCell - The current focused day cell
   */
  function navigateGridCellHome(currentCell) {
    const grid = currentCell.closest('.datagrid_month_grid');
    if (!grid) return;

    const cells = Array.from(grid.querySelectorAll('.datagrid_month_cell'));
    if (cells.length === 0) return;

    const firstCell = cells[0];
    setGridCellFocusState(firstCell, true);
  }

  /**
   * Move focus to last day cell in the active month grid.
   * @param {HTMLElement} currentCell - The current focused day cell
   */
  function navigateGridCellEnd(currentCell) {
    const grid = currentCell.closest('.datagrid_month_grid');
    if (!grid) return;

    const cells = Array.from(grid.querySelectorAll('.datagrid_month_cell'));
    if (cells.length === 0) return;

    const lastCell = cells[cells.length - 1];
    setGridCellFocusState(lastCell, true);
  }

  function isCalendarTextInputTarget(target) {
    const tagName = (target && target.tagName ? target.tagName : '').toUpperCase();
    return tagName === 'INPUT' || tagName === 'TEXTAREA' || !!(target && target.isContentEditable);
  }

  function isCalendarModalOpen() {
    const workModal = document.getElementById('calendar-modal');
    if (workModal && workModal.open) {
      return true;
    }

    const pickerModal = document.getElementById('modal_cal_picker');
    if (pickerModal && pickerModal.open) {
      return true;
    }

    return false;
  }

  function isDatePickerOpen() {
    const pickerModal = document.getElementById('modal_cal_picker');
    return !!(pickerModal && pickerModal.open);
  }

  function isCalendarPageContext() {
    return !!document.getElementById('calendar-v2-root') && !!document.getElementById('calendar-grid');
  }

  function setCalendarScreenMode(mode) {
    const body = document.body;
    const pageHeader = document.getElementById('page_header');
    const pageFooter = document.getElementById('page_footer');
    const controls = document.querySelector('#calendar-grid .datagrid_controls');
    const weekHeaders = document.querySelector('#calendar-grid .calendar-v2-weekday-headers');
    const dayNumberNodes = document.querySelectorAll('#calendar-grid .datagrid_month_cell_header');
    const hideChrome = mode === 'no_nav' || mode === 'no_number';

    if (!controls || !weekHeaders || dayNumberNodes.length === 0) {
      return;
    }

    if (body) {
      body.classList.toggle('calendar-screenmode-minimal', hideChrome);
    }

    if (pageHeader) {
      pageHeader.classList.toggle('hidden', hideChrome);
    }
    if (pageFooter) {
      pageFooter.classList.toggle('hidden', hideChrome);
    }

    controls.classList.toggle('hidden', mode !== 'normal');
    weekHeaders.classList.toggle('hidden', mode === 'no_sub_headers' || mode === 'no_nav' || mode === 'no_number');

    dayNumberNodes.forEach((node) => {
      node.classList.toggle('hidden', mode === 'no_number');
    });

    calendarScreenMode = mode;
  }

  function cycleCalendarScreenMode(reverseDirection) {
    if (reverseDirection) {
      switch (calendarScreenMode) {
        case 'normal':
          setCalendarScreenMode('no_number');
          break;
        case 'no_number':
          setCalendarScreenMode('no_nav');
          break;
        case 'no_nav':
        default:
          setCalendarScreenMode('normal');
          break;
      }
      return;
    }

    switch (calendarScreenMode) {
      case 'normal':
        setCalendarScreenMode('no_nav');
        break;
      case 'no_nav':
        setCalendarScreenMode('no_number');
        break;
      case 'no_number':
      default:
        setCalendarScreenMode('normal');
        break;
    }
  }

  /**
   * Attach global keyboard handlers for month navigation.
  * [ / ] and PageUp / PageDown for month navigation.
   */
  function attachGridKeyboardNavigation() {
    if (gridKeyboardNavigationBound) {
      return;
    }
    gridKeyboardNavigationBound = true;

    document.addEventListener('keydown', function(event) {
      if (isCalendarPageContext() && event.shiftKey && !calendarShiftKeyHeld) {
        calendarShiftKeyHeld = true;
        const activeCell = document.querySelector('#calendar-grid .datagrid_month_grid .datagrid_month_cell[tabindex="0"]')
          || (document.activeElement && document.activeElement.closest ? document.activeElement.closest('.datagrid_month_cell') : null);
        calendarShiftAnchorDateId = activeCell ? (activeCell.getAttribute('data-id') || '') : '';
        refreshShiftRangeSelectionOnActiveCell();
      }

      if (event.defaultPrevented) {
        return;
      }

      if (event.key === 'Home' || event.key === 'End') {
        if (!isCalendarPageContext() || isCalendarTextInputTarget(event.target)) {
          return;
        }

        if (event.altKey || event.ctrlKey || event.metaKey) {
          return;
        }

        const active = document.activeElement;
        const activeCell = active && active.closest ? active.closest('.datagrid_month_cell') : null;
        if (!activeCell) {
          return;
        }

        event.preventDefault();
        event.stopPropagation();
        if (event.key === 'Home') {
          navigateGridCellHome(activeCell);
        } else {
          navigateGridCellEnd(activeCell);
        }
        return;
      }

      if (
        event.code === 'Backslash'
        && !event.shiftKey
        && !event.altKey
        && !event.metaKey
        && !event.ctrlKey
      ) {
        if (!isCalendarPageContext()) {
          return;
        }

        if (isCalendarTextInputTarget(event.target)) {
          return;
        }

        event.preventDefault();
        if (isDatePickerOpen()) {
          if (window.PayCalCore && typeof window.PayCalCore.closeModal === 'function') {
            window.PayCalCore.closeModal('modal_cal_picker', calendarI18n('DATE_PICKER', 'Date Picker'));
          }
          return;
        }

        if (isCalendarModalOpen()) {
          return;
        }

        const root = document.getElementById('calendar-v2-root');
        const activeView = root?.dataset.activeView || 'month';
        let pickerBtn = document.getElementById('cal_picker_button');
        if (activeView === 'week') {
          pickerBtn = document.getElementById('cal_week_picker_button');
        } else if (activeView === 'pay_period') {
          pickerBtn = document.getElementById('cal_payperiod_picker_button');
        }
        if (pickerBtn) {
          pickerBtn.click();
        }
        return;
      }

      if (
        event.key === '/'
        && !event.altKey
        && !event.metaKey
        && !event.ctrlKey
      ) {
        if (!isCalendarPageContext()) {
          return;
        }

        if (isCalendarTextInputTarget(event.target) || isCalendarModalOpen()) {
          return;
        }

        event.preventDefault();
        cycleCalendarScreenMode(false);
        return;
      }

      const isCopy = (event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'c';
      const isPaste = (event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'v';
      if (isCopy || isPaste) {
        const isTextInput = isCalendarTextInputTarget(event.target);
        if (!isTextInput) {
          const targetDate = resolveShortcutTargetDate();
          if (targetDate) {
            event.preventDefault();
            if (isCopy) {
              copyDayEntries(targetDate);
            } else {
              void pasteDayEntries(targetDate).catch(error => {
                console.error('[Calendar Keyboard] Paste failed', { targetDate, error });
              });
            }
            return;
          }
        }
      }

      // [ for previous month
      if (
        event.key === '['
        && !event.altKey
        && !event.metaKey
        && !event.ctrlKey
      ) {
        if (!isCalendarPageContext() || isCalendarTextInputTarget(event.target)) {
          return;
        }

        if (isCalendarModalOpen()) {
          return;
        }

        event.preventDefault();
        const root = document.getElementById('calendar-v2-root');
        const activeView = root?.dataset.activeView || 'month';
        const prevSelector = calendarRangeActionSelector(activeView, 'prev');
        const prevBtn = document.querySelector(prevSelector);
        coreLog('[Calendar Keyboard] [ pressed, triggering previous range');
        if (prevBtn) {
          prevBtn.click();
        }
      }
      // ] for next range
      else if (
        event.key === ']'
        && !event.altKey
        && !event.metaKey
        && !event.ctrlKey
      ) {
        if (!isCalendarPageContext() || isCalendarTextInputTarget(event.target)) {
          return;
        }

        if (isCalendarModalOpen()) {
          return;
        }

        event.preventDefault();
        const root = document.getElementById('calendar-v2-root');
        const activeView = root?.dataset.activeView || 'month';
        const nextSelector = calendarRangeActionSelector(activeView, 'next');
        const nextBtn = document.querySelector(nextSelector);
        coreLog('[Calendar Keyboard] ] pressed, triggering next range');
        if (nextBtn) {
          nextBtn.click();
        }
      }
      // Escape to close modal
      else if (event.key === 'Escape') {
        const modal = document.getElementById('calendar-modal');
        if (modal && modal.open) {
          event.preventDefault();
          closeModal();
        }
      }
    });

    document.addEventListener('keyup', function(event) {
      if (event.key !== 'Shift' || !calendarShiftKeyHeld) {
        return;
      }

      calendarShiftKeyHeld = false;
      calendarShiftAnchorDateId = '';
    });
  }

  // =========================================================================
  // INTERACTION LOGGING
  // =========================================================================

  /**
   * Log a row interaction event.
   * @param {string} action - The action (click, enter, etc.)
   * @param {string} dateId - The date identifier from row data-id
   */
  function logRowInteraction(action, dateId) {
    coreLog(`[Calendar Row Interaction] Action: ${action}, Date: ${dateId}`);
  }

  // =========================================================================
  // MODAL DIALOG
  // =========================================================================

  function getFocusableElements(container) {
    if (!container) {
      return [];
    }

    const selector = [
      'a[href]',
      'button:not([disabled])',
      'input:not([disabled]):not([type="hidden"])',
      'select:not([disabled])',
      'textarea:not([disabled])',
      '[tabindex]:not([tabindex="-1"])'
    ].join(',');

    return Array.from(container.querySelectorAll(selector)).filter((el) => {
      if (!(el instanceof HTMLElement)) {
        return false;
      }

      return el.offsetParent !== null;
    });
  }

  function trapFocusWithin(container, event) {
    if (event.key !== 'Tab') {
      return false;
    }

    const focusables = getFocusableElements(container);
    if (focusables.length === 0) {
      event.preventDefault();
      return true;
    }

    const first = focusables[0];
    const last = focusables[focusables.length - 1];
    const active = document.activeElement;

    if (event.shiftKey && (active === first || !container.contains(active))) {
      event.preventDefault();
      last.focus();
      return true;
    }

    if (!event.shiftKey && (active === last || !container.contains(active))) {
      event.preventDefault();
      first.focus();
      return true;
    }

    return false;
  }

  /**
   * Attach event handlers to modal dialog controls.
   */
  function attachModalHandlers() {
    const modal = document.getElementById('calendar-modal');
    modalLog('[Calendar Modal] Found modal element:', !!modal);
    if (!modal) {
      const debugEl = document.getElementById('calendar-debug-indicator');
      if (debugEl) debugEl.textContent += ' | No modal';
      return;
    }

    const closeBtn = modal.querySelector('.calendar_modal_close');
    const actionBtns = modal.querySelectorAll('[data-action]');

    modalLog('[Calendar Modal] Close button:', !!closeBtn, 'Action buttons:', actionBtns.length);

    if (closeBtn) {
      // Let core delegated [data-dialog-close] handling close this dialog.
    }
    
    if (actionBtns && actionBtns.length > 0) {
      actionBtns.forEach(btn => {
        if (btn) {
          btn.addEventListener('click', handleModalAction);
        }
      });
    }

    modal.addEventListener('keydown', function(event) {
      if (!modal.open) {
        return;
      }

      if ('Escape' === event.key) {
        event.preventDefault();
        closeModal();
        return;
      }

      if (trapFocusWithin(modal, event)) {
        return;
      }

      if ('Enter' === event.key) {
        const target = event.target;
        const isButton = !!(target && target.closest && target.closest('button'));
        const isTextarea = !!(target && 'TEXTAREA' === target.tagName);
        const isSelect = !!(target && 'SELECT' === target.tagName);

        if (!isButton && !isTextarea && !isSelect) {
          event.preventDefault();
          setModalElementsDisabled(true);
          handleWorkEntrySave();
        }
      }
    });

    attachMonthNavigationHandlers();
  }

  /**
   * Attach handlers to month navigation buttons.
   */
  function attachMonthNavigationHandlers() {
    const prevBtn = document.querySelector('.calendar_range_controls[data-calendar-range-controls="month"] [data-action="prev-month"]');
    const nextBtn = document.querySelector('.calendar_range_controls[data-calendar-range-controls="month"] [data-action="next-month"]');
    const openPickerBtn = document.getElementById('cal_picker_button');
    const pickerDialog = document.getElementById('modal_cal_picker');
    const datePickerGoBtn = document.getElementById('date_picker_go_btn');
    const yearInput = document.getElementById('cal_year_input');
    const pickerMinYear = yearInput ? parseInt(yearInput.getAttribute('data-min-year') || '', 10) : Number.NaN;
    const pickerMaxYear = yearInput ? parseInt(yearInput.getAttribute('data-max-year') || '', 10) : Number.NaN;
    const monthButtons = Array.from(document.querySelectorAll('#cal_menu_right button'));

    const normalizeMonthNavA11yHints = () => {
      if (prevBtn) {
        const prevLabel = calendarI18n('DATAGRID_PREVIOUS_MONTH_ARIA', prevBtn.getAttribute('aria-label') || '');
        if (prevLabel !== '') {
          prevBtn.setAttribute('aria-label', prevLabel);
        }
        prevBtn.setAttribute('aria-keyshortcuts', '[ PageUp');
      }

      if (nextBtn) {
        const nextLabel = calendarI18n('DATAGRID_NEXT_MONTH_ARIA', nextBtn.getAttribute('aria-label') || '');
        if (nextLabel !== '') {
          nextBtn.setAttribute('aria-label', nextLabel);
        }
        nextBtn.setAttribute('aria-keyshortcuts', '] PageDown');
      }
    };

    normalizeMonthNavA11yHints();

    const announceCurrentMonth = () => {
      const statusEl = document.getElementById('calendar-month-status');
      const pickerBtn = document.getElementById('cal_picker_button');
      if (!statusEl || !pickerBtn) {
        return;
      }

      const monthLabel = (pickerBtn.textContent || '').trim();
      if (monthLabel !== '') {
        statusEl.textContent = calendarI18nFormat(
          'CALENDAR_MONTH_UPDATED_TO',
          'Calendar month updated to {month}.',
          { month: monthLabel }
        );
      }
    };

    const reinitializeCalendarAfterPartialRefresh = async (options = {}) => {
      const grids = getCalendarGrids();
      const activeGrid = getActiveCalendarGrid() || document.getElementById('calendar-grid');

      initCalendarViewToggle();
      mountCalendarViewToolbar();

      grids.forEach((calendarGrid) => {
        attachGridCellHandlers(calendarGrid);
        attachDayContextMenuHandlers(calendarGrid);
        refreshAllCalendarDayTooltips(calendarGrid);
        calendarGrid.querySelectorAll('.datagrid_month_cell[data-id]').forEach((cell) => {
          updateCalendarEarningsBadges(cell);
        });
        ensureVisibleDailyEarningsLoaded(calendarGrid);
      });

      attachGridKeyboardNavigation();
      void ensureCalendarSiteCatalog();
      attachMonthNavigationHandlers();
      setCalendarScreenMode(calendarScreenMode);

      if (PayCalCryptoState.hasDek) {
        for (const calendarGrid of grids) {
          await hydrateEncryptedCalendarGrid(calendarGrid);
        }
      } else if (activeGrid) {
        hideEncryptedCalendarGrid(activeGrid);
      }

      const focusDay = options.focusDay;
      if (typeof focusDay === 'string' && focusDay !== '' && activeGrid) {
        setTimeout(() => focusTargetDay(focusDay, activeGrid), 25);
      }
    };

    const refreshCalendarPage = async (targetUrl, options = {}) => {
      try {
        const response = await fetch(targetUrl, {
          credentials: 'include',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const replaceIds = ['calendar-v2-root', 'modal_cal_picker', 'modal_cal_week_picker', 'modal_cal_payperiod_picker'];

        for (const elementId of replaceIds) {
          const nextEl = doc.getElementById(elementId);
          const currentEl = document.getElementById(elementId);
          if (nextEl && currentEl) {
            currentEl.replaceWith(nextEl);
          } else if (nextEl && !currentEl) {
            document.body.appendChild(nextEl);
          }
        }

        window.history.pushState({}, '', targetUrl);
        await reinitializeCalendarAfterPartialRefresh(options);
      } catch (error) {
        console.error('[Calendar Nav] Async refresh failed, falling back to full navigation', { error, targetUrl });
        window.location.href = targetUrl;
      }
    };

    const refreshCalendarMonth = async (year, month) => {
      if (!year || !month) {
        console.error('[Calendar Nav] Missing year or month for refresh', { year, month });
        return;
      }

      const monthStr = String(month).padStart(2, '0');
      const storedDay = window._CALENDAR_FOCUSED_DAY;
      const currentRoot = document.getElementById('calendar-v2-root');
      const selectedUserUUID = (currentRoot?.dataset?.calendarUserUuid || '').trim();
      const urlBuilder = new URL(`/${year}-${monthStr}`, window.location.origin);
      if (selectedUserUUID !== '') {
        urlBuilder.searchParams.set('user_uuid', selectedUserUUID);
      }
      urlBuilder.searchParams.set('view', 'month');
      const targetUrl = urlBuilder.pathname + urlBuilder.search;
      const targetDay = storedDay ? `${year}-${monthStr}-${String(storedDay).padStart(2, '0')}` : `${year}-${monthStr}-01`;

      await refreshCalendarPage(targetUrl, { focusDay: targetDay });
      announceCurrentMonth();
    };

    const getSelectedUserUUID = () => {
      return (document.getElementById('calendar-v2-root')?.dataset?.calendarUserUuid || '').trim();
    };

    const buildWeekCalendarUrl = (anchorYmd) => {
      const parts = String(anchorYmd || '').split('-');
      if (parts.length !== 3) {
        return '';
      }

      const urlBuilder = new URL(`/${parts[0]}-${parts[1]}`, window.location.origin);
      urlBuilder.searchParams.set('week_start', anchorYmd);
      urlBuilder.searchParams.set('view', 'week');
      const selectedUserUUID = getSelectedUserUUID();
      if (selectedUserUUID !== '') {
        urlBuilder.searchParams.set('user_uuid', selectedUserUUID);
      }

      return urlBuilder.pathname + urlBuilder.search;
    };

    const buildPayPeriodCalendarUrl = (anchorYmd) => {
      const parts = String(anchorYmd || '').split('-');
      if (parts.length !== 3) {
        return '';
      }

      const activeGrid = document.getElementById('calendar-payperiod-grid');
      const canonicalAnchor = String(activeGrid?.dataset?.rangeAnchor || anchorYmd).trim() || anchorYmd;
      const canonicalParts = canonicalAnchor.split('-');
      const monthPath = canonicalParts.length === 3
        ? `${canonicalParts[0]}-${canonicalParts[1]}`
        : `${parts[0]}-${parts[1]}`;

      const urlBuilder = new URL(`/${monthPath}`, window.location.origin);
      urlBuilder.searchParams.set('pay_period_start', canonicalAnchor);
      urlBuilder.searchParams.set('view', 'pay_period');
      const selectedUserUUID = getSelectedUserUUID();
      if (selectedUserUUID !== '') {
        urlBuilder.searchParams.set('user_uuid', selectedUserUUID);
      }

      return urlBuilder.pathname + urlBuilder.search;
    };

    const navigateToWeek = (anchorYmd) => {
      const targetUrl = buildWeekCalendarUrl(anchorYmd);
      if (targetUrl === '') {
        return;
      }

      void refreshCalendarPage(targetUrl, { focusDay: anchorYmd });
    };

    const navigateToPayPeriod = (anchorYmd) => {
      const grid = document.getElementById('calendar-payperiod-grid');
      const resolvedAnchor = String(grid?.dataset?.rangeAnchor || anchorYmd || '').trim() || anchorYmd;
      const targetUrl = buildPayPeriodCalendarUrl(resolvedAnchor);
      if (targetUrl === '') {
        return;
      }

      void refreshCalendarPage(targetUrl, { focusDay: resolvedAnchor });
    };

    const navigateToMonth = (year, month) => {
      void refreshCalendarMonth(year, month);
    };

    const getSelectedMonthButton = () => document.querySelector('#cal_menu_right button.cal_menu_selected');

    const getSelectedYearValue = () => {
      if (yearInput) {
        const parsed = parseInt((yearInput.value || '').trim(), 10);
        if (Number.isFinite(parsed)) {
          return parsed;
        }
      }

      return new Date().getFullYear();
    };

    const normalizePickerYear = (year) => {
      let normalized = year;
      if (Number.isFinite(pickerMinYear)) {
        normalized = Math.max(normalized, pickerMinYear);
      }
      if (Number.isFinite(pickerMaxYear)) {
        normalized = Math.min(normalized, pickerMaxYear);
      }

      return normalized;
    };

    const setSelectedYearValue = (year, focusInput = false) => {
      if (!yearInput || !Number.isFinite(year)) {
        return false;
      }

      const normalized = normalizePickerYear(year);
      yearInput.value = String(normalized);
      updateDatePickerGoLabel();
      if (focusInput && typeof yearInput.focus === 'function') {
        yearInput.focus();
      }

      return true;
    };

    const updateDatePickerGoLabel = () => {
      if (!datePickerGoBtn) {
        return;
      }

      const selectedMonthBtn = getSelectedMonthButton();
      if (!selectedMonthBtn) {
        datePickerGoBtn.textContent = calendarI18n('VIEW', 'View');
        return;
      }

      const year = String(getSelectedYearValue());
      const monthLabel = (selectedMonthBtn.textContent || '').trim();
      datePickerGoBtn.textContent = `${calendarI18n('VIEW', 'View')} ${year}-${monthLabel}`;
    };

    const selectPickerButton = (button, buttons) => {
      if (!button) {
        return;
      }

      buttons.forEach((otherButton) => otherButton.classList.remove('cal_menu_selected'));
      button.classList.add('cal_menu_selected');
      updateDatePickerGoLabel();
    };

    const moveYearSelection = (direction) => {
      return setSelectedYearValue(getSelectedYearValue() + direction, true);
    };

    const moveMonthSelection = (direction) => {
      if (monthButtons.length === 0) {
        return false;
      }

      const selectedMonthBtn = getSelectedMonthButton();
      const currentIndex = Math.max(monthButtons.indexOf(selectedMonthBtn), 0);
      const targetIndex = currentIndex + direction;
      if (targetIndex < 0 || targetIndex >= monthButtons.length) {
        return false;
      }

      const nextButton = monthButtons[targetIndex];
      selectPickerButton(nextButton, monthButtons);
      nextButton.focus();
      return true;
    };

    const submitDatePickerSelection = () => {
      const selectedMonthBtn = getSelectedMonthButton();
      if (!selectedMonthBtn) {
        return;
      }

      const year = String(getSelectedYearValue());
      const month = selectedMonthBtn.getAttribute('data-month');

      if (window.PayCalCore && typeof window.PayCalCore.closeModal === 'function') {
        window.PayCalCore.closeModal('modal_cal_picker', calendarI18n('DATE_PICKER', 'Date Picker'));
      }

      navigateToMonth(year, month);
    };

    coreLog('[Calendar Nav] Buttons found - Prev:', !!prevBtn, 'Next:', !!nextBtn, 'Dialog:', !!openPickerBtn);

    if (prevBtn && typeof prevBtn.addEventListener === 'function') {
      prevBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        coreLog('[Calendar Nav] Prev clicked');
        
        const year = this.getAttribute('data-year');
        const month = this.getAttribute('data-month');
        coreLog('[Calendar Nav] Button attributes - year:', year, 'month:', month);

        navigateToMonth(year, month);
      });
    }

    if (nextBtn && typeof nextBtn.addEventListener === 'function') {
      nextBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        coreLog('[Calendar Nav] Next clicked');
        
        const year = this.getAttribute('data-year');
        const month = this.getAttribute('data-month');
        coreLog('[Calendar Nav] Button attributes - year:', year, 'month:', month);

        navigateToMonth(year, month);
      });
    }

    if (openPickerBtn && typeof openPickerBtn.addEventListener === 'function') {
      openPickerBtn.addEventListener('click', function(event) {
        event.preventDefault();
        if (window.PayCalCore && typeof window.PayCalCore.openModal === 'function') {
          window.PayCalCore.openModal('modal_cal_picker', calendarI18n('DATE_PICKER', 'Date Picker'));
        }

        if (yearInput && typeof yearInput.focus === 'function') {
          setTimeout(() => yearInput.focus(), 100);
        }
      });
    }

    if (yearInput) {
      yearInput.addEventListener('input', function() {
        updateDatePickerGoLabel();
      });

      yearInput.addEventListener('change', function() {
        const parsedYear = parseInt((yearInput.value || '').trim(), 10);
        if (Number.isFinite(parsedYear)) {
          setSelectedYearValue(parsedYear);
        }
      });
    }

    monthButtons.forEach((button) => {
      button.addEventListener('click', function(event) {
        event.preventDefault();
        selectPickerButton(button, monthButtons);
      });
    });

    updateDatePickerGoLabel();
    announceCurrentMonth();

    if (datePickerGoBtn && typeof datePickerGoBtn.addEventListener === 'function') {
      datePickerGoBtn.addEventListener('click', function(event) {
        event.preventDefault();

        submitDatePickerSelection();
      });
    }

    if (pickerDialog && typeof pickerDialog.addEventListener === 'function') {
      pickerDialog.addEventListener('keydown', function(event) {
        if (trapFocusWithin(pickerDialog, event)) {
          return;
        }

        const target = event.target;
        const tagName = (target && target.tagName ? target.tagName : '').toUpperCase();
        if (tagName === 'TEXTAREA' || !!(target && target.isContentEditable)) {
          return;
        }

        if (target instanceof HTMLElement) {
          const monthButton = target.closest('#cal_menu_right button');
          if (monthButton instanceof HTMLButtonElement) {
            selectPickerButton(monthButton, monthButtons);
          }
        }

        if (event.key === 'PageUp') {
          if (moveYearSelection(-1)) {
            event.preventDefault();
          }
          return;
        }

        if (event.key === 'PageDown') {
          if (moveYearSelection(1)) {
            event.preventDefault();
          }
          return;
        }

        if (event.key === 'ArrowLeft') {
          if (moveMonthSelection(-1)) {
            event.preventDefault();
          }
          return;
        }

        if (event.key === 'ArrowRight') {
          if (moveMonthSelection(1)) {
            event.preventDefault();
          }
          return;
        }

        if (event.key === 'ArrowUp') {
          if (moveMonthSelection(-3)) {
            event.preventDefault();
          }
          return;
        }

        if (event.key === 'ArrowDown') {
          if (moveMonthSelection(3)) {
            event.preventDefault();
          }
          return;
        }

        if (event.key !== 'Enter') {
          return;
        }

        event.preventDefault();
        submitDatePickerSelection();
      });
    }

    document.querySelectorAll('[data-action="prev-week"], [data-action="next-week"]').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const anchor = button.getAttribute('data-anchor') || '';
        if (anchor !== '') {
          navigateToWeek(anchor);
        }
      });
    });

    document.querySelectorAll('[data-action="prev-pay-period"], [data-action="next-pay-period"]').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const anchor = button.getAttribute('data-anchor') || '';
        if (anchor !== '') {
          navigateToPayPeriod(anchor);
        }
      });
    });

    const weekPickerBtn = document.getElementById('cal_week_picker_button');
    const weekPickerDialog = document.getElementById('modal_cal_week_picker');
    const weekDateInput = document.getElementById('cal_week_date_input');
    const weekPickerGoBtn = document.getElementById('cal_week_picker_go_btn');

    if (weekPickerBtn && typeof weekPickerBtn.addEventListener === 'function') {
      weekPickerBtn.addEventListener('click', (event) => {
        event.preventDefault();
        if (window.PayCalCore && typeof window.PayCalCore.openModal === 'function') {
          window.PayCalCore.openModal('modal_cal_week_picker', calendarI18n('CALENDAR_WEEK_PICKER_TITLE', 'Select week'));
        }
        if (weekDateInput instanceof HTMLInputElement && typeof weekDateInput.focus === 'function') {
          setTimeout(() => weekDateInput.focus(), 100);
        }
      });
    }

    if (weekPickerGoBtn && typeof weekPickerGoBtn.addEventListener === 'function') {
      weekPickerGoBtn.addEventListener('click', (event) => {
        event.preventDefault();
        const picked = weekDateInput instanceof HTMLInputElement ? String(weekDateInput.value || '').trim() : '';
        if (picked === '') {
          return;
        }

        if (window.PayCalCore && typeof window.PayCalCore.closeModal === 'function') {
          window.PayCalCore.closeModal('modal_cal_week_picker', calendarI18n('CALENDAR_WEEK_PICKER_TITLE', 'Select week'));
        }

        navigateToWeek(picked);
      });
    }

    const payPeriodPickerBtn = document.getElementById('cal_payperiod_picker_button');
    if (payPeriodPickerBtn && typeof payPeriodPickerBtn.addEventListener === 'function') {
      payPeriodPickerBtn.addEventListener('click', (event) => {
        event.preventDefault();
        if (window.PayCalCore && typeof window.PayCalCore.openModal === 'function') {
          window.PayCalCore.openModal('modal_cal_payperiod_picker', calendarI18n('CALENDAR_PAY_PERIOD_PICKER_TITLE', 'Select pay period'));
        }
      });
    }

    document.querySelectorAll('.calendar_payperiod_picker_option').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const anchor = button.getAttribute('data-pay-period-start') || '';
        if (anchor === '') {
          return;
        }

        if (window.PayCalCore && typeof window.PayCalCore.closeModal === 'function') {
          window.PayCalCore.closeModal('modal_cal_payperiod_picker', calendarI18n('CALENDAR_PAY_PERIOD_PICKER_TITLE', 'Select pay period'));
        }

        navigateToPayPeriod(anchor);
      });
    });

    if (weekPickerDialog && typeof weekPickerDialog.addEventListener === 'function') {
      weekPickerDialog.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
          return;
        }

        const target = event.target;
        if (!(target instanceof HTMLInputElement) || target.id !== 'cal_week_date_input') {
          return;
        }

        event.preventDefault();
        weekPickerGoBtn?.click();
      });
    }
  }

  /**
   * Open modal dialog for a given date.
   * @param {string} dateId - The date identifier
   */
  async function openModalForDate(dateId) {
    const modal = document.getElementById('calendar-modal');
    if (!modal) {
      calendarConsoleDebug('openModalForDate aborted: modal missing', {
        dateId,
      });
      return;
    }

    // Secondary guard - check if date is locked
    if (isDateLocked(dateId)) {
      calendarConsoleDebug('openModalForDate blocked: date locked', {
        dateId,
      });
      showLockedDateMessage(dateId);
      return;
    }

    calendarConsoleDebug('openModalForDate start', {
      dateId,
      hasDek: PayCalCryptoState.hasDek,
      modalOpen: modal.open,
    });

    if (!PayCalCryptoState.hasDek) {
      // User interaction required: first-time DEK generation or passkey assertion for unwrap
      const hasDek = await ensurePayCalDEK({ interactive: true });
      if (!hasDek) {
        calendarConsoleDebug('openModalForDate blocked: DEK unlock required', {
          dateId,
          hasDek,
        });
        const unlockMessage = isWebAuthnCapableBrowser()
          ? calendarI18n(
            'CALENDAR_UNLOCK_REQUIRED_EDIT',
            'Secure unlock is required before editing. Sign in with your passkey again and retry.'
          )
          : calendarWebAuthnUnsupportedMessage();
        PayCalCore.updateStatusMessage(unlockMessage, 'error', 5000);
        return;
      }
    }

    modalAutofocusToken += 1;
    const focusToken = modalAutofocusToken;
    focusLog('[Calendar Focus Debug] openModalForDate start', { dateId, focusToken });

    // Track the grid focus target so we can restore it when modal closes
    window._CALENDAR_LAST_GRID_FOCUS_DATE = dateId;
    if (modal) {
      modal.setAttribute('data-last-grid-focus-date', dateId);
    }

    // Set date in header
    const dateHeader = modal.querySelector('#calendar-modal-date');
    if (dateHeader) {
      try {
        const date = new Date(dateId + 'T00:00:00');
        const formattedDate = formatCalendarLocaleDate(date, {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
        dateHeader.textContent = formattedDate;
      } catch {
        dateHeader.textContent = dateId;
      }
    }

    modal.setAttribute('data-active-date', dateId);

    if (!modal.open) {
      if (window.PayCalCore && typeof window.PayCalCore.openModal === 'function') {
        window.PayCalCore.openModal('calendar-modal', calendarWorkDetailsLabel());
      } else {
        modal.showModal();
      }
    }
    calendarConsoleDebug('openModalForDate modal open attempted', {
      dateId,
      modalOpen: modal.open,
    });
    focusLog('[Calendar Focus Debug] modal opened', { isOpen: modal.open, focusToken });

    showAddEntryForm(dateId, focusToken);

    modalLog(`[Calendar Modal] Opened for date: ${dateId}`);
  }
  
  /**
   * Escape HTML text to prevent XSS
   */
  function escapeText(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, char => map[char]);
  }

  /**
   * Close modal dialog.
   */
  /**
   * Disable/enable all inputs and buttons in the modal to prevent accidental interaction.
   * @param {boolean} disabled - true to disable, false to enable
   */
  function setModalElementsDisabled(disabled) {
    const modal = document.getElementById('calendar-modal');
    if (!modal) return;

    const elements = modal.querySelectorAll('input, button, select, textarea');
    elements.forEach(el => {
      if (disabled) {
        el.disabled = true;
        el.setAttribute('aria-busy', 'true');
      } else {
        el.disabled = false;
        el.removeAttribute('aria-busy');
      }
    });
  }

  function closeModal() {
    const modal = document.getElementById('calendar-modal');
    if (!modal) return;

    // Disable elements while closing to prevent accidental clicks
    setModalElementsDisabled(true);

    modalAutofocusToken += 1;
    focusLog('[Calendar Focus Debug] closeModal', { newToken: modalAutofocusToken });

    if (modal.open) {
      if (window.PayCalCore && typeof window.PayCalCore.closeModal === 'function') {
        window.PayCalCore.closeModal('calendar-modal', calendarWorkDetailsLabel());
      } else {
        modal.close();
      }
    }
    setModalElementsDisabled(false);

    restoreCalendarFocusAfterModalClose(modal);

    modalLog('[Calendar Modal] Closed');
  }

  /**
   * Restore focus to the calendar grid cell that opened the modal.
   * @param {HTMLElement} modal - The modal element
   */
  function restoreCalendarFocusAfterModalClose(modal) {
    const trackedDate = (modal && modal.getAttribute('data-last-grid-focus-date')) || window._CALENDAR_LAST_GRID_FOCUS_DATE;
    focusLog('[Calendar Focus Debug] restoring grid focus', { trackedDate });

    const focusBack = () => {
      const grid = document.querySelector('#calendar-grid .datagrid_month_grid');
      const targetCell = trackedDate ? document.querySelector(`.datagrid_month_cell[data-id="${trackedDate}"]`) : null;
      const fallbackCell = document.querySelector('.datagrid_month_cell[tabindex="0"]') || document.querySelector('.datagrid_month_cell');
      const cellToFocus = targetCell || fallbackCell;

      if (!cellToFocus) {
        focusLog('[Calendar Focus Debug] restore focus skipped - no cell found');
        return;
      }

      if (grid) {
        const allCells = grid.querySelectorAll('.datagrid_month_cell');
        allCells.forEach(c => c.setAttribute('tabindex', '-1'));
      }

      setGridCellFocusState(cellToFocus, true, { preventScroll: true });

      const restoredDateId = cellToFocus.getAttribute('data-id');
      if (restoredDateId) {
        window._CALENDAR_LAST_GRID_FOCUS_DATE = restoredDateId;
      }

      focusLog('[Calendar Focus Debug] grid focus restored', {
        restoredDateId: cellToFocus.getAttribute('data-id'),
        activeTag: document.activeElement ? document.activeElement.tagName : null,
      });
    };

    requestAnimationFrame(() => {
      setTimeout(focusBack, 20);
    });
  }

  /**
   * Handle modal action button clicks.
   * @param {ClickEvent} event - The click event
   */
  function handleModalAction(event) {
    const action = event.currentTarget.getAttribute('data-action');
    modalLog(`[Calendar Modal Action] ${action}`);

    if ('close' === action) {
      closeModal();
    } else if ('add' === action || 'add-row' === action) {
      const modal = document.getElementById('calendar-modal');
      const activeDate = modal ? modal.getAttribute('data-active-date') : '';
      addWorkEntryRow(activeDate);
    } else if ('save' === action) {
      setModalElementsDisabled(true);
      handleWorkEntrySave();
    } else if ('cancel-add' === action) {
      // Return to entries view - reload the modal content
      const modal = document.getElementById('calendar-modal');
      const dateHeader = modal.querySelector('#calendar-modal-date');
      const dateText = dateHeader.textContent;
      // Extract date from header like "Monday, March 4, 2026"
      const cells = document.querySelectorAll('[data-id]');
      let foundDate = null;
      cells.forEach(cell => {
        if (cell.getAttribute('data-id')) {
          try {
            const cellDate = new Date(cell.getAttribute('data-id') + 'T00:00:00');
            const formatted = formatCalendarLocaleDate(cellDate, {
              weekday: 'long',
              year: 'numeric',
              month: 'long',
              day: 'numeric'
            });
            if (formatted === dateText) {
              foundDate = cell.getAttribute('data-id');
            }
          } catch {}
        }
      });
      if (foundDate) {
        openModalForDate(foundDate);
      }
    }
  }

  /**
   * Show the Add Entry form in the modal.
   */
  async function showAddEntryForm(dateId, focusToken = null) {
    const modal = document.getElementById('calendar-modal');
    const content = modal.querySelector('#calendar-modal-content');
    
    if (!content) return;
    
    modalLog('[Calendar Modal] Showing Add Entry form');
    focusLog('[Calendar Focus Debug] showAddEntryForm', { dateId, focusToken, currentToken: modalAutofocusToken });
    
    // Fetch all active sites from the API, falling back to catalog if API fails
    const siteCatalog = await fetchAllSites();

    let existingEntries = [];
    const cell = dateId ? document.querySelector(`[data-id="${dateId}"]`) : null;
    if (cell) {
      try {
        const workEntriesJson = cell.getAttribute('data-work-entries');
        existingEntries = workEntriesJson ? JSON.parse(workEntriesJson) : [];
      } catch {
        existingEntries = [];
      }
    }

    const rowsHtml = (existingEntries && existingEntries.length > 0)
      ? existingEntries.map((entry, index) => generateWorkEntryRow(index, siteCatalog, entry, index === existingEntries.length - 1)).join('')
      : generateWorkEntryRow(0, siteCatalog, null, true);
    
    const formHtml = `
      <div class="work-entries-form">
        <table class="work-entries-table">
          <thead>
            <tr>
              <th class="th-site">${escapeText(WORK_ENTRY_COLUMN_HEADINGS.site)}</th>
              <th class="th-regular">${escapeText(WORK_ENTRY_COLUMN_HEADINGS.regular)}</th>
              <th class="th-overtime">${escapeText(WORK_ENTRY_COLUMN_HEADINGS.overtime)}</th>
              <th class="th-loa">${escapeText(WORK_ENTRY_COLUMN_HEADINGS.livingOutAllowance)}</th>
              <th class="th-travel">${escapeText(WORK_ENTRY_COLUMN_HEADINGS.travel)}</th>
              <th class="th-action"></th>
            </tr>
          </thead>
          <tbody id="work-entries-tbody">
            ${rowsHtml}
          </tbody>
        </table>
      </div>
    `;
    
    Guardian.setHTML(content, formHtml);
    focusLog('[Calendar Focus Debug] form rendered', {
      rowCount: content.querySelectorAll('#work-entries-tbody .work-entry-row').length,
      regularInputs: content.querySelectorAll('#work-entries-tbody input[name^="regular_"]').length,
      focusToken,
    });
    
    attachWorkEntryRowActionHandlers();
    
    // Update footer buttons to have Save functionality
    updateModalFooterForEdit();

    focusFirstRegularInput(focusToken);
    setTimeout(() => {
      const modalOpen = !!(modal && modal.open);
      const active = document.activeElement;
      const focusedName = active && active.getAttribute ? active.getAttribute('name') : null;
      focusLog('[Calendar Focus Debug] post-focus check', {
        modalOpen,
        activeTag: active ? active.tagName : null,
        activeName: focusedName,
        focusToken,
        currentToken: modalAutofocusToken,
      });
      if (modalOpen && (!focusedName || !focusedName.startsWith('regular_'))) {
        focusLog('[Calendar Focus Debug] fallback focus attempt (tokenless)');
        focusFirstRegularInput(null, 0);
      }
    }, 120);
  }

  /**
   * Focus the first row Regular hours input in the modal.
   */
  function focusFirstRegularInput(focusToken = null, attempt = 0) {
    const modal = document.getElementById('calendar-modal');
    if (!modal) return;

    if (null !== focusToken && focusToken !== modalAutofocusToken) {
      focusLog('[Calendar Focus Debug] abort: token mismatch', { focusToken, currentToken: modalAutofocusToken, attempt });
      return;
    }

    if (!modal.open) {
      focusLog('[Calendar Focus Debug] abort: modal not open', { attempt, focusToken, currentToken: modalAutofocusToken });
      return;
    }

    const firstRegularInput = document.querySelector('#work-entries-tbody input[name^="regular_"]');
    const isVisible = !!(firstRegularInput && null !== firstRegularInput.offsetParent);
    focusLog('[Calendar Focus Debug] attempt', {
      attempt,
      focusToken,
      currentToken: modalAutofocusToken,
      foundInput: !!firstRegularInput,
      isVisible,
      inputName: firstRegularInput ? firstRegularInput.getAttribute('name') : null,
    });

    if (isVisible) {
      try {
        firstRegularInput.focus({ preventScroll: true });
      } catch (e) {
        focusLog('[Calendar Focus Debug] focus options unsupported, fallback focus()', e);
        firstRegularInput.focus();
      }
      try {
        firstRegularInput.select();
      } catch (e) {
        focusLog('[Calendar Focus Debug] select() failed', e);
      }
      focusLog('[Calendar Focus Debug] focus success', {
        activeName: document.activeElement && document.activeElement.getAttribute ? document.activeElement.getAttribute('name') : null,
      });
      return;
    }

    if (attempt < 6) {
      requestAnimationFrame(() => {
        setTimeout(() => {
          focusFirstRegularInput(focusToken, attempt + 1);
        }, 30);
      });
    }
  }
  
  /**
   * Fetch merged personal + business-linked sites for the work-entry dialog.
   * Business-linked sites use display_name with a [ABBREV] prefix.
   * @returns {Promise<Array<{site_id: string, site_name: string, scope?: string}>>}
   */
  async function fetchAllSites() {
    try {
      const appUrl = window.location.origin;
      const response = await fetch(`${appUrl}/api/v1/sites/calendar`, {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Accept': 'application/json'
        }
      });

      if (!response.ok) {
        console.error('[Calendar] API error fetching calendar sites:', response.status);
        return [];
      }

      const payload = await response.json();
      const data = payload && typeof payload.data === 'object' ? payload.data : payload;
      const sites = Array.isArray(data?.sites) ? data.sites : [];

      return sites
        .map((site) => ({
          site_id: (site.site_id || site.id || '').toString().trim(),
          site_name: (site.display_name || site.site_name || '').toString().trim(),
          scope: (site.scope || 'personal').toString().trim(),
          wage: parseMoneyValue(site.wage ?? 0),
        }))
        .filter((site) => site.site_id !== '' && site.site_name !== '')
        .sort((a, b) => {
          const aScope = a.scope === 'business' ? 1 : 0;
          const bScope = b.scope === 'business' ? 1 : 0;
          if (aScope !== bScope) {
            return aScope - bScope;
          }

          return a.site_name.localeCompare(b.site_name);
        });
    } catch (error) {
      console.error('[Calendar] Error fetching calendar sites from API:', error);
      return [];
    }
  }

  /**
   * Generate HTML for a single work entry row.
   * @param {number} index - Row index
   * @param {Array<{site_id: string, site_name: string}>} siteCatalog - Site options
   * @returns {string} HTML string for table row
   */
  function generateWorkEntryRow(index, siteCatalog, entry = null, includeAddButton = false) {
    const entryHasSiteMatch = !!(
      entry && siteCatalog.some(site => (
        (entry.site_id && entry.site_id === site.site_id) ||
        (!entry.site_id && entry.site_name && entry.site_name === site.site_name)
      ))
    );

    let sitesOptions = `<option value="">${escapeText(calendarI18n('CALENDAR_MODAL_SELECT_SITE', 'Select site...'))}</option>`;
    if (siteCatalog.length > 0) {
      sitesOptions += siteCatalog.map((site, siteIndex) => {
        const entryMatched = !!(entry && (
          (entry.site_id && entry.site_id === site.site_id) ||
          (!entry.site_id && entry.site_name && entry.site_name === site.site_name)
        ));

        const shouldSelectByDefault = !entry && siteIndex === 0;
        const shouldSelectFallback = !!entry && !entryHasSiteMatch && siteIndex === 0;
        const selected = entryMatched || shouldSelectByDefault || shouldSelectFallback;

        return `<option value="${escapeText(site.site_id)}" ${selected ? 'selected' : ''}>${escapeText(site.site_name)}</option>`;
      }).join('');
    }

    const regularValue = entry ? (parseFloat(entry.regular_hours) || 0) : 0;
    const overtimeValue = entry ? (parseFloat(entry.overtime_hours) || 0) : 0;
    const loaValue = entry ? (parseFloat(entry.living_out_allowance) || 0) : 0;
    const travelValue = entry ? (parseFloat(entry.travel_hours) || 0) : 0;
    
    return `
      <tr class="work-entry-row" data-row-index="${index}">
        <td data-label="${escapeText(WORK_ENTRY_COLUMN_HEADINGS.site)}">
          <select class="entry-site-select" name="site_${index}" required>
            ${sitesOptions}
          </select>
        </td>
        <td data-label="${escapeText(WORK_ENTRY_COLUMN_HEADINGS.regular)}">
          <input type="number" name="regular_${index}" step="0.5" min="0" max="24" placeholder="0" class="entry-hours-input" value="${regularValue > 0 ? regularValue : ''}">
        </td>
        <td data-label="${escapeText(WORK_ENTRY_COLUMN_HEADINGS.overtime)}">
          <input type="number" name="overtime_${index}" step="0.5" min="0" max="24" placeholder="0" class="entry-hours-input" value="${overtimeValue > 0 ? overtimeValue : ''}">
        </td>
        <td data-label="${escapeText(WORK_ENTRY_COLUMN_HEADINGS.livingOutAllowance)}">
          <input type="number" name="loa_${index}" step="0.5" min="0" max="24" placeholder="0" class="entry-hours-input" value="${loaValue > 0 ? loaValue : ''}">
        </td>
        <td data-label="${escapeText(WORK_ENTRY_COLUMN_HEADINGS.travel)}">
          <input type="number" name="travel_${index}" step="0.5" min="0" max="24" placeholder="0" class="entry-hours-input" value="${travelValue > 0 ? travelValue : ''}">
        </td>
        <td class="work-entry-row-actions" data-label="">
          <button type="button" class="work-entry-delete" data-row="${index}">${escapeText(calendarI18n('DELETE', 'Delete'))}</button>
          ${includeAddButton ? `<button type="button" class="work-entry-add" data-action="add-row">${escapeText(calendarI18n('CALENDAR_MODAL_ADD', 'Add'))}</button>` : ''}
        </td>
      </tr>
    `;
  }
  
  /**
   * Keep the Add action on the bottom work-entry row.
   */
  function refreshWorkEntryAddButton() {
    const rows = Array.from(document.querySelectorAll('#work-entries-tbody .work-entry-row'));
    rows.forEach((row, index) => {
      const actionsCell = row.querySelector('.work-entry-row-actions');
      if (!(actionsCell instanceof HTMLElement)) {
        return;
      }

      const existingAdd = actionsCell.querySelector('.work-entry-add');
      const isLast = index === rows.length - 1;
      if (isLast && !existingAdd) {
        Guardian.insertHTML(
          actionsCell,
          'beforeend',
          `<button type="button" class="work-entry-add" data-action="add-row">${escapeText(calendarI18n('CALENDAR_MODAL_ADD', 'Add'))}</button>`
        );
      } else if (!isLast && existingAdd) {
        existingAdd.remove();
      }
    });
  }

  /**
   * Attach click handlers to work-entry row action buttons.
   */
  function attachWorkEntryRowActionHandlers() {
    refreshWorkEntryAddButton();

    document.querySelectorAll('.work-entry-delete').forEach(btn => {
      if ('1' === btn.getAttribute('data-bound-delete')) {
        return;
      }
      btn.setAttribute('data-bound-delete', '1');
      btn.addEventListener('click', function(event) {
        event.preventDefault();
        const row = this.closest('tr');
        const rowIndex = this.getAttribute('data-row');
        coreLog('[Calendar Delete] Delete button clicked', {
          rowIndex,
          rowElement: row ? 'found' : 'not found',
          rowContent: row ? row.textContent.substring(0, 50) : 'N/A'
        });
        if (row) {
          coreLog('[Calendar Delete] Removing row', rowIndex);
          row.remove();
          coreLog('[Calendar Delete] Row removed, remaining rows:', document.querySelectorAll('.work-entry-row').length);
          if (document.querySelectorAll('#work-entries-tbody .work-entry-row').length === 0) {
            const modal = document.getElementById('calendar-modal');
            const activeDate = modal ? modal.getAttribute('data-active-date') : '';
            addWorkEntryRow(activeDate);
            return;
          }
          refreshWorkEntryAddButton();
          attachWorkEntryRowActionHandlers();
        }
      });
    });

    document.querySelectorAll('.work-entry-add').forEach(btn => {
      if ('1' === btn.getAttribute('data-bound-add')) {
        return;
      }
      btn.setAttribute('data-bound-add', '1');
      btn.addEventListener('click', handleModalAction);
    });
  }

  /**
   * Add a new work entry row to the open modal table.
   * @param {string} _dateId - Active date id (reserved for future use)
   */
  async function addWorkEntryRow(_dateId) {
    const tbody = document.getElementById('work-entries-tbody');
    if (!tbody) return;

    const siteCatalog = await fetchAllSites();
    const rowCount = tbody.querySelectorAll('tr').length;
    refreshWorkEntryAddButton();
    const newRow = generateWorkEntryRow(rowCount, siteCatalog, null, true);
    Guardian.insertHTML(tbody, 'beforeend', newRow);
    refreshWorkEntryAddButton();
    attachWorkEntryRowActionHandlers();
  }
  
  /**
   * Update modal footer buttons for edit mode.
   */
  function updateModalFooterForEdit() {
    const footer = document.querySelector('.calendar_modal_footer');
    if (!footer) return;
    
    Guardian.setHTML(footer, `
      <div class="calendar_modal_footer_center">
        <button type="button" class="btn btn_primary calendar_modal_action calendar_modal_action_save" data-action="save">${escapeText(calendarI18n('SAVE', 'Save'))}</button>
        <button type="button" class="btn btn_cancel calendar_modal_action calendar_modal_action_close" data-action="close">${escapeText(calendarI18n('CLOSE', 'Close'))}</button>
      </div>
    `);
    
    // Attach handlers
    footer.querySelectorAll('.calendar_modal_action').forEach(btn => {
      btn.addEventListener('click', handleModalAction);
    });
    
  }

  function formatHourValue(value) {
    const numeric = parseFloat(value) || 0;
    return Number(numeric.toFixed(2)).toString();
  }

  function parseMoneyValue(value) {
    if (typeof value === 'number' && Number.isFinite(value)) {
      return value;
    }

    if (typeof value !== 'string') {
      return 0;
    }

    const normalized = value.replace(/[^0-9.-]/g, '').trim();
    if (normalized === '') {
      return 0;
    }

    const numeric = parseFloat(normalized);
    return Number.isFinite(numeric) ? numeric : 0;
  }

  function formatMoneyValue(value) {
    const numeric = Number.isFinite(value) ? value : 0;
    return numeric.toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  function resolveOvertimeScale() {
    const rawScale = window?.PayCalCore?.config?.pay_overtime_scale;
    const parsed = parseFloat(rawScale);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : 1.5;
  }

  function roundMoneyNumber(value) {
    const numeric = Number.isFinite(value) ? value : parseMoneyValue(value);
    return Number(Number(numeric || 0).toFixed(2));
  }

  function buildWorkEntryEarningsSnapshot(entry) {
    const regular = parseFloat(entry?.regular_hours ?? entry?.regular ?? entry?.r ?? 0) || 0;
    const overtime = parseFloat(entry?.overtime_hours ?? entry?.overtime ?? entry?.o ?? 0) || 0;
    const travel = parseFloat(entry?.travel_hours ?? entry?.travel ?? entry?.t ?? 0) || 0;
    const livingOut = parseMoneyValue(entry?.living_out_allowance ?? entry?.living_out ?? entry?.loa ?? entry?.l ?? 0);
    const directWage = parseMoneyValue(entry?.wage ?? entry?.w ?? 0);
    const siteWage = getSiteWageForEntry(entry);
    const wage = directWage > 0 ? directWage : siteWage;
    const overtimeScale = resolveOvertimeScale();
    const regularAmount = roundMoneyNumber(regular * wage);
    const overtimeAmount = roundMoneyNumber(overtime * wage * overtimeScale);
    const travelAmount = roundMoneyNumber(travel * wage);
    const livingOutAmount = roundMoneyNumber(livingOut);

    return {
      wage: roundMoneyNumber(wage),
      regular_amount: regularAmount,
      overtime_amount: overtimeAmount,
      travel_amount: travelAmount,
      living_out_amount: livingOutAmount,
      gross: roundMoneyNumber(regularAmount + overtimeAmount + travelAmount + livingOutAmount),
      earnings_snapshot_version: 1,
    };
  }

  function applyWorkEntryEarningsSnapshot(entry) {
    if (!entry || typeof entry !== 'object') {
      return entry;
    }
    Object.assign(entry, buildWorkEntryEarningsSnapshot(entry));
    return entry;
  }

  function getEntriesFromCalendarCell(cell) {
    if (!cell) {
      return [];
    }

    try {
      const raw = cell.getAttribute('data-work-entries') || '[]';
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  }

  function setCalendarSiteWages(siteCatalog) {
    if (!Array.isArray(siteCatalog)) {
      return;
    }

    Object.keys(calendarSiteWageMatrix).forEach((key) => {
      delete calendarSiteWageMatrix[key];
    });
    calendarSiteWageById.clear();

    for (const site of siteCatalog) {
      if (!site || typeof site !== 'object') {
        continue;
      }

      const parsedWage = parseMoneyValue(site.wage ?? 0);
      if (!(parsedWage > 0)) {
        continue;
      }

      const candidateIds = [site.site_uuid, site.site_id, site.uuid, site.id];
      for (const candidate of candidateIds) {
        const siteId = (candidate || '').toString().trim();
        if (siteId === '') {
          continue;
        }

        calendarSiteWageMatrix[siteId] = parsedWage;
        calendarSiteWageById.set(siteId, parsedWage);
      }
    }

    window.PAYCAL_SITE_WAGE_MATRIX = calendarSiteWageMatrix;
  }

  function extractApiPayload(jsonResponse) {
    if (!jsonResponse || typeof jsonResponse !== 'object') {
      return {};
    }

    if (jsonResponse.data && typeof jsonResponse.data === 'object') {
      return jsonResponse.data;
    }

    const { status: _status, message: _message, ...rest } = jsonResponse;
    return rest;
  }

  async function ensureDailyEarningsYear(year) {
    const yearString = String(parseInt(year, 10) || '');
    if (yearString === '') {
      return;
    }
    if (calendarDailyEarningsLoadedYears.has(yearString) || calendarDailyEarningsLoadingYears.has(yearString)) {
      return;
    }

    calendarDailyEarningsLoadingYears.add(yearString);
    try {
      const response = await fetch(`/api/v1/daily/year/${yearString}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
          Accept: 'application/json',
        },
      });
      if (!response.ok) {
        return;
      }

      const payload = extractApiPayload(await response.json());
      Object.entries(payload).forEach(([dateId, row]) => {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(String(dateId))) {
          return;
        }
        if (!row || typeof row !== 'object' || Array.isArray(row)) {
          return;
        }

        const gross = parseMoneyValue(row.gross ?? 0);
        const deductions = parseMoneyValue(row.deductions ?? row.tax ?? 0);
        const net = parseMoneyValue(row.net ?? (gross - deductions));
        calendarDailyEarningsByDate[dateId] = { gross, deductions, net };
      });

      calendarDailyEarningsLoadedYears.add(yearString);
      const grid = document.getElementById('calendar-grid');
      if (grid) {
        refreshAllCalendarDayTooltips(grid);
      }
    } catch {
      // Ignore API errors and keep fallback calculations.
    } finally {
      calendarDailyEarningsLoadingYears.delete(yearString);
    }
  }

  function ensureVisibleDailyEarningsLoaded(grid) {
    if (!grid) {
      return;
    }

    const years = new Set();
    grid.querySelectorAll('.datagrid_month_cell[data-id]').forEach((cell) => {
      const dateId = (cell.getAttribute('data-id') || '').trim();
      const year = dateId.split('-')[0] || '';
      if (/^\d{4}$/.test(year)) {
        years.add(year);
      }
    });

    years.forEach((year) => {
      void ensureDailyEarningsYear(year);
    });
  }

  function getSiteWageForEntry(entry) {
    if (!entry || typeof entry !== 'object') {
      return 0;
    }

    const siteId = (entry.site_id ?? entry.s ?? '').toString().trim();
    if (siteId === '') {
      return 0;
    }

    if (Object.prototype.hasOwnProperty.call(calendarSiteWageMatrix, siteId)) {
      return parseMoneyValue(calendarSiteWageMatrix[siteId]);
    }

    return calendarSiteWageById.get(siteId) || 0;
  }

  function ensureCalendarSiteCatalog() {
    if (calendarSiteCatalogPromise) {
      return calendarSiteCatalogPromise;
    }

    calendarSiteCatalogPromise = fetchAllSites()
      .then((sites) => {
        setCalendarSiteWages(sites);
        const grid = document.getElementById('calendar-grid');
        if (grid) {
          refreshAllCalendarDayTooltips(grid);
        }
        return sites;
      })
      .catch(() => [])
      .finally(() => {
        calendarSiteCatalogPromise = null;
      });

    return calendarSiteCatalogPromise;
  }

  function computeCalendarTotals(entries, dateId = '') {
    const list = Array.isArray(entries) ? entries : [];
    let regularTotal = 0;
    let overtimeTotal = 0;
    let grossTotal = 0;
    let netTotal = 0;
    // Daily 8-hour regular cap — mirrors server-side Work::calculateHours().
    // Entries may have been saved before the save-path cap was in place;
    // applying it here ensures the display is always correct.
    let dailyRegularUsed = 0;

    for (const entry of list) {
      if (!entry || typeof entry !== 'object') {
        continue;
      }

      const hasEncryptedBlob = !!entry.encrypted_blob;
      const hasExplicitHours = (
        entry.hours !== undefined || entry.h !== undefined ||
        entry.regular_hours !== undefined || entry.regular !== undefined || entry.r !== undefined ||
        entry.overtime_hours !== undefined || entry.overtime !== undefined || entry.o !== undefined ||
        entry.living_out_allowance !== undefined || entry.living_out !== undefined || entry.loa !== undefined || entry.l !== undefined ||
        entry.travel_hours !== undefined || entry.travel !== undefined || entry.t !== undefined
      );
      if (hasEncryptedBlob && !hasExplicitHours) {
        continue;
      }

      const regularRaw = entry.regular_hours ?? entry.regular ?? entry.r;
      const overtimeRaw = entry.overtime_hours ?? entry.overtime ?? entry.o;
      const fallbackHoursRaw = entry.hours ?? entry.h ?? 0;

      const storedRegular = parseFloat(
        (regularRaw !== undefined && regularRaw !== null)
          ? regularRaw
          : ((overtimeRaw === undefined || overtimeRaw === null) ? fallbackHoursRaw : 0)
      ) || 0;
      const storedOvertime = parseFloat(overtimeRaw ?? 0) || 0;

      // Re-apply daily cap so old entries with wrong splits display correctly.
      const entryTotal = storedRegular + storedOvertime;
      const dailyRemaining = Math.max(0, 8 - dailyRegularUsed);
      const regular = Math.min(entryTotal, dailyRemaining);
      const overtime = entryTotal - regular;
      dailyRegularUsed += regular;

      regularTotal += regular;
      overtimeTotal += overtime;

      const hasGross = entry.gross !== undefined || entry.g !== undefined;
      const hasNet = entry.net !== undefined || entry.nv !== undefined;
      const hasTax = entry.tax !== undefined || entry.tx !== undefined || entry.deductions !== undefined;

      const explicitGross = parseMoneyValue(entry.gross ?? entry.g ?? 0);
      const explicitNet = parseMoneyValue(entry.net ?? entry.nv ?? 0);
      const explicitTax = parseMoneyValue(entry.tax ?? entry.tx ?? entry.deductions ?? 0);
      const snapshot = buildWorkEntryEarningsSnapshot({
        ...entry,
        regular_hours: regular,
        overtime_hours: overtime,
      });
      const computedGross = snapshot.gross;
      const gross = hasGross ? explicitGross : computedGross;
      const net = hasNet ? explicitNet : (hasTax ? (gross - explicitTax) : gross);

      grossTotal += gross;
      netTotal += net;
    }

    if (dateId && Object.prototype.hasOwnProperty.call(calendarDailyEarningsByDate, dateId)) {
      const daily = calendarDailyEarningsByDate[dateId];
      grossTotal = parseMoneyValue(daily.gross ?? 0);
      netTotal = parseMoneyValue(daily.net ?? 0);
    }

    const deductionsTotal = dateId && Object.prototype.hasOwnProperty.call(calendarDailyEarningsByDate, dateId)
      ? parseMoneyValue(calendarDailyEarningsByDate[dateId].deductions ?? Math.max(0, grossTotal - netTotal))
      : Math.max(0, grossTotal - netTotal);

    return {
      regularTotal,
      overtimeTotal,
      totalHours: regularTotal + overtimeTotal,
      grossTotal,
      netTotal,
      deductionsTotal,
    };
  }

  function computeCalendarDayTooltip(entries, dateId = '') {
    const totals = computeCalendarTotals(entries, dateId);
    const grossLabel = calendarI18n('GROSS', 'Gross');
    const deductionsLabel = calendarI18n('DEDUCTIONS', 'Deductions');
    const netLabel = calendarI18n('NET', 'Net');

    return [
      `Regular: ${formatHourValue(totals.regularTotal)}`,
      `Overtime: ${formatHourValue(totals.overtimeTotal)}`,
      `${grossLabel}: $${formatMoneyValue(totals.grossTotal)}`,
      `${deductionsLabel}: $${formatMoneyValue(Math.max(0, totals.grossTotal - totals.netTotal))}`,
      `${netLabel}: $${formatMoneyValue(totals.netTotal)}`,
    ].join(' | ');
  }

  function updateCalendarDayTooltip(cell, entries = null) {
    if (!cell) {
      return;
    }

    const grid = cell.closest('.datagrid_month_grid');
    const selectedCells = grid
      ? Array.from(grid.querySelectorAll('.datagrid_month_cell[data-selected="true"]'))
      : [];

    if (cell.getAttribute('data-selected') === 'true' && selectedCells.length > 0) {
      let regularTotal = 0;
      let overtimeTotal = 0;
      let grossTotal = 0;
      let netTotal = 0;

      selectedCells.forEach((selectedCell) => {
        const selectedEntries = getEntriesFromCalendarCell(selectedCell);
        const selectedDateId = selectedCell.getAttribute('data-id') || '';
        const cellTotals = computeCalendarTotals(selectedEntries, selectedDateId);
        regularTotal += cellTotals.regularTotal;
        overtimeTotal += cellTotals.overtimeTotal;
        grossTotal += cellTotals.grossTotal;
        netTotal += cellTotals.netTotal;
      });

      const grossLabel = calendarI18n('GROSS', 'Gross');
      const deductionsLabel = calendarI18n('DEDUCTIONS', 'Deductions');
      const netLabel = calendarI18n('NET', 'Net');
      const rangeTitle = [
        `Regular: ${formatHourValue(regularTotal)}`,
        `Overtime: ${formatHourValue(overtimeTotal)}`,
        `${grossLabel}: $${formatMoneyValue(grossTotal)}`,
        `${deductionsLabel}: $${formatMoneyValue(Math.max(0, grossTotal - netTotal))}`,
        `${netLabel}: $${formatMoneyValue(netTotal)}`,
      ].join(' | ');
      selectedCells.forEach((selectedCell) => {
        selectedCell.setAttribute('data-tooltip', rangeTitle);
        selectedCell.removeAttribute('title');
      });
      return;
    }

    let sourceEntries = entries;
    if (!Array.isArray(sourceEntries)) {
      sourceEntries = getEntriesFromCalendarCell(cell);
    }

    const dateId = cell.getAttribute('data-id') || '';
    const tooltipText = computeCalendarDayTooltip(sourceEntries, dateId);
    cell.setAttribute('data-tooltip', tooltipText);
    cell.removeAttribute('title');
    updateCalendarEarningsBadges(cell, sourceEntries, dateId);
    updateCalendarHoursBadge(cell, sourceEntries, dateId);

    if (calendarHoverTooltipCell === cell) {
      setCalendarHoverTooltipText(tooltipText);
    }
  }

  function refreshAllCalendarDayTooltips(grid) {
    if (!grid) {
      return;
    }

    grid.querySelectorAll('.datagrid_month_cell[data-id]').forEach((cell) => {
      updateCalendarDayTooltip(cell);
      updateCalendarEarningsBadges(cell);
      updateCalendarHoursBadge(cell);
    });
  }

  function getCalendarDisplayPrefs() {
    const root = document.getElementById('calendar-v2-root');
    return {
      showGross: root?.dataset.showGrossBadge === '1',
      showNet: root?.dataset.showNetBadge === '1',
      showDeductions: root?.dataset.showDeductionsBadge === '1',
    };
  }

  function getWorkEntryFieldPrefs() {
    if (typeof window.CAL_WORK_ENTRY_FIELDS === 'object' && window.CAL_WORK_ENTRY_FIELDS !== null) {
      return window.CAL_WORK_ENTRY_FIELDS;
    }

    const root = document.getElementById('calendar-v2-root');
    if (root) {
      return {
        hours: root.dataset.workEntryHours === '1',
        regular: root.dataset.workEntryRegular === '1',
        overtime: root.dataset.workEntryOvertime === '1',
        livingOut: root.dataset.workEntryLivingOut === '1',
        travel: root.dataset.workEntryTravel === '1',
      };
    }

    return {
      hours: true,
      regular: true,
      overtime: true,
      livingOut: true,
      travel: true,
    };
  }

  function buildWorkEntryDisplayFields(regularValue, overtimeValue, livingOutValue, travelValue, isEncryptedPlaceholder = false, totalValue = null) {
    const prefs = getWorkEntryFieldPrefs();

    if (isEncryptedPlaceholder) {
      const enabledCount = [
        prefs.hours,
        prefs.regular,
        prefs.overtime,
        prefs.livingOut,
        prefs.travel,
      ].filter(Boolean).length;

      return {
        fields: enabledCount > 0 ? Array(enabledCount).fill('--') : [],
        spokenMetrics: [],
      };
    }

    const fields = [];
    const spokenMetrics = [];

    if (prefs.hours) {
      const numericTotal = totalValue === null || totalValue === undefined
        ? (parseFloat(regularValue) || 0) + (parseFloat(overtimeValue) || 0)
        : totalValue;
      const value = formatHourValue(numericTotal);
      fields.push(value);
      spokenMetrics.push(`${value} total hours`);
    }
    if (prefs.regular) {
      const value = formatHourValue(regularValue);
      fields.push(value);
      spokenMetrics.push(`${value} regular hours`);
    }
    if (prefs.overtime) {
      const value = formatHourValue(overtimeValue);
      fields.push(value);
      spokenMetrics.push(`${value} overtime hours`);
    }
    if (prefs.livingOut) {
      const value = formatHourValue(livingOutValue);
      fields.push(value);
      spokenMetrics.push(`${value} living out allowance`);
    }
    if (prefs.travel) {
      const value = formatHourValue(travelValue);
      fields.push(value);
      spokenMetrics.push(`${value} travel hours`);
    }

    return { fields, spokenMetrics };
  }

  function updateCalendarEarningsBadges(cell, entries = null, dateId = '') {
    if (!cell) {
      return;
    }

    const prefs = getCalendarDisplayPrefs();
    cell.querySelectorAll('.calendar_earnings_badges').forEach((row) => row.remove());
    if (!prefs.showGross && !prefs.showNet && !prefs.showDeductions) {
      return;
    }

    const resolvedDateId = dateId || cell.getAttribute('data-id') || '';
    let sourceEntries = entries;
    if (!Array.isArray(sourceEntries)) {
      sourceEntries = getEntriesFromCalendarCell(cell);
    }

    const totals = computeCalendarTotals(sourceEntries, resolvedDateId);
    if (totals.grossTotal <= 0 && totals.netTotal <= 0 && totals.deductionsTotal <= 0) {
      return;
    }

    const badgesRow = document.createElement('div');
    badgesRow.className = 'calendar_earnings_badges';
    if (prefs.showGross && totals.grossTotal > 0) {
      const grossBadge = document.createElement('span');
      grossBadge.className = 'calendar_earnings_badge calendar_earnings_badge_gross';
      grossBadge.textContent = `$${formatMoneyValue(totals.grossTotal)}`;
      badgesRow.appendChild(grossBadge);
    }
    if (prefs.showDeductions && totals.deductionsTotal > 0) {
      const deductionsBadge = document.createElement('span');
      deductionsBadge.className = 'calendar_earnings_badge calendar_earnings_badge_deductions';
      deductionsBadge.textContent = `$${formatMoneyValue(totals.deductionsTotal)}`;
      badgesRow.appendChild(deductionsBadge);
    }
    if (prefs.showNet && totals.netTotal > 0) {
      const netBadge = document.createElement('span');
      netBadge.className = 'calendar_earnings_badge calendar_earnings_badge_net';
      netBadge.textContent = `$${formatMoneyValue(totals.netTotal)}`;
      badgesRow.appendChild(netBadge);
    }
    if (!badgesRow.childElementCount) {
      return;
    }
    cell.appendChild(badgesRow);
  }

  function updateCalendarHoursBadge(cell, entries = null, dateId = '') {
    if (!cell) {
      return;
    }

    const header = cell.querySelector('.datagrid_month_cell_header');
    if (!header) {
      return;
    }

    header.querySelectorAll('.hours-badge').forEach((badge) => badge.remove());

    const resolvedDateId = dateId || cell.getAttribute('data-id') || '';
    let sourceEntries = entries;
    if (!Array.isArray(sourceEntries)) {
      sourceEntries = getEntriesFromCalendarCell(cell);
    }

    const totals = computeCalendarTotals(sourceEntries, resolvedDateId);
    if (totals.totalHours <= 0) {
      cell.removeAttribute('data-total-hours');
      return;
    }

    const formatted = formatHourValue(totals.totalHours);
    cell.setAttribute('data-total-hours', formatted);
  }

  function renderWorkEntriesMarkup(workEntries, workEntryPosition = null, dateAria = '') {
    if (!Array.isArray(workEntries) || workEntries.length === 0) {
      return '';
    }

    // If position not provided, read from grid
    let posClass = 'left';
    if (workEntryPosition) {
      // Map 'middle' to 'center' for CSS class naming
      posClass = workEntryPosition === 'middle' ? 'center' : workEntryPosition;
    } else {
      const grid = document.getElementById('calendar-grid');
      if (grid) {
        const gridPosition = grid.dataset.workEntryPosition || 'left';
        posClass = gridPosition === 'middle' ? 'center' : gridPosition;
      }
    }

    // Apply daily 8-hour regular cap for display — ensures old entries with wrong
    // splits are shown correctly without needing a re-save.
    let displayDailyRegularUsed = 0;

    return workEntries.map(entry => {
      const siteNameRaw = (entry.site_name || entry.n || '').toString().trim();
      const siteName = escapeText(siteNameRaw);
      const spokenSiteName = siteNameRaw || calendarI18n('CALENDAR_WORK_ENTRY_LABEL', 'Work entry');
      const hasExplicitHours = (
        entry.hours !== undefined || entry.h !== undefined ||
        entry.regular_hours !== undefined || entry.r !== undefined ||
        entry.overtime_hours !== undefined || entry.o !== undefined ||
        entry.living_out_allowance !== undefined || entry.l !== undefined ||
        entry.travel_hours !== undefined || entry.t !== undefined
      );
      const isEncryptedPlaceholder = !!entry.encrypted_blob && !hasExplicitHours;

      if (isEncryptedPlaceholder) {
        const encryptedUnavailable = calendarI18n(
          'CALENDAR_ENCRYPTED_DETAILS_UNAVAILABLE',
          'Encrypted work details are unavailable in this view.'
        );
        const placeholderDisplay = buildWorkEntryDisplayFields(0, 0, 0, 0, true);
        const placeholderFieldsMarkup = placeholderDisplay.fields.length > 0
          ? `<br />${placeholderDisplay.fields.join('&nbsp;/&nbsp;')}`
          : '';
        const placeholderAria = escapeText(window.PayCalAriaEcho.cadence(
          spokenSiteName ? `${spokenSiteName}. ${encryptedUnavailable}` : encryptedUnavailable
        ));
        const placeholderSiteColorRaw = entry.site_color ? String(entry.site_color).toUpperCase() : '';
        const placeholderSiteColorValid = /^#[0-9A-Fa-f]{6}$/i.test(placeholderSiteColorRaw);
        const placeholderSiteColorAttr = placeholderSiteColorValid ? ` data-site-color="${escapeText(placeholderSiteColorRaw)}"` : '';
        return `<div class="work work_${posClass}"${placeholderSiteColorAttr} aria-label="${placeholderAria}"><strong>${siteName}</strong>${placeholderFieldsMarkup}</div>`;
      }

      const regularRaw = entry.regular_hours ?? entry.r;
      const overtimeRaw = entry.overtime_hours ?? entry.o;
      const fallbackHoursRaw = entry.hours ?? entry.h ?? 0;
      const storedRegular = parseFloat(
        (regularRaw !== undefined && regularRaw !== null)
          ? regularRaw
          : ((overtimeRaw === undefined || overtimeRaw === null) ? fallbackHoursRaw : 0)
      ) || 0;
      const storedOvertime = parseFloat(overtimeRaw ?? 0) || 0;
      const entryTotal = storedRegular + storedOvertime;
      const dailyRemaining = Math.max(0, 8 - displayDailyRegularUsed);
      const regularValue = Math.min(entryTotal, dailyRemaining);
      const overtimeValue = entryTotal - regularValue;
      displayDailyRegularUsed += regularValue;

      const displayFields = buildWorkEntryDisplayFields(
        regularValue,
        overtimeValue,
        entry.living_out_allowance ?? entry.l ?? 0,
        entry.travel_hours ?? entry.t ?? 0,
        false,
        entry.hours ?? entry.h ?? entryTotal,
      );
      const fields = displayFields.fields;
      const spokenMetrics = displayFields.spokenMetrics;

      const spokenDate = (dateAria || '').toString().trim();
      const spokenSummary = window.PayCalAriaEcho.cadence(spokenMetrics, ', ');
      const lead = spokenDate ? `${spokenSiteName} on ${spokenDate}` : spokenSiteName;
      const entryAriaLabel = escapeText(window.PayCalAriaEcho.cadence(
        spokenSummary ? `${lead}. ${spokenSummary}.` : `${lead}.`
      ));

      const siteColorRaw = (entry.site_color || entry.siteColor || entry.sc || entry.c) ? String(entry.site_color || entry.siteColor || entry.sc || entry.c).toUpperCase() : '';
      const siteColorValid = /^#[0-9A-Fa-f]{6}$/i.test(siteColorRaw);
      const siteColorAttr = siteColorValid ? ` data-site-color="${escapeText(siteColorRaw)}"` : '';
      const fieldsMarkup = fields.length > 0 ? `<br />${fields.join('&nbsp;/&nbsp;')}` : '';
      return `<div class="work work_${posClass}"${siteColorAttr} aria-label="${entryAriaLabel}"><strong>${siteName}</strong>${fieldsMarkup}</div>`;

    }).join('');
  }

  async function updateGridCellFromWeekPayload(activeDate, weekData) {
    if (!weekData || !weekData.days) {
      throw new Error('Calendar save response is missing week reconciliation payload.');
    }

    // Get work entry position from grid
    const grid = document.getElementById('calendar-grid');
    const workEntryPosition = grid ? grid.dataset.workEntryPosition : 'left';

    // Phase 1 (atomic prep): validate and decrypt every entry for the week first.
    // If any day fails integrity/decryption checks, abort without mutating UI state.
    const decryptedWeekByDate = Object.create(null);

    for (const [dateId, workEntries] of Object.entries(weekData.days)) {
      const cell = document.querySelector(`.datagrid_month_cell[data-id="${dateId}"]`);
      if (!cell) {
        modalLog('[Calendar Modal] Cell not found for date', dateId);
        continue;
      }

      const safeEntries = Array.isArray(workEntries) ? workEntries : [];
      const decryptedEntries = [];
      for (const entry of safeEntries) {
        if (!entry || typeof entry !== 'object' || typeof entry.encrypted_blob !== 'string' || entry.encrypted_blob.trim() === '') {
          throw new Error(`Week payload integrity error for ${dateId}: missing encrypted_blob.`);
        }

        let decrypted = null;
        try {
          decrypted = await decryptEntry(entry);
        } catch (decryptError) {
          if (decryptError?.code === 'paycal_decrypt_unlock_mismatch') {
            throw buildEncryptedWorkUnlockError(dateId, decryptError);
          }
          throw decryptError;
        }
        if (!decrypted || typeof decrypted !== 'object') {
          throw new Error(`Week payload decrypt returned empty data for ${dateId}.`);
        }

        // Merge site_color from outer entry metadata into decrypted result
        if (entry.site_color && !decrypted.site_color) {
          decrypted.site_color = entry.site_color;
        }

        window.PayCalWorkIntegrity?.check(decrypted, { context: 'decrypt', date: dateId });
        decryptedEntries.push(decrypted);
      }

      decryptedWeekByDate[dateId] = decryptedEntries;
    }

    // Phase 2 (single apply): update all week cells only after full validation.
    for (const [dateId, decryptedEntries] of Object.entries(decryptedWeekByDate)) {
      const cell = document.querySelector(`.datagrid_month_cell[data-id="${dateId}"]`);
      if (!cell) {
        continue;
      }

      // Invalidate stale server-cached daily earnings so the tooltip
      // recomputes gross/net from the fresh entries.
      delete calendarDailyEarningsByDate[dateId];

      cell.setAttribute('data-work-entries', JSON.stringify(decryptedEntries));
      updateCalendarDayTooltip(cell, decryptedEntries);

      const content = cell.querySelector('.datagrid_month_cell_content');
      if (!content) {
        modalLog('[Calendar Modal] Missing .datagrid_month_cell_content for date', dateId);
        continue;
      }

      const dateAria = cell.getAttribute('data-date-aria') || cell.getAttribute('data-date') || dateId;
      Guardian.setHTML(content, renderWorkEntriesMarkup(decryptedEntries, workEntryPosition, dateAria));
      if (dateId === activeDate) {
        markCalendarCellSaveState(activeCellForDate(dateId), 'committed');
      }
      modalLog('[Calendar Modal] Grid cell refreshed', { dateId, entryCount: decryptedEntries.length });
    }
  }

  function activeCellForDate(dateId) {
    if (!dateId) {
      return null;
    }
    return document.querySelector(`.datagrid_month_cell[data-id="${dateId}"]`);
  }

  function markCalendarCellSaveState(cell, state) {
    if (!(cell instanceof HTMLElement)) {
      return;
    }

    window.clearTimeout(Number(cell.dataset.saveStateTimer || 0));
    cell.classList.remove('calendar_cell_save_pending', 'calendar_cell_save_committed', 'calendar_cell_save_error');
    delete cell.dataset.saveState;

    if (!state) {
      delete cell.dataset.saveStateTimer;
      return;
    }

    cell.dataset.saveState = state;
    cell.classList.add(`calendar_cell_save_${state}`);

    const timeoutMs = state === 'pending' ? 0 : 1800;
    if (timeoutMs > 0) {
      cell.dataset.saveStateTimer = String(window.setTimeout(() => {
        cell.classList.remove(`calendar_cell_save_${state}`);
        delete cell.dataset.saveState;
        delete cell.dataset.saveStateTimer;
      }, timeoutMs));
    }
  }

  function updateGridCellFromEntries(activeDate, entries) {
    const activeCell = document.querySelector(`.datagrid_month_cell[data-id="${activeDate}"]`);
    if (!activeCell) {
      modalLog('[Calendar Modal] Missing cell for optimistic update', { activeDate });
      return;
    }

    // Invalidate stale server-cached daily earnings so the tooltip
    // recomputes gross/net immediately from the optimistic entries.
    delete calendarDailyEarningsByDate[activeDate];

    const safeEntries = Array.isArray(entries) ? entries : [];
    activeCell.setAttribute('data-work-entries', JSON.stringify(safeEntries));
    updateCalendarDayTooltip(activeCell, safeEntries);

    const content = activeCell.querySelector('.datagrid_month_cell_content');
    if (!content) {
      modalLog('[Calendar Modal] Missing .datagrid_month_cell_content for optimistic update', { activeDate });
      return;
    }

    // Get work entry position from grid and apply to rendered entries
    const grid = document.getElementById('calendar-grid');
    const workEntryPosition = grid ? grid.dataset.workEntryPosition : 'left';
    
    const activeDateAria = activeCell.getAttribute('data-date-aria') || activeCell.getAttribute('data-date') || activeDate;
    Guardian.setHTML(content, renderWorkEntriesMarkup(safeEntries, workEntryPosition, activeDateAria));
    modalLog('[Calendar Modal] Grid cell optimistically updated', { activeDate, entryCount: safeEntries.length });
  }

  async function saveEntriesForDate(activeDate, entries) {
    if (calendarLockedForVerification || !isCalendarEmailVerified()) {
      throw new Error(calendarI18n(
        'CALENDAR_EMAIL_VERIFICATION_REQUIRED',
        'Email verification required. Use the banner above to enter your code or resend verification email.'
      ));
    }

    coreLog('[Calendar Save] saveEntriesForDate start', { activeDate, count: Array.isArray(entries) ? entries.length : 0 });

    const previousEntries = getDayEntriesFromCell(activeDate);
    let optimisticApplied = false;

    try {
      if (!(await ensurePayCalDEK())) {
        throw new Error(calendarI18n(
          'CALENDAR_UNLOCK_REQUIRED_SAVE',
          'Unlock required before save. Use passkey unlock and then save again.'
        ));
      }

      await assertVisibleEncryptedWeekDecryptable(activeDate);

      const optimisticEntries = normalizeEntriesForSave(entries);
      updateGridCellFromEntries(activeDate, optimisticEntries);
      markCalendarCellSaveState(activeCellForDate(activeDate), 'pending');
      optimisticApplied = true;

      const encryptedEntries = [];
      for (const entry of entries) {
        window.PayCalWorkIntegrity?.check(entry, { context: 'save', date: activeDate });
        encryptedEntries.push(await encryptEntry(entry));
      }

      coreLog('[Calendar Save] nonce fetch start', { activeDate });
      const csrfToken = await fetchCalendarNonce();
      coreLog('[Calendar Save] nonce received', { hasToken: !!csrfToken });

      const formData = new FormData();
      formData.append('entries', JSON.stringify(encryptedEntries));
      formData.append('d', activeDate);
      formData.append('cal_work_save_as_default', 'false');
      formData.append('csrf_token', csrfToken);

      coreLog('[Calendar Save] update fetch start', { activeDate, entryCount: encryptedEntries.length });
      const saveResponse = await fetchWithTimeout('/api/v1/calendar/update/', {
        method: 'POST',
        credentials: 'include',
        body: formData,
      });
      coreLog('[Calendar Save] update fetch response', { status: saveResponse.status, ok: saveResponse.ok });

      if (!saveResponse.ok) {
        const errorText = await saveResponse.text();
        throw new Error(`Save HTTP ${saveResponse.status}: ${errorText}`);
      }

      const rawResponse = await saveResponse.text();
      coreLog('[Calendar Save] update raw response', {
        length: rawResponse.length,
        preview: rawResponse.slice(0, 300),
      });

      let savePayload;
      try {
        savePayload = JSON.parse(rawResponse);
      } catch (parseError) {
        console.error('[Calendar Save] Failed to parse update response JSON', {
          parseError,
          rawLength: rawResponse.length,
          rawPreview: rawResponse.slice(0, 500),
        });
        throw parseError;
      }

      coreLog('[Calendar Save] saveEntriesForDate response', savePayload);

      // Check for ENTRY_LOCKED status from backend
      if (savePayload?.status === 'ENTRY_LOCKED' || savePayload?.data?.status === 'ENTRY_LOCKED') {
        const lockedDate = savePayload?.date || savePayload?.data?.date || activeDate;
        throw new Error(formatLockedDateMessage(formatStatusDateLabel(lockedDate)));
      }

      const weekPayload = savePayload?.week || savePayload?.data?.week || null;
      if (weekPayload) {
        await updateGridCellFromWeekPayload(activeDate, weekPayload);
        markCalendarCellSaveState(activeCellForDate(activeDate), 'committed');
      } else {
        throw new Error('Calendar save response is missing week reconciliation payload.');
      }

      return savePayload;
    } catch (error) {
      if (optimisticApplied) {
        updateGridCellFromEntries(activeDate, previousEntries);
        markCalendarCellSaveState(activeCellForDate(activeDate), 'error');
        coreLog('[Calendar Save] Optimistic update rolled back', { activeDate, restoredCount: previousEntries.length });
      }
      console.error('[Calendar Save] saveEntriesForDate failed', { activeDate, error });
      throw error;
    }
  }
  
  /**
   * Handle saving work entries from the form.
   */
  async function handleWorkEntrySave() {
    const tbody = document.getElementById('work-entries-tbody');
    if (!tbody) return;

    const modal = document.getElementById('calendar-modal');
    const activeDate = modal ? modal.getAttribute('data-active-date') : '';
    if (!activeDate) {
      console.error('[Calendar Modal] Missing active date for save');
      return;
    }

    const saveBtn = modal.querySelector('[data-action="save"]');
    if (saveBtn) {
      saveBtn.disabled = true;
      saveBtn.textContent = calendarI18n('CALENDAR_SAVING_ELLIPSIS', 'Saving...');
    }
    
    const rows = tbody.querySelectorAll('.work-entry-row');
    const entries = [];
    
    rows.forEach(row => {
      const siteSelect = row.querySelector('.entry-site-select');
      const regularInput = row.querySelector('[name^="regular_"]');
      const overtimeInput = row.querySelector('[name^="overtime_"]');
      const loaInput = row.querySelector('[name^="loa_"]');
      const travelInput = row.querySelector('[name^="travel_"]');
      
      const siteId = siteSelect ? siteSelect.value : '';
      const siteName = siteSelect && siteSelect.selectedOptions && siteSelect.selectedOptions[0]
        ? siteSelect.selectedOptions[0].textContent.trim()
        : '';
      const regular = regularInput ? parseFloat(regularInput.value) || 0 : 0;
      const overtime = overtimeInput ? parseFloat(overtimeInput.value) || 0 : 0;
      const loa = loaInput ? parseFloat(loaInput.value) || 0 : 0;
      const travel = travelInput ? parseFloat(travelInput.value) || 0 : 0;
      const hours = regular + overtime;
      
      // Only add if site is selected and at least one hour is entered
      if (siteId && siteId !== '' && (hours + loa + travel) > 0) {
        entries.push({
          site_id: siteId,
          site_name: siteName,
          hours,
          regular_hours: regular,
          overtime_hours: overtime,
          living_out_allowance: loa,
          travel_hours: travel
        });
      }
    });
    
    // Apply daily 8-hour regular cap (mirrors server-side Work::calculateHours).
    // Server-side recalculation is skipped in encryption mode, so we must enforce
    // the cap here before encrypting.  Entries are processed in order; dailyRegularUsed
    // accumulates to distribute the cap across multiple entries for the same day.
    {
      let dailyRegularUsed = 0;
      for (const e of entries) {
        const total = e.regular_hours + e.overtime_hours;
        const remaining = Math.max(0, 8 - dailyRegularUsed);
        const reg = Math.min(total, remaining);
        const ot = total - reg;
        e.regular_hours = reg;
        e.overtime_hours = ot;
        e.hours = total;
        dailyRegularUsed += reg;
        applyWorkEntryEarningsSnapshot(e);
      }
    }

    modalLog('[Calendar Modal] Saving entries:', entries);
    const activeDateLabel = formatStatusDateLabel(activeDate);
    PayCalCore.updateStatusMessage(
      calendarI18nFormat('CALENDAR_SAVING_ENTRIES_FOR', 'Saving {count} entry/entries for {dateLabel}...', {
        count: entries.length,
        dateLabel: activeDateLabel,
      }),
      'save',
      0
    );

    try {
      const savePayload = await saveEntriesForDate(activeDate, entries);
      modalLog('[Calendar Modal] Save API success:', savePayload);
      
      if (savePayload && savePayload.diagnostic) {
        modalLog('[Calendar Modal] Diagnostic info:', savePayload.diagnostic);
      }

      PayCalCore.updateStatusMessage(
        calendarI18nFormat('CALENDAR_SAVED_ENTRIES_FOR', 'Saved {count} entry/entries for {dateLabel}', {
          count: entries.length,
          dateLabel: activeDateLabel,
        }),
        'save',
        3000
      );
      modalLog('[Calendar Modal] Save successful, closing modal');
      closeModal();
    } catch (error) {
      console.error('[Calendar Modal] Save API failed:', error);
      // Re-enable elements on error so user can retry
      setModalElementsDisabled(false);
      const errorMessage = error && error.message ? error.message : calendarI18n('CALENDAR_UNKNOWN_ERROR', 'Unknown save error');
      PayCalCore.updateStatusMessage(
        calendarI18nFormat('CALENDAR_SAVE_FAILED_FOR', 'Save failed for {dateLabel}: {error}', {
          dateLabel: activeDateLabel,
          error: errorMessage,
        }),
        'error',
        4000
      );
      const content = modal.querySelector('#calendar-modal-content');
      if (content) {
        Guardian.insertHTML(content, 'afterbegin', `
          <div class="success-message calendar-save-error">
            <p><strong>${escapeText(calendarI18n('CALENDAR_SAVE_FAILED_SHORT', 'Save failed'))}</strong></p>
            <p>${escapeText(errorMessage)}</p>
          </div>
        `);
      }
    } finally {
      if (saveBtn) {
        saveBtn.disabled = false;
        saveBtn.textContent = calendarI18n('SAVE', 'Save');
      }
    }
  }


  // =========================================================================
  // PAGE LIFECYCLE
  // =========================================================================

  bindCryptoLifecycleZeroization();

  // Boot on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  // Expose global crypto API for use by other scripts (e.g., settings.js for passkey registration)
  window.PayCalCrypto = {
    // Expose function to wrap DEK with passkey credential_id (called after passkey registration)
    // CRITICAL: Uses stable credential_id for deterministic KEK derivation
    wrapDEKWithPasskeyCredential: wrapDEKWithPasskeyCredential,
    ensureDEK: ensurePayCalDEK,
    createRecoveryMaterial: createRecoveryMaterial,
    capturePlaintextWorkEntries: capturePlaintextWorkEntries,
    // Expose worker-backed unlock state only (DEK is never exposed to main thread)
    get hasDek() { return PayCalCryptoState.hasDek; },
    get dekVersion() { return PayCalCryptoState.dekVersion; },
    get cryptoVersion() { return PayCalCryptoState.cryptoVersion; },
    clear: async () => {
      await zeroizeCryptoState('explicit_clear', { strict: true });
    },
  };

  const payCalEnableTestHooks = (() => {
    const devHostPattern = /^(dev\.paycal\.local|localhost|127\.0\.0\.1)$/;
    return devHostPattern.test(window.location.hostname) && window.__PAYCAL_ENABLE_TEST_HOOKS === true;
  })();

  if (payCalEnableTestHooks) {
    window.__PAYCAL_TEST_HOOKS = {
      forceHasDek(value) {
        const enabled = !!value;
        PayCalCryptoState.hasDek = enabled;
        if (!enabled) {
          resetMainThreadCryptoState();
          return;
        }

        armCryptoIdleTimer();
      },
      setProfileMarker(value) {
        if (value === null || value === undefined || value === '') {
          delete window.PAYCAL_USER_PROFILE_ENCRYPTED;
          PayCalCryptoState.profileEncrypted = false;
          return;
        }

        window.PAYCAL_USER_PROFILE_ENCRYPTED = value;
        PayCalCryptoState.profileEncrypted = true;
      },
      getState() {
        return {
          hasDek: PayCalCryptoState.hasDek,
          profileMarker: window.PAYCAL_USER_PROFILE_ENCRYPTED,
        };
      },
    };
  }

  cryptoLog('[CRYPTO] Global PayCalCrypto API exposed (stable credential_id-based KEK)');

})();
