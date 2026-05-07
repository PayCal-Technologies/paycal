<?php declare(strict_types=1);

namespace PayCal\Domain\Config;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;

/**
 * SystemConfig.php
 *
 * Purpose: System-wide immutable defaults and configurable limit schema for
 * pagination, quotas, work constraints, session settings, and UX guardrails.
 *
 * Developer notes:
 * - SystemConfig constants and schema shape are consumed widely across domain
 *   logic, validation, and settings UI.
 * - Treat key/schema changes as cross-system compatibility changes.
 *
 * Architectural role:
 * - Reusable configuration authority for system defaults, limits, and runtime
 *   schema metadata.
 * - Encapsulates system-level configuration policy outside the HTTP layer.
 *
 * @category   Config
 * @package    PayCal\Domain\Config
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * System configuration authority.
 *
 * Responsibilities:
 * - Define immutable app-level constants and runtime defaults.
 * - Provide normalized schema metadata for configurable system limits.
 * - Serve as the canonical source for limit constraints and labels.
 */
final class SystemConfig
{
  public const FONT_SIZE_ADJUSTMENT_OVERRIDE = 'font_size_adjustment_override_px';
  public const DENSITY_ADJUSTMENT_OVERRIDE = 'density_adjustment_override_px';
  public const TEXT_SMALLER = "smaller";
  public const TEXT_BASE = "base";
  public const TEXT_LARGER = "larger";
  public const SPACING_LESS = "less";
  public const SPACING_MORE = "more";
  public const LINEHEIGHT_LESS = "less";
  public const LINEHEIGHT_MORE = "more";
  public const PC_THEME_DEFAULT = "default";
  public const DEFAULT_LANGUAGE = "en";

  public const PC_VERIFICATION_SET = 'ABCDEFGHJKLMNPQRTUWXYZ346789';
  public const PC_VERIFICATION_LENGTH = 6;
  public const PC_VERIFICATION_SEPARATOR = '-';
  public const PC_UUID_SET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  public const UUID_SALT = 'paycal-uuid-salt';
  public const PUBLIC_UUID = "PUBLIC";
  public const USER_ID_PREFIX = 'user:';

  public const MAX_STRING_LENGTH = 4096;
  public const MAX_ARRAY_DEPTH = 10;

  public const MAX_USER_RESULTS = 10;
  public const DEFAULT_PAGE_SIZE = 100;

  public const ENCRYPTED_BLOB_MAX_BYTES = 4096;
  public const ENCRYPTION_KEY_CACHE_TTL = 30;
  public const ENCRYPTION_TELEMETRY_SCHEMA = 'v1';

    /**
     * Limit schema definition
     * Each limit has: type, default, min, max, label, help, category.
     *
     * @var array<string, array{type: string, default: bool|float|int|string, min?: float|int, max?: float|int, label: string, help: string, category: string, options?: array<string>}>
     */
    private const SCHEMA = [
      'pagination_max_per_page' => [
        'type' => 'int',
        'category' => 'pagination',
        'default' => 100,
        'min' => 10,
        'max' => 1000,
        'label' => 'Max Items Per Page',
        'help' => 'Maximum number of items displayed in paginated lists',
      ],
      'pagination_default_per_page' => [
        'type' => 'int',
        'category' => 'pagination',
        'default' => 50,
        'min' => 10,
        'max' => 500,
        'label' => 'Default Items Per Page',
        'help' => 'Default number of items per page',
      ],
      'max_user_sites' => [
        'type' => 'int',
        'category' => 'quotas',
        'default' => 100,
        'min' => 10,
        'max' => 1000,
        'label' => 'Max Sites Per User',
        'help' => 'Maximum number of sites a user can create',
      ],
      'min_character_length' => [
        'type' => 'int',
        'category' => 'quotas',
        'default' => 3,
        'min' => 1,
        'max' => 10,
        'label' => 'Min Character Length',
        'help' => 'Minimum character length for text inputs',
      ],
      'hash_length' => [
        'type' => 'int',
        'category' => 'quotas',
        'default' => 9,
        'min' => 6,
        'max' => 16,
        'label' => 'Hash Length',
        'help' => 'Length of generated hash identifiers',
      ],
      'year_min' => [
        'type' => 'int',
        'category' => 'quotas',
        'default' => 2020,
        'min' => 2000,
        'max' => 2050,
        'label' => 'Minimum Year',
        'help' => 'Earliest year allowed in the system',
      ],
      'year_max' => [
        'type' => 'int',
        'category' => 'quotas',
        'default' => 2100,
        'min' => 2050,
        'max' => 2150,
        'label' => 'Maximum Year',
        'help' => 'Latest year allowed in the system',
      ],
      'session_timeout_minutes' => [
        'type' => 'int',
        'category' => 'session',
        'default' => 1440,
        'min' => 30,
        'max' => 10080,
        'label' => 'Session Timeout (min)',
        'help' => 'User session timeout (30 min - 7 days)',
      ],
      'cookie_expiry_days' => [
        'type' => 'int',
        'category' => 'session',
        'default' => 30,
        'min' => 1,
        'max' => 365,
        'label' => 'Cookie Expiry (d)',
        'help' => 'Cookie expiration time in days',
      ],
      'new_session_ttl_days' => [
        'type' => 'int',
        'category' => 'session',
        'default' => 30,
        'min' => 1,
        'max' => 90,
        'label' => 'New Session TTL (d)',
        'help' => 'Time-to-live for new sessions in Redis',
      ],
      'new_user_ttl_hours' => [
        'type' => 'int',
        'category' => 'session',
        'default' => 1,
        'min' => 1,
        'max' => 48,
        'label' => 'New User TTL (h)',
        'help' => 'Time-to-live for new user data in Redis',
      ],
      'default_pay_period_length' => [
        'type' => 'int',
        'category' => 'work',
        'default' => 14,
        'min' => 7,
        'max' => 31,
        'label' => 'Default Pay Period (d)',
        'help' => 'Default pay period length in days',
      ],
      'default_work_week_length' => [
        'type' => 'int',
        'category' => 'work',
        'default' => 7,
        'min' => 5,
        'max' => 7,
        'label' => 'Work Week Length (d)',
        'help' => 'Number of days in a work week',
      ],
      'max_daily_regular_hours' => [
        'type' => 'int',
        'category' => 'work',
        'default' => 8,
        'min' => 6,
        'max' => 12,
        'label' => 'Max Daily Regular Hours',
        'help' => 'Maximum regular hours before overtime',
      ],
      'max_weekly_regular_hours' => [
        'type' => 'int',
        'category' => 'work',
        'default' => 44,
        'min' => 35,
        'max' => 60,
        'label' => 'Max Weekly Regular Hours',
        'help' => 'Maximum weekly hours before overtime',
      ],
      'pay_overtime_scale' => [
        'type' => 'float',
        'category' => 'work',
        'default' => 1.5,
        'min' => 1.0,
        'max' => 3.0,
        'label' => 'Overtime Pay Scale',
        'help' => 'Multiplier for overtime pay (e.g., 1.5 = time-and-a-half)',
      ],
      'editing_grace_days_min' => [
        'type' => 'int',
        'category' => 'work',
        'default' => 0,
        'min' => 0,
        'max' => 0,
        'label' => 'Min Editing Grace Days',
        'help' => 'Minimum grace period for editing work entries after pay period ends',
      ],
      'editing_grace_days_max' => [
        'type' => 'int',
        'category' => 'work',
        'default' => 3,
        'min' => 3,
        'max' => 7,
        'label' => 'Max Editing Grace Days',
        'help' => 'Maximum grace period for editing work entries after pay period ends',
      ],
      'max_daily_hours_absolute' => [
        'type' => 'int',
        'category' => 'work',
        'default' => 24,
        'min' => 16,
        'max' => 24,
        'label' => 'Max Daily Hours (absolute)',
        'help' => 'Hard limit on hours per day',
      ],
      'min_daily_hours' => [
        'type' => 'float',
        'category' => 'work',
        'default' => 0.0,
        'min' => 0.0,
        'max' => 8.0,
        'label' => 'Min Daily Hours',
        'help' => 'Minimum hours that can be logged per day',
      ],
      'redis_scan_batch_size' => [
        'type' => 'int',
        'category' => 'database',
        'default' => 100,
        'min' => 10,
        'max' => 1000,
        'label' => 'Redis Scan Batch Size',
        'help' => 'Number of keys per Redis SCAN operation',
      ],
      'redis_connect_timeout' => [
        'type' => 'float',
        'category' => 'database',
        'default' => 2.5,
        'min' => 1.0,
        'max' => 10.0,
        'label' => 'Redis Connect Timeout (s)',
        'help' => 'Redis connection timeout in seconds',
      ],
      'enable_debug_logging' => [
        'type' => 'bool',
        'category' => 'features',
        'default' => false,
        'label' => 'Enable Debug Logging',
        'help' => 'Enable verbose debug logging (may impact performance)',
      ],
      'enable_rate_limiting' => [
        'type' => 'bool',
        'category' => 'features',
        'default' => true,
        'label' => 'Enable Rate Limiting',
        'help' => 'Enable API rate limiting for security',
      ],
      'org_shared_encryption_enabled' => [
        'type' => 'bool',
        'category' => 'features',
        'default' => false,
        'label' => 'Enable Org Shared Encryption',
        'help' => 'Enable organization-shared encryption mode and key lifecycle flows',
      ],
      'org_shared_encryption_enforce_strict_envelope' => [
        'type' => 'bool',
        'category' => 'features',
        'default' => false,
        'label' => 'Enforce Strict Org Envelope Metadata',
        'help' => 'Require explicit org envelope metadata fields when encryption_mode is organization',
      ],
      'org_shared_encryption_enable_write' => [
        'type' => 'bool',
        'category' => 'features',
        'default' => false,
        'label' => 'Enable Org Shared Encryption Writes',
        'help' => 'Enable organization-mode work write paths (read can be rolled out earlier)',
      ],
      'account_recovery_enabled' => [
        'type' => 'bool',
        'category' => 'account_security',
        'default' => true,
        'label' => 'Enable Account Recovery',
        'help' => 'Enable emailed Recovery Key based account recovery flow',
      ],
      'account_recovery_auto_block_enabled' => [
        'type' => 'bool',
        'category' => 'account_security',
        'default' => true,
        'label' => 'Enable Account Recovery Auto Block',
        'help' => 'Automatically block obvious recovery replay abuse by hashed IP',
      ],
      'account_recovery_code_ttl_minutes' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 15,
        'min' => 5,
        'max' => 60,
        'label' => 'Account Recovery Code TTL (min)',
        'help' => 'Expiration time for recovery email codes',
      ],
      'account_recovery_resend_cooldown_seconds' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 60,
        'min' => 30,
        'max' => 300,
        'label' => 'Account Recovery Resend Cooldown (s)',
        'help' => 'Minimum delay between recovery code resend attempts',
      ],
      'account_recovery_max_resends_per_hour' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 5,
        'min' => 1,
        'max' => 20,
        'label' => 'Account Recovery Max Resends Per Hour',
        'help' => 'Maximum recovery code resend attempts allowed per hour',
      ],
      'account_recovery_max_verify_attempts' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 5,
        'min' => 1,
        'max' => 20,
        'label' => 'Account Recovery Max Verify Attempts',
        'help' => 'Maximum email or proof verification failures per transaction',
      ],
      'account_recovery_max_starts_per_day' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 5,
        'min' => 1,
        'max' => 25,
        'label' => 'Account Recovery Max Starts Per Day',
        'help' => 'Maximum number of recovery starts allowed per day',
      ],
      'account_recovery_txn_ttl_minutes' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 30,
        'min' => 10,
        'max' => 120,
        'label' => 'Account Recovery Transaction TTL (min)',
        'help' => 'Expiration time for recovery transactions',
      ],
      'account_recovery_proof_nonce_ttl_seconds' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 180,
        'min' => 30,
        'max' => 600,
        'label' => 'Account Recovery Proof Nonce TTL (s)',
        'help' => 'Expiration time for recovery proof nonce challenges',
      ],
      'account_recovery_bootstrap_ttl_seconds' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 300,
        'min' => 30,
        'max' => 900,
        'label' => 'Account Recovery Bootstrap TTL (s)',
        'help' => 'How long recovery bootstrap may be used to register a replacement passkey',
      ],
      'account_recovery_supersede_cooldown_seconds' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 60,
        'min' => 30,
        'max' => 300,
        'label' => 'Account Recovery Supersede Cooldown (s)',
        'help' => 'Minimum delay before a new recovery start may supersede an active transaction',
      ],
      'account_recovery_replay_block_threshold' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 50,
        'min' => 5,
        'max' => 500,
        'label' => 'Account Recovery Replay Block Threshold',
        'help' => 'Replay events from one hashed IP required before automatic blocking',
      ],
      'account_recovery_replay_block_window_seconds' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 900,
        'min' => 60,
        'max' => 3600,
        'label' => 'Account Recovery Replay Block Window (s)',
        'help' => 'Time window used to count replay attempts for automatic blocking',
      ],
      'account_recovery_replay_ip_block_ttl_minutes' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 60,
        'min' => 5,
        'max' => 1440,
        'label' => 'Account Recovery Replay IP Block TTL (min)',
        'help' => 'Duration of hashed IP blocks applied after replay abuse',
      ],
      'recovery_email_required_for_email_change' => [
        'type' => 'bool',
        'category' => 'account_security',
        'default' => true,
        'label' => 'Require Recovery Email For Email Change',
        'help' => 'Block primary email changes until a verified recovery email exists',
      ],
      'email_change_stepup_max_age_seconds' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 300,
        'min' => 60,
        'max' => 3600,
        'label' => 'Email Change Step-Up Max Age (s)',
        'help' => 'Maximum age of passkey step-up assertion before starting email change',
      ],
      'email_change_code_ttl_minutes' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 15,
        'min' => 5,
        'max' => 60,
        'label' => 'Email Change Code TTL (min)',
        'help' => 'Expiration time for old/new email verification codes',
      ],
      'email_change_resend_cooldown_seconds' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 60,
        'min' => 30,
        'max' => 300,
        'label' => 'Email Change Resend Cooldown (s)',
        'help' => 'Minimum seconds between resend-code actions for email change',
      ],
      'email_change_max_resends_per_hour' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 5,
        'min' => 1,
        'max' => 20,
        'label' => 'Email Change Max Resends Per Hour',
        'help' => 'Maximum code resend attempts per user per hour for email change',
      ],
      'email_change_max_verify_attempts' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 8,
        'min' => 3,
        'max' => 20,
        'label' => 'Email Change Max Verify Attempts',
        'help' => 'Maximum combined code verification attempts before email change lockout',
      ],
      'email_change_max_new_email_starts_per_day' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 10,
        'min' => 1,
        'max' => 50,
        'label' => 'Email Change Max Starts Per Day',
        'help' => 'Maximum number of new email-change transactions started per user per day',
      ],
      'recovery_email_code_ttl_minutes' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 15,
        'min' => 5,
        'max' => 60,
        'label' => 'Recovery Email Code TTL (min)',
        'help' => 'Expiration time for recovery-email verification codes',
      ],
      'recovery_email_resend_cooldown_seconds' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 60,
        'min' => 30,
        'max' => 300,
        'label' => 'Recovery Email Resend Cooldown (s)',
        'help' => 'Minimum seconds between recovery-email code resend actions',
      ],
      'recovery_email_max_resends_per_hour' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 5,
        'min' => 1,
        'max' => 20,
        'label' => 'Recovery Email Max Resends Per Hour',
        'help' => 'Maximum recovery-email code resend attempts per user per hour',
      ],
      'recovery_email_max_verify_attempts' => [
        'type' => 'int',
        'category' => 'account_security',
        'default' => 8,
        'min' => 3,
        'max' => 20,
        'label' => 'Recovery Email Max Verify Attempts',
        'help' => 'Maximum recovery-email code verification attempts before lockout',
      ],
      'text_size_smaller' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => '50%',
        'label' => 'Text Size: Smaller',
        'help' => 'Font size for "smaller" text setting (CSS percentage)',
      ],
      'text_size_base' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => '62.5%',
        'label' => 'Text Size: Base',
        'help' => 'Base font size (CSS percentage)',
      ],
      'text_size_larger' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => '125%',
        'label' => 'Text Size: Larger',
        'help' => 'Font size for "larger" text setting (CSS percentage)',
      ],
      'line_height_less' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => '1.5rem',
        'label' => 'Line Height: Less',
        'help' => 'Reduced line height (CSS rem value)',
      ],
      'line_height_more' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => '2.0rem',
        'label' => 'Line Height: More',
        'help' => 'Increased line height (CSS rem value)',
      ],
      'spacing_less' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => '1.00rem',
        'label' => 'Spacing: Less',
        'help' => 'Reduced spacing (CSS rem value)',
      ],
      'spacing_more' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => '1.20rem',
        'label' => 'Spacing: More',
        'help' => 'Increased spacing (CSS rem value)',
      ],
      'default_theme' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => 'paycal_blue',
        'options' => ['paycal_blue', 'paycal_black', 'paycal_red', 'paycal_green', 'paycal_white'],
        'label' => 'Default Theme',
        'help' => 'Default color theme for new users',
      ],
      'font_line_height' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => '1.5',
        'label' => 'Font Line Height',
        'help' => 'Base line-height value for typography (unitless or CSS length)',
      ],
      'font_size_xs' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => '0.50rem',
        'label' => 'Font Size XS',
        'help' => 'Font size token used by --font-xs',
      ],
      'font_size_sm' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => '0.90rem',
        'label' => 'Font Size SM',
        'help' => 'Font size token used by --font-sm',
      ],
      'font_size_md' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => '1.10rem',
        'label' => 'Font Size MD',
        'help' => 'Font size token used by --font-md',
      ],
      'font_size_lg' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => '1.30rem',
        'label' => 'Font Size LG',
        'help' => 'Font size token used by --font-lg',
      ],
      'font_size_xl' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => '1.70rem',
        'label' => 'Font Size XL',
        'help' => 'Font size token used by --font-xl',
      ],
      'font_weight_base' => [
        'type' => 'int',
        'category' => 'ui',
        'default' => 500,
        'min' => 100,
        'max' => 900,
        'label' => 'Font Weight Base',
        'help' => 'Base font weight token used by --font-weight',
      ],
      'font_size_adjustment_override_px' => [
        'type' => 'int',
        'category' => 'ui',
        'default' => 0,
        'min' => -5,
        'max' => 5,
        'label' => 'Font Size Adjustment Override (px)',
        'help' => 'Global pixel adjustment applied on top of user text slider values',
      ],
      'density_adjustment_override_px' => [
        'type' => 'int',
        'category' => 'ui',
        'default' => 0,
        'min' => -5,
        'max' => 5,
        'label' => 'Density Adjustment Override (px)',
        'help' => 'Global pixel adjustment applied on top of user density slider values',
      ],
      'font_family_sans' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => 'Roboto, "Open Sans", Lato, Nunito, Verdana, Helvetica, Arial, sans-serif',
        'label' => 'Font Family Sans',
        'help' => 'Base sans-serif stack used by --sans-serif',
      ],
      'font_family_serif' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => 'Merriweather, Garamond, "Times New Roman", serif',
        'label' => 'Font Family Serif',
        'help' => 'Base serif stack used by --serif',
      ],
      'font_family_monospace' => [
        'type' => 'string',
        'category' => 'ui',
        'default' => '"Courier New", Courier, monospace',
        'label' => 'Font Family Monospace',
        'help' => 'Base monospace stack used by --monospace',
      ],
    ];

    /** @var null|array<string, bool|float|int|string> Cached limit values */
    private static ?array $cache = null;

  /**
   * @return array<string, array<string, mixed>>
   */
  public static function getSchema(): array
  {
    return self::SCHEMA;
  }

  /**
   * @return array<string, array<string, array<string, mixed>>>
   */
  public static function getByCategory(): array
  {
    $grouped = [];
    foreach (self::SCHEMA as $key => $config) {
      $category = $config['category'];
      if (!isset($grouped[$category])) {
        $grouped[$category] = [];
      }
      $grouped[$category][$key] = $config;
    }

    return $grouped;
  }

  /**
   * @return array<string, string>
   */
  public static function getCategoryLabels(): array
  {
    return [
        'pagination' => 'Pagination & Display',
        'quotas' => 'Quotas & Resource Limits',
        'session' => 'Session & Authentication',
        'work' => 'Work Hours & Pay',
        'database' => 'Database & Performance',
        'features' => 'System Features',
        'account_security' => 'Account Security & Email Change',
        'ui' => 'User Interface Defaults',
    ];
  }

  /**
   * @return array<int, string>
   */
  public static function getKeys(): array
  {
    return array_keys(self::SCHEMA);
  }

  /**
   * Handles get operation.
   */
  public static function get(string $key): bool|float|int|string
  {
    if (!isset(self::SCHEMA[$key])) {
      trigger_error("Unknown system limit key: {$key}", E_USER_WARNING);

      return 0;
    }

    if (null === self::$cache) {
      self::loadLimits();
    }

    return self::$cache[$key] ?? self::normalizeCacheValue(self::SCHEMA[$key]['default'], '');
  }

  /**
   * @return array<string, bool|float|int|string>
   */
  public static function getAll(): array
  {
    if (null === self::$cache) {
      self::loadLimits();
    }

    return self::$cache ?? [];
  }

  /**
   * @return array<string, mixed>
   */
  public static function validate(string $key, mixed $value): array
  {
    if (!isset(self::SCHEMA[$key])) {
      return ['valid' => false, 'value' => 0, 'error' => 'Unknown limit key'];
    }

    $schema = self::SCHEMA[$key];
    $type = $schema['type'];

    switch ($type) {
      case 'int':
        if (!is_numeric($value)) {
          return ['valid' => false, 'value' => $schema['default'], 'error' => 'Must be a number'];
        }
        $intValue = (int) $value;
        $min = $schema['min'] ?? PHP_INT_MIN;
        $max = $schema['max'] ?? PHP_INT_MAX;

        if ($intValue < $min || $intValue > $max) {
          $clamped = max($min, min($max, $intValue));

          return ['valid' => true, 'value' => $clamped, 'error' => "Value clamped to range [{$min}, {$max}]"];
        }

        return ['valid' => true, 'value' => $intValue];

      case 'float':
        if (!is_numeric($value)) {
          return ['valid' => false, 'value' => $schema['default'], 'error' => 'Must be a number'];
        }
        $floatValue = (float) $value;
        $min = $schema['min'] ?? -PHP_FLOAT_MAX;
        $max = $schema['max'] ?? PHP_FLOAT_MAX;

        if ($floatValue < $min || $floatValue > $max) {
          $clamped = max($min, min($max, $floatValue));

          return ['valid' => true, 'value' => $clamped, 'error' => "Value clamped to range [{$min}, {$max}]"];
        }

        return ['valid' => true, 'value' => $floatValue];

      case 'bool':
        $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (null === $boolValue) {
          return ['valid' => false, 'value' => $schema['default'], 'error' => 'Must be true or false'];
        }

        return ['valid' => true, 'value' => $boolValue];

      case 'string':
        $strValue = is_scalar($value) ? (string) $value : '';
        $optionsRaw = $schema['options'] ?? null;
        $options = is_array($optionsRaw) ? $optionsRaw : null;
        if (is_array($options) && !in_array($strValue, $options, true)) {
          return ['valid' => false, 'value' => $schema['default'], 'error' => 'Invalid option'];
        }

        return ['valid' => true, 'value' => $strValue];

      case 'enum':
        $optionsRaw = $schema['options'] ?? [];
        $options = $optionsRaw;
        if (!in_array($value, $options, true)) {
          return ['valid' => false, 'value' => $schema['default'], 'error' => 'Invalid option'];
        }

        return ['valid' => true, 'value' => $value];

      default:
        return ['valid' => false, 'value' => $schema['default'], 'error' => 'Unknown type'];
    }
  }

  /**
   * @return array<string, mixed>
   */
  public static function set(string $key, mixed $value): array
  {
    $validation = self::validate($key, $value);

    if (!$validation['valid']) {
      return ['success' => false, 'value' => $validation['value'], 'error' => $validation['error']];
    }

    $serialized = json_encode($validation['value']);
    if (false === $serialized) {
      return ['success' => false, 'value' => $validation['value'], 'error' => 'Serialization failed'];
    }

    Database::hset(Keys::SYSTEM . ':limits', [$key => $serialized]);

    if (null !== self::$cache) {
      self::$cache[$key] = self::normalizeCacheValue($validation['value'], self::SCHEMA[$key]['default']);
    }

    $result = ['success' => true, 'value' => $validation['value']];
    if (isset($validation['error'])) {
      $result['warning'] = $validation['error'];
    }

    return $result;
  }

  /**
   * Handles remove operation.
   */
  public static function remove(string $key): bool
  {
    if (!isset(self::SCHEMA[$key])) {
      return false;
    }

    Database::hdel(Keys::SYSTEM . ':limits', $key);

    if (null !== self::$cache) {
      self::$cache[$key] = self::normalizeCacheValue(self::SCHEMA[$key]['default'], '');
    }

    return true;
  }

  /**
   * Handles resetAll operation.
   */
  public static function resetAll(): void
  {
    Database::del(Keys::SYSTEM . ':limits');
    self::$cache = null;
  }

  /**
   * Handles clearCache operation.
   */
  public static function clearCache(): void
  {
    self::$cache = null;
  }

  /**
   * @param mixed $value
   * @param mixed $default
   * @return bool|float|int|string
   */
  private static function normalizeCacheValue(mixed $value, mixed $default)
  {
    if (is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
      return $value;
    }

    if (is_bool($default) || is_int($default) || is_float($default) || is_string($default)) {
      return $default;
    }

    return '';
  }

  /**
   * Handles loadLimits operation.
   */
  private static function loadLimits(): void
  {
    $overrides = Database::hgetall(Keys::SYSTEM . ':limits');
    self::$cache = [];

    foreach (self::SCHEMA as $key => $schema) {
      if (isset($overrides[$key])) {
        $decoded = json_decode($overrides[$key], true);
        if (null !== $decoded) {
          $validation = self::validate($key, $decoded);
          self::$cache[$key] = self::normalizeCacheValue($validation['value'], $schema['default']);
        } else {
          self::$cache[$key] = self::normalizeCacheValue($schema['default'], '');
        }
      } else {
        self::$cache[$key] = self::normalizeCacheValue($schema['default'], '');
      }
    }
  }
}

