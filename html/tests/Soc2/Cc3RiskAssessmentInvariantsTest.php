<?php declare(strict_types=1);

namespace PayCal\Tests\Soc2;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * CC3 — Risk Assessment Evidence Invariants
 *
 * Purpose:
 *   Verify that risk-assessment artifacts required for SOC2 CC3 compliance
 *   exist, are non-empty, and are structurally sound.  Checks that the risk
 *   register contains the expected columns, at least one critical-impact entry,
 *   and that all risks have both an owner and a review date.
 *
 * Why here:
 *   SOC2 CC3 requires a formal risk identification and assessment process with
 *   documented outputs.  This suite provides PHPUnit-backed evidence that the
 *   risk register is populated and passes structural integrity checks.
 *   Mapped to the test-control trace by scripts/soc2-export-test-control-trace.sh.
 *
 * @internal
 */
#[Group('unit')]
#[Group('soc2')]
final class Cc3RiskAssessmentInvariantsTest extends TestCase
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

  private function readCsvRows(string $relativePath): array
  {
    $path = $this->repoFile($relativePath);
    $this->assertFileExists($path, "Expected CC3 CSV missing: {$relativePath}");
    $handle = fopen($path, 'r');
    $this->assertNotFalse($handle);

    $rows = [];
    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
      $rows[] = $row;
    }
    fclose($handle);
    return $rows;
  }

  private function csvIndex(array $header, string $column): int
  {
    $idx = array_search($column, $header, true);
    $this->assertIsInt($idx, "Risk register must have column: {$column}");
    return (int) $idx;
  }

  #[Test]
  public function riskRegisterExistsWithRequiredColumns(): void
  {
    $rows = $this->readCsvRows('soc2/reports/risk-register.csv');
    $this->assertGreaterThanOrEqual(2, count($rows), 'Risk register must have header + at least one risk row.');

    $header = $rows[0];
    $required = ['risk_id', 'domain', 'description', 'likelihood', 'impact', 'owner_role', 'last_reviewed_at'];
    foreach ($required as $col) {
      $this->assertContains($col, $header, "Risk register must have column: {$col}");
    }
  }

  #[Test]
  public function riskRegisterHasAtLeastEightRisks(): void
  {
    $rows = $this->readCsvRows('soc2/reports/risk-register.csv');
    $dataRows = array_slice($rows, 1);
    $this->assertGreaterThanOrEqual(8, count($dataRows), 'Risk register must document at least 8 risks (RSK-001 through RSK-008).');
  }

  #[Test]
  public function allRisksHaveOwnerAndReviewDate(): void
  {
    $rows = $this->readCsvRows('soc2/reports/risk-register.csv');
    $this->assertGreaterThanOrEqual(2, count($rows));

    $header = $rows[0];
    $ownerIdx = $this->csvIndex($header, 'owner_role');
    $reviewIdx = $this->csvIndex($header, 'last_reviewed_at');
    $idIdx = $this->csvIndex($header, 'risk_id');

    foreach (array_slice($rows, 1) as $row) {
      $id = trim($row[$idIdx] ?? '');
      $owner = trim($row[$ownerIdx] ?? '');
      $date = trim($row[$reviewIdx] ?? '');

      $this->assertNotEmpty($owner, "Risk {$id} is missing remediation_owner_role.");
      $this->assertNotEmpty($date, "Risk {$id} is missing last_reviewed_at.");
      $this->assertMatchesRegularExpression(
        '/^\d{4}-\d{2}-\d{2}$/',
        $date,
        "Risk {$id} last_reviewed_at must be YYYY-MM-DD; got: {$date}"
      );
    }
  }

  #[Test]
  public function riskRegisterContainsCriticalImpactEntry(): void
  {
    $rows = $this->readCsvRows('soc2/reports/risk-register.csv');
    $header = $rows[0];
    $impactIdx = $this->csvIndex($header, 'impact');

    $hasCritical = false;
    foreach (array_slice($rows, 1) as $row) {
      if (isset($row[$impactIdx]) && trim($row[$impactIdx]) === 'critical') {
        $hasCritical = true;
        break;
      }
    }
    $this->assertTrue($hasCritical, 'Risk register must document at least one critical-impact risk.');
  }

  #[Test]
  public function allRiskIdsFollowExpectedFormat(): void
  {
    $rows = $this->readCsvRows('soc2/reports/risk-register.csv');
    $header = $rows[0];
    $idIdx = $this->csvIndex($header, 'risk_id');

    foreach (array_slice($rows, 1) as $row) {
      $id = trim($row[$idIdx] ?? '');
      $this->assertMatchesRegularExpression(
        '/^RSK-\d{3}$/',
        $id,
        "Risk ID must match RSK-NNN format; got: {$id}"
      );
    }
  }

  #[Test]
  public function riskManagementPolicyExists(): void
  {
    $policy = $this->repoFile('soc2/policies/RISK_MANAGEMENT_PROGRAM_POLICY.md');
    $this->assertFileExists($policy, 'Risk management program policy must exist.');
    $content = file_get_contents($policy);
    $this->assertIsString($content);
    $this->assertStringContainsString('risk', strtolower($content));
    $this->assertGreaterThan(500, strlen($content), 'Risk management policy must be substantive (> 500 bytes).');
  }

  #[Test]
  public function securityCommunicationsLogExists(): void
  {
    $rows = $this->readCsvRows('soc2/reports/security-communications-log.csv');
    $this->assertGreaterThanOrEqual(2, count($rows), 'Security communications log must have header + at least one entry.');

    $header = $rows[0];
    $this->assertContains('communication_id', $header);
    $this->assertContains('date', $header);
    $this->assertContains('type', $header);
    $this->assertContains('communicated_by', $header);
  }
}
