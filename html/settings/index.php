<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

/**
 * Settings.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Functions
 *
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */
$currentPage = 'PAGE_SETTINGS';
$message = '&nbsp;';

require_once __DIR__.'/../config.php';

if (function_exists('settings_index_i18n') === false) {
  function settings_index_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}
Authentication::redirectHomeIfUnauthenticated();

if (!headers_sent()) {
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: 0');
}

$pageLabel = settings_index_i18n('SETTINGS');
$pageLanguage = (string) User::current()->language;

/** @var User $user */
$user = User::current();
$csrfNonce = $user->generateFormNonce('settings');
$currentLanguage = (string) ($user->language ?? 'en');
$payFrequency = $user->pay_frequency ?? '';
if ('' === $payFrequency) {
  $payFrequency = ((int) $user->pay_period_length === 7) ? 'weekly' : 'biweekly';
}
$payAnchor = $user->pay_anchor ?? 'Monday';
$payPeriodEditorStart = Calendar::getCurrentPayPeriods($user)->start()->format('Y-m-d');
$graceDaysMin = (int) SystemLimits::get('editing_grace_days_min');
$graceDaysMax = (int) SystemLimits::get('editing_grace_days_max');
$currentGraceDays = (int) ($user->editing_grace_days ?? UserPreferenceDefaults::DEFAULT_EDITING_GRACE_DAYS);

$normalizeSliderPreference = static function (mixed $raw, array $legacyMap): int {
  $value = is_scalar($raw) ? strtolower(trim((string) $raw)) : '';
  if ($value === '') {
    return 0;
  }

  if (isset($legacyMap[$value])) {
    return $legacyMap[$value];
  }

  if (preg_match('/^-?\d+$/', $value) === 1) {
    return max(-5, min(5, (int) $value));
  }

  return 0;
};

$textSliderValue = $normalizeSliderPreference($user->text ?? UserPreferenceDefaults::DEFAULT_TEXT, [
  'small' => -2,
  'medium' => 0,
  'large' => 2,
  'x-large' => 5,
]);

$densitySliderValue = $normalizeSliderPreference($user->density ?? UserPreferenceDefaults::DEFAULT_DENSITY, [
  'tight' => -5,
  'compact' => -5,
  'comfy' => 0,
  'spacious' => 5,
  'zen' => 5,
]);

Lens::boot('settings');

if (InputSanitizer::getString('lens') === '1') {
  Lens::add('Settings Backend Snapshot', [
    'page' => $currentPage,
    'language' => (string) ($user->language ?? 'en'),
    'theme' => (string) ($user->theme ?? 'default'),
    'variant' => (string) ($user->variant ?? 'default'),
    'text' => (string) ($user->text ?? '0'),
    'density' => (string) ($user->density ?? '0'),
    'dyslexia_typography' => (string) ($user->dyslexia_typography ?? UserPreferenceDefaults::DEFAULT_DYSLEXIA_TYPOGRAPHY),
    'audio_feedback' => (string) ($user->audio_feedback ?? 'all'),
    'default_site_set' => !empty((string) ($user->default_site_id ?? '')),  ]);
}

require_once Environment::appHome().'html/header.php';

?>

  <h1 class="visually_hidden"><?php echo settings_index_i18n('SETTINGS'); ?></h1>

  <!-- SETTINGS JUMP NAV -->
  <nav class="settings_jump_nav" aria-label="<?php echo settings_index_i18n('SETTINGS'); ?> sections">
    <a href="#panel-calendar" class="settings_jump_link"><?php echo settings_index_i18n('CALENDAR'); ?></a>
    <a href="#panel-style" class="settings_jump_link"><?php echo settings_index_i18n('STYLE'); ?></a>
    <a href="#panel-audio" class="settings_jump_link"><?php echo settings_index_i18n('AUDIO'); ?></a>
    <a href="#panel-passkeys" class="settings_jump_link"><?php echo settings_index_i18n('SETTINGS_SECTION_PASSKEYS'); ?></a>
    <a href="#panel-security" class="settings_jump_link"><?php echo settings_index_i18n('SETTINGS_SECTION_SECURITY'); ?></a>
    <a href="#panel-data-portability" class="settings_jump_link"><?php echo settings_index_i18n('SETTINGS_SECTION_DATA_PORTABILITY'); ?></a>
  </nav>

  <!-- MODAL CHANGE EMAIL -->
  <dialog id="modal_change_email" aria-labelledby="modal_change_email_title" aria-describedby="modal_change_email_desc change_email_status">
  <form id="change_email_form" name="change_email_form" aria-label="<?php echo settings_index_i18n('CHANGE_EMAIL'); ?>">
  <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
  <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
  <input type="hidden" id="change_email_txn_id" value="">

    <section class="modal_header">
      <button type="button" class="btn btn_close" data-dialog-close="modal_change_email" aria-label="<?php echo settings_index_i18n('CLOSE'); ?>">&times;</button>
      <h2 id="modal_change_email_title" class="modal_title centered"><?php echo settings_index_i18n('CHANGE_EMAIL'); ?></h2>
    </section>

    <p id="modal_change_email_desc" class="visually_hidden"><?php echo settings_index_i18n('SETTINGS_CHANGE_EMAIL_DESC'); ?></p>

    <section class="modal_content f_column">
      <div id="change_email_step1_section">
        <div class="item_pair">
          <div class="item_label" data-tooltip="<?php echo settings_index_i18n('TOOLTIP_CURRENT_EMAIL'); ?>"><?php echo settings_index_i18n('CURRENT_EMAIL'); ?></div>
          <div class="item_value"><input type="email" name="current_email" value="<?php echo $user->email; ?>" readonly autocomplete="email" aria-readonly="true" aria-label="<?php echo settings_index_i18n('CURRENT_EMAIL'); ?>" disabled></div>
        </div><!-- item_pair -->

        <div class="item_pair">
          <div class="status_message centered" id="change_email_status" aria-live="assertive" role="status"></div>
        </div><!-- item_pair -->

        <div class="item_pair">
          <label for="change_email_new_email" class="item_label"><?php echo settings_index_i18n('SETTINGS_CHANGE_EMAIL_NEW_EMAIL_LABEL'); ?></label>
          <div class="item_value">
            <input type="email" id="change_email_new_email" placeholder="<?php echo settings_index_i18n('SETTINGS_CHANGE_EMAIL_NEW_EMAIL_PLACEHOLDER'); ?>" autocomplete="email" aria-describedby="change_email_status change_email_new_email_error">
            <div id="change_email_new_email_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div><!-- item_pair -->

        <div class="item_pair">
          <label for="change_email_confirm_email" class="item_label"><?php echo settings_index_i18n('SETTINGS_CHANGE_EMAIL_CONFIRM_EMAIL_LABEL'); ?></label>
          <div class="item_value">
            <input type="email" id="change_email_confirm_email" placeholder="<?php echo settings_index_i18n('CONFIRM_NO_TYPOS'); ?>" autocomplete="email" aria-describedby="change_email_status change_email_confirm_email_error">
            <div id="change_email_confirm_email_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div><!-- item_pair -->
      </div><!-- change_email_step1_section -->

      <div id="change_email_step2_section" hidden>
        <div class="item_pair">
          <div class="status_message centered" id="change_email_verify_status" aria-live="assertive" role="status"></div>
        </div><!-- item_pair -->

        <div class="item_pair">
          <label for="change_email_old_code" class="item_label"><?php echo settings_index_i18n('SETTINGS_CHANGE_EMAIL_OLD_CODE_LABEL'); ?></label>
          <div class="item_value">
            <div class="status_text compact_hint" id="old_email_hint"></div>
            <input type="text" id="change_email_old_code" class="code_input" placeholder="<?php echo settings_index_i18n('SETTINGS_CHANGE_EMAIL_CODE_PLACEHOLDER'); ?>" maxlength="6" inputmode="numeric" autocomplete="one-time-code" aria-describedby="change_email_verify_status old_email_hint change_email_old_code_error">
            <div id="change_email_old_code_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div><!-- item_pair -->

        <div class="item_pair">
          <label for="change_email_new_code" class="item_label"><?php echo settings_index_i18n('SETTINGS_CHANGE_EMAIL_NEW_CODE_LABEL'); ?></label>
          <div class="item_value">
            <div class="status_text compact_hint" id="new_email_hint"></div>
            <input type="text" id="change_email_new_code" class="code_input" placeholder="<?php echo settings_index_i18n('SETTINGS_CHANGE_EMAIL_CODE_PLACEHOLDER'); ?>" maxlength="6" inputmode="numeric" autocomplete="one-time-code" aria-describedby="change_email_verify_status new_email_hint change_email_new_code_error">
            <div id="change_email_new_code_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div><!-- item_pair -->

        <div class="item_pair">
          <div class="item_label">&nbsp;</div>
          <div class="item_value">
            <div id="change_email_expiry_timer" class="status_text compact_hint"></div>
          </div>
        </div><!-- item_pair -->
      </div><!-- change_email_step2_section -->

    </section><!-- modal_content -->

    <section class="modal_footer">
      <div class="modal_controls flex centered">
        <button id="change_email_start_btn" class="btn btn_primary f_just_center mar_md" type="button"><?php echo settings_index_i18n('SETTINGS_CHANGE_EMAIL_SEND_CODES'); ?></button>
        <button id="change_email_verify_btn" class="btn btn_primary f_just_center mar_md" type="button" hidden disabled aria-disabled="true"><?php echo settings_index_i18n('SETTINGS_CHANGE_EMAIL_COMPLETE'); ?></button>
        <button id="change_email_resend_btn" class="btn btn_secondary f_just_center mar_md" type="button" hidden><?php echo settings_index_i18n('SETTINGS_CHANGE_EMAIL_RESEND_CODES'); ?></button>
        <button class="btn btn_cancel f_just_center mar_md" id="change_email_prev_btn" type="button"><?php echo settings_index_i18n('CANCEL'); ?></button>
      </div>
    </section>
  </form>
  </dialog>

  <!-- MODAL EDIT DETAILS -->
  <dialog id="modal_edit_details" aria-labelledby="modal_edit_details_title" aria-describedby="modal_edit_details_desc">
  <form method="POST" action="<?php echo Environment::appURL('api/v1/account/info/update/'); ?>" id="edit_details_form" name="edit_details_form" aria-label="<?php echo settings_index_i18n('SETTINGS_ARIA_EDIT_ACCOUNT_DETAILS'); ?>">
  <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">

    <section class="modal_header">
      <button type="button" class="btn btn_close" data-dialog-close="modal_edit_details" aria-label="<?php echo settings_index_i18n('CLOSE'); ?>">&times;</button>
      <h2 id="modal_edit_details_title" class="modal_title centered"><?php echo settings_index_i18n('SETTINGS_ACCOUNT_DETAILS_TITLE'); ?></h2>
    </section>

    <p id="modal_edit_details_desc" class="visually_hidden"><?php echo settings_index_i18n('SETTINGS_ACCOUNT_DETAILS_DESC'); ?></p>
    <div id="edit_details_status" class="status_message centered" role="status" aria-live="polite"></div>

    <section class="modal_content f_column">
      <!-- TWO-COLUMN LAYOUT WITH RESPONSIVE COLLAPSE -->
      <div class="account_details_grid">
        <!-- LEFT COLUMN: MOST IMPORTANT INFO -->
        <div class="details_column left_column">

          <div class="item_pair">
            <label for="edit_details_email" class="item_label"><?php echo settings_index_i18n('EMAIL'); ?></label>
            <div class="item_value flex f_baseline">
              <input type="email" id="edit_details_email" value="<?php echo $user->email; ?>" disabled autocomplete="off" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_EMAIL_HOVER'); ?>">
              <?php
                $isRecoveryEmailVerified = (bool) ($user->recovery_email_verified ?? false);
                if ($isRecoveryEmailVerified) {
                  echo '<button type="button" id="edit_details_change_email_link" class="email_change_link" title="'.settings_index_i18n('SETTINGS_CHANGE_EMAIL_LINK_TITLE').'" data-hover-help="'.settings_index_i18n('SETTINGS_CHANGE_EMAIL_LINK_HOVER').'">'.settings_index_i18n('SETTINGS_CHANGE_EMAIL_LINK_TEXT').'</button>';
                } else {
                  echo '<span class="email_change_link_disabled" title="'.settings_index_i18n('SETTINGS_CHANGE_EMAIL_LINK_DISABLED_TITLE').'">'.settings_index_i18n('SETTINGS_CHANGE_EMAIL_LINK_DISABLED_TEXT').'</span>';
                }
              ?>
            </div>
          </div><!-- item_pair -->

          <!-- RECOVERY EMAIL SECTION -->
          <div class="item_pair">
            <div class="item_label"><?php echo settings_index_i18n('SETTINGS_RECOVERY_LABEL'); ?></div>
            <div class="item_value">
              <div id="recovery_email_status" class="flex f_column gap_sm">
                <div id="recovery_email_status_display" class="status_text" role="status" aria-live="polite" aria-atomic="true">
                  <?php
                    /** @var string $recoveryEmail */
                    $recoveryEmail = (string) ($user->recovery_email ?? '');
                    $isVerified = (bool) ($user->recovery_email_verified ?? false);
                    if ($isVerified && $recoveryEmail !== '') {
                      echo "✓ " . htmlspecialchars($recoveryEmail);
                    } else if ($recoveryEmail !== '') {
                      echo settings_index_i18n('SETTINGS_RECOVERY_PENDING_VERIFICATION') . " (" . htmlspecialchars($recoveryEmail) . ")";
                    } else {
                      echo settings_index_i18n('SETTINGS_RECOVERY_NOT_VERIFIED');
                    }
                  ?>
                </div>
              </div>
            </div>
          </div><!-- item_pair -->

          <!-- RECOVERY EMAIL INPUT SECTION -->
          <div class="item_pair" id="recovery_email_input_section">
            <div class="item_label">&nbsp;</div>
            <div class="item_value">
              <div class="recovery_email_input_row">
                <input type="email" id="recovery_email_input" placeholder="<?php echo settings_index_i18n('SETTINGS_RECOVERY_EMAIL_PLACEHOLDER'); ?>" autocomplete="email" aria-label="<?php echo settings_index_i18n('SETTINGS_RECOVERY_EMAIL_ARIA_LABEL'); ?>" aria-describedby="recovery_email_send_status recovery_email_input_error" value="<?php echo htmlspecialchars($user->recovery_email ?? ''); ?>" data-hover-help="<?php echo settings_index_i18n('SETTINGS_RECOVERY_EMAIL_INPUT_HOVER'); ?>">
                <button type="button" id="recovery_email_send_btn" class="btn btn_secondary mt_8" aria-label="<?php echo settings_index_i18n('SETTINGS_RECOVERY_SEND_VERIFICATION_CODE_ARIA'); ?>" aria-controls="recovery_email_verify_section" aria-describedby="recovery_email_send_status" data-hover-help="<?php echo settings_index_i18n('SETTINGS_RECOVERY_SEND_CODE_HOVER'); ?>"><?php echo settings_index_i18n('SETTINGS_RECOVERY_SEND_BUTTON'); ?></button>
              </div>
              <div id="recovery_email_send_status" class="status_message" role="status" aria-live="assertive"></div>
                <div id="recovery_email_input_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
            </div>
          </div><!-- item_pair -->

          <!-- RECOVERY EMAIL VERIFICATION SECTION (Hidden until code sent) -->
          <div class="item_pair" id="recovery_email_verify_section" hidden>
            <div class="item_label">&nbsp;</div>
            <div class="item_value">
                <input type="text" id="recovery_email_code_input" class="code_input" placeholder="<?php echo settings_index_i18n('SETTINGS_RECOVERY_VERIFICATION_CODE_PLACEHOLDER'); ?>" maxlength="6" inputmode="numeric" aria-label="<?php echo settings_index_i18n('SETTINGS_RECOVERY_VERIFICATION_CODE_ARIA'); ?>" aria-describedby="recovery_email_verify_status recovery_email_expiry_timer recovery_email_code_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_RECOVERY_VERIFICATION_CODE_HOVER'); ?>">
              <button type="button" id="recovery_email_verify_btn" class="btn btn_primary mt_8" aria-describedby="recovery_email_verify_status" data-hover-help="<?php echo settings_index_i18n('SETTINGS_RECOVERY_VERIFY_BUTTON_HOVER'); ?>"><?php echo settings_index_i18n('SETTINGS_RECOVERY_VERIFY_BUTTON'); ?></button>
              <div id="recovery_email_verify_status" class="status_message" role="status" aria-live="assertive"></div>
              <div id="recovery_email_expiry_timer" class="status_text compact_hint mt_8"></div>
                <div id="recovery_email_code_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
            </div>
          </div><!-- item_pair -->

          <div class="item_pair">
            <label for="edit_details_full_name" class="item_label"><?php echo settings_index_i18n('FULL_NAME'); ?></label>
            <div class="item_value">
              <input type="text" id="edit_details_full_name" name="full_name" value="<?php echo $user->full_name; ?>" autocomplete="name" required aria-describedby="edit_details_status edit_details_full_name_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_FULL_NAME_HOVER'); ?>">
              <div id="edit_details_full_name_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
            </div>
          </div><!-- item_pair -->

          <div class="item_pair">
            <label for="edit_details_phone" class="item_label"><?php echo settings_index_i18n('PHONE'); ?></label>
            <div class="item_value">
              <input type="tel" id="edit_details_phone" name="phone" value="<?php echo $user->phone; ?>" autocomplete="tel-national" maxlength="14" inputmode="numeric" pattern="\([0-9]{3}\) [0-9]{3}-[0-9]{4}" placeholder="<?php echo settings_index_i18n('ORGANIZATIONS_CONTACT_PHONE_PLACEHOLDER'); ?>" aria-describedby="edit_details_status edit_details_phone_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PHONE_HOVER'); ?>">
              <div id="edit_details_phone_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
            </div>
          </div><!-- item_pair -->

          <div class="item_pair">
            <label for="edit_details_province" class="item_label"><?php echo settings_index_i18n('PROVINCE'); ?></label>
            <div class="item_value">
              <select id="edit_details_province" name="province" aria-describedby="edit_details_status edit_details_province_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PROVINCE_HOVER'); ?>">
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
              <div id="edit_details_province_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
            </div>
          </div><!-- item_pair -->

          <div class="item_pair">
            <label for="edit_details_timezone_picker" class="item_label"><?php echo settings_index_i18n('TIMEZONE'); ?></label>
            <div class="item_value" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_TIMEZONE_HOVER'); ?>">
<?php
  $editTimezones = ['America/Toronto', 'America/Vancouver', 'America/Edmonton', 'America/Winnipeg', 'America/Halifax', 'America/St_Johns'];
$editCurrentTimezone = $user->timezone ?? '';
$editTimezoneOptionsHtml = '';
foreach ($editTimezones as $editTz) {
  $editTimezoneOptionsHtml .= Render::template('timezone-select-option', [
      '__TIMEZONE__' => $editTz,
      '__SELECTED__' => $editCurrentTimezone === $editTz ? ' selected' : '',
  ]);
}
$editTimezoneSelectHtml = Render::template('timezone-select', [
    '__FIELD_ID__' => 'edit_details_timezone_picker',
    '__FIELD_NAME__' => 'timezone',
    '__FIELD_ACCESSKEY__' => '6',
    '__ARIA_LABEL__' => settings_index_i18n('TIMEZONE_PICKER'),
    '__OPTIONS_HTML__' => $editTimezoneOptionsHtml,
]);
echo $editTimezoneSelectHtml;
?>
            </div>
          </div><!-- item_pair -->

        </div><!-- left_column -->

        <!-- RIGHT COLUMN: SECONDARY INFO -->
        <div class="details_column right_column">

          <div class="item_pair">
            <label for="edit_details_employment_type" class="item_label"><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_EMPLOYMENT_LABEL'); ?></label>
            <div class="item_value">
              <select id="edit_details_employment_type" name="employment_type" aria-describedby="edit_details_status edit_details_employment_type_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_EMPLOYMENT_HOVER'); ?>">
                <option value="">-</option>
                <option value="full_time"<?php if ('full_time' === (string) ($user->employment_type ?? '')) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_EMPLOYMENT_OPTION_FULL_TIME'); ?></option>
                <option value="part_time"<?php if ('part_time' === (string) ($user->employment_type ?? '')) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_EMPLOYMENT_OPTION_PART_TIME'); ?></option>
                <option value="contractor"<?php if ('contractor' === (string) ($user->employment_type ?? '')) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_EMPLOYMENT_OPTION_CONTRACTOR'); ?></option>
                <option value="casual"<?php if ('casual' === (string) ($user->employment_type ?? '')) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_EMPLOYMENT_OPTION_CASUAL'); ?></option>
              </select>
              <div id="edit_details_employment_type_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
            </div>
          </div><!-- item_pair -->

          <div class="item_pair">
            <label for="edit_details_job_title" class="item_label"><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_JOB_TITLE_LABEL'); ?></label>
            <div class="item_value">
              <input type="text" id="edit_details_job_title" name="job_title" value="<?php echo (string) ($user->job_title ?? ''); ?>" maxlength="80" aria-describedby="edit_details_status edit_details_job_title_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_JOB_TITLE_HOVER'); ?>">
              <div id="edit_details_job_title_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
            </div>
          </div><!-- item_pair -->

          <div class="item_pair">
            <label for="edit_details_department" class="item_label"><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_DEPARTMENT_LABEL'); ?></label>
            <div class="item_value">
              <input type="text" id="edit_details_department" name="department" value="<?php echo (string) ($user->department ?? ''); ?>" maxlength="80" aria-describedby="edit_details_status edit_details_department_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_DEPARTMENT_HOVER'); ?>">
              <div id="edit_details_department_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
            </div>
          </div><!-- item_pair -->

          <div class="item_pair">
            <label for="edit_details_hire_date" class="item_label"><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_HIRE_DATE_LABEL'); ?></label>
            <div class="item_value">
              <input type="date" id="edit_details_hire_date" name="hire_date" value="<?php echo (string) ($user->hire_date ?? ''); ?>" aria-describedby="edit_details_status edit_details_hire_date_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_HIRE_DATE_HOVER'); ?>">
              <div id="edit_details_hire_date_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
            </div>
          </div><!-- item_pair -->

          <div class="item_pair">
            <label for="edit_details_pay_rate" class="item_label"><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PAY_RATE_LABEL'); ?></label>
            <div class="item_value">
              <input type="text" id="edit_details_pay_rate" name="pay_rate" value="<?php echo (string) ($user->pay_rate ?? ''); ?>" maxlength="32" aria-describedby="edit_details_status edit_details_pay_rate_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PAY_RATE_HOVER'); ?>">
              <div id="edit_details_pay_rate_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
            </div>
          </div><!-- item_pair -->

          <div class="item_pair">
            <label for="edit_details_pay_rate_type" class="item_label"><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PAY_RATE_TYPE_LABEL'); ?></label>
            <div class="item_value">
              <select id="edit_details_pay_rate_type" name="pay_rate_type" aria-describedby="edit_details_status edit_details_pay_rate_type_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PAY_RATE_TYPE_HOVER'); ?>">
                <option value="">-</option>
                <option value="hourly"<?php if ('hourly' === (string) ($user->pay_rate_type ?? '')) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PAY_RATE_TYPE_OPTION_HOURLY'); ?></option>
                <option value="salary"<?php if ('salary' === (string) ($user->pay_rate_type ?? '')) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PAY_RATE_TYPE_OPTION_SALARY'); ?></option>
                <option value="day_rate"<?php if ('day_rate' === (string) ($user->pay_rate_type ?? '')) { echo ' selected'; } ?>><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PAY_RATE_TYPE_OPTION_DAY_RATE'); ?></option>
              </select>
              <div id="edit_details_pay_rate_type_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
            </div>
          </div><!-- item_pair -->

          <div class="item_pair">
            <label for="edit_details_address_line1" class="item_label"><?php echo settings_index_i18n('ADDRESS'); ?></label>
            <div class="item_value">
              <input type="text" id="edit_details_address_line1" name="address_line1" value="<?php echo (string) ($user->address_line1 ?? ''); ?>" maxlength="120" aria-describedby="edit_details_status edit_details_address_line1_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_ADDRESS_HOVER'); ?>">
              <div id="edit_details_address_line1_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
            </div>
          </div><!-- item_pair -->

          <div class="item_pair">
            <label for="edit_details_address_city" class="item_label"><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_CITY_LABEL'); ?></label>
            <div class="item_value">
              <input type="text" id="edit_details_address_city" name="address_city" value="<?php echo (string) ($user->address_city ?? ''); ?>" maxlength="80" aria-describedby="edit_details_status edit_details_address_city_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_CITY_HOVER'); ?>">
              <div id="edit_details_address_city_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
            </div>
          </div><!-- item_pair -->

          <div class="item_pair">
            <label for="edit_details_address_postal" class="item_label"><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_POSTAL_CODE_LABEL'); ?></label>
            <div class="item_value">
              <input type="text" id="edit_details_address_postal" name="address_postal" value="<?php echo (string) ($user->address_postal ?? ''); ?>" maxlength="20" aria-describedby="edit_details_status edit_details_address_postal_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_POSTAL_CODE_HOVER'); ?>">
              <div id="edit_details_address_postal_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
            </div>
          </div><!-- item_pair -->

        </div><!-- right_column -->
      </div><!-- account_details_grid -->

    </section><!-- modal_content -->

    <section class="modal_footer">
      <div class="modal_controls flex centered">
        <button id="edit_details_submit" class="btn btn_primary f_just_center mar_md" type="submit" aria-describedby="edit_details_status" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_SUBMIT_HOVER'); ?>"><?php echo settings_index_i18n('SAVE'); ?></button>
        <button class="btn btn_cancel f_just_center mar_md" id="edit_details_cancel_btn" type="button" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_CANCEL_HOVER'); ?>"><?php echo settings_index_i18n('CLOSE'); ?></button>
      </div>
    </section>

    <section class="modal_post_footer_sections" aria-label="<?php echo settings_index_i18n('SETTINGS_ARIA_RECOVERY_ACCOUNT_DELETION_ACTIONS'); ?>">
      <div class="details_inset_section" aria-labelledby="recovery_key_inset_title">
        <h3 id="recovery_key_inset_title" class="details_inset_title"><?php echo settings_index_i18n('SETTINGS_RECOVERY_KEY_TITLE'); ?></h3>
        <p id="recovery_key_help_text" class="details_inset_text"><?php echo settings_index_i18n('SETTINGS_RECOVERY_KEY_HELP_TEXT'); ?></p>
        <div id="create_recovery_key_status" class="status_message recovery_key_status_callout" role="status" aria-live="polite" aria-atomic="true"></div>
        <div class="details_inset_actions">
          <button id="create_recovery_key_btn" type="button" class="btn btn_secondary" aria-describedby="recovery_key_help_text create_recovery_key_status" data-hover-help="<?php echo settings_index_i18n('SETTINGS_RECOVERY_KEY_BUTTON_HOVER'); ?>"><?php echo settings_index_i18n('SETTINGS_RECOVERY_KEY_CREATE_BUTTON'); ?></button>
        </div>
      </div>

      <div class="details_inset_section details_inset_danger" aria-labelledby="delete_account_inset_title">
        <h3 id="delete_account_inset_title" class="details_inset_title"><?php echo settings_index_i18n('DELETE_ACCOUNT'); ?></h3>
        <p id="delete_account_warning_text" class="details_inset_text"><?php echo settings_index_i18n('SETTINGS_DELETE_ACCOUNT_WARNING_TEXT'); ?></p>
        <div class="details_inset_actions">
          <button id="call_delete_account_modal" type="button" class="btn btn_delete" aria-describedby="delete_account_warning_text" data-hover-help="<?php echo settings_index_i18n('SETTINGS_DELETE_ACCOUNT_BUTTON_HOVER'); ?>"><?php echo settings_index_i18n('DELETE_ACCOUNT'); ?></button>
        </div>
      </div>
    </section>

  </form>
  </dialog>

  <!-- MODAL DELETE ACCOUNT -->
<?php
ob_start();
?>
      <p class="centered"><?php echo settings_index_i18n('DELETE_ACCOUNT_MESSAGE'); ?></p>
  <p id="delete_account_desc" class="visually_hidden"><?php echo settings_index_i18n('SETTINGS_DELETE_ACCOUNT_DESC'); ?></p>

      <div class="item_pair">
        <div class="status_message centered" id="delete_account_status" aria-live="polite" role="status">&nbsp;</div>
      </div><!-- item_pair -->

      <div class="item_pair">
        <label for="delete_account_confirm_phrase" class="item_label"><?php echo sprintf(settings_index_i18n('SETTINGS_DELETE_ACCOUNT_CONFIRM_LABEL'), '<code>' . settings_index_i18n('SETTINGS_DELETE_ACCOUNT_CONFIRM_PHRASE') . '</code>'); ?></label>
        <div class="item_value">
          <input
            id="delete_account_confirm_phrase"
            type="text"
            name="confirm_phrase"
            autocomplete="off"
            inputmode="text"
            autocapitalize="characters"
            spellcheck="false"
            maxlength="32"
            pattern="<?php echo settings_index_i18n('SETTINGS_DELETE_ACCOUNT_CONFIRM_PHRASE'); ?>"
            placeholder="<?php echo settings_index_i18n('SETTINGS_DELETE_ACCOUNT_CONFIRM_PHRASE'); ?>"
            aria-describedby="delete_account_status delete_account_confirm_error"
          >
          <div id="delete_account_confirm_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
        </div>
      </div><!-- item_pair -->
<?php
$deleteAccountModalContent = (string) ob_get_clean();

ob_start();
?>
      <div class="modal_controls flex centered">
        <button id="delete_account_submit" class="btn btn_delete f_just_center mar_md"><?php echo settings_index_i18n('DELETE_ACCOUNT'); ?></button>
        <button class="btn btn_cancel f_just_center mar_md" id="delete_account_cancel_btn"><?php echo settings_index_i18n('CANCEL'); ?></button>
      </div>
<?php
$deleteAccountModalFooter = (string) ob_get_clean();

echo Render::dialog([
  'id' => 'modal_delete_account',
  'title' => settings_index_i18n('DELETE_ACCOUNT'),
  'titleId' => 'modal_delete_account_title',
  'ariaDescribedBy' => 'delete_account_desc delete_account_status',
  'formAttributes' => [
    'method' => 'POST',
    'action' => Environment::appURL('api/v1/account/delete/'),
    'id' => 'delete_account_form',
    'name' => 'delete_account_form',
    'aria-label' => settings_index_i18n('DELETE_ACCOUNT'),
  ],
  'formInnerHtml' => '<input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">',
  'contentHtml' => $deleteAccountModalContent,
  'footerHtml' => $deleteAccountModalFooter,
  'closeLabel' => settings_index_i18n('CLOSE'),
]);
?>

  <template id="template_site_row">
    <div class="flex w100 f_nowrap list_row">
      <div class="list_item w100">
          <input type="text" class="f_input centered w100" name="name" placeholder="<?php echo settings_index_i18n('NAME'); ?>" aria-label="<?php echo settings_index_i18n('NAME'); ?>" maxlength="30" required="">
      </div>
      <div class="list_item">
          <input type="text" class="f_input centered w100" name="wage" placeholder="<?php echo settings_index_i18n('WAGE'); ?>" aria-label="<?php echo settings_index_i18n('WAGE'); ?>" required="">
      </div>
      <div class="list_item">
          <input type="text" class="f_input centered w100" name="living_out_allowance" placeholder="<?php echo settings_index_i18n('LIVING_OUT_ALLOWANCE'); ?>" aria-label="<?php echo settings_index_i18n('LIVING_OUT_ALLOWANCE'); ?>" required="">
      </div>
      <div class="list_item">
          <input type="text" class="f_input centered w100" name="travel_hours" placeholder="<?php echo settings_index_i18n('TRAVEL_HOURS'); ?>" aria-label="<?php echo settings_index_i18n('TRAVEL_HOURS'); ?>" required="">
      </div>
      <div>
        <button class="btn_delete" aria-label="<?php echo settings_index_i18n('SETTINGS_ARIA_DELETE_SITE_ROW'); ?>">&#128465;</button>
      </div>
    </div>
  </template>

  <template id="template_site_select">
    <?php echo Render::siteSelect(); ?>
  </template>

<!-- CALENDAR PREFERENCES SECTION -->
<section class="panel" id="panel-calendar">
  <form id="account_calendar_form" method="POST" action="<?php echo Environment::appURL('api/v1/settings/calendar/update/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_ARIA_CALENDAR_PREFERENCES_FORM'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent"><?php echo settings_index_i18n('CALENDAR'); ?></h2>
    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('FOCUS'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" data-hover-help="Choose which date the calendar focuses first when opening.">
          <input type="radio" class="radio" id="calendar_autofocus_first" name="calendar_autofocus" value="first" <?php if ('first' === $user->calendar_autofocus) {
            echo 'checked';
          } ?>>
          <label for="calendar_autofocus_first"><?php echo settings_index_i18n('FIRST'); ?></label>
          <input type="radio" class="radio" id="calendar_autofocus_today" name="calendar_autofocus" value="today" <?php if ('today' === $user->calendar_autofocus || 'current' === $user->calendar_autofocus) {
            echo 'checked';
          } ?>>
          <label for="calendar_autofocus_today"><?php echo settings_index_i18n('SETTINGS_CALENDAR_LABEL_TODAY'); ?></label>
          <input type="radio" class="radio" id="calendar_autofocus_last" name="calendar_autofocus" value="last" <?php if ('last' === $user->calendar_autofocus) {
            echo 'checked';
          } ?>>
          <label for="calendar_autofocus_last"><?php echo settings_index_i18n('LAST'); ?></label>
        </div>
      </div>
    </div>

    <br>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_CALENDAR_LABEL_DATE'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" data-hover-help="Set weekday label length: narrow, short, or long names.">
          <input type="radio" class="radio" id="calendar_day_name_format_narrow" name="calendar_day_name_format" value="narrow" <?php if ('narrow' === $user->calendar_day_name_format) {
            echo 'checked';
          } ?>>
          <label for="calendar_day_name_format_narrow"><?php echo settings_index_i18n('NARROW'); ?></label>
          <input type="radio" class="radio" id="calendar_day_name_format_short" name="calendar_day_name_format" value="short" <?php if ('short' === $user->calendar_day_name_format) {
            echo 'checked';
          } ?>>
          <label for="calendar_day_name_format_short"><?php echo settings_index_i18n('SHORT'); ?></label>
          <input type="radio" class="radio" id="calendar_day_name_format_long" name="calendar_day_name_format" value="long" <?php if ('long' === $user->calendar_day_name_format) {
            echo 'checked';
          } ?>>
          <label for="calendar_day_name_format_long"><?php echo settings_index_i18n('LONG'); ?></label>
        </div>
      </div>
    </div>

    <br>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_CALENDAR_LABEL_LABEL'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" data-hover-help="Pick where day labels appear inside each calendar cell.">
          <input type="radio" class="radio" id="calendar_date_label_left" name="calendar_date_label_position" value="left" <?php if ('left' === $user->calendar_date_label_position) {
            echo 'checked';
          } ?>>
          <label for="calendar_date_label_left"><?php echo settings_index_i18n('SETTINGS_POSITION_LEFT'); ?></label>
          <input type="radio" class="radio" id="calendar_date_label_middle" name="calendar_date_label_position" value="middle" <?php if ('middle' === $user->calendar_date_label_position || 'center' === $user->calendar_date_label_position) {
            echo 'checked';
          } ?>>
          <label for="calendar_date_label_middle"><?php echo settings_index_i18n('SETTINGS_POSITION_MIDDLE'); ?></label>
          <input type="radio" class="radio" id="calendar_date_label_right" name="calendar_date_label_position" value="right" <?php if ('right' === $user->calendar_date_label_position) {
            echo 'checked';
          } ?>>
          <label for="calendar_date_label_right"><?php echo settings_index_i18n('SETTINGS_POSITION_RIGHT'); ?></label>
        </div>
      </div>
    </div>

    <br>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_CALENDAR_LABEL_AUDIO'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group calendar_long_pills" data-hover-help="Choose how focused calendar dates are announced: day only, month plus day, or full date with year.">
          <input type="radio" class="radio" id="calendar_audiolabels_number" name="calendar_audio_labels" value="number" <?php if ('number' === $user->calendar_audio_labels) {
            echo 'checked';
          } ?>>
          <label for="calendar_audiolabels_number">Day only (25)</label>
          <input type="radio" class="radio" id="calendar_audiolabels_shortdate" name="calendar_audio_labels" value="short" <?php if ('short' === $user->calendar_audio_labels) {
            echo 'checked';
          } ?>>
          <label for="calendar_audiolabels_shortdate">Month + day (March 25)</label>
          <input type="radio" class="radio" id="calendar_audiolabels_fulldate" name="calendar_audio_labels" value="long" <?php if ('long' === $user->calendar_audio_labels) {
            echo 'checked';
          } ?>>
          <label for="calendar_audiolabels_fulldate">Full date (March 25, 2026)</label>
        </div>
      </div>
    </div>

    <br>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_CALENDAR_LABEL_ENTRIES'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" data-hover-help="Choose where work entry values appear in day cells.">
          <input type="radio" class="radio" id="calendar_work_entry_left" name="calendar_work_entry_position" value="left" <?php if ('left' === $user->calendar_work_entry_position) {
            echo 'checked';
          } ?>>
          <label for="calendar_work_entry_left"><?php echo settings_index_i18n('SETTINGS_POSITION_LEFT'); ?></label>
          <input type="radio" class="radio" id="calendar_work_entry_middle" name="calendar_work_entry_position" value="middle" <?php if ('middle' === $user->calendar_work_entry_position || 'center' === $user->calendar_work_entry_position) {
            echo 'checked';
          } ?>>
          <label for="calendar_work_entry_middle"><?php echo settings_index_i18n('SETTINGS_POSITION_MIDDLE'); ?></label>
          <input type="radio" class="radio" id="calendar_work_entry_right" name="calendar_work_entry_position" value="right" <?php if ('right' === $user->calendar_work_entry_position) {
            echo 'checked';
          } ?>>
          <label for="calendar_work_entry_right"><?php echo settings_index_i18n('SETTINGS_POSITION_RIGHT'); ?></label>
        </div>
      </div>
    </div>

    <br>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_CALENDAR_LABEL_FIELDS'); ?></label>
      <div class="w75 work_entry_tags" data-hover-help="Toggle which work fields appear in calendar entries.">
        <input type="checkbox" name="calendar_work_entry_fields_hours" value="1" class="work_entry_field" id="work_entry_hours" <?php if ($user->calendar_work_entry_fields_hours) {
          echo 'checked';
        } ?>>
        <label for="work_entry_hours">Hours</label>
        
        <input type="checkbox" name="calendar_work_entry_fields_overtime" value="1" class="work_entry_field" id="work_entry_overtime" <?php if ($user->calendar_work_entry_fields_overtime) {
          echo 'checked';
        } ?>>
        <label for="work_entry_overtime">Overtime</label>
        
        <input type="checkbox" name="calendar_work_entry_fields_living_out" value="1" class="work_entry_field" id="work_entry_living_out" <?php if ($user->calendar_work_entry_fields_living_out) {
          echo 'checked';
        } ?>>
        <label for="work_entry_living_out">Living Out Allowance</label>
        
        <input type="checkbox" name="calendar_work_entry_fields_travel" value="1" class="work_entry_field" id="work_entry_travel" <?php if ($user->calendar_work_entry_fields_travel) {
          echo 'checked';
        } ?>>
        <label for="work_entry_travel">Travel</label>
      </div>
    </div>
  </form>
</section>


<!-- STYLE PREFERENCES SECTION -->
<section class="panel" id="panel-style">
  <form id="account_style_form" method="POST" action="<?php echo Environment::appURL('api/v1/settings/style/update/'); ?>" aria-label="<?php echo settings_index_i18n('STYLE_PREFS'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent"><?php echo settings_index_i18n('STYLE'); ?></h2>
    <div class="flex f_baseline w100">
      <label for="theme_picker" class="w25"><?php echo settings_index_i18n('THEME'); ?></label>
      <select id="theme_picker" name="theme" class="w50" aria-label="<?php echo settings_index_i18n('THEME_PICKER'); ?>" data-hover-help="Theme controls color palette and overall visual mood.">
        <option value="choose" disabled selected><?php echo settings_index_i18n('CHOOSE_A_THEME'); ?></option>
        <option value="" disabled>------ Core ------</option>
        <option value="paycal_blue"<?php if (in_array(($user->theme ?? 'paycal_blue'), ['paycal_blue'], true)) echo ' selected'; ?>>PayCal Blue</option>
        <option value="paycal_black"<?php if (in_array(($user->theme ?? 'paycal_blue'), ['paycal_black', 'paycal'], true)) echo ' selected'; ?>>PayCal Black</option>
        <option value="paycal_red"<?php if (in_array(($user->theme ?? 'paycal_blue'), ['paycal_red'], true)) echo ' selected'; ?>>PayCal Red</option>
        <option value="paycal_green"<?php if (in_array(($user->theme ?? 'paycal_blue'), ['paycal_green'], true)) echo ' selected'; ?>>PayCal Green</option>
        <option value="paycal_white"<?php if (in_array(($user->theme ?? 'paycal_blue'), ['paycal_white'], true)) echo ' selected'; ?>>PayCal White</option>

        <option value="" disabled>-- BeOS Lineage --</option>
        <option value="beos"<?php if (($user->theme ?? 'paycal') === 'beos') echo ' selected'; ?>>BeOS</option>
        <option value="haiku"<?php if (($user->theme ?? 'paycal') === 'haiku') echo ' selected'; ?>>Haiku</option>
        <option value="zeta"<?php if (($user->theme ?? 'paycal') === 'zeta') echo ' selected'; ?>>Zeta</option>

        <option value="" disabled>--- Linux Family ---</option>
        <option value="debian"<?php if (($user->theme ?? 'paycal') === 'debian') echo ' selected'; ?>>Debian</option>
        <option value="fedora"<?php if (($user->theme ?? 'paycal') === 'fedora') echo ' selected'; ?>>Fedora</option>
        <option value="mint"<?php if (($user->theme ?? 'paycal') === 'mint') echo ' selected'; ?>>Mint</option>
        <option value="linux"<?php if (($user->theme ?? 'paycal') === 'linux') echo ' selected'; ?>>Ubuntu</option>

        <option value="" disabled>---- Mac OS Family ----</option>
        <option value="system7"<?php if (($user->theme ?? 'paycal') === 'system7') echo ' selected'; ?>>Mac OS 7</option>
        <option value="system8"<?php if (($user->theme ?? 'paycal') === 'system8') echo ' selected'; ?>>Mac OS 8</option>
        <option value="macos9"<?php if (($user->theme ?? 'paycal') === 'macos9') echo ' selected'; ?>>Mac OS 9</option>
        <option value="macos"<?php if (($user->theme ?? 'paycal') === 'macos') echo ' selected'; ?>>Mac OS X</option>

        <option value="" disabled>------ Other ------</option>
        <option value="bluejeans"<?php if (in_array(($user->theme ?? 'paycal'), ['bluejeans', 'denim_dream'], true)) echo ' selected'; ?>>Bluejeans</option>
        <option value="garden"<?php if (in_array(($user->theme ?? 'paycal'), ['garden', 'sweater_weather'], true)) echo ' selected'; ?>>Garden</option>
        <option value="retro"<?php if (($user->theme ?? 'paycal') === 'retro') echo ' selected'; ?>>Retro</option>
        <option value="arcade"<?php if (($user->theme ?? 'paycal') === 'arcade') echo ' selected'; ?>>Arcade</option>

        <option value="" disabled>------ Sci-Fi -----</option>
        <option value="space_odyssey"<?php if (($user->theme ?? 'paycal') === 'space_odyssey') echo ' selected'; ?>>2001: A Space Odyssey</option>
        <option value="akira"<?php if (($user->theme ?? 'paycal') === 'akira') echo ' selected'; ?>>Akira</option>
        <option value="alien"<?php if (($user->theme ?? 'paycal') === 'alien') echo ' selected'; ?>>Alien</option>
        <option value="blade_runner"<?php if (($user->theme ?? 'paycal') === 'blade_runner') echo ' selected'; ?>>Blade Runner</option>
        <option value="dune"<?php if (($user->theme ?? 'paycal') === 'dune') echo ' selected'; ?>>Dune</option>
        <option value="star_trek"<?php if (($user->theme ?? 'paycal') === 'star_trek') echo ' selected'; ?>>Star Trek</option>
        <option value="star_wars"<?php if (($user->theme ?? 'paycal') === 'star_wars') echo ' selected'; ?>>Star Wars</option>
        <option value="fifth_element"<?php if (($user->theme ?? 'paycal') === 'fifth_element') echo ' selected'; ?>>The Fifth Element</option>
        <option value="matrix"<?php if (($user->theme ?? 'paycal') === 'matrix') echo ' selected'; ?>>The Matrix</option>
        <option value="tron"<?php if (($user->theme ?? 'paycal') === 'tron') echo ' selected'; ?>>TRON</option>

        <option value="" disabled>----- Windows -----</option>
        <option value="win10"<?php if (($user->theme ?? 'paycal') === 'win10') echo ' selected'; ?>>Windows 10</option>
        <option value="win95"<?php if (($user->theme ?? 'paycal') === 'win95') echo ' selected'; ?>>Windows 95</option>
        <option value="win98"<?php if (($user->theme ?? 'paycal') === 'win98') echo ' selected'; ?>>Windows 98</option>
        <option value="winxp"<?php if (($user->theme ?? 'paycal') === 'winxp') echo ' selected'; ?>>Windows XP</option>
      </select>
      <label for="variant_picker" class="visually_hidden"><?php echo settings_index_i18n('VARIANT'); ?></label>
      <select id="variant_picker" name="variant" class="w25" aria-label="<?php echo settings_index_i18n('VARIANT_PICKER'); ?>" data-hover-help="Variant switches between light and dark treatment.">
        <option value="light"<?php if (($user->variant ?? 'dark') === 'light') echo ' selected'; ?>><?php echo settings_index_i18n('LIGHT'); ?></option>
        <option value="dark"<?php if (($user->variant ?? 'dark') === 'dark') echo ' selected'; ?>><?php echo settings_index_i18n('DARK'); ?></option>
      </select>
    </div>

    <br>

    <div class="flex f_baseline w100">
      <label for="language_picker" class="w25"><?php echo settings_index_i18n('LANG'); ?></label>
      <select id="language_picker" name="language" class="w75" aria-label="<?php echo settings_index_i18n('LANGUAGE_PICKER'); ?>" data-hover-help="Language updates labels and UI copy throughout PayCal.">
        <option value="choose" selected><?php echo settings_index_i18n('CHOOSE_A_LANGUAGE'); ?></option>
        <option value="nl"<?php if ($currentLanguage === 'nl') {
          echo ' selected';
        } ?>><?php echo settings_index_i18n('DUTCH'); ?></option>
        <option value="en"<?php if ($currentLanguage === 'en') {
          echo ' selected';
        } ?>><?php echo settings_index_i18n('ENGLISH'); ?></option>
        <option value="fr"<?php if ($currentLanguage === 'fr') {
          echo ' selected';
        } ?>><?php echo settings_index_i18n('FRENCH'); ?></option>
        <option value="de"<?php if ($currentLanguage === 'de') {
          echo ' selected';
        } ?>><?php echo settings_index_i18n('GERMAN'); ?></option>
        <option value="hi"<?php if ($currentLanguage === 'hi') {
          echo ' selected';
        } ?>><?php echo settings_index_i18n('HINDI'); ?></option>
        <option value="it"<?php if ($currentLanguage === 'it') {
          echo ' selected';
        } ?>><?php echo settings_index_i18n('ITALIAN'); ?></option>
        <option value="pt"<?php if ($currentLanguage === 'pt') {
          echo ' selected';
        } ?>><?php echo settings_index_i18n('PORTUGUESE'); ?></option>
        <option value="es"<?php if ($currentLanguage === 'es') {
          echo ' selected';
        } ?>><?php echo settings_index_i18n('SPANISH'); ?></option>
        <option value="tl"<?php if ($currentLanguage === 'tl') {
          echo ' selected';
        } ?>><?php echo settings_index_i18n('TAGALOG'); ?></option>
        <option value="tr"<?php if ($currentLanguage === 'tr') {
          echo ' selected';
        } ?>><?php echo settings_index_i18n('TURKISH'); ?></option>
      </select>
    </div>

    <br>

    <div class="flex f_baseline w100" id="text">
      <label class="w25"><?php echo settings_index_i18n('TEXT'); ?></label>
      <div class="w75">
        <div class="security_slider_row security_slider_row_compact" data-hover-help="Text size adjustment in pixels. 0 is the default.">
          <span class="security_slider_edge">-5px</span>
          <input type="range" id="text_slider" name="text" min="-5" max="5" step="1" value="<?php echo $textSliderValue; ?>" aria-valuemin="-5" aria-valuemax="5" aria-valuenow="<?php echo $textSliderValue; ?>" aria-label="Text size adjustment in pixels" aria-describedby="text_slider_value">
          <span class="security_slider_edge">+5px</span>
        </div>
        <p id="text_slider_value" class="security_level_value"><?php echo (($textSliderValue > 0) ? '+' : '').$textSliderValue; ?>px</p>
      </div>
    </div>

    <br>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('DENSITY'); ?></label>
      <div class="w75">
        <div class="security_slider_row security_slider_row_compact" data-hover-help="Density adjustment in pixels. 0 is the default.">
          <span class="security_slider_edge">-5px</span>
          <input type="range" id="density_slider" name="density" min="-5" max="5" step="1" value="<?php echo $densitySliderValue; ?>" aria-valuemin="-5" aria-valuemax="5" aria-valuenow="<?php echo $densitySliderValue; ?>" aria-label="Density adjustment in pixels" aria-describedby="density_slider_value">
          <span class="security_slider_edge">+5px</span>
        </div>
        <p id="density_slider_value" class="security_level_value"><?php echo (($densitySliderValue > 0) ? '+' : '').$densitySliderValue; ?>px</p>
      </div>
    </div>

    <br>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_STYLE_LABEL_TYPOGRAPHY'); ?></label>
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

    <br>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_STYLE_LABEL_SIDEBAR'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" data-hover-help="Set primary navigation position on left or right.">
          <input class="radio" type="radio" id="nav_primary_left" name="nav_position_primary" value="left" <?php if ('right' !== (string) ($user->nav_position_primary ?? UserPreferenceDefaults::DEFAULT_NAV_POSITION_PRIMARY)) {
            echo 'checked';
          } ?>>
          <label for="nav_primary_left"><?php echo settings_index_i18n('SETTINGS_POSITION_LEFT'); ?></label>
          <input class="radio" type="radio" id="nav_primary_right" name="nav_position_primary" value="right" <?php if ('right' === ($user->nav_position_primary ?? '')) {
            echo 'checked';
          } ?>>
          <label for="nav_primary_right"><?php echo settings_index_i18n('SETTINGS_POSITION_RIGHT'); ?></label>
        </div>
      </div>
    </div>

    <br>
  </form>
</section>

<!-- AUDIO PREFERENCES SECTION -->
<section class="panel" id="panel-audio">
  <form id="account_audio_form" method="POST" action="<?php echo Environment::appURL('api/v1/settings/audio/update/'); ?>" aria-label="<?php echo settings_index_i18n('AUDIO_PREFS'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent"><?php echo settings_index_i18n('AUDIO'); ?></h2>
    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('CUES'); ?></label>
      <div class="w75">
        <div id="audio_feedback_group" class="radio_group" role="radiogroup" aria-label="<?php echo settings_index_i18n('SETTINGS_AUDIO_FEEDBACK_MODE_ARIA'); ?>" data-hover-help="Cues off disables spoken feedback and locks voice selection.">
          <input class="radio" type="radio" id="audio_feedback_none" name="audio_feedback" value="none" data-tts="Off" <?php echo ('none' === $user->audio_feedback) ? 'checked' : ''; ?>>
          <label for="audio_feedback_none">Off</label>
          <input class="radio" type="radio" id="audio_feedback_all" name="audio_feedback" value="all" data-tts="On" <?php echo (('all' === $user->audio_feedback) || ('base' === $user->audio_feedback) || ('' === (string) $user->audio_feedback)) ? 'checked' : ''; ?>>
          <label for="audio_feedback_all">On</label>
        </div>
      </div>
    </div>

    <br>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('VOICE'); ?></label>
      <div id="voice_picker" class="w75" aria-describedby="voice_picker_disabled_hint" data-hover-help="Voice changes speech output only, never your saved data.">
        <div class="radio_group">
          <input class="radio" type="radio" id="voice_google_en_us_1" name="voice" value="google_en_us_1" data-tts="Google US Voice 1" <?php echo (($user->voice ?? 'system_default') === 'google_en_us_1') ? 'checked' : ''; ?>>
          <label for="voice_google_en_us_1">US Voice 1</label>
          <input class="radio" type="radio" id="voice_google_en_us_2" name="voice" value="google_en_us_2" data-tts="Google US Voice 2" <?php echo (($user->voice ?? 'system_default') === 'google_en_us_2') ? 'checked' : ''; ?>>
          <label for="voice_google_en_us_2">US Voice 2</label>
          <input class="radio" type="radio" id="voice_google_en_ca_1" name="voice" value="google_en_ca_1" data-tts="Google Canada Voice 1" <?php echo (($user->voice ?? 'system_default') === 'google_en_ca_1') ? 'checked' : ''; ?>>
          <label for="voice_google_en_ca_1">CA Voice 1</label>
          <input class="radio" type="radio" id="voice_system_default" name="voice" value="system_default" data-tts="System Default" <?php echo (($user->voice ?? 'system_default') === 'system_default') ? 'checked' : ''; ?>>
          <label for="voice_system_default">Default</label>
          <input class="radio" type="radio" id="voice_system_female" name="voice" value="system_female" data-tts="System Female" <?php echo (($user->voice ?? 'system_default') === 'system_female') ? 'checked' : ''; ?>>
          <label for="voice_system_female">Female</label>
          <input class="radio" type="radio" id="voice_system_male" name="voice" value="system_male" data-tts="System Male" <?php echo (($user->voice ?? 'system_default') === 'system_male') ? 'checked' : ''; ?>>
          <label for="voice_system_male">Male</label>
        </div>
        <p id="voice_picker_disabled_hint" class="voice_picker_disabled_hint"><?php echo settings_index_i18n('SETTINGS_VOICE_PICKER_ENABLE_CUES'); ?></p>
      </div>
    </div>
  </form>
</section>

<!-- PASSKEYS SECTION -->
<section class="panel" id="panel-passkeys">
  <div class="w100">
    <h2 class="heading-accent"><?php echo settings_index_i18n('SETTINGS_SECTION_PASSKEYS'); ?></h2>

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
      <div id="add_passkey_status" class="status_message passkey_action_status" role="status" aria-live="polite" aria-atomic="true"></div>
    </div>
  </div>
</section>

<!-- SECURITY SECTION -->
<section class="panel" id="panel-security">
  <form id="account_security_timeout_form" method="POST" action="<?php echo Environment::appURL('api/v1/account/security/update/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_TIMEOUT_FORM_ARIA'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent"><?php echo settings_index_i18n('SETTINGS_SECTION_SECURITY'); ?></h2>
    <p class="help_text"><?php echo settings_index_i18n('SETTINGS_SECURITY_SESSION_TIMING_HELP'); ?></p>

    <div class="security_level_card">
      <label for="security_level_slider" class="security_level_label"><?php echo settings_index_i18n('SETTINGS_SECURITY_LEVEL'); ?></label>
      <div class="security_slider_row">
        <span class="security_slider_edge"><?php echo settings_index_i18n('SETTINGS_SECURITY_LOW'); ?></span>
        <input id="security_level_slider" type="range" min="0" max="100" step="1" value="50" aria-label="<?php echo settings_index_i18n('SETTINGS_SECURITY_LEVEL_SLIDER_ARIA'); ?>" data-hover-help="<?php echo settings_index_i18n('SETTINGS_SECURITY_LEVEL_HOVER_HELP'); ?>">
        <span class="security_slider_edge"><?php echo settings_index_i18n('SETTINGS_SECURITY_HIGH'); ?></span>
      </div>
      <div id="security_level_value" class="security_level_value"><?php echo settings_index_i18n('SETTINGS_SECURITY_BALANCED'); ?></div>
      <p id="security_level_hint" class="help_text"><?php echo settings_index_i18n('SETTINGS_SECURITY_LEVEL_HINT'); ?></p>
    </div>

    <div class="security_timeouts_table_wrap">
      <table class="security_datagrid security_datagrid_table" aria-label="<?php echo settings_index_i18n('SETTINGS_SECURITY_DERIVED_TIMEOUTS_ARIA'); ?>">
        <colgroup>
          <col class="security_col_activity">
          <col class="security_col_timeout">
          <col class="security_col_session">
        </colgroup>
        <thead>
          <tr class="security_datagrid_row security_datagrid_header">
            <th scope="col"><?php echo settings_index_i18n('SETTINGS_SECURITY_TABLE_ACTION'); ?></th>
            <th scope="col"><?php echo settings_index_i18n('SETTINGS_SECURITY_TABLE_TTL'); ?></th>
            <th scope="col"><?php echo settings_index_i18n('SETTINGS_SECURITY_TABLE_LEFT'); ?></th>
          </tr>
        </thead>
        <tbody>
          <tr class="security_datagrid_row">
            <th scope="row" id="security_row_signout" data-hover-help="<?php echo settings_index_i18n('SETTINGS_SECURITY_ROW_AUTO_SIGNOUT_HOVER'); ?>"><?php echo settings_index_i18n('SETTINGS_SECURITY_ROW_AUTO_SIGNOUT'); ?></th>
            <td id="security_timeout_signout">60 minutes</td>
            <td id="security_remaining_signout">-</td>
          </tr>
          <tr class="security_datagrid_row">
            <th scope="row" id="security_row_account" data-hover-help="<?php echo settings_index_i18n('SETTINGS_SECURITY_ROW_ACCOUNT_EDIT_HOVER'); ?>"><?php echo settings_index_i18n('SETTINGS_SECURITY_ROW_ACCOUNT_EDIT'); ?></th>
            <td id="security_timeout_account">15 minutes</td>
            <td id="security_remaining_account">-</td>
          </tr>
          <tr class="security_datagrid_row">
            <th scope="row" id="security_row_calendar" data-hover-help="<?php echo settings_index_i18n('SETTINGS_SECURITY_ROW_CALENDAR_EDIT_HOVER'); ?>"><?php echo settings_index_i18n('SETTINGS_SECURITY_ROW_CALENDAR_EDIT'); ?></th>
            <td id="security_timeout_calendar">60 minutes</td>
            <td id="security_remaining_calendar">-</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="security_level_card">
      <label for="emergency_signout_window_ms" class="security_level_label"><?php echo settings_index_i18n('SETTINGS_SECURITY_EMERGENCY_SIGNOUT'); ?></label>
      <div class="security_slider_row security_slider_row_compact">
        <span class="security_slider_edge">0.2s</span>
        <input id="emergency_signout_window_ms" name="emergency_signout_window_ms" type="range" min="200" max="2000" step="200" value="<?php echo htmlspecialchars((string) ($user->emergency_signout_window_ms ?? '600'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_SECURITY_EMERGENCY_WINDOW_ARIA'); ?>" data-hover-help="<?php echo settings_index_i18n('SETTINGS_SECURITY_EMERGENCY_WINDOW_HOVER'); ?>">
        <span class="security_slider_edge">2.0s</span>
      </div>
      <p id="emergency_signout_hint" class="help_text">Press ESC x3 in <span id="emergency_signout_window_ms_value"><?php echo htmlspecialchars(number_format(((int) ($user->emergency_signout_window_ms ?? '600')) / 1000, 1), ENT_QUOTES, 'UTF-8'); ?></span>s to sign out to a safe site.</p>
    </div>

    <input type="hidden" id="session_timeout" name="session_timeout" value="<?php echo htmlspecialchars((string) ($user->session_timeout ?? '3600'), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" id="form_ttl_settings" name="form_ttl_settings" value="<?php echo htmlspecialchars((string) ($user->form_ttl_settings ?? '3600'), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" id="form_ttl_calendar" name="form_ttl_calendar" value="<?php echo htmlspecialchars((string) ($user->form_ttl_calendar ?? '3600'), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" id="form_ttl_general" name="form_ttl_general" value="<?php echo htmlspecialchars((string) ($user->form_ttl_general ?? '3600'), ENT_QUOTES, 'UTF-8'); ?>">
  </form>
</section>

<!-- IMPORT CONFIRM DIALOG -->
<dialog id="modal_import_confirm" aria-labelledby="modal_import_confirm_title" aria-describedby="modal_import_confirm_desc">
  <section class="modal_header">
    <button type="button" class="btn btn_close" data-dialog-close="modal_import_confirm" aria-label="<?php echo settings_index_i18n('CLOSE'); ?>">&times;</button>
    <h2 id="modal_import_confirm_title" class="modal_title centered">Confirm Import</h2>
  </section>
  <section class="modal_content f_column">
    <p id="modal_import_confirm_desc">This will overwrite your existing sites and work entries with the staged import data. This action cannot be undone.</p>
    <p id="modal_import_confirm_summary" class="centered muted"></p>
  </section>
  <section class="modal_footer">
    <div class="modal_controls flex centered">
      <button id="import_confirm_proceed_btn" type="button" class="btn btn_primary f_just_center mar_md">Commit Import</button>
      <button id="import_confirm_cancel_btn" type="button" class="btn btn_cancel f_just_center mar_md"><?php echo settings_index_i18n('CANCEL'); ?></button>
    </div>
  </section>
</dialog>

<!-- DATA PORTABILITY SECTION -->
<section class="panel" id="panel-data-portability">
  <form id="account_data_portability_form" method="POST" action="<?php echo Environment::appURL('api/v1/account/data/export/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_SECTION_DATA_PORTABILITY_EXPORT_IMPORT'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent"><?php echo settings_index_i18n('SETTINGS_SECTION_DATA_PORTABILITY'); ?></h2>
    <p class="help_text">Export creates a portable account package (user profile settings, sites, and work entries). Import runs in two stages: prepare validates and stages data, commit applies changes.</p>
    <p class="data_portability_warning" role="note"><strong>Warning:</strong> Export generates plaintext JSON data, including work details. Treat export files as sensitive and store or transfer securely.</p>

    <div id="data_portability_status" class="status_message" role="status" aria-live="polite" aria-atomic="true"></div>

    <div class="data_portability_grid">
      <section class="data_portability_column" aria-labelledby="data_export_title">
        <h3 id="data_export_title">Stage A: Export</h3>
        <p class="help_text">1) Click Export. 2) Review counts/checksum. 3) Copy or download the payload.</p>
        <div class="data_portability_actions_row">
          <button id="data_export_run_btn" type="button" class="btn btn_primary">Export Account Data</button>
          <button id="data_export_copy_btn" type="button" class="btn btn_secondary" disabled aria-disabled="true">Copy Payload</button>
          <button id="data_export_download_btn" type="button" class="btn btn_secondary" disabled aria-disabled="true">Download JSON</button>
        </div>
        <div class="data_portability_meta">
          <div><strong>Reference:</strong> <span id="data_export_reference">-</span></div>
          <div><strong>Checksum (SHA-256):</strong> <span id="data_export_checksum">-</span></div>
          <div><strong>Counts:</strong> <span id="data_export_counts">-</span></div>
        </div>
        <label for="data_export_payload" class="item_label">Export Payload JSON</label>
        <textarea id="data_export_payload" class="data_portability_textarea" rows="12" readonly aria-describedby="data_portability_status" placeholder="Export payload will appear here after running export."></textarea>
      </section>

      <section class="data_portability_column" aria-labelledby="data_import_title">
        <h3 id="data_import_title">Stage B: Import</h3>
        <p class="help_text">1) Paste payload. 2) Prepare Import validates and stages. 3) Commit Import applies data to your account.</p>
        <label for="data_import_payload_json" class="item_label">Import Payload JSON</label>
        <textarea id="data_import_payload_json" class="data_portability_textarea" rows="12" aria-describedby="data_portability_status" placeholder="Paste exported payload JSON here."></textarea>
        <div class="data_portability_actions_row">
          <button id="data_import_prepare_btn" type="button" class="btn btn_secondary">Prepare Import</button>
          <button id="data_import_commit_btn" type="button" class="btn btn_primary" disabled aria-disabled="true">Commit Import</button>
        </div>
        <div class="data_portability_meta">
          <div><strong>Import ID:</strong> <span id="data_import_id">-</span></div>
          <div><strong>Prepared Checksum:</strong> <span id="data_import_checksum">-</span></div>
          <div><strong>Prepared Counts:</strong> <span id="data_import_counts">-</span></div>
          <div><strong>Session TTL:</strong> <span id="data_import_expires">-</span></div>
          <div><strong>Commit Result:</strong> <span id="data_import_result_counts">-</span></div>
        </div>
      </section>
    </div>

    <section class="data_portability_log_section" aria-labelledby="data_portability_log_title">
      <h3 id="data_portability_log_title"><?php echo settings_index_i18n('SETTINGS_SECTION_DATA_PORTABILITY_ACTION_LOG'); ?></h3>
      <ol id="data_portability_action_log" class="data_portability_action_log" aria-live="polite" aria-atomic="false"></ol>
    </section>
  </form>
</section>

<!-- DEBUGGING SECTION -->
<section class="panel" id="panel-debugging" data-hover-help="<?php echo settings_index_i18n('SETTINGS_DEBUG_PANEL_HOVER_HELP'); ?>">
  <form id="account_debug_form" method="POST" action="<?php echo Environment::appURL('api/v1/settings/debug/update/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_DEBUGGING_FORM_ARIA'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent"><?php echo settings_index_i18n('SETTINGS_SECTION_DEBUGGING_OPTIONAL'); ?></h2>
    <p class="help_text"><?php echo settings_index_i18n('SETTINGS_DEBUG_INTRO'); ?></p>
    <p class="help_text"><?php echo settings_index_i18n('SETTINGS_DEBUG_PERF_NOTE'); ?></p>

    <div class="flex f_baseline w100">
      <div class="w100">
        <p class="help_text"><?php echo settings_index_i18n('SETTINGS_DEBUG_CONSOLE_HELP'); ?></p>
        <div class="radio_group pill_group" data-hover-help="<?php echo settings_index_i18n('SETTINGS_DEBUG_CONSOLE_HOVER'); ?>">
          <input class="radio" type="radio" id="debug_console_enabled_off" name="debug_console_enabled" value="0" <?php echo ('1' === (string) ($user->debug_console_enabled ?? UserPreferenceDefaults::DEFAULT_DEBUG_CONSOLE_ENABLED)) ? '' : 'checked'; ?>>
          <label for="debug_console_enabled_off"><?php echo settings_index_i18n('SETTINGS_DEBUG_OPTION_OFF_DEFAULT'); ?></label>
          <input class="radio" type="radio" id="debug_console_enabled_on" name="debug_console_enabled" value="1" <?php echo ('1' === (string) ($user->debug_console_enabled ?? UserPreferenceDefaults::DEFAULT_DEBUG_CONSOLE_ENABLED)) ? 'checked' : ''; ?>>
          <label for="debug_console_enabled_on"><?php echo settings_index_i18n('SETTINGS_DEBUG_OPTION_ON_MORE_DETAILS'); ?></label>
        </div>
      </div>
    </div>

    <br>

    <div class="flex f_baseline w100">
      <div class="w100">
        <p class="help_text"><?php echo settings_index_i18n('SETTINGS_DEBUG_FINE_GRAINED_HELP'); ?></p>
        <div class="radio_group pill_group" data-hover-help="<?php echo settings_index_i18n('SETTINGS_DEBUG_FINE_GRAINED_HOVER'); ?>">
          <input class="radio" type="radio" id="debug_fine_grained_enabled_off" name="debug_fine_grained_enabled" value="0" <?php echo ('1' === (string) ($user->debug_fine_grained_enabled ?? UserPreferenceDefaults::DEFAULT_DEBUG_FINE_GRAINED_ENABLED)) ? '' : 'checked'; ?>>
          <label for="debug_fine_grained_enabled_off"><?php echo settings_index_i18n('SETTINGS_DEBUG_OPTION_OFF_DEFAULT'); ?></label>
          <input class="radio" type="radio" id="debug_fine_grained_enabled_on" name="debug_fine_grained_enabled" value="1" <?php echo ('1' === (string) ($user->debug_fine_grained_enabled ?? UserPreferenceDefaults::DEFAULT_DEBUG_FINE_GRAINED_ENABLED)) ? 'checked' : ''; ?>>
          <label for="debug_fine_grained_enabled_on"><?php echo settings_index_i18n('SETTINGS_DEBUG_OPTION_ON_MORE_DETAILS'); ?></label>
        </div>
      </div>
    </div>

    <br>

    <div class="flex f_baseline w100">
      <div class="w100">
        <p class="help_text"><?php echo settings_index_i18n('SETTINGS_DEBUG_NETWORK_HELP'); ?></p>
        <div class="radio_group pill_group" data-hover-help="<?php echo settings_index_i18n('SETTINGS_DEBUG_NETWORK_HOVER'); ?>">
          <input class="radio" type="radio" id="debug_network_enabled_off" name="debug_network_enabled" value="0" <?php echo ('1' === (string) ($user->debug_network_enabled ?? UserPreferenceDefaults::DEFAULT_DEBUG_NETWORK_ENABLED)) ? '' : 'checked'; ?>>
          <label for="debug_network_enabled_off"><?php echo settings_index_i18n('SETTINGS_DEBUG_OPTION_OFF_DEFAULT'); ?></label>
          <input class="radio" type="radio" id="debug_network_enabled_on" name="debug_network_enabled" value="1" <?php echo ('1' === (string) ($user->debug_network_enabled ?? UserPreferenceDefaults::DEFAULT_DEBUG_NETWORK_ENABLED)) ? 'checked' : ''; ?>>
          <label for="debug_network_enabled_on"><?php echo settings_index_i18n('SETTINGS_DEBUG_OPTION_ON_MORE_DETAILS'); ?></label>
        </div>
      </div>
    </div>
  </form>
</section>

<?php

echo PHP_EOL."<link rel=\"stylesheet\" href=\"" . Render::cssURL('settings') . "\">".PHP_EOL;
echo PHP_EOL.Render::jsScript('settings');

require_once Environment::appHome().'html/footer.php';
