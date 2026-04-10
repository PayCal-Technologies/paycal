<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Money;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 */
#[CoversClass(Money::class)]
#[Group('unit')]
final class MoneyTest extends TestCase
{
  /**
   * Rounding policy lock-in: All Money calculations must use PHP_ROUND_HALF_UP.
   * This test asserts that .5 cent values always round up, and all conversions are deterministic.
   */
  public function testRoundingPolicyLockIn(): void
  {
    // .5 cent values should always round up
    $this->assertSame(1, Money::multiply(0.00005, '100.00'), '0.00005 hours at $100 should round up to 1 cent');
    $this->assertSame(10, Money::multiply(0.005, '20.00'), '0.005 hours at $20 should yield 10 cents');
    $this->assertSame(1, Money::multiplyByFactor(1, 0.5), '1 cent * 0.5 should round up to 1 cent');
    $this->assertSame(2, Money::divide(1, 0.5), '1 cent / 0.5 should yield 2 cents');

    // Negative .5 cent values should round up to -1
    $this->assertSame(-1, Money::multiply(-0.00005, '100.00'), '-0.00005 hours at $100 should round up to -1 cent');
    $this->assertSame(-10, Money::multiply(-0.005, '20.00'), '-0.005 hours at $20 should yield -10 cents');
    $this->assertSame(-1, Money::multiplyByFactor(-1, 0.5), '-1 cent * 0.5 should round up to -1 cent');
    $this->assertSame(-2, Money::divide(-1, 0.5), '-1 cent / 0.5 should yield -2 cents');

    // Confirm deterministic rounding for classic drift cases
    $this->assertSame(333, Money::calculateGrossSimple(0.1, '33.33'));
    $this->assertSame(18743, Money::calculateGrossSimple(7.35, '25.50'));
    $this->assertSame(15992, Money::calculateGrossSimple(8.0, '19.99'));
  }
  // =====
  // Dollar to Cents Conversion Tests
  // =====

  #[Test]
  public function dollarsToCentsConvertsWholeDollars(): void
  {
    $this->assertSame(10000, Money::dollarsToCents('100'));
    $this->assertSame(2500, Money::dollarsToCents('25'));
    $this->assertSame(0, Money::dollarsToCents('0'));
  }

  #[Test]
  public function dollarsToCentsConvertsDollarsWithCents(): void
  {
    $this->assertSame(2550, Money::dollarsToCents('25.50'));
    $this->assertSame(1999, Money::dollarsToCents('19.99'));
    $this->assertSame(10050, Money::dollarsToCents('100.50'));
  }

  #[Test]
  public function dollarsToCentsHandlesSingleDecimalDigit(): void
  {
    // "25.5" should be 2550 cents (treat as 25.50)
    $this->assertSame(2550, Money::dollarsToCents('25.5'));
    $this->assertSame(1990, Money::dollarsToCents('19.9'));
  }

  #[Test]
  public function dollarsToCentsHandlesEmptyString(): void
  {
    $this->assertSame(0, Money::dollarsToCents(''));
    $this->assertSame(0, Money::dollarsToCents('0'));
  }

  #[Test]
  public function dollarsToCentsHandlesNegativeAmounts(): void
  {
    $this->assertSame(-2550, Money::dollarsToCents('-25.50'));
    $this->assertSame(-1999, Money::dollarsToCents('-19.99'));
  }

  #[Test]
  public function dollarsToCentsHandlesWhitespace(): void
  {
    $this->assertSame(2550, Money::dollarsToCents(' 25.50 '));
    $this->assertSame(1999, Money::dollarsToCents('19.99  '));
  }

  // =====
  // Cents to Dollar Conversion Tests
  // =====

  #[Test]
  public function centsToDollarsConvertsWholeDollars(): void
  {
    $this->assertSame('100.00', Money::centsToDollars(10000));
    $this->assertSame('25.00', Money::centsToDollars(2500));
    $this->assertSame('0.00', Money::centsToDollars(0));
  }

  #[Test]
  public function centsToDollarsConvertsCents(): void
  {
    $this->assertSame('25.50', Money::centsToDollars(2550));
    $this->assertSame('19.99', Money::centsToDollars(1999));
    $this->assertSame('100.50', Money::centsToDollars(10050));
  }

  #[Test]
  public function centsToDollarsHandlesSmallAmounts(): void
  {
    $this->assertSame('0.01', Money::centsToDollars(1));
    $this->assertSame('0.99', Money::centsToDollars(99));
    $this->assertSame('0.50', Money::centsToDollars(50));
  }

  #[Test]
  public function centsToDollarsHandlesNegativeAmounts(): void
  {
    $this->assertSame('-25.50', Money::centsToDollars(-2550));
    $this->assertSame('-19.99', Money::centsToDollars(-1999));
  }

  // =====
  // Round-Trip Tests
  // =====

  #[Test]
  public function roundTripPreservesValue(): void
  {
    $testValues = ['25.50', '19.99', '100.00', '0.01', '9999.99'];

    foreach ($testValues as $dollars) {
      $cents = Money::dollarsToCents($dollars);
      $backToDollars = Money::centsToDollars($cents);
      $this->assertSame($dollars, $backToDollars, "Round-trip failed for {$dollars}");
    }
  }

  // =====
  // DRIFT GUARD TESTS - Critical for Payroll Integrity
  // =====

  /**
   * This test guards against float drift in gross calculation.
   * It MUST pass for payroll to be trustworthy.
   */
  #[Test]
  public function calculateGrossReturnsIntegerCents(): void
  {
    $gross = Money::calculateGrossSimple(0.1, '33.33');

    $this->assertIsInt($gross);
  }

  /**
   * This test ensures gross calculation is deterministic.
   * Same inputs must always produce same outputs.
   */
  #[Test]
  public function calculateGrossIsDeterministic(): void
  {
    $hours = 0.1;
    $wage = '33.33';

    $gross1 = Money::calculateGrossSimple($hours, $wage);
    $gross2 = Money::calculateGrossSimple($hours, $wage);
    $gross3 = Money::calculateGrossSimple($hours, $wage);

    $this->assertSame($gross1, $gross2);
    $this->assertSame($gross2, $gross3);
  }

  /**
   * Classic float drift case: 0.1 hours at $33.33/hour.
   * Float math: 0.1 * 33.33 = 3.333... (repeating)
   * Must resolve deterministically to integer cents.
   */
  #[Test]
  public function calculateGrossHandlesClassicDriftCase(): void
  {
    $gross = Money::calculateGrossSimple(0.1, '33.33');

    // 0.1 * 3333 = 333.3 cents, rounds to 333 cents = $3.33
    $this->assertSame(333, $gross);
    $this->assertSame('3.33', Money::centsToDollars($gross));
  }

  /**
   * Known drift case: 7.35 hours at $25.50/hour.
   * Float math: 7.35 * 25.50 = 187.425
   * Must resolve deterministically.
   */
  #[Test]
  public function calculateGrossHandlesKnownDriftCase735Hours(): void
  {
    $gross = Money::calculateGrossSimple(7.35, '25.50');

    // 7.35 * 2550 = 18742.5 cents, rounds to 18743 cents
    $this->assertSame(18743, $gross);
    $this->assertSame('187.43', Money::centsToDollars($gross));
  }

  /**
   * Common retail wage: 8 hours at $19.99/hour.
   */
  #[Test]
  public function calculateGrossHandlesCommonRetailWage(): void
  {
    $gross = Money::calculateGrossSimple(8.0, '19.99');

    // 8.0 * 1999 = 15992 cents = $159.92
    $this->assertSame(15992, $gross);
    $this->assertSame('159.92', Money::centsToDollars($gross));
  }

  /**
   * Per-component rounding test: regular and overtime calculated separately.
   */
  #[Test]
  public function calculateGrossUsesPerComponentRounding(): void
  {
    // 7.35 regular hours at $25.50 = 18742.5 → 18743 cents
    // 2.5 overtime hours at $25.50 * 1.5 = $38.25/hour
    // 2.5 * 3825 = 9562.5 → 9563 cents
    // Total: 18743 + 9563 = 28306 cents

    $gross = Money::calculateGross(7.35, 2.5, '25.50');

    $this->assertSame(28306, $gross);
  }

  /**
   * Overtime calculation must use time-and-a-half.
   */
  #[Test]
  public function calculateGrossOvertimeUsesTimeAndAHalf(): void
  {
    // 0 hours regular, 2 hours overtime at $20/hour
    // Overtime rate = $30/hour
    // 2 * 3000 = 6000 cents = $60.00

    $gross = Money::calculateGross(0.0, 2.0, '20.00');

    $this->assertSame(6000, $gross);
  }

  /**
   * Zero hours should produce zero gross.
   */
  #[Test]
  public function calculateGrossZeroHoursReturnsZero(): void
  {
    $gross = Money::calculateGross(0.0, 0.0, '25.50');

    $this->assertSame(0, $gross);
  }

  /**
   * Zero wage should produce zero gross.
   */
  #[Test]
  public function calculateGrossZeroWageReturnsZero(): void
  {
    $gross = Money::calculateGross(8.0, 2.0, '0');

    $this->assertSame(0, $gross);
  }

  #[Test]
  #[DataProvider('driftRiskCasesProvider')]
  public function calculateGrossSimpleHandlesDriftRiskCases(float $hours, string $wage, int $expectedCents): void
  {
    $gross = Money::calculateGrossSimple($hours, $wage);

    $this->assertSame($expectedCents, $gross);
  }

  // =====
  // Data Provider Tests for Edge Cases
  // =====

  public static function driftRiskCasesProvider(): array
  {
    return [
        // [hours, wage, expectedCents, description]
        'classic drift 0.1 * 33.33' => [0.1, '33.33', 333],
        '7.35 hours at 25.50' => [7.35, '25.50', 18743],
        '8 hours at 19.99' => [8.0, '19.99', 15992],
        '0.01 hours at 100.00' => [0.01, '100.00', 100],  // 0.01 * 10000 = 100 cents
        '0.5 hours at 10.00' => [0.5, '10.00', 500],
        '0.25 hours at 15.00' => [0.25, '15.00', 375],
        '40 hours at 15.00' => [40.0, '15.00', 60000],
        '0.33 hours at 30.30' => [0.33, '30.30', 1000], // 0.33 * 3030 = 999.9 → 1000
        '0.67 hours at 30.30' => [0.67, '30.30', 2030], // 0.67 * 3030 = 2030.1 → 2030
    ];
  }

  // =====
  // Stability Tests - Repeated Calculations Must Not Drift
  // =====

  /**
   * Running the same calculation 100 times must produce identical results.
   */
  #[Test]
  public function calculateGrossStableOver100Iterations(): void
  {
    $hours = 7.35;
    $wage = '25.50';

    $results = [];
    for ($i = 0; $i < 100; ++$i) {
      $results[] = Money::calculateGrossSimple($hours, $wage);
    }

    $first = $results[0];
    foreach ($results as $result) {
      $this->assertSame($first, $result);
    }
  }

  /**
   * Different drift-prone values must all be stable.
   */
  #[Test]
  public function calculateGrossMultipleDriftCasesAllStable(): void
  {
    $testCases = [
        [0.1, '33.33'],
        [7.35, '25.50'],
        [8.0, '19.99'],
        [0.33, '30.30'],
        [0.67, '30.30'],
    ];

    foreach ($testCases as [$hours, $wage]) {
      $gross1 = Money::calculateGrossSimple($hours, $wage);
      $gross2 = Money::calculateGrossSimple($hours, $wage);
      $gross3 = Money::calculateGrossSimple($hours, $wage);

      $this->assertSame(
        $gross1,
        $gross2,
        "Drift detected for hours={$hours}, wage={$wage}"
      );
      $this->assertSame(
        $gross2,
        $gross3,
        "Drift detected for hours={$hours}, wage={$wage}"
      );
    }
  }

  // =====
  // Arithmetic Operations Tests
  // =====

  #[Test]
  public function addSumsCentsCorrectly(): void
  {
    $this->assertSame(5000, Money::add(2000, 3000));
    $this->assertSame(0, Money::add(0, 0));
    $this->assertSame(100, Money::add(50, 50));
  }

  #[Test]
  public function subtractDifferencesCentsCorrectly(): void
  {
    $this->assertSame(2000, Money::subtract(5000, 3000));
    $this->assertSame(0, Money::subtract(100, 100));
    $this->assertSame(-50, Money::subtract(50, 100));
  }

  #[Test]
  public function multiplyByFactorAppliesFactorCorrectly(): void
  {
    // 10000 cents * 0.15 = 1500 cents
    $this->assertSame(1500, Money::multiplyByFactor(10000, 0.15));

    // 10000 cents * 0.225 = 2250 cents (rounds)
    $this->assertSame(2250, Money::multiplyByFactor(10000, 0.225));

    // 10000 cents * 0.224 = 2240 cents
    $this->assertSame(2240, Money::multiplyByFactor(10000, 0.224));
  }

  #[Test]
  public function divideDividesCentsCorrectly(): void
  {
    // 10000 cents / 2 = 5000 cents
    $this->assertSame(5000, Money::divide(10000, 2.0));

    // 10000 cents / 3 = 3333.33... → 3333 cents
    $this->assertSame(3333, Money::divide(10000, 3.0));

    // Division by zero returns 0
    $this->assertSame(0, Money::divide(10000, 0.0));
  }

  // =====
  // Boundary Tests
  // =====

  #[Test]
  public function dollarsToCentsHandlesLargeAmounts(): void
  {
    // $1,000,000.00 = 100,000,000 cents
    $this->assertSame(100000000, Money::dollarsToCents('1000000.00'));

    // $999,999.99 = 99,999,999 cents
    $this->assertSame(99999999, Money::dollarsToCents('999999.99'));
  }

  #[Test]
  public function centsToDollarsHandlesLargeAmounts(): void
  {
    $this->assertSame('1000000.00', Money::centsToDollars(100000000));
    $this->assertSame('999999.99', Money::centsToDollars(99999999));
  }

  // =====
  // Precision Edge Cases
  // =====

  #[Test]
  public function multiplyHandlesFractionalHours(): void
  {
    // 0.01 hours at $100/hour = 100 cents ($1.00)
    $this->assertSame(100, Money::multiply(0.01, '100.00'));

    // 0.001 hours at $100/hour = 10 cents
    $this->assertSame(10, Money::multiply(0.001, '100.00'));

    // 0.0001 hours at $100/hour = 1 cent
    $this->assertSame(1, Money::multiply(0.0001, '100.00'));

    // 0.00005 hours at $100/hour = 0.5 cent → rounds to 1
    $this->assertSame(1, Money::multiply(0.00005, '100.00'));

    // 0.00004 hours at $100/hour = 0.4 cent → rounds to 0
    $this->assertSame(0, Money::multiply(0.00004, '100.00'));
  }

  #[Test]
  public function calculateGrossRegularAndOvertimeBothContribute(): void
  {
    // 8 regular hours + 2 overtime hours at $20/hour
    // Regular: 8 * 2000 = 16000 cents
    // Overtime: 2 * 2000 * 1.5 = 6000 cents
    // Total: 22000 cents = $220.00

    $gross = Money::calculateGross(8.0, 2.0, '20.00');

    $this->assertSame(22000, $gross);
  }
}
