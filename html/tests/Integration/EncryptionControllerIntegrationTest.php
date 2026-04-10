<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Controllers\EncryptionController;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PHPUnit\Framework\TestCase;

final class EncryptionControllerIntegrationTest extends TestCase
{
  private string $adminUUID;
  private string $adminSessionHash;

  protected function setUp(): void
  {
    parent::setUp();

    $suffix = bin2hex(random_bytes(6));
    $this->adminUUID = 'enc-admin-' . $suffix;
    $this->adminSessionHash = hash('sha256', 'enc-admin-session-' . $suffix . '-' . bin2hex(random_bytes(8)));

    Database::hset(Keys::USER . ':' . $this->adminUUID, [
      'user_uuid' => $this->adminUUID,
      'email' => 'enc-admin-' . $suffix . '@example.com',
      'full_name' => 'Encryption Admin',
      'email_verified' => '1',
      'auth_level' => (string) AuthLevel::ADMIN->value,
    ]);

    Database::hset(Keys::SESSION . ':' . $this->adminSessionHash, [
      'user_uuid' => $this->adminUUID,
      'created_at' => (string) time(),
    ]);
  }

  protected function tearDown(): void
  {
    Database::unlink(Keys::USER . ':' . $this->adminUUID);
    Database::unlink(Keys::SESSION . ':' . $this->adminSessionHash);

    parent::tearDown();
  }

  /**
   * @return array<string, mixed>
   */
  private function runEncryptionCall(string $method, string $requestMethod): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $method = var_export($method, true);
    $requestMethod = var_export($requestMethod, true);
    $script = 'require ' . $bootstrap . '; '
      . '$_SERVER["REQUEST_METHOD"] = ' . $requestMethod . '; '
      . 'ob_start(); '
      . '$c = new \\PayCal\\Controllers\\EncryptionController(); '
      . '$m = ' . $method . '; '
      . '$c->{$m}(); '
      . 'echo ob_get_clean();';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);
    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded);

    return $decoded;
  }

  public function testGetVersionInfoRequiresAuthentication(): void
  {
    $decoded = $this->runEncryptionCall('getVersionInfo', 'GET');
    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('not authenticated', strtolower((string) ($decoded['message'] ?? '')));
  }

  public function testGetConfigRequiresAuthentication(): void
  {
    $decoded = $this->runEncryptionCall('getConfig', 'GET');
    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('not authenticated', strtolower((string) ($decoded['message'] ?? '')));
  }

  public function testGetTelemetrySummaryDeniesCrossStreamJoinRequest(): void
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $sessionHash = var_export($this->adminSessionHash, true);
    $script = 'require ' . $bootstrap . '; '
      . '$_SERVER["REQUEST_METHOD"] = "GET"; '
      . '$_COOKIE["PAYCAL_AUTH"] = ' . $sessionHash . '; '
      . '$_GET = ["join_stream" => "product"]; '
      . 'ob_start(); '
      . '$c = new \\PayCal\\Controllers\\EncryptionController(); '
      . '$c->getTelemetrySummary(); '
      . 'echo ob_get_clean();';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);

    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded);
    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('Telemetry query denied', (string) ($decoded['message'] ?? ''));
    $this->assertSame('cross_stream_join_denied', $decoded['reason'] ?? null);
  }

  public function testGetTelemetrySummaryAllowsSecurityStreamAndReturnsSchema(): void
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $sessionHash = var_export($this->adminSessionHash, true);
    $script = 'require ' . $bootstrap . '; '
      . '$_SERVER["REQUEST_METHOD"] = "GET"; '
      . '$_COOKIE["PAYCAL_AUTH"] = ' . $sessionHash . '; '
      . '$_GET = ["join_stream" => "security"]; '
      . 'ob_start(); '
      . '$c = new \\PayCal\\Controllers\\EncryptionController(); '
      . '$c->getTelemetrySummary(); '
      . 'echo ob_get_clean();';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);

    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded);
    $this->assertSame('success', $decoded['status'] ?? null);
    $this->assertArrayHasKey('schema', $decoded);
  }
}
