<?php declare(strict_types=1);

namespace Tests\Integration\Support;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;

/**
 * Shared integration-test fixture for work-data tests that do not require DEK encryption.
 *
 * Why this exists:
 * - Speeds up work-data tests by avoiding per-test envelope encryption/decryption setup.
 * - Keeps test data deterministic and easy to seed/manipulate.
 * - Centralizes cleanup for user/session/site/work keys touched by these tests.
 */
final class PlaintextWorkTestUser
{
  public readonly string $userUUID;
  public readonly string $sessionHash;

  /** @var array<string, string> */
  private array $originalCookie;
  /** @var array<string, string> */
  private array $originalServer;

  private function __construct(string $userUUID, string $sessionHash)
  {
    $this->userUUID = $userUUID;
    $this->sessionHash = $sessionHash;
    $this->originalCookie = $_COOKIE ?? [];
    $this->originalServer = $_SERVER ?? [];
  }

  public static function create(): self
  {
    $userUUID = 'U' . bin2hex(random_bytes(8));
    $sessionHash = hash('sha256', bin2hex(random_bytes(32)));

    $fixture = new self($userUUID, $sessionHash);

    Database::hset(Keys::USER . ':' . $userUUID, [
      'user_uuid' => $userUUID,
      'email' => 'plain-work-' . bin2hex(random_bytes(3)) . '@example.com',
      'full_name' => 'Plain Work Fixture User',
      'email_verified' => '1',
      'auth_level' => (string) AuthLevel::USER->value,
      'pay_period_type' => 'bi-weekly',
      'pay_period_start_day' => '1',
      'pay_period_start_month' => '0',
      'editing_grace_days' => '3',
      'encryption_salt' => base64_encode(random_bytes(16)),
      'wrapped_dek' => '',
      'wrapped_dek_passkey' => '',
      'dek_version' => '1',
      'crypto_version' => '1',
    ]);

    Database::hset(Keys::SESSION . ':' . $sessionHash, [
      'user_uuid' => $userUUID,
      'created_at' => date('c'),
      'credential_id' => '',
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
      'status' => 'active',
      'wage' => number_format($wage, 2, '.', ''),
    ]);
  }

  /**
   * Ensure there is at least one work row for the current calendar date.
   *
   * This is intentionally idempotent and fast for per-test setup calls.
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
      'date' => $today,
      'site_id' => $siteId,
      'site_name' => $siteName,
      'hours' => $hours,
      'regular_hours' => $regular,
      'overtime_hours' => $overtime,
      'travel_hours' => $travelHours,
      'living_out_allowance' => $livingOutAllowance,
      'wage' => $wage,
      'gross' => $gross,
    ]);
  }

  /**
   * Seed one plaintext work row under the current fixture user.
   *
   * Expected keys in $row:
   * - date (Y-m-d), site_id, site_name
   * Optional numeric keys:
   * - hours, regular_hours, overtime_hours, travel_hours, living_out_allowance, wage, gross, tax, net, other
   *
   * @param array<string, mixed> $row
   */
  public function seedWorkRow(array $row): void
  {
    $date = (string) ($row['date'] ?? '');
    $siteId = (string) ($row['site_id'] ?? '');
    $siteName = (string) ($row['site_name'] ?? '');
    if ($date === '' || $siteId === '') {
      throw new \InvalidArgumentException('seedWorkRow requires non-empty date and site_id');
    }

    $regular = $this->numeric($row['regular_hours'] ?? 0.0);
    $overtime = $this->numeric($row['overtime_hours'] ?? 0.0);
    $travel = $this->numeric($row['travel_hours'] ?? 0.0);
    $loa = $this->numeric($row['living_out_allowance'] ?? 0.0);
    $wage = $this->numeric($row['wage'] ?? 0.0);
    $hours = array_key_exists('hours', $row)
      ? $this->numeric($row['hours'])
      : ($regular + $overtime);

    $gross = array_key_exists('gross', $row)
      ? $this->numeric($row['gross'])
      : (($regular * $wage) + (($overtime * $wage) * 1.5) + ($travel * $wage) + $loa);

    $workKey = Keys::WORK . ':' . $this->userUUID . ':' . $date . ':' . $siteId;
    $payload = [
      'date' => $date,
      'site_id' => $siteId,
      'site_name' => $siteName,
      'hours' => number_format($hours, 2, '.', ''),
      'regular_hours' => number_format($regular, 2, '.', ''),
      'overtime_hours' => number_format($overtime, 2, '.', ''),
      'travel_hours' => number_format($travel, 2, '.', ''),
      'living_out_allowance' => number_format($loa, 2, '.', ''),
      'wage' => number_format($wage, 2, '.', ''),
      'gross' => number_format($gross, 2, '.', ''),
    ];

    foreach (['tax', 'net', 'other'] as $key) {
      if (array_key_exists($key, $row)) {
        $payload[$key] = number_format($this->numeric($row[$key]), 2, '.', '');
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
