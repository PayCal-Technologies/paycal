<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\SystemConfig;
use PayCal\Infrastructure\Cache\EarningsCacheService;
use PayCal\Infrastructure\Telemetry\SecurityLog;

use PayCal\Observability\Lens;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\SiteStatus;

/**
 * WorkEntry.php
 *
 * Purpose: Schema authority and validation helper for normalized work-entry
 * payloads, storage envelopes, and update-safe field handling.
 *
 * Developer notes:
 * - Controllers should not invent their own work-entry validation rules.
 *   Add or change field rules here so reads, writes, and tests stay aligned.
 * - This file sits close to persistence concerns, so changes can affect
 *   historical locking, organization envelope metadata, and reporting.
 * - Keep normalization behavior deterministic; silent shape changes here ripple
 *   into calendar, earnings, exports, and org-scoped flows.
 * - When adding fields, review downstream serializers and repositories rather
 *   than assuming unused fields are harmless.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Work-entry schema and normalization utility.
 *
 * Responsibilities:
 * - Validate canonical work-entry fields and identifiers.
 * - Normalize payloads before persistence or downstream consumption.
 * - Guard storage/update helpers against malformed or incomplete entry data.
 * - Serve as the central reference point for work-entry shape expectations.
 */
class WorkEntry
{
  private const MIN_DAILY_HOURS = 0.0;
  private const MAX_DAILY_HOURS = 24.0;

  // ////////////////////////////////////////////////////////////////////////////
  // SCHEMA AUTHORITY & VALIDATION
  //
  // All field validation must occur in this section. Controllers and tests
  // should NEVER validate fields directly - they must use these methods.

  /**
   * Validate a work entry date field.
   *
   * @param string $date Date in Y-m-d format
   * @return bool True if valid
   */
  public static function validateDate(string $date): bool
  {
    // Must match Y-m-d format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      return false;
    }

    // Must be a valid date that can be parsed
    return false !== strtotime($date);
  }

  /**
   * Validate a site ID field.
   *
   * @param string $siteId Site ID (format: S + 9 hex chars)
   * @return bool True if valid
   */
  public static function validateSiteId(string $siteId): bool
  {
    // Canonical format: S followed by 9 hexadecimal characters
    return (bool) preg_match('/^S[0-9A-Fa-f]{9}$/', $siteId);
  }

  /**
   * Validate and clamp hours field.
   *
   * @param float $hours Hours value
   * @return float Clamped hours value
   */
  public static function validateHours(float $hours): float
  {
    $minDailyHours = self::MIN_DAILY_HOURS;
    $maxDailyHours = self::MAX_DAILY_HOURS;

    if ($hours < $minDailyHours) {
      return $minDailyHours;
    }

    if ($hours > $maxDailyHours) {
      return $maxDailyHours;
    }

    return $hours;
  }

  /**
   * Validate living out allowance field.
   *
   * @param float $allowance Allowance value
   * @return float Valid allowance (non-negative)
   */
  public static function validateAllowance(float $allowance): float
  {
    return max(self::MIN_DAILY_HOURS, $allowance);
  }

  /**
   * Validate travel hours field.
   *
   * @param float $travelHours Travel hours value
   * @return float Valid travel hours (non-negative, max 24)
   */
  public static function validateTravelHours(float $travelHours): float
  {
    return max(self::MIN_DAILY_HOURS, min(self::MAX_DAILY_HOURS, $travelHours));
  }

  /**
   * Validate an encrypted blob field.
   *
   * @param string $blob Base64-encoded encrypted blob
   * @return array{valid: bool, error: string} Validation result
   */
  public static function validateEncryptedBlob(string $blob): array
  {
    // Enforce size limit
    if (strlen($blob) > \PayCal\Domain\Config\SystemConfig::ENCRYPTED_BLOB_MAX_BYTES) {
      return ['valid' => false, 'error' => 'blob_too_large'];
    }

    // Must be valid base64
    $decoded = base64_decode($blob, true);
    if (false === $decoded) {
      return ['valid' => false, 'error' => 'invalid_base64'];
    }

    // Must be valid JSON
    $envelopeRaw = json_decode($decoded, true);
    if (!is_array($envelopeRaw)) {
      return ['valid' => false, 'error' => 'invalid_json_envelope'];
    }

    /** @var array<string, mixed> $envelope */
    $envelope = $envelopeRaw;

    // Must have required envelope fields
    $required = ['ciphertext', 'nonce', 'aad'];
    foreach ($required as $field) {
      if (!isset($envelope[$field]) || !is_string($envelope[$field]) || '' === $envelope[$field]) {
        return ['valid' => false, 'error' => "missing_field_{$field}"];
      }
    }

    $strictOrgEnvelope = (bool) SystemConfig::get('org_shared_encryption_enforce_strict_envelope');
    if ($strictOrgEnvelope) {
      $orgEnvelopeValidation = self::validateOrganizationEnvelopeMetadata($envelope);
      if (!$orgEnvelopeValidation['valid']) {
        return $orgEnvelopeValidation;
      }
    }

    return ['valid' => true, 'error' => ''];
  }

  /**
   * Validate that an encrypted blob is org-mode and matches request context.
   *
   * @return array{valid: bool, error: string}
   */
  public static function validateOrganizationEnvelopeContext(
    string $blob,
    string $expectedOrgId,
    string $expectedSegment = 'current_period'
  ): array {
    $expectedOrgId = trim((string) $expectedOrgId);
    $expectedSegment = trim((string) $expectedSegment);
    if ($expectedOrgId === '') {
      return ['valid' => false, 'error' => 'missing_expected_org_id'];
    }
    if ($expectedSegment === '') {
      $expectedSegment = 'current_period';
    }

    $decoded = base64_decode($blob, true);
    if ($decoded === false) {
      return ['valid' => false, 'error' => 'invalid_base64'];
    }

    $envelope = json_decode($decoded, true);
    if (!is_array($envelope)) {
      return ['valid' => false, 'error' => 'invalid_json'];
    }

    $metaRaw = $envelope['meta'] ?? null;
    $meta = is_array($metaRaw) ? $metaRaw : [];
    $modeRaw = $meta['encryption_mode'] ?? ($envelope['encryption_mode'] ?? null);
    $mode = is_scalar($modeRaw) ? trim((string) $modeRaw) : '';
    if ($mode !== 'organization') {
      return ['valid' => false, 'error' => 'org_mode_required'];
    }

    $orgIdRaw = $meta['org_id'] ?? ($envelope['org_id'] ?? null);
    $segmentRaw = $meta['segment'] ?? ($envelope['segment'] ?? null);
    $keyVersionRaw = $meta['key_version'] ?? ($envelope['key_version'] ?? null);

    $orgId = is_scalar($orgIdRaw) ? trim((string) $orgIdRaw) : '';
    $segment = is_scalar($segmentRaw) ? trim((string) $segmentRaw) : '';
    $keyVersion = is_scalar($keyVersionRaw) ? trim((string) $keyVersionRaw) : '';

    if ($orgId === '' || $segment === '' || $keyVersion === '') {
      return ['valid' => false, 'error' => 'missing_org_context_fields'];
    }

    if ($orgId !== $expectedOrgId) {
      return ['valid' => false, 'error' => 'org_id_mismatch'];
    }

    if ($segment !== $expectedSegment) {
      return ['valid' => false, 'error' => 'segment_mismatch'];
    }

    return ['valid' => true, 'error' => ''];
  }

  /**
   * Validate org-mode envelope metadata.
   *
   * This is intentionally tolerant for non-org payloads.
   * Strict field checks run only when encryption_mode=organization.
   *
   * @param array<string, mixed> $envelope
   * @return array{valid: bool, error: string}
   */
  private static function validateOrganizationEnvelopeMetadata(array $envelope): array
  {
    $metaRaw = $envelope['meta'] ?? null;
    $meta = is_array($metaRaw) ? $metaRaw : [];

    $modeRaw = $meta['encryption_mode'] ?? ($envelope['encryption_mode'] ?? null);
    $mode = is_scalar($modeRaw) ? trim((string) $modeRaw) : '';

    if ($mode === '' || $mode === 'personal') {
      return ['valid' => true, 'error' => ''];
    }

    if ($mode !== 'organization') {
      return ['valid' => false, 'error' => 'invalid_encryption_mode'];
    }

    $requiredOrgFields = ['org_id', 'segment', 'key_version', 'dek_id', 'needs_rewrap'];
    foreach ($requiredOrgFields as $field) {
      $value = $meta[$field] ?? ($envelope[$field] ?? null);
      if (!is_scalar($value) || trim((string) $value) === '') {
        return ['valid' => false, 'error' => "missing_org_meta_{$field}"];
      }
    }

    return ['valid' => true, 'error' => ''];
  }

  /**
   * Handles asString operation.
   */
  private static function asString(mixed $value): string
  {
    return is_scalar($value) ? (string) $value : '';
  }

  // ////////////////////////////////////////////////////////////////////////////
  // PAYLOAD NORMALIZATION
  /**
   * Convert mixed legacy/canonical work-entry payload into canonical keys only.
   *
   * @param array<string, mixed> $payload
   *
  * @return array<string, null|scalar>
   */
  public static function normalizeWorkEntryPayload(array $payload): array
  {
    $canonical = [];

    $map = [
      'date' => ['d'],
      'site_id' => ['s'],
      'site_name' => ['n'],
      'hours' => ['h'],
      'regular_hours' => ['r'],
      'overtime_hours' => ['o'],
      'living_out_allowance' => ['l'],
      'travel_hours' => ['t'],
      'wage' => ['w'],
      'gross' => ['g'],
      'tax' => ['tx'],
      'net' => [],
    ];

    foreach ($map as $canonicalKey => $aliases) {
      if (array_key_exists($canonicalKey, $payload)) {
        $value = $payload[$canonicalKey];
        if (is_scalar($value) || null === $value) {
          $canonical[$canonicalKey] = $value;
        }
        continue;
      }

      foreach ($aliases as $alias) {
        if (array_key_exists($alias, $payload)) {
          $value = $payload[$alias];
          if (is_scalar($value) || null === $value) {
            $canonical[$canonicalKey] = $value;
          }
          break;
        }
      }
    }

    if (array_key_exists('encrypted_blob', $payload)) {
      $value = $payload['encrypted_blob'];
      if (is_scalar($value) || null === $value) {
        $canonical['encrypted_blob'] = $value;
      }
    }

    if (array_key_exists('cal_work_save_as_default', $payload)) {
      $value = $payload['cal_work_save_as_default'];
      if (is_scalar($value) || null === $value) {
        $canonical['cal_work_save_as_default'] = $value;
      }
    }

    if (array_key_exists('other', $payload)) {
      $value = $payload['other'];
      if (is_scalar($value) || null === $value) {
        $canonical['other'] = $value;
      }
    }

    // Reconstruct regular/overtime split when legacy payloads only provide total hours.
    $hasRegularSource = array_key_exists('regular_hours', $payload) || array_key_exists('r', $payload);
    $hasOvertimeSource = array_key_exists('overtime_hours', $payload) || array_key_exists('o', $payload);
    $hoursRaw = $canonical['hours'] ?? null;
    $hours = (is_scalar($hoursRaw) && is_numeric((string) $hoursRaw))
      ? self::validateHours((float) $hoursRaw)
      : null;

    if (
      $hours === null
      && isset($canonical['regular_hours'], $canonical['overtime_hours'])
      && is_numeric((string) $canonical['regular_hours'])
      && is_numeric((string) $canonical['overtime_hours'])
    ) {
      $hours = self::validateHours((float) $canonical['regular_hours'] + (float) $canonical['overtime_hours']);
      $canonical['hours'] = $hours;
    }

    if ($hours === null) {
      return $canonical;
    }

    $dailyRegularCap = (float) SystemConfig::get('max_daily_regular_hours');
    if ($dailyRegularCap <= 0.0 || $dailyRegularCap > self::MAX_DAILY_HOURS) {
      $dailyRegularCap = 8.0;
    }

    if (
      $hasRegularSource
      && $hasOvertimeSource
      && isset($canonical['regular_hours'], $canonical['overtime_hours'])
      && is_numeric((string) $canonical['regular_hours'])
      && is_numeric((string) $canonical['overtime_hours'])
    ) {
      $regular = max(0.0, (float) $canonical['regular_hours']);
      $overtime = max(0.0, (float) $canonical['overtime_hours']);
      $splitMatchesHours = abs(($regular + $overtime) - $hours) < 0.0001;

      // Legacy rows can contain a flat split (all hours in regular, overtime zero)
      // even when total hours exceed the configured daily regular cap.
      if ($hours > $dailyRegularCap && $overtime <= 0.0001 && $splitMatchesHours) {
        $regular = min($hours, $dailyRegularCap);
        $canonical['regular_hours'] = $regular;
        $canonical['overtime_hours'] = max(0.0, $hours - $regular);
      }
    }

    if (!$hasRegularSource && !$hasOvertimeSource) {
      $regular = min($hours, $dailyRegularCap);
      $canonical['regular_hours'] = $regular;
      $canonical['overtime_hours'] = max(0.0, $hours - $regular);
    } elseif (!$hasRegularSource && isset($canonical['overtime_hours']) && is_numeric((string) $canonical['overtime_hours'])) {
      $overtime = max(0.0, (float) $canonical['overtime_hours']);
      $canonical['regular_hours'] = max(0.0, $hours - $overtime);
    } elseif (!$hasOvertimeSource && isset($canonical['regular_hours']) && is_numeric((string) $canonical['regular_hours'])) {
      $regular = max(0.0, (float) $canonical['regular_hours']);
      $canonical['overtime_hours'] = max(0.0, $hours - $regular);
    }

    return $canonical;
  }

  public string $siteId = '';

  public string $siteName = '';

  public float $hours = 0.0;

  public float $regularHours = 0.0;

  public float $overtimeHours = 0.0;

  public float $travelHours = 0.0;

  public float $cost = 0.0;

  public string $notes = '';

  /**
   * Initializes a new instance.
   */
  public function __construct(
    string $siteId = '',
    string $siteName = '',
    float $hours = 0.0,
    float $regularHours = 0.0,
    float $overtimeHours = 0.0,
    float $travelHours = 0.0,
    float $cost = 0.0,
    string $notes = ''
  ) {
    $this->siteId = $siteId;
    $this->siteName = $siteName;
    $this->hours = $hours;
    $this->regularHours = $regularHours;
    $this->overtimeHours = $overtimeHours;
    $this->travelHours = $travelHours;
    $this->cost = $cost;
    $this->notes = $notes;
  }

  /**
   * Check if a work entry exists in Redis by its key.
   *
   * @param string $workEntryKey Redis key for the work entry
   *
   * @return bool True if the work entry exists, false otherwise
   */
  public static function exists(string $workEntryKey): bool
  {
    $keyexists = Database::exists($workEntryKey);
    if ($keyexists) {
      return true;
    }

    return false;
  }

  /**
   * Checks if any Redis key matching the pattern exists.
   *
   * @param string $pattern Redis key pattern (wildcard-supported)
   *
   * @return bool True if any key matches
   */
  public static function zwildcardexists(string $pattern): bool
  {
    $keys = Database::scanKeys($pattern);
    if (!empty($keys)) {
      return true;
    }

    return false;
  }

  /**
   * Deletes all Redis keys matching the given pattern.
   *
   * @param string $pattern Redis key pattern (wildcard-supported)
   *
   * @return false|int Number of keys deleted, or false on error
   */
  public static function deleteWorkEntriesByPattern(string $pattern): false|int
  {
    Log::debug("WorkEntry::deleteWorkEntriesByPattern - Pattern: {$pattern}");
    \PayCal\Observability\Lens::add('Delete Pattern Scan Start', ['pattern' => $pattern], 'delete_scan');
    
    // Scan for keys matching the pattern first (ACTIVE keys)
    $keys = Database::scanKeys($pattern);
    $keyCount = count($keys);
    Log::debug("WorkEntry::deleteWorkEntriesByPattern - Found active keys: {$keyCount}");
    
    // ALSO scan for archived keys with :archived: in the pattern
    // Replace the pattern to look for archived versions
    $archivedPattern = str_replace(Keys::WORK . ':', Keys::WORK . ':archived:', $pattern);
    $archivedKeys = Database::scanKeys($archivedPattern);
    $archivedKeyCount = count($archivedKeys);
    Log::debug("WorkEntry::deleteWorkEntriesByPattern - Found archived keys: {$archivedKeyCount}");
    
    // Merge both sets
    $allKeys = array_merge($keys, $archivedKeys);
    $totalKeyCount = count($allKeys);
    
    Log::debug("Pattern: {$pattern}, Active: {$keyCount}, Archived: {$archivedKeyCount}, Total: {$totalKeyCount}");
    if (!empty($allKeys)) {
      Log::debug("Sample keys found (up to 3):");
      foreach (array_slice($allKeys, 0, 3) as $k) {
        Log::debug("  - " . $k);
      }
    }
    
    \PayCal\Observability\Lens::add('Delete Pattern Scan Result', [
      'pattern' => $pattern,
      'archived_pattern' => $archivedPattern,
      'active_keys_found' => $keyCount,
      'archived_keys_found' => $archivedKeyCount,
      'total_keys_found' => $totalKeyCount,
      'keys_type' => gettype($allKeys),
      'sample_keys' => array_slice($allKeys, 0, 3)
    ], 'delete_scan');
    
    if (empty($allKeys)) {
      Log::debug("WorkEntry::deleteWorkEntriesByPattern - No keys found, returning 0");
      return 0;
    }
    
    // Delete each key individually
    $totalDeleted = 0;
    $deletedKeys = [];
    foreach ($allKeys as $key) {
      $deleted = Database::del($key);
      if ($deleted) {
        $totalDeleted += $deleted;
        $deletedKeys[] = $key;
        Log::debug("  - Deleted: {$key}, count: {$deleted}");
      }
    }
    
    Log::debug("WorkEntry::deleteWorkEntriesByPattern - Total deleted: {$totalDeleted}");
    \PayCal\Observability\Lens::add('Delete Pattern Complete', [
      'pattern' => $pattern,
      'total_keys_scanned' => $totalKeyCount,
      'keys_deleted' => $totalDeleted,
      'deleted_key_samples' => array_slice($deletedKeys, 0, 5)
    ], 'delete_complete');
    
    return $totalDeleted > 0 ? $totalDeleted : false;
  }

  /**
   * Update a normalized day work-entry and persist to Redis.
   * Normalizes inputs:
   * - Validates `$workDate` (Y-m-d) and `$siteID` from POST ("d","s")
   * - Clamps `h` (hours) to [MIN_DAILY_REGULAR_HOURS .. MAX_DAILY_REGULAR_HOURS_ABSOLUTE]
   * - Unsets `r` and `o`
   * - Ensures `l` and `t` exist as numeric strings ("0" if empty)
   * - Activates site if currently "inactive".
   *
   * @param array<string, null|scalar> $workDetails
   * @param null|string                $userUUID    User UUID (falls back to session USER_UUID if null)
   *
   * @throws \RuntimeException if no user UUID available
   */
  public static function updateWorkEntry(array $workDetails, ?string $userUUID = null): bool
  {
    $workDetails = self::normalizeWorkEntryPayload($workDetails);

    // Get UUID from parameter or current user
    if (null === $userUUID) {
      $userUUID = User::currentUUID();
    }
    Log::debug('WorkEntry::updateWorkEntry called');
    $workDate = self::asString($workDetails['date'] ?? null);
    $siteID = self::asString($workDetails['site_id'] ?? null);
    Log::debug('WorkEntry::updateWorkEntry parsed');

    // Validate using centralized validation methods
    if (!self::validateDate($workDate)) {
      Log::warn("WorkEntry::updateWorkEntry - invalid date: {$workDate}");
      return false;
    }

    // Check if date is locked for editing (historical record locking)
    if (WorkEntryLockService::isLocked($workDate, $userUUID)) {
      Log::warn("WorkEntry::updateWorkEntry - date locked: {$workDate} for user {$userUUID}");
      SecurityLog::logEntryLockedAttempt($userUUID, $workDate);
      return false;
    }

    if (!self::validateSiteId($siteID)) {
      Log::warn("WorkEntry::updateWorkEntry - invalid site ID format: {$siteID}");
      return false;
    }

    // Validate and clamp hours using centralized method
    if (isset($workDetails['hours'])) {
      $hours = floatval((string) $workDetails['hours']);
      $workDetails['hours'] = number_format(self::validateHours($hours), 2, '.', '');
    }

    // Activate site if currently inactive
    $siteStatus = (new Sites())->getSiteStatus($siteID, $userUUID);
    if ($siteStatus === SiteStatus::INACTIVE->value) {
        (new Sites())->setSiteStatus($siteID, SiteStatus::ACTIVE->value, $userUUID);
    }
    $workEntryKey = Keys::WORK . ":{$userUUID}:{$workDate}:{$siteID}";
    Log::debug('WorkEntry::updateWorkEntry key');

    $blob = self::asString($workDetails['encrypted_blob'] ?? null);
    if ('' === $blob) {
      try {
        $v = \PayCal\Domain\Config\SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;
        Database::incr("telemetry:encryption:{$v}:server:required_write_rejected");
      } catch (\Throwable) {
      }
      Log::warn("WorkEntry::updateWorkEntry - encrypted_blob required for {$workEntryKey}");

      return false;
    }

    $validation = self::validateEncryptedBlob($blob);
    if (!$validation['valid']) {
      Log::warn("WorkEntry::updateWorkEntry - encrypted_blob validation failed: {$validation['error']} for {$workEntryKey}");

      try {
        $v = \PayCal\Domain\Config\SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;
        Database::incr("telemetry:encryption:{$v}:server:blob_validation_failed");
      } catch (\Throwable) {
      }

      return false;
    }

    // Persist ciphertext plus non-sensitive render snapshot for deterministic month paint.
    $siteName = self::asString($workDetails['site_name'] ?? null);
    $hours = self::validateHours((float) ($workDetails['hours'] ?? 0));
    $regularHours = self::validateHours((float) ($workDetails['regular_hours'] ?? 0));
    $overtimeHours = self::validateHours((float) ($workDetails['overtime_hours'] ?? 0));
    $livingOutAllowance = self::validateHours((float) ($workDetails['living_out_allowance'] ?? 0));
    $travelHours = self::validateHours((float) ($workDetails['travel_hours'] ?? 0));
    $wage = (float) ($workDetails['wage'] ?? 0);

    $fieldsToStore = [
      'encrypted_blob' => $blob,
      'site_name' => $siteName,
      'hours' => number_format($hours, 2, '.', ''),
      'regular_hours' => number_format($regularHours, 2, '.', ''),
      'overtime_hours' => number_format($overtimeHours, 2, '.', ''),
      'living_out_allowance' => number_format($livingOutAllowance, 2, '.', ''),
      'travel_hours' => number_format($travelHours, 2, '.', ''),
      'wage' => number_format($wage, 2, '.', ''),
    ];
    Log::debug('WorkEntry::updateWorkEntry storing');
    Database::hset($workEntryKey, $fieldsToStore);

    // Telemetry & logging for stored blob
    try {
      $v = \PayCal\Domain\Config\SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;
      Database::incr("telemetry:encryption:{$v}:server:blob_stored");
    } catch (\Throwable) {
    }
    Log::info('Stored encrypted_blob', "key={$workEntryKey} length=".strlen($fieldsToStore['encrypted_blob']));

    // Recalculate only the affected week to avoid O(year) scans on each save.
    Work::processWorkWeekContainingDate($userUUID, $workDate);
    EarningsCacheService::invalidateForUser($userUUID);

    return true;
  }

  /**
   * Gets a work entry from database.
   *
   * @param string $key Redis key to retrieve (example: work:userUUID:yyyy-mm-dd:siteUUID)
   *
    * @return array<string, mixed>|null
   */
  public static function getWorkEntry(string $key): ?array
  {
    // Handle both regular and archived key formats
    // Regular: work:UUID:DATE:SITE_ID (indices 0,1,2,3)
    // Archived: work:archived:UUID:DATE:SITE_ID (indices 0,1,2,3,4)
    $parts = explode(':', $key);
    
    // Determine if this is an archived key
    $isArchived = (isset($parts[1]) && 'archived' === $parts[1]);
    
    // Extract site ID and user UUID appropriately
    if ($isArchived) {
      $siteID = $parts[4] ?? null;
      $userUUID = $parts[2] ?? null;
    } else {
      $siteID = $parts[3] ?? null;
      $userUUID = $parts[1] ?? null;
    }
    
    // Guard against if null is in the key in place of valid Site ID
    if (null === $siteID || 'null' === (string) $siteID) {
      return [];
    }
    
    if (null === $userUUID) {
      return null;
    }
    
    $siteName = Sites::getSiteName($siteID, $userUUID);
    Log::debug('WorkEntry::getWorkEntry called');
    $workEntry = Database::hgetall($key);
    Log::debug('WorkEntry::getWorkEntry fetched');

    if (empty($workEntry)) {
      return null;
    }

    // Zero-knowledge read contract: encrypted payload is mandatory.
    $v = \PayCal\Domain\Config\SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;
    $blob = self::asString($workEntry['encrypted_blob'] ?? null);
    if ('' === $blob) {
      try {
        Database::incr("telemetry:encryption:{$v}:server:required_plaintext_rejected");
      } catch (\Throwable) {
      }
      Log::warn("WorkEntry::getWorkEntry - missing encrypted_blob for {$key}");

      return null;
    }

    $validation = self::validateEncryptedBlob($blob);
    if (!$validation['valid']) {
      try {
        Database::incr("telemetry:encryption:{$v}:server:blob_validation_failed");
      } catch (\Throwable) {
      }
      Log::warn("WorkEntry::getWorkEntry - encrypted_blob validation failed: {$validation['error']} for {$key}");

      return null;
    }

    try {
      Database::incr("telemetry:encryption:{$v}:server:decryption_attempt");
      Database::incr("telemetry:encryption:{$v}:server:decryption_failure");
    } catch (\Throwable) {
    }

    $normalized = self::normalizeWorkEntryPayload($workEntry);

    return [
      'date' => $isArchived ? ($parts[3] ?? '') : ($parts[2] ?? ''),
      'site_id' => $siteID,
      'site_name' => self::asString($normalized['site_name'] ?? $siteName),
      'hours' => is_numeric($normalized['hours'] ?? null) ? (float) $normalized['hours'] : 0.0,
      'regular_hours' => is_numeric($normalized['regular_hours'] ?? null) ? (float) $normalized['regular_hours'] : 0.0,
      'overtime_hours' => is_numeric($normalized['overtime_hours'] ?? null) ? (float) $normalized['overtime_hours'] : 0.0,
      'living_out_allowance' => is_numeric($normalized['living_out_allowance'] ?? null) ? (float) $normalized['living_out_allowance'] : 0.0,
      'travel_hours' => is_numeric($normalized['travel_hours'] ?? null) ? (float) $normalized['travel_hours'] : 0.0,
      'wage' => is_numeric($normalized['wage'] ?? null) ? (float) $normalized['wage'] : 0.0,
      'encrypted_blob' => $blob,
    ];
  }
}


