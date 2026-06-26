<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * HTTP cache-control helpers for static and semi-static asset responses.
 *
 * User-specific PHP bundles (core, earnings entrypoints) must stay no-store.
 * Deploy-versioned static files and shared manifests may use private caching.
 */
final class HttpCache
{
  /** nginx static-asset-cache.conf: versioned fonts under /fonts/ */
  public const FONT_TTL_SECONDS = 7776000;

  /** nginx static-asset-cache.conf: static .js files (not PHP bundles) */
  public const STATIC_JS_TTL_SECONDS = 3600;

  private const ONE_YEAR = 31536000;

  public static function sendNoStore(): void
  {
    if (headers_sent()) {
      return;
    }

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
  }

  /**
   * Long-lived cache for fingerprinted static assets (?v= deploy version or mtime suffix).
   */
  public static function sendPublicVersionedImmutable(int $maxAge = self::ONE_YEAR): void
  {
    if (headers_sent()) {
      return;
    }

    if (!self::hasVersionQuery()) {
      self::sendNoStore();

      return;
    }

    header('Cache-Control: public, max-age=' . $maxAge . ', immutable');
  }

  /**
   * Session-scoped cache for auth-gated payloads that are identical for all users
   * at a given deploy version (e.g. tax bracket manifest).
   */
  public static function sendPrivateDeployVersioned(int $maxAge = 86400): void
  {
    if (headers_sent()) {
      return;
    }

    if (!self::hasVersionQuery()) {
      self::sendNoStore();

      return;
    }

    header('Cache-Control: private, max-age=' . $maxAge);
    header('Vary: Cookie');
  }

  /**
   * Private cache validated by ETag derived from backing file metadata.
   *
   * @param non-empty-string $sourcePath
   */
  public static function sendPrivateWithFileEtag(string $sourcePath, int $maxAge = 86400): void
  {
    if (headers_sent()) {
      return;
    }

    if (!is_file($sourcePath)) {
      self::sendNoStore();

      return;
    }

    $mtime = filemtime($sourcePath);
    $size = filesize($sourcePath);
    if ($mtime === false || $size === false) {
      self::sendNoStore();

      return;
    }

    $etag = '"' . hash('sha256', $sourcePath . '|' . $mtime . '|' . $size) . '"';
    header('ETag: ' . $etag);
    header('Cache-Control: private, max-age=' . $maxAge);
    header('Vary: Cookie');

    $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    if (is_string($ifNoneMatch) && trim($ifNoneMatch) === $etag) {
      http_response_code(304);
      exit;
    }
  }

  public static function hasVersionQuery(): bool
  {
    $version = $_GET['v'] ?? '';

    return is_string($version) && trim($version) !== '';
  }
}
