<?php declare(strict_types=1);

namespace PayCal\Tests\Soc2;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * CC2 — Communication and Information Evidence Invariants
 *
 * Purpose:
 *   Verify security communications evidence exists, has required structure,
 *   and contains recent entries with resolvable artifact links.
 *
 * Why here:
 *   CC2 controls require demonstrable communication cadence and traceability.
 *
 * @internal
 */
#[Group('unit')]
#[Group('soc2')]
final class Cc2CommunicationsInvariantsTest extends TestCase
{
  private string $repoRoot;

  protected function setUp(): void
  {
    $this->repoRoot = dirname(__DIR__, 3);
  }

  /**
   * @return list<list<string>>
   */
  private function readCsvRows(string $relativePath): array
  {
    $path = $this->repoRoot . '/' . $relativePath;
    $this->assertFileExists($path, "Expected CC2 evidence file missing: {$relativePath}");

    $handle = fopen($path, 'rb');
    $this->assertNotFalse($handle);

    $rows = [];
    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
      $rows[] = array_map(static fn($v): string => trim((string) $v), $row);
    }

    fclose($handle);
    return $rows;
  }

  private function csvIndex(array $header, string $column): int
  {
    $idx = array_search($column, $header, true);
    $this->assertIsInt($idx, "Missing expected column in communications log: {$column}");
    return $idx;
  }

  #[Test]
  public function communicationsLogHasRequiredColumns(): void
  {
    $rows = $this->readCsvRows('soc2/reports/security-communications-log.csv');
    $this->assertGreaterThanOrEqual(2, count($rows), 'Security communications log must include header + at least one row.');

    $header = $rows[0];
    $required = ['communication_id', 'date', 'type', 'subject', 'audience', 'method', 'communicated_by', 'artifact_path'];
    foreach ($required as $col) {
      $this->assertContains($col, $header, "Security communications log must include column: {$col}");
    }
  }

  #[Test]
  public function communicationsLogContainsAtLeastQuarterlyCadenceEntries(): void
  {
    $rows = $this->readCsvRows('soc2/reports/security-communications-log.csv');
    $this->assertGreaterThanOrEqual(5, count($rows), 'Security communications log should include at least 4 entries plus header.');
  }

  #[Test]
  public function communicationsEntriesHaveValidDateAndResolvableArtifacts(): void
  {
    $rows = $this->readCsvRows('soc2/reports/security-communications-log.csv');
    $header = $rows[0];

    $dateIdx = $this->csvIndex($header, 'date');
    $artifactIdx = $this->csvIndex($header, 'artifact_path');

    $today = new DateTimeImmutable('today');
    $hasRecent = false;

    foreach (array_slice($rows, 1) as $rowNum => $row) {
      $dateRaw = (string) ($row[$dateIdx] ?? '');
      $artifactPath = (string) ($row[$artifactIdx] ?? '');

      $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $dateRaw, "Row {$rowNum} date must be YYYY-MM-DD.");
      $date = DateTimeImmutable::createFromFormat('Y-m-d', $dateRaw);
      $this->assertInstanceOf(DateTimeImmutable::class, $date, "Row {$rowNum} date must parse.");

      if ($date >= $today->modify('-120 days')) {
        $hasRecent = true;
      }

      if ($artifactPath !== '' && !str_starts_with($artifactPath, 'external:')) {
        $abs = $this->repoRoot . '/' . ltrim($artifactPath, '/');
        $this->assertFileExists($abs, "Row {$rowNum} artifact path must resolve: {$artifactPath}");
      }
    }

    $this->assertTrue($hasRecent, 'Security communications log must include at least one entry in the last 120 days.');
  }
}
