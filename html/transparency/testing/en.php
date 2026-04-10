<?php
/**
 * Public Transparency: Testing and Validation Governance
 *
 * PURPOSE: Publish the release validation model and test governance policy.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'TRANSPARENCY_TESTING_GOVERNANCE_PAGE_TITLE',
  'TRANSPARENCY_TESTING_GOVERNANCE_DECK',
  'TRANSPARENCY_TESTING_VERIFICATION_METADATA_TITLE',
  'TRANSPARENCY_TESTING_RELEASE_BLOCKING_STACK_TITLE',
  'TRANSPARENCY_TESTING_CURRENT_TEST_TOPOLOGY_TITLE',
  'TRANSPARENCY_TESTING_RECENT_EXPANSION_TITLE',
  'TRANSPARENCY_TESTING_PUBLIC_TRACEABILITY_TITLE',
  'TRANSPARENCY_TESTING_CURRENT_SUITE_INVENTORY_VALUE',
  'TRANSPARENCY_TESTING_TABLE_ARIA',
  'TRANSPARENCY_TESTING_TABLE_SUITE_CATEGORY',
  'TRANSPARENCY_TESTING_TABLE_COVERAGE_FOCUS',
  'TRANSPARENCY_TESTING_TABLE_FILES',
  'TRANSPARENCY_TESTING_SUITE_UNIT',
  'TRANSPARENCY_TESTING_SUITE_INTEGRATION',
  'TRANSPARENCY_TESTING_SUITE_CONTRACT',
  'TRANSPARENCY_TESTING_SUITE_MANUAL',
  'TRANSPARENCY_TESTING_RELEASE_BLOCKING_STACK_TEXT_1',
  'TRANSPARENCY_TESTING_SUITE_UNIT_COVERAGE',
  'TRANSPARENCY_TESTING_SUITE_INTEGRATION_COVERAGE',
  'TRANSPARENCY_TESTING_SUITE_CONTRACT_COVERAGE',
  'TRANSPARENCY_TESTING_SUITE_MANUAL_COVERAGE',
  'TRANSPARENCY_TESTING_RECENT_EXPANSION_ITEM_1',
  'TRANSPARENCY_TESTING_RECENT_EXPANSION_ITEM_2',
  'TRANSPARENCY_TESTING_RECENT_EXPANSION_ITEM_3',
  'TRANSPARENCY_TESTING_RECENT_EXPANSION_ITEM_4',
  'TRANSPARENCY_TESTING_PUBLIC_TRACEABILITY_TEXT_1',
  'TRANSPARENCY_TESTING_PUBLIC_TRACEABILITY_TEXT_2',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_TESTING_GOVERNANCE_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_TESTING_GOVERNANCE_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo $i18n['TRANSPARENCY_TESTING_GOVERNANCE_PAGE_TITLE']; ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo $i18n['TRANSPARENCY_TESTING_GOVERNANCE_PAGE_TITLE']; ?></h1>
    <p class="deck"><?php echo $i18n['TRANSPARENCY_TESTING_GOVERNANCE_DECK']; ?></p>
    <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_TESTING_VERIFICATION_METADATA_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><strong>Route:</strong> <code>/transparency/testing/</code></li>
        <li><strong>Last verified:</strong> <time datetime="2026-03-24">2026-03-24</time></li>
        <li><strong>Next review due:</strong> <time datetime="2026-06-24">2026-06-24</time></li>
        <li><strong>Current suite inventory:</strong> <?php echo $i18n['TRANSPARENCY_TESTING_CURRENT_SUITE_INVENTORY_VALUE']; ?></li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_TESTING_RELEASE_BLOCKING_STACK_TITLE']; ?></h2>
      <p>For release hygiene sweeps (including <code>/mis</code> workflow), the following gates are run and treated as blockers:</p>
      <ul class="doc-fact-list">
        <li><code>./vendor/bin/phpunit -c phpunit.xml</code> (backend unit/integration/contract suite)</li>
        <li><code>cd html && composer run phpstan:strict</code> (PHPStan Level 9 strict)</li>
        <li><code>cd .. && npm run test:js</code> (JS lint + security sink checks)</li>
        <li><code>cd .. && npm run test:a11y:all</code> (PHPUnit a11y + Playwright + strict WCAG + contrast + Lightpanda)</li>
      </ul>
      <p><?php echo $i18n['TRANSPARENCY_TESTING_RELEASE_BLOCKING_STACK_TEXT_1']; ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_TESTING_CURRENT_TEST_TOPOLOGY_TITLE']; ?></h2>
      <table class="doc-table" aria-label="<?php echo $i18n['TRANSPARENCY_TESTING_TABLE_ARIA']; ?>">
        <thead>
          <tr>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_TESTING_TABLE_SUITE_CATEGORY']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_TESTING_TABLE_FILES']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_TESTING_TABLE_COVERAGE_FOCUS']; ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_TESTING_SUITE_UNIT']; ?></td>
            <td>60</td>
            <td><?php echo $i18n['TRANSPARENCY_TESTING_SUITE_UNIT_COVERAGE']; ?></td>
          </tr>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_TESTING_SUITE_INTEGRATION']; ?></td>
            <td>49</td>
            <td><?php echo $i18n['TRANSPARENCY_TESTING_SUITE_INTEGRATION_COVERAGE']; ?></td>
          </tr>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_TESTING_SUITE_CONTRACT']; ?></td>
            <td>7</td>
            <td><?php echo $i18n['TRANSPARENCY_TESTING_SUITE_CONTRACT_COVERAGE']; ?></td>
          </tr>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_TESTING_SUITE_MANUAL']; ?></td>
            <td>2</td>
            <td><?php echo $i18n['TRANSPARENCY_TESTING_SUITE_MANUAL_COVERAGE']; ?></td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section success">
      <h2><?php echo $i18n['TRANSPARENCY_TESTING_RECENT_EXPANSION_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><?php echo $i18n['TRANSPARENCY_TESTING_RECENT_EXPANSION_ITEM_1']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_TESTING_RECENT_EXPANSION_ITEM_2']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_TESTING_RECENT_EXPANSION_ITEM_3']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_TESTING_RECENT_EXPANSION_ITEM_4']; ?></li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_TESTING_PUBLIC_TRACEABILITY_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_TESTING_PUBLIC_TRACEABILITY_TEXT_1']; ?></p>
      <ul class="doc-fact-list">
        <li><code>docs/v1.changelog.md</code> (technical release detail)</li>
        <li>Transparency pages under <code>/transparency/</code> (public explanation)</li>
      </ul>
      <p><?php echo $i18n['TRANSPARENCY_TESTING_PUBLIC_TRACEABILITY_TEXT_2']; ?></p>
      <p>For npm lockfile policy and CI gate mapping details, see <a href="<?php echo transparency_href('/transparency/dependency-ci/'); ?>"><code>/transparency/dependency-ci/</code></a>.</p>
    </section>
  </div>
</article>
<?php
require_once HTML.'/footer.php';
