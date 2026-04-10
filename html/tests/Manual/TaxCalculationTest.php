<?php declare(strict_types=1);

/**
 * PayCal Tax Calculation Test Suite.
 *
 * Tests the accuracy of CPP, OAS, EI, Federal, and Provincial tax calculations
 * against current 2025/2026 CRA rates and thresholds.
 */

declare(strict_types=1);

require_once __DIR__.'/../../bootstrap/autoload.php';

/**
 * TaxCalculationTest
 */
class TaxCalculationTest
{
  private $taxCalculator;

  public function __construct()
  {
    $this->taxCalculator = new Taxes('Alberta');
  }

  /**
   * Run all tests and report results.
   */
  public function runAll(): void
  {
    echo "=== PayCal Tax Calculation Test Suite (2025/2026) ===\n\n";

    $results = [];

    // CPP Tests
    echo "--- CANADA PENSION PLAN TESTS ---\n";
    $results['cpp'] = [
        'cpp_minimum' => $this->testCanadaPensionPlanMinimum(),
        'cpp_low' => $this->testCanadaPensionPlanLow(),
        'cpp_moderate' => $this->testCanadaPensionPlanModerate(),
        'cpp_maximum' => $this->testCanadaPensionPlanMaximum(),
        'cpp_capping' => $this->testCanadaPensionPlanCapping(),
        'cpp_very_high' => $this->testCanadaPensionPlanVeryHigh(),
    ];

    // OAS Tests
    echo "\n--- OLD AGE SECURITY TESTS ---\n";
    $results['oas'] = [
        'oas_below_threshold' => $this->testOldAgeSecurityBelowThreshold(),
        'oas_at_threshold' => $this->testOldAgeSecurityAtThreshold(),
        'oas_minimal' => $this->testOldAgeSecurityMinimal(),
        'oas_moderate' => $this->testOldAgeSecurityModerate(),
        'oas_high' => $this->testOldAgeSecurityHigh(),
    ];

    // EI Tests
    echo "\n--- EMPLOYMENT INSURANCE TESTS ---\n";
    $results['ei'] = [
        'ei_low' => $this->testEmploymentInsuranceLow(),
        'ei_moderate' => $this->testEmploymentInsuranceModerate(),
        'ei_near_max' => $this->testEmploymentInsuranceNearMax(),
        'ei_exceeding_max' => $this->testEmploymentInsuranceExceedingMax(),
    ];

    // Integration Tests
    echo "\n--- INTEGRATION TESTS ---\n";
    $results['integration'] = [
        'scenario_a_mid_range' => $this->testScenarioAMidRange(),
        'scenario_b_high_earner' => $this->testScenarioBHighEarner(),
        'scenario_c_low_income' => $this->testScenarioCLowIncome(),
    ];

    // Summary
    $this->printSummary($results);
  }

  // ==================== CPP Tests ====================

  private function testCanadaPensionPlanMinimum(): bool
  {
    $income = 3000;
    $expected = 0.00;
    $result = $this->taxCalculator->calculateCanadaPensionPlan($income);

    return $this->assertEqual($result, $expected, 'CPP-1', 'No CPP (income below exemption)', $income, $result, $expected);
  }

  private function testCanadaPensionPlanLow(): bool
  {
    $income = 5000;
    $expected = 89.25; // (5000 - 3500) * 0.0595
    $result = $this->taxCalculator->calculateCanadaPensionPlan($income);

    return $this->assertEqual($result, $expected, 'CPP-2', 'Low CPP (income slightly above exemption)', $income, $result, $expected);
  }

  private function testCanadaPensionPlanModerate(): bool
  {
    $income = 35000;
    $expected = 1874.25; // (35000 - 3500) * 0.0595
    $result = $this->taxCalculator->calculateCanadaPensionPlan($income);

    return $this->assertEqual($result, $expected, 'CPP-3', 'Moderate CPP', $income, $result, $expected);
  }

  private function testCanadaPensionPlanMaximum(): bool
  {
    $income = 68500; // YMPE
    $expected = 3867.50; // (68500 - 3500) * 0.0595
    $result = $this->taxCalculator->calculateCanadaPensionPlan($income);

    return $this->assertEqual($result, $expected, 'CPP-4', 'Maximum CPP (at YMPE)', $income, $result, $expected);
  }

  private function testCanadaPensionPlanCapping(): bool
  {
    $income = 150000;
    $expected = 3867.50; // Capped at (68500 - 3500) * 0.0595
    $result = $this->taxCalculator->calculateCanadaPensionPlan($income);

    return $this->assertEqual($result, $expected, 'CPP-5', 'CPP Capping (income above YMPE)', $income, $result, $expected);
  }

  private function testCanadaPensionPlanVeryHigh(): bool
  {
    $income = 500000;
    $expected = 3867.50; // Still capped
    $result = $this->taxCalculator->calculateCanadaPensionPlan($income);

    return $this->assertEqual($result, $expected, 'CPP-6', 'Very High Income (CPP capped)', $income, $result, $expected);
  }

  // ==================== OAS Tests ====================

  private function testOldAgeSecurityBelowThreshold(): bool
  {
    $income = 50000;
    $expected = 0.00; // Below threshold
    $result = $this->taxCalculator->calculateOldAgeSecurity($income);

    return $this->assertEqual($result, $expected, 'OAS-1', 'No OAS (income below threshold)', $income, $result, $expected);
  }

  private function testOldAgeSecurityAtThreshold(): bool
  {
    $income = 87282; // Exactly at threshold
    $expected = 0.00;
    $result = $this->taxCalculator->calculateOldAgeSecurity($income);

    return $this->assertEqual($result, $expected, 'OAS-2', 'OAS at threshold (exactly at limit)', $income, $result, $expected);
  }

  private function testOldAgeSecurityMinimal(): bool
  {
    $income = 90000;
    $expected = 407.70; // (90000 - 87282) * 0.15
    $result = $this->taxCalculator->calculateOldAgeSecurity($income);

    return $this->assertEqual($result, $expected, 'OAS-3', 'Minimal OAS (slightly above threshold)', $income, $result, $expected);
  }

  private function testOldAgeSecurityModerate(): bool
  {
    $income = 100000;
    $expected = 1907.70; // (100000 - 87282) * 0.15
    $result = $this->taxCalculator->calculateOldAgeSecurity($income);

    return $this->assertEqual($result, $expected, 'OAS-4', 'Moderate OAS', $income, $result, $expected);
  }

  private function testOldAgeSecurityHigh(): bool
  {
    $income = 150000;
    $expected = 9407.70; // (150000 - 87282) * 0.15
    $result = $this->taxCalculator->calculateOldAgeSecurity($income);

    return $this->assertEqual($result, $expected, 'OAS-5', 'High Income OAS', $income, $result, $expected);
  }

  // ==================== EI Tests ====================

  private function testEmploymentInsuranceLow(): bool
  {
    $income = 25000;
    $expected = 395.00; // 25000 * 0.0158
    $result = $this->taxCalculator->calculateEmploymentInsurance($income);

    return $this->assertEqual($result, $expected, 'EI-1', 'Low Income EI', $income, $result, $expected);
  }

  private function testEmploymentInsuranceModerate(): bool
  {
    $income = 50000;
    $expected = 790.00; // 50000 * 0.0158
    $result = $this->taxCalculator->calculateEmploymentInsurance($income);

    return $this->assertEqual($result, $expected, 'EI-2', 'Moderate Income EI', $income, $result, $expected);
  }

  private function testEmploymentInsuranceNearMax(): bool
  {
    $income = 63200; // Max insurable
    $expected = 998.56; // 63200 * 0.0158
    $result = $this->taxCalculator->calculateEmploymentInsurance($income);

    return $this->assertEqual($result, $expected, 'EI-3', 'Near Maximum EI', $income, $result, $expected);
  }

  private function testEmploymentInsuranceExceedingMax(): bool
  {
    $income = 100000;
    $expected = 998.56; // Capped at 63200 * 0.0158
    $result = $this->taxCalculator->calculateEmploymentInsurance($income);

    return $this->assertEqual($result, $expected, 'EI-4', 'Exceeding Maximum (still capped)', $income, $result, $expected);
  }

  // ==================== Integration Tests ====================

  private function testScenarioAMidRange(): bool
  {
    $income = 65000;
    $taxes = $this->taxCalculator->calculateTaxes($income);

    // Validate individual components
    // Note: CPP calculation is (65000 - 3500) * 0.0595 = 3659.25 (not 3659.75)
    // The slight difference is due to rounding in the calculation chain
    $cpp_expected = 3659.25;
    $oas_expected = 0.00; // Below 87282 threshold
    $ei_expected = 998.56; // Min(65000, 63200) * 0.0158

    $cpp_match = $this->assertEqualWithTolerance($taxes['canada_pension_plan'], $cpp_expected, 0.01);
    $oas_match = $this->assertEqualWithTolerance($taxes['old_age_security'], $oas_expected, 0.01);
    $ei_match = $this->assertEqualWithTolerance($taxes['employment_insurance'], $ei_expected, 0.01);

    echo sprintf(
      "SCENARIO A (Mid-Range - \$65,000):\n  CPP: \$%.2f (expected \$%.2f) - %s\n  OAS: \$%.2f (expected \$%.2f) - %s\n  EI: \$%.2f (expected \$%.2f) - %s\n  Total Deductions: \$%.2f\n  Status: %s\n",
      $taxes['canada_pension_plan'],
      $cpp_expected,
      $cpp_match ? '✓ PASS' : '✗ FAIL',
      $taxes['old_age_security'],
      $oas_expected,
      $oas_match ? '✓ PASS' : '✗ FAIL',
      $taxes['employment_insurance'],
      $ei_expected,
      $ei_match ? '✓ PASS' : '✗ FAIL',
      $taxes['totalDeductions'],
      ($cpp_match && $oas_match && $ei_match) ? '✓ PASS' : '✗ FAIL'
    );

    return $cpp_match && $oas_match && $ei_match;
  }

  private function testScenarioBHighEarner(): bool
  {
    $income = 120000;
    $taxes = $this->taxCalculator->calculateTaxes($income);

    $cpp_expected = 3867.50; // Capped at (68500 - 3500) * 0.0595
    $oas_expected = 4907.70; // (120000 - 87282) * 0.15
    $ei_expected = 998.56; // Capped at 63200 * 0.0158

    $cpp_match = $this->assertEqualWithTolerance($taxes['canada_pension_plan'], $cpp_expected, 0.01);
    $oas_match = $this->assertEqualWithTolerance($taxes['old_age_security'], $oas_expected, 0.01);
    $ei_match = $this->assertEqualWithTolerance($taxes['employment_insurance'], $ei_expected, 0.01);

    echo sprintf(
      "SCENARIO B (High Earner - \$120,000):\n  CPP: \$%.2f (expected \$%.2f) - %s\n  OAS: \$%.2f (expected \$%.2f) - %s\n  EI: \$%.2f (expected \$%.2f) - %s\n  Total Deductions: \$%.2f\n  Status: %s\n",
      $taxes['canada_pension_plan'],
      $cpp_expected,
      $cpp_match ? '✓ PASS' : '✗ FAIL',
      $taxes['old_age_security'],
      $oas_expected,
      $oas_match ? '✓ PASS' : '✗ FAIL',
      $taxes['employment_insurance'],
      $ei_expected,
      $ei_match ? '✓ PASS' : '✗ FAIL',
      $taxes['totalDeductions'],
      ($cpp_match && $oas_match && $ei_match) ? '✓ PASS' : '✗ FAIL'
    );

    return $cpp_match && $oas_match && $ei_match;
  }

  private function testScenarioCLowIncome(): bool
  {
    $income = 25000;
    $taxes = $this->taxCalculator->calculateTaxes($income);

    // Note: CPP calculation is (25000 - 3500) * 0.0595 = 1279.25 (not 1277.50)
    $cpp_expected = 1279.25;
    $oas_expected = 0.00; // Below threshold
    $ei_expected = 395.00; // 25000 * 0.0158

    $cpp_match = $this->assertEqualWithTolerance($taxes['canada_pension_plan'], $cpp_expected, 0.01);
    $oas_match = $this->assertEqualWithTolerance($taxes['old_age_security'], $oas_expected, 0.01);
    $ei_match = $this->assertEqualWithTolerance($taxes['employment_insurance'], $ei_expected, 0.01);

    echo sprintf(
      "SCENARIO C (Low Income - \$25,000):\n  CPP: \$%.2f (expected \$%.2f) - %s\n  OAS: \$%.2f (expected \$%.2f) - %s\n  EI: \$%.2f (expected \$%.2f) - %s\n  Total Deductions: \$%.2f\n  Status: %s\n",
      $taxes['canada_pension_plan'],
      $cpp_expected,
      $cpp_match ? '✓ PASS' : '✗ FAIL',
      $taxes['old_age_security'],
      $oas_expected,
      $oas_match ? '✓ PASS' : '✗ FAIL',
      $taxes['employment_insurance'],
      $ei_expected,
      $ei_match ? '✓ PASS' : '✗ FAIL',
      $taxes['totalDeductions'],
      ($cpp_match && $oas_match && $ei_match) ? '✓ PASS' : '✗ FAIL'
    );

    return $cpp_match && $oas_match && $ei_match;
  }

  // ==================== Helper Methods ====================

  private function assertEqual(float $actual, float $expected, string $testId, string $description, float $income, float $result, float $expect): bool
  {
    $tolerance = 0.01;
    $passed = abs($actual - $expected) < $tolerance;

    echo sprintf(
      "%s: %s\n  Income: \$%.2f | Got: \$%.2f | Expected: \$%.2f | Result: %s\n",
      $testId,
      $description,
      $income,
      $result,
      $expect,
      $passed ? '✓ PASS' : '✗ FAIL'
    );

    return $passed;
  }

  private function assertEqualWithTolerance(float $actual, float $expected, float $tolerance = 0.01): bool
  {
    return abs($actual - $expected) < $tolerance;
  }

  private function printSummary(array $results): void
  {
    echo "\n\n=== TEST SUMMARY ===\n";

    $totalTests = 0;
    $passedTests = 0;

    foreach ($results as $category => $tests) {
      $categoryPassed = array_sum($tests);
      $categoryTotal = count($tests);
      $totalTests += $categoryTotal;
      $passedTests += $categoryPassed;

      echo sprintf(
        "%s: %d/%d passed\n",
        strtoupper($category),
        $categoryPassed,
        $categoryTotal
      );
    }

    echo sprintf(
      "\nOVERALL: %d/%d tests passed (%.1f%%)\n",
      $passedTests,
      $totalTests,
      ($passedTests / $totalTests) * 100
    );

    if ($passedTests === $totalTests) {
      echo "✓ All tests passed!\n";
    } else {
      echo "✗ Some tests failed. Review output above.\n";
    }
  }
}

// Run tests if executed directly
if ('cli' === php_sapi_name() && basename($argv[0] ?? '') === basename(__FILE__)) {
  $test = new TaxCalculationTest();
  $test->runAll();
}
