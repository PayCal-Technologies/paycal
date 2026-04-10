<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Earnings;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 *
 */
#[Group('unit')]
final class PayrollVerificationPayloadTest extends TestCase
{
  public function testDeterministicHashIsStable(): void
  {
    $payload = Earnings::buildVerificationPayload(
      '2026-02-01:2026-02-15',
      'employee-uuid-123',
      123456,
      18567,
      104889,
      'CA-AB',
      '2026.1',
      '1.015.000'
    );
    $serialized = Earnings::serializeCanonicalPayload($payload);
    $hash1 = Earnings::hashPayload($serialized);
    $hash2 = Earnings::hashPayload($serialized);
    $this->assertSame($hash1, $hash2, 'Hash must be stable across runs');
    $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash1, 'Hash must be 64-char lowercase hex');
  }

  public function testHashChangesWhenCentChanges(): void
  {
    $payload1 = Earnings::buildVerificationPayload('2026-02-01:2026-02-15', 'employee-uuid-123', 123456, 18567, 104889, 'CA-AB', '2026.1', '1.015.000');
    $payload2 = Earnings::buildVerificationPayload('2026-02-01:2026-02-15', 'employee-uuid-123', 123457, 18567, 104889, 'CA-AB', '2026.1', '1.015.000');
    $hash1 = Earnings::hashPayload(Earnings::serializeCanonicalPayload($payload1));
    $hash2 = Earnings::hashPayload(Earnings::serializeCanonicalPayload($payload2));
    $this->assertNotSame($hash1, $hash2, 'Hash must change if grossCents changes');
  }

  public function testHashChangesWhenBracketVersionChanges(): void
  {
    $payload1 = Earnings::buildVerificationPayload('2026-02-01:2026-02-15', 'employee-uuid-123', 123456, 18567, 104889, 'CA-AB', '2026.1', '1.015.000');
    $payload2 = Earnings::buildVerificationPayload('2026-02-01:2026-02-15', 'employee-uuid-123', 123456, 18567, 104889, 'CA-AB', '2027.1', '1.015.000');
    $hash1 = Earnings::hashPayload(Earnings::serializeCanonicalPayload($payload1));
    $hash2 = Earnings::hashPayload(Earnings::serializeCanonicalPayload($payload2));
    $this->assertNotSame($hash1, $hash2, 'Hash must change if bracketVersion changes');
  }

  public function testHashIsStableAcrossEnvironments(): void
  {
    $payload = Earnings::buildVerificationPayload('2026-02-01:2026-02-15', 'employee-uuid-123', 123456, 18567, 104889, 'CA-AB', '2026.1', '1.015.000');
    $serialized = Earnings::serializeCanonicalPayload($payload);
    $hash = Earnings::hashPayload($serialized);
    $expected = 'f9a8c1b...'; // Replace with actual hash after first run for snapshot freeze
    $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    // Uncomment and set after first run:
    // $this->assertSame($expected, $hash, 'Hash must be stable across environments');
  }

  public function testPayloadContractShapeAndOrder(): void
  {
    $payload = Earnings::buildVerificationPayload('2026-02-01:2026-02-15', 'employee-uuid-123', 123456, 18567, 104889, 'CA-AB', '2026.1', '1.015.000');
    $expectedKeys = ['v', 'periodId', 'employeeId', 'grossCents', 'taxCents', 'netCents', 'jurisdiction', 'bracketVersion', 'engineVersion'];
    $this->assertSame($expectedKeys, array_keys($payload), 'Payload keys and order must not change');
    $this->assertIsInt($payload['grossCents']);
    $this->assertIsInt($payload['taxCents']);
    $this->assertIsInt($payload['netCents']);
    $this->assertIsString($payload['periodId']);
    $this->assertIsString($payload['employeeId']);
    $this->assertIsString($payload['jurisdiction']);
    $this->assertIsString($payload['bracketVersion']);
    $this->assertIsString($payload['engineVersion']);
  }
}
