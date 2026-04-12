<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Controllers\SettingsController;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\UserRepository;
use PHPUnit\Framework\TestCase;

final class SettingsControllerIntegrationTest extends TestCase
{
  protected function tearDown(): void
  {
    unset($_COOKIE['PAYCAL_AUTH']);
    unset($_SERVER['REQUEST_METHOD']);
    unset($_POST);

    parent::tearDown();
  }

  /**
   * @return array<string, mixed>
   */
  private function decodeJsonPayload(string $output): array
  {
    $decoded = json_decode($output, true);
    if (is_array($decoded)) {
      return $decoded;
    }

    if (preg_match_all('/\{\s*"status"\s*:\s*"[^"]+".*?\}/s', $output, $matches) === 1 || !empty($matches[0])) {
      $candidate = end($matches[0]);
      $decoded = json_decode((string) $candidate, true);
      if (is_array($decoded)) {
        return $decoded;
      }
    }

    $this->fail('Expected JSON payload in output: ' . $output);
  }

  /**
   * @return array<string, mixed>
   */
  private function runUpdateSettingsCall(): array
  {
    $_SERVER['REQUEST_METHOD'] = 'POST';

    ob_start();
    (new SettingsController())->updateSettings();
    $output = ob_get_clean();
    $this->assertNotFalse($output);

    return $this->decodeJsonPayload((string) $output);
  }

  /**
   * @return array{userUUID: string, sessionHash: string, email: string, csrfToken: string}
   */
  private function createAuthenticatedContext(): array
  {
    $userUUID = 'test-settings-' . bin2hex(random_bytes(8));
    $email = $userUUID . '@example.test';
    $sessionHash = hash('sha256', bin2hex(random_bytes(32)));

    UserRepository::setUser(
      $userUUID,
      password_hash('secret', PASSWORD_DEFAULT),
      $email,
      AuthLevel::USER,
      'Settings Test User',
      '',
      ''
    );

    Database::hset(Keys::SESSION . ':' . $sessionHash, [
      'user_uuid' => $userUUID,
      'created_at' => date('c'),
    ]);
    Database::expire(Keys::SESSION . ':' . $sessionHash, 3600);

    $_COOKIE['PAYCAL_AUTH'] = $sessionHash;
    $_SERVER['REQUEST_METHOD'] = 'POST';

    $user = UserRepository::getByUUID($userUUID);
    $this->assertNotNull($user);
    $csrfToken = $user->generateFormNonce('settings');

    return [
      'userUUID' => $userUUID,
      'sessionHash' => $sessionHash,
      'email' => $email,
      'csrfToken' => $csrfToken,
    ];
  }

  /**
   * @param array{userUUID: string, sessionHash: string, email: string, csrfToken: string} $context
   */
  private function cleanupAuthenticatedContext(array $context): void
  {
    foreach (Database::scanKeys('user:' . $context['userUUID'] . ':csrf:*') as $key) {
      Database::unlink($key);
    }

    Database::unlink(Keys::SESSION . ':' . $context['sessionHash']);
    Database::unlink(Keys::USER . ':' . $context['userUUID']);
    Database::unlink(Keys::EMAIL . ':' . $context['email']);
    Database::unlink(Keys::EMAIL . $context['email']);

    foreach (Database::scanKeys(Keys::SITE . ':' . $context['userUUID'] . ':*') as $key) {
      Database::unlink($key);
    }

    foreach (Database::scanKeys(Keys::WORK . ':' . $context['userUUID'] . ':*') as $key) {
      Database::unlink($key);
    }

    foreach (Database::scanKeys(Keys::WORK . ':archived:' . $context['userUUID'] . ':*') as $key) {
      Database::unlink($key);
    }

    foreach (Database::scanKeys('user:data_import:prepare:*') as $key) {
      if ((string) Database::hget($key, 'actor_uuid') === $context['userUUID']) {
        Database::unlink($key);
      }
    }

    unset($_COOKIE['PAYCAL_AUTH']);
    unset($_SERVER['REQUEST_METHOD']);
    unset($_POST);
  }

  /**
   * @param array<string, string> $post
   * @return array<string, mixed>
   */
  private function runAuthenticatedUpdateSettingsCall(array $post): array
  {
    $context = $this->createAuthenticatedContext();

    try {
      $_POST = $post;
      $_POST['csrf_token'] = $context['csrfToken'];

      ob_start();
      (new SettingsController())->updateSettings();
      $output = ob_get_clean();
      $this->assertNotFalse($output);

      return $this->decodeJsonPayload((string) $output);
    } finally {
      $this->cleanupAuthenticatedContext($context);
    }
  }

  /**
   * @param array{userUUID: string, sessionHash: string, email: string, csrfToken: string} $context
   * @param array<string, mixed> $post
   * @return array<string, mixed>
   */
  private function runAuthenticatedSettingsMethodCall(array $context, string $method, array $post = []): array
  {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = $post;
    $_POST['csrf_token'] = $context['csrfToken'];

    ob_start();
    (new SettingsController())->{$method}();
    $output = ob_get_clean();
    $this->assertNotFalse($output);

    return $this->decodeJsonPayload((string) $output);
  }

  public function testUpdateSettingsRejectsUnauthenticatedRequest(): void
  {
    $decoded = $this->runUpdateSettingsCall();

    $this->assertSame('error', $decoded['status'] ?? null);
    $message = strtolower((string) ($decoded['message'] ?? ''));
    $this->assertTrue(
      str_contains($message, 'requestguard failed')
      || str_contains($message, 'unauthorized')
      || str_contains($message, 'invalid csrf token')
      || str_contains($message, 'auth failed'),
      'Expected guarded failure message, got: ' . $message
    );
  }

  public function testDeleteAccountRequiresExactConfirmationPhrase(): void
  {
    $_POST['confirm_phrase'] = 'DELETE';

    ob_start();
    (new \PayCal\Controllers\SettingsController())->deleteAccount();
    $output = ob_get_clean();

    $decoded = $this->decodeJsonPayload((string) $output);
    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('delete my account', strtolower((string) ($decoded['message'] ?? '')));
  }

  public function testUpdateSettingsPersistsValidNavigationPositions(): void
  {
    $decoded = $this->runAuthenticatedUpdateSettingsCall([
      'nav_position_primary' => 'left',
    ]);

    $this->assertSame('success', $decoded['status'] ?? null);
    $saved = $decoded['data']['saved'] ?? [];
    $canonical = $decoded['data']['canonical'] ?? [];

    $this->assertSame('left', $saved['nav_position_primary'] ?? null);
    $this->assertSame('left', $canonical['nav_position_primary'] ?? null);
  }

  public function testUpdateSettingsNormalizesInvalidNavigationPositionsToDefaults(): void
  {
    $decoded = $this->runAuthenticatedUpdateSettingsCall([
      'nav_position_primary' => 'ceiling',
    ]);

    $this->assertSame('success', $decoded['status'] ?? null);
    $saved = $decoded['data']['saved'] ?? [];
    $canonical = $decoded['data']['canonical'] ?? [];

    $this->assertSame('left', $saved['nav_position_primary'] ?? null);
    $this->assertSame('left', $canonical['nav_position_primary'] ?? null);
  }

  public function testExportAccountDataIncludesPortablePayloadAndWarning(): void
  {
    $context = $this->createAuthenticatedContext();

    try {
      Database::hset(Keys::SITE . ':' . $context['userUUID'] . ':SITEA', [
        'site_name' => 'Export Site',
        'wage' => '52.10',
        'living_out_allowance' => '20.00',
        'travel_hours' => '1.50',
        'status' => 'active',
        'province' => 'BC',
      ]);

      Database::hset(Keys::WORK . ':' . $context['userUUID'] . ':2026-04-10:SITEA', [
        'date' => '2026-04-10',
        'site_id' => 'SITEA',
        'site_name' => 'Export Site',
        'hours' => '8.00',
        'regular_hours' => '8.00',
        'overtime_hours' => '0.00',
        'wage' => '52.10',
      ]);

      $decoded = $this->runAuthenticatedSettingsMethodCall($context, 'exportAccountData');

      $this->assertSame('success', $decoded['status'] ?? null);
      $data = $decoded['data'] ?? [];
      $this->assertSame(2, $data['schema_version'] ?? null);
      $this->assertSame(true, $data['contains_plaintext'] ?? null);
      $this->assertNotSame('', trim((string) ($data['warning'] ?? '')));
      $this->assertNotSame('', trim((string) ($data['checksum_sha256'] ?? '')));

      $payload = $data['payload'] ?? [];
      $this->assertIsArray($payload);
      $this->assertSame(2, $payload['schema_version'] ?? null);
      $this->assertSame(true, $payload['security']['contains_plaintext'] ?? null);
      $this->assertIsArray($payload['sites'] ?? null);
      $this->assertIsArray($payload['work_entries'] ?? null);
      $this->assertGreaterThanOrEqual(1, count($payload['sites'] ?? []));
      $this->assertGreaterThanOrEqual(1, count($payload['work_entries'] ?? []));
    } finally {
      $this->cleanupAuthenticatedContext($context);
    }
  }

  public function testPrepareAndCommitAccountDataImportPersistsPortableRecords(): void
  {
    $context = $this->createAuthenticatedContext();

    try {
      $payload = [
        'schema_version' => 2,
        'exported_at' => '2026-04-11T00:00:00Z',
        'reference' => 'EXU-test',
        'security' => [
          'contains_plaintext' => true,
          'warning' => 'test warning',
        ],
        'user' => [
          'full_name' => 'Imported User Name',
          'phone' => '555-1234',
          'theme' => 'paycal_blue',
        ],
        'sites' => [
          [
            'id' => 'SITEI',
            'site_name' => 'Imported Site',
            'wage' => '61.25',
            'living_out_allowance' => '15.00',
            'travel_hours' => '2.00',
            'status' => 'active',
            'province' => 'AB',
          ],
        ],
        'work_entries' => [
          [
            'date' => '2026-04-09',
            'site_id' => 'SITEI',
            'site_name' => 'Imported Site',
            'hours' => '10.00',
            'regular_hours' => '8.00',
            'overtime_hours' => '2.00',
            'living_out_allowance' => '15.00',
            'travel_hours' => '2.00',
            'wage' => '61.25',
            'gross' => '700.00',
          ],
        ],
      ];

      $prepare = $this->runAuthenticatedSettingsMethodCall($context, 'prepareAccountDataImport', [
        'payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES),
      ]);

      $this->assertSame('success', $prepare['status'] ?? null);
      $prepareData = $prepare['data'] ?? [];
      $importId = (string) ($prepareData['import_id'] ?? '');
      $this->assertNotSame('', $importId);
      $this->assertSame(1, $prepareData['counts']['sites'] ?? null);
      $this->assertSame(1, $prepareData['counts']['work_entries'] ?? null);

      $commit = $this->runAuthenticatedSettingsMethodCall($context, 'commitAccountDataImport', [
        'import_id' => $importId,
      ]);

      if (($commit['status'] ?? null) === 'error') {
        $message = strtolower((string) ($commit['message'] ?? ''));
        if (str_contains($message, 'redis reliability guard blocked mutation')) {
          $this->markTestSkipped('Redis reliability mutation guard blocked integration commit path.');
        }
      }

      $this->assertSame('success', $commit['status'] ?? null);
      $this->assertSame($importId, (string) ($commit['data']['import_id'] ?? ''));
      $this->assertSame(1, $commit['data']['counts']['user'] ?? null);
      $this->assertSame(1, $commit['data']['counts']['sites'] ?? null);
      $this->assertSame(1, $commit['data']['counts']['work_entries'] ?? null);

      $savedUser = Database::hgetall(Keys::USER . ':' . $context['userUUID']);
      $this->assertSame('Imported User Name', (string) ($savedUser['full_name'] ?? ''));

      $savedSite = Database::hgetall(Keys::SITE . ':' . $context['userUUID'] . ':SITEI');
      $this->assertSame('Imported Site', (string) ($savedSite['site_name'] ?? ''));
      $this->assertSame('61.25', (string) ($savedSite['wage'] ?? ''));

      $savedWork = Database::hgetall(Keys::WORK . ':' . $context['userUUID'] . ':2026-04-09:SITEI');
      $this->assertSame('2026-04-09', (string) ($savedWork['date'] ?? ''));
      $this->assertSame('SITEI', (string) ($savedWork['site_id'] ?? ''));
      $this->assertSame('700.00', (string) ($savedWork['gross'] ?? ''));

      $staged = Database::hgetall('user:data_import:prepare:' . $importId);
      $this->assertSame([], $staged);
    } finally {
      $this->cleanupAuthenticatedContext($context);
    }
  }

  public function testPrepareAccountDataImportRejectsUnsupportedSchemaVersion(): void
  {
    $context = $this->createAuthenticatedContext();

    try {
      $payload = [
        'schema_version' => 1,
        'user' => [],
        'sites' => [],
        'work_entries' => [],
      ];

      $decoded = $this->runAuthenticatedSettingsMethodCall($context, 'prepareAccountDataImport', [
        'payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES),
      ]);

      $this->assertSame('error', $decoded['status'] ?? null);
      $this->assertStringContainsString('Unsupported import schema version', (string) ($decoded['message'] ?? ''));
      $this->assertSame(2, $decoded['data']['supported_schema_version'] ?? null);
      $this->assertSame(1, $decoded['data']['received_schema_version'] ?? null);
    } finally {
      $this->cleanupAuthenticatedContext($context);
    }
  }
}
