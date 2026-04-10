<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Taxes.php
 *
 * Purpose: Define the TaxCalculatorInterface interface for PayCal\Domain.
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

interface TaxCalculatorInterface
{
  /**
   * Handles calculateCents operation.
   */
  public function calculateCents(int $amountCents): int;
}

/**
 * BracketedTaxCalculator
 */
class BracketedTaxCalculator implements TaxCalculatorInterface
{
  private TaxBracketCollection $brackets;

  /**
   * @param TaxBracketCollection|array<int, array{0:int,1:int,2:int}> $brackets
   */
  public function __construct(array|TaxBracketCollection $brackets)
  {
    if (is_array($brackets)) {
      $this->brackets = TaxBracketCollection::fromCentsArrays($brackets);
    } else {
      $this->brackets = $brackets;
    }
  }

  /**
   * Handles calculateCents operation.
   */
  public function calculateCents(int $incomeCents): int
  {
    return $this->brackets->calculateTaxCents($incomeCents);
  }
}

/**
 * EmploymentInsuranceCalculator
 */
class EmploymentInsuranceCalculator implements TaxCalculatorInterface
{
  private const MAX_INSURABLE_EARNINGS_CENTS = 6320000;
  private const RATE_BASIS_POINTS = 158;

  /**
   * Handles calculateCents operation.
   */
  public function calculateCents(int $incomeCents): int
  {
    $capped = min($incomeCents, self::MAX_INSURABLE_EARNINGS_CENTS);
    $tax = ($capped * self::RATE_BASIS_POINTS) / 10000;

    return (int) round($tax, 0, PHP_ROUND_HALF_UP);
  }
}

/**
 * OldAgeSecurityCalculator
 */
class OldAgeSecurityCalculator implements TaxCalculatorInterface
{
  private const THRESHOLD_CENTS = 8728200;
  private const RATE_BASIS_POINTS = 1500;

  /**
   * Handles calculateCents operation.
   */
  public function calculateCents(int $incomeCents): int
  {
    if ($incomeCents <= self::THRESHOLD_CENTS) {
      return 0;
    }

    $excess = $incomeCents - self::THRESHOLD_CENTS;
    $tax = ($excess * self::RATE_BASIS_POINTS) / 10000;

    return (int) round($tax, 0, PHP_ROUND_HALF_UP);
  }
}

/**
 * CanadaPensionPlanCalculator
 */
class CanadaPensionPlanCalculator implements TaxCalculatorInterface
{
  private const BASIC_EXEMPTION_CENTS = 350000;
  private const MAX_PENSIONABLE_CENTS = 6850000;
  private const RATE_BASIS_POINTS = 595;

  /**
   * Handles calculateCents operation.
   */
  public function calculateCents(int $incomeCents): int
  {
    $adjusted = max(0, $incomeCents - self::BASIC_EXEMPTION_CENTS);
    $cap = self::MAX_PENSIONABLE_CENTS - self::BASIC_EXEMPTION_CENTS;
    $capped = min($adjusted, $cap);

    $tax = ($capped * self::RATE_BASIS_POINTS) / 10000;

    return (int) round($tax, 0, PHP_ROUND_HALF_UP);
  }
}

/**
 * Taxes
 */
class Taxes
{
  private const MIN_SUPPORTED_TAX_YEAR = 2020;
  private const MAX_SUPPORTED_TAX_YEAR = 2026;

  /** @var null|array{federal:array<int, list<array<int,int>>>, provincial:array<string, array<int, list<array<int,int>>>>} */
  private static ?array $rateTables = null;

  private TaxCalculatorInterface $federal;
  private TaxCalculatorInterface $provincial;
  private TaxCalculatorInterface $ei;
  private TaxCalculatorInterface $cpp;
  private TaxCalculatorInterface $oas;

  /**
   * Initializes a new instance.
   */
  public function __construct(string $province = "Alberta", ?int $taxYear = null)
  {
    $resolvedYear = self::normalizeTaxYear($taxYear ?? (int) date('Y'));

    $this->federal = new BracketedTaxCalculator(
      $this->getDefaultFederalBrackets($resolvedYear)
    );

    $this->provincial = new BracketedTaxCalculator(
      $this->getDefaultProvincialBrackets($province, $resolvedYear)
    );

    $this->ei = new EmploymentInsuranceCalculator();
    $this->cpp = new CanadaPensionPlanCalculator();
    $this->oas = new OldAgeSecurityCalculator();
  }

  /**
   * Handles getDefaultFederalBrackets operation.
   */
  public function getDefaultFederalBrackets(?int $taxYear = null): TaxBracketCollection
  {
    $year = self::normalizeTaxYear($taxYear ?? (int) date('Y'));

    /** @var array<int, array{0:int,1:int,2:int}> $federal */
    $federal = self::loadRateTables()['federal'][$year];

    return TaxBracketCollection::fromCentsArrays($federal);
  }

  /**
   * Handles getDefaultProvincialBrackets operation.
   */
  public function getDefaultProvincialBrackets(string $province, ?int $taxYear = null): TaxBracketCollection
  {
    $year = self::normalizeTaxYear($taxYear ?? (int) date('Y'));
    $provincialTables = self::loadRateTables()['provincial'];
    $provinceName = isset($provincialTables[$province]) ? $province : 'Alberta';

    /** @var array<int, array{0:int,1:int,2:int}> $brackets */
    $brackets = $provincialTables[$provinceName][$year];

    return TaxBracketCollection::fromCentsArrays($brackets);
  }

  /** @return array{federal:array<int, list<array<int,int>>>, provincial:array<string, array<int, list<array<int,int>>>>} */
  private static function loadRateTables(): array
  {
    if (self::$rateTables === null) {
      $raw = file_get_contents(__DIR__ . '/TaxRateTablesData.json');
      if (!is_string($raw) || $raw === '') {
        throw new \RuntimeException('Unable to load tax table dataset.');
      }

      $decoded = json_decode($raw, true);
      if (!is_array($decoded)) {
        throw new \RuntimeException('Invalid tax table dataset format.');
      }

      /** @var array<string, mixed> $decoded */

      self::$rateTables = self::normalizeRateTables($decoded);
    }

    return self::$rateTables;
  }

  /**
   * @param array<string, mixed> $decoded
    * @return array{federal:array<int, list<array<int,int>>>, provincial:array<string, array<int, list<array<int,int>>>>}
   */
  private static function normalizeRateTables(array $decoded): array
  {
    $federal = [];
    $provincial = [];

    $federalRaw = $decoded['federal'] ?? [];
    if (is_array($federalRaw)) {
      foreach ($federalRaw as $yearKey => $bracketsRaw) {
        if (!is_array($bracketsRaw)) {
          continue;
        }
        $federal[(int) $yearKey] = self::normalizeBrackets($bracketsRaw);
      }
    }

    $provincialRaw = $decoded['provincial'] ?? [];
    if (is_array($provincialRaw)) {
      foreach ($provincialRaw as $province => $byYearRaw) {
        if (!is_string($province) || !is_array($byYearRaw)) {
          continue;
        }
        $provincial[$province] = [];
        foreach ($byYearRaw as $yearKey => $bracketsRaw) {
          if (!is_array($bracketsRaw)) {
            continue;
          }
          $provincial[$province][(int) $yearKey] = self::normalizeBrackets($bracketsRaw);
        }
      }
    }

    return [
      'federal' => $federal,
      'provincial' => $provincial,
    ];
  }

  /**
   * @param array<mixed> $bracketsRaw
    * @return list<array<int,int>>
   */
  private static function normalizeBrackets(array $bracketsRaw): array
  {
    $normalized = [];
    foreach ($bracketsRaw as $row) {
      if (!is_array($row) || !isset($row[0], $row[1], $row[2])) {
        continue;
      }

      $maxValue = $row[1];
      if ($maxValue === 'PHP_INT_MAX') {
        $maxValue = PHP_INT_MAX;
      }

      if (!is_numeric($row[0]) || !is_numeric($maxValue) || !is_numeric($row[2])) {
        continue;
      }

      $normalized[] = [(int) $row[0], (int) $maxValue, (int) $row[2]];
    }

    return $normalized;
  }

  /**
   * Handles normalizeTaxYear operation.
   */
  private static function normalizeTaxYear(int $taxYear): int
  {
    if ($taxYear < self::MIN_SUPPORTED_TAX_YEAR) {
      return self::MIN_SUPPORTED_TAX_YEAR;
    }
    if ($taxYear > self::MAX_SUPPORTED_TAX_YEAR) {
      return self::MAX_SUPPORTED_TAX_YEAR;
    }

    return $taxYear;
  }

  /**
   * Calculate all taxes on the given provincial income (in cents).
   *
   * @param int $incomeCents Income amount in cents
   * @return int Provincial income tax in cents
   */
  public function calculateProvincialTaxCents(int $incomeCents): int
  {
    return $this->provincial->calculateCents($incomeCents);
  }

  /**
   * @return array{
   *   federal:int,
   *   provincial:int,
   *   employment_insurance:int,
   *   canada_pension_plan:int,
   *   old_age_security:int,
   *   incomeTax:int,
   *   totalDeductions:int
   * }
   */
  public function calculateTaxesCents(int $incomeCents): array
  {
    $federal = $this->federal->calculateCents($incomeCents);
    $provincial = $this->provincial->calculateCents($incomeCents);
    $ei = $this->ei->calculateCents($incomeCents);
    $cpp = $this->cpp->calculateCents($incomeCents);
    $oas = $this->oas->calculateCents($incomeCents);

    $incomeTax = $federal + $provincial;
    $total = $incomeTax + $ei + $cpp + $oas;

    return [
      "federal" => $federal,
      "provincial" => $provincial,
      "employment_insurance" => $ei,
      "canada_pension_plan" => $cpp,
      "old_age_security" => $oas,
      "incomeTax" => $incomeTax,
      "totalDeductions" => $total,
    ];
  }

  /**
   * Calculate all taxes on the given income (in dollars).
   *
   * @param float $income Income amount in dollars
   * @return array{
   *   federal:float,
   *   provincial:float,
   *   employment_insurance:float,
   *   canada_pension_plan:float,
   *   old_age_security:float,
   *   incomeTax:float,
   *   totalDeductions:float
   * }
   */
  public function calculateTaxes(float $income): array
  {
    $incomeCents = (int) round($income * 100);
    $result = $this->calculateTaxesCents($incomeCents);

    return [
      "federal" => $result["federal"] / 100.0,
      "provincial" => $result["provincial"] / 100.0,
      "employment_insurance" => $result["employment_insurance"] / 100.0,
      "canada_pension_plan" => $result["canada_pension_plan"] / 100.0,
      "old_age_security" => $result["old_age_security"] / 100.0,
      "incomeTax" => $result["incomeTax"] / 100.0,
      "totalDeductions" => $result["totalDeductions"] / 100.0,
    ];
  }
}

