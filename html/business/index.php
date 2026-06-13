<?php declare(strict_types=1);

namespace PayCal\Domain;

$currentPage = 'PAGE_BUSINESS_DASHBOARD';

require_once '../config.php';
require __DIR__ . '/_partials/i18n.php';

Authentication::redirectHomeIfUnauthenticated();
BusinessSurface::redirectHomeIfPageUnavailable('/business/');

require __DIR__ . '/_partials/vars.php';

$pageTitle = businesses_index_i18n('BUSINESS_NAV_DASHBOARD') . ' - [' . businesses_index_i18n('SITE_NAME') . ']';
$pageLabel = businesses_index_i18n('BUSINESS_NAV_DASHBOARD');

require_once \PayCal\Domain\Config\Environment::appHome() . 'html/header.php';

$cspNonceRaw = $_SERVER['CSP_NONCE'] ?? '';
$cspNonce = is_scalar($cspNonceRaw) ? (string) $cspNonceRaw : '';
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('business') . '" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;

require __DIR__ . '/_context_header.php';

?>

<div id="business-workspace" class="business_workspace business_dashboard" data-business-subpage="dashboard">

  <h1 class="visually_hidden"><?php echo htmlspecialchars(businesses_index_i18n('BUSINESS_NAV_DASHBOARD'), ENT_QUOTES, 'UTF-8'); ?></h1>

  <p class="business_public_preview_lead"><?php echo htmlspecialchars(businesses_index_i18n('BUSINESS_PUBLIC_PREVIEW_LEAD'), ENT_QUOTES, 'UTF-8'); ?></p>

</div>

<?php
require __DIR__ . '/_partials/extension_disclaimer.php';
require_once \PayCal\Domain\Config\Environment::appHome() . 'html/footer.php';
