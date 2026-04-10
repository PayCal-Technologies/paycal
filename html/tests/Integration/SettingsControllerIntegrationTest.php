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
}
