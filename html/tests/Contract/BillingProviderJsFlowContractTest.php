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

  public function testPlanChangeFlowPostsToBillingChangePlanEndpoint(): void
  {
    $billingJs = $this->readProjectFile('js/core/billing.js');

    $this->assertStringContainsString("fetchJson('/api/v1/billing/change-plan'", $billingJs);
    $this->assertStringContainsString("plan,", $billingJs);
    $this->assertStringContainsString("proration_behavior: 'create_prorations',", $billingJs);
    $this->assertStringContainsString("bindPlanChangeButton(downgradePremiumBtn, downgradePremiumStatus, 'premium');", $billingJs);
    $this->assertStringNotContainsString("bindPlanChangeButton(upgradeBusinessPlanBtn, upgradeBusinessPlanStatus, 'business');", $billingJs);
  }

  public function testSubscriptionActionsTrackCurrentTier(): void
  {
    $billingJs = $this->readProjectFile('js/core/billing.js');

    $this->assertStringContainsString('setElementHidden(upgradeBusinessPlanBtn, !hasPaid || isBusiness);', $billingJs);
    $this->assertStringContainsString('setElementHidden(downgradePremiumBtn, !hasPaid || !isBusiness);', $billingJs);
    $this->assertStringContainsString('setElementHidden(downgradeFreeBtn, !hasPaid);', $billingJs);
    $this->assertStringContainsString("downgradeZoneEl.open = true;", $billingJs);
    $this->assertStringContainsString("id=\"billing_downgrade_free_dialog\"", $this->readProjectFile('settings/_partials/panel_account_billing.php'));
  }

  public function testSettingsSubscriptionCheckoutReturnsToSubscriptionPage(): void
  {
    $settingsJs = $this->readProjectFile('js/settings/index.php');

    $this->assertStringContainsString("successUrl: '/settings/subscription/?billing=success'", $settingsJs);
    $this->assertStringNotContainsString("successUrl: '/api/v1/billing/checkout-return'", $settingsJs);
  }

  public function testBusinessUpgradeUsesStripePortalPlanChangeFlow(): void
  {
    $billingJs = $this->readProjectFile('js/core/billing.js');

    $this->assertStringContainsString("fetchJson('/api/v1/billing/plan-change-portal-session'", $billingJs);
    $this->assertStringContainsString("bindPlanChangePortalButton(upgradeBusinessPlanBtn, upgradeBusinessPlanStatus, 'business');", $billingJs);
    $this->assertStringContainsString('window.location.href = portalUrl;', $billingJs);
    $this->assertStringContainsString("billingQuery === 'business-upgrade'", $billingJs);
    $this->assertStringContainsString("document.body.classList.add('business-upgrade-celebrate');", $billingJs);
    $this->assertStringContainsString("document.getElementById('business-upgrade-status')", $billingJs);
    $this->assertStringContainsString("document.getElementById('business-upgrade-wormhole')", $billingJs);
    $this->assertStringContainsString("'wormhole-strand strand-1'", $billingJs);
    $this->assertStringContainsString("'wormhole-core'", $billingJs);
    $this->assertStringContainsString("'wormhole-flash'", $billingJs);
    $this->assertStringContainsString("'--upgrade-target-x'", $billingJs);
    $this->assertStringContainsString("'--upgrade-target-width'", $billingJs);
    $this->assertStringContainsString('playBusinessUpgradeSound', $billingJs);
    $this->assertStringContainsString('primeBusinessUpgradeAudio', $billingJs);
    $this->assertStringContainsString('businessUpgradeCelebrationActive', $billingJs);
    $this->assertStringContainsString('window.clearTimeout(businessUpgradeCleanupTimer)', $billingJs);
    $this->assertStringContainsString("status.classList.remove('is-highlighted');", $billingJs);
    $this->assertStringContainsString("button.addEventListener('pointerdown', primeBusinessUpgradeAudio", $billingJs);
    $this->assertStringContainsString("resolveAudioFeedbackMode() === 'none'", $billingJs);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
