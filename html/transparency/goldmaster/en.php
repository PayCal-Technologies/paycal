<?php
/**
 * Public Transparency: GoldMaster
 *
 * PURPOSE: Explain PayCal's internal canonical-example system.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'GoldMaster - [PayCal]';
$pageLabel = 'GoldMaster';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="Breadcrumb">
    <a href="<?php echo transparency_href('/transparency/'); ?>">Transparency Hub</a>
    <span class="separator">/</span>
    <span class="current">GoldMaster</span>
  </nav>

  <header class="doc-article-header">
    <h1>GoldMaster</h1>
    <p class="deck">
      GoldMaster is PayCal's internal quality guide for canonical examples.
      It keeps future code, UI, tests, and architecture aligned with the best
      existing patterns.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-06-21">2026-06-21</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>What It Is</h2>
      <p>
        GoldMaster is the name of the internal guide. The <code>golden_masters</code>
        directory contains the actual reference artifacts. They are curated
        examples used before building a similar feature.
      </p>
      <ul class="doc-fact-list">
        <li>Golden masters are not production code.</li>
        <li>They are read-only references in the first implementation.</li>
        <li>They describe preferred structure, naming, validation, accessibility, tests, and UI behavior.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Why It Exists</h2>
      <p>
        PayCal has many security-sensitive and accessibility-sensitive patterns.
        GoldMaster makes the preferred examples explicit so humans and AI agents
        start from a known-good pattern rather than rediscovering the same rules
        in scattered files.
      </p>
    </section>

    <section class="doc-section">
      <h2>Operating Rules</h2>
      <ul class="doc-fact-list">
        <li>Consult the nearest golden master before creating or changing a similar feature.</li>
        <li>Mirror the example's structure only when it fits the feature.</li>
        <li>Mark stale examples as <strong>Needs Review</strong> before replacing them.</li>
        <li>Do not execute code from the GoldMaster editor.</li>
        <li>Do not expose the admin editor publicly.</li>
      </ul>
    </section>

    <section class="doc-section success">
      <h2>First Reference</h2>
      <p>
        The first active golden master is the Calendar dialog pattern. It defines
        PayCal's modal header, footer action order, compact confirmation dialogs,
        and focus behavior for Groups, Business Sites, personal Sites, and future
        dialogs.
      </p>
      <ul class="doc-fact-list">
        <li>Primary action appears before Close or Cancel.</li>
        <li>Archive, Restore, Delete, and Unlink use PayCal dialogs, not native browser confirms.</li>
        <li>Focus must not remain inside a hidden dialog.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Verification Anchors</h2>
      <ul class="doc-fact-list">
        <li><code>golden_masters/README.md</code> documents the concept and metadata rules.</li>
        <li><code>golden_masters/ui/modal-dialog-pattern/metadata.json</code> indexes the first active example.</li>
        <li><code>html/src/Domain/GoldMasterCatalog.php</code> loads metadata from disk and keeps previews inside <code>golden_masters</code>.</li>
        <li><code>html/tests/Unit/Domain/GoldMasterCatalogTest.php</code> verifies the catalog and file preview behavior.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Related Reading</h2>
      <p>
        GoldMaster is now part of PayCal's named internal systems. See the
        <a href="<?php echo transparency_href('/transparency/superheroes/'); ?>">Superheroes System Map</a>
        for where it sits beside ShadowTalon, Guardian, Phantom Wing, Lens,
        EmailGarum, and Echo.
      </p>
    </section>
  </div>
</article>
<?php
require_once HTML.'/footer.php';
