<?php declare(strict_types=1);

/**
 * list-missing-method-docblocks.php
 *
 * Purpose: Stage 1 docblock gate helper that compiles a report of missing
 * method docblocks in html/src.
 * Usage: php scripts/test/list-missing-method-docblocks.php [--output /path/report.json] [--paths-file /path/staged.txt]
 * Why here: scripts/test hosts shared quality checks used by hooks and CI.
 */

$repoRoot = dirname(__DIR__, 2);
$sourceRoot = $repoRoot . '/html/src';
if (!is_dir($sourceRoot)) {
  fwrite(STDERR, "Source root not found: {$sourceRoot}\n");
  exit(1);
}

$outputPath = null;
$pathsFile = null;
for ($i = 1; $i < $argc; $i++) {
  $arg = $argv[$i] ?? '';
  if ($arg === '--output') {
    $candidate = $argv[$i + 1] ?? '';
    if (!is_string($candidate) || $candidate === '') {
      fwrite(STDERR, "Missing value for --output\n");
      exit(1);
    }
    $outputPath = $candidate;
    $i++;
    continue;
  }

  if ($arg === '--paths-file') {
    $candidate = $argv[$i + 1] ?? '';
    if (!is_string($candidate) || $candidate === '') {
      fwrite(STDERR, "Missing value for --paths-file\n");
      exit(1);
    }
    $pathsFile = $candidate;
    $i++;
    continue;
  }

  fwrite(STDERR, "Unknown argument: {$arg}\n");
  exit(1);
}

$pathFilter = null;
if ($pathsFile !== null) {
  if (!is_file($pathsFile)) {
    fwrite(STDERR, "Paths file not found: {$pathsFile}\n");
    exit(1);
  }

  $pathFilter = [];
  $pathsRaw = file($pathsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  if (!is_array($pathsRaw)) {
    fwrite(STDERR, "Failed to read paths file: {$pathsFile}\n");
    exit(1);
  }

  foreach ($pathsRaw as $pathRaw) {
    $relativePath = trim((string) $pathRaw);
    if ($relativePath === '') {
      continue;
    }
    $pathFilter[$relativePath] = true;
  }
}

$hasDocblockBefore = static function (array $tokens, int $index): bool {
  for ($j = $index - 1; $j >= 0; $j--) {
    $token = $tokens[$j];

    if (is_array($token)) {
      $id = $token[0];
      if ($id === T_WHITESPACE || $id === T_COMMENT) {
        continue;
      }
      if ($id === T_DOC_COMMENT) {
        return true;
      }
      if (in_array($id, [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_ABSTRACT, T_FINAL, T_STATIC, T_READONLY], true)) {
        continue;
      }
      break;
    }

    if (is_string($token) && trim($token) === '') {
      continue;
    }

    // Skip attribute groups #[...]
    if ($token === ']') {
      $depth = 1;
      for ($k = $j - 1; $k >= 0; $k--) {
        if (!is_string($tokens[$k])) {
          continue;
        }
        if ($tokens[$k] === ']') {
          $depth++;
        } elseif ($tokens[$k] === '[') {
          $depth--;
          if ($depth === 0) {
            $j = $k;
            break;
          }
        }
      }
      continue;
    }

    break;
  }

  return false;
};

$computeInsertionLine = static function (array $tokens, int $index, int $fallbackLine): int {
  $line = $fallbackLine;
  for ($j = $index - 1; $j >= 0; $j--) {
    $token = $tokens[$j];

    if (is_array($token)) {
      $id = $token[0];
      if ($id === T_WHITESPACE || $id === T_COMMENT) {
        continue;
      }
      if ($id === T_DOC_COMMENT) {
        return $line;
      }
      if (in_array($id, [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_ABSTRACT, T_FINAL, T_STATIC, T_READONLY], true)) {
        $line = $token[2];
        continue;
      }
      break;
    }

    if (is_string($token) && trim($token) === '') {
      continue;
    }

    if ($token === ']') {
      for ($k = $j - 1; $k >= 0; $k--) {
        $candidate = $tokens[$k];
        if (is_array($candidate)) {
          $line = $candidate[2];
          break;
        }
      }
      $depth = 1;
      for ($k = $j - 1; $k >= 0; $k--) {
        if (!is_string($tokens[$k])) {
          continue;
        }
        if ($tokens[$k] === ']') {
          $depth++;
        } elseif ($tokens[$k] === '[') {
          $depth--;
          if ($depth === 0) {
            $j = $k;
            break;
          }
        }
      }
      continue;
    }

    break;
  }

  return max(1, $line);
};

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceRoot));
$entries = [];

foreach ($iterator as $file) {
  if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
    continue;
  }

  $path = $file->getPathname();
  $relativePath = str_replace($repoRoot . '/', '', $path);
  if (is_array($pathFilter) && !isset($pathFilter[$relativePath])) {
    continue;
  }

  $code = file_get_contents($path);
  if ($code === false) {
    continue;
  }

  $tokens = token_get_all($code);
  $tokenCount = count($tokens);
  $classDepth = 0;
  $pendingClassBrace = false;

  for ($i = 0; $i < $tokenCount; $i++) {
    $token = $tokens[$i];

    if (is_array($token) && in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
      $pendingClassBrace = true;
      continue;
    }

    if ($token === '{') {
      if ($pendingClassBrace) {
        $classDepth++;
        $pendingClassBrace = false;
        continue;
      }
      if ($classDepth > 0) {
        $classDepth++;
      }
      continue;
    }

    if ($token === '}') {
      if ($classDepth > 0) {
        $classDepth--;
      }
      continue;
    }

    if (!is_array($token) || $token[0] !== T_FUNCTION || $classDepth <= 0) {
      continue;
    }

    $method = '';
    $line = (int) $token[2];
    for ($j = $i + 1; $j < $tokenCount; $j++) {
      if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
        continue;
      }
      if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
        $method = (string) $tokens[$j][1];
        break;
      }
      if (is_string($tokens[$j]) && $tokens[$j] === '(') {
        $method = '';
        break;
      }
    }

    if ($method === '') {
      continue;
    }

    if ($hasDocblockBefore($tokens, $i)) {
      continue;
    }

    $entries[] = [
      'path' => $relativePath,
      'method' => $method,
      'line' => $line,
      'insertion_line' => $computeInsertionLine($tokens, $i, $line),
    ];
  }
}

$files = [];
foreach ($entries as $entry) {
  $files[$entry['path']] = true;
}

$report = [
  'generated_at' => gmdate('c'),
  'source_root' => 'html/src',
  'total_missing_methods' => count($entries),
  'total_files' => count($files),
  'files' => array_keys($files),
  'entries' => $entries,
];

if ($outputPath !== null) {
  $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  if (!is_string($json)) {
    fwrite(STDERR, "Failed to encode report JSON\n");
    exit(1);
  }

  if (file_put_contents($outputPath, $json . "\n") === false) {
    fwrite(STDERR, "Failed to write report: {$outputPath}\n");
    exit(1);
  }

  echo "Missing docblock report written: {$outputPath}\n";
}

echo "Missing method docblocks: " . count($entries) . " across " . count($files) . " files.\n";
foreach (array_keys($files) as $relativePath) {
  echo "- {$relativePath}\n";
}

exit(0);
