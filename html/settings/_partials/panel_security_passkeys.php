<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
<section class="panel" id="panel-passkeys">
  <div class="w100">
    <h2 class="heading-accent"><?php echo settings_index_i18n('SETTINGS_SECTION_PASSKEYS'); ?></h2>

    <div
      id="settings_recovery_key_badge"
      class="settings_recovery_key_badge"
      data-has-recovery-key="<?php echo ($user->recovery_key_generated ?? false) ? '1' : '0'; ?>"
      hidden
    ><?php echo settings_index_i18n('SETTINGS_RECOVERY_KEY_ACTIVE_BADGE'); ?></div>

    <div id="security_passkeys_widget" class="security_status_widget" aria-live="polite">
      <div class="security_status_note"><?php echo settings_index_i18n('SETTINGS_PASSKEYS_SECURITY_NOTE'); ?></div>
      <div class="visually_hidden">
        <p id="passkey_credentials_sr_instructions"><?php echo settings_index_i18n('SETTINGS_PASSKEYS_SR_INSTRUCTIONS'); ?></p>
        <p id="passkey_credentials_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
      </div>
      <div id="passkey_credentials_list" class="passkey_credentials_list" data-hover-help="Passkeys are trusted devices. Remove lost ones and rename for clarity."></div>
      <div class="security_passkey_actions">
        <button id="add_passkey_button" type="button" class="btn btn_primary" data-hover-help="Add another passkey before replacing devices to avoid lockout."><?php echo settings_index_i18n('SETTINGS_ADD_DEVICE'); ?></button>
      </div>
      <div id="add_passkey_status" class="status_message status_message_callout passkey_action_status" role="status" aria-live="polite" aria-atomic="true"></div>
    </div>
  </div>
</section>
