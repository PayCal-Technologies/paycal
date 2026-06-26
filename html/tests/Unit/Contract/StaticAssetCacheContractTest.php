<?php declare(strict_types=1);

use PayCal\Domain\HttpCache;
use PayCal\Domain\Render;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('contract')]
final class StaticAssetCacheContractTest extends TestCase
{
  #[Test]
  public function earningsLazyLoadDefersDailyYearFetch(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $earningsJs = (string) file_get_contents($projectRoot . '/html/js/earnings/index.php');

    $this->assertStringContainsString('scheduleDailyForYear', $earningsJs);
    $this->assertStringContainsString('loadYearContentProgressive', $earningsJs);
    $this->assertStringContainsString('IntersectionObserver', $earningsJs);
  }

  #[Test]
  public function signalPanelDefersFetchPatchUntilOpen(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $signalPanel = (string) file_get_contents($projectRoot . '/html/js/signal-panel.js');

    $this->assertStringContainsString('patchFetchForStatuses();', $signalPanel);
    $this->assertStringNotContainsString('patchFetchForStatuses();\n    bindErrorCapture();', $signalPanel);
  }

  #[Test]
  public function footerDoesNotReloadCoreOrEncryptionModules(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $footer = (string) file_get_contents($projectRoot . '/html/footer.php');

    $this->assertStringNotContainsString("Render::jsScript('-')", $footer);
    $this->assertStringNotContainsString("Render::jsScript('encryption')", $footer);
    $this->assertStringContainsString("Render::jsScript('plaintext-work-capture')", $footer);
    $this->assertStringContainsString("(\$currentPage ?? '') === 'PAGE_INDEX'", $footer);
  }

  #[Test]
  public function earningsEntrypointUsesVersionedModuleUrls(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $earningsJs = (string) file_get_contents($projectRoot . '/html/js/earnings/index.php');

    $this->assertStringContainsString("Render::jsModuleURL()", $earningsJs);
    $this->assertStringContainsString("Render::jsModuleURL('phantomwing')", $earningsJs);
    $this->assertStringContainsString("Render::jsStaticURL('js/vendor/tweetnacl.js')", $earningsJs);
    $this->assertStringNotContainsString("import PC from '/js/';", $earningsJs);
    $this->assertStringNotContainsString("import PW from '/js/phantomwing/';", $earningsJs);
  }

  #[Test]
  public function taxRateManifestUsesPrivateEtagCaching(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $manifest = (string) file_get_contents($projectRoot . '/html/js/earnings/tax-rate-manifest.php');

    $this->assertStringContainsString('HttpCache::sendPrivateWithFileEtag($manifestPath);', $manifest);
  }

  #[Test]
  public function renderJsModuleUrlHelperExists(): void
  {
    $this->assertTrue(method_exists(Render::class, 'jsModuleURL'));
    $this->assertTrue(method_exists(Render::class, 'jsStaticURL'));
  }

  #[Test]
  public function nginxStaticAssetCacheDocumentsExpectedTtls(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $nginxConf = (string) file_get_contents($projectRoot . '/docs/nginx/static-asset-cache.conf');

    $this->assertStringContainsString('^/fonts/.+\\.(ttf|woff2?|eot|svg)$', $nginxConf);
    $this->assertStringContainsString('expires 90d', $nginxConf);
    $this->assertStringContainsString('max-age=7776000, immutable', $nginxConf);
    $this->assertSame(HttpCache::FONT_TTL_SECONDS, 7776000);

    $this->assertStringContainsString('^/img/.+\\.(png|jpe?g|gif|webp|svg|ico)$', $nginxConf);
    $this->assertStringContainsString('max-age=31536000, immutable', $nginxConf);

    $this->assertStringContainsString('^/js/.+\\.js$', $nginxConf);
    $this->assertStringContainsString('expires 1h', $nginxConf);
    $this->assertStringContainsString('max-age=3600', $nginxConf);
    $this->assertSame(HttpCache::STATIC_JS_TTL_SECONDS, 3600);
    $this->assertStringNotContainsString('max-age=31536000, immutable" always;
    try_files $uri =404;
}

# Notes:', $nginxConf);

    $this->assertStringContainsString('must remain no-store', $nginxConf);
  }

  #[Test]
  public function phpJsModuleEndpointsDoNotUsePublicVersionedCache(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $entrypoints = [
      '/html/js/core/index.php',
      '/html/js/earnings/index.php',
      '/html/js/encryption/index.php',
    ];

    foreach ($entrypoints as $relativePath) {
      $source = (string) file_get_contents($projectRoot . $relativePath);
      $this->assertStringNotContainsString(
        'HttpCache::sendPublicVersionedImmutable',
        $source,
        $relativePath . ' must stay no-store (user-specific bundle)',
      );
    }
  }
}
