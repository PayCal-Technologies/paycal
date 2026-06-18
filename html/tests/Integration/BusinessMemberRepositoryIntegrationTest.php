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
  private string $memberA;
  private string $memberB;
  private string $memberAEmail;
  private string $memberBEmail;

  protected function setUp(): void
  {
    parent::setUp();

    $suffix = bin2hex(random_bytes(4));
    $this->businessId = 'biz-repo-' . $suffix;
    $this->memberA = 'member-a-' . $suffix;
    $this->memberB = 'member-b-' . $suffix;
    $this->memberAEmail = $this->memberA . '@example.com';
    $this->memberBEmail = $this->memberB . '@example.com';

    $this->seedUser($this->memberA, $this->memberAEmail, 'Alice Active');
    $this->seedUser($this->memberB, $this->memberBEmail, 'Bob Pending');

    Database::sadd(Keys::BUSINESS_MEMBERS . ':' . $this->businessId, $this->memberA);
    Database::sadd(Keys::BUSINESS_RELATIONSHIPS . ':' . $this->businessId, $this->memberA, $this->memberB);
    Database::sadd(Keys::BUSINESS_PENDING . ':' . $this->businessId, $this->memberB);
    Database::sadd(Keys::BUSINESS_USER . ':' . $this->memberA, $this->businessId);
    Database::sadd(Keys::BUSINESS_RELATIONSHIPS_USER . ':' . $this->memberA, $this->businessId);
    Database::sadd(Keys::BUSINESS_RELATIONSHIPS_USER . ':' . $this->memberB, $this->businessId);

    Database::hset(Keys::BUSINESS_RELATIONSHIP . ':' . $this->businessId . ':' . $this->memberA, [
      'user_uuid' => $this->memberA,
      'role' => 'member',
      'status' => 'active',
      'scopes' => 'sites.read',
      'updated_at' => '2026-01-01T00:00:00Z',
    ]);
    Database::hset(Keys::BUSINESS_RELATIONSHIP . ':' . $this->businessId . ':' . $this->memberB, [
      'user_uuid' => $this->memberB,
      'role' => 'coordinator',
      'status' => 'pending',
      'scopes' => 'all',
      'updated_at' => '2026-01-02T00:00:00Z',
    ]);
  }

  protected function tearDown(): void
  {
    Database::unlink(Keys::BUSINESS_MEMBERS . ':' . $this->businessId);
    Database::unlink(Keys::BUSINESS_RELATIONSHIPS . ':' . $this->businessId);
    Database::unlink(Keys::BUSINESS_PENDING . ':' . $this->businessId);
    Database::unlink(Keys::BUSINESS_RELATIONSHIP . ':' . $this->businessId . ':' . $this->memberA);
    Database::unlink(Keys::BUSINESS_RELATIONSHIP . ':' . $this->businessId . ':' . $this->memberB);
    Database::unlink(Keys::BUSINESS_USER . ':' . $this->memberA);
    Database::unlink(Keys::BUSINESS_USER . ':' . $this->memberB);
    Database::unlink(Keys::BUSINESS_RELATIONSHIPS_USER . ':' . $this->memberA);
    Database::unlink(Keys::BUSINESS_RELATIONSHIPS_USER . ':' . $this->memberB);

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

  public function testForBusinessDoesNotTreatPendingRelationshipsAsMembers(): void
  {
    $members = BusinessMemberRepository::forBusiness($this->businessId);

    $this->assertCount(1, $members);
    $this->assertSame($this->memberA, $members[0]['user']->user_uuid);
    $this->assertSame(1, Database::scard(Keys::BUSINESS_MEMBERS . ':' . $this->businessId));
    $this->assertSame(2, Database::scard(Keys::BUSINESS_RELATIONSHIPS . ':' . $this->businessId));
    $this->assertSame(1, Database::scard(Keys::BUSINESS_PENDING . ':' . $this->businessId));
  }

  public function testForUserReturnsAllMemberships(): void
  {
    $memberships = BusinessMemberRepository::forUser($this->memberB);

    $this->assertCount(1, $memberships);
    $this->assertSame($this->businessId, $memberships[0]['org_id']);
    $this->assertSame('coordinator', $memberships[0]['role']);
    $this->assertSame('pending', $memberships[0]['status']);
    $this->assertSame(['all'], $memberships[0]['scopes']);
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
