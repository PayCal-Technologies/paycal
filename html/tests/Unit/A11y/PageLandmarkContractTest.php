<?php declare(strict_types=1);

use PayCal\Domain\DataGrid;
use PayCal\Domain\Strings;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class PageLandmarkContractTest extends TestCase
{
  #[Test]
  public function personalSitesGridControllerSetsSitesFilterLandmark(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $controller = (string) file_get_contents($projectRoot . '/html/src/Controllers/SitesController.php');

    $this->assertStringContainsString("setControlsAriaLabel(Strings::i18n('SITES_FILTER_ARIA'))", $controller);
    $this->assertStringNotContainsString("setControlsAriaLabel(Strings::i18n('BUSINESS_SITES_FILTER_ARIA'))", $controller);
    $this->assertStringNotContainsString('Search data grid', $controller);
  }

  #[Test]
  public function businessDiscoveryGridsSetSpecificSearchLandmarks(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $controller = (string) file_get_contents($projectRoot . '/html/src/Controllers/BusinessDiscoveryController.php');

    $this->assertStringContainsString("setControlsAriaLabel(Strings::i18n('BUSINESSES_LIST_FILTER_ARIA'))", $controller);
    $this->assertStringContainsString("setControlsAriaLabel(Strings::i18n('BUSINESSES_AUDIT_FILTER_ARIA'))", $controller);
    $this->assertStringContainsString("setControlsAriaLabel(Strings::i18n('BUSINESSES_INVITES_HISTORY_FILTER_ARIA'))", $controller);
    $this->assertStringNotContainsString('role="search" aria-label="Search data grid"', $controller);
  }

  #[Test]
  public function businessBrowserSearchFormUsesLocalizedLandmarkLabel(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $panel = (string) file_get_contents($projectRoot . '/html/business/_archive/partials/browser_panel.php');

    $this->assertStringContainsString("role=\"search\" aria-label=\"<?php echo businesses_index_i18n_html('BUSINESSES_BROWSER_SEARCH_ARIA'); ?>\"", $panel);
    $this->assertStringContainsString("businesses_index_i18n_html('BUSINESSES_BROWSER_SEARCH_ARIA')", $panel);
    $this->assertStringNotContainsString('Search by business name or owner email.', $panel);
  }

  #[Test]
  public function memberReportsDialogBodyRegionHasAccessibleName(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $dialogs = (string) file_get_contents($projectRoot . '/html/business/_partials/members_dialogs.php');

    $this->assertStringContainsString("role=\"region\" aria-label=\"<?php echo businesses_index_i18n_html('BUSINESSES_MEMBER_REPORTS_CONTENT_ARIA'); ?>\"", $dialogs);
  }

  #[Test]
  public function adminTaxBracketsTemplateDoesNotDuplicateMainLandmark(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $template = (string) file_get_contents($projectRoot . '/templates/admin-tax-brackets.php');

    $this->assertStringContainsString('role="region" aria-label="__ADMIN_TAX_BRACKETS_EDITOR_ARIA__"', $template);
    $this->assertStringNotContainsString('role="main"', $template);
  }

  #[Test]
  public function calendarPageSuppressesDataGridMonthNavigationLandmark(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $indexPage = (string) file_get_contents($projectRoot . '/html/index.php');

    $this->assertStringContainsString("'suppressMonthNavigation' => true", $indexPage);
    $this->assertStringContainsString('aria-labelledby="calendar-landmark-title"', $indexPage);
    $this->assertStringContainsString('role="toolbar" aria-label="<?php echo htmlspecialchars((string) html_index_i18n(\'CALENDAR_VIEW_MODE_ARIA\')', $indexPage);
  }

  #[Test]
  public function searchEnabledGridsWithoutCustomLabelUseGenericDataGridSearchLandmark(): void
  {
    $grid = DataGrid::create('generic-search-grid', 'Example');
    $grid->enableSearch('Filter…');

    $html = $grid->table();

    $this->assertStringContainsString('role="search" aria-label="' . Strings::i18n('DATAGRID_SEARCH_ARIA') . '"', $html);
    $this->assertStringNotContainsString('Calendar month navigation', $html);
  }

  #[Test]
  public function landmarkFilterAriaKeysExistInEnglishLocale(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $strings = (string) file_get_contents($projectRoot . '/strings/en.txt');
    $requiredKeys = [
      'SITES_FILTER_ARIA',
      'BUSINESSES_LIST_FILTER_ARIA',
      'BUSINESSES_AUDIT_FILTER_ARIA',
      'BUSINESSES_INVITES_HISTORY_FILTER_ARIA',
      'BUSINESSES_MEMBER_REPORTS_CONTENT_ARIA',
    ];

    foreach ($requiredKeys as $key) {
      $this->assertMatchesRegularExpression(
        '/^' . preg_quote($key, '/') . ' .+/m',
        $strings,
        sprintf('Missing or empty %s in strings/en.txt', $key),
      );
    }
  }
}
