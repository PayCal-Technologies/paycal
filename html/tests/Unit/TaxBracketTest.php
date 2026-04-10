<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Database;
use PayCal\Domain\InvalidArgumentException;
use PayCal\Domain\TaxBracket;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for TaxBracket value object
 *
 * Tests constructor validation, tax calculation, and data conversion
 * without any external dependencies (no Redis, no Database, no config.php).
 */
#[Group('unit')]
final class TaxBracketTest extends TestCase
{
    public function testConstructorDerivesIntegerValues(): void
    {
        $bracket = new TaxBracket(0, 5000000, 1500);
        $this->assertSame(0, $bracket->minIncomeCents);
        $this->assertSame(5000000, $bracket->maxIncomeCents);
        $this->assertSame(1500, $bracket->rateBasisPoints);
    }

    public function testFromCentsCreatesValidBracket(): void
    {
        $bracket = TaxBracket::fromCents(0, 5000000, 1500);

        $this->assertSame(0, $bracket->minIncomeCents);
        $this->assertSame(5000000, $bracket->maxIncomeCents);
        $this->assertSame(1500, $bracket->rateBasisPoints);
    }

    public function testFromCentsRejectsNegativeMinIncome(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Min income cannot be negative');

        TaxBracket::fromCents(-1000, 5000000, 1500);
    }

    public function testFromCentsRejectsMaxLessThanMin(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Max income (4000000) must be greater than min income (5000000)');

        TaxBracket::fromCents(5000000, 4000000, 1500);
    }

    public function testFromCentsRejectsNegativeRate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax rate must be between 0 and 10000 basis points');

        TaxBracket::fromCents(0, 5000000, -500);
    }

    public function testFromCentsRejectsRateOver10000(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax rate must be between 0 and 10000 basis points');

        TaxBracket::fromCents(0, 5000000, 15000);
    }

    public function testCalculateTaxCentsReturnsZeroForIncomeBelowBracket(): void
    {
        $bracket = new TaxBracket(5000000, 10000000, 2000);
        $tax = $bracket->calculateTaxCents(3000000); // $30,000 in cents
        $this->assertSame(0, $tax);
    }

    public function testCalculateTaxCentsReturnsZeroForIncomeAtMinimum(): void
    {
        $bracket = new TaxBracket(5000000, 10000000, 2000);
        $tax = $bracket->calculateTaxCents(5000000); // $50,000 in cents
        $this->assertSame(0, $tax);
    }

    public function testCalculateTaxCentsForIncomeWithinBracket(): void
    {
        $bracket = new TaxBracket(5000000, 10000000, 2000);
        $tax = $bracket->calculateTaxCents(7500000);
        $this->assertSame(500000, $tax);
    }

    public function testCalculateTaxCentsForIncomeAtMaximum(): void
    {
        $bracket = new TaxBracket(5000000, 10000000, 2000);
        $tax = $bracket->calculateTaxCents(10000000);
        $this->assertSame(1000000, $tax);
    }

    public function testCalculateTaxCentsCappedForIncomeAboveBracket(): void
    {
        $bracket = new TaxBracket(5000000, 10000000, 2000);
        $tax = $bracket->calculateTaxCents(15000000);
        $this->assertSame(1000000, $tax);
    }

    public function testToCentsArrayReturnsCorrectStructure(): void
    {
        $bracket = new TaxBracket(5000000, 10000000, 2000);
        $array = $bracket->toCentsArray();
        $this->assertSame([5000000, 10000000, 2000], $array);
    }

    /**
     * Test equivalence across multiple brackets
     */
    // ...existing code...

    /**
     * Test equivalence at bracket boundaries (most sensitive for rounding)
     */
    // ...existing code...

    /**
     * Test equivalence with real-world federal bracket
     */
    // ...existing code...

    /**
     * Test monotonicity: tax never decreases as income increases
     */
    public function testMonotonicityIntegerMethod(): void
    {
        $bracket = new TaxBracket(0, 10000000, 1500);
        $previousTax = 0;
        for ($incomeCents = 0; $incomeCents <= 15000000; $incomeCents += 100000) {
            $tax = $bracket->calculateTaxCents($incomeCents);
            $this->assertGreaterThanOrEqual(
                $previousTax,
                $tax,
                "Tax decreased at income $incomeCents cents"
            );
            $previousTax = $tax;
        }
    }

    /**
     * Test determinism: same input always produces same output
     */
    public function testDeterminismIntegerMethod(): void
    {
        $bracket = new TaxBracket(0, 5000000, 1500);
        $results = [];
        for ($i = 0; $i < 100; $i++) {
            $results[] = $bracket->calculateTaxCents(7500000);
        }
        foreach ($results as $result) {
            $this->assertSame($results[0], $result, 'Integer tax calculation is not deterministic');
        }
    }
}
