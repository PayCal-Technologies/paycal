<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\BusinessDiscoveryService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('private-moat')]
final class ProfilePayPeriodContextTest extends TestCase
{
  #[Test]
  public function accountPageOmitsPayPeriodPanelAndCalendarHostsPayPeriodSettings(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $accountPage = (string) file_get_contents($projectRoot . '/html/settings/account/index.php');
    $calendarPage = (string) file_get_contents($projectRoot . '/html/settings/calendar/index.php');
    $calendarPayPeriodPartial = (string) file_get_contents($projectRoot . '/html/settings/_partials/panel_calendar_pay_period.php');
    $profileJs = (string) file_get_contents($projectRoot . '/html/js/business-profile/index.php');
    $workspaceJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');
    $personalSettingsJs = (string) file_get_contents($projectRoot . '/html/js/business/core/personal-settings.js.php');

    $this->assertStringContainsString('vars_account.php', $accountPage);
    $this->assertStringNotContainsString('panel_account_pay_period.php', $accountPage);
    $this->assertStringNotContainsString('panel-pay-period', $accountPage);
    $this->assertStringContainsString('panel_calendar_pay_period.php', $calendarPage);
    $this->assertStringContainsString('id="settings_pay_period_form"', $calendarPayPeriodPartial);
    $this->assertStringContainsString('initialize().catch', $profileJs);
    $this->assertStringContainsString("require __DIR__ . '/../business/core/context-header.js.php'", $profileJs);
    $this->assertStringContainsString("require __DIR__ . '/../business/core/personal-settings.js.php'", $profileJs);
    $this->assertStringContainsString('loadPersonalBusinessPanel', $personalSettingsJs);
    $this->assertStringContainsString('updateProfilePayPeriodManagedBanner', $personalSettingsJs);
    $this->assertStringContainsString('profilePayPeriodManagedByBusiness', $personalSettingsJs);
    $this->assertStringContainsString("PC.updateResource('account/profile'", $personalSettingsJs);
    $this->assertStringNotContainsString('const loadPersonalBusinessPanel =', $workspaceJs);
    $this->assertStringNotContainsString("PC.updateResource('profile'", $personalSettingsJs);
  }

  #[Test]
  public function legacyProfileRouteEntrypointIsRemoved(): void
  {
    $this->assertFileDoesNotExist(dirname(__DIR__, 4) . '/html/profile/index.php');
  }

  #[Test]
  public function resolveProfilePayPeriodManagedByBusinessIsPublic(): void
  {
    $this->assertTrue(method_exists(BusinessDiscoveryService::class, 'resolveProfilePayPeriodManagedByBusiness'));
  }
}
