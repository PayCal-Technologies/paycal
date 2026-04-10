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
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
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
        Database::unlink('email_change:resend_cooldown:' . $this->testUserUUID);
        Database::unlink('email_change:resend_count:hourly:' . $this->testUserUUID);
        
        unset($_COOKIE['PAYCAL_AUTH']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['CONTENT_TYPE']);
        unset($_POST);

        parent::tearDown();
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
        $_POST = json_decode(json_encode([
            'new_email' => $this->newEmail,
        ], JSON_THROW_ON_ERROR), true);

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
     * Test change email start rejects invalid email
     */
    public function testStartRejectsInvalidEmail(): void
    {
        $_POST = json_decode(json_encode([
            'new_email' => 'not-an-email',
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

        $_POST = json_decode(json_encode([
            'new_email' => $this->newEmail,
        ], JSON_THROW_ON_ERROR), true);

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

        $_POST = json_decode(json_encode([
            'new_email' => $this->newEmail,
        ], JSON_THROW_ON_ERROR), true);

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
        $_POST = json_decode(json_encode([
            'transaction_id' => 'nonexistent-txn',
            'old_code' => 'invalid',
            'new_code' => 'invalid',
        ], JSON_THROW_ON_ERROR), true);

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
        $_POST = json_decode(json_encode([
            'new_email' => $this->newEmail,
        ], JSON_THROW_ON_ERROR), true);

        $startController = new ChangeEmailController();
        ob_start();
        $startController->start();
        $startOutput = ob_get_clean();

        $startResponse = json_decode($startOutput, true);
        $this->assertTrue($startResponse['success'] ?? false);
        $txnId = $startResponse['txn_id'] ?? null;
        $this->assertNotNull($txnId);

        // Now cancel it
        $_POST = json_decode(json_encode([
            'txn_id' => $txnId,
        ], JSON_THROW_ON_ERROR), true);

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
        $_POST = json_decode(json_encode([
            'new_email' => $this->newEmail,
        ], JSON_THROW_ON_ERROR), true);

        $startController = new ChangeEmailController();
        ob_start();
        $startController->start();
        $startOutput = ob_get_clean();

        $startResponse = json_decode((string) $startOutput, true);
        $this->assertIsArray($startResponse);
        $this->assertTrue($startResponse['success'] ?? false);
        $txnId = (string) ($startResponse['txn_id'] ?? '');
        $this->assertNotSame('', $txnId);

        $_POST = json_decode(json_encode([
            'txn_id' => $txnId,
        ], JSON_THROW_ON_ERROR), true);

        $controller = new ChangeEmailController();
        ob_start();
        $controller->resend();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? false);
        $this->assertStringContainsString('retry in', strtolower($response['message'] ?? ''));
    }
}
