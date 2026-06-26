<?php declare(strict_types=1);

use PayCal\Domain\ContrastColor;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class CalendarEarningsBadgeContrastTest extends TestCase
{
  private const PAYCAL_CONTRAST_MIN = 4.75;

  /** @var array<string, string> */
  private const BADGE_BG_TOKENS = [
    'gross' => '--calendar-earnings-badge-gross-bg',
    'net' => '--calendar-earnings-badge-net-bg',
    'deductions' => '--calendar-earnings-badge-deductions-bg',
    'entries' => '--calendar-earnings-badge-entries-bg',
    'hours' => '--calendar-earnings-badge-hours-bg',
  ];

  #[Test]
  public function calendarCssUsesSemanticEarningsBadgeTokens(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $calendarCss = (string) file_get_contents($projectRoot . '/html/css/calendar/index.php');
    $tokensCss = (string) file_get_contents($projectRoot . '/html/css/tokens/index.php');

    foreach (self::BADGE_BG_TOKENS as $token) {
      $this->assertStringContainsString($token, $tokensCss, "Missing token {$token} in tokens scaffold");
      $this->assertStringContainsString($token, $calendarCss, "Calendar CSS should reference {$token}");
    }

    $this->assertStringContainsString('contrast-color(var(--earnings-badge-bg)', $calendarCss);
    $this->assertMatchesRegularExpression(
      '/\.calendar_earnings_badge_deductions\s*\{[^}]*--earnings-badge-bg:\s*var\(--calendar-earnings-badge-deductions-bg\)/s',
      $calendarCss,
      'Deductions badge should use semantic token, not pie-graph tint'
    );
    $this->assertDoesNotMatchRegularExpression(
      '/\.calendar_earnings_badge\s*\{[^}]*color:\s*var\(--work-entry-fore/s',
      $calendarCss,
      'Earnings badges must not fall back to work-entry-fore before contrast-color'
    );
    $this->assertDoesNotMatchRegularExpression(
      '/\.datagrid_month_value\.entries-badge\s*\{[^}]*color:\s*var\(--work-entry-fore/s',
      $calendarCss
    );
    $this->assertDoesNotMatchRegularExpression(
      '/\.datagrid_month_value\.hours-badge\s*\{[^}]*color:\s*var\(--work-entry-fore/s',
      $calendarCss
    );
  }

  #[Test]
  public function calendarEarningsBadgesMeetPayCalContrastAcrossThemes(): void
  {
    $failures = [];

    foreach ($this->themeTokenMaps() as $theme => $tokenMap) {
      foreach (self::BADGE_BG_TOKENS as $badge => $bgToken) {
        $background = $this->resolveTokenToHex($bgToken, $tokenMap);
        if ($background === null) {
          $failures[] = "{$theme} {$badge}: unresolved {$bgToken}";
          continue;
        }

        $foreground = ContrastColor::foregroundForBackground($background);
        $ratio = ContrastColor::contrastRatio($foreground, $background);

        if ($ratio < self::PAYCAL_CONTRAST_MIN) {
          $failures[] = sprintf(
            '%s %s → %s on %s (%.2f:1)',
            $theme,
            $badge,
            $foreground,
            $background,
            $ratio
          );
        }
      }
    }

    $this->assertSame(
      [],
      $failures,
      'Calendar earnings badges below PayCal contrast minimum ('.self::PAYCAL_CONTRAST_MIN.':1):'."\n".implode("\n", $failures)
    );
  }

  /**
   * @return array<string, array<string, string>>
   */
  private function themeTokenMaps(): array
  {
    $projectRoot = dirname(__DIR__, 4);
    $cssRoot = $projectRoot . '/html/css';
    $baseTokens = $this->parseTokenMap((string) file_get_contents($cssRoot . '/tokens/index.php'));
    $maps = [];

    foreach (scandir($cssRoot) ?: [] as $entry) {
      if (!preg_match('/_(dark|light)$/', $entry)) {
        continue;
      }

      $file = $cssRoot . '/' . $entry . '/index.php';
      if (!is_file($file)) {
        continue;
      }

      $themeTokens = $this->parseTokenMap((string) file_get_contents($file));
      $maps[$entry] = array_merge($baseTokens, $themeTokens);
    }

    $this->assertNotEmpty($maps);

    return $maps;
  }

  /**
   * @return array<string, string>
   */
  private function parseTokenMap(string $source): array
  {
    $map = [];
    if (preg_match_all('/^\s*(--[a-z0-9-]+)\s*:\s*([^;]+);/m', $source, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $map[$match[1]] = trim($match[2]);
      }
    }

    return $map;
  }

  /**
   * @param array<string, string> $tokenMap
   */
  private function resolveTokenToHex(string $token, array $tokenMap): ?string
  {
    $raw = $tokenMap[$token] ?? null;
    if ($raw === null) {
      return null;
    }

    return $this->resolveExpressionToHex($raw, $tokenMap);
  }

  /**
   * @param array<string, string> $tokenMap
   */
  private function resolveExpressionToHex(string $raw, array $tokenMap, int $depth = 0): ?string
  {
    if ($depth > 16) {
      return null;
    }

    $raw = trim($raw);

    if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $raw) === 1) {
      return ContrastColor::normalizeHex($raw);
    }

    if (preg_match('/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)(?:\s*,\s*([\d.]+))?\s*\)$/i', $raw, $match) === 1) {
      $r = (int) round((float) $match[1]);
      $g = (int) round((float) $match[2]);
      $b = (int) round((float) $match[3]);
      $alpha = isset($match[4]) ? (float) $match[4] : 1.0;
      if ($alpha < 1.0) {
        $backdrop = $this->resolveExpressionToHex('var(--color-bg)', $tokenMap, $depth + 1) ?? '#000000';
        $bg = ContrastColor::normalizeHex($backdrop);
        $br = hexdec(substr($bg, 1, 2));
        $bgG = hexdec(substr($bg, 3, 2));
        $bb = hexdec(substr($bg, 5, 2));
        $r = (int) round(($r * $alpha) + ($br * (1.0 - $alpha)));
        $g = (int) round(($g * $alpha) + ($bgG * (1.0 - $alpha)));
        $b = (int) round(($b * $alpha) + ($bb * (1.0 - $alpha)));
      }

      return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    if (preg_match('/^hsla?\(\s*([^)]+)\)$/i', $raw, $match) === 1) {
      $rgb = $this->hslExpressionToRgb($match[1], $tokenMap, $depth + 1);
      if ($rgb === null) {
        return null;
      }

      return sprintf('#%02X%02X%02X', $rgb['r'], $rgb['g'], $rgb['b']);
    }

    if (preg_match('/^var\(/i', $raw) === 1) {
      $inner = preg_replace('/^var\(/i', '', $raw);
      $inner = preg_replace('/\)\s*$/', '', $inner);
      [$tokenRef, $fallback] = $this->splitTopLevelComma($inner);
      $token = trim($tokenRef);

      if (str_starts_with($token, '--') && isset($tokenMap[$token])) {
        $resolved = $this->resolveExpressionToHex($tokenMap[$token], $tokenMap, $depth + 1);
        if ($resolved !== null) {
          return $resolved;
        }
      }

      if ($fallback !== null && trim($fallback) !== '') {
        return $this->resolveExpressionToHex(trim($fallback), $tokenMap, $depth + 1);
      }

      return null;
    }

    if (preg_match('/^color-mix\(\s*in\s+srgb\s*,\s*(.+)\)$/i', $raw, $match) === 1) {
      return $this->resolveColorMix($match[1], $tokenMap, $depth + 1);
    }

    return null;
  }

  /**
   * @param array<string, string> $tokenMap
   */
  private function resolveColorMix(string $body, array $tokenMap, int $depth): ?string
  {
    $parts = preg_split('/\s*,\s*/', trim($body)) ?: [];
    if (count($parts) < 2) {
      return null;
    }

    $resolved = [];
    foreach ($parts as $part) {
      if (preg_match('/^(.+?)\s+([\d.]+)%$/', trim($part), $match) === 1) {
        $hex = $this->resolveExpressionToHex(trim($match[1]), $tokenMap, $depth + 1);
        if ($hex === null) {
          return null;
        }
        $resolved[] = ['hex' => $hex, 'pct' => (float) $match[2]];
        continue;
      }

      $hex = $this->resolveExpressionToHex(trim($part), $tokenMap, $depth + 1);
      if ($hex === null) {
        return null;
      }
      $resolved[] = ['hex' => $hex, 'pct' => null];
    }

    if (count($resolved) === 2 && $resolved[0]['pct'] !== null && $resolved[1]['pct'] === null) {
      $resolved[1]['pct'] = 100.0 - $resolved[0]['pct'];
    }

    if (count($resolved) !== 2 || $resolved[0]['pct'] === null || $resolved[1]['pct'] === null) {
      return null;
    }

    $total = $resolved[0]['pct'] + $resolved[1]['pct'];
    if ($total <= 0) {
      return null;
    }

    $weightA = $resolved[0]['pct'] / $total;

    return ContrastColor::mixHex($resolved[0]['hex'], $resolved[1]['hex'], $weightA * 100.0);
  }

  /**
   * @return array{r: int, g: int, b: int}|null
   */
  private function hslExpressionToRgb(string $body, array $tokenMap, int $depth): ?array
  {
    $alpha = 1.0;
    if (str_contains($body, '/')) {
      [$body, $alphaPart] = array_map('trim', explode('/', $body, 2));
      $alphaPart = trim($alphaPart);
      if (str_ends_with($alphaPart, '%')) {
        $alpha = (float) rtrim($alphaPart, '%') / 100.0;
      } else {
        $alpha = (float) $alphaPart;
      }
    }

    $parts = preg_split('/\s*,\s*|\s+/', trim($body)) ?: [];
    if (count($parts) < 3) {
      return null;
    }

    $h = (float) str_replace('deg', '', $parts[0]);
    $s = (float) rtrim($parts[1], '%') / 100.0;
    $l = (float) rtrim($parts[2], '%') / 100.0;

    if ($s === 0.0) {
      $channel = (int) round($l * 255);
      $rgb = ['r' => $channel, 'g' => $channel, 'b' => $channel];
    } else {
      $hue2rgb = static function (float $p, float $q, float $t): float {
        if ($t < 0.0) {
          $t += 1.0;
        } elseif ($t > 1.0) {
          $t -= 1.0;
        }

        if ($t < 1.0 / 6.0) {
          return $p + (($q - $p) * 6.0 * $t);
        }
        if ($t < 1.0 / 2.0) {
          return $q;
        }
        if ($t < 2.0 / 3.0) {
          return $p + (($q - $p) * ((2.0 / 3.0) - $t) * 6.0);
        }

        return $p;
      };

      $q = $l < 0.5 ? $l * (1.0 + $s) : ($l + $s) - ($l * $s);
      $p = (2.0 * $l) - $q;
      $hk = $h / 360.0;
      $rgb = [
        'r' => (int) round($hue2rgb($p, $q, $hk + (1.0 / 3.0)) * 255.0),
        'g' => (int) round($hue2rgb($p, $q, $hk) * 255.0),
        'b' => (int) round($hue2rgb($p, $q, $hk - (1.0 / 3.0)) * 255.0),
      ];
    }

    if ($alpha < 1.0) {
      $backdrop = $this->resolveExpressionToHex('var(--color-bg)', $tokenMap, $depth) ?? '#000000';
      $bg = ContrastColor::normalizeHex($backdrop);
      $br = hexdec(substr($bg, 1, 2));
      $bgG = hexdec(substr($bg, 3, 2));
      $bb = hexdec(substr($bg, 5, 2));
      $rgb['r'] = (int) round(($rgb['r'] * $alpha) + ($br * (1.0 - $alpha)));
      $rgb['g'] = (int) round(($rgb['g'] * $alpha) + ($bgG * (1.0 - $alpha)));
      $rgb['b'] = (int) round(($rgb['b'] * $alpha) + ($bb * (1.0 - $alpha)));
    }

    return $rgb;
  }

  /**
   * @return array{0: string, 1: ?string}
   */
  private function splitTopLevelComma(string $input): array
  {
    $depth = 0;
    $length = strlen($input);
    for ($i = 0; $i < $length; $i++) {
      $ch = $input[$i];
      if ($ch === '(') {
        $depth++;
      } elseif ($ch === ')') {
        $depth = max(0, $depth - 1);
      } elseif ($ch === ',' && $depth === 0) {
        return [substr($input, 0, $i), substr($input, $i + 1)];
      }
    }

    return [$input, null];
  }
}
