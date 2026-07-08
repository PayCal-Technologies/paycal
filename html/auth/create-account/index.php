<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__ . '/../../config.php';

$requestUriRaw = $_SERVER['REQUEST_URI'] ?? '/auth/create-account/';
$requestUri = is_scalar($requestUriRaw) ? (string) $requestUriRaw : '/auth/create-account/';
$requestQueryRaw = parse_url($requestUri, PHP_URL_QUERY);
$redirectUrl = '/auth/signup/';
if (is_string($requestQueryRaw) && $requestQueryRaw !== '') {
  $redirectUrl .= '?' . $requestQueryRaw;
}

header('Location: ' . Environment::appURL($redirectUrl), true, 302);
exit;
