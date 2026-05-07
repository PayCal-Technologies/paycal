<?php declare(strict_types=1);

namespace PayCal\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Authentication;
use PayCal\Domain\Config\Environment;
use PHPUnit\Framework\Attributes\Group;

/**
 * AuthenticationRedirectTest
 */
#[Group('unit')]
#[Group('auth')]
final class AuthenticationRedirectTest extends TestCase
{
  /**
   * @return array<string, string>
   */
  private function envDefaults(array $overrides = []): array
  {
    $defaults = [
      'APP_ENV' => 'dev',
      'APP_SCHEME' => 'https',
      'APP_DOMAIN' => 'mac.paycal.local',
      'APP_HOME' => '/private/var/www/paycal/dev/',
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
      'PAYROLL_SIGNING_PRIVATE_KEY' => '',
      'PAYROLL_SIGNING_PUBLIC_KEY' => '',
      'DEV_ALLOW_INLINE_SCRIPTS' => 'true',
      'DEV_SECURITY_DISABLED' => 'false',
      'ENCRYPTION_ENABLED' => 'false',
    ];

    return array_merge($defaults, $overrides);
  }

  protected function setUp(): void
  {
    parent::setUp();
    Environment::bootstrap($this->envDefaults());
  }

  protected function tearDown(): void
  {
    // Restore real Environment so subsequent tests get correct Redis credentials.
    Environment::bootstrap($_ENV);
    parent::tearDown();
  }

  public function testUnauthenticatedRedirectUrlPointsToAuth(): void
  {
    $this->assertSame('https://mac.paycal.local/auth/', Authentication::unauthenticatedRedirectURL());
  }

  public function testUnauthenticatedRedirectTargetSkipsAuthPath(): void
  {
    $this->assertNull(Authentication::unauthenticatedRedirectTarget('/auth/'));
    $this->assertNull(Authentication::unauthenticatedRedirectTarget('/auth'));
    $this->assertNull(Authentication::unauthenticatedRedirectTarget('/auth/?auth_tab=signin'));
  }

  public function testUnauthenticatedRedirectTargetForHomePointsToAuth(): void
  {
    $this->assertSame(
      'https://mac.paycal.local/auth/',
      Authentication::unauthenticatedRedirectTarget('/')
    );
  }

  public function testCookieSameSiteDefaultsToLaxForSecureCookies(): void
  {
    putenv('AUTH_COOKIE_SAMESITE');
    unset($_ENV['AUTH_COOKIE_SAMESITE']);

    $this->assertSame('Lax', $this->invokeCookieSameSitePolicy(true));
  }

  public function testCookieSameSiteHonorsStrictOverride(): void
  {
    putenv('AUTH_COOKIE_SAMESITE=strict');
    $_ENV['AUTH_COOKIE_SAMESITE'] = 'strict';

    try {
      $this->assertSame('Strict', $this->invokeCookieSameSitePolicy(true));
    } finally {
      putenv('AUTH_COOKIE_SAMESITE');
      unset($_ENV['AUTH_COOKIE_SAMESITE']);
    }
  }

  public function testCookieSameSiteNoneFallsBackToLaxWithoutSecureTransport(): void
  {
    putenv('AUTH_COOKIE_SAMESITE=none');
    $_ENV['AUTH_COOKIE_SAMESITE'] = 'none';

    try {
      $this->assertSame('Lax', $this->invokeCookieSameSitePolicy(false));
    } finally {
      putenv('AUTH_COOKIE_SAMESITE');
      unset($_ENV['AUTH_COOKIE_SAMESITE']);
    }
  }

  private function invokeCookieSameSitePolicy(bool $secure): string
  {
    $method = new \ReflectionMethod(Authentication::class, 'cookieSameSitePolicy');
    return (string) $method->invoke(null, $secure);
  }
}
