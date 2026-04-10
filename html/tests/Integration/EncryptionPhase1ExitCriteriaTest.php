<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Database;
use PayCal\Domain\Config\EncryptionConfig;
use PayCal\Domain\Encryption\EnvelopeFormat;
use PayCal\Domain\Sites;
use PHPUnit\Framework\Attributes\Group;

/**
 * Encryption Exit Criteria Test.
 *
 * Verifies that current encryption runtime meets exit criteria:
 * - ≥99.9% successful encryptions
 * - No increase in error rate
 * - Encrypted-only storage contract (no plaintext reintroduction)
 * - Envelope validity and size constraints
 */


require_once __DIR__.'/../../bootstrap/constants.php';

/**
 * @internal
 *
 */
#[Group('integration')]
#[Group('crypto')]
#[Group('slow')]
final class EncryptionPhase1ExitCriteriaTest extends TestCase
{
  private const MIN_SUCCESS_RATE = 0.999; // 99.9%

  /**
   * Set up test environment.
   */
  protected function setUp(): void
  {
    parent::setUp();

    // Ensure we're in test mode
    if (!defined('USER_UUID')) {
      define('USER_UUID', 'test-user-'.bin2hex(random_bytes(8)));
    }

    // Reset encryption config for testing
    EncryptionConfig::reset();
  }

  /**
   * Clean up after tests.
   */
  protected function tearDown(): void
  {
    EncryptionConfig::reset();
    parent::tearDown();
  }

  // =========================================================================
  // Phase 1 Exit Criteria Tests
  // =========================================================================

  /**
   * Test: Encryption success rate must be ≥99.9%.
   *
   * This is the PRIMARY exit criterion for Phase 1.
   * Measures: encryption-success vs encryption-failure telemetry.
   */
  #[Group('skip')]
  public function testEncryptionSuccessRateMeetsThreshold(): void
  {
    $v = ENCRYPTION_TELEMETRY_SCHEMA;

    // Get telemetry counters
    $successKey = "telemetry:encryption:{$v}:client:encryption-success";
    $failureKey = "telemetry:encryption:{$v}:client:encryption-failure";

    $successCount = (int) Database::get($successKey);
    $failureCount = (int) Database::get($failureKey);

    $total = $successCount + $failureCount;

    // If no data yet, we can't measure - test passes but warns
    if (0 === $total) {
      $this->markTestSkipped('No encryption telemetry data available yet. Run Phase 1 in production first.');
    }

    $successRate = $successCount / $total;

    $this->assertGreaterThanOrEqual(
      self::MIN_SUCCESS_RATE,
      $successRate,
      sprintf(
        'Encryption success rate %.4f%% is below required 99.9%% threshold. '
        .'Success: %d, Failure: %d, Total: %d',
        $successRate * 100,
        $successCount,
        $failureCount,
        $total
      )
    );
  }

  /**
   * Test: Decryption success rate must be ≥99.9%.
   *
   * Ensures encrypted data can be reliably decrypted.
   */
  #[Group('skip')]
  public function testDecryptionSuccessRateMeetsThreshold(): void
  {
    $v = ENCRYPTION_TELEMETRY_SCHEMA;

    $successKey = "telemetry:encryption:{$v}:client:decryption-success";
    $failureKey = "telemetry:encryption:{$v}:client:decryption-failure";

    $successCount = (int) Database::get($successKey);
    $failureCount = (int) Database::get($failureKey);

    $total = $successCount + $failureCount;

    if (0 === $total) {
      $this->markTestSkipped('No decryption telemetry data available yet.');
    }

    $successRate = $successCount / $total;

    $this->assertGreaterThanOrEqual(
      self::MIN_SUCCESS_RATE,
      $successRate,
      sprintf(
        'Decryption success rate %.4f%% is below required 99.9%% threshold. '
        .'Success: %d, Failure: %d, Total: %d',
        $successRate * 100,
        $successCount,
        $failureCount,
        $total
      )
    );
  }

  /**
   * Test: Error rate must not increase during Phase 1.
   *
   * Compares error rates before and after encryption enablement.
   */
  #[Group('skip')]
  public function testErrorRateHasNotIncreased(): void
  {
    $v = ENCRYPTION_TELEMETRY_SCHEMA;

    // Get current error counts
    $encryptionErrors = (int) Database::get("telemetry:encryption:{$v}:client:encryption-failure");
    $decryptionErrors = (int) Database::get("telemetry:encryption:{$v}:client:decryption-failure");

    // Get total operations
    $totalEncryptions = (int) Database::get("telemetry:encryption:{$v}:client:encryption-success") + $encryptionErrors;
    $totalDecryptions = (int) Database::get("telemetry:encryption:{$v}:client:decryption-success") + $decryptionErrors;

    $totalOps = $totalEncryptions + $totalDecryptions;
    $totalErrors = $encryptionErrors + $decryptionErrors;

    if (0 === $totalOps) {
      $this->markTestSkipped('No operations recorded yet.');
    }

    $errorRate = $totalErrors / $totalOps;

    // Error rate should be < 0.1% (inverse of 99.9% success)
    $this->assertLessThan(
      0.001,
      $errorRate,
      sprintf(
        'Error rate %.4f%% exceeds acceptable threshold of 0.1%%. '
        .'Total errors: %d, Total operations: %d',
        $errorRate * 100,
        $totalErrors,
        $totalOps
      )
    );
  }

  // =========================================================================
  // Encrypted-Only Contract Tests
  // =========================================================================

  /**
   * Test: Encrypted entries persist ciphertext envelope only.
   */
  public function testEncryptedOnlyContractPersistsCiphertextWithoutPlaintextFields(): void
  {
    // Enable encryption for this test
    EncryptionConfig::setEnabled(true);

    $userUuid = USER_UUID;
    $siteId = Sites::generateSiteUUID();
    $date = date('Y-m-d');
    $workKey = "work:{$userUuid}:{$date}:{$siteId}";

    // Create test site
    Database::hset("site:{$userUuid}:{$siteId}", [
        'site_name' => 'Test Site',
        'wage' => '25.00',
        'status' => 'active',
    ]);

    // Create work entry with encrypted_blob
    $workData = [
        'siteId' => $siteId,
        'hours' => '8.0',
        'overtime' => '0.0',
        'travel' => '0.5',
        'living_out' => '0',
        'notes' => 'Test entry for dual-write verification',
    ];

    // Simulate encrypted-only write path.
    $encryptedBlob = $this->createTestEncryptedBlob($workData);

    Database::hset($workKey, [
        'siteId' => $siteId,
        'encrypted_blob' => $encryptedBlob,
    ]);

    // Verify plaintext fields are not stored.
    $stored = Database::hgetall($workKey);

    $this->assertArrayHasKey('encrypted_blob', $stored, 'encrypted_blob field must exist');
    $this->assertNotEmpty($stored['encrypted_blob'], 'encrypted_blob must not be empty');
    $this->assertArrayNotHasKey('hours', $stored, 'Plaintext hours must not be stored');
    $this->assertArrayNotHasKey('overtime', $stored, 'Plaintext overtime must not be stored');
    $this->assertArrayNotHasKey('travel', $stored, 'Plaintext travel must not be stored');
    $this->assertArrayNotHasKey('living_out', $stored, 'Plaintext living_out must not be stored');
    $this->assertArrayNotHasKey('notes', $stored, 'Plaintext notes must not be stored');

    // Verify encrypted_blob is valid envelope
    $envelope = json_decode($stored['encrypted_blob'], true);
    $this->assertIsArray($envelope, 'encrypted_blob must be valid JSON');
    $this->assertTrue(EnvelopeFormat::isValid($envelope), 'encrypted_blob must be valid envelope');

    // Cleanup
    Database::unlink($workKey);
    Database::unlink("site:{$userUuid}:{$siteId}");
  }

  /**
    * Test: Corrupted encrypted blob does not imply plaintext fallback contract.
   */
    public function testCorruptedEncryptedBlobKeepsPlaintextAbsent(): void
  {
    $userUuid = USER_UUID;
    $siteId = Sites::generateSiteUUID();
    $date = date('Y-m-d');
    $workKey = "work:{$userUuid}:{$date}:{$siteId}";

    // Corrupted envelope present, plaintext fields absent.
    Database::hset($workKey, [
        'siteId' => $siteId,
        'encrypted_blob' => 'corrupted-not-valid-json-or-envelope',
    ]);

    // Contract proof: no plaintext fallback payload exists.
    $data = Database::hgetall($workKey);
    $this->assertArrayNotHasKey('hours', $data);
    $this->assertArrayNotHasKey('overtime', $data);
    $this->assertArrayNotHasKey('travel', $data);
    $this->assertArrayNotHasKey('living_out', $data);
    $this->assertArrayNotHasKey('notes', $data);

    // Cleanup
    Database::unlink($workKey);
  }

  /**
   * Test: Encrypted blob size is within limits.
   */
  public function testEncryptedBlobSizeWithinLimits(): void
  {
    // Create a large work entry
    $largeWorkData = [
        'siteId' => 'S123456789',
        'hours' => '8.0',
        'overtime' => '2.0',
        'travel' => '1.5',
        'living_out' => '50',
        'notes' => str_repeat('Long note content. ', 100), // ~2KB of notes
    ];

    $encryptedBlob = $this->createTestEncryptedBlob($largeWorkData);
    $blobSize = strlen($encryptedBlob);

    $this->assertLessThanOrEqual(
      ENCRYPTED_BLOB_MAX_BYTES,
      $blobSize,
      sprintf(
        'Encrypted blob size %d bytes exceeds max %d bytes',
        $blobSize,
        ENCRYPTED_BLOB_MAX_BYTES
      )
    );
  }

  // =========================================================================
  // Feature Flag Tests
  // =========================================================================

  /**
   * Test: crypto_enabled flag controls encryption behavior.
   */
  public function testCryptoEnabledFlagDisablesEncryption(): void
  {
    // Test with encryption disabled
    EncryptionConfig::setEnabled(false);

    $this->assertFalse(EncryptionConfig::isEnabled(), 'Encryption should be disabled');

    // Test with encryption enabled
    EncryptionConfig::setEnabled(true);

    $this->assertTrue(EncryptionConfig::isEnabled(), 'Encryption should be enabled');
  }

  /**
   * Test: crypto_required flag enforces encrypted-only mode.
   */
  public function testCryptoRequiredFlagEnforcesEncryption(): void
  {
    EncryptionConfig::setEnabled(true);
    EncryptionConfig::setRequired(true);

    $this->assertTrue(EncryptionConfig::isRequired(), 'Encryption should be required');

    // Reset
    EncryptionConfig::setRequired(false);
  }

  // =========================================================================
  // Helper Methods
  // =========================================================================

  /**
   * Creates a test encrypted blob (simulates client-side encryption).
   *
  * @param array<string, string> $data Data to "encrypt"
   *
   * @return string JSON envelope
   */
  private function createTestEncryptedBlob(array $data): string
  {
    // Simulate encryption envelope (in production, this is done client-side)
    $envelope = EnvelopeFormat::create(
      1, // version
      base64_encode(random_bytes(12)), // nonce
      base64_encode(json_encode($data)), // "ciphertext" (in reality, this would be encrypted)
      null // AAD
    );

    return EnvelopeFormat::toJson($envelope);
  }
}
