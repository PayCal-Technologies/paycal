<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * ArrayPager.php
 *
 * Purpose: Define the ArrayPager class for PayCal\Domain.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Class ArrayPager.
 *
 * Implements PagerInterface for in-memory array pagination.
 * Provides methods for:
 * - Pagination (page size, current page, total pages)
 * - Search/filtering across array fields
 * - Sorting by field and direction
 * - Retrieving current page results
 */
class ArrayPager implements PagerInterface
{
  private const DEFAULT_PAGE_SIZE = 10;

  /** @var array<int, array<string, mixed>> */
  private array $allRows = [];

  /** @var array<int, array<string, mixed>> */
  private array $rows = [];

  private int $total = 0;

  private int $page = 1;

  private int $pageSize = self::DEFAULT_PAGE_SIZE;

  private int $totalPages = 0;

  /**
   * Constructor for ArrayPager.
   * Initializes the pager with an array of rows and configures pagination settings.
   *
   * @param array<int|string, mixed> $rows     Array of items to paginate
   * @param int                      $pageSize Number of items per page (default: 10)
   */
  public function __construct(array $rows, int $pageSize = self::DEFAULT_PAGE_SIZE)
  {
    $this->allRows = self::normalizeRows($rows);
    $this->total = count($this->allRows);
    $this->pageSize = $pageSize;
    $this->totalPages = (int) ceil($this->total / $this->pageSize);

    $this->paginate();
  }

  /**
   * Factory method to create an ArrayPager from an array.
   *
   * @param array<int|string, mixed> $rows    The array of rows to paginate
   * @param array<string, mixed>     $options Configuration options (pageSize, etc.)
   *
   * @return self A new ArrayPager instance
   */
  public static function fromArray(array $rows, array $options = []): self
  {
    $pageSizeRaw = $options['pageSize'] ?? self::DEFAULT_PAGE_SIZE;
    $pageSize = is_numeric($pageSizeRaw) ? (int) $pageSizeRaw : self::DEFAULT_PAGE_SIZE;

    return new self($rows, $pageSize);
  }

  /**
   * Set the current page (1-indexed).
   *
   * @param int $page The page number to set (will be clamped to valid range)
   */
  public function setPage(int $page): void
  {
    $this->page = max(1, min($page, $this->totalPages ?: 1));
    $this->paginate();
  }

  /**
   * Filter rows by search term across all fields.
   *
   * @param string $term The search term to filter by
   */
  public function search(string $term): void
  {
    if (empty($term)) {
      $this->allRows = $this->allRows;

      return;
    }

    $term = strtolower($term);
    $this->allRows = array_filter($this->allRows, function ($row) use ($term) {
      foreach ($row as $value) {
        $stringValue = is_scalar($value) ? (string) $value : '';
        if (false !== stripos($stringValue, $term)) {
          return true;
        }
      }

      return false;
    });

    $this->total = count($this->allRows);
    $this->totalPages = (int) ceil($this->total / $this->pageSize);
    $this->page = 1;
    $this->paginate();
  }

  /**
   * Sort rows by a field in ascending or descending order.
   *
   * @param string $field     The field name to sort by
   * @param string $direction Sort direction: "asc" or "desc"
   */
  public function sortBy(string $field, string $direction = 'asc'): void
  {
    usort($this->allRows, function ($a, $b) use ($field, $direction) {
      $valA = $a[$field] ?? null;
      $valB = $b[$field] ?? null;

      if (null === $valA && null === $valB) {
        return 0;
      }
      if (null === $valA) {
        return 1;
      }
      if (null === $valB) {
        return -1;
      }

      if (is_numeric($valA) && is_numeric($valB)) {
        $cmp = (float) $valA <=> (float) $valB;
      } else {
        $cmp = strnatcmp(is_scalar($valA) ? (string) $valA : '', is_scalar($valB) ? (string) $valB : '');
      }

      return 'desc' === $direction ? -$cmp : $cmp;
    });

    $this->paginate();
  }

  // -------- DataGrid Interface --------

  /**
   * Get the array of rows for the current page.
   * Returns only the items that should be displayed on this page.
   *
   * @return array<int|string, mixed> Rows for the current page
   */
  public function getRows(): array
  {
    return $this->rows;
  }

  /**
   * Check if pagination is needed for this dataset.
   * Returns true if total items exceed one page.
   *
   * @return bool True if more than one page exists
   */
  public function hasPagination(): bool
  {
    return $this->totalPages > 1;
  }

  /**
   * Get the current page number (1-indexed).
   *
   * @return int Current page number
   */
  public function getPage(): int
  {
    return $this->page;
  }

  /**
   * Get the total number of pages.
   * Returns at least 1 even if there are no rows.
   *
   * @return int Number of pages (minimum 1)
   */
  public function getTotalPages(): int
  {
    return max(1, $this->totalPages);
  }

  /**
   * Check if a previous page exists.
   *
   * @return bool True if current page is not the first page
   */
  public function hasPrev(): bool
  {
    return $this->page > 1;
  }

  /**
   * Check if a next page exists.
   *
   * @return bool True if current page is not the last page
   */
  public function hasNext(): bool
  {
    return $this->page < $this->totalPages;
  }

  /**
   * Get the total number of items across all pages.
   *
   * @return int Total item count
   */
  public function getTotal(): int
  {
    return $this->total;
  }

  /**
   * Get the page size (items per page).
   *
   * @return int Number of items per page
   */
  public function getPageSize(): int
  {
    return $this->pageSize;
  }

  /**
   * Extract current page rows.
   */
  private function paginate(): void
  {
    if (0 === $this->total) {
      $this->rows = [];

      return;
    }

    $start = ($this->page - 1) * $this->pageSize;
    $this->rows = array_slice($this->allRows, $start, $this->pageSize);
  }

  /**
   * @param array<int|string, mixed> $rows
   * @return array<int, array<string, mixed>>
   */
  private static function normalizeRows(array $rows): array
  {
    $normalized = [];
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $assoc = [];
      foreach ($row as $k => $v) {
        $assoc[(string) $k] = $v;
      }
      $normalized[] = $assoc;
    }

    return $normalized;
  }
}
