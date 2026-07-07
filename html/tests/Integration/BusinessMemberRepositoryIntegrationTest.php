<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Domain\BusinessMemberRepository;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\UserRepository;
use PHPUnit\Framework\TestCase;

final class BusinessMemberRepositoryIntegrationTest extends TestCase
{
  private string $businessId;
  private string $secondBusinessId;
  private string $ownedBusinessId;
  private string $memberA;
  private string $memberB;
  private string $memberAEmail;
  private string $memberBEmail;

  protected function setUp(): void
  {
    parent::setUp();

    $suffix = bin2hex(random_bytes(4));
    $this->businessId = 'biz-repo-' . $suffix;
    $this->secondBusinessId = 'biz-repo-secondary-' . $suffix;
    $this->ownedBusinessId = 'biz-repo-owned-' . $suffix;
    $this->memberA = 'member-a-' . $suffix;
    $this->memberB = 'member-b-' . $suffix;
    $this->memberAEmail = $this->memberA . '@example.com';
    $this->memberBEmail = $this->memberB . '@example.com';

    $this->seedUser($this->memberA, $this->memberAEmail, 'Alice Active');
    $this->seedUser($this->memberB, $this->memberBEmail, 'Bob Pending');

    Database::sadd(Keys::BUSINESS_MEMBERS . ':' . $this->businessId, $this->memberA);
    Database::sadd(Keys::BUSINESS_CONNECTIONS . ':' . $this->businessId, $this->memberA, $this->memberB);
    Database::sadd(Keys::BUSINESS_PENDING . ':' . $this->businessId, $this->memberB);
    Database::sadd(Keys::BUSINESS_USER . ':' . $this->memberA, $this->businessId);
    Database::sadd(Keys::BUSINESS_CONNECTIONS_USER . ':' . $this->memberA, $this->businessId);
    Database::sadd(Keys::BUSINESS_CONNECTIONS_USER . ':' . $this->memberB, $this->businessId);
    Database::sadd(Keys::BUSINESS_CONNECTIONS . ':' . $this->secondBusinessId, $this->memberB);
    Database::sadd(Keys::BUSINESS_MEMBERS . ':' . $this->secondBusinessId, $this->memberB);
    Database::sadd(Keys::BUSINESS_USER . ':' . $this->memberB, $this->secondBusinessId);
    Database::sadd(Keys::BUSINESS_CONNECTIONS_USER . ':' . $this->memberB, $this->secondBusinessId);
    Database::sadd(Keys::BUSINESS_OWNER . ':' . $this->memberA, $this->ownedBusinessId);

    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->memberA, [
      'user_uuid' => $this->memberA,
      'role' => 'member',
      'status' => 'active',
      'scopes' => 'sites.read',
      'updated_at' => '2026-01-01T00:00:00Z',
    ]);
    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->memberB, [
      'user_uuid' => $this->memberB,
      'role' => 'coordinator',
      'status' => 'pending',
      'scopes' => 'all',
      'updated_at' => '2026-01-02T00:00:00Z',
    ]);
    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->secondBusinessId . ':' . $this->memberB, [
      'user_uuid' => $this->memberB,
      'role' => 'member',
      'status' => 'active',
      'scopes' => 'work.read',
      'updated_at' => '2026-01-03T00:00:00Z',
    ]);
    Database::hset(Keys::BUSINESS . ':' . $this->ownedBusinessId, [
      'business_id' => $this->ownedBusinessId,
      'name' => 'Owned Fallback Business',
      'owner_uuid' => $this->memberA,
      'business_type' => 'shared',
      'status' => 'active',
      'created_at' => '2026-01-04T00:00:00Z',
    ]);
  }

  protected function tearDown(): void
  {
    Database::unlink(Keys::BUSINESS_MEMBERS . ':' . $this->businessId);
    Database::unlink(Keys::BUSINESS_MEMBERS . ':' . $this->secondBusinessId);
    Database::unlink(Keys::BUSINESS_CONNECTIONS . ':' . $this->businessId);
    Database::unlink(Keys::BUSINESS_CONNECTIONS . ':' . $this->secondBusinessId);
    Database::unlink(Keys::BUSINESS_PENDING . ':' . $this->businessId);
    Database::unlink(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->memberA);
    Database::unlink(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->memberB);
    Database::unlink(Keys::BUSINESS_CONNECTION . ':' . $this->secondBusinessId . ':' . $this->memberB);
    Database::unlink(Keys::BUSINESS . ':' . $this->ownedBusinessId);
    Database::unlink(Keys::BUSINESS_USER . ':' . $this->memberA);
    Database::unlink(Keys::BUSINESS_USER . ':' . $this->memberB);
    Database::unlink(Keys::BUSINESS_CONNECTIONS_USER . ':' . $this->memberA);
    Database::unlink(Keys::BUSINESS_CONNECTIONS_USER . ':' . $this->memberB);
    Database::unlink(Keys::BUSINESS_OWNER . ':' . $this->memberA);

    foreach ([$this->memberA => $this->memberAEmail, $this->memberB => $this->memberBEmail] as $uuid => $email) {
      Database::unlink(Keys::USER . ':' . $uuid);
      Database::unlink(Keys::EMAIL . ':' . $email);
      Database::unlink(Keys::EMAIL . $email);
    }

    parent::tearDown();
  }

  public function testForBusinessReturnsActiveMembersSortedByName(): void
  {
    $members = BusinessMemberRepository::forBusiness($this->businessId, null, 'active');

    $this->assertCount(1, $members);
    $this->assertSame($this->memberA, $members[0]['user']->user_uuid);
    $this->assertSame('member', $members[0]['role']);
    $this->assertSame(['sites.read'], $members[0]['scopes']);
  }

  public function testForBusinessDoesNotTreatPendingConnectionsAsMembers(): void
  {
    $members = BusinessMemberRepository::forBusiness($this->businessId);

    $this->assertCount(1, $members);
    $this->assertSame($this->memberA, $members[0]['user']->user_uuid);
    $this->assertSame(1, Database::scard(Keys::BUSINESS_MEMBERS . ':' . $this->businessId));
    $this->assertSame(2, Database::scard(Keys::BUSINESS_CONNECTIONS . ':' . $this->businessId));
    $this->assertSame(1, Database::scard(Keys::BUSINESS_PENDING . ':' . $this->businessId));
  }

  public function testForUserReturnsAllMemberships(): void
  {
    $memberships = BusinessMemberRepository::forUser($this->memberB);

    $this->assertCount(2, $memberships);
    $membershipsByOrg = [];
    foreach ($memberships as $membership) {
      $membershipsByOrg[(string) $membership['org_id']] = $membership;
    }

    $this->assertArrayHasKey($this->businessId, $membershipsByOrg);
    $this->assertArrayHasKey($this->secondBusinessId, $membershipsByOrg);
    $this->assertSame('coordinator', $membershipsByOrg[$this->businessId]['role']);
    $this->assertSame('pending', $membershipsByOrg[$this->businessId]['status']);
    $this->assertSame(['all'], $membershipsByOrg[$this->businessId]['scopes']);
    $this->assertSame('member', $membershipsByOrg[$this->secondBusinessId]['role']);
    $this->assertSame('active', $membershipsByOrg[$this->secondBusinessId]['status']);
    $this->assertSame(['work.read'], $membershipsByOrg[$this->secondBusinessId]['scopes']);
  }

  public function testForUserReturnsOwnedBusinessWhenConnectionMirrorIsMissing(): void
  {
    $memberships = BusinessMemberRepository::forUser($this->memberA);

    $membershipsByOrg = [];
    foreach ($memberships as $membership) {
      $membershipsByOrg[(string) $membership['org_id']] = $membership;
    }

    $this->assertArrayHasKey($this->ownedBusinessId, $membershipsByOrg);
    $this->assertSame('owner', $membershipsByOrg[$this->ownedBusinessId]['role']);
    $this->assertSame('active', $membershipsByOrg[$this->ownedBusinessId]['status']);
    $this->assertSame(['all'], $membershipsByOrg[$this->ownedBusinessId]['scopes']);
  }

  private function seedUser(string $userUUID, string $email, string $fullName): void
  {
    Database::hset(Keys::USER . ':' . $userUUID, [
      'user_uuid' => $userUUID,
      'email' => $email,
      'full_name' => $fullName,
      'email_verified' => '1',
      'auth_level' => AuthLevel::USER->value,
    ]);

    UserRepository::setUserEmail($userUUID, $email);
  }
}
