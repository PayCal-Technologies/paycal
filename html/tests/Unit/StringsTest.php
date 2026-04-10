<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Strings;
use PHPUnit\Framework\Attributes\Group;

/**
 * StringsTest
 * 
 * Unit tests for Strings class
 * Tests string manipulation, formatting, padding, and date utilities
 */
#[Group('unit')]
final class StringsTest extends TestCase
{
    // =========================================================================
    // padNumber() Tests
    // =========================================================================

    #[Test]
    public function padNumber_withSingleDigit_padsWithZero(): void
    {
        $result = Strings::padNumber(5);
        
        $this->assertSame('05', $result);
    }

    #[Test]
    public function padNumber_withDoubleDigit_returnsUnpadded(): void
    {
        $result = Strings::padNumber(12);
        
        $this->assertSame('12', $result);
    }

    #[Test]
    public function padNumber_withZero_padsWithZero(): void
    {
        $result = Strings::padNumber(0);
        
        $this->assertSame('00', $result);
    }

    #[Test]
    public function padNumber_withCustomLength_padsToLength(): void
    {
        $result = Strings::padNumber(5, 4);
        
        $this->assertSame('0005', $result);
    }

    #[Test]
    public function padNumber_withLargerNumber_doesNotTruncate(): void
    {
        $result = Strings::padNumber(123, 2);
        
        $this->assertSame('123', $result);
    }

    #[Test]
    public function padNumber_withLengthOne_returnsNumber(): void
    {
        $result = Strings::padNumber(7, 1);
        
        $this->assertSame('7', $result);
    }

    // =========================================================================
    // spaces() Tests
    // =========================================================================

    #[Test]
    public function spaces_withPositiveLength_returnsSpaces(): void
    {
        $result = Strings::spaces(5);
        
        $this->assertSame('     ', $result);
        $this->assertSame(5, strlen($result));
    }

    #[Test]
    public function spaces_withZeroLength_returnsSingleSpace(): void
    {
        $result = Strings::spaces(0);
        
        $this->assertSame(' ', $result);
    }

    #[Test]
    public function spaces_withLargeLength_returnsCorrectLength(): void
    {
        $result = Strings::spaces(100);
        
        $this->assertSame(100, strlen($result));
        $this->assertSame(str_repeat(' ', 100), $result);
    }

    // =========================================================================
    // extractPiece() Tests
    // =========================================================================

    #[Test]
    public function extractPiece_withValidIndex_returnsPiece(): void
    {
        $result = Strings::extractPiece('apple:banana:cherry', 1);
        
        $this->assertSame('banana', $result);
    }

    #[Test]
    public function extractPiece_withFirstIndex_returnsFirstPiece(): void
    {
        $result = Strings::extractPiece('apple:banana:cherry', 0);
        
        $this->assertSame('apple', $result);
    }

    #[Test]
    public function extractPiece_withLastIndex_returnsLastPiece(): void
    {
        $result = Strings::extractPiece('apple:banana:cherry', 2);
        
        $this->assertSame('cherry', $result);
    }

    #[Test]
    public function extractPiece_withOutOfBoundsIndex_returnsNull(): void
    {
        $result = Strings::extractPiece('apple:banana:cherry', 10);
        
        $this->assertNull($result);
    }

    #[Test]
    public function extractPiece_withNegativeIndex_returnsNull(): void
    {
        $result = Strings::extractPiece('apple:banana:cherry', -1);
        
        $this->assertNull($result);
    }

    #[Test]
    public function extractPiece_withCustomDelimiter_extractsCorrectly(): void
    {
        $result = Strings::extractPiece('apple,banana,cherry', 1, ',');
        
        $this->assertSame('banana', $result);
    }

    #[Test]
    public function extractPiece_withEmptyDelimiterFallsBackToColon(): void
    {
        $result = Strings::extractPiece('apple:banana:cherry', 0, '');
        
        $this->assertSame('apple', $result);
    }

    #[Test]
    public function extractPiece_withSingleElement_returnsElement(): void
    {
        $result = Strings::extractPiece('apple', 0);
        
        $this->assertSame('apple', $result);
    }

    #[Test]
    public function extractPiece_withEmptyString_returnsEmptyString(): void
    {
        $result = Strings::extractPiece('', 0);
        
        $this->assertSame('', $result);
    }

    // =========================================================================
    // ensureKeysExist() Tests
    // =========================================================================

    #[Test]
    public function ensureKeysExist_withMissingKeys_addsKeysWithZero(): void
    {
        $input = ['a' => 5];
        $result = Strings::ensureKeysExist($input, ['a', 'b', 'c']);
        
        $this->assertArrayHasKey('a', $result);
        $this->assertArrayHasKey('b', $result);
        $this->assertArrayHasKey('c', $result);
        $this->assertSame(5, $result['a']);
        $this->assertSame('0', $result['b']);
        $this->assertSame('0', $result['c']);
    }

    #[Test]
    public function ensureKeysExist_withAllKeysPresent_preservesValues(): void
    {
        $input = ['a' => 10, 'b' => 20, 'c' => 30];
        $result = Strings::ensureKeysExist($input, ['a', 'b', 'c']);
        
        $this->assertSame(['a' => 10, 'b' => 20, 'c' => 30], $result);
    }

    #[Test]
    public function ensureKeysExist_withEmptyStringValues_replacesWithZero(): void
    {
        $input = ['a' => '', 'b' => 'value'];
        $result = Strings::ensureKeysExist($input, ['a', 'b']);
        
        $this->assertSame('0', $result['a']);
        $this->assertSame('value', $result['b']);
    }

    #[Test]
    public function ensureKeysExist_withEmptyArray_addsAllKeys(): void
    {
        $result = Strings::ensureKeysExist([], ['x', 'y', 'z']);
        
        $this->assertSame(['x' => '0', 'y' => '0', 'z' => '0'], $result);
    }

    #[Test]
    public function ensureKeysExist_withNoKeysToEnsure_returnsOriginal(): void
    {
        $input = ['a' => 5, 'b' => 10];
        $result = Strings::ensureKeysExist($input, []);
        
        $this->assertSame($input, $result);
    }

    // =========================================================================
    // getOrdinal() Tests
    // =========================================================================

    #[Test]
    #[DataProvider('ordinalProvider')]
    public function getOrdinal_withVariousDays_returnsCorrectSuffix(int $day, string $expected): void
    {
        $result = Strings::getOrdinal($day);
        
        $this->assertSame($expected, $result);
    }

    public static function ordinalProvider(): array
    {
        return [
            '1st' => [1, 'st'],
            '2nd' => [2, 'nd'],
            '3rd' => [3, 'rd'],
            '4th' => [4, 'th'],
            '5th' => [5, 'th'],
            '10th' => [10, 'th'],
            '11th' => [11, 'th'],  // Special case
            '12th' => [12, 'th'],  // Special case
            '13th' => [13, 'th'],  // Special case
            '14th' => [14, 'th'],
            '20th' => [20, 'th'],
            '21st' => [21, 'st'],
            '22nd' => [22, 'nd'],
            '23rd' => [23, 'rd'],
            '24th' => [24, 'th'],
            '30th' => [30, 'th'],
            '31st' => [31, 'st'],
            '111th' => [111, 'th'], // Special case in hundreds
            '112th' => [112, 'th'], // Special case in hundreds
            '113th' => [113, 'th'], // Special case in hundreds
            '121st' => [121, 'st'],
            '122nd' => [122, 'nd'],
            '123rd' => [123, 'rd'],
        ];
    }

    // =========================================================================
    // getOrdinalSpoken() Tests
    // =========================================================================

    #[Test]
    #[DataProvider('ordinalSpokenProvider')]
    public function getOrdinalSpoken_withVariousDays_returnsCorrectWord(int $day, string $expected): void
    {
        $result = Strings::getOrdinalSpoken($day);
        
        $this->assertSame($expected, $result);
    }

    public static function ordinalSpokenProvider(): array
    {
        return [
            '1' => [1, 'first'],
            '2' => [2, 'second'],
            '3' => [3, 'third'],
            '4' => [4, 'fourth'],
            '5' => [5, 'fifth'],
            '10' => [10, 'tenth'],
            '11' => [11, 'eleventh'],
            '12' => [12, 'twelfth'],
            '13' => [13, 'thirteenth'],
            '15' => [15, 'fifteenth'],
            '20' => [20, 'twentieth'],
            '21' => [21, 'twenty-first'],
            '22' => [22, 'twenty-second'],
            '23' => [23, 'twenty-third'],
            '30' => [30, 'thirtieth'],
            '31' => [31, 'thirty-first'],
        ];
    }

    #[Test]
    public function getOrdinalSpoken_withInvalidDay_returnsNumberString(): void
    {
        $result = Strings::getOrdinalSpoken(32);
        
        $this->assertSame('32', $result);
    }

    #[Test]
    public function getOrdinalSpoken_withZero_returnsNumberString(): void
    {
        $result = Strings::getOrdinalSpoken(0);
        
        $this->assertSame('0', $result);
    }

    // =========================================================================
    // formatDateAria() Tests
    // =========================================================================

    #[Test]
    public function formatDateAria_withLongFormat_returnsFullDate(): void
    {
        $result = Strings::formatDateAria('2025-02-15', 'long');
        
        $this->assertStringContainsString('February', $result);
        $this->assertStringContainsString('15', $result);
        $this->assertStringContainsString('2025', $result);
    }

    #[Test]
    public function formatDateAria_withShortFormat_omitsDayOfWeek(): void
    {
        $result = Strings::formatDateAria('2025-02-15', 'short');
        
        $this->assertStringNotContainsString('Saturday', $result);
        $this->assertStringContainsString('February', $result);
        $this->assertStringContainsString('15', $result);
        $this->assertStringNotContainsString('2025', $result);
    }

    #[Test]
    public function formatDateAria_withNumberFormat_returnsOnlyDay(): void
    {
        $result = Strings::formatDateAria('2025-02-15', 'number');
        
        $this->assertSame('15', $result);
    }

    #[Test]
    public function formatDateAria_withDefaultFormat_usesLong(): void
    {
        $result = Strings::formatDateAria('2025-02-15');
        
        $this->assertStringContainsString('February', $result);
        $this->assertStringContainsString('15', $result);
        $this->assertStringContainsString('2025', $result);
    }

    #[Test]
    public function formatDateAria_withFirstOfMonth_returnsFirst(): void
    {
        $result = Strings::formatDateAria('2025-03-01', 'short');
        
        $this->assertStringContainsString('March', $result);
        $this->assertStringContainsString('1', $result);
    }

    // =========================================================================
    // formatFulldateWithOrdinal() Tests
    // =========================================================================

    #[Test]
    public function formatFulldateWithOrdinal_withValidDate_returnsFormattedDate(): void
    {
        $result = Strings::formatFulldateWithOrdinal('2025-02-15');
        
        $this->assertStringContainsString('Saturday', $result);
        $this->assertStringContainsString('February', $result);
        $this->assertStringContainsString('15th', $result);
        $this->assertStringContainsString('2025', $result);
    }

    #[Test]
    public function formatFulldateWithOrdinal_withFirstDay_uses1st(): void
    {
        $result = Strings::formatFulldateWithOrdinal('2025-01-01');
        
        $this->assertStringContainsString('1st', $result);
    }

    #[Test]
    public function formatFulldateWithOrdinal_withSecondDay_uses2nd(): void
    {
        $result = Strings::formatFulldateWithOrdinal('2025-01-02');
        
        $this->assertStringContainsString('2nd', $result);
    }

    #[Test]
    public function formatFulldateWithOrdinal_withThirdDay_uses3rd(): void
    {
        $result = Strings::formatFulldateWithOrdinal('2025-01-03');
        
        $this->assertStringContainsString('3rd', $result);
    }

    #[Test]
    public function formatFulldateWithOrdinal_withEleventhDay_uses11th(): void
    {
        $result = Strings::formatFulldateWithOrdinal('2025-01-11');
        
        $this->assertStringContainsString('11th', $result);
    }

    #[Test]
    public function formatFulldateWithOrdinal_withTwentyFirstDay_uses21st(): void
    {
        $result = Strings::formatFulldateWithOrdinal('2025-01-21');
        
        $this->assertStringContainsString('21st', $result);
    }

    #[Test]
    public function formatFulldateWithOrdinal_withLastDayOfMonth_uses31st(): void
    {
        $result = Strings::formatFulldateWithOrdinal('2025-01-31');
        
        $this->assertStringContainsString('31st', $result);
        $this->assertStringContainsString('January', $result);
    }
}
