<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\UserPreferenceDefaults;

#[Group('unit')]
final class UserPreferenceDefaultsCalendarTest extends TestCase
{
  #[Test]
  public function calendarWeekStartDayDefaultsToSunday(): void
  {
    $this->assertSame(0, UserPreferenceDefaults::calendarWeekStartDay(null));
  }

  #[Test]
  public function calendarWeekStartDayHonorsMondayPreference(): void
  {
    $user = new \PayCal\Domain\User();
    $user->calendar_week_start = '1';

    $this->assertSame(1, UserPreferenceDefaults::calendarWeekStartDay($user));
  }
}
