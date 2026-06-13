<?php declare(strict_types=1);

/**
 * admin/language-dashboard/index.php
 *
 * Purpose: Admin language translation dashboard page.
 * URL: /admin/language-dashboard/
 *
 * Displays a real-time audit of all language string files and provides
 * per-language AI translation controls that drive batch-by-batch progress
 * without leaving the page.
 *
 * Access: Admin and Superadmin only (AdminSurface enforces this).
 *
 * Architecture notes:
 * - Server-renders initial audit data via LanguageAuditService for instant
 *   first paint; the JS module re-fetches on load and refreshes after each
 *   translation batch, so both paths produce consistent output.
 * - All dynamic DOM injection in the JS module goes through Guardian.setHTML().
 * - No inline scripts or styles (CSP compliance).
 */

use PayCal\Domain\AdminSurface;
use PayCal\Domain\Authentication;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\LanguageAuditService;
use PayCal\Domain\Render;
use PayCal\Domain\Strings;
use PayCal\Domain\User;

require_once '../../config.php';

if (function_exists('language_dashboard_i18n') === false) {
  function language_dashboard_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}

$currentPage = 'PAGE_ADMIN';
$pageTitle   = language_dashboard_i18n('ADMIN_LANGUAGE_DASHBOARD_TITLE') . ' - [PayCal]';
$pageLabel   = language_dashboard_i18n('ADMIN_LANGUAGE_DASHBOARD_TITLE');

Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/language-dashboard/');

// ─── Server-side audit (initial page data) ─────────────────────────────────
$stringsDir = HTML . '/../strings';
$service    = new LanguageAuditService($stringsDir);

try {
  $report = $service->fullReport();
} catch (\Throwable $e) {
  $report = null;
  $reportError = $e->getMessage();
}

$cspNonceRaw = $_SERVER['CSP_NONCE'] ?? '';
$cspNonce    = is_scalar($cspNonceRaw) ? htmlspecialchars((string) $cspNonceRaw, ENT_QUOTES, 'UTF-8') : '';
$appVersion  = Environment::appVersion();
$jsBase      = Environment::appURL('js/admin/language-dashboard.php') . '?v=' . $appVersion;

require_once HTML . '/header.php';
?>
<link rel="stylesheet" href="<?= htmlspecialchars(Render::cssURL('admin'), ENT_QUOTES, 'UTF-8') ?>" nonce="<?= $cspNonce ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(Render::cssURL('admin/language-dashboard'), ENT_QUOTES, 'UTF-8') ?>" nonce="<?= $cspNonce ?>">

<div id="lang-dash-announce" class="lang-dash__sr-only" role="status" aria-live="polite" aria-atomic="true"></div>

<div class="lang-dash" id="lang-dash-root">

  <header class="lang-dash__header panel w100 pad_md mar_sm">
    <div>
      <h1><?= htmlspecialchars(language_dashboard_i18n('ADMIN_LANGUAGE_DASHBOARD_TITLE'), ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="help_text"><?= htmlspecialchars(language_dashboard_i18n('ADMIN_LANGUAGE_DASHBOARD_DESC'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="lang-dash__header-actions">
      <a class="btn btn_secondary" href="/admin/">Back to Admin</a>
      <a class="btn btn_secondary" href="/admin/language-editor/">Language Editor</a>
    </div>
  </header>

  <?php if (isset($reportError)): ?>
  <div class="panel w100 pad_md mar_sm" role="alert">
    <p class="danger">Audit service error: <?= htmlspecialchars($reportError, ENT_QUOTES, 'UTF-8') ?></p>
  </div>
  <?php endif; ?>

  <div id="lang-dash-loading" class="panel w100 pad_md mar_sm" aria-live="polite">
    <span class="lang-dash__spinner" aria-hidden="true"></span>
    Loading audit data…
  </div>

  <div id="lang-dash-error" class="panel w100 pad_md mar_sm danger" role="alert" hidden></div>

  <!-- Summary stats bar -->
  <section class="panel w100 pad_md mar_sm" aria-label="Language coverage summary">
    <h2 class="lang-dash__section-title"><?= htmlspecialchars(language_dashboard_i18n('ADMIN_LANGUAGE_DASHBOARD_COVERAGE_SUMMARY'), ENT_QUOTES, 'UTF-8') ?></h2>
    <div id="lang-dash-stats" class="lang-dash__stats">Loading…</div>

    <div class="lang-dash__table-wrap">
      <table class="lang-dash__summary-table" aria-label="Language audit table">
        <thead>
          <tr>
            <th scope="col">Language</th>
            <th scope="col">Translated</th>
            <th scope="col">Coverage</th>
            <th scope="col">Order</th>
            <th scope="col">Encoding</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody id="lang-dash-table-body">
          <tr><td colspan="6" class="lang-dash__loading-cell">Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Per-language detail cards -->
  <section class="panel w100 pad_md mar_sm" aria-label="Per-language translation cards">
    <h2 class="lang-dash__section-title"><?= htmlspecialchars(language_dashboard_i18n('ADMIN_LANGUAGE_DASHBOARD_LANGUAGE_DETAILS'), ENT_QUOTES, 'UTF-8') ?></h2>
    <div id="lang-dash-cards" class="lang-dash__cards">
      <p class="lang-dash__placeholder">Loading…</p>
    </div>
  </section>

</div><!-- /.lang-dash -->

<script type="module" src="<?= htmlspecialchars($jsBase, ENT_QUOTES, 'UTF-8') ?>" nonce="<?= $cspNonce ?>"></script>
<?php
require_once HTML . '/footer.php';
