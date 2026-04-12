import { test, expect } from '@playwright/test';

test.describe('WCAG dialog chrome regression', () => {
  test('openModal auto-wires dialog labels, close button, and focus trap', async ({ page }) => {
    await page.goto('/settings/');
    await expect(page.locator('body')).toBeVisible();

    const result = await page.evaluate(() => {
      const id = 'playwright_dialog_chrome_regression';
      let dialog = document.getElementById(id);
      if (dialog) dialog.remove();

      dialog = document.createElement('dialog');
      dialog.id = id;
      dialog.className = 'dialog';
      dialog.innerHTML = [
        '<div class="modal_header">',
        '  <h2 class="modal_title">Smoke Dialog</h2>',
        '</div>',
        '<div class="modal_content">',
        '  <p>Dialog body for chrome regression.</p>',
        '</div>'
      ].join('');

      document.body.appendChild(dialog);
      window.PayCalCore.openModal(id, 'Smoke Dialog');

      const labelledBy = dialog.getAttribute('aria-labelledby') || '';
      const describedBy = dialog.getAttribute('aria-describedby') || '';
      const labelEl = labelledBy ? document.getElementById(labelledBy) : null;
      const descEl = describedBy ? document.getElementById(describedBy) : null;
      const closeButton = dialog.querySelector('[data-dialog-close="' + id + '"]');

      const snapshot = {
        open: dialog.open,
        ariaHidden: dialog.getAttribute('aria-hidden'),
        ariaModal: dialog.getAttribute('aria-modal'),
        labelledBy,
        describedBy,
        hasLabelTarget: !!labelEl,
        hasDescTarget: !!descEl,
        hasCloseButton: !!closeButton,
        focusTrapBound: dialog.dataset.focusTrapBound === 'true',
      };

      window.PayCalCore.closeModal(id, 'Smoke Dialog');
      dialog.remove();

      return snapshot;
    });

    expect(result.open).toBe(true);
    expect(result.ariaHidden).toBe('false');
    expect(result.ariaModal).toBe('true');
    expect(result.labelledBy).not.toBe('');
    expect(result.describedBy).not.toBe('');
    expect(result.hasLabelTarget).toBe(true);
    expect(result.hasDescTarget).toBe(true);
    expect(result.hasCloseButton).toBe(true);
    expect(result.focusTrapBound).toBe(true);
  });
});
