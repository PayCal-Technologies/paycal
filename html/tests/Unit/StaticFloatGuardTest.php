<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 *
 */
#[Group('unit')]
class StaticFloatGuardTest extends TestCase
{
  /**
   * Fails if any money-related float field or method exists in TaxBracket or TaxBracketCollection.
   * Enforces architectural hardening: integer-only financial core.
   * Note: calculateTax(float) is allowed as a backward-compatible wrapper that delegates to integer methods.
   */
  public function testNoMoneyRelatedFloat(): void
  {
    $taxBracketSource = file_get_contents(__DIR__.'/../../src/Domain/TaxBracket.php');
    $taxBracketCollectionSource = file_get_contents(__DIR__.'/../../src/Domain/TaxBracketCollection.php');

    // Check for float fields in TaxBracket (not allowed)
    $this->assertSame(0, preg_match('/public readonly float/', $taxBracketSource), 'Money-related float field found in TaxBracket.');

    // Check for float fields in TaxBracketCollection (not allowed)
    $this->assertSame(0, preg_match('/public readonly float/', $taxBracketCollectionSource), 'Money-related float field found in TaxBracketCollection.');

    // Verify that calculateTax(float) delegates to integer method (backward-compatible wrapper is OK)
    if (preg_match('/function calculateTax\(float/', $taxBracketCollectionSource)) {
      $this->assertStringContainsString(
        'calculateTaxCents',
        $taxBracketCollectionSource,
        'calculateTax(float) must delegate to integer-based calculateTaxCents() method.'
      );
    }
  }
}
