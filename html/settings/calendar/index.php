<?php declare(strict_types=1);

namespace PayCal\Domain;

$currentPage = 'PAGE_SETTINGS_CALENDAR';

require_once dirname(__DIR__) . '/_layout.php';
?>

<?php require __DIR__ . '/../_partials/panel_calendar.php'; ?>
<?php require __DIR__ . '/../_partials/panel_calendar_work_defaults.php'; ?>
<?php require __DIR__ . '/../_partials/panel_calendar_pay_period.php'; ?>

<?php
require __DIR__ . '/../_partials/footer_shared.php';
require_once Environment::appHome() . 'html/footer.php';
