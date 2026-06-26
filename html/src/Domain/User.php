<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Attributes\Enum;
use PayCal\Attributes\MinLength;
use PayCal\Attributes\Required;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\Enums\FormTTL;
use PayCal\Domain\Enums\SessionTimeout;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Config\SystemConfig;

/**
 * User.php
 *
 * Purpose: Core authenticated user entity: Redis-backed properties, session state,
 *          role resolution, and preference access.
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
 * - User-related data access (work, earnings, sites, businesses)
 * - User preferences and configuration management
 */


final class User
{
  private const DEFAULT_PAY_PERIOD_LENGTH = '14';
  private const DEFAULT_PAY_PERIOD_START = '2024-01-01';

  public ?string $pay_frequency       = 'biweekly';
  public ?string $encryption_salt     = null;  // Encryption salt for PBKDF2 and HKDF
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
  public ?string $recovery_key_updated_at  = null;
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

  #[\PayCal\Domain\Attributes\Enum(AuthLevel::class)]
  public AuthLevel $auth_level                       = AuthLevel::USER;

  public string $last_session_hash                   = '';
  public ?string $last_signin                        = null;
  public ?string $last_signin_ip                     = null;
  public string $phone                               = '';
  public string $theme                               = UserPreferenceDefaults::DEFAULT_THEME;
  public string $variant                             = 'dark';
  public string $language                            = Language::DEFAULT;
  public string $locale                              = 'en-CA';
  public string $text                                = UserPreferenceDefaults::DEFAULT_TEXT;
  public string $spacing                             = UserPreferenceDefaults::DEFAULT_SPACING;
  public string $depth                               = UserPreferenceDefaults::DEFAULT_DEPTH;
  public string $dyslexia_typography                 = UserPreferenceDefaults::DEFAULT_DYSLEXIA_TYPOGRAPHY;
  public string $help_popup_timeout_seconds          = UserPreferenceDefaults::DEFAULT_HELP_POPUP_TIMEOUT_SECONDS;
  public string $toast_position                      = UserPreferenceDefaults::DEFAULT_TOAST_POSITION;
  public string $toast_width_preset                  = UserPreferenceDefaults::DEFAULT_TOAST_WIDTH_PRESET;
  public string $toast_font_size                     = UserPreferenceDefaults::DEFAULT_TOAST_FONT_SIZE;
  public string $nav_position_primary                = UserPreferenceDefaults::DEFAULT_NAV_POSITION_PRIMARY;
  public string $nav_state_primary                   = UserPreferenceDefaults::DEFAULT_NAV_STATE_PRIMARY;
  public string $nav_proximity                       = UserPreferenceDefaults::DEFAULT_NAV_PROXIMITY;
  public string $nav_overlay                         = UserPreferenceDefaults::DEFAULT_NAV_OVERLAY;
  public string $nav_proximity_px                    = UserPreferenceDefaults::DEFAULT_NAV_PROXIMITY_PX;
  public string $nav_proximity_delay_ms              = UserPreferenceDefaults::DEFAULT_NAV_PROXIMITY_DELAY_MS;
  public string $overlay_sidebar_timeout_seconds     = UserPreferenceDefaults::DEFAULT_OVERLAY_SIDEBAR_TIMEOUT_SECONDS;
  public string $calendar_autofocus                  = UserPreferenceDefaults::DEFAULT_CALENDAR_AUTOFOCUS;
  public string $calendar_audio_labels               = UserPreferenceDefaults::DEFAULT_CALENDAR_AUDIO_LABELS;
  public string $calendar_day_name_format            = UserPreferenceDefaults::DEFAULT_CALENDAR_DAY_NAME_FORMAT;
  public string $calendar_day_name_position          = UserPreferenceDefaults::DEFAULT_CALENDAR_DAY_NAME_POSITION;
  public string $calendar_date_label_position        = UserPreferenceDefaults::DEFAULT_CALENDAR_DATE_LABEL_POSITION;
  public string $calendar_work_entry_position        = UserPreferenceDefaults::DEFAULT_CALENDAR_WORK_ENTRY_POSITION;
  public bool $calendar_work_entry_fields_hours      = true;
  public bool $calendar_work_entry_fields_regular    = true;
  public bool $calendar_work_entry_fields_overtime   = true;
  public bool $calendar_work_entry_fields_living_out = true;
  public bool $calendar_work_entry_fields_travel     = true;
  public string $calendar_week_start                 = UserPreferenceDefaults::DEFAULT_CALENDAR_WEEK_START;
  public string $calendar_default_view               = UserPreferenceDefaults::DEFAULT_CALENDAR_DEFAULT_VIEW;
  public bool $calendar_show_gross_badge              = false;
  public bool $calendar_show_net_badge                = false;
  public bool $calendar_show_deductions_badge         = false;
  public bool $calendar_highlight_pay_period          = false;
  public string $accent_preset                       = UserPreferenceDefaults::DEFAULT_ACCENT_PRESET;
  public string $high_contrast_enabled                = UserPreferenceDefaults::DEFAULT_HIGH_CONTRAST_ENABLED;
  public string $reduced_motion_enabled               = UserPreferenceDefaults::DEFAULT_REDUCED_MOTION_ENABLED;
  public string $sr_verbosity                        = UserPreferenceDefaults::DEFAULT_SR_VERBOSITY;
  public string $keyboard_shortcuts_hint             = UserPreferenceDefaults::DEFAULT_KEYBOARD_SHORTCUTS_HINT;
  public string $require_reauth_export               = UserPreferenceDefaults::DEFAULT_REQUIRE_REAUTH_EXPORT;
  public string $require_reauth_import               = UserPreferenceDefaults::DEFAULT_REQUIRE_REAUTH_IMPORT;
  public string $export_encrypt_preference            = UserPreferenceDefaults::DEFAULT_EXPORT_ENCRYPT_PREFERENCE;
  public string $debug_ttl_minutes                   = UserPreferenceDefaults::DEFAULT_DEBUG_TTL_MINUTES;
  public string $debug_enabled_until                 = '';
  public string $voice                               = UserPreferenceDefaults::DEFAULT_VOICE;
  public string $voice_volume                        = UserPreferenceDefaults::DEFAULT_VOICE_VOLUME;
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
  public bool $indigenous_tax_exemption_eligible     = false;
  public bool $lives_on_reserve                      = false;
  public string $reserve_name                        = '';
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
   * Updates user preferences.
   * @param array<string, null|scalar> $newSettings
   */
  public function updateSettings(array $newSettings): bool
  {
    if (empty($newSettings))
      return false;

    if (isset($newSettings['spacing']) && (string) $newSettings['spacing'] === 'compact') {
      $newSettings['spacing'] = 'tight';
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
    Database::hsetex($key, [PayCalCode::normalize(InputSanitizer::sanitizeString($code)) => $created], FormTTL::ONE_HOUR->value);
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
   * True if the current user holds the AUDITOR role.
   * Auditors may access the /soc evidence portal but cannot access admin pages.
   */
  public static function isAuditor(): bool
  {
    $user = self::current();

    return AuthLevel::AUDITOR === $user->auth_level;
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
    if ($nonce === '') {
      return false;
    }

    $key = "user:{$this->user_uuid}:csrf:{$formType}:{$nonce}";
    // GETDEL atomically reads and removes the nonce in one round trip.
    return Database::getdel($key) !== '';
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
    // GETDEL atomically reads and removes the nonce in one round trip.
    // A separate get+del pair has a replay window where a concurrent request
    // can read the same nonce before it is deleted and also pass validation.
    $stored = Database::getdel($key);
    if ('' === $stored || $stored !== $token)
      return false;
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
   * Get help popup auto-dismiss timeout in seconds.
   * @return int timeout in seconds, or 0 for no auto-dismiss
   */
  public function getHelpPopupTimeoutSeconds(): int
  {
    $value = (int) $this->help_popup_timeout_seconds;
    if ($value < 0 || $value > 30) {
      return (int) UserPreferenceDefaults::DEFAULT_HELP_POPUP_TIMEOUT_SECONDS;
    }

    return $value;
  }

  /**
   * Toast anchor position preset.
   */
  public function getToastPosition(): string
  {
    $value = strtolower(trim($this->toast_position));
    $allowed = ['top-left', 'top-center', 'top-right', 'bottom-left', 'bottom-center', 'bottom-right', 'full-top', 'full-bottom'];

    return in_array($value, $allowed, true)
      ? $value
      : UserPreferenceDefaults::DEFAULT_TOAST_POSITION;
  }

  /**
   * Toast max-width preset.
   */
  public function getToastWidthPreset(): string
  {
    $value = strtolower(trim($this->toast_width_preset));
    $allowed = ['tiny', 'narrow', 'normal', 'large', 'larger', 'full-width'];

    return in_array($value, $allowed, true)
      ? $value
      : UserPreferenceDefaults::DEFAULT_TOAST_WIDTH_PRESET;
  }

  /**
   * Toast font size slider value (-5…+5).
   */
  public function getToastFontSize(): int
  {
    $raw = trim($this->toast_font_size);
    if ($raw === '' || preg_match('/^-?\d+$/', $raw) !== 1) {
      return (int) UserPreferenceDefaults::DEFAULT_TOAST_FONT_SIZE;
    }

    return max(-5, min(5, (int) $raw));
  }

  /**
   * Sidebar proximity hover enabled.
   */
  public function isNavProximityEnabled(): bool
  {
    $value = strtolower(trim($this->nav_proximity));

    return $value === 'on' || $value === '';
  }

  /**
   * Sidebar overlay mode (true = overlay, false = push).
   */
  public function isNavOverlayMode(): bool
  {
    return strtolower(trim($this->nav_overlay)) === 'overlay';
  }

  /**
   * Sidebar proximity trigger distance in px (0–600).
   */
  public function getNavProximityPx(): int
  {
    $value = (int) $this->nav_proximity_px;
    if ($value < 0 || $value > 600) {
      return (int) UserPreferenceDefaults::DEFAULT_NAV_PROXIMITY_PX;
    }

    return $value;
  }

  /**
   * Sidebar proximity trigger delay in ms (200–3000).
   */
  public function getNavProximityDelayMs(): int
  {
    $value = (int) $this->nav_proximity_delay_ms;
    if ($value < 200 || $value > 3000) {
      return (int) UserPreferenceDefaults::DEFAULT_NAV_PROXIMITY_DELAY_MS;
    }

    return $value;
  }

  /**
   * Get overlay sidebar auto-collapse timeout in seconds.
   * @return int timeout in seconds, or 0 for no auto-collapse
   */
  public function getOverlaySidebarTimeoutSeconds(): int
  {
    $value = (int) $this->overlay_sidebar_timeout_seconds;
    if ($value < 0 || $value > 30) {
      return (int) UserPreferenceDefaults::DEFAULT_OVERLAY_SIDEBAR_TIMEOUT_SECONDS;
    }

    return $value;
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
