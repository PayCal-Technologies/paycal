import { test, expect } from '@playwright/test';

const DEV_BASE = process.env.PAYCAL_SMOKE_BASE_URL || 'https://dev.paycal.local';

test.describe('Organizations Members Grid Parity', () => {
  test('members tab supports datagrid search/sort/role-filter with row actions', async ({ browser, page }) => {
    test.setTimeout(120_000);

    const suffix = `${Date.now()}`;
    const ownerEmail = 'owner-local@paycal.local';
    const memberOneEmail = `smoke-member-a-${suffix}@example.com`;
    const memberTwoEmail = `smoke-member-z-${suffix}@example.com`;
    const orgName = `Smoke Members Grid ${suffix}`;

    await page.goto(`${DEV_BASE}/organizations/`, { waitUntil: 'domcontentloaded' });
    await expect(page.getByRole('button', { name: 'Create Organization' })).toBeVisible({ timeout: 20000 });

    await page.getByRole('button', { name: 'Create Organization' }).click({ timeout: 10000 });
    await page.locator('#organizations_create_name').fill(orgName);
    await page.getByRole('button', { name: 'Create', exact: true }).click({ timeout: 10000 });
    await expect(page.getByRole('region', { name: 'Organizations results' })).toContainText(orgName, { timeout: 15000 });

    const ownerSetup = await page.evaluate(async ({ ownerEmail, memberOneEmail, memberTwoEmail, orgName }) => {
      const csrfEl = document.getElementById('organizations_csrf_token');
      const csrf = csrfEl && 'value' in csrfEl ? String(csrfEl.value || '') : '';

      const listRes = await fetch('/api/v1/organizations', {
        method: 'GET',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'include',
      });
      const listPayload = await listRes.json();
      const organizations = Array.isArray(listPayload?.data?.organizations)
        ? listPayload.data.organizations
        : (Array.isArray(listPayload?.organizations) ? listPayload.organizations : []);
      const target = organizations.find((org) => String(org?.name || '') === orgName);
      const orgId = String(target?.organization_id || '');
      if (!listRes.ok || orgId === '') {
        return { ok: false, orgId, reason: 'org-not-found' };
      }

      const sendInvite = async (email) => {
        const res = await fetch(`/api/v1/organizations/${encodeURIComponent(orgId)}/invites/send`, {
          method: 'POST',
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: new URLSearchParams({
            email,
            'scopes[]': 'work.read',
            csrf_token: csrf,
          }),
          credentials: 'include',
        });
        const payload = await res.json();
        return { ok: res.ok && !!payload?.success, token: String(payload?.data?.invite_token || payload?.invite_token || '') };
      };

      const inviteA = await sendInvite(memberOneEmail);
      const inviteB = await sendInvite(memberTwoEmail);

      return { ok: inviteA.ok && inviteB.ok, orgId, inviteAToken: inviteA.token, inviteBToken: inviteB.token, ownerEmail };
    }, { ownerEmail, memberOneEmail, memberTwoEmail, orgName });

    expect(ownerSetup.ok).toBeTruthy();
    expect(ownerSetup.orgId).not.toBe('');
    expect(ownerSetup.inviteAToken).not.toBe('');
    expect(ownerSetup.inviteBToken).not.toBe('');

    const memberContextA = await browser.newContext({ ignoreHTTPSErrors: true });
    const memberPageA = await memberContextA.newPage();
    await memberPageA.goto(`${DEV_BASE}/organizations/`, { waitUntil: 'domcontentloaded' });
    const acceptA = await memberPageA.evaluate(async (inviteToken) => {
      const csrfEl = document.getElementById('organizations_csrf_token');
      const csrf = csrfEl && 'value' in csrfEl ? String(csrfEl.value || '') : '';
      const res = await fetch('/api/v1/organizations/invites/accept', {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams({ invite_token: inviteToken, csrf_token: csrf }),
        credentials: 'include',
      });
      const payload = await res.json();
      return { ok: res.ok && !!payload?.success, payload };
    }, ownerSetup.inviteAToken);
    expect(acceptA.ok).toBeTruthy();

    const memberContextB = await browser.newContext({ ignoreHTTPSErrors: true });
    const memberPageB = await memberContextB.newPage();
    await memberPageB.goto(`${DEV_BASE}/organizations/`, { waitUntil: 'domcontentloaded' });
    const acceptB = await memberPageB.evaluate(async (inviteToken) => {
      const csrfEl = document.getElementById('organizations_csrf_token');
      const csrf = csrfEl && 'value' in csrfEl ? String(csrfEl.value || '') : '';
      const res = await fetch('/api/v1/organizations/invites/accept', {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams({ invite_token: inviteToken, csrf_token: csrf }),
        credentials: 'include',
      });
      const payload = await res.json();
      return { ok: res.ok && !!payload?.success, payload };
    }, ownerSetup.inviteBToken);
    expect(acceptB.ok).toBeTruthy();

    await memberContextA.close();
    await memberContextB.close();

    const roleUpdate = await page.evaluate(async ({ orgId, memberTwoEmail }) => {
      const csrfEl = document.getElementById('organizations_csrf_token');
      const csrf = csrfEl && 'value' in csrfEl ? String(csrfEl.value || '') : '';
      const relRes = await fetch(`/api/v1/organizations/${encodeURIComponent(orgId)}/relationships`, {
        method: 'GET',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'include',
      });
      const relPayload = await relRes.json();
      const members = Array.isArray(relPayload?.data?.members) ? relPayload.data.members : [];
      const target = members.find((m) => String(m?.email || '').toLowerCase() === String(memberTwoEmail).toLowerCase());
      const targetUserUuid = String(target?.user_uuid || target?.uuid || '');
      if (!relRes.ok || targetUserUuid === '') {
        return { ok: false, targetUserUuid };
      }

      const updRes = await fetch(`/api/v1/organizations/${encodeURIComponent(orgId)}/relationships/update-role`, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams({
          target_user_uuid: targetUserUuid,
          role: 'viewer',
          csrf_token: csrf,
        }),
        credentials: 'include',
      });
      const updPayload = await updRes.json();
      return { ok: updRes.ok && !!updPayload?.success };
    }, { orgId: ownerSetup.orgId, memberTwoEmail });

    expect(roleUpdate.ok).toBeTruthy();

    await page.reload({ waitUntil: 'load' });
    await expect(page.getByRole('region', { name: 'Organizations results' })).toContainText(orgName, { timeout: 15000 });
    await page.locator('#organizations-grid .datagrid_row').filter({ hasText: orgName }).first().click({ timeout: 10000 });
    await page.locator('#organizations_tab_members').click({ timeout: 10000 });
    await page.waitForSelector('#organizations_tab_members_panel[data-ready="members-loaded"]', { timeout: 15000 });

    await page.evaluate((orgId) => {
      const orgInput = document.getElementById('organizations_editor_org_id');
      if (orgInput && 'value' in orgInput) {
        orgInput.value = String(orgId || '');
      }
    }, ownerSetup.orgId);

    const roleFilter = page.locator('#organizations_members_role_filter');
    await roleFilter.selectOption('');

    const membersGrid = page.locator('#organizations-members-grid .datagrid');
    await expect(membersGrid).toBeVisible({ timeout: 10000 });
    await expect(page.locator('#organizations-members-grid .datagrid_search')).toBeVisible({ timeout: 10000 });

    await roleFilter.selectOption('viewer');
    await expect(page.locator('#organizations-members-grid')).toContainText(memberTwoEmail, { timeout: 10000 });
    await expect(page.locator('#organizations-members-grid')).not.toContainText(memberOneEmail, { timeout: 10000 });

    await roleFilter.selectOption('');
    const searchInput = page.locator('#organizations-members-grid .datagrid_search');
    await searchInput.fill(memberOneEmail);
    await searchInput.press('Enter');
    await expect(page.locator('#organizations-members-grid')).toContainText(memberOneEmail, { timeout: 10000 });

    await searchInput.fill('');
    await searchInput.press('Enter');
    await expect(page.locator('#organizations-members-grid')).toContainText(memberTwoEmail, { timeout: 10000 });

    const emailSort = page.locator('#organizations-members-grid .datagrid_sort[data-column="email"]');
    await emailSort.click({ timeout: 10000 });
    await expect.poll(async () => page.evaluate(() => {
      const grid = document.getElementById('organizations-members-grid');
      return {
        sort: String(grid?.dataset.sort || ''),
        direction: String(grid?.dataset.direction || ''),
      };
    }), { timeout: 10000 }).toEqual({ sort: 'email', direction: 'asc' });

    await emailSort.click({ timeout: 10000 });
    await expect.poll(async () => page.evaluate(() => {
      const grid = document.getElementById('organizations-members-grid');
      return {
        sort: String(grid?.dataset.sort || ''),
        direction: String(grid?.dataset.direction || ''),
      };
    }), { timeout: 10000 }).toEqual({ sort: 'email', direction: 'desc' });

    await expect(page.locator('#organizations-members-grid .datagrid_action[data-action="change-role"]').first()).toBeVisible({ timeout: 10000 });
    await expect(page.locator('#organizations-members-grid .datagrid_action[data-action="revoke"]').first()).toBeVisible({ timeout: 10000 });
  });
});
