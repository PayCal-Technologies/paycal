<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * Batch financial summaries for business members (YTD earnings and hours).
 *
 * Only org-owned org-only work enters rollups; personal/delegated work stays private.
 */
final class BusinessMembersFinancialSummary
{
  /** Members per SCAN + pipeline batch; keeps memory bounded while cutting round trips. */
  private const MEMBER_FETCH_BATCH_SIZE = MemberWorkEntriesFetcher::MEMBER_FETCH_BATCH_SIZE;

  /**
   * @param list<string> $memberUuids
   * @param bool $fresh Bypass the materialized cache (auditor/SOC verification path).
   * @return array<string, array{
   *   ytd_gross: float,
   *   total_hours: float,
   *   reg_hours: float,
   *   ot_hours: float,
   *   trailing_baseline: float
   * }>
   */
  public function forBusinessMembers(
    string $businessId,
    array $memberUuids,
    ?int $year = null,
    bool $fresh = false,
    bool $materializeLockedSnapshots = false,
    bool $cacheOnly = false,
  ): array {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return [];
    }

    $year = $year ?? (int) date('Y');
    if ($year < 2000 || $year > 2100) {
      $year = (int) date('Y');
    }

    $memberSet = [];
    foreach ($memberUuids as $memberUuid) {
      $normalized = trim($memberUuid);
      if ($normalized !== '') {
        $memberSet[$normalized] = true;
      }
    }

    if ($memberSet === []) {
      return [];
    }

    if (!$fresh) {
      $cached = BusinessMembersCache::get($businessId, $year);
      if ($cached !== null) {
        $summaries = [];
        $complete = true;
        foreach (array_keys($memberSet) as $memberUuid) {
          if (!isset($cached[$memberUuid])) {
            $complete = false;
            break;
          }
          $summaries[$memberUuid] = $this->normalizeSummary($cached[$memberUuid]);
        }

        if ($complete) {
          return $summaries;
        }

        if ($cacheOnly) {
          foreach (array_keys($memberSet) as $memberUuid) {
            if (!isset($summaries[$memberUuid])) {
              $summaries[$memberUuid] = $this->emptyMemberSummary();
            }
          }

          return $summaries;
        }
      } elseif ($cacheOnly) {
        return $this->emptySummariesForMembers(array_keys($memberSet));
      }
    }

    $businessOwnerUuid = OrgLockedPeriodSnapshot::resolveBusinessOwnerUuid($businessId);
    $orgSiteIndex = BusinessWorkVisibilityPolicy::buildOrgSiteIndex($businessId);
    $relationshipsByMember = self::loadRelationships($businessId, array_keys($memberSet));

    $summaries = [];
    $cachedMemberWork = BusinessWorkspaceCache::getMemberWork($businessId);
    foreach (array_chunk(array_keys($memberSet), self::MEMBER_FETCH_BATCH_SIZE) as $batch) {
      $workEntriesByMember = self::resolveBatchWorkEntries($batch, $cachedMemberWork);
      foreach ($batch as $memberUuid) {
        $relationship = $relationshipsByMember[$memberUuid] ?? [];
        $memberWork = $workEntriesByMember[$memberUuid] ?? [];

        if (
          $materializeLockedSnapshots
          && BusinessWorkVisibilityPolicy::canAggregateForOrg(
            $businessId,
            $memberUuid,
            $relationship,
            $orgSiteIndex,
          )
        ) {
          OrgLockedPeriodSnapshot::ensureSnapshotForMember(
            $businessId,
            $businessOwnerUuid,
            $memberUuid,
            $year,
            $memberWork,
            $relationship,
            $orgSiteIndex,
          );
        }

        $workSnapshot = $this->collectMemberOrgWork(
          $businessId,
          $businessOwnerUuid,
          $memberUuid,
          $memberWork,
          $relationship,
          $orgSiteIndex,
        );
        $liveSummary = $this->buildSummaryFromSnapshot($workSnapshot, $year);

        $lockedSummary = BusinessWorkVisibilityPolicy::canAggregateForOrg(
          $businessId,
          $memberUuid,
          $relationship,
          $orgSiteIndex,
        ) ? OrgLockedPeriodMetrics::get($businessId, $memberUuid, $year) : null;

        $summaries[$memberUuid] = $lockedSummary !== null
          ? OrgLockedPeriodSnapshot::mergeSummaries($lockedSummary, $liveSummary)
          : $liveSummary;
      }
    }

    BusinessMembersCache::put($businessId, $year, $summaries);

    return $summaries;
  }

  /**
   * @param array<string, float> $summary
   * @return array{
   *   ytd_gross: float,
   *   total_hours: float,
   *   reg_hours: float,
   *   ot_hours: float,
   *   trailing_baseline: float
   * }
   */
  private function normalizeSummary(array $summary): array
  {
    return [
      'ytd_gross' => is_numeric($summary['ytd_gross'] ?? null) ? (float) $summary['ytd_gross'] : 0.0,
      'total_hours' => is_numeric($summary['total_hours'] ?? null) ? (float) $summary['total_hours'] : 0.0,
      'reg_hours' => is_numeric($summary['reg_hours'] ?? null) ? (float) $summary['reg_hours'] : 0.0,
      'ot_hours' => is_numeric($summary['ot_hours'] ?? null) ? (float) $summary['ot_hours'] : 0.0,
      'trailing_baseline' => is_numeric($summary['trailing_baseline'] ?? null) ? (float) $summary['trailing_baseline'] : 0.0,
    ];
  }

  /**
   * @return array{
   *   ytd_gross: float,
   *   total_hours: float,
   *   reg_hours: float,
   *   ot_hours: float,
   *   trailing_baseline: float
   * }
   */
  private function emptyMemberSummary(): array
  {
    return [
      'ytd_gross' => 0.0,
      'total_hours' => 0.0,
      'reg_hours' => 0.0,
      'ot_hours' => 0.0,
      'trailing_baseline' => 0.0,
    ];
  }

  /**
   * @param list<string> $memberUuids
   * @return array<string, array{
   *   ytd_gross: float,
   *   total_hours: float,
   *   reg_hours: float,
   *   ot_hours: float,
   *   trailing_baseline: float
   * }>
   */
  private function emptySummariesForMembers(array $memberUuids): array
  {
    $summaries = [];
    foreach ($memberUuids as $memberUuid) {
      $memberUuid = trim($memberUuid);
      if ($memberUuid === '') {
        continue;
      }
      $summaries[$memberUuid] = $this->emptyMemberSummary();
    }

    return $summaries;
  }

  /**
   * @param array{
   *   by_year: array<int, array{reg_hours: float, ot_hours: float, gross: float}>,
   *   gross_by_year_cents: array<int, int>
   * } $workSnapshot
   * @return array{
   *   ytd_gross: float,
   *   total_hours: float,
   *   reg_hours: float,
   *   ot_hours: float,
   *   trailing_baseline: float
   * }
   */
  private function buildSummaryFromSnapshot(array $workSnapshot, int $year): array
  {
    $yearData = $workSnapshot['by_year'][$year] ?? null;
    if (!is_array($yearData)) {
      $yearData = [];
    }
    $regHours = round(is_numeric($yearData['reg_hours'] ?? null) ? (float) $yearData['reg_hours'] : 0.0, 2);
    $otHours = round(is_numeric($yearData['ot_hours'] ?? null) ? (float) $yearData['ot_hours'] : 0.0, 2);

    $grossByYearCents = $workSnapshot['gross_by_year_cents'];
    $trailingWindowYears = array_values(array_filter(
      [$year - 2, $year - 1, $year],
      static fn (int $candidate): bool => array_key_exists($candidate, $grossByYearCents),
    ));
    $trailingGross = array_map(
      static fn (int $candidate): int => (int) ($grossByYearCents[$candidate] ?? 0),
      $trailingWindowYears,
    );
    $trailingAverageCents = $trailingGross === []
      ? 0
      : (int) round(array_sum($trailingGross) / max(1, count($trailingGross)));

    return [
      'ytd_gross' => round((float) ($yearData['gross'] ?? 0), 2),
      'total_hours' => round($regHours + $otHours, 2),
      'reg_hours' => $regHours,
      'ot_hours' => $otHours,
      'trailing_baseline' => round($trailingAverageCents / 100, 2),
    ];
  }

  /**
   * Aggregate org-visible work entries for a member (org-owned org-only sites only).
   *
   * @param array<string, array<string, string>> $workEntries Work key => entry hash
   * @param array<string, string> $relationship
   * @param array<string, array{
   *   site_owner_uuid: string,
   *   site_id: string,
   *   site_hash: array<string, string>
   * }> $orgSiteIndex
   * @return array{
   *   by_year: array<int, array{reg_hours: float, ot_hours: float, gross: float}>,
   *   gross_by_year_cents: array<int, int>
   * }
   */
  private function collectMemberOrgWork(
    string $businessId,
    string $businessOwnerUuid,
    string $memberUuid,
    array $workEntries,
    array $relationship,
    array $orgSiteIndex,
  ): array {
    if (!BusinessWorkVisibilityPolicy::canAggregateForOrg(
      $businessId,
      $memberUuid,
      $relationship,
      $orgSiteIndex,
    )) {
      return [
        'by_year' => [],
        'gross_by_year_cents' => [],
      ];
    }

    /** @var array<int, array{reg_hours: float, ot_hours: float, gross: float}> $byYear */
    $byYear = [];
    /** @var array<int, int> $grossByYearCents */
    $grossByYearCents = [];
    $lockBoundary = WorkEntryLockService::getLockBoundaryDate($memberUuid);

    foreach ($workEntries as $workKey => $entry) {
      $keyParts = explode(':', (string) $workKey);
      $isArchived = isset($keyParts[1]) && $keyParts[1] === 'archived';
      $date = $isArchived
        ? (isset($keyParts[3]) && strlen((string) $keyParts[3]) >= 10 ? (string) $keyParts[3] : (string) ($entry['date'] ?? ''))
        : (isset($keyParts[2]) && strlen((string) $keyParts[2]) >= 10 ? (string) $keyParts[2] : (string) ($entry['date'] ?? ''));

      if ($date === '' || strlen($date) < 4) {
        continue;
      }

      if ($date < $lockBoundary) {
        continue;
      }

      $decision = BusinessWorkVisibilityPolicy::evaluateWorkEntry(
        $businessId,
        $businessOwnerUuid,
        $memberUuid,
        (string) $workKey,
        $entry,
        $relationship,
        $orgSiteIndex,
      );
      if (!$decision['allowed']) {
        continue;
      }

      $entryYear = (int) substr($date, 0, 4);
      if ($entryYear < 2000 || $entryYear > 2100) {
        continue;
      }

      if (!isset($byYear[$entryYear])) {
        $byYear[$entryYear] = [
          'reg_hours' => 0.0,
          'ot_hours' => 0.0,
          'gross' => 0.0,
        ];
      }

      $entryReg = (float) ($entry['regular_hours'] ?? $entry['r'] ?? 0);
      $entryOt = (float) ($entry['overtime_hours'] ?? $entry['o'] ?? 0);
      $entryGross = (float) ($entry['gross'] ?? $entry['g'] ?? 0);

      $byYear[$entryYear]['reg_hours'] += $entryReg;
      $byYear[$entryYear]['ot_hours'] += $entryOt;
      $byYear[$entryYear]['gross'] += $entryGross;
    }

    $normalizedByYear = [];
    foreach ($byYear as $entryYear => $values) {
      $normalizedByYear[(int) $entryYear] = [
        'reg_hours' => round((float) $values['reg_hours'], 2),
        'ot_hours' => round((float) $values['ot_hours'], 2),
        'gross' => round((float) $values['gross'], 2),
      ];
      $grossByYearCents[(int) $entryYear] = Money::dollarsToCents((string) $values['gross']);
    }

    return [
      'by_year' => $normalizedByYear,
      'gross_by_year_cents' => $grossByYearCents,
    ];
  }

  /**
   * TODO: Document formatCurrency.
   */
  public function formatCurrency(float $amount): string
  {
    return '$' . number_format($amount, 2, '.', ',');
  }

  /**
   * TODO: Document formatHours.
   */
  public function formatHours(float $hours): string
  {
    return number_format($hours, 2, '.', ',');
  }

  /**
   * @param list<string> $batch
   * @param array<string, array<string, array<string, string>>>|null $cachedMemberWork
   * @return array<string, array<string, array<string, string>>>
   */
  private static function resolveBatchWorkEntries(array $batch, ?array $cachedMemberWork): array
  {
    if ($cachedMemberWork === null) {
      return MemberWorkEntriesFetcher::fetchForMembers($batch);
    }

    $resolved = [];
    $missing = [];
    foreach ($batch as $memberUuid) {
      $entries = $cachedMemberWork[$memberUuid] ?? null;
      if (is_array($entries)) {
        $resolved[$memberUuid] = $entries;
      } else {
        $missing[] = $memberUuid;
      }
    }

    if ($missing !== []) {
      $fetched = MemberWorkEntriesFetcher::fetchForMembers($missing);
      foreach ($fetched as $memberUuid => $entries) {
        $resolved[$memberUuid] = $entries;
      }
    }

    return $resolved;
  }

  /**
   * @param list<string> $memberUuids
   * @return array<string, array<string, string>>
   */
  private static function loadRelationships(string $businessId, array $memberUuids): array
  {
    $keys = [];
    foreach ($memberUuids as $memberUuid) {
      $memberUuid = trim($memberUuid);
      if ($memberUuid === '') {
        continue;
      }
      $keys[$memberUuid] = Keys::BUSINESS_RELATIONSHIP . ':' . $businessId . ':' . $memberUuid;
    }

    if ($keys === []) {
      return [];
    }

    $hashes = Database::pipelineHgetall(array_values($keys));
    $relationships = [];
    foreach ($keys as $memberUuid => $key) {
      $hash = $hashes[$key] ?? [];
      $relationships[$memberUuid] = $hash;
    }

    return $relationships;
  }
}
