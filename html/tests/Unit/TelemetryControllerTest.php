<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Controllers\TelemetryController;
use PHPUnit\Framework\Attributes\Group;

/**
 * TelemetryControllerTest
 */
#[Group('unit')]
#[Group('api')]
final class TelemetryControllerTest extends TestCase
{
  #[Test]
  public function recordEventWithoutSessionReturnsUnauthorizedPayload(): void
  {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

    http_response_code(200);

    ob_start();
    TelemetryController::recordEvent();
    $output = ob_get_clean();

    $decoded = json_decode((string) $output, true);

    $this->assertSame(401, http_response_code());
    $this->assertIsArray($decoded);
    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertSame('[Telemetry] Authentication required.', $decoded['message'] ?? null);
  }
}
