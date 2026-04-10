<?php declare(strict_types=1);

use PayCal\Controllers\TestsPageController;

require_once '../config.php';


require_once '../src/Controllers/TestsPageController.php';

$currentPage = 'PAGE_TESTS';
$pageTitle = 'Test Suite Dashboard - [PayCal]';
$pageLabel = 'Test Suite Dashboard';

require_once HTML.'/header.php';

// Delegate rendering to the controller
TestsPageController::dashboard();

require_once HTML.'/footer.php';
