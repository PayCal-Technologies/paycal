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
  public function accountPageOmitsPayPeriodPanelAndCalendarHostsPayPeriodSettings(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $accountPage = (string) file_get_contents($projectRoot . '/html/settings/account/index.php');
    $calendarPage = (string) file_get_contents($projectRoot . '/html/settings/calendar/index.php');
    $calendarPayPeriodPartial = (string) file_get_contents($projectRoot . '/html/settings/_partials/panel_calendar_pay_period.php');

    $this->assertStringContainsString('vars_account.php', $accountPage);
    $this->assertStringNotContainsString('panel_account_pay_period.php', $accountPage);
    $this->assertStringNotContainsString('panel-pay-period', $accountPage);
    $this->assertStringContainsString('panel_calendar_pay_period.php', $calendarPage);
    $this->assertStringContainsString('id="settings_pay_period_form"', $calendarPayPeriodPartial);
  }

  #[Group('private-moat')]
  #[Test]
  public function profileBusinessBundleDocumentsManagedPayPeriodBanner(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $profileJs = (string) file_get_contents($projectRoot . '/html/js/business-profile/index.php');
    $workspaceJs = (string) file_get_contents($projectRoot . '/html/js/business/workspace.js.php');

    $this->assertStringContainsString('initialize().catch', $profileJs);
    $this->assertStringContainsString("require __DIR__ . '/../business/core/context-header.js.php'", $profileJs);
    $this->assertStringContainsString('loadPersonalBusinessPanel', $workspaceJs);
    $this->assertStringContainsString('updateProfilePayPeriodManagedBanner', $workspaceJs);
    $this->assertStringContainsString('profilePayPeriodManagedByBusiness', $workspaceJs);
  }

  #[Test]
  public function profileRouteRedirectsToSettingsAccount(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $profileRedirect = (string) file_get_contents($projectRoot . '/html/profile/index.php');

    $this->assertStringContainsString('/settings/account/', $profileRedirect);
    $this->assertStringContainsString("header('Location:", $profileRedirect);
  }

  #[Test]
  public function resolveProfilePayPeriodManagedByBusinessIsPublic(): void
  {
    $this->assertTrue(method_exists(BusinessDiscoveryService::class, 'resolveProfilePayPeriodManagedByBusiness'));
  }
}
