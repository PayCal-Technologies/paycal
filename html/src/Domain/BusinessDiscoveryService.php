<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\Currency;
use PayCal\Domain\Enums\PayFrequency;
use PayCal\Domain\Enums\SiteStatus;
use PayCal\Domain\Enums\Subscription;
use PayCal\Domain\Enums\Timezone;
use PayCal\Infrastructure\Audit\SystemAuditRepository;
use PayCal\Infrastructure\Business\BusinessEncryptionService;
use PayCal\Infrastructure\Cache\EarningsCacheService;
use PayCal\Domain\Business\BusinessModerationService;
use PayCal\Domain\Business\BusinessSearchIndex;
use PayCal\Observability\Lens;

/**
 * BusinessDiscoveryService.php
 *
 * Purpose: Core business domain service for membership, invites, access
 * requests, settings, audit history, and shared-encryption coordination.
 *
 * Developer notes:
 * - This class is intentionally the policy hub for business behavior.
 *   Controllers should delegate here instead of re-implementing role/scope
 *   checks.
 * - Result contracts use the internal ok/fail array shape consistently.
 *   Maintain that shape so controllers and tests can consume responses without
 *   per-call branching rules.
 * - Membership roles, scope presets, and transition rules are tightly coupled.
 *   Change them together and review every access gate that consumes them.
 * - Business writes should append audit events unless the path is strictly
 *   internal and side-effect free.
 * - Shared-encryption consent and DEK-wrap bootstrapping are security-critical.
 *   Avoid introducing alternate paths that bypass these checks.
 */
/**
 * Business coordination and policy engine.
 *
 * Responsibilities:
 * - Create and manage connections and role assignments.
 * - Enforce scope-based authorization for sites, work, settings, and audit.
 * - Drive invite, access-request, and notification workflows.
 * - Synchronize consent-driven shared encryption metadata for business members.
 *
 * This file is intentionally large because it centralizes cross-cutting org
 * rules. If extracting helpers, preserve this class as the canonical policy
 * entry point.
 */
final class BusinessDiscoveryService
{
  /**
   * Allowed business-level connection roles.
   * 'owner'       – full control, not revokable except via transfer
  * 'coordinator' – manager role with every permission except ownership transfer
   * 'contributor' – work and sites operator with delegated work write access
   * 'viewer'      – read-only access to non-business-sensitive data
  * 'member'      – baseline member with non-sensitive read access and self-scoped work write
   */
  public const MEMBERSHIP_STATE_ACTIVE = 'active';
  public const MEMBERSHIP_STATE_CONSENTED = 'consented';
  public const MEMBERSHIP_STATE_PENDING = 'pending';
  public const MEMBERSHIP_STATE_SUSPENDED = 'suspended';
  public const MEMBERSHIP_STATE_REVOKED = 'revoked';
  public const MEMBERSHIP_STATE_REJECTED = 'rejected';
  public const MEMBERSHIP_STATE_EXPIRED = 'expired';

  public const ENCRYPTION_MODE_PERSONAL = 'personal';
  public const ENCRYPTION_MODE_BUSINESS = 'business';

  public const BUSINESS_SITE_OWNERSHIP_BUSINESS = 'business';
  public const BUSINESS_SITE_OWNERSHIP_LINKED = 'linked';
  public const BUSINESS_SITE_OWNERSHIP_SHARED = 'shared';

  public const BUSINESS_DEK_SEGMENT_CURRENT_PERIOD = 'current_period';
  public const BUSINESS_DEK_SEGMENT_ARCHIVE = 'archive';
  public const BUSINESS_DEK_VERSION_CURRENT = 'v1';

  private const DEFAULT_TIMEZONE = 'America/Edmonton';
  private const DEFAULT_CURRENCY = 'CAD';
  private const ACCESS_REQUEST_DEFAULT_SCOPES = ['sites.read', 'work.read', 'work.scope.self'];
  private const BULK_IMPORT_MAX_INPUT_EMAILS = 500;
  private const BULK_IMPORT_MAX_ACCEPTED_EMAILS = 200;
  private const BULK_IMPORT_PREPARE_TTL_SECONDS = 1200;
  private const BULK_IMPORT_CHALLENGE_TTL_SECONDS = 600;
  private const BULK_IMPORT_CHALLENGE_MAX_ATTEMPTS = 5;
  private const SCOPE_PRESET_VERSION = '1';
  private const SCOPE_POLICY_VERSION = '2026-06-18';

  /**
   * Is delegated work mode.
   */
  public static function isDelegatedWorkMode(string $mode): bool
  {
    $mode = strtolower(trim($mode));

    return $mode === self::ENCRYPTION_MODE_BUSINESS;
  }

  /**
   * Is business encryption mode.
   */
  public static function isBusinessEncryptionMode(string $mode): bool
  {
    return self::isDelegatedWorkMode($mode);
  }

  /**
   * True when a site record was created/managed in a business workspace.
   *
   * @param array<string, mixed> $siteHash
   */
  public static function isBusinessManagedSite(array $siteHash): bool
  {
    $ownershipScope = strtolower(trim(self::scalarString($siteHash['ownership_scope'] ?? '')));
    if ($ownershipScope === self::BUSINESS_SITE_OWNERSHIP_BUSINESS) {
      return true;
    }

    $businessManaged = strtolower(trim(self::scalarString($siteHash['business_managed'] ?? '')));

    return in_array($businessManaged, ['1', 'true', 'yes'], true);
  }

  /**
   * Resolve posted business ID.
   */
  public static function resolvePostedBusinessId(string ...$candidates): string
  {
    foreach ($candidates as $candidate) {
      $candidate = trim(InputSanitizer::sanitizeString($candidate));
      if ($candidate !== '') {
        return $candidate;
      }
    }

    return '';
  }

  /**
   * Allowed business-level connection roles.
   * 'owner'       – full control, not revokable except via transfer
    * 'coordinator' – manager role: settings + access + audit
    * 'contributor' – work and sites write access
    * 'viewer'      – read-only access to non-business-sensitive data
    * 'member'      – baseline member with read-only access plus self-scoped work write
   *
   * @var array<string, bool>
   */
  public const VALID_BUSINESS_ROLES = [
    'owner'       => true,
    'coordinator' => true,
    'contributor' => true,
    'viewer'      => true,
    'member'      => true,
  ];

  /**
   * Default scope sets for each named role preset.
   * Callers may override individual scopes after resolving a preset.
   *
   * @var array<string, array<string>>
   */
  public const ROLE_SCOPE_PRESETS = [
    'coordinator' => ['access.manage', 'audit.read', 'business.settings.read', 'business.settings.write', 'payperiod.read', 'payperiod.write', 'sites.read', 'sites.write', 'wage.read', 'wage.write', 'work.read', 'work.scope.business', 'work.write'],
    'contributor' => ['payperiod.read', 'sites.read', 'sites.write', 'wage.read', 'work.read', 'work.scope.business', 'work.write'],
    'viewer'      => ['payperiod.read', 'sites.read', 'wage.read', 'work.read'],
    'member'      => ['payperiod.read', 'sites.read', 'wage.read', 'work.read', 'work.scope.self', 'work.write'],
  ];

  /** @var array<string, bool> */
  private const ALLOWED_SCOPES = [
    'work.read' => true,
    'work.write' => true,
    'work.scope.self' => true,
    'work.scope.business' => true,
    'sites.read' => true,
    'sites.write' => true,
    'audit.read' => true,
    'payperiod.read' => true,
    'payperiod.write' => true,
    'wage.read' => true,
    'wage.write' => true,
    'business.settings.read' => true,
    'business.settings.write' => true,
    'access.manage' => true,
  ];

  /**
   * Valid status transitions for business connections.
   * Key = current status (empty string = no prior connection).
   * Value = set of allowed next statuses.
   *
   * @var array<string, array<string, bool>>
   */
  private const CONNECTION_TRANSITIONS = [
    ''                               => [
      self::MEMBERSHIP_STATE_ACTIVE => true,
      self::MEMBERSHIP_STATE_CONSENTED => true,
      self::MEMBERSHIP_STATE_PENDING => true,
    ],
    self::MEMBERSHIP_STATE_PENDING   => [
      self::MEMBERSHIP_STATE_CONSENTED => true,
      self::MEMBERSHIP_STATE_ACTIVE => true,
      self::MEMBERSHIP_STATE_REVOKED => true,
      self::MEMBERSHIP_STATE_REJECTED => true,
      self::MEMBERSHIP_STATE_EXPIRED => true,
      'withdrawn' => true,
    ],
    self::MEMBERSHIP_STATE_CONSENTED => [
      self::MEMBERSHIP_STATE_ACTIVE => true,
      self::MEMBERSHIP_STATE_REVOKED => true,
      'withdrawn' => true,
    ],
    self::MEMBERSHIP_STATE_ACTIVE    => [
      self::MEMBERSHIP_STATE_SUSPENDED => true,
      self::MEMBERSHIP_STATE_REVOKED => true,
      'withdrawn' => true,
    ],
    self::MEMBERSHIP_STATE_SUSPENDED => [
      self::MEMBERSHIP_STATE_ACTIVE => true,
      self::MEMBERSHIP_STATE_REVOKED => true,
    ],
    self::MEMBERSHIP_STATE_REVOKED   => [self::MEMBERSHIP_STATE_PENDING => true],
    self::MEMBERSHIP_STATE_REJECTED  => [self::MEMBERSHIP_STATE_PENDING => true],
    self::MEMBERSHIP_STATE_EXPIRED   => [self::MEMBERSHIP_STATE_PENDING => true],
    'withdrawn'                      => [self::MEMBERSHIP_STATE_PENDING => true],
  ];

  /**
   * Org-level event types that are security-significant enough to be mirrored
   * into the immutable TheLedger system audit chain (SystemAuditRepository).
   *
   * Classification:
   *   CRITICAL — governance changes, access grants, privilege mutations
   *   HIGH     — access removal, key management, configuration changes
   *
   * Events NOT listed here (invite.sent, access.requested, site.linked,
   * access.request.notification, business.consent.accepted) remain in business audit
   * only — they are operational/informational and not SOC2 evidence events.
   *
   * @var array<string, string> event_type => 'critical'|'high'
   */
  private const LEDGER_EVENTS = [
    // CRITICAL — CC6.1: new business / access grants / privilege changes
    'business.created'         => 'critical',
    'ownership.transferred'        => 'critical',
    'connection.revoked'         => 'critical',
    'connection.role_updated'    => 'critical',
    'access.request.approved'      => 'critical',
    'invite.accepted'              => 'critical',
    'invite.bulk_import_committed' => 'critical',
    // HIGH — CC6.1/CC6.2/CC6.7/CC9.1: access decisions, key mgmt, config
    'access.request.rejected'      => 'high',
    'invite.revoked'               => 'high',
    'connection.withdrawn'       => 'high',
    'settings.updated'             => 'high',
    'business.dek.wrap.bootstrap'       => 'high',
    'business.dek.wrap.bootstrap.bulk'  => 'high',
  ];

  /**
   * @param array<string, mixed> $options
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function createBusiness(string $ownerUUID, string $name, array $options = []): array
  {
    $normalizedName = trim(InputSanitizer::sanitizeString($name));
    if ('' === $normalizedName || strlen($normalizedName) < 2) {
      return $this->fail(Strings::i18n('BUSINESSES_NAME_MIN'));
    }

    $businessType = $this->normalizeBusinessType($options['business_type'] ?? 'shared');
    if ($businessType === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_TYPE_IS_INVALID'));
    }

    if ($businessType !== 'personal' && !$this->canAccessPremiumFeatures($ownerUUID)) {
      return $this->premiumSubscriptionRequired();
    }

    if (!$this->canCreateBusinessToday($ownerUUID)) {
      return $this->fail('Business creation limit reached. Try again tomorrow.');
    }

    $owner = UserRepository::getByUUID($ownerUUID);
    if (null === $owner) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_OWNER_NOT_FOUND'));
    }

    if ($businessType === 'personal') {
      $existingPersonalBusinessId = $this->findPersonalBusinessId($ownerUUID);
      if ($existingPersonalBusinessId !== '') {
        return $this->ok('Personal business already exists.', [
          'business_id' => $existingPersonalBusinessId,
          'name' => (string) (Database::hget(Keys::BUSINESS . ':' . $existingPersonalBusinessId, 'name') ?: $normalizedName),
        ]);
      }
    } else {
      $existingSharedBusinessId = $this->findOwnedSharedBusinessId($ownerUUID);
      if ($existingSharedBusinessId !== '') {
        return $this->fail(Strings::i18n('BUSINESSES_API_SHARED_ORG_EXISTS'));
      }
    }

    $businessId = $this->generateBusinessId($ownerUUID, $normalizedName);
    $businessKey = Keys::BUSINESS . ':' . $businessId;

    if (Database::exists($businessKey)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_ALREADY_EXISTS'), ['business_id' => $businessId]);
    }

    $timestamp = date('c');
    $requestPublicListing = (bool) ($options['request_public_listing'] ?? ($businessType === 'shared'));
    $nameReview = BusinessModerationService::evaluateCreationName(
      $ownerUUID,
      $normalizedName,
      $businessType,
      $requestPublicListing,
    );

    if ($nameReview['blocked']) {
      return $this->fail($nameReview['user_message']);
    }

    $businessName = $businessType === 'personal'
      ? $this->defaultPersonalBusinessName($owner, $normalizedName)
      : $nameReview['evaluation']['safe_display_name'];
    $defaultSettings = $this->defaultSettingsForOwner($owner);

    Database::hset($businessKey, [
      'business_id' => $businessId,
      'name' => $businessName,
      'owner_uuid' => $ownerUUID,
      'business_type' => $businessType,
      'created_at' => $timestamp,
      'status' => 'active',
    ] + $nameReview['fields']);

    Database::hset(Keys::BUSINESS_SETTINGS . ':' . $businessId, $defaultSettings + [
      'last_updated_at' => $timestamp,
      'last_updated_by' => $ownerUUID,
    ]);

    Database::sadd(Keys::BUSINESS_OWNER . ':' . $ownerUUID, $businessId);

    $this->setConnection($businessId, $ownerUUID, [
      'role' => 'owner',
      'status' => 'active',
      'scopes' => 'all',
      'invited_by' => $ownerUUID,
      'created_at' => $timestamp,
      'accepted_at' => $timestamp,
    ]);

    $this->appendAuditEvent($businessId, 'business.created', $ownerUUID, [
      'name' => $businessName,
      'owner_uuid' => $ownerUUID,
      'business_type' => $businessType,
    ]);

    BusinessModerationService::recordCreationAudit($businessId, $ownerUUID, $nameReview['evaluation']);
    BusinessSearchIndex::sync($businessId);
    $this->incrementBusinessCreateCount($ownerUUID);

    return $this->ok($nameReview['user_message'], [
      'business_id' => $businessId,
      'name' => $businessName,
      'business_type' => $businessType,
      'visibility' => (string) ($nameReview['fields']['visibility'] ?? 'private'),
      'moderation_status' => (string) ($nameReview['fields']['moderation_status'] ?? 'pending'),
      'moderation_message' => $nameReview['user_message'],
    ]);
  }

  /**
   * Ensure the user's personal business exists, creating it when absent.
   *
   * Personal businesses are created automatically on first use. Calling this
   * on a user that already has one is safe (idempotent).
   *
   * @param string $userUUID Authenticated user UUID.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function ensurePersonalBusiness(string $userUUID): array
  {
    $existingBusinessId = $this->findPersonalBusinessId($userUUID);
    if ($existingBusinessId !== '') {
      $business = Database::hgetall(Keys::BUSINESS . ':' . $existingBusinessId);

      return $this->ok('Personal business already exists.', [
        'business_id' => $existingBusinessId,
        'name' => (string) ($business['name'] ?? 'Personal Business'),
      ]);
    }

    $ownedSharedBusinessId = $this->findOwnedSharedBusinessId($userUUID);
    if ($ownedSharedBusinessId !== '') {
      $business = Database::hgetall(Keys::BUSINESS . ':' . $ownedSharedBusinessId);

      return $this->ok('Owned shared business is used for profile context.', [
        'business_id' => $ownedSharedBusinessId,
        'name' => (string) ($business['name'] ?? 'Business'),
      ]);
    }

    $owner = UserRepository::getByUUID($userUUID);
    if (null === $owner) {
      return $this->fail(Strings::i18n('BUSINESSES_API_CANNOT_CREATE_PERSONAL_BUSINESS_FOR_UNKNOWN_USER'));
    }

    $defaultName = $this->defaultPersonalBusinessName($owner);

    return $this->createBusiness($userUUID, $defaultName, [
      'business_type' => 'personal',
    ]);
  }

  /**
   * Resolve whether profile pay-period settings are owned by a shared business.
   *
   * When the user owns an active shared business, profile settings APIs prefer
   * that business over a personal-business record.
   *
   * @return array{business_id: string, name: string, payroll_href: string}|null
   */
  public function resolveProfilePayPeriodManagedByBusiness(string $userUUID): ?array
  {
    $personalContext = $this->ensurePersonalBusiness($userUUID);
    if ($personalContext['success'] !== true) {
      return null;
    }

    $businessId = is_scalar($personalContext['data']['business_id'] ?? null)
      ? trim((string) $personalContext['data']['business_id'])
      : '';
    if ($businessId === '') {
      return null;
    }

    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ($business === []) {
      return null;
    }

    $businessType = (string) ($business['business_type'] ?? 'shared');
    $ownerUUID = (string) ($business['owner_uuid'] ?? '');
    if ($businessType !== 'shared' || $ownerUUID !== $userUUID) {
      return null;
    }

    $status = strtolower((string) ($business['status'] ?? 'active'));
    if (in_array($status, ['archived', 'deleted', 'disabled'], true)) {
      return null;
    }

    return [
      'business_id' => $businessId,
      'name' => (string) ($business['name'] ?? Strings::i18n('BUSINESSES_NOUN_BUSINESS')),
      'payroll_href' => '/business/payroll/',
    ];
  }

  /**
   * List all businesses the user belongs to, including personal and shared.
   *
   * Ensures the personal business exists, resolves unread notification counts
   * per business, and sorts results with the personal business first.
   *
   * @param string $userUUID Authenticated user UUID.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function listForUser(string $userUUID): array
  {
    $this->ensurePersonalBusiness($userUUID);

    $notificationSummary = (new BusinessNotificationService())->summarizeUnreadForUser($userUUID);
    $unreadByOrg = $notificationSummary['by_org'];

    $businessIds = $this->connectionBusinessIdsForUser($userUUID);

    $businesses = [];
    foreach ($businessIds as $businessId) {
      $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
      if ([] === $business) {
        continue;
      }

      $connection = $this->connection($businessId, $userUUID);
      $connectionStatus = (string) ($connection['status'] ?? '');
      $businessType = (string) ($business['business_type'] ?? 'shared');
      $isBusinessOwner = (string) ($business['owner_uuid'] ?? '') === $userUUID;
      $isCurrentConnection = $connectionStatus === 'active' || $connectionStatus === 'pending';

      if (!$this->isSelfBusiness($business, $userUUID) && !$isBusinessOwner && !$isCurrentConnection) {
        continue;
      }

      $owner = UserRepository::getByUUID((string) ($business['owner_uuid'] ?? ''));
      $settings = Database::hgetall(Keys::BUSINESS_SETTINGS . ':' . $businessId);

      $businesses[] = [
        'business_id' => $businessId,
        'name' => (string) ($business['name'] ?? ''),
        'owner_uuid' => (string) ($business['owner_uuid'] ?? ''),
        'owner_name' => $owner instanceof User ? $owner->full_name : '',
        'owner_email' => $owner instanceof User ? $owner->email : '',
        'business_type' => $businessType,
        'status' => (string) ($business['status'] ?? 'active'),
        'role' => (string) ($connection['role'] ?? ''),
        'connection_status' => $connectionStatus,
        'scopes' => $this->scopeList((string) ($connection['scopes'] ?? '')),
        'joined_at' => (string) ($connection['accepted_at'] ?? $connection['created_at'] ?? ''),
        'legal_name' => (string) ($settings['legal_name'] ?? ''),
        'industry' => (string) ($settings['industry'] ?? ''),
        'registration_number' => (string) ($settings['registration_number'] ?? ''),
        'tax_id' => (string) ($settings['tax_id'] ?? ''),
        'employee_count' => (string) ($settings['employee_count'] ?? ''),
        'founded_year' => (string) ($settings['founded_year'] ?? ''),
        'contact_email' => (string) ($settings['contact_email'] ?? ''),
        'contact_phone' => (string) ($settings['contact_phone'] ?? ''),
        'website' => (string) ($settings['website'] ?? ''),
        'indigenous_owned' => (string) ($settings['indigenous_owned'] ?? '0'),
        'resident_on_reserve' => (string) ($settings['resident_on_reserve'] ?? '0'),
        'reserve_name' => (string) ($settings['reserve_name'] ?? ''),
        'address_line1' => (string) ($settings['address_line1'] ?? ''),
        'address_line2' => (string) ($settings['address_line2'] ?? ''),
        'address_city' => (string) ($settings['address_city'] ?? ''),
        'address_region' => (string) ($settings['address_region'] ?? ''),
        'address_postal' => (string) ($settings['address_postal'] ?? ''),
        'address_country' => (string) ($settings['address_country'] ?? ''),
        'support_hours' => (string) ($settings['support_hours'] ?? ''),
        'notification_unread_count' => (string) ((int) ($unreadByOrg[$businessId] ?? 0)),
        'has_unread_notifications' => ((int) ($unreadByOrg[$businessId] ?? 0)) > 0 ? '1' : '0',
      ];
    }

    usort($businesses, static function (array $a, array $b): int {
      $aType = (string) $a['business_type'];
      $bType = (string) $b['business_type'];
      if ($aType !== $bType) {
        return $aType === 'personal' ? -1 : 1;
      }

      return strcasecmp((string) $a['name'], (string) $b['name']);
    });

    return $this->ok('Businesses retrieved.', [
      'businesses' => $businesses,
      'notification_total_unread' => (int) $notificationSummary['total_unread'],
      'notification_unread_by_org' => $unreadByOrg,
    ]);
  }

  /**
   * Mark all unread notifications for an business as read.
   *
   * Requires active or pending membership (or ownership). Returns updated
   * total unread counts across all businesses.
   *
   * @param string $actorUUID Authenticated user UUID.
   * @param string $businessId     Business ID.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function markBusinessNotificationsRead(string $actorUUID, string $businessId): array
  {
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    if ($businessId === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND'));
    }

    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ([] === $business) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND'));
    }

    $connection = $this->connection($businessId, $actorUUID);
    $isOwner = (string) ($business['owner_uuid'] ?? '') === $actorUUID;
    $status = strtolower(trim((string) ($connection['status'] ?? '')));
    if (!$isOwner && !in_array($status, [self::MEMBERSHIP_STATE_ACTIVE, self::MEMBERSHIP_STATE_PENDING], true)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_UPDATE_BUSINESS_NOTIFICATI'));
    }

    $summary = (new BusinessNotificationService())->markBusinessRead($actorUUID, $businessId);

    return $this->ok('Business notifications marked read.', [
      'business_id' => $businessId,
      'total_unread' => (int) $summary['total_unread'],
      'unread_by_org' => $summary['by_org'],
    ]);
  }

  /** @param array<int, string> $scopes
   *  @return array{success: bool, message: string, data: array<string, mixed>} */
  public function sendInvite(string $actorUUID, string $businessId, string $inviteeEmail, array $scopes, ?string $batchCode = null): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_INVITE_MANAGERS_ONLY'));
    }

    $email = InputSanitizer::sanitizeEmail($inviteeEmail);
    if ('' === $email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_INVITE_EMAIL_REQUIRED'));
    }

    $domainGate = $this->ensureContactDomainPolicyAllowsEmail($businessId, $email, 'invite target email');
    if (!$domainGate['success']) {
      return $domainGate;
    }

    $normalizedScopes = $this->normalizeScopes($scopes);
    if ([] === $normalizedScopes) {
      return $this->fail(Strings::i18n('BUSINESSES_API_SCOPE_REQUIRED'));
    }

    if ($this->hasReachedMemberLimit($businessId)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_HAS_REACHED_THE_MAXIMUM_MEMBER_LIMIT_OF_1_000_P'));
    }

    $normalizedBatchCode = $this->normalizeBatchCode($batchCode);
    $inviteId = 'INV' . substr(hash('sha256', $businessId . $email . bin2hex(random_bytes(16))), 0, 20);
    $token = bin2hex(random_bytes(24));
    $timestamp = date('c');
    $expiresAt = date('c', time() + 7 * 24 * 3600);

    $inviteUUID = UserRepository::getUUIDFromEmail($email);
    if ($inviteUUID !== '') {
      $existingConnection = $this->connection($businessId, $inviteUUID);
      $existingStatus = (string) ($existingConnection['status'] ?? '');
      if (in_array($existingStatus, [self::MEMBERSHIP_STATE_ACTIVE, self::MEMBERSHIP_STATE_PENDING, self::MEMBERSHIP_STATE_CONSENTED], true)) {
        return $this->fail(Strings::i18n('BUSINESSES_API_YOU_ALREADY_HAVE_ACTIVE_ACCESS_TO_THIS_BUSINESS'));
      }
    }

    Database::hset(Keys::BUSINESS_INVITE . ':' . $inviteId, [
      'invite_id' => $inviteId,
      'business_id' => $businessId,
      'invited_by' => $actorUUID,
      'invitee_email' => $email,
      'invitee_uuid' => $inviteUUID,
      'invite_token' => $token,
      'scopes' => implode(',', $normalizedScopes),
      'status' => 'pending',
      'batch_code' => $normalizedBatchCode,
      'created_at' => $timestamp,
      'expires_at' => $expiresAt,
    ]);

    Database::set(Keys::BUSINESS_INVITE_TOKEN . ':' . $token, $inviteId, 7 * 24 * 3600);
    Database::sadd(Keys::BUSINESS_INVITE_EMAIL . ':' . $email, $inviteId);
    Database::sadd(Keys::BUSINESS_INVITE_BUSINESS . ':' . $businessId, $inviteId);

    if ($inviteUUID !== '') {
      $this->setConnection($businessId, $inviteUUID, [
        'role' => 'member',
        'status' => self::MEMBERSHIP_STATE_PENDING,
        'scopes' => implode(',', $normalizedScopes),
        'invited_by' => $actorUUID,
        'created_at' => $timestamp,
      ]);
    }

    BusinessDashboardMetrics::recordPendingInviteCreated($businessId);

    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    $inviter = UserRepository::getByUUID($actorUUID);
    $sent = false;
    if (defined('PHPUNIT_COMPOSER_INSTALL') || !defined('PC_NAME')) {
      $sent = true;
    } else {
      try {
        $sent = EmailGarum::sendBusinessInvite(
          inviteToken: $token,
          inviteeEmail: $email,
          businessName: (string) ($business['name'] ?? 'Business'),
          inviterName: (string) ($inviter?->full_name ?: $inviter?->email ?: 'PayCal User'),
          scopes: $normalizedScopes,
          batchCode: $normalizedBatchCode
        );
      } catch (\Throwable $_error) {
        $sent = false;
      }
    }

    $this->appendAuditEvent($businessId, 'invite.sent', $actorUUID, [
      'invite_id' => $inviteId,
      'invitee_email' => $email,
      'batch_code' => $normalizedBatchCode,
      'scopes' => implode(',', $normalizedScopes),
      'email_dispatch' => $sent ? 'sent' : 'failed',
    ]);

    return $this->ok('Business invite created.', [
      'invite_id' => $inviteId,
      'invite_token' => $token,
      'batch_code' => $normalizedBatchCode,
      'expires_at' => $expiresAt,
      'invitee_uuid' => $inviteUUID,
      'email_dispatch' => $sent ? 'sent' : 'failed',
    ]);
  }

  /** @param array<int, string> $scopes
   *  @return array{success: bool, message: string, data: array<string, mixed>} */
  public function prepareBulkInviteImport(string $actorUUID, string $businessId, string $rawEmails, array $scopes): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_ONLY_AUTHORIZED_BUSINESS_MANAGERS_CAN_IMPORT_INVITES'));
    }

    $authority = $this->resolveBulkImportAuthority($actorUUID, $businessId);
    if (!$authority['success']) {
      return $authority;
    }

    $normalizedScopes = $this->normalizeScopes($scopes);
    if ([] === $normalizedScopes) {
      return $this->fail(Strings::i18n('BUSINESSES_API_SCOPE_REQUIRED'));
    }

    $parsed = $this->parseBulkInviteEmails($rawEmails);
    if ($parsed['input_count'] > self::BULK_IMPORT_MAX_INPUT_EMAILS) {
      return $this->fail(Strings::i18n('BUSINESSES_API_TOO_MANY_EMAIL_ENTRIES_MAXIMUM_INPUT_IS_500_ADDRESSES_PE'));
    }

    $authorityData = $authority['data'];
    $authorityDomain = $this->arrayStringValue($authorityData, 'authority_domain');
    $businessIdClean = trim(InputSanitizer::sanitizeString($businessId));
    $accepted = [];
    $wrongDomain = [];
    $alreadyMember = [];
    $alreadyInvited = [];

    foreach ($parsed['valid'] as $email) {
      $emailDomain = $this->emailDomain($email);
      if ($emailDomain === '' || !hash_equals($authorityDomain, $emailDomain)) {
        $wrongDomain[] = $email;
        continue;
      }

      $memberUUID = UserRepository::getUUIDFromEmail($email);
      if ($memberUUID !== '') {
        $connection = $this->connection($businessIdClean, $memberUUID);
        $status = (string) ($connection['status'] ?? '');
        if ($status === 'active' || $status === 'pending') {
          $alreadyMember[] = $email;
          continue;
        }
      }

      $hasPendingInvite = false;
      foreach (Database::smembers(Keys::BUSINESS_INVITE_EMAIL . ':' . $email) as $inviteId) {
        $invite = Database::hgetall(Keys::BUSINESS_INVITE . ':' . $inviteId);
        if (
          [] !== $invite
          && (string) ($invite['business_id'] ?? '') === $businessIdClean
          && (string) ($invite['status'] ?? '') === 'pending'
        ) {
          $hasPendingInvite = true;
          break;
        }
      }

      if ($hasPendingInvite) {
        $alreadyInvited[] = $email;
        continue;
      }

      if (count($accepted) < self::BULK_IMPORT_MAX_ACCEPTED_EMAILS) {
        $accepted[] = $email;
      }
    }

    $importId = 'OIIMP' . substr(hash('sha256', $businessIdClean . '|' . $actorUUID . '|' . bin2hex(random_bytes(16))), 0, 20);
    $scopeCsv = implode(',', $normalizedScopes);
    $acceptedJson = json_encode($accepted, JSON_UNESCAPED_SLASHES);
    if (!is_string($acceptedJson)) {
      $acceptedJson = '[]';
    }
    $summary = [
      'input_count' => $parsed['input_count'],
      'accepted_count' => count($accepted),
      'invalid_count' => count($parsed['invalid']),
      'duplicate_count' => count($parsed['duplicates']),
      'wrong_domain_count' => count($wrongDomain),
      'already_member_count' => count($alreadyMember),
      'already_invited_count' => count($alreadyInvited),
      'truncated_count' => max(0, count($parsed['valid']) - self::BULK_IMPORT_MAX_ACCEPTED_EMAILS),
    ];
    $summaryJson = json_encode($summary, JSON_UNESCAPED_SLASHES);
    if (!is_string($summaryJson)) {
      $summaryJson = '{}';
    }

    $prepareKey = Keys::businessInviteImportPrepare($importId);
    Database::hsetex($prepareKey, [
      'import_id' => $importId,
      'business_id' => $businessIdClean,
      'actor_uuid' => $actorUUID,
      'authority_email' => $this->arrayStringValue($authorityData, 'authority_email'),
      'authority_domain' => $authorityDomain,
      'scopes' => $scopeCsv,
      'accepted_emails' => $acceptedJson,
      'summary' => $summaryJson,
      'created_at' => date('c'),
      'status' => 'prepared',
    ], self::BULK_IMPORT_PREPARE_TTL_SECONDS);

    $this->appendAuditEvent($businessIdClean, 'invite.bulk_prepare', $actorUUID, [
      'import_id' => $importId,
      'accepted_count' => (string) count($accepted),
      'input_count' => (string) $parsed['input_count'],
      'authority_domain' => $authorityDomain,
    ]);

    return $this->ok('Bulk invite import prepared.', [
      'import_id' => $importId,
      'authority_domain' => $authorityDomain,
      'authority_email_hint' => $this->maskEmail($this->arrayStringValue($authorityData, 'authority_email')),
      'summary' => $summary,
      'accepted_emails' => $accepted,
      'invalid_emails' => $parsed['invalid'],
      'duplicate_emails' => $parsed['duplicates'],
      'wrong_domain_emails' => $wrongDomain,
      'already_member_emails' => $alreadyMember,
      'already_invited_emails' => $alreadyInvited,
      'scopes' => $normalizedScopes,
    ]);
  }

  /**
   * Start a bulk invite import verification challenge.
   *
   * Generates a short-lived one-time code and emails it to the import authority.
   * The challenge ID must be supplied when calling verifyBulkInviteImportChallenge().
   *
   * @param string $actorUUID Authenticated actor UUID.
   * @param string $businessId     Business ID.
   * @param string $importId  Import session ID from the prepare step.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function startBulkInviteImportChallenge(string $actorUUID, string $businessId, string $importId): array
  {
    $prepare = $this->loadBulkImportPrepare($actorUUID, $businessId, $importId);
    if (!$prepare['success']) {
      return $prepare;
    }

    $prepareData = is_array($prepare['data']['prepare'] ?? null) ? $prepare['data']['prepare'] : [];
    $acceptedEmails = $this->decodeStringArray($this->arrayStringValue($prepareData, 'accepted_emails', '[]'));
    if ([] === $acceptedEmails) {
      return $this->fail(Strings::i18n('BUSINESSES_API_NO_ELIGIBLE_EMAILS_FOUND_FOR_IMPORT'));
    }

    $challengeId = 'OICH' . substr(hash('sha256', $importId . '|' . bin2hex(random_bytes(12))), 0, 20);
    $code = Security::generateVerificationCode(6);
    $codeHash = hash('sha256', $code);
    $authorityEmail = $this->arrayStringValue($prepareData, 'authority_email');
    $challengeKey = Keys::businessInviteImportChallenge($challengeId);

    Database::hsetex($challengeKey, [
      'challenge_id' => $challengeId,
      'import_id' => $importId,
      'business_id' => trim(InputSanitizer::sanitizeString($businessId)),
      'actor_uuid' => $actorUUID,
      'code_hash' => $codeHash,
      'verify_attempts' => '0',
      'verified' => '0',
      'consumed' => '0',
      'created_at' => date('c'),
      'expires_at' => (string) (time() + self::BULK_IMPORT_CHALLENGE_TTL_SECONDS),
    ], self::BULK_IMPORT_CHALLENGE_TTL_SECONDS + 60);

    $sent = false;
    if (defined('PHPUNIT_COMPOSER_INSTALL') || !defined('PC_NAME')) {
      $sent = true;
    } else {
      try {
        $status = EmailGarum::emailVerificationCode($code, $authorityEmail);
        $sent = str_starts_with($status, 'Email Sent Successfully.');
      } catch (\Throwable $_error) {
        $sent = false;
      }
    }

    if (!$sent) {
      Database::unlink($challengeKey);
      return $this->fail(Strings::i18n('BUSINESSES_API_UNABLE_TO_SEND_VERIFICATION_CODE_RIGHT_NOW'));
    }

    $prepareKey = Keys::businessInviteImportPrepare($importId);
    Database::hset($prepareKey, [
      'status' => 'challenge_sent',
      'challenge_id' => $challengeId,
      'challenge_sent_at' => date('c'),
    ]);

    $this->appendAuditEvent(trim(InputSanitizer::sanitizeString($businessId)), 'invite.bulk_challenge_started', $actorUUID, [
      'import_id' => $importId,
      'challenge_id' => $challengeId,
      'authority_email' => $authorityEmail,
    ]);

    $responseData = [
      'import_id' => $importId,
      'challenge_id' => $challengeId,
      'authority_email_hint' => $this->maskEmail($authorityEmail),
      'expires_in_seconds' => self::BULK_IMPORT_CHALLENGE_TTL_SECONDS,
    ];

    if (defined('PHPUNIT_COMPOSER_INSTALL') || !defined('PC_NAME')) {
      $responseData['test_code'] = $code;
    }

    return $this->ok('Verification code sent.', $responseData);
  }

  /**
   * Verify the code submitted for a bulk invite import challenge.
   *
   * Enforces attempt limits and expiry. On success marks the challenge as
   * verified so commitBulkInviteImport() can proceed.
   *
   * @param string $actorUUID   Authenticated actor UUID.
   * @param string $businessId       Business ID.
   * @param string $importId    Import session ID.
   * @param string $challengeId Challenge ID returned by startBulkInviteImportChallenge().
   * @param string $code        6-character uppercase verification code.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function verifyBulkInviteImportChallenge(string $actorUUID, string $businessId, string $importId, string $challengeId, string $code): array
  {
    $prepare = $this->loadBulkImportPrepare($actorUUID, $businessId, $importId);
    if (!$prepare['success']) {
      return $prepare;
    }

    $challenge = $this->loadBulkImportChallenge($actorUUID, $businessId, $importId, $challengeId);
    if (!$challenge['success']) {
      return $challenge;
    }

    $challengeData = is_array($challenge['data']['challenge'] ?? null) ? $challenge['data']['challenge'] : [];
    $attempts = $this->arrayIntValue($challengeData, 'verify_attempts', 0);
    if ($attempts >= self::BULK_IMPORT_CHALLENGE_MAX_ATTEMPTS) {
      return $this->fail(Strings::i18n('BUSINESSES_API_TOO_MANY_FAILED_VERIFICATION_ATTEMPTS'));
    }

    $expiresAt = $this->arrayIntValue($challengeData, 'expires_at', 0);
    if ($expiresAt <= 0 || time() > $expiresAt) {
      return $this->fail(Strings::i18n('BUSINESSES_API_VERIFICATION_CODE_HAS_EXPIRED'));
    }

    $normalizedCode = strtoupper(trim(InputSanitizer::sanitizeString($code)));
    if (strlen($normalizedCode) !== 6) {
      return $this->fail(Strings::i18n('BUSINESSES_API_VERIFICATION_CODE_MUST_BE_EXACTLY_6_CHARACTERS'));
    }

    $providedHash = hash('sha256', $normalizedCode);
    $storedHash = $this->arrayStringValue($challengeData, 'code_hash');
    if (!hash_equals($storedHash, $providedHash)) {
      Database::hset(Keys::businessInviteImportChallenge($challengeId), [
        'verify_attempts' => (string) ($attempts + 1),
      ]);

      return $this->fail(Strings::i18n('BUSINESSES_API_INVALID_VERIFICATION_CODE'));
    }

    Database::hset(Keys::businessInviteImportChallenge($challengeId), [
      'verified' => '1',
      'verified_at' => date('c'),
    ]);

    Database::hset(Keys::businessInviteImportPrepare($importId), [
      'status' => 'challenge_verified',
      'challenge_id' => $challengeId,
      'challenge_verified_at' => date('c'),
    ]);

    return $this->ok('Verification code accepted.', [
      'import_id' => $importId,
      'challenge_id' => $challengeId,
      'verified' => true,
    ]);
  }

  /**
   * Commit a verified bulk invite import, sending all queued invitations.
   *
   * Requires a verified, unconsumed challenge. Marks the challenge consumed
   * after dispatch to prevent replay.
   *
   * @param string $actorUUID   Authenticated actor UUID.
   * @param string $businessId       Business ID.
   * @param string $importId    Import session ID.
   * @param string $challengeId Verified challenge ID.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function commitBulkInviteImport(string $actorUUID, string $businessId, string $importId, string $challengeId): array
  {
    $prepare = $this->loadBulkImportPrepare($actorUUID, $businessId, $importId);
    if (!$prepare['success']) {
      return $prepare;
    }

    $challenge = $this->loadBulkImportChallenge($actorUUID, $businessId, $importId, $challengeId);
    if (!$challenge['success']) {
      return $challenge;
    }

    $challengeData = is_array($challenge['data']['challenge'] ?? null) ? $challenge['data']['challenge'] : [];
    if ($this->arrayStringValue($challengeData, 'verified', '0') !== '1') {
      return $this->fail(Strings::i18n('BUSINESSES_API_VERIFICATION_IS_REQUIRED_BEFORE_IMPORTING'));
    }

    if ($this->arrayStringValue($challengeData, 'consumed', '0') === '1') {
      return $this->fail(Strings::i18n('BUSINESSES_API_THIS_IMPORT_CHALLENGE_HAS_ALREADY_BEEN_USED'));
    }

    $prepareData = is_array($prepare['data']['prepare'] ?? null) ? $prepare['data']['prepare'] : [];
    $acceptedEmails = $this->decodeStringArray($this->arrayStringValue($prepareData, 'accepted_emails', '[]'));
    $scopeCsv = $this->arrayStringValue($prepareData, 'scopes');
    $scopes = $this->scopeList($scopeCsv);

    if ([] === $acceptedEmails) {
      return $this->fail(Strings::i18n('BUSINESSES_API_NO_ELIGIBLE_EMAILS_ARE_AVAILABLE_TO_IMPORT'));
    }

    if ([] === $scopes) {
      return $this->fail(Strings::i18n('BUSINESSES_API_IMPORT_SCOPES_ARE_MISSING_PLEASE_PREPARE_AGAIN'));
    }

    $batchCode = $this->generateInviteBatchCode();
    $results = [];
    $successCount = 0;
    $failureCount = 0;
    foreach ($acceptedEmails as $email) {
      $inviteResult = $this->sendInvite($actorUUID, $businessId, $email, $scopes, $batchCode);
      if ($inviteResult['success']) {
        $successCount += 1;
        $results[] = [
          'email' => $email,
          'status' => 'invited',
          'invite_id' => $this->arrayStringValue($inviteResult['data'], 'invite_id'),
          'batch_code' => $this->arrayStringValue($inviteResult['data'], 'batch_code', $batchCode),
        ];
      } else {
        $failureCount += 1;
        $results[] = [
          'email' => $email,
          'status' => 'failed',
          'reason' => $inviteResult['message'],
          'batch_code' => $batchCode,
        ];
      }
    }

    Database::hset(Keys::businessInviteImportChallenge($challengeId), [
      'consumed' => '1',
      'consumed_at' => date('c'),
    ]);

    Database::hset(Keys::businessInviteImportPrepare($importId), [
      'status' => 'committed',
      'committed_at' => date('c'),
      'success_count' => (string) $successCount,
      'failure_count' => (string) $failureCount,
    ]);

    $this->appendAuditEvent(trim(InputSanitizer::sanitizeString($businessId)), 'invite.bulk_import_committed', $actorUUID, [
      'import_id' => $importId,
      'challenge_id' => $challengeId,
      'batch_code' => $batchCode,
      'success_count' => (string) $successCount,
      'failure_count' => (string) $failureCount,
    ]);

    return $this->ok('Bulk invite import completed.', [
      'import_id' => $importId,
      'challenge_id' => $challengeId,
      'batch_code' => $batchCode,
      'success_count' => $successCount,
      'failure_count' => $failureCount,
      'results' => $results,
    ]);
  }

  /**
   * Handles normalizeBatchCode operation.
   */
  private function normalizeBatchCode(?string $batchCode): string
  {
    $candidate = strtoupper(trim((string) ($batchCode ?? '')));
    if (preg_match('/^[A-Z0-9]{3}$/', $candidate) === 1) {
      return $candidate;
    }

    return $this->generateInviteBatchCode();
  }

  /**
   * Handles generateInviteBatchCode operation.
   */
  private function generateInviteBatchCode(): string
  {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';

    for ($i = 0; $i < 3; $i++) {
      $index = random_int(0, strlen($alphabet) - 1);
      $code .= $alphabet[$index];
    }

    return $code;
  }

  /**
   * Request membership access to an business identified by its owner's email.
   *
   * Looks up the business owned by the supplied email and creates a pending
   * access request record, notifying the owner via email.
   *
   * @param string $requesterUUID UUID of the user requesting access.
   * @param string $ownerEmail    Email address of the target business's owner.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function requestAccessByOwnerEmail(string $requesterUUID, string $ownerEmail): array
  {
    $normalizedOwnerEmail = InputSanitizer::sanitizeEmail($ownerEmail);
    if ('' === $normalizedOwnerEmail || !filter_var($normalizedOwnerEmail, FILTER_VALIDATE_EMAIL)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_A_VALID_BUSINESS_OWNER_EMAIL_IS_REQUIRED'));
    }

    $ownerUUID = UserRepository::getUUIDFromEmail($normalizedOwnerEmail);
    if ('' === $ownerUUID) {
      return $this->fail(Strings::i18n('BUSINESSES_API_NO_BUSINESS_OWNER_ACCOUNT_WAS_FOUND_FOR_THAT_EMAIL'));
    }

    if ($ownerUUID === $requesterUUID) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_CANNOT_REQUEST_ACCESS_TO_YOUR_OWN_BUSINESS'));
    }

    $businessId = $this->findPreferredBusinessIdForOwner($ownerUUID);
    if ('' === $businessId) {
      return $this->fail(Strings::i18n('BUSINESSES_API_NO_ACTIVE_BUSINESS_WAS_FOUND_FOR_THAT_OWNER'));
    }

    $existingConnection = $this->connection($businessId, $requesterUUID);
    if ([] !== $existingConnection && (string) ($existingConnection['status'] ?? '') === 'active') {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_ALREADY_HAVE_ACTIVE_ACCESS_TO_THIS_BUSINESS'));
    }

    $activeKey = $this->accessRequestActiveKey($businessId, $requesterUUID);
    $activeRequestId = (string) Database::get($activeKey);
    if ($activeRequestId !== '') {
      $existingRequest = Database::hgetall(Keys::BUSINESS_ACCESS_REQUEST . ':' . $activeRequestId);
      if ([] !== $existingRequest && (string) ($existingRequest['status'] ?? '') === 'pending') {
        return $this->ok('Your request is already waiting for this business.', [
          'request_id' => $activeRequestId,
          'business_id' => $businessId,
          'status' => 'pending',
        ]);
      }
    }

    $requester = UserRepository::getByUUID($requesterUUID);
    $requesterContactEmail = InputSanitizer::sanitizeEmail((string) ($requester->email ?? ''));
    $requesterDisplayName = trim((string) ($requester->full_name ?? ''));
    if ($requesterDisplayName === '') {
      $requesterDisplayName = $requesterContactEmail !== '' ? $requesterContactEmail : 'PayCal user';
    }
    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    $businessName = trim((string) ($business['name'] ?? 'Business'));
    if ($businessName === '') {
      $businessName = 'Business';
    }

    if ($requesterContactEmail !== '') {
      $domainGate = $this->ensureContactDomainPolicyAllowsEmail($businessId, $requesterContactEmail, 'access requester email');
      if (!$domainGate['success']) {
        return $domainGate;
      }
    }

    // Block if a pending invite already exists for this user in this org (invite takes priority over request)
    if ($requesterContactEmail !== '') {
      foreach (Database::smembers(Keys::BUSINESS_INVITE_EMAIL . ':' . $requesterContactEmail) as $existingInviteId) {
        $existingInvite = Database::hgetall(Keys::BUSINESS_INVITE . ':' . $existingInviteId);
        if (
          [] !== $existingInvite &&
          (string) ($existingInvite['business_id'] ?? '') === $businessId &&
          (string) ($existingInvite['status'] ?? '') === 'pending'
        ) {
          return $this->fail(Strings::i18n('BUSINESSES_API_YOU_ALREADY_HAVE_A_PENDING_INVITE_FOR_THIS_BUSINESS_CHEC'));
        }
      }
    }

    $requestId = 'OAR' . substr(hash('sha256', $businessId . '|' . $requesterUUID . '|' . bin2hex(random_bytes(16))), 0, 20);
    $createdAt = date('c');
    $expiresAt = date('c', time() + 14 * 24 * 3600);

    Database::hset(Keys::BUSINESS_ACCESS_REQUEST . ':' . $requestId, [
      'request_id' => $requestId,
      'business_id' => $businessId,
      'requester_uuid' => $requesterUUID,
      'owner_uuid' => $ownerUUID,
      'owner_email' => $normalizedOwnerEmail,
      'requester_contact_email' => $requesterContactEmail,
      'status' => 'pending',
      'created_at' => $createdAt,
      'expires_at' => $expiresAt,
    ]);

    Database::sadd(Keys::BUSINESS_ACCESS_REQUEST_BUSINESS . ':' . $businessId, $requestId);
    Database::sadd(Keys::BUSINESS_ACCESS_REQUEST_REQUESTER . ':' . $requesterUUID, $requestId);
    Database::set($activeKey, $requestId, 14 * 24 * 3600);

    $this->setConnection($businessId, $requesterUUID, [
      'role' => 'member',
      'status' => self::MEMBERSHIP_STATE_PENDING,
      'scopes' => implode(',', $this->normalizeScopes(self::ACCESS_REQUEST_DEFAULT_SCOPES)),
      'requested_by' => $requesterUUID,
      'created_at' => $createdAt,
    ]);

    BusinessDashboardMetrics::recordPendingRequestCreated($businessId);

    $this->appendAuditEvent($businessId, 'access.requested', $requesterUUID, [
      'request_id' => $requestId,
      'requester_uuid' => $requesterUUID,
      'requester_contact_email' => $requesterContactEmail,
      'owner_email' => $normalizedOwnerEmail,
    ]);
    $this->incrementAccessRequestTelemetry('requested');

    $emailSent = false;
    if (defined('PHPUNIT_COMPOSER_INSTALL') || !defined('PC_NAME')) {
      $emailSent = true;
    } else {
      try {
        $emailSent = EmailGarum::sendBusinessAccessRequest(
          ownerEmail: $normalizedOwnerEmail,
          businessName: $businessName,
          requesterName: $requesterDisplayName,
          requesterEmail: $requesterContactEmail,
          requestId: $requestId
        );
      } catch (\Throwable $_error) {
        $emailSent = false;
      }
    }

    $this->appendAuditEvent($businessId, 'access.request.notification', $requesterUUID, [
      'request_id' => $requestId,
      'owner_email' => $normalizedOwnerEmail,
      'email_dispatch' => $emailSent ? 'sent' : 'failed',
    ]);

    return $this->ok('Request sent. No protected work data is shared by this request.', [
      'request_id' => $requestId,
      'business_id' => $businessId,
      'owner_uuid' => $ownerUUID,
      'status' => 'pending',
      'expires_at' => $expiresAt,
      'email_dispatch' => $emailSent ? 'sent' : 'failed',
    ]);
  }

  /**
   * List active (pending) invitations for an business.
   *
   * Returns only invites with status 'pending'. Requires manage-access
   * or manage-business permission.
   *
   * @param string $actorUUID Authenticated actor UUID.
   * @param string $businessId     Business ID.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function listInvites(string $actorUUID, string $businessId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_VIEW_INVITES'));
    }

    $inviteIds = Database::smembers(Keys::BUSINESS_INVITE_BUSINESS . ':' . $businessId);
    sort($inviteIds, SORT_STRING);

    $invites = [];
    foreach ($inviteIds as $inviteId) {
      $invite = Database::hgetall(Keys::BUSINESS_INVITE . ':' . $inviteId);
      if ([] === $invite) {
        continue;
      }

      // This endpoint powers the "Sent / Pending Invites" UI list.
      // Keep revoked/accepted/expired history in storage, but exclude it from this active queue.
      if ((string) ($invite['status'] ?? 'pending') !== 'pending') {
        continue;
      }

      $invites[] = [
        'invite_id' => (string) ($invite['invite_id'] ?? $inviteId),
        'invitee_email' => (string) ($invite['invitee_email'] ?? ''),
        'invitee_uuid' => (string) ($invite['invitee_uuid'] ?? ''),
        'status' => (string) ($invite['status'] ?? 'pending'),
        'scopes' => $this->scopeList((string) ($invite['scopes'] ?? '')),
        'created_at' => (string) ($invite['created_at'] ?? ''),
        'expires_at' => (string) ($invite['expires_at'] ?? ''),
      ];
    }

    return $this->ok('Business invites retrieved.', ['invites' => $invites]);
  }

  /**
   * List resolved (accepted, revoked, or withdrawn) invite history for an business.
   *
   * Excludes pending invites. Requires manage-access or manage-business
   * permission.
   *
   * @param string $actorUUID Authenticated actor UUID.
   * @param string $businessId     Business ID.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function listInviteHistory(string $actorUUID, string $businessId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_VIEW_INVITE_HISTORY'));
    }

    $inviteIds = Database::smembers(Keys::BUSINESS_INVITE_BUSINESS . ':' . $businessId);
    sort($inviteIds, SORT_STRING);

    $invites = [];
    foreach ($inviteIds as $inviteId) {
      $invite = Database::hgetall(Keys::BUSINESS_INVITE . ':' . $inviteId);
      if ([] === $invite) {
        continue;
      }

      $status = (string) ($invite['status'] ?? 'pending');
      if ('pending' === $status) {
        continue;
      }

      $invites[] = [
        'invite_id' => (string) ($invite['invite_id'] ?? $inviteId),
        'invitee_email' => (string) ($invite['invitee_email'] ?? ''),
        'invitee_uuid' => (string) ($invite['invitee_uuid'] ?? ''),
        'status' => $status,
        'scopes' => $this->scopeList((string) ($invite['scopes'] ?? '')),
        'batch_code' => (string) ($invite['batch_code'] ?? ''),
        'created_at' => (string) ($invite['created_at'] ?? ''),
        'resolved_at' => (string) ($invite['accepted_at'] ?? $invite['revoked_at'] ?? $invite['withdrawn_at'] ?? ''),
      ];
    }

    usort($invites, static function (array $a, array $b): int {
      return strcmp((string) $b['created_at'], (string) $a['created_at']);
    });

    return $this->ok('Business invite history retrieved.', ['invites' => $invites]);
  }

  /**
   * List resolved access request history for an business.
   *
   * Excludes pending requests. Requires manage-access or manage-business
   * permission.
   *
   * @param string $actorUUID Authenticated actor UUID.
   * @param string $businessId     Business ID.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function listAccessRequestHistory(string $actorUUID, string $businessId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_VIEW_ACCESS_REQUEST_HISTOR'));
    }

    $requestIds = Database::smembers(Keys::BUSINESS_ACCESS_REQUEST_BUSINESS . ':' . $businessId);
    sort($requestIds, SORT_STRING);

    $requests = [];
    foreach ($requestIds as $requestId) {
      $request = Database::hgetall(Keys::BUSINESS_ACCESS_REQUEST . ':' . $requestId);
      if ([] === $request) {
        continue;
      }

      if ((string) ($request['business_id'] ?? '') !== $businessId) {
        continue;
      }

      $status = (string) ($request['status'] ?? 'pending');
      if ($status === 'pending') {
        continue;
      }

      $requests[] = [
        'request_id' => (string) ($request['request_id'] ?? $requestId),
        'requester_contact_email' => (string) ($request['requester_contact_email'] ?? ''),
        'status' => $status,
        'resolved_at' => (string) ($request['approved_at'] ?? $request['rejected_at'] ?? $request['withdrawn_at'] ?? $request['created_at'] ?? ''),
      ];
    }

    usort($requests, static function (array $a, array $b): int {
      return strcmp((string) $b['resolved_at'], (string) $a['resolved_at']);
    });

    return $this->ok('Business access request history retrieved.', [
      'requests' => $requests,
    ]);
  }

  /**
   * List pending membership access requests for an business.
   *
   * Returns only requests with status 'pending'. Requires manage-access
   * or manage-business permission.
   *
   * @param string $actorUUID Authenticated actor UUID.
   * @param string $businessId     Business ID.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function listAccessRequests(string $actorUUID, string $businessId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_VIEW_ACCESS_REQUESTS'));
    }

    $requestIds = Database::smembers(Keys::BUSINESS_ACCESS_REQUEST_BUSINESS . ':' . $businessId);
    $requests = [];

    foreach ($requestIds as $requestId) {
      $request = Database::hgetall(Keys::BUSINESS_ACCESS_REQUEST . ':' . $requestId);
      if ([] === $request) {
        continue;
      }

      if (($request['business_id'] ?? '') !== $businessId) {
        continue;
      }

      $status = (string) ($request['status'] ?? 'pending');
      if ($status !== 'pending') {
        continue;
      }

      $requests[] = [
        'request_id' => (string) ($request['request_id'] ?? $requestId),
        'requester_uuid' => (string) ($request['requester_uuid'] ?? ''),
        'requester_contact_email' => (string) ($request['requester_contact_email'] ?? ''),
        'status' => $status,
        'created_at' => (string) ($request['created_at'] ?? ''),
        'expires_at' => (string) ($request['expires_at'] ?? ''),
      ];
    }

    usort($requests, static function (array $a, array $b): int {
      return strcmp((string) $b['created_at'], (string) $a['created_at']);
    });

    return $this->ok('Business access requests retrieved.', [
      'business_id' => $businessId,
      'requests' => $requests,
    ]);
  }

  /**
   * @param array<string, string> $consentContext
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function approveAccessRequest(string $actorUUID, string $businessId, string $requestId, array $consentContext = []): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_APPROVE_ACCESS_REQUESTS'));
    }

    $requestKey = Keys::BUSINESS_ACCESS_REQUEST . ':' . $requestId;
    $request = Database::hgetall($requestKey);
    if ([] === $request) {
      return $this->fail(Strings::i18n('BUSINESSES_API_ACCESS_REQUEST_NOT_FOUND'));
    }

    if (($request['business_id'] ?? '') !== $businessId) {
      return $this->fail(Strings::i18n('BUSINESSES_API_ACCESS_REQUEST_DOES_NOT_BELONG_TO_THIS_BUSINESS'));
    }

    if ((string) ($request['status'] ?? '') !== 'pending') {
      return $this->fail(Strings::i18n('BUSINESSES_API_ONLY_PENDING_ACCESS_REQUESTS_CAN_BE_APPROVED'));
    }

    $requesterUUID = (string) ($request['requester_uuid'] ?? '');
    if ($requesterUUID === '' || null === UserRepository::getByUUID($requesterUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_REQUESTING_USER_ACCOUNT_WAS_NOT_FOUND'));
    }

    $requesterContactEmail = InputSanitizer::sanitizeEmail((string) ($request['requester_contact_email'] ?? ''));
    if ($requesterContactEmail !== '') {
      $domainGate = $this->ensureContactDomainPolicyAllowsEmail($businessId, $requesterContactEmail, 'request approval email');
      if (!$domainGate['success']) {
        return $domainGate;
      }
    }

    $businessSharedEncryptionEnabled = (bool) SystemConfig::get('business_shared_encryption_enabled');
    $consentId = '';
    $credentialId = '';
    $protectedAccessReady = !$businessSharedEncryptionEnabled;
    $secureBootstrapMessage = '';
    if ($businessSharedEncryptionEnabled) {
      $consentGate = $this->ensureActivationConsent($businessId, $requesterUUID, $consentContext);
      if (!$consentGate['success']) {
        return $consentGate;
      }

      $consentId = self::scalarString($consentGate['data']['consent_id'] ?? '');
      if ($consentId === '') {
        $consentId = self::scalarString($request['consent_id'] ?? '');
      }
      if ($consentId === '') {
        return $this->fail(Strings::i18n('BUSINESSES_API_ACTIVATION_REQUIRES_A_VALID_CONSENT_ID_FOR_DEK_WRAP_BIND'));
      }
    }

    $timestamp = date('c');
    $normalizedScopes = $this->normalizeScopes(self::ACCESS_REQUEST_DEFAULT_SCOPES);
    $scopeCSV = implode(',', $normalizedScopes);
    $currentConnection = $this->connection($businessId, $requesterUUID);
    $currentStatus = (string) ($currentConnection['status'] ?? '');

    if ($currentStatus !== self::MEMBERSHIP_STATE_ACTIVE) {
      $this->setConnection($businessId, $requesterUUID, [
        'role' => 'member',
        'status' => self::MEMBERSHIP_STATE_CONSENTED,
        'scopes' => $scopeCSV,
        'invited_by' => $actorUUID,
        'created_at' => $timestamp,
        'consented_at' => $timestamp,
        'consent_id' => $consentId,
        'credential_id' => $credentialId,
      ]);
    }

    $activeConnection = [
      'status' => self::MEMBERSHIP_STATE_ACTIVE,
      'invited_by' => $actorUUID,
      'accepted_at' => $timestamp,
      'consent_id' => $consentId,
      'credential_id' => $credentialId,
    ];
    if ($currentStatus !== self::MEMBERSHIP_STATE_ACTIVE) {
      $activeConnection['role'] = 'member';
      $activeConnection['scopes'] = $scopeCSV;
      $activeConnection['created_at'] = $timestamp;
    } else {
      if (trim((string) ($currentConnection['role'] ?? '')) === '') {
        $activeConnection['role'] = 'member';
      }
      if (trim((string) ($currentConnection['scopes'] ?? '')) === '') {
        $activeConnection['scopes'] = $scopeCSV;
      }
      if (trim((string) ($currentConnection['created_at'] ?? '')) === '') {
        $activeConnection['created_at'] = $timestamp;
      }
    }

    $this->setConnection($businessId, $requesterUUID, $activeConnection);

    if ($businessSharedEncryptionEnabled) {
      $wrapBootstrap = $this->bootstrapBusinessDekWrapForMember(
        $businessId,
        $requesterUUID,
        $consentId,
        self::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
        self::BUSINESS_DEK_VERSION_CURRENT
      );
      if ($wrapBootstrap['success']) {
        $credentialId = self::scalarString($wrapBootstrap['data']['credential_id'] ?? '');
        $protectedAccessReady = $credentialId !== '';
        $activeConnectionWithCredential = [
          'status' => self::MEMBERSHIP_STATE_ACTIVE,
          'invited_by' => $actorUUID,
          'accepted_at' => $timestamp,
          'consent_id' => $consentId,
          'credential_id' => $credentialId,
        ];
        if ($currentStatus !== self::MEMBERSHIP_STATE_ACTIVE) {
          $activeConnectionWithCredential['role'] = 'member';
          $activeConnectionWithCredential['scopes'] = $scopeCSV;
          $activeConnectionWithCredential['created_at'] = $timestamp;
        }
        $this->setConnection($businessId, $requesterUUID, $activeConnectionWithCredential);
      } else {
        $secureBootstrapMessage = self::scalarString($wrapBootstrap['message']);
      }
    }

    Database::hset($requestKey, [
      'status' => 'approved',
      'approved_by' => $actorUUID,
      'approved_at' => $timestamp,
    ]);
    Database::unlink($this->accessRequestActiveKey($businessId, $requesterUUID));

    BusinessDashboardMetrics::recordPendingRequestResolved($businessId);

    $this->appendAuditEvent($businessId, 'access.request.approved', $actorUUID, [
      'request_id' => $requestId,
      'requester_uuid' => $requesterUUID,
      'scopes' => $scopeCSV,
      'consent_id' => $consentId,
      'credential_id' => $credentialId,
    ]);
    $this->incrementAccessRequestTelemetry('approved');

    return $this->ok('Access request approved.', [
      'request_id' => $requestId,
      'business_id' => $businessId,
      'requester_uuid' => $requesterUUID,
      'scopes' => $normalizedScopes,
      'consent_id' => $consentId,
      'credential_id' => $credentialId,
      'protected_access_ready' => $protectedAccessReady,
      'secure_bootstrap_message' => $secureBootstrapMessage,
    ]);
  }

  /**
   * Reject a pending membership access request.
   *
   * Sets the request status to 'rejected' and records the rejecting actor.
   * Requires manage-access or manage-business permission.
   *
   * @param string $actorUUID  Authenticated actor UUID.
   * @param string $businessId      Business ID.
   * @param string $requestId  Access request ID to reject.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function rejectAccessRequest(string $actorUUID, string $businessId, string $requestId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_REJECT_ACCESS_REQUESTS'));
    }

    $requestKey = Keys::BUSINESS_ACCESS_REQUEST . ':' . $requestId;
    $request = Database::hgetall($requestKey);
    if ([] === $request) {
      return $this->fail(Strings::i18n('BUSINESSES_API_ACCESS_REQUEST_NOT_FOUND'));
    }

    if (($request['business_id'] ?? '') !== $businessId) {
      return $this->fail(Strings::i18n('BUSINESSES_API_ACCESS_REQUEST_DOES_NOT_BELONG_TO_THIS_BUSINESS'));
    }

    if ((string) ($request['status'] ?? '') !== 'pending') {
      return $this->fail(Strings::i18n('BUSINESSES_API_ONLY_PENDING_ACCESS_REQUESTS_CAN_BE_REJECTED'));
    }

    $requesterUUID = (string) ($request['requester_uuid'] ?? '');
    $timestamp = date('c');

    Database::hset($requestKey, [
      'status' => 'rejected',
      'rejected_by' => $actorUUID,
      'rejected_at' => $timestamp,
    ]);

    if ($requesterUUID !== '') {
      Database::unlink($this->accessRequestActiveKey($businessId, $requesterUUID));
      $connection = $this->connection($businessId, $requesterUUID);
      if ((string) ($connection['status'] ?? '') === self::MEMBERSHIP_STATE_PENDING) {
        $this->setConnection($businessId, $requesterUUID, [
          'status' => self::MEMBERSHIP_STATE_REJECTED,
          'rejected_by' => $actorUUID,
          'rejected_at' => $timestamp,
        ]);
      }
    }

    BusinessDashboardMetrics::recordPendingRequestResolved($businessId);

    $this->appendAuditEvent($businessId, 'access.request.rejected', $actorUUID, [
      'request_id' => $requestId,
      'requester_uuid' => $requesterUUID,
    ]);
    $this->incrementAccessRequestTelemetry('rejected');

    return $this->ok('Access request rejected.', [
      'request_id' => $requestId,
      'business_id' => $businessId,
      'requester_uuid' => $requesterUUID,
    ]);
  }

  /**
   * Revoke a pending business invite.
   *
   * Marks the invite as revoked and fires an audit event. Requires
   * manage-access or manage-business permission.
   *
   * @param string $actorUUID Authenticated actor UUID.
   * @param string $businessId     Business ID.
   * @param string $inviteId  Invite ID to revoke.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function revokeInvite(string $actorUUID, string $businessId, string $inviteId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_REVOKE_INVITES'));
    }

    $inviteKey = Keys::BUSINESS_INVITE . ':' . $inviteId;
    $invite = Database::hgetall($inviteKey);
    if ([] === $invite) {
      return $this->fail(Strings::i18n('BUSINESSES_API_INVITE_NOT_FOUND'));
    }

    if (($invite['business_id'] ?? '') !== $businessId) {
      return $this->fail(Strings::i18n('BUSINESSES_API_INVITE_DOES_NOT_BELONG_TO_THIS_BUSINESS'));
    }

    $status = (string) ($invite['status'] ?? '');
    if ($status !== 'pending') {
      return $this->fail(Strings::i18n('BUSINESSES_API_ONLY_PENDING_INVITES_CAN_BE_REVOKED'));
    }

    Database::hset($inviteKey, [
      'status' => 'revoked',
      'revoked_by' => $actorUUID,
      'revoked_at' => date('c'),
    ]);

    $inviteeUUID = (string) ($invite['invitee_uuid'] ?? '');
    if ($inviteeUUID !== '') {
      $connection = $this->connection($businessId, $inviteeUUID);
      if ((string) ($connection['status'] ?? '') === self::MEMBERSHIP_STATE_PENDING) {
        $this->setConnection($businessId, $inviteeUUID, [
          'status' => self::MEMBERSHIP_STATE_REVOKED,
          'revoked_by' => $actorUUID,
          'revoked_at' => date('c'),
        ]);
      }
    }

    BusinessDashboardMetrics::recordPendingInviteResolved($businessId);

    $this->appendAuditEvent($businessId, 'invite.revoked', $actorUUID, [
      'invite_id' => $inviteId,
    ]);

    return $this->ok('Invite revoked.', ['invite_id' => $inviteId, 'business_id' => $businessId]);
  }

  /**
   * @param array<string, string> $consentContext
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function acceptInvite(string $inviteToken, string $inviteeUUID, array $consentContext = []): array
  {
    $token = trim($inviteToken);
    if ('' === $token) {
      return $this->fail(Strings::i18n('BUSINESSES_API_INVITE_TOKEN_IS_REQUIRED'));
    }

    $inviteId = self::scalarString(Database::get(Keys::BUSINESS_INVITE_TOKEN . ':' . $token));
    if ('' === $inviteId) {
      return $this->fail(Strings::i18n('BUSINESSES_API_INVITE_TOKEN_IS_INVALID_OR_EXPIRED'));
    }

    $inviteKey = Keys::BUSINESS_INVITE . ':' . $inviteId;
    $invite = Database::hgetall($inviteKey);
    if ([] === $invite) {
      return $this->fail(Strings::i18n('BUSINESSES_API_INVITE_NOT_FOUND'));
    }

    if (($invite['status'] ?? '') !== 'pending') {
      return $this->fail(Strings::i18n('BUSINESSES_API_INVITE_IS_NO_LONGER_ACTIVE'));
    }

    $inviteeBound = (string) ($invite['invitee_uuid'] ?? '');
    if ('' !== $inviteeBound && $inviteeBound !== $inviteeUUID) {
      return $this->fail(Strings::i18n('BUSINESSES_API_INVITE_DOES_NOT_BELONG_TO_THIS_USER'));
    }

    $expiresAt = strtotime((string) ($invite['expires_at'] ?? ''));
    if (false === $expiresAt || $expiresAt < time()) {
      Database::hset($inviteKey, ['status' => 'expired']);
      if ($inviteeBound !== '') {
        $connection = $this->connection((string) ($invite['business_id'] ?? ''), $inviteeBound);
        if ((string) ($connection['status'] ?? '') === self::MEMBERSHIP_STATE_PENDING) {
          $this->setConnection((string) ($invite['business_id'] ?? ''), $inviteeBound, [
            'status' => self::MEMBERSHIP_STATE_EXPIRED,
            'expired_at' => date('c'),
          ]);
        }
      }
      return $this->fail(Strings::i18n('BUSINESSES_API_INVITE_HAS_EXPIRED'));
    }

    $businessId = (string) ($invite['business_id'] ?? '');
    if ('' === $businessId || !Database::exists(Keys::BUSINESS . ':' . $businessId)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND_FOR_INVITE'));
    }

    $inviteeEmail = InputSanitizer::sanitizeEmail((string) ($invite['invitee_email'] ?? ''));
    if ($inviteeEmail !== '') {
      $domainGate = $this->ensureContactDomainPolicyAllowsEmail($businessId, $inviteeEmail, 'invite acceptance email');
      if (!$domainGate['success']) {
        return $domainGate;
      }
    }

    $consentId = '';
    $credentialId = '';
    if ((bool) SystemConfig::get('business_shared_encryption_enabled')) {
      $consentGate = $this->ensureActivationConsent($businessId, $inviteeUUID, $consentContext);
      if (!$consentGate['success']) {
        return $consentGate;
      }

      $consentId = self::scalarString($consentGate['data']['consent_id'] ?? '');
      if ($consentId === '') {
        return $this->fail(Strings::i18n('BUSINESSES_API_ACTIVATION_REQUIRES_A_VALID_CONSENT_ID_FOR_DEK_WRAP_BIND'));
      }

      $wrapBootstrap = $this->bootstrapBusinessDekWrapForMember(
        $businessId,
        $inviteeUUID,
        $consentId,
        self::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
        self::BUSINESS_DEK_VERSION_CURRENT
      );
      if (!$wrapBootstrap['success']) {
        return $wrapBootstrap;
      }

      $credentialId = self::scalarString($wrapBootstrap['data']['credential_id'] ?? '');
    }

    $timestamp = date('c');
    $scopes = (string) ($invite['scopes'] ?? '');

    $this->setConnection($businessId, $inviteeUUID, [
      'role' => 'member',
      'status' => self::MEMBERSHIP_STATE_CONSENTED,
      'scopes' => $scopes,
      'invited_by' => (string) ($invite['invited_by'] ?? ''),
      'created_at' => $timestamp,
      'consented_at' => $timestamp,
      'consent_id' => $consentId,
      'credential_id' => $credentialId,
    ]);

    $this->setConnection($businessId, $inviteeUUID, [
      'role' => 'member',
      'status' => self::MEMBERSHIP_STATE_ACTIVE,
      'scopes' => $scopes,
      'invited_by' => (string) ($invite['invited_by'] ?? ''),
      'created_at' => $timestamp,
      'accepted_at' => $timestamp,
      'consent_id' => $consentId,
      'credential_id' => $credentialId,
    ]);

    Database::hset($inviteKey, [
      'status' => 'accepted',
      'accepted_by' => $inviteeUUID,
      'accepted_at' => $timestamp,
    ]);

    BusinessDashboardMetrics::recordPendingInviteResolved($businessId);

    $this->appendAuditEvent($businessId, 'invite.accepted', $inviteeUUID, [
      'invite_id' => (string) ($invite['invite_id'] ?? ''),
      'accepted_by' => $inviteeUUID,
      'scopes' => $scopes,
      'consent_id' => $consentId,
      'credential_id' => $credentialId,
    ]);

    return $this->ok('Invite accepted.', [
      'business_id' => $businessId,
      'scopes' => $this->scopeList($scopes),
      'consent_id' => $consentId,
      'credential_id' => $credentialId,
    ]);
  }

  /**
   * Membership acceptance flow with explicit consent payload.
   *
   * Supports:
   * - Invite acceptance with consent context (`invite_token`)
   * - Consent capture for pending access request (`request_id`) while waiting admin approval
   *
   * @param array<string, mixed> $payload
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function acceptMembershipWithConsent(string $actorUUID, string $businessId, array $payload): array
  {
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    if ($businessId === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_ID_IS_REQUIRED'));
    }

    if (!Database::exists(Keys::BUSINESS . ':' . $businessId)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND'));
    }

    $inviteTokenRaw = $payload['invite_token'] ?? '';
    $inviteToken = is_scalar($inviteTokenRaw) ? trim((string) $inviteTokenRaw) : '';

    $requestIdRaw = $payload['request_id'] ?? '';
    $requestId = is_scalar($requestIdRaw) ? trim((string) $requestIdRaw) : '';

    if ($inviteToken === '' && $requestId === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_PROVIDE_INVITE_TOKEN_OR_REQUEST_ID'));
    }

    $consentContext = [
      'consent_id' => is_scalar($payload['consent_id'] ?? null) ? (string) $payload['consent_id'] : '',
      'consent_version' => is_scalar($payload['consent_version'] ?? null) ? (string) $payload['consent_version'] : '',
      'consent_acknowledged' => is_scalar($payload['consent_acknowledged'] ?? null) ? (string) $payload['consent_acknowledged'] : '',
      'disclaimer_text' => is_scalar($payload['disclaimer_text'] ?? null) ? (string) $payload['disclaimer_text'] : '',
      'ip' => is_scalar($payload['ip'] ?? null) ? (string) $payload['ip'] : '',
      'user_agent' => is_scalar($payload['user_agent'] ?? null) ? (string) $payload['user_agent'] : '',
    ];

    if ($inviteToken !== '') {
      $result = $this->acceptInvite($inviteToken, $actorUUID, $consentContext);
      if (!$result['success']) {
        return $result;
      }

      $resolvedOrgId = self::scalarString($result['data']['business_id'] ?? '');
      if ($resolvedOrgId !== $businessId) {
        return $this->fail(Strings::i18n('BUSINESSES_API_INVITE_BUSINESS_DOES_NOT_MATCH_REQUESTED_BUSINESS_CONTEX'));
      }

      return $result;
    }

    $requestKey = Keys::BUSINESS_ACCESS_REQUEST . ':' . $requestId;
    $request = Database::hgetall($requestKey);
    if ($request === []) {
      return $this->fail(Strings::i18n('BUSINESSES_API_ACCESS_REQUEST_NOT_FOUND'));
    }

    if ((string) ($request['business_id'] ?? '') !== $businessId) {
      return $this->fail(Strings::i18n('BUSINESSES_API_ACCESS_REQUEST_DOES_NOT_BELONG_TO_THIS_BUSINESS'));
    }

    if ((string) ($request['requester_uuid'] ?? '') !== $actorUUID) {
      return $this->fail(Strings::i18n('BUSINESSES_API_ACCESS_REQUEST_DOES_NOT_BELONG_TO_CURRENT_USER'));
    }

    if ((string) ($request['status'] ?? '') !== self::MEMBERSHIP_STATE_PENDING) {
      return $this->fail(Strings::i18n('BUSINESSES_API_ONLY_PENDING_ACCESS_REQUESTS_CAN_CAPTURE_CONSENT'));
    }

    $consentGate = $this->ensureActivationConsent($businessId, $actorUUID, $consentContext);
    if (!$consentGate['success']) {
      return $consentGate;
    }

    Database::hset($requestKey, [
      'consent_id' => self::scalarString($consentGate['data']['consent_id'] ?? ''),
      'consented_at' => date('c'),
    ]);

    $this->appendAuditEvent($businessId, 'access.request.consented', $actorUUID, [
      'request_id' => $requestId,
      'consent_id' => self::scalarString($consentGate['data']['consent_id'] ?? ''),
    ]);

    return $this->ok('Consent captured for pending access request.', [
      'business_id' => $businessId,
      'request_id' => $requestId,
      'status' => self::MEMBERSHIP_STATE_PENDING,
      'consent_id' => self::scalarString($consentGate['data']['consent_id'] ?? ''),
      'pending_approval' => true,
    ]);
  }

  /**
   * Grant protected business data sharing consent for the current member.
   *
   * This does not change membership status. It records consent and refreshes
   * the member's business DEK wrap so protected reports can be generated while the
   * membership remains active.
   *
   * @param array<string, mixed> $payload
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function grantBusinessDataConsent(string $actorUUID, string $businessId, array $payload = []): array
  {
    $actorUUID = trim(InputSanitizer::sanitizeString($actorUUID));
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    if ($actorUUID === '' || $businessId === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_CONSENT_REQUIRES_VALID_BUSINESS_AND_USER_IDENTIFIERS'));
    }

    if (!Database::exists(Keys::BUSINESS . ':' . $businessId)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND'));
    }

    $connection = $this->connection($businessId, $actorUUID);
    if ((string) ($connection['status'] ?? '') !== self::MEMBERSHIP_STATE_ACTIVE) {
      return $this->fail(Strings::i18n('BUSINESSES_API_NO_ACTIVE_CONNECTION_FOUND'));
    }

    $consent = $this->loadActiveBusinessConsent($businessId, $actorUUID);
    $consentId = self::scalarString($consent['consent_id'] ?? '');
    if ($consentId === '') {
      $consentAcknowledgedRaw = $payload['consent_acknowledged'] ?? '1';
      $consentAcknowledged = filter_var($consentAcknowledgedRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
      if (!$consentAcknowledged) {
        return $this->fail(Strings::i18n('BUSINESSES_API_ACTIVATION_REQUIRES_EXPLICIT_CONSENT_ACKNOWLEDGMENT'));
      }

      $consentVersion = self::scalarString($payload['consent_version'] ?? 'v1');
      if ($consentVersion === '') {
        $consentVersion = 'v1';
      }
      $disclaimerText = self::scalarString($payload['disclaimer_text'] ?? '');
      if ($disclaimerText === '') {
        $disclaimerText = 'Business shared encryption consent accepted.';
      }

      $consentGate = $this->recordBusinessConsent(
        $businessId,
        $actorUUID,
        $consentVersion,
        $disclaimerText,
        self::scalarString($payload['ip'] ?? ''),
        self::scalarString($payload['user_agent'] ?? '')
      );
      if (!$consentGate['success']) {
        return $consentGate;
      }

      $consentId = self::scalarString($consentGate['data']['consent_id'] ?? '');
      if ($consentId === '') {
        return $this->fail(Strings::i18n('BUSINESSES_API_ACTIVATION_REQUIRES_A_VALID_CONSENT_ID_FOR_DEK_WRAP_BIND'));
      }
    }

    if (!(bool) SystemConfig::get('business_shared_encryption_enabled')) {
      BusinessWorkspaceCache::invalidate($businessId);

      $this->appendAuditEvent($businessId, 'business.consent.granted_from_settings', $actorUUID, [
        'consent_id' => $consentId,
        'user_uuid' => $actorUUID,
        'secure_bootstrap_skipped' => 'business_shared_encryption_disabled',
      ]);

      return $this->ok('Business data consent granted.', [
        'business_id' => $businessId,
        'user_uuid' => $actorUUID,
        'status' => self::MEMBERSHIP_STATE_ACTIVE,
        'consent_id' => $consentId,
        'protected_access_ready' => true,
        'secure_bootstrap_required' => false,
        'credential_id' => '',
      ]);
    }

    $wrapBootstrap = $this->bootstrapBusinessDekWrapForMember(
      $businessId,
      $actorUUID,
      $consentId,
      self::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      self::BUSINESS_DEK_VERSION_CURRENT
    );
    if (!$wrapBootstrap['success']) {
      return $wrapBootstrap;
    }

    BusinessWorkspaceCache::invalidate($businessId);

    $this->appendAuditEvent($businessId, 'business.consent.granted_from_settings', $actorUUID, [
      'consent_id' => $consentId,
      'user_uuid' => $actorUUID,
    ]);

    return $this->ok('Business data consent granted.', [
      'business_id' => $businessId,
      'user_uuid' => $actorUUID,
      'status' => self::MEMBERSHIP_STATE_ACTIVE,
      'consent_id' => $consentId,
      'protected_access_ready' => true,
      'secure_bootstrap_required' => true,
      'credential_id' => self::scalarString($wrapBootstrap['data']['credential_id'] ?? ''),
    ]);
  }

  /**
   * Revoke protected business data sharing consent without leaving the business.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function revokeBusinessDataConsent(string $actorUUID, string $businessId): array
  {
    $actorUUID = trim(InputSanitizer::sanitizeString($actorUUID));
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    if ($actorUUID === '' || $businessId === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_CONSENT_REQUIRES_VALID_BUSINESS_AND_USER_IDENTIFIERS'));
    }

    if (!Database::exists(Keys::BUSINESS . ':' . $businessId)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND'));
    }

    if ([] === $this->connection($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_CONNECTION_NOT_FOUND'));
    }

    $timestamp = date('c');
    $revokedConsentCount = 0;
    foreach (Database::smembers(Keys::businessConsentsByUser($actorUUID)) as $consentIdRaw) {
      $consentId = trim((string) $consentIdRaw);
      if ($consentId === '') {
        continue;
      }

      $consentKey = Keys::businessConsent($consentId);
      $consent = Database::hgetall($consentKey);
      if (
        $consent === []
        || (string) ($consent['business_id'] ?? '') !== $businessId
        || (string) ($consent['user_uuid'] ?? '') !== $actorUUID
        || (string) ($consent['status'] ?? '') !== self::MEMBERSHIP_STATE_ACTIVE
      ) {
        continue;
      }

      Database::hset($consentKey, [
        'status' => self::MEMBERSHIP_STATE_REVOKED,
        'revoked_at' => $timestamp,
        'revoked_by' => $actorUUID,
        'updated_at' => $timestamp,
      ]);
      $revokedConsentCount++;
    }

    $wrapRevocation = (new BusinessEncryptionService())
      ->revokeWrapsForMembership($businessId, $actorUUID, 'consent_revoked');
    $revokedWrapCount = self::scalarInt($wrapRevocation['data']['revoked_wrap_count'] ?? 0);

    BusinessWorkspaceCache::invalidate($businessId);

    $this->appendAuditEvent($businessId, 'business.consent.revoked_from_settings', $actorUUID, [
      'user_uuid' => $actorUUID,
      'revoked_consent_count' => $revokedConsentCount,
      'revoked_wrap_count' => $revokedWrapCount,
    ]);

    return $this->ok('Business data consent revoked.', [
      'business_id' => $businessId,
      'user_uuid' => $actorUUID,
      'status' => self::MEMBERSHIP_STATE_REVOKED,
      'revoked_consent_count' => $revokedConsentCount,
      'revoked_wrap_count' => $revokedWrapCount,
    ]);
  }

  /**
   * Revoke a member's active connection, removing their access and encrypted key wraps.
   *
   * Owners cannot be removed via this path. Triggers DEK-wrap revocation for
   * the target member before deleting the connection record.
   *
   * @param string $actorUUID  Authenticated actor UUID performing the revocation.
   * @param string $businessId      Business ID.
   * @param string $targetUUID UUID of the member being removed.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function revokeConnection(string $actorUUID, string $businessId, string $targetUUID): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_REVOKE_ACCESS'));
    }

    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ([] === $business) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND'));
    }

    if (($business['owner_uuid'] ?? '') === $targetUUID) {
      return $this->fail(Strings::i18n('BUSINESSES_API_CANNOT_REVOKE_THE_BUSINESS_OWNER'));
    }

    if ([] === $this->connection($businessId, $targetUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_CONNECTION_NOT_FOUND'));
    }

    $this->setConnection($businessId, $targetUUID, [
      'status'     => 'revoked',
      'revoked_by' => $actorUUID,
      'revoked_at' => date('c'),
    ]);

    $wrapRevocation = (new BusinessEncryptionService())
      ->revokeWrapsForMembership($businessId, $targetUUID, 'membership_revoked');

    $this->appendAuditEvent($businessId, 'connection.revoked', $actorUUID, [
      'target_user_uuid' => $targetUUID,
      'revoked_wrap_count' => self::scalarInt($wrapRevocation['data']['revoked_wrap_count'] ?? 0),
    ]);

    return $this->ok('Connection revoked.', [
      'business_id' => $businessId,
      'user_uuid' => $targetUUID,
      'revoked_wrap_count' => self::scalarInt($wrapRevocation['data']['revoked_wrap_count'] ?? 0),
    ]);
  }

  /**
   * Update a member's role and associated permission scopes.
   *
   * Validates the new role name, enforces actor permission, and re-evaluates
   * any derived scopes for the role transition.
   *
   * @param string $actorUUID  Authenticated actor UUID.
   * @param string $businessId      Business ID.
   * @param string $targetUUID UUID of the member whose role is changing.
   * @param string $role       New role name to assign.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function updateConnectionRole(string $actorUUID, string $businessId, string $targetUUID, string $role): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_CHANGE_MEMBER_ROLES'));
    }

    $targetRole = strtolower(trim($role));
    if ($targetRole === '' || !isset(self::VALID_BUSINESS_ROLES[$targetRole])) {
      return $this->fail(Strings::i18n('BUSINESSES_API_INVALID_BUSINESS_ROLE'));
    }

    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ([] === $business) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND'));
    }

    $connection = $this->connection($businessId, $targetUUID);
    if ([] === $connection) {
      return $this->fail(Strings::i18n('BUSINESSES_API_CONNECTION_NOT_FOUND'));
    }

    $actorConnection = $this->connection($businessId, $actorUUID);
    $actorIsOwner = (string) ($business['owner_uuid'] ?? '') === $actorUUID;
    $actorRole = $actorIsOwner
      ? 'owner'
      : strtolower(trim((string) ($actorConnection['role'] ?? '')));
    $currentTargetRole = strtolower(trim((string) ($connection['role'] ?? '')));

    if (!$actorIsOwner && $actorRole === 'coordinator') {
      if ((string) ($business['owner_uuid'] ?? '') === $targetUUID || $currentTargetRole === 'owner') {
        return $this->fail(Strings::i18n('BUSINESSES_API_MANAGERS_CANNOT_MODIFY_THE_BUSINESS_OWNER'));
      }

      if ($targetRole === 'owner') {
        return $this->fail(Strings::i18n('BUSINESSES_API_MANAGERS_CANNOT_PROMOTE_MEMBERS_TO_OWNER'));
      }

      if (self::roleRank($targetRole) > self::roleRank($actorRole)) {
        return $this->fail(Strings::i18n('BUSINESSES_API_MANAGERS_CANNOT_ASSIGN_ROLES_ABOVE_THEIR_OWN_LEVEL'));
      }
    }

    $status = (string) ($connection['status'] ?? '');
    if ($status !== 'active' && $status !== 'pending') {
      return $this->fail(Strings::i18n('BUSINESSES_API_ONLY_ACTIVE_OR_PENDING_MEMBERSHIPS_CAN_BE_UPDATED'));
    }

    if ((string) ($business['owner_uuid'] ?? '') === $targetUUID && $targetRole !== 'owner') {
      return $this->fail(Strings::i18n('BUSINESSES_API_CANNOT_CHANGE_OWNER_ROLE'));
    }

    if ($targetRole === 'owner') {
      $scopeCSV = 'all';
    } else {
      $preset = $this->resolveRolePreset($targetRole);
      if ($preset === null) {
        return $this->fail(Strings::i18n('BUSINESSES_API_UNABLE_TO_RESOLVE_ROLE_PRESET_SCOPES'));
      }

      $scopeCSV = $preset['scopes'];
    }

    $this->setConnection($businessId, $targetUUID, [
      'role' => $targetRole,
      'scopes' => $scopeCSV,
      'updated_by' => $actorUUID,
      'role_updated_at' => date('c'),
    ]);

    $this->appendAuditEvent($businessId, 'connection.role_updated', $actorUUID, [
      'target_user_uuid' => $targetUUID,
      'role' => $targetRole,
      'scopes' => $scopeCSV,
    ]);

    return $this->ok('Connection role updated.', [
      'business_id' => $businessId,
      'user_uuid' => $targetUUID,
      'role' => $targetRole,
      'scopes' => $this->scopeList($scopeCSV),
    ]);
  }

  /**
   * Allow a member to voluntarily leave an business.
   *
   * Owners must transfer ownership before leaving. Triggers DEK-wrap
   * revocation for the departing member before removing the connection.
   *
   * @param string $userUUID Authenticated user UUID.
   * @param string $businessId    Business ID to leave.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function leaveBusiness(string $userUUID, string $businessId): array
  {
    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ([] === $business) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND'));
    }

    if (($business['owner_uuid'] ?? '') === $userUUID) {
      if ((string) ($business['business_type'] ?? 'shared') === 'personal') {
        return $this->fail(Strings::i18n('BUSINESSES_API_PERSONAL_BUSINESSES_CANNOT_BE_DELETED_OR_LEFT'));
      }

      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_OWNER_MUST_TRANSFER_OWNERSHIP_BEFORE_LEAVING'));
    }

    $connection = $this->connection($businessId, $userUUID);
    if ([] === $connection) {
      return $this->fail(Strings::i18n('BUSINESSES_API_NO_ACTIVE_CONNECTION_FOUND'));
    }

    $timestamp = date('c');
    $previousStatus = (string) ($connection['status'] ?? '');
    $pendingRequestId = '';

    if ($previousStatus === self::MEMBERSHIP_STATE_PENDING) {
      $activeKey = $this->accessRequestActiveKey($businessId, $userUUID);
      $activeRequestId = (string) Database::get($activeKey);
      $candidateRequestIds = [];
      if ($activeRequestId !== '') {
        $candidateRequestIds[] = $activeRequestId;
      }
      foreach (Database::smembers(Keys::BUSINESS_ACCESS_REQUEST_REQUESTER . ':' . $userUUID) as $requestId) {
        $requestId = trim((string) $requestId);
        if ($requestId !== '' && !in_array($requestId, $candidateRequestIds, true)) {
          $candidateRequestIds[] = $requestId;
        }
      }

      foreach ($candidateRequestIds as $requestId) {
        $request = Database::hgetall(Keys::BUSINESS_ACCESS_REQUEST . ':' . $requestId);
        if (
          [] !== $request
          && (string) ($request['business_id'] ?? '') === $businessId
          && (string) ($request['requester_uuid'] ?? '') === $userUUID
          && (string) ($request['status'] ?? '') === self::MEMBERSHIP_STATE_PENDING
        ) {
          $pendingRequestId = $requestId;
          break;
        }
      }

      Database::unlink($activeKey);

      if ($pendingRequestId !== '') {
        Database::hset(Keys::BUSINESS_ACCESS_REQUEST . ':' . $pendingRequestId, [
          'status' => 'withdrawn',
          'withdrawn_by' => $userUUID,
          'withdrawn_at' => $timestamp,
        ]);
        BusinessDashboardMetrics::recordPendingRequestResolved($businessId);
        $this->incrementAccessRequestTelemetry('withdrawn');
      }
    }

    $this->setConnection($businessId, $userUUID, [
      'status'       => 'withdrawn',
      'withdrawn_at' => $timestamp,
    ]);

    $wrapRevocation = (new BusinessEncryptionService())
      ->revokeWrapsForMembership($businessId, $userUUID, 'membership_withdrawn');

    $this->appendAuditEvent($businessId, 'connection.withdrawn', $userUUID, [
      'user_uuid' => $userUUID,
      'request_id' => $pendingRequestId,
      'previous_status' => $previousStatus,
      'revoked_wrap_count' => self::scalarInt($wrapRevocation['data']['revoked_wrap_count'] ?? 0),
    ]);

    return $this->ok(
      $pendingRequestId !== '' ? 'Membership request canceled.' : 'You have left the business.',
      [
        'business_id' => $businessId,
        'request_id' => $pendingRequestId,
        'revoked_wrap_count' => self::scalarInt($wrapRevocation['data']['revoked_wrap_count'] ?? 0),
      ]
    );
  }

  /**
   * Resolve how a linked site should be labeled in the business workspace.
   *
   * @param array<string, mixed> $siteHash
   */
  public function resolveBusinessSiteOwnership(
    string $actorUUID,
    string $siteOwnerUUID,
    string $businessOwnerUUID,
    array $siteHash,
  ): string {
    $storedScope = strtolower(trim(self::scalarString($siteHash['ownership_scope'] ?? '')));
    if ($storedScope === self::BUSINESS_SITE_OWNERSHIP_BUSINESS) {
      return self::BUSINESS_SITE_OWNERSHIP_BUSINESS;
    }

    $businessManaged = strtolower(trim(self::scalarString($siteHash['business_managed'] ?? '')));
    if (in_array($businessManaged, ['1', 'true', 'yes'], true)) {
      return self::BUSINESS_SITE_OWNERSHIP_BUSINESS;
    }

    if ($storedScope === self::BUSINESS_SITE_OWNERSHIP_LINKED) {
      return self::BUSINESS_SITE_OWNERSHIP_LINKED;
    }

    if ($siteOwnerUUID !== '' && $siteOwnerUUID !== $actorUUID) {
      if ($siteOwnerUUID === $businessOwnerUUID) {
        return self::BUSINESS_SITE_OWNERSHIP_BUSINESS;
      }

      return self::BUSINESS_SITE_OWNERSHIP_SHARED;
    }

    return self::BUSINESS_SITE_OWNERSHIP_LINKED;
  }

  /**
   * Business site ownership label.
   */
  public function businessSiteOwnershipLabel(string $ownershipScope): string
  {
    return match ($ownershipScope) {
      self::BUSINESS_SITE_OWNERSHIP_BUSINESS => Strings::i18n('BUSINESS_SITES_OWNERSHIP_BUSINESS'),
      self::BUSINESS_SITE_OWNERSHIP_SHARED => Strings::i18n('BUSINESS_SITES_OWNERSHIP_SHARED'),
      default => Strings::i18n('BUSINESS_SITES_OWNERSHIP_PERSONAL'),
    };
  }

  /**
   * @param array<string, mixed> $siteHash
   * @return array{ownership_scope: string, ownership_label: string}
   */
  public function businessSiteOwnershipMeta(
    string $actorUUID,
    string $siteOwnerUUID,
    string $businessOwnerUUID,
    array $siteHash,
  ): array {
    $ownershipScope = $this->resolveBusinessSiteOwnership($actorUUID, $siteOwnerUUID, $businessOwnerUUID, $siteHash);

    return [
      'ownership_scope' => $ownershipScope,
      'ownership_label' => $this->businessSiteOwnershipLabel($ownershipScope),
    ];
  }

  /**
   * Persist business-workspace ownership metadata on a site record.
   */
  private function applyBusinessSiteOwnership(
    string $siteOwnerUUID,
    string $siteId,
    string $ownershipScope,
    string $businessId,
  ): void {
    $siteKey = Keys::SITE . ':' . $siteOwnerUUID . ':' . $siteId;
    if ($ownershipScope === self::BUSINESS_SITE_OWNERSHIP_LINKED) {
      $existingScope = strtolower(trim((string) Database::hget($siteKey, 'ownership_scope')));
      $existingManaged = strtolower(trim((string) Database::hget($siteKey, 'business_managed')));
      if (
        $existingScope === self::BUSINESS_SITE_OWNERSHIP_BUSINESS
        || in_array($existingManaged, ['1', 'true', 'yes'], true)
      ) {
        $ownershipScope = self::BUSINESS_SITE_OWNERSHIP_BUSINESS;
      }
    }

    $patch = [
      'business_id' => $businessId,
      'ownership_scope' => $ownershipScope,
      'business_managed' => $ownershipScope === self::BUSINESS_SITE_OWNERSHIP_BUSINESS ? '1' : '',
    ];
    Database::hset($siteKey, $patch);
  }

  /**
   * Clear business-workspace ownership metadata from a site record.
   */
  private function clearBusinessSiteOwnership(string $siteOwnerUUID, string $siteId): void
  {
    Database::hset(Keys::SITE . ':' . $siteOwnerUUID . ':' . $siteId, [
      'business_id' => '',
      'ownership_scope' => '',
      'business_managed' => '',
    ]);
  }

  /**
   * Returns all sites linked to an business, with their org-level site settings.
   *
   * Access: any org member with sites.read scope.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function listBusinessSites(string $actorUUID, string $businessId, bool $useCache = true): array
  {
    if (!$this->canReadBusinessSites($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_VIEW_SITES_FOR_THIS_BUSINE'));
    }

    if ($useCache) {
      $cachedRaw = BusinessWorkspaceCache::getSitesRaw($businessId);
      if ($cachedRaw !== null) {
        return $this->ok('Org sites retrieved.', $this->assembleSitesResponse(
          $actorUUID,
          $businessId,
          $cachedRaw,
        ));
      }
    }

    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    $businessOwnerUUID = (string) ($business['owner_uuid'] ?? '');

    $siteRefs = Database::smembers(Keys::BUSINESS_SITE . ':' . $businessId);
    $refsByKey = [];
    $siteKeys = [];
    $settingsKeys = [];
    foreach ($siteRefs as $ref) {
      $ref = (string) $ref;
      $parts = explode(':', $ref, 2);
      if (count($parts) !== 2) {
        continue;
      }
      [$ownerUUID, $siteId] = $parts;
      $refsByKey[$ref] = [$ownerUUID, $siteId];
      $siteKeys[$ref] = Keys::SITE . ':' . $ownerUUID . ':' . $siteId;
      $settingsKeys[$ref] = Keys::BUSINESS_SITE_SETTINGS . ':' . $businessId . ':' . $ref;
    }

    $siteHashes = $siteKeys !== []
      ? Database::pipelineHgetall(array_values($siteKeys))
      : [];
    $settingsHashes = $settingsKeys !== []
      ? Database::pipelineHgetall(array_values($settingsKeys))
      : [];

    $defaults = self::businessSiteSettingsDefaults();
    $rawEntries = [];
    $sites = [];

    foreach ($refsByKey as $ref => [$ownerUUID, $siteId]) {
      $siteKey = $siteKeys[$ref];
      $siteHash = $siteHashes[$siteKey] ?? [];
      if ($siteHash === []) {
        $this->removeBusinessSiteAssociation($businessId, $ref);
        continue;
      }

      $siteSettings = $settingsHashes[$settingsKeys[$ref]] ?? [];
      $rawEntries[] = [
        'ref' => $ref,
        'site_owner_uuid' => $ownerUUID,
        'site_id' => $siteId,
        'site_hash' => $siteHash,
        'settings' => $siteSettings,
      ];

      $ownership = $this->businessSiteOwnershipMeta($actorUUID, $ownerUUID, $businessOwnerUUID, $siteHash);
      $sites[] = [
        'site_owner_uuid' => $ownerUUID,
        'site_id'         => $siteId,
        'site_name'       => (string) ($siteHash['site_name'] ?? ''),
        'site_color'      => (string) ($siteHash['site_color'] ?? ''),
        'ownership_scope' => $ownership['ownership_scope'],
        'ownership_label' => $ownership['ownership_label'],
        'settings'        => array_merge($defaults, $siteSettings),
        'site_data'       => $siteHash,
      ];
    }

    BusinessWorkspaceCache::putSitesRaw($businessId, [
      'business_owner_uuid' => $businessOwnerUUID,
      'business_name' => (string) ($business['name'] ?? ''),
      'entries' => $rawEntries,
    ]);

    return $this->ok('Org sites retrieved.', [
      'sites' => $sites,
      'business_id' => $businessId,
      'business' => [
        'business_id' => $businessId,
        'name' => (string) ($business['name'] ?? ''),
        'owner_uuid' => $businessOwnerUUID,
        'abbrev' => BusinessNav::abbreviationForName((string) ($business['name'] ?? '')),
      ],
    ]);
  }

  /**
   * @return array{budget_type: string, budget_amount: string, budget_start: string, budget_end: string, warn_threshold: string, critical_threshold: string, site_status: string, primary_manager_uuid: string, target_headcount: string, target_utilization: string, target_ot_ratio: string, tags: string}
   */
  private static function businessSiteSettingsDefaults(): array
  {
    return [
      'budget_type' => 'annual', 'budget_amount' => '', 'budget_start' => '',
      'budget_end' => '', 'warn_threshold' => '80', 'critical_threshold' => '95',
      'site_status' => 'active', 'primary_manager_uuid' => '',
      'target_headcount' => '', 'target_utilization' => '', 'target_ot_ratio' => '', 'tags' => '',
    ];
  }

  /**
   * @param array{
   *   business_owner_uuid: string,
   *   business_name: string,
   *   entries: list<array{
   *     ref: string,
   *     site_owner_uuid: string,
   *     site_id: string,
   *     site_hash: array<string, string>,
   *     settings: array<string, string>
   *   }>
   * } $cachedRaw
   * @return array<string, mixed>
   */
  private function assembleSitesResponse(string $actorUUID, string $businessId, array $cachedRaw): array
  {
    $defaults = self::businessSiteSettingsDefaults();
    $businessOwnerUUID = $cachedRaw['business_owner_uuid'];
    $businessName = $cachedRaw['business_name'];
    $entries = $cachedRaw['entries'];
    $sites = [];

    foreach ($entries as $entry) {
      $ownerUUID = $entry['site_owner_uuid'];
      $siteId = $entry['site_id'];
      $siteHash = $entry['site_hash'];
      if ($siteHash === []) {
        continue;
      }

      $siteSettings = $entry['settings'];
      $ownership = $this->businessSiteOwnershipMeta($actorUUID, $ownerUUID, $businessOwnerUUID, $siteHash);
      $sites[] = [
        'site_owner_uuid' => $ownerUUID,
        'site_id' => $siteId,
        'site_name' => (string) ($siteHash['site_name'] ?? ''),
        'site_color' => (string) ($siteHash['site_color'] ?? ''),
        'ownership_scope' => $ownership['ownership_scope'],
        'ownership_label' => $ownership['ownership_label'],
        'settings' => array_merge($defaults, $siteSettings),
        'site_data' => $siteHash,
      ];
    }

    return [
      'sites' => $sites,
      'business_id' => $businessId,
      'business' => [
        'business_id' => $businessId,
        'name' => $businessName,
        'owner_uuid' => $businessOwnerUUID,
        'abbrev' => BusinessNav::abbreviationForName($businessName),
      ],
    ];
  }

  /**
   * Create a site owned by the actor and link it to the business workspace.
   *
   * @param array<string, mixed> $input
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function createBusinessSite(string $actorUUID, string $businessId, array $input): array
  {
    if (!$this->canWriteBusinessSites($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_CREATE_SITES_FOR_THIS_BUSI'));
    }

    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    $siteName = is_scalar($input['site_name'] ?? null) ? trim((string) $input['site_name']) : '';
    if ($siteName === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_NAME_IS_REQUIRED'));
    }

    $data = [
      'site_name' => $siteName,
      'wage' => is_scalar($input['wage'] ?? null) ? (string) $input['wage'] : '0',
      'living_out_allowance' => is_scalar($input['living_out_allowance'] ?? null) ? (string) $input['living_out_allowance'] : '0',
      'travel_hours' => is_scalar($input['travel_hours'] ?? null) ? (string) $input['travel_hours'] : '0',
      'province' => is_scalar($input['province'] ?? null) ? (string) $input['province'] : 'AB',
      'status' => SiteStatus::ACTIVE->value,
    ];

    $siteId = (new SitesService())->create($actorUUID, $data);
    if ($siteId === null || $siteId === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_FAILED_TO_CREATE_BUSINESS_SITE'));
    }

    $linkResult = $this->linkSite($actorUUID, $businessId, $actorUUID, $siteId);
    if (!$linkResult['success']) {
      return $linkResult;
    }

    $this->applyBusinessSiteOwnership(
      $actorUUID,
      $siteId,
      self::BUSINESS_SITE_OWNERSHIP_BUSINESS,
      $businessId,
    );

    return $this->ok('Business site created.', [
      'business_id' => $businessId,
      'site_owner_uuid' => $actorUUID,
      'site_id' => $siteId,
      'ownership_scope' => self::BUSINESS_SITE_OWNERSHIP_BUSINESS,
    ]);
  }

  /**
   * Remove a site association from the business workspace.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function unlinkBusinessSite(string $actorUUID, string $businessId, string $siteOwnerUUID, string $siteId): array
  {
    if (!$this->canWriteBusinessSites($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_UNLINK_SITES_FOR_THIS_BUSI'));
    }

    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    $siteRef = InputSanitizer::sanitizeString($siteOwnerUUID) . ':' . InputSanitizer::sanitizeString($siteId);
    if (!Database::sismember(Keys::BUSINESS_SITE . ':' . $businessId, $siteRef)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_IS_NOT_LINKED_TO_THIS_BUSINESS'));
    }

    $this->removeBusinessSiteAssociation($businessId, $siteRef);

    $siteKey = Keys::SITE . ':' . $siteOwnerUUID . ':' . $siteId;
    $storedBusinessId = (string) Database::hget($siteKey, 'business_id');
    if ($storedBusinessId === $businessId) {
      $this->clearBusinessSiteOwnership($siteOwnerUUID, $siteId);
    }

    $this->appendAuditEvent($businessId, 'site.unlinked', $actorUUID, [
      'site_owner_uuid' => $siteOwnerUUID,
      'site_id' => $siteId,
    ]);

    return $this->ok('Site unlinked from business.', [
      'business_id' => $businessId,
      'site_owner_uuid' => $siteOwnerUUID,
      'site_id' => $siteId,
    ]);
  }

  /**
   * Load site + business planning data for the business sites editor dialog.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function getBusinessSiteEditorData(string $actorUUID, string $businessId, string $siteOwnerUUID, string $siteId): array
  {
    if (!$this->canReadBusinessSites($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_VIEW_SITES_FOR_THIS_BUSINE'));
    }

    $siteOwnerUUID = trim(InputSanitizer::sanitizeString($siteOwnerUUID));
    $siteId = trim(InputSanitizer::sanitizeString($siteId));
    if ($siteOwnerUUID === '' || $siteId === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_REFERENCE_IS_REQUIRED'));
    }

    $siteRef = $siteOwnerUUID . ':' . $siteId;
    if (!Database::sismember(Keys::BUSINESS_SITE . ':' . $businessId, $siteRef)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_IS_NOT_LINKED_TO_THIS_BUSINESS'));
    }

    $siteKey = Keys::SITE . ':' . $siteOwnerUUID . ':' . $siteId;
    if (!Database::exists($siteKey)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_NOT_FOUND'));
    }

    $siteHash = Database::hgetall($siteKey);
    $siteHash['id'] = $siteId;

    $canUsePlanning = $this->canUseBusinessPlanning($businessId, $actorUUID);
    $settingsResult = $canUsePlanning
      ? $this->getBusinessSiteSettings($actorUUID, $businessId, $siteOwnerUUID, $siteId)
      : $this->fail(Strings::i18n('SITES_BUSINESS_PLANNING_LOCKED'));
    $settings = $settingsResult['success']
      ? (is_array($settingsResult['data']['settings'] ?? null) ? $settingsResult['data']['settings'] : [])
      : [];

    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    $businessOwnerUUID = (string) ($business['owner_uuid'] ?? '');
    $ownership = $this->businessSiteOwnershipMeta($actorUUID, $siteOwnerUUID, $businessOwnerUUID, $siteHash);

    return $this->ok('Business site editor data retrieved.', [
      'site' => $siteHash,
      'settings' => $settings,
      'site_owner_uuid' => $siteOwnerUUID,
      'site_id' => $siteId,
      'ownership_scope' => $ownership['ownership_scope'],
      'ownership_label' => $ownership['ownership_label'],
      'business' => [
        'business_id' => $businessId,
        'name' => (string) ($business['name'] ?? ''),
        'owner_uuid' => $businessOwnerUUID,
      ],
      'can_write_site' => $this->canMutateSitesForOwner($actorUUID, $siteOwnerUUID),
      'can_view_planning' => $canUsePlanning,
      'can_write_planning' => $canUsePlanning,
    ]);
  }

  /**
   * Drop a business site link and its per-business settings row.
   */
  private function removeBusinessSiteAssociation(string $businessId, string $siteRef): void
  {
    $businessId = trim($businessId);
    $siteRef = trim($siteRef);
    if ($businessId === '' || $siteRef === '') {
      return;
    }

    Database::srem(Keys::BUSINESS_SITE . ':' . $businessId, $siteRef);
    Database::unlink(Keys::BUSINESS_SITE_SETTINGS . ':' . $businessId . ':' . $siteRef);
    BusinessWorkspaceCache::invalidate($businessId);
  }

  /**
   * Remove business site links after the underlying site record is deleted.
   *
   * @return int Number of business associations removed
   */
  public function purgeDeletedSiteFromAllBusinesses(string $siteOwnerUUID, string $siteId): int
  {
    $siteOwnerUUID = trim($siteOwnerUUID);
    $siteId = trim($siteId);
    if ($siteOwnerUUID === '' || $siteId === '') {
      return 0;
    }

    $siteRef = $siteOwnerUUID . ':' . $siteId;
    $purged = 0;

    foreach (Database::scanKeys(Keys::BUSINESS_SITE . ':*') as $businessSiteKey) {
      if (!Database::sismember($businessSiteKey, $siteRef)) {
        continue;
      }

      $prefix = Keys::BUSINESS_SITE . ':';
      if (!str_starts_with($businessSiteKey, $prefix)) {
        continue;
      }

      $businessId = substr($businessSiteKey, strlen($prefix));
      if ($businessId === '') {
        continue;
      }

      $this->removeBusinessSiteAssociation($businessId, $siteRef);
      ++$purged;
    }

    return $purged;
  }

  /**
   * Drop stale business workspace caches after a site status/archive mutation.
   *
   * Linked sites remain associated with the business; only cached site hashes
   * and financial summaries need to be rebuilt.
   *
   * @return int Number of business workspaces whose cache was invalidated
   */
  public function invalidateLinkedBusinessCachesForSite(string $siteOwnerUUID, string $siteId): int
  {
    $siteOwnerUUID = trim($siteOwnerUUID);
    $siteId = trim($siteId);
    if ($siteOwnerUUID === '' || $siteId === '') {
      return 0;
    }

    $siteRef = $siteOwnerUUID . ':' . $siteId;
    $invalidated = 0;

    foreach (Database::scanKeys(Keys::BUSINESS_SITE . ':*') as $businessSiteKey) {
      if (!Database::sismember($businessSiteKey, $siteRef)) {
        continue;
      }

      $prefix = Keys::BUSINESS_SITE . ':';
      if (!str_starts_with($businessSiteKey, $prefix)) {
        continue;
      }

      $businessId = substr($businessSiteKey, strlen($prefix));
      if ($businessId === '') {
        continue;
      }

      BusinessWorkspaceCache::invalidate($businessId);
      ++$invalidated;
    }

    return $invalidated;
  }

  /**
   * Link a site to a business workspace and mark it as a linked personal site.
   *
   * @param string $actorUUID     Authenticated actor UUID.
   * @param string $businessId         Business ID.
   * @param string $siteOwnerUUID UUID of the site owner.
   * @param string $siteId        Site ID to link.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function linkSite(string $actorUUID, string $businessId, string $siteOwnerUUID, string $siteId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canWriteBusinessSites($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_LINK_SITES_FOR_THIS_BUSINE'));
    }

    $siteKey = Keys::SITE . ':' . $siteOwnerUUID . ':' . $siteId;
    if (!Database::exists($siteKey)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_NOT_FOUND'));
    }

    $siteRef = $siteOwnerUUID . ':' . $siteId;
    Database::sadd(Keys::BUSINESS_SITE . ':' . $businessId, $siteRef);
    $this->applyBusinessSiteOwnership(
      $siteOwnerUUID,
      $siteId,
      self::BUSINESS_SITE_OWNERSHIP_LINKED,
      $businessId,
    );

    $this->appendAuditEvent($businessId, 'site.linked', $actorUUID, [
      'site_owner_uuid' => $siteOwnerUUID,
      'site_id' => $siteId,
    ]);

    // Site links change which work entries match the org, so cached member
    // financial summaries are stale.
    BusinessWorkspaceCache::invalidate($businessId);

    return $this->ok('Site linked to business.', [
      'business_id' => $businessId,
      'site_owner_uuid' => $siteOwnerUUID,
      'site_id' => $siteId,
    ]);
  }

  /**
   * Build the merged site catalog used by the calendar work-entry dialog.
   *
   * Personal sites are returned using their stored name. Business-link metadata
   * stays available separately so consumers can badge it without changing the
   * selectable site name.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function buildCalendarSiteCatalog(string $actorUUID): array
  {
    $actorUUID = trim($actorUUID);
    if ($actorUUID === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_INVALID_USER_CONTEXT'));
    }

    $linkedRefs = [];
    $businessAbbrevByRef = [];
    $businessIds = Database::smembers(Keys::BUSINESS_USER . ':' . $actorUUID);

    foreach ($businessIds as $businessId) {
      $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
      if ([] === $business) {
        continue;
      }

      $abbrev = BusinessNav::abbreviationForName((string) ($business['name'] ?? ''));
      $siteRefs = Database::smembers(Keys::BUSINESS_SITE . ':' . $businessId);
      foreach ($siteRefs as $ref) {
        $parts = explode(':', (string) $ref, 2);
        if (count($parts) !== 2 || $parts[0] !== $actorUUID) {
          continue;
        }

        $linkedRefs[(string) $ref] = true;
        if (!isset($businessAbbrevByRef[(string) $ref])) {
          $businessAbbrevByRef[(string) $ref] = $abbrev;
        }
      }
    }

    $sites = [];
    foreach (Sites::getSites($actorUUID, SiteStatus::ACTIVE->value) as $siteId => $siteData) {
      $siteId = (string) $siteId;
      $siteName = trim((string) ($siteData['site_name'] ?? ''));
      if ($siteName === '') {
        continue;
      }

      $siteRef = $actorUUID . ':' . $siteId;
      $isBusinessLinked = isset($linkedRefs[$siteRef]);
      $abbrev = $businessAbbrevByRef[$siteRef] ?? '';

      $sites[] = [
        'site_id' => $siteId,
        'site_name' => $siteName,
        'wage' => (string) ($siteData['wage'] ?? '0'),
        'scope' => $isBusinessLinked ? 'business' : 'personal',
        'business_abbrev' => $isBusinessLinked ? $abbrev : '',
        'display_name' => $siteName,
      ];
    }

    usort($sites, static function (array $a, array $b): int {
      $aScope = $a['scope'];
      $bScope = $b['scope'];
      if ($aScope !== $bScope) {
        return $aScope === 'personal' ? -1 : 1;
      }

      return strcasecmp($a['display_name'], $b['display_name']);
    });

    return $this->ok('Calendar site catalog retrieved.', ['sites' => $sites]);
  }

  /**
   * Returns org planning context for a site owned by the current user.
   *
   * Searches all orgs the actor belongs to for one where (a) this site is
   * registered and (b) the actor can use Business Planning.
   * Used by the Sites page to surface per-site org planning controls.
   *
   * @return array{org_id: string, org_name: string, owner_uuid: string, can_view_planning: bool, can_write_planning: bool, settings: array<string, mixed>}|null
   */
  public function getOrgContextForSite(string $actorUUID, string $siteId): ?array
  {
    $siteRef = $actorUUID . ':' . $siteId;
    $businessIds  = Database::smembers(Keys::BUSINESS_USER . ':' . $actorUUID);

    Lens::add('getOrgContextForSite', [
      'actor_uuid' => $actorUUID,
      'site_id'    => $siteId,
      'site_ref'   => $siteRef,
      'org_count'  => count($businessIds),
      'org_ids'    => $businessIds,
    ]);

    foreach ($businessIds as $businessId) {
      $isMember = Database::sismember(Keys::BUSINESS_SITE . ':' . $businessId, $siteRef);
      Lens::add('getOrgContextForSite:check', [
        'org_id'    => $businessId,
        'site_ref'  => $siteRef,
        'is_member' => $isMember,
      ]);
      if ($isMember === 0) {
        continue;
      }

      $business  = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
      $isOrgOwner = (string) ($business['owner_uuid'] ?? '') === $actorUUID;
      $connection = $isOrgOwner ? ['role' => 'owner'] : $this->connection($businessId, $actorUUID);
      $role = strtolower(trim((string) ($connection['role'] ?? '')));

      Lens::add('getOrgContextForSite:role', [
        'org_id'      => $businessId,
        'is_org_owner' => $isOrgOwner,
        'role'        => $role,
      ]);

      $canUsePlanning = $this->canUseBusinessPlanning($businessId, $actorUUID);
      if (!$canUsePlanning) {
        continue;
      }

      $defaults = [
        'budget_type'        => 'annual',
        'budget_amount'      => '',
        'budget_start'       => '',
        'budget_end'         => '',
        'warn_threshold'     => '80',
        'critical_threshold' => '95',
        'site_status'        => 'active',
        'target_headcount'   => '',
        'target_utilization' => '',
        'target_ot_ratio'    => '',
        'manager_uuid'       => '',
        'tags'               => '',
      ];
      $stored   = Database::hgetall(Keys::BUSINESS_SITE_SETTINGS . ':' . $businessId . ':' . $actorUUID . ':' . $siteId);
      $settings = array_merge($defaults, array_map('strval', $stored));

      Lens::add('getOrgContextForSite:found', [
        'org_id'   => $businessId,
        'org_name' => (string) ($business['name'] ?? ''),
        'settings' => $settings,
      ]);

      return [
        'org_id'     => $businessId,
        'org_name'   => (string) ($business['name'] ?? ''),
        'owner_uuid' => $actorUUID,
        'can_view_planning' => true,
        'can_write_planning' => true,
        'settings'   => $settings,
      ];
    }

    Lens::add('getOrgContextForSite:not_found', ['actor_uuid' => $actorUUID, 'site_id' => $siteId]);
    return null;
  }

  /**
   * Reassign selected members' work entries to a business-linked site.
   *
   * By default only entries that do not match the business site link set are
   * migrated (for example seeded EIC-HQ keys). Coordinators may pass
   * apply_scope=all to move every entry not already on the target site.
   *
   * @param list<string> $memberUUIDs
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function applyWorkSiteToMembers(
    string $actorUUID,
    string $businessId,
    array $memberUUIDs,
    string $siteOwnerUUID,
    string $siteId,
    string $applyScope = 'unlinked',
  ): array {
    if (!$this->canWriteBusinessSites($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_APPLY_WORK_SITES_FOR_THIS'));
    }

    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    $businessId = trim($businessId);
    $siteOwnerUUID = trim($siteOwnerUUID);
    $siteId = trim($siteId);
    if ($businessId === '' || $siteOwnerUUID === '' || $siteId === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_SITE_OWNER_AND_SITE_ARE_REQUIRED'));
    }

    $siteRef = $siteOwnerUUID . ':' . $siteId;
    if (!Database::sismember(Keys::BUSINESS_SITE . ':' . $businessId, $siteRef)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_IS_NOT_LINKED_TO_THIS_BUSINESS'));
    }

    $siteKey = Keys::SITE . ':' . $siteOwnerUUID . ':' . $siteId;
    if (!Database::exists($siteKey)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_TARGET_SITE_WAS_NOT_FOUND'));
    }

    $siteHash = Database::hgetall($siteKey);
    $targetSiteName = trim((string) ($siteHash['site_name'] ?? ''));
    $targetSiteColor = strtoupper(trim((string) ($siteHash['site_color'] ?? '')));
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $targetSiteColor)) {
      $targetSiteColor = '';
    }

    $normalizedScope = strtolower(trim($applyScope)) === 'all' ? 'all' : 'unlinked';
    $memberUUIDs = array_values(array_unique(array_filter(array_map(
      static fn (string $uuid): string => trim($uuid),
      $memberUUIDs,
    ), static fn (string $uuid): bool => $uuid !== '')));

    if ($memberUUIDs === []) {
      return $this->fail(Strings::i18n('BUSINESSES_API_SELECT_AT_LEAST_ONE_MEMBER'));
    }

    $linkContext = BusinessSiteLinkResolver::buildContext($businessId);
    $migrated = 0;
    $skipped = 0;
    $locked = 0;
    $conflicts = 0;
    $membersUpdated = 0;
    $membersProcessed = 0;

    foreach ($memberUUIDs as $memberUUID) {
      $connectionKey = Keys::BUSINESS_CONNECTION . ':' . $businessId . ':' . $memberUUID;
      if (!Database::exists($connectionKey)) {
        continue;
      }

      $connectionStatus = strtolower(trim((string) Database::hget($connectionKey, 'status')));
      if ($connectionStatus !== '' && $connectionStatus !== 'active') {
        continue;
      }

      ++$membersProcessed;
      $memberMigrated = 0;
      $workKeys = Database::scanKeys(WorkEntryRepository::activePatternForUser($memberUUID));

      foreach ($workKeys as $oldKey) {
        $parts = explode(':', (string) $oldKey);
        if (count($parts) < 4 || $parts[0] !== Keys::WORK || $parts[1] === 'archived') {
          continue;
        }

        $date = $parts[2];
        $sourceSiteId = $parts[3];
        if ($date === '' || $sourceSiteId === '') {
          continue;
        }

        if ($sourceSiteId === $siteId) {
          ++$skipped;
          continue;
        }

        $entry = Database::hgetall((string) $oldKey);
        $entrySiteName = (string) ($entry['site_name'] ?? $entry['n'] ?? '');
        if ($normalizedScope === 'unlinked') {
          $matchStrategy = BusinessSiteLinkResolver::resolveMatchStrategy(
            $linkContext,
            $sourceSiteId,
            (string) ($entry['site_owner_uuid'] ?? $memberUUID),
            $entrySiteName,
          );
          if ($matchStrategy !== 'no_match') {
            ++$skipped;
            continue;
          }
        }

        // Site reassignment for org reporting does not edit hours/wages; coordinators
        // must be able to relink historical entries to business-linked sites.
        $newKey = WorkEntryRepository::activeKey($memberUUID, $date, $siteId);
        if ($newKey === $oldKey) {
          ++$skipped;
          continue;
        }

        if (Database::exists($newKey)) {
          ++$conflicts;
          continue;
        }

        $patch = [
          'site_id' => $siteId,
          'site_name' => $targetSiteName,
          'site_owner_uuid' => $siteOwnerUUID,
        ];
        if ($targetSiteColor !== '') {
          $patch['site_color'] = $targetSiteColor;
        }

        Database::hset((string) $oldKey, $patch);
        if (!Database::rename((string) $oldKey, $newKey)) {
          ++$skipped;
          continue;
        }

        ++$migrated;
        ++$memberMigrated;
      }

      if ($memberMigrated > 0) {
        ++$membersUpdated;
        EarningsCacheService::invalidateForUser($memberUUID);
      }
    }

    if ($membersProcessed === 0) {
      return $this->fail(Strings::i18n('BUSINESSES_API_NO_ACTIVE_MEMBERS_WERE_ELIGIBLE_FOR_WORK_SITE_ASSIGNMENT'));
    }

    BusinessWorkspaceCache::invalidate($businessId);

    return $this->ok('Work site applied to member entries.', [
      'business_id' => $businessId,
      'site_owner_uuid' => $siteOwnerUUID,
      'site_id' => $siteId,
      'site_name' => $targetSiteName,
      'apply_scope' => $normalizedScope,
      'members_requested' => count($memberUUIDs),
      'members_processed' => $membersProcessed,
      'members_updated' => $membersUpdated,
      'entries_migrated' => $migrated,
      'entries_skipped' => $skipped,
      'entries_locked' => $locked,
      'entries_conflicted' => $conflicts,
    ]);
  }

  /**
   * Returns org-only site settings for a specific site within an business.
   *
   * Key: business:site_settings:{orgId}:{siteOwnerUUID}:{siteId}
   * Access: PayCal Business tier, owner, or coordinator/manager role.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function getBusinessSiteSettings(string $actorUUID, string $businessId, string $siteOwnerUUID, string $siteId): array
  {
    if (!$this->canReadBusinessSites($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_VIEW_SITE_SETTINGS_FOR_THI'));
    }

    $siteRef = InputSanitizer::sanitizeString($siteOwnerUUID) . ':' . InputSanitizer::sanitizeString($siteId);
    if (!Database::sismember(Keys::BUSINESS_SITE . ':' . $businessId, $siteRef)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_IS_NOT_LINKED_TO_THIS_BUSINESS'));
    }

    if (!$this->canUseBusinessPlanning($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('SITES_BUSINESS_PLANNING_LOCKED'));
    }

    $key      = Keys::BUSINESS_SITE_SETTINGS . ':' . $businessId . ':' . $siteRef;
    $settings = Database::hgetall($key);
    $defaults = [
      'budget_type'         => 'annual',
      'budget_amount'       => '',
      'budget_start'        => '',
      'budget_end'          => '',
      'warn_threshold'      => '80',
      'critical_threshold'  => '95',
      'site_status'         => 'active',
      'primary_manager_uuid' => '',
      'target_headcount'    => '',
      'target_utilization'  => '',
      'target_ot_ratio'     => '',
      'tags'                => '',
    ];

    return $this->ok('Org site settings retrieved.', ['settings' => array_merge($defaults, $settings)]);
  }

  /**
   * Persists org-only site settings for a specific site within an business.
   *
   * The site must already be linked to the business and the actor must be
   * eligible for Business Planning. All values are strings; numeric fields are
   * range-clamped server-side.
   *
   * @param  array<string, mixed> $input
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function updateBusinessSiteSettings(string $actorUUID, string $businessId, string $siteOwnerUUID, string $siteId, array $input): array
  {
    if (!$this->canReadBusinessSites($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_VIEW_SITE_SETTINGS_FOR_THI'));
    }

    $siteRef = InputSanitizer::sanitizeString($siteOwnerUUID) . ':' . InputSanitizer::sanitizeString($siteId);
    if (!Database::sismember(Keys::BUSINESS_SITE . ':' . $businessId, $siteRef)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_IS_NOT_LINKED_TO_THIS_BUSINESS'));
    }

    if (!$this->canUseBusinessPlanning($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('SITES_BUSINESS_PLANNING_LOCKED'));
    }

    $normalized = [];

    // Budget amount: non-negative float
    if (array_key_exists('budget_amount', $input)) {
      $v = is_scalar($input['budget_amount']) ? (float) $input['budget_amount'] : 0.0;
      $normalized['budget_amount'] = $v > 0 ? (string) round($v, 2) : '';
    }

    // Budget type
    if (array_key_exists('budget_type', $input)) {
      $v = is_scalar($input['budget_type']) ? strtolower(trim((string) $input['budget_type'])) : 'annual';
      $normalized['budget_type'] = in_array($v, ['monthly', 'quarterly', 'annual'], true) ? $v : 'annual';
    }

    // Date fields: YYYY-MM-DD format
    foreach (['budget_start', 'budget_end'] as $df) {
      if (array_key_exists($df, $input)) {
        $v = is_scalar($input[$df]) ? trim((string) $input[$df]) : '';
        $normalized[$df] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
      }
    }

    // Threshold percentages: 1–100
    foreach (['warn_threshold', 'critical_threshold'] as $tf) {
      if (array_key_exists($tf, $input)) {
        $v = is_scalar($input[$tf]) ? (int) $input[$tf] : 0;
        $normalized[$tf] = (string) max(1, min(100, $v));
      }
    }

    // Site status
    if (array_key_exists('site_status', $input)) {
      $v = is_scalar($input['site_status']) ? strtolower(trim((string) $input['site_status'])) : 'active';
      $valid = ['planning', 'active', 'maintenance', 'complete', 'archived'];
      $normalized['site_status'] = in_array($v, $valid, true) ? $v : 'active';
    }

    // Primary manager UUID (must be an org member)
    if (array_key_exists('primary_manager_uuid', $input)) {
      $v = is_scalar($input['primary_manager_uuid']) ? trim((string) $input['primary_manager_uuid']) : '';
      $normalized['primary_manager_uuid'] = $v;
    }

    // Numeric targets
    foreach (['target_headcount' => [0, 9999], 'target_utilization' => [0, 100], 'target_ot_ratio' => [0, 100]] as $field => [$min, $max]) {
      if (array_key_exists($field, $input)) {
        $v = is_scalar($input[$field]) ? (float) $input[$field] : 0.0;
        $normalized[$field] = $v > 0 ? (string) round(max($min, min($max, $v)), 2) : '';
      }
    }

    // Tags: CSV, max 200 chars
    if (array_key_exists('tags', $input)) {
      $v = is_scalar($input['tags']) ? substr(trim((string) $input['tags']), 0, 200) : '';
      $normalized['tags'] = $v;
    }

    if ([] === $normalized) {
      return $this->fail(Strings::i18n('BUSINESSES_API_NO_VALID_FIELDS_PROVIDED'));
    }

    $key = Keys::BUSINESS_SITE_SETTINGS . ':' . $businessId . ':' . $siteRef;
    Database::hset($key, $normalized);

    $this->appendAuditEvent($businessId, 'site_settings.updated', $actorUUID, [
      'site_owner_uuid' => $siteOwnerUUID,
      'site_id'         => $siteId,
      'fields_updated'  => array_keys($normalized),
    ]);

    BusinessWorkspaceCache::invalidateSiteSettings($businessId);

    return $this->ok('Site settings updated.', ['settings' => $normalized]);
  }

  /**
   * Restore an archived linked site and any archived work entries for it.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function restoreBusinessSite(string $actorUUID, string $businessId, string $siteOwnerUUID, string $siteId): array
  {
    if (!$this->canWriteBusinessSites($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_UPDATE_SITE_SETTINGS_FOR_T'));
    }

    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    $siteOwnerUUID = trim(InputSanitizer::sanitizeString($siteOwnerUUID));
    $siteId = trim(InputSanitizer::sanitizeString($siteId));
    if ($siteOwnerUUID === '' || $siteId === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_REFERENCE_IS_REQUIRED'));
    }

    if (!$this->canMutateSitesForOwner($actorUUID, $siteOwnerUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_UPDATE_SITE_SETTINGS_FOR_T'));
    }

    $siteRef = $siteOwnerUUID . ':' . $siteId;
    if (!Database::sismember(Keys::BUSINESS_SITE . ':' . $businessId, $siteRef)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_IS_NOT_LINKED_TO_THIS_BUSINESS'));
    }

    $siteKey = Keys::SITE . ':' . $siteOwnerUUID . ':' . $siteId;
    $site = Database::hgetall($siteKey);
    if ($site === []) {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_NOT_FOUND'));
    }

    $site['status'] = SiteStatus::ACTIVE->value;
    if (!(new SitesService())->updateSingle($siteOwnerUUID, $siteId, $site)) {
      return $this->fail(Strings::i18n('SITES_RESTORE_FAILED'));
    }

    Database::hset(Keys::BUSINESS_SITE_SETTINGS . ':' . $businessId . ':' . $siteRef, [
      'site_status' => SiteStatus::ACTIVE->value,
    ]);
    BusinessWorkspaceCache::invalidate($businessId);
    $this->appendAuditEvent($businessId, 'site.restored', $actorUUID, [
      'site_owner_uuid' => $siteOwnerUUID,
      'site_id' => $siteId,
    ]);

    return $this->ok(Strings::i18n('SITES_RESTORED'), [
      'business_id' => $businessId,
      'site_owner_uuid' => $siteOwnerUUID,
      'site_id' => $siteId,
    ]);
  }

  /**
   * Archive a business-linked site and keep it attached to the business archive list.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function archiveBusinessSite(string $actorUUID, string $businessId, string $siteOwnerUUID, string $siteId): array
  {
    if (!$this->canWriteBusinessSites($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_UPDATE_SITE_SETTINGS_FOR_T'));
    }

    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    $siteOwnerUUID = trim(InputSanitizer::sanitizeString($siteOwnerUUID));
    $siteId = trim(InputSanitizer::sanitizeString($siteId));
    if ($siteOwnerUUID === '' || $siteId === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_REFERENCE_IS_REQUIRED'));
    }

    if (!$this->canMutateSitesForOwner($actorUUID, $siteOwnerUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_UPDATE_SITE_SETTINGS_FOR_T'));
    }

    $siteRef = $siteOwnerUUID . ':' . $siteId;
    if (!Database::sismember(Keys::BUSINESS_SITE . ':' . $businessId, $siteRef)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_IS_NOT_LINKED_TO_THIS_BUSINESS'));
    }

    if (!Database::exists(Keys::SITE . ':' . $siteOwnerUUID . ':' . $siteId)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_NOT_FOUND'));
    }

    $result = (new SitesService())->delete($siteOwnerUUID, $siteId);
    if (!$result['success']) {
      return $this->fail(Strings::i18n('SITES_ERROR_DELETING'), [
        'locked_entries' => (int) $result['locked_entries'],
      ]);
    }

    Database::hset(Keys::BUSINESS_SITE_SETTINGS . ':' . $businessId . ':' . $siteRef, [
      'site_status' => SiteStatus::ARCHIVED->value,
    ]);
    BusinessWorkspaceCache::invalidate($businessId);
    $this->appendAuditEvent($businessId, 'site.archived', $actorUUID, [
      'site_owner_uuid' => $siteOwnerUUID,
      'site_id' => $siteId,
      'archived_count' => (int) $result['archived_count'],
    ]);

    return $this->ok(Strings::i18n('SITES_ARCHIVED_SHORT'), [
      'business_id' => $businessId,
      'site_owner_uuid' => $siteOwnerUUID,
      'site_id' => $siteId,
      'archived_count' => (int) $result['archived_count'],
    ]);
  }

  /**
   * Permanently delete an archived business-linked site.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function permanentDeleteBusinessSite(string $actorUUID, string $businessId, string $siteOwnerUUID, string $siteId): array
  {
    if (!$this->canWriteBusinessSites($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_UPDATE_SITE_SETTINGS_FOR_T'));
    }

    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    $siteOwnerUUID = trim(InputSanitizer::sanitizeString($siteOwnerUUID));
    $siteId = trim(InputSanitizer::sanitizeString($siteId));
    if ($siteOwnerUUID === '' || $siteId === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_REFERENCE_IS_REQUIRED'));
    }

    if (!$this->canMutateSitesForOwner($actorUUID, $siteOwnerUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_UPDATE_SITE_SETTINGS_FOR_T'));
    }

    $siteRef = $siteOwnerUUID . ':' . $siteId;
    if (!Database::sismember(Keys::BUSINESS_SITE . ':' . $businessId, $siteRef)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_IS_NOT_LINKED_TO_THIS_BUSINESS'));
    }

    if (!Database::exists(Keys::SITE . ':' . $siteOwnerUUID . ':' . $siteId)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_SITE_NOT_FOUND'));
    }

    $result = (new SitesService())->permanentDelete($siteOwnerUUID, $siteId);
    if (!$result['success']) {
      return $this->fail(Strings::i18n('SITES_ERROR_DELETING'), [
        'locked_entries' => (int) $result['locked_entries'],
      ]);
    }

    BusinessWorkspaceCache::invalidate($businessId);
    $this->appendAuditEvent($businessId, 'site.permanent_deleted', $actorUUID, [
      'site_owner_uuid' => $siteOwnerUUID,
      'site_id' => $siteId,
      'deleted_work_count' => (int) $result['deleted_work_count'],
    ]);

    return $this->ok(Strings::i18n('SITES_PERMANENTLY_DELETED_SHORT'), [
      'business_id' => $businessId,
      'site_owner_uuid' => $siteOwnerUUID,
      'site_id' => $siteId,
      'deleted_work_count' => (int) $result['deleted_work_count'],
    ]);
  }

  /**
   * Handles canMutateSitesForOwner operation.
   */
  public function canMutateSitesForOwner(string $actorUUID, string $ownerUUID): bool
  {
    $actorUUID = trim(InputSanitizer::sanitizeString($actorUUID));
    $ownerUUID = trim(InputSanitizer::sanitizeString($ownerUUID));

    if ('' === $actorUUID || '' === $ownerUUID) {
      return false;
    }

    if ($actorUUID === $ownerUUID) {
      return true;
    }

    $actorOrgs = Database::smembers(Keys::BUSINESS_USER . ':' . $actorUUID);
    $ownerOrgs = Database::smembers(Keys::BUSINESS_USER . ':' . $ownerUUID);
    if ([] === $actorOrgs || [] === $ownerOrgs) {
      return false;
    }

    $ownerOrgSet = array_fill_keys($ownerOrgs, true);
    foreach ($actorOrgs as $businessId) {
      if (!isset($ownerOrgSet[$businessId])) {
        continue;
      }

      if ($this->canManageBusiness($businessId, $actorUUID)) {
        return true;
      }

      $connection = $this->connection($businessId, $actorUUID);
      if ([] === $connection || ($connection['status'] ?? '') !== 'active') {
        continue;
      }

      $scopeSet = $this->scopeMap((string) ($connection['scopes'] ?? ''));
      if (isset($scopeSet['sites.write']) || isset($scopeSet['all'])) {
        return true;
      }
    }

    return false;
  }

  /**
   * Handles canMutateWorkForOwner operation.
   */
  public function canMutateWorkForOwner(string $actorUUID, string $ownerUUID, string $businessId = ''): bool
  {
    $actorUUID = trim(InputSanitizer::sanitizeString($actorUUID));
    $ownerUUID = trim(InputSanitizer::sanitizeString($ownerUUID));
    $businessId = trim(InputSanitizer::sanitizeString($businessId));

    if ('' === $actorUUID || '' === $ownerUUID) {
      return false;
    }

    if ($businessId === '') {
      // Without explicit business context, only self-owned work may be changed.
      return $actorUUID === $ownerUUID;
    }

    $actorConnection = $this->connection($businessId, $actorUUID);
    $ownerConnection = $this->connection($businessId, $ownerUUID);

    if ([] === $actorConnection || [] === $ownerConnection) {
      return false;
    }

    if (($actorConnection['status'] ?? '') !== self::MEMBERSHIP_STATE_ACTIVE
      || ($ownerConnection['status'] ?? '') !== self::MEMBERSHIP_STATE_ACTIVE) {
      return false;
    }

    if ($this->canManageBusiness($businessId, $actorUUID)) {
      return true;
    }

    $scopeSet = $this->scopeMap((string) ($actorConnection['scopes'] ?? ''));

    if (isset($scopeSet['all'])) {
      return true;
    }

    if (!isset($scopeSet['work.write'])) {
      return false;
    }

    if (isset($scopeSet['work.scope.business'])) {
      return true;
    }

    if (isset($scopeSet['work.scope.self'])) {
      return $actorUUID === $ownerUUID;
    }

    return false;
  }

  /**
   * Return businesses, sites, and match candidates for the user's discovery view.
   *
   * Aggregates personal business, shared business memberships, linked sites, and any
   * discoverable businesses matching the user's verified domain.
   *
   * @param string $userUUID Authenticated user UUID.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function discoveryForUser(string $userUUID): array
  {
    $businessIds = Database::smembers(Keys::BUSINESS_USER . ':' . $userUUID);
    if (!$this->canAccessPremiumFeatures($userUUID)) {
      $businessIds = array_values(array_filter($businessIds, function (string $businessId) use ($userUUID): bool {
        $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
        $isBusinessOwner = (string) ($business['owner_uuid'] ?? '') === $userUUID;

        return $this->isSelfBusiness($business, $userUUID) || $isBusinessOwner;
      }));
    }

    $sites = [];
    foreach (Sites::getSites($userUUID, 'all') as $siteId => $siteData) {
      $sites[] = [
        'site_id' => $siteId,
        'site_owner_uuid' => $userUUID,
        'name' => (string) ($siteData['site_name'] ?? ''),
        'business_id' => (string) ($siteData['business_id'] ?? ''),
      ];
    }

    $matchCandidates = [];
    foreach ($businessIds as $businessId) {
      foreach ($sites as $site) {
        if ($site['business_id'] === '') {
          $matchCandidates[] = [
            'business_id' => $businessId,
            'candidate_type' => 'site',
            'candidate_id' => (string) $site['site_id'],
            'reason' => 'user_site_without_business',
          ];
        }
      }
    }

    return $this->ok('Business discovery generated.', [
      'user_businesses' => $businessIds,
      'user_sites' => $sites,
      'match_candidates' => $matchCandidates,
    ]);
  }

  /**
   * Return business settings filtered to the actor's read permissions.
   *
   * Includes pay-period config, domain policy, and encryption metadata. Fields
   * are omitted when the actor lacks the corresponding read scope.
   *
   * @param string $actorUUID Authenticated actor UUID.
   * @param string $businessId     Business ID.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function getBusinessSettings(string $actorUUID, string $businessId): array
  {
    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ([] === $business) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND'));
    }

    $canReadOrgSettings = $this->canReadBusinessSettings($businessId, $actorUUID);
    $canReadPayPeriod = $this->canReadBusinessPayPeriod($businessId, $actorUUID);

    if (!$canReadOrgSettings && !$canReadPayPeriod) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_VIEW_BUSINESS_SETTINGS'));
    }

    $settings = Database::hgetall(Keys::BUSINESS_SETTINGS . ':' . $businessId);
    if ([] === $settings) {
      $settings = [
        'pay_period_length' => '14',
        'pay_frequency' => PayFrequency::BIWEEKLY->value,
        'pay_anchor' => 'Monday',
        'pay_period_start' => '2024-01-01',
        'pay_epoch' => '2024-01-01',
        'editing_grace_days' => (string) UserPreferenceDefaults::DEFAULT_EDITING_GRACE_DAYS,
        'default_wage' => '',
        'timezone' => self::DEFAULT_TIMEZONE,
        'currency' => self::DEFAULT_CURRENCY,
        'enforce_contact_domain' => '0',
        'allowed_contact_domains' => '',
      ];
    }

    if (!$canReadOrgSettings) {
      $payPeriodKeys = [
        'pay_frequency',
        'pay_anchor',
        'pay_period_start',
        'pay_period_length',
        'pay_epoch',
        'editing_grace_days',
      ];
      $settings = array_intersect_key($settings, array_flip($payPeriodKeys));
    }

    $ownerUUID = (string) ($business['owner_uuid'] ?? '');
    $owner = $ownerUUID !== '' ? UserRepository::getByUUID($ownerUUID) : null;
    $ownerConnection = $ownerUUID !== '' ? $this->connection($businessId, $ownerUUID) : [];
    $ownerSince = (string) ($ownerConnection['owner_since'] ?? $ownerConnection['accepted_at'] ?? $ownerConnection['created_at'] ?? '');

    return $this->ok('Business settings retrieved.', [
      'business_id' => $businessId,
      'business' => [
        'name' => (string) ($business['name'] ?? ''),
        'owner_uuid' => (string) ($business['owner_uuid'] ?? ''),
        'owner_name' => $owner instanceof User ? $owner->full_name : '',
        'owner_email' => $owner instanceof User ? $owner->email : '',
        'owner_phone' => $owner instanceof User ? $owner->phone : '',
        'owner_since' => $ownerSince,
        'business_type' => (string) ($business['business_type'] ?? 'shared'),
        'status' => (string) ($business['status'] ?? 'active'),
      ],
      'settings' => $settings,
    ]);
  }

  /** @param array<string, mixed> $settings
   *  @return array{success: bool, message: string, data: array<string, mixed>} */
  public function updateBusinessSettings(string $actorUUID, string $businessId, array $settings): array
  {
    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ([] === $business) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND'));
    }

    $canWriteOrgSettings = $this->canWriteBusinessSettings($businessId, $actorUUID);
    $canWritePayPeriod = $this->canWriteBusinessPayPeriod($businessId, $actorUUID);

    if (!$canWriteOrgSettings && !$canWritePayPeriod) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_UPDATE_BUSINESS_SETTINGS'));
    }

    // Contributors can update pay-period controls without full org-settings write scope.
    if (!$canWriteOrgSettings) {
      $payPeriodKeys = [
        'pay_frequency',
        'pay_anchor',
        'pay_period_start',
        'pay_period_length',
        'editing_grace_days',
      ];
      $settings = array_intersect_key($settings, array_flip($payPeriodKeys));
      if ([] === $settings) {
        return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_UPDATE_BUSINESS_SETTINGS'));
      }
    }

    $existingSettings = Database::hgetall(Keys::BUSINESS_SETTINGS . ':' . $businessId);

    $businessUpdates = [];
    $didRoleUpdate = false;

    $normalized = [];

    $moderationMessage = '';
    if (array_key_exists('name', $settings)) {
      $value = is_scalar($settings['name']) ? trim((string) $settings['name']) : '';
      if ($value === '' || strlen($value) < 2) {
        return $this->fail(Strings::i18n('BUSINESSES_NAME_MIN'));
      }

      $rename = BusinessModerationService::handleRename($businessId, $actorUUID, $value);
      if (!$rename['success']) {
        $cooldown = is_array($rename['cooldown'] ?? null) ? $rename['cooldown'] : [];

        return $this->fail(
          $rename['user_message'] !== '' ? $rename['user_message'] : $rename['message'],
          $cooldown !== [] ? ['rename_cooldown' => $cooldown] : [],
        );
      }

      $businessUpdates = array_merge($businessUpdates, $rename['fields']);
      $moderationMessage = $rename['user_message'];
    }

    if (array_key_exists('business_type', $settings)) {
      $type = $this->normalizeBusinessType($settings['business_type']);
      if ($type === '') {
        return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_TYPE_FIELD_INVALID'));
      }

      $currentType = (string) ($business['business_type'] ?? 'shared');
      if ($type !== $currentType && $type === 'shared' && !$this->canAccessPremiumFeatures($actorUUID)) {
        return $this->premiumSubscriptionRequired();
      }

      $businessUpdates['business_type'] = $type;
    }

    if (array_key_exists('status', $settings)) {
      $status = is_scalar($settings['status']) ? strtolower(trim((string) $settings['status'])) : 'active';
      if (!in_array($status, ['active', 'pending'], true)) {
        return $this->fail(Strings::i18n('BUSINESSES_API_STATUS_FIELD_INVALID'));
      }
      $businessUpdates['status'] = $status;
    }

    if (array_key_exists('role', $settings)) {
      $targetRole = is_scalar($settings['role']) ? strtolower(trim((string) $settings['role'])) : '';
      if ($targetRole !== '') {
        if (!isset(self::VALID_BUSINESS_ROLES[$targetRole])) {
          return $this->fail(Strings::i18n('BUSINESSES_API_ROLE_FIELD_INVALID'));
        }

        $connection = $this->connection($businessId, $actorUUID);
        if ([] === $connection) {
          return $this->fail(Strings::i18n('BUSINESSES_API_MEMBERSHIP_CONNECTION_NOT_FOUND_FOR_CURRENT_USER'));
        }

        $currentRole = strtolower(trim((string) ($connection['role'] ?? '')));

        if ((string) ($business['owner_uuid'] ?? '') === $actorUUID && $targetRole !== 'owner') {
          return $this->fail(Strings::i18n('BUSINESSES_API_CANNOT_CHANGE_OWNER_ROLE'));
        }

        if ($currentRole === 'coordinator' && $targetRole !== 'coordinator') {
          return $this->fail(Strings::i18n('BUSINESSES_API_MANAGERS_CANNOT_DOWNGRADE_OR_CHANGE_THEIR_OWN_ROLE'));
        }

        $scopeCSV = $targetRole === 'owner'
          ? 'all'
          : implode(',', self::ROLE_SCOPE_PRESETS[$targetRole]);

        $this->setConnection($businessId, $actorUUID, [
          'role' => $targetRole,
          'scopes' => $scopeCSV,
          'updated_by' => $actorUUID,
          'role_updated_at' => date('c'),
        ]);
        $didRoleUpdate = true;
      }
    }

    if (array_key_exists('pay_frequency', $settings)) {
      $value = is_scalar($settings['pay_frequency']) ? (string) $settings['pay_frequency'] : PayFrequency::BIWEEKLY->value;
      $allowed = [
        PayFrequency::WEEKLY->value,
        PayFrequency::BIWEEKLY->value,
        PayFrequency::SEMIMONTHLY->value,
        PayFrequency::MONTHLY->value,
      ];
      if (!in_array($value, $allowed, true)) {
        return $this->fail(Strings::i18n('BUSINESSES_API_PAY_FREQUENCY_INVALID'));
      }

      $normalized['pay_frequency'] = $value;
      $normalized['pay_period_length'] = match ($value) {
        PayFrequency::WEEKLY->value => '7',
        PayFrequency::BIWEEKLY->value => '14',
        PayFrequency::SEMIMONTHLY->value => '15',
        PayFrequency::MONTHLY->value => '30',
      };
    }

    if (array_key_exists('pay_period_length', $settings)) {
      $value = is_scalar($settings['pay_period_length']) ? (int) $settings['pay_period_length'] : 14;
      if ($value < 7 || $value > 31) {
        return $this->fail(Strings::i18n('BUSINESSES_API_PAY_PERIOD_LENGTH_MUST_BE_BETWEEN_7_AND_31_DAYS'));
      }
      $normalized['pay_period_length'] = (string) $value;
    }

    if (array_key_exists('default_wage', $settings)) {
      $value = is_scalar($settings['default_wage']) ? (string) $settings['default_wage'] : '';
      $value = trim($value);
      $normalized['default_wage'] = $value;
    }

    if (array_key_exists('timezone', $settings)) {
      $value = is_scalar($settings['timezone']) ? trim((string) $settings['timezone']) : '';
      if (!Timezone::isValid($value)) {
        return $this->fail(Strings::i18n('BUSINESSES_API_TIMEZONE_INVALID'));
      }
      $normalized['timezone'] = $value;
    }

    if (array_key_exists('pay_anchor', $settings)) {
      $value = is_scalar($settings['pay_anchor']) ? trim((string) $settings['pay_anchor']) : 'Monday';
      $allowedAnchors = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
      if (!in_array($value, $allowedAnchors, true)) {
        return $this->fail(Strings::i18n('BUSINESSES_API_PAY_ANCHOR_INVALID'));
      }
      $normalized['pay_anchor'] = $value;
    }

    if (array_key_exists('pay_period_start', $settings)) {
      $value = is_scalar($settings['pay_period_start']) ? trim((string) $settings['pay_period_start']) : '';
      if (!$this->isValidYmdDate($value)) {
        return $this->fail(Strings::i18n('BUSINESSES_API_PAY_PERIOD_START_MUST_BE_A_VALID_YYYY_MM_DD_DATE'));
      }
      $normalized['pay_period_start'] = $value;
      $normalized['pay_epoch'] = $value;
    }

    if (array_key_exists('editing_grace_days', $settings)) {
      $value = is_scalar($settings['editing_grace_days']) ? (int) $settings['editing_grace_days'] : UserPreferenceDefaults::DEFAULT_EDITING_GRACE_DAYS;
      $min = (int) SystemLimits::get('editing_grace_days_min');
      $max = (int) SystemLimits::get('editing_grace_days_max');
      if ($value < $min || $value > $max) {
        return $this->fail(Strings::i18n('BUSINESSES_API_EDITING_GRACE_DAYS_IS_OUTSIDE_THE_ALLOWED_RANGE'));
      }
      $normalized['editing_grace_days'] = (string) $value;
    }

    if (array_key_exists('currency', $settings)) {
      $value = is_scalar($settings['currency']) ? strtoupper(trim((string) $settings['currency'])) : 'CAD';
      if (!Currency::isValid($value)) {
        return $this->fail(Strings::i18n('BUSINESSES_API_CURRENCY_MUST_BE_A_VALID_ISO_4217_CODE'));
      }
      $normalized['currency'] = $value;
    }

    if (array_key_exists('enforce_contact_domain', $settings)) {
      $normalized['enforce_contact_domain'] = $this->isTruthySettingValue($settings['enforce_contact_domain']) ? '1' : '0';
    }

    if (array_key_exists('allowed_contact_domains', $settings)) {
      $domainParse = $this->parseAllowedContactDomainPayload($settings['allowed_contact_domains']);
      if ([] !== $domainParse['invalid']) {
        return $this->fail(Strings::i18n('BUSINESSES_API_CONTACT_DOMAINS_INVALID'), [
          'invalid_domains' => $domainParse['invalid'],
        ]);
      }

      $domainCsv = implode(',', $domainParse['domains']);
      if (strlen($domainCsv) > 300) {
        return $this->fail(Strings::i18n('BUSINESSES_API_CONTACT_DOMAINS_TOO_LONG'));
      }

      $normalized['allowed_contact_domains'] = $domainCsv;
    }

    $extendedSettingTextLimits = [
      'legal_name' => 140,
      'industry' => 80,
      'registration_number' => 64,
      'tax_id' => 64,
      'employee_count' => 16,
      'founded_year' => 8,
      'contact_email' => 160,
      'contact_phone' => 32,
      'website' => 180,
      'indigenous_owned' => 1,
      'resident_on_reserve' => 1,
      'reserve_name' => 120,
      'address_line1' => 120,
      'address_line2' => 120,
      'address_city' => 80,
      'address_region' => 80,
      'address_postal' => 20,
      'address_country' => 64,
      'support_hours' => 120,
      'org_notes' => 1200,
      'contact_payroll_name' => 100,
      'contact_payroll_image_url' => 20000,
      'contact_payroll_email' => 160,
      'contact_payroll_phone' => 32,
      'contact_payroll_role' => 80,
      'contact_hr_name' => 100,
      'contact_hr_image_url' => 20000,
      'contact_hr_email' => 160,
      'contact_hr_phone' => 32,
      'contact_hr_role' => 80,
      'contact_ceo_name' => 100,
      'contact_ceo_image_url' => 20000,
      'contact_ceo_email' => 160,
      'contact_ceo_phone' => 32,
      'contact_ceo_role' => 80,
      'contact_coo_name' => 100,
      'contact_coo_image_url' => 20000,
      'contact_coo_email' => 160,
      'contact_coo_phone' => 32,
      'contact_coo_role' => 80,
      'contact_cto_name' => 100,
      'contact_cto_image_url' => 20000,
      'contact_cto_email' => 160,
      'contact_cto_phone' => 32,
      'contact_cto_role' => 80,
      'contact_support_name' => 100,
      'contact_support_image_url' => 20000,
      'contact_support_email' => 160,
      'contact_support_phone' => 32,
      'contact_support_role' => 80,
      'contact_operations_name' => 100,
      'contact_operations_image_url' => 20000,
      'contact_operations_email' => 160,
      'contact_operations_phone' => 32,
      'contact_operations_role' => 80,
      'contact_manager_name' => 100,
      'contact_manager_image_url' => 20000,
      'contact_manager_email' => 160,
      'contact_manager_phone' => 32,
      'contact_manager_role' => 80,
      'contact_custom_json' => 30000,
    ];

    foreach ($extendedSettingTextLimits as $field => $maxLen) {
      if (array_key_exists($field, $settings)) {
        $normalized[$field] = $this->normalizeSettingText($settings[$field], $maxLen);
      }
    }

    foreach (['indigenous_owned', 'resident_on_reserve'] as $flagField) {
      if (array_key_exists($flagField, $settings)) {
        $normalized[$flagField] = $this->isTruthySettingValue($settings[$flagField]) ? '1' : '0';
      }
    }

    $effectiveEnforceContactDomain = ($normalized['enforce_contact_domain'] ?? (string) ($existingSettings['enforce_contact_domain'] ?? '0')) === '1';
    $effectiveAllowedDomains = $this->parseAllowedContactDomainCsv((string) ($normalized['allowed_contact_domains'] ?? ($existingSettings['allowed_contact_domains'] ?? '')));

    if ($effectiveEnforceContactDomain) {
      if ([] === $effectiveAllowedDomains) {
        return $this->fail(Strings::i18n('BUSINESSES_API_CONTACT_DOMAIN_ENFORCEMENT_IS_ENABLED_BUT_NO_ALLOWED_DOM'));
      }

      $contactEmails = $this->collectEffectiveContactEmails($existingSettings, $normalized);
      $violations = [];
      foreach ($contactEmails as $email) {
        $domain = $this->emailDomain($email);
        if ($domain === '' || !isset($effectiveAllowedDomains[$domain])) {
          $violations[] = $email;
        }
      }

      if ([] !== $violations) {
        return $this->fail(Strings::i18n('BUSINESSES_API_ONE_OR_MORE_CONTACT_EMAILS_DO_NOT_MATCH_ALLOWED_DOMAINS'), [
          'violations' => array_values(array_unique($violations)),
        ]);
      }
    }

    if ([] === $normalized && [] === $businessUpdates && !$didRoleUpdate) {
      return $this->fail(Strings::i18n('BUSINESSES_API_NO_VALID_SETTINGS_WERE_PROVIDED'));
    }

    $normalized['last_updated_at'] = date('c');
    $normalized['last_updated_by'] = $actorUUID;

    if ([] !== $businessUpdates) {
      $businessUpdates['updated_at'] = $normalized['last_updated_at'];
      Database::hset(Keys::BUSINESS . ':' . $businessId, $businessUpdates);
      BusinessSearchIndex::sync($businessId);
    }

    Database::hset(Keys::BUSINESS_SETTINGS . ':' . $businessId, $normalized);

    $businessType = (string) ($business['business_type'] ?? 'shared');
    if ($businessType === 'personal') {
      $this->syncPersonalBusinessSettingsToOwner($businessId, $business, $normalized);
    }

    $fieldList = array_unique(array_merge(array_keys($businessUpdates), array_keys($normalized), $didRoleUpdate ? ['role'] : []));
    $this->appendAuditEvent($businessId, 'settings.updated', $actorUUID, [
      'fields' => implode(',', $fieldList),
    ]);

    // Pipeline both hgetall calls to save one network round-trip.
    $fetched = Database::multi(function (\Redis $r) use ($businessId): void {
      $r->hGetAll(Keys::BUSINESS . ':' . $businessId);
      $r->hGetAll(Keys::BUSINESS_SETTINGS . ':' . $businessId);
    });

    $responseData = [
      'business_id' => $businessId,
      'business' => is_array($fetched[0] ?? null) ? $fetched[0] : [],
      'settings'      => is_array($fetched[1] ?? null) ? $fetched[1] : [],
    ];
    if ($moderationMessage !== '') {
      $responseData['moderation_message'] = $moderationMessage;
    }

    return $this->ok($moderationMessage !== '' ? $moderationMessage : 'Business settings updated.', $responseData);
  }

  /**
   * Transfer business ownership from the current owner to an active member.
   *
   * Validates that the target is an active member (not already the owner), then
   * atomically updates both the org record and connection roles.
   *
   * @param string $actorUUID  Current owner UUID (or admin override).
   * @param string $businessId      Business ID.
   * @param string $targetUUID UUID of the member receiving ownership.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function transferOwnership(string $actorUUID, string $businessId, string $targetUUID): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    $targetUUID = trim(InputSanitizer::sanitizeString($targetUUID));
    if ('' === $targetUUID) {
      return $this->fail(Strings::i18n('BUSINESSES_API_TARGET_USER_UUID_IS_REQUIRED'));
    }

    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ([] === $business) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND'));
    }

    if (($business['business_type'] ?? 'shared') === 'personal') {
      return $this->fail(Strings::i18n('BUSINESSES_API_PERSONAL_BUSINESSES_CANNOT_TRANSFER_OWNERSHIP'));
    }

    $currentOwner = (string) ($business['owner_uuid'] ?? '');
    if ($currentOwner !== $actorUUID) {
      return $this->fail(Strings::i18n('BUSINESSES_API_ONLY_THE_CURRENT_OWNER_CAN_TRANSFER_OWNERSHIP'));
    }

    if ($targetUUID === $actorUUID) {
      return $this->fail(Strings::i18n('BUSINESSES_API_TARGET_OWNER_MUST_BE_A_DIFFERENT_USER'));
    }

    $targetUser = UserRepository::getByUUID($targetUUID);
    if (null === $targetUser) {
      return $this->fail(Strings::i18n('BUSINESSES_API_TARGET_USER_NOT_FOUND'));
    }

    $targetEmail = InputSanitizer::sanitizeEmail($targetUser->email);
    $domainGate = $this->ensureContactDomainPolicyAllowsEmail($businessId, $targetEmail, 'ownership transfer target email');
    if (!$domainGate['success']) {
      return $domainGate;
    }

    $targetConnection = $this->connection($businessId, $targetUUID);
    if ([] === $targetConnection || (string) ($targetConnection['status'] ?? '') !== 'active') {
      return $this->fail(Strings::i18n('BUSINESSES_API_OWNERSHIP_CAN_ONLY_BE_TRANSFERRED_TO_AN_EXISTING_ACTIVE'));
    }

    $timestamp = date('c');
    $previousOwnerScopes = implode(',', self::ROLE_SCOPE_PRESETS['coordinator']);

    $this->setConnection($businessId, $actorUUID, [
      'role' => 'coordinator',
      'status' => 'active',
      'scopes' => $previousOwnerScopes,
      'transferred_at' => $timestamp,
      'transferred_to' => $targetUUID,
    ]);

    $this->setConnection($businessId, $targetUUID, [
      'role' => 'owner',
      'status' => 'active',
      'scopes' => 'all',
      'accepted_at' => $timestamp,
      'owner_since' => $timestamp,
      'transferred_from' => $actorUUID,
    ]);

    Database::hset(Keys::BUSINESS . ':' . $businessId, [
      'owner_uuid' => $targetUUID,
      'updated_at' => $timestamp,
    ]);

    Database::srem(Keys::BUSINESS_OWNER . ':' . $actorUUID, $businessId);
    Database::sadd(Keys::BUSINESS_OWNER . ':' . $targetUUID, $businessId);

    $this->appendAuditEvent($businessId, 'ownership.transferred', $actorUUID, [
      'from_user_uuid' => $actorUUID,
      'to_user_uuid' => $targetUUID,
    ]);

    return $this->ok('Ownership transferred.', [
      'business_id' => $businessId,
      'owner_uuid' => $targetUUID,
    ]);
  }

  /**
   * Fast context-header metrics backed by the versioned business snapshot.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function loadBusinessContextHeaderMetrics(string $actorUUID, string $businessId): array
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND'));
    }

    $connection = $this->connection($businessId, $actorUUID);
    if ([] === $connection) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_A_BUSINESS_CONNECTION'));
    }

    $canViewAccessMetrics = $this->canManageAccess($businessId, $actorUUID)
      || $this->canManageBusiness($businessId, $actorUUID);

    $snapshot = BusinessSnapshotCache::getOrBuild(
      $businessId,
      fn (): BusinessSnapshot => $this->buildBusinessSnapshot($businessId, $canViewAccessMetrics),
    );

    return $this->ok('Business context metrics retrieved.', [
      'business_id' => $businessId,
      'snapshot_version' => $snapshot->snapshot_version,
      'snapshot' => $snapshot->toArray(),
      'member_count' => $snapshot->member_count,
      'site_count' => $snapshot->site_count,
      'pending_invites' => $snapshot->pending_invites,
      'pending_requests' => $snapshot->pending_requests,
    ]);
  }

  /**
   * Materialize a versioned org metadata snapshot for a business.
   */
  public function buildBusinessSnapshot(string $businessId, bool $canViewAccessMetrics = true): BusinessSnapshot
  {
    $businessId = trim($businessId);
    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ($business === []) {
      return new BusinessSnapshot(
        snapshot_version: '0',
        business_id: $businessId,
        member_count: 0,
        site_count: 0,
        connections: [],
        members: [],
      );
    }

    $connectionHashes = Database::pipelineHgetall(
      Database::scanKeys(Keys::BUSINESS_CONNECTION . ':' . $businessId . ':*'),
    );

    $memberUUIDs = [];
    $latestUpdatedAt = '';
    foreach ($connectionHashes as $connection) {
      $candidateUUID = trim((string) ($connection['user_uuid'] ?? ''));
      if ($candidateUUID !== '') {
        $memberUUIDs[] = $candidateUUID;
      }

      $updatedAt = trim((string) ($connection['updated_at'] ?? ''));
      if ($updatedAt !== '' && strcmp($updatedAt, $latestUpdatedAt) > 0) {
        $latestUpdatedAt = $updatedAt;
      }
    }

    $profiles = UserRepository::findMany($memberUUIDs);

    $connections = [];
    $members = [];

    foreach ($connectionHashes as $connection) {
      if ($connection === []) {
        continue;
      }

      $role = strtolower(trim((string) ($connection['role'] ?? '')));
      $memberUUID = trim((string) ($connection['user_uuid'] ?? ''));
      if ($memberUUID === '') {
        continue;
      }

      $connectionEntry = [
        'user_uuid' => $memberUUID,
        'role' => $role,
        'status' => (string) ($connection['status'] ?? ''),
        'scopes' => $this->scopeList((string) ($connection['scopes'] ?? '')),
        'created_at' => (string) ($connection['created_at'] ?? ''),
        'accepted_at' => (string) ($connection['accepted_at'] ?? ''),
        'owner_since' => (string) ($connection['owner_since'] ?? ''),
        'updated_at' => (string) ($connection['updated_at'] ?? ''),
      ];
      $connections[] = $connectionEntry;

      $status = (string) ($connection['status'] ?? '');
      $member = $profiles[$memberUUID] ?? null;
      if ($status === self::MEMBERSHIP_STATE_ACTIVE && $member instanceof User) {
        $members[] = [
          'uuid' => $memberUUID,
          'user_uuid' => $memberUUID,
          'full_name' => $member->full_name,
          'email' => $member->email,
          'role' => $role,
          'status' => $status,
          'scopes' => $connectionEntry['scopes'],
          'created_at' => (string) ($connection['created_at'] ?? ''),
          'accepted_at' => (string) ($connection['accepted_at'] ?? ''),
          'owner_since' => (string) ($connection['owner_since'] ?? ''),
          'updated_at' => (string) ($connection['updated_at'] ?? ''),
        ];
      }
    }

    usort($connections, static function (array $a, array $b): int {
      return strcmp((string) $a['user_uuid'], (string) $b['user_uuid']);
    });

    usort($members, static function (array $a, array $b): int {
      $nameCompare = strcasecmp((string) $a['full_name'], (string) $b['full_name']);
      if ($nameCompare !== 0) {
        return $nameCompare;
      }

      $emailCompare = strcasecmp((string) $a['email'], (string) $b['email']);
      if ($emailCompare !== 0) {
        return $emailCompare;
      }

      return strcmp((string) $a['user_uuid'], (string) $b['user_uuid']);
    });

    $dashboardMetrics = BusinessDashboardMetrics::forBusiness($businessId, $canViewAccessMetrics);
    $generatedAt = date('c');
    $snapshotVersion = $latestUpdatedAt !== ''
      ? $latestUpdatedAt
      : substr(sha1($businessId . ':' . $generatedAt . ':' . count($members)), 0, 16);

    return new BusinessSnapshot(
      snapshot_version: $snapshotVersion,
      business_id: $businessId,
      member_count: max(count($members), $dashboardMetrics['members']),
      site_count: $dashboardMetrics['sites'],
      connections: $connections,
      members: $members,
      pending_invites: $dashboardMetrics['pending_invites'],
      pending_requests: $dashboardMetrics['pending_requests'],
      generated_at: $generatedAt,
    );
  }

  /**
   * List all member connections and their roles for an business.
   *
   * Returns active, pending, and revoked connections with hydrated user
   * profile details. Requires manage-access or manage-business permission.
   *
   * @param string $actorUUID Authenticated actor UUID.
   * @param string $businessId     Business ID.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function listConnections(string $actorUUID, string $businessId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($businessId, $actorUUID) && !$this->canManageBusiness($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_VIEW_BUSINESS_CONNECTION'));
    }

    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ([] === $business) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND'));
    }

    $snapshot = BusinessSnapshotCache::getOrBuild(
      $businessId,
      fn (): BusinessSnapshot => $this->buildBusinessSnapshot($businessId, true),
    );

    return $this->ok('Connections retrieved.', [
      'business_id' => $businessId,
      'snapshot_version' => $snapshot->snapshot_version,
      'snapshot' => $snapshot->toArray(),
      'connections' => $snapshot->connections,
      'members' => $snapshot->members,
      'member_count' => $snapshot->member_count,
      'site_count' => $snapshot->site_count,
    ]);
  }

  /**
   * List all audit events for an business (admin or coordinator view).
   *
   * Returns the full audit timeline with actor details. Requires
   * manage-business permission.
   *
   * @param string $actorUUID Authenticated actor UUID.
   * @param string $businessId     Business ID.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function listAuditTimeline(string $actorUUID, string $businessId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canReadAuditTimeline($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_VIEW_BUSINESS_AUDIT_TIMELI'));
    }

    $eventIds = Database::smembers(Keys::BUSINESS_AUDIT . ':' . $businessId);
    $events = [];

    foreach ($eventIds as $eventId) {
      $event = Database::hgetall(Keys::BUSINESS_AUDIT_EVENT . ':' . $eventId);
      if ([] === $event) {
        continue;
      }

      if (($event['business_id'] ?? '') !== $businessId) {
        continue;
      }

      $events[] = [
        'event_id' => (string) ($event['event_id'] ?? $eventId),
        'event_type' => (string) ($event['event_type'] ?? ''),
        'actor_uuid' => (string) ($event['actor_uuid'] ?? ''),
        'details' => (string) ($event['details'] ?? ''),
        'created_at' => (string) ($event['created_at'] ?? ''),
      ];
    }

    usort($events, static function (array $a, array $b): int {
      return strcmp((string) $b['created_at'], (string) $a['created_at']);
    });

    return $this->ok('Business audit timeline retrieved.', [
      'business_id' => $businessId,
      'events' => $events,
    ]);
  }

  /**
   * List audit events scoped to the calling member's own actions.
   *
   * Filters the full timeline to events where the actor is the requesting user,
   * allowing regular members to review their own history.
   *
   * @param string $actorUUID Authenticated member UUID.
   * @param string $businessId     Business ID.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function listAuditTimelineForMember(string $actorUUID, string $businessId): array
  {
    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ([] === $business) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND'));
    }

    $connection = $this->connection($businessId, $actorUUID);
    if ([] === $connection) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_A_BUSINESS_CONNECTION'));
    }

    $connectionStatus = (string) ($connection['status'] ?? '');
    if ($connectionStatus !== 'active' && $connectionStatus !== 'pending') {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOU_DO_NOT_HAVE_PERMISSION_TO_VIEW_BUSINESS_AUDIT_TIMELI'));
    }

    $actor = UserRepository::getByUUID($actorUUID);
    $actorEmail = $actor instanceof User ? strtolower(trim($actor->email)) : '';

    $eventIds = Database::smembers(Keys::BUSINESS_AUDIT . ':' . $businessId);
    $events = [];

    foreach ($eventIds as $eventId) {
      $event = Database::hgetall(Keys::BUSINESS_AUDIT_EVENT . ':' . $eventId);
      if ([] === $event) {
        continue;
      }

      if (($event['business_id'] ?? '') !== $businessId) {
        continue;
      }

      if (!$this->isAuditEventRelatedToProfile($event, $actorUUID, $actorEmail)) {
        continue;
      }

      $events[] = [
        'event_id' => (string) ($event['event_id'] ?? $eventId),
        'event_type' => (string) ($event['event_type'] ?? ''),
        'actor_uuid' => (string) ($event['actor_uuid'] ?? ''),
        'details' => (string) ($event['details'] ?? ''),
        'created_at' => (string) ($event['created_at'] ?? ''),
      ];
    }

    usort($events, static function (array $a, array $b): int {
      return strcmp((string) $b['created_at'], (string) $a['created_at']);
    });

    return $this->ok('Business audit timeline retrieved.', [
      'business_id' => $businessId,
      'events' => $events,
    ]);
  }

  /**
   * Handles canManageBusiness operation.
   */
  private function canManageBusiness(string $businessId, string $userUUID): bool
  {
    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ([] === $business) {
      return false;
    }

    if (($business['owner_uuid'] ?? '') === $userUUID) {
      return true;
    }

    $connection = $this->connection($businessId, $userUUID);
    if ([] === $connection) {
      return false;
    }

    if (($connection['status'] ?? '') !== 'active') {
      return false;
    }

    if (($connection['role'] ?? '') === 'owner') {
      return true;
    }

    $scopeSet = $this->scopeMap((string) ($connection['scopes'] ?? ''));

    return isset($scopeSet['business.settings.write']);
  }

  /**
   * Check if the user can access shared business creation and management.
   * Business tier grants access; admins and managers keep system-level access.
   */
  private function canAccessPremiumFeatures(string $userUUID): bool
  {
    // Admins always have access (system role)
    if (User::isAdmin()) {
      return true;
    }

    // Managers are trusted to operate within org permission frameworks
    if (User::isManager()) {
      return true;
    }

    return SubscriptionRepository::isBusinessActive($userUUID);
  }

  /** @param array<string, mixed> $business */
  private function isSelfBusiness(array $business, string $userUUID): bool
  {
    $typeRaw = $business['business_type'] ?? $business['organization_type'] ?? 'shared';
    $ownerUUIDRaw = $business['owner_uuid'] ?? '';

    $type = is_scalar($typeRaw) ? (string) $typeRaw : 'shared';
    $ownerUUID = is_scalar($ownerUUIDRaw) ? (string) $ownerUUIDRaw : '';

    return $type === 'personal' && $ownerUUID === $userUUID;
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>}|null */
  private function requireAdminPreviewOrSelfOrg(string $actorUUID, string $businessId): ?array
  {
    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ([] === $business) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND'));
    }

    $isBusinessOwner = (string) ($business['owner_uuid'] ?? '') === $actorUUID;

    $connection = $this->connection($businessId, $actorUUID);
    $hasOrgConnection = $connection !== [] && in_array(
      (string) ($connection['status'] ?? ''),
      [self::MEMBERSHIP_STATE_ACTIVE, self::MEMBERSHIP_STATE_PENDING, self::MEMBERSHIP_STATE_CONSENTED],
      true
    );

    if ($this->canAccessPremiumFeatures($actorUUID) || $this->isSelfBusiness($business, $actorUUID) || $isBusinessOwner || $hasOrgConnection) {
      return null;
    }

    return $this->premiumSubscriptionRequired();
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  private function premiumSubscriptionRequired(): array
  {
    return $this->fail(Strings::i18n('BUSINESSES_API_PAYCAL_BUSINESS_SUBSCRIPTION_REQUIRED_UPGRADE_TO_PAYCAL'));
  }

  /**
   * Count active members in an business (connections with status 'active').
   *
   * @param string $businessId
   * @return int
   */
  private function getBusinessMemberCount(string $businessId): int
  {
    return BusinessMemberRepository::count($businessId, 'active');
  }

  /**
   * Check if business has reached the member limit.
   * Business tier allows up to 100 active members total, including the owner.
   *
   * @param string $businessId
   * @return bool True if the org has reached its member limit
   */
  private function hasReachedMemberLimit(string $businessId): bool
  {
    $maxMembers = Subscription::BUSINESS->maxMembersPerOrg();
    $currentMembers = $this->getBusinessMemberCount($businessId);
    return $currentMembers >= $maxMembers;
  }

  /**
   * Handles canManageAccess operation.
   */
  private function canManageAccess(string $businessId, string $userUUID): bool
  {
    if ($this->canManageBusiness($businessId, $userUUID)) {
      return true;
    }

    $connection = $this->connection($businessId, $userUUID);
    if ([] === $connection || ($connection['status'] ?? '') !== 'active') {
      return false;
    }

    $scopeSet = $this->scopeMap((string) ($connection['scopes'] ?? ''));

    return isset($scopeSet['access.manage']);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  private function resolveBulkImportAuthority(string $actorUUID, string $businessId): array
  {
    $actor = UserRepository::getByUUID($actorUUID);
    if (null === $actor) {
      return $this->fail(Strings::i18n('BUSINESSES_API_ACTOR_ACCOUNT_NOT_FOUND'));
    }

    if (!$actor->email_verified) {
      return $this->fail(Strings::i18n('BUSINESSES_API_YOUR_ACCOUNT_EMAIL_MUST_BE_VERIFIED_BEFORE_BULK_IMPORTIN'));
    }

    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ([] === $business) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BUSINESS_NOT_FOUND'));
    }

    $connection = $this->connection($businessId, $actorUUID);
    $role = (string) ($connection['role'] ?? '');
    $isOwner = (string) ($business['owner_uuid'] ?? '') === $actorUUID;
    $isOrgAdmin = $role === 'coordinator';

    if (!User::isAdmin() && !$isOwner && !$isOrgAdmin) {
      return $this->fail(Strings::i18n('BUSINESSES_API_ONLY_BUSINESS_OWNERS_ADMINS_CAN_PERFORM_BULK_IMPORTS'));
    }

    $authorityEmail = InputSanitizer::sanitizeEmail($actor->email);
    if ($authorityEmail === '' || !filter_var($authorityEmail, FILTER_VALIDATE_EMAIL)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_A_VERIFIED_AUTHORITY_EMAIL_IS_REQUIRED_FOR_BULK_IMPORT_V'));
    }

    $authorityDomain = $this->emailDomain($authorityEmail);
    if ($authorityDomain === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_UNABLE_TO_DETERMINE_AUTHORITY_EMAIL_DOMAIN_FOR_BULK_IMPO'));
    }

    return $this->ok('Bulk import authority resolved.', [
      'authority_email' => $authorityEmail,
      'authority_domain' => $authorityDomain,
    ]);
  }

  /** @return array{input_count: int, valid: array<int, string>, invalid: array<int, string>, duplicates: array<int, string>} */
  private function parseBulkInviteEmails(string $raw): array
  {
    $parts = preg_split('/[\s,;]+/', $raw) ?: [];
    $seen = [];
    $valid = [];
    $invalid = [];
    $duplicates = [];

    foreach ($parts as $part) {
      $candidateRaw = trim((string) $part);
      if ($candidateRaw === '') {
        continue;
      }

      $email = strtolower(InputSanitizer::sanitizeEmail($candidateRaw));
      if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $invalid[] = $candidateRaw;
        continue;
      }

      if (isset($seen[$email])) {
        $duplicates[] = $email;
        continue;
      }

      $seen[$email] = true;
      $valid[] = $email;
    }

    return [
      'input_count' => count($valid) + count($invalid) + count($duplicates),
      'valid' => $valid,
      'invalid' => $invalid,
      'duplicates' => $duplicates,
    ];
  }

  /**
   * Handles emailDomain operation.
   */
  private function emailDomain(string $email): string
  {
    $atPos = strrpos($email, '@');
    if ($atPos === false || $atPos === strlen($email) - 1) {
      return '';
    }

    return strtolower(substr($email, $atPos + 1));
  }

  /** @return array{domains: array<int, string>, invalid: array<int, string>} */
  private function parseAllowedContactDomainPayload(mixed $value): array
  {
    if (!is_scalar($value)) {
      return ['domains' => [], 'invalid' => []];
    }

    $raw = trim((string) $value);
    if ($raw === '') {
      return ['domains' => [], 'invalid' => []];
    }

    $parts = preg_split('/[\s,;]+/', $raw) ?: [];
    $seen = [];
    $domains = [];
    $invalid = [];

    foreach ($parts as $part) {
      $candidate = strtolower(trim((string) $part));
      if ($candidate === '') {
        continue;
      }

      if (str_starts_with($candidate, '@')) {
        $candidate = ltrim($candidate, '@');
      }

      if (!$this->isValidContactPolicyDomain($candidate)) {
        $invalid[] = $candidate;
        continue;
      }

      if (!isset($seen[$candidate])) {
        $seen[$candidate] = true;
        $domains[] = $candidate;
      }
    }

    return [
      'domains' => $domains,
      'invalid' => $invalid,
    ];
  }

  /** @return array<string, bool> */
  private function parseAllowedContactDomainCsv(string $csv): array
  {
    $parsed = $this->parseAllowedContactDomainPayload($csv);
    $map = [];
    foreach ($parsed['domains'] as $domain) {
      $map[$domain] = true;
    }

    return $map;
  }

  /**
   * Validate a single domain value used by contact-domain enforcement settings.
   */
  private function isValidContactPolicyDomain(string $domain): bool
  {
    if ($domain === '' || strlen($domain) > 253) {
      return false;
    }

    if (!str_contains($domain, '.')) {
      return false;
    }

    return preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/', $domain) === 1;
  }

  /**
   * Normalize mixed settings input to a strict boolean interpretation.
   */
  private function isTruthySettingValue(mixed $value): bool
  {
    if (is_bool($value)) {
      return $value;
    }

    if (is_int($value) || is_float($value)) {
      return ((int) $value) !== 0;
    }

    if (!is_scalar($value)) {
      return false;
    }

    $normalized = strtolower(trim((string) $value));
    return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
  }

  /**
   * @param array<string, string> $existingSettings
   * @param array<string, string> $normalizedOverrides
   * @return array<int, string>
   */
  private function collectEffectiveContactEmails(array $existingSettings, array $normalizedOverrides): array
  {
    $emailFields = [
      'contact_email',
      'contact_payroll_email',
      'contact_hr_email',
      'contact_ceo_email',
      'contact_coo_email',
      'contact_cto_email',
      'contact_support_email',
      'contact_operations_email',
      'contact_manager_email',
    ];

    $emails = [];
    foreach ($emailFields as $field) {
      $candidate = array_key_exists($field, $normalizedOverrides)
        ? (string) $normalizedOverrides[$field]
        : (string) $existingSettings[$field];
      $clean = InputSanitizer::sanitizeEmail($candidate);
      if ($clean !== '' && filter_var($clean, FILTER_VALIDATE_EMAIL)) {
        $emails[] = strtolower($clean);
      }
    }

    $customJson = array_key_exists('contact_custom_json', $normalizedOverrides)
      ? (string) $normalizedOverrides['contact_custom_json']
      : (string) $existingSettings['contact_custom_json'];
    if ($customJson === '') {
      return array_values(array_unique($emails));
    }

    $decoded = json_decode($customJson, true);
    if (!is_array($decoded)) {
      return array_values(array_unique($emails));
    }

    foreach ($decoded as $row) {
      if (!is_array($row) || !array_key_exists('email', $row)) {
        continue;
      }

      $candidate = is_scalar($row['email']) ? (string) $row['email'] : '';
      $clean = InputSanitizer::sanitizeEmail($candidate);
      if ($clean !== '' && filter_var($clean, FILTER_VALIDATE_EMAIL)) {
        $emails[] = strtolower($clean);
      }
    }

    return array_values(array_unique($emails));
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  private function ensureContactDomainPolicyAllowsEmail(string $businessId, string $email, string $contextLabel): array
  {
    $settings = Database::hgetall(Keys::BUSINESS_SETTINGS . ':' . $businessId);
    $enforce = ((string) ($settings['enforce_contact_domain'] ?? '0')) === '1';
    if (!$enforce) {
      return $this->ok('Contact domain policy not enforced.', []);
    }

    $allowed = $this->parseAllowedContactDomainCsv((string) ($settings['allowed_contact_domains'] ?? ''));
    if ([] === $allowed) {
      return $this->fail(Strings::i18n('BUSINESSES_API_CONTACT_DOMAIN_ENFORCEMENT_IS_ENABLED_BUT_NO_ALLOWED_DOM'));
    }

    $domain = $this->emailDomain(strtolower(InputSanitizer::sanitizeEmail($email)));
    if ($domain === '' || !isset($allowed[$domain])) {
      return $this->fail(Strings::i18n('BUSINESSES_API_EMAIL_DOMAIN_IS_BLOCKED_BY_BUSINESS_CONTACT_DOMAIN_POLIC'), [
        'context' => $contextLabel,
        'email' => $email,
        'domain' => $domain,
        'allowed_domains' => array_keys($allowed),
      ]);
    }

    return $this->ok('Email domain allowed by contact-domain policy.', []);
  }

  /**
   * Handles maskEmail operation.
   */
  private function maskEmail(string $email): string
  {
    $atPos = strrpos($email, '@');
    if ($atPos === false || $atPos < 2) {
      return '***@***';
    }

    return substr($email, 0, 2) . '***' . substr($email, $atPos);
  }

  /** @return array<int, string> */
  private function decodeStringArray(string $json): array
  {
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
      return [];
    }

    $list = [];
    foreach ($decoded as $value) {
      if (!is_string($value)) {
        continue;
      }

      $trimmed = trim($value);
      if ($trimmed !== '') {
        $list[] = $trimmed;
      }
    }

    return $list;
  }

  /** @param array<string, mixed> $payload */
  private function arrayStringValue(array $payload, string $key, string $default = ''): string
  {
    if (!array_key_exists($key, $payload)) {
      return $default;
    }

    $value = $payload[$key];
    if (is_string($value)) {
      return $value;
    }

    if (is_int($value) || is_float($value) || is_bool($value)) {
      return (string) $value;
    }

    return $default;
  }

  /** @param array<string, mixed> $payload */
  private function arrayIntValue(array $payload, string $key, int $default = 0): int
  {
    if (!array_key_exists($key, $payload)) {
      return $default;
    }

    $value = $payload[$key];
    if (is_int($value)) {
      return $value;
    }

    if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
      return (int) $value;
    }

    if (is_float($value)) {
      return (int) $value;
    }

    return $default;
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  private function loadBulkImportPrepare(string $actorUUID, string $businessId, string $importId): array
  {
    $importIdClean = trim(InputSanitizer::sanitizeString($importId));
    if ($importIdClean === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_IMPORT_ID_IS_REQUIRED'));
    }

    $prepare = Database::hgetall(Keys::businessInviteImportPrepare($importIdClean));
    if ([] === $prepare) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BULK_IMPORT_SESSION_NOT_FOUND_OR_EXPIRED'));
    }

    if ((string) ($prepare['business_id'] ?? '') !== trim(InputSanitizer::sanitizeString($businessId))) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BULK_IMPORT_SESSION_DOES_NOT_BELONG_TO_THIS_BUSINESS'));
    }

    if ((string) ($prepare['actor_uuid'] ?? '') !== $actorUUID) {
      return $this->fail(Strings::i18n('BUSINESSES_API_BULK_IMPORT_SESSION_DOES_NOT_BELONG_TO_THIS_USER'));
    }

    return $this->ok('Bulk import session loaded.', [
      'prepare' => $prepare,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  private function loadBulkImportChallenge(string $actorUUID, string $businessId, string $importId, string $challengeId): array
  {
    $challengeIdClean = trim(InputSanitizer::sanitizeString($challengeId));
    if ($challengeIdClean === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_CHALLENGE_ID_IS_REQUIRED'));
    }

    $challenge = Database::hgetall(Keys::businessInviteImportChallenge($challengeIdClean));
    if ([] === $challenge) {
      return $this->fail(Strings::i18n('BUSINESSES_API_CHALLENGE_NOT_FOUND_OR_EXPIRED'));
    }

    if ((string) ($challenge['actor_uuid'] ?? '') !== $actorUUID) {
      return $this->fail(Strings::i18n('BUSINESSES_API_CHALLENGE_DOES_NOT_BELONG_TO_THIS_USER'));
    }

    if ((string) ($challenge['business_id'] ?? '') !== trim(InputSanitizer::sanitizeString($businessId))) {
      return $this->fail(Strings::i18n('BUSINESSES_API_CHALLENGE_DOES_NOT_BELONG_TO_THIS_BUSINESS'));
    }

    if ((string) ($challenge['import_id'] ?? '') !== trim(InputSanitizer::sanitizeString($importId))) {
      return $this->fail(Strings::i18n('BUSINESSES_API_CHALLENGE_DOES_NOT_MATCH_THIS_IMPORT_SESSION'));
    }

    return $this->ok('Challenge loaded.', [
      'challenge' => $challenge,
    ]);
  }

  /**
   * Handles canReadBusinessSites operation.
   */
  public function canReadBusinessSites(string $businessId, string $userUUID): bool
  {
    if ($this->canManageBusiness($businessId, $userUUID)) {
      return true;
    }

    $connection = $this->connection($businessId, $userUUID);
    if ([] === $connection || ($connection['status'] ?? '') !== 'active') {
      return false;
    }

    $scopeSet = $this->scopeMap((string) ($connection['scopes'] ?? ''));

    return isset($scopeSet['sites.read']) || isset($scopeSet['sites.write']) || isset($scopeSet['all']);
  }

  /**
   * Handles canWriteBusinessSites operation.
   */
  public function canWriteBusinessSites(string $businessId, string $userUUID): bool
  {
    if ($this->canManageBusiness($businessId, $userUUID)) {
      return true;
    }

    $connection = $this->connection($businessId, $userUUID);
    if ([] === $connection || ($connection['status'] ?? '') !== 'active') {
      return false;
    }

    $scopeSet = $this->scopeMap((string) ($connection['scopes'] ?? ''));

    return isset($scopeSet['sites.write']) || isset($scopeSet['all']);
  }

  /**
   * Handles access to Business Planning fields on site editors.
   */
  public function canUseBusinessPlanning(string $businessId, string $userUUID): bool
  {
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));

    if ($businessId === '' || $userUUID === '') {
      return false;
    }

    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ([] === $business) {
      return false;
    }

    if ((string) ($business['owner_uuid'] ?? '') === $userUUID) {
      return true;
    }

    $connection = $this->connection($businessId, $userUUID);
    if ([] === $connection || ($connection['status'] ?? '') !== 'active') {
      return false;
    }

    if (SubscriptionRepository::isBusinessActive($userUUID)) {
      return true;
    }

    $role = strtolower(trim((string) ($connection['role'] ?? '')));
    if (in_array($role, ['owner', 'coordinator', 'manager', 'admin'], true)) {
      return true;
    }

    $scopeSet = $this->scopeMap((string) ($connection['scopes'] ?? ''));

    return isset($scopeSet['business.settings.write']) || isset($scopeSet['all']);
  }

  /**
   * Handles canReadBusinessSettings operation.
   */
  private function canReadBusinessSettings(string $businessId, string $userUUID): bool
  {
    if ($this->canManageBusiness($businessId, $userUUID)) {
      return true;
    }

    $connection = $this->connection($businessId, $userUUID);
    if ([] === $connection || ($connection['status'] ?? '') !== 'active') {
      return false;
    }

    $scopeSet = $this->scopeMap((string) ($connection['scopes'] ?? ''));

    return isset($scopeSet['business.settings.read']) || isset($scopeSet['business.settings.write']);
  }

  /**
   * Handles canWriteBusinessSettings operation.
   */
  private function canWriteBusinessSettings(string $businessId, string $userUUID): bool
  {
    if ($this->canManageBusiness($businessId, $userUUID)) {
      return true;
    }

    $connection = $this->connection($businessId, $userUUID);
    if ([] === $connection || ($connection['status'] ?? '') !== 'active') {
      return false;
    }

    $scopeSet = $this->scopeMap((string) ($connection['scopes'] ?? ''));

    return isset($scopeSet['business.settings.write']);
  }

  /**
   * Handles pay-period write checks for roles that can operate pay controls
   * without full business settings mutation rights.
   */
  private function canWriteBusinessPayPeriod(string $businessId, string $userUUID): bool
  {
    if ($this->canManageBusiness($businessId, $userUUID)) {
      return true;
    }

    $connection = $this->connection($businessId, $userUUID);
    if ([] === $connection || ($connection['status'] ?? '') !== 'active') {
      return false;
    }

    $scopeSet = $this->scopeMap((string) ($connection['scopes'] ?? ''));
    if (isset($scopeSet['payperiod.write']) || isset($scopeSet['business.settings.write']) || isset($scopeSet['all'])) {
      return true;
    }

    $role = strtolower(trim((string) ($connection['role'] ?? '')));
    return in_array($role, ['owner', 'coordinator', 'contributor'], true);
  }

  /**
   * Handles pay-period read checks for roles that should access pay controls
   * without full business settings read rights.
   */
  private function canReadBusinessPayPeriod(string $businessId, string $userUUID): bool
  {
    if ($this->canManageBusiness($businessId, $userUUID)) {
      return true;
    }

    $connection = $this->connection($businessId, $userUUID);
    if ([] === $connection || ($connection['status'] ?? '') !== 'active') {
      return false;
    }

    $scopeSet = $this->scopeMap((string) ($connection['scopes'] ?? ''));
    if (isset($scopeSet['payperiod.read']) || isset($scopeSet['payperiod.write']) || isset($scopeSet['business.settings.read']) || isset($scopeSet['business.settings.write']) || isset($scopeSet['all'])) {
      return true;
    }

    $role = strtolower(trim((string) ($connection['role'] ?? '')));
    return in_array($role, ['owner', 'coordinator', 'contributor', 'member', 'viewer'], true);
  }

  /**
   * Handles canReadAuditTimeline operation.
   */
  private function canReadAuditTimeline(string $businessId, string $userUUID): bool
  {
    if ($this->canManageBusiness($businessId, $userUUID)) {
      return true;
    }

    $connection = $this->connection($businessId, $userUUID);
    if ([] === $connection || ($connection['status'] ?? '') !== 'active') {
      return false;
    }

    $scopeSet = $this->scopeMap((string) ($connection['scopes'] ?? ''));
    $role = strtolower(trim((string) ($connection['role'] ?? '')));

    if ($role === 'contributor') {
      return true;
    }

    return isset($scopeSet['audit.read'])
      || isset($scopeSet['business.settings.write'])
      || isset($scopeSet['access.manage'])
      || isset($scopeSet['all']);
  }

  /** @param array<string, string> $event */
  private function isAuditEventRelatedToProfile(array $event, string $userUUID, string $userEmail): bool
  {
    if ((string) ($event['actor_uuid'] ?? '') === $userUUID) {
      return true;
    }

    $detailsRaw = (string) ($event['details'] ?? '{}');
    $decoded = json_decode($detailsRaw, true);
    if (!is_array($decoded)) {
      return false;
    }

    return $this->arrayContainsProfileIdentity($decoded, $userUUID, $userEmail);
  }

  /** @param array<mixed> $details */
  private function arrayContainsProfileIdentity(array $details, string $userUUID, string $userEmail): bool
  {
    foreach ($details as $value) {
      if (is_array($value)) {
        if ($this->arrayContainsProfileIdentity($value, $userUUID, $userEmail)) {
          return true;
        }

        continue;
      }

      if (!is_scalar($value)) {
        continue;
      }

      $text = strtolower(trim((string) $value));
      if ($text === '') {
        continue;
      }

      if ($text === strtolower($userUUID) || str_contains($text, strtolower($userUUID))) {
        return true;
      }

      if ($userEmail !== '' && ($text === $userEmail || str_contains($text, $userEmail))) {
        return true;
      }
    }

    return false;
  }

  /** @param array<int, string> $scopes
   *  @return array<int, string> */
  private function normalizeScopes(array $scopes): array
  {
    $normalized = [];

    foreach ($scopes as $scopeRaw) {
      $scope = trim(InputSanitizer::sanitizeString($scopeRaw));
      if ($scope === 'work.self.write') {
        $normalized['work.write'] = true;
        $normalized['work.scope.self'] = true;
        continue;
      }

      if (isset(self::ALLOWED_SCOPES[$scope])) {
        $normalized[$scope] = true;
      }
    }

    $result = array_keys($normalized);
    sort($result, SORT_STRING);

    return $result;
  }

  /**
   * Consent enforcement gate for membership activation.
   *
   * @param array<string, mixed> $consentContext
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function ensureActivationConsent(string $businessId, string $userUUID, array $consentContext = []): array
  {
    if (!(bool) SystemConfig::get('business_shared_encryption_enabled')) {
      return $this->ok('Business shared encryption disabled; consent gate bypassed.', []);
    }

    $consentIdRaw = $consentContext['consent_id'] ?? '';
    $consentId = is_scalar($consentIdRaw) ? trim((string) $consentIdRaw) : '';
    if ($consentId !== '') {
      if (!$this->isConsentValidForWrap($businessId, $userUUID, $consentId)) {
        return $this->fail(Strings::i18n('BUSINESSES_API_ACTIVATION_REQUIRES_A_VALID_ACTIVE_CONSENT_RECORD'));
      }

      return $this->ok('Activation consent validated from provided consent id.', [
        'consent_id' => $consentId,
      ]);
    }

    $consentAcknowledgedRaw = $consentContext['consent_acknowledged'] ?? false;
    $consentAcknowledged = filter_var($consentAcknowledgedRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    if (!$consentAcknowledged) {
      return $this->fail(Strings::i18n('BUSINESSES_API_ACTIVATION_REQUIRES_EXPLICIT_CONSENT_ACKNOWLEDGMENT'));
    }

    $consentVersionRaw = $consentContext['consent_version'] ?? 'v1';
    $consentVersion = is_scalar($consentVersionRaw) ? trim((string) $consentVersionRaw) : 'v1';
    if ($consentVersion === '') {
      $consentVersion = 'v1';
    }

    $disclaimerTextRaw = $consentContext['disclaimer_text'] ?? '';
    $disclaimerText = is_scalar($disclaimerTextRaw)
      ? trim((string) $disclaimerTextRaw)
      : '';
    if ($disclaimerText === '') {
      $disclaimerText = 'Business shared encryption consent accepted.';
    }

    $ipRaw = $consentContext['ip'] ?? '';
    $ip = is_scalar($ipRaw) ? trim((string) $ipRaw) : '';

    $userAgentRaw = $consentContext['user_agent'] ?? '';
    $userAgent = is_scalar($userAgentRaw) ? trim((string) $userAgentRaw) : '';

    return $this->recordBusinessConsent(
      $businessId,
      $userUUID,
      $consentVersion,
      $disclaimerText,
      $ip,
      $userAgent
    );
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  private function recordBusinessConsent(
    string $businessId,
    string $userUUID,
    string $consentVersion,
    string $disclaimerText,
    string $ip = '',
    string $userAgent = ''
  ): array {
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    if ($businessId === '' || $userUUID === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_CONSENT_REQUIRES_VALID_BUSINESS_AND_USER_IDENTIFIERS'));
    }

    if (!Database::exists(Keys::BUSINESS . ':' . $businessId)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_CANNOT_RECORD_CONSENT_FOR_MISSING_BUSINESS'));
    }

    $consentVersion = trim(InputSanitizer::sanitizeString($consentVersion));
    if ($consentVersion === '') {
      $consentVersion = 'v1';
    }

    $timestamp = date('c');
    $consentId = 'cons_' . bin2hex(random_bytes(8));
    $consentKey = Keys::businessConsent($consentId);

    $ipHash = $ip !== '' ? hash('sha256', $ip) : '';
    $userAgentHash = $userAgent !== '' ? hash('sha256', $userAgent) : '';
    $disclaimerHash = hash('sha256', $disclaimerText);

    Database::hset($consentKey, [
      'consent_id' => $consentId,
      'business_id' => $businessId,
      'user_uuid' => $userUUID,
      'consent_version' => $consentVersion,
      'accepted_at' => $timestamp,
      'ip_hash' => $ipHash,
      'user_agent_hash' => $userAgentHash,
      'disclaimer_text_hash' => $disclaimerHash,
      'status' => self::MEMBERSHIP_STATE_ACTIVE,
    ]);

    Database::sadd(Keys::businessConsentsByBusiness($businessId), $consentId);
    Database::sadd(Keys::businessConsentsByUser($userUUID), $consentId);

    $this->appendAuditEvent($businessId, 'business.consent.accepted', $userUUID, [
      'consent_id' => $consentId,
      'consent_version' => $consentVersion,
      'user_uuid' => $userUUID,
    ]);

    return $this->ok('Business consent recorded.', [
      'consent_id' => $consentId,
      'business_id' => $businessId,
      'user_uuid' => $userUUID,
      'consent_version' => $consentVersion,
      'accepted_at' => $timestamp,
    ]);
  }

  /** @return array<string, string> */
  private function loadActiveBusinessConsent(string $businessId, string $userUUID): array
  {
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    if ($businessId === '' || $userUUID === '') {
      return [];
    }

    $latestConsent = [];
    $latestAcceptedAt = '';

    $consentIds = Database::smembers(Keys::businessConsentsByUser($userUUID));
    sort($consentIds, SORT_STRING);

    foreach ($consentIds as $consentId) {
      $consent = Database::hgetall(Keys::businessConsent((string) $consentId));
      if ($consent === []) {
        continue;
      }

      if ((string) ($consent['business_id'] ?? '') !== $businessId) {
        continue;
      }

      if ((string) ($consent['status'] ?? '') !== self::MEMBERSHIP_STATE_ACTIVE) {
        continue;
      }

      $acceptedAt = (string) ($consent['accepted_at'] ?? '');
      if ($acceptedAt >= $latestAcceptedAt) {
        $latestAcceptedAt = $acceptedAt;
        $latestConsent = $consent;
      }
    }

    return $latestConsent;
  }

  /**
   * Confirm a consent record still authorizes wrap creation and unwrap usage.
   */
  private function isConsentValidForWrap(string $businessId, string $userUUID, string $consentId = ''): bool
  {
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    $consentId = trim(InputSanitizer::sanitizeString($consentId));

    if ($businessId === '' || $userUUID === '') {
      return false;
    }

    if ($consentId !== '') {
      $consent = Database::hgetall(Keys::businessConsent($consentId));

      return $consent !== []
        && (string) ($consent['business_id'] ?? '') === $businessId
        && (string) ($consent['user_uuid'] ?? '') === $userUUID
        && (string) ($consent['status'] ?? '') === self::MEMBERSHIP_STATE_ACTIVE;
    }

    return $this->loadActiveBusinessConsent($businessId, $userUUID) !== [];
  }

  /**
   * Create an org-bound DEK wrap for an activated member using their existing passkey wrapper.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function bootstrapBusinessDekWrapForMember(
    string $businessId,
    string $userUUID,
    string $consentId,
    string $segment,
    string $version
  ): array {
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    $consentId = trim(InputSanitizer::sanitizeString($consentId));
    $segment = trim(InputSanitizer::sanitizeString($segment));
    $version = trim(InputSanitizer::sanitizeString($version));

    if ($businessId === '' || $userUUID === '' || $consentId === '' || $segment === '' || $version === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_CANNOT_CREATE_BUSINESS_DEK_WRAP_MISSING_BOOTSTRAP_IDENTIFIERS'));
    }

    $dekId = $this->businessDekId($businessId, $segment, $userUUID, $version);
    if ($dekId === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_CANNOT_CREATE_BUSINESS_DEK_WRAP_INVALID_DEK_IDENTIFIER_CONTEX'));
    }

    $resolvedWrap = $this->resolveMemberPasskeyWrapForBusinessBootstrap($userUUID);
    if (!$resolvedWrap['success']) {
      return $resolvedWrap;
    }

    $credentialId = self::scalarString($resolvedWrap['data']['credential_id'] ?? '');
    $wrappedDek = self::scalarString($resolvedWrap['data']['wrapped_dek'] ?? '');
    if ($credentialId === '' || $wrappedDek === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_CANNOT_CREATE_BUSINESS_DEK_WRAP_MISSING_CREDENTIAL_WRAPPER_MA'));
    }

    $store = (new BusinessEncryptionService())->storeBusinessDekWrap(
      $businessId,
      $segment,
      $version,
      $userUUID,
      $credentialId,
      $wrappedDek,
      $consentId,
      'hkdf-passkey-v1',
      $dekId
    );

    if (!$store['success']) {
      return $store;
    }

    $this->appendAuditEvent($businessId, 'business.dek.wrap.bootstrap', $userUUID, [
      'user_uuid' => $userUUID,
      'segment' => $segment,
      'key_version' => $version,
      'dek_id' => $dekId,
      'credential_id' => $credentialId,
      'consent_id' => $consentId,
    ]);

    return $this->ok('Business DEK wrap initialized for member.', [
      'business_id' => $businessId,
      'user_uuid' => $userUUID,
      'segment' => $segment,
      'key_version' => $version,
      'dek_id' => $dekId,
      'credential_id' => $credentialId,
      'consent_id' => $consentId,
      'wrap_key' => self::scalarString($store['data']['wrap_key'] ?? ''),
    ]);
  }

  /**
   * Bootstrap business DEK wraps for every active member in the business.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function bootstrapBusinessDekForAllMembers(
    string $actorUUID,
    string $businessId,
    string $segment,
    string $version
  ): array {
    $actorUUID = trim(InputSanitizer::sanitizeString($actorUUID));
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    $segment = trim(InputSanitizer::sanitizeString($segment));
    $version = trim(InputSanitizer::sanitizeString($version));

    if ($actorUUID === '' || $businessId === '' || $segment === '' || $version === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_ACTOR_BUSINESS_SEGMENT_AND_VERSION_ARE_REQUIRED'));
    }

    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($businessId, $actorUUID)) {
      return $this->fail(Strings::i18n('BUSINESSES_API_ONLY_OWNERS_AND_MANAGERS_CAN_BOOTSTRAP_BUSINESS_DEKS_FOR_MEMB'));
    }

    $allowedSegments = [
      self::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD => true,
      self::BUSINESS_DEK_SEGMENT_ARCHIVE => true,
    ];
    if (!isset($allowedSegments[$segment])) {
      return $this->fail(Strings::i18n('BUSINESSES_API_INVALID_BUSINESS_DEK_SEGMENT'));
    }

    $members = array_map(
      static fn (mixed $value): string => trim((string) $value),
      Database::smembers(Keys::BUSINESS_MEMBERS . ':' . $businessId)
    );
    $members = array_values(array_unique(array_filter($members, static fn (string $uuid): bool => $uuid !== '')));
    sort($members, SORT_STRING);

    if ($members === []) {
      return $this->fail(Strings::i18n('BUSINESSES_API_NO_BUSINESS_MEMBERS_WERE_FOUND_TO_BOOTSTRAP'));
    }

    $bootstrapped = [];
    $failed = [];

    foreach ($members as $memberUUID) {
      $connection = $this->connection($businessId, $memberUUID);
      if ((string) ($connection['status'] ?? '') !== self::MEMBERSHIP_STATE_ACTIVE) {
        $failed[] = [
          'user_uuid' => $memberUUID,
          'reason' => 'inactive_membership',
          'message' => 'Membership is not active.',
        ];
        continue;
      }

      $consent = $this->loadActiveBusinessConsent($businessId, $memberUUID);
      $consentId = self::scalarString($consent['consent_id'] ?? '');
      if ($consentId === '') {
        $failed[] = [
          'user_uuid' => $memberUUID,
          'reason' => 'missing_active_consent',
          'message' => 'No active DEK-sharing consent found for member.',
        ];
        continue;
      }

      $result = $this->bootstrapBusinessDekWrapForMember($businessId, $memberUUID, $consentId, $segment, $version);
      if ($result['success']) {
        $bootstrapped[] = [
          'user_uuid' => $memberUUID,
          'dek_id' => self::scalarString($result['data']['dek_id'] ?? ''),
          'wrap_key' => self::scalarString($result['data']['wrap_key'] ?? ''),
          'credential_id' => self::scalarString($result['data']['credential_id'] ?? ''),
          'consent_id' => self::scalarString($result['data']['consent_id'] ?? ''),
        ];
      } else {
        $failed[] = [
          'user_uuid' => $memberUUID,
          'reason' => 'bootstrap_failed',
          'message' => self::scalarString($result['message']),
        ];
      }
    }

    if ($bootstrapped !== []) {
      $timestamp = date('c');
      $registryKey = Keys::businessDekRegistry($businessId, $segment);
      $versionKey = Keys::businessDekVersion($businessId, $segment, $version);

      Database::hset($registryKey, [
        'business_id' => $businessId,
        'segment' => $segment,
        'active_version' => $version,
        'updated_at' => $timestamp,
        'updated_by' => $actorUUID,
      ]);

      Database::hset($versionKey, [
        'business_id' => $businessId,
        'segment' => $segment,
        'version' => $version,
        'status' => self::MEMBERSHIP_STATE_ACTIVE,
        'updated_at' => $timestamp,
        'updated_by' => $actorUUID,
        'bootstrapped_member_count' => (string) count($bootstrapped),
      ]);

      foreach ($bootstrapped as $entry) {
        $memberUUID = self::scalarString($entry['user_uuid']);
        $dekId = self::scalarString($entry['dek_id']);
        if ($memberUUID === '' || $dekId === '') {
          continue;
        }

        Database::hset($versionKey, [
          'member_dek_id:' . $memberUUID => $dekId,
        ]);
      }
    }

    $this->appendAuditEvent($businessId, 'business.dek.wrap.bootstrap.bulk', $actorUUID, [
      'segment' => $segment,
      'key_version' => $version,
      'bootstrapped_count' => count($bootstrapped),
      'failed_count' => count($failed),
    ]);

    if ($bootstrapped === []) {
      return $this->fail(Strings::i18n('BUSINESSES_API_NO_BUSINESS_MEMBER_DEK_WRAPS_WERE_BOOTSTRAPPED'), [
        'business_id' => $businessId,
        'segment' => $segment,
        'key_version' => $version,
        'bootstrapped' => [],
        'failed' => $failed,
        'bootstrapped_count' => 0,
        'failed_count' => count($failed),
      ]);
    }

    $message = $failed === []
      ? 'Business DEK wraps bootstrapped for all active members.'
      : 'Business DEK wraps bootstrapped with partial member failures.';

    return $this->ok($message, [
      'business_id' => $businessId,
      'segment' => $segment,
      'key_version' => $version,
      'bootstrapped' => $bootstrapped,
      'failed' => $failed,
      'bootstrapped_count' => count($bootstrapped),
      'failed_count' => count($failed),
    ]);
  }

  /**
   * Auto-bootstrap business DEK wraps for all active businesses on user page visit.
   *
   * Iterates the user's active business memberships and triggers DEK bootstrapping
   * where the actor has manage-access permission. Uses a per-business throttle key
   * to avoid redundant work within a short window.
   *
   * @param string $actorUUID Authenticated user UUID.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function autoBootstrapBusinessDekOnPageVisit(string $actorUUID): array
  {
    $actorUUID = trim(InputSanitizer::sanitizeString($actorUUID));
    if ($actorUUID === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_ACTOR_IS_REQUIRED'));
    }

    $list = $this->listForUser($actorUUID);
    if (!$list['success']) {
      return $list;
    }

    $businesses = is_array($list['data']['businesses'] ?? null)
      ? $list['data']['businesses']
      : [];

    $attemptedBusinesses = [];
    $skippedBusinesses = [];
    $throttleSeconds = 300;

    foreach ($businesses as $business) {
      if (!is_array($business)) {
        continue;
      }

      $businessId = self::scalarString($business['business_id'] ?? '');
      if ($businessId === '') {
        continue;
      }

      $businessStatus = strtolower(self::scalarString($business['status'] ?? ''));
      $connectionStatus = strtolower(self::scalarString($business['connection_status'] ?? ''));
      if ($businessStatus !== self::MEMBERSHIP_STATE_ACTIVE || $connectionStatus !== self::MEMBERSHIP_STATE_ACTIVE) {
        $skippedBusinesses[] = ['business_id' => $businessId, 'reason' => 'inactive'];
        continue;
      }

      if (!$this->canManageAccess($businessId, $actorUUID)) {
        $skippedBusinesses[] = ['business_id' => $businessId, 'reason' => 'insufficient_access'];
        continue;
      }

      $throttleKey = Keys::TELEMETRY . ':business:dek:auto_bootstrap:' . $businessId . ':' . $actorUUID;
      // setnx is atomic (SET NX EX); eliminates the exists()→set() TOCTOU race.
      if (!Database::setnx($throttleKey, '1', $throttleSeconds)) {
        $skippedBusinesses[] = ['business_id' => $businessId, 'reason' => 'throttled'];
        continue;
      }

      $result = $this->bootstrapBusinessDekForAllMembers(
        $actorUUID,
        $businessId,
        self::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
        self::BUSINESS_DEK_VERSION_CURRENT
      );

      $attemptedBusinesses[] = [
        'business_id' => $businessId,
        'success' => (bool) $result['success'],
        'bootstrapped_count' => self::scalarInt($result['data']['bootstrapped_count'] ?? 0),
        'failed_count' => self::scalarInt($result['data']['failed_count'] ?? 0),
        'message' => self::scalarString($result['message']),
      ];
    }

    return $this->ok('Auto-bootstrap evaluated for active businesses.', [
      'attempted_businesses' => $attemptedBusinesses,
      'skipped_businesses' => $skippedBusinesses,
      'attempted_count' => count($attemptedBusinesses),
      'skipped_count' => count($skippedBusinesses),
    ]);
  }

  /**
   * Build a stable business DEK identifier for a member and segment.
   */
  private function businessDekId(string $businessId, string $segment, string $userUUID, string $version): string
  {
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    $segment = trim(InputSanitizer::sanitizeString($segment));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    $version = trim(InputSanitizer::sanitizeString($version));

    if ($businessId === '' || $segment === '' || $userUUID === '' || $version === '') {
      return '';
    }

    $normalizedVersion = preg_replace('/^v/i', '', $version) ?? $version;
    if ($normalizedVersion === '') {
      return '';
    }

    return 'business-dek:' . $businessId . ':' . $segment . ':' . $userUUID . ':v' . $normalizedVersion;
  }

  /**
   * Resolve a member passkey wrapper suitable for business-wrap bootstrapping.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function resolveMemberPasskeyWrapForBusinessBootstrap(string $userUUID): array
  {
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    if ($userUUID === '') {
      return $this->fail(Strings::i18n('BUSINESSES_API_CANNOT_RESOLVE_PASSKEY_WRAPPER_USER_ID_IS_REQUIRED'));
    }

    $wrappedMapKey = Keys::USER . ':' . $userUUID . ':passkey_wrapped_deks';
    $wrappedMap = Database::hgetall($wrappedMapKey);

    // Prefer active session credential when it belongs to the same user.
    $sessionHash = Authentication::getSessionHashFromCookie();
    if (is_string($sessionHash) && $sessionHash !== '') {
      $sessionKey = Keys::SESSION . ':' . $sessionHash;
      $sessionUserUUID = self::scalarString(Database::hget($sessionKey, 'user_uuid'));
      $sessionCredentialId = self::scalarString(Database::hget($sessionKey, 'credential_id'));
      if ($sessionUserUUID === $userUUID && $sessionCredentialId !== '') {
        $sessionWrapped = self::scalarString($wrappedMap[$sessionCredentialId] ?? '');
        if ($sessionWrapped !== '') {
          return $this->ok('Resolved passkey wrapper from active credential.', [
            'credential_id' => $sessionCredentialId,
            'wrapped_dek' => $sessionWrapped,
          ]);
        }
      }
    }

    foreach ($wrappedMap as $credentialId => $candidateWrapped) {
      $credentialId = trim((string) $credentialId);
      $candidateWrapped = self::scalarString($candidateWrapped);
      if ($credentialId !== '' && $candidateWrapped !== '') {
        return $this->ok('Resolved passkey wrapper from stored credential map.', [
          'credential_id' => $credentialId,
          'wrapped_dek' => $candidateWrapped,
        ]);
      }
    }

    return $this->fail(Strings::i18n('BUSINESSES_API_NO_PASSKEY_WRAPPED_DEK_IS_AVAILABLE_FOR_THIS_MEMBER'));
  }

  /**
   * Normalize a mixed scalar value to string.
   */
  private static function scalarString(mixed $value): string
  {
    return is_scalar($value) ? (string) $value : '';
  }

  /**
   * Normalize a mixed numeric value to int.
   */
  private static function scalarInt(mixed $value): int
  {
    if (is_int($value)) {
      return $value;
    }

    if (is_numeric($value)) {
      return (int) $value;
    }

    return 0;
  }

  /** @return array<int, string> */
  private function scopeList(string $scopeCSV): array
  {
    if ($scopeCSV === 'all') {
      return ['all'];
    }

    $scopeSet = [];
    foreach (array_map('trim', explode(',', $scopeCSV)) as $scope) {
      if ($scope === '') {
        continue;
      }

      if ($scope === 'work.self.write') {
        $scopeSet['work.write'] = true;
        $scopeSet['work.scope.self'] = true;
        continue;
      }

      $scopeSet[$scope] = true;
    }

    $scopes = array_keys($scopeSet);
    sort($scopes, SORT_STRING);

    return $scopes;
  }

  /** @return array<string, bool> */
  private function scopeMap(string $scopeCSV): array
  {
    $map = [];
    foreach ($this->scopeList($scopeCSV) as $scope) {
      $map[$scope] = true;
    }

    return $map;
  }

  /** @param array<string, string> $connection */
  private function setConnection(string $businessId, string $userUUID, array $connection): void
  {
    // Keep BusinessWorkspaceCache::invalidate in this mutation funnel.
    $newRole = strtolower(trim((string) ($connection['role'] ?? '')));
    if ($newRole !== '' && !isset(self::VALID_BUSINESS_ROLES[$newRole])) {
      throw new InvalidArgumentException("Invalid business role: {$newRole}");
    }

    if ($newRole !== '') {
      $connection['role'] = $newRole;
    }

    if (array_key_exists('role', $connection) || array_key_exists('scopes', $connection)) {
      $connection['scope_preset_version'] = self::SCOPE_PRESET_VERSION;
      $connection['scope_policy_version'] = self::SCOPE_POLICY_VERSION;
    }

    $existing = $this->connection($businessId, $userUUID);
    $currentStatus = (string) ($existing['status'] ?? '');
    $newStatus = (string) ($connection['status'] ?? $currentStatus);
    $effectiveRole = $newRole !== '' ? $newRole : strtolower(trim((string) ($existing['role'] ?? '')));

    if (
      (bool) SystemConfig::get('business_shared_encryption_enabled')
      && $newStatus === self::MEMBERSHIP_STATE_ACTIVE
      && $currentStatus !== self::MEMBERSHIP_STATE_ACTIVE
      && $effectiveRole !== 'owner'
    ) {
      if ($currentStatus !== self::MEMBERSHIP_STATE_CONSENTED) {
        throw new InvalidArgumentException(
          "Invalid connection activation: '{$currentStatus}' must transition through consented"
        );
      }

      $consentId = is_scalar($connection['consent_id'] ?? null)
        ? trim((string) $connection['consent_id'])
        : '';
      if (!$this->isConsentValidForWrap($businessId, $userUUID, $consentId)) {
        throw new InvalidArgumentException('Invalid connection activation: active consent is required');
      }
    }

    if ($currentStatus !== $newStatus) {
      $allowed = self::CONNECTION_TRANSITIONS[$currentStatus] ?? [];
      if (!isset($allowed[$newStatus])) {
        throw new InvalidArgumentException(
          "Invalid connection transition: '{$currentStatus}' → '{$newStatus}'"
        );
      }
    }

    $fields = array_merge(['business_id' => $businessId, 'user_uuid' => $userUUID], $connection);

    $hasChange = false;
    foreach ($connection as $field => $value) {
      if (!array_key_exists($field, $existing) || $existing[$field] !== (string) $value) {
        $hasChange = true;
        break;
      }
    }
    if ($hasChange) {
      $fields['updated_at'] = date('c');
    }

    $activeStatuses = [self::MEMBERSHIP_STATE_ACTIVE => true];
    $indexedConnectionStatuses = [
      self::MEMBERSHIP_STATE_ACTIVE => true,
      self::MEMBERSHIP_STATE_CONSENTED => true,
      self::MEMBERSHIP_STATE_PENDING => true,
    ];
    $pendingStatuses = [self::MEMBERSHIP_STATE_PENDING => true];

    $isNowActive = isset($activeStatuses[$newStatus]);
    $wasActive = isset($activeStatuses[$currentStatus]);
    $isNowIndexedConnection = isset($indexedConnectionStatuses[$newStatus]);
    $wasIndexedConnection = isset($indexedConnectionStatuses[$currentStatus]);
    $isNowPending = isset($pendingStatuses[$newStatus]);
    $wasPending = isset($pendingStatuses[$currentStatus]);

    $relKey = $this->connectionKey($businessId, $userUUID);
    $membersKey = Keys::BUSINESS_MEMBERS . ':' . $businessId;
    $userKey = Keys::BUSINESS_USER . ':' . $userUUID;
    $connectionsKey = Keys::BUSINESS_CONNECTIONS . ':' . $businessId;
    $connectionsUserKey = Keys::BUSINESS_CONNECTIONS_USER . ':' . $userUUID;
    $pendingKey = Keys::BUSINESS_PENDING . ':' . $businessId;

    Database::transaction(static function (\Redis $r) use (
      $relKey,
      $membersKey,
      $userKey,
      $connectionsKey,
      $connectionsUserKey,
      $pendingKey,
      $businessId,
      $userUUID,
      $fields,
      $isNowActive,
      $wasActive,
      $isNowIndexedConnection,
      $wasIndexedConnection,
      $isNowPending,
      $wasPending,
    ): void {
      $r->hMSet($relKey, $fields);

      if ($isNowActive && !$wasActive) {
        $r->sAdd($membersKey, $userUUID);
        $r->sAdd($userKey, $businessId);
      } elseif (!$isNowActive && $wasActive) {
        $r->sRem($membersKey, $userUUID);
        $r->sRem($userKey, $businessId);
      }

      if ($isNowIndexedConnection && !$wasIndexedConnection) {
        $r->sAdd($connectionsKey, $userUUID);
        $r->sAdd($connectionsUserKey, $businessId);
      } elseif (!$isNowIndexedConnection && $wasIndexedConnection) {
        $r->sRem($connectionsKey, $userUUID);
        $r->sRem($connectionsUserKey, $businessId);
      }

      if ($isNowPending && !$wasPending) {
        $r->sAdd($pendingKey, $userUUID);
      } elseif (!$isNowPending && $wasPending) {
        $r->sRem($pendingKey, $userUUID);
      }
    });

    // Every membership mutation (invite accepted, role updated, revoke,
    // withdraw, ownership transfer) funnels through this method, making it
    // the single eager-invalidation point for the materialized members cache.
    BusinessWorkspaceCache::invalidate($businessId);
  }

  /**
   * Resolve a named role preset into a concrete role string and sorted scope CSV.
   * Returns ['role' => string, 'scopes' => string] or null if the preset is unknown.
   *
   * @return array{role: string, scopes: string}|null
   */
  public function resolveRolePreset(string $presetName): ?array
  {
    $name = strtolower(trim($presetName));

    if ($name === 'owner') {
      return ['role' => 'owner', 'scopes' => 'all'];
    }

    if (!isset(self::ROLE_SCOPE_PRESETS[$name])) {
      return null;
    }

    $scopes = self::ROLE_SCOPE_PRESETS[$name];
    sort($scopes, SORT_STRING);

    return [
      'role'   => $name,
      'scopes' => implode(',', $scopes),
    ];
  }

  /**
   * Maps an business role to its hierarchy rank.
   */
  private static function roleRank(string $role): int
  {
    return match (strtolower(trim($role))) {
      'owner' => 5,
      'coordinator' => 4,
      'contributor' => 3,
      'member' => 2,
      'viewer' => 1,
      default => 0,
    };
  }

  /** @return array<string, string> */
  private function connection(string $businessId, string $userUUID): array
  {
    return Database::hgetall($this->connectionKey($businessId, $userUUID));
  }

  /** @return list<string> */
  private function connectionBusinessIdsForUser(string $userUUID): array
  {
    $userUUID = trim($userUUID);
    if ($userUUID === '') {
      return [];
    }

    $ids = array_merge(
      Database::smembers(Keys::BUSINESS_USER . ':' . $userUUID),
      Database::smembers(Keys::BUSINESS_CONNECTIONS_USER . ':' . $userUUID),
    );

    $connectionPrefix = Keys::BUSINESS_CONNECTION . ':';
    $connectionSuffix = ':' . $userUUID;
    foreach (Database::scanKeys($connectionPrefix . '*:' . $userUUID) as $connectionKey) {
      $key = trim((string) $connectionKey);
      if (!str_starts_with($key, $connectionPrefix) || !str_ends_with($key, $connectionSuffix)) {
        continue;
      }

      $businessId = substr($key, strlen($connectionPrefix), -strlen($connectionSuffix));
      if ($businessId !== '') {
        $ids[] = $businessId;
      }
    }

    $ids = array_values(array_unique(array_filter(
      array_map(static fn (mixed $value): string => trim((string) $value), $ids),
      static fn (string $value): bool => $value !== ''
    )));
    sort($ids, SORT_STRING);

    return $ids;
  }

  /**
   * Handles connectionKey operation.
   */
  private function connectionKey(string $businessId, string $userUUID): string
  {
    return Keys::BUSINESS_CONNECTION . ':' . $businessId . ':' . $userUUID;
  }

  /**
   * Handles accessRequestActiveKey operation.
   */
  private function accessRequestActiveKey(string $businessId, string $requesterUUID): string
  {
    return Keys::BUSINESS_ACCESS_REQUEST_ACTIVE . ':' . $businessId . ':' . $requesterUUID;
  }

  /**
   * Handles incrementAccessRequestTelemetry operation.
   */
  private function incrementAccessRequestTelemetry(string $metric): void
  {
    $suffix = trim(InputSanitizer::sanitizeString($metric));
    if ($suffix === '') {
      return;
    }

    $day = date('Y-m-d');
    $count = Database::incr(Keys::TELEMETRY . ':business:access_request:' . $suffix . ':' . $day);
    // Set a 90-day TTL on first increment so daily buckets self-expire.
    // Without this, keys accumulate indefinitely and exhaust Redis keyspace.
    if (1 === $count) {
      Database::expire(Keys::TELEMETRY . ':business:access_request:' . $suffix . ':' . $day, 90 * 24 * 3600);
    }
  }

  /**
   * Handles findPersonalBusinessId operation.
   */
  private function findPersonalBusinessId(string $ownerUUID): string
  {
    foreach (Database::smembers(Keys::BUSINESS_OWNER . ':' . $ownerUUID) as $businessId) {
      $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
      $type = (string) ($business['business_type'] ?? $business['organization_type'] ?? 'shared');
      if ($type === 'personal') {
        return (string) $businessId;
      }
    }

    return '';
  }

  /**
   * Handles findOwnedSharedBusinessId operation.
   */
  private function findOwnedSharedBusinessId(string $ownerUUID): string
  {
    foreach (Database::smembers(Keys::BUSINESS_OWNER . ':' . $ownerUUID) as $businessId) {
      $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
      if ($business === []) {
        continue;
      }

      $type = (string) ($business['business_type'] ?? 'shared');
      if ($type !== 'shared') {
        continue;
      }

      $status = strtolower((string) ($business['status'] ?? 'active'));
      if ($status === 'archived' || $status === 'deleted' || $status === 'disabled') {
        continue;
      }

      return (string) $businessId;
    }

    return '';
  }

  /**
   * Handles findPreferredBusinessIdForOwner operation.
   */
  private function findPreferredBusinessIdForOwner(string $ownerUUID): string
  {
    $businessIds = Database::smembers(Keys::BUSINESS_OWNER . ':' . $ownerUUID);
    sort($businessIds, SORT_STRING);

    $personalFallback = '';
    foreach ($businessIds as $businessId) {
      $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
      if ([] === $business) {
        continue;
      }

      if ((string) ($business['status'] ?? 'active') !== 'active') {
        continue;
      }

      $type = (string) ($business['business_type'] ?? 'shared');
      if ($type === 'shared') {
        return (string) $businessId;
      }

      if ($type === 'personal' && $personalFallback === '') {
        $personalFallback = (string) $businessId;
      }
    }

    return $personalFallback;
  }

  /**
   * Handles normalizeBusinessType operation.
   */
  private function normalizeBusinessType(mixed $value): string
  {
    $type = is_scalar($value) ? strtolower(trim((string) $value)) : 'shared';

    return in_array($type, ['personal', 'shared'], true) ? $type : '';
  }

  /**
   * Handles normalizeSettingText operation.
   */
  private function normalizeSettingText(mixed $value, int $maxLen): string
  {
    if (!is_scalar($value)) {
      return '';
    }

    $text = trim((string) $value);
    $text = str_replace("\0", '', $text);

    if (strlen($text) > $maxLen) {
      $text = substr($text, 0, $maxLen);
    }

    return $text;
  }

  /**
   * Handles defaultPersonalBusinessName operation.
   */
  private function defaultPersonalBusinessName(User $owner, string $fallback = 'Personal Business'): string
  {
    $fullName = trim($owner->full_name);
    if ($fullName !== '') {
      return $fullName;
    }

    return $fallback;
  }

  /** @return array<string, string> */
  private function defaultSettingsForOwner(User $owner): array
  {
    $payFrequency = $owner->pay_frequency ?: PayFrequency::BIWEEKLY->value;
    $payPeriodStart = $owner->pay_period_start ?: '2024-01-01';

    return [
      'pay_frequency' => $payFrequency,
      'pay_anchor' => $owner->pay_anchor ?: 'Monday',
      'pay_period_length' => $owner->pay_period_length ?: '14',
      'pay_period_start' => $payPeriodStart,
      'pay_epoch' => $owner->pay_epoch ?: $payPeriodStart,
      'editing_grace_days' => (string) $owner->editing_grace_days,
      'default_wage' => (string) ($owner->pay_rate ?? ''),
      'timezone' => $owner->timezone ?: self::DEFAULT_TIMEZONE,
      'currency' => self::DEFAULT_CURRENCY,
      'enforce_contact_domain' => '0',
      'allowed_contact_domains' => '',
    ];
  }

  /** @param array<string, string> $business
   *  @param array<string, string> $normalizedSettings */
  private function syncPersonalBusinessSettingsToOwner(string $businessId, array $business, array $normalizedSettings): void
  {
    $ownerUUID = (string) ($business['owner_uuid'] ?? '');
    if ($ownerUUID === '') {
      return;
    }

    $owner = UserRepository::getByUUID($ownerUUID);
    if (null === $owner) {
      return;
    }

    $userSettings = [];
    foreach (['pay_frequency', 'pay_anchor', 'pay_period_length', 'pay_period_start', 'pay_epoch', 'editing_grace_days', 'timezone'] as $field) {
      if (isset($normalizedSettings[$field])) {
        $userSettings[$field] = $normalizedSettings[$field];
      }
    }

    if ([] === $userSettings) {
      return;
    }

    $owner->updateSettings($userSettings);

    try {
      $persistedOwner = UserRepository::getByUUID($ownerUUID);
      if (null !== $persistedOwner) {
        PayPeriodGenerator::regenerateForUser($persistedOwner);
        WorkEntryLockService::clearCache($ownerUUID);
      }
    } catch (\Throwable $e) {
      Log::error(
        '[Business] Personal business pay period sync failed: ' . $e->getMessage(),
        'business_id=' . $businessId . ' owner_uuid=' . $ownerUUID
      );
    }
  }

  /**
   * Handles isValidYmdDate operation.
   */
  private function isValidYmdDate(string $value): bool
  {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
      return false;
    }

    $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

    return false !== $dt && $dt->format('Y-m-d') === $value;
  }

  /**
   * Handles generateBusinessId operation.
   */
  private function generateBusinessId(string $ownerUUID, string $name): string
  {
    return 'ORG' . substr(hash('sha256', $ownerUUID . '|' . $name . '|' . bin2hex(random_bytes(16))), 0, 18);
  }

  private function canCreateBusinessToday(string $ownerUUID): bool
  {
    $key = 'business:create:count:' . $ownerUUID . ':' . date('Y-m-d');
    $count = (int) (Database::get($key) ?: 0);

    return $count < 3;
  }

  private function incrementBusinessCreateCount(string $ownerUUID): void
  {
    $key = 'business:create:count:' . $ownerUUID . ':' . date('Y-m-d');
    $count = (int) (Database::get($key) ?: 0);
    Database::set($key, (string) ($count + 1), 86400);
  }

  /** @param array<string, scalar|array<mixed>> $details */
  private function appendAuditEvent(string $businessId, string $eventType, string $actorUUID, array $details = []): void
  {
    $eventID = 'OAE' . substr(hash('sha256', $businessId . '|' . $eventType . '|' . bin2hex(random_bytes(16))), 0, 20);
    $createdAt = date('c');

    $normalizedDetails = [];
    foreach ($details as $key => $value) {
      if (is_array($value)) {
        $flat = [];
        foreach ($value as $item) {
          if (is_scalar($item) || $item === null) {
            $flat[] = (string) $item;
          }
        }
        $normalizedDetails[$key] = implode(',', $flat);
      } else {
        $normalizedDetails[$key] = (string) $value;
      }
    }

    Database::hset(Keys::BUSINESS_AUDIT_EVENT . ':' . $eventID, [
      'event_id' => $eventID,
      'business_id' => $businessId,
      'event_type' => $eventType,
      'actor_uuid' => $actorUUID,
      'details' => json_encode($normalizedDetails, JSON_UNESCAPED_SLASHES) ?: '{}',
      'created_at' => $createdAt,
    ]);

    Database::sadd(Keys::BUSINESS_AUDIT . ':' . $businessId, $eventID);

    BusinessDashboardMetrics::touchLastActivity($businessId, $createdAt);

    // Mirror security-significant events into the immutable TheLedger chain.
    // The system audit stream is the authoritative SOC2 evidence ledger; business
    // events classified as critical or high must appear there for CC6.1/CC6.2/
    // CC6.7/CC9.1 evidence. Fire-and-forget: ledger failure must not block the
    // business audit write or the calling business operation.
    if (isset(self::LEDGER_EVENTS[$eventType])) {
      try {
        SystemAuditRepository::append(
          'business.' . $eventType,
          $actorUUID,
          array_merge($normalizedDetails, [
            'business_id'   => $businessId,
            'business_event_id' => $eventID,
            'ledger_event_level' => self::LEDGER_EVENTS[$eventType],
          ])
        );
      } catch (\Throwable) {
        // Intentionally silent: ledger mirroring failure must not disrupt
        // the underlying org mutation or audit write.
      }
    }

    BusinessSignalHooks::onBusinessAuditEvent([
      'event_id' => $eventID,
      'business_id' => $businessId,
      'event_type' => $eventType,
      'actor_uuid' => $actorUUID,
      'details' => json_encode($normalizedDetails, JSON_UNESCAPED_SLASHES) ?: '{}',
      'created_at' => $createdAt,
    ]);

    (new BusinessNotificationService())->fanoutAuditEvent(
      orgId: $businessId,
      eventType: $eventType,
      actorUUID: $actorUUID,
      details: $normalizedDetails,
      createdAt: $createdAt
    );
  }

  /**
   * Append an audit event to the business's event log.
   *
   * Sanitizes all inputs and delegates to appendAuditEvent(). Returns void;
   * callers should log failures through their own error handling.
   *
   * @param string               $businessId     Business ID.
   * @param string               $eventType Dot-separated event type (e.g. 'membership.revoked').
   * @param string               $actorUUID UUID of the actor generating the event.
   * @param array<string, scalar|array<mixed>> $details   Supplemental key→value event details.
   */
  public function appendBusinessAuditEvent(string $businessId, string $eventType, string $actorUUID, array $details = []): void
  {
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    $eventType = trim(InputSanitizer::sanitizeString($eventType));
    $actorUUID = trim(InputSanitizer::sanitizeString($actorUUID));

    if ($businessId === '' || $eventType === '' || $actorUUID === '') {
      return;
    }

    $this->appendAuditEvent($businessId, $eventType, $actorUUID, $details);
  }

  /**
   * Return whether the actor has permission to trigger an audit control test.
   *
   * Requires manage-access or manage-business scope, and the org must not
   * be an admin-preview org.
   *
   * @param string $businessId      Business ID.
   * @param string $actorUUID  Authenticated actor UUID.
   */
  public function canTriggerAuditControlTest(string $businessId, string $actorUUID): bool
  {
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    $actorUUID = trim(InputSanitizer::sanitizeString($actorUUID));

    if ($businessId === '' || $actorUUID === '') {
      return false;
    }

    if (null !== $this->requireAdminPreviewOrSelfOrg($actorUUID, $businessId)) {
      return false;
    }

    return $this->canManageAccess($businessId, $actorUUID) || $this->canManageBusiness($businessId, $actorUUID);
  }

  /**
   * Return the raw connection hash for a user in an business.
   *
   * Returns an empty array when no connection record exists.
   *
   * @param string $businessId     Business ID.
   * @param string $userUUID  User UUID to look up.
   *
   * @return array<string, string>
   */
  public function getConnectionSummary(string $businessId, string $userUUID): array
  {
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));

    if ($businessId === '' || $userUUID === '') {
      return [];
    }

    return $this->connection($businessId, $userUUID);
  }

  /** @param array<string, mixed> $data
   *  @return array{success: bool, message: string, data: array<string, mixed>} */
  private function ok(string $message, array $data = []): array
  {
    return [
      'success' => true,
      'message' => $message,
      'data' => $data,
    ];
  }

  /** @param array<string, mixed> $data
   *  @return array{success: bool, message: string, data: array<string, mixed>} */
  private function fail(string $message, array $data = []): array
  {
    return [
      'success' => false,
      'message' => $message,
      'data' => $data,
    ];
  }
}
