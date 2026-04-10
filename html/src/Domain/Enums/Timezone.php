<?php declare(strict_types=1);

namespace PayCal\Domain\Enums;

/**
 * Timezone.php
 *
 * Purpose: Central timezone catalog and validation helper.
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
enum Timezone: string
{
  case UTC = 'UTC';

  /**
   * Handles isValid operation.
   */
  public static function isValid(string $timezone): bool
  {
    $value = trim($timezone);
    if ($value === '') {
      return false;
    }

    return in_array($value, timezone_identifiers_list(), true);
  }

  /**
   * @return array<int, string>
   */
  public static function toArray(): array
  {
    return timezone_identifiers_list();
  }
}

