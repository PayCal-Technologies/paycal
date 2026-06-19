<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class ThemeButtonTokenContractTest extends TestCase
{
  private const SCOPED_THEMES = [
    'akira_dark', 'akira_light', 'alien_dark', 'alien_light',
    'beos_dark', 'beos_light', 'blade_runner_dark', 'blade_runner_light',
    'bluejeans_dark', 'bluejeans_light', 'debian_dark', 'debian_light',
    'dune_dark', 'dune_light', 'fedora_dark', 'fedora_light',
    'fifth_element_dark', 'fifth_element_light', 'garden_dark', 'garden_light',
    'haiku_dark', 'haiku_light', 'linux_dark', 'linux_light',
    'macos_dark', 'macos_light', 'macos9_dark', 'macos9_light',
    'matrix_dark', 'matrix_light', 'mint_dark', 'mint_light',
    'paycal_black_light', 'retro_dark', 'retro_light',
    'space_odyssey_dark', 'space_odyssey_light', 'star_trek_dark', 'star_trek_light',
    'star_wars_dark', 'star_wars_light', 'system7_dark', 'system7_light',
    'system8_dark', 'system8_light', 'tron_dark', 'tron_light',
    'win10_dark', 'win10_light', 'win98_dark', 'winxp_dark', 'winxp_light',
    'zeta_dark', 'zeta_light',
  ];

  private const EXEMPT_HEX_THEMES = [
    'arcade_dark',
    'arcade_light',
  ];

  #[Test]
  public function buttonHoverAndActiveTokensAreSemanticAcrossThemes(): void
  {
    $themeRoot = dirname(__DIR__, 3) . '/css';
    $entries = scandir($themeRoot);
    $this->assertIsArray($entries);

    $checked = 0;

    foreach ($entries as $entry) {
      if ($entry === '.' || $entry === '..') {
        continue;
      }

      $themeDir = $themeRoot . '/' . $entry;
      $themeFile = $themeDir . '/index.php';

      if (!is_dir($themeDir) || !is_file($themeFile) || $entry === 'common') {
        continue;
      }

      if (!in_array($entry, self::SCOPED_THEMES, true)) {
        continue;
      }

      $css = (string) file_get_contents($themeFile);
      if ($css === '') {
        continue;
      }

      preg_match('/--button-bg-hover:\s*([^;]+);/i', $css, $hoverMatch);
      preg_match('/--button-bg-active:\s*([^;]+);/i', $css, $activeMatch);

      $hoverValue = trim((string) ($hoverMatch[1] ?? ''));
      $activeValue = trim((string) ($activeMatch[1] ?? ''));

      if ($hoverValue === '' || $activeValue === '') {
        continue;
      }

      if (in_array($entry, self::EXEMPT_HEX_THEMES, true)) {
        continue;
      }

      $semanticPattern = '/^(var\(|color-mix\(|hsl\(|oklch\()/i';
      $hoverIsHex = (bool) preg_match('/^#[0-9a-f]{3,8}$/i', $hoverValue);
      $activeIsHex = (bool) preg_match('/^#[0-9a-f]{3,8}$/i', $activeValue);

      $this->assertMatchesRegularExpression($semanticPattern, $hoverValue, "Non-semantic hover token in {$entry}: {$hoverValue}");
      $this->assertMatchesRegularExpression($semanticPattern, $activeValue, "Non-semantic active token in {$entry}: {$activeValue}");
      $this->assertFalse($hoverIsHex, "Hover token should not be raw hex in {$entry}: {$hoverValue}");
      $this->assertFalse($activeIsHex, "Active token should not be raw hex in {$entry}: {$activeValue}");

      $checked++;
    }

    $this->assertGreaterThan(45, $checked, 'Expected to validate semantic tokens for the recent theme conversion scope.');
  }

  #[Test]
  public function accentPresetSwatchesUseCentralPresetTokens(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $defaults = (string) file_get_contents($projectRoot . '/html/src/Domain/UserPreferenceDefaults.php');
    $settings = (string) file_get_contents($projectRoot . '/html/settings/_partials/panel_appearance_theme.php');
    $controller = (string) file_get_contents($projectRoot . '/html/src/Controllers/SettingsController.php');
    $commonCss = (string) file_get_contents($projectRoot . '/html/css/common/index.php');

    $this->assertStringContainsString('public const ACCENT_PRESETS = [', $defaults);
    $this->assertStringContainsString("public const DEFAULT_ACCENT_PRESET = 'blue';", $defaults);
    $this->assertStringContainsString("'red' => ['label' => 'Red', 'hex' => '#EF4444']", $defaults);
    $this->assertStringContainsString("'blue' => ['label' => 'Blue', 'hex' => '#3B82F6']", $defaults);
    $this->assertStringContainsString("'rose' => ['label' => 'Rose', 'hex' => '#F43F5E']", $defaults);
    $this->assertSame(16, substr_count($defaults, "'hex' =>"), 'Accent presets should stay intentionally smaller than the 20 site-color swatches.');
    $this->assertStringContainsString('$accentPresets = UserPreferenceDefaults::accentPresets();', $settings);
    $this->assertStringContainsString('id="accent_preset_swatches"', $settings);
    $this->assertStringContainsString('id="accent_preset_preview"', $settings);
    $this->assertStringContainsString('$allowed = array_keys(UserPreferenceDefaults::accentPresets());', $controller);
    $this->assertStringContainsString('.settings_accent_swatch[data-accent-idx=', $commonCss);
    $this->assertStringContainsString('--color-accent: var(--accent-color);', $commonCss);
    $this->assertStringContainsString('--calendar-day-selected:', $commonCss);
    $this->assertStringContainsString('background: {$accentHex};', $commonCss);
  }
}
