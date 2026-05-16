<?php declare(strict_types=1);

namespace PayCal\Domain\Enums;

/**
 * EmploymentType.php
 *
 * Purpose: Employment classification enum representing the working arrangement type
 *          (full-time, part-time, contractor, or casual). Used in work-entry validation.
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

enum EmploymentType: string
{
  case FULL_TIME  = 'full_time';
  case PART_TIME  = 'part_time';
  case CONTRACTOR = 'contractor';
  case CASUAL     = 'casual';

  /**
   * Handles label operation.
   */
  public function label(): string
  {
    return match ($this) {
      self::FULL_TIME  => 'Full-time',
      self::PART_TIME  => 'Part-time',
      self::CONTRACTOR => 'Contractor',
      self::CASUAL     => 'Casual',
    };
  }
}

