<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * PagerInterface.php
 *
 * Purpose: Define the PagerInterface interface for PayCal\Domain.
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
 * Interface PagerInterface
 *
 * Describes the pagination metadata and row access required by grid views.
 */
interface PagerInterface
{
  /**
   * Return the rows visible for the current pager state.
   *
   * @return array<int, array<string, mixed>>
   */
  public function getRows(): array;

  /**
   * Determine whether pagination controls should be rendered.
   *
   * @return bool True when the result set spans multiple pages
   */
  public function hasPagination(): bool;

  /**
   * Return the current one-based page number.
   *
   * @return int Active page number
   */
  public function getPage(): int;

  /**
   * Return the total number of pages in the result set.
   *
   * @return int Total page count
   */
  public function getTotalPages(): int;

  /**
   * Determine whether a previous page exists.
   *
   * @return bool True when the pager can move backward
   */
  public function hasPrev(): bool;

  /**
   * Determine whether a next page exists.
   *
   * @return bool True when the pager can move forward
   */
  public function hasNext(): bool;

  /**
   * Return the total number of matching records.
   *
   * @return int Total record count
   */
  public function getTotal(): int;

  /**
   * Return the configured number of records per page.
   *
   * @return int Page size
   */
  public function getPageSize(): int;
}
