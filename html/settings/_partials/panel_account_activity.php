<?php declare(strict_types=1);

namespace PayCal\Domain;

?>

  <section class="panel" id="panel-account-activity" aria-labelledby="panel-account-activity-heading" data-hover-help="<?php echo settings_index_i18n('PROFILE_ACCOUNT_ACTIVITY_PANEL_HELP'); ?>">
    <div class="businesses_section_header">
      <div>
        <h2 id="panel-account-activity-heading" class="settings_card_title"><?php echo settings_index_i18n('PROFILE_ACCOUNT_ACTIVITY_TITLE'); ?></h2>
      </div>
    </div>

    <div id="account_activity_status" class="status_text compact_hint" role="status" aria-live="polite" hidden></div>
    <div id="account_activity_sessions" class="account_activity_sessions" role="list" aria-label="<?php echo settings_index_i18n('PROFILE_ACCOUNT_ACTIVITY_ACTIVE_SESSIONS_ARIA'); ?>"></div>
  </section>
