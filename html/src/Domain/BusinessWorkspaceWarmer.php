<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\Constants\Keys;
use PayCal\Observability\Lens;

/**
 * Pre-populates BusinessWorkspaceCache segments for a business workspace.
 */
final class BusinessWorkspaceWarmer
{
  /**
   * Idempotently queue a background warm for one business workspace.
   *
   * @return array{
   *   business_id: string,
   *   year: int,
   *   warm_status: string,
   *   duration_ms?: float,
   *   segments?: array<string, array{status: string, detail?: string}>
   * }
   */
  public static function requestWarm(string $businessId, string $actorUUID, ?int $year = null): array
  {
    $businessId = trim($businessId);
    $actorUUID = trim($actorUUID);
    $year = self::normalizeYear($year);

    if ($businessId === '' || $actorUUID === '') {
      return [
        'business_id' => $businessId,
        'year' => $year,
        'warm_status' => 'skipped',
      ];
    }

    $discovery = new BusinessDiscoveryService();
    if (!$discovery->canReadBusinessSites($businessId, $actorUUID)) {
      return [
        'business_id' => $businessId,
        'year' => $year,
        'warm_status' => 'denied',
      ];
    }

    if (BusinessWorkspaceCache::isFullyWarm($businessId, $year)) {
      return [
        'business_id' => $businessId,
        'year' => $year,
        'warm_status' => 'warm',
      ];
    }

    if (!BusinessWorkspaceCache::tryAcquireWarmLock($businessId)) {
      return [
        'business_id' => $businessId,
        'year' => $year,
        'warm_status' => 'in_progress',
      ];
    }

    if (!self::dispatchWarmInBackground($businessId, $actorUUID, $year)) {
      BusinessWorkspaceCache::releaseWarmLock($businessId);

      return [
        'business_id' => $businessId,
        'year' => $year,
        'warm_status' => 'dispatch_failed',
      ];
    }

    return [
      'business_id' => $businessId,
      'year' => $year,
      'warm_status' => 'accepted',
    ];
  }

  /**
   * Fire-and-forget warm for every active business membership after login.
   */
  public static function requestWarmForUser(string $actorUUID, ?int $year = null): void
  {
    if (defined('PHPUNIT_COMPOSER_INSTALL')) {
      return;
    }

    $actorUUID = trim($actorUUID);
    if ($actorUUID === '') {
      return;
    }

    $hasActiveMembership = false;
    foreach (BusinessMemberRepository::forUser($actorUUID) as $membership) {
      if (trim($membership['status']) === 'active') {
        $hasActiveMembership = true;
        break;
      }
    }

    if (!$hasActiveMembership) {
      return;
    }

    self::dispatchUserWarmInBackground($actorUUID, self::normalizeYear($year));
  }

  /**
   * Populate all workspace cache segments for the selected business.
   *
   * @return array{
   *   business_id: string,
   *   year: int,
   *   duration_ms: float,
   *   segments: array<string, array{status: string, detail?: string}>
   * }
   */
  public static function warm(string $businessId, string $actorUUID, ?int $year = null): array
  {
    $businessId = trim($businessId);
    $actorUUID = trim($actorUUID);
    $year = self::normalizeYear($year);
    $startedAt = microtime(true);
    $segments = [];

    try {
      if ($businessId === '' || $actorUUID === '') {
        return [
          'business_id' => $businessId,
          'year' => $year,
          'duration_ms' => 0.0,
          'segments' => ['access' => ['status' => 'skipped', 'detail' => 'missing business or actor']],
        ];
      }

      $discovery = new BusinessDiscoveryService();
      if (!$discovery->canReadBusinessSites($businessId, $actorUUID)) {
        return [
          'business_id' => $businessId,
          'year' => $year,
          'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
          'segments' => ['access' => ['status' => 'denied']],
        ];
      }

      Lens::timeStart('Business workspace cache warm');

      $segments['roster'] = self::warmRoster($businessId);
      $segments['sites'] = self::warmSites($businessId, $actorUUID);
      $segments['site_settings'] = self::warmSiteSettings($businessId, $year);
      $segments['member_work'] = self::warmMemberWork($businessId);
      $segments['member_summaries'] = self::warmMemberSummaries($businessId, $year);
      $segments['team_earnings'] = self::warmTeamEarnings($businessId, $year);

      $durationMs = round((microtime(true) - $startedAt) * 1000, 2);
      Lens::timeEnd('Business workspace cache warm');
      Lens::add('Business workspace cache warm complete', [
        'business_id' => $businessId,
        'year' => $year,
        'duration_ms' => $durationMs,
        'segments' => $segments,
      ]);

      return [
        'business_id' => $businessId,
        'year' => $year,
        'duration_ms' => $durationMs,
        'segments' => $segments,
      ];
    } finally {
      BusinessWorkspaceCache::releaseWarmLock($businessId);
    }
  }

  /**
   * Warm every active business membership for a user (CLI / login worker path).
   *
   * @return list<array<string, mixed>>
   */
  public static function warmActiveMembershipsForUser(string $actorUUID, ?int $year = null): array
  {
    $actorUUID = trim($actorUUID);
    $year = self::normalizeYear($year);
    $results = [];

    foreach (BusinessMemberRepository::forUser($actorUUID) as $membership) {
      if (trim($membership['status']) !== 'active') {
        continue;
      }

      $businessId = trim($membership['org_id']);
      if ($businessId === '') {
        continue;
      }

      if (BusinessWorkspaceCache::isFullyWarm($businessId, $year)) {
        $results[] = [
          'business_id' => $businessId,
          'year' => $year,
          'warm_status' => 'warm',
        ];
        continue;
      }

      if (!BusinessWorkspaceCache::tryAcquireWarmLock($businessId)) {
        $results[] = [
          'business_id' => $businessId,
          'year' => $year,
          'warm_status' => 'in_progress',
        ];
        continue;
      }

      $results[] = array_merge(
        ['warm_status' => 'warmed'],
        self::warm($businessId, $actorUUID, $year),
      );
    }

    return $results;
  }

  /**
   * TODO: Document dispatchWarmInBackground.
   */
  private static function dispatchWarmInBackground(string $businessId, string $actorUUID, int $year): bool
  {
    if (defined('PHPUNIT_COMPOSER_INSTALL')) {
      return false;
    }

    if (self::forkSingleBusinessWarm($businessId, $actorUUID, $year)) {
      return true;
    }

    return self::spawnDetachedCliWarm(
      self::warmScriptPath(),
      $businessId,
      $actorUUID,
      (string) $year,
    );
  }

  /**
   * TODO: Document dispatchUserWarmInBackground.
   */
  private static function dispatchUserWarmInBackground(string $actorUUID, int $year): bool
  {
    if (defined('PHPUNIT_COMPOSER_INSTALL')) {
      return false;
    }

    if (self::forkUserWarm($actorUUID, $year)) {
      return true;
    }

    return self::spawnDetachedCliWarm(
      self::warmScriptPath(),
      '--user',
      $actorUUID,
      (string) $year,
    );
  }

  /**
   * pcntl_fork is unsafe under PHP-FPM: the child inherits the parent's
   * persistent Redis connection and corrupts the protocol for both processes.
   */
  private static function canForkSafely(): bool
  {
    return function_exists('pcntl_fork') && PHP_SAPI === 'cli';
  }

  /**
   * TODO: Document forkSingleBusinessWarm.
   */
  private static function forkSingleBusinessWarm(string $businessId, string $actorUUID, int $year): bool
  {
    if (!self::canForkSafely()) {
      return false;
    }

    $pid = pcntl_fork();
    if ($pid === -1) {
      return false;
    }

    if ($pid > 0) {
      return true;
    }

    if (function_exists('posix_setsid')) {
      posix_setsid();
    }

    self::warm($businessId, $actorUUID, $year);
    exit(0);
  }

  /**
   * TODO: Document forkUserWarm.
   */
  private static function forkUserWarm(string $actorUUID, int $year): bool
  {
    if (!self::canForkSafely()) {
      return false;
    }

    $pid = pcntl_fork();
    if ($pid === -1) {
      return false;
    }

    if ($pid > 0) {
      return true;
    }

    if (function_exists('posix_setsid')) {
      posix_setsid();
    }

    self::warmActiveMembershipsForUser($actorUUID, $year);
    exit(0);
  }

  /**
   * TODO: Document spawnDetachedCliWarm.
   */
  private static function spawnDetachedCliWarm(string ...$args): bool
  {
    $command = array_values(array_merge([self::resolveCliPhpBinary()], $args));
    $descriptors = [
      0 => ['file', '/dev/null', 'r'],
      1 => ['file', '/dev/null', 'w'],
      2 => ['file', '/dev/null', 'w'],
    ];

    $process = proc_open(
      $command,
      $descriptors,
      $pipes,
      Environment::appHome(),
      null,
      ['bypass_shell' => true],
    );

    return is_resource($process);
  }

  /**
   * TODO: Document warmScriptPath.
   */
  private static function warmScriptPath(): string
  {
    return rtrim(Environment::appHome(), '/') . '/tools/warm_business_workspace_cache.php';
  }

  /**
   * TODO: Document resolveCliPhpBinary.
   */
  private static function resolveCliPhpBinary(): string
  {
    $phpBinary = PHP_BINARY;
    if (str_contains($phpBinary, 'php-fpm')) {
      $phpBinary = str_replace(['sbin/php-fpm', 'php-fpm'], ['bin/php', 'php'], $phpBinary);
    }

    return $phpBinary;
  }

  /**
   * @return array{status: string, detail?: string}
   */
  private static function warmRoster(string $businessId): array
  {
    if (BusinessWorkspaceCache::getRoster($businessId) !== null) {
      return ['status' => 'hit'];
    }

    $members = BusinessMemberRepository::forBusiness($businessId, null, null, useCache: false);
    if ($members === [] && BusinessWorkspaceCache::indexedMemberCount($businessId) > 0) {
      return ['status' => 'error', 'detail' => 'roster_empty_with_indexed_members'];
    }

    BusinessWorkspaceCache::putRoster($businessId, array_values($members));

    return ['status' => 'warmed', 'detail' => (string) count($members)];
  }

  /**
   * @return array{status: string, detail?: string}
   */
  private static function warmSites(string $businessId, string $actorUUID): array
  {
    if (BusinessWorkspaceCache::getSitesRaw($businessId) !== null) {
      return ['status' => 'hit'];
    }

    $service = new BusinessDiscoveryService();
    $result = $service->listBusinessSites($actorUUID, $businessId, useCache: false);
    if (!$result['success']) {
      return ['status' => 'error', 'detail' => $result['message']];
    }

    $sites = is_array($result['data']['sites'] ?? null) ? $result['data']['sites'] : [];

    return ['status' => 'warmed', 'detail' => (string) count($sites)];
  }

  /**
   * @return array{status: string, detail?: string}
   */
  private static function warmSiteSettings(string $businessId, int $year): array
  {
    if (BusinessWorkspaceCache::getSiteSettings($businessId, $year) !== null) {
      return ['status' => 'hit'];
    }

    $siteRefs = Database::smembers(Keys::BUSINESS_SITE . ':' . $businessId);
    $siteSettingsByRef = [];
    $settingsKeys = [];
    foreach ($siteRefs as $siteRefRaw) {
      $siteRef = (string) $siteRefRaw;
      if ($siteRef === '') {
        continue;
      }
      $settingsKeys[$siteRef] = Keys::BUSINESS_SITE_SETTINGS . ':' . $businessId . ':' . $siteRef;
    }

    $settingsHashes = $settingsKeys !== []
      ? Database::pipelineHgetall(array_values($settingsKeys))
      : [];
    foreach ($settingsKeys as $siteRef => $settingsKey) {
      $settings = $settingsHashes[$settingsKey] ?? [];
      if ($settings !== []) {
        $siteSettingsByRef[$siteRef] = $settings;
      }
    }

    $annualBudget = (string) Database::hget(Keys::BUSINESS . ':' . $businessId, 'annual_budget');
    BusinessWorkspaceCache::putSiteSettings($businessId, $year, $siteSettingsByRef, $annualBudget);

    return ['status' => 'warmed', 'detail' => (string) count($siteSettingsByRef)];
  }

  /**
   * @return array{status: string, detail?: string}
   */
  private static function warmMemberWork(string $businessId): array
  {
    if (BusinessWorkspaceCache::getMemberWork($businessId) !== null) {
      return ['status' => 'hit'];
    }

    $members = BusinessMemberRepository::forBusiness($businessId, null, 'active', useCache: true);
    $memberUuids = [];
    foreach ($members as $member) {
      $memberUuids[] = (string) $member['user']->user_uuid;
    }

    $entriesByMember = [];
    foreach (array_chunk($memberUuids, MemberWorkEntriesFetcher::MEMBER_FETCH_BATCH_SIZE) as $batch) {
      $entriesByMember = array_replace(
        $entriesByMember,
        MemberWorkEntriesFetcher::fetchForMembers($batch),
      );
    }

    BusinessWorkspaceCache::putMemberWork($businessId, $entriesByMember);

    return ['status' => 'warmed', 'detail' => (string) count($entriesByMember)];
  }

  /**
   * @return array{status: string, detail?: string}
   */
  private static function warmMemberSummaries(string $businessId, int $year): array
  {
    if (BusinessMembersCache::get($businessId, $year) !== null) {
      return ['status' => 'hit'];
    }

    $members = BusinessMemberRepository::forBusiness($businessId, null, 'active', useCache: true);
    $memberUuids = [];
    foreach ($members as $member) {
      $memberUuids[] = (string) $member['user']->user_uuid;
    }

    if ($memberUuids === []) {
      BusinessMembersCache::put($businessId, $year, []);

      return ['status' => 'warmed', 'detail' => '0'];
    }

    (new BusinessMembersFinancialSummary())->forBusinessMembers(
      $businessId,
      $memberUuids,
      $year,
      false,
      true,
    );

    return ['status' => 'warmed', 'detail' => (string) count($memberUuids)];
  }

  /**
   * @return array{status: string, detail?: string}
   */
  private static function warmTeamEarnings(string $businessId, int $year): array
  {
    if (BusinessWorkspaceCache::getTeamEarnings($businessId, $year) !== null) {
      return ['status' => 'hit'];
    }

    $snapshot = TeamEarningsSnapshotBuilder::build($businessId, $year);
    BusinessWorkspaceCache::putTeamEarnings($businessId, $year, $snapshot);

    return [
      'status' => 'warmed',
      'detail' => (string) count($snapshot['teamEarningsRows']),
    ];
  }

  /**
   * TODO: Document normalizeYear.
   */
  private static function normalizeYear(?int $year): int
  {
    $year = $year ?? (int) date('Y');
    if ($year < 2000 || $year > 2100) {
      $year = (int) date('Y');
    }

    return $year;
  }
}
