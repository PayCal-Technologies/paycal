<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;
use PayCal\Observability\Lens;

/**
 * ShadowTalon.php
 *
 * Purpose: Provide a global PHP fault-handling guardrail that converts
 * uncaught runtime failures into safe user responses and structured logs.
 *
 * Why this class exists:
 * - Centralize error, exception, and fatal-shutdown handling for the app.
 * - Prevent stack-trace leakage in public HTTP responses.
 * - Create a dedicated operational telemetry channel for runtime faults.
 *
 * What it offers:
 * - Unified registration for `set_error_handler`, `set_exception_handler`, and
 *   `register_shutdown_function`.
 * - Context-aware output format selection (HTML page vs JSON payload).
 * - Daily rotating JSONL logs under `/var/www/paycal/shared/logs/shadow_talon`.
 * - Severity normalization and cleaned trace formatting for faster triage.
 */

/**
 * ShadowTalon
 *
 * Runtime fault guardian for PayCal.
 *
 * Design principles:
 * - Fail safe: never throw from the handler while handling failures.
 * - User safety: return generic, friendly responses with correlation IDs.
 * - Operator clarity: log rich internal diagnostics out-of-band.
 * - Idempotent wiring: registration is safe to call multiple times.
 *
 * This class is intentionally static so registration is trivial during early
 * bootstrap and does not require container state to be available.
 */
final class ShadowTalon
{
  private const MSG_TRY_AGAIN = 'SHADOW_TALON_MESSAGE_TRY_AGAIN';

  private const SHADOW_TALON_LOG_DIR = '/var/www/paycal/shared/logs/shadow_talon';
  private const SHADOW_TALON_LOG_FILE_PREFIX = 'shadow_talon_';

  /** @var array<int, string> */
  private const ERROR_LEVEL_NAMES = [
    E_ERROR => 'ERROR',
    E_WARNING => 'WARNING',
    E_PARSE => 'PARSE',
    E_NOTICE => 'NOTICE',
    E_CORE_ERROR => 'CORE_ERROR',
    E_CORE_WARNING => 'CORE_WARNING',
    E_COMPILE_ERROR => 'COMPILE_ERROR',
    E_COMPILE_WARNING => 'COMPILE_WARNING',
    E_USER_ERROR => 'USER_ERROR',
    E_USER_WARNING => 'USER_WARNING',
    E_USER_NOTICE => 'USER_NOTICE',
    E_RECOVERABLE_ERROR => 'RECOVERABLE',
    E_DEPRECATED => 'DEPRECATED',
    E_USER_DEPRECATED => 'USER_DEPRECATED',
  ];

  private static bool $registered = false;
  private static bool $handling = false;

  /**
   * Handles register operation.
   */
  public static function register(): void
  {
    if (self::$registered) {
      return;
    }

    self::$registered = true;

    if (PHP_SAPI !== 'cli') {
      @ini_set('display_errors', '0');
      @ini_set('html_errors', '0');
    }

    set_error_handler([self::class, 'handleError']);
    set_exception_handler([self::class, 'handleException']);
    register_shutdown_function([self::class, 'handleShutdown']);
  }

  /**
   * Backward-compatible bootstrap alias.
   *
   * Older snippets use `ShadowTalon::init([...])`; the runtime handler is now
   * static and idempotent via `register()`, so config is intentionally ignored.
   *
   * @param array<string, mixed> $_config
   */
  public static function init(array $_config = []): void
  {
    self::register();
  }

  /**
   * Handles handleError operation.
   */
  public static function handleError(int $severity, string $message, string $file = '', int $line = 0): bool
  {
    if (!(error_reporting() & $severity)) {
      return false;
    }

    throw new \ErrorException($message, 0, $severity, $file, $line);
  }

  /**
   * Handles handleException operation.
   */
  public static function handleException(\Throwable $throwable): void
  {
    self::respond($throwable);
  }

  /**
   * Handles handleShutdown operation.
   */
  public static function handleShutdown(): void
  {
    $error = error_get_last();
    if (!is_array($error)) {
      return;
    }

    $severity = (int) $error['type'];
    $message = (string) $error['message'];
    $file = (string) $error['file'];
    $line = (int) $error['line'];

    $fatalErrors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($severity, $fatalErrors, true)) {
      return;
    }

    self::respond(new \ErrorException(
      $message,
      0,
      $severity,
      $file,
      $line
    ));
  }

  /**
   * @param array<string, mixed> $server
   */
  public static function wantsJsonRequest(array $server): bool
  {
    $requestUri = self::serverString($server, 'REQUEST_URI');
    $requestPath = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '');
    $accept = strtolower(self::serverString($server, 'HTTP_ACCEPT'));
    $contentType = strtolower(self::serverString($server, 'CONTENT_TYPE'));
    $requestedWith = strtolower(self::serverString($server, 'HTTP_X_REQUESTED_WITH'));

    if ($requestedWith === 'xmlhttprequest') {
      return true;
    }

    if (str_contains($accept, 'application/json') || str_contains($contentType, 'application/json')) {
      return true;
    }

    return preg_match('#^/(api|ws)(/|$)#', $requestPath) == 1;
  }

  /**
   * @return array<string, mixed>
   */
  public static function jsonPayload(string $errorId): array
  {
    return [
      'success' => false,
      'status' => 500,
      'message' => Strings::i18n(self::MSG_TRY_AGAIN),
      'error_id' => $errorId,
    ];
  }

  /**
   * Handles renderPublicHtml operation.
   */
  public static function renderPublicHtml(string $errorId): string
  {
    $baseUrl = self::baseUrl();
    $homeUrl = self::url($baseUrl, '/');
    $contactUrl = self::url($baseUrl, '/contact/');
    $cssVersion = (string) time();
    $escapedHomeUrl = htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8');
    $escapedContactUrl = htmlspecialchars($contactUrl, ENT_QUOTES, 'UTF-8');
    $escapedErrorId = htmlspecialchars($errorId, ENT_QUOTES, 'UTF-8');
    $escapedCssVersion = rawurlencode($cssVersion);
    $title = self::escapeI18n('SHADOW_TALON_TITLE');
    $requestNotFinished = self::escapeI18n('SHADOW_TALON_REQUEST_NOT_FINISHED');
    $referencePrefix = self::escapeI18n('SHADOW_TALON_REFERENCE_PREFIX');
    $returnHome = self::escapeI18n('SHADOW_TALON_RETURN_HOME');
    $contactSupport = self::escapeI18n('SHADOW_TALON_CONTACT_SUPPORT');

    return <<<HTML
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>{$title}</title>
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="stylesheet" href="/css/?v={$escapedCssVersion}">
  <link rel="stylesheet" href="/css/content/?v={$escapedCssVersion}">
  <link rel="stylesheet" href="/css/utilities/?v={$escapedCssVersion}">
  <link rel="stylesheet" href="/css/responsive/?v={$escapedCssVersion}">
</head>
<body>
  <main id="main" role="main" tabindex="-1" aria-labelledby="error-title">
    <article>
      <header>
        <h1 id="error-title">{$title}</h1>
      </header>
      <p>{$requestNotFinished}</p>
      <p>{$referencePrefix} <strong>{$escapedErrorId}</strong>.</p>
      <p>
        <a href="{$escapedHomeUrl}">{$returnHome}</a>
        <span aria-hidden="true"> | </span>
        <a href="{$escapedContactUrl}">{$contactSupport}</a>
      </p>
    </article>
  </main>
</body>
</html>
HTML;
  }

  /**
   * Handles escapeI18n operation.
   */
  private static function escapeI18n(string $key): string
  {
    return htmlspecialchars(Strings::i18n($key), ENT_QUOTES, 'UTF-8');
  }

  /**
   * Handles respond operation.
   */
  private static function respond(\Throwable $throwable): void
  {
    if (self::$handling) {
      self::emitFallback();
      exit(1);
    }

    self::$handling = true;
    $errorId = self::generateErrorId();
    self::report($throwable, $errorId);

    if (PHP_SAPI === 'cli') {
      fwrite(STDERR, "[{$errorId}] Uncaught " . get_class($throwable) . ': ' . $throwable->getMessage() . PHP_EOL);
      exit(1);
    }

    self::clearOutputBuffers();

    if (!headers_sent()) {
      http_response_code(500);
      header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
      header('Pragma: no-cache');
      header('Expires: 0');
      header('X-Error-Id: ' . $errorId);
    }

    if (self::wantsJsonRequest($_SERVER)) {
      if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
      }

      echo (string) json_encode(self::jsonPayload($errorId), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      exit(1);
    }

    if (!headers_sent()) {
      header('Content-Type: text/html; charset=UTF-8');
    }

    echo self::renderPublicHtml($errorId);
    exit(1);
  }

  /**
   * Handles report operation.
   */
  private static function report(\Throwable $throwable, string $errorId): void
  {
    $level = self::detectLevelName($throwable);
    $trace = self::cleanTrace($throwable);

    $summary = sprintf(
      '[ShadowTalon][%s][%s] Uncaught %s: %s in %s:%d',
      $errorId,
      $level,
      get_class($throwable),
      $throwable->getMessage(),
      $throwable->getFile(),
      $throwable->getLine()
    );

    self::writeShadowTalonLog($summary, $trace);
    self::writeStructuredReferenceLog($throwable, $errorId, $level, $summary);
    self::notifyIfCritical($level, $summary);

    try {
      Log::error($summary);
      Log::debug('[ShadowTalon][' . $errorId . '] ' . $trace);
      return;
    } catch (\Throwable) {
    }

    error_log($summary . PHP_EOL . $trace);
  }

  /**
   * Handles writeStructuredReferenceLog operation.
   */
  private static function writeStructuredReferenceLog(\Throwable $throwable, string $errorId, string $level, string $summary): void
  {
    $context = [
      'error_id' => $errorId,
      'level' => $level,
      'exception' => get_class($throwable),
      'message' => $throwable->getMessage(),
      'file' => $throwable->getFile(),
      'line' => $throwable->getLine(),
      'request_uri' => self::serverString($_SERVER, 'REQUEST_URI'),
      'request_method' => self::serverString($_SERVER, 'REQUEST_METHOD'),
      'host' => self::serverString($_SERVER, 'HTTP_HOST'),
      'client_ip' => Security::getClientIPAddress(),
      'sapi' => PHP_SAPI,
      'summary' => $summary,
    ];

    try {
      Log::error('[ShadowTalonRef] ' . (string) json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    } catch (\Throwable) {
      // Keep reporting path non-fatal.
    }

    try {
      Lens::boot('shadow_talon/error');
      Lens::increment('shadow_talon_errors');
      Lens::add('ShadowTalon Error Reference', $context, 'error');
    } catch (\Throwable) {
      // Keep reporting path non-fatal.
    }
  }

  /**
   * Handles writeShadowTalonLog operation.
   */
  private static function writeShadowTalonLog(string $summary, string $trace): void
  {
    try {
      if (!is_dir(self::SHADOW_TALON_LOG_DIR) && !@mkdir(self::SHADOW_TALON_LOG_DIR, 0o775, true) && !is_dir(self::SHADOW_TALON_LOG_DIR)) {
        return;
      }

      if (!is_writable(self::SHADOW_TALON_LOG_DIR)) {
        return;
      }

      $record = [
        'ts' => gmdate('c'),
        'summary' => $summary,
        'trace' => $trace,
      ];

      $logFile = self::SHADOW_TALON_LOG_DIR
        . '/'
        . self::SHADOW_TALON_LOG_FILE_PREFIX
        . gmdate('Y-m-d')
        . '.log';

      @file_put_contents(
        $logFile,
        (string) json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
        FILE_APPEND | LOCK_EX
      );
    } catch (\Throwable) {
      // Never throw from error-reporting code.
    }
  }

  /**
   * Handles detectLevelName operation.
   */
  private static function detectLevelName(\Throwable $throwable): string
  {
    if ($throwable instanceof \ErrorException) {
      return self::ERROR_LEVEL_NAMES[$throwable->getSeverity()] ?? 'UNKNOWN';
    }

    return 'EXCEPTION';
  }

  /**
   * Handles cleanTrace operation.
   */
  private static function cleanTrace(\Throwable $throwable): string
  {
    /** @var array<int, array<string, mixed>> $frames */
    $frames = $throwable->getTrace();
    if ($frames === []) {
      return '#0 {main}';
    }

    $lines = [];
    foreach ($frames as $i => $frame) {
      $file = isset($frame['file']) && is_string($frame['file']) ? $frame['file'] : '[internal]';
      $line = isset($frame['line']) && is_int($frame['line']) ? (string) $frame['line'] : '?';
      $function = is_string($frame['function']) ? $frame['function'] : 'unknown';
      $class = isset($frame['class']) && is_string($frame['class']) ? $frame['class'] : '';
      $type = isset($frame['type']) && is_string($frame['type']) ? $frame['type'] : '';
      $call = $class !== '' ? $class . $type . $function : $function;
      $lines[] = '#' . $i . ' ' . $call . '() called at [' . $file . ':' . $line . ']';
    }

    return implode(PHP_EOL, $lines);
  }

  /**
   * Handles notifyIfCritical operation.
   */
  private static function notifyIfCritical(string $level, string $_summary): void
  {
    // Hook for future alerting integrations (Slack/email/Sentry/etc).
    if (!in_array($level, ['ERROR', 'CORE_ERROR', 'COMPILE_ERROR', 'USER_ERROR', 'EXCEPTION'], true)) {
      return;
    }

    // Intentionally no-op until alert transport is configured.
  }

  /**
   * Handles clearOutputBuffers operation.
   */
  private static function clearOutputBuffers(): void
  {
    while (ob_get_level() > 0) {
      @ob_end_clean();
    }
  }

  /**
   * Handles emitFallback operation.
   */
  private static function emitFallback(): void
  {
    if (!headers_sent()) {
      http_response_code(500);
      header('Content-Type: text/plain; charset=UTF-8');
    }

    echo 'Internal Server Error';
  }

  /**
   * Handles generateErrorId operation.
   */
  private static function generateErrorId(): string
  {
    try {
      return gmdate('YmdHis') . '-' . bin2hex(random_bytes(4));
    } catch (\Throwable) {
      return gmdate('YmdHis') . '-' . substr(str_replace('.', '', uniqid('', true)), -8);
    }
  }

  /**
   * Handles baseUrl operation.
   */
  private static function baseUrl(): string
  {
    try {
      $baseUrl = Environment::appBaseURL();
      if ($baseUrl !== '') {
        return rtrim($baseUrl, '/');
      }
    } catch (\Throwable) {
    }

    $host = trim(self::serverString($_SERVER, 'HTTP_HOST'));
    if ($host === '') {
      return '';
    }

    $https = strtolower(self::serverString($_SERVER, 'HTTPS'));
    $scheme = ($https !== '' && $https !== 'off') ? 'https' : 'http';

    return $scheme . '://' . $host;
  }

  /**
   * @param array<string, mixed> $server
   */
  private static function serverString(array $server, string $key): string
  {
    if (!array_key_exists($key, $server)) {
      return '';
    }

    $value = $server[$key];
    if (is_string($value)) {
      return $value;
    }

    if (is_int($value) || is_float($value) || is_bool($value)) {
      return (string) $value;
    }

    return '';
  }

  /**
   * Handles url operation.
   */
  private static function url(string $baseUrl, string $path): string
  {
    if ($baseUrl === '') {
      return $path;
    }

    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
  }
}

