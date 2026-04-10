<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Database;
use PayCal\Domain\Config\EncryptionConfig;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\WorkEntry;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 *
 */
#[Group('unit')]
final class WorkEntryEncryptionTest extends TestCase
{
  /**
   * Helper to set up test user
   */
  private function setupTestUser(string $userUUID): void
  {
    $userKey = Keys::USER . ':' . $userUUID;
    Database::hset($userKey, [
      'email' => $userUUID . '@test.local',
      'name' => 'Test User',
      'pay_period_type' => 'bi-weekly',
      'pay_period_start_day' => '1',
      'pay_period_start_month' => '0',
      'editing_grace_days' => '3',
    ]);
  }

  public function testUpdateWorkEntryStoresEncryptedBlob(): void
  {
    // Arrange
    $userUUID = 'Utestuser01';
    $workDate = date('Y-m-d'); // Use today's date to avoid lock issues
    $siteID = 'Sabcdef123';

    // Set up user (required for lock service)
    $this->setupTestUser($userUUID);

    // Create the site first (required by WorkEntry::updateWorkEntry)
    $siteKey = D_SITE.":{$userUUID}:{$siteID}";
    Database::hset($siteKey, [
        'site_name' => 'Test Site',
        'wage' => '25.00',
        'status' => 'active',
    ]);

    $envelope = json_encode([
        'version' => 1,
        'ciphertext' => base64_encode('cipherbytes'),
        'nonce' => base64_encode('somenonce'),
        'aad' => $siteID,
    ]);
    $workDetails = [
        'd' => $workDate,
        's' => $siteID,
        'h' => 8,
        'l' => 0,
        't' => 0,
        'encrypted_blob' => base64_encode($envelope),
    ];
    $workEntryKey = D_WORK.":{$userUUID}:{$workDate}:{$siteID}";

    // Clean up any existing entry
    Database::del($workEntryKey);

    // Act
    $result = WorkEntry::updateWorkEntry($workDetails, $userUUID);

    // Assert
    $this->assertTrue($result);
    $stored = Database::hgetall($workEntryKey);
    $this->assertArrayHasKey('encrypted_blob', $stored);
    $this->assertSame($workDetails['encrypted_blob'], $stored['encrypted_blob']);

    // Clean up
    Database::del($workEntryKey);
    Database::del($siteKey);
    Database::del(Keys::USER . ':' . $userUUID);
  }

  public function testUpdateWorkEntryWithoutEncryptedBlob(): void
  {
    $userUUID = 'Utestuser02';
    $workDate = date('Y-m-d'); // Use today's date to avoid lock issues
    $siteID = 'Sabcdef124';

    // Set up user (required for lock service)
    $this->setupTestUser($userUUID);

    // Create the site first (required by WorkEntry::updateWorkEntry)
    $siteKey = D_SITE.":{$userUUID}:{$siteID}";
    Database::hset($siteKey, [
        'site_name' => 'Test Site 2',
        'wage' => '25.00',
        'status' => 'active',
    ]);

    $workDetails = [
        'd' => $workDate,
        's' => $siteID,
        'h' => 7,
        'l' => 0,
        't' => 0,
    ];
    $workEntryKey = D_WORK.":{$userUUID}:{$workDate}:{$siteID}";
    Database::del($workEntryKey);
    $result = WorkEntry::updateWorkEntry($workDetails, $userUUID);
    $this->assertFalse($result);
    $stored = Database::hgetall($workEntryKey);
    $this->assertArrayNotHasKey('encrypted_blob', $stored);
    Database::del($workEntryKey);
    Database::del($siteKey);
    Database::del(Keys::USER . ':' . $userUUID);
  }

  public function testUpdateWorkEntryRejectsInvalidBlob(): void
  {
    $userUUID = 'Utestuser03';
    $workDate = date('Y-m-d'); // Use today's date to avoid lock issues
    $siteID = 'Sabcdef125';

    // Set up user (required for lock service)
    $this->setupTestUser($userUUID);

    // Create the site first (required by WorkEntry::updateWorkEntry)
    $siteKey = D_SITE.":{$userUUID}:{$siteID}";
    Database::hset($siteKey, [
        'site_name' => 'Test Site 3',
        'wage' => '25.00',
        'status' => 'active',
    ]);

    $workDetails = [
        'd' => $workDate,
        's' => $siteID,
        'h' => 6,
        'l' => 0,
        't' => 0,
        'encrypted_blob' => 'not-base64@@@',
    ];
    $workEntryKey = D_WORK.":{$userUUID}:{$workDate}:{$siteID}";
    Database::del($workEntryKey);

    $result = WorkEntry::updateWorkEntry($workDetails, $userUUID);
    $this->assertFalse($result);
    $stored = Database::hgetall($workEntryKey);
    $this->assertArrayNotHasKey('encrypted_blob', $stored);
    Database::del($workEntryKey);
    Database::del($siteKey);
  }

  public function testGetWorkEntryRejectsPlaintextWhenCryptoRequired(): void
  {
    $userUUID = 'Utestuser04';
    $workDate = date('Y-m-d');
    $siteID = 'Sabcdef126';
    $siteKey = D_SITE.":{$userUUID}:{$siteID}";
    $workKey = D_WORK.":{$userUUID}:{$workDate}:{$siteID}";

    $this->setupTestUser($userUUID);
    Database::hset($siteKey, [
        'site_name' => 'Required Mode Site',
        'wage' => '25.00',
        'status' => 'active',
    ]);
    Database::hset($workKey, [
        'site_id' => $siteID,
        'site_name' => 'Required Mode Site',
        'hours' => '8.00',
        'date' => $workDate,
    ]);

    EncryptionConfig::setRequired(true);
    try {
      $result = WorkEntry::getWorkEntry($workKey);
      $this->assertNull($result);
    } finally {
      EncryptionConfig::setRequired(false);
      Database::del($workKey);
      Database::del($siteKey);
      Database::del(Keys::USER . ':' . $userUUID);
    }
  }
}
