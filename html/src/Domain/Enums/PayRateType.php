<?php declare(strict_types=1);

namespace PayCal\Domain\Enums;

/**
 * PayRateType.php
 *
 * Purpose: Define the PayRateType enum for PayCal\Domain\Enums.
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

enum PayRateType: string
{
  case HOURLY   = 'hourly';
  case SALARY   = 'salary';
  case DAY_RATE = 'day_rate';

  /**
   * Handles label operation.
   */
  public function label(): string
  {
    return match ($this) {
      self::HOURLY   => 'Hourly',
      self::SALARY   => 'Salary',
      self::DAY_RATE => 'Day rate',
    };
  }
}

