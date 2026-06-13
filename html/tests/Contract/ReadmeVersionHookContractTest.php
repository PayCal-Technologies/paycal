<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
final class ReadmeVersionHookContractTest extends TestCase
{
  private function projectRoot(): string
  {
    return dirname(__DIR__, 3);
  }

  #[Test]
  public function readmeVersionCheckScriptExists(): void
  {
    $script = $this->projectRoot() . '/scripts/hooks/check-readme-version.sh';
    $this->assertFileExists($script);
    $this->assertGreaterThan(0, filesize($script));
  }

  #[Test]
  public function readmeVersionSyncScriptExists(): void
  {
    $script = $this->projectRoot() . '/scripts/hooks/sync-readme-version.sh';
    $this->assertFileExists($script);
    $this->assertGreaterThan(0, filesize($script));
  }

  #[Test]
  public function gitHooksSyncStageAndCommitReadmeChanges(): void
  {
    $preCommit = (string) file_get_contents($this->projectRoot() . '/scripts/hooks/pre-commit.sh');
    $prePush = (string) file_get_contents($this->projectRoot() . '/scripts/hooks/pre-push.sh');

    $this->assertStringContainsString('readme-version-hook.sh', $preCommit);
    $this->assertStringContainsString('readme-version-hook.sh', $prePush);
    $this->assertStringContainsString('readme-version-hook.sh" stage', $preCommit);
    $this->assertStringContainsString('readme-version-hook.sh" commit', $prePush);
  }

  #[Test]
  public function rootReadmeDocumentsCurrentVersionWithBlurb(): void
  {
    $version = trim((string) file_get_contents($this->projectRoot() . '/VERSION'));
    $readme = (string) file_get_contents($this->projectRoot() . '/README.md');
    $versionTag = 'v' . $version;

    $this->assertStringContainsString('Latest documented release: **' . $versionTag . '**', $readme);
    $this->assertMatchesRegularExpression(
      '/^## ' . preg_quote($versionTag, '/') . ' \\([^)]+\\)/m',
      $readme,
      'Recent Releases must include a dated heading for the current VERSION',
    );
    $this->assertMatchesRegularExpression(
      '/^## ' . preg_quote($versionTag, '/') . ' \\([^)]+\\)\R(?:.*\R)*?(?:\*\*Release Focus:\*\*|^- )/m',
      $readme,
      'Current release section must include **Release Focus:** and/or bullet summary blurb',
    );
  }
}
