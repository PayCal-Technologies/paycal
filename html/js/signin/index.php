<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__ . '/../../config.php';

CORS::handleORIGIN();
CORS::renderContentType('application/javascript');

$authI18nKeys = [
  'AUTH_SIGNIN_PASSKEY_STATUS',
  'AUTH_REGISTER_PASSKEY_STATUS',
  'AUTH_JS_WEBAUTHN_UNSUPPORTED',
  'AUTH_JS_REGISTER_UNSUPPORTED',
  'AUTH_JS_REGISTER_UNSUPPORTED_HELP',
  'AUTH_JS_SIGNIN_RECOVERY_CODE',
  'AUTH_JS_CONFIRM_DEVICE',
  'AUTH_JS_SUCCESS_REDIRECTING',
  'AUTH_JS_RECOVERY_START_FAILED',
  'AUTH_JS_RECOVERY_SEND_FAILED',
  'AUTH_JS_REGISTER_CHECK_EMAIL',
  'AUTH_JS_REGISTER_FAILED',
  'AUTH_JS_EMAIL_ALREADY_REGISTERED',
  'AUTH_JS_INVALID_INVITE_CODE',
  'AUTH_JS_ENTER_FULL_NAME',
  'AUTH_JS_ENTER_VALID_EMAIL',
  'AUTH_JS_EMAIL_REQUIRED',
  'AUTH_JS_NO_ACCOUNT',
  'AUTH_JS_SIGNIN_FAILED',
  'AUTH_JS_REQUEST_TIMEOUT',
  'AUTH_JS_NETWORK_ERROR',
];
$authI18n = [];
foreach ($authI18nKeys as $authI18nKey) {
  $authI18n[$authI18nKey] = Strings::i18n($authI18nKey);
}

?>
import { fromBase64Url as b64urlToBuffer, toBase64Url as bufferToB64url } from '/js/core/binary-codec.js';
import { isWebAuthnCapableBrowser } from '/js/core/capabilities.js';
import { setActionBusy as setButtonBusy } from '/js/core/actions.js';

// Passkey-only auth helpers for /auth

const AUTH_T = <?php echo json_encode($authI18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

const WEB_AUTHN_UNSUPPORTED_MESSAGE = AUTH_T.AUTH_JS_WEBAUTHN_UNSUPPORTED;
const WEB_AUTHN_HELP_URL = '/help/webauthn-security.php';

const passkeyStatusEl = document.getElementById('signin-passkey-status');
const DEFAULT_SIGNIN_STATUS = AUTH_T.AUTH_SIGNIN_PASSKEY_STATUS;
const animateStatusEl = (el) => {
  if (!el) return;
  el.classList.remove('status-drop-in');
  // Force reflow so repeated messages replay the animation.
  void el.offsetWidth;
  el.classList.add('status-drop-in');
};

const setPasskeyStatus = (msg) => {
  if (passkeyStatusEl) {
    passkeyStatusEl.textContent = msg;
    animateStatusEl(passkeyStatusEl);
  }
};

const registerStatusEl = document.getElementById('register-passkey-status');
const DEFAULT_REGISTER_STATUS = AUTH_T.AUTH_REGISTER_PASSKEY_STATUS;
const setRegisterStatus = (msg) => {
  if (registerStatusEl) {
    registerStatusEl.textContent = msg;
    animateStatusEl(registerStatusEl);
  }
};

const authBannerEl = document.getElementById('auth-feedback-banner');
let authBannerTimer = null;
let recoveryStartInFlight = false;
const RECOVERY_PREFILL_SESSION_KEY = 'paycal.recovery.prefill';
const registerUnsupportedWarning = AUTH_T.AUTH_JS_REGISTER_UNSUPPORTED;
const registerUnsupportedHelpLabel = AUTH_T.AUTH_JS_REGISTER_UNSUPPORTED_HELP;

const hideAuthBanner = () => {
  if (!authBannerEl) return;
  authBannerEl.classList.remove('show', 'success');
  authBannerEl.textContent = '';

  if (authBannerTimer) {
    clearTimeout(authBannerTimer);
    authBannerTimer = null;
  }
};

const showAuthBanner = (msg, type = 'error', options = {}) => {
  if (!authBannerEl || !msg) return;
  authBannerEl.textContent = '';
  const textNode = document.createElement('span');
  textNode.textContent = msg;
  authBannerEl.appendChild(textNode);

  if (options.linkHref && options.linkLabel) {
    const spacer = document.createElement('span');
    spacer.textContent = ' ';
    authBannerEl.appendChild(spacer);

    const linkEl = document.createElement('a');
    linkEl.href = String(options.linkHref);
    linkEl.textContent = String(options.linkLabel);
    linkEl.setAttribute('rel', 'noopener noreferrer');
    authBannerEl.appendChild(linkEl);
  }

  authBannerEl.classList.remove('success');
  if (type === 'success') {
    authBannerEl.classList.add('success');
  }
  authBannerEl.classList.add('show');

  if (authBannerTimer) {
    clearTimeout(authBannerTimer);
    authBannerTimer = null;
  }

  const autoHideMs = Number(options.autoHideMs ?? 10500);
  if (Number.isFinite(autoHideMs) && autoHideMs > 0) {
    authBannerTimer = setTimeout(() => {
      authBannerEl.classList.remove('show');
    }, autoHideMs);
  }
};

const recoveryUrlWithLanguage = () => {
  const current = new URL(window.location.href);
  const language = String(current.searchParams.get('l') || '').trim();
  const target = new URL('/auth/recover/', window.location.origin);
  if (language !== '') {
    target.searchParams.set('l', language);
  }
  return target.toString();
};

const showRecoveryCodeComposer = (prefillEmail = '') => {
  if (!authBannerEl) return;

  showAuthBanner('Passkey sign-in failed. Enter your account email to send a recovery code.', 'error', {
    autoHideMs: 0,
  });

  const actions = document.createElement('div');
  actions.className = 'auth-feedback-banner-actions';

  const input = document.createElement('input');
  input.type = 'email';
  input.className = 'auth-feedback-banner-input';
  input.placeholder = 'you@example.com';
  input.value = String(prefillEmail || '').trim();
  input.autocomplete = 'email';
  input.setAttribute('aria-label', 'Email for recovery code');

  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'auth-feedback-banner-btn';
  button.textContent = AUTH_T.AUTH_JS_SIGNIN_RECOVERY_CODE;

  button.addEventListener('click', async () => {
    const email = String(input.value || '').trim();
    if (!email || !email.includes('@')) {
      showAuthBanner('Enter a valid email address to send a recovery code.', 'error', { autoHideMs: 0 });
      showRecoveryCodeComposer(email);
      return;
    }
    await requestRecoveryCodeAndRedirect(email, { source: 'manual' });
  });

  actions.appendChild(input);
  actions.appendChild(button);
  authBannerEl.appendChild(actions);

  input.focus();
};

const showAuthError = (msg, context = 'signin') => {
  showAuthBanner(msg, 'error');

  if (context === 'register') {
    setRegisterStatus(DEFAULT_REGISTER_STATUS);
    return;
  }

  setPasskeyStatus(DEFAULT_SIGNIN_STATUS);
};

const applyRegisterWebAuthnWarningState = (showBanner = false) => {
  if (isWebAuthnCapableBrowser()) {
    if (registerButton) {
      registerButton.disabled = false;
      registerButton.removeAttribute('aria-disabled');
    }
    return;
  }

  if (registerButton) {
    registerButton.disabled = true;
    registerButton.setAttribute('aria-disabled', 'true');
  }

  setRegisterStatus(registerUnsupportedWarning);

  if (showBanner) {
    showAuthBanner(registerUnsupportedWarning, 'error', {
      linkHref: WEB_AUTHN_HELP_URL,
      linkLabel: registerUnsupportedHelpLabel,
    });
  }
};

const signupFriendlyMessage = (apiMessage) => {
  const message = String(apiMessage || '').trim();
  if (message === '') return AUTH_T.AUTH_JS_REGISTER_FAILED;
  if (/email is already registered/i.test(message)) {
    return AUTH_T.AUTH_JS_EMAIL_ALREADY_REGISTERED;
  }
  if (/invalid invite code/i.test(message)) {
    return AUTH_T.AUTH_JS_INVALID_INVITE_CODE;
  }
  if (/full name is required/i.test(message)) {
    return AUTH_T.AUTH_JS_ENTER_FULL_NAME;
  }
  if (/valid email is required/i.test(message)) {
    return AUTH_T.AUTH_JS_ENTER_VALID_EMAIL;
  }
  return message;
};

const currentDateStamp = () => {
  const now = new Date();
  const y = String(now.getFullYear());
  const m = String(now.getMonth() + 1).padStart(2, '0');
  const d = String(now.getDate()).padStart(2, '0');
  return `${y}${m}${d}`;
};

const suggestedDeviceNameFromEmail = (emailRaw) => {
  const email = String(emailRaw || '').trim();
  const localPart = email.includes('@') ? email.split('@')[0] : email;
  const slug = localPart
    .toLowerCase()
    .replace(/[^a-z0-9._-]+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');

  const base = slug || 'passkey';
  return `${base}-${currentDateStamp()}`;
};

const signinFriendlyMessage = (apiMessage, statusCode) => {
  const message = String(apiMessage || '').trim();
  if (/email is required/i.test(message)) {
    return AUTH_T.AUTH_JS_EMAIL_REQUIRED;
  }
  if (statusCode === 401 || /authentication failed/i.test(message)) {
    return AUTH_T.AUTH_JS_NO_ACCOUNT;
  }
  if (message !== '') {
    return message;
  }
  return AUTH_T.AUTH_JS_SIGNIN_FAILED;
};

const parseJsonOrNull = async (response) => {
  try {
    return await response.json();
  } catch (_error) {
    return null;
  }
};

const fetchJsonWithTimeout = async (url, options, timeoutMs = 15000) => {
  const controller = new AbortController();
  const timerId = window.setTimeout(() => controller.abort(), timeoutMs);

  try {
    const response = await fetch(url, {
      ...options,
      signal: controller.signal,
    });
    const payload = await parseJsonOrNull(response);
    return { response, payload };
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') {
      throw new Error(AUTH_T.AUTH_JS_REQUEST_TIMEOUT);
    }
    throw new Error(AUTH_T.AUTH_JS_NETWORK_ERROR);
  } finally {
    window.clearTimeout(timerId);
  }
};

let signinInFlight = false;
let registerInFlight = false;

const federatedSigninEl = document.getElementById('federated-signin');
const federatedProvidersEl = document.getElementById('federated-signin-providers');
const providerIconText = (providerId) => {
  switch (providerId) {
    case 'google':
      return 'G';
    case 'apple':
      return 'A';
    case 'microsoft':
      return 'M';
    default:
      return '?';
  }
};

const renderFederatedProviders = (providers) => {
  if (!federatedSigninEl || !federatedProvidersEl) return;
  federatedProvidersEl.replaceChildren();

  const visibleProviders = Array.isArray(providers) ? providers : [];
  if (visibleProviders.length === 0) {
    federatedSigninEl.hidden = true;
    return;
  }

  visibleProviders.forEach((provider) => {
    const providerId = String(provider?.id || '').trim();
    const buttonLabel = String(provider?.button_label || provider?.label || '').trim();
    if (providerId === '' || buttonLabel === '') {
      return;
    }

    const button = document.createElement('button');
    button.type = 'button';
    button.className = `federated-signin-button federated-signin-button_${providerId}`;
    button.dataset.provider = providerId;
    button.setAttribute('aria-label', buttonLabel);

    const icon = document.createElement('span');
    icon.className = 'federated-signin-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = providerIconText(providerId);

    const label = document.createElement('span');
    label.className = 'federated-signin-text';
    label.textContent = buttonLabel;

    button.appendChild(icon);
    button.appendChild(label);
    button.addEventListener('click', () => {
      window.location.href = `/api/v1/auth/federated/start/${encodeURIComponent(providerId)}?mode=signin`;
    });

    federatedProvidersEl.appendChild(button);
  });

  federatedSigninEl.hidden = federatedProvidersEl.children.length === 0;
};

const loadFederatedProviders = async () => {
  if (!federatedSigninEl || !federatedProvidersEl) return;

  try {
    const { response, payload } = await fetchJsonWithTimeout('/api/v1/auth/providers', {
      method: 'GET',
      credentials: 'include',
      headers: { 'Accept': 'application/json' },
    }, 8000);

    const providerPayload = payload && typeof payload === 'object' ? payload : {};
    if (!response.ok || providerPayload.status !== 'success') {
      renderFederatedProviders([]);
      return;
    }

    renderFederatedProviders(providerPayload.providers);
  } catch (_error) {
    renderFederatedProviders([]);
  }
};

const runPasskeySignin = async (preferPhoneFlow = false) => {
  if (signinInFlight) {
    return;
  }

  signinInFlight = true;
  setButtonBusy(passkeyButton, true);
  setButtonBusy(passkeyPhoneButton, true);

  try {
    hideAuthBanner();

    if (!isWebAuthnCapableBrowser()) {
      const msg = WEB_AUTHN_UNSUPPORTED_MESSAGE;
      showAuthError(msg, 'signin');
      return;
    }

    const emailInput = document.getElementById('email');
    const email = emailInput?.value?.trim() || '';

    if (!preferPhoneFlow && !email) {
      const msg = 'Email is required.';
      showAuthError(msg, 'signin');
      return;
    }

    setPasskeyStatus('Working…');
    const { response: startResponse, payload: startPayloadRaw } = await fetchJsonWithTimeout('/api/v1/auth/passkey/login/start', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(
        preferPhoneFlow
          ? { discoverable: true }
          : { email }
      ),
    });
    const startPayload = startPayloadRaw && typeof startPayloadRaw === 'object' ? startPayloadRaw : {};
    if (!startResponse.ok || startPayload.status !== 'success') {
      throw new Error(signinFriendlyMessage(startPayload.message, startResponse.status));
    }

    const challengeId = startPayload.challengeId;
    const options = startPayload.publicKey || {};
    options.challenge = b64urlToBuffer(options.challenge || '');
    options.allowCredentials = Array.isArray(options.allowCredentials)
      ? options.allowCredentials.map((c) => ({
        ...c,
        id: b64urlToBuffer(c.id),
      }))
      : [];

    // Keep login discoverable for cross-device passkey (browser-provided QR flow).
    if (!Array.isArray(options.hints)) {
      options.hints = ['client-device', 'hybrid', 'security-key'];
    }
    if (options.authenticatorSelection && options.authenticatorSelection.authenticatorAttachment === 'platform') {
      delete options.authenticatorSelection.authenticatorAttachment;
    }

    setPasskeyStatus(
      preferPhoneFlow
        ? AUTH_T.AUTH_JS_CONFIRM_DEVICE
        : AUTH_T.AUTH_JS_CONFIRM_DEVICE
    );
    const assertion = await navigator.credentials.get({ publicKey: options, mediation: 'optional' });
    if (!assertion) {
      throw new Error('Sign-in cancelled. Try again.');
    }

    const credentialPayload = {
      id: assertion.id,
      type: assertion.type,
      rawId: bufferToB64url(assertion.rawId),
      response: {
        clientDataJSON: bufferToB64url(assertion.response.clientDataJSON),
        authenticatorData: bufferToB64url(assertion.response.authenticatorData),
        signature: bufferToB64url(assertion.response.signature),
        userHandle: assertion.response.userHandle ? bufferToB64url(assertion.response.userHandle) : null,
      },
    };

    const { response: finishResponse, payload: finishPayloadRaw } = await fetchJsonWithTimeout('/api/v1/auth/passkey/login/finish', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ challengeId, assertion: credentialPayload }),
    });

    const finishPayload = finishPayloadRaw && typeof finishPayloadRaw === 'object' ? finishPayloadRaw : {};
    if (!finishResponse.ok || finishPayload.status !== 'success') {
      const finishErrorCode = String(finishPayload.error || '').trim();
      // 403 means credential mismatch (passkey not registered for this email)
      if (finishResponse.status === 403 && email) {
        const error = new Error('This passkey isn\'t registered for this account.');
        error.isPasskeyMismatch = true;
        error.isPasskeyRecoverable = true;
        error.email = email;
        throw error;
      }
      if (finishErrorCode === 'passkey_compromised' && email) {
        const error = new Error('This passkey can no longer be used. Use a recovery link to continue.');
        error.isPasskeyRecoverable = true;
        error.email = email;
        throw error;
      }
      if (finishErrorCode === 'passkey_invalid' && email) {
        const error = new Error('This passkey is no longer valid for this account.');
        error.isPasskeyRecoverable = true;
        error.email = email;
        throw error;
      }
      // 401 means the passkey credential was not found or authentication failed
      if (finishResponse.status === 401) {
        const error = new Error('Passkey not recognized.');
        error.isPasskeyRecoverable = true;
        error.email = email;
        error.requiresEmailForRecovery = !email;
        throw error;
      }
      throw new Error(finishPayload.message || 'Passkey login failed.');
    }

    setPasskeyStatus(AUTH_T.AUTH_JS_SUCCESS_REDIRECTING);
    hideAuthBanner();
    window.location.href = preferPhoneFlow
      ? '/settings/security/?passkey_onboarding=1'
      : '/';
  } catch (error) {
    const msg = error?.message || AUTH_T.AUTH_JS_SIGNIN_FAILED;
    
    // Send recovery code and redirect to recovery page when passkey cannot be used.
    if (error?.isPasskeyRecoverable) {
      const recoveryEmail = String(error?.email || '').trim();
      if (recoveryEmail !== '') {
        await requestRecoveryCodeAndRedirect(recoveryEmail, { source: 'auto' });
      } else {
        showRecoveryCodeComposer('');
        setPasskeyStatus('Enter your email to send a recovery code.');
      }
    } else {
      showAuthError(msg, 'signin');
    }
  } finally {
    signinInFlight = false;
    setButtonBusy(passkeyButton, false);
    setButtonBusy(passkeyPhoneButton, false);
  }
};

const requestRecoveryCodeAndRedirect = async (email, options = {}) => {
  if (recoveryStartInFlight) {
    return;
  }

  recoveryStartInFlight = true;
  const source = String(options.source || 'manual');

  try {
    setPasskeyStatus('Sending recovery code...');
    const { response, payload } = await fetchJsonWithTimeout('/api/v1/auth/recovery/start', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ email }),
    });

    const startPayload = payload && typeof payload === 'object' ? payload : {};
    const txnId = String(startPayload.txnId || startPayload?.data?.txnId || '').trim();
    const txnSecret = String(startPayload.txnSecret || startPayload?.data?.txnSecret || '').trim();

    if (response.ok && startPayload.status === 'success' && txnId !== '' && txnSecret !== '') {
      const normalizedEmail = String(email || '').trim();
      try {
        window.sessionStorage.setItem(RECOVERY_PREFILL_SESSION_KEY, JSON.stringify({
          txnId,
          txnSecret,
          email: normalizedEmail,
          createdAt: Date.now(),
        }));
      } catch (_) {
        // If sessionStorage is blocked, continue with plain redirect.
      }

      showAuthBanner(
        'Recovery code sent to ' + normalizedEmail + '. Redirecting to recovery...',
        'success',
        {
          linkHref: recoveryUrlWithLanguage(),
          linkLabel: 'Go to recovery page',
          autoHideMs: source === 'auto' ? 2200 : 1800,
        }
      );

      setPasskeyStatus('Recovery code sent. Redirecting...');
      window.setTimeout(() => {
        window.location.href = recoveryUrlWithLanguage();
      }, source === 'auto' ? 300 : 500);
    } else {
      throw new Error(AUTH_T.AUTH_JS_RECOVERY_START_FAILED);
    }
  } catch (error) {
    const msg = error?.message || AUTH_T.AUTH_JS_RECOVERY_SEND_FAILED;
    showAuthBanner(msg, 'error', { autoHideMs: 0 });
    showRecoveryCodeComposer(String(email || '').trim());
    setPasskeyStatus('Recovery code could not be sent. Try again.');
  } finally {
    recoveryStartInFlight = false;
  }
};

const isSigninPanelActive = () => {
  const shell = document.getElementById('auth-shell');
  if (shell && shell.classList.contains('is-register')) {
    return false;
  }

  const signinPanel = document.getElementById('panel-signin');
  if (signinPanel && signinPanel.getAttribute('aria-hidden') === 'true') {
    return false;
  }

  return true;
};

// Keep passkey sign-in user-initiated to avoid background 401s from silent
// conditional mediation probes that create confusing console noise.
loadFederatedProviders();

const passkeyButton = document.getElementById('signin-passkey');
if (passkeyButton) {
  passkeyButton.addEventListener('click', async () => {
    await runPasskeySignin(false);
  });
}

const passkeyPhoneButton = document.getElementById('signin-passkey-phone');
if (passkeyPhoneButton) {
  passkeyPhoneButton.addEventListener('click', async () => {
    await runPasskeySignin(true);
  });
}

const registerButton = document.getElementById('register-passkey');
const registerEmailInput = document.getElementById('register-email');
const registerDeviceInput = document.getElementById('register-device-name');

let lastSuggestedRegisterDeviceName = '';
const syncSuggestedRegisterDeviceName = () => {
  if (!registerEmailInput || !registerDeviceInput) return;

  const nextSuggestion = suggestedDeviceNameFromEmail(registerEmailInput.value);
  const currentValue = String(registerDeviceInput.value || '').trim();

  // Only autofill when empty or still using a previous auto-generated suggestion.
  if (currentValue === '' || currentValue === lastSuggestedRegisterDeviceName || currentValue === 'My Passkey') {
    registerDeviceInput.value = nextSuggestion;
  }

  lastSuggestedRegisterDeviceName = nextSuggestion;
};

if (registerEmailInput && registerDeviceInput) {
  syncSuggestedRegisterDeviceName();
  registerEmailInput.addEventListener('input', syncSuggestedRegisterDeviceName);
  registerEmailInput.addEventListener('blur', syncSuggestedRegisterDeviceName);
}

if (registerButton) {
  registerButton.addEventListener('click', async () => {
    if (registerInFlight) {
      return;
    }

    registerInFlight = true;
    setButtonBusy(registerButton, true);

    try {
      hideAuthBanner();

      if (!isWebAuthnCapableBrowser()) {
        const msg = WEB_AUTHN_UNSUPPORTED_MESSAGE;
        showAuthError(msg, 'register');
        return;
      }

      const fullNameInput = document.getElementById('register-full-name');
      const emailInput = document.getElementById('register-email');
      const inviteInput = document.getElementById('invite_code');
      const deviceInput = document.getElementById('register-device-name');

      const fullName = fullNameInput?.value?.trim() || '';
      const email = emailInput?.value?.trim() || '';
      const inviteCode = inviteInput?.value?.trim() || '';
      const deviceName = deviceInput?.value?.trim() || suggestedDeviceNameFromEmail(email);

      if (!fullName || !email) {
        const msg = 'Full name and email are required.';
        showAuthError(msg, 'register');
        return;
      }

      setRegisterStatus('Working…');
      const { response: startResponse, payload: startPayloadRaw } = await fetchJsonWithTimeout('/api/v1/auth/passkey/signup/start', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ fullName, email, inviteCode, deviceName }),
      });

      const startPayload = startPayloadRaw && typeof startPayloadRaw === 'object' ? startPayloadRaw : {};
      if (!startResponse.ok || startPayload.status !== 'success') {
        const friendly = signupFriendlyMessage(startPayload.message);
        if (/already registered/i.test(String(startPayload.message || ''))) {
          showAuthError(friendly, 'register');
          return;
        }
        throw new Error(friendly);
      }

      const challengeId = startPayload.challengeId;
      const options = startPayload.publicKey || {};
      options.challenge = b64urlToBuffer(options.challenge || '');
      options.user = options.user || {};
      options.user.id = b64urlToBuffer(options.user.id || '');
      options.excludeCredentials = Array.isArray(options.excludeCredentials)
        ? options.excludeCredentials.map((c) => ({ ...c, id: b64urlToBuffer(c.id) }))
        : [];

      // Prefer hybrid discoverability so browsers can offer "use another device" (QR/Bluetooth).
      if (!Array.isArray(options.hints)) {
        options.hints = ['client-device', 'hybrid', 'security-key'];
      }
      if (options.authenticatorSelection && options.authenticatorSelection.authenticatorAttachment === 'platform') {
        delete options.authenticatorSelection.authenticatorAttachment;
      }

      setRegisterStatus(AUTH_T.AUTH_JS_CONFIRM_DEVICE);
      const credential = await navigator.credentials.create({ publicKey: options });
      if (!credential) {
        throw new Error('Registration cancelled.');
      }

      const credentialPayload = {
        id: credential.id,
        type: credential.type,
        rawId: bufferToB64url(credential.rawId),
        response: {
          clientDataJSON: bufferToB64url(credential.response.clientDataJSON),
          attestationObject: bufferToB64url(credential.response.attestationObject),
          transports: credential.response.getTransports ? credential.response.getTransports() : [],
        },
      };

      const { response: finishResponse, payload: finishPayloadRaw } = await fetchJsonWithTimeout('/api/v1/auth/passkey/signup/finish', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ challengeId, credential: credentialPayload }),
      });

      const finishPayload = finishPayloadRaw && typeof finishPayloadRaw === 'object' ? finishPayloadRaw : {};
      if (!finishResponse.ok || finishPayload.status !== 'success') {
        throw new Error(finishPayload.message || 'Passkey signup failed.');
      }

      const emailSent = finishPayload.verification_email_sent === true;
      if (emailSent) {
        setRegisterStatus(AUTH_T.AUTH_JS_REGISTER_CHECK_EMAIL);
      } else {
        setRegisterStatus(AUTH_T.AUTH_JS_REGISTER_CHECK_EMAIL);
      }

      hideAuthBanner();
      window.location.href = '/';
    } catch (error) {
      const msg = error?.message || 'Registration failed. Try again.';
      showAuthError(msg, 'register');
    } finally {
      registerInFlight = false;
      setButtonBusy(registerButton, false);
    }
  });
}

if (!isWebAuthnCapableBrowser()) {
  if (passkeyButton) {
    passkeyButton.disabled = true;
    passkeyButton.setAttribute('aria-disabled', 'true');
  }
  if (passkeyPhoneButton) {
    passkeyPhoneButton.disabled = true;
    passkeyPhoneButton.setAttribute('aria-disabled', 'true');
  }
  setPasskeyStatus(WEB_AUTHN_UNSUPPORTED_MESSAGE);
  applyRegisterWebAuthnWarningState(false);
}

// Auth tab switching
const authShell = document.getElementById('auth-shell');
if (authShell) {
  const tabButtons = Array.from(document.querySelectorAll('.auth-tab[data-tab]'));

  const setTab = (tab) => {
    const isRegister = tab === 'register';
    authShell.classList.toggle('is-register', isRegister);

    if (isRegister) {
      applyRegisterWebAuthnWarningState(true);
    }

    document.querySelectorAll('[data-tab="signin"], [data-tab="register"]').forEach((el) => {
      if (!el.classList.contains('auth-tab')) return;
      const active = el.getAttribute('data-tab') === tab;
      el.classList.toggle('active', active);
      el.setAttribute('aria-selected', active ? 'true' : 'false');
      el.setAttribute('tabindex', active ? '0' : '-1');
    });

    const signinPanel = document.getElementById('panel-signin');
    const registerPanel = document.getElementById('panel-register');
    if (signinPanel && registerPanel) {
      const showRegister = tab === 'register';
      const updatePanelState = (panel, isActive) => {
        panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        panel.inert = !isActive;
        if (isActive) {
          panel.removeAttribute('inert');
        } else {
          panel.setAttribute('inert', '');
        }
      };

      updatePanelState(signinPanel, !showRegister);
      updatePanelState(registerPanel, showRegister);
      // Keep both panels in the slider track so translateX math remains stable.
      // Using hidden/display:none collapses the track width and can push panels off-canvas.
      signinPanel.hidden = false;
      registerPanel.hidden = false;
    }
  };

  const activateTabButton = (btn, { focus = false } = {}) => {
    const tab = btn?.getAttribute('data-tab') || 'signin';
    setTab(tab);
    const url = new URL(window.location.href);
    url.searchParams.set('auth_tab', tab);
    history.replaceState(null, '', `${url.pathname}?${url.searchParams.toString()}`);
    if (focus) {
      btn.focus();
    }
  };

  document.querySelectorAll('[data-tab]').forEach((btn) => {
    btn.addEventListener('click', () => {
      activateTabButton(btn);
    });

    btn.addEventListener('keydown', (e) => {
      if (tabButtons.length === 0) {
        return;
      }

      const currentIndex = tabButtons.indexOf(btn);
      if (currentIndex < 0) {
        return;
      }

      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        activateTabButton(btn, { focus: true });
        return;
      }

      if (e.key === 'ArrowRight' || e.key === 'ArrowLeft' || e.key === 'Home' || e.key === 'End') {
        e.preventDefault();

        let nextIndex = currentIndex;
        if (e.key === 'ArrowRight') {
          nextIndex = (currentIndex + 1) % tabButtons.length;
        } else if (e.key === 'ArrowLeft') {
          nextIndex = (currentIndex - 1 + tabButtons.length) % tabButtons.length;
        } else if (e.key === 'Home') {
          nextIndex = 0;
        } else if (e.key === 'End') {
          nextIndex = tabButtons.length - 1;
        }

        const nextBtn = tabButtons[nextIndex];
        if (nextBtn) {
          activateTabButton(nextBtn, { focus: true });
        }
      }
    });
  });

  setTab(authShell.classList.contains('is-register') ? 'register' : 'signin');
}

// Prevent default form submission for both signin and register forms
// since form handling is done via button click listeners for passkey workflows
const signinForm = document.getElementById('signin-form');
if (signinForm) {
  signinForm.addEventListener('submit', (e) => {
    e.preventDefault();
    e.stopPropagation();
  });
}

const registerForm = document.getElementById('register-form');
if (registerForm) {
  registerForm.addEventListener('submit', (e) => {
    e.preventDefault();
    e.stopPropagation();
  });
}
