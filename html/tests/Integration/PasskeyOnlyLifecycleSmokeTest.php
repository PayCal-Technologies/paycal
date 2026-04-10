<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\Authentication;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\UserRepository;

require_once __DIR__ . '/../../tests/bootstrap.php';

#[Group('integration')]
final class PasskeyOnlyLifecycleSmokeTest extends TestCase
{
  private string $userUUID;
  private string $email;
  private string $credentialId;
  private string $sessionHash;

  protected function setUp(): void
  {
    parent::setUp();

    $suffix = substr(bin2hex(random_bytes(8)), 0, 12);
    $this->userUUID = 'U' . $suffix;
    $this->email = 'passkey-smoke-' . strtolower($suffix) . '@example.test';
    $this->credentialId = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $this->sessionHash = bin2hex(random_bytes(32));

    $this->simulateRegistration();
  }

  protected function tearDown(): void
  {
    $this->deleteUserAndAuthArtifacts();

    parent::tearDown();
  }

  public function testPasskeyOnlyRegistrationLoginAndDeletionLifecycle(): void
  {
    // Registration assertions
    $user = UserRepository::getByUUID($this->userUUID);
    $this->assertNotNull($user, 'User should exist after registration simulation.');
    $this->assertSame($this->email, $user->email);
    $this->assertSame('1', Database::hget(Keys::USER . ':' . $this->userUUID, 'webauthn_enabled'));
    $this->assertNotSame('', (string) Database::hget(Keys::USER . ':' . $this->userUUID, 'encryption_salt'));
    $this->assertSame('1', (string) Database::hget(Keys::USER . ':' . $this->userUUID, 'crypto_version'));

    // Login assertions (passkey-authenticated session)
    Authentication::setSession($this->sessionHash, $this->userUUID);
    Database::hset(Keys::SESSION . ':' . $this->sessionHash, [
      'auth_method' => 'passkey',
      'auth_strength' => 'strong',
    ]);

    $_COOKIE['PAYCAL_AUTH'] = $this->sessionHash;
    $this->assertTrue(Authentication::validateAndTouchSession(), 'Passkey session should validate.');
    $this->assertSame($this->userUUID, Authentication::getUserUUIDFromSession($this->sessionHash));

    // Deletion assertions
    $this->deleteUserAndAuthArtifacts();

    $this->assertNull(UserRepository::getByUUID($this->userUUID), 'User should be deleted.');
    $this->assertSame('', UserRepository::getUUIDFromEmail($this->email), 'Email mapping should be removed.');
    $this->assertFalse(Database::exists(Keys::SESSION . ':' . $this->sessionHash), 'Session should be removed.');
    $this->assertFalse(Database::exists('webauthn:credential:' . $this->credentialId), 'Credential record should be removed.');
    $this->assertSame(0, (int) (Database::scard('webauthn:user:' . $this->userUUID . ':credentials') ?? 0));
  }

  private function simulateRegistration(): void
  {
    UserRepository::setUser(
      $this->userUUID,
      password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
      $this->email,
      AuthLevel::USER,
      'Passkey Smoke User',
      '',
      ''
    );

    Database::hset(Keys::USER . ':' . $this->userUUID, [
      'webauthn_enabled' => '1',
      'encryption_salt' => base64_encode(random_bytes(32)),
      'crypto_version' => '1',
      'last_auth_method' => 'passkey',
    ]);

    Database::hset('webauthn:credential:' . $this->credentialId, [
      'credential_id' => $this->credentialId,
      'user_uuid' => $this->userUUID,
      'public_key_pem' => '-----BEGIN PUBLIC KEY-----\nTEST\n-----END PUBLIC KEY-----',
      'sign_count' => '0',
      'transports' => '["internal"]',
      'device_name' => 'Smoke Device',
      'created_at' => (string) time(),
      'last_used_at' => (string) time(),
    ]);

    Database::sadd('webauthn:user:' . $this->userUUID . ':credentials', $this->credentialId);
  }

  private function deleteUserAndAuthArtifacts(): void
  {
    Database::unlink('webauthn:credential:' . $this->credentialId);
    Database::unlink('webauthn:user:' . $this->userUUID . ':credentials');

    Database::unlink(Keys::SESSION . ':' . $this->sessionHash);
    Database::unlink(Keys::USER . ':' . $this->userUUID);

    $emailKey = Keys::EMAIL . ':' . $this->email;
    $legacyEmailKey = Keys::EMAIL . $this->email;
    Database::unlink($emailKey);
    Database::unlink($legacyEmailKey);

    unset($_COOKIE['PAYCAL_AUTH']);
  }
}
