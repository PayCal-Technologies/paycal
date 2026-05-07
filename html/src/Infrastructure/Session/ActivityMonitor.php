<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Session;

use PayCal\Domain\Authentication;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Security;

/**
 * ActivityMonitor.php
 *
 * Purpose: Assemble account activity snapshots from session, device, and
 * request metadata for authenticated user-facing security views.
 *
 * Developer notes:
 * - This class reads from session and request state that users interpret as
 *   authoritative account activity.
 * - Preserve conservative normalization so incomplete metadata degrades safely.
 *
 * Architectural role:
 * - Infrastructure service that assembles security-facing activity views from
 *   request, session, and persisted metadata.
 * - Encapsulates activity snapshot shaping outside the HTTP layer.
 *
 * @category   Infrastructure
 * @package    PayCal\Infrastructure\Session
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * ActivityMonitor
 *
 * Builds account activity snapshots for authenticated users, including
 * current login details, active session metadata, and browser/device details.
 */
final class ActivityMonitor
{
  /**
   * Build an activity snapshot for a user.
   *
   * @return array<string, mixed>
   */
  public static function snapshotForUser(string $userUUID): array
  {
    $currentSessionHash = Authentication::getSessionHashFromCookie() ?? '';
    $sessions = self::loadUserSessions($userUUID, $currentSessionHash);
    $currentSession = self::resolveCurrentSession($sessions, $currentSessionHash);

    $currentIp = Security::getClientIPAddress();
    $currentUserAgent = self::requestUserAgent();
    $currentUserAgent = $currentUserAgent !== ''
      ? $currentUserAgent
      : self::asString($currentSession['user_agent'] ?? '');
    $browser = self::parseUserAgent($currentUserAgent);

    return [
      'current_login' => [
        'ip_address' => self::asString($currentSession['last_ip'] ?? $currentIp),
        'first_ip' => self::asString($currentSession['first_ip'] ?? ''),
        'signed_in_at' => self::asInt($currentSession['created_at'] ?? 0),
        'last_signin_at' => self::asInt($currentSession['last_signin'] ?? 0),
        'last_activity_at' => self::asInt($currentSession['last_activity'] ?? 0),
        'auth_method' => self::asString($currentSession['auth_method'] ?? 'unknown'),
        'auth_strength' => self::asString($currentSession['auth_strength'] ?? 'unknown'),
        'session_fingerprint' => self::asString($currentSession['session_fingerprint'] ?? ''),
        'session_ttl_seconds' => self::asInt($currentSession['ttl_seconds'] ?? 0),
      ],
      'browser' => [
        'user_agent' => $currentUserAgent,
        'browser_name' => $browser['browser_name'],
        'browser_version' => $browser['browser_version'],
        'os_name' => $browser['os_name'],
        'device_type' => $browser['device_type'],
        'platform' => self::asString($_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ?? ''),
        'language' => self::asString($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''),
      ],
      'session_data' => [
        'active_session_count' => count($sessions),
        'current_session_fingerprint' => self::sessionFingerprint($currentSessionHash),
        'sessions' => $sessions,
      ],
    ];
  }

  /**
   * @return array<int, array<string, string|int|bool>>
   */
  private static function loadUserSessions(string $userUUID, string $currentSessionHash): array
  {
    if ($userUUID === '') {
      return [];
    }

    $sessions = [];
    foreach (Database::scanKeys(Keys::SESSION . ':*') as $sessionKey) {
      $key = (string) $sessionKey;
      if ($key === '') {
        continue;
      }

      $sessionData = Database::hgetall($key);
      if (empty($sessionData)) {
        continue;
      }

      if (self::asString($sessionData['user_uuid'] ?? '') !== $userUUID) {
        continue;
      }

      $sessionHash = str_starts_with($key, Keys::SESSION . ':')
        ? substr($key, strlen(Keys::SESSION . ':'))
        : '';

      $createdAt = self::asInt($sessionData['created_at'] ?? 0);
      $lastSignin = self::asInt($sessionData['last_signin'] ?? 0);
      $lastActivity = self::asInt($sessionData['last_activity'] ?? $lastSignin);

      $sessions[] = [
        'session_fingerprint' => self::sessionFingerprint($sessionHash),
        'is_current' => $sessionHash !== '' && hash_equals($sessionHash, $currentSessionHash),
        'created_at' => $createdAt,
        'last_signin' => $lastSignin,
        'last_activity' => $lastActivity,
        'first_ip' => self::asString($sessionData['first_ip'] ?? ''),
        'last_ip' => self::asString($sessionData['last_ip'] ?? ''),
        'auth_method' => self::asString($sessionData['auth_method'] ?? 'unknown'),
        'auth_strength' => self::asString($sessionData['auth_strength'] ?? 'unknown'),
        'credential_id_present' => self::asString($sessionData['credential_id'] ?? '') !== '',
        'recovery_pending' => self::asString($sessionData['recovery_pending'] ?? '') === '1',
        'ttl_seconds' => max(0, Database::ttl($key)),
        'user_agent' => self::asString($sessionData['user_agent'] ?? ''),
      ];
    }

    usort(
      $sessions,
      static fn (array $a, array $b): int => (int) $b['last_activity'] <=> (int) $a['last_activity']
    );

    return $sessions;
  }

  /**
   * @param array<int, array<string, string|int|bool>> $sessions
   * @return array<string, string|int|bool>
   */
  private static function resolveCurrentSession(array $sessions, string $currentSessionHash): array
  {
    foreach ($sessions as $session) {
      if (($session['is_current'] ?? false) === true) {
        return $session;
      }
    }

    if ($currentSessionHash !== '') {
      return ['session_fingerprint' => self::sessionFingerprint($currentSessionHash)];
    }

    return [];
  }

  /**
   * Generate a short non-sensitive identifier for a session hash.
   */
  private static function sessionFingerprint(string $sessionHash): string
  {
    if ($sessionHash === '') {
      return '';
    }

    return substr(hash('sha256', $sessionHash), 0, 12);
  }

  /**
   * Read and normalize the current request User-Agent header.
   */
  private static function requestUserAgent(): string
  {
    $raw = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (!is_scalar($raw)) {
      return '';
    }

    $userAgent = trim((string) $raw);
    if ($userAgent === '') {
      return '';
    }

    return substr($userAgent, 0, 512);
  }

  /**
   * @return array{browser_name:string,browser_version:string,os_name:string,device_type:string}
   */
  private static function parseUserAgent(string $userAgent): array
  {
    $ua = strtolower($userAgent);

    $browserName = 'Unknown';
    $browserVersion = '';
    $browserPatterns = [
      'edg' => 'Edge',
      'chrome' => 'Chrome',
      'firefox' => 'Firefox',
      'safari' => 'Safari',
      'opr' => 'Opera',
    ];

    foreach ($browserPatterns as $token => $name) {
      if (!str_contains($ua, $token)) {
        continue;
      }

      $browserName = $name;
      if (preg_match('/' . preg_quote($token, '/') . '\\/([0-9.]+)/i', $userAgent, $matches) === 1) {
        $browserVersion = (string) $matches[1];
      }
      break;
    }

    $osName = 'Unknown';
    if (str_contains($ua, 'windows')) {
      $osName = 'Windows';
    } elseif (str_contains($ua, 'mac os x') || str_contains($ua, 'macintosh')) {
      $osName = 'macOS';
    } elseif (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ios')) {
      $osName = 'iOS';
    } elseif (str_contains($ua, 'android')) {
      $osName = 'Android';
    } elseif (str_contains($ua, 'linux')) {
      $osName = 'Linux';
    }

    $deviceType = 'Desktop';
    if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
      $deviceType = 'Tablet';
    } elseif (str_contains($ua, 'mobile') || str_contains($ua, 'iphone') || str_contains($ua, 'android')) {
      $deviceType = 'Mobile';
    }

    return [
      'browser_name' => $browserName,
      'browser_version' => $browserVersion,
      'os_name' => $osName,
      'device_type' => $deviceType,
    ];
  }

  /**
   * Normalize scalar values to trimmed strings.
   */
  private static function asString(mixed $value): string
  {
    return is_scalar($value) ? trim((string) $value) : '';
  }

  /**
   * Normalize ints/numeric scalars to integer values.
   */
  private static function asInt(mixed $value): int
  {
    if (is_int($value)) {
      return $value;
    }

    if (is_string($value) && is_numeric($value)) {
      return (int) $value;
    }

    if (is_float($value)) {
      return (int) $value;
    }

    return 0;
  }
}
