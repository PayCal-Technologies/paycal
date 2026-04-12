import { test, expect } from '@playwright/test';

test.describe('PayCal optional UI smoke', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/organizations/');
    await expect(page).toHaveURL(/\/organizations\/?$/);
  });

  test('organizations dialog updates accessibility live regions', async ({ page }) => {
    // Ensure at least one org row exists — create one via the dialog if the grid is empty.
    // After creation refreshIndex auto-opens the editor (reopenDialog=true), so close it first.
    const existingRow = page.locator('#organizations-grid .datagrid_row .datagrid_row_content').first();
    const hasRow = await existingRow.isVisible({ timeout: 3000 }).catch(() => false);

    if (!hasRow) {
      await page.locator('#organizations_create_button').click();
      await expect(page.locator('#organizations_create_dialog')).toBeVisible();
      await page.locator('#organizations_create_name').fill('Smoke Test Org');
      await page.locator('#organizations_create_submit').click();
      await expect(page.locator('#organizations_create_dialog')).not.toBeVisible({ timeout: 10000 });
      // Editor auto-opens after creation — close it so we can test row-click path cleanly
      await expect(page.locator('#organizations_editor_dialog')).toBeVisible({ timeout: 10000 });
      await page.locator('#organizations_editor_close_x').click();
      await expect(page.locator('#organizations_editor_dialog')).not.toBeVisible({ timeout: 5000 });
      await page.locator('#organizations-grid .datagrid_row .datagrid_row_content').first().waitFor({ timeout: 10000 });
    }

    await page.locator('#organizations-grid .datagrid_row .datagrid_row_content').first().click();
    await expect(page.locator('#organizations_editor_dialog')).toBeVisible();

    await expect(page.locator('#organizations_scope_sr_status')).toHaveText(/No invite scopes selected\.|Invite scopes/);
    await expect(page.locator('#organizations_invites_sr_status')).not.toHaveText('');
    await expect(page.locator('#organizations_discovery_sr_status')).not.toHaveText('');
    await expect(page.locator('#organizations_audit_sr_status')).not.toHaveText('');

    // Scope checkboxes may be disabled for non-admin/non-premium users (admin gate during rollout).
    // Verify the SR infrastructure is present; only test the check interaction if enabled.
    await expect(page.locator('#organizations_scope_sr_status')).toBeAttached();
    const firstScope = page.locator('#organizations_scope_grid .organizations_scope').first();
    const scopeEnabled = await firstScope.isEnabled({ timeout: 2000 }).catch(() => false);
    if (scopeEnabled) {
      await firstScope.check();
      await expect(page.locator('#organizations_scope_sr_status')).toHaveText(/scope selected/i);
    }
  });

  test('settings passkey section exposes SR guidance and status', async ({ page }) => {
    await page.goto('/settings/');

    await expect(page.locator('#passkey_credentials_sr_instructions')).toBeAttached();
    await expect(page.locator('#passkey_credentials_sr_status')).toContainText(/Passkeys list loaded|Unable to load passkeys/);

    const grid = page.locator('#passkey_credentials_list [role="grid"]');
    if (await grid.count()) {
      await expect(grid.first()).toHaveAttribute('aria-describedby', /passkey_credentials_sr_instructions/);
      await expect(grid.first()).toHaveAttribute('aria-rowcount', /\d+/);
      await expect(grid.first()).toHaveAttribute('aria-colcount', /\d+/);
    }
  });

  test('calendar month grid uses selected/current/disabled semantics and navigates', async ({ page }) => {
    await page.goto('/');

    const monthGrid = page.locator('.datagrid_month_grid[role="grid"]');
    await expect(monthGrid).toBeVisible();
    await expect(monthGrid).toHaveAttribute('aria-colcount', '7');

    const firstCell = page.locator('.datagrid_month_grid .datagrid_month_cell').first();
    await expect(firstCell).toBeVisible();

    await expect(page.locator('.datagrid_month_grid [role="gridcell"]')).toHaveCount(0);
    await expect(page.locator('.datagrid_month_grid [aria-rowindex], .datagrid_month_grid [aria-colindex]')).toHaveCount(0);
    await expect(page.locator('.datagrid_month_grid .datagrid_month_cell[aria-label]')).toHaveCount(0);

    await expect(page.locator('.datagrid_month_grid .datagrid_month_cell[aria-current="date"]')).toHaveCount(1);
    await expect(page.locator('.datagrid_month_grid .datagrid_month_cell[aria-selected="true"]')).toHaveCount(1);

    const firstLockedCell = page.locator('.datagrid_month_grid .datagrid_month_cell[data-locked="1"]').first();
    if (await firstLockedCell.count()) {
      await expect(firstLockedCell).toHaveAttribute('aria-disabled', 'true');
    }

    await page.locator('button[data-action="next-month"]').click();
    await expect(page.locator('.datagrid_month_grid .datagrid_month_cell').first()).toBeVisible();
    await expect(page.locator('.datagrid_month_grid .datagrid_month_cell[aria-selected="true"]')).toHaveCount(1);
  });

  test('calendar transient hide/show does not clear decrypted profile marker', async ({ page }) => {
    await page.goto('/');

    await expect(page.locator('.datagrid_month_grid[role="grid"]')).toBeVisible();

    const marker = 'playwright-transient-visibility-marker';

    await page.evaluate((value) => {
      window.PAYCAL_USER_PROFILE_ENCRYPTED = value;

      let visibilityState = 'visible';
      Object.defineProperty(document, 'visibilityState', {
        configurable: true,
        get() {
          return visibilityState;
        },
      });

      window.__paycalSetVisibilityForTest = (nextState) => {
        visibilityState = nextState;
        document.dispatchEvent(new Event('visibilitychange'));
      };
    }, marker);

    await page.evaluate(() => {
      window.__paycalSetVisibilityForTest('hidden');
      window.__paycalSetVisibilityForTest('visible');
    });

    await page.waitForTimeout(250);

    await expect
      .poll(async () => page.evaluate(() => window.PAYCAL_USER_PROFILE_ENCRYPTED))
      .toBe(marker);

    await page.locator('button[data-action="next-month"]').click();
    await expect(page.locator('.datagrid_month_grid .datagrid_month_cell').first()).toBeVisible();
  });

  test('calendar explicit crypto clear zeroizes marker and keeps grid usable', async ({ page }) => {
    await page.goto('/');

    await expect(page.locator('.datagrid_month_grid[role="grid"]')).toBeVisible();

    await page.evaluate(() => {
      window.PAYCAL_USER_PROFILE_ENCRYPTED = 'playwright-explicit-clear-marker';
    });

    await page.evaluate(async () => {
      await window.PayCalCrypto.clear();
    });

    await expect
      .poll(async () => page.evaluate(() => window.PAYCAL_USER_PROFILE_ENCRYPTED))
      .toBe(null);

    await page.locator('button[data-action="next-month"]').click();
    await expect(page.locator('.datagrid_month_grid .datagrid_month_cell').first()).toBeVisible();
  });

  test('calendar hidden-delay does not clear marker when DEK is absent', async ({ page }) => {
    await page.goto('/');

    await expect(page.locator('.datagrid_month_grid[role="grid"]')).toBeVisible();

    const marker = 'playwright-hidden-delay-no-dek-marker';

    await page.evaluate((value) => {
      window.PAYCAL_USER_PROFILE_ENCRYPTED = value;

      let visibilityState = 'visible';
      Object.defineProperty(document, 'visibilityState', {
        configurable: true,
        get() {
          return visibilityState;
        },
      });

      window.__paycalSetVisibilityForTest = (nextState) => {
        visibilityState = nextState;
        document.dispatchEvent(new Event('visibilitychange'));
      };
    }, marker);

    await expect
      .poll(async () => page.evaluate(() => window.PayCalCrypto.hasDek))
      .toBe(false);

    await page.evaluate(() => {
      window.__paycalSetVisibilityForTest('hidden');
    });

    await page.waitForTimeout(16000);

    await expect
      .poll(async () => page.evaluate(() => window.PAYCAL_USER_PROFILE_ENCRYPTED))
      .toBe(marker);

    await page.evaluate(() => {
      window.__paycalSetVisibilityForTest('visible');
    });

    await page.locator('button[data-action="next-month"]').click();
    await expect(page.locator('.datagrid_month_grid .datagrid_month_cell').first()).toBeVisible();
  });

  test('calendar hidden-delay zeroizes unlocked DEK state after expiry', async ({ page }) => {
    await page.addInitScript(() => {
      window.__PAYCAL_ENABLE_TEST_HOOKS = true;
    });
    await page.goto('/');

    await expect(page.locator('.datagrid_month_grid[role="grid"]')).toBeVisible();

    await page.evaluate(() => {
      let visibilityState = 'visible';
      Object.defineProperty(document, 'visibilityState', {
        configurable: true,
        get() {
          return visibilityState;
        },
      });

      window.__paycalSetVisibilityForTest = (nextState) => {
        visibilityState = nextState;
        document.dispatchEvent(new Event('visibilitychange'));
      };

      window.__PAYCAL_TEST_HOOKS.forceHasDek(true);
      window.__PAYCAL_TEST_HOOKS.setProfileMarker('playwright-unlocked-hidden-expiry');
      window.__paycalSetVisibilityForTest('hidden');
    });

    await page.waitForTimeout(16000);

    await expect
      .poll(async () => page.evaluate(() => window.__PAYCAL_TEST_HOOKS.getState().hasDek))
      .toBe(false);

    await expect
      .poll(async () => page.evaluate(() => window.__PAYCAL_TEST_HOOKS.getState().profileMarker))
      .toBe(null);
  });

  test('calendar deterministic re-unlock path recovers after lifecycle zeroize', async ({ page }) => {
    await page.addInitScript(() => {
      window.__PAYCAL_ENABLE_TEST_HOOKS = true;
    });
    await page.goto('/');

    await expect(page.locator('.datagrid_month_grid[role="grid"]')).toBeVisible();

    await page.evaluate(() => {
      let visibilityState = 'visible';
      Object.defineProperty(document, 'visibilityState', {
        configurable: true,
        get() {
          return visibilityState;
        },
      });

      window.__paycalSetVisibilityForTest = (nextState) => {
        visibilityState = nextState;
        document.dispatchEvent(new Event('visibilitychange'));
      };

      window.__PAYCAL_TEST_HOOKS.forceHasDek(true);
      window.__PAYCAL_TEST_HOOKS.setProfileMarker('playwright-pre-zeroize');
      window.__paycalSetVisibilityForTest('hidden');
    });

    await page.waitForTimeout(16000);

    await page.evaluate(() => {
      window.__paycalSetVisibilityForTest('visible');
      window.__PAYCAL_TEST_HOOKS.forceHasDek(true);
      window.__PAYCAL_TEST_HOOKS.setProfileMarker('playwright-post-reunlock');
    });

    await expect
      .poll(async () => page.evaluate(() => window.__PAYCAL_TEST_HOOKS.getState().hasDek))
      .toBe(true);

    await expect
      .poll(async () => page.evaluate(() => window.__PAYCAL_TEST_HOOKS.getState().profileMarker))
      .toBe('playwright-post-reunlock');

    await page.locator('button[data-action="next-month"]').click();
    await expect(page.locator('.datagrid_month_grid .datagrid_month_cell').first()).toBeVisible();
  });

  test('calendar keyboard navigation keeps exactly one selected cell', async ({ page }) => {
    await page.goto('/');

    const firstCell = page.locator('.datagrid_month_grid .datagrid_month_cell').first();
    await firstCell.focus();

    await page.keyboard.press('ArrowRight');
    await expect(page.locator('.datagrid_month_grid .datagrid_month_cell[aria-selected="true"]')).toHaveCount(1);

    const selectedDate = await page.locator('.datagrid_month_grid .datagrid_month_cell[aria-selected="true"]').first().getAttribute('data-id');
    const activeDate = await page.evaluate(() => {
      const activeCell = document.activeElement && document.activeElement.closest
        ? document.activeElement.closest('.datagrid_month_cell')
        : null;
      return activeCell ? activeCell.getAttribute('data-id') : null;
    });

    expect(activeDate).toBe(selectedDate);
  });
});
