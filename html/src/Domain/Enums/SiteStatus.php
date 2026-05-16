<?php declare(strict_types=1);

namespace PayCal\Domain\Enums;

/**
 * SiteStatus.php
 *
 * Purpose: Site lifecycle status enum: active (accepting entries), inactive (paused,
 *          read-only), or archived (historical reference, read-only).
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
enum SiteStatus: string
{
  case ACTIVE = 'active';
  case INACTIVE = 'inactive';
  case ARCHIVED = 'archived';
}
