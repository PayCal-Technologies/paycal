/*
 * Verification code formatter for the verify page.
 * Keeps code as XXX-XXX uppercase while typing.
 */
(function () {
  'use strict';

  function formatCode(value) {
    var normalized = String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
    if (normalized.length > 3) {
      return normalized.slice(0, 3) + '-' + normalized.slice(3);
    }
    return normalized;
  }

  document.addEventListener('DOMContentLoaded', function () {
    var input = document.querySelector('[data-verify-code-format="true"]');
    if (!input) {
      return;
    }

    input.addEventListener('input', function () {
      input.value = formatCode(input.value);
    });

    input.addEventListener('blur', function () {
      input.value = input.value.toUpperCase();
    });
  });
})();
