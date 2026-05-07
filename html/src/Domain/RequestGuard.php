<?php declare(strict_types=1);

namespace PayCal\Domain;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Infrastructure\Resilience\RedisReliabilityService;
use PayCal\Infrastructure\RateControl\RateLimiter;
use PayCal\Infrastructure\Telemetry\SecurityLog;

/**
 * RequestGuard.php
 *
 * Request policy gateway for mutating HTTP endpoints.
 *
 * Why this exists:
 * - Centralize auth/session checks before controller logic runs.
 * - Enforce reliability and abuse controls consistently across routes.
 * - Keep controllers focused on business behavior instead of repeated guard code.
 *
 * Internal guarantees:
 * - Authenticated user and Redis-backed user record are required.
 * - Tier-0 Redis reliability gate can pause mutations during incidents.
 * - Per-user and per-IP rate limits are enforced for mutation-heavy paths.
 * - Input allowlists are respected and unknown fields are tracked for diagnostics.
 */

/**
 * Static request guard used by controllers before write operations.
 *
 * Integration contract:
 * - `filterPost()` returns only allowed keys or `false` after emitting a response.
 * - `deleteCheck()` is the canonical minimal ID preflight for destructive routes.
 * - Public guard methods are safe to call repeatedly within a request.
 */
final class RequestGuard
{
  /**
   * Framework/meta POST keys excluded from dropped-key diagnostics.
   *
   * @var array<string, true>
   */
  private const DIAGNOSTIC_EXCLUDED_POST_KEYS = [
    'csrf_token' => true,
    'username' => true,
  ];

  /**
   * Read a POST field as image data URL (base64) with strict sanitizer.
   */
  public static function imageData(string $field, int $maxLen = 20000): string
  {
    return InputSanitizer::postBase64ImageDataUrl($field, $maxLen);
  }

  /**
   * Verifies user is logged in and has a Redis user key.
   */
  public static function authCheck(): bool
  {
    $hash = Authentication::getCookie();
    if ($hash === '') {
      return false;
    }

    // Keep active sessions alive for guarded API calls.
    Authentication::touchSession($hash);

    $userKey = Keys::USER.':'.User::currentUUID();
    if (!Database::exists($userKey)) {
      return false;
    }

    return true;
  }

  /**
   * Check rate limit for calendar mutations (120 req/min).
   * Returns (allowed: bool, remaining: int) from RateLimiter.
   *
   * @return array{allowed: bool, remaining: int}
   */
  private static function checkRateLimit(): array
  {
    return RateLimiter::checkCalendarLimit(User::currentUUID());
  }

  /**
   * @return array{allowed: bool, remaining: int}
   */
  private static function checkIPRateLimit(): array
  {
    return RateLimiter::checkIPCalendarLimit(self::clientIP());
  }

  /**
   * Handles clientIP operation.
   */
  private static function clientIP(): string
  {
    $ip = Security::getClientIPAddress();

    return $ip !== '' ? $ip : '0.0.0.0';
  }

  /**
   * Validate and filter POST input based on controller-provided whitelists.
   *
   * @param array<int,string> $allowedStrings
   * @param array<int,string> $allowedArrays
  * @param array<int,string> $droppedKeys Optional output list of ignored POST keys
  * @param array<int,string> $base64ImageStrings Optional allowlist keys that must be treated as base64 image payloads
  * @param array<int,string> $rawStrings Optional allowlist keys that should use postRaw() only
   *
   * @return array<string, null|array<mixed>|bool|float|int|string>|false
   */
  public static function filterPost(
    array $allowedStrings = [],
    array $allowedArrays = [],
    array &$droppedKeys = [],
    array $base64ImageStrings = [],
    array $rawStrings = []
  ): array|false
  {
    $droppedKeys = [];

    if (!self::authCheck()) {
      \PayCal\Domain\Response::error('[PF] Auth failed.', [], HttpStatus::HTTP_UNAUTHORIZED);

      return false;
    }

    $mutationGate = RedisReliabilityService::allowMutations();
    if (!$mutationGate['allowed']) {
      \PayCal\Domain\Response::error(
        '[PF] Redis reliability guard active. Mutations temporarily disabled.',
        [
          'gate_code' => $mutationGate['code'],
          'gate_reason' => $mutationGate['message'],
          'breaker_state' => $mutationGate['breaker_state'],
        ],
        HttpStatus::HTTP_SERVICE_UNAVAILABLE
      );

      return false;
    }

    // Rate limiting check
    $rateLimitResult = self::checkRateLimit();
    if (!$rateLimitResult['allowed']) {
      SecurityLog::logRateLimitTriggered('user:calendar', User::currentUUID(), $rateLimitResult['remaining']);
      \PayCal\Domain\Response::error(
        '[PF] Rate limit exceeded. Maximum 120 requests per minute.',
        ['remaining_requests' => $rateLimitResult['remaining']],
        HttpStatus::HTTP_TOO_MANY_REQUESTS
      );

      return false;
    }

    $ipRateLimitResult = self::checkIPRateLimit();
    if (!$ipRateLimitResult['allowed']) {
      SecurityLog::logRateLimitTriggered('ip:calendar', self::clientIP(), $ipRateLimitResult['remaining']);
      \PayCal\Domain\Response::error(
        '[PF] IP rate limit exceeded. Please retry shortly.',
        ['remaining_requests' => $ipRateLimitResult['remaining']],
        HttpStatus::HTTP_TOO_MANY_REQUESTS
      );

      return false;
    }

    if ([] === $allowedStrings && [] === $allowedArrays) {
      \PayCal\Domain\Response::error('[PF] Invalid preflight configuration.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);

      return false;
    }

    $allowedArraySet = array_flip($allowedArrays);
    $allowedStringSet = array_flip($allowedStrings);
    $base64ImageSet = array_flip($base64ImageStrings);
    $rawStringSet = array_flip($rawStrings);

    $filtered = [];

    foreach ($_POST as $k => $v) {
      $key = is_string($k) ? $k : (string) $k;
      if (isset($allowedArraySet[$key])) {
        if (is_array($v)) {
          try {
            $filtered[$key] = InputSanitizer::sanitizeArray($v);
          } catch (\RuntimeException $e) {
            \PayCal\Domain\Response::error('[PF] Invalid POST array payload.', ['field' => $key], HttpStatus::HTTP_BAD_REQUEST);

            return false;
          }
        }
        continue;
      }

      if (isset($allowedStringSet[$key])) {
        try {
          if (isset($base64ImageSet[$key])) {
            $filtered[$key] = self::imageData($key);
          } elseif (isset($rawStringSet[$key])) {
            $filtered[$key] = InputSanitizer::postRaw($key);
          } else {
            $filtered[$key] = InputSanitizer::postString($key);
          }
        } catch (\RuntimeException $e) {
          \PayCal\Domain\Response::error('[PF] Invalid POST payload.', ['field' => $key], HttpStatus::HTTP_BAD_REQUEST);

          return false;
        }
        continue;
      }

      // Exclude framework/meta keys from diagnostics to reduce noise.
      if (!isset(self::DIAGNOSTIC_EXCLUDED_POST_KEYS[$key])) {
        $droppedKeys[] = $key;
      }
    }

    if ([] === $filtered) {
      \PayCal\Domain\Response::error('[PF] POST empty or invalid.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);

      return false;
    }

    return $filtered;
  }

  /**
   * Minimal ID validation for DELETE.
   */
  public static function deleteCheck(?string $param): false|string
  {
    if (!self::authCheck()) {
      \PayCal\Domain\Response::error('[PF] Auth failed.', [], HttpStatus::HTTP_UNAUTHORIZED);

      return false;
    }

    $mutationGate = RedisReliabilityService::allowMutations();
    if (!$mutationGate['allowed']) {
      \PayCal\Domain\Response::error(
        '[PF] Redis reliability guard active. Mutations temporarily disabled.',
        [
          'gate_code' => $mutationGate['code'],
          'gate_reason' => $mutationGate['message'],
          'breaker_state' => $mutationGate['breaker_state'],
        ],
        HttpStatus::HTTP_SERVICE_UNAVAILABLE
      );

      return false;
    }

    // Rate limiting check
    $rateLimitResult = self::checkRateLimit();
    if (!$rateLimitResult['allowed']) {
      SecurityLog::logRateLimitTriggered('user:calendar', User::currentUUID(), $rateLimitResult['remaining']);
      \PayCal\Domain\Response::error(
        '[PF] Rate limit exceeded. Maximum 120 requests per minute.',
        ['remaining_requests' => $rateLimitResult['remaining']],
        HttpStatus::HTTP_TOO_MANY_REQUESTS
      );

      return false;
    }

    $ipRateLimitResult = self::checkIPRateLimit();
    if (!$ipRateLimitResult['allowed']) {
      SecurityLog::logRateLimitTriggered('ip:calendar', self::clientIP(), $ipRateLimitResult['remaining']);
      \PayCal\Domain\Response::error(
        '[PF] IP rate limit exceeded. Please retry shortly.',
        ['remaining_requests' => $ipRateLimitResult['remaining']],
        HttpStatus::HTTP_TOO_MANY_REQUESTS
      );

      return false;
    }

    if (!$param) {
      \PayCal\Domain\Response::error('[PF] Missing ID.', [], HttpStatus::HTTP_BAD_REQUEST);

      return false;
    }

    return InputSanitizer::SanitizeString($param);
  }
}

