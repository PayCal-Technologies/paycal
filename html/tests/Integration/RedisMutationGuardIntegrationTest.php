<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Infrastructure\Resilience\RedisReliabilityService;
use PHPUnit\Framework\TestCase;

final class RedisMutationGuardIntegrationTest extends TestCase
{
  protected function setUp(): void
  {
    RedisReliabilityService::setMutationFreeze(false, '');
    RedisReliabilityService::resetCircuitBreaker();
  }

  protected function tearDown(): void
  {
    RedisReliabilityService::setMutationFreeze(false, '');
    RedisReliabilityService::resetCircuitBreaker();
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
  private function runControllerCall(string $controllerFqcn, string $method, string $requestMethod = 'POST', ?string $sessionHash = null): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $controllerFqcn = var_export($controllerFqcn, true);
    $method = var_export($method, true);
    $requestMethod = var_export($requestMethod, true);

    $cookieSetup = '';
    if ($sessionHash !== null) {
      $cookieSetup = '$_COOKIE["PAYCAL_AUTH"] = ' . var_export($sessionHash, true) . '; ';
    }

    $script = 'require ' . $bootstrap . '; '
      . '$_SERVER["REQUEST_METHOD"] = ' . $requestMethod . '; '
      . '$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; '
      . $cookieSetup
      . 'ob_start(); '
      . '$class = ' . $controllerFqcn . '; '
      . '$m = ' . $method . '; '
      . '$c = new $class(); '
      . '$c->{$m}(); '
      . 'echo ob_get_clean();';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);

    return $this->decodeJsonPayload((string) $output);
  }

  /**
   * @return array{userUUID: string, sessionHash: string}
   */
  private function createAuthenticatedSession(bool $emailVerified = true): array
  {
    $userUUID = 'test-mutation-guard-' . bin2hex(random_bytes(6));
    $sessionHash = bin2hex(random_bytes(16));

    Database::hset(Keys::USER . ':' . $userUUID, [
      'user_uuid' => $userUUID,
      'email' => 'guard-' . bin2hex(random_bytes(4)) . '@example.com',
      'full_name' => 'Mutation Guard User',
      'email_verified' => $emailVerified ? '1' : '0',
    ]);

    Database::hset(Keys::SESSION . ':' . $sessionHash, [
      'user_uuid' => $userUUID,
      'created_at' => (string) time(),
      'last_activity' => (string) time(),
    ]);
    Database::expire(Keys::SESSION . ':' . $sessionHash, 3600);

    return ['userUUID' => $userUUID, 'sessionHash' => $sessionHash];
  }

  private function cleanupAuthenticatedSession(string $userUUID, string $sessionHash): void
  {
    Database::unlink(Keys::SESSION . ':' . $sessionHash);
    Database::unlink(Keys::USER . ':' . $userUUID);
  }

  public function testDekWriteBlockedWhenMutationFreezeEnabled(): void
  {
    $session = $this->createAuthenticatedSession();
    RedisReliabilityService::setMutationFreeze(true, 'integration-test-freeze');

    try {
      $decoded = $this->runControllerCall(
        '\\PayCal\\Controllers\\DEKController',
        'postWrappedDek',
        'POST',
        $session['sessionHash']
      );

      $this->assertSame('error', $decoded['status'] ?? null);
      $this->assertStringContainsString('reliability guard blocked mutation', strtolower((string) ($decoded['message'] ?? '')));
      $guard = $decoded['redis_guard'] ?? null;
      $this->assertIsArray($guard);
      $this->assertSame('MUTATION_FREEZE', $guard['code'] ?? null);
      $this->assertTrue((bool) ($guard['freeze'] ?? false));
    } finally {
      $this->cleanupAuthenticatedSession($session['userUUID'], $session['sessionHash']);
    }
  }

  public function testDekWriteBlockedWhenCircuitBreakerOpen(): void
  {
    $session = $this->createAuthenticatedSession();
    RedisReliabilityService::setMutationFreeze(false, '');
    RedisReliabilityService::openCircuitBreaker('integration-test-breaker-open');

    try {
      $decoded = $this->runControllerCall(
        '\\PayCal\\Controllers\\DEKController',
        'postWrappedDek',
        'POST',
        $session['sessionHash']
      );

      $this->assertSame('error', $decoded['status'] ?? null);
      $guard = $decoded['redis_guard'] ?? null;
      $this->assertIsArray($guard);
      $this->assertSame('REDIS_BREAKER_OPEN', $guard['code'] ?? null);
    } finally {
      $this->cleanupAuthenticatedSession($session['userUUID'], $session['sessionHash']);
    }
  }

  public function testAccountRecoveryKeyBlockedWhenMutationFreezeEnabled(): void
  {
    $session = $this->createAuthenticatedSession();
    RedisReliabilityService::setMutationFreeze(true, 'integration-test-freeze');

    try {
      $decoded = $this->runControllerCall(
        '\\PayCal\\Controllers\\AccountController',
        'createRecoveryKey',
        'POST',
        $session['sessionHash']
      );

      $this->assertSame('error', $decoded['status'] ?? null);
      $this->assertStringContainsString('reliability guard blocked mutation', strtolower((string) ($decoded['message'] ?? '')));
      $guard = $decoded['redis_guard'] ?? null;
      $this->assertIsArray($guard);
      $this->assertSame('MUTATION_FREEZE', $guard['code'] ?? null);
    } finally {
      $this->cleanupAuthenticatedSession($session['userUUID'], $session['sessionHash']);
    }
  }

  public function testResendVerificationBlockedWhenMutationFreezeEnabled(): void
  {
    $session = $this->createAuthenticatedSession(false);
    RedisReliabilityService::setMutationFreeze(true, 'integration-test-freeze');

    try {
      $decoded = $this->runControllerCall(
        '\\PayCal\\Controllers\\EmailVerificationController',
        'resendVerification',
        'POST',
        $session['sessionHash']
      );

      $this->assertSame('error', $decoded['status'] ?? null);
      $this->assertStringContainsString('reliability guard blocked mutation', strtolower((string) ($decoded['message'] ?? '')));
      $guard = $decoded['redis_guard'] ?? null;
      $this->assertIsArray($guard);
      $this->assertSame('MUTATION_FREEZE', $guard['code'] ?? null);
    } finally {
      $this->cleanupAuthenticatedSession($session['userUUID'], $session['sessionHash']);
    }
  }

  public function testDeleteAccountBlockedWhenMutationFreezeEnabled(): void
  {
    $session = $this->createAuthenticatedSession();
    RedisReliabilityService::setMutationFreeze(true, 'integration-test-freeze');

    try {
      $decoded = $this->runControllerCall('\\PayCal\\Controllers\\SettingsController', 'deleteAccount', 'POST', $session['sessionHash']);

      $this->assertSame('error', $decoded['status'] ?? null);
      $this->assertStringContainsString('reliability guard blocked mutation', strtolower((string) ($decoded['message'] ?? '')));
      $guard = $decoded['redis_guard'] ?? null;
      $this->assertIsArray($guard);
      $this->assertSame('MUTATION_FREEZE', $guard['code'] ?? null);
    } finally {
      $this->cleanupAuthenticatedSession($session['userUUID'], $session['sessionHash']);
    }
  }
}
