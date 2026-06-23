<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
#[Group('security')]
final class ArgusTraceGateContractTest extends TestCase
{
  /** @return array<int, string> */
  public static function observabilityFileProvider(): array
  {
    return [
      ['src/Observability/Argus.php'],
      ['src/Observability/ArgusConsole.php'],
      ['src/Observability/ArgusPackageStore.php'],
      ['src/Observability/TraceGate.php'],
      ['src/Observability/TraceGatePolicy.php'],
      ['src/Observability/TraceGateDecision.php'],
      ['src/Observability/DiagnosticEvent.php'],
      ['src/Observability/DiagnosticSeverity.php'],
      ['src/Observability/DiagnosticRedactor.php'],
      ['src/Observability/Sink/DiagnosticSinkInterface.php'],
      ['src/Observability/Sink/NullSink.php'],
      ['src/Observability/Sink/FileSink.php'],
      ['src/Observability/Sink/SecurityLogSink.php'],
      ['src/Observability/Sink/DiagnosticSinkRegistry.php'],
      ['src/Observability/SecurityEventCatalog.php'],
    ];
  }

  #[\PHPUnit\Framework\Attributes\DataProvider('observabilityFileProvider')]
  public function testObservabilityMvpFilesExist(string $relativePath): void
  {
    $this->assertFileExists(__DIR__ . '/../../' . $relativePath);
  }

  public function testArgusIsRequestTimeFacadeNotDaemon(): void
  {
    $source = $this->readProjectFile('src/Observability/Argus.php');

    $this->assertStringContainsString('request-time diagnostic observer', $source);
    $this->assertStringContainsString('Not a background daemon', $source);
    $this->assertStringContainsString('TraceGate::evaluate', $source);
    $this->assertStringNotContainsString('while (true)', $source);
  }

  public function testSecurityLogSinkDoesNotReferenceTheLedger(): void
  {
    $source = $this->readProjectFile('src/Observability/Sink/SecurityLogSink.php');

    $this->assertStringContainsString('SecurityLog::writeRecord', $source);
    $this->assertStringContainsString('Does not write to TheLedger', $source);
    $this->assertStringNotContainsString('TheLedger::', $source);
    $this->assertStringNotContainsString('SystemAuditRepository::', $source);
  }

  public function testFileSinkIsDevOnly(): void
  {
    $source = $this->readProjectFile('src/Observability/Sink/FileSink.php');

    $this->assertStringContainsString('TraceGatePolicy::isDevEnvironment()', $source);
    $this->assertStringContainsString('diagnostic.log', $source);
  }

  public function testArgusConsoleAdminPageIsInteractiveControlPanel(): void
  {
    $source = $this->readProjectFile('admin/argus/index.php');

    $this->assertStringContainsString("AdminSurface::redirectHomeIfPageUnavailable('/admin/argus/')", $source);
    $this->assertStringContainsString('ArgusConsole::snapshot', $source);
    $this->assertStringContainsString('argus-pill', $source);
    $this->assertStringContainsString('argus-master-toggle', $source);
    $this->assertStringContainsString('js/admin/argus.php', $source);
    $this->assertStringNotContainsString('read-only', $source);
  }

  public function testArgusAdminApiRoutesExist(): void
  {
    $source = $this->readProjectFile('src/Controllers/AdminController.php');

    $this->assertStringContainsString("'admin.argus.master'", $source);
    $this->assertStringContainsString("'admin.argus.package'", $source);
    $this->assertStringContainsString("Route('admin/argus/status'", $source);
    $this->assertStringContainsString("Route('admin/argus/master'", $source);
    $this->assertStringContainsString("Route('admin/argus/package'", $source);
  }

  public function testArgusAdminJsUsesToastAndStandardApiParsing(): void
  {
    $source = $this->readProjectFile('js/admin/argus.php');

    $this->assertStringContainsString('PC.showToast', $source);
    $this->assertStringContainsString("payload.status !== 'success'", $source);
    $this->assertStringContainsString('X-PayCal-Capability', $source);
    $this->assertStringContainsString('enqueueToggle', $source);
    $this->assertStringContainsString('refreshSnapshot', $source);
    $this->assertStringNotContainsString('setFeedback', $source);
  }

  public function testArgusCaptureSystemFilesExist(): void
  {
    foreach ([
      'src/Observability/ArgusCaptureScope.php',
      'src/Observability/ArgusExpiryPolicy.php',
      'src/Observability/ArgusRequestContext.php',
      'src/Observability/TraceTimelineStore.php',
      'src/Observability/ArgusEventBudget.php',
      'src/Observability/ArgusPresetCatalog.php',
    ] as $path) {
      $this->assertFileExists(__DIR__ . '/../../' . $path);
    }
  }

  public function testTraceGateChecksMasterAndPackageState(): void
  {
    $source = $this->readProjectFile('src/Observability/TraceGate.php');

    $this->assertStringContainsString('TraceGatePolicy::isMasterEnabled()', $source);
    $this->assertStringContainsString('TraceGatePolicy::moduleIsEnabled', $source);
    $this->assertStringContainsString("'master_disabled'", $source);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
