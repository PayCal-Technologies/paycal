<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * Immutable org locked-period metrics (Redis hash org_locked_period_metrics:{org}:{year}).
 */
final class OrgLockedPeriodMetrics
{
  /**
   * @return array{
   *   ytd_gross: float,
   *   total_hours: float,
   *   reg_hours: float,
   *   ot_hours: float,
   *   trailing_baseline: float
   * }|null
   */
  public static function get(string $businessId, string $memberUuid, int $year): ?array
  {
    $businessId = trim($businessId);
    $memberUuid = trim($memberUuid);
    if ($businessId === '' || $memberUuid === '') {
      return null;
    }

    $raw = Database::hget(Keys::orgLockedPeriodMetrics($businessId, $year), $memberUuid);
    if ($raw === '') {
      return null;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      return null;
    }

    /** @var array<string, mixed> $summary */
    $summary = $decoded;

    return self::normalizeSummary($summary);
  }

  /**
   * @param array{
   *   ytd_gross: float,
   *   total_hours: float,
   *   reg_hours: float,
   *   ot_hours: float,
   *   trailing_baseline: float
   * } $summary
   * @param array<string, string> $relationship
   * @param array<string, array{
   *   site_owner_uuid: string,
   *   site_id: string,
   *   site_hash: array<string, string>
   * }> $orgSiteIndex
   */
  public static function put(
    string $businessId,
    string $memberUuid,
    int $year,
    array $summary,
    array $relationship = [],
    array $orgSiteIndex = [],
  ): void {
    $businessId = trim($businessId);
    $memberUuid = trim($memberUuid);
    if ($businessId === '' || $memberUuid === '') {
      return;
    }

    if (!BusinessWorkVisibilityPolicy::canAggregateForOrg($businessId, $memberUuid, $relationship, $orgSiteIndex)) {
      return;
    }

    $encoded = json_encode(self::normalizeSummary($summary));
    if (!is_string($encoded)) {
      return;
    }

    Database::hset(Keys::orgLockedPeriodMetrics($businessId, $year), [$memberUuid => $encoded]);
  }

  /**
   * TODO: Document has.
   */
  public static function has(string $businessId, string $memberUuid, int $year): bool
  {
    return self::get($businessId, $memberUuid, $year) !== null;
  }

  /**
   * @param array<string, mixed> $summary
   * @return array{
   *   ytd_gross: float,
   *   total_hours: float,
   *   reg_hours: float,
   *   ot_hours: float,
   *   trailing_baseline: float
   * }
   */
  private static function normalizeSummary(array $summary): array
  {
    return [
      'ytd_gross' => is_numeric($summary['ytd_gross'] ?? null) ? (float) $summary['ytd_gross'] : 0.0,
      'total_hours' => is_numeric($summary['total_hours'] ?? null) ? (float) $summary['total_hours'] : 0.0,
      'reg_hours' => is_numeric($summary['reg_hours'] ?? null) ? (float) $summary['reg_hours'] : 0.0,
      'ot_hours' => is_numeric($summary['ot_hours'] ?? null) ? (float) $summary['ot_hours'] : 0.0,
      'trailing_baseline' => is_numeric($summary['trailing_baseline'] ?? null) ? (float) $summary['trailing_baseline'] : 0.0,
    ];
  }
}
