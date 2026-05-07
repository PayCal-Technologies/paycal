<?php declare(strict_types=1);

/**
 * affected-tests.php
 *
 * Analyzes changed PHP files and determines which tests should run.
 * 
 * Usage:
 *   php tools/affected-tests.php [base-ref] [head-ref]
 *   composer run test:affected -- main HEAD
 *
 * Default: compare against origin/main
 */

namespace PayCal\Tools;

use DirectoryIterator;
use ReflectionClass;
use RegexIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class AffectedTestsAnalyzer
{
    private string $baseRef = 'origin/main';
    private string $headRef = 'HEAD';
    private string $projectRoot;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = rtrim($projectRoot, '/');
    }

    /**
     * Analyze changed files and return affected test file paths
     */
    public function getAffectedTests(?string $baseRef = null, ?string $headRef = null): array
    {
        if ($baseRef !== null) {
            $this->baseRef = $baseRef;
        }
        if ($headRef !== null) {
            $this->headRef = $headRef;
        }

        $changedFiles = $this->getChangedPhpFiles();
        if (empty($changedFiles)) {
            return [];
        }

        $changedClasses = $this->extractClassesFromFiles($changedFiles);
        if (empty($changedClasses)) {
            return [];
        }

        return $this->findTestsForClasses($changedClasses);
    }

    /**
     * Get list of changed PHP files between base and head refs
     */
    private function getChangedPhpFiles(): array
    {
        $cmd = sprintf(
            'cd %s && git diff --name-only --diff-filter=ACMR %s...%s -- "*.php"',
            escapeshellarg($this->projectRoot),
            escapeshellarg($this->baseRef),
            escapeshellarg($this->headRef)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            return [];
        }

        // Filter out vendor and tests directories, focus on src/
        return array_filter(
            array_map(fn($f) => trim($f), $output),
            fn($f) => str_starts_with($f, 'html/src/') && !str_contains($f, '/tests/')
        );
    }

    /**
     * Extract class names from PHP files
     */
    private function extractClassesFromFiles(array $files): array
    {
        $classes = [];

        foreach ($files as $file) {
            $path = $this->projectRoot . '/' . $file;
            if (!file_exists($path)) {
                continue;
            }

            $extracted = $this->extractClassesFromFile($path);
            $classes = array_merge($classes, $extracted);
        }

        return array_unique($classes);
    }

    /**
     * Extract fully qualified class names from a single PHP file
     */
    private function extractClassesFromFile(string $path): array
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            return [];
        }

        $classes = [];
        $namespace = '';

        // Extract namespace
        if (preg_match('/^namespace\s+([\\w\\\\]+);/m', $contents, $matches)) {
            $namespace = $matches[1];
        }

        // Extract class/interface/enum names
        if (preg_match_all('/^\s*(class|interface|enum|trait)\s+(\w+)/m', $contents, $matches)) {
            foreach ($matches[2] as $className) {
                $classes[] = $namespace ? $namespace . '\\' . $className : $className;
            }
        }

        return $classes;
    }

    /**
     * Find test files that cover the given classes
     */
    private function findTestsForClasses(array $classes): array
    {
        $testFiles = [];
        $testsDir = $this->projectRoot . '/html/tests';

        if (!is_dir($testsDir)) {
            return [];
        }

        // Scan all test files
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($testsDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php' || !str_ends_with($file->getBasename(), 'Test.php')) {
                continue;
            }

            $relPath = str_replace($this->projectRoot . '/', '', (string)$file);

            // Check if test file references any changed classes
            $contents = file_get_contents((string)$file);
            if ($contents === false) {
                continue;
            }

            foreach ($classes as $class) {
                // Look for class references (imports, instantiations, static calls, etc.)
                if (
                    str_contains($contents, $class) ||
                    preg_match('/use\s+' . preg_quote($class) . '/i', $contents) ||
                    preg_match('/new\s+' . preg_quote(class_basename($class)) . '/i', $contents)
                ) {
                    $testFiles[] = $relPath;
                    break;
                }
            }
        }

        return array_unique($testFiles);
    }

    /**
     * Convert test file paths to PHPUnit arguments
     */
    public function getPhpunitArgs(array $testFiles): string
    {
        if (empty($testFiles)) {
            return '--group unit --exclude-group slow --exclude-group stress'; // quick tests as fallback
        }

        // Group by test suite for better organization
        $unitTests = [];
        $integrationTests = [];
        $contractTests = [];

        foreach ($testFiles as $file) {
            if (str_contains($file, '/Unit/')) {
                $unitTests[] = $file;
            } elseif (str_contains($file, '/Contract/')) {
                $contractTests[] = $file;
            } else {
                $integrationTests[] = $file;
            }
        }

        $args = [];
        if (!empty($unitTests)) {
            $args[] = escapeshellarg(str_replace('html/', '', $unitTests[0]));
        }
        if (!empty($integrationTests)) {
            $args[] = escapeshellarg(str_replace('html/', '', $integrationTests[0]));
        }
        if (!empty($contractTests)) {
            $args[] = escapeshellarg(str_replace('html/', '', $contractTests[0]));
        }

        return implode(' ', $args) ?: 'tests/';
    }
}

/**
 * Helper function
 */
function class_basename(string $class): string
{
    return basename(str_replace('\\', '/', $class));
}

// Main execution
$projectRoot = dirname(__DIR__);
$analyzer = new AffectedTestsAnalyzer($projectRoot);

$baseRef = $argv[1] ?? 'origin/main';
$headRef = $argv[2] ?? 'HEAD';

$affectedTests = $analyzer->getAffectedTests($baseRef, $headRef);

if (empty($affectedTests)) {
    // No affected tests; run quick suite
    echo 'No source changes detected. Running quick smoke tests.';
    echo PHP_EOL;
    echo '--group unit --exclude-group slow --exclude-group stress';
    exit(0);
}

// Output test file paths
foreach ($affectedTests as $file) {
    // Keep repo-root relative path for root-level PHPUnit command.
    echo $file;
    echo PHP_EOL;
}
