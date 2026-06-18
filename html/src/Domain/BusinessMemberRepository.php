<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * BusinessMemberRepository.php
 *
 * Purpose: Organization membership repository for enumerating relationships,
 * role data, and user-to-organization membership mirrors from Redis.
 *
 * Developer notes:
 * - This repository is intentionally read-focused and should not absorb access
 *   control policy from calling services or controllers.
 * - Keep key-layout assumptions aligned with the structures written by the
 *   organization discovery domain.
 *
 * Architectural role:
 * - Reusable domain repository for organization membership queries and lookup
 *   helpers consumed by higher-level services.
 * - Encapsulates membership data access outside the HTTP layer.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * BusinessMemberRepository
 *
 * Pure data-access layer for enumerating and querying org membership.
 * No authorization gating — callers (ODS, controllers) handle access control.
 *
 * Key layout (read-only mirrors of what ODS writes):
 *   organization:relationship:{orgId}:{userUUID}  – HASH: role, status, scopes, user_uuid, updated_at
 *   organization:user:{userUUID}                  – SET of active orgIds the user belongs to
 *   organization:relationships:user:{userUUID}    – SET of non-terminal org relationship ids
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */
final class BusinessMemberRepository
{
  /**
   * Enumerate all members of an organization, optionally filtered by role and/or status.
   * Each entry includes the hydrated User plus their relationship fields.
   * Members with no matching user record are silently skipped.
   *
   * Results are sorted alphabetically by full_name.
   *
   * @param  string      $businessId  Organization ID
   * @param  string|null $role        If set, only return members with this exact role
   * @param  string|null $status      If set, only return members with this exact status (e.g. 'active')
   * @return array<int, array{user: User, role: string, status: string, scopes: list<string>, updated_at: string}>
   */
  public static function forBusiness(
    string $businessId,
    ?string $role = null,
    ?string $status = null,
    bool $useCache = true,
  ): array {
    $orgId = $businessId;
    if ('' === $orgId) {
      return [];
    }

    if ($useCache && $role === null) {
      $cached = BusinessWorkspaceCache::getRoster($orgId);
      if ($cached !== null) {
        return self::filterMembers($cached, $role, $status);
      }
    }

    $memberUUIDs = Database::smembers(Keys::BUSINESS_MEMBERS . ':' . $orgId);
    if ([] === $memberUUIDs) {
      return [];
    }

    $relationshipKeys = [];
    $normalizedUUIDs = [];
    foreach ($memberUUIDs as $memberUUID) {
      $memberUUID = (string) $memberUUID;
      if ($memberUUID === '') {
        continue;
      }
      $normalizedUUIDs[] = $memberUUID;
      $relationshipKeys[$memberUUID] = Keys::BUSINESS_RELATIONSHIP . ':' . $orgId . ':' . $memberUUID;
    }

    $relationshipHashes = $relationshipKeys !== []
      ? Database::pipelineHgetall(array_values($relationshipKeys))
      : [];
    $profiles = UserRepository::findMany($normalizedUUIDs);

    $members = [];

    foreach ($normalizedUUIDs as $memberUUID) {
      $rel = $relationshipHashes[$relationshipKeys[$memberUUID]] ?? [];
      if ([] === $rel) {
        continue;
      }

      $relRole   = (string) ($rel['role']   ?? '');
      $relStatus = (string) ($rel['status'] ?? '');

      if (null !== $role && $relRole !== $role) {
        continue;
      }

      if (null !== $status && $relStatus !== $status) {
        continue;
      }

      $user = $profiles[$memberUUID] ?? null;
      if (null === $user) {
        continue;
      }

      $members[] = [
        'user'       => $user,
        'role'       => $relRole,
        'status'     => $relStatus,
        'scopes'     => self::parseScopeCSV((string) ($rel['scopes'] ?? '')),
        'updated_at' => (string) ($rel['updated_at'] ?? ''),
      ];
    }

    usort($members, static function (array $a, array $b): int {
      return strcasecmp((string) $a['user']->full_name, (string) $b['user']->full_name);
    });

    if ($useCache && $role === null) {
      BusinessWorkspaceCache::putRoster($orgId, $members);
    }

    return self::filterMembers($members, $role, $status);
  }

  /**
   * @param list<array{
   *   user: User,
   *   role: string,
   *   status: string,
   *   scopes: list<string>,
   *   updated_at: string
   * }> $members
   * @return list<array{
   *   user: User,
   *   role: string,
   *   status: string,
   *   scopes: list<string>,
   *   updated_at: string
   * }>
   */
  private static function filterMembers(array $members, ?string $role, ?string $status): array
  {
    if ($role === null && $status === null) {
      return $members;
    }

    $filtered = [];
    foreach ($members as $member) {
      if (null !== $role && $member['role'] !== $role) {
        continue;
      }

      if (null !== $status && $member['status'] !== $status) {
        continue;
      }

      $filtered[] = $member;
    }

    return $filtered;
  }

  /**
   * List all organization memberships for a single user.
   * Useful for "which orgs is this user in, and in what role?"
   * Uses active membership plus relationship reverse-index SETs.
   *
   * @param  string $userUUID
   * @return array<int, array{org_id: string, role: string, status: string, scopes: list<string>, updated_at: string}>
   */
  public static function forUser(string $userUUID): array
  {
    if ('' === $userUUID) {
      return [];
    }

    $orgIds = array_merge(
      Database::smembers(Keys::BUSINESS_USER . ':' . $userUUID),
      Database::smembers(Keys::BUSINESS_RELATIONSHIPS_USER . ':' . $userUUID),
    );
    $orgIds = array_values(array_unique(array_filter(
      array_map(static fn (mixed $value): string => trim((string) $value), $orgIds),
      static fn (string $value): bool => $value !== ''
    )));
    if ([] === $orgIds) {
      return [];
    }

    $relationshipKeys = [];
    foreach ($orgIds as $orgId) {
      $relationshipKeys[$orgId] = Keys::BUSINESS_RELATIONSHIP . ':' . $orgId . ':' . $userUUID;
    }

    $relationshipHashes = Database::pipelineHgetall(array_values($relationshipKeys));

    $memberships = [];

    foreach ($relationshipKeys as $orgId => $relationshipKey) {
      $rel = $relationshipHashes[$relationshipKey] ?? [];
      if ([] === $rel) {
        continue;
      }

      $memberships[] = [
        'org_id'     => $orgId,
        'role'       => (string) ($rel['role']       ?? ''),
        'status'     => (string) ($rel['status']     ?? ''),
        'scopes'     => self::parseScopeCSV((string) ($rel['scopes'] ?? '')),
        'updated_at' => (string) ($rel['updated_at'] ?? ''),
      ];
    }

    return $memberships;
  }

  /**
   * Count members of an organization, optionally filtered by status.
   * When $status is null, every relationship key is counted regardless of status.
   */
  public static function count(string $orgId, ?string $status = null): int
  {
    if ('' === $orgId) {
      return 0;
    }

    $pattern = Keys::BUSINESS_RELATIONSHIP . ':' . $orgId . ':*';
    $n       = 0;

    foreach (Database::scanKeys($pattern) as $key) {
      if (null === $status) {
        $n++;
        continue;
      }

      $rel = Database::hgetall($key);
      if ((string) ($rel['status'] ?? '') === $status) {
        $n++;
      }
    }

    return $n;
  }

  /**
   * Parse a comma-separated scope string (as stored in Redis) into a sorted list.
   * The special value 'all' is returned as-is in a single-element array.
   *
   * @return list<string>
   */
  private static function parseScopeCSV(string $csv): array
  {
    if ('all' === $csv) {
      return ['all'];
    }

    $scopes = array_values(
      array_filter(
        array_map('trim', explode(',', $csv)),
        static fn (string $s): bool => $s !== ''
      )
    );

    sort($scopes, SORT_STRING);

    return $scopes;
  }
}
