<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__ . '/../../config.php';

CORS::handleORIGIN();
CORS::renderContentType('application/javascript; charset=utf-8');

$recoveryI18nKeys = [
  'AUTH_RECOVER_SEND_CODE',
  'AUTH_JS_WEBAUTHN_UNSUPPORTED',
  'AUTH_JS_RECOVER_SEND_CODE_FMT',
  'AUTH_JS_RECOVER_CANCEL_FAILED',
  'AUTH_JS_CONFIRM_DEVICE',
];
$recoveryI18n = [];
foreach ($recoveryI18nKeys as $recoveryI18nKey) {
  $recoveryI18n[$recoveryI18nKey] = Strings::i18n($recoveryI18nKey);
}

?>
import { fromBase64Url as b64urlToBuffer, toBase64Url as bufferToB64url } from '/js/core/binary-codec.js';
import { isWebAuthnCapableBrowser } from '/js/core/capabilities.js';
import { setActionBusy } from '/js/core/actions.js';
import {
  PAYCAL_EMAIL_SECRET_LENGTH,
  PAYCAL_RECOVERY_SECRET_LENGTH,
  formatRecoveryCode,
  formatVerificationCode,
  getPayCalCodeValidationState,
  normalizePayCalCode,
} from '/js/core/paycal-code.js';
import { formatTemplate as formatRecoveryMessage } from '/js/core/template.js';

(function () {
  const RECOVERY_PREFILL_SESSION_KEY = 'paycal.recovery.prefill';
  const state = {
    txnId: '',
    txnSecret: '',
    proofPayload: null,
    bootstrap: null,
    credentialId: '',
    magicLinkVerified: false,
    emailCodeVerified: false,
    bootstrapReady: false,
  };

  const startForm = document.getElementById('recovery-start-form');
  const verifyForm = document.getElementById('recovery-verify-form');
  const sendCodeButton = document.getElementById('recovery-send-code');
  const backToSigninButton = document.getElementById('recovery-back-signin');
  const cancelButton = document.getElementById('recovery-cancel');
  const registerButton = document.getElementById('recovery-register-passkey');
  const statusEl = document.getElementById('recovery-status');
  const emailInput = document.getElementById('recovery-email');
  const codeInput = document.getElementById('recovery-code');
  const recoveryCodeBlock = document.getElementById('recovery-code-block');
  const recoveryKeyBlock = document.getElementById('recovery-key-block');
  const recoveryKeyInput = document.getElementById('recovery-key');
  const codeErrorEl = document.getElementById('recovery-code-error');
  const recoveryKeyErrorEl = document.getElementById('recovery-key-error');
  const verifySubmitButton = verifyForm?.querySelector('button[type="submit"]');
  const deviceNameInput = document.getElementById('recovery-device-name');
  const workerVersion = document.body?.dataset?.workerVersion || String(Date.now());
  const RECOVERY_T = <?php echo json_encode($recoveryI18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
  const WEB_AUTHN_UNSUPPORTED_MESSAGE = RECOVERY_T.AUTH_JS_WEBAUTHN_UNSUPPORTED;
  const CHECKSUM_ERROR = 'Check the last two characters.';
  const INVALID_CHAR_ERROR = 'Use only PayCal code characters: ABCDEFGHJKLMNPQRTUWXYZ346789.';
  const AUTOSUBMIT_DEBOUNCE_MS = 500;

  let worker = null;
  let workerRequestId = 0;
  const workerPending = new Map();
  let sendCooldownTimer = null;
  let startInFlight = false;
  let verifyInFlight = false;
  let autoSubmitTimer = null;
  let lastAutoSubmitPair = '';
  let registerInFlight = false;
  let cancelInFlight = false;

  function authUrlWithLanguage(extraParams = {}) {
    const current = new URL(window.location.href);
    const language = String(current.searchParams.get('l') || '').trim();
    const target = new URL('/auth/', window.location.origin);
    if (language !== '') {
      target.searchParams.set('l', language);
    }
    Object.entries(extraParams).forEach(([key, value]) => {
      if (typeof value === 'string' && value !== '') {
        target.searchParams.set(key, value);
      }
    });
    return target.toString();
  }

  function setStatus(message, tone = 'default') {
    if (statusEl) {
      statusEl.textContent = message;
      statusEl.dataset.tone = tone;
    }
  }

  function emailLooksReady() {
    return Boolean(emailInput?.value?.trim());
  }

  function setVerifySubmitEnabled(enabled) {
    if (!verifySubmitButton) {
      return;
    }
    verifySubmitButton.disabled = !enabled;
    if (enabled) {
      verifySubmitButton.removeAttribute('aria-disabled');
    } else {
      verifySubmitButton.setAttribute('aria-disabled', 'true');
    }
  }

  function setFieldMessage(input, errorEl, message, tone = 'error') {
    if (errorEl) {
      errorEl.textContent = message;
      errorEl.dataset.tone = message ? tone : '';
    }
    if (input) {
      input.setAttribute('aria-invalid', message && tone === 'error' ? 'true' : 'false');
    }
  }

  function validateVisibleRecoveryFields(showErrors = false) {
    const codeRequired = !state.emailCodeVerified && !state.magicLinkVerified;
    const codeState = getPayCalCodeValidationState(codeInput?.value || '', PAYCAL_EMAIL_SECRET_LENGTH);
    const keyState = getPayCalCodeValidationState(recoveryKeyInput?.value || '', PAYCAL_RECOVERY_SECRET_LENGTH);
    const messageFor = (state, validMessage) => {
      if (state === 'valid') {
        return { message: validMessage, tone: 'good' };
      }
      if (state === 'empty' || state === 'incomplete') {
        return { message: '', tone: 'error' };
      }
      return {
        message: state === 'invalid-char' ? INVALID_CHAR_ERROR : CHECKSUM_ERROR,
        tone: 'error',
      };
    };
    const codeMessage = messageFor(codeState, 'Verification code looks good.');
    const keyMessage = messageFor(keyState, 'Recovery code looks good.');

    const shouldShowCodeMessage = codeRequired && (showErrors || codeState === 'valid' || codeState === 'invalid-char' || codeState === 'checksum');
    const shouldShowKeyMessage = showErrors || keyState === 'valid' || keyState === 'invalid-char' || keyState === 'checksum';

    setFieldMessage(codeInput, codeErrorEl, shouldShowCodeMessage ? codeMessage.message : '', codeMessage.tone);
    setFieldMessage(recoveryKeyInput, recoveryKeyErrorEl, shouldShowKeyMessage ? keyMessage.message : '', keyMessage.tone);

    return (!codeRequired || codeState === 'valid') && keyState === 'valid';
  }

  function updateVerifyButtonState() {
    const ready = emailLooksReady() && validateVisibleRecoveryFields(false);
    setVerifySubmitEnabled(ready);
    return ready;
  }

  function scheduleAutoSubmit() {
    if (autoSubmitTimer) {
      window.clearTimeout(autoSubmitTimer);
      autoSubmitTimer = null;
    }
    if (verifyInFlight || state.bootstrapReady || state.txnId === '' || state.txnSecret === '' || !validateVisibleRecoveryFields(false)) {
      return;
    }

    autoSubmitTimer = window.setTimeout(() => {
      const emailProof = state.emailCodeVerified || state.magicLinkVerified ? 'email-verified' : normalizePayCalCode(codeInput?.value || '');
      const pair = `${emailProof}:${normalizePayCalCode(recoveryKeyInput?.value || '')}`;
      if (verifyInFlight || state.bootstrapReady || state.txnId === '' || state.txnSecret === '' || pair === lastAutoSubmitPair || !validateVisibleRecoveryFields(true)) {
        return;
      }
      lastAutoSubmitPair = pair;
      setStatus('Checking...');
      verifyForm?.requestSubmit();
    }, AUTOSUBMIT_DEBOUNCE_MS);
  }

  function isNewLinkRecoverableError(error) {
    const message = String(error?.message || error || '');
    return /Recovery bootstrap unavailable|Recovery link is invalid or expired/i.test(message);
  }

  function userRecoveryErrorMessage(error, fallback = 'Recovery failed. Try again.') {
    const message = String(error?.message || '');
    if (/Recovery bootstrap unavailable/i.test(message)) {
      return 'This recovery link is not ready anymore. Request a new passkey setup link and try again.';
    }
    if (/Recovery link is invalid or expired/i.test(message)) {
      return 'This recovery link is invalid or expired. Request a new passkey setup link and try again.';
    }
    return message || fallback;
  }

  async function requestNewRecoveryLink() {
    clearRecoveryPrefill();
    state.txnId = '';
    state.txnSecret = '';
    state.proofPayload = null;
    state.bootstrap = null;
    state.credentialId = '';
    state.magicLinkVerified = false;
    state.emailCodeVerified = false;
    state.bootstrapReady = false;

    startForm?.classList.remove('is-hidden');
    verifyForm?.classList.remove('is-hidden');
    resetRecoveryCodeInput();
    showRecoveryKeyInput();
    setStep(1);

    if (!emailInput?.value?.trim()) {
      setStatus('Enter your account email to request a new recovery link.', 'error');
      emailInput?.focus();
      return;
    }

    await startRecovery({ preventDefault() {} });
  }

  function setRecoveryErrorStatus(error, fallback = 'Recovery failed. Try again.') {
    const message = userRecoveryErrorMessage(error, fallback);
    if (/Recovery Code does not match this account|Recovery code does not match this account/i.test(message)) {
      setFieldMessage(recoveryKeyInput, recoveryKeyErrorEl, 'This recovery code does not match this account.', 'error');
    }
    if (!statusEl || !isNewLinkRecoverableError(error)) {
      setStatus(message, 'error');
      return;
    }

    statusEl.textContent = '';
    statusEl.dataset.tone = 'error';

    const text = document.createElement('span');
    text.textContent = message;
    statusEl.appendChild(text);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'recovery-status-action';
    button.textContent = 'Request New Link';
    button.addEventListener('click', () => {
      setActionBusy(button, true, { ariaBusy: false });
      requestNewRecoveryLink().catch((requestError) => {
        setActionBusy(button, false, { ariaBusy: false });
        setRecoveryErrorStatus(requestError, 'Could not request a new recovery link. Try again.');
      });
    });
    statusEl.appendChild(button);
  }

  function clearRecoveryPrefill() {
    try {
      window.sessionStorage.removeItem(RECOVERY_PREFILL_SESSION_KEY);
    } catch (_error) {
      // Ignore storage cleanup errors.
    }
  }

  function storeRecoveryPrefill(payload) {
    try {
      window.sessionStorage.setItem(RECOVERY_PREFILL_SESSION_KEY, JSON.stringify({
        ...payload,
        createdAt: Date.now(),
      }));
    } catch (_error) {
      // If sessionStorage is blocked, continue with in-memory state.
    }
  }

  function startSendCooldown(seconds) {
    if (!sendCodeButton) {
      return;
    }
    if (sendCooldownTimer) {
      window.clearInterval(sendCooldownTimer);
      sendCooldownTimer = null;
    }

    const formatCooldown = (totalSeconds) => {
      const mins = Math.floor(totalSeconds / 60);
      const secs = totalSeconds % 60;
      return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    };

    let remaining = Math.max(0, Number(seconds) || 0);
    sendCodeButton.disabled = true;
    sendCodeButton.textContent = formatRecoveryMessage(RECOVERY_T.AUTH_JS_RECOVER_SEND_CODE_FMT, { time: formatCooldown(remaining) });

    sendCooldownTimer = window.setInterval(() => {
      remaining -= 1;
      if (remaining <= 0) {
        window.clearInterval(sendCooldownTimer);
        sendCooldownTimer = null;
        sendCodeButton.disabled = false;
        sendCodeButton.textContent = RECOVERY_T.AUTH_RECOVER_SEND_CODE;
        return;
      }
      sendCodeButton.textContent = formatRecoveryMessage(RECOVERY_T.AUTH_JS_RECOVER_SEND_CODE_FMT, { time: formatCooldown(remaining) });
    }, 1000);
  }

  function setStep(step) {
    document.querySelectorAll('[data-step]').forEach((panel) => {
      panel.classList.toggle('is-hidden', panel.getAttribute('data-step') !== String(step));
    });
    document.querySelectorAll('[data-step-indicator]').forEach((item) => {
      item.classList.toggle('is-active', item.getAttribute('data-step-indicator') === String(step));
    });

    if (!registerButton) {
      return;
    }

    if (step !== 2) {
      registerButton.disabled = true;
      registerButton.setAttribute('aria-disabled', 'true');
      return;
    }

    if (!isWebAuthnCapableBrowser()) {
      registerButton.disabled = true;
      registerButton.setAttribute('aria-disabled', 'true');
      setStatus(WEB_AUTHN_UNSUPPORTED_MESSAGE, 'error');
      return;
    }

    const ready = state.bootstrapReady && state.txnId !== '' && state.txnSecret !== '' && state.bootstrap !== null;
    registerButton.disabled = !ready;
    if (ready) {
      registerButton.removeAttribute('aria-disabled');
    } else {
      registerButton.setAttribute('aria-disabled', 'true');
    }
  }

  async function prepareMagicLinkPasskeyRegistration(successMessage = 'Recovery link verified. Create your new passkey now.') {
    state.bootstrap = await postJson('/api/v1/auth/recovery/bootstrap', {
      txnId: state.txnId,
      txnSecret: state.txnSecret,
    });
    state.bootstrapReady = true;

    startForm?.classList.add('is-hidden');
    verifyForm?.classList.add('is-hidden');
    hideRecoveryCodeInput();
    hideRecoveryKeyInput();
    setStep(2);
    setStatus(successMessage, 'sent');
  }

  function hideRecoveryCodeInput() {
    if (recoveryCodeBlock) {
      recoveryCodeBlock.classList.add('is-hidden');
    }
    if (codeInput) {
      codeInput.required = false;
      codeInput.value = '';
      codeInput.disabled = true;
    }
  }

  function resetRecoveryCodeInput() {
    if (recoveryCodeBlock) {
      recoveryCodeBlock.classList.remove('is-hidden');
    }
    if (codeInput) {
      codeInput.required = true;
      codeInput.disabled = false;
      codeInput.value = formatVerificationCode(codeInput.value);
    }
    setFieldMessage(codeInput, codeErrorEl, '');
    updateVerifyButtonState();
  }

  function showRecoveryKeyInput() {
    if (codeInput) {
      codeInput.required = true;
      codeInput.disabled = false;
    }
    if (recoveryKeyBlock) {
      recoveryKeyBlock.classList.remove('is-hidden');
    }
    if (recoveryKeyInput) {
      recoveryKeyInput.required = true;
      recoveryKeyInput.disabled = false;
      recoveryKeyInput.value = formatRecoveryCode(recoveryKeyInput.value);
    }
    setFieldMessage(recoveryKeyInput, recoveryKeyErrorEl, '');
    updateVerifyButtonState();
  }

  function hideRecoveryKeyInput() {
    if (recoveryKeyBlock) {
      recoveryKeyBlock.classList.add('is-hidden');
    }
    if (recoveryKeyInput) {
      recoveryKeyInput.required = false;
      recoveryKeyInput.disabled = true;
      recoveryKeyInput.value = '';
    }
    setFieldMessage(recoveryKeyInput, recoveryKeyErrorEl, '');
    updateVerifyButtonState();
  }

  function ensureWorker() {
    if (worker) {
      return worker;
    }

    worker = new Worker(`/js/calendar/crypto-worker.js?v=${encodeURIComponent(workerVersion)}`, { type: 'module' });
    worker.onmessage = (event) => {
      const payload = event.data || {};
      const pending = workerPending.get(payload.id);
      if (!pending) {
        return;
      }
      workerPending.delete(payload.id);
      if (payload.ok) {
        pending.resolve(payload.result);
      } else {
        const fallback = payload.details || JSON.stringify(payload.diagnostics || {});
        pending.reject(new Error(payload.error || fallback || 'Crypto worker failure'));
      }
    };

    return worker;
  }

  function callWorker(action, payload = {}) {
    const currentWorker = ensureWorker();
    const requestId = ++workerRequestId;
    return new Promise((resolve, reject) => {
      workerPending.set(requestId, { resolve, reject });
      currentWorker.postMessage({ id: requestId, action, payload });
    });
  }

  async function postJson(url, body, timeoutMs = 15000) {
    const controller = new AbortController();
    const timerId = window.setTimeout(() => controller.abort(), timeoutMs);

    let response;
    try {
      response = await fetch(url, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(body),
        signal: controller.signal,
      });
    } catch (error) {
      if (error instanceof DOMException && error.name === 'AbortError') {
        throw new Error('Request timed out. Try again.');
      }
      throw new Error('Network issue. Try again.');
    } finally {
      window.clearTimeout(timerId);
    }

    const raw = await response.text();
    let payload = null;
    if (raw.trim() !== '') {
      try {
        payload = JSON.parse(raw);
      } catch (_error) {
        payload = null;
      }
    }

    if (!response.ok || !payload || payload.status !== 'success') {
      const fallback = !response.ok ? `Request failed (${response.status}).` : 'Request failed.';
      throw new Error(String(payload?.message || fallback));
    }
    return payload;
  }

  async function startRecovery(event) {
    event.preventDefault();
    if (startInFlight) {
      return;
    }
    startInFlight = true;
    setStatus('Sending code…');
    setActionBusy(sendCodeButton, true);
    try {
      const payload = await postJson('/api/v1/auth/recovery/start', { email: emailInput.value.trim() });
      state.txnId = payload?.txnId || '';
      state.txnSecret = payload?.txnSecret || '';
      state.emailCodeVerified = false;
      state.magicLinkVerified = false;
      state.bootstrapReady = false;
      state.bootstrap = null;
      verifyForm?.classList.remove('is-hidden');
      resetRecoveryCodeInput();
      showRecoveryKeyInput();
      updateVerifyButtonState();
      setStatus('Code sent. Check your email.', 'sent');
      startSendCooldown(60);
      codeInput?.focus();
      scheduleAutoSubmit();
    } catch (error) {
      setActionBusy(sendCodeButton, false, { ariaBusy: false });
      throw error;
    } finally {
      startInFlight = false;
      setActionBusy(sendCodeButton, false, { ariaBusyWhenIdle: false, disable: false });
    }
  }

  async function verifyRecovery(event) {
    event.preventDefault();
    if (verifyInFlight) {
      return;
    }
    if (!state.magicLinkVerified && (state.txnId === '' || state.txnSecret === '')) {
      setStatus('Send an email code first.', 'error');
      emailInput?.focus();
      return;
    }
    if (!validateVisibleRecoveryFields(true)) {
      setStatus(CHECKSUM_ERROR, 'error');
      updateVerifyButtonState();
      return;
    }
    verifyInFlight = true;
    if (codeInput && !state.magicLinkVerified && !state.emailCodeVerified) codeInput.disabled = true;
    if (recoveryKeyInput && state.emailCodeVerified) recoveryKeyInput.disabled = true;
    setVerifySubmitEnabled(false);
    setStatus('Checking...');
    try {
      if (!state.emailCodeVerified) {
        const verifyPayload = await postJson('/api/v1/auth/recovery/verify-email', {
          txnId: state.txnId,
          txnSecret: state.txnSecret,
          code: codeInput ? codeInput.value.trim() : '',
        });
        state.emailCodeVerified = true;

        if (verifyPayload?.passkeyReady === true && verifyPayload?.recoveryKeyRequired === false) {
          state.magicLinkVerified = true;
          await prepareMagicLinkPasskeyRegistration('Email verified. Create your passkey now.');
          return;
        }

        if (verifyPayload?.recoveryUnavailable === true) {
          hideRecoveryCodeInput();
          hideRecoveryKeyInput();
          if (verifySubmitButton) {
            verifySubmitButton.disabled = true;
            verifySubmitButton.setAttribute('aria-disabled', 'true');
          }
          setStatus('This account does not have a Recovery Code yet. Sign in with your passkey, then create one from Settings → Security.', 'error');
          return;
        }

        if (!recoveryKeyInput?.value?.trim()) {
          showRecoveryKeyInput();
          setStatus('Email verified. Enter your recovery code to continue.', 'sent');
          return;
        }
      }

      state.proofPayload = await postJson('/api/v1/auth/recovery/proof-payload', {
        txnId: state.txnId,
        txnSecret: state.txnSecret,
      });

      await callWorker('unwrapWithRecoveryKey', {
        wrappedDekRecovery: state.proofPayload.wrappedDekRecovery,
        recoveryKey: recoveryKeyInput.value.trim(),
        accountRecoverySalt: state.proofPayload.accountRecoverySalt,
        dekVersion: state.proofPayload.dekVersion,
        cryptoVersion: state.proofPayload.cryptoVersion,
      });

      const proofResult = await callWorker('deriveRecoveryProof', {
        recoveryKey: recoveryKeyInput.value.trim(),
        accountRecoverySalt: state.proofPayload.accountRecoverySalt,
        proofNonce: state.proofPayload.proofNonce,
        txnId: state.txnId,
        clientFingerprintHash: state.proofPayload.clientFingerprintHash,
      });

      await postJson('/api/v1/auth/recovery/prove-key', {
        txnId: state.txnId,
        txnSecret: state.txnSecret,
        proofNonce: state.proofPayload.proofNonce,
        proof: proofResult.proof,
        recoveryCode: recoveryKeyInput.value.trim(),
      });

      state.bootstrap = await postJson('/api/v1/auth/recovery/bootstrap', {
        txnId: state.txnId,
        txnSecret: state.txnSecret,
      });
      state.bootstrapReady = true;

      setStep(2);
      setStatus('Verified. Confirm on your device to register a new passkey.');
    } finally {
      verifyInFlight = false;
      scheduleAutoSubmit();
      if (codeInput && !state.magicLinkVerified) codeInput.disabled = false;
      if (recoveryKeyInput && state.emailCodeVerified) recoveryKeyInput.disabled = false;
      updateVerifyButtonState();
    }
  }

  async function consumeMagicLinkIfPresent() {
    const currentUrl = new URL(window.location.href);
    const token = String(currentUrl.searchParams.get('ml_token') || '').trim();
    if (token === '') {
      return false;
    }

    setStatus('Verifying recovery link...');
    const payload = await postJson('/api/v1/auth/recovery/magic-link/consume', { token });
    state.txnId = String(payload?.txnId || '');
    state.txnSecret = String(payload?.txnSecret || '');
    state.magicLinkVerified = true;
    state.emailCodeVerified = true;

    // Remove the one-time token as soon as it has been exchanged so reloads do
    // not retry a spent link.
    currentUrl.searchParams.delete('ml_token');
    window.history.replaceState({}, '', currentUrl.toString());

    if (payload?.passkeyReady === true && payload?.recoveryKeyRequired === false) {
      storeRecoveryPrefill({
        txnId: state.txnId,
        txnSecret: state.txnSecret,
        source: 'magic-link',
        magicLinkVerified: true,
        passkeyReady: true,
      });
      await prepareMagicLinkPasskeyRegistration();
      return true;
    }

    if (payload?.recoveryUnavailable === true) {
      hideRecoveryCodeInput();
      hideRecoveryKeyInput();
      setStatus('This account does not have a Recovery Code yet. Sign in with your passkey, then create one from Settings → Security.', 'error');
      return true;
    }

    storeRecoveryPrefill({
      txnId: state.txnId,
      txnSecret: state.txnSecret,
      source: 'magic-link',
      magicLinkVerified: true,
      passkeyReady: false,
    });
    verifyForm?.classList.remove('is-hidden');
    hideRecoveryCodeInput();
    showRecoveryKeyInput();
    setStatus('Email verified. Enter your recovery code to continue.', 'sent');
    recoveryKeyInput?.focus();
    return true;
  }

  async function consumeSigninRecoveryPrefill() {
    let raw = '';
    try {
      raw = String(window.sessionStorage.getItem(RECOVERY_PREFILL_SESSION_KEY) || '');
    } catch (_) {
      return false;
    }

    if (raw.trim() === '') {
      return false;
    }

    let parsed = null;
    try {
      parsed = JSON.parse(raw);
    } catch (_error) {
      clearRecoveryPrefill();
      return false;
    }

    const txnId = String(parsed?.txnId || '').trim();
    const txnSecret = String(parsed?.txnSecret || '').trim();
    const email = String(parsed?.email || '').trim();
    const source = String(parsed?.source || '').trim();
    const magicLinkVerified = parsed?.magicLinkVerified === true;
    const passkeyReady = parsed?.passkeyReady === true;
    const createdAt = Number(parsed?.createdAt || 0);

    // Ignore stale handoff payloads.
    if (!txnId || !txnSecret || !createdAt || (Date.now() - createdAt) > (15 * 60 * 1000)) {
      clearRecoveryPrefill();
      return false;
    }

    state.txnId = txnId;
    state.txnSecret = txnSecret;
    if (emailInput && email !== '') {
      emailInput.value = email;
    }

    if ((source === 'magic-link' || magicLinkVerified) && passkeyReady) {
      state.magicLinkVerified = true;
      state.emailCodeVerified = true;
      setStatus('Resuming passkey setup...');
      try {
        await prepareMagicLinkPasskeyRegistration('Recovery link verified. Create your new passkey now.');
      } catch (error) {
        clearRecoveryPrefill();
        throw error;
      }
      return true;
    }

    if (source === 'magic-link' || magicLinkVerified) {
      state.magicLinkVerified = true;
      state.emailCodeVerified = true;
      verifyForm?.classList.remove('is-hidden');
      hideRecoveryCodeInput();
      showRecoveryKeyInput();
      setStatus('Email verified. Enter your recovery code to continue.', 'sent');
      updateVerifyButtonState();
      recoveryKeyInput?.focus();
      return true;
    }

    verifyForm?.classList.remove('is-hidden');
    resetRecoveryCodeInput();
    showRecoveryKeyInput();
    setStatus('Code sent. Check your email.', 'sent');
    updateVerifyButtonState();
    codeInput?.focus();

    clearRecoveryPrefill();
    return true;
  }

  async function registerReplacementPasskey() {
    if (registerInFlight) {
      return;
    }
    registerInFlight = true;
    let completed = false;
    setActionBusy(registerButton, true);
    setStatus('Working…');
    try {
      if (!state.bootstrapReady || state.txnId === '' || state.txnSecret === '' || state.bootstrap === null) {
        throw new Error('Passkey setup is still loading. Wait a moment and try again.');
      }

      if (!isWebAuthnCapableBrowser()) {
        throw new Error(WEB_AUTHN_UNSUPPORTED_MESSAGE);
      }

      const startRequestBody = {
        txnId: state.txnId,
        txnSecret: state.txnSecret,
        deviceName: deviceNameInput?.value?.trim() || 'Recovered Passkey',
      };

      let startPayload;
      try {
        startPayload = await postJson('/api/v1/auth/recovery/register-passkey/start', startRequestBody);
      } catch (startError) {
        const message = String(startError?.message || '');
        if (!/Recovery bootstrap unavailable/i.test(message)) {
          throw startError;
        }

        // Refresh bootstrap once, then retry passkey registration start.
        state.bootstrap = await postJson('/api/v1/auth/recovery/bootstrap', {
          txnId: state.txnId,
          txnSecret: state.txnSecret,
        });
        state.bootstrapReady = true;
        startPayload = await postJson('/api/v1/auth/recovery/register-passkey/start', startRequestBody);
      }

      const options = startPayload.publicKey || {};
      options.challenge = b64urlToBuffer(options.challenge || '');
      options.user = options.user || {};
      options.user.id = b64urlToBuffer(options.user.id || '');
      options.excludeCredentials = Array.isArray(options.excludeCredentials)
        ? options.excludeCredentials.map((item) => ({ ...item, id: b64urlToBuffer(item.id) }))
        : [];

      setStatus(RECOVERY_T.AUTH_JS_CONFIRM_DEVICE);
      const credential = await navigator.credentials.create({ publicKey: options });
      if (!credential) {
        throw new Error('Registration cancelled. Try again.');
      }

      const credentialPayload = {
        id: credential.id,
        type: credential.type,
        rawId: bufferToB64url(credential.rawId),
        response: {
          clientDataJSON: bufferToB64url(credential.response.clientDataJSON),
          attestationObject: bufferToB64url(credential.response.attestationObject),
          transports: typeof credential.response.getTransports === 'function' ? credential.response.getTransports() : [],
        },
      };

      setStatus('Almost done…');
      const finishPayload = await postJson('/api/v1/auth/recovery/register-passkey/finish', {
        txnId: state.txnId,
        txnSecret: state.txnSecret,
        challengeId: startPayload.challengeId,
        credential: credentialPayload,
      });
      state.credentialId = finishPayload.credentialId;

      setStatus('Almost done…');
      const canGenerateFirstDek = state.bootstrap?.allowDekGeneration === true;
      const wrapAction = canGenerateFirstDek
        ? 'generateAndWrapWithPasskeyCredential'
        : 'wrapCurrentDekWithPasskeyCredential';
      const wrapped = await callWorker(wrapAction, {
        credentialId: state.credentialId,
        userId: state.bootstrap.userId,
        saltBase64: state.bootstrap.encryptionSalt,
        dekVersion: state.bootstrap.dekVersion,
        cryptoVersion: state.bootstrap.cryptoVersion,
      });

      setStatus('Almost done…');
      await postJson('/api/v1/auth/recovery/complete', {
        txnId: state.txnId,
        txnSecret: state.txnSecret,
        credentialId: state.credentialId,
        wrappedDekPasskey: wrapped.wrappedDekPasskey,
        dekVersion: wrapped.dekVersion,
        cryptoVersion: wrapped.cryptoVersion,
      });

      completed = true;
      clearRecoveryPrefill();
      setStep(3);
      setStatus('Recovery complete. Redirecting…');
      window.setTimeout(() => {
        window.location.href = '/';
      }, 900);
    } finally {
      registerInFlight = false;
      if (registerButton) {
        setActionBusy(registerButton, false, { ariaBusyWhenIdle: false, disable: false });
        if (!completed) {
          setStep(state.bootstrapReady ? 2 : 1);
        }
      }
    }
  }

  async function cancelRecovery() {
    if (cancelInFlight) {
      return;
    }
    cancelInFlight = true;
    setActionBusy(cancelButton, true);
    try {
      if (state.txnId && state.txnSecret) {
        await postJson('/api/v1/auth/recovery/cancel', {
          txnId: state.txnId,
          txnSecret: state.txnSecret,
        });
      }

      clearRecoveryPrefill();
      window.location.href = authUrlWithLanguage({ signin_message: 'Recovery cancelled.' });
    } finally {
      cancelInFlight = false;
      setActionBusy(cancelButton, false, { ariaBusyWhenIdle: false });
    }
  }

  function goToSignin() {
    window.location.href = authUrlWithLanguage();
  }

  startForm?.addEventListener('submit', (event) => {
    startRecovery(event).catch((error) => setStatus(error.message || 'Recovery failed. Try again.', 'error'));
  });
  verifyForm?.addEventListener('submit', (event) => {
    verifyRecovery(event).catch((error) => setRecoveryErrorStatus(error));
  });
  backToSigninButton?.addEventListener('click', goToSignin);
  registerButton?.addEventListener('click', () => {
    registerReplacementPasskey().catch((error) => {
      if (error && (error.name === 'InvalidStateError' || /already registered with the relying party/i.test(String(error.message || '')))) {
        setStatus('This device already has a passkey for this account. Sign in instead.');
        const hint = document.getElementById('recovery-existing-passkey-hint');
        if (hint) {
          hint.classList.add('is-prominent');
        }
        return;
      }
      setRecoveryErrorStatus(error);
    });
  });
  cancelButton?.addEventListener('click', () => {
    cancelRecovery().catch((error) => setStatus(error.message || RECOVERY_T.AUTH_JS_RECOVER_CANCEL_FAILED));
  });
  emailInput?.addEventListener('input', () => {
    updateVerifyButtonState();
    scheduleAutoSubmit();
  });
  codeInput?.addEventListener('input', () => {
    codeInput.value = formatVerificationCode(codeInput.value);
    updateVerifyButtonState();
    scheduleAutoSubmit();
  });
  recoveryKeyInput?.addEventListener('input', () => {
    recoveryKeyInput.value = formatRecoveryCode(recoveryKeyInput.value);
    updateVerifyButtonState();
    scheduleAutoSubmit();
  });
  codeInput?.addEventListener('paste', () => {
    window.setTimeout(() => {
      codeInput.value = formatVerificationCode(codeInput.value);
      validateVisibleRecoveryFields(true);
      updateVerifyButtonState();
      scheduleAutoSubmit();
    }, 0);
  });
  recoveryKeyInput?.addEventListener('paste', () => {
    window.setTimeout(() => {
      recoveryKeyInput.value = formatRecoveryCode(recoveryKeyInput.value);
      validateVisibleRecoveryFields(true);
      updateVerifyButtonState();
      scheduleAutoSubmit();
    }, 0);
  });

  updateVerifyButtonState();

  consumeMagicLinkIfPresent()
    .then((consumed) => {
      if (!consumed) {
        return consumeSigninRecoveryPrefill();
      }
      return true;
    })
    .catch((error) => {
      setRecoveryErrorStatus(error, 'Recovery link is invalid or expired. Request a new passkey setup link and try again.');
    });
})();
