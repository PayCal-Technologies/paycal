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

    foreach (['panel-account', 'label_full_name', 'label_phone', 'label_province', 'call_edit_details_modal'] as $id) {
      $this->assertStringContainsString($id, $profile, $id . ' must exist on account profile partial');
    }

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
