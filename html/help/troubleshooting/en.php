<?php
/**
 * Help: Troubleshooting — English
 *
 * PURPOSE: Standalone article covering login issues, export problems, and
 *          support contact information. Part of the Help article system using
 *          doc-article / doc-breadcrumb layout.
 * LOCATION: html/help/troubleshooting/en.php
 */

declare(strict_types=1);

use PayCal\Domain\Render;
use PayCal\Domain\Strings;

require_once __DIR__ . '/../../config.php';

if (function_exists('help_ts_i18n') === false) {
  function help_ts_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }
    return $cache[$key];
  }
}

$currentPage = 'PAGE_HELP';
$pageTitle = help_ts_i18n('HELP_TROUBLESHOOTING_TITLE') . ' - [' . help_ts_i18n('SITE_NAME') . ']';
$pageLabel = help_ts_i18n('HELP_TROUBLESHOOTING_TITLE');

require_once HTML . '/header.php';
?>

<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo htmlspecialchars(help_ts_i18n('HELP_BREADCRUMB_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
    <a href="/"><?php echo htmlspecialchars(help_ts_i18n('HOME'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <a href="/help/"><?php echo htmlspecialchars(help_ts_i18n('HELP_CENTER_TITLE'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_TITLE'), ENT_QUOTES, 'UTF-8'); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck"><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
  </header>

  <div class="doc-article-body">

    <!-- Login Issues -->
    <section id="article-login" class="doc-section">
      <h2><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_LOGIN_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_LOGIN_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_PASSKEY_PROMPTS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_PASSKEY_PROMPTS_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_PASSKEY_PROMPTS_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_PASSKEY_PROMPTS_3'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>

      <h3><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_RECOVERY_KEY_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p>
        <?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_RECOVERY_KEY_PREFIX'), ENT_QUOTES, 'UTF-8'); ?>
        <a href="/settings/"><?php echo htmlspecialchars(help_ts_i18n('SETTINGS'), ENT_QUOTES, 'UTF-8'); ?></a>
        <?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_RECOVERY_KEY_SUFFIX'), ENT_QUOTES, 'UTF-8'); ?>
      </p>

      <h3><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_ACCOUNT_LOCKED_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p>
        <?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_ACCOUNT_LOCKED_PREFIX'), ENT_QUOTES, 'UTF-8'); ?>
        <a href="mailto:info@paycal.app">info@paycal.app</a>.
      </p>
    </section>

    <!-- Data Export Problems -->
    <section id="article-export" class="doc-section">
      <h2><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_EXPORT_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p>
        <?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_EXPORT_PREFIX'), ENT_QUOTES, 'UTF-8'); ?>
        <a href="/earnings/"><?php echo htmlspecialchars(help_ts_i18n('EARNINGS'), ENT_QUOTES, 'UTF-8'); ?></a>
        <?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_EXPORT_SUFFIX'), ENT_QUOTES, 'UTF-8'); ?>
      </p>

      <h3><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_DOWNLOAD_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_DOWNLOAD_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_DOWNLOAD_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_DOWNLOAD_3'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>

      <h3><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_EXPORT_EMPTY_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_EXPORT_EMPTY_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_EXPORT_EMPTY_2'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>

      <h3><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_STILL_NOT_WORKING_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p>
        <?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_STILL_NOT_WORKING_PREFIX'), ENT_QUOTES, 'UTF-8'); ?>
        <a href="mailto:info@paycal.app">info@paycal.app</a>
        <?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_STILL_NOT_WORKING_SUFFIX'), ENT_QUOTES, 'UTF-8'); ?>
      </p>
    </section>

    <!-- Contact Support -->
    <section id="article-contact" class="doc-section">
      <h2><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_CONTACT_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_CONTACT_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
      <ul class="doc-fact-list">
        <li>
          <?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_CONTACT_EMAIL_LABEL'), ENT_QUOTES, 'UTF-8'); ?>
          <a href="mailto:info@paycal.app">info@paycal.app</a>
        </li>
        <li>
          <?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_CONTACT_FORM_LABEL'), ENT_QUOTES, 'UTF-8'); ?>
          <a href="/contact/"><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_CONTACT_FORM_LINK'), ENT_QUOTES, 'UTF-8'); ?></a>
        </li>
      </ul>
      <p><?php echo htmlspecialchars(help_ts_i18n('HELP_TROUBLESHOOTING_CONTACT_NOTE'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>

  </div><!-- .doc-article-body -->
</article>

<?php
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('transparency') . '">' . PHP_EOL;
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('help') . '">' . PHP_EOL;
echo PHP_EOL . Render::jsScript('help');
require_once HTML . '/footer.php';
?>
