<?php
/**
 * Public Transparency: Organization Membership and Role Philosophy
 *
 * PURPOSE:
 * Explain why PayCal uses an Organization <-> Member relationship model,
 * how role changes are governed, and what architectural philosophy guides
 * capability, scope, and security decisions.
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
$pageTitle = 'Organization Membership and Role Philosophy - [PayCal]';
$pageLabel = 'Organization Membership and Role Philosophy';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Organization Membership and Role Philosophy</span>
  </nav>

  <header class="doc-article-header">
    <h1>Organization Membership and Role Philosophy</h1>
    <p class="deck">
      This page explains the shift from loosely-coupled team semantics to an explicit
      Organization <strong>&lt;-&gt;</strong> Member relationship model, the current role policy,
      and the principles we use to keep permissions auditable and secure.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Why This Model Exists</h2>
      <p>
        Payroll collaboration has real security impact. A role model that is easy to read,
        test, and audit is safer than a model built from scattered one-off checks.
      </p>
      <p>
        The Organization <strong>&lt;-&gt;</strong> Member structure gives every actor an explicit
        relationship to an organization with policy-aware status, role, and scope behavior.
      </p>
    </section>

    <section class="doc-section">
      <h2>Organization <strong>&lt;-&gt;</strong> Member Relationship Changes</h2>
      <ul class="doc-list">
        <li>Membership is represented as an explicit relationship rather than an implicit UI state.</li>
        <li>Access-request, invite, approval, activation, and revocation lifecycle states are enforced by backend policy.</li>
        <li>Organization panels and notifications now reflect relationship transitions and role outcomes more consistently.</li>
        <li>Shared organization behavior is governed by membership state before privileged actions are processed.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Role Changes and Current Role Philosophy</h2>
      <p>
        Roles are capability-driven, with scope restrictions applied per operation. The current baseline:
      </p>
      <ul class="doc-list">
        <li><strong>owner:</strong> sovereign control including ownership transfer and high-trust governance actions.</li>
        <li><strong>manager:</strong> day-to-day operational control without ownership transfer authority.</li>
        <li><strong>contributor:</strong> trusted operator with write authority constrained by assigned scope.</li>
        <li><strong>member:</strong> limited self-service participation with restricted mutation rights.</li>
        <li><strong>viewer:</strong> read-only visibility without write permissions.</li>
      </ul>
      <p>
        We favor explicit capability and scope composition over overloaded role flags. This keeps role outcomes easier to test and reason about.
      </p>
    </section>

    <section class="doc-section">
      <h2>Security and Encryption Philosophy</h2>
      <p>
        Organization collaboration intersects with encryption and consent controls. Membership and role checks
        gate shared organization envelope behavior so sensitive operations remain policy-bound.
      </p>
      <ul class="doc-list">
        <li>Membership and consent state are validated before organization-shared secure operations proceed.</li>
        <li>Role changes and membership transitions are treated as security-relevant events, not only UX events.</li>
        <li>Access denial paths are expected behavior under policy mismatch and are surfaced for auditability.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Operational Philosophy Going Forward</h2>
      <ul class="doc-list">
        <li><strong>Single policy source:</strong> role and scope decisions should originate from shared backend policy maps.</li>
        <li><strong>UI as projection:</strong> interfaces should display policy outcomes rather than duplicate authorization logic.</li>
        <li><strong>Traceable transitions:</strong> approvals, role changes, and revocations should remain observable and reviewable.</li>
        <li><strong>Release transparency:</strong> behavior changes in membership and roles are documented in changelogs and transparency pages.</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
