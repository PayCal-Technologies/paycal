<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

final class TelemetryControllerIntegrationTest extends TestCase
{
  /**
   * @return array<string, mixed>
   */
  private function runTelemetryCall(string $requestMethod): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $requestMethod = var_export($requestMethod, true);
    $script = 'require ' . $bootstrap . '; '
      . '$_SERVER["REQUEST_METHOD"] = ' . $requestMethod . '; '
      . '$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; '
      . '$_SERVER["HTTP_USER_AGENT"] = "PHPUnit"; '
      . 'ob_start(); '
      . '\\PayCal\\Controllers\\TelemetryController::recordEvent(); '
      . 'echo ob_get_clean();';
    $cmd = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script);
    $output = shell_exec($cmd);

    $this->assertNotFalse($output);
    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded);

    return $decoded;
  }

  public function testRecordEventWithoutSessionIsUnauthorized(): void
  {
    $decoded = $this->runTelemetryCall('GET');
    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('Authentication required', (string) ($decoded['message'] ?? ''));
  }

  public function testRecordEventWithoutSessionIsUnauthorizedForPostRoute(): void
  {
    $decoded = $this->runTelemetryCall('POST');
    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('Authentication required', (string) ($decoded['message'] ?? ''));
  }
}
