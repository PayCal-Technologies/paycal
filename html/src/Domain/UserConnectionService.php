<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;
use PayCal\Infrastructure\Audit\SystemAuditRepository;

/**
 * Person-to-person connection and grant lifecycle.
 *
 * Connections are identity links. Grants are separate permissions.
 * Active person connections never imply data access by themselves.
 */
final class UserConnectionService
{
  public const TARGET_TYPE_USER = 'user';
  public const STATUS_PENDING = 'pending';
  public const STATUS_ACTIVE = 'active';
  public const STATUS_REVOKED = 'revoked';
  public const STATUS_DECLINED = 'declined';

  public const GRANT_ACTIVE = 'active';
  public const GRANT_PENDING = 'pending';
  public const GRANT_REVOKED = 'revoked';

  public const CAPABILITY_CALENDAR_VIEW = 'calendar_view';
  public const CAPABILITY_CALENDAR_EDIT = 'calendar_edit';
  public const CAPABILITY_EXPORT_RECEIVE = 'export_receive';
  public const CAPABILITY_TRUSTED_RECOVERY = 'trusted_recovery';

  private const RECOVERY_WAIT_SECONDS = 259200;

  /** @var array<string, string> */
  private const CAPABILITY_LABELS = [
    self::CAPABILITY_CALENDAR_VIEW => 'View selected reports and calendar data',
    self::CAPABILITY_CALENDAR_EDIT => 'Help manage work entries',
    self::CAPABILITY_EXPORT_RECEIVE => 'Receive exported records',
    self::CAPABILITY_TRUSTED_RECOVERY => 'Trusted recovery user',
  ];

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function requestPersonConnection(string $ownerUUID, string $targetEmail): array
  {
    $ownerUUID = trim(InputSanitizer::sanitizeString($ownerUUID));
    $targetEmail = strtolower(trim(InputSanitizer::sanitizeEmail($targetEmail)));

    if ($ownerUUID === '') {
      return $this->fail('Sign in before adding a person.');
    }

    if ($targetEmail === '') {
      return $this->fail('Enter the person email address.');
    }

    $owner = UserRepository::getByUUID($ownerUUID);
    if (!$owner instanceof User) {
      return $this->fail('Your account could not be found. Sign in again and retry.');
    }

    $targetUUID = UserRepository::getUUIDFromEmail($targetEmail);
    if ($targetUUID === '') {
      return $this->fail('No PayCal user was found for that email address.');
    }

    if ($targetUUID === $ownerUUID) {
      return $this->fail('Choose another PayCal user.');
    }

    $target = UserRepository::getByUUID($targetUUID);
    if (!$target instanceof User) {
      return $this->fail('That PayCal account is no longer available.');
    }

    $activeKey = $this->activeKey($ownerUUID, $targetUUID);
    $existingId = trim(Database::get($activeKey));
    if ($existingId !== '') {
      $existing = $this->connectionById($existingId);
      $status = strtolower(trim((string) ($existing['status'] ?? '')));
      if ($status === self::STATUS_PENDING || $status === self::STATUS_ACTIVE) {
        return $this->ok(
          $status === self::STATUS_ACTIVE
            ? 'This person is already connected.'
            : 'This connection request is already waiting for approval.',
          ['connection' => $this->presentConnection($existing, $owner, $target)]
        );
      }
    }

    $connectionId = 'UC' . substr(hash('sha256', $ownerUUID . '|' . $targetUUID . '|' . bin2hex(random_bytes(16))), 0, 20);
    $timestamp = date('c');
    $record = [
      'connection_id' => $connectionId,
      'target_type' => self::TARGET_TYPE_USER,
      'owner_uuid' => $ownerUUID,
      'target_user_uuid' => $targetUUID,
      'target_email' => $targetEmail,
      'role' => 'trusted_person',
      'status' => self::STATUS_PENDING,
      'created_at' => $timestamp,
      'updated_at' => $timestamp,
    ];

    Database::hset($this->connectionKey($connectionId), $record);
    Database::sadd(Keys::USER_CONNECTIONS_OWNER . ':' . $ownerUUID, $connectionId);
    Database::sadd(Keys::USER_CONNECTIONS_TARGET . ':' . $targetUUID, $connectionId);
    Database::sadd(Keys::USER_CONNECTIONS_PENDING . ':' . $targetUUID, $connectionId);
    Database::set($activeKey, $connectionId);

    $this->audit('user.connection.requested', $ownerUUID, [
      'connection_id' => $connectionId,
      'target_type' => self::TARGET_TYPE_USER,
      'target_user_uuid' => $targetUUID,
    ]);

    return $this->ok('Connection request sent.', [
      'connection' => $this->presentConnection($record, $owner, $target),
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function approvePersonConnection(string $targetUUID, string $connectionId): array
  {
    $targetUUID = trim(InputSanitizer::sanitizeString($targetUUID));
    $connectionId = trim(InputSanitizer::sanitizeString($connectionId));
    $connection = $this->connectionById($connectionId);

    if ($connection === []) {
      return $this->fail('Connection request was not found.');
    }

    if ((string) ($connection['target_user_uuid'] ?? '') !== $targetUUID) {
      return $this->fail('Only the invited person can approve this request.');
    }

    if ((string) ($connection['status'] ?? '') !== self::STATUS_PENDING) {
      return $this->fail('This connection request is no longer waiting for approval.');
    }

    $timestamp = date('c');
    $updates = [
      'status' => self::STATUS_ACTIVE,
      'approved_at' => $timestamp,
      'updated_at' => $timestamp,
    ];
    Database::hset($this->connectionKey($connectionId), $updates);
    Database::srem(Keys::USER_CONNECTIONS_PENDING . ':' . $targetUUID, $connectionId);

    $updated = array_merge($connection, $updates);
    $this->audit('user.connection.approved', $targetUUID, [
      'connection_id' => $connectionId,
      'owner_uuid' => (string) ($connection['owner_uuid'] ?? ''),
    ]);

    return $this->ok('Connection approved. No access has been granted yet.', [
      'connection' => $this->presentConnection($updated),
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function revokePersonConnection(string $actorUUID, string $connectionId, string $nextStatus = self::STATUS_REVOKED): array
  {
    $actorUUID = trim(InputSanitizer::sanitizeString($actorUUID));
    $connectionId = trim(InputSanitizer::sanitizeString($connectionId));
    $connection = $this->connectionById($connectionId);

    if ($connection === []) {
      return $this->fail('Connection was not found.');
    }

    $ownerUUID = (string) ($connection['owner_uuid'] ?? '');
    $targetUUID = (string) ($connection['target_user_uuid'] ?? '');
    if ($actorUUID !== $ownerUUID && $actorUUID !== $targetUUID) {
      return $this->fail('Only people in this connection can change it.');
    }

    $normalizedStatus = strtolower(trim($nextStatus));
    if ($normalizedStatus !== self::STATUS_DECLINED) {
      $normalizedStatus = self::STATUS_REVOKED;
    }

    $timestamp = date('c');
    $updates = [
      'status' => $normalizedStatus,
      'revoked_at' => $timestamp,
      'updated_at' => $timestamp,
      'revoked_by_uuid' => $actorUUID,
    ];
    Database::hset($this->connectionKey($connectionId), $updates);
    Database::srem(Keys::USER_CONNECTIONS_PENDING . ':' . $targetUUID, $connectionId);
    Database::unlink($this->activeKey($ownerUUID, $targetUUID));
    $this->revokeAllGrants($connectionId, $actorUUID);

    $this->audit($normalizedStatus === self::STATUS_DECLINED ? 'user.connection.declined' : 'user.connection.revoked', $actorUUID, [
      'connection_id' => $connectionId,
      'owner_uuid' => $ownerUUID,
      'target_user_uuid' => $targetUUID,
    ]);

    $updated = array_merge($connection, $updates);

    return $this->ok($normalizedStatus === self::STATUS_DECLINED ? 'Connection request declined.' : 'Connection removed.', [
      'connection' => $this->presentConnection($updated),
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function grantCapability(string $ownerUUID, string $connectionId, string $capability): array
  {
    $ownerUUID = trim(InputSanitizer::sanitizeString($ownerUUID));
    $connectionId = trim(InputSanitizer::sanitizeString($connectionId));
    $capability = $this->normalizeCapability($capability);
    if ($capability === '') {
      return $this->fail('Choose a valid permission.');
    }

    $connection = $this->connectionById($connectionId);
    $gate = $this->requireActiveOwnerConnection($connection, $ownerUUID);
    if ($gate !== null) {
      return $gate;
    }

    $timestamp = date('c');
    $status = self::GRANT_ACTIVE;
    $effectiveAt = $timestamp;
    $waitingSeconds = 0;
    if ($capability === self::CAPABILITY_TRUSTED_RECOVERY) {
      $status = self::GRANT_PENDING;
      $effectiveAt = date('c', time() + self::RECOVERY_WAIT_SECONDS);
      $waitingSeconds = self::RECOVERY_WAIT_SECONDS;
    }

    $grant = [
      'grant_id' => $this->grantId($connectionId, $capability),
      'connection_id' => $connectionId,
      'capability' => $capability,
      'capability_label' => self::CAPABILITY_LABELS[$capability],
      'status' => $status,
      'created_at' => $timestamp,
      'updated_at' => $timestamp,
      'effective_at' => $effectiveAt,
      'waiting_period_seconds' => (string) $waitingSeconds,
      'revoked_at' => '',
    ];

    Database::hset($this->grantKey($connectionId, $capability), $grant);
    Database::sadd(Keys::USER_CONNECTION_GRANTS . ':' . $connectionId, $capability);

    $this->audit('user.connection.grant.enabled', $ownerUUID, [
      'connection_id' => $connectionId,
      'target_user_uuid' => (string) ($connection['target_user_uuid'] ?? ''),
      'capability' => $capability,
      'status' => $status,
      'effective_at' => $effectiveAt,
    ]);

    return $this->ok(
      $capability === self::CAPABILITY_TRUSTED_RECOVERY
        ? 'Trusted recovery request saved. It will become available after the waiting period.'
        : 'Permission saved.',
      ['grant' => $grant, 'connection' => $this->presentConnection($connection)]
    );
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function revokeCapability(string $ownerUUID, string $connectionId, string $capability): array
  {
    $ownerUUID = trim(InputSanitizer::sanitizeString($ownerUUID));
    $connectionId = trim(InputSanitizer::sanitizeString($connectionId));
    $capability = $this->normalizeCapability($capability);
    if ($capability === '') {
      return $this->fail('Choose a valid permission.');
    }

    $connection = $this->connectionById($connectionId);
    $gate = $this->requireOwnerConnection($connection, $ownerUUID);
    if ($gate !== null) {
      return $gate;
    }

    $timestamp = date('c');
    $grant = $this->grantByCapability($connectionId, $capability);
    if ($grant === []) {
      return $this->ok('Permission is already off.', ['connection' => $this->presentConnection($connection)]);
    }

    $updates = [
      'status' => self::GRANT_REVOKED,
      'revoked_at' => $timestamp,
      'updated_at' => $timestamp,
    ];
    Database::hset($this->grantKey($connectionId, $capability), $updates);

    $this->audit('user.connection.grant.revoked', $ownerUUID, [
      'connection_id' => $connectionId,
      'target_user_uuid' => (string) ($connection['target_user_uuid'] ?? ''),
      'capability' => $capability,
    ]);

    return $this->ok('Permission removed.', [
      'grant' => array_merge($grant, $updates),
      'connection' => $this->presentConnection($connection),
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function listForUser(string $userUUID): array
  {
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    if ($userUUID === '') {
      return $this->fail('Sign in to view connections.');
    }

    $ownedIds = Database::smembers(Keys::USER_CONNECTIONS_OWNER . ':' . $userUUID);
    $targetIds = Database::smembers(Keys::USER_CONNECTIONS_TARGET . ':' . $userUUID);
    $ids = array_values(array_unique(array_map('strval', array_merge($ownedIds, $targetIds))));
    sort($ids, SORT_STRING);

    $connections = [];
    $active = 0;
    $pending = 0;
    $archived = 0;

    foreach ($ids as $connectionId) {
      $record = $this->connectionById($connectionId);
      if ($record === []) {
        continue;
      }

      $presented = $this->presentConnection($record);
      $status = self::stringField($presented, 'status');
      if ($status === self::STATUS_ACTIVE) {
        ++$active;
      } elseif ($status === self::STATUS_PENDING) {
        ++$pending;
      } else {
        ++$archived;
      }

      $connections[] = $presented;
    }

    usort($connections, static function (array $left, array $right): int {
      $rank = [
        self::STATUS_PENDING => 0,
        self::STATUS_ACTIVE => 1,
        self::STATUS_REVOKED => 2,
        self::STATUS_DECLINED => 3,
      ];
      $leftRank = $rank[self::stringField($left, 'status')] ?? 9;
      $rightRank = $rank[self::stringField($right, 'status')] ?? 9;
      if ($leftRank !== $rightRank) {
        return $leftRank <=> $rightRank;
      }

      return strcmp(self::stringField($right, 'updated_at'), self::stringField($left, 'updated_at'));
    });

    return $this->ok('Person connections retrieved.', [
      'connections' => $connections,
      'summary' => [
        'active' => $active,
        'pending' => $pending,
        'archived' => $archived,
      ],
      'capabilities' => self::CAPABILITY_LABELS,
    ]);
  }

  /**
   * Return whether a connection has an active grant for a capability.
   */
  public function connectionHasActiveGrant(string $ownerUUID, string $targetUUID, string $capability): bool
  {
    $ownerUUID = trim(InputSanitizer::sanitizeString($ownerUUID));
    $targetUUID = trim(InputSanitizer::sanitizeString($targetUUID));
    $capability = $this->normalizeCapability($capability);
    if ($ownerUUID === '' || $targetUUID === '' || $ownerUUID === $targetUUID || $capability === '') {
      return false;
    }

    $connectionId = trim(Database::get($this->activeKey($ownerUUID, $targetUUID)));
    if ($connectionId === '') {
      return false;
    }

    $connection = $this->connectionById($connectionId);
    if ((string) ($connection['owner_uuid'] ?? '') !== $ownerUUID
      || (string) ($connection['target_user_uuid'] ?? '') !== $targetUUID
      || (string) ($connection['status'] ?? '') !== self::STATUS_ACTIVE) {
      return false;
    }

    $grant = $this->grantByCapability($connectionId, $capability);
    if ($grant === []) {
      return false;
    }

    $status = (string) ($grant['status'] ?? '');
    if ($status === self::GRANT_ACTIVE) {
      return true;
    }

    if ($status !== self::GRANT_PENDING || $capability !== self::CAPABILITY_TRUSTED_RECOVERY) {
      return false;
    }

    $effectiveAt = strtotime((string) ($grant['effective_at'] ?? ''));

    return is_int($effectiveAt) && $effectiveAt <= time();
  }

  /**
   * Work-data read sharing currently maps to the explicit calendar/report view
   * grant. Connection alone must never satisfy this check.
   */
  public function canUserViewSharedWorkData(string $ownerUUID, string $targetUUID): bool
  {
    return $this->connectionHasActiveGrant($ownerUUID, $targetUUID, self::CAPABILITY_CALENDAR_VIEW);
  }

  /** @return array<string, string> */
  private function connectionById(string $connectionId): array
  {
    $connectionId = trim(InputSanitizer::sanitizeString($connectionId));
    if ($connectionId === '') {
      return [];
    }

    return Database::hgetall($this->connectionKey($connectionId));
  }

  /** @return array<string, string> */
  private function grantByCapability(string $connectionId, string $capability): array
  {
    $connectionId = trim(InputSanitizer::sanitizeString($connectionId));
    $capability = $this->normalizeCapability($capability);
    if ($connectionId === '' || $capability === '') {
      return [];
    }

    return Database::hgetall($this->grantKey($connectionId, $capability));
  }

  /**
   * Build the Redis key for a user connection.
   */
  private function connectionKey(string $connectionId): string
  {
    return Keys::USER_CONNECTION . ':' . trim($connectionId);
  }

  /**
   * Build the Redis key for a user's active connection index.
   */
  private function activeKey(string $ownerUUID, string $targetUUID): string
  {
    return Keys::USER_CONNECTION_ACTIVE . ':' . trim($ownerUUID) . ':' . trim($targetUUID);
  }

  /**
   * Build a stable grant ID.
   */
  private function grantId(string $connectionId, string $capability): string
  {
    return trim($connectionId) . ':' . trim($capability);
  }

  /**
   * Build the Redis key for a connection grant.
   */
  private function grantKey(string $connectionId, string $capability): string
  {
    return Keys::USER_CONNECTION_GRANT . ':' . $this->grantId($connectionId, $capability);
  }

  /**
   * Normalize a connection capability identifier.
   */
  private function normalizeCapability(string $capability): string
  {
    $capability = strtolower(trim(InputSanitizer::sanitizeString($capability)));

    return array_key_exists($capability, self::CAPABILITY_LABELS) ? $capability : '';
  }

  /**
   * @param array<string, string> $connection
   * @return array{success: bool, message: string, data: array<string, mixed>}|null
   */
  private function requireOwnerConnection(array $connection, string $ownerUUID): ?array
  {
    if ($connection === []) {
      return $this->fail('Connection was not found.');
    }

    if ((string) ($connection['owner_uuid'] ?? '') !== $ownerUUID) {
      return $this->fail('Only the person who owns this connection can change permissions.');
    }

    return null;
  }

  /**
   * @param array<string, string> $connection
   * @return array{success: bool, message: string, data: array<string, mixed>}|null
   */
  private function requireActiveOwnerConnection(array $connection, string $ownerUUID): ?array
  {
    $gate = $this->requireOwnerConnection($connection, $ownerUUID);
    if ($gate !== null) {
      return $gate;
    }

    if ((string) ($connection['status'] ?? '') !== self::STATUS_ACTIVE) {
      return $this->fail('Approve the connection before granting permissions.');
    }

    return null;
  }

  /**
   * Revoke all grants attached to a connection.
   */
  private function revokeAllGrants(string $connectionId, string $actorUUID): void
  {
    $timestamp = date('c');
    foreach (Database::smembers(Keys::USER_CONNECTION_GRANTS . ':' . $connectionId) as $capabilityRaw) {
      $capability = $this->normalizeCapability((string) $capabilityRaw);
      if ($capability === '') {
        continue;
      }

      $grant = $this->grantByCapability($connectionId, $capability);
      if ($grant === [] || (string) ($grant['status'] ?? '') === self::GRANT_REVOKED) {
        continue;
      }

      Database::hset($this->grantKey($connectionId, $capability), [
        'status' => self::GRANT_REVOKED,
        'revoked_at' => $timestamp,
        'updated_at' => $timestamp,
      ]);
    }

    $this->audit('user.connection.grants.revoked', $actorUUID, [
      'connection_id' => $connectionId,
    ]);
  }

  /**
   * @param array<string, string> $connection
   * @return array<string, mixed>
   */
  private function presentConnection(array $connection, ?User $owner = null, ?User $target = null): array
  {
    $ownerUUID = (string) ($connection['owner_uuid'] ?? '');
    $targetUUID = (string) ($connection['target_user_uuid'] ?? '');
    $owner ??= UserRepository::getByUUID($ownerUUID);
    $target ??= UserRepository::getByUUID($targetUUID);
    $connectionId = (string) ($connection['connection_id'] ?? '');
    $grants = $this->presentGrants($connectionId);
    $activeGrantCount = 0;
    foreach ($grants as $grant) {
      if ((string) ($grant['status'] ?? '') === self::GRANT_ACTIVE) {
        ++$activeGrantCount;
      }
    }

    return [
      'connection_id' => $connectionId,
      'target_type' => (string) ($connection['target_type'] ?? self::TARGET_TYPE_USER),
      'status' => (string) ($connection['status'] ?? ''),
      'role' => (string) ($connection['role'] ?? 'trusted_person'),
      'owner_uuid' => $ownerUUID,
      'owner_name' => $owner instanceof User ? (string) $owner->full_name : '',
      'owner_email' => $owner instanceof User ? (string) $owner->email : '',
      'target_user_uuid' => $targetUUID,
      'target_name' => $target instanceof User ? (string) $target->full_name : '',
      'target_email' => $target instanceof User ? (string) $target->email : (string) ($connection['target_email'] ?? ''),
      'grants' => $grants,
      'access_summary' => $activeGrantCount > 0 ? $activeGrantCount . ' permission' . ($activeGrantCount === 1 ? '' : 's') . ' granted' : 'No access granted',
      'created_at' => (string) ($connection['created_at'] ?? ''),
      'approved_at' => (string) ($connection['approved_at'] ?? ''),
      'revoked_at' => (string) ($connection['revoked_at'] ?? ''),
      'updated_at' => (string) ($connection['updated_at'] ?? ''),
    ];
  }

  /** @return array<int, array<string, string>> */
  private function presentGrants(string $connectionId): array
  {
    $connectionId = trim($connectionId);
    if ($connectionId === '') {
      return [];
    }

    $capabilities = array_values(array_unique(array_map('strval', Database::smembers(Keys::USER_CONNECTION_GRANTS . ':' . $connectionId))));
    sort($capabilities, SORT_STRING);
    $grants = [];
    foreach ($capabilities as $capabilityRaw) {
      $capability = $this->normalizeCapability($capabilityRaw);
      if ($capability === '') {
        continue;
      }

      $grant = $this->grantByCapability($connectionId, $capability);
      if ($grant === []) {
        continue;
      }

      $grants[] = [
        'grant_id' => (string) ($grant['grant_id'] ?? $this->grantId($connectionId, $capability)),
        'connection_id' => $connectionId,
        'capability' => $capability,
        'capability_label' => self::CAPABILITY_LABELS[$capability],
        'status' => (string) ($grant['status'] ?? ''),
        'effective_at' => (string) ($grant['effective_at'] ?? ''),
        'revoked_at' => (string) ($grant['revoked_at'] ?? ''),
        'updated_at' => (string) ($grant['updated_at'] ?? ''),
      ];
    }

    return $grants;
  }

  /** @param array<string, scalar> $details */
  private function audit(string $eventType, string $actorUUID, array $details): void
  {
    try {
      SystemAuditRepository::append($eventType, $actorUUID, $details);
    } catch (\Throwable $e) {
      if (class_exists(Log::class)) {
        Log::debug('[UserConnectionService] audit failed: ' . $e->getMessage());
      }
    }
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

  /** @param array<string, mixed> $data */
  private static function stringField(array $data, string $key): string
  {
    $value = $data[$key] ?? '';

    return is_scalar($value) ? (string) $value : '';
  }
}
