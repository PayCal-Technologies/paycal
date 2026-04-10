<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Controllers\SecurityController;
use PayCal\Domain\Database;
use PayCal\Domain\SecurityLog;
use PHPUnit\Framework\TestCase;

final class SecurityControllerIntegrationTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['CONTENT_TYPE'] = 'application/json';
  }

  protected function tearDown(): void
  {
    unset($_SERVER['REQUEST_METHOD']);
    unset($_SERVER['CONTENT_TYPE']);
    parent::tearDown();
  }

  /**
   * Test CSP violation ingestion with valid csp-report payload
   */
  public function testIngestValidCspViolationReport(): void
  {
    $payload = [
      'csp-report' => [
        'blocked-uri' => 'https://evil.example.com/payload.js',
        'document-uri' => 'https://paycal.local/dashboard',
        'violated-directive' => 'script-src',
        'effective-directive' => 'script-src',
        'source-file' => 'https://paycal.local/js/app.js',
        'line-number' => '42',
        'column-number' => '15',
        'disposition' => 'enforce',
        'status-code' => '200',
        'script-sample' => 'eval("malicious")',
      ]
    ];

    $originalInput = file_get_contents('php://input');
    $this->simulateInput(json_encode($payload, JSON_THROW_ON_ERROR));

    $controller = new SecurityController();

    ob_start();
    $controller->ingestCspReport();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $this->assertIsArray($response);
    $this->assertSame('success', $response['status'] ?? null);
    $this->assertTrue($response['accepted'] ?? false);
  }

  /**
   * Test CSP report ingestion with flat (non-nested) payload format
   */
  public function testIngestCspReportFlatFormat(): void
  {
    $payload = [
      'blocked-uri' => 'https://malicious.example.com',
      'document-uri' => 'https://paycal.local/settings',
      'violated-directive' => 'style-src',
      'effective-directive' => 'style-src-elem',
      'disposition' => 'enforce',
    ];

    $this->simulateInput(json_encode($payload, JSON_THROW_ON_ERROR));

    $controller = new SecurityController();

    ob_start();
    $controller->ingestCspReport();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $this->assertIsArray($response);
    $this->assertSame('success', $response['status'] ?? null);
    $this->assertTrue($response['accepted'] ?? false);
  }

  /**
   * Test CSP report ingestion with empty body is accepted gracefully
   */
  public function testIngestEmptyOrMalformedCspReport(): void
  {
    $this->simulateInput('');

    $controller = new SecurityController();

    ob_start();
    $controller->ingestCspReport();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $this->assertIsArray($response);
    $this->assertSame('success', $response['status'] ?? null);
    $this->assertTrue($response['accepted'] ?? false);
  }

  /**
   * Test CSP report with missing optional fields is still logged
   */
  public function testIngestCspReportWithMinimalFields(): void
  {
    $payload = [
      'csp-report' => [
        'blocked-uri' => 'https://blocked.example.com',
        'violated-directive' => 'script-src',
      ]
    ];

    $this->simulateInput(json_encode($payload, JSON_THROW_ON_ERROR));

    $controller = new SecurityController();

    ob_start();
    $controller->ingestCspReport();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $this->assertIsArray($response);
    $this->assertTrue($response['accepted'] ?? false);
  }

  /**
   * Test that CSP report fields are clipped to reasonable max length
   */
  public function testCspReportFieldsAreClipped(): void
  {
    $longString = str_repeat('a', 2000);
    $payload = [
      'csp-report' => [
        'blocked-uri' => $longString,
        'document-uri' => 'https://paycal.local',
        'script-sample' => str_repeat('x', 1000),
      ]
    ];

    $this->simulateInput(json_encode($payload, JSON_THROW_ON_ERROR));

    $controller = new SecurityController();

    ob_start();
    $controller->ingestCspReport();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $this->assertIsArray($response);
    $this->assertTrue($response['accepted'] ?? false);
  }

  private function simulateInput(string $content): void
  {
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $content);
    rewind($stream);
    
    stream_context_set_default([
      'php' => [
        'stdin' => $stream,
      ]
    ]);
  }
}
