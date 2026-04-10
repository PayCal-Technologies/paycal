<?php declare(strict_types=1);

namespace PayCal\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\ShadowTalon;

#[Group('unit')]
final class ShadowTalonTest extends TestCase
{
  /**
    * @param array<string, string> $overrides
   * @return array<string, string>
   */
  private function envDefaults(array $overrides = []): array
  {
    $defaults = [
      'APP_ENV' => 'dev',
      'APP_SCHEME' => 'https',
      'APP_DOMAIN' => 'paycal.test',
      'APP_HOME' => '/private/var/www/paycal/dev/html/',
      'API_VERSION' => 'v1',
      'REDIS_SERVER' => 'localhost',
      'REDIS_PORT' => '6379',
      'REDIS_READ_PORT' => '6379',
      'REDIS_WRITE_PORT' => '6379',
      'REDIS_DB' => '0',
      'REDIS_USER' => '',
      'REDIS_PASSWORD' => '',
      'REDIS_NEW_SESSION_TTL' => '3600',
      'PC_EMAIL_SMTP_SERVER' => 'localhost',
      'PC_EMAIL_SMTP_PORT' => '25',
      'PC_EMAIL_CONTACT' => 'support@example.com',
      'PC_EMAIL_DEBUG' => 'debug@example.com',
      'PC_EMAIL_REPLYTO' => 'reply@example.com',
      'PC_EMAIL_PASSWORD' => 'x',
      'PC_INVITE_CODE' => 'invite',
      'PAYROLL_SIGNING_PRIVATE_KEY' => '',
      'PAYROLL_SIGNING_PUBLIC_KEY' => '',
      'DEV_ALLOW_INLINE_SCRIPTS' => 'false',
      'DEV_SECURITY_DISABLED' => 'false',
      'ENCRYPTION_ENABLED' => 'false',
    ];

    return array_merge($defaults, $overrides);
  }

  protected function setUp(): void
  {
    parent::setUp();
    require_once __DIR__ . '/../../bootstrap/Classes.php';
    Environment::bootstrap($this->envDefaults());
  }

  public function testWantsJsonRequestForApiRoutes(): void
  {
    $this->assertTrue(ShadowTalon::wantsJsonRequest([
      'REQUEST_URI' => '/api/v1/calendar?month=2026-03',
    ]));
  }

  public function testWantsJsonRequestForJsonAcceptHeader(): void
  {
    $this->assertTrue(ShadowTalon::wantsJsonRequest([
      'REQUEST_URI' => '/contact/',
      'HTTP_ACCEPT' => 'application/json, text/plain;q=0.8',
    ]));
  }

  public function testWantsJsonRequestReturnsFalseForNormalPageRequest(): void
  {
    $this->assertFalse(ShadowTalon::wantsJsonRequest([
      'REQUEST_URI' => '/about/',
      'HTTP_ACCEPT' => 'text/html,application/xhtml+xml',
    ]));
  }

  public function testJsonPayloadStaysGeneric(): void
  {
    $payload = ShadowTalon::jsonPayload('ERR-1234');

    $this->assertFalse($payload['success']);
    $this->assertSame(500, $payload['status']);
    $this->assertSame('ERR-1234', $payload['error_id']);
    $this->assertSame('Something went wrong. Please try again in a moment.', $payload['message']);
    $this->assertArrayNotHasKey('trace', $payload);
    $this->assertArrayNotHasKey('exception', $payload);
  }

  public function testRenderPublicHtmlIsHelpfulButDoesNotLeakInternals(): void
  {
    $html = ShadowTalon::renderPublicHtml('ERR-5678');

    $this->assertStringContainsString('Something went wrong', $html);
    $this->assertStringContainsString('ERR-5678', $html);
    $this->assertStringContainsString('Return home', $html);
    $this->assertStringContainsString('Contact support', $html);
    $this->assertStringContainsString('https://paycal.test/', $html);
    $this->assertStringContainsString('https://paycal.test/contact/', $html);
    $this->assertStringNotContainsString('RuntimeException', $html);
    $this->assertStringNotContainsString('Stack trace', $html);
  }
}