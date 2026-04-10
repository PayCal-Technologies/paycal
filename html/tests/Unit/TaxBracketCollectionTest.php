<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\InvalidArgumentException;
use PayCal\Domain\TaxBracket;
use PayCal\Domain\TaxBracketCollection;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__.'/../../tests/bootstrap.php';

/**
 * Unit tests for TaxBracketCollection.
 *
 * Tests collection creation, validation, sorting, tax calculation,
 * and iteration without any external dependencies.
 *
 * @internal
 *
 */
#[Group('unit')]
final class TaxBracketCollectionTest extends TestCase
{
  // ===== Constructor Validation Tests =====

  public function testConstructorAcceptsValidBrackets(): void
  {
    $brackets = [
        new TaxBracket(0, 50000, 1500),
        new TaxBracket(50000, 100000, 2000),
    ];

    $collection = new TaxBracketCollection($brackets);

    $this->assertSame(2, $collection->count());
  }

  public function testConstructorRejectsEmptyArray(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Brackets cannot be empty');

    new TaxBracketCollection([]);
  }

  public function testConstructorAcceptsSingleBracket(): void
  {
    $brackets = [
        new TaxBracket(0, PHP_INT_MAX, 1500),
    ];

    $collection = new TaxBracketCollection($brackets);

    $this->assertSame(1, $collection->count());
  }

  // ===== Factory Method Tests =====

  public function testFromArraysCreatesValidCollection(): void
  {
    $arrays = [
        [0, 5000000, 1500],
        [5000000, 10000000, 2000],
    ];

    $collection = TaxBracketCollection::fromArrays($arrays);

    $this->assertSame(2, $collection->count());
  }

  public function testFromArraysSortsBrackets(): void
  {
    // Input in dollars - deliberately out of order
    $arrays = [
        [5000000, 10000000, 2000],
        [0, 5000000, 1500],
    ];
    $collection = TaxBracketCollection::fromArrays($arrays);
    $result = $collection->toArrays();
    // Output in cents - sorted by minIncome (ascending)
    // First bracket should be the one starting at 0
    $this->assertSame(0, $result[0][0]); // First bracket starts at 0 cents
    $this->assertSame(5000000, $result[0][1]); // First bracket ends at 5000000 cents
  }

  public function testFromArraysValidatesEachBracket(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Tax rate must be between 0 and 1');

    $arrays = [
        [0, 50000, 15000], // Invalid rate (should be <= 10000)
    ];

    TaxBracketCollection::fromArrays($arrays);
  }

  // ===== Data Conversion Tests =====

  public function testToArraysReturnsCorrectStructure(): void
  {
    $brackets = [
        new TaxBracket(0, 50000, 1500),
        new TaxBracket(50000, 100000, 2000),
    ];

    $collection = new TaxBracketCollection($brackets);
    $arrays = $collection->toArrays();

    $this->assertSame([
        [0, 50000, 1500],
        [50000, 100000, 2000],
    ], $arrays);
  }

  public function testFromArraysToArraysRoundTrip(): void
  {
    // Input in dollars, output in cents (internal format)
    $original = [
        [0, 5471300, 1500],
        [5471300, 10942400, 2050],
        [10942400, 17320500, 2600],
    ];
    $collection = TaxBracketCollection::fromArrays($original);
    $result = $collection->toArrays();
    // Expected output in cents
    $expected = [
        [0, 5471300, 1500],
        [5471300, 10942400, 2050],
        [10942400, 17320500, 2600],
    ];
    $this->assertSame($expected, $result);
  }

  // ===== Tax Calculation Tests =====

  public function testCalculateTaxWithSingleBracket(): void
  {
    $brackets = [
        new TaxBracket(0, 10000000, 1500),
    ];
    $collection = new TaxBracketCollection($brackets);
    $tax = $collection->calculateTaxCents(5000000);
    // Expected: 5000000 * 1500 / 10000 = 750000
    $this->assertSame(750000, $tax);
  }

  public function testCalculateTaxAcrossMultipleBrackets(): void
  {
    $brackets = [
        new TaxBracket(0, 5000000, 1500),
        new TaxBracket(5000000, 10000000, 2000),
    ];
    $collection = new TaxBracketCollection($brackets);
    $tax = $collection->calculateTaxCents(7500000);
    // First bracket: 5000000 * 1500 / 10000 = 750000
    // Second bracket: 2500000 * 2000 / 10000 = 500000
    // Total: 1250000
    $this->assertSame(1250000, $tax);
  }

  public function testCalculateTaxWithIncomeBelowFirstBracket(): void
  {
    $brackets = [
        new TaxBracket(1000000, 5000000, 1500),
    ];

    $collection = new TaxBracketCollection($brackets);
    $tax = $collection->calculateTaxCents(500000);

    // Income below bracket minimum
    $this->assertSame(0, $tax);
  }

  public function testCalculateTaxWithIncomeAboveAllBrackets(): void
  {
    $brackets = [
        new TaxBracket(0, 5000000, 1500),
        new TaxBracket(5000000, 10000000, 2000),
    ];
    $collection = new TaxBracketCollection($brackets);
    $tax = $collection->calculateTaxCents(15000000);
    // First bracket: 5000000 * 1500 / 10000 = 750000
    // Second bracket: 5000000 * 2000 / 10000 = 1000000
    // Total: 1750000
    $this->assertSame(1750000, $tax);
  }

  public function testCalculateTaxWithZeroIncome(): void
  {
    $brackets = [
        new TaxBracket(0, 5000000, 1500),
    ];

    $collection = new TaxBracketCollection($brackets);
    $tax = $collection->calculateTaxCents(0);

    $this->assertSame(0, $tax);
  }

  public function testCalculateTaxWithCanadianFederalBrackets(): void
  {
    // Real Canadian federal tax brackets (simplified)
    $brackets = [
        new TaxBracket(0, 5471300, 1500),
        new TaxBracket(5471300, 10942400, 2050),
        new TaxBracket(10942400, 17320500, 2600),
        new TaxBracket(17320500, 24675200, 2900),
        new TaxBracket(24675200, PHP_INT_MAX, 3300),
    ];

    $collection = new TaxBracketCollection($brackets);

    // Test with $80,000 income (in cents)
    $tax = $collection->calculateTaxCents(8000000);
    // Expected:
    // Bracket 1 (0-5471300): 5471300 * 1500 / 10000 = 820695
    // Bracket 2 (5471300-8000000): 2528700 * 2050 / 10000 = 518384 (rounded)
    // Total: 1339079
    $this->assertSame(1339079, $tax);
  }

  public function testCalculateTaxWithManyBrackets(): void
  {
    $brackets = [
        new TaxBracket(0, 1000000, 1000),
        new TaxBracket(1000000, 2000000, 1500),
        new TaxBracket(2000000, 3000000, 2000),
        new TaxBracket(3000000, 4000000, 2500),
        new TaxBracket(4000000, 5000000, 3000),
    ];

    $collection = new TaxBracketCollection($brackets);
    $tax = $collection->calculateTaxCents(3500000);

    // Expected:
    // Bracket 1: 1000000 * 0.10 = 100000
    // Bracket 2: 1000000 * 0.15 = 150000
    // Bracket 3: 1000000 * 0.20 = 200000
    // Bracket 4: 500000 * 0.25 = 125000
    // Total: 575000
    $this->assertSame(575000, $tax);
  }

  // ===== Iteration Tests =====

  public function testCanIterateWithForeach(): void
  {
    $brackets = [
        new TaxBracket(0, 5471300, 1500),
        new TaxBracket(5471300, 10942400, 2050),
        new TaxBracket(10942400, 17320500, 2600),
        new TaxBracket(17320500, 24675200, 2900),
        new TaxBracket(24675200, PHP_INT_MAX, 3300),
    ];
    $collection = new TaxBracketCollection($brackets);
    $count = 0;
    foreach ($collection as $bracket) {
      ++$count;
    }
    $this->assertSame(5, $count);
  }

  public function testIteratorMaintainsSortedOrder(): void
  {
    $brackets = [
        new TaxBracket(5000000, 10000000, 2000),
        new TaxBracket(0, 5000000, 1500),
    ];
    $collection = new TaxBracketCollection($brackets);
    $minIncomes = [];
    foreach ($collection as $bracket) {
      $minIncomes[] = $bracket->minIncomeCents;
    }
    $this->assertSame([0, 5000000], $minIncomes);
  }

  public function testIteratorReturnsCorrectBrackets(): void
  {
    $brackets = [
        new TaxBracket(0, 5000000, 1500),
        new TaxBracket(5000000, 10000000, 2000),
    ];

    $collection = new TaxBracketCollection($brackets);

    $iterator = $collection->getIterator();

    $this->assertInstanceOf(ArrayIterator::class, $iterator);
    $this->assertCount(2, $iterator);
  }

  // ===== Count Tests =====

  public function testCountReturnsBracketCount(): void
  {
    $brackets = [
        new TaxBracket(0, 5000000, 1500),
        new TaxBracket(5000000, 10000000, 2000),
        new TaxBracket(10000000, 15000000, 2500),
    ];

    $collection = new TaxBracketCollection($brackets);

    $this->assertSame(3, $collection->count());
  }

  public function testCountWithSingleBracket(): void
  {
    $brackets = [
        new TaxBracket(0, 10000000, 1500),
    ];

    $collection = new TaxBracketCollection($brackets);

    $this->assertSame(1, $collection->count());
  }

  // ===== Edge Cases =====

  public function testVeryLargeMaxIncomeInLastBracket(): void
  {
    $brackets = [
        new TaxBracket(0, 5000000, 1500),
        new TaxBracket(5000000, PHP_INT_MAX, 2000),
    ];

    $collection = new TaxBracketCollection($brackets);

    // Should handle very large incomes
    $tax = $collection->calculateTaxCents(100000000000);

    $this->assertGreaterThan(0, $tax);
  }

  public function testBracketsWithZeroRate(): void
  {
    $brackets = [
        new TaxBracket(0, 2000000, 0), // No tax on first 20k
        new TaxBracket(2000000, 5000000, 1500),
    ];

    $collection = new TaxBracketCollection($brackets);
    $tax = $collection->calculateTaxCents(3000000);

    // Expected:
    // First bracket: 2000000 * 0.0 = 0
    // Second bracket: 1000000 * 0.15 = 150000
    // Total: 150000
    $this->assertSame(150000, $tax);
  }

  public function testBracketsWithVerySmallRanges(): void
  {
    $brackets = [
        new TaxBracket(0, 1, 1000),
        new TaxBracket(1, 2, 1500),
    ];

    $collection = new TaxBracketCollection($brackets);

    $this->assertSame(2, $collection->count());
  }

  public function testUnsortedInputAutoSorts(): void
  {
    // Deliberately create in random order - values in dollars
    $arrays = [
        [10000000, 15000000, 2600],
        [0, 5000000, 1500],
        [5000000, 10000000, 2000],
    ];
    $collection = TaxBracketCollection::fromArrays($arrays);
    $result = $collection->toArrays();
    // toArrays returns values in cents (internal format)
    $expected = [
        [0, 5000000, 1500],
        [5000000, 10000000, 2000],
      [10000000, 15000000, 2600],
    ];
    $this->assertSame($expected, $result);
  }

  // ===== Guard Tests for Integer Refactoring =====

  /**
   * MONOTONICITY: Tax must never decrease as income increases
   * This is a critical invariant that must hold for progressive tax systems.
   */
  public function testMonotonicityTaxNeverDecreasesAsIncomeIncreases(): void
  {
    $brackets = [
        new TaxBracket(0, 5000000, 1500),
        new TaxBracket(5000000, 10000000, 2000),
        new TaxBracket(10000000, 20000000, 3000),
    ];
    $collection = new TaxBracketCollection($brackets);
    $previousTax = 0;
    for ($income = 0; $income <= 25000000; $income += 100000) {
      $tax = $collection->calculateTaxCents($income);
      $this->assertGreaterThanOrEqual(
        $previousTax,
        $tax,
        "Tax decreased at income {$income}: previous={$previousTax}, current={$tax}"
      );
      $previousTax = $tax;
    }
  }

  /**
   * BRACKET BOUNDARY: Test exact boundary values
   * These are the most sensitive points for rounding errors.
   */
  public function testBracketBoundaryExactBoundaryValues(): void
  {
    $brackets = [
        new TaxBracket(0, 5000000, 1500),
        new TaxBracket(5000000, 10000000, 2000),
    ];

    $collection = new TaxBracketCollection($brackets);

    // At exactly 5000000 (boundary)
    $taxAtBoundary = $collection->calculateTaxCents(5000000);
    $this->assertSame(750000, $taxAtBoundary); // 5000000 * 1500 / 10000 = 750000

    // One cent above boundary
    $taxAboveBoundary = $collection->calculateTaxCents(5000001);
    // Should be: 5000000 * 0.15 * 100 + 1 * 0.20 * 100 = 75000000 + 20 = 75000020
    $this->assertGreaterThanOrEqual($taxAtBoundary, $taxAboveBoundary);
  }

  /**
   * BRACKET BOUNDARY: Test boundary + 1 cent scenarios.
   */
  public function testBracketBoundaryPlusOneCent(): void
  {
    $brackets = [
        new TaxBracket(0, 5000000, 1500),
        new TaxBracket(5000000, 10000000, 2000),
    ];
    $collection = new TaxBracketCollection($brackets);
    $tax = $collection->calculateTaxCents(7500000);
    // Expected:
    // First bracket: 5000000 * 1500 / 10000 = 750000
    // Second bracket: 2500000 * 2000 / 10000 = 500000
    // Total: 1250000
    $this->assertSame(1250000, $tax);
    // One cent above
    $taxAboveBoundary = $collection->calculateTaxCents(10000001);
    $this->assertGreaterThanOrEqual($tax, $taxAboveBoundary);
  }

  /**
   * DETERMINISM: Same input must always produce same output.
   */
  public function testDeterminismSameInputSameOutput(): void
  {
    $brackets = [
        new TaxBracket(0, 5000000, 1500),
        new TaxBracket(5000000, 10000000, 2000),
    ];

    $collection = new TaxBracketCollection($brackets);

    // Calculate same tax 100 times
    // Income 7500000: Bracket 1 = 5000000*0.15=750000, Bracket 2 = 2500000*0.20=500000, Total = 1250000
    $results = [];
    for ($i = 0; $i < 100; ++$i) {
      $results[] = $collection->calculateTaxCents(7500000);
    }

    // All results must be identical
    $this->assertSame(1250000, $results[0]);
    foreach ($results as $result) {
      $this->assertSame($results[0], $result, 'Tax calculation is not deterministic');
    }
  }

  /**
   * REAL WORLD: Test with actual 2025 federal brackets
   * This ensures our refactoring doesn't break real calculations.
   */

  // ===== Integer Methods Tests =====

  public function testFromCentsArraysCreatesValidCollection(): void
  {
    $arrays = [
        [0, 5000000, 1500],      // 0-$50,000 at 15%
        [5000000, 10000000, 2000], // $50,000-$100,000 at 20%
    ];

    $collection = TaxBracketCollection::fromCentsArrays($arrays);

    $this->assertCount(2, $collection);
  }

  public function testToCentsArraysReturnsCorrectStructure(): void
  {
    $brackets = [
        new TaxBracket(0, 50000, 1500),
        new TaxBracket(50000, 100000, 2000),
    ];
    $collection = new TaxBracketCollection($brackets);
    $arrays = $collection->toCentsArrays();
    $this->assertSame([[0, 50000, 1500], [50000, 100000, 2000]], $arrays);
  }

  public function testCalculateTaxCentsWithSingleBracket(): void
  {
    $brackets = [
        new TaxBracket(0, 10000000, 1500),
    ];

    $collection = new TaxBracketCollection($brackets);

    // $50,000 = 5000000 cents
    // Tax: 5000000 * 1500 / 10000 = 750000 cents = $7,500
    $tax = $collection->calculateTaxCents(5000000);

    $this->assertSame(750000, $tax);
  }

  public function testCalculateTaxCentsAcrossMultipleBrackets(): void
  {
    $brackets = [
        new TaxBracket(0, 5000000, 1500),
        new TaxBracket(5000000, 10000000, 2000),
    ];

    $collection = new TaxBracketCollection($brackets);

    // $75,000 = 7500000 cents
    // Bracket 1: 5000000 * 1500 / 10000 = 750000 cents
    // Bracket 2: 2500000 * 2000 / 10000 = 500000 cents
    // Total: 1250000 cents = $12,500
    $tax = $collection->calculateTaxCents(7500000);

    $this->assertSame(1250000, $tax);
  }

  public function testCalculateTaxCentsWithZeroIncome(): void
  {
    $brackets = [
        new TaxBracket(0, 50000, 1500),
    ];

    $collection = new TaxBracketCollection($brackets);
    $tax = $collection->calculateTaxCents(0);

    $this->assertSame(0, $tax);
  }

  public function testCalculateTaxCentsMonotonicity(): void
  {
    $brackets = [
        new TaxBracket(0, 50000, 1500),
        new TaxBracket(50000, 100000, 2000),
        new TaxBracket(100000, 200000, 3000),
    ];

    $collection = new TaxBracketCollection($brackets);

    $previousTax = 0;
    for ($incomeCents = 0; $incomeCents <= 25000000; $incomeCents += 100000) {
      $tax = $collection->calculateTaxCents($incomeCents);
      $this->assertGreaterThanOrEqual(
        $previousTax,
        $tax,
        "Tax decreased at income {$incomeCents} cents"
      );
      $previousTax = $tax;
    }
  }

  public function testCalculateTaxCentsDeterminism(): void
  {
    $brackets = [
        new TaxBracket(0, 50000, 1500),
        new TaxBracket(50000, 100000, 2000),
    ];

    $collection = new TaxBracketCollection($brackets);

    $results = [];
    for ($i = 0; $i < 100; ++$i) {
      $results[] = $collection->calculateTaxCents(7500000);
    }

    foreach ($results as $result) {
      $this->assertSame($results[0], $result, 'Integer tax calculation is not deterministic');
    }
  }
}
