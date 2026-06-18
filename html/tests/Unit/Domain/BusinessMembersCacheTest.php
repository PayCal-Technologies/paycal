<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\BusinessMembersCache;
use PayCal\Domain\BusinessMembersFinancialSummary;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BusinessMembersCacheTest extends TestCase
{
  private string $businessId;

  protected function setUp(): void
  {
    $this->businessId = 'test-members-cache-' . bin2hex(random_bytes(6));
  }

  protected function tearDown(): void
  {
    BusinessMembersCache::invalidate($this->businessId);
  }

  #[Test]
  public function putThenGetRoundTripsSummaries(): void
  {
    $summaries = [
      'member-a' => [
        'ytd_gross' => 1234.56,
        'total_hours' => 100.25,
        'reg_hours' => 90.0,
        'ot_hours' => 10.25,
        'trailing_baseline' => 999.99,
      ],
    ];

    BusinessMembersCache::put($this->businessId, 2026, $summaries);
    $cached = BusinessMembersCache::get($this->businessId, 2026);

    $this->assertNotNull($cached);
    $this->assertSame(1234.56, $cached['member-a']['ytd_gross']);
    $this->assertSame(100.25, $cached['member-a']['total_hours']);
    $this->assertSame(999.99, $cached['member-a']['trailing_baseline']);
  }

  #[Test]
  public function getReturnsNullOnYearMismatch(): void
  {
    BusinessMembersCache::put($this->businessId, 2025, ['member-a' => ['ytd_gross' => 1.0]]);

    $this->assertNull(BusinessMembersCache::get($this->businessId, 2026));
  }

  #[Test]
  public function getReturnsNullWhenMissing(): void
  {
    $this->assertNull(BusinessMembersCache::get($this->businessId, 2026));
  }

  #[Test]
  public function invalidateRemovesCachedPayload(): void
  {
    BusinessMembersCache::put($this->businessId, 2026, ['member-a' => ['ytd_gross' => 1.0]]);
    $this->assertNotNull(BusinessMembersCache::get($this->businessId, 2026));

    BusinessMembersCache::invalidate($this->businessId);

    $this->assertNull(BusinessMembersCache::get($this->businessId, 2026));
  }

  #[Test]
  public function putAppliesTtlSafetyNet(): void
  {
    BusinessMembersCache::put($this->businessId, 2026, ['member-a' => ['ytd_gross' => 1.0]]);

    $ttl = Database::ttl(Keys::businessMembersCache($this->businessId));

    $this->assertGreaterThan(0, $ttl);
    $this->assertLessThanOrEqual(BusinessMembersCache::TTL_SECONDS, $ttl);
  }

  #[Test]
  public function forBusinessMembersServesFromCacheWhenComplete(): void
  {
    $year = (int) date('Y');
    BusinessMembersCache::put($this->businessId, $year, [
      'member-a' => [
        'ytd_gross' => 4242.42,
        'total_hours' => 12.0,
        'reg_hours' => 10.0,
        'ot_hours' => 2.0,
        'trailing_baseline' => 84.0,
      ],
    ]);

    $service = new BusinessMembersFinancialSummary();
    $result = $service->forBusinessMembers($this->businessId, ['member-a']);

    $this->assertSame(4242.42, $result['member-a']['ytd_gross']);
    $this->assertSame(84.0, $result['member-a']['trailing_baseline']);
  }

  #[Test]
  public function forBusinessMembersFreshBypassesCache(): void
  {
    $year = (int) date('Y');
    BusinessMembersCache::put($this->businessId, $year, [
      'member-a' => [
        'ytd_gross' => 4242.42,
        'total_hours' => 12.0,
        'reg_hours' => 10.0,
        'ot_hours' => 2.0,
        'trailing_baseline' => 84.0,
      ],
    ]);

    $service = new BusinessMembersFinancialSummary();
    $result = $service->forBusinessMembers($this->businessId, ['member-a'], null, true);

    // member-a has no real work entries, so a fresh recompute must yield zeros
    // rather than the seeded cache sentinel values.
    $this->assertSame(0.0, $result['member-a']['ytd_gross']);
    $this->assertSame(0.0, $result['member-a']['trailing_baseline']);
  }

  #[Test]
  public function forBusinessMembersRecomputesWhenCacheMissesRequestedMember(): void
  {
    $year = (int) date('Y');
    BusinessMembersCache::put($this->businessId, $year, [
      'member-a' => [
        'ytd_gross' => 4242.42,
        'total_hours' => 12.0,
        'reg_hours' => 10.0,
        'ot_hours' => 2.0,
        'trailing_baseline' => 84.0,
      ],
    ]);

    $service = new BusinessMembersFinancialSummary();
    $result = $service->forBusinessMembers($this->businessId, ['member-a', 'member-new']);

    // A member missing from the cached payload forces a full recompute.
    $this->assertArrayHasKey('member-new', $result);
    $this->assertSame(0.0, $result['member-a']['ytd_gross']);
    $this->assertSame(0.0, $result['member-new']['ytd_gross']);
  }

  #[Test]
  public function membershipMutationsInvalidateThroughSetRelationship(): void
  {
    $source = (string) file_get_contents(
      dirname(__DIR__, 3) . '/src/Domain/BusinessDiscoveryService.php',
    );

    $setRelationshipPos = strpos($source, 'private function setRelationship');
    $this->assertNotFalse($setRelationshipPos);

    $body = substr($source, $setRelationshipPos, 5000);
    $this->assertStringContainsString('BusinessWorkspaceCache::invalidate', $body);
  }
}
