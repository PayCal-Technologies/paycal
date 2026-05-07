<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'SOC 2 Compliance at PayCal - [PayCal]';
$pageLabel = 'SOC 2 Compliance at PayCal';

require_once HTML . '/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="Breadcrumb">
    <a href="<?php echo transparency_href('/transparency/'); ?>">Transparency Hub</a>
    <span class="separator">/</span>
    <span class="current">SOC 2 Compliance at PayCal</span>
  </nav>

  <header class="doc-article-header">
    <h1>PayCal SOC2 Readiness &amp; Security Model</h1>
    <p class="deck">A technical view of how PayCal maps SOC2 controls to enforced system behavior and continuously generated evidence.</p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-15">2026-04-15</time></p>
  </header>

  <section class="doc-article-body">
    <section class="doc-section highlight">
      <h2>1. Overview</h2>
      <p>PayCal operates a SOC2-aligned security program focused on verifiable enforcement and traceable evidence, not policy-only assertions.</p>
      <ul class="doc-fact-list">
        <li><strong>Controls in scope:</strong> CC1-CC9</li>
        <li><strong>Artifacts in current bundle:</strong> 37</li>
        <li><strong>Control-to-artifact mappings:</strong> 26</li>
        <li><strong>Evidence freshness window:</strong> 35 days</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>2. Control Coverage (CC1-CC9)</h2>
      <p>All SOC2 Common Criteria controls in scope (CC1 through CC9) are mapped to retained evidence in the monthly bundle.</p>
      <p>This mapping supports direct traceability from control objective to concrete artifacts used for review.</p>
    </section>

    <section class="doc-section">
      <h2>3. How Controls Are Enforced</h2>
      <p>PayCal treats enforcement as a system property. Controls are programmatically enforced, not just documented.</p>
      <ul class="doc-fact-list">
        <li><strong>Authentication:</strong> Passkey-capable authentication flow to strengthen phishing resistance.</li>
        <li><strong>Runtime integrity:</strong> Runtime integrity monitoring with operational state handling.</li>
        <li><strong>Output hardening:</strong> Guardian sanitization controls for sensitive DOM/output paths.</li>
        <li><strong>Quality gate:</strong> Automated full-suite PHPUnit gate before bundle evidence is accepted.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>4. Change Management &amp; Testing</h2>
      <p>Change governance is aligned to CC8 with tracked changes, approvals, and test evidence.</p>
      <ul class="doc-fact-list">
        <li><strong>Change records:</strong> 12</li>
        <li><strong>Approval records:</strong> 10</li>
        <li><strong>Test results:</strong> 1528 tests, 8351 assertions (pass)</li>
        <li><strong>Test-control trace:</strong> 5 suites, 5 passed, 8 linked test files</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>5. Audit Trail &amp; Evidence Integrity</h2>
      <p>Administrative and security-relevant runtime events are exported with immutable-ledger validation for integrity checks.</p>
      <p><strong>Current ledger integrity status:</strong> PASS.</p>
    </section>

    <section class="doc-section success">
      <h2>6. Continuous Monitoring &amp; Freshness</h2>
      <p>Evidence exports run continuously and are validated against a deterministic freshness policy.</p>
      <p><strong>Current freshness result:</strong> all mapped artifacts are within the 35-day audit window.</p>
    </section>

    <section class="doc-section">
      <h2>7. Current Status</h2>
      <p><strong>Status:</strong> SOC2 readiness in progress, with continuous control hardening and deterministic evidence updates.</p>
      <p>PayCal does not claim SOC 2 certification or auditor opinion on this page. Formal report access remains NDA-gated.</p>
    </section>

    <section class="doc-section">
      <h2>Reusable Compliance Snippets</h2>
      <p><strong>Footer badge:</strong> SOC2 Aligned • Controls Mapped • Continuous Evidence Monitoring</p>
      <p><strong>Summary block:</strong> CC1-CC9 mapped, 37 artifacts, 26 control links, ledger integrity pass, and automated full-suite test evidence.</p>
    </section>

    <section class="doc-section highlight">
      <h2>References</h2>
      <ul class="doc-fact-list">
        <li>
          <a class="doc-read-more" href="/security/">Security Trust Hub</a>
          <span class="doc-ref-desc">Sanitized public control summary, deterministic narratives, and security contact path.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/">PayCal SOC 2 Summary</a>
          <span class="doc-ref-desc">Status, metrics, and NDA access for this report.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/request/">Request SOC 2 Report (NDA)</a>
          <span class="doc-ref-desc">Gated access for vendor and security due-diligence review.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.aicpa-cima.com/topic/audit-assurance/audit-and-assurance-greater-than-soc-2" target="_blank" rel="noopener noreferrer">AICPA SOC 2 — Official Standard</a>
          <span class="doc-ref-desc">The authoritative framework defining SOC 2 criteria.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://en.wikipedia.org/wiki/System_and_Organization_Controls" target="_blank" rel="noopener noreferrer">SOC 2 — Wikipedia</a>
          <span class="doc-ref-desc">Overview of System and Organization Controls history and scope.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.reddit.com/r/soc2/" target="_blank" rel="noopener noreferrer">r/soc2 — Reddit Community</a>
          <span class="doc-ref-desc">Practitioner discussion on SOC 2 audits and preparation.</span>
        </li>
      </ul>
    </section>
  </section>
</article>
<?php
require_once HTML . '/footer.php';
