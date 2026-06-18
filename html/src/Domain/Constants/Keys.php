<?php declare(strict_types=1);

namespace PayCal\Domain\Constants;

/**
 * Keys.php
 *
 * Purpose: Redis key namespace constants for all PayCal hash prefixes, with roadmap annotations for reserved and unused keys.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Constants
 * @package    PayCal\Domain\Constants
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */


final class Keys
{
  public const SEPARATOR          = ":";

  public const EMAIL              = "email";
  
  /**
   * UNUSED: Reserved for future pay period management features.
   * Roadmap: May be used for storing pay period metadata, schedules,
   * and pay cycle information once pay period management is fully implemented.
   */
  public const PAY_PERIOD         = "pay_period";
  
  public const SITE               = "site";
  public const SESSION            = "session";
  public const USER               = "user";
  public const USER_SUBSCRIPTION  = "user:subscription";
  public const WORK               = "work";
  public const ARCHIVED           = "archived";
  
  /**
   * Lock boundary cache for historical record locking.
   * Stores calculated lock boundary dates per user to avoid recomputation.
   * Format: lock_boundary:{user_uuid} → "YYYY-MM-DD"
   * TTL: 1 hour (3600 seconds)
   */
  public const LOCK_BOUNDARY      = "lock_boundary";
  
  public const EARNING            = "earning";
  public const SETTINGS           = "settings";
  public const SYSTEM             = "system";
  public const TELEMETRY          = "telemetry";
  
    /**
     * Cache prefix for temporary cached data.
     * Used for metrics caching to prevent redundant expensive queries.
     * Format: cache:{namespace}:{key}
     * TTL: Varies by use case (typically 60-600 seconds)
     */
    public const CACHE              = "cache";
  
  /**
   * UNUSED: Reserved for password reset token storage and management.
   * Roadmap: Will store temporary password reset tokens with TTL,
   * allowing secure email-based password recovery flows.
   */
  public const PASSWORD_RESET     = "password_reset";
  
  /**
   * UNUSED: Reserved for email verification and change verification flows.
   * Roadmap: Will store email verification tokens for new email addresses
   * during email change operations and initial account verification.
   */
  public const EMAIL_VERIFICATION = "email_verification";
  
  public const VERIFICATION_CODES = "verification_codes";
  
  /**
   * UNUSED: Reserved for hierarchical site structure and metadata storage.
   * Roadmap: May store parent-child site relationships, site categories,
   * and business hierarchy once advanced site management is implemented.
   */
  public const SITE_STRUCTURE     = "site_structure";
  
  public const SITE_SALT          = "f43i9ihsD23fGf9y3FFs34d8bg89rmnj";

  // Organization discovery and delegated access model
  public const BUSINESS                = 'business';
  public const BUSINESS_SETTINGS       = 'business:settings';
  public const BUSINESS_USER           = 'business:user';
  public const BUSINESS_OWNER          = 'business:owner';
  public const BUSINESS_SITE           = 'business:site';
  public const BUSINESS_SITE_SETTINGS  = 'business:site_settings';
  public const BUSINESS_MEMBERS        = 'business:members';
  public const BUSINESS_RELATIONSHIPS  = 'business:relationships';
  public const BUSINESS_RELATIONSHIPS_USER = 'business:relationships:user';
  public const BUSINESS_PENDING        = 'business:pending';
  public const BUSINESS_RELATIONSHIP   = 'business:relationship';
  public const BUSINESS_INVITE         = 'business:invite';
  public const BUSINESS_INVITE_EMAIL   = 'business:invite:email';
  public const BUSINESS_INVITE_ORG     = 'business:invite:org';
  public const BUSINESS_INVITE_TOKEN   = 'business:invite:token';
  public const BUSINESS_INVITE_IMPORT_PREPARE = 'business:invite:import:prepare';
  public const BUSINESS_INVITE_IMPORT_CHALLENGE = 'business:invite:import:challenge';
  public const BUSINESS_AUDIT          = 'business:audit';
  public const BUSINESS_AUDIT_EVENT    = 'business:audit:event';
  public const BUSINESS_SEARCH_INDEX   = 'business:search:index';
  public const BUSINESS_SEARCH_ENTRY   = 'business:search:entry';
  public const BUSINESS_MODERATION_QUEUE = 'business:moderation:queue';
  public const BUSINESS_NOTIFICATION_UNREAD_USER = 'business:notification:unread:user';
  public const BUSINESS_NOTIFICATION_TOTAL_USER = 'business:notification:total:user';
  public const BUSINESS_NOTIFICATION_LAST_READ = 'business:notification:last_read';
  public const BUSINESS_NOTIFICATION_PUBSUB = 'business:notification:pubsub';
  public const BUSINESS_NOTIFICATION_EVENTS_ORG = 'business:notification:events:org';
  public const BUSINESS_CONSENT        = 'business:consent';
  public const BUSINESS_CONSENTS_ORG   = 'business:consents:org';
  public const BUSINESS_CONSENTS_USER  = 'business:consents:user';
  public const BUSINESS_DEK            = 'business:dek';
  public const BUSINESS_DEK_VERSION    = 'business:dek:version';
  public const BUSINESS_DEK_WRAP       = 'business:dek:wrap';
  /**
   * Materialized members-grid cache (financial summary columns per member).
   * Format: business:cache:members:{businessId} → JSON payload
   * TTL: 900 seconds (safety net; mutations DEL the key eagerly).
   */
  public const BUSINESS_CACHE_MEMBERS  = 'business:cache:members';
  /**
   * Pre-warmed business workspace cache segments (roster, sites, team earnings, etc.).
   * Format: business:cache:workspace:{segment}:{businessId}[:{year}] → JSON payload
   */
  public const BUSINESS_CACHE_WORKSPACE = 'business:cache:workspace';
  /**
   * In-flight workspace cache warm lock (SET NX EX).
   * Format: business:cache:warm:lock:{businessId}
   */
  public const BUSINESS_CACHE_WARM_LOCK = 'business:cache:warm:lock';
  /**
   * Lightweight O(1) dashboard counters (members/work/pending queues).
   * Format: business:metrics:{segment}:{businessId}[:{date}]
   */
  public const BUSINESS_METRICS = 'business:metrics';
  /**
   * Versioned org metadata cache (roster snapshot + fast counters).
   * Format: business:snapshot:{businessId} → HASH (snapshot_version, payload, generated_at)
   */
  public const BUSINESS_SNAPSHOT = 'business:snapshot';
  /**
   * Immutable org locked-period payroll metrics.
   * Format: org_locked_period_metrics:{businessId}:{year} → HASH memberUuid → JSON
   */
  public const ORG_LOCKED_PERIOD_METRICS = 'org_locked_period_metrics';
  public const BUSINESS_AUDIT_CONTROL_TEST = 'business:audit:control_test';
  public const BUSINESS_AUDIT_CONTROL_TEST_INDEX = 'business:audit:control_test:index';
  public const SYSTEM_AUDIT                = 'system:audit';
  public const SYSTEM_AUDIT_EVENT          = 'system:audit:event';
  public const SYSTEM_AUDIT_LEDGER         = 'system:audit:ledger';
  public const SYSTEM_AUDIT_LEDGER_BLOCK   = 'system:audit:ledger:block';
  public const SYSTEM_AUDIT_LEDGER_EVENT   = 'system:audit:ledger:event';
  public const SYSTEM_AUDIT_ANCHOR         = 'system:audit:anchor';
  public const SYSTEM_AUDIT_BLOCKCHAIN         = 'system:audit:blockchain';
  public const SYSTEM_AUDIT_VERIFICATION_REPORT = 'system:audit:verification_report';
  public const SYSTEM_AUDIT_PUBSUB         = 'system:audit:pubsub';
  /** Stores the GCS object path + SHA-256 hash of the last successfully uploaded evidence artifact.
   *  Used to chain evidence objects: each artifact references the previous one by path and hash.
   *  Value: JSON {"object_path":"...","object_hash":"..."} — no TTL (permanent chain anchor). */
  public const SYSTEM_AUDIT_GCS_CHAIN_TIP  = 'system:audit:gcs:chain_tip';
  public const BUSINESS_ACCESS_REQUEST  = 'business:access:request';
  public const BUSINESS_ACCESS_REQUEST_ORG = 'business:access:request:org';
  public const BUSINESS_ACCESS_REQUEST_REQUESTER = 'business:access:request:requester';
  public const BUSINESS_ACCESS_REQUEST_ACTIVE = 'business:access:request:active';
  public const BILLING_WEBHOOK_EVENT       = 'billing:webhook:event';
  public const BILLING_WEBHOOK_QUEUE       = 'billing:webhook:queue';
  public const BILLING_WEBHOOK_DEAD_LETTER = 'billing:webhook:dead_letter';

  public const WEBAUTHN           = "webauthn";
  public const CREDENTIAL         = "credential";
  public const CHALLENGE          = "challenge";
  public const KEK                = "kek";
  public const KEK_V1             = "v1";

  /**
   * Handles userKekV1 operation.
   */
  public static function userKekV1(string $userId): string
  {
    return self::USER . self::SEPARATOR . self::KEK . self::SEPARATOR . self::KEK_V1 . self::SEPARATOR . $userId;
  }

  /**
   * Handles webauthnUserCredentials operation.
   */
  public static function webauthnUserCredentials(string $userUUID): string
  {
    return self::WEBAUTHN . self::SEPARATOR . self::USER . self::SEPARATOR . $userUUID . self::SEPARATOR . 'credentials';
  }

  /**
   * Handles webauthnCredential operation.
   */
  public static function webauthnCredential(string $credentialId): string
  {
    return self::WEBAUTHN . self::SEPARATOR . self::CREDENTIAL . self::SEPARATOR . $credentialId;
  }

  /**
   * Handles webauthnChallenge operation.
   */
  public static function webauthnChallenge(string $flow, string $challengeId): string
  {
    return self::WEBAUTHN . self::SEPARATOR . self::CHALLENGE . self::SEPARATOR . $flow . self::SEPARATOR . $challengeId;
  }

  /**
   * Email change transaction storage.
   * Format: email_change:txn:{txn_id}
   * Stores: user_uuid, old_email, new_email, old_code_hash, new_code_hash,
   *         old_verified, new_verified, expires_at, created_at, last_sent_at,
   *         verify_attempts, resend_count, status
   */
  public static function emailChangeTransaction(string $txnId): string
  {
    return 'email_change:txn' . self::SEPARATOR . $txnId;
  }

  /**
   * Recovery email code storage.
   * Format: recovery_email:code:{user_uuid}
   * Stores: code_hash, expires_at, created_at
   */
  public static function recoveryEmailCode(string $userUuid): string
  {
    return 'recovery_email:code' . self::SEPARATOR . $userUuid;
  }

  /**
   * Handles businessInviteImportPrepare operation.
   */
  public static function businessInviteImportPrepare(string $importId): string
  {
    return self::BUSINESS_INVITE_IMPORT_PREPARE . self::SEPARATOR . $importId;
  }

  /**
   * Handles businessInviteImportChallenge operation.
   */
  public static function businessInviteImportChallenge(string $challengeId): string
  {
    return self::BUSINESS_INVITE_IMPORT_CHALLENGE . self::SEPARATOR . $challengeId;
  }

  /**
   * Handles businessConsent operation.
   */
  public static function businessConsent(string $consentId): string
  {
    return self::BUSINESS_CONSENT . self::SEPARATOR . $consentId;
  }

  /**
   * Handles businessConsentsByOrg operation.
   */
  public static function businessConsentsByOrg(string $orgId): string
  {
    return self::BUSINESS_CONSENTS_ORG . self::SEPARATOR . $orgId;
  }

  /**
   * Handles businessConsentsByUser operation.
   */
  public static function businessConsentsByUser(string $userUUID): string
  {
    return self::BUSINESS_CONSENTS_USER . self::SEPARATOR . $userUUID;
  }

  /**
   * Handles businessDekRegistry operation.
   */
  public static function businessDekRegistry(string $orgId, string $segment): string
  {
    return self::BUSINESS_DEK . self::SEPARATOR . $orgId . self::SEPARATOR . $segment;
  }

  /**
   * Handles businessDekVersion operation.
   */
  public static function businessDekVersion(string $orgId, string $segment, string $version): string
  {
    return self::BUSINESS_DEK_VERSION . self::SEPARATOR . $orgId . self::SEPARATOR . $segment . self::SEPARATOR . $version;
  }

  /**
   * Handles businessDekWrap operation.
   */
  public static function businessDekWrap(
    string $orgId,
    string $segment,
    string $version,
    string $userUUID,
    string $credentialId
  ): string {
    return self::BUSINESS_DEK_WRAP
      . self::SEPARATOR . $orgId
      . self::SEPARATOR . $segment
      . self::SEPARATOR . $version
      . self::SEPARATOR . $userUUID
      . self::SEPARATOR . $credentialId;
  }

  /**
   * Handles businessAuditControlTest operation.
   */
  public static function businessAuditControlTest(string $testId): string
  {
    return self::BUSINESS_AUDIT_CONTROL_TEST . self::SEPARATOR . $testId;
  }

  /**
   * Handles businessAuditControlTestIndex operation.
   */
  public static function businessAuditControlTestIndex(string $orgId): string
  {
    return self::BUSINESS_AUDIT_CONTROL_TEST_INDEX . self::SEPARATOR . $orgId;
  }

  /**
   * Handles businessNotificationUnreadByUser operation.
   * Hash fields are business IDs and values are unread counts.
   */
  public static function businessNotificationUnreadByUser(string $userUUID): string
  {
    return self::BUSINESS_NOTIFICATION_UNREAD_USER . self::SEPARATOR . $userUUID;
  }

  /**
   * Handles businessNotificationTotalByUser operation.
   */
  public static function businessNotificationTotalByUser(string $userUUID): string
  {
    return self::BUSINESS_NOTIFICATION_TOTAL_USER . self::SEPARATOR . $userUUID;
  }

  /**
   * Handles businessNotificationLastRead operation.
   */
  public static function businessNotificationLastRead(string $orgId, string $userUUID): string
  {
    return self::BUSINESS_NOTIFICATION_LAST_READ . self::SEPARATOR . $orgId . self::SEPARATOR . $userUUID;
  }

  /**
   * Handles businessNotificationEventsByOrg operation.
   * Redis list storing recent pub/sub event payload snapshots for pull fallback.
   */
  public static function businessNotificationEventsByOrg(string $orgId): string
  {
    return self::BUSINESS_NOTIFICATION_EVENTS_ORG . self::SEPARATOR . $orgId;
  }

  /**
   * Handles businessNotificationChannelOrg operation.
   */
  public static function businessNotificationChannelOrg(string $orgId): string
  {
    return self::BUSINESS_NOTIFICATION_PUBSUB . self::SEPARATOR . 'org' . self::SEPARATOR . $orgId;
  }

  /**
   * Handles businessNotificationChannelRole operation.
   */
  public static function businessNotificationChannelRole(string $orgId, string $role): string
  {
    return self::businessNotificationChannelOrg($orgId)
      . self::SEPARATOR . 'role' . self::SEPARATOR . strtolower(trim($role));
  }

  /**
   * Handles businessNotificationChannelUser operation.
   */
  public static function businessNotificationChannelUser(string $userUUID): string
  {
    return self::BUSINESS_NOTIFICATION_PUBSUB . self::SEPARATOR . 'user' . self::SEPARATOR . $userUUID;
  }

  /**
   * Handles accountRecoveryTransaction operation.
   */
  public static function accountRecoveryTransaction(string $txnId): string
  {
    return 'account_recovery:txn' . self::SEPARATOR . $txnId;
  }

  /**
   * Handles accountRecoveryActiveTransaction operation.
   */
  public static function accountRecoveryActiveTransaction(string $userUuid): string
  {
    return 'account_recovery:active' . self::SEPARATOR . $userUuid;
  }

  /**
   * Handles accountRecoveryMagicLink operation.
   */
  public static function accountRecoveryMagicLink(string $token): string
  {
    return 'account_recovery:magic_link' . self::SEPARATOR . $token;
  }

  /**
   * Handles accountRecoveryReplayCounter operation.
   */
  public static function accountRecoveryReplayCounter(string $ipHash, string $window): string
  {
    return 'account_recovery:replay:ip' . self::SEPARATOR . $ipHash . self::SEPARATOR . $window;
  }

  /**
   * Handles accountRecoveryBlockedIp operation.
   */
  public static function accountRecoveryBlockedIp(string $ipHash): string
  {
    return 'account_recovery:blocked_ip' . self::SEPARATOR . $ipHash;
  }

  /**
   * Handles accountRecoveryTelemetry operation.
   */
  public static function accountRecoveryTelemetry(string $metric, string $date): string
  {
    return self::TELEMETRY . self::SEPARATOR . 'security' . self::SEPARATOR . 'recovery' . self::SEPARATOR . $metric . self::SEPARATOR . $date;
  }

  /**
   * Handles businessMembersCache operation.
   */
  public static function businessMembersCache(string $businessId): string
  {
    return self::BUSINESS_CACHE_MEMBERS . self::SEPARATOR . $businessId;
  }

  /**
   * Handles businessWorkspaceCache operation.
   */
  public static function businessWorkspaceCache(string $segment, string $businessId, ?int $year = null): string
  {
    $key = self::BUSINESS_CACHE_WORKSPACE . self::SEPARATOR . $segment . self::SEPARATOR . $businessId;
    if ($year !== null) {
      $key .= self::SEPARATOR . (string) $year;
    }

    return $key;
  }

  /**
   * Pattern for deleting all workspace cache keys for a business.
   */
  public static function businessWorkspaceCachePattern(string $businessId): string
  {
    return self::BUSINESS_CACHE_WORKSPACE . self::SEPARATOR . '*' . self::SEPARATOR . $businessId . '*';
  }

  /**
   * Handles businessWorkspaceWarmLock operation.
   */
  public static function businessWorkspaceWarmLock(string $businessId): string
  {
    return self::BUSINESS_CACHE_WARM_LOCK . self::SEPARATOR . $businessId;
  }

  /**
   * Handles capabilityToken operation.
   */
  public static function capabilityToken(string $userUuid, string $token): string
  {
    return 'capability' . self::SEPARATOR . 'token' . self::SEPARATOR . $userUuid . self::SEPARATOR . $token;
  }

  /**
   * Handles capabilityReplay operation.
   */
  public static function capabilityReplay(string $token): string
  {
    return 'capability' . self::SEPARATOR . 'replay' . self::SEPARATOR . $token;
  }

  /**
   * Handles systemAuditLedgerBlock operation.
   */
  public static function systemAuditLedgerBlock(int $sequence): string
  {
    return self::SYSTEM_AUDIT_LEDGER_BLOCK . self::SEPARATOR . (string) $sequence;
  }

  /**
   * Handles systemAuditLedgerEventSequence operation.
   */
  public static function systemAuditLedgerEventSequence(string $eventId): string
  {
    return self::SYSTEM_AUDIT_LEDGER_EVENT . self::SEPARATOR . $eventId;
  }

  /**
   * Handles systemAuditLedgerHeadSequence operation.
   */
  public static function systemAuditLedgerHeadSequence(): string
  {
    return self::SYSTEM_AUDIT_LEDGER . self::SEPARATOR . 'head_sequence';
  }

  /**
   * Handles systemAuditLedgerHeadHash operation.
   */
  public static function systemAuditLedgerHeadHash(): string
  {
    return self::SYSTEM_AUDIT_LEDGER . self::SEPARATOR . 'head_hash';
  }

  /**
   * Handles systemAuditLedgerSequenceCounter operation.
   */
  public static function systemAuditLedgerSequenceCounter(): string
  {
    return self::SYSTEM_AUDIT_LEDGER . self::SEPARATOR . 'sequence';
  }

  /**
   * Handles systemAuditLedgerOrder operation.
   */
  public static function systemAuditLedgerOrder(): string
  {
    return self::SYSTEM_AUDIT_LEDGER . self::SEPARATOR . 'order';
  }

  /**
   * Handles systemAuditAnchor operation.
   */
  public static function systemAuditAnchor(string $anchorId): string
  {
    return self::SYSTEM_AUDIT_ANCHOR . self::SEPARATOR . $anchorId;
  }

  /**
   * Handles systemAuditAnchorIndex operation.
   */
  public static function systemAuditAnchorIndex(): string
  {
    return self::SYSTEM_AUDIT_ANCHOR . self::SEPARATOR . 'index';
  }

  /**
   * Handles systemAuditBlockchainAnchorQueue operation.
   */
  public static function systemAuditBlockchainAnchorQueue(): string
  {
    return self::SYSTEM_AUDIT_BLOCKCHAIN . self::SEPARATOR . 'anchor_queue';
  }

  /**
   * Redis pub/sub channel for real-time system audit event fan-out.
   * Published to by SystemAuditRepository::append(); currently consumed by the
   * legacy SSE stream and reserved for future fan-out paths.
   */
  public static function systemAuditPubsubChannel(): string
  {
    return self::SYSTEM_AUDIT_PUBSUB;
  }

  /**
   * TODO: Document businessMetricsPendingInvites.
   */
  public static function businessMetricsPendingInvites(string $businessId): string
  {
    return self::BUSINESS_METRICS . self::SEPARATOR . 'pending_invites' . self::SEPARATOR . $businessId;
  }

  /**
   * TODO: Document businessMetricsPendingRequests.
   */
  public static function businessMetricsPendingRequests(string $businessId): string
  {
    return self::BUSINESS_METRICS . self::SEPARATOR . 'pending_requests' . self::SEPARATOR . $businessId;
  }

  /**
   * TODO: Document businessMetricsWorkDay.
   */
  public static function businessMetricsWorkDay(string $businessId, string $dateYmd): string
  {
    return self::BUSINESS_METRICS . self::SEPARATOR . 'work' . self::SEPARATOR . $businessId . self::SEPARATOR . $dateYmd;
  }

  /**
   * TODO: Document businessSnapshot.
   */
  public static function businessSnapshot(string $businessId): string
  {
    return self::BUSINESS_SNAPSHOT . self::SEPARATOR . $businessId;
  }

  /**
   * TODO: Document orgLockedPeriodMetrics.
   */
  public static function orgLockedPeriodMetrics(string $businessId, int $year): string
  {
    return self::ORG_LOCKED_PERIOD_METRICS . self::SEPARATOR . $businessId . self::SEPARATOR . (string) $year;
  }

  // Legacy organization aliases (public transitional code; same Redis namespaces as business:*).
  public const ORGANIZATION = self::BUSINESS;
  public const ORGANIZATION_SETTINGS = self::BUSINESS_SETTINGS;
  public const ORGANIZATION_USER = self::BUSINESS_USER;
  public const ORGANIZATION_OWNER = self::BUSINESS_OWNER;
  public const ORGANIZATION_SITE = self::BUSINESS_SITE;
  public const ORGANIZATION_SITE_SETTINGS = self::BUSINESS_SITE_SETTINGS;
  public const ORGANIZATION_MEMBERS = self::BUSINESS_MEMBERS;
  public const ORGANIZATION_RELATIONSHIP = self::BUSINESS_RELATIONSHIP;
  public const ORGANIZATION_INVITE = self::BUSINESS_INVITE;
  public const ORGANIZATION_INVITE_EMAIL = self::BUSINESS_INVITE_EMAIL;
  public const ORGANIZATION_INVITE_ORG = self::BUSINESS_INVITE_ORG;
  public const ORGANIZATION_INVITE_TOKEN = self::BUSINESS_INVITE_TOKEN;
  public const ORGANIZATION_AUDIT = self::BUSINESS_AUDIT;
  public const ORGANIZATION_AUDIT_EVENT = self::BUSINESS_AUDIT_EVENT;
  public const ORGANIZATION_ACCESS_REQUEST = self::BUSINESS_ACCESS_REQUEST;
  public const ORGANIZATION_ACCESS_REQUEST_ORG = self::BUSINESS_ACCESS_REQUEST_ORG;
  public const ORGANIZATION_ACCESS_REQUEST_REQUESTER = self::BUSINESS_ACCESS_REQUEST_REQUESTER;
  public const ORGANIZATION_ACCESS_REQUEST_ACTIVE = self::BUSINESS_ACCESS_REQUEST_ACTIVE;
  public const ORGANIZATION_DEK_WRAP = self::BUSINESS_DEK_WRAP;

  /**
   * TODO: Document organizationInviteImportPrepare.
   */
  public static function organizationInviteImportPrepare(string $importId): string
  {
    return self::businessInviteImportPrepare($importId);
  }

  /**
   * TODO: Document organizationInviteImportChallenge.
   */
  public static function organizationInviteImportChallenge(string $challengeId): string
  {
    return self::businessInviteImportChallenge($challengeId);
  }

  /**
   * TODO: Document organizationConsent.
   */
  public static function organizationConsent(string $consentId): string
  {
    return self::businessConsent($consentId);
  }

  /**
   * TODO: Document organizationConsentsByOrg.
   */
  public static function organizationConsentsByOrg(string $orgId): string
  {
    return self::businessConsentsByOrg($orgId);
  }

  /**
   * TODO: Document organizationConsentsByUser.
   */
  public static function organizationConsentsByUser(string $userUUID): string
  {
    return self::businessConsentsByUser($userUUID);
  }

  /**
   * TODO: Document organizationDekRegistry.
   */
  public static function organizationDekRegistry(string $orgId, string $segment): string
  {
    return self::businessDekRegistry($orgId, $segment);
  }

  /**
   * TODO: Document organizationDekVersion.
   */
  public static function organizationDekVersion(string $orgId, string $segment, string $version): string
  {
    return self::businessDekVersion($orgId, $segment, $version);
  }

  /**
   * TODO: Document organizationDekWrap.
   */
  public static function organizationDekWrap(
    string $orgId,
    string $segment,
    string $version,
    string $userUUID,
    string $credentialId,
  ): string {
    return self::businessDekWrap($orgId, $segment, $version, $userUUID, $credentialId);
  }

  /**
   * TODO: Document organizationAuditControlTest.
   */
  public static function organizationAuditControlTest(string $testId): string
  {
    return self::businessAuditControlTest($testId);
  }

  /**
   * TODO: Document organizationAuditControlTestIndex.
   */
  public static function organizationAuditControlTestIndex(string $orgId): string
  {
    return self::businessAuditControlTestIndex($orgId);
  }

  /**
   * TODO: Document organizationNotificationUnreadByUser.
   */
  public static function organizationNotificationUnreadByUser(string $userUUID): string
  {
    return self::businessNotificationUnreadByUser($userUUID);
  }

  /**
   * TODO: Document organizationNotificationTotalByUser.
   */
  public static function organizationNotificationTotalByUser(string $userUUID): string
  {
    return self::businessNotificationTotalByUser($userUUID);
  }

  /**
   * TODO: Document organizationNotificationLastRead.
   */
  public static function organizationNotificationLastRead(string $orgId, string $userUUID): string
  {
    return self::businessNotificationLastRead($orgId, $userUUID);
  }

  /**
   * TODO: Document organizationNotificationEventsByOrg.
   */
  public static function organizationNotificationEventsByOrg(string $orgId): string
  {
    return self::businessNotificationEventsByOrg($orgId);
  }

  /**
   * TODO: Document organizationNotificationChannelOrg.
   */
  public static function organizationNotificationChannelOrg(string $orgId): string
  {
    return self::businessNotificationChannelOrg($orgId);
  }

  /**
   * TODO: Document organizationNotificationChannelRole.
   */
  public static function organizationNotificationChannelRole(string $orgId, string $role): string
  {
    return self::businessNotificationChannelRole($orgId, $role);
  }

  /**
   * TODO: Document organizationNotificationChannelUser.
   */
  public static function organizationNotificationChannelUser(string $userUUID): string
  {
    return self::businessNotificationChannelUser($userUUID);
  }
}
