<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class CalendarHoverTooltipContractTest extends TestCase
{
  #[Test]
  public function calendarDayHoverTooltipRequiresAltForMouseHover(): void
  {
    $calendarJs = (string) file_get_contents(dirname(__DIR__, 3) . '/js/calendar/calendar.js');

    $this->assertStringContainsString('calendarShiftKeyHeld', $calendarJs);
    $this->assertStringContainsString('isCalendarEarningsTooltipHoverEnabled', $calendarJs);
    $this->assertStringContainsString('return calendarAltKeyHeld', $calendarJs);
    $this->assertStringContainsString('maybeShowCalendarHoverTooltipForPointer', $calendarJs);
    $this->assertStringContainsString('calendarHoveredCell', $calendarJs);
    $this->assertStringContainsString('hideCalendarHoverTooltip();', $calendarJs);

    $altKeyupStart = strpos($calendarJs, "if (event.key !== 'Alt' || !calendarAltKeyHeld)");
    $this->assertNotFalse($altKeyupStart);
    $altKeyupBody = substr($calendarJs, $altKeyupStart, 250);
    $this->assertStringContainsString('hideCalendarHoverTooltip();', $altKeyupBody);
    $this->assertStringNotContainsString('if (calendarHoveredCell)', $altKeyupBody);

    $attachHandlersStart = strpos($calendarJs, 'function attachGridCellHandlers(grid)');
    $this->assertNotFalse($attachHandlersStart);
    $attachHandlersBody = substr($calendarJs, $attachHandlersStart, 200);
    $this->assertStringContainsString('hideCalendarHoverTooltip();', $attachHandlersBody);
  }

  #[Test]
  public function calendarDayHoverTooltipNeverShowsOnFocusWithoutAlt(): void
  {
    $calendarJs = (string) file_get_contents(dirname(__DIR__, 3) . '/js/calendar/calendar.js');

    $this->assertStringContainsString('handleCalendarCellTooltipFocus', $calendarJs);
    $this->assertStringContainsString('isCalendarTooltipSuppressed', $calendarJs);
    $this->assertStringNotContainsString(
      'Keyboard users: focused day cells still show earnings without Alt.',
      $calendarJs
    );
    $this->assertStringNotContainsString('allowWithoutAlt', $calendarJs);

    $showHandlerStart = strpos($calendarJs, 'function showCalendarHoverTooltip(cell, clientX, clientY)');
    $this->assertNotFalse($showHandlerStart);
    $showHandlerBody = substr($calendarJs, $showHandlerStart, 200);
    $this->assertStringContainsString('if (!calendarAltKeyHeld)', $showHandlerBody);

    $focusHandlerStart = strpos($calendarJs, 'function handleCalendarCellTooltipFocus(event)');
    $this->assertNotFalse($focusHandlerStart);
    $focusHandlerBody = substr($calendarJs, $focusHandlerStart, 500);
    $this->assertStringContainsString('if (!calendarAltKeyHeld)', $focusHandlerBody);
    $this->assertStringContainsString('if (calendarSuppressFocusTooltipFromModalClose || isCalendarTooltipSuppressed())', $focusHandlerBody);
  }

  #[Test]
  public function calendarClickAndModalCloseSuppressPointerTooltipFlash(): void
  {
    $calendarJs = (string) file_get_contents(dirname(__DIR__, 3) . '/js/calendar/calendar.js');

    $this->assertStringContainsString('calendarSuppressTooltipUntil', $calendarJs);
    $this->assertStringContainsString('suppressCalendarTooltip', $calendarJs);
    $this->assertStringContainsString('suppressCalendarTooltipAfterModalClose', $calendarJs);
    $this->assertStringContainsString('calendarSuppressFocusTooltipFromModalClose', $calendarJs);
    $this->assertStringContainsString('handleGridCellPointerDown', $calendarJs);
    $this->assertStringContainsString("modal.addEventListener('close', () => {", $calendarJs);
    $this->assertStringContainsString('{ capture: true }', $calendarJs);
    $this->assertStringContainsString('suppressCalendarTooltip(600);', $calendarJs);

    $clickHandlerStart = strpos($calendarJs, 'function handleGridCellClick(event)');
    $this->assertNotFalse($clickHandlerStart);
    $clickHandlerBody = substr($calendarJs, $clickHandlerStart, 800);
    $this->assertStringContainsString('suppressCalendarTooltip();', $clickHandlerBody);
    $this->assertStringNotContainsString('calendarSuppressFocusTooltip = false', $clickHandlerBody);

    $closeModalStart = strpos($calendarJs, 'function closeModal()');
    $this->assertNotFalse($closeModalStart);
    $closeModalBody = substr($calendarJs, $closeModalStart, 600);
    $this->assertStringContainsString('suppressCalendarTooltipAfterModalClose();', $closeModalBody);

    $focusHandlerStart = strpos($calendarJs, 'function handleCalendarCellTooltipFocus(event)');
    $this->assertNotFalse($focusHandlerStart);
    $focusHandlerBody = substr($calendarJs, $focusHandlerStart, 400);
    $this->assertStringContainsString('if (calendarSuppressFocusTooltipFromModalClose || isCalendarTooltipSuppressed()) {', $focusHandlerBody);
  }

  #[Test]
  public function lockedCalendarCellsRestorePointerEventsWhileAltTooltipHoverIsActive(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $calendarJs = (string) file_get_contents($htmlRoot . '/js/calendar/calendar.js');
    $calendarCss = (string) file_get_contents($htmlRoot . '/css/calendar/index.php');

    $this->assertStringContainsString('syncCalendarAltTooltipHoverClass', $calendarJs);
    $this->assertStringContainsString('calendar_alt_tooltip_hover', $calendarJs);
    $this->assertStringContainsString(
      '#calendar-v2-root.calendar_alt_tooltip_hover .datagrid_month_cell.datagrid_month_cell_locked',
      $calendarCss
    );
    $this->assertStringContainsString('pointer-events: auto', $calendarCss);
  }

  #[Test]
  public function calendarInitHidesTooltipWithoutUserAction(): void
  {
    $calendarJs = (string) file_get_contents(dirname(__DIR__, 3) . '/js/calendar/calendar.js');

    $this->assertStringContainsString('calendarBootFocusTooltipSuppressed', $calendarJs);
    $this->assertStringContainsString('resetCalendarHoverTooltipOnInit', $calendarJs);
    $this->assertStringContainsString('finalizeCalendarBootFocus', $calendarJs);

    $bootStart = strpos($calendarJs, 'async function boot()');
    $this->assertNotFalse($bootStart);
    $bootBody = substr($calendarJs, $bootStart, 8000);
    $this->assertStringContainsString('resetCalendarHoverTooltipOnInit();', $bootBody);
    $this->assertStringContainsString('finalizeCalendarBootFocus();', $bootBody);

    $focusHandlerStart = strpos($calendarJs, 'function handleCalendarCellTooltipFocus(event)');
    $this->assertNotFalse($focusHandlerStart);
    $focusHandlerBody = substr($calendarJs, $focusHandlerStart, 500);
    $this->assertStringContainsString('if (!calendarAltKeyHeld)', $focusHandlerBody);
    $this->assertStringContainsString('calendarBootFocusTooltipSuppressed', $focusHandlerBody);
    $this->assertStringContainsString('!event.isTrusted', $focusHandlerBody);

    $reinitStart = strpos($calendarJs, 'const reinitializeCalendarAfterPartialRefresh');
    $this->assertNotFalse($reinitStart);
    $reinitBody = substr($calendarJs, $reinitStart, 1600);
    $this->assertStringContainsString('resetCalendarHoverTooltipOnInit();', $reinitBody);
    $this->assertStringContainsString('finalizeCalendarBootFocus();', $reinitBody);
  }
}
