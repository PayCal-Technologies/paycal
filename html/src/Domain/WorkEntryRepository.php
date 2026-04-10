<?php declare(strict_types=1);

namespace PayCal\Domain;
use PayCal\Domain\Constants\Keys;

/**
 * WorkEntryRepository.php
 *
 * Purpose: Canonical persistence helper for work-entry key naming, lookup,
 * save/delete operations, and active-vs-archived record addressing.
 *
 * Developer notes:
 * - This repository defines canonical work-entry key shapes. Changing them has
 *   direct impact on calendar, earnings, archival, and repair flows.
 * - Preserve active/archive symmetry when adding new repository helpers.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */
/**
 * Work-entry persistence repository.
 *
 * Responsibilities:
 * - Generate canonical storage keys and patterns for work entries.
 * - Persist and delete work-entry records consistently.
 * - Serve as the storage-facing companion to WorkEntry validation logic.
 */
final class WorkEntryRepository
{
  /**
   * Canonical active work-entry key.
   */
  public static function activeKey(string $userUUID, string $date, string $siteId): string
  {
    return Keys::WORK . ":{$userUUID}:{$date}:{$siteId}";
  }

  /**
   * Canonical archived work-entry key.
   */
  public static function archivedKey(string $userUUID, string $date, string $siteId): string
  {
    return Keys::WORK . ":archived:{$userUUID}:{$date}:{$siteId}";
  }

  /**
   * Pattern for active work entries belonging to one site.
   */
  public static function activePatternForSite(string $userUUID, string $siteId): string
  {
    return self::activeKey($userUUID, '*', $siteId);
  }

  /**
   * Pattern for archived work entries belonging to one site.
   */
  public static function archivedPatternForSite(string $userUUID, string $siteId): string
  {
    return self::archivedKey($userUUID, '*', $siteId);
  }

  /**
   * Pattern for all active work entries belonging to a user.
   */
  public static function activePatternForUser(string $userUUID): string
  {
    return self::activeKey($userUUID, '*', '*');
  }

  /**
   * Save or update a work entry through domain validation + lock gates.
   *
   * @param array<string, null|scalar> $workDetails
   */
  public static function save(array $workDetails, ?string $userUUID = null): bool
  {
    return WorkEntry::updateWorkEntry($workDetails, $userUUID);
  }

  /**
   * Archive an active work-entry key after lock enforcement.
   */
  public static function archiveByKey(string $activeKey, string $userUUID): bool
  {
    $parts = self::parseWorkKey($activeKey);
    if (null === $parts || $parts['archived'] || $parts['user_uuid'] !== $userUUID) {
      return false;
    }

    if (WorkEntryLockService::isLocked($parts['date'], $userUUID)) {
      return false;
    }

    $targetKey = self::archivedKey($userUUID, $parts['date'], $parts['site_id']);

    return Database::rename($activeKey, $targetKey);
  }

  /**
   * Restore an archived work-entry key after lock enforcement.
   */
  public static function restoreByKey(string $archivedKey, string $userUUID): bool
  {
    $parts = self::parseWorkKey($archivedKey);
    if (null === $parts || !$parts['archived'] || $parts['user_uuid'] !== $userUUID) {
      return false;
    }

    if (WorkEntryLockService::isLocked($parts['date'], $userUUID)) {
      return false;
    }

    $targetKey = self::activeKey($userUUID, $parts['date'], $parts['site_id']);

    return Database::rename($archivedKey, $targetKey);
  }

  /**
   * Permanently delete an archived work-entry key after lock enforcement.
   */
  public static function deleteArchivedByKey(string $archivedKey, string $userUUID): bool
  {
    $parts = self::parseWorkKey($archivedKey);
    if (null === $parts || !$parts['archived'] || $parts['user_uuid'] !== $userUUID) {
      return false;
    }

    if (WorkEntryLockService::isLocked($parts['date'], $userUUID)) {
      return false;
    }

    return Database::unlink($archivedKey) > 0;
  }

  /**
   * @return null|array{archived: bool, user_uuid: string, date: string, site_id: string}
   */
  private static function parseWorkKey(string $key): ?array
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
        'user_uuid' => $parts[2],
        'date' => $parts[3],
        'site_id' => $parts[4],
      ];
    }

    return [
      'archived' => false,
      'user_uuid' => $parts[1],
      'date' => $parts[2],
      'site_id' => $parts[3],
    ];
  }
}
