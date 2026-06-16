<?php declare(strict_types=1);

namespace PayCal\Domain;

$currentPage = 'PAGE_SETTINGS_ACCOUNT';

require_once dirname(__DIR__) . '/_layout.php';
require __DIR__ . '/../_partials/vars_account.php';
?>

<?php require __DIR__ . '/../_partials/panel_account.php'; ?>
<?php require __DIR__ . '/../_partials/panel_account_locale.php'; ?>

<?php if (!$hasActiveBusiness) {
  require __DIR__ . '/../../business/_partials/profile_connect_panel.php';
} ?>

<?php require __DIR__ . '/../_partials/panel_account_billing.php'; ?>
<?php require __DIR__ . '/../_partials/panel_account_activity.php'; ?>
<?php require __DIR__ . '/../_partials/panel_account_danger.php'; ?>

<?php
require __DIR__ . '/../../business/_partials/route_gate_dialog.php';
if (!$hasActiveBusiness) {
  require __DIR__ . '/../../business/_archive/partials/governance_dialogs.php';
}

require __DIR__ . '/../_partials/footer_shared.php';
require_once Environment::appHome() . 'html/footer.php';
