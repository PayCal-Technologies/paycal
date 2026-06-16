<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SettingsJsContractTest extends TestCase
{
  #[Test]
  public function settingsJsDefinesFormatSettingsMessageAndDiagnosticsHandlers(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $js = (string) file_get_contents($projectRoot . '/html/js/settings/index.php');

    $this->assertStringContainsString('const formatSettingsMessage =', $js);
    $this->assertStringContainsString('settings_copy_support_info_btn', $js);
    $this->assertStringContainsString('settings_export_debug_bundle_btn', $js);
    $this->assertStringContainsString('SETTINGS_T.SETTINGS_JS_SUPPORT_INFO_LABEL', $js);
    $this->assertStringContainsString('SETTINGS_T.SETTINGS_JS_DIAGNOSTICS_BUNDLE_COPIED', $js);
    $this->assertStringContainsString('settings_voice_preview_btn', $js);
    $this->assertStringContainsString('voice_volume', $js);
  }
}
