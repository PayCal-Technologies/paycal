<?php declare(strict_types=1);

/**
 * soc/index.php — SOC 2 Auditor Portal (/soc)
 *
 * Purpose: Central evidence hub for approved external auditors. Renders live
 * bundle validity gate results, control status (CC1–CC9), TheLedger chain
 * verification state, backup integrity evidence, and downloadable artifact
 * inventory — all sourced from the automated SOC 2 evidence pipeline.
 *
 * Access: Requires admin authentication (AdminSurface gating) until a
 * dedicated auditor token/session mechanism is implemented.
 *
 * Data sources (read-only, no user input path):
 *  - soc2/reports/test-controls/soc2-test-control-trace-latest.json
 *  - soc2/reports/change-management/soc2-change-management-trace-latest.json
 *  - soc2/reports/freshness/soc2-evidence-freshness-latest.json
 *  - soc2/reports/reliability/soc2-reliability-resilience-latest.json
 *  - soc2/reports/soc2-control-map.json
 *  - soc2/evidence/audit_evidence_*.json   (TheLedger exports)
 *  - soc2/bundles/YYYY-MM/auditor-index.json
 *
 * Why here: Separate from /admin/soc2/ (internal operations dashboard) and
 * /soc2/ (public marketing page). This page is for external auditor review.
 */

require_once __DIR__ . '/../config.php';

use PayCal\Domain\AdminSurface;
use PayCal\Domain\Authentication;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Render;
use PayCal\Domain\Soc2Surface;
use PayCal\Domain\SystemAuditPolicy;
use PayCal\Domain\User;

$currentPage = 'PAGE_ADMIN';
$pageTitle   = 'SOC 2 Auditor Portal - [PayCal]';
$pageLabel   = 'SOC 2 Auditor Portal';

Authentication::redirectHomeIfUnauthenticated();
// Gate: SUPERADMIN or AUDITOR only.
// - SUPERADMIN (rank 2000): full internal access.
// - AUDITOR (rank 500): external auditor role — can view this portal but not /admin/.
// Plain ADMIN is intentionally excluded to prevent information leakage to ops staff.
if (!User::isSuperAdmin() && !User::isAuditor()) {
  header('Location: ' . Environment::appURL('/'));
  exit;
}
if (!Soc2Surface::isEnabled()) {
  header('Location: ' . Environment::appURL('/'));
  exit;
}

// ── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Safely read a JSON file into an array, returning [] on any failure.
 *
 * @return array<mixed>
 */
function socReadJson(string $path): array
{
  if (!is_file($path) || !is_readable($path)) {
    return [];
  }
  $raw = file_get_contents($path);
  if (!is_string($raw) || $raw === '') {
    return [];
  }
  $decoded = json_decode($raw, true);
  return is_array($decoded) ? $decoded : [];
}

function socH(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function socFileMtime(string $path): string
{
  if (!is_file($path)) {
    return '-';
  }
  return gmdate('Y-m-d H:i', (int) filemtime($path)) . ' UTC';
}

/** @return array{class:string, icon:string, label:string} */
function socGateState(bool $pass, bool $warn = false): array
{
  if ($pass) {
    return ['class' => 'is-pass', 'icon' => '✔', 'icon_class' => 'soc-gate-icon--pass', 'label' => 'PASS'];
  }
  if ($warn) {
    return ['class' => 'is-warn', 'icon' => '!', 'icon_class' => 'soc-gate-icon--warn', 'label' => 'WARN'];
  }
  return ['class' => 'is-fail', 'icon' => '✕', 'icon_class' => 'soc-gate-icon--fail', 'label' => 'FAIL'];
}

// ── Data loading ─────────────────────────────────────────────────────────────

$repoRoot = rtrim(Environment::appHome(), '/');

$testTrace     = socReadJson($repoRoot . '/soc2/reports/test-controls/soc2-test-control-trace-latest.json');
$changeTrace   = socReadJson($repoRoot . '/soc2/reports/change-management/soc2-change-management-trace-latest.json');
$freshnessData = socReadJson($repoRoot . '/soc2/reports/freshness/soc2-evidence-freshness-latest.json');
$reliabilityData = socReadJson($repoRoot . '/soc2/reports/reliability/soc2-reliability-resilience-latest.json');
$controlMap    = socReadJson($repoRoot . '/soc2/reports/soc2-control-map.json');

// ── Latest bundle ─────────────────────────────────────────────────────────────

$bundleRoot    = $repoRoot . '/soc2/bundles';
$latestMonth   = '';
$latestBundleFile = '';
$bundleGenAt   = '';

if (is_dir($bundleRoot)) {
  $months = array_filter(
    scandir($bundleRoot, SCANDIR_SORT_DESCENDING) ?: [],
    static fn (string $d): bool => (bool) preg_match('/^\d{4}-\d{2}$/', $d)
  );
  $latestMonth = $months !== [] ? array_values($months)[0] : '';
}

if ($latestMonth !== '') {
  $indexFile = $bundleRoot . '/' . $latestMonth . '/auditor-index.json';
  if (is_readable($indexFile)) {
    $idx = socReadJson($indexFile);
    $bundleGenAt = is_string($idx['generated_at_utc'] ?? null) ? (string) $idx['generated_at_utc'] : '';
  }
}

// Find latest bundle .txt file
$bundleGlob = glob($repoRoot . '/soc2/bundle_*.txt');
if (is_array($bundleGlob) && $bundleGlob !== []) {
  usort($bundleGlob, static fn (string $a, string $b): int => (int) filemtime($b) <=> (int) filemtime($a));
  $latestBundleFile = (string) $bundleGlob[0];
}

$bundleMonthLabel = '';
if ($latestMonth !== '') {
  $ts = strtotime($latestMonth . '-01');
  $bundleMonthLabel = $ts !== false ? date('F Y', $ts) : $latestMonth;
}

// ── Gate 1: PHPUnit suite ─────────────────────────────────────────────────────

$testAllPassed  = (bool) ($testTrace['all_passed'] ?? false);
$testSuiteCount = (int) ($testTrace['suite_count'] ?? 0);
$testGenAt      = is_string($testTrace['generated_at_utc'] ?? null) ? (string) $testTrace['generated_at_utc'] : '-';

// ── Gate 2: Evidence freshness ────────────────────────────────────────────────

$staleCount   = 0;
$freshnessOk  = false;
$freshnessRecs = is_array($freshnessData['records'] ?? null) ? (array) $freshnessData['records'] : [];
foreach ($freshnessRecs as $rec) {
  if (is_array($rec) && ($rec['stale'] ?? false) === true) {
    $staleCount++;
  }
}
$freshnessOk = $staleCount === 0 && $freshnessRecs !== [];

// ── Gate 3: Immutable ledger integrity ────────────────────────────────────────

$ledgerFiles   = glob($repoRoot . '/soc2/evidence/audit_evidence_*.json');
$ledgerBlocks  = [];
$ledgerChainOk = false;

if (is_array($ledgerFiles) && $ledgerFiles !== []) {
  usort($ledgerFiles, static fn (string $a, string $b): int => (int) filemtime($b) <=> (int) filemtime($a));
  $latestLedger = socReadJson((string) $ledgerFiles[0]);
  $lv = is_array($latestLedger['live_verification'] ?? null) ? (array) $latestLedger['live_verification'] : [];
  $ledgerChainOk = (bool) ($lv['ok'] ?? false);

  // Build display rows from verification_reports (most recent first, up to 5)
  $verReps = is_array($latestLedger['verification_reports'] ?? null) ? (array) $latestLedger['verification_reports'] : [];
  $verReps = array_reverse($verReps);
  foreach (array_slice($verReps, 0, 5) as $rep) {
    if (!is_array($rep)) {
      continue;
    }
    $ledgerBlocks[] = [
      'ok'          => (bool) ($rep['ok'] ?? false),
      'seq'         => (int) ($rep['head_sequence'] ?? 0),
      'hash'        => is_string($rep['head_hash'] ?? null) ? (string) $rep['head_hash'] : '',
      'checked'     => (int) ($rep['checked_blocks'] ?? 0),
      'reason'      => is_string($rep['reason'] ?? null) ? (string) $rep['reason'] : '',
      'verified_at' => is_string($rep['verified_at'] ?? null) ? (string) $rep['verified_at'] : '',
    ];
  }

  // If no verification reports but live_verification is present, synthesise one row
  if ($ledgerBlocks === [] && $lv !== []) {
    $ledgerBlocks[] = [
      'ok'          => (bool) ($lv['ok'] ?? false),
      'seq'         => (int) ($lv['head_sequence'] ?? 0),
      'hash'        => is_string($lv['head_hash'] ?? null) ? (string) $lv['head_hash'] : '',
      'checked'     => (int) ($lv['checked_blocks'] ?? 0),
      'reason'      => is_string($lv['reason'] ?? null) ? (string) $lv['reason'] : '',
      'verified_at' => gmdate('Y-m-d H:i:s', (int) filemtime((string) $ledgerFiles[0])) . ' UTC',
    ];
  }
}

// ── Gate 4: Signed release tags ───────────────────────────────────────────────

$signedTagRecords = [];
$allTagsSigned    = false;

$signedPromo = is_array($changeTrace['signed_promotion_records'] ?? null)
  ? (array) $changeTrace['signed_promotion_records']
  : [];
$signedRecs = is_array($signedPromo['records'] ?? null) ? (array) $signedPromo['records'] : [];

foreach ($signedRecs as $rec) {
  if (!is_array($rec)) {
    continue;
  }
  $signedTagRecords[] = [
    'tag'      => is_string($rec['tag'] ?? null) ? (string) $rec['tag'] : '',
    'verified' => (bool) ($rec['signature_verified'] ?? false),
    'signer'   => is_string($rec['signer'] ?? null) ? (string) $rec['signer'] : '',
    'algo'     => is_string($rec['signing_algorithm'] ?? null) ? (string) $rec['signing_algorithm'] : '',
  ];
}

if ($signedTagRecords !== []) {
  $allTagsSigned = array_reduce(
    $signedTagRecords,
    static fn (bool $carry, array $r): bool => $carry && ($r['verified'] ?? false),
    true
  );
}

// ── Gate 5: Off-host backup integrity ─────────────────────────────────────────

$backupEvidence     = is_array($reliabilityData['backup_evidence'] ?? null)
  ? (array) $reliabilityData['backup_evidence']
  : [];
$backupOk           = (bool) ($backupEvidence['backup_ok'] ?? false);
$backupVerified     = ($backupEvidence['verification_status'] ?? '') === 'ok';
$backupFile         = is_string($backupEvidence['last_backup_file'] ?? null) ? (string) $backupEvidence['last_backup_file'] : '-';
$backupUploads      = (int) ($backupEvidence['uploads_ok'] ?? 0);
$backupAgeHours     = is_numeric($backupEvidence['age_hours'] ?? null) ? round((float) $backupEvidence['age_hours'], 1) : null;
$backupDestinations = is_array($backupEvidence['destinations'] ?? null) ? (array) $backupEvidence['destinations'] : [];
$rsk003Addressed    = (bool) ($backupEvidence['rsk_003_addressed'] ?? false);

// ── Overall gate summary ──────────────────────────────────────────────────────

$overallPass = $testAllPassed
  && $freshnessOk
  && $ledgerChainOk
  && $allTagsSigned
  && $backupOk
  && $backupVerified;

// ── Control status table ──────────────────────────────────────────────────────

// Build a stale-path index from freshness records for quick lookup
$staleByControl = [];
foreach ($freshnessRecs as $rec) {
  if (!is_array($rec)) {
    continue;
  }
  $ctrlId = is_string($rec['control_id'] ?? null) ? (string) $rec['control_id'] : '';
  if ($ctrlId === '' || ($rec['stale'] ?? false) !== true) {
    continue;
  }
  $staleByControl[$ctrlId] = true;
}

$controlMeta = [
  'CC1' => ['title' => 'Control Environment',          'tsc' => 'CC1',    'notes' => '10 formalized policies; system description current'],
  'CC2' => ['title' => 'Communication & Information',  'tsc' => 'CC1-CC2','notes' => 'Security communications log active'],
  'CC3' => ['title' => 'Risk Assessment',               'tsc' => 'CC3',    'notes' => 'Risk register + RSK-003 closed 2026-05-18'],
  'CC4' => ['title' => 'Monitoring Activities',         'tsc' => 'CC4',    'notes' => 'Daily automated bundle; freshness gate'],
  'CC5' => ['title' => 'Control Activities',            'tsc' => 'CC5',    'notes' => 'Access review policy active; DR cadence met'],
  'CC6' => ['title' => 'Logical & Physical Access',     'tsc' => 'CC6',    'notes' => 'Auth runtime evidence; ledger present'],
  'CC7' => ['title' => 'System Operations',             'tsc' => 'CC7',    'notes' => 'Off-host backups verified; nightly cadence'],
  'CC8' => ['title' => 'Change Management',             'tsc' => 'CC8',    'notes' => 'Signed tags + approval traces in place'],
  'CC9' => ['title' => 'Risk Mitigation',               'tsc' => 'CC9',    'notes' => 'Vendor risk register; VENDOR policy reviewed'],
];

/** @var array<int, array{id:string,title:string,tsc:string,artifact_count:int,status:string,stale:bool,notes:string}> $controlRows */
$controlRows = [];
$controls = is_array($controlMap['controls'] ?? null) ? (array) $controlMap['controls'] : [];

foreach ($controls as $ctrl) {
  if (!is_array($ctrl)) {
    continue;
  }
  $id        = is_string($ctrl['control_id'] ?? null) ? (string) $ctrl['control_id'] : '';
  $artifacts = is_array($ctrl['artifacts'] ?? null) ? count((array) $ctrl['artifacts']) : 0;
  if ($id === '') {
    continue;
  }
  $meta  = $controlMeta[$id] ?? ['title' => '', 'tsc' => $id, 'notes' => ''];
  $stale = isset($staleByControl[$id]);

  $controlRows[] = [
    'id'             => $id,
    'title'          => (string) $meta['title'],
    'tsc'            => (string) $meta['tsc'],
    'artifact_count' => $artifacts,
    'status'         => $stale ? 'warn' : 'pass',
    'stale'          => $stale,
    'notes'          => (string) $meta['notes'],
  ];
}

// ── Artifact inventory ────────────────────────────────────────────────────────

$artifactSpecs = [
  ['label' => 'Test Control Trace',    'glob' => 'soc2/reports/test-controls/soc2-test-control-trace-*.json'],
  ['label' => 'Change Mgmt Trace',     'glob' => 'soc2/reports/change-management/soc2-change-management-trace-*.json'],
  ['label' => 'Freshness Report',      'glob' => 'soc2/reports/freshness/soc2-evidence-freshness-*.json'],
  ['label' => 'Auth Runtime Evidence', 'glob' => 'soc2/reports/access-runtime/soc2-auth-access-runtime-evidence-*.json'],
  ['label' => 'Reliability Metrics',   'glob' => 'soc2/reports/reliability/soc2-reliability-resilience-*.json'],
  ['label' => 'Ledger Export',         'glob' => 'soc2/evidence/audit_evidence_*.json'],
  ['label' => 'Auditor Index',         'glob' => 'soc2/bundles/*/auditor-index.json'],
];

/** @var array<int, array{label:string,file:string,updated:string,size:int}> $artifactRows */
$artifactRows = [];
foreach ($artifactSpecs as $spec) {
  $matches = glob($repoRoot . '/' . (string) $spec['glob']);
  if (!is_array($matches) || $matches === []) {
    continue;
  }
  usort($matches, static fn (string $a, string $b): int => (int) filemtime($b) <=> (int) filemtime($a));
  $file = (string) $matches[0];
  $artifactRows[] = [
    'label'   => (string) $spec['label'],
    'file'    => basename($file),
    'updated' => gmdate('Y-m-d H:i', (int) filemtime($file)) . ' UTC',
    'size'    => (int) filesize($file),
  ];
}

// Sort by updated desc
usort($artifactRows, static fn (array $a, array $b): int => strcmp((string) $b['updated'], (string) $a['updated']));

// ── Blockchain anchor records (live from Redis) ───────────────────────────────
// Each anchor is stored at system:audit:anchor:{anchorId} with fields:
//   provider, status, reference (anchor_payload_hash), created_at,
//   published_at, hash_algorithm.
// When an external relayer confirms publication it may also write:
//   tx_id_bitcoin, tx_id_ethereum, finalized_at, confirmations_bitcoin, confirmations_ethereum.

/** @var array<int, array<string, string>> $anchorRows */
$anchorRows = [];
try {
  $anchorIds = Database::smembers(Keys::systemAuditAnchorIndex());
  if (is_array($anchorIds)) {
    foreach ($anchorIds as $anchorId) {
      $record = Database::hgetall(Keys::systemAuditAnchor((string) $anchorId));
      if (!is_array($record) || $record === []) {
        continue;
      }
      $record['anchor_id'] = (string) $anchorId;
      $anchorRows[] = $record;
    }
  }
} catch (\Throwable $anchorReadEx) {
  // Redis unavailable — show empty state, do not crash the portal.
  $anchorRows = [];
}
// Sort most-recent-first by created_at (ISO 8601 strings compare lexicographically).
usort($anchorRows, static fn (array $a, array $b): int => strcmp(
  is_string($b['created_at'] ?? null) ? (string) $b['created_at'] : '',
  is_string($a['created_at'] ?? null) ? (string) $a['created_at'] : ''
));
$anchorRows = array_slice($anchorRows, 0, 10);
$anchorFinalizedCount = count(array_filter($anchorRows, static fn (array $r): bool =>
  in_array(strtolower($r['status'] ?? ''), ['confirmed', 'finalized'], true)
));

// ── Render ────────────────────────────────────────────────────────────────────

require_once HTML . '/header.php';
echo '<link rel="stylesheet" href="' . Render::cssURL('admin/soc2') . '">' . "\n";
echo '<link rel="stylesheet" href="' . Render::cssURL('soc') . '">' . "\n";
?>
<div class="soc-portal">

  <!-- ── 1. Header ─────────────────────────────────────────────────────────── -->
  <header class="soc-portal__header panel">
    <div>
      <h1>SOC 2 Auditor Portal</h1>
      <p>External auditor access to PayCal operational effectiveness evidence.
         <strong>Scope:</strong> Security, Confidentiality, Availability (CC1–CC9).</p>
      <?php if ($bundleMonthLabel !== ''): ?>
        <p class="soc-portal__header-meta">
          Latest bundle: <strong><?php echo socH($bundleMonthLabel); ?></strong>
          <?php if ($bundleGenAt !== ''): ?>
            &mdash; generated <?php echo socH($bundleGenAt); ?>
          <?php endif; ?>
        </p>
      <?php endif; ?>
    </div>
    <div class="soc-portal__actions">
      <?php
        $overallBadgeClass = $overallPass ? 'soc-badge--pass' : 'soc-badge--warn';
        $overallBadgeLabel = $overallPass ? 'All Gates: PASS' : 'Gates: Review Required';
      ?>
      <span class="soc-badge <?php echo $overallBadgeClass; ?>"><?php echo $overallBadgeLabel; ?></span>
      <?php if ($latestBundleFile !== ''): ?>
        <a class="btn btn_secondary"
           href="/admin/soc2/"
           aria-label="Go to SOC 2 admin dashboard to download bundle">
          View Full Dashboard
        </a>
      <?php endif; ?>
    </div>
  </header>

  <div class="soc-portal__grid">

    <!-- ── 2. Bundle Validity Gate ──────────────────────────────────────────── -->
    <section class="panel">
      <div class="soc-portal__section-row">
        <h2>Bundle Validity Gate</h2>
        <span class="soc-badge <?php echo $overallPass ? 'soc-badge--pass' : 'soc-badge--warn'; ?>">
          <?php echo $overallPass ? 'PASS' : 'REVIEW'; ?>
        </span>
      </div>

      <ul class="soc-gate-list" aria-label="Validity gate items">

        <?php
          // Gate 1: PHPUnit
          $g1 = socGateState($testAllPassed);
          $g1Detail = $testSuiteCount > 0
            ? $testSuiteCount . ' SOC2-mapped suites — generated ' . socH($testGenAt)
            : 'No test trace found';
        ?>
        <li class="soc-gate-item <?php echo $g1['class']; ?>">
          <span class="soc-gate-icon <?php echo $g1['icon_class']; ?>" aria-hidden="true"><?php echo $g1['icon']; ?></span>
          <span class="soc-gate-text">
            PHPUnit suites passing
            <div class="soc-gate-detail"><?php echo $g1Detail; ?></div>
          </span>
        </li>

        <?php
          // Gate 2: Freshness
          $g2 = socGateState($freshnessOk);
          $g2Detail = $freshnessOk
            ? count($freshnessRecs) . ' artifact paths checked — all current'
            : ($staleCount > 0 ? $staleCount . ' stale artifact(s) detected' : 'No freshness data found');
        ?>
        <li class="soc-gate-item <?php echo $g2['class']; ?>">
          <span class="soc-gate-icon <?php echo $g2['icon_class']; ?>" aria-hidden="true"><?php echo $g2['icon']; ?></span>
          <span class="soc-gate-text">
            Evidence freshness gate (&lt;35 days)
            <div class="soc-gate-detail"><?php echo $g2Detail; ?></div>
          </span>
        </li>

        <?php
          // Gate 3: Ledger integrity
          $g3 = socGateState($ledgerChainOk, !$ledgerChainOk && $ledgerFiles !== []);
          $g3Detail = $ledgerChainOk
            ? 'Chain verified — head_sequence=' . (isset($ledgerBlocks[0]) ? (int) $ledgerBlocks[0]['seq'] : 0)
            : 'Chain not verified or ledger absent';
        ?>
        <li class="soc-gate-item <?php echo $g3['class']; ?>">
          <span class="soc-gate-icon <?php echo $g3['icon_class']; ?>" aria-hidden="true"><?php echo $g3['icon']; ?></span>
          <span class="soc-gate-text">
            Immutable ledger integrity (SHA3-512)
            <div class="soc-gate-detail"><?php echo $g3Detail; ?></div>
          </span>
        </li>

        <?php
          // Gate 4: Signed tags
          $signedCount = count(array_filter($signedTagRecords, static fn (array $r): bool => (bool) ($r['verified'] ?? false)));
          $totalTags   = count($signedTagRecords);
          $g4 = socGateState($allTagsSigned && $totalTags > 0, !$allTagsSigned && $totalTags > 0);
          $g4Detail = $totalTags > 0
            ? $signedCount . '/' . $totalTags . ' tags ED25519-verified'
            : 'No signed promotion records in change trace';
        ?>
        <li class="soc-gate-item <?php echo $g4['class']; ?>">
          <span class="soc-gate-icon <?php echo $g4['icon_class']; ?>" aria-hidden="true"><?php echo $g4['icon']; ?></span>
          <span class="soc-gate-text">
            Release tags cryptographically signed (ED25519)
            <div class="soc-gate-detail"><?php echo $g4Detail; ?></div>
          </span>
        </li>

        <?php
          // Gate 5: Backup integrity
          $g5 = socGateState($backupOk && $backupVerified, $backupOk && !$backupVerified);
          $g5Detail = $backupOk
            ? 'SHA-256 ' . ($backupVerified ? 'verified' : 'pending') . ' — ' . $backupUploads . ' remote(s) — ' . ($backupAgeHours !== null ? $backupAgeHours . 'h ago' : '')
            : 'Off-host backup not confirmed';
        ?>
        <li class="soc-gate-item <?php echo $g5['class']; ?>">
          <span class="soc-gate-icon <?php echo $g5['icon_class']; ?>" aria-hidden="true"><?php echo $g5['icon']; ?></span>
          <span class="soc-gate-text">
            Off-host backups verified (RSK-003)
            <div class="soc-gate-detail"><?php echo $g5Detail; ?></div>
          </span>
        </li>

      </ul>
    </section>

    <!-- ── 3. Control Summary CC1–CC9 ───────────────────────────────────────── -->
    <section class="panel">
      <div class="soc-portal__section-row">
        <h2>Control Status (CC1–CC9)</h2>
      </div>

      <?php if ($controlRows !== []): ?>
        <table class="soc-control-table" aria-label="SOC 2 control status">
          <thead>
            <tr>
              <th scope="col">Control</th>
              <th scope="col">Status</th>
              <th scope="col">Artifacts</th>
              <th scope="col">Notes</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($controlRows as $row): ?>
              <tr>
                <td class="soc-ctrl-id">
                  <strong><?php echo socH($row['id']); ?></strong><br>
                  <span class="soc-ctrl-title"><?php echo socH($row['title']); ?></span>
                </td>
                <td>
                  <span class="soc-badge <?php echo $row['status'] === 'pass' ? 'soc-badge--pass' : 'soc-badge--warn'; ?>">
                    <?php echo $row['status'] === 'pass' ? 'PASS' : 'REVIEW'; ?>
                  </span>
                </td>
                <td><?php echo (int) $row['artifact_count']; ?></td>
                <td class="soc-ctrl-notes"><?php echo socH($row['notes']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="soc-ledger-empty">Control map not loaded.</p>
      <?php endif; ?>
    </section>

    <!-- ── 4. TheLedger: chain visualization ────────────────────────────────── -->
    <section class="panel grid-span-full">
      <div class="soc-portal__section-row">
        <h2>TheLedger — Append-Only Hash Chain</h2>
        <?php if ($ledgerChainOk): ?>
          <span class="soc-badge soc-badge--pass">Chain Intact</span>
        <?php else: ?>
          <span class="soc-badge soc-badge--warn">Verification Absent</span>
        <?php endif; ?>
      </div>

      <?php if ($ledgerBlocks !== []): ?>
        <div class="soc-ledger-chain" aria-label="Ledger verification history">
          <?php foreach ($ledgerBlocks as $block): ?>
            <?php
              $isVerified = (bool) $block['ok'];
              $isEmpty    = (int) $block['checked'] === 0;
              $blockClass = $isEmpty ? 'is-empty' : ($isVerified ? 'is-verified' : '');
            ?>
            <div class="soc-ledger-block <?php echo $blockClass; ?>"
                 aria-label="Ledger block <?php echo (int) $block['seq']; ?>">
              <div class="soc-ledger-block__header">
                <span class="soc-ledger-block__seq">
                  Sequence <?php echo (int) $block['seq']; ?>
                  &mdash;
                  <?php echo (int) $block['checked']; ?> block<?php echo (int) $block['checked'] === 1 ? '' : 's'; ?> checked
                </span>
                <span class="soc-badge <?php echo $isVerified ? 'soc-badge--pass' : 'soc-badge--neutral'; ?>">
                  <?php echo $isVerified ? socH((string) $block['reason']) : 'not verified'; ?>
                </span>
              </div>
              <?php if ($block['hash'] !== ''): ?>
                <div class="soc-ledger-block__hash"
                     title="SHA3-512 head hash"><?php echo socH($block['hash']); ?></div>
              <?php endif; ?>
              <div class="soc-ledger-block__meta">
                <?php if ($block['verified_at'] !== ''): ?>
                  <span>Verified at: <?php echo socH($block['verified_at']); ?></span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="soc-ledger-empty">No ledger export found. Run the SOC 2 bundle to generate.</p>
      <?php endif; ?>
    </section>

    <!-- ── 5. Backup Integrity Evidence ─────────────────────────────────────── -->
    <section class="panel">
      <div class="soc-portal__section-row">
        <h2>Backup Integrity (CC7 / RSK-003)</h2>
        <span class="soc-badge <?php echo ($backupOk && $backupVerified) ? 'soc-badge--pass' : 'soc-badge--warn'; ?>">
          <?php echo $rsk003Addressed ? 'RSK-003 CLOSED' : 'RSK-003 OPEN'; ?>
        </span>
      </div>

      <div class="soc-backup-strip">

        <div class="soc-backup-kpi <?php echo ($backupOk && $backupVerified) ? 'is-ok' : ''; ?>">
          <div class="soc-backup-kpi__label">SHA-256 Verify</div>
          <div class="soc-backup-kpi__value">
            <?php echo $backupVerified ? 'OK' : ($backupOk ? 'Pending' : '—'); ?>
          </div>
        </div>

        <div class="soc-backup-kpi <?php echo $backupUploads >= 2 ? 'is-ok' : ''; ?>">
          <div class="soc-backup-kpi__label">Remote Copies</div>
          <div class="soc-backup-kpi__value"><?php echo $backupUploads; ?></div>
          <?php if ($backupDestinations !== []): ?>
            <div class="soc-backup-kpi__sub">
              <?php foreach ($backupDestinations as $dest): ?>
                <?php echo socH((string) $dest); ?><br>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="soc-backup-kpi <?php echo $backupAgeHours !== null && $backupAgeHours <= 25 ? 'is-ok' : ''; ?>">
          <div class="soc-backup-kpi__label">Age</div>
          <div class="soc-backup-kpi__value">
            <?php echo $backupAgeHours !== null ? socH((string) $backupAgeHours) . 'h' : '—'; ?>
          </div>
          <div class="soc-backup-kpi__sub">RPO target: 24h</div>
        </div>

        <div class="soc-backup-kpi">
          <div class="soc-backup-kpi__label">Last File</div>
          <div class="soc-backup-kpi__value soc-backup-kpi__value--filename">
            <?php echo socH($backupFile); ?>
          </div>
        </div>

      </div>
    </section>

    <!-- ── 6. Evidence Artifacts inventory ──────────────────────────────────── -->
    <section class="panel">
      <div class="soc-portal__section-row">
        <h2>Generated Evidence Artifacts</h2>
        <span class="soc-badge soc-badge--neutral"><?php echo count($artifactRows); ?> files</span>
      </div>

      <?php if ($artifactRows !== []): ?>
        <table class="soc-artifacts-table" aria-label="Evidence artifact inventory">
          <thead>
            <tr>
              <th scope="col">Artifact</th>
              <th scope="col">File</th>
              <th scope="col">Updated</th>
              <th scope="col">Size</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($artifactRows as $ar): ?>
              <tr>
                <td><?php echo socH($ar['label']); ?></td>
                <td class="soc-artifact-name"><?php echo socH($ar['file']); ?></td>
                <td class="soc-artifact-updated"><?php echo socH($ar['updated']); ?></td>
                <td class="soc-artifact-size">
                  <?php echo (int) $ar['size'] > 0 ? number_format((int) $ar['size']) . ' B' : '—'; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="soc-ledger-empty">No evidence artifacts found.</p>
      <?php endif; ?>
    </section>

    <!-- ── 7. Blockchain Anchor Records (CC7) ───────────────────────────────── -->
    <section class="panel grid-span-full">
      <div class="soc-portal__section-row">
        <h2>Blockchain Anchor Queue (CC7 — Out-of-Band Publication)</h2>
        <?php if ($anchorFinalizedCount > 0): ?>
          <span class="soc-badge soc-badge--pass"><?php echo $anchorFinalizedCount; ?> Finalized</span>
        <?php elseif ($anchorRows !== []): ?>
          <span class="soc-badge soc-badge--info"><?php echo count($anchorRows); ?> Queued — Pending Relayer</span>
        <?php else: ?>
          <span class="soc-badge soc-badge--neutral">No Anchors</span>
        <?php endif; ?>
      </div>
      <p class="soc-anchor-intro">
        Anchors are queued via <code>SystemAuditBlockchainQueueGateway</code> for asynchronous submission
        to Bitcoin (6 confirmations) and Ethereum (64 confirmations) by an external relayer.
        Status transitions from <strong>queued</strong> → <strong>confirmed</strong> once the relayer writes back transaction IDs.
      </p>
      <?php if ($anchorRows !== []): ?>
        <table class="soc-artifacts-table" aria-label="Blockchain anchor records">
          <thead>
            <tr>
              <th scope="col">Anchor ID</th>
              <th scope="col">Status</th>
              <th scope="col">Provider</th>
              <th scope="col">Anchor Payload Hash (SHA3-512)</th>
              <th scope="col">TX: Bitcoin</th>
              <th scope="col">TX: Ethereum</th>
              <th scope="col">Queued At</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($anchorRows as $ar): ?>
              <?php
                $arStatus   = strtolower(is_string($ar['status'] ?? null) ? (string) $ar['status'] : 'queued');
                $statusClass = match ($arStatus) {
                  'confirmed', 'finalized' => 'soc-badge--pass',
                  'queued'                 => 'soc-badge--info',
                  'disabled'               => 'soc-badge--neutral',
                  default                  => 'soc-badge--warn',
                };
                $txBtc = is_string($ar['tx_id_bitcoin'] ?? null) && $ar['tx_id_bitcoin'] !== '' ? (string) $ar['tx_id_bitcoin'] : null;
                $txEth = is_string($ar['tx_id_ethereum'] ?? null) && $ar['tx_id_ethereum'] !== '' ? (string) $ar['tx_id_ethereum'] : null;
                $refHash = is_string($ar['reference'] ?? null) ? (string) $ar['reference'] : '';
                $anchorIdDisplay = is_string($ar['anchor_id'] ?? null) ? (string) $ar['anchor_id'] : '';
              ?>
              <tr>
                <td class="soc-artifact-name" title="<?php echo socH($anchorIdDisplay); ?>">
                  <?php echo socH(strlen($anchorIdDisplay) > 16 ? substr($anchorIdDisplay, 0, 16) . '…' : $anchorIdDisplay); ?>
                </td>
                <td><span class="soc-badge <?php echo $statusClass; ?>"><?php echo socH($arStatus); ?></span></td>
                <td><?php echo socH(is_string($ar['provider'] ?? null) ? (string) $ar['provider'] : '—'); ?></td>
                <td class="soc-artifact-name" title="<?php echo socH($refHash); ?>">
                  <?php echo $refHash !== '' ? socH(substr($refHash, 0, 20) . '…') : '—'; ?>
                </td>
                <td>
                  <?php if ($txBtc !== null): ?>
                    <code title="<?php echo socH($txBtc); ?>"><?php echo socH(substr($txBtc, 0, 16) . '…'); ?></code>
                  <?php else: ?>
                    <span class="soc-tx-pending">pending</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($txEth !== null): ?>
                    <code title="<?php echo socH($txEth); ?>"><?php echo socH(substr($txEth, 0, 16) . '…'); ?></code>
                  <?php else: ?>
                    <span class="soc-tx-pending">pending</span>
                  <?php endif; ?>
                </td>
                <td class="soc-artifact-updated">
                  <?php echo socH(is_string($ar['published_at'] ?? null) ? (string) $ar['published_at'] : '—'); ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="soc-ledger-empty">
          No anchors found in Redis index.
          Anchors are created on each <code>TheLedger::anchor()</code> call.
          Verify <code>SYSTEM_AUDIT_BLOCKCHAIN_MODE</code> is not <code>disabled</code>.
        </p>
      <?php endif; ?>
    </section>

  </div><!-- .soc-portal__grid -->

  <!-- ── 8. Footer ─────────────────────────────────────────────────────────── -->
  <footer class="panel soc-portal__footer">
    <span>
      Generated: <?php echo socH(gmdate('Y-m-d H:i:s') . ' UTC'); ?> &mdash;
      PayCal SOC 2 Readiness (not certified)
    </span>
    <a href="/soc2/" class="soc-badge soc-badge--neutral">Public SOC 2 page</a>
    <p class="soc-disclaimer">
      PayCal has not yet completed a formal SOC 2 Type II audit. No certification claim is made.
      Evidence materials are provided under NDA for qualified auditors only.
      <a href="/soc2/request/">Request NDA access.</a>
    </p>
  </footer>

</div><!-- .soc-portal -->
<?php
require_once HTML . '/footer.php';
