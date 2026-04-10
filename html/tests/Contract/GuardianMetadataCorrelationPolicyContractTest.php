<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
#[Group('security')]
final class GuardianMetadataCorrelationPolicyContractTest extends TestCase
{
  public function testGuardianExportsDenyByDefaultMetadataCorrelationPolicy(): void
  {
    $htmlRoot = dirname(__DIR__, 2);
    $guardianJs = (string) file_get_contents($htmlRoot . '/js/guardian.js');

    $this->assertStringContainsString("defaultDecision: 'deny'", $guardianJs);
    $this->assertStringContainsString("function getMetadataCorrelationPolicy()", $guardianJs);
    $this->assertStringContainsString("function canCorrelateMetadata(context)", $guardianJs);
    $this->assertStringContainsString("window.Guardian = {", $guardianJs);
    $this->assertStringContainsString("getMetadataCorrelationPolicy,", $guardianJs);
    $this->assertStringContainsString("canCorrelateMetadata,", $guardianJs);
  }

  public function testPolicyDocumentDefinesAllowlistAndRequiredControls(): void
  {
    $projectRoot = dirname(dirname(dirname(__DIR__)));
    $doc = (string) file_get_contents($projectRoot . '/docs/GUARDIAN_METADATA_CORRELATION_POLICY.md');

    $this->assertStringContainsString('Correlation is denied by default.', $doc);
    $this->assertStringContainsString('Allowed Contexts (Allowlist)', $doc);
    $this->assertStringContainsString('security-incident', $doc);
    $this->assertStringContainsString('fraud-investigation', $doc);
    $this->assertStringContainsString('regulatory-legal-hold', $doc);
    $this->assertStringContainsString('Required Controls for Any Allowed Correlation', $doc);
    $this->assertStringContainsString('audit_log_entry', $doc);
  }
}
