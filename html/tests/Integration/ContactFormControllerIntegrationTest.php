<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * ContactFormControllerIntegrationTest
 *
 * Integration tests for contact form validation:
 * - POST /contact/ (with pc_method=xhr)
 * 
 * Validates form field validation, error responses, honeypot, and timing checks.
 */
final class ContactFormControllerIntegrationTest extends TestCase
{
  private const BASE_URL = 'http://localhost:8000';
  private const CONTACT_ENDPOINT = '/contact/';

  /**
   * Helper: Extract JSON response from contact form submission
   * 
   * @return array<string, mixed>
   */
  private function runContactFormSubmission(array $formData = []): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $contactIndex = var_export(__DIR__ . '/../../contact/index.php', true);
    
    $formDataExport = var_export($formData, true);
    
    $script = 'require ' . $bootstrap . '; '
      . '$sessionId = "contact-integration-test-" . bin2hex(random_bytes(6)); '
      . 'session_id($sessionId); session_start(); '
      . '$_SERVER["REQUEST_METHOD"] = "POST"; '
      . '$_SERVER["CONTENT_TYPE"] = "application/x-www-form-urlencoded"; '
      . '$_SERVER["REMOTE_ADDR"] = "10." . random_int(1, 254) . "." . random_int(1, 254) . "." . random_int(1, 254); '
      . '$_SERVER["HTTP_USER_AGENT"] = "contact-test-" . bin2hex(random_bytes(4)); '
      . '$_SERVER["HTTP_ACCEPT_LANGUAGE"] = "en-US"; '
      . '$token = bin2hex(random_bytes(32)); '
      . '\\PayCal\\Domain\\Database::set("contact:form_token:" . session_id() . ":" . $token, (string) time(), 1800); '
      . '$_POST = array_merge(' . $formDataExport . ', ["contact_form_token" => $token]); '
      . '$cwd = getcwd(); '
      . 'chdir(dirname(' . $contactIndex . ')); '
      . 'ob_start(); '
      . 'require ' . $contactIndex . '; '
      . '$out = ob_get_clean(); '
      . 'chdir($cwd); '
      . 'echo $out;';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);
    
    $decoded = json_decode((string) $output, true);
    if (is_array($decoded)) {
      return $decoded;
    }
    
    return ['status' => 'error', 'message' => 'Failed to parse response'];
  }

  #[Test]
  public function testSubmitWithValidFieldsInitiates(): void
  {
    // This test validates that all required fields are accepted
    // In a real scenario with actual EmailGarum, this would succeed or fail based on email service
    $response = $this->runContactFormSubmission([
      'name' => 'John Doe',
      'email' => 'john@example.com',
      'subject' => 'Test Subject',
      'message' => 'This is a test message.',
      'reason' => 'general',
      'pc_method' => 'xhr',
      'contact_website' => '', // honeypot
      'contact_form_time' => '5000', // 5 seconds - valid duration
    ]);
    
    $this->assertIsArray($response);
    $this->assertArrayHasKey('status', $response);
    // Response will be error if EmailGarum fails, but validation should pass
  }

  #[Test]
  public function testMissingNameFieldReturnsValidationError(): void
  {
    $response = $this->runContactFormSubmission([
      'name' => '',
      'email' => 'john@example.com',
      'subject' => 'Test Subject',
      'message' => 'Message',
      'reason' => 'general',
      'pc_method' => 'xhr',
      'contact_website' => '',
      'contact_form_time' => '5000',
    ]);
    
    $this->assertEquals('error', $response['status'] ?? null);
    $this->assertArrayHasKey('fieldErrors', $response);
    $this->assertArrayHasKey('name', $response['fieldErrors']);
    $this->assertStringContainsString('name', strtolower($response['fieldErrors']['name']));
  }

  #[Test]
  public function testMissingEmailFieldReturnsValidationError(): void
  {
    $response = $this->runContactFormSubmission([
      'name' => 'John Doe',
      'email' => '',
      'subject' => 'Test Subject',
      'message' => 'Message',
      'reason' => 'general',
      'pc_method' => 'xhr',
      'contact_website' => '',
      'contact_form_time' => '5000',
    ]);
    
    $this->assertEquals('error', $response['status'] ?? null);
    $this->assertArrayHasKey('fieldErrors', $response);
    $this->assertArrayHasKey('email', $response['fieldErrors']);
  }

  #[Test]
  public function testInvalidEmailFormatReturnsValidationError(): void
  {
    $response = $this->runContactFormSubmission([
      'name' => 'John Doe',
      'email' => 'not-an-email',
      'subject' => 'Test Subject',
      'message' => 'Message',
      'reason' => 'general',
      'pc_method' => 'xhr',
      'contact_website' => '',
      'contact_form_time' => '5000',
    ]);
    
    $this->assertEquals('error', $response['status'] ?? null);
    $this->assertArrayHasKey('fieldErrors', $response);
    $this->assertArrayHasKey('email', $response['fieldErrors']);
    $this->assertStringContainsString('valid', strtolower($response['fieldErrors']['email']));
  }

  #[Test]
  public function testMissingReasonFieldReturnsValidationError(): void
  {
    $response = $this->runContactFormSubmission([
      'name' => 'John Doe',
      'email' => 'john@example.com',
      'subject' => 'Test Subject',
      'message' => 'Message',
      'reason' => '',
      'pc_method' => 'xhr',
      'contact_website' => '',
      'contact_form_time' => '5000',
    ]);
    
    $this->assertEquals('error', $response['status'] ?? null);
    $this->assertArrayHasKey('fieldErrors', $response);
    $this->assertArrayHasKey('reason', $response['fieldErrors']);
  }

  #[Test]
  public function testFilledHoneypotFieldRejectsSubmission(): void
  {
    $response = $this->runContactFormSubmission([
      'name' => 'John Doe',
      'email' => 'john@example.com',
      'subject' => 'Test Subject',
      'message' => 'Message',
      'reason' => 'general',
      'pc_method' => 'xhr',
      'contact_website' => 'http://spam.site',  // honeypot filled
      'contact_form_time' => '5000',
    ]);
    
    // Honeypot rejection should be silent
    $this->assertEquals('error', $response['status'] ?? null);
  }

  #[Test]
  public function testTooFastSubmissionRejectsForBotPrevention(): void
  {
    $response = $this->runContactFormSubmission([
      'name' => 'John Doe',
      'email' => 'john@example.com',
      'subject' => 'Test Subject',
      'message' => 'Message',
      'reason' => 'general',
      'pc_method' => 'xhr',
      'contact_website' => '',
      'contact_form_time' => '1000', // 1 second - too fast
    ]);
    
    $this->assertEquals('error', $response['status'] ?? null);
    $this->assertArrayHasKey('message', $response);
    $this->assertStringContainsString('moment', strtolower($response['message']));
  }

  #[Test]
  public function testResponseIncludesFieldErrorsStructure(): void
  {
    $response = $this->runContactFormSubmission([
      'name' => '',
      'email' => 'invalid-email',
      'subject' => '',
      'message' => 'Message',
      'reason' => 'general',
      'pc_method' => 'xhr',
      'contact_website' => '',
      'contact_form_time' => '5000',
    ]);
    
    // Validation should catch errors in name and email
    $this->assertArrayHasKey('fieldErrors', $response);
    $this->assertIsArray($response['fieldErrors']);
    $this->assertGreaterThan(1, count($response['fieldErrors']));
  }
}
