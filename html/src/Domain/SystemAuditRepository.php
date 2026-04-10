<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * SystemAuditRepository
 *
 * Write and retrieve system-level audit events that are not scoped to a single
 * organization (e.g. auth_level changes, account deletions, superadmin promotions).
 *
 * Key layout:
 *   system:audit:event:{id}  – hash of event fields
 *   system:audit:index       – set of event IDs (no ordering guarantee)
 *
 * Events are kept for RETENTION_DAYS (90 days) via Redis TTL on the event hash.
 * The index set entry is left intact; orphaned IDs are skipped on read.
 */
final class SystemAuditRepository
{
  private const RETENTION_SECONDS = 90 * 24 * 3600;

  /**
   * Record a system audit event.
   *
   * @param string               $eventType  Dot-notation event name, e.g. 'user.auth_level.changed'
   * @param string               $actorUUID  UUID of the user performing the action
   * @param array<string, scalar> $details   Freeform key/value context
   */
  public static function append(string $eventType, string $actorUUID, array $details = []): string
  {
    $eventId   = 'SAE' . substr(hash('sha256', $eventType . '|' . $actorUUID . '|' . bin2hex(random_bytes(16))), 0, 20);
    $createdAt = date('c');

    $normalizedDetails = [];
    foreach ($details as $key => $value) {
      $normalizedDetails[(string) $key] = (string) $value;
    }

    $eventKey = Keys::SYSTEM_AUDIT_EVENT . ':' . $eventId;

    Database::hset($eventKey, [
      'event_id'   => $eventId,
      'event_type' => $eventType,
      'actor_uuid' => $actorUUID,
      'details'    => json_encode($normalizedDetails, JSON_UNESCAPED_SLASHES) ?: '{}',
      'created_at' => $createdAt,
    ]);

    Database::expire($eventKey, self::RETENTION_SECONDS);
    Database::sadd(Keys::SYSTEM_AUDIT, $eventId);

    return $eventId;
  }

  /**
   * Retrieve recent system audit events, newest first.
   * Skips event IDs whose hashes have expired.
   *
   * @param  int $limit Maximum events to return (default 200)
   * @return array<int, array<string, string>>
   */
  public static function recent(int $limit = 200): array
  {
    $eventIds = Database::smembers(Keys::SYSTEM_AUDIT);
    $events   = [];

    foreach ($eventIds as $eventId) {
      $event = Database::hgetall(Keys::SYSTEM_AUDIT_EVENT . ':' . $eventId);
      if ([] === $event) {
        continue;
      }

      $events[] = [
        'event_id'   => (string) ($event['event_id']   ?? $eventId),
        'event_type' => (string) ($event['event_type'] ?? ''),
        'actor_uuid' => (string) ($event['actor_uuid'] ?? ''),
        'details'    => (string) ($event['details']    ?? '{}'),
        'created_at' => (string) ($event['created_at'] ?? ''),
      ];
    }

    usort($events, static function (array $a, array $b): int {
      return strcmp((string) $b['created_at'], (string) $a['created_at']);
    });

    return array_slice($events, 0, $limit);
  }
}
