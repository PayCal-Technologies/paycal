<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * Canonical member group service for business workspaces.
 */
final class BusinessGroupService
{
  private const MAX_NAME_LENGTH = 80;
  private const MAX_DESCRIPTION_LENGTH = 300;

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function listGroups(string $actorUUID, string $businessId, bool $activeOnly = false): array
  {
    $actorUUID = trim($actorUUID);
    $businessId = trim($businessId);
    if (!$this->canViewGroups($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESS_GROUPS_ACCESS_DENIED'));
    }

    $groups = [];
    foreach (Database::smembers(Keys::businessGroups($businessId)) as $groupIdRaw) {
      $groupId = trim((string) $groupIdRaw);
      if ($groupId === '') {
        continue;
      }

      $group = Database::hgetall(Keys::businessGroup($businessId, $groupId));
      if ($group === []) {
        continue;
      }

      $status = $this->normalizeGroupStatus((string) ($group['status'] ?? 'active'));
      if ($activeOnly && $status !== 'active') {
        continue;
      }

      $groups[] = [
        'group_id' => $groupId,
        'name' => (string) ($group['name'] ?? ''),
        'description' => (string) ($group['description'] ?? ''),
        'type' => $this->normalizeGroupType((string) ($group['type'] ?? 'manual')),
        'status' => $status,
        'member_count' => (int) (Database::scard(Keys::businessGroupMembers($businessId, $groupId)) ?? 0),
        'created_at' => (string) ($group['created_at'] ?? ''),
        'updated_at' => (string) ($group['updated_at'] ?? ''),
      ];
    }

    usort($groups, static fn (array $a, array $b): int => strcasecmp((string) $a['name'], (string) $b['name']));

    return $this->ok(Strings::i18n('BUSINESS_GROUPS_LOADED'), [
      'groups' => $groups,
    ]);
  }

  /**
   * @param array<string, mixed> $input
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function saveGroup(string $actorUUID, string $businessId, array $input): array
  {
    $actorUUID = trim($actorUUID);
    $businessId = trim($businessId);
    if (!$this->canManageGroups($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESS_GROUPS_MANAGE_DENIED'));
    }

    $name = trim($this->stringValue($input, 'name'));
    if (mb_strlen($name) < 2) {
      return $this->fail(Strings::i18n('BUSINESS_GROUPS_NAME_REQUIRED'));
    }
    if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
      return $this->fail(Strings::i18n('BUSINESS_GROUPS_NAME_TOO_LONG'));
    }

    $description = trim($this->stringValue($input, 'description'));
    if (mb_strlen($description) > self::MAX_DESCRIPTION_LENGTH) {
      return $this->fail(Strings::i18n('BUSINESS_GROUPS_DESCRIPTION_TOO_LONG'));
    }

    $requestedGroupId = trim($this->stringValue($input, 'group_id'));
    $isCreate = $requestedGroupId === '';
    $groupId = $isCreate ? $this->generateGroupId($businessId, $name) : $requestedGroupId;
    $key = Keys::businessGroup($businessId, $groupId);
    $existing = Database::hgetall($key);
    if (!$isCreate && $existing === []) {
      return $this->fail(Strings::i18n('BUSINESS_GROUPS_NOT_FOUND'));
    }

    $type = $isCreate ? 'manual' : $this->normalizeGroupType((string) ($existing['type'] ?? 'manual'));
    $status = $isCreate ? 'active' : $this->normalizeGroupStatus((string) ($existing['status'] ?? 'active'));

    $now = date('c');
    Database::hset($key, [
      'group_id' => $groupId,
      'business_id' => $businessId,
      'name' => $name,
      'description' => $description,
      'type' => $type,
      'status' => $status,
      'created_at' => (string) ($existing['created_at'] ?? $now),
      'updated_at' => $now,
      'created_by' => (string) ($existing['created_by'] ?? $actorUUID),
      'updated_by' => $actorUUID,
    ]);
    Database::sadd(Keys::businessGroups($businessId), $groupId);

    (new BusinessDiscoveryService())->appendBusinessAuditEvent(
      $businessId,
      $isCreate ? 'group.created' : 'group.updated',
      $actorUUID,
      ['group_id' => $groupId, 'name' => $name, 'type' => $type, 'status' => $status],
    );

    return $this->ok($isCreate ? Strings::i18n('BUSINESS_GROUPS_CREATED') : Strings::i18n('BUSINESS_GROUPS_UPDATED'), [
      'group' => [
        'group_id' => $groupId,
        'name' => $name,
        'description' => $description,
        'type' => $type,
        'status' => $status,
        'member_count' => (int) (Database::scard(Keys::businessGroupMembers($businessId, $groupId)) ?? 0),
        'created_at' => (string) ($existing['created_at'] ?? $now),
        'updated_at' => $now,
      ],
    ]);
  }

  /**
   * @param list<string> $memberUUIDs
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function addMembers(string $actorUUID, string $businessId, string $groupId, array $memberUUIDs): array
  {
    $actorUUID = trim($actorUUID);
    $businessId = trim($businessId);
    $groupId = trim($groupId);
    if (!$this->canManageGroups($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESS_GROUPS_MANAGE_DENIED'));
    }

    $group = Database::hgetall(Keys::businessGroup($businessId, $groupId));
    if ($group === [] || strtolower(trim((string) ($group['status'] ?? ''))) !== 'active') {
      return $this->fail(Strings::i18n('BUSINESS_GROUPS_NOT_FOUND'));
    }

    $added = 0;
    foreach ($memberUUIDs as $memberUUIDRaw) {
      $memberUUID = trim((string) $memberUUIDRaw);
      if ($memberUUID === '' || !$this->isActiveMember($businessId, $memberUUID)) {
        continue;
      }

      $added += Database::sadd(Keys::businessGroupMembers($businessId, $groupId), $memberUUID);
      Database::sadd(Keys::businessMemberGroups($businessId, $memberUUID), $groupId);
    }

    Database::hset(Keys::businessGroup($businessId, $groupId), [
      'updated_at' => date('c'),
      'updated_by' => $actorUUID,
    ]);
    if ($added > 0) {
      Database::unlink(Keys::businessGroupMetricsCache($businessId, $groupId));
    }

    (new BusinessDiscoveryService())->appendBusinessAuditEvent(
      $businessId,
      'group.members_added',
      $actorUUID,
      ['group_id' => $groupId, 'member_count' => (string) $added],
    );

    return $this->ok(Strings::i18n('BUSINESS_GROUPS_MEMBERS_ADDED'), [
      'added' => $added,
      'group_id' => $groupId,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function archiveGroup(string $actorUUID, string $businessId, string $groupId): array
  {
    $actorUUID = trim($actorUUID);
    $businessId = trim($businessId);
    $groupId = trim($groupId);
    if (!$this->canManageGroups($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESS_GROUPS_MANAGE_DENIED'));
    }

    $key = Keys::businessGroup($businessId, $groupId);
    $group = Database::hgetall($key);
    if ($group === []) {
      return $this->fail(Strings::i18n('BUSINESS_GROUPS_NOT_FOUND'));
    }

    if ($this->normalizeGroupStatus((string) ($group['status'] ?? 'active')) === 'archived') {
      return $this->ok(Strings::i18n('BUSINESS_GROUPS_ARCHIVED'), [
        'group_id' => $groupId,
      ]);
    }

    Database::hset($key, [
      'status' => 'archived',
      'updated_at' => date('c'),
      'updated_by' => $actorUUID,
    ]);

    (new BusinessDiscoveryService())->appendBusinessAuditEvent(
      $businessId,
      'group.archived',
      $actorUUID,
      ['group_id' => $groupId, 'name' => (string) ($group['name'] ?? '')],
    );

    return $this->ok(Strings::i18n('BUSINESS_GROUPS_ARCHIVED'), [
      'group_id' => $groupId,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function restoreGroup(string $actorUUID, string $businessId, string $groupId): array
  {
    $actorUUID = trim($actorUUID);
    $businessId = trim($businessId);
    $groupId = trim($groupId);
    if (!$this->canManageGroups($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESS_GROUPS_MANAGE_DENIED'));
    }

    $key = Keys::businessGroup($businessId, $groupId);
    $group = Database::hgetall($key);
    if ($group === []) {
      return $this->fail(Strings::i18n('BUSINESS_GROUPS_NOT_FOUND'));
    }

    if ($this->normalizeGroupStatus((string) ($group['status'] ?? 'active')) === 'active') {
      return $this->ok(Strings::i18n('BUSINESS_GROUPS_RESTORED'), [
        'group_id' => $groupId,
      ]);
    }

    Database::hset($key, [
      'status' => 'active',
      'updated_at' => date('c'),
      'updated_by' => $actorUUID,
    ]);

    (new BusinessDiscoveryService())->appendBusinessAuditEvent(
      $businessId,
      'group.restored',
      $actorUUID,
      ['group_id' => $groupId, 'name' => (string) ($group['name'] ?? '')],
    );

    return $this->ok(Strings::i18n('BUSINESS_GROUPS_RESTORED'), [
      'group_id' => $groupId,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function deleteGroup(string $actorUUID, string $businessId, string $groupId): array
  {
    $actorUUID = trim($actorUUID);
    $businessId = trim($businessId);
    $groupId = trim($groupId);
    if (!$this->canManageGroups($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESS_GROUPS_MANAGE_DENIED'));
    }

    $key = Keys::businessGroup($businessId, $groupId);
    $group = Database::hgetall($key);
    if ($group === []) {
      return $this->fail(Strings::i18n('BUSINESS_GROUPS_NOT_FOUND'));
    }

    $status = strtolower(trim((string) ($group['status'] ?? 'active')));
    if ($status !== 'archived') {
      return $this->fail(Strings::i18n('BUSINESS_GROUPS_DELETE_ACTIVE_DENIED'));
    }

    foreach (Database::smembers(Keys::businessGroupMembers($businessId, $groupId)) as $memberUUID) {
      Database::srem(Keys::businessMemberGroups($businessId, (string) $memberUUID), $groupId);
    }
    Database::unlink(Keys::businessGroupMembers($businessId, $groupId));
    Database::srem(Keys::businessGroups($businessId), $groupId);
    Database::unlink($key);
    Database::unlink(Keys::businessGroupMetricsCache($businessId, $groupId));

    (new BusinessDiscoveryService())->appendBusinessAuditEvent(
      $businessId,
      'group.deleted',
      $actorUUID,
      ['group_id' => $groupId, 'name' => (string) ($group['name'] ?? '')],
    );

    return $this->ok(Strings::i18n('BUSINESS_GROUPS_DELETED'), [
      'group_id' => $groupId,
    ]);
  }

  /**
   * Determine whether the actor can view groups for the business.
   */
  private function canViewGroups(string $businessId, string $actorUUID): bool
  {
    return $this->canManageGroups($businessId, $actorUUID);
  }

  /**
   * Normalize stored group type values to supported group types.
   */
  private function normalizeGroupType(string $type): string
  {
    $normalized = strtolower(trim($type));
    return in_array($normalized, ['manual', 'smart'], true) ? $normalized : 'manual';
  }

  /**
   * Normalize stored group status values to active or archived.
   */
  private function normalizeGroupStatus(string $status): string
  {
    return strtolower(trim($status)) === 'archived' ? 'archived' : 'active';
  }

  /**
   * Determine whether the actor can mutate group membership and lifecycle state.
   */
  private function canManageGroups(string $businessId, string $actorUUID): bool
  {
    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ($business === []) {
      return false;
    }

    if ((string) ($business['owner_uuid'] ?? '') === $actorUUID) {
      return true;
    }

    $connection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $businessId . ':' . $actorUUID);
    if ((string) ($connection['status'] ?? '') !== 'active') {
      return false;
    }

    return in_array(strtolower((string) ($connection['role'] ?? '')), ['owner', 'coordinator'], true);
  }

  /**
   * Return true when a user has an active business connection.
   */
  private function isActiveMember(string $businessId, string $memberUUID): bool
  {
    $connection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $businessId . ':' . $memberUUID);
    return (string) ($connection['status'] ?? '') === 'active';
  }

  /**
   * Generate a stable-prefix group ID from business, name, and entropy.
   */
  private function generateGroupId(string $businessId, string $name): string
  {
    $base = preg_replace('/[^a-z0-9]+/', '', strtolower($businessId . ':' . $name . ':' . microtime(true))) ?? '';
    return 'GRP' . substr(sha1($base), 0, 20);
  }

  /** @param array<string, mixed> $input */
  private function stringValue(array $input, string $key, string $default = ''): string
  {
    $value = $input[$key] ?? $default;
    return is_scalar($value) ? (string) $value : $default;
  }

  /**
   * @param array<string, mixed> $data
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function ok(string $message, array $data = []): array
  {
    return ['success' => true, 'message' => $message, 'data' => $data];
  }

  /**
   * @param array<string, mixed> $data
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function fail(string $message, array $data = []): array
  {
    return ['success' => false, 'message' => $message, 'data' => $data];
  }
}
