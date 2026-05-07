#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * check-semantic-diff.php
 *
 * Purpose: reject staged or ranged PHP diffs that look like formatter-driven
 * semantic rewrites rather than layout-only cleanup.
 *
 * Usage:
 * - php scripts/check-semantic-diff.php --cached
 * - php scripts/check-semantic-diff.php --cached --paths-file /tmp/php-paths.txt
 * - php scripts/check-semantic-diff.php --base-ref origin/main --head-ref HEAD
 *
 * Why this exists:
 * - PayCal allows autofix only for AST-preserving formatting changes.
 * - Some formatter rules can mutate call signatures, collapse control flow,
 *   merge statements, or remove PHPStan type-narrowing patterns.
 * - This scanner is intentionally conservative and fails closed on known
 *   high-risk rewrite signatures.
 */

final class SemanticDiffScanner
{
  /** @var list<string> */
  private array $issues = [];

  public function run(array $argv): int
  {
    $options = $this->parseArgs($argv);
    $diff = $this->loadDiff($options);
    $this->scanDiff($diff);

    if ($this->issues === []) {
      fwrite(STDOUT, "[ok] semantic diff scan passed\n");

      return 0;
    }

    fwrite(STDERR, "[fatal] semantic-looking PHP diff detected\n");
    foreach ($this->issues as $issue) {
      fwrite(STDERR, ' - ' . $issue . "\n");
    }
    fwrite(STDERR, "[fatal] review and split refactoring from formatting before commit\n");

    return 1;
  }

  /**
   * @return array{cached: bool, baseRef: ?string, headRef: ?string, paths: list<string>}
   */
  private function parseArgs(array $argv): array
  {
    $cached = false;
    $baseRef = null;
    $headRef = null;
    $pathsFile = null;

    for ($i = 1, $count = count($argv); $i < $count; ++$i) {
      $arg = $argv[$i];
      switch ($arg) {
        case '--cached':
          $cached = true;
          break;

        case '--base-ref':
          $baseRef = $argv[$i + 1] ?? null;
          ++$i;
          break;

        case '--head-ref':
          $headRef = $argv[$i + 1] ?? null;
          ++$i;
          break;

        case '--paths-file':
          $pathsFile = $argv[$i + 1] ?? null;
          ++$i;
          break;

        case '--help':
        case '-h':
          $this->printHelp();
          exit(0);

        default:
          throw new InvalidArgumentException('Unknown argument: ' . $arg);
      }
    }

    if (!$cached && ($baseRef === null || $headRef === null)) {
      $cached = true;
    }

    $paths = [];
    if (is_string($pathsFile) && $pathsFile !== '') {
      $raw = @file($pathsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      if (is_array($raw)) {
        foreach ($raw as $path) {
          $trimmed = trim($path);
          if ($trimmed !== '') {
            $paths[] = $trimmed;
          }
        }
      }
    }

    return [
      'cached' => $cached,
      'baseRef' => $baseRef,
      'headRef' => $headRef,
      'paths' => $paths,
    ];
  }

  private function printHelp(): void
  {
    fwrite(STDOUT, "Usage: php scripts/check-semantic-diff.php [--cached] [--base-ref <ref>] [--head-ref <ref>] [--paths-file <file>]\n");
  }

  /**
   * @param array{cached: bool, baseRef: ?string, headRef: ?string, paths: list<string>} $options
   */
  private function loadDiff(array $options): string
  {
    $command = ['git', '--no-pager', 'diff', '--no-ext-diff', '--unified=0'];

    if ($options['cached']) {
      $command[] = '--cached';
    } elseif ($options['baseRef'] !== null && $options['headRef'] !== null) {
      $command[] = $options['baseRef'] . '..' . $options['headRef'];
    }

    $command[] = '--';
    if ($options['paths'] !== []) {
      foreach ($options['paths'] as $path) {
        $command[] = $path;
      }
    }

    $escaped = array_map('escapeshellarg', $command);
    $output = shell_exec(implode(' ', $escaped));

    return is_string($output) ? $output : '';
  }

  private function scanDiff(string $diff): void
  {
    $lines = preg_split('/\r?\n/', $diff) ?: [];
    $currentFile = null;
    $added = [];
    $removed = [];

    $flush = function () use (&$currentFile, &$added, &$removed): void {
      if ($currentFile === null || !$this->isPhpPath($currentFile)) {
        $added = [];
        $removed = [];
        return;
      }

      $this->scanHunk($currentFile, $removed, $added);
      $added = [];
      $removed = [];
    };

    foreach ($lines as $line) {
      if (str_starts_with($line, 'diff --git ')) {
        $flush();
        if (preg_match('# b/(.+)$#', $line, $matches) === 1) {
          $currentFile = $matches[1];
        } else {
          $currentFile = null;
        }
        continue;
      }

      if (str_starts_with($line, '@@ ')) {
        $flush();
        continue;
      }

      if (str_starts_with($line, '+++') || str_starts_with($line, '---')) {
        continue;
      }

      if ($currentFile === null || !$this->isPhpPath($currentFile)) {
        continue;
      }

      if (str_starts_with($line, '+')) {
        $added[] = substr($line, 1);
        continue;
      }

      if (str_starts_with($line, '-')) {
        $removed[] = substr($line, 1);
      }
    }

    $flush();
  }

  /**
   * @param list<string> $removed
   * @param list<string> $added
   */
  private function scanHunk(string $filePath, array $removed, array $added): void
  {
    if ($removed === [] && $added === []) {
      return;
    }

    $removedText = implode("\n", $removed);
    $addedText = implode("\n", $added);

    if (preg_match('/\bfunction\s*\(/', $removedText) === 1 && preg_match('/\bfn\s*\(/', $addedText) === 1) {
      $this->issues[] = $filePath . ': arrow-function rewrite detected';
    }

    if (preg_match('/base64_decode\s*\([^\n]*\)/', $removedText) === 1
      && preg_match('/base64_decode\s*\([^\n]*,\s*true\s*\)/', $addedText) === 1
      && preg_match('/base64_decode\s*\([^\n]*,\s*true\s*\)/', $removedText) !== 1
    ) {
      $this->issues[] = $filePath . ': base64_decode strict-parameter mutation detected';
    }

    if (preg_match('/\/\*\*\s*@var\s+/', $removedText) === 1 && preg_match('/\/\*\*\s*@var\s+/', $addedText) !== 1) {
      $this->issues[] = $filePath . ': PHPDoc @var narrowing removal detected';
    }

    if (preg_match('/\b(?:unset|isset)\s*\([^\)]*,[^\)]*\)/', $addedText) === 1) {
      $this->issues[] = $filePath . ': merged unset/isset statement detected';
    }

    if (preg_match('/\bif\s*\(/', $removedText) === 1
      && preg_match('/\breturn\s+false;|\breturn\s+true;/', $removedText) === 1
      && preg_match('/\breturn\s+!?\s*\(/', $addedText) === 1
    ) {
      $this->issues[] = $filePath . ': control-flow simplification detected';
    }

    $removedAssignments = $this->collectAssignedReturnVariables($removed);
    if ($removedAssignments !== []) {
      foreach ($removedAssignments as $variable) {
        if (preg_match('/return\s+\$' . preg_quote($variable, '/') . '\s*;/', $addedText) !== 1
          && preg_match('/\$' . preg_quote($variable, '/') . '\s*=/', $addedText) !== 1
          && preg_match('/\breturn\s+/', $addedText) === 1
        ) {
          $this->issues[] = $filePath . ': variable inlining around $' . $variable . ' detected';
        }
      }
    }
  }

  /**
   * @param list<string> $lines
   * @return list<string>
   */
  private function collectAssignedReturnVariables(array $lines): array
  {
    $assigned = [];
    $returned = [];

    foreach ($lines as $line) {
      if (preg_match('/\$(\w+)\s*=/', $line, $assignment) === 1) {
        $assigned[] = $assignment[1];
      }

      if (preg_match('/return\s+\$(\w+)\s*;/', $line, $returnMatch) === 1) {
        $returned[] = $returnMatch[1];
      }
    }

    return array_values(array_unique(array_intersect($assigned, $returned)));
  }

  private function isPhpPath(string $filePath): bool
  {
    return str_ends_with($filePath, '.php');
  }
}

try {
  $scanner = new SemanticDiffScanner();
  exit($scanner->run($argv));
} catch (Throwable $e) {
  fwrite(STDERR, '[fatal] semantic diff scanner failed: ' . $e->getMessage() . "\n");
  exit(1);
}