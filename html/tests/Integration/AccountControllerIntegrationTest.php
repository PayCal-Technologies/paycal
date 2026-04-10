<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Domain\Authentication;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\User;
use PayCal\Domain\UserFields;
use PayCal\Domain\Enums\AuthLevel;
use PHPUnit\Framework\TestCase;

/**
 * AccountControllerIntegrationTest
 *
 * Integration tests for account management endpoints.
 */
final class AccountControllerIntegrationTest extends TestCase
{
    private string $testUserUUID;
    private string $testSessionHash;

    /**
     * Execute AccountController::bootstrap() in a subprocess so Response::exit
     * does not terminate the current PHPUnit process.
     *
     * @return array<string, mixed>
     */
    private function runBootstrapCall(): array
    {
        $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
        $sessionHash = var_export($this->testSessionHash, true);
        $script = 'require ' . $bootstrap . '; '
            . '$_COOKIE["PAYCAL_AUTH"] = ' . $sessionHash . '; '
            . '$_SERVER["REQUEST_METHOD"] = "GET"; '
            . '$controller = new \\PayCal\\Controllers\\AccountController(); '
            . '$controller->bootstrap();';

        $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
        $this->assertNotFalse($output);

        $response = json_decode((string) $output, true);
        $this->assertIsArray($response, 'Bootstrap response must decode as JSON');

        return $response;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->testUserUUID = 'test-user-' . bin2hex(random_bytes(8));
        $testEmail = 'test-' . bin2hex(random_bytes(4)) . '@example.com';

        Database::hset(Keys::USER . ':' . $this->testUserUUID, [
            'user_uuid' => $this->testUserUUID,
            'email' => $testEmail,
            'full_name' => 'Test User',
            'email_verified' => '1',
            'auth_level' => (string) AuthLevel::USER->value,
            'password_hash' => password_hash('testpass123', PASSWORD_DEFAULT),
        ]);

        // Create test session
        $this->testSessionHash = hash('sha256', bin2hex(random_bytes(32)));
        Database::hset(Keys::SESSION . ':' . $this->testSessionHash, [
            'user_uuid' => $this->testUserUUID,
            'created_at' => date('c'),
        ]);
        Database::expire(Keys::SESSION . ':' . $this->testSessionHash, 3600);

        // Set session cookie
        $_COOKIE['PAYCAL_AUTH'] = $this->testSessionHash;
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        // Clean up test data
        if (!empty($this->testUserUUID)) {
            Database::unlink(Keys::USER . ':' . $this->testUserUUID);
        }
        if (!empty($this->testSessionHash)) {
            Database::unlink(Keys::SESSION . ':' . $this->testSessionHash);
        }

        unset($_COOKIE['PAYCAL_AUTH']);
        unset($_SERVER['REQUEST_METHOD']);

        parent::tearDown();
    }

    /**
     * Test bootstrap endpoint generates encryption salt
     */
    public function testBootstrapGeneratesEncryptionSalt(): void
    {
        $response = $this->runBootstrapCall();

        $this->assertIsArray($response);
        $this->assertSame('success', $response['status'] ?? null);
        $this->assertArrayHasKey('encryptionSalt', $response);
        $this->assertNotEmpty($response['encryptionSalt'] ?? '');
        
        // Verify salt is base64 encoded
        $salt = $response['encryptionSalt'] ?? '';
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $salt);
    }

    /**
     * Test bootstrap endpoint persists salt to database
     */
    public function testBootstrapPersistsSaltToDatabase(): void
    {
        $this->runBootstrapCall();

        // Verify salt was persisted
        $user = User::getByUUID($this->testUserUUID);
        $this->assertNotEmpty($user->encryption_salt);
        $this->assertIsString($user->encryption_salt);
    }

    /**
     * Test bootstrap endpoint returns same salt on subsequent calls
     */
    public function testBootstrapReturnsSameSaltOnMultipleCalls(): void
    {
        // First call
        $response1 = $this->runBootstrapCall();
        $salt1 = $response1['encryptionSalt'] ?? '';

        // Second call
        $response2 = $this->runBootstrapCall();
        $salt2 = $response2['encryptionSalt'] ?? '';

        $this->assertSame($salt1, $salt2, 'Bootstrap should return same salt on multiple calls');
    }

    /**
     * Test bootstrap includes crypto version
     */
    public function testBootstrapIncludesCryptoVersion(): void
    {
        $response = $this->runBootstrapCall();

        $this->assertArrayHasKey('cryptoVersion', $response);
        $this->assertGreaterThanOrEqual(1, (int) ($response['cryptoVersion'] ?? 0));
    }

    /**
     * Test bootstrap includes auth strength information
     */
    public function testBootstrapIncludesAuthStrength(): void
    {
        $response = $this->runBootstrapCall();

        $this->assertArrayHasKey('authStrength', $response);
        $this->assertArrayHasKey('passkeyEnabled', $response);
        $this->assertIsBool($response['passkeyEnabled'] ?? null);
    }
}
