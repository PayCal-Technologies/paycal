<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Controllers\DEKController;
use PHPUnit\Framework\TestCase;

final class DEKControllerIntegrationTest extends TestCase
{
  /**
   * @return array<string, mixed>
   */
  private function runDekCall(string $method, ?array $payload = null, array $sessionFields = [], string $credentialId = ''): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $method = var_export($method, true);
    $payloadJson = var_export($payload === null ? '' : (string) json_encode($payload), true);
    $sessionFieldsExport = var_export($sessionFields, true);
    $credentialIdExport = var_export($credentialId, true);
    $script = 'require ' . $bootstrap . '; '
      . '$userUUID = "dek-it-" . bin2hex(random_bytes(6)); '
      . '$sessionHash = bin2hex(random_bytes(16)); '
      . '$credentialId = ' . $credentialIdExport . '; '
      . '\\PayCal\\Domain\\Database::hset(\\PayCal\\Domain\\Constants\\Keys::USER . ":" . $userUUID, ['
      . '"user_uuid" => $userUUID, '
      . '"email" => "dek-" . bin2hex(random_bytes(4)) . "@example.com", '
      . '"full_name" => "DEK Integration", '
      . '"email_verified" => "1"'
      . ']); '
      . '$sessionFields = array_merge(['
      . '"user_uuid" => $userUUID, '
      . '"created_at" => (string) time(), '
      . '"last_activity" => (string) time()'
      . '], ' . $sessionFieldsExport . '); '
      . '\\PayCal\\Domain\\Database::hset(\\PayCal\\Domain\\Constants\\Keys::SESSION . ":" . $sessionHash, $sessionFields); '
      . '\\PayCal\\Domain\\Database::expire(\\PayCal\\Domain\\Constants\\Keys::SESSION . ":" . $sessionHash, 3600); '
      . 'if ($credentialId !== "") { '
      . '\\PayCal\\Domain\\Database::sadd(\\PayCal\\Domain\\Constants\\Keys::webauthnUserCredentials($userUUID), $credentialId); '
      . '\\PayCal\\Domain\\Database::hset(\\PayCal\\Domain\\Constants\\Keys::webauthnCredential($credentialId), ["credential_id" => $credentialId, "user_uuid" => $userUUID]); '
      . '} '
      . '$_COOKIE["PAYCAL_AUTH"] = $sessionHash; '
      . '$_SERVER["REQUEST_METHOD"] = "POST"; '
      . '$GLOBALS["mock_php_input_dek"] = ' . $payloadJson . '; '
      . 'class MockPhpInputStreamDek {'
      . '  public $context;'
      . '  public int $position = 0;'
      . '  public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool { $this->position = 0; return true; }'
      . '  public function stream_read(int $count): string { $data = (string)($GLOBALS["mock_php_input_dek"] ?? ""); $chunk = substr($data, $this->position, $count); $this->position += strlen($chunk); return $chunk; }'
      . '  public function stream_eof(): bool { $data = (string)($GLOBALS["mock_php_input_dek"] ?? ""); return $this->position >= strlen($data); }'
      . '  public function stream_stat(): array { return []; }'
      . '}'
      . 'stream_wrapper_unregister("php"); '
      . 'stream_wrapper_register("php", "MockPhpInputStreamDek"); '
      . 'ob_start(); '
      . '$c = new \\PayCal\\Controllers\\DEKController(); '
      . '$m = ' . $method . '; '
      . '$c->{$m}(); '
      . 'stream_wrapper_restore("php"); '
      . '$out = ob_get_clean(); '
      . '\\PayCal\\Domain\\Database::unlink(\\PayCal\\Domain\\Constants\\Keys::SESSION . ":" . $sessionHash); '
      . 'if ($credentialId !== "") { '
      . '\\PayCal\\Domain\\Database::srem(\\PayCal\\Domain\\Constants\\Keys::webauthnUserCredentials($userUUID), $credentialId); '
      . '\\PayCal\\Domain\\Database::unlink(\\PayCal\\Domain\\Constants\\Keys::webauthnCredential($credentialId)); '
      . '\\PayCal\\Domain\\Database::unlink(\\PayCal\\Domain\\Constants\\Keys::USER . ":" . $userUUID . ":passkey_wrapped_deks"); '
      . '} '
      . '\\PayCal\\Domain\\Database::unlink(\\PayCal\\Domain\\Constants\\Keys::USER . ":" . $userUUID); '
      . 'echo $out;';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);
    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded);

    return $decoded;
  }

  public function testPostWrappedDekRejectsInvalidJsonPayload(): void
  {
    $decoded = $this->runDekCall('postWrappedDek');
    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('invalid json payload', strtolower((string) ($decoded['message'] ?? '')));
  }

  public function testPostPasskeyWrapRejectsInvalidJsonPayload(): void
  {
    $decoded = $this->runDekCall('postPasskeyWrap');
    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('invalid json payload', strtolower((string) ($decoded['message'] ?? '')));
  }

  public function testPostPasskeyWrapRejectsBodySelectedCredentialFromUnboundSession(): void
  {
    $credentialId = 'dek-credential-' . bin2hex(random_bytes(8));
    $wrappedDek = base64_encode((string) json_encode([
      'version' => 1,
      'nonce' => base64_encode(random_bytes(12)),
      'ciphertext' => base64_encode(random_bytes(32)),
      'aad' => null,
    ]));

    $decoded = $this->runDekCall('postPasskeyWrap', [
      'credentialId' => $credentialId,
      'wrappedDekPasskey' => $wrappedDek,
      'dekVersion' => 1,
      'cryptoVersion' => 1,
    ], [
      'auth_strength' => 'standard',
    ], $credentialId);

    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('credentialid required', strtolower((string) ($decoded['message'] ?? '')));
  }
}
