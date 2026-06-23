<?php declare(strict_types=1);

namespace PayCal\Domain;

$currentPage = 'PAGE_BUSINESS_PAYROLL';

require_once dirname(__DIR__) . '/_layout.php';
?>

<div id="business-workspace" class="business_workspace business_payroll" data-business-subpage="payroll"<?php echo $workspaceBusinessIdAttr; ?>>

  <h1 class="visually_hidden"><?php echo Strings::i18n('BUSINESS_NAV_PAYROLL'); ?></h1>

  <input type="hidden" id="businesses_editor_business_id" value="<?php echo htmlspecialchars($workspaceBusinessId, ENT_QUOTES, 'UTF-8'); ?>">

<?php require __DIR__ . '/../_partials/payroll_settings_panel.php'; ?>

</div>

<?php
require __DIR__ . '/../_partials/footer_shared.php';
require_once \PayCal\Domain\Config\Environment::appHome() . 'html/footer.php';
