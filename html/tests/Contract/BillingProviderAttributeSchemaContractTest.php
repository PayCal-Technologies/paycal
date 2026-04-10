<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: BillingProviderMode attribute schema and usage remain stable.
 */
#[Group('contract')]
final class BillingProviderAttributeSchemaContractTest extends TestCase
{
  public function testBillingProviderModeAttributeTargetsMethodsAndExposesProvidersArray(): void
  {
    $attribute = $this->readProjectFile('src/Domain/Attributes/BillingProviderMode.php');

    $this->assertStringContainsString('#[\\Attribute(\\Attribute::TARGET_METHOD)]', $attribute);
    $this->assertStringContainsString('final class BillingProviderMode', $attribute);
    $this->assertStringContainsString('public function __construct(', $attribute);
    $this->assertStringContainsString('public array $providers', $attribute);
  }

  public function testBillingControllerKeepsStripeMethodAnnotations(): void
  {
    $controller = $this->readProjectFile('src/Controllers/BillingController.php');

    $this->assertStringContainsString('#[BillingProviderMode([BillingProvider::STRIPE])]', $controller);
    $this->assertStringContainsString('public function handleCheckoutReturn(): void', $controller);
    $this->assertStringContainsString('public function createPortalSession(): void', $controller);
    $this->assertStringContainsString('public function confirmCheckoutSession(): void', $controller);
    $this->assertStringContainsString('public function webhook(): void', $controller);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
