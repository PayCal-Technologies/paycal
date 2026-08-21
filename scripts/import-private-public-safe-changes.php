#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * Import public-safe files from the private source tree without relying on
 * shared Git blobs. This supports private-to-public promotions after private
 * component splits or file moves that the public repository has not seen yet.
 *
 * Usage:
 *   php scripts/import-private-public-safe-changes.php --private-root=/path/to/paycal-private --range=HEAD~1..HEAD [--paths-file=/tmp/public-paths.txt] [--dry-run]
 */

$repoRoot = dirname(__DIR__);
$options = parseOptions($argv);
$privateRoot = rtrim((string) ($options['private-root'] ?? '/private/var/www/paycal-private'), '/');
$range = (string) ($options['range'] ?? 'HEAD~1..HEAD');
$dryRun = array_key_exists('dry-run', $options);
$pathsFile = isset($options['paths-file']) ? (string) $options['paths-file'] : null;

if (!is_dir($privateRoot . '/.git')) {
  fail('private repository not found: ' . $privateRoot);
}

$publicAllowlist = loadList($repoRoot . '/scripts/public-promotion-allowlist.txt');
$privateOnlyPath = $privateRoot . '/scripts/private-only-allowlist.txt';
$privateOnlyList = is_file($privateOnlyPath) ? loadList($privateOnlyPath) : [];

$changedPaths = gitChangedPaths($privateRoot, $range);
if ($pathsFile !== null) {
  $changedPaths = restrictToPathFile($changedPaths, $pathsFile);
}

if ($changedPaths === []) {
  echo "[public-import] no changed paths in {$range}\n";
  exit(0);
}

$copied = [];
$deleted = [];
$skipped = [];
$blocked = [];

foreach ($changedPaths as $path) {
  if (!isSafeRelativePath($path)) {
    $blocked[] = [$path, 'unsafe relative path'];
    continue;
  }

  if (!pathMatchesList($path, $publicAllowlist)) {
    $skipped[] = [$path, 'outside public allowlist'];
    continue;
  }

  if (pathMatchesList($path, $privateOnlyList) || matchesForbiddenPrivatePath($path)) {
    $blocked[] = [$path, 'private-only path'];
    continue;
  }

  $source = $privateRoot . '/' . $path;
  $target = $repoRoot . '/' . $path;
  if (!file_exists($source)) {
    if (file_exists($target)) {
      if (!$dryRun && !unlink($target)) {
        fail('failed to delete target: ' . $path);
      }
      $deleted[] = $path;
    }
    continue;
  }

  if (!is_file($source)) {
    $skipped[] = [$path, 'not a regular file'];
    continue;
  }

  if (!$dryRun) {
    $dir = dirname($target);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
      fail('failed to create directory: ' . $dir);
    }

    if (!copy($source, $target)) {
      fail('failed to copy: ' . $path);
    }
  }
  $copied[] = $path;
}

echo "[public-import] range={$range}\n";
if ($pathsFile !== null) {
  echo "[public-import] paths-file={$pathsFile}\n";
}
echo "[public-import] copied=" . count($copied) . " deleted=" . count($deleted) . " skipped=" . count($skipped) . " blocked=" . count($blocked) . "\n";
emitRows('copied', $copied);
emitRows('deleted', $deleted);
emitPairs('skipped', $skipped);
emitPairs('blocked', $blocked);

if ($blocked !== []) {
  echo "[public-import] blocked private-only paths were intentionally not imported\n";
}

/**
 * @return array<string, string|bool>
 */
function parseOptions(array $argv): array
{
  $options = [];
  foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') {
      $options['dry-run'] = true;
      continue;
    }

    if (str_starts_with($arg, '--') && str_contains($arg, '=')) {
      [$key, $value] = explode('=', substr($arg, 2), 2);
      $options[$key] = $value;
    }
  }

  return $options;
}

/**
 * @return list<string>
 */
function loadList(string $path): array
{
  $lines = @file($path, FILE_IGNORE_NEW_LINES);
  if ($lines === false) {
    fail('missing list file: ' . $path);
  }

  $entries = [];
  foreach ($lines as $line) {
    $line = trim((string) preg_replace('/#.*/', '', $line));
    if ($line !== '') {
      $entries[] = $line;
    }
  }

  return $entries;
}

/**
 * @return list<string>
 */
function gitChangedPaths(string $repo, string $range): array
{
  $command = 'git -C ' . escapeshellarg($repo) . ' diff --name-only --diff-filter=ACDMRT ' . escapeshellarg($range);
  exec($command, $output, $exitCode);
  if ($exitCode !== 0) {
    fail('git diff failed for range: ' . $range);
  }

  return array_values(array_filter(array_map('trim', $output), static fn(string $path): bool => $path !== ''));
}

/**
 * @param list<string> $paths
 * @return list<string>
 */
function restrictToPathFile(array $paths, string $pathsFile): array
{
  $requestedPaths = [];
  foreach (loadList($pathsFile) as $path) {
    if (!isSafeRelativePath($path)) {
      fail('paths-file contains unsafe relative path: ' . $path);
    }
    $requestedPaths[$path] = true;
  }

  return array_values(array_filter(
    $paths,
    static fn(string $path): bool => isset($requestedPaths[$path])
  ));
}

/**
 * @param list<string> $entries
 */
function pathMatchesList(string $path, array $entries): bool
{
  foreach ($entries as $entry) {
    if (str_ends_with($entry, '/')) {
      if (str_starts_with($path, $entry)) {
        return true;
      }
      continue;
    }

    if ($path === $entry) {
      return true;
    }
  }

  return false;
}

function matchesForbiddenPrivatePath(string $path): bool
{
  return preg_match(
    '#^(ai-notes/|soc2/|keys/|tmp/|logs/|copilot-scripts/|\.github/workflows/|\.circleci/|\.azure-pipelines/|\.gitlab-ci\.yml$|Jenkinsfile$|\.git($|/)|html/admin/soc2/|html/css/admin/soc2/|html/src/Domain/Soc2Surface\.php$|html/extensions/overrides/soc2-surface/|html/js/businesses/|html/css/businesses/|html/extensions/overrides/argus|html/admin/argus/)#',
    $path
  ) === 1;
}

function isSafeRelativePath(string $path): bool
{
  return $path !== ''
    && !str_starts_with($path, '/')
    && !str_contains($path, "\0")
    && !str_contains($path, '../')
    && $path !== '..';
}

/**
 * @param list<string> $rows
 */
function emitRows(string $label, array $rows): void
{
  foreach ($rows as $row) {
    echo " - {$label}: {$row}\n";
  }
}

/**
 * @param list<array{0: string, 1: string}> $rows
 */
function emitPairs(string $label, array $rows): void
{
  foreach ($rows as [$path, $reason]) {
    echo " - {$label}: {$path} ({$reason})\n";
  }
}

function fail(string $message): never
{
  fwrite(STDERR, "[public-import] fatal: {$message}\n");
  exit(1);
}
