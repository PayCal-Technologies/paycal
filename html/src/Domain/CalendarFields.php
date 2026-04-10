<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * CalendarFields.php
 *
 * Purpose: Define the CalendarFields enum for PayCal\Domain.
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
 * CalendarFields
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
