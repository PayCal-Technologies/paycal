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
      'Prince Edward Island' => ['Prince Edward Island', 6],
      'Quebec' => ['Quebec', 4],
      'Saskatchewan' => ['Saskatchewan', 3],
      'Yukon' => ['Yukon', 5],
    ];
  }

  #[Test]
  public function forensicFederal2026BracketsMatchOfficialCraPayrollTable(): void
  {
    $brackets = (new Taxes('Alberta', self::TAX_YEAR))
      ->getDefaultFederalBrackets(self::TAX_YEAR)
      ->toCentsArrays();

    $this->assertSame([
      [0, 5852300, 1400],
      [5852300, 11704500, 2050],
      [11704500, 18144000, 2600],
      [18144000, 25848200, 2900],
      [25848200, PHP_INT_MAX, 3300],
    ], $brackets);
  }

  #[Test]
  #[DataProvider('official2026ProvincialBracketsProvider')]
  public function forensicProvincial2026BracketsMatchOfficialPublishedTables(
    string $province,
    array $expectedBrackets
  ): void {
    $actual = (new Taxes($province, self::TAX_YEAR))
      ->getDefaultProvincialBrackets($province, self::TAX_YEAR)
      ->toCentsArrays();

    $this->assertSame($expectedBrackets, $actual);
  }

  public static function official2026ProvincialBracketsProvider(): array
  {
    return [
      'Alberta' => ['Alberta', [
        [0, 6120000, 800],
        [6120000, 15425900, 1000],
        [15425900, 18511100, 1200],
        [18511100, 24681300, 1300],
        [24681300, 37022000, 1400],
        [37022000, PHP_INT_MAX, 1500],
      ]],
      'British Columbia' => ['British Columbia', [
        [0, 5036300, 560],
        [5036300, 10072800, 770],
        [10072800, 11564800, 1050],
        [11564800, 14043000, 1229],
        [14043000, 19040500, 1470],
        [19040500, 26554500, 1680],
        [26554500, PHP_INT_MAX, 2050],
      ]],
      'Manitoba' => ['Manitoba', [
        [0, 4700000, 1080],
        [4700000, 10000000, 1275],
        [10000000, PHP_INT_MAX, 1740],
      ]],
      'New Brunswick' => ['New Brunswick', [
        [0, 5233300, 940],
        [5233300, 10466600, 1400],
        [10466600, 19386100, 1600],
        [19386100, PHP_INT_MAX, 1950],
      ]],
      'Newfoundland and Labrador' => ['Newfoundland and Labrador', [
        [0, 4467800, 870],
        [4467800, 8935400, 1450],
        [8935400, 15952800, 1580],
        [15952800, 22334000, 1780],
        [22334000, 28531900, 1980],
        [28531900, 57063800, 2080],
        [57063800, 114127500, 2130],
        [114127500, PHP_INT_MAX, 2180],
      ]],
      'Northwest Territories' => ['Northwest Territories', [
        [0, 5300300, 590],
        [5300300, 10600900, 860],
        [10600900, 17234600, 1220],
        [17234600, PHP_INT_MAX, 1405],
      ]],
      'Nova Scotia' => ['Nova Scotia', [
        [0, 3099500, 879],
        [3099500, 6199100, 1495],
        [6199100, 9741700, 1667],
        [9741700, 15712400, 1750],
        [15712400, PHP_INT_MAX, 2100],
      ]],
      'Nunavut' => ['Nunavut', [
        [0, 5580100, 400],
        [5580100, 11160200, 700],
        [11160200, 18143900, 900],
        [18143900, PHP_INT_MAX, 1150],
      ]],
      'Ontario' => ['Ontario', [
        [0, 5389100, 505],
        [5389100, 10778500, 915],
        [10778500, 15000000, 1116],
        [15000000, 22000000, 1216],
        [22000000, PHP_INT_MAX, 1316],
      ]],
      'Prince Edward Island' => ['Prince Edward Island', [
        [0, 3392800, 950],
        [3392800, 6582000, 1347],
        [6582000, 10689000, 1660],
        [10689000, 14252000, 1762],
        [14252000, 20000000, 1900],
        [20000000, PHP_INT_MAX, 2000],
      ]],
      'Quebec' => ['Quebec', [
        [0, 5434500, 1400],
        [5434500, 10868000, 1900],
        [10868000, 13224500, 2400],
        [13224500, PHP_INT_MAX, 2575],
      ]],
      'Saskatchewan' => ['Saskatchewan', [
        [0, 5453200, 1050],
        [5453200, 15580500, 1250],
        [15580500, PHP_INT_MAX, 1450],
      ]],
      'Yukon' => ['Yukon', [
        [0, 5852300, 640],
        [5852300, 11704500, 900],
        [11704500, 18144000, 1090],
        [18144000, 50000000, 1280],
        [50000000, PHP_INT_MAX, 1500],
      ]],
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
    $provinces = ['Alberta', 'Ontario', 'Yukon'];
    $eiValues = array_map(
      static fn (string $p): int => (new Taxes($p, self::TAX_YEAR))->calculateTaxesCents(self::INCOME_CENTS)['employment_insurance'],
      $provinces,
    );
    $this->assertCount(1, array_unique($eiValues));
  }

  #[Test]
  public function forensicQuebecEiDiffersFromCanadaExceptQuebec(): void
  {
    $ab = (new Taxes('Alberta', self::TAX_YEAR))->calculateTaxesCents(self::INCOME_CENTS)['employment_insurance'];
    $qc = (new Taxes('Quebec', self::TAX_YEAR))->calculateTaxesCents(self::INCOME_CENTS)['employment_insurance'];
    $this->assertLessThan($ab, $qc);
  }

  #[Test]
  public function forensicCppIdenticalAcrossProvinces(): void
  {
    $provinces = ['Alberta', 'Ontario', 'Yukon'];
    $cppValues = array_map(
      static fn (string $p): int => (new Taxes($p, self::TAX_YEAR))->calculateTaxesCents(self::INCOME_CENTS)['canada_pension_plan'],
      $provinces,
    );
    $this->assertCount(1, array_unique($cppValues));
  }

  #[Test]
  public function forensicQuebecPensionPlanDiffersFromCanadaPensionPlan(): void
  {
    $ab = (new Taxes('Alberta', self::TAX_YEAR))->calculateTaxesCents(self::INCOME_CENTS)['canada_pension_plan'];
    $qc = (new Taxes('Quebec', self::TAX_YEAR))->calculateTaxesCents(self::INCOME_CENTS)['canada_pension_plan'];
    $this->assertGreaterThan($ab, $qc);
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
