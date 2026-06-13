<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\BusinessDiscoveryService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class BusinessSiteOwnershipTest extends TestCase
{
  #[Test]
  public function resolveOwnershipUsesWorkspaceBusinessScopeForBusinessManagedSites(): void
  {
    $service = new BusinessDiscoveryService();

    $scope = $service->resolveBusinessSiteOwnership(
      'actor-uuid',
      'actor-uuid',
      'owner-uuid',
      [
        'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_BUSINESS,
      ],
    );

    $this->assertSame(BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_BUSINESS, $scope);
  }

  #[Test]
  public function resolveOwnershipMarksLinkedPersonalSitesSeparatelyFromBusinessManagedSites(): void
  {
    $service = new BusinessDiscoveryService();

    $scope = $service->resolveBusinessSiteOwnership(
      'actor-uuid',
      'actor-uuid',
      'owner-uuid',
      [
        'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_LINKED,
      ],
    );

    $this->assertSame(BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_LINKED, $scope);
  }

  #[Test]
  public function resolveOwnershipTreatsOtherMemberSitesAsShared(): void
  {
    $service = new BusinessDiscoveryService();

    $scope = $service->resolveBusinessSiteOwnership(
      'actor-uuid',
      'member-uuid',
      'owner-uuid',
      [],
    );

    $this->assertSame(BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_SHARED, $scope);
  }

  #[Test]
  public function resolveOwnershipFallsBackToLegacyBusinessManagedFlag(): void
  {
    $service = new BusinessDiscoveryService();

    $scope = $service->resolveBusinessSiteOwnership(
      'actor-uuid',
      'actor-uuid',
      'owner-uuid',
      [
        'business_managed' => '1',
      ],
    );

    $this->assertSame(BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_BUSINESS, $scope);
  }

  #[Test]
  public function isBusinessManagedSiteDetectsOwnershipScopeAndLegacyFlag(): void
  {
    $this->assertTrue(BusinessDiscoveryService::isBusinessManagedSite([
      'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_BUSINESS,
    ]));
    $this->assertTrue(BusinessDiscoveryService::isBusinessManagedSite([
      'business_managed' => '1',
    ]));
    $this->assertFalse(BusinessDiscoveryService::isBusinessManagedSite([
      'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_LINKED,
    ]));
    $this->assertFalse(BusinessDiscoveryService::isBusinessManagedSite([]));
  }
}
