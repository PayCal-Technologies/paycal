<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * Batch-fetch member work entry hashes via targeted SCAN + pipelined HGETALL.
 *
 * Callers process and discard each batch before fetching the next so peak
 * memory stays proportional to MEMBER_FETCH_BATCH_SIZE, not roster size.
 */
final class MemberWorkEntriesFetcher
{
  /** Members per SCAN + pipeline batch; keeps memory bounded while cutting round trips. */
  public const MEMBER_FETCH_BATCH_SIZE = 25;

  /**
   * Fetch work entries for one member batch.
   *
   * @param list<string> $memberUuids One batch (≤ MEMBER_FETCH_BATCH_SIZE)
   * @param int|null $year When set, only keys whose date segment starts with this year are scanned
   * @return array<string, array<string, array<string, string>>> Member UUID => (work key => entry hash)
   */
  public static function fetchForMembers(array $memberUuids, ?int $year = null): array
  {
    $byMember = [];
    $workKeys = [];

    foreach ($memberUuids as $memberUuid) {
      $normalized = trim($memberUuid);
      if ($normalized === '') {
        continue;
      }

      $byMember[$normalized] = [];

      if ($year !== null) {
        $yearPrefix = (string) $year;
        $workKeys = array_merge(
          $workKeys,
          Database::scanKeys(Keys::WORK . ':' . $normalized . ':' . $yearPrefix . '-*'),
          Database::scanKeys(Keys::WORK . ':archived:' . $normalized . ':' . $yearPrefix . '-*'),
        );
      } else {
        $workKeys = array_merge(
          $workKeys,
          Database::scanKeys(Keys::WORK . ':' . $normalized . ':*'),
          Database::scanKeys(Keys::WORK . ':archived:' . $normalized . ':*'),
        );
      }
    }

    if ($workKeys === []) {
      return $byMember;
    }

    $entries = Database::pipelineHgetall($workKeys);
    foreach ($entries as $workKey => $entry) {
      $keyParts = explode(':', $workKey);
      $entryMemberUuid = ($keyParts[1] ?? '') === 'archived'
        ? (string) ($keyParts[2] ?? '')
        : (string) ($keyParts[1] ?? '');

      if ($entryMemberUuid !== '' && isset($byMember[$entryMemberUuid])) {
        $byMember[$entryMemberUuid][$workKey] = $entry;
      }
    }

    return $byMember;
  }
}
