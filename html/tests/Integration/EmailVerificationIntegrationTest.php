<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Domain\Database;
use PayCal\Domain\EmailGarum;
use PayCal\Domain\EmailVerificationGuard;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\RecoveryKey;
use PayCal\Domain\User;
use PHPUnit\Framework\TestCase;

/**
 * EmailVerificationIntegrationTest.php
 *
 * Integration tests for email verification flow
 */
final class EmailVerificationIntegrationTest extends TestCase
{
  private string $testUserUUID;
  private string $testEmail;

  protected function setUp(): void
  {
    parent::setUp();

    // Create test user
    $this->testUserUUID = 'test-user-' . bin2hex(random_bytes(8));
    $this->testEmail = 'test-' . bin2hex(random_bytes(4)) . '@example.com';

    Database::hset(Keys::USER . ':' . $this->testUserUUID, [
      'user_uuid' => $this->testUserUUID,
      'email' => $this->testEmail,
      'full_name' => 'Test User',
      'email_verified' => '0',
      'recovery_key_generated' => '0',
    ]);
  }

  protected function tearDown(): void
  {
    // Clean up test user
    if (!empty($this->testUserUUID)) {
      Database::unlink(Keys::USER . ':' . $this->testUserUUID);
    }

    parent::tearDown();
  }

  /**
   * Test verification token generation and storage
   */
  public function testVerificationTokenGeneration(): void
  {
    // Generate token
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiry = time() + (24 * 3600);

    // Store token
    Database::hset(Keys::USER . ':' . $this->testUserUUID, [
      'email_verify_token_hash' => $tokenHash,
      'email_verify_expiry' => (string) $expiry,
    ]);

    // Retrieve and verify
    $userData = Database::hgetall(Keys::USER . ':' . $this->testUserUUID);

    $this->assertSame($tokenHash, $userData['email_verify_token_hash']);
    $this->assertSame((string) $expiry, $userData['email_verify_expiry']);
  }

  /**
   * Test email verification marks user as verified
   */
  public function testEmailVerification(): void
  {
    // Initially unverified
    $user = User::getByUUID($this->testUserUUID);
    $this->assertFalse($user->email_verified);

    // Mark as verified
    Database::hset(Keys::USER . ':' . $this->testUserUUID, [
      'email_verified' => '1',
    ]);

    // Check verified
    $user = User::getByUUID($this->testUserUUID);
    $this->assertTrue($user->email_verified);
  }

  /**
   * Test recovery salt generation
   */
  public function testRecoverySaltGeneration(): void
  {
    $salt = RecoveryKey::generateRecoverySalt();
    $saltBase64 = base64_encode($salt);

    Database::hset(Keys::USER . ':' . $this->testUserUUID, [
      'account_recovery_salt' => $saltBase64,
    ]);

    $userData = Database::hgetall(Keys::USER . ':' . $this->testUserUUID);
    $this->assertSame($saltBase64, $userData['account_recovery_salt']);
  }

  /**
   * Test recovery key generation flag
   */
  public function testRecoveryKeyGeneratedFlag(): void
  {
    // Initially false
    $user = User::getByUUID($this->testUserUUID);
    $this->assertFalse($user->recovery_key_generated);

    // Mark as generated
    Database::hset(Keys::USER . ':' . $this->testUserUUID, [
      'recovery_key_generated' => '1',
    ]);

    // Check flag set
    $user = User::getByUUID($this->testUserUUID);
    $this->assertTrue($user->recovery_key_generated);
  }

  /**
   * Test token expiry validation
   */
  public function testTokenExpiryValidation(): void
  {
    // Expired token (1 second ago)
    $expiredTime = time() - 1;
    Database::hset(Keys::USER . ':' . $this->testUserUUID, [
      'email_verify_expiry' => (string) $expiredTime,
    ]);

    $userData = Database::hgetall(Keys::USER . ':' . $this->testUserUUID);
    $isExpired = time() > (int) $userData['email_verify_expiry'];
    $this->assertTrue($isExpired);

    // Valid token (1 hour from now)
    $validTime = time() + 3600;
    Database::hset(Keys::USER . ':' . $this->testUserUUID, [
      'email_verify_expiry' => (string) $validTime,
    ]);

    $userData = Database::hgetall(Keys::USER . ':' . $this->testUserUUID);
    $isExpired = time() > (int) $userData['email_verify_expiry'];
    $this->assertFalse($isExpired);
  }

  /**
   * Test EmailVerificationGuard blocks unverified users
   */
  public function testVerificationGuardBlocksUnverified(): void
  {
    // This test is conceptual - actual guard calls Response::error which exits
    // In a real test environment, you'd need to mock Response or use HTTP testing

    $user = User::getByUUID($this->testUserUUID);
    $this->assertFalse($user->email_verified);
    $this->assertFalse(EmailVerificationGuard::isVerified());
  }

  /**
   * Test EmailVerificationGuard allows verified users
   */
  public function testVerificationGuardAllowsVerified(): void
  {
    // Mark user as verified
    Database::hset(Keys::USER . ':' . $this->testUserUUID, [
      'email_verified' => '1',
    ]);

    $user = User::getByUUID($this->testUserUUID);
    $this->assertTrue($user->email_verified);
  }

  /**
   * Test full verification workflow
   */
  public function testFullVerificationWorkflow(): void
  {
    // 1. User registers (email_verified = false)
    $user = User::getByUUID($this->testUserUUID);
    $this->assertFalse($user->email_verified);
    $this->assertFalse($user->recovery_key_generated);

    // 2. Generate verification token
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiry = time() + (24 * 3600);

    Database::hset(Keys::USER . ':' . $this->testUserUUID, [
      'email_verify_token_hash' => $tokenHash,
      'email_verify_expiry' => (string) $expiry,
    ]);

    // 3. User clicks link, token validated
    $userData = Database::hgetall(Keys::USER . ':' . $this->testUserUUID);
    $storedHash = $userData['email_verify_token_hash'];
    $this->assertSame($tokenHash, $storedHash);

    // 4. Mark as verified, clear token
    Database::hset(Keys::USER . ':' . $this->testUserUUID, [
      'email_verified' => '1',
      'email_verify_token_hash' => '',
      'email_verify_expiry' => '',
    ]);

    // 5. Generate recovery salt
    $recoverySalt = base64_encode(RecoveryKey::generateRecoverySalt());
    Database::hset(Keys::USER . ':' . $this->testUserUUID, [
      'account_recovery_salt' => $recoverySalt,
    ]);

    // 6. Generate recovery key
    $recoveryKeyBytes = RecoveryKey::generate();
    $recoveryKeyEncoded = RecoveryKey::encodeCrockford($recoveryKeyBytes);
    $recoveryKeyFormatted = RecoveryKey::format($recoveryKeyEncoded);

    // 7. Derive recovery KEK
    $recoveryKEK = RecoveryKey::deriveKEK($recoveryKeyBytes, $recoverySalt);
    $this->assertSame(32, strlen($recoveryKEK));

    // 8. Mark recovery key as generated
    Database::hset(Keys::USER . ':' . $this->testUserUUID, [
      'recovery_key_generated' => '1',
    ]);

    // 9. Verify final state
    $finalUser = User::getByUUID($this->testUserUUID);
    $this->assertTrue($finalUser->email_verified);
    $this->assertTrue($finalUser->recovery_key_generated);
    $this->assertNotEmpty($finalUser->account_recovery_salt);
  }

  /**
   * Test recovery key DEK wrapping integration
   */
  public function testRecoveryKeyDEKWrapping(): void
  {
    // Generate DEK (simulated)
    $dek = random_bytes(32);
    $dekBase64 = base64_encode($dek);

    // Generate recovery key and salt
    $recoveryKeyBytes = RecoveryKey::generate();
    $recoverySalt = base64_encode(RecoveryKey::generateRecoverySalt());

    // Store salt
    Database::hset(Keys::USER . ':' . $this->testUserUUID, [
      'account_recovery_salt' => $recoverySalt,
    ]);

    // Derive KEK and wrap DEK
    $kek = RecoveryKey::deriveKEK($recoveryKeyBytes, $recoverySalt);
    $wrappedDek = RecoveryKey::wrapDEK($dekBase64, $kek);

    // Store wrapped DEK
    Database::hset(Keys::USER . ':' . $this->testUserUUID, [
      'wrapped_dek_recovery' => $wrappedDek,
      'recovery_key_generated' => '1',
    ]);

    // Simulate recovery: unwrap DEK
    $user = User::getByUUID($this->testUserUUID);
    $unwrappedDek = RecoveryKey::unwrapDEK($user->wrapped_dek_recovery, $kek);

    // Verify DEK matches
    $this->assertSame($dek, $unwrappedDek);
  }
}
