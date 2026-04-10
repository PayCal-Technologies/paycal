<?php
/**
 * Help: Log Work Hours — English
 *
 * PURPOSE: Standalone article covering daily work entry on the calendar.
 *          Part of the Help article system using doc-article / doc-breadcrumb
 *          layout matching the /transparency article style.
 * LOCATION: html/help/work-hours/en.php
 */

declare(strict_types=1);

use PayCal\Domain\Render;
use PayCal\Domain\Strings;

require_once __DIR__ . '/../../config.php';

if (function_exists('help_wh_i18n') === false) {
  function help_wh_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }
    return $cache[$key];
  }
}

$currentPage = 'PAGE_HELP';
$pageTitle = help_wh_i18n('HELP_WORK_HOURS_TITLE') . ' - [' . help_wh_i18n('SITE_NAME') . ']';
$pageLabel = help_wh_i18n('HELP_WORK_HOURS_TITLE');

require_once HTML . '/header.php';
?>

<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo htmlspecialchars(help_wh_i18n('HELP_BREADCRUMB_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
    <a href="/"><?php echo htmlspecialchars(help_wh_i18n('HOME'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <a href="/help/"><?php echo htmlspecialchars(help_wh_i18n('HELP_CENTER_TITLE'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck"><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <figure class="help-figure">
        <button
          type="button"
          class="help-image-button"
          data-help-popover-open="work-hours-entry-surface-popover"
          aria-haspopup="true"
          aria-controls="work-hours-entry-surface-popover"
          aria-label="<?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_POPOVER_OPEN_ARIA'), ENT_QUOTES, 'UTF-8'); ?>"
        >
          <img
            class="help-image-thumb"
            src="/images/help/calendar-work-entry-edit-dialog-focused.png"
            alt="<?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_THUMB_ALT'), ENT_QUOTES, 'UTF-8'); ?>"
            loading="lazy"
          >
        </button>
        <figcaption><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_FIGCAPTION'), ENT_QUOTES, 'UTF-8'); ?></figcaption>
      </figure>

      <div
        id="work-hours-entry-surface-popover"
        class="help-image-popover"
        popover="auto"
        aria-label="<?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_POPOVER_ARIA'), ENT_QUOTES, 'UTF-8'); ?>"
      >
        <div class="help-image-popover-card">
          <div class="help-image-popover-header">
            <h4><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_POPOVER_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h4>
            <button
              type="button"
              class="btn btn_secondary help-image-popover-close"
              data-help-popover-close="work-hours-entry-surface-popover"
              aria-label="<?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_POPOVER_CLOSE_ARIA'), ENT_QUOTES, 'UTF-8'); ?>"
            ><?php echo htmlspecialchars(help_wh_i18n('CLOSE'), ENT_QUOTES, 'UTF-8'); ?></button>
          </div>
          <img
            class="help-image-full"
            src="/images/help/calendar-work-entry-edit-dialog-focused.png"
            alt="<?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_FULL_ALT'), ENT_QUOTES, 'UTF-8'); ?>"
            loading="lazy"
          >
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_OVERVIEW_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_OVERVIEW_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_GETTING_STARTED_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>

      <h3><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_START_STEP_1_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_START_STEP_1_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_START_STEP_2_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_START_STEP_2_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_START_STEP_3_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_START_STEP_3_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_FILLING_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>

      <h3><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_SITE_SELECTION_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_SITE_SELECTION_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_HOURS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_HOURS_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_OT_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_OT_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_LIVING_OUT_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_LIVING_OUT_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TRAVEL_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TRAVEL_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_SAVING_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_SAVING_TEXT_1'), ENT_QUOTES, 'UTF-8'); ?></p>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_SAVING_TEXT_2'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_VALIDATION_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_VALIDATION_INTRO'), ENT_QUOTES, 'UTF-8'); ?></p>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_VALIDATION_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_VALIDATION_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_VALIDATION_3'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_VALIDATION_4'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_VALIDATION_NOTE'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TIPS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TIP_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TIP_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TIP_3'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TIP_4'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TIP_5'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TROUBLESHOOTING_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>

      <h3><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TROUBLE_1_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TROUBLE_1_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TROUBLE_2_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TROUBLE_2_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TROUBLE_3_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TROUBLE_3_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <h3><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TROUBLE_4_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(help_wh_i18n('HELP_WORK_HOURS_TROUBLE_4_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>

  </div><!-- .doc-article-body -->
</article>

<?php
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('transparency') . '">' . PHP_EOL;
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('help') . '">' . PHP_EOL;
echo PHP_EOL . Render::jsScript('help');
require_once HTML . '/footer.php';
?>
