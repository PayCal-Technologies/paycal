<?php declare(strict_types=1);

namespace PayCal\Domain\Enums;

/**
 * PayFrequency.php
 *
 * Purpose: Pay-period frequency enum for the four supported scheduling cycles:
 *          weekly, biweekly, semi-monthly, and monthly.
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

enum PayFrequency: string
{
  case WEEKLY = 'weekly';
  case BIWEEKLY = 'biweekly';
  case SEMIMONTHLY = 'semimonthly';
  case MONTHLY = 'monthly';
}
