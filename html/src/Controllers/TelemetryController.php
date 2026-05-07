<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain;
use PayCal\Domain\Authentication;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Infrastructure\RateControl\RateLimiter;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Telemetry\TelemetryAccessToken;
use PayCal\Infrastructure\Telemetry\TelemetryRepository;
use PayCal\Domain\TelemetryPolicy;
use PayCal\Domain\User;

/**
 * TelemetryController.php
 *
 * Purpose: Telemetry intake/controller boundary for client events, scoped
 * access tokens, rate-limited recording, and privacy-aware request metadata.
 *
 * Developer notes:
 * - Telemetry volume can be high; rate limiting and bounded payload behavior
 *   are part of the contract, not optional optimizations.
 * - Keep privacy-sensitive normalization at the controller boundary before data
 *   reaches repository storage.
 *
 * Architectural role:
 * - Entry-point controller for request handling, authorization enforcement,
 *   and response or render shaping at the web boundary.
 * - Domain policy, persistence rules, and side-effect orchestration should
 *   stay in collaborators rather than expanding controller state.
 *
 * @category   Controllers
 * @package    PayCal\Controllers
 * @subpackage HTTP
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */



/**
 * Telemetry API surface.
 *
 * Responsibilities:
 * - Validate and record client telemetry submissions.
 * - Scope access using telemetry tokens and request metadata.
 * - Prevent telemetry intake from becoming an unbounded write path.
 */
class TelemetryController
{
  /**
   * Handles serverString operation.
   */
  private static function serverString(string $key, string $default = ''): string
  {
    $value = $_SERVER[$key] ?? $default;
    return is_scalar($value) ? (string) $value : $default;
  }

  /**
   * Handles telemetrySubjectToken operation.
   */
  private static function telemetrySubjectToken(string $userUUID): string
  {
    return substr(hash('sha256', $userUUID . '|' . date('Y-m-d-H')), 0, 24);
  }

  /**
   * Handles networkClassToken operation.
   */
  private static function networkClassToken(): string
  {
    $ip = self::serverString('REMOTE_ADDR', 'unknown');

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      $parts = explode('.', $ip);
      if (count($parts) < 4) {
        return substr(hash('sha256', 'unknown|' . date('Y-m-d-H')), 0, 24);
      }

      $class = sprintf('%s.%s.%s.0/24', $parts[0], $parts[1], $parts[2]);

      return substr(hash('sha256', $class . '|' . date('Y-m-d-H')), 0, 24);
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
      $parts = explode(':', $ip);
      $class = implode(':', array_slice($parts, 0, 4)) . '::/64';

      return substr(hash('sha256', $class . '|' . date('Y-m-d-H')), 0, 24);
    }

    return substr(hash('sha256', 'unknown|' . date('Y-m-d-H')), 0, 24);
  }

  /**
   * @param array<string, mixed> $fields
   * @return array<string, mixed>
   */
  private static function scrubFields(array $fields): array
  {
    $blocked = [
      'user_uuid', 'uuid', 'userId', 'user_id',
      'ip', 'ip_address', 'email', 'phone', 'address', 'full_name',
    ];

    foreach ($blocked as $key) {
      if (array_key_exists($key, $fields)) {
        unset($fields[$key]);
      }
    }

    return $fields;
  }

  /**
   * @return array<string, mixed>
   */
  private static function normalizeFields(mixed $fields): array
  {
    if (!is_array($fields)) {
      return [];
    }

    $normalized = [];
    foreach ($fields as $key => $value) {
      if (is_string($key)) {
        $normalized[$key] = $value;
      }
    }

    return $normalized;
  }

  /**
   * Return a JSON payload without terminating process execution.
   *
   * @param array<string, mixed> $extra
   */
  private static function respond(string $status, string $message, int $httpCode, array $extra = []): void
  {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
  }

  /**
   * Handles recordEvent operation.
   */
  public static function recordEvent(): void
  {
    if (!Authentication::validateAndTouchSession()) {
      self::respond('error', '[Telemetry] Authentication required.', HttpStatus::HTTP_UNAUTHORIZED);

      return;
    }

    $userUUID = User::currentUUID();
    if ('' === $userUUID) {
      self::respond('error', '[Telemetry] Missing authenticated user context.', HttpStatus::HTTP_UNAUTHORIZED);

      return;
    }

    $rate = RateLimiter::checkTelemetryLimit($userUUID);
    if (!$rate['allowed']) {
      self::respond(
        'error',
        '[Telemetry] Rate limit exceeded. Maximum 90 events per minute.',
        HttpStatus::HTTP_TOO_MANY_REQUESTS,
        ['remaining_events' => $rate['remaining']]
      );

      return;
    }

    $raw = file_get_contents('php://input');
    if ($raw === false) {
      self::respond('error', '[Telemetry] Failed to read input.', HttpStatus::HTTP_BAD_REQUEST);

      return;
    }
    if (strlen($raw) > SystemConfig::MAX_STRING_LENGTH) {
      throw new \RuntimeException('Input exceeds maximum allowed length of ' . SystemConfig::MAX_STRING_LENGTH . ' bytes');
    }
    $input = json_decode($raw, true);
    if (!is_array($input)) {
      self::respond('error', '[Telemetry] Invalid JSON.', HttpStatus::HTTP_BAD_REQUEST);

      return;
    }
    $typeRaw = $input['type'] ?? '';
    $type = is_scalar($typeRaw) ? (string) $typeRaw : '';
    $fieldsRaw = $input['fields'] ?? [];
    if ('' === $type) {
      self::respond('error', '[Telemetry] Missing event type.', HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    // Keep telemetry key cardinality bounded and predictable.
    if (1 !== preg_match('/^[a-z0-9_.:-]{1,64}$/i', $type)) {
      self::respond('error', '[Telemetry] Invalid event type.', HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    if (!is_array($fieldsRaw)) {
      self::respond('error', '[Telemetry] Invalid fields payload.', HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $token = new TelemetryAccessToken(
      TelemetryPolicy::STREAM_PRODUCT,
      'product',
      'default',
      'event'
    );

    $authorization = TelemetryRepository::authorize($token, TelemetryPolicy::STREAM_PRODUCT);
    if (!$authorization['allowed']) {
      self::respond('error', '[Telemetry] Access denied.', HttpStatus::HTTP_FORBIDDEN);

      return;
    }

    TelemetryRepository::incrementEventCounter($type);

    $fields = self::normalizeFields($fieldsRaw);
    $safeFields = self::scrubFields($fields);
    $userAgent = self::serverString('HTTP_USER_AGENT', '');

    $streamMeta = TelemetryPolicy::describeStream(TelemetryPolicy::STREAM_PRODUCT);

    // Log pseudonymous telemetry event without raw user UUID/IP.
    $log = [
        'timestamp' => date('c'),
        'type' => $type,
      'fields' => $safeFields,
      'subject_token' => self::telemetrySubjectToken($userUUID),
      'network_token' => self::networkClassToken(),
      'user_agent_hash' => substr(hash('sha256', $userAgent), 0, 24),
      'stream' => TelemetryPolicy::STREAM_PRODUCT,
      'retention_days' => $streamMeta['retention_days'],
      'access_boundary' => $streamMeta['access_boundary'],
    ];

    error_log('[TELEMETRY] '.json_encode($log));
    self::respond('success', '[Telemetry] Event recorded.', HttpStatus::HTTP_OK, ['remaining_events' => $rate['remaining']]);
  }
}

// Route: /api/v1/telemetry/record
if (isset($_SERVER['REQUEST_METHOD']) && 'POST' === $_SERVER['REQUEST_METHOD']) {
  TelemetryController::recordEvent();

  exit;
}


