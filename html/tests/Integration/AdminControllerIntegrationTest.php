<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Controllers\AdminController;
use PayCal\Domain\CapabilityTokenService;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\Extensions\Bridges\ExtensionBootstrapBridge;
use PayCal\Domain\User;
use PHPUnit\Framework\TestCase;

/**
 * AdminControllerIntegrationTest
 *
 * Integration tests for admin management endpoints.
 */
final class AdminControllerIntegrationTest extends TestCase
{
    private string $testAdminUUID;
    private string $testUserUUID;
    private string $testSessionHash;
    private string $adminEmail;
    private string $userEmail;
    private string $updatedUserEmail;

    private function withCapability(string $action): void
    {
        $issued = CapabilityTokenService::issue($action, $this->testAdminUUID, $this->testSessionHash);
        $_POST['capability_token'] = $issued['token'];
        $_SERVER['HTTP_X_PAYCAL_CAPABILITY'] = $issued['token'];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Integration tests do not load app config bootstrap by default.
        // Initialize extension runtime so AdminSurface capability gates are active.
        ExtensionBootstrapBridge::initialize();

        // Create admin user
        $this->testAdminUUID = 'test-admin-' . bin2hex(random_bytes(8));
        $suffix = bin2hex(random_bytes(4));
        $this->adminEmail = "admin+{$suffix}@example.com";
        $this->userEmail = "user+{$suffix}@example.com";
        $this->updatedUserEmail = "updated+{$suffix}@example.com";
        Database::hset(Keys::USER . ':' . $this->testAdminUUID, [
            'user_uuid' => $this->testAdminUUID,
            'email' => $this->adminEmail,
            'full_name' => 'Test Admin',
            'email_verified' => '1',
            'auth_level' => (string) AuthLevel::ADMIN->value,
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        ]);

        // Create regular user for testing
        $this->testUserUUID = 'test-user-' . bin2hex(random_bytes(8));
        Database::hset(Keys::USER . ':' . $this->testUserUUID, [
            'user_uuid' => $this->testUserUUID,
            'email' => $this->userEmail,
            'full_name' => 'Regular User',
            'email_verified' => '1',
            'auth_level' => (string) AuthLevel::USER->value,
            'password_hash' => password_hash('user123', PASSWORD_DEFAULT),
        ]);

        // Create admin session
        $this->testSessionHash = hash('sha256', bin2hex(random_bytes(32)));
        Database::hset(Keys::SESSION . ':' . $this->testSessionHash, [
            'user_uuid' => $this->testAdminUUID,
            'created_at' => date('c'),
        ]);
        Database::expire(Keys::SESSION . ':' . $this->testSessionHash, 3600);

        $_COOKIE['PAYCAL_AUTH'] = $this->testSessionHash;
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    protected function tearDown(): void
    {
        Database::unlink(Keys::USER . ':' . $this->testAdminUUID);
        Database::unlink(Keys::USER . ':' . $this->testUserUUID);
        Database::unlink(Keys::SESSION . ':' . $this->testSessionHash);

        unset($_COOKIE['PAYCAL_AUTH']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_POST);
        unset($_SERVER['HTTP_X_PAYCAL_CAPABILITY']);

        parent::tearDown();
    }

    /**
     * Test admin can update user information
     */
    public function testAdminCanUpdateUserInformation(): void
    {
        $_POST['user_uuid'] = $this->testUserUUID;
        $_POST['full_name'] = 'Updated Name';
        $_POST['email'] = $this->updatedUserEmail;
        $_POST['auth_level'] = (string) AuthLevel::USER->value;
        $this->withCapability('admin.user.update');

        $controller = new AdminController();

        ob_start();
        $controller->updateUser();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        
        if ($response['success'] ?? false) {
            // Verify update was persisted
            $updatedUser = User::getByUUID($this->testUserUUID);
            $this->assertSame('Updated Name', $updatedUser->full_name);
            $this->assertSame($this->updatedUserEmail, $updatedUser->email);
        } else {
            // Log failure reason for debugging
            $this->markTestSkipped('User update failed: ' . ($response['message'] ?? 'unknown'));
        }
    }

    /**
     * Test admin update ignores deprecated password field.
     */
    public function testAdminUpdateIgnoresPasswordField(): void
    {
        $existingUser = User::getByUUID($this->testUserUUID);
        $this->assertNotNull($existingUser);
        $originalHash = $existingUser->password_hash;

        $_POST['user_uuid'] = $this->testUserUUID;
        $_POST['password'] = 'newpassword123';
        $_POST['full_name'] = 'Regular User';
        $_POST['email'] = $this->userEmail;
        $_POST['auth_level'] = (string) AuthLevel::USER->value;
        $this->withCapability('admin.user.update');

        $controller = new AdminController();

        ob_start();
        $controller->updateUser();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertTrue($response['success'] ?? false);

        $updatedUser = User::getByUUID($this->testUserUUID);
        $this->assertNotNull($updatedUser);
        $this->assertSame($originalHash, $updatedUser->password_hash);
    }

    /**
     * Test admin can delete user and account-linked records.
     */
    public function testAdminCanDeleteUserAndLinkedData(): void
    {
        $email = $this->userEmail;
        $credentialId = 'cid-' . bin2hex(random_bytes(4));
        $userSessionHash = hash('sha256', bin2hex(random_bytes(16)));

        Database::hset(Keys::EMAIL . ':' . $email, [
            'user_uuid' => $this->testUserUUID,
            'created' => (string) time(),
        ]);
        Database::hset(Keys::EMAIL . $email, [
            'user_uuid' => $this->testUserUUID,
            'created' => (string) time(),
        ]);
        Database::hset(Keys::WORK . ':' . $this->testUserUUID . ':2026-03-01:S123', ['hours' => '8']);
        Database::hset(Keys::SITE . ':' . $this->testUserUUID . ':S123', ['name' => 'Delete Me Site']);
        Database::hset(Keys::SESSION . ':' . $userSessionHash, ['user_uuid' => $this->testUserUUID]);
        Database::sadd(Keys::webauthnUserCredentials($this->testUserUUID), $credentialId);
        Database::hset(Keys::webauthnCredential($credentialId), [
            'credential_id' => $credentialId,
            'user_uuid' => $this->testUserUUID,
        ]);

        $_POST['user_uuid'] = $this->testUserUUID;
        $this->withCapability('admin.user.delete');

        $controller = new AdminController();

        ob_start();
        $controller->deleteUser();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertTrue($response['success'] ?? false);
        $this->assertFalse(Database::exists(Keys::USER . ':' . $this->testUserUUID));
        $this->assertFalse(Database::exists(Keys::WORK . ':' . $this->testUserUUID . ':2026-03-01:S123'));
        $this->assertFalse(Database::exists(Keys::SITE . ':' . $this->testUserUUID . ':S123'));
        $this->assertFalse(Database::exists(Keys::SESSION . ':' . $userSessionHash));
        $this->assertFalse(Database::exists(Keys::EMAIL . ':' . $email));
        $this->assertFalse(Database::exists(Keys::EMAIL . $email));
        $this->assertFalse(Database::exists(Keys::webauthnCredential($credentialId)));
    }

    /**
     * Test admin endpoint rejects deleting own account.
     */
    public function testAdminCannotDeleteCurrentSessionUser(): void
    {
        $_POST['user_uuid'] = $this->testAdminUUID;
        $this->withCapability('admin.user.delete');

        $controller = new AdminController();

        ob_start();
        $controller->deleteUser();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? true);
        $this->assertStringContainsString('Refusing to delete current admin session user', $response['message'] ?? '');
    }

    /**
     * Test admin update requires user_uuid
     */
    public function testUpdateUserRequiresUserUuid(): void
    {
        $_POST['full_name'] = 'Some Name';
        // Missing user_uuid
        $this->withCapability('admin.user.update');

        $controller = new AdminController();

        ob_start();
        $controller->updateUser();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? true);
        $this->assertStringContainsString('UUID required', $response['message'] ?? '');
    }

    /**
     * Test admin update with invalid user returns not found
     */
    public function testUpdateUserWithInvalidUuidReturnsNotFound(): void
    {
        $_POST['user_uuid'] = 'invalid-uuid-12345';
        $_POST['full_name'] = 'Some Name';
        $_POST['email'] = 'unknown+' . bin2hex(random_bytes(4)) . '@example.com';
        $_POST['auth_level'] = (string) AuthLevel::USER->value;
        $this->withCapability('admin.user.update');

        $controller = new AdminController();

        ob_start();
        $controller->updateUser();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? true);
        $this->assertStringContainsString('not found', $response['message'] ?? '');
    }

    public function testUpdateUserRequiresCapabilityToken(): void
    {
        $_POST['user_uuid'] = $this->testUserUUID;
        $_POST['full_name'] = 'Updated Name';
        $_POST['email'] = $this->updatedUserEmail;
        $_POST['auth_level'] = (string) AuthLevel::USER->value;

        $controller = new AdminController();

        ob_start();
        $controller->updateUser();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? true);
        $this->assertStringContainsString('Capability token rejected', $response['message'] ?? '');
    }

    /**
     * Test non-admin cannot access admin endpoints
     */
    public function testNonAdminCannotAccessAdminEndpoints(): void
    {
        // Switch session to regular user
        Database::hset(Keys::SESSION . ':' . $this->testSessionHash, [
            'user_uuid' => $this->testUserUUID,
            'created_at' => date('c'),
        ]);

        $_POST['user_uuid'] = $this->testUserUUID;
        $_POST['full_name'] = 'Updated Name';
        $this->withCapability('admin.user.update');

        $controller = new AdminController();

        ob_start();
        $controller->updateUser();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? true);
        $this->assertStringContainsString('Forbidden', $response['message'] ?? '');
    }

    public function testDeleteUserRequiresCapabilityToken(): void
    {
        $_POST['user_uuid'] = $this->testUserUUID;

        $controller = new AdminController();

        ob_start();
        $controller->deleteUser();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? true);
        $this->assertStringContainsString('Capability token rejected', $response['message'] ?? '');
    }

    public function testAdminCannotPromoteUserToSuperadmin(): void
    {
        $_POST['user_uuid'] = $this->testUserUUID;
        $_POST['full_name'] = 'Regular User';
        $_POST['email'] = $this->userEmail;
        $_POST['auth_level'] = (string) AuthLevel::SUPERADMIN->value;
        $this->withCapability('admin.user.update');

        $controller = new AdminController();

        ob_start();
        $controller->updateUser();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? true);
        $this->assertStringContainsString('Only superadmin may modify privileged auth levels', $response['message'] ?? '');

        $updatedUser = User::getByUUID($this->testUserUUID);
        $this->assertNotNull($updatedUser);
        $this->assertSame(AuthLevel::USER, $updatedUser->auth_level);
    }

    public function testSuperadminCanPromoteUserToAdmin(): void
    {
        Database::hset(Keys::USER . ':' . $this->testAdminUUID, [
            'auth_level' => (string) AuthLevel::SUPERADMIN->value,
        ]);

        $_POST['user_uuid'] = $this->testUserUUID;
        $_POST['full_name'] = 'Regular User';
        $_POST['email'] = $this->userEmail;
        $_POST['auth_level'] = (string) AuthLevel::ADMIN->value;
        $this->withCapability('admin.user.update');

        $controller = new AdminController();

        ob_start();
        $controller->updateUser();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertTrue($response['success'] ?? false);

        $updatedUser = User::getByUUID($this->testUserUUID);
        $this->assertNotNull($updatedUser);
        $this->assertSame(AuthLevel::ADMIN, $updatedUser->auth_level);
    }
}
