<?php declare(strict_types=1);

/**
 * Enforce PHPUnit group boundaries for public vs private repo profiles.
 *
 * Usage:
 *   php scripts/test/check-test-repo-boundaries.php
 *   PAYCAL_REPO_PROFILE=public php scripts/test/check-test-repo-boundaries.php
 */

$repoRoot = dirname(__DIR__, 2);

$profile = strtolower(trim((string) (getenv('PAYCAL_REPO_PROFILE') ?: '')));
if ($profile === '') {
  $profile = is_dir($repoRoot . '/soc2/reports') ? 'private' : 'public';
}

$failures = [];

/** @param list<string> $messages */
function boundary_fail(array &$messages, string $message): void
{
  $messages[] = $message;
}

/** @return list<string> */
function boundary_glob(string $pattern): array
{
  $matches = glob($pattern) ?: [];

  return array_values(array_filter($matches, static fn (string $path): bool => is_file($path)));
}

/** @return list<string> */
function boundary_test_files(string $root): array
{
  $files = [];
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
  );

  foreach ($iterator as $file) {
    if (!$file->isFile() || !str_ends_with($file->getPathname(), '.php')) {
      continue;
    }
    $files[] = $file->getPathname();
  }

  sort($files);

  return $files;
}

function boundary_has_group(string $content, string $group): bool
{
  return str_contains($content, "#[Group('{$group}')]");
}

foreach (boundary_glob($repoRoot . '/html/tests/Soc2/*.php') as $file) {
  $content = (string) file_get_contents($file);
  if (!boundary_has_group($content, 'soc2')) {
    boundary_fail($failures, "{$file}: missing #[Group('soc2')]");
  }
}

$soc2Surface = $repoRoot . '/html/tests/Unit/Soc2SurfaceTest.php';
if (is_file($soc2Surface)) {
  $content = (string) file_get_contents($soc2Surface);
  if (!boundary_has_group($content, 'soc2')) {
    boundary_fail($failures, "{$soc2Surface}: missing #[Group('soc2')]");
  }
  if ($profile === 'public' && !boundary_has_group($content, 'private-moat')) {
    boundary_fail($failures, "{$soc2Surface}: public profile requires #[Group('private-moat')]");
  }
}

$moatNeedles = [
  'html/js/business/workspace.js.php',
  'html/business/_archive/',
  'PayCal\\Domain\\Soc2Surface',
  'soc2/reports/',
  'soc2/policies/',
  'soc2/schemas/',
  'soc2/index/',
];

if ($profile === 'public') {
  foreach (boundary_test_files($repoRoot . '/html/tests') as $file) {
    if (str_contains($file, '/Soc2/') || str_ends_with($file, 'Soc2SurfaceTest.php')) {
      continue;
    }

    $content = (string) file_get_contents($file);
    $needsMoatFence = false;

    foreach ($moatNeedles as $needle) {
      if (str_contains($content, $needle)) {
        $needsMoatFence = true;
        break;
      }
    }

    if (!$needsMoatFence) {
      continue;
    }

    if (!boundary_has_group($content, 'private-moat') && !boundary_has_group($content, 'soc2')) {
      boundary_fail(
        $failures,
        "{$file}: references private/moat artifacts but lacks #[Group('private-moat')] or #[Group('soc2')]",
      );
    }
  }
}

$composerPath = $repoRoot . '/composer.json';
if (!is_file($composerPath)) {
  boundary_fail($failures, 'Missing composer.json');
} else {
  $composer = (string) file_get_contents($composerPath);
  if (!str_contains($composer, '"test:soc2"')) {
    boundary_fail($failures, 'composer.json must define test:soc2 script');
  }

  if ($profile === 'public') {
    if (!str_contains($composer, 'exclude-group soc2') || !str_contains($composer, 'exclude-group private-moat')) {
      boundary_fail($failures, 'public composer.json test:quick must exclude soc2 and private-moat groups');
    }
    if (!str_contains($composer, 'phpunit.public.xml')) {
      boundary_fail($failures, 'public composer.json test:quick must use phpunit.public.xml');
    }
    if (!is_file($repoRoot . '/phpunit.public.xml')) {
      boundary_fail($failures, 'public repo missing phpunit.public.xml');
    }
  }

  if ($profile === 'private' && !str_contains($composer, 'exclude-group soc2')) {
    boundary_fail($failures, 'private composer.json test:quick must exclude soc2 (use test:soc2 separately)');
  }
}

if ($failures !== []) {
  fwrite(STDERR, "Test repo boundary check failed ({$profile} profile):\n");
  foreach ($failures as $failure) {
    fwrite(STDERR, "  - {$failure}\n");
  }
  exit(1);
}

fwrite(STDOUT, "OK: test repo boundaries valid ({$profile} profile)\n");
