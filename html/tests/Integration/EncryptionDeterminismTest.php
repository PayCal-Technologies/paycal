<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Encryption Determinism Tests.
 *
 * Tests for encryption behavior guarantees:
 * - Non-deterministic encryption (random IV/nonce produces different ciphertext)
 * - Same plaintext decrypts to same value
 * - Envelope structure consistency
 *
 * PHP version 8.4.16
 *
 * @category   Tests
 *
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */


require_once __DIR__.'/../../tests/bootstrap.php';

/**
 * @internal
 *
 */
#[Group('integration')]
#[Group('crypto')]
final class EncryptionDeterminismTest extends TestCase
{
  /**
   * Test: Encryption is NON-deterministic (different ciphertext each time).
   *
   * AES-GCM uses a random nonce, so the same plaintext encrypted twice
   * should produce DIFFERENT ciphertext but SAME decryption result.
   */
  public function testEncryptionIsNonDeterministic(): void
  {
    $key = random_bytes(32);
    $plaintext = json_encode(['d' => '2025-01-15', 's' => 'S123456789', 'h' => 8, 'l' => 0, 't' => 0]);

    // Encrypt same plaintext twice with different nonces
    $nonce1 = random_bytes(12);
    $tag1 = '';
    $ciphertext1 = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce1, $tag1, '', 16);
    $combined1 = $ciphertext1.$tag1;

    $nonce2 = random_bytes(12);
    $tag2 = '';
    $ciphertext2 = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce2, $tag2, '', 16);
    $combined2 = $ciphertext2.$tag2;

    // Ciphertexts should be DIFFERENT (non-deterministic)
    $this->assertNotSame(
      base64_encode($combined1),
      base64_encode($combined2),
      'Same plaintext with different nonces should produce different ciphertext'
    );

    // Nonces should be different
    $this->assertNotSame(
      base64_encode($nonce1),
      base64_encode($nonce2),
      'Nonces should be different for each encryption'
    );
  }

  /**
   * Test: Different ciphertext from same plaintext decrypts to same value.
   */
  public function testDifferentCiphertextDecryptsToSameValue(): void
  {
    $key = random_bytes(32);
    $plaintext = json_encode(['d' => '2025-01-15', 's' => 'S123456789', 'h' => 8, 'l' => 0, 't' => 0]);

    // Encrypt twice with different nonces
    $nonce1 = random_bytes(12);
    $tag1 = '';
    $ciphertext1 = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce1, $tag1, '', 16);
    $combined1 = $ciphertext1.$tag1;

    $nonce2 = random_bytes(12);
    $tag2 = '';
    $ciphertext2 = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce2, $tag2, '', 16);
    $combined2 = $ciphertext2.$tag2;

    // Decrypt both
    $tagLen = 16;

    $ctLen1 = strlen($combined1) - $tagLen;
    $decrypted1 = openssl_decrypt(
      substr($combined1, 0, $ctLen1),
      'aes-256-gcm',
      $key,
      OPENSSL_RAW_DATA,
      $nonce1,
      substr($combined1, $ctLen1, $tagLen)
    );

    $ctLen2 = strlen($combined2) - $tagLen;
    $decrypted2 = openssl_decrypt(
      substr($combined2, 0, $ctLen2),
      'aes-256-gcm',
      $key,
      OPENSSL_RAW_DATA,
      $nonce2,
      substr($combined2, $ctLen2, $tagLen)
    );

    // Both should decrypt to the SAME plaintext
    $this->assertSame($plaintext, $decrypted1, 'First decryption should match original');
    $this->assertSame($plaintext, $decrypted2, 'Second decryption should match original');
    $this->assertSame($decrypted1, $decrypted2, 'Both decryptions should be identical');
  }

  /**
   * Test: Envelope structure is consistent.
   *
   * All envelopes should have the same structure regardless of content.
   */
  public function testEnvelopeStructureConsistency(): void
  {
    $key = random_bytes(32);
    $nonce = random_bytes(12);

    $testCases = [
        ['d' => '2025-01-15', 's' => 'S123456789', 'h' => 8, 'l' => 0, 't' => 0],
        ['d' => '2025-01-16', 's' => 'S987654321', 'h' => 12, 'l' => 1, 't' => 2],
        ['d' => '2025-01-17', 's' => 'S111111111', 'h' => 0.5, 'l' => 0, 't' => 0],
    ];

    foreach ($testCases as $i => $entry) {
      $plaintext = json_encode($entry);
      $tag = '';
      $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
      $combined = $ciphertext.$tag;

      $envelope = json_encode([
          'version' => 1,
          'ciphertext' => base64_encode($combined),
          'nonce' => base64_encode($nonce),
          'aad' => $entry['s'],
      ]);

      $decoded = json_decode($envelope, true);

      // Assert required keys exist
      $this->assertArrayHasKey('version', $decoded, "Case {$i}: envelope must have 'version'");
      $this->assertArrayHasKey('ciphertext', $decoded, "Case {$i}: envelope must have 'ciphertext'");
      $this->assertArrayHasKey('nonce', $decoded, "Case {$i}: envelope must have 'nonce'");

      // Assert types
      $this->assertIsInt($decoded['version'], "Case {$i}: version must be int");
      $this->assertIsString($decoded['ciphertext'], "Case {$i}: ciphertext must be string");
      $this->assertIsString($decoded['nonce'], "Case {$i}: nonce must be string");

      // Assert version is correct
      $this->assertEquals(1, $decoded['version'], "Case {$i}: version must be 1");

      // Assert base64 validity
      $this->assertNotFalse(
        base64_decode($decoded['ciphertext'], true),
        "Case {$i}: ciphertext must be valid base64"
      );
      $this->assertNotFalse(
        base64_decode($decoded['nonce'], true),
        "Case {$i}: nonce must be valid base64"
      );
    }
  }

  /**
   * Test: Same key and nonce produces same ciphertext (deterministic at crypto level).
   *
   * While we use random nonces in practice, the underlying AES-GCM is
   * deterministic for the same key+nonce+plaintext combination.
   */
  public function testSameKeyNonceProducesSameCiphertext(): void
  {
    $key = random_bytes(32);
    $nonce = random_bytes(12); // Same nonce for both
    $plaintext = json_encode(['d' => '2025-01-15', 's' => 'S123456789', 'h' => 8, 'l' => 0, 't' => 0]);

    // Encrypt twice with SAME key and SAME nonce
    $tag1 = '';
    $ciphertext1 = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag1, '', 16);
    $combined1 = $ciphertext1.$tag1;

    $tag2 = '';
    $ciphertext2 = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag2, '', 16);
    $combined2 = $ciphertext2.$tag2;

    // With same key and nonce, ciphertext SHOULD be identical
    $this->assertSame(
      base64_encode($combined1),
      base64_encode($combined2),
      'Same key+nonce+plaintext should produce identical ciphertext'
    );
  }

  /**
   * Test: Different keys produce different ciphertext.
   */
  public function testDifferentKeysProduceDifferentCiphertext(): void
  {
    $key1 = random_bytes(32);
    $key2 = random_bytes(32);
    $nonce = random_bytes(12);
    $plaintext = json_encode(['d' => '2025-01-15', 's' => 'S123456789', 'h' => 8, 'l' => 0, 't' => 0]);

    $tag1 = '';
    $ciphertext1 = openssl_encrypt($plaintext, 'aes-256-gcm', $key1, OPENSSL_RAW_DATA, $nonce, $tag1, '', 16);
    $combined1 = $ciphertext1.$tag1;

    $tag2 = '';
    $ciphertext2 = openssl_encrypt($plaintext, 'aes-256-gcm', $key2, OPENSSL_RAW_DATA, $nonce, $tag2, '', 16);
    $combined2 = $ciphertext2.$tag2;

    // Different keys should produce different ciphertext
    $this->assertNotSame(
      base64_encode($combined1),
      base64_encode($combined2),
      'Different keys should produce different ciphertext'
    );
  }

  /**
   * Test: Ciphertext length is predictable based on plaintext length.
   *
   * AES-GCM ciphertext length = plaintext length (no padding needed for GCM)
   * Plus 16 bytes for the auth tag
   */
  public function testCiphertextLengthIsPredictable(): void
  {
    $key = random_bytes(32);
    $nonce = random_bytes(12);

    $plaintexts = [
        'short',
        str_repeat('x', 100),
        str_repeat('y', 1000),
        json_encode(['d' => '2025-01-15', 's' => 'S123456789', 'h' => 8, 'l' => 0, 't' => 0]),
    ];

    foreach ($plaintexts as $plaintext) {
      $tag = '';
      $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
      $combined = $ciphertext.$tag;

      // Combined length = plaintext length + 16 (tag)
      $this->assertEquals(
        strlen($plaintext) + 16,
        strlen($combined),
        'Ciphertext + tag length should be plaintext + 16 bytes'
      );
    }
  }

  /**
   * Test: Nonce length is always 12 bytes for AES-GCM.
   */
  public function testNonceLengthIsCorrect(): void
  {
    $nonce = random_bytes(12);
    $this->assertEquals(12, strlen($nonce), 'AES-GCM nonce should be 12 bytes');

    $encoded = base64_encode($nonce);
    $decoded = base64_decode($encoded, true);
    $this->assertEquals(12, strlen($decoded), 'Decoded nonce should still be 12 bytes');
  }

  /**
   * Test: Key length is always 32 bytes for AES-256-GCM.
   */
  public function testKeyLengthIsCorrect(): void
  {
    $key = random_bytes(32);
    $this->assertEquals(32, strlen($key), 'AES-256-GCM key should be 32 bytes');
  }
}
