<?php declare(strict_types=1);

use PayCal\Domain\User;
use PayCal\Domain\CalendarCellDisplay;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class CalendarCellDisplayTest extends TestCase
{
  #[Test]
  public function payPeriodClassesEmptyWhenHighlightDisabled(): void
  {
    $user = new User();
    $user->calendar_highlight_pay_period = false;

    $this->assertSame('', CalendarCellDisplay::payPeriodClasses($user, '2026-06-15'));
  }

  #[Test]
  public function payPeriodClassesEmptyForInvalidDate(): void
  {
    $user = new User();
    $user->calendar_highlight_pay_period = true;

    $this->assertSame('', CalendarCellDisplay::payPeriodClasses($user, 'not-a-date'));
  }

  #[Test]
  public function buildWorkEntryDisplayFieldsOmitsDisabledTravelAndLoa(): void
  {
    $user = new User();
    $user->calendar_work_entry_fields_regular = true;
    $user->calendar_work_entry_fields_overtime = true;
    $user->calendar_work_entry_fields_living_out = false;
    $user->calendar_work_entry_fields_travel = false;

    $display = CalendarCellDisplay::buildWorkEntryDisplayFields(
      8.0,
      2.0,
      50.0,
      1.5,
      CalendarCellDisplay::workEntryFieldPrefs($user),
    );

    $this->assertSame(['10', '8', '2'], $display['fields']);
    $this->assertSame(['10 total hours', '8 regular hours', '2 overtime hours'], $display['spokenMetrics']);
  }

  #[Test]
  public function buildWorkEntryDisplayFieldsReturnsEmptyWhenAllBadgesDisabled(): void
  {
    $user = new User();
    $user->calendar_work_entry_fields_hours = false;
    $user->calendar_work_entry_fields_regular = false;
    $user->calendar_work_entry_fields_overtime = false;
    $user->calendar_work_entry_fields_living_out = false;
    $user->calendar_work_entry_fields_travel = false;

    $display = CalendarCellDisplay::buildWorkEntryDisplayFields(
      8.0,
      2.0,
      50.0,
      1.5,
      CalendarCellDisplay::workEntryFieldPrefs($user),
    );

    $this->assertSame([], $display['fields']);
    $this->assertSame([], $display['spokenMetrics']);
  }

  #[Test]
  public function buildWorkEntryDisplayFieldsIncludesEnabledTravelAndLoa(): void
  {
    $user = new User();
    $user->calendar_work_entry_fields_regular = false;
    $user->calendar_work_entry_fields_overtime = false;
    $user->calendar_work_entry_fields_living_out = true;
    $user->calendar_work_entry_fields_travel = true;

    $display = CalendarCellDisplay::buildWorkEntryDisplayFields(
      8.0,
      2.0,
      0.0,
      0.0,
      CalendarCellDisplay::workEntryFieldPrefs($user),
    );

    $this->assertSame(['10', '0', '0'], $display['fields']);
    $this->assertSame(['10 total hours', '0 living out allowance', '0 travel hours'], $display['spokenMetrics']);
  }

  #[Test]
  public function workEntryFieldPrefsFromMetaDefaultsAllEnabled(): void
  {
    $prefs = CalendarCellDisplay::workEntryFieldPrefsFromMeta(null);

    $this->assertTrue($prefs['hours']);
    $this->assertTrue($prefs['regular']);
    $this->assertTrue($prefs['overtime']);
    $this->assertTrue($prefs['living_out']);
    $this->assertTrue($prefs['travel']);
  }

  #[Test]
  public function calendarPageExportsWorkEntryFieldDataAttributes(): void
  {
    $indexPhp = (string) file_get_contents(dirname(__DIR__, 3) . '/html/index.php');

    $this->assertStringContainsString('$currentUser = User::current();', $indexPhp);
    $this->assertStringContainsString('data-work-entry-hours', $indexPhp);
    $this->assertStringContainsString('data-work-entry-regular', $indexPhp);
    $this->assertStringContainsString('data-work-entry-overtime', $indexPhp);
    $this->assertStringContainsString('data-work-entry-living-out', $indexPhp);
    $this->assertStringContainsString('data-work-entry-travel', $indexPhp);
    $this->assertStringContainsString('data-show-deductions-badge', $indexPhp);
  }

  #[Test]
  public function calendarJsReadsWorkEntryPrefsFromRootDataset(): void
  {
    $calendarJs = (string) file_get_contents(dirname(__DIR__, 3) . '/html/js/calendar/calendar.js');

    $this->assertStringContainsString("root.dataset.workEntryHours === '1'", $calendarJs);
    $this->assertStringContainsString("root.dataset.workEntryRegular === '1'", $calendarJs);
    $this->assertStringContainsString("root.dataset.workEntryLivingOut === '1'", $calendarJs);
    $this->assertStringContainsString('updateCalendarHoursBadge', $calendarJs);
    $this->assertStringNotContainsString("badge.className = 'datagrid_month_value hours-badge'", $calendarJs);
    $this->assertStringContainsString('calendar_earnings_badge_deductions', $calendarJs);
    $this->assertStringContainsString("root?.dataset.showDeductionsBadge === '1'", $calendarJs);
  }
}
