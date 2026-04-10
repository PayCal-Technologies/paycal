<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Controllers\PasskeyController;
use PHPUnit\Framework\TestCase;

final class PasskeyControllerIntegrationTest extends TestCase
{
  /**
   * @return array<string, mixed>
   */
  private function decodeJsonPayload(string $output): array
  {
    $decoded = json_decode($output, true);
    if (is_array($decoded)) {
      return $decoded;
    }

    if (preg_match_all('/\{\s*"status"\s*:\s*"[^"]+".*?\}/s', $output, $matches) === 1 || !empty($matches[0])) {
      $candidate = end($matches[0]);
      $decoded = json_decode((string) $candidate, true);
      if (is_array($decoded)) {
        return $decoded;
      }
    }

    $this->fail('Expected JSON payload in output: ' . $output);
  }

  /**
   * @return array<string, mixed>
   */
  private function runPasskeyCall(string $method, array $payload = []): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $method = var_export($method, true);
    $jsonPayload = var_export(json_encode($payload), true);
    $script = 'require ' . $bootstrap . '; '
      . '$_SERVER["REQUEST_METHOD"] = "POST"; '
      . '$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; '
      . '$_SERVER["CONTENT_TYPE"] = "application/json"; '
      . '$GLOBALS["mock_php_input_passkey"] = ' . $jsonPayload . '; '
      . 'class MockPhpInputStreamPasskey {'
      . '  public $context;'
      . '  public int $position = 0;'
      . '  public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool { $this->position = 0; return true; }'
      . '  public function stream_read(int $count): string { $data = (string)($GLOBALS["mock_php_input_passkey"] ?? ""); $chunk = substr($data, $this->position, $count); $this->position += strlen($chunk); return $chunk; }'
      . '  public function stream_eof(): bool { $data = (string)($GLOBALS["mock_php_input_passkey"] ?? ""); return $this->position >= strlen($data); }'
      . '  public function stream_stat(): array { return []; }'
      . '}'
      . 'stream_wrapper_unregister("php"); '
      . 'stream_wrapper_register("php", "MockPhpInputStreamPasskey"); '
      . 'ob_start(); '
      . '$c = new \\PayCal\\Controllers\\PasskeyController(); '
      . '$m = ' . $method . '; '
      . '$c->{$m}(); '
      . 'stream_wrapper_restore("php"); '
      . 'echo ob_get_clean();';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);
    $decoded = $this->decodeJsonPayload((string) $output);

    return $decoded;
  }

  public function testSignupStartRejectsMissingFullName(): void
  {
    $decoded = $this->runPasskeyCall('signupStart', []);

    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('full name is required', strtolower((string) ($decoded['message'] ?? '')));
  }

  public function testSignupStartRejectsInvalidEmail(): void
  {
    $decoded = $this->runPasskeyCall('signupStart', [
      'fullName' => 'Valid Name',
      'email' => 'not-an-email',
      'inviteCode' => '',
      'deviceName' => 'Test Device',
    ]);

    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('valid email is required', strtolower((string) ($decoded['message'] ?? '')));
  }

  public function testRegisterStartRequiresAuthentication(): void
  {
    $decoded = $this->runPasskeyCall('registerStart', []);

    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('unauthorized', strtolower((string) ($decoded['message'] ?? '')));
  }

  public function testRegisterFinishRequiresAuthentication(): void
  {
    $decoded = $this->runPasskeyCall('registerFinish', []);

    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('unauthorized', strtolower((string) ($decoded['message'] ?? '')));
  }
}
