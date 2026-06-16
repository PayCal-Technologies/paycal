<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Shared bootstrap for /settings/* sub-pages.
 *
 * Expects $currentPage (SettingsNav PAGE_* constant) before include.
 */
if (!isset($currentPage) || !is_string($currentPage) || $currentPage === '') {
  throw new \RuntimeException('Settings sub-page requires $currentPage.');
}

require_once dirname(__DIR__) . '/config.php';
require __DIR__ . '/_partials/i18n.php';

Authentication::redirectHomeIfUnauthenticated();

if (!headers_sent()) {
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: 0');
}

require __DIR__ . '/_partials/vars.php';

$tabMeta = SettingsNav::tabForPage($currentPage);
if ($tabMeta === null) {
  throw new \RuntimeException('Unknown settings sub-page: ' . $currentPage);
}

$pageLanguage = (string) $user->language;
$pageLabel = settings_index_i18n('SETTINGS');

if (!isset($pageTitle) || $pageTitle === '') {
  $pageTitle = settings_index_i18n($tabMeta['title_key']) . ' - [' . settings_index_i18n('SITE_NAME') . ']';
}

$settingsSubpageSlug = $tabMeta['slug'];

require_once Environment::appHome() . 'html/header.php';
require __DIR__ . '/_partials/modals.php';
require __DIR__ . '/_shell.php';
