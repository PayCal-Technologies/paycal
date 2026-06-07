<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Response;
use PayCal\Domain\User;

/**
 * Soc2StatusController.php
 *
 * Purpose: Internal read-only SOC2 status endpoint for operational and audit
 * continuity visibility.
 *
 * Developer notes:
 * - Keep endpoint admin-gated because lifecycle and exception posture are
 *   sensitive operational signals.
 * - This endpoint reports computed status only and does not mutate artifacts.
 */
final class Soc2StatusController
{
  #[Route('internal/soc2/status', ['GET'])]
  public function getSoc2Status(): void
  {
    if (!User::isAdmin()) {
      Response::json('error', 'Forbidden: Admin access required.', 403);
      return;
    }

    $repoRoot = dirname(__DIR__, 3);
    $month = isset($_GET['month']) && is_string($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month'])
      ? $_GET['month']
      : gmdate('Y-m');

    $status = $this->loadJson($repoRoot . '/soc2/reports/lifecycle/soc2-control-status-latest.json');
    $exceptions = $this->loadJson($repoRoot . '/soc2/index/exception-log.json');
    $runtime = $this->loadJson($repoRoot . '/soc2/reports/runtime/soc2-runtime-baseline-comparison-latest.json');
    $regressions = $this->loadJson($repoRoot . '/soc2/reports/lifecycle/soc2-control-regressions-latest.json');
    $sla = $this->loadJson($repoRoot . '/soc2/reports/exceptions/soc2-exception-sla-latest.json');

    $openExceptions = 0;
    foreach ((array) ($exceptions['exceptions'] ?? []) as $row) {
      if (is_array($row) && strtoupper($this->stringValue($row['status'] ?? '')) === 'OPEN') {
        $openExceptions++;
      }
    }

    Response::json('ok', 'SOC2 internal status snapshot.', 200, [
      'schema_version' => 'v1',
      'generated_at_utc' => gmdate('c'),
      'month' => $month,
      'overall_status' => $this->stringValue($status['overall_status'] ?? 'WARN', 'WARN'),
      'control_summary' => (array) ($status['summary'] ?? []),
      'open_exception_count' => $openExceptions,
      'runtime_warn_count' => $this->intValue($runtime['warn_count'] ?? 0),
      'regression_count' => $this->intValue($regressions['regression_count'] ?? 0),
      'exception_sla_overdue_count' => $this->intValue($sla['overdue_count'] ?? 0),
      'sources' => [
        'control_status' => 'soc2/reports/lifecycle/soc2-control-status-latest.json',
        'exception_log' => 'soc2/index/exception-log.json',
        'runtime_baseline' => 'soc2/reports/runtime/soc2-runtime-baseline-comparison-latest.json',
        'control_regressions' => 'soc2/reports/lifecycle/soc2-control-regressions-latest.json',
        'exception_sla' => 'soc2/reports/exceptions/soc2-exception-sla-latest.json',
      ],
    ]);
  }

  /** @return array<string, mixed> */
  private function loadJson(string $path): array
  {
    $raw = @file_get_contents($path);
    $decoded = json_decode((string) $raw, true);
    return is_array($decoded) ? $decoded : [];
  }

  private function stringValue(mixed $value, string $fallback = ''): string
  {
    return is_scalar($value) ? (string) $value : $fallback;
  }

  private function intValue(mixed $value, int $fallback = 0): int
  {
    return is_int($value)
      ? $value
      : (is_numeric($value) ? (int) $value : $fallback);
  }
}
