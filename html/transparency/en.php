<?php
/**
 * Public Transparency Hub
 *
 * PURPOSE: High-level philosophy and links to detailed transparency pages.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/_link.php';

if (!function_exists('transparency_href')) {
  function transparency_href(string $path): string
  {
    return $path;
  }
}

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_HOME',
  'TRANSPARENCY_HUB_PAGE_TITLE',
  'TRANSPARENCY_HUB_DECK',
  'TRANSPARENCY_HUB_PLATFORM_OVERVIEW_TITLE',
  'TRANSPARENCY_HUB_PLATFORM_OVERVIEW_TEXT_1',
  'TRANSPARENCY_HUB_PLATFORM_OVERVIEW_TEXT_2',
  'TRANSPARENCY_HUB_PLATFORM_OVERVIEW_TEXT_3',
  'TRANSPARENCY_HUB_PLATFORM_OVERVIEW_STRIPE_SUPPORT_TEXT',
  'TRANSPARENCY_HUB_PANELS_ARIA',
  'TRANSPARENCY_HUB_SECURITY_AUDIT_TITLE',
  'TRANSPARENCY_HUB_SECURITY_AUDIT_TEXT_1',
  'TRANSPARENCY_HUB_SECURITY_AUDIT_FACT_STATUS',
  'TRANSPARENCY_HUB_SECURITY_AUDIT_FACT_COVERAGE',
  'TRANSPARENCY_HUB_SECURITY_AUDIT_VALIDATION_SNAPSHOT_FACT',
  'TRANSPARENCY_HUB_SECURITY_AUDIT_READ_MORE',
  'TRANSPARENCY_HUB_METRICS_PRIVACY_TITLE',
  'TRANSPARENCY_HUB_METRICS_TEXT_1',
  'TRANSPARENCY_HUB_METRICS_FACT_TELEMETRY_KEYS',
  'TRANSPARENCY_HUB_METRICS_FACT_COLLECTION_SCOPE',
  'TRANSPARENCY_HUB_METRICS_FACT_RETENTION',
  'TRANSPARENCY_HUB_METRICS_READ_MORE',
  'TRANSPARENCY_HUB_ACCESSIBILITY_WCAG_TITLE',
  'TRANSPARENCY_HUB_ACCESSIBILITY_TEXT_1',
  'TRANSPARENCY_HUB_ACCESSIBILITY_FACT_1',
  'TRANSPARENCY_HUB_ACCESSIBILITY_FACT_2',
  'TRANSPARENCY_HUB_ACCESSIBILITY_FACT_3',
  'TRANSPARENCY_HUB_ACCESSIBILITY_FACT_4',
  'TRANSPARENCY_HUB_ACCESSIBILITY_FACT_5',
  'TRANSPARENCY_HUB_ACCESSIBILITY_FACT_6',
  'TRANSPARENCY_HUB_ACCESSIBILITY_READ_MORE',
  'TRANSPARENCY_HUB_VERIFICATION_GOVERNANCE_TITLE',
  'TRANSPARENCY_HUB_VERIFICATION_GOVERNANCE_TEXT_1',
  'TRANSPARENCY_HUB_VERIFICATION_GOVERNANCE_FACT_1',
  'TRANSPARENCY_HUB_VERIFICATION_GOVERNANCE_FACT_2',
  'TRANSPARENCY_HUB_VERIFICATION_GOVERNANCE_FACT_3',
  'TRANSPARENCY_HUB_VERIFICATION_GOVERNANCE_READ_MORE',
  'TRANSPARENCY_HUB_NETWORK_CAPABILITIES_TITLE',
  'TRANSPARENCY_HUB_NETWORK_CAPABILITIES_TEXT_1',
  'TRANSPARENCY_HUB_NETWORK_CAPABILITIES_FACT_1',
  'TRANSPARENCY_HUB_NETWORK_CAPABILITIES_FACT_2',
  'TRANSPARENCY_HUB_NETWORK_CAPABILITIES_FACT_3',
  'TRANSPARENCY_HUB_NETWORK_CAPABILITIES_READ_MORE',
  'TRANSPARENCY_HUB_TESTING_GOVERNANCE_TITLE',
  'TRANSPARENCY_HUB_TESTING_GOVERNANCE_TEXT_1',
  'TRANSPARENCY_HUB_TESTING_GOVERNANCE_FACT_1',
  'TRANSPARENCY_HUB_TESTING_GOVERNANCE_FACT_2',
  'TRANSPARENCY_HUB_TESTING_GOVERNANCE_FACT_3',
  'TRANSPARENCY_HUB_TESTING_GOVERNANCE_READ_MORE',
  'TRANSPARENCY_HUB_DEPENDENCY_CI_TITLE',
  'TRANSPARENCY_HUB_DEPENDENCY_CI_TEXT_1',
  'TRANSPARENCY_HUB_DEPENDENCY_CI_FACT_1',
  'TRANSPARENCY_HUB_DEPENDENCY_CI_FACT_2',
  'TRANSPARENCY_HUB_DEPENDENCY_CI_FACT_3',
  'TRANSPARENCY_HUB_DEPENDENCY_CI_READ_MORE',
  'TRANSPARENCY_HUB_PHP_PACKAGES_TITLE',
  'TRANSPARENCY_HUB_PHP_PACKAGES_TEXT_1',
  'TRANSPARENCY_HUB_PHP_PACKAGES_FACT_1',
  'TRANSPARENCY_HUB_PHP_PACKAGES_FACT_2',
  'TRANSPARENCY_HUB_PHP_PACKAGES_FACT_3',
  'TRANSPARENCY_HUB_PHP_PACKAGES_READ_MORE',
  'TRANSPARENCY_HUB_FRAMEWORK_BACKEND_TITLE',
  'TRANSPARENCY_HUB_FRAMEWORK_BACKEND_TEXT_1',
  'TRANSPARENCY_HUB_FRAMEWORK_BACKEND_FACT_1',
  'TRANSPARENCY_HUB_FRAMEWORK_BACKEND_FACT_2',
  'TRANSPARENCY_HUB_FRAMEWORK_BACKEND_FACT_3',
  'TRANSPARENCY_HUB_FRAMEWORK_BACKEND_READ_MORE',
  'TRANSPARENCY_HUB_PRODUCT_BILLING_TITLE',
  'TRANSPARENCY_HUB_PRODUCT_BILLING_TEXT_1',
  'TRANSPARENCY_HUB_PRODUCT_BILLING_FACT_1',
  'TRANSPARENCY_HUB_PRODUCT_BILLING_FACT_2',
  'TRANSPARENCY_HUB_PRODUCT_BILLING_FACT_3',
  'TRANSPARENCY_HUB_PRODUCT_BILLING_READ_MORE',
  'TRANSPARENCY_HUB_TAX_METHODOLOGY_TITLE',
  'TRANSPARENCY_HUB_TAX_METHODOLOGY_TEXT_1',
  'TRANSPARENCY_HUB_TAX_METHODOLOGY_FACT_1',
  'TRANSPARENCY_HUB_TAX_METHODOLOGY_FACT_2',
  'TRANSPARENCY_HUB_TAX_METHODOLOGY_FACT_3',
  'TRANSPARENCY_HUB_TAX_METHODOLOGY_READ_MORE',
  'TRANSPARENCY_HUB_EMAIL_ARCHITECTURE_TITLE',
  'TRANSPARENCY_HUB_EMAIL_ARCHITECTURE_TEXT_1',
  'TRANSPARENCY_HUB_EMAIL_ARCHITECTURE_FACT_1',
  'TRANSPARENCY_HUB_EMAIL_ARCHITECTURE_FACT_2',
  'TRANSPARENCY_HUB_EMAIL_ARCHITECTURE_FACT_3',
  'TRANSPARENCY_HUB_EMAIL_ARCHITECTURE_READ_MORE',
  'TRANSPARENCY_HUB_EARNINGS_LOAD_TESTING_TITLE',
  'TRANSPARENCY_HUB_EARNINGS_LOAD_TESTING_TEXT_1',
  'TRANSPARENCY_HUB_EARNINGS_LOAD_TESTING_FACT_1',
  'TRANSPARENCY_HUB_EARNINGS_LOAD_TESTING_FACT_2',
  'TRANSPARENCY_HUB_EARNINGS_LOAD_TESTING_FACT_3',
  'TRANSPARENCY_HUB_EARNINGS_LOAD_TESTING_READ_MORE',
  'TRANSPARENCY_HUB_SUPERHEROES_MAP_TITLE',
  'TRANSPARENCY_HUB_SUPERHEROES_MAP_TEXT_1',
  'TRANSPARENCY_HUB_SUPERHEROES_MAP_FACT_1',
  'TRANSPARENCY_HUB_SUPERHEROES_MAP_FACT_2',
  'TRANSPARENCY_HUB_SUPERHEROES_MAP_FACT_3',
  'TRANSPARENCY_HUB_SUPERHEROES_MAP_READ_MORE',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$readMoreLabel = 'Read more';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_HUB_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_HUB_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
    <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
      <a href="/"><?php echo $i18n['HELP_TOC_HOME']; ?></a>
      <span class="separator">/</span>
      <span class="current"><?php echo $i18n['TRANSPARENCY_HUB_PAGE_TITLE']; ?></span>
    </nav>

    <header class="doc-article-header">
      <h1><?php echo $i18n['TRANSPARENCY_HUB_PAGE_TITLE']; ?></h1>
      <p class="deck"><?php echo $i18n['TRANSPARENCY_HUB_DECK']; ?></p>
    </header>

    <section class="doc-article-body">
      <section class="doc-section highlight">
        <h2><?php echo $i18n['TRANSPARENCY_HUB_PLATFORM_OVERVIEW_TITLE']; ?></h2>
        <p><?php echo $i18n['TRANSPARENCY_HUB_PLATFORM_OVERVIEW_TEXT_1']; ?></p>
        <p><?php echo $i18n['TRANSPARENCY_HUB_PLATFORM_OVERVIEW_TEXT_2']; ?></p>
        <p><?php echo $i18n['TRANSPARENCY_HUB_PLATFORM_OVERVIEW_STRIPE_SUPPORT_TEXT']; ?></p>
        <p><?php echo $i18n['TRANSPARENCY_HUB_PLATFORM_OVERVIEW_TEXT_3']; ?></p>
      </section>

      <div class="doc-panel-grid doc-panel-grid--responsive-3" aria-label="<?php echo $i18n['TRANSPARENCY_HUB_PANELS_ARIA']; ?>">
        <section class="doc-section">
          <h2><?php echo $i18n['TRANSPARENCY_HUB_SECURITY_AUDIT_TITLE']; ?></h2>
          <p class="doc-article-meta">Published: <time datetime="2026-03-23">2026-03-23</time></p>
          <p><?php echo $i18n['TRANSPARENCY_HUB_SECURITY_AUDIT_TEXT_1']; ?></p>
          <ul class="doc-fact-list">
            <li><?php echo $i18n['TRANSPARENCY_HUB_SECURITY_AUDIT_FACT_STATUS']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_SECURITY_AUDIT_FACT_COVERAGE']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_SECURITY_AUDIT_VALIDATION_SNAPSHOT_FACT']; ?></li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/security-audit/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2><?php echo $i18n['TRANSPARENCY_HUB_METRICS_PRIVACY_TITLE']; ?></h2>

            <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p><?php echo $i18n['TRANSPARENCY_HUB_METRICS_TEXT_1']; ?></p>
          <ul class="doc-fact-list">
            <li><?php echo $i18n['TRANSPARENCY_HUB_METRICS_FACT_TELEMETRY_KEYS']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_METRICS_FACT_COLLECTION_SCOPE']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_METRICS_FACT_RETENTION']; ?></li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/metrics/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2><?php echo $i18n['TRANSPARENCY_HUB_ACCESSIBILITY_WCAG_TITLE']; ?></h2>

            <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p><?php echo $i18n['TRANSPARENCY_HUB_ACCESSIBILITY_TEXT_1']; ?></p>
          <ul class="doc-fact-list">
            <li><?php echo $i18n['TRANSPARENCY_HUB_ACCESSIBILITY_FACT_1']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_ACCESSIBILITY_FACT_2']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_ACCESSIBILITY_FACT_3']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_ACCESSIBILITY_FACT_4']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_ACCESSIBILITY_FACT_5']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_ACCESSIBILITY_FACT_6']; ?></li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/accessibility/'); ?>"><?php echo $i18n['TRANSPARENCY_HUB_ACCESSIBILITY_READ_MORE']; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Error Handling &amp; Message Normalization</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
          <p>
            PayCal standardizes error messaging across all frontend modules to ensure users receive
            clear, meaningful feedback while protecting system security and preventing sensitive information leakage.
          </p>
          <ul class="doc-fact-list">
            <li>Normalized error resolution across 11 frontend modules (~40+ catch handlers)</li>
            <li>Consistent message extraction, cleaning, and safe fallback patterns</li>
            <li>Security-first design prevents database details, file paths, and auth info exposure</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/error-handling/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Opt-in Diagnostics &amp; Phantom Wing</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-04-05">2026-04-05</time></p>
          <p>
            PayCal ships a built-in diagnostics layer called Phantom Wing. All three debug
            controls default to Off and are entirely user-controlled from the Settings page.
          </p>
          <ul class="doc-fact-list">
            <li>Console Messages, Detailed Diagnostics, and Network Insights — all off by default</li>
            <li>Telemetry sends only anonymized hourly event counts — zero personal data</li>
            <li>All values are redacted before storage or transmission regardless of settings</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/diagnostics/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Organization Membership and Role Philosophy</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time></p>
          <p>
            This article explains the Organization &lt;-&gt; Member model, role policy changes,
            and the capability/scope philosophy used to keep collaboration permissions auditable.
          </p>
          <ul class="doc-fact-list">
            <li>Documents relationship lifecycle semantics for invites, access requests, approval, and revocation</li>
            <li>Publishes current role posture (owner, manager, contributor, member, viewer)</li>
            <li>Clarifies the principle of backend policy as source of truth with UI as projection only</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/organization-membership/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>SOC 2 Compliance at PayCal</h2>
          <p class="doc-article-meta">Published: <time datetime="2026-04-13">2026-04-13</time></p>
          <p>
            PayCal maintains a SOC 2 aligned control environment using deterministic evidence
            generation, approval-linked change governance, runtime verification, and reproducible audit artifacts.
          </p>
          <ul class="doc-fact-list">
            <li>Control coverage focused on Security (CC1-CC9)</li>
            <li>Automated evidence pipeline with runtime traces, approval artifacts, and hash validation</li>
            <li>Report access handled through NDA request workflow</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/soc2/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2><?php echo $i18n['TRANSPARENCY_HUB_VERIFICATION_GOVERNANCE_TITLE']; ?></h2>

            <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p><?php echo $i18n['TRANSPARENCY_HUB_VERIFICATION_GOVERNANCE_TEXT_1']; ?></p>
          <ul class="doc-fact-list">
            <li><?php echo $i18n['TRANSPARENCY_HUB_VERIFICATION_GOVERNANCE_FACT_1']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_VERIFICATION_GOVERNANCE_FACT_2']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_VERIFICATION_GOVERNANCE_FACT_3']; ?></li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/verification-governance/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2><?php echo $i18n['TRANSPARENCY_HUB_NETWORK_CAPABILITIES_TITLE']; ?></h2>

            <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p><?php echo $i18n['TRANSPARENCY_HUB_NETWORK_CAPABILITIES_TEXT_1']; ?></p>
          <ul class="doc-fact-list">
            <li><?php echo $i18n['TRANSPARENCY_HUB_NETWORK_CAPABILITIES_FACT_1']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_NETWORK_CAPABILITIES_FACT_2']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_NETWORK_CAPABILITIES_FACT_3']; ?></li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/network-capabilities/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2><?php echo $i18n['TRANSPARENCY_HUB_TESTING_GOVERNANCE_TITLE']; ?></h2>

            <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p><?php echo $i18n['TRANSPARENCY_HUB_TESTING_GOVERNANCE_TEXT_1']; ?></p>
          <ul class="doc-fact-list">
            <li><?php echo $i18n['TRANSPARENCY_HUB_TESTING_GOVERNANCE_FACT_1']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_TESTING_GOVERNANCE_FACT_2']; ?> <code>/mis</code> sweeps.</li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_TESTING_GOVERNANCE_FACT_3']; ?></li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2><?php echo $i18n['TRANSPARENCY_HUB_DEPENDENCY_CI_TITLE']; ?></h2>

            <p class="doc-article-meta">Published: <time datetime="2026-03-31">2026-03-31</time></p>
          <p><?php echo $i18n['TRANSPARENCY_HUB_DEPENDENCY_CI_TEXT_1']; ?></p>
          <ul class="doc-fact-list">
            <li><?php echo $i18n['TRANSPARENCY_HUB_DEPENDENCY_CI_FACT_1']; ?> <code>npm ci</code> automation requirements.</li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_DEPENDENCY_CI_FACT_2']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_DEPENDENCY_CI_FACT_3']; ?></li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/dependency-ci/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2><?php echo $i18n['TRANSPARENCY_HUB_PHP_PACKAGES_TITLE']; ?></h2>

            <p class="doc-article-meta">Published: <time datetime="2026-05-07">2026-05-07</time></p>
          <p><?php echo $i18n['TRANSPARENCY_HUB_PHP_PACKAGES_TEXT_1']; ?></p>
          <ul class="doc-fact-list">
            <li><?php echo $i18n['TRANSPARENCY_HUB_PHP_PACKAGES_FACT_1']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_PHP_PACKAGES_FACT_2']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_PHP_PACKAGES_FACT_3']; ?></li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/php-packages/'); ?>"><?php echo $i18n['TRANSPARENCY_HUB_PHP_PACKAGES_READ_MORE']; ?></a></p>
        </section>

        <section class="doc-section">
          <h2><?php echo $i18n['TRANSPARENCY_HUB_FRAMEWORK_BACKEND_TITLE']; ?></h2>

            <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p><?php echo $i18n['TRANSPARENCY_HUB_FRAMEWORK_BACKEND_TEXT_1']; ?></p>
          <ul class="doc-fact-list">
            <li><?php echo $i18n['TRANSPARENCY_HUB_FRAMEWORK_BACKEND_FACT_1']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_FRAMEWORK_BACKEND_FACT_2']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_FRAMEWORK_BACKEND_FACT_3']; ?></li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2><?php echo $i18n['TRANSPARENCY_HUB_PRODUCT_BILLING_TITLE']; ?></h2>

            <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p><?php echo $i18n['TRANSPARENCY_HUB_PRODUCT_BILLING_TEXT_1']; ?></p>
          <ul class="doc-fact-list">
            <li><?php echo $i18n['TRANSPARENCY_HUB_PRODUCT_BILLING_FACT_1']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_PRODUCT_BILLING_FACT_2']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_PRODUCT_BILLING_FACT_3']; ?></li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2><?php echo $i18n['TRANSPARENCY_HUB_TAX_METHODOLOGY_TITLE']; ?></h2>

            <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p><?php echo $i18n['TRANSPARENCY_HUB_TAX_METHODOLOGY_TEXT_1']; ?></p>
          <ul class="doc-fact-list">
            <li><?php echo $i18n['TRANSPARENCY_HUB_TAX_METHODOLOGY_FACT_1']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_TAX_METHODOLOGY_FACT_2']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_TAX_METHODOLOGY_FACT_3']; ?></li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/taxes/'); ?>"><?php echo $i18n['TRANSPARENCY_HUB_TAX_METHODOLOGY_READ_MORE']; ?></a></p>
        </section>

        <section class="doc-section">
          <h2><?php echo $i18n['TRANSPARENCY_HUB_EMAIL_ARCHITECTURE_TITLE']; ?></h2>

            <p class="doc-article-meta">Published: <time datetime="2026-03-21">2026-03-21</time></p>
          <p><?php echo $i18n['TRANSPARENCY_HUB_EMAIL_ARCHITECTURE_TEXT_1']; ?></p>
          <ul class="doc-fact-list">
            <li><?php echo $i18n['TRANSPARENCY_HUB_EMAIL_ARCHITECTURE_FACT_1']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_EMAIL_ARCHITECTURE_FACT_2']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_EMAIL_ARCHITECTURE_FACT_3']; ?></li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/email/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2><?php echo $i18n['TRANSPARENCY_HUB_EARNINGS_LOAD_TESTING_TITLE']; ?></h2>

            <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p><?php echo $i18n['TRANSPARENCY_HUB_EARNINGS_LOAD_TESTING_TEXT_1']; ?> <code>/earnings/</code>.</p>
          <ul class="doc-fact-list">
            <li><?php echo $i18n['TRANSPARENCY_HUB_EARNINGS_LOAD_TESTING_FACT_1']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_EARNINGS_LOAD_TESTING_FACT_2']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_EARNINGS_LOAD_TESTING_FACT_3']; ?></li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/load-testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2><?php echo $i18n['TRANSPARENCY_HUB_SUPERHEROES_MAP_TITLE']; ?></h2>

            <p class="doc-article-meta">Published: <time datetime="2026-04-02">2026-04-02</time></p>
          <p><?php echo $i18n['TRANSPARENCY_HUB_SUPERHEROES_MAP_TEXT_1']; ?></p>
          <ul class="doc-fact-list">
            <li><?php echo $i18n['TRANSPARENCY_HUB_SUPERHEROES_MAP_FACT_1']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_SUPERHEROES_MAP_FACT_2']; ?></li>
            <li><?php echo $i18n['TRANSPARENCY_HUB_SUPERHEROES_MAP_FACT_3']; ?></li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/superheroes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Extensions Paradigm</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
          <p>This article explains how PayCal Core remains stable while extension packages provide configurable behavior for different deployment models.</p>
          <ul class="doc-fact-list">
            <li>Clarifies the separation between PayCal Core and in-repo basic extensions.</li>
            <li>Documents how third parties can build custom extensions from this repository.</li>
            <li>Explains how canonical paycal.app uses private extension variants to differentiate the platform.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/extensions/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>
      </div>
    </section>
  </article>
<?php
require_once HTML.'/footer.php';
