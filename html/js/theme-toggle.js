const STORAGE_KEY = 'PayCal_Theme_Mode';
const MODES = new Set(['light', 'dark']);
const COOKIE_MAX_AGE_SECONDS = 60 * 60 * 24 * 1095;

function validMode(value) {
  const normalized = String(value || '').trim().toLowerCase();
  return MODES.has(normalized) ? normalized : '';
}

function readCookieMode() {
  return document.cookie
    .split(';')
    .map((entry) => entry.trim())
    .find((entry) => entry.startsWith(`${STORAGE_KEY}=`))
    ?.split('=')
    .slice(1)
    .join('=') || '';
}

function isAuthenticated() {
  if (document.documentElement.dataset.authenticated === 'true') {
    return true;
  }

  const header = document.getElementById('page_header');
  return header instanceof HTMLElement && !header.classList.contains('nav_component--public');
}

function storedMode() {
  const rootMode = validMode(document.documentElement.dataset.themeMode);
  if (isAuthenticated() && rootMode) {
    return rootMode;
  }

  try {
    const localMode = validMode(window.localStorage.getItem(STORAGE_KEY));
    if (localMode) {
      return localMode;
    }
  } catch {
    // Storage can be unavailable in hardened browsing contexts.
  }

  return validMode(readCookieMode()) || rootMode || 'dark';
}

function persistMode(mode) {
  const expires = `Max-Age=${COOKIE_MAX_AGE_SECONDS}`;
  document.cookie = `${STORAGE_KEY}=${encodeURIComponent(mode)}; ${expires}; Path=/; SameSite=Lax; Secure`;
  try {
    window.localStorage.setItem(STORAGE_KEY, mode);
  } catch {
    // Cookie persistence is enough when localStorage is blocked.
  }
}

function themeNameForMode(currentName, mode) {
  const normalized = String(currentName || '').trim().toLowerCase();
  if (/_(light|dark)$/.test(normalized)) {
    return normalized.replace(/_(light|dark)$/, `_${mode}`);
  }
  return `win10_${mode}`;
}

function themeFamilyFromStylesheet() {
  const rootFamily = String(document.documentElement.dataset.themeFamily || '').trim().toLowerCase();
  if (/^[a-z0-9_]+$/.test(rootFamily)) {
    return rootFamily;
  }

  const themeStylesheet = document.getElementById('theme_stylesheet');
  if (!(themeStylesheet instanceof HTMLLinkElement)) {
    return 'paycal_blue';
  }

  const href = new URL(themeStylesheet.href, window.location.href);
  const currentName = String(href.searchParams.get('name') || '').trim().toLowerCase();
  const match = currentName.match(/^([a-z0-9_]+)_(?:light|dark)$/);
  return match ? match[1] : 'paycal_blue';
}

function updateThemeStylesheet(mode) {
  const themeStylesheet = document.getElementById('theme_stylesheet');
  if (!(themeStylesheet instanceof HTMLLinkElement)) {
    return;
  }

  const href = new URL(themeStylesheet.href, window.location.href);
  const currentName = href.searchParams.get('name') || '';
  href.searchParams.set('name', themeNameForMode(currentName, mode));
  href.searchParams.set('themeMode', mode);
  href.searchParams.set('ts', String(Date.now()));
  themeStylesheet.href = href.toString();
}

function refreshCoreStylesheet(mode) {
  const stylesheet = document.querySelector('link[rel="stylesheet"][href*="/css/?"]');
  if (!(stylesheet instanceof HTMLLinkElement)) {
    return;
  }

  const href = new URL(stylesheet.href, window.location.href);
  href.searchParams.set('themeMode', mode);
  href.searchParams.set('ts', String(Date.now()));
  stylesheet.href = href.toString();
}

function syncHeaderToggle(mode) {
  document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
    toggle.setAttribute('data-theme-mode', mode);
    if (toggle instanceof HTMLElement) {
      toggle.dataset.themeCurrent = mode;
    }
  });
  document.querySelectorAll('[data-theme-mode-option]').forEach((option) => {
    if (option instanceof HTMLInputElement) {
      option.checked = option.value === mode;
    }
  });
  document.querySelectorAll('[data-theme-toggle-status]').forEach((status) => {
    status.textContent = `${mode} mode selected`;
  });
}

function syncSettingsControls(mode) {
  const variantPicker = document.getElementById('variant_picker');
  if (variantPicker instanceof HTMLInputElement || variantPicker instanceof HTMLSelectElement) {
    variantPicker.value = mode;
    variantPicker.dispatchEvent(new Event('change', { bubbles: true }));
  }

  document.querySelectorAll('.settings_mode_option').forEach((option) => {
    if (option instanceof HTMLButtonElement || option instanceof HTMLInputElement) {
      const optionMode = validMode(option.dataset.variantValue || option.dataset.mode || option.value);
      option.toggleAttribute('aria-pressed', optionMode === mode);
      option.classList.toggle('is-selected', optionMode === mode);
      if (option instanceof HTMLInputElement) {
        option.checked = optionMode === mode;
      }
    }
  });
}

function csrfTokenFromResponse(data) {
  const payload = data && typeof data === 'object' && data.data && typeof data.data === 'object'
    ? data.data
    : {};
  return String(
    data?.csrf_token
    || data?.settings_csrf_token
    || payload.csrf_token
    || payload.settings_csrf_token
    || ''
  ).trim();
}

function updateSettingsCsrfToken(value) {
  if (!value) {
    return;
  }

  document.querySelectorAll('.settings_csrf_token, .settings_workspace input[name="csrf_token"], form[id^="account_"] input[name="csrf_token"]').forEach((input) => {
    if (input instanceof HTMLInputElement) {
      input.value = value;
    }
  });
}

async function fetchSettingsCsrfToken() {
  const existing = document.querySelector('.account_style_form input[name="csrf_token"], .settings_csrf_token, .settings_workspace input[name="csrf_token"], form[id^="account_"] input[name="csrf_token"]');
  if (existing instanceof HTMLInputElement && existing.value) {
    return existing.value;
  }

  const response = await fetch('/api/v1/settings/nonce', {
    method: 'GET',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
    },
  });
  if (!response.ok) {
    throw new Error(`Settings nonce failed: ${response.status}`);
  }

  const data = await response.json();
  const token = csrfTokenFromResponse(data);
  updateSettingsCsrfToken(token);
  if (!token) {
    throw new Error('Settings nonce missing');
  }

  return token;
}

async function saveAuthenticatedMode(mode) {
  const csrfToken = await fetchSettingsCsrfToken();
  const formData = new FormData();
  formData.set('csrf_token', csrfToken);
  formData.set('theme', themeFamilyFromStylesheet());
  formData.set('variant', mode);

  const response = await fetch('/api/v1/settings/style/update/', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-CSRF-Token': csrfToken,
    },
    body: formData,
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok || String(data?.status || '').toLowerCase() === 'error') {
    throw new Error(String(data?.message || `Settings update failed: ${response.status}`));
  }

  updateSettingsCsrfToken(csrfTokenFromResponse(data));
}

function applyMode(mode, options = {}) {
  const normalized = validMode(mode) || 'dark';
  document.documentElement.dataset.themeMode = normalized;
  document.documentElement.classList.toggle('dark-mode', normalized === 'dark');
  document.documentElement.classList.toggle('light-mode', normalized === 'light');

  syncHeaderToggle(normalized);
  syncSettingsControls(normalized);

  if (options.persist !== false && !isAuthenticated()) {
    persistMode(normalized);
  }
  if (options.refreshStylesheets !== false) {
    updateThemeStylesheet(normalized);
    refreshCoreStylesheet(normalized);
  }

  window.dispatchEvent(new CustomEvent('paycal:theme-mode-change', {
    detail: { mode: normalized },
  }));
}

async function changeMode(mode) {
  const previous = validMode(document.documentElement.dataset.themeMode) || storedMode();
  const normalized = validMode(mode) || 'dark';
  applyMode(normalized);

  if (!isAuthenticated()) {
    return;
  }

  try {
    await saveAuthenticatedMode(normalized);
    try {
      window.localStorage.removeItem(STORAGE_KEY);
    } catch {
      // Server persistence is authoritative for authenticated users.
    }
    document.cookie = `${STORAGE_KEY}=; Max-Age=0; Path=/; SameSite=Lax; Secure`;
  } catch {
    applyMode(previous, { persist: false });
  }
}

document.addEventListener('change', (event) => {
  const target = event.target;
  if (!(target instanceof HTMLInputElement) || !target.matches('[data-theme-mode-option]')) {
    return;
  }

  changeMode(target.value);
});

applyMode(storedMode(), { refreshStylesheets: false });
