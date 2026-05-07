<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;
use PayCal\Infrastructure\Organization\OrganizationEncryptionService;

/**
 * Earnings.php
 *
 * Purpose: Earnings aggregation and reporting domain service for payroll-style
 * totals, summaries, projections, and rendered reporting fragments.
 *
 * Developer notes:
 * - This class sits near the center of reporting behavior and consumes work,
 *   site, tax, and pay-period information together.
 * - Output-shape changes here can affect APIs, exports, cached payloads, and
 *   rendered earnings UI sections.
 * - Keep calculation logic deterministic and avoid UI-specific leakage into
 *   core aggregation routines where possible.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Earnings domain service.
 *
 * Responsibilities:
 * - Aggregate work-driven earnings over multiple date windows.
 * - Apply tax and deduction logic to derive net results.
 * - Produce reporting-ready summaries for APIs, pages, and exports.
 */
class Earnings
{

  /**
   * Handles batchI18n operation.
   */
  private static function batchI18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
  /**
   * Emit request-scoped debug data to both Lens and debug.log.
   *
   * @param array<string, mixed> $payload
   */
  private static function lensDebug(string $label, array $payload): void
  {
    \PayCal\Observability\Lens::add($label, $payload, 'data');
    Log::debug('[EarningsLens] ' . $label . ' ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
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
   * Resolve request locale for number formatting.
   */
  private static function numberLocale(): string
  {
    if (defined('USER_LOCALE') && is_string(USER_LOCALE) && USER_LOCALE !== '') {
      return USER_LOCALE;
    }

    return 'en_US';
  }

  /**
   * Locale-aware decimal formatter with grouped thousands.
   */
  private static function formatNumberLocalized(int|float $value, int $fractionDigits = 0): string
  {
    if (class_exists('\\NumberFormatter')) {
      $formatter = new \NumberFormatter(self::numberLocale(), \NumberFormatter::DECIMAL);
      $formatter->setAttribute(\NumberFormatter::GROUPING_USED, 1);
      $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $fractionDigits);
      $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $fractionDigits);
      $formatted = $formatter->format($value);
      if (is_string($formatted)) {
        return $formatted;
      }
    }

    return number_format((float) $value, $fractionDigits, '.', ',');
  }

  /**
   * Locale-aware money formatter (keeps current dollar-prefix behavior).
   */
  private static function formatCurrencyCentsLocalized(int $cents): string
  {
    return '$' . self::formatNumberLocalized($cents / 100, 2);
  }

  /**
   * Locale-aware signed percentage formatter.
   */
  private static function formatSignedPercentLocalized(float $value, int $fractionDigits = 1): string
  {
    $sign = $value > 0 ? '+' : ($value < 0 ? '-' : '');

    return $sign . self::formatNumberLocalized(abs($value), $fractionDigits) . '%';
  }

  /**
   * Handles resolveExpectedPayRate operation.
   */
  private static function resolveExpectedPayRate(?User $user): ?float
  {
    if ($user !== null && is_numeric($user->pay_rate) && (float) $user->pay_rate > 0.0) {
      return (float) $user->pay_rate;
    }

    return null;
  }

  /**
   * @param array<string,mixed> $row
   */
  private static function resolveEntryWageRate(array $row, string $userUUID): ?float
  {
    if (isset($row['wage']) && is_numeric($row['wage']) && (float) $row['wage'] > 0.0) {
      return (float) $row['wage'];
    }

    if (isset($row['w']) && is_numeric($row['w']) && (float) $row['w'] > 0.0) {
      return (float) $row['w'];
    }

    $siteId = self::scalarString($row['site_id'] ?? '');
    if ($siteId !== '') {
      $siteWages = self::getSiteWages($userUUID);
      if (isset($siteWages[$siteId]) && is_numeric($siteWages[$siteId]) && (float) $siteWages[$siteId] > 0.0) {
        return (float) $siteWages[$siteId];
      }
    }

    return null;
  }

  /**
   * @param array<string,mixed> $row
   */
  private static function resolveEntrySiteName(array $row, string $userUUID): ?string
  {
    $siteName = trim(self::scalarString($row['site_name'] ?? ''));
    if ($siteName !== '') {
      return $siteName;
    }

    $siteId = trim(self::scalarString($row['site_id'] ?? ''));
    if ($siteId !== '') {
      $resolvedName = trim(Sites::getSiteName($siteId, $userUUID));
      if ($resolvedName !== '') {
        return $resolvedName;
      }
      return $siteId;
    }

    return null;
  }

  /** @var array<string, array<string, string>> */
  private static array $siteWagesCache = [];

  /** @return array<string, string> */
  private static function getSiteWages(string $userUUID): array
  {
    if (!isset(self::$siteWagesCache[$userUUID])) {
      self::$siteWagesCache[$userUUID] = iterator_to_array(Sites::getSiteWages($userUUID));
    }

    return self::$siteWagesCache[$userUUID];
  }

  /**
   * @param array<string,mixed> $row
   * @return array<string, mixed>|null
   */
  private static function resolveWorkRow(array $row, ?string $userUUID = null): ?array
  {
    $userUUID ??= User::currentUUID();
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

    $normalized = WorkEntry::normalizeWorkEntryPayload($decoded);
    $merged = $row;
    foreach ($normalized as $k => $v) {
      $merged[(string) $k] = $v;
    }

    if ((!isset($merged['hours']) || !is_numeric($merged['hours']))
      && isset($merged['regular_hours'], $merged['overtime_hours'])
      && is_numeric($merged['regular_hours'])
      && is_numeric($merged['overtime_hours'])) {
      $merged['hours'] = (float) $merged['regular_hours'] + (float) $merged['overtime_hours'];
    }

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
   * Resolve the correct DEK wrapper for either personal or organization envelopes.
   */
  private static function resolveDekForEnvelope(string $blob, string $ownerUUID, string $credentialId, string $saltB64): ?string
  {
    $orgMeta = self::parseOrganizationEnvelopeMetadata($blob);
    if (is_array($orgMeta)) {
      $actorUUID = User::currentUUID();
      if ($actorUUID === '' || $credentialId === '') {
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
        return null;
      }

      $wrappedDek = self::scalarString($wrap['data']['wrapped_dek'] ?? '');
      if ($wrappedDek === '') {
        return null;
      }

      return self::unwrapDekFromPasskeyWrapper($wrappedDek, $credentialId, $actorUUID, $saltB64);
    }

    $wrappedPasskeyMapKey = Keys::USER . ':' . $ownerUUID . ':passkey_wrapped_deks';
    $wrappedDekPasskey = '';
    if ($credentialId !== '') {
      $wrappedDekPasskey = self::scalarString(Database::hget($wrappedPasskeyMapKey, $credentialId));
    }

    if ($credentialId === '') {
      return null;
    }
    if ($wrappedDekPasskey === '') {
      return null;
    }

    return self::unwrapDekFromPasskeyWrapper($wrappedDekPasskey, $credentialId, $ownerUUID, $saltB64);
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
  private static function hkdfPasskeyKek(string $credentialId, string $saltB64): ?string
  {
    $salt = base64_decode($saltB64, true);
    if ($salt === false) {
      return null;
    }

    return hash_hkdf('sha256', $credentialId, 32, 'paycal-passkey-kek', $salt);
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

    $kekCanonical = self::hkdfPasskeyKek($credentialId, $saltB64);
    if (is_string($kekCanonical) && $kekCanonical !== '') {
      $dek = openssl_decrypt($ciphertext, 'aes-256-gcm', $kekCanonical, OPENSSL_RAW_DATA, $nonce, $tag);
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

  public int $grossCents = 0;

  public int $taxCents = 0;

  public int $netCents = 0;

  /**
   * Initializes a new instance.
   */
  public function __construct(int $grossCents, int $taxCents, int $netCents)
  {
    $this->grossCents = $grossCents;
    $this->taxCents   = $taxCents;
    $this->netCents   = $netCents;
  }

  /**
   * Hash the canonical serialized payload using SHA-256, output as lowercase hex.
   *
   * @return string Lowercase hex SHA-256 hash
   */

  /**
   * Prevent cloning of the Earnings object.
   */
  private function __clone(): void
  {
  }

  /**
   * Prevent unserializing of the Earnings object.
   */
  public function __wakeup(): void
  {
  }

  /**
   * Generate Ed25519 keypair for user if missing.
   * Saves private and public keys as base64 files in keys directory.
   */
  public static function ensureUserSigningKeys(string $userUUID, int $version = 1): string
  {
    $keyDir   = '/var/www/paycal/dev/keys/';
    $settings = UserSettings::getInstance($userUUID);

    // Check if keys already exist
    $existingKeyUUIDRaw = $settings->get('key_uuid');
    $existingKeyUUID = is_scalar($existingKeyUUIDRaw) ? (string) $existingKeyUUIDRaw : '';
    $existingVersion = $settings->get('key_version');
    $existingVersionInt = is_numeric($existingVersion) ? (int) $existingVersion : null;
    
    if ($existingKeyUUID && $existingVersionInt === $version) {
      $privPath = $keyDir . $existingKeyUUID . '-private-signing-v' . $version . '.key';
      $pubPath  = $keyDir . $existingKeyUUID . '-public-signing-v' . $version . '.key';
      
      // If both key files exist, return the existing keyUUID
      if (file_exists($privPath) && file_exists($pubPath)) {
        return $existingKeyUUID;
      }
    }

    // Generate new key UUID and version
    $keyUUID = bin2hex(random_bytes(16));
    $settings->set('key_uuid', $keyUUID);
    $settings->set('key_version', $version);
    $privPath = $keyDir . $keyUUID . '-private-signing-v' . $version . '.key';
    $pubPath  = $keyDir . $keyUUID . '-public-signing-v' . $version . '.key';
    if (!extension_loaded('sodium')) {
      throw new \RuntimeException('Sodium extension required for key generation');
    }
    if (!is_dir($keyDir)) {
      mkdir($keyDir, 0700, true);
    }
    $keypair    = sodium_crypto_sign_keypair();
    $privateKey = sodium_crypto_sign_secretkey($keypair);
    $publicKey  = sodium_crypto_sign_publickey($keypair);
    file_put_contents($privPath, base64_encode($privateKey) . "\n");
    file_put_contents($pubPath, base64_encode($publicKey) . "\n");
    
    return $keyUUID;
  }

  /**
   * Build verification payload for payroll hash tests.
    * @return array<string, int|string>
   */
  public static function buildVerificationPayload(
    string $periodId,
    string $employeeId,
    int $grossCents,
    int $taxCents,
    int $netCents,
    string $jurisdiction,
    string $bracketVersion,
    string $engineVersion
  ): array {
    return [
      'v'              => 1,
      'periodId'       => $periodId,
      'employeeId'     => $employeeId,
      'grossCents'     => $grossCents,
      'taxCents'       => $taxCents,
      'netCents'       => $netCents,
      'jurisdiction'   => $jurisdiction,
      'bracketVersion' => $bracketVersion,
      'engineVersion'  => $engineVersion,
    ];
  }

  /**
   * Serialize payload to canonical JSON format (deterministic for hashing).
   *
    * @param array<string, mixed> $payload The verification payload array
   * @return string JSON-serialized payload
   */
  public static function serializeCanonicalPayload(array $payload): string
  {
    $result = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
    if ($result === false) {
      throw new \RuntimeException('Failed to encode payload to JSON');
    }
    return $result;
  }

  /**
   * Hash the canonical serialized scoped payload using SHA-256, output as lowercase hex.
   *
   * @return string Lowercase hex SHA-256 hash
   */
  public static function hashPayload(string $serialized): string
  {
    return strtolower(hash('sha256', $serialized));
  }

  /**
   * Sign the canonical serialized payload using Ed25519 (libsodium).
   *
   * @return string base64 signature
   */

  /**
   * Sign canonical payload with the private key for the given version.
   *
   * @param string $canonicalPayload The serialized payload to sign
   * @param int|null $keyVersion The key version to use (default: from settings or constant)
   * @param string|null $keyUUID The key UUID to use (default: from settings)
   * @return string base64 signature
   */
  public static function signCanonicalVerificationPayload(string $canonicalPayload, ?int $keyVersion = null, ?string $keyUUID = null): string
  {
    $userUUID = User::currentUUID();
    $settings = UserSettings::getInstance($userUUID);
    $resolvedKeyUUIDRaw = $keyUUID ?? $settings->get('key_uuid');
    $resolvedKeyUUID = is_scalar($resolvedKeyUUIDRaw) ? (string) $resolvedKeyUUIDRaw : '';
    
    if (empty($resolvedKeyUUID)) {
      error_log("Ed25519 key UUID not found for user {$userUUID}");
      throw new \RuntimeException("Ed25519 key UUID not found. Keys may need to be initialized.");
    }
    
    $versionRaw = $keyVersion ?? $settings->get('key_version') ?? 1;
    $version = is_numeric($versionRaw) ? (int) $versionRaw : 1;
    $keyPath = "/var/www/paycal/dev/keys/{$resolvedKeyUUID}-private-signing-v{$version}.key";
    if (!file_exists($keyPath)) {
      error_log("Ed25519 private key file not found: {$keyPath}");
      throw new \RuntimeException("Ed25519 private key file not found: {$keyPath}");
    }
    $keyContent = file_get_contents($keyPath);
    if ($keyContent === false) {
      throw new \RuntimeException("Failed to read key file: {$keyPath}");
    }
    $privateKey = base64_decode(trim($keyContent), true);
    if (!$privateKey || SODIUM_CRYPTO_SIGN_SECRETKEYBYTES !== strlen($privateKey)) {
      error_log('Ed25519 private key not set or invalid length');
      throw new \RuntimeException('Ed25519 private key not set or invalid length');
    }
    $signature = sodium_crypto_sign_detached($canonicalPayload, $privateKey);

    return base64_encode($signature);
  }

  /**
   * Get all active public keys by version (for verification).
   *
   * @param string|null $userUUID User UUID (defaults to current user or test user)
   * @return array<int, string> version => base64 public key
   */
  public static function getActivePublicKeys(?string $userUUID = null): array
  {
    if (null === $userUUID) {
      $userUUID = User::currentUUID();
    }
    $settings = UserSettings::getInstance($userUUID);
    $keyUUIDRaw = $settings->get('key_uuid');
    $keyUUID = is_scalar($keyUUIDRaw) ? (string) $keyUUIDRaw : '';
    $keyVersionRaw = $settings->get('key_version') ?? 1;
    $keyVersion = is_numeric($keyVersionRaw) ? (int) $keyVersionRaw : 1;
    $keys = [];
    foreach ([$keyVersion] as $version) {
      $pubKeyPath = "/var/www/paycal/dev/keys/{$keyUUID}-public-signing-v{$version}.key";
      if (!file_exists($pubKeyPath)) {
        error_log("Public key file not found: {$pubKeyPath}");
        $keys[$version] = '';
        continue;
      }

      $keyContent = file_get_contents($pubKeyPath);
      if ($keyContent === false) {
        error_log("Failed to read public key file: {$pubKeyPath}");
        $keys[$version] = '';
        continue;
      }

      $pubKey = trim($keyContent);
      $decoded = base64_decode($pubKey, true);
      if ($decoded && strlen($decoded) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
        $keys[$version] = $pubKey;
      } else {
        error_log("Invalid Ed25519 public key size for version {$version}: " . (strlen($decoded ?: '')));
        $keys[$version] = '';
      }
    }

    return $keys;
  }

  /**
   * Get revoked key versions (for alerting).
   *
   * @return array<int>
   */
  public static function getRevokedKeyVersions(): array
  {
    // Example: [1] means v1 is revoked
    $revoked = getenv('PAYROLL_SIGNING_REVOKED_KEYS');
    if (!$revoked) {
      return [];
    }

    return array_map('intval', explode(',', $revoked));
  }

  /**
   * Build a canonical, deterministic payroll verification payload (v1).
   *
    * @return array<string, mixed> Canonical, ordered payload
   */
  public static function buildCanonicalVerificationPayload(
    PayPeriods $period,
    string $employeeId,
    string $jurisdiction,
    string $bracketVersion,
    string $engineVersion,
    int $grossCents,
    int $taxCents,
    int $netCents,
    int $signingKeyVersion = 1
  ): array {
    // Canonical, fixed order, explicit scope and period object
    return [
      'v' => 1,
      'scope' => 'pay_period',
      'period' => [
        'start'     => $period->start()->format('Y-m-d'),
        'end'       => $period->endInclusive()->format('Y-m-d'),
        'frequency' => $period->getFrequency(),
      ],
      'employeeId'        => $employeeId,
      'jurisdiction'      => $jurisdiction,
      'bracketVersion'    => $bracketVersion,
      'engineVersion'     => $engineVersion,
      'grossCents'        => $grossCents,
      'taxCents'          => $taxCents,
      'netCents'          => $netCents,
      'signingKeyVersion' => $signingKeyVersion,
    ];
  }

  /** @param array<string, mixed> $payload */
  public static function serializeVerificationPayload(array $payload): string
  {
    // Build JSON in fixed order, no pretty print, no whitespace, UTF-8, unescaped slashes/unicode
    // Do NOT rely on associative array key order in json_encode; use array in correct order
    $ordered = [
      'v'                 => $payload['v'],
      'scope'             => $payload['scope'],
      'period'            => $payload['period'],
      'employeeId'        => $payload['employeeId'],
      'jurisdiction'      => $payload['jurisdiction'],
      'bracketVersion'    => $payload['bracketVersion'],
      'engineVersion'     => $payload['engineVersion'],
      'grossCents'        => $payload['grossCents'],
      'taxCents'          => $payload['taxCents'],
      'netCents'          => $payload['netCents'],
      'signingKeyVersion' => $payload['signingKeyVersion'] ?? 1,
    ];

    $result = json_encode($ordered, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($result === false) {
      throw new \RuntimeException('Failed to encode verification payload to JSON');
    }
    return $result;
  }

  /**
   * Returns a shared instance of Earnings with current user UUID and default Taxes.
   */
  public static function getInstance(): self
  {
    static $instance = null;
    if (null === $instance) {
      $instance = new self(0, 0, 0);
    }

    return $instance;
  }

  /**
   * Aggregate validated work data over a date range.
   * Sums gross income, regular hours, and overtime hours from Work::getWorkInRange().
   *
   * Internal money accumulation uses integer cents for drift-free calculation.
   * Output maintains backward-compatible float format.
   *
   * @param \DateTimeImmutable $start inclusive start date
   * @param \DateTimeImmutable $end   inclusive end date
   *
   * @return array{grossIncome: float, regularHours: float, overtimeHours: float, grossIncomeCents: int}
   */
  public static function getWorkTotalsForRange(\DateTimeImmutable $start, \DateTimeImmutable $end): array
  {
    // Money accumulated as integer cents (drift-free)
    $grossIncomeCents = 0;
    // Hours remain as float (time, not money)
    $regularHours = 0.0;
    $overtimeHours = 0.0;

    $rows = Work::getInstance()->getWorkInRange($start, $end->modify('+1 day'));
    self::lensDebug('getWorkTotalsForRange:start', [
      'start' => $start->format('Y-m-d'),
      'end' => $end->format('Y-m-d'),
    ]);

    foreach ($rows as $row) {
      $lensDebug = [
        'date' => self::scalarString($row['date'] ?? ''),
        'site_id' => self::scalarString($row['site_id'] ?? ''),
        'has_encrypted_blob' => isset($row['encrypted_blob']) && is_string($row['encrypted_blob']) && $row['encrypted_blob'] !== '',
      ];
      $workData = self::resolveWorkRow($row, User::currentUUID());
      if (!is_array($workData)) {
        self::lensDebug('getWorkTotalsForRange:skip', $lensDebug + [
          'reason' => 'resolve_failed',
        ]);
        continue;
      }
      if (
        !isset($workData['regular_hours'], $workData['overtime_hours'])
        || !is_numeric($workData['regular_hours'])
        || !is_numeric($workData['overtime_hours'])
      ) {
        self::lensDebug('getWorkTotalsForRange:skip', $lensDebug + [
          'reason' => 'missing_or_invalid_hour_fields',
          'has_regular' => isset($workData['regular_hours']),
          'has_overtime' => isset($workData['overtime_hours']),
        ]);
        continue; // skip invalid or undecrypted rows
      }

      // Gross may be absent on older/encrypted snapshots; keep hours and compute best-effort gross.
      $grossDollars = self::scalarString($workData['gross'] ?? $workData['g'] ?? '', '');
      if ($grossDollars === '') {
        $regular = self::numericFloat($workData['regular_hours']);
        $overtime = self::numericFloat($workData['overtime_hours']);
        $travel = self::numericFloat($workData['travel_hours'] ?? $workData['t'] ?? 0.0);
        $loa = self::scalarString($workData['living_out_allowance'] ?? $workData['l'] ?? '0', '0');
        $resolvedWageRate = self::resolveEntryWageRate($workData, User::currentUUID());

        if ($resolvedWageRate !== null && $resolvedWageRate > 0.0) {
          $resolvedWage = (string) $resolvedWageRate;
          $computedGrossCents = Money::calculateGross($regular, $overtime, $resolvedWage);
          if ($travel > 0.0) {
            $computedGrossCents += Money::dollarsToCents((string) ($travel * $resolvedWageRate));
          }
          $computedGrossCents += Money::dollarsToCents($loa);
          $grossIncomeCents += $computedGrossCents;
        }
      } else {
        // Convert gross to cents before accumulation (prevents drift)
        $grossIncomeCents += Money::dollarsToCents($grossDollars);
      }

      // Hours can remain float (time, not money)
      $regularHours += self::numericFloat($workData['regular_hours']);
      $overtimeHours += self::numericFloat($workData['overtime_hours']);
      self::lensDebug('getWorkTotalsForRange:aggregate', $lensDebug + [
        'gross' => $grossDollars,
        'regular_hours' => self::numericFloat($workData['regular_hours']),
        'overtime_hours' => self::numericFloat($workData['overtime_hours']),
        'grossIncomeCents' => $grossIncomeCents,
        'regularHours' => $regularHours,
        'overtimeHours' => $overtimeHours,
      ]);
    }

    self::lensDebug('getWorkTotalsForRange:done', [
      'grossIncomeCents' => $grossIncomeCents,
      'regularHours' => $regularHours,
      'overtimeHours' => $overtimeHours,
    ]);

    return [
      'grossIncome'      => (float) Money::centsToDollars($grossIncomeCents),
      'regularHours'     => $regularHours,
      'overtimeHours'    => $overtimeHours,
      'grossIncomeCents' => $grossIncomeCents
    ];
  }

  /**
   * Export work entries for a given year as CSV.
   * Emits a header row followed by one row per work entry and a final totals row.
   * Columns: date, gross, regular_hours, overtime_hours, cum_gross, cum_regular_hours, cum_overtime_hours.
   *
   * @return string CSV content
   */
  public function getDateRange(int $year): string
  {
    $start = new \DateTimeImmutable("{$year}-01-01");
    $end = new \DateTimeImmutable("{$year}-12-31");

    // Fetch raw rows in range (data layer)
    $rows = Work::getInstance()->getWorkInRange($start, $end->modify('+1 day'));

    // Running totals
    $cumulativeGrossEarnings = 0.0;
    $cumulativeRegularHours  = 0.0;
    $cumulativeOvertimeHours = 0.0;

    // Build CSV in-memory
    /** @var resource $fileHandle */
    $fileHandle = fopen('php://temp', 'r+');
    fputcsv($fileHandle, [
      'date',
      'gross',
      'regular_hours',
      'overtime_hours',
      'cum_gross',
      'cum_regular_hours',
      'cum_overtime_hours',
    ]);

    foreach ($rows as $row) {
      $resolved = self::resolveWorkRow($row, User::currentUUID());
      if (is_array($resolved)) {
        $row = $resolved;
      }

      // Minimal validation; earnings payload guarantees g/r/o numeric
      if (
        !isset($row['gross'], $row['regular_hours'], $row['overtime_hours'])
        || !is_numeric($row['gross'])
        || !is_numeric($row['regular_hours'])
        || !is_numeric($row['overtime_hours'])
      ) {
        // Skip bad rows; caller can inspect logs
        continue;
      }

      $date = self::scalarString($row['date'] ?? $row['id'] ?? '');

      $g = self::numericFloat($row['gross']);
      $r = self::numericFloat($row['regular_hours']);
      $o = self::numericFloat($row['overtime_hours']);

      $cumulativeGrossEarnings += $g;
      $cumulativeRegularHours += $r;
      $cumulativeOvertimeHours += $o;

      fputcsv($fileHandle, [
        $date,
        number_format($g, 2, '.', ''),
        number_format($r, 2, '.', ''),
        number_format($o, 2, '.', ''),
        number_format($cumulativeGrossEarnings, 2, '.', ''),
        number_format($cumulativeRegularHours, 2, '.', ''),
        number_format($cumulativeOvertimeHours, 2, '.', ''),
      ]);
    }

    // Final totals row
    $totals = $this->getWorkTotalsForRange($start, $end);
    fputcsv($fileHandle, []); // blank line
    fputcsv($fileHandle, [
      'TOTALS',
      number_format($totals['grossIncome'], 2, '.', ''),
      number_format($totals['regularHours'], 2, '.', ''),
      number_format($totals['overtimeHours'], 2, '.', ''),
      '',
      '',
      '',
    ]);

    rewind($fileHandle);
    $csv = stream_get_contents($fileHandle);
    fclose($fileHandle);

    return (string) $csv;
  }

  /**
   * Generate a rendered HTML snapshot for Year-To-Date earnings summary.
   * Calculates totals (gross, income tax, EI, CPP, OAS), hours, and net,
   * then renders the 'earnings-year-to-date' template.
   *
   * @param null|int $year optional year, defaults to current year
   *
   * @return string rendered HTML summary
   */
  public function renderYearToDateSummary(?int $year = null, string $mode = 'auto'): string
  {
    $year ??= (int) date('Y');
    $normalizedMode = self::normalizeYtdRenderMode($mode);
    $render = $this->buildYearToDatePayload($year);

    if ($normalizedMode === 'basic') {
      return $this->renderYearToDateBasicFromPayload($year, $render);
    }

    if ($normalizedMode === 'override') {
      return $this->renderYearToDateOverrideFromPayload($year, $render);
    }

    $hookRendered = EarningsYtdExtensionBridge::renderFromHookBusAuto($year, $render);
    if (is_string($hookRendered) && trim($hookRendered) !== '') {
      return $hookRendered;
    }

    return Render::template('earnings-year-to-date', $render);
  }

  /**
   * Handles renderYearToDateSummaryCompare operation.
   */
  public function renderYearToDateSummaryCompare(?int $year = null): string
  {
    $year ??= (int) date('Y');
    $render = $this->buildYearToDatePayload($year);

    $basicHtml = $this->renderYearToDateBasicFromPayload($year, $render);
    $overrideHtml = $this->renderYearToDateOverrideFromPayload($year, $render);

    $title = htmlspecialchars(self::batchI18n('YEAR_TO_DATE') . ' Extension Compare', ENT_QUOTES, 'UTF-8');

    return '<section class="earnings_ext_compare" data-earnings-ext-compare="ytd">'
      . '<p class="earnings_ext_compare_notice">' . $title . ' (admin)</p>'
      . '<div class="earnings_ext_compare_grid">'
      . '<article class="earnings_ext_compare_panel">'
      . '<h3 class="earnings_ext_compare_title">Basic</h3>'
      . $basicHtml
      . '</article>'
      . '<article class="earnings_ext_compare_panel">'
      . '<h3 class="earnings_ext_compare_title">Override</h3>'
      . $overrideHtml
      . '</article>'
      . '</div>'
      . '</section>';
  }

  /**
   * @return array<string, string>
   */
  private function buildYearToDatePayload(int $year): array
  {
    $start = new \DateTimeImmutable("{$year}-01-01");
    $end = new \DateTimeImmutable("{$year}-12-31");

    $totals = $this->getWorkTotalsForRange($start, $end);
    $grossIncomeCents = $totals['grossIncomeCents'];
    $regularHours = $totals['regularHours'];
    $overtimeHours = $totals['overtimeHours'];

    $tax = new Taxes('Alberta', $year);
    $t = $tax->calculateTaxesCents($grossIncomeCents);

    $netCents = $grossIncomeCents - $t['totalDeductions'];

    return [
      '__HOURS__'            => self::batchI18n('HOURS'),
      '__EARNINGS_YTD_ID__'  => "{$year}-YTD",
      '__EARNINGS_YTD_ARIA_LABEL__' => self::batchI18n('EARNINGS_YTD_ARIA_LABEL'),
      '__EARNINGS_METRIC__' => self::batchI18n('EARNINGS_METRIC'),
      '__EARNINGS_VALUE__' => self::batchI18n('EARNINGS_VALUE'),
      '__REGULAR__' => self::batchI18n('REGULAR'),
      '__OVERTIME__' => self::batchI18n('OVERTIME'),
      '__GROSS_LABEL__' => self::batchI18n('GROSS'),
      '__FEDERAL_TAX_LABEL__' => self::batchI18n('FEDERAL_TAX'),
      '__PROVINCIAL_TAX_LABEL__' => self::batchI18n('PROVINCIAL_TAX'),
      '__EARNINGS_TOTAL_TAX__' => self::batchI18n('EARNINGS_TOTAL_TAX'),
      '__EARNINGS_EI__' => self::batchI18n('EARNINGS_EI'),
      '__EARNINGS_CPP__' => self::batchI18n('EARNINGS_CPP'),
      '__EARNINGS_OAS__' => self::batchI18n('EARNINGS_OAS'),
      '__EARNINGS_TOTAL_DEDUCTIONS__' => self::batchI18n('EARNINGS_TOTAL_DEDUCTIONS'),
      '__NET_LABEL__' => self::batchI18n('NET'),
      '__GROSS__'            => self::formatNumberLocalized($grossIncomeCents / 100, 2),
      '__FEDERAL_TAX__'      => self::formatNumberLocalized($t['federal'] / 100, 2),
      '__PROVINCIAL_TAX__'   => self::formatNumberLocalized($t['provincial'] / 100, 2),
      '__TOTAL_TAX__'        => self::formatNumberLocalized($t['incomeTax'] / 100, 2),              // income tax only
      '__EI__'               => self::formatNumberLocalized($t['employment_insurance'] / 100, 2),
      '__CPP__'              => self::formatNumberLocalized($t['canada_pension_plan'] / 100, 2),
      '__OAS__'              => self::formatNumberLocalized($t['old_age_security'] / 100, 2),
      '__TOTAL_DEDUCTIONS__' => self::formatNumberLocalized($t['totalDeductions'] / 100, 2),
      '__NET__'              => self::formatNumberLocalized($netCents / 100, 2),
      '__REGULAR_HOURS__'    => self::formatNumberLocalized($regularHours, 2),
      '__OVERTIME_HOURS__'   => self::formatNumberLocalized($overtimeHours, 2),
    ];
  }

  /**
   * @param array<string, string> $render
   */
  private function renderYearToDateBasicFromPayload(int $year, array $render): string
  {
    $extensionRendered = EarningsYtdExtensionBridge::renderWithMode($year, $render, 'basic');
    if (is_string($extensionRendered) && trim($extensionRendered) !== '') {
      return $extensionRendered;
    }

    return Render::template('earnings-year-to-date', $render);
  }

  /**
   * @param array<string, string> $render
   */
  private function renderYearToDateOverrideFromPayload(int $year, array $render): string
  {
    $extensionRendered = EarningsYtdExtensionBridge::renderWithMode($year, $render, 'override');
    if (is_string($extensionRendered) && trim($extensionRendered) !== '') {
      return $extensionRendered;
    }

    return Render::template('earnings-year-to-date', $render);
  }

  /**
   * Handles normalizeYtdRenderMode operation.
   */
  private static function normalizeYtdRenderMode(string $mode): string
  {
    $normalized = strtolower(trim($mode));
    return in_array($normalized, ['auto', 'basic', 'override'], true)
      ? $normalized
      : 'auto';
  }

  /**
   * Render a 12-month horizontal strip of earnings data for a given year.
   * Each month is rendered via earnings-month and assembled into
   * earnings-monthly-viewstrip.
   *
   * @param int $year The year to render (e.g., 2025).
   *
   * @return string rendered HTML of the monthly earnings strip≈
   */
  public function renderMonthlyViewStrip(int $year): string
  {
    $monthLabel = htmlspecialchars(self::batchI18n('EARNINGS_MONTH'), ENT_QUOTES, 'UTF-8');
    $grossLabel = htmlspecialchars(self::batchI18n('GROSS'), ENT_QUOTES, 'UTF-8');
    $deductionsLabel = htmlspecialchars(self::batchI18n('EARNINGS_TOTAL_DEDUCTIONS'), ENT_QUOTES, 'UTF-8');
    $netLabel = htmlspecialchars(self::batchI18n('NET'), ENT_QUOTES, 'UTF-8');
    $ariaLabel = htmlspecialchars(self::batchI18n('EARNINGS_MONTHLY_ARIA_PREFIX') . ' ' . $year, ENT_QUOTES, 'UTF-8');

    $rowsHtml = '';
    for ($month = 1; $month <= 12; ++$month) {
      $startDate = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
      $endDate = (clone $startDate)->modify('last day of this month');
      $totals = self::getTotalsForRange($startDate, $endDate, User::currentUUID());

      $monthName = htmlspecialchars((string) date('M', (int) mktime(0, 0, 0, $month, 1)), ENT_QUOTES, 'UTF-8');
      $gross = htmlspecialchars(self::formatNumberLocalized($totals['totals']['grossCents'] / 100, 2), ENT_QUOTES, 'UTF-8');
      $deductions = htmlspecialchars(self::formatNumberLocalized($totals['totals']['taxCents'] / 100, 2), ENT_QUOTES, 'UTF-8');
      $net = htmlspecialchars(self::formatNumberLocalized($totals['totals']['netCents'] / 100, 2), ENT_QUOTES, 'UTF-8');

      $rowsHtml .= '<div class="datagrid_row" role="row"><div class="datagrid_row_content" role="presentation">'
        . '<div class="datagrid_item" role="gridcell">' . $monthName . '</div>'
        . '<div class="datagrid_item" role="gridcell">$' . $gross . '</div>'
        . '<div class="datagrid_item" role="gridcell">$' . $deductions . '</div>'
        . '<div class="datagrid_item" role="gridcell">$' . $net . '</div>'
        . '</div></div>';
    }

    $coreHtml = '<div class="datagrid datagrid_cols_4 datagrid_layout_auto earnings_monthly_datagrid" data-grid="earnings-monthly-' . $year . '" data-page="1" role="region" aria-label="' . $ariaLabel . '">'
      . '<div class="datagrid_table" role="grid" aria-colcount="4" aria-rowcount="12">'
      . '<div class="datagrid_header_row" role="rowgroup"><div class="datagrid_header_content" role="row">'
      . '<div class="datagrid_heading" role="columnheader">' . $monthLabel . '</div>'
      . '<div class="datagrid_heading" role="columnheader">' . $grossLabel . '</div>'
      . '<div class="datagrid_heading" role="columnheader">' . $deductionsLabel . '</div>'
      . '<div class="datagrid_heading" role="columnheader">' . $netLabel . '</div>'
      . '</div></div>'
      . '<div class="datagrid_body" role="rowgroup">' . $rowsHtml . '</div>'
      . '</div>'
      . '</div>';

    $privateRendered = EarningsMonthlyExtensionBridge::render($year, $coreHtml);
    if (is_string($privateRendered) && trim($privateRendered) !== '') {
      return $privateRendered;
    }

    return $coreHtml;
  }

  /**
   * Render a detailed daily breakdown of earnings for the specified year,
   * using cumulative (YTD) tax logic to calculate daily deductions and net.
   *
   * @param int $year Year to render (e.g., 2024)
   *
   * @return string Rendered HTML of daily transactions
   */
  public function renderDailyView(int $year): string
  {
    $startDate = new \DateTimeImmutable("{$year}-01-01");
    $endDate = new \DateTimeImmutable("{$year}-12-31");
    $tax = new Taxes('Alberta', $year);
    $previousGrossEarnings = $previousFederalTax = $previousProvincialTax = 0.0;
    $previousEmploymentInsurance = $previousCanadaPension = $previousOldAgeSecurity = 0.0;
    $dailyRow = $dailyRows = [];
    $data = iterator_to_array(Work::getInstance()
      ->getWorkInRange($startDate, $endDate->modify('+1 day')));

    if (!$data) {
      return "<div>No data available for {$year}</div>";
    }

    ksort($data);
    foreach ($data as $key => $earnings) {
      $resolved = self::resolveWorkRow($earnings, User::currentUUID());
      if (is_array($resolved)) {
        $earnings = $resolved;
      }
      $date                     = self::scalarString($earnings['date'] ?? '');
      $label                    = date('l, F jS, Y', (int) strtotime($date));
      $grossCumulative          = $previousGrossEarnings + self::numericFloat($earnings['gross'] ?? 0);
      $taxes                    = $tax->calculateTaxesCents((int)$grossCumulative);
      $federalTax               = (float) Money::centsToDollars($taxes["federal"]);
      $provincialTax            = (float) Money::centsToDollars($taxes["provincial"]);
      $employmentInsurance      = (float) Money::centsToDollars($taxes["employment_insurance"]);
      $canadaPensionPlan        = (float) Money::centsToDollars($taxes["canada_pension_plan"]);
      $oldAgeSecurity           = (float) Money::centsToDollars($taxes["old_age_security"]);
      $dailyFederalTax          = $federalTax - $previousFederalTax;
      $dailyProvincialTax       = $provincialTax - $previousProvincialTax;
      $dailyEmploymentInsurance = $employmentInsurance - $previousEmploymentInsurance;
      $dailyCanadaPensionPlan   = $canadaPensionPlan - $previousCanadaPension;
      $dailyOldAgeSecurity      = $oldAgeSecurity - $previousOldAgeSecurity;
      $dailyDeductions          = $dailyFederalTax + $dailyProvincialTax + $dailyEmploymentInsurance + $dailyCanadaPensionPlan + $dailyOldAgeSecurity;
      $netDay                   = self::numericFloat($earnings['gross'] ?? 0) - $dailyDeductions;

      $dailyRow = [
        '__DATE__'           => $label,
        '__HOURS__'          => self::formatNumberLocalized(self::numericFloat($earnings['hours'] ?? 0), 2),
        '__REGULAR_HOURS__'  => self::formatNumberLocalized(self::numericFloat($earnings['regular_hours'] ?? 0), 2),
        '__OVERTIME_HOURS__' => self::formatNumberLocalized(self::numericFloat($earnings['overtime_hours'] ?? 0), 2),
        '__GROSS__'          => self::formatNumberLocalized(self::numericFloat($earnings['gross'] ?? 0), 2),
        '__DEDUCTIONS__'     => self::formatNumberLocalized((float) $dailyDeductions, 2),
        '__NET__'            => self::formatNumberLocalized((float) $netDay, 2),
      ];

      $dailyRows[] = Render::template('earnings-daily-row', $dailyRow);

      // Update previous cumulative totals
      $previousGrossEarnings       = $grossCumulative;
      $previousFederalTax          = $federalTax;
      $previousProvincialTax       = $provincialTax;
      $previousEmploymentInsurance = $employmentInsurance;
      $previousCanadaPension       = $canadaPensionPlan;
      $previousOldAgeSecurity      = $oldAgeSecurity;
    }

    $dailyHeader = [
      '__I_DATE__'           => self::batchI18n('DATE'),
      '__I_HOURS__'          => self::batchI18n('HOURS'),
      '__I_REGULAR_HOURS__'  => self::batchI18n('REGULAR_HOURS'),
      '__I_OVERTIME_HOURS__' => self::batchI18n('OVERTIME_HOURS'),
      '__I_GROSS__'          => self::batchI18n('GROSS'),
      '__I_DEDUCTIONS__'     => self::batchI18n('DEDUCTIONS'),
      '__I_NET__'            => self::batchI18n('NET'),
    ];

    return Render::template(
      'earnings-daily-view',
      $dailyHeader + ['__DAILY_ROWS__' => implode("\n", $dailyRows)]
    );
  }

  /**
   * Render private historical intelligence summary for one earnings year.
   */
  private function renderHistoricalIntelligence(int $year): string
  {
    $payload = $this->buildHistoricalIntelligencePayload($year);
    $privateRendered = EarningsHistoricalIntelligenceBridge::render($year, $payload);
    if (is_string($privateRendered) && trim($privateRendered) !== '') {
      return $privateRendered;
    }

    return Render::template('earnings-historical-intelligence', $payload);
  }

  /**
   * Render private pie graphs panel for one earnings year.
   */
  private function renderPieGraphs(int $year): string
  {
    $payload = [
      'year' => $year,
      'panel_title' => 'Earnings Pie Graphs',
      'ytd_title' => 'YTD Composition',
      'monthly_title' => 'Monthly Composition',
      'month_label' => 'Month',
    ];

    $privateRendered = EarningsPieGraphsExtensionBridge::render($year, $payload);
    if (is_string($privateRendered) && trim($privateRendered) !== '') {
      return $privateRendered;
    }

    return '';
  }

  /**
   * @return array<string, string>
   */
  private function buildHistoricalIntelligencePayload(int $year): array
  {
    $availableYears = iterator_to_array(Work::getInstance()->getAvailableYears(User::currentUUID()));
    $normalizedYears = array_values(array_unique(array_map(static fn (mixed $y): int => (int) $y, $availableYears)));
    sort($normalizedYears);
    $normalizedYears = array_values(array_filter($normalizedYears, static fn (int $y): bool => $y <= $year));

    if ($normalizedYears === []) {
      $normalizedYears = [$year];
    }

    $grossByYear = [];
    foreach ($normalizedYears as $candidateYear) {
      $start = new \DateTimeImmutable(sprintf('%04d-01-01', $candidateYear));
      $end = new \DateTimeImmutable(sprintf('%04d-12-31', $candidateYear));
      $totals = self::getTotalsForRange($start, $end);
      $grossByYear[$candidateYear] = (int) $totals['totals']['grossCents'];
    }

    $currentGrossCents = (int) ($grossByYear[$year] ?? 0);
    $priorGrossCents = (int) ($grossByYear[$year - 1] ?? 0);
    $yoyPercent = null;
    if ($priorGrossCents > 0) {
      $yoyPercent = (($currentGrossCents - $priorGrossCents) / $priorGrossCents) * 100.0;
    }

    $trailingWindowYears = array_values(array_filter(
      [$year - 2, $year - 1, $year],
      static fn (int $candidate): bool => array_key_exists($candidate, $grossByYear)
    ));
    $trailingGross = array_map(static fn (int $candidate): int => (int) $grossByYear[$candidate], $trailingWindowYears);
    $trailingAverageCents = $trailingGross === []
      ? 0
      : (int) round(array_sum($trailingGross) / max(1, count($trailingGross)));

    $peakYear = $year;
    $peakGrossCents = $currentGrossCents;
    foreach ($grossByYear as $candidateYear => $candidateGrossCents) {
      if ($candidateGrossCents > $peakGrossCents) {
        $peakYear = (int) $candidateYear;
        $peakGrossCents = (int) $candidateGrossCents;
      }
    }

    $stabilityIndex = null;
    if (count($trailingGross) >= 2) {
      $maxGross = (float) max($trailingGross);
      $minGross = (float) min($trailingGross);
      if ($maxGross > 0.0) {
        $stabilityIndex = max(0.0, min(100.0, 100.0 - (($maxGross - $minGross) / $maxGross) * 100.0));
      }
    }

    $activeMonths = [];
    $rangeStart = new \DateTimeImmutable(sprintf('%04d-01-01', $year));
    $rangeEndExclusive = (new \DateTimeImmutable(sprintf('%04d-12-31', $year)))->modify('+1 day');
    foreach (Work::getInstance()->getWorkInRange($rangeStart, $rangeEndExclusive) as $row) {
      $resolved = self::resolveWorkRow($row, User::currentUUID());
      if (is_array($resolved)) {
        $row = $resolved;
      }
      $date = self::scalarString($row['date'] ?? '');
      if ($date === '' || strlen($date) < 7) {
        continue;
      }
      $monthKey = substr($date, 0, 7);
      $activeMonths[$monthKey] = true;
    }

    $regime = $trailingAverageCents > 0 && $currentGrossCents >= $trailingAverageCents
      ? 'Above trailing baseline'
      : 'Below trailing baseline';

    return [
      '__HI_ID__' => sprintf('earnings-hi-%d', $year),
      '__HI_ARIA_LABEL__' => sprintf('Historical intelligence for %d earnings', $year),
      '__HI_TITLE__' => 'Historical Intelligence',
      '__HI_SUBTITLE__' => sprintf('Private extension snapshot for %d', $year),
      '__HI_YEARS_OBSERVED__' => self::formatNumberLocalized(count($grossByYear), 0),
      '__HI_ACTIVE_MONTHS__' => self::formatNumberLocalized(count($activeMonths), 0),
      '__HI_TRAILING_BASELINE__' => self::formatCurrencyCentsLocalized($trailingAverageCents),
      '__HI_YOY_SIGNAL__' => $yoyPercent === null ? 'n/a' : self::formatSignedPercentLocalized($yoyPercent, 1),
      '__HI_REGIME__' => $regime,
      '__HI_PEAK_YEAR__' => (string) $peakYear,
      '__HI_PEAK_GROSS__' => self::formatCurrencyCentsLocalized($peakGrossCents),
      '__HI_STABILITY_INDEX__' => $stabilityIndex === null ? 'n/a' : self::formatNumberLocalized($stabilityIndex, 1) . ' / 100',
      '__HI_NOTE__' => 'Signals derive from available yearly earnings history and should be used as directional guidance.',
    ];
  }

  /**
   * Renders the earnings sections HTML.
   */
  public function renderSections(string $renderMode = 'lazy'): string
  {
    $lazyMode = strtolower($renderMode) !== 'eager';
    $compareRequested = User::isAdmin() && InputSanitizer::getString('ext_compare') === 'earnings-ytd';
    $requestedModeRaw = InputSanitizer::getString('ext_mode') ?? 'auto';
    $requestedMode = self::normalizeYtdRenderMode($requestedModeRaw);
    $years = iterator_to_array(Work::getInstance()->getAvailableYears(User::currentUUID()));
    // Reverse year order: older years on left, newer on right
    $years = array_reverse($years);

    $tabs = "<ul class='tabs' role='tablist'>\n";
    $contents = "<section class='f_column w100 tab-content'>\n";

    foreach ($years as $i => $year) {
      $currentYear   = (int)date('Y');
      $active        = $currentYear === (int)$year ? ' active' : '';
      $isActive      = $currentYear === (int)$year;
      $ariaSelected  = $isActive ? "aria-selected='true'" : "aria-selected='false'";
      $tabIndex      = $isActive ? "0" : "-1";
      $tabs         .= "<li data-tab-target='tab-{$year}' class='tab{$active}' role='tab' {$ariaSelected} tabindex='{$tabIndex}'>{$year}</li>\n";

      $yearToDate    = self::batchI18n('YEAR_TO_DATE');
      $payPeriods = self::batchI18n('PAY_PERIODS');
      $cSV           = self::batchI18n('CSV');
      $tXT           = self::batchI18n('TXT');
      $pDF           = self::batchI18n('PDF');
      $xLSX          = 'XLSX';
      $monthly       = self::batchI18n('MONTHLY');
      $daily         = self::batchI18n('DAILY');
      $earningsAriaLabel = self::batchI18n('EARNINGS_LABEL') . ' ' . $year;
      $earningsTrend = self::batchI18n('EARNINGS_TREND');
      $yearToDateExportAria = self::batchI18n('EARNINGS_YEAR_TO_DATE_EXPORT_FORMATS') . ' ' . self::batchI18n('FOR') . ' ' . $year;
      $monthlyExportAria = self::batchI18n('EARNINGS_MONTHLY_EXPORT_FORMATS') . ' ' . self::batchI18n('FOR') . ' ' . $year;
      $dailyExportAria = self::batchI18n('EARNINGS_DAILY_EXPORT_FORMATS') . ' ' . self::batchI18n('FOR') . ' ' . $year;
      $lineGraphTitle = self::batchI18n('EARNINGS_TREND_CHART_FOR') . ' ' . $year;
      $lineGraphDesc = self::batchI18n('EARNINGS_TREND_CHART_DESC') . ' ' . $year . '.';
      $lineGraphStatus = self::batchI18n('EARNINGS_TREND_CHART_LOADING_FOR') . ' ' . $year . '.';
      $dailyGridInstructions = self::batchI18n('EARNINGS_DAILY_GRID_INSTRUCTIONS_FOR') . ' ' . $year . '. ' . self::batchI18n('EARNINGS_DAILY_GRID_INSTRUCTIONS_SUFFIX');
      $dailyGridContext = self::batchI18n('EARNINGS_DAILY_GRID_CONTEXT_FOR') . ' ' . $year . '. ' . self::batchI18n('EARNINGS_DAILY_GRID_CONTEXT_SUFFIX');
      $loadingYtdSummary = self::batchI18n('EARNINGS_LOADING_YEAR_TO_DATE_SUMMARY');
      $loadingPayPeriods = self::batchI18n('EARNINGS_LOADING_PAY_PERIODS');
      $loadingMonthlySummary = self::batchI18n('EARNINGS_LOADING_MONTHLY_SUMMARY');
      $trendHelp = htmlspecialchars($earningsTrend . ' ' . self::batchI18n('FOR') . ' ' . $year . '.', ENT_QUOTES, 'UTF-8');
      $historicalHelp = htmlspecialchars('Historical Intelligence ' . self::batchI18n('FOR') . ' ' . $year . '.', ENT_QUOTES, 'UTF-8');
      $yearToDateHelp = htmlspecialchars($yearToDate . ' ' . self::batchI18n('FOR') . ' ' . $year . '.', ENT_QUOTES, 'UTF-8');
      $payPeriodsHelp = htmlspecialchars($payPeriods . ' ' . self::batchI18n('FOR') . ' ' . $year . '.', ENT_QUOTES, 'UTF-8');
      $monthlyHelp = htmlspecialchars($monthly . ' ' . self::batchI18n('FOR') . ' ' . $year . '.', ENT_QUOTES, 'UTF-8');
      $dailyHelp = htmlspecialchars($daily . ' ' . self::batchI18n('FOR') . ' ' . $year . '.', ENT_QUOTES, 'UTF-8');

      $activeClass = $active ? ' active' : '';
      $eagerYtdHtml = $compareRequested
        ? $this->renderYearToDateSummaryCompare((int) $year)
        : $this->renderYearToDateSummary((int) $year, $requestedMode);
      $renderYearInline = !$lazyMode || $isActive;

      $yearToDateHtml = $renderYearInline
        ? '<div id="earnings_ytd_' . $year . '" class="earnings_async_slot" data-earnings-slot="ytd" data-earnings-year="' . $year . '">' . $eagerYtdHtml . '</div>'
        : '<div id="earnings_ytd_' . $year . '" class="earnings_async_slot" data-earnings-slot="ytd" data-earnings-year="' . $year . '"><p class="earnings_async_status">' . $loadingYtdSummary . '</p></div>';

      $payPeriodsHtml = $renderYearInline
        ? '<div id="earnings_pay_periods_' . $year . '" class="earnings_async_slot" data-earnings-slot="payperiods" data-earnings-year="' . $year . '">' . $this->renderPayPeriodComparison((int) $year) . '</div>'
        : '<div id="earnings_pay_periods_' . $year . '" class="earnings_async_slot" data-earnings-slot="payperiods" data-earnings-year="' . $year . '"><p class="earnings_async_status">' . $loadingPayPeriods . '</p></div>';

      $monthlyHtml = $renderYearInline
        ? '<div id="earnings_monthly_' . $year . '" class="earnings_async_slot" data-earnings-slot="monthly" data-earnings-year="' . $year . '">' . $this->renderMonthlyViewStrip((int) $year) . '</div>'
        : '<div id="earnings_monthly_' . $year . '" class="earnings_async_slot" data-earnings-slot="monthly" data-earnings-year="' . $year . '"><p class="earnings_async_status">' . $loadingMonthlySummary . '</p></div>';

      $historicalIntelligenceHtml = $this->renderHistoricalIntelligence((int) $year);
      $pieGraphsHtml = $this->renderPieGraphs((int) $year);

      $contents .= <<<HTML
<div id="tab-{$year}" data-tab-content="tab-{$year}" class="f_column{$activeClass}" aria-label="{$earningsAriaLabel}">
  <section class="panel w100 earnings_panel" data-hover-help="{$trendHelp}">
    <h2 class="earnings_panel_title">{$earningsTrend}</h2>
    <div class="earnings-graph-container">
      <div class="visually_hidden">
        <p id="earnings_line_graph_{$year}_title">{$lineGraphTitle}</p>
        <p id="earnings_line_graph_{$year}_desc">{$lineGraphDesc}</p>
        <p id="earnings_line_graph_{$year}_status" role="status" aria-live="polite" aria-atomic="true">{$lineGraphStatus}</p>
      </div>
      <svg id="earnings_line_graph_{$year}" width="100%" height="300" role="img" aria-labelledby="earnings_line_graph_{$year}_title" aria-describedby="earnings_line_graph_{$year}_desc earnings_line_graph_{$year}_status"></svg>
    </div>
  </section>

  <section class="panel w100 earnings_panel" data-hover-help="{$historicalHelp}">
    {$historicalIntelligenceHtml}
  </section>

  {$pieGraphsHtml}

  <section class="panel w100 earnings_panel" data-hover-help="{$yearToDateHelp}">
    <h2 class="earnings_panel_title">{$yearToDate}</h2>
    <div class="earnings_export_actions" role="group" aria-label="{$yearToDateExportAria}">
      <button type="button" class="paycal_export_btn" data-export-scope="yearly" data-export-format="csv" data-export-year="{$year}">{$cSV}</button> &sdot;
      <button type="button" class="paycal_export_btn" data-export-scope="yearly" data-export-format="xlsx" data-export-year="{$year}">{$xLSX}</button> &sdot;
      <button type="button" class="paycal_export_btn" data-export-scope="yearly" data-export-format="txt" data-export-year="{$year}">{$tXT}</button> &sdot;
      <button type="button" class="paycal_export_btn" data-export-scope="yearly" data-export-format="pdf" data-export-year="{$year}">{$pDF}</button>
    </div>
    {$yearToDateHtml}
  </section>

  <section class="panel w100 earnings_panel" data-hover-help="{$payPeriodsHelp}">
    <h2 class="earnings_panel_title">{$payPeriods}</h2>
    {$payPeriodsHtml}
  </section>

  <section class="panel w100 earnings_panel" data-hover-help="{$monthlyHelp}">
    <h2 class="earnings_panel_title">{$monthly}</h2>
    <div class="earnings_export_actions" role="group" aria-label="{$monthlyExportAria}">
      <button type="button" class="paycal_export_btn" data-export-scope="monthly" data-export-format="csv" data-export-year="{$year}">{$cSV}</button> &sdot;
      <button type="button" class="paycal_export_btn" data-export-scope="monthly" data-export-format="xlsx" data-export-year="{$year}">{$xLSX}</button> &sdot;
      <button type="button" class="paycal_export_btn" data-export-scope="monthly" data-export-format="txt" data-export-year="{$year}">{$tXT}</button> &sdot;
      <button type="button" class="paycal_export_btn" data-export-scope="monthly" data-export-format="pdf" data-export-year="{$year}">{$pDF}</button>
    </div>
    {$monthlyHtml}
  </section>

  <section class="panel w100 earnings_panel" data-hover-help="{$dailyHelp}">
    <h2 class="earnings_panel_title">{$daily}</h2>
    <div class="earnings_export_actions" role="group" aria-label="{$dailyExportAria}">
      <button type="button" class="paycal_export_btn" data-export-scope="daily" data-export-format="csv" data-export-year="{$year}">{$cSV}</button> &sdot;
      <button type="button" class="paycal_export_btn" data-export-scope="daily" data-export-format="xlsx" data-export-year="{$year}">{$xLSX}</button> &sdot;
      <button type="button" class="paycal_export_btn" data-export-scope="daily" data-export-format="txt" data-export-year="{$year}">{$tXT}</button> &sdot;
      <button type="button" class="paycal_export_btn" data-export-scope="daily" data-export-format="pdf" data-export-year="{$year}">{$pDF}</button>
    </div>
    <div class="visually_hidden">
      <p id="daily_earnings_{$year}_sr_instructions">{$dailyGridInstructions}</p>
      <p id="daily_earnings_{$year}_sr_context">{$dailyGridContext}</p>
      <p id="daily_earnings_{$year}_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
    </div>
    <div id="daily_earnings_{$year}" class="daily-earnings-section"></div>
  </section>
</div>
HTML;
    }

    $tabs .= "</ul>\n";
    $contents .= "</section>\n";

    $mode = $lazyMode ? 'lazy' : 'eager';

    return "<section class=\"w100\" data-earnings-mode=\"{$mode}\">{$tabs}</section>{$contents}";
  }

  /**
   * Aggregate earnings totals for a calendar date range [start..endInclusive].
   * Uses exclusive end internally to query full days.
   * @param \DateTimeInterface $start        Inclusive start (local midnight honored)
   * @param \DateTimeInterface $endInclusive Inclusive end (local midnight honored)
   * @param string            $userUUID     User identifier; defaults to USER_UUID
   * @return array{
   *   range: array{start:string,end:string},
   *   days:int,
   *   hours:array{regular:float,overtime:float,travel:float,total:float},
  *   amounts:array{loa:string,wage:string,other:string},
   *   payRate:array{deviates:bool,expected:?string,min:?string,avg:?string,max:?string},
  *   sites:list<string>,
   *   totals:array{gross:string,tax:string,net:string,grossCents:int,taxCents:int,netCents:int},
   *   deductions:array{tax:string}
	 * }
   */
  public static function getTotalsForRange(
    \DateTimeInterface $start,
    \DateTimeInterface $endInclusive,
    ?string $userUUID = null
  ): array {
    if ($userUUID === null) {
      $userUUID = User::currentUUID();
    }
    $tzName = $start->getTimezone()->getName();
    $tz = new \DateTimeZone($tzName);

    $s = (new \DateTimeImmutable($start->format('Y-m-d'), $tz))->setTime(0, 0, 0);
    $eInc = (new \DateTimeImmutable($endInclusive->format('Y-m-d'), $tz))->setTime(0, 0, 0);
    $eExc = $eInc->modify('+1 day');

    // Pull work entries for [s, eExc)
    // Expected entry shape (best-effort): r=regular hrs, o=overtime hrs, h=total hrs,
    // t=travel hrs, l=LOA amount, w=hourly wage, g=gross, tax=taxes, n=net, other=other adj.
    $entries = Work::getWorkInRange($s, $eExc, $userUUID);

    // Hours remain as float (time, not money)
    $regularHours  = 0.0;
    $overtimeHours   = 0.0;
    $travelHours = 0.0;
    // Monetary amounts as integer cents (drift-free)
    $livingOutAllowanceCents   = 0;
    $wageCents  = 0;
    $otherCents = 0;
    $grossCents = 0;
    $taxesCents = 0;
    $netCents   = 0;
    /** @var array<int, Taxes> $taxByYear */
    $taxByYear = [];

    $expectedPayRate = self::resolveExpectedPayRate(User::current());
    $observedMinRate = null;
    $observedMaxRate = null;
    $rateWeightedSum = 0.0;
    $rateWeightTotal = 0.0;
    /** @var array<string, true> $siteNames */
    $siteNames = [];

    foreach ($entries as $row) {
      $resolved = self::resolveWorkRow($row, $userUUID);
      if (is_array($resolved)) {
        $row = $resolved;
      }

      // Hours (float is acceptable for time)
      $r = self::numericFloat($row['regular_hours'] ?? $row['r'] ?? 0.0);
      $o = self::numericFloat($row['overtime_hours'] ?? $row['o'] ?? 0.0);
      $t = self::numericFloat($row['travel_hours'] ?? $row['t'] ?? 0.0);
      $regularHours += $r;
      $overtimeHours += $o;
      $travelHours += $t;

      // Monetary fields - convert to cents immediately
      $g  = self::scalarString($row['gross'] ?? $row['g'] ?? '0', '0');
      $l  = self::scalarString($row['living_out_allowance'] ?? $row['l'] ?? '0', '0');
      $tx = self::scalarString($row['tax'] ?? $row['tx'] ?? '0', '0');
      $n  = self::scalarString($row['net'] ?? '0', '0');

      // Try to compute wage amount if not explicitly present
      // Prefer explicit gross when provided; fall back to simple wage calc
      $resolvedWageRate = self::resolveEntryWageRate($row, $userUUID);
      $computedWageCents = 0;
      if ($resolvedWageRate !== null && $resolvedWageRate > 0.0) {
        $computedWageCents = Money::calculateGross($r, $o, (string) $resolvedWageRate);
        if ($t > 0.0) {
          $computedWageCents += Money::dollarsToCents((string) ($t * $resolvedWageRate));
        }
      }

      $rowGrossCents = 0;
      if ('0' !== $g && '' !== $g) {
        $rowGrossCents = Money::dollarsToCents($g);
        $grossCents += $rowGrossCents;
      } else {
        $rowGrossCents = $computedWageCents + Money::dollarsToCents($l);
        $grossCents += $rowGrossCents;
      }

      // Track wage and other components separately for reporting
      $wageCents += ($computedWageCents > 0) ? $computedWageCents : Money::dollarsToCents(self::scalarString($row['wage'] ?? $row['w'] ?? '0', '0'));
      $livingOutAllowanceCents += Money::dollarsToCents($l);
      $otherCents += Money::dollarsToCents(self::scalarString($row['other'] ?? '0', '0'));

      $entryWageRate = self::resolveEntryWageRate($row, $userUUID);
      $entryHours = $r + $o + $t;
      if ($entryWageRate !== null && $entryHours > 0.0) {
        $observedMinRate = ($observedMinRate === null) ? $entryWageRate : min($observedMinRate, $entryWageRate);
        $observedMaxRate = ($observedMaxRate === null) ? $entryWageRate : max($observedMaxRate, $entryWageRate);
        $rateWeightedSum += ($entryWageRate * $entryHours);
        $rateWeightTotal += $entryHours;
      }

      $entrySiteName = self::resolveEntrySiteName($row, $userUUID);
      if ($entrySiteName !== null) {
        $siteNames[$entrySiteName] = true;
      }

      // Taxes and net
      $rowTaxCents = Money::dollarsToCents($tx);
      if ($rowTaxCents <= 0 && $rowGrossCents > 0) {
        $rowDate = self::scalarString($row['date'] ?? $row['id'] ?? $s->format('Y-m-d'));
        $rowYear = (int) substr($rowDate, 0, 4);
        if (!isset($taxByYear[$rowYear])) {
          $taxByYear[$rowYear] = new Taxes('Alberta', $rowYear);
        }
        $rowTaxCents = (int) $taxByYear[$rowYear]->calculateTaxesCents($rowGrossCents)['totalDeductions'];
      }
      $taxesCents += $rowTaxCents;
      if ('0' !== $n && '' !== $n) {
        $netCents += Money::dollarsToCents($n);
      }
    }

    $observedAvgRate = ($rateWeightTotal > 0.0) ? ($rateWeightedSum / $rateWeightTotal) : null;
    $deviationTolerance = 0.01;
    $payRateDeviates = false;
    if ($expectedPayRate !== null) {
      if ($observedMinRate !== null && abs($observedMinRate - $expectedPayRate) > $deviationTolerance) {
        $payRateDeviates = true;
      }
      if ($observedMaxRate !== null && abs($observedMaxRate - $expectedPayRate) > $deviationTolerance) {
        $payRateDeviates = true;
      }
      if ($observedAvgRate !== null && abs($observedAvgRate - $expectedPayRate) > $deviationTolerance) {
        $payRateDeviates = true;
      }
    }

    // If net never provided, compute a reasonable default
    if (0 === $netCents) {
      $netCents = max(0, $grossCents - $taxesCents);
    }

    $days = (int) max(0, (int) (($eExc->getTimestamp() - $s->getTimestamp()) / 86400));

    return [
      'range' => [
        'start' => $s->format('Y-m-d'),
        'end' => $eInc->format('Y-m-d'),
      ],
      'days' => $days,
      'hours' => [
        'regular'  => (float) $regularHours,
        'overtime' => (float) $overtimeHours,
        'travel'   => (float) $travelHours,
        'total'    => (float) ($regularHours + $overtimeHours + $travelHours),
      ],
      'amounts' => [
        'loa'   => Money::centsToDollars($livingOutAllowanceCents),
        'wage'  => Money::centsToDollars($wageCents),
        'other' => Money::centsToDollars($otherCents),
      ],
      'payRate' => [
        'deviates' => $payRateDeviates,
        'expected' => $expectedPayRate !== null ? number_format($expectedPayRate, 2, '.', '') : null,
        'min' => $observedMinRate !== null ? number_format($observedMinRate, 2, '.', '') : null,
        'avg' => $observedAvgRate !== null ? number_format($observedAvgRate, 2, '.', '') : null,
        'max' => $observedMaxRate !== null ? number_format($observedMaxRate, 2, '.', '') : null,
      ],
      'sites' => array_keys($siteNames),
      'totals' => [
        'gross'      => Money::centsToDollars($grossCents),
        'tax'        => Money::centsToDollars($taxesCents),
        'net'        => Money::centsToDollars($netCents),
        'grossCents' => $grossCents,
        'taxCents'   => $taxesCents,
        'netCents'   => $netCents,
      ],
      'deductions' => [
        'tax' => Money::centsToDollars($taxesCents),
      ],
    ];
  }

  /**
   * Aggregate earnings totals for a given pay period.
   * Delegates to getTotalsForRange() using the period's start
   * and inclusive end dates.
   * @param PayPeriods $pp The pay period instance
   * @return array{
    *   range:array{start:string,end:string},
    *   days:int,
    *   hours:array{regular:float,overtime:float,travel:float,total:float},
    *   amounts:array{loa:string,wage:string,other:string},
    *   payRate:array{deviates:bool,expected:?string,min:?string,avg:?string,max:?string},
    *   sites:list<string>,
    *   totals:array{gross:string,tax:string,net:string,grossCents:int,taxCents:int,netCents:int},
    *   deductions:array{tax:string}
   * }
   */
  public static function getTotalsForPeriod(PayPeriods $pp): array
  {
    return self::getTotalsForRange($pp->start(), $pp->endInclusive());
  }

  /**
   * Render pay periods for a calendar year as cards, excluding periods with zero logged hours.
   * @return string HTML string with pay period cards
   */
  public function renderPayPeriodComparison(int $year): string
  {
    $yearStart = new \DateTimeImmutable(sprintf('%d-01-01', $year));
    $yearEnd = new \DateTimeImmutable(sprintf('%d-12-31', $year));

    $period = Calendar::getCurrentPayPeriods();
    while ($period->start() > $yearStart) {
      $previousPeriod = $period->previous();
      if ($previousPeriod->start() >= $period->start()) {
        break;
      }
      $period = $previousPeriod;
    }

    while ($period->endInclusive() < $yearStart) {
      $nextPeriod = $period->next();
      if ($nextPeriod->start() <= $period->start()) {
        break;
      }
      $period = $nextPeriod;
    }

    $cards = [];

    while ($period->start() <= $yearEnd || $period->endInclusive() <= $yearEnd) {
      $totals = self::getTotalsForPeriod($period);
      $hoursTotal = (float) $totals['hours']['total'];
      if (abs($hoursTotal) < 0.00001) {
        $nextPeriod = $period->next();
        if ($nextPeriod->start() <= $period->start()) {
          break;
        }
        $period = $nextPeriod;
        continue;
      }

      $cards[] = $this->renderPayPeriodCard($period, $totals);

      $nextPeriod = $period->next();
      if ($nextPeriod->start() <= $period->start()) {
        break;
      }
      if ($nextPeriod->start() > $yearEnd && $nextPeriod->endInclusive() > $yearEnd) {
        break;
      }
      $period = $nextPeriod;
    }

    if ($cards === []) {
      return '<p class="pay-period-empty">' . self::batchI18n('EARNINGS_NO_LOGGED_HOURS_IN_YEAR_PAY_PERIODS') . '</p>';
    }

    return '<div class="pay-period-cards" aria-label="' . self::batchI18n('EARNINGS_PAY_PERIODS_CAROUSEL_FOR') . ' ' . $year . '">' . implode('', $cards) . '</div>';
  }

  /**
   * Render a single pay period card.
   *
   * @param array{
   *   range:array{start:string,end:string},
   *   days:int,
   *   hours:array{regular:float,overtime:float,travel:float,total:float},
  *   payRate:array{deviates:bool,expected:?string,min:?string,avg:?string,max:?string},
  *   sites:list<string>,
   *   totals:array{gross:string,tax:string,net:string,grossCents:int,taxCents:int,netCents:int}
   * } $totals
   */
  private function renderPayPeriodCard(PayPeriods $pp, array $totals): string
  {
    $startDate = $pp->start()->format('M j, Y');
    $endDate = $pp->endInclusive()->format('M j, Y');
    $startDateIso = htmlspecialchars((string) $totals['range']['start'], ENT_QUOTES, 'UTF-8');
    $endDateIso = htmlspecialchars((string) $totals['range']['end'], ENT_QUOTES, 'UTF-8');

    $regularHours = self::formatNumberLocalized((float) $totals['hours']['regular'], 2);
    $overtimeHours = self::formatNumberLocalized((float) $totals['hours']['overtime'], 2);
    $travelHours = self::formatNumberLocalized((float) $totals['hours']['travel'], 2);
    $totalHours = self::formatNumberLocalized((float) $totals['hours']['total'], 2);

    $grossDisplay = self::formatCurrencyCentsLocalized((int) $totals['totals']['grossCents']);
    $taxDisplay = self::formatCurrencyCentsLocalized((int) $totals['totals']['taxCents']);
    $netDisplay = self::formatCurrencyCentsLocalized((int) $totals['totals']['netCents']);

    $payRateSummary = '';
    if ($totals['payRate']['deviates'] === true) {
      $expectedRaw = self::numericFloat($totals['payRate']['expected'] ?? 0);
      $minRateRaw = self::numericFloat($totals['payRate']['min'] ?? 0);
      $avgRateRaw = self::numericFloat($totals['payRate']['avg'] ?? 0);
      $maxRateRaw = self::numericFloat($totals['payRate']['max'] ?? 0);
      $expected = htmlspecialchars(self::formatNumberLocalized($expectedRaw, 2), ENT_QUOTES, 'UTF-8');
      $minRate = htmlspecialchars(self::formatNumberLocalized($minRateRaw, 2), ENT_QUOTES, 'UTF-8');
      $avgRate = htmlspecialchars(self::formatNumberLocalized($avgRateRaw, 2), ENT_QUOTES, 'UTF-8');
      $maxRate = htmlspecialchars(self::formatNumberLocalized($maxRateRaw, 2), ENT_QUOTES, 'UTF-8');
      $payRateSummary = "\n  <div class=\"pay-period-card_rates\">\n    <div class=\"pay-period-card_row\"><span class=\"pay-period-card_label\"><strong>Pay Rate Variance</strong></span><span class=\"pay-period-card_value\"></span></div>\n    <div class=\"pay-period-card_row\"><span class=\"pay-period-card_label\">Expected</span><span class=\"pay-period-card_value\">$" . $expected . "/h</span></div>\n    <div class=\"pay-period-card_row\"><span class=\"pay-period-card_label\">Min</span><span class=\"pay-period-card_value\">$" . $minRate . "/h</span></div>\n    <div class=\"pay-period-card_row\"><span class=\"pay-period-card_label\">Avg</span><span class=\"pay-period-card_value\">$" . $avgRate . "/h</span></div>\n    <div class=\"pay-period-card_row\"><span class=\"pay-period-card_label\">Max</span><span class=\"pay-period-card_value\">$" . $maxRate . "/h</span></div>\n  </div>";
    }

    $sitesSummary = '';
    if ($totals['sites'] !== []) {
      $siteItems = '';
      foreach ($totals['sites'] as $siteName) {
        $safeSiteName = htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');
        $siteItems .= "<li>{$safeSiteName}</li>";
      }
      $sitesSummary = "\n  <div class=\"pay-period-card_sites\">\n    <span><strong>Sites</strong></span>\n    <ul>{$siteItems}</ul>\n  </div>";
    }

    return <<<HTML
<article class="pay-period-card" aria-label="Pay period {$startDate} to {$endDate}">
  <h3 class="pay-period-card_title">{$startDate} - {$endDate}</h3>
  <div class="pay-period-card_exports" role="group" aria-label="Pay period export formats for {$startDate} to {$endDate}">
    <button type="button" class="paycal_export_btn" data-export-scope="payperiod" data-export-format="csv" data-export-start="{$startDateIso}" data-export-end="{$endDateIso}">CSV</button> &sdot;
    <button type="button" class="paycal_export_btn" data-export-scope="payperiod" data-export-format="xlsx" data-export-start="{$startDateIso}" data-export-end="{$endDateIso}">XLSX</button> &sdot;
    <button type="button" class="paycal_export_btn" data-export-scope="payperiod" data-export-format="txt" data-export-start="{$startDateIso}" data-export-end="{$endDateIso}">TXT</button> &sdot;
    <button type="button" class="paycal_export_btn" data-export-scope="payperiod" data-export-format="pdf" data-export-start="{$startDateIso}" data-export-end="{$endDateIso}">PDF</button>
  </div>
  <div class="pay-period-card_hours">
    <div class="pay-period-card_row"><span class="pay-period-card_label">Regular</span><span class="pay-period-card_value">{$regularHours}h</span></div>
    <div class="pay-period-card_row"><span class="pay-period-card_label">Overtime</span><span class="pay-period-card_value">{$overtimeHours}h</span></div>
    <div class="pay-period-card_row"><span class="pay-period-card_label">Travel</span><span class="pay-period-card_value">{$travelHours}h</span></div>
    <div class="pay-period-card_row"><span class="pay-period-card_label"><strong>Total</strong></span><span class="pay-period-card_value"><strong>{$totalHours}h</strong></span></div>
  </div>
  <div class="pay-period-card_totals">
    <div class="pay-period-card_row"><span class="pay-period-card_label">Gross</span><span class="pay-period-card_value">{$grossDisplay}</span></div>
    <div class="pay-period-card_row"><span class="pay-period-card_label">Tax</span><span class="pay-period-card_value">{$taxDisplay}</span></div>
    <div class="pay-period-card_row"><span class="pay-period-card_label"><strong>Net</strong></span><span class="pay-period-card_value"><strong>{$netDisplay}</strong></span></div>
  </div>
  {$sitesSummary}
  {$payRateSummary}
</article>
HTML;
  }

  /**
   * Renders the ASCII pay period progress bar widget.
   * @param null|PayPeriods $pp Pay period to render, defaults to current
   * @return string ASCII text with progress bar
   */
  public function renderAsciiPayPeriodProgress(?PayPeriods $pp = null): string
  {
    $pp      ??= Calendar::getCurrentPayPeriods();
    $startTs   = $pp->start()->getTimestamp();
    $endTs     = $pp->endExclusive()->getTimestamp();  // exclusive end
    $nowTs     = time();

    $totals      = self::getTotalsForPeriod($pp);
    $hoursLogged = $totals['hours']['total'];

    $user = User::current();

    // Calculate pay rate from logged work
    $payRate = $hoursLogged > 0 ? self::numericFloat($totals['totals']['gross']) / $hoursLogged : 0.0;

    // Count actual days with work logged
    $workEntries = Work::getWorkInPeriod($pp);
    $uniqueDays = [];
    foreach ($workEntries as $key => $data) {
      $dateStr = self::scalarString($data['date'] ?? explode(':', $key)[2] ?? '');
      if ($dateStr) {
        $uniqueDays[$dateStr] = true;
      }
    }
    $daysWorked = count($uniqueDays);

    // Compute elapsed pct
    $totalDuration = $endTs - $startTs;
    if ($totalDuration <= 0) {
      $elapsedPct = 1.0;
    } else {
      $elapsed    = max(0, $nowTs - $startTs);
      $elapsedPct = min(1.0, $elapsed / $totalDuration);
    }

    // Calculate expected hours per day based on actual logged work
    $expectedHoursPerDay = $daysWorked > 0 ? ($hoursLogged / $daysWorked) : (float) $user->default_hours;

    // Days remaining (calendar days)
    $daysRemaining = max(0, ($endTs - $nowTs) / 86400);

    // Predicted total hours based on actual work pattern
    $predictedTotalHours = $hoursLogged + ($daysRemaining * $expectedHoursPerDay);

    // Expected total hours for the full period (assuming all days worked at current average)
    $fullDays = $totalDuration / 86400;
    $expectedTotalHours = $fullDays * $expectedHoursPerDay;
    // Current logged hours as % of expected
    $loggedPct = $expectedTotalHours > 0 ? min(1.0, $hoursLogged / $expectedTotalHours) : 1.0;

    // Predicted earnings
    $predictedEarnings = $predictedTotalHours * $payRate;
    $currentEarned = $hoursLogged * $payRate;

    // Render ASCII bar
    $width = 20;
    $loggedChars = (int) floor($width * $loggedPct);

    $bar = '';
    for ($i = 0; $i < $width; ++$i) {
      if ($i < $loggedChars) {
        $bar .= '█';
      } else {
        $bar .= '░';
      }
    }
    $barStr = '[' . $bar . ']';

    // Format output
    $elapsedPctStr        = number_format($elapsedPct * 100, 0) . '%';
    $loggedPctStr         = number_format($loggedPct * 100, 0) . '%';
    $predictedEarningsStr = '$' . number_format($predictedEarnings, 2);

    $startDate = $pp->start()->format('Y-m-d');
    $endDate   = $pp->endInclusive()->format('Y-m-d');

    $hoursLabel      = str_pad((string) $hoursLogged, 3, ' ', STR_PAD_LEFT);
    $targetLabel     = str_pad((string) (int) $expectedTotalHours, 3, ' ', STR_PAD_LEFT);
    $currentPayLabel = str_pad('$' . number_format($currentEarned, 2), 10, ' ', STR_PAD_LEFT);

    $output  = "Hours:  {$hoursLabel} / {$targetLabel}  {$barStr}  {$loggedPctStr} on pace";
    $output .= "\nPay:    {$currentPayLabel} / {$predictedEarningsStr}  (projected)";

    $heading = "Pay Period: {$startDate} → {$endDate}";

    return "{$heading}\n\n{$output}";
  }
}


