<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Extensions\ExtensionRuntime;
use PayCal\Domain\Extensions\HookBus;

/**
 * Runtime contract tests for extension selection, capability semantics,
 * and hook dispatch behavior.
 */
#[Group('unit')]
final class ExtensionRuntimeContractsTest extends TestCase
{
  private bool $originalBooted;

  /** @var array<string, array<string, mixed>> */
  private array $originalDiscovered;

  /** @var array<string, array<string, mixed>> */
  private array $originalActive;

  /** @var array<string, array<int, array{priority:int, callback:callable, source:string}>> */
  private array $originalListeners;

  public static function setUpBeforeClass(): void
  {
    require_once __DIR__ . '/../../extensions/runtime.php';
  }

  protected function setUp(): void
  {
    parent::setUp();

    $this->originalBooted = $this->readRuntimeStatic('booted');
    $this->originalDiscovered = $this->readRuntimeStatic('discovered');
    $this->originalActive = $this->readRuntimeStatic('active');
    $this->originalListeners = $this->readHookBusListeners();

    $this->writeHookBusListeners([]);
  }

  protected function tearDown(): void
  {
    $this->writeRuntimeStatic('booted', $this->originalBooted);
    $this->writeRuntimeStatic('discovered', $this->originalDiscovered);
    $this->writeRuntimeStatic('active', $this->originalActive);
    $this->writeHookBusListeners($this->originalListeners);

    parent::tearDown();
  }

  #[Test]
  public function selectActiveExtensionsPrefersOverrideAndRespectsEnabledFlag(): void
  {
    $selector = new \ReflectionMethod(ExtensionRuntime::class, 'selectActiveExtensions');

    $discovered = [
      'basic:feature-a' => [
        'id' => 'feature-a',
        'source' => 'basic',
        'enabled' => true,
      ],
      'override:feature-a' => [
        'id' => 'feature-a',
        'source' => 'override',
        'enabled' => true,
      ],
      'basic:feature-b' => [
        'id' => 'feature-b',
        'source' => 'basic',
        'enabled' => true,
      ],
      'override:feature-c' => [
        'id' => 'feature-c',
        'source' => 'override',
        'enabled' => false,
      ],
      'basic:feature-c' => [
        'id' => 'feature-c',
        'source' => 'basic',
        'enabled' => true,
      ],
    ];

    /** @var array<string, array<string, mixed>> $selected */
    $selected = $selector->invoke(null, $discovered);

    $this->assertSame('override', $selected['feature-a']['source']);
    $this->assertSame('basic', $selected['feature-b']['source']);
    $this->assertArrayNotHasKey('feature-c', $selected, 'Disabled override suppresses matching basic extension by design.');
  }

  #[Test]
  public function capabilityEnabledAndValueFollowActiveCapabilityManifest(): void
  {
    $active = [
      'admin-surface' => [
        'id' => 'admin-surface',
        'version' => '1.0.0-private',
        'source' => 'override',
        'capabilities' => [
          'admin.surface.enabled' => 'enabled',
          'admin.feature.disabled' => 'off',
          'admin.nav.links' => [
            ['href' => '/admin/', 'label_key' => 'ADMIN', 'icon' => 'admin', 'match_prefix' => '/admin'],
          ],
        ],
      ],
    ];

    $this->writeRuntimeStatic('active', $active);

    $this->assertTrue(ExtensionRuntime::capabilityEnabled('admin.surface.enabled', false));
    $this->assertFalse(ExtensionRuntime::capabilityEnabled('admin.feature.disabled', true));
    $this->assertTrue(ExtensionRuntime::capabilityEnabled('missing.capability', true));

    $links = ExtensionRuntime::capabilityValue('admin.nav.links', []);
    $this->assertIsArray($links);
    $this->assertSame('/admin/', $links[0]['href'] ?? null);
    $this->assertSame('fallback', ExtensionRuntime::capabilityValue('missing.value', 'fallback'));
  }

  #[Test]
  public function capabilityEntriesReturnsEmptyForBlankName(): void
  {
    $this->writeRuntimeStatic('active', [
      'ext-a' => [
        'id' => 'ext-a',
        'version' => '1.0.0',
        'source' => 'basic',
        'capabilities' => ['my.cap' => true],
      ],
    ]);

    $this->assertSame([], ExtensionRuntime::capabilityEntries(''));
    $this->assertSame([], ExtensionRuntime::capabilityEntries('   '));
  }

  #[Test]
  public function capabilityValueFirstExtensionWinsWhenMultipleContribute(): void
  {
    $this->writeRuntimeStatic('active', [
      'ext-a' => [
        'id' => 'ext-a',
        'version' => '1.0.0',
        'source' => 'basic',
        'capabilities' => ['shared.setting' => 'from-ext-a'],
      ],
      'ext-b' => [
        'id' => 'ext-b',
        'version' => '1.0.0',
        'source' => 'basic',
        'capabilities' => ['shared.setting' => 'from-ext-b'],
      ],
    ]);

    $entries = ExtensionRuntime::capabilityEntries('shared.setting');
    $this->assertCount(2, $entries, 'Both extensions should contribute entries.');
    $this->assertSame('from-ext-a', $entries[0]['value']);
    $this->assertSame('from-ext-b', $entries[1]['value']);

    $this->assertSame('from-ext-a', ExtensionRuntime::capabilityValue('shared.setting', null),
      'capabilityValue returns the first entry value when multiple extensions contribute.'
    );
  }

  #[Test]
  public function hasCapabilityReturnsTrueOnlyWhenPresentInActiveManifests(): void
  {
    $this->writeRuntimeStatic('active', [
      'ext-a' => [
        'id' => 'ext-a',
        'version' => '1.0.0',
        'source' => 'basic',
        'capabilities' => ['present.cap' => true],
      ],
    ]);

    $this->assertTrue(ExtensionRuntime::hasCapability('present.cap'));
    $this->assertFalse(ExtensionRuntime::hasCapability('absent.cap'));
    $this->assertFalse(ExtensionRuntime::hasCapability(''));
  }

  #[Test]
  public function hookBusDispatchOrdersListenersAndContinuesAfterFailures(): void
  {
    $executionOrder = [];

    HookBus::register('contract.hook', function () use (&$executionOrder): string {
      $executionOrder[] = 'first';
      return 'first-result';
    }, 10, 'first-source');

    HookBus::register('contract.hook', function () use (&$executionOrder): string {
      $executionOrder[] = 'middle-throws';
      throw new \RuntimeException('boom');
    }, 20, 'middle-source');

    HookBus::register('contract.hook', function () use (&$executionOrder): string {
      $executionOrder[] = 'last';
      return 'last-result';
    }, 30, 'last-source');

    $results = HookBus::dispatch('contract.hook', []);

    $this->assertSame(['first', 'middle-throws', 'last'], $executionOrder);
    $this->assertSame(['first-result', 'last-result'], $results);

    $summary = HookBus::listenersSummary();
    $this->assertArrayHasKey('contract.hook', $summary);
    $this->assertCount(3, $summary['contract.hook']);
    $this->assertSame(10, $summary['contract.hook'][0]['priority']);
    $this->assertSame('first-source', $summary['contract.hook'][0]['source']);
  }

  #[Test]
  public function activeManifestsReturnsIndexedValuesOfActiveExtensions(): void
  {
    $this->writeRuntimeStatic('active', [
      'ext-one' => ['id' => 'ext-one', 'version' => '1.0.0', 'source' => 'basic'],
      'ext-two' => ['id' => 'ext-two', 'version' => '2.0.0', 'source' => 'override'],
    ]);

    $manifests = ExtensionRuntime::activeManifests();

    $this->assertCount(2, $manifests);
    $this->assertSame('ext-one', $manifests[0]['id']);
    $this->assertSame('ext-two', $manifests[1]['id']);
  }

  #[Test]
  public function discoveredManifestsReturnsIndexedValuesOfDiscoveredExtensions(): void
  {
    $this->writeRuntimeStatic('discovered', [
      'basic:feature-x' => ['id' => 'feature-x', 'source' => 'basic', 'enabled' => true],
    ]);

    $discovered = ExtensionRuntime::discoveredManifests();

    $this->assertCount(1, $discovered);
    $this->assertSame('feature-x', $discovered[0]['id']);
    $this->assertSame('basic', $discovered[0]['source']);
  }

  #[Test]
  public function capabilityManifestIndexesCapabilitiesByNameWithExtensionMetadata(): void
  {
    $this->writeRuntimeStatic('active', [
      'ext-a' => [
        'id' => 'ext-a',
        'version' => '1.0.0',
        'source' => 'basic',
        'capabilities' => [
          'feature.flag' => true,
          'shared.cap' => 'a-value',
        ],
      ],
      'ext-b' => [
        'id' => 'ext-b',
        'version' => '2.0.0',
        'source' => 'override',
        'capabilities' => [
          'shared.cap' => 'b-value',
        ],
      ],
    ]);

    $manifest = ExtensionRuntime::capabilityManifest();

    $this->assertArrayHasKey('feature.flag', $manifest);
    $this->assertArrayHasKey('shared.cap', $manifest);

    $this->assertCount(1, $manifest['feature.flag']);
    $this->assertSame('ext-a', $manifest['feature.flag'][0]['extension_id']);
    $this->assertSame('basic', $manifest['feature.flag'][0]['source']);
    $this->assertTrue($manifest['feature.flag'][0]['value']);

    $this->assertCount(2, $manifest['shared.cap']);
    $this->assertSame('a-value', $manifest['shared.cap'][0]['value']);
    $this->assertSame('b-value', $manifest['shared.cap'][1]['value']);
  }

  #[Test]
  public function loadManifestNormalizesI18nMetadata(): void
  {
    $tmpRoot = sys_get_temp_dir() . '/paycal-runtime-manifest-' . bin2hex(random_bytes(6));
    $extensionDir = $tmpRoot . '/ext-i18n';
    mkdir($extensionDir, 0777, true);

    $manifestPhp = <<<'PHP'
<?php
return [
  'id' => 'ext-i18n',
  'name' => 'I18n Extension',
  'enabled' => true,
  'i18n' => [
    'path' => 'locale',
    'default_lang' => 'PT',
  ],
];
PHP;
    file_put_contents($extensionDir . '/manifest.php', $manifestPhp);

    $loader = new \ReflectionMethod(ExtensionRuntime::class, 'loadManifest');
    /** @var array<string, mixed>|null $manifest */
    $manifest = $loader->invoke(null, $extensionDir, 'basic');

    $this->assertIsArray($manifest);
    $this->assertSame('ext-i18n', $manifest['id'] ?? null);
    $this->assertSame('locale', $manifest['i18n']['path'] ?? null);
    $this->assertSame('pt', $manifest['i18n']['default_lang'] ?? null);

    $this->deleteDirRecursive($tmpRoot);
  }

  #[Test]
  public function loadManifestReturnsNullWhenManifestIsNotArray(): void
  {
    $tmpRoot = sys_get_temp_dir() . '/paycal-runtime-manifest-' . bin2hex(random_bytes(6));
    $extensionDir = $tmpRoot . '/ext-invalid-manifest';
    mkdir($extensionDir, 0777, true);

    file_put_contents($extensionDir . '/manifest.php', "<?php\nreturn 'invalid';\n");

    $loader = new \ReflectionMethod(ExtensionRuntime::class, 'loadManifest');
    $manifest = $loader->invoke(null, $extensionDir, 'basic');

    $this->assertNull($manifest);

    $this->deleteDirRecursive($tmpRoot);
  }

  #[Test]
  public function loadManifestReturnsNullWhenIdMissing(): void
  {
    $tmpRoot = sys_get_temp_dir() . '/paycal-runtime-manifest-' . bin2hex(random_bytes(6));
    $extensionDir = $tmpRoot . '/ext-missing-id';
    mkdir($extensionDir, 0777, true);

    $manifestPhp = <<<'PHP'
<?php
return [
  'name' => 'Missing ID',
  'enabled' => true,
];
PHP;
    file_put_contents($extensionDir . '/manifest.php', $manifestPhp);

    $loader = new \ReflectionMethod(ExtensionRuntime::class, 'loadManifest');
    $manifest = $loader->invoke(null, $extensionDir, 'basic');

    $this->assertNull($manifest);

    $this->deleteDirRecursive($tmpRoot);
  }

  #[Test]
  public function loadManifestOmitsI18nWhenPathIsEmpty(): void
  {
    $tmpRoot = sys_get_temp_dir() . '/paycal-runtime-manifest-' . bin2hex(random_bytes(6));
    $extensionDir = $tmpRoot . '/ext-empty-i18n-path';
    mkdir($extensionDir, 0777, true);

    $manifestPhp = <<<'PHP'
<?php
return [
  'id' => 'ext-empty-i18n-path',
  'enabled' => true,
  'i18n' => [
    'path' => '   ',
    'default_lang' => 'fr',
  ],
];
PHP;
    file_put_contents($extensionDir . '/manifest.php', $manifestPhp);

    $loader = new \ReflectionMethod(ExtensionRuntime::class, 'loadManifest');
    /** @var array<string, mixed>|null $manifest */
    $manifest = $loader->invoke(null, $extensionDir, 'basic');

    $this->assertIsArray($manifest);
    $this->assertSame([], $manifest['i18n'] ?? null);

    $this->deleteDirRecursive($tmpRoot);
  }

  /** @return mixed */
  private function readRuntimeStatic(string $property): mixed
  {
    $ref = new \ReflectionProperty(ExtensionRuntime::class, $property);
    return $ref->getValue();
  }

  private function writeRuntimeStatic(string $property, mixed $value): void
  {
    $ref = new \ReflectionProperty(ExtensionRuntime::class, $property);
    $ref->setValue(null, $value);
  }

  /** @return array<string, array<int, array{priority:int, callback:callable, source:string}>> */
  private function readHookBusListeners(): array
  {
    $ref = new \ReflectionProperty(HookBus::class, 'listeners');
    /** @var array<string, array<int, array{priority:int, callback:callable, source:string}>> $listeners */
    $listeners = $ref->getValue();
    return $listeners;
  }

  /** @param array<string, array<int, array{priority:int, callback:callable, source:string}>> $listeners */
  private function writeHookBusListeners(array $listeners): void
  {
    $ref = new \ReflectionProperty(HookBus::class, 'listeners');
    $ref->setValue(null, $listeners);
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
