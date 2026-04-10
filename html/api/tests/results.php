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

// Read test plan metrics from PHPUNIT_TEST_PLAN.md
$testPlanPath = dirname(__DIR__, 2) . '/PHPUNIT_TEST_PLAN.md';
$metrics = [
  'totalTests' => 0,
  'totalAssertions' => 0,
  'phases' => [],
  'classes' => [],
];

if (file_exists($testPlanPath)) {
  $content = file_get_contents($testPlanPath);
  if ($content !== false) {
    // Extract total from "Total: X tests, Y assertions"
    if (preg_match('/Total:\s*(\d+)\s*tests?,\s*(\d+)\s*assertions?/i', $content, $matches)) {
      $metrics['totalTests'] = (int) $matches[1];
      $metrics['totalAssertions'] = (int) $matches[2];
    }

    // Extract phase completion (e.g., "Phase 1: COMPLETE (4/4 classes)")
    if (preg_match_all('/Phase\s+(\d+):\s*([^(]+)\((\d+)\/(\d+)\s*classes?\)/i', $content, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $metrics['phases'][] = [
          'number' => (int) $match[1],
          'status' => trim($match[2]),
          'completed' => (int) $match[3],
          'total' => (int) $match[4],
          'percentage' => $match[4] > 0 ? round(($match[3] / $match[4]) * 100) : 0,
        ];
      }
    }

    // Extract class status lines (e.g., "- ✅ InputSanitizer (33 tests, 88 assertions)")
    if (preg_match_all('/- ([✅⏳❌])\s+(\w+)\s*\((\d+)\s*tests?,\s*(\d+)\s*assertions?\)/i', $content, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $metrics['classes'][] = [
          'name' => $match[2],
          'status' => $match[1],
          'tests' => (int) $match[3],
          'assertions' => (int) $match[4],
        ];
      }
    }
  }
}

// Read last run results from .last-run.json
$lastRunPath = HTML . '/tests/.last-run.json';
$lastRun = null;
if (file_exists($lastRunPath)) {
  $content = file_get_contents($lastRunPath);
  $lastRun = ($content !== false) ? json_decode($content, true) : null;
}

echo json_encode([
  'lastRun' => $lastRun,
  'metrics' => $metrics,
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
