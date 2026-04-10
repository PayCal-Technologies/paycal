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
}
