<?php declare(strict_types=1);

use PayCal\Controllers\PasskeyController;
use PayCal\Domain\Authentication;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\UserRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../tests/bootstrap.php';

if (!class_exists('PasskeySessionPromotionPhpInputStream')) {
  final class PasskeySessionPromotionPhpInputStream
  {
    public mixed $context;
    private int $position = 0;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
      $this->position = 0;
      return true;
    }

    public function stream_read(int $count): string
    {
      $data = (string) ($GLOBALS['mock_php_input_passkey_session_promotion'] ?? '');
      $chunk = substr($data, $this->position, $count);
      $this->position += strlen($chunk);
      return $chunk;
    }

    public function stream_eof(): bool
    {
      $data = (string) ($GLOBALS['mock_php_input_passkey_session_promotion'] ?? '');
      return $this->position >= strlen($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function stream_stat(): array
    {
      return [];
    }
  }
}

#[Group('integration')]
final class PasskeySessionPromotionTest extends TestCase
{
  private string $userUUID;
  private string $email;
  private string $credentialId;
  private string $sessionHash;
  /** @var array<int, string> */
  private array $challengeKeys = [];

  protected function setUp(): void
  {
    parent::setUp();

    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = 'PayCal PHPUnit';

    $suffix = strtolower(substr(bin2hex(random_bytes(8)), 0, 12));
    $this->userUUID = 'U' . $suffix;
    $this->email = 'passkey-session-promotion-' . $suffix . '@example.test';
    $this->credentialId = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $this->sessionHash = bin2hex(random_bytes(32));

    UserRepository::setUser(
      $this->userUUID,
      $this->email,
      AuthLevel::USER,
      'Passkey Session Promotion',
      '',
      ''
    );

    Database::hset(Keys::USER . ':' . $this->userUUID, [
      'webauthn_enabled' => '1',
      'encryption_salt' => base64_encode(random_bytes(32)),
      'crypto_version' => '1',
      'last_auth_method' => 'federated',
    ]);

    Database::hset(Keys::webauthnCredential($this->credentialId), [
      'credential_id' => $this->credentialId,
      'user_uuid' => $this->userUUID,
      'public_key_pem' => '-----BEGIN PUBLIC KEY-----\nTEST\n-----END PUBLIC KEY-----',
      'sign_count' => '0',
      'device_name' => 'Promotion Device',
      'created_at' => (string) time(),
    ]);
    Database::sadd(Keys::webauthnUserCredentials($this->userUUID), $this->credentialId);

    Authentication::setSession($this->sessionHash, $this->userUUID);
    Database::hset(Keys::SESSION . ':' . $this->sessionHash, [
      'auth_method' => 'federated',
      'auth_strength' => 'standard',
    ]);
    $_COOKIE['PAYCAL_AUTH'] = $this->sessionHash;
  }

  protected function tearDown(): void
  {
    Database::unlink(Keys::SESSION . ':' . $this->sessionHash);
    foreach ($this->challengeKeys as $challengeKey) {
      Database::unlink($challengeKey);
    }
    Database::unlink(Keys::webauthnCredential($this->credentialId));
    Database::unlink(Keys::webauthnUserCredentials($this->userUUID));
    Database::unlink(Keys::USER . ':' . $this->userUUID);
    Database::unlink(Keys::EMAIL . ':' . $this->email);
    unset($_COOKIE['PAYCAL_AUTH']);
    unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);

    parent::tearDown();
  }

  public function testPromotesCurrentSessionToRegisteredPasskeyCredential(): void
  {
    $promoted = $this->promote($this->credentialId, '1782012345');

    $this->assertTrue($promoted);
    $sessionKey = Keys::SESSION . ':' . $this->sessionHash;
    $this->assertSame('passkey', Database::hget($sessionKey, 'auth_method'));
    $this->assertSame('strong', Database::hget($sessionKey, 'auth_strength'));
    $this->assertSame($this->credentialId, Database::hget($sessionKey, 'credential_id'));
    $this->assertSame('1782012345', Database::hget($sessionKey, 'passkey_stepup_at'));
    $this->assertSame('0', Database::hget($sessionKey, 'recovery_pending'));
    $this->assertSame('passkey', Database::hget(Keys::USER . ':' . $this->userUUID, 'last_auth_method'));
  }

  public function testDoesNotPromoteSessionForCredentialOutsideUsersSet(): void
  {
    $promoted = $this->promote('not-owned-' . bin2hex(random_bytes(4)), '1782012345');

    $this->assertFalse($promoted);
    $sessionKey = Keys::SESSION . ':' . $this->sessionHash;
    $this->assertSame('federated', Database::hget($sessionKey, 'auth_method'));
    $this->assertSame('standard', Database::hget($sessionKey, 'auth_strength'));
    $this->assertSame('', (string) Database::hget($sessionKey, 'credential_id'));
  }

  public function testLoginStartUsesAuthenticatedCurrentUserWhenEmailIsOmitted(): void
  {
    $decoded = $this->runPasskeyControllerJson('loginStart', []);

    $this->assertSame('success', $decoded['status'] ?? null);
    $challengeId = (string) ($decoded['challengeId'] ?? '');
    $this->assertNotSame('', $challengeId);

    $challengeKey = Keys::webauthnChallenge('login', $challengeId);
    $this->challengeKeys[] = $challengeKey;
    $challengeData = Database::hgetall($challengeKey);

    $this->assertSame($this->userUUID, $challengeData['user_uuid'] ?? null);
    $this->assertSame('0', $challengeData['discoverable'] ?? null);
    $this->assertNotSame('', (string) ($challengeData['challenge'] ?? ''));
  }

  private function promote(string $credentialId, string $now): bool
  {
    $controller = new PasskeyController();
    $method = new ReflectionMethod($controller, 'promoteCurrentSessionToPasskeyCredential');

    return (bool) $method->invoke($controller, $this->userUUID, $credentialId, $now);
  }

  /**
   * @param array<string, mixed> $payload
   * @return array<string, mixed>
   */
  private function runPasskeyControllerJson(string $method, array $payload): array
  {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    $GLOBALS['mock_php_input_passkey_session_promotion'] = (string) json_encode($payload);

    stream_wrapper_unregister('php');
    stream_wrapper_register('php', PasskeySessionPromotionPhpInputStream::class);

    $baseBufferLevel = ob_get_level();
    ob_start();
    try {
      $controller = new PasskeyController();
      $controller->{$method}();
      $output = (string) ob_get_clean();
    } finally {
      while (ob_get_level() > $baseBufferLevel) {
        ob_end_clean();
      }
      stream_wrapper_restore('php');
      unset($GLOBALS['mock_php_input_passkey_session_promotion']);
    }

    $decoded = json_decode($output, true);
    $this->assertIsArray($decoded, 'Expected controller JSON output: ' . $output);

    return $decoded;
  }
}
