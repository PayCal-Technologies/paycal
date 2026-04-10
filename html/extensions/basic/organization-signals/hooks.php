<?php declare(strict_types=1);

namespace PayCal\Extensions\Basic\OrganizationSignals;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;

/**
 * Baseline organization signal hooks.
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
  public static function onOrganizationAuditEvent(array $payload): null
  {
    $eventRaw = $payload['event'] ?? null;
    $event = is_array($eventRaw) ? $eventRaw : [];

    $orgId = is_scalar($event['organization_id'] ?? null) ? (string) $event['organization_id'] : '';
    $eventType = is_scalar($event['event_type'] ?? null) ? (string) $event['event_type'] : '';

    if ($orgId === '' || $eventType !== 'access.requested') {
      return null;
    }

    $organization = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    $ownerUUID = is_scalar($organization['owner_uuid'] ?? null) ? (string) $organization['owner_uuid'] : '';
    if ($ownerUUID === '') {
      return null;
    }

    $signalId = 'OSS' . substr(hash('sha256', $orgId . '|' . $eventType . '|' . bin2hex(random_bytes(16))), 0, 20);
    $createdAt = is_scalar($event['created_at'] ?? null) ? (string) $event['created_at'] : date('c');

    Database::hset('extension:organization:owner:signal:' . $signalId, [
      'signal_id' => $signalId,
      'owner_uuid' => $ownerUUID,
      'organization_id' => $orgId,
      'event_type' => $eventType,
      'audit_event_id' => is_scalar($event['event_id'] ?? null) ? (string) $event['event_id'] : '',
      'details' => is_scalar($event['details'] ?? null) ? (string) $event['details'] : '{}',
      'created_at' => $createdAt,
      'status' => 'new',
      'source' => 'basic',
    ]);

    Database::sadd('extension:organization:owner:signal:index:' . $ownerUUID, $signalId);
    Database::set('extension:organization:owner:signal:latest:' . $ownerUUID, $signalId, 30 * 24 * 3600);

    return null;
  }
}
