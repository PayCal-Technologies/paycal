<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: extension i18n metadata and lookup precedence remain stable.
 */
#[Group('contract')]
final class ExtensionI18nManifestContractTest extends TestCase
{
  public function testRuntimeLoadManifestKeepsI18nNormalizationFields(): void
  {
    $runtime = $this->readProjectFile('extensions/runtime.php');

    $this->assertStringContainsString('$i18nRaw = is_array($manifest[\'i18n\'] ?? null) ? $manifest[\'i18n\'] : [];', $runtime);
    $this->assertStringContainsString('$i18nPath = trim(self::stringValue($i18nRaw[\'path\'] ?? null));', $runtime);
    $this->assertStringContainsString('$i18nDefaultLang = strtolower(trim(self::stringValue($i18nRaw[\'default_lang\'] ?? null, \'en\')));', $runtime);
    $this->assertStringContainsString('\'i18n\' => $i18n,', $runtime);
  }

  public function testStringsI18nChecksExtensionLookupBeforeCoreFiles(): void
  {
    $strings = $this->readProjectFile('src/Domain/Strings.php');

    $this->assertStringContainsString('$extensionValue = self::lookupFromActiveExtensionLocales($lookupKey, $lang)', $strings);
    $this->assertStringContainsString('$fileValue = self::lookupFromLocaleFile($lookupKey, $lang)', $strings);
    $this->assertStringContainsString('private static function loadActiveExtensionLocaleMap(string $lang): array', $strings);
    $this->assertStringContainsString('private static function activeExtensionLocaleCacheKey(string $lang): string', $strings);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
