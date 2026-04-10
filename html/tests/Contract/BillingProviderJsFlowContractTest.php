<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: billing frontend flow must remain provider-conditional.
 */
#[Group('contract')]
final class BillingProviderJsFlowContractTest extends TestCase
{
  public function testUpgradeFlowOnlyRedirectsToCheckoutForStripeProvider(): void
  {
    $billingJs = $this->readProjectFile('js/core/billing.js');

    $this->assertStringContainsString('if (isStripeBilling) {', $billingJs);
    $this->assertStringContainsString('const checkoutUrl = typeof data.checkout_url === \'string\' ? data.checkout_url : \'\';', $billingJs);
    $this->assertStringContainsString('window.location.href = checkoutUrl;', $billingJs);
    $this->assertStringContainsString('await refreshSubscription({ silent: false });', $billingJs);
  }

  public function testPortalActionUsesProviderSpecificEndpointBranch(): void
  {
    $billingJs = $this->readProjectFile('js/core/billing.js');

    $this->assertStringContainsString('const endpoint = isStripeBilling ? \'/api/v1/billing/portal-session\' : \'/api/v1/billing/cancel-subscription\';', $billingJs);
    $this->assertStringContainsString('const body = isStripeBilling', $billingJs);
    $this->assertStringContainsString('return_url: portalReturnUrl,', $billingJs);
    $this->assertStringContainsString('csrf_token: csrfToken,', $billingJs);
  }

  public function testPortalFlowOnlyRedirectsToPortalUrlForStripeProvider(): void
  {
    $billingJs = $this->readProjectFile('js/core/billing.js');

    $this->assertStringContainsString('const portalUrl = typeof data.portal_url === \'string\' ? data.portal_url : \'\';', $billingJs);
    $this->assertStringContainsString('window.location.href = portalUrl;', $billingJs);
    $this->assertStringContainsString('setInlineStatus(portalStatus, messages.cancel);', $billingJs);
    $this->assertStringContainsString('portalBtn.disabled = false;', $billingJs);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
