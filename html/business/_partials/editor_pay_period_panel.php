<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Pay period settings panel — mounted on Payroll page.
 */
?>
          <section class="business_payroll_section business_payroll_pay_period" title="<?php echo businesses_index_i18n_html('BUSINESSES_PP_PANEL_HELP'); ?>" data-hover-help="<?php echo businesses_index_i18n_html('BUSINESSES_PP_PANEL_HELP'); ?>">
            <div class="business_payroll_section_header">
              <h3><?php echo businesses_index_i18n_html('BUSINESSES_PP_TITLE'); ?></h3>
            </div>
            <p class="help_text"><?php echo businesses_index_i18n_html('BUSINESSES_PP_SETTINGS_HELP'); ?></p>
            <div class="businesses_pp_control_strip businesses_editor_pp_controls">
              <div class="businesses_pp_control">
                <label for="businesses_editor_pay_frequency"><?php echo businesses_index_i18n_html('PROFILE_PAY_FREQUENCY_LABEL'); ?></label>
                <select id="businesses_editor_pay_frequency">
                  <option value="weekly"><?php echo businesses_index_i18n_html('PROFILE_PAY_FREQUENCY_WEEKLY'); ?></option>
                  <option value="biweekly"><?php echo businesses_index_i18n_html('PROFILE_PAY_FREQUENCY_BIWEEKLY'); ?></option>
                  <option value="semimonthly"><?php echo businesses_index_i18n_html('PROFILE_PAY_FREQUENCY_SEMIMONTHLY'); ?></option>
                  <option value="monthly"><?php echo businesses_index_i18n_html('MONTHLY'); ?></option>
                </select>
              </div>

              <div class="businesses_pp_control">
                <label for="businesses_editor_pay_period_length"><?php echo businesses_index_i18n_html('LENGTH'); ?></label>
                <input id="businesses_editor_pay_period_length" type="number" min="7" max="31" readonly>
              </div>

              <div class="businesses_pp_control">
                <span class="businesses_pp_control_label"><?php echo businesses_index_i18n_html('PROFILE_PAY_GRACE_LABEL'); ?></span>
                <div id="businesses_editor_editing_grace_days" class="radio_group businesses_grace_radio_group" role="radiogroup" aria-label="<?php echo businesses_index_i18n_html('PROFILE_PAY_GRACE_LABEL'); ?>">
                  <input type="radio" class="radio" id="businesses_editor_grace_0" name="businesses_editor_editing_grace_days" value="0" checked>
                  <label for="businesses_editor_grace_0"><?php echo businesses_index_i18n_html('NONE'); ?></label>
                  <input type="radio" class="radio" id="businesses_editor_grace_1" name="businesses_editor_editing_grace_days" value="1">
                  <label for="businesses_editor_grace_1"><?php echo businesses_index_i18n_html('PROFILE_PAY_GRACE_1_DAY'); ?></label>
                  <input type="radio" class="radio" id="businesses_editor_grace_2" name="businesses_editor_editing_grace_days" value="2">
                  <label for="businesses_editor_grace_2"><?php echo businesses_index_i18n_html('PROFILE_PAY_GRACE_2_DAYS'); ?></label>
                  <input type="radio" class="radio" id="businesses_editor_grace_3" name="businesses_editor_editing_grace_days" value="3">
                  <label for="businesses_editor_grace_3"><?php echo businesses_index_i18n_html('PROFILE_PAY_GRACE_3_DAYS'); ?></label>
                </div>
              </div>
            </div>
            <input id="businesses_editor_pay_anchor" type="hidden" value="<?php echo businesses_index_i18n_html('BUSINESSES_DEFAULT_PAY_ANCHOR'); ?>">
            <input id="businesses_editor_pay_period_start" type="hidden" value="">
            <div class="visually_hidden">
              <p id="businesses_editor_payperiod_sr_instructions"><?php echo businesses_index_i18n_html('BUSINESSES_PP_SR'); ?></p>
              <p id="businesses_editor_payperiod_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
            </div>
            <div id="businesses_editor_preview" class="businesses_preview_box pay_period_preview_compact" aria-live="polite" role="region" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_PP_PREVIEW_ARIA'); ?>" aria-describedby="businesses_editor_payperiod_sr_instructions businesses_editor_payperiod_sr_status"></div>
          </section>
