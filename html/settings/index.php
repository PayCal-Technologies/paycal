<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Settings root — HTTP redirect to the default sub-page (Account).
 *
 * Legacy hash anchors (#panel-style, etc.) are handled on sub-pages via
 * footer_shared.php (CSP nonce script). Do not render header/footer here.
 */
require_once __DIR__ . '/../config.php';

Authentication::redirectHomeIfUnauthenticated();

if (!headers_sent()) {
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: 0');
}

$target = SettingsNav::defaultSubPageHref();
$queryString = $_SERVER['QUERY_STRING'] ?? '';
if ($queryString !== '') {
  $target .= '?' . $queryString;
}

header('Location: ' . $target, true, 302);
exit;
