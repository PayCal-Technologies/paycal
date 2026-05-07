<?php
/**
 * Help: Account & Settings — English
 *
 * PURPOSE: Standalone article covering profile management, themes, preferences,
 *          and security settings. Part of the Help article system using
 *          doc-article / doc-breadcrumb layout.
 * LOCATION: html/help/account-settings/en.php
 */

declare(strict_types=1);

use PayCal\Domain\Render;
use PayCal\Domain\Strings;

require_once __DIR__ . '/../../config.php';

if (function_exists('help_as_i18n') === false) {
  function help_as_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }
    return $cache[$key];
  }
}

$currentPage = 'PAGE_HELP';
$pageTitle = help_as_i18n('HELP_ACCOUNT_SETTINGS_TITLE') . ' - [' . help_as_i18n('SITE_NAME') . ']';
$pageLabel = help_as_i18n('HELP_ACCOUNT_SETTINGS_TITLE');

require_once HTML . '/header.php';
?>

<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo htmlspecialchars(help_as_i18n('HELP_BREADCRUMB_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
    <a href="/"><?php echo htmlspecialchars(help_as_i18n('HOME'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <a href="/help/"><?php echo htmlspecialchars(help_as_i18n('HELP_CENTER_TITLE'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_SETTINGS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_SETTINGS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck"><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_SETTINGS_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
  </header>

  <div class="doc-article-body">

    <!-- Managing Your Profile -->
    <section id="article-profile" class="doc-section">
      <h2><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_PROFILE_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_PROFILE_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_RECOVERY_KEY_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_RECOVERY_KEY_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_STORED_DISPLAYED_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_STORED_DISPLAYED_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_STORED_DISPLAYED_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_STORED_DISPLAYED_3'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_STORED_DISPLAYED_4'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_STORED_DISPLAYED_5'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
    </section>

    <!-- Themes and Preferences -->
    <section id="article-themes" class="doc-section">
      <h2><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_THEMES_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_THEMES_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_THEME_VARIANT_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_THEME_VARIANT_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_LANGUAGE_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_LANGUAGE_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_TEXT_SPACING_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_TEXT_SPACING_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_TEXT_SPACING_2'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>

      <h3><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_NAV_POSITION_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_NAV_POSITION_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_AUDIO_FEEDBACK_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_AUDIO_FEEDBACK_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>

    <!-- Security and Privacy -->
    <section id="article-security" class="doc-section">
      <h2><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_SECURITY_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_SECURITY_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_PASSKEYS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_PASSKEYS_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
      <p><a class="doc-read-more" href="/help/webauthn-security/"><?php echo htmlspecialchars(help_as_i18n('HELP_TOC_WEBAUTHN_SECURITY'), ENT_QUOTES, 'UTF-8'); ?></a></p>

      <h3><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_SECURITY_LEVEL_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_SECURITY_LEVEL_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
      <p><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_SECURITY_LEVEL_INTRO'), ENT_QUOTES, 'UTF-8'); ?></p>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_SECURITY_LEVEL_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_SECURITY_LEVEL_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_SECURITY_LEVEL_3'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>

      <h3><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_EMERGENCY_SIGNOUT_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_as_i18n('HELP_ACCOUNT_EMERGENCY_SIGNOUT_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>

  </div><!-- .doc-article-body -->
</article>

<?php
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('transparency') . '">' . PHP_EOL;
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('help') . '">' . PHP_EOL;
echo PHP_EOL . Render::jsScript('help');
require_once HTML . '/footer.php';
?>
