/**
 * PayCalCore - Locale helpers
 *
 * Shared user-locale resolution for page modules that need Intl formatting.
 */

export function resolveUserLocale(fallback = 'en-US') {
  const coreLocale = typeof globalThis !== 'undefined'
    ? String(globalThis?.PayCalCore?.config?.USER_LOCALE || '').trim()
    : '';
  if (coreLocale !== '') {
    return coreLocale;
  }

  const documentLocale = typeof document !== 'undefined'
    ? String(document.documentElement?.lang || '').trim()
    : '';
  if (documentLocale !== '') {
    return documentLocale;
  }

  const navigatorLocale = typeof navigator !== 'undefined' && typeof navigator.language === 'string'
    ? String(navigator.language).trim()
    : '';
  if (navigatorLocale !== '') {
    return navigatorLocale;
  }

  return fallback;
}
