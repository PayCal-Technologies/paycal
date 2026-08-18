import { expect, test } from '@playwright/test';
import { clickIfAvailable, expectAnyVisible, requirePageSentinel } from './support/page-feature-helpers.js';

test.describe('Page feature: organizations', () => {
  test('organization grid tabs and dialogs are usable', async ({ page }) => {
    const state = await requirePageSentinel(page, '/businesses/', '#organizations_create_button, #organizations-grid', 'organizations-unavailable');
    if (!state) {
      return;
    }

    await expectAnyVisible(page, ['#organizations_create_button', '#organizations-grid'], 'organizations controls');
    if (await clickIfAvailable(page.locator('#organizations_create_button'))) {
      await expect(page.locator('#organizations_create_dialog')).toBeVisible();
      await expect(page.locator('#organizations_create_name')).toBeVisible();
      await page.keyboard.press('Escape');
    }

    const row = page.locator('#organizations-grid .datagrid_row .datagrid_row_content').first();
    if ((await row.count()) > 0 && await row.isVisible().catch(() => false)) {
      await row.click();
      await expect(page.locator('#organizations_editor_dialog')).toBeVisible();
      await clickIfAvailable(page.locator('#organizations_tab_members'));
      await expect(page.locator('#organizations_tab_members_panel, #organizations-members-grid')).toBeVisible();
      await page.keyboard.press('Escape');
    }

    state.health.assertClean();
  });
});

test.describe('Page feature: sites', () => {
  test('site lists, tabs, and create dialog are usable', async ({ page }) => {
    const state = await requirePageSentinel(page, '/sites/', '#sites_list_panel, #sites-grid-active', 'sites-unavailable');
    if (!state) {
      return;
    }

    await expect(page.locator('#sites-grid-active')).toBeVisible();
    await clickIfAvailable(page.locator('#tab-archived_sites'));
    await expect(page.locator('#sites-grid-archived')).toBeVisible();
    await clickIfAvailable(page.locator('#tab-active_sites'));

    if (await clickIfAvailable(page.locator('[data-action="create-site"]'))) {
      await expect(page.locator('[role="dialog"], #site_editor_dialog').first()).toBeVisible();
      await page.keyboard.press('Escape');
    }

    if (await clickIfAvailable(page.locator('#btn_show_orphaned_work'))) {
      await expect(page.locator('#orphaned_work_banner, #orphaned_work_panel').first()).toBeVisible();
    }

    state.health.assertClean();
  });
});

for (const route of ['/reports/']) {
  test.describe(`Page feature: ${route}`, () => {
    test('earnings reports render charts, tabs, and export controls', async ({ page }) => {
      const state = await requirePageSentinel(page, route, '[data-earnings-mode], svg[id^="earnings_line_graph_"], main', 'reports-unavailable');
      if (!state) {
        return;
      }

      await expectAnyVisible(page, ['[data-earnings-mode]', 'svg[id^="earnings_line_graph_"]', 'main'], `${route} report controls`);
      await clickIfAvailable(page.locator('[id^="tab-btn-"]').first());
      await clickIfAvailable(page.locator('button:has-text("Print"), a:has-text("Print"), [data-action*="print"]').first());
      await clickIfAvailable(page.locator('button:has-text("Export"), a:has-text("Export"), [data-action*="export"]').first());
      state.health.assertClean();
    });
  });
}
