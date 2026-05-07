<?php declare(strict_types=1);

use PayCal\Domain\Render;
use PayCal\Domain\Strings;

require_once '../config.php';

if (function_exists('help_index_i18n') === false) {
  function help_index_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}

$currentPage = 'PAGE_HELP';
$pageTitle = help_index_i18n('HELP_CENTER_TITLE') . ' - [' . help_index_i18n('SITE_NAME') . ']';
$pageLabel = help_index_i18n('HELP_CENTER_TITLE');

\PayCal\Observability\Lens::boot('help');
if (\PayCal\Domain\InputSanitizer::getString('lens') === '1') {
  \PayCal\Observability\Lens::add('Help Backend Snapshot', [
    'page' => $currentPage,
    'language' => (string) USER_LANGUAGE,
    'toc_sections' => 7,
  ]);
}

require_once HTML.'/header.php';

?>

<div id="help_main" class="w100">

    <nav class="doc-breadcrumb" aria-label="<?php echo htmlspecialchars(help_index_i18n('HELP_BREADCRUMB_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
      <a href="/"><?php echo htmlspecialchars(help_index_i18n('HOME'), ENT_QUOTES, 'UTF-8'); ?></a>
      <span class="separator">/</span>
      <span class="current"><?php echo htmlspecialchars(help_index_i18n('HELP_CENTER_TITLE'), ENT_QUOTES, 'UTF-8'); ?></span>
    </nav>

    <article class="article doc-article" aria-label="<?php echo htmlspecialchars(help_index_i18n('HELP_TOC_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
      <header class="doc-article-header">
        <h1><?php echo htmlspecialchars(help_index_i18n('HELP_CENTER_HEADING'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="deck"><?php echo htmlspecialchars(help_index_i18n('HELP_CENTER_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
      </header>
      <div class="doc-article-body">
        <section class="doc-section highlight">
          <p><?php echo htmlspecialchars(help_index_i18n('HELP_TOC_INTRO'), ENT_QUOTES, 'UTF-8'); ?></p>
          <p><?php echo htmlspecialchars(help_index_i18n('HELP_TOC_ACCESSIBILITY_PREFIX'), ENT_QUOTES, 'UTF-8'); ?> <a href="/transparency/accessibility/"><?php echo htmlspecialchars(help_index_i18n('HELP_TOC_ACCESSIBILITY_LINK'), ENT_QUOTES, 'UTF-8'); ?></a>.</p>
          <p><?php echo htmlspecialchars(help_index_i18n('HELP_TOC_TRANSPARENCY_HUB_PREFIX'), ENT_QUOTES, 'UTF-8'); ?> <a href="/transparency/"><?php echo htmlspecialchars(help_index_i18n('HELP_TOC_TRANSPARENCY_HUB'), ENT_QUOTES, 'UTF-8'); ?></a>.</p>
        </section>

        <div class="doc-panel-grid doc-panel-grid--responsive-3" aria-label="<?php echo htmlspecialchars(help_index_i18n('HELP_TOC_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">

          <section class="doc-section">
            <h2><?php echo htmlspecialchars(help_index_i18n('HELP_TOC_GETTING_STARTED'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo htmlspecialchars(help_index_i18n('HELP_GETTING_STARTED_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><a class="doc-read-more" href="/help/getting-started/"><?php echo htmlspecialchars(help_index_i18n('HELP_READ_MORE'), ENT_QUOTES, 'UTF-8'); ?></a></p>
          </section>

          <section class="doc-section">
            <h2><?php echo htmlspecialchars(help_index_i18n('HELP_TOC_SETUP_WORK_SITE'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo htmlspecialchars(help_index_i18n('HELP_WORK_SITE_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><a class="doc-read-more" href="/help/work-site/"><?php echo htmlspecialchars(help_index_i18n('HELP_READ_MORE'), ENT_QUOTES, 'UTF-8'); ?></a></p>
          </section>

          <section class="doc-section">
            <h2><?php echo htmlspecialchars(help_index_i18n('HELP_TOC_LOG_WORK_HOURS'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo htmlspecialchars(help_index_i18n('HELP_WORK_HOURS_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><a class="doc-read-more" href="/help/work-hours/"><?php echo htmlspecialchars(help_index_i18n('HELP_READ_MORE'), ENT_QUOTES, 'UTF-8'); ?></a></p>
          </section>

          <section class="doc-section">
            <h2><?php echo htmlspecialchars(help_index_i18n('HELP_TOC_TAX_BRACKETS'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo htmlspecialchars(help_index_i18n('HELP_TAX_BRACKETS_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><a class="doc-read-more" href="/help/tax-brackets/"><?php echo htmlspecialchars(help_index_i18n('HELP_READ_MORE'), ENT_QUOTES, 'UTF-8'); ?></a></p>
          </section>

          <section class="doc-section">
            <h2><?php echo htmlspecialchars(help_index_i18n('HELP_TOC_ACCOUNT_SETTINGS'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo htmlspecialchars(help_index_i18n('HELP_ACCOUNT_SETTINGS_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><a class="doc-read-more" href="/help/account-settings/"><?php echo htmlspecialchars(help_index_i18n('HELP_READ_MORE'), ENT_QUOTES, 'UTF-8'); ?></a></p>
          </section>

          <section class="doc-section">
            <h2><?php echo htmlspecialchars(help_index_i18n('HELP_TOC_TROUBLESHOOTING'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo htmlspecialchars(help_index_i18n('HELP_TROUBLESHOOTING_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><a class="doc-read-more" href="/help/troubleshooting/"><?php echo htmlspecialchars(help_index_i18n('HELP_READ_MORE'), ENT_QUOTES, 'UTF-8'); ?></a></p>
          </section>

          <section class="doc-section">
            <h2><?php echo htmlspecialchars(help_index_i18n('HELP_TOC_POLICIES_LEGAL'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo htmlspecialchars(help_index_i18n('HELP_POLICIES_LEGAL_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><a class="doc-read-more" href="/help/policies-legal/"><?php echo htmlspecialchars(help_index_i18n('HELP_READ_MORE'), ENT_QUOTES, 'UTF-8'); ?></a></p>
          </section>

          <section class="doc-section">
            <h2><?php echo htmlspecialchars(help_index_i18n('HELP_TOC_REQUIREMENTS'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo htmlspecialchars(help_index_i18n('HELP_REQUIREMENTS_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><a class="doc-read-more" href="/help/requirements/"><?php echo htmlspecialchars(help_index_i18n('HELP_READ_MORE'), ENT_QUOTES, 'UTF-8'); ?></a></p>
          </section>

          <section class="doc-section">
            <h2><?php echo htmlspecialchars(help_index_i18n('HELP_TOC_WEBAUTHN_SECURITY'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo htmlspecialchars(help_index_i18n('HELP_WEBAUTHN_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><a class="doc-read-more" href="/help/webauthn-security/"><?php echo htmlspecialchars(help_index_i18n('HELP_READ_MORE'), ENT_QUOTES, 'UTF-8'); ?></a></p>
          </section>

        </div><!-- .doc-panel-grid -->
      </div><!-- .doc-article-body -->
    </article>

</div><!-- help_main -->

<?php

echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('transparency') . '">' . PHP_EOL;
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('help') . '">' . PHP_EOL;
echo PHP_EOL . Render::jsScript('help');
require_once HTML . '/footer.php';

