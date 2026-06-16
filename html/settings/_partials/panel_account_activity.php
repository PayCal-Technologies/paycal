<?php declare(strict_types=1);

namespace PayCal\Domain;

?>

  <section class="panel" id="panel-account-activity" aria-labelledby="panel-account-activity-heading" data-hover-help="<?php echo settings_index_i18n('PROFILE_ACCOUNT_ACTIVITY_PANEL_HELP'); ?>">
    <div class="businesses_section_header">
      <div>
        <h2 id="panel-account-activity-heading"><?php echo settings_index_i18n('PROFILE_ACCOUNT_ACTIVITY_TITLE'); ?></h2>
        <p class="help_text"><?php echo settings_index_i18n('PROFILE_ACCOUNT_ACTIVITY_INTRO'); ?></p>
      </div>
    </div>

    <div id="account_activity_status" class="status_text compact_hint" role="status" aria-live="polite"></div>

    <div class="account_activity_grid">
      <section class="account_activity_card" aria-label="<?php echo settings_index_i18n('PROFILE_ACCOUNT_ACTIVITY_LOGIN_ARIA'); ?>">
        <h3><?php echo settings_index_i18n('PROFILE_ACCOUNT_ACTIVITY_H3_CURRENT_LOGIN'); ?></h3>
        <dl id="account_activity_login_details" class="account_activity_list"></dl>
      </section>

      <section class="account_activity_card" aria-label="<?php echo settings_index_i18n('PROFILE_ACCOUNT_ACTIVITY_BROWSER_ARIA'); ?>">
        <h3><?php echo settings_index_i18n('PROFILE_ACCOUNT_ACTIVITY_H3_BROWSER_DETAILS'); ?></h3>
        <dl id="account_activity_browser_details" class="account_activity_list"></dl>
      </section>

      <section class="account_activity_card account_activity_card_sessions" aria-label="<?php echo settings_index_i18n('PROFILE_ACCOUNT_ACTIVITY_ACTIVE_SESSIONS_ARIA'); ?>">
        <h3><?php echo settings_index_i18n('PROFILE_ACCOUNT_ACTIVITY_H3_ACTIVE_SESSIONS'); ?></h3>
        <div id="account_activity_sessions" class="account_activity_sessions"></div>
      </section>
    </div>
  </section>
