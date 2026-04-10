<?php declare(strict_types=1);

use PayCal\Domain\Authentication;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\User;

require_once '../config.php';

if (Authentication::validateAndTouchSession()) {
  $cookieHash = Authentication::getCookie();
  if ('' !== $cookieHash) {
    Database::del(Keys::SESSION . ':' . $cookieHash);
  }

  $currentUserUuid = User::currentUUID();
  if ('' !== $currentUserUuid) {
    Database::del(Keys::USER . ':' . $currentUserUuid . ':' . session_id() . ':nonce');
  }

  // Delete PayCal cookie before redirecting to a neutral destination.
  $cookieDomain = (string) parse_url((string) SITE, PHP_URL_HOST);
  $cookieParams = [
    'expires' => time() - 3600,
    'path' => '/',
    'domain' => $cookieDomain,
    'secure' => (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
  ];
  setcookie('PAYCAL_AUTH', '', $cookieParams);
}

header('Clear-Site-Data: "cache", "cookies", "storage", "executionContexts"');
header('Location: https://weather.com/');

exit;
