<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: SecurityLog remains dual-path (HookBus dispatch + core log record).
 */
#[Group('contract')]
final class SecurityLogHookContractTest extends TestCase
{
  public function testSecurityLogDispatchesAuditEventThroughHookBus(): void
  {
    $source = $this->readProjectFile('src/Infrastructure/Telemetry/SecurityLog.php');

    $this->assertStringContainsString("ExtensionHookBridge::dispatch('security.audit_event', [", $source);
    $this->assertStringContainsString('\'event\' => $event', $source);
    $this->assertStringContainsString('\'context\' => $context', $source);
    $this->assertStringContainsString('\'timestamp\' => $timestamp', $source);
  }

  public function testSecurityLogStillWritesCoreSecurityLogLine(): void
  {
    $source = $this->readProjectFile('src/Infrastructure/Telemetry/SecurityLog.php');

    $this->assertStringContainsString('Log::error(\'[SECURITY] \' . json_encode($payload));', $source);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
