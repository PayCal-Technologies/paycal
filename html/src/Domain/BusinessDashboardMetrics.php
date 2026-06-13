<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;
use PayCal\Observability\Lens;

/**
 * Fast O(1) dashboard metrics for a business workspace.
 *
 * Reads SCARD/HGET/INCR-backed counters — no earnings aggregation or historical
 * work-entry scans. Pending invite/request counters are maintained on IAM writes
 * and lazily rebuilt once from org SET status fields when missing.
 *
 * Work-entry counters increment on first save per member/site/day; they do not
 * backfill pre-existing entries (shows 0 until new activity after deploy).
 */
final class BusinessDashboardMetrics
{
  private const WORK_COUNTER_TTL_SECONDS = 14 * 24 * 3600;

  /**
   * @return array{
   *   members: int,
   *   sites: int,
   *   pending_invites: int|null,
   *   pending_requests: int|null,
   *   work_entries_today: int,
   *   work_entries_week: int,
   *   last_activity_at: string,
   *   created_at: string
   * }
   */
  public static function forBusiness(string $businessId, bool $canViewAccessMetrics = true): array
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return self::emptyMetrics();
    }

    Lens::timeStart('Business Dashboard: metrics');

    $timezone = self::resolveBusinessTimezone($businessId);
    $today = (new \DateTimeImmutable('now', $timezone))->format('Y-m-d');

    $businessHash = Database::hgetall(Keys::BUSINESS . ':' . $businessId);

    $metrics = [
      'members' => self::setSize(Keys::BUSINESS_MEMBERS . ':' . $businessId),
      'sites' => self::setSize(Keys::BUSINESS_SITE . ':' . $businessId),
      'pending_invites' => $canViewAccessMetrics ? self::pendingInviteCount($businessId) : null,
      'pending_requests' => $canViewAccessMetrics ? self::pendingRequestCount($businessId) : null,
      'work_entries_today' => self::workEntryCountForDate($businessId, $today),
      'work_entries_week' => self::workEntryCountForWeek($businessId, $today, $timezone),
      'last_activity_at' => self::resolveLastActivityAt($businessHash, $businessId),
      'created_at' => trim((string) ($businessHash['created_at'] ?? '')),
    ];

    Lens::timeEnd('Business Dashboard: metrics');

    return $metrics;
  }

  public static function recordPendingInviteCreated(string $businessId): void
  {
    self::adjustCachedCounter(Keys::businessMetricsPendingInvites($businessId), 1);
  }

  public static function recordPendingInviteResolved(string $businessId): void
  {
    self::adjustCachedCounter(Keys::businessMetricsPendingInvites($businessId), -1);
  }

  public static function recordPendingRequestCreated(string $businessId): void
  {
    self::adjustCachedCounter(Keys::businessMetricsPendingRequests($businessId), 1);
  }

  public static function recordPendingRequestResolved(string $businessId): void
  {
    self::adjustCachedCounter(Keys::businessMetricsPendingRequests($businessId), -1);
  }

  public static function touchLastActivity(string $businessId, ?string $timestamp = null): void
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return;
    }

    Database::hset(Keys::BUSINESS . ':' . $businessId, [
      'last_activity_at' => $timestamp ?? date('c'),
    ]);
  }

  /**
   * Increment daily work-entry counters for every business the member belongs to.
   */
  public static function recordWorkEntryCreated(string $memberUUID, string $dateYmd): void
  {
    $memberUUID = trim($memberUUID);
    $dateYmd = trim($dateYmd);
    if ($memberUUID === '' || !WorkEntry::validateDate($dateYmd)) {
      return;
    }

    foreach (BusinessMemberRepository::forUser($memberUUID) as $membership) {
      $businessId = trim($membership['org_id']);
      if ($businessId === '') {
        continue;
      }

      $counterKey = Keys::businessMetricsWorkDay($businessId, $dateYmd);
      Database::incr($counterKey);
      Database::expire($counterKey, self::WORK_COUNTER_TTL_SECONDS);
    }
  }

  public static function formatIntegerCount(int $count): string
  {
    return (string) max(0, $count);
  }

  public static function formatOptionalCount(?int $count): string
  {
    if ($count === null) {
      return '';
    }

    return $count > 0 ? (string) $count : '';
  }

  public static function formatTimestampLabel(string $iso8601): string
  {
    $formatted = TimestampFormatter::formatAuditTimestamp($iso8601);

    return $formatted !== '' ? $formatted : '';
  }

  /**
   * @return array{
   *   members: int,
   *   sites: int,
   *   pending_invites: int|null,
   *   pending_requests: int|null,
   *   work_entries_today: int,
   *   work_entries_week: int,
   *   last_activity_at: string,
   *   created_at: string
   * }
   */
  public static function emptyMetrics(): array
  {
    return [
      'members' => 0,
      'sites' => 0,
      'pending_invites' => null,
      'pending_requests' => null,
      'work_entries_today' => 0,
      'work_entries_week' => 0,
      'last_activity_at' => '',
      'created_at' => '',
    ];
  }

  private static function setSize(string $key): int
  {
    $size = Database::scard($key);

    return $size !== null ? max(0, $size) : max(0, count(Database::smembers($key)));
  }

  private static function pendingInviteCount(string $businessId): int
  {
    return self::readOrRebuildPendingCounter(
      Keys::businessMetricsPendingInvites($businessId),
      Keys::BUSINESS_INVITE_ORG . ':' . $businessId,
      Keys::BUSINESS_INVITE . ':',
    );
  }

  private static function pendingRequestCount(string $businessId): int
  {
    return self::readOrRebuildPendingCounter(
      Keys::businessMetricsPendingRequests($businessId),
      Keys::BUSINESS_ACCESS_REQUEST_ORG . ':' . $businessId,
      Keys::BUSINESS_ACCESS_REQUEST . ':',
    );
  }

  private static function readOrRebuildPendingCounter(
    string $counterKey,
    string $orgSetKey,
    string $recordKeyPrefix,
  ): int {
    $cached = Database::get($counterKey);
    if ($cached !== '') {
      return max(0, (int) $cached);
    }

    $count = self::countPendingStatusesFromOrgSet($orgSetKey, $recordKeyPrefix);
    Database::set($counterKey, (string) $count);

    return $count;
  }

  private static function countPendingStatusesFromOrgSet(string $orgSetKey, string $recordKeyPrefix): int
  {
    $recordIds = Database::smembers($orgSetKey);
    if ($recordIds === []) {
      return 0;
    }

    $statuses = Database::multi(static function (\Redis $redis) use ($recordIds, $recordKeyPrefix): void {
      foreach ($recordIds as $recordId) {
        $redis->hGet($recordKeyPrefix . (string) $recordId, 'status');
      }
    });

    $pending = 0;
    foreach ($statuses as $status) {
      if (is_string($status) && strtolower(trim($status)) === 'pending') {
        $pending++;
      }
    }

    return $pending;
  }

  private static function workEntryCountForDate(string $businessId, string $dateYmd): int
  {
    $raw = Database::get(Keys::businessMetricsWorkDay($businessId, $dateYmd));

    return $raw !== '' ? max(0, (int) $raw) : 0;
  }

  private static function workEntryCountForWeek(
    string $businessId,
    string $todayYmd,
    \DateTimeZone $timezone,
  ): int {
    $today = \DateTimeImmutable::createFromFormat('Y-m-d', $todayYmd, $timezone);
    if (!$today instanceof \DateTimeImmutable) {
      return 0;
    }

    $keys = [];
    for ($offset = 0; $offset < 7; $offset++) {
      $date = $today->modify('-' . $offset . ' days')->format('Y-m-d');
      $keys[] = Keys::businessMetricsWorkDay($businessId, $date);
    }

    $values = Database::multi(static function (\Redis $redis) use ($keys): void {
      foreach ($keys as $key) {
        $redis->get($key);
      }
    });

    $total = 0;
    foreach ($values as $value) {
      if (is_string($value) && $value !== '') {
        $total += max(0, (int) $value);
      }
    }

    return $total;
  }

  /**
   * @param array<string, string> $businessHash
   */
  private static function resolveLastActivityAt(array $businessHash, string $businessId): string
  {
    $lastActivity = trim((string) ($businessHash['last_activity_at'] ?? ''));
    if ($lastActivity !== '') {
      return $lastActivity;
    }

    $updatedAt = trim((string) ($businessHash['updated_at'] ?? ''));
    if ($updatedAt !== '') {
      return $updatedAt;
    }

    $settingsUpdated = trim((string) Database::hget(
      Keys::BUSINESS_SETTINGS . ':' . $businessId,
      'last_updated_at',
    ));

    return $settingsUpdated;
  }

  private static function resolveBusinessTimezone(string $businessId): \DateTimeZone
  {
    $timezoneRaw = trim((string) Database::hget(Keys::BUSINESS_SETTINGS . ':' . $businessId, 'timezone'));
    if ($timezoneRaw === '') {
      $timezoneRaw = 'America/Edmonton';
    }

    try {
      return new \DateTimeZone($timezoneRaw);
    } catch (\Throwable) {
      return new \DateTimeZone('America/Edmonton');
    }
  }

  private static function adjustCachedCounter(string $counterKey, int $delta): void
  {
    if ($delta === 0) {
      return;
    }

    if ($delta > 0) {
      for ($i = 0; $i < $delta; $i++) {
        Database::incr($counterKey);
      }

      return;
    }

    $redis = Database::getWriteInstance()->client;
    for ($i = 0; $i < abs($delta); $i++) {
      $redis->decr($counterKey);
    }
  }
}
