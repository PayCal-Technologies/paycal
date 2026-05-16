<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Infrastructure\Telemetry\SecurityLog;

/**
 * ProtectedMode.php
 *
 * Purpose: Session-level flag that tracks password-only login state when passkeys
 *          are enabled, used to surface security upgrade prompts.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Class ProtectedMode
 *
 * Tracks password-only warning state when passkeys are enabled.
 */
final class ProtectedMode
{
  private const SESSION_AUTH_METHOD = 'auth_method';
  private const SESSION_AUTH_STRENGTH = 'auth_strength';

  private const USER_WEBAUTHN_ENABLED = 'webauthn_enabled';
  private const USER_PASSWORD_ONLY_RISK = 'password_only_risk';
  private const USER_LAST_AUTH_METHOD = 'last_auth_method';

  /**
   * HTTP methods treated as mutating writes.
   *
   * @var array<string, true>
   */
  private const WRITE_METHOD_SET = [
    'POST' => true,
    'PUT' => true,
    'PATCH' => true,
    'DELETE' => true,
  ];

  /**
   * Sensitive routes that require stronger auth than password-only.
   *
   * @var array<string, true>
   */
  private const SENSITIVE_ROUTE_SET = [
    'account/security/update' => true,
    'account/delete' => true,
    'email/update' => true,
    'settings/password/update' => true,
    'account/encryption/update' => true,
  ];

  /**
   * Determine whether a user should be treated as password-restricted.
   *
   * @param string $userUUID User UUID
   *
   * @return bool True when passkeys are enabled for the account
   */
  public static function shouldRestrictPasswordSession(string $userUUID): bool
  {
    return self::isPasskeyEnabled($userUUID);
  }

  /**
   * Check whether passkeys are enabled for a user.
   *
   * @param string $userUUID User UUID
   *
   * @return bool True when the user has passkeys enabled
   */
  public static function isPasskeyEnabled(string $userUUID): bool
  {
    if ('' === $userUUID) {
      return false;
    }

    $key = Keys::USER . ':' . $userUUID;
    $flag = (string) Database::hget($key, self::USER_WEBAUTHN_ENABLED);
    $normalizedFlag = strtolower($flag);

    return '1' === $flag || 'true' === $normalizedFlag || 'yes' === $normalizedFlag;
  }

  /**
   * Mark a session and user record as password-only restricted.
   *
   * @param string $sessionHash Session hash
   * @param string $userUUID    User UUID
   */
  public static function markPasswordOnlyRestricted(string $sessionHash, string $userUUID): void
  {
    if ('' === $sessionHash || '' === $userUUID) {
      return;
    }

    $sessionKey = Keys::SESSION . ':' . $sessionHash;
    $userKey = Keys::USER . ':' . $userUUID;

    Database::hset($sessionKey, [
      self::SESSION_AUTH_METHOD => 'password',
      self::SESSION_AUTH_STRENGTH => 'password_only',
    ]);

    Database::hset($userKey, [
      self::USER_PASSWORD_ONLY_RISK => '1',
      self::USER_LAST_AUTH_METHOD => 'password',
    ]);

    SecurityLog::logProtectedModeActivated($userUUID, 'password_warning_only');
  }

  /**
   * Determine whether the current session is operating in password-only mode.
   *
   * @return bool True when the current session requires step-up authentication
   */
  public static function isCurrentSessionPasswordOnly(): bool
  {
    $hash = Authentication::getSessionHashFromCookie();
    if (null === $hash || !Authentication::sessionExists($hash)) {
      return false;
    }

    $sessionKey = Keys::SESSION . ':' . $hash;
    $strength = (string) Database::hget($sessionKey, self::SESSION_AUTH_STRENGTH);
    $normalizedStrength = strtolower($strength);

    return 'password_only' === $normalizedStrength;
  }

  /**
   * Return the current session auth-strength label.
   *
   * @return string Auth strength indicator for API responses
   */
  public static function getCurrentAuthStrength(): string
  {
    if (self::isCurrentSessionPasswordOnly()) {
      return 'password_only';
    }

    return 'standard';
  }

  /**
   * Block writes to protected routes until the user completes passkey step-up.
   *
   * @param string $method    HTTP method
   * @param string $routePath Normalized route path
   */
  public static function enforceStepUpForSensitiveRoute(string $method, string $routePath): void
  {
    if (!self::isWriteMethod($method)) {
      return;
    }

    $normalized = trim($routePath, '/');
    if (!self::isSensitiveRoute($normalized)) {
      return;
    }

    if (!self::isCurrentSessionPasswordOnly()) {
      return;
    }

    $userUUID = User::currentUUID();
    SecurityLog::logProtectedModeMutationBlocked($userUUID, $method, $normalized);

    Response::error(
      '[AUTH] Passkey confirmation required for this sensitive action.',
      [
        'auth_strength' => 'password_only',
        'step_up_required' => true,
        'recommended_method' => 'passkey',
        'route' => $normalized,
      ],
      HttpStatus::HTTP_FORBIDDEN
    );
  }

  /**
   * Determine whether the route is considered sensitive.
   *
   * @param string $routePath Route path without leading or trailing slashes
   *
   * @return bool True when the route requires step-up protection
   */
  private static function isSensitiveRoute(string $routePath): bool
  {
    return isset(self::SENSITIVE_ROUTE_SET[$routePath]);
  }

  /**
   * Determine whether the HTTP method can mutate protected state.
   *
   * @param string $method HTTP method
   *
   * @return bool True when the method is treated as a write
   */
  private static function isWriteMethod(string $method): bool
  {
    $normalized = strtoupper(trim($method));

    return isset(self::WRITE_METHOD_SET[$normalized]);
  }
}
