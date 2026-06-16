<?php declare(strict_types=1);

namespace PayCal\Domain;

$highContrast = (string) ($user->high_contrast_enabled ?? UserPreferenceDefaults::DEFAULT_HIGH_CONTRAST_ENABLED);
$reducedMotion = (string) ($user->reduced_motion_enabled ?? UserPreferenceDefaults::DEFAULT_REDUCED_MOTION_ENABLED);

?>
<section class="panel settings_card_group" id="panel-accessibility-extras">
  <form id="account_accessibility_extras_form" method="POST" action="<?php echo Environment::appURL('api/v1/settings/style/update/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_ACCESSIBILITY_EXTRAS_ARIA'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_ACCESSIBILITY_EXTRAS_TITLE'); ?></h2>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_SR_VERBOSITY_LABEL'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" role="radiogroup" aria-label="<?php echo settings_index_i18n('SETTINGS_SR_VERBOSITY_LABEL'); ?>">
          <?php foreach (['minimal', 'standard', 'verbose'] as $level) { ?>
            <input type="radio" class="radio" id="sr_verbosity_<?php echo $level; ?>" name="sr_verbosity" value="<?php echo $level; ?>"<?php if ((string) ($user->sr_verbosity ?? 'standard') === $level) { echo ' checked'; } ?>>
            <label for="sr_verbosity_<?php echo $level; ?>"><?php echo settings_index_i18n('SETTINGS_SR_VERBOSITY_' . strtoupper($level)); ?></label>
          <?php } ?>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_KEYBOARD_SHORTCUTS_HINT_LABEL'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" role="radiogroup" aria-label="<?php echo settings_index_i18n('SETTINGS_KEYBOARD_SHORTCUTS_HINT_LABEL'); ?>">
          <?php foreach (['off', 'first_visit', 'always'] as $hint) { ?>
            <input type="radio" class="radio" id="keyboard_shortcuts_hint_<?php echo $hint; ?>" name="keyboard_shortcuts_hint" value="<?php echo $hint; ?>"<?php if ((string) ($user->keyboard_shortcuts_hint ?? 'first_visit') === $hint) { echo ' checked'; } ?>>
            <label for="keyboard_shortcuts_hint_<?php echo $hint; ?>"><?php echo settings_index_i18n('SETTINGS_KEYBOARD_SHORTCUTS_HINT_' . strtoupper($hint)); ?></label>
          <?php } ?>
        </div>
        <button type="button" id="settings_show_keyboard_shortcuts_btn" class="btn btn_secondary settings_voice_preview_btn"><?php echo settings_index_i18n('SETTINGS_KEYBOARD_SHORTCUTS_SHOW_BTN'); ?></button>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_HIGH_CONTRAST_LABEL'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" role="radiogroup" aria-label="<?php echo settings_index_i18n('SETTINGS_HIGH_CONTRAST_LABEL'); ?>">
          <input type="radio" class="radio" id="high_contrast_enabled_off" name="high_contrast_enabled" value="0"<?php if ($highContrast !== '1') { echo ' checked'; } ?>>
          <label for="high_contrast_enabled_off"><?php echo settings_index_i18n('OFF'); ?></label>
          <input type="radio" class="radio" id="high_contrast_enabled_on" name="high_contrast_enabled" value="1"<?php if ($highContrast === '1') { echo ' checked'; } ?>>
          <label for="high_contrast_enabled_on"><?php echo settings_index_i18n('ON'); ?></label>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_REDUCED_MOTION_LABEL'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" role="radiogroup" aria-label="<?php echo settings_index_i18n('SETTINGS_REDUCED_MOTION_LABEL'); ?>">
          <?php foreach (['off', 'on', 'system'] as $motion) { ?>
            <input type="radio" class="radio" id="reduced_motion_enabled_<?php echo $motion; ?>" name="reduced_motion_enabled" value="<?php echo $motion; ?>"<?php if ($reducedMotion === $motion) { echo ' checked'; } ?>>
            <label for="reduced_motion_enabled_<?php echo $motion; ?>"><?php echo settings_index_i18n('SETTINGS_REDUCED_MOTION_' . strtoupper($motion)); ?></label>
          <?php } ?>
        </div>
      </div>
    </div>
  </form>
</section>
