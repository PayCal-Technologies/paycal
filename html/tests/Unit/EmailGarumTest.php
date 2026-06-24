<?php declare(strict_types=1);

namespace Tests\Unit;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\EmailGarum;
use PHPUnit\Framework\TestCase;

/**
 * EmailGarumTest
 *
 * Purpose: lock down legacy constant fallback behavior for mail composition.
 * Why this exists: production/bootstrap paths can differ from test bootstrap,
 * so the mailer must not fatal when PC_NAME is not defined globally.
 */
final class EmailGarumTest extends TestCase
{
  /** @var array<string, mixed> */
  private array $serverBackup = [];

  private string|false $trustedProxiesBackup = false;

  protected function setUp(): void
  {
    parent::setUp();
    $this->serverBackup = $_SERVER;
    $this->trustedProxiesBackup = getenv('TRUSTED_PROXIES');

    Environment::bootstrap($this->envDefaults());
  }

  protected function tearDown(): void
  {
    $_SERVER = $this->serverBackup;
    if ($this->trustedProxiesBackup === false) {
      putenv('TRUSTED_PROXIES');
    } else {
      putenv('TRUSTED_PROXIES=' . $this->trustedProxiesBackup);
    }
    Environment::bootstrap($_ENV);
    parent::tearDown();
  }

  public function testAppNameFallsBackToDefaultWhenLegacyConstantMissing(): void
  {
    $method = new \ReflectionMethod(EmailGarum::class, 'appName');

    $this->assertSame('PayCal', $method->invoke(null));
  }

  public function testVerificationBaseUrlIgnoresUntrustedHostHeader(): void
  {
    $_SERVER['HTTP_HOST'] = 'evil.example';
    $_SERVER['HTTPS'] = 'on';
    unset($_SERVER['HTTP_X_FORWARDED_HOST'], $_SERVER['HTTP_X_FORWARDED_PROTO'], $_SERVER['REMOTE_ADDR']);

    $this->assertSame('https://dev.paycal.local', $this->invokeResolveVerificationBaseUrl());
  }

  public function testVerificationBaseUrlIgnoresForwardedHostEvenFromTrustedProxy(): void
  {
    putenv('TRUSTED_PROXIES=127.0.0.1');
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_HOST'] = 'dev.paycal.local';
    $_SERVER['HTTP_X_FORWARDED_HOST'] = 'evil.example';
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

    $this->assertSame('https://dev.paycal.local', $this->invokeResolveVerificationBaseUrl());
  }

  public function testVerificationBaseUrlUsesConfiguredAppBaseUrl(): void
  {
    $_SERVER['HTTP_HOST'] = 'dev.paycal.local';
    $_SERVER['HTTPS'] = 'on';

    $this->assertSame('https://dev.paycal.local', $this->invokeResolveVerificationBaseUrl());
  }

  private function invokeResolveVerificationBaseUrl(): string
  {
    $method = new \ReflectionMethod(EmailGarum::class, 'resolveVerificationBaseUrl');

    return (string) $method->invoke(null);
  }

  /**
   * @return array<string, string>
   */
  private function envDefaults(array $overrides = []): array
  {
    $defaults = [
      'APP_ENV' => 'dev',
      'APP_SCHEME' => 'https',
      'APP_DOMAIN' => 'dev.paycal.local',
      'APP_HOME' => dirname(__DIR__, 2) . '/',
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
      'PC_EMAIL_CONTACT' => 'noreply@example.test',
      'PC_EMAIL_DEBUG' => '',
      'PC_EMAIL_REPLYTO' => 'reply@example.test',
      'PC_EMAIL_PASSWORD' => 'x',
      'PC_INVITE_CODE' => 'invite',
      'DEV_ALLOW_INLINE_SCRIPTS' => 'true',
      'DEV_SECURITY_DISABLED' => 'false',
      'ENCRYPTION_ENABLED' => 'false',
    ];

    return array_merge($defaults, $overrides);
  }
}
