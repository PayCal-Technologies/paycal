<?php declare(strict_types=1);

namespace PayCal\Tests\Soc2;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * CC7 — System Operations Readiness Invariants
 *
 * Purpose:
 *   Verify that the IR/DR exercise program has both completed evidence and
 *   a scheduled next exercise for Type II continuity.
 *
 * Why here:
 *   CC7 requires operational monitoring and response practices to remain
 *   active over time, not as a one-time event.
 *
 * @internal
 */
#[Group('unit')]
final class Cc7SystemOperationsReadinessInvariantsTest extends TestCase
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
    $this->assertFileExists($path, "Expected CC7 evidence file missing: {$relativePath}");

    $handle = fopen($path, 'rb');
    $this->assertNotFalse($handle);

    $rows = [];
    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
      $rows[] = array_map(static fn($value): string => trim((string) $value), $row);
    }

    fclose($handle);
    return $rows;
  }

  private function csvIndex(array $header, string $column): int
  {
    $idx = array_search($column, $header, true);
    $this->assertIsInt($idx, "Missing expected column in IR/DR exercise log: {$column}");
    return $idx;
  }

  #[Test]
  public function irDrExerciseLogHasRequiredColumns(): void
  {
    $rows = $this->readCsvRows('soc2/reports/ir-dr-exercise-log.csv');
    $this->assertGreaterThanOrEqual(2, count($rows), 'IR/DR exercise log must include header plus at least one row.');

    $header = $rows[0];
    $required = ['exercise_date', 'exercise_type', 'scenario_title', 'outcome', 'signed_off_by_role'];
    foreach ($required as $column) {
      $this->assertContains($column, $header, "IR/DR exercise log must include column: {$column}");
    }
  }

  #[Test]
  public function irDrProgramIncludesCompletedAndScheduledExercises(): void
  {
    $rows = $this->readCsvRows('soc2/reports/ir-dr-exercise-log.csv');
    $header = $rows[0];
    $dateIdx = $this->csvIndex($header, 'exercise_date');
    $outcomeIdx = $this->csvIndex($header, 'outcome');

    $today = new DateTimeImmutable('today');
    $hasCompleted = false;
    $hasScheduledFuture = false;

    foreach (array_slice($rows, 1) as $row) {
      $dateRaw = $row[$dateIdx] ?? '';
      $outcome = strtolower($row[$outcomeIdx] ?? '');

      if ($dateRaw === '') {
        continue;
      }

      $date = DateTimeImmutable::createFromFormat('Y-m-d', $dateRaw);
      $this->assertInstanceOf(DateTimeImmutable::class, $date, "exercise_date must be YYYY-MM-DD; got {$dateRaw}");

      if (str_contains($outcome, 'completed')) {
        $hasCompleted = true;
      }

      if ($outcome === 'scheduled' && $date > $today) {
        $hasScheduledFuture = true;
      }
    }

    $this->assertTrue($hasCompleted, 'IR/DR evidence must include at least one completed exercise.');
    $this->assertTrue($hasScheduledFuture, 'IR/DR evidence must include at least one scheduled future exercise.');
  }
}
