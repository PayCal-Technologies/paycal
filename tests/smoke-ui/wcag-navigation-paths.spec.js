import { test, expect } from '@playwright/test';

const publicRouteMatrix = [
  {
    path: '/help/',
    currentLabel: 'Help Center',
    expectedLinks: [
      { name: 'Home', href: '/' },
      { name: 'Transparency Hub', href: '/transparency/' },
      { name: 'Accessibility Transparency', href: '/transparency/accessibility/' },
    ],
  },
  {
    path: '/transparency/',
    currentLabel: 'Transparency Hub',
    expectedLinks: [
      { name: 'Home', href: '/' },
      { name: 'Read our accessibility standard, recent work, and feedback path', href: '/transparency/accessibility/' },
      { name: 'Read further', href: '/transparency/taxes/' },
    ],
  },
  {
    path: '/transparency/accessibility/',
    currentLabel: 'Accessibility Transparency',
    expectedLinks: [
      { name: 'Transparency', href: '/transparency/' },
    ],
  },
];

test.describe('WCAG multiple navigation paths', () => {
  for (const route of publicRouteMatrix) {
    test(`breadcrumb and alternate links: ${route.path}`, async ({ page }) => {
      await page.goto(route.path);
      await expect(page.locator('body')).toBeVisible();

      const breadcrumb = page.locator('nav.doc-breadcrumb[aria-label="Breadcrumb"]');
      await expect(breadcrumb).toBeVisible();
      await expect(breadcrumb.locator('.current')).toHaveText(route.currentLabel);

      for (const link of route.expectedLinks) {
        const candidate = page.getByRole('link', { name: link.name }).first();
        await expect(candidate).toBeVisible();
        await expect(candidate).toHaveAttribute('href', link.href);
      }
    });
  }

  test('accessibility feedback form routes to contact flow with prefilled contract fields', async ({ page }) => {
    await page.goto('/transparency/accessibility/');
    await expect(page.locator('body')).toBeVisible();

    const form = page.locator('form.a11y-feedback-form');
    await expect(form).toBeVisible();
    await expect(form).toHaveAttribute('method', 'get');
    await expect(form).toHaveAttribute('action', '/contact/');

    await expect(form.locator('input[name="reason"][value="bug"]')).toHaveCount(1);
    await expect(form.locator('input[name="subject"][required]')).toHaveCount(1);
    await expect(form.locator('textarea[name="message"][required]')).toHaveCount(1);
  });
});
