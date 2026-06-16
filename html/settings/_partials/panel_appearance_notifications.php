<?php declare(strict_types=1);

namespace PayCal\Domain;

$helpPopupTimeout = $user->getHelpPopupTimeoutSeconds();
$toastPosition = $user->getToastPosition();
$toastFontSize = $user->getToastFontSize();
$toastFontSizeLabel = $formatSliderStepDisplayPx($toastFontSize, 1.125, 0.75);

$toastPositions = [
  'upper-left',
  'upper-center',
  'upper-right',
  'lower-left',
  'lower-center',
  'lower-right',
  'full-top',
  'full-bottom',
];

?>
<section class="panel settings_card_group" id="panel-appearance-notifications">
  <form id="account_notifications_form" method="POST" action="<?php echo Environment::appURL('api/v1/settings/style/update/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_APPEARANCE_NOTIFICATIONS_FORM_ARIA'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_APPEARANCE_NOTIFICATIONS_TITLE'); ?></h2>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_TOAST_POSITION_LABEL'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group settings_toast_position_grid" role="radiogroup" aria-label="<?php echo settings_index_i18n('SETTINGS_TOAST_POSITION_LABEL'); ?>" data-hover-help="<?php echo settings_index_i18n('SETTINGS_TOAST_POSITION_HOVER'); ?>">
          <?php foreach ($toastPositions as $position) {
            $positionKey = strtoupper(str_replace('-', '_', $position));
            ?>
            <input class="radio" type="radio" id="toast_position_<?php echo $position; ?>" name="toast_position" value="<?php echo $position; ?>"<?php if ($toastPosition === $position) {
              echo ' checked';
            } ?>>
            <label for="toast_position_<?php echo $position; ?>"><?php echo settings_index_i18n('SETTINGS_TOAST_POSITION_' . $positionKey); ?></label>
          <?php } ?>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label for="help_popup_timeout_seconds" class="w25"><?php echo settings_index_i18n('SETTINGS_DURATION_LABEL'); ?></label>
      <div class="w75">
        <div class="proximity_slider_wrap" data-hover-help="<?php echo settings_index_i18n('SETTINGS_HELP_POPUP_TIMEOUT_HOVER'); ?>">
          <input
            type="range"
            id="help_popup_timeout_seconds"
            name="help_popup_timeout_seconds"
            min="0"
            max="30"
            step="1"
            value="<?php echo $helpPopupTimeout; ?>"
            aria-valuemin="0"
            aria-valuemax="30"
            aria-valuenow="<?php echo $helpPopupTimeout; ?>"
            aria-label="<?php echo settings_index_i18n('SETTINGS_DURATION_LABEL'); ?>"
          >
          <output for="help_popup_timeout_seconds" id="help_popup_timeout_seconds_output"><?php echo $helpPopupTimeout; ?>s</output>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label for="toast_font_size" class="w25"><?php echo settings_index_i18n('SETTINGS_TOAST_SIZE_LABEL'); ?></label>
      <div class="w75">
        <div class="proximity_slider_wrap" data-hover-help="<?php echo settings_index_i18n('SETTINGS_TOAST_SIZE_HOVER'); ?>">
          <input
            type="range"
            id="toast_font_size"
            name="toast_font_size"
            min="-5"
            max="5"
            step="1"
            value="<?php echo $toastFontSize; ?>"
            aria-valuemin="-5"
            aria-valuemax="5"
            aria-valuenow="<?php echo $toastFontSize; ?>"
            aria-label="<?php echo settings_index_i18n('SETTINGS_TOAST_SIZE_LABEL'); ?>"
          >
          <output for="toast_font_size" id="toast_font_size_output"><?php echo htmlspecialchars($toastFontSizeLabel, ENT_QUOTES, 'UTF-8'); ?></output>
        </div>
      </div>
    </div>

  </form>
</section>
