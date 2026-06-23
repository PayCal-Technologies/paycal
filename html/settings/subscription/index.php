<?php declare(strict_types=1);

namespace PayCal\Domain;

$currentPage = 'PAGE_SETTINGS_SUBSCRIPTION';

require_once dirname(__DIR__) . '/_layout.php';
require __DIR__ . '/../_partials/vars_account.php';
?>

<?php require __DIR__ . '/../_partials/panel_account_billing.php'; ?>

<?php
require __DIR__ . '/../_partials/footer_shared.php';
require_once Environment::appHome() . 'html/footer.php';
