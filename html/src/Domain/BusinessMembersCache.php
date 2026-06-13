<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * BusinessMembersCache.php
 *
 * Purpose: Materialized server-side cache for the business members grid
 * financial summary columns (YTD gross, hours, trailing baseline).
 *
 * Developer notes:
 * - The members financial summary is the dominant cost of the members grid
 *   (full work-history aggregation per member). This cache stores the
 *   computed per-member summaries under business:cache:members:{businessId}.
 * - Membership and site-link mutations invalidate eagerly (DEL via
 *   setRelationship and link/unlink). Work-entry edits invalidate only
 *   financial segments (member work + this key) via invalidateFinancialData.
 * - Member identity/ACL data is intentionally NOT cached: every request
 *   still runs the gated BusinessDiscoveryService::listRelationships path.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */
final class BusinessMembersCache
{
  public const TTL_SECONDS = 900;
  private const SCHEMA_VERSION = 2;

  /**
   * Fetch the cached per-member financial summaries for a business/year.
   *
   * Returns null on miss, schema mismatch, or year mismatch.
   *
   * @return array<string, array<string, float>>|null Member UUID => summary map
   */
  public static function get(string $businessId, int $year): ?array
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return null;
    }

    $raw = Database::get(Keys::businessMembersCache($businessId));
    if ($raw === '') {
      return null;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      return null;
    }

    $schemaRaw = $decoded['schema'] ?? null;
    if (!is_scalar($schemaRaw) || (int) $schemaRaw !== self::SCHEMA_VERSION) {
      return null;
    }

    $yearRaw = $decoded['year'] ?? null;
    if (!is_scalar($yearRaw) || (int) $yearRaw !== $year) {
      return null;
    }

    $summaries = $decoded['summaries'] ?? null;
    if (!is_array($summaries)) {
      return null;
    }

    $normalized = [];
    foreach ($summaries as $memberUuid => $summary) {
      if (!is_string($memberUuid) || !is_array($summary)) {
        continue;
      }

      $normalized[$memberUuid] = [
        'ytd_gross' => is_numeric($summary['ytd_gross'] ?? null) ? (float) $summary['ytd_gross'] : 0.0,
        'total_hours' => is_numeric($summary['total_hours'] ?? null) ? (float) $summary['total_hours'] : 0.0,
        'reg_hours' => is_numeric($summary['reg_hours'] ?? null) ? (float) $summary['reg_hours'] : 0.0,
        'ot_hours' => is_numeric($summary['ot_hours'] ?? null) ? (float) $summary['ot_hours'] : 0.0,
        'trailing_baseline' => is_numeric($summary['trailing_baseline'] ?? null) ? (float) $summary['trailing_baseline'] : 0.0,
      ];
    }

    if ($normalized === [] && BusinessWorkspaceCache::indexedMemberCount($businessId) > 0) {
      self::invalidate($businessId);

      return null;
    }

    return $normalized;
  }

  /**
   * Store the computed per-member financial summaries.
   *
   * @param array<string, array<string, float>> $summaries Member UUID => summary map
   */
  public static function put(string $businessId, int $year, array $summaries): void
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return;
    }

    if ($summaries === [] && BusinessWorkspaceCache::indexedMemberCount($businessId) > 0) {
      return;
    }

    $payload = json_encode([
      'schema' => self::SCHEMA_VERSION,
      'year' => $year,
      'generated_at' => date('c'),
      'summaries' => $summaries,
    ]);

    if (!is_string($payload)) {
      return;
    }

    Database::set(Keys::businessMembersCache($businessId), $payload, self::TTL_SECONDS);
  }

  /**
   * Drop the cache for a business (call on membership/site-link mutations).
   */
  public static function invalidate(string $businessId): void
  {
    $businessId = trim($businessId);
    if ($businessId === '') {
      return;
    }

    Database::unlink(Keys::businessMembersCache($businessId));
  }
}
