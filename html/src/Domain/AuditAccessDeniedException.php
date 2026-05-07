<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * AuditAccessDeniedException.php
 *
 * Purpose: Thrown when a caller attempts to read or export the system audit log
 * without the required authorization level as defined in SystemAuditPolicy.
 *
 * Developer notes:
 * - Keep this exception narrowly scoped to audit authorization failures so
 *   callers can distinguish them from storage or transport errors.
 *
 * Architectural role:
 * - Domain exception type used by audit-policy and audit-repository flows to
 *   signal authorization denial.
 * - Encapsulates audit access-denial semantics outside the HTTP layer.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */
class AuditAccessDeniedException extends \RuntimeException {}
