<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Domain\Authentication;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\FederatedAuth;
use PayCal\Domain\UserRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[Group('security')]
final class FederatedAuthControllerIntegrationTest extends TestCase
{
  private string $userUUID;
  private string $email;
  private string $sessionHash;
  private string $subject;

  protected function setUp(): void
  {
    parent::setUp();

    $suffix = bin2hex(random_bytes(6));
    $this->userUUID = 'fed-auth-user-' . $suffix;
    $this->email = 'fed-auth-' . $suffix . '@example.test';
    $this->sessionHash = bin2hex(random_bytes(32));
    $this->subject = 'google-subject-' . $suffix;

    UserRepository::setUser(
      $this->userUUID,
      $this->email,
      AuthLevel::USER,
      'Federated Auth Test',
      '',
      '',
    );
    Database::hset(Keys::USER . ':' . $this->userUUID, [
      'email_verified' => '1',
    ]);

    Authentication::setSession($this->sessionHash, $this->userUUID);
    Database::hset(Keys::SESSION . ':' . $this->sessionHash, [
      'auth_method' => 'passkey',
      'auth_strength' => 'strong',
      'passkey_stepup_at' => (string) time(),
    ]);

    $this->linkGoogleProvider();
  }

  protected function tearDown(): void
  {
    FederatedAuth::unlinkProviderIdentity($this->userUUID, 'google');
    Database::unlink(Keys::SESSION . ':' . $this->sessionHash);
    Database::unlink(Keys::USER . ':' . $this->userUUID);
    Database::unlink(Keys::EMAIL . ':' . $this->email);

    parent::tearDown();
  }

  public function testUnlinkRejectsStrongSessionWithoutSettingsCsrfToken(): void
  {
    $payload = $this->invokeUnlink(['provider' => 'google']);

    $this->assertSame('error', $payload['status'] ?? null, json_encode($payload));
    $this->assertSame(403, (int) ($payload['__http_code'] ?? 0));
    $this->assertStringContainsString('CSRF', (string) ($payload['message'] ?? ''));
    $this->assertNotSame([], FederatedAuth::linkedProvider($this->userUUID, 'google'));
  }

  public function testUnlinkAllowsStrongSessionWithValidSettingsCsrfToken(): void
  {
    $csrfToken = $this->settingsCsrfToken();
    $payload = $this->invokeUnlink([
      'provider' => 'google',
      'csrf_token' => $csrfToken,
    ]);

    $this->assertSame('success', $payload['status'] ?? null, json_encode($payload));
    $this->assertSame(200, (int) ($payload['__http_code'] ?? 0));
    $this->assertSame([], FederatedAuth::linkedProvider($this->userUUID, 'google'));
  }

  public function testUnlinkAllowsValidSettingsCsrfHeader(): void
  {
    $csrfToken = $this->settingsCsrfToken();
    $payload = $this->invokeUnlink(
      ['provider' => 'google'],
      ['HTTP_X_CSRF_TOKEN' => $csrfToken],
    );

    $this->assertSame('success', $payload['status'] ?? null, json_encode($payload));
    $this->assertSame(200, (int) ($payload['__http_code'] ?? 0));
    $this->assertSame([], FederatedAuth::linkedProvider($this->userUUID, 'google'));
  }

  public function testUnlinkAllowsAppleProviderWithValidSettingsCsrfToken(): void
  {
    FederatedAuth::linkProviderIdentity($this->userUUID, 'apple', [
      'sub' => 'apple-subject-' . bin2hex(random_bytes(4)),
      'email' => $this->email,
      'email_verified' => 'true',
    ]);

    try {
      $csrfToken = $this->settingsCsrfToken();
      $payload = $this->invokeUnlink([
        'provider' => 'apple',
        'csrf_token' => $csrfToken,
      ]);

      $this->assertSame('success', $payload['status'] ?? null, json_encode($payload));
      $this->assertSame(200, (int) ($payload['__http_code'] ?? 0));
      $this->assertSame([], FederatedAuth::linkedProvider($this->userUUID, 'apple'));
    } finally {
      FederatedAuth::unlinkProviderIdentity($this->userUUID, 'apple');
    }
  }

  private function linkGoogleProvider(): void
  {
    FederatedAuth::linkProviderIdentity($this->userUUID, 'google', [
      'sub' => $this->subject,
      'email' => $this->email,
      'email_verified' => 'true',
    ]);
  }

  private function settingsCsrfToken(): string
  {
    $nonce = bin2hex(random_bytes(32));
    Database::set('user:' . $this->userUUID . ':csrf:settings:' . $nonce, (string) time(), 3600);

    return $nonce;
  }

  /**
   * @param array<string, mixed> $payload
   * @param array<string, string> $server
   * @return array<string, mixed>
   */
  private function invokeUnlink(array $payload, array $server = []): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $sessionHash = var_export($this->sessionHash, true);
    $json = var_export((string) json_encode($payload), true);
    $serverLiteral = var_export($server, true);

    $script = <<<'PHP'
if (!class_exists('FederatedAuthControllerInputStream')) {
  final class FederatedAuthControllerInputStream
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
      $data = (string) ($GLOBALS['fed_auth_controller_input'] ?? '');
      $chunk = substr($data, $this->position, $count);
      $this->position += strlen($chunk);
      return $chunk;
    }

    public function stream_eof(): bool
    {
      $data = (string) ($GLOBALS['fed_auth_controller_input'] ?? '');
      return $this->position >= strlen($data);
    }

    public function stream_stat(): array
    {
      return [];
    }
  }
}
PHP;

    $script .= ' if (!defined("PHPUNIT_COMPOSER_INSTALL")) { define("PHPUNIT_COMPOSER_INSTALL", "1"); }'
      . ' require ' . $bootstrap . ';'
      . ' $GLOBALS["fed_auth_controller_input"] = ' . $json . ';'
      . ' $_COOKIE["PAYCAL_AUTH"] = ' . $sessionHash . ';'
      . ' $_SERVER["REQUEST_METHOD"] = "POST";'
      . ' $_SERVER["REMOTE_ADDR"] = "127.0.0.1";'
      . ' $_SERVER["CONTENT_TYPE"] = "application/json";'
      . ' $_SERVER = array_merge($_SERVER, ' . $serverLiteral . ');'
      . ' stream_wrapper_unregister("php");'
      . ' stream_wrapper_register("php", FederatedAuthControllerInputStream::class);'
      . ' ob_start();'
      . ' try { (new \PayCal\Controllers\FederatedAuthController())->unlink(); $out = ob_get_clean(); }'
      . ' finally { stream_wrapper_restore("php"); unset($GLOBALS["fed_auth_controller_input"]); }'
      . ' $decoded = json_decode($out, true);'
      . ' if (is_array($decoded)) { $decoded["__http_code"] = (int) http_response_code(); echo json_encode($decoded); } else { echo $out; }';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output, 'Controller subprocess failed.');

    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded, 'Expected controller JSON output: ' . (string) $output);

    return $decoded;
  }
}
