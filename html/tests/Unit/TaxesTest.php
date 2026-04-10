<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\TaxBracketCollection;
use PayCal\Taxes;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for Taxes class.
 *
 * Tests the Taxes class methods including:
 * - calculateFederalTax() using default federal brackets
 * - calculateProvincialTax() using default provincial brackets
 * - calculateEmploymentInsurance() (EI deductions)
 * - calculateCanadaPensionPlan() (CPP deductions)
 * - calculateOldAgeSecurity() (OAS clawback)
 * - calculateTaxes() (complete tax certificate calculation)
 * - Default bracket retrieval methods
 *
 * Note: Private tax calculator classes are tested indirectly
 * through the public Taxes class methods.
 *
 * @internal
 *
 */
#[Group('unit')]
final class TaxesTest extends TestCase
{
  #[Test]
  public function taxesCalculateProvincialTaxCentsWithZeroIncomeReturnsZero(): void
  {
    $taxes = $this->createTaxesInstance();
    $provincial = $taxes->calculateProvincialTaxCents(0);
    $this->assertEquals(0, $provincial);
  }

  #[Test]
  public function taxesCalculateProvincialTaxCentsWithHighIncomeCalculatesCorrectly(): void
  {
    $taxes = $this->createTaxesInstance();
    $provincial = $taxes->calculateProvincialTaxCents(10000000); // $100,000
    $this->assertGreaterThanOrEqual(850000, $provincial); // >= $8,500
  }

  // ==========================================
  // Integrated calculateTaxesCents() Tests
  // ==========================================

  #[Test]
  public function taxesCalculateTaxesCentsReturnsCompleteStructure(): void
  {
    $taxes = $this->createTaxesInstance();
    $result = $taxes->calculateTaxesCents(5000000); // $50,000
    $this->assertIsArray($result);
    $this->assertArrayHasKey('federal', $result);
    $this->assertArrayHasKey('provincial', $result);
    $this->assertArrayHasKey('employment_insurance', $result);
    $this->assertArrayHasKey('canada_pension_plan', $result);
    $this->assertArrayHasKey('old_age_security', $result);
    $this->assertArrayHasKey('incomeTax', $result);
    $this->assertArrayHasKey('totalDeductions', $result);
  }

  #[Test]
  public function taxesCalculateTaxesCentsCalculatesIncomeTaxCorrectly(): void
  {
    $taxes = $this->createTaxesInstance();
    $result = $taxes->calculateTaxesCents(5000000); // $50,000
    $expectedIncomeTax = $result['federal'] + $result['provincial'];
    $this->assertEquals($expectedIncomeTax, $result['incomeTax']);
  }

  #[Test]
  public function taxesCalculateTaxesCentsCalculatesTotalDeductionsCorrectly(): void
  {
    $taxes = $this->createTaxesInstance();
    $result = $taxes->calculateTaxesCents(5000000); // $50,000
    $expectedTotal = $result['federal']
                   + $result['provincial']
                   + $result['employment_insurance']
                   + $result['canada_pension_plan']
                   + $result['old_age_security'];
    $this->assertEquals($expectedTotal, $result['totalDeductions']);
  }

  #[Test]
  public function taxesCalculateTaxesCentsWithZeroIncomeReturnsAllZeros(): void
  {
    $taxes = $this->createTaxesInstance();
    $result = $taxes->calculateTaxesCents(0);
    $this->assertEquals(0, $result['federal']);
    $this->assertEquals(0, $result['provincial']);
    $this->assertEquals(0, $result['employment_insurance']);
    $this->assertEquals(0, $result['canada_pension_plan']);
    $this->assertEquals(0, $result['old_age_security']);
    $this->assertEquals(0, $result['incomeTax']);
    $this->assertEquals(0, $result['totalDeductions']);
  }

  #[Test]
  public function taxesCalculateTaxesCentsWithLowIncomeHasOnlyBasicDeductions(): void
  {
    $taxes = $this->createTaxesInstance();
    $result = $taxes->calculateTaxesCents(400000); // $4,000
    $this->assertGreaterThan(0, $result['federal']);
    $this->assertGreaterThan(0, $result['provincial']);
    $this->assertGreaterThan(0, $result['employment_insurance']);
    $this->assertGreaterThan(0, $result['canada_pension_plan']);
    $this->assertEquals(0, $result['old_age_security']); // Below OAS threshold
  }

  #[Test]
  public function taxesCalculateTaxesWithHighIncomeHasOASClawback(): void
  {
    $taxes = $this->createTaxesInstance();

    // $100,000 is above OAS threshold
    $result = $taxes->calculateTaxes(100000);

    $this->assertGreaterThan(0, $result['federal']);
    $this->assertGreaterThan(0, $result['provincial']);
    $this->assertGreaterThan(0, $result['employment_insurance']);
    $this->assertGreaterThan(0, $result['canada_pension_plan']);
    $this->assertGreaterThan(0, $result['old_age_security']); // Above threshold
  }

  #[Test]
  #[DataProvider('incomeScenarios')]
  public function taxesCalculateTaxesWithVariousIncomesCalculatesCorrectly(
    float $income,
    float $expectedMinTotal,
    float $expectedMaxTotal
  ): void {
    $taxes = $this->createTaxesInstance();

    $result = $taxes->calculateTaxes($income);

    $this->assertGreaterThanOrEqual($expectedMinTotal, $result['totalDeductions']);
    $this->assertLessThanOrEqual($expectedMaxTotal, $result['totalDeductions']);
  }

  public static function incomeScenarios(): array
  {
    return [
        'minimum wage' => [20000, 5600, 5900],
        'median income' => [50000, 14400, 14800],
        'high income' => [100000, 32000, 32600],
        'very high income' => [200000, 83500, 84500],
    ];
  }

  // ==========================================
  // Default Bracket Tests
  // ==========================================

  #[Test]
  public function taxesGetDefaultFederalBracketsReturnsCollection(): void
  {
    $taxes = $this->createTaxesInstance();

    $brackets = $taxes->getDefaultFederalBrackets();

    $this->assertInstanceOf(TaxBracketCollection::class, $brackets);
    $this->assertCount(5, $brackets); // 5 federal brackets
  }

  #[Test]
  public function taxesGetDefaultFederalBracketsHasCorrectFirstBracket(): void
  {
    $taxes = $this->createTaxesInstance();

    $brackets = $taxes->getDefaultFederalBrackets();

    // Test first bracket by calculating tax on income in first bracket
    $tax = $brackets->calculateTax(40000);
    $this->assertEquals(5600, $tax); // 40000 * 0.14 for 2026
  }

  #[Test]
  public function taxesGetDefaultProvincialBracketsWithAlbertaReturnsCollection(): void
  {
    $taxes = $this->createTaxesInstance();

    $brackets = $taxes->getDefaultProvincialBrackets('Alberta');

    $this->assertInstanceOf(TaxBracketCollection::class, $brackets);
    $this->assertCount(6, $brackets); // 6 Alberta brackets (2026)
  }

  #[Test]
  public function taxesGetDefaultProvincialBracketsWithOntarioReturnsCollection(): void
  {
    $taxes = $this->createTaxesInstance();

    $brackets = $taxes->getDefaultProvincialBrackets('Ontario');

    $this->assertInstanceOf(TaxBracketCollection::class, $brackets);
    $this->assertCount(5, $brackets); // 5 Ontario brackets (2026)
  }

  #[Test]
  public function taxesGetDefaultProvincialBracketsWithInvalidProvinceFallsBackToAlberta(): void
  {
    $taxes = $this->createTaxesInstance();

    $brackets = $taxes->getDefaultProvincialBrackets('InvalidProvince');

    $this->assertInstanceOf(TaxBracketCollection::class, $brackets);

    // Should match Alberta brackets (first bracket is 10%)
    $tax = $brackets->calculateTax(40000);
    $this->assertEquals(3200, $tax); // 40000 * 0.08 (Alberta 2026 first rate)
  }

  #[Test]
  #[DataProvider('provinceProvider')]
  public function taxesGetDefaultProvincialBracketsAllProvincesReturnValidCollections(
    string $province,
    int $expectedBracketCount
  ): void {
    $taxes = $this->createTaxesInstance();

    $brackets = $taxes->getDefaultProvincialBrackets($province);

    $this->assertInstanceOf(TaxBracketCollection::class, $brackets);
    $this->assertCount($expectedBracketCount, $brackets);
  }

  public static function provinceProvider(): array
  {
    return [
      ['Alberta', 6],
      ['British Columbia', 7],
      ['Manitoba', 3],
      ['New Brunswick', 4],
        ['Newfoundland and Labrador', 8],
        ['Northwest Territories', 4],
        ['Nova Scotia', 5],
        ['Nunavut', 4],
      ['Ontario', 5],
      ['Prince Edward Island', 5],
      ['Quebec', 4],
      ['Saskatchewan', 3],
      ['Yukon', 6],
    ];
  }

  // ==========================================
  // Helper Methods
  // ==========================================

  /**
   * Create Taxes instance
   * Simply calls the constructor which should work with null Database calls.
   */
  private function createTaxesInstance(): Taxes
  {
    // Mock Database class if it hasn't been mocked yet
    if (!class_exists('Database')) {
      eval('class Database { public static function hget($key, $field) { return null; } }');
    }

    return new Taxes();
  }
}
