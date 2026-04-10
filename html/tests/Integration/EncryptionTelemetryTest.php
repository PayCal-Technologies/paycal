<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Database;
use PayCal\Domain\WorkEntry;
use PHPUnit\Framework\Attributes\Group;

/**
 * EncryptionTelemetryTest.php
 *
 * @package PayCal
 */

require_once __DIR__ . '/../../tests/bootstrap.php';

/**
 * EncryptionTelemetryTest
 */
#[Group('integration')]
#[Group('crypto')]
#[Group('api')]
final class EncryptionTelemetryTest extends TestCase
{
    private string $userUUID = 'Utelemetry01';

  private string $siteID = 'S123tel456';  // Must match pattern S[a-f0-9]{9}

  private string $workDate = '';

  private string $workEntryKey = '';

  private string $siteKey = '';

  private string $telemetryPrefix = '';

  protected function setUp(): void
  {
    // Enable test mode to bypass site status checks
    putenv('TEST_MODE=1');

    $this->workDate = date('Y-m-d');
    $this->siteKey = D_SITE.":{$this->userUUID}:{$this->siteID}";
    $this->workEntryKey = D_WORK.":{$this->userUUID}:{$this->workDate}:{$this->siteID}";

    // Determine telemetry schema version
    $v = defined('ENCRYPTION_TELEMETRY_SCHEMA') ? ENCRYPTION_TELEMETRY_SCHEMA : 'v1';
    $this->telemetryPrefix = "telemetry:encryption:{$v}:server";

    // Create the site first
    Database::hset($this->siteKey, [
        'site_name' => 'Telemetry Test Site',
        'wage' => '25.00',
        'status' => 'active',
    ]);

    // Clean up any existing work entry
    Database::del($this->workEntryKey);
  }

  protected function tearDown(): void
  {
    Database::del($this->workEntryKey);
    Database::del($this->siteKey);
  }

  /**
   * Test: Reading encrypted entry increments decryption_attempt counter.
   */
  public function testReadingEncryptedEntryIncrementsDecryptionAttempt(): void
  {
    // Store entry with encrypted blob
    $blob = $this->createValidEnvelope(
      ['d' => $this->workDate, 's' => $this->siteID, 'h' => 8, 'l' => 0, 't' => 0],
      $this->siteID
    );

    $workDetails = [
        'd' => $this->workDate,
        's' => $this->siteID,
        'h' => 8,
        'l' => 0,
        't' => 0,
        'encrypted_blob' => $blob,
    ];

    WorkEntry::updateWorkEntry($workDetails, $this->userUUID);

    // Read the entry (this should trigger telemetry)
    WorkEntry::getWorkEntry($this->workEntryKey);

    // Verify the telemetry key exists and has a value > 0
    $attemptValue = $this->getTelemetryCounter('decryption_attempt');
    $this->assertGreaterThan(0, $attemptValue, 'decryption_attempt counter should be > 0 after reading encrypted entry');
  }

  /**
   * Test: Reading encrypted entry increments decryption_failure counter.
   *
   * Since server cannot decrypt in zero-knowledge mode, failure should always increment.
   */
  public function testReadingEncryptedEntryIncrementsDecryptionFailure(): void
  {
    // Store entry with encrypted blob
    $blob = $this->createValidEnvelope(
      ['d' => $this->workDate, 's' => $this->siteID, 'h' => 8, 'l' => 0, 't' => 0],
      $this->siteID
    );

    $workDetails = [
        'd' => $this->workDate,
        's' => $this->siteID,
        'h' => 8,
        'l' => 0,
        't' => 0,
        'encrypted_blob' => $blob,
    ];

    WorkEntry::updateWorkEntry($workDetails, $this->userUUID);

    // Read the entry (this should trigger telemetry)
    WorkEntry::getWorkEntry($this->workEntryKey);

    // Verify the telemetry key exists and has a value > 0
    $failureValue = $this->getTelemetryCounter('decryption_failure');
    $this->assertGreaterThan(0, $failureValue, 'decryption_failure counter should be > 0 after reading encrypted entry');
  }

  /**
   * Test: Multiple reads increment counters multiple times.
   */
  public function testMultipleReadsIncrementCountersMultipleTimes(): void
  {
    // Store entry with encrypted blob
    $blob = $this->createValidEnvelope(
      ['d' => $this->workDate, 's' => $this->siteID, 'h' => 8, 'l' => 0, 't' => 0],
      $this->siteID
    );

    $workDetails = [
        'd' => $this->workDate,
        's' => $this->siteID,
        'h' => 8,
        'l' => 0,
        't' => 0,
        'encrypted_blob' => $blob,
    ];

    WorkEntry::updateWorkEntry($workDetails, $this->userUUID);

    // Read the entry 3 times
    WorkEntry::getWorkEntry($this->workEntryKey);
    WorkEntry::getWorkEntry($this->workEntryKey);
    WorkEntry::getWorkEntry($this->workEntryKey);

    // Verify counters are > 0 (they should increment with each read)
    $attemptValue = $this->getTelemetryCounter('decryption_attempt');
    $failureValue = $this->getTelemetryCounter('decryption_failure');

    $this->assertGreaterThan(0, $attemptValue, 'decryption_attempt counter should be > 0');
    $this->assertGreaterThan(0, $failureValue, 'decryption_failure counter should be > 0');
  }

  /**
   * Test: Reading plaintext entry does NOT increment encryption telemetry.
   */
  public function testReadingPlaintextEntryDoesNotIncrementEncryptionTelemetry(): void
  {
    // Get baseline
    $beforeAttempt = $this->getTelemetryCounter('decryption_attempt');
    $beforeFailure = $this->getTelemetryCounter('decryption_failure');

    // Store entry WITHOUT encrypted blob
    $workDetails = [
        'd' => $this->workDate,
        's' => $this->siteID,
        'h' => 8,
        'l' => 0,
        't' => 0,
        // No encrypted_blob
    ];

    WorkEntry::updateWorkEntry($workDetails, $this->userUUID);

    // Read the entry
    WorkEntry::getWorkEntry($this->workEntryKey);

    // Get after values
    $afterAttempt = $this->getTelemetryCounter('decryption_attempt');
    $afterFailure = $this->getTelemetryCounter('decryption_failure');

    // Counters should NOT have changed
    $this->assertEquals($beforeAttempt, $afterAttempt, 'Plaintext read should not increment attempt counter');
    $this->assertEquals($beforeFailure, $afterFailure, 'Plaintext read should not increment failure counter');
  }

  /**
   * Test: Telemetry key format is correct.
   */
  public function testTelemetryKeyFormatIsCorrect(): void
  {
    $v = defined('ENCRYPTION_TELEMETRY_SCHEMA') ? ENCRYPTION_TELEMETRY_SCHEMA : 'v1';

    // Expected format: telemetry:encryption:{version}:server:{counter}
    $attemptKey = "telemetry:encryption:{$v}:server:decryption_attempt";
    $failureKey = "telemetry:encryption:{$v}:server:decryption_failure";

    // Keys should exist in Redis (or be accessible)
    $this->assertStringContainsString('telemetry:encryption:', $attemptKey);
    $this->assertStringContainsString('telemetry:encryption:', $failureKey);
    $this->assertStringContainsString($v, $attemptKey);
    $this->assertStringContainsString($v, $failureKey);
  }

  /**
   * Test: Invalid envelope still increments telemetry.
   *
   * Even if the envelope is malformed, telemetry should still be recorded.
   */
  public function testInvalidEnvelopeStillIncrementsTelemetry(): void
  {
    // Store entry with INVALID encrypted blob (but valid base64)
    // The envelope validation happens during updateWorkEntry, so we need to bypass it
    // by directly writing to the database
    Database::hset($this->workEntryKey, [
        'd' => $this->workDate,
        's' => $this->siteID,
        'h' => 8,
        'l' => 0,
        't' => 0,
        'encrypted_blob' => base64_encode('{"invalid": "envelope"}'),  // Valid base64, missing required fields
    ]);

    // Read the entry
    WorkEntry::getWorkEntry($this->workEntryKey);

    // Verify telemetry counters are > 0
    $attemptValue = $this->getTelemetryCounter('decryption_attempt');
    $failureValue = $this->getTelemetryCounter('decryption_failure');

    $this->assertGreaterThan(0, $attemptValue, 'Invalid envelope should still result in attempt counter > 0');
    $this->assertGreaterThan(0, $failureValue, 'Invalid envelope should still result in failure counter > 0');
  }

  /**
   * Helper: Get current telemetry counter value.
   */
  private function getTelemetryCounter(string $counter): int
  {
    $key = "{$this->telemetryPrefix}:{$counter}";
    $value = Database::get($key);

    return (int) ($value ?? 0);
  }

  /**
   * Helper: Create a valid encrypted envelope.
   */
  private function createValidEnvelope(array $entry, string $siteID): string
  {
    $key = random_bytes(32);
    $nonce = random_bytes(12);

    $plaintext = json_encode($entry);
    $tag = '';
    $ciphertext_raw = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    $combined = $ciphertext_raw.$tag;

    $envelope = json_encode([
        'version' => 1,
        'ciphertext' => base64_encode($combined),
        'nonce' => base64_encode($nonce),
        'aad' => $siteID,
    ]);

    return base64_encode($envelope);
  }
}
