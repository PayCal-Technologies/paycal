<?php declare(strict_types=1);

/**
 * check-duplicate-docblocks.php
 *
 * Purpose: Detect accidental duplicate file/method docblocks in html/src files.
 * Usage: php scripts/test/check-duplicate-docblocks.php
 * Why here: scripts/test is the shared quality-check location used by hooks and CI.
 */

$repoRoot = dirname(__DIR__, 2);
$srcRoot = $repoRoot . '/html/src';
if (!is_dir($srcRoot)) {
  fwrite(STDERR, "Source root not found: {$srcRoot}\n");
  exit(1);
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcRoot));
$failures = [];

/**
 * Normalize docblock text for duplicate comparisons.
 */
$normalizeDocblock = static function (string $docblock): string {
  $text = preg_replace('/\s+/u', ' ', trim($docblock));
  return strtolower((string) $text);
};

foreach ($iterator as $file) {
  if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
    continue;
  }

  $path = $file->getPathname();
  $source = file_get_contents($path);
  if ($source === false) {
    continue;
  }

  $tokens = token_get_all($source);
  $tokenCount = count($tokens);
  $topDocblocks = [];
  $bufferedDocblocks = [];

  // File-level duplicate detection: more than one top-of-file docblock before first namespace/use/class/function.
  for ($i = 0; $i < $tokenCount; $i++) {
    $token = $tokens[$i];
    if (!is_array($token)) {
      continue;
    }

    $id = $token[0];
    if ($id === T_OPEN_TAG || $id === T_WHITESPACE || $id === T_COMMENT) {
      continue;
    }

    if ($id === T_DOC_COMMENT) {
      $normalized = $normalizeDocblock($token[1]);
      if (isset($topDocblocks[$normalized])) {
        $failures[] = [
          'type' => 'duplicate-file-docblock',
          'path' => $path,
          'line' => $token[2],
        ];
      }
      $topDocblocks[$normalized] = true;
      continue;
    }

    if (in_array($id, [T_DECLARE, T_NAMESPACE, T_USE, T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_FUNCTION], true)) {
      break;
    }

    break;
  }

  // Declaration-level duplicate detection: stacked docblocks immediately before class/function declarations.
  for ($i = 0; $i < $tokenCount; $i++) {
    $token = $tokens[$i];

    if (is_array($token) && $token[0] === T_DOC_COMMENT) {
      $bufferedDocblocks[] = [
        'line' => $token[2],
        'normalized' => $normalizeDocblock($token[1]),
      ];
      continue;
    }

    if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT], true)) {
      continue;
    }

    // Allow attributes and common modifiers between docblock and declaration.
    if (is_array($token) && in_array($token[0], [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_ABSTRACT, T_FINAL, T_STATIC, T_READONLY], true)) {
      continue;
    }

    if ($token === '#' || $token === '[' || $token === ']' || $token === '(' || $token === ')' || $token === ',') {
      continue;
    }

    $isDeclaration = is_array($token) && in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_FUNCTION], true);
    if ($isDeclaration && count($bufferedDocblocks) > 1) {
      $last = $bufferedDocblocks[count($bufferedDocblocks) - 1];
      $prev = $bufferedDocblocks[count($bufferedDocblocks) - 2];
      if ($last['normalized'] === $prev['normalized']) {
        $failures[] = [
          'type' => 'duplicate-declaration-docblock',
          'path' => $path,
          'line' => (int) $last['line'],
        ];
      }
    }

    $bufferedDocblocks = [];
  }
}

if ($failures === []) {
  echo "OK: no duplicate docblocks detected in html/src.\n";
  exit(0);
}

fwrite(STDERR, "Duplicate docblocks detected:\n");
foreach ($failures as $failure) {
  $relative = str_replace($repoRoot . '/', '', $failure['path']);
  fwrite(STDERR, sprintf("- %s:%d [%s]\n", $relative, $failure['line'], $failure['type']));
}

exit(1);
