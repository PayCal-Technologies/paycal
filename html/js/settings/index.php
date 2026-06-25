<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();

$i18nKeys = [
  'INFO_UPDATED',
  'SIGN_OUT',
  'UPDATING_CALENDAR_AUDIO_LABELS_TO',
  'UPDATING_CALENDAR_AUTOFOCUS_TO',
  'UPDATING_CALENDAR_DATE_LABEL_POSITION_TO',
  'UPDATING_CALENDAR_DAY_NAME_FORMAT_TO',
  'UPDATING_CALENDAR_DAY_NAME_POSITION_TO',
  'UPDATING_CALENDAR_WORK_ENTRY_POSITION_TO',
  'UPDATING_CALENDAR_WEEK_START_TO',
  'UPDATING_CALENDAR_DEFAULT_VIEW_TO',
  'UPDATING_INFO',
  'UPDATING_PAY_PERIOD',
  'CANCEL',
  'PREVIOUS',
  'DELETE_ACCOUNT',
  'SETTINGS_ACCOUNT_DETAILS_TITLE',
  'CHANGE_EMAIL',
  'BUSINESSES_CORRECT_HIGHLIGHTED_FIELDS',
  'BUSINESSES_SAVE_ACCOUNT_DETAILS_FAILED',
  'BUSINESSES_UNKNOWN_ERROR',
  'SETTINGS_JS_PASSKEYS_NONE',
  'SETTINGS_JS_PASSKEYS_LOADED_NONE',
  'SETTINGS_JS_PASSKEYS_LOADED_COUNT',
  'SETTINGS_JS_PASSKEYS_CHECKING',
  'SETTINGS_JS_PASSKEYS_LOAD_FAILED',
  'SETTINGS_JS_PASSKEYS_UPDATE_NAME_PROGRESS',
  'SETTINGS_JS_PASSKEYS_UPDATE_NAME_FAILED',
  'SETTINGS_JS_PASSKEYS_UPDATE_NAME_SUCCESS',
  'SETTINGS_JS_PASSKEYS_REMOVING',
  'SETTINGS_JS_PASSKEYS_REMOVED',
  'SETTINGS_JS_PASSKEYS_UPDATE_FAILED',
  'SETTINGS_PASSKEYS_COUNT_RECOMMENDATION',
  'SETTINGS_PASSKEYS_EMPTY_TITLE',
  'SETTINGS_PASSKEYS_EMPTY_DESC',
  'SETTINGS_PASSKEYS_ADD_FIRST',
  'SETTINGS_PASSKEYS_ADD_TITLE',
  'SETTINGS_PASSKEYS_ADD_DESC',
  'SETTINGS_PASSKEYS_ADD_BUTTON',
  'SETTINGS_PASSKEYS_ADDED_LABEL',
  'SETTINGS_PASSKEYS_LAST_USED_LABEL',
  'SETTINGS_PASSKEYS_LAST_USED_NEVER',
  'SETTINGS_PASSKEYS_UNNAMED',
  'SETTINGS_PASSKEYS_RECOVERED_META',
  'SETTINGS_PASSKEY_BADGE_THIS_DEVICE',
  'SETTINGS_PASSKEY_BADGE_RECOVERED',
  'SETTINGS_PASSKEY_BADGE_NEVER_USED',
  'SETTINGS_PASSKEY_BADGE_RECENTLY_USED',
  'SETTINGS_PASSKEY_BADGE_SECURITY_KEY',
  'SETTINGS_PASSKEY_RENAME',
  'SETTINGS_PASSKEY_REMOVE',
  'SETTINGS_PASSKEY_MENU_ARIA',
  'SETTINGS_PASSKEYS_SETTING_UP',
  'SETTINGS_PASSKEY_STATUS_THIS_DEVICE',
  'SETTINGS_PASSKEY_STATUS_RECENTLY_USED',
  'SETTINGS_PASSKEY_STATUS_NEVER_USED',
  'SETTINGS_PASSKEY_STATUS_RECOVERY',
  'SETTINGS_PASSKEY_STATUS_SECURITY_KEY',
  'SETTINGS_PASSKEY_STATUS_DEFAULT',
  'REMOVE',
  'SETTINGS_JS_WORK_ENTRY_FIELDS_UPDATED',
  'SETTINGS_JS_CALENDAR_DISPLAY_UPDATED',
  'SETTINGS_JS_DENSITY_UPDATED_FMT',
  'SETTINGS_JS_DEPTH_UPDATED_FMT',
  'SETTINGS_JS_ACCENT_UPDATED_FMT',
  'SETTINGS_JS_HIGH_CONTRAST_UPDATED_FMT',
  'SETTINGS_JS_REDUCED_MOTION_UPDATED_FMT',
  'SETTINGS_JS_SR_VERBOSITY_UPDATED_FMT',
  'SETTINGS_JS_KEYBOARD_SHORTCUTS_HINT_UPDATED_FMT',
  'SETTINGS_JS_SECURITY_PREF_UPDATED',
  'SETTINGS_JS_DEBUG_TTL_UPDATED',
  'SETTINGS_JS_SESSIONS_LOADED_COUNT_FMT',
  'SETTINGS_JS_SESSIONS_LOADING',
  'SETTINGS_JS_SESSIONS_LOAD_FAILED',
  'SETTINGS_JS_SESSIONS_NONE',
  'SETTINGS_JS_SESSIONS_REVOKED_FMT',
  'SETTINGS_JS_SESSIONS_REVOKE_FAILED',
  'SETTINGS_JS_EXPORT_HISTORY_LOAD_FAILED',
  'SETTINGS_JS_EXPORT_HISTORY_NONE',
  'SETTINGS_JS_EXPORT_ENCRYPT_PROMPT',
  'SETTINGS_JS_EXPORT_ENCRYPT_CONFIRM',
  'SETTINGS_JS_EXPORT_ENCRYPT_MISMATCH',
  'SETTINGS_JS_EXPORT_ENCRYPT_FAILED',
  'SETTINGS_JS_EXPORT_ENCRYPTED_SUCCESS',
  'SETTINGS_JS_RECOVERY_KEY_ACTIVE',
  'SETTINGS_RECOVERY_KEY_COPY_FAILED',
  'SETTINGS_RECOVERY_KEY_COPIED',
  'SETTINGS_RECOVERY_KEY_DISPLAY_INSTRUCTION',
  'SETTINGS_RECOVERY_KEY_REGENERATE_CONFIRM',
  'SETTINGS_RECOVERY_KEY_SUCCESS_CREATE',
  'SETTINGS_RECOVERY_KEY_SUCCESS_REGENERATE',
  'KEYBOARD_SHORTCUTS',
  'SETTINGS_JS_AUDIO_MUTED',
  'SETTINGS_JS_AUDIO_ENABLED',
  'SETTINGS_JS_AUDIO_ENABLED_SPEECH',
  'SETTINGS_JS_AUDIO_TOAST_FMT',
  'SETTINGS_JS_VOICE_UPDATED_FMT',
  'SETTINGS_JS_VOICE_VOLUME_UPDATED',
  'SETTINGS_JS_DEBUG_CONSOLE_TOAST_FMT',
  'SETTINGS_JS_DEBUG_FINE_GRAINED_TOAST_FMT',
  'SETTINGS_JS_DEBUG_NETWORK_TOAST_FMT',
  'SETTINGS_JS_DIAGNOSTICS_BUNDLE_COPIED',
  'SETTINGS_JS_DIAGNOSTICS_BUNDLE_FAILED',
  'SETTINGS_JS_PASSKEY_ONBOARDING_TOAST',
  'SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_DISABLED',
  'SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_ENABLED',
  'SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_NOT_SUPPORTED',
  'SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_TEMP_UNAVAILABLE',
  'SETTINGS_JS_SUPPORT_INFO_COPIED',
  'SETTINGS_JS_SUPPORT_INFO_COPY_FAILED',
  'SETTINGS_JS_SUPPORT_INFO_LABEL',
  'SETTINGS_VOICE_PREVIEW_SAMPLE',
  'SETTINGS_JS_THEME_UPDATED_FMT',
  'SETTINGS_JS_LANGUAGE_UPDATED',
  'SETTINGS_JS_TIMEZONE_UPDATED',
  'SETTINGS_JS_CURRENCY_UPDATED',
  'SETTINGS_JS_WORK_DEFAULTS_UPDATED',
  'SETTINGS_JS_TYPOGRAPHY_UPDATED',
  'SETTINGS_JS_HELP_POPUP_TIMEOUT_UPDATED',
  'SETTINGS_JS_TOAST_POSITION_UPDATED',
  'SETTINGS_JS_TOAST_SIZE_UPDATED',
  'SETTINGS_JS_NAV_DISTANCE_UPDATED',
  'SETTINGS_JS_NAV_TRIGGER_UPDATED',
  'SETTINGS_JS_TOGGLE_ON',
  'SETTINGS_JS_TOGGLE_OFF',
  'SETTINGS_JS_PROXIMITY_FMT',
  'SETTINGS_JS_OVERLAY_FMT',
  'SETTINGS_JS_OVERLAY_COLLAPSE_UPDATED',
  'SETTINGS_OVERLAY_COLLAPSE_LABEL',
  'SETTINGS_JS_DELETE_ACCOUNT_TYPE_PHRASE',
  'SETTINGS_JS_DELETE_DATA_TYPE_PHRASE',
  'SETTINGS_JS_DELETE_DATA_IN_PROGRESS',
  'SETTINGS_JS_DELETE_DATA_COMPLETE',
  'SETTINGS_JS_SECURITY_CUSTOM',
  'SETTINGS_JS_SECURITY_CUSTOM_HINT',
  'SETTINGS_JS_SECURITY_HINT_SUFFIX',
  'SETTINGS_JS_DATA_PORTABILITY_CONSUMED',
  'SETTINGS_JS_DATA_PORTABILITY_COPY_FAILED',
  'SETTINGS_JS_CODE_EXPIRES_FMT',
  'SETTINGS_JS_CODE_EXPIRED',
  'SETTINGS_JS_CODES_EXPIRE_MINUTES_FMT',
  'SETTINGS_JS_MODAL_CONFIRM_IMPORT',
  'SETTINGS_JS_SECURITY_REMAINING_FMT',
  'SETTINGS_JS_SECURITY_EXPIRED',
  'SETTINGS_DATA_CONSENT_RULE',
  'SETTINGS_DATA_CONSENT_ACTIVE',
  'SETTINGS_DATA_CONSENT_REVOKED',
  'SETTINGS_DATA_CONSENT_ACTIVE_DESC',
  'SETTINGS_DATA_CONSENT_REVOKED_DESC',
  'SETTINGS_DATA_CONSENT_SECURE_ACCESS_NOT_READY_DESC',
  'SETTINGS_DATA_CONSENT_REVOKE',
  'SETTINGS_DATA_CONSENT_REFRESH_ACCESS',
  'SETTINGS_DATA_CONSENT_GRANT_CONFIRM',
  'SETTINGS_DATA_CONSENT_REFRESH_CONFIRM',
  'SETTINGS_DATA_CONSENT_REVOKE_CONFIRM',
  'SETTINGS_DATA_CONSENT_GRANT_SUCCESS',
  'SETTINGS_DATA_CONSENT_REFRESH_SUCCESS',
  'SETTINGS_DATA_CONSENT_REVOKE_SUCCESS',
  'SETTINGS_DATA_CONSENT_GRANT_SETUP_NEEDED',
  'SETTINGS_DATA_CONSENT_ACTION_FAILED',
  'SETTINGS_DATA_CONSENT_REVOKE_MODAL_TITLE',
  'SETTINGS_RECOVERY_SEND_BUTTON',
  'SETTINGS_SECURITY_BALANCED',
  'SETTINGS_SECURITY_LOW',
  'SETTINGS_SECURITY_HIGH',
  'BILLING_JS_PREMIUM_ACTIVE',
  'BILLING_JS_PREMIUM_DISABLED',
  'BILLING_JS_CONFIRMING',
  'BILLING_JS_LOADING_STATUS',
  'BILLING_JS_DOWNGRADE_HELP_SCHEDULED',
  'BILLING_JS_DOWNGRADE_HELP_DEFAULT',
  'AUTH_JS_WEBAUTHN_UNSUPPORTED',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}

CORS::handleORIGIN();
CORS::renderContentType('application/javascript');

if (!headers_sent()) {
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: 0');
}

Javascript::renderDocBlock();

?>

/**
 * Settings Page Logic
 */

import PC from "<?php echo Environment::appURL('js/'); ?>";
import PW from "<?php echo Environment::appURL('js/phantomwing/'); ?>";
import { initializeBillingSection } from "../core/billing.js";
import { fromBase64Url as b64urlToBuffer, toBase64, toBase64Url as bufferToB64url } from "../core/binary-codec.js";
import { isWebAuthnCapableBrowser } from "../core/capabilities.js";
import { setActionBusy } from "../core/actions.js";
import {
  clearFieldErrorStates,
  clearFieldInvalidStates,
  setFieldErrorState,
} from "../core/forms.js";
import { setPrefixedStatusText } from "../core/status-text.js";
import {
  buildPayPeriodCurrentRange,
  buildPayPeriodDayNames,
  buildPayPeriodPreviewState,
  payPeriodFormatYmd,
} from "../core/pay-period-preview.js";
import { formatTemplate as formatSettingsMessage } from "../core/template.js";

const SETTINGS_T = <?php echo json_encode($i18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

const getSettingsCsrfToken = () => {
  const workspaceToken = document.querySelector('#settings_csrf_token');
  if (workspaceToken instanceof HTMLInputElement && workspaceToken.value !== '') {
    return workspaceToken.value;
  }

  const formToken = document.querySelector('#settings-workspace input[name="csrf_token"], form[id^="account_"] input[name="csrf_token"]');
  if (formToken instanceof HTMLInputElement) {
    return formToken.value;
  }

  return '';
};

const appendSettingsCsrfToken = (formData) => {
  const csrfValue = getSettingsCsrfToken();
  if (csrfValue !== '') {
    formData.append('csrf_token', csrfValue);
  }
};

const settingsJsonRequest = (payload = {}) => {
  const csrfValue = getSettingsCsrfToken();
  const body = { ...payload };
  const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
  if (csrfValue !== '') {
    body.csrf_token = csrfValue;
    headers['X-CSRF-Token'] = csrfValue;
  }

  return {
    headers,
    body: JSON.stringify(body),
  };
};

const isDebugEnabled = () => window.PAYCAL_DEBUG === true;
const debugLog = (...args) => {
  if (!isDebugEnabled()) {
    return;
  }
  PW.log('[Settings Debug]', ...args);
};

let lastSettingsAutosaveTarget = null;

const rememberSettingsAutosaveTarget = (target) => {
  if (!(target instanceof HTMLElement)) {
    return;
  }

  const field = target.closest('input, select, textarea');
  if (field instanceof HTMLElement) {
    lastSettingsAutosaveTarget = field;
  }
};

const markSettingsAutosaveTargetSaved = (fallbackTarget = null) => {
  const field = fallbackTarget instanceof HTMLElement
    ? fallbackTarget
    : (lastSettingsAutosaveTarget instanceof HTMLElement && document.contains(lastSettingsAutosaveTarget) ? lastSettingsAutosaveTarget : null);
  const target = field?.closest('.form-field, .item_pair, .settings_field, .details_inset_section') || field;
  if (!(target instanceof HTMLElement)) {
    return;
  }

  window.clearTimeout(Number(target.dataset.autosaveShimmerTimer || 0));
  target.classList.remove('is-autosaved');
  target.dataset.autosaveShimmer = 'saved';
  target.classList.add('is-autosaved');
  target.dataset.autosaveShimmerTimer = String(window.setTimeout(() => {
    target.classList.remove('is-autosaved');
    delete target.dataset.autosaveShimmer;
    delete target.dataset.autosaveShimmerTimer;
  }, 950));
};

document.addEventListener('input', (event) => rememberSettingsAutosaveTarget(event.target), true);
document.addEventListener('change', (event) => rememberSettingsAutosaveTarget(event.target), true);


/**
 * Generic handler for radio button groups in settings.
 * @param {string} name - Name attribute of the radio inputs.
 * @param {string} endpoint - API endpoint for the update.
 * @param {string} messageTemplate - Message template with {value} placeholder.
 */
const handleRadioGroup = (name, endpoint, messageTemplate, options = {}) => {
  const reloadOnSuccess = options.reloadOnSuccess === true;

  PC.queryAll(`input[name="${name}"]`).forEach(radioButton => {
    radioButton.addEventListener('change', () => {
      const checkedRadio = PC.query(`input[name="${name}"]:checked`);
      if (!(checkedRadio instanceof HTMLInputElement)) {
        return;
      }

      const value = checkedRadio.value;
      const label = document.querySelector(`label[for="${checkedRadio.id}"]`)?.textContent?.trim() || value;
      const formData = new FormData();
      formData.append(name, value);
      appendSettingsCsrfToken(formData);

      PC.updateResource(endpoint, formData).then(() => {
        markSettingsAutosaveTargetSaved(checkedRadio);
        PC.showToast(
          messageTemplate
            .replace('{value}', value)
            .replace('{label}', label),
          'save',
          3000,
          true
        );
        if (PC.state.audio_feedback === 'all') {
          PC.textToSpeech(label);
        }
        if (reloadOnSuccess) {
          window.location.reload();
        }
      }).catch(error => PW.error(error));
    });
  });
};

const readDebugSettingFromRadio = (name, fallback = false) => {
  const selected = PC.query(`input[name="${name}"]:checked`);
  if (!(selected instanceof HTMLInputElement)) {
    return fallback;
  }

  return selected.value === '1';
};

const broadcastDebugSettingsUpdate = () => {
  const detail = {
    consoleEnabled: readDebugSettingFromRadio('debug_console_enabled', false),
    fineGrainedEnabled: readDebugSettingFromRadio('debug_fine_grained_enabled', false),
    networkEnabled: readDebugSettingFromRadio('debug_network_enabled', false),
  };

  window.dispatchEvent(new CustomEvent('paycal:debug-settings-updated', { detail }));
};

const encryptExportPayloadWithPassphrase = async (payloadJson, passphrase) => {
  if (!window.crypto?.subtle) {
    throw new Error('Web Crypto is unavailable in this browser.');
  }

  const encoder = new TextEncoder();
  const salt = window.crypto.getRandomValues(new Uint8Array(16));
  const iv = window.crypto.getRandomValues(new Uint8Array(12));
  const baseKey = await window.crypto.subtle.importKey(
    'raw',
    encoder.encode(passphrase),
    'PBKDF2',
    false,
    ['deriveKey']
  );
  const aesKey = await window.crypto.subtle.deriveKey(
    {
      name: 'PBKDF2',
      salt,
      iterations: 210000,
      hash: 'SHA-256',
    },
    baseKey,
    { name: 'AES-GCM', length: 256 },
    false,
    ['encrypt']
  );
  const ciphertext = await window.crypto.subtle.encrypt(
    { name: 'AES-GCM', iv },
    aesKey,
    encoder.encode(payloadJson)
  );

  return JSON.stringify({
    format: 'paycal-export-encrypted-v1',
    kdf: 'PBKDF2-SHA256',
    iterations: 210000,
    cipher: 'AES-GCM',
    salt: toBase64(salt),
    iv: toBase64(iv),
    ciphertext: toBase64(new Uint8Array(ciphertext)),
  }, null, 2);
};

const promptForExportPassphrase = () => {
  const initial = window.prompt(SETTINGS_T.SETTINGS_JS_EXPORT_ENCRYPT_PROMPT, '');
  if (initial === null) {
    return null;
  }

  const trimmed = String(initial).trim();
  if (trimmed === '') {
    return null;
  }

  const confirm = window.prompt(SETTINGS_T.SETTINGS_JS_EXPORT_ENCRYPT_CONFIRM, '');
  if (confirm === null) {
    return null;
  }

  if (String(confirm).trim() !== trimmed) {
    PC.showToast(SETTINGS_T.SETTINGS_JS_EXPORT_ENCRYPT_MISMATCH, 'error', 4000, true);
    return null;
  }

  return trimmed;
};


document.addEventListener("DOMContentLoaded", async () => {

  const params = new URLSearchParams(window.location.search);

  if (params.get('passkey_onboarding') === '1') {
    PC.showToast(
      SETTINGS_T.SETTINGS_JS_PASSKEY_ONBOARDING_TOAST,
      'save',
      10000,
      true
    );
    const passkeysPanel = document.getElementById('panel-passkeys');
    if (passkeysPanel instanceof HTMLElement) {
      passkeysPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  const immediateUiSwitch = document.getElementById('early_access_immediate_ui_switch');
  const immediateUiStatus = document.getElementById('early_access_immediate_ui_status');
  const setImmediateUiStatus = (message, toastType = '') => {
    if (immediateUiStatus instanceof HTMLElement) {
      immediateUiStatus.textContent = message;
    }
    if (toastType !== '') {
      PC.showToast(message, toastType, 3500, true);
    }
  };
  const browserSupportsImmediateUi = async () => {
    if (!window.PublicKeyCredential || typeof PublicKeyCredential.getClientCapabilities !== 'function') {
      return false;
    }

    try {
      const capabilities = await PublicKeyCredential.getClientCapabilities();
      return capabilities?.immediateGet === true;
    } catch (error) {
      debugLog('[Early Access] immediateGet capability detection failed', error);
      return false;
    }
  };

  if (immediateUiSwitch instanceof HTMLInputElement) {
    const earlyAccessCard = immediateUiSwitch.closest('[data-early-access-feature="auth.immediate_ui"]');
    const runtimeEnabled = earlyAccessCard instanceof HTMLElement && earlyAccessCard.dataset.immediateUiRuntime === '1';
    const canEnable = earlyAccessCard instanceof HTMLElement && earlyAccessCard.dataset.immediateUiCanEnable === '1';
    const immediateUiSupported = await browserSupportsImmediateUi();
    const updateEndpoint = (enabled) => enabled
      ? '/api/v1/settings/early-access/immediate-ui/enable'
      : '/api/v1/settings/early-access/immediate-ui/disable';

    if (!runtimeEnabled) {
      immediateUiSwitch.disabled = true;
      setImmediateUiStatus(SETTINGS_T.SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_TEMP_UNAVAILABLE);
    } else if (!immediateUiSupported) {
      immediateUiSwitch.disabled = true;
      immediateUiSwitch.checked = false;
      setImmediateUiStatus(SETTINGS_T.SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_NOT_SUPPORTED);
    } else if (!canEnable && !immediateUiSwitch.checked) {
      immediateUiSwitch.disabled = true;
    }

    immediateUiSwitch.addEventListener('change', async () => {
      const desiredState = immediateUiSwitch.checked;
      const previousState = !desiredState;
      immediateUiSwitch.disabled = true;

      const formData = new FormData();
      appendSettingsCsrfToken(formData);

      try {
        const response = await fetch(updateEndpoint(desiredState), {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload?.success !== true) {
          throw new Error(String(payload?.message || 'Unable to update Early Access preference.'));
        }

        const message = String(
          payload.message
          || (desiredState
            ? SETTINGS_T.SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_ENABLED
            : SETTINGS_T.SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_DISABLED)
        );
        immediateUiSwitch.checked = desiredState;
        setImmediateUiStatus(message, 'save');
      } catch (error) {
        immediateUiSwitch.checked = previousState;
        const fallback = previousState
          ? SETTINGS_T.SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_ENABLED
          : SETTINGS_T.SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_DISABLED;
        setImmediateUiStatus(error instanceof Error ? error.message : fallback, 'error');
        PW.error(error);
      } finally {
        immediateUiSwitch.disabled = false;
      }
    });
  }

  (() => {
    const settingsWorkspace = document.getElementById('settings-workspace');
    const hintPref = PC.query('input[name="keyboard_shortcuts_hint"]:checked')?.value
      || settingsWorkspace?.dataset.keyboardShortcutsHint
      || 'first_visit';
    const configShown = PC.config && PC.config.settings_keyboard_hint_shown === true;
    const storageShown = localStorage.getItem('settings_keyboard_hint_shown') === '1';
    if (!configShown && !storageShown && hintPref === 'first_visit' && document.getElementById('modal_help')) {
      PC.openModal('modal_help', SETTINGS_T.KEYBOARD_SHORTCUTS || PC.config?.KEYBOARD_SHORTCUTS || 'Keyboard shortcuts');
      localStorage.setItem('settings_keyboard_hint_shown', '1');
    }
  })();

  const recoveryKeyBadgeEl = document.getElementById('settings_recovery_key_badge');
  const recoveryKeyStatusValueEl = document.getElementById('settings_recovery_key_status_value');
  const recoveryKeyUpdatedValueEl = document.getElementById('settings_recovery_key_updated_value');
  const recoveryCodeOnceEl = document.getElementById('settings_recovery_code_once');
  const recoveryCodeOnceValueEl = document.getElementById('settings_recovery_code_once_value');
  const recoveryCodeCopyBtn = document.getElementById('settings_recovery_code_copy_btn');
  const recoveryCodeDownloadBtn = document.getElementById('settings_recovery_code_download_btn');
  const recoveryCodeOnceStatusEl = document.getElementById('settings_recovery_code_once_status');
  if (recoveryKeyBadgeEl instanceof HTMLElement) {
    const hasRecoveryKey = recoveryKeyBadgeEl.dataset.hasRecoveryKey === '1'
      || recoveryKeyBadgeEl.getAttribute('data-has-recovery-key') === '1';
    recoveryKeyBadgeEl.hidden = !hasRecoveryKey;
    recoveryKeyBadgeEl.classList.toggle('is-visible', hasRecoveryKey);
    if (hasRecoveryKey) {
      recoveryKeyBadgeEl.setAttribute('aria-label', SETTINGS_T.SETTINGS_JS_RECOVERY_KEY_ACTIVE);
    }
  }

  const addPasskeyStatusEl = document.getElementById('add_passkey_status');
  const passkeyCredentialsListEl = document.getElementById('passkey_credentials_list');
  const passkeyEmptyStateEl = document.getElementById('passkey_empty_state');
  const passkeySecuritySummaryEl = document.getElementById('passkey_security_summary');
  const addPasskeyFirstButtonEl = document.getElementById('add_passkey_first_button');
  let passkeyCredentialsStatusEl = document.getElementById('passkey_credentials_sr_status');
  let addPasskeyButtonEl = null;
  let addPasskeyCardEl = null;
  let passkeyMenuDocumentListenerBound = false;
  const createRecoveryKeyButtonEl = document.getElementById('create_recovery_key_btn');
  const createRecoveryKeyStatusEl = document.getElementById('create_recovery_key_status');

  const WEB_AUTHN_UNSUPPORTED_MESSAGE = SETTINGS_T.AUTH_JS_WEBAUTHN_UNSUPPORTED;
  let passkeyActionHardDisabled = false;

  const normalizeBootstrapData = (payload) => {
    if (!payload || typeof payload !== 'object') {
      return {};
    }

    if (payload.data && typeof payload.data === 'object') {
      return payload.data;
    }

    return payload;
  };

  const fetchAccountBootstrap = async () => {
    const response = await fetch('/api/v1/user/account/bootstrap', {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' },
    });

    const payload = await response.json();
    if (!response.ok || payload.status !== 'success') {
      throw new Error(payload.message || 'Unable to load account bootstrap data.');
    }

    return normalizeBootstrapData(payload);
  };

  const recoveryCryptoBridge = (() => {
    let worker = null;
    let requestId = 1;
    let hasDek = false;
    let dekVersion = 1;
    let cryptoVersion = 1;
    let userId = '';
    let encryptionSalt = '';

    const getWorker = () => {
      if (worker) {
        return worker;
      }

      const base = new URL(import.meta.url);
      const workerUrl = new URL('../calendar/crypto-worker.js', base);
      const version = base.searchParams.get('v');
      if (version) {
        workerUrl.searchParams.set('v', version);
      }

      worker = new Worker(workerUrl.toString(), { type: 'module' });
      return worker;
    };

    const callWorker = (action, payload = {}) => {
      const activeWorker = getWorker();
      const id = requestId++;

      return new Promise((resolve, reject) => {
        const onMessage = (event) => {
          const data = event?.data || {};
          if (data.id !== id) {
            return;
          }

          activeWorker.removeEventListener('message', onMessage);

          if (data.ok) {
            resolve(data.result || {});
            return;
          }

          reject(new Error(data.error || 'Crypto worker request failed.'));
        };

        activeWorker.addEventListener('message', onMessage);
        activeWorker.postMessage({ id, action, payload });
      });
    };

    const ensureDEK = async () => {
      if (hasDek) {
        return true;
      }

      const bootstrap = await fetchAccountBootstrap();
      const wrappedDekPasskey = bootstrap.wrappedDekPasskeyForCredential || '';
      const credentialId = bootstrap.credentialId || '';
      encryptionSalt = bootstrap.encryptionSalt || '';
      userId = bootstrap.userId || '';
      const wrappedCredentialCount = Number(bootstrap.wrappedCredentialCount || 0);

      if (!wrappedDekPasskey || !credentialId || !encryptionSalt) {
        if (wrappedCredentialCount > 0) {
          throw new Error('Sign in with a passkey that can unlock your existing entries before adding another passkey.');
        }

        throw new Error('Encrypted entries are not available yet. Open your calendar once and try again.');
      }

      await callWorker('unwrapWithPasskeyCredential', {
        wrappedDekPasskey,
        credentialId,
        userId,
        saltBase64: encryptionSalt,
        dekVersion: Number(bootstrap.dekVersion || 1),
        cryptoVersion: Number(bootstrap.cryptoVersion || 1),
        derivationMode: 'credential-only',
      });

      hasDek = true;
      dekVersion = Number(bootstrap.dekVersion || 1);
      cryptoVersion = Number(bootstrap.cryptoVersion || 1);

      return true;
    };

    const createRecoveryMaterial = async () => {
      await ensureDEK();
      return callWorker('generateRecoveryMaterial', {
        dekVersion,
        cryptoVersion,
      });
    };

    const wrapDEKWithPasskeyCredential = async (credentialId, saltBase64 = '') => {
      await ensureDEK();

      const saltForWrap = saltBase64 || encryptionSalt;
      if (!credentialId || !saltForWrap || !userId) {
        throw new Error('Missing passkey wrap inputs.');
      }

      const wrapped = await callWorker('wrapCurrentDekWithPasskeyCredential', {
        credentialId,
        userId,
        saltBase64: saltForWrap,
        derivationMode: 'credential-only',
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
        let responseBody = '';
        try {
          responseBody = await uploadResponse.text();
        } catch {
          responseBody = '';
        }

        throw new Error(`Failed to persist wrapped DEK passkey (${uploadResponse.status}): ${responseBody || 'no response body'}`);
      }

      return true;
    };

    return {
      ensureDEK,
      createRecoveryMaterial,
      wrapDEKWithPasskeyCredential,
      get hasDek() {
        return hasDek;
      },
    };
  })();

  const getRecoveryCryptoApi = () => {
    if (window.PayCalCrypto?.ensureDEK && window.PayCalCrypto?.createRecoveryMaterial) {
      return window.PayCalCrypto;
    }

    return recoveryCryptoBridge;
  };

  const getPasskeyWrapCryptoApi = () => {
    if (window.PayCalCrypto?.ensureDEK && window.PayCalCrypto?.wrapDEKWithPasskeyCredential) {
      return window.PayCalCrypto;
    }

    return recoveryCryptoBridge;
  };


  const simplifyPasskeyStatusMessage = (message, fallbackMessage = 'Something went wrong. Try again.') => {
    const raw = normalizeErrorMessage(message, fallbackMessage);
    if (!raw) {
      return fallbackMessage;
    }

    const compact = raw
      .replace(/^\[[^\]]+\]\s*/u, '')
      .replace(/\s*\(HTTP\s+\d+\)\s*/iu, ' ')
      .replace(/\s*\[[A-Z0-9_:-]+\]\s*$/u, '')
      .trim();

    if (/already has a passkey/i.test(compact)) {
      return 'This device already has a passkey for this account.';
    }
    if (/cancelled|canceled|timed out|registration cancelled/i.test(compact)) {
      return 'Passkey setup was cancelled. Try again when you are ready.';
    }
    if (/network error/i.test(compact)) {
      return 'Network issue while adding passkey. Check your connection and try again.';
    }
    if (/unable to start passkey registration|invalid challenge payload/i.test(compact)) {
      return 'Could not start passkey setup. Try again.';
    }
    if (/unable to finish passkey registration/i.test(compact)) {
      return 'Could not complete passkey setup. Try again.';
    }

    return compact || fallbackMessage;
  };

  const setPasskeyStatus = (message, tone = 'info') => {
    if (!addPasskeyStatusEl) {
      return;
    }

    const text = typeof message === 'string' ? message.trim() : '';
    if (text === '') {
      addPasskeyStatusEl.textContent = '';
      addPasskeyStatusEl.classList.remove('is-visible', 'is-info', 'is-success', 'is-warning', 'is-error');
      return;
    }

    addPasskeyStatusEl.textContent = text;
    addPasskeyStatusEl.classList.add('is-visible');
    addPasskeyStatusEl.classList.remove('is-info', 'is-success', 'is-warning', 'is-error');
    if (tone === 'success') {
      addPasskeyStatusEl.classList.add('is-success');
    } else if (tone === 'warning') {
      addPasskeyStatusEl.classList.add('is-warning');
    } else if (tone === 'error') {
      addPasskeyStatusEl.classList.add('is-error');
    } else {
      addPasskeyStatusEl.classList.add('is-info');
    }
  };

  const normalizeErrorMessage = (error, fallbackMessage = 'Something went wrong. Try again.') => {
    if (error instanceof Error && typeof error.message === 'string' && error.message.trim() !== '') {
      return error.message.trim();
    }

    if (typeof error === 'string' && error.trim() !== '') {
      return error.trim();
    }

    try {
      const serialized = JSON.stringify(error);
      if (typeof serialized === 'string' && serialized !== '{}' && serialized !== 'null' && serialized.trim() !== '') {
        return serialized;
      }
    } catch (_) {
      // Ignore stringify failures and use fallback message.
    }

    return fallbackMessage;
  };

  const parseJsonSafely = async (response) => {
    try {
      return await response.json();
    } catch (_) {
      return {};
    }
  };

  const buildPasskeyStepError = (step, error, fallbackMessage) => {
    const stepLabel = String(step || 'unknown-step');
    const message = normalizeErrorMessage(error, fallbackMessage);
    return new Error(`[${stepLabel}] ${message}`);
  };

  const buildPasskeyApiError = (step, response, payload, fallbackMessage) => {
    const stepLabel = String(step || 'unknown-step');
    const statusCode = Number(response?.status || 0);
    const apiMessage = typeof payload?.message === 'string' && payload.message.trim() !== ''
      ? payload.message.trim()
      : (typeof payload?.error === 'string' && payload.error.trim() !== '' ? payload.error.trim() : '');
    const apiCode = typeof payload?.code === 'string' && payload.code.trim() !== ''
      ? payload.code.trim()
      : (typeof payload?.error_code === 'string' && payload.error_code.trim() !== '' ? payload.error_code.trim() : '');

    let message = apiMessage || fallbackMessage;
    if (statusCode > 0) {
      message = `${message} (HTTP ${statusCode})`;
    }
    if (apiCode !== '') {
      message = `${message} [${apiCode}]`;
    }

    return new Error(`[${stepLabel}] ${message}`);
  };

  const setAddPasskeyBusyState = (busy) => {
    const buttons = [addPasskeyButtonEl, addPasskeyFirstButtonEl].filter((button) => button instanceof HTMLElement);
    if ((buttons.length === 0 && !(addPasskeyCardEl instanceof HTMLElement)) || passkeyActionHardDisabled) {
      return;
    }

    buttons.forEach((button) => {
      const label = button.querySelector('.passkey_card_add_btn_label');
      if (label instanceof HTMLElement) {
        label.textContent = busy
          ? SETTINGS_T.SETTINGS_PASSKEYS_SETTING_UP
          : SETTINGS_T.SETTINGS_PASSKEYS_ADD_BUTTON;
      } else if (button.id === 'add_passkey_button') {
        button.textContent = busy
          ? SETTINGS_T.SETTINGS_PASSKEYS_SETTING_UP
          : SETTINGS_T.SETTINGS_PASSKEYS_ADD_BUTTON;
      }

      if (button !== addPasskeyButtonEl) {
        setActionBusy(button, busy, {
          ariaDisabled: true,
          busyClass: 'is-working',
        });
        if (busy) {
          button.classList.remove('is-success');
        }
      }
    });

    if (addPasskeyCardEl instanceof HTMLElement) {
      addPasskeyCardEl.classList.toggle('is-working', busy);
      if (busy) {
        addPasskeyCardEl.classList.remove('is-success');
        addPasskeyCardEl.setAttribute('aria-disabled', 'true');
        addPasskeyCardEl.setAttribute('aria-busy', 'true');
      } else if (!passkeyActionHardDisabled) {
        addPasskeyCardEl.removeAttribute('aria-disabled');
        addPasskeyCardEl.setAttribute('aria-busy', 'false');
      }
    }
  };

  const markAddPasskeySuccess = () => {
    const buttons = [addPasskeyButtonEl, addPasskeyFirstButtonEl].filter((button) => button instanceof HTMLElement && button !== addPasskeyButtonEl);
    if (buttons.length === 0 && !(addPasskeyCardEl instanceof HTMLElement)) {
      return;
    }

    buttons.forEach((button) => {
      button.classList.add('is-success');
      window.setTimeout(() => {
        button.classList.remove('is-success');
      }, 1300);
    });

    if (addPasskeyCardEl instanceof HTMLElement) {
      addPasskeyCardEl.classList.add('is-success');
      window.setTimeout(() => {
        addPasskeyCardEl.classList.remove('is-success');
      }, 1300);
    }
  };

  const setPasskeyGridStatus = (message) => {
    if (!(passkeyCredentialsStatusEl instanceof HTMLElement)) {
      passkeyCredentialsStatusEl = document.getElementById('passkey_credentials_sr_status');
    }

    if (passkeyCredentialsStatusEl instanceof HTMLElement) {
      passkeyCredentialsStatusEl.textContent = message;
    }
  };

  const setRecoveryKeyStatus = (message, tone = 'info') => {
    if (createRecoveryKeyStatusEl) {
      createRecoveryKeyStatusEl.textContent = message;
      createRecoveryKeyStatusEl.classList.add('is-visible');
      createRecoveryKeyStatusEl.classList.remove('is-info', 'is-success', 'is-error');
      if (tone === 'success') {
        createRecoveryKeyStatusEl.classList.add('is-success');
      } else if (tone === 'error') {
        createRecoveryKeyStatusEl.classList.add('is-error');
      } else {
        createRecoveryKeyStatusEl.classList.add('is-info');
      }
    }
  };

  const setRecoveryCodeOnceStatus = (message) => {
    if (recoveryCodeOnceStatusEl instanceof HTMLElement) {
      recoveryCodeOnceStatusEl.textContent = String(message || '');
    }
  };

  const currentDisplayedRecoveryCode = () => {
    if (!(recoveryCodeOnceValueEl instanceof HTMLElement)) {
      return '';
    }
    return String(recoveryCodeOnceValueEl.textContent || '').trim();
  };

  const showRecoveryCodeOnce = (recoveryCode) => {
    if (!(recoveryCodeOnceEl instanceof HTMLElement) || !(recoveryCodeOnceValueEl instanceof HTMLElement)) {
      return;
    }
    recoveryCodeOnceValueEl.textContent = String(recoveryCode || '').trim();
    recoveryCodeOnceEl.hidden = false;
    setRecoveryCodeOnceStatus('');
  };

  const copyDisplayedRecoveryCode = async () => {
    const recoveryCode = currentDisplayedRecoveryCode();
    if (recoveryCode === '') {
      return;
    }
    try {
      await navigator.clipboard.writeText(recoveryCode);
      setRecoveryCodeOnceStatus(SETTINGS_T.SETTINGS_RECOVERY_KEY_COPIED);
    } catch (_error) {
      setRecoveryCodeOnceStatus(SETTINGS_T.SETTINGS_RECOVERY_KEY_COPY_FAILED);
    }
  };

  const downloadDisplayedRecoveryCode = () => {
    const recoveryCode = currentDisplayedRecoveryCode();
    if (recoveryCode === '') {
      return;
    }
    const body = [
      'PayCal Recovery Code',
      '',
      recoveryCode,
      '',
      SETTINGS_T.SETTINGS_RECOVERY_KEY_DISPLAY_INSTRUCTION,
      '',
    ].join('\n');
    const url = URL.createObjectURL(new Blob([body], { type: 'text/plain' }));
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = `paycal-recovery-code-${new Date().toISOString().slice(0, 10)}.txt`;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 0);
  };

  recoveryCodeCopyBtn?.addEventListener('click', () => {
    copyDisplayedRecoveryCode();
  });
  recoveryCodeDownloadBtn?.addEventListener('click', () => {
    downloadDisplayedRecoveryCode();
  });

  const formatPasskeyDate = (ts) => {
    const value = Number(ts || 0);
    if (!value || Number.isNaN(value)) {
      return '';
    }

    return new Date(value * 1000).toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  };

  const isRecoveredPasskeyName = (deviceName) => /^recovered passkey$/iu.test(String(deviceName || '').trim());

  const isRecoveryPasskey = (credential) => credential?.isRecoveryPasskey === true || isRecoveredPasskeyName(credential?.deviceName);

  const getPasskeyDisplayTitle = (credential) => {
    const rawName = String(credential?.deviceName || '').trim();
    if (isRecoveredPasskeyName(rawName)) {
      return SETTINGS_T.SETTINGS_PASSKEYS_UNNAMED;
    }

    if (rawName === '' || /^passkey$/iu.test(rawName)) {
      return SETTINGS_T.SETTINGS_PASSKEYS_UNNAMED;
    }

    return rawName;
  };

  const getPasskeyBadges = (credential) => {
    const badges = [];
    if (credential?.isCurrentDevice === true) {
      badges.push({ className: 'is-accent', label: SETTINGS_T.SETTINGS_PASSKEY_BADGE_THIS_DEVICE });
    }
    if (isRecoveryPasskey(credential)) {
      badges.push({ className: 'is-recovery', label: SETTINGS_T.SETTINGS_PASSKEY_BADGE_RECOVERED });
    }

    const lastUsedAt = Number(credential?.lastUsedAt || 0);
    if (lastUsedAt <= 0) {
      badges.push({ className: 'is-warn', label: SETTINGS_T.SETTINGS_PASSKEY_BADGE_NEVER_USED });
    } else if ((Date.now() / 1000) - lastUsedAt < (7 * 86400)) {
      badges.push({ className: 'is-recent', label: SETTINGS_T.SETTINGS_PASSKEY_BADGE_RECENTLY_USED });
    }

    const transports = Array.isArray(credential?.transports) ? credential.transports : [];
    if (transports.some((transport) => ['usb', 'nfc', 'ble'].includes(transport)) && !transports.includes('internal')) {
      badges.push({ className: 'is-security', label: SETTINGS_T.SETTINGS_PASSKEY_BADGE_SECURITY_KEY });
    }

    return badges;
  };

  const getPasskeyCardClassName = (credential) => {
    const classes = ['passkey_card'];
    if (credential?.isCurrentDevice === true) {
      classes.push('passkey_card--current');
    }
    if (isRecoveryPasskey(credential)) {
      classes.push('passkey_card--recovery');
    }

    const transports = Array.isArray(credential?.transports) ? credential.transports : [];
    if (transports.some((transport) => ['usb', 'nfc', 'ble'].includes(transport)) && !transports.includes('internal')) {
      classes.push('passkey_card--security-key');
    }

    return classes.join(' ');
  };

  const getPasskeyStatusLine = (credential) => {
    if (credential?.isCurrentDevice === true) {
      return SETTINGS_T.SETTINGS_PASSKEY_STATUS_THIS_DEVICE;
    }
    if (isRecoveryPasskey(credential)) {
      return SETTINGS_T.SETTINGS_PASSKEY_STATUS_RECOVERY;
    }

    const transports = Array.isArray(credential?.transports) ? credential.transports : [];
    if (transports.some((transport) => ['usb', 'nfc', 'ble'].includes(transport)) && !transports.includes('internal')) {
      return SETTINGS_T.SETTINGS_PASSKEY_STATUS_SECURITY_KEY;
    }

    const lastUsedAt = Number(credential?.lastUsedAt || 0);
    if (lastUsedAt <= 0) {
      return SETTINGS_T.SETTINGS_PASSKEY_STATUS_NEVER_USED;
    }
    if ((Date.now() / 1000) - lastUsedAt < (7 * 86400)) {
      return SETTINGS_T.SETTINGS_PASSKEY_STATUS_RECENTLY_USED;
    }

    return SETTINGS_T.SETTINGS_PASSKEY_STATUS_DEFAULT;
  };

  const createPasskeyKeyEmblem = () => {
    const emblem = document.createElement('div');
    emblem.className = 'passkey_card_emblem';
    emblem.setAttribute('aria-hidden', 'true');

    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('width', '18');
    svg.setAttribute('height', '18');
    svg.setAttribute('fill', 'currentColor');

    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', 'M7 14a5 5 0 1 1 8.7-3.5L21 15.8V21h-2v-2h-2v-2h-1.6l-1.1-1.1A4.98 4.98 0 0 1 7 14zm2 0a3 3 0 1 0 6 0 3 3 0 0 0-6 0z');
    svg.appendChild(path);
    emblem.appendChild(svg);
    return emblem;
  };

  const closePasskeyCardMenus = (exceptMenu = null) => {
    if (!(passkeyCredentialsListEl instanceof HTMLElement)) {
      return;
    }

    passkeyCredentialsListEl.querySelectorAll('.passkey_card_menu').forEach((menu) => {
      if (!(menu instanceof HTMLElement) || menu === exceptMenu) {
        return;
      }

      menu.hidden = true;
      const trigger = menu.parentElement?.querySelector('.passkey_card_menu_trigger');
      if (trigger instanceof HTMLElement) {
        trigger.setAttribute('aria-expanded', 'false');
      }
    });
  };

  const bindPasskeyMenuDocumentListener = () => {
    if (passkeyMenuDocumentListenerBound) {
      return;
    }

    document.addEventListener('click', (event) => {
      const target = event.target;
      if (!(target instanceof Element)) {
        closePasskeyCardMenus();
        return;
      }

      if (target.closest('.passkey_card_menu_wrap')) {
        return;
      }

      closePasskeyCardMenus();
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closePasskeyCardMenus();
      }
    });

    passkeyMenuDocumentListenerBound = true;
  };

  const createPasskeyCardMenu = ({ credentialId, canRemove, onRename }) => {
    const menuWrap = document.createElement('div');
    menuWrap.className = 'passkey_card_menu_wrap';

    const menuId = `passkey_menu_${String(credentialId || '').replace(/[^a-zA-Z0-9_-]/g, '_')}`;
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'passkey_card_menu_trigger';
    trigger.setAttribute('aria-label', SETTINGS_T.SETTINGS_PASSKEY_MENU_ARIA);
    trigger.setAttribute('aria-haspopup', 'menu');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-controls', menuId);
    trigger.textContent = '•••';

    const menu = document.createElement('div');
    menu.id = menuId;
    menu.className = 'passkey_card_menu';
    menu.setAttribute('role', 'menu');
    menu.hidden = true;

    const renameButton = document.createElement('button');
    renameButton.type = 'button';
    renameButton.className = 'passkey_card_menu_item';
    renameButton.setAttribute('role', 'menuitem');
    renameButton.textContent = SETTINGS_T.SETTINGS_PASSKEY_RENAME;
    renameButton.addEventListener('click', () => {
      closePasskeyCardMenus();
      onRename();
    });
    menu.appendChild(renameButton);

    if (canRemove) {
      const removeButton = document.createElement('button');
      removeButton.type = 'button';
      removeButton.className = 'passkey_card_menu_item is-destructive';
      removeButton.setAttribute('role', 'menuitem');
      removeButton.textContent = SETTINGS_T.SETTINGS_PASSKEY_REMOVE;
      removeButton.addEventListener('click', () => {
        closePasskeyCardMenus();
        removePasskeyCredential(String(credentialId || ''));
      });
      menu.appendChild(removeButton);
    }

    trigger.addEventListener('click', (event) => {
      event.stopPropagation();
      const willOpen = menu.hidden;
      closePasskeyCardMenus(willOpen ? menu : null);
      menu.hidden = !willOpen;
      trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });

    menuWrap.appendChild(trigger);
    menuWrap.appendChild(menu);
    return menuWrap;
  };

  const createPasskeyCredentialCard = (credential, canRemove) => {
    const card = document.createElement('article');
    card.className = getPasskeyCardClassName(credential);
    card.setAttribute('data-credential-id', String(credential.credentialId || ''));

    card.appendChild(createPasskeyKeyEmblem());

    const header = document.createElement('div');
    header.className = 'passkey_card_header';

    const title = document.createElement('h3');
    title.className = 'passkey_card_title is-editable';
    title.contentEditable = 'true';
    title.setAttribute('spellcheck', 'false');
    title.textContent = getPasskeyDisplayTitle(credential);
    title.setAttribute('data-credential-id', String(credential.credentialId || ''));
    title.addEventListener('blur', async (event) => {
      const target = event.target;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      const newName = target.textContent?.trim() || SETTINGS_T.SETTINGS_PASSKEYS_UNNAMED;
      const credId = target.getAttribute('data-credential-id') || '';
      if (credId) {
        await updatePasskeyName(credId, newName);
      }
    });
    title.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        const target = event.target;
        if (target instanceof HTMLElement) {
          target.blur();
        }
      }
    });

    header.appendChild(title);
    header.appendChild(createPasskeyCardMenu({
      credentialId: credential.credentialId,
      canRemove,
      onRename: () => {
        title.focus();
        const selection = window.getSelection();
        const range = document.createRange();
        range.selectNodeContents(title);
        selection?.removeAllRanges();
        selection?.addRange(range);
      },
    }));
    card.appendChild(header);

    const badges = getPasskeyBadges(credential);
    if (badges.length > 0) {
      const badgeRow = document.createElement('div');
      badgeRow.className = 'passkey_card_badges';
      badges.forEach((badge) => {
        const badgeEl = document.createElement('span');
        badgeEl.className = `passkey_card_badge${badge.className ? ` ${badge.className}` : ''}`;
        badgeEl.textContent = badge.label;
        badgeRow.appendChild(badgeEl);
      });
      card.appendChild(badgeRow);
    }

    const statusLine = getPasskeyStatusLine(credential);
    if (statusLine !== '') {
      const status = document.createElement('p');
      status.className = 'passkey_card_status';
      status.textContent = statusLine;
      card.appendChild(status);
    }

    const meta = document.createElement('dl');
    meta.className = 'passkey_card_meta';

    const addedRow = document.createElement('div');
    addedRow.className = 'passkey_card_meta_row passkey_card_meta_row--added';
    const addedLabel = document.createElement('dt');
    addedLabel.textContent = SETTINGS_T.SETTINGS_PASSKEYS_ADDED_LABEL;
    const addedValue = document.createElement('dd');
    const addedDate = formatPasskeyDate(credential.createdAt);
    addedValue.textContent = addedDate !== '' ? addedDate : '—';
    addedRow.appendChild(addedLabel);
    addedRow.appendChild(addedValue);
    meta.appendChild(addedRow);

    const lastUsedRow = document.createElement('div');
    lastUsedRow.className = 'passkey_card_meta_row passkey_card_meta_row--last-used';
    const lastUsedLabel = document.createElement('dt');
    lastUsedLabel.textContent = SETTINGS_T.SETTINGS_PASSKEYS_LAST_USED_LABEL;
    const lastUsedValue = document.createElement('dd');
    const lastUsedDate = formatPasskeyDate(credential.lastUsedAt);
    lastUsedValue.textContent = lastUsedDate !== ''
      ? lastUsedDate
      : SETTINGS_T.SETTINGS_PASSKEYS_LAST_USED_NEVER;
    lastUsedRow.appendChild(lastUsedLabel);
    lastUsedRow.appendChild(lastUsedValue);
    meta.appendChild(lastUsedRow);
    card.appendChild(meta);

    return card;
  };

  const createAddPasskeyCard = () => {
    const card = document.createElement('article');
    card.className = 'passkey_card passkey_card_add';
    card.setAttribute('tabindex', '0');
    card.setAttribute('role', 'button');
    card.setAttribute(
      'aria-label',
      `${SETTINGS_T.SETTINGS_PASSKEYS_ADD_TITLE}. ${SETTINGS_T.SETTINGS_PASSKEYS_ADD_DESC}`
    );

    const icon = document.createElement('div');
    icon.className = 'passkey_card_add_icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = '+';
    card.appendChild(icon);

    const title = document.createElement('h3');
    title.className = 'passkey_card_add_title';
    title.textContent = SETTINGS_T.SETTINGS_PASSKEYS_ADD_TITLE;
    title.setAttribute('aria-hidden', 'true');
    card.appendChild(title);

    const text = document.createElement('p');
    text.className = 'passkey_card_add_text';
    text.textContent = SETTINGS_T.SETTINGS_PASSKEYS_ADD_DESC;
    text.setAttribute('aria-hidden', 'true');
    card.appendChild(text);

    const button = document.createElement('span');
    button.id = 'add_passkey_button';
    button.className = 'btn btn_primary';
    button.setAttribute('aria-hidden', 'true');
    const buttonIcon = document.createElement('span');
    buttonIcon.className = 'passkey_card_add_btn_icon';
    buttonIcon.setAttribute('aria-hidden', 'true');
    buttonIcon.textContent = '+';
    const buttonLabel = document.createElement('span');
    buttonLabel.className = 'passkey_card_add_btn_label';
    buttonLabel.textContent = SETTINGS_T.SETTINGS_PASSKEYS_ADD_BUTTON;
    button.appendChild(buttonIcon);
    button.appendChild(buttonLabel);
    if (passkeyActionHardDisabled) {
      card.setAttribute('aria-disabled', 'true');
    }
    card.appendChild(button);

    addPasskeyButtonEl = button;
    addPasskeyCardEl = card;
    return card;
  };

  const updatePasskeySecuritySummary = (count) => {
    if (!(passkeySecuritySummaryEl instanceof HTMLElement)) {
      return;
    }

    if (count <= 0) {
      passkeySecuritySummaryEl.hidden = true;
      passkeySecuritySummaryEl.textContent = '';
      return;
    }

    passkeySecuritySummaryEl.hidden = false;
    passkeySecuritySummaryEl.textContent = formatSettingsMessage(
      SETTINGS_T.SETTINGS_PASSKEYS_COUNT_RECOMMENDATION,
      { count }
    );
  };

  const renderPasskeyCredentials = (credentials = []) => {
    if (!passkeyCredentialsListEl) {
      return;
    }

    bindPasskeyMenuDocumentListener();
    passkeyCredentialsListEl.textContent = '';
    addPasskeyButtonEl = null;
    addPasskeyCardEl = null;

    const hasCredentials = Array.isArray(credentials) && credentials.length > 0;
    if (passkeyEmptyStateEl instanceof HTMLElement) {
      passkeyEmptyStateEl.hidden = hasCredentials;
    }
    passkeyCredentialsListEl.hidden = !hasCredentials;
    updatePasskeySecuritySummary(hasCredentials ? credentials.length : 0);

    if (!hasCredentials) {
      setPasskeyGridStatus(SETTINGS_T.SETTINGS_JS_PASSKEYS_NONE);
      return;
    }

    passkeyCredentialsListEl.setAttribute(
      'aria-describedby',
      'passkey_credentials_sr_instructions passkey_credentials_sr_status'
    );

    const canRemove = credentials.length > 1;
    credentials.forEach((credential) => {
      passkeyCredentialsListEl.appendChild(createPasskeyCredentialCard(credential, canRemove));
    });
    passkeyCredentialsListEl.appendChild(createAddPasskeyCard());
    wireAddPasskeyButtons();

    setPasskeyGridStatus(formatSettingsMessage(SETTINGS_T.SETTINGS_JS_PASSKEYS_LOADED_COUNT, { count: credentials.length }));
  };

  const refreshPasskeyCredentials = async () => {
    setPasskeyGridStatus(SETTINGS_T.SETTINGS_JS_PASSKEYS_CHECKING);

    try {
      const listResponse = await fetch('/api/v1/auth/passkey/list', {
        method: 'GET',
        credentials: 'include',
        headers: { 'Accept': 'application/json' },
      });

      const listPayload = await listResponse.json();

      if (!listResponse.ok || listPayload.status !== 'success') {
        throw new Error(listPayload.message || 'Unable to load passkeys.');
      }

      renderPasskeyCredentials(listPayload.credentials || []);
    } catch (error) {
      setPasskeyStatus(SETTINGS_T.SETTINGS_JS_PASSKEYS_LOAD_FAILED, 'error');
      setPasskeyGridStatus(SETTINGS_T.SETTINGS_JS_PASSKEYS_LOAD_FAILED);
      PW.error(error);
    }
  };

  /**
   * Rename a passkey label shown to the user.
   * This only changes display metadata, not cryptographic keys.
   */
  const updatePasskeyName = async (credentialId, newName) => {
    try {
      setPasskeyGridStatus(SETTINGS_T.SETTINGS_JS_PASSKEYS_UPDATE_NAME_PROGRESS);
      
      const response = await fetch('/api/v1/auth/passkey/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ credentialId, newName }),
      });

      if (!response.ok) {
        setPasskeyGridStatus(SETTINGS_T.SETTINGS_JS_PASSKEYS_UPDATE_NAME_FAILED);
        PW.error(`[PASSKEY] Update failed: ${response.status}`);
        return;
      }

      const result = await response.json();
      if (result.status !== 'success') {
        setPasskeyGridStatus(SETTINGS_T.SETTINGS_JS_PASSKEYS_UPDATE_NAME_FAILED);
        PW.error(`[PASSKEY] Update failed: ${result.message || 'unknown error'}`);
      } else {
        setPasskeyGridStatus(SETTINGS_T.SETTINGS_JS_PASSKEYS_UPDATE_NAME_SUCCESS);
      }
    } catch (err) {
      setPasskeyGridStatus(SETTINGS_T.SETTINGS_JS_PASSKEYS_UPDATE_NAME_FAILED);
      PW.error(err);
    }
  };

  const removePasskeyCredential = async (credentialId) => {
    if (!credentialId) {
      return;
    }

    try {
      setPasskeyStatus(SETTINGS_T.SETTINGS_JS_PASSKEYS_REMOVING, 'info');
      setPasskeyGridStatus(SETTINGS_T.SETTINGS_JS_PASSKEYS_REMOVING);
      const response = await fetch('/api/v1/auth/passkey/delete', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ credentialId }),
      });

      const payload = await response.json();
      if (!response.ok || payload.status !== 'success') {
        throw new Error(payload.message || 'Unable to remove passkey.');
      }

      setPasskeyStatus(SETTINGS_T.SETTINGS_JS_PASSKEYS_REMOVED, 'success');
      await refreshPasskeyCredentials();
    } catch (error) {
      setPasskeyStatus(simplifyPasskeyStatusMessage(error, SETTINGS_T.SETTINGS_JS_PASSKEYS_UPDATE_FAILED), 'error');
      setPasskeyGridStatus(SETTINGS_T.SETTINGS_JS_PASSKEYS_UPDATE_FAILED);
      PW.error(error);
    }
  };

  const addPasskeyAction = async () => {
    if (!isWebAuthnCapableBrowser()) {
      setPasskeyStatus(WEB_AUTHN_UNSUPPORTED_MESSAGE, 'warning');
      setPasskeyGridStatus(WEB_AUTHN_UNSUPPORTED_MESSAGE);
      return;
    }

    // Avoid blocking click handler timing with a synchronous prompt.
    const deviceName = 'Passkey';

    const passkeyWrapCrypto = getPasskeyWrapCryptoApi();
    let bootstrapBeforeRegistration = {};
    try {
      bootstrapBeforeRegistration = await fetchAccountBootstrap();
    } catch (bootstrapError) {
      debugLog('[PASSKEY] Unable to inspect bootstrap before passkey registration:', bootstrapError);
    }

    const accountHasExistingDek = Number(bootstrapBeforeRegistration.wrappedCredentialCount || 0) > 0
      || !!bootstrapBeforeRegistration.wrappedDekPasskey;

    if (!passkeyWrapCrypto.hasDek) {
      setPasskeyStatus('Preparing encrypted entries...', 'info');
      setPasskeyGridStatus('Preparing encrypted entries for the new passkey...');
      try {
        await passkeyWrapCrypto.ensureDEK();
      } catch (unlockError) {
        if (accountHasExistingDek) {
          const message = unlockError?.message || 'Sign in with a passkey that can unlock your existing entries before adding another passkey.';
          setPasskeyStatus(message, 'error');
          setPasskeyGridStatus(message);
          throw unlockError;
        }

        debugLog('[PASSKEY] Existing DEK was not available before passkey registration:', unlockError);
      }
    }

    setPasskeyStatus('Starting passkey setup...', 'info');
    setPasskeyGridStatus('Starting passkey registration...');

    let startResponse;
    let startPayload;
    try {
      startResponse = await fetch('/api/v1/auth/passkey/register/start', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ deviceName }),
      });
    } catch (error) {
      throw buildPasskeyStepError('register/start', error, 'Network error while starting passkey registration.');
    }

    startPayload = await parseJsonSafely(startResponse);
    if (!startResponse.ok || startPayload.status !== 'success') {
      throw buildPasskeyApiError('register/start', startResponse, startPayload, 'Unable to start passkey registration.');
    }

    const challengeId = startPayload.challengeId;
    const options = startPayload.publicKey || {};
    if (!challengeId || !options.challenge || !options.user?.id) {
      throw new Error('[register/start] Invalid challenge payload from server.');
    }
    options.challenge = b64urlToBuffer(options.challenge || '');
    options.user = options.user || {};
    options.user.id = b64urlToBuffer(options.user.id || '');
    options.excludeCredentials = Array.isArray(options.excludeCredentials)
      ? options.excludeCredentials.map((c) => ({
        ...c,
        id: b64urlToBuffer(c.id),
      }))
      : [];

    // Prefer hybrid discoverability so browsers can offer "use another device" (QR/Bluetooth).
    if (!Array.isArray(options.hints)) {
      options.hints = ['client-device', 'hybrid', 'security-key'];
    }
    if (options.authenticatorSelection && options.authenticatorSelection.authenticatorAttachment === 'platform') {
      delete options.authenticatorSelection.authenticatorAttachment;
    }

    setPasskeyStatus('Confirm the passkey prompt on your device.', 'info');
    setPasskeyGridStatus('Confirm passkey registration on your device.');
    let credential;
    try {
      credential = await navigator.credentials.create({ publicKey: options });
    } catch (error) {
      if (error instanceof DOMException && error.name === 'NotAllowedError') {
        throw new Error('[webauthn/create] Passkey prompt was cancelled or timed out.');
      }
      if (error instanceof DOMException && error.name === 'InvalidStateError') {
        throw new Error('[webauthn/create] This device already has a passkey for this account.');
      }
      throw buildPasskeyStepError('webauthn/create', error, 'Unable to create passkey on this device.');
    }

    if (!credential) {
      throw new Error('[webauthn/create] Registration cancelled.');
    }

    const credentialPayload = {
      id: credential.id,
      type: credential.type,
      rawId: bufferToB64url(credential.rawId),
      response: {
        clientDataJSON: bufferToB64url(credential.response.clientDataJSON),
        attestationObject: bufferToB64url(credential.response.attestationObject),
        publicKey: credential.response.getPublicKey ? bufferToB64url(credential.response.getPublicKey()) : null,
        publicKeyAlgorithm: credential.response.getPublicKeyAlgorithm ? credential.response.getPublicKeyAlgorithm() : null,
        authenticatorData: credential.response.getAuthenticatorData ? bufferToB64url(credential.response.getAuthenticatorData()) : null,
        transports: credential.response.getTransports ? credential.response.getTransports() : [],
      },
      clientExtensionResults: credential.getClientExtensionResults ? credential.getClientExtensionResults() : {},
    };

    let finishResponse;
    let finishPayload;
    try {
      finishResponse = await fetch('/api/v1/auth/passkey/register/finish', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ challengeId, credential: credentialPayload }),
      });
    } catch (error) {
      throw buildPasskeyStepError('register/finish', error, 'Network error while finishing passkey registration.');
    }

    finishPayload = await parseJsonSafely(finishResponse);
    if (!finishResponse.ok || finishPayload.status !== 'success') {
      throw buildPasskeyApiError('register/finish', finishResponse, finishPayload, 'Unable to finish passkey registration.');
    }

    // [CRYPTO] After successful registration, wrap the existing DEK for this credential.
    setPasskeyStatus('Securing your data with this passkey...', 'info');
    setPasskeyGridStatus('Securing your data with the new passkey...');
    try {
      await wrapDEKWithNewPasskey(finishPayload.credentialId || finishPayload.data?.credentialId || '');
      setPasskeyStatus('Passkey added successfully.', 'success');
      setPasskeyGridStatus('Passkey added successfully. Refreshing passkeys list...');
    } catch (dekWrapError) {
      PW.error(dekWrapError);
      setPasskeyStatus('Passkey added. Open your calendar once to complete setup.', 'warning');
      setPasskeyGridStatus('Passkey added. Open your calendar once to complete setup.');
    }

    await refreshPasskeyCredentials();
  };

  /**
   * After passkey registration, securely attach the existing data key (DEK) to that passkey.
   *
   * Plain-language behavior:
   * 1) Use the credential ID returned by verified passkey registration.
   * 2) Fetch encryption salt.
   * 3) Wrap DEK using credential ID + salt so future unlock is deterministic.
   */
  async function wrapDEKWithNewPasskey(registeredCredentialId = '') {
    debugLog('[PASSKEY] wrapDEKWithNewPasskey: starting DEK wrapping process (stable credential_id)...');

    if (!isWebAuthnCapableBrowser()) {
      throw new Error(WEB_AUTHN_UNSUPPORTED_MESSAGE);
    }

    const cryptoApi = getPasskeyWrapCryptoApi();
    if (!cryptoApi.hasDek) {
      await cryptoApi.ensureDEK();
    }

    if (!cryptoApi.hasDek) {
      throw new Error('[PASSKEY] DEK not available. Please unlock encrypted entries first.');
    }

    let credentialId = String(registeredCredentialId || '').trim();
    if (credentialId) {
      debugLog('[PASSKEY] Step 1 complete: using credential_id returned by registration');
    }

    if (!credentialId) {
      // Step 1: Get challenge for authenticating with newly registered passkey
      debugLog('[PASSKEY] Step 1: Requesting passkey challenge for DEK wrapping...');
      const loginStartResponse = await fetch('/api/v1/auth/passkey/login/start', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({}),  // Server will detect current user
      });

      if (!loginStartResponse.ok) {
        throw new Error('[PASSKEY] Unable to get challenge for passkey wrapping.');
      }

      const loginStartPayload = await loginStartResponse.json();
      if (loginStartPayload.status !== 'success') {
        throw new Error(loginStartPayload.message || '[PASSKEY] Challenge request failed.');
      }

      // Step 2: User authenticates with newly registered passkey
      debugLog('[PASSKEY] Step 2: Requesting passkey authentication...');
      const challengeId = loginStartPayload.challengeId;
      const options = loginStartPayload.publicKey || {};
      options.challenge = b64urlToBuffer(options.challenge || '');
      options.allowCredentials = Array.isArray(options.allowCredentials)
        ? options.allowCredentials.map((c) => ({
          ...c,
          id: b64urlToBuffer(c.id),
        }))
        : [];

      const assertion = await navigator.credentials.get({ publicKey: options });
      if (!assertion) {
        throw new Error('[PASSKEY] Passkey authentication was cancelled.');
      }
      debugLog('[PASSKEY] Step 2 complete: assertion obtained');

      // Step 3: Complete passkey login to get stable credential_id
      debugLog('[PASSKEY] Step 3: Completing passkey login to get credential_id...');

      const loginFinishResponse = await fetch('/api/v1/auth/passkey/login/finish', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          challengeId,
          assertion: {
            id: assertion.id,
            type: assertion.type,
            rawId: bufferToB64url(assertion.rawId),
            response: {
              clientDataJSON: bufferToB64url(assertion.response.clientDataJSON),
              authenticatorData: bufferToB64url(assertion.response.authenticatorData),
              signature: bufferToB64url(assertion.response.signature),
              userHandle: assertion.response.userHandle ? bufferToB64url(assertion.response.userHandle) : null,
            },
          },
        }),
      });

      if (!loginFinishResponse.ok) {
        throw new Error('[PASSKEY] DEK wrapping authentication failed.');
      }

      const loginFinishPayload = await loginFinishResponse.json();
      if (loginFinishPayload.status !== 'success') {
        throw new Error('[PASSKEY] DEK wrapping failed at server.');
      }

      // Get credential_id from response (stable, deterministic)
      if (!loginFinishPayload.data?.credential_id) {
        throw new Error('[PASSKEY] credential_id not returned from server.');
      }

      credentialId = loginFinishPayload.data.credential_id;
      debugLog('[PASSKEY] Step 3 complete: credential_id received (stable)');
    }

    // Step 4: Fetch encryption salt from bootstrap
    debugLog('[PASSKEY] Step 4: Fetching encryption salt...');
    const bootstrapResponse = await fetch('/api/v1/user/account/bootstrap', {
      method: 'GET',
      credentials: 'same-origin',
    });

    if (!bootstrapResponse.ok) {
      throw new Error('[PASSKEY] Unable to fetch encryption salt.');
    }

    const bootstrapPayload = await bootstrapResponse.json();
    const bootstrapData = (bootstrapPayload && typeof bootstrapPayload === 'object')
      ? (bootstrapPayload.data && typeof bootstrapPayload.data === 'object' ? bootstrapPayload.data : bootstrapPayload)
      : {};

    if (!bootstrapData.encryptionSalt) {
      throw new Error('[PASSKEY] Encryption salt not available.');
    }

    debugLog('[PASSKEY] Step 4 complete: encryption salt obtained');
    debugLog('[PASSKEY] Step 5: Wrapping DEK with passkey KEK (stable credential_id + salt)...');

    // Step 5: Call the global function to wrap DEK with stable credential_id
    await cryptoApi.wrapDEKWithPasskeyCredential(
      credentialId,
      bootstrapData.encryptionSalt
    );
    debugLog('[PASSKEY] Step 5 complete: DEK wrapped and uploaded (deterministic unwrap enabled)');
  };

  const createRecoveryKeyAction = async () => {
    const recoveryCrypto = getRecoveryCryptoApi();
    const isRegenerating = createRecoveryKeyButtonEl instanceof HTMLButtonElement
      && createRecoveryKeyButtonEl.dataset.hasRecoveryKey === '1';
    if (isRegenerating && !window.confirm(createRecoveryKeyButtonEl.dataset.replaceConfirm || SETTINGS_T.SETTINGS_RECOVERY_KEY_REGENERATE_CONFIRM)) {
      return;
    }

    createRecoveryKeyButtonEl?.setAttribute('disabled', 'disabled');
    setRecoveryKeyStatus('Unlocking encrypted entries...', 'info');

    try {
      const unlocked = await recoveryCrypto.ensureDEK();
      if (!unlocked || !recoveryCrypto.hasDek) {
        throw new Error('Encrypted entries are locked. Open your calendar once or sign in again, then retry.');
      }

      setRecoveryKeyStatus('Working…', 'info');
      let material = await recoveryCrypto.createRecoveryMaterial();
      let recoveryCodeForDisplay = String(material.recoveryKey || '').trim();
      const requestPayload = {
        wrappedDekRecovery: material.wrappedDekRecovery,
        accountRecoverySalt: material.accountRecoverySalt,
        recoveryProofKey: material.recoveryProofKey,
        recoveryKey: material.recoveryKey,
      };
      let requestBody = JSON.stringify(requestPayload);

      // Reduce key lifetime in main-thread memory as soon as request body is built.
      requestPayload.recoveryKey = '';
      material.recoveryKey = '';

      const response = await fetch('/api/v1/user/account/recovery-key', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: requestBody,
      });

      requestBody = '';
      material = null;

      const payload = await response.json();
      if (!response.ok || payload.status !== 'success') {
        throw new Error(payload.message || 'Unable to create Recovery Code.');
      }

      if (recoveryKeyBadgeEl instanceof HTMLElement) {
        recoveryKeyBadgeEl.dataset.hasRecoveryKey = '1';
        recoveryKeyBadgeEl.hidden = false;
        recoveryKeyBadgeEl.classList.add('is-visible');
        recoveryKeyBadgeEl.setAttribute('aria-label', SETTINGS_T.SETTINGS_JS_RECOVERY_KEY_ACTIVE);
      }
      if (recoveryKeyStatusValueEl instanceof HTMLElement) {
        recoveryKeyStatusValueEl.textContent = createRecoveryKeyButtonEl?.dataset.activeStatus || 'Active';
        recoveryKeyStatusValueEl.classList.remove('is-missing');
        recoveryKeyStatusValueEl.classList.add('is-active');
      }
      if (recoveryKeyUpdatedValueEl instanceof HTMLElement) {
        recoveryKeyUpdatedValueEl.textContent = new Date().toLocaleDateString(undefined, {
          year: 'numeric',
          month: 'long',
          day: 'numeric',
        });
      }
      if (createRecoveryKeyButtonEl instanceof HTMLButtonElement) {
        createRecoveryKeyButtonEl.dataset.hasRecoveryKey = '1';
        createRecoveryKeyButtonEl.textContent = createRecoveryKeyButtonEl.dataset.regenerateLabel || createRecoveryKeyButtonEl.textContent;
      }
      showRecoveryCodeOnce(recoveryCodeForDisplay);
      recoveryCodeForDisplay = '';

      setRecoveryKeyStatus(
        isRegenerating
          ? SETTINGS_T.SETTINGS_RECOVERY_KEY_SUCCESS_REGENERATE
          : SETTINGS_T.SETTINGS_RECOVERY_KEY_SUCCESS_CREATE,
        'success'
      );
    } finally {
      createRecoveryKeyButtonEl?.removeAttribute('disabled');
    }
  };

  const runAddPasskeyFlow = async () => {
    setAddPasskeyBusyState(true);
    try {
      await addPasskeyAction();
      markAddPasskeySuccess();
    } catch (error) {
      const errorMessage = simplifyPasskeyStatusMessage(error);
      setPasskeyStatus(errorMessage, 'error');
      setPasskeyGridStatus(`Passkey add failed: ${errorMessage}`);
      PW.error(`[PASSKEY] Add device failed: ${errorMessage}`);
    } finally {
      setAddPasskeyBusyState(false);
    }
  };

  const wireAddPasskeyButtons = () => {
    if (addPasskeyCardEl instanceof HTMLElement && !addPasskeyCardEl.dataset.passkeyBound) {
      addPasskeyCardEl.dataset.passkeyBound = '1';
      const triggerAddPasskey = () => {
        if (passkeyActionHardDisabled || addPasskeyCardEl?.getAttribute('aria-disabled') === 'true') {
          return;
        }
        runAddPasskeyFlow();
      };
      addPasskeyCardEl.addEventListener('click', triggerAddPasskey);
      addPasskeyCardEl.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') {
          return;
        }
        event.preventDefault();
        triggerAddPasskey();
      });
    }

    if (addPasskeyFirstButtonEl instanceof HTMLElement && !addPasskeyFirstButtonEl.dataset.passkeyBound) {
      addPasskeyFirstButtonEl.dataset.passkeyBound = '1';
      addPasskeyFirstButtonEl.addEventListener('click', () => {
        runAddPasskeyFlow();
      });
    }
  };

  if (!isWebAuthnCapableBrowser()) {
    passkeyActionHardDisabled = true;
    if (addPasskeyFirstButtonEl instanceof HTMLElement) {
      addPasskeyFirstButtonEl.disabled = true;
      addPasskeyFirstButtonEl.setAttribute('aria-disabled', 'true');
    }
    setPasskeyStatus(WEB_AUTHN_UNSUPPORTED_MESSAGE, 'warning');
    setPasskeyGridStatus(WEB_AUTHN_UNSUPPORTED_MESSAGE);
  }

  wireAddPasskeyButtons();

  if (createRecoveryKeyButtonEl) {
    createRecoveryKeyButtonEl.addEventListener('click', () => {
      createRecoveryKeyAction().catch((error) => {
        setRecoveryKeyStatus(error?.message || 'Unable to create Recovery Code. Try again.', 'error');
        PW.error(error);
      });
    });
  }

  const federatedProviderListEl = document.getElementById('federated_provider_list');
  const federatedProviderStatusEl = document.getElementById('federated_provider_status');
  const federatedResultStatus = (() => {
    const params = new URLSearchParams(window.location.search || '');
    return String(params.get('federated') || '').trim();
  })();
  const federatedResultMessage = (() => {
    switch (federatedResultStatus) {
      case 'linked':
        return 'Google connected.';
      case 'already_linked':
        return 'This Google account is already connected.';
      case 'provider_not_linked':
        return 'Google is not connected to this PayCal account yet. Try Connect Google from this Security page.';
      case 'invalid_provider_token':
        return 'Google returned a token PayCal could not verify. Try connecting again.';
      case 'invalid_state':
      case 'missing_callback_params':
        return 'The Google connection expired or was incomplete. Start again from Connect Google.';
      case 'link_failed':
        return 'PayCal could not find the account that started this Google connection. Sign in with your passkey and try again.';
      case 'provider_unavailable':
        return 'Google sign-in is not available right now.';
      default:
        return '';
    }
  })();

  const setFederatedProviderStatus = (message) => {
    if (federatedProviderStatusEl instanceof HTMLElement) {
      federatedProviderStatusEl.textContent = message;
    }
  };

  const renderFederatedProviders = (providers) => {
    if (!(federatedProviderListEl instanceof HTMLElement)) {
      return;
    }

    federatedProviderListEl.textContent = '';
    const visibleProviders = Array.isArray(providers)
      ? providers.filter((provider) => provider && provider.id === 'google' && provider.enabled === true)
      : [];

    if (visibleProviders.length === 0) {
      setFederatedProviderStatus('Google sign-in is not configured for this local host.');
      return;
    }

    visibleProviders.forEach((provider) => {
      const row = document.createElement('div');
      row.className = 'federated_provider_row';

      const label = document.createElement('div');
      label.className = 'federated_provider_label';
      label.textContent = provider.linked
        ? `Google connected${provider.email ? `: ${provider.email}` : ''}`
        : 'Google is not connected';

      const button = document.createElement('button');
      button.type = 'button';
      button.className = provider.linked ? 'btn btn_delete' : 'btn btn_primary';
      button.textContent = provider.linked ? 'Disconnect Google' : 'Connect Google';
      button.addEventListener('click', async () => {
        if (provider.linked) {
          try {
            setFederatedProviderStatus('Disconnecting Google...');
            const csrfToken = getSettingsCsrfToken();
            if (csrfToken === '') {
              throw new Error('Unable to verify this request. Refresh the page and try again.');
            }
            const response = await fetch('/api/v1/auth/federated/unlink', {
              method: 'POST',
              credentials: 'include',
              ...settingsJsonRequest({ provider: 'google' }),
            });
            const payload = await response.json();
            if (!response.ok || payload.status !== 'success') {
              throw new Error(payload.message || 'Unable to disconnect Google.');
            }
            renderFederatedProviders(payload.providers || []);
            setFederatedProviderStatus('Google disconnected.');
          } catch (error) {
            setFederatedProviderStatus(error?.message || 'Unable to disconnect Google.');
            PW.error(error);
          }
          return;
        }

        window.location.href = '/api/v1/auth/federated/start/google?mode=link';
      });

      row.appendChild(label);
      row.appendChild(button);
      federatedProviderListEl.appendChild(row);
    });
  };

  const refreshFederatedProviders = async () => {
    if (!(federatedProviderListEl instanceof HTMLElement)) {
      return;
    }

    try {
      setFederatedProviderStatus('Checking connected sign-in providers...');
      const response = await fetch('/api/v1/auth/federated/linked', {
        method: 'GET',
        credentials: 'include',
        headers: { 'Accept': 'application/json' },
      });
      const payload = await response.json();
      if (!response.ok || payload.status !== 'success') {
        throw new Error(payload.message || 'Unable to load connected sign-in providers.');
      }
      renderFederatedProviders(payload.providers || []);
      setFederatedProviderStatus(federatedResultMessage);
    } catch (error) {
      setFederatedProviderStatus(error?.message || 'Unable to load connected sign-in providers.');
      PW.error(error);
    }
  };

  refreshPasskeyCredentials();
  refreshFederatedProviders();

  const toggleChangeEmailStep = (showStep2) => {
    const step1 = PC.getElement('change_email_step1_section');
    const step2 = PC.getElement('change_email_step2_section');
    const startBtn = PC.getElement('change_email_start_btn');
    const verifyBtn = PC.getElement('change_email_verify_btn');
    const resendBtn = PC.getElement('change_email_resend_btn');
    const prevBtn = PC.getElement('change_email_prev_btn');

    if (step1) {
      step1.hidden = !!showStep2;
    }
    if (step2) {
      step2.hidden = !showStep2;
    }
    if (startBtn) {
      startBtn.hidden = !!showStep2;
    }
    if (verifyBtn) {
      verifyBtn.hidden = !showStep2;
    }
    if (resendBtn) {
      resendBtn.hidden = !showStep2;
    }
    if (prevBtn) {
      prevBtn.textContent = showStep2 ? SETTINGS_T.PREVIOUS : SETTINGS_T.CANCEL;
    }

    updateChangeEmailVerifyState();
  };

  const normalizeVerificationCode = (value) => String(value || '').toUpperCase().replace(/[-\s]/g, '').slice(0, 6);

  const updateChangeEmailVerifyState = () => {
    const verifyBtn = PC.getElement('change_email_verify_btn');
    const oldCodeInput = PC.getElement('change_email_old_code');
    const newCodeInput = PC.getElement('change_email_new_code');
    if (!verifyBtn || !oldCodeInput || !newCodeInput) {
      return;
    }

    const oldCode = normalizeVerificationCode(oldCodeInput.value);
    const newCode = normalizeVerificationCode(newCodeInput.value);
    const canVerify = oldCode.length >= 6 && newCode.length >= 6;

    verifyBtn.disabled = !canVerify;
    verifyBtn.setAttribute('aria-disabled', canVerify ? 'false' : 'true');
  };

  const resetChangeEmailModal = () => {
    PC.getElement('change_email_form')?.reset();
    const status = PC.getElement('change_email_status');
    const verifyStatus = PC.getElement('change_email_verify_status');
    const txn = PC.getElement('change_email_txn_id');
    const expiry = PC.getElement('change_email_expiry_timer');
    const oldHint = PC.getElement('old_email_hint');
    const newHint = PC.getElement('new_email_hint');
    if (status) status.textContent = '';
    if (verifyStatus) verifyStatus.textContent = '';
    if (txn) txn.value = '';
    if (expiry) expiry.textContent = '';
    if (oldHint) oldHint.textContent = '';
    if (newHint) newHint.textContent = '';
    clearFieldInvalidStates([
      'change_email_new_email',
      'change_email_confirm_email',
      'change_email_old_code',
      'change_email_new_code',
    ]);
    clearFieldErrorStates([
      ['change_email_new_email', 'change_email_new_email_error'],
      ['change_email_confirm_email', 'change_email_confirm_email_error'],
      ['change_email_old_code', 'change_email_old_code_error'],
      ['change_email_new_code', 'change_email_new_code_error'],
    ]);
    toggleChangeEmailStep(false);
  };

  const attachChangeEmailCodeInputHandlers = () => {
    ['change_email_old_code', 'change_email_new_code'].forEach((id) => {
      const input = PC.getElement(id);
      if (!input) {
        return;
      }

      const syncInput = () => {
        const normalized = normalizeVerificationCode(input.value);
        if (input.value !== normalized) {
          input.value = normalized;
        }
        const errorId = id === 'change_email_old_code' ? 'change_email_old_code_error' : 'change_email_new_code_error';
        setFieldErrorState(input, errorId, '');
        updateChangeEmailVerifyState();
      };

      input.addEventListener('input', syncInput);
      input.addEventListener('blur', syncInput);
    });
  };

  const parseApiResponse = async (response) => {
    const raw = await response.text();
    let data = null;
    try {
      data = JSON.parse(raw);
    } catch (_error) {
      data = null;
    }
    return { data, raw };
  };

  const cleanSettingsApiMessage = (message, fallback = SETTINGS_T.SETTINGS_DATA_CONSENT_ACTION_FAILED || 'Request failed.') => {
    const text = String(message || fallback).trim().replace(/^\[[^\]]+\]\s*/, '');
    return text === '' ? fallback : text;
  };

  const isSettingsApiStatusSuccess = (data) => {
    if (!data || typeof data !== 'object' || !('status' in data)) {
      return true;
    }
    const status = data.status;
    if (status === true || status === 'success' || status === 'ok') {
      return true;
    }
    if (typeof status === 'number') {
      return status >= 200 && status < 300;
    }
    const normalized = String(status || '').trim().toLowerCase();
    if (normalized === 'success' || normalized === 'ok') {
      return true;
    }
    const numericStatus = Number(normalized);
    return Number.isFinite(numericStatus) && numericStatus >= 200 && numericStatus < 300;
  };

  attachChangeEmailCodeInputHandlers();

  PC.addClickAndEnterListener('change_email_prev_btn', (e) => {
    e.preventDefault();
    const step2 = PC.getElement('change_email_step2_section');
    if (step2 && !step2.hidden) {
      toggleChangeEmailStep(false);
      return;
    }

    resetChangeEmailModal();
    PC.closeModal('modal_change_email', SETTINGS_T.CHANGE_EMAIL);
  });

  const CHANGE_EMAIL_I18N = {
    enterBothEmails: <?php echo json_encode(Strings::i18n('CHANGE_EMAIL_STATUS_ENTER_BOTH_EMAILS'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    enterNewEmail: <?php echo json_encode(Strings::i18n('CHANGE_EMAIL_ERROR_ENTER_NEW_EMAIL'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    confirmNewEmail: <?php echo json_encode(Strings::i18n('CHANGE_EMAIL_ERROR_CONFIRM_NEW_EMAIL'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    emailsNoMatch: <?php echo json_encode(Strings::i18n('CHANGE_EMAIL_STATUS_EMAILS_NO_MATCH'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    emailsMustMatch: <?php echo json_encode(Strings::i18n('CHANGE_EMAIL_ERROR_EMAILS_MUST_MATCH'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    working: <?php echo json_encode(Strings::i18n('CHANGE_EMAIL_STATUS_WORKING'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    codesSent: <?php echo json_encode(Strings::i18n('CHANGE_EMAIL_STATUS_CODES_SENT'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    requestFailedPrefix: <?php echo json_encode(Strings::i18n('CHANGE_EMAIL_STATUS_REQUEST_FAILED_PREFIX'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    enterBothCodes: <?php echo json_encode(Strings::i18n('CHANGE_EMAIL_STATUS_ENTER_BOTH_CODES'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    enterValid6CharCode: <?php echo json_encode(Strings::i18n('CHANGE_EMAIL_ERROR_ENTER_VALID_6_CHAR_CODE'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    emailUpdated: <?php echo json_encode(Strings::i18n('CHANGE_EMAIL_STATUS_EMAIL_UPDATED'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    sessionExpired: <?php echo json_encode(Strings::i18n('CHANGE_EMAIL_STATUS_SESSION_EXPIRED'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
  };
  const RECOVERY_EMAIL_I18N = {
    enterValidEmail: <?php echo json_encode(Strings::i18n('RECOVERY_EMAIL_ERROR_ENTER_VALID_EMAIL'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    securityTokenMissing: <?php echo json_encode(Strings::i18n('RECOVERY_EMAIL_STATUS_SECURITY_TOKEN_MISSING'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    sendingCode: <?php echo json_encode(Strings::i18n('RECOVERY_EMAIL_STATUS_SENDING_CODE'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    codeSentCheckEmail: <?php echo json_encode(Strings::i18n('RECOVERY_EMAIL_STATUS_CODE_SENT_CHECK_EMAIL'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    networkErrorTryAgain: <?php echo json_encode(Strings::i18n('RECOVERY_EMAIL_STATUS_NETWORK_ERROR'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    enter6DigitCode: <?php echo json_encode(Strings::i18n('RECOVERY_EMAIL_ERROR_ENTER_6_DIGIT_CODE'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    verifying: <?php echo json_encode(Strings::i18n('RECOVERY_EMAIL_STATUS_VERIFYING'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    verified: <?php echo json_encode(Strings::i18n('RECOVERY_EMAIL_STATUS_VERIFIED'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    resendingCode: <?php echo json_encode(Strings::i18n('RECOVERY_EMAIL_STATUS_RESENDING_CODE'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    newCodeSent: <?php echo json_encode(Strings::i18n('RECOVERY_EMAIL_STATUS_NEW_CODE_SENT'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
  };

  if (document.getElementById('edit_details_change_email_link')) {
    PC.addClickAndEnterListener('edit_details_change_email_link', (e) => {
      e.preventDefault();
      resetChangeEmailModal();
      PC.openModal('modal_change_email', SETTINGS_T.CHANGE_EMAIL);
    });
  }

  PC.addClickAndEnterListener('change_email_start_btn', async (e) => {
    e.preventDefault();
    const newEmailInput = PC.getElement('change_email_new_email');
    const confirmEmailInput = PC.getElement('change_email_confirm_email');
    const newEmail = String(newEmailInput?.value || '').trim();
    const confirmEmail = String(confirmEmailInput?.value || '').trim();
    const statusEl = PC.getElement('change_email_status');

    setFieldErrorState(newEmailInput, 'change_email_new_email_error', '');
    setFieldErrorState(confirmEmailInput, 'change_email_confirm_email_error', '');

    if (!newEmail || !confirmEmail) {
      if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.enterBothEmails;
      if (!newEmail) {
        setFieldErrorState(newEmailInput, 'change_email_new_email_error', CHANGE_EMAIL_I18N.enterNewEmail);
      }
      if (!confirmEmail) {
        setFieldErrorState(confirmEmailInput, 'change_email_confirm_email_error', CHANGE_EMAIL_I18N.confirmNewEmail);
      }
      (newEmail ? confirmEmailInput : newEmailInput)?.focus();
      return;
    }
    if (newEmail !== confirmEmail) {
      if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.emailsNoMatch;
      setFieldErrorState(newEmailInput, 'change_email_new_email_error', CHANGE_EMAIL_I18N.emailsMustMatch);
      setFieldErrorState(confirmEmailInput, 'change_email_confirm_email_error', CHANGE_EMAIL_I18N.emailsMustMatch);
      confirmEmailInput?.focus();
      return;
    }

    try {
      if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.working;
      const response = await fetch('/api/v1/account/change-email/start', {
        method: 'POST',
        credentials: 'same-origin',
        ...settingsJsonRequest({ new_email: newEmail }),
      });
      const { data, raw } = await parseApiResponse(response);

      if (response.ok && data && data.status === 'success') {
        setFieldErrorState(newEmailInput, 'change_email_new_email_error', '');
        setFieldErrorState(confirmEmailInput, 'change_email_confirm_email_error', '');
        const txn = PC.getElement('change_email_txn_id');
        const oldHint = PC.getElement('old_email_hint');
        const newHint = PC.getElement('new_email_hint');
        const expiry = PC.getElement('change_email_expiry_timer');

        if (txn) txn.value = data.txn_id || '';
        if (oldHint) oldHint.textContent = data.old_email_hint || '';
        if (newHint) newHint.textContent = data.new_email_hint || '';
        if (expiry) expiry.textContent = formatSettingsMessage(SETTINGS_T.SETTINGS_JS_CODES_EXPIRE_MINUTES_FMT, { minutes: data.expires_in_minutes });
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.codesSent;

        toggleChangeEmailStep(true);
        setTimeout(() => PC.getElement('change_email_old_code')?.focus(), 50);
      } else {
        const apiMessage = data && typeof data.message === 'string' ? data.message : '';
        const fallback = raw ? raw.slice(0, 180) : `HTTP ${response.status}`;
        if (statusEl) statusEl.textContent = apiMessage || `Failed to send codes. ${fallback}`;
      }
    } catch (error) {
      if (statusEl) statusEl.textContent = `${CHANGE_EMAIL_I18N.requestFailedPrefix} ${String(error?.message || SETTINGS_T.BUSINESSES_UNKNOWN_ERROR)}`;
      PW.error(error);
    }
  });

  PC.addClickAndEnterListener('change_email_verify_btn', async (e) => {
    e.preventDefault();
    const oldCodeInput = PC.getElement('change_email_old_code');
    const newCodeInput = PC.getElement('change_email_new_code');
    const txnId = String(PC.getElement('change_email_txn_id')?.value || '').trim();
    const oldCode = normalizeVerificationCode(oldCodeInput?.value || '');
    const newCode = normalizeVerificationCode(newCodeInput?.value || '');
    const statusEl = PC.getElement('change_email_verify_status');

    setFieldErrorState(oldCodeInput, 'change_email_old_code_error', '');
    setFieldErrorState(newCodeInput, 'change_email_new_code_error', '');

    if (!txnId || oldCode.length !== 6 || newCode.length !== 6) {
      if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.enterBothCodes;
      if (oldCode.length !== 6) {
        setFieldErrorState(oldCodeInput, 'change_email_old_code_error', CHANGE_EMAIL_I18N.enterValid6CharCode);
      }
      if (newCode.length !== 6) {
        setFieldErrorState(newCodeInput, 'change_email_new_code_error', CHANGE_EMAIL_I18N.enterValid6CharCode);
      }
      (oldCode.length !== 6 ? oldCodeInput : newCodeInput)?.focus();
      return;
    }

    try {
      if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.working;
      const response = await fetch('/api/v1/account/change-email/verify', {
        method: 'POST',
        credentials: 'same-origin',
        ...settingsJsonRequest({ txn_id: txnId, old_code: oldCode, new_code: newCode }),
      });
      const { data, raw } = await parseApiResponse(response);

      if (response.ok && data && data.status === 'success') {
        setFieldErrorState(oldCodeInput, 'change_email_old_code_error', '');
        setFieldErrorState(newCodeInput, 'change_email_new_code_error', '');
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.emailUpdated;
        setTimeout(() => {
          PC.closeModal('modal_change_email', SETTINGS_T.CHANGE_EMAIL);
          location.reload();
        }, 1000);
      } else if (statusEl) {
        const apiMessage = data && typeof data.message === 'string' ? data.message : '';
        const fallback = raw ? raw.slice(0, 180) : `HTTP ${response.status}`;
        const errorText = apiMessage || `Verification failed. ${fallback}`;
        statusEl.textContent = errorText;
        setFieldErrorState(oldCodeInput, 'change_email_old_code_error', errorText);
        setFieldErrorState(newCodeInput, 'change_email_new_code_error', errorText);
      }
    } catch (error) {
      if (statusEl) statusEl.textContent = `${CHANGE_EMAIL_I18N.requestFailedPrefix} ${String(error?.message || SETTINGS_T.BUSINESSES_UNKNOWN_ERROR)}`;
      PW.error(error);
    }
  });

  PC.addClickAndEnterListener('change_email_resend_btn', async (e) => {
    e.preventDefault();
    const txnId = String(PC.getElement('change_email_txn_id')?.value || '').trim();
    const statusEl = PC.getElement('change_email_verify_status');
    if (!txnId) {
      if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.sessionExpired;
      return;
    }

    try {
      if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.working;
      const response = await fetch('/api/v1/account/change-email/resend', {
        method: 'POST',
        credentials: 'same-origin',
        ...settingsJsonRequest({ txn_id: txnId }),
      });
      const { data, raw } = await parseApiResponse(response);
      if (response.ok && data && data.status === 'success') {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.codesSent;
      } else if (statusEl) {
        const apiMessage = data && typeof data.message === 'string' ? data.message : '';
        const fallback = raw ? raw.slice(0, 180) : `HTTP ${response.status}`;
        statusEl.textContent = apiMessage || `Failed to resend codes. ${fallback}`;
      }
    } catch (error) {
      if (statusEl) statusEl.textContent = `${CHANGE_EMAIL_I18N.requestFailedPrefix} ${String(error?.message || SETTINGS_T.BUSINESSES_UNKNOWN_ERROR)}`;
      PW.error(error);
    }
  });

  PC.addClickAndEnterListener('call_delete_account_modal', (e) => {
    e.preventDefault();
    PC.openModal('modal_delete_account', SETTINGS_T.DELETE_ACCOUNT);
    PC.getElement('delete_account_confirm_phrase').focus();
  });
  PC.addClickAndEnterListener('delete_account_cancel_btn', (e) => { e.preventDefault(); PC.closeModal('modal_delete_account', SETTINGS_T.DELETE_ACCOUNT); });


  const deleteAccountForm = PC.getElement('delete_account_form');
  const deleteConfirmInput = PC.getElement('delete_account_confirm_phrase');
  const deleteStatus = PC.getElement('delete_account_status');

  if (deleteAccountForm && deleteConfirmInput) {
    deleteConfirmInput.addEventListener('input', () => {
      deleteConfirmInput.value = String(deleteConfirmInput.value || '').toUpperCase();
      setFieldErrorState(deleteConfirmInput, 'delete_account_confirm_error', '');
    });

    deleteAccountForm.addEventListener('submit', (event) => {
      const phrase = String(deleteConfirmInput.value || '').trim().toUpperCase();
      if (phrase !== 'DELETE MY ACCOUNT') {
        event.preventDefault();
        if (deleteStatus) {
          deleteStatus.textContent = SETTINGS_T.SETTINGS_JS_DELETE_ACCOUNT_TYPE_PHRASE;
        }
        setFieldErrorState(deleteConfirmInput, 'delete_account_confirm_error', SETTINGS_T.SETTINGS_JS_DELETE_ACCOUNT_TYPE_PHRASE);
        deleteConfirmInput.focus();
        deleteConfirmInput.select();
      } else {
        setFieldErrorState(deleteConfirmInput, 'delete_account_confirm_error', '');
      }
    });
  }

  const editDetailsPhone = PC.getElement('edit_details_phone');
  const resolveSettingsDialCodeFromLocale = (localeValue) => {
    const locale = String(localeValue || '').trim();
    const dialCodesByLocale = {
      'en-CA': '+1',
      'fr-CA': '+1',
      'en-US': '+1',
      'en-GB': '+44',
      'fr-FR': '+33',
      'de-DE': '+49',
      'es-ES': '+34',
      'pt-BR': '+55',
    };

    return dialCodesByLocale[locale] || '+1';
  };

  const syncSettingsPhoneCountryAdornment = () => {
    const phoneInput = PC.getElement('edit_details_phone');
    if (!(phoneInput instanceof HTMLInputElement)) {
      return;
    }

    const parent = phoneInput.parentElement;
    if (!(parent instanceof HTMLElement)) {
      return;
    }

    let shell = phoneInput.closest('.personal_phone_input_shell');
    if (!(shell instanceof HTMLElement)) {
      shell = document.createElement('div');
      shell.className = 'personal_phone_input_shell';
      parent.insertBefore(shell, phoneInput);
      shell.appendChild(phoneInput);
    }

    let dialCodeEl = shell.querySelector('.personal_phone_country_code');
    if (!(dialCodeEl instanceof HTMLElement)) {
      dialCodeEl = document.createElement('span');
      dialCodeEl.className = 'personal_phone_country_code';
      dialCodeEl.setAttribute('aria-hidden', 'true');
      shell.insertBefore(dialCodeEl, phoneInput);
    }
    shell.querySelectorAll('.personal_phone_country_code').forEach((candidate) => {
      if (candidate !== dialCodeEl) {
        candidate.remove();
      }
    });

    const localeInput = PC.getElement('businesses_personal_locale');
    const locale = localeInput instanceof HTMLSelectElement
      ? String(localeInput.value || 'en-CA')
      : 'en-CA';
    dialCodeEl.textContent = resolveSettingsDialCodeFromLocale(locale);
  };

  if (editDetailsPhone) {
    syncSettingsPhoneCountryAdornment();
    PC.formatPhoneNumber(editDetailsPhone);
    editDetailsPhone.addEventListener('input', (e) => {
      PC.formatPhoneNumber(e.target);
    });
    editDetailsPhone.addEventListener('change', (e) => {
      PC.formatPhoneNumber(e.target);
    });
  }
  const settingsPersonalLocale = PC.getElement('businesses_personal_locale');
  if (settingsPersonalLocale) {
    settingsPersonalLocale.addEventListener('change', syncSettingsPhoneCountryAdornment);
  }

  const editDetailsForm = PC.getElement('edit_details_form');
  if (editDetailsForm) {
    const editDetailsStatus = PC.getElement('edit_details_status');

    const editDetailsValidationPairs = [
      ['edit_details_full_name', 'edit_details_full_name_error'],
      ['edit_details_phone', 'edit_details_phone_error'],
      ['edit_details_province', 'edit_details_province_error'],
      ['edit_details_employment_type', 'edit_details_employment_type_error'],
      ['edit_details_job_title', 'edit_details_job_title_error'],
      ['edit_details_department', 'edit_details_department_error'],
      ['edit_details_hire_date', 'edit_details_hire_date_error'],
      ['edit_details_pay_rate', 'edit_details_pay_rate_error'],
      ['edit_details_pay_rate_type', 'edit_details_pay_rate_type_error'],
      ['edit_details_address_line1', 'edit_details_address_line1_error'],
      ['edit_details_address_city', 'edit_details_address_city_error'],
      ['edit_details_address_postal', 'edit_details_address_postal_error'],
      ['edit_details_reserve_name', 'edit_details_reserve_name_error'],
    ];

    const clearEditDetailsValidationState = () => {
      clearFieldErrorStates(editDetailsValidationPairs);
      if (editDetailsStatus) {
        editDetailsStatus.textContent = '';
      }
    };

    const validateEditDetailsForm = () => {
      clearEditDetailsValidationState();

      const fullNameInput = PC.getElement('edit_details_full_name');
      const phoneInput = PC.getElement('edit_details_phone');
      const provinceInput = PC.getElement('edit_details_province');
      const employmentTypeInput = PC.getElement('edit_details_employment_type');
      const jobTitleInput = PC.getElement('edit_details_job_title');
      const departmentInput = PC.getElement('edit_details_department');
      const hireDateInput = PC.getElement('edit_details_hire_date');
      const payRateInput = PC.getElement('edit_details_pay_rate');
      const payRateTypeInput = PC.getElement('edit_details_pay_rate_type');

      let firstInvalidField = null;
      const markInvalid = (input, errorId, message) => {
        setFieldErrorState(input, errorId, message);
        if (!firstInvalidField && input) {
          firstInvalidField = input;
        }
      };

      const fullName = String(fullNameInput?.value || '').trim();
      if (fullName.length < 2) {
        markInvalid(fullNameInput, 'edit_details_full_name_error', 'Enter your full name.');
      }

      const phone = String(phoneInput?.value || '').trim();
      if (phone.length > 0 && !/^\(\d{3}\) \d{3}-\d{4}$/.test(phone)) {
        markInvalid(phoneInput, 'edit_details_phone_error', 'Use phone format (123) 456-7890.');
      }

      const province = String(provinceInput?.value || '').trim();
      if (province.length !== 2) {
        markInvalid(provinceInput, 'edit_details_province_error', 'Select a province.');
      }

      const employmentType = String(employmentTypeInput?.value || '').trim();
      if (employmentType.length > 0 && !['full_time', 'part_time', 'contractor', 'casual'].includes(employmentType)) {
        markInvalid(employmentTypeInput, 'edit_details_employment_type_error', 'Select a valid employment type.');
      }

      const jobTitle = String(jobTitleInput?.value || '').trim();
      if (jobTitle.length > 80) {
        markInvalid(jobTitleInput, 'edit_details_job_title_error', 'Job title must be 80 characters or fewer.');
      }

      const department = String(departmentInput?.value || '').trim();
      if (department.length > 80) {
        markInvalid(departmentInput, 'edit_details_department_error', 'Department must be 80 characters or fewer.');
      }

      const hireDate = String(hireDateInput?.value || '').trim();
      if (hireDate.length > 0 && !/^\d{4}-\d{2}-\d{2}$/.test(hireDate)) {
        markInvalid(hireDateInput, 'edit_details_hire_date_error', 'Use date format YYYY-MM-DD.');
      }

      const payRate = String(payRateInput?.value || '').trim();
      if (payRate.length > 0 && (!/^\d+(\.\d{1,2})?$/.test(payRate) || parseFloat(payRate) <= 0)) {
        markInvalid(payRateInput, 'edit_details_pay_rate_error', 'Enter a pay rate greater than zero (for example 25 or 25.50).');
      }

      const payRateType = String(payRateTypeInput?.value || '').trim();
      if (payRateType.length > 0 && !['hourly', 'salary', 'day_rate'].includes(payRateType)) {
        markInvalid(payRateTypeInput, 'edit_details_pay_rate_type_error', 'Select a valid pay rate type.');
      }

      if (firstInvalidField) {
        if (editDetailsStatus) {
          editDetailsStatus.textContent = SETTINGS_T.BUSINESSES_CORRECT_HIGHLIGHTED_FIELDS;
        }
        firstInvalidField.focus();
        return false;
      }

      return true;
    };

    const buildEditDetailsSignature = () => {
      const formData = new FormData(editDetailsForm);
      return Array.from(formData.entries())
        .map(([key, value]) => `${key}:${String(value)}`)
        .sort()
        .join('|');
    };

    let editDetailsSaveTimer = null;
    let editDetailsLastSavedSignature = buildEditDetailsSignature();

    const saveEditDetailsForm = () => {
      if (editDetailsSaveTimer !== null) {
        window.clearTimeout(editDetailsSaveTimer);
        editDetailsSaveTimer = null;
      }

      if (!validateEditDetailsForm()) {
        return;
      }

      const nextSignature = buildEditDetailsSignature();
      if (nextSignature === editDetailsLastSavedSignature) {
        return;
      }

      if (editDetailsStatus) {
        editDetailsStatus.textContent = <?php echo json_encode($i18n['UPDATING_INFO'] . '...', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>;
      }

      const formData = new FormData(editDetailsForm);
      PC.updateResource('account/info', formData).then(() => {
        editDetailsLastSavedSignature = nextSignature;
        clearEditDetailsValidationState();
        if (editDetailsStatus) {
          editDetailsStatus.textContent = <?php echo json_encode($i18n['INFO_UPDATED'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>;
        }
      }).catch(error => {
        if (editDetailsStatus) {
          editDetailsStatus.textContent = SETTINGS_T.BUSINESSES_SAVE_ACCOUNT_DETAILS_FAILED;
        }
        PW.error(error);
      });
    };

    const scheduleEditDetailsAutosave = (delayMs = 600) => {
      if (editDetailsSaveTimer !== null) {
        window.clearTimeout(editDetailsSaveTimer);
      }
      editDetailsSaveTimer = window.setTimeout(saveEditDetailsForm, delayMs);
    };

    editDetailsForm.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
      if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) {
        return;
      }
      if (field.disabled || field.type === 'hidden' || field.name === 'csrf_token' || field.name === 'username') {
        return;
      }

      if (field instanceof HTMLInputElement && (field.type === 'checkbox' || field.type === 'radio')) {
        field.addEventListener('change', () => saveEditDetailsForm());
        return;
      }

      if (field instanceof HTMLSelectElement || (field instanceof HTMLInputElement && field.type === 'date')) {
        field.addEventListener('change', () => saveEditDetailsForm());
        return;
      }

      field.addEventListener('input', () => scheduleEditDetailsAutosave());
      field.addEventListener('change', () => saveEditDetailsForm());
    });

    editDetailsForm.addEventListener('submit', (e) => {
      e.preventDefault();
      saveEditDetailsForm();
    });
  }

  /* RECOVERY EMAIL VERIFICATION */
  let recoveryEmailState = {
    codeSentAt: null,
    resendCooldownRemainingSeconds: 0,
    resendCooldownTimerId: null,
    codeExpiresAt: null,
    expiryTimerId: null,
    currentEmail: null,
    isRequestInFlight: false,
  };

  const getRecoveryEmailCsrfToken = () => {
    const form = PC.getElement('edit_details_form');
    const formToken = form?.querySelector('input[name="csrf_token"]');
    if (formToken && typeof formToken.value === 'string' && formToken.value !== '') {
      return formToken.value;
    }

    return getSettingsCsrfToken();
  };

  const formatCooldownTime = (totalSeconds) => {
    const safeSeconds = Math.max(0, Number.parseInt(String(totalSeconds), 10) || 0);
    const minutes = Math.floor(safeSeconds / 60);
    const seconds = safeSeconds % 60;
    return `${minutes}:${String(seconds).padStart(2, '0')}`;
  };

  const clearResendCooldownTimer = () => {
    if (!recoveryEmailState.resendCooldownTimerId) return;
    window.clearInterval(recoveryEmailState.resendCooldownTimerId);
    recoveryEmailState.resendCooldownTimerId = null;
  };

  const clearExpiryTimer = () => {
    if (!recoveryEmailState.expiryTimerId) return;
    window.clearInterval(recoveryEmailState.expiryTimerId);
    recoveryEmailState.expiryTimerId = null;
  };

  const updateSendButtonState = () => {
    const sendBtn = PC.getElement('recovery_email_send_btn');
    if (!sendBtn) return;

    const hasPendingCode = recoveryEmailState.codeSentAt !== null;
    const isCoolingDown = recoveryEmailState.resendCooldownRemainingSeconds > 0;

    if (hasPendingCode) {
      sendBtn.textContent = isCoolingDown
        ? `Resend Code (${formatCooldownTime(recoveryEmailState.resendCooldownRemainingSeconds)})`
        : 'Resend Code';
      sendBtn.setAttribute('aria-label', 'Resend Verification Code');
      sendBtn.dataset.hoverHelp = 'Request a new recovery email verification code.';
      sendBtn.disabled = isCoolingDown || recoveryEmailState.isRequestInFlight;
    } else {
      sendBtn.textContent = SETTINGS_T.SETTINGS_RECOVERY_SEND_BUTTON;
      sendBtn.setAttribute('aria-label', 'Send Verification Code');
      sendBtn.dataset.hoverHelp = 'Send a one-time code to verify this recovery email.';
      sendBtn.disabled = recoveryEmailState.isRequestInFlight;
    }

    sendBtn.setAttribute('aria-disabled', sendBtn.disabled ? 'true' : 'false');
    sendBtn.setAttribute('aria-busy', recoveryEmailState.isRequestInFlight ? 'true' : 'false');
    sendBtn.classList.toggle('is-working', recoveryEmailState.isRequestInFlight);
  };

  const updateRecoveryEmailUI = () => {
    const sendBtn = PC.getElement('recovery_email_send_btn');
    const verifySection = PC.getElement('recovery_email_verify_section');
    const emailInput = PC.getElement('recovery_email_input');
    
    if (!sendBtn || !verifySection || !emailInput) return;

    const hasPendingCode = recoveryEmailState.codeSentAt !== null;

    if (hasPendingCode) {
      verifySection.hidden = false;
      emailInput.readOnly = true;
      emailInput.setAttribute('aria-readonly', 'true');
    } else {
      verifySection.hidden = true;
      emailInput.readOnly = false;
      emailInput.removeAttribute('aria-readonly');
    }

    sendBtn.hidden = false;
    updateSendButtonState();
  };

  const startResendCooldown = (cooldownSeconds) => {
    recoveryEmailState.resendCooldownRemainingSeconds = Math.max(0, Number.parseInt(String(cooldownSeconds), 10) || 0);
    clearResendCooldownTimer();
    updateSendButtonState();

    if (recoveryEmailState.resendCooldownRemainingSeconds <= 0) {
      return;
    }

    recoveryEmailState.resendCooldownTimerId = window.setInterval(() => {
      recoveryEmailState.resendCooldownRemainingSeconds = Math.max(0, recoveryEmailState.resendCooldownRemainingSeconds - 1);
      updateSendButtonState();

      if (recoveryEmailState.resendCooldownRemainingSeconds <= 0) {
        clearResendCooldownTimer();
      }
    }, 1000);
  };

  const startExpiryTimer = (ttlMinutes) => {
    const now = Date.now();
    const safeMinutes = Math.max(1, Number.parseInt(String(ttlMinutes), 10) || 10);
    recoveryEmailState.codeExpiresAt = now + (safeMinutes * 60 * 1000);
    clearExpiryTimer();
    updateExpiryDisplay();

    recoveryEmailState.expiryTimerId = window.setInterval(() => {
      if (!recoveryEmailState.codeExpiresAt) {
        clearExpiryTimer();
        return;
      }

      updateExpiryDisplay();
    }, 1000);
  };

  const updateExpiryDisplay = () => {
    const expiryEl = PC.getElement('recovery_email_expiry_timer');
    if (!expiryEl || !recoveryEmailState.codeExpiresAt) return;

    const now = Date.now();
    const timeRemaining = Math.max(0, recoveryEmailState.codeExpiresAt - now);

    if (timeRemaining > 0) {
      const minutes = Math.floor(timeRemaining / 60000);
      const seconds = Math.floor((timeRemaining % 60000) / 1000);
      expiryEl.textContent = formatSettingsMessage(SETTINGS_T.SETTINGS_JS_CODE_EXPIRES_FMT, { time: `${minutes}:${String(seconds).padStart(2, '0')}` });
      
      setTimeout(updateExpiryDisplay, 1000);
    } else {
      expiryEl.textContent = SETTINGS_T.SETTINGS_JS_CODE_EXPIRED;
      recoveryEmailState.codeExpiresAt = null;
      const codeInput = PC.getElement('recovery_email_code_input');
      if (codeInput) codeInput.disabled = true;
      const verifyBtn = PC.getElement('recovery_email_verify_btn');
      if (verifyBtn) verifyBtn.disabled = true;
      clearExpiryTimer();
    }
  };

  const parseRecoveryResponse = async (response) => {
    try {
      return await response.json();
    } catch (_) {
      return {};
    }
  };

  const setRecoveryStatus = (statusEl, state) => {
    setPrefixedStatusText(statusEl, '', state, { updateText: false });
  };

  const sendRecoveryEmailCode = async () => {
    const emailInput = PC.getElement('recovery_email_input');
    const statusEl = PC.getElement('recovery_email_send_status');

    if (!emailInput || !statusEl) return;

    const email = emailInput.value.trim();
    const csrfToken = getRecoveryEmailCsrfToken();
    setFieldErrorState(emailInput, 'recovery_email_input_error', '');
    
    if (!email || !email.includes('@')) {
      statusEl.textContent = RECOVERY_EMAIL_I18N.enterValidEmail;
      setRecoveryStatus(statusEl, 'error');
      setFieldErrorState(emailInput, 'recovery_email_input_error', RECOVERY_EMAIL_I18N.enterValidEmail);
      emailInput.focus();
      return;
    }

    if (!csrfToken) {
      statusEl.textContent = RECOVERY_EMAIL_I18N.securityTokenMissing;
      setRecoveryStatus(statusEl, 'error');
      return;
    }

    recoveryEmailState.isRequestInFlight = true;
    updateSendButtonState();
    statusEl.textContent = RECOVERY_EMAIL_I18N.sendingCode;
    setRecoveryStatus(statusEl, 'muted');

    try {
      const response = await fetch('/api/v1/account/recovery-email/start', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          recovery_email: email,
          csrf_token: csrfToken,
        }),
      });

      const data = await parseRecoveryResponse(response);

      if (!response.ok) {
        const errorMsg = data.error || data.message || 'Failed to send code.';
        statusEl.textContent = errorMsg;
        setRecoveryStatus(statusEl, 'error');
        setFieldErrorState(emailInput, 'recovery_email_input_error', errorMsg);
        if ((response.status === 429 || response.status === 400) && Number.isFinite(Number(data.retry_after))) {
          startResendCooldown(Number(data.retry_after));
        }
        recoveryEmailState.isRequestInFlight = false;
        updateSendButtonState();
        return;
      }

      setFieldErrorState(emailInput, 'recovery_email_input_error', '');

      recoveryEmailState.codeSentAt = Date.now();
      recoveryEmailState.currentEmail = email;
      
      statusEl.textContent = RECOVERY_EMAIL_I18N.codeSentCheckEmail;
      setRecoveryStatus(statusEl, 'info');
      
      const ttlMinutes = data.expires_in_minutes || data.code_ttl_minutes || 10;
      const cooldownSeconds = data.resend_cooldown_seconds || 30;
      
      startExpiryTimer(ttlMinutes);
      startResendCooldown(cooldownSeconds);
      recoveryEmailState.isRequestInFlight = false;
      updateRecoveryEmailUI();
      
      const codeInput = PC.getElement('recovery_email_code_input');
      if (codeInput) {
        codeInput.disabled = false;
        codeInput.value = '';
        const verifyBtn = PC.getElement('recovery_email_verify_btn');
        if (verifyBtn) verifyBtn.disabled = false;
        setTimeout(() => codeInput.focus(), 100);
      }
    } catch (error) {
      statusEl.textContent = RECOVERY_EMAIL_I18N.networkErrorTryAgain;
      setRecoveryStatus(statusEl, 'error');
      recoveryEmailState.isRequestInFlight = false;
      updateSendButtonState();
      PW.error(error);
    }
  };

  const verifyRecoveryEmailCode = async () => {
    const codeInput = PC.getElement('recovery_email_code_input');
    const statusEl = PC.getElement('recovery_email_verify_status');
    const verifyBtn = PC.getElement('recovery_email_verify_btn');
    
    if (!codeInput || !statusEl || !verifyBtn) return;

    const code = normalizeVerificationCode(codeInput.value);
    codeInput.value = code;
    const csrfToken = getRecoveryEmailCsrfToken();
    setFieldErrorState(codeInput, 'recovery_email_code_error', '');
    
    if (!code || code.length !== 6) {
      statusEl.textContent = RECOVERY_EMAIL_I18N.enter6DigitCode;
      setRecoveryStatus(statusEl, 'error');
      setFieldErrorState(codeInput, 'recovery_email_code_error', RECOVERY_EMAIL_I18N.enter6DigitCode);
      codeInput.focus();
      return;
    }

    if (!csrfToken) {
      statusEl.textContent = RECOVERY_EMAIL_I18N.securityTokenMissing;
      setRecoveryStatus(statusEl, 'error');
      verifyBtn.disabled = false;
      return;
    }

    verifyBtn.disabled = true;
    statusEl.textContent = RECOVERY_EMAIL_I18N.verifying;
    setRecoveryStatus(statusEl, 'muted');

    try {
      const response = await fetch('/api/v1/account/recovery-email/verify', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          code: code,
          csrf_token: csrfToken,
        }),
      });

      const data = await parseRecoveryResponse(response);

      if (!response.ok) {
        const errorMsg = data.error || data.message || 'Verification failed.';
        statusEl.textContent = errorMsg;
        setRecoveryStatus(statusEl, 'error');
        setFieldErrorState(codeInput, 'recovery_email_code_error', errorMsg);
        verifyBtn.disabled = false;
        codeInput.value = '';
        codeInput.focus();
        return;
      }

      setFieldErrorState(codeInput, 'recovery_email_code_error', '');
      statusEl.textContent = RECOVERY_EMAIL_I18N.verified;
      setRecoveryStatus(statusEl, 'success');
      
      // Update status display
      const statusDisplay = PC.getElement('recovery_email_status_display');
      if (statusDisplay && recoveryEmailState.currentEmail) {
        statusDisplay.textContent = `✓ ${recoveryEmailState.currentEmail}`;
      }
      
      // Reset state and UI
      recoveryEmailState.codeSentAt = null;
      recoveryEmailState.codeExpiresAt = null;
      recoveryEmailState.resendCooldownRemainingSeconds = 0;
      recoveryEmailState.currentEmail = null;
      recoveryEmailState.isRequestInFlight = false;
      clearResendCooldownTimer();
      clearExpiryTimer();
      
      setTimeout(() => {
        updateRecoveryEmailUI();
        codeInput.value = '';
        const sendStatus = PC.getElement('recovery_email_send_status');
        if (sendStatus) sendStatus.textContent = '';
        statusEl.textContent = '';
      }, 2000);
    } catch (error) {
      statusEl.textContent = RECOVERY_EMAIL_I18N.networkErrorTryAgain;
      setRecoveryStatus(statusEl, 'error');
      setFieldErrorState(codeInput, 'recovery_email_code_error', RECOVERY_EMAIL_I18N.networkErrorTryAgain);
      verifyBtn.disabled = false;
      PW.error(error);
    }
  };

  const resendRecoveryEmailCode = async () => {
    const statusEl = PC.getElement('recovery_email_send_status');
    const csrfToken = getRecoveryEmailCsrfToken();

    if (!statusEl || !recoveryEmailState.currentEmail) return;
    if (!csrfToken) {
      statusEl.textContent = RECOVERY_EMAIL_I18N.securityTokenMissing;
      setRecoveryStatus(statusEl, 'error');
      return;
    }

    recoveryEmailState.isRequestInFlight = true;
    updateSendButtonState();
    statusEl.textContent = RECOVERY_EMAIL_I18N.resendingCode;
    setRecoveryStatus(statusEl, 'muted');

    try {
      const response = await fetch('/api/v1/account/recovery-email/resend', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          recovery_email: recoveryEmailState.currentEmail,
          csrf_token: csrfToken,
        }),
      });

      const data = await parseRecoveryResponse(response);

      if (!response.ok) {
        const errorMsg = data.error || data.message || 'Failed to resend code.';
        statusEl.textContent = errorMsg;
        setRecoveryStatus(statusEl, 'error');
        if ((response.status === 429 || response.status === 400) && Number.isFinite(Number(data.retry_after))) {
          startResendCooldown(Number(data.retry_after));
        }
        recoveryEmailState.isRequestInFlight = false;
        updateSendButtonState();
        return;
      }

      statusEl.textContent = RECOVERY_EMAIL_I18N.newCodeSent;
      setRecoveryStatus(statusEl, 'info');
      
      const ttlMinutes = data.expires_in_minutes || data.code_ttl_minutes || 10;
      const cooldownSeconds = data.resend_cooldown_seconds || 30;
      
      recoveryEmailState.codeSentAt = Date.now();
      startExpiryTimer(ttlMinutes);
      startResendCooldown(cooldownSeconds);
      recoveryEmailState.isRequestInFlight = false;
      updateSendButtonState();
      
      const codeInput = PC.getElement('recovery_email_code_input');
      if (codeInput) {
        codeInput.disabled = false;
        codeInput.value = '';
        const verifyBtn = PC.getElement('recovery_email_verify_btn');
        if (verifyBtn) verifyBtn.disabled = false;
        setTimeout(() => codeInput.focus(), 100);
      }
    } catch (error) {
      statusEl.textContent = RECOVERY_EMAIL_I18N.networkErrorTryAgain;
      setRecoveryStatus(statusEl, 'error');
      recoveryEmailState.isRequestInFlight = false;
      updateSendButtonState();
      PW.error(error);
    }
  };

  PC.addClickAndEnterListener('recovery_email_send_btn', (e) => {
    e.preventDefault();

    if (recoveryEmailState.isRequestInFlight) {
      return;
    }

    if (recoveryEmailState.codeSentAt === null) {
      sendRecoveryEmailCode();
      return;
    }

    if (recoveryEmailState.resendCooldownRemainingSeconds > 0) {
      return;
    }

    resendRecoveryEmailCode();
  });

  const recoveryEmailInputEl = PC.getElement('recovery_email_input');
  if (recoveryEmailInputEl) {
    recoveryEmailInputEl.addEventListener('input', () => {
      setFieldErrorState(recoveryEmailInputEl, 'recovery_email_input_error', '');
    });
  }

  const recoveryEmailCodeInputEl = PC.getElement('recovery_email_code_input');
  if (recoveryEmailCodeInputEl) {
    recoveryEmailCodeInputEl.addEventListener('input', () => {
      const normalized = normalizeVerificationCode(recoveryEmailCodeInputEl.value);
      if (recoveryEmailCodeInputEl.value !== normalized) {
        recoveryEmailCodeInputEl.value = normalized;
      }
      setFieldErrorState(recoveryEmailCodeInputEl, 'recovery_email_code_error', '');
    });
  }

  PC.addClickAndEnterListener('recovery_email_verify_btn', (e) => {
    e.preventDefault();
    verifyRecoveryEmailCode();
  });

  updateRecoveryEmailUI();

  PC.addClickAndEnterListener('call_signout_modal',          (e) => { e.preventDefault(); PC.openModal('modal_signout', <?php echo json_encode($i18n['SIGN_OUT'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>); });
  PC.addClickAndEnterListener('signout_cancel_btn',          (e) => { e.preventDefault(); PC.closeModal('modal_signout', <?php echo json_encode($i18n['SIGN_OUT'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>); });

  /* CALENDAR */
  handleRadioGroup('calendar_autofocus', 'settings/calendar', <?php echo json_encode($i18n['UPDATING_CALENDAR_AUTOFOCUS_TO'] . ' {value}', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);
  handleRadioGroup('calendar_day_name_format', 'settings/calendar', <?php echo json_encode($i18n['UPDATING_CALENDAR_DAY_NAME_FORMAT_TO'] . ' {value}', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);
  handleRadioGroup('calendar_day_name_position', 'settings/calendar', <?php echo json_encode($i18n['UPDATING_CALENDAR_DAY_NAME_POSITION_TO'] . ' {value}', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);
  handleRadioGroup('calendar_audio_labels', 'settings/calendar', <?php echo json_encode($i18n['UPDATING_CALENDAR_AUDIO_LABELS_TO'] . ' {value}', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);
  handleRadioGroup('calendar_date_label_position', 'settings/calendar', <?php echo json_encode($i18n['UPDATING_CALENDAR_DATE_LABEL_POSITION_TO'] . ' {value}', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);
  handleRadioGroup('calendar_work_entry_position', 'settings/calendar', <?php echo json_encode($i18n['UPDATING_CALENDAR_WORK_ENTRY_POSITION_TO'] . ' {value}', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);
  handleRadioGroup('calendar_week_start', 'settings/calendar', <?php echo json_encode($i18n['UPDATING_CALENDAR_WEEK_START_TO'] . ' {value}', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);
  handleRadioGroup('calendar_default_view', 'settings/calendar', <?php echo json_encode($i18n['UPDATING_CALENDAR_DEFAULT_VIEW_TO'] . ' {value}', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);

  handleRadioGroup('debug_console_enabled', 'settings/debug', <?php echo json_encode($i18n['SETTINGS_JS_DEBUG_CONSOLE_TOAST_FMT'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);
  handleRadioGroup('debug_fine_grained_enabled', 'settings/debug', <?php echo json_encode($i18n['SETTINGS_JS_DEBUG_FINE_GRAINED_TOAST_FMT'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);
  handleRadioGroup('debug_network_enabled', 'settings/debug', <?php echo json_encode($i18n['SETTINGS_JS_DEBUG_NETWORK_TOAST_FMT'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);
  handleRadioGroup('high_contrast_enabled', 'settings/style', <?php echo json_encode($i18n['SETTINGS_JS_HIGH_CONTRAST_UPDATED_FMT'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>, { reloadOnSuccess: true });
  handleRadioGroup('reduced_motion_enabled', 'settings/style', <?php echo json_encode($i18n['SETTINGS_JS_REDUCED_MOTION_UPDATED_FMT'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>, { reloadOnSuccess: true });
  handleRadioGroup('sr_verbosity', 'settings/style', <?php echo json_encode($i18n['SETTINGS_JS_SR_VERBOSITY_UPDATED_FMT'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);
  handleRadioGroup('keyboard_shortcuts_hint', 'settings/style', <?php echo json_encode($i18n['SETTINGS_JS_KEYBOARD_SHORTCUTS_HINT_UPDATED_FMT'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);

  const debugTtlMinutesSelect = document.getElementById('debug_ttl_minutes');
  if (debugTtlMinutesSelect instanceof HTMLSelectElement) {
    debugTtlMinutesSelect.addEventListener('change', () => {
      const formData = new FormData();
      formData.append('debug_ttl_minutes', debugTtlMinutesSelect.value);
      appendSettingsCsrfToken(formData);
      PC.updateResource('settings/debug', formData).then(() => {
        markSettingsAutosaveTargetSaved(debugTtlMinutesSelect);
        PC.showToast(SETTINGS_T.SETTINGS_JS_DEBUG_TTL_UPDATED, 'save', 3000, true);
      }).catch(error => PW.error(error));
    });
  }

  PC.addClickAndEnterListener('settings_show_keyboard_shortcuts_btn', (e) => {
    e.preventDefault();
    PC.openModal('modal_help', SETTINGS_T.KEYBOARD_SHORTCUTS || PC.config?.KEYBOARD_SHORTCUTS || 'Keyboard shortcuts');
  });

  ['debug_console_enabled', 'debug_fine_grained_enabled', 'debug_network_enabled'].forEach((fieldName) => {
    PC.queryAll(`input[name="${fieldName}"]`).forEach((input) => {
      input.addEventListener('change', broadcastDebugSettingsUpdate);
    });
  });

  broadcastDebugSettingsUpdate();

  // Handle work entry fields checkboxes
  PC.queryAll('input[name^="calendar_work_entry_fields_"]').forEach(checkbox => {
    checkbox.addEventListener('change', () => {
      const formData = new FormData();
      formData.append('calendar_work_entry_fields_hours', PC.query('input[name="calendar_work_entry_fields_hours"]')?.checked ? '1' : '0');
      formData.append('calendar_work_entry_fields_regular', PC.query('input[name="calendar_work_entry_fields_regular"]')?.checked ? '1' : '0');
      formData.append('calendar_work_entry_fields_overtime', PC.query('input[name="calendar_work_entry_fields_overtime"]')?.checked ? '1' : '0');
      formData.append('calendar_work_entry_fields_living_out', PC.query('input[name="calendar_work_entry_fields_living_out"]')?.checked ? '1' : '0');
      formData.append('calendar_work_entry_fields_travel', PC.query('input[name="calendar_work_entry_fields_travel"]')?.checked ? '1' : '0');

      appendSettingsCsrfToken(formData);

      PC.updateResource('settings/calendar', formData).then(() => {
        markSettingsAutosaveTargetSaved(checkbox);
        PC.showToast(SETTINGS_T.SETTINGS_JS_WORK_ENTRY_FIELDS_UPDATED, 'save');
      }).catch(error => PW.error(error));
    });
  });

  // Handle calendar display badge checkboxes
  PC.queryAll('input[name="calendar_show_gross_badge"], input[name="calendar_show_net_badge"], input[name="calendar_show_deductions_badge"], input[name="calendar_highlight_pay_period"]').forEach(checkbox => {
    checkbox.addEventListener('change', () => {
      const formData = new FormData();
      formData.append('calendar_show_gross_badge', PC.query('input[name="calendar_show_gross_badge"]')?.checked ? '1' : '0');
      formData.append('calendar_show_net_badge', PC.query('input[name="calendar_show_net_badge"]')?.checked ? '1' : '0');
      formData.append('calendar_show_deductions_badge', PC.query('input[name="calendar_show_deductions_badge"]')?.checked ? '1' : '0');
      formData.append('calendar_highlight_pay_period', PC.query('input[name="calendar_highlight_pay_period"]')?.checked ? '1' : '0');
      appendSettingsCsrfToken(formData);

      PC.updateResource('settings/calendar', formData).then(() => {
        markSettingsAutosaveTargetSaved(checkbox);
        PC.showToast(SETTINGS_T.SETTINGS_JS_CALENDAR_DISPLAY_UPDATED, 'save');
      }).catch(error => PW.error(error));
    });
  });

  // Security reauth / export encryption preference checkboxes
  PC.queryAll('input[name="require_reauth_export"], input[name="require_reauth_import"], input[name="export_encrypt_preference"]').forEach(checkbox => {
    checkbox.addEventListener('change', () => {
      const formData = new FormData();
      ['require_reauth_export', 'require_reauth_import', 'export_encrypt_preference'].forEach((fieldName) => {
        const input = PC.query(`input[name="${fieldName}"]`);
        if (input instanceof HTMLInputElement) {
          formData.append(fieldName, input.checked ? '1' : '0');
        }
      });
      appendSettingsCsrfToken(formData);

      PC.updateResource('account/info', formData).then(() => {
        PC.showToast(SETTINGS_T.SETTINGS_JS_SECURITY_PREF_UPDATED, 'save');
      }).catch(error => PW.error(error));
    });
  });


  /* AUDIO */
  const setVoicePickerAvailability = () => {
    const audioMode = PC.query('input[name="audio_feedback"]:checked')?.value || PC.state.audio_feedback;
    const isDisabled = audioMode === 'none';
    const voicePickerEl = PC.getElement('voice_picker');

    if (voicePickerEl) {
      voicePickerEl.classList.toggle('is-disabled', isDisabled);
      voicePickerEl.setAttribute('aria-disabled', isDisabled ? 'true' : 'false');
    }

    PC.queryAll('input[name="voice"]').forEach((voiceInput) => {
      voiceInput.disabled = isDisabled;
      voiceInput.setAttribute('aria-disabled', isDisabled ? 'true' : 'false');
    });
  };

  setVoicePickerAvailability();

  PC.queryAll('input[name="audio_feedback"]').forEach(radioButton => {
    radioButton.addEventListener('change', () => {
      const previousAudioFeedback = PC.state.audio_feedback;
      const checkedAudio = PC.query('input[name="audio_feedback"]:checked');
      if (!(checkedAudio instanceof HTMLInputElement)) {
        return;
      }

      PC.state.audio_feedback = checkedAudio.value;
      if (window.tts) {
        window.tts.audio_feedback = PC.state.audio_feedback;
      }
      setVoicePickerAvailability();
      const speakerIcon = PC.getElement('speaker_icon');
      if (speakerIcon) {
        if (PC.state.audio_feedback === 'none') {
          speakerIcon.classList.add('hidden');
        } else {
          speakerIcon.classList.remove('hidden');
        }
      }
      const formData = new FormData();
      formData.append('audio_feedback', PC.state.audio_feedback);
      appendSettingsCsrfToken(formData);
      PC.updateResource('settings/audio', formData).then(() => {
        markSettingsAutosaveTargetSaved(checkedAudio);
        const modeLabel = PC.state.audio_feedback === 'none'
          ? SETTINGS_T.SETTINGS_JS_AUDIO_MUTED
          : SETTINGS_T.SETTINGS_JS_AUDIO_ENABLED;
        PC.showToast(formatSettingsMessage(SETTINGS_T.SETTINGS_JS_AUDIO_TOAST_FMT, { mode: modeLabel }), 'save', 3000, true);
        if (previousAudioFeedback !== 'all' && PC.state.audio_feedback === 'all') {
          PC.textToSpeech(SETTINGS_T.SETTINGS_JS_AUDIO_ENABLED_SPEECH);
        }
      }).catch(error=> PW.error(error));
    });
  });

  /* VOICE PICKER */
  const saveVoiceSelection = (radioInput) => {
    if (!radioInput) {
      return;
    }

    if (PC.state.audio_feedback === 'none') {
      return;
    }

    const voice = radioInput.value;
    const voiceLabel = (radioInput.dataset.tts || '').trim() || radioInput.value;
    PC.state.voice = voice;
    if (window.TTS && typeof window.TTS.setVoice === 'function') {
      window.TTS.setVoice(voice);
    } else if (window.tts) {
      window.tts.voice = voice;
    }

    if (voice === 'choose') {
      return;
    }

    const formData = new FormData();
    formData.append('voice', voice);
    appendSettingsCsrfToken(formData);

    PC.updateResource('settings/audio', formData).then(() => {
      markSettingsAutosaveTargetSaved(radioInput);
      PC.showToast(formatSettingsMessage(SETTINGS_T.SETTINGS_JS_VOICE_UPDATED_FMT, { voice: voiceLabel }), 'save', 3000, true);
      if (PC.state.audio_feedback === 'all') {
        PC.textToSpeech(voiceLabel);
      }
    }).catch(error => PW.error(error));
  };

  PC.queryAll('input[name="voice"]').forEach((radioButton) => {
    radioButton.addEventListener('change', () => {
      saveVoiceSelection(radioButton);
    });
  });

  const voiceVolumeSlider = PC.getElement('voice_volume');
  const voiceVolumeOutput = PC.getElement('voice_volume_output');
  const applyVoiceVolume = (percent) => {
    const clamped = Math.max(0, Math.min(100, Number.parseInt(String(percent), 10) || 0));
    if (window.TTS && typeof window.TTS.setVolume === 'function') {
      window.TTS.setVolume(clamped / 100);
    } else if (window.tts) {
      window.tts.voice_volume = clamped / 100;
    }
    if (voiceVolumeOutput instanceof HTMLOutputElement) {
      voiceVolumeOutput.textContent = `${clamped}%`;
    }
  };
  const saveVoiceVolume = () => {
    if (!(voiceVolumeSlider instanceof HTMLInputElement)) {
      return;
    }
    const formData = new FormData();
    formData.append('voice_volume', voiceVolumeSlider.value);
    appendSettingsCsrfToken(formData);
    PC.updateResource('settings/audio', formData).then(() => {
      markSettingsAutosaveTargetSaved(voiceVolumeSlider);
      PC.showToast(SETTINGS_T.SETTINGS_JS_VOICE_VOLUME_UPDATED, 'save', 3000, true);
    }).catch(error => PW.error(error));
  };
  if (voiceVolumeSlider instanceof HTMLInputElement) {
    applyVoiceVolume(voiceVolumeSlider.value);
    voiceVolumeSlider.addEventListener('input', () => applyVoiceVolume(voiceVolumeSlider.value));
    voiceVolumeSlider.addEventListener('change', saveVoiceVolume);
  }

  const voicePreviewBtn = document.getElementById('settings_voice_preview_btn');
  if (voicePreviewBtn instanceof HTMLButtonElement) {
    voicePreviewBtn.addEventListener('click', () => {
      if (PC.state.audio_feedback === 'none') {
        return;
      }
      PC.textToSpeech(SETTINGS_T.SETTINGS_VOICE_PREVIEW_SAMPLE);
    });
  }

  /* STYLE */
  const themePicker = PC.getElement('theme_picker');
  const variantPicker = PC.getElement('variant_picker');
  const themeCards = Array.from(document.querySelectorAll('.settings_theme_card'));
  const modeOptions = Array.from(document.querySelectorAll('.settings_mode_option'));
  const selectedThemeLabel = document.getElementById('selected_theme_label');
  const languagePicker = PC.getElement('language_picker');
  const styleForm = PC.getElement('account_style_form');
  const textSlider = PC.getElement('text_slider');
  const spacingSlider = PC.getElement('spacing_slider');
  const textSliderValue = PC.getElement('text_slider_value');
  const spacingSliderValue = PC.getElement('spacing_slider_value');

  const lockChooseOption = (selectEl) => {
    if (!(selectEl instanceof HTMLSelectElement)) {
      return;
    }

    const chooseOption = Array.from(selectEl.options).find((option) => option.value === 'choose');
    if (!(chooseOption instanceof HTMLOptionElement)) {
      return;
    }

    if (selectEl.value !== 'choose') {
      chooseOption.disabled = true;
      chooseOption.hidden = true;
    }
  };

  lockChooseOption(themePicker);
  lockChooseOption(languagePicker);

  const TEXT_SIZE_BASE_REM = 1.125;
  const TEXT_SIZE_CLAMP_MIN_REM = 0.75;
  const SPACING_SIZE_BASE_REM = 1;
  const SPACING_SIZE_CLAMP_MIN_REM = 0.6;
  const SLIDER_STEP_REM = 0.125;
  const SLIDER_STEP_COUNT = 10;

  const clampSliderAdjustment = (value) => {
    const parsed = Number.parseInt(String(value), 10);
    if (!Number.isFinite(parsed)) {
      return 0;
    }

    return Math.max(-5, Math.min(5, parsed));
  };

  const getRootFontSizePx = () => {
    const rootSize = Number.parseFloat(getComputedStyle(document.documentElement).fontSize);
    return Number.isFinite(rootSize) && rootSize > 0 ? rootSize : 16;
  };

  // Per-step display scale: 11 unique px labels; center = baseline. CSS still uses rem + clamp.
  const toSliderStepDisplayPxLabel = (sliderValue, baseRem, clampMinRem) => {
    const normalized = clampSliderAdjustment(sliderValue);
    const rootPx = getRootFontSizePx();
    const minPx = Math.round(clampMinRem * rootPx);
    const baselinePx = Math.round(baseRem * rootPx);
    const maxPx = (2 * baselinePx) - minPx;
    const stepIndex = normalized + 5;
    const px = Math.round(minPx + (stepIndex * (maxPx - minPx)) / SLIDER_STEP_COUNT);

    return `${px}px`;
  };

  const toTextSizeLabel = (value) => toSliderStepDisplayPxLabel(value, TEXT_SIZE_BASE_REM, TEXT_SIZE_CLAMP_MIN_REM);

  const toSpacingSizeLabel = (value) => toSliderStepDisplayPxLabel(value, SPACING_SIZE_BASE_REM, SPACING_SIZE_CLAMP_MIN_REM);

  const applyRootScaleAdjustment = (group, value) => {
    const root = document.documentElement;
    const normalized = clampSliderAdjustment(value);
    if (group === 'text') {
      root.style.setProperty('--text-adjustment', `${(normalized * 0.125).toFixed(4)}rem`);
      return;
    }

    if (group === 'spacing') {
      root.style.setProperty('--spacing-adjustment', `${(normalized * 0.125).toFixed(4)}rem`);
    }
  };

  const applyRootAccessibilityPreference = (attributeName, value) => {
    document.documentElement.setAttribute(attributeName, value);
  };

  const refreshCoreStylesheet = () => {
    const coreStylesheet = document.querySelector('link[rel="stylesheet"][href*="/css/?"]');
    if (!coreStylesheet) {
      return;
    }

    const currentHref = coreStylesheet.getAttribute('href') || '';
    const separator = currentHref.includes('?') ? '&' : '?';
    const nextHref = currentHref.replace(/([?&])ts=\d+/, '$1ts=' + Date.now());
    coreStylesheet.setAttribute('href', nextHref === currentHref ? `${currentHref}${separator}ts=${Date.now()}` : nextHref);
  };

  function submitStyleChange() {
    if (!(themePicker instanceof HTMLInputElement) || !(variantPicker instanceof HTMLInputElement) || !(styleForm instanceof HTMLFormElement)) {
      return;
    }

    const theme = themePicker.value;
    const variant = variantPicker.value;
    if (theme !== 'choose' && variant) {
      lockChooseOption(themePicker);
      const formData = new FormData();
      formData.append('theme', theme);
      formData.append('variant', variant);
      // Add CSRF token
      const csrfValue = getSettingsCsrfToken();
      if (csrfValue !== '') formData.append('csrf_token', csrfValue);
      PC.updateResource('settings/style', formData).then(() => {
        markSettingsAutosaveTargetSaved(themePicker);
        PC.showToast(formatSettingsMessage(SETTINGS_T.SETTINGS_JS_THEME_UPDATED_FMT, { theme, variant }), 'save', 3000, true);
        if (PC.state.audio_feedback === 'all') {
          PC.textToSpeech(`${theme} ${variant}`);
        }
        refreshCoreStylesheet();
      }).catch(error => PW.error(error));
    }
  }

  const syncThemeCards = () => {
    if (!(themePicker instanceof HTMLInputElement)) {
      return;
    }

    themeCards.forEach(card => {
      if (!(card instanceof HTMLButtonElement)) {
        return;
      }
      const selected = card.dataset.themeValue === themePicker.value;
      card.classList.toggle('is-selected', selected);
      card.setAttribute('aria-pressed', selected ? 'true' : 'false');
      if (selected && selectedThemeLabel instanceof HTMLElement) {
        const label = card.dataset.label || card.textContent?.trim() || themePicker.value;
        const labelValue = selectedThemeLabel.querySelector('span');
        if (labelValue instanceof HTMLElement) {
          labelValue.textContent = label;
        }
      }
    });
  };

  const syncModeOptions = () => {
    if (!(variantPicker instanceof HTMLInputElement)) {
      return;
    }

    modeOptions.forEach(option => {
      if (!(option instanceof HTMLButtonElement)) {
        return;
      }
      const selected = option.dataset.variantValue === variantPicker.value;
      option.classList.toggle('is-selected', selected);
      option.setAttribute('aria-pressed', selected ? 'true' : 'false');
    });
  };

  themeCards.forEach(card => {
    if (!(card instanceof HTMLButtonElement)) {
      return;
    }
    card.addEventListener('click', () => {
      if (!(themePicker instanceof HTMLInputElement) || !card.dataset.themeValue) {
        return;
      }
      themePicker.value = card.dataset.themeValue;
      syncThemeCards();
      themePicker.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });

  modeOptions.forEach(option => {
    if (!(option instanceof HTMLButtonElement)) {
      return;
    }
    option.addEventListener('click', () => {
      if (!(variantPicker instanceof HTMLInputElement) || !option.dataset.variantValue) {
        return;
      }
      variantPicker.value = option.dataset.variantValue;
      syncModeOptions();
      variantPicker.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });

  themePicker?.addEventListener('change', submitStyleChange);
  themePicker?.addEventListener('change', syncThemeCards);
  variantPicker?.addEventListener('change', submitStyleChange);
  variantPicker?.addEventListener('change', syncModeOptions);
  syncThemeCards();
  syncModeOptions();
  if (languagePicker instanceof HTMLSelectElement) {
    languagePicker.addEventListener('change', () => {
      const language = languagePicker.value;
      if (language !== 'choose') {
        lockChooseOption(languagePicker);
        const formData = new FormData();
        formData.append('language', language);
        // Add CSRF token
        const csrfValue = getSettingsCsrfToken();
        if (csrfValue !== '') formData.append('csrf_token', csrfValue);
        PC.updateResource('settings/style', formData).then(() => {
          markSettingsAutosaveTargetSaved(languagePicker);
          const langName = PC.getLanguageName(language);
          PC.showToast(SETTINGS_T.SETTINGS_JS_LANGUAGE_UPDATED, 'save', 3000, true);
          if (PC.state.audio_feedback === 'all') {
            PC.textToSpeech(langName);
          }
          PC.delay(1).then(() => { window.location.reload(); });
        }).catch(error => PW.error(error));
      }
    });
  }

  const submitAccountLocaleField = (fieldName, fieldEl, toastMessage) => {
    if (!(fieldEl instanceof HTMLSelectElement)) {
      return;
    }

    const formData = new FormData();
    formData.append(fieldName, fieldEl.value);
    const csrfValue = getSettingsCsrfToken();
    if (csrfValue !== '') {
      formData.append('csrf_token', csrfValue);
    }

    PC.updateResource('account/info', formData).then(() => {
      markSettingsAutosaveTargetSaved(fieldEl);
      PC.showToast(toastMessage, 'save', 3000, true);
    }).catch(error => PW.error(error));
  };

  const accountTimezonePicker = PC.getElement('timezone_picker');
  if (accountTimezonePicker instanceof HTMLSelectElement) {
    accountTimezonePicker.addEventListener('change', () => {
      submitAccountLocaleField('timezone', accountTimezonePicker, SETTINGS_T.SETTINGS_JS_TIMEZONE_UPDATED);
    });
  }

  const currencyPicker = PC.getElement('currency_picker');
  if (currencyPicker instanceof HTMLSelectElement) {
    currencyPicker.addEventListener('change', () => {
      submitAccountLocaleField('currency', currencyPicker, SETTINGS_T.SETTINGS_JS_CURRENCY_UPDATED);
    });
  }

  const submitAccountWorkDefaultField = (fieldName, controlEl, toastMessage) => {
    if (!(controlEl instanceof HTMLElement)) {
      return;
    }

    const formData = new FormData();
    formData.append(fieldName, controlEl instanceof HTMLInputElement || controlEl instanceof HTMLSelectElement ? controlEl.value : '');
    const csrfValue = getSettingsCsrfToken();
    if (csrfValue !== '') {
      formData.append('csrf_token', csrfValue);
    }

    PC.updateResource('account/info', formData).then(() => {
      markSettingsAutosaveTargetSaved(controlEl);
      PC.showToast(toastMessage, 'save', 3000, true);
    }).catch(error => PW.error(error));
  };

  const defaultSitePicker = PC.getElement('default_site_id');
  if (defaultSitePicker instanceof HTMLSelectElement) {
    defaultSitePicker.addEventListener('change', () => {
      submitAccountWorkDefaultField('default_site_id', defaultSitePicker, SETTINGS_T.SETTINGS_JS_WORK_DEFAULTS_UPDATED);
    });
  }

  ['default_hours', 'default_living_out_allowance', 'default_travel_hours'].forEach((fieldId) => {
    const fieldEl = PC.getElement(fieldId);
    if (fieldEl instanceof HTMLInputElement) {
      fieldEl.addEventListener('change', () => {
        submitAccountWorkDefaultField(fieldId, fieldEl, SETTINGS_T.SETTINGS_JS_WORK_DEFAULTS_UPDATED);
      });
    }
  });

  const submitSliderPreference = (fieldName, sliderEl, valueEl, toastLabel, toSizeLabel) => {
    if (!(sliderEl instanceof HTMLInputElement)) {
      return;
    }

    const normalized = String(clampSliderAdjustment(sliderEl.value));
    const sizeLabel = toSizeLabel(sliderEl.value);
    if (valueEl) {
      valueEl.textContent = sizeLabel;
    }

    applyRootScaleAdjustment(fieldName, normalized);

    const formData = new FormData();
    formData.append(fieldName, normalized);
    const csrfValue = getSettingsCsrfToken();
    if (csrfValue !== '') formData.append('csrf_token', csrfValue);
    PC.updateResource('settings/style', formData).then(() => {
      markSettingsAutosaveTargetSaved(sliderEl);
      PC.showToast(`${toastLabel} updated`, 'save', 3000, true);
      if (PC.state.audio_feedback === 'all') {
        PC.textToSpeech(sizeLabel);
      }
    }).catch(error=> PW.error(error));
  };

  if (textSlider instanceof HTMLInputElement) {
    textSlider.addEventListener('input', () => {
      if (textSliderValue) {
        textSliderValue.textContent = toTextSizeLabel(textSlider.value);
      }
      applyRootScaleAdjustment('text', textSlider.value);
    });

    textSlider.addEventListener('change', () => {
      submitSliderPreference('text', textSlider, textSliderValue, 'Text size', toTextSizeLabel);
    });

    applyRootScaleAdjustment('text', textSlider.value);
    if (textSliderValue) {
      textSliderValue.textContent = toTextSizeLabel(textSlider.value);
    }
  }

  if (spacingSlider instanceof HTMLInputElement) {
    spacingSlider.addEventListener('input', () => {
      if (spacingSliderValue) {
        spacingSliderValue.textContent = toSpacingSizeLabel(spacingSlider.value);
      }
      applyRootScaleAdjustment('spacing', spacingSlider.value);
    });

    spacingSlider.addEventListener('change', () => {
      submitSliderPreference('spacing', spacingSlider, spacingSliderValue, 'Spacing', toSpacingSizeLabel);
    });

    applyRootScaleAdjustment('spacing', spacingSlider.value);
    if (spacingSliderValue) {
      spacingSliderValue.textContent = toSpacingSizeLabel(spacingSlider.value);
    }
  }

  const densityPresetMap = {
    density_preset_compact: { text: '-2', spacing: '-5', labelKey: 'compact' },
    density_preset_comfortable: { text: '0', spacing: '0', labelKey: 'comfortable' },
    density_preset_spacious: { text: '2', spacing: '5', labelKey: 'spacious' },
  };

  Object.entries(densityPresetMap).forEach(([elementId, preset]) => {
    const presetInput = document.getElementById(elementId);
    if (!(presetInput instanceof HTMLInputElement)) {
      return;
    }

    presetInput.addEventListener('change', () => {
      if (!presetInput.checked) {
        return;
      }

      if (textSlider instanceof HTMLInputElement) {
        textSlider.value = preset.text;
        applyRootScaleAdjustment('text', preset.text);
        if (textSliderValue) {
          textSliderValue.textContent = toTextSizeLabel(preset.text);
        }
      }

      if (spacingSlider instanceof HTMLInputElement) {
        spacingSlider.value = preset.spacing;
        applyRootScaleAdjustment('spacing', preset.spacing);
        if (spacingSliderValue) {
          spacingSliderValue.textContent = toSpacingSizeLabel(preset.spacing);
        }
      }

      const formData = new FormData();
      formData.append('text', preset.text);
      formData.append('spacing', preset.spacing);
      appendSettingsCsrfToken(formData);

      const spokenLabel = document.querySelector(`label[for="${elementId}"]`)?.textContent?.trim() || preset.labelKey;
      PC.updateResource('settings/style', formData).then(() => {
        markSettingsAutosaveTargetSaved(presetInput);
        PC.showToast(formatSettingsMessage(SETTINGS_T.SETTINGS_JS_DENSITY_UPDATED_FMT, { label: spokenLabel }), 'save', 3000, true);
        if (PC.state.audio_feedback === 'all') {
          PC.textToSpeech(spokenLabel);
        }
      }).catch(error => PW.error(error));
    });
  });

  Array.from(document.querySelectorAll('input[name="depth"]')).forEach(depthInput => {
    if (!(depthInput instanceof HTMLInputElement)) {
      return;
    }

    depthInput.addEventListener('change', () => {
      if (!depthInput.checked) {
        return;
      }

      const depth = depthInput.value;
      const previousDepth = document.documentElement.getAttribute('data-depth') || 'standard';
      const label = document.querySelector(`label[for="${depthInput.id}"]`)?.textContent?.trim() || depth;
      document.documentElement.setAttribute('data-depth', depth);

      const formData = new FormData();
      formData.append('depth', depth);
      appendSettingsCsrfToken(formData);

      PC.updateResource('settings/style', formData).then(() => {
        markSettingsAutosaveTargetSaved(depthInput);
        PC.showToast(formatSettingsMessage(SETTINGS_T.SETTINGS_JS_DEPTH_UPDATED_FMT, { label }), 'save', 3000, true);
        if (PC.state.audio_feedback === 'all') {
          PC.textToSpeech(label);
        }
      }).catch(error => {
        document.documentElement.setAttribute('data-depth', previousDepth);
        PW.error(error);
      });
    });
  });

  const accentPresetInput = document.getElementById('accent_preset');
  const accentPresetSwatches = document.getElementById('accent_preset_swatches');
  const accentPresetPreviewLabel = document.getElementById('accent_preset_preview_label');
  if (accentPresetInput instanceof HTMLInputElement && accentPresetSwatches instanceof HTMLElement) {
    const updateAccentLabel = swatch => {
      if (!(accentPresetPreviewLabel instanceof HTMLElement) || !(swatch instanceof HTMLElement)) { return; }
      const label = swatch.dataset.label || swatch.dataset.preset || '';
      accentPresetPreviewLabel.textContent = label;
    };
    const restoreSelectedAccentLabel = () => {
      const selected = accentPresetSwatches.querySelector('.settings_accent_swatch.is-selected');
      if (selected instanceof HTMLElement) updateAccentLabel(selected);
    };
    accentPresetSwatches.addEventListener('mouseover', event => {
      const swatch = event.target instanceof Element ? event.target.closest('.settings_accent_swatch') : null;
      if (swatch instanceof HTMLElement) updateAccentLabel(swatch);
    });
    accentPresetSwatches.addEventListener('focusin', event => {
      const swatch = event.target instanceof Element ? event.target.closest('.settings_accent_swatch') : null;
      if (swatch instanceof HTMLElement) updateAccentLabel(swatch);
    });
    accentPresetSwatches.addEventListener('mouseleave', restoreSelectedAccentLabel);
    accentPresetSwatches.addEventListener('focusout', () => {
      window.setTimeout(restoreSelectedAccentLabel, 0);
    });
    accentPresetSwatches.addEventListener('click', event => {
      const swatch = event.target instanceof Element ? event.target.closest('.settings_accent_swatch') : null;
      if (!(swatch instanceof HTMLElement) || !swatch.dataset.preset) { return; }
      const preset = swatch.dataset.preset;
      const label = swatch.dataset.label || preset;
      const previousPreset = accentPresetInput.value || document.documentElement.getAttribute('data-accent-preset') || '';
      accentPresetInput.value = preset;
      accentPresetSwatches.querySelectorAll('.settings_accent_swatch').forEach(option => {
        const selected = option === swatch;
        option.classList.toggle('is-selected', selected);
        option.setAttribute('aria-pressed', selected ? 'true' : 'false');
      });
      updateAccentLabel(swatch);
      document.documentElement.setAttribute('data-accent-preset', preset);
      const formData = new FormData();
      formData.append('accent_preset', preset);
      appendSettingsCsrfToken(formData);

      PC.updateResource('settings/style', formData).then(() => {
        markSettingsAutosaveTargetSaved(swatch);
        PC.showToast(formatSettingsMessage(SETTINGS_T.SETTINGS_JS_ACCENT_UPDATED_FMT, { preset: label }), 'save', 3000, true);
        refreshCoreStylesheet();
      }).catch(error => {
        if (previousPreset !== '') {
          document.documentElement.setAttribute('data-accent-preset', previousPreset);
        }
        PW.error(error);
      });
    });
  }

  Array.from(PC.queryAll('input[name="dyslexia_typography"]')).forEach(radioButton => {
    radioButton.addEventListener('change', function() {
      const preference = PC.query('input[name="dyslexia_typography"]:checked').value;
      const checkedRadio = PC.query('input[name="dyslexia_typography"]:checked');
      const label = checkedRadio ? document.querySelector(`label[for="${checkedRadio.id}"]`) : null;
      const spokenLabel = label ? label.textContent.trim() : preference;
      const formData = new FormData();
      formData.append('dyslexia_typography', preference);
      const csrfValue = getSettingsCsrfToken();
      if (csrfValue !== '') formData.append('csrf_token', csrfValue);
      PC.updateResource('settings/style', formData).then(() => {
        markSettingsAutosaveTargetSaved(checkedRadio);
        PC.showToast(SETTINGS_T.SETTINGS_JS_TYPOGRAPHY_UPDATED, 'save', 3000, true);
        if (PC.state.audio_feedback === 'all') {
          PC.textToSpeech(spokenLabel);
        }
        applyRootAccessibilityPreference('data-a11y-dyslexia-typography', preference);
      }).catch(error=> PW.error(error));
    });
  });

  const helpPopupTimeoutSlider = PC.getElement('help_popup_timeout_seconds');
  const helpPopupTimeoutOutput = PC.getElement('help_popup_timeout_seconds_output');
  const formatHelpPopupTimeoutLabel = (seconds) => `${Number(seconds) || 0}s`;

  if (helpPopupTimeoutSlider instanceof HTMLInputElement) {
    const updateHelpPopupTimeoutDisplay = () => {
      const timeoutSeconds = parseInt(helpPopupTimeoutSlider.value, 10) || 0;
      if (helpPopupTimeoutOutput) {
        helpPopupTimeoutOutput.value = formatHelpPopupTimeoutLabel(timeoutSeconds);
      }
      helpPopupTimeoutSlider.setAttribute('aria-valuenow', String(timeoutSeconds));
    };

    updateHelpPopupTimeoutDisplay();

    helpPopupTimeoutSlider.addEventListener('input', updateHelpPopupTimeoutDisplay);

    helpPopupTimeoutSlider.addEventListener('change', () => {
      const timeoutSeconds = helpPopupTimeoutSlider.value;
      const formData = new FormData();
      formData.append('help_popup_timeout_seconds', timeoutSeconds);
      const csrfValue = getSettingsCsrfToken();
      if (csrfValue !== '') formData.append('csrf_token', csrfValue);
      PC.updateResource('settings/style', formData).then(() => {
        markSettingsAutosaveTargetSaved(helpPopupTimeoutSlider);
        PC.config.help_popup_timeout_seconds = Number(timeoutSeconds) || 0;
        PC.showToast(SETTINGS_T.SETTINGS_JS_HELP_POPUP_TIMEOUT_UPDATED, 'save', 3000, true);
        if (PC.state.audio_feedback === 'all') {
          PC.textToSpeech(formatHelpPopupTimeoutLabel(timeoutSeconds));
        }
      }).catch(error => PW.error(error));
    });
  }

  const submitStylePreference = (fields, onSuccess) => {
    const formData = new FormData();
    Object.entries(fields).forEach(([key, value]) => {
      formData.append(key, String(value));
    });
    const csrfValue = getSettingsCsrfToken();
    if (csrfValue !== '') {
      formData.append('csrf_token', csrfValue);
    }

    return PC.updateResource('settings/style', formData).then(() => {
      markSettingsAutosaveTargetSaved();
      if (typeof onSuccess === 'function') {
        onSuccess();
      }
    }).catch(error => PW.error(error));
  };

  const applyToastRuntimePreferences = ({ position, fontSize } = {}) => {
    if (typeof position === 'string' && position !== '') {
      document.body.setAttribute('data-toast-position', position);
      PC.config.toast_position = position;
    }
    if (Number.isFinite(Number(fontSize))) {
      const normalized = clampSliderAdjustment(fontSize);
      document.body.setAttribute('data-toast-font-size', String(normalized));
      PC.config.toast_font_size = normalized;
    }
  };

  const submitNavPositionPreference = (fieldName, attributeName, statusLabel) => {
    const checked = PC.query(`input[name="${fieldName}"]:checked`);
    if (!checked) {
      return;
    }

    const value = checked.value;
    const formData = new FormData();
    formData.append(fieldName, value);

    const csrfInput = styleForm instanceof HTMLFormElement
      ? styleForm.querySelector('input[name="csrf_token"]')
      : null;
    const csrfValue = csrfInput instanceof HTMLInputElement && csrfInput.value !== ''
      ? csrfInput.value
      : getSettingsCsrfToken();
    if (csrfValue !== '') {
      formData.append('csrf_token', csrfValue);
    }

    PC.updateResource('settings/style', formData).then(() => {
      PC.showToast(statusLabel, 'save', 3000, true);
      document.body.setAttribute(attributeName, value);
      if (PC.state.audio_feedback === 'all') {
        PC.textToSpeech(value);
      }
    }).catch(error => PW.error(error));
  };

  Array.from(PC.queryAll('input[name="nav_position_primary"]')).forEach(radioButton => {
    radioButton.addEventListener('change', () => {
      submitNavPositionPreference('nav_position_primary', 'data-nav-primary-position', 'Sidebar updated');
    });
  });

  const sidebarForm = PC.getElement('account_sidebar_form');
  const getSidebarCsrfToken = () => {
    const csrfInput = sidebarForm instanceof HTMLFormElement
      ? sidebarForm.querySelector('input[name="csrf_token"]')
      : null;
    return csrfInput instanceof HTMLInputElement && csrfInput.value !== ''
      ? csrfInput.value
      : getSettingsCsrfToken();
  };

  const submitSidebarPreference = (fields, toastMessage, runtimeApply) => {
    const formData = new FormData();
    Object.entries(fields).forEach(([key, value]) => {
      formData.append(key, String(value));
    });
    const csrfValue = getSidebarCsrfToken();
    if (csrfValue !== '') {
      formData.append('csrf_token', csrfValue);
    }

    PC.updateResource('settings/style', formData).then(() => {
      if (typeof runtimeApply === 'function') {
        runtimeApply();
      }
      Object.entries(fields).forEach(([key, value]) => {
        if (Object.prototype.hasOwnProperty.call(PC.config, key)) {
          PC.config[key] = value;
        }
      });
      PC.showToast(toastMessage, 'save', 3000, true);
    }).catch(error => PW.error(error));
  };

  /* Sidebar proximity hover toggle (server persisted) */
  (() => {
    const navToggle = (typeof window !== 'undefined' && window.NavToggle) ? window.NavToggle : null;
    const onRadio  = PC.query('#nav_proximity_on');
    const offRadio = PC.query('#nav_proximity_off');
    const distanceSlider = PC.query('#nav_proximity_px');
    const triggerSlider = PC.query('#nav_proximity_delay_ms');
    if (!onRadio || !offRadio) return;

    const syncProximityControlsEnabled = (enabled) => {
      if (distanceSlider instanceof HTMLInputElement) {
        distanceSlider.disabled = !enabled;
      }
      if (triggerSlider instanceof HTMLInputElement) {
        triggerSlider.disabled = !enabled;
      }
    };

    syncProximityControlsEnabled(onRadio.checked);

    [onRadio, offRadio].forEach(radio => {
      radio.addEventListener('change', () => {
        const enabled = onRadio.checked;
        const value = enabled ? 'on' : 'off';
        submitSidebarPreference({ nav_proximity: value }, formatSettingsMessage(SETTINGS_T.SETTINGS_JS_PROXIMITY_FMT, { state: enabled ? SETTINGS_T.SETTINGS_JS_TOGGLE_ON : SETTINGS_T.SETTINGS_JS_TOGGLE_OFF }), () => {
          if (navToggle) navToggle.setProximityEnabled(enabled);
          syncProximityControlsEnabled(enabled);
        });
      });
    });
  })();

  /* Sidebar proximity trigger-distance slider (server persisted) */
  (() => {
    const navToggle = (typeof window !== 'undefined' && window.NavToggle) ? window.NavToggle : null;
    const slider   = PC.query('#nav_proximity_px');
    const output   = PC.query('#nav_proximity_px_output');
    const onRadio  = PC.query('#nav_proximity_on');
    if (!slider || !output) return;

    slider.addEventListener('input', () => {
      const px = parseInt(slider.value, 10) || 0;
      output.value = `${px} px`;
      if (navToggle) navToggle.setProximityPx(px);
    });

    slider.addEventListener('change', () => {
      const px = parseInt(slider.value, 10) || 0;
      submitSidebarPreference({ nav_proximity_px: px }, SETTINGS_T.SETTINGS_JS_NAV_DISTANCE_UPDATED, () => {
        if (navToggle) navToggle.setProximityPx(px);
      });
    });

    if (slider instanceof HTMLInputElement && onRadio instanceof HTMLInputElement && !onRadio.checked) {
      slider.disabled = true;
    }
  })();

  /* Sidebar proximity trigger-delay slider (server persisted) */
  (() => {
    const navToggle = (typeof window !== 'undefined' && window.NavToggle) ? window.NavToggle : null;
    const slider   = PC.query('#nav_proximity_delay_ms');
    const output   = PC.query('#nav_proximity_delay_ms_output');
    const onRadio  = PC.query('#nav_proximity_on');
    if (!slider || !output) return;

    slider.addEventListener('input', () => {
      const delayMs = parseInt(slider.value, 10) || 400;
      output.value = `${delayMs} ms`;
      slider.setAttribute('aria-valuenow', String(delayMs));
      if (navToggle) navToggle.setProximityDelayMs(delayMs);
    });

    slider.addEventListener('change', () => {
      const delayMs = parseInt(slider.value, 10) || 400;
      submitSidebarPreference({ nav_proximity_delay_ms: delayMs }, SETTINGS_T.SETTINGS_JS_NAV_TRIGGER_UPDATED, () => {
        if (navToggle) navToggle.setProximityDelayMs(delayMs);
      });
    });

    if (slider instanceof HTMLInputElement && onRadio instanceof HTMLInputElement && !onRadio.checked) {
      slider.disabled = true;
    }
  })();

  /* Sidebar overlay-vs-push toggle (server persisted) */
  (() => {
    const navToggle      = (typeof window !== 'undefined' && window.NavToggle) ? window.NavToggle : null;
    const pushRadio      = PC.query('#nav_overlay_push');
    const overlayRadio   = PC.query('#nav_overlay_overlay');
    const collapseSlider = PC.query('#overlay_sidebar_timeout_seconds');
    if (!pushRadio || !overlayRadio) return;

    const syncOverlayCollapseEnabled = (overlayOn) => {
      if (collapseSlider instanceof HTMLInputElement) {
        collapseSlider.disabled = !overlayOn;
      }
    };

    syncOverlayCollapseEnabled(overlayRadio.checked);

    [pushRadio, overlayRadio].forEach(radio => {
      radio.addEventListener('change', () => {
        const overlay = overlayRadio.checked;
        const value = overlay ? 'overlay' : 'push';
        submitSidebarPreference({ nav_overlay: value }, formatSettingsMessage(SETTINGS_T.SETTINGS_JS_OVERLAY_FMT, { state: overlay ? SETTINGS_T.SETTINGS_JS_TOGGLE_ON : SETTINGS_T.SETTINGS_JS_TOGGLE_OFF }), () => {
          if (navToggle) navToggle.setOverlayMode(overlay);
          syncOverlayCollapseEnabled(overlay);
        });
      });
    });
  })();

  const overlaySidebarTimeoutSlider = PC.getElement('overlay_sidebar_timeout_seconds');
  const overlaySidebarTimeoutOutput = PC.getElement('overlay_sidebar_timeout_seconds_output');
  const formatOverlaySidebarTimeoutLabel = (seconds) => `${Number(seconds) || 0}s`;

  if (overlaySidebarTimeoutSlider instanceof HTMLInputElement) {
    const navToggle = (typeof window !== 'undefined' && window.NavToggle) ? window.NavToggle : null;

    const updateOverlaySidebarTimeoutDisplay = () => {
      const timeoutSeconds = parseInt(overlaySidebarTimeoutSlider.value, 10) || 0;
      if (overlaySidebarTimeoutOutput) {
        overlaySidebarTimeoutOutput.value = formatOverlaySidebarTimeoutLabel(timeoutSeconds);
      }
      overlaySidebarTimeoutSlider.setAttribute('aria-valuenow', String(timeoutSeconds));
    };

    updateOverlaySidebarTimeoutDisplay();

    overlaySidebarTimeoutSlider.addEventListener('input', updateOverlaySidebarTimeoutDisplay);

    overlaySidebarTimeoutSlider.addEventListener('change', () => {
      const timeoutSeconds = overlaySidebarTimeoutSlider.value;
      submitSidebarPreference({ overlay_sidebar_timeout_seconds: timeoutSeconds }, SETTINGS_T.SETTINGS_JS_OVERLAY_COLLAPSE_UPDATED, () => {
        const numericTimeout = Number(timeoutSeconds) || 0;
        PC.config.overlay_sidebar_timeout_seconds = numericTimeout;
        if (navToggle) navToggle.setOverlaySidebarTimeout(numericTimeout);
      });
      if (PC.state.audio_feedback === 'all') {
        PC.textToSpeech(formatOverlaySidebarTimeoutLabel(timeoutSeconds));
      }
    });
  }

  Array.from(PC.queryAll('input[name="toast_position"]')).forEach(radioButton => {
    radioButton.addEventListener('change', () => {
      const checked = PC.query('input[name="toast_position"]:checked');
      if (!(checked instanceof HTMLInputElement)) {
        return;
      }
      submitStylePreference({ toast_position: checked.value }, () => {
        applyToastRuntimePreferences({ position: checked.value });
        PC.showToast(SETTINGS_T.SETTINGS_JS_TOAST_POSITION_UPDATED, 'save', 3000, true);
      });
    });
  });

  const toastFontSizeSlider = PC.getElement('toast_font_size');
  const toastFontSizeOutput = PC.getElement('toast_font_size_output');

  if (toastFontSizeSlider instanceof HTMLInputElement) {
    const updateToastFontSizeDisplay = () => {
      const normalized = clampSliderAdjustment(toastFontSizeSlider.value);
      if (toastFontSizeOutput) {
        toastFontSizeOutput.value = toTextSizeLabel(normalized);
      }
      toastFontSizeSlider.setAttribute('aria-valuenow', String(normalized));
      applyToastRuntimePreferences({ fontSize: normalized });
    };

    updateToastFontSizeDisplay();
    toastFontSizeSlider.addEventListener('input', updateToastFontSizeDisplay);
    toastFontSizeSlider.addEventListener('change', () => {
      const fontSize = clampSliderAdjustment(toastFontSizeSlider.value);
      submitStylePreference({ toast_font_size: fontSize }, () => {
        applyToastRuntimePreferences({ fontSize });
        PC.showToast(SETTINGS_T.SETTINGS_JS_TOAST_SIZE_UPDATED, 'save', 3000, true);
        if (PC.state.audio_feedback === 'all') {
          PC.textToSpeech(toTextSizeLabel(fontSize));
        }
      });
    });
  }


  /* PAY PERIODS */
  const collectPayPeriodDebugPayload = () => ({
    pay_period_start: getPayControl('pay_period_start')?.value || '',
    pay_frequency: getPayControl('pay_frequency')?.value || '',
    pay_anchor: getPayControl('pay_anchor')?.value || '',
    editing_grace_days: getPayControl('editing_grace_days')?.value || '',
  });
  /**
   * Submit pay-period settings and report outcome to the user.
   * Uses shared updateResource route to keep behavior consistent across settings pages.
   */
  const submitPayPeriodSettings = () => {
    const debugPayload = collectPayPeriodDebugPayload();
    debugLog('[PAYPERIOD_DEBUG] submitPayPeriodSettings payload', debugPayload);
    PC.updateResource('settings/pay_period', 'settings_pay_period_form').then(() => {
      debugLog('[PAYPERIOD_DEBUG] submitPayPeriodSettings success', debugPayload);
      PC.showToast(<?php echo json_encode($i18n['UPDATING_PAY_PERIOD'] . '...', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>, 'save');
    }).catch(error => {
      debugLog('[PAYPERIOD_DEBUG] submitPayPeriodSettings failed', {
        message: error?.message || String(error),
        payload: debugPayload,
      });
      PW.error(error);
    });
  };
  const getPayControl = (id) => document.getElementById(id);
  let payPeriodPreviewWatchId = null;
  let lastPayPeriodSnapshot = '';
  const payPeriodCurrentPreview = document.getElementById('pay_period_current_preview');
  const payPeriodCurrentCalendar = document.getElementById('pay_period_current_calendar');
  const payPeriodPreviewSummary = document.getElementById('pay_period_preview_summary');
  const payPeriodPreviewCalendar = document.getElementById('pay_period_preview_calendar');
  const payPeriodLocale = PC?.config?.USER_LOCALE || document.documentElement.lang || undefined;
  const weekdayFullNames = <?php echo json_encode([
    Strings::i18n('WEEKDAY_SUNDAY'),
    Strings::i18n('WEEKDAY_MONDAY'),
    Strings::i18n('WEEKDAY_TUESDAY'),
    Strings::i18n('WEEKDAY_WEDNESDAY'),
    Strings::i18n('WEEKDAY_THURSDAY'),
    Strings::i18n('WEEKDAY_FRIDAY'),
    Strings::i18n('WEEKDAY_SATURDAY'),
  ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>;
  const payPeriodDayNames = buildPayPeriodDayNames(payPeriodLocale, weekdayFullNames);
  const syncPayPeriodEditorStart = () => {
    const startControl = getPayControl('pay_period_start');
    if (!startControl) {
      return;
    }

    const startRaw = startControl.value;
    const frequency = getPayControl('pay_frequency')?.value || 'biweekly';
    const anchor = getPayControl('pay_anchor')?.value || 'Monday';

    if (!startRaw) {
      return;
    }

    const resolved = buildPayPeriodCurrentRange({
      startYmd: startRaw,
      frequency,
      anchor,
      alignBiweeklyToAnchor: true,
      rollBiweeklyToToday: true,
    });
    startControl.value = payPeriodFormatYmd(resolved.start);
  };
  const renderPreview = () => {
    const startRaw = getPayControl('pay_period_start')?.value;
    const frequency = getPayControl('pay_frequency')?.value || 'biweekly';
    const anchor = getPayControl('pay_anchor')?.value || 'Monday';
    const graceDays = parseInt(getPayControl('editing_grace_days')?.value || '0', 10);
    if (!startRaw) {
      if (payPeriodPreviewSummary) payPeriodPreviewSummary.textContent = '';
      if (payPeriodPreviewCalendar) payPeriodPreviewCalendar.textContent = '';
      if (payPeriodCurrentCalendar) payPeriodCurrentCalendar.textContent = '';
      if (payPeriodCurrentPreview) payPeriodCurrentPreview.textContent = '';
      return;
    }
    const preview = buildPayPeriodPreviewState({
      startYmd: startRaw,
      frequency,
      anchor,
      graceDays,
      dayNames: payPeriodDayNames,
      locale: payPeriodLocale,
      alignBiweeklyToAnchor: true,
      rollBiweeklyToToday: true,
      calendarOptions: {
        clampRibbonToWeek: true,
      },
    });
    if (payPeriodPreviewSummary) {
      payPeriodPreviewSummary.textContent = preview.summary;
    }
    if (payPeriodPreviewCalendar) {
      PC.setHTML(payPeriodPreviewCalendar, preview.html);
    }
    if (payPeriodCurrentPreview) {
      payPeriodCurrentPreview.textContent = preview.summary;
    }
    if (payPeriodCurrentCalendar) {
      PC.setHTML(payPeriodCurrentCalendar, preview.html);
    }
  };
  const getPayPeriodSnapshot = () => {
    const start = getPayControl('pay_period_start')?.value || '';
    const frequency = getPayControl('pay_frequency')?.value || '';
    const anchor = getPayControl('pay_anchor')?.value || '';
    const grace = getPayControl('editing_grace_days')?.value || '';
    return `${start}|${frequency}|${anchor}|${grace}`;
  };
  const startPayPeriodPreviewWatch = () => {
    if (payPeriodPreviewWatchId !== null) return;
    payPeriodPreviewWatchId = window.setInterval(() => {
      const snapshot = getPayPeriodSnapshot();
      if (snapshot !== lastPayPeriodSnapshot) {
        lastPayPeriodSnapshot = snapshot;
        renderPreview();
      }
    }, 120);
  };
  const stopPayPeriodPreviewWatch = () => {
    if (payPeriodPreviewWatchId === null) return;
    window.clearInterval(payPeriodPreviewWatchId);
    payPeriodPreviewWatchId = null;
  };
  ['pay_period_start', 'pay_frequency', 'pay_anchor', 'editing_grace_days'].forEach((id) => {
    const el = getPayControl(id);
    if (el) {
      const update = () => {
        lastPayPeriodSnapshot = getPayPeriodSnapshot();
        renderPreview();
      };
      el.addEventListener('change', update);
      el.addEventListener('input', update);
      el.addEventListener('keyup', update);
      el.addEventListener('click', update);
    }
  });
  const generateButton = document.getElementById('pay_period_generate');
  if (generateButton) {
    generateButton.addEventListener('click', () => {
      syncPayPeriodEditorStart();
      renderPreview();
      lastPayPeriodSnapshot = getPayPeriodSnapshot();
      startPayPeriodPreviewWatch();
      PC.openModal('modal_pay_period_preview', 'Pay Period');
    });
  }
  const previewCancel = document.getElementById('pay_period_preview_cancel');
  if (previewCancel) {
    previewCancel.addEventListener('click', () => {
      stopPayPeriodPreviewWatch();
      PC.closeModal('modal_pay_period_preview', 'Pay Period');
    });
  }
  const previewApply = document.getElementById('pay_period_preview_apply');
  if (previewApply) {
    previewApply.addEventListener('click', () => {
      submitPayPeriodSettings();
      stopPayPeriodPreviewWatch();
      PC.closeModal('modal_pay_period_preview', 'Pay Period');
    });
  }
  lastPayPeriodSnapshot = getPayPeriodSnapshot();
  renderPreview();


  /* SECURITY SETTINGS */
  const securityFormEl = PC.getElement('account_security_timeout_form');
  const securitySliderEl = PC.getElement('security_level_slider');
  const securityLevelValueEl = PC.getElement('security_level_value');
  const securityLevelHintEl = PC.getElement('security_level_hint');
  const emergencySignoutSliderEl = PC.getElement('emergency_signout_window_ms');
  const emergencySignoutValueEl = PC.getElement('emergency_signout_window_ms_value');
  const securityStartTs = Date.now();

  const securitySelects = {
    session_timeout: PC.getElement('session_timeout'),
    form_ttl_settings: PC.getElement('form_ttl_settings'),
    form_ttl_calendar: PC.getElement('form_ttl_calendar'),
    form_ttl_general: PC.getElement('form_ttl_general'),
  };

  const securityPresets = {
    relaxed: {
      label: 'Relaxed',
      hint: 'Longer sessions and fewer interruptions.',
      slider: 0,
      values: {
        session_timeout: '7200',
        form_ttl_settings: '1800',
        form_ttl_calendar: '7200',
        form_ttl_general: '3600',
      },
    },
    balanced: {
      label: 'Balanced',
      hint: 'Recommended for most users.',
      slider: 50,
      values: {
        session_timeout: '3600',
        form_ttl_settings: '900',
        form_ttl_calendar: '3600',
        form_ttl_general: '1800',
      },
    },
    high: {
      label: 'High Security',
      hint: 'Short sessions and stronger protection.',
      slider: 100,
      values: {
        session_timeout: '900',
        form_ttl_settings: '300',
        form_ttl_calendar: '1800',
        form_ttl_general: '600',
      },
    },
  };

  const formatSeconds = (seconds) => {
    const total = Math.max(0, Number(seconds) || 0);
    const mins = Math.floor(total / 60);
    const hrs = Math.floor(mins / 60);
    if (hrs >= 1) {
      const remMins = mins % 60;
      return remMins > 0 ? `${hrs}h ${remMins}m` : `${hrs}h`;
    }
    return `${mins}m`;
  };

  const formatTimeoutLabel = (seconds) => {
    const secs = Number(seconds) || 0;
    if (secs >= 3600 && secs % 3600 === 0) {
      const h = secs / 3600;
      return `${h} hour${h === 1 ? '' : 's'}`;
    }
    return `${Math.floor(secs / 60)} minutes`;
  };

  const getCurrentSecurityValues = () => ({
    session_timeout: securitySelects.session_timeout?.value || '3600',
    form_ttl_settings: securitySelects.form_ttl_settings?.value || '3600',
    form_ttl_calendar: securitySelects.form_ttl_calendar?.value || '3600',
    form_ttl_general: securitySelects.form_ttl_general?.value || '3600',
  });

  const emitSecurityTimeoutUpdate = (values) => {
    const payload = values || getCurrentSecurityValues();
    window.dispatchEvent(new CustomEvent('paycal:security-timeouts-updated', {
      detail: {
        session_timeout_seconds: Number(payload.session_timeout) || 0,
        form_ttl_settings_seconds: Number(payload.form_ttl_settings) || 0,
        form_ttl_calendar_seconds: Number(payload.form_ttl_calendar) || 0,
        form_ttl_general_seconds: Number(payload.form_ttl_general) || 0,
        emergency_signout_window_ms: Number(emergencySignoutSliderEl?.value || 600) || 600,
      },
    }));
  };

  const presetForValues = (values) => {
    const names = Object.keys(securityPresets);
    for (const name of names) {
      const candidate = securityPresets[name].values;
      if (
        values.session_timeout === candidate.session_timeout
        && values.form_ttl_settings === candidate.form_ttl_settings
        && values.form_ttl_calendar === candidate.form_ttl_calendar
        && values.form_ttl_general === candidate.form_ttl_general
      ) {
        return name;
      }
    }
    return 'custom';
  };

  const setSecurityText = (presetName) => {
    if (!securityLevelValueEl || !securityLevelHintEl || !securitySliderEl) return;

    if (presetName in securityPresets) {
      const preset = securityPresets[presetName];
      securityLevelValueEl.textContent = preset.label;
      securityLevelHintEl.textContent = `${preset.hint} ${SETTINGS_T.SETTINGS_JS_SECURITY_HINT_SUFFIX}`;
      securitySliderEl.value = String(preset.slider);
    } else {
      securityLevelValueEl.textContent = SETTINGS_T.SETTINGS_JS_SECURITY_CUSTOM;
      securityLevelHintEl.textContent = SETTINGS_T.SETTINGS_JS_SECURITY_CUSTOM_HINT;
    }
  };

  const writeDerivedTimeouts = () => {
    const values = getCurrentSecurityValues();
    const timeoutMap = {
      signout: Number(values.session_timeout),
      account: Number(values.form_ttl_settings),
      calendar: Number(values.form_ttl_calendar),
    };

    const displayIds = {
      signout: 'security_timeout_signout',
      account: 'security_timeout_account',
      calendar: 'security_timeout_calendar',
    };

    Object.entries(timeoutMap).forEach(([key, seconds]) => {
      const el = PC.getElement(displayIds[key]);
      if (el) {
        el.textContent = formatTimeoutLabel(seconds);
      }
    });
  };

  const writeRemainingTimeouts = () => {
    const values = getCurrentSecurityValues();
    const elapsed = Math.floor((Date.now() - securityStartTs) / 1000);
    const timeoutMap = {
      signout: Number(values.session_timeout),
      account: Number(values.form_ttl_settings),
      calendar: Number(values.form_ttl_calendar),
    };

    const remainingIds = {
      signout: 'security_remaining_signout',
      account: 'security_remaining_account',
      calendar: 'security_remaining_calendar',
    };

    Object.entries(timeoutMap).forEach(([key, total]) => {
      const remaining = Math.max(0, total - elapsed);
      const el = PC.getElement(remainingIds[key]);
      if (el) {
        el.textContent = remaining > 0
          ? formatSettingsMessage(SETTINGS_T.SETTINGS_JS_SECURITY_REMAINING_FMT, { time: formatSeconds(remaining) })
          : SETTINGS_T.SETTINGS_JS_SECURITY_EXPIRED;
      }
    });
  };

  const applySecurityPreset = (presetName) => {
    const preset = securityPresets[presetName];
    if (!preset) return;
    Object.entries(preset.values).forEach(([key, value]) => {
      if (securitySelects[key]) {
        securitySelects[key].value = value;
      }
    });
    setSecurityText(presetName);
    writeDerivedTimeouts();
    writeRemainingTimeouts();
    emitSecurityTimeoutUpdate();
  };

  const saveSecuritySettings = (statusText) => {
    if (!securityFormEl) return;
    PC.updateResource('account/security', 'account_security_timeout_form').then(() => {
      PC.showToast(statusText, 'save', 3000, true);
    }).catch(error => PW.error(error));
  };

  if (securitySliderEl) {
    securitySliderEl.addEventListener('input', () => {
      const value = Number(securitySliderEl.value || 50);
      const presetName = value <= 20 ? 'relaxed' : (value >= 80 ? 'high' : 'balanced');
      applySecurityPreset(presetName);
    });

    securitySliderEl.addEventListener('change', () => {
      const value = Number(securitySliderEl.value || 50);
      const presetName = value <= 20 ? 'relaxed' : (value >= 80 ? 'high' : 'balanced');
      applySecurityPreset(presetName);
      saveSecuritySettings(`Security level set to ${securityPresets[presetName].label}`);
    });
  }

  Object.values(securitySelects).forEach((selectEl) => {
    if (!selectEl) return;
    selectEl.addEventListener('change', () => {
      writeDerivedTimeouts();
      writeRemainingTimeouts();
      emitSecurityTimeoutUpdate();
      const presetName = presetForValues(getCurrentSecurityValues());
      setSecurityText(presetName);
      saveSecuritySettings(presetName === 'custom' ? 'Security level set to Custom' : `Security level set to ${securityPresets[presetName].label}`);
    });
  });

  const initialPreset = presetForValues(getCurrentSecurityValues());
  setSecurityText(initialPreset);

  const renderEmergencySignoutHint = () => {
    if (!emergencySignoutSliderEl || !emergencySignoutValueEl) return;
    const raw = Number(emergencySignoutSliderEl.value || 600);
    const clamped = Math.min(2000, Math.max(200, Number.isFinite(raw) ? raw : 600));
    const normalized = Math.round(clamped / 200) * 200;
    emergencySignoutSliderEl.value = String(normalized);
    emergencySignoutValueEl.textContent = (normalized / 1000).toFixed(1);
  };

  if (emergencySignoutSliderEl) {
    emergencySignoutSliderEl.addEventListener('input', () => {
      renderEmergencySignoutHint();
      emitSecurityTimeoutUpdate();
    });
    emergencySignoutSliderEl.addEventListener('change', () => {
      renderEmergencySignoutHint();
      emitSecurityTimeoutUpdate();
      saveSecuritySettings('Emergency signout setting updated');
    });
  }

  renderEmergencySignoutHint();
  writeDerivedTimeouts();
  writeRemainingTimeouts();
  emitSecurityTimeoutUpdate();
  setInterval(writeRemainingTimeouts, 1000);

  // Delete account confirmation input: convert to uppercase
  // Replaces previous inline oninput handler for WCAG/CSP compliance
  const deleteAccountConfirmInput = PC.query('#delete_account_confirm_phrase');
  if (deleteAccountConfirmInput) {
    deleteAccountConfirmInput.addEventListener('input', () => {
      deleteAccountConfirmInput.value = deleteAccountConfirmInput.value.toUpperCase();
    });
  }

  /* DATA PORTABILITY: EXPORT + STAGED IMPORT */
  const dataPortabilityEls = {
    status: PC.getElement('data_portability_status'),
    actionLog: PC.getElement('data_portability_action_log'),
    exportRunBtn: PC.getElement('data_export_run_btn'),
    exportCopyBtn: PC.getElement('data_export_copy_btn'),
    exportDownloadBtn: PC.getElement('data_export_download_btn'),
    exportPayload: PC.getElement('data_export_payload'),
    exportReference: PC.getElement('data_export_reference'),
    exportChecksum: PC.getElement('data_export_checksum'),
    exportCounts: PC.getElement('data_export_counts'),
    importPayload: PC.getElement('data_import_payload_json'),
    importPrepareBtn: PC.getElement('data_import_prepare_btn'),
    importCommitBtn: PC.getElement('data_import_commit_btn'),
    importId: PC.getElement('data_import_id'),
    importChecksum: PC.getElement('data_import_checksum'),
    importCounts: PC.getElement('data_import_counts'),
    importExpires: PC.getElement('data_import_expires'),
    importResultCounts: PC.getElement('data_import_result_counts'),
  };

  const dataPortabilityState = {
    preparedImportId: '',
    exporting: false,
    preparing: false,
    committing: false,
  };

  const setDataPortabilityStatus = (message, tone = 'muted') => {
    setPrefixedStatusText(dataPortabilityEls.status, message, tone);
  };

  const appendDataPortabilityLog = (title, detail = '') => {
    if (!dataPortabilityEls.actionLog) {
      return;
    }

    const ts = new Date().toLocaleTimeString();
    const item = document.createElement('li');
    item.textContent = detail
      ? `[${ts}] ${title} - ${detail}`
      : `[${ts}] ${title}`;
    dataPortabilityEls.actionLog.prepend(item);

    const maxItems = 30;
    while (dataPortabilityEls.actionLog.children.length > maxItems) {
      dataPortabilityEls.actionLog.removeChild(dataPortabilityEls.actionLog.lastChild);
    }
  };

  const summarizeCounts = (counts) => {
    const sites = Number(counts?.sites || 0);
    const workEntries = Number(counts?.work_entries || 0);
    return `${sites} sites, ${workEntries} work entries`;
  };

  const updateDataPortabilityButtons = () => {
    const hasExportPayload = Boolean(String(dataPortabilityEls.exportPayload?.value || '').trim());
    const hasPreparedImport = Boolean(dataPortabilityState.preparedImportId);
    const busy = dataPortabilityState.exporting || dataPortabilityState.preparing || dataPortabilityState.committing;

    if (dataPortabilityEls.exportRunBtn) {
      dataPortabilityEls.exportRunBtn.disabled = busy;
      dataPortabilityEls.exportRunBtn.setAttribute('aria-disabled', dataPortabilityEls.exportRunBtn.disabled ? 'true' : 'false');
    }
    if (dataPortabilityEls.exportCopyBtn) {
      dataPortabilityEls.exportCopyBtn.disabled = busy || !hasExportPayload;
      dataPortabilityEls.exportCopyBtn.setAttribute('aria-disabled', dataPortabilityEls.exportCopyBtn.disabled ? 'true' : 'false');
    }
    if (dataPortabilityEls.exportDownloadBtn) {
      dataPortabilityEls.exportDownloadBtn.disabled = busy || !hasExportPayload;
      dataPortabilityEls.exportDownloadBtn.setAttribute('aria-disabled', dataPortabilityEls.exportDownloadBtn.disabled ? 'true' : 'false');
    }
    if (dataPortabilityEls.importPrepareBtn) {
      dataPortabilityEls.importPrepareBtn.disabled = busy;
      dataPortabilityEls.importPrepareBtn.setAttribute('aria-disabled', dataPortabilityEls.importPrepareBtn.disabled ? 'true' : 'false');
    }
    if (dataPortabilityEls.importCommitBtn) {
      dataPortabilityEls.importCommitBtn.disabled = busy || !hasPreparedImport;
      dataPortabilityEls.importCommitBtn.setAttribute('aria-disabled', dataPortabilityEls.importCommitBtn.disabled ? 'true' : 'false');
    }
  };

  const normalizeApiData = (payload) => {
    if (!payload || typeof payload !== 'object') {
      return {};
    }
    if (payload.data && typeof payload.data === 'object') {
      return payload.data;
    }
    return payload;
  };

  const postDataPortabilityForm = async (url, formPairs = []) => {
    const csrfToken = getSettingsCsrfToken();
    const body = new URLSearchParams();
    if (csrfToken) {
      body.set('csrf_token', csrfToken);
    }
    formPairs.forEach(([key, value]) => body.set(key, String(value ?? '')));

    const response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
      },
      body: body.toString(),
    });

    const { data, raw } = await parseApiResponse(response);
    return { response, data, raw };
  };

  const dataConsentStatusEl = PC.getElement('settings_data_consent_status');
  const setDataConsentStatus = (message, mode = 'muted') => {
    if (dataConsentStatusEl) {
      dataConsentStatusEl.textContent = message;
      dataConsentStatusEl.dataset.status = mode;
    }
  };

  const confirmBusinessConsentRevoke = async () => new Promise((resolve) => {
    const modal = PC.getElement('modal_business_consent_revoke');
    const confirmBtn = PC.getElement('business_consent_revoke_confirm_btn');
    const cancelBtn = PC.getElement('business_consent_revoke_cancel_btn');
    if (!(modal instanceof HTMLDialogElement) || !(confirmBtn instanceof HTMLButtonElement) || !(cancelBtn instanceof HTMLButtonElement)) {
      resolve(window.confirm(SETTINGS_T.SETTINGS_DATA_CONSENT_REVOKE_CONFIRM));
      return;
    }

    let settled = false;
    const cleanup = () => {
      confirmBtn.removeEventListener('click', onConfirm);
      cancelBtn.removeEventListener('click', onCancel);
      modal.removeEventListener('cancel', onCancel);
      modal.removeEventListener('close', onCancel);
    };
    const finish = (confirmed) => {
      if (settled) {
        return;
      }
      settled = true;
      cleanup();
      if (modal.open) {
        PC.closeModal('modal_business_consent_revoke', SETTINGS_T.SETTINGS_DATA_CONSENT_REVOKE_MODAL_TITLE);
      }
      resolve(confirmed);
    };
    function onConfirm(event) {
      event.preventDefault();
      finish(true);
    }
    function onCancel(event) {
      event.preventDefault();
      finish(false);
    }

    confirmBtn.addEventListener('click', onConfirm);
    cancelBtn.addEventListener('click', onCancel);
    modal.addEventListener('cancel', onCancel);
    modal.addEventListener('close', onCancel);
    PC.openModal('modal_business_consent_revoke', SETTINGS_T.SETTINGS_DATA_CONSENT_REVOKE_MODAL_TITLE);
    confirmBtn.focus();
  });

  const postBusinessConsentAction = async (businessId, action) => {
    const csrfToken = getSettingsCsrfToken();
    const body = new URLSearchParams();
    if (csrfToken) {
      body.set('csrf_token', csrfToken);
    }
    body.set('consent_action', action);
    if (action === 'grant') {
      body.set('consent_acknowledged', '1');
      body.set('consent_version', 'v1');
      body.set('disclaimer_text', SETTINGS_T.SETTINGS_DATA_CONSENT_RULE || 'Only businesses you approve can use your work entries for protected reports.');
    }

    const response = await fetch(`/api/v1/businesses/${encodeURIComponent(businessId)}/consent/${action}`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
      },
      body: body.toString(),
    });

    const { data, raw } = await parseApiResponse(response);
    return { response, data, raw };
  };

  const getBusinessConsentCount = (state) => {
    const countEl = document.querySelector(`[data-business-consent-count="${state}"] strong`);
    if (!(countEl instanceof HTMLElement)) {
      return 0;
    }
    const value = Number.parseInt(String(countEl.textContent || '0'), 10);
    return Number.isFinite(value) ? value : 0;
  };

  const setBusinessConsentCount = (state, value) => {
    const countWrap = document.querySelector(`[data-business-consent-count="${state}"]`);
    if (!(countWrap instanceof HTMLElement)) {
      return;
    }

    const normalizedValue = Math.max(0, value);
    const countEl = countWrap.querySelector('strong');
    if (countEl instanceof HTMLElement) {
      countEl.textContent = String(normalizedValue);
    }

    if (state !== 'active') {
      countWrap.hidden = normalizedValue === 0;
    }
  };

  const adjustBusinessConsentCounts = (fromState, toState) => {
    if (fromState === toState) {
      return;
    }
    if (fromState) {
      setBusinessConsentCount(fromState, getBusinessConsentCount(fromState) - 1);
    }
    if (toState) {
      setBusinessConsentCount(toState, getBusinessConsentCount(toState) + 1);
    }
  };

  const setBusinessConsentCardState = (button, nextState, copy) => {
    const card = button.closest('[data-business-consent-card]');
    if (!(card instanceof HTMLElement)) {
      return;
    }

    const previousState = String(card.dataset.businessConsentState || '');
    const stateClasses = ['is-active', 'is-waiting', 'is-setup', 'is-revoked'];
    card.classList.remove(...stateClasses);
    card.classList.add(`is-${nextState}`);
    card.dataset.businessConsentState = nextState;

    const pill = card.querySelector('[data-business-consent-pill]');
    if (pill instanceof HTMLElement) {
      pill.classList.remove(...stateClasses);
      pill.classList.add(`is-${nextState}`);
      const statusText = pill.querySelector('.settings_data_consent_status_text');
      if (statusText instanceof HTMLElement) {
        statusText.textContent = copy.status;
      } else {
        pill.textContent = copy.status;
      }
    }

    const desc = card.querySelector('[data-business-consent-desc]');
    if (desc instanceof HTMLElement) {
      desc.textContent = copy.description;
    }

    const controls = card.querySelector('.settings_data_consent_controls');
    if (controls instanceof HTMLElement) {
      controls.querySelectorAll('[data-business-consent-action]').forEach((actionEl) => actionEl.remove());
      if (nextState === 'active') {
        const revokeButton = document.createElement('button');
        revokeButton.type = 'button';
        revokeButton.className = 'btn btn_secondary settings_data_consent_action is-revoke';
        revokeButton.dataset.businessConsentAction = 'revoke';
        revokeButton.dataset.businessConsentMode = 'revoke';
        revokeButton.dataset.businessId = String(button.dataset.businessId || '');
        revokeButton.dataset.businessName = String(button.dataset.businessName || '');
        revokeButton.textContent = SETTINGS_T.SETTINGS_DATA_CONSENT_REVOKE || 'Revoke';
        revokeButton.addEventListener('click', handleBusinessConsentClick);
        controls.appendChild(revokeButton);
      }
    }

    adjustBusinessConsentCounts(previousState, nextState);
  };

  async function handleBusinessConsentClick(event) {
    const button = event?.currentTarget;
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    const businessId = String(button.dataset.businessId || '');
    const action = String(button.dataset.businessConsentAction || '');
    const mode = String(button.dataset.businessConsentMode || action);
    if (businessId === '' || !['grant', 'revoke'].includes(action)) {
      return;
    }

    const confirmMessage = action === 'revoke'
      ? SETTINGS_T.SETTINGS_DATA_CONSENT_REVOKE_CONFIRM
      : (mode === 'refresh' ? SETTINGS_T.SETTINGS_DATA_CONSENT_REFRESH_CONFIRM : SETTINGS_T.SETTINGS_DATA_CONSENT_GRANT_CONFIRM);
    if (action === 'revoke') {
      const confirmed = await confirmBusinessConsentRevoke();
      if (!confirmed) {
        return;
      }
    } else if (confirmMessage && !window.confirm(confirmMessage)) {
      return;
    }

    button.disabled = true;
    button.setAttribute('aria-disabled', 'true');
    setDataConsentStatus(confirmMessage, 'muted');

    try {
      const { response, data, raw } = await postBusinessConsentAction(businessId, action);
      if (!response.ok || !isSettingsApiStatusSuccess(data)) {
        const message = cleanSettingsApiMessage(data?.message || raw, SETTINGS_T.SETTINGS_DATA_CONSENT_ACTION_FAILED);
        throw new Error(message);
      }

      const payload = data?.data && typeof data.data === 'object' ? data.data : {};
      const protectedAccessReady = payload.protected_access_ready !== false;
      const successMessage = action === 'revoke'
        ? SETTINGS_T.SETTINGS_DATA_CONSENT_REVOKE_SUCCESS
        : (protectedAccessReady
          ? (mode === 'refresh' ? SETTINGS_T.SETTINGS_DATA_CONSENT_REFRESH_SUCCESS : SETTINGS_T.SETTINGS_DATA_CONSENT_GRANT_SUCCESS)
          : SETTINGS_T.SETTINGS_DATA_CONSENT_GRANT_SETUP_NEEDED);
      setDataConsentStatus(successMessage, 'success');
      PC.showToast(successMessage, 'save', 3000, true);

      if (action === 'revoke') {
        setBusinessConsentCardState(button, 'revoked', {
          status: SETTINGS_T.SETTINGS_DATA_CONSENT_REVOKED || 'Archived',
          description: SETTINGS_T.SETTINGS_DATA_CONSENT_REVOKED_DESC || 'This business can no longer access protected work data.',
        });
      } else if (protectedAccessReady) {
        setBusinessConsentCardState(button, 'active', {
          status: SETTINGS_T.SETTINGS_DATA_CONSENT_ACTIVE || 'Active',
          description: SETTINGS_T.SETTINGS_DATA_CONSENT_ACTIVE_DESC || 'Can use approved work entries for reports.',
        });
      } else {
        const desc = button.closest('[data-business-consent-card]')?.querySelector('[data-business-consent-desc]');
        if (desc instanceof HTMLElement) {
          desc.textContent = SETTINGS_T.SETTINGS_DATA_CONSENT_SECURE_ACCESS_NOT_READY_DESC || SETTINGS_T.SETTINGS_DATA_CONSENT_GRANT_SETUP_NEEDED;
        }
        button.dataset.businessConsentMode = 'refresh';
        button.textContent = SETTINGS_T.SETTINGS_DATA_CONSENT_REFRESH_ACCESS || button.textContent;
        button.disabled = false;
        button.setAttribute('aria-disabled', 'false');
      }
    } catch (error) {
      const message = cleanSettingsApiMessage(error?.message, SETTINGS_T.SETTINGS_DATA_CONSENT_ACTION_FAILED);
      setDataConsentStatus(message, 'error');
      PC.showToast(message, 'error', 4000, true);
      button.disabled = false;
      button.setAttribute('aria-disabled', 'false');
    }
  }

  document.querySelectorAll('[data-business-consent-action][data-business-id]').forEach((button) => {
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    button.addEventListener('click', handleBusinessConsentClick);
  });

  const runAccountDataExport = async () => {
    dataPortabilityState.exporting = true;
    updateDataPortabilityButtons();
    setDataPortabilityStatus('Export started. Requesting account dataset from server.', 'muted');
    appendDataPortabilityLog('Export started', 'POST /api/v1/account/data/export');

    const exportSectionPairs = [
      ['export_section_user', PC.query('#export_section_user')?.checked === false ? '0' : '1'],
      ['export_section_sites', PC.query('#export_section_sites')?.checked === false ? '0' : '1'],
      ['export_section_work', PC.query('#export_section_work')?.checked === false ? '0' : '1'],
    ];

    try {
      const { response, data, raw } = await postDataPortabilityForm('/api/v1/account/data/export', exportSectionPairs);
      if (!response.ok || !data || data.status !== 'success') {
        const message = data?.message || raw || `Export request failed with HTTP ${response.status}.`;
        setDataPortabilityStatus(message, 'error');
        appendDataPortabilityLog('Export failed', message);
        return;
      }

      const normalized = normalizeApiData(data);
      const payload = normalized.payload;
      if (!payload || typeof payload !== 'object') {
        const message = 'Export response did not include payload data.';
        setDataPortabilityStatus(message, 'error');
        appendDataPortabilityLog('Export failed', message);
        return;
      }

      let payloadJson = JSON.stringify(payload, null, 2);
      const exportEncryptPreferenceEl = document.getElementById('export_encrypt_preference');
      const shouldClientEncrypt = exportEncryptPreferenceEl instanceof HTMLInputElement && exportEncryptPreferenceEl.checked;

      if (shouldClientEncrypt) {
        const passphrase = promptForExportPassphrase();
        if (passphrase === null) {
          setDataPortabilityStatus('Export encryption cancelled. Plaintext payload was not written.', 'info');
          appendDataPortabilityLog('Export encryption cancelled', 'User cancelled passphrase entry.');
          return;
        }

        try {
          payloadJson = await encryptExportPayloadWithPassphrase(payloadJson, passphrase);
          appendDataPortabilityLog('Export encrypted', 'Payload encrypted locally with AES-GCM.');
        } catch (error) {
          const message = SETTINGS_T.SETTINGS_JS_EXPORT_ENCRYPT_FAILED;
          setDataPortabilityStatus(message, 'error');
          appendDataPortabilityLog('Export encryption failed', String(error?.message || 'unknown error'));
          PW.error(error);
          return;
        }
      }

      if (dataPortabilityEls.exportPayload) {
        dataPortabilityEls.exportPayload.value = payloadJson;
      }
      if (dataPortabilityEls.importPayload) {
        dataPortabilityEls.importPayload.value = payloadJson;
      }
      if (dataPortabilityEls.exportReference) {
        dataPortabilityEls.exportReference.textContent = String(normalized.reference || '-');
      }
      if (dataPortabilityEls.exportChecksum) {
        dataPortabilityEls.exportChecksum.textContent = String(normalized.checksum_sha256 || '-');
      }
      if (dataPortabilityEls.exportCounts) {
        dataPortabilityEls.exportCounts.textContent = summarizeCounts(normalized.counts);
      }

      const exportWarning = String(normalized.warning || '').trim();
      if (shouldClientEncrypt) {
        setDataPortabilityStatus(SETTINGS_T.SETTINGS_JS_EXPORT_ENCRYPTED_SUCCESS, 'success');
        appendDataPortabilityLog('Export completed', `Reference ${String(normalized.reference || '-')}; encrypted payload ready.`);
      } else if (exportWarning) {
        setDataPortabilityStatus(`Export completed. ${exportWarning}`, 'info');
        appendDataPortabilityLog('Export warning', exportWarning);
      } else {
        setDataPortabilityStatus('Export completed. Payload is ready to copy, download, or import.', 'success');
      }
      if (!shouldClientEncrypt) {
        appendDataPortabilityLog('Export completed', `Reference ${String(normalized.reference || '-')}; ${summarizeCounts(normalized.counts)}`);
      }

      loadExportHistory();
    } catch (error) {
      const message = `Export request failed: ${String(error?.message || 'unknown error')}`;
      setDataPortabilityStatus(message, 'error');
      appendDataPortabilityLog('Export failed', message);
      PW.error(error);
    } finally {
      dataPortabilityState.exporting = false;
      updateDataPortabilityButtons();
    }
  };

  const prepareAccountDataImport = async () => {
    const payloadJson = String(dataPortabilityEls.importPayload?.value || '').trim();
    if (!payloadJson) {
      setDataPortabilityStatus('Paste export payload JSON before preparing import.', 'error');
      appendDataPortabilityLog('Prepare blocked', 'Import payload is empty.');
      return;
    }

    // Guard against pathologically large pastes freezing the tab before we even hit the server.
    const MAX_IMPORT_BYTES = 4 * 1024 * 1024; // 4 MB
    if (payloadJson.length > MAX_IMPORT_BYTES) {
      const sizeMB = (payloadJson.length / (1024 * 1024)).toFixed(1);
      const message = `Payload is too large (${sizeMB} MB). Maximum allowed is 4 MB. Check that you pasted the correct export file.`;
      setDataPortabilityStatus(message, 'error');
      appendDataPortabilityLog('Prepare blocked', message);
      return;
    }

    try {
      JSON.parse(payloadJson);
      appendDataPortabilityLog('Prepare precheck passed', 'Client-side JSON parse succeeded.');
    } catch (error) {
      const message = `Payload is not valid JSON: ${String(error?.message || 'parse error')}`;
      setDataPortabilityStatus(message, 'error');
      appendDataPortabilityLog('Prepare blocked', message);
      return;
    }

    dataPortabilityState.preparing = true;
    dataPortabilityState.preparedImportId = '';
    updateDataPortabilityButtons();
    setDataPortabilityStatus('Prepare started. Validating payload and staging import session.', 'muted');
    appendDataPortabilityLog('Prepare started', 'POST /api/v1/account/data/import/prepare');

    try {
      const { response, data, raw } = await postDataPortabilityForm('/api/v1/account/data/import/prepare', [['payload_json', payloadJson]]);
      if (!response.ok || !data || data.status !== 'success') {
        const message = data?.message || raw || `Prepare request failed with HTTP ${response.status}.`;
        setDataPortabilityStatus(message, 'error');
        appendDataPortabilityLog('Prepare failed', message);
        return;
      }

      const normalized = normalizeApiData(data);
      const importId = String(normalized.import_id || '').trim();
      if (importId === '') {
        const message = 'Prepare response missing import session id.';
        setDataPortabilityStatus(message, 'error');
        appendDataPortabilityLog('Prepare failed', message);
        return;
      }

      dataPortabilityState.preparedImportId = importId;
      if (dataPortabilityEls.importId) {
        dataPortabilityEls.importId.textContent = importId;
      }
      if (dataPortabilityEls.importChecksum) {
        dataPortabilityEls.importChecksum.textContent = String(normalized.checksum_sha256 || '-');
      }
      if (dataPortabilityEls.importCounts) {
        dataPortabilityEls.importCounts.textContent = summarizeCounts(normalized.counts);
      }
      if (dataPortabilityEls.importExpires) {
        dataPortabilityEls.importExpires.textContent = `${String(normalized.expires_in_seconds || '-') } seconds`;
      }
      if (dataPortabilityEls.importResultCounts) {
        dataPortabilityEls.importResultCounts.textContent = '-';
      }

      setDataPortabilityStatus('Prepare completed. Review details, then commit import when ready.', 'info');
      appendDataPortabilityLog('Prepare completed', `Import ID ${importId}; ${summarizeCounts(normalized.counts)}`);
    } catch (error) {
      const message = `Prepare request failed: ${String(error?.message || 'unknown error')}`;
      setDataPortabilityStatus(message, 'error');
      appendDataPortabilityLog('Prepare failed', message);
      PW.error(error);
    } finally {
      dataPortabilityState.preparing = false;
      updateDataPortabilityButtons();
    }
  };

  const commitAccountDataImport = async () => {
    const importId = String(dataPortabilityState.preparedImportId || '').trim();
    if (!importId) {
      setDataPortabilityStatus('Run Prepare Import first to create a valid import session.', 'error');
      appendDataPortabilityLog('Commit blocked', 'No prepared import session found.');
      return;
    }

    dataPortabilityState.committing = true;
    updateDataPortabilityButtons();
    setDataPortabilityStatus('Commit started. Applying staged import to account records.', 'muted');
    appendDataPortabilityLog('Commit started', `POST /api/v1/account/data/import/commit (import_id=${importId})`);

    try {
      const { response, data, raw } = await postDataPortabilityForm('/api/v1/account/data/import/commit', [['import_id', importId]]);
      if (!response.ok || !data || data.status !== 'success') {
        const message = data?.message || raw || `Commit request failed with HTTP ${response.status}.`;
        setDataPortabilityStatus(message, 'error');
        appendDataPortabilityLog('Commit failed', message);
        return;
      }

      const normalized = normalizeApiData(data);
      const counts = normalized.counts || {};
      const userCount = Number(counts.user || 0);
      const siteCount = Number(counts.sites || 0);
      const workCount = Number(counts.work_entries || 0);
      const summary = `${userCount} user profile, ${siteCount} sites, ${workCount} work entries`;

      if (dataPortabilityEls.importResultCounts) {
        dataPortabilityEls.importResultCounts.textContent = summary;
      }

      setDataPortabilityStatus('Commit completed. Imported records are now active on this account.', 'success');
      appendDataPortabilityLog('Commit completed', summary);
      dataPortabilityState.preparedImportId = '';
      if (dataPortabilityEls.importExpires) {
        dataPortabilityEls.importExpires.textContent = SETTINGS_T.SETTINGS_JS_DATA_PORTABILITY_CONSUMED;
      }
    } catch (error) {
      const message = `Commit request failed: ${String(error?.message || 'unknown error')}`;
      setDataPortabilityStatus(message, 'error');
      appendDataPortabilityLog('Commit failed', message);
      PW.error(error);
    } finally {
      dataPortabilityState.committing = false;
      updateDataPortabilityButtons();
    }
  };

  if (dataPortabilityEls.exportRunBtn) {
    dataPortabilityEls.exportRunBtn.addEventListener('click', (event) => {
      event.preventDefault();
      runAccountDataExport();
    });
  }

  if (dataPortabilityEls.exportCopyBtn) {
    dataPortabilityEls.exportCopyBtn.addEventListener('click', async (event) => {
      event.preventDefault();
      const payload = String(dataPortabilityEls.exportPayload?.value || '').trim();
      if (!payload) {
        return;
      }

      try {
        await navigator.clipboard.writeText(payload);
        appendDataPortabilityLog('Payload copied', 'Export payload copied to clipboard.');
        setDataPortabilityStatus('Payload copied to clipboard.', 'info');
      } catch (error) {
        appendDataPortabilityLog('Copy failed', 'Clipboard access was denied.');
        setDataPortabilityStatus(SETTINGS_T.SETTINGS_JS_DATA_PORTABILITY_COPY_FAILED, 'error');
        PW.error(error);
      }
    });
  }

  if (dataPortabilityEls.exportDownloadBtn) {
    dataPortabilityEls.exportDownloadBtn.addEventListener('click', (event) => {
      event.preventDefault();
      const payload = String(dataPortabilityEls.exportPayload?.value || '').trim();
      if (!payload) {
        return;
      }

      const blob = new Blob([payload], { type: 'application/json;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = `paycal-account-export-${new Date().toISOString().slice(0, 10)}.json`;
      document.body.appendChild(anchor);
      anchor.click();
      document.body.removeChild(anchor);
      URL.revokeObjectURL(url);

      appendDataPortabilityLog('Payload downloaded', 'Export JSON file downloaded locally.');
      setDataPortabilityStatus('Export JSON downloaded.', 'info');
    });
  }

  if (dataPortabilityEls.importPrepareBtn) {
    dataPortabilityEls.importPrepareBtn.addEventListener('click', (event) => {
      event.preventDefault();
      prepareAccountDataImport();
    });
  }

  if (dataPortabilityEls.importCommitBtn) {
    dataPortabilityEls.importCommitBtn.addEventListener('click', (event) => {
      event.preventDefault();
      const confirmDialog = /** @type {HTMLDialogElement|null} */ (PC.getElement('modal_import_confirm'));
      const summaryEl = PC.getElement('modal_import_confirm_summary');
      if (summaryEl) {
        const counts = dataPortabilityEls.importCounts?.textContent || '';
        summaryEl.textContent = counts ? `Staged: ${counts}` : '';
      }
      if (confirmDialog) {
        PC.openModal('modal_import_confirm', SETTINGS_T.SETTINGS_JS_MODAL_CONFIRM_IMPORT);
      } else {
        commitAccountDataImport();
      }
    });
  }

  const importConfirmProceedBtn = PC.getElement('import_confirm_proceed_btn');
  if (importConfirmProceedBtn) {
    importConfirmProceedBtn.addEventListener('click', () => {
      PC.closeModal('modal_import_confirm', SETTINGS_T.SETTINGS_JS_MODAL_CONFIRM_IMPORT);
      commitAccountDataImport();
    });
  }

  const importConfirmCancelBtn = PC.getElement('import_confirm_cancel_btn');
  if (importConfirmCancelBtn) {
    importConfirmCancelBtn.addEventListener('click', () => {
      PC.closeModal('modal_import_confirm', SETTINGS_T.SETTINGS_JS_MODAL_CONFIRM_IMPORT);
      appendDataPortabilityLog('Commit cancelled', 'Import was not applied.');
    });
  }

  if (dataPortabilityEls.importPayload) {
    dataPortabilityEls.importPayload.addEventListener('input', () => {
      if (dataPortabilityState.preparedImportId !== '') {
        dataPortabilityState.preparedImportId = '';
        if (dataPortabilityEls.importId) {
          dataPortabilityEls.importId.textContent = '-';
        }
        if (dataPortabilityEls.importExpires) {
          dataPortabilityEls.importExpires.textContent = '-';
        }
        appendDataPortabilityLog('Prepared session cleared', 'Import payload changed; run Prepare Import again.');
      }
      updateDataPortabilityButtons();
    });
  }

  if (dataPortabilityEls.actionLog) {
    appendDataPortabilityLog('Data portability ready', 'Use Export, then Prepare Import, then Commit Import.');
  }
  updateDataPortabilityButtons();

  const formatSessionTimestamp = (rawValue) => {
    const numeric = Number.parseInt(String(rawValue || ''), 10);
    if (!Number.isFinite(numeric) || numeric <= 0) {
      return '-';
    }

    return new Date(numeric * 1000).toLocaleString();
  };

  const renderAccountSessions = (sessions) => {
    const listEl = document.getElementById('settings_sessions_list');
    const statusEl = document.getElementById('settings_sessions_status');
    if (!(listEl instanceof HTMLElement)) {
      return;
    }

    listEl.textContent = '';
    const rows = Array.isArray(sessions) ? sessions : [];

    if (rows.length === 0) {
      const emptyItem = document.createElement('li');
      emptyItem.textContent = SETTINGS_T.SETTINGS_JS_SESSIONS_NONE;
      listEl.appendChild(emptyItem);
      if (statusEl instanceof HTMLElement) {
        statusEl.textContent = SETTINGS_T.SETTINGS_JS_SESSIONS_NONE;
      }
      return;
    }

    rows.forEach((session) => {
      const item = document.createElement('li');
      const agent = String(session?.user_agent || 'Unknown device');
      const lastActivity = formatSessionTimestamp(session?.last_activity);
      const currentSuffix = session?.is_current ? ' (current)' : '';
      item.textContent = `${agent}${currentSuffix} — last active ${lastActivity}`;
      listEl.appendChild(item);
    });

    if (statusEl instanceof HTMLElement) {
      statusEl.textContent = formatSettingsMessage(SETTINGS_T.SETTINGS_JS_SESSIONS_LOADED_COUNT_FMT, { count: rows.length });
    }
  };

  const loadAccountSessions = async () => {
    const listEl = document.getElementById('settings_sessions_list');
    if (!(listEl instanceof HTMLElement)) {
      return;
    }

    const statusEl = document.getElementById('settings_sessions_status');
    if (statusEl instanceof HTMLElement) {
      statusEl.textContent = SETTINGS_T.SETTINGS_JS_SESSIONS_LOADING;
    }

    try {
      const { response, data, raw } = await postDataPortabilityForm('/api/v1/account/sessions/list');
      if (!response.ok || !data || data.status !== 'success') {
        throw new Error(data?.message || raw || SETTINGS_T.SETTINGS_JS_SESSIONS_LOAD_FAILED);
      }

      const normalized = normalizeApiData(data);
      renderAccountSessions(normalized.sessions || []);
    } catch (error) {
      if (statusEl instanceof HTMLElement) {
        statusEl.textContent = SETTINGS_T.SETTINGS_JS_SESSIONS_LOAD_FAILED;
      }
      listEl.textContent = '';
      const errorItem = document.createElement('li');
      errorItem.textContent = SETTINGS_T.SETTINGS_JS_SESSIONS_LOAD_FAILED;
      listEl.appendChild(errorItem);
      PW.error(error);
    }
  };

  const revokeOtherAccountSessions = async () => {
    const statusEl = document.getElementById('settings_sessions_status');
    const revokeBtn = document.getElementById('settings_revoke_other_sessions_btn');
    if (revokeBtn instanceof HTMLButtonElement) {
      revokeBtn.disabled = true;
      revokeBtn.setAttribute('aria-disabled', 'true');
    }

    try {
      const { response, data, raw } = await postDataPortabilityForm('/api/v1/account/sessions/revoke_others');
      if (!response.ok || !data || data.status !== 'success') {
        throw new Error(data?.message || raw || SETTINGS_T.SETTINGS_JS_SESSIONS_REVOKE_FAILED);
      }

      const normalized = normalizeApiData(data);
      const revoked = Number(normalized.revoked || 0);
      if (statusEl instanceof HTMLElement) {
        statusEl.textContent = formatSettingsMessage(SETTINGS_T.SETTINGS_JS_SESSIONS_REVOKED_FMT, { count: revoked });
      }
      await loadAccountSessions();
    } catch (error) {
      if (statusEl instanceof HTMLElement) {
        statusEl.textContent = SETTINGS_T.SETTINGS_JS_SESSIONS_REVOKE_FAILED;
      }
      PW.error(error);
    } finally {
      if (revokeBtn instanceof HTMLButtonElement) {
        revokeBtn.disabled = false;
        revokeBtn.setAttribute('aria-disabled', 'false');
      }
    }
  };

  const renderExportHistory = (history) => {
    const listEl = document.getElementById('settings_export_history_list');
    if (!(listEl instanceof HTMLElement)) {
      return;
    }

    listEl.textContent = '';
    const rows = Array.isArray(history) ? history : [];

    if (rows.length === 0) {
      const emptyItem = document.createElement('li');
      emptyItem.textContent = SETTINGS_T.SETTINGS_JS_EXPORT_HISTORY_NONE;
      listEl.appendChild(emptyItem);
      return;
    }

    rows.forEach((entry) => {
      const item = document.createElement('li');
      const reference = String(entry?.reference || '-');
      const exportedAt = String(entry?.exported_at || '-');
      const siteCount = Number(entry?.counts?.sites || 0);
      const workCount = Number(entry?.counts?.work_entries || 0);
      item.textContent = `${reference} — ${exportedAt} (${siteCount} sites, ${workCount} work entries)`;
      listEl.appendChild(item);
    });
  };

  const loadExportHistory = async () => {
    const listEl = document.getElementById('settings_export_history_list');
    if (!(listEl instanceof HTMLElement)) {
      return;
    }

    try {
      const { response, data, raw } = await postDataPortabilityForm('/api/v1/account/export/history');
      if (!response.ok || !data || data.status !== 'success') {
        throw new Error(data?.message || raw || SETTINGS_T.SETTINGS_JS_EXPORT_HISTORY_LOAD_FAILED);
      }

      const normalized = normalizeApiData(data);
      renderExportHistory(normalized.history || []);
    } catch (error) {
      listEl.textContent = '';
      const errorItem = document.createElement('li');
      errorItem.textContent = SETTINGS_T.SETTINGS_JS_EXPORT_HISTORY_LOAD_FAILED;
      listEl.appendChild(errorItem);
      PW.error(error);
    }
  };

  if (document.getElementById('settings_sessions_list')) {
    loadAccountSessions();
  }
  if (document.getElementById('settings_export_history_list')) {
    loadExportHistory();
  }

  const revokeOtherSessionsBtn = document.getElementById('settings_revoke_other_sessions_btn');
  if (revokeOtherSessionsBtn instanceof HTMLButtonElement) {
    revokeOtherSessionsBtn.addEventListener('click', (event) => {
      event.preventDefault();
      revokeOtherAccountSessions();
    });
  }

  const copySupportInfoBtn = document.getElementById('settings_copy_support_info_btn');
  const copySupportInfoStatus = document.getElementById('settings_copy_support_info_status');
  if (copySupportInfoBtn instanceof HTMLButtonElement) {
    copySupportInfoBtn.addEventListener('click', async () => {
      const payload = [
        SETTINGS_T.SETTINGS_JS_SUPPORT_INFO_LABEL,
        `URL: ${window.location.href}`,
        `User agent: ${navigator.userAgent}`,
        `Language: ${navigator.language || 'unknown'}`,
        `Viewport: ${window.innerWidth}x${window.innerHeight}`,
        `Time: ${new Date().toISOString()}`,
      ].join('\n');

      try {
        await navigator.clipboard.writeText(payload);
        if (copySupportInfoStatus instanceof HTMLElement) {
          copySupportInfoStatus.textContent = SETTINGS_T.SETTINGS_JS_SUPPORT_INFO_COPIED;
        }
        PC.showToast(SETTINGS_T.SETTINGS_JS_SUPPORT_INFO_COPIED, 'save', 3000, true);
      } catch (error) {
        if (copySupportInfoStatus instanceof HTMLElement) {
          copySupportInfoStatus.textContent = SETTINGS_T.SETTINGS_JS_SUPPORT_INFO_COPY_FAILED;
        }
        PW.error(error);
      }
    });
  }

  const exportDebugBundleBtn = document.getElementById('settings_export_debug_bundle_btn');
  const exportDebugBundleStatus = document.getElementById('settings_export_debug_bundle_status');
  if (exportDebugBundleBtn instanceof HTMLButtonElement) {
    exportDebugBundleBtn.addEventListener('click', async () => {
      let bundleText = '';
      try {
        if (window.PW && typeof window.PW.exportErrorData === 'function') {
          bundleText = JSON.stringify(window.PW.exportErrorData(), null, 2);
        } else {
          bundleText = JSON.stringify({
            timestamp: new Date().toISOString(),
            url: window.location.href,
            userAgent: navigator.userAgent,
          }, null, 2);
        }
        await navigator.clipboard.writeText(bundleText);
        if (exportDebugBundleStatus instanceof HTMLElement) {
          exportDebugBundleStatus.textContent = SETTINGS_T.SETTINGS_JS_DIAGNOSTICS_BUNDLE_COPIED;
        }
        PC.showToast(SETTINGS_T.SETTINGS_JS_DIAGNOSTICS_BUNDLE_COPIED, 'save', 3000, true);
      } catch (error) {
        if (exportDebugBundleStatus instanceof HTMLElement) {
          exportDebugBundleStatus.textContent = SETTINGS_T.SETTINGS_JS_DIAGNOSTICS_BUNDLE_FAILED;
        }
        PW.error(error);
      }
    });
  }

  const settingsWorkspace = document.getElementById('settings-workspace');
  const isSubscriptionSubpage = settingsWorkspace instanceof HTMLElement && settingsWorkspace.dataset.settingsSubpage === 'subscription';
  const hasBillingPanel = document.getElementById('panel-billing') instanceof HTMLElement;
  if (isSubscriptionSubpage && hasBillingPanel) {
    await initializeBillingSection({
      successUrl: '/settings/subscription/?billing=success',
      cancelUrl: '/settings/subscription/?billing=cancel',
      returnUrl: '/settings/subscription/',
    messages: {
      success: SETTINGS_T.BILLING_JS_PREMIUM_ACTIVE,
      cancel: SETTINGS_T.BILLING_JS_PREMIUM_DISABLED,
      confirming: SETTINGS_T.BILLING_JS_CONFIRMING,
      loadingStatus: SETTINGS_T.BILLING_JS_LOADING_STATUS,
      downgradeHelpScheduled: SETTINGS_T.BILLING_JS_DOWNGRADE_HELP_SCHEDULED,
      downgradeHelpDefault: SETTINGS_T.BILLING_JS_DOWNGRADE_HELP_DEFAULT,
    },
    });
  }
});
