<?php declare(strict_types=1);

namespace PayCal\Tests\Unit;

use PayCal\Controllers\BillingController;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class BillingControllerUrlNormalizationTest extends TestCase
{
  /** @var array<string, mixed> */
  private array $serverBackup = [];
  /** @var array<string, mixed> */
  private array $cookieBackup = [];

  /**
   * @return array<string, string>
   */
  private function envDefaults(array $overrides = []): array
  {
    $defaults = [
      'APP_ENV' => 'dev',
      'APP_SCHEME' => 'https',
      'APP_DOMAIN' => 'dev.paycal.local',
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
    require_once __DIR__ . '/../../bootstrap/Classes.php';
    Environment::bootstrap($this->envDefaults());
    $this->serverBackup = $_SERVER;
    $this->cookieBackup = $_COOKIE;
    putenv('TRUSTED_PROXIES=127.0.0.1');
  }

  protected function tearDown(): void
  {
    Environment::bootstrap($_ENV);
    $_SERVER = $this->serverBackup;
    $_COOKIE = $this->cookieBackup;
    putenv('TRUSTED_PROXIES');
    parent::tearDown();
  }

  public function testRelativePathUsesCurrentRequestHost(): void
  {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_X_FORWARDED_HOST'] = 'dev.paycal.app';
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

    $this->assertSame(
      'https://dev.paycal.app/settings/account/?billing=portal',
      $this->invokeNormalize('/settings/account/?billing=portal', '/settings/account/?billing=portal')
    );
  }

  public function testAbsoluteCurrentHostIsAllowed(): void
  {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_X_FORWARDED_HOST'] = 'dev.paycal.app';
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

    $candidate = 'https://dev.paycal.app/settings/account/?billing=portal';
    $this->assertSame($candidate, $this->invokeNormalize($candidate, '/settings/account/?billing=portal'));
  }

  public function testUntrustedAbsoluteHostFallsBackToRequestHost(): void
  {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_X_FORWARDED_HOST'] = 'dev.paycal.app';
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

    $this->assertSame(
      'https://dev.paycal.app/settings/account/?billing=portal',
      $this->invokeNormalize('https://evil.example/anywhere', '/settings/account/?billing=portal')
    );
  }

  public function testFallsBackToConfiguredOriginWhenRequestHostMissing(): void
  {
    unset($_SERVER['HTTP_HOST'], $_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_HOST'], $_SERVER['HTTP_X_FORWARDED_PROTO']);

    $this->assertSame(
      'https://dev.paycal.local/settings/account/?billing=portal',
      $this->invokeNormalize('/settings/account/?billing=portal', '/settings/account/?billing=portal')
    );
  }

  public function testAppendQueryParamReplacesExistingBillingStatus(): void
  {
    $controller = new BillingController();
    $method = new \ReflectionMethod(BillingController::class, 'appendQueryParam');

    $this->assertSame(
      'https://dev.paycal.local/settings/subscription/?billing=delayed',
      $method->invoke($controller, 'https://dev.paycal.local/settings/subscription/?billing=success', 'billing', 'delayed')
    );

    $this->assertSame(
      'https://dev.paycal.local/settings/subscription/?billing=delayed#panel-billing',
      $method->invoke($controller, 'https://dev.paycal.local/settings/subscription/?billing=success#panel-billing', 'billing', 'delayed')
    );
  }

  public function testVerifiedRedirectTargetRejectsUnsignedNextUrl(): void
  {
    $sessionHash = $this->seedSession();
    $_COOKIE['PAYCAL_AUTH'] = $sessionHash;
    $_SERVER['HTTP_COOKIE'] = 'PAYCAL_AUTH=' . rawurlencode($sessionHash);

    try {
      $this->assertSame(
        'https://dev.paycal.local/settings/account/?billing=success',
        $this->invokeVerifiedRedirectTarget('https://dev.paycal.local/settings/account/?billing=portal', '')
      );
    } finally {
      Database::unlink(Keys::SESSION . ':' . $sessionHash);
    }
  }

  public function testVerifiedRedirectTargetRejectsWrongSignature(): void
  {
    $sessionHash = $this->seedSession();
    $_COOKIE['PAYCAL_AUTH'] = $sessionHash;
    $_SERVER['HTTP_COOKIE'] = 'PAYCAL_AUTH=' . rawurlencode($sessionHash);
    $next = 'https://dev.paycal.local/settings/account/?billing=portal';
    $wrongSignature = hash_hmac('sha256', $next, 'not-the-session');

    try {
      $this->assertSame(
        'https://dev.paycal.local/settings/account/?billing=success',
        $this->invokeVerifiedRedirectTarget($next, $wrongSignature)
      );
    } finally {
      Database::unlink(Keys::SESSION . ':' . $sessionHash);
    }
  }

  public function testVerifiedRedirectTargetAcceptsSignedSameOriginNextUrl(): void
  {
    $sessionHash = $this->seedSession();
    $_COOKIE['PAYCAL_AUTH'] = $sessionHash;
    $_SERVER['HTTP_COOKIE'] = 'PAYCAL_AUTH=' . rawurlencode($sessionHash);
    $next = 'https://dev.paycal.local/settings/account/?billing=portal';
    $signature = hash_hmac('sha256', $next, $sessionHash);

    try {
      $this->assertSame($next, $this->invokeVerifiedRedirectTarget($next, $signature));
    } finally {
      Database::unlink(Keys::SESSION . ':' . $sessionHash);
    }
  }

  private function invokeNormalize(mixed $value, string $fallbackPath): string
  {
    $controller = new BillingController();
    $method = new \ReflectionMethod(BillingController::class, 'normalizeAppURL');

    return (string) $method->invoke($controller, $value, $fallbackPath);
  }

  private function invokeVerifiedRedirectTarget(mixed $nextRaw, mixed $sigRaw): string
  {
    $controller = new BillingController();
    $method = new \ReflectionMethod(BillingController::class, 'verifiedRedirectTarget');

    return (string) $method->invoke($controller, $nextRaw, $sigRaw);
  }

  private function seedSession(): string
  {
    $sessionHash = hash('sha256', bin2hex(random_bytes(32)));
    Database::hset(Keys::SESSION . ':' . $sessionHash, [
      'user_uuid' => 'billing-url-normalization-user',
      'created_at' => (string) time(),
    ]);
    Database::expire(Keys::SESSION . ':' . $sessionHash, 3600);

    return $sessionHash;
  }
}
