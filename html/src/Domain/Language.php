<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Language.php
 *
 * Purpose: Define the Language class for PayCal\Domain.
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
}
