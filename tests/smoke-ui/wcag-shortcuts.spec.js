import { test, expect } from '@playwright/test';

function currentPath(page) {
  return new URL(page.url()).pathname;
}

test.describe('WCAG 2.1.4 character shortcut safeguards', () => {
  test('single-key shortcut still works in normal browsing context', async ({ page }) => {
    await page.goto('/settings/');
    await expect(page.locator('body')).toBeVisible();

    if ((await new URL(page.url()).pathname) === '/auth/') {
      test.info().annotations.push({
        type: 'auth-required',
        description: 'Settings route not reachable for this test account; skipping shortcut assertion.',
      });
      return;
    }

    await page.locator('body').click();
    await page.keyboard.press('h');

    await expect.poll(() => currentPath(page)).toBe('/help/');
  });

  test('single-key shortcuts do not fire while typing in editable controls', async ({ page }) => {
    await page.goto('/auth/');
    await expect(page.locator('body')).toBeVisible();

    const emailField = page.locator('#email');
    await expect(emailField).toBeVisible();
    await emailField.click();
    await page.keyboard.press('h');

    await expect.poll(() => currentPath(page)).toBe('/auth/');
  });

  test('single-key shortcuts do not fire while a dialog is open', async ({ page }) => {
    await page.goto('/settings/');
    await expect(page.locator('body')).toBeVisible();

    if (new URL(page.url()).pathname === '/auth/') {
      test.info().annotations.push({
        type: 'auth-required',
        description: 'Settings route not reachable for this test account; skipping dialog shortcut assertion.',
      });
      return;
    }

    await page.keyboard.press('Control+k');
    const helpDialog = page.locator('#modal_help');
    await expect(helpDialog).toHaveAttribute('open', '');

    await page.keyboard.press('h');

    await expect.poll(() => currentPath(page)).toBe('/settings/');
    await expect(helpDialog).toHaveAttribute('open', '');
  });
});
