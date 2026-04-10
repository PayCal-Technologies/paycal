<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: BillingController must preserve provider-mode endpoint gating.
 */
#[Group('contract')]
final class BillingProviderModeContractTest extends TestCase
{
  public function testStripeOnlyEndpointsKeepBillingProviderModeAttribute(): void
  {
    $controller = $this->readProjectFile('src/Controllers/BillingController.php');

    $this->assertStringContainsString('#[BillingProviderMode([BillingProvider::STRIPE])]', $controller);
    $this->assertStringContainsString('public function handleCheckoutReturn(): void', $controller);
    $this->assertStringContainsString('public function createPortalSession(): void', $controller);
    $this->assertStringContainsString('public function confirmCheckoutSession(): void', $controller);
    $this->assertStringContainsString('public function webhook(): void', $controller);
  }

  public function testStripeOnlyEndpointsRemainGuardedByBillingProviderAllows(): void
  {
    $controller = $this->readProjectFile('src/Controllers/BillingController.php');

    $this->assertStringContainsString('if (!$this->billingProviderAllows(__FUNCTION__)) {', $controller);
    $this->assertStringContainsString('private function billingProviderAllows(string $method): bool', $controller);
    $this->assertStringContainsString('new \ReflectionMethod($this, $method);', $controller);
  }

  public function testPublicToggleBranchesRemainInCheckoutAndCancelFlows(): void
  {
    $controller = $this->readProjectFile('src/Controllers/BillingController.php');

    $this->assertStringContainsString('if (!BillingProvider::isStripe()) {', $controller);
    $this->assertStringContainsString('SubscriptionRepository::upgradeToPremium($userUUID);', $controller);
    $this->assertStringContainsString('SubscriptionRepository::downgradeToFree($userUUID);', $controller);
    $this->assertStringContainsString("'billing_provider' => BillingProvider::current()", $controller);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
