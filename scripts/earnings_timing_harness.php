<?php declare(strict_types=1);

/**
 * Temporary harness to measure per-test timings for the deterministic-full earnings suite.
 *
 * Why this exists:
 * - Runs each earnings test individually to capture start/end timestamps in milliseconds.
 * - Produces a fixed-width ASCII report for terminal-friendly review.
 *
 * Optional flags:
 * - --sort-duration-desc  Sort rows by duration (highest first).
 * - --sort=duration-desc  Alias for --sort-duration-desc.
 */

$root = trim((string) shell_exec('git rev-parse --show-toplevel'));
$phpBinary = '/opt/homebrew/bin/php';
$phpunit = $root . '/vendor/bin/phpunit';
$config = $root . '/phpunit.xml';
$files = [
  $root . '/html/tests/Unit/EarningsTest.php',
  $root . '/html/tests/Unit/EarningsYtdHookSeamTest.php',
  $root . '/html/tests/Contract/EarningsYtdManifestContractTest.php',
  $root . '/html/tests/Contract/EarningsYtdExtensionModeContractTest.php',
  $root . '/html/tests/Integration/EarningsControllerIntegrationTest.php',
  $root . '/html/tests/Integration/EarningsCalendarParityIntegrationTest.php',
];
$env = ['TZ' => 'UTC', 'LC_ALL' => 'C'];
$descriptor = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

$sortMode = 'none';
foreach (array_slice($argv ?? [], 1) as $arg) {
  if ($arg === '--sort-duration-desc' || $arg === '--sort=duration-desc') {
    $sortMode = 'duration-desc';
    continue;
  }

  if ($arg === '--help' || $arg === '-h') {
    echo "Usage: php scripts/earnings_timing_harness.php [--sort-duration-desc|--sort=duration-desc]\n";
    echo "\n";
    echo "Options:\n";
    echo "  --sort-duration-desc  Sort results by duration (largest first).\n";
    echo "  --sort=duration-desc  Alias for --sort-duration-desc.\n";
    echo "  --help, -h            Show this help text.\n";
    exit(0);
  }

  fwrite(STDERR, "Unknown flag: {$arg}\n");
  fwrite(STDERR, "Use --help for usage.\n");
  exit(1);
}

$listCommand = array_merge([$phpBinary, $phpunit, '--configuration', $config, '--list-tests'], $files);
$listProcess = proc_open($listCommand, $descriptor, $pipes, $root, $env);
if (!is_resource($listProcess)) {
  fwrite(STDERR, "Failed to start phpunit list-tests\n");
  exit(1);
}
$listOutput = stream_get_contents($pipes[1]);
$listError = stream_get_contents($pipes[2]);
foreach ($pipes as $pipe) {
  fclose($pipe);
}
$listExitCode = proc_close($listProcess);
if ($listExitCode !== 0) {
  fwrite(STDERR, (string) $listOutput . (string) $listError);
  exit($listExitCode);
}

$tests = [];
foreach (preg_split('/\R/', (string) $listOutput) as $line) {
  if (preg_match('/^ - (.+)$/', $line, $matches) === 1) {
    $tests[] = $matches[1];
  }
}

$suiteStart = (int) round(microtime(true) * 1000);
$rows = [];
echo "Running Earnings Test Suite\n";
foreach ($tests as $testName) {
  $start = (int) round(microtime(true) * 1000);
  $command = array_merge([
    $phpBinary,
    $phpunit,
    '--configuration', $config,
    '--exclude-group', 'skip',
    '--filter', $testName,
  ], $files);

  $process = proc_open($command, $descriptor, $pipes, $root, $env);
  if (!is_resource($process)) {
    $end = (int) round(microtime(true) * 1000);
    $rows[] = [$start, $end, $end - $start, 'failed', $testName];
    echo 'F';
    flush();
    continue;
  }

  $stdout = stream_get_contents($pipes[1]);
  $stderr = stream_get_contents($pipes[2]);
  foreach ($pipes as $pipe) {
    fclose($pipe);
  }
  $exitCode = proc_close($process);
  $end = (int) round(microtime(true) * 1000);
  $combined = (string) $stdout . "\n" . (string) $stderr;

  $status = 'failed';
  if (
    strpos($combined, 'Skipped:') !== false
    || strpos($combined, 'This test was skipped') !== false
    || strpos($combined, 'OK, but some tests were skipped') !== false
  ) {
    $status = 'skipped';
  } elseif ($exitCode === 0 || strpos($combined, 'OK (') !== false) {
    $status = 'passed';
  }

  $rows[] = [$start, $end, $end - $start, $status, $testName];
  if ($status === 'passed') {
    echo '.';
  } elseif ($status === 'skipped') {
    echo 'S';
  } else {
    echo 'F';
  }
  flush();
}
echo "\n\n";
$suiteEnd = (int) round(microtime(true) * 1000);

if ($sortMode === 'duration-desc') {
  usort($rows, static function (array $a, array $b): int {
    return ((int) $b[2]) <=> ((int) $a[2]);
  });
}

$makeBorder = static function (array $widths): string {
  $parts = ['+'];
  foreach ($widths as $width) {
    $parts[] = str_repeat('-', $width + 2);
    $parts[] = '+';
  }
  return implode('', $parts);
};

$formatCell = static function (string $value, int $width, string $align = 'left'): string {
  if ($align === 'right') {
    return str_pad($value, $width, ' ', STR_PAD_LEFT);
  }
  return str_pad($value, $width, ' ', STR_PAD_RIGHT);
};

$formatDuration = static function (int $milliseconds): string {
  if ($milliseconds < 1000) {
    return $milliseconds . 'ms';
  }

  $seconds = $milliseconds / 1000;
  return rtrim(rtrim(sprintf('%.2f', $seconds), '0'), '.') . 's';
};

$renderTable = static function (array $headers, array $bodyRows, array $alignments = []) use ($makeBorder, $formatCell): void {
  $widths = [];
  foreach ($headers as $index => $header) {
    $widths[$index] = strlen((string) $header);
  }

  foreach ($bodyRows as $row) {
    foreach ($row as $index => $value) {
      $widths[$index] = max($widths[$index] ?? 0, strlen((string) $value));
    }
  }

  $border = $makeBorder($widths);
  echo $border, "\n";

  $headerCells = [];
  foreach ($headers as $index => $header) {
    $headerCells[] = ' ' . $formatCell((string) $header, $widths[$index]) . ' ';
  }
  echo '|', implode('|', $headerCells), '|', "\n";
  echo $border, "\n";

  foreach ($bodyRows as $row) {
    $cells = [];
    foreach ($headers as $index => $_header) {
      $value = (string) ($row[$index] ?? '');
      $align = $alignments[$index] ?? 'left';
      $cells[] = ' ' . $formatCell($value, $widths[$index], $align) . ' ';
    }
    echo '|', implode('|', $cells), '|', "\n";
  }

  echo $border, "\n";
};

$tableRows = [];
$passed = 0;
$failed = 0;
$skipped = 0;
$minDuration = null;
$maxDuration = 0;
$durationTotal = 0;

foreach ($rows as [$start, $end, $duration, $status, $testName]) {
  $durationInt = (int) $duration;
  $durationTotal += $durationInt;
  $minDuration = $minDuration === null ? $durationInt : min($minDuration, $durationInt);
  $maxDuration = max($maxDuration, $durationInt);

  if ($status === 'passed') {
    $passed++;
  } elseif ($status === 'skipped') {
    $skipped++;
  } else {
    $failed++;
  }

  $tableRows[] = [
    $formatDuration($durationInt),
    (string) strtoupper($status),
    (string) $testName,
  ];
}

$totalTests = count($rows);
$avgDuration = $totalTests > 0 ? (int) round($durationTotal / $totalTests) : 0;

echo "EARNINGS TIMING HARNESS\n";
echo "\n";
$renderTable(
  ['DURATION', 'STATUS', 'TEST'],
  $tableRows,
  ['right', 'left', 'left']
);

echo "\nSUMMARY\n";
$renderTable(
  ['METRIC', 'VALUE'],
  [
    ['TOTAL_TESTS', (string) $totalTests],
    ['PASSED', (string) $passed],
    ['FAILED', (string) $failed],
    ['SKIPPED', (string) $skipped],
    ['SUM_DURATION', $formatDuration($durationTotal)],
    ['AVG_DURATION', $formatDuration($avgDuration)],
    ['MIN_DURATION', $formatDuration((int) ($minDuration ?? 0))],
    ['MAX_DURATION', $formatDuration($maxDuration)],
    ['SUITE_DURATION', $formatDuration($suiteEnd - $suiteStart)],
  ],
  ['left', 'right']
);
