import { expect, test } from '@playwright/test';
import { clickIfAvailable, expectAnyVisible, openAppPage } from './support/page-feature-helpers.js';

const publicPages = [
  { path: '/auth/', label: 'sign in', selectors: ['#signin-form', '#signin-passkey', '#email'] },
  { path: '/auth/signup/', label: 'sign up', selectors: ['form[action^="/auth/signup/"]', 'input[type="email"]', 'button[type="submit"]'] },
  { path: '/auth/recover/', label: 'account recovery', selectors: ['main form', 'input[type="email"]', 'button[type="submit"]'] },
  { path: '/help/', label: 'help', selectors: ['main h1', 'main a'] },
  { path: '/pricing/', label: 'pricing', selectors: ['main h1', 'main a', 'main button'] },
  { path: '/status/', label: 'status', selectors: ['main h1', 'main [role="status"]', 'main table'] },
  { path: '/contact/', label: 'contact', selectors: ['main h1', 'main form', 'main a[href^="mailto:"]'] },
  { path: '/about/', label: 'about', selectors: ['main h1', 'main a'] },
  { path: '/security/', label: 'security', selectors: ['main h1', 'main a'] },
  { path: '/transparency/', label: 'transparency', selectors: ['main h1', 'main a'] },
];

test.describe.configure({ mode: 'parallel' });

for (const publicPage of publicPages) {
  test.describe(`Page feature: public ${publicPage.label}`, () => {
    test('renders public controls without frontend errors', async ({ page }) => {
      const state = await openAppPage(page, publicPage.path);
      const status = state.response?.status() || 0;
      if (status === 404) {
        test.info().annotations.push({
          type: 'public-page-unavailable',
          description: `${publicPage.path} returned HTTP 404 on this install.`,
        });
        return;
      }

      await expect(page.locator('main, body').first()).toBeVisible();
      await expectAnyVisible(page, publicPage.selectors, `${publicPage.label} key controls`);
      await clickIfAvailable(page.locator('button[aria-expanded], [data-dialog-open], details summary').first());
      state.health.assertClean();
    });
  });
}
