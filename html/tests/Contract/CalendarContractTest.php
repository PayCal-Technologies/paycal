<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Calendar;

/**
 * Contract: calendar grid emits deterministic 6x7 day IDs.
 */
#[Group('contract')]
final class CalendarContractTest extends TestCase
{
  public function testCalendarGridHasFortyTwoDayIdsInIsoFormat(): void
  {
    $calendar = new Calendar(2026, 3, 0);
    $dayIds = iterator_to_array($calendar->getDayIDs(), false);

    $this->assertCount(42, $dayIds);

    foreach ($dayIds as $dayId) {
      $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $dayId);
    }
  }
}
