<?php declare(strict_types=1);

use PayCal\Domain\Config\EncryptionConfig;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\UserRepository;
use PayCal\Domain\Work;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../tests/bootstrap.php';

#[Group('integration')]
final class WorkWeeklyRecalcE2ETest extends TestCase
{
  private string $userUUID;
  private string $sessionHash;
  private bool $previousRequired;

  protected function setUp(): void
  {
    parent::setUp();

    $this->userUUID = 'U' . substr(bin2hex(random_bytes(8)), 0, 9);
    $this->sessionHash = bin2hex(random_bytes(16));

    UserRepository::setUser(
      $this->userUUID,
      password_hash('test-password', PASSWORD_DEFAULT),
      'work-weekly-e2e-' . substr($this->userUUID, 1) . '@example.test',
      AuthLevel::USER,
      'Work Weekly E2E User',
      $this->sessionHash,
      '555-222-0000'
    );

    $this->previousRequired = EncryptionConfig::isRequired();
    EncryptionConfig::setRequired(false);
  }

  protected function tearDown(): void
  {
    Database::del(Keys::WORK . ':' . $this->userUUID . ':*');
    Database::del(Keys::SITE . ':' . $this->userUUID . ':*');
    Database::del(Keys::USER . ':' . $this->userUUID);
    Database::del(Keys::SESSION . ':' . $this->sessionHash);

    EncryptionConfig::setRequired($this->previousRequired);

    parent::tearDown();
  }

  public function testSplitDayEntriesAreCappedToEightRegularHours(): void
  {
    $date = '2026-03-20';
    $siteA = 'S111111111';
    $siteB = 'S222222222';

    $this->seedSite($siteA, 'Site A');
    $this->seedSite($siteB, 'Site B');

    $this->seedWorkHours($date, $siteA, 6.0);
    $this->seedWorkHours($date, $siteB, 6.0);

    Work::processWorkWeekContainingDate($this->userUUID, $date);

    $entryA = Database::hgetall(Keys::WORK . ':' . $this->userUUID . ':' . $date . ':' . $siteA);
    $entryB = Database::hgetall(Keys::WORK . ':' . $this->userUUID . ':' . $date . ':' . $siteB);

    $regularTotal = ((float) ($entryA['regular_hours'] ?? 0.0)) + ((float) ($entryB['regular_hours'] ?? 0.0));
    $overtimeTotal = ((float) ($entryA['overtime_hours'] ?? 0.0)) + ((float) ($entryB['overtime_hours'] ?? 0.0));

    $this->assertEqualsWithDelta(8.0, $regularTotal, 0.001);
    $this->assertEqualsWithDelta(4.0, $overtimeTotal, 0.001);
  }

  public function testWeeklyCapStillAppliesAfterDailyCapAcrossSplitDay(): void
  {
    $weekStart = '2026-03-15';
    $siteA = 'S333333333';
    $siteB = 'S444444444';

    $this->seedSite($siteA, 'Site C');
    $this->seedSite($siteB, 'Site D');

    // Seed 36 regular hours before target day in same week.
    $this->seedWorkHours('2026-03-15', $siteA, 8.0);
    $this->seedWorkHours('2026-03-16', $siteA, 8.0);
    $this->seedWorkHours('2026-03-17', $siteA, 8.0);
    $this->seedWorkHours('2026-03-18', $siteA, 8.0);
    $this->seedWorkHours('2026-03-19', $siteA, 4.0);

    // Target day totals 12 hours across two entries.
    $this->seedWorkHours('2026-03-20', $siteA, 6.0);
    $this->seedWorkHours('2026-03-20', $siteB, 6.0);

    Work::processWorkWeek($this->userUUID, $weekStart);

    $entryA = Database::hgetall(Keys::WORK . ':' . $this->userUUID . ':2026-03-20:' . $siteA);
    $entryB = Database::hgetall(Keys::WORK . ':' . $this->userUUID . ':2026-03-20:' . $siteB);

    $regularTotal = ((float) ($entryA['regular_hours'] ?? 0.0)) + ((float) ($entryB['regular_hours'] ?? 0.0));
    $overtimeTotal = ((float) ($entryA['overtime_hours'] ?? 0.0)) + ((float) ($entryB['overtime_hours'] ?? 0.0));

    $this->assertEqualsWithDelta(4.0, $regularTotal, 0.001, 'Weekly remaining regular should be 4.');
    $this->assertEqualsWithDelta(8.0, $overtimeTotal, 0.001, 'Remaining 8 hours should spill to overtime.');
  }

  private function seedSite(string $siteId, string $siteName): void
  {
    Database::hset(Keys::SITE . ':' . $this->userUUID . ':' . $siteId, [
      'site_id' => $siteId,
      'site_name' => $siteName,
      'status' => 'active',
      'wage' => '30.00',
    ]);
  }

  private function seedWorkHours(string $date, string $siteId, float $hours): void
  {
    $workKey = Keys::WORK . ':' . $this->userUUID . ':' . $date . ':' . $siteId;

    Database::hset($workKey, [
      'date' => $date,
      'site_id' => $siteId,
      'hours' => number_format($hours, 2, '.', ''),
      'encrypted_blob' => $this->validBlob($siteId),
    ]);
  }

  private function validBlob(string $siteId): string
  {
    return base64_encode((string) json_encode([
      'version' => 1,
      'ciphertext' => base64_encode('work-weekly-e2e-ciphertext'),
      'nonce' => base64_encode('work-weekly-e2e-nonce'),
      'aad' => $siteId,
    ]));
  }
}
