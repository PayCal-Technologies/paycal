<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * Redis-backed versioned org snapshot: business:snapshot:{businessId}.
 */
final class BusinessSnapshotCache
{
  public const TTL_SECONDS = BusinessWorkspaceCache::TTL_SECONDS;

  /**
   * Get.
   */
  public static function get(string $businessId): ?BusinessSnapshot
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return null;
    }

    $raw = Database::hgetall(Keys::businessSnapshot($businessId));
    if ($raw === []) {
      return null;
    }

    $payloadRaw = $raw['payload'] ?? '';
    if ($payloadRaw === '') {
      return null;
    }

    $decoded = json_decode($payloadRaw, true);
    if (!is_array($decoded)) {
      return null;
    }

    /** @var array<string, mixed> $snapshotData */
    $snapshotData = $decoded;

    return BusinessSnapshot::fromArray($snapshotData);
  }

  /**
   * Put.
   */
  public static function put(BusinessSnapshot $snapshot): void
  {
    $businessId = trim($snapshot->business_id);
    if ($businessId === '') {
      return;
    }

    $encoded = json_encode($snapshot->toArray());
    if (!is_string($encoded)) {
      return;
    }

    Database::hset(Keys::businessSnapshot($businessId), [
      'snapshot_version' => $snapshot->snapshot_version,
      'payload' => $encoded,
      'generated_at' => $snapshot->generated_at !== '' ? $snapshot->generated_at : date('c'),
    ]);
    Database::expire(Keys::businessSnapshot($businessId), self::TTL_SECONDS);
  }

  /**
   * Invalidate.
   */
  public static function invalidate(string $businessId): void
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return;
    }

    Database::unlink(Keys::businessSnapshot($businessId));
  }

  /**
   * @param callable(): BusinessSnapshot $builder
   */
  public static function getOrBuild(string $businessId, callable $builder): BusinessSnapshot
  {
    $cached = self::get($businessId);
    if ($cached instanceof BusinessSnapshot) {
      return $cached;
    }

    $snapshot = $builder();
    self::put($snapshot);

    return $snapshot;
  }
}
