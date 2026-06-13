<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * Hard policy boundary: org services must never aggregate personal or delegated work.
 *
 * Only org-owned, org-only sites with payroll-visible membership may enter
 * business snapshots, financial rollups, or locked-period metrics.
 */
final class BusinessWorkVisibilityPolicy
{
  public const REFUSAL_PERSONAL_SITE = 'personal_site';
  public const REFUSAL_SHARED_SITE = 'shared_site';
  public const REFUSAL_IMPORTED_PERSONAL = 'imported_personal';
  public const REFUSAL_NO_ORG_OWNERSHIP = 'no_org_ownership';
  public const REFUSAL_PAYROLL_VISIBILITY = 'payroll_visibility';
  public const REFUSAL_MISSING_SITE = 'missing_site';
  public const REFUSAL_NOT_ORG_ONLY = 'not_org_only';

  /**
   * Hard invariant gate: org financial snapshots/aggregates require payroll-visible
   * membership and at least one org-owned org-only site on the business.
   *
   * @param array<string, string> $relationship
   * @param array<string, array{
   *   site_owner_uuid: string,
   *   site_id: string,
   *   site_hash: array<string, string>
   * }> $orgSiteIndex
   */
  public static function canAggregateForOrg(
    string $businessId,
    string $memberUuid,
    array $relationship,
    array $orgSiteIndex = [],
  ): bool {
    return self::evaluateOrgAggregation($businessId, $memberUuid, $relationship, $orgSiteIndex)['allowed'];
  }

  /**
   * @param array<string, string> $relationship
   * @param array<string, array{
   *   site_owner_uuid: string,
   *   site_id: string,
   *   site_hash: array<string, string>
   * }> $orgSiteIndex
   * @return array{allowed: bool, reason: string}
   */
  public static function evaluateOrgAggregation(
    string $businessId,
    string $memberUuid,
    array $relationship,
    array $orgSiteIndex = [],
  ): array {
    $businessId = trim($businessId);
    $memberUuid = trim($memberUuid);
    if ($businessId === '' || $memberUuid === '') {
      return ['allowed' => false, 'reason' => self::REFUSAL_NO_ORG_OWNERSHIP];
    }

    if (!self::relationshipPermitsPayrollVisibility($relationship)) {
      return ['allowed' => false, 'reason' => self::REFUSAL_PAYROLL_VISIBILITY];
    }

    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    $businessOwnerUuid = trim((string) ($business['owner_uuid'] ?? ''));
    if ($business === [] || $businessOwnerUuid === '') {
      return ['allowed' => false, 'reason' => self::REFUSAL_NO_ORG_OWNERSHIP];
    }

    if ($orgSiteIndex === []) {
      $orgSiteIndex = self::buildOrgSiteIndex($businessId);
    }

    if ($orgSiteIndex === []) {
      return ['allowed' => false, 'reason' => self::REFUSAL_NOT_ORG_ONLY];
    }

    return ['allowed' => true, 'reason' => ''];
  }

  /**
   * @param array<string, string> $relationship
   */
  public static function relationshipPermitsPayrollVisibility(array $relationship): bool
  {
    if ($relationship === []) {
      return false;
    }

    $status = strtolower(trim((string) ($relationship['status'] ?? '')));
    if (!in_array($status, [
      BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
      BusinessDiscoveryService::MEMBERSHIP_STATE_CONSENTED,
    ], true)) {
      return false;
    }

    $role = strtolower(trim((string) ($relationship['role'] ?? '')));
    if (in_array($role, ['owner', 'coordinator'], true)) {
      return true;
    }

    $scopes = self::scopeMap((string) ($relationship['scopes'] ?? ''));

    return isset($scopes['work.read']) && isset($scopes['work.scope.org']);
  }

  /**
   * @param array<string, string> $siteHash
   */
  public static function siteIsOrgOwnedOrgOnly(
    string $businessId,
    string $businessOwnerUuid,
    string $siteOwnerUuid,
    array $siteHash,
  ): bool {
    $businessId = trim($businessId);
    $businessOwnerUuid = trim($businessOwnerUuid);
    $siteOwnerUuid = trim($siteOwnerUuid);
    if ($businessId === '') {
      return false;
    }

    $ownershipScope = strtolower(trim((string) ($siteHash['ownership_scope'] ?? '')));
    if ($ownershipScope === BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_LINKED) {
      return false;
    }

    if ($ownershipScope === BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_SHARED) {
      return false;
    }

    $storedBusinessId = trim((string) ($siteHash['business_id'] ?? ''));
    if ($storedBusinessId !== '' && $storedBusinessId !== $businessId) {
      return false;
    }

    $businessManaged = strtolower(trim((string) ($siteHash['business_managed'] ?? '')));
    $isBusinessManaged = $ownershipScope === BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_BUSINESS
      || in_array($businessManaged, ['1', 'true', 'yes'], true);

    if (!$isBusinessManaged) {
      return false;
    }

    if ($storedBusinessId !== '' && $storedBusinessId !== $businessId) {
      return false;
    }

    return $siteOwnerUuid === $businessOwnerUuid || $storedBusinessId === $businessId;
  }

  /**
   * @param array<string, string> $entry
   * @param array<string, array{
   *   site_owner_uuid: string,
   *   site_id: string,
   *   site_hash: array<string, string>
   * }> $orgSiteIndex
   * @param array<string, string> $relationship
   * @return array{allowed: bool, reason: string}
   */
  public static function evaluateWorkEntry(
    string $businessId,
    string $businessOwnerUuid,
    string $memberUuid,
    string $workKey,
    array $entry,
    array $relationship,
    array $orgSiteIndex,
  ): array {
    if (!self::relationshipPermitsPayrollVisibility($relationship)) {
      return ['allowed' => false, 'reason' => self::REFUSAL_PAYROLL_VISIBILITY];
    }

    $siteId = self::resolveSiteIdFromWorkKey($workKey, $entry);
    if ($siteId === '') {
      return ['allowed' => false, 'reason' => self::REFUSAL_MISSING_SITE];
    }

    if (!isset($orgSiteIndex[$siteId])) {
      return ['allowed' => false, 'reason' => self::REFUSAL_IMPORTED_PERSONAL];
    }

    $siteContext = $orgSiteIndex[$siteId];
    $siteOwnerUuid = $siteContext['site_owner_uuid'];
    $siteHash = $siteContext['site_hash'];

    $ownershipScope = strtolower(trim((string) ($siteHash['ownership_scope'] ?? '')));
    if ($ownershipScope === BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_LINKED) {
      return ['allowed' => false, 'reason' => self::REFUSAL_PERSONAL_SITE];
    }

    if ($ownershipScope === BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_SHARED) {
      return ['allowed' => false, 'reason' => self::REFUSAL_SHARED_SITE];
    }

    if (!self::siteIsOrgOwnedOrgOnly($businessId, $businessOwnerUuid, $siteOwnerUuid, $siteHash)) {
      return ['allowed' => false, 'reason' => self::REFUSAL_NO_ORG_OWNERSHIP];
    }

    return ['allowed' => true, 'reason' => ''];
  }

  /**
   * @return array<string, array{
   *   site_owner_uuid: string,
   *   site_id: string,
   *   site_hash: array<string, string>
   * }>
   */
  public static function buildOrgSiteIndex(string $businessId): array
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return [];
    }

    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    $businessOwnerUuid = trim((string) ($business['owner_uuid'] ?? ''));

    $entries = [];
    $sitesRaw = BusinessWorkspaceCache::getSitesRaw($businessId);
    if ($sitesRaw !== null) {
      $entries = $sitesRaw['entries'];
    } else {
      $siteRefs = Database::smembers(Keys::BUSINESS_SITE . ':' . $businessId);
      $siteKeys = [];
      foreach ($siteRefs as $ref) {
        $ref = (string) $ref;
        $parts = explode(':', $ref, 2);
        if (count($parts) !== 2) {
          continue;
        }
        [$ownerUUID, $siteId] = $parts;
        $siteKeys[$ref] = Keys::SITE . ':' . $ownerUUID . ':' . $siteId;
      }

      $siteHashes = $siteKeys !== [] ? Database::pipelineHgetall(array_values($siteKeys)) : [];
      foreach ($siteRefs as $ref) {
        $ref = (string) $ref;
        $parts = explode(':', $ref, 2);
        if (count($parts) !== 2) {
          continue;
        }
        [$ownerUUID, $siteId] = $parts;
        $siteKey = $siteKeys[$ref] ?? '';
        if ($siteKey === '') {
          continue;
        }
        $siteHash = $siteHashes[$siteKey] ?? [];
        if ($siteHash === []) {
          continue;
        }

        $entries[] = [
          'site_owner_uuid' => $ownerUUID,
          'site_id' => $siteId,
          'site_hash' => $siteHash,
        ];
      }
    }

    $index = [];
    foreach ($entries as $entry) {
      $siteId = $entry['site_id'];
      $siteOwnerUuid = $entry['site_owner_uuid'];
      $siteHash = $entry['site_hash'];

      if (!self::siteIsOrgOwnedOrgOnly($businessId, $businessOwnerUuid, $siteOwnerUuid, $siteHash)) {
        continue;
      }

      $index[$siteId] = [
        'site_owner_uuid' => $siteOwnerUuid,
        'site_id' => $siteId,
        'site_hash' => $siteHash,
      ];
    }

    return $index;
  }

  /**
   * @param array<string, string> $entry
   */
  public static function resolveSiteIdFromWorkKey(string $workKey, array $entry): string
  {
    $keyParts = explode(':', $workKey);
    $isArchived = isset($keyParts[1]) && $keyParts[1] === 'archived';

    $siteIdFromKey = $isArchived ? (string) ($keyParts[4] ?? '') : (string) ($keyParts[3] ?? '');
    if ($siteIdFromKey !== '') {
      return $siteIdFromKey;
    }

    return (string) ($entry['site_id'] ?? '');
  }

  /**
   * @return array<string, true>
   */
  private static function scopeMap(string $scopeCsv): array
  {
    $scopes = array_filter(array_map('trim', explode(',', $scopeCsv)));
    $map = [];
    foreach ($scopes as $scope) {
      $map[$scope] = true;
    }

    return $map;
  }
}
