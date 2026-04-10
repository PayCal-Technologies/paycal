<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Calendar;
use PayCal\Domain\InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;

/**
 * Calendar Test Suite - Simplified Unit Tests.
 *
 * Tests constructor validation, factory methods, and date calculations.
 * Rendering tests excluded (require full app environment).
 *
 * @internal
 *
 */
#[Group('unit')]
class CalendarTest extends TestCase
{
  // ========== Constructor & Factory ==========

  #[Test]
  public function testConstructorCreatesInstanceWithValidParameters(): void
  {
    $calendar = new Calendar(2025, 1);
    $this->assertInstanceOf(Calendar::class, $calendar);
  }

  #[Test]
  public function testConstructorWithWeekStartParameter(): void
  {
    $calendar = new Calendar(2025, 1, 1);
    $this->assertInstanceOf(Calendar::class, $calendar);
  }

  #[Test]
  #[DataProvider('invalidMonthProvider')]
  public function testConstructorThrowsForInvalidMonth(int $invalidMonth): void
  {
    $this->expectException(InvalidArgumentException::class);
    new Calendar(2025, $invalidMonth);
  }

  public static function invalidMonthProvider(): array
  {
    return [
        'month 0' => [0],
        'month 13' => [13],
        'month -1' => [-1],
        'month 25' => [25],
    ];
  }

  #[Test]
  #[DataProvider('invalidYearProvider')]
  public function testConstructorThrowsForInvalidYear(int $invalidYear): void
  {
    $this->expectException(InvalidArgumentException::class);
    new Calendar($invalidYear, 1);
  }

  public static function invalidYearProvider(): array
  {
    return [
        'year 0' => [0],
        'year -1' => [-1],
        'year 10000' => [10000],
    ];
  }

  #[Test]
  public function testFromDateFactoryCreatesInstance(): void
  {
    $date = new DateTime('2025-01-15');
    $calendar = Calendar::fromDate($date);
    $this->assertInstanceOf(Calendar::class, $calendar);
  }

  #[Test]
  public function testFromDateFactoryExtractsYearAndMonth(): void
  {
    $date = new DateTime('2025-06-15');
    $calendar = Calendar::fromDate($date);
    $firstDay = $calendar->getFirstDay();
    $this->assertEquals('2025-06-01', $firstDay->format('Y-m-d'));
  }

  // ========== First/Last Day Calculations ==========

  #[Test]
  #[DataProvider('firstDayProvider')]
  public function testGetFirstDayReturnsFirstDayOfMonth(int $year, int $month, string $expected): void
  {
    $calendar = new Calendar($year, $month);
    $firstDay = $calendar->getFirstDay();

    $this->assertInstanceOf(DateTime::class, $firstDay);
    $this->assertEquals($expected, $firstDay->format('Y-m-d'));
  }

  public static function firstDayProvider(): array
  {
    return [
        'January 2025' => [2025, 1, '2025-01-01'],
        'June 2025' => [2025, 6, '2025-06-01'],
        'December 2025' => [2025, 12, '2025-12-01'],
        'February leap year' => [2024, 2, '2024-02-01'],
        'February non-leap year' => [2025, 2, '2025-02-01'],
    ];
  }

  #[Test]
  #[DataProvider('lastDayProvider')]
  public function testGetLastDayReturnsLastDayOfMonth(int $year, int $month, string $expected): void
  {
    $calendar = new Calendar($year, $month);
    $lastDay = $calendar->getLastDay();

    $this->assertInstanceOf(DateTime::class, $lastDay);
    $this->assertEquals($expected, $lastDay->format('Y-m-d'));
  }

  public static function lastDayProvider(): array
  {
    return [
        'January 2025' => [2025, 1, '2025-01-31'],
        'February leap year' => [2024, 2, '2024-02-29'],
        'February non-leap year' => [2025, 2, '2025-02-28'],
        'April 30-day' => [2025, 4, '2025-04-30'],
        'June 30-day' => [2025, 6, '2025-06-30'],
        'September 30-day' => [2025, 9, '2025-09-30'],
    ];
  }

  // ========== Month Navigation ==========

  #[Test]
  public function testGetPreviousMonthDateReturnsFirstDayOfPreviousMonth(): void
  {
    $calendar = new Calendar(2025, 3);
    $prevDate = $calendar->getPreviousMonthDate();
    $this->assertEquals('2025-02-01', $prevDate);
  }

  #[Test]
  public function testGetPreviousMonthDateHandlesJanuaryBoundary(): void
  {
    $calendar = new Calendar(2025, 1);
    $prevDate = $calendar->getPreviousMonthDate();
    $this->assertEquals('2024-12-01', $prevDate);
  }

  #[Test]
  public function testGetNextMonthDateReturnsFirstDayOfNextMonth(): void
  {
    $calendar = new Calendar(2025, 3);
    $nextDate = $calendar->getNextMonthDate();
    $this->assertEquals('2025-04-01', $nextDate);
  }

  #[Test]
  public function testGetNextMonthDateHandlesDecemberBoundary(): void
  {
    $calendar = new Calendar(2025, 12);
    $nextDate = $calendar->getNextMonthDate();
    $this->assertEquals('2026-01-01', $nextDate);
  }

  // ========== Navigation Methods ==========

  #[Test]
  #[DataProvider('navigationDirectionProvider')]
  public function testGetNavigationReturnsDateStringForDirection(string $direction): void
  {
    $calendar = new Calendar(2025, 3);
    $nav = $calendar->getNavigation($direction);

    $this->assertIsString($nav);
    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $nav);
  }

  public static function navigationDirectionProvider(): array
  {
    return [
        'prev direction' => ['prev'],
        'next direction' => ['next'],
    ];
  }

  #[Test]
  public function testGetNavigationPrevReturnsFirstOfPreviousMonth(): void
  {
    $calendar = new Calendar(2025, 3);
    $nav = $calendar->getNavigation('prev');
    $this->assertEquals('2025-02-01', $nav);
  }

  #[Test]
  public function testGetNavigationNextReturnsFirstOfNextMonth(): void
  {
    $calendar = new Calendar(2025, 3);
    $nav = $calendar->getNavigation('next');
    $this->assertEquals('2025-04-01', $nav);
  }

  #[Test]
  public function testGetNavigationDefaultReturnsCurrent(): void
  {
    $calendar = new Calendar(2025, 3);
    $nav = $calendar->getNavigation('invalid');
    $this->assertEquals('2025-03-01', $nav);
  }

  // ========== Day ID Generation ==========

  #[Test]
  public function testGetDayIDsReturnsGenerator(): void
  {
    $calendar = new Calendar(2025, 1);
    $dayIds = $calendar->getDayIDs();
    $this->assertInstanceOf(Generator::class, $dayIds);
  }

  #[Test]
  public function testGetDayIDsYields42Days(): void
  {
    $calendar = new Calendar(2025, 1);
    $dayIds = $calendar->getDayIDs();

    $count = 0;
    foreach ($dayIds as $id) {
      ++$count;
    }

    $this->assertEquals(42, $count);
  }

  #[Test]
  public function testGetDayIDsContainsValidDateStrings(): void
  {
    $calendar = new Calendar(2025, 1);
    $dayIds = $calendar->getDayIDs();

    foreach ($dayIds as $id) {
      $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $id);
      $date = DateTime::createFromFormat('Y-m-d', $id);
      $this->assertNotFalse($date);

      break;
    }
  }

  #[Test]
  public function testGetDayIDsIncludesLeadingDaysFromPreviousMonth(): void
  {
    $calendar = new Calendar(2025, 1);
    $dayIds = [];
    foreach ($calendar->getDayIDs() as $id) {
      $dayIds[] = $id;
    }

    $firstMonth = substr($dayIds[0], 0, 7);
    $this->assertEquals('2024-12', $firstMonth);
  }

  #[Test]
  public function testGetDayIDsIncludesTrailingDaysFromNextMonth(): void
  {
    $calendar = new Calendar(2025, 1);
    $dayIds = [];
    foreach ($calendar->getDayIDs() as $id) {
      $dayIds[] = $id;
    }

    $lastMonth = substr($dayIds[count($dayIds) - 1], 0, 7);
    $this->assertEquals('2025-02', $lastMonth);
  }

  // ========== Edge Cases ==========

  #[Test]
  public function testCalendarForLeapYearFebruary(): void
  {
    $calendar = new Calendar(2024, 2);
    $lastDay = $calendar->getLastDay();
    $this->assertEquals('2024-02-29', $lastDay->format('Y-m-d'));
  }

  #[Test]
  public function testCalendarForNonLeapYearFebruary(): void
  {
    $calendar = new Calendar(2025, 2);
    $lastDay = $calendar->getLastDay();
    $this->assertEquals('2025-02-28', $lastDay->format('Y-m-d'));
  }

  #[Test]
  public function testCalendarForYearBoundary(): void
  {
    $calendar = new Calendar(2024, 12);
    $nextMonth = $calendar->getNextMonthDate();
    $this->assertEquals('2025-01-01', $nextMonth);
  }

  #[Test]
  public function testMultipleCalendarInstancesAreIndependent(): void
  {
    $cal1 = new Calendar(2025, 1);
    $cal2 = new Calendar(2025, 6);

    $this->assertEquals('2025-01-01', $cal1->getFirstDay()->format('Y-m-d'));
    $this->assertEquals('2025-06-01', $cal2->getFirstDay()->format('Y-m-d'));
  }

  // ========== Type Checking ==========

  #[Test]
  public function testGetFirstDayReturnTypeIsDateTime(): void
  {
    $calendar = new Calendar(2025, 1);
    $result = $calendar->getFirstDay();
    $this->assertInstanceOf(DateTime::class, $result);
  }

  #[Test]
  public function testGetLastDayReturnTypeIsDateTime(): void
  {
    $calendar = new Calendar(2025, 1);
    $result = $calendar->getLastDay();
    $this->assertInstanceOf(DateTime::class, $result);
  }

  #[Test]
  public function testGetPreviousMonthDateReturnTypeIsString(): void
  {
    $calendar = new Calendar(2025, 1);
    $result = $calendar->getPreviousMonthDate();
    $this->assertIsString($result);
  }

  #[Test]
  public function testGetNextMonthDateReturnTypeIsString(): void
  {
    $calendar = new Calendar(2025, 1);
    $result = $calendar->getNextMonthDate();
    $this->assertIsString($result);
  }

  #[Test]
  public function testGetNavigationReturnTypeIsString(): void
  {
    $calendar = new Calendar(2025, 1);
    $result = $calendar->getNavigation('prev');
    $this->assertIsString($result);
  }

  #[Test]
  public function testGetDayIDsReturnTypeIsGenerator(): void
  {
    $calendar = new Calendar(2025, 1);
    $result = $calendar->getDayIDs();
    $this->assertInstanceOf(Generator::class, $result);
  }

  // ========== Boundary Tests ==========

  #[Test]
  public function testCalendarForMinimumYear(): void
  {
    $calendar = new Calendar(1, 1);
    $this->assertInstanceOf(Calendar::class, $calendar);
    $this->assertEquals('0001-01-01', $calendar->getFirstDay()->format('Y-m-d'));
  }

  #[Test]
  public function testCalendarForMaximumYear(): void
  {
    $calendar = new Calendar(9999, 12);
    $this->assertInstanceOf(Calendar::class, $calendar);
    $this->assertEquals('9999-12-01', $calendar->getFirstDay()->format('Y-m-d'));
  }

  #[Test]
  public function testCalendarForMinimumMonth(): void
  {
    $calendar = new Calendar(2025, 1);
    $this->assertInstanceOf(Calendar::class, $calendar);
  }

  #[Test]
  public function testCalendarForMaximumMonth(): void
  {
    $calendar = new Calendar(2025, 12);
    $this->assertInstanceOf(Calendar::class, $calendar);
  }
}
