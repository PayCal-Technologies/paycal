<?php declare(strict_types=1);

use PayCal\Domain\DataGrid;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class CalendarGridAriaContractTest extends TestCase
{
  private function renderSampleMonthGrid(): string
  {
    $rows = [];
    for ($day = 8; $day <= 14; $day++) {
      $rows[] = [
        'id' => sprintf('2026-06-%02d', $day),
        'adjacent' => $day < 10,
        'work_entries' => [],
      ];
    }

    $grid = new DataGrid([
      'id' => 'calendar-grid-aria-contract',
      'columns' => [],
      'rows' => $rows,
      'meta' => [
        'layout' => 'month',
        'year' => 2026,
        'month' => 6,
        'searchEnabled' => false,
        'suppressMonthNavigation' => true,
        'lockBoundary' => '2026-06-10',
        'descriptionId' => 'calendar-grid-instructions',
      ],
    ]);

    return $grid->table();
  }

  /**
   * @return list<DOMElement>
   */
  private function monthCellsFromHtml(string $html): array
  {
    $document = new DOMDocument();
    $this->assertTrue(@$document->loadHTML('<?xml encoding="utf-8" ?>' . $html));

    $xpath = new DOMXPath($document);
    $nodes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' datagrid_month_cell ')]");
    $this->assertInstanceOf(DOMNodeList::class, $nodes);

    $cells = [];
    foreach ($nodes as $node) {
      if ($node instanceof DOMElement) {
        $cells[] = $node;
      }
    }

    return $cells;
  }

  #[Test]
  public function monthGridUsesGridRowAndGridcellRoles(): void
  {
    $html = $this->renderSampleMonthGrid();

    $this->assertMatchesRegularExpression(
      '/class="datagrid_month_grid"[^>]*role="grid"[^>]*aria-colcount="7"/s',
      $html,
    );
    $this->assertStringContainsString('class="datagrid_month_row" role="row"', $html);
    $this->assertStringContainsString('class="datagrid_month_cell" role="gridcell"', $html);
  }

  #[Test]
  public function monthCellsWithAriaStateAttributesDeclareGridcellRole(): void
  {
    $cells = $this->monthCellsFromHtml($this->renderSampleMonthGrid());
    $this->assertNotEmpty($cells);

    foreach ($cells as $cell) {
      $this->assertSame(
        'gridcell',
        $cell->getAttribute('role'),
        'Month grid cells must declare role="gridcell" so aria-selected and aria-disabled are valid.',
      );
    }

    $lockedCells = array_values(array_filter(
      $cells,
      static fn (DOMElement $cell): bool => str_contains($cell->getAttribute('class'), 'datagrid_month_cell_locked'),
    ));
    $this->assertNotEmpty($lockedCells, 'Fixture should include locked cells before lockBoundary.');

    foreach ($lockedCells as $cell) {
      $this->assertSame('true', $cell->getAttribute('aria-disabled'));
      $this->assertSame('gridcell', $cell->getAttribute('role'));
    }

    $adjacentCells = array_values(array_filter(
      $cells,
      static fn (DOMElement $cell): bool => str_contains($cell->getAttribute('class'), 'datagrid_month_cell_adjacent'),
    ));
    $this->assertNotEmpty($adjacentCells, 'Fixture should include adjacent-month cells.');

    foreach ($adjacentCells as $cell) {
      $this->assertSame('gridcell', $cell->getAttribute('role'));
    }
  }

  #[Test]
  public function monthCellsExposeOneBasedRowAndColumnIndices(): void
  {
    $cells = $this->monthCellsFromHtml($this->renderSampleMonthGrid());
    $this->assertCount(7, $cells);

    foreach ($cells as $index => $cell) {
      $this->assertSame((string) ($index + 1), $cell->getAttribute('aria-colindex'));
      $this->assertSame('1', $cell->getAttribute('aria-rowindex'));
    }
  }

  #[Test]
  public function calendarGridFocusScriptSetsAriaSelectedOnMonthCells(): void
  {
    $calendarJs = (string) file_get_contents(dirname(__DIR__, 3) . '/js/calendar/calendar.js');

    $this->assertStringContainsString("cell.setAttribute('aria-selected', isSelected ? 'true' : 'false')", $calendarJs);
    $this->assertStringContainsString("cell.setAttribute('aria-selected', index === 0 ? 'true' : 'false')", $calendarJs);
    $this->assertStringContainsString("grid.querySelectorAll('.datagrid_month_cell')", $calendarJs);
  }
}
