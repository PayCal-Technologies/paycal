<?php declare(strict_types=1);

namespace PayCal\Domain;

$earlyAccessState = EarlyAccessImmediateUi::settingsState($user);
$immediateUiChecked = $earlyAccessState['enrolled'];
$immediateUiDisabled = !$earlyAccessState['can_enable'] || !$earlyAccessState['runtime_enabled'];
$statusText = $immediateUiChecked
  ? settings_index_i18n('SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_ENABLED')
  : settings_index_i18n('SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_DISABLED');

if (!$earlyAccessState['runtime_enabled']) {
  $statusText = settings_index_i18n('SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_TEMP_UNAVAILABLE');
} elseif (!$earlyAccessState['has_passkey']) {
  $statusText = settings_index_i18n('SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_CREATE_PASSKEY');
} elseif (!$earlyAccessState['enrollment_enabled'] && !$immediateUiChecked) {
  $statusText = settings_index_i18n('SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_ENROLLMENT_CLOSED');
}
?>
<section class="panel settings_card_group settings_early_access_panel" id="panel-early-access">
  <h2 class="heading-accent settings_card_title"><?php echo htmlspecialchars(settings_index_i18n('SETTINGS_EARLY_ACCESS_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
  <p class="help_text"><?php echo htmlspecialchars(settings_index_i18n('SETTINGS_EARLY_ACCESS_INTRO'), ENT_QUOTES, 'UTF-8'); ?></p>

  <article
    class="settings_early_access_card"
    data-early-access-feature="auth.immediate_ui"
    data-immediate-ui-can-enable="<?php echo $earlyAccessState['can_enable'] ? '1' : '0'; ?>"
    data-immediate-ui-runtime="<?php echo $earlyAccessState['runtime_enabled'] ? '1' : '0'; ?>"
  >
    <div class="settings_early_access_switch_column">
      <label class="settings_switch">
        <input
          id="early_access_immediate_ui_switch"
          type="checkbox"
          role="switch"
          aria-describedby="early_access_immediate_ui_metadata early_access_immediate_ui_details_copy early_access_immediate_ui_status"
          <?php echo $immediateUiChecked ? ' checked' : ''; ?>
          <?php echo $immediateUiDisabled ? ' disabled' : ''; ?>
        >
        <span class="settings_switch_track" aria-hidden="true"></span>
        <span class="settings_switch_label"><?php echo htmlspecialchars(settings_index_i18n('SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_SWITCH'), ENT_QUOTES, 'UTF-8'); ?></span>
      </label>
    </div>

    <div class="settings_early_access_body">
      <p class="settings_early_access_sentence"><?php echo htmlspecialchars(settings_index_i18n('SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_DESC'), ENT_QUOTES, 'UTF-8'); ?></p>
      <p id="early_access_immediate_ui_metadata" class="settings_early_access_metadata"><?php echo htmlspecialchars(settings_index_i18n('SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_METADATA'), ENT_QUOTES, 'UTF-8'); ?></p>

      <div class="settings_early_access_actions">
        <details class="settings_early_access_details">
          <summary><?php echo htmlspecialchars(settings_index_i18n('SETTINGS_EARLY_ACCESS_DETAILS'), ENT_QUOTES, 'UTF-8'); ?></summary>
          <p id="early_access_immediate_ui_details_copy"><?php echo htmlspecialchars(settings_index_i18n('SETTINGS_EARLY_ACCESS_IMMEDIATE_UI_DETAILS'), ENT_QUOTES, 'UTF-8'); ?></p>
          <p><a href="https://developer.chrome.com/docs/identity/immediate-ui-mode" rel="noopener noreferrer" target="_blank"><?php echo htmlspecialchars(settings_index_i18n('SETTINGS_EARLY_ACCESS_LEARN_CHROME'), ENT_QUOTES, 'UTF-8'); ?></a></p>
        </details>
        <button
          type="button"
          class="settings_early_access_feedback_link"
          data-signal-open
          data-signal-topic="Faster passkey sign-in feedback"
          data-signal-tags="early-access, auth.immediate_ui, version-<?php echo htmlspecialchars((string) $earlyAccessState['version'], ENT_QUOTES, 'UTF-8'); ?>"
          data-signal-notes="Feature: auth.immediate_ui&#10;Release version: <?php echo htmlspecialchars((string) $earlyAccessState['version'], ENT_QUOTES, 'UTF-8'); ?>&#10;&#10;"
        ><?php echo htmlspecialchars(settings_index_i18n('SETTINGS_EARLY_ACCESS_FEEDBACK'), ENT_QUOTES, 'UTF-8'); ?></button>
      </div>

      <p
        id="early_access_immediate_ui_status"
        class="status_message status_message_callout settings_early_access_status"
        role="status"
        aria-live="polite"
        aria-atomic="true"
      ><?php echo htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8'); ?></p>

      <?php if (!$earlyAccessState['has_passkey']) { ?>
        <p><a class="btn btn_secondary" href="/settings/security/#panel-passkeys"><?php echo htmlspecialchars(settings_index_i18n('SETTINGS_EARLY_ACCESS_CREATE_PASSKEY_LINK'), ENT_QUOTES, 'UTF-8'); ?></a></p>
      <?php } ?>
    </div>
  </article>
</section>
