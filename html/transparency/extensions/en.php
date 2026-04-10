<?php
/**
 * Public Transparency: Extensions Paradigm
 *
 * PURPOSE:
 * Explain how PayCal separates core logic from extension layers, how third
 * parties can build custom extensions from this repository, and how
 * canonical paycal.app differentiates through private extension packages.
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
$pageTitle = 'Extensions Paradigm - [PayCal]';
$pageLabel = 'Extensions Paradigm';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Extensions Paradigm</span>
  </nav>

  <header class="doc-article-header">
    <h1>Extensions Paradigm</h1>
    <p class="deck">
      PayCal is designed so core business logic remains stable while extension
      layers can adapt features for different deployments and product strategies.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Core First Architecture</h2>
      <p>
        <strong>PayCal Core</strong> contains the canonical domain and controller logic:
        calculations, validation, permissions, lifecycle policy, and shared API contracts.
      </p>
      <p>
        Core remains extension-agnostic by design. Integration points are isolated
        through bridge contracts so core services can be tested independently of
        runtime-specific packages.
      </p>
    </section>

    <section class="doc-section">
      <h2>Basic Extensions Included In This Repository</h2>
      <p>
        This repository ships <strong>basic extension implementations</strong> that provide
        default behavior for extension seams. These act as public reference packages
        and safe defaults for self-hosted deployments.
      </p>
      <ul class="doc-list">
        <li><strong>billing-provider:</strong> baseline billing capability hooks and mode selection</li>
        <li><strong>earnings-ytd:</strong> baseline YTD rendering and earnings hook points</li>
        <li><strong>organization-signals:</strong> baseline organization signal hooks</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Third-Party Extension Model</h2>
      <p>
        Third parties using this repository can create and maintain their own
        extension packages. The expected model is:
      </p>
      <ol class="doc-list">
        <li>Keep core logic unmodified whenever possible</li>
        <li>Implement custom behavior in extension packages</li>
        <li>Bind custom packages through documented extension bootstrap and hook seams</li>
        <li>Preserve core contracts so upstream upgrades remain manageable</li>
      </ol>
      <p>
        This allows competitive and vertical-specific deployments without forcing
        long-term forks of core domain code.
      </p>
    </section>

    <section class="doc-section">
      <h2>Canonical paycal.app Differentiation</h2>
      <p>
        The canonical <code>https://paycal.app</code> platform runs <strong>private extension
        variants</strong> on top of the same core and basic extension paradigm.
      </p>
      <p>
        These private variants are a deliberate product differentiation layer for
        PayCal-operated environments. They can tune workflows, capability behavior,
        and UI-specific integrations while preserving compatibility with the same
        core architecture.
      </p>
      <ul class="doc-list">
        <li>Core logic remains shared and auditable</li>
        <li>Public/basic extensions remain available in-repo</li>
        <li>Private extensions provide canonical platform differentiation</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Transparency Commitments</h2>
      <ul class="doc-list">
        <li>Core contracts are documented and tested at extension seams</li>
        <li>Bridge boundaries are explicit to make coupling discoverable</li>
        <li>Extension behavior can evolve without destabilizing core services</li>
        <li>Self-hosted adopters are free to build alternative extension strategies</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
