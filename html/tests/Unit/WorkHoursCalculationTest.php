<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Work;

#[Group('unit')]
final class WorkHoursCalculationTest extends TestCase
{
  #[Test]
  public function dailyRegularHoursAreCappedAcrossMultipleEntries(): void
  {
    // First entry on day: all regular.
    $first = Work::calculateHours(6.0, 0.0, 0.0);
    $this->assertSame(6.0, $first['regular_hours']);
    $this->assertSame(0.0, $first['overtime_hours']);

    // Second entry on same day: only 2 regular remain, rest becomes overtime.
    $second = Work::calculateHours(6.0, 6.0, 6.0);
    $this->assertSame(2.0, $second['regular_hours']);
    $this->assertSame(4.0, $second['overtime_hours']);
  }

  #[Test]
  public function weeklyCapStillAppliesAfterDailyCapping(): void
  {
    // Weekly running regular total is already 39h; only 1h regular can remain.
    $result = Work::calculateHours(4.0, 39.0, 4.0);

    $this->assertSame(1.0, $result['regular_hours']);
    $this->assertSame(3.0, $result['overtime_hours']);
  }
}
