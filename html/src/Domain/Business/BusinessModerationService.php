<?php declare(strict_types=1);

namespace PayCal\Domain\Business;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\UserRepository;

/**
 * Submit, review, approve, and reject business public names.
 */
final class BusinessModerationService
{
  /**
   * @return array{
   *   blocked: bool,
   *   message: string,
   *   fields: array<string, string>,
   *   user_message: string,
   *   evaluation: array{
   *     decision: string,
   *     score: int,
   *     reasons: array<int, string>,
   *     safe_display_name: string,
   *     normalized_name: string,
   *     search_name: string,
   *     name_skeleton: string
   *   }
   * }
   */
  public static function evaluateCreationName(
    string $ownerUUID,
    string $rawName,
    string $businessType,
    bool $requestPublicListing = true,
  ): array {
    $trustDefaults = BusinessTrustPolicy::defaultTrustFields($ownerUUID, $businessType);
    $paidStatus = BusinessTrustPolicy::resolvePaidStatus($ownerUUID);
    $trustDefaults['paid_status'] = $paidStatus;

    if ($businessType === 'personal') {
      $safeName = trim($rawName);
      $personalEvaluation = BusinessNameGuard::evaluate($safeName);

      return [
        'blocked' => false,
        'message' => '',
        'user_message' => 'Business created.',
        'evaluation' => [
          'decision' => BusinessNameGuard::DECISION_APPROVED,
          'score' => 0,
          'reasons' => [],
          'safe_display_name' => $safeName,
          'normalized_name' => $safeName,
          'search_name' => mb_strtolower($safeName, 'UTF-8'),
          'name_skeleton' => $personalEvaluation['name_skeleton'],
        ],
        'fields' => $trustDefaults + [
          'display_name' => '',
          'normalized_name' => $safeName,
          'search_name' => mb_strtolower($safeName, 'UTF-8'),
          'name_skeleton' => $personalEvaluation['name_skeleton'],
        ],
      ];
    }

    $evaluation = BusinessNameGuard::evaluate($rawName);
    if ($evaluation['decision'] === BusinessNameGuard::DECISION_REJECTED && $evaluation['score'] >= 90) {
      return [
        'blocked' => true,
        'message' => 'This business name cannot be used.',
        'user_message' => 'This business name cannot be used publicly. Choose a different public display name.',
        'fields' => [],
        'evaluation' => $evaluation,
      ];
    }

    $fields = $trustDefaults + [
      'display_name' => '',
      'normalized_name' => $evaluation['normalized_name'],
      'search_name' => $evaluation['search_name'],
      'name_skeleton' => $evaluation['name_skeleton'],
      'moderation_score' => (string) $evaluation['score'],
      'moderation_reasons' => implode(',', $evaluation['reasons']),
    ];

    $fields = self::applyNameDecision($fields, $evaluation, $requestPublicListing);

    $userMessage = match ($evaluation['decision']) {
      BusinessNameGuard::DECISION_APPROVED => $requestPublicListing
        ? 'Business created.'
        : 'Business created. It is private until the public name is approved.',
      BusinessNameGuard::DECISION_PENDING => 'Your business is active privately. Public search visibility is pending review.',
      default => 'This business name cannot be used publicly. Choose a different public display name.',
    };

    return [
      'blocked' => false,
      'message' => '',
      'user_message' => $userMessage,
      'fields' => $fields,
      'evaluation' => $evaluation,
    ];
  }

  /**
   * @return array{
   *   success: bool,
   *   message: string,
   *   user_message: string,
   *   fields: array<string, string>,
   *   cooldown?: array<string, int|string|bool>
   * }
   */
  public static function handleRename(
    string $businessId,
    string $actorUUID,
    string $newName,
    bool $isAdmin = false,
  ): array {
    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ($business === []) {
      return ['success' => false, 'message' => 'Business not found.', 'user_message' => '', 'fields' => []];
    }

    if (!$isAdmin) {
      if (trim((string) ($business['rename_abuse_lock'] ?? '')) === '1') {
        return [
          'success' => false,
          'message' => 'Rename locked pending admin review.',
          'user_message' => 'This business is locked from public rename submissions until an admin reviews it.',
          'fields' => [],
        ];
      }

      $cooldown = self::evaluateRenameCooldown($business);
      if (!$cooldown['allowed']) {
        return [
          'success' => false,
          'message' => 'Rename cooldown active.',
          'user_message' => self::formatRenameCooldownMessage($cooldown),
          'fields' => [],
          'cooldown' => $cooldown,
        ];
      }
    }

    $evaluation = BusinessNameGuard::evaluate($newName);
    if ($evaluation['decision'] === BusinessNameGuard::DECISION_REJECTED && $evaluation['score'] >= 90 && !$isAdmin) {
      return [
        'success' => false,
        'message' => 'This business name cannot be used.',
        'user_message' => 'This business name cannot be used publicly. Choose a different public display name.',
        'fields' => [],
      ];
    }

    $oldName = trim((string) ($business['name'] ?? ''));
    $newName = $evaluation['safe_display_name'];
    $businessType = strtolower(trim((string) ($business['business_type'] ?? 'shared')));
    $approvedName = BusinessVisibilityPolicy::resolveApprovedDisplayName($business);
    $pendingName = trim((string) ($business['pending_display_name'] ?? ''));

    if ($newName === $oldName && ($pendingName === '' || $pendingName === $newName)) {
      return [
        'success' => true,
        'message' => 'Business name unchanged.',
        'user_message' => '',
        'fields' => [],
      ];
    }

    $fields = [
      'name' => $newName,
      'normalized_name' => $evaluation['normalized_name'],
      'search_name' => $evaluation['search_name'],
      'name_skeleton' => $evaluation['name_skeleton'],
      'moderation_score' => (string) $evaluation['score'],
      'moderation_reasons' => implode(',', $evaluation['reasons']),
      'renamed_at' => date('c'),
      'last_reviewed_at' => date('c'),
    ];

    if ($businessType === 'personal') {
      BusinessAuditLedger::record(
        $businessId,
        BusinessAuditLedger::EVENT_RENAMED,
        $actorUUID,
        $isAdmin ? 'admin' : 'user',
        ['name' => $oldName],
        ['name' => $newName],
        $evaluation['reasons'],
      );

      return [
        'success' => true,
        'message' => 'Business renamed.',
        'user_message' => 'Business renamed.',
        'fields' => $fields,
      ];
    }

    $wasListed = strtolower(trim((string) ($business['visibility'] ?? ''))) === BusinessVisibilityPolicy::VISIBILITY_LISTED;
    $fields = self::applyRenameReviewState($fields, $evaluation, $business, $wasListed, $oldName, $isAdmin);
    $outcome = (string) ($fields['_rename_outcome'] ?? 'needs_review');
    unset($fields['_rename_outcome']);

    BusinessAuditLedger::record(
      $businessId,
      BusinessAuditLedger::EVENT_RENAMED,
      $actorUUID,
      $isAdmin ? 'admin' : 'user',
      ['name' => $oldName, 'approved_display_name' => $approvedName],
      ['name' => $newName, 'pending_display_name' => (string) ($fields['pending_display_name'] ?? '')],
      $evaluation['reasons'],
    );

    self::recordRenameAuditEvent($businessId, $actorUUID, $evaluation, $isAdmin ? 'admin' : 'user', $outcome);

    $userMessage = match ($outcome) {
      'auto_approved' => 'Your public business name was updated.',
      'needs_review' => $wasListed && $approvedName !== ''
        ? 'Rename submitted for review. Your current public name stays visible until approved.'
        : 'Your business was renamed privately. The new name will appear in public search after review.',
      'rejected' => 'This business name cannot be used publicly. Your current public name is unchanged.',
      'suspended' => 'This rename was blocked and the public listing was suspended pending review.',
      default => 'Rename submitted for review.',
    };

    return [
      'success' => true,
      'message' => 'Business rename submitted.',
      'user_message' => $userMessage,
      'fields' => $fields,
    ];
  }

  /**
   * @return array<int, array<string, string>>
   */
  public static function listQueue(int $limit = 100): array
  {
    $rows = [];
    foreach (Database::smembers(Keys::BUSINESS_MODERATION_QUEUE) as $businessId) {
      $businessId = trim((string) $businessId);
      if ($businessId === '') {
        continue;
      }

      $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
      if ($business === []) {
        Database::srem(Keys::BUSINESS_MODERATION_QUEUE, $businessId);
        continue;
      }

      $moderation = strtolower(trim((string) ($business['moderation_status'] ?? '')));
      if (!in_array($moderation, [BusinessVisibilityPolicy::MODERATION_PENDING, BusinessVisibilityPolicy::MODERATION_NEEDS_REVIEW], true)) {
        Database::srem(Keys::BUSINESS_MODERATION_QUEUE, $businessId);
        continue;
      }

      $ownerUUID = trim((string) ($business['owner_uuid'] ?? ''));
      $owner = $ownerUUID !== '' ? UserRepository::getByUUID($ownerUUID) : null;

      $rows[] = [
        'business_id' => $businessId,
        'business_name' => trim((string) ($business['name'] ?? '')),
        'approved_display_name' => BusinessVisibilityPolicy::resolveApprovedDisplayName($business),
        'pending_display_name' => trim((string) ($business['pending_display_name'] ?? '')),
        'owner_uuid' => $ownerUUID,
        'owner_email' => $owner !== null ? $owner->email : '',
        'paid_status' => (string) ($business['paid_status'] ?? ''),
        'visibility' => (string) ($business['visibility'] ?? ''),
        'moderation_status' => $moderation,
        'moderation_score' => (string) ($business['moderation_score'] ?? '0'),
        'moderation_reasons' => (string) ($business['moderation_reasons'] ?? ''),
        'created_at' => (string) ($business['created_at'] ?? ''),
        'last_reviewed_at' => (string) ($business['last_reviewed_at'] ?? ''),
      ];

      if (count($rows) >= $limit) {
        break;
      }
    }

    usort($rows, static fn(array $a, array $b): int => strcmp((string) $b['moderation_score'], (string) $a['moderation_score']));

    return $rows;
  }

  /**
   * Approve listing.
   */
  public static function approveListing(string $businessId, string $adminUUID): bool
  {
    return self::adminAction($businessId, $adminUUID, 'approve_listing', function (array $business) use ($adminUUID): array {
      $pendingName = trim((string) ($business['pending_display_name'] ?? ''));
      $name = $pendingName !== ''
        ? $pendingName
        : trim((string) ($business['name'] ?? ''));
      $sponsorUUID = BusinessVisibilityPolicy::resolveListingSponsorUUID($business);
      $trustFields = $sponsorUUID !== '' ? BusinessTrustPolicy::ownerTrustSnapshot($sponsorUUID) : [];

      return BusinessVisibilityPolicy::applyListingEnabled($trustFields + [
        'name' => $name,
        'approved_display_name' => $name,
        'pending_display_name' => '',
        'display_name' => $name,
        'search_name' => mb_strtolower($name, 'UTF-8'),
        'normalized_name' => $name,
        'approved_by_uuid' => $adminUUID,
        'approved_at' => date('c'),
        'moderation_score' => '0',
        'moderation_reasons' => '',
        'suspended_reason' => '',
        'rename_abuse_lock' => '',
      ]);
    }, BusinessAuditLedger::EVENT_LISTING_ENABLED);
  }

  /**
   * Reject name.
   */
  public static function rejectName(string $businessId, string $adminUUID, string $reason = 'manual_admin_decision'): bool
  {
    return self::adminAction($businessId, $adminUUID, 'reject_name', function (array $business) use ($reason): array {
      $approvedName = BusinessVisibilityPolicy::resolveApprovedDisplayName($business);
      $wasListed = strtolower(trim((string) ($business['visibility'] ?? ''))) === BusinessVisibilityPolicy::VISIBILITY_LISTED;

      $fields = [
        'pending_display_name' => '',
        'moderation_reasons' => $reason,
        'last_reviewed_at' => date('c'),
        'rename_abuse_lock' => '',
      ];

      if ($wasListed && $approvedName !== '') {
        $fields['visibility'] = BusinessVisibilityPolicy::VISIBILITY_LISTED;
        $fields['moderation_status'] = BusinessVisibilityPolicy::MODERATION_APPROVED;
        $fields['display_name'] = $approvedName;
      } else {
        $fields['visibility'] = BusinessVisibilityPolicy::VISIBILITY_PRIVATE;
        $fields['moderation_status'] = BusinessVisibilityPolicy::MODERATION_REJECTED;
        $fields['display_name'] = '';
      }

      return $fields;
    }, BusinessAuditLedger::EVENT_NAME_REJECTED);
  }

  /**
   * Hide listing.
   */
  public static function hideListing(string $businessId, string $adminUUID): bool
  {
    return self::adminAction($businessId, $adminUUID, 'hide_listing', function (): array {
      return BusinessVisibilityPolicy::applyListingHidden([
        'display_name' => '',
        'last_reviewed_at' => date('c'),
      ]);
    }, BusinessAuditLedger::EVENT_LISTING_HIDDEN);
  }

  /**
   * Suspend business.
   */
  public static function suspendBusiness(string $businessId, string $adminUUID, string $reason): bool
  {
    return self::adminAction($businessId, $adminUUID, 'suspend_business', function () use ($reason): array {
      return BusinessVisibilityPolicy::applySuspended([
        'display_name' => '',
        'last_reviewed_at' => date('c'),
      ], $reason);
    }, BusinessAuditLedger::EVENT_SUSPENDED);
  }

  /**
   * Restore business.
   */
  public static function restoreBusiness(string $businessId, string $adminUUID): bool
  {
    return self::adminAction($businessId, $adminUUID, 'restore_business', function (): array {
      return [
        'visibility' => BusinessVisibilityPolicy::VISIBILITY_PRIVATE,
        'moderation_status' => BusinessVisibilityPolicy::MODERATION_NEEDS_REVIEW,
        'suspended_reason' => '',
        'last_reviewed_at' => date('c'),
      ];
    }, BusinessAuditLedger::EVENT_RESTORED);
  }

  /**
   * Mark owner trusted.
   */
  public static function markOwnerTrusted(string $businessId, string $adminUUID): bool
  {
    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ($business === []) {
      return false;
    }

    $ownerUUID = trim((string) ($business['owner_uuid'] ?? ''));
    if ($ownerUUID === '') {
      return false;
    }

    Database::hset(Keys::USER . ':' . $ownerUUID, [
      'business_trust_level' => BusinessTrustPolicy::TRUST_MANUAL_TRUSTED,
    ]);

    Database::hset(Keys::BUSINESS . ':' . $businessId, [
      'trust_level' => BusinessTrustPolicy::TRUST_MANUAL_TRUSTED,
      'last_reviewed_at' => date('c'),
    ]);

    BusinessAuditLedger::record(
      $businessId,
      BusinessAuditLedger::EVENT_TRUST_CHANGED,
      $adminUUID,
      'admin',
      [],
      ['trust_level' => BusinessTrustPolicy::TRUST_MANUAL_TRUSTED],
      ['manual_admin_decision'],
    );

    BusinessSearchIndex::sync($businessId);

    return true;
  }

  /**
   * Enqueue.
   */
  public static function enqueue(string $businessId): void
  {
    if ($businessId !== '') {
      Database::sadd(Keys::BUSINESS_MODERATION_QUEUE, $businessId);
    }
  }

  /**
   * Dequeue.
   */
  public static function dequeue(string $businessId): void
  {
    if ($businessId !== '') {
      Database::srem(Keys::BUSINESS_MODERATION_QUEUE, $businessId);
    }
  }

  /**
   * Shared-business renames always require admin review before public listing.
   *
   * @param array<string, string> $fields
   * @param array<string, string> $business
   * @param array{
   *   decision: string,
   *   score: int,
   *   reasons: array<int, string>,
   *   safe_display_name: string,
   *   normalized_name: string,
   *   search_name: string,
   *   name_skeleton: string
   * } $evaluation
   * @return array<string, string>
   */
  private static function applyRenameReviewState(
    array $fields,
    array $evaluation,
    array $business,
    bool $wasListed,
    string $oldName,
    bool $isAdmin = false,
  ): array {
    $rejectPublicMax = self::rejectPublicMaxScore();
    $approvedName = BusinessVisibilityPolicy::resolveApprovedDisplayName($business);
    $proposedName = $evaluation['safe_display_name'];
    $now = date('c');

    $fields['name'] = $proposedName;
    $fields['normalized_name'] = $evaluation['normalized_name'];
    $fields['search_name'] = $evaluation['search_name'];
    $fields['name_skeleton'] = $evaluation['name_skeleton'];
    $fields['last_public_rename_submitted_at'] = $now;
    $fields['renamed_at'] = $now;
    $fields['last_reviewed_at'] = $now;

    if ($evaluation['score'] >= $rejectPublicMax || ($evaluation['decision'] === BusinessNameGuard::DECISION_REJECTED && $evaluation['score'] >= 90)) {
      $fields['_rename_outcome'] = 'suspended';
      $fields['pending_display_name'] = '';
      $fields['rename_abuse_lock'] = '1';

      return BusinessVisibilityPolicy::applySuspended($fields, 'rename_abuse');
    }

    if ($evaluation['decision'] === BusinessNameGuard::DECISION_APPROVED || $isAdmin) {
      $fields['_rename_outcome'] = 'auto_approved';
      $fields['approved_display_name'] = $proposedName;
      $fields['pending_display_name'] = '';
      $fields['display_name'] = $proposedName;
      $fields['moderation_status'] = BusinessVisibilityPolicy::MODERATION_APPROVED;
      $fields['visibility'] = ($wasListed || $approvedName !== '')
        ? BusinessVisibilityPolicy::VISIBILITY_LISTED
        : BusinessVisibilityPolicy::VISIBILITY_PRIVATE;

      return $fields;
    }

    if ($evaluation['decision'] === BusinessNameGuard::DECISION_REJECTED) {
      $fields['_rename_outcome'] = 'rejected';
      $fields['pending_display_name'] = '';

      if ($wasListed && $approvedName !== '') {
        $fields['visibility'] = BusinessVisibilityPolicy::VISIBILITY_LISTED;
        $fields['moderation_status'] = BusinessVisibilityPolicy::MODERATION_APPROVED;
        $fields['display_name'] = $approvedName;
      } else {
        $fields['visibility'] = BusinessVisibilityPolicy::VISIBILITY_PRIVATE;
        $fields['moderation_status'] = BusinessVisibilityPolicy::MODERATION_REJECTED;
        $fields['display_name'] = '';
      }

      return $fields;
    }

    $fields['_rename_outcome'] = 'needs_review';
    $fields['pending_display_name'] = $proposedName;
    $fields['moderation_status'] = BusinessVisibilityPolicy::MODERATION_NEEDS_REVIEW;

    if ($wasListed && $approvedName !== '') {
      $fields['visibility'] = BusinessVisibilityPolicy::VISIBILITY_LISTED;
      $fields['display_name'] = $approvedName;
    } else {
      $fields['visibility'] = BusinessVisibilityPolicy::VISIBILITY_PRIVATE;
      $fields['display_name'] = '';
    }

    return $fields;
  }

  /**
   * @param array<string, string> $fields
   * @param array{
   *   decision: string,
   *   score: int,
   *   reasons: array<int, string>,
   *   safe_display_name: string,
   *   normalized_name: string,
   *   search_name: string,
   *   name_skeleton: string
   * } $evaluation
   * @return array<string, string>
   */
  private static function applyNameDecision(array $fields, array $evaluation, bool $requestPublicListing): array
  {
    $decision = $evaluation['decision'];
    $safeName = $evaluation['safe_display_name'];

    if ($decision === BusinessNameGuard::DECISION_APPROVED) {
      $fields['moderation_status'] = BusinessVisibilityPolicy::MODERATION_APPROVED;
      if ($requestPublicListing) {
        $fields['visibility'] = BusinessVisibilityPolicy::VISIBILITY_LISTED;
        $fields['approved_display_name'] = $safeName;
        $fields['display_name'] = $safeName;
        $fields['pending_display_name'] = '';
      } else {
        $fields['visibility'] = BusinessVisibilityPolicy::VISIBILITY_PRIVATE;
        $fields['approved_display_name'] = '';
        $fields['display_name'] = '';
        $fields['pending_display_name'] = '';
      }

      return $fields;
    }

    if ($decision === BusinessNameGuard::DECISION_PENDING) {
      $fields['moderation_status'] = BusinessVisibilityPolicy::MODERATION_NEEDS_REVIEW;
      $fields['visibility'] = BusinessVisibilityPolicy::VISIBILITY_PRIVATE;
      $fields['pending_display_name'] = $safeName;
      $fields['approved_display_name'] = '';
      $fields['display_name'] = '';

      return $fields;
    }

    $fields['moderation_status'] = BusinessVisibilityPolicy::MODERATION_REJECTED;
    $fields['visibility'] = BusinessVisibilityPolicy::VISIBILITY_PRIVATE;
    $fields['approved_display_name'] = '';
    $fields['pending_display_name'] = '';
    $fields['display_name'] = '';

    return $fields;
  }

  /**
   * @param array<string, string> $business
   * @return array{
   *   allowed: bool,
   *   cooldown_seconds: int,
   *   remaining_seconds: int,
   *   elapsed_seconds: int,
   *   available_at: string,
   *   last_renamed_at: string,
   *   trust_tier: string,
   *   rename_limit_per_day: int
   * }
   */
  public static function evaluateRenameCooldown(array $business): array
  {
    $trustLevel = strtolower(trim((string) ($business['trust_level'] ?? BusinessTrustPolicy::TRUST_NEW)));
    $isTrusted = in_array($trustLevel, [
      BusinessTrustPolicy::TRUST_PAID,
      BusinessTrustPolicy::TRUST_BUSINESS_VERIFIED,
      BusinessTrustPolicy::TRUST_MANUAL_TRUSTED,
    ], true);

    $cooldownSeconds = $isTrusted
      ? self::trustConfigInt('business_public_rename_cooldown_seconds_trusted', self::trustConfigInt('rename_cooldown_seconds_trusted_owner', 21600))
      : self::trustConfigInt('business_public_rename_cooldown_seconds', self::trustConfigInt('rename_cooldown_seconds_new_owner', 86400));

    $renamedAt = trim((string) ($business['last_public_rename_submitted_at'] ?? $business['renamed_at'] ?? ''));
    if ($renamedAt === '') {
      return [
        'allowed' => true,
        'cooldown_seconds' => $cooldownSeconds,
        'remaining_seconds' => 0,
        'elapsed_seconds' => 0,
        'available_at' => '',
        'last_renamed_at' => '',
        'trust_tier' => $isTrusted ? 'trusted' : 'new',
        'rename_limit_per_day' => 1,
      ];
    }

    $lastRename = strtotime($renamedAt);
    if ($lastRename === false) {
      return [
        'allowed' => true,
        'cooldown_seconds' => $cooldownSeconds,
        'remaining_seconds' => 0,
        'elapsed_seconds' => 0,
        'available_at' => '',
        'last_renamed_at' => $renamedAt,
        'trust_tier' => $isTrusted ? 'trusted' : 'new',
        'rename_limit_per_day' => 1,
      ];
    }

    $elapsed = max(0, time() - $lastRename);
    $remaining = max(0, $cooldownSeconds - $elapsed);

    return [
      'allowed' => $remaining === 0,
      'cooldown_seconds' => $cooldownSeconds,
      'remaining_seconds' => $remaining,
      'elapsed_seconds' => $elapsed,
      'available_at' => $remaining > 0 ? date('c', $lastRename + $cooldownSeconds) : '',
      'last_renamed_at' => $renamedAt,
      'trust_tier' => $isTrusted ? 'trusted' : 'new',
      'rename_limit_per_day' => 1,
    ];
  }

  /** @param array<string, int|string> $cooldown */
  /** @param array<string, int|string|bool> $cooldown */
  public static function formatRenameCooldownMessage(array $cooldown): string
  {
    $remaining = (int) ($cooldown['remaining_seconds'] ?? 0);
    $cooldownSeconds = (int) ($cooldown['cooldown_seconds'] ?? 0);
    $limitPerDay = (int) ($cooldown['rename_limit_per_day'] ?? 1);
    $trustTier = (string) ($cooldown['trust_tier'] ?? 'new');
    $availableAt = trim((string) ($cooldown['available_at'] ?? ''));

    $policyLine = sprintf(
      'Each business can submit one public rename every %s.',
      self::formatDuration($cooldownSeconds),
    );

    $remainingLine = sprintf(
      'Try again in %s',
      self::formatDuration($remaining),
    );

    if ($availableAt !== '') {
      $availableLabel = date('M j, Y g:i A', strtotime($availableAt) ?: time());
      $remainingLine .= ' (after ' . $availableLabel . ')';
    }

    return $policyLine . ' ' . $remainingLine . '.';
  }

  /**
   * Format duration.
   */
  private static function formatDuration(int $seconds): string
  {
    if ($seconds <= 0) {
      return '0 minutes';
    }

    $days = intdiv($seconds, 86400);
    $hours = intdiv($seconds % 86400, 3600);
    $minutes = intdiv($seconds % 3600, 60);

    $parts = [];
    if ($days > 0) {
      $parts[] = $days . ' ' . ($days === 1 ? 'day' : 'days');
    }
    if ($hours > 0) {
      $parts[] = $hours . ' ' . ($hours === 1 ? 'hour' : 'hours');
    }
    if ($minutes > 0 || $parts === []) {
      $parts[] = $minutes . ' ' . ($minutes === 1 ? 'minute' : 'minutes');
    }

    return implode(' ', $parts);
  }

  /** @return array<string, mixed> */
  private static function trustConfig(): array
  {
    static $config = null;
    if ($config !== null) {
      return $config;
    }

    $path = dirname(__DIR__, 4) . '/config/business-trust.php';
    /** @var array<string, mixed> $loaded */
    $loaded = is_file($path) ? require $path : [];
    $config = $loaded;

    return $config;
  }

  /**
   * Trust config int.
   */
  private static function trustConfigInt(string $key, int $default): int
  {
    $config = self::trustConfig();
    $value = $config[$key] ?? $default;

    return is_numeric($value) ? (int) $value : $default;
  }

  /**
   * Reject public max score.
   */
  private static function rejectPublicMaxScore(): int
  {
    $config = self::trustConfig();
    $name = is_array($config['name'] ?? null) ? $config['name'] : [];
    $value = $name['reject_public_max_score'] ?? 89;

    return is_numeric($value) ? (int) $value : 89;
  }

  /**
   * @param callable(array<string, string>): array<string, string> $mutator
   */
  private static function adminAction(string $businessId, string $adminUUID, string $action, callable $mutator, string $auditEvent): bool
  {
    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ($business === []) {
      return false;
    }

    $updates = $mutator($business);
    Database::hset(Keys::BUSINESS . ':' . $businessId, $updates);

    BusinessAuditLedger::record(
      $businessId,
      $auditEvent,
      $adminUUID,
      'admin',
      ['visibility' => (string) ($business['visibility'] ?? '')],
      $updates,
      [(string) ($updates['moderation_reasons'] ?? $action)],
    );

    self::dequeue($businessId);
    BusinessSearchIndex::sync($businessId);

    return true;
  }

  /** @param array{decision: string, reasons: array<int, string>} $evaluation */
  private static function recordNameAuditEvent(string $businessId, string $actorUUID, array $evaluation, string $actorType): void
  {
    $event = match ($evaluation['decision']) {
      BusinessNameGuard::DECISION_APPROVED => BusinessAuditLedger::EVENT_NAME_AUTO_APPROVED,
      BusinessNameGuard::DECISION_PENDING => BusinessAuditLedger::EVENT_NAME_NEEDS_REVIEW,
      default => BusinessAuditLedger::EVENT_NAME_REJECTED,
    };

    BusinessAuditLedger::record(
      $businessId,
      $event,
      $actorUUID,
      $actorType,
      [],
      ['decision' => $evaluation['decision']],
      $evaluation['reasons'],
    );

    if ($evaluation['decision'] === BusinessNameGuard::DECISION_PENDING) {
      self::enqueue($businessId);
    } elseif ($evaluation['decision'] === BusinessNameGuard::DECISION_APPROVED) {
      self::dequeue($businessId);
    } else {
      self::enqueue($businessId);
    }
  }

  /** @param array{decision: string, reasons: array<int, string>} $evaluation */
  private static function recordRenameAuditEvent(string $businessId, string $actorUUID, array $evaluation, string $actorType, string $outcome): void
  {
    $event = match ($outcome) {
      'auto_approved' => BusinessAuditLedger::EVENT_NAME_AUTO_APPROVED,
      'rejected' => BusinessAuditLedger::EVENT_NAME_REJECTED,
      'suspended' => BusinessAuditLedger::EVENT_SUSPENDED,
      default => BusinessAuditLedger::EVENT_NAME_NEEDS_REVIEW,
    };

    BusinessAuditLedger::record(
      $businessId,
      $event,
      $actorUUID,
      $actorType,
      [],
      ['decision' => $evaluation['decision'], 'outcome' => $outcome],
      $evaluation['reasons'],
    );

    if ($outcome === 'needs_review' || $outcome === 'suspended') {
      self::enqueue($businessId);
    } elseif ($outcome === 'auto_approved') {
      self::dequeue($businessId);
    }
  }

  /**
   * @param array{
   *   decision: string,
   *   reasons: array<int, string>,
   *   safe_display_name?: string
   * } $evaluation
   */
  public static function recordCreationAudit(string $businessId, string $ownerUUID, array $evaluation): void
  {
    BusinessAuditLedger::record(
      $businessId,
      BusinessAuditLedger::EVENT_CREATED,
      $ownerUUID,
      'user',
      [],
      ['name' => $evaluation['safe_display_name'] ?? ''],
    );

    BusinessAuditLedger::record(
      $businessId,
      BusinessAuditLedger::EVENT_NAME_SUBMITTED,
      $ownerUUID,
      'user',
      [],
      ['decision' => $evaluation['decision']],
      $evaluation['reasons'],
    );

    self::recordNameAuditEvent($businessId, $ownerUUID, $evaluation, 'user');
  }
}
