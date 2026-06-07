<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * OrganizationSiteLinkResolver
 *
 * Normalizes and resolves whether a work entry belongs to an organization's
 * linked site set using deterministic matching tiers.
 */
final class OrganizationSiteLinkResolver
{
  /**
   * Build organization site link context used for work-entry matching.
   *
   * @return array{
   *   ref_set: array<string, true>,
   *   id_refs: array<string, list<string>>,
   *   normalized_name_set: array<string, true>
   * }
   */
  public static function buildContext(string $orgId): array
  {
    $orgSiteRefs = Database::smembers(Keys::ORGANIZATION_SITE . ':' . $orgId);

    /** @var array<string, true> $orgSiteRefSet */
    $orgSiteRefSet = [];
    /** @var array<string, list<string>> $orgSiteIdRefs */
    $orgSiteIdRefs = [];
    /** @var array<string, true> $orgSiteNameSet */
    $orgSiteNameSet = [];

    foreach ($orgSiteRefs as $siteRefRaw) {
      $siteRef = (string) $siteRefRaw;
      $parts = explode(':', $siteRef, 2);
      if (count($parts) !== 2) {
        continue;
      }

      $siteOwnerUUID = (string) $parts[0];
      $siteId = (string) $parts[1];
      if ($siteId === '') {
        continue;
      }

      $orgSiteRefSet[$siteRef] = true;
      if (!isset($orgSiteIdRefs[$siteId])) {
        $orgSiteIdRefs[$siteId] = [];
      }
      $orgSiteIdRefs[$siteId][] = $siteRef;

      $siteNameRaw = (string) Database::hget(Keys::SITE . ':' . $siteOwnerUUID . ':' . $siteId, 'site_name');
      $siteNameNormalized = self::normalizeSiteName($siteNameRaw);
      if ($siteNameNormalized !== '') {
        $orgSiteNameSet[$siteNameNormalized] = true;
      }
    }

    return [
      'ref_set' => $orgSiteRefSet,
      'id_refs' => $orgSiteIdRefs,
      'normalized_name_set' => $orgSiteNameSet,
    ];
  }

  /**
   * Resolve site link matching strategy.
   *
    * @param array{
    *   ref_set: array<string, true>,
    *   id_refs: array<string, list<string>>,
    *   normalized_name_set: array<string, true>
    * } $context
    *
   * Returns one of: owner_and_site | unique_site_id | site_name | no_match
   */
  public static function resolveMatchStrategy(
    array $context,
    string $siteId,
    string $siteOwnerUUID,
    string $entrySiteName
  ): string {
    if ($siteId === '') {
      return 'no_match';
    }

    $siteRefCandidate = $siteOwnerUUID . ':' . $siteId;
    $refSet = $context['ref_set'];
    $idRefs = $context['id_refs'];
    $nameSet = $context['normalized_name_set'];

    if (isset($refSet[$siteRefCandidate])) {
      return 'owner_and_site';
    }

    if (isset($idRefs[$siteId]) && count((array) $idRefs[$siteId]) === 1) {
      return 'unique_site_id';
    }

    if ($entrySiteName !== '') {
      $normalizedEntrySiteName = self::normalizeSiteName($entrySiteName);
      if ($normalizedEntrySiteName !== '' && isset($nameSet[$normalizedEntrySiteName])) {
        return 'site_name';
      }
    }

    return 'no_match';
  }

  /**
   * Conservative site-name normalization for legacy fallback matching.
   */
  public static function normalizeSiteName(string $name): string
  {
    $normalized = strtolower(trim($name));
    if ($normalized === '') {
      return '';
    }

    $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
    return preg_replace('/[^a-z0-9]+/', '', $normalized) ?? $normalized;
  }
}
