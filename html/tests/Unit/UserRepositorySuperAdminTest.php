<?php declare(strict_types=1);

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\UserRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('security')]
final class UserRepositorySuperAdminTest extends TestCase
{
  private string $uuidA;
  private string $uuidB;
  private string $emailA;
  private string $emailB;
  /** @var array<int, string> */
  private array $preExistingSuperAdmins = [];

  protected function setUp(): void
  {
    parent::setUp();

    $suffix = bin2hex(random_bytes(6));
    $this->uuidA = 'superadmin-a-' . $suffix;
    $this->uuidB = 'superadmin-b-' . $suffix;
    $this->emailA = 'superadmin-a-' . $suffix . '@example.com';
    $this->emailB = 'superadmin-b-' . $suffix . '@example.com';

    $prefix = Keys::USER . ':';
    foreach (Database::scanKeys($prefix . '*') as $userKey) {
      if (substr_count($userKey, ':') !== 1) {
        continue;
      }

      $level = (string) Database::hget($userKey, 'auth_level');
      if ($level === AuthLevel::SUPERADMIN->value) {
        $this->preExistingSuperAdmins[] = substr($userKey, strlen($prefix));
      }
    }
  }

  protected function tearDown(): void
  {
    Database::unlink(Keys::USER . ':' . $this->uuidA);
    Database::unlink(Keys::USER . ':' . $this->uuidB);

    Database::unlink(Keys::EMAIL . ':' . $this->emailA);
    Database::unlink(Keys::EMAIL . $this->emailA);
    Database::unlink(Keys::EMAIL . ':' . $this->emailB);
    Database::unlink(Keys::EMAIL . $this->emailB);

    foreach ($this->preExistingSuperAdmins as $uuid) {
      Database::hset(Keys::USER . ':' . $uuid, ['auth_level' => AuthLevel::SUPERADMIN->value]);
    }

    parent::tearDown();
  }

  public function testSuperadminRankIsAboveAdmin(): void
  {
    $this->assertTrue(AuthLevel::SUPERADMIN->higherThan(AuthLevel::ADMIN));
    $this->assertTrue(AuthLevel::SUPERADMIN->atLeast(AuthLevel::ADMIN));
    $this->assertTrue(AuthLevel::ADMIN->atLeast(AuthLevel::MANAGER));
  }

  public function testSettingSuperadminDemotesPreviousSuperadmin(): void
  {
    UserRepository::setUser(
      $this->uuidA,
      password_hash('pw-a', PASSWORD_DEFAULT),
      $this->emailA,
      AuthLevel::SUPERADMIN,
      'Super A',
      '',
      ''
    );

    UserRepository::setUser(
      $this->uuidB,
      password_hash('pw-b', PASSWORD_DEFAULT),
      $this->emailB,
      AuthLevel::SUPERADMIN,
      'Super B',
      '',
      ''
    );

    $this->assertSame(AuthLevel::ADMIN->value, (string) Database::hget(Keys::USER . ':' . $this->uuidA, 'auth_level'));
    $this->assertSame(AuthLevel::SUPERADMIN->value, (string) Database::hget(Keys::USER . ':' . $this->uuidB, 'auth_level'));
  }

  public function testGetByUUIDBackfillsVariantFromLegacyThemeMode(): void
  {
    Database::hset(Keys::USER . ':' . $this->uuidA, [
      'user_uuid' => $this->uuidA,
      'email' => $this->emailA,
      'auth_level' => AuthLevel::SUPERADMIN->value,
      'theme' => 'win10',
      'theme_mode' => 'dark',
    ]);

    $user = UserRepository::getByUUID($this->uuidA);

    $this->assertNotNull($user);
    $this->assertSame('win10', $user->theme);
    $this->assertSame('dark', $user->variant);
  }
}
