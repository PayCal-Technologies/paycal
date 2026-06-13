<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\BusinessSnapshot;
use PayCal\Domain\BusinessSnapshotCache;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BusinessSnapshotCacheTest extends TestCase
{
  private string $businessId;

  protected function setUp(): void
  {
    $this->businessId = 'test-snapshot-cache-' . bin2hex(random_bytes(6));
  }

  protected function tearDown(): void
  {
    BusinessSnapshotCache::invalidate($this->businessId);
  }

  #[Test]
  public function putThenGetRoundTripsSnapshot(): void
  {
    $snapshot = new BusinessSnapshot(
      snapshot_version: '2026-06-10T12:00:00Z',
      business_id: $this->businessId,
      member_count: 2,
      site_count: 1,
      relationships: [],
      members: [],
      pending_invites: 0,
      pending_requests: 0,
      generated_at: '2026-06-10T12:00:00Z',
    );

    BusinessSnapshotCache::put($snapshot);
    $cached = BusinessSnapshotCache::get($this->businessId);

    $this->assertNotNull($cached);
    $this->assertSame('2026-06-10T12:00:00Z', $cached->snapshot_version);
    $this->assertSame(2, $cached->member_count);
    $this->assertSame(1, $cached->site_count);
  }

  #[Test]
  public function invalidateDropsSnapshotKey(): void
  {
    BusinessSnapshotCache::put(new BusinessSnapshot(
      snapshot_version: 'v1',
      business_id: $this->businessId,
      member_count: 0,
      site_count: 0,
      relationships: [],
      members: [],
    ));

    BusinessSnapshotCache::invalidate($this->businessId);

    $this->assertNull(BusinessSnapshotCache::get($this->businessId));
    $this->assertSame('', Database::get(Keys::businessSnapshot($this->businessId)));
  }
}
