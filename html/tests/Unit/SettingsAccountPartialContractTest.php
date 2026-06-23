<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SettingsAccountPartialContractTest extends TestCase
{
  #[Test]
  public function accountPartialsExposeProfileAndLocaleControls(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $profile = (string) file_get_contents($projectRoot . '/html/settings/_partials/panel_account.php');
    $locale = (string) file_get_contents($projectRoot . '/html/settings/_partials/panel_account_locale.php');

    foreach ([
      'panel-account',
      'edit_details_form',
      'edit_details_full_name',
      'edit_details_phone',
      'edit_details_province',
      'edit_details_employment_type',
      'edit_details_indigenous_tax_exemption_eligible',
    ] as $id) {
      $this->assertStringContainsString($id, $profile, $id . ' must exist on account profile partial');
    }

    $this->assertStringNotContainsString('call_edit_details_modal', $profile);
    $this->assertStringNotContainsString('edit_details_submit', $profile);
    $this->assertStringContainsString('Details', $profile);
    $this->assertStringContainsString('Employment', $profile);
    $this->assertStringContainsString('Tax Exemptions', $profile);

    foreach (['businesses_personal_language', 'businesses_personal_locale', 'businesses_personal_currency_search', 'businesses_personal_timezone_search'] as $id) {
      $this->assertStringContainsString('id="' . $id . '"', $locale, $id . ' must exist on account locale partial');
    }

    $billing = (string) file_get_contents($projectRoot . '/html/settings/_partials/panel_account_billing.php');
    $this->assertStringContainsString('id="panel-billing"', $billing);

    $activity = (string) file_get_contents($projectRoot . '/html/settings/_partials/panel_account_activity.php');
    $this->assertStringContainsString('id="account_activity_status"', $activity);

    $danger = (string) file_get_contents($projectRoot . '/html/settings/_partials/panel_account_danger.php');
    $this->assertStringContainsString('id="danger_delete_data_pill"', $danger);
  }
}
