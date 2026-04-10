<?php
/**
 * Help: Browser & Device Requirements — English
 *
 * PURPOSE: Standalone article covering WebAuthn, passkey, supported browser,
 *          and HTTPS requirements for using PayCal. This is the authoritative
 *          user-facing reference for why these requirements exist and what
 *          hardware/software combinations are supported.
 * LOCATION: html/help/requirements/en.php
 */

declare(strict_types=1);

use PayCal\Domain\Render;
use PayCal\Domain\Strings;

require_once __DIR__ . '/../../config.php';

if (function_exists('help_req_i18n') === false) {
  function help_req_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }
    return $cache[$key];
  }
}

$currentPage = 'PAGE_HELP';
$pageTitle = help_req_i18n('HELP_REQUIREMENTS_TITLE') . ' - [' . help_req_i18n('SITE_NAME') . ']';
$pageLabel = help_req_i18n('HELP_REQUIREMENTS_TITLE');

require_once HTML . '/header.php';
?>

<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo htmlspecialchars(help_req_i18n('HELP_BREADCRUMB_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
    <a href="/"><?php echo htmlspecialchars(help_req_i18n('HOME'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <a href="/help/"><?php echo htmlspecialchars(help_req_i18n('HELP_CENTER_TITLE'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck"><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_WHY_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_WHY_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
      <p><a class="doc-read-more" href="/help/webauthn-security/"><?php echo htmlspecialchars(help_req_i18n('HELP_WEBAUTHN_REQUIREMENTS_LINK'), ENT_QUOTES, 'UTF-8'); ?></a></p>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_WEBAUTHN_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_WEBAUTHN_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_HTTPS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_HTTPS_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_PASSKEYS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_PASSKEYS_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_PASSKEYS_ITEMS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_PASSKEYS_ITEM_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_PASSKEYS_ITEM_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_PASSKEYS_ITEM_3'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_BROWSERS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_BROWSERS_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_BROWSER_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_BROWSER_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_BROWSER_3'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_BROWSER_4'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_BROWSER_5'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
      <p><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_BROWSER_CAVEAT'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_UNSUPPORTED_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_req_i18n('HELP_REQUIREMENTS_UNSUPPORTED_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>

  </div><!-- .doc-article-body -->
</article>

<?php
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('transparency') . '">' . PHP_EOL;
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('help') . '">' . PHP_EOL;
echo PHP_EOL . Render::jsScript('help');
require_once HTML . '/footer.php';
?>
