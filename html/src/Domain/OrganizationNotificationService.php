<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * OrganizationNotificationService
 *
 * Purpose:
 * - Maintain unread organization notification counters per user.
 * - Fan out event notifications using a role/recipient matrix.
 * - Send transactional notification emails for significant organization events.
 */
final class OrganizationNotificationService
{
  private const RECENT_EVENT_BUFFER_LIMIT = 200;

  /** @var array<string, string> */
  private const EVENT_LABELS = [
    'access.requested' => 'New access request',
    'access.request.approved' => 'Access request approved',
    'access.request.rejected' => 'Access request rejected',
    'invite.accepted' => 'Invite accepted',
    'relationship.role_updated' => 'Role updated',
    'relationship.revoked' => 'Access revoked',
    'ownership.transferred' => 'Ownership transferred',
  ];

  /**
   * Build unread summary for a user.
   *
   * @return array{total_unread: int, by_org: array<string, int>}
   */
  public function summarizeUnreadForUser(string $userUUID): array
  {
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    if ($userUUID === '') {
      return ['total_unread' => 0, 'by_org' => []];
    }

    $raw = Database::hgetall(Keys::organizationNotificationUnreadByUser($userUUID));
    $byOrg = [];
    $total = 0;

    foreach ($raw as $orgId => $countRaw) {
      $orgId = trim((string) $orgId);
      $count = (int) $countRaw;
      if ($orgId === '' || $count <= 0) {
        continue;
      }
      $byOrg[$orgId] = $count;
      $total += $count;
    }

    Database::set(Keys::organizationNotificationTotalByUser($userUUID), (string) $total, 86400);

    return [
      'total_unread' => $total,
      'by_org' => $byOrg,
    ];
  }

  /**
   * Mark all unread notifications as read for one user+organization.
   *
   * @return array{total_unread: int, by_org: array<string, int>}
   */
  public function markOrganizationRead(string $userUUID, string $orgId): array
  {
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    $orgId = trim(InputSanitizer::sanitizeString($orgId));

    if ($userUUID === '' || $orgId === '') {
      return $this->summarizeUnreadForUser($userUUID);
    }

    Database::hdel(Keys::organizationNotificationUnreadByUser($userUUID), $orgId);
    Database::set(Keys::organizationNotificationLastRead($orgId, $userUUID), date('c'), 365 * 24 * 3600);

    return $this->summarizeUnreadForUser($userUUID);
  }

  /**
   * Create notifications and send emails for significant organization events.
   *
   * @param array<string, string> $details
   */
  public function fanoutAuditEvent(string $orgId, string $eventType, string $actorUUID, array $details, string $createdAt): void
  {
    if (!isset(self::EVENT_LABELS[$eventType])) {
      return;
    }

    $org = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    $organizationName = trim((string) ($org['name'] ?? 'Organization'));
    if ($organizationName === '') {
      $organizationName = 'Organization';
    }

    $roleIndex = $this->activeRoleIndexForOrganization($orgId);
    $recipientUUIDs = $this->resolveRecipients($roleIndex, $eventType, $actorUUID, $details);
    if ($recipientUUIDs === []) {
      $this->publishEvent(
        orgId: $orgId,
        eventType: $eventType,
        actorUUID: $actorUUID,
        eventLabel: self::EVENT_LABELS[$eventType],
        eventDetail: $this->buildEventDetail($eventType, $details),
        createdAt: $createdAt,
        details: $details,
        recipientUUIDs: [],
        roleIndex: $roleIndex
      );
      return;
    }

    $label = self::EVENT_LABELS[$eventType];
    $eventDetail = $this->buildEventDetail($eventType, $details);

    foreach ($recipientUUIDs as $recipientUUID) {
      $this->incrementUnread($recipientUUID, $orgId);

      $recipient = UserRepository::getByUUID($recipientUUID);
      if ($recipient === null) {
        continue;
      }

      $email = trim(InputSanitizer::sanitizeEmail((string) ($recipient->email ?? '')));
      if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        continue;
      }

      if (defined('PHPUNIT_COMPOSER_INSTALL')) {
        continue;
      }

      try {
        EmailGarum::sendOrganizationEventNotification(
          emailTo: $email,
          recipientName: (string) ($recipient->full_name ?? ''),
          organizationName: $organizationName,
          eventLabel: $label,
          eventDetail: $eventDetail,
          eventTimeUTC: $createdAt
        );
      } catch (\Throwable) {
        // Notification email failures must never block business flows.
      }
    }

    $this->publishEvent(
      orgId: $orgId,
      eventType: $eventType,
      actorUUID: $actorUUID,
      eventLabel: $label,
      eventDetail: $eventDetail,
      createdAt: $createdAt,
      details: $details,
      recipientUUIDs: $recipientUUIDs,
      roleIndex: $roleIndex
    );
  }

  /**
   * @return array<string, string>
   */
  private function activeRoleIndexForOrganization(string $orgId): array
  {
    $activeMembers = OrganizationMemberRepository::forOrganization($orgId, null, 'active');
    $roleIndex = [];
    foreach ($activeMembers as $member) {
      $uuid = trim((string) ($member['user']->user_uuid ?? ''));
      $role = $this->normalizeRole((string) $member['role']);
      if ($uuid === '' || $role === '') {
        continue;
      }

      $roleIndex[$uuid] = $role;
    }

    return $roleIndex;
  }

  /**
    * @param array<string, string> $roleIndex
   * @param array<string, string> $details
   * @return array<int, string>
   */
  private function resolveRecipients(array $roleIndex, string $eventType, string $actorUUID, array $details): array
  {
    $recipients = [];

    $addByRole = static function (array $index, array $roles) use (&$recipients, $actorUUID): void {
      foreach ($index as $uuid => $role) {
        if ($uuid === $actorUUID) {
          continue;
        }
        if (in_array($role, $roles, true)) {
          $recipients[] = $uuid;
        }
      }
    };

    switch ($eventType) {
      case 'access.requested':
      case 'invite.accepted':
        $addByRole($roleIndex, ['owner', 'coordinator']);
        break;

      case 'access.request.approved':
      case 'access.request.rejected':
        $requester = trim((string) ($details['requester_uuid'] ?? ''));
        if ($requester !== '' && $requester !== $actorUUID) {
          $recipients[] = $requester;
        }
        break;

      case 'relationship.role_updated':
      case 'relationship.revoked':
        $target = trim((string) ($details['target_user_uuid'] ?? ''));
        if ($target !== '' && $target !== $actorUUID) {
          $recipients[] = $target;
        }
        break;

      case 'ownership.transferred':
        $fromUser = trim((string) ($details['from_user_uuid'] ?? ''));
        $toUser = trim((string) ($details['to_user_uuid'] ?? ''));
        if ($fromUser !== '' && $fromUser !== $actorUUID) {
          $recipients[] = $fromUser;
        }
        if ($toUser !== '' && $toUser !== $actorUUID) {
          $recipients[] = $toUser;
        }
        break;
    }

    $recipients = array_values(array_unique(array_filter($recipients, static fn(string $uuid): bool => $uuid !== '')));

    return $recipients;
  }

  /**
   * @param array<string, string> $details
   * @param array<int, string> $recipientUUIDs
   * @param array<string, string> $roleIndex
   */
  private function publishEvent(
    string $orgId,
    string $eventType,
    string $actorUUID,
    string $eventLabel,
    string $eventDetail,
    string $createdAt,
    array $details,
    array $recipientUUIDs,
    array $roleIndex
  ): void {
    $orgRoles = array_values(array_unique(array_values($roleIndex)));

    $recipientRoles = array_values(array_unique(array_filter(array_map(
      fn(string $uuid): string => $this->normalizeRole((string) ($roleIndex[$uuid] ?? '')),
      $recipientUUIDs
    ), fn(string $role): bool => $role !== '')));

    $payload = [
      'schema' => 'organization.notification.event.v1',
      'organization_id' => $orgId,
      'event_type' => $eventType,
      'event_label' => $eventLabel,
      'event_detail' => $eventDetail,
      'actor_uuid' => $actorUUID,
      'created_at' => $createdAt,
      'recipient_count' => count($recipientUUIDs),
      'recipient_roles' => $recipientRoles,
      'roles_present' => $orgRoles,
      'details' => $details,
    ];

    $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) {
      return;
    }

    Database::publish(Keys::organizationNotificationChannelOrg($orgId), $encoded);

    // Role channels let all role groups hook into org activity without forcing unread/email delivery.
    foreach ($orgRoles as $role) {
      Database::publish(Keys::organizationNotificationChannelRole($orgId, $role), $encoded);
    }

    foreach ($recipientUUIDs as $recipientUUID) {
      Database::publish(Keys::organizationNotificationChannelUser($recipientUUID), $encoded);
    }

    $eventsKey = Keys::organizationNotificationEventsByOrg($orgId);
    Database::lpush($eventsKey, $encoded);
    Database::ltrim($eventsKey, 0, self::RECENT_EVENT_BUFFER_LIMIT - 1);
    Database::expire($eventsKey, 7 * 24 * 3600);
  }

  /**
   * Handles normalizeRole operation.
   */
  private function normalizeRole(string $role): string
  {
    $normalized = strtolower(trim($role));

    return match ($normalized) {
      'owner', 'coordinator', 'contributor', 'member', 'viewer', 'delegate' => $normalized,
      default => '',
    };
  }

  /**
   * @param array<string, string> $details
   */
  private function buildEventDetail(string $eventType, array $details): string
  {
    if ($eventType === 'relationship.role_updated') {
      $role = trim((string) ($details['role'] ?? ''));
      if ($role !== '') {
        return 'New role: ' . $role;
      }
    }

    if ($eventType === 'access.requested') {
      $requestId = trim((string) ($details['request_id'] ?? ''));
      if ($requestId !== '') {
        return 'Request ID: ' . $requestId;
      }
    }

    return '';
  }

  /**
   * Handles incrementUnread operation.
   */
  private function incrementUnread(string $userUUID, string $orgId): void
  {
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    $orgId = trim(InputSanitizer::sanitizeString($orgId));
    if ($userUUID === '' || $orgId === '') {
      return;
    }

    $key = Keys::organizationNotificationUnreadByUser($userUUID);
    $current = (int) Database::hget($key, $orgId);
    $next = $current + 1;
    Database::hset($key, [$orgId => (string) $next]);

    $totalKey = Keys::organizationNotificationTotalByUser($userUUID);
    $total = (int) Database::get($totalKey);
    Database::set($totalKey, (string) max(0, $total + 1), 86400);
  }
}
