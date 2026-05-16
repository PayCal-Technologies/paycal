<?php declare(strict_types=1);

namespace PayCal\Domain;
use PayCal\Domain\Constants\Keys;

/**
 * KEK.php
 *
 * Purpose: Key Encryption Key (KEK) lifecycle model: identity, ownership, and
 *          rotation contracts that remain independent of user identity.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * KEK - Key Encryption Key Management.
 *
 * Formal ownership model that decouples KEK identity from user identity.
 * This prevents architectural issues during user migrations, account merges,
 * and key rotation scenarios.
 *
 * Architecture:
 *
 *   User ──owns──> KEK ──encrypts──> DEKs
 *
 * Why This Matters:
 * - User UUIDs may change (migration, consolidation)
 * - KEKs have independent lifecycle (creation, rotation, revocation)
 * - Multiple KEKs per user (device-specific, purpose-specific)
 * - Audit trail requires distinct KEK identity
 *
 * Schema (Future):
 * ```
 * kek:{kek_id}
 *   kek_id: UUID
 *   owner_user_id: UUID
 *   created_at: timestamp
 *   updated_at: timestamp
 *   version: int
 *   status: active|rotated|revoked
 *   salt: base64
 *   wrapped_dek: base64
 *   dek_version: int
 *
 * user_kek:{user_uuid} -> {kek_id}  (index)
 * ```
 *
 * Migration Path:
 * Currently using: `user:kek:v1:{user_uuid}`
 * Future state: `kek:{kek_id}` with `user_kek:{user_uuid}` index
 */
class KEK
{
  public const STATUS_ACTIVE = 'active';
  public const STATUS_ROTATED = 'rotated';
  public const STATUS_REVOKED = 'revoked';

  /**
   * Create a new KEK record with explicit ownership.
   *
   * @param string $userId Owner user UUID
   * @param string $salt   Password derivation salt (base64)
   * @return array{kek_id: string, key: string} KEK metadata
   */
  public static function create(string $userId, string $salt): array
  {
    $kekId = self::generateKekId($userId);
    $timestamp = Clock::now();

    $kekData = [
      'kek_id' => $kekId,
      'owner_user_id' => $userId,
      'salt' => $salt,
      'dek_version' => '1',
      'status' => self::STATUS_ACTIVE,
      'created_at' => (string) $timestamp,
      'updated_at' => (string) $timestamp,
      'version' => '1',
    ];

    // Store in legacy format for now (backward compatibility)
    $legacyKey = self::getLegacyKey($userId);
    Database::storeRecord($legacyKey, $kekData);

    // TODO: Also create user_kek index when migrating to new schema
    // Database::set("user_kek:{$userId}", $kekId);

    return [
      'kek_id' => $kekId,
      'key' => $legacyKey,
    ];
  }

  /**
   * Get KEK by user ID (current implementation).
   *
   * @param string $userId User UUID
   * @return null|array<string, string> KEK data or null
   */
  public static function getByUserId(string $userId): ?array
  {
    $key = self::getLegacyKey($userId);
    $data = Database::fetchRecord($key);

    return !empty($data) ? $data : null;
  }

  /**
   * Update KEK metadata.
   *
   * @param string               $userId User UUID
   * @param array<string,string> $fields Fields to update
   */
  public static function update(string $userId, array $fields): void
  {
    $key = self::getLegacyKey($userId);

    // Always update timestamp
    $fields['updated_at'] = (string) Clock::now();

    Database::storeRecord($key, $fields);
  }

  /**
   * Store wrapped DEK.
   *
   * @param string $userId     User UUID
   * @param string $wrappedDek Base64-encoded wrapped DEK
   * @param int    $dekVersion DEK version number
   */
  public static function storeWrappedDek(string $userId, string $wrappedDek, int $dekVersion): void
  {
    self::update($userId, [
      'wrapped_dek' => $wrappedDek,
      'dek_version' => (string) $dekVersion,
    ]);
  }

  /**
   * Get wrapped DEK.
   *
   * @param string $userId User UUID
   * @return null|array{wrapped_dek: string, dek_version: int} Wrapped DEK data
   */
  public static function getWrappedDek(string $userId): ?array
  {
    $kek = self::getByUserId($userId);

    if (empty($kek) || !isset($kek['wrapped_dek'])) {
      return null;
    }

    return [
      'wrapped_dek' => $kek['wrapped_dek'],
      'dek_version' => (int) ($kek['dek_version'] ?? 1),
    ];
  }

  /**
   * Mark KEK as rotated (for future key rotation).
   *
   * @param string $userId User UUID
   */
  public static function markRotated(string $userId): void
  {
    self::update($userId, ['status' => self::STATUS_ROTATED]);
  }

  /**
   * Revoke a KEK (emergency key compromise).
   *
   * @param string $userId User UUID
   */
  public static function revoke(string $userId): void
  {
    self::update($userId, ['status' => self::STATUS_REVOKED]);
  }

  /**
   * Check if KEK exists and is active.
   *
   * @param string $userId User UUID
   * @return bool True if active KEK exists
   */
  public static function isActive(string $userId): bool
  {
    $kek = self::getByUserId($userId);

    if (null === $kek) {
      return false;
    }

    $status = $kek['status'] ?? self::STATUS_ACTIVE;

    return $status === self::STATUS_ACTIVE;
  }

  /**
   * Get legacy Redis key format.
   *
   * @param string $userId User UUID
   * @return string Redis key
   */
  private static function getLegacyKey(string $userId): string
  {
    return Keys::userKekV1($userId);
  }

  /**
   * Generate a deterministic KEK ID from user ID.
   * In future, this should be a true UUID independent of user ID.
   *
   * @param string $userId User UUID
   * @return string KEK ID
   */
  private static function generateKekId(string $userId): string
  {
    // TODO: In future schema, use independent UUID
    // For now, derive from user ID for backward compatibility
    return hash('sha256', "kek:{$userId}:" . Clock::now());
  }

  /**
   * Migrate user KEK to new schema (future implementation).
   *
   * @param string $userId User UUID
   * @return bool True if migration successful
   */
  public static function migrateToNewSchema(string $userId): bool
  {
    // TODO: Implement when ready to migrate from user:kek:v1:{uuid} to kek:{kek_id}
    // 1. Read from legacy key
    // 2. Generate new kek_id
    // 3. Write to new key format
    // 4. Create user_kek index
    // 5. Keep legacy key for fallback
    // 6. Return true on success

    return false;
  }

  /**
   * Get all KEKs for a user (future: for multi-device support).
   *
   * @param string $userId User UUID
   * @return list<array<string, string>> Array of KEK records
   */
  public static function getAllForUser(string $userId): array
  {
    // TODO: When supporting multiple KEKs per user:
    // $kekIds = Database::smembers("user_keks:{$userId}");
    // return array_map(fn($id) => self::getById($id), $kekIds);

    // For now, return single KEK
    $kek = self::getByUserId($userId);

    return $kek ? [$kek] : [];
  }
}
