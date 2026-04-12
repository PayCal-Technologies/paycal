<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();

$user = User::current();

$i18nKeys = [
  'INFO_UPDATED',
  'SIGN_OUT',
  'UPDATING_CALENDAR_AUDIO_LABELS_TO',
  'UPDATING_CALENDAR_AUTOFOCUS_TO',
  'UPDATING_CALENDAR_DATE_LABEL_POSITION_TO',
  'UPDATING_CALENDAR_DAY_NAME_FORMAT_TO',
  'UPDATING_CALENDAR_WORK_ENTRY_POSITION_TO',
  'UPDATING_INFO',
  'UPDATING_PAY_PERIOD',
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

const isDebugEnabled = () => window.PAYCAL_DEBUG === true;
const debugLog = (...args) => {
  if (!isDebugEnabled()) {
    return;
  }
  PW.log('[Settings Debug]', ...args);
};


/**
 * Generic handler for radio button groups in settings.
 * @param {string} name - Name attribute of the radio inputs.
 * @param {string} endpoint - API endpoint for the update.
 * @param {string} messageTemplate - Message template with {value} placeholder.
 */
const handleRadioGroup = (name, endpoint, messageTemplate) => {
  PC.queryAll(`input[name="${name}"]`).forEach(radioButton => {
    radioButton.addEventListener('change', () => {
      const value = PC.query(`input[name="${name}"]:checked`).value;
      const checkedRadio = PC.query(`input[name="${name}"]:checked`);
      const label = checkedRadio
        ? (document.querySelector(`label[for="${checkedRadio.id}"]`)?.textContent?.trim() || value)
        : value;
      const formData = new FormData();
      formData.append(name, value);


      // Find and append CSRF token from the nearest form
      const form = radioButton.closest('form');
      if (form) {
        const csrfInput = form.querySelector('input[name="csrf_token"]');
        if (csrfInput) {
          formData.append('csrf_token', csrfInput.value);
        }
      }

      const submitPromise = PC.updateResource(endpoint, formData);

      submitPromise.then(() => {
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


document.addEventListener("DOMContentLoaded", async () => {

  const initDelayedHoverHelp = () => {
    const targets = Array.from(document.querySelectorAll('[data-hover-help]'));
    if (targets.length === 0) {
      return;
    }

    const tooltipEl = document.createElement('div');
    tooltipEl.className = 'hover_help_tooltip';
    tooltipEl.setAttribute('role', 'tooltip');
    tooltipEl.setAttribute('aria-hidden', 'true');
    document.body.appendChild(tooltipEl);

    let showTimer = null;
    let activeTarget = null;

    const clearShowTimer = () => {
      if (showTimer !== null) {
        window.clearTimeout(showTimer);
        showTimer = null;
      }
    };

    const hideTooltip = () => {
      clearShowTimer();
      activeTarget = null;
      tooltipEl.classList.remove('is-visible');
      tooltipEl.setAttribute('aria-hidden', 'true');
    };

    const positionTooltip = (targetEl) => {
      if (!targetEl) {
        return;
      }
      const margin = '1.5rem';
      tooltipEl.style.bottom = margin;
      tooltipEl.style.right = margin;
      tooltipEl.style.top = 'auto';
      tooltipEl.style.left = 'auto';
    };

    const showTooltip = (targetEl) => {
      const helpText = (targetEl?.getAttribute('data-hover-help') || '').trim();
      if (!helpText) {
        return;
      }

      activeTarget = targetEl;
      tooltipEl.textContent = helpText;
      tooltipEl.classList.add('is-visible');
      tooltipEl.setAttribute('aria-hidden', 'false');
      positionTooltip(targetEl);
    };

    const scheduleShow = (targetEl) => {
      clearShowTimer();
      showTimer = window.setTimeout(() => {
        showTimer = null;
        showTooltip(targetEl);
      }, 250);
    };

    targets.forEach((targetEl) => {
      targetEl.addEventListener('mouseenter', () => scheduleShow(targetEl));
      targetEl.addEventListener('mouseleave', hideTooltip);
      targetEl.addEventListener('focus', () => scheduleShow(targetEl));
      targetEl.addEventListener('blur', hideTooltip);
      targetEl.addEventListener('mousedown', hideTooltip);
    });

    window.addEventListener('scroll', () => {
      if (activeTarget && tooltipEl.classList.contains('is-visible')) {
        positionTooltip(activeTarget);
      }
    }, true);

    window.addEventListener('resize', () => {
      if (activeTarget && tooltipEl.classList.contains('is-visible')) {
        positionTooltip(activeTarget);
      }
    });
  };

  initDelayedHoverHelp();

  const params = new URLSearchParams(window.location.search);

  if (params.get('passkey_onboarding') === '1') {
    PC.showToast(
      'Signed in on this phone. Tap Add another device to add a local passkey for this device.',
      'save',
      10000,
      true
    );
  }

  const addPasskeyButtonEl = document.getElementById('add_passkey_button');
  const addPasskeyStatusEl = document.getElementById('add_passkey_status');
  const passkeyCredentialsListEl = document.getElementById('passkey_credentials_list');
  let passkeyCredentialsStatusEl = document.getElementById('passkey_credentials_sr_status');
  const createRecoveryKeyButtonEl = document.getElementById('create_recovery_key_btn');
  const createRecoveryKeyStatusEl = document.getElementById('create_recovery_key_status');

  const b64urlToBuffer = (b64url) => {
    const padding = '='.repeat((4 - (b64url.length % 4)) % 4);
    const base64 = (b64url + padding).replace(/-/g, '+').replace(/_/g, '/');
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i += 1) {
      bytes[i] = binary.charCodeAt(i);
    }
    return bytes.buffer;
  };

  const bufferToB64url = (input) => {
    const bytes = input instanceof ArrayBuffer ? new Uint8Array(input) : new Uint8Array(input.buffer);
    let binary = '';
    bytes.forEach((b) => { binary += String.fromCharCode(b); });
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
  };

  const WEB_AUTHN_UNSUPPORTED_MESSAGE = 'This browser cannot use passkeys. Use a WebAuthn-capable browser on a secure connection (HTTPS).';
  let passkeyActionHardDisabled = false;
  const isWebAuthnCapableBrowser = () => {
    const hasPublicKeyCredential = typeof window.PublicKeyCredential !== 'undefined';
    const hasCredentialsApi = typeof navigator.credentials !== 'undefined' && navigator.credentials !== null;
    const hasGet = hasCredentialsApi && typeof navigator.credentials.get === 'function';
    const hasCreate = hasCredentialsApi && typeof navigator.credentials.create === 'function';
    return window.isSecureContext && hasPublicKeyCredential && hasCredentialsApi && hasGet && hasCreate;
  };

  const normalizeBootstrapData = (payload) => {
    if (!payload || typeof payload !== 'object') {
      return {};
    }

    return payload;
  };

  const recoveryCryptoBridge = (() => {
    let worker = null;
    let requestId = 1;
    let hasDek = false;
    let dekVersion = 1;
    let cryptoVersion = 1;

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

      worker = new Worker(workerUrl.toString());
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

    const fetchBootstrap = async () => {
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

    const ensureDEK = async () => {
      if (hasDek) {
        return true;
      }

      const bootstrap = await fetchBootstrap();
      const wrappedDekPasskey = bootstrap.wrappedDekPasskeyForCredential || '';
      const credentialId = bootstrap.credentialId || '';
      const encryptionSalt = bootstrap.encryptionSalt || '';

      if (!wrappedDekPasskey || !credentialId || !encryptionSalt) {
        throw new Error('Encrypted entries are not available yet. Open your calendar once and try again.');
      }

      await callWorker('unwrapWithPasskeyCredential', {
        wrappedDekPasskey,
        credentialId,
        userId: bootstrap.userId || '',
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

    return {
      ensureDEK,
      createRecoveryMaterial,
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


  const setPasskeyStatus = (message) => {
    if (addPasskeyStatusEl) {
      addPasskeyStatusEl.textContent = message;
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
    if (!addPasskeyButtonEl || passkeyActionHardDisabled) {
      return;
    }

    addPasskeyButtonEl.disabled = busy;
    addPasskeyButtonEl.setAttribute('aria-disabled', busy ? 'true' : 'false');
    addPasskeyButtonEl.setAttribute('aria-busy', busy ? 'true' : 'false');
    addPasskeyButtonEl.classList.toggle('is-working', busy);
    addPasskeyButtonEl.textContent = busy ? 'Setting up passkey...' : 'Add Device';
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

  const formatPasskeyTimestamp = (ts) => {
    const value = Number(ts || 0);
    if (!value || Number.isNaN(value)) {
      return 'never';
    }
    const date = new Date(value * 1000);
    return date.toLocaleString();
  };

  const renderPasskeyCredentials = (credentials = []) => {
    if (!passkeyCredentialsListEl) {
      return;
    }

    passkeyCredentialsListEl.textContent = '';

    if (!Array.isArray(credentials) || credentials.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'passkey_credential_detail';
      empty.textContent = 'No passkeys registered yet.';
      passkeyCredentialsListEl.appendChild(empty);
      setPasskeyGridStatus('Passkeys list loaded. No passkeys registered yet.');
      return;
    }

    const canRemove = credentials.length > 1;
    const table = document.createElement('div');
    table.className = 'passkey_datagrid datagrid_no_chrome';
    table.setAttribute('role', 'grid');
    table.setAttribute('aria-colcount', canRemove ? '3' : '2');
    table.setAttribute('aria-rowcount', String(credentials.length + 1));
    table.setAttribute('aria-describedby', 'passkey_credentials_sr_instructions passkey_credentials_sr_status');
    
    // Set grid to 2 or 3 columns based on credential count
    if (canRemove) {
      table.classList.add('passkey_datagrid_3col');
    }

    const header = document.createElement('div');
    header.className = 'passkey_datagrid_row passkey_datagrid_header';
    header.setAttribute('role', 'row');
    if (canRemove) {
      PC.setHTML(header, '<div role="columnheader" id="passkey_col_name">Passkey</div><div role="columnheader" id="passkey_col_date">Date</div><div role="columnheader" id="passkey_col_actions">Actions</div>');
    } else {
      PC.setHTML(header, '<div role="columnheader" id="passkey_col_name">Passkey</div><div role="columnheader" id="passkey_col_date">Date</div>');
    }
    table.appendChild(header);

    credentials.forEach((credential) => {
      const row = document.createElement('div');
      row.className = 'passkey_datagrid_row';
      row.setAttribute('role', 'row');

      const nameCell = document.createElement('div');
      nameCell.className = 'passkey_credential_name';
      nameCell.setAttribute('role', 'gridcell');
      nameCell.setAttribute('aria-labelledby', 'passkey_col_name');
      nameCell.contentEditable = 'true';
      nameCell.textContent = credential.deviceName || 'Passkey';
      nameCell.setAttribute('data-credential-id', String(credential.credentialId || ''));
      nameCell.setAttribute('spellcheck', 'false');
      nameCell.addEventListener('blur', async (e) => {
        const target = e.target;
        if (!(target instanceof HTMLElement)) return;
        const newName = target.textContent?.trim() || 'Passkey';
        const credId = target.getAttribute('data-credential-id') || '';
        if (newName && credId) {
          await updatePasskeyName(credId, newName);
        }
      });
      nameCell.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          const target = e.target;
          if (target instanceof HTMLElement) {
            target.blur();
          }
        }
      });

      const dateCell = document.createElement('div');
      dateCell.className = 'passkey_credential_detail';
      dateCell.setAttribute('role', 'gridcell');
      dateCell.setAttribute('aria-labelledby', 'passkey_col_date');
      dateCell.textContent = formatPasskeyTimestamp(credential.lastUsedAt);

      row.appendChild(nameCell);
      row.appendChild(dateCell);

      if (canRemove) {
        const actionCell = document.createElement('div');
        actionCell.setAttribute('role', 'gridcell');
        actionCell.setAttribute('aria-labelledby', 'passkey_col_actions');
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn btn_delete';
        removeButton.textContent = <?php echo json_encode(Strings::i18n('REMOVE'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>;
        removeButton.addEventListener('click', () => {
          removePasskeyCredential(String(credential.credentialId || ''));
        });
        actionCell.appendChild(removeButton);
        row.appendChild(actionCell);
      }

      table.appendChild(row);
    });

    passkeyCredentialsListEl.appendChild(table);
    setPasskeyGridStatus(`Passkeys list loaded. ${credentials.length} passkey${credentials.length === 1 ? '' : 's'} available.`);
  };

  const refreshPasskeyCredentials = async () => {
    setPasskeyGridStatus('Passkeys list loaded. Checking passkeys...');

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

      // Keep Add Passkey visible to support per-device enrollment.
      if (addPasskeyButtonEl) {
        addPasskeyButtonEl.hidden = false;
      }

      renderPasskeyCredentials(listPayload.credentials || []);
    } catch (error) {
      setPasskeyStatus('Unable to load passkeys. Try again.');
      setPasskeyGridStatus('Unable to load passkeys. Try again.');
      PW.error(error);
    }
  };

  /**
   * Rename a passkey label shown to the user.
   * This only changes display metadata, not cryptographic keys.
   */
  const updatePasskeyName = async (credentialId, newName) => {
    try {
      setPasskeyGridStatus('Updating passkey name...');
      
      const response = await fetch('/api/v1/auth/passkey/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ credentialId, newName }),
      });

      if (!response.ok) {
        setPasskeyGridStatus('Unable to update passkey name.');
        PW.error(`[PASSKEY] Update failed: ${response.status}`);
        return;
      }

      const result = await response.json();
      if (result.status !== 'success') {
        setPasskeyGridStatus('Unable to update passkey name.');
        PW.error(`[PASSKEY] Update failed: ${result.message || 'unknown error'}`);
      } else {
        setPasskeyGridStatus('Passkey name updated.');
      }
    } catch (err) {
      setPasskeyGridStatus('Unable to update passkey name.');
      PW.error(err);
    }
  };

  const removePasskeyCredential = async (credentialId) => {
    if (!credentialId) {
      return;
    }

    try {
      setPasskeyStatus('Working…');
      setPasskeyGridStatus('Removing passkey...');
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

      setPasskeyStatus('Passkey removed.');
      await refreshPasskeyCredentials();
    } catch (error) {
      setPasskeyStatus(error?.message || 'Unable to update passkeys. Try again.');
      setPasskeyGridStatus('Unable to update passkeys. Try again.');
      PW.error(error);
    }
  };

  const addPasskeyAction = async () => {
    if (!isWebAuthnCapableBrowser()) {
      setPasskeyStatus(WEB_AUTHN_UNSUPPORTED_MESSAGE);
      setPasskeyGridStatus(WEB_AUTHN_UNSUPPORTED_MESSAGE);
      return;
    }

    // Avoid blocking click handler timing with a synchronous prompt.
    const deviceName = 'Passkey';

    setPasskeyStatus('Working…');
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

    setPasskeyStatus('Confirm on your device…');
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

    // [CRYPTO] After successful registration, wrap DEK with passkey KEK
    // This requires an immediate authentication to get the assertion signature
    setPasskeyStatus('Securing your data…');
    setPasskeyGridStatus('Securing your data with the new passkey...');
    try {
      await wrapDEKWithNewPasskey();
      setPasskeyStatus('Passkey added.');
      setPasskeyGridStatus('Passkey added successfully. Refreshing passkeys list...');
    } catch (dekWrapError) {
      PW.error(dekWrapError);
      setPasskeyStatus('Passkey added. Open your calendar once to complete setup.');
      setPasskeyGridStatus('Passkey added. Open your calendar once to complete setup.');
    }

    await refreshPasskeyCredentials();
  };

  /**
   * After passkey registration, securely attach the existing data key (DEK) to that passkey.
   *
   * Plain-language behavior:
   * 1) Ask server for a passkey challenge.
   * 2) User confirms with passkey.
   * 3) Server returns stable credential ID.
   * 4) Fetch encryption salt.
   * 5) Wrap DEK using credential ID + salt so future unlock is deterministic.
   */
  async function wrapDEKWithNewPasskey() {
    debugLog('[PASSKEY] wrapDEKWithNewPasskey: starting DEK wrapping process (stable credential_id)...');

    if (!isWebAuthnCapableBrowser()) {
      throw new Error(WEB_AUTHN_UNSUPPORTED_MESSAGE);
    }
    
    // Check if DEK is available (calendar must have initialized encryption first)
    if (!window.PayCalCrypto?.hasDek) {
      throw new Error('[PASSKEY] DEK not available. Please unlock encrypted entries first.');
    }

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

    debugLog('[PASSKEY] Step 3 complete: credential_id received (stable)');

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
    if (window.PayCalCrypto?.wrapDEKWithPasskeyCredential) {
      await window.PayCalCrypto.wrapDEKWithPasskeyCredential(
        loginFinishPayload.data.credential_id,
        bootstrapData.encryptionSalt
      );
      debugLog('[PASSKEY] Step 5 complete: DEK wrapped and uploaded (deterministic unwrap enabled)');
    } else {
      throw new Error('[PASSKEY] DEK wrapping API not available.');
    }
  };

  const createRecoveryKeyAction = async () => {
    const recoveryCrypto = getRecoveryCryptoApi();

    createRecoveryKeyButtonEl?.setAttribute('disabled', 'disabled');
    setRecoveryKeyStatus('Unlocking encrypted entries...', 'info');

    try {
      const unlocked = await recoveryCrypto.ensureDEK();
      if (!unlocked || !recoveryCrypto.hasDek) {
        throw new Error('Encrypted entries are locked. Open your calendar once or sign in again, then retry.');
      }

      setRecoveryKeyStatus('Working…', 'info');
      let material = await recoveryCrypto.createRecoveryMaterial();
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
        throw new Error(payload.message || 'Unable to create Recovery Key.');
      }

      setRecoveryKeyStatus('Recovery Key created. Check your email.', 'success');
    } finally {
      createRecoveryKeyButtonEl?.removeAttribute('disabled');
    }
  };

  if (addPasskeyButtonEl) {
    if (!isWebAuthnCapableBrowser()) {
      passkeyActionHardDisabled = true;
      addPasskeyButtonEl.disabled = true;
      addPasskeyButtonEl.setAttribute('aria-disabled', 'true');
      setPasskeyStatus(WEB_AUTHN_UNSUPPORTED_MESSAGE);
      setPasskeyGridStatus(WEB_AUTHN_UNSUPPORTED_MESSAGE);
    }

    addPasskeyButtonEl.addEventListener('click', async () => {
      setAddPasskeyBusyState(true);
      try {
        await addPasskeyAction();
      } catch (error) {
        const errorMessage = normalizeErrorMessage(error);
        setPasskeyStatus(errorMessage);
        setPasskeyGridStatus(`Passkey add failed: ${errorMessage}`);
        PW.error(`[PASSKEY] Add device failed: ${errorMessage}`);
      } finally {
        setAddPasskeyBusyState(false);
      }
    });
  }

  if (createRecoveryKeyButtonEl) {
    createRecoveryKeyButtonEl.addEventListener('click', () => {
      createRecoveryKeyAction().catch((error) => {
        setRecoveryKeyStatus(error?.message || 'Unable to create Recovery Key. Try again.', 'error');
        PW.error(error);
      });
    });
  }

  refreshPasskeyCredentials();

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
      prevBtn.textContent = showStep2 ? 'Previous' : 'Cancel';
    }

    updateChangeEmailVerifyState();
  };

  const normalizeVerificationCode = (value) => String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);

  const setFieldInvalidState = (input, isInvalid) => {
    if (!input) {
      return;
    }

    if (isInvalid) {
      input.setAttribute('aria-invalid', 'true');
    } else {
      input.removeAttribute('aria-invalid');
    }
  };

  const setFieldErrorMessage = (errorId, message) => {
    const errorEl = PC.getElement(errorId);
    if (!errorEl) {
      return;
    }

    errorEl.textContent = String(message || '').trim();
  };

  const setFieldErrorState = (input, errorId, message) => {
    const text = String(message || '').trim();
    setFieldInvalidState(input, text.length > 0);
    setFieldErrorMessage(errorId, text);
  };

  const clearFieldErrorStates = (pairs) => {
    pairs.forEach(([inputId, errorId]) => {
      setFieldErrorState(PC.getElement(inputId), errorId, '');
    });
  };

  const clearFieldInvalidStates = (ids) => {
    ids.forEach((id) => setFieldInvalidState(PC.getElement(id), false));
  };

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

  attachChangeEmailCodeInputHandlers();

  PC.addClickAndEnterListener('change_email_prev_btn', (e) => {
    e.preventDefault();
    const step2 = PC.getElement('change_email_step2_section');
    if (step2 && !step2.hidden) {
      toggleChangeEmailStep(false);
      return;
    }

    resetChangeEmailModal();
    PC.closeModal('modal_change_email', 'Change Email');
  });

  if (document.getElementById('call_edit_details_modal')) {
    PC.addClickAndEnterListener('call_edit_details_modal', (e) => { e.preventDefault(); PC.openModal('modal_edit_details', 'Account Details'); });
  }
  PC.addClickAndEnterListener('edit_details_cancel_btn', (e) => { e.preventDefault(); PC.closeModal('modal_edit_details', 'Account Details'); });
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
      PC.closeModal('modal_edit_details', 'Account Details');
      PC.openModal('modal_change_email', 'Change Email');
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
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ new_email: newEmail }),
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
        if (expiry) expiry.textContent = `Codes expire in ${data.expires_in_minutes} minutes`;
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.codesSent;

        toggleChangeEmailStep(true);
        setTimeout(() => PC.getElement('change_email_old_code')?.focus(), 50);
      } else {
        const apiMessage = data && typeof data.message === 'string' ? data.message : '';
        const fallback = raw ? raw.slice(0, 180) : `HTTP ${response.status}`;
        if (statusEl) statusEl.textContent = apiMessage || `Failed to send codes. ${fallback}`;
      }
    } catch (error) {
      if (statusEl) statusEl.textContent = `${CHANGE_EMAIL_I18N.requestFailedPrefix} ${String(error?.message || 'unknown error')}`;
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
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ txn_id: txnId, old_code: oldCode, new_code: newCode }),
      });
      const { data, raw } = await parseApiResponse(response);

      if (response.ok && data && data.status === 'success') {
        setFieldErrorState(oldCodeInput, 'change_email_old_code_error', '');
        setFieldErrorState(newCodeInput, 'change_email_new_code_error', '');
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.emailUpdated;
        setTimeout(() => {
          PC.closeModal('modal_change_email', 'Change Email');
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
      if (statusEl) statusEl.textContent = `${CHANGE_EMAIL_I18N.requestFailedPrefix} ${String(error?.message || 'unknown error')}`;
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
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ txn_id: txnId }),
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
      if (statusEl) statusEl.textContent = `${CHANGE_EMAIL_I18N.requestFailedPrefix} ${String(error?.message || 'unknown error')}`;
      PW.error(error);
    }
  });

  PC.addClickAndEnterListener('call_delete_account_modal', (e) => {
    e.preventDefault();
    const editDetailsModal = PC.getElement('modal_edit_details');
    if (editDetailsModal?.open) {
      PC.closeModal('modal_edit_details', 'Account Details');
    }
    PC.openModal('modal_delete_account', 'Delete Account');
    PC.getElement('delete_account_confirm_phrase').focus();
  });
  PC.addClickAndEnterListener('delete_account_cancel_btn', (e) => { e.preventDefault(); PC.closeModal('modal_delete_account', 'Delete Account'); });


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
          deleteStatus.textContent = 'Type DELETE MY ACCOUNT exactly to confirm account deletion.';
        }
        setFieldErrorState(deleteConfirmInput, 'delete_account_confirm_error', 'Type DELETE MY ACCOUNT exactly to confirm account deletion.');
        deleteConfirmInput.focus();
        deleteConfirmInput.select();
      } else {
        setFieldErrorState(deleteConfirmInput, 'delete_account_confirm_error', '');
      }
    });
  }

  const editDetailsPhone = PC.getElement('edit_details_phone');
  if (editDetailsPhone) {
    PC.formatPhoneNumber(editDetailsPhone);
    editDetailsPhone.addEventListener('input', (e) => {
      PC.formatPhoneNumber(e.target);
    });
    editDetailsPhone.addEventListener('change', (e) => {
      PC.formatPhoneNumber(e.target);
    });
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
      if (payRate.length > 0 && !/^\d+(\.\d{1,2})?$/.test(payRate)) {
        markInvalid(payRateInput, 'edit_details_pay_rate_error', 'Enter a valid pay rate (for example 25 or 25.50).');
      }

      const payRateType = String(payRateTypeInput?.value || '').trim();
      if (payRateType.length > 0 && !['hourly', 'salary', 'day_rate'].includes(payRateType)) {
        markInvalid(payRateTypeInput, 'edit_details_pay_rate_type_error', 'Select a valid pay rate type.');
      }

      if (firstInvalidField) {
        if (editDetailsStatus) {
          editDetailsStatus.textContent = 'Please correct the highlighted fields and try again.';
        }
        firstInvalidField.focus();
        return false;
      }

      return true;
    };

    const updatePanelField = (panelEl, value) => {
      if (!panelEl) {
        return;
      }
      if ('value' in panelEl) {
        panelEl.value = value;
        return;
      }
      panelEl.textContent = value;
    };

    editDetailsForm.addEventListener('submit', (e) => {
      e.preventDefault();

      if (!validateEditDetailsForm()) {
        return;
      }

      if (editDetailsStatus) {
        editDetailsStatus.textContent = <?php echo json_encode($i18n['UPDATING_INFO'] . '...', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>;
      }

      const formData = new FormData(editDetailsForm);
      PC.updateResource('account/info', formData).then(() => {
        clearEditDetailsValidationState();
        const panelName = PC.getElement('label_full_name');
        const panelPhone = PC.getElement('label_phone');
        const panelProvince = PC.getElement('label_province');
        const panelTimezone = PC.getElement('timezone_picker');

        const modalName = PC.getElement('edit_details_full_name');
        const modalPhone = PC.getElement('edit_details_phone');
        const modalProvince = PC.getElement('edit_details_province');
        const modalTimezone = PC.getElement('edit_details_timezone_picker');

        if (panelName && modalName) updatePanelField(panelName, modalName.value);
        if (panelPhone && modalPhone) updatePanelField(panelPhone, modalPhone.value);
        if (panelProvince && modalProvince) {
          const provinceLabel = modalProvince.options[modalProvince.selectedIndex]?.text || modalProvince.value;
          updatePanelField(panelProvince, provinceLabel);
        }
        if (panelTimezone && modalTimezone) {
          const timezoneLabel = modalTimezone.options[modalTimezone.selectedIndex]?.text || modalTimezone.value;
          updatePanelField(panelTimezone, timezoneLabel);
        }

        if (editDetailsStatus) {
          editDetailsStatus.textContent = <?php echo json_encode($i18n['INFO_UPDATED'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>;
        }
        PC.closeModal('modal_edit_details', 'Account Details');
      }).catch(error => {
        if (editDetailsStatus) {
          editDetailsStatus.textContent = 'Unable to save account details right now. Please try again.';
        }
        PW.error(error);
      });
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

  const getSettingsCsrfToken = () => {
    const form = PC.getElement('edit_details_form');
    const formToken = form?.querySelector('input[name="csrf_token"]');
    if (formToken && typeof formToken.value === 'string' && formToken.value !== '') {
      return formToken.value;
    }

    const fallback = document.querySelector('input[name="csrf_token"]');
    return fallback && typeof fallback.value === 'string' ? fallback.value : '';
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
      sendBtn.textContent = 'Send';
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
      expiryEl.textContent = `Code expires in ${minutes}:${String(seconds).padStart(2, '0')}`;
      
      setTimeout(updateExpiryDisplay, 1000);
    } else {
      expiryEl.textContent = 'Code has expired. Please request a new one.';
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
    if (!statusEl) return;

    statusEl.classList.remove(
      'status_message_error',
      'status_message_muted',
      'status_message_info',
      'status_message_success'
    );

    if (state) {
      statusEl.classList.add(`status_message_${state}`);
    }
  };

  const sendRecoveryEmailCode = async () => {
    const emailInput = PC.getElement('recovery_email_input');
    const statusEl = PC.getElement('recovery_email_send_status');

    if (!emailInput || !statusEl) return;

    const email = emailInput.value.trim();
    const csrfToken = getSettingsCsrfToken();
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

    const code = codeInput.value.trim();
    const csrfToken = getSettingsCsrfToken();
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
    const csrfToken = getSettingsCsrfToken();

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
  handleRadioGroup('calendar_audio_labels', 'settings/calendar', <?php echo json_encode($i18n['UPDATING_CALENDAR_AUDIO_LABELS_TO'] . ' {value}', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);
  handleRadioGroup('calendar_date_label_position', 'settings/calendar', <?php echo json_encode($i18n['UPDATING_CALENDAR_DATE_LABEL_POSITION_TO'] . ' {value}', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);
  handleRadioGroup('calendar_work_entry_position', 'settings/calendar', <?php echo json_encode($i18n['UPDATING_CALENDAR_WORK_ENTRY_POSITION_TO'] . ' {value}', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);

  handleRadioGroup('debug_console_enabled', 'settings/debug', 'Console Messages: {label}');
  handleRadioGroup('debug_fine_grained_enabled', 'settings/debug', 'Detailed Diagnostics: {label}');
  handleRadioGroup('debug_network_enabled', 'settings/debug', 'Network Insights: {label}');

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
      formData.append('calendar_work_entry_fields_overtime', PC.query('input[name="calendar_work_entry_fields_overtime"]')?.checked ? '1' : '0');
      formData.append('calendar_work_entry_fields_living_out', PC.query('input[name="calendar_work_entry_fields_living_out"]')?.checked ? '1' : '0');
      formData.append('calendar_work_entry_fields_travel', PC.query('input[name="calendar_work_entry_fields_travel"]')?.checked ? '1' : '0');
      
      // Get CSRF token from the calendar form
      let csrfToken = null;
      const form = checkbox.closest('form');
      if (form) {
        const csrfInput = form.querySelector('input[name="csrf_token"]');
        if (csrfInput) csrfToken = csrfInput.value;
      }
      // Fallback: try to find CSRF in the document
      if (!csrfToken) {
        const csrfFallback = document.querySelector('input[name="csrf_token"]');
        if (csrfFallback) csrfToken = csrfFallback.value;
      }
      if (csrfToken) {
        formData.append('csrf_token', csrfToken);
      }

      const submitPromise = PC.updateResource('settings/calendar', formData);

      submitPromise.then(() => {
        PC.showToast('Work entry fields updated', 'save');
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
      PC.state.audio_feedback = PC.query('input[name="audio_feedback"]:checked').value;
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
      const audioForm = PC.getElement('account_audio_form');
      let csrfToken = null;
      if (audioForm) {
        const csrfInput = audioForm.querySelector('input[name="csrf_token"]');
        if (csrfInput) csrfToken = csrfInput.value;
      }
      // Fallback: try to find CSRF in the document
      if (!csrfToken) {
        const csrfFallback = document.querySelector('input[name="csrf_token"]');
        if (csrfFallback) csrfToken = csrfFallback.value;
      }
      if (csrfToken) {
        formData.append('csrf_token', csrfToken);
      }
      PC.updateResource('settings/audio', formData).then(() => {
        const modeLabel = PC.state.audio_feedback === 'none' ? 'Muted' : 'Enabled';
        PC.showToast(`Audio ${modeLabel.toLowerCase()}`, 'save', 3000, true);
        if (previousAudioFeedback !== 'all' && PC.state.audio_feedback === 'all') {
          PC.textToSpeech('Audio enabled');
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

    // Add CSRF token
    const audioForm = PC.getElement('account_audio_form');
    let csrfToken = null;
    if (audioForm) {
      const csrfInput = audioForm.querySelector('input[name="csrf_token"]');
      if (csrfInput) csrfToken = csrfInput.value;
    }
    // Fallback: try to find CSRF in the document
    if (!csrfToken) {
      const csrfFallback = document.querySelector('input[name="csrf_token"]');
      if (csrfFallback) csrfToken = csrfFallback.value;
    }
    if (csrfToken) {
      formData.append('csrf_token', csrfToken);
    }

    PC.updateResource('settings/audio', formData).then(() => {
      PC.showToast(`Voice updated to ${voiceLabel}`, 'save', 3000, true);
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

  /* STYLE */
  const themePicker = PC.getElement('theme_picker');
  const variantPicker = PC.getElement('variant_picker');
  const languagePicker = PC.getElement('language_picker');
  const styleForm = PC.getElement('account_style_form');
  const textSlider = PC.getElement('text_slider');
  const densitySlider = PC.getElement('density_slider');
  const textSliderValue = PC.getElement('text_slider_value');
  const densitySliderValue = PC.getElement('density_slider_value');

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

  const clampSliderAdjustment = (value) => {
    const parsed = Number.parseInt(String(value), 10);
    if (!Number.isFinite(parsed)) {
      return 0;
    }

    return Math.max(-5, Math.min(5, parsed));
  };

  const toAdjustmentLabel = (value) => {
    const normalized = clampSliderAdjustment(value);
    return `${normalized > 0 ? '+' : ''}${normalized}px`;
  };

  const applyRootScaleAdjustment = (group, value) => {
    const root = document.documentElement;
    const normalized = clampSliderAdjustment(value);
    if (group === 'text') {
      root.style.setProperty('--text-adjustment-px', `${normalized}px`);
      return;
    }

    if (group === 'density') {
      root.style.setProperty('--density-adjustment-px', `${normalized}px`);
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
    const theme = themePicker.value;
    const variant = variantPicker.value;
    if (theme !== 'choose' && variant) {
      lockChooseOption(themePicker);
      const formData = new FormData();
      formData.append('theme', theme);
      formData.append('variant', variant);
      // Add CSRF token
      const csrf = styleForm.querySelector('input[name="csrf_token"]');
      if (csrf) formData.append('csrf_token', csrf.value);
      PC.updateResource('settings/style', formData).then(() => {
        PC.showToast(`Theme updated to ${theme} (${variant})`, 'save', 3000, true);
        if (PC.state.audio_feedback === 'all') {
          PC.textToSpeech(`${theme} ${variant}`);
        }
        refreshCoreStylesheet();
      }).catch(error => PW.error(error));
    }
  }

  themePicker.addEventListener('change', submitStyleChange);
  variantPicker.addEventListener('change', submitStyleChange);
  if (languagePicker instanceof HTMLSelectElement) {
    languagePicker.addEventListener('change', () => {
      const language = languagePicker.value;
      if (language !== 'choose') {
        lockChooseOption(languagePicker);
        const formData = new FormData();
        formData.append('language', language);
        // Add CSRF token
        const csrf = document.querySelector('#account_style_form input[name="csrf_token"]');
        if (csrf) formData.append('csrf_token', csrf.value);
        PC.updateResource('settings/style', formData).then(() => {
          const langName = PC.getLanguageName(language);
          PC.showToast(`Language updated`, 'save', 3000, true);
          if (PC.state.audio_feedback === 'all') {
            PC.textToSpeech(langName);
          }
          window.location.hash = '#preferences';
          PC.delay(1).then(() => { window.location.reload(); });
        }).catch(error => PW.error(error));
      }
    });
  }
  const submitSliderPreference = (fieldName, sliderEl, valueEl, toastLabel) => {
    if (!(sliderEl instanceof HTMLInputElement)) {
      return;
    }

    const normalized = String(clampSliderAdjustment(sliderEl.value));
    if (valueEl) {
      valueEl.textContent = toAdjustmentLabel(normalized);
    }

    applyRootScaleAdjustment(fieldName, normalized);

    const formData = new FormData();
    formData.append(fieldName, normalized);
    const csrf = document.querySelector('#account_style_form input[name="csrf_token"]');
    if (csrf) formData.append('csrf_token', csrf.value);
    PC.updateResource('settings/style', formData).then(() => {
      PC.showToast(`${toastLabel} updated`, 'save', 3000, true);
      if (PC.state.audio_feedback === 'all') {
        PC.textToSpeech(toAdjustmentLabel(normalized));
      }
    }).catch(error=> PW.error(error));
  };

  if (textSlider instanceof HTMLInputElement) {
    textSlider.addEventListener('input', () => {
      if (textSliderValue) {
        textSliderValue.textContent = toAdjustmentLabel(textSlider.value);
      }
      applyRootScaleAdjustment('text', textSlider.value);
    });

    textSlider.addEventListener('change', () => {
      submitSliderPreference('text', textSlider, textSliderValue, 'Text size');
    });

    applyRootScaleAdjustment('text', textSlider.value);
    if (textSliderValue) {
      textSliderValue.textContent = toAdjustmentLabel(textSlider.value);
    }
  }

  if (densitySlider instanceof HTMLInputElement) {
    densitySlider.addEventListener('input', () => {
      if (densitySliderValue) {
        densitySliderValue.textContent = toAdjustmentLabel(densitySlider.value);
      }
      applyRootScaleAdjustment('density', densitySlider.value);
    });

    densitySlider.addEventListener('change', () => {
      submitSliderPreference('density', densitySlider, densitySliderValue, 'Density');
    });

    applyRootScaleAdjustment('density', densitySlider.value);
    if (densitySliderValue) {
      densitySliderValue.textContent = toAdjustmentLabel(densitySlider.value);
    }
  }
  Array.from(PC.queryAll('input[name="dyslexia_typography"]')).forEach(radioButton => {
    radioButton.addEventListener('change', function() {
      const preference = PC.query('input[name="dyslexia_typography"]:checked').value;
      const checkedRadio = PC.query('input[name="dyslexia_typography"]:checked');
      const label = checkedRadio ? document.querySelector(`label[for="${checkedRadio.id}"]`) : null;
      const spokenLabel = label ? label.textContent.trim() : preference;
      const formData = new FormData();
      formData.append('dyslexia_typography', preference);
      const csrf = document.querySelector('#account_style_form input[name="csrf_token"]');
      if (csrf) formData.append('csrf_token', csrf.value);
      PC.updateResource('settings/style', formData).then(() => {
        PC.showToast('Typography preference updated', 'save', 3000, true);
        if (PC.state.audio_feedback === 'all') {
          PC.textToSpeech(spokenLabel);
        }
        applyRootAccessibilityPreference('data-a11y-dyslexia-typography', preference);
      }).catch(error=> PW.error(error));
    });
  });
  const submitNavPositionPreference = (fieldName, attributeName, statusLabel) => {
    const checked = PC.query(`input[name="${fieldName}"]:checked`);
    if (!checked) {
      return;
    }

    const value = checked.value;
    const formData = new FormData();
    formData.append(fieldName, value);

    const styleForm = PC.getElement('account_style_form');
    const csrfInput = styleForm ? styleForm.querySelector('input[name="csrf_token"]') : null;
    if (csrfInput) {
      formData.append('csrf_token', csrfInput.value);
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
  const canonicalWeekdayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  const weekdayFullNames = <?php echo json_encode([
    Strings::i18n('WEEKDAY_SUNDAY'),
    Strings::i18n('WEEKDAY_MONDAY'),
    Strings::i18n('WEEKDAY_TUESDAY'),
    Strings::i18n('WEEKDAY_WEDNESDAY'),
    Strings::i18n('WEEKDAY_THURSDAY'),
    Strings::i18n('WEEKDAY_FRIDAY'),
    Strings::i18n('WEEKDAY_SATURDAY'),
  ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>;
  const dayNames = (() => {
    try {
      const locale = document.documentElement.lang || undefined;
      const formatter = new Intl.DateTimeFormat(locale, { weekday: 'short' });
      const sunday = new Date(Date.UTC(2026, 0, 4));

      return canonicalWeekdayNames.map((_, index) => formatter.format(new Date(sunday.getTime() + (index * 86400000))));
    } catch {
      return ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    }
  })();
  const weekdayMap = canonicalWeekdayNames.reduce((map, dayName, index) => {
    map[dayName] = index;
    return map;
  }, {});
  const parseYmd = (ymd) => new Date(`${ymd}T00:00:00`);
  const addDays = (d, n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
  const formatYmd = (d) => {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  };
  const alignToAnchor = (start, anchor) => {
    const target = weekdayMap[anchor] ?? 1;
    let cursor = new Date(start.getFullYear(), start.getMonth(), start.getDate());
    while (cursor.getDay() !== target) {
      cursor = addDays(cursor, -1);
    }
    return cursor;
  };
  const nextPeriod = (start, frequency) => {
    if (frequency === 'weekly') return addDays(start, 7);
    if (frequency === 'biweekly') return addDays(start, 14);
    if (frequency === 'semimonthly') {
      if (start.getDate() <= 15) return new Date(start.getFullYear(), start.getMonth(), 16);
      return new Date(start.getFullYear(), start.getMonth() + 1, 1);
    }
    return new Date(start.getFullYear(), start.getMonth() + 1, 1);
  };
  const periodEndExclusive = (start, frequency) => nextPeriod(start, frequency);
  const currentPeriod = (startRaw, frequency, anchor) => {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    if (frequency === 'weekly') {
      const start = alignToAnchor(today, anchor);
      return { start, endExclusive: addDays(start, 7) };
    }
    if (frequency === 'biweekly') {
      let start = alignToAnchor(parseYmd(startRaw), anchor);
      while (today < start) start = addDays(start, -14);
      while (today >= addDays(start, 14)) start = addDays(start, 14);
      return { start, endExclusive: addDays(start, 14) };
    }
    if (frequency === 'semimonthly') {
      if (today.getDate() <= 15) {
        const start = new Date(today.getFullYear(), today.getMonth(), 1);
        return { start, endExclusive: new Date(today.getFullYear(), today.getMonth(), 16) };
      }
      const start = new Date(today.getFullYear(), today.getMonth(), 16);
      return { start, endExclusive: new Date(today.getFullYear(), today.getMonth() + 1, 1) };
    }
    const start = new Date(today.getFullYear(), today.getMonth(), 1);
    return { start, endExclusive: new Date(today.getFullYear(), today.getMonth() + 1, 1) };
  };
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

    const resolved = currentPeriod(startRaw, frequency, anchor);
    startControl.value = formatYmd(resolved.start);
  };
  const startOfWeek = (d) => addDays(d, -d.getDay());
  const inRange = (d, start, endExclusive) => d >= start && d < endExclusive;
  const monthLabel = (d) => d.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
  const buildRibbonCalendar = (periods, graceDays, today) => {
    const header = dayNames.map((d) => `<th class="pp_day_head">${d}</th>`).join('');
    const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const gridStart = startOfWeek(firstOfMonth);
    const badgesPlaced = { p1: false, p2: false };
    let bodyRows = '';
    for (let week = 0; week < 6; week += 1) {
      bodyRows += '<tr>';
      for (let day = 0; day < 7; day += 1) {
        const offset = (week * 7) + day;
        const cellDate = addDays(gridStart, offset);
        const isToday = formatYmd(cellDate) === formatYmd(today);
        const classes = ['pp_day_cell'];
        let badge = '';
        periods.forEach((period, idx) => {
          const periodKey = idx === 0 ? 'p1' : 'p2';
          const prevDate = addDays(cellDate, -1);
          const nextDate = addDays(cellDate, 1);
          const active = inRange(cellDate, period.start, period.endExclusive);
          const prevActive = inRange(prevDate, period.start, period.endExclusive);
          const nextActive = inRange(nextDate, period.start, period.endExclusive);
          const graceStart = period.endExclusive;
          const graceEndExclusive = addDays(graceStart, graceDays);
          const graceActive = inRange(cellDate, graceStart, graceEndExclusive);
          if (active) {
            classes.push('pp_in_period', `pp_in_${periodKey}`);
            if (!prevActive || day === 0) classes.push(`pp_ribbon_start_${periodKey}`);
            if (!nextActive || day === 6) classes.push(`pp_ribbon_end_${periodKey}`);
            if (!badgesPlaced[periodKey]) {
              badge = `<span class="pp_badge ${periodKey === 'p2' ? 'pp_badge_p2' : ''}">${period.label}</span>`;
              badgesPlaced[periodKey] = true;
            }
          }
          if (graceActive && graceDays > 0) {
            const graceIndex = Math.min(graceDays, Math.max(1, Math.floor((cellDate - graceStart) / 86400000) + 1));
            classes.push('pp_grace_day', `pp_grace_${graceIndex}`, `pp_grace_${periodKey}`);
          }
        });
        if (isToday) classes.push('pp_today');
        bodyRows += `<td class="${classes.join(' ')}"><span>${String(cellDate.getDate()).padStart(2, '0')}</span>${badge}</td>`;
      }
      bodyRows += '</tr>';
    }
    return `
      <div class="pp_month_label">${monthLabel(today)}</div>
      <table class="pp_three_week">
        <thead><tr>${header}</tr></thead>
        <tbody>${bodyRows}</tbody>
      </table>
    `;
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
    const today = new Date();
    const period1 = currentPeriod(startRaw, frequency, anchor);
    const period2 = {
      start: period1.endExclusive,
      endExclusive: periodEndExclusive(period1.endExclusive, frequency),
    };
    const endInclusive1 = addDays(period1.endExclusive, -1);
    const endInclusive2 = addDays(period2.endExclusive, -1);
    const periods = [
      { label: 'P1', start: period1.start, endExclusive: period1.endExclusive },
      { label: 'P2', start: period2.start, endExclusive: period2.endExclusive },
    ];
    if (payPeriodPreviewSummary) {
      payPeriodPreviewSummary.textContent = `P1 ${formatYmd(period1.start)} → ${formatYmd(endInclusive1)}   P2 ${formatYmd(period2.start)} → ${formatYmd(endInclusive2)}`;
    }
    if (payPeriodPreviewCalendar) {
      PC.setHTML(payPeriodPreviewCalendar, buildRibbonCalendar(periods, graceDays, today));
    }
    if (payPeriodCurrentPreview) {
      payPeriodCurrentPreview.textContent = `P1 ${formatYmd(period1.start)} → ${formatYmd(endInclusive1)}   P2 ${formatYmd(period2.start)} → ${formatYmd(endInclusive2)}`;
    }
    if (payPeriodCurrentCalendar) {
      PC.setHTML(payPeriodCurrentCalendar, buildRibbonCalendar(periods, graceDays, today));
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
      securityLevelHintEl.textContent = `${preset.hint} Shorter sessions increase protection on shared devices.`;
      securitySliderEl.value = String(preset.slider);
    } else {
      securityLevelValueEl.textContent = 'Custom';
      securityLevelHintEl.textContent = 'Custom values are active. Shorter sessions increase protection on shared devices.';
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
        el.textContent = remaining > 0 ? `${formatSeconds(remaining)} remaining` : 'Expired';
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
    if (!dataPortabilityEls.status) {
      return;
    }

    dataPortabilityEls.status.classList.remove(
      'status_message_error',
      'status_message_muted',
      'status_message_info',
      'status_message_success'
    );
    dataPortabilityEls.status.classList.add(`status_message_${tone}`);
    dataPortabilityEls.status.textContent = message;
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

  const runAccountDataExport = async () => {
    dataPortabilityState.exporting = true;
    updateDataPortabilityButtons();
    setDataPortabilityStatus('Export started. Requesting account dataset from server.', 'muted');
    appendDataPortabilityLog('Export started', 'POST /api/v1/account/data/export');

    try {
      const { response, data, raw } = await postDataPortabilityForm('/api/v1/account/data/export');
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

      const payloadJson = JSON.stringify(payload, null, 2);
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
      if (exportWarning) {
        setDataPortabilityStatus(`Export completed. ${exportWarning}`, 'info');
        appendDataPortabilityLog('Export warning', exportWarning);
      } else {
        setDataPortabilityStatus('Export completed. Payload is ready to copy, download, or import.', 'success');
      }
      appendDataPortabilityLog('Export completed', `Reference ${String(normalized.reference || '-')}; ${summarizeCounts(normalized.counts)}`);
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
        dataPortabilityEls.importExpires.textContent = 'Consumed';
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
        setDataPortabilityStatus('Unable to copy payload automatically. Copy manually from the text area.', 'error');
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
        PC.openModal('modal_import_confirm', 'Confirm Import');
      } else {
        commitAccountDataImport();
      }
    });
  }

  const importConfirmProceedBtn = PC.getElement('import_confirm_proceed_btn');
  if (importConfirmProceedBtn) {
    importConfirmProceedBtn.addEventListener('click', () => {
      PC.closeModal('modal_import_confirm', 'Confirm Import');
      commitAccountDataImport();
    });
  }

  const importConfirmCancelBtn = PC.getElement('import_confirm_cancel_btn');
  if (importConfirmCancelBtn) {
    importConfirmCancelBtn.addEventListener('click', () => {
      PC.closeModal('modal_import_confirm', 'Confirm Import');
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

  await initializeBillingSection({
    successUrl: '/api/v1/billing/checkout-return',
    cancelUrl: '/profile/?billing=cancel',
    returnUrl: '/profile/#panel-billing',
  });
});
