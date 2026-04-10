<?php
/**
 * Public Transparency: Security Audit Status
 *
 * PURPOSE: Publish the current security audit posture, scope, and evidence summary.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'TRANSPARENCY_SECURITY_AUDIT_PAGE_TITLE',
  'TRANSPARENCY_SECURITY_AUDIT_DECK',
  'TRANSPARENCY_SECURITY_AUDIT_VERIFICATION_METADATA_TITLE',
  'TRANSPARENCY_SECURITY_AUDIT_SCOPE_CLOSED_TITLE',
  'TRANSPARENCY_SECURITY_AUDIT_VALIDATION_SNAPSHOT_TITLE',
  'TRANSPARENCY_SECURITY_AUDIT_VALIDATION_SNAPSHOT_ARIA',
  'TRANSPARENCY_SECURITY_AUDIT_RUNTIME_SUMMARY_TITLE',
  'TRANSPARENCY_SECURITY_AUDIT_MAINTENANCE_COMMITMENTS_TITLE',
  'TRANSPARENCY_SECURITY_AUDIT_EVIDENCE_SOURCES_TITLE',
  'TRANSPARENCY_SECURITY_AUDIT_ROUTE_METADATA_TITLE',
  'TRANSPARENCY_SECURITY_AUDIT_TABLE_GATE',
  'TRANSPARENCY_SECURITY_AUDIT_TABLE_RESULT',
  'TRANSPARENCY_SECURITY_AUDIT_TABLE_EVIDENCE',
    'TRANSPARENCY_SECURITY_AUDIT_USE_CASE_TITLE',
  'TRANSPARENCY_SECURITY_AUDIT_USE_CASE_ARIA',
  'TRANSPARENCY_SECURITY_AUDIT_ROUTE_LABEL',
  'TRANSPARENCY_SECURITY_AUDIT_STATUS_LABEL',
  'TRANSPARENCY_SECURITY_AUDIT_STATUS_VALUE',
  'TRANSPARENCY_SECURITY_AUDIT_LAST_VERIFIED_LABEL',
  'TRANSPARENCY_SECURITY_AUDIT_NEXT_REVIEW_LABEL',
  'TRANSPARENCY_SECURITY_AUDIT_SUMMARY_TEXT_1',
  'TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_A',
  'TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_B',
  'TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_C',
  'TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_D',
  'TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_E',
  'TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_F',
  'TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_G',
  'TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_H',
  'TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_I',
  'TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_GATE_PLAYWRIGHT',
  'TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_GATE_JSLINT',
  'TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_GATE_PHPSTAN',
  'TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_GATE_PHPUNIT',
  'TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_RESULT_PASS',
  'TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_EVIDENCE_PLAYWRIGHT',
  'TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_EVIDENCE_PHPUNIT',
  'TRANSPARENCY_SECURITY_AUDIT_RUNTIME_ITEM_1',
  'TRANSPARENCY_SECURITY_AUDIT_RUNTIME_ITEM_2',
  'TRANSPARENCY_SECURITY_AUDIT_RUNTIME_ITEM_3',
  'TRANSPARENCY_SECURITY_AUDIT_RUNTIME_ITEM_4',
  'TRANSPARENCY_SECURITY_AUDIT_MAINTENANCE_TEXT_1',
  'TRANSPARENCY_SECURITY_AUDIT_USE_CASE_TEXT_1',
  'TRANSPARENCY_SECURITY_AUDIT_MAINTENANCE_ITEM_3',
  'TRANSPARENCY_SECURITY_AUDIT_MAINTENANCE_TEXT_2',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_SECURITY_AUDIT_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_SECURITY_AUDIT_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_PAGE_TITLE']; ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_PAGE_TITLE']; ?></h1>
    <p class="deck"><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_DECK']; ?></p>
    <p class="doc-article-meta">Published: <time datetime="2026-03-23">2026-03-23</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_VERIFICATION_METADATA_TITLE']; ?></h2>
      <div class="doc-two-column">
        <div>
          <h3><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_ROUTE_METADATA_TITLE']; ?></h3>
          <ul class="doc-fact-list">
            <li><strong><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_ROUTE_LABEL']; ?></strong> <code>/transparency/security-audit/</code></li>
            <li><strong><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_STATUS_LABEL']; ?></strong> <?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_STATUS_VALUE']; ?></li>
            <li><strong><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_LAST_VERIFIED_LABEL']; ?></strong> <time datetime="2026-03-24">2026-03-24</time></li>
            <li><strong><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_NEXT_REVIEW_LABEL']; ?></strong> <time datetime="2026-06-24">2026-06-24</time></li>
          </ul>
        </div>
        <div>
          <h3><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_EVIDENCE_SOURCES_TITLE']; ?></h3>
          <ul class="doc-fact-list">
            <li><code>docs/security/GEMINI_SECURITY_AUDIT_HANDOFF_AUDITOR_2026-03-23.md</code></li>
            <li><code>docs/SECURITY_INTERROGATION_EVIDENCE_2026-03-23.md</code></li>
            <li><code>docs/security/PHASE_CLOSURE_PROGRAM.md</code></li>
          </ul>
        </div>
      </div>
      <p><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SUMMARY_TEXT_1']; ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SCOPE_CLOSED_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_A']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_B']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_C']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_D']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_E']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_F']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_G']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_H']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SCOPE_ITEM_I']; ?></li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_VALIDATION_SNAPSHOT_TITLE']; ?></h2>
      <table class="doc-table" aria-label="<?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_VALIDATION_SNAPSHOT_ARIA']; ?>">
        <thead>
          <tr>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_TABLE_GATE']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_TABLE_RESULT']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_TABLE_EVIDENCE']; ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_GATE_PLAYWRIGHT']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_RESULT_PASS']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_EVIDENCE_PLAYWRIGHT']; ?></td>
          </tr>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_GATE_JSLINT']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_RESULT_PASS']; ?></td>
            <td><code>npm run test:js</code></td>
          </tr>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_GATE_PHPSTAN']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_RESULT_PASS']; ?></td>
            <td><code>[OK] No errors</code></td>
          </tr>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_GATE_PHPUNIT']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_RESULT_PASS']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_SNAPSHOT_EVIDENCE_PHPUNIT']; ?></td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section success">
      <h2><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_RUNTIME_SUMMARY_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_RUNTIME_ITEM_1']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_RUNTIME_ITEM_2']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_RUNTIME_ITEM_3']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_RUNTIME_ITEM_4']; ?></li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_MAINTENANCE_COMMITMENTS_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_MAINTENANCE_TEXT_1']; ?></p>
      <div class="subject-example-cutout" role="note" aria-label="<?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_USE_CASE_ARIA']; ?>">
          <h3><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_USE_CASE_TITLE']; ?></h3>
        <p><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_USE_CASE_TEXT_1']; ?></p>
      </div>
      <ul class="doc-fact-list">
        <li><code>tests/smoke-ui/dev-bypass-smoke.spec.js</code> lifecycle regression suite.</li>
        <li><code>composer run phpstan:strict</code> with no baseline policy exceptions.</li>
        <li><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_MAINTENANCE_ITEM_3']; ?></li>
      </ul>
      <p><?php echo $i18n['TRANSPARENCY_SECURITY_AUDIT_MAINTENANCE_TEXT_2']; ?></p>
    </section>
  </div>
</article>
<?php
require_once HTML.'/footer.php';
