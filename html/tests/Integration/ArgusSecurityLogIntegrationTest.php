<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\Extensions\HookBus;
use PayCal\Infrastructure\Telemetry\SecurityLog;
use PayCal\Observability\Argus;
use PayCal\Observability\DiagnosticSeverity;
use PayCal\Observability\Sink\DiagnosticSinkRegistry;
use PayCal\Observability\TraceGatePolicy;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[Group('security')]
final class ArgusSecurityLogIntegrationTest extends TestCase
{
  public static function setUpBeforeClass(): void
  {
    require_once __DIR__ . '/../../extensions/runtime.php';
  }

  /**
   * @return array<string, string>
   */
  private function prodEnv(): array
  {
    return [
      'APP_ENV' => 'prod',
      'APP_SCHEME' => 'https',
      'APP_DOMAIN' => 'paycal.app',
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
      'PC_EMAIL_CONTACT' => 'noreply@example.com',
      'PC_EMAIL_DEBUG' => 'debug@example.com',
      'PC_EMAIL_REPLYTO' => 'reply@example.com',
      'PC_EMAIL_PASSWORD' => 'x',
      'PC_INVITE_CODE' => 'invite',
      'PAYROLL_SIGNING_PRIVATE_KEY' => '',
      'PAYROLL_SIGNING_PUBLIC_KEY' => '',
      'DEV_ALLOW_INLINE_SCRIPTS' => 'false',
      'DEV_SECURITY_DISABLED' => 'false',
      'ENCRYPTION_ENABLED' => 'true',
    ];
  }

  protected function tearDown(): void
  {
    TraceGatePolicy::resetForTests();
    DiagnosticSinkRegistry::resetForTests();
    Environment::bootstrap($_ENV);
    parent::tearDown();
  }

  #[Test]
  public function securityLogRoutesMappedProdEventsThroughArgusHook(): void
  {
    Environment::bootstrap($this->prodEnv());
    TraceGatePolicy::resetForTests();
    DiagnosticSinkRegistry::resetForTests();

    $ref = new \ReflectionProperty(HookBus::class, 'listeners');
    /** @var array<string, array<int, array{priority:int, callback:callable, source:string}>> $originalListeners */
    $originalListeners = $ref->getValue();

    $captured = [];

    try {
      $ref->setValue(null, []);
      HookBus::register('security.audit_event', static function (array $payload) use (&$captured): null {
        $captured[] = $payload;
        return null;
      }, 100, 'test:argus-integration');

      SecurityLog::logRateLimitTriggered('user:calendar', 'U-test-user', 0);

      $this->assertCount(1, $captured);
      $this->assertSame('rate_limit_triggered', $captured[0]['event'] ?? null);
      $this->assertSame('request_guard', $captured[0]['context']['diagnostic_module'] ?? null);
      $this->assertArrayNotHasKey('user_uuid', $captured[0]['context'] ?? []);
      $this->assertArrayNotHasKey('ip', $captured[0]['context'] ?? []);
    } finally {
      $ref->setValue(null, $originalListeners);
    }
  }

  #[Test]
  public function securityLogDropsUnmappedProdEvents(): void
  {
    Environment::bootstrap($this->prodEnv());
    TraceGatePolicy::resetForTests();

    $ref = new \ReflectionProperty(HookBus::class, 'listeners');
    /** @var array<string, array<int, array{priority:int, callback:callable, source:string}>> $originalListeners */
    $originalListeners = $ref->getValue();

    $captured = [];

    try {
      $ref->setValue(null, []);
      HookBus::register('security.audit_event', static function (array $payload) use (&$captured): null {
        $captured[] = $payload;
        return null;
      }, 100, 'test:argus-integration');

      SecurityLog::log('totally_unknown_custom_event', ['foo' => 'bar']);

      $this->assertCount(0, $captured);
    } finally {
      $ref->setValue(null, $originalListeners);
    }
  }

  #[Test]
  public function authModuleEventsAllowedInProduction(): void
  {
    Environment::bootstrap($this->prodEnv());
    TraceGatePolicy::resetForTests();
    DiagnosticSinkRegistry::resetForTests();

    $this->assertTrue(Argus::emit('auth.passkey_login_success', DiagnosticSeverity::Info, [
      'credential_count' => '1',
    ]));
  }
}
