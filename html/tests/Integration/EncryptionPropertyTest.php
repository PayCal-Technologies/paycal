<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Encryption Property-Based Tests.
 *
 * Tests using random inputs to verify encryption invariants:
 * - Encrypt/decrypt roundtrip for random strings
 * - Edge cases with special characters
 * - Boundary conditions
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
 */
#[Group('integration')]
#[Group('crypto')]
final class EncryptionPropertyTest extends TestCase
{
  #[DataProvider('randomStringProvider')]
  /**
   * Test: Random strings encrypt and decrypt correctly.
   */
  public function testRandomStringRoundtrip(string $plaintext): void
  {
    $key = random_bytes(32);
    $nonce = random_bytes(12);

    // Encrypt
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    $combined = $ciphertext.$tag;

    // Decrypt
    $tagLen = 16;
    $ctLen = strlen($combined) - $tagLen;
    $decrypted = openssl_decrypt(
      substr($combined, 0, $ctLen),
      'aes-256-gcm',
      $key,
      OPENSSL_RAW_DATA,
      $nonce,
      substr($combined, $ctLen, $tagLen)
    );

    $this->assertSame($plaintext, $decrypted, 'Decrypted text should match original');
  }

  /**
   * Data provider for random string tests.
   */
  public static function randomStringProvider(): array
  {
    $cases = [];

    // Random ASCII strings of various lengths
    for ($i = 0; $i < 10; ++$i) {
      $length = random_int(1, 1000);
      $cases["random_ascii_{$length}"] = [self::generateRandomString($length)];
    }

    // Random UTF-8 strings
    for ($i = 0; $i < 5; ++$i) {
      $cases["random_utf8_{$i}"] = [self::generateRandomUtf8String(100)];
    }

    // Edge cases
    $cases['empty_string'] = [''];
    $cases['single_char'] = ['x'];
    $cases['single_byte'] = [chr(0)];
    $cases['null_byte'] = ["\0"];
    $cases['all_null_bytes'] = [str_repeat("\0", 100)];

    // Special characters
    $cases['json_special'] = ['{"key":"value with \"quotes\" and \\\backslashes\\\"}'];
    $cases['newlines'] = ["line1\nline2\rline3\r\nline4"];
    $cases['tabs'] = ["col1\tcol2\tcol3"];
    $cases['unicode_emoji'] = ['🎉🔐💻🚀'];
    $cases['unicode_chinese'] = ['中文测试'];
    $cases['unicode_arabic'] = ['اختبار عربي'];
    $cases['unicode_russian'] = ['Русский тест'];

    // Boundary lengths (power of 2 boundaries)
    $cases['length_15'] = [str_repeat('x', 15)];  // Just under block size
    $cases['length_16'] = [str_repeat('x', 16)];  // Exactly block size
    $cases['length_17'] = [str_repeat('x', 17)];  // Just over block size
    $cases['length_255'] = [str_repeat('x', 255)];
    $cases['length_256'] = [str_repeat('x', 256)];
    $cases['length_1024'] = [str_repeat('x', 1024)];
    $cases['length_4096'] = [str_repeat('x', 4096)];

    // Work entry-like JSON payloads
    $cases['work_entry_minimal'] = [json_encode(['d' => '2025-01-15', 's' => 'S123456789', 'h' => 0, 'l' => 0, 't' => 0])];
    $cases['work_entry_typical'] = [json_encode(['d' => '2025-01-15', 's' => 'S123456789', 'h' => 8.5, 'l' => 0.5, 't' => 0, 'n' => 'Some notes here'])];
    $cases['work_entry_max_hours'] = [json_encode(['d' => '2025-01-15', 's' => 'S123456789', 'h' => 24, 'l' => 0, 't' => 0])];
    $cases['work_entry_long_notes'] = [json_encode(['d' => '2025-01-15', 's' => 'S123456789', 'h' => 8, 'l' => 0, 't' => 0, 'n' => str_repeat('Long note ', 100)])];

    return $cases;
  }

  /**
   * Test: Multiple random encryptions all decrypt correctly.
   */
  public function testMultipleRandomEncryptionsDecryptCorrectly(): void
  {
    $iterations = 50;

    for ($i = 0; $i < $iterations; ++$i) {
      $key = random_bytes(32);
      $nonce = random_bytes(12);
      $plaintext = self::generateRandomString(random_int(10, 500));

      $tag = '';
      $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
      $combined = $ciphertext.$tag;

      $tagLen = 16;
      $ctLen = strlen($combined) - $tagLen;
      $decrypted = openssl_decrypt(
        substr($combined, 0, $ctLen),
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $nonce,
        substr($combined, $ctLen, $tagLen)
      );

      $this->assertSame(
        $plaintext,
        $decrypted,
        "Iteration {$i}: Decrypted text should match original"
      );
    }
  }

  /**
   * Test: Envelope roundtrip with random work entries.
   */
  public function testEnvelopeRoundtripWithRandomWorkEntries(): void
  {
    $iterations = 20;

    for ($i = 0; $i < $iterations; ++$i) {
      $key = random_bytes(32);
      $nonce = random_bytes(12);

      // Generate random work entry
      $entry = [
          'd' => date('Y-m-d', strtotime('-'.random_int(0, 365).' days')),
          's' => 'S'.random_int(100000000, 999999999),
          'h' => round(random_int(0, 240) / 10, 1),
          'l' => round(random_int(0, 50) / 10, 1),
          't' => round(random_int(0, 50) / 10, 1),
      ];

      $plaintext = json_encode($entry);

      // Encrypt
      $tag = '';
      $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
      $combined = $ciphertext.$tag;

      // Create envelope
      $envelope = json_encode([
          'version' => 1,
          'ciphertext' => base64_encode($combined),
          'nonce' => base64_encode($nonce),
          'aad' => $entry['s'],
      ]);

      // Decode envelope
      $decodedEnvelope = json_decode($envelope, true);
      $this->assertIsArray($decodedEnvelope, "Iteration {$i}: Envelope should decode to array");

      // Decode and decrypt
      $decodedCombined = base64_decode($decodedEnvelope['ciphertext'], true);
      $decodedNonce = base64_decode($decodedEnvelope['nonce'], true);

      $tagLen = 16;
      $ctLen = strlen($decodedCombined) - $tagLen;
      $decrypted = openssl_decrypt(
        substr($decodedCombined, 0, $ctLen),
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $decodedNonce,
        substr($decodedCombined, $ctLen, $tagLen)
      );

      $this->assertNotFalse($decrypted, "Iteration {$i}: Decryption should succeed");

      $decodedEntry = json_decode($decrypted, true);
      $this->assertIsArray($decodedEntry, "Iteration {$i}: Decrypted JSON should be valid");
      $this->assertEquals($entry['d'], $decodedEntry['d'], "Iteration {$i}: Date should match");
      $this->assertEquals($entry['s'], $decodedEntry['s'], "Iteration {$i}: Site ID should match");
      $this->assertEquals($entry['h'], $decodedEntry['h'], "Iteration {$i}: Hours should match");
    }
  }

  /**
   * Test: Binary data encrypts and decrypts correctly.
   */
  public function testBinaryDataRoundtrip(): void
  {
    // All possible byte values
    $allBytes = '';
    for ($i = 0; $i < 256; ++$i) {
      $allBytes .= chr($i);
    }

    $key = random_bytes(32);
    $nonce = random_bytes(12);

    $tag = '';
    $ciphertext = openssl_encrypt($allBytes, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    $combined = $ciphertext.$tag;

    $tagLen = 16;
    $ctLen = strlen($combined) - $tagLen;
    $decrypted = openssl_decrypt(
      substr($combined, 0, $ctLen),
      'aes-256-gcm',
      $key,
      OPENSSL_RAW_DATA,
      $nonce,
      substr($combined, $ctLen, $tagLen)
    );

    $this->assertSame($allBytes, $decrypted, 'All byte values should roundtrip correctly');
  }

  /**
   * Test: Very long plaintext (1MB).
   */
  public function testVeryLongPlaintextRoundtrip(): void
  {
    $plaintext = str_repeat('x', 1024 * 1024); // 1MB

    $key = random_bytes(32);
    $nonce = random_bytes(12);

    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    $combined = $ciphertext.$tag;

    $tagLen = 16;
    $ctLen = strlen($combined) - $tagLen;
    $decrypted = openssl_decrypt(
      substr($combined, 0, $ctLen),
      'aes-256-gcm',
      $key,
      OPENSSL_RAW_DATA,
      $nonce,
      substr($combined, $ctLen, $tagLen)
    );

    $this->assertSame(strlen($plaintext), strlen($decrypted), 'Decrypted length should match');
    $this->assertSame($plaintext, $decrypted, 'Large plaintext should roundtrip correctly');
  }

  /**
   * Test: Auth tag is always 16 bytes.
   */
  public function testAuthTagIsAlways16Bytes(): void
  {
    $key = random_bytes(32);
    $nonce = random_bytes(12);

    $plaintexts = [
        '',
        'x',
        str_repeat('x', 15),
        str_repeat('x', 16),
        str_repeat('x', 17),
        str_repeat('x', 1000),
    ];

    foreach ($plaintexts as $i => $plaintext) {
      $tag = '';
      openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);

      $this->assertEquals(16, strlen($tag), "Case {$i}: Auth tag should always be 16 bytes");
    }
  }

  /**
   * Helper: Generate random ASCII string.
   */
  private static function generateRandomString(int $length): string
  {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 !@#$%^&*()_+-=[]{}|;:,.<>?';
    $result = '';
    $max = strlen($chars) - 1;

    for ($i = 0; $i < $length; ++$i) {
      $result .= $chars[random_int(0, $max)];
    }

    return $result;
  }

  /**
   * Helper: Generate random UTF-8 string.
   */
  private static function generateRandomUtf8String(int $length): string
  {
    $result = '';
    $chars = [];

    // Build array of UTF-8 characters
    for ($i = 0x20; $i <= 0xD7FF; ++$i) {
      $chars[] = mb_chr($i, 'UTF-8');
    }
    for ($i = 0xE000; $i <= 0xFFFF; ++$i) {
      $chars[] = mb_chr($i, 'UTF-8');
    }

    $max = count($chars) - 1;

    for ($i = 0; $i < $length; ++$i) {
      $result .= $chars[random_int(0, $max)];
    }

    return $result;
  }
}
