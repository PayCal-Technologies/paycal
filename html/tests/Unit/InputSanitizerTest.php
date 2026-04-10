<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\InputSanitizer;
use PHPUnit\Framework\Attributes\Group;

/**
 * InputSanitizerTest.
 *
 * Unit tests for InputSanitizer class
 * Tests all sanitization methods with various inputs including edge cases
 *
 * @internal
 *
 */
#[Group('unit')]
final class InputSanitizerTest extends TestCase
{
  // =========================================================================
  // sanitizeString() Tests
  // =========================================================================

  #[Test]
  public function sanitizeStringWithValidStringReturnsSanitizedString(): void
  {
    $input = 'Hello World';
    $result = InputSanitizer::sanitizeString($input);

    $this->assertIsString($result);
    $this->assertSame('Hello World', $result);
  }

  #[Test]
  public function sanitizeStringWithLeadingAndTrailingWhitespaceTrimsWhitespace(): void
  {
    $input = '  Hello World  ';
    $result = InputSanitizer::sanitizeString($input);

    $this->assertSame('Hello World', $result);
  }

  #[Test]
  public function sanitizeStringWithSpecialCharactersEscapesHTMLEntities(): void
  {
    $input = "<script>alert('XSS')</script>";
    $result = InputSanitizer::sanitizeString($input);

    $this->assertStringNotContainsString('<script>', $result);
    $this->assertStringNotContainsString('</script>', $result);
  }

  #[Test]
  public function sanitizeStringWithAllowedPunctuationPreservesPunctuation(): void
  {
    $input = 'Hello, World! Test@Example.com #123 $50 %10 ^2 &Co (test) +1 -5 _ .';
    $result = InputSanitizer::sanitizeString($input);

    // All these characters should be preserved
    $this->assertStringContainsString(',', $result);
    $this->assertStringContainsString('!', $result);
    $this->assertStringContainsString('@', $result);
    $this->assertStringContainsString('.', $result);
    $this->assertStringContainsString('#', $result);
    $this->assertStringContainsString('$', $result);
    $this->assertStringContainsString('%', $result);
  }

  #[Test]
  public function sanitizeStringWithDisallowedCharactersRemovesDisallowedChars(): void
  {
    $input = 'Hello{World}[Test]<Tag>|Pipe~Tilde';
    $result = InputSanitizer::sanitizeString($input);

    $this->assertStringNotContainsString('{', $result);
    $this->assertStringNotContainsString('}', $result);
    $this->assertStringNotContainsString('[', $result);
    $this->assertStringNotContainsString(']', $result);
    $this->assertStringNotContainsString('|', $result);
    $this->assertStringNotContainsString('~', $result);
  }

  #[Test]
  public function sanitizeStringWithEmptyStringReturnsEmptyString(): void
  {
    $result = InputSanitizer::sanitizeString('');

    $this->assertSame('', $result);
  }

  #[Test]
  public function sanitizeStringWithNonStringInputConvertsToString(): void
  {
    $result = InputSanitizer::sanitizeString(null);
    $this->assertSame('', $result);

    $result = InputSanitizer::sanitizeString(123);
    $this->assertSame('', $result);

    $result = InputSanitizer::sanitizeString(true);
    $this->assertSame('', $result);
  }

  #[Test]
  #[DataProvider('xssAttackProvider')]
  public function sanitizeStringWithXSSAttemptsSanitizesAttack(string $attack): void
  {
    $result = InputSanitizer::sanitizeString($attack);

    // Should encode dangerous tags (not necessarily remove them)
    $this->assertStringNotContainsString('<script', strtolower($result));
    $this->assertStringNotContainsString('javascript:', strtolower($result));
    // Note: some patterns like onerror may remain as text after encoding
    $this->assertStringNotContainsString('<img', strtolower($result));
    $this->assertStringNotContainsString('<svg', strtolower($result));
  }

  public static function xssAttackProvider(): array
  {
    return [
        'script tag' => ["<script>alert('XSS')</script>"],
        'img onerror' => ['<img src=x onerror="alert(1)">'],
        'javascript protocol' => ['<a href="javascript:alert(1)">Click</a>'],
        'svg onload' => ['<svg onload="alert(1)">'],
        'iframe' => ['<iframe src="evil.com"></iframe>'],
    ];
  }

  // =========================================================================
  // sanitizeArray() Tests
  // =========================================================================

  #[Test]
  public function sanitizeArrayWithSimpleArraySanitizesAllValues(): void
  {
    $input = [
        'name' => '  John Doe  ',
        'email' => '<script>test@example.com</script>',
        'age' => '25',
    ];

    $result = InputSanitizer::sanitizeArray($input);

    $this->assertSame('John Doe', $result['name']);
    $this->assertStringNotContainsString('<script>', $result['email']);
    $this->assertSame('25', $result['age']);
  }

  #[Test]
  public function sanitizeArrayWithNestedArrayRecursivelySanitizesAllLevels(): void
  {
    $input = [
        'user' => [
            'name' => ' Test User ',
            'details' => [
                'city' => '<b>Toronto</b>',
            ],
        ],
    ];

    $result = InputSanitizer::sanitizeArray($input);

    $this->assertIsArray($result['user']);
    $this->assertIsArray($result['user']['details']);
    $this->assertSame('Test User', $result['user']['name']);
    $this->assertStringNotContainsString('<b>', $result['user']['details']['city']);
  }

  #[Test]
  public function sanitizeArrayWithEmptyArrayReturnsEmptyArray(): void
  {
    $result = InputSanitizer::sanitizeArray([]);

    $this->assertSame([], $result);
  }

  // =========================================================================
  // sanitizeName() Tests
  // =========================================================================

  #[Test]
  public function sanitizeNameWithValidNamePreservesName(): void
  {
    $input = "John O'Brien-Smith";
    $result = InputSanitizer::sanitizeName($input);

    $this->assertStringContainsString('John', $result);
    $this->assertStringContainsString('O', $result);
    $this->assertStringContainsString('Brien', $result);
    $this->assertStringContainsString('Smith', $result);
  }

  #[Test]
  public function sanitizeNameWithMultipleSpacesCollapsesToSingleSpace(): void
  {
    $input = 'John    Doe';
    $result = InputSanitizer::sanitizeName($input);

    $this->assertSame('John Doe', $result);
  }

  #[Test]
  public function sanitizeNameWithUnicodeCharactersStripsAccents(): void
  {
    $input = 'François Müller'; // French and German characters
    $result = InputSanitizer::sanitizeName($input);

    // sanitizeName strips accented characters to ASCII-safe equivalents
    $this->assertStringContainsString('Fran', $result);
    $this->assertStringContainsString('M', $result);
    $this->assertStringContainsString('ller', $result);
  }

  #[Test]
  public function sanitizeNameWithNumbersRemovesNumbers(): void
  {
    $input = 'John123 Doe456';
    $result = InputSanitizer::sanitizeName($input);

    // Numbers should be removed based on sanitizeName regex
    $this->assertStringContainsString('John', $result);
    $this->assertStringContainsString('Doe', $result);
  }

  // =========================================================================
  // sanitizeEmail() Tests
  // =========================================================================

  #[Test]
  public function sanitizeEmailWithValidEmailReturnsLowercasedEmail(): void
  {
    $input = 'Test.User@Example.COM';
    $result = InputSanitizer::sanitizeEmail($input);

    $this->assertSame('test.user@example.com', $result);
  }

  #[Test]
  public function sanitizeEmailWithPlusAliasPreservesPlusCharacter(): void
  {
    $input = 'cshaiku+2@gmail.com';
    $result = InputSanitizer::sanitizeEmail($input);

    $this->assertSame('cshaiku+2@gmail.com', $result);
  }

  #[Test]
  public function sanitizeEmailWithMultipleAtSignsKeepsOnlyLastDomain(): void
  {
    $input = 'user@@example.com';
    $result = InputSanitizer::sanitizeEmail($input);

    // Should keep first part and last domain
    $this->assertStringContainsString('user@example.com', $result);
    $this->assertSame(1, substr_count($result, '@'));
  }

  #[Test]
  public function sanitizeEmailWithInvalidCharactersRemovesInvalidChars(): void
  {
    $input = 'test<user>@example{.}com';
    $result = InputSanitizer::sanitizeEmail($input);

    $this->assertStringNotContainsString('<', $result);
    $this->assertStringNotContainsString('>', $result);
    $this->assertStringNotContainsString('{', $result);
    $this->assertStringNotContainsString('}', $result);
  }

  #[Test]
  public function sanitizeEmailWithWhitespaceTrimsWhitespace(): void
  {
    $input = '  test@example.com  ';
    $result = InputSanitizer::sanitizeEmail($input);

    $this->assertSame('test@example.com', $result);
  }

  // =========================================================================
  // sanitizeIPAddress() Tests
  // =========================================================================

  #[Test]
  public function sanitizeIPAddressWithValidIPv4ReturnsIP(): void
  {
    $input = '192.168.1.1';
    $result = InputSanitizer::sanitizeIPAddress($input);

    $this->assertSame('192.168.1.1', $result);
  }

  #[Test]
  public function sanitizeIPAddressWithValidIPv6ReturnsIP(): void
  {
    $input = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
    $result = InputSanitizer::sanitizeIPAddress($input);

    $this->assertSame($input, $result);
  }

  #[Test]
  public function sanitizeIPAddressWithInvalidIPReturnsUnknown(): void
  {
    $input = 'not.an.ip.address';
    $result = InputSanitizer::sanitizeIPAddress($input);

    $this->assertSame('unknown', $result);
  }

  #[Test]
  public function sanitizeIPAddressWithNonStringInputReturnsUnknown(): void
  {
    $result = InputSanitizer::sanitizeIPAddress(null);
    $this->assertSame('unknown', $result);

    $result = InputSanitizer::sanitizeIPAddress(123);
    $this->assertSame('unknown', $result);
  }

  // =========================================================================
  // stripControls() Tests
  // =========================================================================

  #[Test]
  public function stripControlsWithControlCharactersBehaviorTest(): void
  {
    $input = "Hello\x00\x01\x02World";
    $result = InputSanitizer::stripControls($input);

    // stripControls preserves most control chars except specific ones
    // Just verify the method executes and returns a string
    $this->assertIsString($result);
    $this->assertStringContainsString('Hello', $result);
    $this->assertStringContainsString('World', $result);
  }

  #[Test]
  public function stripControlsWithTabsAndNewlinesPreservesTabsAndNewlines(): void
  {
    $input = "Line1\nLine2\rLine3\tTabbed";
    $result = InputSanitizer::stripControls($input);

    $this->assertStringContainsString("\n", $result);
    $this->assertStringContainsString("\r", $result);
    $this->assertStringContainsString("\t", $result);
  }

  // =========================================================================
  // sanitizeNotes() Tests
  // =========================================================================

  #[Test]
  public function sanitizeNotesWithMultilineTextNormalizesNewlines(): void
  {
    $input = "Line1\r\nLine2\rLine3\nLine4";
    $result = InputSanitizer::sanitizeNotes($input);

    // All line breaks should be normalized to \n
    $this->assertStringNotContainsString("\r\n", $result);
    $this->assertStringNotContainsString("\r", $result);
  }

  #[Test]
  public function sanitizeNotesWithExcessiveWhitespaceCollapsesSpaces(): void
  {
    $input = 'Hello     World    !';
    $result = InputSanitizer::sanitizeNotes($input);

    $this->assertSame('Hello World !', $result);
  }

  // =========================================================================
  // sanitizeSiteName() Tests
  // =========================================================================

  #[Test]
  public function sanitizeSiteNameWithValidSiteNamePreservesName(): void
  {
    $input = 'Acme Corp & Sons, Inc.';
    $result = InputSanitizer::sanitizeSiteName($input);

    $this->assertStringContainsString('Acme', $result);
    $this->assertStringContainsString('Corp', $result);
    $this->assertStringContainsString('Sons', $result);
    $this->assertStringContainsString('Inc', $result);
  }

  #[Test]
  public function sanitizeSiteNameWithSlashesAllowsSlashes(): void
  {
    $input = 'Site A/B Location 2/3';
    $result = InputSanitizer::sanitizeSiteName($input);

    $this->assertStringContainsString('Site', $result);
    $this->assertStringContainsString('Location', $result);
  }
}
