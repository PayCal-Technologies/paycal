<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Taxes;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 *
 */
#[Group('unit')]
final class PayrollContractFreezeTest extends TestCase
{
  /**
   * Snapshot test: Assert public payroll response shape, key order, and types.
   */
  public function testPayrollApiResponseShape(): void
  {
    $taxes = new Taxes();
    $result = $taxes->calculateTaxes(50000);
    $expectedKeys = [
        'federal',
        'provincial',
        'employment_insurance',
        'canada_pension_plan',
        'old_age_security',
        'incomeTax',
        'totalDeductions',
    ];
    $this->assertSame($expectedKeys, array_keys($result), 'Payroll API response key order must not change');
    $this->assertIsFloat($result['federal']);
    $this->assertIsFloat($result['provincial']);
    $this->assertIsFloat($result['employment_insurance']);
    $this->assertIsFloat($result['canada_pension_plan']);
    $this->assertIsFloat($result['old_age_security']);
    $this->assertIsFloat($result['incomeTax']);
    $this->assertIsFloat($result['totalDeductions']);
  }
}
