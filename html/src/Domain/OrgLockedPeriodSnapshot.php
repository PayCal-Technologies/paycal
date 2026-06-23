<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * Snapshots locked-period org payroll metrics for org-owned org-only work only.
 */
final class OrgLockedPeriodSnapshot
{
  /**
   * @param array<string, array<string, string>> $workEntries
   * @param array<string, string> $connection
   * @param array<string, array{
   *   site_owner_uuid: string,
   *   site_id: string,
   *   site_hash: array<string, string>
   * }> $orgSiteIndex
   */
  public static function ensureSnapshotForMember(
    string $businessId,
    string $businessOwnerUuid,
    string $memberUuid,
    int $year,
    array $workEntries,
    array $connection,
    array $orgSiteIndex,
  ): void {
    $businessId = trim($businessId);
    $memberUuid = trim($memberUuid);
    if ($businessId === '' || $memberUuid === '') {
      return;
    }

    if (!BusinessWorkVisibilityPolicy::canAggregateForOrg(
      $businessId,
      $memberUuid,
      $connection,
      $orgSiteIndex,
    )) {
      return;
    }

    if (OrgLockedPeriodMetrics::has($businessId, $memberUuid, $year)) {
      return;
    }

    if (!self::yearHasLockedDates($memberUuid, $year)) {
      return;
    }

    $lockedSnapshot = self::collectLockedOrgWork(
      $businessId,
      $businessOwnerUuid,
      $memberUuid,
      $year,
      $workEntries,
      $connection,
      $orgSiteIndex,
    );
    $summary = self::buildSummaryFromSnapshot($lockedSnapshot, $year);

    if ($summary['ytd_gross'] <= 0.0 && $summary['total_hours'] <= 0.0) {
      return;
    }

    OrgLockedPeriodMetrics::put(
      $businessId,
      $memberUuid,
      $year,
      $summary,
      $connection,
      $orgSiteIndex,
    );
  }

  /**
   * @param array{
   *   ytd_gross: float,
   *   total_hours: float,
   *   reg_hours: float,
   *   ot_hours: float,
   *   trailing_baseline: float
   * } $locked
   * @param array{
   *   ytd_gross: float,
   *   total_hours: float,
   *   reg_hours: float,
   *   ot_hours: float,
   *   trailing_baseline: float
   * } $live
   * @return array{
   *   ytd_gross: float,
   *   total_hours: float,
   *   reg_hours: float,
   *   ot_hours: float,
   *   trailing_baseline: float
   * }
   */
  public static function mergeSummaries(array $locked, array $live): array
  {
    return [
      'ytd_gross' => round($locked['ytd_gross'] + $live['ytd_gross'], 2),
      'total_hours' => round($locked['total_hours'] + $live['total_hours'], 2),
      'reg_hours' => round($locked['reg_hours'] + $live['reg_hours'], 2),
      'ot_hours' => round($locked['ot_hours'] + $live['ot_hours'], 2),
      'trailing_baseline' => round(max($locked['trailing_baseline'], $live['trailing_baseline']), 2),
    ];
  }

  /**
   * Year has locked dates.
   */
  private static function yearHasLockedDates(string $memberUuid, int $year): bool
  {
    $lockBoundary = WorkEntryLockService::getLockBoundaryDate($memberUuid);
    $yearStart = sprintf('%04d-01-01', $year);

    return $lockBoundary > $yearStart;
  }

  /**
   * @param array<string, array<string, string>> $workEntries
   * @param array<string, string> $connection
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
  private static function collectLockedOrgWork(
    string $businessId,
    string $businessOwnerUuid,
    string $memberUuid,
    int $year,
    array $workEntries,
    array $connection,
    array $orgSiteIndex,
  ): array {
    /** @var array<int, array{reg_hours: float, ot_hours: float, gross: float}> $byYear */
    $byYear = [];
    /** @var array<int, int> $grossByYearCents */
    $grossByYearCents = [];

    foreach ($workEntries as $workKey => $entry) {
      $keyParts = explode(':', (string) $workKey);
      $isArchived = isset($keyParts[1]) && $keyParts[1] === 'archived';
      $date = $isArchived
        ? (isset($keyParts[3]) && strlen((string) $keyParts[3]) >= 10 ? (string) $keyParts[3] : (string) ($entry['date'] ?? ''))
        : (isset($keyParts[2]) && strlen((string) $keyParts[2]) >= 10 ? (string) $keyParts[2] : (string) ($entry['date'] ?? ''));

      if ($date === '' || strlen($date) < 4) {
        continue;
      }

      if (!WorkEntryLockService::isLocked($date, $memberUuid)) {
        continue;
      }

      $entryYear = (int) substr($date, 0, 4);
      if ($entryYear !== $year) {
        continue;
      }

      $decision = BusinessWorkVisibilityPolicy::evaluateWorkEntry(
        $businessId,
        $businessOwnerUuid,
        $memberUuid,
        (string) $workKey,
        $entry,
        $connection,
        $orgSiteIndex,
      );
      if (!$decision['allowed']) {
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
  private static function buildSummaryFromSnapshot(array $workSnapshot, int $year): array
  {
    $yearData = $workSnapshot['by_year'][$year] ?? [];
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
   * Resolve business owner UUID.
   */
  public static function resolveBusinessOwnerUuid(string $businessId): string
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return '';
    }

    return trim((string) Database::hget(Keys::BUSINESS . ':' . $businessId, 'owner_uuid'));
  }
}
