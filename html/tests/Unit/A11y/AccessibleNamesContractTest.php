<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class AccessibleNamesContractTest extends TestCase
{
  private function projectRoot(): string
  {
    return dirname(__DIR__, 4);
  }

  /**
   * @return list<string>
   */
  private function accessibleNameKeys(): array
  {
    return [
      'BUSINESS_REPORTS_TOOLBAR_ARIA',
      'BUSINESS_REPORTS_SECTIONS_ARIA',
      'BUSINESS_REPORTS_FILTERS_ARIA',
      'BUSINESS_REPORTS_FILTER_PERIOD_ARIA',
      'BUSINESS_REPORTS_FILTER_COMPARE_ARIA',
      'BUSINESS_REPORTS_FILTER_SITE_ARIA',
      'BUSINESS_REPORTS_FILTER_GROUP_ARIA',
      'BUSINESS_REPORTS_FILTER_MEMBER_ARIA',
      'BUSINESS_REPORTS_CUSTOMIZE_DRAWER_ARIA',
      'BUSINESS_REPORTS_PRESETS_ARIA',
      'BUSINESS_MEMBERS_REPORT_SELECTED_LIST_ARIA',
      'BUSINESS_MEMBERS_REPORT_ADD_LIST_ARIA',
      'BUSINESS_MEMBERS_REPORT_REMOVE_ARIA',
      'BUSINESS_MEMBERS_ROLE_MATRIX_ARIA',
      'REPORTS_PRINT_DIALOG_DESC',
      'REPORTS_PRINT_MODE_LEGEND',
      'REPORTS_PRINT_MODE_BW',
      'REPORTS_PRINT_MODE_GRAYSCALE',
      'REPORTS_PRINT_MODE_COLOR',
      'REPORTS_PRINT_MODE_BW_DESC',
      'REPORTS_PRINT_MODE_GRAYSCALE_DESC',
      'REPORTS_PRINT_MODE_COLOR_DESC',
      'SETTINGS_ACCENT_PREVIEW_ARIA',
      'SETTINGS_TEXT_SIZE_SLIDER_ARIA',
      'SETTINGS_SPACING_SLIDER_ARIA',
    ];
  }

  #[Test]
  public function businessReportsToolbarUsesLocalizedAccessibleNamesAndTabRoles(): void
  {
    $page = (string) file_get_contents($this->projectRoot() . '/html/business/reports/index.php');

    $this->assertStringContainsString("aria-label=\"<?php echo htmlspecialchars(\$i18n['BUSINESS_REPORTS_TOOLBAR_ARIA']", $page);
    $this->assertStringContainsString("aria-label=\"<?php echo htmlspecialchars(\$i18n['BUSINESS_REPORTS_SECTIONS_ARIA']", $page);
    $this->assertStringContainsString("aria-label=\"<?php echo htmlspecialchars(\$i18n['BUSINESS_REPORTS_FILTERS_ARIA']", $page);
    $this->assertStringContainsString('role="tablist"', $page);
    $this->assertStringContainsString('role="tab"', $page);
    $this->assertStringContainsString('aria-controls="business_reports_panel"', $page);
    $this->assertStringContainsString('id="business_reports_panel" role="tabpanel"', $page);
    $this->assertStringNotContainsString('aria-label="Business report controls"', $page);
    $this->assertStringNotContainsString('aria-label="Report filters"', $page);
  }

  #[Test]
  public function businessMembersReportListsUseLocalizedAccessibleNames(): void
  {
    $page = (string) file_get_contents($this->projectRoot() . '/html/business/members/index.php');
    $membersJs = (string) file_get_contents($this->projectRoot() . '/html/js/business/subpages/members.js.php');
    $stateJs = (string) file_get_contents($this->projectRoot() . '/html/js/business/core/state.js.php');

    $this->assertStringContainsString("BUSINESS_MEMBERS_REPORT_SELECTED_LIST_ARIA", $page);
    $this->assertStringContainsString("BUSINESS_MEMBERS_REPORT_ADD_LIST_ARIA", $page);
    $this->assertStringContainsString("businesses_index_i18n_html('BUSINESS_MEMBERS_ROLE_MATRIX_ARIA')", $page);
    $this->assertStringNotContainsString('aria-label="Selected members for report generation"', $page);
    $this->assertStringNotContainsString('aria-label="Members available to add"', $page);
    $this->assertStringContainsString('T.memberReportRemoveAria', $membersJs);
    $this->assertStringContainsString("org_js_index_i18n('BUSINESS_MEMBERS_REPORT_REMOVE_ARIA')", $stateJs);
    $this->assertStringNotContainsString('aria-label="Remove ', $membersJs);
  }

  #[Test]
  public function reportsPrintDialogUsesModalSemanticsAndLocalizedStrings(): void
  {
    $reportsPrintJs = (string) file_get_contents($this->projectRoot() . '/html/js/reports-print/index.php');

    $this->assertStringContainsString('const REPORTS_PRINT_T = ', $reportsPrintJs);
    $this->assertStringContainsString("setAttribute('aria-modal', 'true')", $reportsPrintJs);
    $this->assertStringContainsString('REPORTS_PRINT_T.CLOSE', $reportsPrintJs);
    $this->assertStringContainsString('REPORTS_PRINT_T.EARNINGS_PRINT_REPORT', $reportsPrintJs);
    $this->assertStringContainsString('aria-label="${REPORTS_PRINT_T.EARNINGS_PRINT_REPORT}"', $reportsPrintJs);
    $this->assertStringNotContainsString('aria-label="Close"', $reportsPrintJs);
    $this->assertStringNotContainsString("class=\"modal_title\">Print report<", $reportsPrintJs);
  }

  #[Test]
  public function settingsAppearanceSlidersUseLocalizedAccessibleNames(): void
  {
    $themePanel = (string) file_get_contents($this->projectRoot() . '/html/settings/_partials/panel_appearance_theme.php');

    $this->assertStringContainsString("settings_index_i18n('SETTINGS_ACCENT_PREVIEW_ARIA')", $themePanel);
    $this->assertStringContainsString("settings_index_i18n('SETTINGS_TEXT_SIZE_SLIDER_ARIA')", $themePanel);
    $this->assertStringContainsString("settings_index_i18n('SETTINGS_SPACING_SLIDER_ARIA')", $themePanel);
    $this->assertStringNotContainsString('aria-label="Accent preview"', $themePanel);
    $this->assertStringNotContainsString('aria-label="Text size adjustment"', $themePanel);
    $this->assertStringNotContainsString('aria-label="Spacing adjustment"', $themePanel);
  }

  #[Test]
  public function accessibleNameKeysAreTranslatedInAllLocales(): void
  {
    $locales = ['de', 'en', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'];

    foreach ($locales as $locale) {
      $strings = (string) file_get_contents($this->projectRoot() . '/strings/' . $locale . '.txt');

      foreach ($this->accessibleNameKeys() as $key) {
        $this->assertMatchesRegularExpression(
          '/^' . preg_quote($key, '/') . ' .+/m',
          $strings,
          sprintf('Missing or empty %s in %s.txt', $key, $locale),
        );
      }
    }
  }
}
