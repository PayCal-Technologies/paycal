<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Constants\Keys;

final class EarlyAccessImmediateUi
{
  public const FEATURE_KEY = 'auth.immediate_ui';
  public const COOKIE_NAME = 'paycal_ea_immediate_ui';

  public static function pageEnabled(): bool
  {
    return (bool) SystemConfig::get('early_access.page_enabled');
  }

  public static function enrollmentEnabled(): bool
  {
    return (bool) SystemConfig::get('auth.immediate_ui.enrollment_enabled');
  }

  public static function runtimeEnabled(): bool
  {
    return (bool) SystemConfig::get('auth.immediate_ui.runtime_enabled');
  }

  public static function activationVersion(): int
  {
    return max(1, (int) SystemConfig::get('auth.immediate_ui.activation_version'));
  }

  public static function userHasPasskey(string $userUUID): bool
  {
    if ($userUUID === '') {
      return false;
    }

    return count(Database::smembers(Keys::webauthnUserCredentials($userUUID))) > 0;
  }

  public static function userInRollout(User $user): bool
  {
    $mode = (string) SystemConfig::get('auth.immediate_ui.rollout_mode');
    return match ($mode) {
      'all' => true,
      'percentage' => self::inPercentageRollout($user->user_uuid),
      'allowlist' => Database::sismember('early_access:auth.immediate_ui:allowlist', $user->user_uuid) === 1,
      default => User::isAdmin() || User::isManager(),
    };
  }

  /**
   * Eligibility that can be determined server-side for the signed-in settings UI.
   *
   * @return array{visible: bool, can_enable: bool, has_passkey: bool, enrolled: bool, runtime_enabled: bool, enrollment_enabled: bool, version: int, ttl_days: int, reason: string}
   */
  public static function settingsState(User $user): array
  {
    $hasPasskey = self::userHasPasskey($user->user_uuid);
    $pageEnabled = self::pageEnabled();
    $inRollout = self::userInRollout($user);
    $enrollmentEnabled = self::enrollmentEnabled();
    $runtimeEnabled = self::runtimeEnabled();
    $visible = $pageEnabled && $inRollout;
    $canEnable = $visible && $enrollmentEnabled && $hasPasskey;
    $reason = '';

    if (!$runtimeEnabled) {
      $reason = 'runtime_disabled';
    } elseif (!$enrollmentEnabled) {
      $reason = 'enrollment_closed';
    } elseif (!$hasPasskey) {
      $reason = 'missing_passkey';
    } elseif (!$visible) {
      $reason = 'not_eligible';
    }

    return [
      'visible' => $visible,
      'can_enable' => $canEnable,
      'has_passkey' => $hasPasskey,
      'enrolled' => self::hasValidActivationCookie(),
      'runtime_enabled' => $runtimeEnabled,
      'enrollment_enabled' => $enrollmentEnabled,
      'version' => self::activationVersion(),
      'ttl_days' => self::ttlDays(),
      'reason' => $reason,
    ];
  }

  public static function issueActivationCookie(): void
  {
    $expiresAt = time() + (self::ttlDays() * 86400);
    $payload = self::activationVersion() . '.' . $expiresAt;
    $value = $payload . '.' . self::signature($payload);

    setcookie(self::COOKIE_NAME, $value, [
      'expires' => $expiresAt,
      'path' => '/',
      'secure' => self::secureCookie(),
      'httponly' => true,
      'samesite' => 'Lax',
    ]);
  }

  public static function clearActivationCookie(): void
  {
    setcookie(self::COOKIE_NAME, '', [
      'expires' => time() - 3600,
      'path' => '/',
      'secure' => self::secureCookie(),
      'httponly' => true,
      'samesite' => 'Lax',
    ]);
  }

  public static function hasValidActivationCookie(): bool
  {
    $cookieValue = $_COOKIE[self::COOKIE_NAME] ?? null;
    $value = is_string($cookieValue) ? trim($cookieValue) : '';
    if ($value === '') {
      return false;
    }

    $parts = explode('.', $value);
    if (count($parts) !== 3) {
      self::clearActivationCookie();
      return false;
    }

    [$versionRaw, $expiresRaw, $signature] = $parts;
    if (!ctype_digit($versionRaw) || !ctype_digit($expiresRaw)) {
      self::clearActivationCookie();
      return false;
    }

    $payload = $versionRaw . '.' . $expiresRaw;
    if (!hash_equals(self::signature($payload), $signature)) {
      self::clearActivationCookie();
      return false;
    }

    if ((int) $versionRaw !== self::activationVersion() || (int) $expiresRaw <= time()) {
      self::clearActivationCookie();
      return false;
    }

    return true;
  }

  public static function signedOutAllowed(): bool
  {
    return self::runtimeEnabled() && self::hasValidActivationCookie();
  }

  private static function ttlDays(): int
  {
    return max(1, (int) SystemConfig::get('auth.immediate_ui.activation_ttl_days'));
  }

  private static function inPercentageRollout(string $userUUID): bool
  {
    $percent = max(0, min(100, (int) SystemConfig::get('auth.immediate_ui.rollout_percent')));
    if ($percent <= 0 || $userUUID === '') {
      return false;
    }
    if ($percent >= 100) {
      return true;
    }

    $bucket = hexdec(substr(hash('sha256', $userUUID), 0, 8)) % 100;
    return $bucket < $percent;
  }

  private static function signature(string $payload): string
  {
    return hash_hmac('sha256', $payload, self::signingKey());
  }

  private static function signingKey(): string
  {
    $envValue = $_ENV['PAYCAL_EARLY_ACCESS_SIGNING_KEY'] ?? null;
    $envKey = is_string($envValue) ? trim($envValue) : '';
    if ($envKey !== '') {
      return $envKey;
    }

    return hash('sha256', Environment::appHome() . '|' . Environment::appDomain() . '|early-access-immediate-ui');
  }

  private static function secureCookie(): bool
  {
    return Environment::appScheme() === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  }
}
