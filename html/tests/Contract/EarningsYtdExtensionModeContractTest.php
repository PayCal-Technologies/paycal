<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: Earnings YTD extension compare/mode seam remains wired
 * between controller and frontend async loader.
 */
#[Group('contract')]
final class EarningsYtdExtensionModeContractTest extends TestCase
{
  public function testControllerKeepsCompareAndModeRequestParsing(): void
  {
    $controller = $this->readProjectFile('src/Controllers/EarningsController.php');

    $this->assertStringContainsString("InputSanitizer::getString('ext_compare') === 'earnings-ytd'", $controller);
    $this->assertStringContainsString("InputSanitizer::getString('ext_mode') ?? 'auto'", $controller);
    $this->assertStringContainsString('in_array(strtolower($requestedModeRaw), [\'auto\', \'basic\', \'override\'], true)', $controller);
  }

  public function testControllerKeepsCompareRenderAndCacheBypassLogic(): void
  {
    $controller = $this->readProjectFile('src/Controllers/EarningsController.php');

    $this->assertStringContainsString('$cacheAllowed = !$compareRequested && $requestedMode === \'auto\';', $controller);
    $this->assertStringContainsString('? Earnings::getInstance()->renderYearToDateSummaryCompare($year)', $controller);
    $this->assertStringContainsString(': Earnings::getInstance()->renderYearToDateSummary($year, $requestedMode);', $controller);
  }

  public function testFrontendYtdFetchKeepsExtCompareAndModePassthrough(): void
  {
    $frontend = $this->readProjectFile('js/earnings/index.php');

    $this->assertStringContainsString("if (section === 'ytd') {", $frontend);
    $this->assertStringContainsString("const extCompare = String(searchParams.get('ext_compare') || '').trim().toLowerCase();", $frontend);
    $this->assertStringContainsString("const extMode = String(searchParams.get('ext_mode') || '').trim().toLowerCase();", $frontend);
    $this->assertStringContainsString("const allowedModes = ['auto', 'basic', 'override'];", $frontend);
    $this->assertStringContainsString('endpoint += `?${queryString}`;', $frontend);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
