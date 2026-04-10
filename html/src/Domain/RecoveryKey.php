<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * RecoveryKey.php
 *
 * Purpose: Recovery-key domain helper for generation, encoding, proof support,
 * KEK derivation, and DEK wrap/unwrap operations.
 *
 * Developer notes:
 * - Recovery-key behavior is security-critical; encoding, proof, and wrapping
 *   semantics must remain stable once issued to users.
 * - Do not introduce incompatible format changes without a migration story.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Recovery-key crypto helper.
 *
 * Responsibilities:
 * - Generate and format recovery-key material.
 * - Derive proof and wrapping keys from recovery secrets.
 * - Support recovery-key-based DEK protection workflows.
 */
final class RecoveryKey
{
  public const PROOF_LABEL = 'paycal-recovery-proof-v1';

  // Crockford Base32 alphabet (32 characters, excludes I, L, O, U to prevent confusion)
  private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

  /**
   * Generate 256-bit recovery key (returns raw bytes)
   *
   * @return string 32 random bytes
   */
  public static function generate(): string
  {
    return random_bytes(32);
  }

  /**
   * Generate account recovery salt (32 bytes)
   *
   * @return string 32 random bytes
   */
  public static function generateRecoverySalt(): string
  {
    return random_bytes(32);
  }

  /**
   * Encode bytes using Crockford Base32
   *
   * @param string $data Raw bytes to encode
   *
   * @return string Crockford Base32 encoded string (uppercase, no dashes)
   */
  public static function encodeCrockford(string $data): string
  {
    $encoded = '';
    $bits = 0;
    $value = 0;

    for ($i = 0; $i < strlen($data); $i++) {
      $value = ($value << 8) | ord($data[$i]);
      $bits += 8;

      while ($bits >= 5) {
        $bits -= 5;
        $encoded .= self::ALPHABET[($value >> $bits) & 0x1F];
      }
    }

    // Handle remaining bits
    if ($bits > 0) {
      $encoded .= self::ALPHABET[($value << (5 - $bits)) & 0x1F];
    }

    return $encoded;
  }

  /**
   * Decode Crockford Base32 to bytes
   *
   * @param string $encoded Crockford Base32 encoded string (with or without dashes)
   *
   * @return string Raw bytes
   *
   * @throws \InvalidArgumentException if invalid character found
   */
  public static function decodeCrockford(string $encoded): string
  {
    $normalized = self::normalize($encoded);
    $decoded = '';
    $bits = 0;
    $value = 0;

    for ($i = 0; $i < strlen($normalized); $i++) {
      $char = $normalized[$i];
      $idx = strpos(self::ALPHABET, $char);

      if ($idx === false) {
        throw new \InvalidArgumentException("Invalid character in recovery key: {$char}");
      }

      $value = ($value << 5) | $idx;
      $bits += 5;

      if ($bits >= 8) {
        $bits -= 8;
        $decoded .= chr(($value >> $bits) & 0xFF);
      }
    }

    return $decoded;
  }

  /**
   * Format encoded string as grouped blocks
   * 52 characters → 8 groups of 4 + 1 group of 4 remainder = 13 groups total
   * Example: AB3F-9K2L-M7QX-D4ZT-Y8WP-6BRC-J2ND-T4GH...
   *
   * @param string $encoded Crockford Base32 encoded string
   *
   * @return string Formatted with dashes every 4 characters
   */
  public static function format(string $encoded): string
  {
    return implode('-', str_split($encoded, 4));
  }

  /**
   * Normalize user input (remove dashes, uppercase, trim)
   *
   * @param string $input User-provided recovery key
   *
   * @return string Normalized string ready for decoding
   */
  public static function normalize(string $input): string
  {
    return strtoupper(str_replace(['-', ' '], '', trim($input)));
  }

  /**
   * Derive recovery KEK via HKDF-SHA256
   *
   * @param string $recoveryKeyBytes Raw recovery key bytes (after decoding Crockford Base32)
   * @param string $saltBase64       Account recovery salt (base64 encoded)
   *
   * @return string 32-byte KEK
   */
  public static function deriveKEK(string $recoveryKeyBytes, string $saltBase64): string
  {
    $salt = base64_decode($saltBase64);

    return hash_hkdf('sha256', $recoveryKeyBytes, 32, 'paycal-recovery-kek', $salt);
  }

  /**
   * Handles deriveProofKey operation.
   */
  public static function deriveProofKey(string $recoveryKeyBytes, string $saltBase64): string
  {
    $salt = base64_decode($saltBase64, true);
    if ($salt === false) {
      throw new \InvalidArgumentException('Invalid recovery proof salt');
    }

    return hash_hkdf('sha256', $recoveryKeyBytes, 32, self::PROOF_LABEL, $salt);
  }

  /**
   * Handles buildProofMessage operation.
   */
  public static function buildProofMessage(string $txnId, string $proofNonce, string $clientFingerprintHash): string
  {
    return implode('|', [self::PROOF_LABEL, $txnId, $proofNonce, $clientFingerprintHash]);
  }

  /**
   * Handles generateProof operation.
   */
  public static function generateProof(string $proofKeyBase64, string $txnId, string $proofNonce, string $clientFingerprintHash): string
  {
    $proofKey = base64_decode($proofKeyBase64, true);
    if ($proofKey === false) {
      throw new \InvalidArgumentException('Invalid recovery proof key');
    }

    return base64_encode(hash_hmac('sha256', self::buildProofMessage($txnId, $proofNonce, $clientFingerprintHash), $proofKey, true));
  }

  /**
  * Wrap DEK with recovery KEK using AES-256-GCM
   *
   * @param string $dekBase64   DEK to wrap (base64 encoded)
   * @param string $recoveryKEK Recovery KEK derived from recovery key
   *
   * @return string Base64-encoded JSON envelope
   *
   * @throws \RuntimeException if encryption fails
   */
  public static function wrapDEK(string $dekBase64, string $recoveryKEK): string
  {
    $dek = base64_decode($dekBase64);

    // Use AES-256-GCM (universally supported)
    $nonce = random_bytes(12); // AES-GCM uses 12-byte nonce
    $tag = '';
    
    $ciphertext = openssl_encrypt(
      $dek,
      'aes-256-gcm',
      $recoveryKEK,
      OPENSSL_RAW_DATA,
      $nonce,
      $tag
    );

    if ($ciphertext === false) {
      throw new \RuntimeException('Failed to encrypt DEK with AES-256-GCM');
    }

    return base64_encode(json_encode([
        'version' => 1,
        'nonce' => base64_encode($nonce),
        'ciphertext' => base64_encode($ciphertext . $tag),
    ], JSON_THROW_ON_ERROR));
  }

  /**
   * Unwrap DEK with recovery KEK
   *
   * @param string $wrappedDekBase64 Wrapped DEK envelope (base64 encoded)
   * @param string $recoveryKEK      Recovery KEK derived from recovery key
   *
   * @return string Raw DEK bytes
   *
   * @throws \RuntimeException if decryption fails
   * @throws \InvalidArgumentException if envelope is invalid
   */
  public static function unwrapDEK(string $wrappedDekBase64, string $recoveryKEK): string
  {
    $envelope = json_decode(base64_decode($wrappedDekBase64), true);

    if (!is_array($envelope) || !isset($envelope['nonce'], $envelope['ciphertext'])) {
      throw new \InvalidArgumentException('Invalid recovery envelope structure');
    }

    if (!is_string($envelope['nonce']) || !is_string($envelope['ciphertext'])) {
      throw new \InvalidArgumentException('Invalid recovery envelope payload');
    }

    $nonce = base64_decode($envelope['nonce'], true);
    $ciphertextWithTag = base64_decode($envelope['ciphertext'], true);

    if ($nonce === false || $ciphertextWithTag === false) {
      throw new \InvalidArgumentException('Invalid recovery envelope encoding');
    }

    // AES-256-GCM (12-byte nonce, 16-byte tag)
    $ciphertext = substr($ciphertextWithTag, 0, -16);
    $tag = substr($ciphertextWithTag, -16);
    
    $dek = openssl_decrypt(
      $ciphertext,
      'aes-256-gcm',
      $recoveryKEK,
      OPENSSL_RAW_DATA,
      $nonce,
      $tag
    );

    if ($dek === false) {
      throw new \RuntimeException('Failed to decrypt DEK with recovery key');
    }

    return $dek;
  }

  /**
   * Validate recovery key format
   *
   * @param string $input Recovery key to validate (formatted or unformatted)
   *
   * @return bool True if valid, false otherwise
   */
  public static function validate(string $input): bool
  {
    try {
      $normalized = self::normalize($input);
      
      // Crockford Base32 encoding of 32 bytes produces 52 characters
      // (32 bytes * 8 bits/byte = 256 bits ÷ 5 bits/char = 51.2 → 52 chars)
      if (strlen($normalized) !== 52) {
        return false;
      }
      
      // Try to decode - will throw if invalid characters
      $decoded = self::decodeCrockford($normalized);
      
      // Must decode to exactly 32 bytes
      return strlen($decoded) === 32;
    } catch (\Throwable) {
      return false;
    }
  }
}

