<?php
/**
 * Public Transparency: Dependency and CI/CD Governance
 *
 * PURPOSE: Publish how PayCal manages npm dependencies and CI/CD validation controls.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'TRANSPARENCY_DEPENDENCY_CI_PAGE_TITLE',
  'TRANSPARENCY_DEPENDENCY_CI_DECK',
  'TRANSPARENCY_DEPENDENCY_CI_VERIFICATION_METADATA_TITLE',
  'TRANSPARENCY_DEPENDENCY_CI_NPM_POLICY_TITLE',
  'TRANSPARENCY_DEPENDENCY_CI_USAGE_TITLE',
  'TRANSPARENCY_DEPENDENCY_CI_ROUTE_LABEL',
  'TRANSPARENCY_DEPENDENCY_CI_LAST_VERIFIED_LABEL',
  'TRANSPARENCY_DEPENDENCY_CI_NEXT_REVIEW_LABEL',
  'TRANSPARENCY_DEPENDENCY_CI_SCOPE_LABEL',
  'TRANSPARENCY_DEPENDENCY_CI_SCOPE_VALUE',
  'TRANSPARENCY_DEPENDENCY_CI_NPM_POLICY_TEXT_1',
  'TRANSPARENCY_DEPENDENCY_CI_TABLE_ARIA',
  'TRANSPARENCY_DEPENDENCY_CI_TABLE_PURPOSE',
  'TRANSPARENCY_DEPENDENCY_CI_TABLE_COMMAND',
  'TRANSPARENCY_DEPENDENCY_CI_TABLE_CONTROL',
  'TRANSPARENCY_DEPENDENCY_CI_USAGE_ROW1_PURPOSE',
  'TRANSPARENCY_DEPENDENCY_CI_USAGE_ROW1_CONTROL',
  'TRANSPARENCY_DEPENDENCY_CI_USAGE_ROW2_PURPOSE',
  'TRANSPARENCY_DEPENDENCY_CI_USAGE_ROW2_CONTROL',
  'TRANSPARENCY_DEPENDENCY_CI_USAGE_ROW3_PURPOSE',
  'TRANSPARENCY_DEPENDENCY_CI_USAGE_ROW3_CONTROL',
  'TRANSPARENCY_DEPENDENCY_CI_USAGE_ROW4_PURPOSE',
  'TRANSPARENCY_DEPENDENCY_CI_USAGE_ROW4_CONTROL',
  'TRANSPARENCY_DEPENDENCY_CI_CICD_GATE_TITLE',
  'TRANSPARENCY_DEPENDENCY_CI_CICD_GATE_TEXT_1',
  'TRANSPARENCY_DEPENDENCY_CI_CICD_GATE_TEXT_2',
  'TRANSPARENCY_DEPENDENCY_CI_KNOWN_LIMITATIONS_TITLE',
  'TRANSPARENCY_DEPENDENCY_CI_KNOWN_LIMITATIONS_ITEM_1',
  'TRANSPARENCY_DEPENDENCY_CI_KNOWN_LIMITATIONS_ITEM_2',
  'TRANSPARENCY_DEPENDENCY_CI_PLANNED_IMPROVEMENTS_TITLE',
  'TRANSPARENCY_DEPENDENCY_CI_PLANNED_IMPROVEMENTS_ITEM_1',
  'TRANSPARENCY_DEPENDENCY_CI_PLANNED_IMPROVEMENTS_ITEM_2',
  'TRANSPARENCY_DEPENDENCY_CI_PLANNED_IMPROVEMENTS_ITEM_3',
  'TRANSPARENCY_DEPENDENCY_CI_HOW_TO_VERIFY_TITLE',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_DEPENDENCY_CI_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_DEPENDENCY_CI_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_PAGE_TITLE']; ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_PAGE_TITLE']; ?></h1>
    <p class="deck"><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_DECK']; ?></p>
<p class="doc-article-meta">Published: <time datetime="2026-03-31">2026-03-31</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_VERIFICATION_METADATA_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><strong><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_ROUTE_LABEL']; ?></strong> <code>/transparency/dependency-ci/</code></li>
        <li><strong><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_LAST_VERIFIED_LABEL']; ?></strong> <time datetime="2026-03-31">2026-03-31</time></li>
        <li><strong><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_NEXT_REVIEW_LABEL']; ?></strong> <time datetime="2026-06-30">2026-06-30</time></li>
        <li><strong><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_SCOPE_LABEL']; ?></strong> <?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_SCOPE_VALUE']; ?></li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_NPM_POLICY_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_NPM_POLICY_TEXT_1']; ?></p>
      <ul class="doc-fact-list">
        <li><strong>Install mode for automation:</strong> <code>npm ci</code> only (frozen lockfile install).</li>
        <li><strong>Lockfile source of truth:</strong> <code>package-lock.json</code> is required for deterministic CI installs.</li>
        <li><strong>Declared package manifest:</strong> <code>package.json</code> defines lint, smoke, and accessibility command surfaces.</li>
        <li><strong>Override controls:</strong> dependency overrides are declared in <code>package.json</code> to pin selected transitive risk points.</li>
      </ul>
      <p>If <code>npm ci</code> reports a mismatch between <code>package.json</code> and <code>package-lock.json</code>, the lockfile is updated intentionally in a dedicated maintenance change before CI reruns.</p>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_USAGE_TITLE']; ?></h2>
      <table class="doc-table" aria-label="<?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_TABLE_ARIA']; ?>">
        <thead>
          <tr>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_TABLE_PURPOSE']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_TABLE_COMMAND']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_TABLE_CONTROL']; ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_USAGE_ROW1_PURPOSE']; ?></td>
            <td><code>npm run test:js</code></td>
            <td><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_USAGE_ROW1_CONTROL']; ?></td>
          </tr>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_USAGE_ROW2_PURPOSE']; ?></td>
            <td><code>npm run test:smoke:ui</code></td>
            <td><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_USAGE_ROW2_CONTROL']; ?></td>
          </tr>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_USAGE_ROW3_PURPOSE']; ?></td>
            <td><code>npm run test:a11y:all</code></td>
            <td><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_USAGE_ROW3_CONTROL']; ?></td>
          </tr>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_USAGE_ROW4_PURPOSE']; ?></td>
            <td><code>npm run test:a11y:contrast</code></td>
            <td><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_USAGE_ROW4_CONTROL']; ?></td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section success">
      <h2><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_CICD_GATE_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_CICD_GATE_TEXT_1']; ?></p>
      <ul class="doc-fact-list">
        <li><code>.github/workflows/javascript.yml</code>: Node 20 + <code>npm ci</code> + JavaScript quality gates.</li>
        <li><code>.github/workflows/phpunit.yml</code>: staged backend validation from fast gate to deep verification and artifacts.</li>
        <li><code>.github/workflows/phpstan.yml</code>: strict static analysis with baseline-blocking policy.</li>
      </ul>
      <p><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_CICD_GATE_TEXT_2']; ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_KNOWN_LIMITATIONS_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_KNOWN_LIMITATIONS_ITEM_1']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_KNOWN_LIMITATIONS_ITEM_2']; ?></li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_PLANNED_IMPROVEMENTS_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_PLANNED_IMPROVEMENTS_ITEM_1']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_PLANNED_IMPROVEMENTS_ITEM_2']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_PLANNED_IMPROVEMENTS_ITEM_3']; ?></li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_DEPENDENCY_CI_HOW_TO_VERIFY_TITLE']; ?></h2>
      <pre class="doc-code"># Reproduce JavaScript CI gates locally
npm ci
npm run test:js

# Reproduce broader release-level accessibility gate
npm run test:a11y:all

# Inspect workflow definitions
cat .github/workflows/javascript.yml
cat .github/workflows/phpunit.yml
cat .github/workflows/phpstan.yml</pre>
      <p>Related transparency pages: <a href="<?php echo transparency_href('/transparency/testing/'); ?>">Testing and Validation Governance</a> and <a href="<?php echo transparency_href('/transparency/verification-governance/'); ?>">Verification and Governance</a>.</p>
    </section>
  </div>
</article>
<?php
require_once HTML.'/footer.php';
