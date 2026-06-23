<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

$currentPage = 'PAGE_BUSINESS_SITES';

require_once dirname(__DIR__) . '/_layout.php';

$isLensMode = InputSanitizer::getString('lens') === '1';

Lens::boot('business-sites');
Lens::timeStart('Business Sites: SSR grid render');

$sitesGridRenderer = new BusinessSitesGridRenderer();
$businessSitesGridBodyHtml = $sitesGridRenderer->loadingSkeleton();
$businessSitesGridStatusMessage = businesses_index_i18n('BUSINESSES_LOADING');
$sitesGridSiteCount = 0;
$sitesGridRenderSuccess = false;

if ($workspaceBusinessId !== '') {
  $sitesGridResult = $sitesGridRenderer->renderForBusiness($userUUID, $workspaceBusinessId, [
    'status' => 'active',
  ]);
  $businessSitesGridBodyHtml = (string) ($sitesGridResult['html'] ?? $sitesGridRenderer->loadingSkeleton());
  $sitesGridRenderSuccess = (bool) ($sitesGridResult['success'] ?? false);
  if ($sitesGridRenderSuccess) {
    $sitesGridSiteCount = (int) ($sitesGridResult['site_count'] ?? 0);
    $businessSitesGridStatusMessage = sprintf(
      businesses_index_i18n('BUSINESS_SITES_GRID_LOADED'),
      $sitesGridSiteCount,
      $sitesGridSiteCount === 1 ? '' : 's',
    );
  } else {
    $businessSitesGridStatusMessage = (string) ($sitesGridResult['message'] ?? businesses_index_i18n('BUSINESS_SITES_LOAD_FAILED'));
  }
} else {
  $businessSitesGridBodyHtml = $sitesGridRenderer->emptyMessage(businesses_index_i18n('BUSINESSES_SELECT_FIRST'));
  $businessSitesGridStatusMessage = businesses_index_i18n('BUSINESSES_SELECT_FIRST');
}

Lens::timeEnd('Business Sites: SSR grid render');

$businessSitesLensSnapshot = [
  'is_lens_mode' => $isLensMode,
  'workspace_business_id' => $workspaceBusinessId,
  'site_count' => $sitesGridSiteCount,
  'ssr_grid_render_success' => $sitesGridRenderSuccess,
  'ssr_status_message' => $businessSitesGridStatusMessage,
  'ssr_grid_html_length' => strlen($businessSitesGridBodyHtml),
];

Lens::add('Business Sites: page snapshot', $businessSitesLensSnapshot);

if ($isLensMode) {
  Lens::add('Business Sites: SSR grid body length', strlen($businessSitesGridBodyHtml));
}
?>

<div id="business-workspace" class="business_workspace business_sites" data-business-subpage="sites"<?php echo $workspaceBusinessIdAttr; ?> data-lens-mode="<?php echo $isLensMode ? '1' : '0'; ?>"<?php echo Lens::workspaceLensDataAttributes('business/sites', $businessSitesLensSnapshot, ['fetch_url_pattern' => '/sites/grid']); ?>>

  <h1 class="visually_hidden"><?php echo Strings::i18n('BUSINESS_NAV_SITES'); ?></h1>

<?php require __DIR__ . '/../_partials/business_sites_assigned_panel.php'; ?>

  <div class="business_sites_panels">
<?php
require __DIR__ . '/../_partials/editor_sites_discovery_panel.php';
?>
  </div>

</div>

<?php
$siteEditorContext = 'business';
require dirname(__DIR__) . '/../sites/_partials/site_editor_dialogs.php';
?>

<?php
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('sites') . '">' . PHP_EOL;

Lens::renderPageConsoleDebug('business/sites', $businessSitesLensSnapshot);
Lens::renderPagePerformanceBoot('business/sites', [
  'fetch_url_pattern' => '/sites/grid',
]);
require __DIR__ . '/../_partials/footer_shared.php';
require_once \PayCal\Domain\Config\Environment::appHome() . 'html/footer.php';
