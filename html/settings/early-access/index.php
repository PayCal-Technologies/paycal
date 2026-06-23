<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once dirname(__DIR__, 2) . '/config.php';
Authentication::redirectHomeIfUnauthenticated();

$currentPage = SettingsNav::PAGE_EARLY_ACCESS;

if (!SettingsNav::canViewEarlyAccess()) {
  header('Location: /settings/security/', true, 302);
  exit;
}

require_once dirname(__DIR__) . '/_layout.php';
?>

<?php require __DIR__ . '/../_partials/panel_early_access.php'; ?>

<?php
require __DIR__ . '/../_partials/footer_shared.php';
require_once Environment::appHome() . 'html/footer.php';
