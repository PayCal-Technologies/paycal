(() => {
  'use strict';

  const STORAGE_KEY = 'paycal_org_dek_auto_bootstrap_last_ms';
  const MIN_INTERVAL_MS = 5 * 60 * 1000;

  const now = Date.now();
  const lastRaw = window.localStorage ? window.localStorage.getItem(STORAGE_KEY) : null;
  const last = Number(lastRaw || '0');
  if (Number.isFinite(last) && last > 0 && (now - last) < MIN_INTERVAL_MS) {
    return;
  }

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const run = () => {
    const body = new URLSearchParams();
    body.set('trigger', 'page_visit');

    fetch('/api/v1/organizations/encryption/auto-bootstrap', {
      method: 'POST',
      credentials: 'same-origin',
      keepalive: true,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrfToken,
      },
      body,
    }).finally(() => {
      if (window.localStorage) {
        window.localStorage.setItem(STORAGE_KEY, String(Date.now()));
      }
    });
  };

  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(run, { timeout: 1500 });
  } else {
    window.setTimeout(run, 0);
  }
})();
