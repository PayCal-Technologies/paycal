<?php declare(strict_types=1);

use PayCal\Domain\Language;

/**
 * Expose a page-scoped language override for transparency pages when a valid
 * explicit ?l=xx query is present.
 */
if (!defined('PAYCAL_PAGE_LANGUAGE_OVERRIDE')) {
  $requested = $_GET['l'] ?? null;
  if (is_string($requested)) {
    $requested = strtolower(trim($requested));
    if (preg_match('/^[a-z]{2}$/', $requested) && Language::isSupported($requested)) {
      define('PAYCAL_PAGE_LANGUAGE_OVERRIDE', $requested);
    }
  }
}

/**
 * Build a transparency-internal URL while preserving an explicit ?l=xx selection.
 */
function transparency_href(string $path): string
{
  $requested = defined('PAYCAL_PAGE_LANGUAGE_OVERRIDE') ? (string) PAYCAL_PAGE_LANGUAGE_OVERRIDE : null;
  if (!is_string($requested) || $requested === '') {
    return $path;
  }

  $separator = str_contains($path, '?') ? '&' : '?';
  return $path . $separator . 'l=' . rawurlencode($requested);
}