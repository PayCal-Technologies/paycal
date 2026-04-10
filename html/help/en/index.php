<?php declare(strict_types=1);

$current_page = 'PAGE_HELP';

require_once __DIR__.'/../../config.php';

if (function_exists('en_index_i18n') === false) {
  function en_index_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}

$sMessage = '&nbsp;';
$sPageTitle = en_index_i18n('HELP');
$sPageLabel = en_index_i18n('HELP');
$sPageLanguage = 'en';

Authentication::redirectHomeIfUnauthenticated();

?>

<section class='mar_md pad_md'>
  <h1 class='centered'><?php echo en_index_i18n('HELP'); ?> &amp; <?php echo en_index_i18n('CONTACT_US'); ?></h1>

  <p>
    <?php echo en_index_i18n('HELP_INTRO_PRIMARY_PREFIX'); ?> <em><?php echo en_index_i18n('HELP_INTRO_PRIMARY_EMPHASIS_1'); ?></em>.
    <?php echo en_index_i18n('HELP_INTRO_PRIMARY_MIDDLE'); ?>
    <em><?php echo en_index_i18n('HELP_INTRO_PRIMARY_EMPHASIS_2'); ?></em>.
  </p>
  <p>
    <?php echo en_index_i18n('HELP_INTRO_SECONDARY'); ?>
  </p>

  <section class="panel mar_md pad_md">
    <h2 class='section_header centered'><?php echo en_index_i18n('HELP_TOC_ARIA'); ?></h2>
    <ul role="list" class="centered">
      <li><a href="#keyboard-shortcuts"><?php echo en_index_i18n('KEYBOARD_SHORTCUTS'); ?></a></li>
      <li><a href="#video-tutorials"><?php echo en_index_i18n('VIDEO_TUTORIALS'); ?></a></li>
      <li><a href="#getting-started"><?php echo en_index_i18n('HELP_TOC_GETTING_STARTED'); ?></a></li>
      <li><a href="#detailed-guides"><?php echo en_index_i18n('DETAILED_GUIDES'); ?></a></li>
      <li><a href="#troubleshooting-faq"><?php echo en_index_i18n('HELP_TOC_TROUBLESHOOTING'); ?> &amp; <?php echo en_index_i18n('HELP_FAQ_SHORT'); ?></a></li>
      <li><a href="#contact-us"><?php echo en_index_i18n('CONTACT_US'); ?></a></li>
    </ul>
  </section>
?>
?>

  <section id="detailed-guides" class="panel mar_md pad_md">
    <h2 class="section_header centered"><?php echo en_index_i18n('DETAILED_GUIDES'); ?></h2>
    <p><?php echo en_index_i18n('HELP_DETAILED_GUIDES_INTRO'); ?></p>
    <ul class="centered">
      <li><a href="<?php echo \PayCal\Domain\Environment::appURL('help/en/calendar.html'); ?>"><?php echo en_index_i18n('CALENDAR_HELP_LINK'); ?></a> - <?php echo en_index_i18n('HELP_DETAILED_GUIDES_CALENDAR_DESC'); ?></li>
      <li><a href="<?php echo \PayCal\Domain\Environment::appURL('help/en/sites.html'); ?>"><?php echo en_index_i18n('HELP_SITES_MANAGEMENT_LINK'); ?></a> - <?php echo en_index_i18n('HELP_DETAILED_GUIDES_SITES_DESC'); ?></li>
      <li><a href="<?php echo \PayCal\Domain\Environment::appURL('help/en/earnings.html'); ?>"><?php echo en_index_i18n('EARNINGS_HELP_LINK'); ?></a> - <?php echo en_index_i18n('HELP_DETAILED_GUIDES_EARNINGS_DESC'); ?></li>
    </ul>
  </section>
?>
?>

  <section id="keyboard-shortcuts" class="panel mar_md pad_md">
    <h2 class="section_header centered"><?php echo en_index_i18n('KEYBOARD_SHORTCUTS'); ?></h2>
    <p><?php echo en_index_i18n('HELP_KEYBOARD_SHORTCUTS_INTRO'); ?></p>
    <section class="flex">
$aRenders = [
'__KEYBOARD_SHORTCUTS__' => en_index_i18n('KEYBOARD_SHORTCUTS'),
'__OPEN_CALENDAR_WITH__' => en_index_i18n('OPEN_CALENDAR_WITH'),
'__ALT_C__' => en_index_i18n('ALT_C'),
'__OPEN_CALENDAR__' => en_index_i18n('OPEN_CALENDAR'),
'__OPEN_EARNINGS_WITH__' => en_index_i18n('OPEN_EARNINGS_WITH'),
'__ALT_R__' => en_index_i18n('ALT_R'),
'__OPEN_EARNINGS__' => en_index_i18n('OPEN_EARNINGS'),
'__OPEN_ACCOUNT_WITH__' => en_index_i18n('OPEN_ACCOUNT_WITH'),
'__ALT_A__' => en_index_i18n('ALT_A'),
'__OPEN_ACCOUNT__' => en_index_i18n('OPEN_ACCOUNT'),
'__OPEN_ABOUT_WITH__' => en_index_i18n('OPEN_ABOUT_WITH'),
'__ALT_B__' => en_index_i18n('ALT_B'),
'__OPEN_ABOUT__' => en_index_i18n('OPEN_ABOUT'),
'__OPEN_POLICIES_WITH__' => en_index_i18n('OPEN_POLICIES_WITH'),
'__ALT_P__' => en_index_i18n('ALT_P'),
'__OPEN_POLICIES__' => en_index_i18n('OPEN_POLICIES'),
'__OPEN_SHORTCUTS_WITH__' => en_index_i18n('OPEN_SHORTCUTS_WITH'),
'__QUESTION_MARK_KEY__' => en_index_i18n('QUESTION_MARK_KEY'),
'__OPEN_NUMBERED_TAB_WITH__' => en_index_i18n('OPEN_NUMBERED_TAB_WITH'),
'__OPEN_NUMBERED_TAB__' => en_index_i18n('OPEN_NUMBERED_TAB'),
'__NUMBERED__' => en_index_i18n('NUMBERED'),
'__OPEN_SHORTCUTS__' => en_index_i18n('OPEN_SHORTCUTS'),
'__KEYBOARD_SHORTCUTS_SYSTEM_TITLE__' => en_index_i18n('KEYBOARD_SHORTCUTS_SYSTEM_TITLE'),
'__KEYBOARD_SHORTCUTS_OPEN_SHORTCUTS_KEYS_HTML__' => en_index_i18n('KEYBOARD_SHORTCUTS_OPEN_SHORTCUTS_KEYS_HTML'),
'__KEYBOARD_SHORTCUTS_SAFEGUARDS_ARIA__' => en_index_i18n('KEYBOARD_SHORTCUTS_SAFEGUARDS_ARIA'),
'__KEYBOARD_SHORTCUTS_SAFEGUARDS_TITLE__' => en_index_i18n('KEYBOARD_SHORTCUTS_SAFEGUARDS_TITLE'),
'__KEYBOARD_SHORTCUTS_SAFEGUARDS_TEXT__' => en_index_i18n('KEYBOARD_SHORTCUTS_SAFEGUARDS_TEXT'),
'__CALENDAR__' => en_index_i18n('CALENDAR'),
'__KEYBOARD_SHORTCUTS_TOGGLE_SIDEBAR_ARIA__' => en_index_i18n('KEYBOARD_SHORTCUTS_TOGGLE_SIDEBAR_ARIA'),
'__KEYBOARD_SHORTCUTS_TOGGLE_SIDEBAR_LABEL__' => en_index_i18n('KEYBOARD_SHORTCUTS_TOGGLE_SIDEBAR_LABEL'),
'__OPEN_DIALOG_WITH__' => en_index_i18n('OPEN_DIALOG_WITH'),
'__ENTER_KEY__' => en_index_i18n('ENTER_KEY'),
'__OPEN_DIALOG__' => en_index_i18n('OPEN_DIALOG'),
'__CLOSE_DIALOG_WITH__' => en_index_i18n('CLOSE_DIALOG_WITH'),
'__ESCAPE_KEY__' => en_index_i18n('ESCAPE_KEY'),
'__CLOSE_DIALOG__' => en_index_i18n('CLOSE_DIALOG'),
'__TOGGLE_CALENDAR_SCREENMODE_WITH__' => en_index_i18n('TOGGLE_CALENDAR_SCREENMODE_WITH'),
'__TOGGLE_CALENDAR_SCREENMODE__' => en_index_i18n('TOGGLE_CALENDAR_SCREENMODE'),
'__TILDE_KEY__' => en_index_i18n('TILDE_KEY'),
'__OPEN_DATE_PICKER_WITH__' => en_index_i18n('OPEN_DATE_PICKER_WITH'),
'__ALT_BACKSLASH__' => en_index_i18n('ALT_BACKSLASH'),
'__OPEN_DATE_PICKER__' => en_index_i18n('OPEN_DATE_PICKER'),
'__NAVIGATE_WITH__' => en_index_i18n('NAVIGATE_WITH'),
'__TAB_KEY__' => en_index_i18n('TAB_KEY'),
'__ARROW_KEYS__' => en_index_i18n('ARROW_KEYS'),
'__HOME_KEY__' => en_index_i18n('HOME_KEY'),
'__END_KEY__' => en_index_i18n('END_KEY'),
'__NAVIGATE__' => en_index_i18n('NAVIGATE'),
'__NEXT_PREV_CALENDAR_MONTH_WITH__' => en_index_i18n('NEXT_PREV_CALENDAR_MONTH_WITH'),
'__NEXT_PREV_BRACKETS__' => en_index_i18n('NEXT_PREV_BRACKETS'),
'__NEXT_PREV_PAGEKEYS__' => en_index_i18n('NEXT_PREV_PAGEKEYS'),
'__NEXT_PREV_CALENDAR_MONTH__' => en_index_i18n('NEXT_PREV_CALENDAR_MONTH'),
'__COPY_WORK_WITH__' => en_index_i18n('COPY_WORK_WITH'),
'__COPY_WORK__' => en_index_i18n('COPY_WORK'),
'__CTRL_C__' => en_index_i18n('CTRL_C'),
'__PASTE_WORK_WITH__' => en_index_i18n('PASTE_WORK_WITH'),
'__PASTE_WORK__' => en_index_i18n('PASTE_WORK'),
'__CTRL_V__' => en_index_i18n('CTRL_V'),
'__DELETE_WORK_WITH__' => en_index_i18n('DELETE_WORK_WITH'),
'__DELETE_KEY__' => en_index_i18n('DELETE_KEY'),
'__DELETE_WORK__' => en_index_i18n('DELETE_WORK'),
'__KEYBOARD_SHORTCUTS_GOT_IT__' => en_index_i18n('KEYBOARD_SHORTCUTS_GOT_IT'),
'__HELP_PAGE_TEASER__' => en_index_i18n('HELP_PAGE_TEASER')
];

echo Render::template('keyboard-shortcuts-help-list', $aRenders);

?>
    </section>
?>
?>
  </section>
?>
?>

  <section id="video-tutorials" class="panel mar_md pad_md">
    <h2 class="section_header centered"><?php echo en_index_i18n('VIDEO_TUTORIALS'); ?></h2>
    <div class="flex f_space_around f_wrap">
      <div class="w50 f_column pad_md centered">
        <h3><?php echo en_index_i18n('HELP_VIDEO_LOGGING_FIRST_HOURS_TITLE'); ?></h3>
        <div class="video-placeholder">
          <?php echo en_index_i18n('HELP_VIDEO_PLACEHOLDER_1'); ?>
          <!-- Example: <iframe width="560" height="315" src="https://www.youtube.com/embed/dQw4w9WgXcQ" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe> -->
        </div>
        <p><?php echo en_index_i18n('HELP_VIDEO_LOGGING_FIRST_HOURS_DESC'); ?></p>
      </div>
      <div class="w50 f_column pad_md centered">
        <h3><?php echo en_index_i18n('HELP_VIDEO_MANAGING_MULTIPLE_SITES_TITLE'); ?></h3>
        <div class="video-placeholder">
          <?php echo en_index_i18n('HELP_VIDEO_PLACEHOLDER_2'); ?>
        </div>
        <p><?php echo en_index_i18n('HELP_VIDEO_MANAGING_MULTIPLE_SITES_DESC'); ?></p>
      </div>
      <div class="w50 f_column pad_md centered">
        <h3><?php echo en_index_i18n('HELP_VIDEO_UNDERSTANDING_EARNINGS_TITLE'); ?></h3>
        <div class="video-placeholder">
          <?php echo en_index_i18n('HELP_VIDEO_PLACEHOLDER_3'); ?>
        </div>
        <p><?php echo en_index_i18n('HELP_VIDEO_UNDERSTANDING_EARNINGS_DESC'); ?></p>
      </div>
    </div>
    <p class="centered"><a href="https://www.youtube.com/@PayCalApp/videos" target="_blank" rel="noopener noreferrer"><?php echo en_index_i18n('HELP_VIDEO_VIEW_ALL_YOUTUBE'); ?></a></p>
  </section>
?>
?>

  <section id="getting-started" class=" panel mar_md pad_md">
    <h2 class="section_header centered"><?php echo en_index_i18n('HELP_GETTING_STARTED_TITLE'); ?></h2>
    <p><?php echo en_index_i18n('HELP_GETTING_STARTED_INTRO'); ?></p>
    <ol>
      <li><strong><?php echo en_index_i18n('HELP_GETTING_STARTED_STEP_1_TITLE'); ?></strong> <?php echo en_index_i18n('HELP_GETTING_STARTED_STEP_1_PREFIX'); ?> <a href="/auth/?auth_tab=register"><?php echo en_index_i18n('HELP_GETTING_STARTED_REGISTRATION_PAGE'); ?></a> <?php echo en_index_i18n('HELP_GETTING_STARTED_STEP_1_SUFFIX'); ?></li>
      <li><strong><?php echo en_index_i18n('HELP_GETTING_STARTED_STEP_2_TITLE'); ?></strong> <?php echo en_index_i18n('HELP_GETTING_STARTED_STEP_2_TEXT'); ?></li>
      <li><strong><?php echo en_index_i18n('HELP_GETTING_STARTED_STEP_3_TITLE'); ?></strong> <?php echo en_index_i18n('HELP_GETTING_STARTED_STEP_3_PREFIX'); ?> <a href="/settings/#sites"><?php echo en_index_i18n('HELP_GETTING_STARTED_SETTINGS_SITES'); ?></a> <?php echo en_index_i18n('HELP_GETTING_STARTED_STEP_3_MIDDLE'); ?> <a href="<?php echo \PayCal\Domain\Environment::appURL('help/en/sites.html'); ?>"><?php echo en_index_i18n('HELP_GETTING_STARTED_SITES_HELP_PAGE'); ?></a>.</li>
      <li><strong><?php echo en_index_i18n('HELP_GETTING_STARTED_STEP_4_TITLE'); ?></strong> <?php echo en_index_i18n('HELP_GETTING_STARTED_STEP_4_PREFIX'); ?> <a href="/settings/#pay_period"><?php echo en_index_i18n('HELP_GETTING_STARTED_SETTINGS_PAY_PERIOD'); ?></a> <?php echo en_index_i18n('HELP_GETTING_STARTED_STEP_4_SUFFIX'); ?></li>
      <li><strong><?php echo en_index_i18n('HELP_GETTING_STARTED_STEP_5_TITLE'); ?></strong> <?php echo en_index_i18n('HELP_GETTING_STARTED_STEP_5_PREFIX'); ?> <a href="/"><?php echo en_index_i18n('HELP_GETTING_STARTED_CALENDAR_LINK'); ?></a> <?php echo en_index_i18n('HELP_GETTING_STARTED_STEP_5_MIDDLE'); ?> <a href="<?php echo \PayCal\Domain\Environment::appURL('help/en/calendar.html'); ?>"><?php echo en_index_i18n('HELP_GETTING_STARTED_CALENDAR_HELP_PAGE'); ?></a>.</li>
    </ol>
    <p><?php echo en_index_i18n('HELP_GETTING_STARTED_OUTRO_PREFIX'); ?> <a href="<?php echo \PayCal\Domain\Environment::appURL('help/en/earnings.html'); ?>"><?php echo en_index_i18n('HELP_GETTING_STARTED_EARNINGS_HELP_PAGE'); ?></a>. <?php echo en_index_i18n('HELP_GETTING_STARTED_OUTRO_SUFFIX_PREFIX'); ?> <a href="/settings/"><?php echo en_index_i18n('HELP_GETTING_STARTED_SETTINGS_LINK'); ?></a> <?php echo en_index_i18n('HELP_GETTING_STARTED_OUTRO_SUFFIX_END'); ?></p>
  </section>
?>
?>

  <section id="troubleshooting-faq" class="panel mar_md pad_md">
    <h2 class="section_header centered"><?php echo en_index_i18n('HELP_TROUBLESHOOTING_TITLE'); ?> &amp; <?php echo en_index_i18n('HELP_FAQ_SHORT'); ?></h2>
    <p><?php echo en_index_i18n('HELP_FAQ_INTRO_PREFIX'); ?> <a href="/v1/faq"><?php echo en_index_i18n('HELP_FAQ_PAGE_LINK'); ?></a> <?php echo en_index_i18n('HELP_FAQ_INTRO_SUFFIX'); ?></p>
    <details class="panel mar_md">
      <summary><strong><?php echo en_index_i18n('HELP_FAQ_Q1_TITLE'); ?></strong></summary>
      <p><?php echo en_index_i18n('HELP_FAQ_Q1_PREFIX'); ?> <a href="/v1/settings#sites"><?php echo en_index_i18n('HELP_GETTING_STARTED_SETTINGS_SITES'); ?></a>. <?php echo en_index_i18n('HELP_FAQ_Q1_MIDDLE'); ?> <a href="/v1/settings#pay_period"><?php echo en_index_i18n('HELP_GETTING_STARTED_SETTINGS_PAY_PERIOD'); ?></a>. <?php echo en_index_i18n('HELP_FAQ_Q1_SUFFIX'); ?></p>
    </details>
    <details class="panel mar_md">
      <summary><strong><?php echo en_index_i18n('HELP_FAQ_Q2_TITLE'); ?></strong></summary>
      <p><?php echo en_index_i18n('HELP_FAQ_Q2_TEXT'); ?></p>
    </details>
    <details class="panel mar_md">
      <summary><strong><?php echo en_index_i18n('HELP_FAQ_Q3_TITLE'); ?></strong></summary>
      <p><?php echo en_index_i18n('HELP_FAQ_Q3_PREFIX'); ?> <a href="/v1/settings#account"><?php echo en_index_i18n('HELP_FAQ_SETTINGS_ACCOUNT_INFO'); ?></a>. <?php echo en_index_i18n('HELP_FAQ_Q3_SUFFIX'); ?></p>
    </details>
    <details class="panel mar_md">
      <summary><strong><?php echo en_index_i18n('HELP_FAQ_Q4_TITLE'); ?></strong></summary>
      <p><?php echo en_index_i18n('HELP_FAQ_Q4_TEXT'); ?></p>
    </details>
  </section>
?>
?>

</section>
?>
?>




