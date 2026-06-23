<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class G5DomainRenderersA11yContractTest extends TestCase
{
  #[Test]
  public function adminPaletteSwatchesUseDataHexNotInlineStyles(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $controller = (string) file_get_contents($projectRoot . '/html/src/Controllers/AdminPageController.php');
    $adminCss = (string) file_get_contents($projectRoot . '/html/css/admin/index.php');

    $this->assertStringContainsString("class='admin-palette-swatch' data-hex='{\$hex}'", $controller);
    $this->assertStringNotContainsString("admin-palette-swatch' style='background:", $controller);
    $this->assertStringContainsString('.admin-palette-swatch[data-hex=', $adminCss);
    $this->assertStringContainsString('SiteColorPalette::palette()', $adminCss);
  }

  #[Test]
  public function shadowTalonErrorPageUsesDynamicLanguageNotHardcodedEnglish(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $shadowTalon = (string) file_get_contents($projectRoot . '/html/src/Domain/ShadowTalon.php');

    $this->assertStringContainsString("defined('USER_LANGUAGE')", $shadowTalon);
    $this->assertStringContainsString('$pageLanguageRaw', $shadowTalon);
    $this->assertStringContainsString('$pageLanguage', $shadowTalon);
    $this->assertStringContainsString('<html lang="{$pageLanguage}" dir="ltr">', $shadowTalon);
    $this->assertStringNotContainsString('<html lang="en" dir="ltr">', $shadowTalon);
  }

  #[Test]
  public function earningsYearTabsLinkTabpanelsWithAriaLabelledby(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $earnings = (string) file_get_contents($projectRoot . '/html/src/Domain/Earnings.php');

    $this->assertStringContainsString("id='{\$tabBtnId}' data-tab-target='{\$panelId}'", $earnings);
    $this->assertStringContainsString("aria-controls='{\$panelId}'", $earnings);
    $this->assertStringContainsString('role="tabpanel" aria-labelledby="tab-btn-{$year}"', $earnings);
    $this->assertStringContainsString("id='tab-btn-forecast'", $earnings);
    $this->assertStringContainsString('role="tabpanel" aria-labelledby="tab-btn-forecast"', $earnings);
  }

  #[Test]
  public function memberReportsYearTabsLinkTabpanelsWithAriaLabelledby(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $memberReports = (string) file_get_contents($projectRoot . '/html/src/Domain/BusinessMemberReportsService.php');

    $this->assertStringContainsString("id='{\$tabBtnId}' data-tab-target='{\$tabTarget}'", $memberReports);
    $this->assertStringContainsString("aria-controls='{\$tabTarget}'", $memberReports);
    $this->assertStringContainsString('role="tabpanel" aria-labelledby="{$tabBtnId}"', $memberReports);
    $this->assertStringContainsString("id='{\$forecastTabBtnId}'", $memberReports);
    $this->assertStringContainsString('role="tabpanel" aria-labelledby="{$forecastTabBtnId}"', $memberReports);
  }

  #[Test]
  public function contactCardMenuToggleUsesMenuHaspopupNotTrue(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $contactCardsJs = (string) file_get_contents($projectRoot . '/html/js/business/core/contact-cards.js.php');

    $this->assertStringContainsString(
      'businesses_contact_card_menu_toggle" aria-haspopup="menu"',
      $contactCardsJs,
    );
    $this->assertStringNotContainsString(
      'businesses_contact_card_menu_toggle" aria-haspopup="true"',
      $contactCardsJs,
    );
  }
}
