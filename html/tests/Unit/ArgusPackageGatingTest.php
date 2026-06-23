<?php declare(strict_types=1);

namespace Tests\Unit;

use PayCal\Domain\Config\Environment;
use PayCal\Observability\Argus;
use PayCal\Observability\ArgusPackageStore;
use PayCal\Observability\DiagnosticSeverity;
use PayCal\Observability\Sink\DiagnosticSinkRegistry;
use PayCal\Observability\Sink\NullSink;
use PayCal\Observability\TraceGate;
use PayCal\Observability\TraceGatePolicy;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('security')]
final class ArgusPackageGatingTest extends TestCase
{
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
  public function masterForcedOffDeniesAllEvents(): void
  {
    Environment::bootstrap($this->prodEnv());
    TraceGatePolicy::resetForTests();
    DiagnosticSinkRegistry::resetForTests();

    ArgusPackageStore::setMasterEnabled(false, 'admin-test');

    $this->assertFalse(Argus::emit('request_guard.rate_limit_triggered', DiagnosticSeverity::Warn, [
      'scope' => 'user:calendar',
    ]));
  }

  #[Test]
  public function packageDisabledDeniesModuleEvenWhenProductionAllowed(): void
  {
    Environment::bootstrap($this->prodEnv());
    TraceGatePolicy::resetForTests();
    DiagnosticSinkRegistry::resetForTests();

    ArgusPackageStore::setMasterEnabled(true, 'admin-test');
    ArgusPackageStore::setPackageEnabled('auth', false, 'admin-test');

    $this->assertFalse(Argus::emit('auth.signup.start', DiagnosticSeverity::Info, [
      'step' => 'requested',
    ]));
  }

  #[Test]
  public function packageEnabledAllowsModuleInProductionWhenPolicyPermits(): void
  {
    Environment::bootstrap($this->prodEnv());
    TraceGatePolicy::resetForTests();
    DiagnosticSinkRegistry::resetForTests();

    ArgusPackageStore::setMasterEnabled(true, 'admin-test');
    ArgusPackageStore::setPackageEnabled('request_guard', true, 'admin-test');

    $this->assertTrue(Argus::emit('request_guard.rate_limit_triggered', DiagnosticSeverity::Warn, [
      'scope' => 'user:calendar',
    ]));
  }

  #[Test]
  public function disabledCalendarPackageBlocksCalendarEvents(): void
  {
    Environment::bootstrap($this->prodEnv());
    TraceGatePolicy::resetForTests();
    DiagnosticSinkRegistry::resetForTests();
    NullSink::resetSuppressedCountForTests();

    ArgusPackageStore::setMasterEnabled(true, 'admin-test');
    ArgusPackageStore::setPackageEnabled('calendar_mutation', false, 'admin-test');

    $this->assertFalse(Argus::emit('calendar_mutation.work_saved', DiagnosticSeverity::Info, [
      'action' => 'save',
    ]));
  }

  #[Test]
  public function traceGateDeniesWithMasterDisabledReason(): void
  {
    Environment::bootstrap($this->prodEnv());
    TraceGatePolicy::resetForTests();

    ArgusPackageStore::setMasterEnabled(false, 'admin-test');

    $event = \PayCal\Observability\DiagnosticEvent::create(
      'auth.signup.start',
      DiagnosticSeverity::Info,
      [],
    );
    $this->assertNotNull($event);

    $decision = TraceGate::evaluate($event);
    $this->assertFalse($decision->allowed);
    $this->assertSame('master_disabled', $decision->reason);
  }

  #[Test]
  public function packageStoreReflectsWriteInSameRequestWithoutReplicaRead(): void
  {
    TraceGatePolicy::resetForTests();

    ArgusPackageStore::setPackageEnabled('auth', false, 'admin-test');

    $this->assertFalse(TraceGatePolicy::moduleIsEnabled('auth'));

    ArgusPackageStore::setPackageEnabled('auth', true, 'admin-test');

    $this->assertTrue(TraceGatePolicy::moduleIsEnabled('auth'));
  }
}
