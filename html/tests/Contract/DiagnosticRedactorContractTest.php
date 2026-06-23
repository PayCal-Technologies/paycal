<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
#[Group('security')]
final class DiagnosticRedactorContractTest extends TestCase
{
  public function testRedactorBlocksSensitiveKeyClasses(): void
  {
    $source = $this->readProjectFile('src/Observability/DiagnosticRedactor.php');

    foreach (['user_uuid', 'session_id', 'passkey', 'wage', 'tax', 'email', 'token', 'encrypted'] as $needle) {
      $this->assertStringContainsString("'{$needle}'", $source);
    }
  }

  public function testTelemetryControllerScrubFieldsAlignedWithRedactor(): void
  {
    $redactor = $this->readProjectFile('src/Observability/DiagnosticRedactor.php');
    $telemetry = $this->readProjectFile('src/Controllers/TelemetryController.php');

    foreach (['user_uuid', 'email', 'ip', 'full_name'] as $needle) {
      $this->assertStringContainsString($needle, $redactor);
      $this->assertStringContainsString($needle, $telemetry);
    }
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
