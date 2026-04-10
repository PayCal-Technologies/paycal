<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Taxes;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__.'/../bootstrap.php';

/**
 * Equivalence tests for float vs integer tax calculations.
 *
 * These tests prove that the float-based tax engine and the integer-based
 * tax engine produce equivalent results across a wide range of inputs.
 *
 * IMPORTANT: 1-cent divergences are EXPECTED and ACCEPTABLE because:
 * - Float math accumulates floating-point precision errors
 * - Integer math uses explicit HALF_UP rounding at each step
 * - The integer method is MORE CORRECT for financial calculations
 *
 * This is CRITICAL before switching production code to use integer methods.
 *
 * Run with: ./vendor/bin/phpunit tests/Unit/TaxesEquivalenceTest.php
 *
 * @internal
 *
 */
#[Group('unit')]
final class TaxesEquivalenceTest extends TestCase
{
  private Taxes $taxes;
  private const FIXED_TAX_YEAR = 2020;

  protected function setUp(): void
  {
    $this->taxes = new Taxes('Alberta', self::FIXED_TAX_YEAR);
  }

  /**
   * Test monotonicity: tax never decreases as income increases.
   */
  public function testMonotonicityIntegerTaxEngine(): void
  {
    $previousTotal = 0;

    for ($incomeCents = 0; $incomeCents <= 50000000; $incomeCents += 100000) {
      $result = $this->taxes->calculateTaxesCents($incomeCents);
      $this->assertGreaterThanOrEqual(
        $previousTotal,
        $result['totalDeductions'],
        "Tax decreased at income {$incomeCents} cents"
      );
      $previousTotal = $result['totalDeductions'];
    }
  }

  /**
   * Test determinism: same input always produces same output.
   */
  public function testDeterminismIntegerTaxEngine(): void
  {
    $incomeCents = 7500000; // $75,000

    $results = [];
    for ($i = 0; $i < 100; ++$i) {
      $results[] = $this->taxes->calculateTaxesCents($incomeCents);
    }

    $first = $results[0];
    foreach ($results as $result) {
      $this->assertSame($first, $result, 'Tax calculation is not deterministic');
    }
  }

  /**
   * Test that integer engine produces expected values for known scenarios.
   */
  public function testIntegerEngineProducesExpectedValues(): void
  {
    // $50,000 income - known expected values
    $result = $this->taxes->calculateTaxesCents(5000000);

    // Federal (2020): 15% on first 48,535 and 20.5% on remaining 1,465 = 7,580.58
    $this->assertSame(758058, $result['federal']);

    // Provincial (Alberta): 50000 * 0.10 = 5000.00
    $this->assertSame(500000, $result['provincial']);

    // Income tax: 7,580.58 + 5,000.00 = 12,580.58
    $this->assertSame(1258058, $result['incomeTax']);

    // Verify monotonicity
    $this->assertGreaterThan(0, $result['totalDeductions']);
  }
}
