<?php declare(strict_types=1);

namespace PayCal\Observability;

use PayCal\Domain\Authentication;
use PayCal\Domain\User;

/**
 * Per-request context for Argus scoped capture and trace timelines.
 */
final class ArgusRequestContext
{
  private static string $traceId = '';
  private static string $spanId = '';
  private static string $requestId = '';
  private static string $route = '';
  private static string $userUuid = '';
  private static string $businessId = '';
  private static string $sessionHash = '';

  /**
   * Reset cached request context values for isolated tests.
   */
  public static function resetForTests(): void
  {
    self::$traceId = '';
    self::$spanId = '';
    self::$requestId = '';
    self::$route = '';
    self::$userUuid = '';
    self::$businessId = '';
    self::$sessionHash = '';
  }

  /**
   * Bootstrap request-scoped observability context for web requests.
   */
  public static function bootstrap(): void
  {
    if (PHP_SAPI === 'cli') {
      return;
    }

    self::$traceId = bin2hex(random_bytes(16));
    self::$spanId = substr(self::$traceId, 0, 16);
    self::$requestId = substr(hash('sha256', self::$traceId . '|req'), 0, 16);

    $uriRaw = $_SERVER['REQUEST_URI'] ?? '/';
    $uri = is_scalar($uriRaw) ? (string) $uriRaw : '/';
    $pathRaw = parse_url($uri, PHP_URL_PATH);
    $path = is_string($pathRaw) && $pathRaw !== '' ? $pathRaw : '/';
    self::$route = strtolower(trim($path));

    $businessIdRaw = $_GET['business_id'] ?? $_POST['business_id'] ?? '';
    self::$businessId = ConfigValue::string($businessIdRaw);
    self::refreshIdentity();
  }

  /**
   * Refresh cached authenticated identity values from the current request.
   */
  public static function refreshIdentity(): void
  {
    try {
      self::$userUuid = trim(User::currentUUID());
    } catch (\Throwable) {
      self::$userUuid = '';
    }

    try {
      self::$sessionHash = trim(Authentication::getCookie());
    } catch (\Throwable) {
      self::$sessionHash = '';
    }
  }

  /**
   * Return the active trace identifier.
   */
  public static function traceId(): string
  {
    return self::$traceId;
  }

  /**
   * Return the active span identifier.
   */
  public static function spanId(): string
  {
    return self::$spanId;
  }

  /**
   * Return the active request identifier.
   */
  public static function requestId(): string
  {
    return self::$requestId;
  }

  /**
   * Return the normalized request route.
   */
  public static function route(): string
  {
    return self::$route;
  }

  /**
   * Return the authenticated user UUID captured for this request.
   */
  public static function userUuid(): string
  {
    return self::$userUuid;
  }

  /**
   * Return the business identifier captured from request parameters.
   */
  public static function businessId(): string
  {
    return self::$businessId;
  }

  /**
   * Return the session hash captured for this request.
   */
  public static function sessionHash(): string
  {
    return self::$sessionHash;
  }

  /**
   * Build the capture scope used by trace-gate package filters.
   */
  public static function captureScope(): ArgusCaptureScope
  {
    return new ArgusCaptureScope(
      self::$userUuid,
      self::$businessId,
      self::$sessionHash,
      self::$requestId,
      self::$route,
    );
  }

  /**
   * Seed request context values in CLI tests.
   */
  public static function seedForTests(
    string $userUuid = '',
    string $businessId = '',
    string $sessionHash = '',
    string $requestId = 'test-request',
    string $route = '/test'
  ): void {
    if (PHP_SAPI !== 'cli') {
      throw new \LogicException('Argus request context can only be seeded in CLI tests.');
    }

    self::$userUuid = trim($userUuid);
    self::$businessId = trim($businessId);
    self::$sessionHash = trim($sessionHash);
    self::$requestId = trim($requestId);
    self::$route = strtolower(trim($route));

    if (self::$traceId === '') {
      self::$traceId = str_repeat('0', 32);
    }

    if (self::$spanId === '') {
      self::$spanId = substr(self::$traceId, 0, 16);
    }
  }
}
