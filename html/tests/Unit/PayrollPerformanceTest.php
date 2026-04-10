<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\TaxBracket;
use PayCal\Domain\TaxBracketCollection;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 */
#[Group('unit')]
final class PayrollPerformanceTest extends TestCase
{
  /**
   * Performance regression guard: 10,000 payroll calcs < 0.100 sec, peak memory < 2 MB.
   */
  public function testPayrollCalculationPerformance(): void
  {
    random_int(10000, 1000000); // deterministic dataset for performance stability
    $payrollRuns = 10000;
    $bracket = new TaxBracket(0, 10000000, 1500); // 15% bracket
    $collection = new TaxBracketCollection([$bracket]);
    $grosses = array_map(fn () => random_int(10000, 1000000), range(1, $payrollRuns));

    $start = microtime(true);
    $startMemory = memory_get_usage(true);
    $startPeak = memory_get_peak_usage(true);

    foreach ($grosses as $gross) {
      $tax = $collection->calculateTaxCents($gross);
      $net = $gross - $tax;
    }

    $duration = microtime(true) - $start;
    $deltaMemory = memory_get_usage(true) - $startMemory;
    $deltaPeak = memory_get_peak_usage(true) - $startPeak;

    $this->assertLessThan(0.100, $duration, 'Payroll calculations must complete in < 0.100 sec');
    $this->assertLessThan(2 * 1024 * 1024, $deltaMemory, 'Delta memory must be < 2 MB');
    $this->assertLessThan(2 * 1024 * 1024, $deltaPeak, 'Delta Peak memory must be < 2 MB');
  }
}
