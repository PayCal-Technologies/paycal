<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

$currentPage = 'PAGE_BUSINESS_REPORTS';

require_once dirname(__DIR__) . '/_layout.php';

$i18nKeys = [
  'EARNINGS_TEAM_EARNINGS',
  'EARNINGS_SELECT_BUSINESS',
  'EARNINGS_PRINT_REPORT',
  'NAME',
  'USER_ROLE',
  'REGULAR_HOURS',
  'OVERTIME_HOURS',
  'GROSS',
  'EARNINGS_BREAKDOWN_TOTAL',
  'CLOSE',
  'BUSINESS_PAYROLL_PACKAGE_HEADING',
  'BUSINESS_PAYROLL_PACKAGE_DESC',
  'BUSINESS_PAYROLL_PACKAGE_GENERATE',
  'BUSINESS_PAYROLL_PACKAGE_OPTIONS_ARIA',
  'BUSINESS_PAYROLL_PACKAGE_YEAR',
  'BUSINESS_PAYROLL_PACKAGE_LABEL',
  'BUSINESS_PAYROLL_PACKAGE_YEARLY_EVIDENCE',
  'BUSINESS_PAYROLL_PACKAGE_NOTICE',
  'BUSINESS_PAYROLL_PACKAGE_GENERATED_HEADING',
  'MEMBERS',
  'BUSINESS_PAYROLL_PACKAGE_FILES',
  'BUSINESS_PAYROLL_PACKAGE_COMPLETED',
  'BUSINESS_PAYROLL_PACKAGE_EXCEPTIONS',
  'BUSINESS_PAYROLL_PACKAGE_TIME',
  'BUSINESS_EXPORT_SECONDS',
  'BUSINESS_EXPORT_DOWNLOAD_ZIP',
  'BUSINESS_EXPORT_DOWNLOAD_MANIFEST',
  'BUSINESS_EXPORT_VIEW_AUDIT_LOG',
  'BUSINESS_REPORTS_TOOLBAR_ARIA',
  'BUSINESS_REPORTS_SECTIONS_ARIA',
  'BUSINESS_REPORTS_FILTERS_ARIA',
  'BUSINESS_REPORTS_FILTER_PERIOD_ARIA',
  'BUSINESS_REPORTS_FILTER_COMPARE_ARIA',
  'BUSINESS_REPORTS_FILTER_SITE_ARIA',
  'BUSINESS_REPORTS_FILTER_GROUP_ARIA',
  'BUSINESS_REPORTS_FILTER_MEMBER_ARIA',
  'BUSINESS_REPORTS_CUSTOMIZE_DRAWER_ARIA',
  'BUSINESS_REPORTS_PRESETS_ARIA',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}

$isLensMode = InputSanitizer::getString('lens') === '1';
$teamEarningsYear = (int) (InputSanitizer::getString('year') ?: date('Y'));
$preferredOrgId = $workspaceBusinessId;

require __DIR__ . '/../../reports/_partials/team_earnings_helpers.php';
require __DIR__ . '/../../reports/_partials/team_earnings_org.php';

Lens::boot('business-reports');
Lens::timeStart('Business Reports: page bootstrap through layout');

$teamReportsBaseUrl = '/business/reports/';
$reportsPanelRenderer = new BusinessReportsPanelRenderer();
$teamReportsStatusMessage = $hasOrgMembership
  ? businesses_index_i18n('BUSINESSES_LOADING')
  : Strings::i18n('BUSINESS_REPORTS_NO_ACCESS');
$teamPanelHtml = $reportsPanelRenderer->loadingSkeleton($teamEarningsYear);
$teamPanelRenderSuccess = false;
$teamPanelRenderFailed = false;
$teamEarningsRows = [];
$teamSiteMatchStats = [];

if ($hasOrgMembership) {
  try {
    Lens::timeEnd('Business Reports: page bootstrap through layout');
    Lens::timeStart('Business Reports: business reports data');
    require __DIR__ . '/../../reports/_partials/team_earnings_data.php';
    Lens::timeEnd('Business Reports: business reports data');

    Lens::timeStart('Business Reports: group panel render');
    ob_start();
    require __DIR__ . '/../../reports/_partials/team_earnings_panel.php';
    $teamPanelHtml = (string) ob_get_clean();
    $teamPanelRenderSuccess = true;
    Lens::timeEnd('Business Reports: group panel render');

    $memberCount = count($teamEarningsRows);
    $teamReportsStatusMessage = $memberCount > 0
      ? sprintf(
        businesses_index_i18n('BUSINESS_REPORTS_ANALYTICS_LOADED'),
        $memberCount,
        $memberCount === 1 ? '' : 's',
      )
      : sprintf(businesses_index_i18n('BUSINESS_REPORTS_LOADED_NO_ROWS'), $teamEarningsYear);

    if (trim($teamPanelHtml) === '') {
      $teamPanelHtml = '<section class="panel business_reports_empty" role="status" aria-live="polite">'
        . '<p class="help_text">' . htmlspecialchars(
          sprintf(businesses_index_i18n('BUSINESS_REPORTS_NO_ANALYTICS_YEAR'), $teamEarningsYear),
          ENT_QUOTES,
          'UTF-8',
        ) . '</p>'
        . '</section>';
    }
  } catch (\Throwable $teamPanelException) {
    $teamPanelRenderFailed = true;
    $teamReportsStatusMessage = businesses_index_i18n('BUSINESSES_FAILED_LOAD_MEMBERS_GRID');
    $teamPanelHtml = '<section class="panel business_reports_error" role="alert" aria-live="assertive">'
      . '<p class="help_text">' . htmlspecialchars($teamReportsStatusMessage, ENT_QUOTES, 'UTF-8') . '</p>';
    if ($isLensMode) {
      $teamPanelHtml .= '<p class="help_text business_reports_lens_error">'
        . htmlspecialchars($teamPanelException->getMessage(), ENT_QUOTES, 'UTF-8')
        . '</p>';
    }
    $teamPanelHtml .= '</section>';
    Lens::add('Business Reports: group panel render failed', [
      'message' => $teamPanelException->getMessage(),
      'selected_org' => $selectedOrgId,
      'year' => $teamEarningsYear,
    ], 'error');
  }
} else {
  Lens::timeEnd('Business Reports: page bootstrap through layout');
}

$businessReportsLensSnapshot = [
  'is_lens_mode' => $isLensMode,
  'has_org_membership' => $hasOrgMembership,
  'selected_org_id' => $selectedOrgId,
  'workspace_business_id' => $workspaceBusinessId,
  'team_earnings_year' => $teamEarningsYear,
  'active_org_count' => count($activeOrgs),
  'member_count' => count($teamEarningsRows),
  'team_panel_render_failed' => $teamPanelRenderFailed,
  'ssr_panel_render_success' => $teamPanelRenderSuccess,
  'team_panel_html_length' => strlen($teamPanelHtml),
  'team_site_match_stats' => $teamSiteMatchStats,
  'ssr_status_message' => $teamReportsStatusMessage,
];

Lens::add('Business Reports: page snapshot', $businessReportsLensSnapshot);

if ($isLensMode) {
  Lens::add('Business Reports: activeOrgs', $activeOrgs);
  Lens::add('Business Reports: selectedOrgId', $selectedOrgId);
  Lens::add('Business Reports: hasOrgMembership', $hasOrgMembership);
  Lens::add('Business Reports: userUUID', $userUUID);
  Lens::add('Business Reports: raw memberships', array_map(
    fn($m) => ['org_id' => $m['org_id'], 'status' => $m['status'], 'role' => $m['role']],
    $allMemberships
  ));
}

$selectedReportSite = InputSanitizer::getString('site');
$selectedReportGroup = InputSanitizer::getString('group');
$selectedReportMember = InputSanitizer::getString('member');

$reportSiteOptions = [];
foreach ($orgSiteRefData_ as $siteRef => $siteRollup) {
  if (!is_array($siteRollup)) {
    continue;
  }
  $siteValue = trim((string) ($siteRollup['site_ref'] ?? $siteRef));
  $siteLabel = trim((string) ($siteRollup['site_name'] ?? $siteValue));
  if ($siteValue !== '' && $siteLabel !== '') {
    $reportSiteOptions[$siteValue] = $siteLabel;
  }
}
asort($reportSiteOptions, SORT_NATURAL | SORT_FLAG_CASE);

$reportGroupOptions = [];
foreach ($businessGroupData_ as $groupRollup) {
  if (!is_array($groupRollup)) {
    continue;
  }
  $groupValue = trim((string) ($groupRollup['group_id'] ?? $groupRollup['name'] ?? ''));
  $groupLabel = trim((string) ($groupRollup['name'] ?? $groupValue));
  if ($groupValue !== '' && $groupLabel !== '') {
    $reportGroupOptions[$groupValue] = $groupLabel;
  }
}
asort($reportGroupOptions, SORT_NATURAL | SORT_FLAG_CASE);

$reportMemberOptions = [];
foreach ($teamEarningsRows as $memberRow) {
  if (!is_array($memberRow)) {
    continue;
  }
  $memberValue = trim((string) ($memberRow['uuid'] ?? $memberRow['name'] ?? ''));
  $memberLabel = trim((string) ($memberRow['name'] ?? $memberValue));
  if ($memberValue !== '' && $memberLabel !== '') {
    $reportMemberOptions[$memberValue] = $memberLabel;
  }
}
asort($reportMemberOptions, SORT_NATURAL | SORT_FLAG_CASE);
?>

<div id="business-workspace" class="business_workspace business_reports" data-business-subpage="reports"<?php echo $workspaceBusinessIdAttr; ?> data-selected-business-id="<?php echo htmlspecialchars($selectedOrgId, ENT_QUOTES, 'UTF-8'); ?>" data-group-reports-year="<?php echo htmlspecialchars((string) $teamEarningsYear, ENT_QUOTES, 'UTF-8'); ?>" data-lens-mode="<?php echo $isLensMode ? '1' : '0'; ?>"<?php echo Lens::workspaceLensDataAttributes('business/reports', $businessReportsLensSnapshot); ?>>

  <h1 class="visually_hidden"><?php echo Strings::i18n('BUSINESS_NAV_REPORTS'); ?></h1>

  <div class="visually_hidden">
    <p id="business_reports_sr_status" role="status" aria-live="polite" aria-atomic="true"><?php echo htmlspecialchars($teamReportsStatusMessage, ENT_QUOTES, 'UTF-8'); ?></p>
  </div>

  <?php if (!$hasOrgMembership): ?>
    <section class="panel business_reports_empty" role="status" aria-live="polite" aria-describedby="business_reports_sr_status">
      <p class="help_text"><?php echo Strings::i18n('BUSINESS_REPORTS_NO_ACCESS'); ?></p>
    </section>
  <?php else: ?>

    <?php if (count($activeOrgs) > 1): ?>
    <form class="earnings_tab_org_form business_reports_org_form" method="get" action="/business/reports/" aria-label="<?php echo htmlspecialchars($i18n['EARNINGS_SELECT_BUSINESS'], ENT_QUOTES, 'UTF-8'); ?>">
      <?php if ($teamEarningsYear !== (int) date('Y')): ?>
      <input type="hidden" name="year" value="<?php echo $teamEarningsYear; ?>">
      <?php endif; ?>
      <label for="earnings_team_org" class="visually_hidden"><?php echo htmlspecialchars($i18n['EARNINGS_SELECT_BUSINESS'], ENT_QUOTES, 'UTF-8'); ?></label>
      <select id="earnings_team_org" name="org" class="earnings_tab_org_select" aria-label="<?php echo htmlspecialchars($i18n['EARNINGS_SELECT_BUSINESS'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php foreach ($activeOrgs as $o): ?>
          <option value="<?php echo htmlspecialchars($o['org_id'], ENT_QUOTES, 'UTF-8'); ?>"
            <?php echo $o['org_id'] === $selectedOrgId ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($o['name'], ENT_QUOTES, 'UTF-8'); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
    <?php endif; ?>

    <section class="business_reports_toolbar" aria-label="<?php echo htmlspecialchars($i18n['BUSINESS_REPORTS_TOOLBAR_ARIA'], ENT_QUOTES, 'UTF-8'); ?>" data-business-reports-toolbar>
      <nav class="business_reports_tabs" role="tablist" aria-label="<?php echo htmlspecialchars($i18n['BUSINESS_REPORTS_SECTIONS_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
        <button type="button" class="business_reports_tab active" role="tab" id="business_reports_tab_overview" aria-controls="business_reports_panel" aria-selected="true" tabindex="0" data-report-tab-button="overview">Overview</button>
        <button type="button" class="business_reports_tab" role="tab" id="business_reports_tab_payroll" aria-controls="business_reports_panel" aria-selected="false" tabindex="-1" data-report-tab-button="payroll">Payroll</button>
        <button type="button" class="business_reports_tab" role="tab" id="business_reports_tab_workforce" aria-controls="business_reports_panel" aria-selected="false" tabindex="-1" data-report-tab-button="workforce">Workforce</button>
        <button type="button" class="business_reports_tab" role="tab" id="business_reports_tab_sites" aria-controls="business_reports_panel" aria-selected="false" tabindex="-1" data-report-tab-button="sites">Sites</button>
        <button type="button" class="business_reports_tab" role="tab" id="business_reports_tab_groups" aria-controls="business_reports_panel" aria-selected="false" tabindex="-1" data-report-tab-button="groups">Groups</button>
        <button type="button" class="business_reports_tab" role="tab" id="business_reports_tab_risks" aria-controls="business_reports_panel" aria-selected="false" tabindex="-1" data-report-tab-button="risks">Risks</button>
      </nav>
      <div class="business_reports_filters" aria-label="<?php echo htmlspecialchars($i18n['BUSINESS_REPORTS_FILTERS_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
        <label>
          <span>Period</span>
          <select data-report-filter="year" aria-label="<?php echo htmlspecialchars($i18n['BUSINESS_REPORTS_FILTER_PERIOD_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php foreach ([(int) date('Y') - 1, (int) date('Y')] as $reportFilterYear): ?>
            <option value="<?php echo $reportFilterYear; ?>"<?php echo $reportFilterYear === $teamEarningsYear ? ' selected' : ''; ?>><?php echo $reportFilterYear; ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          <span>Compare</span>
          <select data-report-filter="compare" aria-label="<?php echo htmlspecialchars($i18n['BUSINESS_REPORTS_FILTER_COMPARE_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
            <option value="">None</option>
            <option value="previous-period">Previous period</option>
            <option value="previous-year">Previous year</option>
          </select>
        </label>
        <label>
          <span>Site</span>
          <select data-report-filter="site" aria-label="<?php echo htmlspecialchars($i18n['BUSINESS_REPORTS_FILTER_SITE_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
            <option value="">All sites</option>
            <?php foreach ($reportSiteOptions as $siteValue => $siteLabel): ?>
            <option value="<?php echo htmlspecialchars($siteValue, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $selectedReportSite === $siteValue ? ' selected' : ''; ?>><?php echo htmlspecialchars($siteLabel, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          <span>Group</span>
          <select data-report-filter="group" aria-label="<?php echo htmlspecialchars($i18n['BUSINESS_REPORTS_FILTER_GROUP_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
            <option value="">All groups</option>
            <?php foreach ($reportGroupOptions as $groupValue => $groupLabel): ?>
            <option value="<?php echo htmlspecialchars($groupValue, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $selectedReportGroup === $groupValue ? ' selected' : ''; ?>><?php echo htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          <span>Member</span>
          <select data-report-filter="member" aria-label="<?php echo htmlspecialchars($i18n['BUSINESS_REPORTS_FILTER_MEMBER_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
            <option value="">All members</option>
            <?php foreach ($reportMemberOptions as $memberValue => $memberLabel): ?>
            <option value="<?php echo htmlspecialchars($memberValue, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $selectedReportMember === $memberValue ? ' selected' : ''; ?>><?php echo htmlspecialchars($memberLabel, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="business_reports_exception_toggle">
          <input type="checkbox" data-report-filter="exceptions">
          <span>Exceptions only</span>
        </label>
        <button type="button" class="btn btn_secondary btn_compact" data-report-customize-open aria-expanded="false" aria-controls="business_reports_customize_drawer">Customize</button>
        <button type="button" class="btn btn_primary btn_compact" data-report-export-open aria-expanded="false" aria-controls="business_reports_export_drawer">Export</button>
      </div>
    </section>

    <aside id="business_reports_customize_drawer" class="business_reports_customize_drawer" data-report-customize-drawer hidden aria-label="<?php echo htmlspecialchars($i18n['BUSINESS_REPORTS_CUSTOMIZE_DRAWER_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
      <div class="business_reports_drawer_header">
        <h2>Customize View</h2>
        <button type="button" class="btn btn_secondary btn_compact" data-report-customize-close>Close</button>
      </div>
      <div class="business_reports_preset_row" role="group" aria-label="<?php echo htmlspecialchars($i18n['BUSINESS_REPORTS_PRESETS_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
        <button type="button" class="btn btn_secondary btn_compact" data-report-preset="executive">Executive</button>
        <button type="button" class="btn btn_secondary btn_compact" data-report-preset="payroll">Payroll</button>
        <button type="button" class="btn btn_secondary btn_compact" data-report-preset="workforce">Workforce</button>
        <button type="button" class="btn btn_secondary btn_compact" data-report-preset="site-manager">Site Manager</button>
      </div>
      <label class="business_reports_named_view">
        <span>Named view</span>
        <input type="text" data-report-view-name maxlength="64" placeholder="My report view">
      </label>
      <div class="business_reports_module_list" data-report-module-list></div>
      <div class="business_reports_drawer_actions">
        <button type="button" class="btn btn_primary btn_compact" data-report-save-view>Save view</button>
        <button type="button" class="btn btn_secondary btn_compact" data-report-reset-view>Reset default</button>
      </div>
    </aside>

    <aside id="business_reports_export_drawer" class="business_reports_export_drawer" data-report-export-drawer hidden aria-labelledby="business_reports_export_heading">
      <div class="business_reports_drawer_header">
        <h2 id="business_reports_export_heading">Export</h2>
        <button type="button" class="btn btn_secondary btn_compact" data-report-export-close>Close</button>
      </div>
      <div class="business_reports_export_actions">
        <button type="button" class="btn btn_secondary btn_compact" data-group-export-format="pdf">PDF</button>
        <button type="button" class="btn btn_secondary btn_compact" data-report-export-csv>CSV</button>
        <button type="button" class="btn btn_secondary btn_compact" data-report-export-zip-focus>ZIP Package</button>
      </div>
    </aside>

    <section class="business_payroll_package_builder" aria-labelledby="business_payroll_package_heading" data-report-export-panel hidden>
      <div class="business_payroll_package_header">
        <div>
          <h2 id="business_payroll_package_heading"><?php echo htmlspecialchars($i18n['BUSINESS_PAYROLL_PACKAGE_HEADING'], ENT_QUOTES, 'UTF-8'); ?></h2>
          <p><?php echo htmlspecialchars($i18n['BUSINESS_PAYROLL_PACKAGE_DESC'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <button type="button" class="btn btn_primary" id="business_payroll_package_generate">
          <?php echo htmlspecialchars($i18n['BUSINESS_PAYROLL_PACKAGE_GENERATE'], ENT_QUOTES, 'UTF-8'); ?>
        </button>
      </div>
      <div class="business_payroll_package_controls" aria-label="<?php echo htmlspecialchars($i18n['BUSINESS_PAYROLL_PACKAGE_OPTIONS_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
        <label for="business_payroll_package_year">
          <span><?php echo htmlspecialchars($i18n['BUSINESS_PAYROLL_PACKAGE_YEAR'], ENT_QUOTES, 'UTF-8'); ?></span>
          <input id="business_payroll_package_year" type="number" inputmode="numeric" min="2000" max="2100" step="1" value="<?php echo htmlspecialchars((string) $teamEarningsYear, ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label for="business_payroll_package_scope">
          <span><?php echo htmlspecialchars($i18n['BUSINESS_PAYROLL_PACKAGE_LABEL'], ENT_QUOTES, 'UTF-8'); ?></span>
          <select id="business_payroll_package_scope">
            <option value="yearly"><?php echo htmlspecialchars($i18n['BUSINESS_PAYROLL_PACKAGE_YEARLY_EVIDENCE'], ENT_QUOTES, 'UTF-8'); ?></option>
          </select>
        </label>
        <p class="business_payroll_package_notice"><?php echo htmlspecialchars($i18n['BUSINESS_PAYROLL_PACKAGE_NOTICE'], ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
      <p id="business_payroll_package_status" class="business_payroll_package_status" role="status" aria-live="polite" aria-atomic="true"></p>
      <div id="business_payroll_package_summary" class="business_payroll_package_summary" hidden>
        <h3><?php echo htmlspecialchars($i18n['BUSINESS_PAYROLL_PACKAGE_GENERATED_HEADING'], ENT_QUOTES, 'UTF-8'); ?></h3>
        <dl>
          <div><dt><?php echo htmlspecialchars($i18n['MEMBERS'], ENT_QUOTES, 'UTF-8'); ?></dt><dd id="business_payroll_package_summary_members">0</dd></div>
          <div><dt><?php echo htmlspecialchars($i18n['BUSINESS_PAYROLL_PACKAGE_YEAR'], ENT_QUOTES, 'UTF-8'); ?></dt><dd id="business_payroll_package_summary_year">-</dd></div>
          <div><dt><?php echo htmlspecialchars($i18n['BUSINESS_PAYROLL_PACKAGE_FILES'], ENT_QUOTES, 'UTF-8'); ?></dt><dd id="business_payroll_package_summary_files">0</dd></div>
          <div><dt><?php echo htmlspecialchars($i18n['BUSINESS_PAYROLL_PACKAGE_COMPLETED'], ENT_QUOTES, 'UTF-8'); ?></dt><dd id="business_payroll_package_summary_completed">0 / 0</dd></div>
          <div><dt><?php echo htmlspecialchars($i18n['BUSINESS_PAYROLL_PACKAGE_EXCEPTIONS'], ENT_QUOTES, 'UTF-8'); ?></dt><dd id="business_payroll_package_summary_exceptions">0</dd></div>
          <div><dt><?php echo htmlspecialchars($i18n['BUSINESS_PAYROLL_PACKAGE_TIME'], ENT_QUOTES, 'UTF-8'); ?></dt><dd id="business_payroll_package_summary_time">0.0 <?php echo htmlspecialchars($i18n['BUSINESS_EXPORT_SECONDS'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
        </dl>
        <div class="business_payroll_package_actions">
          <button type="button" class="btn btn_primary btn_compact" id="business_payroll_package_download_zip" disabled><?php echo htmlspecialchars($i18n['BUSINESS_EXPORT_DOWNLOAD_ZIP'], ENT_QUOTES, 'UTF-8'); ?></button>
          <button type="button" class="btn btn_secondary btn_compact" id="business_payroll_package_download_manifest" disabled><?php echo htmlspecialchars($i18n['BUSINESS_EXPORT_DOWNLOAD_MANIFEST'], ENT_QUOTES, 'UTF-8'); ?></button>
          <a class="btn btn_secondary btn_compact" href="/business/audit/"><?php echo htmlspecialchars($i18n['BUSINESS_EXPORT_VIEW_AUDIT_LOG'], ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
      </div>
    </section>

    <section class="business_reports_panel_shell" id="business_reports_panel" role="tabpanel" aria-labelledby="business_reports_panel_heading" aria-describedby="business_reports_sr_status"<?php if ($teamPanelRenderSuccess) { ?> data-ssr-reports-panel="1"<?php } ?>>
      <h3 id="business_reports_panel_heading" class="visually_hidden"><?php echo Strings::i18n('BUSINESS_REPORTS_ANALYTICS_HEADING'); ?></h3>
      <?php echo $teamPanelHtml; ?>
    </section>

  <?php endif; ?>

</div>

<?php
if (!$teamPanelRenderFailed) {
  require __DIR__ . '/../../reports/_partials/team_earnings_dialog.php';
}
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('earnings') . '">' . PHP_EOL;
echo PHP_EOL . Render::jsScript('team-earnings') . PHP_EOL;
echo PHP_EOL . Render::jsScript('reports-print') . PHP_EOL;
Lens::renderPageConsoleDebug('business/reports', $businessReportsLensSnapshot);
Lens::renderPagePerformanceBoot('business/reports');
require __DIR__ . '/../_partials/footer_shared.php';
require_once \PayCal\Domain\Config\Environment::appHome() . 'html/footer.php';
