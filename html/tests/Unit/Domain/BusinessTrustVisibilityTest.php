<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\Business\BusinessModerationService;
use PayCal\Domain\Business\BusinessNameGuard;
use PayCal\Domain\Business\BusinessSearchIndex;
use PayCal\Domain\Business\BusinessTrustPolicy;
use PayCal\Domain\Business\BusinessVisibilityPolicy;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('redis-write')]
final class BusinessTrustVisibilityTest extends TestCase
{
  private string $ownerUUID;
  private string $businessId;

  protected function setUp(): void
  {
    parent::setUp();

    $suffix = bin2hex(random_bytes(4));
    $this->ownerUUID = 'trust-owner-' . $suffix;
    $this->businessId = 'ORGtrust' . $suffix;

    Database::hset(Keys::USER . ':' . $this->ownerUUID, [
      'user_uuid' => $this->ownerUUID,
      'email' => 'trust-' . $suffix . '@example.com',
      'full_name' => 'Trust Owner',
      'email_verified' => '1',
    ]);

    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID, [
      'tier' => 'business',
      'status' => 'active',
    ]);
  }

  protected function tearDown(): void
  {
    BusinessSearchIndex::remove($this->businessId);
    Database::unlink(Keys::BUSINESS . ':' . $this->businessId);
    Database::unlink(Keys::USER . ':' . $this->ownerUUID);
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID);
    Database::srem(Keys::BUSINESS_MODERATION_QUEUE, $this->businessId);

    parent::tearDown();
  }

  #[Test]
  public function normalBusinessNameAutoApproves(): void
  {
    $result = BusinessNameGuard::evaluate('Acme Plumbing Ltd');

    $this->assertSame(BusinessNameGuard::DECISION_APPROVED, $result['decision']);
    $this->assertLessThanOrEqual(20, $result['score']);
  }

  #[Test]
  public function emptyNameRejected(): void
  {
    $result = BusinessNameGuard::evaluate('   ');

    $this->assertSame(BusinessNameGuard::DECISION_REJECTED, $result['decision']);
  }

  #[Test]
  public function reservedPayCalNameRejected(): void
  {
    $result = BusinessNameGuard::evaluate('PayCal Support');

    $this->assertSame(BusinessNameGuard::DECISION_REJECTED, $result['decision']);
    $this->assertContains('reserved_name', $result['reasons']);
  }

  #[Test]
  public function scriptInputRejected(): void
  {
    $result = BusinessNameGuard::evaluate('<script>alert(1)</script>');

    $this->assertSame(BusinessNameGuard::DECISION_REJECTED, $result['decision']);
  }

  #[Test]
  public function suspiciousNameGoesPending(): void
  {
    $result = BusinessNameGuard::evaluate('Αcme Co +1 (555) 123-4567');

    $this->assertSame(BusinessNameGuard::DECISION_PENDING, $result['decision']);
  }

  #[Test]
  public function privateBusinessNotSearchable(): void
  {
    $this->seedBusiness([
      'name' => 'Hidden Private Org',
      'visibility' => BusinessVisibilityPolicy::VISIBILITY_PRIVATE,
      'moderation_status' => BusinessVisibilityPolicy::MODERATION_APPROVED,
      'paid_status' => BusinessTrustPolicy::PAID_ACTIVE,
      'trust_level' => BusinessTrustPolicy::TRUST_PAID,
      'display_name' => 'Hidden Private Org',
      'search_name' => 'hidden private org',
    ]);

    BusinessSearchIndex::sync($this->businessId);

    $results = BusinessSearchIndex::search('hidden private', 10);

    $this->assertSame([], $results);
  }

  #[Test]
  public function approvedListedBusinessSearchable(): void
  {
    $this->seedBusiness([
      'name' => 'Visible Approved Org',
      'visibility' => BusinessVisibilityPolicy::VISIBILITY_LISTED,
      'moderation_status' => BusinessVisibilityPolicy::MODERATION_APPROVED,
      'paid_status' => BusinessTrustPolicy::PAID_ACTIVE,
      'trust_level' => BusinessTrustPolicy::TRUST_PAID,
      'display_name' => 'Visible Approved Org',
      'search_name' => 'visible approved org',
    ]);

    BusinessSearchIndex::sync($this->businessId);

    $results = BusinessSearchIndex::search('visible approved', 10);

    $this->assertCount(1, $results);
    $this->assertSame('Visible Approved Org', $results[0]['business_name']);
  }

  #[Test]
  public function pendingBusinessNotSearchable(): void
  {
    $this->seedBusiness([
      'name' => 'Pending Review Org',
      'visibility' => BusinessVisibilityPolicy::VISIBILITY_PRIVATE,
      'moderation_status' => BusinessVisibilityPolicy::MODERATION_NEEDS_REVIEW,
      'paid_status' => BusinessTrustPolicy::PAID_ACTIVE,
      'trust_level' => BusinessTrustPolicy::TRUST_PAID,
    ]);

    BusinessSearchIndex::sync($this->businessId);

    $this->assertSame([], BusinessSearchIndex::search('pending review', 10));
  }

  #[Test]
  public function pastDueBusinessNotSearchable(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID, [
      'tier' => 'business',
      'status' => 'past_due',
    ]);

    $this->seedBusiness([
      'name' => 'Past Due Org',
      'visibility' => BusinessVisibilityPolicy::VISIBILITY_LISTED,
      'moderation_status' => BusinessVisibilityPolicy::MODERATION_APPROVED,
      'paid_status' => BusinessTrustPolicy::PAID_PAST_DUE,
      'trust_level' => BusinessTrustPolicy::TRUST_PAID,
      'approved_display_name' => 'Past Due Org',
      'display_name' => 'Past Due Org',
      'search_name' => 'past due org',
    ]);

    BusinessSearchIndex::sync($this->businessId);

    $this->assertSame([], BusinessSearchIndex::search('past due', 10));
  }

  #[Test]
  public function approvedBusinessNotSearchableWhenOwnerIsAdminWithoutBusinessTier(): void
  {
    Database::hset(Keys::USER . ':' . $this->ownerUUID, [
      'user_uuid' => $this->ownerUUID,
      'email' => 'admin-owner@example.com',
      'full_name' => 'Admin Owner',
      'auth_level' => 'admin',
      'email_verified' => '1',
    ]);
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID);

    $this->seedBusiness([
      'name' => 'Admin Owned Org',
      'visibility' => BusinessVisibilityPolicy::VISIBILITY_LISTED,
      'moderation_status' => BusinessVisibilityPolicy::MODERATION_APPROVED,
      'paid_status' => '',
      'trust_level' => BusinessTrustPolicy::TRUST_MANUAL_TRUSTED,
      'display_name' => 'Admin Owned Org',
      'search_name' => 'admin owned org',
    ]);

    BusinessSearchIndex::sync($this->businessId);

    $results = BusinessSearchIndex::search('admin owned', 10, 'other-user-uuid');
    $this->assertCount(0, $results);
  }

  #[Test]
  public function premiumTierAloneDoesNotMakeBusinessSearchable(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID, [
      'tier' => 'premium',
      'status' => 'active',
    ]);

    $this->seedBusiness([
      'name' => 'Premium Only Plumbing Co',
      'visibility' => BusinessVisibilityPolicy::VISIBILITY_LISTED,
      'moderation_status' => BusinessVisibilityPolicy::MODERATION_APPROVED,
      'paid_status' => '',
      'trust_level' => '',
      'display_name' => 'Premium Only Plumbing Co',
      'search_name' => 'premium only plumbing co',
    ]);

    BusinessSearchIndex::sync($this->businessId);

    $results = BusinessSearchIndex::search('premium only', 10, 'other-user-uuid');
    $this->assertCount(0, $results);
  }

  #[Test]
  public function approvedBusinessSearchableWhenPaidStatusResolvedFromOwner(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID, [
      'tier' => 'business',
      'status' => 'active',
    ]);

    $this->seedBusiness([
      'name' => 'Zyxx Premium Plumbing Co',
      'visibility' => BusinessVisibilityPolicy::VISIBILITY_LISTED,
      'moderation_status' => BusinessVisibilityPolicy::MODERATION_APPROVED,
      'paid_status' => '',
      'trust_level' => '',
      'display_name' => 'Zyxx Premium Plumbing Co',
      'search_name' => 'zyxx premium plumbing co',
    ]);

    BusinessSearchIndex::sync($this->businessId);

    $results = BusinessSearchIndex::search('zyxx premium', 10, 'other-user-uuid');
    $this->assertCount(1, $results);
    $this->assertSame('Zyxx Premium Plumbing Co', $results[0]['business_name']);
  }

  #[Test]
  public function adminApprovalMakesBusinessSearchable(): void
  {
    $this->seedBusiness([
      'name' => 'Needs Admin Org',
      'visibility' => BusinessVisibilityPolicy::VISIBILITY_PRIVATE,
      'moderation_status' => BusinessVisibilityPolicy::MODERATION_NEEDS_REVIEW,
      'paid_status' => BusinessTrustPolicy::PAID_ACTIVE,
      'trust_level' => BusinessTrustPolicy::TRUST_PAID,
    ]);

    BusinessModerationService::enqueue($this->businessId);
    $this->assertTrue(BusinessModerationService::approveListing($this->businessId, 'admin-test'));

    $results = BusinessSearchIndex::search('needs admin', 10);
    $this->assertCount(1, $results);
  }

  #[Test]
  public function adminSuspensionRemovesFromSearch(): void
  {
    $this->seedBusiness([
      'name' => 'Suspend Me Org',
      'visibility' => BusinessVisibilityPolicy::VISIBILITY_LISTED,
      'moderation_status' => BusinessVisibilityPolicy::MODERATION_APPROVED,
      'paid_status' => BusinessTrustPolicy::PAID_ACTIVE,
      'trust_level' => BusinessTrustPolicy::TRUST_PAID,
      'display_name' => 'Suspend Me Org',
      'search_name' => 'suspend me org',
    ]);
    BusinessSearchIndex::sync($this->businessId);
    $this->assertCount(1, BusinessSearchIndex::search('suspend me', 10));

    BusinessModerationService::suspendBusiness($this->businessId, 'admin-test', 'abuse');

    $this->assertSame([], BusinessSearchIndex::search('suspend me', 10));
  }

  #[Test]
  public function renameTriggersReReviewForSuspiciousName(): void
  {
    $this->seedBusiness([
      'name' => 'Original Name Org',
      'visibility' => BusinessVisibilityPolicy::VISIBILITY_LISTED,
      'moderation_status' => BusinessVisibilityPolicy::MODERATION_APPROVED,
      'paid_status' => BusinessTrustPolicy::PAID_ACTIVE,
      'trust_level' => BusinessTrustPolicy::TRUST_PAID,
      'approved_display_name' => 'Original Name Org',
      'display_name' => 'Original Name Org',
      'search_name' => 'original name org',
      'owner_uuid' => $this->ownerUUID,
    ]);

    $rename = BusinessModerationService::handleRename($this->businessId, $this->ownerUUID, 'Αcme Co +1 (555) 123-4567');
    $this->assertTrue($rename['success']);
    $this->assertSame(BusinessVisibilityPolicy::VISIBILITY_LISTED, $rename['fields']['visibility']);
    $this->assertSame(BusinessVisibilityPolicy::MODERATION_NEEDS_REVIEW, $rename['fields']['moderation_status']);
    $this->assertSame('Original Name Org', $rename['fields']['display_name']);
    $this->assertNotSame('', trim((string) ($rename['fields']['pending_display_name'] ?? '')));
    $this->assertContains($this->businessId, Database::smembers(Keys::BUSINESS_MODERATION_QUEUE));

    Database::hset(Keys::BUSINESS . ':' . $this->businessId, $rename['fields']);
    BusinessSearchIndex::sync($this->businessId);
    $results = BusinessSearchIndex::search('original name', 10);
    $this->assertCount(1, $results);
    $this->assertSame('Original Name Org', $results[0]['business_name']);
  }

  #[Test]
  public function renameAutoApprovesCleanName(): void
  {
    $this->seedBusiness([
      'name' => 'Original Name Org',
      'visibility' => BusinessVisibilityPolicy::VISIBILITY_LISTED,
      'moderation_status' => BusinessVisibilityPolicy::MODERATION_APPROVED,
      'paid_status' => BusinessTrustPolicy::PAID_ACTIVE,
      'trust_level' => BusinessTrustPolicy::TRUST_PAID,
      'approved_display_name' => 'Original Name Org',
      'display_name' => 'Original Name Org',
      'search_name' => 'original name org',
      'owner_uuid' => $this->ownerUUID,
    ]);

    $rename = BusinessModerationService::handleRename($this->businessId, $this->ownerUUID, 'Acme Plumbing Ltd');
    $this->assertTrue($rename['success']);
    $this->assertSame(BusinessVisibilityPolicy::VISIBILITY_LISTED, $rename['fields']['visibility']);
    $this->assertSame(BusinessVisibilityPolicy::MODERATION_APPROVED, $rename['fields']['moderation_status']);
    $this->assertSame('Acme Plumbing Ltd', $rename['fields']['approved_display_name']);

    Database::hset(Keys::BUSINESS . ':' . $this->businessId, $rename['fields']);
    BusinessSearchIndex::sync($this->businessId);
    $results = BusinessSearchIndex::search('acme plumbing', 10);
    $this->assertCount(1, $results);
    $this->assertSame('Acme Plumbing Ltd', $results[0]['business_name']);
  }

  #[Test]
  public function renameCooldownIsPerBusiness(): void
  {
    $this->seedBusiness([
      'name' => 'Cooldown Test Org',
      'owner_uuid' => $this->ownerUUID,
      'trust_level' => BusinessTrustPolicy::TRUST_NEW,
      'last_public_rename_submitted_at' => date('c', time() - 2),
    ]);

    $cooldown = BusinessModerationService::evaluateRenameCooldown(
      Database::hgetall(Keys::BUSINESS . ':' . $this->businessId),
    );

    $this->assertFalse($cooldown['allowed']);
    $this->assertGreaterThan(0, (int) $cooldown['remaining_seconds']);

    $message = BusinessModerationService::formatRenameCooldownMessage($cooldown);
    $this->assertStringContainsString('Try again in', $message);
    $this->assertStringContainsString('Each business', $message);
  }

  #[Test]
  public function listingHiddenWhenSponsorLosesBusinessTier(): void
  {
    $sponsorUUID = 'sponsor-' . bin2hex(random_bytes(3));
    Database::hset(Keys::USER . ':' . $sponsorUUID, [
      'user_uuid' => $sponsorUUID,
      'email' => 'sponsor@example.com',
      'email_verified' => '1',
    ]);
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $sponsorUUID, [
      'tier' => 'free',
      'status' => 'active',
    ]);

    $this->seedBusiness([
      'name' => 'Sponsored Org',
      'owner_uuid' => $this->ownerUUID,
      'listing_sponsor_uuid' => $sponsorUUID,
      'visibility' => BusinessVisibilityPolicy::VISIBILITY_LISTED,
      'moderation_status' => BusinessVisibilityPolicy::MODERATION_APPROVED,
      'approved_display_name' => 'Sponsored Org',
      'display_name' => 'Sponsored Org',
      'search_name' => 'sponsored org',
      'paid_status' => '',
    ]);

    BusinessSearchIndex::sync($this->businessId);
    $this->assertSame([], BusinessSearchIndex::search('sponsored', 10));

    Database::unlink(Keys::USER . ':' . $sponsorUUID);
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $sponsorUUID);
  }

  /** @param array<string, string> $fields */
  private function seedBusiness(array $fields): void
  {
    if (!isset($fields['listing_sponsor_uuid'])) {
      $fields['listing_sponsor_uuid'] = $this->ownerUUID;
    }

    if (!isset($fields['approved_display_name']) && isset($fields['display_name'])) {
      $fields['approved_display_name'] = $fields['display_name'];
    }

    Database::hset(Keys::BUSINESS . ':' . $this->businessId, [
      'business_id' => $this->businessId,
      'owner_uuid' => $this->ownerUUID,
      'business_type' => 'shared',
      'status' => 'active',
      'created_at' => date('c'),
    ] + $fields);
  }
}
