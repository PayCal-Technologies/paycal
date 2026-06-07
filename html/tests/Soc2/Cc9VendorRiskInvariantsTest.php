<?php declare(strict_types=1);

namespace PayCal\Tests\Soc2;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * CC9 — Risk Mitigation / Vendor Risk Evidence Invariants
 *
 * Purpose:
 *   Verify that vendor and risk register artifacts required for SOC2 CC9
 *   compliance exist, are non-empty, contain the required CSV columns, and
 *   include at least one Tier 1 vendor entry.
 *
 * Why here:
 *   SOC2 CC9 requires demonstrable controls around third-party risk management
 *   and vendor oversight.  This suite provides auditor-verifiable, PHPUnit-
 *   backed evidence that those registers are populated and structurally sound.
 *   Mapped to the test-control trace by scripts/soc2-export-test-control-trace.sh.
 *
 * @internal
 */
#[Group('unit')]
final class Cc9VendorRiskInvariantsTest extends TestCase
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
    $this->assertFileExists($path, "Expected CC9 CSV missing: {$relativePath}");
    $handle = fopen($path, 'r');
    $this->assertNotFalse($handle);

    $rows = [];
    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
      $rows[] = $row;
    }
    fclose($handle);
    return $rows;
  }

  #[Test]
  public function vendorRegisterExistsAndHasHeader(): void
  {
    $rows = $this->readCsvRows('soc2/reports/vendor-register.csv');

    $this->assertGreaterThanOrEqual(2, count($rows), 'Vendor register must have header + at least one data row.');

    $header = $rows[0];
    $this->assertContains('vendor_name', $header, 'Vendor register must have vendor_name column.');
    $this->assertContains('tier', $header, 'Vendor register must have tier column.');
    $this->assertContains('security_review_date', $header, 'Vendor register must have security_review_date column.');
  }

  #[Test]
  public function vendorRegisterContainsTierOneVendor(): void
  {
    $rows = $this->readCsvRows('soc2/reports/vendor-register.csv');
    $this->assertGreaterThanOrEqual(2, count($rows));

    $header = $rows[0];
    $tierIdx = array_search('tier', $header, true);
    $this->assertIsInt($tierIdx, 'tier column must exist in vendor register.');

    $hasTierOne = false;
    foreach (array_slice($rows, 1) as $row) {
      if (isset($row[$tierIdx]) && trim($row[$tierIdx]) === '1') {
        $hasTierOne = true;
        break;
      }
    }
    $this->assertTrue($hasTierOne, 'Vendor register must include at least one Tier 1 vendor.');
  }

  #[Test]
  public function vendorRegisterAllRowsHaveReviewDate(): void
  {
    $rows = $this->readCsvRows('soc2/reports/vendor-register.csv');
    $this->assertGreaterThanOrEqual(2, count($rows));

    $header = $rows[0];
    $reviewIdx = array_search('security_review_date', $header, true);
    $this->assertIsInt($reviewIdx);

    foreach (array_slice($rows, 1) as $i => $row) {
      $date = trim($row[$reviewIdx] ?? '');
      $this->assertNotEmpty($date, "Vendor register row {$i} is missing last_reviewed_at.");
      $this->assertMatchesRegularExpression(
        '/^\d{4}-\d{2}-\d{2}$/',
        $date,
        "Vendor register row {$i} last_reviewed_at must be YYYY-MM-DD."
      );
    }
  }

  #[Test]
  public function riskRegisterExistsAndHasHeader(): void
  {
    $rows = $this->readCsvRows('soc2/reports/risk-register.csv');

    $this->assertGreaterThanOrEqual(2, count($rows), 'Risk register must have header + at least one data row.');

    $header = $rows[0];
    $this->assertContains('risk_id', $header, 'Risk register must have risk_id column.');
    $this->assertContains('likelihood', $header, 'Risk register must have likelihood column.');
    $this->assertContains('impact', $header, 'Risk register must have impact column.');
    $this->assertContains('last_reviewed_at', $header, 'Risk register must have last_reviewed_at column.');
  }

  #[Test]
  public function riskRegisterContainsCriticalImpactEntries(): void
  {
    $rows = $this->readCsvRows('soc2/reports/risk-register.csv');
    $this->assertGreaterThanOrEqual(2, count($rows));

    $header = $rows[0];
    $impactIdx = array_search('impact', $header, true);
    $this->assertIsInt($impactIdx);

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
  public function vendorRiskPolicyExists(): void
  {
    $policy = $this->repoFile('soc2/policies/VENDOR_RISK_MANAGEMENT_POLICY.md');
    $this->assertFileExists($policy, 'Vendor risk management policy must exist.');
    $content = file_get_contents($policy);
    $this->assertIsString($content);
    $this->assertStringContainsString('tier', strtolower($content), 'Vendor policy must describe vendor tier classification.');
  }

  #[Test]
  public function plannedGcsVendorEntryIsExplicitlyTracked(): void
  {
    $rows = $this->readCsvRows('soc2/reports/vendor-register.csv');
    $this->assertGreaterThanOrEqual(2, count($rows));

    $header = $rows[0];
    $nameIdx = array_search('vendor_name', $header, true);
    $statusIdx = array_search('status', $header, true);
    $contractIdx = array_search('contract_status', $header, true);
    $reviewIdx = array_search('next_review_due', $header, true);

    $this->assertIsInt($nameIdx);
    $this->assertIsInt($statusIdx);
    $this->assertIsInt($contractIdx);
    $this->assertIsInt($reviewIdx);

    $found = false;
    foreach (array_slice($rows, 1) as $row) {
      $name = strtolower(trim($row[$nameIdx] ?? ''));
      if (!str_contains($name, 'google cloud storage')) {
        continue;
      }

      $found = true;
      $status = strtolower(trim($row[$statusIdx] ?? ''));
      $contract = strtolower(trim($row[$contractIdx] ?? ''));
      $nextReview = trim($row[$reviewIdx] ?? '');

      $this->assertSame('planned', $status, 'Planned GCS entry must remain in planned status until activation controls are complete.');
      $this->assertSame('planned', $contract, 'Planned GCS entry must have planned contract_status.');
      $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $nextReview, 'Planned GCS entry must define next_review_due date.');

      $reviewDate = DateTimeImmutable::createFromFormat('Y-m-d', $nextReview);
      $this->assertInstanceOf(DateTimeImmutable::class, $reviewDate);
      $this->assertGreaterThanOrEqual(new DateTimeImmutable('2026-04-19'), $reviewDate, 'Planned GCS entry review date must be current or future.');
      break;
    }

    $this->assertTrue($found, 'Vendor register must include Google Cloud Storage planned entry until activation is complete.');
  }

  #[Test]
  public function gcsActivationReadinessChecklistExists(): void
  {
    $path = $this->repoFile('soc2/reports/vendor-risk/gcs-activation-readiness-checklist.md');
    $this->assertFileExists($path, 'GCS activation readiness checklist must exist while vendor status is planned.');

    $content = (string) file_get_contents($path);
    $this->assertStringContainsString('MUST remain in planned/not-active state', $content);
    $this->assertStringContainsString('Sign-Off Block', $content);
  }
}
