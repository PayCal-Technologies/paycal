<?php declare(strict_types=1);

namespace PayCal\Domain\Crypto;

/**
 * ChainVerifier.php
 *
 * Purpose: Cryptographic chain verifier: validates tamper-evident hash chains over ordered period payloads using Ed25519 signatures and SHA-256 linking.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain\Crypto
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */


final class ChainVerifier
{
  /**
   * Verify a chain of cryptographic periods.
   *
    * @param array<int, array<string, mixed>> $periods Array of period payloads
   * @return bool True if chain is valid
   */
  public static function verify(array $periods): bool
  {
    $prev = str_repeat('0', 64);

    foreach ($periods as $p) {
      // Extract version (default to 1 for backward compatibility)
      $version = $p['version'] ?? 1;
      $algorithm = $p['algorithm'] ?? 'ed25519';
      $signingKeyVersion = $p['signingKeyVersion'] ?? 1;

      // Version-specific verification
      if ($version !== 1) {
        // Future versions would be handled here
        return false;
      }

      // Verify using v1 algorithm
      if (!self::verifyV1($p, $prev)) {
        return false;
      }

      $payload = $p['payload'] ?? null;
      if (!is_array($payload)) {
        return false;
      }
      ksort($payload);
      $canonical = json_encode($payload, JSON_UNESCAPED_SLASHES);
      if ($canonical === false) {
        return false;
      }
      $payloadHash = hash('sha256', $canonical);
      $chainHash = hash('sha256', $prev . $payloadHash);

      $prev = $chainHash;
    }

    return true;
  }

  /**
   * Verify a single period using v1 algorithm (Ed25519).
   *
    * @param array<string, mixed> $period Period payload
   * @param string $prevHash Previous chain hash
   * @return bool True if valid
   */
  private static function verifyV1(array $period, string $prevHash): bool
  {
    $payload = $period['payload'] ?? null;
    if (!is_array($payload)) {
      return false;
    }
    ksort($payload);

    $canonical = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if ($canonical === false) {
      return false;
    }
    $payloadHash = hash('sha256', $canonical);
    $chainHash = hash('sha256', $prevHash . $payloadHash);

    $storedChainHash = $period['chainHash'] ?? '';
    if (!is_string($storedChainHash) || !hash_equals($storedChainHash, $chainHash)) {
      return false;
    }

    $publicKeyRaw = $period['publicKey'] ?? '';
    $signatureRaw = $period['signature'] ?? '';
    if (!is_scalar($publicKeyRaw) || !is_scalar($signatureRaw)) {
      return false;
    }
    $public = base64_decode((string) $publicKeyRaw, true);
    $sig = base64_decode((string) $signatureRaw, true);

    if (false === $public || false === $sig || '' === $public || '' === $sig) {
      return false;
    }

    return sodium_crypto_sign_verify_detached($sig, $canonical, $public);
  }

  /**
   * Create a versioned payload structure.
   *
    * @param array<string, mixed> $payload Payload data
   * @param int    $version Payload version
   * @param int    $signingKeyVersion Signing key version
   * @param string $algorithm Algorithm identifier
   * @return array<string, mixed> Versioned payload structure
   */
  public static function createVersionedPayload(
    array $payload,
    int $version = 1,
    int $signingKeyVersion = 1,
    string $algorithm = 'ed25519'
  ): array {
    return [
      'version' => $version,
      'algorithm' => $algorithm,
      'signingKeyVersion' => $signingKeyVersion,
      'payload' => $payload,
    ];
  }
}

