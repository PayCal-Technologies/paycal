<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Attributes\Enum;
use PayCal\Attributes\MinLength;
use PayCal\Attributes\Required;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\Enums\FormTTL;
use PayCal\Domain\Enums\SessionTimeout;
use PayCal\Domain\Enums\Subscription;
use PayCal\Domain\Enums\SubscriptionStatus;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Config\SystemConfig;

/**
 * User.php
 *
 * Purpose: Define the User class for PayCal\Domain.
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


/*
 * Class User
 *
 * Core user management class providing:
 * - Current user instance and authentication state
 * - User properties (name, email, preferences, settings)
 * - User permissions and role management
 * - User-related data access (work, earnings, sites, organizations)
 * - User preferences and configuration management
 */


final class User
{
  private const DEFAULT_PAY_PERIOD_LENGTH = '14';
  private const DEFAULT_PAY_PERIOD_START = '2024-01-01';

  public ?string $pay_frequency       = 'biweekly';
  public ?string $encryption_salt     = null;  // Encryption salt for PBKDF2 and HKDF
  public ?string $wrapped_dek         = null;  // Legacy password-wrapped DEK
  public ?string $wrapped_dek_passkey = null;  // DEK wrapped with passkey-derived KEK (HKDF)
  public int $dek_version             = 1;     // DEK version (for rotation)
  public int $crypto_version          = 1;     // Crypto version (for algorithm migration)

  // Email verification
  public bool $email_verified              = false;
  public ?string $email_verify_token_hash  = null;
  public ?string $email_verify_expiry      = null;

  // Recovery email
  public ?string $recovery_email           = null;
  public bool $recovery_email_verified     = false;
  public ?string $recovery_email_verified_at = null;
  public ?string $recovery_email_last_sent_at = null;
  public int $recovery_email_verify_attempts = 0;

  // Recovery key
  public ?string $account_recovery_salt    = null;  // Salt for recovery KEK derivation
  public ?string $wrapped_dek_recovery     = null;  // DEK wrapped with recovery-derived KEK
  public bool $recovery_key_generated      = false;
  public ?string $recovery_proof_key       = null;
  public int $recovery_proof_key_version   = 0;

  public ?string $pay_anchor      = 'Monday';
  public ?string $pay_epoch       = null;

  #[\PayCal\Domain\Attributes\Required]
  public string $user_uuid = '';

  #[\PayCal\Domain\Attributes\Required]
  #[\PayCal\Domain\Attributes\MinLength(1)]
  public string $full_name = '';

  #[\PayCal\Domain\Attributes\Required]
  #[\PayCal\Domain\Attributes\Email]
  public string $email = '';

  #[\PayCal\Domain\Attributes\Required]
  public string $password_hash = '';

  #[\PayCal\Domain\Attributes\Enum(AuthLevel::class)]
  public AuthLevel $auth_level                       = AuthLevel::USER;

  // Subscription management
  #[\PayCal\Domain\Attributes\Enum(Subscription::class)]
  public Subscription $subscription_tier             = Subscription::FREE;
  #[\PayCal\Domain\Attributes\Enum(SubscriptionStatus::class)]
  public SubscriptionStatus $subscription_status     = SubscriptionStatus::ACTIVE;
  public ?string $subscription_id                    = null;  // External payment provider ID
  public ?string $subscription_start_date            = null;  // ISO 8601 date when subscription became active
  public ?string $subscription_renewal_date          = null;  // ISO 8601 date for next renewal
  public ?string $subscription_cancel_date           = null;  // ISO 8601 date when canceled (for analytics)

  public string $last_session_hash                   = '';
  public ?string $last_signin                        = null;
  public ?string $last_signin_ip                     = null;
  public string $phone                               = '';
  public string $theme                               = UserPreferenceDefaults::DEFAULT_THEME;
  public string $variant                             = 'dark';
  public string $language                            = Language::DEFAULT;
  public string $text                                = UserPreferenceDefaults::DEFAULT_TEXT;
  public string $density                             = UserPreferenceDefaults::DEFAULT_DENSITY;
  public string $dyslexia_typography                 = UserPreferenceDefaults::DEFAULT_DYSLEXIA_TYPOGRAPHY;
  public string $nav_position_primary                = UserPreferenceDefaults::DEFAULT_NAV_POSITION_PRIMARY;
  public string $nav_state_primary                   = UserPreferenceDefaults::DEFAULT_NAV_STATE_PRIMARY;
  public string $calendar_autofocus                  = UserPreferenceDefaults::DEFAULT_CALENDAR_AUTOFOCUS;
  public string $calendar_audio_labels               = UserPreferenceDefaults::DEFAULT_CALENDAR_AUDIO_LABELS;
  public string $calendar_day_name_format            = UserPreferenceDefaults::DEFAULT_CALENDAR_DAY_NAME_FORMAT;
  public string $calendar_date_label_position        = UserPreferenceDefaults::DEFAULT_CALENDAR_DATE_LABEL_POSITION;
  public string $calendar_work_entry_position        = UserPreferenceDefaults::DEFAULT_CALENDAR_WORK_ENTRY_POSITION;
  public bool $calendar_work_entry_fields_hours      = true;
  public bool $calendar_work_entry_fields_overtime   = true;
  public bool $calendar_work_entry_fields_living_out = true;
  public bool $calendar_work_entry_fields_travel     = true;
  public string $voice                               = UserPreferenceDefaults::DEFAULT_VOICE;
  public string $audio_feedback                      = UserPreferenceDefaults::DEFAULT_AUDIO_FEEDBACK;
  public string $debug_console_enabled               = UserPreferenceDefaults::DEFAULT_DEBUG_CONSOLE_ENABLED;
  public string $debug_fine_grained_enabled          = UserPreferenceDefaults::DEFAULT_DEBUG_FINE_GRAINED_ENABLED;
  public string $debug_network_enabled               = UserPreferenceDefaults::DEFAULT_DEBUG_NETWORK_ENABLED;
  public ?string $key_uuid                           = null;
  public string $pay_period_length                   = self::DEFAULT_PAY_PERIOD_LENGTH;
  public string $pay_period_start                    = self::DEFAULT_PAY_PERIOD_START;
  public ?string $pay_period_range                   = null;
  public string $default_site_id                     = '';
  public string $default_hours                       = '';
  public string $default_living_out_allowance        = '';
  public string $default_travel_hours                = '';
  public string $province                            = 'AB';
  public string $timezone                            = 'America/Edmonton';
  public string $currency                            = 'CAD';
  public string $session_timeout                     = UserPreferenceDefaults::DEFAULT_SESSION_TIMEOUT;
  public string $emergency_signout_window_ms         = '600';
  public string $editing_grace_days                  = UserPreferenceDefaults::DEFAULT_EDITING_GRACE_DAYS;
  public string $form_ttl_settings                   = '3600';
  public string $form_ttl_calendar                   = '3600';
  public string $form_ttl_general                    = '3600';

  // Work profile (optional — multi-employer / managed-user support)
  public ?string $employment_type                    = null;
  public ?string $job_title                          = null;
  public ?string $department                         = null;
  public ?string $hire_date                          = null;
  public ?string $pay_rate                           = null;
  public ?string $pay_rate_type                      = null;
  public ?string $address_line1                      = null;
  public ?string $address_city                       = null;
  public ?string $address_postal                     = null;

  /** @var array<string, \stdClass> */
  public array $work = [];


  /**
   * Initializes a new instance.
   */
  public function __construct() { }
  /**
   * Prevents cloning or customizes clone behavior.
   */
  private function __clone() { }
  /**
   * Rehydrates the object after unserialization.
   */
  public function __wakeup(): void { }


  /**
   * Returns the UUID of the current user, or SystemConfig::PUBLIC_UUID if not authenticated.
   */
  public static function currentUUID(): string
  {
    return self::current()->user_uuid;
  }


  /**
   * Validates the User object against its attributes.
   * @return array<string, array<string>> array of validation errors, keyed by property name
   */
  public function validate(): array
  {
    $errors = [];
    $reflection = new \ReflectionClass($this);

    foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
      $name = $property->getName();
      $value = $this->{$name};
      $attributes = $property->getAttributes();

      foreach ($attributes as $attribute) {
        $attrInstance = $attribute->newInstance();
        $attrName = $attribute->getName();

        switch ($attrName) {
          case 'Required':
            if (empty($value))
              $errors[$name][] = 'is required';

            break;

          case 'Email':
            if (!filter_var($value, FILTER_VALIDATE_EMAIL))
              $errors[$name][] = 'must be a valid email address';

            break;

          case 'MinLength':
            if (property_exists($attrInstance, 'length') && is_int($attrInstance->length)) {
              if (strlen($value) < $attrInstance->length)
                $errors[$name][] = 'must be at least '.$attrInstance->length.' characters long';
            }

            break;

          case 'Enum':
            if (property_exists($attrInstance, 'enumClass')) {
              $enumClass = $attrInstance->enumClass;
              $cases = array_map(fn ($case) => $case->value, $enumClass::cases());
              if (!in_array($value->value ?? $value, $cases, true))
                $errors[$name][] = 'must be a valid '.$enumClass;
            }

            break;
        }
      }
    }

    return $errors;
  }


  /**
   * Get user from current session (cookie → uuid).
   * Returns null if there is no authenticated user.
   */
  public static function current(): User
  {
    // Use Authentication's validated cookie resolution (handles duplicate/stale cookies).
    $hash = Authentication::getCookie();

    if ($hash !== '') {
      $uuid = Authentication::getUserUUIDFromSession($hash);
      if ($uuid) {
        $user = UserRepository::getByUUID($uuid);
        if ($user !== null) {
          return $user;
        }
      }
    }
    return self::publicUser();
  }

  /**
   * Handles publicUser operation.
   */
  private static function publicUser(): User
  {
    $u = new User();
    $u->user_uuid = SystemConfig::PUBLIC_UUID;
    $u->language = Language::DEFAULT;
    return $u;
  }

  /**
   * Handles uuid operation.
   */
  public static function uuid(): string
	{

    return self::current()->user_uuid;
	}

  /**
   * Get a user by UUID.
   * @param string $uuid User UUID to retrieve
   * @return ?User The user object or null if not found
   */
  public static function getByUUID(string $uuid): ?User
  {
    return UserRepository::getByUUID($uuid);
  }

  /**
   * Set user information in the database.
   * @param string $userUUID User UUID
   * @param string $passwordHash Password hash
   * @param string $email User email
   * @param AuthLevel $authLevel User authorization level
   * @param string $fullName Full name
   * @param string $lastSessionHash Last session hash
   * @param string $phone Phone number
   */
  public static function setUser(
    string $userUUID,
    string $passwordHash,
    string $email,
    AuthLevel $authLevel,
    string $fullName,
    string $lastSessionHash,
    string $phone
  ): void
  {
    UserRepository::setUser($userUUID, $passwordHash, $email, $authLevel, $fullName, $lastSessionHash, $phone);
  }

  /**
   * Get user UUID by email address.
   * @param string $email Email address to look up
   * @return string The user UUID if found, empty string otherwise
   */
  public static function getUUIDFromEmail(string $email): string
  {
    return UserRepository::getUUIDFromEmail($email);
  }


  /**
   * Updates user preferences.
   * @param array<string, null|scalar> $newSettings
   */
  public function updateSettings(array $newSettings): bool
  {
    if (empty($newSettings))
      return false;

    if (isset($newSettings['density']) && (string) $newSettings['density'] === 'compact') {
      $newSettings['density'] = 'tight';
    }

    // Convert all values to strings for Redis hset
    $stringSettings = [];
    foreach ($newSettings as $key => $value) {
      $stringSettings[$key] = (string) ($value ?? '');
    }

    Database::hset(Keys::USER . ':' . $this->user_uuid, $stringSettings);

    return true;
  }

  /**
   * Render a paginated HTML list of users from Redis.
   * Iterates over user:* keys, hydrates basic fields, and feeds them into row templates.
   * Falls back to an “empty” template when no users exist.
   * @param int $start Zero-based index of first user to display
   * @param int $count Maximum number of users to include
   * @return string Rendered HTML table/list output
   */
  public static function listUsers(int $start = 0, int $count = SystemLimits::DEFAULT_PAGE_SIZE): string
  {
    $keys = Database::scanKeys('user:*');
    if (empty($keys))
      return Render::template('user-list-empty', ['__MESSAGE__' => 'No users found.']);

    $total    = count($keys);
    $end      = min($start + $count, $total);
    $rowsHTML = '';
    $i18n = [];
    $i18nKeys = ['DELETE', 'NAME', 'EMAIL', 'PHONE', 'USER_ROLE', 'ACTION'];
    foreach ($i18nKeys as $key) {
      $i18n[$key] = Strings::i18n($key);
    }

    for ($i = $start; $i < $end; ++$i) {
      $key      = $keys[$i];
      $data     = Database::hgetall($key);
      $userUUID = str_replace(SystemConfig::USER_ID_PREFIX, '', $key);

      $row = [
        '__USER_UUID__'  => htmlspecialchars($userUUID),
        '__FULL_NAME__'  => htmlspecialchars((string) ($data['full_name'] ?? 'N/A')),
        '__EMAIL__'      => htmlspecialchars((string) ($data['email'] ?? 'N/A')),
        '__PHONE__'      => htmlspecialchars((string) ($data['phone'] ?? 'N/A')),
        '__AUTH_LEVEL__' => htmlspecialchars((string) ($data['auth_level'] ?? 'N/A')),
        '__DELETE__'     => $i18n['DELETE'],
      ];
      $rowsHTML .= Render::template('user-list-row', $row);
    }

    return Render::template('user-list', [
      '__USER_ROWS_HTML__' => $rowsHTML,
      '__NAME__' => $i18n['NAME'],
      '__EMAIL__' => $i18n['EMAIL'],
      '__PHONE__' => $i18n['PHONE'],
      '__USER_ROLE__' => $i18n['USER_ROLE'],
      '__ACTION__' => $i18n['ACTION'],
    ]);
  }


  /**
   * Update last-signin metadata for a session.
   * Stores the current timestamp and visitor IP in the session hash.
   * No update is performed when the session hash is empty.
   * @param string $sessionHash Session identifier
   */
  public static function setLastSignin(string $sessionHash): void
  {
    if (empty($sessionHash))
      return;

    $timestamp = strval(time());
    $ip        = Security::getVisitorRealIPAddress();
    Database::hset(Keys::SESSION . ":" . $sessionHash, ['last_signin' => $timestamp]);
    Database::hset(Keys::SESSION . ":" . $sessionHash, ['last_ip' => $ip]);
  }


  /**
   * Store a time-stamped verification code for a user.
   * Codes are kept in a Redis hash keyed by user UUID and expire after one hour.
   * No action is taken when code or UUID is empty.
   * @param string      $code     Verification code
   * @param null|string $userUUID User UUID
   */
  public static function addVerificationCode(string $code, ?string $userUUID): void
  {
    if (empty($code) || empty($userUUID))
      return;

    $created = strval(time());
    $key     = Keys::VERIFICATION_CODES . ":" . InputSanitizer::sanitizeString($userUUID);
    Database::hset($key, [InputSanitizer::sanitizeString($code) => $created]);
    Database::expire($key, FormTTL::ONE_HOUR->value);
  }


  /**
   * Returns verification codes for a given user UUID.
   * @param string $userUUID The user UUID
   * @return array<string, string> // code => timestamp
   */
  public static function getVerificationCodes(string $userUUID): array
  {
    if (empty($userUUID))
      return [];

    $codes = Database::hgetall(Keys::VERIFICATION_CODES . ":" . InputSanitizer::sanitizeString($userUUID));

    return $codes ?: [];
  }




  /**
   * Retrieve available authentication level options.
   * @return array<string,string> Associative array of auth level keys to labels
   */
  public static function getAuthLevelOptions(): array
  {
    $options = [];

    foreach (AuthLevel::cases() as $case) {
      $options[$case->value] = ucfirst($case->name);
    }

    return $options;
  }


  /**
   * Determine whether the current authenticated user has admin privileges.
   * Returns false when no user is authenticated.
   * @return bool True if current user is ADMIN, otherwise false
   */
  public static function isAdmin(): bool
  {
    $user = self::current();
    \PayCal\Domain\Log::debug('[User::isAdmin] user=' . json_encode(["user_uuid"=>$user->user_uuid,"auth_level"=>$user->auth_level->value]));

    $isAdmin = $user->auth_level->atLeast(AuthLevel::ADMIN);
    \PayCal\Domain\Log::debug('[User::isAdmin] auth_level=' . $user->auth_level->value . ', isAdmin=' . ($isAdmin ? 'true' : 'false'));
    return $isAdmin;
  }

  /**
   * Handles isSuperAdmin operation.
   */
  public static function isSuperAdmin(): bool
  {
    $user = self::current();

    return AuthLevel::SUPERADMIN === $user->auth_level;
  }


  /**
   * Check if current user has manager privileges or higher.
   * @return bool True if user is a manager or admin
   */
  public static function isManager(): bool
  {
    $user = self::current();

    return $user->auth_level->atLeast(AuthLevel::MANAGER);
  }


  /**
   * Generate and return user's initials based on full name.
   * @return string Two-letter uppercase initials or "??" if unavailable
   */
  public function initials(): string
  {
    $parts = explode(' ', $this->full_name);
    $initials = '';

    foreach ($parts as $p) {
      if ('' !== $p) {
        $initials .= strtoupper(substr($p, 0, 1));
      }
    }

    if (strlen($initials) > 2) {
      $initials = substr($initials, 0, 2);
    } elseif ('' === $initials && '' !== $this->full_name) {
      $initials = strtoupper(substr($this->full_name, 0, 1));
    } elseif ('' === $initials) {
      $initials = '??';
    }

    return $initials;
  }


  /** Generate and return user's First name based on full name.
   * @return string Capitalized first name or "??" if unavailable
   */
  public function firstName(): string
  {
    $name = trim($this->full_name);
    if ('' === $name) {
      return '??';
    }
    $parts = explode(' ', $name);

    return ucfirst($parts[0]);
  }

  /**
   * Generates a CSRF nonce for a specific form type using the user's TTL setting.
   * @param string $formType 'settings', 'calendar', or 'general'
   * @return string the generated nonce
   */
  public function generateFormNonce(string $formType): string
  {
    $ttl = match ($formType) {
      'settings' => $this->getFormTtlSettingsSeconds(),
      'calendar' => $this->getFormTtlCalendarSeconds(),
      'general' => $this->getFormTtlGeneralSeconds(),
      default => FormTTL::ONE_HOUR->value,
    };

    $nonce = bin2hex(random_bytes(32));
    $key = "user:{$this->user_uuid}:csrf:{$formType}:{$nonce}";
    Database::set($key, (string) time(), $ttl);

    return $nonce;
  }


  /**
   * Verifies a CSRF nonce for a specific form type and deletes it (single-use).
   * @param string $formType 'settings', 'calendar', or 'general'
   * @param string $nonce    The nonce to verify
   * @return bool true if valid and deleted, false otherwise
   */
  public function verifyFormNonce(string $formType, string $nonce): bool
  {
    $key = "user:{$this->user_uuid}:csrf:{$formType}:{$nonce}";

    return Database::exists($key);
    // Don't delete the token - just verify it exists and is valid (TTL-based)
    // This allows multiple submissions with the same token
  }


  /**
   * Returns the user's CSP nonce from Redis if it exists.
   * Generates, stores, and returns a new cryptographically secure nonce if missing.
   * @return string the existing or newly generated nonce value
   */
  public static function nonce(): string
  {

    $uuid = User::currentUUID();
    $key = Keys::SESSION . ':' . $uuid . ':nonce';

    // Ensure timeout is between 1 minute and 2 hours
    $timeout = (int) Database::hget(Keys::USER . ':' . $uuid, 'session_timeout');
    if ($timeout <= 0) {
      $timeout = SessionTimeout::TWO_HOURS->value;
    } // default 2 hours
    $ttl = (int) min(SessionTimeout::TWO_HOURS->value, max(SessionTimeout::ONE_MIN->value, $timeout));

    $nonce = (string) Database::get($key);
    if ('' !== $nonce) {
      // Sliding window since activity
      Database::expire($key, $ttl);
    } else {
      $nonce = bin2hex(random_bytes(32));
      Database::set($key, $nonce, $ttl);
    }

    return $nonce;
  }


  /**
   * Store a CSRF/form nonce for the current user and action context.
   * @param string $context The action or form context (e.g., "calendar").
   * @param string $nonce The nonce to store
   */
  public function setFormNonce(string $context, string $nonce): void
  {
    $key = Keys::SESSION . ':' . $this->user_uuid . ':form_nonce:' . $context;
    $timeout = (int) Database::hget(Keys::USER . ':' . $this->user_uuid, 'session_timeout');
    if ($timeout <= 0) {
      $timeout = SessionTimeout::TWO_HOURS->value;
    }
    $ttl = (int) min(SessionTimeout::TWO_HOURS->value, max(SessionTimeout::ONE_MIN->value, $timeout));
    Database::set($key, $nonce, $ttl);
  }


  /**
   * Retrieve the stored CSRF/form nonce for the current user and action context.
   * @param string $context The action or form context (e.g., "calendar").
   * @return null|string the stored nonce or null if not found
   */
  public function getFormNonces(string $context): ?string
  {
    $key = Keys::SESSION . ':' . $this->user_uuid . ':form_nonce:' . $context;
    return (string) Database::get($key) ?: null;
  }


  /**
   * Validate a CSRF token against the stored nonce for the given context.
   * Removes the nonce after successful validation to prevent replay attacks.
   * @param string $context The action or form context (e.g., "calendar").
   * @param string $token   the token to validate
   * @return bool true if valid, false otherwise
   */
  public function validateCSRFToken(string $context, string $token): bool
  {
    $key = Keys::SESSION . ':' . $this->user_uuid . ':form_nonce:' . $context;
    $stored = (string) Database::get($key);
    if ('' === $stored || $stored !== $token)
      return false;
    Database::del($key);
    return true;
  }



  /**
   * Get the session timeout in seconds based on user's setting.
   * @return int timeout in seconds, or 0 for no timeout
   */
  public function getSessionTimeoutSeconds(): int
  {
    $validTimeouts = array_map(fn (SessionTimeout $case) => $case->value, SessionTimeout::cases());
    if (!in_array($this->session_timeout, $validTimeouts, true)) {
      return FormTTL::THIRTY_DAYS->value;
    } // Default to 30 days if invalid

    return (int) $this->session_timeout;
  }

  /**
   * Get emergency ESC signout window in milliseconds.
   * @return int window in ms (200 to 2000, 200ms increments)
   */
  public function getEmergencySignoutWindowMs(): int
  {
    $window = (int) $this->emergency_signout_window_ms;
    if ($window < 200 || $window > 2000 || ($window % 200) !== 0) {
      return 600;
    }

    return $window;
  }


  /**
   * Get the form TTL for settings in seconds based on user's setting.
   * @return int TTL in seconds
   */
  public function getFormTtlSettingsSeconds(): int
  {
    $validTtls = array_map(fn (FormTTL $case) => $case->value, FormTTL::cases());
    if (!in_array((int) $this->form_ttl_settings, $validTtls, true))
      return FormTTL::ONE_HOUR->value;

    return (int) $this->form_ttl_settings;
  }

  /**
   * Get the form TTL for calendar in seconds based on user's setting.
   * @return int TTL in seconds
   */
  public function getFormTtlCalendarSeconds(): int
  {
    $validTtls = array_map(fn (FormTTL $case) => $case->value, FormTTL::cases());
    if (!in_array((int) $this->form_ttl_calendar, $validTtls, true))
      return FormTTL::ONE_HOUR->value;

    return (int) $this->form_ttl_calendar;
  }

  /**
   * Get the form TTL for general in seconds based on user's setting.
   * @return int TTL in seconds
   */
  public function getFormTtlGeneralSeconds(): int
  {
    $validTtls = array_map(fn (FormTTL $case) => $case->value, FormTTL::cases());
    if (!in_array((int) $this->form_ttl_general, $validTtls, true))
      return FormTTL::ONE_HOUR->value;

    return (int) $this->form_ttl_general;
  }



  /**
   * Generates a unique User UUID identifier.
   * @return string a short unique ID based on SHA-256 hash with added entropy, prefixed with 'U'
   */
  public static function generateUserUUID(): string
  {
    // Generate a random seed for additional entropy (256 bits)
    $randomSeed = bin2hex(random_bytes(32));
    $combinedData = $randomSeed.SystemConfig::UUID_SALT;
    $hA256Hash = hash('sha256', $combinedData);

    // Format: U<8 hex chars> e.g., Ub9127d01
    return 'U'.substr($hA256Hash, 0, 8);
  }
}



