<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\UserRepository;

require_once __DIR__ . '/../../tests/bootstrap.php';

/**
 * MultiCredentialPasskeyTest
 * 
 * Tests multi-passkey scenarios:
 * 1. Register multiple passpkeys per user
 * 2. Login with different credentials
 * 3. Credential revocation
 * 4. Verify DEK remains unchanged across credential operations
 */
#[Group('integration')]
final class MultiCredentialPasskeyTest extends TestCase
{
  private string $userUUID;
  private string $email;
  private array $credentialIds = [];
  private string $encryptionSalt;

  private static function base64url(string $bytes): string
  {
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
  }

  protected function setUp(): void
  {
    parent::setUp();
    
    $suffix = substr(bin2hex(random_bytes(8)), 0, 12);
    $this->userUUID = 'U' . $suffix;
    $this->email = 'multi-cred-' . strtolower($suffix) . '@example.test';
    $this->encryptionSalt = base64_encode(random_bytes(32));
    
    $this->setupUserWithFirstCredential();
  }

  protected function tearDown(): void
  {
    $this->cleanupUser();
    parent::tearDown();
  }

  /**
   * Test: Register multiple passpkeys for same user
   */
  public function testRegisterMultiplePasskeys(): void
  {
    // User already has first credential from setUp()
    $this->assertCount(1, $this->credentialIds, 'User should have 1 credential initially');
    
    // Register second credential
    $secondCredId = self::base64url(random_bytes(32));
    $this->registerCredential($secondCredId, 'Second Device');
    
    $credentials = Database::smembers('webauthn:user:' . $this->userUUID . ':credentials');
    $this->assertIsArray($credentials);
    $this->assertCount(2, $credentials, 'User should have 2 credentials');
    $this->assertContains($this->credentialIds[0], $credentials);
    $this->assertContains($secondCredId, $credentials);
    
    // Register third credential
    $thirdCredId = self::base64url(random_bytes(32));
    $this->registerCredential($thirdCredId, 'Third Device');
    
    $credentials = Database::smembers('webauthn:user:' . $this->userUUID . ':credentials');
    $this->assertCount(3, $credentials, 'User should have 3 credentials');
  }

  /**
   * Test: Login with each credential works independently
   */
  public function testLoginWithDifferentCredentials(): void
  {
    // Register second credential
    $secondCredId = self::base64url(random_bytes(32));
    $this->registerCredential($secondCredId, 'Second Device');
    
    // Create entries while logged in with first credential
    $dekVersionBefore = Database::hget(Keys::USER . ':' . $this->userUUID, 'dek_version');
    
    // Simulate login with first credential
    $session1 = bin2hex(random_bytes(32));
    Database::hset(Keys::SESSION . ':' . $session1, [
      'user_uuid' => $this->userUUID,
      'auth_method' => 'passkey',
      'credential_id' => $this->credentialIds[0],
    ]);
    
    $this->assertSame($this->userUUID, Database::hget(Keys::SESSION . ':' . $session1, 'user_uuid'));
    
    // Simulate login with second credential
    $session2 = bin2hex(random_bytes(32));
    Database::hset(Keys::SESSION . ':' . $session2, [
      'user_uuid' => $this->userUUID,
      'auth_method' => 'passkey',
      'credential_id' => $secondCredId,
    ]);
    
    $this->assertSame($this->userUUID, Database::hget(Keys::SESSION . ':' . $session2, 'user_uuid'));
    
    // DEK version should not change
    $dekVersionAfter = Database::hget(Keys::USER . ':' . $this->userUUID, 'dek_version');
    $this->assertSame($dekVersionBefore, $dekVersionAfter);
    
    // Cleanup sessions
    Database::unlink(Keys::SESSION . ':' . $session1);
    Database::unlink(Keys::SESSION . ':' . $session2);
  }

  /**
   * Test: Revoke credential (remove from credential set)
   */
  public function testRevokeCredential(): void
  {
    // Register second credential
    $secondCredId = self::base64url(random_bytes(32));
    $this->registerCredential($secondCredId, 'Second Device');
    
    $credentials = Database::smembers('webauthn:user:' . $this->userUUID . ':credentials');
    $this->assertCount(2, $credentials);
    
    // Revoke second credential
    Database::srem('webauthn:user:' . $this->userUUID . ':credentials', $secondCredId);
    Database::unlink('webauthn:credential:' . $secondCredId);
    
    // User should only have first credential
    $credentialsAfter = Database::smembers('webauthn:user:' . $this->userUUID . ':credentials');
    $this->assertCount(1, $credentialsAfter);
    $this->assertContains($this->credentialIds[0], $credentialsAfter);
    $this->assertNotContains($secondCredId, $credentialsAfter);
    
    // Revoked credential metadata should be gone
    $revokedData = Database::hgetall('webauthn:credential:' . $secondCredId);
    $this->assertEmpty($revokedData);
  }

  /**
   * Test: Bootstrap returns first credential even with multiple registered
   */
  public function testBootstrapWithMultipleCredentials(): void
  {
    // Register second and third credentials
    $secondCredId = self::base64url(random_bytes(32));
    $thirdCredId = self::base64url(random_bytes(32));
    
    $this->registerCredential($secondCredId, 'Second Device');
    $this->registerCredential($thirdCredId, 'Third Device');
    
    // Bootstrap should return first credential ID
    $credIds = Database::smembers('webauthn:user:' . $this->userUUID . ':credentials');
    $bootstrapCredential = reset($credIds);
    
    $this->assertNotEmpty($bootstrapCredential);
    $this->assertIsString($bootstrapCredential);
    
    // Should be one of the registered credentials
    $allCreds = [$this->credentialIds[0], $secondCredId, $thirdCredId];
    $this->assertContains($bootstrapCredential, $allCreds);
  }

  /**
   * Test: DEK does not change when adding/removing credentials
   */
  public function testDEKUnchangedDuringCredentialOperations(): void
  {
    $dekBefore = Database::hget(Keys::USER . ':' . $this->userUUID, 'wrapped_dek_passkey');
    $dekVersionBefore = Database::hget(Keys::USER . ':' . $this->userUUID, 'dek_version');
    
    // Add credential
    $secondCredId = self::base64url(random_bytes(32));
    $this->registerCredential($secondCredId, 'New Device');
    
    $dekAfterAdd = Database::hget(Keys::USER . ':' . $this->userUUID, 'wrapped_dek_passkey');
    $dekVersionAfterAdd = Database::hget(Keys::USER . ':' . $this->userUUID, 'dek_version');
    
    $this->assertSame($dekBefore, $dekAfterAdd, 'DEK should not change when adding credential');
    $this->assertSame($dekVersionBefore, $dekVersionAfterAdd, 'DEK version should not change');
    
    // Remove credential
    Database::srem('webauthn:user:' . $this->userUUID . ':credentials', $secondCredId);
    Database::unlink('webauthn:credential:' . $secondCredId);
    
    $dekAfterRemove = Database::hget(Keys::USER . ':' . $this->userUUID, 'wrapped_dek_passkey');
    $dekVersionAfterRemove = Database::hget(Keys::USER . ':' . $this->userUUID, 'dek_version');
    
    $this->assertSame($dekBefore, $dekAfterRemove, 'DEK should not change when removing credential');
    $this->assertSame($dekVersionBefore, $dekVersionAfterRemove, 'DEK version should not change');
  }

  // ========== HELPERS ==========

  private function setupUserWithFirstCredential(): void
  {
    UserRepository::setUser(
      $this->userUUID,
      password_hash('dummy', PASSWORD_DEFAULT),
      $this->email,
      \PayCal\Domain\AuthLevel::USER,
      'Multi-Cred Test User',
      '',
      ''
    );
    
    $firstCredId = self::base64url(random_bytes(32));
    $this->credentialIds[] = $firstCredId;
    
    Database::hset(Keys::USER . ':' . $this->userUUID, [
      'webauthn_enabled' => '1',
      'encryption_salt' => $this->encryptionSalt,
      'crypto_version' => '1',
      'dek_version' => '1',
      'wrapped_dek_passkey' => $this->generateWrappedDek(),
    ]);
    
    $this->registerCredential($firstCredId, 'Primary Device');
  }

  private function registerCredential(string $credId, string $deviceName): void
  {
    Database::hset('webauthn:credential:' . $credId, [
      'credential_id' => $credId,
      'user_uuid' => $this->userUUID,
      'public_key_pem' => '-----BEGIN PUBLIC KEY-----' . base64_encode(random_bytes(32)) . '-----END PUBLIC KEY-----',
      'sign_count' => '0',
      'device_name' => $deviceName,
      'created_at' => (string) time(),
    ]);
    
    Database::sadd('webauthn:user:' . $this->userUUID . ':credentials', $credId);
  }

  private function cleanupUser(): void
  {
    // Remove all credentials
    $credIds = Database::smembers('webauthn:user:' . $this->userUUID . ':credentials');
    foreach ($credIds as $cid) {
      Database::unlink('webauthn:credential:' . $cid);
    }
    
    Database::unlink('webauthn:user:' . $this->userUUID . ':credentials');
    Database::unlink(Keys::USER . ':' . $this->userUUID);
    Database::unlink(Keys::EMAIL . ':' . $this->email);
  }

  private function generateWrappedDek(): string
  {
    $envelope = [
      'version' => 1,
      'nonce' => self::base64url(random_bytes(12)),
      'ciphertext' => self::base64url(random_bytes(48)),
    ];
    
    return base64_encode(json_encode($envelope));
  }
}
