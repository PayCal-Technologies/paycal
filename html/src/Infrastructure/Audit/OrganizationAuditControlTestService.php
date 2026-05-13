<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Audit;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\OrganizationDiscoveryService;
use PayCal\Infrastructure\Audit\SystemAuditRepository;

/**
 * OrganizationAuditControlTestService.php
 *
 * Purpose: Controlled organization-audit test service for producing verifiable
 * failure artifacts, ledger evidence, and downstream alert fanout.
 *
 * Developer notes:
 * - This path exists to produce deliberate audit-control evidence without
 *   weakening or mutating the underlying production controls.
 * - Keep evidence capture, ledger writes, and alert fanout explicit so the
 *   generated artifact chain remains auditor-verifiable.
 *
 * Architectural role:
 * - Reusable domain service for controlled organization-scoped audit test
 *   execution and evidence generation.
 * - Encapsulates audit-control test orchestration outside the HTTP layer.
 *
 * @category   Infrastructure
 * @package    PayCal\Infrastructure\Audit
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * OrganizationAuditControlTestService
 *
 * Purpose: Generate a controlled organization-scoped audit failure artifact for
 * owner/authorized-manager testing. This path is intentionally explicit: it
 * records the test in Redis, appends an immutable TheLedger system-audit event,
 * fans out an organization audit event, and uploads a chained GCS alert
 * artifact when GCS evidence storage is configured.
 *
 * Why this file exists:
 * - SOC 2 CC7.3 evidence requires at least one demonstrable alert/failure path.
 * - The scheduled verifier proves continuous integrity checks, but auditors also
 *   need proof that failure handling and alert evidence capture work end-to-end.
 * - This service provides a controlled test path without tampering with the
 *   ledger itself or weakening production controls.
 */
final class OrganizationAuditControlTestService
{
  private const REDIS_RETENTION_SECONDS = 365 * 24 * 3600;
  private const DEFAULT_SUMMARY = 'Manual organization audit control test';
  private const DEFAULT_WEBHOOK_ERROR = 'manual_control_test';

  private OrganizationDiscoveryService $organizationService;

  /** @var \Closure(array<string, scalar>, array<string, scalar>, string, array{object_path: string, object_hash: string}): array{uploaded: bool, object_path: string, object_hash: string, http_code: int, error: string, attempts: int} */
  private \Closure $alertArtifactUploader;

  public function __construct(
    ?OrganizationDiscoveryService $organizationService = null,
    ?\Closure $alertArtifactUploader = null,
  ) {
    $this->organizationService = $organizationService ?? new OrganizationDiscoveryService();
    $this->alertArtifactUploader = $alertArtifactUploader ?? static function (
      array $verificationResult,
      array $webhookResult,
      string $timestampIso8601,
      array $previousChainTip,
    ): array {
      require_once dirname(__DIR__, 4) . '/copilot-scripts/GcsEvidenceUploader.php';

      if (!class_exists('GcsEvidenceUploader')) {
        return [
          'uploaded' => false,
          'object_path' => '',
          'object_hash' => '',
          'http_code' => 0,
          'error' => 'gcs_uploader_class_missing',
          'attempts' => 0,
        ];
      }

      if (!\GcsEvidenceUploader::isConfigured()) {
        return [
          'uploaded' => false,
          'object_path' => '',
          'object_hash' => '',
          'http_code' => 0,
          'error' => 'gcs_not_configured',
          'attempts' => 0,
        ];
      }

      $uploader = \GcsEvidenceUploader::fromEnv();

      return $uploader->uploadAlertArtifact($verificationResult, $webhookResult, $timestampIso8601, $previousChainTip);
    };
  }

  /**
   * @param array<string, mixed> $options
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function generateErrorTest(string $actorUUID, string $orgId, array $options = []): array
  {
    $actorUUID = trim(InputSanitizer::sanitizeString($actorUUID));
    $orgId = trim(InputSanitizer::sanitizeString($orgId));

    if ($actorUUID === '' || $orgId === '') {
      return $this->fail('Organization and actor are required.');
    }

    $organization = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
    if ([] === $organization) {
      return $this->fail('Organization not found.');
    }

    if (!$this->organizationService->canTriggerAuditControlTest($orgId, $actorUUID)) {
      return $this->fail('You do not have permission to generate an audit control test error.');
    }

    $summaryRaw = $options['summary'] ?? self::DEFAULT_SUMMARY;
    $summary = is_scalar($summaryRaw) ? trim((string) $summaryRaw) : self::DEFAULT_SUMMARY;
    if ($summary === '') {
      $summary = self::DEFAULT_SUMMARY;
    }
    if (strlen($summary) > 240) {
      $summary = substr($summary, 0, 240);
    }

    $sourceRaw = $options['source'] ?? 'organizations_ui';
    $source = is_scalar($sourceRaw) ? trim((string) $sourceRaw) : 'organizations_ui';
    if ($source === '') {
      $source = 'organizations_ui';
    }

    $timestamp = date('c');
    $testId = 'OACT' . substr(hash('sha256', $orgId . '|' . $actorUUID . '|' . bin2hex(random_bytes(16))), 0, 20);
    $orgName = (string) ($organization['name'] ?? $orgId);
    $actorRelationship = $this->organizationService->getRelationshipSummary($orgId, $actorUUID);
    $actorRole = (string) ($actorRelationship['role'] ?? ((string) ($organization['owner_uuid'] ?? '') === $actorUUID ? 'owner' : ''));

    $systemAuditEventId = SystemAuditRepository::append(
      'organization.audit_control_test.error_generated',
      $actorUUID,
      [
        'organization_id' => $orgId,
        'organization_name' => $orgName,
        'summary' => $summary,
        'source' => $source,
        'test_id' => $testId,
        'actor_role' => $actorRole,
      ]
    );

    $this->organizationService->appendOrganizationAuditEvent(
      $orgId,
      'audit.control_test.error_generated',
      $actorUUID,
      [
        'summary' => $summary,
        'source' => $source,
        'test_id' => $testId,
        'system_audit_event_id' => $systemAuditEventId,
      ]
    );

    $proof = SystemAuditRepository::proofForEvent($systemAuditEventId);
    $verificationResult = [
      'ok' => false,
      'checked_blocks' => (string) ((int) ($proof['sequence'] ?? 0)),
      'head_sequence' => (string) ((int) Database::get(Keys::systemAuditLedgerHeadSequence())),
      'head_hash' => (string) Database::get(Keys::systemAuditLedgerHeadHash()),
      'reason' => 'manual_organization_audit_control_test',
      'organization_id' => $orgId,
      'organization_name' => $orgName,
      'test_id' => $testId,
      'summary' => $summary,
      'source' => $source,
      'system_audit_event_id' => $systemAuditEventId,
    ];

    $webhookResult = [
      'attempted' => false,
      'http_code' => 0,
      'delivered' => false,
      'error' => self::DEFAULT_WEBHOOK_ERROR,
    ];

    require_once dirname(__DIR__, 4) . '/copilot-scripts/GcsEvidenceUploader.php';
    $rawChainTip = (string) (Database::get(Keys::SYSTEM_AUDIT_GCS_CHAIN_TIP) ?: '');
    $chainTip = class_exists('GcsEvidenceUploader')
      ? \GcsEvidenceUploader::loadChainTip($rawChainTip)
      : ['object_path' => '', 'object_hash' => ''];

    $gcsResult = ($this->alertArtifactUploader)($verificationResult, $webhookResult, $timestamp, $chainTip);
    $gcsUploaded = $gcsResult['uploaded'];
    $gcsObjectPath = $gcsResult['object_path'];
    $gcsObjectHash = $gcsResult['object_hash'];
    $gcsHttpCode = $gcsResult['http_code'];
    $gcsAttempts = $gcsResult['attempts'];
    $gcsError = $gcsResult['error'];

    if ($gcsUploaded && class_exists('GcsEvidenceUploader')) {
      Database::set(
        Keys::SYSTEM_AUDIT_GCS_CHAIN_TIP,
        \GcsEvidenceUploader::persistChainTip($gcsObjectPath, $gcsObjectHash)
      );
    }

    $recordKey = Keys::organizationAuditControlTest($testId);
    Database::hsetex($recordKey, [
      'test_id' => $testId,
      'organization_id' => $orgId,
      'actor_uuid' => $actorUUID,
      'actor_role' => $actorRole,
      'summary' => $summary,
      'source' => $source,
      'created_at' => $timestamp,
      'system_audit_event_id' => $systemAuditEventId,
      'ledger_sequence' => (string) ($proof['sequence'] ?? ''),
      'ledger_block_hash' => (string) ($proof['block_hash'] ?? ''),
      'gcs_uploaded' => $gcsUploaded ? '1' : '0',
      'gcs_object_path' => $gcsObjectPath,
      'gcs_object_hash' => $gcsObjectHash,
      'gcs_http_code' => (string) $gcsHttpCode,
      'gcs_attempts' => (string) $gcsAttempts,
      'gcs_error' => $gcsError,
    ], self::REDIS_RETENTION_SECONDS);
    Database::sadd(Keys::organizationAuditControlTestIndex($orgId), $testId);

    $data = [
      'test_id' => $testId,
      'organization_id' => $orgId,
      'organization_name' => $orgName,
      'summary' => $summary,
      'source' => $source,
      'system_audit_event_id' => $systemAuditEventId,
      'ledger_proof' => $proof,
      'redis_record_key' => $recordKey,
      'gcs' => [
        'uploaded' => $gcsUploaded,
        'object_path' => $gcsObjectPath,
        'object_hash' => $gcsObjectHash,
        'http_code' => $gcsHttpCode,
        'attempts' => $gcsAttempts,
        'error' => $gcsError,
      ],
    ];

    if ($gcsUploaded) {
      return $this->ok('Audit control test error generated and alert artifact uploaded.', $data);
    }

    return $this->fail('Audit control test error generated, but GCS alert upload failed.', $data);
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
