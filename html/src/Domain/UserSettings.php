<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * UserSettings.php
 *
 * Purpose: Define the UserSettings class for PayCal\Domain.
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
 * Class UserSettings.
 *
 * Central schema for all user settings and preferences.
 * Responsibilities include:
 * - Mapping Redis fields to User object properties
 * - Defining validation rules for each setting
 * - Grouping settings by feature area (theme, calendar, notifications, etc.)
 * - Providing type-safe access to all user-configurable preferences
 */
final class UserSettings
{
  private string $userUUID;

  /** @var array<string, mixed> */
  private array $cache = [];

  /**
   * Get a UserSettings instance for a user UUID.
   */
  public static function getInstance(string $userUUID): self
  {
    $instance = new self();
    $instance->userUUID = $userUUID;

    return $instance;
  }

  /**
   * Get a user setting value by key.
   */
  public function get(string $key): mixed
  {
    // Try cache first
    if (array_key_exists($key, $this->cache)) {
      return $this->cache[$key];
    }
    // Fetch from Redis
    $redis = Database::getInstance();
    $value = $redis->hGet('user:settings:' . $this->userUUID, $key);
    $this->cache[$key] = $value;

    return $value;
  }

  /**
   * Set a user setting value by key.
   */
  public function set(string $key, mixed $value): void
  {
    $redis = Database::getInstance();
    $redisValue = is_scalar($value) ? (string) $value : '';
    $redis->hSet('user:settings:' . $this->userUUID, $key, $redisValue);
    $this->cache[$key] = $value;
  }

  /**
   * Redis field → User object property mapping.
   *
   * @return array<string,string>
   */
  public static function fieldMap(): array
  {
    return [
        UserFields::FULL_NAME->value => 'full_name',
        UserFields::EMAIL->value => 'email',
        UserFields::PHONE->value => 'phone',
        UserFields::THEME->value => 'theme',
        UserFields::VARIANT->value => 'variant',
        UserFields::LANGUAGE->value => 'language',
        UserFields::TEXT->value => 'text',
        UserFields::DENSITY->value => 'density',
        UserFields::DYSLEXIA_TYPOGRAPHY->value => 'dyslexia_typography',
        UserFields::NAV_POSITION_PRIMARY->value => 'nav_position_primary',
        UserFields::NAV_STATE_PRIMARY->value => 'nav_state_primary',
        UserFields::CALENDAR_AUTOFOCUS->value => 'calendar_autofocus',
        UserFields::CALENDAR_AUDIO_LABELS->value => 'calendar_audio_labels',
        UserFields::CALENDAR_DAY_NAME_FORMAT->value => 'calendar_day_name_format',
        UserFields::CALENDAR_DATE_LABEL_POSITION->value => 'calendar_date_label_position',
        UserFields::CALENDAR_WORK_ENTRY_POSITION->value => 'calendar_work_entry_position',
        UserFields::CALENDAR_WORK_ENTRY_FIELDS_HOURS->value => 'calendar_work_entry_fields_hours',
        UserFields::CALENDAR_WORK_ENTRY_FIELDS_OVERTIME->value => 'calendar_work_entry_fields_overtime',
        UserFields::CALENDAR_WORK_ENTRY_FIELDS_LIVING_OUT->value => 'calendar_work_entry_fields_living_out',
        UserFields::CALENDAR_WORK_ENTRY_FIELDS_TRAVEL->value => 'calendar_work_entry_fields_travel',
        UserFields::VOICE->value => 'voice',
        UserFields::AUDIO_FEEDBACK->value => 'audio_feedback',
        UserFields::DEBUG_CONSOLE_ENABLED->value => 'debug_console_enabled',
        UserFields::DEBUG_FINE_GRAINED_ENABLED->value => 'debug_fine_grained_enabled',
        UserFields::DEBUG_NETWORK_ENABLED->value => 'debug_network_enabled',
        UserFields::KEY_UUID->value => 'key_uuid',
        UserFields::PAY_FREQUENCY->value => 'pay_frequency',
        UserFields::PAY_ANCHOR->value => 'pay_anchor',
        UserFields::PAY_EPOCH->value => 'pay_epoch',
        UserFields::PAY_PERIOD_LENGTH->value => 'pay_period_length',
        UserFields::PAY_PERIOD_START->value => 'pay_period_start',
        UserFields::PAY_PERIOD_RANGE->value => 'pay_period_range',
        UserFields::DEFAULT_SITE_ID->value => 'default_site_id',
        UserFields::DEFAULT_HOURS->value => 'default_hours',
        UserFields::DEFAULT_LIVING_OUT_ALLOWANCE->value => 'default_living_out_allowance',
        UserFields::DEFAULT_TRAVEL_HOURS->value => 'default_travel_hours',
        UserFields::PROVINCE->value => 'province',
        UserFields::TIMEZONE->value => 'timezone',
        UserFields::EMPLOYMENT_TYPE->value => 'employment_type',
        UserFields::JOB_TITLE->value => 'job_title',
        UserFields::DEPARTMENT->value => 'department',
        UserFields::HIRE_DATE->value => 'hire_date',
        UserFields::PAY_RATE->value => 'pay_rate',
        UserFields::PAY_RATE_TYPE->value => 'pay_rate_type',
        UserFields::ADDRESS_LINE1->value => 'address_line1',
        UserFields::ADDRESS_CITY->value => 'address_city',
        UserFields::ADDRESS_POSTAL->value => 'address_postal',
        UserFields::SESSION_TIMEOUT->value => 'session_timeout',
        UserFields::EMERGENCY_SIGNOUT_WINDOW_MS->value => 'emergency_signout_window_ms',
        UserFields::FORM_TTL_SETTINGS->value => 'form_ttl_settings',
        UserFields::FORM_TTL_CALENDAR->value => 'form_ttl_calendar',
        UserFields::FORM_TTL_GENERAL->value => 'form_ttl_general',
    ];
  }

  /**
   * Allowed POST string fields for RequestGuard.
   *
   * @return array<string>
   */
  public static function allowedStrings(): array
  {
    return array_map(
      static fn (UserFields $s) => $s->value,
      UserFields::cases()
    );
  }

  /**
   * Optional groupings for controllers (account/info, style, audio, etc).
   *
   * @return array<string,array<string>>
   */
  public static function groups(): array
  {
    return [
        'account_info' => [
            UserFields::FULL_NAME->value,
            UserFields::PHONE->value,
            UserFields::PROVINCE->value,
            UserFields::TIMEZONE->value,
            UserFields::CURRENCY->value,
          UserFields::EMPLOYMENT_TYPE->value,
          UserFields::JOB_TITLE->value,
          UserFields::DEPARTMENT->value,
          UserFields::HIRE_DATE->value,
          UserFields::PAY_RATE->value,
          UserFields::PAY_RATE_TYPE->value,
          UserFields::ADDRESS_LINE1->value,
          UserFields::ADDRESS_CITY->value,
          UserFields::ADDRESS_POSTAL->value,
        ],

        'style' => [
            UserFields::THEME->value,
            UserFields::LANGUAGE->value,
            UserFields::TEXT->value,
            UserFields::DENSITY->value,
            UserFields::DYSLEXIA_TYPOGRAPHY->value,
            UserFields::NAV_POSITION_PRIMARY->value,
            UserFields::NAV_STATE_PRIMARY->value,
        ],

        'calendar' => [
            UserFields::CALENDAR_AUTOFOCUS->value,
            UserFields::CALENDAR_AUDIO_LABELS->value,
            UserFields::CALENDAR_DAY_NAME_FORMAT->value,
            UserFields::CALENDAR_DATE_LABEL_POSITION->value,
            UserFields::CALENDAR_WORK_ENTRY_POSITION->value,
            UserFields::CALENDAR_WORK_ENTRY_FIELDS_HOURS->value,
            UserFields::CALENDAR_WORK_ENTRY_FIELDS_OVERTIME->value,
            UserFields::CALENDAR_WORK_ENTRY_FIELDS_LIVING_OUT->value,
            UserFields::CALENDAR_WORK_ENTRY_FIELDS_TRAVEL->value,
        ],

        'audio' => [
          UserFields::VOICE->value,
            UserFields::AUDIO_FEEDBACK->value,
        ],

        'debug' => [
          UserFields::DEBUG_CONSOLE_ENABLED->value,
          UserFields::DEBUG_FINE_GRAINED_ENABLED->value,
          UserFields::DEBUG_NETWORK_ENABLED->value,
        ],

        'pay_period' => [
            UserFields::PAY_FREQUENCY->value,
            UserFields::PAY_ANCHOR->value,
            UserFields::PAY_EPOCH->value,
            UserFields::PAY_PERIOD_LENGTH->value,
            UserFields::PAY_PERIOD_START->value,
            UserFields::PAY_PERIOD_RANGE->value,
            UserFields::EDITING_GRACE_DAYS->value,
        ],

        'work_profile' => [
          UserFields::EMPLOYMENT_TYPE->value,
          UserFields::JOB_TITLE->value,
          UserFields::DEPARTMENT->value,
          UserFields::HIRE_DATE->value,
          UserFields::PAY_RATE->value,
          UserFields::PAY_RATE_TYPE->value,
          UserFields::ADDRESS_LINE1->value,
          UserFields::ADDRESS_CITY->value,
          UserFields::ADDRESS_POSTAL->value,
        ],
    ];
  }
}
