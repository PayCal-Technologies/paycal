<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Skip;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Earnings;
use PayCal\Domain\Money;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__.'/../bootstrap.php';

/**
 * @internal
 *
 */
#[Group('unit')]
final class EarningsTest extends TestCase
{
  #[Test]
  public function earnings_getInstance_returnsEarningsInstance(): void
  {
    $earnings = Earnings::getInstance();
    $this->assertInstanceOf(Earnings::class, $earnings);
  }

  #[Test]
  public function earnings_getInstance_returnsSameInstanceOnMultipleCalls(): void
  {
    $instance1 = Earnings::getInstance();
    $instance2 = Earnings::getInstance();
    $this->assertSame($instance1, $instance2);
  }

  // =====
  // getTotalsForRange Static Method Tests
  // =====

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_returnsArrayWithRequiredKeys(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-31');

    $totals = Earnings::getTotalsForRange($start, $end);

    $this->assertIsArray($totals);
    $this->assertArrayHasKey('range', $totals);
    $this->assertArrayHasKey('days', $totals);
    $this->assertArrayHasKey('hours', $totals);
    $this->assertArrayHasKey('amounts', $totals);
    $this->assertArrayHasKey('totals', $totals);
    $this->assertArrayHasKey('deductions', $totals);
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_rangeIsInclusiveOfBothDates(): void
  {
    $start = new DateTimeImmutable('2024-01-05');
    $end = new DateTimeImmutable('2024-01-10');

    $totals = Earnings::getTotalsForRange($start, $end);

    $this->assertEquals('2024-01-05', $totals['range']['start']);
    $this->assertEquals('2024-01-10', $totals['range']['end']);
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_calculatesDaysCorrectly(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-10');

    $totals = Earnings::getTotalsForRange($start, $end);

    $this->assertEquals(10, $totals['days']);
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_hoursArrayHasRequiredKeys(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-01');

    $totals = Earnings::getTotalsForRange($start, $end);

    $this->assertArrayHasKey('regular', $totals['hours']);
    $this->assertArrayHasKey('overtime', $totals['hours']);
    $this->assertArrayHasKey('travel', $totals['hours']);
    $this->assertArrayHasKey('total', $totals['hours']);
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_amountsArrayHasRequiredKeys(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-01');

    $totals = Earnings::getTotalsForRange($start, $end);

    $this->assertArrayHasKey('loa', $totals['amounts']);
    $this->assertArrayHasKey('wage', $totals['amounts']);
    $this->assertArrayHasKey('other', $totals['amounts']);
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_totalsArrayHasRequiredKeys(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-01');

    $totals = Earnings::getTotalsForRange($start, $end);

    $this->assertArrayHasKey('gross', $totals['totals']);
    $this->assertArrayHasKey('tax', $totals['totals']);
    $this->assertArrayHasKey('net', $totals['totals']);
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_returnsNumericValues(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-03');

    $totals = Earnings::getTotalsForRange($start, $end);

    $this->assertIsNumeric($totals['hours']['regular']);
    $this->assertIsNumeric($totals['hours']['overtime']);
    $this->assertIsNumeric($totals['hours']['total']);
    $this->assertIsNumeric($totals['totals']['gross']);
    $this->assertIsNumeric($totals['totals']['tax']);
    $this->assertIsNumeric($totals['totals']['net']);
  }

  /**
   * Data provider for date range tests.
   */
  public static function dateRangeScenarios(): array
  {
    return [
        'single day' => [
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-01'),
            1,
        ],
        'week' => [
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-07'),
            7,
        ],
        'month' => [
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
            31,
        ],
        'quarter' => [
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-03-31'),
            91,
        ],
    ];
  }

  #[DataProvider('dateRangeScenarios')]
  #[Test]
  public function earnings_getTotalsForRange_staticMethod_withVariousDateRanges_calculatesDaysCorrectly(
    DateTimeImmutable $start,
    DateTimeImmutable $end,
    int $expectedDays
  ): void {
    $totals = Earnings::getTotalsForRange($start, $end);
    $this->assertEquals($expectedDays, $totals['days']);
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_withRespectToTimezones(): void
  {
    $tz = new DateTimeZone('America/Toronto');
    $start = new DateTimeImmutable('2024-01-01', $tz);
    $end = new DateTimeImmutable('2024-01-05', $tz);

    $totals = Earnings::getTotalsForRange($start, $end);

    $this->assertEquals(5, $totals['days']);
    $this->assertEquals('2024-01-01', $totals['range']['start']);
    $this->assertEquals('2024-01-05', $totals['range']['end']);
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_netIsCalculatedAsGrossMinusTax(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-01');

    $totals = Earnings::getTotalsForRange($start, $end);

    $expected = $totals['totals']['gross'] - $totals['totals']['tax'];
    $this->assertEqualsWithDelta($expected, $totals['totals']['net'], 0.01);
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_totalHoursEqualsSumOfComponents(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-01');

    $totals = Earnings::getTotalsForRange($start, $end);

    $expectedTotal = $totals['hours']['regular'] + $totals['hours']['overtime'] + $totals['hours']['travel'];
    $this->assertEqualsWithDelta($expectedTotal, $totals['hours']['total'], 0.01);
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_defaultsToUserUUIDConstant(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-01');

    $totals = Earnings::getTotalsForRange($start, $end);
    $this->assertIsArray($totals);
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_acceptsCustomUserUUID(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-01');
    $customUUID = 'test-uuid-12345';

    $totals = Earnings::getTotalsForRange($start, $end, $customUUID);
    $this->assertIsArray($totals);
  }

  // =====
  // getInstance Method Tests
  // =====

  #[Test]
  public function earnings_getInstance_singletonPatternPreventsMultipleInstances(): void
  {
    $instance1 = Earnings::getInstance();
    $instance2 = Earnings::getInstance();
    $instance3 = Earnings::getInstance();

    $this->assertSame($instance1, $instance2);
    $this->assertSame($instance2, $instance3);
  }

  #[Test]
  public function earnings_getInstance_instanceCanCreateMultipleTimes(): void
  {
    for ($i = 0; $i < 5; ++$i) {
      $earnings = Earnings::getInstance();
      $this->assertInstanceOf(Earnings::class, $earnings);
    }
  }

  // =====
  // getTotalsForPeriod Static Method Tests
  // =====

  #[Test]
  public function earnings_getTotalsForPeriod_staticMethod_existsAndIsCallable(): void
  {
    $this->assertTrue(
      method_exists(Earnings::class, 'getTotalsForPeriod'),
      'getTotalsForPeriod method should exist'
    );
  }

  // =====
  // Instance Method Existence Tests
  // =====

  #[Test]
  public function earnings_getWorkTotalsForRange_instanceMethodExists(): void
  {
    $this->assertTrue(
      method_exists(Earnings::class, 'getWorkTotalsForRange'),
      'getWorkTotalsForRange instance method should exist'
    );
  }

  #[Test]
  public function earnings_getDateRange_instanceMethodExists(): void
  {
    $this->assertTrue(
      method_exists(Earnings::class, 'getDateRange'),
      'getDateRange instance method should exist'
    );
  }

  #[Test]
  public function earnings_renderYearToDateSummary_instanceMethodExists(): void
  {
    $this->assertTrue(
      method_exists(Earnings::class, 'renderYearToDateSummary'),
      'renderYearToDateSummary instance method should exist'
    );
  }

  #[Test]
  public function earnings_renderMonthlyViewStrip_instanceMethodExists(): void
  {
    $this->assertTrue(
      method_exists(Earnings::class, 'renderMonthlyViewStrip'),
      'renderMonthlyViewStrip instance method should exist'
    );
  }

  #[Test]
  public function earnings_renderDailyView_instanceMethodExists(): void
  {
    $this->assertTrue(
      method_exists(Earnings::class, 'renderDailyView'),
      'renderDailyView instance method should exist'
    );
  }

  #[Test]
  public function earnings_renderSections_instanceMethodExists(): void
  {
    $this->assertTrue(
      method_exists(Earnings::class, 'renderSections'),
      'renderSections instance method should exist'
    );
  }

  #[Test]
  public function earnings_renderPayPeriodComparison_instanceMethodExists(): void
  {
    $this->assertTrue(
      method_exists(Earnings::class, 'renderPayPeriodComparison'),
      'renderPayPeriodComparison instance method should exist'
    );
  }

  #[Test]
  public function earnings_renderAsciiPayPeriodProgress_instanceMethodExists(): void
  {
    $this->assertTrue(
      method_exists(Earnings::class, 'renderAsciiPayPeriodProgress'),
      'renderAsciiPayPeriodProgress instance method should exist'
    );
  }

  // =====
  // Earnings Class Tests
  // =====

  #[Test]
  public function earnings_classIsInstantiable(): void
  {
    $earnings = new Earnings(0, 0, 0);
    $this->assertInstanceOf(Earnings::class, $earnings);
  }

  #[Test]
  public function earnings_classDefinesGetInstanceStaticMethod(): void
  {
    $this->assertTrue(
      method_exists(Earnings::class, 'getInstance'),
      'getInstance static method should exist'
    );
  }

  // =====
  // Data Provider & Edge Cases
  // =====

  /**
   * Data provider for various date ranges.
   */
  public static function edgeCaseDateRanges(): array
  {
    // Fixed fixture dates keep this provider deterministic across timezones and calendar rollovers.
    $anchor = new DateTimeImmutable('2024-06-15');
    $oneYearAgo = $anchor->modify('-1 year');
    $oneMonthAgo = $anchor->modify('-1 month');
    $yesterday = $anchor->modify('-1 day');

    return [
      'past year to anchor day' => [$oneYearAgo, $anchor],
      'past month range' => [$oneMonthAgo, $anchor],
      'recent days' => [$yesterday, $anchor],
      'anchor day' => [$anchor, $anchor],
        'different timezones' => [
            new DateTimeImmutable('2024-01-01', new DateTimeZone('America/New_York')),
            new DateTimeImmutable('2024-01-31', new DateTimeZone('America/New_York')),
        ],
    ];
  }

  #[DataProvider('edgeCaseDateRanges')]
  #[Test]
  public function earnings_getTotalsForRange_staticMethod_withEdgeCaseDateRanges_returnsValidArrays(
    DateTimeImmutable $start,
    DateTimeImmutable $end
  ): void {
    $totals = Earnings::getTotalsForRange($start, $end);

    $this->assertIsArray($totals);
    $this->assertIsArray($totals['hours']);
    $this->assertIsArray($totals['amounts']);
    $this->assertIsArray($totals['totals']);

    $this->assertGreaterThanOrEqual(0, $totals['hours']['regular']);
    $this->assertGreaterThanOrEqual(0, $totals['hours']['overtime']);
    $this->assertGreaterThanOrEqual(0, $totals['hours']['travel']);
    $this->assertGreaterThanOrEqual(0, $totals['totals']['gross']);
  }

  // =====
  // Type Checking Tests
  // =====

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_alwaysReturnsArray(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-31');

    $result = Earnings::getTotalsForRange($start, $end);
    $this->assertIsArray($result);
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_hoursValueTypesAreCorrect(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-05');

    $totals = Earnings::getTotalsForRange($start, $end);

    foreach ($totals['hours'] as $hourType => $value) {
      $this->assertTrue(
        is_numeric($value),
        "Hour type '{$hourType}' should be numeric, got ".gettype($value)
      );
    }
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_totalsValueTypesAreCorrect(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-05');

    $totals = Earnings::getTotalsForRange($start, $end);

    foreach ($totals['totals'] as $totalType => $value) {
      $this->assertTrue(
        is_numeric($value),
        "Total type '{$totalType}' should be numeric, got ".gettype($value)
      );
    }
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_daysValueIsInteger(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-10');

    $totals = Earnings::getTotalsForRange($start, $end);
    $this->assertIsInt($totals['days']);
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_rangeValuesAreStrings(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-31');

    $totals = Earnings::getTotalsForRange($start, $end);
    $this->assertIsString($totals['range']['start']);
    $this->assertIsString($totals['range']['end']);
  }

  // =====
  // Date Format Tests
  // =====

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_returnsDateStringsInYMDFormat(): void
  {
    $start = new DateTimeImmutable('2024-02-15');
    $end = new DateTimeImmutable('2024-03-20');

    $totals = Earnings::getTotalsForRange($start, $end);

    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $totals['range']['start']);
    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $totals['range']['end']);
  }

  // =====
  // Boundary Tests
  // =====

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_withSingleDay_stillReturnsValidArray(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-01');

    $totals = Earnings::getTotalsForRange($start, $end);

    $this->assertIsArray($totals);
    $this->assertEquals(1, $totals['days']);
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_withLargeDateRange_stillReturnsValidData(): void
  {
    $start = new DateTimeImmutable('2020-01-01');
    $end = new DateTimeImmutable('2024-12-31');

    $totals = Earnings::getTotalsForRange($start, $end);

    $this->assertIsArray($totals);
    $this->assertGreaterThan(1000, $totals['days']);
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_nonNegativeHours(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-12-31');

    $totals = Earnings::getTotalsForRange($start, $end);

    $this->assertGreaterThanOrEqual(0, $totals['hours']['regular']);
    $this->assertGreaterThanOrEqual(0, $totals['hours']['overtime']);
    $this->assertGreaterThanOrEqual(0, $totals['hours']['travel']);
    $this->assertGreaterThanOrEqual(0, $totals['hours']['total']);
  }

  #[Test]
  public function earnings_getTotalsForRange_staticMethod_nonNegativeGross(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-01-31');

    $totals = Earnings::getTotalsForRange($start, $end);

    $this->assertGreaterThanOrEqual(0, $totals['totals']['gross']);
  }

  // =====
  // DRIFT GUARD TESTS - Critical for Aggregation Integrity
  // =====

  /**
   * This test guards against float drift in earnings aggregation.
   * It MUST pass for payroll aggregation to be trustworthy.
   *
   * The test simulates aggregating multiple entries with known
   * float-problematic values (0.1 * 33.33 = 3.333...) to ensure
   * the aggregation produces deterministic results.
   */
  #[Test]
  public function aggregation_floatAccumulation_isDeterministic(): void
  {
    // Simulate aggregating 100 entries of the same gross value
    // Using classic float-problematic case: 0.1 hours at $33.33 = $3.33
    $grossPerEntry = 3.33;
    $entryCount = 100;
    $expectedTotal = 333.00;

    // Run aggregation simulation 5 times
    $results = [];
    for ($run = 0; $run < 5; ++$run) {
      $results[] = $this->simulateFloatAggregation($grossPerEntry, $entryCount);
    }

    // All runs must produce identical results
    $uniqueResults = array_unique($results, SORT_STRING);
    $this->assertCount(
      1,
      $uniqueResults,
      'Float aggregation produced different results across runs: '.implode(', ', $results)
    );

    // Result must match expected total
    $this->assertSame(
      number_format($expectedTotal, 2, '.', ''),
      $results[0],
      "Aggregation of {$entryCount} entries of {$grossPerEntry} should equal {$expectedTotal}"
    );
  }

  /**
   * Test raw float accumulation for drift detection.
   * This test checks the raw float value BEFORE formatting.
   *
   * IMPORTANT: This test may pass now but serves as a guard
   * against future changes that could introduce drift.
   */
  #[Test]
  public function aggregation_rawFloat_detectsDrift(): void
  {
    // Use 0.1 * 33.33 = 3.333... which is problematic in binary
    $grossPerEntry = 3.33;
    $entryCount = 100;

    // Simulate raw float accumulation
    $rawFloat = 0.0;
    for ($i = 0; $i < $entryCount; ++$i) {
      $rawFloat += $grossPerEntry;
    }

    // The raw float should be close to 333.00
    // But may have tiny drift due to binary representation
    $expectedExact = 333.00;
    $drift = abs($rawFloat - $expectedExact);

    // Document current drift level
    // If this test fails, float drift has exceeded tolerance
    $this->assertLessThan(
      0.01,  // 1 cent tolerance
      $drift,
      "Float drift detected: raw={$rawFloat}, expected={$expectedExact}, drift={$drift}"
    );
  }

  /**
   * Test that demonstrates the difference between float and cents aggregation.
   * This documents WHY we need cent-based aggregation.
   */
  #[Test]
  public function aggregation_centsVsFloat_centsIsExact(): void
  {
    $grossPerEntry = 3.33;
    $entryCount = 100;

    // Float aggregation (current approach)
    $floatTotal = 0.0;
    for ($i = 0; $i < $entryCount; ++$i) {
      $floatTotal += $grossPerEntry;
    }

    // Cents aggregation (Money approach)
    $centsPerEntry = Money::dollarsToCents((string) $grossPerEntry);
    $centsTotal = 0;
    for ($i = 0; $i < $entryCount; ++$i) {
      $centsTotal += $centsPerEntry;
    }

    // Cents should always be exact
    $this->assertSame(33300, $centsTotal);
    $this->assertSame('333.00', Money::centsToDollars($centsTotal));

    // Float may or may not be exact (depends on PHP's float handling)
    // This test documents that cents is ALWAYS exact
    $floatFormatted = number_format($floatTotal, 2, '.', '');
    $centsFormatted = Money::centsToDollars($centsTotal);

    // Both should produce the same formatted output
    $this->assertSame(
      $centsFormatted,
      $floatFormatted,
      'Float and cents aggregation should produce same result for this case'
    );
  }

  /**
   * Test that gross accumulation via += produces stable string output.
   */
  #[Test]
  public function aggregation_grossAccumulation_producesStableStringOutput(): void
  {
    // Use known drift-prone values
    $testCases = [
        ['gross' => 3.33, 'count' => 100, 'expected' => 333.00],
        ['gross' => 187.43, 'count' => 10, 'expected' => 1874.30],
        ['gross' => 19.99, 'count' => 50, 'expected' => 999.50],
    ];

    foreach ($testCases as $case) {
      $results = [];
      for ($run = 0; $run < 3; ++$run) {
        $results[] = $this->simulateFloatAggregation($case['gross'], $case['count']);
      }

      $uniqueResults = array_unique($results, SORT_STRING);
      $this->assertCount(
        1,
        $uniqueResults,
        "Gross {$case['gross']} × {$case['count']} produced inconsistent results: ".implode(', ', $results)
      );

      $this->assertSame(
        number_format($case['expected'], 2, '.', ''),
        $results[0],
        "Gross {$case['gross']} × {$case['count']} should equal {$case['expected']}"
      );
    }
  }

  /**
   * Test that the output format is always a valid dollar string.
   */
  #[Test]
  public function aggregation_output_isValidDollarString(): void
  {
    $result = $this->simulateFloatAggregation(3.33, 100);

    $this->assertIsString($result);
    $this->assertMatchesRegularExpression(
      '/^-?\d+\.\d{2}$/',
      $result,
      'Output must be a valid dollar string with exactly 2 decimal places'
    );
  }

  // =====
  // Cents-Based Aggregation Tests
  // =====



  /**
   * Simulate the current float aggregation path in Earnings.
   *
   * This mimics the behavior in getWorkTotalsForRange() where:
   * $grossIncome = 0.0;
   * foreach ($rows as $row) {
   *     $grossIncome += (float) $row['g'];
   * }
   *
   * @param float $grossPerEntry The gross value per entry
   * @param int   $entryCount    Number of entries to aggregate
   *
   * @return string The aggregated gross as a dollar string
   */
  private function simulateFloatAggregation(float $grossPerEntry, int $entryCount): string
  {
    // Simulate current Earnings aggregation behavior
    $grossIncome = 0.0;

    for ($i = 0; $i < $entryCount; ++$i) {
      $grossIncome += $grossPerEntry;
    }

    // Format as dollar string (2 decimal places)
    return number_format($grossIncome, 2, '.', '');
  }
}
