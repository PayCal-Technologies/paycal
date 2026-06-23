<?php
/**
 * Public Transparency: Protected Business Work Data Boundary — June 2026
 *
 * PURPOSE:
 * Explain the protected-work-data lifecycle hardening release in public-safe
 * language: what risk we found, what boundary now exists, and how we verified
 * the platform behavior without publishing attacker-useful route detail.
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
$pageTitle = 'How PayCal Hardened Protected Business Work Data - [PayCal]';
$pageLabel = 'How PayCal Hardened Protected Business Work Data';
$pageMetaDescription = 'PayCal hardened protected business work data so business-scoped reads and exports pass through one canonical access gate with membership, consent, encryption, audit, revocation, and regression tests.';
$pageMetaDescriptionLong = 'PayCal hardened its protected business work data lifecycle in June 2026 by enforcing one canonical server-side access boundary before member work rows can be read, reported, exported, cached, or audited.';
$pageSocialTitle = 'How PayCal Hardened Protected Business Work Data';
$pageOgDescription = 'PayCal closed alternate protected-work read and export paths, added regression tests, and verified the full suite for the June 2026 protected-data boundary release.';
$pageTwitterTitle = 'How PayCal Hardened Protected Business Work Data';
$pageTwitterDescription = 'A public summary of PayCal\'s June 2026 protected business work data hardening release.';
$pageDcTitle = 'How PayCal Hardened Protected Business Work Data';
$pageDcDescription = $pageMetaDescription;
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Protected Business Work Data</span>
  </nav>

  <header class="doc-article-header">
    <h1>How PayCal Hardened Protected Business Work Data</h1>
    <p class="deck">
      In June 2026 we completed a security hardening pass around PayCal Business
      member work data. The goal was simple: protected business work data should
      only become readable, reportable, exportable, cacheable, or auditable after
      the same access checks have passed every time.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-06-19">2026-06-19</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Executive Summary</h2>
      <table class="doc-table" aria-label="Protected business work data hardening summary">
        <tbody>
          <tr>
            <td><strong>Area affected</strong></td>
            <td>PayCal Business member work reports, exports, summaries, caches, revocation handling, and audit records</td>
          </tr>
          <tr>
            <td><strong>Risk we addressed</strong></td>
            <td>Different features could evolve their own data-read paths unless the boundary was made explicit and test-enforced</td>
          </tr>
          <tr>
            <td><strong>Core fix</strong></td>
            <td>Protected business work rows now originate through one canonical server-side access gate</td>
          </tr>
          <tr>
            <td><strong>Release verification</strong></td>
            <td>Full PHPUnit passed: 2,274 tests, 18,892 assertions, 31 skipped</td>
          </tr>
          <tr>
            <td><strong>Release commit</strong></td>
            <td><code>9e04fca8</code> — Harden protected business work data lifecycle</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>The Boundary We Wanted</h2>
      <p>
        Business work data is sensitive because it combines identity, membership,
        work history, pay-related calculations, and business context. A correct
        system must not let every report, export, dashboard, or cache decide on
        its own whether that data is readable.
      </p>
      <p>
        The hardened rule is now:
      </p>
      <div class="doc-code-block" data-label="Protected data invariant">
        <pre><code>Protected business member work rows may only originate from the canonical protected access gate.</code></pre>
      </div>
      <p>
        That gate checks the same access basis before protected rows exist for
        downstream features: actor authority, active business membership, member
        visibility, consent, encryption key-wrap state, encrypted envelope context,
        and business-scoped work visibility.
      </p>
    </section>

    <section class="doc-section">
      <h2>What We Fixed</h2>
      <p>
        The hardening pass closed the places where protected work data handling
        could become inconsistent over time:
      </p>
      <ul class="doc-list">
        <li>Business member reports now read protected work through the same server-side gate.</li>
        <li>Business summaries, business reports, and workspace warmers no longer materialize protected rows through weaker no-actor fallbacks.</li>
        <li>Binary business exports are rebuilt server-side from authorized data instead of trusting client-supplied rows.</li>
        <li>Legacy personal export endpoints reject business-marked or protected-looking payloads.</li>
        <li>Revocation and cache behavior now have regression coverage proving stale cached data is denied after access is revoked.</li>
        <li>Audit events now distinguish requested, started, denied, completed, and failed export outcomes.</li>
        <li>CSV and TXT files are labeled as browser convenience exports, while PDF and XLSX business artifacts are server-authorized exports.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Consent, Encryption, and Revocation</h2>
      <p>
        Consent is not treated as a one-time UI checkbox. For protected business
        work data, PayCal checks active membership, active consent, and active
        encryption key-wrap state before protected rows are released. If access is
        revoked or the required encryption state is no longer valid, protected
        reads and exports fail closed.
      </p>
      <p>
        We also updated the member-facing consent panel so users can see a clearer
        permission matrix: who can access protected work data, what membership
        state is required, what credential/envelope checks apply, what consent
        value is current, and what revocation means.
      </p>
    </section>

    <section class="doc-section">
      <h2>Exports and Evidence</h2>
      <p>
        Not every downloadable file has the same trust level. This release makes
        that distinction explicit.
      </p>
      <table class="doc-table" aria-label="Export trust model">
        <thead>
          <tr>
            <th>Format</th>
            <th>Trust model</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>PDF / XLSX</td>
            <td>Server-authorized business artifacts rebuilt from protected access checks</td>
          </tr>
          <tr>
            <td>CSV / TXT</td>
            <td>Browser convenience exports generated from already authorized report data</td>
          </tr>
          <tr>
            <td>ZIP packages</td>
            <td>Package assembly that preserves the trust label of each included artifact</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>How We Verified It</h2>
      <p>
        The release added focused regression tests and architecture tests. The
        important verification is not only that the current code works, but that
        future changes are blocked from quietly reintroducing alternate protected
        data paths.
      </p>
      <ul class="doc-list">
        <li>Architecture tests fail if protected business work rows are fetched outside the canonical gate.</li>
        <li>Legacy export endpoint tests cover forged business/member payloads and business-marked current-user payloads.</li>
        <li>Revocation tests cover stale cached reports, summaries, exports, and business reports.</li>
        <li>Bulk audit tests cover a 100-member report batch as one coherent audit event.</li>
        <li>Full PHPUnit passed with 2,274 tests and 18,892 assertions.</li>
        <li>The pre-commit suite passed with 1,397 tests and 9,351 assertions.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>What This Does Not Mean</h2>
      <p>
        This article is not a claim that no security work remains. It is a public
        record of one boundary being tightened: protected business work data now
        has a single tested access path before rows can be materialized.
      </p>
      <p>
        We are publishing the outcome and the control model, not low-level route
        internals or operational details that would make the system easier to
        target.
      </p>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
