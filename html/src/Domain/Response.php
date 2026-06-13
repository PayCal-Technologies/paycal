<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Enums\HttpStatus;

/**
 * Response.php
 *
 * Unified HTTP response facade for controllers.
 *
 * Why this exists:
 * - Keep API and page handlers consistent for status code and payload shape.
 * - Ensure all terminal response paths end the request lifecycle explicitly.
 * - Provide one integration point for development-only observability injection.
 */

/**
 * Static response emitter for JSON, HTML, and redirects.
 *
 * Internal guarantees:
 * - JSON payload baseline is always `{status, message}` with optional merged extras.
 * - Lens payload injection only occurs in development mode.
 * - All public emitters terminate execution after sending headers/body.
 */
final class Response
{
  /**
   * Keep request termination in normal runtime, but not while PHPUnit is executing.
   */
  private static function shouldTerminate(): bool
  {
    return !defined('PHPUNIT_COMPOSER_INSTALL');
  }

  /**
   * Send a JSON API response with a status string, message, HTTP code, and optional extra payload.
   *
   * @param string               $status  Response status such as "success" or "error"
   * @param string               $message Human-readable message describing the result
   * @param int                  $code    HTTP status code to send (default 200)
   * @param array<string, mixed> $extra   Additional data to include in the JSON payload
   */
  public static function json(string $status, string $message, int $code = HttpStatus::HTTP_OK, array $extra = []): void
  {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');

    $payload = [
      'status' => $status,
      'success' => ($status === 'success'),
      'message' => $message,
    ];

    $payload['data'] = $extra;

    if ([] !== $extra) {
      // Keep flat fields for existing frontend consumers while preserving a data envelope.
      $payload = array_merge($payload, $extra);
    }

    // Include Lens observability data in development mode
    if (Environment::devSecurityDisabled()) {
      $payload['_lens'] = \PayCal\Observability\Lens::data();
    }

    /**
     * @psalm-taint-escape html
     * @psalm-taint-escape has_quotes
     */
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo $encoded;

    if (self::shouldTerminate()) {
      exit;
    }
  }

  /**
   * Success JSON response.
   *
   * @param array<string, mixed> $extra
   */
  public static function success(string $message, array $extra = [], int $code = HttpStatus::HTTP_OK): void
  {
    self::json('success', $message, $code, $extra);
  }

  /**
   * Error JSON response.
   *
   * @param array<string, mixed> $extra
   */
  public static function error(string $message, array $extra = [], int $code = HttpStatus::HTTP_BAD_REQUEST): void
  {
    self::json('error', $message, $code, $extra);
  }
}
