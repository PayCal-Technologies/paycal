<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Controllers\RecoveryEmailController;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PHPUnit\Framework\TestCase;

/**
 * RecoveryEmailControllerIntegrationTest
 *
 * Integration tests for recovery email verification endpoints:
 * - POST /api/v1/account/recovery-email/start
 * - POST /api/v1/account/recovery-email/verify
 * - POST /api/v1/account/recovery-email/resend
 * 
 * Tests focus on validation, authentication, and error handling.
 */
final class RecoveryEmailControllerIntegrationTest extends TestCase
{
  /**
   * Decode JSON response from controller
   * 
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
   * Run recovery email endpoint with mocked php://input
   * 
   * @return array<string, mixed>
   */
  private function runRecoveryEmailCall(string $method, array $payload = [], ?string $sessionHash = null): array
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
      . '$GLOBALS["mock_php_input_recovery"] = ' . $jsonPayload . '; '
      . 'class MockPhpInputStreamRecovery {'
      . '  public mixed $context = null;'
      . '  public int $position = 0;'
      . '  public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool { $this->position = 0; return true; }'
      . '  public function stream_read(int $count): string { $data = (string)($GLOBALS["mock_php_input_recovery"] ?? ""); $chunk = substr($data, $this->position, $count); $this->position += strlen($chunk); return $chunk; }'
      . '  public function stream_eof(): bool { $data = (string)($GLOBALS["mock_php_input_recovery"] ?? ""); return $this->position >= strlen($data); }'
      . '  public function stream_stat(): array { return []; }'
      . '}'
      . 'stream_wrapper_unregister("php"); '
      . 'stream_wrapper_register("php", "MockPhpInputStreamRecovery"); '
      . 'ob_start(); '
      . '$c = new \\PayCal\\Controllers\\RecoveryEmailController(); '
      . '$m = ' . $method . '; '
      . '$c->{$m}(); '
      . 'stream_wrapper_restore("php"); '
      . 'echo ob_get_clean();';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);
    return $this->decodeJsonPayload((string) $output);
  }

  /**
   * Helper: Create test session
   */
  private function createTestSession(): string
  {
    $userUUID = 'test-' . bin2hex(random_bytes(4));
    $sessionHash = bin2hex(random_bytes(16));

    Database::hset(Keys::USER . ':' . $userUUID, [
      'user_uuid' => $userUUID,
      'email' => 'test@example.com',
      'full_name' => 'Test User',
      'email_verified' => '1',
    ]);

    Database::hset(Keys::SESSION . ':' . $sessionHash, [
      'user_uuid' => $userUUID,
      'created_at' => date('c'),
    ]);
    Database::expire(Keys::SESSION . ':' . $sessionHash, 3600);

    return $sessionHash;
  }

  /**
   * Helper: Seed and return a settings CSRF token for the given session's user.
   */
  private function createSettingsCsrfToken(string $sessionHash): string
  {
    $uuid = $this->getUserUUID($sessionHash);
    $token = bin2hex(random_bytes(16));
    Database::set('user:' . $uuid . ':csrf:settings:' . $token, (string) time(), 3600);
    return $token;
  }

  /**
   * Helper: Get user UUID from session
   */
  private function getUserUUID(string $sessionHash): string
  {
    $data = Database::hgetall(Keys::SESSION . ':' . $sessionHash);
    return (string) ($data['user_uuid'] ?? '');
  }

  /**
   * Helper: Clean up test data
   */
  private function cleanup(string $sessionHash): void
  {
    $uuid = $this->getUserUUID($sessionHash);
    Database::unlink(Keys::USER . ':' . $uuid);
    Database::unlink(Keys::SESSION . ':' . $sessionHash);
    Database::unlink(Keys::recoveryEmailCode($uuid));
    Database::unlink('recovery_email:resend:' . $uuid);
    Database::unlink('recovery_email:resends:' . $uuid);
  }

  // =========================================================================
  // START ENDPOINT TESTS
  // =========================================================================

  public function testStartRejectsMissingRecoveryEmail(): void
  {
    $session = $this->createTestSession();
    $response = $this->runRecoveryEmailCall('start', ['csrf_token' => $this->createSettingsCsrfToken($session)], $session);
    
    $this->assertSame('error', $response['status'] ?? null);
    $this->assertStringContainsString('missing recovery_email', strtolower($response['message'] ?? ''));
    
    $this->cleanup($session);
  }

  public function testStartRejectsInvalidEmail(): void
  {
    $session = $this->createTestSession();
    $response = $this->runRecoveryEmailCall('start', [
      'recovery_email' => 'invalid',
      'csrf_token' => $this->createSettingsCsrfToken($session),
    ], $session);
    
    $this->assertSame('error', $response['status'] ?? null);
    $this->assertStringContainsString('invalid email', strtolower($response['message'] ?? ''));
    
    $this->cleanup($session);
  }

  public function testStartRejectsUnauthenticated(): void
  {
    $response = $this->runRecoveryEmailCall('start', ['recovery_email' => 'test@example.com'], null);
    
    $this->assertSame('error', $response['status'] ?? null);
    $this->assertStringContainsString('unauthorized', strtolower($response['message'] ?? ''));
  }

  // =========================================================================
  // VERIFY ENDPOINT TESTS
  // =========================================================================

  public function testVerifyRejectsMissingCode(): void
  {
    $session = $this->createTestSession();
    $response = $this->runRecoveryEmailCall('verify', ['csrf_token' => $this->createSettingsCsrfToken($session)], $session);
    
    $this->assertSame('error', $response['status'] ?? null);
    
    $this->cleanup($session);
  }

  public function testVerifyRejectsInvalidLength(): void
  {
    $session = $this->createTestSession();
    $response = $this->runRecoveryEmailCall('verify', [
      'code' => 'ABC12',
      'csrf_token' => $this->createSettingsCsrfToken($session),
    ], $session);
    
    $this->assertSame('error', $response['status'] ?? null);
    $this->assertStringContainsString('exactly 6', strtolower($response['message'] ?? ''));
    
    $this->cleanup($session);
  }

  public function testVerifyRejectsUnauthenticated(): void
  {
    $response = $this->runRecoveryEmailCall('verify', ['code' => '123456'], null);
    
    $this->assertSame('error', $response['status'] ?? null);
    $this->assertStringContainsString('unauthorized', strtolower($response['message'] ?? ''));
  }

  public function testVerifyRejectsWhenNoCodeStored(): void
  {
    $session = $this->createTestSession();
    $response = $this->runRecoveryEmailCall('verify', [
      'code' => 'test123',
      'csrf_token' => $this->createSettingsCsrfToken($session),
    ], $session);
    
    $this->assertSame('error', $response['status'] ?? null);
    $this->assertStringContainsString('verification code', strtolower($response['message'] ?? ''));
    
    $this->cleanup($session);
  }

  public function testVerifyRejectsExpiredCode(): void
  {
    $session = $this->createTestSession();
    $uuid = $this->getUserUUID($session);
    
    // Store expired code (6 characters)
    $now = time();
    Database::hset(Keys::recoveryEmailCode($uuid), [
      'code_hash' => hash('sha256', 'ABC123'),
      'expires_at' => (string) ($now - 3600),
      'created_at' => (string) ($now - 3610),
      'verify_attempts' => '0',
    ]);
    
    $response = $this->runRecoveryEmailCall('verify', [
      'code' => 'ABC123',
      'csrf_token' => $this->createSettingsCsrfToken($session),
    ], $session);
    
    $this->assertSame('error', $response['status'] ?? null);
    $this->assertStringContainsString('expired', strtolower($response['message'] ?? ''));
    
    $this->cleanup($session);
  }

  public function testVerifyRejectsWrongCode(): void
  {
    $session = $this->createTestSession();
    $uuid = $this->getUserUUID($session);
    
    // Store valid code (6 characters)
    $now = time();
    Database::hset(Keys::recoveryEmailCode($uuid), [
      'code_hash' => hash('sha256', 'CORRECT'),
      'expires_at' => (string) ($now + 600),
      'created_at' => (string) $now,
      'verify_attempts' => '0',
    ]);
    
    $response = $this->runRecoveryEmailCall('verify', [
      'code' => 'WRONG12',
      'csrf_token' => $this->createSettingsCsrfToken($session),
    ], $session);
    
    $this->assertSame('error', $response['status'] ?? null);
    
    $this->cleanup($session);
  }

  // =========================================================================
  // RESEND ENDPOINT TESTS
  // =========================================================================

  public function testResendRejectsUnauthenticated(): void
  {
    $response = $this->runRecoveryEmailCall('resend', [], null);
    
    $this->assertSame('error', $response['status'] ?? null);
    $this->assertStringContainsString('unauthorized', strtolower($response['message'] ?? ''));
  }

  public function testResendRejectsWhenNoPendingEmail(): void
  {
    $session = $this->createTestSession();
    $response = $this->runRecoveryEmailCall('resend', ['csrf_token' => $this->createSettingsCsrfToken($session)], $session);
    
    $this->assertSame('error', $response['status'] ?? null);
    $this->assertStringContainsString('pending', strtolower($response['message'] ?? ''));
    
    $this->cleanup($session);
  }

  public function testResendEnforcesCooldown(): void
  {
    $session = $this->createTestSession();
    $uuid = $this->getUserUUID($session);
    
    // Set pending email and cooldown
    Database::hset(Keys::SESSION . ':' . $session, [
      'recovery_email_pending' => 'recovery@example.com',
    ]);
    Database::set('recovery_email:resend:' . $uuid, (string) time(), 60);
    
    $response = $this->runRecoveryEmailCall('resend', ['csrf_token' => $this->createSettingsCsrfToken($session)], $session);
    
    $this->assertSame('error', $response['status'] ?? null);
    $this->assertStringContainsString('retry', strtolower($response['message'] ?? ''));
    
    $this->cleanup($session);
  }

  public function testResendEnforcesMaxAttemptsLimit(): void
  {
    $session = $this->createTestSession();
    $uuid = $this->getUserUUID($session);
    
    // Set pending email and max attempts reached
    Database::hset(Keys::SESSION . ':' . $session, [
      'recovery_email_pending' => 'recovery@example.com',
    ]);
    
    // Set max resends
    $maxResends = (int) \PayCal\Domain\Config\SystemConfig::get('recovery_email_max_resends_per_hour') ?: 5;
    Database::set('recovery_email:resends:' . $uuid, (string) $maxResends, 3600);
    
    $response = $this->runRecoveryEmailCall('resend', ['csrf_token' => $this->createSettingsCsrfToken($session)], $session);
    
    $this->assertSame('error', $response['status'] ?? null);
    
    $this->cleanup($session);
  }
}
