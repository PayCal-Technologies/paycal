import { expect, test } from '@playwright/test';
import { clickIfAvailable, requirePageSentinel } from './support/page-feature-helpers.js';

test.describe('Page feature: calendar', () => {
  test('calendar grid, view modes, picker, and entry dialog stay usable', async ({ page }) => {
    const state = await requirePageSentinel(page, '/', '.datagrid_month_grid[role="grid"]', 'calendar-unavailable');
    if (!state) {
      return;
    }

    await expect(page.locator('#calendar-v2-root, section[aria-labelledby="calendar-landmark-title"]').first()).toBeVisible();
    await expect(page.locator('.datagrid_month_grid[role="grid"]')).toHaveAttribute('aria-colcount', '7');

    await page.locator('button[data-action="next-month"]').click();
    await expect(page.locator('.datagrid_month_grid .datagrid_month_cell').first()).toBeVisible();

    await clickIfAvailable(page.locator('#calendar_view_mode_week'));
    await expect(page.locator('#calendar-v2-root, .datagrid_month_grid, [data-calendar-view]').first()).toBeVisible();

    await clickIfAvailable(page.locator('#calendar_view_mode_month'));
    await clickIfAvailable(page.locator('#calendar_view_mode_pay_period'));
    await clickIfAvailable(page.locator('#calendar_view_mode_month'));

    if (await clickIfAvailable(page.locator('#cal_picker_button'))) {
      await expect(page.locator('#cal_picker_dialog, [role="dialog"]').first()).toBeVisible();
      await page.keyboard.press('Escape');
    }

    const unlockedCell = page.locator('.datagrid_month_grid .datagrid_month_cell:not([data-locked="1"])').first();
    if ((await unlockedCell.count()) > 0) {
      await unlockedCell.click();
      await expect(page.locator('#calendar-modal, [role="dialog"]').first()).toBeVisible();
      await page.keyboard.press('Escape');
    }

    state.health.assertClean();
  });
});
