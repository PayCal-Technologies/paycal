<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

$currentPage = 'PAGE_BUSINESS_DETAILS';

require_once dirname(__DIR__) . '/_layout.php';

$isLensMode = InputSanitizer::getString('lens') === '1';

Lens::boot('business-details');
Lens::timeStart('Business Details: page shell');
Lens::timeEnd('Business Details: page shell');

$businessDetailsLensSnapshot = [
  'is_lens_mode' => $isLensMode,
  'workspace_business_id' => $workspaceBusinessId,
  'workspace_business_id_present' => $workspaceBusinessId !== '',
  'has_active_business_subscription' => $hasActiveBusinessSubscription,
  'has_active_premium_subscription' => $hasActivePremiumSubscription,
  'is_free_profile' => $isFreeProfile,
  'subpage' => 'details',
  'business_panel' => 'business_details_panel',
  'autosave_surface' => 'business_details',
];

Lens::add('Business Details: page snapshot', $businessDetailsLensSnapshot);

if ($workspaceBusinessId === '') {
  Lens::increment('business_details_missing_workspace_business_id');
}
?>

<div id="business-workspace" class="business_workspace business_details" data-business-subpage="details"<?php echo $workspaceBusinessIdAttr; ?> data-lens-mode="<?php echo $isLensMode ? '1' : '0'; ?>"<?php echo Lens::workspaceLensDataAttributes('business/details', $businessDetailsLensSnapshot, ['autosave_probe' => true]); ?>>

  <h1 class="visually_hidden"><?php echo Strings::i18n('BUSINESS_NAV_DETAILS'); ?></h1>

  <input type="hidden" id="businesses_editor_business_id" value="<?php echo htmlspecialchars($workspaceBusinessId, ENT_QUOTES, 'UTF-8'); ?>">

<?php require __DIR__ . '/../_partials/business_details_panel.php'; ?>

</div>

<?php
Lens::renderPageConsoleDebug('business/details', $businessDetailsLensSnapshot);
Lens::renderPagePerformanceBoot('business/details', [
  'autosave_probe' => true,
]);
require __DIR__ . '/../_partials/footer_shared.php';
require_once \PayCal\Domain\Config\Environment::appHome() . 'html/footer.php';
