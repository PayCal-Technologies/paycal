<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
  <dialog id="modal_business_consent_revoke" data-dialog-invoker-bridge data-dialog-close-tts="<?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_REVOKE_MODAL_TITLE'); ?>" aria-modal="true" aria-labelledby="modal_business_consent_revoke_title" aria-describedby="modal_business_consent_revoke_desc">
    <section class="modal_header">
      <button type="button" class="btn btn_close" data-dialog-close="modal_business_consent_revoke" commandfor="modal_business_consent_revoke" command="close" aria-label="<?php echo settings_index_i18n('CLOSE'); ?>">&times;</button>
      <h2 id="modal_business_consent_revoke_title" class="modal_title centered"><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_REVOKE_MODAL_TITLE'); ?></h2>
    </section>
    <section class="modal_content f_column">
      <p id="modal_business_consent_revoke_desc"><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_REVOKE_MODAL_BODY'); ?></p>
      <p><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_REVOKE_MODAL_MEMBERSHIP'); ?></p>
    </section>
    <section class="modal_footer">
      <div class="modal_controls flex centered">
        <button type="button" id="business_consent_revoke_cancel_btn" class="btn btn_cancel f_just_center mar_md" data-dialog-close="modal_business_consent_revoke" commandfor="modal_business_consent_revoke" command="close"><?php echo settings_index_i18n('CANCEL'); ?></button>
        <button type="button" id="business_consent_revoke_confirm_btn" class="btn btn_delete f_just_center mar_md"><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_REVOKE'); ?></button>
      </div>
    </section>
  </dialog>

  <!-- MODAL CHANGE EMAIL -->
  <dialog id="modal_change_email" data-dialog-invoker-bridge data-dialog-close-tts="<?php echo settings_index_i18n('CHANGE_EMAIL'); ?>" aria-modal="true" aria-labelledby="modal_change_email_title" aria-describedby="modal_change_email_desc change_email_status">
  <form id="change_email_form" name="change_email_form" aria-label="<?php echo settings_index_i18n('CHANGE_EMAIL'); ?>">
  <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
  <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
  <input type="hidden" id="change_email_txn_id" value="">

    <section class="modal_header">
      <button type="button" class="btn btn_close" data-dialog-close="modal_change_email" commandfor="modal_change_email" command="close" aria-label="<?php echo settings_index_i18n('CLOSE'); ?>">&times;</button>
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
            <input type="text" id="change_email_old_code" class="code_input" placeholder="<?php echo settings_index_i18n('SETTINGS_CHANGE_EMAIL_CODE_PLACEHOLDER'); ?>" maxlength="6" inputmode="text" autocomplete="one-time-code" aria-describedby="change_email_verify_status old_email_hint change_email_old_code_error">
            <div id="change_email_old_code_error" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </div><!-- item_pair -->

        <div class="item_pair">
          <label for="change_email_new_code" class="item_label"><?php echo settings_index_i18n('SETTINGS_CHANGE_EMAIL_NEW_CODE_LABEL'); ?></label>
          <div class="item_value">
            <div class="status_text compact_hint" id="new_email_hint"></div>
            <input type="text" id="change_email_new_code" class="code_input" placeholder="<?php echo settings_index_i18n('SETTINGS_CHANGE_EMAIL_CODE_PLACEHOLDER'); ?>" maxlength="6" inputmode="text" autocomplete="one-time-code" aria-describedby="change_email_verify_status new_email_hint change_email_new_code_error">
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
        <button id="pay_period_preview_cancel" type="button" class="btn btn_cancel f_just_center mar_md" data-dialog-close="modal_pay_period_preview" commandfor="modal_pay_period_preview" command="close"><?php echo settings_index_i18n('CANCEL'); ?></button>
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
  'invokerBridge' => true,
  'closeTts' => settings_index_i18n('PAY_PERIOD'),
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
        <button class="btn btn_cancel f_just_center mar_md" id="delete_account_cancel_btn" data-dialog-close="modal_delete_account" commandfor="modal_delete_account" command="close"><?php echo settings_index_i18n('CANCEL'); ?></button>
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
  'invokerBridge' => true,
  'closeTts' => settings_index_i18n('DELETE_ACCOUNT'),
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
<dialog id="modal_import_confirm" data-dialog-invoker-bridge data-dialog-close-tts="<?php echo settings_index_i18n('SETTINGS_JS_MODAL_CONFIRM_IMPORT'); ?>" aria-modal="true" aria-labelledby="modal_import_confirm_title" aria-describedby="modal_import_confirm_desc">
  <section class="modal_header">
    <button type="button" class="btn btn_close" data-dialog-close="modal_import_confirm" commandfor="modal_import_confirm" command="close" aria-label="<?php echo settings_index_i18n('CLOSE'); ?>">&times;</button>
    <h2 id="modal_import_confirm_title" class="modal_title centered">Confirm Import</h2>
  </section>
  <section class="modal_content f_column">
    <p id="modal_import_confirm_desc">This will overwrite your existing sites and work entries with the staged import data. This action cannot be undone.</p>
    <p id="modal_import_confirm_summary" class="centered muted"></p>
  </section>
  <section class="modal_footer">
    <div class="modal_controls flex centered">
      <button id="import_confirm_proceed_btn" type="button" class="btn btn_primary f_just_center mar_md">Commit Import</button>
      <button id="import_confirm_cancel_btn" type="button" class="btn btn_cancel f_just_center mar_md" data-dialog-close="modal_import_confirm" commandfor="modal_import_confirm" command="close"><?php echo settings_index_i18n('CANCEL'); ?></button>
    </div>
  </section>
</dialog>
