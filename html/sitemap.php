<?php declare(strict_types=1);

use PayCal\Domain\CrawlPolicy;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Security;

require_once __DIR__ . '/config.php';

if (!Environment::allowsPublicIndexing()) {
  http_response_code(404);
  header('Content-Type: text/plain; charset=UTF-8');
  Security::sendCoreSecurityHeaders();
  echo 'Not Found';
  exit;
}

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');
Security::sendCoreSecurityHeaders();

echo CrawlPolicy::renderSitemapXml();
