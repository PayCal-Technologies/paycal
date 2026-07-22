<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Database;
use PayCal\Domain\Config\EncryptionConfig;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\PlaintextWorkEntryCaptureService;
use PayCal\Domain\WorkEntry;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 *
 */
#[Group('unit')]
#[Group('redis-write')]
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
        'l' => 5,
        't' => 1,
        'w' => 25,
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
    $this->assertSame('25.00', $stored['wage']);
    $this->assertSame('200.00', $stored['regular_amount']);
    $this->assertSame('0.00', $stored['overtime_amount']);
    $this->assertSame('25.00', $stored['travel_amount']);
    $this->assertSame('5.00', $stored['living_out_amount']);
    $this->assertSame('230.00', $stored['gross']);
    $this->assertSame('1', $stored['earnings_snapshot_version']);

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

  public function testPlaintextCaptureEncryptsLegacyRowsAndRemovesRawFields(): void
  {
    $suffix = bin2hex(random_bytes(4));
    $userUUID = 'Ucapture' . $suffix;
    $workDate = date('Y-m-d');
    $siteID = 'S' . strtoupper(substr($suffix . 'ABCDEF123', 0, 9));
    $siteKey = D_SITE . ":{$userUUID}:{$siteID}";
    $workKey = D_WORK . ":{$userUUID}:{$workDate}:{$siteID}";

    $this->setupTestUser($userUUID);
    Database::hset($siteKey, [
      'site_name' => 'Capture Site',
      'wage' => '30.00',
      'status' => 'active',
    ]);
    Database::hset($workKey, [
      'date' => $workDate,
      'site_id' => $siteID,
      'site_name' => 'Capture Site',
      'hours' => '8.00',
      'regular_hours' => '8.00',
      'overtime_hours' => '0.00',
      'living_out_allowance' => '10.00',
      'travel_hours' => '1.00',
      'wage' => '30.00',
      'tax' => '12.34',
      'net' => '200.00',
      'other' => '99.99',
    ]);

    try {
      $service = new PlaintextWorkEntryCaptureService();
      $pending = $service->listPending($userUUID, 10);
      $this->assertTrue($pending['success']);
      $this->assertCount(1, $pending['data']['entries']);
      $capture = $pending['data']['entries'][0];

      $blob = base64_encode((string) json_encode([
        'version' => 1,
        'ciphertext' => base64_encode('capture-ciphertext'),
        'nonce' => base64_encode('capture-nonce'),
        'aad' => $siteID,
      ]));
      $finalized = $service->finalize($userUUID, [[
        'key' => $capture['key'],
        'capture_token' => $capture['capture_token'],
        'encrypted_blob' => $blob,
      ]]);

      $this->assertTrue($finalized['success']);
      $this->assertSame(1, $finalized['data']['encrypted']);

      $stored = Database::hgetall($workKey);
      $this->assertSame($blob, $stored['encrypted_blob'] ?? '');
      $this->assertSame('30.00', $stored['wage'] ?? '');
      $this->assertSame('280.00', $stored['gross'] ?? '');
      $this->assertArrayHasKey('plaintext_captured_at', $stored);
      $this->assertArrayNotHasKey('date', $stored);
      $this->assertArrayNotHasKey('site_id', $stored);
      $this->assertArrayNotHasKey('tax', $stored);
      $this->assertArrayNotHasKey('net', $stored);
      $this->assertArrayNotHasKey('other', $stored);

      $after = $service->listPending($userUUID, 10);
      $this->assertSame([], $after['data']['entries']);
    } finally {
      Database::del($workKey);
      Database::del($siteKey);
      Database::del(Keys::USER . ':' . $userUUID);
    }
  }
}
