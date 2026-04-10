<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\Enums\HttpStatus;

require_once '../../config.php';

if (!in_array(Environment::appEnv(), ['mac', 'dev'], true)) {
  http_response_code(HttpStatus::HTTP_FORBIDDEN);
  header('Content-Type: text/plain; charset=utf-8');
  echo "OPcache reset is only available in mac/dev environments.\n";
  exit;
}

Authentication::abortIfUnauthenticated();

if (!User::isAdmin()) {
  http_response_code(HttpStatus::HTTP_FORBIDDEN);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Admin access required.\n";
  exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(HttpStatus::HTTP_METHOD_NOT_ALLOWED);
  header('Allow: POST');
  header('Content-Type: text/plain; charset=utf-8');
  echo "Method not allowed. Use POST.\n";
  exit;
}

header('Content-Type: text/plain; charset=utf-8');

if (!function_exists('opcache_reset')) {
  echo "OPcache extension not available.\n";
  exit;
}

$ok = opcache_reset();
echo $ok ? "OPcache cleared.\n" : "OPcache reset failed.\n";
