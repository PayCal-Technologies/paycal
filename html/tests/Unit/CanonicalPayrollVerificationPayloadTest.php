<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Earnings;
use PayCal\Domain\Enums\PayFrequency;
use PayCal\Domain\PayPeriods;
use PHPUnit\Framework\Attributes\Group;

/**
 * CanonicalPayrollVerificationPayloadTest
 */
#[Group('unit')]
final class CanonicalPayrollVerificationPayloadTest extends TestCase
{
  private function makePeriod(): PayPeriods
  {
    return PayPeriods::fromDate('2026-02-01', PayFrequency::BIWEEKLY, 'Monday', null, 'America/Edmonton');
  }

  public function testPayloadStructureAndOrder(): void
  {
    $period = $this->makePeriod();
    $payload = Earnings::buildCanonicalVerificationPayload(
      $period,
      'employee-uuid-123',
      'CA-AB',
      '2026.1',
      '1.015.000',
      123456,
      18567,
      104889
    );

    $expectedKeys = ['v', 'scope', 'period', 'employeeId', 'jurisdiction', 'bracketVersion', 'engineVersion', 'grossCents', 'taxCents', 'netCents', 'signingKeyVersion'];
    $this->assertSame($expectedKeys, array_keys($payload));
    $this->assertSame('pay_period', $payload['scope']);
    $this->assertIsArray($payload['period']);
  }

  public function testSerializedPayloadDeterministic(): void
  {
    $period = $this->makePeriod();
    $payload = Earnings::buildCanonicalVerificationPayload(
      $period,
      'employee-uuid-123',
      'CA-AB',
      '2026.1',
      '1.015.000',
      123456,
      18567,
      104889
    );

    $serializedA = Earnings::serializeVerificationPayload($payload);
    $serializedB = Earnings::serializeVerificationPayload($payload);
    $this->assertSame($serializedA, $serializedB);
  }
}
