<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * WCAG 2.1 contrast helpers for server-side color decisions (tests, reports).
 */
final class ContrastColor
{
  public static function normalizeHex(string $hex): string
  {
    $hex = ltrim(trim($hex), '#');
    if (strlen($hex) === 3) {
      $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }

    return '#'.strtoupper($hex);
  }

  public static function relativeLuminance(string $hex): float
  {
    $hex = self::normalizeHex($hex);
    $channels = [
      hexdec(substr($hex, 1, 2)) / 255,
      hexdec(substr($hex, 3, 2)) / 255,
      hexdec(substr($hex, 5, 2)) / 255,
    ];

    $linear = [];
    foreach ($channels as $channel) {
      $linear[] = $channel <= 0.03928
        ? $channel / 12.92
        : (($channel + 0.055) / 1.055) ** 2.4;
    }

    return (0.2126 * $linear[0]) + (0.7152 * $linear[1]) + (0.0722 * $linear[2]);
  }

  public static function contrastRatio(string $foreground, string $background): float
  {
    $fg = self::relativeLuminance($foreground);
    $bg = self::relativeLuminance($background);
    $lighter = max($fg, $bg);
    $darker = min($fg, $bg);

    return ($lighter + 0.05) / ($darker + 0.05);
  }

  /**
   * Mix two sRGB hex colors (percent applies to $hexA).
   */
  public static function mixHex(string $hexA, string $hexB, float $percentA): string
  {
    $a = self::normalizeHex($hexA);
    $b = self::normalizeHex($hexB);
    $weight = max(0.0, min(100.0, $percentA)) / 100.0;

    $ar = hexdec(substr($a, 1, 2));
    $ag = hexdec(substr($a, 3, 2));
    $ab = hexdec(substr($a, 5, 2));
    $br = hexdec(substr($b, 1, 2));
    $bg = hexdec(substr($b, 3, 2));
    $bb = hexdec(substr($b, 5, 2));

    $r = (int) round(($ar * $weight) + ($br * (1.0 - $weight)));
    $g = (int) round(($ag * $weight) + ($bg * (1.0 - $weight)));
    $bChannel = (int) round(($ab * $weight) + ($bb * (1.0 - $weight)));

    return sprintf('#%02X%02X%02X', $r, $g, $bChannel);
  }

  /**
   * Pick the higher-contrast of light/dark foreground candidates for a background.
   */
  public static function foregroundForBackground(
    string $background,
    string $light = '#FFFFFF',
    string $dark = '#111111',
  ): string {
    $lightRatio = self::contrastRatio($light, $background);
    $darkRatio = self::contrastRatio($dark, $background);

    return self::normalizeHex($lightRatio >= $darkRatio ? $light : $dark);
  }

  public static function meetsWcagAaNormalText(string $foreground, string $background, float $min = 4.5): bool
  {
    return self::contrastRatio($foreground, $background) >= $min;
  }
}
