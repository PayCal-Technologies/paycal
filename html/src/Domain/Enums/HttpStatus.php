<?php declare(strict_types=1);

namespace PayCal\Domain\Enums;

/**
 * HttpStatus.php
 *
 * Purpose: Define the HttpStatus class for PayCal\Domain\Enums.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Enums
 * @package    PayCal\Domain\Enums
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */
/**
 * HttpStatus constants.
 */
final class HttpStatus
{
  // Success codes
  public const HTTP_OK = 200;
  public const HTTP_CREATED = 201;

  // Redirect codes
  public const HTTP_MOVED_PERMANENTLY = 301;
  public const HTTP_FOUND = 302;
  public const HTTP_SEE_OTHER = 303;
  public const HTTP_TEMPORARY_REDIRECT = 307;
  public const HTTP_PERMANENT_REDIRECT = 308;

  // Client error codes
  public const HTTP_BAD_REQUEST = 400;
  public const HTTP_UNAUTHORIZED = 401;
  public const HTTP_FORBIDDEN = 403;
  public const HTTP_NOT_FOUND = 404;
  public const HTTP_METHOD_NOT_ALLOWED = 405;
  public const HTTP_TOO_MANY_REQUESTS = 429;
  public const HTTP_UNPROCESSABLE = 422;

  // Server error codes
  public const HTTP_INTERNAL_SERVER_ERROR = 500;
  public const HTTP_SERVICE_UNAVAILABLE = 503;
}
