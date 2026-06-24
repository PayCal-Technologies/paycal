<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('contract')]
final class AppLayoutWidthContractTest extends TestCase
{
  #[Test]
  public function appPageShellsUseSharedFullWidthContentToken(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $commonCss = (string) file_get_contents($projectRoot . '/html/css/common/index.php');
    $settingsCss = (string) file_get_contents($projectRoot . '/html/css/settings/index.php');
    $businessesCss = (string) file_get_contents($projectRoot . '/html/css/business/index.php');
    $sitesCss = (string) file_get_contents($projectRoot . '/html/css/sites/index.php');
    $profileCss = (string) file_get_contents($projectRoot . '/html/css/profile/index.php');
    $reportsCss = (string) file_get_contents($projectRoot . '/html/css/reports/index.php');

    $this->assertStringContainsString('--app-content-width:                   100%;', $commonCss);
    $this->assertStringContainsString('max-width: var(--app-content-width, 100%);', $settingsCss);
    $this->assertStringContainsString('width: var(--app-content-width, 100%);', $settingsCss);
    $this->assertStringContainsString('max-width: var(--app-content-width, 100%);', $settingsCss);
    $this->assertStringContainsString('--businesses-content-width: 100%;', $businessesCss);
    $this->assertStringContainsString('width: var(--app-content-width, 100%);', $sitesCss);
    $this->assertStringContainsString('width: var(--app-content-width, 100%);', $profileCss);
    $this->assertStringContainsString('max-width: var(--app-content-width, 100%);', $reportsCss);

    foreach ([$settingsCss, $businessesCss, $sitesCss, $profileCss, $reportsCss] as $css) {
      $this->assertStringNotContainsString('min(80vw, 1240px)', $css);
      $this->assertStringNotContainsString('min(80vw, 1200px)', $css);
      $this->assertStringNotContainsString('max-width: 820px', $css);
    }
  }

  #[Group('private-moat')]
  #[Test]
  public function appSubpagePanelHeadingsUseCompactOperationalScale(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $settingsCss = (string) file_get_contents($projectRoot . '/html/css/settings/index.php');
    $businessesCss = (string) file_get_contents($projectRoot . '/html/css/businesses/index.php');

    $this->assertStringContainsString('.settings_page_content h2.settings_card_title', $settingsCss);
    $this->assertStringContainsString('font-size: clamp(1.05rem, 1.8vw, 1.2rem);', $settingsCss);
    $this->assertStringContainsString('.businesses_section_header h2,', $businessesCss);
    $this->assertStringContainsString('.businesses_section_header h3', $businessesCss);
    $this->assertStringContainsString('font-size: clamp(1.05rem, 1.8vw, 1.2rem);', $businessesCss);
    $this->assertStringContainsString('letter-spacing: 0.04rem;', $businessesCss);
  }

  #[Test]
  public function commonStylesheetDefinesPrinterFriendlyGlobalPrintMode(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $commonCss = (string) file_get_contents($projectRoot . '/html/css/common/index.php');

    $this->assertStringContainsString('@media print {', $commonCss);
    $this->assertStringContainsString('@page {', $commonCss);
    $this->assertStringContainsString('--panel-bg: #ffffff;', $commonCss);
    $this->assertStringContainsString('--text-color: #000000;', $commonCss);
    $this->assertStringContainsString('--text-muted: #111111;', $commonCss);
    $this->assertStringContainsString('--color-link: #000000;', $commonCss);
    $this->assertStringContainsString('background: #ffffff !important;', $commonCss);
    $this->assertStringContainsString('color: #000000 !important;', $commonCss);
    $this->assertStringContainsString('print-color-adjust: economy;', $commonCss);
    $this->assertStringContainsString('-webkit-print-color-adjust: economy;', $commonCss);
    $this->assertStringContainsString('#page_header,', $commonCss);
    $this->assertStringContainsString('#page_footer,', $commonCss);
    $this->assertStringContainsString('background-image: none !important;', $commonCss);
    $this->assertStringContainsString('.earnings-graph-container svg path[fill^="url"]', $commonCss);
    $this->assertStringContainsString('.ytd_line--gross,', $commonCss);
    $this->assertStringContainsString('.ytd_bar--reg,', $commonCss);
    $this->assertStringContainsString('.earnings_piegraphs_slice', $commonCss);
    $this->assertStringContainsString('stroke-width: 2.8 !important;', $commonCss);
    $this->assertStringContainsString('html:not([data-print-mode="color"]) .earnings-graph-container svg', $commonCss);
    $this->assertStringContainsString('html[data-print-mode="grayscale"] .ytd_line--gross', $commonCss);
    $this->assertStringContainsString('html[data-print-mode="color"] {', $commonCss);
    $this->assertStringContainsString('-webkit-print-color-adjust: exact;', $commonCss);
  }

  #[Test]
  public function commonStylesheetEnablesDocumentTransitionsButHonorsReducedMotion(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $commonCss = (string) file_get_contents($projectRoot . '/html/css/common/index.php');

    $this->assertStringContainsString('@view-transition {', $commonCss);
    $this->assertStringContainsString('navigation: auto;', $commonCss);
    $this->assertStringContainsString('::view-transition-old(root),', $commonCss);
    $this->assertStringContainsString('::view-transition-new(root)', $commonCss);
    $this->assertStringContainsString('html[data-a11y-reduced-motion="on"]::view-transition-old(root),', $commonCss);
    $this->assertStringContainsString('html[data-a11y-reduced-motion="on"]::view-transition-new(root)', $commonCss);
    $this->assertStringContainsString('html[data-a11y-reduced-motion="system"]::view-transition-old(root),', $commonCss);
    $this->assertStringContainsString('html[data-a11y-reduced-motion="system"]::view-transition-new(root)', $commonCss);
  }

  #[Test]
  public function firstPartyJavascriptAvoidsBeforeUnloadForBfcacheEligibility(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $scriptFiles = [
      $projectRoot . '/html/js/core/index.php',
      $projectRoot . '/html/js/calendar/calendar.js',
      $projectRoot . '/html/js/earnings/index.php',
      $projectRoot . '/html/js/phantomwing/index.php',
    ];

    foreach ($scriptFiles as $scriptFile) {
      $script = (string) file_get_contents($scriptFile);

      $this->assertStringNotContainsString("addEventListener('beforeunload'", $script, $scriptFile);
      $this->assertStringNotContainsString('addEventListener("beforeunload"', $script, $scriptFile);
    }

    $core = (string) file_get_contents($projectRoot . '/html/js/core/index.php');
    $this->assertStringContainsString("window.addEventListener('pageshow'", $core);
    $this->assertStringContainsString('paycal:bfcache-restore', $core);
  }

  #[Test]
  public function coreJavascriptNormalizesThrownMessagesForUserFacingAsyncFailures(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $core = (string) file_get_contents($projectRoot . '/html/js/core/index.php');
    $earnings = (string) file_get_contents($projectRoot . '/html/js/earnings/index.php');
    $memberReports = (string) file_get_contents($projectRoot . '/html/js/earnings/member-reports-view.js');
    $siteEditor = (string) file_get_contents($projectRoot . '/html/js/sites/site-editor-core.php');

    $this->assertStringContainsString('function resolveThrownMessage(error, fallbackMessage', $core);
    $this->assertStringContainsString('ECONNREFUSED', $core);
    $this->assertStringContainsString('Failed to fetch', $core);
    $this->assertStringContainsString('resolveThrownMessage,', $core);
    $this->assertStringContainsString('PC.resolveThrownMessage(error', $earnings);
    $this->assertStringContainsString('options.resolveThrownMessage', $memberReports);
    $this->assertStringContainsString('PC.resolveThrownMessage(error', $siteEditor);
  }

  #[Test]
  public function reportsPagesLoadPrintModeDialogForPageAndPanelPdfActions(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $personalReportsPage = (string) file_get_contents($projectRoot . '/html/reports/index.php');
    $businessReportsPage = (string) file_get_contents($projectRoot . '/html/business/reports/index.php');
    $renderPhp = (string) file_get_contents($projectRoot . '/html/src/Domain/Render.php');
    $reportsPrintJs = (string) file_get_contents($projectRoot . '/html/js/reports-print/index.php');
    $earningsJs = (string) file_get_contents($projectRoot . '/html/js/earnings/index.php');
    $exportJs = (string) file_get_contents($projectRoot . '/html/js/earnings/earnings-export.js');
    $earningsController = (string) file_get_contents($projectRoot . '/html/src/Controllers/EarningsController.php');
    $earningsPdf = (string) file_get_contents($projectRoot . '/html/src/Domain/EarningsPdf.php');
    $earningsCss = (string) file_get_contents($projectRoot . '/html/css/earnings/index.php');

    $this->assertStringContainsString("Render::jsScript('reports-print')", $personalReportsPage);
    $this->assertStringContainsString("Render::jsScript('reports-print')", $businessReportsPage);
    $this->assertStringContainsString("'reports-print'", $renderPhp);
    $this->assertStringContainsString("'/reports/'", $reportsPrintJs);
    $this->assertStringContainsString("'/business/reports/'", $reportsPrintJs);
    $this->assertStringContainsString("new Set(['bw', 'grayscale', 'color'])", $reportsPrintJs);
    $this->assertStringContainsString('data-export-scope][data-export-format="pdf"', $reportsPrintJs);
    $this->assertStringContainsString('data-group-export-format="pdf"', $reportsPrintJs);
    $this->assertStringContainsString('data-team-export-format="pdf"', $reportsPrintJs);
    $this->assertStringContainsString('BUSINESS_REPORT_PDF_SELECTOR', $reportsPrintJs);
    $this->assertStringContainsString('printAfterStyleFlush', $reportsPrintJs);
    $this->assertStringContainsString('paycal:reports-print-mode-applied', $reportsPrintJs);
    $this->assertStringContainsString('data-reports-print-bypass', $reportsPrintJs);
    $this->assertStringContainsString('stopImmediatePropagation', $reportsPrintJs);
    $this->assertStringContainsString('beforeprint', $reportsPrintJs);
    $this->assertStringContainsString('document.documentElement.dataset.printMode', $earningsJs);
    $this->assertStringContainsString('print_mode: normalizedPrintMode', $exportJs);
    $this->assertStringContainsString("\$postData['print_mode']", $earningsController);
    $this->assertStringContainsString('EarningsPdf::generate($scope, $report, $printMode)', $earningsController);
    $this->assertStringContainsString('normalizePrintMode', $earningsPdf);
    $this->assertStringContainsString('tableHeaderFill', $earningsPdf);
    $this->assertStringContainsString('.reports_print_toolbar', $earningsCss);
    $this->assertStringContainsString('.reports_print_dialog', $earningsCss);
    $this->assertStringContainsString('.reports_print_mode:has(input:checked)', $earningsCss);
    $this->assertStringContainsString('html:not([data-print-mode="color"]) .et_exec_snapshot_value--risk', $earningsCss);
    $this->assertStringContainsString('html[data-print-mode="grayscale"] .et_budget_bar_fill--ok', $earningsCss);
    $this->assertStringContainsString('html[data-print-mode="bw"] .et_comp_bar_rect--reg', $earningsCss);
  }
}
