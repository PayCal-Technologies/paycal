<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * OrganizationMemberRepository
 *
 * Pure data-access layer for enumerating and querying org membership.
 * No authorization gating — callers (ODS, controllers) handle access control.
 *
 * Key layout (read-only mirrors of what ODS writes):
 *   organization:relationship:{orgId}:{userUUID}  – HASH: role, status, scopes, user_uuid, updated_at
 *   organization:user:{userUUID}                  – SET of orgIds the user belongs to
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
final class OrganizationMemberRepository
{
  /**
   * Enumerate all members of an organization, optionally filtered by role and/or status.
   * Each entry includes the hydrated User plus their relationship fields.
   * Members with no matching user record are silently skipped.
   *
   * Results are sorted alphabetically by full_name.
   *
   * @param  string      $orgId   Organization ID
   * @param  string|null $role    If set, only return members with this exact role
   * @param  string|null $status  If set, only return members with this exact status (e.g. 'active')
   * @return array<int, array{user: User, role: string, status: string, scopes: list<string>, updated_at: string}>
   */
  public static function forOrganization(string $orgId, ?string $role = null, ?string $status = null): array
  {
    if ('' === $orgId) {
      return [];
    }

    $memberUUIDs = Database::smembers(Keys::ORGANIZATION_MEMBERS . ':' . $orgId);
    $members     = [];

    foreach ($memberUUIDs as $memberUUID) {
      $memberUUID = (string) $memberUUID;
      $rel = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $memberUUID);
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

      $user = UserRepository::find($memberUUID);
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

    return $members;
  }

  /**
   * List all organization memberships for a single user.
   * Useful for "which orgs is this user in, and in what role?"
   * Uses the organization:user:{uuid} reverse-index SET.
   *
   * @param  string $userUUID
   * @return array<int, array{org_id: string, role: string, status: string, scopes: list<string>, updated_at: string}>
   */
  public static function forUser(string $userUUID): array
  {
    if ('' === $userUUID) {
      return [];
    }

    $orgIds      = Database::smembers(Keys::ORGANIZATION_USER . ':' . $userUUID);
    $memberships = [];

    foreach ($orgIds as $orgId) {
      $rel = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $userUUID);
      if ([] === $rel) {
        continue;
      }

      $memberships[] = [
        'org_id'     => (string) $orgId,
        'role'       => (string) ($rel['role']       ?? ''),
        'status'     => (string) ($rel['status']     ?? ''),
        'scopes'     => self::parseScopeCSV((string) ($rel['scopes'] ?? '')),
        'updated_at' => (string) ($rel['updated_at'] ?? ''),
      ];
    }

    return $memberships;
  }

  /**
   * Return the raw relationship hash for a single org–user pair.
   * Returns null if no relationship key exists in Redis.
   *
   * @return array<string, string>|null
   */
  public static function find(string $orgId, string $userUUID): ?array
  {
    if ('' === $orgId || '' === $userUUID) {
      return null;
    }

    $rel = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $userUUID);

    return [] !== $rel ? $rel : null;
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

    $pattern = Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':*';
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
