<?php declare(strict_types=1);

namespace PayCal\Domain;

$currentPage = 'PAGE_SETTINGS_APPEARANCE';

require_once dirname(__DIR__) . '/_layout.php';
?>

<?php
require __DIR__ . '/../_partials/panel_appearance_theme.php';
require __DIR__ . '/../_partials/panel_appearance_sidebar.php';
require __DIR__ . '/../_partials/panel_appearance_notifications.php';
?>

<?php
require __DIR__ . '/../_partials/footer_shared.php';
require_once Environment::appHome() . 'html/footer.php';
