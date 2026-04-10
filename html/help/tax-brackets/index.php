<?php declare(strict_types=1);
/**
 * Help: Canadian Tax Brackets — language router
 *
 * PURPOSE: Resolve the visitor's language and forward to the correct locale
 *          template for the Tax Brackets help article.
 * LOCATION: html/help/tax-brackets/index.php
 */

use PayCal\Domain\Language;

require_once __DIR__ . '/../../config.php';

$lang = defined('USER_LANGUAGE') ? strtolower((string) USER_LANGUAGE) : 'en';
if (!Language::isSupported($lang)) {
  $lang = 'en';
}

$candidate = __DIR__ . '/' . $lang . '.php';
if (!is_file($candidate)) {
  $candidate = __DIR__ . '/en.php';
}

require_once $candidate;
