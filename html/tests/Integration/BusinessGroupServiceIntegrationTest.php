<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Domain\BusinessGroupService;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
final class BusinessGroupServiceIntegrationTest extends TestCase
{
  private BusinessGroupService $service;
  private string $businessId;
  private string $ownerUUID;
  private string $coordinatorUUID;
  private string $memberUUID;

  protected function setUp(): void
  {
    parent::setUp();

    $suffix = bin2hex(random_bytes(6));
    $this->service = new BusinessGroupService();
    $this->businessId = 'ORG-group-service-' . $suffix;
    $this->ownerUUID = 'group-owner-' . $suffix;
    $this->coordinatorUUID = 'group-coordinator-' . $suffix;
    $this->memberUUID = 'group-member-' . $suffix;

    Database::hset(Keys::BUSINESS . ':' . $this->businessId, [
      'business_id' => $this->businessId,
      'name' => 'Group Service Test Org',
      'owner_uuid' => $this->ownerUUID,
      'status' => 'active',
    ]);
    $this->seedConnection($this->coordinatorUUID, 'coordinator');
    $this->seedConnection($this->memberUUID, 'member');
  }

  protected function tearDown(): void
  {
    foreach (Database::smembers(Keys::businessGroups($this->businessId)) as $groupIdRaw) {
      $groupId = (string) $groupIdRaw;
      Database::unlink(Keys::businessGroupMembers($this->businessId, $groupId));
      Database::unlink(Keys::businessGroup($this->businessId, $groupId));
    }
    Database::unlink(Keys::businessGroups($this->businessId));

    foreach (Database::scanKeys(Keys::BUSINESS_MEMBER_GROUPS . ':' . $this->businessId . ':*') as $memberGroupsKey) {
      Database::unlink((string) $memberGroupsKey);
    }

    $auditSetKey = Keys::BUSINESS_AUDIT . ':' . $this->businessId;
    foreach (Database::smembers($auditSetKey) as $eventId) {
      Database::unlink(Keys::BUSINESS_AUDIT_EVENT . ':' . (string) $eventId);
    }
    Database::unlink($auditSetKey);

    Database::unlink(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->coordinatorUUID);
    Database::unlink(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->memberUUID);
    Database::unlink(Keys::BUSINESS . ':' . $this->businessId);

    parent::tearDown();
  }

  public function testListGroupsRequiresOwnerOrCoordinatorAccess(): void
  {
    $created = $this->service->saveGroup($this->coordinatorUUID, $this->businessId, [
      'name' => 'Field Leads',
      'description' => '',
    ]);
    $this->assertTrue($created['success'], (string) ($created['message'] ?? ''));

    $ownerList = $this->service->listGroups($this->ownerUUID, $this->businessId);
    $this->assertTrue($ownerList['success'], (string) ($ownerList['message'] ?? ''));

    $coordinatorList = $this->service->listGroups($this->coordinatorUUID, $this->businessId);
    $this->assertTrue($coordinatorList['success'], (string) ($coordinatorList['message'] ?? ''));

    $memberList = $this->service->listGroups($this->memberUUID, $this->businessId);
    $this->assertFalse($memberList['success'], 'Active members without coordinator access must not read payroll group aggregates.');
  }

  public function testSaveGroupKeepsTypeAndStatusServerOwned(): void
  {
    $created = $this->service->saveGroup($this->coordinatorUUID, $this->businessId, [
      'name' => 'Pipeline Crew',
      'description' => 'Created through a tampered request.',
      'status' => 'archived',
      'type' => 'smart',
    ]);
    $this->assertTrue($created['success'], (string) ($created['message'] ?? ''));

    $groupId = (string) ($created['data']['group']['group_id'] ?? '');
    $this->assertNotSame('', $groupId);

    $group = Database::hgetall(Keys::businessGroup($this->businessId, $groupId));
    $this->assertSame('active', (string) ($group['status'] ?? ''));
    $this->assertSame('manual', (string) ($group['type'] ?? ''));

    $updated = $this->service->saveGroup($this->coordinatorUUID, $this->businessId, [
      'group_id' => $groupId,
      'name' => 'Pipeline Leads',
      'description' => 'Updated through a tampered request.',
      'status' => 'archived',
      'type' => 'smart',
    ]);
    $this->assertTrue($updated['success'], (string) ($updated['message'] ?? ''));

    $group = Database::hgetall(Keys::businessGroup($this->businessId, $groupId));
    $this->assertSame('active', (string) ($group['status'] ?? ''));
    $this->assertSame('manual', (string) ($group['type'] ?? ''));

    $archived = $this->service->archiveGroup($this->coordinatorUUID, $this->businessId, $groupId);
    $this->assertTrue($archived['success'], (string) ($archived['message'] ?? ''));

    $updatedArchived = $this->service->saveGroup($this->coordinatorUUID, $this->businessId, [
      'group_id' => $groupId,
      'name' => 'Pipeline Leads Archived',
      'description' => 'Lifecycle remains archived.',
      'status' => 'active',
      'type' => 'smart',
    ]);
    $this->assertTrue($updatedArchived['success'], (string) ($updatedArchived['message'] ?? ''));

    $group = Database::hgetall(Keys::businessGroup($this->businessId, $groupId));
    $this->assertSame('archived', (string) ($group['status'] ?? ''));
    $this->assertSame('manual', (string) ($group['type'] ?? ''));
  }

  public function testArchiveAndRestoreAreIdempotent(): void
  {
    $groupId = $this->createGroup('Night Shift');
    $groupKey = Keys::businessGroup($this->businessId, $groupId);

    $archived = $this->service->archiveGroup($this->coordinatorUUID, $this->businessId, $groupId);
    $this->assertTrue($archived['success'], (string) ($archived['message'] ?? ''));

    Database::hset($groupKey, ['updated_at' => 'sentinel-archived']);
    $archivedAgain = $this->service->archiveGroup($this->coordinatorUUID, $this->businessId, $groupId);
    $this->assertTrue($archivedAgain['success'], (string) ($archivedAgain['message'] ?? ''));
    $group = Database::hgetall($groupKey);
    $this->assertSame('archived', (string) ($group['status'] ?? ''));
    $this->assertSame('sentinel-archived', (string) ($group['updated_at'] ?? ''));

    $restored = $this->service->restoreGroup($this->coordinatorUUID, $this->businessId, $groupId);
    $this->assertTrue($restored['success'], (string) ($restored['message'] ?? ''));

    Database::hset($groupKey, ['updated_at' => 'sentinel-active']);
    $restoredAgain = $this->service->restoreGroup($this->coordinatorUUID, $this->businessId, $groupId);
    $this->assertTrue($restoredAgain['success'], (string) ($restoredAgain['message'] ?? ''));
    $group = Database::hgetall($groupKey);
    $this->assertSame('active', (string) ($group['status'] ?? ''));
    $this->assertSame('sentinel-active', (string) ($group['updated_at'] ?? ''));
  }

  public function testSaveGroupEnforcesServerSideLengths(): void
  {
    $longName = $this->service->saveGroup($this->coordinatorUUID, $this->businessId, [
      'name' => str_repeat('A', 81),
      'description' => '',
    ]);
    $this->assertFalse($longName['success']);

    $longDescription = $this->service->saveGroup($this->coordinatorUUID, $this->businessId, [
      'name' => 'Valid group',
      'description' => str_repeat('B', 301),
    ]);
    $this->assertFalse($longDescription['success']);
  }

  private function createGroup(string $name): string
  {
    $created = $this->service->saveGroup($this->coordinatorUUID, $this->businessId, [
      'name' => $name,
      'description' => '',
    ]);
    $this->assertTrue($created['success'], (string) ($created['message'] ?? ''));

    $groupId = (string) ($created['data']['group']['group_id'] ?? '');
    $this->assertNotSame('', $groupId);

    return $groupId;
  }

  private function seedConnection(string $memberUUID, string $role): void
  {
    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $memberUUID, [
      'business_id' => $this->businessId,
      'member_uuid' => $memberUUID,
      'status' => 'active',
      'role' => $role,
      'scopes' => $role === 'coordinator' ? 'all' : 'sites.read,work.read,work.scope.self',
    ]);
  }
}
