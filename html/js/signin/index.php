<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__ . '/../../config.php';

CORS::handleORIGIN();
Javascript::renderModuleContentType('application/javascript');

$authI18nKeys = [
  'AUTH_SIGNIN_PASSKEY_STATUS',
  'AUTH_REGISTER_PASSKEY_STATUS',
  'AUTH_JS_WEBAUTHN_UNSUPPORTED',
  'AUTH_JS_REGISTER_UNSUPPORTED',
  'AUTH_JS_REGISTER_UNSUPPORTED_HELP',
  'AUTH_JS_CONFIRM_DEVICE',
  'AUTH_JS_SUCCESS_REDIRECTING',
  'AUTH_JS_REGISTER_CHECK_EMAIL',
  'AUTH_JS_REGISTER_FAILED',
  'AUTH_JS_EMAIL_ALREADY_REGISTERED',
  'AUTH_JS_INVALID_INVITE_CODE',
  'AUTH_JS_ENTER_FULL_NAME',
  'AUTH_JS_ENTER_VALID_EMAIL',
  'AUTH_JS_IMMEDIATE_UI_MISS',
  'AUTH_JS_IMMEDIATE_UI_MISS',
  'AUTH_JS_NO_ACCOUNT',
  'AUTH_JS_SIGNIN_FAILED',
  'AUTH_JS_REQUEST_TIMEOUT',
  'AUTH_JS_NETWORK_ERROR',
  'AUTH_JS_PASSKEY_CANCEL',
  'AUTH_JS_PASSKEY_PHONE_CANCEL',
  'AUTH_JS_PASSKEY_CREDENTIAL_REJECTED',
  'AUTH_JS_PASSKEY_COMPROMISED',
  'AUTH_JS_PASSKEY_MISMATCH_DETAIL',
  'AUTH_JS_PASSKEY_NOT_RECOGNIZED',
  'AUTH_JS_CONNECT_FAILED',
  'AUTH_JS_RATE_LIMIT_FMT',
  'AUTH_JS_TRY_AGAIN',
  'AUTH_JS_TRY_ANOTHER_PASSKEY',
  'AUTH_JS_TRY_PHONE_AGAIN',
  'AUTH_JS_TRY_PHONE_OR_TABLET_AGAIN',
  'AUTH_JS_USE_PASSKEY_THIS_DEVICE',
  'AUTH_JS_USE_ANOTHER_PASSKEY',
  'AUTH_JS_EDIT_EMAIL',
  'AUTH_RECOVER_ACCOUNT',
  'AUTH_FEDERATED_CONTINUE_GOOGLE',
  'AUTH_FEDERATED_CONTINUE_APPLE',
  'AUTH_FEDERATED_CONTINUE_MICROSOFT',
];
$authI18n = [];
foreach ($authI18nKeys as $authI18nKey) {
  $authI18n[$authI18nKey] = Strings::i18n($authI18nKey);
}

?>
import { fromBase64Url as b64urlToBuffer, toBase64Url as bufferToB64url } from '/js/core/binary-codec.js';
import { isWebAuthnCapableBrowser } from '/js/core/capabilities.js';
import { setActionBusy as setButtonBusy } from '/js/core/actions.js';
import { clearFieldErrorStates, setFieldErrorState } from '/js/core/forms.js';
import { formatTemplate as formatAuthMessage } from '/js/core/template.js';

// Passkey-only auth helpers for /auth

const AUTH_T = <?php echo json_encode($authI18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const authScriptEl = document.querySelector('script[src*="/js/signin/"]');
const AUTH_CONFIG = window.PayCalAuthConfig && typeof window.PayCalAuthConfig === 'object'
  ? window.PayCalAuthConfig
  : {
    immediateUiAllowed: authScriptEl instanceof HTMLScriptElement && authScriptEl.dataset.immediateUiAllowed === '1',
    immediateUiRuntimeEnabled: authScriptEl instanceof HTMLScriptElement && authScriptEl.dataset.immediateUiRuntimeEnabled === '1',
  };

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

const signinNoticeEl = document.getElementById('signin-notice');
const signinErrorActionsEl = document.getElementById('signin-error-actions');
const emailInputEl = document.getElementById('email');

const REGISTER_FIELD_PAIRS = [
  ['register-full-name', 'register_full_name_error'],
  ['register-email', 'register_email_error'],
  ['invite_code', 'register_invite_code_error'],
  ['register-device-name', 'register_device_name_error'],
];

const clearRegisterFieldErrors = () => {
  clearFieldErrorStates(REGISTER_FIELD_PAIRS);
};

const clearSigninEmailFieldError = () => {
  setFieldErrorState(emailInputEl, 'signin_email_error', '');
};

const emailLooksValid = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());

const applyRegisterFieldErrorsFromMessage = (message) => {
  clearRegisterFieldErrors();
  const normalized = String(message || '').trim();
  if (normalized === '') {
    return;
  }

  if (/full name is required/i.test(normalized) || normalized === AUTH_T.AUTH_JS_ENTER_FULL_NAME) {
    setFieldErrorState(document.getElementById('register-full-name'), 'register_full_name_error', AUTH_T.AUTH_JS_ENTER_FULL_NAME);
    return;
  }
  if (/valid email is required/i.test(normalized) || normalized === AUTH_T.AUTH_JS_ENTER_VALID_EMAIL) {
    setFieldErrorState(document.getElementById('register-email'), 'register_email_error', AUTH_T.AUTH_JS_ENTER_VALID_EMAIL);
    return;
  }
  if (/already registered/i.test(normalized) || normalized === AUTH_T.AUTH_JS_EMAIL_ALREADY_REGISTERED) {
    setFieldErrorState(document.getElementById('register-email'), 'register_email_error', AUTH_T.AUTH_JS_EMAIL_ALREADY_REGISTERED);
    return;
  }
  if (/invalid invite code/i.test(normalized) || normalized === AUTH_T.AUTH_JS_INVALID_INVITE_CODE) {
    setFieldErrorState(document.getElementById('invite_code'), 'register_invite_code_error', AUTH_T.AUTH_JS_INVALID_INVITE_CODE);
  }
};

const validateRegisterFields = ({ fullName, email }) => {
  clearRegisterFieldErrors();
  let firstInvalidField = null;

  if (!fullName) {
    setFieldErrorState(document.getElementById('register-full-name'), 'register_full_name_error', AUTH_T.AUTH_JS_ENTER_FULL_NAME);
    firstInvalidField = firstInvalidField || document.getElementById('register-full-name');
  }

  if (!email) {
    setFieldErrorState(document.getElementById('register-email'), 'register_email_error', AUTH_T.AUTH_JS_ENTER_VALID_EMAIL);
    firstInvalidField = firstInvalidField || document.getElementById('register-email');
  } else if (!emailLooksValid(email)) {
    setFieldErrorState(document.getElementById('register-email'), 'register_email_error', AUTH_T.AUTH_JS_ENTER_VALID_EMAIL);
    firstInvalidField = firstInvalidField || document.getElementById('register-email');
  }

  if (firstInvalidField instanceof HTMLElement) {
    firstInvalidField.focus();
  }

  return firstInvalidField === null;
};

const hideSigninNotice = () => {
  if (signinNoticeEl) {
    signinNoticeEl.hidden = true;
    signinNoticeEl.classList.remove('is-security');
    signinNoticeEl.replaceChildren();
  }
  if (signinErrorActionsEl) {
    signinErrorActionsEl.hidden = true;
    signinErrorActionsEl.replaceChildren();
  }
};

const showSigninNotice = (title, detail = '', { security = false } = {}) => {
  if (!signinNoticeEl) return;
  signinNoticeEl.replaceChildren();
  if (title) {
    const titleEl = document.createElement('strong');
    titleEl.className = 'auth-signin-notice-title';
    titleEl.textContent = title;
    signinNoticeEl.appendChild(titleEl);
  }
  if (detail) {
    const detailEl = document.createElement('span');
    detailEl.textContent = detail;
    signinNoticeEl.appendChild(detailEl);
  }
  signinNoticeEl.classList.toggle('is-security', security === true);
  signinNoticeEl.hidden = false;
};

const showSigninErrorActions = (actions = []) => {
  if (!signinErrorActionsEl) return;
  signinErrorActionsEl.replaceChildren();
  actions.filter((action) => action && !action.link).forEach((action) => {
    if (typeof action.onClick !== 'function') return;
    const button = document.createElement('button');
    button.type = 'button';
    button.className = action.primary ? 'btn btn_primary' : 'btn btn_secondary';
    button.textContent = String(action.label || '');
    button.addEventListener('click', action.onClick);
    signinErrorActionsEl.appendChild(button);
  });

  const links = actions.filter((action) => action && action.link);
  if (links.length > 0) {
    const linksWrap = document.createElement('div');
    linksWrap.className = 'auth-signin-error-links';
    links.forEach((action) => {
      const link = document.createElement('button');
      link.type = 'button';
      link.className = 'btn-link';
      link.textContent = String(action.label || '');
      link.addEventListener('click', action.onClick);
      linksWrap.appendChild(link);
    });
    signinErrorActionsEl.appendChild(linksWrap);
  }

  signinErrorActionsEl.hidden = signinErrorActionsEl.children.length === 0;
};

const focusEmailInput = () => {
  if (emailInputEl instanceof HTMLInputElement) {
    emailInputEl.focus();
    emailInputEl.select();
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

const shouldAutofocusSigninEmail = () => {
  if (!isSigninPanelActive()) {
    return false;
  }
  if (!(emailInputEl instanceof HTMLInputElement)) {
    return false;
  }
  if (emailInputEl.disabled) {
    return false;
  }
  if (document.querySelector('.auth-verification-panel')) {
    return false;
  }
  return true;
};

const tryAutofocusSigninEmail = () => {
  if (!shouldAutofocusSigninEmail()) {
    return;
  }
  focusEmailInput();
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

const buildLoginStartBody = (email) => {
  if (email) {
    return { email };
  }
  return { discoverable: true };
};

const transportHintsForFlow = (preferPhoneFlow) => (
  preferPhoneFlow
    ? ['hybrid', 'client-device', 'security-key']
    : ['client-device', 'hybrid', 'security-key']
);

const authBannerEl = document.getElementById('auth-feedback-banner');
let authBannerTimer = null;
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

const browserSupportsImmediateUi = async () => {
  if (!window.PublicKeyCredential || typeof PublicKeyCredential.getClientCapabilities !== 'function') {
    return false;
  }

  try {
    const capabilities = await PublicKeyCredential.getClientCapabilities();
    return capabilities?.immediateGet === true;
  } catch (_error) {
    return false;
  }
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
  if (statusCode === 401 || /authentication failed/i.test(message)) {
    return AUTH_T.AUTH_JS_NO_ACCOUNT;
  }
  if (statusCode === 429 || /too many attempts/i.test(message)) {
    return message || AUTH_T.AUTH_JS_SIGNIN_FAILED;
  }
  if (message !== '') {
    return message;
  }
  return AUTH_T.AUTH_JS_SIGNIN_FAILED;
};

const classifySigninFailure = (error, context = {}) => {
  const preferPhoneFlow = context.preferPhoneFlow === true;
  const email = String(context.email || '').trim();
  const statusCode = Number(context.statusCode || 0);
  const finishErrorCode = String(context.finishErrorCode || '').trim();
  const message = String(error?.message || '').trim();

  if (statusCode === 429 || /too many attempts/i.test(message)) {
    return { kind: 'rate_limit', retrySeconds: 60 };
  }
  if (message === AUTH_T.AUTH_JS_REQUEST_TIMEOUT || message === AUTH_T.AUTH_JS_NETWORK_ERROR || message === AUTH_T.AUTH_JS_CONNECT_FAILED) {
    return { kind: message === AUTH_T.AUTH_JS_NETWORK_ERROR || message === AUTH_T.AUTH_JS_CONNECT_FAILED ? 'network' : 'network' };
  }
  if (error instanceof DOMException) {
    if (error.name === 'NotAllowedError' || error.name === 'AbortError' || error.name === 'TimeoutError') {
      return { kind: preferPhoneFlow ? 'phone_cancel' : 'cancel' };
    }
  }
  if (finishErrorCode === 'passkey_compromised') {
    return { kind: 'revoked' };
  }
  if (statusCode === 403 && email) {
    return { kind: 'credential_mismatch', email, preferPhoneFlow };
  }
  if (finishErrorCode === 'passkey_invalid' || statusCode === 401 || statusCode === 403) {
    return { kind: 'credential_rejected' };
  }
  return { kind: 'generic', message: message || AUTH_T.AUTH_JS_SIGNIN_FAILED };
};

let rateLimitTimer = null;

const renderSigninFailure = (failure, retryHandler) => {
  hideAuthBanner();
  hideSigninNotice();
  showSigninErrorActions([]);
  setPasskeyStatus(DEFAULT_SIGNIN_STATUS);

  const recoverAction = {
    label: AUTH_T.AUTH_RECOVER_ACCOUNT,
    link: true,
    onClick: () => {
      window.location.href = recoveryUrlWithLanguage();
    },
  };

  switch (failure.kind) {
    case 'cancel':
      showSigninNotice(AUTH_T.AUTH_JS_PASSKEY_CANCEL);
      showSigninErrorActions([{
        label: AUTH_T.AUTH_JS_TRY_AGAIN,
        primary: true,
        onClick: retryHandler,
      }]);
      return;
    case 'phone_cancel':
      showSigninNotice(AUTH_T.AUTH_JS_PASSKEY_PHONE_CANCEL);
      showSigninErrorActions([{
        label: AUTH_T.AUTH_JS_TRY_PHONE_AGAIN,
        primary: true,
        onClick: retryHandler,
      }]);
      return;
    case 'credential_mismatch':
      if (emailInputEl instanceof HTMLInputElement) {
        setFieldErrorState(emailInputEl, 'signin_email_error', AUTH_T.AUTH_JS_PASSKEY_MISMATCH_DETAIL);
      }
      showSigninNotice(
        AUTH_T.AUTH_JS_PASSKEY_CREDENTIAL_REJECTED,
        AUTH_T.AUTH_JS_PASSKEY_MISMATCH_DETAIL,
        { security: true },
      );
      showSigninErrorActions([
        {
          label: AUTH_T.AUTH_JS_TRY_PHONE_OR_TABLET_AGAIN,
          primary: true,
          onClick: () => runPasskeySignin(true),
        },
        {
          label: AUTH_T.AUTH_JS_USE_PASSKEY_THIS_DEVICE,
          primary: false,
          onClick: () => runPasskeySignin(false),
        },
        {
          label: AUTH_T.AUTH_JS_EDIT_EMAIL,
          link: true,
          onClick: focusEmailInput,
        },
        recoverAction,
      ]);
      return;
    case 'credential_rejected':
      showSigninNotice(AUTH_T.AUTH_JS_PASSKEY_NOT_RECOGNIZED, '', { security: true });
      showSigninErrorActions([
        {
          label: AUTH_T.AUTH_JS_USE_ANOTHER_PASSKEY,
          primary: true,
          onClick: retryHandler,
        },
        recoverAction,
      ]);
      return;
    case 'revoked':
      showSigninNotice(AUTH_T.AUTH_JS_PASSKEY_COMPROMISED, '', { security: true });
      showSigninErrorActions([
        {
          label: AUTH_T.AUTH_JS_USE_ANOTHER_PASSKEY,
          primary: true,
          onClick: retryHandler,
        },
        recoverAction,
      ]);
      return;
    case 'network':
      showSigninNotice(AUTH_T.AUTH_JS_CONNECT_FAILED);
      showSigninErrorActions([{
        label: AUTH_T.AUTH_JS_TRY_AGAIN,
        primary: true,
        onClick: retryHandler,
      }]);
      return;
    case 'rate_limit': {
      let remaining = Math.max(1, Number(failure.retrySeconds) || 60);
      const renderCountdown = () => {
        showSigninNotice(formatAuthMessage(AUTH_T.AUTH_JS_RATE_LIMIT_FMT, { seconds: remaining }));
      };
      renderCountdown();
      if (rateLimitTimer) {
        window.clearInterval(rateLimitTimer);
      }
      showSigninErrorActions([{
        label: AUTH_T.AUTH_JS_TRY_AGAIN,
        primary: true,
        onClick: retryHandler,
      }]);
      const tryAgainButton = signinErrorActionsEl?.querySelector('.btn_primary');
      if (tryAgainButton instanceof HTMLButtonElement) {
        tryAgainButton.disabled = true;
        rateLimitTimer = window.setInterval(() => {
          remaining -= 1;
          if (remaining <= 0) {
            window.clearInterval(rateLimitTimer);
            rateLimitTimer = null;
            tryAgainButton.disabled = false;
            hideSigninNotice();
            showSigninErrorActions([]);
            return;
          }
          renderCountdown();
        }, 1000);
      }
      return;
    }
    default:
      showSigninNotice(failure.message || AUTH_T.AUTH_JS_SIGNIN_FAILED);
      showSigninErrorActions([{
        label: AUTH_T.AUTH_JS_TRY_AGAIN,
        primary: true,
        onClick: retryHandler,
      }]);
  }
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

const federatedButtonLabel = (provider) => {
  const labelKey = String(provider?.button_label_key || '').trim();
  if (labelKey !== '' && typeof AUTH_T[labelKey] === 'string' && AUTH_T[labelKey].trim() !== '') {
    return AUTH_T[labelKey].trim();
  }

  return String(provider?.button_label || provider?.label || '').trim();
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
    const buttonLabel = federatedButtonLabel(provider);
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

const completePasskeySignin = async ({
  preferPhoneFlow = false,
  useImmediateUi = false,
  silentFailure = false,
} = {}) => {
  const email = emailInputEl instanceof HTMLInputElement ? emailInputEl.value.trim() : '';

  setPasskeyStatus('Working…');
  const { response: startResponse, payload: startPayloadRaw } = await fetchJsonWithTimeout('/api/v1/auth/passkey/login/start', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify(buildLoginStartBody(email)),
  });
  const startPayload = startPayloadRaw && typeof startPayloadRaw === 'object' ? startPayloadRaw : {};
  if (!startResponse.ok || startPayload.status !== 'success') {
    if (silentFailure) {
      throw new Error(signinFriendlyMessage(startPayload.message, startResponse.status));
    }
    const failure = classifySigninFailure(new Error(signinFriendlyMessage(startPayload.message, startResponse.status)), {
      preferPhoneFlow,
      email,
      statusCode: startResponse.status,
    });
    renderSigninFailure(failure, () => runPasskeySignin(preferPhoneFlow));
    return;
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

  if (useImmediateUi) {
    options.allowCredentials = [];
    delete options.hints;
  } else {
    options.hints = transportHintsForFlow(preferPhoneFlow);
  }

  if (options.authenticatorSelection && options.authenticatorSelection.authenticatorAttachment === 'platform') {
    delete options.authenticatorSelection.authenticatorAttachment;
  }

  setPasskeyStatus(AUTH_T.AUTH_JS_CONFIRM_DEVICE);
  const credentialRequest = { publicKey: options };
  if (useImmediateUi) {
    credentialRequest.uiMode = 'immediate';
  } else {
    credentialRequest.mediation = 'optional';
  }

  const assertion = await navigator.credentials.get(credentialRequest);
  if (!assertion) {
    throw Object.assign(new Error(AUTH_T.AUTH_JS_PASSKEY_CANCEL), { name: 'NotAllowedError' });
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
    if (silentFailure) {
      throw new Error(finishPayload.message || AUTH_T.AUTH_JS_SIGNIN_FAILED);
    }
    const failure = classifySigninFailure(new Error(finishPayload.message || AUTH_T.AUTH_JS_SIGNIN_FAILED), {
      preferPhoneFlow,
      email,
      statusCode: finishResponse.status,
      finishErrorCode,
    });
    renderSigninFailure(failure, () => runPasskeySignin(preferPhoneFlow));
    return;
  }

  setPasskeyStatus(AUTH_T.AUTH_JS_SUCCESS_REDIRECTING);
  hideAuthBanner();
  hideSigninNotice();
  window.location.href = preferPhoneFlow ? '/?passkey_device_hint=1' : '/';
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
    hideSigninNotice();
    clearSigninEmailFieldError();
    showSigninErrorActions([]);

    if (!isWebAuthnCapableBrowser()) {
      showAuthError(WEB_AUTHN_UNSUPPORTED_MESSAGE, 'signin');
      return;
    }

    const email = emailInputEl instanceof HTMLInputElement ? emailInputEl.value.trim() : '';
    if (email !== '' && !emailLooksValid(email)) {
      setFieldErrorState(emailInputEl, 'signin_email_error', AUTH_T.AUTH_JS_ENTER_VALID_EMAIL);
      emailInputEl.focus();
      return;
    }

    await completePasskeySignin({ preferPhoneFlow, useImmediateUi: false });
  } catch (error) {
    const email = emailInputEl instanceof HTMLInputElement ? emailInputEl.value.trim() : '';
    const failure = classifySigninFailure(error, { preferPhoneFlow, email });
    renderSigninFailure(failure, () => runPasskeySignin(preferPhoneFlow));
  } finally {
    signinInFlight = false;
    setButtonBusy(passkeyButton, false);
    setButtonBusy(passkeyPhoneButton, false);
  }
};

let passiveImmediateUiAttempted = false;

const tryPassiveImmediateUiSignin = async () => {
  if (passiveImmediateUiAttempted || signinInFlight || !isSigninPanelActive()) {
    return;
  }
  if (AUTH_CONFIG.immediateUiAllowed !== true || AUTH_CONFIG.immediateUiRuntimeEnabled !== true) {
    return;
  }
  if (emailInputEl instanceof HTMLInputElement && emailInputEl.value.trim() !== '') {
    return;
  }
  if (!isWebAuthnCapableBrowser() || !(await browserSupportsImmediateUi())) {
    return;
  }

  passiveImmediateUiAttempted = true;
  signinInFlight = true;

  try {
    await completePasskeySignin({ preferPhoneFlow: false, useImmediateUi: true, silentFailure: true });
  } catch (error) {
    if (error instanceof DOMException && error.name === 'NotAllowedError') {
      setPasskeyStatus(AUTH_T.AUTH_JS_IMMEDIATE_UI_MISS || DEFAULT_SIGNIN_STATUS);
      return;
    }
    // Passive enhancement: ignore other failures silently.
  } finally {
    signinInFlight = false;
    tryAutofocusSigninEmail();
  }
};

// Keep passkey sign-in user-initiated to avoid background 401s from silent
// conditional mediation probes that create confusing console noise.
loadFederatedProviders();

const passkeyButton = document.getElementById('signin-passkey');

const shouldTriggerSigninPasskeyFromEmail = () => {
  if (!shouldAutofocusSigninEmail()) {
    return false;
  }
  if (!(passkeyButton instanceof HTMLButtonElement) || passkeyButton.disabled) {
    return false;
  }
  return true;
};

if (passkeyButton) {
  passkeyButton.addEventListener('click', async () => {
    await runPasskeySignin(false);
  });
}

if (emailInputEl) {
  emailInputEl.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') {
      return;
    }
    if (!shouldTriggerSigninPasskeyFromEmail()) {
      return;
    }
    e.preventDefault();
    void runPasskeySignin(false);
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
const signupRegisterForm = document.getElementById('register-form');
const signupStorageKey = 'paycal.signup.personalization.v1';
const signupDefaults = {
  tier: signupRegisterForm instanceof HTMLFormElement ? String(signupRegisterForm.dataset.signupInitialTier || 'free') : 'free',
  themeMode: 'system',
  accentPreset: 'blue',
  textSize: 'standard',
  spacing: 'comfortable',
  language: signupRegisterForm instanceof HTMLFormElement ? String(signupRegisterForm.dataset.signupInitialLanguage || 'en') : 'en',
  payFrequency: 'biweekly',
  signupIntent: 'worker',
};
const signupAccentLabels = {
  blue: 'Blue',
  green: 'Green',
  purple: 'Purple',
  amber: 'Amber',
  red: 'Red',
  slate: 'Slate',
};
const signupTierLabels = {
  free: 'Free Personal',
  premium: 'Premium',
  business: 'Business',
};
const signupTierTitles = {
  free: 'Your PayCal',
  premium: 'Your PayCal Premium',
  business: 'Your PayCal Business workspace',
};
const signupPayLabels = {
  weekly: 'Weekly',
  biweekly: 'Biweekly',
  semimonthly: 'Semimonthly',
  monthly: 'Monthly',
};
const signupIntentLabels = {
  worker: 'Worker',
  manager: 'Manager',
  business: 'Business',
};
const signupSystemThemeQuery = typeof window.matchMedia === 'function'
  ? window.matchMedia('(prefers-color-scheme: light)')
  : null;

const safeSignupValue = (value, allowed, fallback) => {
  const normalized = String(value || '').trim().toLowerCase();
  return allowed.includes(normalized) ? normalized : fallback;
};

const getCheckedSignupValue = (name, fallback = '') => {
  if (!(signupRegisterForm instanceof HTMLFormElement)) {
    return fallback;
  }
  const checked = signupRegisterForm.querySelector(`input[name="${name}"]:checked`);
  return checked instanceof HTMLInputElement ? checked.value : fallback;
};

const setCheckedSignupValue = (name, value) => {
  if (!(signupRegisterForm instanceof HTMLFormElement)) {
    return;
  }
  const inputs = Array.from(signupRegisterForm.querySelectorAll(`input[name="${name}"]`));
  inputs.forEach((input) => {
    if (input instanceof HTMLInputElement) {
      input.checked = input.value === value;
    }
  });
};

const setSelectSignupValue = (id, value) => {
  const select = document.getElementById(id);
  if (!(select instanceof HTMLSelectElement)) {
    return;
  }
  const hasOption = Array.from(select.options).some((option) => option.value === value);
  if (hasOption) {
    select.value = value;
  }
};

const selectedSignupAccent = () => {
  const selected = document.querySelector('.auth-accent-swatch.is-selected');
  return selected instanceof HTMLElement ? String(selected.dataset.signupAccent || 'blue') : 'blue';
};

const resolveSignupVariant = (themeMode) => {
  if (themeMode === 'light') return 'light';
  if (themeMode === 'dark') return 'dark';
  return signupSystemThemeQuery?.matches === true ? 'light' : 'dark';
};

const readStoredSignupPreferences = () => {
  try {
    const parsed = JSON.parse(window.localStorage.getItem(signupStorageKey) || '{}');
    if (!parsed || typeof parsed !== 'object') {
      return {};
    }
    return {
      tier: safeSignupValue(parsed.tier, ['free', 'premium', 'business'], signupDefaults.tier),
      themeMode: safeSignupValue(parsed.themeMode, ['light', 'dark', 'system'], signupDefaults.themeMode),
      accentPreset: safeSignupValue(parsed.accentPreset, ['blue', 'green', 'purple', 'amber', 'red', 'slate'], signupDefaults.accentPreset),
      textSize: safeSignupValue(parsed.textSize, ['standard', 'larger'], signupDefaults.textSize),
      spacing: safeSignupValue(parsed.spacing, ['compact', 'comfortable'], signupDefaults.spacing),
      language: typeof parsed.language === 'string' ? parsed.language : signupDefaults.language,
      payFrequency: safeSignupValue(parsed.payFrequency, ['weekly', 'biweekly', 'semimonthly', 'monthly'], signupDefaults.payFrequency),
      signupIntent: safeSignupValue(parsed.signupIntent, ['worker', 'manager', 'business'], signupDefaults.signupIntent),
    };
  } catch (_error) {
    return {};
  }
};

const storeSignupPreferences = (state) => {
  try {
    window.localStorage.setItem(signupStorageKey, JSON.stringify({
      tier: state.tier,
      themeMode: state.themeMode,
      accentPreset: state.accentPreset,
      textSize: state.textSize,
      spacing: state.spacing,
      language: state.language,
      payFrequency: state.payFrequency,
      signupIntent: state.signupIntent,
    }));
  } catch (_error) {
    // Browser storage is best-effort only.
  }
};

const clearStoredSignupPreferences = () => {
  try {
    window.localStorage.removeItem(signupStorageKey);
  } catch (_error) {
    // Browser storage is best-effort only.
  }
};

const readSignupPersonalizationState = () => {
  const languageSelect = document.getElementById('signup-language');
  const paySelect = document.getElementById('signup-pay-frequency');
  const dashboardNameInput = document.getElementById('signup-dashboard-name');
  const themeMode = safeSignupValue(getCheckedSignupValue('signup_theme_mode', signupDefaults.themeMode), ['light', 'dark', 'system'], signupDefaults.themeMode);
  const tier = safeSignupValue(getCheckedSignupValue('signup_tier', signupDefaults.tier), ['free', 'premium', 'business'], signupDefaults.tier);
  const accentPreset = safeSignupValue(selectedSignupAccent(), ['blue', 'green', 'purple', 'amber', 'red', 'slate'], signupDefaults.accentPreset);

  return {
    tier,
    themeMode,
    resolvedVariant: resolveSignupVariant(themeMode),
    accentPreset,
    textSize: safeSignupValue(getCheckedSignupValue('signup_text_size', signupDefaults.textSize), ['standard', 'larger'], signupDefaults.textSize),
    spacing: safeSignupValue(getCheckedSignupValue('signup_spacing', signupDefaults.spacing), ['compact', 'comfortable'], signupDefaults.spacing),
    language: languageSelect instanceof HTMLSelectElement ? languageSelect.value : signupDefaults.language,
    payFrequency: paySelect instanceof HTMLSelectElement ? safeSignupValue(paySelect.value, ['weekly', 'biweekly', 'semimonthly', 'monthly'], signupDefaults.payFrequency) : signupDefaults.payFrequency,
    signupIntent: safeSignupValue(getCheckedSignupValue('signup_intent', signupDefaults.signupIntent), ['worker', 'manager', 'business'], signupDefaults.signupIntent),
    dashboardName: dashboardNameInput instanceof HTMLInputElement ? dashboardNameInput.value.trim() : '',
  };
};

const syncSignupAccentButtons = (accentPreset) => {
  document.querySelectorAll('.auth-accent-swatch').forEach((button) => {
    if (!(button instanceof HTMLElement)) {
      return;
    }
    const selected = button.dataset.signupAccent === accentPreset;
    button.classList.toggle('is-selected', selected);
    button.setAttribute('aria-pressed', selected ? 'true' : 'false');
  });
};

const syncSignupTierCards = (tier) => {
  document.querySelectorAll('[data-signup-tier-card]').forEach((card) => {
    if (!(card instanceof HTMLElement)) {
      return;
    }
    card.classList.toggle('is-selected', card.dataset.signupTierCard === tier);
  });
};

const applySignupPreviewTheme = (state) => {
  document.documentElement.setAttribute('data-accent-preset', state.accentPreset);
  delete document.body.dataset.authPreviewVariant;
  const shell = document.getElementById('auth-shell');
  if (shell instanceof HTMLElement) {
    shell.dataset.signupPreviewVariant = state.resolvedVariant;
  }
};

const updateSignupPreview = (state) => {
  const tierLabel = signupTierLabels[state.tier] || signupTierLabels.free;
  const accentLabel = signupAccentLabels[state.accentPreset] || state.accentPreset;
  const payLabel = signupPayLabels[state.payFrequency] || state.payFrequency;
  const themeLabel = state.themeMode === 'system' ? 'System' : (state.resolvedVariant === 'light' ? 'Light' : 'Dark');
  const spacingLabel = state.spacing === 'compact' ? 'Compact' : 'Comfortable';
  const textLabel = state.textSize === 'larger' ? 'Larger text' : 'Standard text';
  const intentLabel = signupIntentLabels[state.signupIntent] || signupIntentLabels.worker;
  const fallbackTitle = signupTierTitles[state.tier] || signupTierTitles.free;
  const title = state.dashboardName !== '' ? state.dashboardName : fallbackTitle;

  const confirmEl = document.querySelector('[data-signup-tier-confirm]');
  if (confirmEl) {
    confirmEl.textContent = `Your starting setup: ${tierLabel}.`;
  }

  const titleEl = document.querySelector('[data-signup-preview-title]');
  if (titleEl) {
    titleEl.textContent = title;
  }

  const metaEl = document.querySelector('[data-signup-preview-meta]');
  if (metaEl) {
    metaEl.textContent = `${themeLabel} theme - ${accentLabel} accent - ${payLabel} pay`;
  }

  const listEl = document.querySelector('[data-signup-preview-list]');
  if (listEl) {
    const items = state.tier === 'business'
      ? ['Business dashboard', 'Shared calendar view', 'Team reporting', `${spacingLabel} spacing`, textLabel]
      : [`${spacingLabel} calendar spacing`, textLabel, `${intentLabel} setup`];
    listEl.replaceChildren(...items.map((item) => {
      const li = document.createElement('li');
      li.textContent = item;
      return li;
    }));
  }
};

const syncSignupPersonalization = ({ persist = true } = {}) => {
  if (!(signupRegisterForm instanceof HTMLFormElement)) {
    return;
  }
  const state = readSignupPersonalizationState();
  syncSignupTierCards(state.tier);
  syncSignupAccentButtons(state.accentPreset);
  applySignupPreviewTheme(state);
  updateSignupPreview(state);
  if (persist) {
    storeSignupPreferences(state);
  }
};

const applyInitialSignupPersonalization = () => {
  if (!(signupRegisterForm instanceof HTMLFormElement)) {
    return;
  }
  const stored = readStoredSignupPreferences();
  const initialTier = String(signupRegisterForm.dataset.signupInitialTier || '').trim().toLowerCase();
  const state = {
    ...signupDefaults,
    ...stored,
    tier: ['free', 'premium', 'business'].includes(initialTier) ? initialTier : (stored.tier || signupDefaults.tier),
  };

  setCheckedSignupValue('signup_tier', state.tier);
  setCheckedSignupValue('signup_theme_mode', state.themeMode);
  setCheckedSignupValue('signup_text_size', state.textSize);
  setCheckedSignupValue('signup_spacing', state.spacing);
  setCheckedSignupValue('signup_intent', state.signupIntent);
  setSelectSignupValue('signup-language', state.language);
  setSelectSignupValue('signup-pay-frequency', state.payFrequency);
  syncSignupAccentButtons(state.accentPreset);
  syncSignupPersonalization({ persist: false });
};

const signupStepOrder = ['tier', 'personalize', 'secure'];

const signupStepIndex = (step) => {
  const index = signupStepOrder.indexOf(String(step || ''));
  return index >= 0 ? index : 0;
};

const setSignupStepInert = (stepEl, shouldBeInert) => {
  if ('inert' in stepEl) {
    stepEl.inert = shouldBeInert;
  }
  if (shouldBeInert) {
    stepEl.setAttribute('inert', '');
  } else {
    stepEl.removeAttribute('inert');
  }
};

const showSignupStep = (step, { focus = false } = {}) => {
  if (!(signupRegisterForm instanceof HTMLFormElement)) {
    return;
  }

  const nextStep = signupStepOrder.includes(step) ? step : signupStepOrder[0];
  const nextStepIndex = signupStepIndex(nextStep);
  signupRegisterForm.dataset.signupCurrentStep = nextStep;

  signupRegisterForm.querySelectorAll('[data-signup-step]').forEach((stepEl) => {
    if (!(stepEl instanceof HTMLElement)) {
      return;
    }
    const active = stepEl.dataset.signupStep === nextStep;
    stepEl.classList.toggle('is-active', active);
    stepEl.hidden = !active;
    stepEl.setAttribute('aria-hidden', active ? 'false' : 'true');
    setSignupStepInert(stepEl, !active);
  });

  signupRegisterForm.querySelectorAll('[data-signup-step-trigger]').forEach((trigger) => {
    if (!(trigger instanceof HTMLElement)) {
      return;
    }
    const triggerStep = String(trigger.dataset.signupStepTrigger || '');
    const triggerIndex = signupStepIndex(triggerStep);
    const active = triggerStep === nextStep;
    trigger.classList.toggle('is-active', active);
    trigger.classList.toggle('is-complete', triggerIndex < nextStepIndex);
    if (active) {
      trigger.setAttribute('aria-current', 'step');
    } else {
      trigger.removeAttribute('aria-current');
    }
  });

  if (focus) {
    const activePanel = signupRegisterForm.querySelector(`[data-signup-step="${nextStep}"]`);
    const heading = activePanel instanceof HTMLElement ? activePanel.querySelector('[data-signup-step-heading]') : null;
    if (heading instanceof HTMLElement) {
      heading.focus({ preventScroll: false });
    }
  }
};

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

if (signupRegisterForm) {
  applyInitialSignupPersonalization();
  showSignupStep(String(signupRegisterForm.dataset.signupCurrentStep || signupStepOrder[0]));
  signupRegisterForm.addEventListener('change', (event) => {
    const target = event.target;
    if (
      target instanceof HTMLInputElement
      || target instanceof HTMLSelectElement
    ) {
      syncSignupPersonalization();
    }
  });
  signupRegisterForm.addEventListener('input', (event) => {
    if (event.target instanceof HTMLInputElement && event.target.id === 'signup-dashboard-name') {
      syncSignupPersonalization({ persist: false });
    }
  });
  signupRegisterForm.querySelectorAll('[data-signup-next], [data-signup-back], [data-signup-step-trigger]').forEach((button) => {
    button.addEventListener('click', () => {
      if (!(button instanceof HTMLElement)) {
        return;
      }
      const nextStep = button.dataset.signupNext || button.dataset.signupBack || button.dataset.signupStepTrigger || signupStepOrder[0];
      showSignupStep(nextStep, { focus: true });
    });
  });
  document.querySelectorAll('.auth-accent-swatch').forEach((button) => {
    button.addEventListener('click', () => {
      if (!(button instanceof HTMLElement)) {
        return;
      }
      syncSignupAccentButtons(String(button.dataset.signupAccent || 'blue'));
      syncSignupPersonalization();
    });
  });
  if (signupSystemThemeQuery && typeof signupSystemThemeQuery.addEventListener === 'function') {
    signupSystemThemeQuery.addEventListener('change', () => {
      if (getCheckedSignupValue('signup_theme_mode', signupDefaults.themeMode) === 'system') {
        syncSignupPersonalization({ persist: false });
      }
    });
  }
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
      clearRegisterFieldErrors();

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
      const signupPersonalization = readSignupPersonalizationState();

      showSignupStep('secure');
      if (!validateRegisterFields({ fullName, email })) {
        setRegisterStatus(DEFAULT_REGISTER_STATUS);
        return;
      }

      setRegisterStatus('Working…');
      const { response: startResponse, payload: startPayloadRaw } = await fetchJsonWithTimeout('/api/v1/auth/passkey/signup/start', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ fullName, email, inviteCode, deviceName, personalization: signupPersonalization }),
      });

      const startPayload = startPayloadRaw && typeof startPayloadRaw === 'object' ? startPayloadRaw : {};
      if (!startResponse.ok || startPayload.status !== 'success') {
        const friendly = signupFriendlyMessage(startPayload.message);
        showSignupStep('secure');
        applyRegisterFieldErrorsFromMessage(friendly);
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
      clearStoredSignupPreferences();
      window.location.href = '/';
    } catch (error) {
      const msg = error?.message || 'Registration failed. Try again.';
      showSignupStep('secure');
      applyRegisterFieldErrorsFromMessage(msg);
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

  const setTab = (tab, { autofocusEmail = false } = {}) => {
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

      if (!showRegister && autofocusEmail) {
        tryAutofocusSigninEmail();
      }
    }
  };

  const activateTabButton = (btn, { focus = false } = {}) => {
    const tab = btn?.getAttribute('data-tab') || 'signin';
    setTab(tab, { autofocusEmail: tab === 'signin' });
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

const initAuthSigninFocus = async () => {
  await tryPassiveImmediateUiSignin();
  tryAutofocusSigninEmail();
};

initAuthSigninFocus();

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
