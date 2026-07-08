<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * UserPreferenceDefaults.php
 *
 * Purpose: Compile-time constants for user preference defaults (theme, text size,
 *          locale, etc.) applied on account creation.
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

final class UserPreferenceDefaults
{
  /**
   * Default theme preference.
   *
   * Options: 'dark', 'light', or theme identifiers.
   */
  public const DEFAULT_THEME = 'win10';

  /**
   * Default text sizing preference.
   *
    * Range: -5 to +5 px adjustment (stored as a string integer)
   * Controls font size scaling for accessibility.
   */
    public const DEFAULT_TEXT = '0';

  /**
   * Default spacing preference.
   *
   * Range: -5 to +5 rem adjustment (stored as a string integer)
   * Controls UI element spacing for accessibility.
   */
  public const DEFAULT_SPACING = '0';

  /**
   * Default z-axis surface depth.
   *
   * Options: 'flat', 'low', 'standard', 'high'
   * Controls shared button, input, panel, dialog, and chrome elevation tokens.
   */
  public const DEFAULT_DEPTH = 'standard';

  /**
   * Default dyslexia-friendly typography mode.
   *
   * Options: 'off', 'on'
   */
  public const DEFAULT_DYSLEXIA_TYPOGRAPHY = 'on';

  /**
   * Default help popup auto-dismiss timeout in seconds.
   *
   * Options: '0' (never), '3', '5', '8', '12', '15', '20', '30'
   * Controls how long hover help popups stay visible before hiding.
   */
  public const DEFAULT_HELP_POPUP_TIMEOUT_SECONDS = '8';

  /** Toast anchor: top-left, top-center, top-right, bottom-left, bottom-center, bottom-right. */
  public const DEFAULT_TOAST_POSITION = 'bottom-center';

  /** Toast width preset: tiny, narrow, normal, large, larger, full-width. */
  public const DEFAULT_TOAST_WIDTH_PRESET = 'normal';

  /** Toast font size slider (-5…+5). */
  public const DEFAULT_TOAST_FONT_SIZE = '0';

  /** Sidebar proximity hover: on, off. */
  public const DEFAULT_NAV_PROXIMITY = 'on';

  /** Sidebar overlay mode: overlay, push. */
  public const DEFAULT_NAV_OVERLAY = 'push';

  /** Sidebar proximity trigger distance in px (0–600). */
  public const DEFAULT_NAV_PROXIMITY_PX = '200';

  /** Sidebar proximity trigger delay in ms (200–3000). */
  public const DEFAULT_NAV_PROXIMITY_DELAY_MS = '400';

  /**
   * Default primary navigation position.
   *
    * Options: 'left', 'right'.
   */
  public const DEFAULT_NAV_POSITION_PRIMARY = 'left';

  /**
   * Default primary navigation state for side-nav layouts.
   *
   * Options: 'collapsed', 'pinned'.
   */
  public const DEFAULT_NAV_STATE_PRIMARY = 'collapsed';

  /**
   * Default overlay sidebar auto-collapse timeout in seconds.
   *
   * Options: '0' (never), '1'–'30'
   * Controls how long the expanded overlay sidebar stays open without pointer activity.
   */
  public const DEFAULT_OVERLAY_SIDEBAR_TIMEOUT_SECONDS = '5';

  /**
   * Default calendar auto-focus preference.
   *
   * Options: 'current', 'first', 'none'
   * Controls which date is focused when calendar loads.
   */
  public const DEFAULT_CALENDAR_AUTOFOCUS = 'today';

  /**
   * Default calendar audio labels setting.
   *
    * Options: 'number', 'short', 'long'
   * Controls verbosity of calendar audio announcements.
   */
  public const DEFAULT_CALENDAR_AUDIO_LABELS = 'number';

  /**
   * Default calendar day name format.
   *
   * Options: 'narrow', 'short', 'long'
   * Controls weekday label width (e.g., "M", "Mon", "Monday").
   */
  public const DEFAULT_CALENDAR_DAY_NAME_FORMAT = 'short';

  /**
   * Default calendar day name position.
   *
   * Options: 'left', 'middle', 'right'
   * Controls weekday heading alignment.
   */
  public const DEFAULT_CALENDAR_DAY_NAME_POSITION = 'middle';

  /**
   * Default calendar date label position.
   *
   * Options: 'left', 'middle', 'right'
   * Controls where date labels appear within calendar cells.
   */
  public const DEFAULT_CALENDAR_DATE_LABEL_POSITION = 'right';

  /**
   * Default calendar work entry position.
   *
   * Options: 'left', 'middle', 'right'
   * Controls where work entries display within calendar cells.
   */
  public const DEFAULT_CALENDAR_WORK_ENTRY_POSITION = 'middle';

  /** Calendar grid week start: 0 = Sunday, 1 = Monday. */
  public const DEFAULT_CALENDAR_WEEK_START = '0';

  /** Default calendar view: month, week, or pay_period. */
  public const DEFAULT_CALENDAR_DEFAULT_VIEW = 'month';

  public const DEFAULT_ACCENT_PRESET = 'blue';
  public const ACCENT_PRESETS = [
    'red' => ['label' => 'Red', 'hex' => '#EF4444'],
    'coral' => ['label' => 'Coral', 'hex' => '#F87171'],
    'rose' => ['label' => 'Rose', 'hex' => '#F43F5E'],
    'salmon' => ['label' => 'Salmon', 'hex' => '#FB7185'],
    'orange' => ['label' => 'Orange', 'hex' => '#F97316'],
    'tangerine' => ['label' => 'Tangerine', 'hex' => '#FB923C'],
    'peach' => ['label' => 'Peach', 'hex' => '#FDBA74'],
    'amber' => ['label' => 'Amber', 'hex' => '#F59E0B'],
    'gold' => ['label' => 'Gold', 'hex' => '#FBBF24'],
    'yellow' => ['label' => 'Yellow', 'hex' => '#EAB308'],
    'honey' => ['label' => 'Honey', 'hex' => '#FACC15'],
    'pear' => ['label' => 'Pear', 'hex' => '#BEF264'],
    'chartreuse' => ['label' => 'Chartreuse', 'hex' => '#A3E635'],
    'lime' => ['label' => 'Lime', 'hex' => '#84CC16'],
    'moss' => ['label' => 'Moss', 'hex' => '#65A30D'],
    'green' => ['label' => 'Green', 'hex' => '#22C55E'],
    'spring' => ['label' => 'Spring', 'hex' => '#4ADE80'],
    'fern' => ['label' => 'Fern', 'hex' => '#16A34A'],
    'jade' => ['label' => 'Jade', 'hex' => '#34D399'],
    'emerald' => ['label' => 'Emerald', 'hex' => '#10B981'],
    'mint' => ['label' => 'Mint', 'hex' => '#5EEAD4'],
    'seafoam' => ['label' => 'Seafoam', 'hex' => '#2DD4BF'],
    'lagoon' => ['label' => 'Lagoon', 'hex' => '#0D9488'],
    'teal' => ['label' => 'Teal', 'hex' => '#14B8A6'],
    'aqua' => ['label' => 'Aqua', 'hex' => '#22D3EE'],
    'cyan' => ['label' => 'Cyan', 'hex' => '#06B6D4'],
    'azure' => ['label' => 'Azure', 'hex' => '#38BDF8'],
    'glacier' => ['label' => 'Glacier', 'hex' => '#7DD3FC'],
    'sky' => ['label' => 'Sky', 'hex' => '#0EA5E9'],
    'denim' => ['label' => 'Denim', 'hex' => '#0284C7'],
    'cerulean' => ['label' => 'Cerulean', 'hex' => '#60A5FA'],
    'blue' => ['label' => 'Blue', 'hex' => '#3B82F6'],
    'cobalt' => ['label' => 'Cobalt', 'hex' => '#2563EB'],
    'royal' => ['label' => 'Royal', 'hex' => '#1D4ED8'],
    'slate' => ['label' => 'Slate', 'hex' => '#64748B'],
    'periwinkle' => ['label' => 'Periwinkle', 'hex' => '#818CF8'],
    'indigo' => ['label' => 'Indigo', 'hex' => '#6366F1'],
    'iris' => ['label' => 'Iris', 'hex' => '#4F46E5'],
    'lavender' => ['label' => 'Lavender', 'hex' => '#A78BFA'],
    'violet' => ['label' => 'Violet', 'hex' => '#8B5CF6'],
    'amethyst' => ['label' => 'Amethyst', 'hex' => '#7C3AED'],
    'orchid' => ['label' => 'Orchid', 'hex' => '#C084FC'],
    'purple' => ['label' => 'Purple', 'hex' => '#A855F7'],
    'plum' => ['label' => 'Plum', 'hex' => '#9333EA'],
    'fuchsia' => ['label' => 'Fuchsia', 'hex' => '#D946EF'],
    'magenta' => ['label' => 'Magenta', 'hex' => '#E879F9'],
    'bubblegum' => ['label' => 'Bubblegum', 'hex' => '#F472B6'],
    'pink' => ['label' => 'Pink', 'hex' => '#EC4899'],
    'hotpink' => ['label' => 'Hot Pink', 'hex' => '#DB2777'],
  ];

  /**
   * @return array<string, array{label: string, hex: string}>
   */
  public static function accentPresets(): array
  {
    return self::ACCENT_PRESETS;
  }

  public const DEFAULT_HIGH_CONTRAST_ENABLED = '0';
  public const DEFAULT_REDUCED_MOTION_ENABLED = 'system';
  public const DEFAULT_SR_VERBOSITY = 'standard';
  public const DEFAULT_KEYBOARD_SHORTCUTS_HINT = 'first_visit';
  public const DEFAULT_REQUIRE_REAUTH_EXPORT = '0';
  public const DEFAULT_REQUIRE_REAUTH_IMPORT = '0';
  public const DEFAULT_EXPORT_ENCRYPT_PREFERENCE = '0';
  public const DEFAULT_DEBUG_TTL_MINUTES = '15';

  /**
   * Resolve stored week-start preference to Calendar grid index (0–6, Sunday-based).
   */
  public static function calendarWeekStartDay(?User $user = null): int
  {
    $raw = $user === null
      ? self::DEFAULT_CALENDAR_WEEK_START
      : trim($user->calendar_week_start);

    return $raw === '1' ? 1 : 0;
  }

  /**
    * Default voice preference.
    *
    * Options: 'system_default', 'system_female', 'system_male', and provider-specific voices.
    */
    public const DEFAULT_VOICE = 'system_default';

  /** Default TTS output volume (0.0–1.0). */
  public const DEFAULT_VOICE_VOLUME = '1';

    /**
   * Default audio feedback preference.
   *
   * Options: 'none', 'subtle', 'prominent'
   * Controls whether and how the application provides audio feedback.
   */
  public const DEFAULT_AUDIO_FEEDBACK = 'none';

  /**
   * Default debug output policy: opt-in and disabled by default.
   */
  public const DEFAULT_DEBUG_CONSOLE_ENABLED = '0';
  public const DEFAULT_DEBUG_FINE_GRAINED_ENABLED = '0';
  public const DEFAULT_DEBUG_NETWORK_ENABLED = '0';

  /**
   * Default session timeout preference.
   *
   * Options: 'forever', '30m', '1h', '2h', etc.
   * Controls how long users stay logged in without activity.
   */
  public const DEFAULT_SESSION_TIMEOUT = 'forever';

  /**
   * Default editing grace period (days after pay period ends).
   *
   * Options: '0', '1', '2', '3'
   * Controls how many days users can edit work entries after the pay period ends.
   * '0' = lock immediately at period end
   * '1' = 1 day grace period (default)
   * '2' = 2 days grace period
   * '3' = 3 days grace period (maximum)
   */
  public const DEFAULT_EDITING_GRACE_DAYS = '1';
}
