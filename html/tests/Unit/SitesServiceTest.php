<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\SitesService;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 */
#[CoversClass(SitesService::class)]
#[Group('unit')]
final class SitesServiceTest extends TestCase
{
  private SitesService $service;

  protected function setUp(): void
  {
    $this->service = new SitesService();
  }

  // =====
  // Basic Instantiation Tests
  // =====

  #[Test]
  public function sitesServiceCanBeInstantiated(): void
  {
    $this->assertInstanceOf(SitesService::class, $this->service);
  }

  // =====
  // validateSite Tests
  // =====

  #[Test]
  public function sitesServiceValidateSiteWithValidDataReturnsTrue(): void
  {
    $row = [
        'site_name' => 'Main Office',
        'wage' => '45.50',
    ];

    $result = $this->service->validateSite('S123456789', $row);
    $this->assertTrue($result);
  }

  #[Test]
  public function sitesServiceValidateSiteWithEmptySiteUUIDReturnsFalse(): void
  {
    $row = [
        'site_name' => 'Main Office',
        'wage' => '45.50',
    ];

    $result = $this->service->validateSite('', $row);
    $this->assertFalse($result);
  }

  #[Test]
  public function sitesServiceValidateSiteWithMissingSiteNameReturnsFalse(): void
  {
    $row = [
        'wage' => '45.50',
    ];

    $result = $this->service->validateSite('S123456789', $row);
    $this->assertFalse($result);
  }

  #[Test]
  public function sitesServiceValidateSiteWithEmptySiteNameReturnsFalse(): void
  {
    $row = [
        'site_name' => '',
        'wage' => '45.50',
    ];

    $result = $this->service->validateSite('S123456789', $row);
    $this->assertFalse($result);
  }

  #[Test]
  public function sitesServiceValidateSiteWithMissingWageReturnsFalse(): void
  {
    $row = [
        'site_name' => 'Main Office',
    ];

    $result = $this->service->validateSite('S123456789', $row);
    $this->assertFalse($result);
  }

  #[Test]
  public function sitesServiceValidateSiteWithEmptyWageReturnsFalse(): void
  {
    $row = [
        'site_name' => 'Main Office',
        'wage' => '',
    ];

    $result = $this->service->validateSite('S123456789', $row);
    $this->assertFalse($result);
  }

  #[DataProvider('validWageValues')]
  #[Test]
  public function sitesServiceValidateSiteWithVariousValidWagesReturnsTrue(
    array $row,
    bool $expectedValid
  ): void {
    $result = $this->service->validateSite('S123456789', $row);
    $this->assertEquals($expectedValid, $result);
  }

  /**
   * Data provider for various wage values.
   */
  public static function validWageValues(): array
  {
    return [
        'whole number' => [
            ['site_name' => 'Office', 'wage' => '50'],
            true,
        ],
        'decimal value' => [
            ['site_name' => 'Office', 'wage' => '45.50'],
            true,
        ],
        'small value' => [
            ['site_name' => 'Office', 'wage' => '15.25'],
            true,
        ],
        'large value' => [
            ['site_name' => 'Office', 'wage' => '150.00'],
            true,
        ],
    ];
  }

  // =====
  // normalizeSite Tests
  // =====

  #[Test]
  public function sitesServiceNormalizeSiteWithMinimalDataReturnsNormalizedArray(): void
  {
    $row = [
        'site_name' => 'Test Site',
        'wage' => '50.00',
    ];

    $normalized = $this->service->normalizeSite($row);

    $this->assertIsArray($normalized);
    $this->assertArrayHasKey('site_name', $normalized);
    $this->assertArrayHasKey('wage', $normalized);
    $this->assertArrayHasKey('living_out_allowance', $normalized);
    $this->assertArrayHasKey('travel_hours', $normalized);
    $this->assertArrayHasKey('province', $normalized);
    $this->assertArrayHasKey('status', $normalized);
  }

  #[Test]
  public function sitesServiceNormalizeSitePreservesSiteNameAndWage(): void
  {
    $row = [
        'site_name' => 'Downtown Office',
        'wage' => '52.75',
    ];

    $normalized = $this->service->normalizeSite($row);

    $this->assertEquals('Downtown Office', $normalized['site_name']);
    $this->assertEquals('52.75', $normalized['wage']);
  }

  #[Test]
  public function sitesServiceNormalizeSiteDefaultsStatusToActive(): void
  {
    $row = [
        'site_name' => 'Test',
        'wage' => '50',
    ];

    $normalized = $this->service->normalizeSite($row);

    $this->assertEquals('active', $normalized['status']);
  }

  #[Test]
  public function sitesServiceNormalizeSitePreservesProvidedStatus(): void
  {
    $row = [
        'site_name' => 'Test',
        'wage' => '50',
        'status' => 'inactive',
    ];

    $normalized = $this->service->normalizeSite($row);

    $this->assertEquals('inactive', $normalized['status']);
  }

  #[Test]
  public function sitesServiceNormalizeSiteConvertsAllValuesToStrings(): void
  {
    $row = [
        'site_name' => 'Test Site',
        'wage' => 50,
        'living_out_allowance' => 15.50,
        'travel_hours' => 5,
        'province' => 'ON',
        'status' => 'active',
    ];

    $normalized = $this->service->normalizeSite($row);

    foreach ($normalized as $key => $value) {
      $this->assertIsString($value, "Field '{$key}' should be string, got ".gettype($value));
    }
  }

  #[Test]
  public function sitesServiceNormalizeSiteWithMissingOptionalFieldsSetsEmptyStrings(): void
  {
    $row = [
        'site_name' => 'Test',
        'wage' => '50',
    ];

    $normalized = $this->service->normalizeSite($row);

    $this->assertEmpty($normalized['living_out_allowance']);
    $this->assertEmpty($normalized['travel_hours']);
    $this->assertEmpty($normalized['province']);
  }

  // =====
  // get Tests
  // =====

  #[Test]
  public function sitesServiceGetCalledWithUserUUIDReturnsArray(): void
  {
    $result = $this->service->get('test-user-uuid-001');

    $this->assertIsArray($result);
  }

  // =====
  // create Tests
  // =====

  #[Test]
  public function sitesServiceCreateWithValidDataReturnsStringOrNull(): void
  {
    $result = $this->service->create('test-uuid-001', ['site_name' => 'Test', 'wage' => '50']);

    $this->assertTrue(is_string($result) || is_null($result));
  }

  #[Test]
  public function sitesServiceCreateWithEmptySiteNameReturnsNull(): void
  {
    $result = $this->service->create('test-uuid-001', ['site_name' => '', 'wage' => '50']);

    $this->assertNull($result);
  }

  #[Test]
  public function sitesServiceCreateWithMissingSiteNameReturnsNull(): void
  {
    $result = $this->service->create('test-uuid-001', ['wage' => '50']);

    $this->assertNull($result);
  }

  #[Test]
  public function sitesServiceCreateWithEmptyWageReturnsNull(): void
  {
    $result = $this->service->create('test-uuid-001', ['site_name' => 'Test', 'wage' => '']);

    $this->assertNull($result);
  }

  #[Test]
  public function sitesServiceCreateWithMissingWageReturnsNull(): void
  {
    $result = $this->service->create('test-uuid-001', ['site_name' => 'Test']);

    $this->assertNull($result);
  }

  // =====
  // updateSingle Tests
  // =====

  #[Test]
  public function sitesServiceUpdateSingleWithValidDataReturnsBoolean(): void
  {
    $result = $this->service->updateSingle('test-uuid-001', 'S123456789', [
        'site_name' => 'Updated Site',
        'wage' => '55.00',
    ]);

    $this->assertIsBool($result);
  }

  #[Test]
  public function sitesServiceUpdateSingleWithEmptySiteNameReturnsFalse(): void
  {
    $result = $this->service->updateSingle('test-uuid-001', 'S123456789', [
        'site_name' => '',
        'wage' => '55.00',
    ]);

    $this->assertFalse($result);
  }

  #[Test]
  public function sitesServiceUpdateSingleWithEmptyWageReturnsFalse(): void
  {
    $result = $this->service->updateSingle('test-uuid-001', 'S123456789', [
        'site_name' => 'Test Site',
        'wage' => '',
    ]);

    $this->assertFalse($result);
  }

  // =====
  // update Tests (Bulk Operations)
  // =====

  #[Test]
  public function sitesServiceUpdateWithValidDataReturnsTrue(): void
  {
    $result = $this->service->update('test-uuid-001', []);

    $this->assertTrue($result);
  }

  #[Test]
  public function sitesServiceUpdateWithEmptyDataReturnsTrue(): void
  {
    $result = $this->service->update('test-uuid-001', []);

    $this->assertTrue($result);
  }

  #[Test]
  public function sitesServiceUpdateWithSitesArrayReturnsTrue(): void
  {
    $result = $this->service->update('test-uuid-001', [
        'sites' => [
            'S123456789' => [
                'site_name' => 'Site 1',
                'wage' => '50.00',
            ],
        ],
    ]);

    $this->assertTrue($result);
  }

  // =====
  // delete Tests (Archive)
  // =====

  #[Test]
  public function sitesServiceDeleteReturnsArray(): void
  {
    $result = $this->service->delete('test-uuid-001', 'S123456789');

    $this->assertIsArray($result);
    $this->assertArrayHasKey('success', $result);
    $this->assertArrayHasKey('archived_count', $result);
  }

  #[Test]
  public function sitesServiceDeleteWithValidDataSetsSuccessTrue(): void
  {
    $result = $this->service->delete('test-uuid-001', 'S123456789');

    $this->assertTrue($result['success']);
  }

  #[Test]
  public function sitesServiceDeleteReturnsArchivedCount(): void
  {
    $result = $this->service->delete('test-uuid-001', 'S123456789');

    $this->assertIsInt($result['archived_count']);
    $this->assertGreaterThanOrEqual(0, $result['archived_count']);
  }

  // =====
  // permanentDelete Tests
  // =====

  #[Test]
  public function sitesServicePermanentDeleteReturnsArray(): void
  {
    $result = $this->service->permanentDelete('test-uuid-001', 'S123456789');

    $this->assertIsArray($result);
    $this->assertArrayHasKey('success', $result);
    $this->assertArrayHasKey('deleted_work_count', $result);
  }

  #[Test]
  public function sitesServicePermanentDeleteSetsSuccessTrue(): void
  {
    $result = $this->service->permanentDelete('test-uuid-001', 'S123456789');

    $this->assertTrue($result['success']);
  }

  #[Test]
  public function sitesServicePermanentDeleteReturnsDeletedWorkCount(): void
  {
    $result = $this->service->permanentDelete('test-uuid-001', 'S123456789');

    $this->assertIsInt($result['deleted_work_count']);
    $this->assertGreaterThanOrEqual(0, $result['deleted_work_count']);
  }

  // =====
  // getArchivedWorkSummary Tests
  // =====

  #[Test]
  public function sitesServiceGetArchivedWorkSummaryReturnsArray(): void
  {
    $result = $this->service->getArchivedWorkSummary('test-uuid-001', 'S123456789');

    $this->assertIsArray($result);
  }

  #[Test]
  public function sitesServiceGetArchivedWorkSummaryHasRequiredKeys(): void
  {
    $result = $this->service->getArchivedWorkSummary('test-uuid-001', 'S123456789');

    $this->assertArrayHasKey('count', $result);
    $this->assertArrayHasKey('total_earnings', $result);
    $this->assertArrayHasKey('total_hours', $result);
    $this->assertArrayHasKey('date_range', $result);
    $this->assertArrayHasKey('entries', $result);
  }

  #[Test]
  public function sitesServiceGetArchivedWorkSummaryCountIsInteger(): void
  {
    $result = $this->service->getArchivedWorkSummary('test-uuid-001', 'S123456789');

    $this->assertIsInt($result['count']);
    $this->assertGreaterThanOrEqual(0, $result['count']);
  }

  #[Test]
  public function sitesServiceGetArchivedWorkSummaryTotalsAreNumeric(): void
  {
    $result = $this->service->getArchivedWorkSummary('test-uuid-001', 'S123456789');

    $this->assertIsNumeric($result['total_earnings']);
    $this->assertIsNumeric($result['total_hours']);
  }

  #[Test]
  public function sitesServiceGetArchivedWorkSummaryDateRangeIsArray(): void
  {
    $result = $this->service->getArchivedWorkSummary('test-uuid-001', 'S123456789');

    $this->assertIsArray($result['date_range']);
    $this->assertArrayHasKey('start', $result['date_range']);
    $this->assertArrayHasKey('end', $result['date_range']);
  }

  #[Test]
  public function sitesServiceGetArchivedWorkSummaryEntriesIsArray(): void
  {
    $result = $this->service->getArchivedWorkSummary('test-uuid-001', 'S123456789');

    $this->assertIsArray($result['entries']);
  }

  // =====
  // findOrphanedWork Tests
  // =====

  #[Test]
  public function sitesServiceFindOrphanedWorkReturnsArray(): void
  {
    $result = $this->service->findOrphanedWork('test-uuid-001');

    $this->assertIsArray($result);
  }

  #[Test]
  public function sitesServiceFindOrphanedWorkHasRequiredKeys(): void
  {
    $result = $this->service->findOrphanedWork('test-uuid-001');

    $this->assertArrayHasKey('orphaned_groups', $result);
    $this->assertArrayHasKey('total_count', $result);
  }

  #[Test]
  public function sitesServiceFindOrphanedWorkOrphanedGroupsIsArray(): void
  {
    $result = $this->service->findOrphanedWork('test-uuid-001');

    $this->assertIsArray($result['orphaned_groups']);
  }

  #[Test]
  public function sitesServiceFindOrphanedWorkTotalCountIsInteger(): void
  {
    $result = $this->service->findOrphanedWork('test-uuid-001');

    $this->assertIsInt($result['total_count']);
    $this->assertGreaterThanOrEqual(0, $result['total_count']);
  }

  // =====
  // recoverOrphanedWork Tests
  // =====

  #[Test]
  public function sitesServiceRecoverOrphanedWorkReturnsArray(): void
  {
    $result = $this->service->recoverOrphanedWork('test-uuid-001', 'old-site-id', [
        'site_name' => 'Recovered Site',
        'wage' => '50.00',
    ]);

    $this->assertIsArray($result);
  }

  #[Test]
  public function sitesServiceRecoverOrphanedWorkHasRequiredKeys(): void
  {
    $result = $this->service->recoverOrphanedWork('test-uuid-001', 'old-site-id', [
        'site_name' => 'Recovered Site',
        'wage' => '50.00',
    ]);

    $this->assertArrayHasKey('success', $result);
    $this->assertArrayHasKey('new_site_id', $result);
    $this->assertArrayHasKey('bound_count', $result);
  }

  #[Test]
  public function sitesServiceRecoverOrphanedWorkSetsSuccessTrue(): void
  {
    $result = $this->service->recoverOrphanedWork('test-uuid-001', 'old-site-id', [
        'site_name' => 'Recovered Site',
        'wage' => '50.00',
    ]);

    $this->assertTrue($result['success']);
  }

  #[Test]
  public function sitesServiceRecoverOrphanedWorkNewSiteIdIsString(): void
  {
    $result = $this->service->recoverOrphanedWork('test-uuid-001', 'old-site-id', [
        'site_name' => 'Recovered Site',
        'wage' => '50.00',
    ]);

    $this->assertIsString($result['new_site_id']);
  }

  #[Test]
  public function sitesServiceRecoverOrphanedWorkBoundCountIsInteger(): void
  {
    $result = $this->service->recoverOrphanedWork('test-uuid-001', 'old-site-id', [
        'site_name' => 'Recovered Site',
        'wage' => '50.00',
    ]);

    $this->assertIsInt($result['bound_count']);
    $this->assertGreaterThanOrEqual(0, $result['bound_count']);
  }
}
