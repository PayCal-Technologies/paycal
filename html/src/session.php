<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\Enums\FormTTL;

/**
 * session.php
 *
 * Purpose: Bootstrap user-scoped runtime constants and presentation defaults for each request.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



// Defaults
$textSizing = SystemConfig::TEXT_BASE;
$spacing = SystemConfig::SPACING_LESS;
$lineHeight = SystemConfig::LINEHEIGHT_LESS;
$audioVisibility = 'hidden';

$userTheme = SystemConfig::PC_THEME_DEFAULT;
$userLang = SystemConfig::DEFAULT_LANGUAGE;

$user = User::current();
$hash = Authentication::getSessionHashFromCookie();
$isAuthenticated = $hash !== null && Authentication::sessionExists($hash);
if ($isAuthenticated) {
  $userTheme = $user->theme;
  $userLang = $user->language;

  $textRaw = strtolower(trim($user->text));
  $spacingRaw = strtolower(trim($user->spacing));

  $textAdjustment = 0;
  if ($textRaw === 'small') {
    $textAdjustment = -2;
  } elseif ($textRaw === 'large') {
    $textAdjustment = 2;
  } elseif ($textRaw === 'x-large') {
    $textAdjustment = 5;
  } elseif (preg_match('/^-?\d+$/', $textRaw) === 1) {
    $textAdjustment = max(-5, min(5, (int) $textRaw));
  }

  $spacingAdjustment = 0;
  if ($spacingRaw === 'tight' || $spacingRaw === 'compact') {
    $spacingAdjustment = -5;
  } elseif ($spacingRaw === 'spacious') {
    $spacingAdjustment = 5;
  } elseif (preg_match('/^-?\d+$/', $spacingRaw) === 1) {
    $spacingAdjustment = max(-5, min(5, (int) $spacingRaw));
  }

  $textSizing = ($textAdjustment > 0)
    ? SystemConfig::TEXT_LARGER
    : (($textAdjustment < 0) ? SystemConfig::TEXT_SMALLER : SystemConfig::TEXT_BASE);

  $spacing = $spacingAdjustment > 0 ? SystemConfig::SPACING_MORE : SystemConfig::SPACING_LESS;
  $lineHeight = $spacingAdjustment > 0 ? SystemConfig::LINEHEIGHT_MORE : SystemConfig::LINEHEIGHT_LESS;

  $audioVisibility = 'none' !== $user->audio_feedback
    ? 'visible'
    : 'hidden';
}

// Derived Locale

$userLocale = strtolower($userLang).'_'.strtoupper($userLang);

if (!defined('USER_THEME')) {
  define('USER_THEME', $userTheme);
}

if (!defined('USER_LANGUAGE')) {
  define('USER_LANGUAGE', $userLang);
}

if (!defined('USER_LOCALE')) {
  define('USER_LOCALE', $userLocale);
}

if (!defined('USER_TEXT_SIZING')) {
  define('USER_TEXT_SIZING', $textSizing);
}

if (!defined('USER_SPACING')) {
  define('USER_SPACING', $spacing);
}

if (!defined('USER_LINE_HEIGHT')) {
  define('USER_LINE_HEIGHT', $lineHeight);
}

if (!defined('AUDIO_FEEDBACK_VISIBILITY')) {
  define('AUDIO_FEEDBACK_VISIBILITY', $audioVisibility);
}
// Logged-in-only constants

if ($isAuthenticated) {
  $authCookieRaw = $_COOKIE['PAYCAL_AUTH'] ?? '';
  $authCookie = is_scalar($authCookieRaw) ? (string) $authCookieRaw : '';
  Config::createStringConstants([
      'USER_COOKIE' => Authentication::getCookie(),
      'USER_PAY_PERIOD_LENGTH' => (string) $user->pay_period_length,
      'USER_CALENDAR_AUTOFOCUS' => (string) $user->calendar_autofocus,
      'USER_CALENDAR_AUDIOLABELS' => (string) $user->calendar_audio_labels,
      'USER_CALENDAR_DAY_NAME_FORMAT' => (string) $user->calendar_day_name_format,
      'USER_AUTH_COOKIE' => $authCookie,
  ]);
}

// Locale Activation

setlocale(LC_ALL, USER_LOCALE.'.UTF-8');
$pageLanguage = USER_LANGUAGE;
