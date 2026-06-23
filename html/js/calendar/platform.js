(function() {
  'use strict';

  const PLATFORM_TOKENS = new Set(['mac', 'win', 'linux', 'ios', 'android', 'unknown']);

  function normalizePlatformToken(value) {
    const normalized = String(value || '').trim().toLowerCase().replace(/^['"]|['"]$/g, '');

    if (normalized === 'iphone' || normalized === 'ipad' || normalized === 'ios') {
      return 'ios';
    }

    if (normalized === 'android') {
      return 'android';
    }

    if (normalized === 'mac' || normalized === 'macos' || normalized === 'macintosh' || normalized === 'mac os x') {
      return 'mac';
    }

    if (normalized === 'win' || normalized === 'windows' || normalized === 'win32' || normalized === 'win64') {
      return 'win';
    }

    if (normalized === 'linux' || normalized === 'x11') {
      return 'linux';
    }

    return PLATFORM_TOKENS.has(normalized) ? normalized : 'unknown';
  }

  function resolvePlatformToken() {
    const override = (() => {
      try {
        return normalizePlatformToken(window.localStorage?.getItem('platformOverride') || '');
      } catch {
        return 'unknown';
      }
    })();
    if (override !== 'unknown') {
      return override;
    }

    const userAgentDataPlatform = normalizePlatformToken(
      window.navigator?.userAgentData?.platform || ''
    );
    if (userAgentDataPlatform !== 'unknown') {
      return userAgentDataPlatform;
    }

    const navigatorPlatform = normalizePlatformToken(window.navigator?.platform || '');
    if (navigatorPlatform !== 'unknown') {
      return navigatorPlatform;
    }

    const serverToken = normalizePlatformToken(document.documentElement.dataset.os || '');
    if (serverToken !== 'unknown') {
      return serverToken;
    }

    return 'win';
  }

  function applyResolvedPlatformToken() {
    const token = resolvePlatformToken();
    document.documentElement.dataset.os = token;

    try {
      window.localStorage?.setItem('platformResolved', token);
    } catch {
      // Ignore storage failures.
    }

    return token;
  }

  window.PayCalCalendarPlatform = Object.freeze({
    applyResolvedPlatformToken,
    normalizePlatformToken,
    resolvePlatformToken,
  });
})();
