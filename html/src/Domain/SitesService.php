<?php declare(strict_types=1);

namespace PayCal\Domain;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\SiteStatus;

/**
 * SitesService.php
 *
 * Mid-tier service for site lifecycle operations.
 *
 * Why this exists:
 * - Keep controller actions thin by centralizing site validation and mutation logic.
 * - Coordinate related side effects (status changes, archive behavior, normalization).
 * - Enforce domain-level constraints before writing Redis records.
 */

/**
 * Site management service used by controllers and admin workflows.
 *
 * Internal guarantees:
 * - All write paths normalize and validate site payload fields.
 * - Bulk actions use explicit status/delete semantics.
 * - Archival behavior respects lock checks for recent work entries.
 */
final class SitesService
{
  /** @var array<string,bool> */
  private const VALID_PROVINCES = [
      'AB' => true,
      'BC' => true,
      'MB' => true,
      'NB' => true,
      'NL' => true,
      'NS' => true,
      'ON' => true,
      'PE' => true,
      'QC' => true,
      'SK' => true,
      'NT' => true,
      'NU' => true,
      'YT' => true,
  ];

  /**
   * Returns all sites a user owns.
   * Stored as: site:{userUUID}:{siteId}.
   *
   * @return array<int, array<string,string>>
   */
  public function get(string $userUUID): array
  {
    $sites = iterator_to_array(Sites::getInstance()->getSites($userUUID, 'all'));

    return array_values($sites);
  }

  /**
   * Update sites for a user.
   * $data may contain:
   *   - bulk_action (string)
   *   - sites (array of site rows)
   *   - site_ids (array).
   *
   * @param array<string, mixed> $data
   */
  public function update(string $userUUID, array $data): bool
  {
    // Handle bulk deletes - use proper archive workflow
    if (($data['bulk_action'] ?? '') === BulkAction::DELETE->value && !empty($data['selected_sites']) && is_array($data['selected_sites'])) {
      foreach ($data['selected_sites'] as $id) {
        $idStr = is_scalar($id) ? (string) $id : '';
        // Use the proper delete method which archives work entries
        $this->delete($userUUID, $idStr);
      }
    }

    // Handle bulk status changes
    if (in_array($data['bulk_action'] ?? '', [BulkAction::ACTIVE->value, BulkAction::INACTIVE->value], true) && !empty($data['selected_sites']) && is_array($data['selected_sites'])) {
      $newStatus = BulkAction::ACTIVE->value === $data['bulk_action'] ? SiteStatus::ACTIVE->value : SiteStatus::INACTIVE->value;
      foreach ($data['selected_sites'] as $id) {
        $idStr = is_scalar($id) ? (string) $id : '';
        Database::hset(Keys::SITE . ":{$userUUID}:{$idStr}", ['status' => $newStatus]);
      }
    }

    // Handle per-row site updates
    if (empty($data['sites']) || !is_array($data['sites'])) {
      return true;
    }

    foreach ($data['sites'] as $siteUUID => $row) {
      $row = (array) $row;
      $keys = array_map('strval', array_keys($row));
      $values = array_values($row);
      $row = array_combine($keys, $values) ?: [];
      if (!$this->validateSite($siteUUID, $row)) {
        continue;
      }

      $normalized = $this->normalizeSite($row);

      // Generate new site ID if it's a temporary "new_" ID
      if (str_starts_with($siteUUID, 'new_')) {
        $newSiteUUID = $this->generateSiteId();
        $key = "site:{$userUUID}:{$newSiteUUID}";
        $normalized['id'] = $newSiteUUID; // Store the ID in the data
      } else {
        $key = "site:{$userUUID}:{$siteUUID}";
      }

      foreach ($normalized as $field => $value) {
        Database::hset($key, [(string) $field => (string) $value]);
      }
    }

    return true;
  }

  /**
   * Archive a site by setting status to 'archived' and archiving its work entries.
   * Work entries are moved from work:UUID:DATE:SITE_ID to work:archived:UUID:DATE:SITE_ID.
   *
   * **Lock Enforcement:** Prevents archival of locked (recent) work entries.
   *
   * @return array{success: bool, archived_count: int, locked_entries: int} Success status, count of archived work entries, and count of locked entries (if blocked)
   */
  public function delete(string $userUUID, string $siteId): array
  {
    // Find all work entries for this site
    $workPattern = WorkEntryRepository::activePatternForSite($userUUID, $siteId);
    $workKeys = Database::scanKeys($workPattern);

    $lockedCount = 0;
    $archivedCount = 0;

    // Check all work entries for locks first - prevent archival if ANY are locked
    foreach ($workKeys as $oldKey) {
      // Extract the date portion from work:UUID:DATE:SITE_ID
      $parts = explode(':', $oldKey);
      if (count($parts) >= 4) {
        $date = $parts[2];

        // Check if entry is locked using WorkEntryLockService
        if (WorkEntryLockService::isLocked($date, $userUUID)) {
          ++$lockedCount;
        }
      }
    }

    // If any entries are locked, abort the entire deletion
    if ($lockedCount > 0) {
      return [
          'success' => false,
          'archived_count' => 0,
          'locked_entries' => $lockedCount,
      ];
    }

    // Archive each work entry by renaming the key
    foreach ($workKeys as $oldKey) {
      // Extract the date portion from work:UUID:DATE:SITE_ID
      $parts = explode(':', $oldKey);
      if (count($parts) >= 4) {
        if (WorkEntryRepository::archiveByKey($oldKey, $userUUID)) {
          ++$archivedCount;
        }
      }
    }

    // Set site status to 'archived' instead of deleting it
    $siteKey = "site:{$userUUID}:{$siteId}";
    Database::hset($siteKey, ['status' => SiteStatus::ARCHIVED->value]);

    return [
        'success' => true,
        'archived_count' => $archivedCount,
        'locked_entries' => 0,
    ];
  }

  /**
   * Permanently delete an archived site and all its archived work entries.
   * This is the "finality delete" - cannot be undone.
   *
   * **Lock Enforcement:** Prevents deletion of archived entries from locked periods.
   *
   * @return array{success: bool, deleted_work_count: int, locked_entries: int}
   */
  public function permanentDelete(string $userUUID, string $siteId): array
  {
    // Delete all archived work entries for this site
    $workPattern = WorkEntryRepository::archivedPatternForSite($userUUID, $siteId);
    $workKeys = Database::scanKeys($workPattern);

    $deletedWorkCount = 0;
    $lockedEntries = 0;

    foreach ($workKeys as $key) {
      if (WorkEntryRepository::deleteArchivedByKey($key, $userUUID)) {
        ++$deletedWorkCount;
      } else {
        ++$lockedEntries;
      }
    }

    if ($lockedEntries > 0) {
      return [
          'success' => false,
          'deleted_work_count' => 0,
          'locked_entries' => $lockedEntries,
      ];
    }

    // Delete the site record
    $siteKey = "site:{$userUUID}:{$siteId}";
    Database::unlink($siteKey);

    return [
        'success' => true,
        'deleted_work_count' => $deletedWorkCount,
        'locked_entries' => 0,
    ];
  }

  /**
   * Get summary of archived work entries for a site.
   *
   * @return array{count: int, total_earnings: float, total_hours: float, date_range: array{start: null|string, end: null|string}, entries: array<int, array<string, mixed>>}
   */
  public function getArchivedWorkSummary(string $userUUID, string $siteId): array
  {
    $pattern = WorkEntryRepository::archivedPatternForSite($userUUID, $siteId);
    $keys = Database::scanKeys($pattern);

    $totalEarnings = 0.0;
    $totalHours = 0.0;
    $dates = [];
    $entries = [];

    foreach ($keys as $key) {
      $data = Database::hgetall($key);

      if (empty($data)) {
        continue;
      }

      $gross = (float) ($data['gross'] ?? $data['g'] ?? 0);
      $hours = (float) ($data['hours'] ?? $data['h'] ?? 0);
      $date = $data['date'] ?? $data['d'] ?? '';
      $siteName = $data['site_name'] ?? $data['n'] ?? '';

      $totalEarnings += $gross;
      $totalHours += $hours;

      if ($date) {
        $dates[] = $date;
      }

      $entries[] = [
          'date' => $date,
          'site_name' => $siteName,
          'hours' => $hours,
          'gross' => $gross,
          'key' => $key,
      ];
    }

    sort($dates);

    return [
        'count' => count($keys),
        'total_earnings' => $totalEarnings,
        'total_hours' => $totalHours,
        'date_range' => [
            'start' => $dates[0] ?? null,
            'end' => $dates[count($dates) - 1] ?? null,
        ],
        'entries' => $entries,
    ];
  }

  /**
   * Create a new site.
   *
   * @param string               $userUUID User UUID
   * @param array<string, mixed> $data     Site data
   *
   * @return null|string Site ID on success, null on failure
   */
  public function create(string $userUUID, array $data): ?string
  {
    if (!$this->validateSite('new_site', $data)) {
      return null;
    }

    // Generate new site ID
    $siteId = $this->generateSiteId();

    // Normalize data
    $normalized = $this->normalizeSite($data);
    $normalized['id'] = $siteId;

    // Save to Redis
    $key = "site:{$userUUID}:{$siteId}";
    foreach ($normalized as $field => $value) {
      Database::hset($key, [(string) $field => (string) $value]);
    }

    return $siteId;
  }

  /**
   * Update a single site.
   * If status is changing to 'archived', also archive work entries.
   *
   * @param string               $userUUID User UUID
   * @param string               $siteId   Site ID
   * @param array<string, mixed> $data     Site data
   *
   * @return bool Success
   */
  public function updateSingle(string $userUUID, string $siteId, array $data): bool
  {
    if (!$this->validateSite($siteId, $data)) {
      return false;
    }

    // Get current site data to check for status change
    $key = "site:{$userUUID}:{$siteId}";
    $currentSite = Database::hgetall($key);
    $currentStatus = $currentSite['status'] ?? SiteStatus::ACTIVE->value;
    $newStatus = $data['status'] ?? SiteStatus::ACTIVE->value;

    // If status is changing to 'archived', archive the work entries
    if (SiteStatus::ARCHIVED->value !== $currentStatus && SiteStatus::ARCHIVED->value === $newStatus) {
      $workPattern = WorkEntryRepository::activePatternForSite($userUUID, $siteId);
      $workKeys = Database::scanKeys($workPattern);

      foreach ($workKeys as $oldKey) {
        WorkEntryRepository::archiveByKey($oldKey, $userUUID);
      }
    }

    // If status is changing back to 'active', unarchive the work entries
    if (SiteStatus::ARCHIVED->value === $currentStatus && SiteStatus::ACTIVE->value === $newStatus) {
      $archivedPattern = WorkEntryRepository::archivedPatternForSite($userUUID, $siteId);
      $archivedKeys = Database::scanKeys($archivedPattern);

      foreach ($archivedKeys as $oldKey) {
        WorkEntryRepository::restoreByKey($oldKey, $userUUID);
      }
    }

    // Normalize data
    $normalized = $this->normalizeSite($data);

    // Save to Redis
    foreach ($normalized as $field => $value) {
      Database::hset($key, [(string) $field => (string) $value]);
    }

    return true;
  }

  /**
   * Validate an incoming site row.
   * Adjust business rules as needed.
   *
   * @param string              $siteUUID The Site's UUID
   * @param array<string,mixed> $row
   */
  public function validateSite(string $siteUUID, array $row): bool
  {
    if ('' === $siteUUID) {
      return false;
    }

    $siteName = is_scalar($row['site_name'] ?? null)
      ? trim((string) $row['site_name'])
      : '';
    if ('' === $siteName) {
      return false;
    }

    $wage = is_scalar($row['wage'] ?? null)
      ? trim((string) $row['wage'])
      : '';
    if ('' === $wage || !is_numeric($wage) || (float) $wage < 0) {
      return false;
    }

    $loa = is_scalar($row['living_out_allowance'] ?? null)
      ? trim((string) $row['living_out_allowance'])
      : '0';
    if ('' !== $loa && (!is_numeric($loa) || (float) $loa < 0)) {
      return false;
    }

    $travelHours = is_scalar($row['travel_hours'] ?? null)
      ? trim((string) $row['travel_hours'])
      : '0';
    if ('' !== $travelHours && (!is_numeric($travelHours) || (float) $travelHours < 0)) {
      return false;
    }

    $status = is_scalar($row['status'] ?? null)
      ? strtolower(trim((string) $row['status']))
      : SiteStatus::ACTIVE->value;
    if (!in_array($status, [SiteStatus::ACTIVE->value, SiteStatus::INACTIVE->value, SiteStatus::ARCHIVED->value], true)) {
      return false;
    }

    $province = is_scalar($row['province'] ?? null)
      ? strtoupper(trim((string) $row['province']))
      : '';
    if ('' !== $province && !isset(self::VALID_PROVINCES[$province])) {
      return false;
    }

    return true;
  }

  /**
   * Normalize a site row before Redis storage.
   *
   * @param array<string,mixed> $row
   *
   * @return array<string,string>
   */
  public function normalizeSite(array $row): array
  {
    $siteName = is_scalar($row['site_name'] ?? null)
      ? trim((string) $row['site_name'])
      : '';

    $wage = is_scalar($row['wage'] ?? null)
      ? trim((string) $row['wage'])
      : '0';

    $loa = is_scalar($row['living_out_allowance'] ?? null)
      ? trim((string) $row['living_out_allowance'])
      : '0';

    $travelHours = is_scalar($row['travel_hours'] ?? null)
      ? trim((string) $row['travel_hours'])
      : '0';

    $province = is_scalar($row['province'] ?? null)
      ? strtoupper(trim((string) $row['province']))
      : '';

    $status = is_scalar($row['status'] ?? null)
      ? strtolower(trim((string) $row['status']))
      : SiteStatus::ACTIVE->value;
    if (!in_array($status, [SiteStatus::ACTIVE->value, SiteStatus::INACTIVE->value, SiteStatus::ARCHIVED->value], true)) {
      $status = SiteStatus::ACTIVE->value;
    }

    return [
        'site_name' => $siteName,
        'wage' => $wage,
        'living_out_allowance' => $loa,
        'travel_hours' => $travelHours,
        'province' => $province,
        'status' => $status,
    ];
  }

  /**
   * Find orphaned work entries (work entries with no corresponding site).
   * Returns grouped data by site_id with recovered site info from work entries.
   *
   * @return array{orphaned_groups: list<array{site_id: string, site_name: string, count: int<1, max>, dates: non-empty-list<string>, total_hours: float, total_earnings: float}>, total_count: int<0, max>}
   */
  public function findOrphanedWork(string $userUUID): array
  {
    // Get all existing site IDs
    $siteKeys = Database::scanKeys("site:{$userUUID}:*");
    $existingSiteIds = [];
    foreach ($siteKeys as $key) {
      $parts = explode(':', $key);
      if (count($parts) >= 3) {
        $existingSiteIds[$parts[2]] = true;
      }
    }

    // Get all work entries
    $workPattern = WorkEntryRepository::activePatternForUser($userUUID);
    $workKeys = Database::scanKeys($workPattern);

    $orphanedGroups = [];
    $totalCount = 0;

    foreach ($workKeys as $workKey) {
      // Extract site_id from work:UUID:DATE:SITE_ID
      $parts = explode(':', $workKey);
      if (count($parts) < 4) {
        continue;
      }

      $siteId = $parts[3];

      // Check if site exists
      if (isset($existingSiteIds[$siteId])) {
        continue; // Not orphaned
      }

      // This work entry is orphaned
      $workData = Database::hgetall($workKey);

      if (!isset($orphanedGroups[$siteId])) {
        // Initialize group with data from first work entry
        $orphanedGroups[$siteId] = [
            'site_id' => $siteId,
            'site_name' => $workData['site_name'] ?? $workData['n'] ?? 'Unknown Site',
            'count' => 0,
            'dates' => [],
            'total_hours' => 0.0,
            'total_earnings' => 0.0,
        ];
      }

      // Accumulate data
      ++$orphanedGroups[$siteId]['count'];
      $orphanedGroups[$siteId]['dates'][] = $workData['date'] ?? $workData['d'] ?? '';
      $orphanedGroups[$siteId]['total_hours'] += (float) ($workData['hours'] ?? $workData['h'] ?? 0);
      $orphanedGroups[$siteId]['total_earnings'] += (float) ($workData['gross'] ?? $workData['g'] ?? 0);
      ++$totalCount;
    }

    // Sort dates and keep only first 5 for display
    foreach ($orphanedGroups as &$group) {
      sort($group['dates']);
      $group['date_range'] = $group['dates'][0].' to '.end($group['dates']);
      $group['sample_dates'] = array_slice($group['dates'], 0, 5);
      unset($group['dates']); // Remove full dates array
    }

    return [
        'orphaned_groups' => array_values($orphanedGroups),
        'total_count' => $totalCount,
    ];
  }

  /**
   * Create a site from orphaned work data and bind all orphaned entries.
   *
   * @param string              $orphanedSiteId The old site ID from orphaned work entries
   * @param array<string,mixed> $siteData       New site data (site_name, wage, etc.)
   *
   * @return array{success: bool, new_site_id: string, bound_count: int}
   */
  public function recoverOrphanedWork(string $userUUID, string $orphanedSiteId, array $siteData): array
  {
    // Create new site
    $newSiteId = $this->generateSiteId();
    $siteKey = "site:{$userUUID}:{$newSiteId}";

    $normalized = $this->normalizeSite($siteData);
    $normalized['id'] = $newSiteId;

    foreach ($normalized as $field => $value) {
      Database::hset($siteKey, [(string) $field => (string) $value]);
    }

    // Find all orphaned work entries for this site_id
    $workPattern = WorkEntryRepository::activePatternForSite($userUUID, $orphanedSiteId);
    $workKeys = Database::scanKeys($workPattern);

    $boundCount = 0;

    foreach ($workKeys as $oldKey) {
      // Extract date from work:UUID:DATE:OLD_SITE_ID
      $parts = explode(':', $oldKey);
      if (count($parts) < 4) {
        continue;
      }

      $date = $parts[2];

      // Get work data
      $workData = Database::hgetall($oldKey);
      $workData = WorkEntry::normalizeWorkEntryPayload($workData);

      // Update site_id and site_name in work data
      $workData['site_id'] = $newSiteId;
      $workData['site_name'] = $normalized['site_name'];

      // Persist through repository to ensure lock/validation gates remain consistent.
      if (WorkEntryRepository::save($workData, $userUUID)) {
        Database::unlink($oldKey);
        ++$boundCount;
      }
    }

    return [
        'success' => true,
        'new_site_id' => $newSiteId,
        'bound_count' => $boundCount,
    ];
  }

  /**
   * Generates a legacy-compatible site identifier.
   */
  private function generateSiteId(): string
  {
    // Keep legacy width (S + 9 hex chars) for compatibility with validation and existing keys.
    return 'S' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 9));
  }
}
