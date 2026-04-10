<?php
/**
 * Public Transparency: Verification and Governance
 *
 * PURPOSE: Show how PayCal enforces quality, security, and policy guardrails in code.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_PAGE_TITLE',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_DECK',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_MEANING_TITLE',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_MEANING_TEXT_1',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_METADATA_TITLE',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_SECURITY_STATUS_TITLE',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_LOCAL_GATES_TITLE',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_CI_PIPELINE_TITLE',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_RUNTIME_LIMITS_TITLE',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_TTL_LIMITS_TITLE',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_CSP_ASSETS_TITLE',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_VERIFY_CLAIMS_TITLE',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_ROUTE_METADATA_TITLE',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_KNOWN_LIMITATIONS_TITLE',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_LAST_UPDATED_LABEL',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_ROUTE_LABEL',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_LAST_VERIFIED_LABEL',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_MEANING_TEXT_2',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_NEXT_REVIEW_LABEL',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_VERIFICATION_SCOPE_LABEL',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_VERIFICATION_SCOPE_VALUE',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_KNOWN_LIMITATIONS_FACT_1',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_KNOWN_LIMITATIONS_FACT_2',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_METADATA_TEXT_1',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_SECURITY_STATUS_TEXT_1',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_SECURITY_STATUS_TEXT_2',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_LOCAL_GATES_TEXT_1',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_LOCAL_GATES_TEXT_2',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_CI_PIPELINE_TEXT_1',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_RUNTIME_LIMITS_TEXT_1',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_RUNTIME_LIMITS_TEXT_2',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_TTL_LIMITS_TEXT_1',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_TTL_LIMITS_ACCOUNT_RECOVERY_ITEM',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_TTL_LIMITS_TEXT_2',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_CSP_ASSETS_TEXT_1',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_CSP_ASSETS_TEXT_2',
  'TRANSPARENCY_VERIFICATION_GOVERNANCE_VERIFY_CLAIMS_TEXT_1',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_PAGE_TITLE']; ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_PAGE_TITLE']; ?></h1>
    <p class="deck"><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_DECK']; ?></p>
    <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_MEANING_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_MEANING_TEXT_1']; ?></p>
      <p><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_MEANING_TEXT_2']; ?></p>
    </section>

    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_METADATA_TITLE']; ?></h2>
      <div class="doc-two-column">
        <div>
          <h3><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_ROUTE_METADATA_TITLE']; ?></h3>
          <ul class="doc-fact-list">
            <li><strong><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_ROUTE_LABEL']; ?></strong> <code>/transparency/verification-governance/</code></li>
            <li><strong><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_LAST_VERIFIED_LABEL']; ?></strong> <time datetime="2026-03-24">2026-03-24</time></li>
            <li><strong><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_NEXT_REVIEW_LABEL']; ?></strong> <time datetime="2026-06-24">2026-06-24</time></li>
            <li><strong><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_VERIFICATION_SCOPE_LABEL']; ?></strong> <?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_VERIFICATION_SCOPE_VALUE']; ?></li>
          </ul>
        </div>
        <div>
          <h3><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_KNOWN_LIMITATIONS_TITLE']; ?></h3>
          <ul class="doc-fact-list">
            <li><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_KNOWN_LIMITATIONS_FACT_1']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_KNOWN_LIMITATIONS_FACT_2']; ?></li>
          </ul>
        </div>
      </div>
      <p><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_METADATA_TEXT_1']; ?></p>
    </section>

    <section class="doc-section success">
      <h2><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_SECURITY_STATUS_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_SECURITY_STATUS_TEXT_1']; ?></p>
      <p><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_SECURITY_STATUS_TEXT_2']; ?> <a href="<?php echo transparency_href('/transparency/security-audit/'); ?>"><code>/transparency/security-audit/</code></a>.</p>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_LOCAL_GATES_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_LOCAL_GATES_TEXT_1']; ?></p>
      <ul class="doc-fact-list">
        <li><code>githooks/pre-commit</code> runs PHPStan Level 9 on staged PHP files under <code>html/</code>.</li>
        <li><code>githooks/pre-commit</code> blocks baseline usage in <code>phpstan.neon</code> and blocks <code>phpstan-baseline.neon</code>.</li>
        <li><code>githooks/pre-push</code> runs full-repo PHPStan Level 9 and applies the same baseline-blocking policy.</li>
      </ul>
      <p><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_LOCAL_GATES_TEXT_2']; ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_CI_PIPELINE_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_CI_PIPELINE_TEXT_1']; ?></p>
      <ul class="doc-fact-list">
        <li><code>.github/workflows/phpunit.yml</code> Stage 2: <code>composer run test:all</code> (unit + integration + contract).</li>
        <li><code>.github/workflows/phpunit.yml</code> Stage 3: <code>composer run test:random</code> (order-randomized).</li>
        <li><code>.github/workflows/phpunit.yml</code> Stage 3: <code>composer run test:coverage</code>.</li>
        <li><code>.github/workflows/phpunit.yml</code> Stage 4: mutation test job.</li>
      </ul>
      <p>Representative contract/integration suites include <code>html/tests/Integration/KekContractTest.php</code>, <code>html/tests/Integration/RedisContractTest.php</code>, and contract tests under <code>html/tests/Contract/</code>.</p>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_RUNTIME_LIMITS_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_RUNTIME_LIMITS_TEXT_1']; ?></p>
      <ul class="doc-fact-list">
        <li><code>html/src/Domain/RateLimiter.php</code> defines per-minute endpoint and IP limits, including telemetry (90/minute).</li>
        <li><code>html/src/Controllers/TelemetryController.php</code> enforces authentication and telemetry rate limits before accepting events.</li>
        <li><code>html/src/Controllers/TelemetryController.php</code> bounds event type format with a strict regex to control key cardinality.</li>
        <li><code>html/src/Controllers/EmailVerificationController.php</code> applies retry windows with TTL-backed rate-limit keys.</li>
      </ul>
      <p><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_RUNTIME_LIMITS_TEXT_2']; ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_TTL_LIMITS_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_TTL_LIMITS_TEXT_1']; ?></p>
      <ul class="doc-fact-list">
        <li><code>html/src/system-limits-master.php</code> includes <code>enable_rate_limiting</code> and account-recovery controls.</li>
        <li><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_TTL_LIMITS_ACCOUNT_RECOVERY_ITEM']; ?></li>
        <li><code>html/src/Domain/AccountRecoveryAbuseGuard.php</code> records replay telemetry and applies automatic hashed-IP blocking when thresholds are exceeded.</li>
        <li><code>html/src/Domain/AccountRecoveryTransaction.php</code> enforces transaction/proof/bootstrap expiries from system limits.</li>
      </ul>
      <p><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_TTL_LIMITS_TEXT_2']; ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_CSP_ASSETS_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_CSP_ASSETS_TEXT_1']; ?></p>
      <ul class="doc-fact-list">
        <li><code>html/header.php</code> builds and emits CSP directives including <code>default-src 'none'</code> and strict <code>script-src</code>/<code>style-src</code> policies.</li>
        <li><code>html/header.php</code> includes Trusted Types policy directives.</li>
        <li><code>html/src/Domain/Render.php</code> supports nonce-based module script rendering.</li>
      </ul>
      <p><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_CSP_ASSETS_TEXT_2']; ?></p>
    </section>

    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_VERIFY_CLAIMS_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_VERIFY_CLAIMS_TEXT_1']; ?></p>
      <pre class="doc-code"># Static analysis gates
bash githooks/pre-commit
bash githooks/pre-push

# Test pipeline equivalents
cd html
composer run test:all
composer run test:random
composer run test:coverage</pre>
      <p><strong><?php echo $i18n['TRANSPARENCY_VERIFICATION_GOVERNANCE_LAST_UPDATED_LABEL']; ?></strong> March 24, 2026.</p>
    </section>
  </div>
</article>
<?php
require_once HTML.'/footer.php';
