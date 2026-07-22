<?php declare(strict_types=1);

use PayCal\Domain\ArrayPager;
use PayCal\Domain\BusinessMemberReportsService;
use PayCal\Domain\BusinessMembersGridRenderer;
use PayCal\Domain\BusinessReportsPanelRenderer;
use PayCal\Domain\BusinessSitesGridRenderer;
use PayCal\Domain\DataGrid;
use PayCal\Domain\Earnings;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
#[Group('redis-write')]
final class BusinessesCspStyleContractTest extends TestCase
{
  private function assertNoInlineStyles(string $html, string $context): void
  {
    $this->assertStringNotContainsString('style="', $html, $context . ' must not contain style="..." attributes (CSP blocks inline styles)');
    $this->assertStringNotContainsString("style='", $html, $context . " must not contain style='...' attributes (CSP blocks inline styles)");
  }

  #[Test]
  public function businessesJsDoesNotUseInlineStyleAttributesInSkeletonMarkup(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $businessesJs = (string) file_get_contents($projectRoot . '/html/js/business/core/display-utils.js.php');

    $this->assertStringContainsString('datagrid_skeleton_row', $businessesJs);
    $this->assertStringNotContainsString('style="', $businessesJs);
    $this->assertStringNotContainsString("style='", $businessesJs);
  }

  #[Test]
  public function datagridColumnVisibilityUsesCssClassesNotInlineStyles(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $datagridJs = (string) file_get_contents($projectRoot . '/html/js/datagrid/index.php');
    $datagridCss = (string) file_get_contents($projectRoot . '/html/css/datagrid/index.php');

    $this->assertStringContainsString('datagrid_col_hidden', $datagridJs);
    $this->assertStringContainsString("classList.toggle('datagrid_col_hidden'", $datagridJs);
    $this->assertStringContainsString('.datagrid_table [data-col-key]', $datagridJs);
    $this->assertStringNotContainsString("querySelectorAll('[data-col-key]')", $datagridJs);
    $this->assertStringContainsString('.datagrid_col_hidden', $datagridCss);
    $this->assertStringNotContainsString('.style.display', $datagridJs);
    $this->assertStringNotContainsString('.style.width', $datagridJs);
    $this->assertStringNotContainsString('element.style', $datagridJs);
  }

  #[Test]
  public function renderedMembersGridHtmlContainsNoInlineStyles(): void
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
      'business_id' => 'business-123',
    ]);

    $this->assertStringContainsString('data-grid="business-members"', $html);
    $this->assertNoInlineStyles($html, 'BusinessMembersGridRenderer::renderMembers output');
    $this->assertNoInlineStyles($renderer->loadingSkeleton(), 'BusinessMembersGridRenderer::loadingSkeleton output');
    $this->assertNoInlineStyles($renderer->emptyMessage('No members'), 'BusinessMembersGridRenderer::emptyMessage output');
  }

  #[Test]
  public function renderedBusinessSitesGridHtmlContainsNoInlineStyles(): void
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
          'living_out_allowance' => '100.00',
          'travel_hours' => '50.00',
        ],
      ],
    ], [
      'owner_uuid' => 'owner-1',
      'name' => 'Acme Builders',
    ], 'owner-1', 'business-123', [
      'status' => 'active',
    ]);

    $this->assertStringContainsString('data-grid="business-sites-active"', $html);
    $this->assertStringContainsString('data-color="#6AA6FF"', $html);
    $this->assertStringContainsString('business_sites_site_name_cell', $html);
    $this->assertStringContainsString('datagrid_mobile_cards business_sites_mobile_cards', $html);
    $this->assertStringContainsString('business_sites_ownership_symbol--personal', $html);
    $this->assertStringContainsString('business_sites_ownership_status--personal', $html);
    $this->assertStringContainsString('datagrid_col_work_gross', $html);
    $this->assertStringContainsString('datagrid_col_wage', $html);
    $this->assertStringContainsString('datagrid_col_last_worked', $html);
    $this->assertStringContainsString('datagrid_col_budget_used', $html);
    $this->assertStringNotContainsString('datagrid_col_ownership', $html);
    $this->assertStringNotContainsString('[EIC]', $html);

    $managedHtml = $renderer->renderSites([
      [
        'site_owner_uuid' => 'owner-1',
        'site_id' => 'site-managed',
        'site_name' => 'Managed Yard',
        'settings' => ['site_status' => 'active'],
        'site_data' => [
          'site_name' => 'Managed Yard',
          'ownership_scope' => \PayCal\Domain\BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_BUSINESS,
          'status' => 'active',
          'province' => 'AB',
          'wage' => '45.00',
        ],
      ],
      [
        'site_owner_uuid' => 'owner-1',
        'site_id' => 'site-linked',
        'site_name' => 'Linked Yard',
        'settings' => ['site_status' => 'active'],
        'site_data' => [
          'site_name' => 'Linked Yard',
          'ownership_scope' => \PayCal\Domain\BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_LINKED,
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

    $this->assertStringContainsString('[EIC] Managed Yard', $managedHtml);
    $this->assertStringContainsString('Linked Yard', $managedHtml);
    $this->assertStringContainsString('business_sites_ownership_symbol--business', $managedHtml);
    $this->assertStringContainsString('business_sites_ownership_symbol--personal', $managedHtml);
    $this->assertStringNotContainsString('[EIC] Linked Yard', $managedHtml);
    $this->assertNoInlineStyles($managedHtml, 'BusinessSitesGridRenderer::renderSites managed ownership output');
    $this->assertNoInlineStyles($html, 'BusinessSitesGridRenderer::renderSites output');
    $this->assertNoInlineStyles($renderer->loadingSkeleton(), 'BusinessSitesGridRenderer::loadingSkeleton output');
    $this->assertNoInlineStyles($renderer->emptyMessage('No sites'), 'BusinessSitesGridRenderer::emptyMessage output');
  }

  #[Test]
  public function businessReportsPanelLoadingSkeletonContainsNoInlineStyles(): void
  {
    $renderer = new BusinessReportsPanelRenderer();
    $this->assertNoInlineStyles(
      $renderer->loadingSkeleton(2026),
      'BusinessReportsPanelRenderer::loadingSkeleton output',
    );
  }

  #[Test]
  public function dataGridTableOutputContainsNoInlineStyles(): void
  {
    $grid = DataGrid::create('csp-contract-grid', 'CSP Contract Grid');
    $grid->enableSearch('Filter...');
    $grid->enableSorting();
    $grid->enableColumnVisibility();
    $grid->addColumn('name', 'Name', true);
    $grid->addColumn('amount', 'Amount', true, null, 'right');
    $grid->addColumn('internal_count', 'Internal Count', true, null, 'right', false);
    $grid->addRowAction('revoke', 'Revoke');

    $pager = ArrayPager::fromArray([
      ['id' => 'row-1', 'name' => 'Alpha', 'amount' => '1.00', 'internal_count' => '4'],
      ['id' => 'row-2', 'name' => 'Zulu', 'amount' => '2.00', 'internal_count' => '8'],
    ], ['pageSize' => 25]);

    $html = $grid->table($pager);

    $this->assertStringContainsString('data-grid="csp-contract-grid"', $html);
    $this->assertStringContainsString('datagrid_col_internal_count datagrid_align_right datagrid_col_hidden', $html);
    $this->assertStringContainsString('data-col-key="internal_count" aria-hidden="true" inert', $html);
    $this->assertNoInlineStyles($html, 'DataGrid::table output');
  }

  #[Test]
  public function memberReportsAsyncSkeletonUsesClassBasedGridNotInlineStyles(): void
  {
    $method = new ReflectionMethod(BusinessMemberReportsService::class, 'buildAsyncSkeletonGrid');
    $service = new BusinessMemberReportsService();

    foreach ([3, 4, 11] as $cols) {
      $html = (string) $method->invoke($service, $cols, 4);
      $this->assertStringContainsString('sk-grid sk-grid--cols-' . $cols, $html);
      $this->assertNoInlineStyles($html, 'BusinessMemberReportsService::buildAsyncSkeletonGrid(' . $cols . ') output');
    }
  }

  #[Test]
  public function earningsAsyncSkeletonUsesClassBasedGridNotInlineStyles(): void
  {
    $method = new ReflectionMethod(Earnings::class, 'buildAsyncSkeletonGrid');

    foreach ([3, 4, 11] as $cols) {
      $html = (string) $method->invoke(null, $cols, 4);
      $this->assertStringContainsString('sk-grid sk-grid--cols-' . $cols, $html);
      $this->assertNoInlineStyles($html, 'Earnings::buildAsyncSkeletonGrid(' . $cols . ') output');
    }
  }

  #[Test]
  public function commonStylesheetDefinesSkeletonGridUtilityClasses(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $commonCss = (string) file_get_contents($projectRoot . '/html/css/common/index.php');

    $this->assertStringContainsString('.sk-grid {', $commonCss);
    foreach ([3, 4, 11] as $cols) {
      $this->assertStringContainsString('.sk-grid--cols-' . $cols, $commonCss);
    }
  }
}
