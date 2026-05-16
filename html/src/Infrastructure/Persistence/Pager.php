<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Persistence;

use PayCal\Domain\Database;

/**
 * Pager.php
 *
 * Purpose: Redis-backed pagination engine: SCAN-based row discovery, numeric-aware sorting, field projection, and consistent paginated response contract.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Infrastructure
 * @package    PayCal\Infrastructure\Persistence
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Class Pager.
 *
 * Handles pagination, sorting, and field projection for Redis-backed datasets.
 * Operates over multiple Redis hashes discovered via SCAN patterns.
 *
 * Design Principles:
 * - Never throws exceptions; all errors reported via response metadata
 * - Materializes full result set before sorting/pagination
 * - Numeric-aware sorting with null handling
 * - Strict response contract with all keys always present
 */
class Pager
{
  /**
   * Default maximum list size to prevent unbounded requests.
   */
  private const DEFAULT_MAX_LIST_SIZE = 100;

  /**
   * Default list size for requests without specification.
   */
  private const DEFAULT_LIST_SIZE = 25;

  /**
   * Allowed sort directions for list requests.
   *
   * @var array<string, true>
   */
  private const ALLOWED_SORT_DIRECTIONS = [
    'asc' => true,
    'desc' => true,
  ];

  /**
   * @var mixed The Redis read instance for data retrieval
   */
  private $redis;

  /**
   * Constructor.
   *
   * @param mixed $redis Optional Redis instance. If null, uses Database::getReadInstance()
   */
  public function __construct($redis = null)
  {
    $this->redis = $redis ?? Database::getReadInstance();
  }

  /**
   * Retrieve paginated, sorted data from Redis hashes.
   *
   * Full signature with all parameters:
   *
  * @param string $redisKey       Redis key or SCAN pattern (e.g. "user:*" or "organization:site:{org_id}")
   * @param int    $startIndex     Zero-based starting position (default: 0)
   * @param int    $listSize       Number of records per page (default: 25, max: 100)
   * @param array<int, string>  $columnHeadings Field names to retrieve; also defines sortable columns
   * @param string $sortHeading    Field name to sort by (must exist in columnHeadings or ignored)
   * @param string $sortDirection  'asc' or 'desc' (default: 'asc')
   *
   * @return array<string, mixed> Structured response with data, pagination metadata, and optional error
   */
  public function list(
    string $redisKey,
    int $startIndex = 0,
    int $listSize = self::DEFAULT_LIST_SIZE,
    array $columnHeadings = [],
    string $sortHeading = '',
    string $sortDirection = 'asc'
  ): array {
    // Initialize response template
    $response = $this->buildResponseTemplate($startIndex, $listSize);

    // Validate parameters
    $validationError = $this->validateParameters(
      $redisKey,
      $startIndex,
      $listSize,
      $columnHeadings,
      $sortHeading,
      $sortDirection
    );

    if (null !== $validationError) {
      $response['error'] = $validationError;

      return $response;
    }

    try {
      // Retrieve all matching keys
      $keys = Database::scanKeys($redisKey);

      if (empty($keys)) {
        // No keys found is not an error, just empty result
        return $response;
      }

      // Retrieve and normalize rows
      $rows = $this->retrieveRows($keys, $columnHeadings);

      if (empty($rows)) {
        return $response;
      }

      // Store total before pagination
      $response['total'] = count($rows);

      // Apply sorting if requested
      if (!empty($sortHeading)) {
        $rows = $this->sortRows($rows, $sortHeading, $sortDirection);
      }

      // Apply pagination
      $paginatedRows = array_slice($rows, $startIndex, $listSize);
      $returned = count($paginatedRows);

      // Build response
      $response['data'] = $paginatedRows;
      $response['returned'] = $returned;
      $response['hasMore'] = ($startIndex + $returned) < $response['total'];

      return $response;
    } catch (\Throwable $e) {
      // Catch any Redis or unexpected errors
      $response['error'] = 'Retrieval failed: '.$e->getMessage();

      return $response;
    }
  }

  /**
   * Validate all input parameters.
   *
   * @param array<int, string> $columnHeadings
   * @return null|string Error message or null if valid
   */
  private function validateParameters(
    string $redisKey,
    int $startIndex,
    int $listSize,
    array $columnHeadings,
    string $sortHeading,
    string $sortDirection
  ): ?string {
    if (empty($redisKey)) {
      return 'Invalid redisKey: must not be empty';
    }

    if ($startIndex < 0) {
      return 'Invalid startIndex: must be >= 0';
    }

    if ($listSize <= 0) {
      return 'Invalid listSize: must be > 0';
    }

    if ($listSize > self::DEFAULT_MAX_LIST_SIZE) {
      return 'Invalid listSize: exceeded maximum of '.self::DEFAULT_MAX_LIST_SIZE;
    }

    // sortHeading validation: must exist in columnHeadings or be empty
    if (!empty($sortHeading)) {
      if (empty($columnHeadings)) {
        return 'Invalid sortHeading: columnHeadings must not be empty when sorting';
      }

      if (!in_array($sortHeading, $columnHeadings, true)) {
        // Log warning but don't fail; sorting will be skipped
        // For now, we'll set sortHeading to empty and continue
      }
    }

    // Validate sortDirection
    if (!isset(self::ALLOWED_SORT_DIRECTIONS[$sortDirection])) {
      return "Invalid sortDirection: must be 'asc' or 'desc'";
    }

    return null;
  }

  /**
   * Retrieve rows from Redis by key, extracting only requested fields.
   *
   * @param array<int, string> $keys           List of Redis keys to retrieve
   * @param array<int, string> $columnHeadings Fields to extract from each hash
   *
   * @return array<int, array<string, mixed>> List of row arrays with field projections
   */
  private function retrieveRows(array $keys, array $columnHeadings): array
  {
    if (!is_object($this->redis)) {
      return [];
    }

    if (!method_exists($this->redis, 'hgetall') || !method_exists($this->redis, 'hmget')) {
      return [];
    }

    $rows = [];

    foreach ($keys as $key) {
      if (empty($columnHeadings)) {
        // If no columns specified, retrieve all fields
        $row = $this->redis->hgetall($key);
      } else {
        // Retrieve only specified fields using HMGET
        $row = $this->redis->hmget($key, $columnHeadings);

        // HMGET returns array with keys as field names and null for missing fields
        // Filter out any null values to keep only present fields
        if (is_array($row)) {
          $row = array_filter($row, fn ($value) => null !== $value);
        } else {
          $row = [];
        }
      }

      if (is_array($row) && !empty($row)) {
        $rows[] = $row;
      }
    }

    return $rows;
  }

  /**
   * Sort rows by a specified column with numeric-aware sorting.
   *
   * Sorting rules:
   * - Numeric values sorted numerically
   * - Strings sorted lexicographically
   * - Null values always sort last
   * - Stable sort preserves original order for equal values
   *
   * @param array<int, array<string, mixed>> $rows Array of row associative arrays
   * @param string $sortHeading   Field name to sort by
   * @param string $sortDirection 'asc' or 'desc'
   *
   * @return array<int, array<string, mixed>> Sorted rows
   */
  private function sortRows(array $rows, string $sortHeading, string $sortDirection): array
  {
    // Partition rows directly into null and non-null buckets.
    $nonNulls = [];
    $nulls = [];
    foreach ($rows as $row) {
      $value = $row[$sortHeading] ?? null;

      $item = [
          'value' => $value,
          'row' => $row,
      ];

      if (null === $value) {
        $nulls[] = $item;
      } else {
        $nonNulls[] = $item;
      }
    }

    // Sort non-null values
    usort($nonNulls, function ($a, $b) use ($sortDirection) {
      $valueA = $a['value'];
      $valueB = $b['value'];

      // Attempt numeric sort if both are numeric
      if (is_numeric($valueA) && is_numeric($valueB)) {
        $numA = (float) $valueA;
        $numB = (float) $valueB;
        $cmp = $numA <=> $numB;
      } else {
        // String comparison
        $strA = is_scalar($valueA) ? (string) $valueA : '';
        $strB = is_scalar($valueB) ? (string) $valueB : '';
        $cmp = strnatcmp($strA, $strB);
      }

      // Reverse comparison if descending
      return 'desc' === $sortDirection ? -$cmp : $cmp;
    });

    // Extract rows in sorted order with nulls last.
    $sortedRows = [];
    foreach ($nonNulls as $item) {
      $sortedRows[] = $item['row'];
    }
    foreach ($nulls as $item) {
      $sortedRows[] = $item['row'];
    }

    return $sortedRows;
  }

  /**
   * Build the response template with default values.
   *
   * @return array<string, mixed> Response template
   */
  private function buildResponseTemplate(int $startIndex, int $listSize): array
  {
    return [
        'data' => [],
        'total' => 0,
        'startIndex' => $startIndex,
        'listSize' => $listSize,
        'returned' => 0,
        'hasMore' => false,
        'error' => null,
    ];
  }
}
