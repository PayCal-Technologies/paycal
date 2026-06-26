<?php declare(strict_types=1);

use PayCal\Domain\CrawlPolicy;
use PayCal\Domain\Security;

require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: public, max-age=3600');
Security::sendCoreSecurityHeaders();

echo CrawlPolicy::renderRobotsTxt();
