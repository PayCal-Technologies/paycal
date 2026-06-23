<?php declare(strict_types=1);

namespace PayCal\Tests\Indigenous;

use PayCal\Domain\IndigenousEmploymentIncomeExemption;
use PayCal\Domain\Taxes;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Indigenous employment income exemption tests based on CRA Indian Act
 * section 87 employment income guidelines.
 */
final class IndigenousEmploymentIncomeExemptionTest extends TestCase
{
  #[Test]
  public function guideline1AtLeast90PercentOnReserveDutiesMakesEmploymentIncomeTaxExempt(): void
  {
    $result = IndigenousEmploymentIncomeExemption::evaluate(
      incomeCents: 9135504,
      workerRegisteredOrEntitled: true,
      onReserveDutyPercent: 90,
    );

    $this->assertTrue($result['fullyExempt']);
    $this->assertFalse($result['prorated']);
    $this->assertSame(9135504, $result['exemptCents']);
    $this->assertSame(0, $result['taxableCents']);
    $this->assertSame('guideline_1_90_percent_on_reserve', $result['reason']);
  }

  #[Test]
  public function indigenousBusinessAloneDoesNotCreateTaxExemptionWithoutEligibleWorkerAndReserveConnection(): void
  {
    $result = IndigenousEmploymentIncomeExemption::evaluate(
      incomeCents: 9135504,
      workerRegisteredOrEntitled: false,
      onReserveDutyPercent: 90,
      employerResidentOnReserve: true,
    );

    $this->assertFalse($result['fullyExempt']);
    $this->assertSame(0, $result['exemptCents']);
    $this->assertSame(9135504, $result['taxableCents']);
  }

  #[Test]
  public function lessThan90PercentOnReserveDutiesIsProratedWhenNoOtherGuidelineApplies(): void
  {
    $result = IndigenousEmploymentIncomeExemption::evaluate(
      incomeCents: 10000000,
      workerRegisteredOrEntitled: true,
      onReserveDutyPercent: 40,
    );

    $this->assertFalse($result['fullyExempt']);
    $this->assertTrue($result['prorated']);
    $this->assertSame(4000000, $result['exemptCents']);
    $this->assertSame(6000000, $result['taxableCents']);
  }

  #[Test]
  public function moreThan50PercentOnReservePlusEmployerOnReserveMakesAllIncomeExempt(): void
  {
    $result = IndigenousEmploymentIncomeExemption::evaluate(
      incomeCents: 10000000,
      workerRegisteredOrEntitled: true,
      onReserveDutyPercent: 51,
      employerResidentOnReserve: true,
      workerLivesOnReserve: false,
    );

    $this->assertTrue($result['fullyExempt']);
    $this->assertSame(10000000, $result['exemptCents']);
    $this->assertSame(0, $result['taxableCents']);
    $this->assertSame('guideline_3_majority_plus_connecting_factor', $result['reason']);
  }

  #[Test]
  public function moreThan50PercentOnReservePlusWorkerLivesOnReserveMakesAllIncomeExempt(): void
  {
    $result = IndigenousEmploymentIncomeExemption::evaluate(
      incomeCents: 10000000,
      workerRegisteredOrEntitled: true,
      onReserveDutyPercent: 51,
      employerResidentOnReserve: false,
      workerLivesOnReserve: true,
    );

    $this->assertTrue($result['fullyExempt']);
    $this->assertSame(10000000, $result['exemptCents']);
    $this->assertSame(0, $result['taxableCents']);
  }

  #[Test]
  public function employerAndWorkerBothOnReserveMakesAllEmploymentIncomeExempt(): void
  {
    $result = IndigenousEmploymentIncomeExemption::evaluate(
      incomeCents: 10000000,
      workerRegisteredOrEntitled: true,
      onReserveDutyPercent: 0,
      employerResidentOnReserve: true,
      workerLivesOnReserve: true,
    );

    $this->assertTrue($result['fullyExempt']);
    $this->assertSame(10000000, $result['exemptCents']);
    $this->assertSame(0, $result['taxableCents']);
    $this->assertSame('guideline_2_employer_and_worker_on_reserve', $result['reason']);
  }

  #[Test]
  public function taxEngineCanBeAppliedToTaxableRemainderAfterProration(): void
  {
    $exemption = IndigenousEmploymentIncomeExemption::evaluate(
      incomeCents: 10000000,
      workerRegisteredOrEntitled: true,
      onReserveDutyPercent: 40,
    );

    $taxOnFullIncome = (new Taxes('Alberta', 2026))->calculateTaxesCents(10000000)['totalDeductions'];
    $taxOnTaxableRemainder = (new Taxes('Alberta', 2026))->calculateTaxesCents($exemption['taxableCents'])['totalDeductions'];

    $this->assertSame(6000000, $exemption['taxableCents']);
    $this->assertLessThan($taxOnFullIncome, $taxOnTaxableRemainder);
    $this->assertGreaterThan(0, $taxOnTaxableRemainder);
  }

  #[Test]
  public function fullyExemptIncomeProducesNoIncomeTaxWhenTaxableRemainderIsUsed(): void
  {
    $exemption = IndigenousEmploymentIncomeExemption::evaluate(
      incomeCents: 9135504,
      workerRegisteredOrEntitled: true,
      onReserveDutyPercent: 90,
    );

    $tax = (new Taxes('Alberta', 2026))->calculateTaxesCents($exemption['taxableCents']);
    $this->assertSame(0, $tax['incomeTax']);
    $this->assertSame(0, $tax['totalDeductions']);
  }
}
