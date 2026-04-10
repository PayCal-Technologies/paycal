<?php
/**
 * Help: WebAuthn Security and Privacy Controls — English
 *
 * PURPOSE: Standalone article explaining the WebAuthn / passkey requirement,
 *          the KEK + DEK encryption model, and what it means for user privacy.
 *          Part of the Help article system using doc-article / doc-breadcrumb
 *          layout matching the /transparency article style.
 * LOCATION: html/help/webauthn-security/en.php
 */

declare(strict_types=1);

use PayCal\Domain\Render;
use PayCal\Domain\Strings;

require_once __DIR__ . '/../../config.php';

if (function_exists('help_wa_i18n') === false) {
  function help_wa_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }
    return $cache[$key];
  }
}

$currentPage = 'PAGE_HELP';
$pageTitle = help_wa_i18n('HELP_WEBAUTHN_TITLE') . ' - [' . help_wa_i18n('SITE_NAME') . ']';
$pageLabel = help_wa_i18n('HELP_WEBAUTHN_TITLE');

require_once HTML . '/header.php';
?>

<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo htmlspecialchars(help_wa_i18n('HELP_BREADCRUMB_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
    <a href="/"><?php echo htmlspecialchars(help_wa_i18n('HOME'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <a href="/help/"><?php echo htmlspecialchars(help_wa_i18n('HELP_CENTER_TITLE'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_TITLE'), ENT_QUOTES, 'UTF-8'); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck"><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_WHY_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_WHY_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
      <p><a class="doc-read-more" href="/help/requirements/"><?php echo htmlspecialchars(help_wa_i18n('HELP_TOC_REQUIREMENTS'), ENT_QUOTES, 'UTF-8'); ?></a></p>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_KEK_DEK_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_KEK_DEK_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
      <ul class="doc-fact-list">
        <li><strong><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_DEK_LABEL'), ENT_QUOTES, 'UTF-8'); ?></strong> — <?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_DEK_DESC'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><strong><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_KEK_LABEL'), ENT_QUOTES, 'UTF-8'); ?></strong> — <?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_KEK_DESC'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_PRIVACY_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_PRIVACY_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_PRIVACY_ITEM_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_PRIVACY_ITEM_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_PRIVACY_ITEM_3'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_PRIVACY_ITEM_4'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_UNSUPPORTED_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_UNSUPPORTED_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
      <p><a class="doc-read-more" href="/help/requirements/"><?php echo htmlspecialchars(help_wa_i18n('HELP_WEBAUTHN_REQUIREMENTS_LINK'), ENT_QUOTES, 'UTF-8'); ?></a></p>
    </section>

  </div><!-- .doc-article-body -->
</article>

<?php
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('transparency') . '">' . PHP_EOL;
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('help') . '">' . PHP_EOL;
echo PHP_EOL . Render::jsScript('help');
require_once HTML . '/footer.php';
?>
