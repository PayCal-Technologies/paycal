<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: billing-provider manifests keep stable IDs and provider values.
 */
#[Group('contract')]
final class BillingProviderManifestContractTest extends TestCase
{
  public function testBasicBillingProviderManifestUsesPublicToggleCapability(): void
  {
    /** @var array<string, mixed> $manifest */
    $manifest = require __DIR__ . '/../../extensions/basic/billing-provider/manifest.php';

    $this->assertSame('billing-provider', $manifest['id'] ?? null);
    $this->assertSame('public-toggle', $manifest['capabilities']['billing.provider'] ?? null);
    $this->assertSame('bootstrap.php', $manifest['bootstrap'] ?? null);
  }

  public function testOverrideBillingProviderManifestUsesStripeCapability(): void
  {
    /** @var array<string, mixed> $manifest */
    $manifest = require __DIR__ . '/../../extensions/overrides/billing-provider/manifest.php';

    $this->assertSame('billing-provider', $manifest['id'] ?? null);
    $this->assertSame('stripe', $manifest['capabilities']['billing.provider'] ?? null);
    $this->assertSame('bootstrap.php', $manifest['bootstrap'] ?? null);
  }
}
