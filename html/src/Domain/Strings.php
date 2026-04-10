<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Strings.php
 *
 * Purpose: Shared string, i18n, formatting, and HTML/text helper utility used
 * across rendering, routing, and reporting layers.
 *
 * Developer notes:
 * - This file is a heavily reused utility surface; behavioral changes can have
 *   extremely broad impact.
 * - Keep locale/i18n caching behavior predictable and avoid mixing unrelated
 *   domain logic into this helper just because it returns strings.
 *
 * @category   Domain
 * @package    PayCal\Domain
 *
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * String and i18n helper utility.
 *
 * Responsibilities:
 * - Provide reusable formatting and extraction helpers.
 * - Resolve localized text and cached string maps.
 * - Centralize HTML/text-safe string transformations used across the app.
 */
final class Strings
{
  private const APCU_LOCALE_CACHE_PREFIX = 'paycal:i18n:locale:';
  private const APCU_LOCALE_CACHE_TTL_SECONDS = 300;

  /**
   * Cached on-disk locale maps loaded from strings/*.txt.
   *
   * @var array<string, array<string, string>>
   */
  private static array $fileLocaleCache = [];

  /**
   * Cached merged locale maps loaded from active extension i18n folders.
   *
   * @var array<string, array<string, string>>
   */
  private static array $extensionLocaleCache = [];

  /**
   * Pads a number with leading zeros to ensure it has a specified length.
   * Commonly used for formatting date components like month and day.
   *
   * @param int $number    the number to be padded
   * @param int $padLength The desired length. Default is 2.
   *
   * @return string the padded number as a string
   */
  public static function padNumber(int $number, int $padLength = 2): string
  {
    return str_pad((string) $number, $padLength, '0', STR_PAD_LEFT);
  }

  /**
   * Generates a string of spaces of a specified length.
   * Useful for padding, alignment, or visual separation.
   *
   * @param int $length the desired length
   *
   * @return string a string of spaces
   */
  public static function spaces(int $length): string
  {
    return str_pad(' ', $length);
  }

  /**
   * Extract a specific piece from a delimited string.
   *
   * @param string $input     input string
   * @param int    $index     0-based index of the piece
   * @param string $delimiter separator (default ":")
   *
   * @return null|string extracted piece or null if out of bounds
   */
  public static function extractPiece(string $input, int $index, string $delimiter = ':'): ?string
  {
    $delimiter = ('' === $delimiter) ? ':' : $delimiter;
    $pieces = explode($delimiter, $input);
    $index = (int) intval($index);

    return $pieces[$index] ?? null;
  }

  /**
   * Returns a copy of the given array where all specified keys are guaranteed to exist.
   * If a key is missing, it will be initialized to 0.
   * Useful for normalizing data arrays before numeric operations.   * Example:
   *   $arr = ["l" => 2];
   *   $arr = Strings::ensureKeysExist($arr, ["l", "t"]);
   *   // Result: ["l" => 2, "t" => 0].
   *
   * @param array<string, null|bool|float|int|string> $arr  the input array to check
   * @param array<int, string>                        $keys a list of expected keys to ensure exist
   *
   * @return array<string, null|bool|float|int|string> the normalized array with missing keys set to 0
   */
  public static function ensureKeysExist(array $arr, array $keys): array
  {
    foreach ($keys as $k) {
      if (!array_key_exists($k, $arr)) {
        $arr[$k] = (string) '0';
      } elseif ('' === $arr[$k]) {
        $arr[$k] = (string) '0';
      }
    }

    return $arr;
  }

  /**
   * Determines the appropriate ordinal suffix for a given day of the month.
   *
   * @param int $day Day of the month (without leading zeros)
   *
   * @return string Ordinal suffix for the day (st, nd, rd, or th)
   */
  public static function getOrdinal(int $day): string
  {
    if (!in_array($day % 100, [11, 12, 13], true)) {
      switch ($day % 10) {
        case 1:
          return 'st';

        case 2:
          return 'nd';

        case 3:
          return 'rd';
      }
    }

    return 'th';
  }

  /**
   * Returns the spoken ordinal word for a given day of the month (e.g., 1 → "first", 2 → "second").
   *
   * @param int $day Day of the month (1–31)
   *                 TODO: ADD i18n constants here...
   *
   * @return string Ordinal word
   */
  public static function getOrdinalSpoken(int $day): string
  {
    static $ordinals = [
        1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth',
        6 => 'sixth', 7 => 'seventh', 8 => 'eighth', 9 => 'ninth', 10 => 'tenth',
        11 => 'eleventh', 12 => 'twelfth', 13 => 'thirteenth', 14 => 'fourteenth',
        15 => 'fifteenth', 16 => 'sixteenth', 17 => 'seventeenth', 18 => 'eighteenth', 19 => 'nineteenth',
        20 => 'twentieth', 21 => 'twenty-first', 22 => 'twenty-second', 23 => 'twenty-third', 24 => 'twenty-fourth',
        25 => 'twenty-fifth', 26 => 'twenty-sixth', 27 => 'twenty-seventh', 28 => 'twenty-eighth',
        29 => 'twenty-ninth', 30 => 'thirtieth', 31 => 'thirty-first',
    ];

    return $ordinals[$day] ?? (string) $day;
  }

  /**
   * Returns a spoken-formatted date string for aria-labels.
   *
   * @param string $date Date in "YYYY-MM-DD" or compatible format
   * @param string $type Format type: "number", "short", or "long"
   */
  public static function formatDateAria(string $date, string $type = 'long'): string
  {
    $d = new \DateTime($date);

    return match ($type) {
      'number' => $d->format('j'),
      'short' => $d->format('F j'),
      'long' => $d->format('F j, Y'),
      default => $d->format('F j, Y'),
    };
  }

  /**
   * Formats a date string with an ordinal day suffix (e.g., "1st", "2nd").
   * This function formats a given date string into a more human-readable form,
   * including the day of the week, month, day with ordinal suffix, and year.
   *
   * @param string $dateString Date in a string format understood by DateTime (e.g., "YYYY-MM-DD").
   *
   * @return string formatted date string including the ordinal suffix for the day
   */
  public static function formatFulldateWithOrdinal(string $dateString): string
  {
    $date = new \DateTime($dateString);

    return $date->format('l F j').self::getOrdinal((int) $date->format('j')).$date->format(', Y');
  }

  /**
   * Retrieves a language string from locale files based on key and language code.
   *
   * @param string      $key  The i18n key (e.g., 'i_SETTINGS').
   * @param null|string $lang The language code (e.g., 'en', 'de'). Defaults to USER_LANGUAGE or 'en'.
   *
   * @return string the translated string, or the key if not found
   */
  public static function i18n(string $key, ?string $lang = null): string
  {
    $lang = self::resolveLanguage($lang);
    $lookupKey = (0 === strncasecmp($key, 'i_', 2)) ? substr($key, 2) : $key; // Case insensitive guard

    $extensionValue = self::lookupFromActiveExtensionLocales($lookupKey, $lang)
      ?? ($lang !== 'en' ? self::lookupFromActiveExtensionLocales($lookupKey, 'en') : null);

    if ($extensionValue !== null && $extensionValue !== '') {
      return $extensionValue;
    }

    $fileValue = self::lookupFromLocaleFile($lookupKey, $lang)
      ?? ($lang !== 'en' ? self::lookupFromLocaleFile($lookupKey, 'en') : null);

    if ($fileValue !== null && $fileValue !== '') {
      return $fileValue;
    }

    return $key;
  }

  /**
   * Lookup a translation key from active extension locale files.
   */
  private static function lookupFromActiveExtensionLocales(string $key, string $lang): ?string
  {
    $map = self::loadActiveExtensionLocaleMap($lang);
    return $map[$key] ?? null;
  }

  /**
   * Header i18n access point to keep template calls explicit.
   */
  public static function headerI18n(string $key, ?string $lang = null): string
  {
    return self::i18n($key, $lang);
  }

  /**
   * Lookup a translation key from on-disk locale files.
   */
  private static function lookupFromLocaleFile(string $key, string $lang): ?string
  {
    if (!isset(self::$fileLocaleCache[$lang])) {
      self::$fileLocaleCache[$lang] = self::loadLocaleFileMap($lang);
    }

    return self::$fileLocaleCache[$lang][$key] ?? null;
  }

  /**
   * Load key-value pairs from strings/<lang>.txt.
   *
   * @return array<string, string>
   */
  private static function loadLocaleFileMap(string $lang): array
  {
    $candidatePaths = [
      rtrim((string) Environment::appHome(), '/').'/strings/'.$lang.'.txt',
      dirname(__DIR__, 3).'/strings/'.$lang.'.txt',
    ];

    $basePath = null;
    foreach ($candidatePaths as $candidate) {
      if (is_file($candidate)) {
        $basePath = $candidate;
        break;
      }
    }

    if ($basePath === null) {
      return [];
    }

    $fileMtime = @filemtime($basePath);
    $fileSize = @filesize($basePath);
    $cacheVersion = ($fileMtime !== false ? (string) $fileMtime : '0')
      . ':'
      . ($fileSize !== false ? (string) $fileSize : '0');
    $apcuCacheKey = self::APCU_LOCALE_CACHE_PREFIX . $lang . ':' . hash('sha256', $basePath . ':' . $cacheVersion);

    if (self::isApcuAvailable()) {
      $cachedMap = apcu_fetch($apcuCacheKey, $cacheHit);
      if ($cacheHit && is_array($cachedMap)) {
        /** @var array<string, string> $cachedMap */
        return $cachedMap;
      }
    }

    $map = self::parseLocaleFile($basePath);

    if (self::isApcuAvailable()) {
      apcu_store($apcuCacheKey, $map, self::APCU_LOCALE_CACHE_TTL_SECONDS);
    }

    return $map;
  }

  /**
   * @return array<string, string>
   */
  private static function loadActiveExtensionLocaleMap(string $lang): array
  {
    $cacheKey = self::activeExtensionLocaleCacheKey($lang);
    if (isset(self::$extensionLocaleCache[$cacheKey])) {
      return self::$extensionLocaleCache[$cacheKey];
    }

    $map = [];

    foreach (self::activeExtensionManifestsForI18n() as $manifest) {
      $i18n = is_array($manifest['i18n'] ?? null) ? $manifest['i18n'] : [];
      $relativePath = trim(self::arrayString($i18n, 'path'));
      if ($relativePath === '') {
        continue;
      }

      $defaultLang = strtolower(trim(self::arrayString($i18n, 'default_lang', 'en')));
      if ($defaultLang === '') {
        $defaultLang = 'en';
      }

      $directory = trim(self::arrayString($manifest, 'directory'));
      if ($directory === '') {
        continue;
      }

      $localeDir = rtrim($directory, '/') . '/' . trim($relativePath, '/');
      if (!is_dir($localeDir)) {
        continue;
      }

      $languages = [$lang];
      if (!in_array($defaultLang, $languages, true)) {
        $languages[] = $defaultLang;
      }
      if (!in_array('en', $languages, true)) {
        $languages[] = 'en';
      }

      foreach ($languages as $candidateLang) {
        $file = $localeDir . '/' . $candidateLang . '.txt';
        if (!is_file($file)) {
          continue;
        }

        $candidateMap = self::parseLocaleFile($file);
        foreach ($candidateMap as $candidateKey => $candidateValue) {
          if (!isset($map[$candidateKey])) {
            $map[$candidateKey] = $candidateValue;
          }
        }
      }
    }

    self::$extensionLocaleCache = [$cacheKey => $map];
    return $map;
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  private static function activeExtensionManifestsForI18n(): array
  {
    return ExtensionI18nManifestBridge::activeManifestsForI18n();
  }

  /**
   * Handles activeExtensionLocaleCacheKey operation.
   */
  private static function activeExtensionLocaleCacheKey(string $lang): string
  {
    if (!ExtensionI18nManifestBridge::runtimeAvailable()) {
      return 'none:' . $lang;
    }

    $parts = [$lang];
    foreach (self::activeExtensionManifestsForI18n() as $manifest) {
      $i18n = is_array($manifest['i18n'] ?? null) ? $manifest['i18n'] : [];
      $relativePath = trim(self::arrayString($i18n, 'path'));
      if ($relativePath === '') {
        continue;
      }

      $directory = trim(self::arrayString($manifest, 'directory'));
      if ($directory === '') {
        continue;
      }

      $localeDir = rtrim($directory, '/') . '/' . trim($relativePath, '/');
      if (!is_dir($localeDir)) {
        continue;
      }

      $defaultLang = strtolower(trim(self::arrayString($i18n, 'default_lang', 'en')));
      if ($defaultLang === '') {
        $defaultLang = 'en';
      }

      $versionChunks = [];
      foreach ([$lang, $defaultLang, 'en'] as $candidateLang) {
        $file = $localeDir . '/' . $candidateLang . '.txt';
        if (!is_file($file)) {
          continue;
        }
        $mtime = @filemtime($file);
        $size = @filesize($file);
        $versionChunks[] = $candidateLang . ':'
          . ($mtime !== false ? (string) $mtime : '0')
          . ':'
          . ($size !== false ? (string) $size : '0');
      }

      $parts[] = self::arrayString($manifest, 'source')
        . '|'
        . self::arrayString($manifest, 'id')
        . '|'
        . $localeDir
        . '|'
        . implode(',', $versionChunks);
    }

    return hash('sha256', implode(';', $parts));
  }

  /**
   * @return array<string, string>
   */
  private static function parseLocaleFile(string $filePath): array
  {
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
      return [];
    }

    $map = [];
    foreach ($lines as $line) {
      if ($line === '' || $line[0] === '#') {
        continue;
      }

      $parts = preg_split('/\s+/', $line, 2);
      if (!is_array($parts) || count($parts) !== 2) {
        continue;
      }

      $map[$parts[0]] = $parts[1];
    }

    return $map;
  }

  /**
   * Handles isApcuAvailable operation.
   */
  private static function isApcuAvailable(): bool
  {
    return function_exists('apcu_fetch')
      && function_exists('apcu_store')
      && (bool) ini_get('apc.enabled')
      && ((PHP_SAPI !== 'cli') || (bool) ini_get('apc.enable_cli'));
  }

  /**
   * @param array<string, mixed> $input
   */
  private static function arrayString(array $input, string $key, string $default = ''): string
  {
    if (!array_key_exists($key, $input)) {
      return $default;
    }

    $value = $input[$key];
    return is_string($value) ? $value : $default;
  }

  /**
   * Retrieves an HTML constant from Redis based on the key and language code.
   *
   * @param string      $key  The HTML key (e.g., 'h_PAYCAL').
   * @param null|string $lang The language code (e.g., 'en', 'de'). Defaults to USER_LANGUAGE or 'en'.
   *
   * @return string the HTML string, or the key if not found
   */
  public static function html(string $key, ?string $lang = null): string
  {
    $lang = self::resolveLanguage($lang);
    $resolvedKey = (0 === strncasecmp($key, 'h_', 2)) ? substr($key, 2) : $key; // Case insensitive guard
    $resolvedKey = strtoupper($resolvedKey);

    if (str_ends_with($resolvedKey, '_HTML')) {
      $withoutHtmlSuffix = substr($resolvedKey, 0, -5);
      if (defined(I18n::class.'::'.$withoutHtmlSuffix)) {
        $resolvedKey = $withoutHtmlSuffix;
      }
    }

    if (defined(I18n::class.'::'.$resolvedKey)) {
      /** @var string $value */
      $value = constant(I18n::class.'::'.$resolvedKey);

      return $value;
    }

    return self::i18n($resolvedKey, $lang);
  }

  /**
   * Handles resolveLanguage operation.
   */
  private static function resolveLanguage(?string $lang): string
  {
    if (is_string($lang) && $lang !== '') {
      return $lang;
    }

    if (defined('PAYCAL_PAGE_LANGUAGE_OVERRIDE')) {
      return (string) PAYCAL_PAGE_LANGUAGE_OVERRIDE;
    }

    return defined('USER_LANGUAGE') ? USER_LANGUAGE : 'en';
  }
}

