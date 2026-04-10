<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: ExtensionRuntime capability coercion accepts known true/false
 * tokens and reads the first capability entry as effective value.
 */
#[Group('contract')]
final class ExtensionCapabilityCoercionContractTest extends TestCase
{
  public function testCapabilityEnabledSupportsKnownTruthyAndFalsyStringTokens(): void
  {
    $runtime = $this->readProjectFile('extensions/runtime.php');

    $this->assertStringContainsString("['1', 'true', 'yes', 'on', 'enabled']", $runtime);
    $this->assertStringContainsString("['0', 'false', 'no', 'off', 'disabled']", $runtime);
  }

  public function testCapabilityEnabledUsesFirstCapabilityEntryValue(): void
  {
    $runtime = $this->readProjectFile('extensions/runtime.php');

    $this->assertStringContainsString('$value = $entries[0][\'value\'] ?? $default;', $runtime);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
