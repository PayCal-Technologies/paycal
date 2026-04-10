<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\ArrayPager;
use PayCal\Domain\PagerInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * ArrayPagerTest.
 *
 * Unit tests for ArrayPager class
 * Tests pagination, sorting, filtering, and navigation
 *
 * @internal
 *
 */
#[Group('unit')]
final class ArrayPagerTest extends TestCase
{
  // =========================================================================
  // Constructor and Instantiation Tests
  // =========================================================================

  #[Test]
  public function constructorWithDefaultPageSizePaginatesCorrectly(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data);

    $this->assertInstanceOf(ArrayPager::class, $pager);
    $this->assertSame(10, $pager->getPageSize());
    $this->assertSame(1, $pager->getPage());
    $this->assertSame(10, $pager->getTotal());
  }

  #[Test]
  public function constructorWithCustomPageSizeUsesProvidedSize(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 5);

    $this->assertSame(5, $pager->getPageSize());
    $this->assertSame(2, $pager->getTotalPages());
  }

  #[Test]
  public function constructorWithEmptyArrayHandlesGracefully(): void
  {
    $pager = new ArrayPager([]);

    $this->assertSame(0, $pager->getTotal());
    $this->assertSame(1, $pager->getTotalPages());
    $this->assertSame([], $pager->getRows());
  }

  #[Test]
  public function constructorWithSingleItemReturnsOnePage(): void
  {
    $pager = new ArrayPager([['id' => 1]]);

    $this->assertSame(1, $pager->getTotal());
    $this->assertSame(1, $pager->getTotalPages());
    $this->assertFalse($pager->hasPagination());
  }

  #[Test]
  public function constructorCalculatesTotalPagesCorrectly(): void
  {
    $data = array_fill(0, 25, ['test' => 'data']);
    $pager = new ArrayPager($data, 10);

    $this->assertSame(3, $pager->getTotalPages());
    $this->assertSame(25, $pager->getTotal());
  }

  // =========================================================================
  // Factory Method Tests
  // =========================================================================

  #[Test]
  public function fromArrayCreatesInstance(): void
  {
    $data = $this->getSampleData();
    $pager = ArrayPager::fromArray($data);

    $this->assertInstanceOf(ArrayPager::class, $pager);
    $this->assertSame(10, $pager->getPageSize());
  }

  #[Test]
  public function fromArrayWithPageSizeOptionUsesProvidedSize(): void
  {
    $data = $this->getSampleData();
    $pager = ArrayPager::fromArray($data, ['pageSize' => 3]);

    $this->assertSame(3, $pager->getPageSize());
    $this->assertSame(4, $pager->getTotalPages());
  }

  #[Test]
  public function fromArrayWithEmptyOptionsUsesDefaults(): void
  {
    $data = $this->getSampleData();
    $pager = ArrayPager::fromArray($data, []);

    $this->assertSame(10, $pager->getPageSize());
  }

  // =========================================================================
  // Pagination Tests
  // =========================================================================

  #[Test]
  public function getRowsReturnsCurrentPageRows(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 3);

    $rows = $pager->getRows();

    $this->assertCount(3, $rows);
    $this->assertSame('Alice', $rows[0]['name']);
    $this->assertSame('Bob', $rows[1]['name']);
    $this->assertSame('Charlie', $rows[2]['name']);
  }

  #[Test]
  public function setPageNavigatesToCorrectPage(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 3);

    $pager->setPage(2);
    $rows = $pager->getRows();

    $this->assertSame(2, $pager->getPage());
    $this->assertCount(3, $rows);
    $this->assertSame('Diana', $rows[0]['name']);
    $this->assertSame('Eve', $rows[1]['name']);
    $this->assertSame('Frank', $rows[2]['name']);
  }

  #[Test]
  public function setPageWithPageBeyondTotalClampsToLastPage(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 3);

    $pager->setPage(999);

    $this->assertSame(4, $pager->getPage()); // Last page
  }

  #[Test]
  public function setPageWithPageZeroClampsToFirstPage(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 3);

    $pager->setPage(0);

    $this->assertSame(1, $pager->getPage());
  }

  #[Test]
  public function setPageWithNegativePageClampsToFirstPage(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 3);

    $pager->setPage(-5);

    $this->assertSame(1, $pager->getPage());
  }

  #[Test]
  public function setPageLastPageWithPartialResultsReturnsRemainingRows(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 3); // 10 items, 3 per page = 4 pages

    $pager->setPage(4);
    $rows = $pager->getRows();

    $this->assertCount(1, $rows); // Only 1 item on last page
    $this->assertSame('Jack', $rows[0]['name']);
  }

  // =========================================================================
  // Pagination State Tests
  // =========================================================================

  #[Test]
  public function hasPaginationWithMultiplePagesReturnsTrue(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 5);

    $this->assertTrue($pager->hasPagination());
  }

  #[Test]
  public function hasPaginationWithOnePageReturnsFalse(): void
  {
    $data = array_slice($this->getSampleData(), 0, 5);
    $pager = new ArrayPager($data, 10);

    $this->assertFalse($pager->hasPagination());
  }

  #[Test]
  public function hasPrevOnFirstPageReturnsFalse(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 3);

    $this->assertFalse($pager->hasPrev());
  }

  #[Test]
  public function hasPrevOnSecondPageReturnsTrue(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 3);

    $pager->setPage(2);

    $this->assertTrue($pager->hasPrev());
  }

  #[Test]
  public function hasNextOnLastPageReturnsFalse(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 3);

    $pager->setPage(4); // Last page

    $this->assertFalse($pager->hasNext());
  }

  #[Test]
  public function hasNextOnFirstPageReturnsTrue(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 3);

    $this->assertTrue($pager->hasNext());
  }

  // =========================================================================
  // Search/Filter Tests
  // =========================================================================

  #[Test]
  public function searchWithMatchingTermFiltersResults(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 10);

    $pager->search('Alice');

    $this->assertSame(1, $pager->getTotal());
    $rows = $pager->getRows();
    $this->assertSame('Alice', $rows[0]['name']);
  }

  #[Test]
  public function searchCaseInsensitiveFindsMatches(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 10);

    $pager->search('alice');

    $this->assertSame(1, $pager->getTotal());
  }

  #[Test]
  public function searchPartialMatchFindsResults(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 10);

    $pager->search('ice'); // Should match Alice

    $this->assertSame(1, $pager->getTotal());
    $rows = $pager->getRows();
    $this->assertSame('Alice', $rows[0]['name']);
  }

  #[Test]
  public function searchSearchesAllFieldsFindsMatches(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 10);

    $pager->search('Toronto'); // City field

    $this->assertSame(1, $pager->getTotal());
    $rows = $pager->getRows();
    $this->assertSame('Alice', $rows[0]['name']);
  }

  #[Test]
  public function searchWithEmptyTermReturnsAllResults(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 10);

    $pager->search('');

    $this->assertSame(10, $pager->getTotal());
  }

  #[Test]
  public function searchWithNoMatchesReturnsEmpty(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 10);

    $pager->search('ZZZZZ');

    $this->assertSame(0, $pager->getTotal());
    $this->assertSame([], $pager->getRows());
  }

  #[Test]
  public function searchResetsToPageOne(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 3);

    $pager->setPage(2);
    $pager->search('a'); // Many matches

    $this->assertSame(1, $pager->getPage());
  }

  #[Test]
  public function searchRecalculatesTotalPages(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 3);

    $originalPages = $pager->getTotalPages();
    $pager->search('Alice'); // Only 1 result

    $this->assertSame(1, $pager->getTotalPages());
    $this->assertNotSame($originalPages, $pager->getTotalPages());
  }

  // =========================================================================
  // Sorting Tests
  // =========================================================================

  #[Test]
  public function sortByAscendingNumericSortsCorrectly(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 10);

    $pager->sortBy('age', 'asc');
    $rows = $pager->getRows();

    $this->assertSame(25, $rows[0]['age']); // Bob (youngest)
    $this->assertSame(26, $rows[1]['age']); // Iris
    $this->assertSame(35, $rows[9]['age']); // Charlie (oldest)
  }

  #[Test]
  public function sortByDescendingNumericSortsCorrectly(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 10);

    $pager->sortBy('age', 'desc');
    $rows = $pager->getRows();

    $this->assertSame(35, $rows[0]['age']); // Charlie
    $this->assertSame(33, $rows[1]['age']); // Jack
    $this->assertSame(25, $rows[9]['age']); // Bob
  }

  #[Test]
  public function sortByAscendingStringSortsAlphabetically(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 10);

    $pager->sortBy('name', 'asc');
    $rows = $pager->getRows();

    $this->assertSame('Alice', $rows[0]['name']);
    $this->assertSame('Bob', $rows[1]['name']);
    $this->assertSame('Charlie', $rows[2]['name']);
  }

  #[Test]
  public function sortByDescendingStringSortReverseAlphabetically(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 10);

    $pager->sortBy('name', 'desc');
    $rows = $pager->getRows();

    $this->assertSame('Jack', $rows[0]['name']);
    $this->assertSame('Iris', $rows[1]['name']);
    $this->assertSame('Henry', $rows[2]['name']);
  }

  #[Test]
  public function sortByWithNullValuesPlacesNullsAtEnd(): void
  {
    $data = [
        ['id' => 1, 'score' => 100],
        ['id' => 2, 'score' => null],
        ['id' => 3, 'score' => 50],
        ['id' => 4, 'score' => null],
        ['id' => 5, 'score' => 75],
    ];
    $pager = new ArrayPager($data, 10);

    $pager->sortBy('score', 'asc');
    $rows = $pager->getRows();

    $this->assertSame(50, $rows[0]['score']);
    $this->assertSame(75, $rows[1]['score']);
    $this->assertSame(100, $rows[2]['score']);
    $this->assertNull($rows[3]['score']);
    $this->assertNull($rows[4]['score']);
  }

  #[Test]
  public function sortByWithMissingFieldHandlesSafely(): void
  {
    $data = [
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2], // Missing 'name'
        ['id' => 3, 'name' => 'Bob'],
    ];
    $pager = new ArrayPager($data, 10);

    $pager->sortBy('name', 'asc');
    $rows = $pager->getRows();

    $this->assertSame('Alice', $rows[0]['name']);
    $this->assertSame('Bob', $rows[1]['name']);
    $this->assertArrayNotHasKey('name', $rows[2]);
  }

  #[Test]
  public function sortByMaintainsPagination(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 3);

    $pager->setPage(2);
    $pager->sortBy('name', 'asc');

    // Sorting should repaginate current page
    $rows = $pager->getRows();
    $this->assertCount(3, $rows);
  }

  // =========================================================================
  // Interface Implementation Tests
  // =========================================================================

  #[Test]
  public function implementsPagerInterface(): void
  {
    $pager = new ArrayPager([]);

    $this->assertInstanceOf(PagerInterface::class, $pager);
  }

  #[Test]
  public function interfaceMethodsAreAccessible(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 5);

    // All interface methods should be callable
    $this->assertIsArray($pager->getRows());
    $this->assertIsBool($pager->hasPagination());
    $this->assertIsInt($pager->getPage());
    $this->assertIsInt($pager->getTotalPages());
    $this->assertIsBool($pager->hasPrev());
    $this->assertIsBool($pager->hasNext());
  }

  // =========================================================================
  // Edge Cases and Integration Tests
  // =========================================================================

  #[Test]
  public function complexWorkflowSearchSortPaginateWorksCorrectly(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 3);

    // Search for common letter
    $pager->search('a');
    $searchResults = $pager->getTotal();
    $this->assertGreaterThan(0, $searchResults);

    // Sort by age
    $pager->sortBy('age', 'asc');

    // Navigate to page 2 if available
    if ($pager->getTotalPages() > 1) {
      $pager->setPage(2);
      $this->assertSame(2, $pager->getPage());
    }

    // Should still have results
    $this->assertNotEmpty($pager->getRows());
  }

  #[Test]
  public function getTotalReturnsCorrectCount(): void
  {
    $data = $this->getSampleData();
    $pager = new ArrayPager($data, 3);

    $this->assertSame(10, $pager->getTotal());
  }

  #[Test]
  public function getTotalPagesWithExactDivisionCalculatesCorrectly(): void
  {
    $data = array_fill(0, 20, ['test' => 'data']);
    $pager = new ArrayPager($data, 5);

    $this->assertSame(4, $pager->getTotalPages());
  }

  #[Test]
  public function getTotalPagesWithPartialPageRoundsUp(): void
  {
    $data = array_fill(0, 22, ['test' => 'data']);
    $pager = new ArrayPager($data, 5);

    $this->assertSame(5, $pager->getTotalPages());
  }

  #[Test]
  public function emptyDataSetHandlesAllOperationsSafely(): void
  {
    $pager = new ArrayPager([]);

    $this->assertSame([], $pager->getRows());
    $this->assertSame(0, $pager->getTotal());
    $this->assertSame(1, $pager->getTotalPages());
    $this->assertFalse($pager->hasPagination());
    $this->assertFalse($pager->hasPrev());
    $this->assertFalse($pager->hasNext());

    // Operations should not crash
    $pager->setPage(1);
    $pager->search('test');
    $pager->sortBy('field', 'asc');

    $this->assertSame([], $pager->getRows());
  }

  private function getSampleData(): array
  {
    return [
        ['id' => 1, 'name' => 'Alice', 'age' => 30, 'city' => 'Toronto'],
        ['id' => 2, 'name' => 'Bob', 'age' => 25, 'city' => 'Vancouver'],
        ['id' => 3, 'name' => 'Charlie', 'age' => 35, 'city' => 'Montreal'],
        ['id' => 4, 'name' => 'Diana', 'age' => 28, 'city' => 'Calgary'],
        ['id' => 5, 'name' => 'Eve', 'age' => 32, 'city' => 'Ottawa'],
        ['id' => 6, 'name' => 'Frank', 'age' => 27, 'city' => 'Edmonton'],
        ['id' => 7, 'name' => 'Grace', 'age' => 31, 'city' => 'Winnipeg'],
        ['id' => 8, 'name' => 'Henry', 'age' => 29, 'city' => 'Halifax'],
        ['id' => 9, 'name' => 'Iris', 'age' => 26, 'city' => 'Victoria'],
        ['id' => 10, 'name' => 'Jack', 'age' => 33, 'city' => 'Quebec'],
    ];
  }
}
