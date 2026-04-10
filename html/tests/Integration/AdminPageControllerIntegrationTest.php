<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\AuthLevel;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[Group('security')]
final class AdminPageControllerIntegrationTest extends TestCase
{
  private string $adminUUID;
  private string $targetUUID;
  private string $adminSessionHash;
  private string $targetSessionHash;
  private string $credentialId;

  /** @param array<string, string> $query */
  private function runDashboardSubprocess(array $query): string
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $config = var_export(__DIR__ . '/../../config.php', true);
    $queryExport = var_export($query, true);
    $sessionHash = var_export($this->adminSessionHash, true);

    $script = 'require ' . $bootstrap . '; '
      . 'require ' . $config . '; '
      . '$_SERVER["REQUEST_METHOD"] = "GET"; '
      . '$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; '
      . '$_SERVER["HTTP_USER_AGENT"] = "PHPUnit AdminPage"; '
      . '$_COOKIE["PAYCAL_AUTH"] = ' . $sessionHash . '; '
      . '$_GET = ' . $queryExport . '; '
      . 'ob_start(); '
      . '\\PayCal\\Controllers\\AdminPageController::dashboard(); '
      . 'echo ob_get_clean();';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);

    return (string) $output;
  }

  protected function setUp(): void
  {
    parent::setUp();

    $suffix = bin2hex(random_bytes(6));
    $this->adminUUID = 'admin-' . $suffix;
    $this->targetUUID = 'target-' . $suffix;
    $this->adminSessionHash = hash('sha256', 'admin-' . $suffix . '-' . bin2hex(random_bytes(8)));
    $this->targetSessionHash = hash('sha256', 'target-' . $suffix . '-' . bin2hex(random_bytes(8)));
    $this->credentialId = 'cid-' . bin2hex(random_bytes(6));

    Database::hset(Keys::USER . ':' . $this->adminUUID, [
      'user_uuid' => $this->adminUUID,
      'email' => 'admin-' . $suffix . '@example.com',
      'full_name' => 'Admin User',
      'email_verified' => '1',
      'auth_level' => (string) AuthLevel::ADMIN->value,
    ]);

    Database::hset(Keys::USER . ':' . $this->targetUUID, [
      'user_uuid' => $this->targetUUID,
      'email' => 'target-' . $suffix . '@example.com',
      'full_name' => 'Target User',
      'email_verified' => '1',
      'auth_level' => (string) AuthLevel::USER->value,
    ]);

    Database::hset(Keys::SESSION . ':' . $this->adminSessionHash, [
      'user_uuid' => $this->adminUUID,
      'created_at' => (string) time(),
    ]);

    Database::hset(Keys::SESSION . ':' . $this->targetSessionHash, [
      'user_uuid' => $this->targetUUID,
      'created_at' => (string) (time() - 3600),
      'last_signin' => (string) time(),
      'last_activity' => (string) time(),
    ]);

    Database::sadd(Keys::webauthnUserCredentials($this->targetUUID), $this->credentialId);
    Database::hset(Keys::webauthnCredential($this->credentialId), [
      'credential_id' => $this->credentialId,
      'user_uuid' => $this->targetUUID,
      'last_used_at' => (string) time(),
    ]);
  }

  protected function tearDown(): void
  {
    Database::unlink(Keys::USER . ':' . $this->adminUUID);
    Database::unlink(Keys::USER . ':' . $this->targetUUID);
    Database::unlink(Keys::SESSION . ':' . $this->adminSessionHash);
    Database::unlink(Keys::SESSION . ':' . $this->targetSessionHash);
    Database::unlink(Keys::webauthnCredential($this->credentialId));
    Database::unlink(Keys::webauthnUserCredentials($this->targetUUID));

    parent::tearDown();
  }

  public function testDashboardIncludesAdminEnrichmentWhenContextAllowed(): void
  {
    $output = $this->runDashboardSubprocess(['correlation_context' => 'security-incident']);

    $this->assertStringContainsString('Target User', $output);
    $this->assertStringContainsString($this->targetSessionHash, $output);
    $this->assertStringContainsString("data-credential-count='1'", $output);
    $this->assertMatchesRegularExpression("/data-last-session-at='\\d+'/", $output);
    $this->assertMatchesRegularExpression("/data-registered-at='\\d+'/", $output);
  }

  public function testDashboardOmitsAdminEnrichmentWhenContextDenied(): void
  {
    $output = $this->runDashboardSubprocess(['correlation_context' => 'unknown-correlation-context']);

    $this->assertStringContainsString('Target User', $output);
    $this->assertStringNotContainsString($this->targetSessionHash, $output);
    $this->assertStringNotContainsString("data-credential-count='1'", $output);
    $this->assertStringContainsString("data-credential-count='0'", $output);
    $this->assertStringContainsString("data-last-session-at=''", $output);
    $this->assertStringContainsString("data-last-session-hash=''", $output);
    $this->assertStringContainsString("data-registered-at=''", $output);
    $this->assertStringContainsString("data-last-passkey-used-at=''", $output);
  }

  public function testDashboardDefaultsToDenySafeEnrichmentWhenContextUnset(): void
  {
    $output = $this->runDashboardSubprocess([]);

    $this->assertStringContainsString('Target User', $output);
    $this->assertStringNotContainsString($this->targetSessionHash, $output);
    $this->assertStringContainsString("data-credential-count='0'", $output);
    $this->assertStringContainsString("data-last-session-at=''", $output);
    $this->assertStringContainsString("data-last-session-hash=''", $output);
    $this->assertStringContainsString("data-last-passkey-used-at=''", $output);
  }
}
