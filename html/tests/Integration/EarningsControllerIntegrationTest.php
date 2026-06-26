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
   * @return array{status_code: int, response: array<string, mixed>|null, raw: string}
   */
  private function runLegacyExportCall(string $method, array $payload): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $method = var_export($method, true);
    $payloadExport = var_export((string) json_encode($payload), true);

    $script = <<<'PHP'
if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
  define('PHPUNIT_COMPOSER_INSTALL', true);
}
require __BOOTSTRAP__;

$userUUID = 'legacy-export-user-' . bin2hex(random_bytes(6));
$sessionHash = hash('sha256', bin2hex(random_bytes(32)));

\PayCal\Domain\Database::hset(\PayCal\Domain\Constants\Keys::USER . ':' . $userUUID, [
  'user_uuid' => $userUUID,
  'email' => $userUUID . '@example.com',
  'full_name' => 'Legacy Export User',
  'email_verified' => '1',
  'auth_level' => (string) \PayCal\Domain\Enums\AuthLevel::USER->value,
  'encryption_salt' => base64_encode(random_bytes(16)),
]);
\PayCal\Domain\Database::hset(\PayCal\Domain\Constants\Keys::USER_SUBSCRIPTION . ':' . $userUUID, [
  'tier' => 'premium',
  'status' => 'active',
]);
\PayCal\Domain\Database::hset(\PayCal\Domain\Constants\Keys::SESSION . ':' . $sessionHash, [
  'user_uuid' => $userUUID,
  'created_at' => (string) time(),
  'last_activity' => (string) time(),
]);
\PayCal\Domain\Database::expire(\PayCal\Domain\Constants\Keys::SESSION . ':' . $sessionHash, 3600);

    $_COOKIE['PAYCAL_AUTH'] = $sessionHash;
    $_SERVER['HTTP_COOKIE'] = 'PAYCAL_AUTH=' . rawurlencode($sessionHash);
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $user = \PayCal\Domain\User::getByUUID($userUUID);
    $csrfToken = $user->generateFormNonce('settings');
    $payloadJson = str_replace('__CURRENT_USER_UUID__', $userUUID, __PAYLOAD__);
    $payloadData = json_decode($payloadJson, true);
    if (!is_array($payloadData)) {
      $payloadData = [];
    }
    $payloadData['csrf_token'] = $csrfToken;
    $GLOBALS['mock_php_input_legacy_export'] = json_encode($payloadData);

class MockPhpInputStreamLegacyExport {
  public mixed $context = null;
  public int $position = 0;
  public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool { $this->position = 0; return true; }
  public function stream_read(int $count): string { $data = (string) ($GLOBALS['mock_php_input_legacy_export'] ?? ''); $chunk = substr($data, $this->position, $count); $this->position += strlen($chunk); return $chunk; }
  public function stream_eof(): bool { $data = (string) ($GLOBALS['mock_php_input_legacy_export'] ?? ''); return $this->position >= strlen($data); }
  public function stream_stat(): array { return []; }
}

stream_wrapper_unregister('php');
stream_wrapper_register('php', 'MockPhpInputStreamLegacyExport');
ob_start();
$controller = new \PayCal\Controllers\EarningsController();
$method = __METHOD__;
$controller->{$method}();
$raw = ob_get_clean();
$statusCode = http_response_code();
stream_wrapper_restore('php');

\PayCal\Domain\Database::unlink(\PayCal\Domain\Constants\Keys::SESSION . ':' . $sessionHash);
\PayCal\Domain\Database::unlink(\PayCal\Domain\Constants\Keys::USER . ':' . $userUUID);
\PayCal\Domain\Database::unlink(\PayCal\Domain\Constants\Keys::USER_SUBSCRIPTION . ':' . $userUUID);

echo json_encode([
  'status_code' => $statusCode,
  'response' => json_decode((string) $raw, true),
  'raw' => (string) $raw,
]);
PHP;

    $script = str_replace('__BOOTSTRAP__', $bootstrap, $script);
    $script = str_replace('__PAYLOAD__', $payloadExport, $script);
    $script = str_replace('__METHOD__', $method, $script);

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);
    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded, 'Expected JSON wrapper from legacy export call. Raw output: ' . (string) $output);

    /** @var array{status_code: int, response: array<string, mixed>|null, raw: string} $decoded */
    return $decoded;
  }

  /**
   * @return array{status_code: int, response: array<string, mixed>|null, raw: string}
   */
  private function runLegacyExportRouteCall(string $routePath, array $payload): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $apiIndex = var_export(__DIR__ . '/../../api/index.php', true);
    $routeUri = var_export('/api/v1/' . trim($routePath, '/'), true);
    $payloadExport = var_export((string) json_encode($payload), true);

    $script = <<<'PHP'
if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
  define('PHPUNIT_COMPOSER_INSTALL', true);
}
require __BOOTSTRAP__;

$userUUID = 'legacy-export-route-user-' . bin2hex(random_bytes(6));
$sessionHash = hash('sha256', bin2hex(random_bytes(32)));

\PayCal\Domain\Database::hset(\PayCal\Domain\Constants\Keys::USER . ':' . $userUUID, [
  'user_uuid' => $userUUID,
  'email' => $userUUID . '@example.com',
  'full_name' => 'Legacy Export Route User',
  'email_verified' => '1',
  'auth_level' => (string) \PayCal\Domain\Enums\AuthLevel::USER->value,
  'encryption_salt' => base64_encode(random_bytes(16)),
]);
\PayCal\Domain\Database::hset(\PayCal\Domain\Constants\Keys::USER_SUBSCRIPTION . ':' . $userUUID, [
  'tier' => 'premium',
  'status' => 'active',
]);
\PayCal\Domain\Database::hset(\PayCal\Domain\Constants\Keys::SESSION . ':' . $sessionHash, [
  'user_uuid' => $userUUID,
  'created_at' => (string) time(),
  'last_activity' => (string) time(),
]);
\PayCal\Domain\Database::expire(\PayCal\Domain\Constants\Keys::SESSION . ':' . $sessionHash, 3600);

$_COOKIE['PAYCAL_AUTH'] = $sessionHash;
$_SERVER['HTTP_COOKIE'] = 'PAYCAL_AUTH=' . rawurlencode($sessionHash);
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = __ROUTE_URI__;
$_SERVER['CONTENT_TYPE'] = 'application/json';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'paycal.test';
$_SERVER['SCRIPT_NAME'] = '/api/index.php';
$user = \PayCal\Domain\User::getByUUID($userUUID);
$csrfToken = $user->generateFormNonce('settings');
$payloadJson = str_replace('__CURRENT_USER_UUID__', $userUUID, __PAYLOAD__);
$payloadData = json_decode($payloadJson, true);
if (!is_array($payloadData)) {
  $payloadData = [];
}
$payloadData['csrf_token'] = $csrfToken;
$GLOBALS['mock_php_input_legacy_export_route'] = json_encode($payloadData);

class MockPhpInputStreamLegacyExportRoute {
  public mixed $context = null;
  public int $position = 0;
  public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool { $this->position = 0; return true; }
  public function stream_read(int $count): string { $data = (string) ($GLOBALS['mock_php_input_legacy_export_route'] ?? ''); $chunk = substr($data, $this->position, $count); $this->position += strlen($chunk); return $chunk; }
  public function stream_eof(): bool { $data = (string) ($GLOBALS['mock_php_input_legacy_export_route'] ?? ''); return $this->position >= strlen($data); }
  public function stream_stat(): array { return []; }
}

stream_wrapper_unregister('php');
stream_wrapper_register('php', 'MockPhpInputStreamLegacyExportRoute');

register_shutdown_function(static function () use ($sessionHash, $userUUID): void {
  $raw = ob_get_clean();
  $statusCode = http_response_code();
  stream_wrapper_restore('php');

  \PayCal\Domain\Database::unlink(\PayCal\Domain\Constants\Keys::SESSION . ':' . $sessionHash);
  \PayCal\Domain\Database::unlink(\PayCal\Domain\Constants\Keys::USER . ':' . $userUUID);
  \PayCal\Domain\Database::unlink(\PayCal\Domain\Constants\Keys::USER_SUBSCRIPTION . ':' . $userUUID);

  echo json_encode([
    'status_code' => $statusCode,
    'response' => json_decode((string) $raw, true),
    'raw' => (string) $raw,
  ]);
});

ob_start();
require __API_INDEX__;
PHP;

    $script = str_replace('__BOOTSTRAP__', $bootstrap, $script);
    $script = str_replace('__API_INDEX__', $apiIndex, $script);
    $script = str_replace('__ROUTE_URI__', $routeUri, $script);
    $script = str_replace('__PAYLOAD__', $payloadExport, $script);

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);
    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded, 'Expected JSON wrapper from legacy export route call. Raw output: ' . (string) $output);

    /** @var array{status_code: int, response: array<string, mixed>|null, raw: string} $decoded */
    return $decoded;
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

  public function testLegacyXlsxExportRejectsForgedBusinessMemberPayload(): void
  {
    $payload = $this->forgedBusinessMemberExportPayload();
    $decoded = $this->runLegacyExportCall('exportXlsx', $payload);

    $this->assertSame(403, $decoded['status_code']);
    $this->assertSame('error', $decoded['response']['status'] ?? null);
    $this->assertStringContainsString('not authorized', (string) ($decoded['response']['message'] ?? ''));
  }

  public function testLegacyPdfExportRejectsForgedBusinessMemberPayload(): void
  {
    $payload = $this->forgedBusinessMemberExportPayload();
    $decoded = $this->runLegacyExportCall('exportPdf', $payload);

    $this->assertSame(403, $decoded['status_code']);
    $this->assertSame('error', $decoded['response']['status'] ?? null);
    $this->assertStringContainsString('not authorized', (string) ($decoded['response']['message'] ?? ''));
  }

  public function testLegacyXlsxExportRejectsBusinessMarkedPayloadForCurrentUser(): void
  {
    $payload = $this->currentUserBusinessMarkedExportPayload();
    $decoded = $this->runLegacyExportCall('exportXlsx', $payload);

    $this->assertSame(403, $decoded['status_code']);
    $this->assertSame('error', $decoded['response']['status'] ?? null);
    $this->assertStringContainsString('not authorized', (string) ($decoded['response']['message'] ?? ''));
  }

  public function testLegacyPdfExportRejectsBusinessMarkedPayloadForCurrentUser(): void
  {
    $payload = $this->currentUserBusinessMarkedExportPayload();
    $decoded = $this->runLegacyExportCall('exportPdf', $payload);

    $this->assertSame(403, $decoded['status_code']);
    $this->assertSame('error', $decoded['response']['status'] ?? null);
    $this->assertStringContainsString('not authorized', (string) ($decoded['response']['message'] ?? ''));
  }

  public function testLegacyXlsxRouteRejectsBusinessMarkedPayloadForCurrentUser(): void
  {
    $payload = $this->currentUserBusinessMarkedExportPayload();
    $decoded = $this->runLegacyExportRouteCall('export/xlsx', $payload);

    $this->assertSame(403, $decoded['status_code']);
    $this->assertSame('error', $decoded['response']['status'] ?? null);
    $this->assertStringContainsString('not authorized', (string) ($decoded['response']['message'] ?? ''));
  }

  public function testLegacyPdfRouteRejectsBusinessMarkedPayloadForCurrentUser(): void
  {
    $payload = $this->currentUserBusinessMarkedExportPayload();
    $decoded = $this->runLegacyExportRouteCall('export/pdf', $payload);

    $this->assertSame(403, $decoded['status_code']);
    $this->assertSame('error', $decoded['response']['status'] ?? null);
    $this->assertStringContainsString('not authorized', (string) ($decoded['response']['message'] ?? ''));
  }

  /**
   * @return array<string, mixed>
   */
  private function forgedBusinessMemberExportPayload(): array
  {
    return [
      'scope' => 'yearly',
      'year' => 2026,
      'rows' => [
        [
          'date' => '2026-01-02',
          'site_name' => 'Forged Business Site',
          'wage' => 50,
          'hours' => 8,
          'regular_hours' => 8,
          'overtime_hours' => 0,
          'regular_pay' => 400,
          'overtime_pay' => 0,
          'gross' => 400,
          'net' => 320,
          'federal_tax' => 40,
          'provincial_tax' => 20,
          'employment_insurance' => 6,
          'canada_pension_plan' => 10,
          'old_age_security' => 0,
          'tax' => 80,
        ],
      ],
      'report' => [
        'meta' => [
          'employee' => 'other-business-member',
          'full_name' => 'Other Business Member',
          'business_id' => 'forged-business',
          'member_uuid' => 'other-business-member',
          'year' => 2026,
        ],
        'summary' => [
          'regular_hours' => 8,
          'overtime_hours' => 0,
          'gross' => 400,
          'taxes' => 80,
          'net' => 320,
        ],
        'rows' => [
          [
            'site_name' => 'Forged Business Site',
            'regular' => 8,
            'overtime' => 0,
            'gross' => 400,
            'tax' => 80,
            'net' => 320,
          ],
        ],
      ],
    ];
  }

  /**
   * @return array<string, mixed>
   */
  private function currentUserBusinessMarkedExportPayload(): array
  {
    return [
      'scope' => 'yearly',
      'year' => 2026,
      'business_id' => 'forged-business',
      'rows' => [
        [
          'date' => '2026-01-02',
          'site_name' => 'Forged Business Site',
          'business_id' => 'forged-business',
          'member_uuid' => '__CURRENT_USER_UUID__',
          'encrypted_blob' => base64_encode('not-a-real-envelope'),
          'wage' => 50,
          'hours' => 8,
          'regular_hours' => 8,
          'overtime_hours' => 0,
          'regular_pay' => 400,
          'overtime_pay' => 0,
          'gross' => 400,
          'net' => 320,
          'federal_tax' => 40,
          'provincial_tax' => 20,
          'employment_insurance' => 6,
          'canada_pension_plan' => 10,
          'old_age_security' => 0,
          'tax' => 80,
        ],
      ],
      'report' => [
        'meta' => [
          'employee' => '__CURRENT_USER_UUID__',
          'full_name' => 'Current User',
          'business_id' => 'forged-business',
          'member_uuid' => '__CURRENT_USER_UUID__',
          'year' => 2026,
        ],
        'summary' => [
          'regular_hours' => 8,
          'overtime_hours' => 0,
          'gross' => 400,
          'taxes' => 80,
          'net' => 320,
        ],
        'rows' => [
          [
            'site_name' => 'Forged Business Site',
            'regular' => 8,
            'overtime' => 0,
            'gross' => 400,
            'tax' => 80,
            'net' => 320,
          ],
        ],
      ],
    ];
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

  public function testGetDailyBusinessEnvelopeWithoutWrapDoesNotFatal(): void
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
$counterKey = 'telemetry:encryption:' . \PayCal\Domain\Config\SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA . ':business:unwrap_denied_missing_wrap';

foreach (\PayCal\Domain\Database::scanKeys('telemetry:encryption:' . \PayCal\Domain\Config\SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA . ':business:unwrap_denied_*') as $key) {
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

\PayCal\Domain\Database::hset(\PayCal\Domain\Constants\Keys::BUSINESS_CONNECTION . ':' . $orgId . ':' . $userUUID, [
  'business_id' => $orgId,
  'user_uuid' => $userUUID,
  'role' => 'member',
  'status' => \PayCal\Domain\BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
  'scopes' => 'work.read',
]);

$envelope = [
  'ciphertext' => base64_encode('ciphertext-placeholder'),
  'nonce' => base64_encode(str_repeat('n', 12)),
  'aad' => 'work-aad',
  'meta' => [
    'encryption_mode' => 'business',
    'business_id' => $orgId,
    'segment' => \PayCal\Domain\BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
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
\PayCal\Domain\Database::unlink(\PayCal\Domain\Constants\Keys::BUSINESS_CONNECTION . ':' . $orgId . ':' . $userUUID);
\PayCal\Domain\Database::unlink(\PayCal\Domain\Constants\Keys::SESSION . ':' . $sessionHash);
\PayCal\Domain\Database::unlink(\PayCal\Domain\Constants\Keys::USER . ':' . $userUUID);

foreach (\PayCal\Domain\Database::scanKeys('telemetry:encryption:' . \PayCal\Domain\Config\SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA . ':business:unwrap_denied_*') as $key) {
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
