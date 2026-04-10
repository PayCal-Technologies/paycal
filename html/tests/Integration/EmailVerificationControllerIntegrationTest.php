<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Controllers\EmailVerificationController;
use PHPUnit\Framework\TestCase;

final class EmailVerificationControllerIntegrationTest extends TestCase
{
  /**
   * @return array<string, mixed>
   */
  private function runResendCall(): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $script = 'require ' . $bootstrap . '; '
      . '$_SERVER["REQUEST_METHOD"] = "POST"; '
      . 'ob_start(); '
      . '(new \\PayCal\\Controllers\\EmailVerificationController())->resendVerification(); '
      . 'echo ob_get_clean();';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);
    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded);

    return $decoded;
  }

  public function testResendVerificationRequiresAuthentication(): void
  {
    $decoded = $this->runResendCall();

    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('unauthorized', strtolower((string) ($decoded['message'] ?? '')));
  }
}
