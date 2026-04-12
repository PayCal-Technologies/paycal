import { test, expect } from '@playwright/test';

const routeMatrix = [
  { path: '/', requiresAuth: true, label: 'calendar home' },
  { path: '/auth/', requiresAuth: false, label: 'auth' },
  { path: '/settings/', requiresAuth: true, label: 'settings' },
  { path: '/sites/', requiresAuth: true, label: 'sites' },
  { path: '/organizations/', requiresAuth: true, label: 'organizations' },
  { path: '/help/', requiresAuth: true, label: 'help' },
  { path: '/about/', requiresAuth: true, label: 'about' },
  { path: '/transparency/', requiresAuth: true, label: 'transparency' },
  { path: '/transparency/accessibility/', requiresAuth: false, label: 'transparency accessibility' },
  { path: '/policies/', requiresAuth: true, label: 'policies' },
];

async function openRoute(page, route) {
  await page.goto(route.path);
}

test.describe('WCAG heading and landmark regression sweep', () => {
  for (const route of routeMatrix) {
    test(`heading structure: ${route.label} (${route.path})`, async ({ page }) => {
      await openRoute(page, route);
      await expect(page.locator('body')).toBeVisible();

      const main = page.locator('main, [role="main"]').first();
      await expect(main, `Missing main landmark on ${route.path}`).toBeVisible();

      const h1Count = await page.evaluate(() => {
        const isHeadingIncluded = (heading) => {
          if (heading.closest('template, [hidden], [aria-hidden="true"], dialog:not([open])')) {
            return false;
          }

          if (heading.classList.contains('visually_hidden')) {
            return true;
          }

          if (heading.offsetParent === null && getComputedStyle(heading).position !== 'fixed') {
            return false;
          }

          return true;
        };

        return Array.from(document.querySelectorAll('h1')).filter(isHeadingIncluded).length;
      });
      expect(h1Count, `Expected at least one page-level h1 on ${route.path}`).toBeGreaterThan(0);

      const headingData = await main.evaluate((root) => {
        const isHeadingIncluded = (heading) => {
          if (heading.closest('template, [hidden], [aria-hidden="true"], dialog:not([open])')) {
            return false;
          }

          if (heading.classList.contains('visually_hidden')) {
            return true;
          }

          if (heading.offsetParent === null && getComputedStyle(heading).position !== 'fixed') {
            return false;
          }

          return true;
        };

        const headings = Array.from(root.querySelectorAll('h1, h2, h3, h4, h5, h6')).filter(isHeadingIncluded);
        return headings.map((heading) => ({
          level: Number(heading.tagName.slice(1)),
          text: (heading.textContent || '').trim(),
        }));
      });

      expect(headingData.length, `No headings found in main on ${route.path}`).toBeGreaterThan(0);
      expect(headingData[0].level, `First heading in main should be h1 on ${route.path}`).toBe(1);

      for (let i = 1; i < headingData.length; i += 1) {
        const previousLevel = headingData[i - 1].level;
        const currentLevel = headingData[i].level;
        expect(
          currentLevel - previousLevel,
          `Heading level skip detected on ${route.path}: ${headingData[i - 1].text} -> ${headingData[i].text}`
        ).toBeLessThanOrEqual(1);
      }
    });
  }
});
