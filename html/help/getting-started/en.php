<?php
/**
 * Help: Getting Started — English
 *
 * PURPOSE: Standalone article covering pay period setup. Part of the Help
 *          article system using doc-article / doc-breadcrumb layout matching
 *          the /transparency article style.
 * LOCATION: html/help/getting-started/en.php
 */

declare(strict_types=1);

use PayCal\Domain\Render;
use PayCal\Domain\Strings;

require_once __DIR__ . '/../../config.php';

if (function_exists('help_gs_i18n') === false) {
  function help_gs_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }
    return $cache[$key];
  }
}

$currentPage = 'PAGE_HELP';
$pageTitle = help_gs_i18n('HELP_GETTING_STARTED_TITLE') . ' - [' . help_gs_i18n('SITE_NAME') . ']';
$pageLabel = help_gs_i18n('HELP_GETTING_STARTED_TITLE');

require_once HTML . '/header.php';
?>

<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo htmlspecialchars(help_gs_i18n('HELP_BREADCRUMB_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
    <a href="/"><?php echo htmlspecialchars(help_gs_i18n('HOME'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <a href="/help/"><?php echo htmlspecialchars(help_gs_i18n('HELP_CENTER_TITLE'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo htmlspecialchars(help_gs_i18n('HELP_GETTING_STARTED_TITLE'), ENT_QUOTES, 'UTF-8'); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars(help_gs_i18n('HELP_GETTING_STARTED_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck"><?php echo htmlspecialchars(help_gs_i18n('HELP_GETTING_STARTED_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
  </header>

  <div class="doc-article-body">

    <section id="article-payperiods" class="doc-section">
      <h2><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_INTRO'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_GATHER_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_GATHER_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_GATHER_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_GATHER_3'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_GATHER_4'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>

      <h3><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_RECOMMENDED_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_RECOMMENDED_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <figure class="help-figure">
        <button
          type="button"
          class="help-image-button"
          data-help-popover-open="pay-period-example-popover"
          aria-haspopup="true"
          aria-controls="pay-period-example-popover"
          aria-label="<?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_POPOVER_OPEN_ARIA'), ENT_QUOTES, 'UTF-8'); ?>"
        >
          <img
            class="help-image-thumb"
            src="/images/help/settings-pay-period-biweekly-monday-sunday.png"
            alt="<?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_THUMB_ALT'), ENT_QUOTES, 'UTF-8'); ?>"
            loading="lazy"
          >
        </button>
        <figcaption><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_FIGCAPTION'), ENT_QUOTES, 'UTF-8'); ?></figcaption>
      </figure>

      <div
        id="pay-period-example-popover"
        class="help-image-popover"
        popover="auto"
        aria-label="<?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_POPOVER_ARIA'), ENT_QUOTES, 'UTF-8'); ?>"
      >
        <div class="help-image-popover-card">
          <div class="help-image-popover-header">
            <h4><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_POPOVER_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h4>
            <button
              type="button"
              class="btn btn_secondary help-image-popover-close"
              data-help-popover-close="pay-period-example-popover"
              aria-label="<?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_POPOVER_CLOSE_ARIA'), ENT_QUOTES, 'UTF-8'); ?>"
            ><?php echo htmlspecialchars(help_gs_i18n('CLOSE'), ENT_QUOTES, 'UTF-8'); ?></button>
          </div>
          <img
            class="help-image-full"
            src="/images/help/settings-pay-period-biweekly-monday-sunday.png"
            alt="<?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_FULL_ALT'), ENT_QUOTES, 'UTF-8'); ?>"
            loading="lazy"
          >
        </div>
      </div>

      <h3><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_STEPS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <ol>
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_STEP_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_STEP_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_STEP_3'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_STEP_4'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_STEP_5'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_STEP_6'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_STEP_7'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_STEP_8'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ol>

      <h3><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_CHECK_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_CHECK_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_CHECK_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_CHECK_3'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>

      <h3><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_UNSURE_DATE_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_UNSURE_DATE_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_COMMON_MISTAKES_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_COMMON_MISTAKE_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_COMMON_MISTAKE_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_gs_i18n('HELP_PAY_PERIODS_COMMON_MISTAKE_3'), ENT_QUOTES, 'UTF-8'); ?></li>
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
