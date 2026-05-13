<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Audit;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Infrastructure\Audit\TheLedger;

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
 * Events are kept for RETENTION_DAYS (365 days / 1 year, SOC 2 minimum) via Redis TTL on the event
 * hash. Ledger block entries in TheLedger carry no TTL and are permanent by design.
 * The index set entry is left intact; orphaned IDs are skipped on read.
 *
 * Access policy: read operations (recent, proofForEvent, verifyImmutableLedger) require at least
 * AuthLevel::ADMIN as defined in SystemAuditPolicy. Callers are responsible for enforcing the policy
 * before invoking read methods. See SystemAuditPolicy::assertCanRead().
 */
final class SystemAuditRepository
{
  private const RETENTION_SECONDS = 365 * 24 * 3600; // SOC 2 minimum: 1 year

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

    Database::hsetex($eventKey, [
      'event_id'   => $eventId,
      'event_type' => $eventType,
      'actor_uuid' => $actorUUID,
      'details'    => json_encode($normalizedDetails, JSON_UNESCAPED_SLASHES) ?: '{}',
      'created_at' => $createdAt,
    ], self::RETENTION_SECONDS);

    Database::sadd(Keys::SYSTEM_AUDIT, $eventId);

    $proof = TheLedger::append(
      $eventId,
      $eventType,
      $actorUUID,
      $normalizedDetails,
      $createdAt
    );

    Database::hset($eventKey, [
      'ledger_sequence' => (string) ($proof['sequence'] ?? ''),
      'ledger_block_hash' => (string) ($proof['block_hash'] ?? ''),
      'ledger_anchor_status' => (string) ($proof['anchor_status'] ?? 'unknown'),
      'ledger_anchor_provider' => (string) ($proof['anchor_provider'] ?? 'none'),
      'ledger_anchor_reference' => (string) ($proof['anchor_reference'] ?? ''),
    ]);

      // Publish a lightweight summary to the admin live-feed pub/sub channel.
      // Fire-and-forget: publish failure must never block audit writes.
      try {
        Database::publish(
          Keys::systemAuditPubsubChannel(),
          (string) json_encode([
            'event_id'        => $eventId,
            'event_type'      => $eventType,
            'actor_uuid'      => $actorUUID,
            'ledger_sequence' => (string) ($proof['sequence'] ?? ''),
            'created_at'      => $createdAt,
          ], JSON_UNESCAPED_SLASHES)
        );
      } catch (\Throwable) {
        // Intentionally silent.
      }

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
        'ledger_sequence' => (string) ($event['ledger_sequence'] ?? ''),
        'ledger_block_hash' => (string) ($event['ledger_block_hash'] ?? ''),
        'ledger_anchor_status' => (string) ($event['ledger_anchor_status'] ?? 'unknown'),
        'ledger_anchor_provider' => (string) ($event['ledger_anchor_provider'] ?? 'none'),
        'ledger_anchor_reference' => (string) ($event['ledger_anchor_reference'] ?? ''),
      ];
    }

    usort($events, static function (array $a, array $b): int {
      return strcmp((string) $b['created_at'], (string) $a['created_at']);
    });

    return array_slice($events, 0, $limit);
  }

  /**
   * Record that an admin user read the audit log (meta-audit).
   * Call this from any controller action that exposes audit data to a user.
   *
   * @param string $readerUUID UUID of the admin who performed the read
   * @param string $operation  Freeform label, e.g. 'recent_events', 'proof_for_event', 'verify'
   */
  public static function recordReadAccess(string $readerUUID, string $operation): string
  {
    return self::append(
      'audit_log.read_access',
      $readerUUID,
      ['operation' => $operation]
    );
  }

  /**
   * Return immutable ledger verification status for system audit events.
   *
   * @return array<string, scalar>
   */
  public static function verifyImmutableLedger(): array
  {
    return TheLedger::verify();
  }

  /**
   * Resolve immutable proof for a single event.
   *
   * @return array<string, scalar>
   */
  public static function proofForEvent(string $eventId): array
  {
    return TheLedger::proofForEvent($eventId);
  }
}
