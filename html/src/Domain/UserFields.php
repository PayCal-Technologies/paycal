<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * UserFields.php
 *
 * Purpose: Define the UserFields enum for PayCal\Domain.
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
 * Canonical list of user settings stored in Redis.
 * Enum value = Redis field name.
 */
enum UserFields: string
{
  case USER_UUID = 'user_uuid';
  case FULL_NAME = 'full_name';
  case EMAIL = 'email';
  case PHONE = 'phone';
  case PASSWORD_HASH = 'password_hash';

  // E2E encryption salt
  case ENCRYPTION_SALT = 'encryption_salt';

  // DEK wrapping
  case WRAPPED_DEK = 'wrapped_dek';
  case WRAPPED_DEK_PASSKEY = 'wrapped_dek_passkey';
  case DEK_VERSION = 'dek_version';
  case CRYPTO_VERSION = 'crypto_version';

  // Email verification
  case EMAIL_VERIFIED = 'email_verified';
  case EMAIL_VERIFY_TOKEN_HASH = 'email_verify_token_hash';
  case EMAIL_VERIFY_EXPIRY = 'email_verify_expiry';

  // Recovery email
  case RECOVERY_EMAIL = 'recovery_email';
  case RECOVERY_EMAIL_VERIFIED = 'recovery_email_verified';
  case RECOVERY_EMAIL_VERIFIED_AT = 'recovery_email_verified_at';
  case RECOVERY_EMAIL_LAST_SENT_AT = 'recovery_email_last_sent_at';
  case RECOVERY_EMAIL_VERIFY_ATTEMPTS = 'recovery_email_verify_attempts';

  // Recovery key
  case ACCOUNT_RECOVERY_SALT = 'account_recovery_salt';
  case WRAPPED_DEK_RECOVERY = 'wrapped_dek_recovery';
  case RECOVERY_KEY_GENERATED = 'recovery_key_generated';
  case RECOVERY_PROOF_KEY = 'recovery_proof_key';
  case RECOVERY_PROOF_KEY_VERSION = 'recovery_proof_key_version';

  case THEME = 'theme';
  case VARIANT = 'variant';
  case LANGUAGE = 'language';
  case LOCALE = 'locale';
  case TEXT = 'text';
  case DENSITY = 'density';
  case DYSLEXIA_TYPOGRAPHY = 'dyslexia_typography';
  case NAV_POSITION_PRIMARY = 'nav_position_primary';
  case NAV_STATE_PRIMARY = 'nav_state_primary';

  case CALENDAR_AUTOFOCUS = 'calendar_autofocus';
  case CALENDAR_AUDIO_LABELS = 'calendar_audio_labels';
  case CALENDAR_DAY_NAME_FORMAT = 'calendar_day_name_format';
  case CALENDAR_DATE_LABEL_POSITION = 'calendar_date_label_position';
  case CALENDAR_WORK_ENTRY_POSITION = 'calendar_work_entry_position';
  case CALENDAR_WORK_ENTRY_FIELDS_HOURS = 'calendar_work_entry_fields_hours';
  case CALENDAR_WORK_ENTRY_FIELDS_OVERTIME = 'calendar_work_entry_fields_overtime';
  case CALENDAR_WORK_ENTRY_FIELDS_LIVING_OUT = 'calendar_work_entry_fields_living_out';
  case CALENDAR_WORK_ENTRY_FIELDS_TRAVEL = 'calendar_work_entry_fields_travel';
  case EDITING_GRACE_DAYS = 'editing_grace_days';
  case VOICE = 'voice';
  case AUDIO_FEEDBACK = 'audio_feedback';
  case DEBUG_CONSOLE_ENABLED = 'debug_console_enabled';
  case DEBUG_FINE_GRAINED_ENABLED = 'debug_fine_grained_enabled';
  case DEBUG_NETWORK_ENABLED = 'debug_network_enabled';

  case KEY_UUID = 'key_uuid';
  case PAY_FREQUENCY = 'pay_frequency';
  case PAY_ANCHOR = 'pay_anchor';
  case PAY_EPOCH = 'pay_epoch';
  case PAY_PERIOD_LENGTH = 'pay_period_length';
  case PAY_PERIOD_START = 'pay_period_start';
  case PAY_PERIOD_RANGE = 'pay_period_range';

  case DEFAULT_SITE_ID = 'default_site_id';
  case DEFAULT_HOURS = 'default_hours';
  case DEFAULT_LIVING_OUT_ALLOWANCE = 'default_living_out_allowance';
  case DEFAULT_TRAVEL_HOURS = 'default_travel_hours';

  case PROVINCE = 'province';
  case TIMEZONE = 'timezone';
  case CURRENCY = 'currency';
  case SESSION_TIMEOUT = 'session_timeout';
  case EMERGENCY_SIGNOUT_WINDOW_MS = 'emergency_signout_window_ms';
  case FORM_TTL_SETTINGS = 'form_ttl_settings';
  case FORM_TTL_CALENDAR = 'form_ttl_calendar';
  case FORM_TTL_GENERAL = 'form_ttl_general';

  // Work profile (optional — multi-employer / managed-user support)
  case EMPLOYMENT_TYPE = 'employment_type';
  case JOB_TITLE       = 'job_title';
  case DEPARTMENT      = 'department';
  case HIRE_DATE       = 'hire_date';
  case PAY_RATE        = 'pay_rate';
  case PAY_RATE_TYPE   = 'pay_rate_type';
  case ADDRESS_LINE1   = 'address_line1';
  case ADDRESS_CITY    = 'address_city';
  case ADDRESS_POSTAL  = 'address_postal';
}
