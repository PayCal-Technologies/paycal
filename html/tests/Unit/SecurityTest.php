<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Security;
use PHPUnit\Framework\Attributes\Group;

/**
 * SecurityTest.
 *
 * Unit tests for Security class
 * Tests verification code generation and IP address detection
 *
 * @internal
 *
 */
#[Group('unit')]
final class SecurityTest extends TestCase
{
  // =========================================================================
  // generateVerificationCode() Tests
  // =========================================================================

  #[Test]
  public function generateVerificationCodeWithDefaultLengthReturnsCorrectLength(): void
  {
    $code = Security::generateVerificationCode(6);

    $this->assertSame(6, strlen($code));
  }

  #[Test]
  public function generateVerificationCodeWithVariousLengthsReturnsCorrectLength(): void
  {
    $lengths = [1, 4, 6, 8, 10, 16, 32];

    foreach ($lengths as $length) {
      $code = Security::generateVerificationCode($length);
      $this->assertSame($length, strlen($code), "Code length should be {$length}");
    }
  }

  #[Test]
  public function generateVerificationCodeWithZeroLengthReturnsEmptyString(): void
  {
    $code = Security::generateVerificationCode(0);

    $this->assertSame('', $code);
    $this->assertSame(0, strlen($code));
  }

  #[Test]
  public function generateVerificationCodeOnlyContainsValidCharacters(): void
  {
    $validChars = 'ABCDEFGHJKLMNPQRTUWXYZ346789'; // PC_VERIFICATION_SET

    for ($i = 0; $i < 10; ++$i) {
      $code = Security::generateVerificationCode(20);

      // Every character should be in the valid set
      for ($j = 0; $j < strlen($code); ++$j) {
        $char = $code[$j];
        $this->assertStringContainsString(
          $char,
          $validChars,
          "Character '{$char}' should be in valid set"
        );
      }
    }
  }

  #[Test]
  public function generateVerificationCodeIsAlwaysUppercase(): void
  {
    for ($i = 0; $i < 20; ++$i) {
      $code = Security::generateVerificationCode(10);

      $this->assertSame(
        strtoupper($code),
        $code,
        'Code should be uppercase'
      );
    }
  }

  #[Test]
  public function generateVerificationCodeIsString(): void
  {
    $code = Security::generateVerificationCode(8);

    $this->assertIsString($code);
  }

  #[Test]
  public function generateVerificationCodeExcludesConfusingCharacters(): void
  {
    $excludedChars = ['I', 'O', 'S', 'V', '0', '1', '2', '5']; // Easily confused

    for ($i = 0; $i < 50; ++$i) {
      $code = Security::generateVerificationCode(20);

      foreach ($excludedChars as $char) {
        $this->assertStringNotContainsString(
          $char,
          $code,
          "Code should not contain confusing character '{$char}'"
        );
      }
    }
  }

  #[Test]
  public function generateVerificationCodeGeneratesRandomCodes(): void
  {
    $codes = [];

    // Generate 100 codes
    for ($i = 0; $i < 100; ++$i) {
      $codes[] = Security::generateVerificationCode(8);
    }

    // All codes should be unique (statistically very likely)
    $uniqueCodes = array_unique($codes);

    $this->assertGreaterThan(
      95,
      count($uniqueCodes),
      'At least 95 out of 100 codes should be unique'
    );
  }

  #[Test]
  public function generateVerificationCodeMultipleCallsProduceDifferentResults(): void
  {
    $code1 = Security::generateVerificationCode(10);
    $code2 = Security::generateVerificationCode(10);
    $code3 = Security::generateVerificationCode(10);

    // While it's theoretically possible they could match, it's statistically unlikely
    $allSame = ($code1 === $code2 && $code2 === $code3);

    $this->assertFalse(
      $allSame,
      'Three consecutive codes should not all be identical'
    );
  }

  #[Test]
  #[DataProvider('codeLengthProvider')]
  public function generateVerificationCodeWithDataProviderGeneratesCorrectLength(int $length): void
  {
    $code = Security::generateVerificationCode($length);

    $this->assertSame($length, strlen($code));
  }

  public static function codeLengthProvider(): array
  {
    return [
        'zero' => [0],
        'one' => [1],
        'four' => [4],
        'six' => [6],
        'eight' => [8],
        'twelve' => [12],
        'sixteen' => [16],
    ];
  }

  #[Test]
  public function generateVerificationCodeWithLength1ReturnsSingleCharacter(): void
  {
    $code = Security::generateVerificationCode(1);

    $this->assertSame(1, strlen($code));
    $this->assertMatchesRegularExpression('/^[A-Z0-9]$/', $code);
  }

  #[Test]
  public function generateVerificationCodeOnlyUsesAllowedCharacterSet(): void
  {
    $allowedPattern = '/^[ABCDEFGHJKLMNPQRTUWXYZ346789]+$/';

    for ($i = 0; $i < 20; ++$i) {
      $code = Security::generateVerificationCode(15);

      $this->assertMatchesRegularExpression(
        $allowedPattern,
        $code,
        "Code '{$code}' should only contain allowed characters"
      );
    }
  }

  #[Test]
  public function generateVerificationCodeStatisticallyUsesAllCharacters(): void
  {
    $allChars = [];

    // Generate many codes to get good distribution
    for ($i = 0; $i < 500; ++$i) {
      $code = Security::generateVerificationCode(10);
      $chars = str_split($code);
      $allChars = array_merge($allChars, $chars);
    }

    $uniqueChars = array_unique($allChars);

    // Should see most characters from the set (at least 20 out of 28)
    $this->assertGreaterThanOrEqual(
      20,
      count($uniqueChars),
      'Should see most characters from the verification set'
    );
  }

  #[Test]
  public function generateVerificationCodeWithLargeLengthHandlesCorrectly(): void
  {
    $code = Security::generateVerificationCode(100);

    $this->assertSame(100, strlen($code));
    $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRTUWXYZ346789]+$/', $code);
  }

  #[Test]
  public function generateVerificationCodeIsNotPredictable(): void
  {
    $codes = [];

    for ($i = 0; $i < 10; ++$i) {
      $codes[] = Security::generateVerificationCode(6);
    }

    // No code should be repeated (very unlikely with 6 chars)
    $uniqueCodes = array_unique($codes);

    $this->assertSame(
      count($codes),
      count($uniqueCodes),
      'All generated codes should be unique'
    );
  }

  #[Test]
  public function generateVerificationCodeTypicalUseCaseSixDigitsWorks(): void
  {
    $code = Security::generateVerificationCode(6);

    $this->assertSame(6, strlen($code));
    $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $code);
  }

  #[Test]
  public function generateVerificationCodeDoesNotContainLowercaseLetters(): void
  {
    for ($i = 0; $i < 20; ++$i) {
      $code = Security::generateVerificationCode(10);

      $this->assertDoesNotMatchRegularExpression(
        '/[a-z]/',
        $code,
        'Code should not contain lowercase letters'
      );
    }
  }

  // =========================================================================
  // getVisitorRealIPAddress() Tests
  // =========================================================================

  #[Test]
  public function getVisitorRealIPAddressReturnsString(): void
  {
    $ip = Security::getVisitorRealIPAddress();

    $this->assertIsString($ip);
  }

  #[Test]
  public function getVisitorRealIPAddressWithRemoteAddrReturnsSanitizedIP(): void
  {
    // Set up test environment
    $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
    unset($_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR']);

    $ip = Security::getVisitorRealIPAddress();

    $this->assertSame('192.168.1.100', $ip);
  }

  #[Test]
  public function getVisitorRealIPAddressWithClientIPPrefersClientIP(): void
  {
    $_SERVER['HTTP_CLIENT_IP'] = '203.0.113.50';
    $_SERVER['REMOTE_ADDR'] = '192.168.1.100';

    $ip = Security::getVisitorRealIPAddress();

    $this->assertSame('203.0.113.50', $ip);
  }

  #[Test]
  public function getVisitorRealIPAddressWithForwardedForUsesForwardedIP(): void
  {
    unset($_SERVER['HTTP_CLIENT_IP']);
    $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.25';
    $_SERVER['REMOTE_ADDR'] = '192.168.1.100';

    $ip = Security::getVisitorRealIPAddress();

    $this->assertSame('198.51.100.25', $ip);
  }

  #[Test]
  public function getVisitorRealIPAddressWithNoServerVarsReturnsUnknown(): void
  {
    unset($_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['REMOTE_ADDR']);

    $ip = Security::getVisitorRealIPAddress();

    $this->assertSame('unknown', $ip);
  }

  #[Test]
  public function getVisitorRealIPAddressWithInvalidIPReturnsSanitized(): void
  {
    unset($_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR']);

    $_SERVER['REMOTE_ADDR'] = 'not.a.valid.ip';

    $ip = Security::getVisitorRealIPAddress();

    // InputSanitizer::sanitizeIPAddress returns 'unknown' for invalid IPs
    $this->assertSame('unknown', $ip);
  }

  #[Test]
  public function getVisitorRealIPAddressWithIPv6HandlesProperly(): void
  {
    unset($_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR']);

    $_SERVER['REMOTE_ADDR'] = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';

    $ip = Security::getVisitorRealIPAddress();

    $this->assertSame('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $ip);
  }

  #[Test]
  public function getVisitorRealIPAddressPriorityOrderClientIPFirst(): void
  {
    // When all three are set, HTTP_CLIENT_IP should take priority
    $_SERVER['HTTP_CLIENT_IP'] = '203.0.113.1';
    $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.2';
    $_SERVER['REMOTE_ADDR'] = '203.0.113.3';

    $ip = Security::getVisitorRealIPAddress();

    $this->assertSame('203.0.113.1', $ip);
  }

  #[Test]
  public function getVisitorRealIPAddressCallsSanitizer(): void
  {
    $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
    unset($_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR']);

    $ip = Security::getVisitorRealIPAddress();

    // The result should be sanitized (which for a valid IP returns the IP)
    $this->assertMatchesRegularExpression('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $ip);
  }

  #[Test]
  public function getClientIPAddressUsesRemoteAddrWhenProxyNotTrusted(): void
  {
    putenv('TRUSTED_PROXIES=');
    $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
    $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.25';

    $ip = Security::getClientIPAddress();

    $this->assertSame('192.168.1.100', $ip);
  }

  #[Test]
  public function getClientIPAddressUsesForwardedHeaderForTrustedProxy(): void
  {
    putenv('TRUSTED_PROXIES=192.168.1.100');
    $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
    $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.25, 198.51.100.26';

    $ip = Security::getClientIPAddress();

    $this->assertSame('198.51.100.25', $ip);
    putenv('TRUSTED_PROXIES');
  }
}
