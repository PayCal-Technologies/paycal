<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: HookBus runtime keeps deterministic listener ordering and logs
 * listener failures while continuing dispatch.
 */
#[Group('contract')]
final class HookBusRuntimeContractTest extends TestCase
{
  public function testRegisterSortsByPriorityThenSource(): void
  {
    $runtime = $this->readProjectFile('extensions/runtime.php');

    $this->assertStringContainsString('usort(self::$listeners[$hook], static function (array $a, array $b): int {', $runtime);
    $this->assertStringContainsString('if ($a[\'priority\'] === $b[\'priority\'])', $runtime);
    $this->assertStringContainsString('return strcmp($a[\'source\'], $b[\'source\']);', $runtime);
    $this->assertStringContainsString('return $a[\'priority\'] <=> $b[\'priority\'];', $runtime);
  }

  public function testDispatchLogsListenerFailuresWithoutAborting(): void
  {
    $runtime = $this->readProjectFile('extensions/runtime.php');

    $this->assertStringContainsString('catch (\\Throwable $e) {', $runtime);
    $this->assertStringContainsString('Log::error(\'[HookBus] Listener failed for \' . $hook . \' from \' . $listener[\'source\'] . \': \' . $e->getMessage());', $runtime);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
