<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\BusinessDiscoveryService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ProfilePayPeriodContextTest extends TestCase
{
  #[Test]
  public function profilePageDocumentsManagedPayPeriodBannerAndProfileBundleBoot(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $profilePage = (string) file_get_contents($projectRoot . '/html/profile/index.php');
    $profileJs = (string) file_get_contents($projectRoot . '/html/js/business-profile/index.php');
    $workspaceJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');

    $this->assertStringContainsString('resolveProfilePayPeriodManagedByBusiness', $profilePage);
    $this->assertStringContainsString('id="profile_pay_period_managed_banner"', $profilePage);
    $this->assertStringContainsString('PROFILE_PAY_PERIOD_MANAGED_BANNER', $profilePage);
    $this->assertStringContainsString('initialize().catch', $profileJs);
    $this->assertStringContainsString("require __DIR__ . '/../business/core/context-header.js.php'", $profileJs);
    $this->assertStringContainsString('loadPersonalBusinessPanel', $workspaceJs);
    $this->assertStringContainsString('updateProfilePayPeriodManagedBanner', $workspaceJs);
    $this->assertStringContainsString('profilePayPeriodManagedByBusiness', $workspaceJs);
  }

  #[Test]
  public function resolveProfilePayPeriodManagedByBusinessIsPublic(): void
  {
    $this->assertTrue(method_exists(BusinessDiscoveryService::class, 'resolveProfilePayPeriodManagedByBusiness'));
  }
}
