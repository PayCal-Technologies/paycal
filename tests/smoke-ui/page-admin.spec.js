import { expect, test } from '@playwright/test';
import { clickIfAvailable, expectAnyVisible, requirePageSentinel } from './support/page-feature-helpers.js';

const adminPages = [
  { path: '/admin/', label: 'admin dashboard', sentinel: '#admin-nav-toggle, main', selectors: ['#admin-nav-toggle', '#admin-nav-popover', 'main a[href^="/admin/"]'] },
  { path: '/admin/ast/', label: 'admin AST', sentinel: '#ast_graph_canvas, #ast_generate_btn', selectors: ['#ast_search', '#ast_generate_btn', '#ast_graph_canvas', '#ast_graph_status'] },
  { path: '/admin/redis/', label: 'admin Redis', sentinel: '#redis-refresh, .redis-admin', selectors: ['#redis-refresh', '#redis-control-reason', '#redis-freeze-on', '#redis-breaker-open'] },
  { path: '/admin/metrics/', label: 'admin metrics', sentinel: '#refresh-metrics-btn, .metrics-dashboard, main', selectors: ['#refresh-metrics-btn', '.metrics-dashboard', 'main table'] },
  { path: '/admin/stripe/', label: 'admin Stripe', sentinel: '.metrics-dashboard, main', selectors: ['.metrics-dashboard', 'a[href="/admin/"]', 'a[href="/api/v1/billing/telemetry"]'] },
  { path: '/admin/feedback/', label: 'admin feedback', sentinel: '.admin-feedback-page, main', selectors: ['.admin-feedback-filters', '.admin-feedback-table', 'button:has-text("Save")'] },
  { path: '/admin/business-moderation/', label: 'admin business moderation', sentinel: '#business-moderation-feedback, main', selectors: ['#business-moderation-feedback', 'main form', 'main table'] },
  { path: '/admin/user-roles/', label: 'admin user roles', sentinel: '#lookup-email, main', selectors: ['#lookup-email', 'main form', 'main button'] },
  { path: '/admin/language-dashboard/', label: 'admin language dashboard', sentinel: '#lang-dash-root, main', selectors: ['#lang-dash-root', '#lang-dash-stats', '#lang-dash-table-body'] },
  { path: '/admin/language-editor/', label: 'admin language editor', sentinel: 'main', selectors: ['main form', 'main button', 'main textarea'] },
  { path: '/admin/languages/', label: 'admin languages', sentinel: 'main', selectors: ['main form', 'main button', 'main table'] },
  { path: '/admin/goldmaster/', label: 'admin goldmaster', sentinel: '#goldmaster_admin_title, main', selectors: ['#goldmaster_admin_title', '[data-dialog-open="goldmaster_dialog"]', '#goldmaster_dialog'] },
  { path: '/admin/documentation/', label: 'admin documentation', sentinel: '.documentation-sidebar, main', selectors: ['.documentation-sidebar', 'main a', 'main h1'] },
  { path: '/admin/release-ledger/', label: 'admin release ledger', sentinel: '#release_ledger_title, main', selectors: ['#release_ledger_title', '.ledger-summary-grid', '.ledger-table'] },
];

test.describe.configure({ mode: 'parallel' });

for (const adminPage of adminPages) {
  test.describe(`Page feature: ${adminPage.label}`, () => {
    test('renders admin controls and safe interactions', async ({ page }) => {
      const state = await requirePageSentinel(page, adminPage.path, adminPage.sentinel, 'admin-page-unavailable');
      if (!state) {
        return;
      }

      await expect(page.locator('main').first()).toBeVisible();
      await expectAnyVisible(page, adminPage.selectors, `${adminPage.label} key controls`);

      if (await clickIfAvailable(page.locator('#admin-nav-toggle'))) {
        await expect(page.locator('#admin-nav-popover')).toBeVisible();
        await page.keyboard.press('Escape');
      }
      if (await clickIfAvailable(page.locator('[data-dialog-open="goldmaster_dialog"]'))) {
        await expect(page.locator('#goldmaster_dialog')).toBeVisible();
        await page.keyboard.press('Escape');
      }
      if (await clickIfAvailable(page.locator('#ast_view_3d_btn'))) {
        await expect(page.locator('#ast_graph_canvas')).toBeVisible();
      }
      if (await clickIfAvailable(page.locator('#ast_view_2d_btn'))) {
        await expect(page.locator('#ast_graph_canvas')).toBeVisible();
      }

      state.health.assertClean();
    });
  });
}
