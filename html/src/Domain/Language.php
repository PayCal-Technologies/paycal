<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Language.php
 *
 * Purpose: Supported language catalog and locale resolution helper for server-side
 *          string loading and UI language selection.
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
/**
 * Class Language.
 *
 * Static class providing language codes, default language, and language
 * metadata for UI rendering and localization operations.
 */
final class Language
{
  /**
   * Default language code.
   */
  public const DEFAULT = 'en';

  /**
   * Supported languages with their display names.
   * Format: language_code => 'Display Name'.
   *
   * @var array<string, string>
   */
  public const AVAILABLE = [
    'en' => 'English',
    'de' => 'German',
    'fr' => 'French',
    'es' => 'Spanish',
    'it' => 'Italian',
    'nl' => 'Dutch',
    'pt' => 'Portuguese',
    'hi' => 'Hindi',
    'tl' => 'Tagalog',
    'tr' => 'Turkish',
  ];

  /**
   * Check if a language code is supported.
   *
   * @param string $code Language code
   */
  public static function isSupported(string $code): bool
  {
    return isset(self::AVAILABLE[$code]);
  }

  /**
   * Get language display name by code.
   *
   * @param string $code Language code
   *
   * @return string Display name or empty string if not found
   */
  public static function getDisplayName(string $code): string
  {
    return self::AVAILABLE[$code] ?? '';
  }

  /**
   * Get all language codes.
   *
   * @return array<int, string>
   */
  public static function getCodes(): array
  {
    return array_keys(self::AVAILABLE);
  }

  /**
   * Resolve a language from a query parameter using sanitized input.
   *
   * Only 2-letter supported language codes are accepted.
   */
  public static function resolveFromQuery(string $queryParam = 'l', ?string $fallback = null): string
  {
    $fallbackCode = strtolower((string) ($fallback ?? (defined('USER_LANGUAGE') ? USER_LANGUAGE : self::DEFAULT)));
    if (!self::isSupported($fallbackCode)) {
      $fallbackCode = self::DEFAULT;
    }

    $requested = InputSanitizer::getString($queryParam);
    if (!is_string($requested)) {
      return $fallbackCode;
    }

    $requested = strtolower(trim($requested));
    if (!preg_match('/^[a-z]{2}$/', $requested)) {
      return $fallbackCode;
    }

    return self::isSupported($requested) ? $requested : $fallbackCode;
  }

  /**
   * Resolve BCP-47 / ICU locale tag for date and number formatting.
   *
   * Prefers a stored locale when its language subtag matches the UI language;
   * otherwise falls back to the UI language code (Intl accepts bare codes like "fr").
   */
  public static function resolveDateLocale(string $storedLocale, string $language): string
  {
    $language = strtolower(trim($language));
    if (!self::isSupported($language)) {
      $language = self::DEFAULT;
    }

    $storedLocale = trim($storedLocale);
    if ($storedLocale !== '') {
      $localeLanguage = self::extractLanguageSubtag($storedLocale);
      if ($localeLanguage !== '' && $localeLanguage === $language) {
        return $storedLocale;
      }
    }

    return $language;
  }

  /**
   * Extract the primary ISO-639-1 language code from a locale tag.
   */
  public static function extractLanguageSubtag(string $localeTag): string
  {
    $normalized = str_replace('_', '-', trim($localeTag));
    if (preg_match('/^([a-z]{2})/i', $normalized, $matches) === 1) {
      return strtolower($matches[1]);
    }

    return '';
  }
}
