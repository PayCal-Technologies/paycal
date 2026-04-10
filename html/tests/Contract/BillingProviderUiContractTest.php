<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: billing UI remains provider-aware across profile markup and JS.
 */
#[Group('contract')]
final class BillingProviderUiContractTest extends TestCase
{
  public function testProfilePanelExposesBillingProviderDataAttribute(): void
  {
    $profile = $this->readProjectFile('profile/index.php');

    $this->assertStringContainsString('$billingProvider = BillingProvider::current();', $profile);
    $this->assertStringContainsString('$isStripeBilling = $billingProvider === BillingProvider::STRIPE;', $profile);
    $this->assertStringContainsString('data-billing-provider="<?php echo htmlspecialchars($billingProvider, ENT_QUOTES, \'UTF-8\'); ?>"', $profile);
  }

  public function testProfileBillingActionsRemainProviderConditional(): void
  {
    $profile = $this->readProjectFile('profile/index.php');

    $this->assertStringContainsString('<?php if ($isStripeBilling) { ?>', $profile);
    $this->assertStringContainsString('<?php } else { ?>', $profile);
    $this->assertStringContainsString('<?php if ($isStripeBilling) { ?>', $profile);
    $this->assertStringContainsString('id="billing_downgrade_zone"', $profile);
  }

  public function testBillingJsReadsProviderFromPanelDataset(): void
  {
    $billingJs = $this->readProjectFile('js/core/billing.js');

    $this->assertStringContainsString('const billingProvider = billingPanel instanceof HTMLElement', $billingJs);
    $this->assertStringContainsString("String(billingPanel.dataset.billingProvider || 'public-toggle').trim().toLowerCase()", $billingJs);
    $this->assertStringContainsString("const isStripeBilling = billingProvider === 'stripe';", $billingJs);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
