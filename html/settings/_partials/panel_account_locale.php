<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
<section class="panel settings_card_group" id="panel-internationalization" aria-labelledby="panel-internationalization-heading" title="<?php echo settings_index_i18n('PROFILE_INTERNATIONALIZATION_TITLE'); ?>" data-hover-help="<?php echo settings_index_i18n('PROFILE_INTERNATIONALIZATION_PANEL_HELP'); ?>">
  <h2 class="heading-accent settings_card_title" id="panel-internationalization-heading"><?php echo settings_index_i18n('PROFILE_INTERNATIONALIZATION_TITLE'); ?></h2>

  <div class="profile_i18n_grid">
    <div class="item_pair">
      <label for="businesses_personal_language" class="item_label"><?php echo settings_index_i18n('LANGUAGE'); ?></label>
      <div class="item_value">
        <select id="businesses_personal_language" name="language" aria-describedby="edit_details_status edit_details_language_error">
          <?php foreach (Language::AVAILABLE as $languageCode => $languageName) { ?>
            <option value="<?php echo htmlspecialchars($languageCode, ENT_QUOTES, 'UTF-8'); ?>"<?php if ((string) $user->language === (string) $languageCode) { echo ' selected'; } ?>><?php echo htmlspecialchars($languageName, ENT_QUOTES, 'UTF-8'); ?></option>
          <?php } ?>
        </select>
        <div id="edit_details_language_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
      </div>
    </div>

    <div class="item_pair">
      <label for="businesses_personal_locale" class="item_label"><?php echo settings_index_i18n('LOCALE'); ?></label>
      <div class="item_value">
        <select id="businesses_personal_locale" name="locale" aria-describedby="edit_details_status edit_details_locale_error">
          <?php foreach ($localeOptions as $localeCode => $localeLabel) { ?>
            <option value="<?php echo htmlspecialchars($localeCode, ENT_QUOTES, 'UTF-8'); ?>"<?php if ((string) (($user->locale ?? '') !== '' ? $user->locale : 'en-CA') === (string) $localeCode) { echo ' selected'; } ?>><?php echo htmlspecialchars($localeLabel, ENT_QUOTES, 'UTF-8'); ?></option>
          <?php } ?>
        </select>
        <div id="edit_details_locale_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
      </div>
    </div>

    <div class="item_pair">
      <label for="businesses_personal_currency_search" class="item_label"><?php echo settings_index_i18n('BUSINESSES_CURRENCY'); ?></label>
      <div class="item_value">
        <div class="currency_finder" id="businesses_personal_currency_finder" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-owns="businesses_personal_currency_listbox">
          <input class="currency_finder_search" id="businesses_personal_currency_search" type="text" autocomplete="off" spellcheck="false" placeholder="<?php echo settings_index_i18n('PROFILE_SEARCH_CURRENCIES_PLACEHOLDER'); ?>" aria-autocomplete="list" aria-controls="businesses_personal_currency_listbox" aria-label="<?php echo settings_index_i18n('BUSINESSES_CURRENCY'); ?>">
          <input id="businesses_personal_currency" type="hidden">
          <ul id="businesses_personal_currency_listbox" class="currency_finder_list" role="listbox" hidden></ul>
        </div>
      </div>
    </div>

    <div class="item_pair">
      <label for="businesses_personal_timezone_search" class="item_label"><?php echo settings_index_i18n('BUSINESSES_TIMEZONE'); ?></label>
      <div class="item_value">
        <div class="timezone_finder" id="businesses_personal_timezone_finder" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-owns="businesses_personal_timezone_listbox">
          <input class="timezone_finder_search" id="businesses_personal_timezone_search" type="text" autocomplete="off" spellcheck="false" placeholder="<?php echo settings_index_i18n('PROFILE_SEARCH_TIMEZONES_PLACEHOLDER'); ?>" aria-autocomplete="list" aria-controls="businesses_personal_timezone_listbox" aria-label="<?php echo settings_index_i18n('BUSINESSES_TIMEZONE'); ?>">
          <input id="businesses_personal_timezone" type="hidden">
          <ul id="businesses_personal_timezone_listbox" class="timezone_finder_list" role="listbox" hidden></ul>
        </div>
      </div>
    </div>
  </div>

  <div id="businesses_i18n_preview" class="profile_i18n_preview" role="status" aria-live="polite"></div>
</section>
