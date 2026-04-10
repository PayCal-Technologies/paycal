<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\SiteStatus;

/**
 * Sites.php
 *
 * Purpose: Legacy site domain/orchestration helper for site retrieval, site
 * state mutation, and work-site-related operations.
 *
 * Developer notes:
 * - This class overlaps with newer site services/repositories, so changes here
 *   should be made carefully to avoid splitting site behavior across layers.
 * - Prefer keeping validation/storage authority in the more explicit service or
 *   repository classes when possible.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Site orchestration helper.
 *
 * Responsibilities:
 * - Manage user-facing site data access patterns.
 * - Support site mutation and lookup workflows used by legacy callers.
 * - Bridge site records with related work-entry context where needed.
 */
class Sites
{
  public string $site_name            = '';
  public string $wage                 = '';
  public string $living_out_allowance = '';
  public string $travel_hours         = '';
  public string $status               = '';

  private static ?Sites $instance = null;

  /**
   * Initializes a new instance.
   */
  public function __construct() {}
  /**
   * Prevents cloning or customizes clone behavior.
   */
  private function __clone(): void {}
  /**
   * Rehydrates the object after unserialization.
   */
  public function __wakeup(): void {}

  /**
   * Handles getInstance operation.
   */
  public static function getInstance(): Sites
  {
    if (null === self::$instance) {
      self::$instance = new Sites();
      Log::debug('[VERBOSE][Sites] Created new Sites singleton instance');
    }

    return self::$instance;
  }

  /**
   * @return \Generator<string, array<string, string>>
   */
  public static function getSites(string $userUUID, string $status = 'all'): \Generator
  {
    $pattern  = Keys::SITE . ":{$userUUID}:*";
    
    // Log the pattern being searched
    \PayCal\Observability\Lens::add("[Sites::getSites] Pattern Search", [
      'pattern' => $pattern,
      'status_filter' => $status
    ]);
    
    $siteKeys = Database::scanKeys($pattern);
    
    \PayCal\Observability\Lens::add("[Sites::getSites] Scan Results", [
      'keys_found' => count($siteKeys),
      'sample_keys' => array_slice($siteKeys, 0, 5)
    ]);

    if ([] === $siteKeys) {
      return;
    }

    // Directly fetch each site's hash data instead of using Pager
    $yielded_count = 0;
    foreach ($siteKeys as $key) {
      // Extract site ID from key: "site:UUID:SITEID"
      $parts = explode(':', $key);
      $siteID = $parts[2] ?? null;
      
      if (!$siteID)
        continue;

      // Get all fields from the site hash
      $siteData = Database::hgetall($key);
      
      if (empty($siteData))
        continue;

      // Add the ID to the data
      $siteData['id'] = $siteID;
      
      // Filter by status
      $siteStatus = $siteData['status'] ?? '';
      
      if ('all' === $status || $siteStatus === $status) {
        ++$yielded_count;
        yield $siteID => $siteData;
      }
    }
    
    \PayCal\Observability\Lens::add("[Sites::getSites] Yield Summary", [
      'status_filter' => $status,
      'total_keys' => count($siteKeys),
      'yielded_count' => $yielded_count
    ]);
  }

  /**
   * @return array<string, array<string, string>>
   */
  public static function getAllSiteTypes(string $userUUID): array
  {
    return iterator_to_array(
      self::getInstance()->getSites($userUUID, 'all')
    );
  }

  /**
   * @return null|array<string,string>
   */
  public static function getSiteById(string $userUUID, string $siteId): ?array
  {
    $key  = Keys::SITE . ":{$userUUID}:{$siteId}";
    $data = Database::hgetall($key);

    if (empty($data))
      return null;

    $data['id'] = $siteId;

    return $data;
  }

  /**
   * Handles getSiteName operation.
   */
  public static function getSiteName(string $siteID, string $userUUID): string
  {
    return (string) Database::hget(
      Keys::SITE . ":{$userUUID}:{$siteID}",
      'site_name'
    );
  }

  /**
   * Handles getSiteWage operation.
   */
  public static function getSiteWage(string $siteID, string $userUUID): string
  {
    return (string) Database::hget(
      Keys::SITE . ":{$userUUID}:{$siteID}",
      'wage'
    );
  }

  /**
   * Handles getSiteStatus operation.
   */
  public static function getSiteStatus(string $siteID, string $userUUID): string
  {
    return (string) Database::hget(
      Keys::SITE . ":{$userUUID}:{$siteID}",
      'status'
    );
  }

  /**
   * Handles setSiteStatus operation.
   */
  public static function setSiteStatus(string $siteID, string $status, string $userUUID): void
  {
    Database::getInstance()->hset(
      Keys::SITE . ":{$userUUID}:{$siteID}",
      'status',
      $status
    );
  }

  /**
   * @return \Generator<string,string>
   */
  public static function getSiteWages(string $userUUID): \Generator
  {
    foreach (self::getSites($userUUID, 'all') as $siteID => $siteData)
      yield $siteID => $siteData['wage'] ?? '0';
  }

  /**
   * @param array<int|string, array<string, mixed>> $sites
   */
  public static function updateSites(array $sites, string $userUUID): bool
  {
    $action  = InputSanitizer::postString('bulk_action');
    $siteIDs = json_decode(InputSanitizer::postString('site_ids'), true);

    if (is_array($siteIDs)) {

      foreach ($siteIDs as $siteID) {
        $siteIDStr = is_scalar($siteID) ? (string) $siteID : '';
        $key       = Keys::SITE . ":{$userUUID}:{$siteIDStr}";

        switch ($action) {
          case 'make_active':
            Database::hset($key, ['status' => SiteStatus::ACTIVE->value]);
            break;

          case 'make_inactive':
            Database::hset($key, ['status' => SiteStatus::INACTIVE->value]);
            break;

          case 'delete':
            if (0 === Database::unlink($key))
              return false;
            break;
        }
      }

      return true;
    }

    foreach ($sites as $siteID => $siteData) {
      $key = Keys::SITE . ":{$userUUID}:{$siteID}";
      foreach ($siteData as $field => $value) {
        $fieldValue = is_scalar($value) ? (string) $value : '';
        Database::hset($key, [$field => $fieldValue]);
      }
    }

    return true;
  }

  /**
   * Handles generateSiteUUID operation.
   */
  public static function generateSiteUUID(): string
  {
    $randomSeed   = bin2hex(random_bytes(9));
    $combinedData = $randomSeed . Keys::SITE_SALT;
    $hash         = hash('sha256', $combinedData);

    return substr($hash, 0, 9);
  }

  /**
   * @param array<string, string> $siteData
   */
  public function generateNewSite(array $siteData, string $userUUID): string
  {
    do {
      $siteUUID = self::generateSiteUUID();
      $key      = Keys::SITE . ":{$userUUID}:{$siteUUID}";
    } while (Database::exists($key));

    Database::hset($key, $siteData);

    $status = $siteData['status'] ?? SiteStatus::ACTIVE->value;

    return $siteUUID;
  }

  /**
   * Handles getSitesAsJson operation.
   */
  public static function getSitesAsJson(string $userUUID): string
  {
    $sites = iterator_to_array(self::getSites($userUUID, 'all'));

    $json = json_encode(
      $sites,
      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if (false === $json)
      return '{}';

    return $json;
  }

  /**
   * @param array<string, string> $data
   */
  public static function fromArray(array $data): Sites
  {
    $s = new Sites();

    $s->site_name            = (string) ($data['site_name'] ?? '');
    $s->wage                 = (string) ($data['wage'] ?? '');
    $s->living_out_allowance = (string) ($data['living_out_allowance'] ?? '');
    $s->travel_hours         = (string) ($data['travel_hours'] ?? '');
    $s->status               = (string) ($data['status'] ?? SiteStatus::ACTIVE->value);

    return $s;
  }

}


