<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
#[Group('security')]
final class DiagnosticSinkContractTest extends TestCase
{
  public function testNullSinkIsRegisteredFallback(): void
  {
    $source = $this->readProjectFile('src/Observability/Sink/DiagnosticSinkRegistry.php');

    $this->assertStringContainsString("'none' =>", $source);
    $this->assertStringContainsString("self::\$sinks['none']", $source);
  }

  public function testNullSinkWriteIsNoOp(): void
  {
    $source = $this->readProjectFile('src/Observability/Sink/NullSink.php');

    $this->assertStringContainsString('no-op', strtolower($source));
    $this->assertStringNotContainsString('file_put_contents', $source);
    $this->assertStringNotContainsString('Database::', $source);
  }

  public function testSecurityLogAdapterPreservesDualPathComment(): void
  {
    $source = $this->readProjectFile('src/Observability/Sink/SecurityLogSink.php');

    $this->assertStringContainsString('Redis counter + file line + hook', $source);
    $this->assertStringContainsString('SecurityLog::writeRecord', $source);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
