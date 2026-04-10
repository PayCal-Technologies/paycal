<?php declare(strict_types=1);

use PayCal\Controllers\AdminPageController;
use PayCal\Domain\AdminSurface;
use PayCal\Domain\Authentication;

require_once '../config.php';


require_once __DIR__.'/../src/Controllers/AdminPageController.php';

$currentPage = 'PAGE_ADMIN';
$pageTitle = 'Admin Tax Brackets - [PayCal]';
$pageLabel = 'Admin Tax Brackets';

Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/tax-brackets/');

require_once HTML.'/header.php';

// Delegate rendering to the controller
AdminPageController::taxBrackets();

require_once HTML.'/footer.php';
