(function() {
  'use strict';

  if (window.PayCalCalendarI18n) {
    return;
  }

  let pageI18nCache = null;

  function calendarConfig() {
    return window.PayCalCore?.config ?? window.PC?.config ?? null;
  }

  function pageI18nMap() {
    if (pageI18nCache !== null) {
      return pageI18nCache;
    }

    const legacy = window.__CALENDAR_I18N__;
    if (legacy && typeof legacy === 'object') {
      pageI18nCache = legacy;
      return pageI18nCache;
    }

    const node = document.getElementById('calendar-page-i18n');
    if (node instanceof HTMLScriptElement && node.textContent.trim() !== '') {
      try {
        const parsed = JSON.parse(node.textContent);
        if (parsed && typeof parsed === 'object') {
          pageI18nCache = parsed;
          return pageI18nCache;
        }
      } catch {
        // Fall through to empty map.
      }
    }

    pageI18nCache = {};
    return pageI18nCache;
  }

  function get(key, fallback = '') {
    const fromPage = String(pageI18nMap()[key] ?? '').trim();
    if (fromPage !== '') {
      return fromPage;
    }

    const fromConfig = String(calendarConfig()?.[key] ?? '').trim();
    return fromConfig !== '' ? fromConfig : fallback;
  }

  function userLocale() {
    const locale = String(calendarConfig()?.USER_LOCALE ?? '').trim();
    return locale !== '' ? locale : undefined;
  }

  function formatDate(date, options) {
    return date.toLocaleDateString(userLocale(), options);
  }

  function format(key, fallback, params = {}) {
    let label = get(key, fallback);
    Object.entries(params).forEach(([paramKey, paramValue]) => {
      const token = new RegExp(`\\{${paramKey}\\}`, 'g');
      label = label.replace(token, String(paramValue));
    });
    return label;
  }

  window.PayCalCalendarI18n = Object.freeze({
    config: calendarConfig,
    format,
    formatDate,
    get,
    pageI18nMap,
    userLocale,
  });
})();
