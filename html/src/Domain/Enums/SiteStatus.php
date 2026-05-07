<?php declare(strict_types=1);

namespace PayCal\Domain\Enums;

/**
 * SiteStatus.php
 *
 * Purpose: Define the SiteStatus enum for PayCal\Domain\Enums.
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
/**
 * Enum SiteStatus.
 *
 * Represents the operational state of a site:
 * - ACTIVE: Site is operational and accepting work entries
 * - INACTIVE: Site is paused/disabled (read-only)
 * - ARCHIVED: Site is archived (historical reference, read-only)
 */
enum SiteStatus: string
{
  case ACTIVE = 'active';
  case INACTIVE = 'inactive';
  case ARCHIVED = 'archived';
}
