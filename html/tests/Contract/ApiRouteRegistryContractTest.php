<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: API route wiring remains registry-driven and reflection-discovered.
 */
#[Group('contract')]
final class ApiRouteRegistryContractTest extends TestCase
{
  public function testApiIndexUsesControllerRegistryInsteadOfInlineControllerList(): void
  {
    $apiIndex = $this->readProjectFile('api/index.php');

    $this->assertStringContainsString('$controllers = ApiControllerRegistry::controllers();', $apiIndex);
    $this->assertStringNotContainsString('AdminController::class,', $apiIndex);
    $this->assertStringNotContainsString('BillingController::class,', $apiIndex);
    $this->assertStringNotContainsString('UserController::class,', $apiIndex);
  }

  public function testApiIndexBuildsRoutesFromRouteAttributesByReflection(): void
  {
    $apiIndex = $this->readProjectFile('api/index.php');

    $this->assertStringContainsString('new \\ReflectionClass($controllerClass);', $apiIndex);
    $this->assertStringContainsString('$attributes = $method->getAttributes(Route::class);', $apiIndex);
    $this->assertStringContainsString('$route = $attribute->newInstance();', $apiIndex);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
