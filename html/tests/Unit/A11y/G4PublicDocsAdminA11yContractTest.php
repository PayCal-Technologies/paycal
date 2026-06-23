<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class G4PublicDocsAdminA11yContractTest extends TestCase
{
  private function projectRoot(): string
  {
    return dirname(__DIR__, 4);
  }

  private function htmlRoot(): string
  {
    return $this->projectRoot() . '/html';
  }

  private function assertNoInlineStyles(string $html, string $context): void
  {
    $this->assertStringNotContainsString('style="', $html, $context . ' must not contain style="..." attributes (CSP blocks inline styles)');
    $this->assertStringNotContainsString("style='", $html, $context . " must not contain style='...' attributes (CSP blocks inline styles)");
  }

  #[Test]
  public function cssDoesNotUseDecorativeTranslationMotion(): void
  {
    $cssRoot = $this->htmlRoot() . '/css';
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cssRoot));

    foreach ($iterator as $file) {
      if (!$file instanceof SplFileInfo || !$file->isFile()) {
        continue;
      }

      $path = $file->getPathname();
      if (!preg_match('/\.(?:css|php)$/', $path)) {
        continue;
      }

      $css = (string) file_get_contents($path);
      $relativePath = substr($path, strlen($this->projectRoot()) + 1);

      $this->assertDoesNotMatchRegularExpression(
        '/@keyframes\s+[^{]*(?:slide|shift|shake|drop|reveal|materialize)/i',
        $css,
        $relativePath . ' must not define decorative translation keyframes',
      );
      $this->assertDoesNotMatchRegularExpression(
        '/animation(?:-[a-z-]+)?\s*:\s*[^;]*(?:slide|shift|shake|drop|reveal|materialize)/i',
        $css,
        $relativePath . ' must not attach decorative translation animations',
      );
      $this->assertDoesNotMatchRegularExpression(
        '/transform\s*:\s*[^;]*translate[XY]\([^)]*(?:px|rem)/i',
        $css,
        $relativePath . ' must not use pixel/rem translation effects',
      );
    }
  }

  #[Test]
  public function compactNavStateDoesNotAdjustPageContentLayout(): void
  {
    foreach (['css/navigation/index.php', 'css/responsive/index.php'] as $relativePath) {
      $css = (string) file_get_contents($this->htmlRoot() . '/' . $relativePath);

      $this->assertDoesNotMatchRegularExpression(
        '/body\[data-nav-viewport-compact\][^{]+#main\s*\{[^}]*padding\s*:/',
        $css,
        $relativePath . ' must not change #main padding from JS-applied compact nav state',
      );
      $this->assertDoesNotMatchRegularExpression(
        '/body\[data-nav-viewport-compact\][^{]+(?:#main\s*>\s*section\.panel|\.panel|\.sites_main_container\s*>\s*section\.panel)\s*\{[^}]*(?:border-left|border-right|border-radius)\s*:/',
        $css,
        $relativePath . ' must not change panel chrome from JS-applied compact nav state',
      );
    }
  }

  #[Test]
  public function interactivePageScriptsUseCssVariablesForDynamicPositioning(): void
  {
    $scripts = [
      'js/business/workspace.js.php',
      'js/calendar/calendar.js',
      'js/signal-panel.js',
    ];

    foreach ($scripts as $relativePath) {
      $js = (string) file_get_contents($this->htmlRoot() . '/' . $relativePath);

      $this->assertDoesNotMatchRegularExpression(
        '/\.style\.(?:left|top|right|bottom|width|height|transform|display|gridTemplateColumns|position)\s*=/',
        $js,
        $relativePath . ' must not write CSS layout properties directly',
      );
    }

    $coreJs = (string) file_get_contents($this->htmlRoot() . '/js/core/index.php');
    $this->assertStringContainsString('--nav-admin-popover-left', $coreJs);
    $this->assertStringContainsString('--nav-admin-popover-top', $coreJs);
    $this->assertStringContainsString('--dashboard-panel-left', $coreJs);
    $this->assertStringContainsString('--dashboard-panel-width', $coreJs);
    $this->assertStringNotContainsString('adminPopover.style.left', $coreJs);
    $this->assertStringNotContainsString('adminPopover.style.top', $coreJs);
    $this->assertDoesNotMatchRegularExpression(
      '/dashboardEl\.style\.(?:width|height|transform)\s*=/',
      $coreJs,
      'core dashboard must use CSS variables for dynamic geometry',
    );
  }

  #[Test]
  public function adminSoc2DatagridsDoNotUseInlineGridTemplateStyles(): void
  {
    $soc2Page = (string) file_get_contents($this->htmlRoot() . '/admin/soc2/index.php');
    $soc2Css = (string) file_get_contents($this->htmlRoot() . '/css/admin/soc2/index.php');

    foreach ([
      'soc2-latest-generated-files',
      'soc2-gcs-inventory',
      'soc2-audit-artifacts',
      'soc2-admin-actions',
      'soc2-full-artifact-index',
    ] as $gridId) {
      $this->assertStringContainsString('data-grid="' . $gridId . '"', $soc2Page);
      $this->assertStringContainsString('[data-grid="' . $gridId . '"]', $soc2Css);
      $this->assertStringContainsString('--grid-template-columns:', $soc2Css);
    }

    $this->assertDoesNotMatchRegularExpression(
      '/data-grid="soc2-[^"]+"[^>]*style="/',
      $soc2Page,
      'SOC2 admin datagrids must not use inline style attributes',
    );
    $this->assertNoInlineStyles($soc2Page, 'html/admin/soc2/index.php');
  }

  #[Test]
  public function socPortalPageDoesNotUseInlineStyles(): void
  {
    $socPage = (string) file_get_contents($this->htmlRoot() . '/soc/index.php');
    $socCss = (string) file_get_contents($this->htmlRoot() . '/css/soc/index.php');

    $this->assertStringContainsString('soc-ctrl-title', $socPage);
    $this->assertStringContainsString('soc-backup-kpi__value--filename', $socPage);
    $this->assertStringContainsString('soc-anchor-intro', $socPage);
    $this->assertStringContainsString('soc-tx-pending', $socPage);
    $this->assertStringContainsString('.soc-ctrl-title', $socCss);
    $this->assertNoInlineStyles($socPage, 'html/soc/index.php');
  }

  #[Test]
  public function languageDashboardPageDoesNotUseInlineStyles(): void
  {
    $page = (string) file_get_contents($this->htmlRoot() . '/admin/language-dashboard/index.php');
    $css = (string) file_get_contents($this->htmlRoot() . '/css/admin/language-dashboard.css');

    $this->assertStringContainsString('lang-dash__section-title', $page);
    $this->assertStringContainsString('lang-dash__stats', $page);
    $this->assertStringContainsString('lang-dash__table-wrap', $page);
    $this->assertStringContainsString('.lang-dash__section-title', $css);
    $this->assertNoInlineStyles($page, 'html/admin/language-dashboard/index.php');
  }

  #[Test]
  public function deferredAdminPageLabelsUseI18nKeys(): void
  {
    $adminPages = [
      'admin/index.php' => ['ADMIN_DASHBOARD_TITLE', 'Admin Dashboard'],
      'admin/languages.php' => ['ADMIN_LANGUAGE_EDITOR', 'Language Editor'],
      'admin/tax-brackets.php' => ['ADMIN_TAX_BRACKETS_TITLE', 'Admin Tax Brackets'],
      'admin/documentation/index.php' => ['ADMIN_OPERATIONS_DOCUMENTATION', 'Operations Documentation'],
    ];

    foreach ($adminPages as $relativePath => [$key, $hardcodedLabel]) {
      $contents = (string) file_get_contents($this->htmlRoot() . '/' . $relativePath);

      $this->assertStringContainsString(
        "Strings::i18n('{$key}')",
        $contents,
        $relativePath . ' pageLabel/pageTitle should use ' . $key,
      );
      $this->assertStringNotContainsString(
        "\$pageLabel = '{$hardcodedLabel}';",
        $contents,
        $relativePath . ' must not hardcode pageLabel',
      );
      $this->assertStringNotContainsString(
        "\$pageTitle = '{$hardcodedLabel} - [PayCal]';",
        $contents,
        $relativePath . ' must not hardcode pageTitle',
      );
    }
  }

  #[Test]
  public function adminDashboardSiteColorPaletteUsesI18nKeys(): void
  {
    $controller = (string) file_get_contents($this->projectRoot() . '/html/src/Controllers/AdminPageController.php');

    $this->assertStringContainsString("batchI18n('ADMIN_SITE_COLOR_PALETTE')", $controller);
    $this->assertStringContainsString("batchI18n('ADMIN_SITE_COLOR_PALETTE_DESC')", $controller);
    $this->assertStringNotContainsString("aria-label='Site Color Palette'", $controller);
    $this->assertStringNotContainsString('<h2>Site Color Palette</h2>', $controller);
  }

  #[Test]
  public function adminPageHeadingsUseI18nKeys(): void
  {
    $adminPages = [
      'admin/metrics/index.php' => "metrics_index_i18n('ADMIN_METRICS_DASHBOARD_TITLE')",
      'admin/redis/index.php' => "Strings::i18n('ADMIN_REDIS_TIER0_RELIABILITY')",
      'admin/stripe/index.php' => "Strings::i18n('ADMIN_STRIPE_DASHBOARD')",
      'admin/argus/index.php' => "Strings::i18n('ARGUS_CONSOLE')",
      'admin/user-roles/index.php' => "Strings::i18n('ADMIN_USER_ROLES_TITLE')",
      'admin/language-dashboard/index.php' => "language_dashboard_i18n('ADMIN_LANGUAGE_DASHBOARD_TITLE')",
      'admin/business-moderation/index.php' => "Strings::i18n('ADMIN_BUSINESS_MODERATION_TITLE')",
    ];

    foreach ($adminPages as $relativePath => $expectedSnippet) {
      $contents = (string) file_get_contents($this->htmlRoot() . '/' . $relativePath);
      $this->assertStringContainsString('<h1', $contents, $relativePath . ' should expose an h1 heading');
      $this->assertStringContainsString($expectedSnippet, $contents, $relativePath . ' h1 should use i18n');
      $this->assertDoesNotMatchRegularExpression(
        '/<h1>[^<]+<\/h1>/',
        $contents,
        $relativePath . ' must not contain a hardcoded English-only h1',
      );
    }
  }

  #[Test]
  public function languageEditorTablistAriaLabelUsesI18nKey(): void
  {
    $page = (string) file_get_contents($this->htmlRoot() . '/admin/language-editor.php');

    $this->assertStringContainsString("Strings::i18n('ADMIN_LANGUAGE_EDITOR_TABLIST_ARIA')", $page);
    $this->assertStringNotContainsString("aria-label='Language selector'", $page);
  }

  #[Test]
  public function mediaPageHeadingUsesI18nKey(): void
  {
    $page = (string) file_get_contents($this->htmlRoot() . '/media/index.php');

    $this->assertStringContainsString("'MEDIA_PAGE_TITLE'", $page);
    $this->assertStringContainsString("<h1 id=\"media-page-title\">{\$mediaI18n['MEDIA_PAGE_TITLE']}</h1>", $page);
    $this->assertStringNotContainsString('<h1>Media</h1>', $page);
  }

  #[Test]
  public function mediaPageUsesHeadingsForArticleAndPlayerNames(): void
  {
    $page = (string) file_get_contents($this->htmlRoot() . '/media/index.php');

    $this->assertStringContainsString('<article class="article doc-article" aria-labelledby="media-page-title" aria-describedby="media-page-deck">', $page);
    $this->assertStringContainsString('<section class="doc-section highlight" aria-labelledby="media-featured-title">', $page);
    $this->assertStringContainsString('<div class="media-embed" role="region" aria-labelledby="media-player-title">', $page);
    $this->assertStringContainsString('<h3 id="media-player-title" class="visually_hidden">', $page);
    $this->assertStringContainsString("title=\"{\$mediaI18n['MEDIA_PAGE_VIDEO_TITLE']}\"", $page);
  }

  #[Test]
  public function helpArticleSectionsDoNotUseFaqTitleForH1(): void
  {
    $articleDirs = glob($this->htmlRoot() . '/help/*/en.php') ?: [];
    foreach ($articleDirs as $articleFile) {
      $contents = (string) file_get_contents($articleFile);
      if (!str_contains($contents, '<h1')) {
        continue;
      }
      $this->assertStringNotContainsString(
        "['FAQ_TITLE']",
        $contents,
        basename(dirname($articleFile)) . ' help article must not use FAQ_TITLE for h1',
      );
    }
  }

  #[Test]
  public function helpEnIndexUsesHelpCenterHeading(): void
  {
    $page = (string) file_get_contents($this->htmlRoot() . '/help/en/index.php');

    $this->assertStringContainsString("en_index_i18n('HELP_CENTER_HEADING')", $page);
    $this->assertStringNotContainsString("en_index_i18n('FAQ_TITLE')", $page);
  }

  #[Test]
  public function publicHelpIndexCardsExposeNamedRegionsAndSpecificReadMoreLinks(): void
  {
    $page = (string) file_get_contents($this->htmlRoot() . '/help/en.php');

    $this->assertStringContainsString('role="list" aria-label="<?php echo htmlspecialchars(help_index_i18n(\'HELP_TOC_ARIA\')', $page);
    foreach ([
      'getting-started',
      'work-site',
      'work-hours',
      'tax-brackets',
      'account-settings',
      'troubleshooting',
      'policies-legal',
      'requirements',
      'webauthn-security',
    ] as $slug) {
      $this->assertStringContainsString('role="listitem" aria-labelledby="help-card-' . $slug . '"', $page);
      $this->assertStringContainsString('id="help-card-' . $slug . '"', $page);
      $this->assertStringContainsString('aria-labelledby="help-card-' . $slug . ' help-read-more-' . $slug . '"', $page);
    }
  }

  #[Test]
  public function statusPageTablesAndPanelsHaveAccessibleNames(): void
  {
    $page = (string) file_get_contents($this->htmlRoot() . '/status/index.php');

    $this->assertStringContainsString('<section class="doc-section highlight" aria-labelledby="status-summary-heading">', $page);
    $this->assertStringContainsString('<caption class="visually_hidden">PayCal public status summary by area, status, and public detail.</caption>', $page);
    $this->assertStringContainsString('<div class="doc-panel-grid doc-panel-grid--responsive-3" role="list" aria-label="Status panels">', $page);
    $this->assertStringContainsString('role="listitem" aria-labelledby="status-platform-heading"', $page);
    $this->assertStringContainsString('role="listitem" aria-labelledby="status-data-boundary-heading"', $page);
    $this->assertStringContainsString('<section id="language-coverage" class="doc-section success" aria-labelledby="language-coverage-heading">', $page);
    $this->assertStringContainsString('<caption class="visually_hidden">Language coverage status by content layer, current state, and status.</caption>', $page);
    $this->assertStringContainsString('aria-label="Status: In progress"', $page);
    $this->assertStringContainsString('aria-label="Open Transparency Hub on paycaltech.com"', $page);
  }

  #[Test]
  public function helpImagePopoversExposeDialogNamesAndExpandedState(): void
  {
    $articleFiles = [
      'help/getting-started/en.php' => 'pay-period-example-popover',
      'help/work-site/en.php' => 'work-site-setup-popover',
      'help/work-hours/en.php' => 'work-hours-entry-surface-popover',
    ];

    foreach ($articleFiles as $relativePath => $popoverId) {
      $page = (string) file_get_contents($this->htmlRoot() . '/' . $relativePath);
      $titleId = $popoverId . '-title';

      $this->assertStringContainsString('data-help-popover-open="' . $popoverId . '"', $page);
      $this->assertStringContainsString('aria-haspopup="dialog"', $page);
      $this->assertStringContainsString('aria-controls="' . $popoverId . '"', $page);
      $this->assertStringContainsString('aria-expanded="false"', $page);
      $this->assertStringContainsString('id="' . $popoverId . '"', $page);
      $this->assertStringContainsString('role="dialog"', $page);
      $this->assertStringContainsString('aria-labelledby="' . $titleId . '"', $page);
      $this->assertStringContainsString('<h4 id="' . $titleId . '">', $page);
    }

    $helpJs = (string) file_get_contents($this->htmlRoot() . '/js/help/index.php');
    $this->assertStringContainsString('function setPopoverExpanded(popover, expanded)', $helpJs);
    $this->assertStringContainsString("button.setAttribute('aria-expanded', expanded ? 'true' : 'false');", $helpJs);
    $this->assertStringContainsString("popover.addEventListener('toggle'", $helpJs);
    $this->assertStringNotContainsString('style.display', $helpJs);
  }

  #[Test]
  public function transparencyLocalePagesUseI18nPageTitleKeysForH1(): void
  {
    $sections = [
      'error-handling' => 'TRANSPARENCY_ERROR_HANDLING_PAGE_TITLE',
      'diagnostics' => 'TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE',
      'extensions' => 'TRANSPARENCY_EXTENSIONS_PAGE_TITLE',
      'auth-hardening-2026-05' => 'TRANSPARENCY_AUTH_HARDENING_2026_05_PAGE_TITLE',
      'business-membership' => 'TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE',
      'soc2' => 'TRANSPARENCY_SOC2_PAGE_TITLE',
      'members-performance-2026-06' => 'TRANSPARENCY_MEMBERS_PERFORMANCE_2026_06_PAGE_TITLE',
    ];

    foreach ($sections as $section => $key) {
      foreach (glob($this->htmlRoot() . '/transparency/' . $section . '/*.php') as $localeFile) {
        if (basename($localeFile) === 'index.php') {
          continue;
        }
        $contents = (string) file_get_contents($localeFile);
        $this->assertStringContainsString(
          "\$i18n['{$key}']",
          $contents,
          $section . '/' . basename($localeFile) . ' h1 should use ' . $key,
        );
        $this->assertDoesNotMatchRegularExpression(
          '/<h1>[^<]+<\/h1>/',
          $contents,
          $section . '/' . basename($localeFile) . ' must not contain a hardcoded h1',
        );
      }
    }
  }

  #[Test]
  public function transparencyHeadingKeysAreTranslatedInAllLocales(): void
  {
    $locales = ['de', 'en', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'];
    $requiredKeys = [
      'TRANSPARENCY_AUTH_HARDENING_2026_05_PAGE_TITLE',
      'TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE',
      'TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE',
      'TRANSPARENCY_ERROR_HANDLING_PAGE_TITLE',
      'TRANSPARENCY_EXTENSIONS_PAGE_TITLE',
      'TRANSPARENCY_MEMBERS_PERFORMANCE_2026_06_PAGE_TITLE',
      'TRANSPARENCY_SOC2_PAGE_TITLE',
    ];

    foreach ($locales as $locale) {
      $strings = (string) file_get_contents($this->projectRoot() . '/strings/' . $locale . '.txt');

      foreach ($requiredKeys as $key) {
        $this->assertMatchesRegularExpression(
          '/^' . preg_quote($key, '/') . ' .+/m',
          $strings,
          sprintf('Missing or empty %s in %s.txt', $key, $locale),
        );
      }
    }
  }

  #[Test]
  public function g4AdminHeadingKeysAreTranslatedInAllLocales(): void
  {
    $locales = ['de', 'en', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'];
    $requiredKeys = [
      'ADMIN_BUSINESS_MODERATION_TITLE',
      'ADMIN_DASHBOARD_TITLE',
      'ADMIN_LANGUAGE_DASHBOARD_COVERAGE_SUMMARY',
      'ADMIN_LANGUAGE_DASHBOARD_DESC',
      'ADMIN_LANGUAGE_DASHBOARD_LANGUAGE_DETAILS',
      'ADMIN_LANGUAGE_DASHBOARD_TITLE',
      'ADMIN_LANGUAGE_EDITOR',
      'ADMIN_LANGUAGE_EDITOR_TABLIST_ARIA',
      'ADMIN_OPERATIONS_DOCUMENTATION',
      'ADMIN_SITE_COLOR_PALETTE',
      'ADMIN_SITE_COLOR_PALETTE_DESC',
      'ADMIN_TAX_BRACKETS_TITLE',
      'ADMIN_USER_ROLES_TITLE',
      'MEDIA_PAGE_TITLE',
      'ADMIN_METRICS_DASHBOARD_TITLE',
      'ADMIN_REDIS_TIER0_RELIABILITY',
      'ADMIN_STRIPE_DASHBOARD',
      'ARGUS_CONSOLE',
    ];

    foreach ($locales as $locale) {
      $strings = (string) file_get_contents($this->projectRoot() . '/strings/' . $locale . '.txt');

      foreach ($requiredKeys as $key) {
        $this->assertMatchesRegularExpression(
          '/^' . preg_quote($key, '/') . ' .+/m',
          $strings,
          sprintf('Missing or empty %s in %s.txt', $key, $locale),
        );
      }
    }
  }
}
