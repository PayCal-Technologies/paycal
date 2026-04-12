import { test, expect } from '@playwright/test';

const initializeBillingUi = async (page) => {
  await page.evaluate(async () => {
    const billingModule = await import('/js/core/billing.js');
    await billingModule.initializeBillingSection({
      successUrl: '/api/v1/billing/checkout-return',
      cancelUrl: '/profile/?billing=cancel',
      returnUrl: '/profile/#panel-billing',
    });
  });
};

test.describe('Stripe billing E2E smoke', () => {
  test('free user can start checkout redirect flow', async ({ page }) => {
    await page.route('**/api/v1/billing/subscription*', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'success',
          data: {
            is_premium: false,
            is_pending_cancellation: false,
            subscription_status: 'free',
            start_date: '',
            renewal_date: '',
            cancel_date: '',
            subscription_id: '',
          },
        }),
      });
    });

    await page.route('**/api/v1/billing/checkout-session*', async (route) => {
      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'success',
          data: {
            checkout_url: 'https://dev.paycal.local/profile/?stripe_test_checkout=1#panel-billing',
            session_id: 'cs_test_mock_001',
          },
        }),
      });
    });

    await page.goto('/profile/#panel-billing');
    await initializeBillingUi(page);

    const upgradeBtn = page.locator('#billing_upgrade_btn');
    await expect(upgradeBtn).toBeAttached();
    await page.evaluate(() => {
      document.querySelector('#billing_free_view')?.removeAttribute('hidden');
    });

    const checkoutRequestPromise = page.waitForRequest(
      (request) => request.method() === 'POST' && request.url().includes('/api/v1/billing/checkout-session')
    );

    await page.evaluate(() => {
      const buttons = Array.from(document.querySelectorAll('#billing_upgrade_btn'));
      for (const button of buttons) {
        if (button instanceof HTMLButtonElement) {
          button.click();
        }
      }
    });

    const checkoutRequest = await checkoutRequestPromise;
    const checkoutPayload = JSON.parse(checkoutRequest.postData() || '{}');

    await expect(page).toHaveURL(/stripe_test_checkout=1/);

    expect(typeof checkoutPayload.success_url).toBe('string');
    expect(typeof checkoutPayload.cancel_url).toBe('string');
    expect(checkoutPayload.success_url).toContain('/api/v1/billing/checkout-return');
    expect(checkoutPayload.cancel_url).toContain('/profile/');
  });

  test('premium user can start billing portal redirect flow', async ({ page }) => {
    await page.route('**/api/v1/billing/subscription*', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'success',
          data: {
            is_premium: true,
            is_pending_cancellation: false,
            subscription_status: 'active',
            start_date: '2026-03-01T00:00:00Z',
            renewal_date: '2026-04-01T00:00:00Z',
            cancel_date: '',
            subscription_id: 'sub_mock_active',
          },
        }),
      });
    });

    await page.route('**/api/v1/billing/portal-session*', async (route) => {
      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'success',
          data: {
            portal_url: 'https://dev.paycal.local/profile/?stripe_test_portal=1#panel-billing',
          },
        }),
      });
    });

    await page.goto('/profile/#panel-billing');
    await initializeBillingUi(page);

    const portalBtn = page.locator('#billing_portal_btn');
    await expect(portalBtn).toBeAttached();
    await page.evaluate(() => {
      document.querySelector('#billing_premium_view')?.removeAttribute('hidden');
    });

    const portalRequestPromise = page.waitForRequest(
      (request) => request.method() === 'POST' && request.url().includes('/api/v1/billing/portal-session')
    );

    await page.evaluate(() => {
      const buttons = Array.from(document.querySelectorAll('#billing_portal_btn'));
      for (const button of buttons) {
        if (button instanceof HTMLButtonElement) {
          button.click();
        }
      }
    });

    const portalRequest = await portalRequestPromise;
    const portalPayload = JSON.parse(portalRequest.postData() || '{}');

    await expect(page).toHaveURL(/stripe_test_portal=1/);

    expect(typeof portalPayload.return_url).toBe('string');
    expect(portalPayload.return_url).toContain('/profile/');
  });

  test('downgrade confirmation requires phrase and refreshes to free state', async ({ page }) => {
    await page.addInitScript(() => {
      const originalFetch = window.fetch.bind(window);
      window.__stripeDowngradePayload = null;
      window.__stripeDowngradeApplied = false;

      window.fetch = async (input, init) => {
        const url = typeof input === 'string' ? input : String(input?.url || '');

        if (url.includes('/api/v1/billing/subscription')) {
          const data = window.__stripeDowngradeApplied
            ? {
                is_premium: false,
                is_pending_cancellation: false,
                subscription_status: 'free',
                start_date: '',
                renewal_date: '',
                cancel_date: '',
                subscription_id: '',
              }
            : {
                is_premium: true,
                is_pending_cancellation: false,
                subscription_status: 'active',
                start_date: '2026-03-01T00:00:00Z',
                renewal_date: '2026-04-01T00:00:00Z',
                cancel_date: '',
                subscription_id: 'sub_mock_active',
              };

          return new Response(JSON.stringify({ status: 'success', data }), {
            status: 200,
            headers: { 'Content-Type': 'application/json' },
          });
        }

        if (url.includes('/api/v1/billing/cancel-subscription')) {
          let payload = {};
          try {
            payload = init?.body ? JSON.parse(String(init.body)) : {};
          } catch {
            payload = {};
          }

          window.__stripeDowngradePayload = payload;
          window.__stripeDowngradeApplied = true;

          return new Response(JSON.stringify({
            status: 'success',
            data: {
              subscription_id: 'sub_mock_active',
              stripe_status: 'canceled',
            },
          }), {
            status: 200,
            headers: { 'Content-Type': 'application/json' },
          });
        }

        return originalFetch(input, init);
      };
    });

    await page.goto('/profile/#panel-billing');
    await initializeBillingUi(page);

    const phraseInput = page.locator('#billing_downgrade_phrase');
    const confirmBtn = page.locator('#billing_downgrade_confirm');
    const statusMsg = page.locator('#billing_downgrade_status');

    await expect(phraseInput).toBeVisible();
    await expect(confirmBtn).toBeDisabled();

    await phraseInput.fill('DOWNGRADE ME');
    await expect(confirmBtn).toBeEnabled();

    await confirmBtn.click();

    await expect(statusMsg).toContainText('now on Free');
    await expect.poll(async () => {
      const hiddenState = await page.locator('#billing_free_view').getAttribute('hidden');
      return hiddenState === null;
    }).toBe(true);
    await expect.poll(async () => {
      const hiddenState = await page.locator('#billing_premium_view').getAttribute('hidden');
      return hiddenState !== null;
    }).toBe(true);

    const downgradePayload = await page.evaluate(() => window.__stripeDowngradePayload);
    expect(downgradePayload).toBeTruthy();
    expect(downgradePayload.confirm_phrase).toBe('DOWNGRADE ME');
  });
});
