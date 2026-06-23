<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * User session listing and revocation for account security settings.
 */
final class UserSessionService
{
  /**
   * @return array<int, array{hash: string, is_current: bool, created_at: string, last_activity: string, user_agent: string, last_ip: string, auth_strength: string}>
   */
  public static function listForUser(string $userUUID, ?string $currentSessionHash = null): array
  {
    if ($userUUID === '') {
      return [];
    }

    $sessions = [];
    foreach (Database::scanKeys(Keys::SESSION . ':*') as $sessionKey) {
      $key = (string) $sessionKey;
      if ($key === '' || Database::hget($key, 'user_uuid') !== $userUUID) {
        continue;
      }

      $hash = substr($key, strlen(Keys::SESSION . ':'));
      if ($hash === '') {
        continue;
      }

      $sessions[] = [
        'hash' => $hash,
        'is_current' => $currentSessionHash !== null && hash_equals($currentSessionHash, $hash),
        'created_at' => (string) Database::hget($key, 'created_at'),
        'last_activity' => (string) (Database::hget($key, 'last_activity') ?: Database::hget($key, 'last_signin')),
        'user_agent' => (string) Database::hget($key, 'user_agent'),
        'last_ip' => (string) Database::hget($key, 'last_ip'),
        'auth_strength' => (string) Database::hget($key, 'auth_strength'),
      ];
    }

    usort($sessions, static function (array $a, array $b): int {
      return (int) $b['last_activity'] <=> (int) $a['last_activity'];
    });

    return $sessions;
  }

  /**
   * Revoke other sessions.
   */
  public static function revokeOtherSessions(string $userUUID, string $currentSessionHash): int
  {
    if ($userUUID === '' || $currentSessionHash === '') {
      return 0;
    }

    $revoked = 0;
    foreach (Database::scanKeys(Keys::SESSION . ':*') as $sessionKey) {
      $key = (string) $sessionKey;
      if ($key === '' || Database::hget($key, 'user_uuid') !== $userUUID) {
        continue;
      }

      $hash = substr($key, strlen(Keys::SESSION . ':'));
      if ($hash === '' || hash_equals($hash, $currentSessionHash)) {
        continue;
      }

      Authentication::destroySession($hash);
      ++$revoked;
    }

    return $revoked;
  }
}
