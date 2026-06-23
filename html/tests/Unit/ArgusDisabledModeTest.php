<?php declare(strict_types=1);

namespace Tests\Unit;

use PayCal\Domain\Config\Environment;
use PayCal\Observability\Argus;
use PayCal\Observability\DiagnosticSeverity;
use PayCal\Observability\Sink\DiagnosticSinkRegistry;
use PayCal\Observability\Sink\NullSink;
use PayCal\Observability\TraceGatePolicy;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('security')]
final class ArgusDisabledModeTest extends TestCase
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
  public function productionDeniesUnlistedEvents(): void
  {
    Environment::bootstrap($this->prodEnv());
    TraceGatePolicy::resetForTests();
    DiagnosticSinkRegistry::resetForTests();
    NullSink::resetSuppressedCountForTests();

    $before = NullSink::suppressedCount();
    $allowed = Argus::emit('calendar_mutation.work_saved', DiagnosticSeverity::Info, [
      'action' => 'save',
    ]);

    $this->assertFalse($allowed);
    $this->assertGreaterThan($before, NullSink::suppressedCount());
  }

  #[Test]
  public function productionAllowsExplicitSecurityEventShape(): void
  {
    Environment::bootstrap($this->prodEnv());
    TraceGatePolicy::resetForTests();
    DiagnosticSinkRegistry::resetForTests();

    $decisionAllowed = Argus::emit('request_guard.rate_limit_triggered', DiagnosticSeverity::Warn, [
      'scope' => 'user:calendar',
      'remaining' => '0',
    ]);

    $this->assertTrue($decisionAllowed);
  }

  #[Test]
  public function invalidEventNamesAreRejected(): void
  {
    Environment::bootstrap($this->prodEnv());
    TraceGatePolicy::resetForTests();

    $this->assertFalse(Argus::emit('INVALID EVENT', DiagnosticSeverity::Warn, []));
    $this->assertFalse(Argus::emit('singlesegment', DiagnosticSeverity::Warn, []));
  }
}
