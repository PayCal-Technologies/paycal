<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
<section class="panel settings_card_group" id="panel-accessibility-typography">
  <form id="account_typography_form" method="POST" action="<?php echo Environment::appURL('api/v1/settings/style/update/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_ACCESSIBILITY_TYPOGRAPHY_FORM_ARIA'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_STYLE_LABEL_TYPOGRAPHY'); ?></h2>
    <div class="flex f_baseline w100">
      <label class="w25 visually_hidden"><?php echo settings_index_i18n('SETTINGS_STYLE_LABEL_TYPOGRAPHY'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" data-hover-help="Use a dyslexia-friendly type stack with roomier spacing across the interface.">
          <input class="radio" type="radio" id="dyslexia_typography_off" name="dyslexia_typography" value="off" <?php if ('off' === strtolower((string) ($user->dyslexia_typography ?? UserPreferenceDefaults::DEFAULT_DYSLEXIA_TYPOGRAPHY))) {
            echo 'checked';
          } ?>>
          <label for="dyslexia_typography_off"><?php echo settings_index_i18n('SETTINGS_TYPOGRAPHY_STANDARD'); ?></label>
          <input class="radio" type="radio" id="dyslexia_typography_on" name="dyslexia_typography" value="on" <?php if ('off' !== strtolower((string) ($user->dyslexia_typography ?? UserPreferenceDefaults::DEFAULT_DYSLEXIA_TYPOGRAPHY))) {
            echo 'checked';
          } ?>>
          <label for="dyslexia_typography_on"><?php echo settings_index_i18n('SETTINGS_TYPOGRAPHY_DYSLEXIA_FRIENDLY'); ?></label>
        </div>
      </div>
    </div>
  </form>
</section>
