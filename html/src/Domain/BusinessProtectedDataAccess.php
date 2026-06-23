<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Constants\Keys;
use PayCal\Infrastructure\Business\BusinessEncryptionService;

/**
 * Canonical gate for business-scoped protected member work reads.
 *
 * Protected business member work rows must originate here before any report,
 * export, warmer, or business cache materializes them.
 */
final class BusinessProtectedDataAccess
{
  /**
   * @param array<string, array<string, array<string, string>>>|null $cachedMemberWork
   * @return array{success: bool, message: string, reason: string, data: array<string, mixed>}
   */
  public function readMemberWork(
    string $actorUUID,
    string $businessId,
    string $memberUUID,
    ?int $year = null,
    ?array $cachedMemberWork = null,
    bool $audit = true,
    string $operation = 'business.member.report.read',
  ): array {
    $actorUUID = trim($actorUUID);
    $businessId = trim($businessId);
    $memberUUID = trim($memberUUID);
    $year = $this->normalizeYear($year);

    $context = $this->resolveContext($actorUUID, $businessId, $memberUUID);
    if (!$context['success']) {
      if ($audit) {
        $this->auditDenied($businessId, $actorUUID, $memberUUID, $operation, $year, $context['reason']);
      }

      return $context;
    }

    /** @var array<string, string> $targetConnection */
    $targetConnection = $context['data']['target_connection'];
    $isSelfRead = (bool) ($context['data']['is_self_read'] ?? false);
    /** @var array<string, array{site_owner_uuid: string, site_id: string, site_hash: array<string, string>}> $businessSiteIndex */
    $businessSiteIndex = $context['data']['business_site_index'];
    $businessOwnerUUID = $this->detailString($context['data'], 'business_owner_uuid');

    if ($cachedMemberWork !== null && isset($cachedMemberWork[$memberUUID])) {
      $entries = $this->normalizeEntries($cachedMemberWork[$memberUUID]);
    } elseif ($year !== null) {
      $fetched = MemberWorkEntriesFetcher::fetchForMembers([$memberUUID], $year);
      $entries = $this->normalizeEntries($fetched[$memberUUID] ?? []);
    } else {
      $fetched = MemberWorkEntriesFetcher::fetchForMembers([$memberUUID], null);
      $entries = $this->normalizeEntries($fetched[$memberUUID] ?? []);
    }
    if ($year !== null) {
      $entries = $this->filterEntriesByYear($entries, $year);
    }

    $filtered = [];
    $firstConsentId = '';
    $firstDekId = '';
    $firstWrapKey = '';

    foreach ($entries as $workKey => $entry) {
      $decision = BusinessWorkVisibilityPolicy::evaluateWorkEntry(
        $businessId,
        $businessOwnerUUID,
        $memberUUID,
        (string) $workKey,
        $entry,
        $targetConnection,
        $businessSiteIndex,
        true,
      );
      if (!$decision['allowed']) {
        continue;
      }

      if (!$isSelfRead) {
        $entryGate = $this->validateEntryEnvelopeAndWrap($businessId, $memberUUID, $actorUUID, $entry);
        if (!$entryGate['success']) {
          continue;
        }

        if ($firstConsentId === '') {
          $firstConsentId = $this->detailString($entryGate['data'], 'consent_id');
          $firstDekId = $this->detailString($entryGate['data'], 'dek_id');
          $firstWrapKey = $this->detailString($entryGate['data'], 'wrap_key');
        }
      }

      $filtered[(string) $workKey] = $entry;
    }

    if ($audit) {
      $this->appendAuditEvent($businessId, 'business.member.report.read.started', $actorUUID, [
        'operation' => $operation,
        'target_member_uuid' => $memberUUID,
        'year' => $year !== null ? (string) $year : '',
        'entry_count' => (string) count($filtered),
        'consent_id' => $firstConsentId,
        'dek_id' => $firstDekId,
        'wrap_key' => $firstWrapKey,
        'result' => 'allowed',
      ]);
    }

    return [
      'success' => true,
      'message' => 'Protected business member work read allowed.',
      'reason' => '',
      'data' => [
        'entries' => $filtered,
        'connection' => $targetConnection,
        'business_site_index' => $businessSiteIndex,
        'business_owner_uuid' => $businessOwnerUUID,
        'consent_id' => $firstConsentId,
        'dek_id' => $firstDekId,
        'wrap_key' => $firstWrapKey,
      ],
    ];
  }

  /**
   * @param list<string> $memberUUIDs
   * @return array<string, array<string, array<string, string>>>
   */
  public function readMembersWork(
    string $actorUUID,
    string $businessId,
    array $memberUUIDs,
    ?int $year = null,
    bool $audit = false,
    string $operation = 'business.member.report.batch.read',
  ): array {
    $cachedMemberWork = BusinessWorkspaceCache::getMemberWork($businessId);
    $resolved = [];
    foreach ($memberUUIDs as $memberUUID) {
      $result = $this->readMemberWork(
        $actorUUID,
        $businessId,
        $memberUUID,
        $year,
        $cachedMemberWork,
        $audit,
        $operation,
      );
      if ($result['success']) {
        $resolved[$memberUUID] = $this->normalizeEntries($result['data']['entries'] ?? []);
      } else {
        $resolved[$memberUUID] = [];
      }
    }

    return $resolved;
  }

  /**
   * @return array{success: bool, message: string, reason: string, data: array<string, mixed>}
   */
  private function resolveContext(string $actorUUID, string $businessId, string $memberUUID): array
  {
    if ($actorUUID === '' || $businessId === '' || $memberUUID === '') {
      return $this->deny('missing_context', 'Actor, business, and member are required.');
    }

    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    $businessOwnerUUID = trim((string) ($business['owner_uuid'] ?? ''));
    if ($business === [] || $businessOwnerUUID === '') {
      return $this->deny('missing_business', 'Business not found.');
    }

    $actorConnection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $businessId . ':' . $actorUUID);
    if (!$this->actorCanReadProtectedWork($actorConnection, $actorUUID, $businessOwnerUUID)) {
      return $this->deny('actor_not_authorized', 'Actor is not authorized to read protected business work.');
    }

    $targetConnection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $businessId . ':' . $memberUUID);
    if ((string) ($targetConnection['status'] ?? '') !== BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE) {
      return $this->deny('target_membership_inactive', Strings::i18n('BUSINESS_CONSENT_STATUS_SKIPPED'));
    }

    if (!BusinessWorkVisibilityPolicy::connectionPermitsPayrollVisibility($targetConnection)) {
      return $this->deny('target_not_payroll_visible', Strings::i18n('BUSINESS_PROTECTED_WORK_NOT_AVAILABLE'));
    }

    $isSelfRead = $actorUUID === $memberUUID;
    if (
      !$isSelfRead
      && (bool) SystemConfig::get('business_shared_encryption_enabled')
      && !$this->hasActiveConsentAndWrap($businessId, $memberUUID)
    ) {
      return $this->deny('missing_consent_or_wrap', Strings::i18n('BUSINESS_CONSENT_STATUS_SKIPPED'));
    }

    $businessSiteIndex = BusinessWorkVisibilityPolicy::buildMemberReportSiteIndex($businessId, $memberUUID);
    if ($businessSiteIndex === []) {
      return $this->deny('missing_business_site_index', Strings::i18n('BUSINESS_PROTECTED_WORK_NO_SHARED_WORK'));
    }

    return [
      'success' => true,
      'message' => 'Protected business member context resolved.',
      'reason' => '',
      'data' => [
        'actor_connection' => $actorConnection,
        'target_connection' => $targetConnection,
        'business_owner_uuid' => $businessOwnerUUID,
        'business_site_index' => $businessSiteIndex,
        'is_self_read' => $isSelfRead,
      ],
    ];
  }

  /** @param array<string, string> $connection */
  private function actorCanReadProtectedWork(array $connection, string $actorUUID, string $businessOwnerUUID): bool
  {
    if ($actorUUID === $businessOwnerUUID) {
      return true;
    }

    if ((string) ($connection['status'] ?? '') !== BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE) {
      return false;
    }

    $role = strtolower(trim((string) ($connection['role'] ?? '')));
    if (in_array($role, ['owner', 'coordinator'], true)) {
      return true;
    }

    $scopes = $this->scopeMap((string) ($connection['scopes'] ?? ''));

    return isset($scopes['all']) || (isset($scopes['work.read']) && isset($scopes['work.scope.business']));
  }

  /**
   * Has active consent and wrap.
   */
  private function hasActiveConsentAndWrap(string $businessId, string $memberUUID): bool
  {
    foreach ($this->activeConsentIds($businessId, $memberUUID) as $consentId) {
      if ($this->findActiveWrap($businessId, $memberUUID, '', '', $consentId)['success']) {
        return true;
      }
    }

    return false;
  }

  /**
   * @param array<string, string> $entry
   * @return array{success: bool, message: string, reason: string, data: array<string, mixed>}
   */
  private function validateEntryEnvelopeAndWrap(string $businessId, string $memberUUID, string $actorUUID, array $entry): array
  {
    if (!(bool) SystemConfig::get('business_shared_encryption_enabled')) {
      return [
        'success' => true,
        'message' => 'Business shared encryption is disabled.',
        'reason' => '',
        'data' => [],
      ];
    }

    $blob = trim((string) ($entry['encrypted_blob'] ?? ''));
    if ($blob === '') {
      return $this->deny('missing_encrypted_blob', 'Protected business work requires an encrypted envelope.');
    }

    $contextValidation = WorkEntry::validateBusinessEnvelopeContext($blob, $businessId);
    if (!$contextValidation['valid']) {
      return $this->deny($contextValidation['error'], 'Encrypted envelope does not match business context.');
    }

    $meta = $this->decodeEnvelopeMeta($blob);
    $segment = (string) ($meta['segment'] ?? BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD);
    $version = (string) ($meta['key_version'] ?? '');
    $dekId = (string) ($meta['dek_id'] ?? '');
    if ($segment === '' || $version === '' || $dekId === '') {
      return $this->deny('missing_envelope_key_context', 'Encrypted envelope is missing key context.');
    }

    foreach ($this->activeConsentIds($businessId, $memberUUID) as $consentId) {
      $wrap = $this->findActiveWrap($businessId, $memberUUID, $segment, $version, $consentId, $dekId);
      if ($wrap['success']) {
        $this->appendAuditEvent($businessId, 'business.dek.unwrap.succeeded', $actorUUID, [
          'target_member_uuid' => $memberUUID,
          'consent_id' => $consentId,
          'segment' => $segment,
          'key_version' => $version,
          'dek_id' => $dekId,
          'wrap_key' => $this->detailString($wrap['data'], 'wrap_key'),
          'result' => 'allowed',
        ]);

        return $wrap;
      }
    }

    return $this->deny('missing_active_wrap_for_envelope', Strings::i18n('BUSINESS_CONSENT_STATUS_MISSING_SETUP'));
  }

  /**
   * @return list<string>
   */
  private function activeConsentIds(string $businessId, string $memberUUID): array
  {
    $ids = [];
    foreach (Database::smembers(Keys::businessConsentsByUser($memberUUID)) as $consentIdRaw) {
      $consentId = trim((string) $consentIdRaw);
      if ($consentId === '') {
        continue;
      }

      $consent = Database::hgetall(Keys::businessConsent($consentId));
      if (
        $consent !== []
        && (string) ($consent['business_id'] ?? '') === $businessId
        && (string) ($consent['user_uuid'] ?? '') === $memberUUID
        && (string) ($consent['status'] ?? '') === BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE
      ) {
        $ids[] = $consentId;
      }
    }

    return array_values(array_unique($ids));
  }

  /**
   * @return array{success: bool, message: string, reason: string, data: array<string, mixed>}
   */
  private function findActiveWrap(
    string $businessId,
    string $memberUUID,
    string $segment = '',
    string $version = '',
    string $consentId = '',
    string $dekId = '',
  ): array {
    $segmentPattern = $segment !== '' ? $segment : '*';
    $versionPattern = $version !== '' ? $version : '*';
    $pattern = Keys::BUSINESS_DEK_WRAP . ':' . $businessId . ':' . $segmentPattern . ':' . $versionPattern . ':' . $memberUUID . ':*';
    $service = new BusinessEncryptionService();

    foreach (Database::scanKeys($pattern) as $key) {
      $parts = explode(':', (string) $key);
      $credentialId = (string) ($parts[7] ?? '');
      $keySegment = (string) ($parts[4] ?? $segment);
      $keyVersion = (string) ($parts[5] ?? $version);
      if ($credentialId === '' || $keySegment === '' || $keyVersion === '') {
        continue;
      }

      $resolved = $service->resolveActiveWrapForUnwrap(
        $businessId,
        $keySegment,
        $keyVersion,
        $memberUUID,
        $credentialId,
        $consentId,
        $dekId,
      );
      if ($resolved['success']) {
        return [
          'success' => true,
          'message' => 'Active business DEK wrap resolved.',
          'reason' => '',
          'data' => $resolved['data'],
        ];
      }
    }

    return $this->deny('missing_active_wrap', Strings::i18n('BUSINESS_CONSENT_STATUS_MISSING_SETUP'));
  }

  /**
   * @return array<string, string>
   */
  private function decodeEnvelopeMeta(string $blob): array
  {
    $decoded = base64_decode($blob, true);
    if ($decoded === false) {
      return [];
    }

    $envelope = json_decode($decoded, true);
    if (!is_array($envelope)) {
      return [];
    }

    $meta = is_array($envelope['meta'] ?? null) ? $envelope['meta'] : $envelope;
    $normalized = [];
    foreach (['encryption_mode', 'business_id', 'segment', 'key_version', 'dek_id'] as $field) {
      $value = $meta[$field] ?? null;
      if (is_scalar($value)) {
        $normalized[$field] = trim((string) $value);
      }
    }

    return $normalized;
  }

  /**
   * @param array<string, array<string, string>> $entries
   * @return array<string, array<string, string>>
   */
  private function filterEntriesByYear(array $entries, int $year): array
  {
    $yearPrefix = (string) $year;
    $filtered = [];
    foreach ($entries as $workKey => $entry) {
      $keyParts = explode(':', (string) $workKey);
      $isArchived = isset($keyParts[1]) && $keyParts[1] === 'archived';
      $date = $isArchived
        ? (string) ($keyParts[3] ?? ($entry['date'] ?? ''))
        : (string) ($keyParts[2] ?? ($entry['date'] ?? ''));
      if ($date !== '' && str_starts_with($date, $yearPrefix)) {
        $filtered[(string) $workKey] = $entry;
      }
    }

    return $filtered;
  }

  /**
   * @param mixed $entries
   * @return array<string, array<string, string>>
   */
  private function normalizeEntries(mixed $entries): array
  {
    if (!is_array($entries)) {
      return [];
    }

    $normalized = [];
    foreach ($entries as $workKey => $entry) {
      if (!is_string($workKey) || !is_array($entry)) {
        continue;
      }

      $row = [];
      foreach ($entry as $key => $value) {
        if (is_string($key) && is_scalar($value)) {
          $row[$key] = (string) $value;
        }
      }
      $normalized[$workKey] = $row;
    }

    return $normalized;
  }

  /** @param array<string, mixed> $details */
  private function detailString(array $details, string $key): string
  {
    $value = $details[$key] ?? '';
    return is_scalar($value) ? trim((string) $value) : '';
  }

  /** @return array<string, bool> */
  private function scopeMap(string $scopeCSV): array
  {
    $map = [];
    foreach (explode(',', $scopeCSV) as $scope) {
      $scope = trim($scope);
      if ($scope !== '') {
        $map[$scope] = true;
      }
    }

    return $map;
  }

  /**
   * Normalize year.
   */
  private function normalizeYear(?int $year): ?int
  {
    if ($year === null) {
      return null;
    }

    return $year >= 2000 && $year <= 2100 ? $year : (int) date('Y');
  }

  /**
   * @return array{success: bool, message: string, reason: string, data: array<string, mixed>}
   */
  private function deny(string $reason, string $message): array
  {
    return [
      'success' => false,
      'message' => $message,
      'reason' => $reason,
      'data' => [],
    ];
  }

  /**
   * Audit denied.
   */
  private function auditDenied(
    string $businessId,
    string $actorUUID,
    string $memberUUID,
    string $operation,
    ?int $year,
    string $reason,
  ): void {
    $this->appendAuditEvent($businessId, 'business.member.report.read.denied', $actorUUID, [
      'operation' => $operation,
      'target_member_uuid' => $memberUUID,
      'year' => $year !== null ? (string) $year : '',
      'result' => 'denied',
      'reason' => $reason,
    ]);
  }

  /**
   * @param array<string, scalar|array<mixed>> $details
   */
  private function appendAuditEvent(string $businessId, string $eventType, string $actorUUID, array $details): void
  {
    if (trim($businessId) === '' || trim($eventType) === '' || trim($actorUUID) === '') {
      return;
    }

    try {
      (new BusinessDiscoveryService())->appendBusinessAuditEvent($businessId, $eventType, $actorUUID, $details);
    } catch (\Throwable) {
      // Audit must never turn an allowed read into partial data leakage or a 500.
    }
  }
}
