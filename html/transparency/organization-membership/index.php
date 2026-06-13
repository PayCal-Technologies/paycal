<?php declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$target = transparency_href('/transparency/business-membership/');
header('Location: ' . $target, true, 301);
exit;
