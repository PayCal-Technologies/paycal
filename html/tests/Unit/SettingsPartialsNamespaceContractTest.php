<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SettingsPartialsNamespaceContractTest extends TestCase
{
  #[Test]
  public function htmlFirstSettingsPartialsDeclarePayCalDomainNamespace(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $partialsDir = $projectRoot . '/html/settings/_partials';
    $files = [
      'modals.php',
      'panel_calendar.php',
      'panel_calendar_pay_period.php',
      'panel_account.php',
      'panel_account_locale.php',
      'panel_calendar_work_defaults.php',
      'panel_account_billing.php',
      'panel_account_activity.php',
      'panel_account_danger.php',
      'vars_account.php',
      'panel_appearance_theme.php',
      'panel_appearance_sidebar.php',
      'panel_appearance_notifications.php',
      'panel_accessibility_typography.php',
      'panel_accessibility_audio.php',
      'panel_security_passkeys.php',
      'panel_security_timeouts.php',
      'panel_data.php',
      'panel_diagnostics_basic.php',
      'panel_diagnostics_advanced.php',
    ];

    foreach ($files as $file) {
      $path = $partialsDir . '/' . $file;
      $contents = (string) file_get_contents($path);
      $this->assertStringStartsWith(
        "<?php declare(strict_types=1);\n\nnamespace PayCal\\Domain;",
        $contents,
        $file . ' must declare namespace PayCal\\Domain at file start so HTML-first partials resolve settings_index_i18n() and Environment.'
      );
    }
  }
}
