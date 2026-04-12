<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\Currency;
use PayCal\Domain\Enums\PayFrequency;
use PayCal\Domain\Enums\Subscription;
use PayCal\Domain\Enums\Timezone;

/**
 * OrganizationDiscoveryService.php
 *
 * Purpose: Core organization domain service for membership, invites, access
 * requests, settings, audit history, and shared-encryption coordination.
 *
 * Developer notes:
 * - This class is intentionally the policy hub for organization behavior.
 *   Controllers should delegate here instead of re-implementing role/scope
 *   checks.
 * - Result contracts use the internal ok/fail array shape consistently.
 *   Maintain that shape so controllers and tests can consume responses without
 *   per-call branching rules.
 * - Membership roles, scope presets, and transition rules are tightly coupled.
 *   Change them together and review every access gate that consumes them.
 * - Organization writes should append audit events unless the path is strictly
 *   internal and side-effect free.
 * - Shared-encryption consent and DEK-wrap bootstrapping are security-critical.
 *   Avoid introducing alternate paths that bypass these checks.
 */
/**
 * Organization coordination and policy engine.
 *
 * Responsibilities:
 * - Create and manage organization relationships and role assignments.
 * - Enforce scope-based authorization for sites, work, settings, and audit.
 * - Drive invite, access-request, and notification workflows.
 * - Synchronize consent-driven shared encryption metadata for org members.
 *
 * This file is intentionally large because it centralizes cross-cutting org
 * rules. If extracting helpers, preserve this class as the canonical policy
 * entry point.
 */
final class OrganizationDiscoveryService
{
  /**
   * Allowed org-level relationship roles.
   * 'owner'       – full control, not revokable except via transfer
  * 'coordinator' – manager role with every permission except ownership transfer
   * 'contributor' – work and sites operator with delegated work write access
   * 'viewer'      – read-only access to non-org-sensitive data
  * 'member'      – baseline member with non-sensitive read access and self-scoped work write
   */
  public const MEMBERSHIP_STATE_ACTIVE = 'active';
  public const MEMBERSHIP_STATE_CONSENTED = 'consented';
  public const MEMBERSHIP_STATE_PENDING = 'pending';
  public const MEMBERSHIP_STATE_SUSPENDED = 'suspended';
  public const MEMBERSHIP_STATE_REVOKED = 'revoked';

  public const ENCRYPTION_MODE_PERSONAL = 'personal';
  public const ENCRYPTION_MODE_ORGANIZATION = 'organization';

  public const ORG_DEK_SEGMENT_CURRENT_PERIOD = 'current_period';
  public const ORG_DEK_SEGMENT_ARCHIVE = 'archive';

  private const DEFAULT_TIMEZONE = 'America/Edmonton';
  private const DEFAULT_CURRENCY = 'CAD';
  private const ACCESS_REQUEST_DEFAULT_SCOPES = ['sites.read', 'work.read'];
  private const BULK_IMPORT_MAX_INPUT_EMAILS = 500;
  private const BULK_IMPORT_MAX_ACCEPTED_EMAILS = 200;
  private const BULK_IMPORT_PREPARE_TTL_SECONDS = 1200;
  private const BULK_IMPORT_CHALLENGE_TTL_SECONDS = 600;
  private const BULK_IMPORT_CHALLENGE_MAX_ATTEMPTS = 5;

  /**
   * Allowed org-level relationship roles.
   * 'owner'       – full control, not revokable except via transfer
    * 'coordinator' – manager role: settings + access + audit
    * 'contributor' – work and sites write access
    * 'viewer'      – read-only access to non-org-sensitive data
    * 'member'      – baseline member with read-only access plus self-scoped work write
   *
   * @var array<string, bool>
   */
  public const VALID_ORG_ROLES = [
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
    'coordinator' => ['access.manage', 'audit.read', 'org.settings.read', 'org.settings.write', 'payperiod.read', 'payperiod.write', 'sites.read', 'sites.write', 'wage.read', 'wage.write', 'work.read', 'work.scope.org', 'work.write'],
    'contributor' => ['payperiod.read', 'sites.read', 'sites.write', 'wage.read', 'work.read', 'work.scope.org', 'work.write'],
    'viewer'      => ['payperiod.read', 'sites.read', 'wage.read', 'work.read'],
    'member'      => ['payperiod.read', 'sites.read', 'wage.read', 'work.read', 'work.scope.self', 'work.write'],
  ];

  /** @var array<string, bool> */
  private const ALLOWED_SCOPES = [
    'work.read' => true,
    'work.write' => true,
    'work.scope.self' => true,
    'work.scope.org' => true,
    'sites.read' => true,
    'sites.write' => true,
    'audit.read' => true,
    'payperiod.read' => true,
    'payperiod.write' => true,
    'wage.read' => true,
    'wage.write' => true,
    'org.settings.read' => true,
    'org.settings.write' => true,
    'access.manage' => true,
  ];

  /**
   * Valid status transitions for org relationships.
   * Key = current status (empty string = no prior relationship).
   * Value = set of allowed next statuses.
   *
   * @var array<string, array<string, bool>>
   */
  private const RELATIONSHIP_TRANSITIONS = [
    ''                               => [
      self::MEMBERSHIP_STATE_ACTIVE => true,
      self::MEMBERSHIP_STATE_CONSENTED => true,
      self::MEMBERSHIP_STATE_PENDING => true,
    ],
    self::MEMBERSHIP_STATE_PENDING   => [
      self::MEMBERSHIP_STATE_CONSENTED => true,
      self::MEMBERSHIP_STATE_ACTIVE => true,
      self::MEMBERSHIP_STATE_REVOKED => true,
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
    self::MEMBERSHIP_STATE_REVOKED   => [self::MEMBERSHIP_STATE_ACTIVE => true],
    'withdrawn'                      => [self::MEMBERSHIP_STATE_ACTIVE => true],
  ];

  /**
   * @param array<string, mixed> $options
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function createOrganization(string $ownerUUID, string $name, array $options = []): array
  {
    $normalizedName = trim(InputSanitizer::sanitizeString($name));
    if ('' === $normalizedName || strlen($normalizedName) < 2) {
      return $this->fail('Organization name must be at least 2 characters.');
    }

    $organizationType = $this->normalizeOrganizationType($options['organization_type'] ?? 'shared');
    if ($organizationType === '') {
      return $this->fail('Organization type is invalid.');
    }

    if ($organizationType !== 'personal' && !$this->canAccessPremiumFeatures($ownerUUID)) {
      return $this->premiumSubscriptionRequired();
    }

    $owner = UserRepository::getByUUID($ownerUUID);
    if (null === $owner) {
      return $this->fail('Organization owner not found.');
    }

    if ($organizationType === 'personal') {
      $existingPersonal = $this->findPersonalOrganizationId($ownerUUID);
      if ($existingPersonal !== '') {
        return $this->ok('Personal organization already exists.', [
          'organization_id' => $existingPersonal,
          'name' => (string) (Database::hget(Keys::ORGANIZATION . ':' . $existingPersonal, 'name') ?: $normalizedName),
        ]);
      }
    }

    $orgId = $this->generateOrganizationId($ownerUUID, $normalizedName);
    $orgKey = Keys::ORGANIZATION . ':' . $orgId;

    if (Database::exists($orgKey)) {
      return $this->fail('Organization already exists.', ['organization_id' => $orgId]);
    }

    $timestamp = date('c');
    $organizationName = $organizationType === 'personal'
      ? $this->defaultPersonalOrganizationName($owner, $normalizedName)
      : $normalizedName;
    $defaultSettings = $this->defaultSettingsForOwner($owner);

    Database::hset($orgKey, [
      'organization_id' => $orgId,
      'name' => $organizationName,
      'owner_uuid' => $ownerUUID,
      'organization_type' => $organizationType,
      'created_at' => $timestamp,
      'status' => 'active',
    ]);

    Database::hset(Keys::ORGANIZATION_SETTINGS . ':' . $orgId, $defaultSettings + [
      'last_updated_at' => $timestamp,
      'last_updated_by' => $ownerUUID,
    ]);

    Database::sadd(Keys::ORGANIZATION_OWNER . ':' . $ownerUUID, $orgId);

    $this->setRelationship($orgId, $ownerUUID, [
      'role' => 'owner',
      'status' => 'active',
      'scopes' => 'all',
      'invited_by' => $ownerUUID,
      'created_at' => $timestamp,
      'accepted_at' => $timestamp,
    ]);

    $this->appendAuditEvent($orgId, 'organization.created', $ownerUUID, [
      'name' => $organizationName,
      'owner_uuid' => $ownerUUID,
      'organization_type' => $organizationType,
    ]);

    return $this->ok('Organization created.', [
      'organization_id' => $orgId,
      'name' => $organizationName,
      'organization_type' => $organizationType,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function ensurePersonalOrganization(string $userUUID): array
  {
    $existingOrgId = $this->findPersonalOrganizationId($userUUID);
    if ($existingOrgId !== '') {
      $org = Database::hgetall(Keys::ORGANIZATION . ':' . $existingOrgId);

      return $this->ok('Personal organization already exists.', [
        'organization_id' => $existingOrgId,
        'name' => (string) ($org['name'] ?? 'Personal Organization'),
      ]);
    }

    $owner = UserRepository::getByUUID($userUUID);
    if (null === $owner) {
      return $this->fail('Cannot create personal organization for unknown user.');
    }

    $defaultName = $this->defaultPersonalOrganizationName($owner);

    return $this->createOrganization($userUUID, $defaultName, [
      'organization_type' => 'personal',
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function listForUser(string $userUUID): array
  {
    $this->ensurePersonalOrganization($userUUID);

    $notificationSummary = (new OrganizationNotificationService())->summarizeUnreadForUser($userUUID);
    $unreadByOrg = $notificationSummary['by_org'];

    $orgIds = Database::smembers(Keys::ORGANIZATION_USER . ':' . $userUUID);

    $organizations = [];
    foreach ($orgIds as $orgId) {
      $org = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
      if ([] === $org) {
        continue;
      }

      $relationship = $this->relationship($orgId, $userUUID);
      $relationshipStatus = (string) ($relationship['status'] ?? '');
      $organizationType = (string) ($org['organization_type'] ?? 'shared');
      $isOrganizationOwner = (string) ($org['owner_uuid'] ?? '') === $userUUID;
      $isCurrentRelationship = $relationshipStatus === 'active' || $relationshipStatus === 'pending';

      if (!$this->isSelfOrganization($org, $userUUID) && !$isOrganizationOwner && !$isCurrentRelationship) {
        continue;
      }

      $owner = UserRepository::getByUUID((string) ($org['owner_uuid'] ?? ''));
      $settings = Database::hgetall(Keys::ORGANIZATION_SETTINGS . ':' . $orgId);

      $organizations[] = [
        'organization_id' => $orgId,
        'name' => (string) ($org['name'] ?? ''),
        'owner_uuid' => (string) ($org['owner_uuid'] ?? ''),
        'owner_name' => $owner instanceof User ? $owner->full_name : '',
        'owner_email' => $owner instanceof User ? $owner->email : '',
        'organization_type' => $organizationType,
        'status' => (string) ($org['status'] ?? 'active'),
        'role' => (string) ($relationship['role'] ?? ''),
        'relationship_status' => $relationshipStatus,
        'scopes' => $this->scopeList((string) ($relationship['scopes'] ?? '')),
        'joined_at' => (string) ($relationship['accepted_at'] ?? $relationship['created_at'] ?? ''),
        'legal_name' => (string) ($settings['legal_name'] ?? ''),
        'industry' => (string) ($settings['industry'] ?? ''),
        'registration_number' => (string) ($settings['registration_number'] ?? ''),
        'tax_id' => (string) ($settings['tax_id'] ?? ''),
        'employee_count' => (string) ($settings['employee_count'] ?? ''),
        'founded_year' => (string) ($settings['founded_year'] ?? ''),
        'contact_email' => (string) ($settings['contact_email'] ?? ''),
        'contact_phone' => (string) ($settings['contact_phone'] ?? ''),
        'website' => (string) ($settings['website'] ?? ''),
        'address_line1' => (string) ($settings['address_line1'] ?? ''),
        'address_line2' => (string) ($settings['address_line2'] ?? ''),
        'address_city' => (string) ($settings['address_city'] ?? ''),
        'address_region' => (string) ($settings['address_region'] ?? ''),
        'address_postal' => (string) ($settings['address_postal'] ?? ''),
        'address_country' => (string) ($settings['address_country'] ?? ''),
        'support_hours' => (string) ($settings['support_hours'] ?? ''),
        'notification_unread_count' => (string) ((int) ($unreadByOrg[$orgId] ?? 0)),
        'has_unread_notifications' => ((int) ($unreadByOrg[$orgId] ?? 0)) > 0 ? '1' : '0',
      ];
    }

    usort($organizations, static function (array $a, array $b): int {
      $aType = (string) $a['organization_type'];
      $bType = (string) $b['organization_type'];
      if ($aType !== $bType) {
        return $aType === 'personal' ? -1 : 1;
      }

      return strcasecmp((string) $a['name'], (string) $b['name']);
    });

    return $this->ok('Organizations retrieved.', [
      'organizations' => $organizations,
      'notification_total_unread' => (int) $notificationSummary['total_unread'],
      'notification_unread_by_org' => $unreadByOrg,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function markOrganizationNotificationsRead(string $actorUUID, string $orgId): array
  {
    $orgId = trim(InputSanitizer::sanitizeString($orgId));
    if ($orgId === '') {
      return $this->fail('Organization not found.');
    }

    $organization = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    if ([] === $organization) {
      return $this->fail('Organization not found.');
    }

    $relationship = $this->relationship($orgId, $actorUUID);
    $isOwner = (string) ($organization['owner_uuid'] ?? '') === $actorUUID;
    $status = strtolower(trim((string) ($relationship['status'] ?? '')));
    if (!$isOwner && !in_array($status, [self::MEMBERSHIP_STATE_ACTIVE, self::MEMBERSHIP_STATE_PENDING], true)) {
      return $this->fail('You do not have permission to update organization notifications.');
    }

    $summary = (new OrganizationNotificationService())->markOrganizationRead($actorUUID, $orgId);

    return $this->ok('Organization notifications marked read.', [
      'organization_id' => $orgId,
      'total_unread' => (int) $summary['total_unread'],
      'unread_by_org' => $summary['by_org'],
    ]);
  }

  /** @param array<int, string> $scopes
   *  @return array{success: bool, message: string, data: array<string, mixed>} */
  public function sendInvite(string $actorUUID, string $orgId, string $inviteeEmail, array $scopes, ?string $batchCode = null): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($orgId, $actorUUID)) {
      return $this->fail('Only authorized organization managers can invite users.');
    }

    $email = InputSanitizer::sanitizeEmail($inviteeEmail);
    if ('' === $email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return $this->fail('A valid invite email is required.');
    }

    $domainGate = $this->ensureContactDomainPolicyAllowsEmail($orgId, $email, 'invite target email');
    if (!$domainGate['success']) {
      return $domainGate;
    }

    $normalizedScopes = $this->normalizeScopes($scopes);
    if ([] === $normalizedScopes) {
      return $this->fail('At least one valid scope is required.');
    }

    if ($this->hasReachedMemberLimit($orgId)) {
      return $this->fail('Organization has reached the maximum member limit of 1,000. Please remove members or contact us for enterprise limits.');
    }

    $normalizedBatchCode = $this->normalizeBatchCode($batchCode);
    $inviteId = 'INV' . substr(hash('sha256', $orgId . $email . bin2hex(random_bytes(16))), 0, 20);
    $token = bin2hex(random_bytes(24));
    $timestamp = date('c');
    $expiresAt = date('c', time() + 7 * 24 * 3600);

    $inviteUUID = UserRepository::getUUIDFromEmail($email);

    Database::hset(Keys::ORGANIZATION_INVITE . ':' . $inviteId, [
      'invite_id' => $inviteId,
      'organization_id' => $orgId,
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

    Database::set(Keys::ORGANIZATION_INVITE_TOKEN . ':' . $token, $inviteId, 7 * 24 * 3600);
    Database::sadd(Keys::ORGANIZATION_INVITE_EMAIL . ':' . $email, $inviteId);
    Database::sadd(Keys::ORGANIZATION_INVITE_ORG . ':' . $orgId, $inviteId);

    $organization = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    $inviter = UserRepository::getByUUID($actorUUID);
    $sent = false;
    if (defined('PHPUNIT_COMPOSER_INSTALL') || !defined('PC_NAME')) {
      $sent = true;
    } else {
      try {
        $sent = EmailGarum::sendOrganizationInvite(
          inviteToken: $token,
          inviteeEmail: $email,
          organizationName: (string) ($organization['name'] ?? 'Organization'),
          inviterName: (string) ($inviter?->full_name ?: $inviter?->email ?: 'PayCal User'),
          scopes: $normalizedScopes,
          batchCode: $normalizedBatchCode
        );
      } catch (\Throwable $_error) {
        $sent = false;
      }
    }

    $this->appendAuditEvent($orgId, 'invite.sent', $actorUUID, [
      'invite_id' => $inviteId,
      'invitee_email' => $email,
      'batch_code' => $normalizedBatchCode,
      'scopes' => implode(',', $normalizedScopes),
      'email_dispatch' => $sent ? 'sent' : 'failed',
    ]);

    return $this->ok('Organization invite created.', [
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
  public function prepareBulkInviteImport(string $actorUUID, string $orgId, string $rawEmails, array $scopes): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($orgId, $actorUUID)) {
      return $this->fail('Only authorized organization managers can import invites.');
    }

    $authority = $this->resolveBulkImportAuthority($actorUUID, $orgId);
    if (!$authority['success']) {
      return $authority;
    }

    $normalizedScopes = $this->normalizeScopes($scopes);
    if ([] === $normalizedScopes) {
      return $this->fail('At least one valid scope is required.');
    }

    $parsed = $this->parseBulkInviteEmails($rawEmails);
    if ($parsed['input_count'] > self::BULK_IMPORT_MAX_INPUT_EMAILS) {
      return $this->fail('Too many email entries. Maximum input is 500 addresses per import.');
    }

    $authorityData = $authority['data'];
    $authorityDomain = $this->arrayStringValue($authorityData, 'authority_domain');
    $orgIdClean = trim(InputSanitizer::sanitizeString($orgId));
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
        $relationship = $this->relationship($orgIdClean, $memberUUID);
        $status = (string) ($relationship['status'] ?? '');
        if ($status === 'active' || $status === 'pending') {
          $alreadyMember[] = $email;
          continue;
        }
      }

      $hasPendingInvite = false;
      foreach (Database::smembers(Keys::ORGANIZATION_INVITE_EMAIL . ':' . $email) as $inviteId) {
        $invite = Database::hgetall(Keys::ORGANIZATION_INVITE . ':' . $inviteId);
        if (
          [] !== $invite
          && (string) ($invite['organization_id'] ?? '') === $orgIdClean
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

    $importId = 'OIIMP' . substr(hash('sha256', $orgIdClean . '|' . $actorUUID . '|' . bin2hex(random_bytes(16))), 0, 20);
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

    $prepareKey = Keys::organizationInviteImportPrepare($importId);
    Database::hset($prepareKey, [
      'import_id' => $importId,
      'organization_id' => $orgIdClean,
      'actor_uuid' => $actorUUID,
      'authority_email' => $this->arrayStringValue($authorityData, 'authority_email'),
      'authority_domain' => $authorityDomain,
      'scopes' => $scopeCsv,
      'accepted_emails' => $acceptedJson,
      'summary' => $summaryJson,
      'created_at' => date('c'),
      'status' => 'prepared',
    ]);
    Database::expire($prepareKey, self::BULK_IMPORT_PREPARE_TTL_SECONDS);

    $this->appendAuditEvent($orgIdClean, 'invite.bulk_prepare', $actorUUID, [
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

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function startBulkInviteImportChallenge(string $actorUUID, string $orgId, string $importId): array
  {
    $prepare = $this->loadBulkImportPrepare($actorUUID, $orgId, $importId);
    if (!$prepare['success']) {
      return $prepare;
    }

    $prepareData = is_array($prepare['data']['prepare'] ?? null) ? $prepare['data']['prepare'] : [];
    $acceptedEmails = $this->decodeStringArray($this->arrayStringValue($prepareData, 'accepted_emails', '[]'));
    if ([] === $acceptedEmails) {
      return $this->fail('No eligible emails found for import.');
    }

    $challengeId = 'OICH' . substr(hash('sha256', $importId . '|' . bin2hex(random_bytes(12))), 0, 20);
    $code = Security::generateVerificationCode(6);
    $codeHash = hash('sha256', $code);
    $authorityEmail = $this->arrayStringValue($prepareData, 'authority_email');
    $challengeKey = Keys::organizationInviteImportChallenge($challengeId);

    Database::hset($challengeKey, [
      'challenge_id' => $challengeId,
      'import_id' => $importId,
      'organization_id' => trim(InputSanitizer::sanitizeString($orgId)),
      'actor_uuid' => $actorUUID,
      'code_hash' => $codeHash,
      'verify_attempts' => '0',
      'verified' => '0',
      'consumed' => '0',
      'created_at' => date('c'),
      'expires_at' => (string) (time() + self::BULK_IMPORT_CHALLENGE_TTL_SECONDS),
    ]);
    Database::expire($challengeKey, self::BULK_IMPORT_CHALLENGE_TTL_SECONDS + 60);

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
      return $this->fail('Unable to send verification code right now.');
    }

    $prepareKey = Keys::organizationInviteImportPrepare($importId);
    Database::hset($prepareKey, [
      'status' => 'challenge_sent',
      'challenge_id' => $challengeId,
      'challenge_sent_at' => date('c'),
    ]);

    $this->appendAuditEvent(trim(InputSanitizer::sanitizeString($orgId)), 'invite.bulk_challenge_started', $actorUUID, [
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

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function verifyBulkInviteImportChallenge(string $actorUUID, string $orgId, string $importId, string $challengeId, string $code): array
  {
    $prepare = $this->loadBulkImportPrepare($actorUUID, $orgId, $importId);
    if (!$prepare['success']) {
      return $prepare;
    }

    $challenge = $this->loadBulkImportChallenge($actorUUID, $orgId, $importId, $challengeId);
    if (!$challenge['success']) {
      return $challenge;
    }

    $challengeData = is_array($challenge['data']['challenge'] ?? null) ? $challenge['data']['challenge'] : [];
    $attempts = $this->arrayIntValue($challengeData, 'verify_attempts', 0);
    if ($attempts >= self::BULK_IMPORT_CHALLENGE_MAX_ATTEMPTS) {
      return $this->fail('Too many failed verification attempts.');
    }

    $expiresAt = $this->arrayIntValue($challengeData, 'expires_at', 0);
    if ($expiresAt <= 0 || time() > $expiresAt) {
      return $this->fail('Verification code has expired.');
    }

    $normalizedCode = strtoupper(trim(InputSanitizer::sanitizeString($code)));
    if (strlen($normalizedCode) !== 6) {
      return $this->fail('Verification code must be exactly 6 characters.');
    }

    $providedHash = hash('sha256', $normalizedCode);
    $storedHash = $this->arrayStringValue($challengeData, 'code_hash');
    if (!hash_equals($storedHash, $providedHash)) {
      Database::hset(Keys::organizationInviteImportChallenge($challengeId), [
        'verify_attempts' => (string) ($attempts + 1),
      ]);

      return $this->fail('Invalid verification code.');
    }

    Database::hset(Keys::organizationInviteImportChallenge($challengeId), [
      'verified' => '1',
      'verified_at' => date('c'),
    ]);

    Database::hset(Keys::organizationInviteImportPrepare($importId), [
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

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function commitBulkInviteImport(string $actorUUID, string $orgId, string $importId, string $challengeId): array
  {
    $prepare = $this->loadBulkImportPrepare($actorUUID, $orgId, $importId);
    if (!$prepare['success']) {
      return $prepare;
    }

    $challenge = $this->loadBulkImportChallenge($actorUUID, $orgId, $importId, $challengeId);
    if (!$challenge['success']) {
      return $challenge;
    }

    $challengeData = is_array($challenge['data']['challenge'] ?? null) ? $challenge['data']['challenge'] : [];
    if ($this->arrayStringValue($challengeData, 'verified', '0') !== '1') {
      return $this->fail('Verification is required before importing.');
    }

    if ($this->arrayStringValue($challengeData, 'consumed', '0') === '1') {
      return $this->fail('This import challenge has already been used.');
    }

    $prepareData = is_array($prepare['data']['prepare'] ?? null) ? $prepare['data']['prepare'] : [];
    $acceptedEmails = $this->decodeStringArray($this->arrayStringValue($prepareData, 'accepted_emails', '[]'));
    $scopeCsv = $this->arrayStringValue($prepareData, 'scopes');
    $scopes = $this->scopeList($scopeCsv);

    if ([] === $acceptedEmails) {
      return $this->fail('No eligible emails are available to import.');
    }

    if ([] === $scopes) {
      return $this->fail('Import scopes are missing. Please prepare again.');
    }

    $batchCode = $this->generateInviteBatchCode();
    $results = [];
    $successCount = 0;
    $failureCount = 0;
    foreach ($acceptedEmails as $email) {
      $inviteResult = $this->sendInvite($actorUUID, $orgId, $email, $scopes, $batchCode);
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

    Database::hset(Keys::organizationInviteImportChallenge($challengeId), [
      'consumed' => '1',
      'consumed_at' => date('c'),
    ]);

    Database::hset(Keys::organizationInviteImportPrepare($importId), [
      'status' => 'committed',
      'committed_at' => date('c'),
      'success_count' => (string) $successCount,
      'failure_count' => (string) $failureCount,
    ]);

    $this->appendAuditEvent(trim(InputSanitizer::sanitizeString($orgId)), 'invite.bulk_import_committed', $actorUUID, [
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

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function requestAccessByOwnerEmail(string $requesterUUID, string $ownerEmail): array
  {
    $normalizedOwnerEmail = InputSanitizer::sanitizeEmail($ownerEmail);
    if ('' === $normalizedOwnerEmail || !filter_var($normalizedOwnerEmail, FILTER_VALIDATE_EMAIL)) {
      return $this->fail('A valid organization owner email is required.');
    }

    $ownerUUID = UserRepository::getUUIDFromEmail($normalizedOwnerEmail);
    if ('' === $ownerUUID) {
      return $this->fail('No organization owner account was found for that email.');
    }

    if ($ownerUUID === $requesterUUID) {
      return $this->fail('You cannot request access to your own organization.');
    }

    $orgId = $this->findPreferredOrganizationIdForOwner($ownerUUID);
    if ('' === $orgId) {
      return $this->fail('No active organization was found for that owner.');
    }

    $existingRelationship = $this->relationship($orgId, $requesterUUID);
    if ([] !== $existingRelationship && (string) ($existingRelationship['status'] ?? '') === 'active') {
      return $this->fail('You already have active access to this organization.');
    }

    $activeKey = $this->accessRequestActiveKey($orgId, $requesterUUID);
    $activeRequestId = (string) Database::get($activeKey);
    if ($activeRequestId !== '') {
      $existingRequest = Database::hgetall(Keys::ORGANIZATION_ACCESS_REQUEST . ':' . $activeRequestId);
      if ([] !== $existingRequest && (string) ($existingRequest['status'] ?? '') === 'pending') {
        return $this->ok('Access request already pending for this organization.', [
          'request_id' => $activeRequestId,
          'organization_id' => $orgId,
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
    $organization = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    $organizationName = trim((string) ($organization['name'] ?? 'Organization'));
    if ($organizationName === '') {
      $organizationName = 'Organization';
    }

    if ($requesterContactEmail !== '') {
      $domainGate = $this->ensureContactDomainPolicyAllowsEmail($orgId, $requesterContactEmail, 'access requester email');
      if (!$domainGate['success']) {
        return $domainGate;
      }
    }

    // Block if a pending invite already exists for this user in this org (invite takes priority over request)
    if ($requesterContactEmail !== '') {
      foreach (Database::smembers(Keys::ORGANIZATION_INVITE_EMAIL . ':' . $requesterContactEmail) as $existingInviteId) {
        $existingInvite = Database::hgetall(Keys::ORGANIZATION_INVITE . ':' . $existingInviteId);
        if (
          [] !== $existingInvite &&
          (string) ($existingInvite['organization_id'] ?? '') === $orgId &&
          (string) ($existingInvite['status'] ?? '') === 'pending'
        ) {
          return $this->fail('You already have a pending invite for this organization. Check your email to accept it.');
        }
      }
    }

    $requestId = 'OAR' . substr(hash('sha256', $orgId . '|' . $requesterUUID . '|' . bin2hex(random_bytes(16))), 0, 20);
    $createdAt = date('c');
    $expiresAt = date('c', time() + 14 * 24 * 3600);

    Database::hset(Keys::ORGANIZATION_ACCESS_REQUEST . ':' . $requestId, [
      'request_id' => $requestId,
      'organization_id' => $orgId,
      'requester_uuid' => $requesterUUID,
      'owner_uuid' => $ownerUUID,
      'owner_email' => $normalizedOwnerEmail,
      'requester_contact_email' => $requesterContactEmail,
      'status' => 'pending',
      'created_at' => $createdAt,
      'expires_at' => $expiresAt,
    ]);

    Database::sadd(Keys::ORGANIZATION_ACCESS_REQUEST_ORG . ':' . $orgId, $requestId);
    Database::sadd(Keys::ORGANIZATION_ACCESS_REQUEST_REQUESTER . ':' . $requesterUUID, $requestId);
    Database::set($activeKey, $requestId, 14 * 24 * 3600);

    $this->appendAuditEvent($orgId, 'access.requested', $requesterUUID, [
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
        $emailSent = EmailGarum::sendOrganizationAccessRequest(
          ownerEmail: $normalizedOwnerEmail,
          organizationName: $organizationName,
          requesterName: $requesterDisplayName,
          requesterEmail: $requesterContactEmail,
          requestId: $requestId
        );
      } catch (\Throwable $_error) {
        $emailSent = false;
      }
    }

    $this->appendAuditEvent($orgId, 'access.request.notification', $requesterUUID, [
      'request_id' => $requestId,
      'owner_email' => $normalizedOwnerEmail,
      'email_dispatch' => $emailSent ? 'sent' : 'failed',
    ]);

    return $this->ok('Access request submitted.', [
      'request_id' => $requestId,
      'organization_id' => $orgId,
      'owner_uuid' => $ownerUUID,
      'status' => 'pending',
      'expires_at' => $expiresAt,
      'email_dispatch' => $emailSent ? 'sent' : 'failed',
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function listInvites(string $actorUUID, string $orgId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($orgId, $actorUUID)) {
      return $this->fail('You do not have permission to view invites.');
    }

    $inviteIds = Database::smembers(Keys::ORGANIZATION_INVITE_ORG . ':' . $orgId);
    sort($inviteIds, SORT_STRING);

    $invites = [];
    foreach ($inviteIds as $inviteId) {
      $invite = Database::hgetall(Keys::ORGANIZATION_INVITE . ':' . $inviteId);
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

    return $this->ok('Organization invites retrieved.', ['invites' => $invites]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function listInviteHistory(string $actorUUID, string $orgId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($orgId, $actorUUID)) {
      return $this->fail('You do not have permission to view invite history.');
    }

    $inviteIds = Database::smembers(Keys::ORGANIZATION_INVITE_ORG . ':' . $orgId);
    sort($inviteIds, SORT_STRING);

    $invites = [];
    foreach ($inviteIds as $inviteId) {
      $invite = Database::hgetall(Keys::ORGANIZATION_INVITE . ':' . $inviteId);
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

    return $this->ok('Organization invite history retrieved.', ['invites' => $invites]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function listAccessRequestHistory(string $actorUUID, string $orgId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($orgId, $actorUUID)) {
      return $this->fail('You do not have permission to view access request history.');
    }

    $requestIds = Database::smembers(Keys::ORGANIZATION_ACCESS_REQUEST_ORG . ':' . $orgId);
    sort($requestIds, SORT_STRING);

    $requests = [];
    foreach ($requestIds as $requestId) {
      $request = Database::hgetall(Keys::ORGANIZATION_ACCESS_REQUEST . ':' . $requestId);
      if ([] === $request) {
        continue;
      }

      if ((string) ($request['organization_id'] ?? '') !== $orgId) {
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
        'resolved_at' => (string) ($request['approved_at'] ?? $request['rejected_at'] ?? $request['created_at'] ?? ''),
      ];
    }

    usort($requests, static function (array $a, array $b): int {
      return strcmp((string) $b['resolved_at'], (string) $a['resolved_at']);
    });

    return $this->ok('Organization access request history retrieved.', [
      'requests' => $requests,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function listAccessRequests(string $actorUUID, string $orgId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($orgId, $actorUUID)) {
      return $this->fail('You do not have permission to view access requests.');
    }

    $requestIds = Database::smembers(Keys::ORGANIZATION_ACCESS_REQUEST_ORG . ':' . $orgId);
    $requests = [];

    foreach ($requestIds as $requestId) {
      $request = Database::hgetall(Keys::ORGANIZATION_ACCESS_REQUEST . ':' . $requestId);
      if ([] === $request) {
        continue;
      }

      if (($request['organization_id'] ?? '') !== $orgId) {
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

    return $this->ok('Organization access requests retrieved.', [
      'organization_id' => $orgId,
      'requests' => $requests,
    ]);
  }

  /**
   * @param array<string, string> $consentContext
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function approveAccessRequest(string $actorUUID, string $orgId, string $requestId, array $consentContext = []): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($orgId, $actorUUID)) {
      return $this->fail('You do not have permission to approve access requests.');
    }

    $requestKey = Keys::ORGANIZATION_ACCESS_REQUEST . ':' . $requestId;
    $request = Database::hgetall($requestKey);
    if ([] === $request) {
      return $this->fail('Access request not found.');
    }

    if (($request['organization_id'] ?? '') !== $orgId) {
      return $this->fail('Access request does not belong to this organization.');
    }

    if ((string) ($request['status'] ?? '') !== 'pending') {
      return $this->fail('Only pending access requests can be approved.');
    }

    $requesterUUID = (string) ($request['requester_uuid'] ?? '');
    if ($requesterUUID === '' || null === UserRepository::getByUUID($requesterUUID)) {
      return $this->fail('Requesting user account was not found.');
    }

    $requesterContactEmail = InputSanitizer::sanitizeEmail((string) ($request['requester_contact_email'] ?? ''));
    if ($requesterContactEmail !== '') {
      $domainGate = $this->ensureContactDomainPolicyAllowsEmail($orgId, $requesterContactEmail, 'request approval email');
      if (!$domainGate['success']) {
        return $domainGate;
      }
    }

    $consentId = '';
    $credentialId = '';
    if ((bool) SystemConfig::get('org_shared_encryption_enabled')) {
      $consentGate = $this->ensureActivationConsent($orgId, $requesterUUID, $consentContext);
      if (!$consentGate['success']) {
        return $consentGate;
      }

      $consentId = self::scalarString($consentGate['data']['consent_id'] ?? '');
      if ($consentId === '') {
        $consentId = self::scalarString($request['consent_id'] ?? '');
      }
      if ($consentId === '') {
        return $this->fail('Activation requires a valid consent_id for DEK wrap binding.');
      }

      $wrapBootstrap = $this->bootstrapOrgDekWrapForMember(
        $orgId,
        $requesterUUID,
        $consentId,
        self::ORG_DEK_SEGMENT_CURRENT_PERIOD,
        '1'
      );
      if (!$wrapBootstrap['success']) {
        return $wrapBootstrap;
      }

      $credentialId = self::scalarString($wrapBootstrap['data']['credential_id'] ?? '');
    }

    $timestamp = date('c');
    $normalizedScopes = $this->normalizeScopes(self::ACCESS_REQUEST_DEFAULT_SCOPES);
    $scopeCSV = implode(',', $normalizedScopes);

    $this->setRelationship($orgId, $requesterUUID, [
      'role' => 'member',
      'status' => self::MEMBERSHIP_STATE_CONSENTED,
      'scopes' => $scopeCSV,
      'invited_by' => $actorUUID,
      'created_at' => $timestamp,
      'consented_at' => $timestamp,
    ]);

    $this->setRelationship($orgId, $requesterUUID, [
      'role' => 'member',
      'status' => self::MEMBERSHIP_STATE_ACTIVE,
      'scopes' => $scopeCSV,
      'invited_by' => $actorUUID,
      'created_at' => $timestamp,
      'accepted_at' => $timestamp,
    ]);

    Database::hset($requestKey, [
      'status' => 'approved',
      'approved_by' => $actorUUID,
      'approved_at' => $timestamp,
    ]);
    Database::unlink($this->accessRequestActiveKey($orgId, $requesterUUID));

    $this->appendAuditEvent($orgId, 'access.request.approved', $actorUUID, [
      'request_id' => $requestId,
      'requester_uuid' => $requesterUUID,
      'scopes' => $scopeCSV,
      'consent_id' => $consentId,
      'credential_id' => $credentialId,
    ]);
    $this->incrementAccessRequestTelemetry('approved');

    return $this->ok('Access request approved.', [
      'request_id' => $requestId,
      'organization_id' => $orgId,
      'requester_uuid' => $requesterUUID,
      'scopes' => $normalizedScopes,
      'consent_id' => $consentId,
      'credential_id' => $credentialId,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function rejectAccessRequest(string $actorUUID, string $orgId, string $requestId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($orgId, $actorUUID)) {
      return $this->fail('You do not have permission to reject access requests.');
    }

    $requestKey = Keys::ORGANIZATION_ACCESS_REQUEST . ':' . $requestId;
    $request = Database::hgetall($requestKey);
    if ([] === $request) {
      return $this->fail('Access request not found.');
    }

    if (($request['organization_id'] ?? '') !== $orgId) {
      return $this->fail('Access request does not belong to this organization.');
    }

    if ((string) ($request['status'] ?? '') !== 'pending') {
      return $this->fail('Only pending access requests can be rejected.');
    }

    $requesterUUID = (string) ($request['requester_uuid'] ?? '');
    $timestamp = date('c');

    Database::hset($requestKey, [
      'status' => 'rejected',
      'rejected_by' => $actorUUID,
      'rejected_at' => $timestamp,
    ]);

    if ($requesterUUID !== '') {
      Database::unlink($this->accessRequestActiveKey($orgId, $requesterUUID));
    }

    $this->appendAuditEvent($orgId, 'access.request.rejected', $actorUUID, [
      'request_id' => $requestId,
      'requester_uuid' => $requesterUUID,
    ]);
    $this->incrementAccessRequestTelemetry('rejected');

    return $this->ok('Access request rejected.', [
      'request_id' => $requestId,
      'organization_id' => $orgId,
      'requester_uuid' => $requesterUUID,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function revokeInvite(string $actorUUID, string $orgId, string $inviteId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($orgId, $actorUUID)) {
      return $this->fail('You do not have permission to revoke invites.');
    }

    $inviteKey = Keys::ORGANIZATION_INVITE . ':' . $inviteId;
    $invite = Database::hgetall($inviteKey);
    if ([] === $invite) {
      return $this->fail('Invite not found.');
    }

    if (($invite['organization_id'] ?? '') !== $orgId) {
      return $this->fail('Invite does not belong to this organization.');
    }

    $status = (string) ($invite['status'] ?? '');
    if ($status !== 'pending') {
      return $this->fail('Only pending invites can be revoked.');
    }

    Database::hset($inviteKey, [
      'status' => 'revoked',
      'revoked_by' => $actorUUID,
      'revoked_at' => date('c'),
    ]);

    $this->appendAuditEvent($orgId, 'invite.revoked', $actorUUID, [
      'invite_id' => $inviteId,
    ]);

    return $this->ok('Invite revoked.', ['invite_id' => $inviteId, 'organization_id' => $orgId]);
  }

  /**
   * @param array<string, string> $consentContext
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function acceptInvite(string $inviteToken, string $inviteeUUID, array $consentContext = []): array
  {
    $token = trim($inviteToken);
    if ('' === $token) {
      return $this->fail('Invite token is required.');
    }

    $inviteId = self::scalarString(Database::get(Keys::ORGANIZATION_INVITE_TOKEN . ':' . $token));
    if ('' === $inviteId) {
      return $this->fail('Invite token is invalid or expired.');
    }

    $inviteKey = Keys::ORGANIZATION_INVITE . ':' . $inviteId;
    $invite = Database::hgetall($inviteKey);
    if ([] === $invite) {
      return $this->fail('Invite not found.');
    }

    if (($invite['status'] ?? '') !== 'pending') {
      return $this->fail('Invite is no longer active.');
    }

    $inviteeBound = (string) ($invite['invitee_uuid'] ?? '');
    if ('' !== $inviteeBound && $inviteeBound !== $inviteeUUID) {
      return $this->fail('Invite does not belong to this user.');
    }

    $expiresAt = strtotime((string) ($invite['expires_at'] ?? ''));
    if (false === $expiresAt || $expiresAt < time()) {
      Database::hset($inviteKey, ['status' => 'expired']);
      return $this->fail('Invite has expired.');
    }

    $orgId = (string) ($invite['organization_id'] ?? '');
    if ('' === $orgId || !Database::exists(Keys::ORGANIZATION . ':' . $orgId)) {
      return $this->fail('Organization not found for invite.');
    }

    $inviteeEmail = InputSanitizer::sanitizeEmail((string) ($invite['invitee_email'] ?? ''));
    if ($inviteeEmail !== '') {
      $domainGate = $this->ensureContactDomainPolicyAllowsEmail($orgId, $inviteeEmail, 'invite acceptance email');
      if (!$domainGate['success']) {
        return $domainGate;
      }
    }

    $gate = $this->requireAdminPreviewOrSelfOrg($inviteeUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    $consentId = '';
    $credentialId = '';
    if ((bool) SystemConfig::get('org_shared_encryption_enabled')) {
      $consentGate = $this->ensureActivationConsent($orgId, $inviteeUUID, $consentContext);
      if (!$consentGate['success']) {
        return $consentGate;
      }

      $consentId = self::scalarString($consentGate['data']['consent_id'] ?? '');
      if ($consentId === '') {
        return $this->fail('Activation requires a valid consent_id for DEK wrap binding.');
      }

      $wrapBootstrap = $this->bootstrapOrgDekWrapForMember(
        $orgId,
        $inviteeUUID,
        $consentId,
        self::ORG_DEK_SEGMENT_CURRENT_PERIOD,
        '1'
      );
      if (!$wrapBootstrap['success']) {
        return $wrapBootstrap;
      }

      $credentialId = self::scalarString($wrapBootstrap['data']['credential_id'] ?? '');
    }

    $timestamp = date('c');
    $scopes = (string) ($invite['scopes'] ?? '');

    $this->setRelationship($orgId, $inviteeUUID, [
      'role' => 'member',
      'status' => self::MEMBERSHIP_STATE_CONSENTED,
      'scopes' => $scopes,
      'invited_by' => (string) ($invite['invited_by'] ?? ''),
      'created_at' => $timestamp,
      'consented_at' => $timestamp,
    ]);

    $this->setRelationship($orgId, $inviteeUUID, [
      'role' => 'member',
      'status' => self::MEMBERSHIP_STATE_ACTIVE,
      'scopes' => $scopes,
      'invited_by' => (string) ($invite['invited_by'] ?? ''),
      'created_at' => $timestamp,
      'accepted_at' => $timestamp,
    ]);

    Database::hset($inviteKey, [
      'status' => 'accepted',
      'accepted_by' => $inviteeUUID,
      'accepted_at' => $timestamp,
    ]);

    $this->appendAuditEvent($orgId, 'invite.accepted', $inviteeUUID, [
      'invite_id' => (string) ($invite['invite_id'] ?? ''),
      'accepted_by' => $inviteeUUID,
      'scopes' => $scopes,
      'consent_id' => $consentId,
      'credential_id' => $credentialId,
    ]);

    return $this->ok('Invite accepted.', [
      'organization_id' => $orgId,
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
  public function acceptMembershipWithConsent(string $actorUUID, string $orgId, array $payload): array
  {
    $orgId = trim(InputSanitizer::sanitizeString($orgId));
    if ($orgId === '') {
      return $this->fail('Organization id is required.');
    }

    if (!Database::exists(Keys::ORGANIZATION . ':' . $orgId)) {
      return $this->fail('Organization not found.');
    }

    $inviteTokenRaw = $payload['invite_token'] ?? '';
    $inviteToken = is_scalar($inviteTokenRaw) ? trim((string) $inviteTokenRaw) : '';

    $requestIdRaw = $payload['request_id'] ?? '';
    $requestId = is_scalar($requestIdRaw) ? trim((string) $requestIdRaw) : '';

    if ($inviteToken === '' && $requestId === '') {
      return $this->fail('Provide invite_token or request_id.');
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

      $resolvedOrgId = self::scalarString($result['data']['organization_id'] ?? '');
      if ($resolvedOrgId !== $orgId) {
        return $this->fail('Invite organization does not match requested organization context.');
      }

      return $result;
    }

    $requestKey = Keys::ORGANIZATION_ACCESS_REQUEST . ':' . $requestId;
    $request = Database::hgetall($requestKey);
    if ($request === []) {
      return $this->fail('Access request not found.');
    }

    if ((string) ($request['organization_id'] ?? '') !== $orgId) {
      return $this->fail('Access request does not belong to this organization.');
    }

    if ((string) ($request['requester_uuid'] ?? '') !== $actorUUID) {
      return $this->fail('Access request does not belong to current user.');
    }

    if ((string) ($request['status'] ?? '') !== self::MEMBERSHIP_STATE_PENDING) {
      return $this->fail('Only pending access requests can capture consent.');
    }

    $consentGate = $this->ensureActivationConsent($orgId, $actorUUID, $consentContext);
    if (!$consentGate['success']) {
      return $consentGate;
    }

    Database::hset($requestKey, [
      'consent_id' => self::scalarString($consentGate['data']['consent_id'] ?? ''),
      'consented_at' => date('c'),
    ]);

    $this->appendAuditEvent($orgId, 'access.request.consented', $actorUUID, [
      'request_id' => $requestId,
      'consent_id' => self::scalarString($consentGate['data']['consent_id'] ?? ''),
    ]);

    return $this->ok('Consent captured for pending access request.', [
      'organization_id' => $orgId,
      'request_id' => $requestId,
      'status' => self::MEMBERSHIP_STATE_PENDING,
      'consent_id' => self::scalarString($consentGate['data']['consent_id'] ?? ''),
      'pending_approval' => true,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function revokeRelationship(string $actorUUID, string $orgId, string $targetUUID): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($orgId, $actorUUID)) {
      return $this->fail('You do not have permission to revoke access.');
    }

    $org = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    if ([] === $org) {
      return $this->fail('Organization not found.');
    }

    if (($org['owner_uuid'] ?? '') === $targetUUID) {
      return $this->fail('Cannot revoke the organization owner.');
    }

    if ([] === $this->relationship($orgId, $targetUUID)) {
      return $this->fail('Relationship not found.');
    }

    $this->setRelationship($orgId, $targetUUID, [
      'status'     => 'revoked',
      'revoked_by' => $actorUUID,
      'revoked_at' => date('c'),
    ]);

    $wrapRevocation = (new OrganizationEncryptionService())
      ->revokeWrapsForMembership($orgId, $targetUUID, 'membership_revoked');

    $this->appendAuditEvent($orgId, 'relationship.revoked', $actorUUID, [
      'target_user_uuid' => $targetUUID,
      'revoked_wrap_count' => self::scalarInt($wrapRevocation['data']['revoked_wrap_count'] ?? 0),
    ]);

    return $this->ok('Relationship revoked.', [
      'organization_id' => $orgId,
      'user_uuid' => $targetUUID,
      'revoked_wrap_count' => self::scalarInt($wrapRevocation['data']['revoked_wrap_count'] ?? 0),
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function updateRelationshipRole(string $actorUUID, string $orgId, string $targetUUID, string $role): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($orgId, $actorUUID)) {
      return $this->fail('You do not have permission to change member roles.');
    }

    $targetRole = strtolower(trim($role));
    if ($targetRole === '' || !isset(self::VALID_ORG_ROLES[$targetRole])) {
      return $this->fail('Invalid organization role.');
    }

    $org = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    if ([] === $org) {
      return $this->fail('Organization not found.');
    }

    $relationship = $this->relationship($orgId, $targetUUID);
    if ([] === $relationship) {
      return $this->fail('Relationship not found.');
    }

    $actorRelationship = $this->relationship($orgId, $actorUUID);
    $actorIsOwner = (string) ($org['owner_uuid'] ?? '') === $actorUUID;
    $actorRole = $actorIsOwner
      ? 'owner'
      : strtolower(trim((string) ($actorRelationship['role'] ?? '')));
    $currentTargetRole = strtolower(trim((string) ($relationship['role'] ?? '')));

    if (!$actorIsOwner && $actorRole === 'coordinator') {
      if ((string) ($org['owner_uuid'] ?? '') === $targetUUID || $currentTargetRole === 'owner') {
        return $this->fail('Managers cannot modify the organization owner.');
      }

      if ($targetRole === 'owner') {
        return $this->fail('Managers cannot promote members to Owner.');
      }

      if (self::roleRank($targetRole) > self::roleRank($actorRole)) {
        return $this->fail('Managers cannot assign roles above their own level.');
      }
    }

    $status = (string) ($relationship['status'] ?? '');
    if ($status !== 'active' && $status !== 'pending') {
      return $this->fail('Only active or pending memberships can be updated.');
    }

    if ((string) ($org['owner_uuid'] ?? '') === $targetUUID && $targetRole !== 'owner') {
      return $this->fail('Cannot change the organization owner role.');
    }

    if ($targetRole === 'owner') {
      $scopeCSV = 'all';
    } else {
      $preset = $this->resolveRolePreset($targetRole);
      if ($preset === null) {
        return $this->fail('Unable to resolve role preset scopes.');
      }

      $scopeCSV = $preset['scopes'];
    }

    $this->setRelationship($orgId, $targetUUID, [
      'role' => $targetRole,
      'scopes' => $scopeCSV,
      'updated_by' => $actorUUID,
      'role_updated_at' => date('c'),
    ]);

    $this->appendAuditEvent($orgId, 'relationship.role_updated', $actorUUID, [
      'target_user_uuid' => $targetUUID,
      'role' => $targetRole,
      'scopes' => $scopeCSV,
    ]);

    return $this->ok('Relationship role updated.', [
      'organization_id' => $orgId,
      'user_uuid' => $targetUUID,
      'role' => $targetRole,
      'scopes' => $this->scopeList($scopeCSV),
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function leaveOrganization(string $userUUID, string $orgId): array
  {
    $org = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    if ([] === $org) {
      return $this->fail('Organization not found.');
    }

    if (($org['owner_uuid'] ?? '') === $userUUID) {
      if ((string) ($org['organization_type'] ?? 'shared') === 'personal') {
        return $this->fail('Personal organizations cannot be deleted or left.');
      }

      return $this->fail('Organization owner must transfer ownership before leaving.');
    }

    if ([] === $this->relationship($orgId, $userUUID)) {
      return $this->fail('No active relationship found.');
    }

    $this->setRelationship($orgId, $userUUID, [
      'status'       => 'withdrawn',
      'withdrawn_at' => date('c'),
    ]);

    $wrapRevocation = (new OrganizationEncryptionService())
      ->revokeWrapsForMembership($orgId, $userUUID, 'membership_withdrawn');

    $this->appendAuditEvent($orgId, 'relationship.withdrawn', $userUUID, [
      'user_uuid' => $userUUID,
      'revoked_wrap_count' => self::scalarInt($wrapRevocation['data']['revoked_wrap_count'] ?? 0),
    ]);

    return $this->ok('You have left the organization.', [
      'organization_id' => $orgId,
      'revoked_wrap_count' => self::scalarInt($wrapRevocation['data']['revoked_wrap_count'] ?? 0),
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function linkSite(string $actorUUID, string $orgId, string $siteOwnerUUID, string $siteId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageOrganization($orgId, $actorUUID)) {
      return $this->fail('You do not have permission to link sites for this organization.');
    }

    $siteKey = Keys::SITE . ':' . $siteOwnerUUID . ':' . $siteId;
    if (!Database::exists($siteKey)) {
      return $this->fail('Site not found.');
    }

    $siteRef = $siteOwnerUUID . ':' . $siteId;
    Database::sadd(Keys::ORGANIZATION_SITE . ':' . $orgId, $siteRef);
    Database::hset($siteKey, ['organization_id' => $orgId]);

    $this->appendAuditEvent($orgId, 'site.linked', $actorUUID, [
      'site_owner_uuid' => $siteOwnerUUID,
      'site_id' => $siteId,
    ]);

    return $this->ok('Site linked to organization.', [
      'organization_id' => $orgId,
      'site_owner_uuid' => $siteOwnerUUID,
      'site_id' => $siteId,
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

    $actorOrgs = Database::smembers(Keys::ORGANIZATION_USER . ':' . $actorUUID);
    $ownerOrgs = Database::smembers(Keys::ORGANIZATION_USER . ':' . $ownerUUID);
    if ([] === $actorOrgs || [] === $ownerOrgs) {
      return false;
    }

    $ownerOrgSet = array_fill_keys($ownerOrgs, true);
    foreach ($actorOrgs as $orgId) {
      if (!isset($ownerOrgSet[$orgId])) {
        continue;
      }

      if ($this->canManageOrganization($orgId, $actorUUID)) {
        return true;
      }

      $relationship = $this->relationship($orgId, $actorUUID);
      if ([] === $relationship || ($relationship['status'] ?? '') !== 'active') {
        continue;
      }

      $scopeSet = $this->scopeMap((string) ($relationship['scopes'] ?? ''));
      if (isset($scopeSet['sites.write']) || isset($scopeSet['all'])) {
        return true;
      }
    }

    return false;
  }

  /**
   * Handles canMutateWorkForOwner operation.
   */
  public function canMutateWorkForOwner(string $actorUUID, string $ownerUUID, string $orgId = ''): bool
  {
    $actorUUID = trim(InputSanitizer::sanitizeString($actorUUID));
    $ownerUUID = trim(InputSanitizer::sanitizeString($ownerUUID));
    $orgId = trim(InputSanitizer::sanitizeString($orgId));

    if ('' === $actorUUID || '' === $ownerUUID) {
      return false;
    }

    if ($orgId !== '') {
      $actorRelationship = $this->relationship($orgId, $actorUUID);
      $ownerRelationship = $this->relationship($orgId, $ownerUUID);

      if ([] === $actorRelationship || [] === $ownerRelationship) {
        return false;
      }

      if (($actorRelationship['status'] ?? '') !== self::MEMBERSHIP_STATE_ACTIVE
        || ($ownerRelationship['status'] ?? '') !== self::MEMBERSHIP_STATE_ACTIVE) {
        return false;
      }

      if ($this->canManageOrganization($orgId, $actorUUID)) {
        return true;
      }

      $scopeSet = $this->scopeMap((string) ($actorRelationship['scopes'] ?? ''));

      if (isset($scopeSet['all'])) {
        return true;
      }

      if (!isset($scopeSet['work.write'])) {
        return false;
      }

      if (isset($scopeSet['work.scope.org'])) {
        return true;
      }

      if (isset($scopeSet['work.scope.self'])) {
        return $actorUUID === $ownerUUID;
      }

      // Legacy bare work.write grants org-wide mutation until relationship scopes are resaved.
      return true;
    }

    // Without explicit organization context, keep legacy self-only write behavior.
    return $actorUUID === $ownerUUID;
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function discoveryForUser(string $userUUID): array
  {
    $orgIds = Database::smembers(Keys::ORGANIZATION_USER . ':' . $userUUID);
    if (!$this->canAccessPremiumFeatures($userUUID)) {
      $orgIds = array_values(array_filter($orgIds, function (string $orgId) use ($userUUID): bool {
        $organization = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
        $isOrganizationOwner = (string) ($organization['owner_uuid'] ?? '') === $userUUID;

        return $this->isSelfOrganization($organization, $userUUID) || $isOrganizationOwner;
      }));
    }

    $sites = [];
    foreach (Sites::getSites($userUUID, 'all') as $siteId => $siteData) {
      $sites[] = [
        'site_id' => $siteId,
        'site_owner_uuid' => $userUUID,
        'name' => (string) ($siteData['site_name'] ?? ''),
        'organization_id' => (string) ($siteData['organization_id'] ?? ''),
      ];
    }

    $matchCandidates = [];
    foreach ($orgIds as $orgId) {
      foreach ($sites as $site) {
        if ($site['organization_id'] === '') {
          $matchCandidates[] = [
            'organization_id' => $orgId,
            'candidate_type' => 'site',
            'candidate_id' => (string) $site['site_id'],
            'reason' => 'user_site_without_organization',
          ];
        }
      }
    }

    return $this->ok('Organization discovery generated.', [
      'user_organizations' => $orgIds,
      'user_sites' => $sites,
      'match_candidates' => $matchCandidates,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function getOrganizationSettings(string $actorUUID, string $orgId): array
  {
    $organization = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    if ([] === $organization) {
      return $this->fail('Organization not found.');
    }

    $canReadOrgSettings = $this->canReadOrganizationSettings($orgId, $actorUUID);
    $canReadPayPeriod = $this->canReadOrganizationPayPeriod($orgId, $actorUUID);

    if (!$canReadOrgSettings && !$canReadPayPeriod) {
      return $this->fail('You do not have permission to view organization settings.');
    }

    $settings = Database::hgetall(Keys::ORGANIZATION_SETTINGS . ':' . $orgId);
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

    $ownerUUID = (string) ($organization['owner_uuid'] ?? '');
    $owner = $ownerUUID !== '' ? UserRepository::getByUUID($ownerUUID) : null;
    $ownerRelationship = $ownerUUID !== '' ? $this->relationship($orgId, $ownerUUID) : [];
    $ownerSince = (string) ($ownerRelationship['owner_since'] ?? $ownerRelationship['accepted_at'] ?? $ownerRelationship['created_at'] ?? '');

    return $this->ok('Organization settings retrieved.', [
      'organization_id' => $orgId,
      'organization' => [
        'name' => (string) ($organization['name'] ?? ''),
        'owner_uuid' => (string) ($organization['owner_uuid'] ?? ''),
        'owner_name' => $owner instanceof User ? $owner->full_name : '',
        'owner_email' => $owner instanceof User ? $owner->email : '',
        'owner_phone' => $owner instanceof User ? $owner->phone : '',
        'owner_since' => $ownerSince,
        'organization_type' => (string) ($organization['organization_type'] ?? 'shared'),
        'status' => (string) ($organization['status'] ?? 'active'),
      ],
      'settings' => $settings,
    ]);
  }

  /** @param array<string, mixed> $settings
   *  @return array{success: bool, message: string, data: array<string, mixed>} */
  public function updateOrganizationSettings(string $actorUUID, string $orgId, array $settings): array
  {
    $organization = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    if ([] === $organization) {
      return $this->fail('Organization not found.');
    }

    $canWriteOrgSettings = $this->canWriteOrganizationSettings($orgId, $actorUUID);
    $canWritePayPeriod = $this->canWriteOrganizationPayPeriod($orgId, $actorUUID);

    if (!$canWriteOrgSettings && !$canWritePayPeriod) {
      return $this->fail('You do not have permission to update organization settings.');
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
        return $this->fail('You do not have permission to update organization settings.');
      }
    }

    $existingSettings = Database::hgetall(Keys::ORGANIZATION_SETTINGS . ':' . $orgId);

    $organizationUpdates = [];
    $didRoleUpdate = false;

    $normalized = [];

    if (array_key_exists('name', $settings)) {
      $value = is_scalar($settings['name']) ? trim((string) $settings['name']) : '';
      if ($value === '' || strlen($value) < 2) {
        return $this->fail('Organization name must be at least 2 characters.');
      }
      $organizationUpdates['name'] = $value;
    }

    if (array_key_exists('organization_type', $settings)) {
      $type = $this->normalizeOrganizationType($settings['organization_type']);
      if ($type === '') {
        return $this->fail('organization_type is invalid.');
      }

      $currentType = (string) ($organization['organization_type'] ?? 'shared');
      if ($type !== $currentType && $type === 'shared' && !$this->canAccessPremiumFeatures($actorUUID)) {
        return $this->premiumSubscriptionRequired();
      }

      $organizationUpdates['organization_type'] = $type;
    }

    if (array_key_exists('status', $settings)) {
      $status = is_scalar($settings['status']) ? strtolower(trim((string) $settings['status'])) : 'active';
      if (!in_array($status, ['active', 'pending'], true)) {
        return $this->fail('status is invalid.');
      }
      $organizationUpdates['status'] = $status;
    }

    if (array_key_exists('role', $settings)) {
      $targetRole = is_scalar($settings['role']) ? strtolower(trim((string) $settings['role'])) : '';
      if ($targetRole !== '') {
        if (!isset(self::VALID_ORG_ROLES[$targetRole])) {
          return $this->fail('role is invalid.');
        }

        $relationship = $this->relationship($orgId, $actorUUID);
        if ([] === $relationship) {
          return $this->fail('Membership relationship not found for current user.');
        }

        $currentRole = strtolower(trim((string) ($relationship['role'] ?? '')));

        if ((string) ($organization['owner_uuid'] ?? '') === $actorUUID && $targetRole !== 'owner') {
          return $this->fail('Cannot change the organization owner role.');
        }

        if ($currentRole === 'coordinator' && $targetRole !== 'coordinator') {
          return $this->fail('Managers cannot downgrade or change their own role.');
        }

        $scopeCSV = $targetRole === 'owner'
          ? 'all'
          : implode(',', self::ROLE_SCOPE_PRESETS[$targetRole]);

        $this->setRelationship($orgId, $actorUUID, [
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
        return $this->fail('pay_frequency is invalid.');
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
        return $this->fail('pay_period_length must be between 7 and 31 days.');
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
        return $this->fail('timezone is invalid.');
      }
      $normalized['timezone'] = $value;
    }

    if (array_key_exists('pay_anchor', $settings)) {
      $value = is_scalar($settings['pay_anchor']) ? trim((string) $settings['pay_anchor']) : 'Monday';
      $allowedAnchors = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
      if (!in_array($value, $allowedAnchors, true)) {
        return $this->fail('pay_anchor is invalid.');
      }
      $normalized['pay_anchor'] = $value;
    }

    if (array_key_exists('pay_period_start', $settings)) {
      $value = is_scalar($settings['pay_period_start']) ? trim((string) $settings['pay_period_start']) : '';
      if (!$this->isValidYmdDate($value)) {
        return $this->fail('pay_period_start must be a valid YYYY-MM-DD date.');
      }
      $normalized['pay_period_start'] = $value;
      $normalized['pay_epoch'] = $value;
    }

    if (array_key_exists('editing_grace_days', $settings)) {
      $value = is_scalar($settings['editing_grace_days']) ? (int) $settings['editing_grace_days'] : UserPreferenceDefaults::DEFAULT_EDITING_GRACE_DAYS;
      $min = (int) SystemLimits::get('editing_grace_days_min');
      $max = (int) SystemLimits::get('editing_grace_days_max');
      if ($value < $min || $value > $max) {
        return $this->fail('editing_grace_days is outside the allowed range.');
      }
      $normalized['editing_grace_days'] = (string) $value;
    }

    if (array_key_exists('currency', $settings)) {
      $value = is_scalar($settings['currency']) ? strtoupper(trim((string) $settings['currency'])) : 'CAD';
      if (!Currency::isValid($value)) {
        return $this->fail('currency must be a valid ISO 4217 code.');
      }
      $normalized['currency'] = $value;
    }

    if (array_key_exists('enforce_contact_domain', $settings)) {
      $normalized['enforce_contact_domain'] = $this->isTruthySettingValue($settings['enforce_contact_domain']) ? '1' : '0';
    }

    if (array_key_exists('allowed_contact_domains', $settings)) {
      $domainParse = $this->parseAllowedContactDomainPayload($settings['allowed_contact_domains']);
      if ([] !== $domainParse['invalid']) {
        return $this->fail('allowed_contact_domains contains invalid domains.', [
          'invalid_domains' => $domainParse['invalid'],
        ]);
      }

      $domainCsv = implode(',', $domainParse['domains']);
      if (strlen($domainCsv) > 300) {
        return $this->fail('allowed_contact_domains exceeds the maximum length.');
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

    $effectiveEnforceContactDomain = ($normalized['enforce_contact_domain'] ?? (string) ($existingSettings['enforce_contact_domain'] ?? '0')) === '1';
    $effectiveAllowedDomains = $this->parseAllowedContactDomainCsv((string) ($normalized['allowed_contact_domains'] ?? ($existingSettings['allowed_contact_domains'] ?? '')));

    if ($effectiveEnforceContactDomain) {
      if ([] === $effectiveAllowedDomains) {
        return $this->fail('Contact domain enforcement is enabled but no allowed domains are configured.');
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
        return $this->fail('One or more contact emails do not match allowed domains.', [
          'violations' => array_values(array_unique($violations)),
        ]);
      }
    }

    if ([] === $normalized && [] === $organizationUpdates && !$didRoleUpdate) {
      return $this->fail('No valid settings were provided.');
    }

    $normalized['last_updated_at'] = date('c');
    $normalized['last_updated_by'] = $actorUUID;

    if ([] !== $organizationUpdates) {
      $organizationUpdates['updated_at'] = $normalized['last_updated_at'];
      Database::hset(Keys::ORGANIZATION . ':' . $orgId, $organizationUpdates);
    }

    Database::hset(Keys::ORGANIZATION_SETTINGS . ':' . $orgId, $normalized);

    $organizationType = (string) ($organization['organization_type'] ?? 'shared');
    if ($organizationType === 'personal') {
      $this->syncPersonalOrganizationSettingsToOwner($orgId, $organization, $normalized);
    }

    $fieldList = array_unique(array_merge(array_keys($organizationUpdates), array_keys($normalized), $didRoleUpdate ? ['role'] : []));
    $this->appendAuditEvent($orgId, 'settings.updated', $actorUUID, [
      'fields' => implode(',', $fieldList),
    ]);

    return $this->ok('Organization settings updated.', [
      'organization_id' => $orgId,
      'organization' => Database::hgetall(Keys::ORGANIZATION . ':' . $orgId),
      'settings' => Database::hgetall(Keys::ORGANIZATION_SETTINGS . ':' . $orgId),
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function transferOwnership(string $actorUUID, string $orgId, string $targetUUID): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    $targetUUID = trim(InputSanitizer::sanitizeString($targetUUID));
    if ('' === $targetUUID) {
      return $this->fail('Target user UUID is required.');
    }

    $org = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    if ([] === $org) {
      return $this->fail('Organization not found.');
    }

    if (($org['organization_type'] ?? 'shared') === 'personal') {
      return $this->fail('Personal organizations cannot transfer ownership.');
    }

    $currentOwner = (string) ($org['owner_uuid'] ?? '');
    if ($currentOwner !== $actorUUID) {
      return $this->fail('Only the current owner can transfer ownership.');
    }

    if ($targetUUID === $actorUUID) {
      return $this->fail('Target owner must be a different user.');
    }

    $targetUser = UserRepository::getByUUID($targetUUID);
    if (null === $targetUser) {
      return $this->fail('Target user not found.');
    }

    $targetEmail = InputSanitizer::sanitizeEmail($targetUser->email);
    $domainGate = $this->ensureContactDomainPolicyAllowsEmail($orgId, $targetEmail, 'ownership transfer target email');
    if (!$domainGate['success']) {
      return $domainGate;
    }

    $targetRelationship = $this->relationship($orgId, $targetUUID);
    if ([] === $targetRelationship || (string) ($targetRelationship['status'] ?? '') !== 'active') {
      return $this->fail('Ownership can only be transferred to an existing active member of the organization.');
    }

    $timestamp = date('c');
      $previousOwnerScopes = implode(',', self::ROLE_SCOPE_PRESETS['coordinator']);

    $this->setRelationship($orgId, $actorUUID, [
        'role' => 'coordinator',
      'status' => 'active',
        'scopes' => $previousOwnerScopes,
      'transferred_at' => $timestamp,
      'transferred_to' => $targetUUID,
    ]);

    $this->setRelationship($orgId, $targetUUID, [
      'role' => 'owner',
      'status' => 'active',
      'scopes' => 'all',
      'accepted_at' => $timestamp,
      'owner_since' => $timestamp,
      'transferred_from' => $actorUUID,
    ]);

    Database::hset(Keys::ORGANIZATION . ':' . $orgId, [
      'owner_uuid' => $targetUUID,
      'updated_at' => $timestamp,
    ]);

    Database::srem(Keys::ORGANIZATION_OWNER . ':' . $actorUUID, $orgId);
    Database::sadd(Keys::ORGANIZATION_OWNER . ':' . $targetUUID, $orgId);

    $this->appendAuditEvent($orgId, 'ownership.transferred', $actorUUID, [
      'from_user_uuid' => $actorUUID,
      'to_user_uuid' => $targetUUID,
    ]);

    return $this->ok('Ownership transferred.', [
      'organization_id' => $orgId,
      'owner_uuid' => $targetUUID,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function listRelationships(string $actorUUID, string $orgId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($orgId, $actorUUID) && !$this->canManageOrganization($orgId, $actorUUID)) {
      return $this->fail('You do not have permission to view organization relationships.');
    }

    $org = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    if ([] === $org) {
      return $this->fail('Organization not found.');
    }

    $relationships = [];
    $members = [];
    $pattern = Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':*';
    foreach (Database::scanKeys($pattern) as $relationshipKey) {
      $relationship = Database::hgetall($relationshipKey);
      if ([] === $relationship) {
        continue;
      }

      $role = strtolower(trim((string) ($relationship['role'] ?? '')));

      $memberUUID = (string) ($relationship['user_uuid'] ?? '');
      if ('' === $memberUUID) {
        continue;
      }

      $member = UserRepository::find($memberUUID);

      $relationships[] = [
        'user_uuid' => $memberUUID,
        'role' => $role,
        'status' => (string) ($relationship['status'] ?? ''),
        'scopes' => $this->scopeList((string) ($relationship['scopes'] ?? '')),
        'created_at' => (string) ($relationship['created_at'] ?? ''),
        'accepted_at' => (string) ($relationship['accepted_at'] ?? ''),
        'owner_since' => (string) ($relationship['owner_since'] ?? ''),
        'updated_at' => (string) ($relationship['updated_at'] ?? ''),
      ];

      if (null !== $member) {
        $members[] = [
          'uuid' => $memberUUID,
          'user_uuid' => $memberUUID,
          'full_name' => $member->full_name,
          'email' => $member->email,
          'role' => $role,
          'status' => (string) ($relationship['status'] ?? ''),
          'scopes' => $this->scopeList((string) ($relationship['scopes'] ?? '')),
          'created_at' => (string) ($relationship['created_at'] ?? ''),
          'accepted_at' => (string) ($relationship['accepted_at'] ?? ''),
          'owner_since' => (string) ($relationship['owner_since'] ?? ''),
          'updated_at' => (string) ($relationship['updated_at'] ?? ''),
        ];
      }
    }

    usort($relationships, static function (array $a, array $b): int {
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

    return $this->ok('Organization relationships retrieved.', [
      'organization_id' => $orgId,
      'relationships' => $relationships,
      'members' => $members,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function listAuditTimeline(string $actorUUID, string $orgId): array
  {
    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canReadAuditTimeline($orgId, $actorUUID)) {
      return $this->fail('You do not have permission to view organization audit timeline.');
    }

    $eventIds = Database::smembers(Keys::ORGANIZATION_AUDIT . ':' . $orgId);
    $events = [];

    foreach ($eventIds as $eventId) {
      $event = Database::hgetall(Keys::ORGANIZATION_AUDIT_EVENT . ':' . $eventId);
      if ([] === $event) {
        continue;
      }

      if (($event['organization_id'] ?? '') !== $orgId) {
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

    return $this->ok('Organization audit timeline retrieved.', [
      'organization_id' => $orgId,
      'events' => $events,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function listAuditTimelineForMember(string $actorUUID, string $orgId): array
  {
    $organization = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    if ([] === $organization) {
      return $this->fail('Organization not found.');
    }

    $relationship = $this->relationship($orgId, $actorUUID);
    if ([] === $relationship) {
      return $this->fail('You do not have an organization relationship.');
    }

    $relationshipStatus = (string) ($relationship['status'] ?? '');
    if ($relationshipStatus !== 'active' && $relationshipStatus !== 'pending') {
      return $this->fail('You do not have permission to view organization audit timeline.');
    }

    $actor = UserRepository::getByUUID($actorUUID);
    $actorEmail = $actor instanceof User ? strtolower(trim($actor->email)) : '';

    $eventIds = Database::smembers(Keys::ORGANIZATION_AUDIT . ':' . $orgId);
    $events = [];

    foreach ($eventIds as $eventId) {
      $event = Database::hgetall(Keys::ORGANIZATION_AUDIT_EVENT . ':' . $eventId);
      if ([] === $event) {
        continue;
      }

      if (($event['organization_id'] ?? '') !== $orgId) {
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

    return $this->ok('Organization audit timeline retrieved.', [
      'organization_id' => $orgId,
      'events' => $events,
    ]);
  }

  /**
   * Handles canManageOrganization operation.
   */
  private function canManageOrganization(string $orgId, string $userUUID): bool
  {
    $org = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    if ([] === $org) {
      return false;
    }

    if (($org['owner_uuid'] ?? '') === $userUUID) {
      return true;
    }

    $relationship = $this->relationship($orgId, $userUUID);
    if ([] === $relationship) {
      return false;
    }

    if (($relationship['status'] ?? '') !== 'active') {
      return false;
    }

    if (($relationship['role'] ?? '') === 'owner') {
      return true;
    }

    $scopeSet = $this->scopeMap((string) ($relationship['scopes'] ?? ''));

    return isset($scopeSet['org.settings.write']);
  }

  /**
   * Check if user can access premium organization features.
   * Premium tier grants access to shared org creation and management.
   * Admins always have access (legacy system role).
   *
   * @return bool
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

    // Check if user has active Premium subscription
    return SubscriptionRepository::isPremiumActive($userUUID);
  }

  /** @param array<string, mixed> $organization */
  private function isSelfOrganization(array $organization, string $userUUID): bool
  {
    $typeRaw = $organization['organization_type'] ?? 'shared';
    $ownerUUIDRaw = $organization['owner_uuid'] ?? '';

    $type = is_scalar($typeRaw) ? (string) $typeRaw : 'shared';
    $ownerUUID = is_scalar($ownerUUIDRaw) ? (string) $ownerUUIDRaw : '';

    return $type === 'personal' && $ownerUUID === $userUUID;
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>}|null */
  private function requireAdminPreviewOrSelfOrg(string $actorUUID, string $orgId): ?array
  {
    $organization = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    if ([] === $organization) {
      return $this->fail('Organization not found.');
    }

    $isOrganizationOwner = (string) ($organization['owner_uuid'] ?? '') === $actorUUID;

    $relationship = $this->relationship($orgId, $actorUUID);
    $hasOrgRelationship = $relationship !== [] && in_array(
      (string) ($relationship['status'] ?? ''),
      [self::MEMBERSHIP_STATE_ACTIVE, self::MEMBERSHIP_STATE_PENDING, self::MEMBERSHIP_STATE_CONSENTED],
      true
    );

    if ($this->canAccessPremiumFeatures($actorUUID) || $this->isSelfOrganization($organization, $actorUUID) || $isOrganizationOwner || $hasOrgRelationship) {
      return null;
    }

    return $this->premiumSubscriptionRequired();
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  private function premiumSubscriptionRequired(): array
  {
    return $this->fail('Premium subscription required. Upgrade to PayCal Premium to create and manage shared organizations linked to your profile.');
  }

  /**
   * Count active members in an organization (relationships with status 'active').
   *
   * @param string $orgId
   * @return int
   */
  private function getOrganizationMemberCount(string $orgId): int
  {
    return OrganizationMemberRepository::count($orgId, 'active');
  }

  /**
   * Check if organization has reached the member limit.
   * Premium tier allows up to 1,000 members per organization.
   *
   * @param string $orgId
   * @return bool True if the org has reached its member limit
   */
  private function hasReachedMemberLimit(string $orgId): bool
  {
    $maxMembers = Subscription::PREMIUM->maxMembersPerOrg();
    $currentMembers = $this->getOrganizationMemberCount($orgId);
    return $currentMembers >= $maxMembers;
  }

  /**
   * Handles canManageAccess operation.
   */
  private function canManageAccess(string $orgId, string $userUUID): bool
  {
    if ($this->canManageOrganization($orgId, $userUUID)) {
      return true;
    }

    $relationship = $this->relationship($orgId, $userUUID);
    if ([] === $relationship || ($relationship['status'] ?? '') !== 'active') {
      return false;
    }

    $scopeSet = $this->scopeMap((string) ($relationship['scopes'] ?? ''));

    return isset($scopeSet['access.manage']);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  private function resolveBulkImportAuthority(string $actorUUID, string $orgId): array
  {
    $actor = UserRepository::getByUUID($actorUUID);
    if (null === $actor) {
      return $this->fail('Actor account not found.');
    }

    if (!$actor->email_verified) {
      return $this->fail('Your account email must be verified before bulk importing member invites.');
    }

    $org = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    if ([] === $org) {
      return $this->fail('Organization not found.');
    }

    $relationship = $this->relationship($orgId, $actorUUID);
    $role = (string) ($relationship['role'] ?? '');
    $isOwner = (string) ($org['owner_uuid'] ?? '') === $actorUUID;
    $isOrgAdmin = $role === 'coordinator';

    if (!User::isAdmin() && !$isOwner && !$isOrgAdmin) {
      return $this->fail('Only organization owners/admins can perform bulk imports.');
    }

    $authorityEmail = InputSanitizer::sanitizeEmail($actor->email);
    if ($authorityEmail === '' || !filter_var($authorityEmail, FILTER_VALIDATE_EMAIL)) {
      return $this->fail('A verified authority email is required for bulk import verification.');
    }

    $authorityDomain = $this->emailDomain($authorityEmail);
    if ($authorityDomain === '') {
      return $this->fail('Unable to determine authority email domain for bulk import enforcement.');
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
    if ($customJson !== '') {
      $decoded = json_decode($customJson, true);
      if (is_array($decoded)) {
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
      }
    }

    return array_values(array_unique($emails));
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  private function ensureContactDomainPolicyAllowsEmail(string $orgId, string $email, string $contextLabel): array
  {
    $settings = Database::hgetall(Keys::ORGANIZATION_SETTINGS . ':' . $orgId);
    $enforce = ((string) ($settings['enforce_contact_domain'] ?? '0')) === '1';
    if (!$enforce) {
      return $this->ok('Contact domain policy not enforced.', []);
    }

    $allowed = $this->parseAllowedContactDomainCsv((string) ($settings['allowed_contact_domains'] ?? ''));
    if ([] === $allowed) {
      return $this->fail('Contact domain enforcement is enabled but no allowed domains are configured.');
    }

    $domain = $this->emailDomain(strtolower(InputSanitizer::sanitizeEmail($email)));
    if ($domain === '' || !isset($allowed[$domain])) {
      return $this->fail('Email domain is blocked by organization contact-domain policy.', [
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
  private function loadBulkImportPrepare(string $actorUUID, string $orgId, string $importId): array
  {
    $importIdClean = trim(InputSanitizer::sanitizeString($importId));
    if ($importIdClean === '') {
      return $this->fail('Import id is required.');
    }

    $prepare = Database::hgetall(Keys::organizationInviteImportPrepare($importIdClean));
    if ([] === $prepare) {
      return $this->fail('Bulk import session not found or expired.');
    }

    if ((string) ($prepare['organization_id'] ?? '') !== trim(InputSanitizer::sanitizeString($orgId))) {
      return $this->fail('Bulk import session does not belong to this organization.');
    }

    if ((string) ($prepare['actor_uuid'] ?? '') !== $actorUUID) {
      return $this->fail('Bulk import session does not belong to this user.');
    }

    return $this->ok('Bulk import session loaded.', [
      'prepare' => $prepare,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  private function loadBulkImportChallenge(string $actorUUID, string $orgId, string $importId, string $challengeId): array
  {
    $challengeIdClean = trim(InputSanitizer::sanitizeString($challengeId));
    if ($challengeIdClean === '') {
      return $this->fail('Challenge id is required.');
    }

    $challenge = Database::hgetall(Keys::organizationInviteImportChallenge($challengeIdClean));
    if ([] === $challenge) {
      return $this->fail('Challenge not found or expired.');
    }

    if ((string) ($challenge['actor_uuid'] ?? '') !== $actorUUID) {
      return $this->fail('Challenge does not belong to this user.');
    }

    if ((string) ($challenge['organization_id'] ?? '') !== trim(InputSanitizer::sanitizeString($orgId))) {
      return $this->fail('Challenge does not belong to this organization.');
    }

    if ((string) ($challenge['import_id'] ?? '') !== trim(InputSanitizer::sanitizeString($importId))) {
      return $this->fail('Challenge does not match this import session.');
    }

    return $this->ok('Challenge loaded.', [
      'challenge' => $challenge,
    ]);
  }

  /**
   * Handles canReadOrganizationSettings operation.
   */
  private function canReadOrganizationSettings(string $orgId, string $userUUID): bool
  {
    if ($this->canManageOrganization($orgId, $userUUID)) {
      return true;
    }

    $relationship = $this->relationship($orgId, $userUUID);
    if ([] === $relationship || ($relationship['status'] ?? '') !== 'active') {
      return false;
    }

    $scopeSet = $this->scopeMap((string) ($relationship['scopes'] ?? ''));

    return isset($scopeSet['org.settings.read']) || isset($scopeSet['org.settings.write']);
  }

  /**
   * Handles canWriteOrganizationSettings operation.
   */
  private function canWriteOrganizationSettings(string $orgId, string $userUUID): bool
  {
    if ($this->canManageOrganization($orgId, $userUUID)) {
      return true;
    }

    $relationship = $this->relationship($orgId, $userUUID);
    if ([] === $relationship || ($relationship['status'] ?? '') !== 'active') {
      return false;
    }

    $scopeSet = $this->scopeMap((string) ($relationship['scopes'] ?? ''));

    return isset($scopeSet['org.settings.write']);
  }

  /**
   * Handles pay-period write checks for roles that can operate pay controls
   * without full organization settings mutation rights.
   */
  private function canWriteOrganizationPayPeriod(string $orgId, string $userUUID): bool
  {
    if ($this->canManageOrganization($orgId, $userUUID)) {
      return true;
    }

    $relationship = $this->relationship($orgId, $userUUID);
    if ([] === $relationship || ($relationship['status'] ?? '') !== 'active') {
      return false;
    }

    $scopeSet = $this->scopeMap((string) ($relationship['scopes'] ?? ''));
    if (isset($scopeSet['payperiod.write']) || isset($scopeSet['org.settings.write']) || isset($scopeSet['all'])) {
      return true;
    }

    $role = strtolower(trim((string) ($relationship['role'] ?? '')));
    return in_array($role, ['owner', 'coordinator', 'contributor'], true);
  }

  /**
   * Handles pay-period read checks for roles that should access pay controls
   * without full organization settings read rights.
   */
  private function canReadOrganizationPayPeriod(string $orgId, string $userUUID): bool
  {
    if ($this->canManageOrganization($orgId, $userUUID)) {
      return true;
    }

    $relationship = $this->relationship($orgId, $userUUID);
    if ([] === $relationship || ($relationship['status'] ?? '') !== 'active') {
      return false;
    }

    $scopeSet = $this->scopeMap((string) ($relationship['scopes'] ?? ''));
    if (isset($scopeSet['payperiod.read']) || isset($scopeSet['payperiod.write']) || isset($scopeSet['org.settings.read']) || isset($scopeSet['org.settings.write']) || isset($scopeSet['all'])) {
      return true;
    }

    $role = strtolower(trim((string) ($relationship['role'] ?? '')));
    return in_array($role, ['owner', 'coordinator', 'contributor', 'member', 'viewer'], true);
  }

  /**
   * Handles canReadAuditTimeline operation.
   */
  private function canReadAuditTimeline(string $orgId, string $userUUID): bool
  {
    if ($this->canManageOrganization($orgId, $userUUID)) {
      return true;
    }

    $relationship = $this->relationship($orgId, $userUUID);
    if ([] === $relationship || ($relationship['status'] ?? '') !== 'active') {
      return false;
    }

    $scopeSet = $this->scopeMap((string) ($relationship['scopes'] ?? ''));
    $role = strtolower(trim((string) ($relationship['role'] ?? '')));

    if ($role === 'contributor') {
      return true;
    }

    return isset($scopeSet['audit.read'])
      || isset($scopeSet['org.settings.write'])
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
  private function ensureActivationConsent(string $orgId, string $userUUID, array $consentContext = []): array
  {
    if (!(bool) SystemConfig::get('org_shared_encryption_enabled')) {
      return $this->ok('Org shared encryption disabled; consent gate bypassed.', []);
    }

    $consentIdRaw = $consentContext['consent_id'] ?? '';
    $consentId = is_scalar($consentIdRaw) ? trim((string) $consentIdRaw) : '';
    if ($consentId !== '') {
      if (!$this->isConsentValidForWrap($orgId, $userUUID, $consentId)) {
        return $this->fail('Activation requires a valid active consent record.');
      }

      return $this->ok('Activation consent validated from provided consent id.', [
        'consent_id' => $consentId,
      ]);
    }

    $consentAcknowledgedRaw = $consentContext['consent_acknowledged'] ?? false;
    $consentAcknowledged = filter_var($consentAcknowledgedRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    if (!$consentAcknowledged) {
      return $this->fail('Activation requires explicit consent acknowledgment.');
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
      $disclaimerText = 'Org shared encryption consent accepted.';
    }

    $ipRaw = $consentContext['ip'] ?? '';
    $ip = is_scalar($ipRaw) ? trim((string) $ipRaw) : '';

    $userAgentRaw = $consentContext['user_agent'] ?? '';
    $userAgent = is_scalar($userAgentRaw) ? trim((string) $userAgentRaw) : '';

    return $this->recordOrgConsent(
      $orgId,
      $userUUID,
      $consentVersion,
      $disclaimerText,
      $ip,
      $userAgent
    );
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  private function recordOrgConsent(
    string $orgId,
    string $userUUID,
    string $consentVersion,
    string $disclaimerText,
    string $ip = '',
    string $userAgent = ''
  ): array {
    $orgId = trim(InputSanitizer::sanitizeString($orgId));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    if ($orgId === '' || $userUUID === '') {
      return $this->fail('Consent requires valid organization and user identifiers.');
    }

    if (!Database::exists(Keys::ORGANIZATION . ':' . $orgId)) {
      return $this->fail('Cannot record consent for missing organization.');
    }

    $consentVersion = trim(InputSanitizer::sanitizeString($consentVersion));
    if ($consentVersion === '') {
      $consentVersion = 'v1';
    }

    $timestamp = date('c');
    $consentId = 'cons_' . bin2hex(random_bytes(8));
    $consentKey = Keys::organizationConsent($consentId);

    $ipHash = $ip !== '' ? hash('sha256', $ip) : '';
    $userAgentHash = $userAgent !== '' ? hash('sha256', $userAgent) : '';
    $disclaimerHash = hash('sha256', $disclaimerText);

    Database::hset($consentKey, [
      'consent_id' => $consentId,
      'org_id' => $orgId,
      'user_uuid' => $userUUID,
      'consent_version' => $consentVersion,
      'accepted_at' => $timestamp,
      'ip_hash' => $ipHash,
      'user_agent_hash' => $userAgentHash,
      'disclaimer_text_hash' => $disclaimerHash,
      'status' => self::MEMBERSHIP_STATE_ACTIVE,
    ]);

    Database::sadd(Keys::organizationConsentsByOrg($orgId), $consentId);
    Database::sadd(Keys::organizationConsentsByUser($userUUID), $consentId);

    $this->appendAuditEvent($orgId, 'org.consent.accepted', $userUUID, [
      'consent_id' => $consentId,
      'consent_version' => $consentVersion,
      'user_uuid' => $userUUID,
    ]);

    return $this->ok('Organization consent recorded.', [
      'consent_id' => $consentId,
      'org_id' => $orgId,
      'user_uuid' => $userUUID,
      'consent_version' => $consentVersion,
      'accepted_at' => $timestamp,
    ]);
  }

  /** @return array<string, string> */
  private function loadActiveOrgConsent(string $orgId, string $userUUID): array
  {
    $orgId = trim(InputSanitizer::sanitizeString($orgId));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    if ($orgId === '' || $userUUID === '') {
      return [];
    }

    $latestConsent = [];
    $latestAcceptedAt = '';

    $consentIds = Database::smembers(Keys::organizationConsentsByUser($userUUID));
    sort($consentIds, SORT_STRING);

    foreach ($consentIds as $consentId) {
      $consent = Database::hgetall(Keys::organizationConsent((string) $consentId));
      if ($consent === []) {
        continue;
      }

      if ((string) ($consent['org_id'] ?? '') !== $orgId) {
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
  private function isConsentValidForWrap(string $orgId, string $userUUID, string $consentId = ''): bool
  {
    $orgId = trim(InputSanitizer::sanitizeString($orgId));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    $consentId = trim(InputSanitizer::sanitizeString($consentId));

    if ($orgId === '' || $userUUID === '') {
      return false;
    }

    if ($consentId !== '') {
      $consent = Database::hgetall(Keys::organizationConsent($consentId));

      return $consent !== []
        && (string) ($consent['org_id'] ?? '') === $orgId
        && (string) ($consent['user_uuid'] ?? '') === $userUUID
        && (string) ($consent['status'] ?? '') === self::MEMBERSHIP_STATE_ACTIVE;
    }

    return $this->loadActiveOrgConsent($orgId, $userUUID) !== [];
  }

  /**
   * Create an org-bound DEK wrap for an activated member using their existing passkey wrapper.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function bootstrapOrgDekWrapForMember(
    string $orgId,
    string $userUUID,
    string $consentId,
    string $segment,
    string $version
  ): array {
    $orgId = trim(InputSanitizer::sanitizeString($orgId));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    $consentId = trim(InputSanitizer::sanitizeString($consentId));
    $segment = trim(InputSanitizer::sanitizeString($segment));
    $version = trim(InputSanitizer::sanitizeString($version));

    if ($orgId === '' || $userUUID === '' || $consentId === '' || $segment === '' || $version === '') {
      return $this->fail('Cannot create org DEK wrap: missing bootstrap identifiers.');
    }

    $dekId = $this->organizationDekId($orgId, $segment, $userUUID, $version);
    if ($dekId === '') {
      return $this->fail('Cannot create org DEK wrap: invalid DEK identifier context.');
    }

    $resolvedWrap = $this->resolveMemberPasskeyWrapForOrgBootstrap($userUUID);
    if (!$resolvedWrap['success']) {
      return $resolvedWrap;
    }

    $credentialId = self::scalarString($resolvedWrap['data']['credential_id'] ?? '');
    $wrappedDek = self::scalarString($resolvedWrap['data']['wrapped_dek'] ?? '');
    if ($credentialId === '' || $wrappedDek === '') {
      return $this->fail('Cannot create org DEK wrap: missing credential wrapper material.');
    }

    $store = (new OrganizationEncryptionService())->storeOrgDekWrap(
      $orgId,
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

    $this->appendAuditEvent($orgId, 'org.dek.wrap.bootstrap', $userUUID, [
      'user_uuid' => $userUUID,
      'segment' => $segment,
      'key_version' => $version,
      'dek_id' => $dekId,
      'credential_id' => $credentialId,
      'consent_id' => $consentId,
    ]);

    return $this->ok('Organization DEK wrap initialized for member.', [
      'organization_id' => $orgId,
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
   * Bootstrap org DEK wraps for every active member in the organization.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function bootstrapOrgDekForAllMembers(
    string $actorUUID,
    string $orgId,
    string $segment,
    string $version
  ): array {
    $actorUUID = trim(InputSanitizer::sanitizeString($actorUUID));
    $orgId = trim(InputSanitizer::sanitizeString($orgId));
    $segment = trim(InputSanitizer::sanitizeString($segment));
    $version = trim(InputSanitizer::sanitizeString($version));

    if ($actorUUID === '' || $orgId === '' || $segment === '' || $version === '') {
      return $this->fail('Actor, organization, segment, and version are required.');
    }

    $gate = $this->requireAdminPreviewOrSelfOrg($actorUUID, $orgId);
    if (null !== $gate) {
      return $gate;
    }

    if (!$this->canManageAccess($orgId, $actorUUID)) {
      return $this->fail('Only owners and managers can bootstrap org DEKs for members.');
    }

    $allowedSegments = [
      self::ORG_DEK_SEGMENT_CURRENT_PERIOD => true,
      self::ORG_DEK_SEGMENT_ARCHIVE => true,
    ];
    if (!isset($allowedSegments[$segment])) {
      return $this->fail('Invalid org DEK segment.');
    }

    $members = array_map(
      static fn (mixed $value): string => trim((string) $value),
      Database::smembers(Keys::ORGANIZATION_MEMBERS . ':' . $orgId)
    );
    $members = array_values(array_unique(array_filter($members, static fn (string $uuid): bool => $uuid !== '')));
    sort($members, SORT_STRING);

    if ($members === []) {
      return $this->fail('No organization members were found to bootstrap.');
    }

    $bootstrapped = [];
    $failed = [];

    foreach ($members as $memberUUID) {
      $relationship = $this->relationship($orgId, $memberUUID);
      if ((string) ($relationship['status'] ?? '') !== self::MEMBERSHIP_STATE_ACTIVE) {
        $failed[] = [
          'user_uuid' => $memberUUID,
          'reason' => 'inactive_membership',
          'message' => 'Membership is not active.',
        ];
        continue;
      }

      $consent = $this->loadActiveOrgConsent($orgId, $memberUUID);
      $consentId = self::scalarString($consent['consent_id'] ?? '');
      if ($consentId === '') {
        $failed[] = [
          'user_uuid' => $memberUUID,
          'reason' => 'missing_active_consent',
          'message' => 'No active DEK-sharing consent found for member.',
        ];
        continue;
      }

      $result = $this->bootstrapOrgDekWrapForMember($orgId, $memberUUID, $consentId, $segment, $version);
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
      $registryKey = Keys::organizationDekRegistry($orgId, $segment);
      $versionKey = Keys::organizationDekVersion($orgId, $segment, $version);

      Database::hset($registryKey, [
        'organization_id' => $orgId,
        'segment' => $segment,
        'active_version' => $version,
        'updated_at' => $timestamp,
        'updated_by' => $actorUUID,
      ]);

      Database::hset($versionKey, [
        'organization_id' => $orgId,
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

    $this->appendAuditEvent($orgId, 'org.dek.wrap.bootstrap.bulk', $actorUUID, [
      'segment' => $segment,
      'key_version' => $version,
      'bootstrapped_count' => count($bootstrapped),
      'failed_count' => count($failed),
    ]);

    if ($bootstrapped === []) {
      return $this->fail('No organization member DEK wraps were bootstrapped.', [
        'organization_id' => $orgId,
        'segment' => $segment,
        'key_version' => $version,
        'bootstrapped' => [],
        'failed' => $failed,
        'bootstrapped_count' => 0,
        'failed_count' => count($failed),
      ]);
    }

    $message = $failed === []
      ? 'Organization DEK wraps bootstrapped for all active members.'
      : 'Organization DEK wraps bootstrapped with partial member failures.';

    return $this->ok($message, [
      'organization_id' => $orgId,
      'segment' => $segment,
      'key_version' => $version,
      'bootstrapped' => $bootstrapped,
      'failed' => $failed,
      'bootstrapped_count' => count($bootstrapped),
      'failed_count' => count($failed),
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function autoBootstrapOrgDekOnPageVisit(string $actorUUID): array
  {
    $actorUUID = trim(InputSanitizer::sanitizeString($actorUUID));
    if ($actorUUID === '') {
      return $this->fail('Actor is required.');
    }

    $list = $this->listForUser($actorUUID);
    if (!$list['success']) {
      return $list;
    }

    $organizations = is_array($list['data']['organizations'] ?? null)
      ? $list['data']['organizations']
      : [];

    $attemptedOrgs = [];
    $skippedOrgs = [];
    $throttleSeconds = 300;

    foreach ($organizations as $organization) {
      if (!is_array($organization)) {
        continue;
      }

      $orgId = self::scalarString($organization['organization_id'] ?? '');
      if ($orgId === '') {
        continue;
      }

      $orgStatus = strtolower(self::scalarString($organization['status'] ?? ''));
      $relationshipStatus = strtolower(self::scalarString($organization['relationship_status'] ?? ''));
      if ($orgStatus !== self::MEMBERSHIP_STATE_ACTIVE || $relationshipStatus !== self::MEMBERSHIP_STATE_ACTIVE) {
        $skippedOrgs[] = ['organization_id' => $orgId, 'reason' => 'inactive'];
        continue;
      }

      if (!$this->canManageAccess($orgId, $actorUUID)) {
        $skippedOrgs[] = ['organization_id' => $orgId, 'reason' => 'insufficient_access'];
        continue;
      }

      $throttleKey = Keys::TELEMETRY . ':org:dek:auto_bootstrap:' . $orgId . ':' . $actorUUID;
      if (Database::exists($throttleKey)) {
        $skippedOrgs[] = ['organization_id' => $orgId, 'reason' => 'throttled'];
        continue;
      }

      Database::set($throttleKey, '1', $throttleSeconds);

      $result = $this->bootstrapOrgDekForAllMembers(
        $actorUUID,
        $orgId,
        self::ORG_DEK_SEGMENT_CURRENT_PERIOD,
        '1'
      );

      $attemptedOrgs[] = [
        'organization_id' => $orgId,
        'success' => (bool) $result['success'],
        'bootstrapped_count' => self::scalarInt($result['data']['bootstrapped_count'] ?? 0),
        'failed_count' => self::scalarInt($result['data']['failed_count'] ?? 0),
        'message' => self::scalarString($result['message']),
      ];
    }

    return $this->ok('Auto-bootstrap evaluated for active organizations.', [
      'attempted_orgs' => $attemptedOrgs,
      'skipped_orgs' => $skippedOrgs,
      'attempted_count' => count($attemptedOrgs),
      'skipped_count' => count($skippedOrgs),
    ]);
  }

  /**
   * Build a stable organization DEK identifier for a member and segment.
   */
  private function organizationDekId(string $orgId, string $segment, string $userUUID, string $version): string
  {
    $orgId = trim(InputSanitizer::sanitizeString($orgId));
    $segment = trim(InputSanitizer::sanitizeString($segment));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    $version = trim(InputSanitizer::sanitizeString($version));

    if ($orgId === '' || $segment === '' || $userUUID === '' || $version === '') {
      return '';
    }

    return 'org-dek:' . $orgId . ':' . $segment . ':' . $userUUID . ':v' . $version;
  }

  /**
   * Resolve a member passkey wrapper suitable for org-wrap bootstrapping.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function resolveMemberPasskeyWrapForOrgBootstrap(string $userUUID): array
  {
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    if ($userUUID === '') {
      return $this->fail('Cannot resolve passkey wrapper: user id is required.');
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

    return $this->fail('No passkey-wrapped DEK is available for this member.');
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

  /** @param array<string, string> $relationship */
  private function setRelationship(string $orgId, string $userUUID, array $relationship): void
  {
    $newRole = strtolower(trim((string) ($relationship['role'] ?? '')));
    if ($newRole !== '' && !isset(self::VALID_ORG_ROLES[$newRole])) {
      throw new InvalidArgumentException("Invalid org role: {$newRole}");
    }

    if ($newRole !== '') {
      $relationship['role'] = $newRole;
    }

    $existing      = $this->relationship($orgId, $userUUID);
    $currentStatus = (string) ($existing['status'] ?? '');
    $newStatus     = (string) ($relationship['status'] ?? $currentStatus);

    if ($currentStatus !== $newStatus) {
      $allowed = self::RELATIONSHIP_TRANSITIONS[$currentStatus] ?? [];
      if (!isset($allowed[$newStatus])) {
        throw new InvalidArgumentException(
          "Invalid relationship transition: '{$currentStatus}' → '{$newStatus}'"
        );
      }
    }

    $fields = array_merge(['organization_id' => $orgId, 'user_uuid' => $userUUID], $relationship);

    $hasChange = false;
    foreach ($relationship as $field => $value) {
      if (!array_key_exists($field, $existing) || $existing[$field] !== (string) $value) {
        $hasChange = true;
        break;
      }
    }
    if ($hasChange) {
      $fields['updated_at'] = date('c');
    }

    $liveStatuses = ['active' => true, 'pending' => true];
    $isNowLive    = isset($liveStatuses[$newStatus]);
    $wasLive      = isset($liveStatuses[$currentStatus]);

    $relKey     = $this->relationshipKey($orgId, $userUUID);
    $membersKey = Keys::ORGANIZATION_MEMBERS . ':' . $orgId;
    $userKey    = Keys::ORGANIZATION_USER . ':' . $userUUID;

    Database::transaction(static function (\Redis $r) use ($relKey, $membersKey, $userKey, $orgId, $userUUID, $fields, $isNowLive, $wasLive): void {
      $r->hMSet($relKey, $fields);

      if ($isNowLive && !$wasLive) {
        $r->sAdd($membersKey, $userUUID);
        $r->sAdd($userKey, $orgId);
      } elseif (!$isNowLive && $wasLive) {
        $r->sRem($membersKey, $userUUID);
        $r->sRem($userKey, $orgId);
      }
    });
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
   * Maps an organization role to its hierarchy rank.
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
  private function relationship(string $orgId, string $userUUID): array
  {
    return Database::hgetall($this->relationshipKey($orgId, $userUUID));
  }

  /**
   * Handles relationshipKey operation.
   */
  private function relationshipKey(string $orgId, string $userUUID): string
  {
    return Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $userUUID;
  }

  /**
   * Handles accessRequestActiveKey operation.
   */
  private function accessRequestActiveKey(string $orgId, string $requesterUUID): string
  {
    return Keys::ORGANIZATION_ACCESS_REQUEST_ACTIVE . ':' . $orgId . ':' . $requesterUUID;
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
    Database::incr(Keys::TELEMETRY . ':organization:access_request:' . $suffix . ':' . $day);
  }

  /**
   * Handles findPersonalOrganizationId operation.
   */
  private function findPersonalOrganizationId(string $ownerUUID): string
  {
    foreach (Database::smembers(Keys::ORGANIZATION_OWNER . ':' . $ownerUUID) as $orgId) {
      $organization = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
      if (($organization['organization_type'] ?? 'shared') === 'personal') {
        return (string) $orgId;
      }
    }

    return '';
  }

  /**
   * Handles findPreferredOrganizationIdForOwner operation.
   */
  private function findPreferredOrganizationIdForOwner(string $ownerUUID): string
  {
    $orgIds = Database::smembers(Keys::ORGANIZATION_OWNER . ':' . $ownerUUID);
    sort($orgIds, SORT_STRING);

    $personalFallback = '';
    foreach ($orgIds as $orgId) {
      $organization = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
      if ([] === $organization) {
        continue;
      }

      if ((string) ($organization['status'] ?? 'active') !== 'active') {
        continue;
      }

      $type = (string) ($organization['organization_type'] ?? 'shared');
      if ($type === 'shared') {
        return (string) $orgId;
      }

      if ($type === 'personal' && $personalFallback === '') {
        $personalFallback = (string) $orgId;
      }
    }

    return $personalFallback;
  }

  /**
   * Handles normalizeOrganizationType operation.
   */
  private function normalizeOrganizationType(mixed $value): string
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
   * Handles defaultPersonalOrganizationName operation.
   */
  private function defaultPersonalOrganizationName(User $owner, string $fallback = 'Personal Organization'): string
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

  /** @param array<string, string> $organization
   *  @param array<string, string> $normalizedSettings */
  private function syncPersonalOrganizationSettingsToOwner(string $orgId, array $organization, array $normalizedSettings): void
  {
    $ownerUUID = (string) ($organization['owner_uuid'] ?? '');
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
        '[OrgC] Personal organization pay period sync failed: ' . $e->getMessage(),
        'organization_id=' . $orgId . ' owner_uuid=' . $ownerUUID
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
   * Handles generateOrganizationId operation.
   */
  private function generateOrganizationId(string $ownerUUID, string $name): string
  {
    return 'ORG' . substr(hash('sha256', $ownerUUID . '|' . $name . '|' . bin2hex(random_bytes(16))), 0, 18);
  }

  /** @param array<string, scalar> $details */
  private function appendAuditEvent(string $orgId, string $eventType, string $actorUUID, array $details = []): void
  {
    $eventID = 'OAE' . substr(hash('sha256', $orgId . '|' . $eventType . '|' . bin2hex(random_bytes(16))), 0, 20);
    $createdAt = date('c');

    $normalizedDetails = [];
    foreach ($details as $key => $value) {
      $normalizedDetails[$key] = (string) $value;
    }

    Database::hset(Keys::ORGANIZATION_AUDIT_EVENT . ':' . $eventID, [
      'event_id' => $eventID,
      'organization_id' => $orgId,
      'event_type' => $eventType,
      'actor_uuid' => $actorUUID,
      'details' => json_encode($normalizedDetails, JSON_UNESCAPED_SLASHES) ?: '{}',
      'created_at' => $createdAt,
    ]);

    Database::sadd(Keys::ORGANIZATION_AUDIT . ':' . $orgId, $eventID);

    OrganizationSignalHooks::onOrganizationAuditEvent([
      'event_id' => $eventID,
      'organization_id' => $orgId,
      'event_type' => $eventType,
      'actor_uuid' => $actorUUID,
      'details' => json_encode($normalizedDetails, JSON_UNESCAPED_SLASHES) ?: '{}',
      'created_at' => $createdAt,
    ]);

    (new OrganizationNotificationService())->fanoutAuditEvent(
      orgId: $orgId,
      eventType: $eventType,
      actorUUID: $actorUUID,
      details: $normalizedDetails,
      createdAt: $createdAt
    );
  }

  /** @param array<string, scalar> $details */
  public function appendOrganizationAuditEvent(string $orgId, string $eventType, string $actorUUID, array $details = []): void
  {
    $orgId = trim(InputSanitizer::sanitizeString($orgId));
    $eventType = trim(InputSanitizer::sanitizeString($eventType));
    $actorUUID = trim(InputSanitizer::sanitizeString($actorUUID));

    if ($orgId === '' || $eventType === '' || $actorUUID === '') {
      return;
    }

    $this->appendAuditEvent($orgId, $eventType, $actorUUID, $details);
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


