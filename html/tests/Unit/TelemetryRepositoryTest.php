<?php declare(strict_types=1);

use PayCal\Domain\Telemetry\TelemetryAccessToken;
use PayCal\Infrastructure\Telemetry\TelemetryRepository;
use PayCal\Domain\TelemetryPolicy;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('security')]
final class TelemetryRepositoryTest extends TestCase
{
  private string $dayBucket;
  private string $weekBucket;
  private string $monthBucket;
  private string $yearBucket;

  protected function setUp(): void
  {
    parent::setUp();
    $this->dayBucket = date('Y-m-d');
    $this->weekBucket = date('o-\\WW');
    $this->monthBucket = date('Y-m');
    $this->yearBucket = date('Y');
  }

  protected function tearDown(): void
  {
    Database::unlink(Keys::TELEMETRY . ':event:calendar.load.success:' . $this->dayBucket);
    Database::unlink('telemetry:encryption:v1:client:decryption-success');
    Database::unlink(Keys::TELEMETRY . ':auth:login:' . $this->dayBucket);
    Database::unlink(Keys::TELEMETRY . ':auth:logout:' . $this->dayBucket);
    Database::unlink(Keys::TELEMETRY . ':session:duration:0-5min');
    Database::unlink(Keys::TELEMETRY . ':scraper:attempts:total');
    Database::unlink(Keys::TELEMETRY . ':scraper:attempts:day:' . $this->dayBucket);
    Database::unlink(Keys::TELEMETRY . ':scraper:attempts:week:' . $this->weekBucket);
    Database::unlink(Keys::TELEMETRY . ':scraper:attempts:month:' . $this->monthBucket);
    Database::unlink(Keys::TELEMETRY . ':scraper:attempts:year:' . $this->yearBucket);
    Database::unlink(Keys::TELEMETRY . ':scraper:netblock:count:test_block');
    Database::hdel(Keys::TELEMETRY . ':scraper:netblock:labels', 'test_block');
    parent::tearDown();
  }

  public function testAuthorizeAllowsProductStreamForProductRole(): void
  {
    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_PRODUCT, 'product', 'default', 'event');

    $result = TelemetryRepository::authorize($token, TelemetryPolicy::STREAM_PRODUCT);

    $this->assertTrue($result['allowed']);
    $this->assertSame('ok', $result['reason']);
  }

  public function testAuthorizeDeniesSecurityStreamForProductRole(): void
  {
    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_PRODUCT, 'product', 'default', 'event');

    $result = TelemetryRepository::authorize($token, TelemetryPolicy::STREAM_SECURITY);

    $this->assertFalse($result['allowed']);
    $this->assertSame('telemetry_access_denied', $result['reason']);
  }

  public function testGuardCrossStreamJoinDeniesMixedStreams(): void
  {
    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_SECURITY, 'security-admin', 'forensics', 'bucket');

    $result = TelemetryRepository::guardCrossStreamJoin($token, TelemetryPolicy::STREAM_PRODUCT, TelemetryPolicy::STREAM_SECURITY);

    $this->assertFalse($result['allowed']);
    $this->assertSame('cross_stream_join_denied', $result['reason']);
  }

  public function testGuardCrossStreamJoinAllowsSameStreamWhenAuthorized(): void
  {
    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_SECURITY, 'security-admin', 'forensics', 'bucket');

    $result = TelemetryRepository::guardCrossStreamJoin($token, TelemetryPolicy::STREAM_SECURITY, TelemetryPolicy::STREAM_SECURITY);

    $this->assertTrue($result['allowed']);
    $this->assertSame('ok', $result['reason']);
  }

  public function testFetchWhitelistedEventCountsDeniesUnauthorizedStreamToken(): void
  {
    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_SECURITY, 'security-readonly', 'forensics', 'bucket');

    $result = TelemetryRepository::fetchWhitelistedEventCounts($token, ['calendar.load.success'], $this->dayBucket);

    $this->assertFalse($result['allowed']);
    $this->assertSame('telemetry_access_denied', $result['reason']);
    $this->assertSame([], $result['events']);
  }

  public function testFetchWhitelistedEventCountsReturnsProductEventsWhenAuthorized(): void
  {
    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_PRODUCT, 'product', 'default', 'bucket');
    Database::set(Keys::TELEMETRY . ':event:calendar.load.success:' . $this->dayBucket, '3');

    $result = TelemetryRepository::fetchWhitelistedEventCounts(
      $token,
      ['calendar.load.success', 'calendar.load.failure'],
      $this->dayBucket
    );

    $this->assertTrue($result['allowed']);
    $this->assertSame('ok', $result['reason']);
    $this->assertSame(['calendar.load.success' => 3], $result['events']);
  }

  public function testFetchEncryptionClientCountersDeniesProductScopedToken(): void
  {
    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_PRODUCT, 'product', 'default', 'bucket');

    $result = TelemetryRepository::fetchEncryptionClientCounters($token, 'v1', ['decryption-success']);

    $this->assertFalse($result['allowed']);
    $this->assertSame('telemetry_access_denied', $result['reason']);
    $this->assertSame([], $result['counters']);
  }

  public function testFetchEncryptionClientCountersReturnsSecurityCountsWhenAuthorized(): void
  {
    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_SECURITY, 'security-admin', 'forensics', 'bucket');
    Database::set('telemetry:encryption:v1:client:decryption-success', '7');

    $result = TelemetryRepository::fetchEncryptionClientCounters($token, 'v1', ['decryption-success', 'decryption-failure']);

    $this->assertTrue($result['allowed']);
    $this->assertSame('ok', $result['reason']);
    $this->assertSame(['decryption-success' => 7, 'decryption-failure' => 0], $result['counters']);
  }

  public function testFetchSessionLifecycleMetricsDeniesUnauthorizedStreamToken(): void
  {
    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_SECURITY, 'readonly', 'forensics', 'bucket');

    $result = TelemetryRepository::fetchSessionLifecycleMetrics($token, $this->dayBucket, ['0-5min']);

    $this->assertFalse($result['allowed']);
    $this->assertSame('telemetry_access_denied', $result['reason']);
    $this->assertSame([], $result['metrics']);
  }

  public function testFetchSessionLifecycleMetricsReturnsCountsWhenAuthorized(): void
  {
    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_PRODUCT, 'product', 'default', 'bucket');
    Database::set(Keys::TELEMETRY . ':auth:login:' . $this->dayBucket, '11');
    Database::set(Keys::TELEMETRY . ':auth:logout:' . $this->dayBucket, '7');
    Database::set(Keys::TELEMETRY . ':session:duration:0-5min', '5');

    $result = TelemetryRepository::fetchSessionLifecycleMetrics($token, $this->dayBucket, ['0-5min']);

    $this->assertTrue($result['allowed']);
    $this->assertSame('ok', $result['reason']);
    $this->assertSame(11, $result['metrics']['logins_today'] ?? null);
    $this->assertSame(7, $result['metrics']['logouts_today'] ?? null);
    $this->assertSame(5, $result['metrics']['duration:0-5min'] ?? null);
  }

  public function testFetchContactSupportMetricsDeniesUnauthorizedToken(): void
  {
    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_SECURITY, 'readonly', 'forensics', 'bucket');

    $result = TelemetryRepository::fetchContactSupportMetrics($token);

    $this->assertFalse($result['allowed']);
    $this->assertSame('telemetry_access_denied', $result['reason']);
    $this->assertSame([], $result['metrics']);
  }

  public function testFetchContactSupportMetricsReturnsSnapshotWhenAuthorized(): void
  {
    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_PRODUCT, 'product', 'default', 'bucket');

    $result = TelemetryRepository::fetchContactSupportMetrics($token);

    $this->assertTrue($result['allowed']);
    $this->assertSame('ok', $result['reason']);
    $this->assertIsArray($result['metrics']);
    $this->assertArrayHasKey('total_submissions', $result['metrics']);
  }

  public function testFetchScraperDefenseMetricsDeniedForUnauthorizedStream(): void
  {
    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_SECURITY, 'readonly', 'forensics', 'bucket');

    $result = TelemetryRepository::fetchScraperDefenseMetrics($token);

    $this->assertFalse($result['allowed']);
    $this->assertSame('telemetry_access_denied', $result['reason']);
    $this->assertSame([], $result['metrics']);
  }

  public function testFetchScraperDefenseMetricsReturnsAggregatesWhenAuthorized(): void
  {
    Database::set(Keys::TELEMETRY . ':scraper:attempts:total', '15');
    Database::set(Keys::TELEMETRY . ':scraper:attempts:day:' . $this->dayBucket, '5');
    Database::set(Keys::TELEMETRY . ':scraper:attempts:week:' . $this->weekBucket, '9');
    Database::set(Keys::TELEMETRY . ':scraper:attempts:month:' . $this->monthBucket, '11');
    Database::set(Keys::TELEMETRY . ':scraper:attempts:year:' . $this->yearBucket, '15');
    Database::set(Keys::TELEMETRY . ':scraper:netblock:count:test_block', '4');
    Database::hset(Keys::TELEMETRY . ':scraper:netblock:labels', ['test_block' => '203.0.113.0/24']);

    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_PRODUCT, 'product', 'default', 'bucket');
    $result = TelemetryRepository::fetchScraperDefenseMetrics($token);

    $this->assertTrue($result['allowed']);
    $this->assertSame('ok', $result['reason']);
    $this->assertSame(15, $result['metrics']['total_attempts'] ?? null);
    $this->assertSame(5, $result['metrics']['attempts_today'] ?? null);
    $this->assertNotEmpty($result['metrics']['top_netblocks'] ?? []);
    $this->assertSame('203.0.113.0/24', $result['metrics']['top_netblocks'][0]['name'] ?? null);
    $this->assertSame(4, $result['metrics']['top_netblocks'][0]['attempts'] ?? null);
  }
}
