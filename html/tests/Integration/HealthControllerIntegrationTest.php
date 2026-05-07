<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[Group('security')]
final class HealthControllerIntegrationTest extends TestCase
{
  private string $adminUUID;
  private string $adminSessionHash;
  private string $dayBucket;

  protected function setUp(): void
  {
    parent::setUp();

    $suffix = bin2hex(random_bytes(6));
    $this->adminUUID = 'health-admin-' . $suffix;
    $this->adminSessionHash = hash('sha256', 'health-admin-session-' . $suffix . '-' . bin2hex(random_bytes(8)));
    $this->dayBucket = date('Y-m-d');

    Database::unlink(Keys::CACHE . ':metrics:contact_support');
    Database::unlink(Keys::CACHE . ':metrics:scraper_defense');

    Database::hset(Keys::USER . ':' . $this->adminUUID, [
      'user_uuid' => $this->adminUUID,
      'email' => 'health-admin-' . $suffix . '@example.com',
      'full_name' => 'Health Admin',
      'email_verified' => '1',
      'auth_level' => (string) AuthLevel::ADMIN->value,
    ]);

    Database::hset(Keys::SESSION . ':' . $this->adminSessionHash, [
      'user_uuid' => $this->adminUUID,
      'created_at' => (string) time(),
    ]);

    Database::set(Keys::TELEMETRY . ':auth:login:' . $this->dayBucket, '4');
    Database::set(Keys::TELEMETRY . ':auth:logout:' . $this->dayBucket, '2');
    Database::set(Keys::TELEMETRY . ':session:duration:0-5min', '3');
    Database::set(Keys::TELEMETRY . ':contact:submissions:total', '9');
    Database::set(Keys::TELEMETRY . ':scraper:attempts:total', '20');
    Database::set(Keys::TELEMETRY . ':scraper:attempts:day:' . $this->dayBucket, '6');
  }

  protected function tearDown(): void
  {
    Database::unlink(Keys::USER . ':' . $this->adminUUID);
    Database::unlink(Keys::SESSION . ':' . $this->adminSessionHash);
    Database::unlink(Keys::TELEMETRY . ':auth:login:' . $this->dayBucket);
    Database::unlink(Keys::TELEMETRY . ':auth:logout:' . $this->dayBucket);
    Database::unlink(Keys::TELEMETRY . ':session:duration:0-5min');
    Database::unlink(Keys::TELEMETRY . ':contact:submissions:total');
    Database::unlink(Keys::TELEMETRY . ':scraper:attempts:total');
    Database::unlink(Keys::TELEMETRY . ':scraper:attempts:day:' . $this->dayBucket);
    Database::unlink(Keys::CACHE . ':metrics:contact_support');
    Database::unlink(Keys::CACHE . ':metrics:scraper_defense');

    parent::tearDown();
  }

  public function testGetSessionHealthReturnsSessionLifecycleMetricsForAdmin(): void
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $sessionHash = var_export($this->adminSessionHash, true);

    $script = 'require ' . $bootstrap . '; '
      . '$_SERVER["REQUEST_METHOD"] = "GET"; '
      . '$_COOKIE["PAYCAL_AUTH"] = ' . $sessionHash . '; '
      . '$result = \\PayCal\\Controllers\\HealthController::getSessionHealth(); '
      . 'echo json_encode($result);';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);

    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded);
    $this->assertArrayHasKey('active_sessions', $decoded);
    $this->assertSame(4, $decoded['logins_today'] ?? null);
    $this->assertSame(2, $decoded['logouts_today'] ?? null);
    $this->assertSame(3, $decoded['duration_0_5min'] ?? null);
  }

  public function testGetHealthSnapshotIncludesContactTelemetryMetrics(): void
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $sessionHash = var_export($this->adminSessionHash, true);

    $script = 'require ' . $bootstrap . '; '
      . '$_SERVER["REQUEST_METHOD"] = "GET"; '
      . '$_COOKIE["PAYCAL_AUTH"] = ' . $sessionHash . '; '
      . '$result = \\PayCal\\Controllers\\HealthController::getHealthSnapshot(); '
      . 'echo json_encode($result);';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);

    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded);
    $this->assertArrayHasKey('contact', $decoded);
    $this->assertIsArray($decoded['contact']);
    $totalSubmissions = $decoded['contact']['total_submissions'] ?? null;
    $this->assertIsInt($totalSubmissions);
    $this->assertGreaterThanOrEqual(9, $totalSubmissions);
  }

  public function testGetHealthSnapshotIncludesScraperTelemetryMetrics(): void
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $sessionHash = var_export($this->adminSessionHash, true);

    $script = 'require ' . $bootstrap . '; '
      . '$_SERVER["REQUEST_METHOD"] = "GET"; '
      . '$_COOKIE["PAYCAL_AUTH"] = ' . $sessionHash . '; '
      . '$result = \\PayCal\\Controllers\\HealthController::getHealthSnapshot(); '
      . 'echo json_encode($result);';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);

    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded);
    $this->assertArrayHasKey('scraper_defense', $decoded);
    $this->assertIsArray($decoded['scraper_defense']);
    $this->assertSame(20, $decoded['scraper_defense']['total_attempts'] ?? null);
    $this->assertSame(6, $decoded['scraper_defense']['attempts_today'] ?? null);
  }
}
