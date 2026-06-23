<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
  <section class="panel business_details_profile_panel" aria-labelledby="business_details_profile_heading">
    <h2 id="business_details_profile_heading" class="visually_hidden"><?php echo businesses_index_i18n('BUSINESSES_EDITOR_DETAILS_H3'); ?></h2>

    <div class="businesses_details_columns">
      <section class="businesses_details_column">
        <div class="businesses_field_grid">
          <label for="businesses_editor_name"><?php echo businesses_index_i18n('BUSINESSES_NAME'); ?></label>
          <input id="businesses_editor_name" type="text" maxlength="80" required autocomplete="off">

          <label for="businesses_editor_legal_name"><?php echo businesses_index_i18n('BUSINESSES_LEGAL_NAME'); ?></label>
          <input id="businesses_editor_legal_name" type="text" maxlength="140" placeholder="<?php echo businesses_index_i18n('BUSINESSES_LEGAL_NAME_PLACEHOLDER'); ?>" autocomplete="off">

          <label for="businesses_editor_industry"><?php echo businesses_index_i18n('BUSINESSES_INDUSTRY'); ?></label>
          <input id="businesses_editor_industry" type="text" maxlength="80" placeholder="<?php echo businesses_index_i18n('BUSINESSES_INDUSTRY_PLACEHOLDER'); ?>">

          <label for="businesses_editor_registration_number"><?php echo businesses_index_i18n('BUSINESSES_REG_NUMBER'); ?></label>
          <input id="businesses_editor_registration_number" type="text" maxlength="64" placeholder="<?php echo businesses_index_i18n('BUSINESSES_REG_NUMBER_PLACEHOLDER'); ?>">

          <label for="businesses_editor_tax_id"><?php echo businesses_index_i18n('BUSINESSES_TAX_ID'); ?></label>
          <input id="businesses_editor_tax_id" type="text" maxlength="64" placeholder="<?php echo businesses_index_i18n('BUSINESSES_TAX_ID_PLACEHOLDER'); ?>">

          <label for="businesses_editor_employee_count"><?php echo businesses_index_i18n('BUSINESSES_EMPLOYEE_COUNT'); ?></label>
          <input id="businesses_editor_employee_count" type="text" maxlength="16" placeholder="<?php echo businesses_index_i18n('BUSINESSES_EMPLOYEE_COUNT_PLACEHOLDER'); ?>">

          <label for="businesses_editor_founded_year"><?php echo businesses_index_i18n('BUSINESSES_FOUNDED_YEAR'); ?></label>
          <input id="businesses_editor_founded_year" type="text" maxlength="8" placeholder="<?php echo businesses_index_i18n('BUSINESSES_FOUNDED_YEAR_PLACEHOLDER'); ?>">

          <label for="businesses_editor_timezone_search"><?php echo businesses_index_i18n('BUSINESSES_TIMEZONE'); ?></label>
          <div class="timezone_finder" id="businesses_editor_timezone_finder" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-owns="businesses_editor_timezone_listbox">
            <input class="timezone_finder_search" id="businesses_editor_timezone_search" type="text" autocomplete="off" spellcheck="false" placeholder="<?php echo businesses_index_i18n('PROFILE_TIMEZONE_SEARCH_PLACEHOLDER'); ?>" aria-autocomplete="list" aria-controls="businesses_editor_timezone_listbox" aria-label="<?php echo businesses_index_i18n('BUSINESSES_TIMEZONE'); ?>">
            <input id="businesses_editor_timezone" type="hidden">
            <ul id="businesses_editor_timezone_listbox" class="timezone_finder_list" role="listbox" hidden></ul>
          </div>

          <label for="businesses_editor_currency_search"><?php echo businesses_index_i18n('BUSINESSES_CURRENCY'); ?></label>
          <div class="currency_finder" id="businesses_editor_currency_finder" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-owns="businesses_editor_currency_listbox">
            <input class="currency_finder_search" id="businesses_editor_currency_search" type="text" autocomplete="off" spellcheck="false" placeholder="<?php echo businesses_index_i18n('PROFILE_CURRENCY_SEARCH_PLACEHOLDER'); ?>" aria-autocomplete="list" aria-controls="businesses_editor_currency_listbox" aria-label="<?php echo businesses_index_i18n('BUSINESSES_CURRENCY'); ?>">
            <input id="businesses_editor_currency" type="hidden">
            <ul id="businesses_editor_currency_listbox" class="currency_finder_list" role="listbox" hidden></ul>
          </div>
        </div>
      </section>

      <section class="businesses_details_column">
        <div class="businesses_field_grid">
          <label for="businesses_editor_contact_email"><?php echo businesses_index_i18n('BUSINESSES_CONTACT_EMAIL'); ?></label>
          <input id="businesses_editor_contact_email" type="email" maxlength="160" placeholder="<?php echo businesses_index_i18n('BUSINESSES_CONTACT_EMAIL_PLACEHOLDER'); ?>" autocomplete="email">

          <label for="businesses_editor_contact_phone"><?php echo businesses_index_i18n('BUSINESSES_CONTACT_PHONE'); ?></label>
          <input id="businesses_editor_contact_phone" type="tel" maxlength="14" inputmode="numeric" pattern="\([0-9]{3}\) [0-9]{3}-[0-9]{4}" placeholder="<?php echo businesses_index_i18n('BUSINESSES_CONTACT_PHONE_PLACEHOLDER'); ?>" autocomplete="tel-national">

          <label for="businesses_editor_website"><?php echo businesses_index_i18n('BUSINESSES_WEBSITE'); ?></label>
          <input id="businesses_editor_website" type="url" maxlength="180" placeholder="<?php echo businesses_index_i18n('BUSINESSES_WEBSITE_PLACEHOLDER'); ?>" autocomplete="url">

          <label for="businesses_editor_indigenous_owned">Indigenous business</label>
          <input id="businesses_editor_indigenous_owned" type="checkbox" value="1">

          <label for="businesses_editor_resident_on_reserve">Resident on reserve</label>
          <input id="businesses_editor_resident_on_reserve" type="checkbox" value="1">

          <label for="businesses_editor_reserve_name">Reserve name</label>
          <input id="businesses_editor_reserve_name" type="text" maxlength="120">

          <label for="businesses_editor_address_line1"><?php echo businesses_index_i18n('BUSINESSES_ADDRESS_LINE_1'); ?></label>
          <input id="businesses_editor_address_line1" type="text" maxlength="120" placeholder="<?php echo businesses_index_i18n('BUSINESSES_ADDRESS_LINE_1_PLACEHOLDER'); ?>" autocomplete="address-line1">

          <label for="businesses_editor_address_line2"><?php echo businesses_index_i18n('BUSINESSES_ADDRESS_LINE_2'); ?></label>
          <input id="businesses_editor_address_line2" type="text" maxlength="120" placeholder="<?php echo businesses_index_i18n('BUSINESSES_ADDRESS_LINE_2_PLACEHOLDER'); ?>" autocomplete="address-line2">

          <label for="businesses_editor_address_city"><?php echo businesses_index_i18n('BUSINESSES_ADDRESS_CITY'); ?></label>
          <input id="businesses_editor_address_city" type="text" maxlength="80" placeholder="<?php echo businesses_index_i18n('BUSINESSES_ADDRESS_CITY_PLACEHOLDER'); ?>" autocomplete="address-level2">

          <label for="businesses_editor_address_region"><?php echo businesses_index_i18n('BUSINESSES_ADDRESS_REGION'); ?></label>
          <input id="businesses_editor_address_region" type="text" maxlength="80" placeholder="<?php echo businesses_index_i18n('BUSINESSES_ADDRESS_REGION_PLACEHOLDER'); ?>" autocomplete="address-level1">

          <label for="businesses_editor_address_postal"><?php echo businesses_index_i18n('BUSINESSES_ADDRESS_POSTAL'); ?></label>
          <input id="businesses_editor_address_postal" type="text" maxlength="20" placeholder="<?php echo businesses_index_i18n('BUSINESSES_ADDRESS_POSTAL_PLACEHOLDER'); ?>" autocomplete="postal-code">

          <label for="businesses_editor_address_country"><?php echo businesses_index_i18n('BUSINESSES_ADDRESS_COUNTRY'); ?></label>
          <input id="businesses_editor_address_country" type="text" maxlength="64" placeholder="<?php echo businesses_index_i18n('BUSINESSES_ADDRESS_COUNTRY_PLACEHOLDER'); ?>" autocomplete="country-name">

          <label for="businesses_editor_support_hours"><?php echo businesses_index_i18n('BUSINESSES_SUPPORT_HOURS'); ?></label>
          <input id="businesses_editor_support_hours" type="text" maxlength="120" placeholder="<?php echo businesses_index_i18n('BUSINESSES_SUPPORT_HOURS_PLACEHOLDER'); ?>">
        </div>
      </section>

      <div class="businesses_field_grid business_details_notes_row">
        <label for="businesses_editor_business_notes"><?php echo businesses_index_i18n('BUSINESSES_ORG_NOTES'); ?></label>
        <textarea id="businesses_editor_business_notes" rows="8" maxlength="1200" placeholder="<?php echo businesses_index_i18n('BUSINESSES_ORG_NOTES_PLACEHOLDER'); ?>"></textarea>
      </div>
    </div>
  </section>

  <section class="panel business_details_contacts_panel" aria-labelledby="business_details_contacts_heading">
    <div class="businesses_section_header">
      <h2 id="business_details_contacts_heading"><?php echo businesses_index_i18n('BUSINESSES_CONTACT_DIRECTORY_H3'); ?></h2>
    </div>
    <p class="help_text"><?php echo businesses_index_i18n('BUSINESSES_CONTACT_DIRECTORY_HELP'); ?></p>
    <div class="businesses_contact_directory_grid">
<?php
$fixedContactKeys = ['ceo', 'coo', 'cto', 'payroll', 'hr'];
foreach ($fixedContactKeys as $contactKey):
  $fieldPrefix = 'businesses_editor_contact_' . $contactKey;
  $avatarPreviewId = $fieldPrefix . '_avatar_preview';
?>
      <div class="businesses_contact_card">
        <button type="button" class="businesses_contact_card_avatar_button" aria-haspopup="dialog" aria-controls="businesses_contact_image_popover" aria-expanded="false" aria-label="<?php echo businesses_index_i18n('BUSINESSES_CONTACT_IMAGE_POPOVER_ARIA'); ?>">
          <img id="<?php echo $avatarPreviewId; ?>" class="businesses_contact_card_avatar" src="" alt="" role="presentation" loading="lazy">
        </button>
        <input id="<?php echo $fieldPrefix; ?>_image_url" class="businesses_contact_image_input" data-preview-id="<?php echo $avatarPreviewId; ?>" type="hidden" maxlength="20000" value="">
        <input id="<?php echo $fieldPrefix; ?>_role" class="businesses_contact_role_input" type="text" maxlength="80" placeholder="<?php echo businesses_index_i18n('BUSINESSES_CONTACT_ROLE_PH'); ?>">
        <input id="<?php echo $fieldPrefix; ?>_name" class="businesses_contact_body_input" name="name" autocomplete="name" type="text" maxlength="100" placeholder="<?php echo businesses_index_i18n('NAME'); ?>">
        <input id="<?php echo $fieldPrefix; ?>_email" class="businesses_contact_body_input" name="email" autocomplete="email" type="email" maxlength="160" placeholder="<?php echo businesses_index_i18n('EMAIL'); ?>">
        <input id="<?php echo $fieldPrefix; ?>_phone" class="businesses_contact_body_input" name="phone" autocomplete="tel" type="tel" maxlength="32" placeholder="<?php echo businesses_index_i18n('PHONE'); ?>">
        <div class="businesses_contact_card_menu">
          <button type="button" class="btn btn_secondary businesses_contact_card_menu_toggle" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo businesses_index_i18n('BUSINESSES_CONTACT_ACTIONS_ARIA'); ?>">...</button>
          <button type="button" class="btn btn_secondary businesses_contact_card_menu_delete" data-card-type="fixed" data-confirming="false" hidden><?php echo businesses_index_i18n('BUSINESSES_CONTACT_CLEAR'); ?></button>
        </div>
      </div>
<?php endforeach; ?>
    </div>
    <div id="businesses_contact_directory_custom_cards" class="businesses_contact_directory_grid businesses_contact_directory_custom_grid"></div>
    <input id="businesses_editor_contact_custom_json" type="hidden" value="">

    <p id="businesses_business_details_status" class="help_text visually_hidden" role="status" aria-live="polite" aria-atomic="true"></p>
  </section>

  <div id="businesses_contact_image_popover" class="businesses_contact_image_popover hidden" role="dialog" aria-modal="false" aria-labelledby="businesses_contact_image_popover_title" hidden>
    <p id="businesses_contact_image_popover_title" class="businesses_contact_image_popover_title"><?php echo businesses_index_i18n('BUSINESSES_CONTACT_IMAGE_POPOVER_TITLE'); ?></p>
    <div id="businesses_contact_image_dropzone" class="businesses_contact_image_dropzone" tabindex="0" role="button" aria-label="<?php echo businesses_index_i18n('BUSINESSES_CONTACT_IMAGE_DROP_ARIA'); ?>">
      <?php echo businesses_index_i18n('BUSINESSES_CONTACT_IMAGE_DROP'); ?>
    </div>
    <input id="businesses_contact_image_file" type="file" accept="image/*" class="visually_hidden">
    <div class="businesses_contact_image_popover_actions">
      <button id="businesses_contact_image_clear" type="button" class="btn btn_secondary"><?php echo businesses_index_i18n('BUSINESSES_CONTACT_IMAGE_REMOVE'); ?></button>
      <button id="businesses_contact_image_cancel" type="button" class="btn btn_secondary"><?php echo businesses_index_i18n('CLOSE'); ?></button>
    </div>
  </div>
