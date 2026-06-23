<?php declare(strict_types=1);

namespace PayCal\Domain;

$currentPage = 'PAGE_SETTINGS';

require_once __DIR__ . '/_layout.php';
?>

<?php
require __DIR__ . '/_partials/panel_dashboard.php';
?>

<?php
require __DIR__ . '/_partials/footer_shared.php';
require_once Environment::appHome() . 'html/footer.php';
