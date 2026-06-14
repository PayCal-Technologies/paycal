<?php declare(strict_types=1);

namespace PayCal\Tests\Soc2;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * CC8 — Change Management Evidence Invariants
 *
 * Purpose:
 *   Verify that the change-management evidence artifacts required for SOC2
 *   CC8 compliance exist, are non-empty, and contain the expected structural
 *   fields.  These are file-existence + structural-integrity checks against
 *   generated runtime evidence; they do not re-run the export scripts.
 *
 * Why here:
 *   SOC2 CC8 requires demonstrable controls around change approval, version
 *   gating, and promotion scope enforcement.  This suite provides auditor-
 *   verifiable, PHPUnit-backed evidence that those controls produced output.
 *   Mapped to the test-control trace by scripts/soc2-export-test-control-trace.sh.
 *
 * @internal
 */
#[Group('unit')]
#[Group('soc2')]
final class Cc8ChangeManagementInvariantsTest extends TestCase
{
  private string $repoRoot;

  protected function setUp(): void
  {
    $this->repoRoot = dirname(__DIR__, 3);
  }

  private function repoFile(string $relativePath): string
  {
    return $this->repoRoot . '/' . $relativePath;
  }

  private function readJson(string $relativePath): mixed
  {
    $path = $this->repoFile($relativePath);
    $this->assertFileExists($path, "Expected CC8 evidence file missing: {$relativePath}");
    $raw = file_get_contents($path);
    $this->assertNotFalse($raw);
    $data = json_decode((string) $raw, true);
    $this->assertIsArray($data, "CC8 evidence file is not valid JSON: {$relativePath}");
    return $data;
  }

  #[Test]
  public function changeManagementTraceExistsAndHasRequiredFields(): void
  {
    $data = $this->readJson('soc2/reports/change-management/soc2-change-management-trace-latest.json');

    $this->assertArrayHasKey('generated_at_utc', $data, 'Trace must record generation timestamp.');
    $this->assertArrayHasKey('pr_style_approval_log', $data, 'Trace must include pr_style_approval_log section.');
    $this->assertIsArray($data['pr_style_approval_log']);
  }

  #[Test]
  public function approvalsLogExistsAndHasRequiredFields(): void
  {
    $data = $this->readJson('soc2/reports/change-management/approvals-latest.json');

    $this->assertArrayHasKey('generated_at_utc', $data, 'Approvals log must record generation timestamp.');
    $this->assertArrayHasKey('approval_artifacts', $data, 'Approvals log must include approval_artifacts list.');
    $this->assertIsArray($data['approval_artifacts']);
  }

  #[Test]
  public function promotionScopeScriptExists(): void
  {
    $script = $this->repoFile('scripts/check-public-promotion-scope.sh');
    $this->assertFileExists($script, 'Promotion scope guard script must exist.');
    $this->assertGreaterThan(0, filesize($script), 'Promotion scope guard script must be non-empty.');
  }

  #[Test]
  public function sdlcChangePolicyExists(): void
  {
    $policy = $this->repoFile('soc2/policies/SDLC_CHANGE_CONTROL_POLICY.md');
    $this->assertFileExists($policy, 'SDLC change control policy must exist.');
    $content = file_get_contents($policy);
    $this->assertIsString($content);
    $this->assertStringContainsString('approval', strtolower($content), 'SDLC policy must describe approval requirements.');
  }

  #[Test]
  public function versionFileIsMonotonicallyFormatted(): void
  {
    $path = $this->repoFile('VERSION');
    $this->assertFileExists($path, 'VERSION file must exist for release traceability.');
    $version = trim((string) file_get_contents($path));
    $this->assertMatchesRegularExpression(
      '/^\d+\.\d{3}\.\d{3}$/',
      $version,
      "VERSION must match x.yyy.zzz format; got: {$version}"
    );
  }

  #[Test]
  public function preCommitHookExists(): void
  {
    $hook = $this->repoFile('githooks/pre-commit');
    $this->assertFileExists($hook, 'pre-commit hook must exist to enforce change-gate controls.');
    $this->assertGreaterThan(0, filesize($hook));
  }

  #[Test]
  public function prePushHookExists(): void
  {
    $hook = $this->repoFile('githooks/pre-push');
    $this->assertFileExists($hook, 'pre-push hook must exist to enforce PHPStan + test gate.');
    $this->assertGreaterThan(0, filesize($hook));
  }

  #[Test]
  public function reviewerRosterExistsAndHasAtLeastOneActiveReviewer(): void
  {
    $path = $this->repoFile('soc2/reports/change-management/reviewer-roster.csv');
    $this->assertFileExists($path, 'CC8 reviewer roster must exist.');

    $handle = fopen($path, 'rb');
    $this->assertNotFalse($handle);

    $header = fgetcsv($handle, 0, ',', '"', '');
    $this->assertIsArray($header);
    $this->assertContains('reviewer_id', $header);
    $this->assertContains('active', $header);

    $reviewerIdIdx = array_search('reviewer_id', $header, true);
    $activeIdx = array_search('active', $header, true);
    $this->assertIsInt($reviewerIdIdx);
    $this->assertIsInt($activeIdx);

    $activeCount = 0;
    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
      $reviewerId = trim((string) ($row[$reviewerIdIdx] ?? ''));
      $active = strtolower(trim((string) ($row[$activeIdx] ?? '')));
      if ($reviewerId !== '' && in_array($active, ['yes', 'true', '1'], true)) {
        $activeCount++;
      }
    }

    fclose($handle);

    $this->assertGreaterThan(0, $activeCount, 'CC8 reviewer roster must include at least one active reviewer.');

    $policy = (string) file_get_contents($this->repoFile('soc2/policies/SDLC_CHANGE_CONTROL_POLICY.md'));
    $this->assertStringContainsString('Reviewer Signature Block', $policy, 'SDLC policy must include reviewer signature block section.');
  }
}
