<?php declare(strict_types=1);

namespace Tests\Contract;

use PayCal\Domain\Authentication;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PHPUnit\Framework\TestCase;

/**
 * Test harness self-diagnostics.
 *
 * These checks validate assumptions the test suite relies on, so we catch
 * harness drift early (before application tests start failing in confusing ways).
 */
final class TestHarnessSelfDiagnosticContractTest extends TestCase
{
  private const PROJECT_ROOT = __DIR__ . '/../../';
  private const INTEGRATION_DIR = __DIR__ . '/../Integration';

  public function testAuthenticationCookieContractUsesPaycalAuth(): void
  {
    $previous = $_COOKIE;
    $sessionHash = str_repeat('a', 64);
    Database::hset(Keys::SESSION . ':' . $sessionHash, [
      'user_uuid' => 'contract-test-user',
      'created_at' => (string) time(),
      'last_activity' => (string) time(),
    ]);

    $_COOKIE = [];
    $_COOKIE['PAYCAL_AUTH'] = $sessionHash;
    $this->assertSame($sessionHash, Authentication::getSessionHashFromCookie());

    $_COOKIE = [];
    $_COOKIE['paycal_session'] = str_repeat('b', 64);
    $this->assertNull(Authentication::getSessionHashFromCookie());

    Database::unlink(Keys::SESSION . ':' . $sessionHash);
    $_COOKIE = $previous;
  }

  public function testIntegrationTestsDoNotUseDeprecatedSessionCookieKey(): void
  {
    $files = glob(self::INTEGRATION_DIR . '/*.php') ?: [];
    $offenders = [];

    foreach ($files as $file) {
      $content = file_get_contents($file);
      if ($content === false) {
        continue;
      }

      if (str_contains($content, "'paycal_session'") || str_contains($content, '"paycal_session"')) {
        $offenders[] = basename($file);
      }
    }

    $this->assertSame([], $offenders, 'Integration tests must use PAYCAL_AUTH cookie contract.');
  }

  public function testIntegrationTestsDoNotReferenceRemovedSitesUpdateSiteMethod(): void
  {
    $files = glob(self::INTEGRATION_DIR . '/*.php') ?: [];
    $offenders = [];

    foreach ($files as $file) {
      $content = file_get_contents($file);
      if ($content === false) {
        continue;
      }

      if (str_contains($content, 'Sites::updateSite(')) {
        $offenders[] = basename($file);
      }
    }

    $this->assertSame([], $offenders, 'Use Sites::updateSites(...) canonical API in tests.');
  }

  public function testControllerIntegrationTestFilesContainAtLeastOneTestMethod(): void
  {
    $files = glob(self::INTEGRATION_DIR . '/*ControllerIntegrationTest.php') ?: [];
    $missing = [];

    foreach ($files as $file) {
      $content = file_get_contents($file);
      if ($content === false) {
        continue;
      }

      if (!preg_match('/function\s+test[A-Za-z0-9_]+\s*\(/', $content)) {
        $missing[] = basename($file);
      }
    }

    $this->assertSame([], $missing, 'Every controller integration test file must include at least one test* method.');
  }

  public function testIntegrationTestsUsingSubprocessIsolationAreProperlyStructured(): void
  {
    $files = glob(self::INTEGRATION_DIR . '/*.php') ?: [];
    $violations = [];

    foreach ($files as $file) {
      $content = file_get_contents($file);
      if ($content === false) {
        continue;
      }

      // If test uses subprocess pattern (php -r), verify it follows proper structure
      if (preg_match('/php\s+-r/', $content)) {
        // Check for proper escapeshellarg() usage when invoking php -r
        if (!str_contains($content, 'escapeshellarg($script)')) {
          $violations[] = basename($file) . ' uses php -r but missing escapeshellarg($script)';
        }

        // Check that subprocess tests don't mix direct controller instantiation with subprocess calls
        // This would indicate inconsistent isolation strategy
        if (preg_match('/new\s+[A-Za-z]+Controller\s*\(\)/', $content) && 
            preg_match('/php\s+-r/', $content)) {
          $violations[] = basename($file) . ' mixes direct controller instantiation with subprocess isolation';
        }
      }
    }

    $this->assertSame([], $violations, 'Subprocess isolation tests must use proper shell escaping and consistent patterns.');
  }

  public function testIntegrationTestsCleanUpSuperglobalsInTearDown(): void
  {
    $files = glob(self::INTEGRATION_DIR . '/*.php') ?: [];
    $violations = [];

    foreach ($files as $file) {
      $content = file_get_contents($file);
      if ($content === false) {
        continue;
      }

      $basename = basename($file);

      // Skip tests using subprocess isolation - they don't contaminate the test process
      if (preg_match('/shell_exec\s*\(\s*[\'"]php\s+-r/', $content)) {
        continue;
      }

      $hasTearDown = (bool) preg_match('/function\s+tearDown\s*\(\)/', $content);

      // Check for direct superglobal assignments (not inside escapeshellarg or script strings)
      // This regex looks for $_COOKIE/$_POST/$_GET assignments outside of variable strings
      $modifiesCookie = (bool) preg_match('/^\s*\$_COOKIE\[/m', $content);
      $modifiesPost = (bool) preg_match('/^\s*\$_POST\[/m', $content);
      $modifiesGet = (bool) preg_match('/^\s*\$_GET\[/m', $content);
      $modifiesServer = (bool) preg_match('/^\s*\$_SERVER\[/m', $content);

      if ($modifiesCookie || $modifiesServer || $modifiesPost || $modifiesGet) {
        if (!$hasTearDown) {
          $violations[] = $basename . ' modifies superglobals but missing tearDown()';
          continue;
        }

        // Check tearDown actually cleans up
        if ($modifiesCookie && !str_contains($content, "unset(\$_COOKIE")) {
          $violations[] = $basename . ' sets $_COOKIE but tearDown() does not unset it';
        }
        if ($modifiesPost && !str_contains($content, "unset(\$_POST")) {
          $violations[] = $basename . ' sets $_POST but tearDown() does not unset it';
        }
        if ($modifiesGet && !str_contains($content, "unset(\$_GET")) {
          $violations[] = $basename . ' sets $_GET but tearDown() does not unset it';
        }
        if ($modifiesServer && !preg_match('/unset\s*\(\s*\$_SERVER\[/', $content)) {
          $violations[] = $basename . ' sets $_SERVER but tearDown() does not unset it';
        }
      }
    }

    $this->assertSame([], $violations, 'Integration tests must clean up superglobals in tearDown() to prevent test contamination.');
  }
}
