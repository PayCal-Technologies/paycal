import { expect, test } from '@playwright/test';

const DOCUMENT_OK_STATUSES = new Set([200, 204, 301, 302, 304]);
const ASSET_EXTENSIONS = /\.(?:css|js|mjs|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|otf)(?:\?|$)/i;
const BENIGN_CONSOLE_PATTERNS = [
  /favicon/i,
  /ResizeObserver loop/i,
];

export function watchPageHealth(page) {
  const consoleErrors = [];
  const pageErrors = [];
  const failedResponses = [];
  const failedRequests = [];

  page.on('console', (message) => {
    if (message.type() !== 'error') {
      return;
    }

    const text = message.text();
    if (BENIGN_CONSOLE_PATTERNS.some((pattern) => pattern.test(text))) {
      return;
    }
    consoleErrors.push(text);
  });

  page.on('pageerror', (error) => {
    pageErrors.push(error.message);
  });

  page.on('response', (response) => {
    const request = response.request();
    const status = response.status();
    const url = response.url();
    const type = request.resourceType();

    if (type === 'document' && !DOCUMENT_OK_STATUSES.has(status)) {
      failedResponses.push(`${status} ${url}`);
      return;
    }

    if ((status >= 500 || (status >= 400 && ASSET_EXTENSIONS.test(url))) && !url.includes('/api/v1/observability/')) {
      failedResponses.push(`${status} ${url}`);
    }
  });

  page.on('requestfailed', (request) => {
    const failure = request.failure();
    const url = request.url();
    if (url.startsWith('data:') || (failure?.errorText || '').includes('net::ERR_ABORTED')) {
      return;
    }
    failedRequests.push(`${failure?.errorText || 'failed'} ${url}`);
  });

  return {
    assertClean() {
      expect.soft(pageErrors, 'uncaught browser errors').toEqual([]);
      expect.soft(consoleErrors, 'console errors').toEqual([]);
      expect.soft(failedResponses, 'failed document/assets/API responses').toEqual([]);
      expect.soft(failedRequests, 'failed browser requests').toEqual([]);
    },
  };
}

export async function openAppPage(page, path, options = {}) {
  const health = watchPageHealth(page);
  const response = await page.goto(path, {
    waitUntil: 'domcontentloaded',
    ...options,
  });
  await expect(page.locator('body')).toBeVisible();
  await expect(page.locator('body')).not.toContainText(/Fatal error|Parse error|Uncaught|Stack trace|Warning:|Notice:/);

  return {
    health,
    response,
    path: new URL(page.url()).pathname,
    authenticated: new URL(page.url()).pathname !== '/auth/',
  };
}

export async function requireAuthenticated(page, path) {
  const state = await openAppPage(page, path);
  if (!state.authenticated) {
    test.info().annotations.push({
      type: 'auth-required',
      description: `${path} redirected to /auth/; run with a local admin PAYCAL_AUTH session to exercise this page.`,
    });
    return null;
  }
  return state;
}

export async function requirePageSentinel(page, path, sentinel, annotationType = 'page-unavailable') {
  const state = await requireAuthenticated(page, path);
  if (!state) {
    return null;
  }

  const status = state.response?.status() || 0;
  if (status === 401 || status === 403) {
    test.info().annotations.push({
      type: 'authz-required',
      description: `${path} returned HTTP ${status}; run with a local admin account that can access this feature.`,
    });
    return null;
  }

  const locator = page.locator(sentinel).first();
  if ((await locator.count()) === 0) {
    test.info().annotations.push({
      type: annotationType,
      description: `${path} did not expose ${sentinel}; this account may not have that feature enabled.`,
    });
    state.health.assertClean();
    return null;
  }

  await expect(locator).toBeVisible();
  return state;
}

export async function clickIfAvailable(locator) {
  if ((await locator.count()) === 0) {
    return false;
  }
  if (!(await locator.first().isVisible().catch(() => false))) {
    return false;
  }
  if (!(await locator.first().isEnabled().catch(() => false))) {
    return false;
  }
  await locator.first().click();
  return true;
}

export async function expectAnyVisible(page, selectors, label) {
  const joined = selectors.join(', ');
  await expect(page.locator(joined).first(), label).toBeVisible();
}
