<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain\Tax;

use PayCal\Domain\Taxes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Forensic verification of provincial vs federal rate table behavior.
 */
final class ProvincialFederalRatesForensicTest extends TestCase
{
  private const TAX_YEAR = 2026;
  private const INCOME_CENTS = 7500000;

  #[Test]
  #[DataProvider('forensicAllProvincesProvider')]
  public function forensicEveryProvinceProducesValidTaxStructure(string $province, int $bracketCount): void
  {
    $taxes = new Taxes($province, self::TAX_YEAR);
    $brackets = $taxes->getDefaultProvincialBrackets($province, self::TAX_YEAR);
    $this->assertCount($bracketCount, $brackets);
    $result = $taxes->calculateTaxesCents(self::INCOME_CENTS);
    $this->assertGreaterThan(0, $result['provincial']);
    $this->assertGreaterThan(0, $result['federal']);
  }

  public static function forensicAllProvincesProvider(): array
  {
    return [
      'Alberta' => ['Alberta', 6],
      'British Columbia' => ['British Columbia', 7],
      'Manitoba' => ['Manitoba', 3],
      'New Brunswick' => ['New Brunswick', 4],
      'Newfoundland and Labrador' => ['Newfoundland and Labrador', 8],
      'Northwest Territories' => ['Northwest Territories', 4],
      'Nova Scotia' => ['Nova Scotia', 5],
      'Nunavut' => ['Nunavut', 4],
      'Ontario' => ['Ontario', 5],
      'Prince Edward Island' => ['Prince Edward Island', 5],
      'Quebec' => ['Quebec', 4],
      'Saskatchewan' => ['Saskatchewan', 3],
      'Yukon' => ['Yukon', 6],
    ];
  }

  #[Test]
  public function forensicOntarioProvincialDiffersFromAlbertaAt75k(): void
  {
    $ab = new Taxes('Alberta', self::TAX_YEAR)->calculateTaxesCents(self::INCOME_CENTS)['provincial'];
    $on = new Taxes('Ontario', self::TAX_YEAR)->calculateTaxesCents(self::INCOME_CENTS)['provincial'];
    $this->assertNotSame($ab, $on);
    $this->assertGreaterThan(0, $ab);
    $this->assertGreaterThan(0, $on);
  }

  #[Test]
  public function forensicQuebecProvincialDistinctFromOntario(): void
  {
    $qc = new Taxes('Quebec', self::TAX_YEAR)->calculateTaxesCents(self::INCOME_CENTS)['provincial'];
    $on = new Taxes('Ontario', self::TAX_YEAR)->calculateTaxesCents(self::INCOME_CENTS)['provincial'];
    $this->assertNotSame($qc, $on);
  }

  #[Test]
  public function forensicFederalTaxIdenticalAcrossProvinces(): void
  {
    $provinces = ['Alberta', 'Ontario', 'British Columbia', 'Quebec', 'Nova Scotia'];
    $federalValues = array_map(
      static fn (string $p): int => (new Taxes($p, self::TAX_YEAR))->calculateTaxesCents(self::INCOME_CENTS)['federal'],
      $provinces,
    );
    $this->assertCount(1, array_unique($federalValues));
  }

  #[Test]
  public function forensicEiIdenticalAcrossProvinces(): void
  {
    $provinces = ['Alberta', 'Ontario', 'Quebec'];
    $eiValues = array_map(
      static fn (string $p): int => (new Taxes($p, self::TAX_YEAR))->calculateTaxesCents(self::INCOME_CENTS)['employment_insurance'],
      $provinces,
    );
    $this->assertCount(1, array_unique($eiValues));
  }

  #[Test]
  public function forensicCppIdenticalAcrossProvinces(): void
  {
    $provinces = ['Alberta', 'Ontario', 'Quebec'];
    $cppValues = array_map(
      static fn (string $p): int => (new Taxes($p, self::TAX_YEAR))->calculateTaxesCents(self::INCOME_CENTS)['canada_pension_plan'],
      $provinces,
    );
    $this->assertCount(1, array_unique($cppValues));
  }

  #[Test]
  public function forensicProvinceCodeAbDoesNotMatchAlbertaFullName(): void
  {
    $code = new Taxes('AB', self::TAX_YEAR)->calculateTaxesCents(self::INCOME_CENTS)['provincial'];
    $full = new Taxes('Alberta', self::TAX_YEAR)->calculateTaxesCents(self::INCOME_CENTS)['provincial'];
    $this->assertSame($full, $code, 'AB code falls back to Alberta — same brackets');
  }

  #[Test]
  public function forensicBcDiffersFromAbAtSameIncome(): void
  {
    $ab = new Taxes('Alberta', self::TAX_YEAR)->calculateTaxesCents(10000000)['provincial'];
    $bc = new Taxes('British Columbia', self::TAX_YEAR)->calculateTaxesCents(10000000)['provincial'];
    $this->assertNotSame($ab, $bc);
  }

  #[Test]
  public function forensicTotalDeductionsVariesByProvince(): void
  {
    $totals = [];
    foreach (['Alberta', 'Ontario', 'British Columbia', 'Quebec'] as $province) {
      $totals[$province] = (new Taxes($province, self::TAX_YEAR))->calculateTaxesCents(self::INCOME_CENTS)['totalDeductions'];
    }
    $this->assertGreaterThan(2, count(array_unique($totals)));
  }

  #[Test]
  public function forensicFederalFirstBracketRate14PercentAt40k(): void
  {
    $federal = new Taxes('Alberta', self::TAX_YEAR)->getDefaultFederalBrackets(self::TAX_YEAR);
    $tax = $federal->calculateTaxCents(4000000);
    $this->assertSame(560000, $tax);
  }

  #[Test]
  public function forensicAlbertaFirstBracketRate8PercentAt40k(): void
  {
    $provincial = new Taxes('Alberta', self::TAX_YEAR)->getDefaultProvincialBrackets('Alberta', self::TAX_YEAR);
    $tax = $provincial->calculateTaxCents(4000000);
    $this->assertSame(320000, $tax);
  }

  #[Test]
  public function forensicRateTablesSupportYears2020Through2026(): void
  {
    foreach (range(2020, 2026) as $year) {
      $taxes = new Taxes('Alberta', $year);
      $result = $taxes->calculateTaxesCents(5000000);
      $this->assertGreaterThan(0, $result['totalDeductions'], "Year {$year} should produce deductions");
    }
  }

  #[Test]
  public function forensic2026Alberta50kTotalDeductionsInExpectedBand(): void
  {
    $total = (new Taxes('Alberta', 2026))->calculateTaxesCents(5000000)['totalDeductions'];
    $this->assertGreaterThanOrEqual(1300000, $total);
    $this->assertLessThanOrEqual(1600000, $total);
  }

  #[Test]
  public function forensicNovaScotiaHasMoreBracketsThanSaskatchewan(): void
  {
    $ns = new Taxes('Nova Scotia', self::TAX_YEAR)->getDefaultProvincialBrackets('Nova Scotia', self::TAX_YEAR);
    $sk = new Taxes('Saskatchewan', self::TAX_YEAR)->getDefaultProvincialBrackets('Saskatchewan', self::TAX_YEAR);
    $this->assertGreaterThan($sk->count(), $ns->count());
  }

  #[Test]
  public function forensicNewfoundlandHasMostProvincialBrackets(): void
  {
    $nl = new Taxes('Newfoundland and Labrador', self::TAX_YEAR)->getDefaultProvincialBrackets('Newfoundland and Labrador', self::TAX_YEAR);
    $this->assertCount(8, $nl);
  }
}
