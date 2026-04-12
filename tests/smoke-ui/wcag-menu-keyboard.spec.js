import { test, expect } from '@playwright/test';

test.describe('WCAG menu keyboard navigation', () => {
  test('admin popover supports roving menu keyboard controls when available', async ({ page }) => {
    await page.goto('/settings/');
    await expect(page.locator('body')).toBeVisible();

    const toggle = page.locator('#admin-nav-toggle');
    const popover = page.locator('#admin-nav-popover');

    if ((await toggle.count()) === 0 || (await popover.count()) === 0) {
      test.info().annotations.push({
        type: 'admin-menu-unavailable',
        description: 'Admin popover not present for this account/route; skipping keyboard menu assertions.',
      });
      return;
    }

    await toggle.focus();
    await page.keyboard.press('ArrowDown');

    await expect(toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(popover).toBeVisible();

    const menuItems = popover.locator('[role="menuitem"]');
    const itemCount = await menuItems.count();
    expect(itemCount).toBeGreaterThan(0);

    await expect(menuItems.first()).toBeFocused();

    await page.keyboard.press('End');
    await expect(menuItems.nth(itemCount - 1)).toBeFocused();

    await page.keyboard.press('Home');
    await expect(menuItems.first()).toBeFocused();

    if (itemCount > 1) {
      await page.keyboard.press('ArrowDown');
      await expect(menuItems.nth(1)).toBeFocused();

      await page.keyboard.press('ArrowUp');
      await expect(menuItems.first()).toBeFocused();
    }

    await page.keyboard.press('Escape');
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(toggle).toBeFocused();
  });
});
