<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
#[Group('private-moat')]
final class BusinessesAndEarningsA11yContractTest extends TestCase
{
  #[Test]
  public function businessesDashboardIsReadOnlyExecutiveHome(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $dashboard = (string) file_get_contents($projectRoot . '/html/business/index.php');
    $businessesJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $dashboardJs = (string) file_get_contents($projectRoot . '/html/js/business/subpages/dashboard.js.php');
    $contextHeaderJs = (string) file_get_contents($projectRoot . '/html/js/business/core/context-header.js.php');
    $datagridReloadJs = (string) file_get_contents($projectRoot . '/html/js/business/core/datagrid-reload-handlers.js.php');
    $businessRouterJs = (string) file_get_contents($projectRoot . '/html/js/business/index.php');
    $contextHeaderPhp = (string) file_get_contents($projectRoot . '/html/business/_context_header.php');
    $businessCss = (string) file_get_contents($projectRoot . '/html/css/business/index.php');
    $responsiveCss = (string) file_get_contents($projectRoot . '/html/css/responsive/index.php');
    $navigationCss = (string) file_get_contents($projectRoot . '/html/css/navigation/index.php');
    $commonCss = (string) file_get_contents($projectRoot . '/html/css/common/index.php');

    $this->assertStringNotContainsString('business_page_shell business_dashboard_intro', $dashboard);
    $this->assertStringNotContainsString('business_page_title', $dashboard);
    $this->assertStringNotContainsString('BUSINESS_DASHBOARD_TITLE', $dashboard);
    $this->assertStringNotContainsString('BUSINESS_DASHBOARD_HELP', $dashboard);
    $this->assertStringContainsString('class="visually_hidden"', $dashboard);
    $this->assertStringContainsString('BUSINESS_NAV_DASHBOARD', $dashboard);
    $dashboardMetricsPanel = (string) file_get_contents($projectRoot . '/html/business/_partials/dashboard_metrics_panel.php');
    $this->assertStringContainsString('_partials/dashboard_metrics_panel.php', $dashboard);
    $this->assertStringContainsString('namespace PayCal\\Domain;', $dashboardMetricsPanel);
    $this->assertStringContainsString('business_dashboard_metrics', $dashboardMetricsPanel);
    $this->assertStringContainsString('business_dashboard_metrics_grid', $dashboardMetricsPanel);
    $this->assertStringContainsString('BUSINESS_DASHBOARD_METRIC_MEMBERS', $dashboardMetricsPanel);
    $this->assertStringContainsString('BUSINESS_DASHBOARD_METRIC_SITES', $dashboardMetricsPanel);
    $this->assertStringContainsString('BUSINESS_DASHBOARD_METRIC_PENDING_INVITES', $dashboardMetricsPanel);
    $this->assertStringContainsString('BUSINESS_DASHBOARD_METRIC_PENDING_REQUESTS', $dashboardMetricsPanel);
    $this->assertStringContainsString('BUSINESS_DASHBOARD_METRIC_WORK_TODAY', $dashboardMetricsPanel);
    $this->assertStringContainsString('BUSINESS_DASHBOARD_METRIC_WORK_WEEK', $dashboardMetricsPanel);
    $this->assertStringContainsString('BUSINESS_DASHBOARD_METRIC_LAST_ACTIVITY', $dashboardMetricsPanel);
    $this->assertStringContainsString('BUSINESS_DASHBOARD_METRIC_CREATED', $dashboardMetricsPanel);
    $this->assertStringContainsString('BusinessDashboardMetrics::forBusiness', $dashboard);
    $this->assertStringNotContainsString('businesses_executive_summary', $dashboard);
    $this->assertStringNotContainsString('id="businesses_exec_summary_heading"', $dashboard);
    $this->assertStringNotContainsString('business_dashboard_quick_actions', $dashboard);
    $this->assertStringNotContainsString('businesses-dashboard-audit-grid-host', $dashboard);
    $this->assertStringNotContainsString('business_dashboard_audit_snippet', $dashboard);
    $this->assertStringContainsString('id="business-workspace"', $dashboard);
    $this->assertStringContainsString('data-business-subpage="dashboard"', $dashboard);
    $this->assertStringContainsString("'/business/members/'", $dashboardMetricsPanel);
    $this->assertStringContainsString("'/business/sites/'", $dashboardMetricsPanel);
    $this->assertStringContainsString("'/business/details/'", $dashboardMetricsPanel);
    $this->assertStringContainsString("'/business/audit/'", $dashboardMetricsPanel);
    $this->assertStringContainsString("'/calendar/'", $dashboardMetricsPanel);
    $this->assertStringContainsString("'/business/reports/'", $dashboardMetricsPanel);

    $this->assertStringNotContainsString('id="businesses_control_center_name"', $dashboard);
    $this->assertStringNotContainsString('id="businesses_editor_name"', $dashboard);
    $this->assertStringNotContainsString('id="businesses_editor_inline_mount"', $dashboard);
    $this->assertStringNotContainsString('businesses_people_access_panel', $dashboard);
    $this->assertStringNotContainsString('businesses_governance_panel', $dashboard);
    $this->assertStringNotContainsString('id="businesses-hub"', $dashboard);
    $this->assertStringNotContainsString('id="businesses_editor_dialog"', $dashboard);
    $this->assertStringNotContainsString('id="businesses_live_requests_list"', $dashboard);
    $this->assertStringNotContainsString('id="businesses-members-grid"', $dashboard);
    $this->assertStringNotContainsString('id="businesses_definitions_help_button"', $dashboard);

    $this->assertStringContainsString('updateBusinessContextHeader', $businessesJs);
    $this->assertStringNotContainsString('loadDashboardAuditSnippet', $businessesJs);
    $this->assertStringContainsString('warmBusinessWorkspaceCache', $dashboardJs);
    $this->assertStringContainsString('cache/warm', $businessesJs);
    $this->assertStringContainsString('refreshDashboardWorkspace', $dashboardJs);
    $this->assertStringContainsString("window.matchMedia?.('(max-width: 768px)')?.matches === true", $dashboardJs);
    $this->assertStringContainsString('font-variant-numeric: tabular-nums;', $businessCss);
    $this->assertStringNotContainsString('businessWorkspaceSlideIn', $businessCss);
    $this->assertStringNotContainsString('body[data-nav-viewport-compact]:has(#page_header.nav_component--header:not(.nav_component--public)) #main:has(#business-workspace)', $responsiveCss);
    $this->assertStringNotContainsString('body[data-nav-viewport-compact]:not(.calendar-screenmode-minimal):has(#page_header.nav_component--header:not(.nav_component--public)) #main:has(#business-workspace)', $navigationCss);
    $this->assertStringContainsString('body[data-nav-viewport-compact][data-nav-primary-position=\'left\'],', $navigationCss);
    $this->assertStringContainsString('padding-top: 0;', $navigationCss);
    $this->assertMatchesRegularExpression('/body\[data-nav-viewport-compact\]\s+\.mobile_navigation_bar\s*\{[^}]*position:\s*relative;/s', $navigationCss);
    $this->assertMatchesRegularExpression('/body\[data-nav-primary-position=\'left\'\]:has\(#page_header\.nav_component--header:not\(\.nav_component--public\)\)\s+\.sidebar_toggle_accessible,[\s\S]*?position:\s*absolute;/s', $navigationCss);
    $this->assertStringNotContainsString('body[data-nav-viewport-compact].nav-pinned .sidebar_toggle_accessible {' . "\n" . '    position: fixed;', $navigationCss);
    $this->assertMatchesRegularExpression('/\.public_beta_echo_banner\s*\{[^}]*position:\s*relative;/s', $commonCss);
    $this->assertStringNotContainsString('z-index: 9900;', $commonCss);
    $this->assertStringContainsString('syncBusinessWorkspaceElementRefs', $contextHeaderJs);
    $this->assertStringNotContainsString('business_context_metrics', $contextHeaderPhp);
    $this->assertStringContainsString('$workspaceBusinessIdAttr', $dashboard);
    $this->assertStringContainsString('resolveWorkspaceBusinessId', $businessesJs);
    $this->assertStringContainsString('dataset.businessId', $businessesJs);
    $this->assertStringContainsString("resolveBusinessSubPage() === 'dashboard'", $businessesJs);
    $this->assertStringContainsString('refreshDashboardWorkspace', $businessesJs);
    $this->assertStringNotContainsString('fetchLiveRequestsSnapshot', $dashboardJs);
    $this->assertStringNotContainsString('updateExecutiveSummary', $dashboardJs);
    $this->assertStringContainsString('initializeDatagridReloadHandlers', $datagridReloadJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/core/datagrid-reload-handlers.js.php'", $businessRouterJs);
    $this->assertStringNotContainsString('loadBusinessContextHeaderMetrics', $contextHeaderJs);
    $this->assertStringNotContainsString('initializeInlineEditorMount', $businessesJs);
    $this->assertStringNotContainsString('initializeTabNavigation', $businessesJs);
    $this->assertStringContainsString('initialize().catch', $businessRouterJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/subpages/dashboard.js.php'", $businessRouterJs);
    $this->assertStringContainsString('resolveBusinessSubPage', $businessesJs);
    $this->assertStringContainsString('openDetailsPage', $businessesJs);
    $this->assertStringContainsString('openMembersPage', $businessesJs);
    $this->assertStringContainsString('openSitesPage', $businessesJs);
    $this->assertStringContainsString('openPayrollPage', $businessesJs);
    $this->assertStringContainsString('openAuditPage', $businessesJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/subpages/audit.js.php'", $businessRouterJs);
  }

  #[Test]
  public function businessContextHeaderMergesNameAndSubnavOnOneRow(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $contextHeaderPhp = (string) file_get_contents($projectRoot . '/html/business/_context_header.php');
    $contextHeaderJs = (string) file_get_contents($projectRoot . '/html/js/business/core/context-header.js.php');
    $businessCss = (string) file_get_contents($projectRoot . '/html/css/business/index.php');

    $this->assertStringContainsString('class="business_context_header"', $contextHeaderPhp);
    $this->assertStringContainsString('id="business_context_name" class="business_context_name"', $contextHeaderPhp);
    $this->assertStringContainsString('class="business_context_separator" aria-hidden="true"', $contextHeaderPhp);
    $this->assertStringContainsString('class="business_subnav" aria-label', $contextHeaderPhp);
    $this->assertStringContainsString('class="business_subnav_tabs"', $contextHeaderPhp);
    $this->assertStringNotContainsString('business_context_identity', $contextHeaderPhp);
    $this->assertStringNotContainsString('business_context_role', $contextHeaderPhp);
    $this->assertStringNotContainsString('business_context_role', $contextHeaderJs);
    $this->assertStringNotContainsString('contextHeaderRole', $contextHeaderJs);
    $this->assertMatchesRegularExpression(
      '/<header class="business_context_header"[\s\S]*<h2 id="business_context_name"[\s\S]*<span class="business_context_separator"[\s\S]*<nav class="business_subnav"/s',
      $contextHeaderPhp,
    );
    $this->assertMatchesRegularExpression(
      '/\.business_context_header\s*\{[^}]*display:\s*flex;/s',
      $businessCss,
    );
    $this->assertMatchesRegularExpression(
      '/\.business_context_separator\s*\{[^}]*width:\s*1px;/s',
      $businessCss,
    );
  }

  #[Test]
  public function detailsPageIsSingleEditableBusinessIdentityHome(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $detailsPage = (string) file_get_contents($projectRoot . '/html/business/details/index.php');
    $payrollPage = (string) file_get_contents($projectRoot . '/html/business/payroll/index.php');
    $businessPanel = (string) file_get_contents($projectRoot . '/html/business/_partials/business_details_panel.php');
    $businessCss = (string) file_get_contents($projectRoot . '/html/css/business/index.php');
    $membersPage = (string) file_get_contents($projectRoot . '/html/business/members/index.php');
    $editorDialog = (string) file_get_contents($projectRoot . '/html/business/_archive/partials/editor_dialog.php');
    $businessesJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $contactCardsJs = (string) file_get_contents($projectRoot . '/html/js/business/core/contact-cards.js.php');
    $businessRouterJs = (string) file_get_contents($projectRoot . '/html/js/business/index.php');
    $profileRouterJs = (string) file_get_contents($projectRoot . '/html/js/business-profile/index.php');
    $detailsJs = (string) file_get_contents($projectRoot . '/html/js/business/subpages/details.js.php');

    $this->assertStringContainsString('_partials/business_details_panel.php', $detailsPage);
    $this->assertStringContainsString('data-business-subpage="details"', $detailsPage);
    $this->assertStringContainsString("Lens::boot('business-details')", $detailsPage);
    $this->assertStringContainsString("Lens::workspaceLensDataAttributes('business/details'", $detailsPage);
    $this->assertStringContainsString("Lens::renderPageConsoleDebug('business/details'", $detailsPage);
    $this->assertStringContainsString("Lens::renderPagePerformanceBoot('business/details'", $detailsPage);
    $this->assertStringContainsString('<?php echo $workspaceBusinessIdAttr; ?>', $detailsPage);
    $this->assertStringContainsString('value="<?php echo htmlspecialchars($workspaceBusinessId, ENT_QUOTES, \'UTF-8\'); ?>"', $detailsPage);
    $this->assertStringContainsString('data-business-subpage="payroll"', $payrollPage);
    $this->assertStringContainsString('<?php echo $workspaceBusinessIdAttr; ?>', $payrollPage);
    $this->assertStringContainsString('value="<?php echo htmlspecialchars($workspaceBusinessId, ENT_QUOTES, \'UTF-8\'); ?>"', $payrollPage);
    $this->assertStringNotContainsString('business_page_shell business_details_intro', $detailsPage);
    $this->assertStringNotContainsString('business_page_title', $detailsPage);
    $this->assertStringNotContainsString('BUSINESS_DETAILS_TITLE', $detailsPage);
    $this->assertStringNotContainsString('BUSINESS_DETAILS_HELP', $detailsPage);
    $this->assertStringContainsString('class="visually_hidden"', $detailsPage);
    $this->assertStringContainsString('BUSINESS_NAV_DETAILS', $detailsPage);
    $this->assertStringContainsString('id="businesses_editor_business_id"', $detailsPage);
    $this->assertStringContainsString('PayCalBusinessDetailsDiagnostics', $detailsJs);
    $this->assertStringContainsString('pushBusinessDetailsDiagnostic', $detailsJs);
    $this->assertStringContainsString('autosave-scheduled', $businessesJs);
    $this->assertStringContainsString('autosave-timer-fired', $businessesJs);
    $this->assertStringContainsString('save-skip-no-business-id', $businessesJs);
    $this->assertStringContainsString('request-success', $businessesJs);
    $this->assertStringContainsString('request-failed', $businessesJs);

    $this->assertStringNotContainsString('id="businesses_business_details_title"', $businessPanel);
    $this->assertStringContainsString('class="panel business_details_profile_panel"', $businessPanel);
    $this->assertStringContainsString('class="panel business_details_contacts_panel"', $businessPanel);
    $this->assertStringContainsString('aria-labelledby="business_details_profile_heading"', $businessPanel);
    $this->assertStringContainsString('aria-labelledby="business_details_contacts_heading"', $businessPanel);
    $this->assertStringNotContainsString('businesses_editor_card', $businessPanel);
    $this->assertStringNotContainsString('businesses_actions_row', $businessPanel);
    $this->assertStringNotContainsString('businesses_editor_grid', $businessPanel);
    $this->assertStringContainsString('id="businesses_editor_name"', $businessPanel);
    $this->assertStringContainsString('id="businesses_editor_industry"', $businessPanel);
    $this->assertStringContainsString('id="businesses_editor_timezone_search"', $businessPanel);
    $this->assertStringContainsString('id="businesses_editor_currency_search"', $businessPanel);
    $this->assertStringNotContainsString('BUSINESSES_EDITOR_DETAILS_NOTICE', $businessPanel);
    $this->assertStringNotContainsString('id="businesses_editor_enforce_contact_domain"', $businessPanel);
    $this->assertStringNotContainsString('id="businesses_editor_allowed_contact_domains"', $businessPanel);
    $this->assertStringNotContainsString('id="businesses_editor_domain_policy_status"', $businessPanel);
    $this->assertStringNotContainsString('id="businesses_contact_card_add"', $businessPanel);
    $this->assertStringNotContainsString('id="businesses_editor_contact_operations_name"', $businessPanel);
    $this->assertStringNotContainsString('id="businesses_editor_contact_manager_name"', $businessPanel);
    $this->assertStringNotContainsString('id="businesses_editor_contact_support_name"', $businessPanel);
    $this->assertStringContainsString('$fixedContactKeys = [\'ceo\', \'coo\', \'cto\', \'payroll\', \'hr\'];', $businessPanel);
    $this->assertSame(1, substr_count($businessPanel, 'class="businesses_contact_card"'));
    $this->assertStringContainsString('businesses_contact_card_avatar_button', $businessPanel);
    $this->assertStringContainsString("businesses_index_i18n_html('BUSINESSES_CONTACT_CLEAR')", $businessPanel);
    $this->assertStringNotContainsString("businesses_index_i18n('DELETE')", $businessPanel);
    $this->assertMatchesRegularExpression(
      '/businesses_contact_card_avatar_button[\s\S]*?_role[\s\S]*?_name/s',
      $businessPanel,
    );
    $this->assertStringContainsString('business_details_notes_row', $businessPanel);
    $this->assertMatchesRegularExpression(
      '/<\/section>\s*<div class="businesses_field_grid business_details_notes_row">/s',
      $businessPanel,
    );
    $this->assertStringContainsString(
      'id="business_details_profile_heading" class="visually_hidden"',
      $businessPanel,
    );
    $this->assertStringNotContainsString(
      'businesses_section_header',
      substr(
        $businessPanel,
        0,
        (int) strpos($businessPanel, 'business_details_contacts_panel'),
      ),
    );
    $this->assertMatchesRegularExpression(
      '/\.business_details_notes_row\s*\{[^}]*grid-column:\s*1 \/ -1;/s',
      $businessCss,
    );
    $this->assertMatchesRegularExpression(
      '/\.business_details_notes_row\s*\{[^}]*width:\s*100%;/s',
      $businessCss,
    );
    $this->assertStringContainsString(
      '.business_details_profile_panel .businesses_details_column .businesses_field_grid',
      $businessCss,
    );
    $this->assertStringNotContainsString('id="businesses_business_details_save"', $businessPanel);
    $this->assertStringContainsString('id="businesses_business_details_status"', $businessPanel);
    $this->assertStringContainsString('rows="8"', $businessPanel);
    $this->assertStringContainsString('id="businesses_editor_business_notes"', $businessPanel);

    $this->assertStringNotContainsString('id="businesses_control_center_name"', $businessPanel);
    $this->assertStringNotContainsString('businesses_panel_org_details', $businessPanel);
    $this->assertStringNotContainsString('id="businesses_editor_name"', $membersPage);
    $this->assertStringNotContainsString('id="businesses_editor_name"', $editorDialog);
    $this->assertStringNotContainsString('businesses_panel_org_details', $editorDialog);
    $this->assertStringNotContainsString('businesses_panel_pay_period', $editorDialog);
    $this->assertStringNotContainsString('id="businesses_editor_default_wage"', $editorDialog);
    $this->assertStringNotContainsString('businesses_panel_sites_discovery', $editorDialog);
    $this->assertStringNotContainsString('businesses_panel_audit_timeline', $editorDialog);
    $this->assertStringNotContainsString('id="businesses_audit_control_test_panel"', $editorDialog);

    $this->assertStringContainsString('saveBusinessDetailsSettings', $businessesJs);
    $this->assertStringContainsString('collectBusinessDetailsPayload', $businessesJs);
    $this->assertStringContainsString('bindBusinessDetailsContactPanelEvents', $contactCardsJs);
    $this->assertStringContainsString("businessWorkspace?.addEventListener('click', handleContactCardPanelClick)", $contactCardsJs);
    $this->assertStringContainsString('BUSINESSES_CONTACT_CLEAR', $contactCardsJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/core/contact-cards.js.php'", $businessRouterJs);
    $this->assertStringContainsString("require __DIR__ . '/../business/core/contact-cards.js.php'", $profileRouterJs);
    $this->assertStringNotContainsString('const bindBusinessDetailsContactPanelEvents =', $businessesJs);
    $this->assertStringContainsString('aria-haspopup="dialog"', $businessPanel);
    $this->assertStringContainsString('aria-controls="businesses_contact_image_popover"', $businessPanel);
    $this->assertStringContainsString('aria-labelledby="businesses_contact_image_popover_title"', $businessPanel);
    $this->assertStringContainsString('id="businesses_contact_image_popover_title"', $businessPanel);
    $this->assertStringContainsString('closeContactImagePopover({ restoreFocus: true })', $contactCardsJs);
    $this->assertStringContainsString('trapContactImagePopoverFocus', $contactCardsJs);
    $this->assertStringContainsString('contactImagePopoverTrigger', $contactCardsJs);
    $this->assertStringNotContainsString('saveControlCenterSettings', $businessesJs);
    $this->assertStringNotContainsString('hydrateControlCenterInputs', $businessesJs);
  }

  #[Test]
  public function businessWorkspaceDialogsExposeConsistentAriaModalAndLabels(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $dialogFiles = [
      $projectRoot . '/html/business/_partials/dialogs.php',
      $projectRoot . '/html/business/_partials/members_dialogs.php',
      $projectRoot . '/html/business/_partials/route_gate_dialog.php',
    ];

    foreach ($dialogFiles as $dialogFile) {
      $markup = (string) file_get_contents($dialogFile);
      preg_match_all('/<dialog[^>]*>/', $markup, $matches);
      $this->assertNotEmpty($matches[0], $dialogFile);

      foreach ($matches[0] as $dialogTag) {
        $this->assertStringContainsString('aria-modal="true"', $dialogTag, $dialogFile);
        $this->assertStringContainsString('aria-labelledby=', $dialogTag, $dialogFile);
        $this->assertStringContainsString('aria-describedby=', $dialogTag, $dialogFile);
      }

      $this->assertStringContainsString("businesses_index_i18n_html('CLOSE')", $markup, $dialogFile);
    }
  }

  #[Test]
  public function controlCenterFieldIdsAreRemovedFromBusinessHtml(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $businessHtmlRoot = $projectRoot . '/html/business';

    $paths = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($businessHtmlRoot));
    foreach ($iterator as $fileInfo) {
      if (!$fileInfo->isFile()) {
        continue;
      }

      $path = $fileInfo->getPathname();
      if (!str_ends_with($path, '.php')) {
        continue;
      }

      $paths[] = $path;
    }

    $this->assertNotEmpty($paths);

    foreach ($paths as $path) {
      $contents = (string) file_get_contents($path);
      $this->assertStringNotContainsString('businesses_control_center_', $contents, $path);
      $this->assertStringNotContainsString('businesses_panel_org_details', $contents, $path);
      $this->assertStringNotContainsString('businesses_detail_name', $contents, $path);
    }
  }

  #[Test]
  public function businessesRequestControlsExposeAccessibleNamesAndToggleState(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $hubPanel = (string) file_get_contents($projectRoot . '/html/business/_archive/partials/hub_panel.php');
    $requestAccessPanel = (string) file_get_contents($projectRoot . '/html/business/_archive/partials/request_access_panel.php');
    $membersPage = (string) file_get_contents($projectRoot . '/html/business/members/index.php');
    $peopleAccessPanel = (string) file_get_contents($projectRoot . '/html/business/_archive/partials/people_access_panel.php');
    $governancePanel = (string) file_get_contents($projectRoot . '/html/business/_partials/governance_panel.php');
    $auditPage = (string) file_get_contents($projectRoot . '/html/business/audit/index.php');
    $businessesJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $contactCardsJs = (string) file_get_contents($projectRoot . '/html/js/business/core/contact-cards.js.php');

    $this->assertStringContainsString('id="businesses-hub"', $hubPanel);
    $this->assertStringContainsString('request_access_panel.php', $hubPanel);
    $this->assertStringContainsString('id="businesses_request_email"', $requestAccessPanel);
    $this->assertStringContainsString('aria-label="<?php echo businesses_index_i18n_html(\'BUSINESSES_REQUEST_EMAIL_PLACEHOLDER\'); ?>"', $requestAccessPanel);
    $this->assertStringContainsString('id="businesses_request_access_readonly"', $requestAccessPanel);
    $this->assertStringContainsString('aria-pressed="true"', $requestAccessPanel);
    $this->assertStringContainsString('id="businesses_request_access_full"', $requestAccessPanel);
    $this->assertStringContainsString('aria-pressed="false"', $requestAccessPanel);
    $this->assertStringNotContainsString('id="businesses_create_button"', $requestAccessPanel);

    $this->assertStringContainsString('id="businesses_editor_inline_mount"', $peopleAccessPanel);
    $this->assertStringContainsString('businesses_people_access_panel', $peopleAccessPanel);
    $this->assertStringContainsString('businesses_governance_panel', $governancePanel);
    $this->assertStringContainsString('businesses_permission_cards', $governancePanel);
    $this->assertStringContainsString('id="businesses_definitions_help_button"', $peopleAccessPanel);
    $this->assertStringContainsString('aria-label="<?php echo businesses_index_i18n_html(\'BUSINESSES_OPEN_DEFINITIONS_BTN\'); ?>"', $peopleAccessPanel);
    $this->assertStringContainsString('aria-expanded="false"', $peopleAccessPanel);
    $this->assertStringContainsString('class="panel businesses_inline_editor_panel businesses_settings_panel"', $peopleAccessPanel);

    $this->assertStringContainsString('data-business-subpage="audit"', $auditPage);
    $this->assertStringContainsString('BusinessNav::requireCoordinatorAccess()', $auditPage);
    $this->assertStringContainsString('_partials/governance_panel.php', $auditPage);
    $this->assertStringNotContainsString('_partials/hub_panel.php', $membersPage);
    $this->assertStringNotContainsString('_partials/connections_panel.php', $membersPage);
    $this->assertStringNotContainsString('_partials/people_access_panel.php', $membersPage);

    $this->assertStringContainsString('syncDefinitionsHelpExpanded', $businessesJs);
    $this->assertStringContainsString('p.setAttribute(\'aria-pressed\'', $businessesJs);
    $this->assertStringContainsString('applyContactInputAriaLabels', $contactCardsJs);
  }

  #[Test]
  public function membersPageIsFirstClassIamHome(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $membersPage = (string) file_get_contents($projectRoot . '/html/business/members/index.php');
    $membersDialogs = (string) file_get_contents($projectRoot . '/html/business/_partials/members_dialogs.php');
    $membersJs = (string) file_get_contents($projectRoot . '/html/js/business/subpages/members.js.php');
    $dashboard = (string) file_get_contents($projectRoot . '/html/business/index.php');
    $detailsPage = (string) file_get_contents($projectRoot . '/html/business/details/index.php');
    $businessesJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $memberRolePopoverJs = (string) file_get_contents($projectRoot . '/html/js/business/core/member-role-popover.js.php');
    $memberActionsJs = (string) file_get_contents($projectRoot . '/html/js/business/core/member-actions.js.php');
    $businessRouterJs = (string) file_get_contents($projectRoot . '/html/js/business/index.php');
    $membersGridRenderer = (string) file_get_contents($projectRoot . '/html/src/Domain/BusinessMembersGridRenderer.php');

    $this->assertStringContainsString('data-business-subpage="members"', $membersPage);
    $this->assertStringContainsString('$workspaceBusinessIdAttr', $membersPage);
    $this->assertStringNotContainsString('BUSINESS_MEMBERS_TITLE', $membersPage);
    $this->assertStringNotContainsString('BUSINESS_MEMBERS_HELP', $membersPage);
    $this->assertStringNotContainsString('business_page_shell business_members_intro', $membersPage);
    $this->assertStringNotContainsString('business_page_title', $membersPage);
    $this->assertStringContainsString('class="visually_hidden"', $membersPage);
    $this->assertStringContainsString('BUSINESS_NAV_MEMBERS', $membersPage);
    $this->assertStringContainsString('business_members_grid_shell', $membersPage);
    $this->assertStringContainsString('id="businesses-members-grid"', $membersPage);
    $this->assertStringContainsString('business_members_metrics_bar', $membersPage);
    $this->assertStringContainsString('business_members_bulk_toolbar_mount', $membersPage);
    $this->assertStringContainsString('business_members_bulk_toolbar', $membersPage);
    $this->assertStringNotContainsString('document.createElement(\'div\')', (string) file_get_contents($projectRoot . '/html/js/business/subpages/members.js.php'));
    $this->assertStringNotContainsString('id="business_members_select_all"', $membersPage);
    $this->assertStringContainsString('business_members_select_all_checkbox', $membersGridRenderer);
    $this->assertStringNotContainsString('business_members_apply_work_site', $membersPage);
    $this->assertStringNotContainsString('business_members_apply_site_select', $membersPage);
    $this->assertStringContainsString('business_members_datagrid', $membersPage);
    $this->assertStringContainsString('businesses_members_grid_sr_status', $membersPage);
    $this->assertStringContainsString('BUSINESSES_MEMBERS_GRID_ARIA', $membersPage);
    $this->assertStringContainsString('BusinessMembersGridRenderer', $membersPage);
    $this->assertStringContainsString('renderForBusiness', $membersPage);

    $this->assertStringNotContainsString('_partials/hub_panel.php', $membersPage);
    $this->assertStringNotContainsString('_partials/people_access_panel.php', $membersPage);
    $this->assertStringNotContainsString('_partials/governance_panel.php', $membersPage);
    $this->assertStringContainsString('_partials/members_dialogs.php', $membersPage);
    $this->assertStringContainsString('Render::cssURL(\'earnings\')', $membersPage);
    $this->assertStringContainsString('businesses_member_revoke_dialog', $membersDialogs);
    $this->assertStringContainsString('businesses_member_reports_dialog', $membersDialogs);
    $this->assertStringContainsString('earnings_member_reports_mount', $membersDialogs);
    $this->assertStringContainsString('aria-labelledby="businesses_member_revoke_dialog_title"', $membersDialogs);
    $this->assertStringContainsString('dialog_fullscreen', $membersDialogs);
    $this->assertStringNotContainsString('_partials/dialogs.php', $membersPage);
    $this->assertStringNotContainsString('_partials/editor_dialog.php', $membersPage);
    $this->assertStringNotContainsString('id="businesses-hub"', $membersPage);
    $this->assertStringNotContainsString('id="businesses_editor_inline_mount"', $membersPage);
    $this->assertStringNotContainsString('id="businesses_members_role_filter"', $membersPage);
    $this->assertStringNotContainsString('id="businesses_editor_pay_frequency"', $membersPage);
    $this->assertStringNotContainsString('businesses_panel_pay_period', $membersPage);
    $this->assertStringNotContainsString('id="businesses_editor_preview"', $membersPage);
    $this->assertStringNotContainsString('id="businesses_editor_default_wage"', $membersPage);
    $this->assertStringNotContainsString('editor_pay_period_panel.php', $membersPage);

    $this->assertStringNotContainsString('id="businesses_live_requests_list"', $dashboard);
    $this->assertStringNotContainsString('id="businesses-members-grid"', $dashboard);
    $this->assertStringNotContainsString('_partials/editor_dialog.php', $detailsPage);

    $this->assertStringContainsString("resolveBusinessSubPage() === 'members'", $businessesJs);
    $this->assertStringContainsString('openMembersPage', $businessesJs);
    $this->assertStringContainsString('loadBusinessMembersGrid', $businessesJs);
    $this->assertStringContainsString('loadBusinessMembersGrid', $membersJs);
    $this->assertStringContainsString('members/grid', $membersJs);
    $this->assertStringContainsString('ensureBusinessMembersGridManager', $membersJs);
    $this->assertStringContainsString('focusMembersGridSearchOnInitialLoad', $membersJs);
    $this->assertStringContainsString('membersSearchInitialFocusDone', $membersJs);
    $this->assertStringContainsString('bindBusinessMembersGridInteractions', $membersJs);
    $datagridJs = (string) file_get_contents($projectRoot . '/html/js/datagrid/index.php');
    $this->assertStringContainsString('paycal:datagrid-before-reload', $datagridJs);
    $this->assertStringContainsString('syncSearchFromInput', $datagridJs);
    $this->assertStringContainsString('function syncVisibleColumnState', $datagridJs);
    $this->assertStringContainsString('grid.dataset.visibleColumns', $datagridJs);
    $this->assertStringNotContainsString('style.gridTemplateColumns', $datagridJs);
    $this->assertStringContainsString('evacuateMembersBulkToolbar', $membersJs);
    $this->assertStringContainsString('paycal:datagrid-before-reload', $membersJs);
    $businessCss = (string) file_get_contents($projectRoot . '/html/css/business/index.php');
    $this->assertStringContainsString('[data-grid="business-members"] .datagrid_toolbar_start', $businessCss);
    $this->assertStringContainsString('grid-template-areas:', $businessCss);
    $this->assertStringContainsString('business_members_toolbar_filters', $businessCss);
    $this->assertStringContainsString('[data-grid="business-members"] .business_member_details_name', $businessCss);
    $this->assertStringContainsString('word-break: normal;', $businessCss);
    $this->assertStringContainsString('[data-grid="business-members"] .datagrid_col_last_active_at', $businessCss);
    $this->assertStringNotContainsString('business_members_role_filter', $membersJs);
    $this->assertStringContainsString('data-datagrid-param', $datagridJs);
    $this->assertStringContainsString('instance.setSearch(search)', $businessesJs);
    $this->assertStringNotContainsString('applyWorkSiteToSelectedMembers', $membersJs);
    $this->assertStringNotContainsString('members/apply-work-site', $membersJs);
    $this->assertStringContainsString('business_members_row_checkbox', $membersGridRenderer);
    $discoveryController = (string) file_get_contents($projectRoot . '/html/src/Controllers/BusinessDiscoveryController.php');
    $this->assertStringContainsString('members/apply-work-site', $discoveryController);
    $this->assertStringContainsString('applyWorkSiteToMembers', $discoveryController);
    $this->assertStringContainsString('$parts[3]', $discoveryController);
    $this->assertStringNotContainsString('businesses_member_role_trigger', $membersJs);
    $this->assertStringContainsString('business_member_role_submenu', $membersGridRenderer);
    $this->assertStringContainsString('business_member_role_menu_item', $membersJs);
    $this->assertStringContainsString('submitMemberRoleChange', $membersJs);
    $this->assertStringContainsString('MEMBER_ROLE_LABELS', $memberRolePopoverJs);
    $this->assertStringContainsString('resolveMemberDisplayNameFromRow', $memberActionsJs);
    $this->assertStringContainsString('formatMemberRoleUpdatedMessage', $memberRolePopoverJs);
    $this->assertStringContainsString('memberRoleUpdated', $memberRolePopoverJs);
    $this->assertStringContainsString('syncMemberRoleTriggerLabels', $memberRolePopoverJs);
    $this->assertStringContainsString('core/member-actions.js.php', $businessRouterJs);
    $this->assertStringContainsString('core/member-role-popover.js.php', $businessRouterJs);
    $this->assertStringNotContainsString('toggleMemberRolePopover', $membersJs);
    $this->assertStringContainsString('showConfirmRevokeDialog', $membersJs);
    $this->assertStringContainsString('openMemberReportsDialog', $membersJs);
    $this->assertStringContainsString('isMembersGridInteractiveTarget', $membersJs);
    $this->assertStringContainsString('promptMemberRevokeDialog', $memberActionsJs);
    $this->assertStringContainsString('openMemberReportsDialog', $memberActionsJs);
    $this->assertStringContainsString('/members/${encodeURIComponent(memberUuid)}/reports', $memberActionsJs);
    $this->assertStringContainsString('initMemberReportsEarningsView', $memberActionsJs);
    $memberReportsService = (string) file_get_contents($projectRoot . '/html/src/Domain/BusinessMemberReportsService.php');
    $this->assertStringContainsString('member_reports_line_graph_', $memberReportsService);
    $businessJsIndex = (string) file_get_contents($projectRoot . '/html/js/business/index.php');
    $memberReportsViewJs = (string) file_get_contents($projectRoot . '/html/js/earnings/member-reports-view.js');
    $this->assertStringContainsString('earnings_member_reports_view', $memberReportsService);
    $this->assertStringContainsString('member_reports_ytd_', $memberReportsService);
    $this->assertStringContainsString('member_reports_pay_periods_', $memberReportsService);
    $this->assertStringContainsString('member_reports_monthly_', $memberReportsService);
    $this->assertStringContainsString('member_reports_daily_earnings_', $memberReportsService);
    $this->assertStringContainsString('member_reports_piegraphs_panel_', $memberReportsService);
    $this->assertStringContainsString("jsStaticURL('js/earnings/member-reports-view.js')", $businessJsIndex);
    $this->assertStringContainsString('initMemberReportsEarningsView', $memberReportsViewJs);
    $this->assertStringContainsString('data-member-export-scope', $memberReportsViewJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/subpages/members.js.php'", $businessRouterJs);
    $this->assertStringNotContainsString('loadBusinessDetailViews(orgId)', $businessesJs);
    $this->assertStringNotContainsString("resolveBusinessSubPage() === 'members' && elements.liveRequestsList", $businessesJs);
  }

  #[Test]
  public function businessSitesPageIsDedicatedSiteManagementHome(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $sitesPage = (string) file_get_contents($projectRoot . '/html/business/sites/index.php');
    $businessCss = (string) file_get_contents($projectRoot . '/html/css/business/index.php');
    $businessesCss = (string) file_get_contents($projectRoot . '/html/css/businesses/index.php');
    $sitesCss = (string) file_get_contents($projectRoot . '/html/css/sites/index.php');
    $siteEditorDialogs = (string) file_get_contents($projectRoot . '/html/sites/_partials/site_editor_dialogs.php');
    $discoveryPanel = (string) file_get_contents($projectRoot . '/html/business/_partials/editor_sites_discovery_panel.php');
    $assignedPanel = (string) file_get_contents($projectRoot . '/html/business/_partials/business_sites_assigned_panel.php');
    $membersPage = (string) file_get_contents($projectRoot . '/html/business/members/index.php');
    $editorDialog = (string) file_get_contents($projectRoot . '/html/business/_archive/partials/editor_dialog.php');
    $businessesJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $permissionsJs = (string) file_get_contents($projectRoot . '/html/js/business/core/business-permissions.js.php');
    $sitesJs = (string) file_get_contents($projectRoot . '/html/js/business/subpages/sites.js.php');
    $discoveryCoreJs = (string) file_get_contents($projectRoot . '/html/js/business/core/discovery.js.php');
    $businessRouterJs = (string) file_get_contents($projectRoot . '/html/js/business/index.php');
    $calendarJs = (string) file_get_contents($projectRoot . '/html/js/calendar/calendar.js');

    $this->assertStringContainsString('data-business-subpage="sites"', $sitesPage);
    $this->assertStringNotContainsString('business_page_shell business_sites_intro', $sitesPage);
    $this->assertStringNotContainsString('business_page_title', $sitesPage);
    $this->assertStringNotContainsString('href="/sites/"', $sitesPage);
    $this->assertStringNotContainsString('BUSINESS_SITES_SCOPE_NOTE', $sitesPage);
    $this->assertStringNotContainsString('BUSINESS_SITES_PERSONAL_LINK', $sitesPage);
    $this->assertStringContainsString('class="visually_hidden"', $sitesPage);
    $this->assertStringContainsString('BUSINESS_NAV_SITES', $sitesPage);
    $this->assertStringContainsString('_partials/editor_sites_discovery_panel.php', $sitesPage);
    $this->assertStringContainsString('_partials/business_sites_assigned_panel.php', $sitesPage);
    $this->assertStringContainsString('<div class="business_sites_panels">', $sitesPage);
    $this->assertStringNotContainsString('businesses_editor_grid business_sites_panels', $sitesPage);
    $this->assertStringContainsString('class="panel businesses_sites_assigned_panel"', $assignedPanel);
    $this->assertStringContainsString('businesses_sites_assigned_panel', $assignedPanel);
    $this->assertStringNotContainsString('businesses_editor_card', $assignedPanel);
    $this->assertStringNotContainsString('businesses_editor_panel', $assignedPanel);
    $this->assertStringNotContainsString('BUSINESS_PAGE_COMING_SOON', $sitesPage);

    $this->assertStringContainsString('businesses_panel_sites_discovery', $discoveryPanel);
    $this->assertStringContainsString('class="panel businesses_panel_sites_discovery"', $discoveryPanel);
    $this->assertStringNotContainsString('businesses_editor_card', $discoveryPanel);
    $this->assertStringNotContainsString('businesses_editor_panel', $discoveryPanel);
    $this->assertStringContainsString('id="businesses_discovery_results"', $discoveryPanel);
    $this->assertStringContainsString('id="businesses_discovery_run"', $discoveryPanel);

    $this->assertStringContainsString('id="businesses-sites-grid"', $assignedPanel);
    $this->assertStringContainsString('id="businesses_sites_sr_status"', $assignedPanel);
    $this->assertStringContainsString('businesses_sites_assigned_panel', $assignedPanel);
    $this->assertStringContainsString('business_sites_datagrid', $assignedPanel);
    $this->assertStringContainsString('data-ssr-sites-grid="active"', $assignedPanel);
    $this->assertStringContainsString('canHydrateBusinessSitesGridFromSsr', $sitesJs);
    $this->assertStringContainsString('#main:has(#business-workspace.business_sites)', $businessCss);
    $this->assertStringContainsString('--businesses-content-width: 100%', $businessCss);
    $this->assertStringContainsString('[data-grid="business-sites-active"]', $businessCss);
    $this->assertStringContainsString('#main:has(#business-workspace.business_sites)', $businessesCss);
    $this->assertStringContainsString('[data-grid="business-sites-active"]', $sitesCss);
    $this->assertStringContainsString("siteEditorContext = 'business'", $sitesPage);
    $this->assertStringContainsString('site_editor_dialogs.php', $sitesPage);
    $this->assertStringContainsString('modal_create_site', $siteEditorDialogs);
    $this->assertStringContainsString('modal_edit_site', $siteEditorDialogs);
    $this->assertStringContainsString('edit_site_unlink_business', $siteEditorDialogs);
    $this->assertStringContainsString('data-business-sites-status="active"', $assignedPanel);
    $this->assertStringContainsString('data-business-sites-status="archived"', $assignedPanel);

    $this->assertStringNotContainsString('editor_sites_discovery_panel.php', $membersPage);
    $this->assertStringNotContainsString('businesses_panel_sites_discovery', $membersPage);
    $this->assertStringNotContainsString('id="businesses_sites_list"', $membersPage);
    $this->assertStringNotContainsString('businesses_panel_sites_discovery', $editorDialog);

    $this->assertStringContainsString("resolveBusinessSubPage() === 'sites'", $businessesJs);
    $this->assertStringContainsString('openSitesPage', $sitesJs);
    $this->assertStringContainsString('loadBusinessSitesGrid', $sitesJs);
    $this->assertStringContainsString('startDiscoveryPolling', $discoveryCoreJs);
    $this->assertStringContainsString('handleDiscovery', $discoveryCoreJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/core/discovery.js.php'", $businessRouterJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/subpages/sites.js.php'", $businessRouterJs);
    $this->assertStringContainsString('loadBusinessSitesGrid', $sitesJs);
    $this->assertStringContainsString('ensureBusinessSitesGridManager', $sitesJs);
    $this->assertStringContainsString('initSiteEditor', $sitesJs);
    $this->assertStringContainsString('getBusinessSiteEditor', $sitesJs);
    $this->assertStringContainsString('sitesGridManagerStatus', $sitesJs);
    $this->assertStringContainsString('onRowClick: handleBusinessSitesRowClick', $sitesJs);
    $this->assertStringContainsString('openEditSiteDialog', $sitesJs);
    $this->assertStringContainsString('const canWriteBusinessSites =', $permissionsJs);
    $this->assertStringNotContainsString('const canWriteBusinessSites =', $businessesJs);
    $this->assertStringContainsString('site-editor-core.php', $businessRouterJs);
    $this->assertStringContainsString('/api/v1/sites/calendar', $calendarJs);
    $this->assertStringContainsString('display_name', $calendarJs);
  }

  #[Test]
  public function businessGroupsPageUsesPagePanelMarkup(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $groupsPage = (string) file_get_contents($projectRoot . '/html/business/groups/index.php');
    $businessCss = (string) file_get_contents($projectRoot . '/html/css/business/index.php');

    $this->assertStringContainsString('data-business-subpage="groups"', $groupsPage);
    $this->assertStringContainsString('class="panel business_groups_panel"', $groupsPage);
    $this->assertStringContainsString('business_groups_datagrid', $groupsPage);
    $this->assertStringNotContainsString('businesses_editor_card businesses_editor_card_full businesses_editor_panel business_groups_panel', $groupsPage);
    $this->assertStringContainsString('.business_groups_panel {', $businessCss);
  }

  #[Test]
  public function businessPayrollPageIsDedicatedPaySettingsHome(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $payrollPage = (string) file_get_contents($projectRoot . '/html/business/payroll/index.php');
    $payrollPanel = (string) file_get_contents($projectRoot . '/html/business/_partials/payroll_settings_panel.php');
    $payPeriodPanel = (string) file_get_contents($projectRoot . '/html/business/_partials/editor_pay_period_panel.php');
    $membersPage = (string) file_get_contents($projectRoot . '/html/business/members/index.php');
    $editorDialog = (string) file_get_contents($projectRoot . '/html/business/_archive/partials/editor_dialog.php');
    $businessesJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $businessCss = (string) file_get_contents($projectRoot . '/html/css/business/index.php');

    $this->assertStringContainsString('data-business-subpage="payroll"', $payrollPage);
    $this->assertStringNotContainsString('business_page_shell business_payroll_intro', $payrollPage);
    $this->assertStringNotContainsString('business_page_title', $payrollPage);
    $this->assertStringNotContainsString('BUSINESS_PAYROLL_HELP', $payrollPage);
    $this->assertStringNotContainsString('href="/forecast/"', $payrollPage);
    $this->assertStringNotContainsString('BUSINESS_PAYROLL_FORECAST_LINK', $payrollPage);
    $this->assertStringContainsString('class="visually_hidden"', $payrollPage);
    $this->assertStringContainsString('BUSINESS_NAV_PAYROLL', $payrollPage);
    $this->assertStringContainsString('_partials/payroll_settings_panel.php', $payrollPage);
    $this->assertStringContainsString('id="businesses_editor_business_id"', $payrollPage);
    $this->assertStringNotContainsString('BUSINESS_PAGE_COMING_SOON', $payrollPage);

    $this->assertStringContainsString('businesses_payroll_panel', $payrollPanel);
    $this->assertStringContainsString('business_payroll_section business_payroll_default_wage', $payrollPanel);
    $this->assertStringContainsString('business_payroll_section business_payroll_pay_period', $payPeriodPanel);
    $this->assertStringContainsString('business_payroll_section_header', $payrollPanel);
    $this->assertStringContainsString('business_payroll_section_header', $payPeriodPanel);
    $this->assertStringNotContainsString('businesses_editor_card', $payrollPanel);
    $this->assertStringNotContainsString('businesses_editor_card', $payPeriodPanel);
    $this->assertStringNotContainsString('businesses_editor_grid', $payrollPanel);
    $this->assertStringNotContainsString('businesses_editor_panel', $payPeriodPanel);
    $this->assertStringNotContainsString('id="businesses_payroll_title"', $payrollPanel);
    $this->assertStringContainsString('aria-labelledby="business_payroll_heading"', $payrollPanel);
    $this->assertStringContainsString('id="business_payroll_heading"', $payrollPanel);
    $this->assertStringContainsString('BUSINESS_PAYROLL_TITLE', $payrollPanel);
    $this->assertStringContainsString('id="businesses_editor_default_wage"', $payrollPanel);
    $this->assertStringContainsString('id="businesses_payroll_save"', $payrollPanel);
    $this->assertStringContainsString('id="businesses_payroll_status"', $payrollPanel);
    $this->assertStringContainsString('editor_pay_period_panel.php', $payrollPanel);

    $this->assertStringContainsString('id="businesses_editor_pay_frequency"', $payPeriodPanel);
    $this->assertStringContainsString('id="businesses_editor_pay_period_length"', $payPeriodPanel);
    $this->assertStringContainsString('business_payroll_pay_period', $payPeriodPanel);
    $this->assertStringContainsString('id="businesses_editor_preview"', $payPeriodPanel);
    $this->assertStringContainsString('businesses_index_i18n_html(', $payPeriodPanel);

    $this->assertStringNotContainsString('id="businesses_editor_pay_frequency"', $membersPage);
    $this->assertStringNotContainsString('businesses_panel_pay_period', $membersPage);
    $this->assertStringNotContainsString('id="businesses_editor_preview"', $membersPage);
    $this->assertStringNotContainsString('id="businesses_editor_default_wage"', $membersPage);
    $this->assertStringNotContainsString('payroll_settings_panel.php', $membersPage);
    $this->assertStringNotContainsString('id="businesses_editor_default_wage"', $editorDialog);
    $this->assertStringNotContainsString('businesses_panel_pay_period', $editorDialog);

    $this->assertStringContainsString("resolveBusinessSubPage() === 'payroll'", $businessesJs);
    $this->assertStringContainsString('openPayrollPage', $businessesJs);
    $this->assertStringContainsString('savePayrollSettings', $businessesJs);
    $this->assertStringContainsString('collectPayrollPayload', $businessesJs);
    $this->assertStringContainsString('handleSavePayroll', $businessesJs);
    $this->assertStringContainsString('.business_payroll_section {', $businessCss);
    $this->assertStringContainsString('#business-workspace.business_payroll .business_payroll_section', $businessCss);
    $this->assertStringNotContainsString('#business-workspace.business_payroll .businesses_editor_card', $businessCss);
    $this->assertStringNotContainsString('scroll-snap-stop: normal;', $businessCss);
  }

  #[Test]
  public function businessAuditPageIsConsolidatedAuditHome(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $auditPage = (string) file_get_contents($projectRoot . '/html/business/audit/index.php');
    $complianceRedirect = (string) file_get_contents($projectRoot . '/html/business/compliance/index.php');
    $governanceRedirect = (string) file_get_contents($projectRoot . '/html/business/governance/index.php');
    $auditPanels = (string) file_get_contents($projectRoot . '/html/business/_partials/editor_audit_panels.php');
    $membersPage = (string) file_get_contents($projectRoot . '/html/business/members/index.php');
    $payrollPage = (string) file_get_contents($projectRoot . '/html/business/payroll/index.php');
    $dashboard = (string) file_get_contents($projectRoot . '/html/business/index.php');
    $editorDialog = (string) file_get_contents($projectRoot . '/html/business/_archive/partials/editor_dialog.php');
    $businessesJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $businessRouterJs = (string) file_get_contents($projectRoot . '/html/js/business/index.php');
    $auditSubpageJs = (string) file_get_contents($projectRoot . '/html/js/business/subpages/audit.js.php');
    $auditCoreJs = (string) file_get_contents($projectRoot . '/html/js/business/core/audit-grids.js.php');
    $businessNav = (string) file_get_contents($projectRoot . '/html/src/Domain/BusinessNav.php');
    $businessCss = (string) file_get_contents($projectRoot . '/html/css/business/index.php');

    $this->assertStringContainsString('data-business-subpage="audit"', $auditPage);
    $this->assertStringContainsString('_partials/editor_audit_panels.php', $auditPage);
    $this->assertStringContainsString('_partials/governance_panel.php', $auditPage);
    $this->assertStringContainsString('<div class="business_audit_panels">', $auditPage);
    $this->assertStringNotContainsString('businesses_editor_grid business_audit_panels', $auditPage);
    $this->assertStringNotContainsString('business_page_shell business_audit_intro', $auditPage);
    $this->assertStringNotContainsString('business_page_title', $auditPage);
    $this->assertStringNotContainsString('BUSINESS_AUDIT_TITLE', $auditPage);
    $this->assertStringNotContainsString('BUSINESS_AUDIT_HELP', $auditPage);
    $this->assertStringContainsString('class="visually_hidden"', $auditPage);
    $this->assertStringContainsString('BUSINESS_NAV_AUDIT', $auditPage);
    $this->assertStringContainsString('BUSINESS_COMPLIANCE_SOC_HEADING', $auditPage);
    $this->assertStringContainsString('BUSINESS_GOVERNANCE_SUMMARY_TITLE', $auditPage);
    $this->assertStringContainsString('BUSINESS_GOVERNANCE_REVOKED_CONNECTIONS', $auditPage);
    $this->assertStringNotContainsString('BUSINESS_GOVERNANCE_REVOKED_RELATIONSHIPS', $auditPage);
    $this->assertStringContainsString('href="/soc2/"', $auditPage);
    $this->assertStringContainsString('loadBusinessContextHeaderMetrics', $auditPage);
    $this->assertStringContainsString('$snapshot[\'connections\']', $auditPage);
    $this->assertStringNotContainsString('listRelationships', $auditPage);
    $this->assertStringNotContainsString('BUSINESS_PAGE_COMING_SOON', $auditPage);

    $this->assertStringContainsString("header('Location: /business/audit/'", $complianceRedirect);
    $this->assertStringContainsString("header('Location: /business/audit/'", $governanceRedirect);

    $this->assertStringContainsString('businesses_panel_audit_timeline', $auditPanels);
    $this->assertStringContainsString('business_audit_section business_audit_control_test_section', $auditPanels);
    $this->assertStringContainsString('business_audit_section businesses_panel_audit_timeline', $auditPanels);
    $this->assertStringNotContainsString('businesses_editor_card', $auditPanels);
    $this->assertStringNotContainsString('businesses_editor_panel', $auditPanels);
    $this->assertStringContainsString('id="businesses-audit-grid-host"', $auditPanels);
    $this->assertStringContainsString('id="businesses_audit_control_test_panel"', $auditPanels);
    $this->assertStringContainsString('id="businesses_audit_reload"', $auditPanels);
    $this->assertStringContainsString('$showDevAdminPanels', $auditPanels);
    $this->assertStringContainsString('.business_audit_section {', $businessCss);
    $this->assertStringContainsString('#business-workspace.business_audit .business_audit_section', $businessCss);

    $this->assertStringNotContainsString('id="businesses-audit-grid-host"', $membersPage);
    $this->assertStringNotContainsString('editor_audit_panels.php', $membersPage);
    $this->assertStringNotContainsString('businesses_panel_audit_timeline', $membersPage);
    $this->assertStringNotContainsString('id="businesses-audit-grid-host"', $payrollPage);
    $this->assertStringNotContainsString('id="businesses-audit-grid-host"', $dashboard);
    $this->assertStringNotContainsString('businesses_panel_audit_timeline', $editorDialog);

    $this->assertStringContainsString('BusinessNav::requireCoordinatorAccess()', $auditPage);
    $this->assertStringContainsString("'page' => 'PAGE_BUSINESS_AUDIT', 'href' => '/business/audit/', 'label_key' => 'BUSINESS_NAV_AUDIT', 'min_role' => 'coordinator'", $businessNav);

    $this->assertStringContainsString("resolveBusinessSubPage() === 'audit'", $businessesJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/core/audit-grids.js.php'", $businessRouterJs);
    $this->assertStringContainsString('openAuditPage', $auditSubpageJs);
    $this->assertStringContainsString('loadBusinessAudit', $auditSubpageJs);
    $this->assertStringContainsString('startRealtimeAuditPolling', $auditSubpageJs);
    $this->assertStringContainsString('loadBusinessAudit', $auditCoreJs);
    $this->assertStringContainsString('ensureAuditGridManager', $auditCoreJs);
    $this->assertStringContainsString('startRealtimeAuditPolling', $auditCoreJs);
    $this->assertStringContainsString("resolveBusinessSubPage() !== 'audit'", $businessesJs);
  }

  #[Test]
  public function membersPageHostsConsolidatedIamSurfacesAlongsideAuditPage(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $auditPage = (string) file_get_contents($projectRoot . '/html/business/audit/index.php');
    $hubPanel = (string) file_get_contents($projectRoot . '/html/business/_archive/partials/hub_panel.php');
    $requestAccessPanel = (string) file_get_contents($projectRoot . '/html/business/_archive/partials/request_access_panel.php');
    $membersPage = (string) file_get_contents($projectRoot . '/html/business/members/index.php');
    $membersJs = (string) file_get_contents($projectRoot . '/html/js/business/subpages/members.js.php');
    $dashboard = (string) file_get_contents($projectRoot . '/html/business/index.php');
    $accountPage = (string) file_get_contents($projectRoot . '/html/settings/account/index.php');
    $calendarPage = (string) file_get_contents($projectRoot . '/html/settings/calendar/index.php');
    $billingPartial = (string) file_get_contents($projectRoot . '/html/settings/_partials/panel_account_billing.php');
    $businessNav = (string) file_get_contents($projectRoot . '/html/src/Domain/BusinessNav.php');
    $businessesJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');

    $this->assertStringContainsString('data-business-subpage="audit"', $auditPage);
    $this->assertStringContainsString('BusinessNav::requireCoordinatorAccess()', $auditPage);
    $this->assertStringContainsString("'page' => 'PAGE_BUSINESS_AUDIT', 'href' => '/business/audit/', 'label_key' => 'BUSINESS_NAV_AUDIT', 'min_role' => 'coordinator'", $businessNav);

    $this->assertStringContainsString('data-business-subpage="members"', $membersPage);
    $this->assertStringContainsString('$workspaceBusinessIdAttr', $membersPage);
    $this->assertStringContainsString('id="businesses-members-grid"', $membersPage);
    $this->assertStringContainsString('BusinessMembersGridRenderer', $membersPage);
    $this->assertStringNotContainsString('_partials/hub_panel.php', $membersPage);
    $this->assertStringNotContainsString('_partials/connections_panel.php', $membersPage);
    $this->assertStringNotContainsString('_partials/governance_dialogs.php', $membersPage);
    $this->assertStringNotContainsString('_partials/people_access_panel.php', $membersPage);
    $this->assertStringNotContainsString('_partials/governance_panel.php', $membersPage);

    $this->assertStringContainsString('id="businesses-hub"', $hubPanel);
    $this->assertStringContainsString('id="businesses_request_email"', $requestAccessPanel);
    $this->assertStringNotContainsString('businesses_browser_panel', $hubPanel);

    $this->assertStringContainsString('loadBusinessMembersGrid', $membersJs);
    $this->assertStringContainsString('openMembersPage', $businessesJs);
    $this->assertStringNotContainsString('loadMembersHubPanels', $businessesJs);

    $this->assertStringNotContainsString('id="businesses-hub"', $dashboard);
    $this->assertStringNotContainsString('id="businesses_request_email"', $dashboard);

    $businessConnectionsPage = (string) file_get_contents($projectRoot . '/html/connections/index.php');
    $this->assertStringNotContainsString('profile_connect_panel.php', $accountPage);
    $this->assertStringContainsString('profile_connect_panel.php', $businessConnectionsPage);
    $this->assertStringContainsString('data-business-subpage="connections"', $businessConnectionsPage);
    $this->assertStringContainsString('id="panel-billing"', $billingPartial);
    $this->assertStringContainsString('route_gate_dialog.php', $accountPage);
    $this->assertStringContainsString('panel_calendar_work_defaults.php', $calendarPage);

    $profileJs = (string) file_get_contents($projectRoot . '/html/js/business-profile/index.php');
    $this->assertStringContainsString('initialize().catch', $profileJs);

    $this->assertStringContainsString("resolveBusinessSubPage() === 'audit'", $businessesJs);
    $this->assertStringContainsString('openAuditPage', $businessesJs);
  }

  #[Test]
  public function personalReportsPageHasNoTeamViewTabs(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $page = (string) file_get_contents($projectRoot . '/html/reports/index.php');

    $this->assertStringNotContainsString('earnings_view_tabs', $page);
    $this->assertStringNotContainsString('view=team', $page);
    $this->assertStringContainsString('renderSections', $page);
  }

  #[Test]
  public function businessReportsUsesOrgSelectorSemantics(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $page = (string) file_get_contents($projectRoot . '/html/business/reports/index.php');
    $reportsJs = (string) file_get_contents($projectRoot . '/html/js/business/subpages/reports.js.php');
    $businessesJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');

    $this->assertStringContainsString('use PayCal\\Observability\\Lens;', $page);
    $this->assertStringContainsString('Lens::renderPageConsoleDebug(\'business/reports\'', $page);
    $this->assertStringContainsString('Lens::renderPagePerformanceBoot(\'business/reports\'', $page);
    $this->assertStringContainsString('Lens::workspaceLensDataAttributes(\'business/reports\'', $page);
    $this->assertStringContainsString('data-business-subpage="reports"', $page);
    $this->assertStringContainsString('data-lens-mode', $page);
    $this->assertStringContainsString('$workspaceBusinessIdAttr', $page);
    $this->assertStringNotContainsString('business_page_shell business_reports_intro', $page);
    $this->assertStringNotContainsString('business_page_title', $page);
    $this->assertStringNotContainsString('BUSINESS_REPORTS_TITLE', $page);
    $this->assertStringNotContainsString('BUSINESS_REPORTS_HELP', $page);
    $this->assertStringContainsString('class="visually_hidden"', $page);
    $this->assertStringContainsString('BUSINESS_NAV_REPORTS', $page);
    $this->assertStringContainsString('business_reports_panel_shell', $page);
    $this->assertStringContainsString('BusinessReportsPanelRenderer', $page);
    $this->assertStringContainsString('loadingSkeleton', $page);
    $this->assertStringContainsString('data-ssr-reports-panel="1"', $page);
    $this->assertStringContainsString('ssr_panel_render_success', $page);
    $this->assertStringNotContainsString('data-reports-panel-defer="1"', $page);
    $this->assertStringContainsString('business_reports_sr_status', $page);
    $this->assertStringContainsString('team_earnings_data.php', $page);
    $this->assertStringContainsString('team_earnings_panel.php', $page);
    $this->assertStringNotContainsString('BusinessReportsMembersGridRenderer', $page);
    $this->assertStringNotContainsString('business-reports-members-grid', $page);
    $this->assertStringNotContainsString('business_reports_members_shell', $page);
    $this->assertStringNotContainsString('data-ssr-reports-members-grid', $page);
    $this->assertStringNotContainsString('data-reports-members-defer="1"', $page);
    $this->assertStringNotContainsString('fetch_url_pattern\' => \'/reports/members/grid\'', $page);
    $this->assertStringNotContainsString('datagrid_container', $page);
    $this->assertStringNotContainsString('BUSINESS_REPORTS_MEMBERS_GRID_', $page);
    $this->assertStringContainsString('id="earnings_team_org" name="org" class="earnings_tab_org_select" aria-label="<?php echo htmlspecialchars($i18n[\'EARNINGS_SELECT_BUSINESS\']', $page);
    $this->assertStringNotContainsString('earnings_view_tabs', $page);
    $this->assertStringNotContainsString('earnings_team_grid_row--clickable', $page);
    $this->assertStringContainsString('business_reports_panel_shell', $page);

    $teamPanel = (string) file_get_contents($projectRoot . '/html/reports/_partials/team_earnings_panel.php');
    $this->assertStringNotContainsString('data-team-type="members"', $teamPanel);
    $this->assertStringNotContainsString('EARNINGS_MEMBER_RANKING_SUBTITLE', $teamPanel);
    $this->assertStringNotContainsString('foreach ($memberRanked_ as $ri => $mr)', $teamPanel);
    $this->assertStringNotContainsString('BusinessReportsMembersGridRenderer', $teamPanel);

    $this->assertStringContainsString('openBusinessReportsPage', $businessesJs);
    $this->assertStringContainsString('syncBusinessReportsPanelFromDom', $reportsJs);
    $this->assertStringContainsString('canHydrateBusinessReportsPanelFromSsr', $reportsJs);
    $this->assertStringContainsString('ssrReportsPanel', $reportsJs);
    $this->assertStringContainsString('reportsPanelLoading', $reportsJs);
    $this->assertStringNotContainsString('loadBusinessReportsMembersGrid', $reportsJs);
    $this->assertStringNotContainsString('reports/members/grid', $reportsJs);
    $this->assertStringNotContainsString('createDataGrid', $reportsJs);
    $this->assertStringContainsString('[PayCal Lens][business/reports]', $reportsJs);
    $this->assertStringContainsString('logBusinessReportsLensDebug', $reportsJs);
    $this->assertStringContainsString('logBusinessReportsLensPageDebug', $reportsJs);
    $this->assertStringContainsString('resolveBusinessReportsLensBootOptions', $reportsJs);
    $this->assertStringContainsString('resolveBusinessReportsLensPerf', $reportsJs);
    $this->assertStringContainsString("PayCalLensPerformance.create('business/reports'", $reportsJs);
    $this->assertStringContainsString('ranked: true', $reportsJs);
    $this->assertStringContainsString('finalizeBusinessReportsLensPerfSummary', $reportsJs);
    $this->assertStringContainsString("resolveBusinessSubPage() === 'reports'", $reportsJs);

    $this->assertFileDoesNotExist($projectRoot . '/html/src/Domain/BusinessReportsMembersGridRenderer.php');
    $discoveryController = (string) file_get_contents($projectRoot . '/html/src/Controllers/BusinessDiscoveryController.php');
    $this->assertStringNotContainsString('reports/members/grid', $discoveryController);
    $this->assertStringContainsString('reports/breakdown/year', $discoveryController);

    $this->assertStringContainsString('resolveReportsLensPerf', $businessesJs);
    $this->assertStringContainsString('markHydrationComplete', $reportsJs);
  }

  #[Test]
  public function businessSubpagesOmitPageIntroShell(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $subpages = [
      'dashboard' => $projectRoot . '/html/business/index.php',
      'details' => $projectRoot . '/html/business/details/index.php',
      'members' => $projectRoot . '/html/business/members/index.php',
      'sites' => $projectRoot . '/html/business/sites/index.php',
      'payroll' => $projectRoot . '/html/business/payroll/index.php',
      'audit' => $projectRoot . '/html/business/audit/index.php',
      'reports' => $projectRoot . '/html/business/reports/index.php',
    ];

    foreach ($subpages as $name => $path) {
      $page = (string) file_get_contents($path);
      $this->assertStringNotContainsString('business_page_shell business_' . $name . '_intro', $page, $path);
      $this->assertStringNotContainsString('class="business_page_title"', $page, $path);
      $this->assertStringContainsString('<h1 class="visually_hidden">', $page, $path);
    }
  }

  #[Test]
  public function businessMembersUsesLensPerformanceSemantics(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $page = (string) file_get_contents($projectRoot . '/html/business/members/index.php');
    $membersJs = (string) file_get_contents($projectRoot . '/html/js/business/subpages/members.js.php');
    $businessesJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');

    $this->assertStringContainsString('use PayCal\\Observability\\Lens;', $page);
    $this->assertStringContainsString('Lens::renderPageConsoleDebug(\'business/members\'', $page);
    $this->assertStringContainsString('Lens::renderPagePerformanceBoot(\'business/members\'', $page);
    $this->assertStringContainsString('Lens::workspaceLensDataAttributes(\'business/members\'', $page);
    $this->assertStringContainsString('data-business-subpage="members"', $page);
    $this->assertStringContainsString('data-lens-mode', $page);

    $this->assertStringContainsString('[PayCal Lens][business/members]', $membersJs);
    $this->assertStringContainsString('logBusinessMembersLensDebug', $membersJs);
    $this->assertStringContainsString('logBusinessMembersLensPageDebug', $membersJs);
    $this->assertStringContainsString('resolveBusinessMembersLensBootOptions', $membersJs);
    $this->assertStringContainsString('resolveBusinessMembersLensPerf', $membersJs);
    $this->assertStringContainsString("PayCalLensPerformance.create('business/members'", $membersJs);
    $this->assertStringContainsString('ranked: true', $membersJs);
    $this->assertStringContainsString('loadBusinessMembersGrid (API)', $membersJs);
    $this->assertStringContainsString('syncMemberRoleTriggerLabels', $membersJs);

    $this->assertStringContainsString('resolveMembersLensPerf', $businessesJs);
    $this->assertStringContainsString('finalizeBusinessMembersLensPerfSummary', $membersJs);
    $this->assertStringContainsString('markHydrationComplete', $businessesJs);
    $this->assertStringContainsString('summarize(\'Performance Summary\'', $businessesJs);
    $this->assertStringContainsString('} finally {', $businessesJs);
  }

  #[Test]
  public function businessReportsPanelUsesI18nForVisibleCopy(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $page = (string) file_get_contents($projectRoot . '/html/business/reports/index.php');
    $teamPanel = (string) file_get_contents($projectRoot . '/html/reports/_partials/team_earnings_panel.php');
    $memberReports = (string) file_get_contents($projectRoot . '/html/src/Domain/BusinessMemberReportsService.php');
    $renderer = (string) file_get_contents($projectRoot . '/html/src/Domain/BusinessReportsPanelRenderer.php');
    $stateJs = (string) file_get_contents($projectRoot . '/html/js/business/core/state.js.php');

    $this->assertStringContainsString("Strings::i18n('BUSINESS_NAV_REPORTS')", $page);
    $this->assertStringContainsString("Strings::i18n('BUSINESS_REPORTS_ANALYTICS_HEADING')", $page);
    $this->assertStringContainsString("businesses_index_i18n('BUSINESS_REPORTS_ANALYTICS_LOADED')", $page);
    $this->assertStringContainsString('BusinessReportsPanelRenderer', $page);
    $this->assertStringContainsString("Strings::i18n('EARNINGS_TREND')", $memberReports);
    $this->assertStringContainsString("Strings::i18n('EARNINGS_HI_TITLE')", $memberReports);
    $this->assertStringContainsString("Strings::i18n('EARNINGS_HI_REGIME_ABOVE')", $memberReports);
    $this->assertStringContainsString("earnings_i18n('EARNINGS_HEALTH_AVG_WEEKLY_HRS'", $teamPanel);
    $this->assertStringContainsString("Strings::i18n('BUSINESS_REPORTS_LOADING_ANALYTICS_YEAR')", $renderer);
    $this->assertStringContainsString("org_js_index_i18n('BUSINESS_REPORTS_LOADING_ANALYTICS')", $stateJs);
    $this->assertStringNotContainsString('>Earnings Trend<', $memberReports);
    $this->assertStringNotContainsString('>Historical Intelligence<', $memberReports);
    $this->assertStringNotContainsString('>Years Observed<', $memberReports);
    $this->assertStringNotContainsString('>Above trailing baseline<', $memberReports);
    $this->assertStringNotContainsString('>Avg Weekly Hrs / Worker<', $teamPanel);
    $this->assertStringNotContainsString('aria-label="Payroll composition bar"', $teamPanel);
    $this->assertStringNotContainsString('>Future Net Pay<', $memberReports);
    $this->assertStringContainsString("earnings_i18n_fmt('EARNINGS_RISK_REGISTER_SUBTITLE_FMT'", $teamPanel);
    $this->assertStringNotContainsString('> active risk', $teamPanel);
    $this->assertStringNotContainsString('ucfirst($rk_[\'severity\'])', $teamPanel);

    $teamEarningsJs = (string) file_get_contents($projectRoot . '/html/js/team-earnings/index.php');
    $this->assertStringContainsString('Object.assign(PC.config', $teamEarningsJs);
    $this->assertStringContainsString('EARNINGS_MEMBER_DIALOG_TITLE', $teamEarningsJs);

    $snapshotBuilder = (string) file_get_contents($projectRoot . '/html/src/Domain/TeamEarningsSnapshotBuilder.php');
    $this->assertStringContainsString("Strings::i18n('EARNINGS_UNKNOWN_SITE')", $snapshotBuilder);
    $this->assertStringContainsString('Strings::formatLocalizedShortMonthYear', $snapshotBuilder);
    $this->assertStringContainsString('Strings::formatLocalizedShortMonthYear', $memberReports);
    $this->assertStringNotContainsString("->format('M Y')", $snapshotBuilder);
    $this->assertStringNotContainsString("->format('M Y')", $memberReports);
  }

  #[Test]
  public function businessReportsHistoricalIntelligenceKeysAreTranslatedInAllLocales(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $englishFallbacks = [
      'EARNINGS_HI_TITLE' => 'Historical Intelligence',
      'EARNINGS_HI_YEARS_OBSERVED' => 'Years Observed',
      'EARNINGS_HI_TRAILING_BASELINE' => 'Trailing Baseline',
      'EARNINGS_HI_REGIME_ABOVE' => 'Above trailing baseline',
      'EARNINGS_HI_NOTE' => 'Signals derive from available yearly earnings history and should be used as directional guidance.',
    ];

    foreach (['de', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'] as $locale) {
      $content = (string) file_get_contents($projectRoot . '/strings/' . $locale . '.txt');

      foreach ($englishFallbacks as $key => $englishValue) {
        $pattern = '/^' . preg_quote($key, '/') . ' ' . preg_quote($englishValue, '/') . '$/m';
        $this->assertDoesNotMatchRegularExpression(
          $pattern,
          $content,
          sprintf('%s still uses the English fallback for %s', $locale, $key),
        );
      }
    }
  }

  #[Test]
  public function businessReportsRemainingPanelKeysAreTranslatedInAllLocales(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $englishFallbacks = [
      'EARNINGS_TEAM_UNLINKED_ONLY_GUARD' => 'Detected {count} work entries that were excluded because their sites are not linked to this business. Link the missing site(s) in Businesses to restore business totals.',
      'EARNINGS_RISK_REGISTER_SUBTITLE_FMT' => '{count} active risk(s) requiring attention',
      'EARNINGS_TEAM_EMPTY_TITLE' => 'No payroll data yet',
      'EARNINGS_TEAM_FOR_ORG_YEAR' => 'Business reports for {org} {year}',
      'EARNINGS_MEMBER_DIALOG_TITLE' => '{name} - {year} Earnings',
      'EARNINGS_BREAKDOWN_NO_ENTRIES_FOR_YEAR' => 'No entries for {year}.',
      'EARNINGS_UNKNOWN_SITE' => 'Unknown Site',
      'BUSINESS_REPORTS_NO_ACCESS' => 'You need an active business membership with financial access to view reports.',
      'BUSINESS_REPORTS_ANALYTICS_HEADING' => 'Payroll analytics',
    ];

    foreach (['de', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'] as $locale) {
      $content = (string) file_get_contents($projectRoot . '/strings/' . $locale . '.txt');

      foreach ($englishFallbacks as $key => $englishValue) {
        $pattern = '/^' . preg_quote($key, '/') . ' ' . preg_quote($englishValue, '/') . '$/m';
        $this->assertDoesNotMatchRegularExpression(
          $pattern,
          $content,
          sprintf('%s still uses the English fallback for %s', $locale, $key),
        );
      }
    }
  }

  #[Test]
  public function businessReportsTrendKeysAreTranslatedInAllLocales(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $englishFallbacks = [
      'EARNINGS_TREND' => 'Earnings Trend',
      'EARNINGS_HEALTH_AVG_WEEKLY_HRS' => 'Avg Weekly Hrs / Worker',
      'EARNINGS_MEMBER_EARNINGS_FOR_YEAR' => 'Member earnings for {year}',
      'BUSINESS_REPORTS_LOADING_ANALYTICS_YEAR' => 'Loading reports analytics for %s.',
    ];

    foreach (['de', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'] as $locale) {
      $content = (string) file_get_contents($projectRoot . '/strings/' . $locale . '.txt');

      foreach ($englishFallbacks as $key => $englishValue) {
        $pattern = '/^' . preg_quote($key, '/') . ' ' . preg_quote($englishValue, '/') . '$/m';
        $this->assertDoesNotMatchRegularExpression(
          $pattern,
          $content,
          sprintf('%s still uses the English fallback for %s', $locale, $key),
        );
      }
    }
  }

  #[Test]
  public function businessReportsCompositionKeysAreTranslatedInAllLocales(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $englishFallbacks = [
      'EARNINGS_COMPOSITION_PANEL_TITLE' => 'Earnings Composition',
      'EARNINGS_YTD_COMPOSITION' => 'YTD Composition',
      'EARNINGS_MONTHLY_COMPOSITION' => 'Monthly Composition',
      'EARNINGS_PIEGRAPHS_NO_VALUES' => 'No values available.',
    ];

    foreach (['de', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'] as $locale) {
      $content = (string) file_get_contents($projectRoot . '/strings/' . $locale . '.txt');

      foreach ($englishFallbacks as $key => $englishValue) {
        $pattern = '/^' . preg_quote($key, '/') . ' ' . preg_quote($englishValue, '/') . '$/m';
        $this->assertDoesNotMatchRegularExpression(
          $pattern,
          $content,
          sprintf('%s still uses the English fallback for %s', $locale, $key),
        );
      }
    }
  }

  #[Test]
  public function memberReportsPieGraphsUseSharedLocaleHelpers(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $memberReportsViewJs = (string) file_get_contents($projectRoot . '/html/js/earnings/member-reports-view.js');
    $pieGraphCoreJs = (string) file_get_contents($projectRoot . '/html/js/earnings/pie-graph-core.js');
    $coreLocaleJs = (string) file_get_contents($projectRoot . '/html/js/core/locale.js');
    $earningsJs = (string) file_get_contents($projectRoot . '/html/js/earnings/index.php');
    $earningsDomain = (string) file_get_contents($projectRoot . '/html/src/Domain/Earnings.php');
    $memberReportsService = (string) file_get_contents($projectRoot . '/html/src/Domain/BusinessMemberReportsService.php');
    $businessStateJs = (string) file_get_contents($projectRoot . '/html/js/business/core/state.js.php');

    $this->assertStringContainsString("from '/js/earnings/pie-graph-core.js'", $memberReportsViewJs);
    $this->assertStringContainsString('createPieGraphHelpers', $memberReportsViewJs);
    $this->assertStringContainsString('resolveUserLocale', $memberReportsViewJs);
    $this->assertStringContainsString("from '/js/core/locale.js'", $memberReportsViewJs);
    $this->assertStringContainsString("from '/js/core/locale.js'", $pieGraphCoreJs);
    $this->assertStringContainsString('export function resolveUserLocale', $coreLocaleJs);
    $this->assertStringNotContainsString('/js/earnings/locale.js', $memberReportsViewJs);
    $this->assertStringNotContainsString('/js/earnings/locale.js', $pieGraphCoreJs);
    $this->assertStringContainsString("getI18nLabel(config, 'GROSS'", $memberReportsViewJs);
    $this->assertStringContainsString("getI18nLabel(config, 'EARNINGS_PIEGRAPHS_NO_VALUES'", $memberReportsViewJs);
    $this->assertStringNotContainsString("label: 'Gross'", $memberReportsViewJs);
    $this->assertStringNotContainsString('.toFixed(2)', $memberReportsViewJs);

    $this->assertStringContainsString('Intl.NumberFormat', $pieGraphCoreJs);
    $this->assertStringContainsString("month: 'short'", $pieGraphCoreJs);

    $this->assertStringContainsString("Render::jsStaticURL('js/earnings/pie-graph-core.js')", $earningsJs);
    $this->assertStringContainsString("batchI18n('EARNINGS_COMPOSITION_PANEL_TITLE')", $earningsDomain);
    $this->assertStringContainsString("Strings::i18n('EARNINGS_COMPOSITION_PANEL_TITLE')", $memberReportsService);
    $this->assertStringContainsString("org_js_index_i18n('EARNINGS_PIEGRAPHS_NO_VALUES')", $businessStateJs);
  }

  #[Test]
  public function earningsTrendChartUsesLocalizedHoverAndAxisFormatting(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $trendChartJs = (string) file_get_contents($projectRoot . '/html/js/earnings/trend-chart.js');
    $formatJs = (string) file_get_contents($projectRoot . '/html/js/earnings/format.js');
    $earningsJs = (string) file_get_contents($projectRoot . '/html/js/earnings/index.php');
    $memberReportsViewJs = (string) file_get_contents($projectRoot . '/html/js/earnings/member-reports-view.js');

    $this->assertStringContainsString("from '/js/earnings/format.js'", $trendChartJs);
    $this->assertStringContainsString('createEarningsFormatHelpers', $trendChartJs);
    $this->assertStringContainsString('EARNINGS_TREND_HOVER_TOOLTIP', $trendChartJs);
    $this->assertStringContainsString('EARNINGS_TREND_Y_AXIS_LABEL', $trendChartJs);
    $this->assertStringContainsString('EARNINGS_TREND_DIRECTION_INCREASING', $trendChartJs);
    $this->assertStringNotContainsString('.toFixed(2)', $trendChartJs);
    $this->assertStringContainsString('Intl.NumberFormat', $formatJs);

    $this->assertStringContainsString('createEarningsFormatHelpers', $earningsJs);
    $this->assertStringContainsString('EARNINGS_TREND_HOVER_TOOLTIP', $earningsJs);
    $this->assertStringNotContainsString('minValue.toFixed(2)', $earningsJs);
    $this->assertStringNotContainsString('amountValue.toFixed(2)', $earningsJs);

    $this->assertStringContainsString('createEarningsFormatHelpers', $memberReportsViewJs);
    $this->assertStringContainsString('EARNINGS_DAILY_GRID_INSTRUCTIONS_FOR', $memberReportsViewJs);
    $this->assertStringContainsString("getI18nLabel(config, 'DATE'", $memberReportsViewJs);
    $this->assertStringNotContainsString('Daily earnings ${year}', $memberReportsViewJs);
  }

  #[Test]
  public function teamEarningsPanelChartHoverTitlesUseI18nKeys(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $teamPanel = (string) file_get_contents($projectRoot . '/html/reports/_partials/team_earnings_panel.php');
    $helpers = (string) file_get_contents($projectRoot . '/html/reports/_partials/team_earnings_helpers.php');

    $this->assertStringContainsString('earnings_chart_hover(', $teamPanel);
    $this->assertStringContainsString('EARNINGS_YTD_HOVER_GROSS', $teamPanel);
    $this->assertStringContainsString('EARNINGS_SITE_BAR_HOVER', $teamPanel);
    $this->assertStringContainsString('EARNINGS_COMPOSITION_SEGMENT_HOVER', $teamPanel);
    $this->assertStringNotContainsString("': Gross $'", $teamPanel);
    $this->assertStringNotContainsString("number_format(\$d['gross'], 2)", $teamPanel);

    $this->assertStringContainsString('Strings::formatLocalizedNumber', $helpers);
    $this->assertStringContainsString('earnings_chart_hover', $helpers);
  }

  #[Test]
  public function earningsMonthlyAndPayPeriodDatesUseLocalizedFormatters(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $memberReports = (string) file_get_contents($projectRoot . '/html/src/Domain/BusinessMemberReportsService.php');
    $earnings = (string) file_get_contents($projectRoot . '/html/src/Domain/Earnings.php');
    $strings = (string) file_get_contents($projectRoot . '/html/src/Domain/Strings.php');

    $this->assertStringContainsString('formatLocalizedShortMonth', $memberReports);
    $this->assertStringContainsString('formatLocalizedMediumDate', $memberReports);
    $this->assertStringContainsString('formatLocalizedShortMonth', $earnings);
    $this->assertStringContainsString('formatLocalizedMediumDate', $earnings);
    $this->assertStringNotContainsString("date('M'", $memberReports);
    $this->assertStringNotContainsString("->format('M j, Y')", $memberReports);
    $this->assertStringNotContainsString("date('M'", $earnings);
    $this->assertStringNotContainsString("->format('M j, Y')", $earnings);
    $this->assertStringContainsString('formatLocalizedShortMonth', $strings);
    $this->assertStringContainsString('formatLocalizedMediumDate', $strings);

    $monthlyHooks = (string) file_get_contents($projectRoot . '/html/extensions/overrides/earnings-monthly/hooks.php');
    $this->assertStringContainsString('Strings::formatLocalizedShortMonth', $monthlyHooks);
    $this->assertStringContainsString('Strings::formatLocalizedNumber', $monthlyHooks);
    $this->assertStringNotContainsString("date('M'", $monthlyHooks);
    $this->assertStringContainsString('EARNINGS_MONTHLY_GRID_ARIA_FOR', $memberReports);
    $this->assertStringContainsString('new DataGrid', $memberReports);
    $this->assertStringContainsString('member-reports-monthly-', $memberReports);
  }

  #[Test]
  public function memberReportsMonthlyGridKeysAreTranslatedInAllLocales(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $englishFallbacks = [
      'EARNINGS_MONTH' => 'Month',
      'EARNINGS_MONTHLY_ARIA_PREFIX' => 'Monthly earnings',
      'EARNINGS_MONTHLY_GRID_ARIA_FOR' => 'Monthly earnings grid for {year}',
      'EARNINGS_MONTHLY_EXPORT_FORMATS' => 'Monthly export formats',
      'EARNINGS_TOTAL_DEDUCTIONS' => 'Total Deductions',
    ];

    foreach (['de', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'] as $locale) {
      $content = (string) file_get_contents($projectRoot . '/strings/' . $locale . '.txt');

      foreach ($englishFallbacks as $key => $englishValue) {
        $pattern = '/^' . preg_quote($key, '/') . ' ' . preg_quote($englishValue, '/') . '$/m';
        $this->assertDoesNotMatchRegularExpression(
          $pattern,
          $content,
          sprintf('%s still uses the English fallback for %s', $locale, $key),
        );
      }
    }
  }

  #[Test]
  public function earningsChartHoverKeysAreTranslatedInAllLocales(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $englishFallbacks = [
      'EARNINGS_YTD_HOVER_GROSS' => '{label}: Gross {amount}',
      'EARNINGS_YTD_HOVER_ACTIVE_MEMBERS' => '{label}: {count} active members',
      'EARNINGS_SITE_BAR_HOVER' => '{site}: {amount} | {count} {memberLabel} | {rate}/hr | {otPct}% OT',
      'EARNINGS_TREND_DIRECTION_INCREASING' => 'increasing',
      'EARNINGS_TREND_DIRECTION_DECREASING' => 'decreasing',
      'EARNINGS_TREND_DIRECTION_FLAT' => 'flat',
    ];

    foreach (['de', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'] as $locale) {
      $content = (string) file_get_contents($projectRoot . '/strings/' . $locale . '.txt');

      foreach ($englishFallbacks as $key => $englishValue) {
        $pattern = '/^' . preg_quote($key, '/') . ' ' . preg_quote($englishValue, '/') . '$/m';
        $this->assertDoesNotMatchRegularExpression(
          $pattern,
          $content,
          sprintf('%s still uses the English fallback for %s', $locale, $key),
        );
      }
    }
  }

  #[Test]
  public function memberReportsForecastPanelUsesI18nKeys(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $earnings = (string) file_get_contents($projectRoot . '/html/src/Domain/Earnings.php');
    $memberReportsService = (string) file_get_contents($projectRoot . '/html/src/Domain/BusinessMemberReportsService.php');
    $memberReportsViewJs = (string) file_get_contents($projectRoot . '/html/js/earnings/member-reports-view.js');
    $forecastCalculatorJs = (string) file_get_contents($projectRoot . '/html/js/earnings/forecast-calculator.js');
    $businessStateJs = (string) file_get_contents($projectRoot . '/html/js/business/core/state.js.php');
    $forecastRenderer = (string) file_get_contents($projectRoot . '/html/src/Domain/ForecastWorkspaceRenderer.php');

    $this->assertStringContainsString('ForecastWorkspaceRenderer::renderShell', $earnings);
    $this->assertStringContainsString('buildForecastStateForUser', $earnings);
    $this->assertStringContainsString("Strings::i18n('EARNINGS_FORECAST')", $memberReportsService);
    $this->assertStringContainsString('ForecastWorkspaceRenderer::renderShell', $memberReportsService);
    $this->assertStringNotContainsString('>Future Net Pay<', $memberReportsService);

    $this->assertStringContainsString("getI18nLabel(config, 'EARNINGS_FORECAST_NO_DATA'", $memberReportsViewJs);
    $this->assertStringContainsString('initForecastWorkspace', $memberReportsViewJs);
    $this->assertStringContainsString("getI18nLabel(config, 'EARNINGS_FORECAST_CALC_TITLE'", $forecastCalculatorJs);
    $this->assertStringContainsString('aria-live="polite"', $forecastCalculatorJs);
    $this->assertStringNotContainsString('style=', $forecastCalculatorJs);

    $this->assertStringContainsString('EARNINGS_FORECAST_TITLE', $businessStateJs);
    $this->assertStringContainsString('EARNINGS_FORECAST_TITLE', $forecastRenderer);
    $this->assertStringNotContainsString('CRA-authoritative payroll calculations.</p>', $earnings);
  }

  #[Test]
  public function memberReportsForecastKeysAreTranslatedInAllLocales(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $englishFallbacks = [
      'EARNINGS_FORECAST' => 'Forecast',
      'EARNINGS_FORECAST_TITLE' => 'Personal Earnings Forecast',
      'EARNINGS_FORECAST_BADGE_ESTIMATE' => 'Estimate only',
      'EARNINGS_FORECAST_BADGE_NOT_CRA' => 'Not CRA-authoritative',
      'EARNINGS_FORECAST_WORKSPACE_ARIA' => 'Personal earnings forecast workspace',
      'EARNINGS_FORECAST_LOADING' => 'Loading forecast…',
      'EARNINGS_FORECAST_TIMEFRAME' => 'Timeframe',
      'EARNINGS_FORECAST_EST_GROSS' => 'Est. Gross',
      'EARNINGS_FORECAST_EST_TAX' => 'Est. Tax',
      'EARNINGS_FORECAST_EST_NET' => 'Est. Net',
      'EARNINGS_FORECAST_NEXT_PAYCHECK' => 'Next Paycheck',
      'EARNINGS_FORECAST_NEXT_30_DAYS' => 'Next 30 Days',
      'EARNINGS_FORECAST_YTD_PROJECTION' => 'YTD Projection',
      'EARNINGS_FORECAST_YEAR_PROJECTION' => 'Year Projection',
      'EARNINGS_FORECAST_SCENARIO_CONSERVATIVE' => 'Conservative',
      'EARNINGS_FORECAST_SCENARIO_NORMAL' => 'Normal',
      'EARNINGS_FORECAST_SCENARIO_OVERTIME' => 'Overtime',
      'EARNINGS_FORECAST_SCENARIO_CUSTOM' => 'Custom',
      'EARNINGS_FORECAST_CALC_TITLE' => 'Forecast Calculator',
      'EARNINGS_FORECAST_RESET_PROFILE' => 'Reset to Profile',
      'EARNINGS_FORECAST_RESET_SCHEDULED' => 'Reset to Scheduled Work',
      'EARNINGS_FORECAST_DISCLAIMER' => 'Figures are projections based on your profile rate, province, and a standard 5-day work schedule. Actual deductions depend on YTD income, benefit elections, and other factors. Not CRA-authoritative.',
      'EARNINGS_FORECAST_SETUP_NOTICE' => 'Set your hourly rate in {link} to see your earnings forecast.',
      'EARNINGS_FORECAST_SETUP_LINK' => 'Profile → Pay Period',
      'EARNINGS_FORECAST_GRID_ARIA' => 'Future earnings forecast',
      'EARNINGS_FORECAST_INTRO' => 'Projected earnings based on the member\'s profile settings. All figures are ESTIMATES only — not CRA-authoritative payroll calculations.',
      'EARNINGS_FORECAST_INTRO_SELF' => 'Projected earnings based on your current profile settings. All figures are ESTIMATES only — not CRA-authoritative payroll calculations.',
      'EARNINGS_FORECAST_NO_DATA' => 'No forecast available.',
      'EARNINGS_FORECAST_LOAD_FAILED' => 'Unable to load forecast.',
      'EARNINGS_FORECAST_PREVIEW_FAILED' => 'Unable to calculate forecast preview.',
      'EARNINGS_MONTH' => 'Month',
    ];

    foreach (['de', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'] as $locale) {
      $content = (string) file_get_contents($projectRoot . '/strings/' . $locale . '.txt');

      foreach ($englishFallbacks as $key => $englishValue) {
        $pattern = '/^' . preg_quote($key, '/') . ' ' . preg_quote($englishValue, '/') . '$/m';
        $this->assertDoesNotMatchRegularExpression(
          $pattern,
          $content,
          sprintf('%s still uses the English fallback for %s', $locale, $key),
        );
      }
    }
  }

  #[Test]
  public function businessJsApiHelpersLiveInSharedCoreModule(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $workspaceJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $apiCoreJs = (string) file_get_contents($projectRoot . '/html/js/business/core/api.js.php');
    $businessRouterJs = (string) file_get_contents($projectRoot . '/html/js/business/index.php');
    $profileRouterJs = (string) file_get_contents($projectRoot . '/html/js/business-profile/index.php');

    $this->assertStringContainsString('const apiRequest = async', $apiCoreJs);
    $this->assertStringContainsString('const postForm = async', $apiCoreJs);
    $this->assertStringContainsString('const postJsonRaw = async', $apiCoreJs);
    $this->assertStringContainsString("document.getElementById('businesses_csrf_token')", $apiCoreJs);
    $this->assertStringContainsString("'X-CSRF-Token': csrfToken", $apiCoreJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/core/api.js.php'", $businessRouterJs);
    $this->assertStringContainsString("require __DIR__ . '/../business/core/api.js.php'", $profileRouterJs);
    $this->assertStringNotContainsString('const apiRequest = async', $workspaceJs);
    $this->assertStringNotContainsString('const postForm = async', $workspaceJs);
    $this->assertStringNotContainsString('const getCsrfToken =', $workspaceJs);
  }

  #[Test]
  public function businessGridControllerLivesInSharedCoreModule(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $workspaceJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $businessGridJs = (string) file_get_contents($projectRoot . '/html/js/business/core/business-grid.js.php');
    $businessRouterJs = (string) file_get_contents($projectRoot . '/html/js/business/index.php');
    $profileRouterJs = (string) file_get_contents($projectRoot . '/html/js/business-profile/index.php');

    $this->assertStringContainsString('const loadGrid = async', $businessGridJs);
    $this->assertStringContainsString('const loadBusinesses = async', $businessGridJs);
    $this->assertStringContainsString('const startBusinessNotificationPolling =', $businessGridJs);
    $this->assertStringContainsString('const markBusinessNotificationsRead = async', $businessGridJs);
    $this->assertStringContainsString("const legacyWsHttpBase = '/ws/';", $businessGridJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/core/business-grid.js.php'", $businessRouterJs);
    $this->assertStringContainsString("require __DIR__ . '/../business/core/business-grid.js.php'", $profileRouterJs);
    $this->assertStringNotContainsString('const loadGrid = async', $workspaceJs);
    $this->assertStringNotContainsString('const loadBusinesses = async', $workspaceJs);
    $this->assertStringNotContainsString('const startBusinessNotificationPolling =', $workspaceJs);
    $this->assertStringNotContainsString('const markBusinessNotificationsRead = async', $workspaceJs);
  }

  #[Test]
  public function businessAccessLookupHelpersLiveInSharedCoreModule(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $workspaceJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $accessLookupJs = (string) file_get_contents($projectRoot . '/html/js/business/core/access-lookup.js.php');
    $businessBrowserJs = (string) file_get_contents($projectRoot . '/html/js/business/core/business-browser.js.php');
    $businessRouterJs = (string) file_get_contents($projectRoot . '/html/js/business/index.php');
    $profileRouterJs = (string) file_get_contents($projectRoot . '/html/js/business-profile/index.php');

    $this->assertStringContainsString('const extractLookupEmail =', $accessLookupJs);
    $this->assertStringContainsString('const fetchAccessLookupSuggestions = async', $accessLookupJs);
    $this->assertStringContainsString('const bindAccessLookupInput =', $accessLookupJs);
    $this->assertStringContainsString('/api/v1/businesses/access/search', $accessLookupJs);
    $this->assertStringContainsString('fetchAccessLookupSuggestions(trimmed)', $businessBrowserJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/core/access-lookup.js.php'", $businessRouterJs);
    $this->assertStringContainsString("require __DIR__ . '/../business/core/access-lookup.js.php'", $profileRouterJs);
    $this->assertStringNotContainsString('const extractLookupEmail =', $workspaceJs);
    $this->assertStringNotContainsString('const fetchAccessLookupSuggestions = async', $workspaceJs);
    $this->assertStringNotContainsString('const bindAccessLookupInput =', $workspaceJs);
  }

  #[Test]
  public function currencyAndTimezoneSearchPickerLivesInSharedCoreModule(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $workspaceJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $searchablePickerJs = (string) file_get_contents($projectRoot . '/html/js/business/core/searchable-picker.js.php');
    $businessRouterJs = (string) file_get_contents($projectRoot . '/html/js/business/index.php');
    $profileRouterJs = (string) file_get_contents($projectRoot . '/html/js/business-profile/index.php');

    $this->assertStringContainsString('const initSearchListbox =', $searchablePickerJs);
    $this->assertStringContainsString('const initCurrencyFinder =', $searchablePickerJs);
    $this->assertStringContainsString('const initTimezoneFinder =', $searchablePickerJs);
    $this->assertStringContainsString('displayCurrencyValue', $searchablePickerJs);
    $this->assertStringContainsString('displayTimezoneValue', $searchablePickerJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/core/searchable-picker.js.php'", $businessRouterJs);
    $this->assertStringContainsString("require __DIR__ . '/../business/core/searchable-picker.js.php'", $profileRouterJs);
    $this->assertStringNotContainsString('const initSearchListbox =', $workspaceJs);
    $this->assertStringNotContainsString('const initCurrencyFinder =', $workspaceJs);
    $this->assertStringNotContainsString('const initTimezoneFinder =', $workspaceJs);
  }

  #[Test]
  public function businessReportExportUtilitiesLiveInSharedCoreModule(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $reportsSubpageJs = (string) file_get_contents($projectRoot . '/html/js/business/subpages/reports.js.php');
    $reportExportUtilsJs = (string) file_get_contents($projectRoot . '/html/js/business/core/report-export-utils.js.php');
    $businessRouterJs = (string) file_get_contents($projectRoot . '/html/js/business/index.php');

    $this->assertStringContainsString('const buildPayrollPackageCsv =', $reportExportUtilsJs);
    $this->assertStringContainsString('const buildPayrollSiteSummaryCsv =', $reportExportUtilsJs);
    $this->assertStringContainsString('const buildPayrollExceptionsCsv =', $reportExportUtilsJs);
    $this->assertStringContainsString('const buildPayrollPackageManifestCsv =', $reportExportUtilsJs);
    $this->assertStringContainsString('const downloadPayrollPackageZip = async', $reportExportUtilsJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/core/report-export-utils.js.php'", $businessRouterJs);
    $this->assertStringNotContainsString('const buildPayrollPackageCsv =', $reportsSubpageJs);
    $this->assertStringNotContainsString('const buildPayrollSiteSummaryCsv =', $reportsSubpageJs);
    $this->assertStringNotContainsString('const buildPayrollPackageManifestCsv =', $reportsSubpageJs);
  }

  #[Test]
  public function businessMembershipConsentDialogLivesInSharedCoreModule(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $workspaceJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $membershipConsentJs = (string) file_get_contents($projectRoot . '/html/js/business/core/membership-consent.js.php');
    $accessManagementJs = (string) file_get_contents($projectRoot . '/html/js/business/core/access-management.js.php');
    $businessRouterJs = (string) file_get_contents($projectRoot . '/html/js/business/index.php');
    $profileRouterJs = (string) file_get_contents($projectRoot . '/html/js/business-profile/index.php');

    $this->assertStringContainsString('const closeMembershipConsentDialog =', $membershipConsentJs);
    $this->assertStringContainsString('const promptMembershipConsent = async', $membershipConsentJs);
    $this->assertStringContainsString('elements.membershipConsentDialog.showModal()', $membershipConsentJs);
    $this->assertStringContainsString("promptMembershipConsent('Approve access request')", $accessManagementJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/core/membership-consent.js.php'", $businessRouterJs);
    $this->assertStringContainsString("require __DIR__ . '/../business/core/membership-consent.js.php'", $profileRouterJs);
    $this->assertStringNotContainsString('const closeMembershipConsentDialog =', $workspaceJs);
    $this->assertStringNotContainsString('const promptMembershipConsent = async', $workspaceJs);
  }

  #[Test]
  public function businessDisplayUtilitiesLiveInSharedCoreModule(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $workspaceJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $displayUtilsJs = (string) file_get_contents($projectRoot . '/html/js/business/core/display-utils.js.php');
    $businessRouterJs = (string) file_get_contents($projectRoot . '/html/js/business/index.php');
    $profileRouterJs = (string) file_get_contents($projectRoot . '/html/js/business-profile/index.php');

    $this->assertStringContainsString('const normalizeBusinessScopeTokens =', $displayUtilsJs);
    $this->assertStringContainsString('const formatBusinessConnectionDate =', $displayUtilsJs);
    $this->assertStringContainsString('const formatBusinessRoleLabel =', $displayUtilsJs);
    $this->assertStringContainsString('const setStackMessage =', $displayUtilsJs);
    $this->assertStringContainsString('const setDatagridMessage =', $displayUtilsJs);
    $this->assertStringContainsString('const setDiscoveryPanelStatus =', $displayUtilsJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/core/display-utils.js.php'", $businessRouterJs);
    $this->assertStringContainsString("require __DIR__ . '/../business/core/display-utils.js.php'", $profileRouterJs);
    $this->assertStringNotContainsString('const normalizeBusinessScopeTokens =', $workspaceJs);
    $this->assertStringNotContainsString('const setDatagridMessage =', $workspaceJs);
  }

  #[Test]
  public function businessTimestampPopoversLiveInSharedCoreModule(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $workspaceJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $timestampPopoversJs = (string) file_get_contents($projectRoot . '/html/js/business/core/timestamp-popovers.js.php');
    $businessRouterJs = (string) file_get_contents($projectRoot . '/html/js/business/index.php');
    $profileRouterJs = (string) file_get_contents($projectRoot . '/html/js/business-profile/index.php');

    $this->assertStringContainsString('const formatInviteTimestamp =', $timestampPopoversJs);
    $this->assertStringContainsString('const formatTimestampInTimeZone =', $timestampPopoversJs);
    $this->assertStringContainsString('const historyTimestampPopoverController = createAnchoredPopoverController', $timestampPopoversJs);
    $this->assertStringContainsString('const enhanceInviteHistoryTimestampCells =', $timestampPopoversJs);
    $this->assertStringContainsString('const enhanceMembersJoinedTimestampCells =', $timestampPopoversJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/core/timestamp-popovers.js.php'", $businessRouterJs);
    $this->assertStringContainsString("require __DIR__ . '/../business/core/timestamp-popovers.js.php'", $profileRouterJs);
    $this->assertStringNotContainsString('const formatInviteTimestamp =', $workspaceJs);
    $this->assertStringNotContainsString('const enhanceMembersJoinedTimestampCells =', $workspaceJs);
  }

  #[Test]
  public function businessPayPeriodPreviewRendererLivesInSharedCoreModule(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $workspaceJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $personalSettingsJs = (string) file_get_contents($projectRoot . '/html/js/business/core/personal-settings.js.php');
    $uiHelpersJs = (string) file_get_contents($projectRoot . '/html/js/business/core/ui-helpers.js.php');
    $payPeriodPreviewJs = (string) file_get_contents($projectRoot . '/html/js/core/pay-period-preview.js');
    $businessRouterJs = (string) file_get_contents($projectRoot . '/html/js/business/index.php');
    $profileRouterJs = (string) file_get_contents($projectRoot . '/html/js/business-profile/index.php');

    $this->assertStringContainsString('export function buildPayPeriodCurrentRange', $payPeriodPreviewJs);
    $this->assertStringContainsString('export function buildPayPeriodPreviewState', $payPeriodPreviewJs);
    $this->assertStringContainsString('export function resolvePayPeriodPreviewSelection', $payPeriodPreviewJs);
    $this->assertStringContainsString('export function buildPayPeriodRibbonCalendar', $payPeriodPreviewJs);
    $this->assertStringContainsString('alignBiweeklyToAnchor: true', $personalSettingsJs);
    $this->assertStringContainsString('alignBiweeklyToAnchor: false', $workspaceJs);
    $this->assertStringContainsString('includeSummary: true', $personalSettingsJs);
    $this->assertStringContainsString("headerMode: 'stripbar'", $workspaceJs);
    $this->assertStringContainsString("Render::jsStaticURL('js/core/pay-period-preview.js')", $businessRouterJs);
    $this->assertStringContainsString("Render::jsStaticURL('js/core/pay-period-preview.js')", $profileRouterJs);
    $this->assertStringNotContainsString("Javascript::emitJsSegment(__DIR__ . '/core/pay-period-preview.js.php'", $businessRouterJs);
    $this->assertStringNotContainsString("require __DIR__ . '/../business/core/pay-period-preview.js.php'", $profileRouterJs);
    $this->assertStringNotContainsString('const buildPayPeriodRibbonCalendar =', $uiHelpersJs);
    $this->assertStringNotContainsString('const currentPeriod =', $workspaceJs);
  }

  #[Test]
  public function businessPermissionAndGatingHelpersLiveInSharedCoreModule(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $workspaceJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $permissionsJs = (string) file_get_contents($projectRoot . '/html/js/business/core/business-permissions.js.php');
    $businessRouterJs = (string) file_get_contents($projectRoot . '/html/js/business/index.php');
    $profileRouterJs = (string) file_get_contents($projectRoot . '/html/js/business-profile/index.php');

    $this->assertStringContainsString('const canUsePremiumOrgFeatures =', $permissionsJs);
    $this->assertStringContainsString('const canWriteBusinessSites =', $permissionsJs);
    $this->assertStringContainsString('const canManageBusinessAccess =', $permissionsJs);
    $this->assertStringContainsString('const canGenerateAuditControlTest =', $permissionsJs);
    $this->assertStringContainsString('const showAccessManagementDeniedWarning =', $permissionsJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/core/business-permissions.js.php'", $businessRouterJs);
    $this->assertStringContainsString("require __DIR__ . '/../business/core/business-permissions.js.php'", $profileRouterJs);
    $this->assertStringNotContainsString('const canUsePremiumOrgFeatures =', $workspaceJs);
    $this->assertStringNotContainsString('const canManageBusinessAccess =', $workspaceJs);
  }

  #[Test]
  public function personConnectionControllerLivesInSharedCoreModule(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $workspaceJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $currentBusinessPanelJs = (string) file_get_contents($projectRoot . '/html/js/business/core/current-business-panel.js.php');
    $personConnectionsJs = (string) file_get_contents($projectRoot . '/html/js/business/core/person-connections.js.php');
    $businessRouterJs = (string) file_get_contents($projectRoot . '/html/js/business/index.php');
    $profileRouterJs = (string) file_get_contents($projectRoot . '/html/js/business-profile/index.php');

    $this->assertStringContainsString('const renderCurrentBusinessPanel =', $currentBusinessPanelJs);
    $this->assertStringContainsString('const openCurrentBusinessDetailsDialog =', $currentBusinessPanelJs);
    $this->assertStringContainsString('const handleRevokeCurrentBusinessAccess = async', $currentBusinessPanelJs);
    $this->assertStringContainsString('const formatConnectionStatusLabel =', $currentBusinessPanelJs);
    $this->assertStringContainsString('const loadPersonConnections = async', $personConnectionsJs);
    $this->assertStringContainsString('const handlePersonConnectionRequest = async', $personConnectionsJs);
    $this->assertStringContainsString('const handlePersonConnectionAction = async', $personConnectionsJs);
    $this->assertStringContainsString('formatConnectionStatusLabel(status || T.unknown)', $personConnectionsJs);
    $this->assertStringContainsString('/api/v1/connections/people', $personConnectionsJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/core/current-business-panel.js.php'", $businessRouterJs);
    $this->assertStringContainsString("Javascript::emitJsSegment(__DIR__ . '/core/person-connections.js.php'", $businessRouterJs);
    $this->assertStringContainsString("require __DIR__ . '/../business/core/current-business-panel.js.php'", $profileRouterJs);
    $this->assertStringContainsString("require __DIR__ . '/../business/core/person-connections.js.php'", $profileRouterJs);
    $this->assertStringNotContainsString('const renderCurrentBusinessPanel =', $workspaceJs);
    $this->assertStringNotContainsString('const openCurrentBusinessDetailsDialog =', $workspaceJs);
    $this->assertStringNotContainsString('const handleRevokeCurrentBusinessAccess = async', $workspaceJs);
    $this->assertStringNotContainsString('const loadPersonConnections = async', $workspaceJs);
    $this->assertStringNotContainsString('const handlePersonConnectionRequest = async', $workspaceJs);
    $this->assertStringNotContainsString('const handlePersonConnectionAction = async', $workspaceJs);
  }

  #[Test]
  public function highTrafficJsModulesUseInjectedI18nNotHardcodedUserCopy(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $settingsJs = (string) file_get_contents($projectRoot . '/html/js/settings/index.php');
    $signinJs = (string) file_get_contents($projectRoot . '/html/js/signin/index.php');
    $workspaceJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $profileAccountJs = (string) file_get_contents($projectRoot . '/html/js/business/core/profile-account.js.php');
    $stateJs = (string) file_get_contents($projectRoot . '/html/js/business/core/state.js.php');
    $memberReportsJs = (string) file_get_contents($projectRoot . '/html/js/earnings/member-reports-view.js');

    $this->assertStringContainsString('const SETTINGS_T =', $settingsJs);
    $this->assertStringContainsString('SETTINGS_T.SETTINGS_JS_PASSKEYS_NONE', $settingsJs);
    $this->assertStringNotContainsString("'No passkeys registered yet.'", $settingsJs);

    $this->assertStringContainsString('const AUTH_T =', $signinJs);
    $this->assertStringContainsString('AUTH_T.AUTH_SIGNIN_PASSKEY_STATUS', $signinJs);
    $this->assertStringNotContainsString("'Sign-in failed. Try again.'", $signinJs);

    $this->assertStringContainsString('T.deleteAccountTypePhrase', $profileAccountJs);
    $this->assertStringContainsString('SETTINGS_JS_DELETE_ACCOUNT_TYPE_PHRASE', $stateJs);
    $this->assertStringNotContainsString("'Type DELETE MY ACCOUNT exactly to confirm account deletion.'", $profileAccountJs);

    $this->assertStringContainsString("formatI18n(config, 'EARNINGS_DAILY_LOAD_FAILED_FMT'", $memberReportsJs);
    $this->assertStringContainsString('EARNINGS_DAILY_LOAD_FAILED_FMT', $stateJs);
  }

  #[Test]
  public function businessSitesAndGroupsUseMobileCardDataGrids(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $businessCss = (string) file_get_contents($projectRoot . '/html/css/business/index.php');
    $dataGrid = (string) file_get_contents($projectRoot . '/html/src/Domain/DataGrid.php');
    $sitesRenderer = (string) file_get_contents($projectRoot . '/html/src/Domain/BusinessSitesGridRenderer.php');
    $groupsRenderer = (string) file_get_contents($projectRoot . '/html/src/Domain/BusinessGroupsGridRenderer.php');

    $this->assertStringContainsString('Business workspace mobile layouts (<= 768px)', $businessCss);
    $this->assertStringContainsString('public function setClass(string $class): void', $dataGrid);
    $this->assertStringContainsString('sanitizeClassList', $dataGrid);
    $this->assertStringContainsString("setClass('datagrid_mobile_cards business_sites_mobile_cards')", $sitesRenderer);
    $this->assertStringContainsString("setClass('datagrid_mobile_cards business_groups_mobile_cards')", $groupsRenderer);
    $this->assertStringContainsString('.business_sites_mobile_cards .datagrid_header_row', $businessCss);
    $this->assertStringContainsString('.business_groups_mobile_cards .datagrid_header_row', $businessCss);
    $this->assertStringNotContainsString('[data-grid^="business-sites-"] .datagrid_header_row', $businessCss);
    $this->assertStringNotContainsString('[data-grid^="business-groups-"] .datagrid_header_row', $businessCss);
    $this->assertStringContainsString('"site site"', $businessCss);
    $this->assertStringContainsString('"entries gross"', $businessCss);
    $this->assertStringContainsString('"budget used"', $businessCss);
    $this->assertStringContainsString('"name name"', $businessCss);
    $this->assertStringContainsString('"members sites"', $businessCss);
    $this->assertStringContainsString('"hours gross"', $businessCss);
    $this->assertStringContainsString('.business_sites_mobile_cards .datagrid_col_entries::before', $businessCss);
    $this->assertStringContainsString('.business_sites_mobile_cards .datagrid_col_budget_used::before', $businessCss);
    $this->assertStringContainsString('.business_groups_mobile_cards .datagrid_col_member_count::before', $businessCss);
    $this->assertStringContainsString('.business_groups_mobile_cards .datagrid_col_updated_at::before', $businessCss);
    $this->assertStringContainsString('.business_sites_mobile_cards .datagrid_col_entries:not(.datagrid_col_hidden)', $businessCss);
    $this->assertStringContainsString('.business_groups_mobile_cards .datagrid_col_member_count:not(.datagrid_col_hidden)', $businessCss);
  }
}
