#!/usr/bin/env php
<?php declare(strict_types=1);

$exitCode = 0;
$expected = "<?php declare(strict_types=1);";

$paths = array_slice($argv, 1);

if ($paths === []) {
  fwrite(STDERR, "Usage: php check_strict_header.php <path> [path...]\n");
  exit(1);
}

$files = [];

foreach ($paths as $path) {
  if (is_file($path) && str_ends_with($path, ".php")) {
    $files[] = $path;
    continue;
  }

  if (is_dir($path)) {
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
      if ($file->isFile() && str_ends_with($file->getFilename(), ".php")) {
        $files[] = $file->getPathname();
      }
    }
  }
}

foreach ($files as $file) {
  $content = file_get_contents($file);

  if ($content === false) {
    fwrite(STDERR, "[ERROR] {$file} unreadable\n");
    $exitCode = 1;
    continue;
  }

  if (preg_match("/^\s+<\?php/", $content) === 1) {
    fwrite(STDERR, "[ERROR] {$file} has leading whitespace\n");
    $exitCode = 1;
    continue;
  }

  $firstLine = strtok($content, "\n");

  if (rtrim($firstLine, "\r") !== $expected) {
    fwrite(STDERR, "[ERROR] {$file} missing strict header\n");
    $exitCode = 1;
  }
}

exit($exitCode);
