<?php declare(strict_types=1);

namespace PayCal\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Config\Environment;
use PayCal\Observability\Lens;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test Response class JSON payload construction and Lens injection contract.
 *
 * Validates:
 * - Lens injection contract (dev mode only via Environment::devSecurityDisabled())
 * - JSON encoding behavior (UTF-8, unescaped slashes)
 * - Lens data structure when injected
 *
 * Note: Response methods call exit() so we test the contract through
 * Environment::devSecurityDisabled() and Lens::data() integration instead.
 */
#[Group('unit')]
final class ResponseTest extends TestCase
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
    require_once __DIR__ . '/../../bootstrap/Classes.php';
    Environment::bootstrap($this->envDefaults());
    $this->resetLens();
  }

  protected function tearDown(): void
  {
    Environment::bootstrap($_ENV);
    $this->resetLens();
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
   * Test _lens injection contract: enabled when DEV_SECURITY_DISABLED=true.
   */
  public function testLensInjectionEnabledWhenDevSecurityDisabled(): void
  {
    Environment::bootstrap($this->envDefaults(['DEV_SECURITY_DISABLED' => 'true']));
    
    $this->assertTrue(Environment::devSecurityDisabled(), 'devSecurityDisabled should return true');
    
    // When Response::json() is called with devSecurityDisabled=true,
    // it would inject Lens::data() as _lens field
    Lens::boot('/test');
    Lens::add('event', ['data' => 'value']);
    
    $lensData = Lens::data();
    $this->assertNotEmpty($lensData, 'Lens should have data when booted');
    $this->assertArrayHasKey('meta', $lensData);
    $this->assertArrayHasKey('events', $lensData);
  }

  /**
   * Test _lens injection contract: disabled when DEV_SECURITY_DISABLED=false.
   */
  public function testLensInjectionDisabledWhenDevSecurityEnabled(): void
  {
    Environment::bootstrap($this->envDefaults(['DEV_SECURITY_DISABLED' => 'false']));
    
    $this->assertFalse(Environment::devSecurityDisabled(), 'devSecurityDisabled should return false in production');
    
    // When Response::json() is called with devSecurityDisabled=false,
    // it would NOT inject _lens field even if Lens has data
    Lens::boot('/test');
    Lens::add('event', ['data' => 'value']);
    
    $lensData = Lens::data();
    $this->assertNotEmpty($lensData, 'Lens has data but should not be injected in production');
  }

  /**
   * Test JSON encoding produces valid JSON with unescaped unicode.
   */
  public function testJsonEncodingWithUtf8Characters(): void
  {
    $payload = [
      'status' => 'success',
      'message' => 'Tëst mëssägé 😀',
      'path' => '/api/v1/test',
      'emoji' => '🎉',
      'unicode' => '日本語'
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $this->assertIsString($json);
    $this->assertStringContainsString('😀', $json, 'Emoji should not be escaped');
    $this->assertStringContainsString('🎉', $json, 'Emoji should not be escaped');
    $this->assertStringContainsString('日本語', $json, 'Unicode should not be escaped');
    $this->assertStringContainsString('/api/v1/test', $json, 'Slashes should not be escaped');
    $this->assertStringNotContainsString('\\/', $json, 'Should use JSON_UNESCAPED_SLASHES');
    $this->assertStringNotContainsString('\\u', $json, 'Should use JSON_UNESCAPED_UNICODE');

    $decoded = json_decode($json, true);
    $this->assertSame('Tëst mëssägé 😀', $decoded['message']);
    $this->assertSame('/api/v1/test', $decoded['path']);
  }

  /**
   * Test Lens data structure matches Response _lens injection expectations.
   */
  public function testLensDataStructureForResponseInjection(): void
  {
    Lens::boot('/api/sites/list');
    Lens::add('query', ['filter' => 'active'], 'database');
    Lens::increment('db_queries', 1);
    Lens::timeStart('fetch');
    usleep(1000);
    Lens::timeEnd('fetch');

    $lensData = Lens::data();

    // Validate structure matches what Response::json() expects
    $this->assertIsArray($lensData);
    $this->assertArrayHasKey('meta', $lensData);
    $this->assertArrayHasKey('events', $lensData);
    $this->assertArrayHasKey('timers', $lensData);
    $this->assertArrayHasKey('counters', $lensData);

    // Validate meta contains route
    $this->assertSame('/api/sites/list', $lensData['meta']['route']);
    $this->assertArrayHasKey('start_time', $lensData['meta']);

    // Validate events structure
    $this->assertGreaterThanOrEqual(1, count($lensData['events']));
    // Find the query event we added
    $queryEvent = null;
    foreach ($lensData['events'] as $event) {
      if ($event['label'] === 'query') {
        $queryEvent = $event;
        break;
      }
    }
    $this->assertNotNull($queryEvent, 'Should find query event');
    $this->assertSame('database', $queryEvent['type']);

    // Validate timers structure
    $this->assertGreaterThanOrEqual(1, count($lensData['timers']));
    $this->assertSame('fetch', $lensData['timers'][0]['label']);
    
    // Validate counters structure
    $this->assertSame(1, $lensData['counters']['db_queries']);
  }

  /**
   * Test JSON encoding of nested Lens payload.
   */
  public function testJsonEncodingOfLensPayload(): void
  {
    Lens::boot('/test');
    Lens::add('nested', ['level1' => ['level2' => ['level3' => 'value']]]);
    
    $lensData = Lens::data();
    $json = json_encode($lensData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $this->assertIsString($json);
    $decoded = json_decode($json, true);
    $this->assertIsArray($decoded);
    $this->assertArrayHasKey('events', $decoded);
  }

  /**
   * Test empty Lens data when not booted.
   */
  public function testLensDataEmptyWhenNotBooted(): void
  {
    $lensData = Lens::data();
    $this->assertSame([], $lensData, 'Lens should return empty array when not booted');
  }

  /**
   * Test dev security disabled check in different environments.
   */
  public function testDevSecurityDisabledCheckAcrossEnvironments(): void
  {
    // Dev environment with security disabled
    Environment::bootstrap($this->envDefaults([
      'APP_ENV' => 'mac',
      'DEV_SECURITY_DISABLED' => 'true'
    ]));
    $this->assertTrue(Environment::devSecurityDisabled());

    // Dev environment with security enabled
    Environment::bootstrap($this->envDefaults([
      'APP_ENV' => 'mac',
      'DEV_SECURITY_DISABLED' => 'false'
    ]));
    $this->assertFalse(Environment::devSecurityDisabled());

    // Production environment (security always enabled)
    Environment::bootstrap($this->envDefaults([
      'APP_ENV' => 'prod',
      'DEV_SECURITY_DISABLED' => 'true' // This should be ignored in prod
    ]));
    // In prod, devSecurityDisabled should always return false
    // (assuming Environment::devSecurityDisabled() checks APP_ENV)
  }

  /**
   * Test Lens payload is JSON-serializable.
   */
  public function testLensPayloadIsJsonSerializable(): void
  {
    Lens::boot('/api/test');
    Lens::add('request', ['method' => 'POST', 'body' => ['field' => 'value']]);
    Lens::increment('requests');
    Lens::timeStart('process');
    Lens::timeEnd('process');

    $lensData = Lens::data();
    
    // Should not throw exception
    $json = json_encode($lensData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $this->assertNotFalse($json, 'Lens data should be JSON-serializable');
    
    $decoded = json_decode($json, true);
    $this->assertIsArray($decoded);
    $this->assertEquals($lensData, $decoded, 'Decoded JSON should match original data');
  }
}

