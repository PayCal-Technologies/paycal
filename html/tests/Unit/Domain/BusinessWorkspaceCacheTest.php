<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\BusinessMembersCache;
use PayCal\Domain\BusinessWorkspaceCache;
use PayCal\Domain\BusinessWorkspaceWarmer;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\TeamEarningsSnapshotBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BusinessWorkspaceCacheTest extends TestCase
{
  private string $businessId;
  private string $actorUUID;

  protected function setUp(): void
  {
    $this->businessId = 'test-workspace-cache-' . bin2hex(random_bytes(6));
    $this->actorUUID = 'actor-' . bin2hex(random_bytes(6));
  }

  protected function tearDown(): void
  {
    BusinessWorkspaceCache::releaseWarmLock($this->businessId);
    BusinessWorkspaceCache::invalidate($this->businessId);
    Database::unlink(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->actorUUID);
    Database::unlink(Keys::BUSINESS_USER . ':' . $this->actorUUID);
  }

  private function seedActorSitesReadAccess(): void
  {
    Database::sadd(Keys::BUSINESS_USER . ':' . $this->actorUUID, $this->businessId);
    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->actorUUID, [
      'user_uuid' => $this->actorUUID,
      'role' => 'member',
      'status' => 'active',
      'scopes' => 'sites.read',
      'updated_at' => '2026-01-01T00:00:00Z',
    ]);
  }

  #[Test]
  public function putThenGetRosterRoundTrips(): void
  {
    BusinessWorkspaceCache::putRoster($this->businessId, []);

    $this->assertSame([], BusinessWorkspaceCache::getRoster($this->businessId));
  }

  #[Test]
  public function putThenGetTeamEarningsRoundTrips(): void
  {
    $snapshot = TeamEarningsSnapshotBuilder::emptySnapshot();
    BusinessWorkspaceCache::putTeamEarnings($this->businessId, 2026, $snapshot);

    $cached = BusinessWorkspaceCache::getTeamEarnings($this->businessId, 2026);

    $this->assertNotNull($cached);
    $this->assertSame([], $cached['teamEarningsRows']);
  }

  #[Test]
  public function getTeamEarningsReturnsNullOnYearMismatch(): void
  {
    BusinessWorkspaceCache::putTeamEarnings(
      $this->businessId,
      2025,
      TeamEarningsSnapshotBuilder::emptySnapshot(),
    );

    $this->assertNull(BusinessWorkspaceCache::getTeamEarnings($this->businessId, 2026));
  }

  #[Test]
  public function invalidateClearsMembersCacheToo(): void
  {
    BusinessMembersCache::put($this->businessId, 2026, ['member-a' => ['ytd_gross' => 1.0]]);
    BusinessWorkspaceCache::putSiteSettings($this->businessId, 2026, ['ref' => ['budget_amount' => '100']]);

    BusinessWorkspaceCache::invalidate($this->businessId);

    $this->assertNull(BusinessMembersCache::get($this->businessId, 2026));
    $this->assertNull(BusinessWorkspaceCache::getSiteSettings($this->businessId, 2026));
  }

  #[Test]
  public function putAppliesTtlSafetyNet(): void
  {
    BusinessWorkspaceCache::putRoster($this->businessId, []);

    $ttl = Database::ttl(Keys::businessWorkspaceCache(BusinessWorkspaceCache::SEGMENT_ROSTER, $this->businessId));

    $this->assertGreaterThan(0, $ttl);
    $this->assertLessThanOrEqual(BusinessWorkspaceCache::TTL_SECONDS, $ttl);
  }

  #[Test]
  public function warmReturnsSkippedForEmptyActor(): void
  {
    $result = BusinessWorkspaceWarmer::warm($this->businessId, '');

    $this->assertSame('skipped', $result['segments']['access']['status'] ?? '');
  }

  #[Test]
  public function warmLockAllowsOnlyOneConcurrentClaim(): void
  {
    $this->assertTrue(BusinessWorkspaceCache::tryAcquireWarmLock($this->businessId));
    $this->assertFalse(BusinessWorkspaceCache::tryAcquireWarmLock($this->businessId));
  }

  #[Test]
  public function releaseWarmLockAllowsSubsequentClaim(): void
  {
    $this->assertTrue(BusinessWorkspaceCache::tryAcquireWarmLock($this->businessId));
    BusinessWorkspaceCache::releaseWarmLock($this->businessId);

    $this->assertTrue(BusinessWorkspaceCache::tryAcquireWarmLock($this->businessId));
  }

  #[Test]
  public function isFullyWarmReturnsFalseWhenAnySegmentMissing(): void
  {
    BusinessWorkspaceCache::putRoster($this->businessId, []);

    $this->assertFalse(BusinessWorkspaceCache::isFullyWarm($this->businessId, 2026));
  }

  #[Test]
  public function staleEmptyRosterIsRejectedWhenMembersAreIndexed(): void
  {
    Database::sadd(Keys::BUSINESS_MEMBERS . ':' . $this->businessId, 'member-a', 'member-b');
    BusinessWorkspaceCache::putRoster($this->businessId, []);

    $this->assertNull(BusinessWorkspaceCache::getRoster($this->businessId));
    $this->assertFalse(BusinessWorkspaceCache::isFullyWarm($this->businessId, 2026));

    Database::unlink(Keys::BUSINESS_MEMBERS . ':' . $this->businessId);
  }

  #[Test]
  public function staleEmptyTeamEarningsIsRejectedWhenMembersAreIndexed(): void
  {
    Database::sadd(Keys::BUSINESS_MEMBERS . ':' . $this->businessId, 'member-a');
    BusinessWorkspaceCache::putTeamEarnings(
      $this->businessId,
      2026,
      TeamEarningsSnapshotBuilder::emptySnapshot(),
    );

    $this->assertNull(BusinessWorkspaceCache::getTeamEarnings($this->businessId, 2026));

    Database::unlink(Keys::BUSINESS_MEMBERS . ':' . $this->businessId);
  }

  #[Test]
  public function isFullyWarmReturnsTrueWhenAllSegmentsPresent(): void
  {
    $snapshot = TeamEarningsSnapshotBuilder::emptySnapshot();
    BusinessWorkspaceCache::putRoster($this->businessId, []);
    BusinessWorkspaceCache::putSitesRaw($this->businessId, [
      'business_owner_uuid' => 'owner',
      'business_name' => 'Test',
      'entries' => [],
    ]);
    BusinessWorkspaceCache::putSiteSettings($this->businessId, 2026, [], '');
    BusinessWorkspaceCache::putMemberWork($this->businessId, []);
    BusinessMembersCache::put($this->businessId, 2026, []);
    BusinessWorkspaceCache::putTeamEarnings($this->businessId, 2026, $snapshot);

    $this->assertTrue(BusinessWorkspaceCache::isFullyWarm($this->businessId, 2026));
  }

  #[Test]
  public function requestWarmSkipsWhenCacheAlreadyWarm(): void
  {
    $this->seedActorSitesReadAccess();

    $snapshot = TeamEarningsSnapshotBuilder::emptySnapshot();
    BusinessWorkspaceCache::putRoster($this->businessId, []);
    BusinessWorkspaceCache::putSitesRaw($this->businessId, [
      'business_owner_uuid' => 'owner',
      'business_name' => 'Test',
      'entries' => [],
    ]);
    BusinessWorkspaceCache::putSiteSettings($this->businessId, 2026, [], '');
    BusinessWorkspaceCache::putMemberWork($this->businessId, []);
    BusinessMembersCache::put($this->businessId, 2026, []);
    BusinessWorkspaceCache::putTeamEarnings($this->businessId, 2026, $snapshot);

    $result = BusinessWorkspaceWarmer::requestWarm($this->businessId, $this->actorUUID);

    $this->assertSame('warm', $result['warm_status'] ?? '');
    $this->assertTrue(
      BusinessWorkspaceCache::tryAcquireWarmLock($this->businessId),
      'A warm cache should not leave an in-flight warm lock behind',
    );
  }

  #[Test]
  public function requestWarmReturnsInProgressWhenWarmLockHeld(): void
  {
    $this->seedActorSitesReadAccess();
    $this->assertTrue(BusinessWorkspaceCache::tryAcquireWarmLock($this->businessId));

    $result = BusinessWorkspaceWarmer::requestWarm($this->businessId, $this->actorUUID);

    $this->assertSame('in_progress', $result['warm_status'] ?? '');
  }

  #[Test]
  public function invalidateFinancialDataPreservesRosterAndSites(): void
  {
    BusinessWorkspaceCache::putRoster($this->businessId, []);
    BusinessWorkspaceCache::putSitesRaw($this->businessId, [
      'business_owner_uuid' => 'owner',
      'business_name' => 'Test',
      'entries' => [],
    ]);
    BusinessWorkspaceCache::putMemberWork($this->businessId, ['member-a' => []]);
    BusinessMembersCache::put($this->businessId, 2026, ['member-a' => ['ytd_gross' => 1.0]]);
    BusinessWorkspaceCache::putTeamEarnings(
      $this->businessId,
      2026,
      TeamEarningsSnapshotBuilder::emptySnapshot(),
    );

    BusinessWorkspaceCache::invalidateFinancialData($this->businessId, 2026);

    $this->assertNotNull(BusinessWorkspaceCache::getRoster($this->businessId));
    $this->assertNotNull(BusinessWorkspaceCache::getSitesRaw($this->businessId));
    $this->assertNull(BusinessWorkspaceCache::getMemberWork($this->businessId));
    $this->assertNull(BusinessMembersCache::get($this->businessId, 2026));
    $this->assertNull(BusinessWorkspaceCache::getTeamEarnings($this->businessId, 2026));
  }

  #[Test]
  public function invalidateSiteSettingsPreservesRosterAndFinancialSegments(): void
  {
    BusinessWorkspaceCache::putRoster($this->businessId, []);
    BusinessWorkspaceCache::putSiteSettings($this->businessId, 2026, ['ref' => ['budget_amount' => '100']]);
    BusinessWorkspaceCache::putMemberWork($this->businessId, ['member-a' => []]);
    BusinessMembersCache::put($this->businessId, 2026, ['member-a' => ['ytd_gross' => 1.0]]);

    BusinessWorkspaceCache::invalidateSiteSettings($this->businessId);

    $this->assertNotNull(BusinessWorkspaceCache::getRoster($this->businessId));
    $this->assertNull(BusinessWorkspaceCache::getSiteSettings($this->businessId, 2026));
    $this->assertNotNull(BusinessWorkspaceCache::getMemberWork($this->businessId));
    $this->assertNotNull(BusinessMembersCache::get($this->businessId, 2026));
  }

  #[Test]
  public function workEntrySaveUsesFinancialInvalidationOnly(): void
  {
    $source = (string) file_get_contents(
      dirname(__DIR__, 3) . '/src/Domain/WorkEntry.php',
    );

    $this->assertStringContainsString('invalidateFinancialDataForMember', $source);
    $this->assertStringNotContainsString('invalidateForMember', $source);
  }

  #[Test]
  public function membershipMutationsInvalidateThroughSetConnection(): void
  {
    $source = (string) file_get_contents(
      dirname(__DIR__, 3) . '/src/Domain/BusinessDiscoveryService.php',
    );

    $setConnectionPos = strpos($source, 'private function setConnection');
    $this->assertNotFalse($setConnectionPos);

    $body = substr($source, $setConnectionPos, 3000);
    $this->assertStringContainsString('BusinessWorkspaceCache::invalidate', $body);
  }
}
