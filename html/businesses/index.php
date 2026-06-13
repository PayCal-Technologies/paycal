<?php declare(strict_types=1);

require_once __DIR__ . '/../config.php';

header('Location: ' . \PayCal\Domain\Config\Environment::appURL('business/'), true, 301);
exit;
