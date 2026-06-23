<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Shared bootstrap for /business/* sub-pages (excludes dashboard monolith).
 *
 * Expects $currentPage (Page enum value string, e.g. PAGE_BUSINESS_DETAILS)
 * to be set before include. Use a string literal — config.php is not loaded yet,
 * so Page::* cannot be referenced in the including file.
 */
if (!isset($currentPage) || !is_string($currentPage) || $currentPage === '') {
  throw new \RuntimeException('Business sub-page requires $currentPage.');
}

require_once dirname(__DIR__) . '/config.php';
require __DIR__ . '/_partials/i18n.php';

Authentication::redirectHomeIfUnauthenticated();
BusinessNav::requirePremiumAccess();

require __DIR__ . '/_partials/vars.php';

$titleKey = BusinessNav::pageTitleKeyFor($currentPage);
if (!isset($pageTitle) || $pageTitle === '') {
  $pageTitle = Strings::i18n($titleKey) . ' - [' . Strings::i18n('SITE_NAME') . ']';
}
if (!isset($pageLabel) || $pageLabel === '') {
  $pageLabel = Strings::i18n($titleKey);
}

require_once \PayCal\Domain\Config\Environment::appHome() . 'html/header.php';
require __DIR__ . '/_context_header.php';
