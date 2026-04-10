<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Controllers\KekController;
use PHPUnit\Framework\TestCase;

final class KekControllerIntegrationTest extends TestCase
{
  /**
   * @return array<string, mixed>
   */
  private function runKekCall(string $method, string $requestMethod): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $method = var_export($method, true);
    $requestMethod = var_export($requestMethod, true);
    $script = 'require ' . $bootstrap . '; '
      . '$_SERVER["REQUEST_METHOD"] = ' . $requestMethod . '; '
      . 'ob_start(); '
      . '$c = new \\PayCal\\Controllers\\KekController(); '
      . '$m = ' . $method . '; '
      . '$c->{$m}(); '
      . 'echo ob_get_clean();';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);
    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded);

    return $decoded;
  }

  private function assertKekAuthOrDisabled(array $decoded): void
  {
    $this->assertSame('error', $decoded['status'] ?? null);
    $message = strtolower((string) ($decoded['message'] ?? ''));
    $this->assertTrue(
      str_contains($message, 'not authenticated') || str_contains($message, 'encryption is disabled'),
      'Expected authentication or encryption-disabled error, got: ' . $message
    );
  }

  public function testGetSaltWithoutSessionReturnsError(): void
  {
    $decoded = $this->runKekCall('getSalt', 'GET');
    $this->assertKekAuthOrDisabled($decoded);
  }

  public function testGetWrappedWithoutSessionReturnsError(): void
  {
    $decoded = $this->runKekCall('getWrapped', 'GET');
    $this->assertKekAuthOrDisabled($decoded);
  }

  public function testPostWrappedWithoutSessionReturnsError(): void
  {
    $decoded = $this->runKekCall('postWrapped', 'POST');
    $this->assertKekAuthOrDisabled($decoded);
  }
}
