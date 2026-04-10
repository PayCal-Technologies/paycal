<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * OrganizationEncryptionService
 *
 * Consent-bound helpers for storing and resolving organization DEK wraps.
 * This service enforces membership and consent integrity before unwrap use.
 */
final class OrganizationEncryptionService
{
  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function storeOrgDekWrap(
    string $orgId,
    string $segment,
    string $version,
    string $userUUID,
    string $credentialId,
    string $wrappedDek,
    string $consentId,
    string $kdfProfile = 'hkdf-passkey-v1',
    string $dekId = ''
  ): array {
    $orgId = trim(InputSanitizer::sanitizeString($orgId));
    $segment = trim(InputSanitizer::sanitizeString($segment));
    $version = trim(InputSanitizer::sanitizeString($version));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    $credentialId = trim((string) $credentialId);
    $wrappedDek = trim((string) $wrappedDek);
    $consentId = trim(InputSanitizer::sanitizeString($consentId));
    $dekId = trim(InputSanitizer::sanitizeString($dekId));

    if ($orgId === '' || $segment === '' || $version === '' || $userUUID === '' || $credentialId === '' || $wrappedDek === '') {
      return $this->fail('All wrap fields are required.');
    }

    if ($dekId === '') {
      $dekId = 'org-dek:' . $orgId . ':' . $segment . ':' . $userUUID . ':v' . $version;
    }

    if (!$this->isValidSegment($segment)) {
      return $this->fail('Invalid org DEK segment.');
    }

    if (!$this->isMembershipActive($orgId, $userUUID)) {
      return $this->fail('Only active members can receive org DEK wraps.');
    }

    if (!$this->isConsentBindingValid($orgId, $userUUID, $consentId)) {
      return $this->fail('A valid active consent binding is required for wrap creation.');
    }

    $key = Keys::organizationDekWrap($orgId, $segment, $version, $userUUID, $credentialId);
    $timestamp = date('c');

    Database::hset($key, [
      'org_id' => $orgId,
      'segment' => $segment,
      'key_version' => $version,
      'user_uuid' => $userUUID,
      'credential_id' => $credentialId,
      'dek_id' => $dekId,
      'wrapped_dek' => $wrappedDek,
      'kdf_profile' => $kdfProfile,
      'consent_id' => $consentId,
      'status' => OrganizationDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
      'created_at' => $timestamp,
      'updated_at' => $timestamp,
    ]);

    return $this->ok('Org DEK wrap stored.', [
      'wrap_key' => $key,
      'org_id' => $orgId,
      'segment' => $segment,
      'key_version' => $version,
      'user_uuid' => $userUUID,
      'credential_id' => $credentialId,
      'dek_id' => $dekId,
      'consent_id' => $consentId,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function resolveActiveWrapForUnwrap(
    string $orgId,
    string $segment,
    string $version,
    string $userUUID,
    string $credentialId,
    string $consentId = '',
    string $expectedDekId = ''
  ): array {
    $orgId = trim(InputSanitizer::sanitizeString($orgId));
    $segment = trim(InputSanitizer::sanitizeString($segment));
    $version = trim(InputSanitizer::sanitizeString($version));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    $credentialId = trim((string) $credentialId);
    $consentId = trim(InputSanitizer::sanitizeString($consentId));
    $expectedDekId = trim(InputSanitizer::sanitizeString($expectedDekId));

    if ($orgId === '' || $segment === '' || $version === '' || $userUUID === '' || $credentialId === '') {
      $this->incrementOrgUnwrapDeniedCounter('missing_wrap');
      return $this->fail('Wrap lookup fields are required.');
    }

    if (!$this->isMembershipActive($orgId, $userUUID)) {
      $this->incrementOrgUnwrapDeniedCounter('inactive_membership');
      return $this->fail('Membership is not active; unwrap denied.');
    }

    $key = Keys::organizationDekWrap($orgId, $segment, $version, $userUUID, $credentialId);
    $wrap = Database::hgetall($key);
    if ($wrap === []) {
      $this->incrementOrgUnwrapDeniedCounter('missing_wrap');
      return $this->fail('Org DEK wrap not found.');
    }

    if ((string) ($wrap['status'] ?? '') !== OrganizationDiscoveryService::MEMBERSHIP_STATE_ACTIVE) {
      $this->incrementOrgUnwrapDeniedCounter('inactive_membership');
      return $this->fail('Org DEK wrap is inactive.');
    }

    $wrapConsentId = trim((string) ($wrap['consent_id'] ?? ''));
    if ($wrapConsentId === '') {
      $this->incrementOrgUnwrapDeniedCounter('no_consent');
      return $this->fail('Org DEK wrap is missing consent binding.');
    }

    if ($consentId !== '' && $consentId !== $wrapConsentId) {
      $this->incrementOrgUnwrapDeniedCounter('no_consent');
      return $this->fail('Provided consent_id does not match wrap binding.');
    }

    if (!$this->isConsentBindingValid($orgId, $userUUID, $wrapConsentId)) {
      $this->incrementOrgUnwrapDeniedCounter('no_consent');
      return $this->fail('Consent binding is invalid or inactive; unwrap denied.');
    }

    if ($expectedDekId !== '') {
      $actualDekId = trim((string) ($wrap['dek_id'] ?? ''));
      if ($actualDekId === '' || $actualDekId !== $expectedDekId) {
        $this->incrementOrgUnwrapDeniedCounter('dek_mismatch');
        return $this->fail('Org DEK wrap does not match the envelope DEK id.');
      }
    }

    return $this->ok('Org DEK wrap resolved for active unwrap.', [
      'wrap_key' => $key,
      'wrapped_dek' => (string) ($wrap['wrapped_dek'] ?? ''),
      'kdf_profile' => (string) ($wrap['kdf_profile'] ?? ''),
      'consent_id' => $wrapConsentId,
      'key_version' => (string) ($wrap['key_version'] ?? ''),
      'segment' => (string) ($wrap['segment'] ?? ''),
      'credential_id' => (string) ($wrap['credential_id'] ?? ''),
      'dek_id' => (string) ($wrap['dek_id'] ?? ''),
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function revokeWrapsForMembership(string $orgId, string $userUUID, string $reason = 'membership_revoked'): array
  {
    $orgId = trim(InputSanitizer::sanitizeString($orgId));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    $reason = trim(InputSanitizer::sanitizeString($reason));

    if ($orgId === '' || $userUUID === '') {
      return $this->fail('Organization id and user id are required for wrap revocation.');
    }

    $pattern = Keys::ORGANIZATION_DEK_WRAP . ':' . $orgId . ':*:*:' . $userUUID . ':*';
    $keys = Database::scanKeys($pattern);
    $revokedCount = 0;
    $timestamp = date('c');

    foreach ($keys as $key) {
      if (!Database::exists($key)) {
        continue;
      }

      Database::hset($key, [
        'status' => OrganizationDiscoveryService::MEMBERSHIP_STATE_REVOKED,
        'revoked_at' => $timestamp,
        'revocation_reason' => $reason,
        'updated_at' => $timestamp,
      ]);
      $revokedCount += 1;
    }

    return $this->ok('Org DEK wraps revoked for membership.', [
      'organization_id' => $orgId,
      'user_uuid' => $userUUID,
      'revoked_wrap_count' => $revokedCount,
      'reason' => $reason,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function revokeWrapsForOrganization(string $orgId, string $reason = 'organization_removed'): array
  {
    $orgId = trim(InputSanitizer::sanitizeString($orgId));
    $reason = trim(InputSanitizer::sanitizeString($reason));

    if ($orgId === '') {
      return $this->fail('Organization id is required for org-wide wrap revocation.');
    }

    $pattern = Keys::ORGANIZATION_DEK_WRAP . ':' . $orgId . ':*';
    $keys = Database::scanKeys($pattern);
    $revokedCount = 0;
    $timestamp = date('c');

    foreach ($keys as $key) {
      if (!Database::exists($key)) {
        continue;
      }

      Database::hset($key, [
        'status' => OrganizationDiscoveryService::MEMBERSHIP_STATE_REVOKED,
        'revoked_at' => $timestamp,
        'revocation_reason' => $reason,
        'updated_at' => $timestamp,
      ]);
      $revokedCount += 1;
    }

    return $this->ok('Org DEK wraps revoked for organization.', [
      'organization_id' => $orgId,
      'revoked_wrap_count' => $revokedCount,
      'reason' => $reason,
    ]);
  }

  /**
   * Validate supported organization DEK segments.
   */
  private function isValidSegment(string $segment): bool
  {
    return in_array(
      $segment,
      [
        OrganizationDiscoveryService::ORG_DEK_SEGMENT_CURRENT_PERIOD,
        OrganizationDiscoveryService::ORG_DEK_SEGMENT_ARCHIVE,
      ],
      true
    );
  }

  /**
   * Check whether the membership relationship is currently active.
   */
  private function isMembershipActive(string $orgId, string $userUUID): bool
  {
    $relationship = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $userUUID);

    return (string) ($relationship['status'] ?? '') === OrganizationDiscoveryService::MEMBERSHIP_STATE_ACTIVE;
  }

  /**
   * Check whether the stored consent binding still matches the user and organization.
   */
  private function isConsentBindingValid(string $orgId, string $userUUID, string $consentId): bool
  {
    if ($consentId === '') {
      return false;
    }

    $consent = Database::hgetall(Keys::organizationConsent($consentId));
    if ($consent === []) {
      return false;
    }

    return (string) ($consent['org_id'] ?? '') === $orgId
      && (string) ($consent['user_uuid'] ?? '') === $userUUID
      && (string) ($consent['status'] ?? '') === OrganizationDiscoveryService::MEMBERSHIP_STATE_ACTIVE;
  }

  /**
   * Increment telemetry for denied organization unwrap attempts.
   */
  private function incrementOrgUnwrapDeniedCounter(string $reason): void
  {
    $reason = trim(InputSanitizer::sanitizeString($reason));
    if ($reason === '') {
      $reason = 'unknown';
    }

    try {
      $v = \PayCal\Domain\Config\SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;
      Database::incr("telemetry:encryption:{$v}:org:unwrap_denied_{$reason}");
    } catch (\Throwable $e) {
      Log::debug('Org unwrap denied telemetry increment failed: ' . $e->getMessage());
    }
  }

  /**
   * @param array<string, mixed> $data
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function ok(string $message, array $data = []): array
  {
    return [
      'success' => true,
      'message' => $message,
      'data' => $data,
    ];
  }

  /**
   * @param array<string, mixed> $data
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function fail(string $message, array $data = []): array
  {
    return [
      'success' => false,
      'message' => $message,
      'data' => $data,
    ];
  }
}
