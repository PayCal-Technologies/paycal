<?php declare(strict_types=1);

use PayCal\Domain\Config\Environment;
use PayCal\Domain\PageHeadRenderer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('contract')]
final class WebVitalsDiagnosticsContractTest extends TestCase
{
  #[Test]
  public function renderScriptsOmitsDiagnosticsByDefault(): void
  {
    $html = PageHeadRenderer::renderScripts([
      'cspNonce' => 'test-nonce',
      'jsonLdDocument' => '{}',
      'isDocPdfView' => false,
      'isAuthenticated' => true,
      'loadPhantomWing' => false,
      'loadWebVitalsDiagnostics' => false,
    ]);

    $this->assertStringNotContainsString('web-vitals-diagnostics', $html);
    $this->assertStringNotContainsString('web-vitals-attribution', $html);
  }

  #[Test]
  public function renderScriptsIncludesDiagnosticsOnlyWhenExplicitlyEnabled(): void
  {
    $html = PageHeadRenderer::renderScripts([
      'cspNonce' => 'test-nonce',
      'jsonLdDocument' => '{}',
      'isDocPdfView' => false,
      'isAuthenticated' => true,
      'loadPhantomWing' => false,
      'loadWebVitalsDiagnostics' => true,
    ]);

    $this->assertStringContainsString('js/dev/web-vitals-diagnostics/', $html);
    $this->assertStringContainsString('type="module"', $html);
  }

  #[Test]
  public function headerGatesDiagnosticsToDevAndMacEnvironments(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $header = (string) file_get_contents($projectRoot . '/header.php');

    $this->assertStringContainsString('Environment::isWebVitalsDiagnosticsEnabled()', $header);
    $this->assertStringContainsString("'loadWebVitalsDiagnostics' => \$loadWebVitalsDiagnostics", $header);
    $this->assertStringNotContainsString("appEnv() === 'prod'", $header);
  }

  #[Test]
  public function environmentHelperNeverEnablesDiagnosticsInProduction(): void
  {
    $source = (string) file_get_contents(
      dirname(__DIR__, 3) . '/src/Domain/Config/Environment.php'
    );

    $this->assertStringContainsString("in_array(self::\$appEnv, ['dev', 'mac'], true)", $source);
    $this->assertMatchesRegularExpression(
      '/function isWebVitalsDiagnosticsEnabled\(\): bool\s*\{[^}]*in_array\(self::\$appEnv, \[\'dev\', \'mac\'\], true\)/s',
      $source
    );
  }

  #[Test]
  public function devEndpointsAbortOutsideDevMac(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $files = [
      $projectRoot . '/js/dev/web-vitals-diagnostics/index.php',
      $projectRoot . '/js/dev/web-vitals-attribution/index.php',
    ];

    foreach ($files as $file) {
      $source = (string) file_get_contents($file);
      $this->assertStringContainsString('isWebVitalsDiagnosticsEnabled()', $source, $file);
      $this->assertStringContainsString('http_response_code(404)', $source, $file);
    }
  }

  #[Test]
  public function resourceHintsPreloadPrimaryWoff2InAllEnvironments(): void
  {
    $without = PageHeadRenderer::renderResourceHints([]);
    $withDiagnostics = PageHeadRenderer::renderResourceHints(['loadWebVitalsDiagnostics' => true]);

    $this->assertStringContainsString('rel="preload"', $without);
    $this->assertStringContainsString('open-dyslexic-400.woff2', $without);
    $this->assertStringContainsString('type="font/woff2"', $without);
    $this->assertSame($without, $withDiagnostics);
  }
}
