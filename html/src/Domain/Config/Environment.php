<?php declare(strict_types=1);

namespace PayCal\Domain\Config;

/**
 * Environment.php
 *
 * Purpose: Typed environment facade and bootstrap authority for app/domain,
 * Redis/mail, and security-related runtime environment settings.
 *
 * Developer notes:
 * - Environment::bootstrap is a central process-wide initialization path.
 * - Add new environment keys here with typed accessors to avoid scattered
 *   getenv lookups throughout the codebase.
 * - Security-impacting flags (e.g., encryption and dev overrides) should remain
 *   explicit and auditable in this class.
 *
 * @category   Config
 * @package    PayCal\Domain\Config
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Runtime environment configuration facade.
 *
 * Responsibilities:
 * - Bootstrap process environment into typed static settings.
 * - Expose normalized accessors for app and infrastructure configuration.
 * - Provide defaulting and missing-key diagnostics for predictable startup.
 */
final class Environment
{
  private const DEFAULT_REDIS_SESSION_TTL = 3600;

  /**
   * Truthy string values accepted by environment bool parsing.
   *
    * @var array<int|string, true>
   */
  private const TRUTHY_BOOL_SET = [
    'true' => true,
    '1' => true,
    'yes' => true,
    'on' => true,
  ];

  private static string $appEnv = '';
  private static string $appScheme = '';
  private static string $appDomain = '';
  private static string $appPublicURL = '';
  private static string $appHome = '';
  private static string $appVersion = 'unknown';
  private static string $apiVersion = 'v0';
  private static string $redisServer = 'localhost';
  private static int    $redisPort = 6379;
  private static int    $redisReadPort = 6379;
  private static int    $redisWritePort = 6379;
  private static int    $redisDb = 0;
  private static bool   $redisAuthEnabled = false;
  private static string $redisUser = '';
  private static string $redisPassword = '';
  private static int    $redisNewSessionTTL = 0;
  private static string $smtpServer = '';
  private static int    $smtpPort = 0;
  private static string $emailContact = '';
  private static string $emailDebug = '';
  private static string $emailReplyTo = '';
  private static string $emailPassword = '';
  private static string $inviteCode = '';
  private static string $payrollPrivateKey = '';
  private static string $payrollPublicKey = '';
  private static bool   $devAllowInlineScripts = false;
  private static bool   $encryptionEnabled = false;
  private static bool   $devSecurityDisabled = false;

  /**
   * @param array<string, string> $env
   */
  public static function bootstrap(array $env): void
  {
    self::$appEnv            = $env["APP_ENV"] ?? self::logMissingString("APP_ENV", "");
    self::$appScheme         = $env["APP_SCHEME"] ?? self::logMissingString("APP_SCHEME", "");
    self::$appDomain         = $env["APP_DOMAIN"] ?? self::logMissingString("APP_DOMAIN", "");
    self::$appPublicURL      = self::normalizePublicURL(self::$appScheme, self::$appDomain);
    self::$appHome           = self::normalizeAppHome($env["APP_HOME"] ?? self::logMissingString("APP_HOME", ""));
    self::$appVersion        = self::resolveVersion();
    self::$apiVersion        = $env["API_VERSION"] ?? self::logMissingString("API_VERSION", "v0");
    self::$redisServer       = $env["REDIS_SERVER"] ?? self::logMissingString("REDIS_SERVER", "localhost");
    self::$redisPort         = isset($env["REDIS_PORT"]) ? (int) $env["REDIS_PORT"] : self::logMissingInt("REDIS_PORT", 6379);
    self::$redisReadPort     = isset($env["REDIS_READ_PORT"]) ? (int) $env["REDIS_READ_PORT"] : self::logMissingInt("REDIS_READ_PORT", 6379);
    self::$redisWritePort    = isset($env["REDIS_WRITE_PORT"]) ? (int) $env["REDIS_WRITE_PORT"] : self::logMissingInt("REDIS_WRITE_PORT", 6379);
    self::$redisDb           = isset($env["REDIS_DB"]) ? (int) $env["REDIS_DB"] : self::logMissingInt("REDIS_DB", 0);
    self::$redisUser         = $env["REDIS_USER"] ?? self::logMissingString("REDIS_USER", "");
    self::$redisPassword     = $env["REDIS_PASSWORD"] ?? self::logMissingString("REDIS_PASSWORD", "");
    $redisAuthInferred = trim(self::$redisPassword) !== '' || trim(self::$redisUser) !== '';
    self::$redisAuthEnabled  = isset($env["REDIS_AUTH_ENABLED"]) ? self::toBool($env["REDIS_AUTH_ENABLED"]) : $redisAuthInferred;
    self::$redisNewSessionTTL= isset($env["REDIS_NEW_SESSION_TTL"]) ? (int) $env["REDIS_NEW_SESSION_TTL"] : self::logMissingInt("REDIS_NEW_SESSION_TTL", self::DEFAULT_REDIS_SESSION_TTL);
    self::$smtpServer        = $env["PC_EMAIL_SMTP_SERVER"] ?? self::logMissingString("PC_EMAIL_SMTP_SERVER", "");
    self::$smtpPort          = isset($env["PC_EMAIL_SMTP_PORT"]) ? (int) $env["PC_EMAIL_SMTP_PORT"] : self::logMissingInt("PC_EMAIL_SMTP_PORT", 0);
    self::$emailContact      = $env["PC_EMAIL_CONTACT"] ?? self::logMissingString("PC_EMAIL_CONTACT", "");
    self::$emailDebug        = $env["PC_EMAIL_DEBUG"] ?? self::logMissingString("PC_EMAIL_DEBUG", "");
    self::$emailReplyTo      = $env["PC_EMAIL_REPLYTO"] ?? self::logMissingString("PC_EMAIL_REPLYTO", "");
    self::$emailPassword     = $env["PC_EMAIL_PASSWORD"] ?? self::logMissingString("PC_EMAIL_PASSWORD", "");
    self::$inviteCode             = $env["PC_INVITE_CODE"] ?? self::logMissingString("PC_INVITE_CODE", "");
    self::$payrollPrivateKey      = $env["PAYROLL_SIGNING_PRIVATE_KEY"] ?? "";
    self::$payrollPublicKey       = $env["PAYROLL_SIGNING_PUBLIC_KEY"] ?? "";
    self::$devAllowInlineScripts  = isset($env["DEV_ALLOW_INLINE_SCRIPTS"]) ? self::toBool($env["DEV_ALLOW_INLINE_SCRIPTS"]) : false;
    self::$devSecurityDisabled    = isset($env["DEV_SECURITY_DISABLED"]) ? self::toBool($env["DEV_SECURITY_DISABLED"]) : false;
    // When DEV_SECURITY_DISABLED is true, force encryption off
    self::$encryptionEnabled      = self::$devSecurityDisabled ? false : (isset($env["ENCRYPTION_ENABLED"]) ? self::toBool($env["ENCRYPTION_ENABLED"]) : false);
  }

  /**
   * Handles logMissingString operation.
   */
  private static function logMissingString(string $key, string $default): string
  {
    \PayCal\Domain\Log::error("Missing environment key: {$key}, using default: " . var_export($default, true));

    return $default;
  }

  /**
   * Handles logMissingInt operation.
   */
  private static function logMissingInt(string $key, int $default): int
  {
    \PayCal\Domain\Log::error("Missing environment key: {$key}, using default: " . var_export($default, true));

    return $default;
  }

  /**
   * Handles appEnv operation.
   */
  public static function appEnv()            : string { return self::$appEnv; }
  /**
   * Handles isLocalMac operation.
   */
  public static function isLocalMac()        : bool   { return self::$appEnv === 'mac'; }
  /**
   * Handles appScheme operation.
   */
  public static function appScheme()         : string { return self::$appScheme; }
  /**
   * Handles appDomain operation.
   */
  public static function appDomain()         : string { return self::$appDomain; }
  /**
   * Handles appPublicURL operation.
   */
  public static function appPublicURL()      : string { return self::$appPublicURL; }
  /**
   * Handles appBaseURL operation.
   */
  public static function appBaseURL()        : string { return rtrim(self::$appPublicURL, '/'); }
  /**
   * Handles appURL operation.
   */
  public static function appURL(string $path = ''): string
  {
    $base = self::appBaseURL();
    if ($path === '') {
      return $base;
    }

    if ($path === '/') {
      return $base . '/';
    }

    return $base . '/' . ltrim($path, '/');
  }
  /**
   * Handles appHome operation.
   */
  public static function appHome()           : string { return self::$appHome; }
  /**
   * Handles appVersion operation.
   */
  public static function appVersion()        : string { return self::$appVersion; }
  /**
   * Handles apiVersion operation.
   */
  public static function apiVersion()        : string { return self::$apiVersion; }
  /**
   * Handles redisServer operation.
   */
  public static function redisServer()       : string { return self::$redisServer; }
  /**
   * Handles redisPort operation.
   */
  public static function redisPort()         : int    { return self::$redisPort; }
  /**
   * Handles redisReadPort operation.
   */
  public static function redisReadPort()     : int    { return self::$redisReadPort; }
  /**
   * Handles redisWritePort operation.
   */
  public static function redisWritePort()    : int    { return self::$redisWritePort; }
  /**
   * Handles redisDb operation.
   */
  public static function redisDb()           : int    { return self::$redisDb; }
  /**
   * Handles redisAuthEnabled operation.
   */
  public static function redisAuthEnabled()  : bool   { return self::$redisAuthEnabled; }
  /**
   * Handles redisUser operation.
   */
  public static function redisUser()         : string { return self::$redisUser; }
  /**
   * Handles redisPassword operation.
   */
  public static function redisPassword()     : string { return self::$redisPassword; }
  /**
   * Handles redisNewSessionTTL operation.
   */
  public static function redisNewSessionTTL(): int
  {
    return self::$redisNewSessionTTL > 0
      ? self::$redisNewSessionTTL
      : self::DEFAULT_REDIS_SESSION_TTL;
  }
  /**
   * Handles smtpServer operation.
   */
  public static function smtpServer()        : string { return self::$smtpServer; }
  /**
   * Handles smtpPort operation.
   */
  public static function smtpPort()          : int    { return self::$smtpPort; }
  /**
   * Handles emailContact operation.
   */
  public static function emailContact()      : string { return self::$emailContact; }
  /**
   * Handles emailDebug operation.
   */
  public static function emailDebug()        : string { return self::$emailDebug; }
  /**
   * Handles emailReplyTo operation.
   */
  public static function emailReplyTo()      : string { return self::$emailReplyTo; }
  /**
   * Handles emailPassword operation.
   */
  public static function emailPassword()     : string { return self::$emailPassword; }
  /**
   * Handles inviteCode operation.
   */
  public static function inviteCode()           : string { return self::$inviteCode; }
  /**
   * Handles payrollPrivateKey operation.
   */
  public static function payrollPrivateKey()    : string { return self::$payrollPrivateKey; }
  /**
   * Handles payrollPublicKey operation.
   */
  public static function payrollPublicKey()     : string { return self::$payrollPublicKey; }
  /**
   * Handles devAllowInlineScripts operation.
   */
  public static function devAllowInlineScripts(): bool   { return self::$devAllowInlineScripts; }
  /**
   * Handles encryptionEnabled operation.
   */
  public static function encryptionEnabled()    : bool   { return self::$encryptionEnabled; }
  /**
   * Handles devSecurityDisabled operation.
   */
  public static function devSecurityDisabled()  : bool   { return self::$devSecurityDisabled; }

  /**
   * Convert environment variable string to boolean.
   *
   * @param string $value
   * @return bool
   */
  private static function toBool(string $value): bool
  {
    return isset(self::TRUTHY_BOOL_SET[strtolower($value)]);
  }

  /**
   * Build canonical application public URL from env values.
   */
  private static function normalizePublicURL(string $scheme, string $domain): string
  {
    $cleanScheme = strtolower(trim($scheme));
    $cleanScheme = rtrim($cleanScheme, ':/');
    if ($cleanScheme === '') {
      $cleanScheme = 'https';
    }

    $cleanDomain = trim($domain);
    $cleanDomain = preg_replace('/^[a-z][a-z0-9+\-.]*:\/\//i', '', $cleanDomain) ?? $cleanDomain;
    $cleanDomain = rtrim($cleanDomain, '/');

    return $cleanScheme . '://' . $cleanDomain;
  }

  /**
   * Normalize APP_HOME and fall back to the actual repository root when the
   * configured path is stale for the current machine.
   */
  private static function normalizeAppHome(string $appHome): string
  {
    $trimmed = trim($appHome);
    if ($trimmed !== '') {
      $resolved = realpath($trimmed);
      if ($resolved !== false && is_dir($resolved)) {
        return rtrim($resolved, '/') . '/';
      }
    }

    return rtrim(dirname(__DIR__, 4), '/') . '/';
  }

  /**
   * Resolve application version with priority:
   * 1. Git describe (if available in repo)
   * 2. VERSION file (repo root)
   * 3. 'unknown' fallback
   *
   * @return string
   */
  private static function resolveVersion(): string
  {
    // Try git describe for development builds (shows commits ahead)
    if (self::isGitAvailable()) {
      $gitVersion = self::getGitVersion();
      if ($gitVersion !== null) {
        return $gitVersion;
      }
    }

    // Fall back to VERSION file (for deployments/non-git environments)
    $versionFile = self::repoRoot() . '/VERSION';
    if (file_exists($versionFile)) {
      $content = file_get_contents($versionFile);
      if ($content !== false) {
        $version = trim($content);
        if (!empty($version)) {
          return $version;
        }
      }
    }

    // Final fallback
    \PayCal\Domain\Log::error("Could not resolve app version - neither git nor VERSION file available");
    return 'unknown';
  }

  /**
   * Check if git is available and we're in a git repository.
   *
   * @return bool
   */
  private static function isGitAvailable(): bool
  {
    $gitDir = self::repoRoot() . '/.git';
    return is_dir($gitDir) && self::commandExists('git');
  }

  /**
   * Check if a command exists in PATH.
   *
   * @param string $command
   * @return bool
   */
  private static function commandExists(string $command): bool
  {
    $return = shell_exec(sprintf("which %s 2>/dev/null", escapeshellarg($command)));
    return !empty($return);
  }

  /**
   * Get version from git describe.
   *
   * @return string|null
   */
  private static function getGitVersion(): ?string
  {
    $repoRoot = self::repoRoot();
    $command = sprintf(
      'cd %s && git describe --tags --dirty --always 2>/dev/null',
      escapeshellarg($repoRoot)
    );
    
    $output = shell_exec($command);
    if ($output !== null && $output !== false) {
      $trimmed = trim($output);
      if (!empty($trimmed)) {
        return $trimmed;
      }
    }

    return null;
  }

  /**
   * Resolve repository root for git metadata and VERSION file lookup.
   */
  private static function repoRoot(): string
  {
    if (self::$appHome !== '' && is_dir(self::$appHome)) {
      return rtrim(self::$appHome, '/');
    }

    return dirname(__DIR__, 4);
  }
}


