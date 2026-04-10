<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Authentication;
use PayCal\Domain\Database;
use PayCal\Domain\Config\EncryptionConfig;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Log;
use PayCal\Domain\RedisReliabilityService;
use PayCal\Domain\Response;
use PayCal\Domain\Config\SystemConfig;

/**
 * KekController.php
 *
 * Purpose: Server-side KEK and wrapped-DEK storage controller for password-
 * derived encryption flows and compatibility-era envelope management.
 *
 * Developer notes:
 * - This code participates in key-material handling and must remain strict
 *   about rate limits, salt generation, and persisted wrapper validation.
 * - Keep crypto semantics in domain helpers/config where possible; this layer
 *   should primarily adapt HTTP requests into those operations.
 *
 * @category   Controllers
 * @package    PayCal\Controllers
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * KEK persistence API surface.
 *
 * Responsibilities:
 * - Issue salts and persist wrapped key material for authenticated users.
 * - Enforce bounded write/read behavior around legacy encryption flows.
 * - Coordinate storage responses used by client-side bootstrap code.
 */
class KekController
{
  private const WRITE_RATE_LIMIT_PER_MINUTE = 10;
  private const RATE_WINDOW_SECONDS = 60;
  private const SALT_TTL_SECONDS = 31536000;

  /**
   * Get authenticated user ID from session.
   *
   * @return string User UUID or empty string if not authenticated
   */
  private function getAuthenticatedUserId(): string
  {
    $hash = Authentication::getSessionHashFromCookie();
    if (null === $hash) {
      return '';
    }

    return (string) Authentication::getUserUUIDFromSession($hash);
  }

  /**
   * Verify identity consistency between session and request context.
   * Prevents session poisoning and identity mismatches.
   *
   * @param string $sessionUserId User ID from session
   * @return bool True if identity is consistent
   */
  private function verifyIdentityConsistency(string $sessionUserId): bool
  {
    // For now, session is the authoritative source
    // Future enhancement: cross-reference with request headers, JWT claims, etc.

    if ('' === $sessionUserId) {
      Log::warning('[KEK] Identity verification failed: empty session user ID');
      return false;
    }

    // Additional checks can be added here:
    // - Verify session hasn't been hijacked (IP consistency, user-agent)
    // - Check for concurrent session anomalies
    // - Validate against request context if available

    return true;
  }

  /**
   * GET /api/v1/system/kek/salt
   * Returns existing salt and dek_version or generates and stores a new salt.
   */
  #[Route('system/kek/salt', ['GET'])]
  /**
   * Handles getSalt operation.
   */
  public function getSalt(): void
  {
    // Check if encryption is enabled
    if (!\PayCal\Domain\Config\EncryptionConfig::isEnabled()) {
      \PayCal\Domain\Response::error('[KEK] Encryption is disabled.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_SERVICE_UNAVAILABLE);
      return;
    }

    if (!Authentication::validateAndTouchSession()) {
      \PayCal\Domain\Response::error('[KEK] User not authenticated.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_UNAUTHORIZED);

      return;
    }

    $userId = $this->getAuthenticatedUserId();
    if ('' === $userId) {
      \PayCal\Domain\Response::error('[KEK] User not authenticated.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_UNAUTHORIZED);

      return;
    }

    // Verify identity consistency
    if (!$this->verifyIdentityConsistency($userId)) {
      Log::error('[KEK] security.identity_mismatch', "user={$userId}");
      \PayCal\Domain\Response::error('[KEK] Identity verification failed.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    $key = Keys::userKekV1($userId);

    try {
      // If a record exists, return salt + dek_version
      if (\PayCal\Domain\Database::hexists($key, 'salt')) {
        $salt = (string) \PayCal\Domain\Database::hget($key, 'salt');
        $dekVersion = (int) (\PayCal\Domain\Database::hget($key, 'dek_version') ?: 1);

        \PayCal\Domain\Response::success('[KEK] Salt retrieved.', ['salt' => $salt, 'dek_version' => $dekVersion], \PayCal\Domain\Enums\HttpStatus::HTTP_OK);

        return;
      }

      // Otherwise generate a new salt and store it (do not create DEK)
        $mutationGate = RedisReliabilityService::allowMutations();
        if (!$mutationGate['allowed']) {
          \PayCal\Domain\Response::error(
            '[KEK] Mutation path temporarily disabled by reliability guard.',
            [
              'gate_code' => $mutationGate['code'],
              'gate_reason' => $mutationGate['message'],
              'breaker_state' => $mutationGate['breaker_state'],
            ],
            \PayCal\Domain\Enums\HttpStatus::HTTP_SERVICE_UNAVAILABLE
          );

          return;
        }

      $saltBytes = random_bytes(16);
      $salt = base64_encode($saltBytes);
      $now = time();

      \PayCal\Domain\Database::hset($key, ['salt' => $salt]);
      \PayCal\Domain\Database::hset($key, ['dek_version' => '1']);
      \PayCal\Domain\Database::hset($key, ['created_at' => (string) $now]);
      \PayCal\Domain\Database::hset($key, ['updated_at' => (string) $now]);

      // Set a conservative TTL to avoid stale autogenerated salts lingering forever
      try {
        \PayCal\Domain\Database::expire($key, self::SALT_TTL_SECONDS);
      } catch (\Throwable $e) {
      }

      RedisReliabilityService::recordMutationSuccess();
      \PayCal\Domain\Response::success('[KEK] Salt generated.', ['salt' => $salt, 'dek_version' => 1], \PayCal\Domain\Enums\HttpStatus::HTTP_OK);
    } catch (\Throwable $e) {
      RedisReliabilityService::recordMutationFailure('kek_salt_write_failed');
      Log::error('[KEK] getSalt failed: '.$e->getMessage());
      \PayCal\Domain\Response::error('[KEK] Failed to provide salt.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * POST /api/v1/system/kek/wrapped
   * Body: { "wrapped_dek": "..base64..", "dek_version": 1 }
   * Stores the wrapped DEK in the user hash. Does not overwrite salt.
   */
  #[Route('system/kek/wrapped', ['POST'])]
  /**
   * Handles postWrapped operation.
   */
  public function postWrapped(): void
  {
    // Check if encryption is enabled
    if (!\PayCal\Domain\Config\EncryptionConfig::isEnabled()) {
      \PayCal\Domain\Response::error('[KEK] Encryption is disabled.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_SERVICE_UNAVAILABLE);
      return;
    }

    if (!Authentication::validateAndTouchSession()) {
      \PayCal\Domain\Response::error('[KEK] User not authenticated.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_UNAUTHORIZED);

      return;
    }

    $userId = $this->getAuthenticatedUserId();
    if ('' === $userId) {
      \PayCal\Domain\Response::error('[KEK] User not authenticated.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_UNAUTHORIZED);

      return;
    }

    // Verify identity consistency
    if (!$this->verifyIdentityConsistency($userId)) {
      Log::error('[KEK] security.identity_mismatch', "user={$userId}");
      \PayCal\Domain\Response::error('[KEK] Identity verification failed.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    $key = Keys::userKekV1($userId);

    $raw = file_get_contents('php://input');
    if (false === $raw || '' === $raw) {
      // CLI child scripts may provide data on STDIN instead of php://input
      $raw = file_get_contents('php://stdin');
      if ($raw !== false && strlen($raw) > SystemConfig::MAX_STRING_LENGTH) {
        throw new \RuntimeException('Input exceeds maximum allowed length of ' . SystemConfig::MAX_STRING_LENGTH . ' bytes');
      }
    }
    if ($raw === false) {
      \PayCal\Domain\Response::error('[KEK] Failed to read input.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_BAD_REQUEST);

      return;
    }
    if (strlen($raw) > SystemConfig::MAX_STRING_LENGTH) {
      throw new \RuntimeException('Input exceeds maximum allowed length of ' . SystemConfig::MAX_STRING_LENGTH . ' bytes');
    }
    $body = json_decode($raw, true);
    if (!is_array($body) || empty($body['wrapped_dek'])) {
      \PayCal\Domain\Response::error('[KEK] Invalid request body.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $wrappedDekRaw = $body['wrapped_dek'];
    $wrappedB64 = is_scalar($wrappedDekRaw) ? (string) $wrappedDekRaw : '';
    $dekVersionRaw = $body['dek_version'] ?? null;
    $dekVersion = is_scalar($dekVersionRaw) ? (int) $dekVersionRaw : 1;

    // Validate base64 and length
    $decoded = base64_decode($wrappedB64, true);
    if (false === $decoded) {
      \PayCal\Domain\Response::error('[KEK] wrapped_dek is not valid base64.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $maxBytes = \PayCal\Domain\Config\SystemConfig::ENCRYPTED_BLOB_MAX_BYTES;
    if (strlen($decoded) > $maxBytes) {
      \PayCal\Domain\Response::error('[KEK] wrapped_dek exceeds maximum allowed size.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    try {
      $mutationGate = RedisReliabilityService::allowMutations();
      if (!$mutationGate['allowed']) {
        \PayCal\Domain\Response::error(
          '[KEK] Mutation path temporarily disabled by reliability guard.',
          [
            'gate_code' => $mutationGate['code'],
            'gate_reason' => $mutationGate['message'],
            'breaker_state' => $mutationGate['breaker_state'],
          ],
          \PayCal\Domain\Enums\HttpStatus::HTTP_SERVICE_UNAVAILABLE
        );

        return;
      }

      // Rate limit per-user per-minute for KEK writes
      $rateKey = "kek:v1:rate:{$userId}";
      $count = \PayCal\Domain\Database::incr($rateKey);
      if (1 === $count) {
        \PayCal\Domain\Database::expire($rateKey, self::RATE_WINDOW_SECONDS);
      }
      if ($count > self::WRITE_RATE_LIMIT_PER_MINUTE) {
        Log::info('[KEK] rate limit exceeded', "user={$userId} count={$count}");
        \PayCal\Domain\Response::error('[KEK] Too many requests.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_FORBIDDEN);

        return;
      }

      // Ensure salt exists; do not modify salt if present
      if (!\PayCal\Domain\Database::hexists($key, 'salt')) {
        \PayCal\Domain\Response::error('[KEK] Salt missing. Fetch salt first (GET /kek/salt).', [], \PayCal\Domain\Enums\HttpStatus::HTTP_BAD_REQUEST);

        return;
      }

      // Store wrapped_dek and dek_version, update timestamp
      $now = time();
      \PayCal\Domain\Database::hset($key, ['wrapped_dek' => $wrappedB64]);
      \PayCal\Domain\Database::hset($key, ['dek_version' => (string) $dekVersion]);
      \PayCal\Domain\Database::hset($key, ['updated_at' => (string) $now]);

      RedisReliabilityService::recordMutationSuccess();
      // Audit the write (no blob logging)
      Log::info('[KEK] Wrapped DEK stored', "user={$userId} dek_version={$dekVersion}");

      Response::success('[KEK] Wrapped DEK stored.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_OK);
    } catch (\Throwable $e) {
      RedisReliabilityService::recordMutationFailure('kek_wrapped_write_failed');
      Log::error('[KEK] postWrapped failed: '.$e->getMessage());
      \PayCal\Domain\Response::error('[KEK] Failed to store wrapped DEK.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * GET /api/v1/system/kek/wrapped
   * Returns the wrapped_dek and dek_version if present. 404 if missing.
   */
  #[Route('system/kek/wrapped', ['GET'])]
  /**
   * Handles getWrapped operation.
   */
  public function getWrapped(): void
  {
    // Check if encryption is enabled
    if (!\PayCal\Domain\Config\EncryptionConfig::isEnabled()) {
      \PayCal\Domain\Response::error('[KEK] Encryption is disabled.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_SERVICE_UNAVAILABLE);
      return;
    }

    if (!Authentication::validateAndTouchSession()) {
      \PayCal\Domain\Response::error('[KEK] User not authenticated.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_UNAUTHORIZED);

      return;
    }

    $userId = $this->getAuthenticatedUserId();
    if ('' === $userId) {
      \PayCal\Domain\Response::error('[KEK] User not authenticated.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_UNAUTHORIZED);

      return;
    }

    // Verify identity consistency
    if (!$this->verifyIdentityConsistency($userId)) {
      Log::error('[KEK] security.identity_mismatch', "user={$userId}");
      \PayCal\Domain\Response::error('[KEK] Identity verification failed.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    $key = Keys::userKekV1($userId);

    try {
      if (!Database::hexists($key, 'wrapped_dek')) {
        \PayCal\Domain\Response::error('[KEK] Wrapped DEK not found.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_NOT_FOUND);

        return;
      }

      $wrapped = (string) Database::hget($key, 'wrapped_dek');
      $dekVersion = (int) (Database::hget($key, 'dek_version') ?: 1);

      // Audit read (do not log blob)
      Log::info('[KEK] Wrapped DEK retrieved', "user={$userId} dek_version={$dekVersion}");

      Response::success('[KEK] Wrapped DEK.', ['wrapped_dek' => $wrapped, 'dek_version' => $dekVersion], \PayCal\Domain\Enums\HttpStatus::HTTP_OK);
    } catch (\Throwable $e) {
      Log::error('[KEK] getWrapped failed: '.$e->getMessage());
      \PayCal\Domain\Response::error('[KEK] Failed to retrieve wrapped DEK.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }
}

