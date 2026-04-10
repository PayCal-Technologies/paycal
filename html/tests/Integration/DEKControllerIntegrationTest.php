<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Controllers\DEKController;
use PHPUnit\Framework\TestCase;

final class DEKControllerIntegrationTest extends TestCase
{
  /**
   * @return array<string, mixed>
   */
  private function runDekCall(string $method): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $method = var_export($method, true);
    $script = 'require ' . $bootstrap . '; '
      . '$userUUID = "dek-it-" . bin2hex(random_bytes(6)); '
      . '$sessionHash = bin2hex(random_bytes(16)); '
      . '\\PayCal\\Domain\\Database::hset(\\PayCal\\Domain\\Constants\\Keys::USER . ":" . $userUUID, ['
      . '"user_uuid" => $userUUID, '
      . '"email" => "dek-" . bin2hex(random_bytes(4)) . "@example.com", '
      . '"full_name" => "DEK Integration", '
      . '"email_verified" => "1"'
      . ']); '
      . '\\PayCal\\Domain\\Database::hset(\\PayCal\\Domain\\Constants\\Keys::SESSION . ":" . $sessionHash, ['
      . '"user_uuid" => $userUUID, '
      . '"created_at" => (string) time(), '
      . '"last_activity" => (string) time()'
      . ']); '
      . '\\PayCal\\Domain\\Database::expire(\\PayCal\\Domain\\Constants\\Keys::SESSION . ":" . $sessionHash, 3600); '
      . '$_COOKIE["PAYCAL_AUTH"] = $sessionHash; '
      . '$_SERVER["REQUEST_METHOD"] = "POST"; '
      . 'ob_start(); '
      . '$c = new \\PayCal\\Controllers\\DEKController(); '
      . '$m = ' . $method . '; '
      . '$c->{$m}(); '
      . '$out = ob_get_clean(); '
      . '\\PayCal\\Domain\\Database::unlink(\\PayCal\\Domain\\Constants\\Keys::SESSION . ":" . $sessionHash); '
      . '\\PayCal\\Domain\\Database::unlink(\\PayCal\\Domain\\Constants\\Keys::USER . ":" . $userUUID); '
      . 'echo $out;';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);
    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded);

    return $decoded;
  }

  public function testPostWrappedDekRejectsInvalidJsonPayload(): void
  {
    $decoded = $this->runDekCall('postWrappedDek');
    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('invalid json payload', strtolower((string) ($decoded['message'] ?? '')));
  }

  public function testPostPasskeyWrapRejectsInvalidJsonPayload(): void
  {
    $decoded = $this->runDekCall('postPasskeyWrap');
    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('invalid json payload', strtolower((string) ($decoded['message'] ?? '')));
  }
}
