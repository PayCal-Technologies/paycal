<?php declare(strict_types=1);

namespace PayCal\Domain\Business;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;

/**
 * Append-only trust and moderation audit events for businesses.
 */
final class BusinessAuditLedger
{
  public const EVENT_CREATED = 'business_created';
  public const EVENT_NAME_SUBMITTED = 'business_name_submitted';
  public const EVENT_NAME_AUTO_APPROVED = 'business_name_auto_approved';
  public const EVENT_NAME_NEEDS_REVIEW = 'business_name_needs_review';
  public const EVENT_NAME_REJECTED = 'business_name_rejected';
  public const EVENT_LISTING_ENABLED = 'business_listing_enabled';
  public const EVENT_LISTING_HIDDEN = 'business_listing_hidden';
  public const EVENT_SUSPENDED = 'business_suspended';
  public const EVENT_RESTORED = 'business_restored';
  public const EVENT_RENAMED = 'business_renamed';
  public const EVENT_PAID_STATUS_CHANGED = 'business_paid_status_changed';
  public const EVENT_TRUST_CHANGED = 'business_trust_changed';

  /**
   * @param array<string, scalar|null> $oldValue
   * @param array<string, scalar|null> $newValue
   * @param array<int, string> $reasonCodes
   */
  public static function record(
    string $businessId,
    string $eventType,
    string $actorUUID,
    string $actorType = 'user',
    array $oldValue = [],
    array $newValue = [],
    array $reasonCodes = [],
  ): void {
    if ($businessId === '' || $eventType === '') {
      return;
    }

    $eventId = 'BTE' . substr(hash('sha256', $businessId . '|' . $eventType . '|' . bin2hex(random_bytes(16))), 0, 20);
    $createdAt = date('c');

    Database::hset(Keys::BUSINESS_AUDIT_EVENT . ':' . $eventId, [
      'event_id' => $eventId,
      'business_id' => $businessId,
      'event_type' => $eventType,
      'actor_uuid' => $actorUUID,
      'actor_type' => $actorType,
      'old_value' => self::encodePayload($oldValue),
      'new_value' => self::encodePayload($newValue),
      'reason_codes' => implode(',', $reasonCodes),
      'created_at' => $createdAt,
      'details' => '{}',
    ]);

    Database::sadd(Keys::BUSINESS_AUDIT . ':' . $businessId, $eventId);
  }

  /** @param array<string, scalar|null> $payload */
  private static function encodePayload(array $payload): string
  {
    $normalized = [];
    foreach ($payload as $key => $value) {
      $normalized[(string) $key] = $value === null ? '' : (string) $value;
    }

    return json_encode($normalized, JSON_UNESCAPED_SLASHES) ?: '{}';
  }
}
