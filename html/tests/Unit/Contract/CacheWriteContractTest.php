<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('contract')]
#[Group('redis-write')]
final class CacheWriteContractTest extends TestCase
{
  #[Test]
  public function hashWritesDoNotSetExpiryInSeparateFollowUpCalls(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $files = $this->productionPhpFiles($projectRoot);

    foreach ($files as $file) {
      $lines = file($file);
      $this->assertIsArray($lines, $file);

      foreach ($lines as $index => $line) {
        if (!preg_match('/Database::hset\(([^,]+),/', $line, $hsetMatch)) {
          continue;
        }

        $hsetKey = $this->normalizeKeyExpression($hsetMatch[1]);
        $lookahead = array_slice($lines, $index + 1, 12, true);
        foreach ($lookahead as $expireIndex => $candidate) {
          if (!preg_match('/Database::expire\(([^,]+),/', $candidate, $expireMatch)) {
            continue;
          }

          $expireKey = $this->normalizeKeyExpression($expireMatch[1]);
          $this->assertNotSame(
            $hsetKey,
            $expireKey,
            sprintf(
              '%s:%d writes a hash then expires the same key at line %d. Use Database::hsetex() so the write and TTL are atomic.',
              $file,
              $index + 1,
              $expireIndex + 1
            )
          );
        }
      }
    }
  }

  /**
   * @return list<string>
   */
  private function productionPhpFiles(string $projectRoot): array
  {
    $roots = [
      $projectRoot . '/html/src',
      $projectRoot . '/html/api',
      $projectRoot . '/html/business',
      $projectRoot . '/html/sites',
      $projectRoot . '/html/settings',
    ];

    $files = [];
    foreach ($roots as $root) {
      if (!is_dir($root)) {
        continue;
      }

      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
      );

      foreach ($iterator as $fileInfo) {
        if (!$fileInfo instanceof SplFileInfo || $fileInfo->getExtension() !== 'php') {
          continue;
        }

        $path = $fileInfo->getPathname();
        if (str_contains($path, '/vendor/')) {
          continue;
        }

        $files[] = $path;
      }
    }

    sort($files);
    return array_values(array_unique($files));
  }

  private function normalizeKeyExpression(string $expression): string
  {
    return preg_replace('/\s+/', '', trim($expression)) ?? trim($expression);
  }
}
