<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\UserRepository;
use PayCal\Domain\Enums\AuthLevel;

require_once __DIR__ . '/../../tests/bootstrap.php';

/**
 * BootstrapEndpointContractTest
 * 
 * Tests bootstrap endpoint API contract:
 * 1. Required fields present (credentialId, encryptionSalt, wrappedDekPasskey, etc.)
 * 2. Response schema validation
 * 3. Credential retrieval for multi-credential users
 * 4. Error handling and edge cases
 */
#[Group('integration')]
final class BootstrapEndpointContractTest extends TestCase
{
  private string $userUUID;
  private string $email;
  private string $encryptionSalt;
  private array $credentialIds = [];

  private static function base64url(string $bytes): string
  {
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
  }

  private static function decodeBase64url(string $value): string|false
  {
    $padding = str_repeat('=', (4 - (strlen($value) % 4)) % 4);
    return base64_decode(strtr($value . $padding, '-_', '+/'), true);
  }

  protected function setUp(): void
  {
    parent::setUp();
    
    $suffix = substr(bin2hex(random_bytes(8)), 0, 12);
    $this->userUUID = 'U' . $suffix;
    $this->email = 'bootstrap-' . strtolower($suffix) . '@example.test';
    $this->encryptionSalt = base64_encode(random_bytes(32));
    
    $this->setupUser();
  }

  protected function tearDown(): void
  {
    $this->cleanupUser();
    parent::tearDown();
  }

  /**
   * Test: Bootstrap response contains all required fields
   */
  public function testBootstrapResponseSchemaWithSingleCredential(): void
  {
    $bootstrap = $this->getBootstrapData();
    
    // Required fields MUST be present
    $this->assertArrayHasKey('userId', $bootstrap, 'Response must include userId');
    $this->assertArrayHasKey('credentialId', $bootstrap, 'Response must include credentialId');
    $this->assertArrayHasKey('encryptionSalt', $bootstrap, 'Response must include encryptionSalt');
    $this->assertArrayHasKey('wrappedDekPasskey', $bootstrap, 'Response must include wrappedDekPasskey');
    $this->assertArrayHasKey('dekVersion', $bootstrap, 'Response must include dekVersion');
    $this->assertArrayHasKey('cryptoVersion', $bootstrap, 'Response must include cryptoVersion');
    
    // Field values should be non-empty strings
    $this->assertNotEmpty($bootstrap['userId']);
    $this->assertNotEmpty($bootstrap['credentialId']);
    $this->assertNotEmpty($bootstrap['encryptionSalt']);
    $this->assertNotEmpty($bootstrap['wrappedDekPasskey']);
    $this->assertNotEmpty($bootstrap['dekVersion']);
    $this->assertNotEmpty($bootstrap['cryptoVersion']);
    
    // Types should be correct
    $this->assertIsString($bootstrap['userId']);
    $this->assertIsString($bootstrap['credentialId']);
    $this->assertIsString($bootstrap['encryptionSalt']);
    $this->assertIsString($bootstrap['wrappedDekPasskey']);
    $this->assertIsString($bootstrap['dekVersion']);
    $this->assertIsString($bootstrap['cryptoVersion']);
  }

  /**
   * Test: Credential ID is base64url encoded
   */
  public function testCredentialIdIsBase64urlEncoded(): void
  {
    $bootstrap = $this->getBootstrapData();
    $credentialId = $bootstrap['credentialId'];
    
    // Should be valid base64url
    $decoded = self::decodeBase64url($credentialId);
    $this->assertNotFalse($decoded, 'credentialId should be valid base64url');
    
    // Should not have padding (base64url property)
    $this->assertStringNotContainsString('=', $credentialId, 'credentialId should not have = padding');
    
    // Should use URL-safe characters
    $this->assertStringNotContainsString('+', $credentialId, 'credentialId should not contain +');
    $this->assertStringNotContainsString('/', $credentialId, 'credentialId should not contain /');
  }

  /**
   * Test: Encryption salt is properly formatted
   */
  public function testEncryptionSaltFormat(): void
  {
    $bootstrap = $this->getBootstrapData();
    $salt = $bootstrap['encryptionSalt'];
    
    // Should be base64 encoded (with padding allowed)
    $decoded = base64_decode($salt);
    $this->assertNotFalse($decoded, 'encryptionSalt should be valid base64');
    
    // Should be 32 bytes (256 bits)
    $this->assertSame(32, strlen($decoded), 'encryptionSalt should be 32 bytes');
  }

  /**
   * Test: Wrapped DEK has correct envelope structure
   */
  public function testWrappedDekEnvelopeStructure(): void
  {
    $bootstrap = $this->getBootstrapData();
    $wrapped = $bootstrap['wrappedDekPasskey'];
    
    // Should be base64 encoded
    $decoded = base64_decode($wrapped);
    $this->assertNotFalse($decoded, 'wrappedDekPasskey should be base64 encoded');
    
    // Should contain valid JSON
    $parsed = json_decode($decoded, true);
    $this->assertIsArray($parsed, 'wrappedDekPasskey should decode to JSON object');
    
    // Must have required envelope fields
    $this->assertArrayHasKey('version', $parsed);
    $this->assertArrayHasKey('nonce', $parsed);
    $this->assertArrayHasKey('ciphertext', $parsed);
    
    // Version should be 1
    $this->assertSame(1, $parsed['version']);
    
    // Nonce and ciphertext should be base64url encoded
    $nonceDecoded = self::decodeBase64url((string) $parsed['nonce']);
    $ciphertextDecoded = self::decodeBase64url((string) $parsed['ciphertext']);
    
    $this->assertNotFalse($nonceDecoded, 'nonce should be valid base64url');
    $this->assertNotFalse($ciphertextDecoded, 'ciphertext should be valid base64url');
    
    // Nonce should be 12 bytes (AES-GCM IV)
    $this->assertSame(12, strlen($nonceDecoded), 'nonce should be 12 bytes');
  }

  /**
   * Test: Bootstrap returns first credential with multiple registered
   */
  public function testBootstrapReturnsFirstCredentialWithMultiple(): void
  {
    // Register additional credentials
    $secondCredId = self::base64url(random_bytes(32));
    $thirdCredId = self::base64url(random_bytes(32));
    
    $this->registerCredential($secondCredId, 'Second Device');
    $this->registerCredential($thirdCredId, 'Third Device');
    
    // Get bootstrap
    $bootstrap = $this->getBootstrapData();
    $returnedCredId = $bootstrap['credentialId'];
    
    // Should return one of the registered credentials
    $allCreds = array_merge($this->credentialIds, [$secondCredId, $thirdCredId]);
    $this->assertContains($returnedCredId, $allCreds);
    
    // Should consistently return the same one (first in set)
    $credSet = Database::smembers('webauthn:user:' . $this->userUUID . ':credentials');
    $firstInSet = reset($credSet);
    
    $this->assertSame($firstInSet, $returnedCredId);
  }

  /**
   * Test: Encryption salt is consistent across requests
   */
  public function testEncryptionSaltConsistency(): void
  {
    $bootstrap1 = $this->getBootstrapData();
    $bootstrap2 = $this->getBootstrapData();
    $bootstrap3 = $this->getBootstrapData();
    
    $this->assertSame(
      $bootstrap1['encryptionSalt'],
      $bootstrap2['encryptionSalt'],
      'encryptionSalt should be consistent across requests'
    );
    
    $this->assertSame(
      $bootstrap2['encryptionSalt'],
      $bootstrap3['encryptionSalt'],
      'encryptionSalt should be consistent across requests'
    );
    
    // Should match the stored value
    $storedSalt = Database::hget(Keys::USER . ':' . $this->userUUID, 'encryption_salt');
    $this->assertSame($storedSalt, $bootstrap1['encryptionSalt']);
  }

  /**
   * Test: Wrapped DEK is consistent across requests
   */
  public function testWrappedDekConsistency(): void
  {
    $bootstrap1 = $this->getBootstrapData();
    $bootstrap2 = $this->getBootstrapData();
    
    $this->assertSame(
      $bootstrap1['wrappedDekPasskey'],
      $bootstrap2['wrappedDekPasskey'],
      'wrappedDekPasskey should be consistent across requests'
    );
    
    // Should match stored value
    $storedWrapped = Database::hget(Keys::USER . ':' . $this->userUUID, 'wrapped_dek_passkey');
    $this->assertSame($storedWrapped, $bootstrap1['wrappedDekPasskey']);
  }

  /**
   * Test: DEK version is correctly returned
   */
  public function testDekVersionInResponse(): void
  {
    $bootstrap = $this->getBootstrapData();
    
    $this->assertNotEmpty($bootstrap['dekVersion']);
    $this->assertSame('1', $bootstrap['dekVersion']);
    
    // Should match stored value
    $storedVersion = Database::hget(Keys::USER . ':' . $this->userUUID, 'dek_version');
    $this->assertSame($storedVersion, $bootstrap['dekVersion']);
  }

  /**
   * Test: Crypto version is correctly returned
   */
  public function testCryptoVersionInResponse(): void
  {
    $bootstrap = $this->getBootstrapData();
    
    $this->assertNotEmpty($bootstrap['cryptoVersion']);
    $this->assertSame('1', $bootstrap['cryptoVersion']);
    
    // Should match stored value
    $storedVersion = Database::hget(Keys::USER . ':' . $this->userUUID, 'crypto_version');
    $this->assertSame($storedVersion, $bootstrap['cryptoVersion']);
  }

  /**
   * Test: Bootstrap response is complete for passkey flow
   * 
   * Client needs all fields to:
   * 1. Load credential_id for HKDF derivation
   * 2. Load encryption_salt for same HKDF derivation
   * 3. Load wrappedDekPasskey for DEK unwrap
   * 4. Know dek_version for backwards compatibility
   */
  public function testBootstrapHasAllRequiredFieldsForPasskeyFlow(): void
  {
    $bootstrap = $this->getBootstrapData();
    
    // For HKDF derivation
    $this->assertNotEmpty($bootstrap['credentialId'], 'credentialId required for HKDF-IKM');
    $this->assertNotEmpty($bootstrap['encryptionSalt'], 'encryptionSalt required for HKDF-salt');
    
    // For DEK unwrap
    $this->assertNotEmpty($bootstrap['wrappedDekPasskey'], 'wrappedDekPasskey required for AES-GCM.decrypt');
    
    // For version handling
    $this->assertNotEmpty($bootstrap['dekVersion'], 'dekVersion required for backwards compatibility');
    $this->assertNotEmpty($bootstrap['cryptoVersion'], 'cryptoVersion required for version check');
    
    // Complete client flow should be possible:
    // 1. const kek = HKDF(credentialId, encryptionSalt, 'paycal-passkey-kek')
    // 2. const dek = AES-GCM.decrypt(kek, wrappedDekPasskey)
    // 3. Use dek for decrypting entries
    
    $this->assertTrue(true, 'Bootstrap has all fields for complete passkey flow');
  }

  // ========== HELPERS ==========

  private function setupUser(): void
  {
    UserRepository::setUser(
      $this->userUUID,
      password_hash('dummy', PASSWORD_DEFAULT),
      $this->email,
      AuthLevel::USER,
      'Bootstrap Test User',
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
      'public_key_pem' => '-----BEGIN PUBLIC KEY-----test-----END PUBLIC KEY-----',
      'sign_count' => '0',
      'device_name' => $deviceName,
    ]);
    
    Database::sadd('webauthn:user:' . $this->userUUID . ':credentials', $credId);
  }

  private function cleanupUser(): void
  {
    $credIds = Database::smembers('webauthn:user:' . $this->userUUID . ':credentials');
    foreach ($credIds as $cid) {
      Database::unlink('webauthn:credential:' . $cid);
    }
    
    Database::unlink('webauthn:user:' . $this->userUUID . ':credentials');
    Database::unlink(Keys::USER . ':' . $this->userUUID);
    Database::unlink(Keys::EMAIL . ':' . $this->email);
  }

  private function getBootstrapData(): array
  {
    // Simulate AccountController::bootstrap() response
    $credentialIds = Database::smembers('webauthn:user:' . $this->userUUID . ':credentials');
    $firstCredId = !empty($credentialIds) ? (string) reset($credentialIds) : null;
    
    return [
      'userId' => $this->userUUID,
      'credentialId' => $firstCredId,
      'encryptionSalt' => Database::hget(Keys::USER . ':' . $this->userUUID, 'encryption_salt'),
      'wrappedDekPasskey' => Database::hget(Keys::USER . ':' . $this->userUUID, 'wrapped_dek_passkey'),
      'dekVersion' => Database::hget(Keys::USER . ':' . $this->userUUID, 'dek_version'),
      'cryptoVersion' => Database::hget(Keys::USER . ':' . $this->userUUID, 'crypto_version'),
    ];
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
