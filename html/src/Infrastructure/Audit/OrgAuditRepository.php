<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Audit;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;

/**
 * OrgAuditRepository.php
 *
 * Purpose: Read-only access layer for organisation-scoped audit events stored
 * in Redis by OrganizationDiscoveryService::appendAuditEvent(). Provides
 * listing, count, and event-type query methods without requiring the full
 * OrganizationDiscoveryService to be instantiated.
 *
 * Developer notes:
 * - The write path lives exclusively in OrganizationDiscoveryService to keep
 *   audit writes consistent with business-rule enforcement and fanout hooks.
 * - This repository is intentionally read-only. Do not add write methods here.
 * - Callers are responsible for authorisation before calling. This class never
 *   enforces role or ownership — that must happen in the calling controller
 *   or service layer before data is returned to the user.
 *
 * Architectural role:
 * - Infrastructure-layer repository consumed by the org audit controller
 *   endpoints and the SOC2 dashboard to surface evidence of coordinator and
 *   owner actions without coupling to OrganizationDiscoveryService.
 *
 * @category   Infrastructure
 * @package    PayCal\Infrastructure\Audit
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.054.000
 */

/**
 * OrgAuditRepository
 *
 * Read access to organisation-scoped audit events. Events are written by
 * OrganizationDiscoveryService and indexed under
 * Keys::ORGANIZATION_AUDIT:{orgId} (Redis SET of event IDs). Payloads live at
 * Keys::ORGANIZATION_AUDIT_EVENT:{eventId}.
 */
final class OrgAuditRepository
{
  /**
   * Retrieve recent audit events for an organisation, newest first.
   *
   * @param  string $orgId Organisation identifier.
   * @param  int    $limit Maximum events to return.
   * @return array<int, array<string, string>>
   */
  public static function recent(string $orgId, int $limit = 50): array
  {
    $eventIds = Database::smembers(Keys::ORGANIZATION_AUDIT . ':' . $orgId);
    $events   = [];

    foreach ($eventIds as $eventId) {
      $event = Database::hgetall(Keys::ORGANIZATION_AUDIT_EVENT . ':' . (string) $eventId);
      if ([] === $event) {
        continue;
      }

      if ((string) ($event['organization_id'] ?? '') !== $orgId) {
        continue;
      }

      $events[] = [
        'event_id'        => (string) ($event['event_id']        ?? $eventId),
        'event_type'      => (string) ($event['event_type']      ?? ''),
        'actor_uuid'      => (string) ($event['actor_uuid']      ?? ''),
        'details'         => (string) ($event['details']         ?? '{}'),
        'created_at'      => (string) ($event['created_at']      ?? ''),
        'organization_id' => (string) ($event['organization_id'] ?? $orgId),
      ];
    }

    usort($events, static function (array $a, array $b): int {
      return strcmp((string) $b['created_at'], (string) $a['created_at']);
    });

    return array_slice($events, 0, $limit);
  }

  /**
   * Return the total number of audit events recorded for an organisation.
   *
   * @param  string $orgId Organisation identifier.
   * @return int
   */
  public static function countForOrg(string $orgId): int
  {
    return (int) Database::scard(Keys::ORGANIZATION_AUDIT . ':' . $orgId);
  }

  /**
   * Retrieve recent events matching a specific event_type, newest first.
   *
   * @param  string $orgId     Organisation identifier.
   * @param  string $eventType Exact event type string to filter on.
   * @param  int    $limit     Maximum events to return.
   * @return array<int, array<string, string>>
   */
  public static function forEventType(string $orgId, string $eventType, int $limit = 20): array
  {
    $all = self::recent($orgId, 500);
    $filtered = array_values(array_filter(
      $all,
      static fn (array $e): bool => ($e['event_type'] ?? '') === $eventType
    ));

    return array_slice($filtered, 0, $limit);
  }
}
