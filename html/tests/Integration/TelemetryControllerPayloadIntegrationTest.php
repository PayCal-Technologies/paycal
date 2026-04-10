<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

final class TelemetryControllerPayloadIntegrationTest extends TestCase
{
  /**
   * @return array{response: array<string, mixed>, telemetryLog: array<string, mixed>|null}
   */
  private function runTelemetryPayloadWithLog(string $payload): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $payloadExport = var_export($payload, true);

    $script = 'require ' . $bootstrap . '; '
      . '$userUUID = "test-user-" . bin2hex(random_bytes(6)); '
      . '$sessionHash = hash("sha256", bin2hex(random_bytes(16))); '
      . '$logFile = tempnam(sys_get_temp_dir(), "telemetry-log-"); '
      . 'if ($logFile === false) { throw new RuntimeException("Failed to create temp log file"); } '
      . 'ini_set("error_log", $logFile); '
      . '$_SERVER["REQUEST_METHOD"] = "GET"; '
      . 'class_exists("\\PayCal\\Controllers\\TelemetryController"); '
      . '\\PayCal\\Domain\\Database::hset(\\PayCal\\Domain\\Constants\\Keys::USER . ":" . $userUUID, ['
      . '"user_uuid" => $userUUID,'
      . '"email" => "telemetry@example.com",'
      . '"full_name" => "Telemetry User",'
      . '"email_verified" => "1",'
      . '"auth_level" => "user"'
      . ']); '
      . '\\PayCal\\Domain\\Database::hset(\\PayCal\\Domain\\Constants\\Keys::SESSION . ":" . $sessionHash, ['
      . '"user_uuid" => $userUUID,'
      . '"created_at" => date("c")'
      . ']); '
      . '\\PayCal\\Domain\\Database::expire(\\PayCal\\Domain\\Constants\\Keys::SESSION . ":" . $sessionHash, 3600); '
      . '$_COOKIE["PAYCAL_AUTH"] = $sessionHash; '
      . '$_SERVER["REQUEST_METHOD"] = "POST"; '
      . '$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; '
      . '$_SERVER["HTTP_USER_AGENT"] = "PHPUnit"; '
      . '$GLOBALS["mock_php_input_telemetry"] = ' . $payloadExport . '; '
      . 'class MockPhpInputStreamTelemetryPayloadWithLog {'
      . '  public mixed $context;'
      . '  public int $position = 0;'
      . '  public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool { $this->position = 0; return true; }'
      . '  public function stream_read(int $count): string { $data = (string)($GLOBALS["mock_php_input_telemetry"] ?? ""); $chunk = substr($data, $this->position, $count); $this->position += strlen($chunk); return $chunk; }'
      . '  public function stream_eof(): bool { $data = (string)($GLOBALS["mock_php_input_telemetry"] ?? ""); return $this->position >= strlen($data); }'
      . '  public function stream_stat(): array { return []; }'
      . '}'
      . 'stream_wrapper_unregister("php"); '
      . 'stream_wrapper_register("php", "MockPhpInputStreamTelemetryPayloadWithLog"); '
      . 'ob_start(); '
      . '\\PayCal\\Controllers\\TelemetryController::recordEvent(); '
      . '$out = ob_get_clean(); '
      . 'stream_wrapper_restore("php"); '
      . '$logContents = (string) @file_get_contents($logFile); '
      . '@unlink($logFile); '
      . '\\PayCal\\Domain\\Database::unlink(\\PayCal\\Domain\\Constants\\Keys::USER . ":" . $userUUID); '
      . '\\PayCal\\Domain\\Database::unlink(\\PayCal\\Domain\\Constants\\Keys::SESSION . ":" . $sessionHash); '
      . '$firstLine = ""; '
      . 'foreach (preg_split("/\\r?\\n/", $logContents) as $line) { if (strpos((string) $line, "[TELEMETRY] ") !== false) { $firstLine = (string) $line; break; } } '
      . '$payloadLine = ""; '
      . 'if ($firstLine !== "") { $payloadLine = substr($firstLine, strpos($firstLine, "[TELEMETRY] ") + 12); } '
      . 'echo "__TELEMETRY_WRAPPER__" . json_encode(["response" => json_decode($out, true), "telemetryLog" => json_decode($payloadLine, true)]);';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);
    $decoded = null;
    $marker = '__TELEMETRY_WRAPPER__';
    $markerPos = strpos((string) $output, $marker);
    if ($markerPos !== false) {
      $json = substr((string) $output, $markerPos + strlen($marker));
      $decoded = json_decode((string) $json, true);
    }

    $this->assertIsArray($decoded, 'Expected structured telemetry wrapper JSON. Raw output: ' . (string) $output);

    /** @var array{response: array<string, mixed>, telemetryLog: array<string, mixed>|null} $decoded */
    return $decoded;
  }

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
  private function runTelemetryPayload(string $payload): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $payloadExport = var_export($payload, true);

    $script = 'require ' . $bootstrap . '; '
      . '$userUUID = "test-user-" . bin2hex(random_bytes(6)); '
      . '$sessionHash = hash("sha256", bin2hex(random_bytes(16))); '
      . '$_SERVER["REQUEST_METHOD"] = "GET"; '
      . 'class_exists("\\PayCal\\Controllers\\TelemetryController"); '
      . '\\PayCal\\Domain\\Database::hset(\\PayCal\\Domain\\Constants\\Keys::USER . ":" . $userUUID, ['
      . '"user_uuid" => $userUUID,'
      . '"email" => "telemetry@example.com",'
      . '"full_name" => "Telemetry User",'
      . '"email_verified" => "1",'
      . '"auth_level" => "user"'
      . ']); '
      . '\\PayCal\\Domain\\Database::hset(\\PayCal\\Domain\\Constants\\Keys::SESSION . ":" . $sessionHash, ['
      . '"user_uuid" => $userUUID,'
      . '"created_at" => date("c")'
      . ']); '
      . '\\PayCal\\Domain\\Database::expire(\\PayCal\\Domain\\Constants\\Keys::SESSION . ":" . $sessionHash, 3600); '
      . '$_COOKIE["PAYCAL_AUTH"] = $sessionHash; '
      . '$_SERVER["REQUEST_METHOD"] = "POST"; '
      . '$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; '
      . '$_SERVER["HTTP_USER_AGENT"] = "PHPUnit"; '
      . '$GLOBALS["mock_php_input_telemetry"] = ' . $payloadExport . '; '
      . 'class MockPhpInputStreamTelemetryPayload {'
      . '  public mixed $context;'
      . '  public int $position = 0;'
      . '  public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool { $this->position = 0; return true; }'
      . '  public function stream_read(int $count): string { $data = (string)($GLOBALS["mock_php_input_telemetry"] ?? ""); $chunk = substr($data, $this->position, $count); $this->position += strlen($chunk); return $chunk; }'
      . '  public function stream_eof(): bool { $data = (string)($GLOBALS["mock_php_input_telemetry"] ?? ""); return $this->position >= strlen($data); }'
      . '  public function stream_stat(): array { return []; }'
      . '}'
      . 'stream_wrapper_unregister("php"); '
      . 'stream_wrapper_register("php", "MockPhpInputStreamTelemetryPayload"); '
      . 'ob_start(); '
      . '\\PayCal\\Controllers\\TelemetryController::recordEvent(); '
      . '$out = ob_get_clean(); '
      . 'stream_wrapper_restore("php"); '
      . '\\PayCal\\Domain\\Database::unlink(\\PayCal\\Domain\\Constants\\Keys::USER . ":" . $userUUID); '
      . '\\PayCal\\Domain\\Database::unlink(\\PayCal\\Domain\\Constants\\Keys::SESSION . ":" . $sessionHash); '
      . 'echo $out;';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);
    $decoded = $this->decodeJsonPayload((string) $output);

    return $decoded;
  }

  public function testRecordEventRejectsInvalidJsonPayload(): void
  {
    $decoded = $this->runTelemetryPayload('{invalid-json');

    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('invalid json', strtolower((string) ($decoded['message'] ?? '')));
  }

  public function testRecordEventRejectsMissingType(): void
  {
    $decoded = $this->runTelemetryPayload(json_encode(['fields' => ['x' => 1]]) ?: '{}');

    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('missing event type', strtolower((string) ($decoded['message'] ?? '')));
  }

  public function testRecordEventScrubsSensitiveFieldsAndAddsStreamBoundaries(): void
  {
    $payload = json_encode([
      'type' => 'pw.security.event',
      'fields' => [
        'email' => 'pii@example.com',
        'ip_address' => '127.0.0.1',
        'full_name' => 'Telemetry User',
        'safe_metric' => 42,
      ],
    ]) ?: '{}';

    $decoded = $this->runTelemetryPayloadWithLog($payload);
    $response = $decoded['response'];
    $log = $decoded['telemetryLog'];

    $this->assertSame('success', $response['status'] ?? null);
    $this->assertIsArray($log);
    $this->assertSame('pw.security.event', $log['type'] ?? null);
    $this->assertArrayNotHasKey('user_uuid', $log);
    $this->assertArrayNotHasKey('ip', $log);
    $this->assertSame('product', $log['stream'] ?? null);
    $this->assertSame(30, (int) ($log['retention_days'] ?? 0));
    $this->assertSame('product-observability-only', $log['access_boundary'] ?? null);
    $this->assertMatchesRegularExpression('/^[a-f0-9]{24}$/', (string) ($log['subject_token'] ?? ''));
    $this->assertMatchesRegularExpression('/^[a-f0-9]{24}$/', (string) ($log['network_token'] ?? ''));

    $fields = $log['fields'] ?? [];
    $this->assertIsArray($fields);
    $this->assertArrayNotHasKey('email', $fields);
    $this->assertArrayNotHasKey('ip_address', $fields);
    $this->assertArrayNotHasKey('full_name', $fields);
    $this->assertSame(42, $fields['safe_metric'] ?? null);
  }
}
