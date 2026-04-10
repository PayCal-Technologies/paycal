<?php declare(strict_types=1);

namespace PayCal\Domain\Encryption;

use PayCal\Domain\InvalidArgumentException;

/**
 * EnvelopeFormat.php
 *
 * Purpose: Define the EnvelopeFormat class for PayCal\Domain\Encryption.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain\Encryption
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Class EnvelopeFormat
 *
 * Static factory for creating and managing encrypted data envelopes.
 * Envelopes contain version, nonce, ciphertext, and optional additional authenticated data.
 */
class EnvelopeFormat
{
  public const ENVELOPE_SCHEMA_VERSION = 1;

  /**
   * @return array{version: int, nonce: string, ciphertext: string, aad: ?string}
   */
  public static function create(
    int $version,
    string $nonce,
    string $ciphertext,
    ?string $aad = null
  ): array {
    if ($version < 1) {
      throw new InvalidArgumentException('Envelope version must be >= 1');
    }
    if (empty($nonce)) {
      throw new InvalidArgumentException('Nonce cannot be empty');
    }
    if (empty($ciphertext)) {
      throw new InvalidArgumentException('Ciphertext cannot be empty');
    }

    return [
        'version' => $version,
        'nonce' => $nonce,
        'ciphertext' => $ciphertext,
        'aad' => $aad,
    ];
  }

  /**
   * @param array<string, mixed> $envelope
   */
  public static function isValid(array $envelope): bool
  {
    if (!isset($envelope['version']) || !is_int($envelope['version']) || $envelope['version'] < 1) {
      return false;
    }
    if (!isset($envelope['nonce']) || !is_string($envelope['nonce']) || empty($envelope['nonce'])) {
      return false;
    }
    if (!isset($envelope['ciphertext']) || !is_string($envelope['ciphertext']) || empty($envelope['ciphertext'])) {
      return false;
    }
    if (array_key_exists('aad', $envelope) && !is_null($envelope['aad']) && !is_string($envelope['aad'])) {
      return false;
    }

    return true;
  }

  /**
   * @param array<string, mixed> $envelope
   */
  public static function validateOrThrow(array $envelope): void
  {
    if (!self::isValid($envelope)) {
      throw new InvalidArgumentException('Invalid encryption envelope structure');
    }
  }

  /**
   * @param array<string, mixed> $envelope
   */
  public static function toJson(array $envelope): string
  {
    self::validateOrThrow($envelope);

    $result = json_encode($envelope, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($result === false) {
      throw new InvalidArgumentException('Failed to encode envelope to JSON');
    }
    return $result;
  }

  /**
   * @return array{version: int, nonce: string, ciphertext: string, aad: string|null}
   */
  public static function fromJson(string $json): array
  {
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
      throw new InvalidArgumentException('Failed to decode envelope JSON');
    }

    $envelope = [];
    foreach ($decoded as $k => $v) {
      $envelope[(string) $k] = $v;
    }
    self::validateOrThrow($envelope);

    /** @var array{version: int, nonce: string, ciphertext: string, aad: string|null} */
    return $envelope;
  }

  /**
   * @param array<string, mixed> $envelope
   */
  public static function getVersion(array $envelope): int
  {
    $version = $envelope['version'] ?? 0;

    return is_numeric($version) ? (int) $version : 0;
  }

  /**
   * @param array<string, mixed> $envelope
   */
  public static function getNonce(array $envelope): string
  {
    $nonce = $envelope['nonce'] ?? '';

    return is_scalar($nonce) ? (string) $nonce : '';
  }

  /**
   * @param array<string, mixed> $envelope
   */
  public static function getCiphertext(array $envelope): string
  {
    $ciphertext = $envelope['ciphertext'] ?? '';

    return is_scalar($ciphertext) ? (string) $ciphertext : '';
  }

  /**
   * @param array<string, mixed> $envelope
   */
  public static function getAAD(array $envelope): ?string
  {
    $aad = $envelope['aad'] ?? null;
    if ($aad === null) {
      return null;
    }

    return is_scalar($aad) ? (string) $aad : null;
  }
}
