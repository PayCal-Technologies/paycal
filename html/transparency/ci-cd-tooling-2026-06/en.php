<?php
/**
 * Public Transparency: CI/CD Tooling and Release Governance
 *
 * PURPOSE: Publish PayCal's local-authoritative CI/CD model, validation gates,
 *          remote verification posture, and portability commitments.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'CI/CD Tooling and Release Governance - [PayCal]';
$pageLabel = 'CI/CD Tooling and Release Governance';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="Breadcrumb">
    <a href="<?php echo transparency_href('/transparency/'); ?>">Transparency Hub</a>
    <span class="separator">/</span>
    <span class="current">CI/CD Tooling and Release Governance</span>
  </nav>

  <header class="doc-article-header">
    <h1>CI/CD Tooling and Release Governance</h1>
    <p class="deck">
      PayCal uses a local-authoritative CI/CD model: versioned scripts, local hooks,
      static analysis, tests, and promotion checks decide whether work may proceed.
      Hosted workflows provide independent verification and evidence, but they are
      not the only place where safety lives.
    </p>
    <p class="doc-article-meta">
      Published: <time datetime="2026-06-22">2026-06-22</time>
      &middot; Last updated: <time datetime="2026-06-22">2026-06-22</time>
    </p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Verification Metadata</h2>
      <ul class="doc-fact-list">
        <li><strong>Route:</strong> <code>/transparency/ci-cd-tooling-2026-06/</code></li>
        <li><strong>Last verified:</strong> <time datetime="2026-06-22">2026-06-22</time></li>
        <li><strong>Current release version at publication:</strong> <code>1.059.009</code></li>
        <li><strong>Primary local command surface:</strong> <code>scripts/paycal</code></li>
        <li><strong>Primary remote workflow folder:</strong> <code>.github/workflows/</code></li>
        <li><strong>Review cadence:</strong> monthly, and whenever CI/CD scripts or workflows change materially</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Operating Model</h2>
      <p>
        PayCal's CI/CD process is designed so the project can validate, release,
        and recover without depending on one specific workstation, hosted dashboard,
        or paid platform feature. The repository carries the rules: hooks, scripts,
        workflow definitions, test configuration, dependency locks, policy checks,
        and release documentation are versioned beside the application.
      </p>
      <p>
        The model is local-authoritative. Local gates decide whether work is ready
        to become history or leave the workstation. GitHub Actions are still useful,
        but their role is verification and evidence, not sole authority. That keeps
        the process inspectable by a human, reproducible by another machine, and
        legible to AI tools working inside the repository.
      </p>
      <table class="doc-table" aria-label="CI/CD authority layers">
        <thead>
          <tr>
            <th scope="col">Layer</th>
            <th scope="col">Primary role</th>
            <th scope="col">Authority</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Local hooks and scripts</td>
            <td>Commit, push, policy, static analysis, test, and promotion validation</td>
            <td>Authoritative</td>
          </tr>
          <tr>
            <td>GitHub repositories</td>
            <td>Remote storage, collaboration, private/public distribution, signed history</td>
            <td>Storage and publication boundary</td>
          </tr>
          <tr>
            <td>GitHub Actions</td>
            <td>Independent hosted verification, scheduled checks, and audit evidence</td>
            <td>Advisory evidence unless explicitly promoted by governance decision</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section success">
      <h2>Mandatory Local Gates</h2>
      <p>
        The normal developer path uses Git hooks installed from <code>githooks/</code>
        and executed through <code>scripts/paycal</code>. These hooks are deliberately
        close to the work: they block bad staged content before commit and block
        remote publication before push.
      </p>
      <ul class="doc-fact-list">
        <li><strong>Pre-commit:</strong> removes <code>.DS_Store</code>, scans staged changes for secrets, validates Composer state, checks README/VERSION inventory, blocks PHPStan baselines, captures AST metrics, and runs the quick PHPUnit suite.</li>
        <li><strong>Post-commit:</strong> records verified HEAD metadata so pre-push can skip rerunning quick tests when the exact commit has already passed.</li>
        <li><strong>Pre-push:</strong> rechecks README policy, runs policy-meta, enforces no PHPStan baselines, runs PHPStan Level 9 with a <code>1G</code> memory limit, and runs quick tests unless the commit is already verified.</li>
      </ul>
      <pre class="doc-code">scripts/paycal hooks:install
scripts/paycal checks:policy-meta
scripts/paycal checks:test-boundaries
composer run test:quick
composer run phpstan:strict</pre>
    </section>

    <section class="doc-section">
      <h2>Policy Meta-Checks</h2>
      <p>
        <code>scripts/paycal checks:policy-meta</code> checks the validation machinery
        itself. It is intentionally boring: it confirms required hooks exist, shell
        scripts parse, workflow references remain pinned, PHPStan baselines are not
        introduced, and private/public test boundaries remain valid.
      </p>
      <p>
        This matters because CI/CD security is not only application behavior. A test
        suite can be strong while the release machinery quietly weakens. Policy-meta
        gives the machinery its own regression gate.
      </p>
    </section>

    <section class="doc-section">
      <h2>PHP, JavaScript, and Mutation Gates</h2>
      <table class="doc-table" aria-label="Language and quality gates">
        <thead>
          <tr>
            <th scope="col">Gate</th>
            <th scope="col">Command or workflow</th>
            <th scope="col">What it protects</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>PHP static analysis</td>
            <td><code>composer run phpstan:strict</code></td>
            <td>Level 9 PHPStan analysis with no baseline escape hatch</td>
          </tr>
          <tr>
            <td>Quick tests</td>
            <td><code>composer run test:quick</code></td>
            <td>Fast unit-focused regression coverage before commit and push</td>
          </tr>
          <tr>
            <td>Affected tests</td>
            <td><code>composer run test:affected</code></td>
            <td>Portable affected-test runner with quick-suite fallback</td>
          </tr>
          <tr>
            <td>Mutation testing</td>
            <td><code>composer run test:mutation</code></td>
            <td>Deep verification using Infection when intentionally invoked</td>
          </tr>
          <tr>
            <td>JavaScript security</td>
            <td><code>npm run test:js</code></td>
            <td>ESLint plus the custom HTML sink scanner for unsafe DOM writes</td>
          </tr>
          <tr>
            <td>Accessibility and UI smoke</td>
            <td><code>npm run test:a11y:all</code>, <code>npm run test:smoke:ui</code></td>
            <td>Browser-facing regressions, contrast, and navigation behavior</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Remote Workflows</h2>
      <p>
        GitHub Actions mirror important checks on a hosted runner. They are hardened
        because optional evidence still needs integrity. Current workflow patterns
        include empty or minimal permissions, full SHA pins for third-party actions,
        pinned tool versions, job timeouts, concurrency groups, and explicit PHP and
        Node versions.
      </p>
      <ul class="doc-fact-list">
        <li><code>phpunit.yml</code> runs fast, full, and deep PHP validation stages.</li>
        <li><code>phpstan.yml</code> runs strict PHPStan on PHP 8.4 and 8.5.</li>
        <li><code>javascript.yml</code> runs Node <code>20.19.0</code> with lockfile-based <code>npm ci</code>.</li>
        <li><code>security-gates.yml</code> runs dependency review, Composer audit, npm audit, PHPStan, and Gitleaks.</li>
        <li><code>public-repo-health.yml</code> verifies the curated public repository after promotion.</li>
        <li><code>soc2-compliance.yml</code> generates compliance-oriented evidence checks.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Dependency and Version Controls</h2>
      <p>
        PayCal treats language dependencies and CI runtime versions as part of the
        release surface. Composer uses a platform pin for PHP 8.4 compatibility while
        CI also validates PHP 8.5. JavaScript workflows use Node <code>20.19.0</code>
        because the current ESLint toolchain requires modern Node 20 behavior.
      </p>
      <ul class="doc-fact-list">
        <li><code>composer.lock</code> and <code>package-lock.json</code> are deterministic install sources.</li>
        <li><code>composer validate --no-check-publish</code> runs before commit.</li>
        <li><code>composer outdated --direct --strict</code> checks direct package freshness, with documented conflict exceptions.</li>
        <li><code>npm ci</code> is the automation install mode; lockfile drift is treated as a maintenance change.</li>
        <li>Infection is installed explicitly so deep verification does not depend on a missing local binary.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Portability Commitments</h2>
      <p>
        The CI/CD tooling is being moved away from assumptions that only hold on the
        current Mac mini. Shell scripts prefer <code>/usr/bin/env bash</code>, repo-root
        discovery through Git, quoted path arrays instead of word-split file lists,
        portable temp-file rewrites instead of platform-specific <code>sed -i</code>,
        and explicit environment variables for local paths.
      </p>
      <ul class="doc-fact-list">
        <li>Git hooks call versioned scripts rather than embedding local machine paths.</li>
        <li>Workflow file loops use null-delimited <code>find -print0</code> where filenames could otherwise break shell parsing.</li>
        <li>PHPUnit inventory is kept deterministic by avoiding random data-provider keys.</li>
        <li>Public repository health uses the same PHPStan <code>1G</code> memory setting as local hooks.</li>
        <li>Mac-only operational files remain explicit operational helpers, not general development prerequisites.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Public Promotion and Repository Sync</h2>
      <p>
        The public repository is not a blind mirror of the private repository. Promotion
        uses allowlists, private moat exclusions, public-health checks, and signed public
        commits. Private-only materials such as SOC 2 evidence trees, operational notes,
        local logs, keys, and internal AI notes are excluded by policy.
      </p>
      <pre class="doc-code">scripts/paycal checks:public-promotion-scope main...HEAD
scripts/paycal checks:public-health /path/to/public/repo
bash scripts/push-and-sync.sh</pre>
      <p>
        The intent is narrow publication: public users should receive the product,
        public documentation, and transparency material without receiving private
        operational surfaces.
      </p>
    </section>

    <section class="doc-section highlight">
      <h2>Known Tradeoffs</h2>
      <ul class="doc-fact-list">
        <li>Local-authoritative CI requires local dependencies to stay installable outside the Mac mini.</li>
        <li>Hooks must remain installed and current in every active workstation clone.</li>
        <li>Hosted dashboards may not show every local gate as a required remote check.</li>
        <li>Some operational scripts are still Mac-oriented and should remain clearly labeled or be given Linux equivalents over time.</li>
        <li>Public promotion remains intentionally narrower than a simple repository mirror.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>How to Reproduce the Core Gate</h2>
      <pre class="doc-code">git status --short --branch
composer validate --no-check-publish
scripts/paycal checks:readme-version
scripts/paycal checks:policy-meta
composer run phpstan:strict
composer run test:quick
composer run test:affected
git diff --check</pre>
      <p>
        Related articles: <a href="<?php echo transparency_href('/transparency/dependency-ci/'); ?>">Dependency and CI/CD Governance</a>,
        <a href="<?php echo transparency_href('/transparency/testing/'); ?>">Testing and Validation Governance</a>,
        and <a href="<?php echo transparency_href('/transparency/verification-governance/'); ?>">Verification and Governance</a>.
      </p>
    </section>
  </div>
</article>
<?php
require_once HTML.'/footer.php';
