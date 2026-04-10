<?php declare(strict_types=1);

use PayCal\Domain\Authentication;
use PayCal\Domain\Database;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Constants\Keys;

require_once '../config.php';

if (Authentication::validateAndTouchSession()) {
  $cookieHash = Authentication::getCookie();
  if ('' !== $cookieHash) {
    // Destroy session and record metrics
    Authentication::destroySession($cookieHash);
  }

  Database::del(Keys::SESSION . ':' . \PayCal\Domain\User::currentUUID() . ':nonce');

  // Delete PayCal cookie
    $domain = parse_url((string) Environment::appPublicURL(), PHP_URL_HOST);
  $cookieParams = [
      'expires' => time() - 3600,
      'path' => '/',
      'domain' => is_string($domain) ? $domain : '',
      'secure' => (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS']),
      'httponly' => true,
      'samesite' => 'Lax',
  ];
  setcookie('PAYCAL_AUTH', '', $cookieParams);
}

header('Location: ' . Environment::appURL('/'));

exit;
