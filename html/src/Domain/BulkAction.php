<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * BulkAction.php
 *
 * Purpose: Enumerate supported bulk actions for site and list-management flows
 * that need stable action identifiers.
 *
 * Developer notes:
 * - Enum case values are contract-sensitive because UI actions and backend
 *   handling may persist or compare them directly.
 * - Keep action semantics explicit and additive when expanding the set.
 *
 * Architectural role:
 * - Reusable domain enum for bulk-action identifiers consumed by controllers,
 *   services, and UI workflows.
 * - Encapsulates action identity outside the HTTP layer.
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
