<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

/**
 * Business Dashboard — read-only executive home (Phase 2).
 */
$currentPage = 'PAGE_BUSINESS_DASHBOARD';

require_once '../config.php';
require __DIR__ . '/_partials/i18n.php';

Authentication::redirectHomeIfUnauthenticated();
BusinessNav::requirePremiumAccess();

require __DIR__ . '/_partials/vars.php';

$userUUID = User::currentUUID();

Lens::boot('business');

$pageTitle = businesses_index_i18n('BUSINESSES') . ' - [' . businesses_index_i18n('SITE_NAME') . ']';
$pageLabel = businesses_index_i18n('BUSINESSES');
$pageLanguage = (string) User::current()->language;

$dashboardMetrics = BusinessDashboardMetrics::emptyMetrics();
$canViewAccessMetrics = false;

if ($workspaceBusinessId !== '') {
  $workspaceRole = is_array($workspaceBusiness)
    ? strtolower(trim((string) ($workspaceBusiness['role'] ?? '')))
    : '';
  $canViewAccessMetrics = User::isAdmin()
    || in_array($workspaceRole, ['owner', 'coordinator'], true);

  Lens::timeStart('Business Dashboard: page bootstrap');
  $dashboardMetrics = BusinessDashboardMetrics::forBusiness($workspaceBusinessId, $canViewAccessMetrics);
  Lens::timeEnd('Business Dashboard: page bootstrap');
}

if (InputSanitizer::getString('lens') === '1') {
  $businessIds = Database::smembers(\PayCal\Domain\Constants\Keys::BUSINESS_USER . ':' . $userUUID);

  Lens::add('Businesses Backend Snapshot', [
    'page' => $currentPage,
    'business_count' => count($businessIds),
    'is_admin' => User::isAdmin(),
    'is_manager' => User::isManager(),
    'dashboard_metrics' => $dashboardMetrics,
  ]);
}

require_once \PayCal\Domain\Config\Environment::appHome().'html/header.php';
require __DIR__ . '/_context_header.php';

if ($workspaceBusinessId !== '' && $currentPage === 'PAGE_BUSINESS_DASHBOARD') {
  BusinessWorkspaceWarmer::requestWarm($workspaceBusinessId, $userUUID);
}

?>

<div id="business-workspace" class="business_workspace business_dashboard" data-business-subpage="dashboard"<?php echo $workspaceBusinessIdAttr; ?>>

  <h1 class="visually_hidden"><?php echo businesses_index_i18n_html('BUSINESS_NAV_DASHBOARD'); ?></h1>

<?php if ($workspaceBusinessId !== '') {
  require __DIR__ . '/_partials/dashboard_metrics_panel.php';
} ?>

</div>

<?php
require __DIR__ . '/_partials/footer_shared.php';
require_once \PayCal\Domain\Config\Environment::appHome().'html/footer.php';
