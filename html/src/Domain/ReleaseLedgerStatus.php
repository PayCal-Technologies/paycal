<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Read-only release ledger status projection.
 */
final class ReleaseLedgerStatus
{
  /**
   * @return array<int, array<string, mixed>>
   */
  public static function environments(?string $ledgerRoot = null): array
  {
    $root = self::ledgerRoot($ledgerRoot);
    $productsRoot = $root . '/products';
    if (!is_dir($productsRoot)) {
      return [];
    }

    $rows = [];
    foreach (glob($productsRoot . '/*/environments/*', GLOB_ONLYDIR) ?: [] as $environmentRoot) {
      $product = basename(dirname(dirname($environmentRoot)));
      if ($product === '.template') {
        continue;
      }

      $target = basename($environmentRoot);
      $desired = self::readJson($environmentRoot . '/desired.json');
      $receipt = self::readJson($environmentRoot . '/latest-receipt.json');
      $runtime = self::readJson($environmentRoot . '/runtime-proof.json');
      $rollback = self::readJson($environmentRoot . '/last-known-good.json');

      $releaseSha = self::stringValue($desired, 'app_sha');
      $desiredSha = self::stringValue($desired, 'desired_sha');
      $deployedSha = self::stringValue($receipt, 'checked_out_sha');
      $runtimeSha = self::stringValue($runtime, 'runtime_sha');
      $health = self::stringValue($receipt, 'healthcheck_result');
      $state = self::state($releaseSha, $desiredSha, $deployedSha, $runtimeSha, $health);

      $rows[] = [
        'product' => $product,
        'target' => $target,
        'version' => self::stringValue($desired, 'desired_version'),
        'state' => $state,
        'release_sha' => $releaseSha,
        'desired_sha' => $desiredSha,
        'deployed_sha' => $deployedSha,
        'runtime_sha' => $runtimeSha,
        'healthcheck_result' => $health,
        'last_receipt_at' => self::stringValue($receipt, 'created_at'),
        'runtime_proof_at' => self::stringValue($runtime, 'created_at'),
        'last_known_good_sha' => self::stringValue($rollback, 'known_good_sha'),
        'deploy_mode' => self::stringValue($desired, 'deploy_mode'),
        'healthcheck_path' => self::stringValue($desired, 'healthcheck_path'),
      ];
    }

    usort($rows, static fn(array $a, array $b): int => ((string) $a['product'] . (string) $a['target']) <=> ((string) $b['product'] . (string) $b['target']));
    return $rows;
  }

  /**
   * @return array<string, int>
   */
  public static function summary(?string $ledgerRoot = null): array
  {
    $summary = ['total' => 0, 'clean' => 0, 'drift' => 0, 'missing' => 0];
    foreach (self::environments($ledgerRoot) as $row) {
      $summary['total']++;
      $state = self::stringValue($row, 'state');
      if ($state === '') {
        $state = 'missing';
      }
      if ($state === 'clean') {
        $summary['clean']++;
      } elseif (str_contains($state, 'missing')) {
        $summary['missing']++;
      } else {
        $summary['drift']++;
      }
    }

    return $summary;
  }

  /**
   * Ledger root.
   */
  private static function ledgerRoot(?string $ledgerRoot): string
  {
    if ($ledgerRoot !== null && trim($ledgerRoot) !== '') {
      return rtrim($ledgerRoot, '/');
    }

    $env = getenv('PAYCAL_LEDGER_ROOT');
    if (is_string($env) && trim($env) !== '') {
      return rtrim($env, '/');
    }

    foreach (['/private/var/www/paycal-ledgers', '/home/deploy/paycal-ledgers', '/var/www/paycal-ledgers'] as $candidate) {
      if (is_dir($candidate)) {
        return $candidate;
      }
    }

    return '/private/var/www/paycal-ledgers';
  }

  /**
   * @return array<string, mixed>
   */
  private static function readJson(string $path): array
  {
    if (!is_file($path)) {
      return [];
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * @param array<string, mixed> $row
   */
  private static function stringValue(array $row, string $key): string
  {
    $value = $row[$key] ?? '';
    return is_scalar($value) ? trim((string) $value) : '';
  }

  /**
   * State.
   */
  private static function state(string $releaseSha, string $desiredSha, string $deployedSha, string $runtimeSha, string $health): string
  {
    if ($releaseSha === '' || $desiredSha === '') {
      return 'missing desired state';
    }
    if ($deployedSha === '') {
      return 'missing receipt';
    }
    if ($runtimeSha === '') {
      return 'missing runtime proof';
    }
    if ($releaseSha !== $desiredSha) {
      return 'release mismatch';
    }
    if ($desiredSha !== $deployedSha) {
      return 'deployed mismatch';
    }
    if ($desiredSha !== $runtimeSha) {
      return 'runtime mismatch';
    }
    if ($health !== 'pass') {
      return 'health check failed';
    }

    return 'clean';
  }
}
