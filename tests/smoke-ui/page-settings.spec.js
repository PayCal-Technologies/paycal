import { expect, test } from '@playwright/test';
import { clickIfAvailable, expectAnyVisible, requireAuthenticated, requirePageSentinel } from './support/page-feature-helpers.js';

const settingsPages = [
  {
    path: '/settings/',
    label: 'settings dashboard',
    sentinel: '#settings_dashboard_heading, #settings_page, main',
    selectors: ['#passkey_credentials_sr_status', '#admin-nav-toggle', 'main'],
  },
  {
    path: '/settings/account/',
    label: 'account settings',
    sentinel: '#edit_details_form, #recovery_email_input, main',
    selectors: ['#edit_details_full_name', '#edit_details_status', '#recovery_email_input'],
  },
  {
    path: '/settings/calendar/',
    label: 'calendar settings',
    sentinel: '#account_work_defaults_form, #default_hours, main',
    selectors: ['#default_hours', '#default_site_id', '#default_travel_hours'],
  },
  {
    path: '/settings/appearance/',
    label: 'appearance settings',
    sentinel: '#accent_preset_swatches, #accent_preset_preview, main',
    selectors: ['#accent_preset_swatches', '#accent_preset_preview', '[name="theme"]'],
  },
  {
    path: '/settings/accessibility/',
    label: 'accessibility settings',
    sentinel: 'main',
    selectors: ['main form', 'main [role="status"]', 'main input'],
  },
  {
    path: '/settings/security/',
    label: 'security settings',
    sentinel: '#security_passkeys_widget, #passkey_credentials_list, main',
    selectors: ['#passkey_credentials_sr_status', '#add_passkey_first_button', '#create_recovery_key_btn'],
  },
  {
    path: '/settings/data/',
    label: 'data settings',
    sentinel: '#account_data_portability_form, #panel-data-consent, main',
    selectors: ['#data_export_run_btn', '#data_import_prepare_btn', '#account_data_portability_form'],
  },
  {
    path: '/settings/subscription/',
    label: 'subscription settings',
    sentinel: '#billing_upgrade_btn, #billing_downgrade_free_dialog, main',
    selectors: ['#billing_upgrade_btn', '#billing_portal_btn', '#billing_downgrade_free_dialog'],
  },
  {
    path: '/settings/diagnostics/',
    label: 'diagnostics settings',
    sentinel: 'main',
    selectors: ['main [role="status"]', 'main form', 'main a[href*="/admin/argus/"]'],
  },
  {
    path: '/settings/early-access/',
    label: 'early access settings',
    sentinel: 'main',
    selectors: ['main form', 'main [role="status"]', 'main button'],
  },
];

test.describe.configure({ mode: 'parallel' });

for (const settingsPage of settingsPages) {
  test.describe(`Page feature: ${settingsPage.label}`, () => {
    test('renders controls and supports safe interactions', async ({ page }) => {
      const state = await requirePageSentinel(page, settingsPage.path, settingsPage.sentinel, 'settings-page-unavailable');
      if (!state) {
        return;
      }

      await expect(page.locator('main').first()).toBeVisible();
      await expectAnyVisible(page, settingsPage.selectors, `${settingsPage.label} key controls`);

      await clickIfAvailable(page.locator('#admin-nav-toggle'));
      if ((await page.locator('#admin-nav-popover').count()) > 0) {
        await expect(page.locator('#admin-nav-popover')).toBeVisible();
        await page.keyboard.press('Escape');
      }

      await clickIfAvailable(page.locator('[aria-expanded][aria-controls]').first());
      state.health.assertClean();
    });
  });
}

test.describe('Page feature: settings billing module', () => {
  test('subscription downgrade confirmation gate can be exercised without submitting', async ({ page }) => {
    const state = await requireAuthenticated(page, '/settings/subscription/');
    if (!state) {
      return;
    }

    const phrase = page.locator('#billing_downgrade_phrase');
    const confirm = page.locator('#billing_downgrade_confirm');
    if ((await phrase.count()) === 0 || (await confirm.count()) === 0) {
      state.health.assertClean();
      return;
    }

    await page.evaluate(() => {
      document.querySelector('#billing_downgrade_free_dialog')?.removeAttribute('hidden');
    });
    await expect(phrase).toBeVisible();
    await phrase.fill('DOWNGRADE ME');
    await expect(confirm).toBeEnabled();
    state.health.assertClean();
  });
});
