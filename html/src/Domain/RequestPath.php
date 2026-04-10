<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * RequestPath.php
 *
 * Purpose: Request-path and HTTP-method helper for normalized route parsing,
 * subpath extraction, and request matching logic.
 *
 * Developer notes:
 * - This class centralizes lightweight request-path normalization helpers used
 *   across routing and page composition code.
 * - Keep it side-effect free and tolerant of incomplete server state.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Request metadata helper.
 *
 * Responsibilities:
 * - Normalize request method and URI access.
 * - Provide safe path/subpath extraction helpers.
 * - Support simple route/path matching without side effects.
 */
final class RequestPath
{
  /**
   * Get the request path without query string. Always starts with "/".
   *
   * @return string Request URI including query string, or '/' as default
   */
  public static function getFull(): string
  {
    return is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/';
  }

  /**
   * Get the HTTP request method.
   * - Normalizes to uppercase.
   * - Returns a safe default if missing or invalid.
   * - No exceptions; no side effects.
   *
   * @param string $default Default method if missing or invalid
   *
   * @return string HTTP method (GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD, or default)
   */
  public static function getMethod(string $default = 'GET'): string
  {
    $method = $_SERVER['REQUEST_METHOD'] ?? '';

    if (!is_string($method) || '' === $method) {
      return $default;
    }

    $method = strtoupper($method);

    // Allow only known HTTP methods to avoid garbage input
    switch ($method) {
      case 'GET':
      case 'POST':
      case 'PUT':
      case 'PATCH':
      case 'DELETE':
      case 'OPTIONS':
      case 'HEAD':
        return $method;

      default:
        return $default;
    }
  }

  /**
   * Check if request method is GET.
   *
   * @return bool True if HTTP method is GET
   */
  public static function isGet(): bool
  {
    return 'GET' === self::getMethod();
  }

  /**
   * Check if request method is POST.
   *
   * @return bool True if HTTP method is POST
   */
  public static function isPost(): bool
  {
    return 'POST' === self::getMethod();
  }

  /**
   * Check if request method is write-like (POST, PUT, PATCH, DELETE).
   *
   * @return bool True if method is a write operation
   */
  public static function isWrite(): bool
  {
    $m = self::getMethod();

    return 'POST' === $m || 'PUT' === $m || 'PATCH' === $m || 'DELETE' === $m;
  }

  /**
   * Extract requested year/month from the current URL.
   *
   * Checks both query string format (?2026-01) and path format (/2026-01-01).
   * Defaults to current year/month if no valid date found.
   *
   * @return array<string, int> Associative array with 'year' and 'month' keys
   */
  public static function getDate(): array
  {
    $uri = (string) self::getFull();

    // Check for query string format: ?2026-01 or ?2026-01-15
    if (str_contains($uri, '?')) {
      $queryString = substr($uri, strpos($uri, '?') + 1);
      // Remove any additional parameters after &
      $queryString = explode('&', $queryString)[0];

      if (preg_match('/^(\d{4})-(\d{2})(?:-\d{2})?$/', $queryString, $matches)) {
        $year = (int) $matches[1];
        $month = (int) $matches[2];

        return ['year' => $year, 'month' => $month];
      }
    }

    // Check for path format: /2026-01-01 (REQUEST_URI starts with /)
    // Split and filter out empty parts
    $parts = array_values(array_filter(explode('/', $uri), fn ($p) => '' !== $p && !str_starts_with($p, '?')));

    // Check first path segment for date format
    if (!empty($parts) && preg_match('/^(\d{4})-(\d{2})-\d{2}$/', $parts[0], $matches)) {
      $year = (int) $matches[1];
      $month = (int) $matches[2];

      return ['year' => $year, 'month' => $month];
    }

    return ['year' => (int) date('Y'), 'month' => (int) date('m')];
  }

  /**
   * Get PATH_INFO if set (normalized to start with "/"), else empty string.
   * Useful when FastCGI rewrites set PATH_INFO for routed files.
   *
   * @return string PATH_INFO from $_SERVER or empty string
   */
  public static function getPathInfo(): string
  {
    $pi = is_string($_SERVER['PATH_INFO'] ?? null) ? $_SERVER['PATH_INFO'] : '';
    if ('' === $pi) {
      return '';
    }

    return '/' === $pi[0] ? $pi : '/'.$pi;
  }

  /**
   * Get an indexed array of non-empty path segments.
   *
   * @param null|int $offset optional array_slice offset
   * @param null|int $length optional array_slice length
   *
   * @return array<int, string>
   */
  public static function getSegments(?int $offset = null, ?int $length = null): array
  {
    $raw = explode('/', trim(self::getFull(), '/'));
    $segments = array_values(array_filter($raw, static fn ($s) => '' !== $s));
    if (null === $offset && null === $length) {
      return $segments;
    }

    return array_slice($segments, $offset ?? 0, $length ?? null);
  }

  /**
   * Get a specific segment by zero-based index; returns default if missing.
   *
   * @param int    $index   Zero-based segment index
   * @param string $default Default value if segment not found
   *
   * @return string Segment value or default
   */
  public static function getSegment(int $index, string $default = ''): string
  {
    $segments = self::getSegments();

    return $segments[$index] ?? $default;
  }

  /**
   * Get a specific segment as integer; returns default if missing or non-numeric.
   *
   * @param int $index   Zero-based segment index
   * @param int $default Default value if segment not found or not numeric
   *
   * @return int Segment value as integer or default
   */
  public static function getIntSegment(int $index, int $default = 0): int
  {
    $seg = self::getSegment($index, '');

    return '' === $seg ? $default : (int) $seg;
  }

  /**
   * Return the last segment or default if none.
   *
   * @param string $default Default value if no segments found
   *
   * @return string Last path segment or default
   */
  public static function getLastSegment(string $default = ''): string
  {
    $segments = self::getSegments();
    if ([] === $segments) {
      return $default;
    }

    return $segments[count($segments) - 1];
  }

  /**
   * Check if a value appears as an exact segment.
   *
   * @param string $value         Segment value to search for
   * @param bool   $caseSensitive If false, compare case-insensitively (default false)
   *
   * @return bool True if segment found
   */
  public static function hasSegment(string $value, bool $caseSensitive = false): bool
  {
    if ('' === $value) {
      return false;
    }
    $segments = self::getSegments();
    if ($caseSensitive) {
      return in_array($value, $segments, true);
    }
    $needle = mb_strtolower($value);
    foreach ($segments as $s) {
      if (mb_strtolower($s) === $needle) {
        return true;
      }
    }

    return false;
  }

  /**
   * Get file extension from the path (no dot prefix).
   *
   * Extracts extension from REQUEST_URI using pathinfo(),
   * returns empty string if no extension found.
   *
   * @return string File extension without dot, or empty string
   */
  public static function getExtension(): string
  {
    return pathinfo(self::getFull(), PATHINFO_EXTENSION);
  }

  /**
   * Get the base filename (last segment without extension).
   *
   * Extracts the filename from the last path segment and removes its extension.
   *
   * @return string Base filename without extension, or empty string
   */
  public static function getBaseName(): string
  {
    $last = self::getLastSegment('');
    if ('' === $last) {
      return '';
    }
    $ext = pathinfo($last, PATHINFO_EXTENSION);
    if ('' !== $ext) {
      return substr($last, 0, -(strlen($ext) + 1));
    }

    return $last;
  }

  /**
   * Build a subpath from a starting segment index.
   *
   * Example: for "/a/b/c/d", from=1 => "/b/c/d"
   *
   * @param int $from Zero-based starting segment index
   *
   * @return string Subpath starting from segment at index
   */
  public static function getSubpath(int $from): string
  {
    $from = max(0, $from);
    $tail = self::getSegments($from);

    return '/'.implode('/', $tail);
  }

  /**
   * Simple pattern match against the full path.
   *
   * If pattern looks like a regex (format: /pattern/modifiers), uses preg_match.
   * Otherwise treats asterisk and question mark as wildcards (fnmatch or converted to regex).
   *
   * @param string $pattern Regex pattern or wildcard pattern
   *
   * @return bool True if path matches the pattern
   */
  public static function matchesPattern(string $pattern): bool
  {
    $path = self::getFull();

    $len = strlen($pattern);
    if ($len >= 2 && '/' === $pattern[0] && 0 === strrpos($pattern, '/') ? false : '/' === $pattern[$len - 1]) {
      // Regex pattern with trailing slash delimiter (allow modifiers after last "/")
      $lastSlash = strrpos($pattern, '/');
      if (false !== $lastSlash && $lastSlash > 0) {
        return (bool) @preg_match($pattern, $path);
      }
    }

    if (function_exists('fnmatch')) {
      return fnmatch($pattern, $path);
    }

    // Fallback: convert wildcards to regex
    $quoted = preg_quote($pattern, '/');
    $regex = '/^'.strtr($quoted, ['\*' => '.*', '\?' => '.']).'$/u';

    return (bool) preg_match($regex, $path);
  }

  /**
   * Check if path equals "/" (root with no segments).
   *
   * @return bool True if root path with no segments
   */
  public static function isRoot(): bool
  {
    return [] === self::getSegments();
  }

  /**
   * Return the number of path segments.
   *
   * @return int Number of segments in the current request path
   */
  public static function countSegments(): int
  {
    return count(self::getSegments());
  }
}
