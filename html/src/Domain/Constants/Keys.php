<?php declare(strict_types=1);

namespace PayCal\Domain\Constants;

/**
 * Keys.php
 *
 * Purpose: Define the Keys class for PayCal\Domain\Constants.
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



/**
 * Keys
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
   * and organizational hierarchy once advanced site management is implemented.
   */
  public const SITE_STRUCTURE     = "site_structure";
  
  public const SITE_SALT          = "f43i9ihsD23fGf9y3FFs34d8bg89rmnj";

  // Organization discovery and delegated access model
  public const ORGANIZATION                = 'organization';
  public const ORGANIZATION_SETTINGS       = 'organization:settings';
  public const ORGANIZATION_USER           = 'organization:user';
  public const ORGANIZATION_OWNER          = 'organization:owner';
  public const ORGANIZATION_SITE           = 'organization:site';
  public const ORGANIZATION_MEMBERS        = 'organization:members';
  public const ORGANIZATION_RELATIONSHIP   = 'organization:relationship';
  public const ORGANIZATION_INVITE         = 'organization:invite';
  public const ORGANIZATION_INVITE_EMAIL   = 'organization:invite:email';
  public const ORGANIZATION_INVITE_ORG     = 'organization:invite:org';
  public const ORGANIZATION_INVITE_TOKEN   = 'organization:invite:token';
  public const ORGANIZATION_INVITE_IMPORT_PREPARE = 'organization:invite:import:prepare';
  public const ORGANIZATION_INVITE_IMPORT_CHALLENGE = 'organization:invite:import:challenge';
  public const ORGANIZATION_AUDIT          = 'organization:audit';
  public const ORGANIZATION_AUDIT_EVENT    = 'organization:audit:event';
  public const ORGANIZATION_NOTIFICATION_UNREAD_USER = 'organization:notification:unread:user';
  public const ORGANIZATION_NOTIFICATION_TOTAL_USER = 'organization:notification:total:user';
  public const ORGANIZATION_NOTIFICATION_LAST_READ = 'organization:notification:last_read';
  public const ORGANIZATION_NOTIFICATION_PUBSUB = 'organization:notification:pubsub';
  public const ORGANIZATION_NOTIFICATION_EVENTS_ORG = 'organization:notification:events:org';
  public const ORGANIZATION_CONSENT        = 'organization:consent';
  public const ORGANIZATION_CONSENTS_ORG   = 'organization:consents:org';
  public const ORGANIZATION_CONSENTS_USER  = 'organization:consents:user';
  public const ORGANIZATION_DEK            = 'organization:dek';
  public const ORGANIZATION_DEK_VERSION    = 'organization:dek:version';
  public const ORGANIZATION_DEK_WRAP       = 'organization:dek:wrap';
  public const ORGANIZATION_AUDIT_CONTROL_TEST = 'organization:audit:control_test';
  public const ORGANIZATION_AUDIT_CONTROL_TEST_INDEX = 'organization:audit:control_test:index';
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
  public const ORGANIZATION_ACCESS_REQUEST  = 'organization:access:request';
  public const ORGANIZATION_ACCESS_REQUEST_ORG = 'organization:access:request:org';
  public const ORGANIZATION_ACCESS_REQUEST_REQUESTER = 'organization:access:request:requester';
  public const ORGANIZATION_ACCESS_REQUEST_ACTIVE = 'organization:access:request:active';
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
   * Handles organizationInviteImportPrepare operation.
   */
  public static function organizationInviteImportPrepare(string $importId): string
  {
    return self::ORGANIZATION_INVITE_IMPORT_PREPARE . self::SEPARATOR . $importId;
  }

  /**
   * Handles organizationInviteImportChallenge operation.
   */
  public static function organizationInviteImportChallenge(string $challengeId): string
  {
    return self::ORGANIZATION_INVITE_IMPORT_CHALLENGE . self::SEPARATOR . $challengeId;
  }

  /**
   * Handles organizationConsent operation.
   */
  public static function organizationConsent(string $consentId): string
  {
    return self::ORGANIZATION_CONSENT . self::SEPARATOR . $consentId;
  }

  /**
   * Handles organizationConsentsByOrg operation.
   */
  public static function organizationConsentsByOrg(string $orgId): string
  {
    return self::ORGANIZATION_CONSENTS_ORG . self::SEPARATOR . $orgId;
  }

  /**
   * Handles organizationConsentsByUser operation.
   */
  public static function organizationConsentsByUser(string $userUUID): string
  {
    return self::ORGANIZATION_CONSENTS_USER . self::SEPARATOR . $userUUID;
  }

  /**
   * Handles organizationDekRegistry operation.
   */
  public static function organizationDekRegistry(string $orgId, string $segment): string
  {
    return self::ORGANIZATION_DEK . self::SEPARATOR . $orgId . self::SEPARATOR . $segment;
  }

  /**
   * Handles organizationDekVersion operation.
   */
  public static function organizationDekVersion(string $orgId, string $segment, string $version): string
  {
    return self::ORGANIZATION_DEK_VERSION . self::SEPARATOR . $orgId . self::SEPARATOR . $segment . self::SEPARATOR . $version;
  }

  /**
   * Handles organizationDekWrap operation.
   */
  public static function organizationDekWrap(
    string $orgId,
    string $segment,
    string $version,
    string $userUUID,
    string $credentialId
  ): string {
    return self::ORGANIZATION_DEK_WRAP
      . self::SEPARATOR . $orgId
      . self::SEPARATOR . $segment
      . self::SEPARATOR . $version
      . self::SEPARATOR . $userUUID
      . self::SEPARATOR . $credentialId;
  }

  /**
   * Handles organizationAuditControlTest operation.
   */
  public static function organizationAuditControlTest(string $testId): string
  {
    return self::ORGANIZATION_AUDIT_CONTROL_TEST . self::SEPARATOR . $testId;
  }

  /**
   * Handles organizationAuditControlTestIndex operation.
   */
  public static function organizationAuditControlTestIndex(string $orgId): string
  {
    return self::ORGANIZATION_AUDIT_CONTROL_TEST_INDEX . self::SEPARATOR . $orgId;
  }

  /**
   * Handles organizationNotificationUnreadByUser operation.
   * Hash fields are organization IDs and values are unread counts.
   */
  public static function organizationNotificationUnreadByUser(string $userUUID): string
  {
    return self::ORGANIZATION_NOTIFICATION_UNREAD_USER . self::SEPARATOR . $userUUID;
  }

  /**
   * Handles organizationNotificationTotalByUser operation.
   */
  public static function organizationNotificationTotalByUser(string $userUUID): string
  {
    return self::ORGANIZATION_NOTIFICATION_TOTAL_USER . self::SEPARATOR . $userUUID;
  }

  /**
   * Handles organizationNotificationLastRead operation.
   */
  public static function organizationNotificationLastRead(string $orgId, string $userUUID): string
  {
    return self::ORGANIZATION_NOTIFICATION_LAST_READ . self::SEPARATOR . $orgId . self::SEPARATOR . $userUUID;
  }

  /**
   * Handles organizationNotificationEventsByOrg operation.
   * Redis list storing recent pub/sub event payload snapshots for pull fallback.
   */
  public static function organizationNotificationEventsByOrg(string $orgId): string
  {
    return self::ORGANIZATION_NOTIFICATION_EVENTS_ORG . self::SEPARATOR . $orgId;
  }

  /**
   * Handles organizationNotificationChannelOrg operation.
   */
  public static function organizationNotificationChannelOrg(string $orgId): string
  {
    return self::ORGANIZATION_NOTIFICATION_PUBSUB . self::SEPARATOR . 'org' . self::SEPARATOR . $orgId;
  }

  /**
   * Handles organizationNotificationChannelRole operation.
   */
  public static function organizationNotificationChannelRole(string $orgId, string $role): string
  {
    return self::organizationNotificationChannelOrg($orgId)
      . self::SEPARATOR . 'role' . self::SEPARATOR . strtolower(trim($role));
  }

  /**
   * Handles organizationNotificationChannelUser operation.
   */
  public static function organizationNotificationChannelUser(string $userUUID): string
  {
    return self::ORGANIZATION_NOTIFICATION_PUBSUB . self::SEPARATOR . 'user' . self::SEPARATOR . $userUUID;
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
   * Handles systemAuditVerificationReport operation.
   * Stores a single JSON verification report keyed by RFC 3339 timestamp.
   */
  public static function systemAuditVerificationReport(string $timestamp): string
  {
    return self::SYSTEM_AUDIT_VERIFICATION_REPORT . self::SEPARATOR . $timestamp;
  }

  /**
   * Sorted set of verification report timestamps (score = unix timestamp).
   * Used to page through historical verification runs via zrangebyscore.
   */
  public static function systemAuditVerificationReportIndex(): string
  {
    return self::SYSTEM_AUDIT_VERIFICATION_REPORT . self::SEPARATOR . 'index';
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
}

