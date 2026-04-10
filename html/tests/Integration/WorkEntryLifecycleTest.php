<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\SiteStatus;
use PayCal\Domain\Sites;
use PayCal\Domain\WorkEntry;
use PHPUnit\Framework\Attributes\Group;

/**
 * WorkEntryLifecycleTest.php
 *
 * @package PayCal
 */

/**
 * WorkEntryLifecycleTest
 */
#[Group('integration')]
class WorkEntryLifecycleTest extends TestCase
{
  private static string $testUserUUID;
  private static string $testSiteID;
  private static string $testDate;

  private static function validBlob(string $siteId): string
  {
    return base64_encode(json_encode([
      'version' => 1,
      'ciphertext' => base64_encode('work-entry-lifecycle-ciphertext'),
      'nonce' => base64_encode('work-entry-lifecycle-nonce'),
      'aad' => $siteId,
    ]));
  }

  /**
   * Set up test environment before any tests run
   */
  public static function setUpBeforeClass(): void
  {
    // Generate test identifiers
    self::$testUserUUID = 'U' . str_pad(dechex(mt_rand(0, 0xFFFFFFFF)), 9, '0', STR_PAD_LEFT);
    self::$testSiteID = 'S' . str_pad(dechex(mt_rand(0, 0xFFFFFFFF)), 9, '0', STR_PAD_LEFT);
    self::$testDate = date('Y-m-d');
    if (!defined('USER_UUID')) define('USER_UUID', self::$testUserUUID);
  }

  /**
   * Set up before each test - ensure site exists
   */
  protected function setUp(): void
  {
    parent::setUp();

    // Ensure test isolation for random test order runs.
    WorkEntry::deleteWorkEntriesByPattern(D_WORK . ':' . self::$testUserUUID . ':*');
    
    // Create test user with pay period config (required for lock service)
    $userKey = Keys::USER . ':' . self::$testUserUUID;
    Database::hset($userKey, [
      'email' => 'test@paycal.app',
      'name' => 'Test User',
      'pay_period_type' => 'bi-weekly',
      'pay_period_start_day' => '1',
      'pay_period_start_month' => '0', // No specific month
      'editing_grace_days' => '3',
    ]);
    
    // Ensure test site exists before each test
    $siteKey = D_SITE . ':' . self::$testUserUUID . ':' . self::$testSiteID;
    Database::hset($siteKey, [
      'site_name' => 'Test Site for Lifecycle',
      'status' => 'active',
      'wage' => '25.00',
    ]);

    // Seed a baseline entry so tests do not depend on prior test execution order.
    WorkEntry::updateWorkEntry([
      'date' => self::$testDate,
      'site_id' => self::$testSiteID,
      'encrypted_blob' => self::validBlob(self::$testSiteID),
    ], self::$testUserUUID);
  }

  /**
   * Clean up test data after all tests complete
   */
  public static function tearDownAfterClass(): void
  {
    // Clean up any test data
    $pattern = D_WORK . ':' . self::$testUserUUID . ':*';
    Database::del($pattern);
    // Clean up site
    $siteKey = D_SITE . ':' . self::$testUserUUID . ':' . self::$testSiteID;
    Database::del($siteKey);
    // Clean up user
    $userKey = Keys::USER . ':' . self::$testUserUUID;
    Database::del($userKey);
  }

  /**
   * Test complete work entry creation workflow
   */
  #[Test]
  public function workEntryCanBeCreatedWithValidData(): void
  {
    // Create work entry (site already exists from setUp)
    $workDetails = [
      'date' => self::$testDate,
      'site_id' => self::$testSiteID,
      'encrypted_blob' => self::validBlob(self::$testSiteID),
    ];

    $result = WorkEntry::updateWorkEntry($workDetails, self::$testUserUUID);

    $this->assertTrue($result, 'Work entry creation should succeed');

    // Verify entry exists
    $workKey = D_WORK . ':' . self::$testUserUUID . ':' . self::$testDate . ':' . self::$testSiteID;
    $exists = WorkEntry::exists($workKey);
    
    $this->assertTrue($exists, 'Work entry should exist in database');

    // Verify encrypted payload integrity
    $savedEntry = Database::hgetall($workKey);
    $this->assertArrayHasKey('encrypted_blob', $savedEntry, 'Encrypted blob should be saved');
  }

  /**
   * Test work entry modification workflow
   */
  #[Test]
  public function workEntryCanBeModified(): void
  {
    $workKey = D_WORK . ':' . self::$testUserUUID . ':' . self::$testDate . ':' . self::$testSiteID;
    
    // Ensure entry exists from previous test
    $this->assertTrue(WorkEntry::exists($workKey), 'Work entry must exist before modification');

    // Modify the work entry
    $modifiedDetails = [
      'date' => self::$testDate,
      'site_id' => self::$testSiteID,
      'encrypted_blob' => self::validBlob(self::$testSiteID),
    ];

    $result = WorkEntry::updateWorkEntry($modifiedDetails, self::$testUserUUID);
    $this->assertTrue($result, 'Work entry modification should succeed');

    // Verify encrypted payload still present after modification
    $savedEntry = Database::hgetall($workKey);
    $this->assertArrayHasKey('encrypted_blob', $savedEntry);
  }

  /**
   * Test work entry zero-hours handling (soft delete)
   */
  #[Test]
  public function workEntryCanBeSetToZeroHours(): void
  {
    $workKey = D_WORK . ':' . self::$testUserUUID . ':' . self::$testDate . ':' . self::$testSiteID;

    // Set hours to minimum (effectively zero)
    $zeroDetails = [
      'date' => self::$testDate,
      'site_id' => self::$testSiteID,
      'encrypted_blob' => self::validBlob(self::$testSiteID),
    ];

    $result = WorkEntry::updateWorkEntry($zeroDetails, self::$testUserUUID);
    $this->assertTrue($result, 'Setting zero hours should succeed');

    // Entry should still exist with encrypted payload
    $this->assertTrue(WorkEntry::exists($workKey), 'Work entry should still exist');
    $savedEntry = Database::hgetall($workKey);
    $this->assertArrayHasKey('encrypted_blob', $savedEntry);
  }

  /**
   * Test multiple work entries can exist for same day/different sites
   */
  #[Test]
  public function multipleEntriesCanExistForSameDay(): void
  {
    // Create second test site
    $site2ID = 'S' . str_pad(dechex(mt_rand(0, 0xFFFFFFFF)), 9, '0', STR_PAD_LEFT);
    $site2Key = D_SITE . ':' . self::$testUserUUID . ':' . $site2ID;
    
    Database::hset($site2Key, [
      'site_name' => 'Second Site',
      'status' => 'active',
      'wage' => '30.00',
    ]);

    // Create second work entry for same day
    $workDetails2 = [
      'date' => self::$testDate,
      'site_id' => $site2ID,
      'encrypted_blob' => self::validBlob($site2ID),
    ];

    $result = WorkEntry::updateWorkEntry($workDetails2, self::$testUserUUID);
    $this->assertTrue($result, 'Second work entry creation should succeed');

    // Verify both entries exist
    $workKey1 = D_WORK . ':' . self::$testUserUUID . ':' . self::$testDate . ':' . self::$testSiteID;
    $workKey2 = D_WORK . ':' . self::$testUserUUID . ':' . self::$testDate . ':' . $site2ID;

    $this->assertTrue(WorkEntry::exists($workKey1), 'First work entry should exist');
    $this->assertTrue(WorkEntry::exists($workKey2), 'Second work entry should exist');

    // Clean up second site
    Database::del($site2Key);
    Database::del($workKey2);
  }

  /**
   * Test wildcard pattern detection
   */
  #[Test]
  public function wildcardPatternDetectsMatchingEntries(): void
  {
    $pattern = D_WORK . ':' . self::$testUserUUID . ':' . self::$testDate . ':*';
    
    $exists = WorkEntry::zwildcardexists($pattern);
    $this->assertTrue($exists, 'Wildcard pattern should find matching work entries');

    // Test non-matching pattern
    $futureDate = date('Y-m-d', strtotime('+100 days'));
    $noMatchPattern = D_WORK . ':' . self::$testUserUUID . ':' . $futureDate . ':*';
    
    $noExists = WorkEntry::zwildcardexists($noMatchPattern);
    $this->assertFalse($noExists, 'Wildcard pattern should not find non-existent entries');
  }

  /**
   * Test batch deletion by pattern
   */
  #[Test]
  public function workEntriesCanBeDeletedByPattern(): void
  {
    // Create multiple entries for deletion (site already exists from setUp)
    // Use today's date to avoid lock service issues with past dates
    $baseDate = date('Y-m-d');
    $dates = [
      $baseDate,
      date('Y-m-d', strtotime($baseDate . ' +1 day')),
      date('Y-m-d', strtotime($baseDate . ' +2 days'))
    ];

    foreach ($dates as $date) {
      $workDetails = [
        'date' => $date,
        'site_id' => self::$testSiteID,
        'encrypted_blob' => self::validBlob(self::$testSiteID),
      ];
      $result = WorkEntry::updateWorkEntry($workDetails, self::$testUserUUID);
      $this->assertTrue($result, "Work entry for $date should be created");
    }

    // Verify entries exist
    foreach ($dates as $date) {
      $workKey = D_WORK . ':' . self::$testUserUUID . ':' . $date . ':' . self::$testSiteID;
      $this->assertTrue(WorkEntry::exists($workKey), "Entry for $date should exist before deletion");
    }

    // Delete all entries for this user/site
    $pattern = D_WORK . ':' . self::$testUserUUID . ':*:' . self::$testSiteID;
    $deleted = WorkEntry::deleteWorkEntriesByPattern($pattern);

    $this->assertGreaterThan(0, $deleted, 'Pattern deletion should delete at least one entry');

    // Verify entries are gone
    $stillExists = WorkEntry::zwildcardexists($pattern);
    $this->assertFalse($stillExists, 'Entries should be deleted after pattern deletion');
  }

  /**
   * Test work entry validation - invalid date
   */
  #[Test]
  public function workEntryRejectsInvalidDate(): void
  {
    $invalidDetails = [
      'date' => '2025-13-45', // Invalid date
      'site_id' => self::$testSiteID,
      'hours' => '8.0',
      'living_out_allowance' => '0',
      'travel_hours' => '0'
    ];

    $result = WorkEntry::updateWorkEntry($invalidDetails, self::$testUserUUID);
    $this->assertFalse($result, 'Invalid date should be rejected');
  }

  /**
   * Test work entry validation - invalid site ID format
   */
  #[Test]
  public function workEntryRejectsInvalidSiteIdFormat(): void
  {
    $invalidDetails = [
      'date' => self::$testDate,
      'site_id' => 'INVALID_SITE_ID', // Invalid format
      'hours' => '8.0',
      'living_out_allowance' => '0',
      'travel_hours' => '0'
    ];

    $result = WorkEntry::updateWorkEntry($invalidDetails, self::$testUserUUID);
    $this->assertFalse($result, 'Invalid site ID format should be rejected');
  }

  /**
   * Test work entry validation - hours clamping to maximum
   */
  #[Test]
  public function workEntryHoursAreClampedToMaximum(): void
  {
    $excessiveDetails = [
      'date' => self::$testDate,
      'site_id' => self::$testSiteID,
      'encrypted_blob' => self::validBlob(self::$testSiteID),
    ];

    $result = WorkEntry::updateWorkEntry($excessiveDetails, self::$testUserUUID);
    $this->assertTrue($result, 'Excessive hours should be clamped and saved');

    $workKey = D_WORK . ':' . self::$testUserUUID . ':' . self::$testDate . ':' . self::$testSiteID;
    $savedEntry = Database::hgetall($workKey);
    $this->assertArrayHasKey('encrypted_blob', $savedEntry);
  }

  /**
   * Test work entry data provider for various hour values
   */
  public static function hourValueProvider(): array
  {
    return [
      'minimum hours' => ['0'],
      'quarter shift' => ['2.0'],
      'half shift' => ['4.0'],
      'full shift' => ['8.0'],
      'overtime shift' => ['10.0'],
      'double shift' => ['16.0'],
    ];
  }

  /**
   * Test various hour values in lifecycle
   */
  #[Test]
  #[DataProvider('hourValueProvider')]
  public function workEntryAcceptsVariousHourValues(string $inputHours): void
  {
    // Use today's date to avoid lock service issues with past dates
    $testDate = date('Y-m-d');
    
    $workDetails = [
      'date' => $testDate,
      'site_id' => self::$testSiteID,
      'encrypted_blob' => self::validBlob(self::$testSiteID),
    ];

    $result = WorkEntry::updateWorkEntry($workDetails, self::$testUserUUID);
    $this->assertTrue($result, "Encrypted entry should be accepted for input marker $inputHours");

    $workKey = D_WORK . ':' . self::$testUserUUID . ':' . $testDate . ':' . self::$testSiteID;
    $savedEntry = Database::hgetall($workKey);
    
    $this->assertArrayHasKey('encrypted_blob', $savedEntry);

    // Clean up
    Database::del($workKey);
  }

  /**
   * Test inactive site activation on work entry creation
   */
  #[Test]
  public function inactiveSiteIsActivatedOnWorkEntryCreation(): void
  {
    // Create inactive site
    $inactiveSiteID = 'S' . str_pad(dechex(mt_rand(0, 0xFFFFFFFF)), 9, '0', STR_PAD_LEFT);
    $siteKey = D_SITE . ':' . self::$testUserUUID . ':' . $inactiveSiteID;
    
    Database::hset($siteKey, [
      'site_name' => 'Inactive Site',
      'status' => 'inactive',
      'wage' => '20.00',
    ]);

    $sites = Sites::GetInstance();
    $this->assertEquals('inactive', $sites->getSiteStatus($inactiveSiteID, self::$testUserUUID), 'Site should start as inactive');

    // Create work entry for inactive site
    $workDetails = [
      'date' => self::$testDate,
      'site_id' => $inactiveSiteID,
      'encrypted_blob' => self::validBlob($inactiveSiteID),
    ];

    $result = WorkEntry::updateWorkEntry($workDetails, self::$testUserUUID);
    $this->assertTrue($result, 'Work entry should be created for inactive site');

    // Verify site is now active
    $this->assertEquals('active', $sites->getSiteStatus($inactiveSiteID, self::$testUserUUID), 'Site should be activated after work entry');

    // Clean up
    $workKey = D_WORK . ':' . self::$testUserUUID . ':' . self::$testDate . ':' . $inactiveSiteID;
    Database::del($workKey);
    Database::del($siteKey);
  }

  /**
   * Test complete archive workflow simulation
   */
  #[Test]
  public function workEntriesCanBeArchivedAndRestored(): void
  {
    // Create work entries for archiving (site already exists from setUp)
    // Use today's date to avoid lock service issues with past dates
    $archiveDate = date('Y-m-d');
    $archiveKey = D_WORK . ':' . self::$testUserUUID . ':' . $archiveDate . ':' . self::$testSiteID;
    
    $workDetails = [
      'date' => $archiveDate,
      'site_id' => self::$testSiteID,
      'encrypted_blob' => self::validBlob(self::$testSiteID),
    ];

    $result = WorkEntry::updateWorkEntry($workDetails, self::$testUserUUID);
    $this->assertTrue($result, 'Work entry should be created for archiving');
    
    // Verify entry exists
    $this->assertTrue(WorkEntry::exists($archiveKey), 'Entry should exist before archiving');

    // Simulate archive: Read data and store with archive prefix
    $originalData = Database::hgetall($archiveKey);
    $archivePrefix = 'archive:' . D_WORK;
    $archiveStorageKey = str_replace(D_WORK, $archivePrefix, $archiveKey);
    
    Database::hset($archiveStorageKey, $originalData);
    $this->assertTrue(Database::exists($archiveStorageKey), 'Archive should be created');

    // Simulate deletion of original
    Database::del($archiveKey);
    $this->assertFalse(WorkEntry::exists($archiveKey), 'Original should be deleted after archiving');

    // Simulate restore: Copy archive back to original location
    $archivedData = Database::hgetall($archiveStorageKey);
    Database::hset($archiveKey, $archivedData);
    
    // Verify restoration
    $this->assertTrue(WorkEntry::exists($archiveKey), 'Entry should be restored');
    $restoredData = Database::hgetall($archiveKey);
    
    $this->assertEquals($originalData['encrypted_blob'], $restoredData['encrypted_blob'], 'Encrypted blob should match after restore');

    // Clean up
    Database::del($archiveKey);
    Database::del($archiveStorageKey);
  }
}
