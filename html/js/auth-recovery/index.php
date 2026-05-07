<?php declare(strict_types=1);

header('Content-Type: application/javascript; charset=utf-8');
?>
(function () {
  const RECOVERY_PREFILL_SESSION_KEY = 'paycal.recovery.prefill';
  const state = {
    txnId: '',
    txnSecret: '',
    proofPayload: null,
    bootstrap: null,
    credentialId: '',
    magicLinkVerified: false,
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
  const recoveryKeyInput = document.getElementById('recovery-key');
  const deviceNameInput = document.getElementById('recovery-device-name');
  const workerVersion = document.body?.dataset?.workerVersion || String(Date.now());
  const WEB_AUTHN_UNSUPPORTED_MESSAGE = 'This browser cannot register passkeys. Use a WebAuthn-capable browser on a secure connection (HTTPS).';

  let worker = null;
  let workerRequestId = 0;
  const workerPending = new Map();
  let sendCooldownTimer = null;
  let startInFlight = false;
  let verifyInFlight = false;
  let registerInFlight = false;
  let cancelInFlight = false;

  function isWebAuthnCapableBrowser() {
    const hasPublicKeyCredential = typeof window.PublicKeyCredential !== 'undefined';
    const hasCredentialsApi = typeof navigator.credentials !== 'undefined' && navigator.credentials !== null;
    const hasGet = hasCredentialsApi && typeof navigator.credentials.get === 'function';
    const hasCreate = hasCredentialsApi && typeof navigator.credentials.create === 'function';
    return window.isSecureContext && hasPublicKeyCredential && hasCredentialsApi && hasGet && hasCreate;
  }

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
    sendCodeButton.textContent = `Send code (${formatCooldown(remaining)})`;

    sendCooldownTimer = window.setInterval(() => {
      remaining -= 1;
      if (remaining <= 0) {
        window.clearInterval(sendCooldownTimer);
        sendCooldownTimer = null;
        sendCodeButton.disabled = false;
        sendCodeButton.textContent = 'Send code';
        return;
      }
      sendCodeButton.textContent = `Send code (${formatCooldown(remaining)})`;
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

    if (step === 2 && !isWebAuthnCapableBrowser()) {
      registerButton.disabled = true;
      registerButton.setAttribute('aria-disabled', 'true');
      setStatus(WEB_AUTHN_UNSUPPORTED_MESSAGE, 'error');
      return;
    }

    registerButton.disabled = false;
    registerButton.removeAttribute('aria-disabled');
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

  function ensureWorker() {
    if (worker) {
      return worker;
    }

    worker = new Worker(`/js/calendar/crypto-worker.js?v=${encodeURIComponent(workerVersion)}`);
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

  function b64urlToBuffer(b64url) {
    const padding = '='.repeat((4 - (b64url.length % 4)) % 4);
    const base64 = (b64url + padding).replace(/-/g, '+').replace(/_/g, '/');
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i += 1) {
      bytes[i] = binary.charCodeAt(i);
    }
    return bytes.buffer;
  }

  function bufferToB64url(input) {
    const bytes = input instanceof ArrayBuffer ? new Uint8Array(input) : new Uint8Array(input.buffer);
    let binary = '';
    bytes.forEach((b) => { binary += String.fromCharCode(b); });
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
  }

  async function startRecovery(event) {
    event.preventDefault();
    if (startInFlight) {
      return;
    }
    startInFlight = true;
    setStatus('Working…');
    if (sendCodeButton) {
      sendCodeButton.disabled = true;
      sendCodeButton.setAttribute('aria-busy', 'true');
    }
    try {
      const payload = await postJson('/api/v1/auth/recovery/start', { email: emailInput.value.trim() });
      state.txnId = payload?.txnId || '';
      state.txnSecret = payload?.txnSecret || '';
      verifyForm?.classList.remove('is-hidden');
      setStatus('Code sent if the account exists.', 'sent');
      startSendCooldown(60);
    } catch (error) {
      if (sendCodeButton) {
        sendCodeButton.disabled = false;
      }
      throw error;
    } finally {
      startInFlight = false;
      if (sendCodeButton) {
        sendCodeButton.removeAttribute('aria-busy');
      }
    }
  }

  async function verifyRecovery(event) {
    event.preventDefault();
    if (verifyInFlight) {
      return;
    }
    verifyInFlight = true;
    if (codeInput && !state.magicLinkVerified) codeInput.disabled = true;
    if (recoveryKeyInput) recoveryKeyInput.disabled = true;
    setStatus('Working…');
    try {
      if (!state.magicLinkVerified) {
        await postJson('/api/v1/auth/recovery/verify-email', {
          txnId: state.txnId,
          txnSecret: state.txnSecret,
          code: codeInput ? codeInput.value.trim() : '',
        });
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
      });

      state.bootstrap = await postJson('/api/v1/auth/recovery/bootstrap', {
        txnId: state.txnId,
        txnSecret: state.txnSecret,
      });

      setStep(2);
      setStatus('Verified. Confirm on your device to register a new passkey.');
    } finally {
      verifyInFlight = false;
      if (codeInput && !state.magicLinkVerified) codeInput.disabled = false;
      if (recoveryKeyInput) recoveryKeyInput.disabled = false;
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

    // Magic-link flow skips manual verify inputs and opens passkey registration directly.
    state.bootstrap = await postJson('/api/v1/auth/recovery/bootstrap', {
      txnId: state.txnId,
      txnSecret: state.txnSecret,
    });

    startForm?.classList.add('is-hidden');
    verifyForm?.classList.add('is-hidden');
    hideRecoveryCodeInput();
    setStep(2);
    setStatus('Recovery link verified. Create your new passkey now.', 'sent');

    // Remove one-time token from the URL after successful consumption.
    currentUrl.searchParams.delete('ml_token');
    window.history.replaceState({}, '', currentUrl.toString());
    return true;
  }

  function consumeSigninRecoveryPrefill() {
    let raw = '';
    try {
      raw = String(window.sessionStorage.getItem(RECOVERY_PREFILL_SESSION_KEY) || '');
    } catch (_) {
      return false;
    }

    if (raw.trim() === '') {
      return false;
    }

    try {
      const parsed = JSON.parse(raw);
      const txnId = String(parsed?.txnId || '').trim();
      const txnSecret = String(parsed?.txnSecret || '').trim();
      const email = String(parsed?.email || '').trim();
      const createdAt = Number(parsed?.createdAt || 0);

      // Ignore stale handoff payloads.
      if (!txnId || !txnSecret || !createdAt || (Date.now() - createdAt) > (15 * 60 * 1000)) {
        window.sessionStorage.removeItem(RECOVERY_PREFILL_SESSION_KEY);
        return false;
      }

      state.txnId = txnId;
      state.txnSecret = txnSecret;
      if (emailInput && email !== '') {
        emailInput.value = email;
      }

      startForm?.classList.add('is-hidden');
      verifyForm?.classList.remove('is-hidden');
      setStatus('Recovery code sent. Enter the code from your email and your Recovery Key.', 'sent');
      codeInput?.focus();

      window.sessionStorage.removeItem(RECOVERY_PREFILL_SESSION_KEY);
      return true;
    } catch (_) {
      try {
        window.sessionStorage.removeItem(RECOVERY_PREFILL_SESSION_KEY);
      } catch (__){
        // Ignore storage cleanup errors.
      }
      return false;
    }
  }

  async function registerReplacementPasskey() {
    if (registerInFlight) {
      return;
    }
    registerInFlight = true;
    if (registerButton) {
      registerButton.disabled = true;
      registerButton.setAttribute('aria-busy', 'true');
    }
    setStatus('Working…');
    try {
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
        startPayload = await postJson('/api/v1/auth/recovery/register-passkey/start', startRequestBody);
      }

      const options = startPayload.publicKey || {};
      options.challenge = b64urlToBuffer(options.challenge || '');
      options.user = options.user || {};
      options.user.id = b64urlToBuffer(options.user.id || '');
      options.excludeCredentials = Array.isArray(options.excludeCredentials)
        ? options.excludeCredentials.map((item) => ({ ...item, id: b64urlToBuffer(item.id) }))
        : [];

      setStatus('Confirm on your device…');
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
      const wrapped = await callWorker('wrapCurrentDekWithPasskeyCredential', {
        credentialId: state.credentialId,
        userId: state.bootstrap.userId,
        saltBase64: state.bootstrap.encryptionSalt,
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

      setStep(3);
      setStatus('Recovery complete. Redirecting…');
      window.setTimeout(() => {
        window.location.href = '/';
      }, 900);
    } finally {
      registerInFlight = false;
      if (registerButton) {
        registerButton.disabled = false;
        registerButton.removeAttribute('aria-busy');
      }
    }
  }

  async function cancelRecovery() {
    if (cancelInFlight) {
      return;
    }
    cancelInFlight = true;
    if (cancelButton) {
      cancelButton.disabled = true;
      cancelButton.setAttribute('aria-busy', 'true');
    }
    try {
      if (state.txnId && state.txnSecret) {
        await postJson('/api/v1/auth/recovery/cancel', {
          txnId: state.txnId,
          txnSecret: state.txnSecret,
        });
      }

      window.location.href = authUrlWithLanguage({ signin_message: 'Recovery cancelled.' });
    } finally {
      cancelInFlight = false;
      if (cancelButton) {
        cancelButton.disabled = false;
        cancelButton.removeAttribute('aria-busy');
      }
    }
  }

  function goToSignin() {
    window.location.href = authUrlWithLanguage();
  }

  startForm?.addEventListener('submit', (event) => {
    startRecovery(event).catch((error) => setStatus(error.message || 'Recovery failed. Try again.'));
  });
  verifyForm?.addEventListener('submit', (event) => {
    verifyRecovery(event).catch((error) => setStatus(error.message || 'Recovery failed. Try again.'));
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
      setStatus(error.message || 'Recovery failed. Try again.');
    });
  });
  cancelButton?.addEventListener('click', () => {
    cancelRecovery().catch((error) => setStatus(error.message || 'Unable to cancel recovery.'));
  });

  consumeMagicLinkIfPresent()
    .then((consumed) => {
      if (!consumed) {
        consumeSigninRecoveryPrefill();
      }
    })
    .catch((error) => {
      setStatus(error?.message || 'Recovery link is invalid or expired. Request a new link.');
    });
})();
