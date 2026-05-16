<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\FormTTL;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\Enums\AuthLevel;

/**
 * Authentication.php
 *
 * Purpose: Session and authentication authority for request identity,
 * cookie policy, CSRF/session lifetime enforcement, and auth-level checks.
 *
 * Developer notes:
 * - Treat this class as the source of truth for PAYCAL_AUTH cookie resolution.
 * - Session state is security-sensitive and must stay consistent with the
 *   backing Redis/session store semantics defined here.
 * - Cookie attributes, SameSite policy, and secure transport requirements are
 *   coupled; update them together, not piecemeal.
 * - Prefer helper methods here over duplicating auth/session rules in
 *   controllers or templates.
 *
 * Architectural role:
 * - Reusable domain authority for session resolution, auth-level checks, and
 *   request identity policy.
 * - Encapsulates authentication and session policy outside the HTTP layer.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */


/**
 * Authentication and session lifecycle service.
 *
 * Responsibilities:
 * - Resolve the active session from cookies and request context.
 * - Enforce authentication level and session expiry rules.
 * - Issue, refresh, and destroy session state safely.
 * - Provide cookie-policy helpers so auth transport behavior remains uniform.
 */

class Authentication
{
  /** @var array<string, true> */
  private const SAME_SITE_PRIMARY_SET = [
    'lax' => true,
    'strict' => true,
  ];


  /**
   * Collect PAYCAL_AUTH candidates from parsed cookies and raw header.
   * Handles duplicate cookie scenarios (domain/path variants).
   *
   * @return array<int, string>
   */
  private static function sessionHashCandidates(): array
  {
    $maxLen = SystemConfig::MAX_STRING_LENGTH;
    $candidates = [];

    $rawCookie = $_COOKIE['PAYCAL_AUTH'] ?? null;
    if (is_string($rawCookie) && strlen($rawCookie) <= $maxLen) {
      $sanitized = InputSanitizer::SanitizeString($rawCookie);
      if ($sanitized !== '') {
        $candidates[] = $sanitized;
      }
    }

    $headerRaw = $_SERVER['HTTP_COOKIE'] ?? '';
    if (is_string($headerRaw) && $headerRaw !== '') {
      $parts = explode(';', $headerRaw);
      // Iterate from right to left so the most recently set value is preferred first.
      for ($i = count($parts) - 1; $i >= 0; $i -= 1) {
        $part = trim($parts[$i]);
        if (!str_starts_with($part, 'PAYCAL_AUTH=')) {
          continue;
        }

        $value = substr($part, strlen('PAYCAL_AUTH='));
        $decoded = rawurldecode($value);
        if ($decoded === '' || strlen($decoded) > $maxLen) {
          continue;
        }

        $sanitized = InputSanitizer::SanitizeString($decoded);
        if ($sanitized !== '') {
          $candidates[] = $sanitized;
        }
      }
    }

    if (empty($candidates)) {
      return [];
    }

    return array_values(array_unique($candidates));
  }

  /**
   * @return null|string
   */
  private static function resolveValidSessionHash(): ?string
  {
    $bestHash = null;
    $bestActivity = PHP_INT_MIN;

    foreach (self::sessionHashCandidates() as $candidate) {
      if (!self::sessionExists($candidate)) {
        continue;
      }

      $sessionKey = Keys::SESSION . ':' . $candidate;
      $lastActivity = (string) Database::hget($sessionKey, 'last_activity');
      $createdAt = (string) Database::hget($sessionKey, 'created_at');

      $activity = 0;
      if (is_numeric($lastActivity)) {
        $activity = (int) $lastActivity;
      } elseif (is_numeric($createdAt)) {
        $activity = (int) $createdAt;
      }

      // Prefer the most recently active valid session when duplicates exist.
      if ($bestHash === null || $activity > $bestActivity) {
        $bestHash = $candidate;
        $bestActivity = $activity;
      }
    }

    return $bestHash;
  }

  /**
   * Extract and validate PAYCAL_AUTH cookie value.
   * @return null|string Sanitized session hash or null if invalid/missing
   */
  public static function getSessionHashFromCookie(): ?string
  {
    return self::resolveValidSessionHash();
  }

  /**
   * Pure existence check for session hash in Redis.
   * @param string $hash
   * @return bool
   */
  public static function sessionExists(string $hash): bool
  {
    $key = Keys::SESSION . ":" . $hash;
    if (Database::exists($key)) {
      return true;
    }

    try {
      return Database::getWriteInstance()->exists($key);
    } catch (\Throwable $e) {
      return false;
    }
  }

  /**
   * Touch/extend session TTL and update last_activity.
   * @param string $hash
   * @return void
   */
  public static function touchSession(string $hash): void
  {
    $key = Keys::SESSION . ":" . $hash;
    Database::hsetex($key, ["last_activity" => (string) time()], Environment::redisNewSessionTTL());
  }

  /**
   * Convenience wrapper: validate session and extend TTL if valid.
   * @return bool
   */
  public static function validateAndTouchSession(): bool
  {
    $hash = self::resolveValidSessionHash();
    if ($hash === null)
      return false;

    self::touchSession($hash);
    return true;
  }
  /**
   * Retrieves value of PAYCAL_AUTH cookie if the user session is valid, or empty string.
   * @return string Value of PAYCAL_AUTH cookie if the user session is valid, or empty string
   */
  public static function getCookie(): string
  {
    /** @var string @cookie */
    $cookie = '';

    $hash = self::resolveValidSessionHash();
    if ($hash !== null) {
      $cookie = $hash;
    }

    return $cookie;
  }


  /**
   * Sets a secure HTTP-only cookie for authentication purposes.
   * This function sets a cookie named 'PAYCAL_AUTH' with a specified session hash value.
   * The cookie has additional security options such as expiration time, path, secure flag,
   * HTTP-only flag, and SameSite attribute set to 'Strict'.
   * @param string $sessionHash the session hash value to be stored in the cookie
   */
  public static function setCookie(string $sessionHash): void
  {
    $domain = (string) parse_url((string) Environment::appPublicURL(), PHP_URL_HOST);
    $secure = isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS'];
    $sameSite = self::cookieSameSitePolicy($secure);

    $cookieOptions = [
      'expires' => time() + FormTTL::THIRTY_DAYS->value,
      'path' => '/',
      'secure' => $secure,
      'httponly' => true,
      'samesite' => $sameSite,
    ];

    $cookieOptionsDomain = $cookieOptions;
    if ($domain !== '') {
      $cookieOptionsDomain['domain'] = $domain;
    }

    $deleteOptions = [
      'expires' => time() - FormTTL::ONE_HOUR->value,
      'path' => '/',
      'secure' => $secure,
      'httponly' => true,
      'samesite' => $sameSite,
    ];

    $deleteOptionsDomain = $deleteOptions;
    if ($domain !== '') {
      $deleteOptionsDomain['domain'] = $domain;
    }

    // Clear both host-only and domain-scoped variants to prevent stale collisions.
    @setcookie('PAYCAL_AUTH', '', $deleteOptions);
    @setcookie('PAYCAL_AUTH', '', $deleteOptionsDomain);
    unset($_COOKIE['PAYCAL_AUTH']);

    // Set host-only cookie for maximum browser compatibility.
    setcookie('PAYCAL_AUTH', $sessionHash, $cookieOptions);

    // Also set domain-scoped cookie when domain is available.
    if ($domain !== '') {
      setcookie('PAYCAL_AUTH', $sessionHash, $cookieOptionsDomain);
    }
  }

  /** @return 'Lax'|'Strict'|'None' */
  private static function cookieSameSitePolicy(bool $secure): string
  {
    $configured = '';
    $configuredEnv = $_ENV['AUTH_COOKIE_SAMESITE'] ?? null;
    if (is_scalar($configuredEnv)) {
      $configured = (string) $configuredEnv;
    }

    if ($configured === '') {
      $configuredGetenv = getenv('AUTH_COOKIE_SAMESITE');
      if (is_string($configuredGetenv)) {
        $configured = $configuredGetenv;
      }
    }

    $normalized = strtolower(trim($configured));

    if (isset(self::SAME_SITE_PRIMARY_SET[$normalized])) {
      return ucfirst($normalized);
    }

    if ($normalized === 'none') {
      // `None` requires Secure cookies; otherwise browsers will reject it.
      return $secure ? 'None' : 'Lax';
    }

    // Default to Lax so cross-site top-level redirects (for example Stripe return URLs)
    // retain the authenticated session while still blocking most cross-site POSTs.
    return 'Lax';
  }



  /**
   * Builds the canonical unauthenticated sign-in URL.
   */
  public static function unauthenticatedRedirectURL(): string
  {
    return Environment::appURL('auth/');
  }


  /**
   * Computes the redirect target for unauthenticated UI requests.
  * Returns null when already on the auth path to prevent redirect loops.
   *
   * @param null|string $requestUri Optional request URI override for testing.
   */
  public static function unauthenticatedRedirectTarget(?string $requestUri = null): ?string
  {
    $uriRaw = $requestUri ?? ($_SERVER['REQUEST_URI'] ?? '/');
    $uri = is_scalar($uriRaw) ? (string) $uriRaw : '/';
    $path = parse_url($uri, PHP_URL_PATH);
    $normalized = '/'.trim((string) $path, '/');

    if ('/auth' === $normalized) {
      return null;
    }

    return self::unauthenticatedRedirectURL();
  }


  /**
   * Abort the current request if the user is not logged in.
   * Intended for endpoints that must not run unauthenticated.
   */
  public static function abortIfUnauthenticated(): void
  {
    $devBypass = $_ENV['DEV_AUTH_BYPASS'] ?? getenv('DEV_AUTH_BYPASS');
    if (in_array(Environment::appEnv(), ['dev', 'mac'], true) && is_string($devBypass) && $devBypass === 'true') {
      return;
    }
    if (!self::validateAndTouchSession())
      Response::error('[AUTH] Unauthorized', [], HttpStatus::HTTP_UNAUTHORIZED);
  }


  /**
   * Redirect to the site home if the user is not logged in.
   * Intended for UI pages that should bounce unauthenticated users home.
   */
  public static function redirectHomeIfUnauthenticated(): void
  {
    // Dev-only bypass: skip all auth checks when DEV_AUTH_BYPASS=true and APP_ENV=dev.
    // Both conditions must be true; this can never fire in production (APP_ENV != 'dev').
    $devBypass = $_ENV['DEV_AUTH_BYPASS'] ?? getenv('DEV_AUTH_BYPASS');
    if (in_array(Environment::appEnv(), ['dev', 'mac'], true) && is_string($devBypass) && $devBypass === 'true') {
      return;
    }

    if (!self::validateAndTouchSession()) {
      $target = self::unauthenticatedRedirectTarget();
      if ($target === null) {
        return;
      }

      self::sendRedirectSecurityHeaders();
      header('Location: '.$target);
      exit;
    }

    if (self::shouldRedirectUnverifiedUser()) {
      self::sendRedirectSecurityHeaders();
      header('Location: '.Environment::appURL('unverified/'));
      exit;
    }
  }

  /**
   * Redirect to /unverified if the user is authenticated but has not verified their email.
   * Intended for pages that require email verification.
   */
  public static function redirectUnverifiedToVerificationPage(): void
  {
    if (!self::validateAndTouchSession()) {
      return;
    }

    $currentUser = User::current();
    if (!$currentUser->email_verified) {
      self::sendRedirectSecurityHeaders();
      header('Location: ' . Environment::appURL('/unverified/'));
      exit;
    }
  }

  /**
   * Handles sendRedirectSecurityHeaders operation.
   */
  private static function sendRedirectSecurityHeaders(): void
  {
    Security::sendCoreSecurityHeaders();
    header("Content-Security-Policy: default-src 'self' https: data: blob:; object-src 'none'; frame-ancestors 'none'; base-uri 'self'");
  }

  /**
   * Determine if the current authenticated user should be redirected to /unverified.
   * Keeps auth and unverified endpoints reachable to avoid redirect loops.
   */
  private static function shouldRedirectUnverifiedUser(?string $requestUri = null): bool
  {
    $currentUser = User::current();
    if ($currentUser->email_verified) {
      return false;
    }

    $uriRaw = $requestUri ?? ($_SERVER['REQUEST_URI'] ?? '/');
    $uri = is_scalar($uriRaw) ? (string) $uriRaw : '/';
    $path = parse_url($uri, PHP_URL_PATH);
    $normalized = '/'.trim((string) $path, '/');

    if ($normalized === '/') {
      return true;
    }

    if (
      str_starts_with($normalized, '/auth')
      || str_starts_with($normalized, '/unverified')
      || str_starts_with($normalized, '/api/v1/account/resend-verification')
      || str_starts_with($normalized, '/api/v1/auth/logout')
      || str_starts_with($normalized, '/css')
      || str_starts_with($normalized, '/js')
      || str_starts_with($normalized, '/images')
      || str_starts_with($normalized, '/fonts')
    ) {
      return false;
    }

    return true;
  }

  /**
   * Abort the current request if the user is authenticated but has not verified their email.
   */
  public static function requireEmailVerifiedOrDie(): void
  {
    if (!self::validateAndTouchSession()) {
      Response::error('[AUTH] Unauthorized', [], HttpStatus::HTTP_UNAUTHORIZED);
    }

    $currentUser = User::current();
    if (!$currentUser->email_verified) {
      Response::error('[AUTH] Email not verified', [], HttpStatus::HTTP_FORBIDDEN);
    }
  }

  /**
   * Abort the current request if the current user is not an admin.
   */
  public static function isAdminOrDie(): void
  {
    if (!User::isAdmin())
      Response::error('[AUTH] Unauthorized', [], HttpStatus::HTTP_UNAUTHORIZED);
  }


  /**
   * Abort the current request if the current user is not a manager.
   */
  public static function isManagerOrDie(): void
  {
    if (!User::isManager())
      Response::error('[AUTH] Unauthorized', [], HttpStatus::HTTP_UNAUTHORIZED);
  }


  /**
   * Creates or updates a user session in Redis.
   * This function sets various session-related details in Redis,
   * such as the UUID, creation time, last sign in time, and IP addresses.
   * @param string $sessionHash session hash to identify the session
   * @param string $userUUID    unique identifier of the user for whom the session is being set
   */
  public static function setSession(string $sessionHash, string $userUUID): void
  {
    $sessionKey     = Keys::SESSION . ':' . InputSanitizer::sanitizeString($sessionHash);
    $createdAt      = $lastSignin    = InputSanitizer::sanitizeString(strval(time()));
    $firstIPAddress = $lastIPAddress = InputSanitizer::sanitizeString(strval(Security::getClientIPAddress()));
    $rawUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $userAgent = is_scalar($rawUserAgent) ? trim((string) $rawUserAgent) : '';
    if ($userAgent !== '') {
      $userAgent = substr($userAgent, 0, 512);
    }

    Database::hsetex($sessionKey, [
      'user_uuid'     => $userUUID,
      'created_at'    => $createdAt,
      'last_signin'   => $lastSignin,
      'last_activity' => $createdAt,
      'first_ip'      => $firstIPAddress,
      'last_ip'       => $lastIPAddress,
      'auth_method'   => 'session_start',
      'auth_strength' => 'unknown',
      'user_agent'    => $userAgent,
      'login_time'    => $createdAt, // For session duration metrics
    ], Environment::redisNewSessionTTL());

    // Confirm the session key has propagated to at least one replica before
    // responding.  Without WAIT, the post-login redirect can hit a replica
    // that has not yet received the new session, making auth appear to fail.
    // On single-instance deployments WAIT returns 0 immediately with no cost.
    Database::wait(1, 50);

      // Telemetry: Track login event (aggregate only, no PII)
      // Atomic pattern: incr() on a missing key creates it as 1 in one round
      // trip.  The previous exists()+set()+incr() had a race: two concurrent
      // logins both saw exists()=false, both wrote '0', then both incremented —
      // creating a permanent TTL-less key if either incr() ran before set().
      $loginKey = Keys::TELEMETRY . ':auth:login:' . date('Y-m-d');
      $loginCount = Database::incr($loginKey);
      if (1 === $loginCount) {
        Database::expire($loginKey, 2592000); // 30 days
      }
  }


  /**
   * Retrieves the UUID associated with a given session hash from Redis.
   * @param string $sessionHash Raw session hash (may be unsanitized)
   * @return null|string UUID if found, or null if the session is invalid or does not exist
   */
  public static function getUserUUIDFromSession(string $sessionHash): ?string
  {
    $hash = InputSanitizer::SanitizeString($sessionHash);

    if ('' === $hash)
      return null;

    $key = Keys::SESSION . ':' . $hash;
    $uuid = Database::hget($key, 'user_uuid');

    return '' !== $uuid ? $uuid : null;
  }

  /**
   * Process user sign in credentials
   * This function checks user sign in credential against Redis and sets their Cookie.
   * @return bool the boolean result indicates if a user is authenticated or not
   */
  public static function attemptSignin(): bool
  {
    Log::debug('[SIGNIN] Password signin disabled. Use passkeys at /auth/.');
    return false;
  }


  /**
   * Generates a verification reminder message for unverified users.
   * This function retrieves user information from Redis, checks if the user is unverified,
   * and generates a reminder message with a link to the registration page.
   * @return null|string the HTML reminder message for unverified users or null if the user is not unverified
   */
  public static function getVerificationReminderHtml(): ?string
  {
    $hash = self::getSessionHashFromCookie();
    if ($hash === null || !self::sessionExists($hash))
      return null;

    $user = User::current();
    if ($user->email_verified) {
      return null;
    }

    $cspNonceRaw = $_SERVER['CSP_NONCE'] ?? '';
    $cspNonce = is_scalar($cspNonceRaw) ? (string) $cspNonceRaw : '';
    $i18n = [];
    $i18nKeys = [
      'AUTH_VERIFICATION_REMINDER_TITLE',
      'AUTH_VERIFICATION_REMINDER_BODY',
      'AUTH_VERIFICATION_REMINDER_CODE_PLACEHOLDER',
      'AUTH_VERIFICATION_REMINDER_VERIFY_BUTTON',
      'AUTH_VERIFICATION_REMINDER_RESEND_LINK',
    ];
    foreach ($i18nKeys as $key) {
      $i18n[$key] = Strings::i18n($key);
    }

    $renders = [
      '__SITE_REGISTER_VERIFY_URL__' => Environment::appURL('verify/'),
      '__SITE_API_RESEND_VERIFY_URL__' => Environment::appURL('api/v1/account/resend-verification'),
      '__VERIFICATION_REMINDER_JS_URL__' => Environment::appURL('js/signin/verification-reminder.js') . '?v=' . Environment::appVersion(),
      '__VERIFICATION_REMINDER_JS_INTEGRITY_ATTR__' => Render::sriAttribute('js/signin/verification-reminder.js'),
      '__AUTH_VERIFICATION_REMINDER_TITLE__' => $i18n['AUTH_VERIFICATION_REMINDER_TITLE'],
      '__AUTH_VERIFICATION_REMINDER_BODY__' => $i18n['AUTH_VERIFICATION_REMINDER_BODY'],
      '__AUTH_VERIFICATION_REMINDER_CODE_PLACEHOLDER__' => $i18n['AUTH_VERIFICATION_REMINDER_CODE_PLACEHOLDER'],
      '__AUTH_VERIFICATION_REMINDER_VERIFY_BUTTON__' => $i18n['AUTH_VERIFICATION_REMINDER_VERIFY_BUTTON'],
      '__AUTH_VERIFICATION_REMINDER_RESEND_LINK__' => $i18n['AUTH_VERIFICATION_REMINDER_RESEND_LINK'],
      '__CSP_NONCE__' => $cspNonce,
    ];

    return Render::template('verification-reminder', $renders);
  }


  /**
   * Retrieve the authentication types from the platform as an html <select> tag.
   */
  public static function getAuthOptionsHtml(): string
  {
    $i18n = [];
    foreach (['SELECT_AUTHENTICATION_LEVEL'] as $key) {
      $i18n[$key] = Strings::i18n($key);
    }

    $buffer = "<select id='update_user_auth_level' name='update_user_auth_level' aria-label='"
            . $i18n['SELECT_AUTHENTICATION_LEVEL']."'>".PHP_EOL;

    foreach (AuthLevel::cases() as $level) {
      $display = strtoupper($level->value);

      $renders = [
        '__LEVEL_VALUE__' => $level->value,
        '__LEVEL_DISPLAY__' => $display,
      ];

      $buffer .= Render::template('auth-option', $renders);
    }

    $buffer .= '</select>'.PHP_EOL;

    return $buffer;
}

  /**
   * Destroy a user session and record session duration metrics.
   * 
   * Purpose: Track session lifecycle for platform health monitoring
   * Privacy: Session hash deleted after metrics recorded, no user UUID in telemetry
   * 
   * @param string $sessionHash Session hash to destroy
   * @return bool True if session was destroyed, false if session didn't exist
   */
  public static function destroySession(string $sessionHash): bool
  {
    $sessionKey = Keys::SESSION . ':' . InputSanitizer::sanitizeString($sessionHash);
    
    // Check if session exists
    if (!Database::exists($sessionKey)) {
      return false;
    }
    
    // Get login time to calculate session duration
    $loginTime = Database::hget($sessionKey, 'login_time');
    
    if ($loginTime !== '' && is_numeric($loginTime)) {
      $duration = time() - (int)$loginTime;
      $bucket = self::getSessionDurationBucket($duration);
      
      // Telemetry: Increment duration bucket (aggregate only, no PII)
      $durationKey = Keys::TELEMETRY . ':session:duration:' . $bucket;
      $durationCount = Database::incr($durationKey);
      if (1 === $durationCount) {
        Database::expire($durationKey, 2592000); // 30 days TTL
      }
    }
    
    // Delete session (privacy guard: no persistence after metrics recorded)
    Database::del($sessionKey);
    
    // Telemetry: Track logout event (aggregate only, no PII)
    $logoutKey = Keys::TELEMETRY . ':auth:logout:' . date('Y-m-d');
    $logoutCount = Database::incr($logoutKey);
    if (1 === $logoutCount) {
      Database::expire($logoutKey, 2592000); // 30 days TTL
    }
    
    return true;
  }

  /**
   * Handles destroyAllUserSessions operation.
   */
  public static function destroyAllUserSessions(string $userUUID): void
  {
    if ($userUUID === '') {
      return;
    }

    foreach (Database::scanKeys(Keys::SESSION . ':*') as $sessionKey) {
      $key = (string) $sessionKey;
      if ($key === '' || Database::hget($key, 'user_uuid') !== $userUUID) {
        continue;
      }

      $sessionHash = substr($key, strlen(Keys::SESSION . ':'));
      if ($sessionHash !== '') {
        self::destroySession($sessionHash);
      }
    }
  }

  /**
   * Handles markUserSessionsRecoveryPending operation.
   */
  public static function markUserSessionsRecoveryPending(string $userUUID, string $txnId): void
  {
    if ($userUUID === '' || $txnId === '') {
      return;
    }

    foreach (Database::scanKeys(Keys::SESSION . ':*') as $sessionKey) {
      $key = (string) $sessionKey;
      if ($key === '' || Database::hget($key, 'user_uuid') !== $userUUID) {
        continue;
      }

      Database::hset($key, [
        'recovery_pending' => '1',
        'recovery_txn_id' => $txnId,
      ]);
    }
  }

  /**
   * Handles clearUserRecoveryPending operation.
   */
  public static function clearUserRecoveryPending(string $userUUID, string $txnId): void
  {
    if ($userUUID === '') {
      return;
    }

    foreach (Database::scanKeys(Keys::SESSION . ':*') as $sessionKey) {
      $key = (string) $sessionKey;
      if ($key === '' || Database::hget($key, 'user_uuid') !== $userUUID) {
        continue;
      }

      $storedTxnId = Database::hget($key, 'recovery_txn_id');
      if ($txnId !== '' && $storedTxnId !== '' && $storedTxnId !== $txnId) {
        continue;
      }

      Database::hset($key, [
        'recovery_pending' => '0',
        'recovery_txn_id' => '',
      ]);
    }
  }

  /**
   * Handles isCurrentSessionRecoveryPending operation.
   */
  public static function isCurrentSessionRecoveryPending(): bool
  {
    $sessionHash = self::getSessionHashFromCookie();
    if ($sessionHash === null || $sessionHash === '') {
      return false;
    }

    return Database::hget(Keys::SESSION . ':' . $sessionHash, 'recovery_pending') === '1';
  }

  /**
   * Get session duration bucket for metrics.
   * 
   * Guard: Exactly 4 buckets (enforced by contract test)
   * Purpose: Aggregate session durations without tracking individual sessions
   * 
   * @param int $durationSeconds Session duration in seconds
   * @return string One of: '0-5min', '5-30min', '30-60min', '60min+'
   */
  private static function getSessionDurationBucket(int $durationSeconds): string
  {
    if ($durationSeconds < 300) {        // < 5 minutes
      return '0-5min';
    } elseif ($durationSeconds < 1800) { // < 30 minutes
      return '5-30min';
    } elseif ($durationSeconds < 3600) { // < 60 minutes
      return '30-60min';
    } else {                              // >= 60 minutes
      return '60min+';
    }
  }
}


