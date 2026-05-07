<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PHPUnit\Framework\TestCase;

final class AccountRecoveryKeySettingsIntegrationTest extends TestCase
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
    private function runAccountCall(string $method, array $payload = [], ?string $sessionHash = null): array
    {
        $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
        $method = var_export($method, true);
        $jsonPayload = var_export(json_encode($payload), true);

        $cookieSetup = '';
        if (null !== $sessionHash) {
            $cookieSetup = '$_COOKIE["PAYCAL_AUTH"] = ' . var_export($sessionHash, true) . '; ';
        }

        $script = 'require ' . $bootstrap . '; '
            . '$_SERVER["REQUEST_METHOD"] = "POST"; '
            . '$_SERVER["CONTENT_TYPE"] = "application/json"; '
            . '$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; '
            . $cookieSetup
            . '$GLOBALS["mock_php_input_account_recovery_key"] = ' . $jsonPayload . '; '
            . 'class MockPhpInputStreamAccountRecoveryKey {'
            . '  public mixed $context;'
            . '  public int $position = 0;'
            . '  public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool { $this->position = 0; return true; }'
            . '  public function stream_read(int $count): string { $data = (string)($GLOBALS["mock_php_input_account_recovery_key"] ?? ""); $chunk = substr($data, $this->position, $count); $this->position += strlen($chunk); return $chunk; }'
            . '  public function stream_eof(): bool { $data = (string)($GLOBALS["mock_php_input_account_recovery_key"] ?? ""); return $this->position >= strlen($data); }'
            . '  public function stream_stat(): array { return []; }'
            . '}'
            . 'stream_wrapper_unregister("php"); '
            . 'stream_wrapper_register("php", "MockPhpInputStreamAccountRecoveryKey"); '
            . 'ob_start(); '
            . '$c = new \\PayCal\\Controllers\\AccountController(); '
            . '$m = ' . $method . '; '
            . '$c->{$m}(); '
            . 'stream_wrapper_restore("php"); '
            . 'echo ob_get_clean();';

        $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
        $this->assertNotFalse($output);

        return $this->decodeJsonPayload((string) $output);
    }

    /**
     * @return array{userUUID: string, sessionHash: string}
     */
    private function createTestSession(): array
    {
        $userUUID = 'test-user-' . bin2hex(random_bytes(8));
        $sessionHash = bin2hex(random_bytes(16));

        Database::hset(Keys::USER . ':' . $userUUID, [
            'user_uuid' => $userUUID,
            'email' => 'recovery-' . bin2hex(random_bytes(4)) . '@example.com',
            'full_name' => 'Recovery Test User',
            'email_verified' => '1',
        ]);

        Database::hset(Keys::SESSION . ':' . $sessionHash, [
            'user_uuid' => $userUUID,
            'created_at' => date('c'),
        ]);
        Database::expire(Keys::SESSION . ':' . $sessionHash, 3600);

        return ['userUUID' => $userUUID, 'sessionHash' => $sessionHash];
    }

    private function cleanup(string $userUUID, string $sessionHash): void
    {
        Database::unlink(Keys::USER . ':' . $userUUID);
        Database::unlink(Keys::SESSION . ':' . $sessionHash);
    }

    public function testCreateRecoveryKeyRejectsUnauthenticated(): void
    {
        $recoveryKey = 'ABCD-EFGH-JKMN-PQRS-TVWX-YZ01-2345-6789-ABCD-EFGH-JKMN-PQRS-TVWX';

        $response = $this->runAccountCall('createRecoveryKey', [
            'wrappedDekRecovery' => base64_encode(json_encode([
                'version' => 1,
                'nonce' => base64_encode(random_bytes(12)),
                'ciphertext' => base64_encode(random_bytes(48)),
            ])),
            'accountRecoverySalt' => base64_encode(random_bytes(32)),
            'recoveryProofKey' => base64_encode(random_bytes(32)),
            'recoveryKey' => $recoveryKey,
        ]);

        $this->assertSame('error', $response['status'] ?? null);
        $this->assertStringContainsString('unauthorized', strtolower($response['message'] ?? ''));
    }

    public function testCreateRecoveryKeyPersistsRecoveryMaterial(): void
    {
        $session = $this->createTestSession();
        $userUUID = $session['userUUID'];
        $sessionHash = $session['sessionHash'];

        $wrappedDekRecovery = base64_encode(json_encode([
            'version' => 1,
            'nonce' => base64_encode(random_bytes(12)),
            'ciphertext' => base64_encode(random_bytes(48)),
        ]));
        $accountRecoverySalt = base64_encode(random_bytes(32));
        $recoveryProofKey = base64_encode(random_bytes(32));
        $recoveryKey = 'ABCD-EFGH-JKMN-PQRS-TVWX-YZ01-2345-6789-ABCD-EFGH-JKMN-PQRS-TVWX';

        $response = $this->runAccountCall('createRecoveryKey', [
            'wrappedDekRecovery' => $wrappedDekRecovery,
            'accountRecoverySalt' => $accountRecoverySalt,
            'recoveryProofKey' => $recoveryProofKey,
            'recoveryKey' => $recoveryKey,
        ], $sessionHash);

        $this->assertSame('success', $response['status'] ?? null);

        $stored = Database::hgetall(Keys::USER . ':' . $userUUID);
        $this->assertArrayHasKey('wrapped_dek_recovery', $stored);
        $this->assertArrayHasKey('account_recovery_salt', $stored);
        $this->assertArrayHasKey('recovery_proof_key', $stored);
        $this->assertArrayHasKey('recovery_key_generated', $stored);
        $this->assertArrayHasKey('recovery_proof_key_version', $stored);

        $this->cleanup($userUUID, $sessionHash);
    }
}