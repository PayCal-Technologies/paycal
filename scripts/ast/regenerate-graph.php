#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * regenerate-graph.php
 *
 * Purpose:
 *   Rebuild tmp/ast/dependency-graph.json from html/src using the same
 *   extraction approach as the AST admin endpoint, but runnable from CLI.
 *
 * Usage:
 *   php scripts/ast/regenerate-graph.php
 */

namespace PayCal\Scripts\Ast;

require_once __DIR__ . '/../../html/config.php';

main();

function main(): void
{
  $repoRoot = realpath(__DIR__ . '/../../');
  if (!is_string($repoRoot) || $repoRoot === '') {
    fwrite(STDERR, "[ast-regenerate] Unable to resolve repository root.\n");
    return;
  }

  $sourceRoot = $repoRoot . '/html/src';
  $tmpAstDir = $repoRoot . '/tmp/ast';
  $graphPath = $tmpAstDir . '/dependency-graph.json';

  if (!is_dir($sourceRoot)) {
    fwrite(STDERR, "[ast-regenerate] Source root not found: html/src\n");
    return;
  }

  if (!is_dir($tmpAstDir) && !mkdir($tmpAstDir, 0775, true) && !is_dir($tmpAstDir)) {
    fwrite(STDERR, "[ast-regenerate] Unable to create tmp/ast\n");
    return;
  }

  $classById = [];
  $fileByClass = [];

  foreach (collectPhpFiles($sourceRoot) as $filePath) {
    $content = file_get_contents($filePath);
    if (!is_string($content) || $content === '') {
      continue;
    }

    $namespace = extractNamespace($content);
    foreach (extractClassNames($content) as $className) {
      $fqcn = ltrim(($namespace !== '' ? $namespace . '\\' : '') . $className, '\\');
      if ($fqcn === '') {
        continue;
      }

      $classById[$fqcn] = [
        'id' => $fqcn,
        'type' => 'class',
        'file' => relativePath($repoRoot, $filePath),
      ];
      $fileByClass[$fqcn] = $filePath;
    }
  }

  $edges = [];
  foreach ($fileByClass as $fromClass => $filePath) {
    $content = file_get_contents($filePath);
    if (!is_string($content) || $content === '') {
      continue;
    }

    $namespace = extractNamespace($content);
    $useMap = extractUseMap($content);
    $references = extractReferencedClasses($content, $namespace, $useMap);

    foreach ($references as $toClass) {
      if ($toClass === '' || $toClass === $fromClass) {
        continue;
      }
      if (!isset($classById[$toClass])) {
        continue;
      }

      $edgeKey = $fromClass . '>' . $toClass;
      $edges[$edgeKey] = [
        'from' => $fromClass,
        'to' => $toClass,
        'type' => 'dependency',
      ];
    }
  }

  $payload = [
    'nodes' => array_values($classById),
    'edges' => array_values($edges),
  ];

  file_put_contents($graphPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

  fwrite(
    STDOUT,
    sprintf(
      "[ast-regenerate] graph updated: nodes=%d edges=%d path=%s\n",
      count($payload['nodes']),
      count($payload['edges']),
      $graphPath
    )
  );
}

/**
 * @return list<string>
 */
function collectPhpFiles(string $sourceRoot): array
{
  $files = [];
  $iterator = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($sourceRoot, \FilesystemIterator::SKIP_DOTS)
  );

  /** @var \SplFileInfo $entry */
  foreach ($iterator as $entry) {
    if (!$entry->isFile()) {
      continue;
    }
    $path = $entry->getPathname();
    if (strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) !== 'php') {
      continue;
    }
    $files[] = $path;
  }

  sort($files, SORT_STRING);
  return $files;
}

function extractNamespace(string $content): string
{
  if (preg_match('/^\s*namespace\s+([^;]+);/m', $content, $m) === 1) {
    return trim((string) $m[1]);
  }

  return '';
}

/**
 * @return list<string>
 */
function extractClassNames(string $content): array
{
  $tokens = token_get_all($content);
  $names = [];
  $tokenCount = count($tokens);

  for ($i = 0; $i < $tokenCount; $i++) {
    $token = $tokens[$i];
    if (!is_array($token)) {
      continue;
    }

    $tokenId = $token[0];
    $isDeclarationToken = $tokenId === T_CLASS || $tokenId === T_INTERFACE || $tokenId === T_TRAIT || (defined('T_ENUM') && $tokenId === T_ENUM);
    if (!$isDeclarationToken) {
      continue;
    }

    if ($tokenId === T_CLASS) {
      $prev = previousMeaningfulToken($tokens, $i);
      if (is_array($prev) && $prev[0] === T_NEW) {
        continue;
      }
    }

    $nameToken = nextStringToken($tokens, $i + 1);
    if (!is_array($nameToken) || $nameToken[0] !== T_STRING) {
      continue;
    }

    $name = trim((string) $nameToken[1]);
    if ($name !== '') {
      $names[] = $name;
    }
  }

  return array_values(array_unique($names));
}

/**
 * @param list<int|string|array{0:int,1:string,2:int}> $tokens
 * @return int|string|array{0:int,1:string,2:int}|null
 */
function previousMeaningfulToken(array $tokens, int $start): int|string|array|null
{
  for ($i = $start - 1; $i >= 0; $i--) {
    $token = $tokens[$i];
    if (is_string($token)) {
      if (trim($token) === '') {
        continue;
      }
      return $token;
    }

    if (!is_array($token)) {
      continue;
    }

    if ($token[0] === T_WHITESPACE || $token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
      continue;
    }

    return $token;
  }

  return null;
}

/**
 * @param list<int|string|array{0:int,1:string,2:int}> $tokens
 * @return array{0:int,1:string,2:int}|null
 */
function nextStringToken(array $tokens, int $start): ?array
{
  $tokenCount = count($tokens);
  for ($i = $start; $i < $tokenCount; $i++) {
    $token = $tokens[$i];
    if (is_string($token)) {
      if (trim($token) === '') {
        continue;
      }
      return null;
    }

    if (!is_array($token)) {
      continue;
    }

    if ($token[0] === T_WHITESPACE || $token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
      continue;
    }

    if ($token[0] === T_STRING) {
      return $token;
    }

    return null;
  }

  return null;
}

/**
 * @return array<string, string>
 */
function extractUseMap(string $content): array
{
  $map = [];
  if (preg_match_all('/^\s*use\s+([^;]+);/m', $content, $m) !== 1 && !isset($m[1])) {
    return $map;
  }

  foreach (($m[1] ?? []) as $rawUse) {
    if (!is_string($rawUse)) {
      continue;
    }

    $normalized = trim($rawUse);
    if ($normalized === '' || stripos($normalized, 'function ') === 0 || stripos($normalized, 'const ') === 0) {
      continue;
    }

    $parts = preg_split('/\s+as\s+/i', $normalized);
    if (!is_array($parts) || $parts === []) {
      continue;
    }

    $fqcn = ltrim(trim((string) $parts[0]), '\\');
    if ($fqcn === '') {
      continue;
    }

    $alias = '';
    if (isset($parts[1]) && is_string($parts[1]) && trim($parts[1]) !== '') {
      $alias = trim($parts[1]);
    } else {
      $segments = explode('\\', $fqcn);
      $alias = (string) end($segments);
    }

    if ($alias !== '') {
      $map[$alias] = $fqcn;
    }
  }

  return $map;
}

/**
 * @param array<string, string> $useMap
 * @return list<string>
 */
function extractReferencedClasses(string $content, string $namespace, array $useMap): array
{
  $tokens = token_get_all($content);
  $refs = [];

  $tokenCount = count($tokens);
  for ($i = 0; $i < $tokenCount; $i++) {
    $token = $tokens[$i];
    if (!is_array($token) || $token[0] !== T_STRING) {
      continue;
    }

    $identifier = $token[1];
    if ($identifier === '') {
      continue;
    }

    // Skip declaration contexts.
    $prev = previousMeaningfulToken($tokens, $i);
    if (is_array($prev) && in_array($prev[0], [T_CLASS, T_INTERFACE, T_TRAIT], true)) {
      continue;
    }

    $fqcn = '';
    if (isset($useMap[$identifier])) {
      $fqcn = $useMap[$identifier];
    } elseif (str_contains($identifier, '\\')) {
      $fqcn = ltrim($identifier, '\\');
    } elseif ($namespace !== '') {
      $fqcn = $namespace . '\\' . $identifier;
    }

    if ($fqcn !== '') {
      $refs[] = $fqcn;
    }
  }

  return array_values(array_unique($refs));
}

function relativePath(string $repoRoot, string $absolutePath): string
{
  $normalizedRoot = rtrim(str_replace('\\', '/', $repoRoot), '/');
  $normalizedPath = str_replace('\\', '/', $absolutePath);
  if (str_starts_with($normalizedPath, $normalizedRoot . '/')) {
    return substr($normalizedPath, strlen($normalizedRoot) + 1);
  }

  return $normalizedPath;
}
