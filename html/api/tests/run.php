<?php declare(strict_types=1);

use PayCal\Domain\Authentication;
use PayCal\Domain\User;
use PayCal\Domain\Response;
use PayCal\Domain\Enums\HttpStatus;

require_once '../../config.php';

Authentication::redirectHomeIfUnauthenticated();

if (!User::isAdmin()) {
  Response::error('Admin access required', [], HttpStatus::HTTP_UNAUTHORIZED);
  exit;
}

header('Content-Type: application/json');

try {
  $startTime = microtime(true);
  
  // Execute PHPUnit from workspace root where phpunit.xml is located
  $workspaceRoot = dirname(HTML);
  
  // Get PHP CLI binary (PHP_BINARY may point to php-fpm when running under FPM)
  // Try multiple strategies: php-fpm → bin/php, or sbin binary → bin directory
  $phpBinary = PHP_BINARY;
  if (strpos($phpBinary, 'php-fpm') !== false) {
    // Replace php-fpm with php, and sbin directory with bin
    $phpBinary = str_replace(['sbin/php-fpm', 'php-fpm'], ['bin/php', 'php'], $phpBinary);
  }
  
  $command = sprintf(
    'cd %s && %s ./vendor/bin/phpunit --colors=never 2>&1',
    escapeshellarg($workspaceRoot),
    escapeshellarg($phpBinary)
  );
  $output = shell_exec($command) ?: '';
  
  $duration = microtime(true) - $startTime;
  
  // Parse PHPUnit output to extract key metrics
  $success = strpos($output, 'FAILED') === false && strpos($output, 'ERRORS!') === false && strpos($output, ' Error') === false;
  $testCount = 0;
  $assertionCount = 0;
  $failures = 0;
  
  // Extract test count from output (supports both "X tests" and "Tests: X" formats)
  if (preg_match('/Tests: (\d+)/', $output, $matches) || preg_match('/(\d+) tests?/', $output, $matches)) {
    $testCount = (int) $matches[1];
  }
  
  // Extract assertion count (supports both "X assertions" and "Assertions: X" formats)
  if (preg_match('/Assertions: (\d+)/', $output, $matches) || preg_match('/(\d+) assertions?/', $output, $matches)) {
    $assertionCount = (int) $matches[1];
  }
  
  // Extract failures (supports both "X failures" and "Failures: X" formats)
  if (preg_match('/Failures: (\d+)/', $output, $matches) || preg_match('/(\d+) failures?/', $output, $matches)) {
    $failures = (int) $matches[1];
  }
  
  $result = [
    'success' => $success,
    'timestamp' => date('Y-m-d H:i:s'),
    'testCount' => $testCount,
    'assertionCount' => $assertionCount,
    'failures' => $failures,
    'duration' => round($duration, 2),
    'output' => $output,
  ];
  
  // Save to .last-run.json for persistence
  $lastRunPath = HTML . '/tests/.last-run.json';
  $lastRunDir = dirname($lastRunPath);
  if (!is_dir($lastRunDir)) {
    @mkdir($lastRunDir, 0755, true);
  }
  @file_put_contents($lastRunPath, json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
  
  echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => $e->getMessage(),
  ], JSON_UNESCAPED_SLASHES);
}
