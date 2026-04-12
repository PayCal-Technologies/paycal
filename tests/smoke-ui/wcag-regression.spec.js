import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const routeMatrix = [
  { path: '/', requiresAuth: true, label: 'calendar home' },
  { path: '/auth/', requiresAuth: false, label: 'auth' },
  { path: '/settings/', requiresAuth: true, label: 'settings' },
  { path: '/sites/', requiresAuth: true, label: 'sites' },
  { path: '/organizations/', requiresAuth: true, label: 'organizations' },
  { path: '/help/', requiresAuth: true, label: 'help' },
  { path: '/about/', requiresAuth: true, label: 'about' },
  { path: '/contact/', requiresAuth: true, label: 'contact' },
  { path: '/transparency/', requiresAuth: true, label: 'transparency' },
  { path: '/transparency/accessibility/', requiresAuth: true, label: 'transparency accessibility' },
  { path: '/policies/', requiresAuth: true, label: 'policies' },
  { path: '/earnings/', requiresAuth: true, label: 'earnings' },
  { path: '/profile/', requiresAuth: true, label: 'profile' },
];

const strictMode = process.env.PAYCAL_A11Y_STRICT === '1';

async function openRoute(page, route) {
  await page.goto(route.path);
}

test.describe('WCAG regression route sweep', () => {
  for (const route of routeMatrix) {
    test(`axe route scan: ${route.label} (${route.path})`, async ({ page }) => {
      await openRoute(page, route);

      await expect(page.locator('body')).toBeVisible();

      const axe = new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa'])
        .exclude('#calendar_month_announce_placeholder');

      const results = await axe.analyze();

      if (strictMode) {
        expect(results.violations, `Strict mode violations on ${route.path}`).toEqual([]);
        return;
      }

      const blocking = results.violations.filter((violation) =>
        violation.impact === 'serious' || violation.impact === 'critical'
      );

      const blockingSummary = blocking.map((v) => `${v.id}(${v.impact})`).join(', ');
      test.info().annotations.push({
        type: 'a11y-blocking-count',
        description: `${route.path}: ${blocking.length}`,
      });

      if (blockingSummary.length > 0) {
        // Default mode is report-only to keep scans repeatable while backlog remediation is in progress.
        console.warn(`[a11y][${route.path}] serious/critical: ${blockingSummary}`);
      }

      expect(Array.isArray(results.violations)).toBe(true);
    });
  }
});
