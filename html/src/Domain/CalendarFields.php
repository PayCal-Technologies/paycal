<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * CalendarFields.php
 *
 * Purpose: Enumerate canonical calendar field keys used by calendar payloads
 * and work-entry representations.
 *
 * Developer notes:
 * - Enum values are payload-contract keys and should remain stable across API
 *   and rendering flows.
 * - Keep this file focused on field identity, not validation logic.
 *
 * Architectural role:
 * - Reusable domain enum for calendar field identifiers shared across core
 *   calendar and work-entry flows.
 * - Encapsulates field identity outside the HTTP layer.
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
 * CalendarFields enum.
 */
enum CalendarFields: string
{
  // Extra fields for API
  case CAL_WORK_SAVE_DEFAULT = 'cal_work_save_as_default';
  case CSRF_TOKEN = 'csrf_token';
  case DAY_ID = 'd';

  // Calendar-specific fields
  case CAL_WORK_DATE = 'cal_work_date';
  case CAL_WORK_SITE_SELECT = 'cal_work_site_select';

  // Work entry short fields
  case H = 'h';
  case L = 'l';
  case N = 'n';
  case O = 'o';
  case R = 'r';
  case S = 's';
  case T = 't';
  case W = 'w';
}
