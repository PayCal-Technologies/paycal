<?php declare(strict_types=1);

namespace PayCal\Extensions\Basic\BusinessSignals;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;

/**
 * Baseline business signal hooks.
 *
 * This listener projects selected audit events into a lightweight owner inbox
 * index stored in Redis for downstream triage workflows.
 */
final class Hooks
{
  /**
   * @param array<string, mixed> $payload
   * @return null
   */
  public static function onBusinessAuditEvent(array $payload): null
  {
    $eventRaw = $payload['event'] ?? null;
    $event = is_array($eventRaw) ? $eventRaw : [];

    $orgId = is_scalar($event['business_id'] ?? null) ? (string) $event['business_id'] : '';
    $eventType = is_scalar($event['event_type'] ?? null) ? (string) $event['event_type'] : '';

    if ($orgId === '' || $eventType !== 'access.requested') {
      return null;
    }

    $business = Database::hgetall(Keys::BUSINESS . ':' . $orgId);
    $ownerUUID = is_scalar($business['owner_uuid'] ?? null) ? (string) $business['owner_uuid'] : '';
    if ($ownerUUID === '') {
      return null;
    }

    $signalId = 'OSS' . substr(hash('sha256', $orgId . '|' . $eventType . '|' . bin2hex(random_bytes(16))), 0, 20);
    $createdAt = is_scalar($event['created_at'] ?? null) ? (string) $event['created_at'] : date('c');

    Database::hset('extension:business:owner:signal:' . $signalId, [
      'signal_id' => $signalId,
      'owner_uuid' => $ownerUUID,
      'business_id' => $orgId,
      'event_type' => $eventType,
      'audit_event_id' => is_scalar($event['event_id'] ?? null) ? (string) $event['event_id'] : '',
      'details' => is_scalar($event['details'] ?? null) ? (string) $event['details'] : '{}',
      'created_at' => $createdAt,
      'status' => 'new',
      'source' => 'basic',
    ]);

    Database::sadd('extension:business:owner:signal:index:' . $ownerUUID, $signalId);
    Database::set('extension:business:owner:signal:latest:' . $ownerUUID, $signalId, 30 * 24 * 3600);

    return null;
  }
}
