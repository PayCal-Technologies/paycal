<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * Central pre-warmable cache for business workspace read paths.
 *
 * Stores materialized roster, sites, site settings, team earnings, and member
 * work-entry batches under business:cache:workspace:* keys. Member financial
 * summaries remain in BusinessMembersCache.
 *
 * Invalidation tiers:
 * - invalidate(): membership/site-link mutations (full purge)
 * - invalidateFinancialData(): work-entry edits (member work + summaries + team earnings)
 * - invalidateSiteSettings(): site planning/budget field updates only
 */
final class BusinessWorkspaceCache
{
  /** Safety-net TTL; membership/site-link mutations DEL keys eagerly. */
  public const TTL_SECONDS = 900;
  public const WARM_LOCK_TTL_SECONDS = 180;
  private const SCHEMA_VERSION = 1;

  public const SEGMENT_ROSTER = 'roster';
  public const SEGMENT_SITES = 'sites';
  public const SEGMENT_SITE_SETTINGS = 'site_settings';
  public const SEGMENT_TEAM_EARNINGS = 'team_earnings';
  public const SEGMENT_MEMBER_WORK = 'member_work';

  /**
   * Members indexed for a business in Redis (business:members SET size).
   */
  public static function indexedMemberCount(string $businessId): int
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return 0;
    }

    return count(Database::smembers(Keys::BUSINESS_MEMBERS . ':' . $businessId));
  }

  /**
   * @return list<array{
   *   user: User,
   *   role: string,
   *   status: string,
   *   scopes: list<string>,
   *   updated_at: string
   * }>|null
   */
  public static function getRoster(string $businessId): ?array
  {
    $payload = self::readSegment(self::SEGMENT_ROSTER, $businessId);
    if ($payload === null) {
      return null;
    }

    $membersRaw = $payload['members'] ?? null;
    if (!is_array($membersRaw)) {
      return null;
    }

    $members = [];
    foreach ($membersRaw as $entry) {
      if (!is_array($entry)) {
        continue;
      }

      $userUuid = trim(self::scalarString($entry['user_uuid'] ?? ''));
      if ($userUuid === '') {
        continue;
      }

      $user = new User();
      $user->user_uuid = $userUuid;
      $user->full_name = trim(self::scalarString($entry['full_name'] ?? ''));
      $user->email = trim(self::scalarString($entry['email'] ?? ''));

      $scopes = $entry['scopes'] ?? [];
      if (!is_array($scopes)) {
        $scopes = [];
      }

      $members[] = [
        'user' => $user,
        'role' => trim(self::scalarString($entry['role'] ?? '')),
        'status' => trim(self::scalarString($entry['status'] ?? '')),
        'scopes' => array_values(array_filter(array_map(
          static fn ($scope): string => is_scalar($scope) ? trim((string) $scope) : '',
          $scopes,
        ), static fn (string $scope): bool => $scope !== '')),
        'updated_at' => trim(self::scalarString($entry['updated_at'] ?? '')),
      ];
    }

    usort($members, static function (array $a, array $b): int {
      return strcasecmp((string) $a['user']->full_name, (string) $b['user']->full_name);
    });

    if ($members === [] && self::indexedMemberCount($businessId) > 0) {
      self::dropSegment(self::SEGMENT_ROSTER, $businessId);

      return null;
    }

    return $members;
  }

  /**
   * @param list<array{
   *   user: User,
   *   role: string,
   *   status: string,
   *   scopes: list<string>,
   *   updated_at: string
   * }> $members
   */
  public static function putRoster(string $businessId, array $members): void
  {
    if ($members === [] && self::indexedMemberCount($businessId) > 0) {
      return;
    }

    $serialized = [];
    foreach ($members as $member) {
      $user = $member['user'];

      $serialized[] = [
        'user_uuid' => (string) $user->user_uuid,
        'full_name' => (string) $user->full_name,
        'email' => (string) $user->email,
        'role' => $member['role'],
        'status' => $member['status'],
        'scopes' => $member['scopes'],
        'updated_at' => $member['updated_at'],
      ];
    }

    self::writeSegment(self::SEGMENT_ROSTER, $businessId, [
      'members' => $serialized,
    ]);
  }

  /**
   * @return array{
   *   business_owner_uuid: string,
   *   business_name: string,
   *   entries: list<array{
   *     ref: string,
   *     site_owner_uuid: string,
   *     site_id: string,
   *     site_hash: array<string, string>,
   *     settings: array<string, string>
   *   }>
   * }|null
   */
  public static function getSitesRaw(string $businessId): ?array
  {
    $payload = self::readSegment(self::SEGMENT_SITES, $businessId);
    if ($payload === null) {
      return null;
    }

    $entries = $payload['entries'] ?? null;
    if (!is_array($entries)) {
      return null;
    }

    return [
      'business_owner_uuid' => trim(self::scalarString($payload['business_owner_uuid'] ?? '')),
      'business_name' => trim(self::scalarString($payload['business_name'] ?? '')),
      'entries' => self::normalizeSiteEntries($entries),
    ];
  }

  /**
   * @param array{
   *   business_owner_uuid: string,
   *   business_name: string,
   *   entries: list<array{
   *     ref: string,
   *     site_owner_uuid: string,
   *     site_id: string,
   *     site_hash: array<string, string>,
   *     settings: array<string, string>
   *   }>
   * } $sitesRaw
   */
  public static function putSitesRaw(string $businessId, array $sitesRaw): void
  {
    self::writeSegment(self::SEGMENT_SITES, $businessId, $sitesRaw);
  }

  /**
   * @return array{
   *   site_settings_by_ref: array<string, array<string, string>>,
   *   business_annual_budget: string
   * }|null
   */
  public static function getSiteSettings(string $businessId, int $year): ?array
  {
    $payload = self::readSegment(self::SEGMENT_SITE_SETTINGS, $businessId, $year);
    if ($payload === null) {
      return null;
    }

    $yearRaw = $payload['year'] ?? null;
    if (!is_scalar($yearRaw) || (int) $yearRaw !== $year) {
      return null;
    }

    $settingsByRef = $payload['site_settings_by_ref'] ?? null;
    if (!is_array($settingsByRef)) {
      return null;
    }

    return [
      'site_settings_by_ref' => self::normalizeSiteSettingsByRef($settingsByRef),
      'business_annual_budget' => trim(self::scalarString($payload['business_annual_budget'] ?? '')),
    ];
  }

  /**
   * @param array<string, array<string, string>> $siteSettingsByRef
   */
  public static function putSiteSettings(
    string $businessId,
    int $year,
    array $siteSettingsByRef,
    string $businessAnnualBudget = '',
  ): void {
    self::writeSegment(self::SEGMENT_SITE_SETTINGS, $businessId, [
      'year' => $year,
      'site_settings_by_ref' => $siteSettingsByRef,
      'business_annual_budget' => $businessAnnualBudget,
    ], $year);
  }

  /**
   * @return array<string, mixed>|null
   */
  public static function getTeamEarnings(string $businessId, int $year): ?array
  {
    $payload = self::readSegment(self::SEGMENT_TEAM_EARNINGS, $businessId, $year);
    if ($payload === null) {
      return null;
    }

    $yearRaw = $payload['year'] ?? null;
    if (!is_scalar($yearRaw) || (int) $yearRaw !== $year) {
      return null;
    }

    $snapshot = $payload['snapshot'] ?? null;
    if (!is_array($snapshot)) {
      return null;
    }

    $rows = $snapshot['teamEarningsRows'] ?? null;
    if (is_array($rows) && $rows === [] && self::indexedMemberCount($businessId) > 0) {
      self::dropSegment(self::SEGMENT_TEAM_EARNINGS, $businessId, $year);

      return null;
    }

    return $snapshot;
  }

  /**
   * @param array<string, mixed> $snapshot
   */
  public static function putTeamEarnings(string $businessId, int $year, array $snapshot): void
  {
    $rows = $snapshot['teamEarningsRows'] ?? null;
    if (is_array($rows) && $rows === [] && self::indexedMemberCount($businessId) > 0) {
      return;
    }

    self::writeSegment(self::SEGMENT_TEAM_EARNINGS, $businessId, [
      'year' => $year,
      'snapshot' => $snapshot,
    ], $year);
  }

  /**
   * @return array<string, array<string, array<string, string>>>|null Member UUID => work key => entry hash
   */
  public static function getMemberWork(string $businessId): ?array
  {
    $payload = self::readSegment(self::SEGMENT_MEMBER_WORK, $businessId);
    if ($payload === null) {
      return null;
    }

    $entriesByMember = $payload['entries_by_member'] ?? null;
    if (!is_array($entriesByMember)) {
      return null;
    }

    if ($entriesByMember === [] && self::indexedMemberCount($businessId) > 0) {
      self::dropSegment(self::SEGMENT_MEMBER_WORK, $businessId);

      return null;
    }

    return self::normalizeMemberWorkEntries($entriesByMember);
  }

  /**
   * @param array<string, array<string, array<string, string>>> $entriesByMember
   */
  public static function putMemberWork(string $businessId, array $entriesByMember): void
  {
    self::writeSegment(self::SEGMENT_MEMBER_WORK, $businessId, [
      'entries_by_member' => $entriesByMember,
    ]);
  }

  /**
   * True when every pre-warmable workspace segment is present for the given year.
   */
  public static function isFullyWarm(string $businessId, int $year): bool
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return false;
    }

    return self::getRoster($businessId) !== null
      && self::getSitesRaw($businessId) !== null
      && self::getSiteSettings($businessId, $year) !== null
      && self::getMemberWork($businessId) !== null
      && BusinessMembersCache::get($businessId, $year) !== null
      && self::getTeamEarnings($businessId, $year) !== null;
  }

  /**
   * Atomically claim the in-flight warm lock for a business.
   */
  public static function tryAcquireWarmLock(string $businessId): bool
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return false;
    }

    return Database::setnx(
      Keys::businessWorkspaceWarmLock($businessId),
      (string) time(),
      self::WARM_LOCK_TTL_SECONDS,
    );
  }

  /**
   * Release the in-flight warm lock for a business.
   */
  public static function releaseWarmLock(string $businessId): void
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return;
    }

    Database::unlink(Keys::businessWorkspaceWarmLock($businessId));
  }

  /**
   * Drop all workspace cache segments and the members financial summary cache.
   */
  public static function invalidate(string $businessId): void
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return;
    }

    BusinessMembersCache::invalidate($businessId);
    BusinessSnapshotCache::invalidate($businessId);

    foreach (Database::scanKeys(Keys::businessWorkspaceCachePattern($businessId)) as $key) {
      Database::unlink($key);
    }
  }

  /**
   * Drop financial segments only (member work, team earnings, members summaries).
   * Preserves roster, sites, and site settings.
   */
  public static function invalidateFinancialData(string $businessId, ?int $year = null): void
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return;
    }

    BusinessMembersCache::invalidate($businessId);
    self::dropSegment(self::SEGMENT_MEMBER_WORK, $businessId);

    if ($year !== null) {
      self::dropSegment(self::SEGMENT_TEAM_EARNINGS, $businessId, $year);

      return;
    }

    self::dropSegmentKeys(self::SEGMENT_TEAM_EARNINGS, $businessId);
  }

  /**
   * Financial invalidation for every business the member belongs to.
   */
  public static function invalidateFinancialDataForMember(string $memberUUID, ?int $year = null): void
  {
    $memberUUID = trim($memberUUID);
    if ($memberUUID === '') {
      return;
    }

    foreach (BusinessMemberRepository::forUser($memberUUID) as $membership) {
      $businessId = trim($membership['org_id']);
      if ($businessId !== '') {
        self::invalidateFinancialData($businessId, $year);
      }
    }
  }

  /**
   * Drop cached site settings only (budget/planning fields).
   */
  public static function invalidateSiteSettings(string $businessId): void
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return;
    }

    self::dropSegmentKeys(self::SEGMENT_SITE_SETTINGS, $businessId);
  }

  /**
   * TODO: Document dropSegment.
   */
  private static function dropSegment(string $segment, string $businessId, ?int $year = null): void
  {
    Database::unlink(Keys::businessWorkspaceCache($segment, $businessId, $year));
  }

  /**
   * TODO: Document dropSegmentKeys.
   */
  private static function dropSegmentKeys(string $segment, string $businessId): void
  {
    $needle = ':' . $segment . ':' . $businessId;
    foreach (Database::scanKeys(Keys::businessWorkspaceCachePattern($businessId)) as $key) {
      if (str_contains($key, $needle)) {
        Database::unlink($key);
      }
    }
  }

  /**
   * @return array<string, mixed>|null
   */
  private static function readSegment(string $segment, string $businessId, ?int $year = null): ?array
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return null;
    }

    $raw = Database::get(Keys::businessWorkspaceCache($segment, $businessId, $year));
    if ($raw === '') {
      return null;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      return null;
    }

    $schemaRaw = $decoded['schema'] ?? null;
    if (!is_scalar($schemaRaw) || (int) $schemaRaw !== self::SCHEMA_VERSION) {
      return null;
    }

    return $decoded;
  }

  /**
   * @param array<string, mixed> $payload
   */
  private static function writeSegment(
    string $segment,
    string $businessId,
    array $payload,
    ?int $year = null,
  ): void {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return;
    }

    $encoded = json_encode([
      'schema' => self::SCHEMA_VERSION,
      'generated_at' => date('c'),
      ...$payload,
    ]);

    if (!is_string($encoded)) {
      return;
    }

    Database::set(Keys::businessWorkspaceCache($segment, $businessId, $year), $encoded, self::TTL_SECONDS);
  }
  /**
   * TODO: Document scalarString.
   */
  private static function scalarString(mixed $value): string
  {
    return is_scalar($value) ? (string) $value : '';
  }

  /**
   * @param array<mixed, mixed> $entriesRaw
   * @return list<array{
   *   ref: string,
   *   site_owner_uuid: string,
   *   site_id: string,
   *   site_hash: array<string, string>,
   *   settings: array<string, string>
   * }>
   */
  private static function normalizeSiteEntries(array $entriesRaw): array
  {
    $entries = [];
    foreach ($entriesRaw as $entry) {
      if (!is_array($entry)) {
        continue;
      }

      $ref = trim(self::scalarString($entry['ref'] ?? ''));
      $siteOwnerUuid = trim(self::scalarString($entry['site_owner_uuid'] ?? ''));
      $siteId = trim(self::scalarString($entry['site_id'] ?? ''));
      if ($siteOwnerUuid === '' || $siteId === '') {
        continue;
      }

      $siteHashRaw = $entry['site_hash'] ?? null;
      $siteHash = is_array($siteHashRaw) ? self::normalizeStringMap($siteHashRaw) : [];
      $settingsRaw = $entry['settings'] ?? null;
      $settings = is_array($settingsRaw) ? self::normalizeStringMap($settingsRaw) : [];

      $entries[] = [
        'ref' => $ref !== '' ? $ref : ($siteOwnerUuid . ':' . $siteId),
        'site_owner_uuid' => $siteOwnerUuid,
        'site_id' => $siteId,
        'site_hash' => $siteHash,
        'settings' => $settings,
      ];
    }

    return $entries;
  }

  /**
   * @param array<mixed, mixed> $map
   * @return array<string, string>
   */
  private static function normalizeStringMap(array $map): array
  {
    $normalized = [];
    foreach ($map as $key => $value) {
      $normalized[(string) $key] = self::scalarString($value);
    }

    return $normalized;
  }

  /**
   * @param array<mixed, mixed> $settingsByRefRaw
   * @return array<string, array<string, string>>
   */
  private static function normalizeSiteSettingsByRef(array $settingsByRefRaw): array
  {
    $normalized = [];
    foreach ($settingsByRefRaw as $ref => $settings) {
      if (!is_array($settings)) {
        continue;
      }
      $normalized[(string) $ref] = self::normalizeStringMap($settings);
    }

    return $normalized;
  }

  /**
   * @param array<mixed, mixed> $entriesByMemberRaw
   * @return array<string, array<string, array<string, string>>>
   */
  private static function normalizeMemberWorkEntries(array $entriesByMemberRaw): array
  {
    $normalized = [];
    foreach ($entriesByMemberRaw as $memberUuid => $workEntries) {
      if (!is_array($workEntries)) {
        continue;
      }

      $memberKey = (string) $memberUuid;
      $memberEntries = [];
      foreach ($workEntries as $workKey => $entry) {
        if (!is_array($entry)) {
          continue;
        }
        $memberEntries[(string) $workKey] = self::normalizeStringMap($entry);
      }
      $normalized[$memberKey] = $memberEntries;
    }

    return $normalized;
  }

}
