<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\SystemConfig;

/**
 * InputSanitizer.php
 *
 * Shared input normalization and sanitization helpers.
 *
 * Why this exists:
 * - Keep input hygiene rules centralized across controllers and services.
 * - Enforce maximum payload boundaries before persistence or downstream parsing.
 * - Reduce accidental unsafe output by normalizing text for storage and rendering.
 */

/**
 * Static input sanitation utility layer.
 *
 * Internal guarantees:
 * - String and array sanitation paths are deterministic and side-effect free.
 * - Oversized payloads fail fast via runtime exceptions.
 * - Helper entry points (`getString`, `postString`, `fromPost`, etc.) normalize
 *   superglobal access into typed, predictable return values.
 */
final class InputSanitizer
{
  /**
   * Sanitize a single string using built-in filters and regex whitelist.
   * Trims whitespace, encodes special characters, applies character whitelist.
   * Safe for storing in Redis and outputting in HTML/JSON.
   *
   * @param mixed $input Input value to sanitize (will be converted to string)
   *
   * @return string Sanitized string with safe characters only
   */
  public static function sanitizeString(mixed $input): string
  {
    $inputStr = is_string($input) ? $input : '';
    $input = trim($inputStr);
    $maxLen = SystemConfig::MAX_STRING_LENGTH;
    if (strlen($input) > $maxLen) {
      throw new \RuntimeException('InputSanitizer: input exceeds maximum allowed length of ' . $maxLen . ' bytes');
    }
    $input = (string) filter_var($input, FILTER_SANITIZE_SPECIAL_CHARS);
    $input = (string) htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (strlen($input) > $maxLen) {
      throw new \RuntimeException('InputSanitizer: input exceeds maximum allowed length after encoding of ' . $maxLen . ' bytes');
    }

    // Whitelist: letters, numbers, spaces, and select punctuation
    return preg_replace(
      '/[^a-zA-Z0-9 !@#$%^&*(),+\-_.\/]/u',
      '',
      $input
    ) ?? $input;
  }

  /**
   * Get sanitized string from $_GET.
   */
  public static function getString(string $variable): ?string
  {
    if (!isset($_GET[$variable])) {
      return null;
    }

    $value = is_string($_GET[$variable] ?? null) ? $_GET[$variable] : '';

    return self::sanitizeString($value);
  }

  /**
   * Get sanitized string from $_POST.
   */
  public static function postString(string $variable): string
  {
    if (!isset($_POST[$variable])) {
      return '';
    }

    $value = is_string($_POST[$variable] ?? null) ? $_POST[$variable] : '';

    return self::sanitizeString($value);
  }

  /**
   * Get raw string from $_POST with control characters stripped.
   * Use when payloads (e.g., JSON) must preserve punctuation.
   */
  public static function postRaw(string $variable): string
  {
    if (!isset($_POST[$variable])) {
      return '';
    }

    $value = is_string($_POST[$variable] ?? null) ? $_POST[$variable] : '';

    return self::stripControls(trim($value));
  }

  /**
   * Sanitize a base64 image data URL payload.
   *
   * Use case is intentionally narrow: image upload data URLs only.
   * Returns an empty string when input is empty (used to clear image).
   */
  public static function sanitizeBase64ImageDataUrl(mixed $input, int $maxLen = 20000): string
  {
    if (!is_scalar($input)) {
      return '';
    }

    $value = self::stripControls(trim((string) $input));
    if ($value === '') {
      return '';
    }

    if (strlen($value) > $maxLen) {
      throw new \RuntimeException('InputSanitizer: base64 image payload exceeds maximum allowed length of ' . $maxLen . ' bytes');
    }

    if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,([A-Za-z0-9+\/=]+)$/', $value, $matches)) {
      throw new \RuntimeException('InputSanitizer: invalid base64 image payload format');
    }

    $mime = strtolower((string) $matches[1]);
    $rawBase64 = (string) $matches[2];
    $decoded = base64_decode($rawBase64, true);
    if ($decoded === false) {
      throw new \RuntimeException('InputSanitizer: invalid base64 image payload encoding');
    }

    $canonicalBase64 = base64_encode($decoded);
    $normalizedMime = $mime === 'jpg' ? 'jpeg' : $mime;
    $normalized = 'data:image/' . $normalizedMime . ';base64,' . $canonicalBase64;

    if (strlen($normalized) > $maxLen) {
      throw new \RuntimeException('InputSanitizer: normalized base64 image payload exceeds maximum allowed length of ' . $maxLen . ' bytes');
    }

    return $normalized;
  }

  /**
   * Read and sanitize base64 image data URL from POST.
   */
  public static function postBase64ImageDataUrl(string $variable, int $maxLen = 20000): string
  {
    if (!isset($_POST[$variable])) {
      return '';
    }

    return self::sanitizeBase64ImageDataUrl($_POST[$variable], $maxLen);
  }

  /**
   * Get sanitized array from $_POST.
   *
   * @return array<mixed>
   */
  public static function postArray(string $variable): array
  {
    if (!isset($_POST[$variable]) || !is_array($_POST[$variable])) {
      return [];
    }

    /** @var array<mixed> $value */
    $value = $_POST[$variable];
    $maxLen = SystemConfig::MAX_STRING_LENGTH;
    $valueRaw = json_encode($value);
    if ($valueRaw !== false && strlen($valueRaw) > $maxLen) {
      throw new \RuntimeException('InputSanitizer: POST array input exceeds maximum allowed length of ' . $maxLen . ' bytes');
    }
    return self::sanitizeArray($value);
  }

  /**
   * Sanitize all values in $_POST.
   *
   * @return array<string, string>
   */
  public static function fromPost(): array
  {
    $maxLen = SystemConfig::MAX_STRING_LENGTH;
    $postRaw = json_encode($_POST);
    if ($postRaw !== false && strlen($postRaw) > $maxLen) {
      throw new \RuntimeException('InputSanitizer: POST input exceeds maximum allowed length of ' . $maxLen . ' bytes');
    }
    return self::sanitizeArray($_POST);
  }

  /**
   * Sanitize all values from HTTP DELETE headers.
   *
   * @return array<string, string>
   */
  public static function fromDelete(): array
  {
    return self::sanitizeArray((array) getallheaders());
  }

  /**
   * Sanitize an array (keys and values)
   * Recursively applies sanitizeString to each element.
   *
   * @param array<mixed, mixed> $array
   *
   * @return array<string, string>
   */
  public static function sanitizeArray(array $array): array
  {
    $maxDepth = SystemConfig::MAX_ARRAY_DEPTH;
    $sanitizeItem = function ($item, $depth = 1) use (&$sanitizeItem, $maxDepth) {
      if ($depth > $maxDepth) {
        throw new \RuntimeException('InputSanitizer: input array exceeds maximum recursion depth of ' . $maxDepth);
      }
      if (is_array($item)) {
        // @var array<mixed> $item
        return array_map(function ($i) use (&$sanitizeItem, $depth) {
          return $sanitizeItem($i, $depth + 1);
        }, $item);
      }
      return self::sanitizeString((string) $item);
    };

    /** @var array<int|string, string> $sanitizedKeys */
    $sanitizedKeys = array_map(function ($k) use ($sanitizeItem) { return $sanitizeItem($k); }, array_keys($array));

    /** @var array<int|string, string> $sanitizedValues */
    $sanitizedValues = array_map(function ($v) use ($sanitizeItem) { return $sanitizeItem($v); }, array_values($array));

    return array_combine($sanitizedKeys, $sanitizedValues) ?: [];
  }

  /**
   * Strip control characters from a string while preserving tabs and newlines.
   * Removes Unicode control characters and format characters that could cause issues.
   *
   * @param string $input Input string to process
   *
   * @return string String with control characters removed
   */
  public static function stripControls(string $input): string
  {
    // Remove all control characters except \t (0x09), \n (0x0A), \r (0x0D)
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input) ?? '';
  }

  /**
   * Sanitize a personal or site name
   * Allows letters, marks, numbers, spaces, and a
   * conservative set of punctuation.
   */
  public static function sanitizeName(string $input): string
  {
    $clean = self::sanitizeString($input);
    $clean = preg_replace('~[^\p{L}\p{M}\p{N}\p{Zs}\'’\-–—\.,&()/]~u', '', $clean) ?? $clean;

    return self::collapseSpaces($clean);
  }

  /**
   * Sanitize a work site name
   * Same as sanitizeName but also allows digits, slash, and ampersand.
   */
  public static function sanitizeSiteName(string $input): string
  {
    return (string) self::sanitizeName($input); // identical rule for now
  }

  /**
   * Sanitize free-text notes or addresses
   * Allows most printable Unicode except controls. Normalizes whitespace.
   */
  public static function sanitizeNotes(string $input): string
  {
    $normalized = preg_replace('/\r\n?/', "\n", trim($input)) ?? '';

    return self::collapseSpaces($normalized);
  }

  /**
   * Sanitize email address - Lowercases, Trims, Removes invalid characters, Ensures basic RFC-like structure.
   */
  public static function sanitizeEmail(string $input): string
  {
    $email = trim(strtolower($input));
    
    // Remove invalid characters (keep only alphanumeric, @, ., -, _, +)
    $email = preg_replace('/[^a-z0-9@.\-_+]/i', '', $email) ?? '';
    
    // Handle multiple @ signs: keep first part and last domain
    if (substr_count($email, '@') > 1) {
      $parts = explode('@', $email);
      $username = array_shift($parts); // First part
      $domain = array_pop($parts); // Last part
      $email = $username.'@'.$domain;
    }
    
    return $email;
  }

  /**
   * Sanitize IP address - Validates and returns a valid IP or 'unknown' if invalid.
   */
  public static function sanitizeIPAddress(mixed $input): string
  {
    if (is_string($input) && filter_var($input, FILTER_VALIDATE_IP)) {
      return $input;
    }

    return 'unknown';
  }

  /**
   * Collapse multiple whitespace to a single space.
   */
  private static function collapseSpaces(string $input): string
  {
    $clean = preg_replace('/\s+/u', ' ', $input) ?? '';

    return trim($clean);
  }
}
