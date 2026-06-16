<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
<section class="panel settings_card_group" id="panel-account">
  <form id="account_profile_summary_form" method="POST" action="<?php echo Environment::appURL('api/v1/account/info/update/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_ACCOUNT_PROFILE_SUMMARY_ARIA'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_ACCOUNT_PROFILE_TITLE'); ?></h2>
    <p class="help_text"><?php echo settings_index_i18n('SETTINGS_ACCOUNT_PROFILE_DESC'); ?></p>

    <div class="flex f_baseline w100">
      <label for="label_email" class="w25"><?php echo settings_index_i18n('EMAIL'); ?></label>
      <div class="flex f_baseline w75">
        <input type="text" id="label_email" name="email" value="<?php echo htmlspecialchars((string) $user->email, ENT_QUOTES, 'UTF-8'); ?>" class="w100" autocomplete="off" disabled aria-disabled="true">
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label for="label_full_name" class="w25"><?php echo settings_index_i18n('FULL_NAME'); ?></label>
      <input type="text" id="label_full_name" name="full_name" value="<?php echo htmlspecialchars((string) $user->full_name, ENT_QUOTES, 'UTF-8'); ?>" class="w75" autocomplete="name" disabled aria-disabled="true">
    </div>

    <div class="flex f_baseline w100">
      <label for="label_phone" class="w25"><?php echo settings_index_i18n('PHONE'); ?></label>
      <input type="tel" id="label_phone" name="phone" value="<?php echo htmlspecialchars((string) $user->phone, ENT_QUOTES, 'UTF-8'); ?>" class="w75" autocomplete="tel-national" disabled aria-disabled="true">
    </div>

    <div class="flex f_baseline w100">
      <label for="label_province" class="w25"><?php echo settings_index_i18n('PROVINCE'); ?></label>
      <select id="label_province" name="province" class="w75" disabled aria-disabled="true">
        <option value='AB'<?php if ('AB' === $user->province) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PROVINCE_OPTION_ALBERTA'); ?></option>
        <option value='BC'<?php if ('BC' === $user->province) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PROVINCE_OPTION_BRITISH_COLUMBIA'); ?></option>
        <option value='MB'<?php if ('MB' === $user->province) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PROVINCE_OPTION_MANITOBA'); ?></option>
        <option value='NB'<?php if ('NB' === $user->province) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PROVINCE_OPTION_NEW_BRUNSWICK'); ?></option>
        <option value='NL'<?php if ('NL' === $user->province) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PROVINCE_OPTION_NEWFOUNDLAND_AND_LABRADOR'); ?></option>
        <option value='NS'<?php if ('NS' === $user->province) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PROVINCE_OPTION_NOVA_SCOTIA'); ?></option>
        <option value='ON'<?php if ('ON' === $user->province) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PROVINCE_OPTION_ONTARIO'); ?></option>
        <option value='PE'<?php if ('PE' === $user->province) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PROVINCE_OPTION_PRINCE_EDWARD_ISLAND'); ?></option>
        <option value='QC'<?php if ('QC' === $user->province) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PROVINCE_OPTION_QUEBEC'); ?></option>
        <option value='SK'<?php if ('SK' === $user->province) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PROVINCE_OPTION_SASKATCHEWAN'); ?></option>
        <option value='NT'<?php if ('NT' === $user->province) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PROVINCE_OPTION_NORTHWEST_TERRITORIES'); ?></option>
        <option value='NU'<?php if ('NU' === $user->province) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PROVINCE_OPTION_NUNAVUT'); ?></option>
        <option value='YT'<?php if ('YT' === $user->province) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PROVINCE_OPTION_YUKON'); ?></option>
      </select>
    </div>

    <div class="account_actions">
      <button type="button" id="call_edit_details_modal" class="btn btn_primary"><?php echo settings_index_i18n('SETTINGS_ACCOUNT_EDIT_DETAILS_BTN'); ?></button>
    </div>
  </form>
</section>
