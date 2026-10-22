<?php declare(strict_types=1);

require_once __DIR__ . '/../config.php';

// ── Dynamic SOC2 bundle state ────────────────────────────────────────────────
// Reads the latest monthly bundle index and test-control trace from the repo's
// soc2/ directory so the dashboard always reflects the most recent run without
// manual updates.

$soc2Root = rtrim(\PayCal\Domain\Config\Environment::appHome(), '/') . '/soc2';
$bundleRoot = $soc2Root . '/bundles';
$traceFile = $soc2Root . '/reports/test-controls/soc2-test-control-trace-latest.json';

// Find the latest YYYY-MM bundle directory.
$latestMonth = '';
$latestBundleDate = 'Unknown';
$artifactCount = 0;
$controlIds = [];
$generatedAt = '';
$freshnessLabel = 'Unknown';
$bundleMonthCount = 0;

if (is_dir($bundleRoot)) {
    $months = array_filter(
        scandir($bundleRoot, SCANDIR_SORT_DESCENDING) ?: [],
        static fn(string $d): bool => (bool) preg_match('/^\d{4}-\d{2}$/', $d)
    );
    $bundleMonthCount = count($months);
    $latestMonth = $months !== [] ? array_values($months)[0] : '';
}

if ($latestMonth !== '') {
    $indexFile = $bundleRoot . '/' . $latestMonth . '/auditor-index.json';
    if (is_readable($indexFile)) {
        $idx = json_decode((string) file_get_contents($indexFile), true);
        if (is_array($idx)) {
            $entries = is_array($idx['entries'] ?? null) ? $idx['entries'] : [];
            $artifactCount = count($entries);
            foreach ($entries as $e) {
                if (is_array($e) && is_string($e['control_id'] ?? null)) {
                    $controlIds[(string) $e['control_id']] = true;
                }
            }
            $generatedAt = is_string($idx['generated_at_utc'] ?? null) ? $idx['generated_at_utc'] : '';
        }
    }
    // Format month label: "2026-05" → "May 2026"
    $ts = strtotime($latestMonth . '-01');
    if ($ts !== false) {
        $latestBundleDate = date('F Y', $ts);
    }
}

if ($generatedAt !== '') {
    $ts = strtotime($generatedAt);
    if ($ts !== false) {
        $diffSec = time() - $ts;
        if ($diffSec < 3600) {
            $freshnessLabel = 'less than 1 hour ago';
        } elseif ($diffSec < 86400) {
            $h = (int) floor($diffSec / 3600);
            $freshnessLabel = $h . ' hour' . ($h === 1 ? '' : 's') . ' ago';
        } else {
            $d = (int) floor($diffSec / 86400);
            $freshnessLabel = $d . ' day' . ($d === 1 ? '' : 's') . ' ago';
        }
    }
}

$suiteCount = 0;
$suitesPassed = false;
if (is_readable($traceFile)) {
    $trace = json_decode((string) file_get_contents($traceFile), true);
    if (is_array($trace)) {
        $suitesPassed = ($trace['all_passed'] ?? false) === true;
        $raw = $trace['suite_count'] ?? 0;
        $suiteCount = is_int($raw) ? $raw : (int) (is_numeric($raw) ? $raw : 0);
    }
}

$controlCount = count($controlIds);

// ── End dynamic bundle state ─────────────────────────────────────────────────

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'SOC 2 Compliance - [PayCal]';
$pageLabel = 'SOC 2 Compliance';
$pageMetaDescription = 'PayCal SOC 2 readiness: CC1-CC9 control mapping, evidence bundles, test-control traces, and NDA-gated report access.';
$pageMetaDescriptionLong = 'PayCal is building toward SOC 2 compliance with CC1-CC9 control mapping, continuous evidence generation, test-control traces, and report access through an NDA request workflow.';
$pageStylesheets = ['transparency'];

require_once HTML . '/header.php';
?>
<article class="article doc-article">
  <header class="doc-article-header">
    <h1>PayCal SOC 2 Readiness</h1>
    <p class="deck">PayCal is building toward SOC 2 compliance, subject to independent auditor validation. All Trust Services Criteria (CC1–CC9) are mapped to verifiable evidence.</p>
  </header>

  <section class="doc-article-body">
    <section class="doc-section highlight" aria-labelledby="october-evidence-status">
      <h2 id="october-evidence-status">Evidence review, October 22, 2026</h2>
      <p>All ten automated test control suites passed in the local verification. Twelve policy and operational records still need review. The evidence freshness gate remains blocked, and SOC 2 readiness is in progress.</p>
      <p>This dated review does not establish certification, continuous uptime or delivery of alert emails. <a href="https://paycaltech.com/transparency/soc2-readiness-2026-10/">Read the evidence and monitoring update</a>.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Status</h2>
      <ul class="doc-fact-list">
        <li><strong>Readiness:</strong> SOC 2 readiness in progress</li>
        <li><strong>Controls:</strong> CC1–CC9 Mapped</li>
        <li><strong>Last Evidence Bundle:</strong> <?php echo htmlspecialchars($latestBundleDate, ENT_QUOTES, 'UTF-8'); ?></li>
        <li><strong>Bundle Generated:</strong> <?php echo htmlspecialchars($freshnessLabel, ENT_QUOTES, 'UTF-8'); ?></li>
        <li><strong>Schedule:</strong> Job success must be verified from its latest result</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Evidence Summary</h2>
      <ul class="doc-fact-list">
        <li><?php echo $artifactCount; ?> evidence artifacts in latest bundle</li>
        <li><?php echo $controlCount; ?> control IDs mapped (<?php echo $bundleMonthCount; ?> monthly bundle<?php echo $bundleMonthCount === 1 ? '' : 's'; ?> on record)</li>
        <li><?php echo $suiteCount; ?> SOC2-mapped test suite<?php echo $suiteCount === 1 ? '' : 's'; ?> (<?php echo $suitesPassed ? 'latest trace reports all passing' : 'passing status not established'; ?>)</li>
        <li>Automated evidence generation does not replace operational review</li>
      </ul>
    </section>

    <section id="soc2-system" class="doc-section">
      <h2>Security Enforcement Model</h2>
      <p>Controls are programmatically enforced through authentication systems, runtime monitoring, input sanitization, and automated test gates.</p>
      <ul class="doc-fact-list">
        <li>Passkey-capable authentication and access control flows</li>
        <li>Runtime integrity monitoring and audit-event capture</li>
        <li>Guardian sanitization controls on sensitive output paths</li>
        <li>Full-suite PHPUnit gate before compliance bundle finalization</li>
      </ul>
    </section>

    <section class="doc-section success">
      <h2>Scope Clarification</h2>
      <p>PayCal has not yet completed a formal SOC 2 audit. No SOC 2 certification claim or auditor opinion is made on this page.</p>
      <p>Detailed report materials are available under NDA request workflow.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Learn More and Request Access</h2>
      <p><a class="doc-read-more" href="/security/">Open Security Trust Hub</a></p>
      <p><a class="doc-read-more" href="/transparency/soc2/">Read Technical Transparency Article</a></p>
      <p><a class="doc-read-more" href="/soc2/request/">Start SOC 2 NDA Request</a></p>
    </section>
  </section>
</article>
<?php
require_once HTML . '/footer.php';
