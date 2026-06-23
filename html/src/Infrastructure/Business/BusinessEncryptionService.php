<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Business;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\BusinessDiscoveryService;
use PayCal\Domain\Log;
use PayCal\Domain\Config\SystemConfig;

/**
 * BusinessEncryptionService.php
 *
 * Purpose: Coordinate business-scoped DEK wrap storage and retrieval under
 * membership, consent, and credential-binding constraints.
 *
 * Developer notes:
 * - This service sits on security-sensitive business encryption flows.
 * - Preserve consent validation and membership checks as first-class behavior,
 *   not optional caller responsibilities.
 *
 * Architectural role:
 * - Reusable domain service for business-scoped encryption-wrap storage,
 *   lookup, and consent-bound resolution.
 * - Encapsulates business encryption policy outside the HTTP layer.
 *
 * @category   Infrastructure
 * @package    PayCal\Infrastructure\Business
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * BusinessEncryptionService
 *
 * Consent-bound helpers for storing and resolving business DEK wraps.
 * This service enforces membership and consent integrity before unwrap use.
 */
final class BusinessEncryptionService
{
  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function storeBusinessDekWrap(
    string $businessId,
    string $segment,
    string $version,
    string $userUUID,
    string $credentialId,
    string $wrappedDek,
    string $consentId,
    string $kdfProfile = 'hkdf-passkey-v1',
    string $dekId = ''
  ): array {
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    $segment = trim(InputSanitizer::sanitizeString($segment));
    $version = trim(InputSanitizer::sanitizeString($version));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    $credentialId = trim((string) $credentialId);
    $wrappedDek = trim((string) $wrappedDek);
    $consentId = trim(InputSanitizer::sanitizeString($consentId));
    $dekId = trim(InputSanitizer::sanitizeString($dekId));

    if ($businessId === '' || $segment === '' || $version === '' || $userUUID === '' || $credentialId === '' || $wrappedDek === '') {
      return $this->fail('All wrap fields are required.');
    }

    if ($dekId === '') {
      $dekId = 'business-dek:' . $businessId . ':' . $segment . ':' . $userUUID . ':v' . $version;
    }

    if (!$this->isValidSegment($segment)) {
      return $this->fail('Invalid business DEK segment.');
    }

    if (!$this->isMembershipActive($businessId, $userUUID)) {
      return $this->fail('Only active members can receive business DEK wraps.');
    }

    if (!$this->isConsentBindingValid($businessId, $userUUID, $consentId)) {
      return $this->fail('A valid active consent binding is required for wrap creation.');
    }

    $key = Keys::businessDekWrap($businessId, $segment, $version, $userUUID, $credentialId);
    $timestamp = date('c');

    Database::hset($key, [
      'business_id' => $businessId,
      'segment' => $segment,
      'key_version' => $version,
      'user_uuid' => $userUUID,
      'credential_id' => $credentialId,
      'dek_id' => $dekId,
      'wrapped_dek' => $wrappedDek,
      'kdf_profile' => $kdfProfile,
      'consent_id' => $consentId,
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
      'created_at' => $timestamp,
      'updated_at' => $timestamp,
    ]);

    return $this->ok('Business DEK wrap stored.', [
      'wrap_key' => $key,
      'business_id' => $businessId,
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
    string $businessId,
    string $segment,
    string $version,
    string $userUUID,
    string $credentialId,
    string $consentId = '',
    string $expectedDekId = ''
  ): array {
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    $segment = trim(InputSanitizer::sanitizeString($segment));
    $version = trim(InputSanitizer::sanitizeString($version));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    $credentialId = trim((string) $credentialId);
    $consentId = trim(InputSanitizer::sanitizeString($consentId));
    $expectedDekId = trim(InputSanitizer::sanitizeString($expectedDekId));

    if ($businessId === '' || $segment === '' || $version === '' || $userUUID === '' || $credentialId === '') {
      $this->incrementBusinessUnwrapDeniedCounter('missing_wrap');
      return $this->fail('Wrap lookup fields are required.');
    }

    if (!$this->isMembershipActive($businessId, $userUUID)) {
      $this->incrementBusinessUnwrapDeniedCounter('inactive_membership');
      return $this->fail('Membership is not active; unwrap denied.');
    }

    $key = Keys::businessDekWrap($businessId, $segment, $version, $userUUID, $credentialId);
    $wrap = Database::hgetall($key);
    if ($wrap === []) {
      $this->incrementBusinessUnwrapDeniedCounter('missing_wrap');
      return $this->fail('Business DEK wrap not found.');
    }

    if ((string) ($wrap['status'] ?? '') !== BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE) {
      $this->incrementBusinessUnwrapDeniedCounter('inactive_membership');
      return $this->fail('Business DEK wrap is inactive.');
    }

    $wrapConsentId = trim((string) ($wrap['consent_id'] ?? ''));
    if ($wrapConsentId === '') {
      $this->incrementBusinessUnwrapDeniedCounter('no_consent');
      return $this->fail('Business DEK wrap is missing consent binding.');
    }

    if ($consentId !== '' && $consentId !== $wrapConsentId) {
      $this->incrementBusinessUnwrapDeniedCounter('no_consent');
      return $this->fail('Provided consent_id does not match wrap binding.');
    }

    if (!$this->isConsentBindingValid($businessId, $userUUID, $wrapConsentId)) {
      $this->incrementBusinessUnwrapDeniedCounter('no_consent');
      return $this->fail('Consent binding is invalid or inactive; unwrap denied.');
    }

    if ($expectedDekId !== '') {
      $actualDekId = trim((string) ($wrap['dek_id'] ?? ''));
      if ($actualDekId === '' || $actualDekId !== $expectedDekId) {
        $this->incrementBusinessUnwrapDeniedCounter('dek_mismatch');
        return $this->fail('Business DEK wrap does not match the envelope DEK id.');
      }
    }

    return $this->ok('Business DEK wrap resolved for active unwrap.', [
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
  public function revokeWrapsForMembership(string $businessId, string $userUUID, string $reason = 'membership_revoked'): array
  {
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    $userUUID = trim(InputSanitizer::sanitizeString($userUUID));
    $reason = trim(InputSanitizer::sanitizeString($reason));

    if ($businessId === '' || $userUUID === '') {
      return $this->fail('Business id and user id are required for wrap revocation.');
    }

    $pattern = Keys::BUSINESS_DEK_WRAP . ':' . $businessId . ':*:*:' . $userUUID . ':*';
    $keys = Database::scanKeys($pattern);
    $revokedCount = 0;
    $timestamp = date('c');

    foreach ($keys as $key) {
      if (!Database::exists($key)) {
        continue;
      }

      Database::hset($key, [
        'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_REVOKED,
        'revoked_at' => $timestamp,
        'revocation_reason' => $reason,
        'updated_at' => $timestamp,
      ]);
      $revokedCount += 1;
    }

    return $this->ok('Business DEK wraps revoked for membership.', [
      'business_id' => $businessId,
      'user_uuid' => $userUUID,
      'revoked_wrap_count' => $revokedCount,
      'reason' => $reason,
    ]);
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function revokeWrapsForBusiness(string $businessId, string $reason = 'business_removed'): array
  {
    $businessId = trim(InputSanitizer::sanitizeString($businessId));
    $reason = trim(InputSanitizer::sanitizeString($reason));

    if ($businessId === '') {
      return $this->fail('Business id is required for business-wide wrap revocation.');
    }

    $pattern = Keys::BUSINESS_DEK_WRAP . ':' . $businessId . ':*';
    $keys = Database::scanKeys($pattern);
    $revokedCount = 0;
    $timestamp = date('c');

    foreach ($keys as $key) {
      if (!Database::exists($key)) {
        continue;
      }

      Database::hset($key, [
        'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_REVOKED,
        'revoked_at' => $timestamp,
        'revocation_reason' => $reason,
        'updated_at' => $timestamp,
      ]);
      $revokedCount += 1;
    }

    return $this->ok('Business DEK wraps revoked for business.', [
      'business_id' => $businessId,
      'revoked_wrap_count' => $revokedCount,
      'reason' => $reason,
    ]);
  }

  /**
   * Validate supported business DEK segments.
   */
  private function isValidSegment(string $segment): bool
  {
    return in_array(
      $segment,
      [
        BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
        BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_ARCHIVE,
      ],
      true
    );
  }

  /**
   * Check whether the business connection is currently active.
   */
  private function isMembershipActive(string $businessId, string $userUUID): bool
  {
    $connection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $businessId . ':' . $userUUID);

    return (string) ($connection['status'] ?? '') === BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE;
  }

  /**
   * Check whether the stored consent binding still matches the user and business.
   */
  private function isConsentBindingValid(string $businessId, string $userUUID, string $consentId): bool
  {
    if ($consentId === '') {
      return false;
    }

    $consent = Database::hgetall(Keys::businessConsent($consentId));
    if ($consent === []) {
      return false;
    }

    return (string) ($consent['business_id'] ?? '') === $businessId
      && (string) ($consent['user_uuid'] ?? '') === $userUUID
      && (string) ($consent['status'] ?? '') === BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE;
  }

  /**
   * Increment telemetry for denied business unwrap attempts.
   */
  private function incrementBusinessUnwrapDeniedCounter(string $reason): void
  {
    $reason = trim(InputSanitizer::sanitizeString($reason));
    if ($reason === '') {
      $reason = 'unknown';
    }

    try {
      $v = SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;
      Database::incr("telemetry:encryption:{$v}:business:unwrap_denied_{$reason}");
    } catch (\Throwable $e) {
      Log::debug('Business unwrap denied telemetry increment failed: ' . $e->getMessage());
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
