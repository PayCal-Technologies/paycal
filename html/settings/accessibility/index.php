<?php declare(strict_types=1);

namespace PayCal\Domain;

$currentPage = 'PAGE_SETTINGS_ACCESSIBILITY';

require_once dirname(__DIR__) . '/_layout.php';
?>

<?php
require __DIR__ . '/../_partials/panel_accessibility_typography.php';
require __DIR__ . '/../_partials/panel_accessibility_audio.php';
require __DIR__ . '/../_partials/panel_accessibility_extras.php';
?>

<?php
require __DIR__ . '/../_partials/footer_shared.php';
require_once Environment::appHome() . 'html/footer.php';
