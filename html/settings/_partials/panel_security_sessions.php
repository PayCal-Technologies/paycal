<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
<section class="panel settings_card_group" id="panel-security-sessions">
  <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_SECURITY_SESSIONS_TITLE'); ?></h2>
  <p class="help_text"><?php echo settings_index_i18n('SETTINGS_SECURITY_SESSIONS_DESC'); ?></p>
  <ul id="settings_sessions_list" class="settings_sessions_list" aria-live="polite"></ul>
  <div class="flex centered w100">
    <button type="button" id="settings_revoke_other_sessions_btn" class="btn btn_secondary"><?php echo settings_index_i18n('SETTINGS_SECURITY_REVOKE_OTHER_SESSIONS'); ?></button>
  </div>
  <div id="settings_sessions_status" class="status_message" role="status" aria-live="polite"></div>
</section>
