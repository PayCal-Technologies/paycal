<?php declare(strict_types=1);

namespace PayCal\Domain\Business;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\UserRepository;

/**
 * Search index containing only approved, publicly listable businesses.
 */
final class BusinessSearchIndex
{
  /**
   * TODO: Document entryKey.
   */
  public static function entryKey(string $businessId): string
  {
    return Keys::BUSINESS_SEARCH_ENTRY . ':' . $businessId;
  }

  /**
   * TODO: Document sync.
   */
  public static function sync(string $businessId): void
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return;
    }

    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    $business = self::withListingTrustContext($business);
    if ($business === [] || !BusinessVisibilityPolicy::isPubliclyDiscoverable($business)) {
      self::remove($businessId);

      return;
    }

    $ownerUUID = trim((string) ($business['owner_uuid'] ?? ''));
    $owner = $ownerUUID !== '' ? UserRepository::getByUUID($ownerUUID) : null;
    $ownerEmail = $owner !== null ? InputSanitizer::sanitizeEmail($owner->email) : '';
    $displayName = BusinessVisibilityPolicy::resolveApprovedDisplayName($business);
    $searchName = mb_strtolower($displayName, 'UTF-8');

    if ($displayName === '' || $ownerEmail === '') {
      self::remove($businessId);

      return;
    }

    Database::hset(self::entryKey($businessId), [
      'business_id' => $businessId,
      'display_name' => $displayName,
      'search_name' => $searchName,
      'owner_uuid' => $ownerUUID,
      'owner_email' => $ownerEmail,
      'owner_name' => $owner !== null ? trim($owner->full_name) : '',
      'updated_at' => date('c'),
    ]);
    Database::sadd(Keys::BUSINESS_SEARCH_INDEX, $businessId);
  }

  /**
   * TODO: Document remove.
   */
  public static function remove(string $businessId): void
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return;
    }

    Database::srem(Keys::BUSINESS_SEARCH_INDEX, $businessId);
    Database::unlink(self::entryKey($businessId));
  }

  /**
   * @return array<int, array<string, string>>
   */
  public static function search(string $query, int $limit, string $excludeOwnerUUID = ''): array
  {
    $needle = mb_strtolower(trim($query));
    if (mb_strlen($needle) < 2) {
      return [];
    }

    if ($limit < 1) {
      $limit = 12;
    }
    if ($limit > 25) {
      $limit = 25;
    }

    $results = [];
    $seenEmails = [];

    foreach (Database::smembers(Keys::BUSINESS_SEARCH_INDEX) as $businessId) {
      $businessId = trim((string) $businessId);
      if ($businessId === '') {
        continue;
      }

      $entry = Database::hgetall(self::entryKey($businessId));
      if ($entry === []) {
        Database::srem(Keys::BUSINESS_SEARCH_INDEX, $businessId);
        continue;
      }

      $ownerUUID = trim((string) ($entry['owner_uuid'] ?? ''));
      if ($ownerUUID === '' || $ownerUUID === $excludeOwnerUUID) {
        continue;
      }

      $displayName = trim((string) ($entry['display_name'] ?? ''));
      $searchName = trim((string) ($entry['search_name'] ?? ''));
      $ownerEmail = trim((string) ($entry['owner_email'] ?? ''));
      $ownerName = trim((string) ($entry['owner_name'] ?? ''));

      if ($displayName === '' || $ownerEmail === '') {
        continue;
      }

      $matches = str_contains($searchName, $needle)
        || str_contains(mb_strtolower($ownerEmail, 'UTF-8'), $needle)
        || ($ownerName !== '' && str_contains(mb_strtolower($ownerName, 'UTF-8'), $needle));

      if (!$matches) {
        continue;
      }

      if (isset($seenEmails[$ownerEmail])) {
        continue;
      }

      $seenEmails[$ownerEmail] = true;
      $results[] = [
        'source' => 'business',
        'email' => $ownerEmail,
        'name' => $ownerName,
        'business_name' => $displayName,
        'business_id' => $businessId,
      ];

      if (count($results) >= $limit) {
        break;
      }
    }

    return $results;
  }

  /**
   * Rebuild the public business search index from Redis state.
   */
  public static function rebuildAll(): int
  {
    $indexed = 0;
    foreach (Database::scanKeys(Keys::BUSINESS . ':*') as $businessKey) {
      $businessId = str_replace(Keys::BUSINESS . ':', '', $businessKey);
      if ($businessId === '') {
        continue;
      }

      self::sync($businessId);
      if (Database::sismember(Keys::BUSINESS_SEARCH_INDEX, $businessId) === 1) {
        $indexed++;
      }
    }

    return $indexed;
  }

  /**
   * @param array<string, string> $business
   * @return array<string, string>
   */
  private static function withListingTrustContext(array $business): array
  {
    $snapshot = BusinessTrustPolicy::listingSponsorTrustSnapshot($business);
    $business['paid_status'] = $snapshot['paid_status'];
    if (trim((string) ($business['trust_level'] ?? '')) === '') {
      $business['trust_level'] = $snapshot['trust_level'];
    }

    $sponsorUUID = BusinessVisibilityPolicy::resolveListingSponsorUUID($business);
    if ($sponsorUUID !== '' && trim((string) ($business['listing_sponsor_uuid'] ?? '')) === '') {
      $business['listing_sponsor_uuid'] = $sponsorUUID;
    }

    return $business;
  }
}
