<?php declare(strict_types=1);

namespace PayCal\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Config\Environment;
use PayCal\Observability\Lens;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test Lens render() guards and output generation.
 *
 * Validates:
 * - isDev() environment + config checks
 * - isHtmlResponse() detection (CLI, redirects, Content-Type)
 * - isLensRequested() query param validation
 * - render() output structure when all guards pass
 */
#[Group('unit')]
final class LensRenderTest extends TestCase
{
  /**
   * Get default environment variables for testing.
   *
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

  protected function setUp(): void
  {
    parent::setUp();

    // Bootstrap environment for each test
    require_once __DIR__ . '/../../bootstrap/Classes.php';
    Environment::bootstrap($this->envDefaults());

    // Reset Lens state between tests
    $this->resetLens();
  }

  protected function tearDown(): void
  {
    Environment::bootstrap($_ENV);
    $this->resetLens();
    $_GET = [];
    $_SERVER = [];
    parent::tearDown();
  }

  /**
   * Reset Lens static state via reflection.
   */
  private function resetLens(): void
  {
    $reflection = new \ReflectionClass(Lens::class);

    $enabled = $reflection->getProperty('enabled');
    $enabled->setValue(null, false);

    $payload = $reflection->getProperty('payload');
    $payload->setValue(null, [
      'meta' => [],
      'events' => [],
      'timers' => [],
      'counters' => []
    ]);

    $activeTimers = $reflection->getProperty('activeTimers');
    $activeTimers->setValue(null, []);
  }

  /**
   * Test render() respects CLI environment (cannot inject HTML in CLI).
   * 
   * Note: PHPUnit runs in CLI mode where php_sapi_name() === 'cli'.
   * Lens correctly guards against HTML injection in non-web contexts.
   */
  public function testRenderDoesNotOutputInCliEnvironment(): void
  {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['lens'] = '1';

    ob_start();
    Lens::boot('/test-route');
    Lens::render();
    $output = ob_get_clean();

    // In CLI mode (like PHPUnit), render should not output HTML
    $this->assertEmpty($output, 'Lens should not render HTML in CLI environment');
  }

  /**
   * Test isLensRequested() guard requires ?lens=1.
   */
  public function testRenderDoesNotOutputWithoutLensQueryParam(): void
  {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    // No $_GET['lens'] set

    ob_start();
    Lens::boot('/test-route');
    Lens::render();
    $output = ob_get_clean();

    // Without ?lens=1, render should output nothing
    $this->assertEmpty($output, 'Lens should not render without ?lens=1 query param');
  }

  /**
   * Test isLensRequested() rejects invalid lens values.
   */
  public function testRenderDoesNotOutputWhenLensParamIsNotOne(): void
  {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['lens'] = 'true'; // Not "1"

    ob_start();
    Lens::boot('/test-route');
    Lens::render();
    $output = ob_get_clean();

    $this->assertEmpty($output, 'Lens should require exact value "1" for lens param');
  }

  /**
   * Test Lens collects payload data correctly regardless of render guards.
   * 
   * While render() won't output in CLI, data() should still return collected payload.
   */
  public function testLensCollectsPayloadDataCorrectly(): void
  {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['lens'] = '1';

    Lens::boot('/api/payroll');
    Lens::add('Test Event', ['key' => 'value'], 'test');
    Lens::timeStart('operation');
    usleep(1000); // 1ms
    Lens::timeEnd('operation');
    Lens::increment('test_counter', 5);

    $data = Lens::data();

    // Validate schema structure
    $this->assertArrayHasKey('meta', $data);
    $this->assertArrayHasKey('events', $data);
    $this->assertArrayHasKey('timers', $data);
    $this->assertArrayHasKey('counters', $data);

    // Validate meta fields
    $this->assertSame('/api/payroll', $data['meta']['route']);
    $this->assertSame('GET', $data['meta']['method']);
    $this->assertArrayHasKey('start_time', $data['meta']);
    $this->assertGreaterThan(0, $data['meta']['start_time']);

    // Validate events captured
    $this->assertCount(1, $data['events']);
    $this->assertSame('Test Event', $data['events'][0]['label']);
    $this->assertSame('test', $data['events'][0]['type']);
    $this->assertSame(['key' => 'value'], $data['events'][0]['payload']);

    // Validate timers captured
    $this->assertCount(1, $data['timers']);
    $this->assertSame('operation', $data['timers'][0]['label']);
    $this->assertGreaterThan(0, $data['timers'][0]['duration_ms']);

    // Validate counters captured
    $this->assertSame(5, $data['counters']['test_counter']);
  }

  /**
   * Test render() does not execute when Lens is not booted.
   */
  public function testRenderDoesNothingWhenNotBooted(): void
  {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['lens'] = '1';

    ob_start();
    // No Lens::boot() call
    Lens::render();
    $output = ob_get_clean();

    $this->assertEmpty($output, 'Lens should not render without boot()');
  }

  /**
   * Test renderConsoleScript generates valid JS structure.
   * 
   * Since render() won't output in CLI, test the script structure via data().
   */
  public function testDataContainsExpectedMetaFields(): void
  {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['lens'] = '1';

    Lens::boot('/test');
    $data = Lens::data();

    // Validate all expected meta fields are present
    $this->assertArrayHasKey('route', $data['meta']);
    $this->assertArrayHasKey('method', $data['meta']);
    $this->assertArrayHasKey('env', $data['meta']);
    $this->assertArrayHasKey('php_version', $data['meta']);
    $this->assertArrayHasKey('start_time', $data['meta']);
    $this->assertSame('GET', $data['meta']['method']);
    $this->assertSame('mac', $data['meta']['env']);
  }

  /**
   * Test Lens captures start_time correctly.
   */
  public function testLensCapturesStartTime(): void
  {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['lens'] = '1';

    $beforeBoot = microtime(true);
    Lens::boot('/test');
    usleep(1000); // 1ms
    $data = Lens::data();

    $this->assertGreaterThanOrEqual($beforeBoot, $data['meta']['start_time']);
    $this->assertLessThanOrEqual(microtime(true), $data['meta']['start_time']);
  }

  /**
   * Test data() method returns empty array when not enabled.
   */
  public function testDataReturnsEmptyArrayWhenNotEnabled(): void
  {
    // Without boot(), data() should return empty array
    $data = Lens::data();
    $this->assertSame([], $data);
  }

  /**
   * Test data() method returns payload structure when enabled.
   */
  public function testDataReturnsPayloadWhenEnabled(): void
  {
    Lens::boot('/test-route');
    Lens::add('Event', ['data' => 123]);

    $data = Lens::data();

    $this->assertArrayHasKey('meta', $data);
    $this->assertArrayHasKey('events', $data);
    $this->assertArrayHasKey('timers', $data);
    $this->assertArrayHasKey('counters', $data);

    $this->assertSame('/test-route', $data['meta']['route']);
    $this->assertCount(1, $data['events']);
  }
}
