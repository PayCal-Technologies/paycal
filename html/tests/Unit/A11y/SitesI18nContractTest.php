<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class SitesI18nContractTest extends TestCase
{
  #[Test]
  public function sitesPageUsesI18nHelpersForVisibleCopy(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $page = (string) file_get_contents($projectRoot . '/html/sites/index.php');

    $this->assertStringContainsString('sites_index_i18n(', $page);
    $this->assertStringContainsString("sites_index_i18n('ACTIVE')", $page);
    $this->assertStringContainsString("sites_index_i18n('ARCHIVED')", $page);
    $this->assertStringContainsString("sites_index_i18n('BUSINESS_STATUS_ADD_SHORT')", $page);
    $this->assertStringContainsString("sites_index_i18n('BUSINESS_SITES_STATUS_TAG_PERSONAL')", $page);
    $this->assertStringContainsString("sites_index_i18n('SITES_EARNINGS_HISTORY_DISCLAIMER')", $page);
    $this->assertStringContainsString("sites_index_i18n('SITES_RECOVER_DATA')", $page);
    $this->assertStringNotContainsString('>Recover Data<', $page);
    $this->assertStringNotContainsString('>Active</li>', $page);
    $this->assertStringNotContainsString('>Archived</li>', $page);
    $this->assertStringNotContainsString("sites_index_i18n('SITES_ACTIVE_TAB_DISCLAIMER')", $page);
    $this->assertStringNotContainsString("sites_index_i18n('SITES_ARCHIVED_DELETE_WARNING')", $page);
    $this->assertStringNotContainsString('These sites are currently in use and available for new work entries.', $page);
    $this->assertStringNotContainsString('History of your earnings per site.', $page);
  }

  #[Test]
  public function sitesJsTabSwitchingUsesSsrDisclaimersNotHardcodedCopy(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $js = (string) file_get_contents($projectRoot . '/html/js/sites/index.php');

    $this->assertStringContainsString("disclaimer.dataset.forTab", $js);
    $this->assertStringNotContainsString('These sites are currently in use', $js);
    $this->assertStringNotContainsString('History of your earnings per site', $js);
    $this->assertStringNotContainsString('Deleting an archived site will permanently remove', $js);
  }

  #[Test]
  public function sitesPageTabCopyIsTranslatedInAllLocales(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $englishFallbacks = [
      'SITES_ACTIVE_TAB_DISCLAIMER' => 'These sites are currently in use and available for new work entries.',
      'SITES_ARCHIVED_DELETE_WARNING' => 'Deleting an archived site will permanently remove all associated work entries.',
      'SITES_EARNINGS_HISTORY_DISCLAIMER' => 'History of your earnings per site.',
      'ARCHIVED' => 'Archived',
    ];

    foreach (['de', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'] as $locale) {
      $content = (string) file_get_contents($projectRoot . '/strings/' . $locale . '.txt');

      foreach ($englishFallbacks as $key => $englishValue) {
        $pattern = '/^' . preg_quote($key, '/') . ' ' . preg_quote($englishValue, '/') . '$/m';
        $this->assertDoesNotMatchRegularExpression(
          $pattern,
          $content,
          sprintf('%s still uses the English fallback for %s', $locale, $key),
        );
      }
    }
  }

  #[Test]
  public function siteEditorDialogsUseI18nForPlanningAndSettings(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $partial = (string) file_get_contents($projectRoot . '/html/sites/_partials/site_editor_dialogs.php');

    $this->assertStringContainsString("site_editor_i18n('SITES_EDITOR_SETTINGS_HEADING')", $partial);
    $this->assertStringContainsString("site_editor_i18n('SITES_SAVE_SITE')", $partial);
    $this->assertStringContainsString("site_editor_i18n('SITES_PERSONAL_PLANNING_EMPTY')", $partial);
    $this->assertStringNotContainsString('>Save Site<', $partial);
  }

  #[Test]
  public function siteColorSwatchesExposeAccessibleLabelsAndSelectionState(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $partial = (string) file_get_contents($projectRoot . '/html/sites/_partials/site_editor_dialogs.php');
    $coreJs = (string) file_get_contents($projectRoot . '/html/js/sites/site-editor-core.php');
    $legacyJs = (string) file_get_contents($projectRoot . '/html/js/sites/index.php');
    $palette = (string) file_get_contents($projectRoot . '/html/src/Domain/Config/SiteColorPalette.php');

    $this->assertStringContainsString("data-label='{\$label}'", $partial);
    $this->assertStringContainsString("aria-label='{\$ariaLabel}'", $partial);
    $this->assertStringContainsString("aria-pressed='{\$pressed}'", $partial);
    $this->assertStringContainsString("site_editor_i18n('SITES_SITE_COLOR_LABEL') . ': ' . \$sc['label'] . ' (' . \$sc['hex'] . ')'", $partial);
    $this->assertStringContainsString("swatch.setAttribute('aria-pressed', selected ? 'true' : 'false');", $coreJs);
    $this->assertStringContainsString("s.setAttribute('aria-pressed', selected ? 'true' : 'false');", $legacyJs);
    $this->assertStringContainsString('foreach ([self::pickerPalette(), self::palette()] as $palette)', $palette);
  }

  #[Test]
  public function sitesJsBundleInjectsSharedSitesTranslationObject(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $js = (string) file_get_contents($projectRoot . '/html/js/sites/index.php');

    $this->assertStringContainsString("require __DIR__ . '/i18n.php'", $js);
    $this->assertStringContainsString('SITES_T.SITES_CREATED_SUCCESS', $js);
    $this->assertStringContainsString('sitesFormatMessage', $js);
    $this->assertStringNotContainsString("PC.showToast('Site created successfully')", $js);
  }

  #[Test]
  public function personalSitesGridUsesLocalizedStringsInController(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $controller = (string) file_get_contents($projectRoot . '/html/src/Controllers/SitesController.php');

    $this->assertStringContainsString("Strings::i18n('BUSINESS_SITES_FILTER_PLACEHOLDER')", $controller);
    $this->assertStringContainsString("Strings::i18n('SITE')", $controller);
    $this->assertStringContainsString("Strings::i18n('BUSINESS_SITES_STATUS_TAG_PERSONAL')", $controller);
    $this->assertStringContainsString("Strings::i18n('BUSINESS_SITES_GRID_COLUMN_ENTRIES')", $controller);
    $this->assertStringContainsString("Strings::i18n('BUSINESS_SITES_GRID_COLUMN_WORK_GROSS')", $controller);
    $this->assertStringContainsString("Strings::i18n('WAGE')", $controller);
    $this->assertStringContainsString("Strings::i18n('BUSINESS_SITES_GRID_COLUMN_LAST_WORKED')", $controller);
    $this->assertStringContainsString("Strings::i18n('BUSINESS_SITES_GRID_COLUMN_BUDGET')", $controller);
    $this->assertStringContainsString("Strings::i18n('BUSINESS_SITES_GRID_COLUMN_USED')", $controller);
    $this->assertStringContainsString("\$grid->addColumn('budget_amount', Strings::i18n('BUSINESS_SITES_GRID_COLUMN_BUDGET'), true, 'minmax(6rem, 1fr)', 'right', false, true)", $controller);
    $this->assertStringContainsString("\$grid->addColumn('budget_used', Strings::i18n('BUSINESS_SITES_GRID_COLUMN_USED'), true, 'minmax(7rem, 1.1fr)', 'right', false, true, true)", $controller);
    $this->assertStringNotContainsString("enableSearch('Filter sites", $controller);
    $this->assertStringNotContainsString("addColumn('site_name', 'Name'", $controller);
    $this->assertStringNotContainsString("addColumn('living_out_allowance'", $controller);
    $this->assertStringNotContainsString("addColumn('travel_hours'", $controller);
    $this->assertStringNotContainsString("addColumn('province'", $controller);
  }

  #[Test]
  public function personalSitesGridColumnLabelsAreTranslatedInAllLocales(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $englishFallbacks = [
      'SITES_CREATE' => 'Create',
      'BUSINESS_SITES_FILTER_PLACEHOLDER' => 'Filter sites…',
      'BUSINESS_SITES_GRID_COLUMN_ENTRIES' => 'Entries',
      'BUSINESS_SITES_GRID_COLUMN_LAST_WORKED' => 'Last worked',
    ];

    foreach (['de', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'] as $locale) {
      $content = (string) file_get_contents($projectRoot . '/strings/' . $locale . '.txt');

      foreach ($englishFallbacks as $key => $englishValue) {
        $pattern = '/^' . preg_quote($key, '/') . ' ' . preg_quote($englishValue, '/') . '$/m';
        $this->assertDoesNotMatchRegularExpression(
          $pattern,
          $content,
          sprintf('%s still uses the English fallback for %s', $locale, $key),
        );
      }
    }
  }

  #[Test]
  public function personalSitesMobileGridLabelsUseDataColLabelAttribute(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $css = (string) file_get_contents($projectRoot . '/html/css/sites/index.php');
    $dataGrid = (string) file_get_contents($projectRoot . '/html/src/Domain/DataGrid.php');

    $this->assertStringContainsString('data-col-label', $dataGrid);
    $this->assertStringContainsString('content: attr(data-col-label)', $css);
    $this->assertStringNotContainsString('content: "Name"', $css);
    $this->assertStringNotContainsString('content: "Wage"', $css);
  }

  #[Test]
  public function personalSitesGridHeaderSizingScopesNumericStylesToDataCells(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $css = (string) file_get_contents($projectRoot . '/html/css/sites/index.php');

    $this->assertStringContainsString('[data-grid="sites-active"] .datagrid_heading', $css);
    $this->assertStringContainsString('[data-grid="sites-active"] .datagrid_item.datagrid_col_wage', $css);
    $this->assertStringContainsString('[data-grid="sites-active"] .datagrid_item.datagrid_col_last_worked', $css);
    $this->assertStringContainsString('.datagrid_item.datagrid_col_budget_amount:not(:empty)', $css);
    $this->assertStringNotContainsString('[data-grid="sites-active"] .datagrid_col_wage,', $css);
  }

  #[Test]
  public function profileBillingUsesI18nForLocalCoreCopy(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $billing = (string) file_get_contents($projectRoot . '/html/settings/_partials/panel_account_billing.php');
    $locale = (string) file_get_contents($projectRoot . '/html/settings/_partials/panel_account_locale.php');

    $this->assertStringContainsString("settings_index_i18n('PROFILE_BILLING_PUBLIC_CORE_NOTE')", $billing);
    $this->assertStringContainsString("settings_index_i18n('PROFILE_LOCALE_EN_CA')", (string) file_get_contents($projectRoot . '/html/settings/_partials/vars_account.php'));
    $this->assertStringNotContainsString("'English (Canada)'", $locale);
    $this->assertStringNotContainsString('>Enable Premium<', $billing);
  }
}
