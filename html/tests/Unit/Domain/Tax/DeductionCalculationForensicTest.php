<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain\Tax;

use PayCal\Domain\CanadaPensionPlanCalculator;
use PayCal\Domain\EmploymentInsuranceCalculator;
use PayCal\Domain\OldAgeSecurityCalculator;
use PayCal\Domain\Taxes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Forensic verification of individual deduction calculators (EI, CPP, OAS).
 */
final class DeductionCalculationForensicTest extends TestCase
{
  private const TAX_YEAR = 2026;

  #[Test]
  public function forensicCppCalculatorImplementsTaxCalculatorInterface(): void
  {
    $calc = new CanadaPensionPlanCalculator();
    $this->assertInstanceOf(\PayCal\Domain\TaxCalculatorInterface::class, $calc);
  }

  #[Test]
  public function forensicEiCalculatorImplementsTaxCalculatorInterface(): void
  {
    $calc = new EmploymentInsuranceCalculator();
    $this->assertInstanceOf(\PayCal\Domain\TaxCalculatorInterface::class, $calc);
  }

  #[Test]
  public function forensicOasCalculatorImplementsTaxCalculatorInterface(): void
  {
    $calc = new OldAgeSecurityCalculator();
    $this->assertInstanceOf(\PayCal\Domain\TaxCalculatorInterface::class, $calc);
  }

  #[Test]
  public function forensicCppAt3500ExemptionBoundaryIsZero(): void
  {
    $cpp = new CanadaPensionPlanCalculator();
    $this->assertSame(0, $cpp->calculateCents(350000));
  }

  #[Test]
  public function forensicCppOneDollarAboveExemptionIsPositive(): void
  {
    $cpp = new CanadaPensionPlanCalculator();
    $this->assertGreaterThan(0, $cpp->calculateCents(360000));
  }

  #[Test]
  public function forensicCppAt68500YmppMaxIsCapped(): void
  {
    $cpp = new CanadaPensionPlanCalculator();
    $atMax = $cpp->calculateCents(6850000);
    $aboveMax = $cpp->calculateCents(10000000);
    $this->assertSame($atMax, $aboveMax);
  }

  #[Test]
  public function forensicCpp50kMatchesExpectedFormula(): void
  {
    $cpp = new CanadaPensionPlanCalculator();
    $expected = (int) round((5000000 - 350000) * 595 / 10000, 0, PHP_ROUND_HALF_UP);
    $this->assertSame($expected, $cpp->calculateCents(5000000));
  }

  #[Test]
  public function forensicCppMonotonicUntilCap(): void
  {
    $cpp = new CanadaPensionPlanCalculator();
    $prev = 0;
    for ($cents = 0; $cents <= 8000000; $cents += 100000) {
      $value = $cpp->calculateCents($cents);
      $this->assertGreaterThanOrEqual($prev, $value);
      $prev = $value;
    }
  }

  #[Test]
  public function forensicEiRateIs158BasisPoints(): void
  {
    $ei = new EmploymentInsuranceCalculator();
    $this->assertSame(158, $ei->calculateCents(10000));
  }

  #[Test]
  public function forensicEiCapsAt63200Insurable(): void
  {
    $ei = new EmploymentInsuranceCalculator();
    $atCap = $ei->calculateCents(6320000);
    $aboveCap = $ei->calculateCents(9000000);
    $this->assertSame($atCap, $aboveCap);
    $this->assertSame(99856, $atCap);
  }

  #[Test]
  public function forensicEiZeroIncomeIsZero(): void
  {
    $ei = new EmploymentInsuranceCalculator();
    $this->assertSame(0, $ei->calculateCents(0));
  }

  #[Test]
  public function forensicEiUsesHalfUpRounding(): void
  {
    $ei = new EmploymentInsuranceCalculator();
    $incomeCents = 632900;
    $expected = (int) round($incomeCents * 158 / 10000, 0, PHP_ROUND_HALF_UP);
    $this->assertSame($expected, $ei->calculateCents($incomeCents));
    $this->assertSame(10000, $expected);
  }

  #[Test]
  public function forensicOasBelow87282ThresholdIsZero(): void
  {
    $oas = new OldAgeSecurityCalculator();
    $this->assertSame(0, $oas->calculateCents(8728199));
  }

  #[Test]
  public function forensicOasAtThresholdIsZero(): void
  {
    $oas = new OldAgeSecurityCalculator();
    $this->assertSame(0, $oas->calculateCents(8728200));
  }

  #[Test]
  public function forensicOas15PercentOnExcess(): void
  {
    $oas = new OldAgeSecurityCalculator();
    $income = 9000000;
    $expected = (int) round(($income - 8728200) * 1500 / 10000, 0, PHP_ROUND_HALF_UP);
    $this->assertSame($expected, $oas->calculateCents($income));
  }

  #[Test]
  public function forensicOasMonotonicAboveThreshold(): void
  {
    $oas = new OldAgeSecurityCalculator();
    $prev = 0;
    for ($cents = 8728200; $cents <= 20000000; $cents += 500000) {
      $value = $oas->calculateCents($cents);
      $this->assertGreaterThanOrEqual($prev, $value);
      $prev = $value;
    }
  }

  #[Test]
  public function forensicTaxesCppComponentMatchesStandaloneCalculator(): void
  {
    $taxes = new Taxes('Alberta', self::TAX_YEAR);
    $cpp = new CanadaPensionPlanCalculator();
    $income = 4200000;
    $this->assertSame($cpp->calculateCents($income), $taxes->calculateTaxesCents($income)['canada_pension_plan']);
  }

  #[Test]
  public function forensicTaxesEiComponentMatchesStandaloneCalculator(): void
  {
    $taxes = new Taxes('Alberta', self::TAX_YEAR);
    $ei = new EmploymentInsuranceCalculator();
    $income = 4200000;
    $this->assertSame($ei->calculateCents($income), $taxes->calculateTaxesCents($income)['employment_insurance']);
  }

  #[Test]
  public function forensicTaxesOasComponentMatchesStandaloneCalculator(): void
  {
    $taxes = new Taxes('Alberta', self::TAX_YEAR);
    $oas = new OldAgeSecurityCalculator();
    $income = 13000000;
    $this->assertSame($oas->calculateCents($income), $taxes->calculateTaxesCents($income)['old_age_security']);
  }

  #[Test]
  #[DataProvider('forensicCppIncomeProvider')]
  public function forensicCppScalesLinearlyBelowCap(int $incomeCents, int $expectedCpp): void
  {
    $cpp = new CanadaPensionPlanCalculator();
    $this->assertSame($expectedCpp, $cpp->calculateCents($incomeCents));
  }

  public static function forensicCppIncomeProvider(): array
  {
    return [
      'below exemption' => [200000, 0],
      '5k income' => [500000, 8925],
      '35k income' => [3500000, 187425],
      '50k income' => [5000000, 276675],
    ];
  }

  #[Test]
  public function forensicLowIncomeIncludesNonZeroEiAndCpp(): void
  {
    $result = (new Taxes('Alberta', self::TAX_YEAR))->calculateTaxesCents(500000);
    $this->assertGreaterThan(0, $result['employment_insurance']);
    $this->assertGreaterThan(0, $result['canada_pension_plan']);
    $this->assertGreaterThan(0, $result['incomeTax']);
  }

  #[Test]
  public function forensicHighIncomeDeductionsDominatedByIncomeTax(): void
  {
    $result = (new Taxes('Alberta', self::TAX_YEAR))->calculateTaxesCents(18000000);
    $this->assertGreaterThan(
      $result['employment_insurance'] + $result['canada_pension_plan'],
      $result['incomeTax'],
    );
  }
}
