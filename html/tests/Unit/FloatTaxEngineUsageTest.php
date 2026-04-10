<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\TaxBracket;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 *
 */
#[Group('unit')]
final class FloatTaxEngineUsageTest extends TestCase
{
  public function testFloatCalculationMatchesExpected(): void
  {
    // TaxBracket uses basis points (1500 = 15%), not decimal rates
    // minIncome, maxIncome in cents, rate in basis points
    $bracket = new TaxBracket(0, 5000000, 1500); // 15% rate
    $incomeCents = 1234567; // $12,345.67
    $expected = ($incomeCents * 1500) / 10000; // Calculate tax
    $actual = $bracket->calculateTaxCents($incomeCents);
    $this->assertEqualsWithDelta($expected, $actual, 1); // Allow 1 cent rounding
  }

  public function testMultipleIncomeLevels(): void
  {
    $bracket = new TaxBracket(0, 10000000, 2000); // 20% rate
    $incomeLevels = [0, 100050, 5000075, 9999999]; // Income in cents

    foreach ($incomeLevels as $incomeCents) {
      $expected = ($incomeCents * 2000) / 10000;
      $actual = $bracket->calculateTaxCents($incomeCents);
      $this->assertEqualsWithDelta($expected, $actual, 1); // Allow 1 cent rounding
    }
  }
}
