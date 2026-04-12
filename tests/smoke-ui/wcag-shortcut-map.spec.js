import { test, expect } from '@playwright/test';

test.describe('WCAG shortcut map and metadata contract', () => {
  test('declared navigation shortcut links expose matching metadata', async ({ page }) => {
    await page.goto('/settings/');
    await expect(page.locator('body')).toBeVisible();

    const navLinks = page.locator('[data-nav-shortcut]');
    const count = await navLinks.count();
    expect(count).toBeGreaterThan(0);

    for (let i = 0; i < count; i += 1) {
      const navLink = navLinks.nth(i);
      const key = await navLink.getAttribute('data-nav-shortcut');
      expect(key).toBeTruthy();
      await expect(navLink).toHaveAttribute('aria-keyshortcuts', key);
    }

    const settingsShortcut = page.locator('[data-nav-shortcut="e"]').first();
    await expect(settingsShortcut).toBeVisible();
  });

  test('keyboard shortcuts help modal includes safeguard messaging', async ({ page }) => {
    await page.goto('/settings/');
    await expect(page.locator('body')).toBeVisible();

    await page.keyboard.press('Control+k');

    const modal = page.locator('#modal_help');
    await expect(modal).toHaveAttribute('open', '');
    await expect(modal).toContainText('Shortcut Safeguards');
    await expect(modal).toContainText('Single-key page shortcuts are suppressed while typing in inputs and while dialogs are open.');
  });
});
