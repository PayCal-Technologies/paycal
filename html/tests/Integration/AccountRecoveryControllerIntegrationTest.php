<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Infrastructure\Transaction\AccountRecoveryTransaction;
use PayCal\Domain\Authentication;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\PayCalCode;
use PayCal\Domain\RecoveryKey;
use PayCal\Domain\UserRepository;
use PHPUnit\Framework\TestCase;

final class AccountRecoveryControllerIntegrationTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    $this->resetRecoveryTestState();
  }

  protected function tearDown(): void
  {
    $this->resetRecoveryTestState();
    parent::tearDown();
  }

  private function resetRecoveryTestState(): void
  {
    $this->clearAllRecoveryRateLimitKeys();
    $this->clearRecoveryBlockedIp('127.0.0.1');
  }

  /** @return array<string, mixed> */
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

  /** @return array<string, mixed> */
  private function runControllerCall(string $method, array $payload = [], ?string $sessionHash = null): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $method = var_export($method, true);
    $jsonPayload = var_export(json_encode($payload), true);
    $cookieSetup = '';
    if ($sessionHash !== null) {
      $cookieSetup = '$_COOKIE["PAYCAL_AUTH"] = ' . var_export($sessionHash, true) . '; ';
    }

    $script = 'require ' . $bootstrap . '; '
      . '$_SERVER["REQUEST_METHOD"] = "POST"; '
      . '$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; '
      . '$_SERVER["HTTP_USER_AGENT"] = "PHPUnit Recovery"; '
      . '$_SERVER["HTTP_ACCEPT_LANGUAGE"] = "en-US"; '
      . '$_SERVER["CONTENT_TYPE"] = "application/json"; '
      . $cookieSetup
      . '$GLOBALS["mock_php_input_account_recovery"] = ' . $jsonPayload . '; '
      . 'class MockPhpInputStreamAccountRecovery {'
      . '  public mixed $context;' 
      . '  public int $position = 0;'
      . '  public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool { $this->position = 0; return true; }'
      . '  public function stream_read(int $count): string { $data = (string)($GLOBALS["mock_php_input_account_recovery"] ?? ""); $chunk = substr($data, $this->position, $count); $this->position += strlen($chunk); return $chunk; }'
      . '  public function stream_eof(): bool { $data = (string)($GLOBALS["mock_php_input_account_recovery"] ?? ""); return $this->position >= strlen($data); }'
      . '  public function stream_stat(): array { return []; }'
      . '}'
      . 'stream_wrapper_unregister("php"); '
      . 'stream_wrapper_register("php", "MockPhpInputStreamAccountRecovery"); '
      . 'ob_start(); '
      . '$c = new \\PayCal\\Controllers\\AccountRecoveryController(); '
      . '$m = ' . $method . '; '
      . '$c->{$m}(); '
      . 'stream_wrapper_restore("php"); '
      . 'echo ob_get_clean();';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);

    return $this->decodeJsonPayload((string) $output);
  }

  /** @return array{userUuid:string,recoverySalt:string,recoveryProofKey:string,wrappedDekRecovery:string,recoveryCode:string} */
  private function createUserWithRecoveryMaterial(string $email = 'recover@example.com'): array
  {
    $userUuid = 'U' . bin2hex(random_bytes(4));
    UserRepository::setUser(
      $userUuid,
      $email,
      AuthLevel::USER,
      'Recovery Test User',
      '',
      ''
    );

    $recoveryCode = RecoveryKey::generateCode();
    $recoveryKeyBytes = RecoveryKey::secretMaterial($recoveryCode);
    $recoverySalt = base64_encode(RecoveryKey::generateRecoverySalt());
    $recoveryProofKey = base64_encode(RecoveryKey::deriveProofKey($recoveryKeyBytes, $recoverySalt));
    $wrappedDekRecovery = RecoveryKey::wrapDEK(base64_encode(random_bytes(32)), RecoveryKey::deriveKEK($recoveryKeyBytes, $recoverySalt));

    Database::hset(Keys::USER . ':' . $userUuid, [
      'email_verified' => '1',
      'account_recovery_salt' => $recoverySalt,
      'wrapped_dek_recovery' => $wrappedDekRecovery,
      'recovery_key_generated' => '1',
      'recovery_proof_key' => $recoveryProofKey,
      'recovery_proof_key_version' => '1',
      'encryption_salt' => base64_encode(random_bytes(32)),
      'dek_version' => '1',
      'crypto_version' => '1',
    ]);

    return [
      'userUuid' => $userUuid,
      'recoverySalt' => $recoverySalt,
      'recoveryProofKey' => $recoveryProofKey,
      'wrappedDekRecovery' => $wrappedDekRecovery,
      'recoveryCode' => $recoveryCode,
    ];
  }

  /** @return array{userUuid:string,email:string} */
  private function createUserWithoutCryptoMaterial(string $email): array
  {
    $userUuid = 'U' . bin2hex(random_bytes(4));
    UserRepository::setUser(
      $userUuid,
      $email,
      AuthLevel::USER,
      'First Key Test User',
      '',
      ''
    );
    Database::hset(Keys::USER . ':' . $userUuid, ['email_verified' => '1']);

    return [
      'userUuid' => $userUuid,
      'email' => $email,
    ];
  }

  /** @return array{userUuid:string,email:string} */
  private function createUserWithPasskeyWrapButNoRecoveryKey(string $email): array
  {
    $fixture = $this->createUserWithoutCryptoMaterial($email);
    Database::hset(Keys::USER . ':' . $fixture['userUuid'], [
      'encryption_salt' => base64_encode(random_bytes(32)),
      'dek_version' => '1',
      'crypto_version' => '1',
    ]);
    Database::hset(Keys::USER . ':' . $fixture['userUuid'] . ':passkey_wrapped_deks', [
      'credential-test' => base64_encode('wrapped-passkey-dek'),
    ]);

    return $fixture;
  }

  /** @return array{userUuid:string,email:string,credentialId:string} */
  private function createUserWithPasskeyButNoCryptoMaterial(string $email): array
  {
    $fixture = $this->createUserWithoutCryptoMaterial($email);
    $credentialId = 'credential-existing-' . bin2hex(random_bytes(4));
    Database::hset(Keys::webauthnCredential($credentialId), [
      'credential_id' => $credentialId,
      'user_uuid' => $fixture['userUuid'],
      'public_key_pem' => 'test-public-key',
      'sign_count' => '0',
      'created_at' => (string) time(),
      'last_used_at' => '',
    ]);
    Database::sadd(Keys::webauthnUserCredentials($fixture['userUuid']), $credentialId);
    Database::hset(Keys::USER . ':' . $fixture['userUuid'], ['webauthn_enabled' => '1']);

    return [
      'userUuid' => $fixture['userUuid'],
      'email' => $email,
      'credentialId' => $credentialId,
    ];
  }

  private function storeMagicLinkToken(AccountRecoveryTransaction $transaction, string $txnSecret, string $token): void
  {
    Database::hsetex(Keys::accountRecoveryMagicLink($token), [
      'txn_id' => $transaction->id(),
      'txn_secret' => $txnSecret,
    ], 300);
  }

  private function validWrappedDekEnvelope(): string
  {
    return base64_encode(json_encode([
      'version' => 1,
      'nonce' => base64_encode(random_bytes(12)),
      'ciphertext' => base64_encode(random_bytes(48)),
    ]));
  }

  /** @return array{transaction:AccountRecoveryTransaction,txnSecret:string} */
  private function createMagicLinkReadyTransaction(string $email, string $userUuid): array
  {
    $created = AccountRecoveryTransaction::create($email, $userUuid, 'First Key Test User');
    /** @var AccountRecoveryTransaction $transaction */
    $transaction = $created['transaction'];
    $fingerprintHash = hash('sha256', 'PHPUnit Recovery|en-US');
    $ipClass = '127.0.0.0/24';
    $this->assertTrue($transaction->markEmailVerifiedByMagicLink($fingerprintHash, $ipClass));
    $this->assertTrue($transaction->elevateForMagicLinkPasskey($fingerprintHash, $ipClass));

    return [
      'transaction' => $transaction,
      'txnSecret' => (string) $created['txnSecret'],
    ];
  }

  private function createSession(string $userUuid): string
  {
    $sessionHash = bin2hex(random_bytes(16));
    Authentication::setSession($sessionHash, $userUuid);
    return $sessionHash;
  }

  private function cleanupUser(string $userUuid, string $email): void
  {
    Database::unlink(Keys::USER . ':' . $userUuid);
    Database::unlink(Keys::accountRecoveryActiveTransaction($userUuid));
    Database::unlink(Keys::USER . ':' . $userUuid . ':passkey_wrapped_deks');
    foreach (Database::scanKeys(Keys::SESSION . ':*') as $sessionKey) {
      if (Database::hget($sessionKey, 'user_uuid') === $userUuid) {
        Database::unlink($sessionKey);
      }
    }
    foreach (Database::scanKeys('account_recovery:txn:*') as $txnKey) {
      if (Database::hget($txnKey, 'user_uuid') === $userUuid) {
        Database::unlink($txnKey);
      }
    }
    foreach (Database::smembers(Keys::webauthnUserCredentials($userUuid)) as $credentialId) {
      Database::unlink(Keys::webauthnCredential((string) $credentialId));
    }
    Database::unlink(Keys::webauthnUserCredentials($userUuid));
    Database::unlink(Keys::EMAIL . ':' . $email);
    Database::unlink(Keys::EMAIL . $email);
  }

  private function clearRecoveryRateLimitKeys(string $route): void
  {
    foreach (Database::scanKeys('ratelimit:recovery:' . $route . ':*') as $key) {
      Database::unlink((string) $key);
    }
  }

  private function clearAllRecoveryRateLimitKeys(): void
  {
    foreach (Database::scanKeys('ratelimit:recovery:*') as $key) {
      Database::unlink((string) $key);
    }
  }

  private function clearRecoveryBlockedIp(string $clientIp): void
  {
    $ipHash = substr(hash('sha256', $clientIp), 0, 32);
    Database::unlink(Keys::accountRecoveryBlockedIp($ipHash));
  }

  public function testStartUnknownEmailReturnsGenericSuccessAndTransactionData(): void
  {
    $this->clearRecoveryRateLimitKeys('start');
    $email = 'unknown-' . bin2hex(random_bytes(4)) . '@example.com';
    $response = $this->runControllerCall('start', ['email' => $email]);

    $this->assertSame('success', $response['status'] ?? null);
    $this->assertNotEmpty($response['data']['txnId'] ?? '');
    $this->assertNotEmpty($response['data']['txnSecret'] ?? '');

    $txnId = (string) ($response['data']['txnId'] ?? '');
    if ($txnId !== '') {
      Database::unlink(Keys::accountRecoveryTransaction($txnId));
    }
  }

  public function testVerifyProofAndBootstrapHappyPath(): void
  {
    $fixture = $this->createUserWithRecoveryMaterial();
    $created = AccountRecoveryTransaction::create('recover@example.com', $fixture['userUuid'], 'Recovery Test User');
    /** @var AccountRecoveryTransaction $transaction */
    $transaction = $created['transaction'];
    $emailCode = PayCalCode::appendChecksum('ABCD');
    $transaction->storeEmailCode($emailCode);

    $verify = $this->runControllerCall('verifyEmail', [
      'txnId' => $transaction->id(),
      'txnSecret' => $created['txnSecret'],
      'code' => $emailCode,
    ]);
    $this->assertSame('success', $verify['status'] ?? null);
    $this->assertTrue((bool) ($verify['recoveryKeyRequired'] ?? false));
    $this->assertFalse((bool) ($verify['passkeyReady'] ?? true));
    $this->assertFalse((bool) ($verify['firstPasskeySetup'] ?? true));

    $proofPayload = $this->runControllerCall('proofPayload', [
      'txnId' => $transaction->id(),
      'txnSecret' => $created['txnSecret'],
    ]);
    $this->assertSame('success', $proofPayload['status'] ?? null);
    $this->assertSame($fixture['wrappedDekRecovery'], $proofPayload['wrappedDekRecovery'] ?? null);

    $proof = RecoveryKey::generateProof(
      $fixture['recoveryProofKey'],
      $transaction->id(),
      (string) $proofPayload['proofNonce'],
      (string) $proofPayload['clientFingerprintHash']
    );

    $prove = $this->runControllerCall('proveKey', [
      'txnId' => $transaction->id(),
      'txnSecret' => $created['txnSecret'],
      'proofNonce' => $proofPayload['proofNonce'],
      'proof' => $proof,
      'recoveryCode' => $fixture['recoveryCode'],
    ]);
    $this->assertSame('success', $prove['status'] ?? null);

    $bootstrap = $this->runControllerCall('bootstrap', [
      'txnId' => $transaction->id(),
      'txnSecret' => $created['txnSecret'],
    ]);
    $this->assertSame('success', $bootstrap['status'] ?? null);
    $this->assertSame('recover@example.com', $bootstrap['email'] ?? null);
    $this->assertNotEmpty($bootstrap['encryptionSalt'] ?? '');

    $this->cleanupUser($fixture['userUuid'], 'recover@example.com');
  }

  public function testVerifyEmailAllowsFirstPasskeySetupWhenAccountHasNoCryptoMaterial(): void
  {
    $email = 'first-code-' . bin2hex(random_bytes(4)) . '@example.com';
    $fixture = $this->createUserWithoutCryptoMaterial($email);
    $created = AccountRecoveryTransaction::create($email, $fixture['userUuid'], 'First Key Test User');
    /** @var AccountRecoveryTransaction $transaction */
    $transaction = $created['transaction'];
    $emailCode = PayCalCode::appendChecksum('FQRT');
    $transaction->storeEmailCode($emailCode);

    $verify = $this->runControllerCall('verifyEmail', [
      'txnId' => $transaction->id(),
      'txnSecret' => $created['txnSecret'],
      'code' => $emailCode,
    ]);

    $this->assertSame('success', $verify['status'] ?? null);
    $this->assertFalse((bool) ($verify['recoveryKeyRequired'] ?? true));
    $this->assertTrue((bool) ($verify['passkeyReady'] ?? false));
    $this->assertTrue((bool) ($verify['firstPasskeySetup'] ?? false));
    $this->assertSame('', Database::hget(Keys::USER . ':' . $fixture['userUuid'], 'encryption_salt'));

    $bootstrap = $this->runControllerCall('bootstrap', [
      'txnId' => $transaction->id(),
      'txnSecret' => $created['txnSecret'],
    ]);

    $this->assertSame('success', $bootstrap['status'] ?? null);
    $this->assertTrue((bool) ($bootstrap['allowDekGeneration'] ?? false));
    $this->assertNotEmpty($bootstrap['encryptionSalt'] ?? '');

    $this->cleanupUser($fixture['userUuid'], $email);
  }

  public function testVerifyEmailReportsUnavailableWhenPasskeyAccountHasNoRecoveryKey(): void
  {
    $email = 'missing-recovery-key-' . bin2hex(random_bytes(4)) . '@example.com';
    $fixture = $this->createUserWithPasskeyWrapButNoRecoveryKey($email);
    $created = AccountRecoveryTransaction::create($email, $fixture['userUuid'], 'Passkey Without Recovery Key');
    /** @var AccountRecoveryTransaction $transaction */
    $transaction = $created['transaction'];
    $emailCode = PayCalCode::appendChecksum('NKEY');
    $transaction->storeEmailCode($emailCode);

    $verify = $this->runControllerCall('verifyEmail', [
      'txnId' => $transaction->id(),
      'txnSecret' => $created['txnSecret'],
      'code' => $emailCode,
    ]);

    $this->assertSame('success', $verify['status'] ?? null);
    $this->assertFalse((bool) ($verify['recoveryKeyRequired'] ?? true));
    $this->assertFalse((bool) ($verify['recoveryKeyAvailable'] ?? true));
    $this->assertTrue((bool) ($verify['recoveryUnavailable'] ?? false));
    $this->assertFalse((bool) ($verify['passkeyReady'] ?? true));
    $this->assertFalse((bool) ($verify['firstPasskeySetup'] ?? true));

    $this->cleanupUser($fixture['userUuid'], $email);
  }

  public function testVerifyEmailDoesNotAllowFirstPasskeySetupWhenExistingPasskeyExists(): void
  {
    $email = 'existing-passkey-' . bin2hex(random_bytes(4)) . '@example.com';
    $fixture = $this->createUserWithPasskeyButNoCryptoMaterial($email);
    $created = AccountRecoveryTransaction::create($email, $fixture['userUuid'], 'Existing Passkey User');
    /** @var AccountRecoveryTransaction $transaction */
    $transaction = $created['transaction'];
    $emailCode = PayCalCode::appendChecksum('PATR');
    $transaction->storeEmailCode($emailCode);

    $verify = $this->runControllerCall('verifyEmail', [
      'txnId' => $transaction->id(),
      'txnSecret' => $created['txnSecret'],
      'code' => $emailCode,
    ]);

    $this->assertSame('success', $verify['status'] ?? null);
    $this->assertFalse((bool) ($verify['passkeyReady'] ?? true));
    $this->assertFalse((bool) ($verify['firstPasskeySetup'] ?? true));
    $this->assertTrue((bool) ($verify['recoveryUnavailable'] ?? false));

    $this->cleanupUser($fixture['userUuid'], $email);
  }

  public function testMagicLinkRequiresRecoveryCodeForProtectedAccount(): void
  {
    $fixture = $this->createUserWithRecoveryMaterial('magic-protected@example.com');
    $created = AccountRecoveryTransaction::create('magic-protected@example.com', $fixture['userUuid'], 'Recovery Test User');
    /** @var AccountRecoveryTransaction $transaction */
    $transaction = $created['transaction'];
    $token = 'magic-protected-' . bin2hex(random_bytes(8));
    $this->storeMagicLinkToken($transaction, (string) $created['txnSecret'], $token);

    $response = $this->runControllerCall('consumeMagicLink', ['token' => $token]);

    $this->assertSame('success', $response['status'] ?? null);
    $this->assertFalse((bool) ($response['passkeyReady'] ?? true));
    $this->assertTrue((bool) ($response['recoveryKeyRequired'] ?? false));
    $reloaded = AccountRecoveryTransaction::load($transaction->id());
    $this->assertSame(AccountRecoveryTransaction::STATUS_EMAIL_VERIFIED, $reloaded?->status());

    $this->cleanupUser($fixture['userUuid'], 'magic-protected@example.com');
  }

  public function testMagicLinkAllowsFirstPasskeyOnlyWithoutCryptoOrPasskeys(): void
  {
    $email = 'magic-first-' . bin2hex(random_bytes(4)) . '@example.com';
    $fixture = $this->createUserWithoutCryptoMaterial($email);
    $created = AccountRecoveryTransaction::create($email, $fixture['userUuid'], 'First Key Test User');
    /** @var AccountRecoveryTransaction $transaction */
    $transaction = $created['transaction'];
    $token = 'magic-first-' . bin2hex(random_bytes(8));
    $this->storeMagicLinkToken($transaction, (string) $created['txnSecret'], $token);

    $response = $this->runControllerCall('consumeMagicLink', ['token' => $token]);

    $this->assertSame('success', $response['status'] ?? null);
    $this->assertTrue((bool) ($response['passkeyReady'] ?? false));
    $this->assertTrue((bool) ($response['firstPasskeySetup'] ?? false));
    $reloaded = AccountRecoveryTransaction::load($transaction->id());
    $this->assertSame(AccountRecoveryTransaction::STATUS_PROOF_VERIFIED, $reloaded?->status());

    $this->cleanupUser($fixture['userUuid'], $email);
  }

  public function testCompleteRequiresRegisteredCredentialIdMatch(): void
  {
    $fixture = $this->createUserWithoutCryptoMaterial('complete-mismatch@example.com');
    $created = $this->createMagicLinkReadyTransaction('complete-mismatch@example.com', $fixture['userUuid']);
    $transaction = $created['transaction'];
    $this->assertTrue($transaction->issueBootstrap());
    $registeredCredentialId = 'credential-new-' . bin2hex(random_bytes(4));
    $transaction->markReplacementPasskeyRegistered($registeredCredentialId);
    Database::hset(Keys::webauthnCredential($registeredCredentialId), [
      'credential_id' => $registeredCredentialId,
      'user_uuid' => $fixture['userUuid'],
      'public_key_pem' => 'test-public-key',
      'sign_count' => '0',
      'created_at' => (string) time(),
      'last_used_at' => '',
    ]);
    Database::sadd(Keys::webauthnUserCredentials($fixture['userUuid']), $registeredCredentialId);

    $response = $this->runControllerCall('complete', [
      'txnId' => $transaction->id(),
      'txnSecret' => $created['txnSecret'],
      'credentialId' => 'credential-other',
      'wrappedDekPasskey' => $this->validWrappedDekEnvelope(),
    ]);

    $this->assertSame('error', $response['status'] ?? null);
    $this->assertSame('Recovery completion failed.', $response['message'] ?? null);

    $this->cleanupUser($fixture['userUuid'], 'complete-mismatch@example.com');
  }

  public function testCompleteRevokesPreviousPasskeysAfterRecovery(): void
  {
    $fixture = $this->createUserWithoutCryptoMaterial('complete-revokes@example.com');
    $created = $this->createMagicLinkReadyTransaction('complete-revokes@example.com', $fixture['userUuid']);
    $transaction = $created['transaction'];
    $this->assertTrue($transaction->issueBootstrap());
    $oldCredentialId = 'credential-old-' . bin2hex(random_bytes(4));
    $newCredentialId = 'credential-new-' . bin2hex(random_bytes(4));
    foreach ([$oldCredentialId, $newCredentialId] as $credentialId) {
      Database::hset(Keys::webauthnCredential($credentialId), [
        'credential_id' => $credentialId,
        'user_uuid' => $fixture['userUuid'],
        'public_key_pem' => 'test-public-key',
        'sign_count' => '0',
        'created_at' => (string) time(),
        'last_used_at' => '',
      ]);
      Database::sadd(Keys::webauthnUserCredentials($fixture['userUuid']), $credentialId);
    }
    $transaction->markReplacementPasskeyRegistered($newCredentialId);

    $response = $this->runControllerCall('complete', [
      'txnId' => $transaction->id(),
      'txnSecret' => $created['txnSecret'],
      'credentialId' => $newCredentialId,
      'wrappedDekPasskey' => $this->validWrappedDekEnvelope(),
    ]);

    $this->assertSame('success', $response['status'] ?? null);
    $oldCredential = Database::hgetall(Keys::webauthnCredential($oldCredentialId));
    $newCredential = Database::hgetall(Keys::webauthnCredential($newCredentialId));
    $this->assertNotSame('', (string) ($oldCredential['revoked_at'] ?? ''));
    $this->assertSame('account_recovery', $oldCredential['revoked_reason'] ?? null);
    $this->assertSame('', (string) ($newCredential['revoked_at'] ?? ''));

    $this->cleanupUser($fixture['userUuid'], 'complete-revokes@example.com');
  }

  public function testBootstrapAllowsFirstDekGenerationWhenAccountHasNoCryptoMaterial(): void
  {
    $email = 'first-key-' . bin2hex(random_bytes(4)) . '@example.com';
    $fixture = $this->createUserWithoutCryptoMaterial($email);
    $created = $this->createMagicLinkReadyTransaction($email, $fixture['userUuid']);

    $bootstrap = $this->runControllerCall('bootstrap', [
      'txnId' => $created['transaction']->id(),
      'txnSecret' => $created['txnSecret'],
    ]);

    $this->assertSame('success', $bootstrap['status'] ?? null);
    $this->assertTrue((bool) ($bootstrap['allowDekGeneration'] ?? false));
    $this->assertFalse((bool) ($bootstrap['hasExistingCryptoMaterial'] ?? true));
    $this->assertFalse((bool) ($bootstrap['hasEncryptedEntries'] ?? true));
    $this->assertNotEmpty($bootstrap['encryptionSalt'] ?? '');
    $this->assertSame(
      (string) ($bootstrap['encryptionSalt'] ?? ''),
      Database::hget(Keys::USER . ':' . $fixture['userUuid'], 'encryption_salt')
    );

    $this->cleanupUser($fixture['userUuid'], $email);
  }

  public function testBootstrapDoesNotAllowFirstDekGenerationWhenEncryptedWorkExists(): void
  {
    $email = 'encrypted-work-' . bin2hex(random_bytes(4)) . '@example.com';
    $fixture = $this->createUserWithoutCryptoMaterial($email);
    $workKey = Keys::WORK . ':' . $fixture['userUuid'] . ':encrypted-fixture';
    Database::hset($workKey, ['encrypted_blob' => base64_encode('encrypted-fixture')]);
    $created = $this->createMagicLinkReadyTransaction($email, $fixture['userUuid']);

    try {
      $bootstrap = $this->runControllerCall('bootstrap', [
        'txnId' => $created['transaction']->id(),
        'txnSecret' => $created['txnSecret'],
      ]);

      $this->assertSame('success', $bootstrap['status'] ?? null);
      $this->assertFalse((bool) ($bootstrap['allowDekGeneration'] ?? true));
      $this->assertFalse((bool) ($bootstrap['hasExistingCryptoMaterial'] ?? true));
      $this->assertTrue((bool) ($bootstrap['hasEncryptedEntries'] ?? false));
      $this->assertSame('', (string) ($bootstrap['encryptionSalt'] ?? ''));
    } finally {
      Database::unlink($workKey);
      $this->cleanupUser($fixture['userUuid'], $email);
    }
  }

  public function testBackfillRecoveryMaterialStoresFields(): void
  {
    $fixture = $this->createUserWithRecoveryMaterial('backfill@example.com');
    $sessionHash = $this->createSession($fixture['userUuid']);

    $response = $this->runControllerCall('backfillRecoveryMaterial', [
      'wrappedDekRecovery' => $fixture['wrappedDekRecovery'],
      'recoveryProofKey' => $fixture['recoveryProofKey'],
      'accountRecoverySalt' => $fixture['recoverySalt'],
    ], $sessionHash);

    $this->assertSame('success', $response['status'] ?? null);
    $this->assertSame($fixture['wrappedDekRecovery'], Database::hget(Keys::USER . ':' . $fixture['userUuid'], 'wrapped_dek_recovery'));

    $this->cleanupUser($fixture['userUuid'], 'backfill@example.com');
  }

  public function testStartRouteReturns429WithQuotaMetadataWhenLimitExceeded(): void
  {
    $this->clearRecoveryRateLimitKeys('start');

    $original = SystemConfig::get('account_recovery_max_starts_per_day');
    SystemConfig::set('account_recovery_max_starts_per_day', 1);

    try {
      $first = $this->runControllerCall('start', ['email' => 'limit-a@example.com']);
      $this->assertSame('success', $first['status'] ?? null);

      $second = $this->runControllerCall('start', ['email' => 'limit-b@example.com']);
      $this->assertSame('error', $second['status'] ?? null);
      $this->assertSame('Recovery rate limit exceeded.', $second['message'] ?? null);
      $this->assertSame('start', $second['route'] ?? null);
      $this->assertSame(1, (int) ($second['quota'] ?? 0));
      $this->assertSame(86400, (int) ($second['window_seconds'] ?? 0));
    } finally {
      SystemConfig::set('account_recovery_max_starts_per_day', $original);
      $this->clearRecoveryRateLimitKeys('start');
    }
  }

  public function testVerifyEmailRouteReturns429WithQuotaMetadataWhenLimitExceeded(): void
  {
    $this->clearRecoveryRateLimitKeys('verify-email');

    $fixture = $this->createUserWithRecoveryMaterial('verify-limit@example.com');
    $created = AccountRecoveryTransaction::create('verify-limit@example.com', $fixture['userUuid'], 'Recovery Test User');
    /** @var AccountRecoveryTransaction $transaction */
    $transaction = $created['transaction'];

    $original = SystemConfig::get('account_recovery_max_verify_attempts');
    SystemConfig::set('account_recovery_max_verify_attempts', 1);

    try {
      $first = $this->runControllerCall('verifyEmail', [
        'txnId' => $transaction->id(),
        'txnSecret' => $created['txnSecret'],
        'code' => 'WRONG1',
      ]);
      $this->assertSame('error', $first['status'] ?? null);
      $this->assertSame('Recovery verification failed.', $first['message'] ?? null);

      $second = $this->runControllerCall('verifyEmail', [
        'txnId' => $transaction->id(),
        'txnSecret' => $created['txnSecret'],
        'code' => 'WRONG2',
      ]);
      $this->assertSame('error', $second['status'] ?? null);
      $this->assertSame('Recovery rate limit exceeded.', $second['message'] ?? null);
      $this->assertSame('verify-email', $second['route'] ?? null);
      $this->assertSame(1, (int) ($second['quota'] ?? 0));
      $this->assertSame(3600, (int) ($second['window_seconds'] ?? 0));
    } finally {
      SystemConfig::set('account_recovery_max_verify_attempts', $original);
      $this->clearRecoveryRateLimitKeys('verify-email');
      $this->cleanupUser($fixture['userUuid'], 'verify-limit@example.com');
    }
  }

  public function testResendRouteReturns429WithQuotaMetadataWhenLimitExceeded(): void
  {
    $this->clearRecoveryRateLimitKeys('resend');

    $original = SystemConfig::get('account_recovery_max_resends_per_hour');
    SystemConfig::set('account_recovery_max_resends_per_hour', 1);

    $fakeTxn = 'resend-saturation-txn';

    try {
      $first = $this->runControllerCall('resend', ['txnId' => $fakeTxn]);
      $this->assertSame('error', $first['status'] ?? null);
      $this->assertNotSame('Recovery rate limit exceeded.', $first['message'] ?? null);

      $second = $this->runControllerCall('resend', ['txnId' => $fakeTxn]);
      $this->assertSame('error', $second['status'] ?? null);
      $this->assertSame('Recovery rate limit exceeded.', $second['message'] ?? null);
      $this->assertSame('resend', $second['route'] ?? null);
      $this->assertSame(1, (int) ($second['quota'] ?? 0));
      $this->assertSame(3600, (int) ($second['window_seconds'] ?? 0));
    } finally {
      SystemConfig::set('account_recovery_max_resends_per_hour', $original);
      $this->clearRecoveryRateLimitKeys('resend');
    }
  }

  public function testProofPayloadRouteReturns429WithQuotaMetadataWhenLimitExceeded(): void
  {
    $this->clearRecoveryRateLimitKeys('proof-payload');

    $original = SystemConfig::get('account_recovery_max_verify_attempts');
    SystemConfig::set('account_recovery_max_verify_attempts', 1);

    $fakeTxn = 'proof-payload-saturation-txn';

    try {
      $first = $this->runControllerCall('proofPayload', ['txnId' => $fakeTxn]);
      $this->assertSame('error', $first['status'] ?? null);
      $this->assertNotSame('Recovery rate limit exceeded.', $first['message'] ?? null);

      $second = $this->runControllerCall('proofPayload', ['txnId' => $fakeTxn]);
      $this->assertSame('error', $second['status'] ?? null);
      $this->assertSame('Recovery rate limit exceeded.', $second['message'] ?? null);
      $this->assertSame('proof-payload', $second['route'] ?? null);
      $this->assertSame(1, (int) ($second['quota'] ?? 0));
      $this->assertSame(3600, (int) ($second['window_seconds'] ?? 0));
    } finally {
      SystemConfig::set('account_recovery_max_verify_attempts', $original);
      $this->clearRecoveryRateLimitKeys('proof-payload');
    }
  }

  public function testCancelRouteReturns429WithQuotaMetadataWhenLimitExceeded(): void
  {
    $this->clearRecoveryRateLimitKeys('cancel');

    $original = SystemConfig::get('account_recovery_max_verify_attempts');
    SystemConfig::set('account_recovery_max_verify_attempts', 1);

    $fakeTxn = 'cancel-saturation-txn';

    try {
      $first = $this->runControllerCall('cancel', ['txnId' => $fakeTxn]);
      $this->assertSame('success', $first['status'] ?? null); // cancel succeeds even for unknown txn
      $this->assertNotSame('Recovery rate limit exceeded.', $first['message'] ?? null);

      $second = $this->runControllerCall('cancel', ['txnId' => $fakeTxn]);
      $this->assertSame('error', $second['status'] ?? null);
      $this->assertSame('Recovery rate limit exceeded.', $second['message'] ?? null);
      $this->assertSame('cancel', $second['route'] ?? null);
      $this->assertSame(1, (int) ($second['quota'] ?? 0));
      $this->assertSame(3600, (int) ($second['window_seconds'] ?? 0));
    } finally {
      SystemConfig::set('account_recovery_max_verify_attempts', $original);
      $this->clearRecoveryRateLimitKeys('cancel');
    }
  }

  public function testProveKeyRouteReturns429WithQuotaMetadataWhenLimitExceeded(): void
  {
    $this->clearRecoveryRateLimitKeys('prove-key');

    $original = SystemConfig::get('account_recovery_max_verify_attempts');
    SystemConfig::set('account_recovery_max_verify_attempts', 1);

    $fakeTxn = 'prove-key-saturation-txn';

    try {
      $first = $this->runControllerCall('proveKey', [
        'txnId' => $fakeTxn,
        'proof' => 'invalid-proof',
        'proofNonce' => 'invalid-proof-nonce',
      ]);
      $this->assertSame('error', $first['status'] ?? null);
      $this->assertNotSame('Recovery rate limit exceeded.', $first['message'] ?? null);

      $second = $this->runControllerCall('proveKey', [
        'txnId' => $fakeTxn,
        'proof' => 'invalid-proof',
        'proofNonce' => 'invalid-proof-nonce',
      ]);
      $this->assertSame('error', $second['status'] ?? null);
      $this->assertSame('Recovery rate limit exceeded.', $second['message'] ?? null);
      $this->assertSame('prove-key', $second['route'] ?? null);
      $this->assertSame(1, (int) ($second['quota'] ?? 0));
      $this->assertSame(3600, (int) ($second['window_seconds'] ?? 0));
    } finally {
      SystemConfig::set('account_recovery_max_verify_attempts', $original);
      $this->clearRecoveryRateLimitKeys('prove-key');
    }
  }

  public function testBootstrapRouteReturns429WithQuotaMetadataWhenLimitExceeded(): void
  {
    $this->clearRecoveryRateLimitKeys('bootstrap');

    $original = SystemConfig::get('account_recovery_max_verify_attempts');
    SystemConfig::set('account_recovery_max_verify_attempts', 1);

    $fakeTxn = 'bootstrap-saturation-txn';

    try {
      $first = $this->runControllerCall('bootstrap', ['txnId' => $fakeTxn]);
      $this->assertSame('error', $first['status'] ?? null);
      $this->assertNotSame('Recovery rate limit exceeded.', $first['message'] ?? null);

      $second = $this->runControllerCall('bootstrap', ['txnId' => $fakeTxn]);
      $this->assertSame('error', $second['status'] ?? null);
      $this->assertSame('Recovery rate limit exceeded.', $second['message'] ?? null);
      $this->assertSame('bootstrap', $second['route'] ?? null);
      $this->assertSame(1, (int) ($second['quota'] ?? 0));
      $this->assertSame(3600, (int) ($second['window_seconds'] ?? 0));
    } finally {
      SystemConfig::set('account_recovery_max_verify_attempts', $original);
      $this->clearRecoveryRateLimitKeys('bootstrap');
    }
  }

  public function testRegisterPasskeyStartRouteReturns429WithQuotaMetadataWhenLimitExceeded(): void
  {
    $this->clearRecoveryRateLimitKeys('register-passkey-start');

    $original = SystemConfig::get('account_recovery_max_verify_attempts');
    SystemConfig::set('account_recovery_max_verify_attempts', 1);

    $fakeTxn = 'register-passkey-start-saturation-txn';

    try {
      $first = $this->runControllerCall('registerPasskeyStart', [
        'txnId' => $fakeTxn,
        'deviceName' => 'Recovered Passkey',
      ]);
      $this->assertSame('error', $first['status'] ?? null);
      $this->assertNotSame('Recovery rate limit exceeded.', $first['message'] ?? null);

      $second = $this->runControllerCall('registerPasskeyStart', [
        'txnId' => $fakeTxn,
        'deviceName' => 'Recovered Passkey',
      ]);
      $this->assertSame('error', $second['status'] ?? null);
      $this->assertSame('Recovery rate limit exceeded.', $second['message'] ?? null);
      $this->assertSame('register-passkey-start', $second['route'] ?? null);
      $this->assertSame(1, (int) ($second['quota'] ?? 0));
      $this->assertSame(3600, (int) ($second['window_seconds'] ?? 0));
    } finally {
      SystemConfig::set('account_recovery_max_verify_attempts', $original);
      $this->clearRecoveryRateLimitKeys('register-passkey-start');
    }
  }

  public function testRegisterPasskeyFinishRouteReturns429WithQuotaMetadataWhenLimitExceeded(): void
  {
    $this->clearRecoveryRateLimitKeys('register-passkey-finish');

    $original = SystemConfig::get('account_recovery_max_verify_attempts');
    SystemConfig::set('account_recovery_max_verify_attempts', 1);

    $fakeTxn = 'register-passkey-finish-saturation-txn';

    try {
      $first = $this->runControllerCall('registerPasskeyFinish', [
        'txnId' => $fakeTxn,
        'challengeId' => 'missing-challenge',
      ]);
      $this->assertSame('error', $first['status'] ?? null);
      $this->assertNotSame('Recovery rate limit exceeded.', $first['message'] ?? null);

      $second = $this->runControllerCall('registerPasskeyFinish', [
        'txnId' => $fakeTxn,
        'challengeId' => 'missing-challenge',
      ]);
      $this->assertSame('error', $second['status'] ?? null);
      $this->assertSame('Recovery rate limit exceeded.', $second['message'] ?? null);
      $this->assertSame('register-passkey-finish', $second['route'] ?? null);
      $this->assertSame(1, (int) ($second['quota'] ?? 0));
      $this->assertSame(3600, (int) ($second['window_seconds'] ?? 0));
    } finally {
      SystemConfig::set('account_recovery_max_verify_attempts', $original);
      $this->clearRecoveryRateLimitKeys('register-passkey-finish');
    }
  }

  public function testCompleteRouteReturns429WithQuotaMetadataWhenLimitExceeded(): void
  {
    $this->clearRecoveryRateLimitKeys('complete');

    $original = SystemConfig::get('account_recovery_max_verify_attempts');
    SystemConfig::set('account_recovery_max_verify_attempts', 1);

    $fakeTxn = 'complete-saturation-txn';

    try {
      $first = $this->runControllerCall('complete', [
        'txnId' => $fakeTxn,
        'credentialId' => 'missing-credential',
        'wrappedDekPasskey' => base64_encode('{"v":1}'),
      ]);
      $this->assertSame('error', $first['status'] ?? null);
      $this->assertNotSame('Recovery rate limit exceeded.', $first['message'] ?? null);

      $second = $this->runControllerCall('complete', [
        'txnId' => $fakeTxn,
        'credentialId' => 'missing-credential',
        'wrappedDekPasskey' => base64_encode('{"v":1}'),
      ]);
      $this->assertSame('error', $second['status'] ?? null);
      $this->assertSame('Recovery rate limit exceeded.', $second['message'] ?? null);
      $this->assertSame('complete', $second['route'] ?? null);
      $this->assertSame(1, (int) ($second['quota'] ?? 0));
      $this->assertSame(3600, (int) ($second['window_seconds'] ?? 0));
    } finally {
      SystemConfig::set('account_recovery_max_verify_attempts', $original);
      $this->clearRecoveryRateLimitKeys('complete');
    }
  }
}
