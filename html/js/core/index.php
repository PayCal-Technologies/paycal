<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();

CORS::renderContentType('text/javascript');
Javascript::renderDocBlock();

$user = User::current();

$i18nKeys = [
  'CAPSLOCK_ACTIVE',
  'CLOSED_DIALOG',
  'KEYBOARD_SHORTCUTS',
  'OPENED_DIALOG',
  'SIGN_OUT',
  'WORK_DETAILS',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}

?>
/**
 * ============================================================================
 * PayCalCore - Application Core Utilities Module
 * ============================================================================
 *
 * INTERNAL MAINTAINER NOTES:
 * - Treat this module as the frontend orchestration layer: shared DOM helpers,
 *   request helpers, accessibility behaviors, and page-level state live here.
 * - Keep this API stable; page modules import these primitives directly.
 * - Trust-layer writes must continue to flow through Guardian-backed helpers.
 * - Network helpers must preserve timeout, auth-failure handling, and Phantom Wing
 *   reporting semantics to keep diagnostics and UX behavior consistent.
 * 
 * OVERVIEW:
 * PayCalCore is the central utility library for PayCal, providing DOM access,
 * state management, network operations, user preferences, and modal dialogs.
 * All PayCal pages import and use this module for consistent functionality.
 * 
 * IMPORT:
 *   import PayCalCore from '/js/';
 *   // OR: import PC from '/js/';
 * 
 * USAGE:
 *   PayCalCore.config.pc_api         - API endpoint URL
 *   PayCalCore.state.active_day_id   - Currently active calendar day
 *   PayCalCore.getElement(id)        - Safe DOM element access
 *   PayCalCore.deleteResource(ep, id) - DELETE API call with timeout
 *   PayCalCore.openModal(id, text)   - Show dialog with accessibility
 *   PayCalCore.init()                - Initialize all listeners at page load
 * 
 * CORE ATTRIBUTES:
 * ==================
 * 
 * config: {
 *   pc_api:                   string   - API base URL
 *   USER_LOCALE:              string   - User's language code (en, fr, etc)
 *   session_timeout_seconds:  number   - Seconds before auto-logout
 *   pc_UUID_set:              string   - Character set for site UUIDs
 *   pc_verification_set:      string   - Character set for verification codes
 *   languages:                object   - Map of language codes to names
 *   [strings]:                string   - i18n strings (CAPSLOCK_ACTIVE, etc)
 * }
 * 
 * state: {
 *   active_day_id:                   string|null - Currently selected calendar day
 *   modal_is_active:                 boolean     - Is any modal currently open
 *   calendar_screen:                 string      - 'normal' or 'fullscreen'
 *   audio_feedback:                  string      - 'all', 'none', etc
 *   default_site_id:                 string      - User's default work site
 *   default_hours:                   number      - Default work hours
 *   default_living_out_allowance:    number      - Default daily allowance
 *   default_travel_hours:            number      - Default travel time
 *   [currentPage]:                   string      - Detected via URL pathname
 *   [modal_is_active]:               boolean     - Set by openModal/closeModal
 *   [lastFocused]:                   Element     - For focus restoration
 * }
 * 
 * PUBLIC API - DOM & Element Access:
 * ===================================
 *   getElement(id)             - Get element by id, log warning if missing
 *   query(selector, parent?)   - querySelector with parent scope
 *   queryAll(selector, parent?) - querySelectorAll returning array
 *   removeElement(id)          - Remove element from DOM
 *   copyAttribute(target, source) - Copy attribute between elements
 * 
 * PUBLIC API - Modal & Dialog:
 * ============================
 *   openModal(id, text)        - Show dialog, announce via audio, focus first input
 *   closeModal(id, text)       - Close dialog, restore focus, announce close
 *   showTrustLayerWarning(msg) - Display security warning banner
 * 
 * PUBLIC API - Network & API:
 * ===========================
 *   deleteResource(ep, id)     - DELETE /api/v1/{ep}/delete/ with 10s timeout
 *   readResource(ep, options?) - GET /api/v1/{ep} (returns text)
 *   updateResource(ep, form)   - POST /api/v1/{ep}/update/ with 10s timeout
 *   [All include]: AbortController timeout, error handling, status checking
 * 
 * PUBLIC API - Form & Input Formatting:
 * ======================================
 *   formatPhoneNumber(input)        - Convert input value to (XXX) XXX-XXXX format
 *   formatPhoneNumberValue(value)   - Convert raw phone text to (XXX) XXX-XXXX format
 *   formatVerificationCode(input)   - Filter to valid charset, uppercase
 *   generateSiteUUID()              - Create secure random S + 9 chars
 *   togglePasswordVisibility(id)    - Switch input type password <-> text
 * 
 * PUBLIC API - Date, Text, Language:
 * ===================================
 *   formatReadableDate(yyyymmdd)    - Format YYYY-MM-DD to "Long Format" in locale
 *   toTitleCase(text)               - Capitalize each word
 *   getLanguageName(code)           - Get full language name from code
 * 
 * PUBLIC API - User Experience:
 * =============================
 *   showToast(text, type, ms)   - Canonical cross-page toast API
 *   textToSpeech(text)          - Speak text using Web Speech API
 *   activateCapslockWarning()   - Show CAPS LOCK warning
 *   deactivateCapslockWarning() - Hide CAPS LOCK warning
 *   addAudioFocusListener(el)   - Read field value on focus (if audio_feedback='all')
 * 
 * PUBLIC API - Utilities:
 * =======================
 *   getCurrentPage()            - Get current page name from URL
 *   escapeCssId(id)             - Escape CSS selector for numeric-starting ids
 *   getDataAttribute(el, id, name) - Safe data-{id}-{name} access
 *   delay(ms)                   - Promise-based setTimeout
 *   debounce(func, ms)          - Rate-limit function calls
 *   addClickAndEnterListener(id, func) - Click and Enter key binding
 *   setSelectOption(select, value) - Set <select> option by value
 *   getBrowserVendor()          - Detect browser vendor (chrome, webkit, etc)
 * 
 * PUBLIC API - Initialization:
 * ============================
 *   init()                      - Register all global listeners, setup handlers
 *                                Returns cleanup function for unregistering
 * 
 * ERROR HANDLING:
 * ===============
 * All network operations (DELETE, GET, POST) include:
 * - 10 second timeout with AbortController
 * - Error collation via Phantom Wing (PW.error/warn)
 * - Proper error type detection (timeout, network, HTTP status)
 * - Auth failure detection (401/403 → redirect to /login)
 * 
 * ACCESSIBILITY:
 * ===============
 * - Audio feedback announcements via textToSpeech
 * - ARIA modal attributes (aria-hidden)
 * - Capslock warning with visual + audio notification
 * - Focus management with lastFocused restoration
 * - Keyboard shortcuts (Escape, Enter, etc)
 * 
 * ============================================================================
 */

import PW from '/js/phantomwing/';
import NavigationToggle from '/js/navigation-toggle.js';
import RuntimeIntegrity from '/js/runtime-integrity.js';
import A11yModule from '/js/core/a11y.js';

const PayCalCore = (() => {

  RuntimeIntegrity.start({
    intervalMs: 10000,
    report: (type, data) => {
      PW.report('security', type, data);
    },
  });

  // === TRUST-LAYER: UI WARNING BANNER ===
  // Call showTrustLayerWarning(message) to display a persistent warning at the top of the page
  function showTrustLayerWarning(message) {
    const dialog = document.getElementById("trust_layer_warning_dialog");
    const text = document.getElementById("trust_layer_warning_text");
    if (!dialog || !text) return;
    text.textContent = message ?? '';
    dialog.showModal();
  }

  /** INIT */
  const config = {
    pc_api               : '<?php echo \PayCal\Domain\Environment::appURL('api/v1'); ?>',
    USER_LOCALE          : '<?php echo $user->language; ?>',
    CAPSLOCK_ACTIVE      : '<?php echo htmlspecialchars($i18n['CAPSLOCK_ACTIVE'], ENT_QUOTES, 'UTF-8'); ?>',
    KEYBOARD_SHORTCUTS   : '<?php echo htmlspecialchars($i18n['KEYBOARD_SHORTCUTS'], ENT_QUOTES, 'UTF-8'); ?>',
    OPENED_DIALOG        : '<?php echo htmlspecialchars($i18n['OPENED_DIALOG'], ENT_QUOTES, 'UTF-8'); ?>',
    CLOSED_DIALOG        : '<?php echo htmlspecialchars($i18n['CLOSED_DIALOG'], ENT_QUOTES, 'UTF-8'); ?>',
    WORK_DETAILS         : '<?php echo htmlspecialchars($i18n['WORK_DETAILS'], ENT_QUOTES, 'UTF-8'); ?>',
    SIGN_OUT             : '<?php echo htmlspecialchars($i18n['SIGN_OUT'], ENT_QUOTES, 'UTF-8'); ?>',
    pc_UUID_set          : '<?php echo \PayCal\Domain\SystemConfig::PC_UUID_SET; ?>',
    pc_verification_set  : '<?php echo \PayCal\Domain\SystemConfig::PC_VERIFICATION_SET; ?>',
    session_timeout_seconds : <?php echo (int) $user->getSessionTimeoutSeconds(); ?>,
    emergency_signout_window_ms : <?php echo (int) $user->getEmergencySignoutWindowMs(); ?>,
    form_ttl_settings_seconds : <?php echo (int) $user->getFormTtlSettingsSeconds(); ?>,
    form_ttl_calendar_seconds : <?php echo (int) $user->getFormTtlCalendarSeconds(); ?>,
    form_ttl_general_seconds : <?php echo (int) $user->getFormTtlGeneralSeconds(); ?>,
    languages            : { <?php
      $langEntries = [];
      foreach (\PayCal\Domain\Language::AVAILABLE as $code => $name) {
        $langEntries[] = "'$code': '$name'";
      }
      echo implode(", ", $langEntries);
    ?> }
  };

  /** STATE */
  let state = {
    active_day_id                : null,
    modal_is_active              : false,
    lastFocused                  : null,
    context_menu_is_active       : false,
    calendar_screen              : 'normal',
    audio_feedback               : '<?php echo $user->audio_feedback ?? 'all'; ?>',
    voice                        : '<?php echo $user->voice ?? 'system_default'; ?>',
    default_save                 : '<?php echo $user->default_site_id ?? ''; ?>',
    default_site_id              : '<?php echo $user->default_site_id ?? ''; ?>',
    default_hours                : <?php echo json_encode((float) ($user->default_hours ?? 0)); ?>,
    default_living_out_allowance : <?php echo json_encode((float) ($user->default_living_out_allowance ?? 0)); ?>,
    default_travel_hours         : <?php echo json_encode((float) ($user->default_travel_hours ?? 0)); ?>,
  };

  const normalizeDebugFlag = (value) => String(value ?? '0') === '1';

  let debugSettings = {
    consoleEnabled: normalizeDebugFlag('<?php echo htmlspecialchars((string) ($user->debug_console_enabled ?? UserPreferenceDefaults::DEFAULT_DEBUG_CONSOLE_ENABLED), ENT_QUOTES, 'UTF-8'); ?>'),
    fineGrainedEnabled: normalizeDebugFlag('<?php echo htmlspecialchars((string) ($user->debug_fine_grained_enabled ?? UserPreferenceDefaults::DEFAULT_DEBUG_FINE_GRAINED_ENABLED), ENT_QUOTES, 'UTF-8'); ?>'),
    networkEnabled: normalizeDebugFlag('<?php echo htmlspecialchars((string) ($user->debug_network_enabled ?? UserPreferenceDefaults::DEFAULT_DEBUG_NETWORK_ENABLED), ENT_QUOTES, 'UTF-8'); ?>'),
  };

  const applyGlobalDebugSettings = (nextSettings) => {
    const normalized = {
      consoleEnabled: Boolean(nextSettings?.consoleEnabled),
      fineGrainedEnabled: Boolean(nextSettings?.fineGrainedEnabled),
      networkEnabled: Boolean(nextSettings?.networkEnabled),
    };

    debugSettings = normalized;

    if (typeof window !== 'undefined') {
      window.PAYCAL_DEBUG_SETTINGS = Object.freeze({ ...normalized });
      window.PAYCAL_DEBUG = normalized.consoleEnabled || normalized.fineGrainedEnabled;
    }

    config.DEBUG_SETTINGS = Object.freeze({ ...normalized });
  };

  applyGlobalDebugSettings(debugSettings);

  if (typeof window !== 'undefined') {
    window.addEventListener('paycal:debug-settings-updated', (event) => {
      const detail = event?.detail || {};
      applyGlobalDebugSettings({
        consoleEnabled: detail.consoleEnabled,
        fineGrainedEnabled: detail.fineGrainedEnabled,
        networkEnabled: detail.networkEnabled,
      });
    });
  }

  const trustLayer = (typeof window !== 'undefined' && window.Guardian)
    ? window.Guardian
    : (() => {
    const hasTrustedTypes = typeof window !== 'undefined'
      && typeof window.trustedTypes !== 'undefined'
      && typeof window.trustedTypes.createPolicy === 'function';

    const toStringValue = (value) => value == null ? '' : String(value);

    // Remove dangerous tags/attributes while preserving allowed markup structure.
    function sanitizeHTML(input) {
      const raw = toStringValue(input);
      if (raw === '') return '';

      const template = document.createElement('template');
      template.innerHTML = raw;

      const blockedSelector = 'script, iframe, object, embed, link[rel="import"], meta[http-equiv="refresh"]';
      template.content.querySelectorAll(blockedSelector).forEach((el) => el.remove());

      const walker = document.createTreeWalker(template.content, NodeFilter.SHOW_ELEMENT);
      let node = walker.currentNode;

      while (node) {
        if (node.attributes) {
          Array.from(node.attributes).forEach((attr) => {
            const name = attr.name.toLowerCase();
            const value = (attr.value || '').trim();

            if (name.startsWith('on')) {
              node.removeAttribute(attr.name);
              return;
            }

            if (name === 'srcdoc') {
              node.removeAttribute(attr.name);
              return;
            }

            if ((name === 'href' || name === 'src' || name === 'xlink:href' || name === 'formaction')
              && /^javascript:/i.test(value)) {
              node.removeAttribute(attr.name);
            }
          });
        }
        node = walker.nextNode();
      }

      return template.innerHTML;
    }

    // Compatibility helper: return escaped text-safe markup for legacy callers.
    function sanitizedText(input) {
      const span = document.createElement('span');
      span.textContent = toStringValue(input);
      return span.innerHTML;
    }

    let defaultPolicy = null;
    let paycalPolicy = null;

    if (hasTrustedTypes) {
      try {
        defaultPolicy = window.trustedTypes.createPolicy('default', {
          createHTML: (markup) => sanitizeHTML(markup),
          createScriptURL: (url) => toStringValue(url)
        });
      } catch {}

      try {
        paycalPolicy = window.trustedTypes.createPolicy('paycal', {
          createHTML: (markup) => sanitizeHTML(markup),
          createScriptURL: (url) => toStringValue(url)
        });
      } catch {}
    }

    function toTrustedHTML(markup) {
      const sanitized = sanitizeHTML(markup);

      if (paycalPolicy && typeof paycalPolicy.createHTML === 'function') {
        try {
          return paycalPolicy.createHTML(sanitized);
        } catch {}
      }

      if (defaultPolicy && typeof defaultPolicy.createHTML === 'function') {
        try {
          return defaultPolicy.createHTML(sanitized);
        } catch {}
      }

      return sanitized;
    }

    function setHTML(el, markup) {
      if (!el) return;
      el.innerHTML = toTrustedHTML(markup);
    }

    function insertHTML(el, position, markup) {
      if (!el) return;
      el.insertAdjacentHTML(position, toTrustedHTML(markup));
    }

    function replaceOuterHTML(el, markup) {
      if (!el) return;
      el.outerHTML = toTrustedHTML(markup);
    }

    const api = {
      sanitizedText,
      sanitizeHTML,
      toTrustedHTML,
      setHTML,
      insertHTML,
      replaceOuterHTML,
    };

    if (typeof window !== 'undefined') {
      window.Guardian = api;
    }

    return api;
  })();

  // Private helpers
  const getElement = (id) => {
    const el = document.getElementById(id);
    if (!el) PW.warn(`Element not found: ${id}`);
    return el;
  };

  const query = (selector, parent = document) => parent.querySelector(selector);

  const queryAll = (selectors, parent = document) => Array.from(parent.querySelectorAll(selectors));

  const a11y = A11yModule(state, getElement, query, queryAll, textToSpeech, config);

  function getFocusableElements(container) {
    return a11y.getFocusableElements(container);
  }

  function trapFocusWithin(container, event) {
    return a11y.trapFocusWithin(container, event);
  }

  function addAudioFocusListener(el, prefix = "", suffix = "") {
    a11y.addAudioFocusListener(el, prefix, suffix);
  }

  function activateCapslockWarning() {
    queryAll("center.status_message").forEach(el => {
      if (el) trustLayer.setHTML(el, config.CAPSLOCK_ACTIVE);
    });
    showToast(config.CAPSLOCK_ACTIVE);
    const icon = getElement("capslock_icon");
    if (icon) icon.classList.add('visibility-visible');
  }

  function deactivateCapslockWarning() {
    queryAll("center.status_message").forEach(el => {
      if (el) trustLayer.setHTML(el, "&nbsp;");
    });
    const icon = getElement("capslock_icon");
    if (icon) icon.classList.remove('visibility-visible');
  }

  function ensureDialogChrome(dialog) { a11y.ensureDialogChrome(dialog); }

  function ensureAllDialogsChrome() { a11y.ensureAllDialogsChrome(); }

  function closeModal(id, text = "") {
    a11y.closeModal(id, text);
  }

  async function deleteResource(ep, id) {
    if (!ep || !id) {
      const msg = 'Invalid endpoint or ID';
      PW.error(`[deleteResource] ${msg}`);
      throw new Error(msg);
    }
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 10000); // 10s timeout
    try {
      const response = await fetch(`${config.pc_api}/${ep}/delete/`, {
        method: "DELETE",
        credentials: "include",
        headers: new Headers({
          "Content-Type": "application/json",
          "X-Resource-ID": id
        }),
        signal: controller.signal
      });
      clearTimeout(timeout);
      if (!response.ok) {
        const errorMsg = `[deleteResource] HTTP ${response.status} - ${response.statusText}`;
        PW.error(errorMsg);
        if (response.status === 401 || response.status === 403) {
          window.location.href = '/login';
        }
        throw new Error(errorMsg);
      }
      const json = await response.json();
      return json.data;
    } catch (error) {
      clearTimeout(timeout);
      if (error.name === 'AbortError') {
        const msg = '[deleteResource] Request timed out';
        PW.error(msg);
        throw new Error(msg);
      }
      const msg = `[deleteResource] Network error: ${error.message}`;
      PW.error(msg);
      throw new Error(msg);
    }
  }

  function copyAttribute(target, source) {
    const targetEl = getElement(target);
    const sourceEl = query(`[${source}]`);
    if (!targetEl || !sourceEl) return;
    targetEl.setAttribute("value", sourceEl.getAttribute(source) ?? '');
  }

  function getDataAttribute(el, id, name) {
    return el?.getAttribute(`data-${id}-${name}`) ?? null;
  }

  const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms));

  const debounce = (func, delay) => {
    let timeoutId;
    return function (...args) {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
  };

  function addClickAndEnterListener(id, func) {
    const el = getElement(id);
    if (!el) return null;
    el.addEventListener("click", func);
    el.addEventListener("keypress", (event) => {
      if (event.key === "Enter") func(event);
    });
    return el;
  }

  function escapeCssId(id) {
    if (typeof id !== 'string') return '';
    if (!isNaN(parseInt(id.charAt(0)))) {
      return `\\${id.charCodeAt(0).toString(16)} ${id.slice(1)}`;
    }
    return id;
  }

  function formatPhoneNumberValue(value) {
    const rawValue = typeof value === 'string' ? value : String(value ?? '');
    const digits = rawValue.replace(/\D/g, '').replace(/^1(?=\d{10}$)/, '').slice(0, 10);
    if (digits === '') return '';
    if (digits.length <= 3) return `(${digits}`;
    if (digits.length <= 6) return `(${digits.slice(0, 3)}) ${digits.slice(3)}`;
    return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
  }

  function formatPhoneNumber(input) {
    if (!input) return '';
    const formattedNumber = formatPhoneNumberValue(input.value ?? '');
    input.value = formattedNumber;
    return formattedNumber;
  }

  function formatVerificationCode(el) {
    if (!el?.value) return;
    const regex = new RegExp(`[^${config.pc_verification_set}]`, "gi");
    el.value = el.value.toUpperCase().replace(regex, "");
  }

  function generateSiteUUID() {
    const chars = config.pc_UUID_set;
    if ('crypto' in window && crypto.getRandomValues) {
      const b = new Uint8Array(9);
      crypto.getRandomValues(b);
      return "S" + Array.from(b, byte => chars[byte % chars.length]).join("");
    } else {
      // Fallback for older browsers
      return "S" + Array.from({length: 9}, () => chars[Math.floor(Math.random() * chars.length)]).join("");
    }
  }

  function getLanguageName(code) {
    return config.languages[code] ?? "";
  }

  function openModal(id, text = "") {
    a11y.openModal(id, text);
  }

  function getCurrentPage() {
    return window.location.href.split("?")[0]?.replace(/\/$/, "").split("/").pop() ?? '';
  }

  async function readResource(ep, options = {}) {
    if (!ep) {
      const msg = 'Invalid endpoint';
      PW.error(`[readResource] ${msg}`);
      throw new Error(msg);
    }
    const timeoutMs = Number.isFinite(options?.timeoutMs)
      ? Math.max(1000, Number(options.timeoutMs))
      : 10000;

    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), timeoutMs);
    try {
      const response = await fetch(`${config.pc_api}/${ep}`, {
        method: "GET",
        signal: controller.signal
      });
      clearTimeout(timeout);
      if (!response.ok) {
        const errorMsg = `[readResource] HTTP ${response.status} - ${response.statusText}`;
        PW.error(errorMsg);
        throw new Error(errorMsg);
      }
      return await response.text();
    } catch (error) {
      clearTimeout(timeout);
      if (error.name === 'AbortError') {
        const msg = `[readResource] Request timed out after ${timeoutMs}ms`;
        PW.error(msg);
        throw new Error(msg);
      }
      const msg = `[readResource] Network error: ${error.message}`;
      PW.error(msg);
      throw new Error(msg);
    }
  }

  function formatReadableDate(yyyymmdd) {
    if (!yyyymmdd || typeof yyyymmdd !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(yyyymmdd)) return '';
    const year = parseInt(yyyymmdd.substring(0, 4), 10);
    const month = parseInt(yyyymmdd.substring(5, 7), 10);
    const day = parseInt(yyyymmdd.substring(8, 10), 10);
    const date = new Date(year, month - 1, day);
    if (isNaN(date.getTime())) return '';
    return new Intl.DateTimeFormat(config.USER_LOCALE, {
      year: "numeric", month: "long", day: "numeric"
    }).format(date).replace(/\b\w/g, char => char.toUpperCase());
  }

  function removeElement(id) {
    const el = getElement(id);
    if (el) el.remove();
  }

  function setSelectOption(select, selected_value) {
    if (!select?.options) return;
    for (let i = 0; i < select.options.length; i++) {
      if (select.options[i].value.toUpperCase() === (selected_value ?? '').toUpperCase()) {
        select.options[i].selected = true;
        break;
      }
    }
  }

  /**
   * Update status message with themed styling and icons.
   * @param {string} message - The message to display
   * @param {string} type - 'success', 'save', 'copy', 'paste', 'delete', 'error', 'info', 'working' (default: 'info')
   * @param {number} autoClearMs - Auto-clear after this many ms (0 = don't auto-clear, default: 4000)
   */
  function updateStatusMessage(message, type = 'info', autoClearMs = 4000, skipTTS = false) {
    const statusDiv = document.getElementById('status');
    
    if (!statusDiv) {
      PW.warn('[Status] #status div not found');
      return;
    }

    const statusThemes = {
      copy: {
        bg: '#00ff66',
        fg: '#05210f',
        border: '#00c853',
        iconName: 'copy',
      },
      save: {
        bg: '#00ff66',
        fg: '#05210f',
        border: '#00c853',
        iconName: 'save',
      },
      paste: {
        bg: '#1f8bff',
        fg: '#031a33',
        border: '#006edc',
        iconName: 'paste',
      },
      delete: {
        bg: '#ff3b30',
        fg: '#2b0806',
        border: '#d91e18',
        iconName: 'delete-sign',
      },
      error: {
        bg: '#ff3b30',
        fg: '#2b0806',
        border: '#d91e18',
        iconName: 'error',
      },
      info: {
        bg: '#1f8bff',
        fg: '#031a33',
        border: '#006edc',
        iconName: 'info',
      },
      working: {
        bg: '#1f8bff',
        fg: '#031a33',
        border: '#006edc',
        iconName: 'hourglass',
      },
      success: {
        bg: '#00ff66',
        fg: '#05210f',
        border: '#00c853',
        iconName: 'checkmark',
      }
    };

    const theme = statusThemes[type] || statusThemes.info;
    const token = `${Date.now()}-${Math.random()}`;

    statusDiv.textContent = '';
    statusDiv.className = `status visible ${type}`;

    const iconBox = document.createElement('span');
    iconBox.className = 'status-icon-box';
    iconBox.setAttribute('aria-hidden', 'true');

    const iconImg = document.createElement('img');
    const baseHref = (document.querySelector('base') && document.querySelector('base').href) || window.location.origin + '/';
    const iconCandidates = [
      new URL(`images/icons8/status/${theme.iconName}.png`, baseHref).href,
      new URL(`img/icons8/status/${theme.iconName}.png`, baseHref).href,
      `/images/icons8/status/${theme.iconName}.png`,
      `/img/icons8/status/${theme.iconName}.png`
    ];
    let iconCandidateIndex = 0;
    iconImg.src = iconCandidates[iconCandidateIndex];
    iconImg.alt = '';
    iconImg.width = 20;
    iconImg.height = 20;
    iconImg.addEventListener('error', () => {
      iconCandidateIndex += 1;
      if (iconCandidateIndex < iconCandidates.length) {
        iconImg.src = iconCandidates[iconCandidateIndex];
        return;
      }
      PW.warn('[Status] Icon failed to load', {
        type,
        iconName: theme.iconName,
        attempted: iconCandidates
      });
      iconImg.remove();
      iconBox.textContent = '!';
    });
    iconBox.appendChild(iconImg);

    const messageText = document.createElement('span');
    messageText.className = 'status-message-text';
    messageText.textContent = message;

    statusDiv.appendChild(iconBox);
    statusDiv.appendChild(messageText);
    statusDiv.dataset.statusToken = token;

    // Audio feedback
    if (!skipTTS && state.audio_feedback === "all") {
      try {
        const spokenMessage = String(message ?? '').replace(/^status:\s*/i, '').trim();
        if (spokenMessage !== '') {
          const category = type === 'error' ? 'error' : 'status';
          textToSpeech(spokenMessage, category);
        }
      } catch {}
    }

    // Auto-clear if specified
    if (autoClearMs > 0) {
      const effectiveAutoClearMs = autoClearMs * 2;
      setTimeout(() => {
        if (statusDiv.dataset.statusToken === token) {
          statusDiv.textContent = '';
          statusDiv.className = 'status';
          delete statusDiv.dataset.statusToken;
        }
      }, effectiveAutoClearMs);
    }
  }

  /**
   * Canonical cross-page toast API.
   * Use this from page scripts to avoid page-level toast duplication.
   */
  function showToast(message, type = 'info', autoClearMs = 3000, skipTTS = false) {
    updateStatusMessage(message, type, autoClearMs, skipTTS);
  }

  function textToSpeech(t, category = 'status') {
    const text = String(t ?? '').trim();
    if (text === '') return;

    // Keep TTS config in sync with current app state.
    if (window.tts) {
      window.tts.audio_feedback = state.audio_feedback;
      window.tts.voice = state.voice || window.tts.voice;
    }

    if (window.TTS && typeof window.TTS.enqueue === 'function') {
      window.TTS.enqueue(category, text);
      return;
    }

    // Silent fallback if manager is unavailable.
    if (!('speechSynthesis' in window)) return;
    const s = window.speechSynthesis;
    if (s.speaking) s.cancel();
    s.speak(new SpeechSynthesisUtterance(text));
  }

  function toTitleCase(t) {
    if (typeof t !== 'string') return '';
    return t.toLowerCase()
      .split(" ")
      .map(word => word[0]?.toUpperCase() + word.slice(1))
      .join(" ");
  }

  function togglePasswordVisibility(id) {
    const el = getElement(id);
    if (!el || !el.type) return;
    el.type = el.type === "password" ? "text" : "password";
  }

  async function updateResource(ep, form_or_id, options = {}) {
    if (!ep) {
      const msg = 'Invalid endpoint';
      PW.error(`[updateResource] ${msg}`);
      throw new Error(msg);
    }
    let form_data;
    if (typeof form_or_id === "string") {
      const formEl = getElement(form_or_id);
      if (!formEl) {
        const msg = 'Form not found';
        PW.error(`[updateResource] ${msg}`);
        throw new Error(msg);
      }
      form_data = new FormData(formEl);
    } else if (form_or_id instanceof FormData) {
      form_data = form_or_id;
    } else {
      const msg = 'Invalid form data';
      PW.error(`[updateResource] ${msg}`);
      throw new Error(msg);
    }
    const timeoutMs = Number.isFinite(options?.timeoutMs)
      ? Math.max(1000, Number(options.timeoutMs))
      : 10000;
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), timeoutMs);
    try {
      const response = await fetch(`${config.pc_api}/${ep}/update/`, {
        method: "POST",
        credentials: "include",
        body: form_data,
        signal: controller.signal
      });
      clearTimeout(timeout);
      if (!response.ok) {
        let detail = '';
        try {
          const raw = await response.text();
          if (raw.trim() !== '') {
            try {
              const parsed = JSON.parse(raw);
              if (parsed && typeof parsed === 'object' && parsed.message) {
                detail = String(parsed.message);
              } else {
                detail = raw.slice(0, 240);
              }
            } catch (_parseErr) {
              detail = raw.slice(0, 240);
            }
          }
        } catch (_readErr) {
          detail = '';
        }

        const errorMsg = `[updateResource] HTTP ${response.status} - ${response.statusText}${detail ? ` | ${detail}` : ''}`;
        PW.error(errorMsg);
        if (response.status === 401 || response.status === 403) {
          window.location.href = '/login';
        }
        throw new Error(errorMsg);
      }
      const json = await response.json();
      return json.data;
    } catch (error) {
      clearTimeout(timeout);
      if (error.name === 'AbortError') {
        const msg = '[updateResource] Request timed out';
        PW.error(msg);
        throw new Error(msg);
      }
      const msg = `[updateResource] Network error: ${error.message}`;
      PW.error(msg);
      throw new Error(msg);
    }
  }

  function getBrowserVendor() {
    const userAgent = navigator.userAgent.toLowerCase();
    switch (true) {
      case /edg/.test(userAgent):
        return "Edge";
      case /chrome|crios|chromium/.test(userAgent):
        return "Chrome";
      case /firefox|fxios/.test(userAgent):
        return "Firefox";
      case /safari/.test(userAgent) && !/chrome|crios|chromium/.test(userAgent):
        return "Safari";
      default:
        return "Unknown";
    }
  }

  let clockInterval;
  let escPressCount = 0;
  let escTimerStart = 0;
  const ESC_SIGNOUT_COUNT = 3;
  const normalizeEscWindow = (value) => {
    const raw = Number(value) || 600;
    const clamped = Math.min(2000, Math.max(200, raw));
    return Math.round(clamped / 200) * 200;
  };
  let escSignoutWindowMs = normalizeEscWindow(config.emergency_signout_window_ms);

  let isDragging = false;
  let isResizing = false;
  let startX, startY, startWidth, startHeight;
  let hasInitialized = false;
  let cleanupHandler = null;
  let orgNotificationsIntervalId = null;
  let orgNotificationsSignature = '';
  let orgNotificationsUpdateHandler = null;

  function init() {
    if (hasInitialized) {
      return cleanupHandler || (() => {});
    }
    hasInitialized = true;

    // ====================================================================
    // DIAGNOSTIC DATA OUTPUT
    // ====================================================================
    function outputDiagnostics() {
      const now = Date.now();
      const navigationStart = performance.timing?.navigationStart || 0;
      const totalLoadTime = navigationStart > 0 ? now - navigationStart : 0;
      const domInteractive = performance.timing?.domInteractive || 0;
      const domComplete = performance.timing?.domComplete || 0;
      const loadTime = domComplete > 0 ? domComplete - navigationStart : 0;
      
      const currentPage = getCurrentPage();
      const userAgent = navigator.userAgent;
      const browserVendor = getBrowserVendor();
      const locale = config.USER_LOCALE;
      const viewport = {
        width: window.innerWidth,
        height: window.innerHeight,
        devicePixelRatio: window.devicePixelRatio
      };
      
      const diagnostics = {
        page: currentPage || 'unknown',
        load_time_ms: loadTime,
        total_elapsed_ms: totalLoadTime,
        viewport: viewport,
        browser: browserVendor,
        locale: locale,
        timestamp: new Date().toISOString(),
        performance: {
          domInteractive: domInteractive - navigationStart,
          domComplete: domComplete - navigationStart,
          navigationStart: navigationStart
        }
      };
      
      const style = 'color: #0066cc; font-weight: bold; font-size: 13px; background: #f0f0f0; padding: 8px; border-radius: 4px; display: block; margin: 8px 0;';
      const infoStyle = 'color: #333; font-family: monospace; font-size: 12px; background: #fafafa; padding: 6px; margin: 4px 0; border-left: 3px solid #0066cc;';
      
      PW.log('📊 PAYCAL DIAGNOSTICS');
      PW.log('Page: ' + diagnostics.page);
      PW.log('Load Time: ' + diagnostics.load_time_ms + 'ms');
      PW.log('Viewport: ' + diagnostics.viewport.width + 'x' + diagnostics.viewport.height + '@' + diagnostics.viewport.devicePixelRatio + 'x');
      PW.log('Browser: ' + diagnostics.browser);
      PW.log('Locale: ' + diagnostics.locale);
      
      // Log via Phantom Wing as well for error tracking
      PW.log('[DIAGNOSTICS] Page=' + diagnostics.page + ', Load=' + diagnostics.load_time_ms + 'ms, Viewport=' + diagnostics.viewport.width + 'x' + diagnostics.viewport.height + ', Browser=' + diagnostics.browser);
      
      // Expose for debugging
      if (typeof window.PAYCAL_DIAGNOSTICS === 'undefined') {
        window.PAYCAL_DIAGNOSTICS = diagnostics;
      }

      renderLensClientMetrics();
      
      // Log full data structure if debug mode
      if (window.PAYCAL_DEBUG) {
        PW.log('[DEBUG] Full Diagnostics Object: ' + JSON.stringify(diagnostics));
      }
    }
    
    // Run diagnostics after page settles
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', outputDiagnostics);
    } else {
      outputDiagnostics();
    }

    // ====================================================================
    // LENS DASHBOARD CLIENT METRICS (NO FLOATING PANEL)
    // ====================================================================
    function renderLensClientMetrics() {
      if (!window.PAYCAL_DIAGNOSTICS) return;

      const container = document.getElementById('lens_metrics_content');
      if (!container) return;

      const diag = window.PAYCAL_DIAGNOSTICS;
      const metrics = window.PAYCAL_METRICS || {};
      const serverMetricsEl = document.querySelector('#lens_footer_segment #lens_server_metrics');
      const serverRows = serverMetricsEl ? `
        <dt>Page</dt><dd>${serverMetricsEl.dataset.page || 'unknown'}</dd>
        <dt>PHP Backend Load</dt><dd>${serverMetricsEl.dataset.phpLoadTime || 'n/a'}</dd>
        <dt>Memory Usage</dt><dd>${serverMetricsEl.dataset.memoryUsage || 'n/a'}</dd>
        <dt>Peak Memory</dt><dd>${serverMetricsEl.dataset.peakMemoryUsage || 'n/a'}</dd>
      ` : `
        <dt>Page</dt><dd>${diag.page || 'unknown'}</dd>
        <dt>PHP Backend Load</dt><dd>n/a</dd>
        <dt>Memory Usage</dt><dd>n/a</dd>
        <dt>Peak Memory</dt><dd>n/a</dd>
      `;
      const perfRows = metrics.dom_complete ? `
        <dt>DNS Lookup</dt><dd>${Math.round(metrics.dns || 0)}ms</dd>
        <dt>Time to First Byte</dt><dd>${Math.round(metrics.ttfb || 0)}ms</dd>
        <dt>DOM Complete</dt><dd>${Math.round(metrics.dom_complete || 0)}ms</dd>
        <dt>Resources</dt><dd>${metrics.resource_count || 0} files (${metrics.total_resource_size || 0} KB)</dd>
      ` : '';

      trustLayer.setHTML(container, `
        <dl class="lens-metrics-grid">
          ${serverRows}
        </dl>
      `);

      const html = `
        <div id="lens_client_metrics" class="lens-client-metrics">
          <h3 class="lens-subtitle">Client Metrics</h3>
          <dl class="lens-metrics-grid">
            <dt>Browser</dt><dd>${diag.browser || 'unknown'}</dd>
            <dt>Viewport</dt><dd>${diag.viewport.width}×${diag.viewport.height}</dd>
            <dt>Locale</dt><dd>${diag.locale || 'unknown'}</dd>
            ${perfRows}
          </dl>
        </div>
      `;

      const existing = document.getElementById('lens_client_metrics');
      if (existing) {
        trustLayer.replaceOuterHTML(existing, html);
      } else {
        trustLayer.insertHTML(container, 'beforeend', html);
      }
    }

    if (window.PAYCAL_DIAGNOSTICS) {
      renderLensClientMetrics();
    }

    // ====================================================================
    // PERFORMANCE MONITORING
    // ====================================================================
    function monitorPerformanceMetrics() {
      // Get Performance API data
      const perfData = performance.getEntriesByType("navigation")[0];
      const resources = performance.getEntriesByType("resource");
      
      // Calculate key metrics
      const metrics = {
        dns: perfData?.domainLookupEnd - perfData?.domainLookupStart || 0,
        tcp: perfData?.connectEnd - perfData?.connectStart || 0,
        ttfb: perfData?.responseStart - perfData?.requestStart || 0, // Time to First Byte
        download: perfData?.responseEnd - perfData?.responseStart || 0,
        dom_interactive: perfData?.domInteractive - perfData?.navigationStart || 0,
        dom_complete: perfData?.domComplete - perfData?.navigationStart || 0,
        load_complete: perfData?.loadEventEnd - perfData?.navigationStart || 0,
        resource_count: resources.length,
        total_resource_size: Math.round(resources.reduce((sum, r) => sum + (r.transferSize || 0), 0) / 1024) // KB
      };
      
      // Log performance timeline
      if (window.PAYCAL_DEBUG) {
        const perfStyle = 'color: #008800; font-weight: bold; font-size: 12px; background: #f0fff0; padding: 6px;';
        PW.log('⚡ PERFORMANCE METRICS');
        PW.log('DNS Lookup: ' + metrics.dns + 'ms');
        PW.log('TCP Connection: ' + metrics.tcp + 'ms');
        PW.log('Time to First Byte: ' + metrics.ttfb + 'ms');
        PW.log('Document Download: ' + metrics.download + 'ms');
        PW.log('DOM Interactive: ' + metrics.dom_interactive + 'ms');
        PW.log('DOM Complete: ' + metrics.dom_complete + 'ms');
        PW.log('Full Load: ' + metrics.load_complete + 'ms');
        PW.log('Resources: ' + metrics.resource_count + ' files, ' + metrics.total_resource_size + ' KB');
        
        // Expose metrics globally
        if (typeof window.PAYCAL_METRICS === 'undefined') {
          window.PAYCAL_METRICS = metrics;
        }
      }

      if (typeof window.PAYCAL_METRICS === 'undefined') {
        window.PAYCAL_METRICS = metrics;
      }

      renderLensClientMetrics();
      
      // Report via Phantom Wing (non-debug)
      PW.report('performance', 'page_load', {
        dns: metrics.dns,
        ttfb: metrics.ttfb,
        dom_complete: metrics.dom_complete,
        resources: metrics.resource_count
      });
    }
    
    // Run performance metrics after page load
    if (document.readyState === 'complete') {
      monitorPerformanceMetrics();
    } else {
      window.addEventListener('load', monitorPerformanceMetrics);
    }

    // Clock + session timer widget (absent in sidebar nav mode — use getElementById to skip PW warning)
    const timeEl = document.getElementById("current_time");
    const clockStartedAt = Date.now();
    let clockMode = localStorage.getItem('paycal_time_mode') || 'clock';
    const timePopoverEl = document.createElement('div');
    timePopoverEl.id = 'tray_time_popover';
    timePopoverEl.className = 'tray_time_popover';
    timePopoverEl.setAttribute('role', 'tooltip');
    timePopoverEl.setAttribute('aria-label', 'Session timers');
    document.body.appendChild(timePopoverEl);

    const formatCompactCountdown = (seconds) => {
      const safe = Math.max(0, Number(seconds) || 0);
      const hrs = Math.floor(safe / 3600);
      const mins = Math.floor((safe % 3600) / 60);
      const secs = safe % 60;
      if (hrs > 0) {
        return `${hrs}h ${String(mins).padStart(2, '0')}m`;
      }
      return `${mins}m ${String(secs).padStart(2, '0')}s`;
    };

    const formatShort = (seconds) => {
      const safe = Math.max(0, Number(seconds) || 0);
      const mins = Math.floor(safe / 60);
      return mins >= 60 ? `${Math.floor(mins / 60)}h ${mins % 60}m` : `${mins}m`;
    };

    const runtimeTimeouts = {
      session_timeout_seconds: Number(config.session_timeout_seconds) || 0,
      form_ttl_settings_seconds: Number(config.form_ttl_settings_seconds) || 0,
      form_ttl_calendar_seconds: Number(config.form_ttl_calendar_seconds) || 0,
      form_ttl_general_seconds: Number(config.form_ttl_general_seconds) || 0,
    };

    const getRemaining = (totalSeconds) => {
      const elapsed = Math.floor((Date.now() - clockStartedAt) / 1000);
      return Math.max(0, Number(totalSeconds || 0) - elapsed);
    };

    const getTimerSummaryLines = () => {
      const sessionLeft = getRemaining(runtimeTimeouts.session_timeout_seconds);
      const accountLeft = getRemaining(runtimeTimeouts.form_ttl_settings_seconds);
      const calendarLeft = getRemaining(runtimeTimeouts.form_ttl_calendar_seconds);
      return {
        sessionLeft,
        lines: [
        `Session: ${formatShort(sessionLeft)} remaining`,
        `Account changes: ${formatShort(accountLeft)} remaining`,
        `Calendar editing: ${formatShort(calendarLeft)} remaining`,
        ],
      };
    };

    const renderTimePopover = () => {
      if (!timeEl || !timePopoverEl) return;
      const { lines } = getTimerSummaryLines();
      trustLayer.setHTML(timePopoverEl, `
        <div class="tray_time_popover_title">Session Timers</div>
        ${lines.map((line) => {
          const parts = line.split(':');
          if (parts.length < 2) return `<div class="tray_time_popover_row">${line}</div>`;
          const label = parts[0];
          const value = parts.slice(1).join(':').trim();
          return `<div class="tray_time_popover_row"><span>${label}</span><strong>${value}</strong></div>`;
        }).join('')}
      `);

      const popoverAnchor = timeEl.closest('.tray_widget') || timeEl.parentElement || timeEl;
      if (timePopoverEl.parentElement !== popoverAnchor) {
        popoverAnchor.appendChild(timePopoverEl);
      }
    };

    const showTimePopover = () => {
      renderTimePopover();
      timePopoverEl.classList.add('is-open');
    };

    const hideTimePopover = () => {
      timePopoverEl.classList.remove('is-open');
    };

    window.addEventListener('paycal:security-timeouts-updated', (event) => {
      const next = event?.detail || {};
      runtimeTimeouts.session_timeout_seconds = Number(next.session_timeout_seconds ?? runtimeTimeouts.session_timeout_seconds) || 0;
      runtimeTimeouts.form_ttl_settings_seconds = Number(next.form_ttl_settings_seconds ?? runtimeTimeouts.form_ttl_settings_seconds) || 0;
      runtimeTimeouts.form_ttl_calendar_seconds = Number(next.form_ttl_calendar_seconds ?? runtimeTimeouts.form_ttl_calendar_seconds) || 0;
      runtimeTimeouts.form_ttl_general_seconds = Number(next.form_ttl_general_seconds ?? runtimeTimeouts.form_ttl_general_seconds) || 0;
      escSignoutWindowMs = normalizeEscWindow(next.emergency_signout_window_ms ?? escSignoutWindowMs);
      renderCurrentTimeWidget();
    });

    const renderCurrentTimeWidget = () => {
      if (!timeEl) return;
      const { sessionLeft, lines } = getTimerSummaryLines();
      if (clockMode === 'countdown') {
        timeEl.textContent = `\u23f3 ${formatCompactCountdown(sessionLeft)}`;
      } else {
        timeEl.textContent = new Date().toLocaleTimeString("en-US", { hour12: false });
      }
      timeEl.title = lines.join('\n');
      timeEl.classList.toggle('is-countdown', clockMode === 'countdown');
      timeEl.classList.toggle('is-expiring', sessionLeft > 0 && sessionLeft <= 120);
      timeEl.classList.toggle('is-expired', sessionLeft <= 0);
      if (timePopoverEl.classList.contains('is-open')) {
        renderTimePopover();
      }
    };

    if (timeEl) {
      timeEl.addEventListener('click', () => {
        clockMode = clockMode === 'clock' ? 'countdown' : 'clock';
        localStorage.setItem('paycal_time_mode', clockMode);
        renderCurrentTimeWidget();
      });
      timeEl.addEventListener('mouseenter', () => {
        renderCurrentTimeWidget();
        showTimePopover();
      });
      timeEl.addEventListener('mouseleave', hideTimePopover);
      timeEl.addEventListener('focus', showTimePopover);
      timeEl.addEventListener('blur', hideTimePopover);
      renderCurrentTimeWidget();
    }

    clockInterval = setInterval(renderCurrentTimeWidget, 1000);

    // Emergency Sign Out
    const handleEscapeSignout = (e) => {
      if (e.key !== "Escape") return;
      const now = Date.now();
      if (escPressCount === 0) {
        escTimerStart = now;
        escPressCount = 1;
        return;
      }
      if ((now - escTimerStart) <= escSignoutWindowMs) {
        escPressCount++;
        if (escPressCount >= ESC_SIGNOUT_COUNT) {
          history.replaceState(null, "", location.href);
          window.location.href = "/signout-esc";
        }
      } else {
        escTimerStart = now;
        escPressCount = 1;
      }
    };
    document.addEventListener("keyup", handleEscapeSignout);

    const toggleHelpModal = () => {
      const helpModal = getElement("modal_help");
      if (!helpModal) return;

      if (helpModal.open) {
        closeModal("modal_help", config.KEYBOARD_SHORTCUTS);
        return;
      }

      openModal("modal_help", config.KEYBOARD_SHORTCUTS);
    };

    const initKeyboardShortcutComboAccessibility = () => {
      const normalizeShortcutSpeech = (text) => {
        return String(text || '')
          .replace(/Ctrl\/?Cmd/gi, 'Control or Command')
          .replace(/\bCTRL\b/gi, 'Control')
          .replace(/\bCMD\b/gi, 'Command')
          .replace(/\bALT\b/gi, 'Alt key')
          .replace(/\bPGDN\b/gi, 'Page Down')
          .replace(/\bPGUP\b/gi, 'Page Up')
          .replace(/\bESC\b/gi, 'Escape')
          .replace(/\?/g, ' question mark ')
          .replace(/\+/g, ' plus ')
          .replace(/\s+/g, ' ')
          .trim();
      };

      const rows = queryAll('#modal_help .keyboard_shortcuts_row');
      rows.forEach((row) => {
        if (!(row instanceof HTMLElement)) return;

        const labelEl = row.querySelector('.keyboard_shortcuts_label');
        const comboEl = row.querySelector('.keyboard_shortcuts_keys[data-shortcut-combo="true"]');
        if (!(labelEl instanceof HTMLElement) || !(comboEl instanceof HTMLElement)) return;

        const labelText = (labelEl.textContent || '').trim();
        const comboText = (comboEl.innerText || '').replace(/\s+/g, ' ').trim();
        if (labelText === '' || comboText === '') return;

        // Avoid duplicate announcements: only the focusable combo should expose an aria-label.
        row.removeAttribute('aria-label');

        comboEl.setAttribute('tabindex', '0');
        comboEl.removeAttribute('role');
        const fallbackSpeech = `${labelText}: ${comboText}`;
        comboEl.setAttribute('aria-label', normalizeShortcutSpeech(fallbackSpeech));
      });
    };

    const navShortcutRoutes = {
      c: "/",
      r: "/earnings/",
      s: "/sites/",
      o: "/profile/",
      e: "/settings/",
      a: "/about/",
      h: "/help/",
      n: "/transparency/",
      l: "/policies/",
      p: "/policies/",
    };

    const isEditableTarget = (target) => {
      if (!target || !(target instanceof Element)) return false;
      if (target.isContentEditable) return true;

      if (target.closest('[contenteditable="true"], [role="textbox"], textarea')) {
        return true;
      }

      const input = target.closest('input');
      if (input instanceof HTMLInputElement) {
        const type = String(input.type || 'text').toLowerCase();
        const textEntryTypes = new Set([
          'text',
          'search',
          'email',
          'url',
          'tel',
          'password',
          'number',
          'date',
          'datetime-local',
          'month',
          'time',
          'week',
        ]);

        return textEntryTypes.has(type);
      }

      return false;
    };

    const hasOpenDialog = () => !!document.querySelector("dialog[open]");

    const hasGlobalShortcutModifiers = (event) => {
      return event.altKey && event.shiftKey && !event.ctrlKey && !event.metaKey;
    };

    const hasNoShortcutModifiers = (event) => {
      return !event.altKey && !event.shiftKey && !event.ctrlKey && !event.metaKey;
    };

    const isPrimaryShortcutModalChord = (event) => {
      const key = typeof event.key === 'string' ? event.key.toLowerCase() : '';
      return (event.ctrlKey || event.metaKey) && !event.altKey && !event.shiftKey && key === 'k';
    };

    const isQuestionShortcut = (event, key) => {
      if (key !== '?') return false;
      // Support bare ? (Shift+/) and legacy Alt+Shift+?
      if (!event.ctrlKey && !event.metaKey && !event.altKey) return true;
      return hasGlobalShortcutModifiers(event);
    };

    const triggerNavShortcut = (key) => {
      const selector = `[data-nav-shortcut="${key}"]`;
      const candidates = Array.from(queryAll(selector));
      const visible = candidates.find((el) => {
        if (!(el instanceof HTMLElement)) return false;
        if (el.closest(".visually_hidden")) return false;
        return el.getClientRects().length > 0;
      });
      const target = visible ?? candidates[0] ?? null;
      const route = navShortcutRoutes[key];

      if (target) {
        // Shortcut navigation should route to pages even when nav links are wired as modal triggers.
        if (target.hasAttribute('data-help-trigger') && route) {
          window.location.href = route;
          return true;
        }

        target.click();
        return true;
      }

      if (!route) return false;
      window.location.href = route;
      return true;
    };

    const handleDialogFocusTrap = (event) => {
      if (event.key !== 'Tab') return;

      const openDialogs = queryAll('dialog[open]');
      if (openDialogs.length === 0) return;

      const activeDialog = openDialogs[openDialogs.length - 1];
      if (!(activeDialog instanceof HTMLElement)) return;

      trapFocusWithin(activeDialog, event);
    };

    document.addEventListener('keydown', handleDialogFocusTrap, true);

    // General Keyboard Events
    const handleGlobalKeys = (e) => {
      const key = typeof e.key === 'string' ? e.key : '';
      const caps = e.getModifierState?.("CapsLock");
      caps ? activateCapslockWarning() : deactivateCapslockWarning();
      if (key === "Escape") {
        queryAll("dialog").forEach(modal => closeModal(modal.id));
        if (state.context_menu_is_active) {
          const menu = getElement("calendar_day_context_menu");
          if (menu) menu.classList.add("hidden");
        }
        if (state.active_day_id) {
          const day = getElement(state.active_day_id);
          if (day) day.focus();
        }
        state.modal_is_active = false;
        state.context_menu_is_active = false;
        return;
      }
      if (!e.repeat && (isPrimaryShortcutModalChord(e) || isQuestionShortcut(e, key))) {
        if (isEditableTarget(e.target)) return;
        e.preventDefault();
        e.stopPropagation();
        toggleHelpModal();
        return;
      }

      if (!e.repeat && key !== '') {
        const singleKey = key.toLowerCase();
        const isNavShortcut = Object.prototype.hasOwnProperty.call(navShortcutRoutes, singleKey);
        const allowNavShortcut = hasNoShortcutModifiers(e) || hasGlobalShortcutModifiers(e);
        if (isNavShortcut && allowNavShortcut) {
          if (isEditableTarget(e.target) || hasOpenDialog()) return;
          e.preventDefault();
          e.stopPropagation();
          triggerNavShortcut(singleKey);
          return;
        }
      }

      if (!e.altKey || e.shiftKey || e.ctrlKey) return;
      queryAll("a[accesskey]").forEach((el) => {
        if (key !== '' && key === el.getAttribute("accesskey")) {
          e.preventDefault();
          e.stopPropagation();
          el.click();
        }
      });
    };
    document.addEventListener("keydown", handleGlobalKeys);

    // Text-to-Speech on focus
    queryAll("div[data-tts]").forEach(div => {
      div.addEventListener("focus", () => {
        const tts = div.getAttribute("data-tts");
        if (tts && state.audio_feedback === "all") textToSpeech(tts);
      });
    });

    ensureAllDialogsChrome();
    initKeyboardShortcutComboAccessibility();

    document.addEventListener('click', (event) => {
      const target = event.target;
      if (!(target instanceof Element)) return;

      const closeControl = target.closest('[data-dialog-close]');
      if (!(closeControl instanceof Element)) return;

      const dialogId = closeControl.getAttribute('data-dialog-close') || closeControl.closest('dialog')?.id;
      if (!dialogId) return;

      event.preventDefault();
      closeModal(dialogId);
    });

    // Opt-in outside-click close behavior for dialogs that should close on backdrop click.
    queryAll('dialog[data-dialog-close-on-backdrop="true"]').forEach((dialog) => {
      if (!(dialog instanceof HTMLDialogElement)) return;
      if (dialog.dataset.backdropCloseBound === 'true') return;

      dialog.dataset.backdropCloseBound = 'true';
      dialog.addEventListener('click', (event) => {
        if (event.target !== dialog) {
          return;
        }

        if (!dialog.id) {
          return;
        }

        const dialogLabel = dialog.getAttribute('aria-label') || undefined;
        closeModal(dialog.id, dialogLabel);
      });
    });

    // Modal Close
    queryAll(".modal_close").forEach(button => {
      button.addEventListener("click", () => {
        queryAll("dialog").forEach(modal => closeModal(modal.id));
      });
      button.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " " || e.key === "Spacebar") {
          e.stopPropagation();
          e.preventDefault();
          const dialog_id = e.currentTarget.closest("dialog")?.id;
          if (dialog_id) delay(200).then(() => closeModal(dialog_id));
        }
      });
    });

    // Capslock on password inputs
    queryAll("input[type=password]").forEach(input => {
      input.addEventListener("keyup", (e) => {
        const caps = e.getModifierState?.("CapsLock");
        caps ? activateCapslockWarning() : deactivateCapslockWarning();
      });
    });

    queryAll('[data-help-trigger="true"]').forEach((helpTrigger) => {
      helpTrigger.addEventListener("click", (e) => {
        e.preventDefault();
        toggleHelpModal();
      });
    });

    // Admin navigation popover (single trigger, nested admin hierarchy)
    const adminPopoverToggle = document.querySelector('[data-admin-popover-toggle="admin-nav-popover"]');
    const adminPopover = document.getElementById('admin-nav-popover');
    // Use controlled popover behavior for nav menus to avoid global backdrop side effects.
    const supportsPopoverApi = false;
    let adminPopoverPortalMounted = false;

    const ensureAdminPopoverPortal = () => {
      if (!(adminPopover instanceof HTMLElement)) return;
      if (adminPopoverPortalMounted) return;
      if (!(document.body instanceof HTMLElement)) return;
      document.body.appendChild(adminPopover);
      adminPopover.classList.add('is-portal');
      adminPopoverPortalMounted = true;
    };

    const positionAdminPopover = () => {
      if (!(adminPopover instanceof HTMLElement) || !(adminPopoverToggle instanceof HTMLElement)) return;
      if (!isAdminPopoverOpen()) return;

      const edgePad = 8;
      const gap = 6;
      const triggerRect = adminPopoverToggle.getBoundingClientRect();
      const navPosition = document.body.getAttribute('data-nav-primary-position') || 'left';

      const popoverWidth = adminPopover.offsetWidth || 220;
      const popoverHeight = adminPopover.offsetHeight || 180;

      let left = triggerRect.left;
      let top = triggerRect.bottom + gap;

      if (navPosition === 'left') {
        left = triggerRect.right + gap;
        top = triggerRect.top;
      } else if (navPosition === 'right') {
        left = triggerRect.left - popoverWidth - gap;
        top = triggerRect.top;
      }

      left = Math.max(edgePad, Math.min(left, window.innerWidth - popoverWidth - edgePad));
      top = Math.max(edgePad, Math.min(top, window.innerHeight - popoverHeight - edgePad));

      adminPopover.style.left = `${Math.round(left)}px`;
      adminPopover.style.top = `${Math.round(top)}px`;
    };

    const isAdminPopoverOpen = () => {
      if (!adminPopover) return false;
      if (supportsPopoverApi) {
        return adminPopover.matches(':popover-open');
      }
      return adminPopover.classList.contains('is-open');
    };

    const syncAdminPopoverState = () => {
      if (!(adminPopoverToggle instanceof HTMLElement)) return;
      adminPopoverToggle.setAttribute('aria-expanded', isAdminPopoverOpen() ? 'true' : 'false');
    };

    const closeAdminPopover = () => {
      if (!adminPopover) return;
      if (supportsPopoverApi) {
        if (adminPopover.matches(':popover-open')) {
          adminPopover.hidePopover();
        }
      } else {
        adminPopover.classList.remove('is-open');
      }
      adminPopover.setAttribute('hidden', 'hidden');
      window.removeEventListener('resize', positionAdminPopover);
      window.removeEventListener('scroll', positionAdminPopover, true);
      syncAdminPopoverState();
    };

    const getAdminMenuItems = () => {
      if (!(adminPopover instanceof HTMLElement)) {
        return [];
      }

      return Array.from(adminPopover.querySelectorAll('[role="menuitem"]'))
        .filter((item) => item instanceof HTMLElement);
    };

    const focusAdminMenuItem = (index) => {
      const items = getAdminMenuItems();
      if (items.length === 0) {
        return;
      }

      const normalized = ((index % items.length) + items.length) % items.length;
      const target = items[normalized];
      if (target instanceof HTMLElement) {
        target.focus();
      }
    };

    const focusAdjacentAdminMenuItem = (current, delta) => {
      const items = getAdminMenuItems();
      if (items.length === 0) {
        return;
      }

      const currentIndex = current instanceof HTMLElement ? items.indexOf(current) : -1;
      const baseIndex = currentIndex >= 0 ? currentIndex : 0;
      focusAdminMenuItem(baseIndex + delta);
    };

    const openAdminPopover = () => {
      if (!adminPopover) return;
      ensureAdminPopoverPortal();
      adminPopover.removeAttribute('hidden');
      if (supportsPopoverApi) {
        if (!adminPopover.matches(':popover-open')) {
          adminPopover.showPopover();
        }
      } else {
        adminPopover.classList.add('is-open');
      }
      positionAdminPopover();
      window.addEventListener('resize', positionAdminPopover);
      window.addEventListener('scroll', positionAdminPopover, true);
      syncAdminPopoverState();
    };

    if (adminPopoverToggle instanceof HTMLElement && adminPopover instanceof HTMLElement) {
      // Portal-mount the popover immediately so the admin li contains only its <a>
      // child at page load, matching the single-child structure of all other nav li items.
      ensureAdminPopoverPortal();

      adminPopoverToggle.addEventListener('click', (event) => {
        event.preventDefault();
        if (isAdminPopoverOpen()) {
          closeAdminPopover();
          return;
        }
        openAdminPopover();
      });

      if (!supportsPopoverApi) {
        document.addEventListener('click', (event) => {
          if (!isAdminPopoverOpen()) return;
          const target = event.target;
          if (!(target instanceof Node)) return;
          if (adminPopover.contains(target) || adminPopoverToggle.contains(target)) return;
          closeAdminPopover();
        });
      } else {
        adminPopover.addEventListener('toggle', syncAdminPopoverState);
      }

      adminPopoverToggle.addEventListener('keydown', (event) => {
        if (event.key === ' ' || event.key === 'Spacebar' || event.key === 'Enter') {
          event.preventDefault();
          if (isAdminPopoverOpen()) {
            closeAdminPopover();
          } else {
            openAdminPopover();
          }
          return;
        }

        if (event.key === 'ArrowDown') {
          event.preventDefault();
          openAdminPopover();
          focusAdminMenuItem(0);
          return;
        }

        if (event.key === 'ArrowUp') {
          event.preventDefault();
          openAdminPopover();
          const items = getAdminMenuItems();
          if (items.length > 0) {
            focusAdminMenuItem(items.length - 1);
          }
          return;
        }

        if (event.key === 'Home') {
          event.preventDefault();
          openAdminPopover();
          focusAdminMenuItem(0);
          return;
        }

        if (event.key === 'End') {
          event.preventDefault();
          openAdminPopover();
          const items = getAdminMenuItems();
          if (items.length > 0) {
            focusAdminMenuItem(items.length - 1);
          }
        }
      });

      adminPopover.addEventListener('keydown', (event) => {
        const target = event.target;
        const activeItem = target instanceof HTMLElement ? target.closest('[role="menuitem"]') : null;

        if (event.key === 'Escape') {
          event.preventDefault();
          closeAdminPopover();
          adminPopoverToggle.focus();
          return;
        }

        if (event.key === 'ArrowDown') {
          event.preventDefault();
          focusAdjacentAdminMenuItem(activeItem, 1);
          return;
        }

        if (event.key === 'ArrowUp') {
          event.preventDefault();
          focusAdjacentAdminMenuItem(activeItem, -1);
          return;
        }

        if (event.key === 'Home') {
          event.preventDefault();
          focusAdminMenuItem(0);
          return;
        }

        if (event.key === 'End') {
          event.preventDefault();
          const items = getAdminMenuItems();
          if (items.length > 0) {
            focusAdminMenuItem(items.length - 1);
          }
          return;
        }

        if ((event.key === ' ' || event.key === 'Spacebar' || event.key === 'Enter') && activeItem instanceof HTMLElement) {
          event.preventDefault();
          activeItem.click();
        }
      });

      adminPopover.querySelectorAll('[role="menuitem"]').forEach((item) => {
        item.addEventListener('click', () => {
          closeAdminPopover();
        });
      });

      closeAdminPopover();
      syncAdminPopoverState();
    }

    // Page Tabs
    const tabs = queryAll("[data-tab-target]");
    const tabContents = queryAll("[data-tab-content]");
    function activateTab(tab) {
      const target = query(tab.dataset.tabTarget);
      if (!target) return;
      tabContents.forEach(tc => tc.classList.remove("active"));
      tabs.forEach(t => t.classList.remove("active"));
      tab.classList.add("active");
      target.classList.add("active");
    }
    tabs.forEach(tab => {
      tab.addEventListener("click", () => activateTab(tab));
      tab.addEventListener("keydown", e => {
        if (e.key === "Enter" || e.key === " " || e.key === "Spacebar") {
          e.preventDefault();
          activateTab(tab);
        }
      });
    });

    // Dashboard
    const dashboardEl = getElement("dashboard");
    if (dashboardEl) {
      const heartbeatContentEl = getElement("ws_heartbeat_content");
      let heartbeatIntervalId = null;
      let heartbeatInFlight = false;

      const renderHeartbeatStatus = (message, redisHitRate, activeSessions) => {
        if (!heartbeatContentEl) return;
        if (redisHitRate === null && activeSessions === null) {
          heartbeatContentEl.textContent = message;
          return;
        }
        const redisRow = redisHitRate !== null
          ? `<span class="dashboard-heartbeat-label">Redis hit rate</span><span class="dashboard-heartbeat-value">${String(redisHitRate)}%</span>`
          : '';
        const sessionsRow = activeSessions !== null
          ? `<span class="dashboard-heartbeat-label">Active sessions</span><span class="dashboard-heartbeat-value">${String(activeSessions)}</span>`
          : '';
        trustLayer.setHTML(heartbeatContentEl, `
          <div class="dashboard-heartbeat-grid">
            <span class="dashboard-heartbeat-label">Status</span><span class="dashboard-heartbeat-value">${message}</span>
            ${redisRow}
            ${sessionsRow}
          </div>
        `);
      };

      const pollDashboardHeartbeat = async () => {
        if (!heartbeatContentEl || heartbeatInFlight) {
          return;
        }

        heartbeatInFlight = true;

        try {
          const response = await fetch('/ws/', {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
              Accept: 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
          });

          if (!response.ok) {
            throw new Error(`Heartbeat HTTP ${response.status}`);
          }

          const payload = await response.json();
          const now = new Date().toLocaleTimeString();
          const service = typeof payload?.service === 'string' ? payload.service : 'ws';
          const status = typeof payload?.status === 'string' ? payload.status : 'unknown';
          const redisHitRate = typeof payload?.redis_hit_rate === 'number' ? payload.redis_hit_rate : null;
          const activeSessions = typeof payload?.active_sessions === 'number' ? payload.active_sessions : null;
          renderHeartbeatStatus(`${status} (${service}) at ${now}`, redisHitRate, activeSessions);
        } catch (error) {
          PW.warn('Dashboard heartbeat failed', error);
          renderHeartbeatStatus('Heartbeat error. Retrying in 5s.', null, null);
        } finally {
          heartbeatInFlight = false;
        }
      };

      const startDashboardHeartbeat = () => {
        if (!heartbeatContentEl || heartbeatIntervalId !== null) {
          return;
        }

        pollDashboardHeartbeat();
        heartbeatIntervalId = window.setInterval(pollDashboardHeartbeat, 5000);
      };

      const stopDashboardHeartbeat = () => {
        if (heartbeatIntervalId === null) {
          return;
        }

        window.clearInterval(heartbeatIntervalId);
        heartbeatIntervalId = null;
      };

      const handleDashboardToggle = (event) => {
        const isEscapeKey = event.key === "Escape" || event.code === "Escape";
        if (event.shiftKey && isEscapeKey) {
          event.preventDefault();
          dashboardEl.classList.toggle("active");
          if (dashboardEl.classList.contains("active")) {
            startDashboardHeartbeat();
          } else {
            stopDashboardHeartbeat();
          }
        }
      };

      document.addEventListener("keydown", handleDashboardToggle);

      const closeBtn = getElement("dashboardCloseButton");

      if (closeBtn) {
        closeBtn.addEventListener("click", () => {
          dashboardEl.classList.remove("active");
          stopDashboardHeartbeat();
        });
      }

      let isDragging = false;
      let isResizing = false;

      let rafId = null;
      let pendingEvent = null;

      let startPointerX = 0;
      let startPointerY = 0;

      let startX = 0;
      let startY = 0;

      let currentX = 0;
      let currentY = 0;

      let startWidth = 0;
      let startHeight = 0;

      /* -------------------------
         INITIAL CENTER POSITION
      -------------------------- */

      dashboardEl.classList.add("dashboard-prep");

      const rect = dashboardEl.getBoundingClientRect();

      currentX = (window.innerWidth - rect.width) / 2;
      currentY = (window.innerHeight - rect.height) / 4;

      dashboardEl.style.transform = `translate(${currentX}px, ${currentY}px)`;
      dashboardEl.classList.remove("dashboard-prep");

      /* -------------------------
         POINTER HANDLERS
      -------------------------- */

      function onPointerDown(e) {

        const interactive = e.target.closest("button, input, select, textarea, a");

        if (e.target.id === "dashboardResizeGrip") {

          isResizing = true;

          const rect = dashboardEl.getBoundingClientRect();

          startPointerX = e.clientX;
          startPointerY = e.clientY;

          startWidth = rect.width;
          startHeight = rect.height;

          dashboardEl.setPointerCapture(e.pointerId);
          e.preventDefault();
          return;
        }

        if (interactive) return;

        isDragging = true;

        startPointerX = e.clientX;
        startPointerY = e.clientY;

        startX = currentX;
        startY = currentY;

        dashboardEl.setPointerCapture(e.pointerId);
        e.preventDefault();
      }

      function onPointerMove(e) {
        pendingEvent = e;

        if (!rafId) {
          rafId = requestAnimationFrame(processFrame);
        }
      }

      function processFrame() {

        if (!pendingEvent) {
          rafId = null;
          return;
        }

        const e = pendingEvent;
        pendingEvent = null;

        if (isDragging) {

          const dx = e.clientX - startPointerX;
          const dy = e.clientY - startPointerY;

          let newX = startX + dx;
          let newY = startY + dy;

          const rect = dashboardEl.getBoundingClientRect();

          const maxX = window.innerWidth - rect.width;
          const maxY = window.innerHeight - rect.height;

          newX = Math.max(0, Math.min(newX, maxX));
          newY = Math.max(0, Math.min(newY, maxY));

          currentX = newX;
          currentY = newY;

          dashboardEl.style.transform = `translate(${newX}px, ${newY}px)`;
        }

        if (isResizing) {

          const dx = e.clientX - startPointerX;
          const dy = e.clientY - startPointerY;

          const minWidth = 280;
          const minHeight = 180;

          const newWidth = Math.max(minWidth, startWidth + dx);
          const newHeight = Math.max(minHeight, startHeight + dy);

          dashboardEl.style.width = `${newWidth}px`;
          dashboardEl.style.height = `${newHeight}px`;
        }

        rafId = null;
      }

      function onPointerUp(e) {

        isDragging = false;
        isResizing = false;

        pendingEvent = null;

        if (rafId) {
          cancelAnimationFrame(rafId);
          rafId = null;
        }

        try {
          dashboardEl.releasePointerCapture(e.pointerId);
        } catch {}
      }

      dashboardEl.addEventListener("pointerdown", onPointerDown);
      dashboardEl.addEventListener("pointermove", onPointerMove);
      dashboardEl.addEventListener("pointerup", onPointerUp);
    }

    // Trust Layer Dialog
    const trustDialog = document.getElementById("trust_layer_warning_dialog");
    if (trustDialog) {
      const closeBtn = trustDialog.querySelector("[data-trust-close]");
      if (closeBtn) {
        closeBtn.addEventListener("click", () => trustDialog.close());
      }
      trustDialog.addEventListener("click", (e) => {
        const rect = trustDialog.getBoundingClientRect();
        const clickedInDialog = e.clientX >= rect.left && e.clientX <= rect.right &&
                                e.clientY >= rect.top && e.clientY <= rect.bottom;
        if (!clickedInDialog) {
          trustDialog.close();
        }
      });
    }

    // Sign Out Listeners (example of delegation)
    addClickAndEnterListener("call_signout_modal", (e) => { e.preventDefault(); openModal("modal_signout", config.SIGN_OUT); });
    addClickAndEnterListener("signout_cancel_btn", (e) => { e.preventDefault(); closeModal("modal_signout", config.SIGN_OUT); });

    // Log initialization complete
    const initColor = 'color: #00aa00; font-weight: bold; font-size: 12px; background: #f0fff0; padding: 6px; border-radius: 4px;';
    PW.log('✓ PayCalCore initialization complete');
    if (window.PAYCAL_DIAGNOSTICS) {
      PW.log('📍 Session: ' + (config.USER_LOCALE || 'unknown'));
      PW.log('⏱ Page: ' + window.PAYCAL_DIAGNOSTICS.page + ' (' + window.PAYCAL_DIAGNOSTICS.load_time_ms + 'ms)');
    }

    // Initialize navigation toggle for sidebar mode
    if (NavigationToggle && typeof NavigationToggle.init === 'function') {
      try {
        NavigationToggle.init();
      } catch (err) {
        PW.warn('Navigation toggle initialization failed:', err);
      }
    }

    const getOrganizationsNavLink = () => document.querySelector(
      '.nav_menu--primary a[href="/organizations"], .nav_menu--primary a[href="/organizations/"]'
    );

    const setOrganizationsNavNotificationDot = (totalUnread) => {
      const navLink = getOrganizationsNavLink();
      if (!(navLink instanceof HTMLElement)) {
        return;
      }

      const unread = Math.max(0, Number(totalUnread || 0));
      let dot = navLink.querySelector('.nav_notification_dot');

      if (unread <= 0) {
        if (dot) {
          dot.remove();
        }
        navLink.removeAttribute('data-has-notifications');
        navLink.removeAttribute('aria-label');
        return;
      }

      if (!(dot instanceof HTMLElement)) {
        dot = document.createElement('span');
        dot.className = 'nav_notification_dot';
        dot.setAttribute('aria-hidden', 'true');
        navLink.appendChild(dot);
      }

      navLink.setAttribute('data-has-notifications', '1');
      navLink.setAttribute('aria-label', unread > 99
        ? 'Organizations, 99 plus unread notifications'
        : `Organizations, ${String(unread)} unread notification${unread === 1 ? '' : 's'}`
      );
    };

    const pollOrganizationsNotifications = async () => {
      const params = new URLSearchParams({
        channel: 'organization_notifications',
      });

      if (orgNotificationsSignature !== '') {
        params.set('since_signature', orgNotificationsSignature);
      }

      const response = await fetch(`/ws/?${params.toString()}`, {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store',
      });

      if (!response.ok) {
        throw new Error(`Organization notifications channel failed (${response.status}).`);
      }

      const payload = await response.json();
      if (!payload || payload.status !== 'success') {
        throw new Error(String(payload?.error || payload?.message || 'Organization notifications payload invalid.'));
      }

      orgNotificationsSignature = String(payload.latest_signature || '');
      setOrganizationsNavNotificationDot(Number(payload.total_unread || 0));
    };

    const startOrganizationsNotificationsPolling = () => {
      pollOrganizationsNotifications().catch((error) => PW.warn(error));
      if (orgNotificationsIntervalId !== null) {
        clearInterval(orgNotificationsIntervalId);
        orgNotificationsIntervalId = null;
      }
      orgNotificationsIntervalId = window.setInterval(() => {
        pollOrganizationsNotifications().catch((error) => PW.warn(error));
      }, 60000);
    };

    startOrganizationsNotificationsPolling();
    orgNotificationsUpdateHandler = () => {
      pollOrganizationsNotifications().catch((error) => PW.warn(error));
    };
    window.addEventListener('paycal:notifications-updated', orgNotificationsUpdateHandler);

    // Cleanup function
    cleanupHandler = () => {
      clearInterval(clockInterval);
      if (orgNotificationsIntervalId !== null) {
        clearInterval(orgNotificationsIntervalId);
        orgNotificationsIntervalId = null;
      }
      if (orgNotificationsUpdateHandler) {
        window.removeEventListener('paycal:notifications-updated', orgNotificationsUpdateHandler);
        orgNotificationsUpdateHandler = null;
      }
      orgNotificationsSignature = '';
      const trayPopover = document.getElementById('tray_time_popover');
      trayPopover?.remove();
      document.removeEventListener("keyup", handleEscapeSignout);
      document.removeEventListener('keydown', handleDialogFocusTrap, true);
      document.removeEventListener("keydown", handleGlobalKeys);
      // ... remove other listeners as needed
      hasInitialized = false;
      cleanupHandler = null;
    };

    return cleanupHandler;
  }

  return {
    showTrustLayerWarning,
    config,
    state,
    getElement,
    query,
    queryAll,
    addAudioFocusListener,
    activateCapslockWarning,
    deactivateCapslockWarning,
    closeModal,
    deleteResource,
    copyAttribute,
    getDataAttribute,
    delay,
    debounce,
    addClickAndEnterListener,
    escapeCssId,
    formatPhoneNumberValue,
    formatPhoneNumber,
    formatVerificationCode,
    generateSiteUUID,
    getLanguageName,
    openModal,
    getCurrentPage,
    readResource,
    formatReadableDate,
    sanitizedText: trustLayer.sanitizedText,
    sanitizeHTML: trustLayer.sanitizeHTML,
    toTrustedHTML: trustLayer.toTrustedHTML,
    setHTML: trustLayer.setHTML,
    insertHTML: trustLayer.insertHTML,
    replaceOuterHTML: trustLayer.replaceOuterHTML,
    removeElement,
    setSelectOption,
    showToast,
    updateStatusMessage,
    textToSpeech,
    toTitleCase,
    togglePasswordVisibility,
    updateResource,
    getBrowserVendor,
    init
  };
})();

export default PayCalCore;

if (typeof window !== 'undefined') {
  window.PayCalCore = PayCalCore;
  if (!window.__PAYCAL_CORE_AUTO_INIT__) {
    window.__PAYCAL_CORE_AUTO_INIT__ = true;

    const bootPayCalCore = () => {
      try {
        PayCalCore.init();
      } catch (err) {
        PW.error('PayCalCore auto-init failed:', err);
      }
    };

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', bootPayCalCore, { once: true });
    } else {
      bootPayCalCore();
    }
  }
}
