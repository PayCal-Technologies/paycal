<?php
/**
 * Public Transparency: How PayCal Keeps Business Connections Accurate and Auditable
 *
 * PURPOSE:
 * Explain the PayCal Business connection-index reconciliation work,
 * including the model problem, observed Redis drift, repair command, exit
 * codes, and operational rationale.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'How PayCal Keeps Business Connections Accurate and Auditable - [PayCal]';
$pageLabel = 'How PayCal Keeps Business Connections Accurate and Auditable';
$pageMetaDescription = 'How PayCal separates business connection lifecycle records from Redis lookup indexes, audits derived-index drift, and keeps active membership, pending access, and ownership authority clean.';
$pageMetaDescriptionLong = 'PayCal\'s June 2026 business connections integrity update separated canonical connection records from derived Redis indexes and verified the final state with zero drift, zero owner violations, and zero unknown drift.';
$pageSocialTitle = 'How PayCal Keeps Business Connections Accurate and Auditable';
$pageOgDescription = 'PayCal\'s June 2026 Redis connections reconciliation separated active membership, pending access, and lifecycle records, then verified the graph with an audit-and-repair command.';
$pageTwitterTitle = 'How PayCal Keeps Business Connections Accurate and Auditable';
$pageTwitterDescription = 'How PayCal audits and repairs Redis connection index drift without mutating canonical business authority records.';
$pageDcTitle = 'How PayCal Keeps Business Connections Accurate and Auditable';
$pageDcDescription = 'PayCal\'s June 2026 business connections integrity update separated canonical connection records from derived Redis indexes and verified the final state with zero drift, zero owner violations, and zero unknown drift.';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Business Connections Integrity</span>
  </nav>

  <header class="doc-article-header">
    <h1>How PayCal Keeps Business Connections Accurate and Auditable</h1>
    <p class="deck">
      Business connections are more than a list of people. In June 2026 we tightened how PayCal
      Business represents connections, membership, access requests, invites, and active access in Redis.
      This article explains the model, the drift we found, the repair command we built, and
      why the change matters for privacy, dashboard correctness, and future auditability.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-06-18">2026-06-18</time> &middot; Last updated: <time datetime="2026-06-19">2026-06-19</time> &middot; <a href="<?php echo transparency_href('/transparency/redis-connections-reconciliation-2026-06-19-pre-connections/'); ?>">Previous version</a></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Executive Summary</h2>
      <table class="doc-table" aria-label="Redis connections reconciliation summary">
        <tbody>
          <tr>
            <td><strong>Area affected</strong></td>
            <td>PayCal Business connection lifecycle records and Redis lookup indexes</td>
          </tr>
          <tr>
            <td><strong>Problem</strong></td>
            <td>Older derived Redis sets could mix active membership, pending workflow state, and stale historical lookup entries</td>
          </tr>
          <tr>
            <td><strong>Core fix</strong></td>
            <td>Connection hashes remain canonical; active members, pending connections, and non-terminal lookup indexes are separated</td>
          </tr>
          <tr>
            <td><strong>Operational tool</strong></td>
            <td><code>scripts/paycal business:connections:audit</code> audits and repairs derived-index drift</td>
          </tr>
          <tr>
            <td><strong>Finding before repair</strong></td>
            <td>18,349 known repairable drift findings, zero owner violations, zero unknown drift</td>
          </tr>
          <tr>
            <td><strong>Final state after repair</strong></td>
            <td>0 drift, 0 owner violations, 0 unknown drift</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>The Problem</h2>
      <p>
        PayCal Business uses Redis for fast connection lookups. The canonical record is a hash
        shaped like <code>business:connection:{businessId}:{userUUID}</code>. That hash stores
        the lifecycle state for one connection: role, status, scopes, timestamps, invitation
        or request metadata, and related audit fields.
      </p>
      <p>
        The problem was not the canonical hash. The problem was semantic drift in derived Redis
        sets used as lookup indexes. Historically, <code>business:members:{businessId}</code> and
        <code>business:user:{userUUID}</code> were sometimes used as broad connection indexes.
        That made it too easy for older code to treat a pending or stale connection as though
        it were active membership.
      </p>
      <p>
        That distinction matters. A pending access request is not active membership. An accepted
        connection is not automatically encrypted data access unless the required consent and
        key-wrap state are valid. A derived Redis set is not authority; it is a disposable index.
      </p>
    </section>

    <section class="doc-section">
      <h2>The Model We Now Enforce</h2>
      <p>The hardened model separates lifecycle state from active access state:</p>
      <div class="doc-code-block" data-label="Redis model">
        <pre><code>Canonical truth:
  business:connection:{businessId}:{userUUID}

Derived active access indexes:
  business:members:{businessId}              active members only
  business:user:{userUUID}                   active businesses only

Derived connection lookup indexes:
  business:connections:{businessId}        active + pending + consented
  business:connections:user:{userUUID}     active + pending + consented

Derived workflow queue:
  business:pending:{businessId}              pending only</code></pre>
      </div>
      <p>
        This gives each Redis set one meaning. Dashboard counts can rely on active-member indexes.
        Pending queues can rely on <code>business:pending:{businessId}</code>. Connection-aware UI can use
        connection lookup indexes without granting active access.
      </p>
    </section>

    <section class="doc-section">
      <h2>Example of Redis Drift</h2>
      <p>A stale index entry looks like this:</p>
      <div class="doc-code-block" data-label="Stale index drift">
        <pre><code># Derived index says the user belongs to the business
SISMEMBER business:user:user-123 ORGabc
=> 1

# But the canonical lifecycle record no longer exists
HGETALL business:connection:ORGabc:user-123
=> empty

# Correct repair
SREM business:user:user-123 ORGabc</code></pre>
      </div>
      <p>
        Another repairable drift example is an active connection missing its active indexes:
      </p>
      <div class="doc-code-block" data-label="Missing active indexes">
        <pre><code>HGETALL business:connection:ORGabc:user-456
=> status=active role=member

SISMEMBER business:members:ORGabc user-456
=> 0

SISMEMBER business:user:user-456 ORGabc
=> 0

# Correct repair
SADD business:members:ORGabc user-456
SADD business:user:user-456 ORGabc</code></pre>
      </div>
      <p>
        In both cases the repair does not invent a connection. It only reconciles derived sets
        against the canonical connection hash.
      </p>
    </section>

    <section class="doc-section">
      <h2>What We Found Before Repair</h2>
      <p>
        The report-only audit found a large amount of derived-index drift, but no authority
        corruption and no terminal-state leakage into live indexes.
      </p>
      <table class="doc-table" aria-label="Pre-repair Redis connection audit buckets">
        <thead>
          <tr>
            <th scope="col">Bucket</th>
            <th scope="col">Count</th>
            <th scope="col">Meaning</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>stale_index_without_connection</code></td>
            <td>14,886</td>
            <td>Old derived indexes pointed to connections that no longer existed</td>
          </tr>
          <tr>
            <td><code>connection_lookup_missing</code></td>
            <td>2,908</td>
            <td>New non-terminal connection lookup indexes needed backfill</td>
          </tr>
          <tr>
            <td><code>active_missing_member_index</code></td>
            <td>46</td>
            <td>Active connections missing the business-side active member index</td>
          </tr>
          <tr>
            <td><code>active_missing_user_index</code></td>
            <td>493</td>
            <td>Active connections missing the user-side active business index</td>
          </tr>
          <tr>
            <td><code>terminal_in_connection_index</code></td>
            <td>0</td>
            <td>No revoked, rejected, expired, or withdrawn connections leaked into connection lookup indexes</td>
          </tr>
          <tr>
            <td><code>terminal_in_member_index</code></td>
            <td>0</td>
            <td>No terminal connections leaked into active member indexes</td>
          </tr>
          <tr>
            <td><code>owner_violation</code></td>
            <td>0</td>
            <td>Every active business still had a sane owner authority model</td>
          </tr>
          <tr>
            <td><code>other</code></td>
            <td>0</td>
            <td>No unknown drift category was found</td>
          </tr>
        </tbody>
      </table>
      <p>
        The conclusion was important: the audit found migration residue in derived indexes,
        with no evidence of terminal-state leakage, owner-role corruption, or unknown drift.
        The canonical lifecycle graph was intact, and owner authority was intact.
      </p>
    </section>

    <section class="doc-section">
      <h2>The Repair Command We Built</h2>
      <p>
        We created <code>scripts/connections-audit.php</code> and exposed it through
        the existing internal command dispatcher:
      </p>
      <div class="doc-code-block" data-label="Internal ops command">
        <pre><code>scripts/paycal business:connections:audit</code></pre>
      </div>
      <p>The command has two modes:</p>
      <ul class="doc-list">
        <li><strong>Report-only:</strong> scans connection hashes and reports drift without mutating Redis.</li>
        <li><strong>Fix:</strong> repairs known derived-index drift by adding or removing Redis set members.</li>
      </ul>
      <p>
        The command never auto-selects owners, never rewrites role authority, and never creates
        canonical connections. It only reconciles disposable indexes against canonical hashes.
      </p>
    </section>

    <section class="doc-section">
      <h2>Examples for Future Operators</h2>
      <div class="operator-command-list">
        <div class="subject-example-cutout operator-command">
          <p>Run a full report-only audit</p>
          <div class="doc-code-block" data-label="Report-only">
            <pre><code>scripts/paycal business:connections:audit --json</code></pre>
          </div>
        </div>
        <div class="subject-example-cutout operator-command">
          <p>Run a report for a single business</p>
          <div class="doc-code-block" data-label="Scoped report">
            <pre><code>scripts/paycal business:connections:audit --business ORGabc123 --json</code></pre>
          </div>
        </div>
        <div class="subject-example-cutout operator-command">
          <p>Save a report artifact before repair</p>
          <div class="doc-code-block" data-label="Before artifact">
            <pre><code>scripts/paycal business:connections:audit --json \
  &gt; tmp/connections-report-before.json</code></pre>
          </div>
        </div>
        <div class="subject-example-cutout operator-command">
          <p>Repair known drift</p>
          <div class="doc-code-block" data-label="Controlled repair">
            <pre><code>scripts/paycal business:connections:audit --fix --json \
  &gt; tmp/connections-fix.json</code></pre>
          </div>
        </div>
        <div class="subject-example-cutout operator-command">
          <p>Re-run the report and expect a clean result</p>
          <div class="doc-code-block" data-label="After artifact">
            <pre><code>scripts/paycal business:connections:audit --json \
  &gt; tmp/connections-report-after.json</code></pre>
          </div>
        </div>
        <div class="subject-example-cutout operator-command">
          <p>The expected healthy post-repair summary</p>
          <div class="doc-code-block" data-label="Clean result">
            <pre><code>drift=0
owner_violations=0
other=0</code></pre>
          </div>
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2>CI/CD and Ops Exit Codes</h2>
      <p>
        We made the command machine-readable so CI and operations jobs can distinguish normal
        repairable drift from unsafe conditions.
      </p>
      <table class="doc-table" aria-label="Connection audit exit codes">
        <thead>
          <tr>
            <th scope="col">Exit code</th>
            <th scope="col">Meaning</th>
            <th scope="col">Operational interpretation</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>0</code></td>
            <td>Clean audit, or <code>--fix</code> repaired known repairable drift</td>
            <td>Safe to proceed</td>
          </tr>
          <tr>
            <td><code>1</code></td>
            <td>Report-only drift found</td>
            <td>Known repairable drift exists; review or run controlled repair</td>
          </tr>
          <tr>
            <td><code>2</code></td>
            <td>Owner invariant violation</td>
            <td>Stop and review manually; never auto-pick an owner</td>
          </tr>
          <tr>
            <td><code>3</code></td>
            <td>Unknown or other drift found</td>
            <td>Stop and classify the new drift type before repairing</td>
          </tr>
          <tr>
            <td><code>4</code></td>
            <td>Script or Redis failure</td>
            <td>Infrastructure failure; rerun only after root cause is clear</td>
          </tr>
        </tbody>
      </table>
      <p>
        This makes the tool suitable for scheduled checks and deployment gates. A report-only
        job can fail with code <code>1</code> when known drift exists, while a repair job can
        return <code>0</code> after successfully repairing known categories.
      </p>
    </section>

    <section class="doc-section">
      <h2>Actions We Took</h2>
      <ol class="doc-list">
        <li>Separated active membership indexes from connection lifecycle indexes.</li>
        <li>Added <code>business:connections:{businessId}</code> and <code>business:connections:user:{userUUID}</code> for non-terminal lookup.</li>
        <li>Added <code>business:pending:{businessId}</code> for pending-only workflow visibility.</li>
        <li>Updated the central connection writer so all derived sets are maintained from one path.</li>
        <li>Hardened terminal transitions so revoked, rejected, expired, and withdrawn connections cannot jump directly to active.</li>
        <li>Added scope preset and policy version fields to connection writes.</li>
        <li>Updated seed and consolidation scripts to populate the new indexes consistently.</li>
        <li>Built the reconciliation command with report-only, fix, JSON, bucket counts, and CI-friendly exit codes.</li>
        <li>Ran report-only, repaired known drift, re-ran the report, and verified the final state was clean.</li>
        <li>Ran the targeted PHPUnit connection/cache/site suite after repair.</li>
      </ol>
    </section>

    <section class="doc-section success">
      <h2>Final Outcome</h2>
      <p>The final repair sequence produced this result:</p>
      <div class="doc-code-block" data-label="Repair sequence">
        <pre><code>Before:
drift=18349 owner=0 other=0

Fix:
fixed=18349 exit=0

After:
drift=0 owner=0 other=0</code></pre>
      </div>
      <p>
        The targeted test suite passed after the repair:
      </p>
      <div class="doc-code-block" data-label="Test result">
        <pre><code>83 tests, 578 assertions
OK</code></pre>
      </div>
      <p>
        After the targeted tests, we ran one additional reconciliation pass to confirm that
        fixture-created indexes were clean. The final audit returned:
      </p>
      <div class="doc-code-block" data-label="Final audit">
        <pre><code>after_exit=0
connections=1488
businesses=1237
drift=0
owner=0
other=0</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Why This Matters</h2>
      <p>
        Payroll collaboration is security-sensitive. Dashboards, invites, access approvals,
        encryption consent, and revocation all depend on clean connection semantics.
      </p>
      <p>
        The new model gives PayCal a stronger operational boundary:
      </p>
      <div class="doc-code-block" data-label="Design principle">
        <pre><code>connection = lifecycle record
membership   = active access state
pending      = workflow state
indexes      = disposable projections</code></pre>
      </div>
      <p>
        That separation keeps dashboard member counts accurate, prevents pending workflow state
        from being treated as active access, keeps terminal states out of live indexes, and gives
        operators a safe way to repair Redis drift without mutating authority records.
      </p>
    </section>

    <section class="doc-section">
      <h2>What This Was Not</h2>
      <ul class="doc-list">
        <li>It was not evidence of owner-role corruption. The audit found zero owner violations.</li>
        <li>It was not evidence of revoked or rejected users leaking into live member indexes. Terminal leakage buckets were zero.</li>
        <li>It was not a rewrite of canonical connection records. The repair only reconciled derived Redis sets.</li>
        <li>It was not a change to public business search policy. Public search remains governed by visibility policy, subscription state, approval state, and listing state.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Future Operating Procedure</h2>
      <ol class="doc-list">
        <li>Take a Redis snapshot or backup before large repairs.</li>
        <li>Run report-only and save the JSON artifact.</li>
        <li>Run <code>--fix</code> first in a non-production environment.</li>
        <li>Re-run report-only and confirm <code>drift=0</code>, <code>owner_violations=0</code>, and <code>other=0</code>.</li>
        <li>Run targeted connection and business workflow tests.</li>
        <li>Smoke-test dashboard counts, pending requests, invite acceptance, access approval, revocation, and public search.</li>
        <li>Run production repair only during a quiet window after the non-production run is boring.</li>
      </ol>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
