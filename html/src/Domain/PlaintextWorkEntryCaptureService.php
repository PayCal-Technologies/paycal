<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;
use PayCal\Infrastructure\Cache\EarningsCacheService;

/**
 * Client-assisted capture for legacy work rows that still lack encrypted_blob.
 *
 * The server never synthesizes encrypted work payloads. It can only expose the
 * signed-in user's own plaintext legacy rows so the unlocked client DEK can
 * encrypt them, then finalize the rewrite after validating the row has not
 * changed.
 */
final class PlaintextWorkEntryCaptureService
{
  private const DEFAULT_LIMIT = 25;
  private const MAX_LIMIT = 100;

  private const LEGACY_PLAINTEXT_FIELDS = [
    'date',
    'site_id',
    'tax',
    'net',
    'other',
    'd',
    's',
    'n',
    'h',
    'r',
    'o',
    'l',
    't',
    'w',
    'g',
    'tx',
    'business_id',
    'organization_id',
    'org_id',
    'target_user_uuid',
    'owner_uuid',
  ];

  /**
   * @return array{success: bool, data: array{entries: list<array<string, mixed>>, remaining: int}}
   */
  public function listPending(string $userUUID, int $limit = self::DEFAULT_LIMIT, bool $includeArchived = false): array
  {
    $userUUID = trim($userUUID);
    $limit = max(1, min(self::MAX_LIMIT, $limit));
    if ($userUUID === '') {
      return ['success' => false, 'data' => ['entries' => [], 'remaining' => 0]];
    }

    $keys = Database::scanKeys(Keys::WORK . ':' . $userUUID . ':*');
    if ($includeArchived) {
      $keys = array_merge($keys, Database::scanKeys(Keys::WORK . ':archived:' . $userUUID . ':*'));
      sort($keys, SORT_STRING);
    }

    $entries = [];
    $remaining = 0;
    foreach ($keys as $key) {
      $parts = $this->parseWorkKey((string) $key);
      if ($parts === null || $parts['user_uuid'] !== $userUUID) {
        continue;
      }

      $row = Database::hgetall((string) $key);
      if (!$this->needsCapture($row)) {
        continue;
      }

      $entry = $this->capturePayload((string) $key, $parts, $row);
      if ($entry === null) {
        continue;
      }

      ++$remaining;
      if (count($entries) < $limit) {
        $entries[] = $entry;
      }
    }

    return [
      'success' => true,
      'data' => [
        'entries' => $entries,
        'remaining' => max(0, $remaining - count($entries)),
      ],
    ];
  }

  /**
   * @param list<array<string, mixed>> $submitted
   * @return array{success: bool, data: array{encrypted: int, skipped: int, failed: int, results: list<array<string, string>>}}
   */
  public function finalize(string $userUUID, array $submitted): array
  {
    $userUUID = trim($userUUID);
    $encrypted = 0;
    $skipped = 0;
    $failed = 0;
    $results = [];

    foreach ($submitted as $item) {
      $key = $this->stringValue($item['key'] ?? '');
      $captureToken = $this->stringValue($item['capture_token'] ?? '');
      $blob = $this->stringValue($item['encrypted_blob'] ?? '');
      $parts = $this->parseWorkKey($key);
      if ($userUUID === '' || $parts === null || $parts['user_uuid'] !== $userUUID) {
        ++$failed;
        $results[] = ['key' => $key, 'status' => 'failed', 'reason' => 'invalid_key'];
        continue;
      }

      $row = Database::hgetall($key);
      if ($row === []) {
        ++$skipped;
        $results[] = ['key' => $key, 'status' => 'skipped', 'reason' => 'missing_row'];
        continue;
      }

      if (!$this->needsCapture($row)) {
        ++$skipped;
        $results[] = ['key' => $key, 'status' => 'skipped', 'reason' => 'already_encrypted'];
        continue;
      }

      $expected = $this->capturePayload($key, $parts, $row);
      if ($expected === null || !hash_equals($this->stringValue($expected['capture_token'] ?? ''), $captureToken)) {
        ++$failed;
        $results[] = ['key' => $key, 'status' => 'failed', 'reason' => 'stale_capture'];
        continue;
      }

      $validation = WorkEntry::validateEncryptedBlob($blob);
      if (!$validation['valid']) {
        ++$failed;
        $results[] = ['key' => $key, 'status' => 'failed', 'reason' => $validation['error']];
        continue;
      }

      if (!$this->encryptedBlobMatchesSite($blob, $parts['site_id'])) {
        ++$failed;
        $results[] = ['key' => $key, 'status' => 'failed', 'reason' => 'site_aad_mismatch'];
        continue;
      }

      $snapshot = $this->storageSnapshot($parts, $row, $blob);
      Database::hset($key, $snapshot);
      Database::hdel($key, ...self::LEGACY_PLAINTEXT_FIELDS);
      $this->afterRewrite($userUUID, $parts['date']);

      ++$encrypted;
      $results[] = ['key' => $key, 'status' => 'encrypted', 'reason' => ''];
    }

    return [
      'success' => $failed === 0,
      'data' => [
        'encrypted' => $encrypted,
        'skipped' => $skipped,
        'failed' => $failed,
        'results' => $results,
      ],
    ];
  }

  /**
   * @param array<string, string> $row
   */
  private function needsCapture(array $row): bool
  {
    if ($row === []) {
      return false;
    }

    $blob = trim((string) ($row['encrypted_blob'] ?? ''));
    if ($blob === '') {
      return true;
    }

    return !WorkEntry::validateEncryptedBlob($blob)['valid'];
  }

  /**
   * @param array{archived: bool, user_uuid: string, date: string, site_id: string} $parts
   * @param array<string, string> $row
   * @return array<string, mixed>|null
   */
  private function capturePayload(string $key, array $parts, array $row): ?array
  {
    if (!WorkEntry::validateDate($parts['date']) || !WorkEntry::validateSiteId($parts['site_id'])) {
      return null;
    }

    $entry = $this->canonicalEntry($parts, $row);

    return [
      'key' => $key,
      'capture_token' => $this->captureToken($key, $entry),
      'date' => $parts['date'],
      'archived' => $parts['archived'],
      'entry' => $entry,
    ];
  }

  /**
   * @param array{archived: bool, user_uuid: string, date: string, site_id: string} $parts
   * @param array<string, string> $row
   * @return array{
   *   date: string,
   *   site_id: string,
   *   site_name: string,
   *   hours: float,
   *   regular_hours: float,
   *   overtime_hours: float,
   *   living_out_allowance: float,
   *   travel_hours: float,
   *   wage: float
   * }
   */
  private function canonicalEntry(array $parts, array $row): array
  {
    $normalized = WorkEntry::normalizeWorkEntryPayload($row);
    $regularHours = $this->numericValue($normalized['regular_hours'] ?? $normalized['hours'] ?? 0);
    $overtimeHours = $this->numericValue($normalized['overtime_hours'] ?? 0);
    $travelHours = $this->numericValue($normalized['travel_hours'] ?? 0);
    $livingOut = $this->numericValue($normalized['living_out_allowance'] ?? 0);
    $hours = $this->numericValue($normalized['hours'] ?? ($regularHours + $overtimeHours));
    if ($regularHours === 0.0 && $overtimeHours === 0.0 && $hours > 0.0) {
      $regularHours = $hours;
    }

    $siteName = trim((string) ($normalized['site_name'] ?? ''));
    if ($siteName === '') {
      $siteName = Sites::getSiteName($parts['site_id'], $parts['user_uuid']);
    }

    return [
      'date' => $parts['date'],
      'site_id' => $parts['site_id'],
      'site_name' => $siteName,
      'hours' => $hours,
      'regular_hours' => $regularHours,
      'overtime_hours' => $overtimeHours,
      'living_out_allowance' => $livingOut,
      'travel_hours' => $travelHours,
      'wage' => $this->numericValue($normalized['wage'] ?? 0),
    ];
  }

  /**
   * @param array<string, mixed> $entry
   */
  private function captureToken(string $key, array $entry): string
  {
    ksort($entry);

    return hash('sha256', $key . "\n" . (string) json_encode($entry, JSON_UNESCAPED_SLASHES));
  }

  /**
   * @param array{archived: bool, user_uuid: string, date: string, site_id: string} $parts
   * @param array<string, string> $row
   * @return array<string, string>
   */
  private function storageSnapshot(array $parts, array $row, string $blob): array
  {
    $entry = $this->canonicalEntry($parts, $row);
    $earnings = WorkEntry::calculateEarningsSnapshot(
      $entry['regular_hours'],
      $entry['overtime_hours'],
      $entry['travel_hours'],
      $entry['living_out_allowance'],
      $entry['wage'],
    );

    $siteColor = $this->validColor((string) ($row['site_color'] ?? ''));

    return [
      'encrypted_blob' => $blob,
      'site_name' => $entry['site_name'],
      'site_color' => $siteColor,
      'hours' => number_format($entry['hours'], 2, '.', ''),
      'regular_hours' => number_format($entry['regular_hours'], 2, '.', ''),
      'overtime_hours' => number_format($entry['overtime_hours'], 2, '.', ''),
      'living_out_allowance' => number_format($entry['living_out_allowance'], 2, '.', ''),
      'travel_hours' => number_format($entry['travel_hours'], 2, '.', ''),
      'wage' => number_format($entry['wage'], 2, '.', ''),
      'regular_amount' => number_format($earnings['regular_amount'], 2, '.', ''),
      'overtime_amount' => number_format($earnings['overtime_amount'], 2, '.', ''),
      'travel_amount' => number_format($earnings['travel_amount'], 2, '.', ''),
      'living_out_amount' => number_format($earnings['living_out_amount'], 2, '.', ''),
      'gross' => number_format($earnings['gross'], 2, '.', ''),
      'earnings_snapshot_version' => '1',
      'plaintext_captured_at' => gmdate('c'),
    ];
  }

  /**
   * Check whether an encrypted work-entry envelope is bound to the expected site.
   */
  private function encryptedBlobMatchesSite(string $blob, string $siteId): bool
  {
    $decoded = base64_decode($blob, true);
    if ($decoded === false) {
      return false;
    }

    $envelope = json_decode($decoded, true);
    if (!is_array($envelope)) {
      return false;
    }

    $aad = $envelope['aad'] ?? '';

    return is_scalar($aad) && trim((string) $aad) === $siteId;
  }

  /**
   * @return null|array{archived: bool, user_uuid: string, date: string, site_id: string}
   */
  private function parseWorkKey(string $key): ?array
  {
    $parts = explode(':', $key);
    if (count($parts) < 4 || $parts[0] !== Keys::WORK) {
      return null;
    }

    if ($parts[1] === 'archived') {
      if (count($parts) < 5) {
        return null;
      }

      return [
        'archived' => true,
        'user_uuid' => (string) $parts[2],
        'date' => (string) $parts[3],
        'site_id' => (string) $parts[4],
      ];
    }

    return [
      'archived' => false,
      'user_uuid' => (string) $parts[1],
      'date' => (string) $parts[2],
      'site_id' => (string) $parts[3],
    ];
  }

  /**
   * Rebuild dependent work-week, earnings, and business caches after rewriting.
   */
  private function afterRewrite(string $userUUID, string $date): void
  {
    Work::processWorkWeekContainingDate($userUUID, $date);
    EarningsCacheService::invalidateForUser($userUUID);
    $year = (int) substr($date, 0, 4);
    BusinessWorkspaceCache::invalidateFinancialDataForMember(
      $userUUID,
      $year >= 2000 && $year <= 2100 ? $year : null,
    );
  }

  /**
   * Coerce numeric Redis payload values into floats.
   */
  private function numericValue(mixed $value): float
  {
    return is_numeric($value) ? (float) $value : 0.0;
  }

  /**
   * Trim scalar payload values and reject arrays or objects.
   */
  private function stringValue(mixed $value): string
  {
    return is_scalar($value) ? trim((string) $value) : '';
  }

  /**
   * Normalize a stored color token to a six-digit hex value when valid.
   */
  private function validColor(string $value): string
  {
    $color = strtoupper(trim($value));

    return preg_match('/^#[0-9A-F]{6}$/', $color) ? $color : '';
  }
}
