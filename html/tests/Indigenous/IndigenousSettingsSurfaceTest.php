<?php declare(strict_types=1);

namespace PayCal\Tests\Indigenous;

use PayCal\Controllers\SettingsController;
use PayCal\Domain\SitesService;
use PayCal\Domain\UserFields;
use PayCal\Domain\UserSettings;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('private-moat')]
final class IndigenousSettingsSurfaceTest extends TestCase
{
  private string $root;

  protected function setUp(): void
  {
    $this->root = dirname(__DIR__, 3);
  }

  #[Test]
  public function userSettingsWhitelistIncludesIndigenousResidenceFields(): void
  {
    $fields = array_map(static fn (UserFields $field): string => $field->value, UserFields::cases());
    $allowed = UserSettings::allowedStrings();

    foreach (['indigenous_tax_exemption_eligible', 'lives_on_reserve', 'reserve_name'] as $field) {
      $this->assertContains($field, $fields);
      $this->assertContains($field, $allowed);
    }
  }

  #[Test]
  public function accountInfoNormalizationCleansIndigenousFlagsAndReserveName(): void
  {
    $method = new \ReflectionMethod(SettingsController::class, 'normalizeAccountInfoPreferences');

    /** @var array<string, mixed> $result */
    $result = $method->invoke(null, [
      'indigenous_tax_exemption_eligible' => 'on',
      'lives_on_reserve' => '1',
      'reserve_name' => str_repeat('A', 140),
    ]);

    $this->assertSame('1', $result['indigenous_tax_exemption_eligible']);
    $this->assertSame('1', $result['lives_on_reserve']);
    $this->assertSame(120, strlen((string) $result['reserve_name']));
  }

  #[Test]
  public function accountSettingsUiExposesIndigenousFieldsInline(): void
  {
    $panel = (string) file_get_contents($this->root . '/html/settings/_partials/panel_account.php');

    foreach ([
      'edit_details_indigenous_tax_exemption_eligible',
      'edit_details_lives_on_reserve',
      'edit_details_reserve_name',
    ] as $id) {
      $this->assertStringContainsString($id, $panel);
    }

    $this->assertStringNotContainsString('modal_edit_details', (string) file_get_contents($this->root . '/html/settings/_partials/modals.php'));
  }

  #[Test]
  public function sitesNormalizeReserveFieldsForPersonalAndBusinessSites(): void
  {
    $service = new SitesService();

    $normalized = $service->normalizeSite([
      'site_name' => 'Reserve Job',
      'wage' => '45.36',
      'is_on_reserve' => 'on',
      'reserve_name' => str_repeat('N', 140),
    ]);

    $this->assertSame('1', $normalized['is_on_reserve']);
    $this->assertSame(120, strlen($normalized['reserve_name']));
  }

  #[Test]
  public function siteEditorsExposeReserveFieldsAndHydrateThem(): void
  {
    $dialogs = (string) file_get_contents($this->root . '/html/sites/_partials/site_editor_dialogs.php');
    $personalSitesJs = (string) file_get_contents($this->root . '/html/js/sites/index.php');
    $sharedSiteEditorJs = (string) file_get_contents($this->root . '/html/js/sites/site-editor-core.php');

    foreach ([
      'site_is_on_reserve_input',
      'site_reserve_name_input',
      'edit_site_is_on_reserve_input',
      'edit_site_reserve_name_input',
    ] as $id) {
      $this->assertStringContainsString($id, $dialogs);
    }

    $this->assertStringContainsString('site.is_on_reserve', $personalSitesJs);
    $this->assertStringContainsString('site.reserve_name', $personalSitesJs);
    $this->assertStringContainsString('site?.is_on_reserve', $sharedSiteEditorJs);
    $this->assertStringContainsString('site?.reserve_name', $sharedSiteEditorJs);
  }

  #[Test]
  public function businessProfileExposesIndigenousAndReserveClaimFields(): void
  {
    $controller = (string) file_get_contents($this->root . '/html/src/Controllers/BusinessDiscoveryController.php');
    $service = (string) file_get_contents($this->root . '/html/src/Domain/BusinessDiscoveryService.php');
    $panel = (string) file_get_contents($this->root . '/html/business/_partials/business_details_panel.php');
    $workspaceJs = (string) file_get_contents($this->root . '/html/js/business/workspace.js.php');

    foreach ([
      'indigenous_owned',
      'resident_on_reserve',
      'reserve_name',
    ] as $field) {
      $this->assertStringContainsString("'" . $field . "'", $controller);
      $this->assertStringContainsString("'" . $field . "'", $service);
      $this->assertStringContainsString($field, $workspaceJs);
    }

    foreach ([
      'businesses_editor_indigenous_owned',
      'businesses_editor_resident_on_reserve',
      'businesses_editor_reserve_name',
    ] as $id) {
      $this->assertStringContainsString($id, $panel);
      $this->assertStringContainsString($id, $workspaceJs);
    }
  }

  #[Test]
  public function taxRateManifestIncludesProvinceSpecificIndigenousSalesTaxRelief(): void
  {
    $path = $this->root . '/html/src/Domain/TaxRateTablesData.json';
    $decoded = json_decode((string) file_get_contents($path), true);

    $this->assertIsArray($decoded);
    $relief = $decoded['salesTaxExemptions']['indigenousStatus'] ?? null;
    $this->assertIsArray($relief);

    $this->assertSame(700, $relief['British Columbia']['pst']['rateBasisPoints']);
    $this->assertTrue($relief['British Columbia']['pst']['eligible']);
    $this->assertSame(800, $relief['Ontario']['pst']['rateBasisPoints']);
    $this->assertTrue($relief['Ontario']['pst']['eligible']);
    $this->assertSame(0, $relief['Alberta']['pst']['rateBasisPoints']);
    $this->assertFalse($relief['Alberta']['pst']['eligible']);
  }

  #[Test]
  public function browserTaxMirrorExposesIndigenousSalesTaxReliefAccessor(): void
  {
    $source = (string) file_get_contents($this->root . '/html/js/earnings/taxes.js');

    $this->assertStringContainsString('getIndigenousStatusSalesTaxExemption', $source);
    $this->assertStringContainsString('salesTaxExemptions', $source);
    $this->assertStringContainsString('window.PayCalTaxes', $source);
  }
}
