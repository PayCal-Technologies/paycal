<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

/**
 * Profile Page.
 *
 * Personal info, pay period settings, subscription, and account management.
 */
$currentPage = 'PAGE_PROFILE';

require_once '../config.php';

if (function_exists('profile_index_i18n') === false) {
  function profile_index_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}

Authentication::redirectHomeIfUnauthenticated();

/** @var User $user */
$user = User::current();
$profileSubscription = SubscriptionRepository::get($user->user_uuid);
$billingProvider = BillingProvider::current();
$isStripeBilling = $billingProvider === BillingProvider::STRIPE;
$hasActivePremium = SubscriptionRepository::isPremiumActive($user->user_uuid);
$hasActiveBusiness = SubscriptionRepository::isBusinessActive($user->user_uuid);
$hasPaidSubscription = $hasActivePremium || $hasActiveBusiness;
$billingHint = $hasActiveBusiness ? 'business' : ($hasActivePremium ? 'premium' : 'free');

Lens::boot('profile');

$pageTitle = profile_index_i18n('PROFILE') . ' - [' . profile_index_i18n('SITE_NAME') . ']';
$pageLabel = profile_index_i18n('PROFILE');
$pageLanguage = (string) User::current()->language;
$settingsCsrfNonce = User::current()->generateFormNonce('settings');
$businessesCsrfNonce = User::current()->generateFormNonce('businesses');
require __DIR__ . '/../business/_partials/i18n.php';
$profilePayPeriodManagedBy = (new BusinessDiscoveryService())->resolveProfilePayPeriodManagedByBusiness($user->user_uuid);
$localeOptions = [
  'en-CA' => profile_index_i18n('PROFILE_LOCALE_EN_CA'),
  'fr-CA' => profile_index_i18n('PROFILE_LOCALE_FR_CA'),
  'en-US' => profile_index_i18n('PROFILE_LOCALE_EN_US'),
  'en-GB' => profile_index_i18n('PROFILE_LOCALE_EN_GB'),
  'fr-FR' => profile_index_i18n('PROFILE_LOCALE_FR_FR'),
  'de-DE' => profile_index_i18n('PROFILE_LOCALE_DE_DE'),
  'es-ES' => profile_index_i18n('PROFILE_LOCALE_ES_ES'),
  'pt-BR' => profile_index_i18n('PROFILE_LOCALE_PT_BR'),
];

require_once \PayCal\Domain\Config\Environment::appHome().'html/header.php';
?>

  <h1 class="visually_hidden"><?php echo profile_index_i18n('PROFILE'); ?></h1>

  <!-- MODAL CHANGE EMAIL -->
  <dialog id="modal_change_email" aria-modal="true" aria-labelledby="modal_change_email_title" aria-describedby="modal_change_email_desc change_email_status">
  <form id="change_email_form" name="change_email_form" aria-label="<?php echo profile_index_i18n('CHANGE_EMAIL'); ?>">
  <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
  <input type="hidden" name="csrf_token" value="<?php echo $settingsCsrfNonce; ?>">
  <input type="hidden" id="change_email_txn_id" value="">

    <section class="modal_header">
      <button type="button" class="btn btn_close" data-dialog-close="modal_change_email" aria-label="<?php echo profile_index_i18n('CLOSE'); ?>">&times;</button>
      <h2 id="modal_change_email_title" class="modal_title centered"><?php echo profile_index_i18n('CHANGE_EMAIL'); ?></h2>
    </section>

    <p id="modal_change_email_desc" class="visually_hidden"><?php echo profile_index_i18n('PROFILE_CHANGE_EMAIL_DESC'); ?></p>

    <section class="modal_content f_column">
      <div id="change_email_step1_section">
        <div class="item_pair">
          <div class="item_label" data-tooltip="<?php echo profile_index_i18n('TOOLTIP_CURRENT_EMAIL'); ?>"><?php echo profile_index_i18n('CURRENT_EMAIL'); ?></div>
          <div class="item_value"><input type="email" name="current_email" value="<?php echo $user->email; ?>" readonly autocomplete="email" aria-readonly="true" aria-label="<?php echo profile_index_i18n('CURRENT_EMAIL'); ?>" disabled></div>
        </div>

        <div class="item_pair">
          <div class="status_message centered" id="change_email_status" aria-live="assertive" role="status"></div>
        </div>

        <div class="item_pair">
          <label for="change_email_new_email" class="item_label"><?php echo profile_index_i18n('PROFILE_NEW_EMAIL_ADDRESS'); ?></label>
          <div class="item_value">
            <input type="email" id="change_email_new_email" placeholder="<?php echo profile_index_i18n('PROFILE_ENTER_NEW_EMAIL_ADDRESS'); ?>" autocomplete="email" aria-describedby="change_email_status change_email_new_email_error">
            <div id="change_email_new_email_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="item_pair">
          <label for="change_email_confirm_email" class="item_label"><?php echo profile_index_i18n('PROFILE_CONFIRM_EMAIL_ADDRESS'); ?></label>
          <div class="item_value">
            <input type="email" id="change_email_confirm_email" placeholder="<?php echo profile_index_i18n('CONFIRM_NO_TYPOS'); ?>" autocomplete="email" aria-describedby="change_email_status change_email_confirm_email_error">
            <div id="change_email_confirm_email_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>
      </div>

      <div id="change_email_step2_section" hidden>
        <div class="item_pair">
          <div class="status_message centered" id="change_email_verify_status" aria-live="assertive" role="status"></div>
        </div>

        <div class="item_pair">
          <label for="change_email_old_code" class="item_label"><?php echo profile_index_i18n('PROFILE_OLD_EMAIL_CODE'); ?></label>
          <div class="item_value">
            <div class="status_text compact_hint" id="old_email_hint"></div>
            <input type="text" id="change_email_old_code" class="code_input" placeholder="<?php echo profile_index_i18n('PROFILE_ENTER_6_DIGIT_CODE'); ?>" maxlength="6" inputmode="numeric" autocomplete="one-time-code" aria-describedby="change_email_verify_status old_email_hint change_email_old_code_error">
            <div id="change_email_old_code_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="item_pair">
          <label for="change_email_new_code" class="item_label"><?php echo profile_index_i18n('PROFILE_NEW_EMAIL_CODE'); ?></label>
          <div class="item_value">
            <div class="status_text compact_hint" id="new_email_hint"></div>
            <input type="text" id="change_email_new_code" class="code_input" placeholder="<?php echo profile_index_i18n('PROFILE_ENTER_6_DIGIT_CODE'); ?>" maxlength="6" inputmode="numeric" autocomplete="one-time-code" aria-describedby="change_email_verify_status new_email_hint change_email_new_code_error">
            <div id="change_email_new_code_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="item_pair">
          <div class="item_label">&nbsp;</div>
          <div class="item_value">
            <div id="change_email_expiry_timer" class="status_text compact_hint"></div>
          </div>
        </div>
      </div>

    </section>

    <section class="modal_footer">
      <div class="modal_controls flex centered">
        <button id="change_email_start_btn" class="btn btn_primary f_just_center mar_md" type="button"><?php echo profile_index_i18n('PROFILE_SEND_VERIFICATION_CODES'); ?></button>
        <button id="change_email_verify_btn" class="btn btn_primary f_just_center mar_md" type="button" hidden disabled aria-disabled="true"><?php echo profile_index_i18n('PROFILE_COMPLETE_EMAIL_CHANGE'); ?></button>
        <button id="change_email_resend_btn" class="btn btn_secondary f_just_center mar_md" type="button" hidden><?php echo profile_index_i18n('PROFILE_RESEND_CODES'); ?></button>
        <button class="btn btn_cancel f_just_center mar_md" id="change_email_prev_btn" type="button"><?php echo profile_index_i18n('CANCEL'); ?></button>
      </div>
    </section>
  </form>
  </dialog>

  <section class="panel profile_lead_panel" id="panel-personal-info" aria-labelledby="panel-personal-info-heading" title="<?php echo profile_index_i18n('PROFILE_PERSONAL_INFO_PANEL_HELP'); ?>" data-hover-help="<?php echo profile_index_i18n('PROFILE_PERSONAL_INFO_PANEL_HELP'); ?>">
    <div class="businesses_section_header">
      <div>
        <h2 id="panel-personal-info-heading"><?php echo profile_index_i18n('PROFILE_PERSONAL_INFO_TITLE'); ?></h2>
      </div>
    </div>
    <form method="POST" action="<?php echo Environment::appURL('api/v1/account/info/update/'); ?>" id="edit_details_form" name="edit_details_form" aria-label="<?php echo profile_index_i18n('PROFILE_PERSONAL_INFO_FORM_ARIA'); ?>">
      <input type="hidden" name="csrf_token" value="<?php echo $settingsCsrfNonce; ?>">
      <input type="hidden" id="businesses_personal_name" value="">

      <div id="edit_details_status" class="status_message" role="status" aria-live="polite"></div>

      <div class="profile_personal_info_grid">
        <div class="item_pair">
          <label for="edit_details_full_name" class="item_label"><?php echo profile_index_i18n('FULL_NAME'); ?></label>
          <div class="item_value">
            <input type="text" id="edit_details_full_name" name="full_name" value="<?php echo $user->full_name; ?>" autocomplete="name" required aria-describedby="edit_details_status edit_details_full_name_error">
            <div id="edit_details_full_name_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="item_pair">
          <label for="edit_details_email" class="item_label"><?php echo profile_index_i18n('EMAIL'); ?></label>
          <div class="item_value">
            <input type="email" id="edit_details_email" value="<?php echo $user->email; ?>" readonly autocomplete="off" aria-readonly="true" title="<?php echo profile_index_i18n('CHANGE_EMAIL'); ?>" data-hover-help="<?php echo profile_index_i18n('PROFILE_CHANGE_EMAIL_HELP'); ?>">
          </div>
        </div>

        <div class="item_pair">
          <label for="edit_details_phone" class="item_label"><?php echo profile_index_i18n('PHONE'); ?></label>
          <div class="item_value">
            <input type="tel" id="edit_details_phone" name="phone" value="<?php echo $user->phone; ?>" autocomplete="tel-national" maxlength="14" inputmode="numeric" pattern="\([0-9]{3}\) [0-9]{3}-[0-9]{4}" placeholder="<?php echo profile_index_i18n('BUSINESSES_CONTACT_PHONE_PLACEHOLDER'); ?>" aria-describedby="edit_details_status edit_details_phone_error">
            <div id="edit_details_phone_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="item_pair">
          <label for="edit_details_province" class="item_label"><?php echo profile_index_i18n('PROVINCE'); ?></label>
          <div class="item_value">
            <select id="edit_details_province" name="province" aria-describedby="edit_details_status edit_details_province_error">
              <option value='AB'<?php if ('AB' === $user->province) { echo ' selected'; } ?>><?php echo profile_index_i18n('PROFILE_PROVINCE_AB'); ?></option>
              <option value='BC'<?php if ('BC' === $user->province) { echo ' selected'; } ?>><?php echo profile_index_i18n('PROFILE_PROVINCE_BC'); ?></option>
              <option value='MB'<?php if ('MB' === $user->province) { echo ' selected'; } ?>><?php echo profile_index_i18n('PROFILE_PROVINCE_MB'); ?></option>
              <option value='NB'<?php if ('NB' === $user->province) { echo ' selected'; } ?>><?php echo profile_index_i18n('PROFILE_PROVINCE_NB'); ?></option>
              <option value='NL'<?php if ('NL' === $user->province) { echo ' selected'; } ?>><?php echo profile_index_i18n('PROFILE_PROVINCE_NL'); ?></option>
              <option value='NS'<?php if ('NS' === $user->province) { echo ' selected'; } ?>><?php echo profile_index_i18n('PROFILE_PROVINCE_NS'); ?></option>
              <option value='ON'<?php if ('ON' === $user->province) { echo ' selected'; } ?>><?php echo profile_index_i18n('PROFILE_PROVINCE_ON'); ?></option>
              <option value='PE'<?php if ('PE' === $user->province) { echo ' selected'; } ?>><?php echo profile_index_i18n('PROFILE_PROVINCE_PE'); ?></option>
              <option value='QC'<?php if ('QC' === $user->province) { echo ' selected'; } ?>><?php echo profile_index_i18n('PROFILE_PROVINCE_QC'); ?></option>
              <option value='SK'<?php if ('SK' === $user->province) { echo ' selected'; } ?>><?php echo profile_index_i18n('PROFILE_PROVINCE_SK'); ?></option>
              <option value='NT'<?php if ('NT' === $user->province) { echo ' selected'; } ?>><?php echo profile_index_i18n('PROFILE_PROVINCE_NT'); ?></option>
              <option value='NU'<?php if ('NU' === $user->province) { echo ' selected'; } ?>><?php echo profile_index_i18n('PROFILE_PROVINCE_NU'); ?></option>
              <option value='YT'<?php if ('YT' === $user->province) { echo ' selected'; } ?>><?php echo profile_index_i18n('PROFILE_PROVINCE_YT'); ?></option>
            </select>
            <div id="edit_details_province_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="item_pair">
          <label for="edit_details_address_line1" class="item_label"><?php echo profile_index_i18n('PROFILE_ADDRESS_LABEL'); ?></label>
          <div class="item_value">
            <input type="text" id="edit_details_address_line1" name="address_line1" value="<?php echo (string) ($user->address_line1 ?? ''); ?>" maxlength="120" aria-describedby="edit_details_status edit_details_address_line1_error">
            <div id="edit_details_address_line1_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="item_pair">
          <label for="businesses_personal_default_wage" class="item_label"><?php echo profile_index_i18n('WAGE'); ?></label>
          <div class="item_value">
            <input id="businesses_personal_default_wage" type="text" maxlength="32" placeholder="<?php echo profile_index_i18n('BUSINESSES_DEFAULT_WAGE_PLACEHOLDER'); ?>">
          </div>
        </div>

        <div class="item_pair">
          <label for="edit_details_address_city" class="item_label"><?php echo profile_index_i18n('PROFILE_CITY_LABEL'); ?></label>
          <div class="item_value">
            <input type="text" id="edit_details_address_city" name="address_city" value="<?php echo (string) ($user->address_city ?? ''); ?>" maxlength="80" aria-describedby="edit_details_status edit_details_address_city_error">
            <div id="edit_details_address_city_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="item_pair">
          <label for="edit_details_address_postal" class="item_label"><?php echo profile_index_i18n('PROFILE_POSTAL_LABEL'); ?></label>
          <div class="item_value">
            <input type="text" id="edit_details_address_postal" name="address_postal" value="<?php echo (string) ($user->address_postal ?? ''); ?>" maxlength="20" aria-describedby="edit_details_status edit_details_address_postal_error">
            <div id="edit_details_address_postal_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>
      </div>

    </form>
  </section>

  <section class="panel" id="panel-internationalization" aria-labelledby="panel-internationalization-heading" title="<?php echo profile_index_i18n('PROFILE_INTERNATIONALIZATION_TITLE'); ?>" data-hover-help="<?php echo profile_index_i18n('PROFILE_INTERNATIONALIZATION_PANEL_HELP'); ?>">
    <div class="businesses_section_header">
      <div>
        <h2 id="panel-internationalization-heading"><?php echo profile_index_i18n('PROFILE_INTERNATIONALIZATION_TITLE'); ?></h2>
      </div>
    </div>

    <div class="profile_i18n_grid">
      <div class="item_pair">
        <label for="businesses_personal_language" class="item_label"><?php echo profile_index_i18n('LANGUAGE'); ?></label>
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
        <label for="businesses_personal_locale" class="item_label"><?php echo profile_index_i18n('LOCALE'); ?></label>
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
        <label for="businesses_personal_currency_search" class="item_label"><?php echo profile_index_i18n('BUSINESSES_CURRENCY'); ?></label>
        <div class="item_value">
          <div class="currency_finder" id="businesses_personal_currency_finder" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-owns="businesses_personal_currency_listbox">
            <input class="currency_finder_search" id="businesses_personal_currency_search" type="text" autocomplete="off" spellcheck="false" placeholder="<?php echo profile_index_i18n('PROFILE_SEARCH_CURRENCIES_PLACEHOLDER'); ?>" aria-autocomplete="list" aria-controls="businesses_personal_currency_listbox" aria-label="<?php echo profile_index_i18n('BUSINESSES_CURRENCY'); ?>">
            <input id="businesses_personal_currency" type="hidden">
            <ul id="businesses_personal_currency_listbox" class="currency_finder_list" role="listbox" hidden></ul>
          </div>
        </div>
      </div>

      <div class="item_pair">
        <label for="businesses_personal_timezone_search" class="item_label"><?php echo profile_index_i18n('BUSINESSES_TIMEZONE'); ?></label>
        <div class="item_value">
          <div class="timezone_finder" id="businesses_personal_timezone_finder" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-owns="businesses_personal_timezone_listbox">
            <input class="timezone_finder_search" id="businesses_personal_timezone_search" type="text" autocomplete="off" spellcheck="false" placeholder="<?php echo profile_index_i18n('PROFILE_SEARCH_TIMEZONES_PLACEHOLDER'); ?>" aria-autocomplete="list" aria-controls="businesses_personal_timezone_listbox" aria-label="<?php echo profile_index_i18n('BUSINESSES_TIMEZONE'); ?>">
            <input id="businesses_personal_timezone" type="hidden">
            <ul id="businesses_personal_timezone_listbox" class="timezone_finder_list" role="listbox" hidden></ul>
          </div>
        </div>
      </div>
    </div>

    <div id="businesses_i18n_preview" class="profile_i18n_preview" role="status" aria-live="polite"></div>
  </section>

  <section class="panel<?php echo $profilePayPeriodManagedBy !== null ? ' is-managed-by-business' : ''; ?>" id="panel-pay-period" aria-labelledby="panel-pay-period-heading" title="<?php echo profile_index_i18n('PROFILE_PAY_PERIOD_PANEL_HELP'); ?>" data-hover-help="<?php echo profile_index_i18n('PROFILE_PAY_PERIOD_PANEL_HELP'); ?>"
    <?php if ($profilePayPeriodManagedBy !== null) { ?>
    data-pay-period-managed="true"
    data-managed-business-id="<?php echo htmlspecialchars((string) $profilePayPeriodManagedBy['business_id'], ENT_QUOTES, 'UTF-8'); ?>"
    data-managed-business-name="<?php echo htmlspecialchars((string) $profilePayPeriodManagedBy['name'], ENT_QUOTES, 'UTF-8'); ?>"
    data-managed-payroll-href="<?php echo htmlspecialchars((string) $profilePayPeriodManagedBy['payroll_href'], ENT_QUOTES, 'UTF-8'); ?>"
    <?php } ?>
    data-user-settings='<?php echo htmlspecialchars((string) (json_encode([
      'pay_frequency'      => (string) ($user->pay_frequency       ?? 'biweekly'),
      'pay_anchor'         => (string) ($user->pay_anchor          ?? 'Monday'),
      'pay_period_start'   => (string) ($user->pay_period_start    ?? ''),
      'pay_period_length'  => (string) ($user->pay_period_length   ?? '14'),
      'editing_grace_days' => (string) ($user->editing_grace_days  ?? '0'),
      'pay_rate'           => (string) ($user->pay_rate            ?? ''),
      'timezone'           => (string) ($user->timezone            ?? 'America/Edmonton'),
      'currency'           => (string) ($user->currency            ?? 'CAD'),
      'language'           => (string) ($user->language            ?? 'en'),
      'locale'             => (string) (($user->locale             ?? '') !== '' ? $user->locale : 'en-CA'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'), ENT_QUOTES, 'UTF-8'); ?>'>
    <div class="businesses_section_header">
      <div>
        <h2 id="panel-pay-period-heading"><?php echo profile_index_i18n('PAY_PERIOD'); ?></h2>
      </div>
    </div>
    <div id="profile_pay_period_managed_banner" class="profile_pay_period_managed_banner" role="status" aria-live="polite"<?php if ($profilePayPeriodManagedBy === null) { echo ' hidden'; } ?>>
      <p class="profile_pay_period_managed_banner_lede">
        <?php
        if ($profilePayPeriodManagedBy !== null) {
          echo htmlspecialchars(
            sprintf(profile_index_i18n('PROFILE_PAY_PERIOD_MANAGED_BANNER'), (string) $profilePayPeriodManagedBy['name']),
            ENT_QUOTES,
            'UTF-8'
          );
        }
        ?>
      </p>
      <p class="help_text profile_pay_period_managed_banner_help"><?php echo profile_index_i18n('PROFILE_PAY_PERIOD_MANAGED_HELP'); ?></p>
      <p class="profile_pay_period_managed_banner_action">
        <a class="btn btn_secondary" href="<?php echo htmlspecialchars($profilePayPeriodManagedBy !== null ? (string) $profilePayPeriodManagedBy['payroll_href'] : '/business/payroll/', ENT_QUOTES, 'UTF-8'); ?>"><?php echo profile_index_i18n('PROFILE_PAY_PERIOD_MANAGED_LINK'); ?></a>
      </p>
    </div>
    <form id="businesses_personal_form" class="businesses_create_form" method="dialog">
      <input type="hidden" id="businesses_personal_org_id" value="">
      <input type="hidden" id="businesses_personal_pay_anchor" value="Monday">
      <input type="hidden" id="businesses_personal_pay_period_start" value="">
      <div class="businesses_pp_control_strip" role="group" aria-label="<?php echo profile_index_i18n('PROFILE_PAY_PERIOD_CONTROLS_ARIA'); ?>" title="<?php echo profile_index_i18n('PROFILE_PAY_PERIOD_CONTROLS_HELP'); ?>" data-hover-help="<?php echo profile_index_i18n('PROFILE_PAY_PERIOD_CONTROLS_HELP'); ?>">
        <div class="businesses_pp_control" title="<?php echo profile_index_i18n('PROFILE_PAY_FREQUENCY_HELP'); ?>" data-hover-help="<?php echo profile_index_i18n('PROFILE_PAY_FREQUENCY_HELP'); ?>">
          <label for="businesses_personal_pay_frequency"><?php echo profile_index_i18n('PROFILE_PAY_FREQUENCY_LABEL'); ?></label>
          <select id="businesses_personal_pay_frequency">
            <option value="weekly"><?php echo profile_index_i18n('PROFILE_PAY_FREQUENCY_WEEKLY'); ?></option>
            <option value="biweekly"><?php echo profile_index_i18n('PROFILE_PAY_FREQUENCY_BIWEEKLY'); ?></option>
            <option value="semimonthly"><?php echo profile_index_i18n('PROFILE_PAY_FREQUENCY_SEMIMONTHLY'); ?></option>
            <option value="monthly"><?php echo profile_index_i18n('MONTHLY'); ?></option>
          </select>
        </div>

        <div class="businesses_pp_control" title="<?php echo profile_index_i18n('PROFILE_PAY_LENGTH_HELP'); ?>" data-hover-help="<?php echo profile_index_i18n('PROFILE_PAY_LENGTH_HELP'); ?>">
          <label for="businesses_personal_pay_period_length"><?php echo profile_index_i18n('LENGTH'); ?></label>
          <input id="businesses_personal_pay_period_length" type="number" min="7" max="31" readonly>
        </div>

        <div class="businesses_pp_control" title="<?php echo profile_index_i18n('PROFILE_PAY_GRACE_HELP'); ?>" data-hover-help="<?php echo profile_index_i18n('PROFILE_PAY_GRACE_HELP'); ?>">
          <span class="businesses_pp_control_label"><?php echo profile_index_i18n('PROFILE_PAY_GRACE_LABEL'); ?></span>
          <div id="businesses_personal_editing_grace_days" class="radio_group businesses_grace_radio_group" role="radiogroup" aria-label="<?php echo profile_index_i18n('PROFILE_PAY_GRACE_LABEL'); ?>" title="<?php echo profile_index_i18n('PROFILE_PAY_GRACE_PICKER_HELP'); ?>">
            <input type="radio" class="radio" id="businesses_personal_grace_0" name="businesses_personal_editing_grace_days" value="0" checked>
            <label for="businesses_personal_grace_0"><?php echo profile_index_i18n('NONE'); ?></label>
            <input type="radio" class="radio" id="businesses_personal_grace_1" name="businesses_personal_editing_grace_days" value="1">
            <label for="businesses_personal_grace_1"><?php echo profile_index_i18n('PROFILE_PAY_GRACE_1_DAY'); ?></label>
            <input type="radio" class="radio" id="businesses_personal_grace_2" name="businesses_personal_editing_grace_days" value="2">
            <label for="businesses_personal_grace_2"><?php echo profile_index_i18n('PROFILE_PAY_GRACE_2_DAYS'); ?></label>
            <input type="radio" class="radio" id="businesses_personal_grace_3" name="businesses_personal_editing_grace_days" value="3">
            <label for="businesses_personal_grace_3"><?php echo profile_index_i18n('PROFILE_PAY_GRACE_3_DAYS'); ?></label>
          </div>
        </div>
      </div>

      <div id="businesses_personal_payperiod_warning" class="businesses_payperiod_warning" role="alert" aria-live="assertive"></div>

      <div id="businesses_personal_preview" class="businesses_preview_box pay_period_preview_compact" aria-live="polite" title="<?php echo profile_index_i18n('PROFILE_PAY_PREVIEW_HELP'); ?>"></div>
    </form>
  </section>

  <input type="hidden" id="settings_csrf_token" value="<?php echo $settingsCsrfNonce; ?>">
  <input type="hidden" id="businesses_csrf_token" value="<?php echo $businessesCsrfNonce; ?>">

<?php if (!$hasActiveBusiness) {
  require __DIR__ . '/../business/_partials/profile_connect_panel.php';
} ?>

  <section class="panel" id="panel-billing" aria-labelledby="panel-billing-heading" title="<?php echo profile_index_i18n('PROFILE_BILLING_PANEL_HELP'); ?>" data-hover-help="<?php echo profile_index_i18n('PROFILE_BILLING_PANEL_HELP'); ?>" data-billing-hint="<?php echo htmlspecialchars($billingHint, ENT_QUOTES, 'UTF-8'); ?>" data-billing-hydrated="false" data-billing-provider="<?php echo htmlspecialchars($billingProvider, ENT_QUOTES, 'UTF-8'); ?>" data-account-timezone="<?php echo htmlspecialchars((string) ($user->timezone ?? 'UTC'), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="businesses_section_header">
      <div>
        <h2 id="panel-billing-heading"><?php echo profile_index_i18n('PROFILE_BILLING_TITLE'); ?></h2>
      </div>
    </div>
    <div id="billing_status_sr" class="visually_hidden" role="status" aria-live="polite" aria-atomic="true"></div>
    <?php if ($isStripeBilling) { ?>
      <p class="help_text"><?php echo profile_index_i18n('PROFILE_BILLING_STRIPE_NOTE_PREFIX'); ?> <a href="/contact"><?php echo profile_index_i18n('PROFILE_BILLING_STRIPE_NOTE_LINK'); ?></a>.</p>
    <?php } else { ?>
      <p class="help_text"><?php echo profile_index_i18n('PROFILE_BILLING_PUBLIC_CORE_NOTE'); ?></p>
    <?php } ?>

    <div id="billing_free_view" class="billing_shell billing_tier_matrix"<?php if ($hasPaidSubscription) echo ' hidden'; ?>>
      <p class="help_text billing_matrix_intro"><?php echo profile_index_i18n('PROFILE_BILLING_MATRIX_INTRO'); ?></p>
      <div class="billing_tier_grid" role="list" aria-label="<?php echo profile_index_i18n('PROFILE_BILLING_MATRIX_ARIA'); ?>">
        <section class="billing_tier_card billing_tier_card_current" role="listitem" aria-label="<?php echo profile_index_i18n('PROFILE_BILLING_TIER_PUBLIC'); ?>">
          <h3><?php echo profile_index_i18n('PROFILE_BILLING_TIER_PUBLIC'); ?></h3>
          <p class="billing_tier_price"><?php echo profile_index_i18n('PROFILE_BILLING_TIER_PUBLIC_PRICE'); ?></p>
          <ul class="billing_value_list billing_tier_features">
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_PUBLIC_FEATURE_1'); ?></span></li>
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_PUBLIC_FEATURE_2'); ?></span></li>
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_PUBLIC_FEATURE_3'); ?></span></li>
          </ul>
          <p class="billing_tier_current_label"><?php echo profile_index_i18n('PROFILE_BILLING_CURRENT_PLAN'); ?></p>
        </section>

        <section class="billing_tier_card" role="listitem" aria-label="<?php echo profile_index_i18n('PROFILE_BILLING_TIER_PREMIUM'); ?>">
          <h3><?php echo profile_index_i18n('PROFILE_BILLING_TIER_PREMIUM'); ?></h3>
          <p class="billing_tier_price"><?php echo profile_index_i18n('PROFILE_BILLING_TIER_PREMIUM_PRICE'); ?></p>
          <ul class="billing_value_list billing_tier_features">
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_5'); ?></span></li>
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_6'); ?></span></li>
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_PREMIUM_REPORTING_FEATURE'); ?></span></li>
          </ul>
          <button type="button" id="billing_upgrade_premium_btn" class="btn btn_secondary" data-billing-plan="premium"><?php echo $isStripeBilling ? profile_index_i18n('PROFILE_BILLING_UPGRADE_PREMIUM_BUTTON') : profile_index_i18n('PROFILE_BILLING_ENABLE_PREMIUM'); ?></button>
          <div id="billing_upgrade_premium_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
        </section>

        <section class="billing_tier_card billing_tier_card_featured" role="listitem" aria-label="<?php echo profile_index_i18n('PROFILE_BILLING_TIER_BUSINESS'); ?>">
          <h3><?php echo profile_index_i18n('PROFILE_BILLING_TIER_BUSINESS'); ?></h3>
          <p class="billing_tier_price"><?php echo profile_index_i18n('PROFILE_BILLING_TIER_BUSINESS_PRICE'); ?></p>
          <ul class="billing_value_list billing_tier_features">
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_1'); ?></span></li>
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_2'); ?></span></li>
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_BUSINESS_FEATURE_LISTING'); ?></span></li>
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_4'); ?></span></li>
          </ul>
          <button type="button" id="billing_upgrade_business_btn" class="btn btn_primary" data-billing-plan="business"><?php echo $isStripeBilling ? profile_index_i18n('PROFILE_BILLING_UPGRADE_BUSINESS_BUTTON') : profile_index_i18n('PROFILE_BILLING_ENABLE_BUSINESS'); ?></button>
          <div id="billing_upgrade_business_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
        </section>
      </div>
      <p class="billing_businesses_link">
        <a href="#panel-business-connect"><?php echo profile_index_i18n('PROFILE_BILLING_CONNECT_LINK'); ?></a>
      </p>
    </div>

    <div id="billing_premium_view" class="billing_shell"<?php if (!$hasPaidSubscription) echo ' hidden'; ?>>
      <div class="billing_columns">
        <section class="billing_column billing_column_main" aria-label="<?php echo profile_index_i18n('PROFILE_BILLING_PREMIUM_ARIA'); ?>">
          <p class="billing_plan_value">
            <strong id="billing_plan_label"><?php echo $hasActiveBusiness ? profile_index_i18n('PROFILE_BILLING_PLAN_BUSINESS') : profile_index_i18n('PROFILE_BILLING_PLAN_PREMIUM'); ?></strong>
            <span id="billing_plan_status_badge" class="badge" hidden></span>
            <span class="billing_member_since">&mdash; <?php echo profile_index_i18n('PROFILE_BILLING_MEMBER_SINCE'); ?> <span id="billing_start_date">&#8212;</span></span>
          </p>
          <p class="billing_renewal_date" id="billing_renewal_line"><?php echo profile_index_i18n('PROFILE_BILLING_RENEWS'); ?> <span id="billing_renewal_date">&#8212;</span></p>
          <p class="billing_cancel_notice" id="billing_cancel_notice" hidden>
            <?php echo profile_index_i18n('PROFILE_BILLING_CANCEL_SCHEDULED_PREFIX'); ?>
            <span class="billing_datetime_anchor">
              <button
                type="button"
                id="billing_cancel_date_trigger"
                class="billing_datetime_trigger"
                aria-haspopup="dialog"
                aria-controls="billing_datetime_popover"
                aria-expanded="false"
              >
                <span id="billing_cancel_date">&#8212;</span>
                <span class="visually_hidden"><?php echo profile_index_i18n('PROFILE_BILLING_CANCEL_DATE_TRIGGER'); ?></span>
              </button>
              <span
                id="billing_datetime_popover"
                class="billing_datetime_popover"
                role="dialog"
                aria-modal="false"
                aria-label="<?php echo profile_index_i18n('PROFILE_BILLING_CANCEL_DATE_DETAILS_ARIA'); ?>"
                hidden
              >
                <span class="billing_datetime_popover_title"><?php echo profile_index_i18n('PROFILE_BILLING_TIMEZONES'); ?></span>
                <span class="billing_datetime_popover_rows" id="billing_datetime_popover_rows"></span>
              </span>
            </span>
            . <?php echo profile_index_i18n('PROFILE_BILLING_CANCEL_SCHEDULED_SUFFIX'); ?>
          </p>
          <button type="button" id="billing_portal_btn" class="btn btn_primary"><?php echo $isStripeBilling ? profile_index_i18n('PROFILE_BILLING_PORTAL_BUTTON') : profile_index_i18n('PROFILE_BILLING_DISABLE_PREMIUM'); ?></button>
          <div id="billing_portal_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
          <p class="billing_businesses_link">
            <a href="/business/"><?php echo profile_index_i18n('PROFILE_BILLING_ORGS_LINK'); ?></a>
          </p>
        </section>

        <section class="billing_column billing_column_side" role="region" aria-label="<?php echo profile_index_i18n('PROFILE_BILLING_PREMIUM_BENEFITS_ARIA'); ?>">
          <h3 id="billing_subscribed_features_title"><?php echo $hasActiveBusiness ? profile_index_i18n('PROFILE_BILLING_BUSINESS_BENEFITS_TITLE') : profile_index_i18n('PROFILE_BILLING_PREMIUM_BENEFITS_TITLE'); ?></h3>
          <ul class="billing_value_list" id="billing_subscribed_features_list" aria-label="<?php echo profile_index_i18n('PROFILE_BILLING_PREMIUM_FEATURES_ARIA'); ?>">
            <?php if ($hasActiveBusiness) { ?>
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_1'); ?></span></li>
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_2'); ?></span></li>
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_BUSINESS_FEATURE_LISTING'); ?></span></li>
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_4'); ?></span></li>
            <?php } else { ?>
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_5'); ?></span></li>
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_6'); ?></span></li>
            <li><span><?php echo profile_index_i18n('PROFILE_BILLING_PREMIUM_REPORTING_FEATURE'); ?></span></li>
            <?php } ?>
          </ul>
          <?php if ($hasActivePremium && !$hasActiveBusiness) { ?>
          <div id="billing_business_upgrade_zone" class="billing_business_upgrade_zone">
            <p class="help_text"><?php echo profile_index_i18n('PROFILE_BILLING_UPGRADE_TO_BUSINESS_HELP'); ?></p>
            <button type="button" id="billing_upgrade_business_subscribed_btn" class="btn btn_primary" data-billing-plan="business"><?php echo $isStripeBilling ? profile_index_i18n('PROFILE_BILLING_UPGRADE_BUSINESS_BUTTON') : profile_index_i18n('PROFILE_BILLING_ENABLE_BUSINESS'); ?></button>
            <div id="billing_upgrade_business_subscribed_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
          <?php } ?>
        </section>
      </div>
      <?php if ($isStripeBilling) { ?>
        <div id="billing_downgrade_zone" class="billing_downgrade_zone">
          <p class="help_text" id="billing_downgrade_help"><?php echo profile_index_i18n('PROFILE_BILLING_DOWNGRADE_HELP'); ?></p>
          <div class="danger_confirm_pill" id="billing_downgrade_pill">
            <span><?php echo profile_index_i18n('PROFILE_BILLING_DOWNGRADE_PROMPT_PREFIX'); ?> <code><?php echo profile_index_i18n('PROFILE_BILLING_DOWNGRADE_PHRASE'); ?></code></span>
            <input type="text" id="billing_downgrade_phrase" autocomplete="off" spellcheck="false" autocapitalize="characters" maxlength="24" aria-label="<?php echo profile_index_i18n('PROFILE_BILLING_DOWNGRADE_ARIA'); ?>">
            <button type="button" id="billing_downgrade_confirm" class="btn btn_delete" disabled><?php echo profile_index_i18n('PROFILE_CONFIRM_BUTTON'); ?></button>
          </div>
          <div id="billing_downgrade_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
        </div>
      <?php } ?>
    </div>
  </section>

  <section class="panel" id="panel-account-activity" aria-labelledby="panel-account-activity-heading" data-hover-help="<?php echo profile_index_i18n('PROFILE_ACCOUNT_ACTIVITY_PANEL_HELP'); ?>">
    <div class="businesses_section_header">
      <div>
        <h2 id="panel-account-activity-heading"><?php echo profile_index_i18n('PROFILE_ACCOUNT_ACTIVITY_TITLE'); ?></h2>
        <p class="help_text"><?php echo profile_index_i18n('PROFILE_ACCOUNT_ACTIVITY_INTRO'); ?></p>
      </div>
    </div>

    <div id="account_activity_status" class="status_text compact_hint" role="status" aria-live="polite"></div>

    <div class="account_activity_grid">
      <section class="account_activity_card" aria-label="<?php echo profile_index_i18n('PROFILE_ACCOUNT_ACTIVITY_LOGIN_ARIA'); ?>">
        <h3><?php echo profile_index_i18n('PROFILE_ACCOUNT_ACTIVITY_H3_CURRENT_LOGIN'); ?></h3>
        <dl id="account_activity_login_details" class="account_activity_list"></dl>
      </section>

      <section class="account_activity_card" aria-label="<?php echo profile_index_i18n('PROFILE_ACCOUNT_ACTIVITY_BROWSER_ARIA'); ?>">
        <h3><?php echo profile_index_i18n('PROFILE_ACCOUNT_ACTIVITY_H3_BROWSER_DETAILS'); ?></h3>
        <dl id="account_activity_browser_details" class="account_activity_list"></dl>
      </section>

      <section class="account_activity_card account_activity_card_sessions" aria-label="<?php echo profile_index_i18n('PROFILE_ACCOUNT_ACTIVITY_ACTIVE_SESSIONS_ARIA'); ?>">
        <h3><?php echo profile_index_i18n('PROFILE_ACCOUNT_ACTIVITY_H3_ACTIVE_SESSIONS'); ?></h3>
        <div id="account_activity_sessions" class="account_activity_sessions"></div>
      </section>
    </div>
  </section>

  <section class="panel" id="panel-danger-zone" aria-labelledby="panel-danger-zone-heading" title="<?php echo profile_index_i18n('PROFILE_DANGER_PANEL_HELP'); ?>" data-hover-help="<?php echo profile_index_i18n('PROFILE_DANGER_PANEL_HELP'); ?>">
    <div class="businesses_section_header">
      <div>
        <h2 id="panel-danger-zone-heading"><?php echo profile_index_i18n('PROFILE_DANGER_TITLE'); ?></h2>
        <p class="help_text danger_zone_intro"><?php echo profile_index_i18n('PROFILE_DANGER_INTRO'); ?></p>
      </div>
    </div>

    <div class="danger_zone_actions" aria-label="<?php echo profile_index_i18n('PROFILE_DANGER_ACTIONS_ARIA'); ?>">
      <div class="danger_zone_row">
        <div class="danger_zone_text">
          <p class="help_text"><?php echo profile_index_i18n('PROFILE_DANGER_DELETE_DATA_TEXT'); ?></p>
        </div>
        <div class="danger_zone_controls">
          <div class="danger_confirm_pill" id="danger_delete_data_pill">
            <span><?php echo profile_index_i18n('PROFILE_DANGER_TYPE_PREFIX'); ?> <code><?php echo profile_index_i18n('PROFILE_DANGER_DELETE_DATA_PHRASE'); ?></code></span>
            <input type="text" id="danger_delete_data_phrase" autocomplete="off" spellcheck="false" autocapitalize="characters" maxlength="32" aria-label="<?php echo profile_index_i18n('PROFILE_DANGER_DELETE_DATA_ARIA'); ?>">
            <button type="button" class="btn btn_delete" id="danger_delete_data_confirm" disabled><?php echo profile_index_i18n('PROFILE_CONFIRM_BUTTON'); ?></button>
          </div>
        </div>
      </div>

      <div class="danger_zone_row">
        <div class="danger_zone_text">
          <p class="help_text"><?php echo profile_index_i18n('PROFILE_DANGER_DELETE_ACCOUNT_TEXT'); ?></p>
        </div>
        <div class="danger_zone_controls">
          <div class="danger_confirm_pill" id="danger_delete_account_pill">
            <span><?php echo profile_index_i18n('PROFILE_DANGER_TYPE_PREFIX'); ?> <code><?php echo profile_index_i18n('PROFILE_DANGER_DELETE_ACCOUNT_PHRASE'); ?></code></span>
            <form id="danger_delete_account_form" method="POST" action="<?php echo Environment::appURL('api/v1/account/delete/'); ?>">
              <input type="text" id="danger_delete_account_phrase" name="confirm_phrase" autocomplete="off" spellcheck="false" autocapitalize="characters" maxlength="32" pattern="DELETE MY ACCOUNT" aria-label="<?php echo profile_index_i18n('PROFILE_DANGER_DELETE_ACCOUNT_ARIA'); ?>">
              <button type="submit" class="btn btn_delete" id="danger_delete_account_confirm" disabled><?php echo profile_index_i18n('PROFILE_CONFIRM_BUTTON'); ?></button>
            </form>
          </div>
        </div>
      </div>

      <div id="danger_zone_status" class="status_text" role="status" aria-live="polite"></div>
    </div>
  </section>

<?php
require __DIR__ . '/../business/_partials/route_gate_dialog.php';
if (!$hasActiveBusiness) {
  require __DIR__ . '/../business/_archive/partials/governance_dialogs.php';
}

echo PHP_EOL."<link rel=\"stylesheet\" href=\"" . Render::cssURL('settings') . "\">".PHP_EOL;
echo PHP_EOL.Render::jsScript('business-profile');

require_once \PayCal\Domain\Config\Environment::appHome().'html/footer.php';
