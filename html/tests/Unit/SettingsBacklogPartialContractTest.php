<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SettingsBacklogPartialContractTest extends TestCase
{
  private string $projectRoot;

  protected function setUp(): void
  {
    $this->projectRoot = dirname(__DIR__, 3);
  }

  #[Test]
  public function backlogPartialsExposeExpectedControlIds(): void
  {
    $files = [
      'panel_calendar_work_defaults.php' => ['default_site_id', 'default_hours', 'default_travel_hours'],
      'panel_appearance_theme.php' => ['id="density_preset_', 'accent_preset'],
      'panel_appearance_notifications.php' => ['help_popup_timeout_seconds', 'toast_position', 'toast_font_size', 'full-top', 'full-bottom'],
      'panel_appearance_sidebar.php' => ['nav_proximity', 'nav_overlay', 'nav_proximity_px', 'overlay_sidebar_timeout_seconds'],
      'panel_accessibility_extras.php' => ['id="sr_verbosity_', 'settings_show_keyboard_shortcuts_btn', 'high_contrast_enabled_on', 'name="reduced_motion_enabled"'],
      'panel_security_sessions.php' => ['settings_sessions_list', 'settings_revoke_other_sessions_btn'],
      'panel_security_sensitive.php' => ['require_reauth_export', 'require_reauth_import'],
      'panel_calendar.php' => ['calendar_show_gross_badge', 'calendar_show_deductions_badge', 'calendar_highlight_pay_period', 'work_entry_hours', 'work_entry_regular', 'SETTINGS_CALENDAR_LABEL_BADGES', 'SETTINGS_CALENDAR_PAY_PERIOD_BADGE'],
      'panel_data.php' => ['export_section_user', 'settings_export_history_list', 'settings_data_delete_account_btn'],
      'panel_security_passkeys.php' => ['settings_recovery_key_badge'],
      'panel_diagnostics_advanced.php' => ['debug_ttl_minutes'],
    ];

    foreach ($files as $file => $needles) {
      $path = $this->projectRoot . '/html/settings/_partials/' . $file;
      $contents = (string) file_get_contents($path);
      foreach ($needles as $needle) {
        $this->assertStringContainsString($needle, $contents, $needle . ' must exist in ' . $file);
      }
    }
  }
}
