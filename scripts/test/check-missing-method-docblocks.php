<?php declare(strict_types=1);

/**
 * check-missing-method-docblocks.php
 *
 * Purpose: Block regressions where methods in html/src are added without PHPDoc.
 * Usage: php scripts/test/check-missing-method-docblocks.php
 * Why here: This repository keeps source quality checks in scripts/test for CI and hooks.
 */

$root = dirname(__DIR__, 2) . '/html/src';
if (!is_dir($root)) {
  fwrite(STDERR, "Source root not found: {$root}\n");
  exit(1);
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$failures = [];

/**
 * Returns true if a declaration at token index has a nearby docblock above it.
 *
 * Attribute lines and visibility/static/final tokens are ignored while scanning upward.
 */
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

foreach ($iterator as $file) {
  if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
    continue;
  }

  $path = $file->getPathname();
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

    $name = '';
    $line = $token[2];
    for ($j = $i + 1; $j < $tokenCount; $j++) {
      if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
        continue;
      }
      if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
        $name = $tokens[$j][1];
        break;
      }
      if (is_string($tokens[$j]) && $tokens[$j] === '(') {
        // Closure; no docblock requirement.
        $name = '';
        break;
      }
    }

    if ($name === '') {
      continue;
    }

    if ($hasDocblockBefore($tokens, $i) === false) {
      $failures[] = [
        'path' => $path,
        'line' => $line,
        'method' => $name,
      ];
    }
  }
}

if ($failures === []) {
  echo "OK: no missing method docblocks detected in html/src.\n";
  exit(0);
}

fwrite(STDERR, "Missing method docblocks detected:\n");
foreach ($failures as $failure) {
  $relative = str_replace(dirname(__DIR__, 2) . '/', '', $failure['path']);
  fwrite(STDERR, sprintf("- %s:%d (%s)\n", $relative, $failure['line'], $failure['method']));
}

exit(1);
