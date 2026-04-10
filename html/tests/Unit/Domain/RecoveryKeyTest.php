<?php declare(strict_types=1);

namespace Tests\Unit\Domain;

use PayCal\Domain\RecoveryKey;
use PHPUnit\Framework\TestCase;

/**
 * RecoveryKeyTest.php
 *
 * Unit tests for RecoveryKey class
 */
final class RecoveryKeyTest extends TestCase
{
  /**
   * Test recovery key generation produces 32 bytes
   */
  public function testGenerateProduces32Bytes(): void
  {
    $key = RecoveryKey::generate();

    $this->assertIsString($key);
    $this->assertSame(32, strlen($key));
  }

  /**
   * Test recovery salt generation produces 32 bytes
   */
  public function testGenerateRecoverySaltProduces32Bytes(): void
  {
    $salt = RecoveryKey::generateRecoverySalt();

    $this->assertIsString($salt);
    $this->assertSame(32, strlen($salt));
  }

  /**
   * Test Crockford Base32 encoding/decoding round trip
   */
  public function testCrockfordBase32RoundTrip(): void
  {
    $originalBytes = random_bytes(32);
    
    $encoded = RecoveryKey::encodeCrockford($originalBytes);
    $decoded = RecoveryKey::decodeCrockford($encoded);

    $this->assertSame($originalBytes, $decoded);
  }

  /**
   * Test Crockford Base32 encoding produces valid length
   */
  public function testCrockfordEncodingLength(): void
  {
    $bytes = random_bytes(32); // 256 bits
    $encoded = RecoveryKey::encodeCrockford($bytes);

    // 256 bits / 5 bits per char = 51.2 → 52 chars
    $this->assertGreaterThanOrEqual(51, strlen($encoded));
    $this->assertLessThanOrEqual(53, strlen($encoded));
  }

  /**
   * Test Crockford alphabet excludes I, L, O, U
   */
  public function testCrockfordAlphabetExcludesConfusingChars(): void
  {
    $bytes = random_bytes(32);
    $encoded = RecoveryKey::encodeCrockford($bytes);

    // Should not contain I, L, O, U
    $this->assertStringNotContainsString('I', $encoded);
    $this->assertStringNotContainsString('L', $encoded);
    $this->assertStringNotContainsString('O', $encoded);
    $this->assertStringNotContainsString('U', $encoded);
  }

  /**
   * Test format adds dashes every 4 characters
   */
  public function testFormatAddsDashes(): void
  {
    $encoded = 'AB3F9K2LM7QXD4ZTY8WP6BRCJ2NDT4GH'; // 32 chars example
    $formatted = RecoveryKey::format($encoded);

    $parts = explode('-', $formatted);
    
    // Each part should be 4 characters
    foreach ($parts as $part) {
      $this->assertLessThanOrEqual(4, strlen($part));
    }

    // Should have dashes
    $this->assertStringContainsString('-', $formatted);
  }

  /**
   * Test normalize removes dashes and uppercases
   */
  public function testNormalize(): void
  {
    $input = 'ab3f-9k2l-m7qx-d4zt';
    $normalized = RecoveryKey::normalize($input);

    $this->assertSame('AB3F9K2LM7QXD4ZT', $normalized);
  }

  /**
   * Test normalize handles various formats
   */
  public function testNormalizeHandlesVariousFormats(): void
  {
    $tests = [
      'AB3F-9K2L' => 'AB3F9K2L',
      '  ab3f-9k2l  ' => 'AB3F9K2L',
      'ab3f 9k2l' => 'AB3F9K2L',
      'AB3F9K2L' => 'AB3F9K2L',
    ];

    foreach ($tests as $input => $expected) {
      $this->assertSame($expected, RecoveryKey::normalize($input));
    }
  }

  /**
   * Test decode throws on invalid characters
   */
  public function testDecodeThrowsOnInvalidCharacters(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid character');

    RecoveryKey::decodeCrockford('ABCD-EFGH-ILOU'); // I, L, O, U are invalid
  }

  /**
   * Test KEK derivation is deterministic
   */
  public function testKEKDerivationIsDeterministic(): void
  {
    $recoveryKeyBytes = random_bytes(32);
    $salt = base64_encode(random_bytes(32));

    $kek1 = RecoveryKey::deriveKEK($recoveryKeyBytes, $salt);
    $kek2 = RecoveryKey::deriveKEK($recoveryKeyBytes, $salt);

    $this->assertSame($kek1, $kek2);
  }

  /**
   * Test KEK derivation produces 32 bytes
   */
  public function testKEKDerivationProduces32Bytes(): void
  {
    $recoveryKeyBytes = random_bytes(32);
    $salt = base64_encode(random_bytes(32));

    $kek = RecoveryKey::deriveKEK($recoveryKeyBytes, $salt);

    $this->assertSame(32, strlen($kek));
  }

  /**
   * Test DEK wrap/unwrap round trip
   */
  public function testDEKWrapUnwrapRoundTrip(): void
  {
    $dek = random_bytes(32);
    $dekBase64 = base64_encode($dek);
    $recoveryKeyBytes = random_bytes(32);
    $salt = base64_encode(random_bytes(32));

    // Derive KEK
    $kek = RecoveryKey::deriveKEK($recoveryKeyBytes, $salt);

    // Wrap DEK
    $wrapped = RecoveryKey::wrapDEK($dekBase64, $kek);

    // Unwrap DEK
    $unwrapped = RecoveryKey::unwrapDEK($wrapped, $kek);

    $this->assertSame($dek, $unwrapped);
  }

  /**
   * Test validate accepts valid recovery keys
   */
  public function testValidateAcceptsValidKeys(): void
  {
    $bytes = random_bytes(32);
    $encoded = RecoveryKey::encodeCrockford($bytes);
    $formatted = RecoveryKey::format($encoded);

    $this->assertTrue(RecoveryKey::validate($encoded));
    $this->assertTrue(RecoveryKey::validate($formatted));
  }

  /**
   * Test validate rejects invalid keys
   */
  public function testValidateRejectsInvalidKeys(): void
  {
    $invalid = [
      'TOO-SHORT',
      'ILOU-ILOU-ILOU-ILOU', // Contains invalid chars
      '12345', // Too short
      str_repeat('A', 100), // Too long
    ];

    foreach ($invalid as $key) {
      $this->assertFalse(RecoveryKey::validate($key), "Should reject: {$key}");
    }
  }

  /**
   * Test full workflow: generate → encode → format → normalize → decode
   */
  public function testFullWorkflow(): void
  {
    // Generate recovery key
    $keyBytes = RecoveryKey::generate();
    $this->assertSame(32, strlen($keyBytes));

    // Encode with Crockford Base32
    $encoded = RecoveryKey::encodeCrockford($keyBytes);
    $this->assertGreaterThanOrEqual(51, strlen($encoded));

    // Format for display
    $formatted = RecoveryKey::format($encoded);
    $this->assertStringContainsString('-', $formatted);

    // User input: normalize (remove dashes, uppercase)
    $normalized = RecoveryKey::normalize($formatted);
    $this->assertSame($encoded, $normalized);

    // Decode back to bytes
    $decoded = RecoveryKey::decodeCrockford($normalized);
    $this->assertSame($keyBytes, $decoded);
  }

  /**
   * Test unwrap fails with wrong KEK
   */
  public function testUnwrapFailsWithWrongKEK(): void
  {
    $this->expectException(\RuntimeException::class);

    $dek = random_bytes(32);
    $recoveryKey1 = random_bytes(32);
    $recoveryKey2 = random_bytes(32);
    $salt = base64_encode(random_bytes(32));

    $kek1 = RecoveryKey::deriveKEK($recoveryKey1, $salt);
    $kek2 = RecoveryKey::deriveKEK($recoveryKey2, $salt);

    $wrapped = RecoveryKey::wrapDEK(base64_encode($dek), $kek1);
    
    // Try to unwrap with wrong KEK
    RecoveryKey::unwrapDEK($wrapped, $kek2);
  }
}
