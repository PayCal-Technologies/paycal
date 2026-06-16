<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: billing UI remains provider-aware across account settings markup and JS.
 */
#[Group('contract')]
final class BillingProviderUiContractTest extends TestCase
{
  public function testAccountPanelExposesBillingProviderDataAttribute(): void
  {
    $vars = $this->readProjectFile('settings/_partials/vars_account.php');
    $billing = $this->readProjectFile('settings/_partials/panel_account_billing.php');

    $this->assertStringContainsString('$billingProvider = BillingProvider::current();', $vars);
    $this->assertStringContainsString('$isStripeBilling = $billingProvider === BillingProvider::STRIPE;', $vars);
    $this->assertStringContainsString('data-billing-provider="<?php echo htmlspecialchars($billingProvider, ENT_QUOTES, \'UTF-8\'); ?>"', $billing);
  }

  public function testAccountBillingActionsRemainProviderConditional(): void
  {
    $billing = $this->readProjectFile('settings/_partials/panel_account_billing.php');

    $this->assertStringContainsString('<?php if ($isStripeBilling) { ?>', $billing);
    $this->assertStringContainsString('<?php } else { ?>', $billing);
    $this->assertStringContainsString('id="billing_downgrade_zone"', $billing);
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
