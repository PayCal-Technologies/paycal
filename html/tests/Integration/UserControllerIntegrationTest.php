<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Controllers\UserController;
use PayCal\Domain\Authentication;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\AuthLevel;
use PHPUnit\Framework\TestCase;

/**
 * UserControllerIntegrationTest
 *
 * Integration tests for user management endpoints.
 */
final class UserControllerIntegrationTest extends TestCase
{
    private string $testUserUUID;
    private string $testUser2UUID;
    private string $testSessionHash;

    protected function setUp(): void
    {
        parent::setUp();

        // Create primary test user
        $this->testUserUUID = 'test-user-' . bin2hex(random_bytes(8));
        Database::hset(Keys::USER . ':' . $this->testUserUUID, [
            'user_uuid' => $this->testUserUUID,
            'email' => 'primary@example.com',
            'full_name' => 'Primary Test User',
            'email_verified' => '1',
            'auth_level' => (string) AuthLevel::USER->value,
        ]);

        // Create second test user for search
        $this->testUser2UUID = 'test-user-2-' . bin2hex(random_bytes(8));
        Database::hset(Keys::USER . ':' . $this->testUser2UUID, [
            'user_uuid' => $this->testUser2UUID,
            'email' => 'searchable@example.com',
            'full_name' => 'Searchable User',
            'email_verified' => '1',
            'auth_level' => (string) AuthLevel::USER->value,
        ]);

        // Create session for primary user
        $this->testSessionHash = hash('sha256', bin2hex(random_bytes(32)));
        Database::hset(Keys::SESSION . ':' . $this->testSessionHash, [
            'user_uuid' => $this->testUserUUID,
            'created_at' => date('c'),
        ]);
        Database::expire(Keys::SESSION . ':' . $this->testSessionHash, 3600);

        $_COOKIE['PAYCAL_AUTH'] = $this->testSessionHash;
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        Database::unlink(Keys::USER . ':' . $this->testUserUUID);
        Database::unlink(Keys::USER . ':' . $this->testUser2UUID);
        Database::unlink(Keys::SESSION . ':' . $this->testSessionHash);

        unset($_COOKIE['PAYCAL_AUTH']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_GET['q']);

        parent::tearDown();
    }

    /**
     * Test user search with valid query
     */
    public function testSearchUsersWithValidQuery(): void
    {
        $_GET['q'] = 'Searchable';

        $controller = new UserController();

        ob_start();
        $controller->searchUsers();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertTrue($response['success'] ?? false);
        $this->assertArrayHasKey('users', $response['data'] ?? []);
        
        $users = $response['data']['users'] ?? [];
        $this->assertNotEmpty($users);
        $this->assertGreaterThan(0, count($users));
        
        // Check user structure
        $firstUser = $users[0] ?? [];
        $this->assertArrayHasKey('uuid', $firstUser);
        $this->assertArrayHasKey('name', $firstUser);
        $this->assertArrayHasKey('email', $firstUser);
    }

    /**
     * Test user search with short query returns empty
     */
    public function testSearchUsersWithShortQuery(): void
    {
        $_GET['q'] = 'a'; // Too short (< 2 chars)

        $controller = new UserController();

        ob_start();
        $controller->searchUsers();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertTrue($response['success'] ?? false);
        $this->assertArrayHasKey('data', $response);
    }

    /**
     * Test user search by email
     */
    public function testSearchUsersByEmail(): void
    {
        $_GET['q'] = 'searchable@';

        $controller = new UserController();

        ob_start();
        $controller->searchUsers();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertTrue($response['success'] ?? false);
        
        $users = $response['data']['users'] ?? [];
        $this->assertNotEmpty($users);
        
        // Verify correct user was found
        $found = false;
        foreach ($users as $user) {
            if (str_contains($user['email'] ?? '', 'searchable@')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Should find user by email');
    }

    /**
     * Test user search excludes current user
     */
    public function testSearchUsersExcludesCurrentUser(): void
    {
        $_GET['q'] = 'Primary';

        $controller = new UserController();

        ob_start();
        $controller->searchUsers();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertTrue($response['success'] ?? false);

        $users = $response['data']['users'] ?? [];
        $this->assertIsArray($users);
        
        // Current user should be excluded from results
        foreach ($users as $user) {
            $this->assertNotSame($this->testUserUUID, $user['uuid'] ?? '', 
                'Current user should not appear in search results');
        }
    }
}
