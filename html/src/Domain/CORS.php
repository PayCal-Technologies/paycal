<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * CORS.php
 *
 * Purpose: Cross-origin request validator and response-header applier for the
 *          PayCal API and page surface.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain
 *
 * @author   Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license  Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Class CORS.
 *
 * Cross-Origin Resource Sharing (CORS) security handler.
 * Validates request origins against approved PayCal domains and applies appropriate response headers.
 * Manages Vary headers for cache correctness across different origins.
 */
class CORS
{
  /** @var list<string> */
  private const ALLOWED_ORIGINS = [
    'https://paycal.app',
    'https://www.paycal.app',
  ];

  /**
   * Return the request origin only when it is explicitly trusted.
   */
  public static function allowedOrigin(string $origin): ?string
  {
    $origin = trim($origin);

    return in_array($origin, self::ALLOWED_ORIGINS, true) ? $origin : null;
  }

  /**
   * Handles CORS origin validation and response headers.
   * Allows requests only from approved PayCal domains.
   * Adds the Vary: Origin header for cache correctness.
   *
   * @return void
   */
  public static function handleORIGIN(): void
  {
    $origin = self::allowedOrigin(self::requestOrigin());
    if ($origin !== null) {
      header("Access-Control-Allow-Origin: {$origin}");
      header('Vary: Origin');
    }
  }

  /**
   * Handles preflight (OPTIONS) HTTP requests.
   * Sends CORS headers defining allowed origins, methods, and headers.
   *
   * @return void
   */
  public static function handleOPTIONS(): void
  {
    if ('OPTIONS' === $_SERVER['REQUEST_METHOD']) {
      http_response_code(204);
      $origin = self::allowedOrigin(self::requestOrigin());
      if ($origin !== null) {
        header("Access-Control-Allow-Origin: {$origin}");
      }
      header('Vary: Origin');
      header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
      header('Access-Control-Allow-Headers: Content-Type, X-Resource-ID');
      exit;
    }
  }

  /**
   * Sends the HTTP Content-Type header for the response.
   *
   * @param string $type MIME type to set, e.g., "application/json".
   * @return void
   */
  public static function renderContentType(string $type): void
  {
    header("Content-Type: {$type}");
  }

  private static function requestOrigin(): string
  {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    return is_string($origin) ? $origin : '';
  }
}
