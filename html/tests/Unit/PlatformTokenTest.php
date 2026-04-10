<?php declare(strict_types=1);

use PayCal\Domain\PlatformToken;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class PlatformTokenTest extends TestCase
{
  private array $originalServer;

  protected function setUp(): void
  {
    $this->originalServer = $_SERVER;
  }

  protected function tearDown(): void
  {
    $_SERVER = $this->originalServer;
  }

  public function testDetectPrefersClientHintPlatform(): void
  {
    $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] = '"macOS"';
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';

    $this->assertSame(PlatformToken::MAC, PlatformToken::detect());
  }

  public function testDetectReturnsIosFromUserAgent(): void
  {
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)';

    $this->assertSame(PlatformToken::IOS, PlatformToken::detect());
  }

  public function testDetectReturnsAndroidFromUserAgent(): void
  {
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 14; Pixel Tablet)';

    $this->assertSame(PlatformToken::ANDROID, PlatformToken::detect());
  }

  public function testDetectReturnsMacFromUserAgent(): void
  {
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_4)';

    $this->assertSame(PlatformToken::MAC, PlatformToken::detect());
  }

  public function testDetectReturnsWinFromUserAgent(): void
  {
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';

    $this->assertSame(PlatformToken::WIN, PlatformToken::detect());
  }

  public function testDetectReturnsLinuxFromUserAgent(): void
  {
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64)';

    $this->assertSame(PlatformToken::LINUX, PlatformToken::detect());
  }

  public function testDetectFallsBackToUnknown(): void
  {
    unset($_SERVER['HTTP_SEC_CH_UA_PLATFORM'], $_SERVER['HTTP_USER_AGENT']);

    $this->assertSame(PlatformToken::UNKNOWN, PlatformToken::detect());
  }

  public function testNormalizeRejectsUnexpectedTokens(): void
  {
    $this->assertSame(PlatformToken::UNKNOWN, PlatformToken::normalize('solaris'));
  }
}