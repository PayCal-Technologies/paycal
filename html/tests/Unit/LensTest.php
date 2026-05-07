<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Config\Environment;
use PayCal\Observability\Lens;
use PHPUnit\Framework\Attributes\Group;

/**
 * LensTest
 */
#[Group('unit')]
final class LensTest extends TestCase
{
  /**
   * @return array<string, string>
   */
  private function envDefaults(array $overrides = []): array
  {
    $defaults = [
      'APP_ENV' => 'mac',
      'APP_SCHEME' => 'https',
      'APP_DOMAIN' => 'localhost',
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
      'DEV_ALLOW_INLINE_SCRIPTS' => 'true',
      'DEV_SECURITY_DISABLED' => 'false',
      'ENCRYPTION_ENABLED' => 'false',
    ];

    return array_merge($defaults, $overrides);
  }

  protected function tearDown(): void
  {
    Environment::bootstrap($_ENV);
    parent::tearDown();
  }

  #[Test]
  public function lensCollectsEventsTimersCountersAndNormalizesPayload(): void
  {
    Environment::bootstrap($this->envDefaults());
    $_SERVER['REQUEST_METHOD'] = 'GET';

    Lens::boot('sites');
    Lens::add('scalar-event', 'ok');
    Lens::add('deep-array', ['a' => ['b' => ['c' => ['d' => 'value']]]]);

    $obj = new stdClass();
    $obj->alpha = 'beta';
    Lens::add('object-event', $obj);

    Lens::timeStart('redis-op');
    usleep(1000);
    Lens::timeEnd('redis-op');

    Lens::increment('redis_ops');
    Lens::increment('redis_ops', 4);

    $data = Lens::data();

    $this->assertSame('sites', $data['meta']['route']);
    $this->assertSame('GET', $data['meta']['method']);
    $this->assertSame('mac', $data['meta']['env']);

    $this->assertCount(3, $data['events']);
    $this->assertSame('ok', $data['events'][0]['payload']);
    $this->assertSame('[max-depth]', $data['events'][1]['payload']['a']['b']['c']);
    $this->assertSame(stdClass::class, $data['events'][2]['payload']['__class']);
    $this->assertSame('beta', $data['events'][2]['payload']['alpha']);

    $this->assertCount(1, $data['timers']);
    $this->assertSame('redis-op', $data['timers'][0]['label']);
    $this->assertGreaterThan(0, $data['timers'][0]['duration_ms']);

    $this->assertSame(5, $data['counters']['redis_ops']);
  }
}
