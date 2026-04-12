import { test, expect } from '@playwright/test';

const DEV_BASE = process.env.PAYCAL_SMOKE_BASE_URL || 'https://dev.paycal.local';

test.describe('Organizations Access Requests', () => {
  test('dev user requests access and owner approves end-to-end', async ({ browser, page }) => {
    test.setTimeout(90_000);

    const suffix = `${Date.now()}`;
    const ownerEmail = `smoke-owner-${suffix}@example.com`;
    const requesterEmail = `smoke-requester-${suffix}@example.com`;
    const orgName = `Smoke Org ${suffix}`;

    const ownerContext = await browser.newContext({ ignoreHTTPSErrors: true });
    const ownerPage = await ownerContext.newPage();
    ownerPage.on('dialog', async (dialog) => {
      await dialog.accept();
    });

    try {
      await ownerPage.goto(`${DEV_BASE}/organizations/`, { waitUntil: 'domcontentloaded' });
      await expect(ownerPage.getByRole('button', { name: 'Create Organization' })).toBeVisible({ timeout: 20000 });

      // Create a personal org so non-admin owner can manage requests in the UI.
      const creationResult = await ownerPage.evaluate(async ({ orgName }) => {
        const csrfEl = document.getElementById('organizations_csrf_token');
        const csrf = csrfEl && 'value' in csrfEl ? String(csrfEl.value || '') : '';
        const response = await fetch('/api/v1/organizations/create', {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: new URLSearchParams({
            name: orgName,
            organization_type: 'personal',
            csrf_token: csrf,
          }),
          credentials: 'include',
        });
        const payload = await response.json();
        return { httpStatus: response.status, payload };
      }, { orgName });

      expect(creationResult.httpStatus).toBeLessThan(300);
      expect(creationResult.payload?.success).toBeTruthy();
      const orgId = String(creationResult.payload?.data?.organization_id || creationResult.payload?.organization_id || '');
      expect(orgId).not.toBe('');

      await page.goto(`${DEV_BASE}/organizations/`, { waitUntil: 'domcontentloaded' });
      await expect(page.getByRole('button', { name: 'Request Access' })).toBeVisible({ timeout: 20000 });
      await page.getByPlaceholder('owner@organization.com').fill(ownerEmail);
      await page.getByRole('button', { name: 'Request Access' }).click();
      await expect(page.locator('#organizations_discovery_panel_status')).toContainText(/request/i, { timeout: 10000 });

      await ownerPage.reload({ waitUntil: 'load' });
      await expect(ownerPage.getByRole('button', { name: 'Create Organization' })).toBeVisible({ timeout: 15000 });

      const approvalResult = await ownerPage.evaluate(async ({ orgId }) => {
        const csrfEl = document.getElementById('organizations_csrf_token');
        const csrf = csrfEl && 'value' in csrfEl ? String(csrfEl.value || '') : '';

        const listResponse = await fetch(`/api/v1/organizations/${encodeURIComponent(orgId)}/access/requests`, {
          method: 'GET',
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'include',
        });
        const listPayload = await listResponse.json();
        const requests = Array.isArray(listPayload?.data?.requests)
          ? listPayload.data.requests
          : (Array.isArray(listPayload?.requests) ? listPayload.requests : []);
        const requestId = String(requests[0]?.request_id || requests[0]?.id || '');

        if (!listResponse.ok || requestId === '') {
          return {
            listOk: listResponse.ok,
            requestId,
            listPayload,
            approveOk: false,
            approvePayload: null,
          };
        }

        const approveResponse = await fetch(`/api/v1/organizations/${encodeURIComponent(orgId)}/access/requests/approve`, {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: new URLSearchParams({
            request_id: requestId,
            csrf_token: csrf,
          }),
          credentials: 'include',
        });
        const approvePayload = await approveResponse.json();

        return {
          listOk: listResponse.ok,
          requestId,
          listPayload,
          approveOk: approveResponse.ok,
          approvePayload,
        };
      }, { orgId });

      expect(approvalResult.listOk).toBeTruthy();
      expect(approvalResult.requestId).not.toBe('');
      expect(approvalResult.approveOk).toBeTruthy();
      expect(approvalResult.approvePayload?.success).toBeTruthy();
    } finally {
      await ownerContext.close();
    }
  });
});
