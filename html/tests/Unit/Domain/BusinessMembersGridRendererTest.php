<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\BusinessMembersGridRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BusinessMembersGridRendererTest extends TestCase
{
  #[Test]
  public function renderMembersIncludesStackedDetailsAndConsolidatedColumns(): void
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
      [
        'user_uuid' => 'member-zulu',
        'full_name' => 'Zulu Member',
        'email' => 'zulu@example.com',
        'role' => 'contributor',
        'status' => 'active',
        'accepted_at' => '2026-01-02T00:00:00Z',
      ],
    ], [
      'sort' => 'email',
      'direction' => 'asc',
      'business_id' => 'business-123',
    ]);

    $this->assertStringContainsString('id="business-members"', $html);
    $this->assertStringContainsString('data-grid="business-members"', $html);
    $this->assertStringContainsString('class="business_member_details_cell"', $html);
    $this->assertStringContainsString('class="business_member_details_status"', $html);
    $this->assertStringContainsString('business_member_details_item', $html);
    $this->assertStringContainsString('data-joined-at-raw="2026-01-01T00:00:00Z"', $html);
    $this->assertStringContainsString('data-joined-display="Jan 1"', $html);
    $this->assertStringContainsString('business_member_joined_item', $html);
    $this->assertStringContainsString('businesses_member_role_trigger', $html);
    $this->assertStringContainsString('data-current-role="viewer"', $html);
    $this->assertStringContainsString('data-member-id="member-alpha"', $html);
    $this->assertStringContainsString('data-business-id="business-123"', $html);
    $this->assertStringContainsString('aria-haspopup="listbox"', $html);
    $this->assertStringContainsString('aria-expanded="false"', $html);
    $this->assertStringContainsString('business_member_row_menu_toggle', $html);
    $this->assertStringContainsString('data-member-action="revoke"', $html);
    $this->assertStringContainsString('businesses_member_row_clickable', $html);
    $this->assertStringContainsString('data-member-name="Alpha Member"', $html);
    $this->assertStringContainsString('aria-label="View reports for Alpha Member"', $html);
    $this->assertStringContainsString('alpha@example.com', $html);
    $this->assertStringContainsString('zulu@example.com', $html);
    $this->assertStringContainsString('Name &amp; Details', $html);
    $this->assertStringContainsString('Hours', $html);
    $this->assertStringContainsString('Earnings', $html);
    $this->assertStringContainsString('Active', $html);
    $this->assertStringContainsString('data-column-visibility="1"', $html);
    $this->assertStringContainsString('datagrid_column_menu', $html);
    $this->assertStringContainsString('datagrid_column_menu_toggle', $html);
    $this->assertStringContainsString('data-col-key="joined_at"', $html);
    $this->assertStringContainsString('data-col-key="hours"', $html);
    $this->assertStringContainsString('data-col-key="earnings"', $html);
    $this->assertStringNotContainsString('data-col-key="email"', $html);
    $this->assertStringNotContainsString('data-col-key="role"', $html);
    $this->assertStringNotContainsString('data-col-key="status"', $html);
    $this->assertStringNotContainsString('YTD Gross', $html);
    $this->assertStringNotContainsString('Total Hours', $html);
    $this->assertStringNotContainsString('Trailing Baseline', $html);
    $this->assertLessThan(strpos($html, 'zulu@example.com'), strpos($html, 'alpha@example.com'));
  }

  #[Test]
  public function renderMembersDisplaysCoordinatorAsManagerForHarryStylesRow(): void
  {
    $renderer = new BusinessMembersGridRenderer();
    $html = $renderer->renderMembers([
      [
        'user_uuid' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        'full_name' => 'Harry Styles',
        'email' => 'harry@example.com',
        'role' => 'coordinator',
        'status' => 'active',
        'accepted_at' => '2026-01-01T00:00:00Z',
      ],
    ], [
      'business_id' => 'business-123',
    ]);

    $this->assertStringContainsString('Harry Styles', $html);
    $this->assertStringContainsString('data-current-role="coordinator"', $html);
    $this->assertStringContainsString('>Manager</button>', $html);
    $this->assertStringNotContainsString('>coordinator</button>', $html);
    $this->assertStringNotContainsString('>coordinator<', $html);
  }

  #[Test]
  public function renderMembersDisplaysFriendlyRoleLabels(): void
  {
    $renderer = new BusinessMembersGridRenderer();
    $html = $renderer->renderMembers([
      [
        'user_uuid' => 'member-owner',
        'full_name' => 'Owner Member',
        'email' => 'owner@example.com',
        'role' => 'owner',
        'status' => 'active',
        'accepted_at' => '2026-01-01T00:00:00Z',
      ],
      [
        'user_uuid' => 'member-manager',
        'full_name' => 'Manager Member',
        'email' => 'manager@example.com',
        'role' => 'coordinator',
        'status' => 'active',
        'accepted_at' => '2026-01-02T00:00:00Z',
      ],
      [
        'user_uuid' => 'member-contributor',
        'full_name' => 'Contributor Member',
        'email' => 'contributor@example.com',
        'role' => 'contributor',
        'status' => 'active',
        'accepted_at' => '2026-01-03T00:00:00Z',
      ],
      [
        'user_uuid' => 'member-viewer',
        'full_name' => 'Viewer Member',
        'email' => 'viewer@example.com',
        'role' => 'viewer',
        'status' => 'active',
        'accepted_at' => '2026-01-04T00:00:00Z',
      ],
      [
        'user_uuid' => 'member-member',
        'full_name' => 'Plain Member',
        'email' => 'member@example.com',
        'role' => 'member',
        'status' => 'active',
        'accepted_at' => '2026-01-05T00:00:00Z',
      ],
    ], [
      'business_id' => 'business-123',
    ]);

    $this->assertStringContainsString('>Owner<', $html);
    $this->assertStringContainsString('data-current-role="coordinator"', $html);
    $this->assertStringContainsString('>Manager<', $html);
    $this->assertStringContainsString('>Contributor<', $html);
    $this->assertStringContainsString('>Viewer<', $html);
    $this->assertStringContainsString('>Member<', $html);
    $this->assertStringNotContainsString('>coordinator<', $html);
    $this->assertStringContainsString('aria-label="Change role, currently Manager"', $html);
  }

  #[Test]
  public function renderMembersMarksOwnerRoleCellAsStatic(): void
  {
    $renderer = new BusinessMembersGridRenderer();
    $html = $renderer->renderMembers([
      [
        'user_uuid' => 'member-owner',
        'full_name' => 'Owner Member',
        'email' => 'owner@example.com',
        'role' => 'owner',
        'status' => 'active',
        'accepted_at' => '2026-01-01T00:00:00Z',
      ],
    ], [
      'business_id' => 'business-123',
    ]);

    $this->assertStringContainsString('businesses_member_role_cell_static', $html);
    $this->assertStringContainsString('data-member-id="member-owner"', $html);
    $this->assertStringNotContainsString('class="businesses_member_role_trigger"', $html);
    $this->assertStringNotContainsString('businesses_member_role_trigger', $html);
  }

  #[Test]
  public function renderMembersUsesJoinedAtFieldWhenAcceptedAtMissing(): void
  {
    $renderer = new BusinessMembersGridRenderer();
    $html = $renderer->renderMembers([
      [
        'user_uuid' => 'member-joined-at',
        'full_name' => 'Joined At Member',
        'email' => 'joined@example.com',
        'role' => 'viewer',
        'status' => 'active',
        'joined_at' => '2026-03-15T12:00:00Z',
      ],
    ], [
      'business_id' => 'business-123',
    ]);

    $this->assertStringContainsString('data-joined-at-raw="2026-03-15T12:00:00Z"', $html);
    $this->assertStringContainsString('data-joined-display="Mar 15"', $html);
    $this->assertStringNotContainsString('Unavailable', $html);
  }

  #[Test]
  public function renderMembersKeepsRoleStatusInsideDetailsCell(): void
  {
    $renderer = new BusinessMembersGridRenderer();
    $html = $renderer->renderMembers([
      [
        'user_uuid' => 'member-structure',
        'full_name' => 'Structure Member',
        'email' => 'structure@example.com',
        'role' => 'viewer',
        'status' => 'active',
        'accepted_at' => '2026-01-01T00:00:00Z',
      ],
    ], [
      'business_id' => 'business-123',
    ]);

    preg_match(
      '/<div class="datagrid_item datagrid_col_full_name business_member_details_item"[^>]*data-col-key="full_name"[^>]*>.*?<\/div>\s*<div class="datagrid_item datagrid_col_joined_at[^"]*"[^>]*>.*?<\/div>/s',
      $html,
      $rowSegmentMatch,
    );
    $this->assertNotFalse($rowSegmentMatch[0] ?? false);
    $this->assertStringContainsString('business_member_details_status', $rowSegmentMatch[0]);
    $this->assertStringContainsString('Active', $rowSegmentMatch[0]);

    preg_match(
      '/<div class="datagrid_item datagrid_col_joined_at[^"]*"[^>]*>.*?<\/div>/s',
      $html,
      $joinedCellMatch,
    );
    $this->assertNotFalse($joinedCellMatch[0] ?? false);
    $this->assertStringNotContainsString('business_member_details_status', $joinedCellMatch[0]);
    $this->assertStringContainsString('Jan 1', $joinedCellMatch[0]);
  }

  #[Test]
  public function renderMembersFormatsJoinedDateWithoutMinutePrecision(): void
  {
    $renderer = new BusinessMembersGridRenderer();
    $html = $renderer->renderMembers([
      [
        'user_uuid' => 'member-recent',
        'full_name' => 'Recent Member',
        'email' => 'recent@example.com',
        'role' => 'viewer',
        'status' => 'active',
        'accepted_at' => (new \DateTimeImmutable('today'))->format('Y-m-d\TH:i:s\Z'),
      ],
    ], [
      'business_id' => 'business-123',
    ]);

    $this->assertStringContainsString('Today', $html);
    $this->assertStringNotContainsString('00:05', $html);
  }

  #[Test]
  public function summarizePageMetricsCountsManagersAndPending(): void
  {
    $metrics = BusinessMembersGridRenderer::summarizePageMetrics([
      ['role' => 'coordinator'],
      ['role' => 'viewer'],
      ['role' => 'coordinator'],
    ], '');

    $this->assertSame(3, $metrics['members']);
    $this->assertSame(2, $metrics['managers']);
  }

  #[Test]
  public function emptyMessageEscapesUserFacingText(): void
  {
    $renderer = new BusinessMembersGridRenderer();
    $html = $renderer->emptyMessage('<script>alert(1)</script>');

    $this->assertStringContainsString('datagrid_empty', $html);
    $this->assertStringNotContainsString('<script>', $html);
    $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
  }

  #[Test]
  public function renderMembersUsesMergedSearchPaginationToolbar(): void
  {
    $renderer = new BusinessMembersGridRenderer();
    $members = [];
    for ($index = 1; $index <= 30; $index++) {
      $members[] = [
        'user_uuid' => 'member-' . $index,
        'full_name' => 'Member ' . $index,
        'email' => 'member' . $index . '@example.com',
        'role' => 'viewer',
        'status' => 'active',
        'accepted_at' => '2026-01-01T00:00:00Z',
      ];
    }

    $html = $renderer->renderMembers($members, [
      'business_id' => 'business-123',
      'page' => 1,
    ]);

    $toolbarStart = strpos($html, 'class="datagrid_toolbar datagrid_toolbar_search_pagination"');
    $this->assertNotFalse($toolbarStart);
    $this->assertStringContainsString('placeholder="Filter members..."', $html);
    $this->assertStringContainsString('class="datagrid_toolbar_start"', $html);
    $this->assertStringContainsString('class="datagrid_toolbar_center"', $html);
    $this->assertStringContainsString('class="datagrid_toolbar_end datagrid_pagination"', $html);
    $this->assertStringContainsString('Showing 1–25 of 30 members', $html);
    $this->assertStringContainsString('class="datagrid_pagination_btn datagrid_pagination_btn_icon"', $html);
    $this->assertStringContainsString('aria-label="Previous"', $html);
    $this->assertStringContainsString('aria-label="Next"', $html);
    $this->assertStringNotContainsString('datagrid_pagination_top', $html);
    $this->assertStringNotContainsString('datagrid_pagination_bottom', $html);
    $this->assertStringNotContainsString('class="datagrid_controls"', $html);

    $searchPos = strpos($html, 'class="datagrid_search"', $toolbarStart);
    $infoPos = strpos($html, 'class="datagrid_page datagrid_page_info"', $toolbarStart);
    $prevPos = strpos($html, 'data-direction="prev"', $toolbarStart);
    $nextPos = strpos($html, 'data-direction="next"', $toolbarStart);
    $this->assertNotFalse($searchPos);
    $this->assertNotFalse($infoPos);
    $this->assertNotFalse($prevPos);
    $this->assertNotFalse($nextPos);
    $this->assertLessThan($infoPos, $searchPos);
    $this->assertLessThan($prevPos, $infoPos);
    $this->assertLessThan($nextPos, $prevPos);

    $this->assertStringContainsString('class="datagrid_column_menu"', $html);
    $this->assertStringContainsString('class="datagrid_header_row"', $html);
  }

  #[Test]
  public function renderMembersFiltersRowsBySearchTermAndPreservesInputValue(): void
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
      [
        'user_uuid' => 'member-beta',
        'full_name' => 'Beta Member',
        'email' => 'beta@example.com',
        'role' => 'contributor',
        'status' => 'active',
        'accepted_at' => '2026-01-02T00:00:00Z',
      ],
    ], [
      'business_id' => 'business-123',
      'search' => 'alpha',
    ]);

    $this->assertStringContainsString('Alpha Member', $html);
    $this->assertStringNotContainsString('Beta Member', $html);
    $this->assertStringContainsString('data-search="alpha"', $html);
    $this->assertStringContainsString('value="alpha"', $html);
    $this->assertStringContainsString('Showing 1–1 of 1 members', $html);
  }

  #[Test]
  public function renderMembersFiltersRowsByPartialNameSearchTom(): void
  {
    $renderer = new BusinessMembersGridRenderer();
    $html = $renderer->renderMembers([
      [
        'user_uuid' => 'member-adrian',
        'full_name' => 'Adrianl Tchaikovskie',
        'email' => 'adrian@example.com',
        'role' => 'viewer',
        'status' => 'active',
        'accepted_at' => '2026-01-01T00:00:00Z',
      ],
      [
        'user_uuid' => 'member-tom',
        'full_name' => 'Tom Henderson',
        'email' => 'tom@example.com',
        'role' => 'contributor',
        'status' => 'active',
        'accepted_at' => '2026-01-02T00:00:00Z',
      ],
      [
        'user_uuid' => 'member-thompson',
        'full_name' => 'Tommy Lee',
        'email' => 'tommy@example.com',
        'role' => 'viewer',
        'status' => 'active',
        'accepted_at' => '2026-01-03T00:00:00Z',
      ],
    ], [
      'business_id' => 'business-123',
      'search' => 'tom',
    ]);

    $this->assertStringContainsString('Tom Henderson', $html);
    $this->assertStringContainsString('Tommy Lee', $html);
    $this->assertStringNotContainsString('Adrianl Tchaikovskie', $html);
    $this->assertStringContainsString('data-search="tom"', $html);
    $this->assertStringContainsString('value="tom"', $html);
    $this->assertStringContainsString('Showing 1–2 of 2 members', $html);
  }

  #[Test]
  public function renderMembersAlignsHeaderAndRowColumnCounts(): void
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
    ]);

    $this->assertStringContainsString('datagrid_col_select business_members_header_select', $html);
    $this->assertStringContainsString('datagrid_col_select business_members_row_select', $html);

    preg_match(
      '/<div class="datagrid_header_content" role="row">(.*?)<\/div>\s*<\/div>\s*<div class="datagrid_body"/s',
      $html,
      $headerMatch,
    );
    $this->assertNotFalse($headerMatch[1] ?? false);
    preg_match_all('/class="datagrid_heading[^"]*"/', $headerMatch[1], $headerCells);

    preg_match(
      '/data-id="member-alpha".*?<div class="datagrid_row_content">(.*?)<\/div>\s*<\/div>\s*<\/div>/s',
      $html,
      $rowMatch,
    );
    $this->assertNotFalse($rowMatch[1] ?? false);
    preg_match_all('/class="datagrid_item[^"]*"/', $rowMatch[1], $rowCells);

    $this->assertSame(count($headerCells[0]), count($rowCells[0]));
    $this->assertSame(6, count($headerCells[0]));
  }

  #[Test]
  public function loadingSkeletonUsesDatagridSkeletonRows(): void
  {
    $renderer = new BusinessMembersGridRenderer();
    $html = $renderer->loadingSkeleton();

    $this->assertStringContainsString('businesses_datagrid_skeleton_row', $html);
    $this->assertStringContainsString('sk-line', $html);
  }
}
