<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__ . '/../config.php';
require __DIR__ . '/../business/_partials/i18n.php';

$currentPage = Page::CONNECTIONS->value;

Authentication::redirectHomeIfUnauthenticated();

if (!headers_sent()) {
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: 0');
}

require __DIR__ . '/../business/_partials/vars.php';

$pageLanguage = (string) User::current()->language;
$pageTitle = 'Connections - [' . businesses_index_i18n('SITE_NAME') . ']';
$pageLabel = 'Connections';

require_once Config\Environment::appHome() . 'html/header.php';
?>

<div id="business-workspace" class="business_workspace business_connections_page" data-business-subpage="connections">
<?php require __DIR__ . '/../business/_partials/profile_connect_panel.php'; ?>
</div>

<?php
require __DIR__ . '/../business/_partials/route_gate_dialog.php';
require __DIR__ . '/../business/_archive/partials/governance_dialogs.php';
require __DIR__ . '/../business/_partials/footer_shared.php';
require_once Config\Environment::appHome() . 'html/footer.php';
