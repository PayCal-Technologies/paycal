<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Controllers\ChangeEmailController;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\User;
use PHPUnit\Framework\TestCase;

/**
 * ChangeEmailControllerIntegrationTest
 *
 * Integration tests for email change endpoints.
 */
final class ChangeEmailControllerIntegrationTest extends TestCase
{
    private string $testUserUUID;
    private string $testSessionHash;
    private string $oldEmail = 'user@example.com';
    private string $newEmail = 'newuser@example.com';
    private string $recoveryEmail = 'recovery@example.com';

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user with recovery email verified
        $this->testUserUUID = 'test-user-' . bin2hex(random_bytes(8));
        Database::hset(Keys::USER . ':' . $this->testUserUUID, [
            'user_uuid' => $this->testUserUUID,
            'email' => $this->oldEmail,
            'full_name' => 'Test User',
            'email_verified' => '1',
            'auth_level' => (string) AuthLevel::USER->value,
            'recovery_email' => $this->recoveryEmail,
            'recovery_email_verified' => '1',
            'recovery_email_verified_at' => date('c'),
            'recovery_email_last_sent_at' => '',
            'recovery_email_verify_attempts' => '0',
        ]);

        // Create user session
        $this->testSessionHash = hash('sha256', bin2hex(random_bytes(32)));
        Database::hset(Keys::SESSION . ':' . $this->testSessionHash, [
            'user_uuid' => $this->testUserUUID,
            'created_at' => date('c'),
            'auth_method' => 'passkey',
            'auth_strength' => 'strong',
            'passkey_stepup_at' => (string) time(),
        ]);
        Database::expire(Keys::SESSION . ':' . $this->testSessionHash, 3600);

        $_COOKIE['PAYCAL_AUTH'] = $this->testSessionHash;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
    }

    protected function tearDown(): void
    {
        Database::unlink(Keys::USER . ':' . $this->testUserUUID);
        Database::unlink(Keys::SESSION . ':' . $this->testSessionHash);
        
        // Clean up email indices
        Database::unlink(Keys::EMAIL . ':' . $this->oldEmail);
        Database::unlink(Keys::EMAIL . ':' . $this->newEmail);
        
        // Clean up rate limit keys
        Database::unlink('email_change:start_count:daily:' . $this->testUserUUID);
        Database::unlink('change_email:starts:' . $this->testUserUUID);
        Database::unlink('email_change:resend_cooldown:' . $this->testUserUUID);
        Database::unlink('email_change:resend_count:hourly:' . $this->testUserUUID);
        foreach (Database::scanKeys('user:' . $this->testUserUUID . ':csrf:settings:*') as $key) {
            Database::unlink((string) $key);
        }
        foreach (Database::scanKeys('email_change:txn:*') as $key) {
            $userUuid = (string) Database::hget((string) $key, 'user_uuid');
            if ($userUuid === $this->testUserUUID) {
                Database::unlink((string) $key);
            }
        }
        
        unset($_COOKIE['PAYCAL_AUTH']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['CONTENT_TYPE']);
        unset($_POST);

        parent::tearDown();
    }

    private function createSettingsCsrfToken(): string
    {
        $nonce = bin2hex(random_bytes(32));
        Database::set('user:' . $this->testUserUUID . ':csrf:settings:' . $nonce, (string) time(), 3600);

        return $nonce;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function withSettingsCsrfToken(array $payload): array
    {
        $payload['csrf_token'] = $this->createSettingsCsrfToken();

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizePostPayload(array $payload): array
    {
        return json_decode(json_encode($payload, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    private function callChangeEmailEndpoint(string $method): array
    {
        $controller = new ChangeEmailController();
        ob_start();
        $controller->{$method}();
        $output = ob_get_clean();

        $response = json_decode((string) $output, true);
        $this->assertIsArray($response);

        return $response;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $server
     * @return array<string, mixed>
     */
    private function invokeChangeEmailJson(string $method, array $payload, array $server = []): array
    {
        $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
        $methodLiteral = var_export($method, true);
        $sessionHash = var_export($this->testSessionHash, true);
        $json = var_export((string) json_encode($payload), true);
        $serverLiteral = var_export($server, true);

        $script = <<<'PHP'
if (!class_exists('ChangeEmailControllerInputStream')) {
  final class ChangeEmailControllerInputStream
  {
    public mixed $context;
    private int $position = 0;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
      $this->position = 0;
      return true;
    }

    public function stream_read(int $count): string
    {
      $data = (string) ($GLOBALS['change_email_controller_input'] ?? '');
      $chunk = substr($data, $this->position, $count);
      $this->position += strlen($chunk);
      return $chunk;
    }

    public function stream_eof(): bool
    {
      $data = (string) ($GLOBALS['change_email_controller_input'] ?? '');
      return $this->position >= strlen($data);
    }

    public function stream_stat(): array
    {
      return [];
    }
  }
}
PHP;

        $script .= ' if (!defined("PHPUNIT_COMPOSER_INSTALL")) { define("PHPUNIT_COMPOSER_INSTALL", "1"); }'
            . ' require ' . $bootstrap . ';'
            . ' $GLOBALS["change_email_controller_input"] = ' . $json . ';'
            . ' $_COOKIE["PAYCAL_AUTH"] = ' . $sessionHash . ';'
            . ' $_SERVER["REQUEST_METHOD"] = "POST";'
            . ' $_SERVER["REMOTE_ADDR"] = "127.0.0.1";'
            . ' $_SERVER["CONTENT_TYPE"] = "application/json";'
            . ' $_SERVER = array_merge($_SERVER, ' . $serverLiteral . ');'
            . ' stream_wrapper_unregister("php");'
            . ' stream_wrapper_register("php", ChangeEmailControllerInputStream::class);'
            . ' $method = ' . $methodLiteral . ';'
            . ' ob_start();'
            . ' try { $controller = new \PayCal\Controllers\ChangeEmailController(); $controller->{$method}(); $out = ob_get_clean(); }'
            . ' finally { stream_wrapper_restore("php"); unset($GLOBALS["change_email_controller_input"]); }'
            . ' $decoded = json_decode($out, true);'
            . ' if (is_array($decoded)) { $decoded["__http_code"] = (int) http_response_code(); echo json_encode($decoded); } else { echo $out; }';

        $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
        $this->assertNotFalse($output, 'Controller subprocess failed.');

        $decoded = json_decode((string) $output, true);
        $this->assertIsArray($decoded, 'Expected controller JSON output: ' . (string) $output);

        return $decoded;
    }

    /**
     * Test change email start requires authentication
     */
    public function testStartRequiresAuthentication(): void
    {
        unset($_COOKIE['PAYCAL_AUTH']);
        $_POST = json_decode(json_encode([
            'new_email' => $this->newEmail,
            'stepup_assertion' => 'mock-assertion',
        ], JSON_THROW_ON_ERROR), true);

        $controller = new ChangeEmailController();
        ob_start();
        $controller->start();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? false);
    }

    /**
     * Test change email start accepts valid new email
     */
    public function testStartAcceptsValidNewEmail(): void
    {
        $_POST = $this->normalizePostPayload($this->withSettingsCsrfToken([
            'new_email' => $this->newEmail,
        ]));

        $controller = new ChangeEmailController();
        ob_start();
        $controller->start();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertTrue($response['success'] ?? false);
        $this->assertArrayHasKey('txn_id', $response);
    }

    /**
     * Test change email start accepts a production-shaped JSON body CSRF token.
     */
    public function testStartAcceptsJsonBodySettingsCsrfToken(): void
    {
        $response = $this->invokeChangeEmailJson('start', [
            'new_email' => $this->newEmail,
            'csrf_token' => $this->createSettingsCsrfToken(),
        ]);

        $this->assertSame('success', $response['status'] ?? null, json_encode($response));
        $this->assertSame(200, (int) ($response['__http_code'] ?? 0));
        $this->assertArrayHasKey('txn_id', $response);
    }

    /**
     * Test change email start accepts a valid settings CSRF header.
     */
    public function testStartAcceptsJsonRequestWithSettingsCsrfHeader(): void
    {
        $response = $this->invokeChangeEmailJson(
            'start',
            ['new_email' => $this->newEmail],
            ['HTTP_X_CSRF_TOKEN' => $this->createSettingsCsrfToken()],
        );

        $this->assertSame('success', $response['status'] ?? null, json_encode($response));
        $this->assertSame(200, (int) ($response['__http_code'] ?? 0));
        $this->assertArrayHasKey('txn_id', $response);
    }

    /**
     * Test change email start rejects production-shaped JSON without CSRF.
     */
    public function testStartRejectsJsonRequestWithoutSettingsCsrfToken(): void
    {
        $response = $this->invokeChangeEmailJson('start', [
            'new_email' => $this->newEmail,
        ]);

        $this->assertSame('error', $response['status'] ?? null, json_encode($response));
        $this->assertSame(403, (int) ($response['__http_code'] ?? 0));
        $this->assertStringContainsString('csrf', strtolower((string) ($response['message'] ?? '')));
    }

    /**
     * Test change email start rejects invalid email
     */
    public function testStartRejectsInvalidEmail(): void
    {
        $_POST = $this->normalizePostPayload($this->withSettingsCsrfToken([
            'new_email' => 'not-an-email',
        ]));

        $controller = new ChangeEmailController();
        ob_start();
        $controller->start();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? false);
    }

    /**
     * Test change email start rejects email already in use
     */
    public function testStartRejectsEmailInUse(): void
    {
        // Create another user with the target email
        $otherUserUUID = 'test-user-' . bin2hex(random_bytes(8));
        Database::hset(Keys::USER . ':' . $otherUserUUID, [
            'user_uuid' => $otherUserUUID,
            'email' => $this->newEmail,
            'full_name' => 'Other User',
            'email_verified' => '1',
        ]);
        Database::hset(Keys::EMAIL . ':' . $this->newEmail, [
            'user_uuid' => $otherUserUUID,
        ]);

        $_POST = $this->normalizePostPayload($this->withSettingsCsrfToken([
            'new_email' => $this->newEmail,
        ]));

        $controller = new ChangeEmailController();
        ob_start();
        $controller->start();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? false);

        // Clean up
        Database::unlink(Keys::USER . ':' . $otherUserUUID);
    }

    /**
     * Test change email start enforces daily rate limit
     */
    public function testStartEnforcesDailyRateLimit(): void
    {
        $dailyCountKey = 'change_email:starts:' . $this->testUserUUID;
        $maxStarts = (int) SystemConfig::get('email_change_max_new_email_starts_per_day');

        // Set counter to max
        Database::set($dailyCountKey, (string)$maxStarts);
        Database::expire($dailyCountKey, 86400);

        $_POST = $this->normalizePostPayload($this->withSettingsCsrfToken([
            'new_email' => $this->newEmail,
        ]));

        $controller = new ChangeEmailController();
        ob_start();
        $controller->start();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? false);
        $this->assertStringContainsString('max email change attempts', strtolower($response['message'] ?? ''));
    }

    /**
     * Test change email verify requires valid codes
     */
    public function testVerifyRejectsInvalidCodes(): void
    {
        $_POST = $this->normalizePostPayload($this->withSettingsCsrfToken([
            'txn_id' => 'nonexistent-txn',
            'old_code' => 'invalid',
            'new_code' => 'invalid',
        ]));

        $controller = new ChangeEmailController();
        ob_start();
        $controller->verify();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? false);
    }

    /**
     * Test change email cancel marks transaction as cancelled
     */
    public function testCancelMarksTransactionCancelled(): void
    {
        // First create a transaction via start endpoint
        $_POST = $this->normalizePostPayload($this->withSettingsCsrfToken([
            'new_email' => $this->newEmail,
        ]));

        $startController = new ChangeEmailController();
        ob_start();
        $startController->start();
        $startOutput = ob_get_clean();

        $startResponse = json_decode($startOutput, true);
        $this->assertTrue($startResponse['success'] ?? false);
        $txnId = $startResponse['txn_id'] ?? null;
        $this->assertNotNull($txnId);

        // Now cancel it
        $_POST = $this->normalizePostPayload($this->withSettingsCsrfToken([
            'txn_id' => $txnId,
        ]));

        $cancelController = new ChangeEmailController();
        ob_start();
        $cancelController->cancel();
        $cancelOutput = ob_get_clean();

        $cancelResponse = json_decode($cancelOutput, true);
        $this->assertIsArray($cancelResponse);
        $this->assertTrue($cancelResponse['success'] ?? false);
    }

    /**
     * Test change email resend enforces cooldown
     */
    public function testResendEnforcesCooldown(): void
    {
        // Create a pending transaction via start first.
        $_POST = $this->normalizePostPayload($this->withSettingsCsrfToken([
            'new_email' => $this->newEmail,
        ]));

        $startController = new ChangeEmailController();
        ob_start();
        $startController->start();
        $startOutput = ob_get_clean();

        $startResponse = json_decode((string) $startOutput, true);
        $this->assertIsArray($startResponse);
        $this->assertTrue($startResponse['success'] ?? false);
        $txnId = (string) ($startResponse['txn_id'] ?? '');
        $this->assertNotSame('', $txnId);

        $_POST = $this->normalizePostPayload($this->withSettingsCsrfToken([
            'txn_id' => $txnId,
        ]));

        $controller = new ChangeEmailController();
        ob_start();
        $controller->resend();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? false);
        $this->assertStringContainsString('retry in', strtolower($response['message'] ?? ''));
    }

    /**
     * Test change email mutations reject browser-forged requests without settings CSRF.
     */
    public function testMutationsRejectMissingSettingsCsrfToken(): void
    {
        $cases = [
            'start' => [
                'new_email' => $this->newEmail,
            ],
            'verify' => [
                'txn_id' => 'nonexistent-txn',
                'old_code' => 'invalid',
                'new_code' => 'invalid',
            ],
            'resend' => [
                'txn_id' => 'nonexistent-txn',
            ],
            'cancel' => [
                'txn_id' => 'nonexistent-txn',
            ],
        ];

        foreach ($cases as $method => $payload) {
            $_POST = $this->normalizePostPayload($payload);

            $response = $this->callChangeEmailEndpoint($method);

            $this->assertFalse($response['success'] ?? false, $method . ' should reject missing CSRF token.');
            $this->assertStringContainsString('csrf', strtolower((string) ($response['message'] ?? '')));
        }
    }
}
