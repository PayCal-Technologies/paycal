<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

$currentPage = 'PAGE_BUSINESS_MEMBERS';

require_once dirname(__DIR__) . '/_layout.php';

$isLensMode = InputSanitizer::getString('lens') === '1';

Lens::boot('business-members');
Lens::timeStart('Business Members: SSR grid render');

$membersGridRenderer = new BusinessMembersGridRenderer();
$membersGridBodyHtml = $membersGridRenderer->loadingSkeleton();
$membersGridStatusMessage = businesses_index_i18n('BUSINESSES_LOADING');
$membersGridMemberCount = 0;
$membersGridRenderSuccess = false;
$membersPageMetrics = [
  'members' => 0,
  'managers' => 0,
  'sites' => 0,
  'pending' => 0,
];

if ($workspaceBusinessId !== '') {
  $membersGridResult = $membersGridRenderer->renderForBusiness($userUUID, $workspaceBusinessId, [
    'financial_cache_only' => true,
  ]);
  $membersGridBodyHtml = (string) ($membersGridResult['html'] ?? $membersGridRenderer->loadingSkeleton());
  $membersGridRenderSuccess = (bool) ($membersGridResult['success'] ?? false);
  $membersPageMetrics = is_array($membersGridResult['metrics'] ?? null)
    ? $membersGridResult['metrics']
    : $membersPageMetrics;
  if ($membersGridRenderSuccess) {
    $membersGridMemberCount = (int) ($membersGridResult['member_count'] ?? 0);
    $membersGridStatusMessage = sprintf(
      businesses_index_i18n('BUSINESSES_MEMBERS_GRID_LOADED'),
      $membersGridMemberCount,
      $membersGridMemberCount === 1 ? '' : 's',
    );
  } else {
    $membersGridStatusMessage = (string) ($membersGridResult['message'] ?? businesses_index_i18n('BUSINESSES_FAILED_LOAD_MEMBERS_GRID'));
  }
} else {
  $membersGridBodyHtml = $membersGridRenderer->emptyMessage(businesses_index_i18n('BUSINESSES_SELECT_FIRST'));
  $membersGridStatusMessage = businesses_index_i18n('BUSINESSES_SELECT_FIRST');
}

Lens::timeEnd('Business Members: SSR grid render');

$businessMembersLensSnapshot = [
  'is_lens_mode' => $isLensMode,
  'workspace_business_id' => $workspaceBusinessId,
  'member_count' => $membersGridMemberCount,
  'ssr_grid_render_success' => $membersGridRenderSuccess,
  'ssr_status_message' => $membersGridStatusMessage,
  'ssr_grid_html_length' => strlen($membersGridBodyHtml),
];

Lens::add('Business Members: page snapshot', $businessMembersLensSnapshot);

if ($isLensMode) {
  Lens::add('Business Members: SSR grid body length', strlen($membersGridBodyHtml));
}

$memberReportCatalog = BusinessMemberReportCatalog::reports();
$memberReportFormats = BusinessMemberReportCatalog::formats();
$memberReportDefaultYear = (int) date('Y');
$memberReportDefaultDescription = isset($memberReportCatalog[0]['description']) && is_scalar($memberReportCatalog[0]['description'])
  ? (string) $memberReportCatalog[0]['description']
  : '';
$memberReportI18nKeys = [
  'REPORTS',
  'BUSINESS_MEMBERS_REPORT_DIALOG_TITLE',
  'BUSINESS_MEMBERS_REPORT_OPTIONS_ARIA',
  'BUSINESS_MEMBERS_REPORT_LABEL',
  'BUSINESS_MEMBERS_REPORT_SETTINGS_HEADING',
  'BUSINESS_MEMBERS_REPORT_SELECTED_HEADING',
  'BUSINESS_MEMBERS_REPORT_ADD_MEMBERS',
  'BUSINESS_MEMBERS_REPORT_SELECTED_FILTER_PLACEHOLDER',
  'BUSINESS_MEMBERS_REPORT_ADD_PLACEHOLDER',
  'BUSINESS_MEMBERS_REPORT_FORMAT',
  'BUSINESS_MEMBERS_REPORT_DELIVERY',
  'BUSINESS_EXPORT_DOWNLOAD_ZIP',
  'BUSINESS_MEMBERS_REPORT_DOWNLOAD_FILES',
  'BUSINESS_PAYROLL_PACKAGE_YEAR',
  'BUSINESS_MEMBERS_REPORT_PRIVACY_NOTICE',
  'BUSINESS_MEMBERS_REPORT_GENERATE_SELECTED',
  'BUSINESS_MEMBERS_REPORTS_GENERATED_HEADING',
  'MEMBERS',
  'BUSINESS_MEMBERS_REPORT_COMPLETED',
  'BUSINESS_PAYROLL_PACKAGE_TIME',
  'BUSINESS_EXPORT_SECONDS',
  'BUSINESS_EXPORT_DOWNLOAD_MANIFEST',
  'BUSINESS_EXPORT_GENERATE_ANOTHER',
  'BUSINESS_EXPORT_VIEW_AUDIT_LOG',
];
$memberReportI18n = [];
foreach ($memberReportI18nKeys as $memberReportI18nKey) {
  $memberReportI18n[$memberReportI18nKey] = Strings::i18n($memberReportI18nKey);
}
$membersPendingCount = max(0, (int) ($membersPageMetrics['pending'] ?? 0));
?>

<div id="business-workspace" class="business_workspace business_members" data-business-subpage="members"<?php echo $workspaceBusinessIdAttr; ?> data-lens-mode="<?php echo $isLensMode ? '1' : '0'; ?>"<?php echo Lens::workspaceLensDataAttributes('business/members', $businessMembersLensSnapshot, ['fetch_url_pattern' => '/members/grid']); ?>>

  <h1 class="visually_hidden"><?php echo Strings::i18n('BUSINESS_NAV_MEMBERS'); ?></h1>

  <section class="business_members_grid_shell" aria-labelledby="business_members_grid_heading">
    <h3 id="business_members_grid_heading" class="visually_hidden"><?php echo businesses_index_i18n('BUSINESSES_MEMBERS_H3'); ?></h3>
    <div class="business_members_metrics_bar" role="status" aria-live="polite" aria-label="<?php echo businesses_index_i18n('BUSINESSES_MEMBERS_METRICS_ARIA'); ?>">
      <span class="business_members_metric_chip">
        <span class="business_members_metric_label"><?php echo businesses_index_i18n('BUSINESSES_MEMBERS_METRIC_MEMBERS'); ?></span>
        <span class="business_members_metric_value" id="business_members_metric_members"><?php echo htmlspecialchars((string) ($membersPageMetrics['members'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></span>
      </span>
      <span class="business_members_metric_divider" aria-hidden="true">|</span>
      <span class="business_members_metric_chip">
        <span class="business_members_metric_label"><?php echo businesses_index_i18n('BUSINESSES_MEMBERS_METRIC_MANAGERS'); ?></span>
        <span class="business_members_metric_value" id="business_members_metric_managers"><?php echo htmlspecialchars((string) ($membersPageMetrics['managers'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></span>
      </span>
      <span class="business_members_metric_divider" aria-hidden="true">|</span>
      <span class="business_members_metric_chip">
        <span class="business_members_metric_label"><?php echo businesses_index_i18n('BUSINESSES_MEMBERS_METRIC_SITES'); ?></span>
        <span class="business_members_metric_value" id="business_members_metric_sites"><?php echo htmlspecialchars((string) ($membersPageMetrics['sites'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></span>
      </span>
      <span class="business_members_metric_divider" aria-hidden="true">|</span>
      <span class="business_members_security_legend" title="<?php echo htmlspecialchars(Strings::i18n('BUSINESSES_MEMBERS_SECURITY_SETUP_HELP'), ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo BusinessMembersGridRenderer::shieldWrenchIconMarkup(Strings::i18n('BUSINESSES_MEMBERS_SECURITY_SETUP_HELP'), 'business_member_data_access_icon is-setup business_members_security_legend_icon', true); ?>
        <span class="business_members_security_legend_text">
          <span class="business_members_security_legend_label"><?php echo htmlspecialchars(Strings::i18n('SETTINGS_DATA_CONSENT_SETUP_PILL'), ENT_QUOTES, 'UTF-8'); ?></span>
          <span class="business_members_security_legend_help"><?php echo htmlspecialchars(Strings::i18n('BUSINESSES_MEMBERS_SECURITY_SETUP_HELP'), ENT_QUOTES, 'UTF-8'); ?></span>
        </span>
      </span>
    </div>
    <div id="business_members_bulk_toolbar_mount" class="business_members_bulk_toolbar_mount">
    <div
      id="business_members_bulk_toolbar"
      class="business_members_bulk_toolbar business_members_bulk_toolbar_compact hidden"
      aria-label="<?php echo businesses_index_i18n('BUSINESSES_MEMBERS_BULK_TOOLBAR_ARIA'); ?>"
    >
      <span id="business_members_selection_count" class="business_members_selection_badge" role="status" aria-live="polite">
        <span class="business_members_selection_badge_icon" aria-hidden="true">&#10003;</span>
        <span class="business_members_selection_badge_count" id="business_members_selection_badge_count">0</span>
        <span class="business_members_selection_badge_label"><?php echo businesses_index_i18n('BUSINESSES_MEMBERS_SELECTION_BADGE_LABEL'); ?></span>
      </span>
      <button type="button" class="btn btn_secondary btn_compact" id="business_members_clear_selection">
        <?php echo businesses_index_i18n('BUSINESSES_MEMBERS_CLEAR_SELECTION'); ?>
      </button>
      <div class="business_members_bulk_group_control" id="business_members_bulk_group_control">
        <button
          type="button"
          class="btn btn_secondary btn_compact business_members_bulk_group_toggle"
          id="business_members_bulk_group_toggle"
          aria-haspopup="menu"
          aria-expanded="false"
          aria-controls="business_members_bulk_group_menu"
        >
          <?php echo htmlspecialchars(Strings::i18n('BUSINESS_GROUPS_ADD_TO_SHORT'), ENT_QUOTES, 'UTF-8'); ?>
        </button>
        <div
          id="business_members_bulk_group_menu"
          class="business_member_row_submenu business_members_bulk_group_menu"
          role="menu"
          hidden
        ></div>
      </div>
      <div class="business_members_report_control" id="business_members_report_control">
        <button
          type="button"
          class="btn btn_secondary btn_compact business_members_report_toggle"
          id="business_members_report_toggle"
          aria-haspopup="dialog"
          aria-expanded="false"
          aria-controls="business_members_report_panel"
        >
          <?php echo htmlspecialchars($memberReportI18n['REPORTS'], ENT_QUOTES, 'UTF-8'); ?>
        </button>
        <dialog
          id="business_members_report_panel"
          class="business_members_report_panel"
          aria-modal="true"
          aria-label="<?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORT_OPTIONS_ARIA'], ENT_QUOTES, 'UTF-8'); ?>"
        >
          <div class="business_members_report_dialog_header">
            <h4><?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORT_DIALOG_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h4>
            <button type="button" class="btn_close business_members_report_close" id="business_members_report_close" aria-label="<?php echo businesses_index_i18n('CLOSE'); ?>">&times;</button>
          </div>
          <div class="business_members_report_selected">
            <h5 class="business_members_report_section_title">
              <span><?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORT_SELECTED_HEADING'], ENT_QUOTES, 'UTF-8'); ?></span>
              <span id="business_members_report_selected_count" class="business_members_report_section_count">0</span>
            </h5>
            <input id="business_members_report_member_filter" type="search" placeholder="<?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORT_SELECTED_FILTER_PLACEHOLDER'], ENT_QUOTES, 'UTF-8'); ?>">
            <div id="business_members_report_member_pills" class="business_members_report_member_pills" role="list" aria-label="Selected members for report generation"></div>
            <p id="business_members_report_member_empty" class="business_members_report_member_empty" hidden>No selected members match this filter.</p>
            <label for="business_members_report_member_add"><?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORT_ADD_MEMBERS'], ENT_QUOTES, 'UTF-8'); ?></label>
            <input id="business_members_report_member_add" type="search" placeholder="<?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORT_ADD_PLACEHOLDER'], ENT_QUOTES, 'UTF-8'); ?>">
            <div id="business_members_report_member_add_results" class="business_members_report_member_add_results" role="list" aria-label="Members available to add"></div>
          </div>
          <div class="business_members_report_settings">
            <h5 class="business_members_report_section_title"><?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORT_SETTINGS_HEADING'], ENT_QUOTES, 'UTF-8'); ?></h5>
            <div class="business_members_report_field">
              <label for="business_members_report_type"><?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORT_LABEL'], ENT_QUOTES, 'UTF-8'); ?></label>
              <select id="business_members_report_type">
                <?php foreach ($memberReportCatalog as $reportOption) { ?>
                  <option
                    value="<?php echo htmlspecialchars($reportOption['key'], ENT_QUOTES, 'UTF-8'); ?>"
                    data-report-scope="<?php echo htmlspecialchars($reportOption['scope'], ENT_QUOTES, 'UTF-8'); ?>"
                    data-report-description="<?php echo htmlspecialchars($reportOption['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                  >
                    <?php echo htmlspecialchars($reportOption['label'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php } ?>
              </select>
              <p id="business_members_report_description" class="business_members_report_field_help"><?php echo htmlspecialchars($memberReportDefaultDescription, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="business_members_report_field">
              <label for="business_members_report_year"><?php echo htmlspecialchars($memberReportI18n['BUSINESS_PAYROLL_PACKAGE_YEAR'], ENT_QUOTES, 'UTF-8'); ?></label>
              <input id="business_members_report_year" type="number" inputmode="numeric" min="2000" max="2100" step="1" value="<?php echo htmlspecialchars((string) $memberReportDefaultYear, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="business_members_report_field">
              <label for="business_members_report_format"><?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORT_FORMAT'], ENT_QUOTES, 'UTF-8'); ?></label>
              <select id="business_members_report_format">
                <?php foreach ($memberReportFormats as $formatOption) { ?>
                  <option value="<?php echo htmlspecialchars($formatOption['key'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($formatOption['label'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php } ?>
              </select>
            </div>
            <div class="business_members_report_field">
              <label for="business_members_report_delivery"><?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORT_DELIVERY'], ENT_QUOTES, 'UTF-8'); ?></label>
              <select id="business_members_report_delivery">
                <option value="zip"><?php echo htmlspecialchars($memberReportI18n['BUSINESS_EXPORT_DOWNLOAD_ZIP'], ENT_QUOTES, 'UTF-8'); ?></option>
                <option value="files"><?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORT_DOWNLOAD_FILES'], ENT_QUOTES, 'UTF-8'); ?></option>
              </select>
            </div>
            <p class="business_members_report_privacy_notice"><?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORT_PRIVACY_NOTICE'], ENT_QUOTES, 'UTF-8'); ?></p>
            <button type="button" class="btn btn_primary btn_compact" id="business_members_generate_reports" disabled>
              <?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORT_GENERATE_SELECTED'], ENT_QUOTES, 'UTF-8'); ?>
            </button>
            <p id="business_members_report_status" class="business_members_report_status" role="status" aria-live="polite" aria-atomic="true"></p>
            <div id="business_members_report_summary" class="business_members_report_summary" hidden>
              <h4><?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORTS_GENERATED_HEADING'], ENT_QUOTES, 'UTF-8'); ?></h4>
              <dl>
                <div><dt><?php echo htmlspecialchars($memberReportI18n['MEMBERS'], ENT_QUOTES, 'UTF-8'); ?></dt><dd id="business_members_report_summary_members">0</dd></div>
                <div><dt><?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORT_LABEL'], ENT_QUOTES, 'UTF-8'); ?></dt><dd id="business_members_report_summary_report">-</dd></div>
                <div><dt><?php echo htmlspecialchars($memberReportI18n['BUSINESS_PAYROLL_PACKAGE_YEAR'], ENT_QUOTES, 'UTF-8'); ?></dt><dd id="business_members_report_summary_year">-</dd></div>
                <div><dt><?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORT_FORMAT'], ENT_QUOTES, 'UTF-8'); ?></dt><dd id="business_members_report_summary_format">-</dd></div>
                <div><dt><?php echo htmlspecialchars($memberReportI18n['BUSINESS_MEMBERS_REPORT_COMPLETED'], ENT_QUOTES, 'UTF-8'); ?></dt><dd id="business_members_report_summary_completed">0 / 0</dd></div>
                <div><dt><?php echo htmlspecialchars($memberReportI18n['BUSINESS_PAYROLL_PACKAGE_TIME'], ENT_QUOTES, 'UTF-8'); ?></dt><dd id="business_members_report_summary_time">0.0 <?php echo htmlspecialchars($memberReportI18n['BUSINESS_EXPORT_SECONDS'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
              </dl>
              <div class="business_members_report_summary_actions">
                <button type="button" class="btn btn_primary btn_compact" id="business_members_report_download_zip" disabled><?php echo htmlspecialchars($memberReportI18n['BUSINESS_EXPORT_DOWNLOAD_ZIP'], ENT_QUOTES, 'UTF-8'); ?></button>
                <button type="button" class="btn btn_secondary btn_compact" id="business_members_report_download_manifest" disabled><?php echo htmlspecialchars($memberReportI18n['BUSINESS_EXPORT_DOWNLOAD_MANIFEST'], ENT_QUOTES, 'UTF-8'); ?></button>
                <button type="button" class="btn btn_secondary btn_compact" id="business_members_report_generate_another"><?php echo htmlspecialchars($memberReportI18n['BUSINESS_EXPORT_GENERATE_ANOTHER'], ENT_QUOTES, 'UTF-8'); ?></button>
                <a class="btn btn_secondary btn_compact" href="/business/audit/"><?php echo htmlspecialchars($memberReportI18n['BUSINESS_EXPORT_VIEW_AUDIT_LOG'], ENT_QUOTES, 'UTF-8'); ?></a>
              </div>
            </div>
          </div>
        </dialog>
      </div>
      <p id="business_members_bulk_status" class="visually_hidden" role="status" aria-live="polite" aria-atomic="true"></p>
    </div>
    </div>
    <?php
    $membersPendingSummary = sprintf(
      businesses_index_i18n('BUSINESSES_MEMBERS_PENDING_SUMMARY'),
      $membersPendingCount,
    );
    ?>
    <details
      id="business_members_pending_details"
      class="business_members_pending_details"
      <?php if ($membersPendingCount <= 0) { ?>hidden<?php } ?>
    >
      <summary class="business_members_pending_summary">
        <span id="business_members_pending_summary_label"><?php echo htmlspecialchars($membersPendingSummary, ENT_QUOTES, 'UTF-8'); ?></span>
      </summary>
      <div
        id="business_members_pending_list"
        class="business_members_pending_list"
        role="list"
        aria-label="<?php echo businesses_index_i18n('BUSINESSES_MEMBERS_PENDING_ARIA'); ?>"
        aria-describedby="business_members_pending_sr_status"
      ></div>
      <p id="business_members_pending_sr_status" class="visually_hidden" role="status" aria-live="polite" aria-atomic="true"></p>
    </details>
    <div class="visually_hidden">
      <p id="businesses_members_grid_sr_instructions"><?php echo businesses_index_i18n('BUSINESSES_MEMBERS_GRID_SR'); ?></p>
      <p id="businesses_members_grid_sr_context"><?php echo businesses_index_i18n('BUSINESSES_MEMBERS_GRID_CONTEXT'); ?></p>
      <p id="businesses_members_grid_sr_status" role="status" aria-live="polite" aria-atomic="true"><?php echo htmlspecialchars($membersGridStatusMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div
      id="businesses-members-grid"
      class="datagrid_container business_members_datagrid"
      role="region"
      aria-label="<?php echo businesses_index_i18n('BUSINESSES_MEMBERS_GRID_ARIA'); ?>"
      aria-describedby="businesses_members_grid_sr_instructions businesses_members_grid_sr_context businesses_members_grid_sr_status"
      <?php if ($membersGridRenderSuccess) { ?>data-ssr-members-grid="1"<?php } ?>
    >
      <div class="datagrid_body"><?php echo $membersGridBodyHtml; ?></div>
    </div>
  </section>

  <dialog
    id="business_members_info_dialog"
    class="dialog business_members_info_dialog"
    aria-modal="true"
    aria-labelledby="business_members_info_dialog_title"
    aria-describedby="business_members_info_dialog_body"
    data-dialog-close-on-backdrop="true"
  >
    <section class="modal_header">
      <h2 id="business_members_info_dialog_title" class="modal_title">Members Guide</h2>
      <button type="button" class="btn_close" data-dialog-close="business_members_info_dialog" aria-label="<?php echo businesses_index_i18n('CLOSE'); ?>">&times;</button>
    </section>
    <section id="business_members_info_dialog_body" class="modal_content business_members_info_dialog_content">
      <div class="business_members_info_dialog_grid">
        <article class="business_members_info_panel">
          <h3>Workspace Controls</h3>
          <dl class="business_members_info_terms">
            <div>
              <dt>Members</dt>
              <dd>People connected to this workspace.</dd>
            </div>
            <div>
              <dt>Managers</dt>
              <dd>Owners and coordinators who manage access.</dd>
            </div>
            <div>
              <dt>Sites</dt>
              <dd>Work sites linked to this workspace.</dd>
            </div>
            <div>
              <dt>Pending</dt>
              <dd>Invites and access requests needing review.</dd>
            </div>
            <div>
              <dt>Selected</dt>
              <dd>Checked rows available for bulk actions.</dd>
            </div>
          </dl>
        </article>
        <article class="business_members_info_panel">
          <h3>Roles</h3>
          <div class="business_members_role_cards">
            <article class="business_members_role_card">
              <header>
                <h4>Owner</h4>
                <span class="business_members_role_badge">Full access</span>
              </header>
              <p>Controls members, sites, wages, reports, audit history, and ownership.</p>
            </article>
            <article class="business_members_role_card">
              <header>
                <h4>Coordinator</h4>
                <span class="business_members_role_badge">Admin</span>
              </header>
              <p>Manages access, approvals, roles, sites, wages, reports, and audit history.</p>
              <p class="business_members_role_limit">Cannot transfer ownership.</p>
            </article>
            <article class="business_members_role_card">
              <header>
                <h4>Contributor</h4>
                <span class="business_members_role_badge">Can edit</span>
              </header>
              <p>Works with assigned site/work records and reports.</p>
              <p class="business_members_role_limit">Cannot manage access or settings.</p>
            </article>
            <article class="business_members_role_card">
              <header>
                <h4>Member</h4>
                <span class="business_members_role_badge">Own records</span>
              </header>
              <p>Manages their own work entries and shared context for those records.</p>
              <p class="business_members_role_limit">Cannot view org-wide member work.</p>
            </article>
            <article class="business_members_role_card">
              <header>
                <h4>Viewer</h4>
                <span class="business_members_role_badge">Read only</span>
              </header>
              <p>Views shared pay periods, sites, wages, and basic work data.</p>
              <p class="business_members_role_limit">Cannot edit or manage anything.</p>
            </article>
          </div>
          <div class="business_members_role_matrix" role="table" aria-label="Role permission matrix">
            <div class="business_members_role_matrix_header" role="row">
              <span role="columnheader">Role</span>
              <span role="columnheader">Access</span>
              <span role="columnheader">Reports</span>
              <span role="columnheader">Access mgmt</span>
            </div>
            <div role="row">
              <span role="cell">Owner</span>
              <span role="cell">Full</span>
              <span role="cell">Yes</span>
              <span role="cell">Yes</span>
            </div>
            <div role="row">
              <span role="cell">Coordinator</span>
              <span role="cell">Admin</span>
              <span role="cell">Yes</span>
              <span role="cell">Yes</span>
            </div>
            <div role="row">
              <span role="cell">Contributor</span>
              <span role="cell">Edit</span>
              <span role="cell">Yes</span>
              <span role="cell">No</span>
            </div>
            <div role="row">
              <span role="cell">Member</span>
              <span role="cell">Self</span>
              <span role="cell">No</span>
              <span role="cell">No</span>
            </div>
            <div role="row">
              <span role="cell">Viewer</span>
              <span role="cell">Read</span>
              <span role="cell">No</span>
              <span role="cell">No</span>
            </div>
          </div>
        </article>
      </div>
    </section>
    <section class="modal_footer">
      <button type="button" class="btn btn_secondary" data-dialog-close="business_members_info_dialog"><?php echo businesses_index_i18n('CLOSE'); ?></button>
    </section>
  </dialog>

</div>

<?php
require __DIR__ . '/../_partials/members_dialogs.php';
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('earnings') . '">' . PHP_EOL;
Lens::renderPageConsoleDebug('business/members', $businessMembersLensSnapshot);
Lens::renderPagePerformanceBoot('business/members', [
  'fetch_url_pattern' => '/members/grid',
]);
require __DIR__ . '/../_partials/footer_shared.php';
require_once \PayCal\Domain\Config\Environment::appHome() . 'html/footer.php';
