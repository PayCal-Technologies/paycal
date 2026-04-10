<?php declare(strict_types=1);

use PayCal\Domain\Authentication;
use PayCal\Domain\Database;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Config\Environment;

require_once '../config.php';

Authentication::redirectHomeIfUnauthenticated();

$lang = InputSanitizer::GetString('lang') ?? '';
$supported = ['en', 'fr', 'de', 'es', 'it', 'nl', 'pt', 'tr', 'tl', 'hi']; // whitelist

if (in_array($lang, $supported, true)) {
  Database::hset('user:'.USER_UUID, ['language' => $lang]);
}

$redirectTarget = '/';
$referrerRaw = $_SERVER['HTTP_REFERER'] ?? '';

if (is_scalar($referrerRaw)) {
  $referrer = trim((string) $referrerRaw);
  if ($referrer !== '') {
    $parts = parse_url($referrer);
    if (is_array($parts)) {
      $requestHostRaw = $_SERVER['HTTP_HOST'] ?? '';
      $requestHost = strtolower(trim((string) (is_scalar($requestHostRaw) ? $requestHostRaw : '')));
      if (str_contains($requestHost, ':')) {
        $requestHost = explode(':', $requestHost)[0];
      }

      $appHost = strtolower((string) (parse_url(Environment::appPublicURL(), PHP_URL_HOST) ?? ''));
      $refHost = strtolower((string) ($parts['host'] ?? ''));

      $hostAllowed = $refHost === '' || $refHost === $requestHost || ($appHost !== '' && $refHost === $appHost);
      if ($hostAllowed) {
        $path = (string) ($parts['path'] ?? '/');
        if (!str_starts_with($path, '/')) {
          $path = '/' . ltrim($path, '/');
        }

        $query = isset($parts['query']) ? ('?' . (string) $parts['query']) : '';
        $redirectTarget = $path . $query;
      }
    }
  }
}

header('Location: '.$redirectTarget);

exit;
