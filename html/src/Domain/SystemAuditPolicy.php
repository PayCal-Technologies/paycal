<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Enums\AuthLevel;

/**
 * SystemAuditPolicy
 *
 * Purpose: Define and enforce access-control rules for system audit log operations.
 *
 * This class is the canonical, machine-readable RBAC definition for SOC 2 compliance.
 * It answers the auditor question: "Who can read, write, and export audit logs?"
 *
 * Rule summary:
 *   - WRITE (append):  Internal system code only. No user-facing HTTP endpoint may
 *                      call SystemAuditRepository::append() directly. Enforced by
 *                      architecture: append() is invoked only within domain classes,
 *                      never exposed as a public route action.
 *   - READ (recent, proofForEvent, verifyImmutableLedger):
 *                      Requires AuthLevel::ADMIN or higher.
 *   - EXPORT (evidence bundles):
 *                      Requires AuthLevel::SUPERADMIN.
 *   - META-READ:       Every time a user reads audit data via a controller,
 *                      SystemAuditRepository::recordReadAccess() must be called so the
 *                      read itself is audited (read-of-audit-log event).
 *
 * PHP version 8.4
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
final class SystemAuditPolicy
{
  /** Minimum auth level required to read audit events. */
  public const REQUIRED_READ_LEVEL = AuthLevel::ADMIN;

  /** Minimum auth level required to export evidence bundles. */
  public const REQUIRED_EXPORT_LEVEL = AuthLevel::SUPERADMIN;

  /**
   * Assert that a user has the right to read the audit log.
   *
   * Usage (in any controller that renders audit data):
   *   SystemAuditPolicy::assertCanRead($user);
   *
   * @throws AuditAccessDeniedException if auth level is insufficient
   */
  public static function assertCanRead(User $user): void
  {
    if (!$user->auth_level->atLeast(self::REQUIRED_READ_LEVEL)) {
      throw new AuditAccessDeniedException(
        'Audit log read requires ' . self::REQUIRED_READ_LEVEL->value
        . '; caller has ' . $user->auth_level->value
      );
    }
  }

  /**
   * Assert that a user has the right to export evidence bundles.
   *
   * @throws AuditAccessDeniedException if auth level is insufficient
   */
  public static function assertCanExport(User $user): void
  {
    if (!$user->auth_level->atLeast(self::REQUIRED_EXPORT_LEVEL)) {
      throw new AuditAccessDeniedException(
        'Audit evidence export requires ' . self::REQUIRED_EXPORT_LEVEL->value
        . '; caller has ' . $user->auth_level->value
      );
    }
  }
}
