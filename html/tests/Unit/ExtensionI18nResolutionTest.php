<?php declare(strict_types=1);

namespace Tests\Unit;

use PayCal\Domain\Extensions\ExtensionRuntime;
use PayCal\Domain\Strings;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ExtensionI18nResolutionTest extends TestCase
{
  /** @var array<string, array<string, mixed>> */
  private array $originalActive;

  private string $tmpRoot = '';

  public static function setUpBeforeClass(): void
  {
    require_once __DIR__ . '/../../extensions/runtime.php';
  }

  protected function setUp(): void
  {
    parent::setUp();

    $runtimeRef = new \ReflectionProperty(ExtensionRuntime::class, 'active');
    /** @var array<string, array<string, mixed>> $active */
    $active = $runtimeRef->getValue();
    $this->originalActive = $active;

    $this->tmpRoot = sys_get_temp_dir() . '/paycal-ext-i18n-' . bin2hex(random_bytes(6));
    mkdir($this->tmpRoot, 0777, true);
  }

  protected function tearDown(): void
  {
    $runtimeRef = new \ReflectionProperty(ExtensionRuntime::class, 'active');
    $runtimeRef->setValue(null, $this->originalActive);

    $fileCacheRef = new \ReflectionProperty(Strings::class, 'fileLocaleCache');
    $fileCacheRef->setValue(null, []);
    $extensionCacheRef = new \ReflectionProperty(Strings::class, 'extensionLocaleCache');
    $extensionCacheRef->setValue(null, []);

    $this->deleteDirRecursive($this->tmpRoot);

    parent::tearDown();
  }

  #[Test]
  public function overrideExtensionI18nWinsOverBasicForSameKey(): void
  {
    $basicDir = $this->tmpRoot . '/basic-a';
    $overrideDir = $this->tmpRoot . '/override-a';
    mkdir($basicDir . '/i18n', 0777, true);
    mkdir($overrideDir . '/i18n', 0777, true);

    file_put_contents($basicDir . '/i18n/en.txt', "BILLING_TOGGLE_LABEL Enable Premium\n");
    file_put_contents($overrideDir . '/i18n/en.txt', "BILLING_TOGGLE_LABEL Upgrade with Stripe\n");

    $runtimeRef = new \ReflectionProperty(ExtensionRuntime::class, 'active');
    $runtimeRef->setValue(null, [
      'billing-provider' => [
        'id' => 'billing-provider',
        'source' => 'override',
        'directory' => $overrideDir,
        'i18n' => ['path' => 'i18n', 'default_lang' => 'en'],
      ],
      'billing-provider-basic' => [
        'id' => 'billing-provider-basic',
        'source' => 'basic',
        'directory' => $basicDir,
        'i18n' => ['path' => 'i18n', 'default_lang' => 'en'],
      ],
    ]);

    $this->assertSame('Upgrade with Stripe', Strings::i18n('BILLING_TOGGLE_LABEL', 'en'));
  }

  #[Test]
  public function extensionI18nFallsBackToDefaultLangWhenRequestedLangMissing(): void
  {
    $extensionDir = $this->tmpRoot . '/ext-default-lang';
    mkdir($extensionDir . '/i18n', 0777, true);
    file_put_contents($extensionDir . '/i18n/en.txt', "BILLING_PUBLIC_HELP Public Core toggle mode\n");

    $runtimeRef = new \ReflectionProperty(ExtensionRuntime::class, 'active');
    $runtimeRef->setValue(null, [
      'billing-provider' => [
        'id' => 'billing-provider',
        'source' => 'basic',
        'directory' => $extensionDir,
        'i18n' => ['path' => 'i18n', 'default_lang' => 'en'],
      ],
    ]);

    $this->assertSame('Public Core toggle mode', Strings::i18n('BILLING_PUBLIC_HELP', 'pt'));
  }

  #[Test]
  public function extensionMissFallsBackToCoreLocaleFileValue(): void
  {
    $extensionDir = $this->tmpRoot . '/ext-core-fallback';
    mkdir($extensionDir . '/i18n', 0777, true);
    file_put_contents($extensionDir . '/i18n/en.txt', "EXTENSION_ONLY_KEY Extension-only value\n");

    $runtimeRef = new \ReflectionProperty(ExtensionRuntime::class, 'active');
    $runtimeRef->setValue(null, [
      'billing-provider' => [
        'id' => 'billing-provider',
        'source' => 'basic',
        'directory' => $extensionDir,
        'i18n' => ['path' => 'i18n', 'default_lang' => 'en'],
      ],
    ]);

    $this->assertSame('14 Days', Strings::i18n('14_DAYS', 'en'));
  }

  private function deleteDirRecursive(string $path): void
  {
    if ($path === '' || !is_dir($path)) {
      return;
    }

    $entries = scandir($path);
    if (!is_array($entries)) {
      return;
    }

    foreach ($entries as $entry) {
      if ($entry === '.' || $entry === '..') {
        continue;
      }

      $target = $path . '/' . $entry;
      if (is_dir($target)) {
        $this->deleteDirRecursive($target);
      } else {
        @unlink($target);
      }
    }

    @rmdir($path);
  }
}