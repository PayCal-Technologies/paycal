<?php declare(strict_types=1);

namespace Tests\Unit;

use PayCal\Domain\Config\Environment;
use PayCal\Observability\Argus;
use PayCal\Observability\ArgusCaptureScope;
use PayCal\Observability\ArgusEventBudget;
use PayCal\Observability\ArgusExpiryPolicy;
use PayCal\Observability\ArgusPackageStore;
use PayCal\Observability\ArgusPresetCatalog;
use PayCal\Observability\ArgusRequestContext;
use PayCal\Observability\DiagnosticEvent;
use PayCal\Observability\DiagnosticRedactor;
use PayCal\Observability\DiagnosticSeverity;
use PayCal\Observability\Sink\DiagnosticSinkRegistry;
use PayCal\Observability\TraceGate;
use PayCal\Observability\TraceGatePolicy;
use PayCal\Observability\TraceTimelineStore;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('security')]
final class ArgusCaptureSystemTest extends TestCase
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
    ArgusRequestContext::resetForTests();
    ArgusEventBudget::resetForTests();
    Environment::bootstrap($_ENV);
    parent::tearDown();
  }

  #[Test]
  public function expiredPackageOverrideIsPurgedAndDisabled(): void
  {
    TraceGatePolicy::resetForTests();

    ArgusPackageStore::setPackageEnabled('auth', true, 'admin-test', time() - 10);
    ArgusPackageStore::purgeExpired();

    $this->assertNull(ArgusPackageStore::packageOverride('auth'));
  }

  #[Test]
  public function productionRequiresExpiryDurationCap(): void
  {
    Environment::bootstrap($this->prodEnv());
    TraceGatePolicy::resetForTests();

    $this->assertTrue(ArgusExpiryPolicy::requiresExpiry());
    $expires = ArgusExpiryPolicy::resolveExpiresAt(120, false);
    $this->assertNotNull($expires);
    $this->assertLessThanOrEqual(time() + 3600, $expires);
    $this->assertGreaterThan(time(), $expires);
  }

  #[Test]
  public function scopedCaptureDeniesNonMatchingRequest(): void
  {
    Environment::bootstrap($this->prodEnv());
    TraceGatePolicy::resetForTests();
    DiagnosticSinkRegistry::resetForTests();

    ArgusRequestContext::resetForTests();
    ArgusRequestContext::bootstrap();

    ArgusPackageStore::setCaptureScope(new ArgusCaptureScope(userUuid: 'target-user'), 'admin-test');
    ArgusPackageStore::setMasterEnabled(true, 'admin-test');
    ArgusPackageStore::setPackageEnabled('request_guard', true, 'admin-test', time() + 3600);

    $this->assertFalse(Argus::emit('request_guard.rate_limit_triggered', DiagnosticSeverity::Warn, [
      'scope' => 'user:calendar',
    ]));
  }

  #[Test]
  public function scopedCaptureAllowsMatchingUser(): void
  {
    Environment::bootstrap($this->prodEnv());
    TraceGatePolicy::resetForTests();
    DiagnosticSinkRegistry::resetForTests();

    ArgusRequestContext::resetForTests();
    ArgusRequestContext::bootstrap();

    $userUuid = 'argus-test-user';
    ArgusRequestContext::seedForTests(userUuid: $userUuid);

    ArgusPackageStore::setCaptureScope(new ArgusCaptureScope(userUuid: $userUuid), 'admin-test');
    ArgusPackageStore::setMasterEnabled(true, 'admin-test');
    ArgusPackageStore::setPackageEnabled('request_guard', true, 'admin-test', time() + 3600);

    $this->assertTrue(Argus::emit('request_guard.rate_limit_triggered', DiagnosticSeverity::Warn, [
      'scope' => 'user:calendar',
    ]));
  }

  #[Test]
  public function presetCatalogIncludesAuthInvestigationBundle(): void
  {
    TraceGatePolicy::resetForTests();

    $preset = ArgusPresetCatalog::get('auth_investigation');
    $this->assertNotNull($preset);
    $this->assertContains('auth', $preset['modules']);
    $this->assertContains('request_guard', $preset['modules']);
  }

  #[Test]
  public function diagnosticEventIncludesTraceIdFields(): void
  {
    $event = DiagnosticEvent::create(
      'auth.signup.start',
      DiagnosticSeverity::Info,
      ['step' => 'requested'],
      'corr-1',
      'trace-abc',
      'span-xyz',
    );

    $this->assertNotNull($event);
    $array = $event->toArray();
    $this->assertSame('trace-abc', $array['trace_id']);
    $this->assertSame('span-xyz', $array['span_id']);
  }

  #[Test]
  public function redactorFailsClosedOnBlockedKeys(): void
  {
    TraceGatePolicy::resetForTests();

    $this->assertNull(DiagnosticRedactor::redact(['email' => 'person@example.com']));
    $this->assertNull(DiagnosticRedactor::redact(['authorization' => 'Bearer secret']));
  }

  #[Test]
  public function eventBudgetCapsEmissionsPerRequest(): void
  {
    Environment::bootstrap($this->prodEnv());
    TraceGatePolicy::resetForTests();
    DiagnosticSinkRegistry::resetForTests();
    ArgusEventBudget::resetForTests();

    ArgusPackageStore::setMasterEnabled(true, 'admin-test');
    ArgusPackageStore::setPackageEnabled('request_guard', true, 'admin-test', time() + 3600);

    for ($i = 0; $i < 100; $i++) {
      Argus::emit('request_guard.rate_limit_triggered', DiagnosticSeverity::Warn, ['remaining' => '0']);
    }

    $this->assertFalse(Argus::emit('request_guard.rate_limit_triggered', DiagnosticSeverity::Warn, ['remaining' => '0']));
  }

  #[Test]
  public function traceGateDeniesScopeMismatchReason(): void
  {
    Environment::bootstrap($this->prodEnv());
    TraceGatePolicy::resetForTests();

    ArgusPackageStore::setCaptureScope(new ArgusCaptureScope(route: '/admin/only'), 'admin-test');
    ArgusPackageStore::setMasterEnabled(true, 'admin-test');
    ArgusPackageStore::setPackageEnabled('auth', true, 'admin-test', time() + 3600);

    $event = DiagnosticEvent::create('auth.signup.start', DiagnosticSeverity::Info, [], '', 'trace-1', 'span-1');
    $this->assertNotNull($event);

    $decision = TraceGate::evaluate($event);
    $this->assertFalse($decision->allowed);
    $this->assertSame('scope_mismatch', $decision->reason);
  }
}
