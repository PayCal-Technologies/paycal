<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * BulkAction.php
 *
 * Purpose: Define the BulkAction enum for PayCal\Domain.
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
 * Enum BulkAction.
 *
 * Represents available bulk actions for site management:
 * - DELETE: Archive selected sites (soft delete)
 * - ACTIVE: Set selected sites to active status
 * - INACTIVE: Set selected sites to inactive status
 */
enum BulkAction: string
{
  case DELETE = 'DELETE';
  case ACTIVE = 'ACTIVE';
  case INACTIVE = 'INACTIVE';
}
