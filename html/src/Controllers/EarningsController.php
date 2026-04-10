<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Authentication;
use PayCal\Domain\Database;
use PayCal\Domain\Earnings;
use PayCal\Domain\EarningsCacheService;
use PayCal\Domain\EarningsDailyExtensionBridge;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Log;
use PayCal\Domain\Money;
use PayCal\Domain\OrganizationDiscoveryService;
use PayCal\Domain\OrganizationEncryptionService;
use PayCal\Domain\Enums\PayFrequency;
use PayCal\Domain\PayPeriods;
use PayCal\Domain\Response;
use PayCal\Domain\Sites;
use PayCal\Domain\Strings;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Security\CorrelationBroker;
use PayCal\Domain\Security\CorrelationContext;
use PayCal\Domain\Taxes;
use PayCal\Domain\User;
use PayCal\Domain\Work;
use PayCal\Domain\WorkEntry;
use PayCal\Observability\Lens;

/**
 * EarningsController.php
 *
 * Purpose: Request-layer orchestration for earnings views, year-to-date and
 * period summaries, export initialization, and guarded payroll correlation.
 *
 * Developer notes:
 * - This controller coordinates reporting flows but should not become the
 *   canonical source for earnings math. Keep calculations in domain services.
 * - Correlation of wages, sites, and work metadata is privileged and must stay
 *   behind the existing broker/context checks.
 * - Cache writes must preserve payload-shape compatibility with frontend
 *   consumers and export flows.
 * - Encryption unwrap/bootstrap logic here is security-sensitive; avoid adding
 *   alternate bypass paths in controller code.
 *
 * @category   Controllers
 * @package    PayCal\Controllers
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Earnings API surface.
 *
 * Responsibilities:
 * - Authenticate access to earnings/reporting endpoints.
 * - Build response payloads for daily, monthly, pay-period, and YTD views.
 * - Coordinate export initialization and audit-friendly reference generation.
 * - Attach dev diagnostics only through Lens and existing debug gates.
 */
class EarningsController
{
  private static bool $lensBooted = false;

  /**
   * Handles bootLens operation.
   */
  private static function bootLens(string $route): void
  {
    if (self::$lensBooted) {
      return;
    }
    Lens::boot($route);
    self::$lensBooted = true;
  }

  /** @param array<string,mixed> $payload */
  private static function debug(string $label, array $payload): void
  {
    Lens::add($label, $payload, 'data');
    Log::debug('[EarningsController] ' . $label . ' ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
  }

  /**
   * Handles debugRequested operation.
   */
  private static function debugRequested(): bool
  {
    return InputSanitizer::getString('debug') === '1';
  }

  /**
   * @param array<string,mixed> $row
   * @return array<string,mixed>|null
   */
  private static function decryptWorkRowIfNeeded(array $row, string $userUUID): ?array
  {
    $hasGross = isset($row['gross']) || isset($row['g']);
    $hasEncryptedBlob = isset($row['encrypted_blob']) && is_string($row['encrypted_blob']) && $row['encrypted_blob'] !== '';
    $allowSnapshotFallback = $hasGross || self::hasSnapshotFallbackData($row);

    if ($hasGross && !$hasEncryptedBlob) {
      return self::normalizeSnapshotRow($row);
    }

    $blob = self::scalarString($row['encrypted_blob'] ?? '');
    if ($blob === '') {
      return $allowSnapshotFallback ? self::normalizeSnapshotRow($row) : null;
    }

    $sessionHash = Authentication::getSessionHashFromCookie();
    if ($sessionHash === null) {
      return $allowSnapshotFallback ? self::normalizeSnapshotRow($row) : null;
    }

    $sessionKey = Keys::SESSION . ':' . $sessionHash;
    $credentialId = self::scalarString(Database::hget($sessionKey, 'credential_id'));

    $user = User::current();
    $saltB64 = self::scalarString($user->encryption_salt);
    if ($saltB64 === '') {
      return $allowSnapshotFallback ? self::normalizeSnapshotRow($row) : null;
    }

    $dek = self::resolveDekForEnvelope($blob, $userUUID, $credentialId, $saltB64);
    if ($dek === null) {
      return $allowSnapshotFallback ? self::normalizeSnapshotRow($row) : null;
    }

    $decryptedJson = self::decryptWorkBlob($blob, $dek);
    if ($decryptedJson === null) {
      return $allowSnapshotFallback ? self::normalizeSnapshotRow($row) : null;
    }

    $decoded = json_decode($decryptedJson, true);
    if (!is_array($decoded)) {
      return $allowSnapshotFallback ? self::normalizeSnapshotRow($row) : null;
    }
    /** @var array<string,mixed> $decoded */

    // Normalize decrypted payload keys and merge with original metadata so date/site are preserved.
    $normalized = WorkEntry::normalizeWorkEntryPayload($decoded);
    $merged = $row;
    foreach ($normalized as $k => $v) {
      $merged[(string) $k] = $v;
    }

    if (!isset($merged['date']) || self::scalarString($merged['date']) === '') {
      $merged['date'] = self::scalarString($row['date'] ?? '');
    }
    if (!isset($merged['site_id']) || self::scalarString($merged['site_id']) === '') {
      $merged['site_id'] = self::scalarString($row['site_id'] ?? '');
    }
    if (!isset($merged['site_name']) || self::scalarString($merged['site_name']) === '') {
      $merged['site_name'] = self::scalarString($row['site_name'] ?? '');
    }

    if ((!isset($merged['hours']) || !is_numeric($merged['hours']))
      && isset($merged['regular_hours'], $merged['overtime_hours'])
      && is_numeric($merged['regular_hours'])
      && is_numeric($merged['overtime_hours'])) {
      $merged['hours'] = (float) $merged['regular_hours'] + (float) $merged['overtime_hours'];
    }

    // Calculate gross if missing by using decrypted component fields.
    $hasGrossAfterMerge = isset($merged['gross']) || isset($merged['g']);
    if (!$hasGrossAfterMerge) {
      $regularHours = self::numericFloat($merged['regular_hours'] ?? $merged['r'] ?? 0);
      $overtimeHours = self::numericFloat($merged['overtime_hours'] ?? $merged['o'] ?? 0);
      $travelHours = self::numericFloat($merged['travel_hours'] ?? $merged['t'] ?? 0);
      $loa = self::numericFloat($merged['living_out_allowance'] ?? $merged['l'] ?? 0);

      $wage = null;
      if (isset($merged['wage']) && is_numeric($merged['wage'])) {
        $wage = (string) $merged['wage'];
      } elseif (isset($merged['w']) && is_numeric($merged['w'])) {
        $wage = (string) $merged['w'];
      } else {
        // Lookup wage from site
        $siteId = self::scalarString($merged['site_id']);
        if ($siteId !== '') {
          $siteWages = iterator_to_array(\PayCal\Domain\Sites::getSiteWages($userUUID));
          if (isset($siteWages[$siteId])) {
            $wage = $siteWages[$siteId];
          }
        }
      }

      $grossCents = Money::dollarsToCents((string) $loa);
      if ($wage !== null && is_numeric($wage)) {
        // Backfill effective wage so API consumers can compute travel/regular splits consistently.
        $merged['wage'] = $wage;
        $grossCents += Money::calculateGross($regularHours, $overtimeHours, $wage);
        if ($travelHours > 0) {
          $travelPay = $travelHours * (float) $wage;
          $grossCents += Money::dollarsToCents((string) $travelPay);
        }
      }

      if ($grossCents > 0) {
        $merged['gross'] = Money::centsToDollars($grossCents);
      }
    }

    return $merged;
  }

  /**
   * @param array<string,mixed> $row
   */
  private static function hasSnapshotFallbackData(array $row): bool
  {
    $hoursPresent = isset($row['hours']) && is_numeric($row['hours']);
    $splitPresent = isset($row['regular_hours'], $row['overtime_hours'])
      && is_numeric($row['regular_hours'])
      && is_numeric($row['overtime_hours']);
    $wagePresent = isset($row['wage']) && is_numeric($row['wage']);

    return $hoursPresent || $splitPresent || $wagePresent;
  }

  /**
   * @param array<string,mixed> $row
   * @return array<string,mixed>
   */
  private static function normalizeSnapshotRow(array $row): array
  {
    $normalized = WorkEntry::normalizeWorkEntryPayload($row);
    $merged = $row;
    foreach ($normalized as $k => $v) {
      $merged[(string) $k] = $v;
    }

    if (
      (!isset($merged['hours']) || !is_numeric($merged['hours']))
      && isset($merged['regular_hours'], $merged['overtime_hours'])
      && is_numeric($merged['regular_hours'])
      && is_numeric($merged['overtime_hours'])
    ) {
      $merged['hours'] = (float) $merged['regular_hours'] + (float) $merged['overtime_hours'];
    }

    return $merged;
  }

  /**
   * Resolve the correct DEK wrapper for either personal or organization envelopes.
   */
  private static function resolveDekForEnvelope(string $blob, string $ownerUUID, string $credentialId, string $saltB64): ?string
  {
    $orgMeta = self::parseOrganizationEnvelopeMetadata($blob);
    if (is_array($orgMeta)) {
      $actorUUID = User::currentUUID();
      if ($actorUUID === '' || $credentialId === '') {
        self::appendOrganizationWorkReadAudit($orgMeta, $actorUUID, $ownerUUID, 'denied', 'missing_actor_or_credential');
        return null;
      }

      $wrap = (new OrganizationEncryptionService())->resolveActiveWrapForUnwrap(
        $orgMeta['org_id'],
        $orgMeta['segment'],
        $orgMeta['key_version'],
        $actorUUID,
        $credentialId,
        '',
        $orgMeta['dek_id']
      );
      if (!$wrap['success']) {
        self::appendOrganizationWorkReadAudit($orgMeta, $actorUUID, $ownerUUID, 'denied', 'wrap_resolution_failed');
        return null;
      }

      $wrappedDek = self::scalarString($wrap['data']['wrapped_dek'] ?? '');
      if ($wrappedDek === '') {
        self::appendOrganizationWorkReadAudit($orgMeta, $actorUUID, $ownerUUID, 'denied', 'missing_wrapped_dek');
        return null;
      }

      self::appendOrganizationWorkReadAudit($orgMeta, $actorUUID, $ownerUUID, 'success', 'wrap_resolved');

      if (self::debugRequested()) {
        self::debug('resolveDekForEnvelope:source', [
          'mode' => 'organization',
          'source' => 'org_wrap_resolved',
          'owner_uuid_present' => true,
          'credential_present' => true,
        ]);
      }

      return self::unwrapDekFromPasskeyWrapper($wrappedDek, $credentialId, $actorUUID, $saltB64);
    }

    $wrappedPasskeyMapKey = Keys::USER . ':' . $ownerUUID . ':passkey_wrapped_deks';
    $wrappedDekPasskey = '';
    $credentialSource = 'none';
    if ($credentialId !== '') {
      $wrappedDekPasskey = self::scalarString(Database::hget($wrappedPasskeyMapKey, $credentialId));
      if ($wrappedDekPasskey !== '') {
        $credentialSource = 'selected_credential_map';
      }
    }

    // Fallback: if session credential wrapper missing, try any known credential.
    if ($wrappedDekPasskey === '') {
      $credentialIds = Database::smembers(Keys::webauthnUserCredentials($ownerUUID));
      foreach ($credentialIds as $candidate) {
        $candidateId = (string) $candidate;
        if ($candidateId === '') {
          continue;
        }
        $candidateWrapped = self::scalarString(Database::hget($wrappedPasskeyMapKey, $candidateId));
        if ($candidateWrapped !== '') {
          $credentialId = $candidateId;
          $wrappedDekPasskey = $candidateWrapped;
          $credentialSource = 'fallback_credential_map';
          break;
        }
      }

      // Parity with account bootstrap: if no credential-scoped wrapper was found,
      // still select a known credential ID so legacy single-wrapper fallback can unwrap.
      if ($credentialId === '') {
        foreach ($credentialIds as $candidate) {
          $candidateId = (string) $candidate;
          if ($candidateId !== '') {
            $credentialId = $candidateId;
            $credentialSource = 'credential_selected_no_map';
            break;
          }
        }
      }
    }

    if ($credentialId === '') {
      return null;
    }
    if ($wrappedDekPasskey === '') {
      $wrappedDekPasskey = self::scalarString(User::current()->wrapped_dek_passkey);
      if ($wrappedDekPasskey !== '') {
        $credentialSource = 'legacy_single_wrapper';
      }
    }
    if ($wrappedDekPasskey === '') {
      return null;
    }

    if (self::debugRequested()) {
      self::debug('resolveDekForEnvelope:source', [
        'mode' => 'personal',
        'source' => $credentialSource,
        'owner_uuid_present' => true,
        'credential_present' => true,
      ]);
    }

    return self::unwrapDekFromPasskeyWrapper($wrappedDekPasskey, $credentialId, $ownerUUID, $saltB64);
  }

  /** @param array{org_id: string, segment: string, key_version: string, dek_id: string} $orgMeta */
  private static function appendOrganizationWorkReadAudit(array $orgMeta, string $actorUUID, string $targetUUID, string $outcome, string $reason): void
  {
    if ($orgMeta['org_id'] === '') {
      return;
    }

    try {
      (new OrganizationDiscoveryService())->appendOrganizationAuditEvent(
        (string) $orgMeta['org_id'],
        'org.work.read',
        $actorUUID !== '' ? $actorUUID : User::currentUUID(),
        [
          'target_user_uuid' => $targetUUID,
          'segment' => $orgMeta['segment'],
          'key_version' => $orgMeta['key_version'],
          'dek_id' => $orgMeta['dek_id'],
          'outcome' => $outcome,
          'reason' => $reason,
        ]
      );
    } catch (\Throwable $e) {
      Log::debug('[EarningsController] org.work.read audit emit failed: ' . $e->getMessage());
    }
  }

  /** @return array{org_id: string, segment: string, key_version: string, dek_id: string}|null */
  private static function parseOrganizationEnvelopeMetadata(string $blob): ?array
  {
    $decodedEnvelope = base64_decode($blob, true);
    if ($decodedEnvelope === false) {
      return null;
    }

    $envelope = json_decode($decodedEnvelope, true);
    if (!is_array($envelope)) {
      return null;
    }

    $metaRaw = $envelope['meta'] ?? null;
    $meta = is_array($metaRaw) ? $metaRaw : [];
    $modeRaw = $meta['encryption_mode'] ?? ($envelope['encryption_mode'] ?? '');
    $mode = is_scalar($modeRaw) ? trim((string) $modeRaw) : '';
    if ($mode !== 'organization') {
      return null;
    }

    $orgIdRaw = $meta['org_id'] ?? ($envelope['org_id'] ?? '');
    $segmentRaw = $meta['segment'] ?? ($envelope['segment'] ?? '');
    $keyVersionRaw = $meta['key_version'] ?? ($envelope['key_version'] ?? '');
    $dekIdRaw = $meta['dek_id'] ?? ($envelope['dek_id'] ?? '');

    $orgId = is_scalar($orgIdRaw) ? trim((string) $orgIdRaw) : '';
    $segment = is_scalar($segmentRaw) ? trim((string) $segmentRaw) : '';
    $keyVersion = is_scalar($keyVersionRaw) ? trim((string) $keyVersionRaw) : '';
    $dekId = is_scalar($dekIdRaw) ? trim((string) $dekIdRaw) : '';

    if ($orgId === '' || $segment === '' || $keyVersion === '' || $dekId === '') {
      return null;
    }

    return [
      'org_id' => $orgId,
      'segment' => $segment,
      'key_version' => $keyVersion,
      'dek_id' => $dekId,
    ];
  }

  /**
   * Handles hkdfPasskeyKek operation.
   */
  private static function hkdfPasskeyKek(string $credentialId, string $userUUID, string $saltB64, bool $legacyUserBound): ?string
  {
    $salt = base64_decode($saltB64, true);
    if ($salt === false) {
      return null;
    }

    $ikm = $legacyUserBound ? ($credentialId . '|' . $userUUID) : $credentialId;
    // Matches client derivePasskeyKEK() info label.
    return hash_hkdf('sha256', $ikm, 32, 'paycal-passkey-kek', $salt);
  }

  /**
   * Handles unwrapDekFromPasskeyWrapper operation.
   */
  private static function unwrapDekFromPasskeyWrapper(string $wrappedDekPasskey, string $credentialId, string $userUUID, string $saltB64): ?string
  {
    $decodedEnvelope = base64_decode($wrappedDekPasskey, true);
    if ($decodedEnvelope === false) {
      return null;
    }

    $envelope = json_decode($decodedEnvelope, true);
    if (!is_array($envelope)) {
      return null;
    }

    $nonceB64 = self::scalarString($envelope['nonce'] ?? $envelope['iv'] ?? '');
    $ctB64 = self::scalarString($envelope['ciphertext'] ?? $envelope['ct'] ?? '');
    if ($nonceB64 === '' || $ctB64 === '') {
      return null;
    }

    $nonce = base64_decode($nonceB64, true);
    $ciphertextWithTag = base64_decode($ctB64, true);
    if ($nonce === false || $ciphertextWithTag === false || strlen($ciphertextWithTag) < 17) {
      return null;
    }

    $ciphertext = substr($ciphertextWithTag, 0, -16);
    $tag = substr($ciphertextWithTag, -16);

    // Canonical derivation: credential-only
    $kekCanonical = self::hkdfPasskeyKek($credentialId, $userUUID, $saltB64, false);
    if (is_string($kekCanonical) && $kekCanonical !== '') {
      $dek = openssl_decrypt($ciphertext, 'aes-256-gcm', $kekCanonical, OPENSSL_RAW_DATA, $nonce, $tag);
      if (is_string($dek) && $dek !== '') {
        return $dek;
      }
    }

    // Legacy derivation fallback: credential-user
    $kekLegacy = self::hkdfPasskeyKek($credentialId, $userUUID, $saltB64, true);
    if (is_string($kekLegacy) && $kekLegacy !== '') {
      $dek = openssl_decrypt($ciphertext, 'aes-256-gcm', $kekLegacy, OPENSSL_RAW_DATA, $nonce, $tag);
      if (is_string($dek) && $dek !== '') {
        return $dek;
      }
    }

    return null;
  }

  /**
   * Handles decryptWorkBlob operation.
   */
  private static function decryptWorkBlob(string $blobBase64Envelope, string $dekRaw): ?string
  {
    $decodedEnvelope = base64_decode($blobBase64Envelope, true);
    if ($decodedEnvelope === false) {
      return null;
    }

    $envelope = json_decode($decodedEnvelope, true);
    if (!is_array($envelope)) {
      return null;
    }

    $nonceB64 = self::scalarString($envelope['nonce'] ?? $envelope['iv'] ?? '');
    $ctB64 = self::scalarString($envelope['ciphertext'] ?? $envelope['ct'] ?? '');
    $aad = self::scalarString($envelope['aad'] ?? '');
    if ($nonceB64 === '' || $ctB64 === '') {
      return null;
    }

    $nonce = base64_decode($nonceB64, true);
    $ciphertextWithTag = base64_decode($ctB64, true);
    if ($nonce === false || $ciphertextWithTag === false || strlen($ciphertextWithTag) < 17) {
      return null;
    }

    $ciphertext = substr($ciphertextWithTag, 0, -16);
    $tag = substr($ciphertextWithTag, -16);

    $plaintext = openssl_decrypt(
      $ciphertext,
      'aes-256-gcm',
      $dekRaw,
      OPENSSL_RAW_DATA,
      $nonce,
      $tag,
      $aad
    );

    return is_string($plaintext) ? $plaintext : null;
  }

  /**
   * Handles scalarString operation.
   */
  private static function scalarString(mixed $value, string $default = ''): string
  {
    return is_scalar($value) ? (string) $value : $default;
  }

  /**
   * Handles numericFloat operation.
   */
  private static function numericFloat(mixed $value, float $default = 0.0): float
  {
    return is_numeric($value) ? (float) $value : $default;
  }

  /**
   * Handles correlationContext operation.
   */
  private static function correlationContext(): string
  {
    $raw = InputSanitizer::sanitizeString(InputSanitizer::getString('correlation_context'));
    return $raw === '' ? 'self-service-earnings' : strtolower($raw);
  }

  /** @return array<string, mixed> */
  private static function siteFinancialCorrelationComposeProbe(): array
  {
    $context = new CorrelationContext(
      self::correlationContext(),
      User::currentUUID(),
      User::isAdmin() ? 'security-admin' : 'user',
      'earnings-self-service',
      ['site_metadata:financial_payload'],
      'earnings_controller'
    );

    return CorrelationBroker::compose(
      ['scope' => 'site_metadata'],
      ['scope' => 'financial_payload'],
      'site_metadata',
      'financial_payload',
      $context
    );
  }

  /**
   * Constructor. Aborts with 401 if the request is not authenticated.
   */
  public function __construct()
  {
    Authentication::abortIfUnauthenticated();
  }

  /**
   * Handles the /api/v1/verification/year endpoint.
   * Returns canonical verification payload and hash for each pay period.
   *
   * @param string $year Year parameter from route
   */
  #[Route('verification/year/{year}', ['GET'])]
  /**
   * Handles getVerificationYear operation.
   */
  public static function getVerificationYear(string $year): void
  {
    self::bootLens('api/verification/year');

    $correlationProbe = self::siteFinancialCorrelationComposeProbe();
    if (($correlationProbe['status'] ?? '') !== 'success') {
      Response::error('[EC] Correlation context denied.', [
        'context' => self::correlationContext(),
        'reason' => 'metadata_correlation_denied',
        'decision' => $correlationProbe['decision'] ?? null,
      ], HttpStatus::HTTP_FORBIDDEN);

      return;
    }

    $year = (int) $year;
    if ($year < SystemConfig::get('year_min') || $year > SystemConfig::get('year_max')) {
      \PayCal\Domain\Response::error("[EC] Year {$year} is out of allowed range.", []);

      return;
    }

    $dStart = new \DateTimeImmutable("{$year}-01-01");
    $dEnd = new \DateTimeImmutable("{$year}-12-31");
    $userUUID = User::currentUUID();

    try {
      $keyUUID = Earnings::ensureUserSigningKeys($userUUID, 1);
    } catch (\Throwable $exception) {
      self::debug('verificationYear:keyBootstrapFailed', [
        'year' => $year,
        'user_uuid' => $userUUID,
        'error' => $exception->getMessage(),
      ]);

      Response::json(
        'success',
        '[EC] Canonical verification data unavailable for this year.',
        HttpStatus::HTTP_OK,
        ['periods' => []]
      );

      return;
    }

    $workEntries = Work::getInstance()->GetWorkInRange($dStart, $dEnd->modify('+1 day'));
    $debug = ['rows' => 0, 'encrypted_only' => 0, 'plaintext_rows' => 0, 'gross_zero_rows' => 0, 'skipped_rows' => 0, 'decrypt_failed_rows' => 0];

    $result = [];
    $prevChainHash = str_repeat('0', 64); // Genesis
    foreach ($workEntries as $sKey => $earnings) {
      try {
        $resolved = self::decryptWorkRowIfNeeded($earnings, $userUUID);
        if (!is_array($resolved)) {
          $debug['decrypt_failed_rows']++;
          self::debug('verificationYear:rowSkipped', [
            'reason' => 'decrypt_failed',
            'work_key' => self::scalarString($sKey),
          ]);
          continue;
        }
        $earnings = $resolved;
        $debug['rows']++;
        $hasBlob = isset($earnings['encrypted_blob']) && is_string($earnings['encrypted_blob']) && $earnings['encrypted_blob'] !== '';
        $hasGross = isset($earnings['gross']) || isset($earnings['g']);
        if ($hasBlob && !$hasGross) {
          $debug['encrypted_only']++;
        }
        if ($hasGross) {
          $debug['plaintext_rows']++;
        }
        $sDate = self::scalarString($earnings['date'] ?? '');
        if ('' === $sDate) {
          continue;
        }
        $g = self::numericFloat($earnings['gross'] ?? $earnings['g'] ?? 0);
        if (0.0 === $g) {
          $debug['gross_zero_rows']++;
        }
        self::debug('verificationYear:row', [
          'date' => $sDate,
          'site_id' => self::scalarString($earnings['site_id'] ?? ''),
          'has_encrypted_blob' => $hasBlob,
          'has_plaintext_gross' => $hasGross,
          'gross_used' => $g,
        ]);
        $grossCents = Money::dollarsToCents((string) $g);
        $tax = new Taxes('Alberta', $year);
        $t = $tax->calculateTaxesCents($grossCents);
        $taxCents = (int) $t['totalDeductions'];
        $netCents = $grossCents - $taxCents;

        $period = PayPeriods::fromDate($sDate, PayFrequency::BIWEEKLY, 'Monday', null, 'America/Edmonton');
        $employeeId = $userUUID;
        $jurisdiction = 'CA-AB';
        $bracketVersion = '2026.1';
        $engineVersion = '1.015.000';

        $keyVersion = 1;
        $payload = Earnings::buildCanonicalVerificationPayload(
          $period,
          $employeeId,
          $jurisdiction,
          $bracketVersion,
          $engineVersion,
          $grossCents,
          $taxCents,
          $netCents,
          $keyVersion
        );
        $serialized = Earnings::serializeVerificationPayload($payload);
        $verificationSignature = Earnings::signCanonicalVerificationPayload($serialized, $keyVersion, $keyUUID);
        $payloadHash = hash('sha256', $serialized);
        $chainHash = hash('sha256', $prevChainHash.$payloadHash);
        $result[] = [
            'payload' => $payload,
            'canonicalPayload' => $serialized,
            'verificationSignature' => $verificationSignature,
            'signingKeyVersion' => $keyVersion,
            'payloadHash' => $payloadHash,
            'chainHash' => $chainHash,
        ];
        $prevChainHash = $chainHash;
      } catch (\Throwable $exception) {
        $debug['skipped_rows']++;
        self::debug('verificationYear:rowSkipped', [
          'error' => $exception->getMessage(),
          'work_key' => self::scalarString($sKey),
        ]);
      }
    }

    self::debug('verificationYear:summary', $debug);

    $extra = ['periods' => $result];
    if (self::debugRequested()) {
      $extra['_debug'] = $debug;
    }
    Response::json('success', '[EC] Canonical verification data retrieved successfully.', HttpStatus::HTTP_OK, $extra);
  }

  /**
   * Handles the /api/earnings/year endpoint.
   * Retrieves yearly earnings data for the authenticated user.
   *
   * @param string $year Year parameter from route
   */
  #[Route('ytd/year/{year}', ['GET'])]
  /**
   * Handles getYearToDateSection operation.
   */
  public static function getYearToDateSection(string $year): void
  {
    self::bootLens('api/ytd/year');

    $year = (int) $year;
    if ($year < SystemConfig::get('year_min') || $year > SystemConfig::get('year_max')) {
      Response::error("[EC] Year {$year} is out of allowed range.", []);

      return;
    }

    $compareRequested = User::isAdmin() && InputSanitizer::getString('ext_compare') === 'earnings-ytd';
    $requestedModeRaw = InputSanitizer::getString('ext_mode') ?? 'auto';
    $requestedMode = in_array(strtolower($requestedModeRaw), ['auto', 'basic', 'override'], true)
      ? strtolower($requestedModeRaw)
      : 'auto';

    $cacheAllowed = !$compareRequested && $requestedMode === 'auto';

    $userUUID = User::currentUUID();
    $sessionHash = Authentication::getSessionHashFromCookie();
    if ($cacheAllowed && is_string($sessionHash) && $sessionHash !== '') {
      $cached = EarningsCacheService::getYearPayload($userUUID, 'ytd', $year, $sessionHash);
      if (is_array($cached) && isset($cached['html']) && is_string($cached['html'])) {
        Lens::add('ytdYear:cache', ['year' => $year, 'hit' => true], 'cache');
        Response::success('[EC] YTD section retrieved successfully.', ['html' => $cached['html']]);

        return;
      }
      Lens::add('ytdYear:cache', ['year' => $year, 'hit' => false], 'cache');
    }

    $html = $compareRequested
      ? Earnings::getInstance()->renderYearToDateSummaryCompare($year)
      : Earnings::getInstance()->renderYearToDateSummary($year, $requestedMode);

    if ($cacheAllowed && is_string($sessionHash) && $sessionHash !== '') {
      EarningsCacheService::putYearPayload($userUUID, 'ytd', $year, $sessionHash, ['html' => $html]);
    }

    Response::success('[EC] YTD section retrieved successfully.', ['html' => $html]);
  }

  /**
   * GET payperiods/year/{year}
   *
   * Returns an HTML fragment comparing pay-period earnings for the given year.
   * Response is cached per user session for performance.
   *
   * @param string $year Four-digit year from the route.
   */
  #[Route('payperiods/year/{year}', ['GET'])]
  /**
   * Handles getPayPeriodsSection operation.
   */
  public static function getPayPeriodsSection(string $year): void
  {
    self::bootLens('api/payperiods/year');

    $year = (int) $year;
    if ($year < SystemConfig::get('year_min') || $year > SystemConfig::get('year_max')) {
      Response::error("[EC] Year {$year} is out of allowed range.", []);

      return;
    }

    $userUUID = User::currentUUID();
    $sessionHash = Authentication::getSessionHashFromCookie();
    if (is_string($sessionHash) && $sessionHash !== '') {
      $cached = EarningsCacheService::getYearPayload($userUUID, 'payperiods', $year, $sessionHash);
      if (is_array($cached) && isset($cached['html']) && is_string($cached['html'])) {
        Lens::add('payPeriodsYear:cache', ['year' => $year, 'hit' => true], 'cache');
        Response::success('[EC] Pay periods section retrieved successfully.', ['html' => $cached['html']]);

        return;
      }
      Lens::add('payPeriodsYear:cache', ['year' => $year, 'hit' => false], 'cache');
    }

    $html = Earnings::getInstance()->renderPayPeriodComparison($year);
    if (is_string($sessionHash) && $sessionHash !== '') {
      EarningsCacheService::putYearPayload($userUUID, 'payperiods', $year, $sessionHash, ['html' => $html]);
    }

    Response::success('[EC] Pay periods section retrieved successfully.', ['html' => $html]);
  }

  /**
   * GET monthly/year/{year}
   *
   * Returns an HTML fragment summarising monthly earnings for the given year.
   * Response is cached per user session for performance.
   *
   * @param string $year Four-digit year from the route.
   */
  #[Route('monthly/year/{year}', ['GET'])]
  /**
   * Handles getMonthlySection operation.
   */
  public static function getMonthlySection(string $year): void
  {
    self::bootLens('api/monthly/year');

    $year = (int) $year;
    if ($year < SystemConfig::get('year_min') || $year > SystemConfig::get('year_max')) {
      Response::error("[EC] Year {$year} is out of allowed range.", []);

      return;
    }

    $userUUID = User::currentUUID();
    $sessionHash = Authentication::getSessionHashFromCookie();
    if (is_string($sessionHash) && $sessionHash !== '') {
      $cached = EarningsCacheService::getYearPayload($userUUID, 'monthly', $year, $sessionHash);
      if (is_array($cached) && isset($cached['html']) && is_string($cached['html'])) {
        Lens::add('monthlyYear:cache', ['year' => $year, 'hit' => true], 'cache');
        Response::success('[EC] Monthly section retrieved successfully.', ['html' => $cached['html']]);

        return;
      }
      Lens::add('monthlyYear:cache', ['year' => $year, 'hit' => false], 'cache');
    }

    $html = Earnings::getInstance()->renderMonthlyViewStrip($year);
    if (is_string($sessionHash) && $sessionHash !== '') {
      EarningsCacheService::putYearPayload($userUUID, 'monthly', $year, $sessionHash, ['html' => $html]);
    }

    Response::success('[EC] Monthly section retrieved successfully.', ['html' => $html]);
  }

  /**
   * Handles the /api/earnings/year endpoint.
   * Retrieves yearly earnings data for the authenticated user.
   *
   * @param string $year Year parameter from route
   */
  #[Route('gross/year/{year}', ['GET'])]
  /**
   * Handles getGross operation.
   */
  public static function getGross(string $year): void
  {
    self::bootLens('api/gross/year');

    $correlationProbe = self::siteFinancialCorrelationComposeProbe();
    if (($correlationProbe['status'] ?? '') !== 'success') {
      Response::error('[EC] Correlation context denied.', [
        'context' => self::correlationContext(),
        'reason' => 'metadata_correlation_denied',
        'decision' => $correlationProbe['decision'] ?? null,
      ], HttpStatus::HTTP_FORBIDDEN);

      return;
    }

    $year = (int) $year;

    if ($year < SystemConfig::get('year_min') || $year > SystemConfig::get('year_max')) {
      \PayCal\Domain\Response::error("[EC] Year {$year} is out of allowed range.", []);

      return;
    }

    $userUUID = User::currentUUID();
    $sessionHash = Authentication::getSessionHashFromCookie();
    if (!self::debugRequested() && is_string($sessionHash) && $sessionHash !== '') {
      $cachedPayload = EarningsCacheService::getYearPayload($userUUID, 'gross', $year, $sessionHash);
      if (is_array($cachedPayload)) {
        Lens::add('grossYear:cache', ['year' => $year, 'hit' => true], 'cache');
        Response::success('[EC] Earnings data retrieved successfully.', $cachedPayload);

        return;
      }
      Lens::add('grossYear:cache', ['year' => $year, 'hit' => false], 'cache');
    }

    $dStart = new \DateTimeImmutable("{$year}-01-01");
    $dEnd = new \DateTimeImmutable("{$year}-12-31");
    $aData = Work::getInstance()->GetWorkInRange($dStart, $dEnd->modify('+1 day'));
    $debug = ['rows' => 0, 'encrypted_only' => 0, 'gross_zero_rows' => 0, 'decrypt_failed_rows' => 0];

    $formattedData = [];
    foreach ($aData as $sKey => $aEarnings) {
      $resolved = self::decryptWorkRowIfNeeded($aEarnings, User::currentUUID());
      if (!is_array($resolved)) {
        $debug['decrypt_failed_rows']++;
        self::debug('grossYear:rowSkipped', [
          'reason' => 'decrypt_failed',
          'work_key' => self::scalarString($sKey),
        ]);
        continue;
      }
      $aEarnings = $resolved;
      $debug['rows']++;
      $sDate = self::scalarString($aEarnings['date'] ?? '');
      if ('' === $sDate) {
        continue;
      }
      $hasBlob = isset($aEarnings['encrypted_blob']) && is_string($aEarnings['encrypted_blob']) && $aEarnings['encrypted_blob'] !== '';
      $hasGross = isset($aEarnings['gross']) || isset($aEarnings['g']);
      if ($hasBlob && !$hasGross) {
        $debug['encrypted_only']++;
      }
      $gross = self::numericFloat($aEarnings['gross'] ?? $aEarnings['g'] ?? 0);
      if (0.0 === $gross) {
        $debug['gross_zero_rows']++;
      }
      self::debug('grossYear:row', [
        'date' => $sDate,
        'has_encrypted_blob' => $hasBlob,
        'has_plaintext_gross' => $hasGross,
        'gross_used' => $gross,
      ]);
      if (!isset($formattedData[$sDate])) {
        $formattedData[$sDate] = 0.0;
      }
      $formattedData[$sDate] += $gross;
    }

    self::debug('grossYear:summary', $debug);

    $extra = $formattedData;
    if (self::debugRequested()) {
      $extra['_debug'] = $debug;
    }
    if (!self::debugRequested() && is_string($sessionHash) && $sessionHash !== '') {
      EarningsCacheService::putYearPayload($userUUID, 'gross', $year, $sessionHash, $formattedData);
    }

    Response::success('[EC] Earnings data retrieved successfully.', $extra);
  }

  /**
   * Handles the /v1/api/earnings/year endpoint.
   * Retrieves yearly earnings data for the authenticated user.
   *
   * @param string $year Year parameter from route
   */
  #[Route('daily/year/{year}', ['GET'])]
  /**
   * Handles getDaily operation.
   */
  public static function getDaily(string $year): void
  {
    self::bootLens('api/daily/year');

    $correlationProbe = self::siteFinancialCorrelationComposeProbe();
    if (($correlationProbe['status'] ?? '') !== 'success') {
      Response::error('[EC] Correlation context denied.', [
        'context' => self::correlationContext(),
        'reason' => 'metadata_correlation_denied',
        'decision' => $correlationProbe['decision'] ?? null,
      ], HttpStatus::HTTP_FORBIDDEN);

      return;
    }

    $year = (int) $year;

    if ($year < SystemConfig::get('year_min') || $year > SystemConfig::get('year_max')) {
      \PayCal\Domain\Response::error("[EC] Year {$year} is out of allowed range.", []);

      return;
    }

    $userUUID = User::currentUUID();
    $sessionHash = Authentication::getSessionHashFromCookie();
    if (!self::debugRequested() && is_string($sessionHash) && $sessionHash !== '') {
      $cachedPayload = EarningsCacheService::getYearPayload($userUUID, 'daily', $year, $sessionHash);
      if (is_array($cachedPayload)) {
        Lens::add('dailyYear:cache', ['year' => $year, 'hit' => true], 'cache');
        Response::success('[EC] Earnings data retrieved successfully.', $cachedPayload);

        return;
      }
      Lens::add('dailyYear:cache', ['year' => $year, 'hit' => false], 'cache');
    }

    $dStart = new \DateTimeImmutable("{$year}-01-01");
    $dEnd = new \DateTimeImmutable("{$year}-12-31");
    $aData = Work::getInstance()->GetWorkInRange($dStart, $dEnd->modify('+1 day'));
    $debug = ['rows' => 0, 'encrypted_only' => 0, 'gross_zero_rows' => 0, 'hours_zero_rows' => 0, 'decrypt_failed_rows' => 0];
    $tax = new Taxes('Alberta', $year);
    $employeeId = $userUUID;

    $formattedData = [];
    foreach ($aData as $sKey => $aEarnings) {
      $resolved = self::decryptWorkRowIfNeeded($aEarnings, $userUUID);
      if (!is_array($resolved)) {
        $debug['decrypt_failed_rows']++;
        self::debug('dailyYear:rowSkipped', [
          'reason' => 'decrypt_failed',
          'work_key' => self::scalarString($sKey),
        ]);
        continue;
      }
      $aEarnings = $resolved;
      $debug['rows']++;
      $sDate = self::scalarString($aEarnings['date'] ?? '');
      if ('' === $sDate) {
        continue;
      }
      $hasBlob = isset($aEarnings['encrypted_blob']) && is_string($aEarnings['encrypted_blob']) && $aEarnings['encrypted_blob'] !== '';
      $hasGross = isset($aEarnings['gross']) || isset($aEarnings['g']);
      if ($hasBlob && !$hasGross) {
        $debug['encrypted_only']++;
      }
      $g = self::numericFloat($aEarnings['gross'] ?? $aEarnings['g'] ?? 0);
      if (0.0 === $g) {
        $debug['gross_zero_rows']++;
      }
      $hours = self::numericFloat($aEarnings['hours'] ?? $aEarnings['h'] ?? 0);
      $travelHours = self::numericFloat($aEarnings['travel_hours'] ?? $aEarnings['t'] ?? 0);
      $livingOutAllowance = self::numericFloat($aEarnings['living_out_allowance'] ?? $aEarnings['l'] ?? 0);
      if (0.0 === $hours) {
        $debug['hours_zero_rows']++;
      }
      self::debug('dailyYear:row', [
        'date' => $sDate,
        'site_id' => self::scalarString($aEarnings['site_id'] ?? ''),
        'has_encrypted_blob' => $hasBlob,
        'has_plaintext_gross' => $hasGross,
        'hours_used' => $hours,
        'travel_used' => $travelHours,
        'loa_used' => $livingOutAllowance,
        'gross_used' => $g,
      ]);
      $grossCents = Money::dollarsToCents((string) $g);
      $t = $tax->calculateTaxesCents($grossCents);
      $federalCents = (int) $t['federal'];
      $provincialCents = (int) $t['provincial'];
      $eiCents = (int) $t['employment_insurance'];
      $cppCents = (int) $t['canada_pension_plan'];
      $oasCents = (int) $t['old_age_security'];
      $taxCents = (int) $t['totalDeductions'];
      $netCents = $grossCents - $taxCents;

      // Determine pay period (biweekly, Monday anchor, default timezone)
      $period = PayPeriods::fromDate($sDate, PayFrequency::BIWEEKLY, 'Monday', null, 'America/Edmonton');
      $jurisdiction = 'CA-AB'; // TODO: derive from user or context
      $bracketVersion = '2026.1'; // TODO: derive from config
      $engineVersion = '1.015.000'; // TODO: derive from config
      $keyVersion = defined('SIGNING_KEY_VERSION') ? SIGNING_KEY_VERSION : 1;

      $payload = Earnings::buildCanonicalVerificationPayload(
        $period,
        $employeeId,
        $jurisdiction,
        $bracketVersion,
        $engineVersion,
        $grossCents,
        $taxCents,
        $netCents,
        $keyVersion
      );
      $serialized = Earnings::serializeVerificationPayload($payload);
      $verificationHash = Earnings::hashPayload($serialized);

      if (!isset($formattedData[$sDate])) {
        $formattedData[$sDate] = [
          'date' => $sDate,
          'grossCents' => 0,
          'taxCents' => 0,
          'netCents' => 0,
        ];
      }

      $formattedData[$sDate]['grossCents'] += $grossCents;
      $formattedData[$sDate]['taxCents'] += $taxCents;
      $formattedData[$sDate]['netCents'] += $netCents;

      // Keep debug fields referenced above in scope to avoid altering diagnostics.
      unset($federalCents, $provincialCents, $eiCents, $cppCents, $oasCents, $verificationHash);
    }

    foreach ($formattedData as $dateKey => $row) {
      $formattedData[$dateKey] = [
        'date' => (string) $row['date'],
        'gross' => Money::centsToDollars((int) $row['grossCents']),
        'deductions' => Money::centsToDollars((int) $row['taxCents']),
        'net' => Money::centsToDollars((int) $row['netCents']),
      ];
    }

    $extensionPayload = EarningsDailyExtensionBridge::render($year, $formattedData);
    if (is_array($extensionPayload)) {
      $formattedData = $extensionPayload;
    }

    self::debug('dailyYear:summary', $debug);

    $extra = $formattedData;
    if (self::debugRequested()) {
      $extra['_debug'] = $debug;
    }
    if (!self::debugRequested() && is_string($sessionHash) && $sessionHash !== '') {
      EarningsCacheService::putYearPayload($userUUID, 'daily', $year, $sessionHash, $formattedData);
    }

    Response::success('[EC] Earnings data retrieved successfully.', $extra);
  }

  /**
   * POST earnings/export/init
   *
   * Initialize an export and generate a reference code for audit logging.
   * Logs the export event immediately when called.
   */
  #[Route('export/init', ['POST'])]
  /**
   * Handles initializeExport operation.
   */
  public function initializeExport(): void
  {
    Authentication::abortIfUnauthenticated();
    
    $body = file_get_contents('php://input');
    if ($body === false || $body === '') {
      Response::error('[EC] Empty request body.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    try {
      $postData = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
      Response::error('[EC] Invalid JSON: ' . $e->getMessage(), [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    if (!is_array($postData)) {
      Response::error('[EC] JSON payload must be an object.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    // Extract and validate required fields
    $format = isset($postData['format']) && is_string($postData['format']) ? trim($postData['format']) : '';
    $scope = isset($postData['scope']) && is_string($postData['scope']) ? trim($postData['scope']) : 'yearly';
    $year = isset($postData['year']) && is_numeric($postData['year']) ? (int) $postData['year'] : 0;

    // Validate inputs
    if ($format === '' || $year < 1900 || $year > 2100) {
      Response::error('[EC] Invalid export parameters: format and valid year are required.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    // Generate reference code (16-char alphanumeric)
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $referenceCode = '';
    for ($i = 0; $i < 16; $i += 1) {
      $referenceCode .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    // Log the export event immediately
    \PayCal\Domain\SecurityLog::log('earnings_export', [
      'scope' => $scope,
      'format' => $format,
      'year' => $year,
      'reference_code' => $referenceCode,
    ]);

    Response::success('[EC] Export initialized.', [
      'reference_code' => $referenceCode,
    ]);
  }
}


