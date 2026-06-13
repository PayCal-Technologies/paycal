<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Shared bootstrap for /business/* sub-pages (public extension preview shell).
 *
 * Expects $currentPage (Page enum value string) to be set before include.
 */
if (!isset($currentPage) || !is_string($currentPage) || $currentPage === '') {
  throw new \RuntimeException('Business sub-page requires $currentPage.');
}

require_once dirname(__DIR__) . '/config.php';
require __DIR__ . '/_partials/i18n.php';

Authentication::redirectHomeIfUnauthenticated();
BusinessSurface::redirectHomeIfPageUnavailable(parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/business/'), PHP_URL_PATH) ?: '/business/');

require __DIR__ . '/_partials/vars.php';

$titleKey = BusinessSurface::pageTitleKeyFor($currentPage);
if (!isset($pageTitle) || $pageTitle === '') {
  $pageTitle = Strings::i18n($titleKey) . ' - [' . Strings::i18n('SITE_NAME') . ']';
}
if (!isset($pageLabel) || $pageLabel === '') {
  $pageLabel = Strings::i18n($titleKey);
}

require_once \PayCal\Domain\Config\Environment::appHome() . 'html/header.php';

$cspNonceRaw = $_SERVER['CSP_NONCE'] ?? '';
$cspNonce = is_scalar($cspNonceRaw) ? (string) $cspNonceRaw : '';
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('business') . '" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;

require __DIR__ . '/_context_header.php';
