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

$message = '&nbsp;';
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
          <div id='site_earnings_loading' class='f_center earnings_loading_container'>
            <span>Loading earnings data…</span>
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
  <dialog id='modal_create_site' class='dialog' aria-labelledby='modal_create_site_title' aria-describedby='modal_create_site_aria'>
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
              type='text'
              name='wage'
              value=''
              placeholder='<?php echo sites_index_i18n('WAGE'); ?>'
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
              type='text'
              name='living_out_allowance'
              value=''
              placeholder='<?php echo sites_index_i18n('LIVING_OUT_ALLOWANCE'); ?>'
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
              type='text'
              name='travel_hours'
              value=''
              placeholder='<?php echo sites_index_i18n('TRAVEL_HOURLY_RATE'); ?>'
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
  <dialog id='modal_edit_site' class='dialog' aria-labelledby='modal_edit_site_title' aria-describedby='modal_edit_site_aria'>
    <div class='modal_aria visually_hidden'>
      <span id='modal_edit_site_aria'><?php echo sites_index_i18n('EDIT_SITE'); ?></span>
    </div>
    <form id='edit_site_form' method='POST' action='<?php echo Environment::appURL('api/sites/update/'); ?>'>
      <input type='hidden' id='edit_site_id' name='id' value=''>
      <section class='modal_header'>
        <h2 id='modal_edit_site_title' class='modal_title'><?php echo sites_index_i18n('EDIT_SITE'); ?></h2>
        <button type='button' class='btn_close' data-dialog-close='modal_edit_site' aria-label='<?php echo sites_index_i18n('CLOSE'); ?>'>&times;</button>
      </section>
      <section class='modal_content f_column'>
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
              type='text'
              name='wage'
              value=''
              placeholder='<?php echo sites_index_i18n('WAGE'); ?>'
              required
              aria-required='true'
              aria-describedby='edit_site_form_status edit_site_wage_error'
            >
            <small id='edit_site_wage_error' class='status_text compact_hint' role='status' aria-live='polite'></small>
          </div>
        </div>

        <!-- Living Out Allowance -->
        <div class='item_pair'>
          <label class='item_label' for='edit_site_loa_input'><?php echo sites_index_i18n('LIVING_OUT_ALLOWANCE'); ?></label>
          <div class='item_value'>
            <input
              id='edit_site_loa_input'
              type='text'
              name='living_out_allowance'
              value=''
              placeholder='<?php echo sites_index_i18n('LIVING_OUT_ALLOWANCE'); ?>'
              aria-describedby='edit_site_form_status'
            >
          </div>
        </div>

        <!-- Travel Hourly Rate -->
        <div class='item_pair'>
          <label class='item_label' for='edit_site_travel_input'><?php echo sites_index_i18n('TRAVEL_HOURLY_RATE'); ?></label>
          <div class='item_value'>
            <input
              id='edit_site_travel_input'
              type='text'
              name='travel_hours'
              value=''
              placeholder='<?php echo sites_index_i18n('TRAVEL_HOURLY_RATE'); ?>'
              aria-describedby='edit_site_form_status'
            >
          </div>
        </div>

        <!-- Province -->
        <div class='item_pair'>
          <label class='item_label' for='edit_site_province_select'><?php echo sites_index_i18n('PROVINCE'); ?></label>
          <div class='item_value'>
            <select id='edit_site_province_select' name='province' aria-describedby='edit_site_form_status'>
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
            <select id='edit_site_status_select' name='status' aria-describedby='edit_site_form_status'>
              <option value='active'><?php echo sites_index_i18n('ACTIVE'); ?></option>
              <option value='archived'>Archived</option>
            </select>
          </div>
        </div>
      </section>
      <section class='modal_footer'>
        <div id='edit_site_form_status' class='status_message centered' role='status' aria-live='polite'></div>
        <div class='flex f_center f_space_around'>
          <button type='submit' id='edit_site_submit' class='btn btn_primary'>
            <?php echo sites_index_i18n('SAVE'); ?>
          </button>
          <button type='button' id='edit_site_cancel' class='btn btn_secondary' data-dialog-close='modal_edit_site'>
            <?php echo sites_index_i18n('CLOSE'); ?>
          </button>
        </div>
      </section>
    </form>
  </dialog>

  <!-- Confirmation Dialog for Delete Site -->
  <dialog id='modal_confirm_delete_site' class='dialog' aria-labelledby='modal_confirm_delete_site_title' aria-describedby='confirm_delete_site_aria confirm_delete_site_message'>
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
  <dialog id='modal_archived_work' class='dialog modal_archived_work' aria-labelledby='archived_work_title' aria-describedby='archived_work_aria archived_work_content'>
    <p id='archived_work_aria' class='visually_hidden'>Review archived work records for the selected site, then close to return to site management.</p>
    <section class='modal_header'>
      <button type='button' class='btn_close' data-dialog-close='modal_archived_work' aria-label='<?php echo sites_index_i18n('CLOSE'); ?>'>&times;</button>
      <h2 class='modal_title' id='archived_work_title'>Archived Work</h2>
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
  <dialog id='modal_finality_delete' class='dialog' aria-labelledby='modal_finality_delete_title' aria-describedby='finality_delete_aria finality_delete_message'>
    <p id='finality_delete_aria' class='visually_hidden'>This confirms permanent deletion of archived work data. Continue only if you intend to remove records permanently.</p>
    <section class='modal_header'>
      <button type='button' class='btn_close' data-dialog-close='modal_finality_delete' aria-label='<?php echo sites_index_i18n('CLOSE'); ?>'>&times;</button>
      <h2 id='modal_finality_delete_title' class='modal_title modal_title_danger'>⚠️ PERMANENT DELETION</h2>
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
  <dialog id='modal_orphaned_work' class='dialog modal_orphaned_work' aria-labelledby='modal_orphaned_work_title' aria-describedby='modal_orphaned_work_aria modal_orphaned_work_desc'>
    <p id='modal_orphaned_work_aria' class='visually_hidden'>Recover orphaned work entries by linking each orphaned site identifier to a real site.</p>
    <section class='modal_header'>
      <h2 id='modal_orphaned_work_title' class='modal_title'>🔧 Recover Orphaned Work Entries</h2>
      <button type='button' class='btn_close' data-dialog-close='modal_orphaned_work' aria-label='Close'>&times;</button>
    </section>
    <section class='modal_content'>
      <div id='modal_orphaned_work_desc' class='orphaned_work_disclaimer'>
        The following work entries have no corresponding site. You can recover them by creating a site to bind the data to.
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
  <dialog id='modal_recovery_site' class='dialog' aria-labelledby='modal_recovery_site_title' aria-describedby='recovery_site_aria recovery_site_info'>
    <p id='recovery_site_aria' class='visually_hidden'>Create a site to attach orphaned work entries and restore them to normal reporting.</p>
    <section class='modal_header'>
      <h2 id='modal_recovery_site_title' class='modal_title'>Create Site for Orphaned Work</h2>
      <button type='button' class='btn_close' data-dialog-close='modal_recovery_site' aria-label='Close'>&times;</button>
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
