<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

final class RegistrationControllerIntegrationTest extends TestCase
{
  /**
   * @return array<string, mixed>
   */
  private function runRegistrationWithMethod(string $requestMethod): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $requestMethod = var_export($requestMethod, true);
    $script = 'require ' . $bootstrap . '; '
      . '$_SERVER["REQUEST_METHOD"] = ' . $requestMethod . '; '
      . 'ob_start(); '
      . '\\PayCal\\Controllers\\RegistrationController::register(); '
      . 'echo ob_get_clean();';
    $cmd = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script);
    $output = shell_exec($cmd);

    $this->assertNotFalse($output);
    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded);

    return $decoded;
  }

  public function testRegisterRejectsNonPostRequest(): void
  {
    $decoded = $this->runRegistrationWithMethod('GET');
    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('Method rejected', (string) ($decoded['message'] ?? ''));
  }
}
