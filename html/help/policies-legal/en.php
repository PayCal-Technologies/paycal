<?php
/**
 * Help: Policies & Legal — English
 *
 * PURPOSE: Standalone article covering privacy policy, terms of service, and
 *          accessibility links. Part of the Help article system using
 *          doc-article / doc-breadcrumb layout.
 * LOCATION: html/help/policies-legal/en.php
 */

declare(strict_types=1);

use PayCal\Domain\Render;
use PayCal\Domain\Strings;

require_once __DIR__ . '/../../config.php';

if (function_exists('help_pl_i18n') === false) {
  function help_pl_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }
    return $cache[$key];
  }
}

$currentPage = 'PAGE_HELP';
$pageTitle = help_pl_i18n('HELP_LEGAL_TITLE') . ' - [' . help_pl_i18n('SITE_NAME') . ']';
$pageLabel = help_pl_i18n('HELP_LEGAL_TITLE');

require_once HTML . '/header.php';
?>

<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo htmlspecialchars(help_pl_i18n('HELP_BREADCRUMB_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
    <a href="/"><?php echo htmlspecialchars(help_pl_i18n('HOME'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <a href="/help/"><?php echo htmlspecialchars(help_pl_i18n('HELP_CENTER_TITLE'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_TITLE'), ENT_QUOTES, 'UTF-8'); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck"><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
  </header>

  <div class="doc-article-body">

    <!-- Privacy Policy -->
    <section id="article-privacy" class="doc-section">
      <h2><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_PRIVACY_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_PRIVACY_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_PRIVACY_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_PRIVACY_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_PRIVACY_3'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_PRIVACY_4'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
      <p><a class="doc-read-more" href="/policies/#privacy"><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_PRIVACY_LINK'), ENT_QUOTES, 'UTF-8'); ?></a></p>
    </section>

    <!-- Terms of Service -->
    <section id="article-terms" class="doc-section">
      <h2><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_TERMS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_TERMS_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_TERMS_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_TERMS_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_TERMS_3'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_TERMS_4'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
      <p><a class="doc-read-more" href="/policies/#terms"><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_TERMS_LINK'), ENT_QUOTES, 'UTF-8'); ?></a></p>
    </section>

    <!-- Accessibility -->
    <section id="article-accessibility" class="doc-section">
      <h2><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_ACCESSIBILITY_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_ACCESSIBILITY_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
      <ul class="doc-fact-list">
        <li><a href="/transparency/accessibility/"><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_ACCESSIBILITY_LINK'), ENT_QUOTES, 'UTF-8'); ?></a></li>
        <li><a href="/transparency/"><?php echo htmlspecialchars(help_pl_i18n('HELP_LEGAL_TRANSPARENCY_LINK'), ENT_QUOTES, 'UTF-8'); ?></a></li>
      </ul>
    </section>

  </div><!-- .doc-article-body -->
</article>

<?php
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('transparency') . '">' . PHP_EOL;
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('help') . '">' . PHP_EOL;
echo PHP_EOL . Render::jsScript('help');
require_once HTML . '/footer.php';
?>
