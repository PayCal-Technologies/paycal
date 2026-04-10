<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

final class EarningsControllerIntegrationTest extends TestCase
{
  protected function tearDown(): void
  {
    unset($_COOKIE['PAYCAL_AUTH']);
    unset($_SERVER['REQUEST_METHOD']);
    unset($_GET['correlation_context']);

    parent::tearDown();
  }

  /**
   * @return array<string, mixed>
   */
  private function runControllerCall(string $method, string $year, ?string $correlationContext = null): array
  {
    $bootstrap = addslashes(__DIR__ . '/../../bootstrap/Classes.php');
    $method = addslashes($method);
    $year = addslashes($year);

    $contextSetup = '';
    if ($correlationContext !== null) {
      $contextSetup = "\$_GET['correlation_context'] = " . var_export($correlationContext, true) . '; ';
    }

    $script = "require '{$bootstrap}'; {$contextSetup}ob_start(); \\PayCal\\Controllers\\EarningsController::{$method}('{$year}'); echo ob_get_clean();";
    $cmd = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script);
    $output = shell_exec($cmd);

    $this->assertNotFalse($output);
    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded);

    return $decoded;
  }

  public function testGetGrossRejectsOutOfRangeYear(): void
  {
    $decoded = $this->runControllerCall('getGross', '1800');
    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('out of allowed range', (string) ($decoded['message'] ?? ''));
  }

  public function testGetDailyRejectsOutOfRangeYear(): void
  {
    $decoded = $this->runControllerCall('getDaily', '3000');
    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('out of allowed range', (string) ($decoded['message'] ?? ''));
  }

  public function testGetVerificationYearRejectsOutOfRangeYear(): void
  {
    $decoded = $this->runControllerCall('getVerificationYear', '1600');
    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('out of allowed range', (string) ($decoded['message'] ?? ''));
  }

  public function testGetDailyRejectsUnknownCorrelationContext(): void
  {
    $decoded = $this->runControllerCall('getDaily', '2026', 'unapproved-correlation-context');

    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('Correlation context denied', (string) ($decoded['message'] ?? ''));
    $this->assertSame('metadata_correlation_denied', $decoded['reason'] ?? null);
    $this->assertSame('unapproved-correlation-context', $decoded['context'] ?? null);
    $this->assertIsArray($decoded['decision'] ?? null);
    $this->assertSame('metadata_correlation_denied', $decoded['decision']['reason'] ?? null);
    $this->assertContains('site_metadata:financial_payload', $decoded['decision']['denied_pairs'] ?? []);
  }

  public function testGetDailyOrgEnvelopeWithoutWrapDoesNotFatal(): void
  {
    $script = <<<'PHP'
require '__BOOTSTRAP__';

$userUUID = 'test-user-' . bin2hex(random_bytes(6));
$sessionHash = hash('sha256', bin2hex(random_bytes(32)));
$siteId = 'site-' . bin2hex(random_bytes(6));
$orgId = 'org-' . bin2hex(random_bytes(6));
$credentialId = 'cred-' . bin2hex(random_bytes(6));
$year = (int) \PayCal\Domain\Config\SystemConfig::get('year_min');
$workDate = sprintf('%04d-06-15', $year);
$counterKey = 'telemetry:encryption:' . \PayCal\Domain\Config\SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA . ':org:unwrap_denied_missing_wrap';

foreach (\PayCal\Domain\Database::scanKeys('telemetry:encryption:' . \PayCal\Domain\Config\SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA . ':org:unwrap_denied_*') as $key) {
  \PayCal\Domain\Database::unlink((string) $key);
}

\PayCal\Domain\Database::hset(\PayCal\Domain\Constants\Keys::USER . ':' . $userUUID, [
  'user_uuid' => $userUUID,
  'email' => 'telemetry-' . bin2hex(random_bytes(3)) . '@example.com',
  'full_name' => 'Telemetry User',
  'email_verified' => '1',
  'auth_level' => (string) \PayCal\Domain\Enums\AuthLevel::USER->value,
  'encryption_salt' => base64_encode(random_bytes(16)),
]);

\PayCal\Domain\Database::hset(\PayCal\Domain\Constants\Keys::SESSION . ':' . $sessionHash, [
  'user_uuid' => $userUUID,
  'created_at' => date('c'),
  'credential_id' => $credentialId,
]);
\PayCal\Domain\Database::expire(\PayCal\Domain\Constants\Keys::SESSION . ':' . $sessionHash, 3600);

\PayCal\Domain\Database::hset(\PayCal\Domain\Constants\Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $userUUID, [
  'organization_id' => $orgId,
  'user_uuid' => $userUUID,
  'role' => 'member',
  'status' => \PayCal\Domain\OrganizationDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
  'scopes' => 'work.read',
]);

$envelope = [
  'ciphertext' => base64_encode('ciphertext-placeholder'),
  'nonce' => base64_encode(str_repeat('n', 12)),
  'aad' => 'work-aad',
  'meta' => [
    'encryption_mode' => 'organization',
    'org_id' => $orgId,
    'segment' => \PayCal\Domain\OrganizationDiscoveryService::ORG_DEK_SEGMENT_CURRENT_PERIOD,
    'key_version' => 'v1',
  ],
];
$workKey = \PayCal\Domain\Constants\Keys::WORK . ':' . $userUUID . ':' . $workDate . ':' . $siteId;
\PayCal\Domain\Database::hset($workKey, [
  'encrypted_blob' => base64_encode((string) json_encode($envelope)),
]);

$_COOKIE['PAYCAL_AUTH'] = $sessionHash;
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
\PayCal\Controllers\EarningsController::getDaily((string) $year);
$raw = ob_get_clean();
$response = json_decode((string) $raw, true);
$counter = (string) \PayCal\Domain\Database::get($counterKey);

\PayCal\Domain\Database::unlink($workKey);
\PayCal\Domain\Database::unlink(\PayCal\Domain\Constants\Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $userUUID);
\PayCal\Domain\Database::unlink(\PayCal\Domain\Constants\Keys::SESSION . ':' . $sessionHash);
\PayCal\Domain\Database::unlink(\PayCal\Domain\Constants\Keys::USER . ':' . $userUUID);

foreach (\PayCal\Domain\Database::scanKeys('telemetry:encryption:' . \PayCal\Domain\Config\SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA . ':org:unwrap_denied_*') as $key) {
  \PayCal\Domain\Database::unlink((string) $key);
}

echo json_encode(['response' => $response, 'counter' => $counter]);
PHP;

    $bootstrap = addslashes(__DIR__ . '/../../bootstrap/Classes.php');
    $script = str_replace('__BOOTSTRAP__', $bootstrap, $script);

    $cmd = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script);
    $output = shell_exec($cmd);

    $this->assertNotFalse($output);
    $decoded = json_decode((string) $output, true);
    if (!is_array($decoded) && preg_match('/(\{.*\})\s*$/s', (string) $output, $matches) === 1) {
      $decoded = json_decode((string) ($matches[1] ?? ''), true);
    }
    $this->assertIsArray($decoded);
    $isWrappedProbe = array_key_exists('response', $decoded);
    $isControllerPayload = array_key_exists('success', $decoded) || array_key_exists('status', $decoded);
    $this->assertTrue($isWrappedProbe || $isControllerPayload);
  }

}
