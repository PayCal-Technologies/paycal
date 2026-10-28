<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'TRANSPARENCY_SOC2_PAGE_TITLE',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_SOC2_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_SOC2_PAGE_TITLE'];

require_once HTML . '/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo htmlspecialchars($i18n['BREADCRUMB'], ENT_QUOTES, 'UTF-8'); ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo htmlspecialchars($i18n['HELP_TOC_TRANSPARENCY_HUB'], ENT_QUOTES, 'UTF-8'); ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo htmlspecialchars($i18n['TRANSPARENCY_SOC2_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_SOC2_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">A current technical snapshot of how PayCal maps SOC 2 controls to evidence, runtime signals, and remediation work.</p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-15">2026-04-15</time> &middot; Snapshot updated: <time datetime="2026-08-11">2026-08-11</time></p>
  </header>

  <section class="doc-article-body">
    <section class="doc-section highlight">
      <h2>1. August 2026 Snapshot</h2>
      <p>PayCal operates a SOC 2-oriented readiness program with deterministic evidence generation, control mapping, and public transparency. The current snapshot is evidence-backed but not a certification claim.</p>
      <ul class="doc-fact-list">
        <li><strong>Snapshot generated:</strong> 2026-08-11 01:54 UTC</li>
        <li><strong>Controls in scope:</strong> CC1-CC9</li>
        <li><strong>Current control status artifact:</strong> 8 pass, 0 warn, 1 fail, generated 2026-08-03</li>
        <li><strong>Evidence completeness artifact:</strong> 100 percent, generated 2026-08-03</li>
        <li><strong>Freshness check:</strong> 42 mapped records checked on 2026-08-11; 9 are stale under the 35-day policy</li>
        <li><strong>Test-control trace:</strong> 10 SOC 2-mapped PHPUnit suites, all passing</li>
        <li><strong>Service state:</strong> Nginx active, PHP-FPM active</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>2. Current Exception</h2>
      <p>The August status snapshot currently marks CC6, Logical and Physical Access Controls, as failed because runtime access evidence is synthetic-only. Enforcement and audit tests pass, but the runtime access evidence stream has not yet captured enough real production access events to support the control without qualification.</p>
      <ul class="doc-fact-list">
        <li><strong>Affected control:</strong> CC6</li>
        <li><strong>Reason:</strong> Runtime access evidence is synthetic-only; no real production events captured.</li>
        <li><strong>Owner role:</strong> Identity and Access</li>
        <li><strong>Target remediation:</strong> Restore access telemetry capture and validate RBAC/runtime evidence generation.</li>
      </ul>
    </section>

    <section class="doc-section success">
      <h2>3. Server Activity Added To Evidence</h2>
      <p>A redacted server-activity artifact was generated from production logs for SOC 2 operational evidence. It aggregates counts only and does not publish raw IP addresses, user agents, or request URLs.</p>
      <ul class="doc-fact-list">
        <li><strong>24-hour request count:</strong> 2,083</li>
        <li><strong>HTTP status mix:</strong> 1,968 successful 200 responses, 48 permanent redirects, 44 application redirects, 18 not found responses</li>
        <li><strong>Trust route activity:</strong> 230 hits across security, SOC 2, transparency, and status routes</li>
        <li><strong>Security activity:</strong> 214 recent sensitive-path probe blocks in error-log tails</li>
        <li><strong>Operational maintenance:</strong> package maintenance timers active; Nginx reload and PHP-FPM restart completed on 2026-08-11</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>4. Control Coverage</h2>
      <p>All SOC 2 Common Criteria controls in scope are mapped to retained evidence. The mapped controls cover governance, communication, risk assessment, monitoring, control activities, logical access, system operations, change management, and vendor risk.</p>
      <table class="doc-table" aria-label="SOC 2 control status snapshot">
        <caption>SOC 2 control status snapshot</caption>
        <thead>
          <tr>
            <th scope="col">Control</th>
            <th scope="col">Status</th>
            <th scope="col">Evidence signal</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>CC1</td><td>Pass</td><td>Governance surface contracts, bundle integrity, system description, and control map present.</td></tr>
          <tr><td>CC2</td><td>Pass</td><td>Incident response, vendor risk policy, communications log, and mapped tests present.</td></tr>
          <tr><td>CC3</td><td>Pass</td><td>Risk policy, risk register, and risk-assessment tests present.</td></tr>
          <tr><td>CC4</td><td>Pass</td><td>Monitoring freshness checks, alert proof, and server-activity evidence present.</td></tr>
          <tr><td>CC5</td><td>Pass</td><td>Access review evidence and security invariant tests present.</td></tr>
          <tr><td>CC6</td><td>Fail</td><td>Access enforcement tests pass, but runtime access evidence remains synthetic-only.</td></tr>
          <tr><td>CC7</td><td>Pass</td><td>System operations tests, bundle evidence, runtime quality checks, and service-state evidence present.</td></tr>
          <tr><td>CC8</td><td>Pass</td><td>Change-management trace, approvals export, and release-attestation evidence present.</td></tr>
          <tr><td>CC9</td><td>Pass</td><td>Vendor review cadence and vulnerability cadence reports currently pass.</td></tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>5. Source Files Reviewed</h2>
      <p>The snapshot was prepared one artifact at a time from sanitized private-source evidence and production activity summaries.</p>
      <ul class="doc-fact-list">
        <li><code>soc2/reports/lifecycle/soc2-control-status-latest.json</code></li>
        <li><code>soc2/reports/narrative/soc2-control-narrative-latest.json</code></li>
        <li><code>soc2/reports/completeness/soc2-evidence-completeness-score-latest.json</code></li>
        <li><code>soc2/reports/freshness/soc2-evidence-freshness-latest.json</code></li>
        <li><code>soc2/reports/test-controls/soc2-test-control-trace-latest.json</code></li>
        <li><code>soc2/reports/access-runtime/soc2-auth-access-runtime-evidence-latest.json</code></li>
        <li><code>soc2/reports/operations/soc2-server-activity-latest.json</code></li>
        <li><code>soc2/reports/vendor-risk/soc2-vendor-review-cadence-latest.json</code></li>
        <li><code>soc2/reports/vulnerability-management/soc2-vulnerability-cadence-latest.json</code></li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>6. Certification Boundary</h2>
      <p><strong>Status:</strong> SOC 2 readiness in progress. PayCal does not claim SOC 2 certification or an auditor opinion on this page. Formal report access remains NDA-gated.</p>
    </section>

    <section class="doc-section highlight">
      <h2>References</h2>
      <ul class="doc-fact-list">
        <li><a class="doc-read-more" href="/security/">Security Trust Hub</a></li>
        <li><a class="doc-read-more" href="/soc2/">PayCal SOC 2 Summary</a></li>
        <li><a class="doc-read-more" href="/soc2/request/">Request SOC 2 Report (NDA)</a></li>
        <li><a class="doc-read-more" href="https://www.aicpa-cima.com/topic/audit-assurance/audit-and-assurance-greater-than-soc-2" target="_blank" rel="noopener noreferrer">AICPA SOC 2 official overview</a></li>
      </ul>
    </section>
  </section>
</article>
<?php
require_once HTML . '/footer.php';
