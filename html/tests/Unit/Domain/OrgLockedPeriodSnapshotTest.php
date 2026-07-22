<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\BusinessDiscoveryService;
use PayCal\Domain\BusinessWorkVisibilityPolicy;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\OrgLockedPeriodMetrics;
use PayCal\Domain\OrgLockedPeriodSnapshot;
use PayCal\Domain\WorkEntryLockService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('redis-write')]
final class OrgLockedPeriodSnapshotTest extends TestCase
{
  private string $businessId;
  private string $ownerUuid;
  private string $memberUuid;
  private int $year = 2026;

  protected function setUp(): void
  {
    parent::setUp();

    $suffix = bin2hex(random_bytes(4));
    $this->businessId = 'biz-lock-' . $suffix;
    $this->ownerUuid = 'owner-' . $suffix;
    $this->memberUuid = 'member-' . $suffix;

    Database::hset(Keys::BUSINESS . ':' . $this->businessId, [
      'business_id' => $this->businessId,
      'owner_uuid' => $this->ownerUuid,
      'name' => 'Policy Test Org',
    ]);
  }

  protected function tearDown(): void
  {
    Database::unlink(Keys::BUSINESS . ':' . $this->businessId);
    Database::unlink(Keys::businessLockedPeriodMetrics($this->businessId, $this->year));
    Database::unlink(Keys::BUSINESS_SITE . ':' . $this->businessId);
    Database::unlink(Keys::SITE . ':' . $this->ownerUuid . ':site-org');
    Database::unlink(Keys::SITE . ':' . $this->memberUuid . ':site-personal');
    Database::unlink(Keys::SITE . ':' . $this->memberUuid . ':site-linked');
    Database::unlink(Keys::SITE . ':' . 'other-' . $this->memberUuid . ':site-shared');
    Database::unlink('work:' . $this->memberUuid . ':2026-01-10:site-org');
    Database::unlink('work:' . $this->memberUuid . ':2026-01-10:site-personal');
    Database::unlink('work:' . $this->memberUuid . ':2026-01-10:site-linked');
    Database::unlink('work:' . $this->memberUuid . ':2026-01-10:site-shared');
    WorkEntryLockService::clearCache($this->memberUuid);

    parent::tearDown();
  }

  #[Test]
  public function refusesSnapshotForUserOwnedPersonalSites(): void
  {
    $this->seedPersonalSite();
    $this->seedLockBoundary('2026-06-01');
    $connection = $this->payrollVisibleConnection();
    $workEntries = [
      'work:' . $this->memberUuid . ':2026-01-10:site-personal' => [
        'regular_hours' => '8',
        'overtime_hours' => '0',
        'gross' => '200',
      ],
    ];
    $orgSiteIndex = BusinessWorkVisibilityPolicy::buildOrgSiteIndex($this->businessId);

    $this->assertFalse(BusinessWorkVisibilityPolicy::canAggregateForOrg(
      $this->businessId,
      $this->memberUuid,
      $connection,
      $orgSiteIndex,
    ));

    OrgLockedPeriodSnapshot::ensureSnapshotForMember(
      $this->businessId,
      $this->ownerUuid,
      $this->memberUuid,
      $this->year,
      $workEntries,
      $connection,
      $orgSiteIndex,
    );

    $this->assertFalse(OrgLockedPeriodMetrics::has($this->businessId, $this->memberUuid, $this->year));
  }

  #[Test]
  public function refusesSnapshotForSharedPersonalSites(): void
  {
    $this->seedSharedSite();
    $this->seedLockBoundary('2026-06-01');
    $connection = $this->payrollVisibleConnection();
    $workEntries = [
      'work:' . $this->memberUuid . ':2026-01-10:site-shared' => [
        'regular_hours' => '8',
        'overtime_hours' => '0',
        'gross' => '200',
      ],
    ];
    $orgSiteIndex = BusinessWorkVisibilityPolicy::buildOrgSiteIndex($this->businessId);

    $this->assertFalse(BusinessWorkVisibilityPolicy::canAggregateForOrg(
      $this->businessId,
      $this->memberUuid,
      $connection,
      $orgSiteIndex,
    ));

    OrgLockedPeriodSnapshot::ensureSnapshotForMember(
      $this->businessId,
      $this->ownerUuid,
      $this->memberUuid,
      $this->year,
      $workEntries,
      $connection,
      $orgSiteIndex,
    );

    $this->assertFalse(OrgLockedPeriodMetrics::has($this->businessId, $this->memberUuid, $this->year));
  }

  #[Test]
  public function refusesSnapshotWhenOwnerMetadataMissing(): void
  {
    $this->seedSiteMissingOwnerMetadata();
    $this->seedLockBoundary('2026-06-01');
    $connection = $this->payrollVisibleConnection();
    $workEntries = [
      'work:' . $this->memberUuid . ':2026-01-10:site-org' => [
        'regular_hours' => '8',
        'overtime_hours' => '0',
        'gross' => '200',
      ],
    ];
    $orgSiteIndex = BusinessWorkVisibilityPolicy::buildOrgSiteIndex($this->businessId);

    $this->assertFalse(BusinessWorkVisibilityPolicy::canAggregateForOrg(
      $this->businessId,
      $this->memberUuid,
      $connection,
      $orgSiteIndex,
    ));

    OrgLockedPeriodSnapshot::ensureSnapshotForMember(
      $this->businessId,
      $this->ownerUuid,
      $this->memberUuid,
      $this->year,
      $workEntries,
      $connection,
      $orgSiteIndex,
    );

    $this->assertFalse(OrgLockedPeriodMetrics::has($this->businessId, $this->memberUuid, $this->year));
  }

  #[Test]
  public function refusesSnapshotForOrgLinkedButNotOrgOnlySites(): void
  {
    $this->seedLinkedOrgSite();
    $this->seedLockBoundary('2026-06-01');
    $connection = $this->payrollVisibleConnection();
    $workEntries = [
      'work:' . $this->memberUuid . ':2026-01-10:site-linked' => [
        'regular_hours' => '8',
        'overtime_hours' => '0',
        'gross' => '200',
      ],
    ];
    $orgSiteIndex = BusinessWorkVisibilityPolicy::buildOrgSiteIndex($this->businessId);

    $this->assertFalse(BusinessWorkVisibilityPolicy::canAggregateForOrg(
      $this->businessId,
      $this->memberUuid,
      $connection,
      $orgSiteIndex,
    ));

    OrgLockedPeriodSnapshot::ensureSnapshotForMember(
      $this->businessId,
      $this->ownerUuid,
      $this->memberUuid,
      $this->year,
      $workEntries,
      $connection,
      $orgSiteIndex,
    );

    $this->assertFalse(OrgLockedPeriodMetrics::has($this->businessId, $this->memberUuid, $this->year));
  }

  #[Test]
  public function refusesSnapshotForRevokedConnections(): void
  {
    $this->seedOrgOwnedSite();
    $this->seedLockBoundary('2026-06-01');
    $connection = [
      'status' => 'revoked',
      'role' => 'contributor',
      'scopes' => 'work.read,work.scope.business',
    ];
    $workEntries = [
      'work:' . $this->memberUuid . ':2026-01-10:site-org' => [
        'regular_hours' => '8',
        'overtime_hours' => '0',
        'gross' => '200',
      ],
    ];
    $orgSiteIndex = BusinessWorkVisibilityPolicy::buildOrgSiteIndex($this->businessId);

    $this->assertFalse(BusinessWorkVisibilityPolicy::canAggregateForOrg(
      $this->businessId,
      $this->memberUuid,
      $connection,
      $orgSiteIndex,
    ));

    OrgLockedPeriodSnapshot::ensureSnapshotForMember(
      $this->businessId,
      $this->ownerUuid,
      $this->memberUuid,
      $this->year,
      $workEntries,
      $connection,
      $orgSiteIndex,
    );

    $this->assertFalse(OrgLockedPeriodMetrics::has($this->businessId, $this->memberUuid, $this->year));
  }

  #[Test]
  public function writesSnapshotForOrgOwnedOrgOnlyLockedWork(): void
  {
    $this->seedOrgOwnedSite();
    $this->seedLockBoundary('2026-06-01');
    $connection = $this->payrollVisibleConnection();
    $workEntries = [
      'work:' . $this->memberUuid . ':2026-01-10:site-org' => [
        'regular_hours' => '8',
        'overtime_hours' => '2',
        'gross' => '500',
      ],
    ];
    $orgSiteIndex = BusinessWorkVisibilityPolicy::buildOrgSiteIndex($this->businessId);

    $this->assertTrue(BusinessWorkVisibilityPolicy::canAggregateForOrg(
      $this->businessId,
      $this->memberUuid,
      $connection,
      $orgSiteIndex,
    ));

    OrgLockedPeriodSnapshot::ensureSnapshotForMember(
      $this->businessId,
      $this->ownerUuid,
      $this->memberUuid,
      $this->year,
      $workEntries,
      $connection,
      $orgSiteIndex,
    );

    $snapshot = OrgLockedPeriodMetrics::get($this->businessId, $this->memberUuid, $this->year);
    $this->assertNotNull($snapshot);
    $this->assertSame(500.0, $snapshot['ytd_gross']);
    $this->assertSame(10.0, $snapshot['total_hours']);
  }

  private function seedLockBoundary(string $boundaryDate): void
  {
    $today = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d');
    Database::set(Keys::LOCK_BOUNDARY . ':' . $this->memberUuid . ':' . $today, $boundaryDate, 3600);
  }

  /**
   * @return array<string, string>
   */
  private function payrollVisibleConnection(): array
  {
    return [
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
      'role' => 'contributor',
      'scopes' => 'work.read,work.scope.business',
    ];
  }

  private function seedOrgOwnedSite(): void
  {
    Database::sadd(Keys::BUSINESS_SITE . ':' . $this->businessId, $this->ownerUuid . ':site-org');
    Database::hset(Keys::SITE . ':' . $this->ownerUuid . ':site-org', [
      'site_id' => 'site-org',
      'site_name' => 'Org Yard',
      'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_BUSINESS,
      'business_id' => $this->businessId,
      'business_managed' => '1',
    ]);
  }

  private function seedPersonalSite(): void
  {
    Database::sadd(Keys::BUSINESS_SITE . ':' . $this->businessId, $this->memberUuid . ':site-personal');
    Database::hset(Keys::SITE . ':' . $this->memberUuid . ':site-personal', [
      'site_id' => 'site-personal',
      'site_name' => 'Personal Yard',
      'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_LINKED,
      'business_id' => $this->businessId,
    ]);
  }

  private function seedSharedSite(): void
  {
    $otherOwner = 'other-' . $this->memberUuid;
    Database::sadd(Keys::BUSINESS_SITE . ':' . $this->businessId, $otherOwner . ':site-shared');
    Database::hset(Keys::SITE . ':' . $otherOwner . ':site-shared', [
      'site_id' => 'site-shared',
      'site_name' => 'Shared Yard',
      'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_SHARED,
      'business_id' => $this->businessId,
    ]);
  }

  private function seedLinkedOrgSite(): void
  {
    Database::sadd(Keys::BUSINESS_SITE . ':' . $this->businessId, $this->memberUuid . ':site-linked');
    Database::hset(Keys::SITE . ':' . $this->memberUuid . ':site-linked', [
      'site_id' => 'site-linked',
      'site_name' => 'Linked Yard',
      'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_LINKED,
      'business_id' => $this->businessId,
    ]);
  }

  private function seedSiteMissingOwnerMetadata(): void
  {
    Database::sadd(Keys::BUSINESS_SITE . ':' . $this->businessId, $this->ownerUuid . ':site-org');
    Database::hset(Keys::SITE . ':' . $this->ownerUuid . ':site-org', [
      'site_id' => 'site-org',
      'site_name' => 'Unowned Yard',
    ]);
  }
}
