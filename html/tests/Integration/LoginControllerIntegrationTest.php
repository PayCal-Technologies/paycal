<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Controllers\LoginController;
use PayCal\Domain\Enums\HttpStatus;
use PHPUnit\Framework\TestCase;

/**
 * LoginControllerIntegrationTest
 *
 * Integration tests for login endpoint behavior.
 */
final class LoginControllerIntegrationTest extends TestCase
{
    /**
     * Test that password login is disabled
     */
    public function testPasswordLoginIsDisabled(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        ob_start();
        $controller = new LoginController();
        $controller->login();
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $response = json_decode($output, true);
        
        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Password authentication is disabled', $response['message']);
    }

    /**
     * Test login endpoint returns proper error structure
     */
    public function testLoginReturnsProperErrorStructure(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        ob_start();
        $controller = new LoginController();
        $controller->login();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        
        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('data', $response);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        parent::tearDown();
    }
}
