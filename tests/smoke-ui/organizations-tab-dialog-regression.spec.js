import { test, expect } from '@playwright/test';

const DEV_BASE = process.env.PAYCAL_SMOKE_BASE_URL || 'https://dev.paycal.local';

test.describe('Organizations Tab Dialog Regression', () => {
  test('clicking inactive tab does not close organization editor dialog', async ({ page }) => {
    test.setTimeout(90_000);

    const suffix = `${Date.now()}`;
    const orgName = `Smoke Tab Org ${suffix}`;

    await page.goto(`${DEV_BASE}/organizations/`, { waitUntil: 'domcontentloaded' });
    await expect(page.getByRole('button', { name: 'Create Organization' })).toBeVisible({ timeout: 20000 });

    await page.getByRole('button', { name: 'Create Organization' }).click({ timeout: 10000 });
    await page.locator('#organizations_create_name').fill(orgName);
    await page.getByRole('button', { name: 'Create', exact: true }).click({ timeout: 10000 });

    const dialog = page.locator('#organizations_editor_dialog[open]');
    await expect(dialog).toBeVisible({ timeout: 10000 });

    const membersTab = page.locator('#organizations_tab_members');
    await expect(membersTab).toBeVisible({ timeout: 10000 });
    await membersTab.click({ timeout: 10000 });

    await expect(dialog).toBeVisible({ timeout: 10000 });
    await expect(page.locator('#organizations_tab_members_panel.is-visible')).toBeVisible({ timeout: 10000 });

    const detailsTab = page.locator('#organizations_tab_details');
    await detailsTab.click({ timeout: 10000 });
    await expect(dialog).toBeVisible({ timeout: 10000 });
    await expect(page.locator('#organizations_tab_details_panel.is-visible')).toBeVisible({ timeout: 10000 });
  });
});
