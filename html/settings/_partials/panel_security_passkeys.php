<?php declare(strict_types=1);

namespace PayCal\Domain;

$hasRecoveryKey = (bool) ($user->recovery_key_generated ?? false);
$recoveryKeyUpdatedRaw = trim((string) ($user->recovery_key_updated_at ?? ''));
$recoveryKeyUpdatedLabel = '';
if ($recoveryKeyUpdatedRaw !== '') {
  $recoveryKeyUpdatedTimestamp = strtotime($recoveryKeyUpdatedRaw);
  if ($recoveryKeyUpdatedTimestamp !== false && $recoveryKeyUpdatedTimestamp > 0) {
    $recoveryKeyUpdatedLabel = date('F j, Y', $recoveryKeyUpdatedTimestamp);
  }
}

?>
<section class="panel" id="panel-passkeys">
  <div class="w100">
    <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_SECTION_PASSKEYS'); ?></h2>

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

    <div class="settings_recovery_key_card" aria-labelledby="recovery_key_panel_title">
      <div class="settings_recovery_key_header">
        <div>
          <h3 id="recovery_key_panel_title" class="settings_recovery_key_title"><?php echo settings_index_i18n('SETTINGS_RECOVERY_KEY_TITLE'); ?></h3>
          <p id="recovery_key_help_text" class="settings_recovery_key_text"><?php echo settings_index_i18n($hasRecoveryKey ? 'SETTINGS_RECOVERY_KEY_HELP_TEXT' : 'SETTINGS_RECOVERY_KEY_MISSING_HELP_TEXT'); ?></p>
        </div>
        <div
          id="settings_recovery_key_badge"
          class="settings_recovery_key_badge"
          data-has-recovery-key="<?php echo $hasRecoveryKey ? '1' : '0'; ?>"
          hidden
        ><?php echo settings_index_i18n('SETTINGS_RECOVERY_KEY_ACTIVE_BADGE'); ?></div>
      </div>

      <dl class="settings_recovery_key_meta">
        <div>
          <dt><?php echo settings_index_i18n('SETTINGS_RECOVERY_KEY_STATUS_LABEL'); ?></dt>
          <dd
            id="settings_recovery_key_status_value"
            class="<?php echo $hasRecoveryKey ? 'is-active' : 'is-missing'; ?>"
          ><?php echo settings_index_i18n($hasRecoveryKey ? 'SETTINGS_RECOVERY_KEY_STATUS_ACTIVE' : 'SETTINGS_RECOVERY_KEY_STATUS_MISSING'); ?></dd>
        </div>
        <div>
          <dt><?php echo settings_index_i18n('SETTINGS_RECOVERY_KEY_UPDATED_LABEL'); ?></dt>
          <dd id="settings_recovery_key_updated_value"><?php echo htmlspecialchars($recoveryKeyUpdatedLabel !== '' ? $recoveryKeyUpdatedLabel : settings_index_i18n($hasRecoveryKey ? 'SETTINGS_RECOVERY_KEY_UPDATED_UNKNOWN' : 'SETTINGS_RECOVERY_KEY_UPDATED_NEVER'), ENT_QUOTES, 'UTF-8'); ?></dd>
        </div>
      </dl>

      <div id="create_recovery_key_status" class="status_message status_message_callout recovery_key_status_callout" role="status" aria-live="polite" aria-atomic="true"></div>
      <div id="settings_recovery_code_once" class="settings_recovery_code_once" hidden>
        <p class="settings_recovery_code_once_help"><?php echo settings_index_i18n('SETTINGS_RECOVERY_KEY_DISPLAY_INSTRUCTION'); ?></p>
        <code id="settings_recovery_code_once_value" class="settings_recovery_code_once_value"></code>
        <div class="settings_recovery_code_once_actions">
          <button type="button" id="settings_recovery_code_copy_btn" class="btn btn_secondary"><?php echo settings_index_i18n('SETTINGS_RECOVERY_KEY_COPY_BUTTON'); ?></button>
          <button type="button" id="settings_recovery_code_download_btn" class="btn btn_secondary"><?php echo settings_index_i18n('SETTINGS_RECOVERY_KEY_DOWNLOAD_BUTTON'); ?></button>
        </div>
        <p id="settings_recovery_code_once_status" class="settings_recovery_code_once_status" role="status" aria-live="polite"></p>
      </div>
      <div class="settings_recovery_key_actions">
        <button
          id="create_recovery_key_btn"
          type="button"
          class="btn btn_secondary"
          aria-describedby="recovery_key_help_text create_recovery_key_status"
          data-has-recovery-key="<?php echo $hasRecoveryKey ? '1' : '0'; ?>"
          data-create-label="<?php echo htmlspecialchars(settings_index_i18n('SETTINGS_RECOVERY_KEY_CREATE_BUTTON'), ENT_QUOTES, 'UTF-8'); ?>"
          data-regenerate-label="<?php echo htmlspecialchars(settings_index_i18n('SETTINGS_RECOVERY_KEY_REGENERATE_BUTTON'), ENT_QUOTES, 'UTF-8'); ?>"
          data-active-status="<?php echo htmlspecialchars(settings_index_i18n('SETTINGS_RECOVERY_KEY_STATUS_ACTIVE'), ENT_QUOTES, 'UTF-8'); ?>"
          data-replace-confirm="<?php echo htmlspecialchars(settings_index_i18n('SETTINGS_RECOVERY_KEY_REGENERATE_CONFIRM'), ENT_QUOTES, 'UTF-8'); ?>"
          data-hover-help="<?php echo settings_index_i18n('SETTINGS_RECOVERY_KEY_BUTTON_HOVER'); ?>"
        ><?php echo settings_index_i18n($hasRecoveryKey ? 'SETTINGS_RECOVERY_KEY_REGENERATE_BUTTON' : 'SETTINGS_RECOVERY_KEY_CREATE_BUTTON'); ?></button>
      </div>
    </div>
  </div>
</section>
