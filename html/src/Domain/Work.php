<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;
use PayCal\Domain\Constants\Keys;

/**
 * Work.php
 *
 * Purpose: User-scoped work-entry orchestration service for retrieval, range
 * traversal, aggregation, and higher-level work-data access patterns.
 *
 * Developer notes:
 * - This class bridges raw work-entry persistence with consumer-facing read and
 *   aggregation flows.
 * - Validation authority still belongs in WorkEntry/WorkEntryRepository; keep
 *   this layer focused on orchestration and derived access patterns.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Work access/orchestration service.
 *
 * Responsibilities:
 * - Provide user-scoped work lookup helpers.
 * - Aggregate work-entry collections for higher-level consumers.
 * - Adapt repository data into runtime-friendly result sets.
 */
class Work
{
  private string $userUUID;

  /** @var array<string, list<string>> */
  private static array $availableYearsCache = [];

  /** @var array<string, array<string, array<string, mixed>>> */
  private static array $workRangeCache = [];

  /**
   * Initialize Work instance for a specific user.
   *
   * @param string $userUUID User UUID to manage work entries for
   */
  public function __construct(string $userUUID)
  {
    $this->userUUID = $userUUID;
  }

  /**
   * Return a shared instance of Work for the current user.
   */
  public static function getInstance(): self
  {
    static $instance = null;

    if (null === $instance) {
      $instance = new self(User::uuid());
    }

    return $instance;
  }

  /**
   * Retrieves work data for a specific date for the user associated with this Work instance.
   *
   * @param string $date the date in 'YYYY-MM-DD' format
   *
   * @return \stdClass an object containing work details for the specified date, or an empty object if no work is found
   */
  public function getWorkForDate(string $date): \stdClass
  {
    $workForDate = new \stdClass();
    
    // Scan for both active and archived work entries
    $redisSearch = Keys::WORK . ':'.$this->userUUID.':'.$date.':*';
    $keys = Database::scanKeys($redisSearch);
    
    // Also include archived work entries
    $archivedSearch = Keys::WORK . ':archived:'.$this->userUUID.':'.$date.':*';
    $archivedKeys = Database::scanKeys($archivedSearch);
    
    // Merge both sets of keys
    $allKeys = array_merge($keys, $archivedKeys);
    
    foreach ($allKeys as $i => $key) {
      $entry = WorkEntry::getWorkEntry($key);
      if (is_array($entry)) {
        $workForDate->{$key} = $entry;
      }
    }

    return $workForDate;
  }

  /**
   * Retrieves all work data for a user within a specified date range.
   *
   * @param \DateTimeImmutable $start    the starting date of the range (inclusive)
   * @param \DateTimeImmutable $end      the ending date of the range (exclusive)
   * @param null|string       $userUUID The User UUID to retrieve records for (defaults to session User::uuid() if available)
   *
  * @return \Generator<string, array<string, mixed>> Redis keys "work:user:UUID:YYYY-MM-DD:SITE_ID"
   *
   * @throws \RuntimeException if no user UUID is available
   */
  public static function getWorkInRange(\DateTimeImmutable $start, \DateTimeImmutable $end, ?string $userUUID = null): \Generator
  {
    if ($end < $start) {
      return;
    }

    // Use provided UUID, fall back to User, or throw
		if (null === $userUUID) {
		  $userUUID = User::uuid();

		  if ("PUBLIC" === $userUUID) {
		    throw new \RuntimeException("No authenticated user for getWorkInRange");
		  }
		}

    $rangeCacheKey = implode('|', [
      $userUUID,
      $start->format('c'),
      $end->format('c'),
      $start->getTimezone()->getName(),
    ]);
    if (isset(self::$workRangeCache[$rangeCacheKey])) {
      foreach (self::$workRangeCache[$rangeCacheKey] as $key => $row) {
        yield $key => $row;
      }

      return;
    }

    // Scan for both regular and archived work keys
    $redisSearch = Keys::WORK . ':'.$userUUID.':*';
    $allKeys = Database::scanKeys($redisSearch);
    
    // Also include archived work keys
    $archivedSearch = Keys::WORK . ':archived:'.$userUUID.':*';
    $archivedKeys = Database::scanKeys($archivedSearch);
    
    // Merge both sets
    $allKeys = array_merge($allKeys, $archivedKeys);
    
    $matchingKeys = [];
    foreach ($allKeys as $key) {
      $parts = explode(':', $key);
      
      // Determine if this is an archived key
      $isArchived = (isset($parts[1]) && 'archived' === $parts[1]);
      
      // Extract date string appropriately
      $dateStr = $isArchived ? ($parts[3] ?? null) : ($parts[2] ?? null);
      
      if (null === $dateStr) {
        continue;
      }
      
      try {
        $keyDate = new \DateTimeImmutable($dateStr, $start->getTimezone());
      } catch (\Exception $e) {
        continue;
      }
      if ($keyDate >= $start && $keyDate < $end) {
        $matchingKeys[] = $key;
      }
    }
    sort($matchingKeys);

    $redis = Database::getInstance();
    $pipe = $redis->pipeline();
    $keyMap = [];
    foreach ($matchingKeys as $key) {
      $pipe->hgetall($key);
      $keyMap[] = $key;
    }
    $results = $pipe->exec();

    $rangeRows = [];
    foreach ($results as $i => $data) {
      $key = $keyMap[$i];
      $parts = explode(':', $key);

      if (!is_array($data)) {
        continue;
      }
      
      // Determine if this is an archived key
      $isArchived = (isset($parts[1]) && 'archived' === $parts[1]);
      
      // Extract date and site ID appropriately
      if ($isArchived) {
        $dateStr = $parts[3] ?? '';
        $siteID = $parts[4] ?? '';
      } else {
        $dateStr = $parts[2] ?? '';
        $siteID = $parts[3] ?? '';
      }

      $dataWithStringKeys = [];
      foreach ($data as $field => $value) {
        if (is_string($field)) {
          $dataWithStringKeys[$field] = $value;
        }
      }

      $data = WorkEntry::normalizeWorkEntryPayload($dataWithStringKeys);
      if (isset($data['date']) && is_string($data['date']) && '' !== $data['date']) {
        $dateStr = $data['date'];
      }
      if (isset($data['site_id']) && is_string($data['site_id']) && '' !== $data['site_id']) {
        $siteID = $data['site_id'];
      }
      
      $blob = isset($data['encrypted_blob']) ? (string) $data['encrypted_blob'] : '';

      $siteName = isset($data['site_name'])
        ? trim((string) $data['site_name'])
        : ($siteID ? Sites::getSiteName($siteID, $userUUID) : '');

      $hours = is_numeric($data['hours'] ?? null) ? (float) $data['hours'] : 0.0;
      $regularHours = is_numeric($data['regular_hours'] ?? null) ? (float) $data['regular_hours'] : 0.0;
      $overtimeHours = is_numeric($data['overtime_hours'] ?? null) ? (float) $data['overtime_hours'] : 0.0;
      $livingOutAllowance = is_numeric($data['living_out_allowance'] ?? null) ? (float) $data['living_out_allowance'] : 0.0;
      $travelHours = is_numeric($data['travel_hours'] ?? null) ? (float) $data['travel_hours'] : 0.0;
      $wage = is_numeric($data['wage'] ?? null) ? (float) $data['wage'] : 0.0;
      $regularAmount = is_numeric($data['regular_amount'] ?? null) ? (float) $data['regular_amount'] : null;
      $overtimeAmount = is_numeric($data['overtime_amount'] ?? null) ? (float) $data['overtime_amount'] : null;
      $travelAmount = is_numeric($data['travel_amount'] ?? null) ? (float) $data['travel_amount'] : null;
      $livingOutAmount = is_numeric($data['living_out_amount'] ?? null) ? (float) $data['living_out_amount'] : null;
      $gross = is_numeric($data['gross'] ?? null) ? (float) $data['gross'] : null;
      $tax = is_numeric($data['tax'] ?? null) ? (float) $data['tax'] : null;
      $net = is_numeric($data['net'] ?? null) ? (float) $data['net'] : null;
      $other = is_numeric($data['other'] ?? null) ? (float) $data['other'] : null;

      $siteColor = isset($data['site_color']) && preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $data['site_color'])
        ? strtoupper((string) $data['site_color'])
        : '';
      $yieldRow = [
        'date' => $dateStr,
        'site_id' => $siteID,
        'site_name' => $siteName,
        'site_color' => $siteColor,
        'hours' => $hours,
        'regular_hours' => $regularHours,
        'overtime_hours' => $overtimeHours,
        'living_out_allowance' => $livingOutAllowance,
        'travel_hours' => $travelHours,
        'wage' => $wage,
        'encrypted_blob' => $blob,
      ];

      if ($gross !== null) {
        $yieldRow['gross'] = $gross;
      }
      if ($regularAmount !== null) {
        $yieldRow['regular_amount'] = $regularAmount;
      }
      if ($overtimeAmount !== null) {
        $yieldRow['overtime_amount'] = $overtimeAmount;
      }
      if ($travelAmount !== null) {
        $yieldRow['travel_amount'] = $travelAmount;
      }
      if ($livingOutAmount !== null) {
        $yieldRow['living_out_amount'] = $livingOutAmount;
      }
      if ($tax !== null) {
        $yieldRow['tax'] = $tax;
      }
      if ($net !== null) {
        $yieldRow['net'] = $net;
      }
      if ($other !== null) {
        $yieldRow['other'] = $other;
      }

      $rangeRows[$key] = $yieldRow;
    }

    self::$workRangeCache[$rangeCacheKey] = $rangeRows;
    foreach ($rangeRows as $key => $row) {
      yield $key => $row;
    }
  }

  /**
   * Return a list of unique years for which work entries exist.
   *
   * @param string $userUUID User UUID
   *
   * @return \Generator<int> List of year integers (e.g., [2022, 2023, 2024])
   */
  public static function getAvailableYears(string $userUUID): \Generator
  {
    if (isset(self::$availableYearsCache[$userUUID])) {
      foreach (self::$availableYearsCache[$userUUID] as $year) {
        yield (int) $year;
      }

      return;
    }

    // Search for both regular and archived work keys
    $keys = Database::scanKeys("work:{$userUUID}:*");
    $archivedKeys = Database::scanKeys("work:archived:{$userUUID}:*");
    $allKeys = array_merge($keys, $archivedKeys);
    
    $years = [];
    foreach ($allKeys as $key) {
      $parts = explode(':', $key);
      
      // Determine if this is an archived key
      $isArchived = (isset($parts[1]) && 'archived' === $parts[1]);
      
      // Extract date string appropriately
      $dateStr = $isArchived ? ($parts[3] ?? null) : ($parts[2] ?? null);
      
      if (null === $dateStr) {
        continue;
      }
      
      $year = explode('-', $dateStr)[0];
      if (ctype_digit($year)) {
        $years[$year] = true;
      }
    }

    $uniqueYears = $years ? array_map('intval', array_keys($years)) : [(int) date('Y')];
    rsort($uniqueYears);  // Sort in reverse chronological order (newest first)
    self::$availableYearsCache[$userUUID] = array_map('strval', $uniqueYears);
    foreach ($uniqueYears as $year) {
      yield $year;
    }
  }

  /**
   * Calculate regular and overtime hours for a given day.
   *
  * @param float $hours             the total hours worked on a given entry
  * @param float $weeklyTotal       the cumulative regular hours worked in the week so far
  * @param float $dailyRegularTotal the cumulative regular hours already allocated for the current day
   *
   * @return array<string, float> an array containing "regular_hours" and "overtime_hours"
   */
  public static function calculateHours(float $hours, float $weeklyTotal, float $dailyRegularTotal = 0.0): array
  {
    // Local safe thresholds (avoid undefined legacy constants)
    $maxDailyRegularHours = 8.0;
    $maxWeeklyRegularHours = 40.0;

    // Enforce daily regular-hours cap across all entries for the same day.
    $remainingDailyRegular = max(0.0, $maxDailyRegularHours - $dailyRegularTotal);
    $regularHours = min($hours, $remainingDailyRegular);
    $overtimeHours = max(0.0, $hours - $regularHours);

    // Apply weekly threshold on top of the daily calculation
    if (($weeklyTotal + $regularHours) > $maxWeeklyRegularHours) {
      $excess = ($weeklyTotal + $regularHours) - $maxWeeklyRegularHours;
      $overtimeHours += $excess;
      $regularHours -= $excess;
    }

    return ['regular_hours' => $regularHours, 'overtime_hours' => $overtimeHours];
  }

  /**
   * Process the pay period and calculate regular and overtime hours for each day and site.
   *
   * @param string $userUUID       the user's UUID
   * @param string $payPeriodStart the start date of the pay period in YYYY-MM-DD format
   */
  public static function processWorkWeek(string $userUUID, string $payPeriodStart): void
  {
    if (\PayCal\Domain\Config\EncryptionConfig::isRequired()) {
      Lens::add('Work Week Recalc Skipped', [
        'user_uuid' => $userUUID,
        'week_start' => $payPeriodStart,
        'reason' => 'encrypted_only_mode',
      ], 'work_recalc');

      return;
    }

    $weeklyTotal = 0.0;
    $hSetQueue = [];
    $recalcSummary = [];

    \PayCal\Observability\Lens::add('Work Week Recalc Start', [
      'user_uuid' => $userUUID,
      'week_start' => $payPeriodStart,
    ], 'work_recalc');

    $siteWages = iterator_to_array(Sites::getSiteWages($userUUID));

    // Collect all keys for the week
    for ($i = 0; $i < 7; ++$i) {
      $currentDate = date('Y-m-d', strtotime("{$payPeriodStart} +{$i} days") ?: time());
      $siteKeysPattern = Keys::WORK . ":{$userUUID}:{$currentDate}:*";
      $archivedSiteKeysPattern = Keys::WORK . ":archived:{$userUUID}:{$currentDate}:*";
      $siteKeys = Database::scanKeys($siteKeysPattern);
      $archivedSiteKeys = Database::scanKeys($archivedSiteKeysPattern);
      $siteKeys = array_merge($siteKeys, $archivedSiteKeys);
      sort($siteKeys);

      if (empty($siteKeys)) {
        continue;
      }

      $dayRegularTotal = 0.0;
      $dayOvertimeTotal = 0.0;
      $dayEntries = 0;

      foreach ($siteKeys as $siteKey) {
        if ('' === $siteKey) {
          continue;
        }

        $workData = Database::hgetall($siteKey);
        $hoursValue = $workData['hours'] ?? $workData['h'] ?? null;
        if (null === $hoursValue || '' === (string) $hoursValue) {
          continue;
        }

        $hoursWorked = (float) $hoursValue;
        $calculatedHours = self::calculateHours($hoursWorked, $weeklyTotal, $dayRegularTotal);
        $weeklyTotal += $calculatedHours['regular_hours'];
        $dayRegularTotal += $calculatedHours['regular_hours'];
        $dayOvertimeTotal += $calculatedHours['overtime_hours'];
        ++$dayEntries;

        $parts = explode(':', $siteKey);
        $isArchived = (isset($parts[1]) && 'archived' === $parts[1]);
        $siteID = $isArchived ? ($parts[4] ?? '') : ($parts[3] ?? '');
        if ('' === $siteID) {
          continue;
        }

        $wage = is_numeric($siteWages[$siteID] ?? null)
          ? (float) $siteWages[$siteID]
          : (is_numeric($workData['wage'] ?? $workData['w'] ?? null) ? (float) ($workData['wage'] ?? $workData['w']) : 0.0);
        $travelHours = is_numeric($workData['travel_hours'] ?? $workData['t'] ?? null) ? (float) ($workData['travel_hours'] ?? $workData['t']) : 0.0;
        $livingOutAllowance = is_numeric($workData['living_out_allowance'] ?? $workData['l'] ?? null) ? (float) ($workData['living_out_allowance'] ?? $workData['l']) : 0.0;
        $snapshot = WorkEntry::calculateEarningsSnapshot(
          $calculatedHours['regular_hours'],
          $calculatedHours['overtime_hours'],
          $travelHours,
          $livingOutAllowance,
          $wage
        );

        Database::hset($siteKey, [
          'regular_hours' => number_format($calculatedHours['regular_hours'], 2, '.', ''),
          'overtime_hours' => number_format($calculatedHours['overtime_hours'], 2, '.', ''),
          'wage' => number_format($wage, 2, '.', ''),
          'regular_amount' => number_format($snapshot['regular_amount'], 2, '.', ''),
          'overtime_amount' => number_format($snapshot['overtime_amount'], 2, '.', ''),
          'travel_amount' => number_format($snapshot['travel_amount'], 2, '.', ''),
          'living_out_amount' => number_format($snapshot['living_out_amount'], 2, '.', ''),
          'gross' => number_format($snapshot['gross'], 2, '.', ''),
          'earnings_snapshot_version' => '1',
        ]);
      }

      if ($dayEntries > 0) {
        $recalcSummary[$currentDate] = [
          'entries' => $dayEntries,
          'regular_hours' => round($dayRegularTotal, 2),
          'overtime_hours' => round($dayOvertimeTotal, 2),
          'weekly_regular_running_total' => round($weeklyTotal, 2),
        ];
      }
    }

    \PayCal\Observability\Lens::add('Work Week Recalc Complete', [
      'user_uuid' => $userUUID,
      'week_start' => $payPeriodStart,
      'weekly_regular_total' => round($weeklyTotal, 2),
      'days_recalculated' => $recalcSummary,
    ], 'work_recalc');
  }

  /**
   * Recalculate regular/overtime/gross for the week containing a given date.
   *
   * @param string $userUUID User UUID
   * @param string $date     YYYY-MM-DD
   */
  public static function processWorkWeekContainingDate(string $userUUID, string $date): void
  {
    $ts = (int) strtotime($date);

    $dayOfWeek = (int) date('w', $ts); // 0 = Sunday
    $weekStartTs = (int) strtotime("-{$dayOfWeek} days", $ts);

    self::processWorkWeek($userUUID, date('Y-m-d', $weekStartTs));
  }

  /** PAY PERIODS HELPERS */

  /**
   * Retrieve all work entries that fall within the specified pay period.
   *
   * @param PayPeriods $pp Pay period instance providing start and end dates
   *
  * @return \Generator<string, array<string, mixed>> List of work entry records within the period
   */
  public static function getWorkInPeriod(PayPeriods $pp): \Generator
  {
    return self::getWorkInRange($pp->start(), $pp->endInclusive()->modify('+1 day'));
  }
}
