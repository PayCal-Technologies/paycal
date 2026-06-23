/*
 * Verification code formatter for the verify page.
 * Keeps code as XXX-XXX uppercase while typing.
 */
import { bindGroupedCodeInput } from '/js/core/paycal-code.js';

document.addEventListener('DOMContentLoaded', () => {
  const input = document.querySelector('[data-verify-code-format="true"]');
  if (!input) {
    return;
  }

  bindGroupedCodeInput(input, {
    allowedChars: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
    maxLength: 6,
    splitAt: 3,
  });
});
