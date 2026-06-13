<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain\Tax;

use PayCal\Domain\Taxes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Forensic verification of core Taxes engine behavior.
 */
final class TaxEngineForensicTest extends TestCase
{
  private const TAX_YEAR = 2026;

  private function engine(string $province = 'Alberta'): Taxes
  {
    return new Taxes($province, self::TAX_YEAR);
  }

  #[Test]
  public function forensicZeroGrossYieldsZeroAcrossAllComponents(): void
  {
    $result = $this->engine()->calculateTaxesCents(0);
    foreach ($result as $key => $value) {
      $this->assertSame(0, $value, "Expected zero for {$key}");
    }
  }

  #[Test]
  public function forensicIncomeTaxEqualsFederalPlusProvincial(): void
  {
    $result = $this->engine()->calculateTaxesCents(6500000);
    $this->assertSame($result['federal'] + $result['provincial'], $result['incomeTax']);
  }

  #[Test]
  public function forensicTotalDeductionsEqualsSumOfAllComponents(): void
  {
    $result = $this->engine()->calculateTaxesCents(6500000);
    $expected = $result['federal']
      + $result['provincial']
      + $result['employment_insurance']
      + $result['canada_pension_plan']
      + $result['old_age_security'];
    $this->assertSame($expected, $result['totalDeductions']);
  }

  #[Test]
  public function forensicScenarioAMidRangeIncome65kHasNonZeroDeductions(): void
  {
    $result = $this->engine()->calculateTaxesCents(6500000);
    $this->assertGreaterThan(0, $result['totalDeductions']);
    $this->assertGreaterThan(0, $result['federal']);
    $this->assertGreaterThan(0, $result['provincial']);
    $this->assertGreaterThan(0, $result['employment_insurance']);
    $this->assertGreaterThan(0, $result['canada_pension_plan']);
    $this->assertSame(0, $result['old_age_security']);
  }

  #[Test]
  public function forensicScenarioBHighEarner120kTriggersOasClawback(): void
  {
    $result = $this->engine()->calculateTaxesCents(12000000);
    $this->assertGreaterThan(0, $result['old_age_security']);
    $this->assertGreaterThan(3000000, $result['totalDeductions']);
  }

  #[Test]
  public function forensicScenarioCLowIncome25kHasBasicDeductionsOnly(): void
  {
    $result = $this->engine()->calculateTaxesCents(2500000);
    $this->assertGreaterThan(0, $result['totalDeductions']);
    $this->assertSame(0, $result['old_age_security']);
    $this->assertLessThan(800000, $result['totalDeductions']);
  }

  #[Test]
  public function forensicNetLessThanGrossForPositiveIncome(): void
  {
    $grossCents = 5400000;
    $deductions = $this->engine()->calculateTaxesCents($grossCents)['totalDeductions'];
    $netCents = $grossCents - $deductions;
    $this->assertGreaterThan(0, $deductions);
    $this->assertLessThan($grossCents, $netCents);
    $this->assertSame($grossCents, $netCents + $deductions);
  }

  #[Test]
  public function forensicAllResultValuesAreIntegers(): void
  {
    $result = $this->engine()->calculateTaxesCents(8123456);
    foreach ($result as $key => $value) {
      $this->assertIsInt($value, "{$key} must be int cents");
    }
  }

  #[Test]
  public function forensicTaxYearBelow2020ClampsTo2020(): void
  {
    $low = new Taxes('Alberta', 2015);
    $ref = new Taxes('Alberta', 2020);
    $this->assertSame(
      $ref->calculateTaxesCents(5000000),
      $low->calculateTaxesCents(5000000),
    );
  }

  #[Test]
  public function forensicTaxYearAbove2026ClampsTo2026(): void
  {
    $high = new Taxes('Alberta', 2035);
    $ref = new Taxes('Alberta', 2026);
    $this->assertSame(
      $ref->calculateTaxesCents(5000000),
      $high->calculateTaxesCents(5000000),
    );
  }

  #[Test]
  public function forensicFederalTaxMonotonicWithIncome(): void
  {
    $engine = $this->engine();
    $prev = 0;
    foreach ([100000, 500000, 1000000, 5000000, 10000000, 20000000] as $cents) {
      $federal = $engine->calculateTaxesCents($cents)['federal'];
      $this->assertGreaterThanOrEqual($prev, $federal);
      $prev = $federal;
    }
  }

  #[Test]
  public function forensicProvincialTaxMonotonicWithIncome(): void
  {
    $engine = $this->engine();
    $prev = 0;
    foreach ([100000, 500000, 1000000, 5000000, 10000000, 20000000] as $cents) {
      $provincial = $engine->calculateTaxesCents($cents)['provincial'];
      $this->assertGreaterThanOrEqual($prev, $provincial);
      $prev = $provincial;
    }
  }

  #[Test]
  public function forensicTotalDeductionsMonotonicWithIncome(): void
  {
    $engine = $this->engine();
    $prev = 0;
    for ($cents = 0; $cents <= 15000000; $cents += 250000) {
      $total = $engine->calculateTaxesCents($cents)['totalDeductions'];
      $this->assertGreaterThanOrEqual($prev, $total);
      $prev = $total;
    }
  }

  #[Test]
  public function forensicDeterministicAcrossRepeatedCalls(): void
  {
    $engine = $this->engine();
    $first = $engine->calculateTaxesCents(7777700);
    for ($i = 0; $i < 50; ++$i) {
      $this->assertSame($first, $engine->calculateTaxesCents(7777700));
    }
  }

  #[Test]
  public function forensicDollarPathMatchesCentsPath(): void
  {
    $engine = $this->engine();
    $centsResult = $engine->calculateTaxesCents(5000000);
    $dollarResult = $engine->calculateTaxes(50000.0);
    $this->assertEqualsWithDelta($centsResult['totalDeductions'] / 100, $dollarResult['totalDeductions'], 0.01);
    $this->assertEqualsWithDelta($centsResult['federal'] / 100, $dollarResult['federal'], 0.01);
  }

  #[Test]
  public function forensicCppZeroBelowBasicExemption(): void
  {
    $result = $this->engine()->calculateTaxesCents(300000);
    $this->assertSame(0, $result['canada_pension_plan']);
  }

  #[Test]
  public function forensicCppPositiveJustAboveExemption(): void
  {
    $result = $this->engine()->calculateTaxesCents(400000);
    $this->assertGreaterThan(0, $result['canada_pension_plan']);
  }

  #[Test]
  public function forensicEiPositiveForAnyPositiveIncome(): void
  {
    $result = $this->engine()->calculateTaxesCents(100);
    $this->assertGreaterThan(0, $result['employment_insurance']);
  }

  #[Test]
  public function forensicOasZeroAtThreshold(): void
  {
    $result = $this->engine()->calculateTaxesCents(8728200);
    $this->assertSame(0, $result['old_age_security']);
  }

  #[Test]
  public function forensicOasPositiveWellAboveThreshold(): void
  {
    $result = $this->engine()->calculateTaxesCents(9000000);
    $this->assertGreaterThan(0, $result['old_age_security']);
  }

  #[Test]
  public function forensicUnknownProvinceFallsBackToAlbertaBrackets(): void
  {
    $unknown = new Taxes('ZZ', self::TAX_YEAR);
    $alberta = new Taxes('Alberta', self::TAX_YEAR);
    $this->assertSame(
      $alberta->calculateTaxesCents(4500000),
      $unknown->calculateTaxesCents(4500000),
    );
  }

  #[Test]
  public function forensicEmptyProvinceStringUsesAlbertaDefault(): void
  {
    $empty = new Taxes('', self::TAX_YEAR);
    $alberta = new Taxes('Alberta', self::TAX_YEAR);
    $this->assertSame(
      $alberta->calculateProvincialTaxCents(6000000),
      $empty->calculateProvincialTaxCents(6000000),
    );
  }

  #[Test]
  #[DataProvider('forensicGoldenIncomeProvider')]
  public function forensicGoldenTotalDeductionsWithinExpectedBand(int $incomeCents, int $min, int $max): void
  {
    $total = $this->engine()->calculateTaxesCents($incomeCents)['totalDeductions'];
    $this->assertGreaterThanOrEqual($min, $total);
    $this->assertLessThanOrEqual($max, $total);
  }

  public static function forensicGoldenIncomeProvider(): array
  {
    return [
      '20k' => [2000000, 500000, 650000],
      '50k' => [5000000, 1300000, 1600000],
      '75k' => [7500000, 2100000, 2500000],
      '100k' => [10000000, 3000000, 3400000],
      '150k' => [15000000, 5000000, 5800000],
    ];
  }

  #[Test]
  public function forensicFederalBracketJumpIncreasesMarginalBurden(): void
  {
    $engine = $this->engine();
    $low = $engine->calculateTaxesCents(5850000)['federal'];
    $high = $engine->calculateTaxesCents(5860000)['federal'];
    $this->assertGreaterThan($low, $high);
  }

  #[Test]
  public function forensicProvincialBracketJumpIncreasesMarginalBurden(): void
  {
    $engine = $this->engine();
    $low = $engine->calculateProvincialTaxCents(6110000);
    $high = $engine->calculateProvincialTaxCents(6130000);
    $this->assertGreaterThan($low, $high);
  }

  #[Test]
  public function forensicCalculateTaxesCentsStructureHasSevenKeys(): void
  {
    $result = $this->engine()->calculateTaxesCents(100);
    $this->assertCount(7, $result);
    $this->assertArrayHasKey('incomeTax', $result);
    $this->assertArrayHasKey('totalDeductions', $result);
  }

  #[Test]
  public function forensicVeryHighIncomeDoesNotOverflow(): void
  {
    $result = $this->engine()->calculateTaxesCents(500000000);
    $this->assertGreaterThan(0, $result['totalDeductions']);
    $this->assertLessThan(500000000, $result['totalDeductions']);
  }

  #[Test]
  public function forensicOneDollarIncomeProducesMinimalDeductions(): void
  {
    $result = $this->engine()->calculateTaxesCents(100);
    $this->assertGreaterThan(0, $result['totalDeductions']);
    $this->assertLessThan(100, $result['totalDeductions']);
  }

  #[Test]
  public function forensicIncomeTaxNeverExceedsGross(): void
  {
    foreach ([100000, 2500000, 8000000, 25000000] as $cents) {
      $result = $this->engine()->calculateTaxesCents($cents);
      $this->assertLessThanOrEqual($cents, $result['incomeTax']);
      $this->assertLessThanOrEqual($cents, $result['totalDeductions']);
    }
  }

  #[Test]
  public function forensicFederalBracketsReturnCollectionWithFiveRows(): void
  {
    $brackets = $this->engine()->getDefaultFederalBrackets(self::TAX_YEAR);
    $this->assertCount(5, $brackets);
  }

  #[Test]
  public function forensicAlbertaProvincialBracketsReturnSixRows(): void
  {
    $brackets = $this->engine()->getDefaultProvincialBrackets('Alberta', self::TAX_YEAR);
    $this->assertCount(6, $brackets);
  }
}
