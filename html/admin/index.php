<?php declare(strict_types=1);

use PayCal\Controllers\AdminPageController;
use PayCal\Domain\AdminSurface;
use PayCal\Domain\Authentication;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Render;
use PayCal\Domain\User;
use PayCal\Observability\Lens;

require_once '../config.php';


require_once __DIR__.'/../src/Controllers/AdminPageController.php';

$currentPage = 'PAGE_ADMIN';
$pageTitle = 'Admin Dashboard - [PayCal]';
$pageLabel = 'Admin Dashboard';

Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/');

Lens::boot('admin');
if (\PayCal\Domain\InputSanitizer::getString('lens') === '1') {
  $requestMethodRaw = $_SERVER['REQUEST_METHOD'] ?? 'GET';
  $requestMethod = is_scalar($requestMethodRaw) ? (string) $requestMethodRaw : 'GET';
  Lens::add('Admin Backend Snapshot', [
    'page' => $currentPage,
    'is_admin' => \PayCal\Domain\User::isAdmin(),
    'admin_surface_enabled' => \PayCal\Domain\AdminSurface::isEnabled(),
    'is_manager' => \PayCal\Domain\User::isManager(),
    'request_method' => $requestMethod,
  ]);
}

require_once HTML.'/header.php';

$cspNonceRaw = $_SERVER['CSP_NONCE'] ?? '';
$cspNonce = is_scalar($cspNonceRaw) ? (string) $cspNonceRaw : '';
echo PHP_EOL."<link rel=\"stylesheet\" href=\"" . Render::cssURL('admin') . "\" nonce=\"{$cspNonce}\">".PHP_EOL;

// Delegate rendering to the controller
AdminPageController::dashboard();

require_once HTML.'/footer.php';
