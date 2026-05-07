<?php declare(strict_types=1);

/**
 * apply-missing-method-docblocks.php
 *
 * Purpose: Stage 2 docblock helper that applies generated method docblocks
 * using a Stage 1 missing-docblocks report.
 * Usage: php scripts/test/apply-missing-method-docblocks.php --input /path/report.json [--output-paths-file /path/changed.txt]
 * Why here: scripts/test is the shared quality-check and repair location for hooks/CI.
 */

$repoRoot = dirname(__DIR__, 2);
$inputPath = null;
$outputPathsFile = null;

for ($i = 1; $i < $argc; $i++) {
  $arg = $argv[$i] ?? '';
  if ($arg === '--input') {
    $candidate = $argv[$i + 1] ?? '';
    if (!is_string($candidate) || $candidate === '') {
      fwrite(STDERR, "Missing value for --input\n");
      exit(1);
    }
    $inputPath = $candidate;
    $i++;
    continue;
  }

  if ($arg === '--output-paths-file') {
    $candidate = $argv[$i + 1] ?? '';
    if (!is_string($candidate) || $candidate === '') {
      fwrite(STDERR, "Missing value for --output-paths-file\n");
      exit(1);
    }
    $outputPathsFile = $candidate;
    $i++;
    continue;
  }

  fwrite(STDERR, "Unknown argument: {$arg}\n");
  exit(1);
}

if ($inputPath === null) {
  fwrite(STDERR, "Usage: php scripts/test/apply-missing-method-docblocks.php --input /path/report.json\n");
  exit(1);
}

if (!is_file($inputPath)) {
  fwrite(STDERR, "Report file not found: {$inputPath}\n");
  exit(1);
}

$json = file_get_contents($inputPath);
if (!is_string($json) || $json === '') {
  fwrite(STDERR, "Report file is empty: {$inputPath}\n");
  exit(1);
}

$report = json_decode($json, true);
if (!is_array($report)) {
  fwrite(STDERR, "Invalid report JSON: {$inputPath}\n");
  exit(1);
}

$entriesRaw = $report['entries'] ?? null;
if (!is_array($entriesRaw)) {
  fwrite(STDERR, "Report missing entries array\n");
  exit(1);
}

$entriesByFile = [];
foreach ($entriesRaw as $entryRaw) {
  if (!is_array($entryRaw)) {
    continue;
  }

  $path = isset($entryRaw['path']) ? (string) $entryRaw['path'] : '';
  $method = isset($entryRaw['method']) ? (string) $entryRaw['method'] : '';
  $line = isset($entryRaw['line']) ? (int) $entryRaw['line'] : 0;
  $insertionLine = isset($entryRaw['insertion_line']) ? (int) $entryRaw['insertion_line'] : $line;

  if ($path === '' || $method === '' || $line <= 0 || $insertionLine <= 0) {
    continue;
  }

  $entriesByFile[$path][] = [
    'method' => $method,
    'line' => $line,
    'insertion_line' => $insertionLine,
  ];
}

$changedFiles = [];
$createdCount = 0;
$skippedCount = 0;

foreach ($entriesByFile as $relativePath => $entries) {
  $absolutePath = $repoRoot . '/' . $relativePath;
  if (!is_file($absolutePath)) {
    $skippedCount += count($entries);
    continue;
  }

  $source = file_get_contents($absolutePath);
  if (!is_string($source)) {
    $skippedCount += count($entries);
    continue;
  }

  $lines = preg_split('/\R/', $source);
  if (!is_array($lines)) {
    $skippedCount += count($entries);
    continue;
  }

  usort(
    $entries,
    static function (array $a, array $b): int {
      return ($b['insertion_line'] <=> $a['insertion_line']) ?: ($b['line'] <=> $a['line']);
    }
  );

  $fileChanged = false;
  foreach ($entries as $entry) {
    $insertionLine = (int) $entry['insertion_line'];
    $method = (string) $entry['method'];

    $targetIndex = max(0, min(count($lines) - 1, $insertionLine - 1));
    $targetLine = $lines[$targetIndex] ?? '';

    if (!is_string($targetLine)) {
      $skippedCount++;
      continue;
    }

    // Skip if a docblock already exists immediately above insertion point.
    $scanIndex = $targetIndex - 1;
    while ($scanIndex >= 0) {
      $scanLine = trim((string) ($lines[$scanIndex] ?? ''));
      if ($scanLine === '') {
        $scanIndex--;
        continue;
      }

      if (str_starts_with($scanLine, '/**')) {
        $skippedCount++;
        continue 2;
      }

      break;
    }

    if (!preg_match('/^(\s*)/', $targetLine, $matches)) {
      $indent = '';
    } else {
      $indent = (string) ($matches[1] ?? '');
    }

    $docblockLines = [
      $indent . '/**',
      $indent . ' * TODO: Document ' . $method . '.',
      $indent . ' */',
    ];

    array_splice($lines, $targetIndex, 0, $docblockLines);
    $createdCount++;
    $fileChanged = true;
  }

  if (!$fileChanged) {
    continue;
  }

  $updated = implode("\n", $lines);
  if (!str_ends_with($updated, "\n")) {
    $updated .= "\n";
  }

  if (file_put_contents($absolutePath, $updated) === false) {
    fwrite(STDERR, "Failed to write updated file: {$relativePath}\n");
    exit(1);
  }

  $changedFiles[] = $relativePath;
}

echo "Docblocks created: {$createdCount}\n";
echo "Docblocks skipped: {$skippedCount}\n";
if ($changedFiles !== []) {
  echo "Updated files:\n";
  foreach ($changedFiles as $changedFile) {
    echo "- {$changedFile}\n";
  }
}

if ($outputPathsFile !== null) {
  $pathsPayload = implode("\n", $changedFiles);
  if ($pathsPayload !== '') {
    $pathsPayload .= "\n";
  }

  if (file_put_contents($outputPathsFile, $pathsPayload) === false) {
    fwrite(STDERR, "Failed to write changed paths file: {$outputPathsFile}\n");
    exit(1);
  }
}

exit(0);
