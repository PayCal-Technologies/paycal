<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

$controller = new \PayCal\Controllers\EmailVerificationController();
$controller->verifyEmail();
