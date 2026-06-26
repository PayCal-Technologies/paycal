<?php declare(strict_types=1);

use PayCal\Domain\Config\Environment;
use PayCal\Domain\Javascript;
use PayCal\Domain\JavascriptSourceMap;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('contract')]
final class PhpJsSourceMapContractTest extends TestCase
{
  #[Test]
  public function environmentHelperNeverEnablesSourceMapsInProduction(): void
  {
    $source = (string) file_get_contents(
      dirname(__DIR__, 3) . '/src/Domain/Config/Environment.php'
    );

    $this->assertStringContainsString("function isPhpJsSourceMapsEnabled(): bool", $source);
    $this->assertMatchesRegularExpression(
      '/function isPhpJsSourceMapsEnabled\(\): bool\s*\{[^}]*in_array\(self::\$appEnv, \[\'dev\', \'mac\'\], true\)/s',
      $source
    );
  }

  #[Test]
  public function businessBundleUsesSegmentTracingHelpers(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $businessJs = (string) file_get_contents($projectRoot . '/js/business/index.php');

    $this->assertStringContainsString("Javascript::beginSourceMapBundle('business')", $businessJs);
    $this->assertStringContainsString('Javascript::emitJsSegment(', $businessJs);
    $this->assertStringContainsString('Javascript::finishSourceMapBundle()', $businessJs);
    $this->assertStringNotContainsString("require __DIR__ . '/core/", $businessJs);
  }

  #[Test]
  public function sourceMapBuilderIsGatedOnEnvironmentHelper(): void
  {
    $source = (string) file_get_contents(
      dirname(__DIR__, 3) . '/src/Domain/JavascriptSourceMap.php'
    );

    $this->assertStringContainsString('Environment::isPhpJsSourceMapsEnabled()', $source);
    $this->assertStringContainsString('data:application/json;charset=utf-8;base64,', $source);
  }

  #[Test]
  public function moduleContentTypeRemainsNoStoreWhenSourceMapsEnabled(): void
  {
    $javascript = (string) file_get_contents(
      dirname(__DIR__, 3) . '/src/Domain/Javascript.php'
    );

    $this->assertStringContainsString('HttpCache::sendNoStore()', $javascript);
    $this->assertStringContainsString('renderModuleContentType', $javascript);
  }
}
