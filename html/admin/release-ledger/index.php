<?php declare(strict_types=1);

use PayCal\Domain\AdminSurface;
use PayCal\Domain\Authentication;
use PayCal\Domain\ReleaseLedgerStatus;
use PayCal\Domain\Render;

require_once __DIR__ . '/../../config.php';

Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/release-ledger/');

$pageTitle = 'Release Ledger - [PayCal]';
$pageLabel = 'Release Ledger';
$rows = ReleaseLedgerStatus::environments();
$summary = ReleaseLedgerStatus::summary();

require_once HTML . '/header.php';

$rawCspNonce = $_SERVER['CSP_NONCE'] ?? '';
$cspNonce = htmlspecialchars(is_scalar($rawCspNonce) ? (string) $rawCspNonce : '', ENT_QUOTES, 'UTF-8');
echo PHP_EOL . '<link rel="stylesheet" href="' . htmlspecialchars(Render::cssURL('admin'), ENT_QUOTES, 'UTF-8') . '" nonce="' . $cspNonce . '">' . PHP_EOL;

$h = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$v = static fn(array $row, string $key): string => is_scalar($row[$key] ?? null) ? trim((string) $row[$key]) : '';
$shortSha = static fn(string $sha): string => $sha === '' ? 'missing' : substr($sha, 0, 12);
$stateClass = static fn(string $state): string => $state === 'clean' ? 'status-clean' : (str_contains($state, 'missing') ? 'status-missing' : 'status-drift');
?>
<section class="admin-ledger-page panel w100 pad_md" aria-labelledby="release_ledger_title">
  <div class="admin-feedback-header">
    <div>
      <h1 id="release_ledger_title">Release Ledger</h1>
      <p class="text-muted">Approved, desired, deployed, and runtime SHA alignment across PayCal Technologies targets.</p>
    </div>
    <a class="btn btn_secondary" href="/admin/">Back to Admin</a>
  </div>

  <div class="ledger-summary-grid" aria-label="Ledger summary">
    <div><strong><?= $h((string) $summary['total']) ?></strong><span>Targets</span></div>
    <div><strong><?= $h((string) $summary['clean']) ?></strong><span>Clean</span></div>
    <div><strong><?= $h((string) $summary['drift']) ?></strong><span>Drift</span></div>
    <div><strong><?= $h((string) $summary['missing']) ?></strong><span>Missing Proof</span></div>
  </div>

  <div class="admin-feedback-table ledger-table" role="region" aria-label="Release ledger targets" tabindex="0">
    <table>
      <thead>
        <tr>
          <th>Product</th>
          <th>Target</th>
          <th>Status</th>
          <th>Version</th>
          <th>Release</th>
          <th>Desired</th>
          <th>Deployed</th>
          <th>Runtime</th>
          <th>Health</th>
          <th>Last Receipt</th>
          <th>Rollback</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($rows === []) { ?>
        <tr><td colspan="11">No release ledger records found.</td></tr>
      <?php } ?>
      <?php foreach ($rows as $row) {
        $state = $v($row, 'state');
        if ($state === '') {
          $state = 'missing';
        }
      ?>
        <tr>
          <td><?= $h($v($row, 'product')) ?></td>
          <td><?= $h($v($row, 'target')) ?></td>
          <td><span class="ledger-status <?= $h($stateClass($state)) ?>"><?= $h($state) ?></span></td>
          <td><?= $h($v($row, 'version')) ?></td>
          <td><code><?= $h($shortSha($v($row, 'release_sha'))) ?></code></td>
          <td><code><?= $h($shortSha($v($row, 'desired_sha'))) ?></code></td>
          <td><code><?= $h($shortSha($v($row, 'deployed_sha'))) ?></code></td>
          <td><code><?= $h($shortSha($v($row, 'runtime_sha'))) ?></code></td>
          <td><?= $h($v($row, 'healthcheck_result')) ?></td>
          <td><?= $h($v($row, 'last_receipt_at')) ?></td>
          <td><code><?= $h($shortSha($v($row, 'last_known_good_sha'))) ?></code></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
</section>
<?php require_once HTML . '/footer.php'; ?>
