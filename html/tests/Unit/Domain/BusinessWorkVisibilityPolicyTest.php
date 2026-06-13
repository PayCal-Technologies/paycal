<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\BusinessDiscoveryService;
use PayCal\Domain\BusinessWorkVisibilityPolicy;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class BusinessWorkVisibilityPolicyTest extends TestCase
{
  private const BUSINESS_ID = 'biz-policy-test';
  private const OWNER_UUID = 'owner-policy-test';
  private const MEMBER_UUID = 'member-policy-test';

  #[Test]
  public function canAggregateForOrgRefusesRevokedRelationship(): void
  {
    $relationship = [
      'status' => 'revoked',
      'role' => 'contributor',
      'scopes' => 'work.read,work.scope.org',
    ];

    $this->assertFalse(BusinessWorkVisibilityPolicy::canAggregateForOrg(
      self::BUSINESS_ID,
      self::MEMBER_UUID,
      $relationship,
      $this->orgSiteIndex(),
    ));

    $decision = BusinessWorkVisibilityPolicy::evaluateOrgAggregation(
      self::BUSINESS_ID,
      self::MEMBER_UUID,
      $relationship,
      $this->orgSiteIndex(),
    );

    $this->assertSame(BusinessWorkVisibilityPolicy::REFUSAL_PAYROLL_VISIBILITY, $decision['reason']);
  }

  #[Test]
  public function evaluateWorkEntryRefusesPersonalLinkedSite(): void
  {
    $orgSiteIndex = [];
    $relationship = $this->payrollVisibleRelationship();

    $decision = BusinessWorkVisibilityPolicy::evaluateWorkEntry(
      self::BUSINESS_ID,
      self::OWNER_UUID,
      self::MEMBER_UUID,
      'work:' . self::MEMBER_UUID . ':2026-01-10:site-personal',
      ['regular_hours' => '8', 'gross' => '100'],
      $relationship,
      $orgSiteIndex,
    );

    $this->assertFalse($decision['allowed']);
    $this->assertSame(BusinessWorkVisibilityPolicy::REFUSAL_IMPORTED_PERSONAL, $decision['reason']);
  }

  #[Test]
  public function evaluateWorkEntryRefusesSharedSite(): void
  {
    $relationship = $this->payrollVisibleRelationship();
    $orgSiteIndex = [
      'site-shared' => [
        'site_owner_uuid' => 'other-member',
        'site_id' => 'site-shared',
        'site_hash' => [
          'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_SHARED,
          'business_id' => self::BUSINESS_ID,
        ],
      ],
    ];

    $decision = BusinessWorkVisibilityPolicy::evaluateWorkEntry(
      self::BUSINESS_ID,
      self::OWNER_UUID,
      self::MEMBER_UUID,
      'work:' . self::MEMBER_UUID . ':2026-01-10:site-shared',
      ['regular_hours' => '8', 'gross' => '100'],
      $relationship,
      $orgSiteIndex,
    );

    $this->assertFalse($decision['allowed']);
    $this->assertSame(BusinessWorkVisibilityPolicy::REFUSAL_SHARED_SITE, $decision['reason']);
  }

  #[Test]
  public function siteIsOrgOwnedOrgOnlyRefusesMissingOwnerMetadata(): void
  {
    $allowed = BusinessWorkVisibilityPolicy::siteIsOrgOwnedOrgOnly(
      self::BUSINESS_ID,
      self::OWNER_UUID,
      self::MEMBER_UUID,
      [],
    );

    $this->assertFalse($allowed);
  }

  #[Test]
  public function siteIsOrgOwnedOrgOnlyRefusesLinkedOrgSite(): void
  {
    $allowed = BusinessWorkVisibilityPolicy::siteIsOrgOwnedOrgOnly(
      self::BUSINESS_ID,
      self::OWNER_UUID,
      self::MEMBER_UUID,
      [
        'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_LINKED,
        'business_id' => self::BUSINESS_ID,
      ],
    );

    $this->assertFalse($allowed);
  }

  #[Test]
  public function evaluateWorkEntryAllowsOrgOwnedOrgOnlySite(): void
  {
    $relationship = $this->payrollVisibleRelationship();
    $orgSiteIndex = [
      'site-org' => [
        'site_owner_uuid' => self::OWNER_UUID,
        'site_id' => 'site-org',
        'site_hash' => [
          'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_BUSINESS,
          'business_id' => self::BUSINESS_ID,
          'business_managed' => '1',
        ],
      ],
    ];

    $decision = BusinessWorkVisibilityPolicy::evaluateWorkEntry(
      self::BUSINESS_ID,
      self::OWNER_UUID,
      self::MEMBER_UUID,
      'work:' . self::MEMBER_UUID . ':2026-01-10:site-org',
      ['regular_hours' => '8', 'gross' => '100'],
      $relationship,
      $orgSiteIndex,
    );

    $this->assertTrue($decision['allowed']);
    $this->assertSame('', $decision['reason']);
  }

  /**
   * @return array<string, string>
   */
  private function payrollVisibleRelationship(): array
  {
    return [
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
      'role' => 'contributor',
      'scopes' => 'work.read,work.scope.org',
    ];
  }

  /**
   * @return array<string, array{site_owner_uuid: string, site_id: string, site_hash: array<string, string>}>
   */
  private function orgSiteIndex(): array
  {
    return [
      'site-org' => [
        'site_owner_uuid' => self::OWNER_UUID,
        'site_id' => 'site-org',
        'site_hash' => [
          'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_BUSINESS,
          'business_id' => self::BUSINESS_ID,
          'business_managed' => '1',
        ],
      ],
    ];
  }
}
