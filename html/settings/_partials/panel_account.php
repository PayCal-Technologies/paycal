<?php declare(strict_types=1);

namespace PayCal\Domain;

$accountTimezones = ['America/Toronto', 'America/Vancouver', 'America/Edmonton', 'America/Winnipeg', 'America/Halifax', 'America/St_Johns'];
$accountCurrentTimezone = $user->timezone ?? '';
$accountTimezoneOptionsHtml = '';
foreach ($accountTimezones as $accountTz) {
  $accountTimezoneOptionsHtml .= Render::template('timezone-select-option', [
      '__TIMEZONE__' => $accountTz,
      '__SELECTED__' => $accountCurrentTimezone === $accountTz ? ' selected' : '',
  ]);
}
$accountTimezoneSelectHtml = Render::template('timezone-select', [
    '__FIELD_ID__' => 'edit_details_timezone_picker',
    '__FIELD_NAME__' => 'timezone',
    '__FIELD_ACCESSKEY__' => '6',
    '__ARIA_LABEL__' => settings_index_i18n('TIMEZONE_PICKER'),
    '__OPTIONS_HTML__' => $accountTimezoneOptionsHtml,
]);
$accountPhoneRaw = (string) ($user->phone ?? '');
$accountPhoneDigits = preg_replace('/\D+/', '', $accountPhoneRaw) ?? '';
if (strlen($accountPhoneDigits) === 11 && str_starts_with($accountPhoneDigits, '1')) {
  $accountPhoneDigits = substr($accountPhoneDigits, 1);
}
$accountPhoneDisplay = $accountPhoneRaw;
if (strlen($accountPhoneDigits) === 10) {
  $accountPhoneDisplay = sprintf(
      '(%s) %s-%s',
      substr($accountPhoneDigits, 0, 3),
      substr($accountPhoneDigits, 3, 3),
      substr($accountPhoneDigits, 6, 4)
  );
}

?>
<section class="panel settings_card_group" id="panel-account">
  <form id="edit_details_form" name="edit_details_form" method="POST" action="<?php echo Environment::appURL('api/v1/account/info/update/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_ACCOUNT_PROFILE_SUMMARY_ARIA'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_ACCOUNT_PROFILE_TITLE'); ?></h2>
    <div id="edit_details_status" class="status_message centered" role="status" aria-live="polite"></div>

    <div class="account_details_grid">
      <section class="details_column details_column_profile" aria-labelledby="account-details-heading">
        <h3 id="account-details-heading" class="details_column_title">Details</h3>
        <div class="item_pair">
          <label for="edit_details_email" class="item_label"><?php echo settings_index_i18n('EMAIL'); ?></label>
          <div class="item_value flex f_baseline">
            <input type="email" id="edit_details_email" value="<?php echo htmlspecialchars((string) $user->email, ENT_QUOTES, 'UTF-8'); ?>" disabled autocomplete="off" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_EMAIL_HOVER'); ?>">
            <?php
              $isRecoveryEmailVerified = (bool) ($user->recovery_email_verified ?? false);
              if ($isRecoveryEmailVerified) {
                echo '<button type="button" id="edit_details_change_email_link" class="email_change_link" title="'.settings_index_i18n('SETTINGS_CHANGE_EMAIL_LINK_TITLE').'" data-hover-help="'.settings_index_i18n('SETTINGS_CHANGE_EMAIL_LINK_HOVER').'">'.settings_index_i18n('SETTINGS_CHANGE_EMAIL_LINK_TEXT').'</button>';
              } else {
                echo '<span class="email_change_link_disabled" title="'.settings_index_i18n('SETTINGS_CHANGE_EMAIL_LINK_DISABLED_TITLE').'">'.settings_index_i18n('SETTINGS_CHANGE_EMAIL_LINK_DISABLED_TEXT').'</span>';
              }
            ?>
          </div>
        </div>

        <div class="item_pair">
          <label for="edit_details_full_name" class="item_label"><?php echo settings_index_i18n('FULL_NAME'); ?></label>
          <div class="item_value">
            <input type="text" id="edit_details_full_name" name="full_name" value="<?php echo htmlspecialchars((string) $user->full_name, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="name" required aria-describedby="edit_details_status edit_details_full_name_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_FULL_NAME_HOVER'); ?>">
            <div id="edit_details_full_name_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="item_pair">
          <div class="item_label"><?php echo settings_index_i18n('SETTINGS_RECOVERY_LABEL'); ?></div>
          <div class="item_value">
            <div id="recovery_email_status" class="flex f_column gap_sm">
              <div id="recovery_email_status_display" class="status_text" role="status" aria-live="polite" aria-atomic="true">
                <?php
                  $recoveryEmail = (string) ($user->recovery_email ?? '');
                  $isVerified = (bool) ($user->recovery_email_verified ?? false);
                  if ($isVerified && $recoveryEmail !== '') {
                    echo "✓ " . htmlspecialchars($recoveryEmail, ENT_QUOTES, 'UTF-8');
                  } elseif ($recoveryEmail !== '') {
                    echo settings_index_i18n('SETTINGS_RECOVERY_PENDING_VERIFICATION') . " (" . htmlspecialchars($recoveryEmail, ENT_QUOTES, 'UTF-8') . ")";
                  } else {
                    echo settings_index_i18n('SETTINGS_RECOVERY_NOT_VERIFIED');
                  }
                ?>
              </div>
            </div>
          </div>
        </div>

        <div class="item_pair" id="recovery_email_input_section">
          <div class="item_label">&nbsp;</div>
          <div class="item_value">
            <div class="recovery_email_input_row">
              <input type="email" id="recovery_email_input" placeholder="<?php echo settings_index_i18n('SETTINGS_RECOVERY_EMAIL_PLACEHOLDER'); ?>" autocomplete="email" aria-label="<?php echo settings_index_i18n('SETTINGS_RECOVERY_EMAIL_ARIA_LABEL'); ?>" aria-describedby="recovery_email_send_status recovery_email_input_error" value="<?php echo htmlspecialchars((string) ($user->recovery_email ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-hover-help="<?php echo settings_index_i18n('SETTINGS_RECOVERY_EMAIL_INPUT_HOVER'); ?>">
              <button type="button" id="recovery_email_send_btn" class="btn btn_secondary mt_8" aria-label="<?php echo settings_index_i18n('SETTINGS_RECOVERY_SEND_VERIFICATION_CODE_ARIA'); ?>" aria-controls="recovery_email_verify_section" aria-describedby="recovery_email_send_status" data-hover-help="<?php echo settings_index_i18n('SETTINGS_RECOVERY_SEND_CODE_HOVER'); ?>"><?php echo settings_index_i18n('SETTINGS_RECOVERY_SEND_BUTTON'); ?></button>
            </div>
            <div id="recovery_email_send_status" class="status_message" role="status" aria-live="assertive"></div>
            <div id="recovery_email_input_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="item_pair" id="recovery_email_verify_section" hidden>
          <div class="item_label">&nbsp;</div>
          <div class="item_value">
            <input type="text" id="recovery_email_code_input" class="code_input" placeholder="enter code" maxlength="6" inputmode="text" aria-label="<?php echo settings_index_i18n('SETTINGS_RECOVERY_VERIFICATION_CODE_ARIA'); ?>" aria-describedby="recovery_email_verify_status recovery_email_expiry_timer recovery_email_code_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_RECOVERY_VERIFICATION_CODE_HOVER'); ?>">
            <button type="button" id="recovery_email_verify_btn" class="btn btn_primary mt_8" aria-describedby="recovery_email_verify_status" data-hover-help="<?php echo settings_index_i18n('SETTINGS_RECOVERY_VERIFY_BUTTON_HOVER'); ?>"><?php echo settings_index_i18n('SETTINGS_RECOVERY_VERIFY_BUTTON'); ?></button>
            <div id="recovery_email_verify_status" class="status_message" role="status" aria-live="assertive"></div>
            <div id="recovery_email_expiry_timer" class="status_text compact_hint mt_8"></div>
            <div id="recovery_email_code_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="item_pair">
          <label for="edit_details_phone" class="item_label"><?php echo settings_index_i18n('PHONE'); ?></label>
          <div class="item_value">
            <div class="personal_phone_input_shell">
              <span class="personal_phone_country_code" aria-hidden="true">+1</span>
              <input type="tel" id="edit_details_phone" name="phone" value="<?php echo htmlspecialchars($accountPhoneDisplay, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="tel-national" maxlength="14" inputmode="numeric" pattern="\([0-9]{3}\) [0-9]{3}-[0-9]{4}" placeholder="<?php echo settings_index_i18n('BUSINESSES_CONTACT_PHONE_PLACEHOLDER'); ?>" aria-describedby="edit_details_status edit_details_phone_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PHONE_HOVER'); ?>">
            </div>
            <div id="edit_details_phone_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

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
        </div>

        <div class="item_pair">
          <label for="edit_details_timezone_picker" class="item_label"><?php echo settings_index_i18n('TIMEZONE'); ?></label>
          <div class="item_value" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_TIMEZONE_HOVER'); ?>">
            <?php echo $accountTimezoneSelectHtml; ?>
          </div>
        </div>

        <div class="item_pair">
          <label for="edit_details_address_line1" class="item_label"><?php echo settings_index_i18n('ADDRESS'); ?></label>
          <div class="item_value">
            <input type="text" id="edit_details_address_line1" name="address_line1" value="<?php echo htmlspecialchars((string) ($user->address_line1 ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="120" aria-describedby="edit_details_status edit_details_address_line1_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_ADDRESS_HOVER'); ?>">
            <div id="edit_details_address_line1_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="item_pair">
          <label for="edit_details_address_city" class="item_label"><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_CITY_LABEL'); ?></label>
          <div class="item_value">
            <input type="text" id="edit_details_address_city" name="address_city" value="<?php echo htmlspecialchars((string) ($user->address_city ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="80" aria-describedby="edit_details_status edit_details_address_city_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_CITY_HOVER'); ?>">
            <div id="edit_details_address_city_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="item_pair">
          <label for="edit_details_address_postal" class="item_label"><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_POSTAL_CODE_LABEL'); ?></label>
          <div class="item_value">
            <input type="text" id="edit_details_address_postal" name="address_postal" value="<?php echo htmlspecialchars((string) ($user->address_postal ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="20" aria-describedby="edit_details_status edit_details_address_postal_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_POSTAL_CODE_HOVER'); ?>">
            <div id="edit_details_address_postal_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>
      </section>

      <section class="details_column details_column_employment" aria-labelledby="account-employment-heading">
        <h3 id="account-employment-heading" class="details_column_title">Employment</h3>
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
        </div>

        <div class="item_pair">
          <label for="edit_details_job_title" class="item_label"><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_JOB_TITLE_LABEL'); ?></label>
          <div class="item_value">
            <input type="text" id="edit_details_job_title" name="job_title" value="<?php echo htmlspecialchars((string) ($user->job_title ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="80" aria-describedby="edit_details_status edit_details_job_title_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_JOB_TITLE_HOVER'); ?>">
            <div id="edit_details_job_title_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="item_pair">
          <label for="edit_details_department" class="item_label"><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_DEPARTMENT_LABEL'); ?></label>
          <div class="item_value">
            <input type="text" id="edit_details_department" name="department" value="<?php echo htmlspecialchars((string) ($user->department ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="80" aria-describedby="edit_details_status edit_details_department_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_DEPARTMENT_HOVER'); ?>">
            <div id="edit_details_department_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="item_pair">
          <label for="edit_details_hire_date" class="item_label"><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_HIRE_DATE_LABEL'); ?></label>
          <div class="item_value">
            <input type="date" id="edit_details_hire_date" name="hire_date" value="<?php echo htmlspecialchars((string) ($user->hire_date ?? ''), ENT_QUOTES, 'UTF-8'); ?>" aria-describedby="edit_details_status edit_details_hire_date_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_HIRE_DATE_HOVER'); ?>">
            <div id="edit_details_hire_date_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="item_pair">
          <label for="edit_details_pay_rate" class="item_label"><?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PAY_RATE_LABEL'); ?></label>
          <div class="item_value">
            <input type="text" id="edit_details_pay_rate" name="pay_rate" value="<?php echo htmlspecialchars((string) ($user->pay_rate ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="32" aria-describedby="edit_details_status edit_details_pay_rate_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PAY_RATE_HOVER'); ?>">
            <div id="edit_details_pay_rate_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>

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
        </div>

      </section>

      <section class="details_column details_column_tax" aria-labelledby="account-tax-heading">
        <h2 id="account-tax-heading" class="heading-accent settings_card_title">Tax Exemptions</h2>
        <div class="item_pair">
          <span class="item_label" id="edit_details_indigenous_tax_exemption_eligible_label">Indigenous tax exemption eligible</span>
          <div class="item_value">
            <div class="radio_group pill_group settings_tax_pill_group" role="radiogroup" aria-labelledby="edit_details_indigenous_tax_exemption_eligible_label">
              <input type="radio" class="radio" id="edit_details_indigenous_tax_exemption_eligible_yes" name="indigenous_tax_exemption_eligible" value="1"<?php if ($user->indigenous_tax_exemption_eligible) { echo ' checked'; } ?> aria-describedby="edit_details_status">
              <label for="edit_details_indigenous_tax_exemption_eligible_yes">Yes</label>
              <input type="radio" class="radio" id="edit_details_indigenous_tax_exemption_eligible_no" name="indigenous_tax_exemption_eligible" value="0"<?php if (!$user->indigenous_tax_exemption_eligible) { echo ' checked'; } ?> aria-describedby="edit_details_status">
              <label for="edit_details_indigenous_tax_exemption_eligible_no">No</label>
            </div>
          </div>
        </div>

        <div class="item_pair">
          <span class="item_label" id="edit_details_lives_on_reserve_label">Lives on reserve</span>
          <div class="item_value">
            <div class="radio_group pill_group settings_tax_pill_group" role="radiogroup" aria-labelledby="edit_details_lives_on_reserve_label">
              <input type="radio" class="radio" id="edit_details_lives_on_reserve_yes" name="lives_on_reserve" value="1"<?php if ($user->lives_on_reserve) { echo ' checked'; } ?> aria-describedby="edit_details_status">
              <label for="edit_details_lives_on_reserve_yes">Yes</label>
              <input type="radio" class="radio" id="edit_details_lives_on_reserve_no" name="lives_on_reserve" value="0"<?php if (!$user->lives_on_reserve) { echo ' checked'; } ?> aria-describedby="edit_details_status">
              <label for="edit_details_lives_on_reserve_no">No</label>
            </div>
          </div>
        </div>

        <div class="item_pair">
          <label for="edit_details_reserve_name" class="item_label">Reserve name</label>
          <div class="item_value">
            <input type="text" id="edit_details_reserve_name" name="reserve_name" value="<?php echo htmlspecialchars((string) $user->reserve_name, ENT_QUOTES, 'UTF-8'); ?>" maxlength="120" aria-describedby="edit_details_status edit_details_reserve_name_error">
            <div id="edit_details_reserve_name_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div>
      </section>
    </div>
  </form>
</section>
