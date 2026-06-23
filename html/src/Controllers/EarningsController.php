<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Authentication;
use PayCal\Domain\CryptoCompatibilityTelemetry;
use PayCal\Domain\Database;
use PayCal\Domain\Earnings;
use PayCal\Domain\EarningsDailyExtensionBridge;
use PayCal\Infrastructure\Cache\EarningsCacheService;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Log;
use PayCal\Domain\Money;
use PayCal\Domain\BusinessDiscoveryService;
use PayCal\Infrastructure\Business\BusinessEncryptionService;
use PayCal\Domain\Enums\PayFrequency;
use PayCal\Domain\PayPeriods;
use PayCal\Domain\Response;
use PayCal\Domain\Sites;
use PayCal\Domain\Strings;
use PayCal\Domain\SubscriptionRepository;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Security\CorrelationBroker;
use PayCal\Domain\Security\CorrelationContext;
use PayCal\Domain\Taxes;
use PayCal\Domain\User;
use PayCal\Domain\Work;
use PayCal\Domain\WorkEntry;
use PayCal\Domain\EarningsPdf;
use PayCal\Domain\ForecastProjectionService;
use PayCal\Domain\ForecastScenario;
use PayCal\Domain\Xlsx;
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
 * Architectural role:
 * - Entry-point controller for request handling, authorization enforcement,
 *   and response or render shaping at the web boundary.
 * - Domain policy, persistence rules, and side-effect orchestration should
 *   stay in collaborators rather than expanding controller state.
 *
 * @category   Controllers
 * @package    PayCal\Controllers
 * @subpackage HTTP
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
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
  private const DEFAULT_PAYROLL_VERIFICATION_JURISDICTION = 'CA-AB';
  private const DEFAULT_PAYROLL_VERIFICATION_BRACKET_VERSION = '2026.1';
  private const DEFAULT_PAYROLL_VERIFICATION_ENGINE_VERSION = '1.015.000';

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
    if (!self::debugRequested()) {
      return;
    }

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
    $blob = self::scalarString($row['encrypted_blob'] ?? '');
    if ($blob === '') {
      return null;
    }

    $sessionHash = Authentication::getSessionHashFromCookie();
    if ($sessionHash === null) {
      return null;
    }

    $sessionKey = Keys::SESSION . ':' . $sessionHash;
    $credentialId = self::scalarString(Database::hget($sessionKey, 'credential_id'));

    $user = User::current();
    $saltB64 = self::scalarString($user->encryption_salt);
    if ($saltB64 === '') {
      return null;
    }

    $dek = self::resolveDekForEnvelope($blob, $userUUID, $credentialId, $saltB64);
    if ($dek === null) {
      return null;
    }

    $decryptedJson = self::decryptWorkBlob($blob, $dek);
    if ($decryptedJson === null) {
      return null;
    }

    $decoded = json_decode($decryptedJson, true);
    if (!is_array($decoded)) {
      return null;
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
    if ($hasGrossAfterMerge) {
      return $merged;
    }

    $regularHours = self::numericFloat($merged['regular_hours'] ?? $merged['r'] ?? 0);
    $overtimeHours = self::numericFloat($merged['overtime_hours'] ?? $merged['o'] ?? 0);
    $travelHours = self::numericFloat($merged['travel_hours'] ?? $merged['t'] ?? 0);
    $loa = self::numericFloat($merged['living_out_allowance'] ?? $merged['l'] ?? 0);

    $wage = null;
    if (isset($merged['wage']) && is_numeric($merged['wage'])) {
      $wage = (string) $merged['wage'];
    } elseif (isset($merged['w']) && is_numeric($merged['w'])) {
      $wage = (string) $merged['w'];
    }

    $grossCents = Money::dollarsToCents((string) $loa);
    if ($wage !== null) {
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

    return $merged;
  }

  /**
   * Resolve the correct DEK wrapper for either personal or business envelopes.
   */
  private static function resolveDekForEnvelope(string $blob, string $ownerUUID, string $credentialId, string $saltB64): ?string
  {
    $businessMeta = self::parseBusinessEnvelopeMetadata($blob);
    if (is_array($businessMeta)) {
      $actorUUID = User::currentUUID();
      if ($actorUUID === '' || $credentialId === '') {
        self::appendBusinessWorkReadAudit($businessMeta, $actorUUID, $ownerUUID, 'denied', 'missing_actor_or_credential');
        return null;
      }

      $wrap = (new BusinessEncryptionService())->resolveActiveWrapForUnwrap(
        $businessMeta['business_id'],
        $businessMeta['segment'],
        $businessMeta['key_version'],
        $actorUUID,
        $credentialId,
        '',
        $businessMeta['dek_id']
      );
      if (!$wrap['success']) {
        self::appendBusinessWorkReadAudit($businessMeta, $actorUUID, $ownerUUID, 'denied', 'wrap_resolution_failed');
        return null;
      }

      $wrappedDek = self::scalarString($wrap['data']['wrapped_dek'] ?? '');
      if ($wrappedDek === '') {
        CryptoCompatibilityTelemetry::wrapperMissing(CryptoCompatibilityTelemetry::SOURCE_BUSINESS_CURRENT);
        self::appendBusinessWorkReadAudit($businessMeta, $actorUUID, $ownerUUID, 'denied', 'missing_wrapped_dek');
        return null;
      }

      CryptoCompatibilityTelemetry::wrapperPresent(CryptoCompatibilityTelemetry::SOURCE_BUSINESS_CURRENT);
      self::appendBusinessWorkReadAudit($businessMeta, $actorUUID, $ownerUUID, 'success', 'wrap_resolved');

      if (self::debugRequested()) {
        self::debug('resolveDekForEnvelope:source', [
          'mode' => 'business',
          'source' => 'business_wrap_resolved',
          'owner_uuid_present' => true,
          'credential_present' => true,
        ]);
      }

      return self::unwrapDekFromPasskeyWrapper(
        $wrappedDek,
        $credentialId,
        $actorUUID,
        $saltB64,
        CryptoCompatibilityTelemetry::SOURCE_BUSINESS_CURRENT
      );
    }

    if ($credentialId === '') {
      CryptoCompatibilityTelemetry::wrapperMissing(CryptoCompatibilityTelemetry::SOURCE_PERSONAL_CURRENT);
      return null;
    }

    $wrappedPasskeyMapKey = Keys::USER . ':' . $ownerUUID . ':passkey_wrapped_deks';
    $wrappedDekPasskey = self::scalarString(Database::hget($wrappedPasskeyMapKey, $credentialId));

    if ($wrappedDekPasskey === '') {
      CryptoCompatibilityTelemetry::wrapperMissing(CryptoCompatibilityTelemetry::SOURCE_PERSONAL_CURRENT);
      $legacyWrappedDek = self::scalarString(Database::hget(Keys::USER . ':' . $ownerUUID, 'wrapped_dek_passkey'));
      if ($legacyWrappedDek !== '') {
        CryptoCompatibilityTelemetry::wrapperPresent(CryptoCompatibilityTelemetry::SOURCE_PERSONAL_LEGACY);
        CryptoCompatibilityTelemetry::legacyWrapperBlocked();
      }
      return null;
    }

    CryptoCompatibilityTelemetry::wrapperPresent(CryptoCompatibilityTelemetry::SOURCE_PERSONAL_CURRENT);

    if (self::debugRequested()) {
      self::debug('resolveDekForEnvelope:source', [
        'mode' => 'personal',
        'source' => 'selected_credential_map',
        'owner_uuid_present' => true,
        'credential_present' => true,
      ]);
    }

    return self::unwrapDekFromPasskeyWrapper(
      $wrappedDekPasskey,
      $credentialId,
      $ownerUUID,
      $saltB64,
      CryptoCompatibilityTelemetry::SOURCE_PERSONAL_CURRENT
    );
  }

  /** @param array{business_id: string, segment: string, key_version: string, dek_id: string} $businessMeta */
  private static function appendBusinessWorkReadAudit(array $businessMeta, string $actorUUID, string $targetUUID, string $outcome, string $reason): void
  {
    if ($businessMeta['business_id'] === '') {
      return;
    }

    try {
      (new BusinessDiscoveryService())->appendBusinessAuditEvent(
        (string) $businessMeta['business_id'],
        'business.work.read',
        $actorUUID !== '' ? $actorUUID : User::currentUUID(),
        [
          'target_user_uuid' => $targetUUID,
          'segment' => $businessMeta['segment'],
          'key_version' => $businessMeta['key_version'],
          'dek_id' => $businessMeta['dek_id'],
          'outcome' => $outcome,
          'reason' => $reason,
        ]
      );
    } catch (\Throwable $e) {
      Log::debug('[EarningsController] business.work.read audit emit failed: ' . $e->getMessage());
    }
  }

  /** @return array{business_id: string, segment: string, key_version: string, dek_id: string}|null */
  private static function parseBusinessEnvelopeMetadata(string $blob): ?array
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
    if (!BusinessDiscoveryService::isBusinessEncryptionMode($mode)) {
      return null;
    }

    $businessIdRaw = $meta['business_id'] ?? ($envelope['business_id'] ?? '');
    $segmentRaw = $meta['segment'] ?? ($envelope['segment'] ?? '');
    $keyVersionRaw = $meta['key_version'] ?? ($envelope['key_version'] ?? '');
    $dekIdRaw = $meta['dek_id'] ?? ($envelope['dek_id'] ?? '');

    $businessId = is_scalar($businessIdRaw) ? trim((string) $businessIdRaw) : '';
    $segment = is_scalar($segmentRaw) ? trim((string) $segmentRaw) : '';
    $keyVersion = is_scalar($keyVersionRaw) ? trim((string) $keyVersionRaw) : '';
    $dekId = is_scalar($dekIdRaw) ? trim((string) $dekIdRaw) : '';

    if ($businessId === '' || $segment === '' || $keyVersion === '' || $dekId === '') {
      return null;
    }

    return [
      'business_id' => $businessId,
      'segment' => $segment,
      'key_version' => $keyVersion,
      'dek_id' => $dekId,
    ];
  }

  /**
   * Handles hkdfPasskeyKek operation.
   */
  private static function hkdfPasskeyKek(string $credentialId, string $saltB64): ?string
  {
    $salt = base64_decode($saltB64, true);
    if ($salt === false) {
      return null;
    }

    // Matches client derivePasskeyKEK() info label.
    return hash_hkdf('sha256', $credentialId, 32, 'paycal-passkey-kek', $salt);
  }

  /**
   * Handles unwrapDekFromPasskeyWrapper operation.
   */
  private static function unwrapDekFromPasskeyWrapper(
    string $wrappedDekPasskey,
    string $credentialId,
    string $userUUID,
    string $saltB64,
    string $telemetrySource
  ): ?string
  {
    CryptoCompatibilityTelemetry::unwrapAttempt($telemetrySource);

    $decodedEnvelope = base64_decode($wrappedDekPasskey, true);
    if ($decodedEnvelope === false) {
      CryptoCompatibilityTelemetry::unwrapFailure($telemetrySource);
      return null;
    }

    $envelope = json_decode($decodedEnvelope, true);
    if (!is_array($envelope)) {
      CryptoCompatibilityTelemetry::unwrapFailure($telemetrySource);
      return null;
    }

    $nonceB64 = self::scalarString($envelope['nonce'] ?? $envelope['iv'] ?? '');
    $ctB64 = self::scalarString($envelope['ciphertext'] ?? $envelope['ct'] ?? '');
    if ($nonceB64 === '' || $ctB64 === '') {
      CryptoCompatibilityTelemetry::unwrapFailure($telemetrySource);
      return null;
    }

    $nonce = base64_decode($nonceB64, true);
    $ciphertextWithTag = base64_decode($ctB64, true);
    if ($nonce === false || $ciphertextWithTag === false || strlen($ciphertextWithTag) < 17) {
      CryptoCompatibilityTelemetry::unwrapFailure($telemetrySource);
      return null;
    }

    $ciphertext = substr($ciphertextWithTag, 0, -16);
    $tag = substr($ciphertextWithTag, -16);

    // Canonical derivation: credential-only
    $kekCanonical = self::hkdfPasskeyKek($credentialId, $saltB64);
    if (is_string($kekCanonical) && $kekCanonical !== '') {
      $dek = openssl_decrypt($ciphertext, 'aes-256-gcm', $kekCanonical, OPENSSL_RAW_DATA, $nonce, $tag);
      if (is_string($dek) && $dek !== '') {
        CryptoCompatibilityTelemetry::unwrapSuccess($telemetrySource);
        return $dek;
      }
    }

    CryptoCompatibilityTelemetry::unwrapFailure($telemetrySource);

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
        $jurisdiction = self::payrollVerificationJurisdiction();
        $bracketVersion = self::payrollVerificationBracketVersion();
        $engineVersion = self::payrollVerificationEngineVersion();

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
    $debugRequested = self::debugRequested();
    if (!$debugRequested && is_string($sessionHash) && $sessionHash !== '') {
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
        // Decryption unavailable — fall through to plaintext snapshot fields
        // (gross is stored alongside the blob). Consistent with Earnings::getWorkTotalsForRange().
        $debug['decrypt_failed_rows']++;
        if ($debugRequested) {
          self::debug('grossYear:fallthrough', [
            'reason' => 'decrypt_failed_using_plaintext',
            'work_key' => self::scalarString($sKey),
          ]);
        }
        CryptoCompatibilityTelemetry::plaintextFallback('gross_year');
      } else {
        $aEarnings = $resolved;
      }
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
      if ($debugRequested) {
        self::debug('grossYear:row', [
          'date' => $sDate,
          'has_encrypted_blob' => $hasBlob,
          'has_plaintext_gross' => $hasGross,
          'gross_used' => $gross,
        ]);
      }
      if (!isset($formattedData[$sDate])) {
        $formattedData[$sDate] = 0.0;
      }
      $formattedData[$sDate] += $gross;
    }

    if ($debugRequested) {
      self::debug('grossYear:summary', $debug);
    }

    $extra = $formattedData;
    if ($debugRequested) {
      $extra['_debug'] = $debug;
    }
    if (!$debugRequested && is_string($sessionHash) && $sessionHash !== '') {
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
    $debugRequested = self::debugRequested();
    if (!$debugRequested && is_string($sessionHash) && $sessionHash !== '') {
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

    $formattedData = [];
    foreach ($aData as $sKey => $aEarnings) {
      $resolved = self::decryptWorkRowIfNeeded($aEarnings, $userUUID);
      if (!is_array($resolved)) {
        // Decryption unavailable — fall through to plaintext snapshot fields.
        $debug['decrypt_failed_rows']++;
        if ($debugRequested) {
          self::debug('dailyYear:fallthrough', [
            'reason' => 'decrypt_failed_using_plaintext',
            'work_key' => self::scalarString($sKey),
          ]);
        }
        CryptoCompatibilityTelemetry::plaintextFallback('daily_year');
      } else {
        $aEarnings = $resolved;
      }
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
      if ($debugRequested) {
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
      }
      if (!isset($formattedData[$sDate])) {
        $formattedData[$sDate] = [
          'date' => $sDate,
          'grossCents' => 0,
        ];
      }

      $formattedData[$sDate]['grossCents'] += Money::dollarsToCents((string) $g);
    }

    ksort($formattedData);
    $ytdGrossCents = 0;
    $previousYtdDeductionCents = 0;
    foreach ($formattedData as $dateKey => $row) {
      $grossCents = (int) $row['grossCents'];
      $ytdGrossCents += $grossCents;
      $ytdDeductions = $tax->calculateTaxesCents($ytdGrossCents);
      $ytdDeductionCents = (int) $ytdDeductions['totalDeductions'];
      $dailyDeductionCents = max(0, $ytdDeductionCents - $previousYtdDeductionCents);
      $formattedData[$dateKey]['taxCents'] = $dailyDeductionCents;
      $formattedData[$dateKey]['netCents'] = max(0, $grossCents - $dailyDeductionCents);
      $previousYtdDeductionCents = $ytdDeductionCents;
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

    if ($debugRequested) {
      self::debug('dailyYear:summary', $debug);
    }

    $extra = $formattedData;
    if ($debugRequested) {
      $extra['_debug'] = $debug;
    }
    if (!$debugRequested && is_string($sessionHash) && $sessionHash !== '') {
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
      Log::error('[EC] initializeExport: invalid JSON body: ' . $e->getMessage());
      Response::error('[EC] Invalid JSON payload.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    if (!is_array($postData)) {
      Response::error('[EC] JSON payload must be an object.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    // Extract and validate required fields
    $format = isset($postData['format']) && is_string($postData['format']) ? strtolower(trim($postData['format'])) : '';
    $scope = isset($postData['scope']) && is_string($postData['scope']) ? strtolower(trim($postData['scope'])) : 'yearly';
    $year = isset($postData['year']) && is_numeric($postData['year']) ? (int) $postData['year'] : 0;

    // Validate inputs
    if ($format === '' || !in_array($format, self::supportedExportFormats(), true) || $year < 1900 || $year > 2100) {
      Response::error('[EC] Invalid export parameters: format and valid year are required.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    if (!self::currentUserCanExportFormat($format)) {
      \PayCal\Infrastructure\Telemetry\SecurityLog::log('earnings_export', [
        'scope' => $scope,
        'format' => $format,
        'year' => $year,
        'result' => 'denied',
        'reason' => 'premium_required',
      ]);
      Response::error('[EC] Premium subscription required for this export format.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    // Generate reference code (16-char alphanumeric)
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $referenceCode = '';
    for ($i = 0; $i < 16; $i += 1) {
      $referenceCode .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    // Log the export event immediately
    \PayCal\Infrastructure\Telemetry\SecurityLog::log('earnings_export', [
      'scope' => $scope,
      'format' => $format,
      'year' => $year,
      'reference_code' => $referenceCode,
    ]);

    Response::success('[EC] Export initialized.', [
      'reference_code' => $referenceCode,
    ]);
  }

  #[Route('export/xlsx', ['POST'])]
  /**
   * Generate and stream an XLSX earnings report.
   *
   * Expects a JSON body with:
   *   scope        string  yearly|monthly|daily|payperiod
   *   rows         array   Day-level detailed rows (from buildDetailedRows in JS)
   *   report       object  Aggregated report object (from buildXxxReportJson in JS)
   *   year         int     Report year (used for filename)
   *   start_date   string  ISO date (payperiod only)
   *   end_date     string  ISO date (payperiod only)
   */
  public function exportXlsx(): void
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
      Log::error('[EC] exportXlsx: invalid JSON body: ' . $e->getMessage());
      Response::error('[EC] Invalid JSON payload.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    if (!is_array($postData)) {
      Response::error('[EC] JSON payload must be an object.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $scope     = isset($postData['scope']) && is_string($postData['scope']) ? trim($postData['scope']) : '';
    $rows      = isset($postData['rows']) && is_array($postData['rows']) ? $postData['rows'] : [];
    $report    = isset($postData['report']) && is_array($postData['report']) ? $postData['report'] : [];
    $year      = isset($postData['year']) && is_numeric($postData['year']) ? (int) $postData['year'] : (int) date('Y');
    $startDate = isset($postData['start_date']) && is_string($postData['start_date']) ? preg_replace('/[^0-9\-]/', '', $postData['start_date']) : '';
    $endDate   = isset($postData['end_date']) && is_string($postData['end_date']) ? preg_replace('/[^0-9\-]/', '', $postData['end_date']) : '';

    $allowedScopes = ['yearly', 'monthly', 'daily', 'payperiod'];
    if (!in_array($scope, $allowedScopes, true)) {
      Response::error('[EC] Invalid export scope.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    if (!self::currentUserCanExportFormat('xlsx')) {
      \PayCal\Infrastructure\Telemetry\SecurityLog::log('earnings_export', [
        'scope' => $scope,
        'format' => 'xlsx',
        'year' => $year,
        'result' => 'denied',
        'reason' => 'premium_required',
      ]);
      Response::error('[EC] Premium subscription required for XLSX exports.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    if (count($rows) === 0) {
      Response::error('[EC] No rows to export.', [], HttpStatus::HTTP_UNPROCESSABLE);
      return;
    }

    if (!self::exportPayloadIsPersonalToCurrentUser($postData)) {
      \PayCal\Infrastructure\Telemetry\SecurityLog::log('earnings_export', [
        'scope' => $scope,
        'format' => 'xlsx',
        'year' => $year,
        'result' => 'denied',
        'reason' => 'non_personal_export_payload',
      ]);
      Response::error('[EC] Export payload is not authorized for this user.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    $fileSuffix = ($scope === 'payperiod' && $startDate !== '' && $endDate !== '')
      ? "{$startDate}_to_{$endDate}"
      : (string) $year;
    $filename = "paycal-{$scope}-{$fileSuffix}.xlsx";

    try {
      $xlsx = Xlsx::generate($scope, $rows, $report);
    } catch (\InvalidArgumentException $e) {
      Log::error('[EC] exportXlsx: Xlsx generation failed: ' . $e->getMessage());
      Response::error('[EC] Invalid export parameters.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    \PayCal\Infrastructure\Telemetry\SecurityLog::log('earnings_export', [
      'scope'  => $scope,
      'format' => 'xlsx',
      'year'   => $year,
    ]);

    http_response_code(HttpStatus::HTTP_OK);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    echo $xlsx;
  }

  // ---------------------------------------------------------------------------

  /**
   * Export earnings as a PDF report using Tabularium.
   *
   * Expects a JSON body with:
   *   scope        string  yearly|monthly|daily|payperiod
   *   report       object  Aggregated report object (from buildXxxReportJson in JS)
   *   year         int     Report year (used for filename)
   *   start_date   string  ISO date (payperiod only)
   *   end_date     string  ISO date (payperiod only)
   *   print_mode   string  Optional color mode: bw|grayscale|color
   */
  #[Route('export/pdf', ['POST'])]
  public function exportPdf(): void
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
      Log::error('[EC] exportPdf: invalid JSON body: ' . $e->getMessage());
      Response::error('[EC] Invalid JSON payload.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    if (!is_array($postData)) {
      Response::error('[EC] JSON payload must be an object.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $scope     = isset($postData['scope']) && is_string($postData['scope']) ? trim($postData['scope']) : '';
    $report    = isset($postData['report']) && is_array($postData['report']) ? $postData['report'] : [];
    $year      = isset($postData['year']) && is_numeric($postData['year']) ? (int) $postData['year'] : (int) date('Y');
    $startDate = isset($postData['start_date']) && is_string($postData['start_date']) ? preg_replace('/[^0-9\-]/', '', $postData['start_date']) : '';
    $endDate   = isset($postData['end_date']) && is_string($postData['end_date']) ? preg_replace('/[^0-9\-]/', '', $postData['end_date']) : '';
    $printMode = isset($postData['print_mode']) && is_string($postData['print_mode'])
      ? strtolower(trim($postData['print_mode']))
      : 'color';
    if (!in_array($printMode, ['bw', 'grayscale', 'color'], true)) {
      $printMode = 'color';
    }

    $allowedScopes = ['yearly', 'monthly', 'daily', 'payperiod'];
    if (!in_array($scope, $allowedScopes, true)) {
      Response::error('[EC] Invalid export scope.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $rows = isset($report['rows']) && is_array($report['rows']) ? $report['rows'] : [];
    if (count($rows) === 0) {
      Response::error('[EC] No rows to export.', [], HttpStatus::HTTP_UNPROCESSABLE);
      return;
    }

    if (!self::exportPayloadIsPersonalToCurrentUser($postData)) {
      \PayCal\Infrastructure\Telemetry\SecurityLog::log('earnings_export', [
        'scope' => $scope,
        'format' => 'pdf',
        'year' => $year,
        'result' => 'denied',
        'reason' => 'non_personal_export_payload',
      ]);
      Response::error('[EC] Export payload is not authorized for this user.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    $fileSuffix = ($scope === 'payperiod' && $startDate !== '' && $endDate !== '')
      ? "{$startDate}_to_{$endDate}"
      : (string) $year;
    $filename = "paycal-{$scope}-{$fileSuffix}.pdf";

    try {
      $pdf = EarningsPdf::generate($scope, $report, $printMode);
    } catch (\InvalidArgumentException $e) {
      Log::error('[EC] exportPdf: PDF generation failed: ' . $e->getMessage());
      Response::error('[EC] Invalid export parameters.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    \PayCal\Infrastructure\Telemetry\SecurityLog::log('earnings_export', [
      'scope'  => $scope,
      'format' => 'pdf',
      'year'   => $year,
    ]);

    $encoded = rawurlencode($filename);
    http_response_code(HttpStatus::HTTP_OK);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\''. $encoded);
    header('Cache-Control: max-age=0');
    echo $pdf;
  }

  /**
   * @param array<string, mixed> $payload
   */
  private static function exportPayloadIsPersonalToCurrentUser(array $payload): bool
  {
    $currentUUID = trim(User::currentUUID());
    if ($currentUUID === '') {
      return false;
    }

    if (self::containsBusinessExportMarker($payload)) {
      return false;
    }

    $report = is_array($payload['report'] ?? null) ? $payload['report'] : [];
    $meta = is_array($report['meta'] ?? null) ? $report['meta'] : [];
    $employee = isset($meta['employee']) && is_scalar($meta['employee'])
      ? trim((string) $meta['employee'])
      : '';

    return $employee !== '' && hash_equals($currentUUID, $employee);
  }

  /** @param array<string|int, mixed> $payload */
  private static function containsBusinessExportMarker(array $payload, int $depth = 0): bool
  {
    if ($depth > 5) {
      return false;
    }

    $businessMarkerKeys = [
      'business_id' => true,
      'business_uuid' => true,
      'org_id' => true,
      'organization_id' => true,
      'member_uuid' => true,
      'target_member_uuid' => true,
      'business_member_uuid' => true,
      'protected_business_export' => true,
      'business_scope' => true,
      'encrypted_blob' => true,
      'org_envelope' => true,
      'generation_path' => true,
      'trust_level' => true,
    ];

    foreach ($payload as $key => $value) {
      $normalizedKey = is_string($key) ? strtolower(trim($key)) : '';
      if ($normalizedKey !== '') {
        if (isset($businessMarkerKeys[$normalizedKey]) && self::hasMarkerValue($value)) {
          return true;
        }

        if (
          in_array($normalizedKey, ['source', 'report_source', 'export_source'], true)
          && is_scalar($value)
          && str_contains(strtolower((string) $value), 'business')
        ) {
          return true;
        }
      }

      if (is_array($value) && self::containsBusinessExportMarker($value, $depth + 1)) {
        return true;
      }
    }

    return false;
  }

  private static function hasMarkerValue(mixed $value): bool
  {
    if (is_array($value)) {
      return $value !== [];
    }

    if (is_scalar($value)) {
      return trim((string) $value) !== '';
    }

    return $value !== null;
  }

  private static function payrollVerificationJurisdiction(): string
  {
    return self::envString(
      'PAYROLL_VERIFICATION_JURISDICTION',
      self::DEFAULT_PAYROLL_VERIFICATION_JURISDICTION
    );
  }

  private static function payrollVerificationBracketVersion(): string
  {
    return self::envString(
      'PAYROLL_VERIFICATION_BRACKET_VERSION',
      self::DEFAULT_PAYROLL_VERIFICATION_BRACKET_VERSION
    );
  }

  private static function payrollVerificationEngineVersion(): string
  {
    return self::envString(
      'PAYROLL_VERIFICATION_ENGINE_VERSION',
      self::DEFAULT_PAYROLL_VERIFICATION_ENGINE_VERSION
    );
  }

  private static function envString(string $key, string $default): string
  {
    $value = $_ENV[$key] ?? $default;
    if (!is_scalar($value)) {
      return $default;
    }

    $sanitized = trim(InputSanitizer::sanitizeString((string) $value));

    return $sanitized !== '' ? $sanitized : $default;
  }

  /**
   * GET forecast/state — initial forecast workspace state (no Redis mutation).
   */
  #[Route('forecast/state', ['GET'])]
  public static function getForecastState(): void
  {
    self::bootLens('api/forecast/state');

    if (!self::currentUserHasPremiumReporting()) {
      Response::error('[EC] Premium subscription required for Forecast.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    $scenarioRaw = strtolower(trim(InputSanitizer::getString('scenario') ?? 'normal'));
    $scenario = ForecastScenario::tryFrom($scenarioRaw) ?? ForecastScenario::Normal;

    $state = (new ForecastProjectionService())->buildState(User::current(), $scenario);
    Response::success('[EC] Forecast state retrieved.', $state);
  }

  /**
   * POST forecast/preview — recalculate with calculator overrides (no persistence).
   *
   * Body: { overrides?: object, scenario?: string }
   */
  #[Route('forecast/preview', ['POST'])]
  public static function postForecastPreview(): void
  {
    self::bootLens('api/forecast/preview');

    if (!self::currentUserHasPremiumReporting()) {
      Response::error('[EC] Premium subscription required for Forecast.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    $body = file_get_contents('php://input');
    $postData = [];
    if (is_string($body) && $body !== '') {
      try {
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (is_array($decoded)) {
          $postData = $decoded;
        }
      } catch (\JsonException) {
        Response::error('[EC] Invalid JSON payload.', [], HttpStatus::HTTP_BAD_REQUEST);

        return;
      }
    }

    $scenarioInput = $postData['scenario'] ?? 'normal';
    $scenarioRaw = strtolower(trim(is_scalar($scenarioInput) ? (string) $scenarioInput : 'normal'));
    $scenario = ForecastScenario::tryFrom($scenarioRaw) ?? ForecastScenario::Normal;
    $overrides = isset($postData['overrides']) && is_array($postData['overrides'])
      ? $postData['overrides']
      : [];

    $state = (new ForecastProjectionService())->preview(User::current(), $overrides, $scenario);
    Response::success('[EC] Forecast preview calculated.', $state);
  }

  /**
   * @return list<string>
   */
  private static function supportedExportFormats(): array
  {
    return ['csv', 'txt', 'xlsx', 'pdf'];
  }

  private static function currentUserCanExportFormat(string $format): bool
  {
    return strtolower($format) === 'pdf' || self::currentUserHasPremiumReporting();
  }

  private static function currentUserHasPremiumReporting(): bool
  {
    return SubscriptionRepository::isPremiumActive(User::currentUUID());
  }
}
