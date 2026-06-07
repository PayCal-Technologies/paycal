<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

/**
 * Sites.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   User Page
 *
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */
$currentPage = 'PAGE_SITES';

require_once '../config.php';

if (function_exists('sites_index_i18n') === false) {
  function sites_index_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}

Authentication::redirectHomeIfUnauthenticated();

Lens::boot('sites');

$pageTitle = sites_index_i18n('SITES') . ' - [' . sites_index_i18n('SITE_NAME') . ']';
$pageLabel = sites_index_i18n('SITES');
$pageLanguage = 'en';

if (InputSanitizer::getString('lens') === '1') {
  $userUUID = User::currentUUID();
  $allSites = iterator_to_array(Sites::getSites($userUUID, 'all'));
  $activeCount = 0;
  $archivedCount = 0;

  foreach ($allSites as $site) {
    $status = strtolower((string) ($site['status'] ?? ''));
    if ('active' === $status) {
      ++$activeCount;
      continue;
    }

    if ('archived' === $status || 'inactive' === $status) {
      ++$archivedCount;
    }
  }

  $workEntryCount = count(Database::scanKeys(Keys::WORK . ':' . $userUUID . ':*'));

  Lens::add('Sites Backend Snapshot', [
    'page' => $currentPage,
    'sites_total' => count($allSites),
    'sites_active' => $activeCount,
    'sites_archived_or_inactive' => $archivedCount,
    'work_entries_total' => $workEntryCount,
    'analytics_default_year' => (int) date('Y'),
  ]);
}

require_once Environment::appHome().'html/header.php';
?>

  <h1 class='visually_hidden'><?php echo sites_index_i18n('SITES'); ?></h1>

  <!-- Orphaned Work Warning Banner -->
  <aside id='orphaned_work_banner' class='orphaned_work_banner hidden'>
    <div class='orphaned_work_banner_content'>
      <div class='orphaned_work_banner_icon'>⚠️</div>
      <div class='orphaned_work_banner_text'>
        <strong id='orphaned_work_count'>0 orphaned work entries</strong> found (work entries with no site).
        <span class='orphaned_work_banner_hint'>Click to recover this data.</span>
      </div>
      <button type='button' id='btn_show_orphaned_work' class='btn btn_warning'>
        Recover Data
      </button>
    </div>
  </aside>

  <!-- Two-panel layout: Sites DataGrid | Earnings Analytics -->
  <div class='flex w100 sites_main_container'>

    <!-- LEFT PANEL: Sites DataGrid -->
    <div class='f_column w50'>
      <section id='sites_list_panel' class='f_column panel tab-content sites_list_panel' title='Browse site records and open a site to edit details.' data-hover-help='Browse site records and open a site to edit details.'>
        <ul class='tabs' role="tablist" aria-label="Sites tabs">
          <li id="tab-active_sites" data-tab-target='#active_sites' class='tab active' tabindex="0" role="tab" aria-selected="true" aria-controls="active_sites">Active</li>
          <li id="tab-archived_sites" data-tab-target='#archived_sites' class='tab' tabindex="-1" role="tab" aria-selected="false" aria-controls="archived_sites">Archived</li>
        </ul>
        <p class='tab-disclaimer' data-for-tab='active_sites'>
          These sites are currently in use and available for new work entries.
        </p>
        <p class='tab-disclaimer hidden' data-for-tab='archived_sites'>
          <span class='tab_disclaimer_warning'>Deleting an archived site will permanently remove all associated work entries.</span>
        </p>

        <div id='active_sites' data-tab-content='active_sites' class='active' role="tabpanel" aria-labelledby="tab-active_sites">
          <div class="visually_hidden">
            <p id="sites_grid_active_sr_instructions">Active sites grid. Use search to filter sites and activate a row to open site details.</p>
            <p id="sites_grid_active_sr_context">Rows include site name, wage, allowances, and province. Active sites remain available for new work entries.</p>
            <p id="sites_grid_active_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
          </div>
          <div id="sites-grid-active" class="datagrid_container" role="region" aria-label="Active sites results" aria-describedby="sites_grid_active_sr_instructions sites_grid_active_sr_context sites_grid_active_sr_status">
            <div class="datagrid_body"></div>
          </div>
        </div>
        <div id='archived_sites' data-tab-content='archived_sites' role="tabpanel" aria-labelledby="tab-archived_sites">
          <div class="visually_hidden">
            <p id="sites_grid_archived_sr_instructions">Archived sites grid. Archived sites keep history but are removed from active entry flows.</p>
            <p id="sites_grid_archived_sr_context">Archived rows remain available for reporting and recovery actions. Deleting archived sites permanently removes linked work history.</p>
            <p id="sites_grid_archived_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
          </div>
          <div id="sites-grid-archived" class="datagrid_container" role="region" aria-label="Archived sites results" aria-describedby="sites_grid_archived_sr_instructions sites_grid_archived_sr_context sites_grid_archived_sr_status">
            <div class="datagrid_body"></div>
          </div>
        </div>
      </section>
    </div>

    <!-- RIGHT PANEL: Earnings Analytics -->
    <div class='f_column w50'>
      <section id='sites_earnings_panel' class='panel tab-content sites_earnings_panel' title='Review yearly earnings analytics grouped by site.' data-hover-help='Review yearly earnings analytics grouped by site.'>
        <ul id='earnings_year_tabs' class='tabs' role='tablist' aria-label='Earnings year tabs'>
          <!-- Year tabs populated dynamically -->
        </ul>
        <p class='tab-disclaimer'>History of your earnings per site.</p>

        <div id='site_earnings_container'>
          <div id='site_earnings_loading' class='earnings_loading_container skeleton' aria-label="Loading earnings data" aria-live="polite">
            <!-- Skeleton bar chart -->
            <div class="sites_earnings_skeleton_wrap">
              <div class="sites_earnings_skeleton_head">
                <span class="sk-line sk-line--sm sites_sk_line_head_title"></span>
                <span class="sk-line sites_sk_line_head_subtitle"></span>
              </div>
              <div class="sites_earnings_skeleton_bars" aria-hidden="true">
                <?php foreach ([45,70,55,85,40,95,65,75,50,80,60,35] as $__sh): ?>
                <span class="sk-chart-bar sk-box sites_sk_bar sites_sk_bar_h_<?php echo $__sh; ?>"></span>
                <?php endforeach; ?>
              </div>
              <div class="sites_earnings_skeleton_rows">
                <?php for ($__sr = 0; $__sr < 3; $__sr++): ?>
                <div class="sites_earnings_skeleton_row">
                  <span class="sk-line sk-line--lg"></span>
                  <span class="sk-line sk-line--md"></span>
                  <span class="sk-line sk-line--sm"></span>
                </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>

          <div id='site_earnings_list' class='hidden'>
            <!-- Site earnings rows populated via JavaScript -->
          </div>

          <div id='site_earnings_totals' class='hidden earnings_totals_container'>
            <!-- Totals summary populated via JavaScript -->
          </div>

          <div id='site_earnings_empty' class='hidden f_center earnings_empty_container'>
            <span>No earnings data for this year</span>
          </div>
        </div>
      </section>
    </div>

  </div>

  <!-- Create Site Dialog -->
  <dialog id='modal_create_site' class='dialog' aria-modal='true' aria-labelledby='modal_create_site_title' aria-describedby='modal_create_site_aria'>
    <div class='modal_aria visually_hidden'>
      <span id='modal_create_site_aria'><?php echo sites_index_i18n('CREATE_SITE'); ?></span>
    </div>
    <form id='create_site_form' method='POST' action='<?php echo Environment::appURL('api/sites/create/'); ?>'>
      <input type='hidden' id='create_site_status' name='status' value='active'>
      <section class='modal_header'>
        <h2 id='modal_create_site_title' class='modal_title'><?php echo sites_index_i18n('CREATE_SITE'); ?></h2>
        <button type='button' class='btn_close' data-dialog-close='modal_create_site' aria-label='<?php echo sites_index_i18n('CLOSE'); ?>'>&times;</button>
      </section>
      <section class='modal_content f_column'>
        <!-- Site Name -->
        <div class='item_pair'>
          <label class='item_label' for='site_name_input'><?php echo sites_index_i18n('NAME'); ?></label>
          <div class='item_value'>
            <input
              id='site_name_input'
              type='text'
              name='site_name'
              value=''
              placeholder='<?php echo sites_index_i18n('NAME'); ?>'
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
          <label class='item_label' for='site_wage_input'><?php echo sites_index_i18n('WAGE'); ?></label>
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
          <label class='item_label' for='site_loa_input'><?php echo sites_index_i18n('LIVING_OUT_ALLOWANCE'); ?></label>
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
          <label class='item_label' for='site_travel_hours_input'><?php echo sites_index_i18n('TRAVEL_HOURLY_RATE'); ?></label>
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

        <!-- Province -->
        <div class='item_pair'>
          <label class='item_label' for='site_province_select'><?php echo sites_index_i18n('PROVINCE'); ?></label>
          <div class='item_value'>
            <select id='site_province_select' name='province' aria-describedby='create_site_form_status'>
              <option value=''><?php echo sites_index_i18n('PROVINCE'); ?></option>
              <option value='AB'>Alberta</option>
              <option value='BC'>British Columbia</option>
              <option value='MB'>Manitoba</option>
              <option value='NB'>New Brunswick</option>
              <option value='NL'>Newfoundland and Labrador</option>
              <option value='NS'>Nova Scotia</option>
              <option value='ON'>Ontario</option>
              <option value='PE'>Prince Edward Island</option>
              <option value='QC'>Quebec</option>
              <option value='SK'>Saskatchewan</option>
              <option value='NT'>Northwest Territories</option>
              <option value='NU'>Nunavut</option>
              <option value='YT'>Yukon</option>
            </select>
          </div>
        </div>
      </section>
      <section class='modal_footer'>
        <div id='create_site_form_status' class='status_message centered' role='status' aria-live='polite'></div>
        <div class='flex f_center f_space_around'>
          <button type='submit' id='create_site_submit' class='btn btn_primary'>
            <?php echo sites_index_i18n('CREATE'); ?>
          </button>
          <button type='button' id='create_site_cancel' class='btn btn_secondary' data-dialog-close='modal_create_site'>
            <?php echo sites_index_i18n('CLOSE'); ?>
          </button>
        </div>
      </section>
    </form>
  </dialog>

  <!-- Edit Site Dialog -->
  <dialog id='modal_edit_site' class='dialog' aria-modal='true' aria-labelledby='modal_edit_site_title' aria-describedby='modal_edit_site_aria'>
    <div class='modal_aria visually_hidden'>
      <span id='modal_edit_site_aria'><?php echo sites_index_i18n('EDIT_SITE'); ?></span>
    </div>
    <form id='edit_site_form' method='POST' action='<?php echo Environment::appURL('api/sites/update/'); ?>'>
      <input type='hidden' id='edit_site_id' name='id' value=''>
      <section class='modal_header'>
        <h2 id='modal_edit_site_title' class='modal_title'><?php echo sites_index_i18n('EDIT_SITE'); ?></h2>
        <button type='button' class='btn_close' data-dialog-close='modal_edit_site' aria-label='<?php echo sites_index_i18n('CLOSE'); ?>'>&times;</button>
      </section>
      <section class='modal_content'>

        <!-- ── Left column: Site Settings ────────────────────────────── -->
        <div class='edit_site_col edit_site_col_basic'>
          <h3 class='edit_site_col_heading'>&#9881; Site Settings</h3>

          <!-- Site Name -->
          <div class='item_pair'>
            <label class='item_label' for='edit_site_name_input'><?php echo sites_index_i18n('NAME'); ?></label>
            <div class='item_value'>
              <input
                id='edit_site_name_input'
                type='text'
                name='site_name'
                value=''
                placeholder='<?php echo sites_index_i18n('NAME'); ?>'
                maxlength='100'
                required
                aria-required='true'
                aria-describedby='edit_site_form_status edit_site_name_error'
                data-hover-help='The display name for this site. Used in reports, the calendar, and payroll summaries.'
              >
              <small id='edit_site_name_error' class='status_text compact_hint' role='status' aria-live='polite'></small>
            </div>
          </div>

          <!-- Wage -->
          <div class='item_pair'>
            <label class='item_label' for='edit_site_wage_input'><?php echo sites_index_i18n('WAGE'); ?></label>
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
                data-hover-help='Base hourly rate used to calculate gross earnings for all work entries at this site.'
              >
              <small id='edit_site_wage_error' class='status_text compact_hint' role='status' aria-live='polite'></small>
            </div>
          </div>

          <!-- Province -->
          <div class='item_pair'>
            <label class='item_label' for='edit_site_province_select'><?php echo sites_index_i18n('PROVINCE'); ?></label>
            <div class='item_value'>
              <select id='edit_site_province_select' name='province' aria-describedby='edit_site_form_status'
                data-hover-help='Province where this site is located. Used to apply the correct provincial tax rates to your earnings.'>
                <option value=''><?php echo sites_index_i18n('PROVINCE'); ?></option>
                <option value='AB'>Alberta</option>
                <option value='BC'>British Columbia</option>
                <option value='MB'>Manitoba</option>
                <option value='NB'>New Brunswick</option>
                <option value='NL'>Newfoundland and Labrador</option>
                <option value='NS'>Nova Scotia</option>
                <option value='ON'>Ontario</option>
                <option value='PE'>Prince Edward Island</option>
                <option value='QC'>Quebec</option>
                <option value='SK'>Saskatchewan</option>
                <option value='NT'>Northwest Territories</option>
                <option value='NU'>Nunavut</option>
                <option value='YT'>Yukon</option>
              </select>
            </div>
          </div>

          <!-- Status -->
          <div class='item_pair'>
            <label class='item_label' for='edit_site_status_select'><?php echo sites_index_i18n('STATUS'); ?></label>
            <div class='item_value'>
              <select id='edit_site_status_select' name='status' aria-describedby='edit_site_form_status'
                data-hover-help='Active sites appear in the calendar and reports. Archiving a site hides it and preserves all work history.'>
                <option value='active'><?php echo sites_index_i18n('ACTIVE'); ?></option>
                <option value='archived'>Archived</option>
              </select>
            </div>
          </div>

          <!-- Living Out Allowance -->
          <div class='item_pair'>
            <label class='item_label' for='edit_site_loa_input'><?php echo sites_index_i18n('LIVING_OUT_ALLOWANCE'); ?></label>
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
                data-hover-help='Daily tax-free allowance paid when working away from home. Added to your gross earnings per day worked.'
              >
            </div>
          </div>

          <!-- Travel Hourly Rate -->
          <div class='item_pair'>
            <label class='item_label' for='edit_site_travel_input'><?php echo sites_index_i18n('TRAVEL_HOURLY_RATE'); ?></label>
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
                data-hover-help='Hourly rate paid for travel time to and from this site. Calculated separately from regular hours.'
              >
            </div>
          </div>

          <!-- Default Hours Per Day -->
          <div class='item_pair'>
            <label class='item_label' for='edit_site_default_hours_input'>Default Hours / Day</label>
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
                data-hover-help='Pre-fills the hours field when adding a new work entry for this site. Saves time for sites with a consistent shift length.'
              >
            </div>
          </div>

          <!-- Site Color -->
          <div class='item_pair item_pair_color'>
            <div class='item_label item_label_color'>
              <span>Site Color</span>
              <span class='site_color_name' id='edit_site_color_name'><?= htmlspecialchars(\PayCal\Domain\Config\SiteColorPalette::labelFor(\PayCal\Domain\Config\SiteColorPalette::default()) ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class='item_value'>
              <?php
              use PayCal\Domain\Config\SiteColorPalette;
              $swatchHtml = '';
              foreach (SiteColorPalette::pickerPalette() as $idx => $sc) {
                $hex   = htmlspecialchars($sc['hex'],   ENT_QUOTES, 'UTF-8');
                $label = htmlspecialchars($sc['label'], ENT_QUOTES, 'UTF-8');
                $swatchHtml .= "<button type='button' class='site_color_swatch' data-hex='{$hex}' data-idx='{$idx}' aria-label='{$label}'></button>";
              }
              echo "<div class='site_color_picker' id='edit_site_color_picker'>"
                . "<div class='site_color_swatches' id='edit_site_color_swatches'>{$swatchHtml}</div>"
                . "<input type='hidden' id='edit_site_color_input' name='site_color' value='" . SiteColorPalette::default() . "'>"
                . "</div>";
              ?>
            </div>
          </div>
        </div>

        <!-- ── Right column: Organization Planning ────────────────────── -->
        <div class='edit_site_col edit_site_col_advanced'>
          <h3 class='edit_site_col_heading'>&#128202; Organization Planning</h3>

          <!-- Org Planning — only shown when site is linked to a managed org -->
          <section id='edit_site_org_planning' hidden>
            <input type='hidden' id='edit_site_plan_org_id' value=''>
            <input type='hidden' id='edit_site_plan_owner_uuid' value=''>
            <div class='edit_site_org_planning_body'>

              <!-- Organization (read-only) -->
              <div class='item_pair'>
                <label class='item_label'>Organization</label>
                <div class='item_value'>
                  <span id='edit_site_org_planning_org_name' class='site_field_readonly'></span>
                </div>
              </div>

              <!-- Annual Budget -->
              <div class='item_pair'>
                <label class='item_label' for='edit_site_plan_budget'>Annual Budget ($)</label>
                <div class='item_value'>
                  <input id='edit_site_plan_budget' type='number' inputmode='decimal' placeholder='0.00' step='0.01' min='0'
                    aria-describedby='edit_site_plan_status'
                    data-hover-help='Total payroll budget allocated to this site for the year. Used to track spending against plan and trigger threshold alerts.'>
                </div>
              </div>

              <!-- Warn Threshold -->
              <div class='item_pair'>
                <label class='item_label' for='edit_site_plan_warn'>Warn Threshold (%)</label>
                <div class='item_value'>
                  <input id='edit_site_plan_warn' type='number' inputmode='numeric' placeholder='80' min='1' max='100' step='1'
                    aria-describedby='edit_site_plan_status'
                    data-hover-help='A warning indicator appears when cumulative spending reaches this percentage of the annual budget. Default: 80%.'>
                </div>
              </div>

              <!-- Critical Threshold -->
              <div class='item_pair'>
                <label class='item_label' for='edit_site_plan_critical'>Critical Threshold (%)</label>
                <div class='item_value'>
                  <input id='edit_site_plan_critical' type='number' inputmode='numeric' placeholder='95' min='1' max='100' step='1'
                    aria-describedby='edit_site_plan_status'
                    data-hover-help='A critical alert appears when cumulative spending reaches this percentage of the annual budget. Default: 95%.'>
                </div>
              </div>

              <!-- Planning Status -->
              <div class='item_pair'>
                <label class='item_label' for='edit_site_plan_status_select'>Planning Status</label>
                <div class='item_value'>
                  <select id='edit_site_plan_status_select' aria-describedby='edit_site_plan_status'
                    data-hover-help='Tracks the lifecycle stage of this site within the organization. Used for filtering and reporting.'>
                    <option value='planning'>Planning</option>
                    <option value='active'>Active</option>
                    <option value='maintenance'>Maintenance</option>
                    <option value='complete'>Complete</option>
                    <option value='archived'>Archived</option>
                  </select>
                </div>
              </div>

              <!-- Client / Customer -->
              <div class='item_pair'>
                <label class='item_label' for='edit_site_client_input'>Client</label>
                <div class='item_value'>
                  <input id='edit_site_client_input' type='text' name='client_name' value='' placeholder='e.g. Imperial Oil' maxlength='100'
                    aria-describedby='edit_site_form_status'
                    data-hover-help='The client or customer this site is being worked for. Used for future invoicing and client-level reporting.'>
                </div>
              </div>

              <!-- Cost Code -->
              <div class='item_pair'>
                <label class='item_label' for='edit_site_cost_code_input'>Cost Code</label>
                <div class='item_value'>
                  <input id='edit_site_cost_code_input' type='text' name='cost_code' value='' placeholder='e.g. EDM-001' maxlength='40'
                    aria-describedby='edit_site_form_status'
                    data-hover-help='Internal or client-assigned cost code for this site. Typically used for accounting, ERP integration, or purchase orders.'>
                </div>
              </div>

              <!-- Start Date -->
              <div class='item_pair'>
                <label class='item_label' for='edit_site_start_date_input'>Start Date</label>
                <div class='item_value'>
                  <input id='edit_site_start_date_input' type='date' name='start_date' value=''
                    aria-describedby='edit_site_form_status'
                    data-hover-help='The date this site becomes active. Used for forecasting, reporting windows, and automatic status transitions.'>
                </div>
              </div>

              <!-- End Date -->
              <div class='item_pair'>
                <label class='item_label' for='edit_site_end_date_input'>End Date</label>
                <div class='item_value'>
                  <input id='edit_site_end_date_input' type='date' name='end_date' value=''
                    aria-describedby='edit_site_form_status'
                    data-hover-help='The projected or actual completion date for this site. Sites past their end date can be flagged for archival.'>
                </div>
              </div>

            </div>
            <div id='edit_site_plan_status' class='status_message' role='status' aria-live='polite' hidden></div>
          </section>
          <p id='edit_site_org_planning_empty' class='edit_site_org_planning_empty'>This site is not linked to an organization. Link it from the Organizations page to unlock planning tools.</p>
        </div>

      </section>
      <section class='modal_footer'>
        <div id='edit_site_form_status' class='status_message centered' role='status' aria-live='polite'></div>
        <div class='flex f_center f_space_around'>
          <button type='submit' id='edit_site_submit' class='btn btn_primary'>Save Site</button>
          <button type='button' id='edit_site_cancel' class='btn btn_secondary' data-dialog-close='modal_edit_site'>
            <?php echo sites_index_i18n('CLOSE'); ?>
          </button>
        </div>
      </section>
    </form>
  </dialog>

  <!-- Confirmation Dialog for Delete Site -->
  <dialog id='modal_confirm_delete_site' class='dialog' aria-modal='true' aria-labelledby='modal_confirm_delete_site_title' aria-describedby='confirm_delete_site_aria confirm_delete_site_message'>
    <p id='confirm_delete_site_aria' class='visually_hidden'>Review the archive confirmation for this site. This action hides the site and keeps existing work history.</p>
    <section class='modal_header'>
      <button type='button' class='btn_close' data-dialog-close='modal_confirm_delete_site' aria-label='<?php echo sites_index_i18n('CLOSE'); ?>'>&times;</button>
      <h2 id='modal_confirm_delete_site_title' class='modal_title'><?php echo sites_index_i18n('CONFIRM_DELETE'); ?></h2>
    </section>
    <section class='modal_content'>
      <p id='confirm_delete_site_message'></p>
    </section>
    <section class='modal_footer'>
      <div class='flex f_center f_space_around'>
        <button type='button' id='confirm_delete_site_yes' class='btn btn_primary'>
          Archive Site
        </button>
        <button type='button' id='confirm_delete_site_no' class='btn btn_secondary'>
          <?php echo sites_index_i18n('CLOSE'); ?>
        </button>
      </div>
    </section>
  </dialog>

  <!-- Archived Work Viewer Dialog -->
  <dialog id='modal_archived_work' class='dialog modal_archived_work' aria-modal='true' aria-labelledby='archived_work_title' aria-describedby='archived_work_aria archived_work_content'>
    <p id='archived_work_aria' class='visually_hidden'><?php echo sites_index_i18n('SITES_ARCHIVED_WORK_ARIA'); ?></p>
    <section class='modal_header'>
      <button type='button' class='btn_close' data-dialog-close='modal_archived_work' aria-label='<?php echo sites_index_i18n('CLOSE'); ?>'>&times;</button>
      <h2 class='modal_title' id='archived_work_title'><?php echo sites_index_i18n('SITES_ARCHIVED_WORK_TITLE'); ?></h2>
    </section>
    <section class='modal_content' id='archived_work_content'>
      <p class='archived_work_loading'>Loading...</p>
    </section>
    <section class='modal_footer'>
      <div class='flex f_center f_space_between archived_controls_container'>
        <button type='button' id='archived_work_finality_delete' class='btn btn_danger hidden'>
          🗑️ Finality Delete (Permanent)
        </button>
        <button type='button' id='archived_work_close' class='btn btn_secondary'>
          Close
        </button>
      </div>
    </section>
  </dialog>

  <!-- Finality Delete Confirmation Dialog -->
  <dialog id='modal_finality_delete' class='dialog' aria-modal='true' aria-labelledby='modal_finality_delete_title' aria-describedby='finality_delete_aria finality_delete_message'>
    <p id='finality_delete_aria' class='visually_hidden'><?php echo sites_index_i18n('SITES_FINALITY_DELETE_ARIA'); ?></p>
    <section class='modal_header'>
      <button type='button' class='btn_close' data-dialog-close='modal_finality_delete' aria-label='<?php echo sites_index_i18n('CLOSE'); ?>'>&times;</button>
      <h2 id='modal_finality_delete_title' class='modal_title modal_title_danger'>⚠️ <?php echo sites_index_i18n('SITES_FINALITY_DELETE_TITLE'); ?></h2>
    </section>
    <section class='modal_content'>
      <p id='finality_delete_message'></p>
    </section>
    <section class='modal_footer'>
      <div class='flex f_center f_space_around'>
        <button type='button' id='finality_delete_yes' class='btn btn_danger'>
          Yes, Permanently Delete
        </button>
        <button type='button' id='finality_delete_no' class='btn btn_secondary'>
          Close
        </button>
      </div>
    </section>
  </dialog>

  <!-- Orphaned Work Recovery Dialog -->
  <dialog id='modal_orphaned_work' class='dialog modal_orphaned_work' aria-modal='true' aria-labelledby='modal_orphaned_work_title' aria-describedby='modal_orphaned_work_aria modal_orphaned_work_desc'>
    <p id='modal_orphaned_work_aria' class='visually_hidden'><?php echo sites_index_i18n('SITES_ORPHANED_MODAL_ARIA'); ?></p>
    <section class='modal_header'>
      <h2 id='modal_orphaned_work_title' class='modal_title'>🔧 <?php echo sites_index_i18n('SITES_ORPHANED_MODAL_TITLE'); ?></h2>
      <button type='button' class='btn_close' data-dialog-close='modal_orphaned_work' aria-label='<?php echo sites_index_i18n('CLOSE'); ?>'>&times;</button>
    </section>
    <section class='modal_content'>
      <div id='modal_orphaned_work_desc' class='orphaned_work_disclaimer'>
        <?php echo sites_index_i18n('SITES_ORPHANED_MODAL_DESC'); ?>
      </div>
      <div id='orphaned_groups_container' class='orphaned_groups_container'>
        <!-- Orphaned groups populated via JavaScript -->
      </div>
    </section>
    <section class='modal_footer'>
      <button type='button' class='btn btn_secondary' data-dialog-close='modal_orphaned_work'>
        Close
      </button>
    </section>
  </dialog>

  <!-- Recovery Site Dialog -->
  <dialog id='modal_recovery_site' class='dialog' aria-modal='true' aria-labelledby='modal_recovery_site_title' aria-describedby='recovery_site_aria recovery_site_info'>
    <p id='recovery_site_aria' class='visually_hidden'><?php echo sites_index_i18n('SITES_RECOVERY_SITE_ARIA'); ?></p>
    <section class='modal_header'>
      <h2 id='modal_recovery_site_title' class='modal_title'><?php echo sites_index_i18n('SITES_RECOVERY_SITE_TITLE'); ?></h2>
      <button type='button' class='btn_close' data-dialog-close='modal_recovery_site' aria-label='<?php echo sites_index_i18n('CLOSE'); ?>'>&times;</button>
    </section>
    <form id='recovery_site_form'>
      <input type='hidden' id='recovery_orphaned_site_id' name='orphaned_site_id' value=''>
      <section class='modal_content'>
        <p id='recovery_site_info' class='recovery_site_info'>
          Creating a site for <strong id='recovery_site_name_display'></strong><br>
          <span id='recovery_work_count_display'></span> work entries will be bound to this site.
        </p>

        <div class='form_group'>
          <label for='recovery_site_name_input'>Site Name</label>
          <input
            type='text'
            id='recovery_site_name_input'
            name='site_name'
            required
            class='form_control'
            placeholder='Enter site name'
            aria-describedby='recovery_site_form_status recovery_site_name_error'
          />
          <small id='recovery_site_name_error' class='status_text compact_hint' role='status' aria-live='polite'></small>
        </div>

        <div class='form_row'>
          <div class='form_group'>
            <label for='recovery_site_wage_input'>Hourly Wage</label>
            <input
              type='number'
              id='recovery_site_wage_input'
              name='wage'
              step='0.01'
              min='0'
              class='form_control'
              placeholder='0.00'
              aria-describedby='recovery_site_form_status recovery_site_wage_error'
            />
            <small id='recovery_site_wage_error' class='status_text compact_hint' role='status' aria-live='polite'></small>
          </div>

          <div class='form_group'>
            <label for='recovery_site_loa_input'>Living Out Allowance</label>
            <input 
              type='number' 
              id='recovery_site_loa_input' 
              name='living_out_allowance' 
              step='0.01' 
              min='0'
              class='form_control'
              placeholder='0.00'
              aria-describedby='recovery_site_form_status'
            />
          </div>
        </div>

        <div class='form_row'>
          <div class='form_group'>
            <label for='recovery_site_travel_input'>Daily Travel Hours</label>
            <input 
              type='number' 
              id='recovery_site_travel_input' 
              name='travel_hours' 
              step='0.01' 
              min='0'
              class='form_control'
              placeholder='0.00'
              aria-describedby='recovery_site_form_status'
            />
          </div>

          <div class='form_group'>
            <label for='recovery_site_province_select'>Province</label>
            <select id='recovery_site_province_select' name='province' class='form_control' aria-describedby='recovery_site_form_status'>
              <option value='AB'>Alberta</option>
              <option value='BC'>British Columbia</option>
              <option value='SK'>Saskatchewan</option>
              <option value='MB'>Manitoba</option>
              <option value='ON'>Ontario</option>
              <option value='QC'>Quebec</option>
              <option value='NB'>New Brunswick</option>
              <option value='NS'>Nova Scotia</option>
              <option value='PE'>Prince Edward Island</option>
              <option value='NL'>Newfoundland and Labrador</option>
              <option value='YT'>Yukon</option>
              <option value='NT'>Northwest Territories</option>
              <option value='NU'>Nunavut</option>
            </select>
          </div>
        </div>
      </section>

      <section class='modal_footer'>
        <div id='recovery_site_form_status' class='status_message centered' role='status' aria-live='polite'></div>
        <div class='flex f_center f_space_around'>
          <button type='submit' id='recovery_site_submit' class='btn btn_primary'>
            Create Site & Bind Work
          </button>
          <button type='button' class='btn btn_secondary' data-dialog-close='modal_recovery_site'>
            Close
          </button>
        </div>
      </section>
    </form>
  </dialog>


<?php

echo PHP_EOL."<link rel=\"stylesheet\" href=\"" . Render::cssURL('datagrid') . "\">".PHP_EOL;
echo PHP_EOL."<link rel=\"stylesheet\" href=\"" . Render::cssURL('sites') . "\">".PHP_EOL;
echo PHP_EOL.Render::jsScript('sites');
Lens::render();

require_once Environment::appHome().'html/footer.php';
