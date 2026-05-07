<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Authentication;
use PayCal\Domain\Database;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Log;
use PayCal\Domain\Render;
use PayCal\Domain\RequestGuard;
use PayCal\Domain\Strings;
use PayCal\Domain\User;
use PayCal\Domain\WorkEntry;

/**
 * TestsPageController.php
 *
 * Purpose: Server-rendered diagnostics/test dashboard controller for internal
 * verification pages and development-facing inspection views.
 *
 * Developer notes:
 * - This controller supports internal diagnostics and should stay clearly
 *   separated from production user workflows.
 * - Keep heavy or sensitive inspection logic behind authenticated/internal use.
 *
 * Architectural role:
 * - Entry-point controller for request handling, authorization enforcement,
 *   and response or render shaping at the web boundary.
 * - Domain policy, persistence rules, and side-effect orchestration should
 *   stay in collaborators rather than expanding controller state.
 *
 * @category   Controllers
 * @package    PayCal\Controllers
 * @subpackage HTTP
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */



/**
 * Tests page controller.
 *
 * Responsibilities:
 * - Build internal test/diagnostic pages and supporting template payloads.
 * - Surface development inspection data without affecting product flows.
 * - Keep presentation-only concerns separate from domain validation logic.
 */
class TestsPageController
{

  /**
   * Handles batchI18n operation.
   */
  private static function batchI18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
  /**
   * Handles asString operation.
   */
  private static function asString(mixed $value, string $default = ''): string
  {
    return is_scalar($value) ? (string) $value : $default;
  }

  /**
   * Handles asInt operation.
   */
  private static function asInt(mixed $value, int $default = 0): int
  {
    return is_numeric($value) ? (int) $value : $default;
  }

  /**
   * Handles asFloat operation.
   */
  private static function asFloat(mixed $value, float $default = 0.0): float
  {
    return is_numeric($value) ? (float) $value : $default;
  }

  /**
   * Handles asBool operation.
   */
  private static function asBool(mixed $value, bool $default = false): bool
  {
    return is_bool($value) ? $value : $default;
  }

  /**
   * @param array<mixed, mixed>|null $value
   * @return array<string, mixed>|null
   */
  private static function normalizeAssoc(?array $value): ?array
  {
    if (!is_array($value)) {
      return null;
    }

    $normalized = [];
    foreach ($value as $key => $item) {
      $normalized[(string) $key] = $item;
    }

    return $normalized;
  }

  /**
   * Renders the test dashboard page.
   * Handles authentication, test data fetching, and template rendering.
   */
  public static function dashboard(): void
  {
    if (!Authentication::validateAndTouchSession() || !User::isAdmin()) {
      header('Location: ' . \PayCal\Domain\Config\Environment::appHome());

      exit;
    }

    // Parse test plan to get current metrics
    $testPlanPath = dirname(__DIR__, 2).'/PHPUNIT_TEST_PLAN.md';
    $metrics = self::parseTestPlan($testPlanPath);

    // Check for recent test run results
    $lastRunPath = dirname(__DIR__).'/tests/.last-run.json';
    $lastRun = null;
    if (file_exists($lastRunPath)) {
      $content = file_get_contents($lastRunPath);
      $lastRun = ($content !== false) ? json_decode($content, true) : null;
    }
    $lastRun = self::normalizeAssoc(is_array($lastRun) ? $lastRun : null);

    // Generate phase progress HTML
    $phaseProgressHtml = self::generatePhaseProgressHtml($metrics);

    // Generate test class list HTML
    $testClassListHtml = self::generateTestClassListHtml($metrics);

    // Render the template
    echo Render::template('admin-test-dashboard', [
        '__PAGE_LABEL__' => self::batchI18n('TESTS_DASHBOARD_PAGE_LABEL'),
      '__PAGE_LANGUAGE__' => self::asString(User::current()->language),
        '__TOTAL_TESTS__' => (string) self::asInt($metrics['totalTests'] ?? 0),
        '__TOTAL_ASSERTIONS__' => (string) self::asInt($metrics['totalAssertions'] ?? 0),
        '__ADMIN_TEST_METRICS_SUMMARY_ARIA__' => self::batchI18n('ADMIN_TEST_METRICS_SUMMARY_ARIA'),
        '__ADMIN_TEST_TOTAL_TESTS__' => self::batchI18n('ADMIN_TEST_TOTAL_TESTS'),
        '__ADMIN_TEST_TOTAL_ASSERTIONS__' => self::batchI18n('ADMIN_TEST_TOTAL_ASSERTIONS'),
        '__ADMIN_TEST_PASS_RATE__' => self::batchI18n('ADMIN_TEST_PASS_RATE'),
        '__ADMIN_TEST_COVERAGE__' => self::batchI18n('ADMIN_TEST_COVERAGE'),
        '__ADMIN_TEST_RUNNER_ARIA__' => self::batchI18n('ADMIN_TEST_RUNNER_ARIA'),
        '__ADMIN_TEST_RUN_SUITE_TITLE__' => self::batchI18n('ADMIN_TEST_RUN_SUITE_TITLE'),
        '__ADMIN_TEST_RUN_SUITE_SUBTITLE__' => self::batchI18n('ADMIN_TEST_RUN_SUITE_SUBTITLE'),
        '__ADMIN_TEST_RUN_ALL_TESTS__' => self::batchI18n('ADMIN_TEST_RUN_ALL_TESTS'),
        '__ADMIN_TEST_DOWNLOAD_REPORT__' => self::batchI18n('ADMIN_TEST_DOWNLOAD_REPORT'),
        '__ADMIN_TEST_RUNNING_TESTS__' => self::batchI18n('ADMIN_TEST_RUNNING_TESTS'),
        '__ADMIN_TEST_PHASE_PROGRESS_ARIA__' => self::batchI18n('ADMIN_TEST_PHASE_PROGRESS_ARIA'),
        '__ADMIN_TEST_IMPLEMENTATION_PROGRESS__' => self::batchI18n('ADMIN_TEST_IMPLEMENTATION_PROGRESS'),
        '__ADMIN_TEST_CLASS_LIST_ARIA__' => self::batchI18n('ADMIN_TEST_CLASS_LIST_ARIA'),
        '__ADMIN_TEST_CLASSES_TITLE__' => self::batchI18n('ADMIN_TEST_CLASSES_TITLE'),
        '__PHASE_PROGRESS_HTML__' => $phaseProgressHtml,
        '__TEST_CLASS_LIST_HTML__' => $testClassListHtml,
        '__LAST_RUN_HTML__' => self::generateLastRunHtml($lastRun),
      '__SITE__' => '/',
      '__CSP_NONCE__' => self::asString($_SERVER['CSP_NONCE'] ?? ''),
    ]);
  }

  /**
   * Parse the test plan markdown file to extract metrics.
    * @return array<string, mixed>
   */
  private static function parseTestPlan(string $path): array
  {
    if (!file_exists($path)) {
      return [
          'totalTests' => 0,
          'totalAssertions' => 0,
          'phases' => [],
          'classes' => [],
      ];
    }

    $content = file_get_contents($path);
    if ($content === false) {
      return [
          'totalTests' => 0,
          'totalAssertions' => 0,
          'phases' => [],
          'classes' => [],
      ];
    }

    $metrics = [
        'totalTests' => 0,
        'totalAssertions' => 0,
        'phases' => [],
        'classes' => [],
    ];

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

    $metrics['classes'] = self::parseClassStatusLines($content);

    return $metrics;
  }

  /**
   * Parse class status rows from test plan markdown text.
   *
   * @return array<int, array{name: string, status: string, tests: int, assertions: int}>
   */
  private static function parseClassStatusLines(string $content): array
  {
    $rows = [];
    $lines = preg_split('/\R/u', $content);
    if (!is_array($lines)) {
      return $rows;
    }

    foreach ($lines as $line) {
      $parsed = self::parseClassStatusLine($line);
      if ($parsed !== null) {
        $rows[] = $parsed;
      }
    }

    return $rows;
  }

  /**
   * Parse one class status line.
   *
   * Expected examples:
   * - "- ✅ InputSanitizer (33 tests, 88 assertions)"
   * - "* X PayrollContractFreezeTest (12 tests, 44 assertions)"
   *
   * @return array{name: string, status: string, tests: int, assertions: int}|null
   */
  private static function parseClassStatusLine(string $line): ?array
  {
    if (!preg_match('/^\s*[-*•]\s+(\S+)\s+([A-Za-z_\\\\][A-Za-z0-9_\\\\]*)\s*\((\d+)\s*tests?,\s*(\d+)\s*assertions?\)\s*$/u', $line, $match)) {
      return null;
    }

    return [
      'name' => $match[2],
      'status' => $match[1],
      'tests' => (int) $match[3],
      'assertions' => (int) $match[4],
    ];
  }

  /**
   * Generate HTML for phase progress bars.
    * @param array<string, mixed> $metrics
   */
  private static function generatePhaseProgressHtml(array $metrics): string
  {
    $phases = $metrics['phases'] ?? [];
    if (!is_array($phases) || [] === $phases) {
      return '<p>' . self::batchI18n('TESTS_DASHBOARD_NO_PHASE_DATA') . '</p>';
    }

    $html = '<div class="phase_progress_container">';
    foreach ($phases as $phase) {
      if (!is_array($phase)) {
        continue;
      }

      $percentage = self::asInt($phase['percentage'] ?? 0);
      $phaseNumber = self::asString($phase['number'] ?? '');
      $phaseStatus = self::asString($phase['status'] ?? '');
      $phaseCompleted = self::asInt($phase['completed'] ?? 0);
      $phaseTotal = self::asInt($phase['total'] ?? 0);
      // 100 represents complete (all tests passing)
      $statusClass = 100 === $percentage ? 'complete' : 'partial';

      $html .= '<div class="phase_row">';
      $html .= '<div class="phase_label">';
      $html .= '<strong>' . self::batchI18n('TESTS_DASHBOARD_PHASE_PREFIX') . ' '.$phaseNumber.':</strong> ';
      $html .= $phaseStatus.' ('.$phaseCompleted.'/'.$phaseTotal.')';
      $html .= '</div>';
      $html .= '<div class="phase_bar_container">';
      $html .= '<div class="phase_bar '.$statusClass.'" data-percentage="'.$percentage.'">';
      $html .= '<span class="phase_percentage">'.$percentage.'%</span>';
      $html .= '</div>';
      $html .= '</div>';
      $html .= '</div>';
    }
    $html .= '</div>';

    return $html;
  }

  /**
   * Generate HTML for test class list.
    * @param array<string, mixed> $metrics
   */
  private static function generateTestClassListHtml(array $metrics): string
  {
    $classes = $metrics['classes'] ?? [];
    if (!is_array($classes) || [] === $classes) {
      return '<p>' . self::batchI18n('TESTS_DASHBOARD_NO_TEST_CLASSES') . '</p>';
    }

    $html = '<table class="test_class_table">';
    $html .= '<thead><tr>';
    $html .= '<th>' . self::batchI18n('TESTS_DASHBOARD_STATUS') . '</th>';
    $html .= '<th>' . self::batchI18n('TESTS_DASHBOARD_CLASS') . '</th>';
    $html .= '<th>' . self::batchI18n('TESTS_DASHBOARD_TESTS') . '</th>';
    $html .= '<th>' . self::batchI18n('TESTS_DASHBOARD_ASSERTIONS') . '</th>';
    $html .= '</tr></thead>';
    $html .= '<tbody>';

    foreach ($classes as $class) {
      if (!is_array($class)) {
        continue;
      }

      $statusIcon = htmlspecialchars(self::asString($class['status'] ?? ''));
      $className = htmlspecialchars(self::asString($class['name'] ?? ''));
      $tests = self::asInt($class['tests'] ?? 0);
      $assertions = self::asInt($class['assertions'] ?? 0);

      $html .= '<tr>';
      $html .= '<td class="status_icon">'.$statusIcon.'</td>';
      $html .= '<td class="class_name">'.$className.'</td>';
      $html .= '<td class="test_count">'.$tests.'</td>';
      $html .= '<td class="assertion_count">'.$assertions.'</td>';
      $html .= '</tr>';
    }

    $html .= '</tbody></table>';

    return $html;
  }

  /**
   * Generate HTML for last test run results.
    * @param null|array<string, mixed> $lastRun
   */
  private static function generateLastRunHtml(?array $lastRun): string
  {
    if (null === $lastRun) {
      return '<p class="no_last_run">' . self::batchI18n('TESTS_DASHBOARD_NO_LAST_RUN') . '</p>';
    }

    $success = self::asBool($lastRun['success'] ?? false);
    $timestamp = self::asString($lastRun['timestamp'] ?? 'Unknown', 'Unknown');
    $testCount = self::asInt($lastRun['testCount'] ?? 0);
    $assertionCount = self::asInt($lastRun['assertionCount'] ?? 0);
    $failures = self::asInt($lastRun['failures'] ?? 0);
    $duration = self::asFloat($lastRun['duration'] ?? 0);

    $statusClass = $success ? 'success' : 'failure';
    $statusIcon = $success ? '✅' : '❌';
    $statusText = $success ? 'PASSED' : 'FAILED';

    $html = '<div class="last_run_container '.$statusClass.'">';
    $html .= '<div class="last_run_header">';
    $html .= '<span class="status_icon">'.$statusIcon.'</span>';
    $html .= '<strong>'.$statusText.'</strong>';
    $html .= '<span class="timestamp">'.htmlspecialchars($timestamp).'</span>';
    $html .= '</div>';
    $html .= '<div class="last_run_stats">';
    $html .= '<div class="stat"><label>' . self::batchI18n('TESTS_DASHBOARD_TESTS') . ':</label> <span>' . Strings::formatLocalizedNumber($testCount, 0, 0) . '</span></div>';
    $html .= '<div class="stat"><label>' . self::batchI18n('TESTS_DASHBOARD_ASSERTIONS') . ':</label> <span>' . Strings::formatLocalizedNumber($assertionCount, 0, 0) . '</span></div>';
    $html .= '<div class="stat"><label>' . self::batchI18n('TESTS_DASHBOARD_FAILURES') . ':</label> <span>' . Strings::formatLocalizedNumber($failures, 0, 0) . '</span></div>';
    $html .= '<div class="stat"><label>' . self::batchI18n('TESTS_DASHBOARD_DURATION') . ':</label> <span>' . Strings::formatLocalizedNumber($duration, 2, 2) . 's</span></div>';
    $html .= '</div>';

    if (!empty($lastRun['output'])) {
      $html .= '<details class="test_output">';
      $html .= '<summary>' . self::batchI18n('TESTS_DASHBOARD_VIEW_OUTPUT') . '</summary>';
      $html .= '<pre>'.htmlspecialchars(self::asString($lastRun['output'])).'</pre>';
      $html .= '</details>';
    }

    $html .= '</div>';

    return $html;
  }
}

