<?php declare(strict_types=1);

use PayCal\Domain\Language;

require_once __DIR__ . '/../config.php';

// Expose an explicit ?l=xx page-language override so Strings::i18n() uses the
// correct locale for all blog_index_i18n() calls (same pattern as transparency pages).
if (!defined('PAYCAL_PAGE_LANGUAGE_OVERRIDE')) {
  $requested = isset($_GET['l']) && is_string($_GET['l']) ? strtolower(trim($_GET['l'])) : '';
  if ($requested !== '' && preg_match('/^[a-z]{2}$/', $requested) && Language::isSupported($requested)) {
    define('PAYCAL_PAGE_LANGUAGE_OVERRIDE', $requested);
  }
}

$lang = Language::resolveFromQuery('l');

$candidate = __DIR__ . '/' . $lang . '.php';
if (!is_file($candidate)) {
  $candidate = __DIR__ . '/en.php';
}

require_once $candidate;
