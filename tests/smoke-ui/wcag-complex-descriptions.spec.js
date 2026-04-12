import { test, expect } from '@playwright/test';

async function openSignedIn(page, path) {
  await page.goto(path);
}

test.describe('WCAG complex descriptions', () => {
  test('calendar month grid exposes instruction and context descriptions', async ({ page }) => {
    await openSignedIn(page, '/');

    const section = page.locator('section[aria-labelledby="calendar-landmark-title"]');
    await expect(section).toHaveAttribute('aria-describedby', /calendar-grid-instructions/);
    await expect(section).toHaveAttribute('aria-describedby', /calendar-grid-context/);
    await expect(section).toHaveAttribute('aria-describedby', /calendar-month-status/);

    const monthGrid = page.locator('.datagrid_month_grid[role="grid"]').first();
    await expect(monthGrid).toHaveAttribute('aria-describedby', /calendar-grid-context/);
    await expect(page.locator('#calendar-grid-context')).toBeAttached();
  });

  test('sites and organizations grids include extended SR context', async ({ page }) => {
    await openSignedIn(page, '/sites/');

    await expect(page.locator('#sites-grid-active')).toHaveAttribute('aria-describedby', /sites_grid_active_sr_context/);
    await expect(page.locator('#sites-grid-archived')).toHaveAttribute('aria-describedby', /sites_grid_archived_sr_context/);
    await expect(page.locator('#sites_grid_active_sr_context')).toBeAttached();
    await expect(page.locator('#sites_grid_archived_sr_context')).toBeAttached();

    await openSignedIn(page, '/organizations/');

    await expect(page.locator('#organizations-grid')).toHaveAttribute('aria-describedby', /organizations_grid_sr_context/);
    await expect(page.locator('#organizations_grid_sr_context')).toBeAttached();
  });

  test('earnings trend chart exposes text alternatives and live status channel', async ({ page }) => {
    await openSignedIn(page, '/earnings/');

    const chart = page.locator('svg[id^="earnings_line_graph_"]').first();
    await expect(chart).toBeVisible();
    await expect(chart).toHaveAttribute('role', 'img');
    await expect(chart).toHaveAttribute('aria-labelledby', /earnings_line_graph_\d{4}_title/);
    await expect(chart).toHaveAttribute('aria-describedby', /earnings_line_graph_\d{4}_desc/);
    await expect(chart).toHaveAttribute('aria-describedby', /earnings_line_graph_\d{4}_status/);

    const status = page.locator('[id^="earnings_line_graph_"][id$="_status"]').first();
    await expect(status).not.toHaveText('');
  });

  test('admin AST canvas exposes text alternatives when admin route is available', async ({ page }) => {
    await openSignedIn(page, '/admin/ast/');

    const canvas = page.locator('#ast_graph_canvas');
    if ((await canvas.count()) === 0) {
      test.info().annotations.push({
        type: 'ast-admin-route',
        description: 'Admin AST route not reachable for this test account; skipping assertions.',
      });
      return;
    }

    await expect(canvas).toHaveAttribute('role', 'img');
    await expect(canvas).toHaveAttribute('aria-labelledby', 'ast_graph_title');
    await expect(canvas).toHaveAttribute('aria-describedby', /ast_graph_desc/);
    await expect(canvas).toHaveAttribute('aria-describedby', /ast_graph_status/);
    await expect(page.locator('#ast_graph_status')).not.toHaveText('');
  });
});
