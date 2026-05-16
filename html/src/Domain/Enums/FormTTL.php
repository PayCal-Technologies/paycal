<?php declare(strict_types=1);

namespace PayCal\Domain\Enums;

/**
 * FormTTL.php
 *
 * Purpose: Named time-to-live durations (in seconds) for form tokens and transient
 *          session-bound operations such as email challenges and rate-limit windows.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Enums
 * @package    PayCal\Domain\Enums
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

enum FormTTL: int
{
    case ONE_MIN = 60;
    case FIVE_MIN = 300;
    case TEN_MIN = 600;
    case FIFTEEN_MIN = 900;
    case THIRTY_MIN = 1800;
    case ONE_HOUR = 3600;
    case TWO_HOURS = 7200;
    case ONE_DAY = 86400;
    case THIRTY_DAYS = 2592000;
}
