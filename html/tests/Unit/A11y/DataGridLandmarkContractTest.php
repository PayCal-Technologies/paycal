<?php declare(strict_types=1);

use PayCal\Domain\BusinessGroupsGridRenderer;
use PayCal\Domain\BusinessMembersGridRenderer;
use PayCal\Domain\BusinessSitesGridRenderer;
use PayCal\Domain\DataGrid;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
#[Group('redis-write')]
final class DataGridLandmarkContractTest extends TestCase
{
  #[Test]
  public function tableLayoutSearchStripDoesNotUseCalendarMonthNavigationLabel(): void
  {
    $grid = DataGrid::create('business-sites-active', 'Business Sites');
    $grid->enableSearch('Filter sites…');
    $grid->setControlsAriaLabel('Filter business sites');

    $html = $grid->table();

    $this->assertStringContainsString('class="datagrid_controls" role="search" aria-label="Filter business sites"', $html);
    $this->assertStringNotContainsString('Calendar month navigation', $html);
    $this->assertStringNotContainsString('role="navigation" aria-label="Calendar month navigation"', $html);
  }

  #[Test]
  public function monthLayoutControlsUseCalendarMonthNavigationLandmark(): void
  {
    $grid = new DataGrid([
      'id' => 'calendar-grid',
      'columns' => [],
      'rows' => [],
      'meta' => [
        'layout' => 'month',
        'year' => 2026,
        'month' => 6,
        'searchEnabled' => false,
      ],
    ]);

    $html = $grid->table();

    $this->assertMatchesRegularExpression(
      '/class="datagrid_controls"[^>]*role="navigation"[^>]*aria-label="Calendar month navigation"/s',
      $html,
    );
    $this->assertStringContainsString('data-action="prev-month"', $html);
    $this->assertStringContainsString('data-action="next-month"', $html);
  }

  #[Test]
  public function mergedSearchPaginationToolbarUsesToolbarLandmark(): void
  {
    $grid = DataGrid::create('business-members', 'Members');
    $grid->enableSearch('Filter members...');
    $grid->setControlsAriaLabel('Filter members');
    $grid->setToolbarLayout('search_pagination');

    $html = $grid->table();

    $this->assertMatchesRegularExpression(
      '/class="datagrid_toolbar datagrid_toolbar_search_pagination"[^>]*role="toolbar"[^>]*aria-label="Filter members"/s',
      $html,
    );
    $this->assertStringNotContainsString('Calendar month navigation', $html);
    $this->assertStringNotContainsString('class="datagrid_controls"', $html);
  }

  #[Test]
  public function paginationControlsUsePaginationLandmarkLabel(): void
  {
    $grid = DataGrid::create('audit-grid', 'Audit');
    $grid->setItemLabel('events');
    $rows = [];
    for ($index = 0; $index < 25; $index++) {
      $rows[] = ['id' => 'row-' . $index, 'event' => 'Event ' . $index];
    }
    $grid->addColumn('event', 'Event');

    $pager = \PayCal\Domain\ArrayPager::fromArray($rows, ['pageSize' => 10]);
    $html = $grid->table($pager);

    $this->assertMatchesRegularExpression('/role="navigation"[^>]*aria-label="Data grid pagination"/s', $html);
    $this->assertDoesNotMatchRegularExpression('/role="navigation"[^>]*aria-label="Data grid"/s', $html);
  }

  #[Test]
  public function businessSitesGridFilterUsesSearchLandmarkNotCalendarNavigation(): void
  {
    $renderer = new BusinessSitesGridRenderer();
    $html = $renderer->renderSites([
      [
        'site_owner_uuid' => 'owner-1',
        'site_id' => 'site-1',
        'site_name' => 'Alpha Yard',
        'settings' => ['budget_amount' => '1000', 'site_status' => 'active'],
        'site_color' => '#6AA6FF',
        'site_data' => [
          'site_name' => 'Alpha Yard',
          'site_color' => '#6AA6FF',
          'status' => 'active',
          'province' => 'AB',
          'wage' => '45.00',
        ],
      ],
    ], [
      'owner_uuid' => 'owner-1',
      'name' => 'Acme Builders',
    ], 'owner-1', 'business-123', [
      'status' => 'active',
    ]);

    $this->assertStringContainsString('role="search" aria-label="Filter business sites"', $html);
    $this->assertStringNotContainsString('Calendar month navigation', $html);
  }

  #[Test]
  public function businessGroupsGridFilterUsesSearchLandmarkNotCalendarNavigation(): void
  {
    $renderer = new BusinessGroupsGridRenderer();
    $html = $renderer->renderGroups('business-123', [
      [
        'group_id' => 'group-1',
        'name' => 'Crew A',
        'member_count' => 2,
        'site_count' => 1,
        'status' => 'active',
      ],
    ], [
      'status' => 'active',
    ]);

    $this->assertStringContainsString('role="search" aria-label="Filter groups"', $html);
    $this->assertStringNotContainsString('Calendar month navigation', $html);
  }

  #[Test]
  public function businessMembersGridToolbarUsesMembersFilterLandmark(): void
  {
    $renderer = new BusinessMembersGridRenderer();
    $html = $renderer->renderMembers([
      [
        'user_uuid' => 'member-alpha',
        'full_name' => 'Alpha Member',
        'email' => 'alpha@example.com',
        'role' => 'viewer',
        'status' => 'active',
        'accepted_at' => '2026-01-01T00:00:00Z',
      ],
    ], [
      'business_id' => 'business-123',
      'page' => 1,
    ]);

    $this->assertMatchesRegularExpression('/role="toolbar"[^>]*aria-label="Filter members"/s', $html);
    $this->assertStringNotContainsString('Calendar month navigation', $html);
  }

  #[Test]
  public function searchEnabledGridWithoutCustomLabelUsesGenericSearchLandmark(): void
  {
    $grid = DataGrid::create('unlabeled-search-grid', 'Unlabeled');
    $grid->enableSearch('Filter…');

    $html = $grid->table();

    $this->assertStringContainsString('role="search" aria-label="Search data grid"', $html);
    $this->assertStringNotContainsString('Calendar month navigation', $html);
  }
}
