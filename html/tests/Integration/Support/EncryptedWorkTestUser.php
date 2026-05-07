<?php declare(strict_types=1);

namespace Tests\Integration\Support;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;

/**
 * Integration-test fixture for work-data tests that require proper DEK encryption.
 *
 * Why this exists:
 * - EarningsController::getDaily() rejects work rows without an encrypted_blob after SOC 2
 *   hardening (portability commit a4272c96). All financial work data must be stored inside
 *   AES-256-GCM encrypted_blob; plaintext wage/gross/net fields are rejected at runtime.
 * - This fixture provisions a test user with a real in-memory DEK wrapped via HKDF/AES-256-GCM,
 *   so seedWorkRow() produces encrypted_blob entries that pass decryptWorkRowIfNeeded().
 * - Raw numeric fields are stored alongside encrypted_blob so calendar-path helpers
 *   (Work::getWorkInRange) can read metadata without decryption.
 *
 * Encryption chain (mirrors server-side logic exactly):
 *   salt (16 bytes, stored as base64 in user.encryption_salt)
 *   credential_id (arbitrary bytes, stable test value)
 *   KEK = hash_hkdf('sha256', credential_id, 32, 'paycal-passkey-kek', salt)
 *   DEK = random_bytes(32)  — kept in PHP memory only, never stored plaintext
 *   wrapped_dek = base64(JSON{ nonce, ciphertext=AES-256-GCM(KEK, DEK)+tag })
 *                 stored in: user:<uuid>:passkey_wrapped_deks hash, field = credential_id
 *   per-row blob = base64(JSON{ nonce, ciphertext=AES-256-GCM(DEK, payload_json)+tag, aad })
 *                  stored as encrypted_blob field on the work hash key
 */
final class EncryptedWorkTestUser
{
  public readonly string $userUUID;
  public readonly string $sessionHash;
  public readonly string $credentialId;

  /** @var array<string, string> */
  private array $originalCookie;
  /** @var array<string, string> */
  private array $originalServer;

  /** Raw 32-byte DEK (held in PHP memory only — never persisted in plaintext). */
  private string $dek;

  private function __construct(
    string $userUUID,
    string $sessionHash,
    string $credentialId,
    string $dek
  ) {
    $this->userUUID = $userUUID;
    $this->sessionHash = $sessionHash;
    $this->credentialId = $credentialId;
    $this->dek = $dek;
    $this->originalCookie = $_COOKIE ?? [];
    $this->originalServer = $_SERVER ?? [];
  }

  /**
   * Create a fresh fixture user with a randomly-generated DEK and wrapped credential.
   *
   * After this call:
   * - $_COOKIE['PAYCAL_AUTH'] = $sessionHash
   * - $_SERVER['REQUEST_METHOD'] = 'GET'
   * - Redis contains: user hash, session hash, passkey_wrapped_deks hash
   */
  public static function create(): self
  {
    $userUUID = 'U' . bin2hex(random_bytes(8));
    $sessionHash = hash('sha256', bin2hex(random_bytes(32)));
    $credentialId = 'tc-' . bin2hex(random_bytes(8));

    $salt = random_bytes(16);
    $saltB64 = base64_encode($salt);

    // Derive KEK from credential_id + salt — matches server hkdfPasskeyKek().
    $kek = hash_hkdf('sha256', $credentialId, 32, 'paycal-passkey-kek', $salt);

    // Generate a fresh DEK.
    $dek = random_bytes(32);

    // Wrap DEK with KEK (AES-256-GCM).
    $wrapNonce = random_bytes(12);
    $wrapTag = '';
    $wrapCt = openssl_encrypt($dek, 'aes-256-gcm', $kek, OPENSSL_RAW_DATA, $wrapNonce, $wrapTag);
    if ($wrapCt === false) {
      throw new \RuntimeException('EncryptedWorkTestUser: DEK wrapping failed');
    }

    // Store as base64(JSON{nonce, ciphertext}) — matches unwrapDekFromPasskeyWrapper() expectations.
    $wrappedDekB64 = base64_encode((string) json_encode([
      'nonce'      => base64_encode($wrapNonce),
      'ciphertext' => base64_encode($wrapCt . $wrapTag),
    ]));

    $fixture = new self($userUUID, $sessionHash, $credentialId, $dek);

    Database::hset(Keys::USER . ':' . $userUUID, [
      'user_uuid'            => $userUUID,
      'email'                => 'enc-work-' . bin2hex(random_bytes(3)) . '@example.com',
      'full_name'            => 'Encrypted Work Fixture User',
      'email_verified'       => '1',
      'auth_level'           => (string) AuthLevel::USER->value,
      'pay_period_type'      => 'bi-weekly',
      'pay_period_start_day' => '1',
      'pay_period_start_month' => '0',
      'editing_grace_days'   => '3',
      'encryption_salt'      => $saltB64,
    ]);

    // Store wrapped DEK under the credential — personal resolveDekForEnvelope() path.
    Database::hset(Keys::USER . ':' . $userUUID . ':passkey_wrapped_deks', [
      $credentialId => $wrappedDekB64,
    ]);

    Database::hset(Keys::SESSION . ':' . $sessionHash, [
      'user_uuid'     => $userUUID,
      'created_at'    => date('c'),
      'credential_id' => $credentialId,
    ]);
    Database::expire(Keys::SESSION . ':' . $sessionHash, 3600);

    $_COOKIE['PAYCAL_AUTH'] = $sessionHash;
    $_SERVER['REQUEST_METHOD'] = 'GET';

    return $fixture;
  }

  public function addSite(string $siteId, string $siteName, float $wage): void
  {
    Database::hset(Keys::SITE . ':' . $this->userUUID . ':' . $siteId, [
      'site_name' => $siteName,
      'status'    => 'active',
      'wage'      => number_format($wage, 2, '.', ''),
    ]);
  }

  /**
   * Ensure at least one work row exists for the current calendar date.
   *
   * Idempotent: no-op if the key already exists in Redis.
   */
  public function ensureCurrentDateHasWorkData(
    string $siteId,
    string $siteName,
    float $wage,
    float $hours = 8.0,
    float $travelHours = 0.0,
    float $livingOutAllowance = 0.0
  ): void {
    $today = date('Y-m-d');
    $workKey = Keys::WORK . ':' . $this->userUUID . ':' . $today . ':' . $siteId;
    if (Database::exists($workKey)) {
      return;
    }

    $regular = min(8.0, max(0.0, $hours));
    $overtime = max(0.0, $hours - $regular);
    $gross = ($regular * $wage) + (($overtime * $wage) * 1.5) + ($travelHours * $wage) + $livingOutAllowance;

    $this->seedWorkRow([
      'date'               => $today,
      'site_id'            => $siteId,
      'site_name'          => $siteName,
      'hours'              => $hours,
      'regular_hours'      => $regular,
      'overtime_hours'     => $overtime,
      'travel_hours'       => $travelHours,
      'living_out_allowance' => $livingOutAllowance,
      'wage'               => $wage,
      'gross'              => $gross,
    ]);
  }

  /**
   * Seed one encrypted work row under the current fixture user.
   *
   * The work payload is AES-256-GCM-encrypted with the fixture's DEK and stored as
   * encrypted_blob. Raw numeric fields are also written to the hash so calendar-path
   * helpers (Work::getWorkInRange) can read metadata without decryption.
   *
   * Expected keys in $row:
   *   date (Y-m-d), site_id, site_name
   * Optional numeric keys:
   *   hours, regular_hours, overtime_hours, travel_hours, living_out_allowance, wage, gross,
   *   tax, net, other
   *
   * @param array<string, mixed> $row
   */
  public function seedWorkRow(array $row): void
  {
    $date     = (string) ($row['date'] ?? '');
    $siteId   = (string) ($row['site_id'] ?? '');
    $siteName = (string) ($row['site_name'] ?? '');
    if ($date === '' || $siteId === '') {
      throw new \InvalidArgumentException('seedWorkRow requires non-empty date and site_id');
    }

    $regular  = $this->numeric($row['regular_hours'] ?? 0.0);
    $overtime = $this->numeric($row['overtime_hours'] ?? 0.0);
    $travel   = $this->numeric($row['travel_hours'] ?? 0.0);
    $loa      = $this->numeric($row['living_out_allowance'] ?? 0.0);
    $wage     = $this->numeric($row['wage'] ?? 0.0);
    $hours    = array_key_exists('hours', $row)
      ? $this->numeric($row['hours'])
      : ($regular + $overtime);
    $gross    = array_key_exists('gross', $row)
      ? $this->numeric($row['gross'])
      : (($regular * $wage) + (($overtime * $wage) * 1.5) + ($travel * $wage) + $loa);

    // Build plaintext payload (financial fields for decryptWorkRowIfNeeded to decode).
    $plaintextData = [
      'date'               => $date,
      'site_id'            => $siteId,
      'site_name'          => $siteName,
      'hours'              => number_format($hours, 2, '.', ''),
      'regular_hours'      => number_format($regular, 2, '.', ''),
      'overtime_hours'     => number_format($overtime, 2, '.', ''),
      'travel_hours'       => number_format($travel, 2, '.', ''),
      'living_out_allowance' => number_format($loa, 2, '.', ''),
      'wage'               => number_format($wage, 2, '.', ''),
      'gross'              => number_format($gross, 2, '.', ''),
    ];
    foreach (['tax', 'net', 'other'] as $optKey) {
      if (array_key_exists($optKey, $row)) {
        $plaintextData[$optKey] = number_format($this->numeric($row[$optKey]), 2, '.', '');
      }
    }

    // Encrypt plaintext JSON with DEK (AES-256-GCM, empty AAD).
    $nonce = random_bytes(12);
    $tag   = '';
    $ct    = openssl_encrypt(
      (string) json_encode($plaintextData),
      'aes-256-gcm',
      $this->dek,
      OPENSSL_RAW_DATA,
      $nonce,
      $tag
    );
    if ($ct === false) {
      throw new \RuntimeException('EncryptedWorkTestUser: work row encryption failed');
    }

    $encryptedBlob = base64_encode((string) json_encode([
      'nonce'      => base64_encode($nonce),
      'ciphertext' => base64_encode($ct . $tag),
      'aad'        => '',
    ]));

    // Write both raw fields (calendar metadata) and encrypted_blob (auth financial source).
    $workKey = Keys::WORK . ':' . $this->userUUID . ':' . $date . ':' . $siteId;
    $payload = [
      'date'               => $date,
      'site_id'            => $siteId,
      'site_name'          => $siteName,
      'hours'              => number_format($hours, 2, '.', ''),
      'regular_hours'      => number_format($regular, 2, '.', ''),
      'overtime_hours'     => number_format($overtime, 2, '.', ''),
      'travel_hours'       => number_format($travel, 2, '.', ''),
      'living_out_allowance' => number_format($loa, 2, '.', ''),
      'wage'               => number_format($wage, 2, '.', ''),
      'gross'              => number_format($gross, 2, '.', ''),
      'encrypted_blob'     => $encryptedBlob,
    ];
    foreach (['tax', 'net', 'other'] as $optKey) {
      if (array_key_exists($optKey, $row)) {
        $payload[$optKey] = number_format($this->numeric($row[$optKey]), 2, '.', '');
      }
    }

    Database::hset($workKey, $payload);
  }

  public function cleanup(): void
  {
    foreach (Database::scanKeys(Keys::WORK . ':' . $this->userUUID . ':*') as $key) {
      Database::unlink((string) $key);
    }
    foreach (Database::scanKeys(Keys::WORK . ':archived:' . $this->userUUID . ':*') as $key) {
      Database::unlink((string) $key);
    }
    foreach (Database::scanKeys(Keys::SITE . ':' . $this->userUUID . ':*') as $key) {
      Database::unlink((string) $key);
    }

    Database::unlink(Keys::USER . ':' . $this->userUUID . ':passkey_wrapped_deks');
    Database::unlink(Keys::SESSION . ':' . $this->sessionHash);
    Database::unlink(Keys::USER . ':' . $this->userUUID);

    $_COOKIE = $this->originalCookie;
    $_SERVER = $this->originalServer;
  }

  private function numeric(mixed $value): float
  {
    if (is_int($value) || is_float($value)) {
      return (float) $value;
    }
    if (is_string($value) && is_numeric($value)) {
      return (float) $value;
    }
    return 0.0;
  }
}
