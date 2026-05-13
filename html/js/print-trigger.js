/**
 * print-trigger.js
 *
 * Auto-fires window.print() for the ?view=pdf content view endpoint.
 * This file is conditionally loaded by header.php only when:
 *   - $currentPage is a doc-article type (PAGE_BLOG, PAGE_HELP, etc.)
 *   - $_GET['view'] === 'pdf'
 *
 * Using an external script (with nonce) instead of an inline script
 * satisfies the CSP script-src policy (no 'unsafe-inline' required).
 *
 * Location: html/js/print-trigger.js
 * Loaded by: html/header.php (conditionally, with nonce)
 */

window.print();
