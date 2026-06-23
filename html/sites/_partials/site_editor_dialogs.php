<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\Config\SiteColorPalette;

if (!function_exists('site_editor_i18n')) {
  function site_editor_i18n(string $key): string
  {
    if (function_exists('sites_index_i18n')) {
      return sites_index_i18n($key);
    }

    return Strings::i18n($key);
  }
}

$siteEditorContext = isset($siteEditorContext) && is_string($siteEditorContext)
  ? $siteEditorContext
  : 'personal';
$siteEditorPlanningEmptyText = $siteEditorContext === 'business'
  ? site_editor_i18n('BUSINESS_SITES_PLANNING_SCOPE_NOTE')
  : site_editor_i18n('SITES_PERSONAL_PLANNING_EMPTY');
?>
  <!-- Create Site Dialog -->
  <dialog id='modal_create_site' class='dialog' aria-modal='true' aria-labelledby='modal_create_site_title' aria-describedby='modal_create_site_aria'>
    <div class='modal_aria visually_hidden'>
      <span id='modal_create_site_aria'><?php echo site_editor_i18n('CREATE_SITE'); ?></span>
    </div>
    <form id='create_site_form' method='POST' action='<?php echo Environment::appURL('api/sites/create/'); ?>'>
      <input type='hidden' id='create_site_status' name='status' value='active'>
      <section class='modal_header'>
        <h2 id='modal_create_site_title' class='modal_title'><?php echo site_editor_i18n('CREATE_SITE'); ?></h2>
        <button type='button' class='btn_close' data-dialog-close='modal_create_site' aria-label='<?php echo site_editor_i18n('CLOSE'); ?>'>&times;</button>
      </section>
      <section class='modal_content f_column'>
        <!-- Site Name -->
        <div class='item_pair'>
          <label class='item_label' for='site_name_input'><?php echo site_editor_i18n('NAME'); ?></label>
          <div class='item_value'>
            <input
              id='site_name_input'
              type='text'
              name='site_name'
              value=''
              placeholder='<?php echo site_editor_i18n('NAME'); ?>'
              maxlength='100'
              required
              aria-required='true'
              aria-describedby='create_site_form_status site_name_error'
            >
            <small id='site_name_error' class='status_text compact_hint' role='status' aria-live='polite'></small>
          </div>
        </div>

        <!-- Wage -->
        <div class='item_pair'>
          <label class='item_label' for='site_wage_input'><?php echo site_editor_i18n('WAGE'); ?></label>
          <div class='item_value'>
            <input
              id='site_wage_input'
              type='number'
              inputmode='decimal'
              name='wage'
              value=''
              placeholder='25.00'
              step='0.01'
              min='0.01'
              required
              aria-required='true'
              aria-describedby='create_site_form_status site_wage_error'
            >
            <small id='site_wage_error' class='status_text compact_hint' role='status' aria-live='polite'></small>
          </div>
        </div>

        <!-- Living Out Allowance -->
        <div class='item_pair'>
          <label class='item_label' for='site_loa_input'><?php echo site_editor_i18n('LIVING_OUT_ALLOWANCE'); ?></label>
          <div class='item_value'>
            <input
              id='site_loa_input'
              type='number'
              inputmode='decimal'
              name='living_out_allowance'
              value=''
              placeholder='0.00'
              step='0.01'
              min='0'
              aria-describedby='create_site_form_status'
            >
          </div>
        </div>

        <!-- Travel Hourly Rate -->
        <div class='item_pair'>
          <label class='item_label' for='site_travel_hours_input'><?php echo site_editor_i18n('TRAVEL_HOURLY_RATE'); ?></label>
          <div class='item_value'>
            <input
              id='site_travel_hours_input'
              type='number'
              inputmode='decimal'
              name='travel_hours'
              value=''
              placeholder='0.00'
              step='0.01'
              min='0'
              aria-describedby='create_site_form_status'
            >
          </div>
        </div>

        <div class='item_pair'>
          <label class='item_label' for='site_is_on_reserve_input'>On reserve</label>
          <div class='item_value'>
            <input type='hidden' name='is_on_reserve' value='0'>
            <input id='site_is_on_reserve_input' type='checkbox' name='is_on_reserve' value='1' aria-describedby='create_site_form_status'>
          </div>
        </div>

        <div class='item_pair'>
          <label class='item_label' for='site_reserve_name_input'>Reserve name</label>
          <div class='item_value'>
            <input id='site_reserve_name_input' type='text' name='reserve_name' value='' maxlength='120' aria-describedby='create_site_form_status'>
          </div>
        </div>

        <!-- Province -->
        <div class='item_pair'>
          <label class='item_label' for='site_province_select'><?php echo site_editor_i18n('PROVINCE'); ?></label>
          <div class='item_value'>
            <select id='site_province_select' name='province' aria-describedby='create_site_form_status'>
              <option value=''><?php echo site_editor_i18n('PROVINCE'); ?></option>
              <option value='AB'><?php echo site_editor_i18n('PROFILE_PROVINCE_AB'); ?></option>
              <option value='BC'><?php echo site_editor_i18n('PROFILE_PROVINCE_BC'); ?></option>
              <option value='MB'><?php echo site_editor_i18n('PROFILE_PROVINCE_MB'); ?></option>
              <option value='NB'><?php echo site_editor_i18n('PROFILE_PROVINCE_NB'); ?></option>
              <option value='NL'><?php echo site_editor_i18n('PROFILE_PROVINCE_NL'); ?></option>
              <option value='NS'><?php echo site_editor_i18n('PROFILE_PROVINCE_NS'); ?></option>
              <option value='ON'><?php echo site_editor_i18n('PROFILE_PROVINCE_ON'); ?></option>
              <option value='PE'><?php echo site_editor_i18n('PROFILE_PROVINCE_PE'); ?></option>
              <option value='QC'><?php echo site_editor_i18n('PROFILE_PROVINCE_QC'); ?></option>
              <option value='SK'><?php echo site_editor_i18n('PROFILE_PROVINCE_SK'); ?></option>
              <option value='NT'><?php echo site_editor_i18n('PROFILE_PROVINCE_NT'); ?></option>
              <option value='NU'><?php echo site_editor_i18n('PROFILE_PROVINCE_NU'); ?></option>
              <option value='YT'><?php echo site_editor_i18n('PROFILE_PROVINCE_YT'); ?></option>
            </select>
          </div>
        </div>
      </section>
      <section class='modal_footer'>
        <div id='create_site_form_status' class='status_message centered' role='status' aria-live='polite'></div>
        <div class='flex f_center f_space_around'>
          <button type='submit' id='create_site_submit' class='btn btn_primary'>
            <?php echo site_editor_i18n('CREATE'); ?>
          </button>
          <button type='button' id='create_site_cancel' class='btn btn_secondary' data-dialog-close='modal_create_site'>
            <?php echo site_editor_i18n('CLOSE'); ?>
          </button>
        </div>
      </section>
    </form>
  </dialog>

  <!-- Edit Site Dialog -->
  <dialog id='modal_edit_site' class='dialog' aria-modal='true' aria-labelledby='modal_edit_site_title' aria-describedby='modal_edit_site_aria'>
    <div class='modal_aria visually_hidden'>
      <span id='modal_edit_site_aria'><?php echo site_editor_i18n('EDIT_SITE'); ?></span>
    </div>
    <form id='edit_site_form' method='POST' action='<?php echo Environment::appURL('api/sites/update/'); ?>' data-site-editor-context='<?php echo htmlspecialchars($siteEditorContext, ENT_QUOTES, 'UTF-8'); ?>'>
      <input type='hidden' id='edit_site_id' name='id' value=''>
      <input type='hidden' id='edit_site_owner_uuid' name='owner_uuid' value=''>
      <section class='modal_header'>
        <h2 id='modal_edit_site_title' class='modal_title'><?php echo site_editor_i18n('EDIT_SITE'); ?></h2>
        <button type='button' class='btn_close' data-dialog-close='modal_edit_site' aria-label='<?php echo site_editor_i18n('CLOSE'); ?>'>&times;</button>
      </section>
      <section class='modal_content'>

        <!-- ── Left column: Site Settings ────────────────────────────── -->
        <div class='edit_site_col edit_site_col_basic'>
          <h3 class='edit_site_col_heading'>&#9881; <?php echo site_editor_i18n('SITES_EDITOR_SETTINGS_HEADING'); ?></h3>

          <!-- Site Name -->
          <div class='item_pair'>
            <label class='item_label' for='edit_site_name_input'><?php echo site_editor_i18n('NAME'); ?></label>
            <div class='item_value'>
              <input
                id='edit_site_name_input'
                type='text'
                name='site_name'
                value=''
                placeholder='<?php echo site_editor_i18n('NAME'); ?>'
                maxlength='100'
                required
                aria-required='true'
                aria-describedby='edit_site_form_status edit_site_name_error'
                data-hover-help='<?php echo site_editor_i18n('SITES_HELP_SITE_NAME'); ?>'
              >
              <small id='edit_site_name_error' class='status_text compact_hint' role='status' aria-live='polite'></small>
            </div>
          </div>

          <!-- Wage -->
          <div class='item_pair'>
            <label class='item_label' for='edit_site_wage_input'><?php echo site_editor_i18n('WAGE'); ?></label>
            <div class='item_value'>
              <input
                id='edit_site_wage_input'
                type='number'
                inputmode='decimal'
                name='wage'
                value=''
                placeholder='25.00'
                step='0.01'
                min='0.01'
                required
                aria-required='true'
                aria-describedby='edit_site_form_status edit_site_wage_error'
                data-hover-help='<?php echo site_editor_i18n('SITES_HELP_WAGE'); ?>'
              >
              <small id='edit_site_wage_error' class='status_text compact_hint' role='status' aria-live='polite'></small>
            </div>
          </div>

          <!-- Province -->
          <div class='item_pair'>
            <label class='item_label' for='edit_site_province_select'><?php echo site_editor_i18n('PROVINCE'); ?></label>
            <div class='item_value'>
              <select id='edit_site_province_select' name='province' aria-describedby='edit_site_form_status'
                data-hover-help='<?php echo site_editor_i18n('SITES_HELP_PROVINCE'); ?>'>
                <option value=''><?php echo site_editor_i18n('PROVINCE'); ?></option>
                <option value='AB'><?php echo site_editor_i18n('PROFILE_PROVINCE_AB'); ?></option>
                <option value='BC'><?php echo site_editor_i18n('PROFILE_PROVINCE_BC'); ?></option>
                <option value='MB'><?php echo site_editor_i18n('PROFILE_PROVINCE_MB'); ?></option>
                <option value='NB'><?php echo site_editor_i18n('PROFILE_PROVINCE_NB'); ?></option>
                <option value='NL'><?php echo site_editor_i18n('PROFILE_PROVINCE_NL'); ?></option>
                <option value='NS'><?php echo site_editor_i18n('PROFILE_PROVINCE_NS'); ?></option>
                <option value='ON'><?php echo site_editor_i18n('PROFILE_PROVINCE_ON'); ?></option>
                <option value='PE'><?php echo site_editor_i18n('PROFILE_PROVINCE_PE'); ?></option>
                <option value='QC'><?php echo site_editor_i18n('PROFILE_PROVINCE_QC'); ?></option>
                <option value='SK'><?php echo site_editor_i18n('PROFILE_PROVINCE_SK'); ?></option>
                <option value='NT'><?php echo site_editor_i18n('PROFILE_PROVINCE_NT'); ?></option>
                <option value='NU'><?php echo site_editor_i18n('PROFILE_PROVINCE_NU'); ?></option>
                <option value='YT'><?php echo site_editor_i18n('PROFILE_PROVINCE_YT'); ?></option>
              </select>
            </div>
          </div>

          <!-- Status -->
          <div class='item_pair'>
            <label class='item_label' for='edit_site_status_select'><?php echo site_editor_i18n('STATUS'); ?></label>
            <div class='item_value'>
              <select id='edit_site_status_select' name='status' aria-describedby='edit_site_form_status'
                data-hover-help='<?php echo site_editor_i18n('SITES_HELP_STATUS'); ?>'
                >
                <option value='active'><?php echo site_editor_i18n('ACTIVE'); ?></option>
                <option value='archived'><?php echo site_editor_i18n('ARCHIVED'); ?></option>
              </select>
            </div>
          </div>

          <!-- Living Out Allowance -->
          <div class='item_pair'>
            <label class='item_label' for='edit_site_loa_input'><?php echo site_editor_i18n('LIVING_OUT_ALLOWANCE'); ?></label>
            <div class='item_value'>
              <input
                id='edit_site_loa_input'
                type='number'
                inputmode='decimal'
                name='living_out_allowance'
                value=''
                placeholder='0.00'
                step='0.01'
                min='0'
                aria-describedby='edit_site_form_status'
                data-hover-help='<?php echo site_editor_i18n('SITES_HELP_LOA'); ?>'
              >
            </div>
          </div>

          <!-- Travel Hourly Rate -->
          <div class='item_pair'>
            <label class='item_label' for='edit_site_travel_input'><?php echo site_editor_i18n('TRAVEL_HOURLY_RATE'); ?></label>
            <div class='item_value'>
              <input
                id='edit_site_travel_input'
                type='number'
                inputmode='decimal'
                name='travel_hours'
                value=''
                placeholder='0.00'
                step='0.01'
                min='0'
                aria-describedby='edit_site_form_status'
                data-hover-help='<?php echo site_editor_i18n('SITES_HELP_TRAVEL'); ?>'
              >
            </div>
          </div>

          <!-- Default Hours Per Day -->
          <div class='item_pair'>
            <label class='item_label' for='edit_site_default_hours_input'><?php echo site_editor_i18n('SITES_DEFAULT_HOURS_LABEL'); ?></label>
            <div class='item_value'>
              <input
                id='edit_site_default_hours_input'
                type='number'
                inputmode='numeric'
                name='default_hours'
                value=''
                placeholder='10'
                step='0.5'
                min='0.5'
                max='24'
                aria-describedby='edit_site_form_status'
                data-hover-help='<?php echo site_editor_i18n('SITES_HELP_DEFAULT_HOURS'); ?>'
              >
            </div>
          </div>

          <div class='item_pair'>
            <label class='item_label' for='edit_site_is_on_reserve_input'>On reserve</label>
            <div class='item_value'>
              <input type='hidden' name='is_on_reserve' value='0'>
              <input id='edit_site_is_on_reserve_input' type='checkbox' name='is_on_reserve' value='1' aria-describedby='edit_site_form_status'>
            </div>
          </div>

          <div class='item_pair'>
            <label class='item_label' for='edit_site_reserve_name_input'>Reserve name</label>
            <div class='item_value'>
              <input id='edit_site_reserve_name_input' type='text' name='reserve_name' value='' maxlength='120' aria-describedby='edit_site_form_status'>
            </div>
          </div>

          <!-- Site Color -->
          <div class='item_pair item_pair_color'>
            <div class='item_label item_label_color'>
              <span><?php echo site_editor_i18n('SITES_SITE_COLOR_LABEL'); ?></span>
              <span class='site_color_name' id='edit_site_color_name'><?= htmlspecialchars(SiteColorPalette::labelFor(SiteColorPalette::default()) ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class='item_value'>
              <?php
              $swatchHtml = '';
              $defaultSiteColor = SiteColorPalette::default();
              foreach (SiteColorPalette::pickerPalette() as $idx => $sc) {
                $hex       = htmlspecialchars($sc['hex'], ENT_QUOTES, 'UTF-8');
                $label     = htmlspecialchars($sc['label'], ENT_QUOTES, 'UTF-8');
                $ariaLabel = htmlspecialchars(site_editor_i18n('SITES_SITE_COLOR_LABEL') . ': ' . $sc['label'] . ' (' . $sc['hex'] . ')', ENT_QUOTES, 'UTF-8');
                $selected  = strtoupper((string) $sc['hex']) === strtoupper($defaultSiteColor);
                $className = $selected ? 'site_color_swatch is-selected' : 'site_color_swatch';
                $pressed   = $selected ? 'true' : 'false';
                $swatchHtml .= "<button type='button' class='{$className}' data-hex='{$hex}' data-label='{$label}' data-idx='{$idx}' aria-label='{$ariaLabel}' aria-pressed='{$pressed}'></button>";
              }
              echo "<div class='site_color_picker' id='edit_site_color_picker'>"
                . "<div class='site_color_swatches' id='edit_site_color_swatches'>{$swatchHtml}</div>"
                . "<input type='hidden' id='edit_site_color_input' name='site_color' value='" . $defaultSiteColor . "'>"
                . "</div>";
              ?>
            </div>
          </div>
        </div>

        <!-- ── Right column: Business Planning ────────────────────── -->
        <div class='edit_site_col edit_site_col_advanced'>
          <h3 class='edit_site_col_heading'>&#128202; <?php echo site_editor_i18n('BUSINESS_PLANNING'); ?></h3>

          <!-- Org Planning — only shown when site is linked to a managed org -->
          <section id='edit_site_org_planning' hidden>
            <input type='hidden' id='edit_site_plan_org_id' value=''>
            <input type='hidden' id='edit_site_plan_owner_uuid' value=''>
            <div class='edit_site_org_planning_body'>

              <!-- Business (read-only) -->
              <div class='item_pair'>
                <label class='item_label'><?php echo site_editor_i18n('BUSINESSES'); ?></label>
                <div class='item_value'>
                  <span id='edit_site_org_planning_org_name' class='site_field_readonly'></span>
                </div>
              </div>

              <!-- Annual Budget -->
              <div class='item_pair'>
                <label class='item_label' for='edit_site_plan_budget'><?php echo site_editor_i18n('SITES_ANNUAL_BUDGET_LABEL'); ?></label>
                <div class='item_value'>
                  <input id='edit_site_plan_budget' type='number' inputmode='decimal' placeholder='0.00' step='0.01' min='0'
                    aria-describedby='edit_site_plan_status'
                    data-hover-help='<?php echo site_editor_i18n('SITES_HELP_BUDGET'); ?>'>
                </div>
              </div>

              <!-- Warn Threshold -->
              <div class='item_pair'>
                <label class='item_label' for='edit_site_plan_warn'><?php echo site_editor_i18n('SITES_WARN_THRESHOLD_LABEL'); ?></label>
                <div class='item_value'>
                  <input id='edit_site_plan_warn' type='number' inputmode='numeric' placeholder='80' min='1' max='100' step='1'
                    aria-describedby='edit_site_plan_status'
                    data-hover-help='<?php echo site_editor_i18n('SITES_HELP_WARN'); ?>'>
                </div>
              </div>

              <!-- Critical Threshold -->
              <div class='item_pair'>
                <label class='item_label' for='edit_site_plan_critical'><?php echo site_editor_i18n('SITES_CRITICAL_THRESHOLD_LABEL'); ?></label>
                <div class='item_value'>
                  <input id='edit_site_plan_critical' type='number' inputmode='numeric' placeholder='95' min='1' max='100' step='1'
                    aria-describedby='edit_site_plan_status'
                    data-hover-help='<?php echo site_editor_i18n('SITES_HELP_CRITICAL'); ?>'>
                </div>
              </div>

              <!-- Planning Status -->
              <div class='item_pair'>
                <label class='item_label' for='edit_site_plan_status_select'><?php echo site_editor_i18n('SITES_PLANNING_STATUS_LABEL'); ?></label>
                <div class='item_value'>
                  <select id='edit_site_plan_status_select' aria-describedby='edit_site_plan_status'
                    data-hover-help='<?php echo site_editor_i18n('SITES_HELP_PLANNING_STATUS'); ?>'>
                    <option value='planning'><?php echo site_editor_i18n('SITES_PLANNING_STATUS_PLANNING'); ?></option>
                    <option value='active'><?php echo site_editor_i18n('ACTIVE'); ?></option>
                    <option value='maintenance'><?php echo site_editor_i18n('SITES_PLANNING_STATUS_MAINTENANCE'); ?></option>
                    <option value='complete'><?php echo site_editor_i18n('SITES_PLANNING_STATUS_COMPLETE'); ?></option>
                    <option value='archived'><?php echo site_editor_i18n('ARCHIVED'); ?></option>
                  </select>
                </div>
              </div>

              <!-- Client / Customer -->
              <div class='item_pair'>
                <label class='item_label' for='edit_site_client_input'><?php echo site_editor_i18n('SITES_CLIENT_LABEL'); ?></label>
                <div class='item_value'>
                  <input id='edit_site_client_input' type='text' name='client_name' value='' placeholder='<?php echo site_editor_i18n('SITES_CLIENT_PLACEHOLDER'); ?>' maxlength='100'
                    aria-describedby='edit_site_form_status'
                    data-hover-help='<?php echo site_editor_i18n('SITES_HELP_CLIENT'); ?>'>
                </div>
              </div>

              <!-- Cost Code -->
              <div class='item_pair'>
                <label class='item_label' for='edit_site_cost_code_input'><?php echo site_editor_i18n('SITES_COST_CODE_LABEL'); ?></label>
                <div class='item_value'>
                  <input id='edit_site_cost_code_input' type='text' name='cost_code' value='' placeholder='<?php echo site_editor_i18n('SITES_COST_CODE_PLACEHOLDER'); ?>' maxlength='40'
                    aria-describedby='edit_site_form_status'
                    data-hover-help='<?php echo site_editor_i18n('SITES_HELP_COST_CODE'); ?>'>
                </div>
              </div>

              <!-- Start Date -->
              <div class='item_pair'>
                <label class='item_label' for='edit_site_start_date_input'><?php echo site_editor_i18n('SITES_START_DATE_LABEL'); ?></label>
                <div class='item_value'>
                  <input id='edit_site_start_date_input' type='date' name='start_date' value=''
                    aria-describedby='edit_site_form_status'
                    data-hover-help='<?php echo site_editor_i18n('SITES_HELP_START_DATE'); ?>'>
                </div>
              </div>

              <!-- End Date -->
              <div class='item_pair'>
                <label class='item_label' for='edit_site_end_date_input'><?php echo site_editor_i18n('SITES_END_DATE_LABEL'); ?></label>
                <div class='item_value'>
                  <input id='edit_site_end_date_input' type='date' name='end_date' value=''
                    aria-describedby='edit_site_form_status'
                    data-hover-help='<?php echo site_editor_i18n('SITES_HELP_END_DATE'); ?>'>
                </div>
              </div>

            </div>
            <div id='edit_site_plan_status' class='status_message' role='status' aria-live='polite' hidden></div>
          </section>
          <p id='edit_site_org_planning_empty' class='edit_site_org_planning_empty'><?php echo htmlspecialchars($siteEditorPlanningEmptyText, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

      </section>
      <section class='modal_footer'>
        <div id='edit_site_form_status' class='status_message centered' role='status' aria-live='polite'></div>
        <div class='flex f_center f_space_around'>
          <?php if ($siteEditorContext === 'business'): ?>
          <button type='button' id='edit_site_unlink_business' class='btn btn_secondary'><?php echo site_editor_i18n('BUSINESS_SITES_UNLINK'); ?></button>
          <?php endif; ?>
          <button type='button' id='edit_site_delete' class='btn btn_danger' hidden><?php echo site_editor_i18n('SITES_DELETE_ACTION'); ?></button>
          <button type='submit' id='edit_site_submit' class='btn btn_primary'><?php echo site_editor_i18n('SITES_SAVE_SITE'); ?></button>
          <button type='button' id='edit_site_cancel' class='btn btn_secondary' data-dialog-close='modal_edit_site'>
            <?php echo site_editor_i18n('CLOSE'); ?>
          </button>
        </div>
      </section>
    </form>
  </dialog>

  <!-- Confirmation Dialog for Delete Site -->
  <dialog id='modal_confirm_delete_site' class='dialog' aria-modal='true' aria-labelledby='modal_confirm_delete_site_title' aria-describedby='confirm_delete_site_aria confirm_delete_site_message'>
    <p id='confirm_delete_site_aria' class='visually_hidden'><?php echo site_editor_i18n('SITES_CONFIRM_ARCHIVE_ARIA'); ?></p>
    <section class='modal_header'>
      <h2 id='modal_confirm_delete_site_title' class='modal_title'><?php echo site_editor_i18n('SITES_CONFIRM_ARCHIVE_TITLE'); ?></h2>
      <button type='button' class='btn_close' data-dialog-close='modal_confirm_delete_site' aria-label='<?php echo site_editor_i18n('CLOSE'); ?>'>&times;</button>
    </section>
    <section class='modal_content'>
      <p id='confirm_delete_site_message'></p>
    </section>
    <section class='modal_footer'>
      <div class='flex f_center f_space_around'>
        <button type='button' id='confirm_delete_site_yes' class='btn btn_primary'>
          <?php echo site_editor_i18n('SITES_ARCHIVE_SITE'); ?>
        </button>
        <button type='button' id='confirm_delete_site_no' class='btn btn_secondary'>
          <?php echo site_editor_i18n('CANCEL'); ?>
        </button>
      </div>
    </section>
  </dialog>

  <!-- Archived Work Viewer Dialog -->
  <dialog id='modal_archived_work' class='dialog modal_archived_work' aria-modal='true' aria-labelledby='archived_work_title' aria-describedby='archived_work_aria archived_work_content'>
    <p id='archived_work_aria' class='visually_hidden'><?php echo site_editor_i18n('SITES_ARCHIVED_WORK_ARIA'); ?></p>
    <section class='modal_header'>
      <h2 class='modal_title' id='archived_work_title'><?php echo site_editor_i18n('SITES_ARCHIVED_WORK_TITLE'); ?></h2>
      <button type='button' class='btn_close' data-dialog-close='modal_archived_work' aria-label='<?php echo site_editor_i18n('CLOSE'); ?>'>&times;</button>
    </section>
    <section class='modal_content' id='archived_work_content'>
      <p class='archived_work_loading'><?php echo site_editor_i18n('LOADING'); ?></p>
    </section>
    <section class='modal_footer'>
      <div class='flex f_center f_space_between archived_controls_container'>
        <button type='button' id='archived_work_finality_delete' class='btn btn_danger hidden'>
          🗑️ <?php echo site_editor_i18n('SITES_FINALITY_DELETE_BUTTON'); ?>
        </button>
        <button type='button' id='archived_work_close' class='btn btn_secondary'>
          <?php echo site_editor_i18n('CLOSE'); ?>
        </button>
      </div>
    </section>
  </dialog>

  <!-- Finality Delete Confirmation Dialog -->
  <dialog id='modal_finality_delete' class='dialog' aria-modal='true' aria-labelledby='modal_finality_delete_title' aria-describedby='finality_delete_aria finality_delete_message'>
    <p id='finality_delete_aria' class='visually_hidden'><?php echo site_editor_i18n('SITES_FINALITY_DELETE_ARIA'); ?></p>
    <section class='modal_header'>
      <h2 id='modal_finality_delete_title' class='modal_title modal_title_danger'>⚠️ <?php echo site_editor_i18n('SITES_FINALITY_DELETE_TITLE'); ?></h2>
      <button type='button' class='btn_close' data-dialog-close='modal_finality_delete' aria-label='<?php echo site_editor_i18n('CLOSE'); ?>'>&times;</button>
    </section>
    <section class='modal_content'>
      <p id='finality_delete_message'></p>
    </section>
    <section class='modal_footer'>
      <div class='flex f_center f_space_around'>
        <button type='button' id='finality_delete_yes' class='btn btn_danger'>
          <?php echo site_editor_i18n('SITES_FINALITY_DELETE_CONFIRM'); ?>
        </button>
        <button type='button' id='finality_delete_no' class='btn btn_secondary'>
          <?php echo site_editor_i18n('CLOSE'); ?>
        </button>
      </div>
    </section>
  </dialog>
