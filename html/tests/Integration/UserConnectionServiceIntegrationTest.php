<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\UserConnectionService;
use PayCal\Domain\UserRepository;

#[Group('integration')]
#[Group('redis')]
final class UserConnectionServiceIntegrationTest extends TestCase
{
  private UserConnectionService $service;
  private string $ownerUUID;
  private string $ownerEmail;
  private string $targetUUID;
  private string $targetEmail;
  /** @var array<int, string> */
  private array $connectionIds = [];

  protected function setUp(): void
  {
    parent::setUp();

    $suffix = bin2hex(random_bytes(6));
    $this->service = new UserConnectionService();
    $this->ownerUUID = 'conn-owner-' . $suffix;
    $this->ownerEmail = 'conn-owner-' . $suffix . '@example.com';
    $this->targetUUID = 'conn-target-' . $suffix;
    $this->targetEmail = 'conn-target-' . $suffix . '@example.com';

    $this->seedUser($this->ownerUUID, $this->ownerEmail, 'Connection Owner');
    $this->seedUser($this->targetUUID, $this->targetEmail, 'Connection Target');
  }

  protected function tearDown(): void
  {
    foreach ($this->connectionIds as $connectionId) {
      foreach ([
        UserConnectionService::CAPABILITY_CALENDAR_VIEW,
        UserConnectionService::CAPABILITY_CALENDAR_EDIT,
        UserConnectionService::CAPABILITY_EXPORT_RECEIVE,
        UserConnectionService::CAPABILITY_TRUSTED_RECOVERY,
      ] as $capability) {
        Database::unlink(Keys::USER_CONNECTION_GRANT . ':' . $connectionId . ':' . $capability);
      }
      Database::unlink(Keys::USER_CONNECTION_GRANTS . ':' . $connectionId);
      Database::unlink(Keys::USER_CONNECTION . ':' . $connectionId);
    }

    Database::unlink(Keys::USER_CONNECTION_ACTIVE . ':' . $this->ownerUUID . ':' . $this->targetUUID);
    Database::unlink(Keys::USER_CONNECTIONS_OWNER . ':' . $this->ownerUUID);
    Database::unlink(Keys::USER_CONNECTIONS_TARGET . ':' . $this->targetUUID);
    Database::unlink(Keys::USER_CONNECTIONS_PENDING . ':' . $this->targetUUID);
    $this->cleanupUser($this->ownerUUID, $this->ownerEmail);
    $this->cleanupUser($this->targetUUID, $this->targetEmail);

    parent::tearDown();
  }

  public function testApprovedPersonConnectionDoesNotGrantWorkDataByItself(): void
  {
    $requested = $this->service->requestPersonConnection($this->ownerUUID, $this->targetEmail);
    $this->assertTrue($requested['success']);
    $connectionId = (string) ($requested['data']['connection']['connection_id'] ?? '');
    $this->connectionIds[] = $connectionId;

    $approved = $this->service->approvePersonConnection($this->targetUUID, $connectionId);
    $this->assertTrue($approved['success']);

    $this->assertFalse($this->service->canUserViewSharedWorkData($this->ownerUUID, $this->targetUUID));

    $listed = $this->service->listForUser($this->ownerUUID);
    $this->assertTrue($listed['success']);
    $this->assertSame('No access granted', (string) ($listed['data']['connections'][0]['access_summary'] ?? ''));
  }

  public function testCalendarViewGrantControlsSharedWorkDataAccess(): void
  {
    $connectionId = $this->createApprovedConnection();

    $granted = $this->service->grantCapability(
      $this->ownerUUID,
      $connectionId,
      UserConnectionService::CAPABILITY_CALENDAR_VIEW
    );
    $this->assertTrue($granted['success']);
    $this->assertTrue($this->service->canUserViewSharedWorkData($this->ownerUUID, $this->targetUUID));

    $revoked = $this->service->revokeCapability(
      $this->ownerUUID,
      $connectionId,
      UserConnectionService::CAPABILITY_CALENDAR_VIEW
    );
    $this->assertTrue($revoked['success']);
    $this->assertFalse($this->service->canUserViewSharedWorkData($this->ownerUUID, $this->targetUUID));
  }

  public function testTrustedRecoveryGrantIsSeparateFromWorkDataAccess(): void
  {
    $connectionId = $this->createApprovedConnection();

    $granted = $this->service->grantCapability(
      $this->ownerUUID,
      $connectionId,
      UserConnectionService::CAPABILITY_TRUSTED_RECOVERY
    );

    $this->assertTrue($granted['success']);
    $this->assertSame(UserConnectionService::GRANT_PENDING, (string) ($granted['data']['grant']['status'] ?? ''));
    $this->assertFalse($this->service->canUserViewSharedWorkData($this->ownerUUID, $this->targetUUID));
  }

  private function createApprovedConnection(): string
  {
    $requested = $this->service->requestPersonConnection($this->ownerUUID, $this->targetEmail);
    $this->assertTrue($requested['success']);
    $connectionId = (string) ($requested['data']['connection']['connection_id'] ?? '');
    $this->assertNotSame('', $connectionId);
    $this->connectionIds[] = $connectionId;

    $approved = $this->service->approvePersonConnection($this->targetUUID, $connectionId);
    $this->assertTrue($approved['success']);

    return $connectionId;
  }

  private function seedUser(string $userUUID, string $email, string $fullName): void
  {
    UserRepository::setUser(
      $userUUID,
      password_hash('test-password', PASSWORD_DEFAULT),
      $email,
      AuthLevel::USER,
      $fullName,
      '',
      ''
    );
  }

  private function cleanupUser(string $userUUID, string $email): void
  {
    Database::unlink(Keys::USER . ':' . $userUUID);
    Database::unlink(Keys::EMAIL . ':' . $email);
  }
}
