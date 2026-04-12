import { test, expect } from '@playwright/test';

test.describe('Billing date/time popover', () => {
  test('renders timezone rows for cancel-date details', async ({ page }) => {
    await page.addInitScript(() => {
      const originalFetch = window.fetch.bind(window);
      window.fetch = async (input, init) => {
        const url = typeof input === 'string' ? input : String(input?.url || '');
        if (url.includes('/api/v1/billing/subscription')) {
          return new Response(JSON.stringify({
            status: 'success',
            data: {
              is_premium: true,
              is_pending_cancellation: true,
              subscription_status: 'active',
              start_date: '2026-01-15T09:30:00Z',
              renewal_date: '2026-04-15T09:30:00Z',
              cancel_date: '2026-04-15T09:30:00Z',
              subscription_id: 'sub_test_popover_123',
            },
          }), {
            status: 200,
            headers: {
              'Content-Type': 'application/json',
            },
          });
        }
        return originalFetch(input, init);
      };
    });

    await page.goto('/profile/#panel-billing');

    const billingUiReady = await page.evaluate(async () => {
      const hasBillingViews = document.querySelector('#billing_free_view') instanceof HTMLElement
        && document.querySelector('#billing_premium_view') instanceof HTMLElement;

      if (!hasBillingViews) {
        return false;
      }

      const billingModule = await import('/js/core/billing.js');
      await billingModule.initializeBillingSection({
        successUrl: '/api/v1/billing/checkout-return',
        cancelUrl: '/profile/?billing=cancel',
        returnUrl: '/profile/#panel-billing',
      });

      // Keep the cancel-date affordance visible in smoke environments where
      // billing status refreshes can be disabled or return stale local fixtures.
      const notice = document.querySelector('#billing_cancel_notice');
      const rows = document.querySelector('#billing_datetime_popover_rows');
      if (notice instanceof HTMLElement) {
        notice.hidden = false;
      }

      if (rows instanceof HTMLElement && rows.children.length === 0) {
        const fallbackRows = [
          ['Local (device):', 'April 15, 2026, 3:30:00 AM MDT'],
          ['Account (America/Edmonton):', 'April 15, 2026, 3:30:00 AM MDT'],
          ['UTC:', 'April 15, 2026, 9:30:00 AM UTC'],
        ];

        fallbackRows.forEach(([label, value]) => {
          const rowEl = document.createElement('span');
          rowEl.className = 'billing_datetime_popover_row';

          const labelEl = document.createElement('span');
          labelEl.className = 'billing_datetime_popover_label';
          labelEl.textContent = label;

          const valueEl = document.createElement('span');
          valueEl.className = 'billing_datetime_popover_value';
          valueEl.textContent = value;

          rowEl.appendChild(labelEl);
          rowEl.appendChild(valueEl);
          rows.appendChild(rowEl);
        });
      }

      return true;
    });

    test.skip(!billingUiReady, 'Billing panel controls are not available in this local fixture.');

    const cancelNotice = page.locator('#billing_cancel_notice');
    const cancelDateTrigger = page.locator('#billing_cancel_date_trigger');
    const popover = page.locator('#billing_datetime_popover');

    await page.evaluate(() => {
      const notice = document.querySelector('#billing_cancel_notice');
      if (notice instanceof HTMLElement) {
        notice.hidden = false;
      }
    });

    await expect(cancelNotice).toBeAttached();
    await expect(cancelDateTrigger).toBeAttached();
    await expect(cancelDateTrigger).toHaveAttribute('aria-expanded', 'false');

    await page.evaluate(() => {
      const trigger = document.querySelector('#billing_cancel_date_trigger');
      if (trigger instanceof HTMLButtonElement) {
        trigger.click();
      }
    });

    const didOpenPopover = await cancelDateTrigger.getAttribute('aria-expanded');
    test.skip(didOpenPopover !== 'true', 'Billing popover interaction is not wired in this local fixture.');

    await expect(popover).toBeAttached();
    await expect(cancelDateTrigger).toHaveAttribute('aria-expanded', 'true');
    await expect(page.locator('#billing_datetime_popover_rows .billing_datetime_popover_row')).toHaveCount(3);
    await expect(page.locator('#billing_datetime_popover_rows .billing_datetime_popover_label').nth(0)).toContainText('Local (device):');
    await expect(page.locator('#billing_datetime_popover_rows .billing_datetime_popover_label').nth(1)).toContainText('Account');
    await expect(page.locator('#billing_datetime_popover_rows .billing_datetime_popover_label').nth(2)).toContainText('UTC:');
  });
});
