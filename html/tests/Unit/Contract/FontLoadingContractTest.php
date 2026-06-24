<?php declare(strict_types=1);

use PayCal\Domain\PageHeadRenderer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('contract')]
final class FontLoadingContractTest extends TestCase
{
  #[Test]
  public function publicPagesResolveToStableStylesheetFamilies(): void
  {
    $expectedPageFiles = [
      'PAGE_AUTH' => 'auth',
      'PAGE_SIGNIN' => 'auth',
      'PAGE_REGISTER' => 'auth',
      'PAGE_CONTACT' => 'contact',
      'PAGE_ABOUT' => 'content',
      'PAGE_HELP' => 'help',
      'PAGE_TRANSPARENCY' => 'transparency',
      'PAGE_POLICIES' => 'content',
      'PAGE_BLOG' => 'content',
      'PAGE_MEDIA' => 'content',
      'PAGE_PRICING' => 'pricing',
      'PAGE_STATUS' => 'transparency',
    ];

    foreach ($expectedPageFiles as $page => $pageFile) {
      $this->assertSame($pageFile, PageHeadRenderer::pageFileFor($page), $page);
    }
  }

  #[Test]
  public function sharedFontDefaultsUseNativeStacks(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $commonCss = (string) file_get_contents($projectRoot . '/html/css/common/index.php');
    $systemConfig = (string) file_get_contents($projectRoot . '/html/src/Domain/Config/SystemConfig.php');

    $this->assertStringContainsString('system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', $commonCss);
    $this->assertStringContainsString('Georgia, "Times New Roman", Times, serif', $commonCss);
    $this->assertStringContainsString('ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace', $commonCss);
    $this->assertStringContainsString('system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', $systemConfig);
    $this->assertStringContainsString('Georgia, "Times New Roman", Times, serif', $systemConfig);
    $this->assertStringContainsString('ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace', $systemConfig);
  }

  #[Test]
  public function fontEndpointsNeverForceLateSwaps(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $fontCssFiles = [
      $projectRoot . '/html/css/common/index.php',
      $projectRoot . '/html/css/fonts/index.php',
      $projectRoot . '/html/fonts/stylesheet.css',
    ];

    foreach ($fontCssFiles as $fontCssFile) {
      $css = (string) file_get_contents($fontCssFile);

      $this->assertStringNotContainsString('font-display: swap', $css, $fontCssFile);
      $this->assertStringNotContainsString('font-display: block', $css, $fontCssFile);
      $this->assertStringContainsString('font-display: optional', $css, $fontCssFile);
    }
  }
}
