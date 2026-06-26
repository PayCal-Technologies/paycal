<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class CalendarGridFocusContractTest extends TestCase
{
  #[Test]
  public function calendarDeadspaceClicksRestoreGridCellFocus(): void
  {
    $calendarJs = (string) file_get_contents(dirname(__DIR__, 3) . '/js/calendar/calendar.js');

    $this->assertStringContainsString('function handleCalendarDeadspacePointerDown(event)', $calendarJs);
    $this->assertStringContainsString('function restoreCalendarGridFocusAfterDeadspaceClick()', $calendarJs);
    $this->assertStringContainsString('function isCalendarInteractiveTarget(target)', $calendarJs);
    $this->assertStringContainsString('function isAnyCalendarDialogActive()', $calendarJs);
    $this->assertStringContainsString('function resolveCalendarGridFocusCell()', $calendarJs);
    $this->assertStringContainsString("document.addEventListener('mousedown', handleCalendarDeadspacePointerDown, true)", $calendarJs);

    $handlerStart = strpos($calendarJs, 'function handleCalendarDeadspacePointerDown(event)');
    $this->assertNotFalse($handlerStart);
    $handlerBody = substr($calendarJs, $handlerStart, 1200);

    $this->assertStringContainsString('isCalendarPageContext()', $handlerBody);
    $this->assertStringContainsString('isAnyCalendarDialogActive()', $handlerBody);
    $this->assertStringContainsString('calendarShiftKeyHeld', $handlerBody);
    $this->assertStringContainsString('event.shiftKey', $handlerBody);
    $this->assertStringContainsString('calendarAltKeyHeld', $handlerBody);
    $this->assertStringContainsString('event.altKey', $handlerBody);
    $this->assertStringContainsString("target.closest('.datagrid_month_cell')", $handlerBody);
    $this->assertStringContainsString('isCalendarInteractiveTarget(target)', $handlerBody);
    $this->assertStringContainsString('requestAnimationFrame', $handlerBody);
    $this->assertStringContainsString('restoreCalendarGridFocusAfterDeadspaceClick', $handlerBody);
    $this->assertStringContainsString('calendar_day_context_menu', $handlerBody);
    $this->assertStringContainsString("classList.contains('hidden')", $handlerBody);

    $restoreStart = strpos($calendarJs, 'function restoreCalendarGridFocusAfterDeadspaceClick()');
    $this->assertNotFalse($restoreStart);
    $restoreBody = substr($calendarJs, $restoreStart, 1200);

    $this->assertStringContainsString('document.activeElement', $restoreBody);
    $this->assertStringContainsString("active.closest('.datagrid_month_cell')", $restoreBody);
    $this->assertStringContainsString('isCalendarInteractiveTarget(active)', $restoreBody);
    $this->assertStringContainsString('setGridCellFocusState(cell, true', $restoreBody);
    $this->assertStringContainsString('resolveCalendarGridFocusCell()', $restoreBody);

    $resolveStart = strpos($calendarJs, 'function resolveCalendarGridFocusCell()');
    $this->assertNotFalse($resolveStart);
    $resolveBody = substr($calendarJs, $resolveStart, 600);
    $this->assertStringContainsString('window._CALENDAR_LAST_GRID_FOCUS_DATE', $resolveBody);
    $this->assertStringContainsString('tabindex="0"', $resolveBody);
  }

  #[Test]
  public function calendarDeadspaceHandlerSkipsInteractiveTargets(): void
  {
    $calendarJs = (string) file_get_contents(dirname(__DIR__, 3) . '/js/calendar/calendar.js');

    $interactiveStart = strpos($calendarJs, 'function isCalendarInteractiveTarget(target)');
    $this->assertNotFalse($interactiveStart);
    $interactiveBody = substr($calendarJs, $interactiveStart, 450);

    $this->assertStringContainsString('a[href]', $interactiveBody);
    $this->assertStringContainsString('button', $interactiveBody);
    $this->assertStringContainsString('input', $interactiveBody);
    $this->assertStringContainsString('select', $interactiveBody);
    $this->assertStringContainsString('textarea', $interactiveBody);
    $this->assertStringContainsString('[role="button"]', $interactiveBody);
    $this->assertStringContainsString('[role="menuitem"]', $interactiveBody);
    $this->assertStringContainsString('dialog', $interactiveBody);
  }

  #[Test]
  public function calendarGridCellFocusScrollsIntoViewWhenNotSuppressed(): void
  {
    $calendarJs = (string) file_get_contents(dirname(__DIR__, 3) . '/js/calendar/calendar.js');

    $this->assertStringContainsString('function scrollGridCellIntoView(cell)', $calendarJs);

    $scrollStart = strpos($calendarJs, 'function scrollGridCellIntoView(cell)');
    $this->assertNotFalse($scrollStart);
    $scrollBody = substr($calendarJs, $scrollStart, 450);

    $this->assertStringContainsString('scrollIntoView({ block: \'nearest\', inline: \'nearest\'', $scrollBody);
    $this->assertStringContainsString("behavior: 'auto'", $scrollBody);

    $focusStart = strpos($calendarJs, 'function setGridCellFocusState(targetCell');
    $this->assertNotFalse($focusStart);
    $focusBody = substr($calendarJs, $focusStart, 1200);

    $this->assertStringContainsString('preventScroll: true', $focusBody);
    $this->assertStringContainsString('scrollGridCellIntoView(targetCell)', $focusBody);
    $this->assertStringContainsString('focusOptions?.preventScroll === true', $focusBody);
  }

  #[Test]
  public function calendarDeadspaceHandlerRespectsModalState(): void
  {
    $calendarJs = (string) file_get_contents(dirname(__DIR__, 3) . '/js/calendar/calendar.js');

    $modalGuardStart = strpos($calendarJs, 'function isAnyCalendarDialogActive()');
    $this->assertNotFalse($modalGuardStart);
    $modalGuardBody = substr($calendarJs, $modalGuardStart, 450);

    $this->assertStringContainsString('isCalendarModalOpen()', $modalGuardBody);
    $this->assertStringContainsString("document.querySelector('dialog[open]')", $modalGuardBody);
    $this->assertStringContainsString('modal_is_active', $modalGuardBody);
  }
}
