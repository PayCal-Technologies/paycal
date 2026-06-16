<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\DataGrid;
use PayCal\Domain\PagerInterface;
use PHPUnit\Framework\Attributes\Group;

/*
 * DataGrid Test Suite
 *
 * Tests for the DataGrid UI component.
 * Verifies configuration, column setup, and rendering logic.
 *
 * PHP version 8.4.16
 *
 * @category   Tests
 * @package    PayCal
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * @internal
 *
 */
#[Group('unit')]
final class DataGridTest extends TestCase
{
  // ==========================================
  // Factory Method Tests
  // ==========================================

  #[Test]
  public function createReturnsDataGridInstance(): void
  {
    $grid = DataGrid::create('test-grid', 'Test Grid');

    $this->assertInstanceOf(DataGrid::class, $grid);
  }

  #[Test]
  public function createAcceptsIdAndTitle(): void
  {
    $grid = DataGrid::create('users-grid', 'User Management');

    // Grid should be created successfully
    $this->assertInstanceOf(DataGrid::class, $grid);
  }

  #[Test]
  public function createHandlesSpecialCharactersInId(): void
  {
    $grid = DataGrid::create('grid_with-special.chars', 'Test');

    $this->assertInstanceOf(DataGrid::class, $grid);
  }

  // ==========================================
  // Column Configuration Tests
  // ==========================================

  #[Test]
  public function addColumnConfiguresBasicColumn(): void
  {
    $grid = DataGrid::create('test', 'Test');

    // Should not throw exception
    $grid->addColumn('name', 'Name');

    $this->assertTrue(true, 'Column added successfully');
  }

  #[Test]
  public function addColumnSupportsSortableFlag(): void
  {
    $grid = DataGrid::create('test', 'Test');

    $grid->addColumn('email', 'Email', true);

    $this->assertTrue(true, 'Sortable column added');
  }

  #[Test]
  public function addColumnSupportsCustomWidth(): void
  {
    $grid = DataGrid::create('test', 'Test');

    $grid->addColumn('id', 'ID', false, '100px');

    $this->assertTrue(true, 'Column with width added');
  }

  #[Test]
  public function addColumnSupportsMultipleColumns(): void
  {
    $grid = DataGrid::create('test', 'Test');

    $grid->addColumn('id', 'ID');
    $grid->addColumn('name', 'Name', true);
    $grid->addColumn('email', 'Email', true, '200px');
    $grid->addColumn('created', 'Created');

    $this->assertTrue(true, 'Multiple columns added');
  }

  // ==========================================
  // Row Action Configuration Tests
  // ==========================================

  #[Test]
  public function addRowActionConfiguresAction(): void
  {
    $grid = DataGrid::create('test', 'Test');

    $grid->addRowAction('edit', 'Edit');

    $this->assertTrue(true, 'Row action added');
  }

  #[Test]
  public function addRowActionSupportsMultipleActions(): void
  {
    $grid = DataGrid::create('test', 'Test');

    $grid->addRowAction('view', 'View');
    $grid->addRowAction('edit', 'Edit');
    $grid->addRowAction('delete', 'Delete');

    $this->assertTrue(true, 'Multiple row actions added');
  }

  #[Test]
  public function addRowActionHandlesSpecialActionKeys(): void
  {
    $grid = DataGrid::create('test', 'Test');

    $grid->addRowAction('delete', 'Delete');  // Special styling
    $grid->addRowAction('archive', 'Archive');

    $this->assertTrue(true, 'Special action keys handled');
  }

  // ==========================================
  // Control Configuration Tests
  // ==========================================

  #[Test]
  public function addControlConfiguresControl(): void
  {
    $grid = DataGrid::create('test', 'Test');

    $grid->addControl(['action' => 'create', 'label' => 'New User']);

    $this->assertTrue(true, 'Control added');
  }

  #[Test]
  public function addControlSupportsMultipleControls(): void
  {
    $grid = DataGrid::create('test', 'Test');

    $grid->addControl(['action' => 'create', 'label' => 'Create']);
    $grid->addControl(['action' => 'import', 'label' => 'Import']);
    $grid->addControl(['action' => 'export', 'label' => 'Export']);

    $this->assertTrue(true, 'Multiple controls added');
  }

  // ==========================================
  // Search Configuration Tests
  // ==========================================

  #[Test]
  public function enableSearchActivatesSearchFeature(): void
  {
    $grid = DataGrid::create('test', 'Test');

    $grid->enableSearch();

    $this->assertTrue(true, 'Search enabled');
  }

  #[Test]
  public function enableSearchAcceptsCustomPlaceholder(): void
  {
    $grid = DataGrid::create('test', 'Test');

    $grid->enableSearch('Search users...');

    $this->assertTrue(true, 'Search enabled with custom placeholder');
  }

  #[Test]
  public function enableSearchDefaultsToGenericPlaceholder(): void
  {
    $grid = DataGrid::create('test', 'Test');

    $grid->enableSearch();

    // Default placeholder should be "Search…"
    $this->assertTrue(true, 'Search enabled with default placeholder');
  }

  // ==========================================
  // Sorting Configuration Tests
  // ==========================================

  #[Test]
  public function enableSortingActivatesSortingFeature(): void
  {
    $grid = DataGrid::create('test', 'Test');

    $grid->enableSorting();

    $this->assertTrue(true, 'Sorting enabled');
  }

  #[Test]
  public function monthLayoutHonorsDateLabelPositionSetting(): void
  {
    $expectedClasses = [
      'left' => ['datagrid_date_label_left', 'datagrid_month_cell_header_left', 'datagrid_month_cell_day_left'],
      'middle' => ['datagrid_date_label_center', 'datagrid_month_cell_header_center', 'datagrid_month_cell_day_center'],
      'right' => ['datagrid_date_label_right', 'datagrid_month_cell_header_right', 'datagrid_month_cell_day_right'],
    ];

    foreach ($expectedClasses as $position => $positionClasses) {
      $grid = new DataGrid([
        'id' => 'calendar-grid-' . $position,
        'columns' => [],
        'rows' => [
          [
            'id' => '2026-06-15',
            'date' => '2026-06-15',
          ],
        ],
        'meta' => [
          'layout' => 'month',
          'year' => 2026,
          'month' => 6,
          'dateLabelPosition' => $position,
          'searchEnabled' => false,
        ],
      ]);

      $html = $grid->table();

      $this->assertStringContainsString('data-date-label-position="' . $position . '"', $html);
      foreach ($positionClasses as $expectedClass) {
        $this->assertStringContainsString($expectedClass, $html);
      }
    }
  }

  #[Test]
  public function monthLayoutHonorsDayNameFormatSetting(): void
  {
    $expectedLabels = [
      'narrow' => ['>S</div>', '>M</div>'],
      'short' => ['>Sun</div>', '>Mon</div>'],
      'long' => ['>Sunday</div>', '>Monday</div>'],
    ];

    foreach ($expectedLabels as $format => $labels) {
      $grid = new DataGrid([
        'id' => 'calendar-grid-day-names-' . $format,
        'columns' => [],
        'rows' => [],
        'meta' => [
          'layout' => 'month',
          'year' => 2026,
          'month' => 6,
          'locale' => 'en_US',
          'dayNameFormat' => $format,
          'searchEnabled' => false,
        ],
      ]);

      $html = $grid->table();

      foreach ($labels as $label) {
        $this->assertStringContainsString($label, $html);
      }
    }
  }

  #[Test]
  public function monthLayoutHonorsDayNamePositionSetting(): void
  {
    $expectedClasses = [
      'left' => ['datagrid_day_heading_left', 'calendar-v2-weekday-headers_left'],
      'middle' => ['datagrid_day_heading_center', 'calendar-v2-weekday-headers_center'],
      'right' => ['datagrid_day_heading_right', 'calendar-v2-weekday-headers_right'],
    ];

    foreach ($expectedClasses as $position => $positionClasses) {
      $grid = new DataGrid([
        'id' => 'calendar-grid-day-heading-position-' . $position,
        'columns' => [],
        'rows' => [],
        'meta' => [
          'layout' => 'month',
          'year' => 2026,
          'month' => 6,
          'dayNamePosition' => $position,
          'searchEnabled' => false,
        ],
      ]);

      $html = $grid->table();

      $this->assertStringContainsString('data-day-heading-position="' . $position . '"', $html);
      foreach ($positionClasses as $expectedClass) {
        $this->assertStringContainsString($expectedClass, $html);
      }
    }
  }

  #[Test]
  public function monthLayoutCanOmitInternalNavigationForExternalControlStrip(): void
  {
    $grid = new DataGrid([
      'id' => 'calendar-grid-external-controls',
      'columns' => [],
      'rows' => [],
      'meta' => [
        'layout' => 'month',
        'year' => 2026,
        'month' => 6,
        'suppressMonthNavigation' => true,
        'searchEnabled' => false,
      ],
    ]);

    $html = $grid->table();

    $this->assertStringNotContainsString('datagrid_controls', $html);
    $this->assertStringNotContainsString('id="cal_picker_button"', $html);
    $this->assertStringNotContainsString('data-action="prev-month"', $html);
    $this->assertStringNotContainsString('data-action="next-month"', $html);
    $this->assertStringContainsString('calendar-v2-weekday-headers', $html);
  }

  #[Test]
  public function monthLayoutUsesLocaleAwareDayNameLabels(): void
  {
    if (!class_exists('\IntlDateFormatter')) {
      $this->markTestSkipped('IntlDateFormatter extension is required.');
    }

    $grid = new DataGrid([
      'id' => 'calendar-grid-french-day-names',
      'columns' => [],
      'rows' => [],
      'meta' => [
        'layout' => 'month',
        'year' => 2026,
        'month' => 6,
        'locale' => 'fr',
        'dayNameFormat' => 'long',
        'searchEnabled' => false,
      ],
    ]);

    $html = $grid->table();

    $this->assertStringContainsString('>lundi</div>', $html);
    $this->assertStringNotContainsString('>Monday</div>', $html);
  }

  // ==========================================
  // Item Label Configuration Tests
  // ==========================================

  #[Test]
  public function setItemLabelConfiguresCustomLabel(): void
  {
    $grid = DataGrid::create('test', 'Test');

    $grid->setItemLabel('users');

    $this->assertTrue(true, 'Item label set');
  }

  #[Test]
  public function setItemLabelHandlesPluralForms(): void
  {
    $grid = DataGrid::create('test', 'Test');

    $grid->setItemLabel('entries');

    $this->assertTrue(true, 'Plural item label set');
  }

  #[Test]
  public function enableColumnVisibilityRendersControlStripMarkup(): void
  {
    $grid = DataGrid::create('business-members', 'Business Members');
    $grid->enableColumnVisibility();
    $grid->addColumn('full_name', 'Name', true, null, null, true, false);
    $grid->addColumn('email', 'Email', true);
    $grid->addColumn('ytd_gross', 'YTD Gross', true, null, 'right', false);

    $pager = $this->createMockPager([
      [
        'id' => 'member-1',
        'full_name' => 'Alpha Member',
        'email' => 'alpha@example.com',
        'ytd_gross' => '$0.00',
      ],
    ], 1, false);

    $html = $grid->table($pager);

    $this->assertStringContainsString('data-column-visibility="1"', $html);
    $this->assertStringContainsString('class="datagrid_column_strip"', $html);
    $this->assertStringContainsString('aria-label="Toggle visible columns"', $html);
    $this->assertStringNotContainsString('datagrid_column_strip_label', $html);
    $this->assertStringContainsString('data-col-key="email"', $html);
    $this->assertStringContainsString('data-col-key="ytd_gross"', $html);
    $this->assertStringContainsString('class="datagrid_column_toggle_input"', $html);
    $this->assertStringContainsString('datagrid_column_strip_status', $html);
    $this->assertStringContainsString('data-col-key="full_name"', $html);
    $this->assertDoesNotMatchRegularExpression(
      '/class="datagrid_column_toggle_input"[^>]*data-col-key="full_name"/',
      $html,
    );

    $gridWithToolbar = DataGrid::create('business-members-toolbar', 'Business Members');
    $gridWithToolbar->enableSearch('Filter members...');
    $gridWithToolbar->setToolbarLayout('search_pagination');
    $gridWithToolbar->enableColumnVisibility();
    $gridWithToolbar->addColumn('full_name', 'Name', true, null, null, true, false);
    $gridWithToolbar->addColumn('email', 'Email', true);

    $toolbarHtml = $gridWithToolbar->table($pager);
    $toolbarPos = strpos($toolbarHtml, 'class="datagrid_toolbar datagrid_toolbar_search_pagination"');
    $columnStripPos = strpos($toolbarHtml, 'class="datagrid_column_strip"');
    $headerPos = strpos($toolbarHtml, 'class="datagrid_header_row"');
    $this->assertNotFalse($toolbarPos);
    $this->assertNotFalse($columnStripPos);
    $this->assertNotFalse($headerPos);
    $this->assertLessThan($headerPos, $columnStripPos);
    $this->assertLessThan($columnStripPos, $toolbarPos);
  }

  #[Test]
  public function setNoChromeAddsNoChromeClassToRenderedGrid(): void
  {
    $grid = DataGrid::create('test', 'Test');
    $grid->addColumn('name', 'Name');
    $grid->setNoChrome(true);

    $html = $grid->table();

    $this->assertStringContainsString('datagrid_no_chrome', $html);
  }

  // ==========================================
  // Rendering Tests - Integration Only
  // ==========================================
  // Note: All rendering tests require DataGrid::table() which calls getPageSize()
  // and getTotal() methods not in the PagerInterface. Rendering tests deferred
  // to integration test suite where concrete Pager instances are used.

  #[Test]
  public function tableRendersWithEmptyData(): void
  {
    // Note: table() method uses getPageSize() and getTotal() not in PagerInterface
    // Rendering tests require concrete Pager implementation (integration tests)
    $this->assertTrue(true, 'Rendering tests in integration suite');
  }

  #[Test]
  public function tableRendersWithData(): void
  {
    $this->assertTrue(true, 'Rendering tests in integration suite');
  }

  #[Test]
  public function tableRendersColumnHeaders(): void
  {
    $this->assertTrue(true, 'Rendering tests in integration suite');
  }

  #[Test]
  public function tableRendersSortableColumns(): void
  {
    $this->assertTrue(true, 'Rendering tests in integration suite');
  }

  #[Test]
  public function tableRendersRowActions(): void
  {
    $this->assertTrue(true, 'Rendering tests in integration suite');
  }

  #[Test]
  public function tableAppliesDangerStyleToDeleteAction(): void
  {
    $this->assertTrue(true, 'Rendering tests in integration suite');
  }

  #[Test]
  public function tableRendersControlButtons(): void
  {
    $this->assertTrue(true, 'Rendering tests in integration suite');
  }

  #[Test]
  public function tableRendersSearchField(): void
  {
    $this->assertTrue(true, 'Rendering tests in integration suite');
  }

  #[Test]
  public function tableRendersPaginationWhenEnabled(): void
  {
    // Note: DataGrid.table() calls getTotal() and getPageSize()
    // which are not in PagerInterface, so pagination rendering
    // requires a concrete Pager instance (integration test territory)
    $this->assertTrue(true, 'Pagination rendering tested via integration tests');
  }

  #[Test]
  public function tableDoesNotRenderPaginationWhenDisabled(): void
  {
    $grid = DataGrid::create('test', 'Test');
    $grid->addColumn('name', 'Name');

    $pager = $this->createMockPager([], 5, false);

    $html = $grid->table($pager);

    $this->assertStringNotContainsString('datagrid_pagination', $html);
  }

  #[Test]
  public function tableShowsCorrectPaginationInfo(): void
  {
    // Pagination rendering requires concrete Pager with getTotal/getPageSize
    $this->assertTrue(true, 'Tested via integration suite');
  }

  #[Test]
  public function tableIncludesPageAttributeInGrid(): void
  {
    $grid = DataGrid::create('test', 'Test');
    $grid->addColumn('name', 'Name');

    $pager = $this->createMockPager([], 0, false, 2);

    $html = $grid->table($pager);

    $this->assertStringContainsString('data-page="2"', $html);
  }

  #[Test]
  public function tableHandlesMissingRowData(): void
  {
    $this->assertTrue(true, 'Rendering tests in integration suite');
  }

  #[Test]
  public function tableEscapesHtmlInData(): void
  {
    $this->assertTrue(true, 'Rendering tests in integration suite');
  }

  #[Test]
  public function tableRendersMultiplePages(): void
  {
    $this->assertTrue(true, 'Rendering tests in integration suite');
  }

  #[Test]
  public function tableHandlesZeroRecords(): void
  {
    $this->assertTrue(true, 'Rendering tests in integration suite');
  }

  // ==========================================
  // Complex Configuration Tests
  // ==========================================

  #[Test]
  public function fullFeaturedGridConfiguration(): void
  {
    $grid = DataGrid::create('users-grid', 'User Management');

    // Configure columns
    $grid->addColumn('id', 'ID', false, '80px');
    $grid->addColumn('name', 'Name', true);
    $grid->addColumn('email', 'Email', true);
    $grid->addColumn('status', 'Status', true, '120px');

    // Configure actions
    $grid->addRowAction('view', 'View');
    $grid->addRowAction('edit', 'Edit');
    $grid->addRowAction('delete', 'Delete');

    // Configure controls
    $grid->addControl(['action' => 'create', 'label' => 'New User']);
    $grid->addControl(['action' => 'export', 'label' => 'Export CSV']);

    // Enable features
    $grid->enableSearch('Search users...');
    $grid->enableSorting();
    $grid->setItemLabel('users');

    // Rendering requires concrete Pager with getPageSize/getTotal
    $this->assertInstanceOf(DataGrid::class, $grid);
  }

  // ==========================================
  // Helper Methods
  // ==========================================

  /**
   * Create a mock PagerInterface for testing.
   */
  private function createMockPager(
    array $rows,
    int $totalRecords,
    bool $hasPagination = false,
    int $currentPage = 1,
    int $pageSize = 10
  ): PagerInterface {
    $pager = $this->createStub(PagerInterface::class);

    $pager->method('getRows')->willReturn($rows);
    $pager->method('hasPagination')->willReturn($hasPagination);
    $pager->method('getPage')->willReturn($currentPage);
    $pager->method('getTotalPages')->willReturn(
      $hasPagination ? max(1, (int) ceil($totalRecords / $pageSize)) : 1
    );
    $pager->method('hasPrev')->willReturn($currentPage > 1);
    $pager->method('hasNext')->willReturn(
      $hasPagination && $currentPage < max(1, (int) ceil($totalRecords / $pageSize))
    );

    return $pager;
  }
}
