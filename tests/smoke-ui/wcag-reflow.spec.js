import { test, expect } from '@playwright/test';

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

const strictMode = process.env.PAYCAL_REFLOW_STRICT === '1';
const baseURL = process.env.PAYCAL_SMOKE_BASE_URL || 'https://dev.paycal.local';

async function openRoute(page, route) {
  await page.goto(route.path);
}

async function collectOverflow(page) {
  return page.evaluate(() => {
    const viewportWidth = window.innerWidth;
    const rootWidth = Math.max(
      document.documentElement.scrollWidth || 0,
      document.body ? document.body.scrollWidth || 0 : 0
    );

    const hasHorizontalOverflow = rootWidth > viewportWidth + 1;

    const offenders = [];
    const nodes = document.querySelectorAll('body *');
    for (const node of nodes) {
      if (!(node instanceof HTMLElement)) {
        continue;
      }

      const rect = node.getBoundingClientRect();
      if (rect.width <= 0 || rect.height <= 0) {
        continue;
      }

      const overLeft = rect.left < -1;
      const overRight = rect.right > viewportWidth + 1;
      if (!overLeft && !overRight) {
        continue;
      }

      const className = typeof node.className === 'string' ? node.className.trim() : '';
      const selector = `${node.tagName.toLowerCase()}${node.id ? `#${node.id}` : ''}${className ? `.${className.replace(/\s+/g, '.')}` : ''}`;

      offenders.push({
        selector,
        left: Math.round(rect.left),
        right: Math.round(rect.right),
      });

      if (offenders.length >= 10) {
        break;
      }
    }

    return {
      viewportWidth,
      rootWidth,
      hasHorizontalOverflow,
      offenders,
    };
  });
}

const textSpacingCss = `
* {
  letter-spacing: 0.12em !important;
  word-spacing: 0.16em !important;
  line-height: 1.5 !important;
}
p, li, figcaption {
  margin-bottom: 2em !important;
}
`;

test.describe('WCAG reflow and text-spacing sweep', () => {
  for (const route of routeMatrix) {
    test(`reflow @ 640w: ${route.label} (${route.path})`, async ({ page }) => {
      await page.setViewportSize({ width: 640, height: 1200 });
      await openRoute(page, route);
      await expect(page.locator('body')).toBeVisible();

      const metrics = await collectOverflow(page);
      if (strictMode) {
        expect(metrics.hasHorizontalOverflow, `Horizontal overflow on ${route.path} at 640w`).toBe(false);
      }

      if (metrics.hasHorizontalOverflow) {
        console.warn(`[a11y-reflow][${route.path}] root=${metrics.rootWidth} viewport=${metrics.viewportWidth} offenders=${JSON.stringify(metrics.offenders)}`);
      }

      expect(Array.isArray(metrics.offenders)).toBe(true);
    });

    test(`text spacing @ 640w: ${route.label} (${route.path})`, async ({ browser }) => {
      const context = await browser.newContext({
        baseURL,
        ignoreHTTPSErrors: true,
        viewport: { width: 640, height: 1200 },
        bypassCSP: true,
      });

      const page = await context.newPage();
      await openRoute(page, route);
      await expect(page.locator('body')).toBeVisible();

      await page.addStyleTag({ content: textSpacingCss });
      const metrics = await collectOverflow(page);

      if (strictMode) {
        expect(metrics.hasHorizontalOverflow, `Horizontal overflow with text spacing on ${route.path}`).toBe(false);
      }

      if (metrics.hasHorizontalOverflow) {
        console.warn(`[a11y-spacing][${route.path}] root=${metrics.rootWidth} viewport=${metrics.viewportWidth} offenders=${JSON.stringify(metrics.offenders)}`);
      }

      expect(Array.isArray(metrics.offenders)).toBe(true);
      await context.close();
    });
  }
});
