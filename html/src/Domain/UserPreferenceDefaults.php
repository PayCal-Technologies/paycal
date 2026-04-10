<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * UserPreferenceDefaults.php
 *
 * Purpose: Define the UserPreferenceDefaults class for PayCal\Domain.
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
   * Default spacing (density) preference.
   *
  * Range: -5 to +5 px adjustment (stored as a string integer)
   * Controls UI element spacing for accessibility.
   */
  public const DEFAULT_DENSITY = '0';

  /**
   * Default dyslexia-friendly typography mode.
   *
   * Options: 'off', 'on'
   */
  public const DEFAULT_DYSLEXIA_TYPOGRAPHY = 'on';

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
   * Options: 'short', 'long'
   * Controls how day names are displayed (e.g., "Mon" vs "Monday").
   */
  public const DEFAULT_CALENDAR_DAY_NAME_FORMAT = 'short';

  /**
   * Default calendar date label position.
   *
   * Options: 'left', 'right', 'above', 'below'
   * Controls where date labels appear relative to cells.
   */
  public const DEFAULT_CALENDAR_DATE_LABEL_POSITION = 'right';

  /**
   * Default calendar work entry position.
   *
   * Options: 'top', 'middle', 'bottom'
   * Controls where work entries display within calendar cells.
   */
  public const DEFAULT_CALENDAR_WORK_ENTRY_POSITION = 'middle';

  /**
    * Default voice preference.
    *
    * Options: 'system_default', 'system_female', 'system_male', and provider-specific voices.
    */
    public const DEFAULT_VOICE = 'system_default';

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
