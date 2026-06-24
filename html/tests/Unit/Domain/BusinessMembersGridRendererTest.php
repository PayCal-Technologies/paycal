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
    $this->assertStringContainsString('business_member_details_cell', $html);
    $this->assertStringContainsString('class="business_member_details_stack"', $html);
    $this->assertStringNotContainsString('business_member_details_status', $html);
    $this->assertStringContainsString('business_member_details_item', $html);
    $this->assertStringContainsString('data-joined-at-raw="2026-01-01T00:00:00Z"', $html);
    $this->assertStringContainsString('data-joined-display="Jan 1"', $html);
    $this->assertStringContainsString('business_member_joined_item', $html);
    $this->assertStringContainsString('data-current-role="viewer"', $html);
    $this->assertStringContainsString('data-member-id="member-alpha"', $html);
    $this->assertStringNotContainsString('businesses_member_role_trigger', $html);
    $this->assertStringContainsString('business_member_role_submenu', $html);
    $this->assertStringContainsString('business_member_role_menu_item', $html);
    $this->assertStringContainsString('data-member-action="edit-role"', $html);
    $this->assertStringContainsString('aria-haspopup="menu"', $html);
    $this->assertStringContainsString('aria-expanded="false"', $html);
    $this->assertStringContainsString('business_member_row_menu_toggle', $html);
    $this->assertStringContainsString('data-member-action="revoke"', $html);
    $this->assertStringContainsString('businesses_member_row_clickable', $html);
    $this->assertStringContainsString('data-member-name="Alpha Member"', $html);
    $this->assertStringContainsString('aria-label="View reports for Alpha Member"', $html);
    $this->assertStringContainsString('alpha@example.com', $html);
    $this->assertStringContainsString('zulu@example.com', $html);
    $this->assertStringContainsString('Name &amp; Details', $html);
    $this->assertStringContainsString('Last Active', $html);
    $this->assertStringContainsString('Hours', $html);
    $this->assertStringContainsString('Earnings', $html);
    $this->assertStringNotContainsString('<span class="business_member_details_status">Active</span>', $html);
    $this->assertStringContainsString('data-column-visibility="1"', $html);
    $this->assertStringContainsString('datagrid_column_menu', $html);
    $this->assertStringContainsString('datagrid_column_menu_toggle', $html);
    $this->assertStringContainsString('data-col-key="joined_at"', $html);
    $this->assertStringContainsString('data-col-key="last_active_at"', $html);
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
  public function shieldCheckIconMarkupRendersAccessibleVerifiedIcon(): void
  {
    $html = BusinessMembersGridRenderer::shieldCheckIconMarkup('Verified: protected reports enabled', 'business_member_data_access_icon is-active');

    $this->assertStringContainsString('business_member_data_access_icon is-active', $html);
    $this->assertStringContainsString('aria-label="Verified: protected reports enabled"', $html);
    $this->assertStringContainsString('business_member_data_access_icon_verified_shield', $html);
    $this->assertStringContainsString('business_member_data_access_icon_verified_check', $html);
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
    $this->assertStringContainsString('>Manager</span>', $html);
    $this->assertStringContainsString('data-role="coordinator" data-member-id="a1b2c3d4-e5f6-7890-abcd-ef1234567890" aria-current="true" disabled', $html);
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
    $this->assertStringNotContainsString('aria-label="Change role, currently Manager"', $html);
    $this->assertStringContainsString('business_member_role_submenu', $html);
    $this->assertStringContainsString('data-role="coordinator"', $html);
    $this->assertStringContainsString('data-role="contributor"', $html);
    $this->assertStringContainsString('data-role="viewer"', $html);
    $this->assertStringContainsString('data-role="member"', $html);
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
      '/<div class="datagrid_item datagrid_col_full_name business_member_details_item"[^>]*data-col-key="full_name"[^>]*>.*?<\/div>\s*<div class="datagrid_item datagrid_col_joined_at[^"]*"[^>]*>.*?<\/div>\s*<div class="datagrid_item datagrid_col_last_active_at[^"]*"[^>]*>.*?<\/div>/s',
      $html,
      $rowSegmentMatch,
    );
    $this->assertNotFalse($rowSegmentMatch[0] ?? false);
    $this->assertStringContainsString('business_member_details_stack', $rowSegmentMatch[0]);
    $this->assertStringNotContainsString('business_member_details_status', $rowSegmentMatch[0]);
    $this->assertStringNotContainsString('>Active<', $rowSegmentMatch[0]);

    preg_match(
      '/<div class="datagrid_item datagrid_col_joined_at[^"]*"[^>]*>.*?<\/div>/s',
      $html,
      $joinedCellMatch,
    );
    $this->assertNotFalse($joinedCellMatch[0] ?? false);
    $this->assertStringNotContainsString('business_member_details_status', $joinedCellMatch[0]);
    $this->assertStringContainsString('Jan 1', $joinedCellMatch[0]);

    preg_match(
      '/<div class="datagrid_item datagrid_col_last_active_at[^"]*"[^>]*>.*?<\/div>/s',
      $html,
      $lastActiveCellMatch,
    );
    $this->assertNotFalse($lastActiveCellMatch[0] ?? false);
    $this->assertStringNotContainsString('business_member_details_status', $lastActiveCellMatch[0]);
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
  public function renderMembersUsesMergedSearchToolbarWithoutPagination(): void
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
    $this->assertStringContainsString('class="datagrid_toolbar_filters business_members_toolbar_filters"', $html);
    $this->assertStringContainsString('class="datagrid_toolbar_bulk business_members_toolbar_bulk"', $html);
    $this->assertStringNotContainsString('class="datagrid_toolbar_center"', $html);
    $this->assertStringNotContainsString('class="datagrid_toolbar_end datagrid_pagination"', $html);
    $this->assertStringNotContainsString('Showing 1–25 of 30 members', $html);
    $this->assertStringNotContainsString('class="datagrid_pagination_btn datagrid_pagination_btn_icon"', $html);
    $this->assertStringNotContainsString('datagrid_pagination_top', $html);
    $this->assertStringNotContainsString('datagrid_pagination_bottom', $html);
    $this->assertStringNotContainsString('class="datagrid_controls"', $html);

    $searchPos = strpos($html, 'class="datagrid_search"', $toolbarStart);
    $this->assertNotFalse($searchPos);

    $this->assertStringContainsString('class="datagrid_column_menu"', $html);
    $this->assertMatchesRegularExpression(
      '/class="datagrid_toolbar_filters business_members_toolbar_filters"[\s\S]*class="datagrid_column_menu"/',
      $html,
    );
    $this->assertStringNotContainsString('class="datagrid_column_strip"', $html);
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
    $this->assertStringNotContainsString('Showing 1–1 of 1 members', $html);
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
    $this->assertStringNotContainsString('Showing 1–2 of 2 members', $html);
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

    preg_match('/<div class="datagrid_row[^"]*"[^>]*data-id="member-alpha".*?<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/s', $html, $rowMatch);
    $this->assertNotFalse($rowMatch[0] ?? false);
    preg_match_all('/class="datagrid_item (?:datagrid_col_select|datagrid_col_full_name|datagrid_col_joined_at|datagrid_col_last_active_at|datagrid_col_hours|datagrid_col_earnings|datagrid_item_actions)[^"]*"/', $rowMatch[0], $rowCells);

    $this->assertSame(count($headerCells[0]), count($rowCells[0]));
    $this->assertSame(7, count($headerCells[0]));
  }

  #[Test]
  public function loadingSkeletonUsesDatagridSkeletonRows(): void
  {
    $renderer = new BusinessMembersGridRenderer();
    $html = $renderer->loadingSkeleton();

    $this->assertStringContainsString('datagrid_loading', $html);
    $this->assertStringContainsString('datagrid_skeleton_row', $html);
    $this->assertStringContainsString('sk-line', $html);
  }

  #[Test]
  public function memberConsentStatusUsesBatchedHashReads(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $renderer = (string) file_get_contents($projectRoot . '/html/src/Domain/BusinessMembersGridRenderer.php');

    $this->assertStringContainsString('activeBusinessConsentByMember', $renderer);
    $this->assertStringContainsString('Database::pipelineHgetall(array_values($consentKeysById))', $renderer);
    $this->assertStringNotContainsString('hasActiveBusinessConsent', $renderer);
  }
}
