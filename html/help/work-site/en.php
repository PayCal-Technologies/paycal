<?php
/**
 * Help: Setup a Work Site — English
 *
 * PURPOSE: Standalone article covering work site creation. Part of the Help
 *          article system using doc-article / doc-breadcrumb layout matching
 *          the /transparency article style.
 * LOCATION: html/help/work-site/en.php
 */

declare(strict_types=1);

use PayCal\Domain\Render;
use PayCal\Domain\Strings;

require_once __DIR__ . '/../../config.php';

if (function_exists('help_ws_i18n') === false) {
  function help_ws_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }
    return $cache[$key];
  }
}

$currentPage = 'PAGE_HELP';
$pageTitle = help_ws_i18n('HELP_WORK_SITE_TITLE') . ' - [' . help_ws_i18n('SITE_NAME') . ']';
$pageLabel = help_ws_i18n('HELP_WORK_SITE_TITLE');

require_once HTML . '/header.php';
?>

<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo htmlspecialchars(help_ws_i18n('HELP_BREADCRUMB_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
    <a href="/"><?php echo htmlspecialchars(help_ws_i18n('HOME'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <a href="/help/"><?php echo htmlspecialchars(help_ws_i18n('HELP_CENTER_TITLE'), ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_TITLE'), ENT_QUOTES, 'UTF-8'); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck"><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
  </header>

  <div class="doc-article-body">

    <section id="article-work-site" class="doc-section">
      <h2><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_WHY_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_WHY_TEXT'), ENT_QUOTES, 'UTF-8'); ?></p>

      <figure class="help-figure">
        <button
          type="button"
          class="help-image-button"
          data-help-popover-open="work-site-setup-popover"
          aria-haspopup="true"
          aria-controls="work-site-setup-popover"
          aria-label="<?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_POPOVER_OPEN_ARIA'), ENT_QUOTES, 'UTF-8'); ?>"
        >
          <img
            class="help-image-thumb"
            src="/images/help/sites-create-dialog-focused.png"
            alt="<?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_THUMB_ALT'), ENT_QUOTES, 'UTF-8'); ?>"
            loading="lazy"
          >
        </button>
        <figcaption><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_FIGCAPTION'), ENT_QUOTES, 'UTF-8'); ?></figcaption>
      </figure>

      <div
        id="work-site-setup-popover"
        class="help-image-popover"
        popover="auto"
        aria-label="<?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_POPOVER_ARIA'), ENT_QUOTES, 'UTF-8'); ?>"
      >
        <div class="help-image-popover-card">
          <div class="help-image-popover-header">
            <h4><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_POPOVER_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h4>
            <button
              type="button"
              class="btn btn_secondary help-image-popover-close"
              data-help-popover-close="work-site-setup-popover"
              aria-label="<?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_POPOVER_CLOSE_ARIA'), ENT_QUOTES, 'UTF-8'); ?>"
            ><?php echo htmlspecialchars(help_ws_i18n('CLOSE'), ENT_QUOTES, 'UTF-8'); ?></button>
          </div>
          <img
            class="help-image-full"
            src="/images/help/sites-create-dialog-focused.png"
            alt="<?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_FULL_ALT'), ENT_QUOTES, 'UTF-8'); ?>"
            loading="lazy"
          >
        </div>
      </div>

      <h2><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_STEPS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <ol>
        <li><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_STEP_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_STEP_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_STEP_3'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_STEP_4'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_STEP_5'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ol>

      <h2><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_BEST_PRACTICES_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_BEST_PRACTICE_1'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_BEST_PRACTICE_2'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_BEST_PRACTICE_3'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>

      <h2><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_BEFORE_MOVING_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_SITE_BEFORE_MOVING_PREFIX'), ENT_QUOTES, 'UTF-8'); ?> <a href="/help/work-hours/"><?php echo htmlspecialchars(help_ws_i18n('HELP_WORK_HOURS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></a>.</p>
    </section>

  </div><!-- .doc-article-body -->
</article>

<?php
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('transparency') . '">' . PHP_EOL;
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('help') . '">' . PHP_EOL;
echo PHP_EOL . Render::jsScript('help');
require_once HTML . '/footer.php';
?>
