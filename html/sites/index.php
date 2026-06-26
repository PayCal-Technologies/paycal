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

$sitesCsrfNonce = User::current()->generateFormNonce('settings');

$pageTitle = sites_index_i18n('SITES') . ' - [' . sites_index_i18n('SITE_NAME') . ']';
$pageLabel = sites_index_i18n('SITES');
$pageLanguage = (string) User::current()->language;

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
        <strong id='orphaned_work_count'>0 <?php echo sites_index_i18n('SITES_ORPHANED_WORK_ENTRIES'); ?></strong> <?php echo sites_index_i18n('SITES_ORPHANED_FOUND_SUFFIX'); ?>
        <span class='orphaned_work_banner_hint'><?php echo sites_index_i18n('SITES_ORPHANED_RECOVER_HINT'); ?></span>
      </div>
      <button type='button' id='btn_show_orphaned_work' class='btn btn_warning'>
        <?php echo sites_index_i18n('SITES_RECOVER_DATA'); ?>
      </button>
    </div>
  </aside>

  <!-- Two-panel layout: Sites DataGrid | Earnings Analytics -->
  <div class='flex w100 sites_main_container'>

    <!-- LEFT PANEL: Sites DataGrid -->
    <div class='f_column w50'>
      <section id='sites_list_panel' class='f_column panel tab-content sites_list_panel' title='<?php echo sites_index_i18n('SITES_GRID_BROWSE_HELP'); ?>' data-hover-help='<?php echo sites_index_i18n('SITES_GRID_BROWSE_HELP'); ?>'>
        <div class="sites_status_row">
          <div class="sites_status_action_group" role="group" aria-label="<?php echo sites_index_i18n('SITES_TABS_ARIA_LABEL'); ?>">
            <ul class='tabs sites_status_tabs' role="tablist" aria-label="<?php echo sites_index_i18n('SITES_TABS_ARIA_LABEL'); ?>">
              <li>
                <button
                  id="tab-active_sites"
                  type="button"
                  data-tab-target="#active_sites"
                  class="tab active"
                  tabindex="0"
                  role="tab"
                  aria-selected="true"
                  aria-controls="active_sites"
                ><?php echo sites_index_i18n('ACTIVE'); ?></button>
              </li>
              <li>
                <button
                  id="tab-archived_sites"
                  type="button"
                  data-tab-target="#archived_sites"
                  class="tab"
                  tabindex="-1"
                  role="tab"
                  aria-selected="false"
                  aria-controls="archived_sites"
                ><?php echo sites_index_i18n('ARCHIVED'); ?></button>
              </li>
            </ul>
            <button
              type="button"
              class="btn btn_primary sites_status_add_button"
              data-action="create-site"
              data-sites-visible-tab="active_sites"
              aria-label="<?php echo sites_index_i18n('BUSINESS_SITES_ADD_ARIA'); ?>"
            ><?php echo sites_index_i18n('BUSINESS_STATUS_ADD_SHORT'); ?></button>
          </div>
          <div class="sites_ownership_legend" aria-label="<?php echo sites_index_i18n('BUSINESS_SITES_OWNERSHIP_LEGEND_ARIA'); ?>">
            <span class="sites_ownership_legend_item">
              <span class="business_sites_ownership_symbol business_sites_ownership_symbol--personal" aria-hidden="true"></span>
              <span><?php echo sites_index_i18n('BUSINESS_SITES_STATUS_TAG_PERSONAL'); ?></span>
            </span>
          </div>
        </div>

        <div id='active_sites' data-tab-content='active_sites' class='active' role="tabpanel" aria-labelledby="tab-active_sites">
          <div class="visually_hidden">
            <p id="sites_grid_active_sr_instructions"><?php echo sites_index_i18n('SITES_GRID_ACTIVE_SR_INSTRUCTIONS'); ?></p>
            <p id="sites_grid_active_sr_context"><?php echo sites_index_i18n('SITES_GRID_ACTIVE_SR_CONTEXT'); ?></p>
            <p id="sites_grid_active_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
          </div>
          <div id="sites-grid-active" class="datagrid_container" role="region" aria-label="<?php echo sites_index_i18n('SITES_ACTIVE_RESULTS_ARIA'); ?>" aria-describedby="sites_grid_active_sr_instructions sites_grid_active_sr_context sites_grid_active_sr_status">
            <div class="datagrid_body"><?php echo DataGrid::loadingSkeleton(7, 4); ?></div>
          </div>
        </div>
        <div id='archived_sites' data-tab-content='archived_sites' role="tabpanel" aria-labelledby="tab-archived_sites">
          <div class="visually_hidden">
            <p id="sites_grid_archived_sr_instructions"><?php echo sites_index_i18n('SITES_GRID_ARCHIVED_SR_INSTRUCTIONS'); ?></p>
            <p id="sites_grid_archived_sr_context"><?php echo sites_index_i18n('SITES_GRID_ARCHIVED_SR_CONTEXT'); ?></p>
            <p id="sites_grid_archived_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
          </div>
          <div id="sites-grid-archived" class="datagrid_container" role="region" aria-label="<?php echo sites_index_i18n('SITES_ARCHIVED_RESULTS_ARIA'); ?>" aria-describedby="sites_grid_archived_sr_instructions sites_grid_archived_sr_context sites_grid_archived_sr_status">
            <div class="datagrid_body"><?php echo DataGrid::loadingSkeleton(7, 4); ?></div>
          </div>
        </div>
      </section>
    </div>

    <!-- RIGHT PANEL: Earnings Analytics -->
    <div class='f_column w50'>
      <section id='sites_earnings_panel' class='panel tab-content sites_earnings_panel' title='<?php echo sites_index_i18n('SITES_EARNINGS_REVIEW_HELP'); ?>' data-hover-help='<?php echo sites_index_i18n('SITES_EARNINGS_REVIEW_HELP'); ?>'>
        <ul id='earnings_year_tabs' class='tabs' role='tablist' aria-label='<?php echo sites_index_i18n('SITES_EARNINGS_YEAR_TABS_ARIA'); ?>'>
          <!-- Year tabs populated dynamically -->
        </ul>
        <p class='tab-disclaimer'><?php echo sites_index_i18n('SITES_EARNINGS_HISTORY_DISCLAIMER'); ?></p>

        <div id='site_earnings_container'>
          <div id='site_earnings_loading' class='earnings_loading_container skeleton site_earnings_state is-active' aria-label="<?php echo sites_index_i18n('SITES_LOADING_EARNINGS_DATA'); ?>" aria-live="polite">
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

          <div id='site_earnings_list' class='site_earnings_state' aria-hidden="true" inert>
            <!-- Site earnings rows populated via JavaScript -->
          </div>

          <div id='site_earnings_totals' class='site_earnings_state earnings_totals_container' aria-hidden="true" inert>
            <!-- Totals summary populated via JavaScript -->
          </div>

          <div id='site_earnings_empty' class='site_earnings_state f_center earnings_empty_container' aria-hidden="true" inert>
            <span><?php echo sites_index_i18n('SITES_NO_EARNINGS_FOR_YEAR'); ?></span>
          </div>
        </div>
      </section>
    </div>

  </div>

<?php
$siteEditorContext = 'personal';
require __DIR__ . '/_partials/site_editor_dialogs.php';
?>


  <!-- Orphaned Work Recovery Dialog -->
  <dialog id='modal_orphaned_work' data-dialog-invoker-bridge data-dialog-close-tts='<?php echo sites_index_i18n('SITES_ORPHANED_MODAL_TITLE'); ?>' class='dialog modal_orphaned_work' aria-modal='true' aria-labelledby='modal_orphaned_work_title' aria-describedby='modal_orphaned_work_aria modal_orphaned_work_desc'>
    <p id='modal_orphaned_work_aria' class='visually_hidden'><?php echo sites_index_i18n('SITES_ORPHANED_MODAL_ARIA'); ?></p>
    <section class='modal_header'>
      <h2 id='modal_orphaned_work_title' class='modal_title'>🔧 <?php echo sites_index_i18n('SITES_ORPHANED_MODAL_TITLE'); ?></h2>
      <button type='button' class='btn_close' data-dialog-close='modal_orphaned_work' commandfor='modal_orphaned_work' command='close' aria-label='<?php echo sites_index_i18n('CLOSE'); ?>'>&times;</button>
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
      <button type='button' class='btn btn_secondary' data-dialog-close='modal_orphaned_work' commandfor='modal_orphaned_work' command='close'>
        <?php echo sites_index_i18n('CLOSE'); ?>
      </button>
    </section>
  </dialog>

  <!-- Recovery Site Dialog -->
  <dialog id='modal_recovery_site' data-dialog-invoker-bridge data-dialog-close-tts='<?php echo sites_index_i18n('SITES_RECOVERY_SITE_TITLE'); ?>' class='dialog' aria-modal='true' aria-labelledby='modal_recovery_site_title' aria-describedby='recovery_site_aria recovery_site_info'>
    <p id='recovery_site_aria' class='visually_hidden'><?php echo sites_index_i18n('SITES_RECOVERY_SITE_ARIA'); ?></p>
    <section class='modal_header'>
      <h2 id='modal_recovery_site_title' class='modal_title'><?php echo sites_index_i18n('SITES_RECOVERY_SITE_TITLE'); ?></h2>
      <button type='button' class='btn_close' data-dialog-close='modal_recovery_site' commandfor='modal_recovery_site' command='close' aria-label='<?php echo sites_index_i18n('CLOSE'); ?>'>&times;</button>
    </section>
    <form id='recovery_site_form'>
      <input type='hidden' id='recovery_orphaned_site_id' name='orphaned_site_id' value=''>
      <section class='modal_content'>
        <p id='recovery_site_info' class='recovery_site_info'>
          <?php echo sites_index_i18n('SITES_RECOVERY_INFO_PREFIX'); ?> <strong id='recovery_site_name_display'></strong><br>
          <span id='recovery_work_count_display'></span> <?php echo sites_index_i18n('SITES_RECOVERY_INFO_SUFFIX'); ?>
        </p>

        <div class='form_group'>
          <label for='recovery_site_name_input'><?php echo sites_index_i18n('SITES_SITE_NAME_LABEL'); ?></label>
          <input
            type='text'
            id='recovery_site_name_input'
            name='site_name'
            required
            class='form_control'
            placeholder='<?php echo sites_index_i18n('SITES_ENTER_SITE_NAME_PLACEHOLDER'); ?>'
            aria-describedby='recovery_site_form_status recovery_site_name_error'
          />
          <small id='recovery_site_name_error' class='status_text compact_hint' role='status' aria-live='polite'></small>
        </div>

        <div class='form_row'>
          <div class='form_group'>
            <label for='recovery_site_wage_input'><?php echo sites_index_i18n('SITES_HOURLY_WAGE'); ?></label>
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
            <label for='recovery_site_loa_input'><?php echo sites_index_i18n('LIVING_OUT_ALLOWANCE'); ?></label>
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
            <label for='recovery_site_travel_input'><?php echo sites_index_i18n('SITES_DAILY_TRAVEL_HOURS'); ?></label>
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
            <label for='recovery_site_province_select'><?php echo sites_index_i18n('PROVINCE'); ?></label>
            <select id='recovery_site_province_select' name='province' class='form_control' aria-describedby='recovery_site_form_status'>
              <option value='AB'><?php echo sites_index_i18n('PROFILE_PROVINCE_AB'); ?></option>
              <option value='BC'><?php echo sites_index_i18n('PROFILE_PROVINCE_BC'); ?></option>
              <option value='SK'><?php echo sites_index_i18n('PROFILE_PROVINCE_SK'); ?></option>
              <option value='MB'><?php echo sites_index_i18n('PROFILE_PROVINCE_MB'); ?></option>
              <option value='ON'><?php echo sites_index_i18n('PROFILE_PROVINCE_ON'); ?></option>
              <option value='QC'><?php echo sites_index_i18n('PROFILE_PROVINCE_QC'); ?></option>
              <option value='NB'><?php echo sites_index_i18n('PROFILE_PROVINCE_NB'); ?></option>
              <option value='NS'><?php echo sites_index_i18n('PROFILE_PROVINCE_NS'); ?></option>
              <option value='PE'><?php echo sites_index_i18n('PROFILE_PROVINCE_PE'); ?></option>
              <option value='NL'><?php echo sites_index_i18n('PROFILE_PROVINCE_NL'); ?></option>
              <option value='YT'><?php echo sites_index_i18n('PROFILE_PROVINCE_YT'); ?></option>
              <option value='NT'><?php echo sites_index_i18n('PROFILE_PROVINCE_NT'); ?></option>
              <option value='NU'><?php echo sites_index_i18n('PROFILE_PROVINCE_NU'); ?></option>
            </select>
          </div>
        </div>
      </section>

      <section class='modal_footer'>
        <div id='recovery_site_form_status' class='status_message centered' role='status' aria-live='polite'></div>
        <div class='flex f_center f_space_around'>
          <button type='submit' id='recovery_site_submit' class='btn btn_primary'>
            <?php echo sites_index_i18n('SITES_CREATE_SITE_BIND_WORK'); ?>
          </button>
          <button type='button' class='btn btn_secondary' data-dialog-close='modal_recovery_site' commandfor='modal_recovery_site' command='close'>
            <?php echo sites_index_i18n('CLOSE'); ?>
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
