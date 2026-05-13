<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Auth;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;

/**
 * CapabilityTokenService.php
 *
 * Purpose: Issue and validate short-lived capability tokens for sensitive,
 * auditable actions that require replay protection and bounded lifetime.
 *
 * Developer notes:
 * - Token namespace normalization is part of the security contract.
 * - Keep replay handling and TTL semantics explicit and stable for callers.
 *
 * Architectural role:
 * - Reusable domain security service for issuing and validating capability
 *   tokens across mutation-sensitive workflows.
 * - Encapsulates token replay and lifetime policy outside the HTTP layer.
 *
 * @category   Infrastructure
 * @package    PayCal\Infrastructure\Auth
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * CapabilityTokenService
 *
 * Mints and validates one-shot capability tokens for high-risk mutations.
 */
final class CapabilityTokenService
{
  private const TTL_SECONDS = 120;
  private const REPLAY_TTL_SECONDS = 300;

  /**
   * Normalize action names to a strict, auditable token namespace.
   */
  public static function normalizeAction(string $action): string
  {
    $normalized = strtolower(trim($action));
    if ($normalized === '') {
      return '';
    }

    if (!preg_match('/^[a-z0-9._-]{1,64}$/', $normalized)) {
      return '';
    }

    return $normalized;
  }

  /**
   * @return array{token: string, action: string, expires_in: int, expires_at: int}
   */
  public static function issue(string $action, string $userUuid, string $sessionHash): array
  {
    $normalizedAction = self::normalizeAction($action);
    if ($normalizedAction === '') {
      throw new \InvalidArgumentException('Invalid capability action.');
    }

    if ($userUuid === '' || $sessionHash === '') {
      throw new \InvalidArgumentException('Capability issuance requires user and session context.');
    }

    $token = bin2hex(random_bytes(24));
    $expiresAt = time() + self::TTL_SECONDS;

    $key = Keys::capabilityToken($userUuid, $token);
    Database::hsetex($key, [
      'user_uuid' => $userUuid,
      'session_hash' => $sessionHash,
      'action' => $normalizedAction,
      'issued_at' => (string) time(),
      'expires_at' => (string) $expiresAt,
    ], self::TTL_SECONDS);

    return [
      'token' => $token,
      'action' => $normalizedAction,
      'expires_in' => self::TTL_SECONDS,
      'expires_at' => $expiresAt,
    ];
  }

  /**
   * @return array{ok: bool, code: string, message: string}
   */
  public static function consume(string $token, string $action, string $userUuid, string $sessionHash): array
  {
    $token = trim($token);
    $normalizedAction = self::normalizeAction($action);

    if ($token === '') {
      return [
        'ok' => false,
        'code' => 'CAPABILITY_MISSING',
        'message' => 'Capability token is required.',
      ];
    }

    if ($normalizedAction === '') {
      return [
        'ok' => false,
        'code' => 'CAPABILITY_ACTION_INVALID',
        'message' => 'Capability action is invalid.',
      ];
    }

    if (Database::exists(Keys::capabilityReplay($token))) {
      return [
        'ok' => false,
        'code' => 'CAPABILITY_REPLAY',
        'message' => 'Capability token was already consumed.',
      ];
    }

    $key = Keys::capabilityToken($userUuid, $token);
    $stored = Database::hgetall($key);
    if ($stored === []) {
      return [
        'ok' => false,
        'code' => 'CAPABILITY_UNKNOWN',
        'message' => 'Capability token is unknown or expired.',
      ];
    }

    $storedUser = (string) ($stored['user_uuid'] ?? '');
    $storedSession = (string) ($stored['session_hash'] ?? '');
    $storedAction = (string) ($stored['action'] ?? '');
    $expiresAt = (int) ($stored['expires_at'] ?? '0');

    if (!hash_equals($storedUser, $userUuid) || !hash_equals($storedSession, $sessionHash)) {
      Database::unlink($key);

      return [
        'ok' => false,
        'code' => 'CAPABILITY_SCOPE_MISMATCH',
        'message' => 'Capability token scope mismatch.',
      ];
    }

    if (!hash_equals($storedAction, $normalizedAction)) {
      Database::unlink($key);

      return [
        'ok' => false,
        'code' => 'CAPABILITY_ACTION_MISMATCH',
        'message' => 'Capability token action mismatch.',
      ];
    }

    if ($expiresAt <= time()) {
      Database::unlink($key);

      return [
        'ok' => false,
        'code' => 'CAPABILITY_EXPIRED',
        'message' => 'Capability token expired.',
      ];
    }

    Database::unlink($key);
    Database::set(Keys::capabilityReplay($token), '1', self::REPLAY_TTL_SECONDS);

    return [
      'ok' => true,
      'code' => 'OK',
      'message' => 'Capability token accepted.',
    ];
  }
}
