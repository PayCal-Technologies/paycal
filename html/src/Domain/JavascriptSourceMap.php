<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;

/**
 * Dev/mac-only source map tracing for PHP-assembled JS bundles.
 *
 * Production bundles are per-user (i18n, prefs) and must not ship maps.
 * When enabled, segments are traced during render and an inline data-URL map
 * is appended so DevTools / Lighthouse can resolve .js.php sources.
 */
final class JavascriptSourceMap
{
  private const VLQ_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

  private static ?string $moduleSlug = null;

  /** @var list<array{source: string, generatedLine: int}> */
  private static array $segments = [];

  private static int $generatedLine = 0;

  public static function begin(string $moduleSlug): void
  {
    if (!Environment::isPhpJsSourceMapsEnabled()) {
      return;
    }

    self::$moduleSlug = $moduleSlug;
    self::$segments = [];
    self::$generatedLine = 0;
  }

  /**
   * Require a .js.php segment and record its generated line offset for the map.
   */
  public static function emitSegment(string $absolutePath): void
  {
    if (!Environment::isPhpJsSourceMapsEnabled()) {
      require $absolutePath;

      return;
    }

    if (self::$moduleSlug === null) {
      require $absolutePath;

      return;
    }

    ob_start();
    require $absolutePath;
    $content = (string) ob_get_clean();

    self::$segments[] = [
      'source' => self::toPublicSourcePath($absolutePath),
      'generatedLine' => self::$generatedLine,
    ];

    self::$generatedLine += self::countLines($content);
    echo $content;
  }

  /**
   * Append an inline //# sourceMappingURL=data:… comment (dev/mac only).
   */
  public static function finishInlineReference(): void
  {
    if (!Environment::isPhpJsSourceMapsEnabled() || self::$moduleSlug === null || self::$segments === []) {
      return;
    }

    $sources = array_map(static fn (array $segment): string => $segment['source'], self::$segments);
    $map = [
      'version' => 3,
      'file' => self::$moduleSlug . '.js',
      'sources' => $sources,
      'sourcesContent' => self::loadSourcesContent($sources),
      'mappings' => self::buildMappings(),
    ];

    $json = json_encode($map, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $encoded = base64_encode($json);
    echo "\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,{$encoded}\n";
  }

  /**
   * @param list<string> $sources
   *
   * @return list<string|null>
   */
  private static function loadSourcesContent(array $sources): array
  {
    $htmlRoot = dirname(__DIR__, 2);

    return array_map(static function (string $source) use ($htmlRoot): ?string {
      $path = $htmlRoot . '/' . ltrim($source, '/');
      if (!is_file($path)) {
        return null;
      }

      $contents = file_get_contents($path);

      return $contents === false ? null : $contents;
    }, $sources);
  }

  private static function toPublicSourcePath(string $absolutePath): string
  {
    $htmlRoot = realpath(dirname(__DIR__, 2));
    $resolved = realpath($absolutePath);
    if ($htmlRoot === false || $resolved === false) {
      return basename($absolutePath);
    }

    $relative = str_replace('\\', '/', substr($resolved, strlen($htmlRoot) + 1));

    return ltrim($relative, '/');
  }

  private static function countLines(string $content): int
  {
    if ($content === '') {
      return 0;
    }

    return substr_count($content, "\n") + (str_ends_with($content, "\n") ? 0 : 1);
  }

  private static function buildMappings(): string
  {
    $parts = [];
    $previousGeneratedLine = 0;
    $previousSourceIndex = 0;

    foreach (self::$segments as $index => $segment) {
      $generatedLineDelta = $segment['generatedLine'] - $previousGeneratedLine;
      $sourceIndexDelta = $index - $previousSourceIndex;

      $parts[] = self::encodeSegment($generatedLineDelta, 0, $sourceIndexDelta, 0, 0);

      $previousGeneratedLine = $segment['generatedLine'];
      $previousSourceIndex = $index;
    }

    return implode(';', $parts);
  }

  private static function encodeSegment(
    int $generatedLineDelta,
    int $generatedColumnDelta,
    int $sourceIndexDelta,
    int $sourceLineDelta,
    int $sourceColumnDelta,
  ): string {
    return self::toVLQ($generatedLineDelta)
      . self::toVLQ($generatedColumnDelta)
      . self::toVLQ($sourceIndexDelta)
      . self::toVLQ($sourceLineDelta)
      . self::toVLQ($sourceColumnDelta);
  }

  private static function toVLQ(int $value): string
  {
    $vlq = $value < 0 ? ((-$value) << 1) | 1 : ($value << 1);
    $encoded = '';

    do {
      $digit = $vlq & 0x1F;
      $vlq >>= 5;
      if ($vlq > 0) {
        $digit |= 0x20;
      }
      $encoded .= self::VLQ_CHARS[$digit];
    } while ($vlq > 0);

    return $encoded;
  }
}
