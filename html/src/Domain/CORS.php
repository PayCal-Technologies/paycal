<?php declare(strict_types=1);

namespace PayCal\Domain;
use PayCal\Domain\Config\Environment;

/**
 * CORS.php
 *
 * Purpose: Define the CORS class for PayCal\Domain.
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
  /**
   * Handles CORS origin validation and response headers.
   * Allows requests only from approved PayCal domains.
   * Adds the Vary: Origin header for cache correctness.
   *
   * @return void
   */
  public static function handleORIGIN(): void
  {
    $origin = $_SERVER['SERVER_NAME'] ?? '';
    if ('paycal.app' === $origin || 'www.paycal.app' === $origin) {
      header("Access-Control-Allow-Origin: https://{$origin}");
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
      header('Access-Control-Allow-Origin: '.Environment::appDomain());
      header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
      header('Access-Control-Allow-Headers: Content-Type, X-Resource-ID');
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

  /**
   * Sends the HTTP Content-Disposition header for file responses.
   * Defines whether content should be displayed inline or downloaded.
   *
   * @param string $disposition either "inline" or "attachment"
   * @param string $filename    suggested filename for the browser
   * @return void
   */
  public static function renderContentDisposition(string $disposition, string $filename): void
  {
    $safeFilename = preg_replace('/[^a-zA-Z0-9._\-]/', '_', basename($filename));
    header("Content-Disposition: {$disposition}; filename=\"{$safeFilename}\"");
  }
}
