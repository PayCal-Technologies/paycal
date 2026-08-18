import { expect, test } from '@playwright/test';
import { clickIfAvailable, expectAnyVisible, requirePageSentinel } from './support/page-feature-helpers.js';

const businessPages = [
  { path: '/business/', label: 'business dashboard', subpage: 'dashboard', selectors: ['#business-workspace', 'main nav', 'main a'] },
  { path: '/business/details/', label: 'business details', subpage: 'details', selectors: ['#businesses_editor_business_id', 'main form', 'main input'] },
  { path: '/business/members/', label: 'business members', subpage: 'members', selectors: ['#businesses-members-grid', '#business_members_report_toggle', '#business_members_info_dialog'] },
  { path: '/business/groups/', label: 'business groups', subpage: null, selectors: ['#business-groups-grid', '[data-action="create-business-group"]', '[data-business-groups-status]'] },
  { path: '/business/sites/', label: 'business sites', subpage: 'sites', selectors: ['#business-workspace', '[data-action="create-site"]', '.datagrid'] },
  { path: '/business/payroll/', label: 'business payroll', subpage: 'payroll', selectors: ['#businesses_editor_default_wage', '#businesses_payroll_save', '#businesses_payroll_status'] },
  { path: '/business/audit/', label: 'business audit', subpage: 'audit', selectors: ['#business-workspace', '.datagrid', 'main form'] },
  { path: '/business/reports/', label: 'business reports', subpage: null, selectors: ['#business_reports_sr_status', '[data-report-tab-button]', '[data-report-export-open]'] },
  { path: '/business/governance/', label: 'business governance', subpage: null, selectors: ['#business-workspace', '#businesses_definitions_help_button', '#businesses_definitions_dialog'] },
  { path: '/business/compliance/', label: 'business compliance', subpage: null, selectors: ['#business-workspace', 'main form', 'main button'] },
];

test.describe.configure({ mode: 'parallel' });

for (const businessPage of businessPages) {
  test.describe(`Page feature: ${businessPage.label}`, () => {
    test('renders workspace controls and safe drawers/dialogs', async ({ page }) => {
      const state = await requirePageSentinel(page, businessPage.path, '#business-workspace, main', 'business-page-unavailable');
      if (!state) {
        return;
      }

      if (businessPage.subpage) {
        await expect(page.locator(`#business-workspace[data-business-subpage="${businessPage.subpage}"], #business-workspace`).first()).toBeVisible();
      }
      await expectAnyVisible(page, businessPage.selectors, `${businessPage.label} key controls`);

      if (await clickIfAvailable(page.locator('[data-action="create-business-group"]'))) {
        await expect(page.locator('#modal_business_group, [role="dialog"]').first()).toBeVisible();
        await page.keyboard.press('Escape');
      }
      if (await clickIfAvailable(page.locator('#business_members_report_toggle'))) {
        await expect(page.locator('#business_members_report_panel')).toBeVisible();
      }
      if (await clickIfAvailable(page.locator('#businesses_definitions_help_button'))) {
        await expect(page.locator('#businesses_definitions_dialog, [role="dialog"]').first()).toBeVisible();
        await page.keyboard.press('Escape');
      }
      if (await clickIfAvailable(page.locator('[data-report-customize-open]'))) {
        await expect(page.locator('#business_reports_customize_drawer, [role="dialog"]').first()).toBeVisible();
        await page.keyboard.press('Escape');
      }
      if (await clickIfAvailable(page.locator('[data-report-export-open]'))) {
        await expect(page.locator('#business_reports_export_drawer, [role="dialog"]').first()).toBeVisible();
        await page.keyboard.press('Escape');
      }

      state.health.assertClean();
    });
  });
}
