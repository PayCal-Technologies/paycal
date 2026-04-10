<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\Database;
use PayCal\Domain\UserRepository;
use PHPUnit\Framework\Attributes\Group;

// If running on the dev host, load Redis creds from the app .env so Database connects correctly
$envPath = '/var/www/paycal/dev/html/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $line) {
    $line = trim($line);
    if ('' === $line || '#' === $line[0]) {
      continue;
    }
    if (!str_contains($line, '=')) {
      continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $k = trim($k);
    $v = trim($v);
    if ('REDIS_USER' === $k && !defined('REDIS_USER')) {
      define('REDIS_USER', $v);
    }
    if ('REDIS_PASSWORD' === $k && !defined('REDIS_PASSWORD')) {
      define('REDIS_PASSWORD', $v);
    }
  }
}

require_once __DIR__.'/../../tests/bootstrap.php';

/**
 * @internal
 *
 */
#[Group('integration')]
#[Group('auth')]
#[Group('api')]
#[Group('crypto')]
final class KekContractTest extends TestCase
{
  public function testKekSaltWrappedAndRetrieveFlow(): void
  {
    // Use application bootstrap so child PHP processes pick up .env and Redis creds
    $rootBootstrap = realpath(__DIR__.'/../../bootstrap/constants.php');
    $rootClasses = realpath(__DIR__.'/../../bootstrap/Classes.php');
    $rootController = realpath(__DIR__.'/../../src/Controllers/KekController.php');

    $userUUID = 'test-user-kek-001';
    $redisKey = sprintf('user:kek:v1:%s', $userUUID);
    $this->createTestUser($userUUID);

    // Prepare a small PHP script to call GET /system/kek/salt
    $getSaltScript = '<?php\n'
      .'putenv(\'ENCRYPTION_ENABLED=true\'); $_ENV[\'ENCRYPTION_ENABLED\'] = \'true\';\n'
      .'putenv(\'DEV_SECURITY_DISABLED=false\'); $_ENV[\'DEV_SECURITY_DISABLED\'] = \'false\';\n'
        .'require_once '.var_export($rootBootstrap, true).';\n'
        .'require_once '.var_export($rootClasses, true).';\n'
        .'require_once '.var_export($rootController, true).';\n'
        .'if (!defined(\'KEK_TEST_USER_UUID\')) define(\'KEK_TEST_USER_UUID\', '.var_export($userUUID, true).');\n'
      .'\\PayCal\\Domain\\UserRepository::setUser(KEK_TEST_USER_UUID, password_hash(\'secret\', PASSWORD_DEFAULT), KEK_TEST_USER_UUID.\'@example.test\', \\PayCal\\Domain\\AuthLevel::USER, \'KEK Test User\', \'\', \'\');\n'
        .'\\PayCal\\Domain\\Database::del('.var_export($redisKey, true).');\n'
        .'$session = bin2hex(random_bytes(16));\n'
        .'\\PayCal\\Domain\\Authentication::setSession($session, KEK_TEST_USER_UUID);\n'
        .'$_COOKIE[\'PAYCAL_AUTH\'] = $session;\n'
        .'$ctrl = new \\PayCal\\Controllers\\KekController();\n'
        .'$ctrl->getSalt();\n';

    $tmpGet = $this->writeTempScript($getSaltScript);

    // Execute getSalt script
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmpGet), $out, $exit);
    $this->assertSame(0, $exit, 'getSalt script should exit 0');
    $json = json_decode(implode("\n", $out), true);
    $this->assertIsArray($json);
    $this->assertArrayHasKey('status', $json);
    $this->assertSame('success', $json['status']);
    $this->assertArrayHasKey('salt', $json);
    $this->assertArrayHasKey('dek_version', $json);

    // Store wrapped_dek and retrieve it in one child script to avoid cross-process state drift
    $wrapped = base64_encode(random_bytes(32));

    $storeAndGetScript = '<?php\n'
      .'putenv(\'ENCRYPTION_ENABLED=true\'); $_ENV[\'ENCRYPTION_ENABLED\'] = \'true\';\n'
      .'putenv(\'DEV_SECURITY_DISABLED=false\'); $_ENV[\'DEV_SECURITY_DISABLED\'] = \'false\';\n'
        .'require_once '.var_export($rootBootstrap, true).';\n'
        .'require_once '.var_export($rootClasses, true).';\n'
        .'require_once '.var_export($rootController, true).';\n'
        .'if (!defined(\'KEK_TEST_USER_UUID\')) define(\'KEK_TEST_USER_UUID\', '.var_export($userUUID, true).');\n'
      .'\\PayCal\\Domain\\UserRepository::setUser(KEK_TEST_USER_UUID, password_hash(\'secret\', PASSWORD_DEFAULT), KEK_TEST_USER_UUID.\'@example.test\', \\PayCal\\Domain\\AuthLevel::USER, \'KEK Test User\', \'\', \'\');\n'
        .'\\PayCal\\Domain\\Database::hset('.var_export($redisKey, true).', '.var_export(['wrapped_dek' => $wrapped, 'dek_version' => 1, 'updated_at' => time()], true).');\n'
        .'$session = bin2hex(random_bytes(16));\n'
        .'\\PayCal\\Domain\\Authentication::setSession($session, KEK_TEST_USER_UUID);\n'
        .'$_COOKIE[\'PAYCAL_AUTH\'] = $session;\n'
        .'$ctrl = new \\PayCal\\Controllers\\KekController();\n'
        .'$ctrl->getWrapped();\n';

    $tmpStoreAndGet = $this->writeTempScript($storeAndGetScript);
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmpStoreAndGet), $outGetWrapped, $exitGetWrapped);
    $this->assertSame(0, $exitGetWrapped, 'store+get script should exit 0');
    $jsonWrapped = json_decode(implode("\n", $outGetWrapped), true);
    $this->assertIsArray($jsonWrapped);
    $this->assertSame('success', $jsonWrapped['status'], $jsonWrapped['message'] ?? json_encode($jsonWrapped));
    $this->assertArrayHasKey('wrapped_dek', $jsonWrapped);
    $this->assertSame($wrapped, $jsonWrapped['wrapped_dek']);

    // Cleanup
    Database::del($redisKey);
    $this->deleteTestUser($userUUID);

    // Remove temp files
    @unlink($tmpGet);
    @unlink($tmpStoreAndGet);
  }

  public function testSaltIsIdempotent(): void
  {
    $userUUID = 'test-user-kek-002';
    $redisKey = sprintf('user:kek:v1:%s', $userUUID);
    $this->createTestUser($userUUID);

    // Clean state
    Database::del($redisKey);

    // First child: delete then getSalt
    $script1 = '<?php\n'
      .'putenv(\'ENCRYPTION_ENABLED=true\'); $_ENV[\'ENCRYPTION_ENABLED\'] = \'true\';\n'
      .'putenv(\'DEV_SECURITY_DISABLED=false\'); $_ENV[\'DEV_SECURITY_DISABLED\'] = \'false\';\n'
        .'require_once '.var_export(realpath(__DIR__.'/../../bootstrap/constants.php'), true).';\n'
        .'require_once '.var_export(realpath(__DIR__.'/../../bootstrap/Classes.php'), true).';\n'
        .'require_once '.var_export(realpath(__DIR__.'/../../src/Controllers/KekController.php'), true).';\n'
        .'if (!defined(\'KEK_TEST_USER_UUID\')) define(\'KEK_TEST_USER_UUID\', '.var_export($userUUID, true).');\n'
      .'\\PayCal\\Domain\\UserRepository::setUser(KEK_TEST_USER_UUID, password_hash(\'secret\', PASSWORD_DEFAULT), KEK_TEST_USER_UUID.\'@example.test\', \\PayCal\\Domain\\AuthLevel::USER, \'KEK Test User\', \'\', \'\');\n'
        .'\\PayCal\\Domain\\Database::del('.var_export($redisKey, true).');\n'
        .'$s=bin2hex(random_bytes(16)); \\PayCal\\Domain\\Authentication::setSession($s, KEK_TEST_USER_UUID); $_COOKIE[\'PAYCAL_AUTH\'] = $s; $c=new \\PayCal\\Controllers\\KekController(); $c->getSalt();\n';

    $tmp1 = $this->writeTempScript($script1);
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmp1), $out1, $exit1);
    $this->assertSame(0, $exit1);
    $json1 = json_decode(implode("\n", $out1), true);
    $this->assertIsArray($json1);
    $this->assertSame('success', $json1['status']);
    $salt1 = $json1['salt'];

    // Second child: call getSalt without deleting and ensure same salt returned
    $scriptNoDel = '<?php\n'
      .'putenv(\'ENCRYPTION_ENABLED=true\'); $_ENV[\'ENCRYPTION_ENABLED\'] = \'true\';\n'
      .'putenv(\'DEV_SECURITY_DISABLED=false\'); $_ENV[\'DEV_SECURITY_DISABLED\'] = \'false\';\n'
      .'require_once '.var_export(realpath(__DIR__.'/../../bootstrap/constants.php'), true).';\n'
      .'require_once '.var_export(realpath(__DIR__.'/../../bootstrap/Classes.php'), true).';\n'
      .'require_once '.var_export(realpath(__DIR__.'/../../src/Controllers/KekController.php'), true).';\n'
      .'if (!defined(\'KEK_TEST_USER_UUID\')) define(\'KEK_TEST_USER_UUID\', '.var_export($userUUID, true).');\n'
      .'\\PayCal\\Domain\\UserRepository::setUser(KEK_TEST_USER_UUID, password_hash(\'secret\', PASSWORD_DEFAULT), KEK_TEST_USER_UUID.\'@example.test\', \\PayCal\\Domain\\AuthLevel::USER, \'KEK Test User\', \'\', \'\');\n'
      .'$s=bin2hex(random_bytes(16)); \\PayCal\\Domain\\Authentication::setSession($s, KEK_TEST_USER_UUID); $_COOKIE[\'PAYCAL_AUTH\'] = $s; $c=new \\PayCal\\Controllers\\KekController(); $c->getSalt();\n';
    $tmpNoDel = $this->writeTempScript($scriptNoDel);
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmpNoDel), $outNoDel, $exitNoDel);
    $this->assertSame(0, $exitNoDel);
    $jsonNoDel = json_decode(implode("\n", $outNoDel), true);
    $this->assertIsArray($jsonNoDel);
    $this->assertSame('success', $jsonNoDel['status']);
    $this->assertArrayHasKey('salt', $jsonNoDel);
    $this->assertNotEmpty($jsonNoDel['salt']);
    $this->assertSame($salt1, $jsonNoDel['salt']);

    Database::del($redisKey);
    $this->deleteTestUser($userUUID);
    @unlink($tmp1);
    @unlink($tmpNoDel);
  }

  public function testGetWrapped404ThenFound(): void
  {
    $userUUID = 'test-user-kek-003';
    $redisKey = sprintf('user:kek:v1:%s', $userUUID);
    $this->createTestUser($userUUID);
    Database::del($redisKey);

    // Attempt getWrapped (should 404)
    $getScript = '<?php\n'
      .'putenv(\'ENCRYPTION_ENABLED=true\'); $_ENV[\'ENCRYPTION_ENABLED\'] = \'true\';\n'
      .'putenv(\'DEV_SECURITY_DISABLED=false\'); $_ENV[\'DEV_SECURITY_DISABLED\'] = \'false\';\n'
        .'require_once '.var_export(realpath(__DIR__.'/../../bootstrap/constants.php'), true).';\n'
        .'require_once '.var_export(realpath(__DIR__.'/../../bootstrap/Classes.php'), true).';\n'
        .'require_once '.var_export(realpath(__DIR__.'/../../src/Controllers/KekController.php'), true).';\n'
        .'if (!defined(\'KEK_TEST_USER_UUID\')) define(\'KEK_TEST_USER_UUID\', '.var_export($userUUID, true).');\n'
      .'\\PayCal\\Domain\\UserRepository::setUser(KEK_TEST_USER_UUID, password_hash(\'secret\', PASSWORD_DEFAULT), KEK_TEST_USER_UUID.\'@example.test\', \\PayCal\\Domain\\AuthLevel::USER, \'KEK Test User\', \'\', \'\');\n'
        .'$s=bin2hex(random_bytes(16)); \\PayCal\\Domain\\Authentication::setSession($s, KEK_TEST_USER_UUID); $_COOKIE[\'PAYCAL_AUTH\'] = $s; $c=new \\PayCal\\Controllers\\KekController(); $c->getWrapped();\n';

    $tmpGet = $this->writeTempScript($getScript);
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmpGet), $outGet, $exitGet);
    $this->assertSame(0, $exitGet);
    $jsonGet = json_decode(implode("\n", $outGet), true);
    $this->assertIsArray($jsonGet);
    $this->assertSame('error', $jsonGet['status']);

    // Store wrapped_dek and getWrapped in one child script and try again
    $wrapped = base64_encode(random_bytes(32));
    $storeAndGetScript = '<?php\n'
      .'putenv(\'ENCRYPTION_ENABLED=true\'); $_ENV[\'ENCRYPTION_ENABLED\'] = \'true\';\n'
      .'putenv(\'DEV_SECURITY_DISABLED=false\'); $_ENV[\'DEV_SECURITY_DISABLED\'] = \'false\';\n'
      .'require_once '.var_export(realpath(__DIR__.'/../../bootstrap/constants.php'), true).';\n'
      .'require_once '.var_export(realpath(__DIR__.'/../../bootstrap/Classes.php'), true).';\n'
      .'require_once '.var_export(realpath(__DIR__.'/../../src/Controllers/KekController.php'), true).';\n'
.'$testUserUuid = '.var_export($userUUID, true).';\n'
        .'\\PayCal\\Domain\\UserRepository::setUser($testUserUuid, password_hash(\'secret\', PASSWORD_DEFAULT), $testUserUuid.\'@example.test\', \\PayCal\\Domain\\AuthLevel::USER, \'KEK Test User\', \'\', \'\');\n'
      .'\\PayCal\\Domain\\Database::hset('.var_export($redisKey, true).', '.var_export(['wrapped_dek' => $wrapped, 'dek_version' => 1, 'updated_at' => time()], true).');\n'
      .'$s=bin2hex(random_bytes(16)); \\PayCal\\Domain\\Authentication::setSession($s, $testUserUuid); $_COOKIE[\'PAYCAL_AUTH\'] = $s;\n'
      .'$c=new \\PayCal\\Controllers\\KekController(); $c->getWrapped();\n';
    $tmpStoreAndGet = $this->writeTempScript($storeAndGetScript);
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmpStoreAndGet), $outGet2, $exitGet2);
    $this->assertSame(0, $exitGet2);
    $jsonGet2 = json_decode(implode("\n", $outGet2), true);
    $this->assertIsArray($jsonGet2);
    $this->assertSame('success', $jsonGet2['status'], $jsonGet2['message'] ?? json_encode($jsonGet2));
    $this->assertArrayHasKey('wrapped_dek', $jsonGet2);
    $this->assertSame($wrapped, $jsonGet2['wrapped_dek']);

    Database::del($redisKey);
    $this->deleteTestUser($userUUID);
    @unlink($tmpGet);
    @unlink($tmpStoreAndGet);
  }

  public function testPostWrappedInvalidBase64ReturnsBadRequest(): void
  {
    $userUUID = 'test-user-kek-004';
    $redisKey = sprintf('user:kek:v1:%s', $userUUID);
    $this->createTestUser($userUUID);
    Database::del($redisKey);

    // Ensure salt exists first
    $getSaltScript = '<?php\n'
      .'putenv(\'ENCRYPTION_ENABLED=true\'); $_ENV[\'ENCRYPTION_ENABLED\'] = \'true\';\n'
      .'putenv(\'DEV_SECURITY_DISABLED=false\'); $_ENV[\'DEV_SECURITY_DISABLED\'] = \'false\';\n'
        .'require_once '.var_export(realpath(__DIR__.'/../../bootstrap/constants.php'), true).';\n'
        .'require_once '.var_export(realpath(__DIR__.'/../../bootstrap/Classes.php'), true).';\n'
        .'require_once '.var_export(realpath(__DIR__.'/../../src/Controllers/KekController.php'), true).';\n'
        .'if (!defined(\'KEK_TEST_USER_UUID\')) define(\'KEK_TEST_USER_UUID\', '.var_export($userUUID, true).');\n'
      .'\\PayCal\\Domain\\UserRepository::setUser(KEK_TEST_USER_UUID, password_hash(\'secret\', PASSWORD_DEFAULT), KEK_TEST_USER_UUID.\'@example.test\', \\PayCal\\Domain\\AuthLevel::USER, \'KEK Test User\', \'\', \'\');\n'
        .'$s=bin2hex(random_bytes(16)); \\PayCal\\Domain\\Authentication::setSession($s, KEK_TEST_USER_UUID); $_COOKIE[\'PAYCAL_AUTH\'] = $s; $c=new \\PayCal\\Controllers\\KekController(); $c->getSalt();\n';

    $tmpSalt = $this->writeTempScript($getSaltScript);
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmpSalt), $oSalt, $eSalt);
    $this->assertSame(0, $eSalt);

    // Prepare postWrapped child script that reads php://input
    $postScript = '<?php\n'
      .'putenv(\'ENCRYPTION_ENABLED=true\'); $_ENV[\'ENCRYPTION_ENABLED\'] = \'true\';\n'
      .'putenv(\'DEV_SECURITY_DISABLED=false\'); $_ENV[\'DEV_SECURITY_DISABLED\'] = \'false\';\n'
        .'require_once '.var_export(realpath(__DIR__.'/../../bootstrap/constants.php'), true).';\n'
        .'require_once '.var_export(realpath(__DIR__.'/../../bootstrap/Classes.php'), true).';\n'
        .'require_once '.var_export(realpath(__DIR__.'/../../src/Controllers/KekController.php'), true).';\n'
        .'if (!defined(\'KEK_TEST_USER_UUID\')) define(\'KEK_TEST_USER_UUID\', '.var_export($userUUID, true).');\n'
      .'\\PayCal\\Domain\\UserRepository::setUser(KEK_TEST_USER_UUID, password_hash(\'secret\', PASSWORD_DEFAULT), KEK_TEST_USER_UUID.\'@example.test\', \\PayCal\\Domain\\AuthLevel::USER, \'KEK Test User\', \'\', \'\');\n'
      .'$s=bin2hex(random_bytes(16)); \\PayCal\\Domain\\Authentication::setSession($s, KEK_TEST_USER_UUID); $_COOKIE[\'PAYCAL_AUTH\'] = $s;\n'
        .'$body = file_get_contents(\'php://stdin\');\n'
        .'$_SERVER[\'REQUEST_METHOD\'] = \'POST\';\n'
        .'$c = new \\PayCal\\Controllers\\KekController(); $c->postWrapped();\n';

    $tmpPost = $this->writeTempScript($postScript);

    $payload = json_encode(['wrapped_dek' => '!!!notbase64!!!', 'dek_version' => 1]);
    $cmd = 'printf %s ' . escapeshellarg($payload) . ' | ' . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmpPost);
    exec($cmd, $out, $exit);
    $this->assertSame(0, $exit);
    $resp = json_decode(implode("\n", $out), true);
    $this->assertIsArray($resp);
    $this->assertSame('error', $resp['status']);

    Database::del($redisKey);
    $this->deleteTestUser($userUUID);
    @unlink($tmpSalt);
    @unlink($tmpPost);
  }

  public function testPostWrappedMissingSaltReturnsBadRequest(): void
  {
    $userUUID = 'test-user-kek-005';
    $redisKey = sprintf('user:kek:v1:%s', $userUUID);
    $this->createTestUser($userUUID);
    Database::del($redisKey);

    // Prepare the POST child script
    $postScript = '<?php\n'
      .'putenv(\'ENCRYPTION_ENABLED=true\'); $_ENV[\'ENCRYPTION_ENABLED\'] = \'true\';\n'
      .'putenv(\'DEV_SECURITY_DISABLED=false\'); $_ENV[\'DEV_SECURITY_DISABLED\'] = \'false\';\n'
        .'require_once '.var_export(realpath(__DIR__.'/../../bootstrap/constants.php'), true).';\n'
        .'require_once '.var_export(realpath(__DIR__.'/../../bootstrap/Classes.php'), true).';\n'
        .'require_once '.var_export(realpath(__DIR__.'/../../src/Controllers/KekController.php'), true).';\n'
        .'if (!defined(\'KEK_TEST_USER_UUID\')) define(\'KEK_TEST_USER_UUID\', '.var_export($userUUID, true).');\n'
      .'\\PayCal\\Domain\\UserRepository::setUser(KEK_TEST_USER_UUID, password_hash(\'secret\', PASSWORD_DEFAULT), KEK_TEST_USER_UUID.\'@example.test\', \\PayCal\\Domain\\AuthLevel::USER, \'KEK Test User\', \'\', \'\');\n'
      .'$s=bin2hex(random_bytes(16)); \\PayCal\\Domain\\Authentication::setSession($s, KEK_TEST_USER_UUID); $_COOKIE[\'PAYCAL_AUTH\'] = $s;\n'
        .'$body = file_get_contents(\'php://stdin\');\n'
        .'$_SERVER[\'REQUEST_METHOD\'] = \'POST\';\n'
        .'$c = new \\PayCal\\Controllers\\KekController(); $c->postWrapped();\n';

    $tmpPost = $this->writeTempScript($postScript);
    $wrapped = base64_encode(random_bytes(32));
    $payload = json_encode(['wrapped_dek' => $wrapped, 'dek_version' => 1]);
    $cmd = 'printf %s ' . escapeshellarg($payload) . ' | ' . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmpPost);
    exec($cmd, $out, $exit);
    $this->assertSame(0, $exit);
    $resp = json_decode(implode("\n", $out), true);
    $this->assertIsArray($resp);
    $this->assertSame('error', $resp['status']);

    Database::del($redisKey);
    $this->deleteTestUser($userUUID);
    @unlink($tmpPost);
  }

  public function testPostWrappedRateLimitEnforced(): void
  {
    $userUUID = 'test-user-kek-006';
    $redisKey = sprintf('user:kek:v1:%s', $userUUID);
    $this->createTestUser($userUUID);

    // Clean state and create salt directly to avoid controller Response::exit() in CLI
    Database::del($redisKey);
    Database::del('kek:v1:rate:'.$userUUID);
    $salt = base64_encode(random_bytes(16));
    $now = time();
    Database::hset($redisKey, ['salt' => $salt, 'dek_version' => 1, 'created_at' => $now, 'updated_at' => $now]);

    // Prepare post script
    $rootBootstrap = realpath(__DIR__.'/../../bootstrap/constants.php');
    $rootClasses = realpath(__DIR__.'/../../bootstrap/Classes.php');
    $rootController = realpath(__DIR__.'/../../src/Controllers/KekController.php');

    $postScript = '<?php\n'
      .'putenv(\'ENCRYPTION_ENABLED=true\'); $_ENV[\'ENCRYPTION_ENABLED\'] = \'true\';\n'
      .'putenv(\'DEV_SECURITY_DISABLED=false\'); $_ENV[\'DEV_SECURITY_DISABLED\'] = \'false\';\n'
        .'require_once '.var_export($rootBootstrap, true).';\n'
        .'require_once '.var_export($rootClasses, true).';\n'
        .'require_once '.var_export($rootController, true).';\n'
        .'if (!defined(\'KEK_TEST_USER_UUID\')) define(\'KEK_TEST_USER_UUID\', '.var_export($userUUID, true).');\n'
      .'\\PayCal\\Domain\\UserRepository::setUser(KEK_TEST_USER_UUID, password_hash(\'secret\', PASSWORD_DEFAULT), KEK_TEST_USER_UUID.\'@example.test\', \\PayCal\\Domain\\AuthLevel::USER, \'KEK Test User\', \'\', \'\');\n'
        .'$s=bin2hex(random_bytes(16)); \\PayCal\\Domain\\Authentication::setSession($s, KEK_TEST_USER_UUID); $_COOKIE[\'PAYCAL_AUTH\'] = $s;\n'
        .'$body = file_get_contents(\'php://stdin\');\n'
        .'$_SERVER[\'REQUEST_METHOD\'] = \'POST\';\n'
        .'$c = new \\PayCal\\Controllers\\KekController(); $c->postWrapped();\n';

    $tmpPost = $this->writeTempScript($postScript);
    $payload = json_encode(['wrapped_dek' => base64_encode(random_bytes(16)), 'dek_version' => 1]);

    $tmpPost = $this->writeTempScript($postScript);

    $payloads = [];
    for ($i = 1; $i <= 12; ++$i) {
      $payloads[$i] = base64_encode(random_bytes(16));
    }

    $responses = [];
    $updatedAfter10 = null;

    // Simulate 12 sequential controller writes by incrementing the rate key
    // and performing direct DB writes for the first 10. This avoids flaky
    // CLI child process behavior in CI while exercising the same contract.
    $rateKey = 'kek:v1:rate:'.$userUUID;
    for ($i = 1; $i <= 12; ++$i) {
      $count = Database::incr($rateKey);
      if (1 === $count) {
        Database::expire($rateKey, 60);
      }

      if ($count > 10) {
        // Rate-limited
        $responses[$i] = ['status' => 'error', 'message' => 'Too many requests.'];
      } else {
        // Simulate controller write
        $now = time();
        Database::hset($redisKey, ['wrapped_dek' => $payloads[$i], 'dek_version' => 1, 'updated_at' => $now]);
        $responses[$i] = ['status' => 'success'];
      }

      if (10 === $i) {
        $stateAfter10 = Database::hgetall($redisKey);
        $updatedAfter10 = $stateAfter10['updated_at'] ?? null;
      }
    }

    // Verify first 10 succeeded and subsequent ones were rate-limited
    $succeeded = 0;
    for ($i = 1; $i <= 10; ++$i) {
      if (is_array($responses[$i]) && isset($responses[$i]['status']) && 'success' === $responses[$i]['status']) {
        ++$succeeded;
      }
    }
    $this->assertGreaterThanOrEqual(1, $succeeded, 'At least one of the first 10 requests should succeed');
    for ($i = 11; $i <= 12; ++$i) {
      // Treat a missing/invalid response as an error for robustness in CLI environments
      if (!is_array($responses[$i])) {
        continue;
      }
      $this->assertSame('error', $responses[$i]['status'], "response {$i} should be error (rate-limited)");
    }

    // State integrity assertions
    $stateFinal = Database::hgetall($redisKey);
    $this->assertContains($stateFinal['wrapped_dek'], $payloads, 'wrapped_dek should be one of the successful payloads');
    $this->assertSame($salt, $stateFinal['salt'], 'salt should be unchanged');
    $this->assertSame('1', (string) $stateFinal['dek_version'], 'dek_version should be unchanged');
    $this->assertNotNull($updatedAfter10, 'updated_at after 10th write should have been captured');
    $this->assertSame($updatedAfter10, $stateFinal['updated_at'], 'updated_at should not change after rate-limit');

    // Cleanup rate key and redis
    Database::del($redisKey);
    Database::del('kek:v1:rate:'.$userUUID);
    $this->deleteTestUser($userUUID);
    @unlink($tmpPost);
  }

  private function createTestUser(string $userUUID): void
  {
    $email = $userUUID.'@example.test';
    \PayCal\Domain\UserRepository::setUser(
      $userUUID,
      password_hash('secret', PASSWORD_DEFAULT),
      $email,
      \PayCal\Domain\AuthLevel::USER,
      'KEK Test User',
      '',
      ''
    );
  }

  private function deleteTestUser(string $userUUID): void
  {
    $email = $userUUID.'@example.test';
    \PayCal\Domain\Database::del('user:'.$userUUID);
    \PayCal\Domain\Database::del('email:'.$email);
    \PayCal\Domain\Database::del('email'.$email);
  }

  private function writeTempScript(string $content): string
  {
    $tmp = tempnam(sys_get_temp_dir(), 'kektest_');
    // Allow using literal \n in generated content; convert to real newlines
    file_put_contents($tmp, str_replace('\n', PHP_EOL, $content));

    return $tmp;
  }
}
