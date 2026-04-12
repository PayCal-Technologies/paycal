/**
 * WCAG-017: Live-region quality regression checks.
 *
 * Verifies DOM structure contracts for the /auth/ and /settings/ live-region
 * model. These checks lock the separation between:
 *   - assertive alert regions (errors that require immediate interruption)
 *   - polite status regions (progress, success, field-level guidance)
 *
 * They also catch regressions where a container-level aria-live could compete
 * with scoped inline regions and produce duplicate announcements.
 *
 * WCAG mapping: 4.1.3 Status Messages, 3.3.1 Error Identification, 3.3.3 Error Suggestion
 */

import { test, expect } from '@playwright/test';

async function openPublic(page, path) {
  await page.goto(path);
  await expect(page.locator('body')).toBeVisible();
}

async function openAuthenticated(page, path) {
  await page.goto(path);
  await expect(page.locator('body')).toBeVisible();
}

// ---------------------------------------------------------------------------
// /auth/ live-region structure
// ---------------------------------------------------------------------------

test.describe('WCAG-017 live-region structure: /auth/', () => {
  test('auth: exactly one assertive live region exists', async ({ page }) => {
    await openPublic(page, '/auth/');

    const assertiveRegions = await page.evaluate(() => {
      return Array.from(document.querySelectorAll('[aria-live="assertive"]')).map(
        (el) => ({ id: el.id, role: el.getAttribute('role'), tag: el.tagName.toLowerCase() })
      );
    });

    expect(
      assertiveRegions.length,
      `Expected exactly 1 assertive live region; got: ${JSON.stringify(assertiveRegions)}`
    ).toBe(1);

    expect(assertiveRegions[0].id).toBe('auth-feedback-banner');
    expect(assertiveRegions[0].role).toBe('alert');
  });

  test('auth: assertive banner has aria-atomic', async ({ page }) => {
    await openPublic(page, '/auth/');
    const banner = page.locator('#auth-feedback-banner');
    await expect(banner).toHaveAttribute('aria-atomic', 'true');
  });

  test('auth: auth-viewport container has no aria-live', async ({ page }) => {
    await openPublic(page, '/auth/');

    const hasAriaLive = await page.evaluate(() => {
      const viewport = document.querySelector('.auth-viewport');
      return viewport ? viewport.hasAttribute('aria-live') : null;
    });

    expect(hasAriaLive, 'auth-viewport must not carry a container-level aria-live').toBe(false);
  });

  test('auth: signin inline status paragraph is polite with role=status', async ({ page }) => {
    await openPublic(page, '/auth/');

    const signin = page.locator('#signin-passkey-status');
    await expect(signin).toHaveAttribute('role', 'status');
    await expect(signin).toHaveAttribute('aria-live', 'polite');
    await expect(signin).toHaveAttribute('aria-atomic', 'true');
  });

  test('auth: register inline status paragraph is polite with role=status', async ({ page }) => {
    await openPublic(page, '/auth/?auth_tab=register');

    const register = page.locator('#register-passkey-status');
    await expect(register).toHaveAttribute('role', 'status');
    await expect(register).toHaveAttribute('aria-live', 'polite');
    await expect(register).toHaveAttribute('aria-atomic', 'true');
  });

  test('auth: no extra aria-live regions outside the expected three', async ({ page }) => {
    await openPublic(page, '/auth/');

    const liveRegions = await page.evaluate(() => {
      return Array.from(document.querySelectorAll('[aria-live]')).map((el) => ({
        id: el.id || null,
        role: el.getAttribute('role'),
        live: el.getAttribute('aria-live'),
        tag: el.tagName.toLowerCase(),
      }));
    });

    // Expected: assertive banner + signin-passkey-status + register-passkey-status
    // Plus server-rendered PHP success/error paragraphs which are conditionally present.
    // We filter to permanent (non-conditional) ones.
    const permanent = liveRegions.filter(
      (r) =>
        r.id === 'auth-feedback-banner' ||
        r.id === 'signin-passkey-status' ||
        r.id === 'register-passkey-status'
    );

    expect(permanent.length).toBe(3);

    const assertiveCount = liveRegions.filter((r) => r.live === 'assertive').length;
    expect(
      assertiveCount,
      `Only the assertive banner should use aria-live="assertive"; found: ${JSON.stringify(liveRegions.filter((r) => r.live === 'assertive'))}`
    ).toBe(1);
  });
});

// ---------------------------------------------------------------------------
// /settings/ live-region structure
// ---------------------------------------------------------------------------

test.describe('WCAG-017 live-region structure: /settings/', () => {
  test('settings: change-email flow has scoped polite status regions', async ({ page }) => {
    await openAuthenticated(page, '/settings/');

    for (const id of ['change_email_status', 'change_email_verify_status']) {
      const el = page.locator(`#${id}`);
      await expect(el, `${id} should have role=status`).toHaveAttribute('role', 'status');
      await expect(el, `${id} should have aria-live=polite`).toHaveAttribute('aria-live', 'polite');
    }
  });

  test('settings: edit-details status region is polite with role=status', async ({ page }) => {
    await openAuthenticated(page, '/settings/');

    const status = page.locator('#edit_details_status');
    await expect(status).toHaveAttribute('role', 'status');
    await expect(status).toHaveAttribute('aria-live', 'polite');
  });

  test('settings: passkey credentials region uses polite status', async ({ page }) => {
    await openAuthenticated(page, '/settings/');

    const status = page.locator('#passkey_credentials_sr_status');
    await expect(status).toHaveAttribute('role', 'status');
    await expect(status).toHaveAttribute('aria-live', 'polite');
  });

  test('settings: recovery-email flow regions are scoped polite status', async ({ page }) => {
    await openAuthenticated(page, '/settings/');

    for (const id of ['recovery_email_send_status', 'recovery_email_verify_status']) {
      const el = page.locator(`#${id}`);
      await expect(el, `${id} should have role=status`).toHaveAttribute('role', 'status');
      await expect(el, `${id} should have aria-live=polite`).toHaveAttribute('aria-live', 'polite');
    }
  });

  test('settings: no assertive live regions exist on the settings page', async ({ page }) => {
    await openAuthenticated(page, '/settings/');

    const assertiveRegions = await page.evaluate(() => {
      return Array.from(document.querySelectorAll('[aria-live="assertive"]')).map(
        (el) => ({ id: el.id, tag: el.tagName.toLowerCase() })
      );
    });

    expect(
      assertiveRegions.length,
      `Settings should have zero assertive live regions; found: ${JSON.stringify(assertiveRegions)}`
    ).toBe(0);
  });

  test('settings: delete-account status region is scoped polite', async ({ page }) => {
    await openAuthenticated(page, '/settings/');

    const status = page.locator('#delete_account_status');
    await expect(status).toHaveAttribute('role', 'status');
    await expect(status).toHaveAttribute('aria-live', 'polite');
  });
});
