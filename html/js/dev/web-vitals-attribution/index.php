<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../../config.php';

if (!Environment::isWebVitalsDiagnosticsEnabled()) {
  http_response_code(404);
  exit;
}

$vendorPath = dirname(__DIR__, 4) . '/node_modules/web-vitals/dist/web-vitals.attribution.js';
if (!is_file($vendorPath)) {
  http_response_code(503);
  header('Content-Type: text/plain; charset=UTF-8');
  echo 'web-vitals attribution build missing; run npm install in project root';
  exit;
}

CORS::handleORIGIN();
CORS::renderContentType('text/javascript');
header('Cache-Control: no-store');

readfile($vendorPath);
