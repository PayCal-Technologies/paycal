<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
<section class="panel settings_card_group" id="panel-payperiod">
  <form id="settings_pay_period_form" method="POST" action="<?php echo Environment::appURL('api/v1/settings/pay_period/update/'); ?>" aria-label="<?php echo settings_index_i18n('PAY_SETTINGS_FORM'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('PAY_PERIOD'); ?></h2>
    <p class="help_text"><?php echo settings_index_i18n('EDITING_GRACE_DAYS_HELP'); ?></p>
    <div id="pay_period_current_preview" class="help_text centered" aria-live="polite">&nbsp;</div>
    <div id="pay_period_current_calendar" class="pay_period_preview_calendar pay_period_preview_compact"></div>
    <div class="flex centered w100">
      <button id="pay_period_generate" type="button" class="btn btn_primary"><?php echo settings_index_i18n('UPDATE'); ?></button>
    </div>
  </form>
</section>
