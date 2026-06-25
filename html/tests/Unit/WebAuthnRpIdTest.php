<?php declare(strict_types=1);

namespace PayCal\Tests\Unit;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\WebAuthnRpId;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class WebAuthnRpIdTest extends TestCase
{
  /** @var array<string, string> */
  private array $originalEnv;

  /** @var array<string, mixed> */
  private array $originalServer;

  protected function setUp(): void
  {
    parent::setUp();
    require_once __DIR__ . '/../../bootstrap/Classes.php';
    $this->originalEnv = $_ENV;
    $this->originalServer = $_SERVER;
    Environment::bootstrap($this->envDefaults());
  }

  protected function tearDown(): void
  {
    $_SERVER = $this->originalServer;
    Environment::bootstrap($this->originalEnv);
    parent::tearDown();
  }

  public function testResolvesMacPaycalAppFromRequestHostWhenAppDomainDiffers(): void
  {
    $_SERVER['HTTP_HOST'] = 'mac.paycal.app';
    Environment::bootstrap($this->envDefaults(['APP_DOMAIN' => 'dev.paycal.local']));

    $this->assertSame('mac.paycal.app', WebAuthnRpId::resolve());
  }

  public function testKeepsMacPaycalAppRpIdInsteadOfCollapsingToProduction(): void
  {
    $_SERVER['HTTP_HOST'] = 'mac.paycal.app';
    Environment::bootstrap($this->envDefaults(['APP_DOMAIN' => 'mac.paycal.app']));

    $this->assertSame('mac.paycal.app', WebAuthnRpId::resolve());
  }

  public function testCollapsesProductionHostsToPaycalApp(): void
  {
    $_SERVER['HTTP_HOST'] = 'www.paycal.app';
    Environment::bootstrap($this->envDefaults(['APP_DOMAIN' => 'www.paycal.app']));

    $this->assertSame('paycal.app', WebAuthnRpId::resolve());
  }

  public function testEnvOverrideWins(): void
  {
    $_SERVER['HTTP_HOST'] = 'mac.paycal.app';
    Environment::bootstrap($this->envDefaults([
      'APP_DOMAIN' => 'mac.paycal.app',
      'WEBAUTHN_RP_ID' => 'custom.example',
    ]));

    $this->assertSame('custom.example', WebAuthnRpId::resolve());
  }

  public function testFallsBackToConfiguredDomainWhenRequestHostMissing(): void
  {
    unset($_SERVER['HTTP_HOST'], $_SERVER['SERVER_NAME']);
    Environment::bootstrap($this->envDefaults(['APP_DOMAIN' => 'dev.paycal.local']));

    $this->assertSame('dev.paycal.local', WebAuthnRpId::resolve());
  }

  /**
   * @param array<string, string> $overrides
   * @return array<string, string>
   */
  private function envDefaults(array $overrides = []): array
  {
    $defaults = [
      'APP_ENV' => 'mac',
      'APP_SCHEME' => 'https',
      'APP_DOMAIN' => 'localhost',
      'APP_HOME' => '/private/var/www/paycal-private/',
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
      'PC_EMAIL_CONTACT' => 'noreply@example.com',
      'PC_EMAIL_DEBUG' => 'debug@example.com',
      'PC_EMAIL_REPLYTO' => 'reply@example.com',
      'PC_EMAIL_PASSWORD' => 'x',
      'PC_INVITE_CODE' => 'invite',
      'DEV_ALLOW_INLINE_SCRIPTS' => 'true',
      'DEV_SECURITY_DISABLED' => 'false',
      'ENCRYPTION_ENABLED' => 'false',
      'WEBAUTHN_RP_ID' => '',
    ];

    return array_merge($defaults, $overrides);
  }
}
