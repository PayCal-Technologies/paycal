<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Database;
use PayCal\Domain\WorkEntry;
use PHPUnit\Framework\Attributes\Group;

/**
 * EncryptionCorruptionTest.php
 *
 * @package PayCal
 */

require_once __DIR__ . '/../../tests/bootstrap.php';

/**
 * EncryptionCorruptionTest
 */
#[Group('integration')]
#[Group('crypto')]
final class EncryptionCorruptionTest extends TestCase
{
    private string $userUUID = 'Ucorruption01';

  private string $siteID = 'S123abc456';  // Must match pattern S[a-f0-9]{9}

  private string $workDate = '';

  private string $workEntryKey = '';

  private string $siteKey = '';

  protected function setUp(): void
  {
    // Enable test mode to bypass site status checks
    putenv('TEST_MODE=1');

    $this->workDate = date('Y-m-d');
    $this->siteKey = D_SITE.":{$this->userUUID}:{$this->siteID}";
    $this->workEntryKey = D_WORK.":{$this->userUUID}:{$this->workDate}:{$this->siteID}";

    // Create the site first
    Database::hset($this->siteKey, [
        'site_name' => 'Corruption Test Site',
        'wage' => '25.00',
        'status' => 'active',
    ]);

    // Clean up any existing work entry
    Database::del($this->workEntryKey);
  }

  protected function tearDown(): void
  {
    // Cleanup
    Database::del($this->workEntryKey);
    Database::del($this->siteKey);
  }

  /**
   * Test: Truncated envelope should fail validation.
   *
   * When the envelope is truncated (incomplete base64), the system should
   * reject it during storage validation.
   */
  public function testTruncatedEnvelopeRejectedByValidation(): void
  {
    // Create a valid envelope then truncate it
    $validBlob = $this->createValidEnvelope(
      ['d' => $this->workDate, 's' => $this->siteID, 'h' => 8, 'l' => 0, 't' => 0],
      $this->siteID
    );

    // Truncate by 20%
    $truncatedBlob = substr($validBlob, 0, (int) (strlen($validBlob) * 0.8));

    $workDetails = [
        'd' => $this->workDate,
        's' => $this->siteID,
        'h' => 8,
        'l' => 0,
        't' => 0,
        'encrypted_blob' => $truncatedBlob,
    ];

    // Should FAIL validation (truncated base64 is invalid)
    $ok = WorkEntry::updateWorkEntry($workDetails, $this->userUUID);
    $this->assertFalse($ok, 'Storage should reject truncated blob');

    // Entry should not exist
    $stored = Database::hgetall($this->workEntryKey);
    $this->assertEmpty($stored, 'No entry should be stored for invalid blob');
  }

  /**
   * Test: Wrong version string should be stored but handled on retrieval.
   *
   * Envelope with unsupported version should be stored (validation passes)
   * but handled gracefully on retrieval.
   */
  public function testWrongVersionStringStoredAndHandled(): void
  {
    $key = random_bytes(32);
    $nonce = random_bytes(12);

    $entry = ['d' => $this->workDate, 's' => $this->siteID, 'h' => 8, 'l' => 0, 't' => 0];
    $plaintext = json_encode($entry);
    $tag = '';
    $ciphertext_raw = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    $combined = $ciphertext_raw.$tag;

    // Create envelope with wrong version (still valid structure)
    $envelope = json_encode([
        'version' => 999, // Unsupported version
        'ciphertext' => base64_encode($combined),
        'nonce' => base64_encode($nonce),
        'aad' => $this->siteID,
    ]);

    $blob = base64_encode($envelope);

    $workDetails = [
        'd' => $this->workDate,
        's' => $this->siteID,
        'h' => 8,
        'l' => 0,
        't' => 0,
        'encrypted_blob' => $blob,
    ];

    // Should succeed (valid envelope structure)
    $ok = WorkEntry::updateWorkEntry($workDetails, $this->userUUID);
    $this->assertTrue($ok, 'Storage should succeed with valid envelope structure');

    // Retrieval should preserve encrypted payload only (no plaintext fallback)
    $result = WorkEntry::getWorkEntry($this->workEntryKey);
    $this->assertIsArray($result);
    $this->assertArrayHasKey('encrypted_blob', $result);
    $this->assertNotEmpty((string) ($result['encrypted_blob'] ?? ''));
  }

  /**
   * Test: Modified ciphertext should be stored but fail client-side decryption.
   *
   * If ciphertext is modified, the envelope is still valid for storage,
   * but client-side decryption would fail (integrity violation).
   */
  public function testModifiedCiphertextStoredButUndecryptable(): void
  {
    $key = random_bytes(32);
    $nonce = random_bytes(12);

    $entry = ['d' => $this->workDate, 's' => $this->siteID, 'h' => 8, 'l' => 0, 't' => 0];
    $plaintext = json_encode($entry);
    $tag = '';
    $ciphertext_raw = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    $combined = $ciphertext_raw.$tag;

    // Modify the ciphertext (flip some bits)
    $modifiedCombined = $combined;
    $modifiedCombined[0] = chr(ord($modifiedCombined[0]) ^ 0xFF);

    $envelope = json_encode([
        'version' => 1,
        'ciphertext' => base64_encode($modifiedCombined),
        'nonce' => base64_encode($nonce),
        'aad' => $this->siteID,
    ]);

    $blob = base64_encode($envelope);

    $workDetails = [
        'd' => $this->workDate,
        's' => $this->siteID,
        'h' => 8,
        'l' => 0,
        't' => 0,
        'encrypted_blob' => $blob,
    ];

    // Should succeed (valid envelope structure)
    $ok = WorkEntry::updateWorkEntry($workDetails, $this->userUUID);
    $this->assertTrue($ok, 'Storage should succeed with valid envelope structure');

    // Server cannot decrypt anyway, but should handle gracefully
    $result = WorkEntry::getWorkEntry($this->workEntryKey);
    $this->assertIsArray($result, 'Should fall back to plaintext');
  }

  /**
   * Test: Missing required fields should be rejected.
   *
   * Envelope missing 'ciphertext' or 'nonce' should fail validation.
   */
  public function testMissingRequiredFieldsRejected(): void
  {
    // Envelope missing 'nonce'
    $envelopeMissingNonce = json_encode([
        'version' => 1,
        'ciphertext' => base64_encode('somefakeciphertext'),
        // 'nonce' is missing
        'aad' => $this->siteID,
    ]);

    $blob = base64_encode($envelopeMissingNonce);

    $workDetails = [
        'd' => $this->workDate,
        's' => $this->siteID,
        'h' => 8,
        'l' => 0,
        't' => 0,
        'encrypted_blob' => $blob,
    ];

    // Should FAIL validation (missing nonce)
    $ok = WorkEntry::updateWorkEntry($workDetails, $this->userUUID);
    $this->assertFalse($ok, 'Storage should reject envelope missing nonce');

    // Entry should not exist
    $stored = Database::hgetall($this->workEntryKey);
    $this->assertEmpty($stored, 'No entry should be stored for invalid envelope');
  }

  /**
   * Test: Invalid base64 encoding should be rejected.
   */
  public function testInvalidBase64Rejected(): void
  {
    // Invalid base64 characters
    $invalidBase64 = '!!!InvalidBase64!!!';

    $workDetails = [
        'd' => $this->workDate,
        's' => $this->siteID,
        'h' => 8,
        'l' => 0,
        't' => 0,
        'encrypted_blob' => $invalidBase64,
    ];

    // Should FAIL validation (invalid base64)
    $ok = WorkEntry::updateWorkEntry($workDetails, $this->userUUID);
    $this->assertFalse($ok, 'Storage should reject invalid base64');

    // Entry should not exist
    $stored = Database::hgetall($this->workEntryKey);
    $this->assertEmpty($stored, 'No entry should be stored for invalid base64');
  }

  /**
   * Test: Malformed JSON envelope should be rejected.
   */
  public function testMalformedJsonEnvelopeRejected(): void
  {
    // Valid base64 but invalid JSON inside
    $malformedJson = base64_encode('{"version": 1, "ciphertext": "abc"'); // Missing closing brace

    $workDetails = [
        'd' => $this->workDate,
        's' => $this->siteID,
        'h' => 8,
        'l' => 0,
        't' => 0,
        'encrypted_blob' => $malformedJson,
    ];

    // Should FAIL validation (malformed JSON)
    $ok = WorkEntry::updateWorkEntry($workDetails, $this->userUUID);
    $this->assertFalse($ok, 'Storage should reject malformed JSON');

    // Entry should not exist
    $stored = Database::hgetall($this->workEntryKey);
    $this->assertEmpty($stored, 'No entry should be stored for malformed JSON');
  }

  /**
   * Test: Empty envelope should be rejected.
   */
  public function testEmptyEnvelopeRejected(): void
  {
    $workDetails = [
        'd' => $this->workDate,
        's' => $this->siteID,
        'h' => 8,
        'l' => 0,
        't' => 0,
        'encrypted_blob' => '',
    ];

    // Should FAIL validation (empty string is not valid base64-encoded JSON)
    $ok = WorkEntry::updateWorkEntry($workDetails, $this->userUUID);
    $this->assertFalse($ok, 'Storage should reject empty blob');

    // Entry should not exist
    $stored = Database::hgetall($this->workEntryKey);
    $this->assertEmpty($stored, 'No entry should be stored for empty blob');
  }

  /**
   * Test: Encrypted blob without plaintext fallback returns entry with empty fields.
   *
   * When encrypted_blob exists but no plaintext fields are available,
   * the system returns the entry with empty/minimal fields (not null).
   */
  public function testEncryptedBlobWithoutPlaintextFallbackReturnsMinimalEntry(): void
  {
    // Store ONLY the encrypted blob, no plaintext fields
    $blob = $this->createValidEnvelope(
      ['d' => $this->workDate, 's' => $this->siteID, 'h' => 8, 'l' => 0, 't' => 0],
      $this->siteID
    );

    // Direct database write without plaintext hours
    Database::hset($this->workEntryKey, [
        'd' => $this->workDate,
        's' => $this->siteID,
        'encrypted_blob' => $blob,
    ]);

    // Retrieval should return the entry (with site name populated)
    $result = WorkEntry::getWorkEntry($this->workEntryKey);
    $this->assertIsArray($result, 'Should return entry array');
    $this->assertArrayHasKey('encrypted_blob', $result, 'Should have encrypted_blob');
    $this->assertEquals($this->workDate, $result['date'], 'Should have date');
    $this->assertEquals($this->siteID, $result['site_id'], 'Should have site ID');
  }

  /**
   * Test: Envelope with extra unexpected fields should still work.
   */
  public function testEnvelopeWithExtraFieldsHandled(): void
  {
    $key = random_bytes(32);
    $nonce = random_bytes(12);

    $entry = ['d' => $this->workDate, 's' => $this->siteID, 'h' => 8, 'l' => 0, 't' => 0];
    $plaintext = json_encode($entry);
    $tag = '';
    $ciphertext_raw = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    $combined = $ciphertext_raw.$tag;

    // Envelope with extra fields
    $envelope = json_encode([
        'version' => 1,
        'ciphertext' => base64_encode($combined),
        'nonce' => base64_encode($nonce),
        'aad' => $this->siteID,
        'extra_field' => 'should be ignored',
        'another_extra' => ['nested' => 'data'],
    ]);

    $blob = base64_encode($envelope);

    $workDetails = [
        'd' => $this->workDate,
        's' => $this->siteID,
        'h' => 8,
        'l' => 0,
        't' => 0,
        'encrypted_blob' => $blob,
    ];

    $ok = WorkEntry::updateWorkEntry($workDetails, $this->userUUID);
    $this->assertTrue($ok, 'Storage should succeed with extra fields');

    $result = WorkEntry::getWorkEntry($this->workEntryKey);
    $this->assertIsArray($result, 'Should fall back to plaintext');
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
