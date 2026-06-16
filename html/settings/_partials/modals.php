<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
  <!-- MODAL CHANGE EMAIL -->
  <dialog id="modal_change_email" aria-modal="true" aria-labelledby="modal_change_email_title" aria-describedby="modal_change_email_desc change_email_status">
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
  <dialog id="modal_edit_details" aria-modal="true" aria-labelledby="modal_edit_details_title" aria-describedby="modal_edit_details_desc">
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
              <input type="tel" id="edit_details_phone" name="phone" value="<?php echo $user->phone; ?>" autocomplete="tel-national" maxlength="14" inputmode="numeric" pattern="\([0-9]{3}\) [0-9]{3}-[0-9]{4}" placeholder="<?php echo settings_index_i18n('BUSINESSES_CONTACT_PHONE_PLACEHOLDER'); ?>" aria-describedby="edit_details_status edit_details_phone_error" data-hover-help="<?php echo settings_index_i18n('SETTINGS_EDIT_DETAILS_PHONE_HOVER'); ?>">
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

  <!-- MODAL PAY PERIOD PREVIEW -->
<?php
ob_start();
?>
      <div class="pay_period_control_bar">
        <div class="pay_period_control">
          <input form="settings_pay_period_form" type="date" id="pay_period_start" name="pay_period_start" value="<?php echo htmlspecialchars((string) $user->pay_period_start, ENT_QUOTES, 'UTF-8'); ?>" class="w100" aria-label="<?php echo settings_index_i18n('PAY_PERIOD'); ?> <?php echo settings_index_i18n('START'); ?>" title="<?php echo settings_index_i18n('START'); ?>">
        </div>
        <div class="pay_period_control">
          <select form="settings_pay_period_form" id="pay_frequency" name="pay_frequency" class="w100" aria-label="<?php echo settings_index_i18n('PROFILE_PAY_FREQUENCY_LABEL'); ?>" title="<?php echo settings_index_i18n('PROFILE_PAY_FREQUENCY_LABEL'); ?>">
            <option value="weekly" <?php echo ($payFrequency === 'weekly') ? 'selected' : ''; ?>><?php echo settings_index_i18n('PROFILE_PAY_FREQUENCY_WEEKLY'); ?></option>
            <option value="biweekly" <?php echo ($payFrequency === 'biweekly') ? 'selected' : ''; ?>><?php echo settings_index_i18n('PROFILE_PAY_FREQUENCY_BIWEEKLY'); ?></option>
            <option value="semimonthly" <?php echo ($payFrequency === 'semimonthly') ? 'selected' : ''; ?>><?php echo settings_index_i18n('PROFILE_PAY_FREQUENCY_SEMIMONTHLY'); ?></option>
            <option value="monthly" <?php echo ($payFrequency === 'monthly') ? 'selected' : ''; ?>><?php echo settings_index_i18n('MONTHLY'); ?></option>
          </select>
        </div>
        <div class="pay_period_control">
          <select form="settings_pay_period_form" id="pay_anchor" name="pay_anchor" class="w100" aria-label="<?php echo settings_index_i18n('PAY_PERIOD'); ?>" title="<?php echo settings_index_i18n('PAY_PERIOD'); ?>">
            <?php
            foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $weekday) {
              $selected = ($payAnchor === $weekday) ? ' selected' : '';
              $labelKey = 'WEEKDAY_' . strtoupper($weekday);
              $label = settings_index_i18n($labelKey);
              echo '<option value="' . htmlspecialchars($weekday, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</option>\n";
            }
            ?>
          </select>
        </div>
        <div class="pay_period_control">
          <select form="settings_pay_period_form" id="editing_grace_days" name="editing_grace_days" class="w100" aria-label="<?php echo settings_index_i18n('EDITING_GRACE_DAYS'); ?>" title="<?php echo settings_index_i18n('EDITING_GRACE_DAYS'); ?>">
            <?php
            for ($days = $graceDaysMin; $days <= $graceDaysMax; ++$days) {
              $selected = ($days === $currentGraceDays) ? ' selected' : '';
              $labelKey = match ($days) {
                0 => 'EDITING_GRACE_DAYS_LOCK_IMMEDIATELY',
                1 => 'EDITING_GRACE_DAYS_1_DAY',
                2 => 'EDITING_GRACE_DAYS_2_DAYS',
                3 => 'EDITING_GRACE_DAYS_3_DAYS',
                default => 'EDITING_GRACE_DAYS_' . $days . '_DAYS',
              };
              $label = settings_index_i18n($labelKey);
              echo '<option value="' . $days . '"' . $selected . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</option>\n";
            }
            ?>
          </select>
        </div>
      </div>
      <div class="pay_period_preview_block">
        <div id="pay_period_preview_calendar" class="pay_period_preview_calendar"></div>
      </div>
      <div class="item_pair">
        <div id="pay_period_preview_summary" class="item_value w100 centered" aria-live="polite">&nbsp;</div>
      </div>
<?php
$payPeriodPreviewContent = (string) ob_get_clean();

ob_start();
?>
      <div class="modal_controls flex centered">
        <button id="pay_period_preview_apply" type="button" class="btn btn_primary f_just_center mar_md"><?php echo settings_index_i18n('SAVE'); ?></button>
        <button id="pay_period_preview_cancel" type="button" class="btn btn_cancel f_just_center mar_md"><?php echo settings_index_i18n('CANCEL'); ?></button>
      </div>
<?php
$payPeriodPreviewFooter = (string) ob_get_clean();

echo Render::dialog([
  'id' => 'modal_pay_period_preview',
  'title' => settings_index_i18n('PAY_PERIOD'),
  'titleId' => 'modal_pay_period_preview_title',
  'ariaDescribedBy' => 'pay_period_preview_summary',
  'contentHtml' => $payPeriodPreviewContent,
  'footerHtml' => $payPeriodPreviewFooter,
  'closeLabel' => settings_index_i18n('CLOSE'),
]);
?>

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
<!-- IMPORT CONFIRM DIALOG -->
<dialog id="modal_import_confirm" aria-modal="true" aria-labelledby="modal_import_confirm_title" aria-describedby="modal_import_confirm_desc">
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
