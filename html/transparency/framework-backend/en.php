<?php
/**
 * Public Transparency: Framework and Backend Change Ledger
 *
 * PURPOSE: Publish backend/framework-level changes that materially affect product behavior and security posture.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'TRANSPARENCY_FRAMEWORK_BACKEND_PAGE_TITLE',
  'TRANSPARENCY_FRAMEWORK_BACKEND_DECK',
  'TRANSPARENCY_FRAMEWORK_BACKEND_VERIFICATION_METADATA_TITLE',
  'TRANSPARENCY_FRAMEWORK_BACKEND_RECENT_MILESTONES_TITLE',
  'TRANSPARENCY_FRAMEWORK_BACKEND_GOVERNANCE_PRINCIPLES_TITLE',
  'TRANSPARENCY_FRAMEWORK_BACKEND_EVIDENCE_TITLE',
  'TRANSPARENCY_FRAMEWORK_BACKEND_TABLE_ARIA',
  'TRANSPARENCY_FRAMEWORK_BACKEND_TABLE_RELEASE',
  'TRANSPARENCY_FRAMEWORK_BACKEND_TABLE_WHAT_CHANGED',
  'TRANSPARENCY_FRAMEWORK_BACKEND_TABLE_AREA',
  'TRANSPARENCY_FRAMEWORK_BACKEND_AREA_SECURITY_GOVERNANCE',
  'TRANSPARENCY_FRAMEWORK_BACKEND_AREA_SECURITY_CONTROLS',
  'TRANSPARENCY_FRAMEWORK_BACKEND_AREA_CONTROL_CLOSURE',
  'TRANSPARENCY_FRAMEWORK_BACKEND_VERIFICATION_METADATA_ROUTE_LABEL',
  'TRANSPARENCY_FRAMEWORK_BACKEND_VERIFICATION_METADATA_LAST_VERIFIED_LABEL',
  'TRANSPARENCY_FRAMEWORK_BACKEND_VERIFICATION_METADATA_NEXT_REVIEW_LABEL',
  'TRANSPARENCY_FRAMEWORK_BACKEND_VERIFICATION_METADATA_PRIMARY_REFS_LABEL',
  'TRANSPARENCY_FRAMEWORK_BACKEND_MILESTONE_1043_WHAT_CHANGED',
  'TRANSPARENCY_FRAMEWORK_BACKEND_MILESTONE_1042_WHAT_CHANGED',
  'TRANSPARENCY_FRAMEWORK_BACKEND_MILESTONE_1041_WHAT_CHANGED',
  'TRANSPARENCY_FRAMEWORK_BACKEND_AREA_PRODUCT_MODEL',
  'TRANSPARENCY_FRAMEWORK_BACKEND_MILESTONE_1037_WHAT_CHANGED',
  'TRANSPARENCY_FRAMEWORK_BACKEND_AREA_OBSERVABILITY',
  'TRANSPARENCY_FRAMEWORK_BACKEND_MILESTONE_1032_WHAT_CHANGED',
  'TRANSPARENCY_FRAMEWORK_BACKEND_GOVERNANCE_PRINCIPLES_FACT_1',
  'TRANSPARENCY_FRAMEWORK_BACKEND_GOVERNANCE_PRINCIPLES_FACT_2',
  'TRANSPARENCY_FRAMEWORK_BACKEND_GOVERNANCE_PRINCIPLES_FACT_3',
  'TRANSPARENCY_FRAMEWORK_BACKEND_GOVERNANCE_PRINCIPLES_FACT_4',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_PAGE_TITLE']; ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_PAGE_TITLE']; ?></h1>
    <p class="deck"><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_DECK']; ?></p>
    <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_VERIFICATION_METADATA_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><strong><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_VERIFICATION_METADATA_ROUTE_LABEL']; ?></strong> <code>/transparency/framework-backend/</code></li>
        <li><strong><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_VERIFICATION_METADATA_LAST_VERIFIED_LABEL']; ?></strong> <time datetime="2026-03-24">2026-03-24</time></li>
        <li><strong><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_VERIFICATION_METADATA_NEXT_REVIEW_LABEL']; ?></strong> <time datetime="2026-06-24">2026-06-24</time></li>
        <li><strong><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_VERIFICATION_METADATA_PRIMARY_REFS_LABEL']; ?></strong> <code>docs/v1.changelog.md</code>, <code>docs/CHANGELOG.md</code>, security audit handoff artifacts.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_RECENT_MILESTONES_TITLE']; ?></h2>
      <table class="doc-table" aria-label="<?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_TABLE_ARIA']; ?>">
        <thead>
          <tr>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_TABLE_RELEASE']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_TABLE_AREA']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_TABLE_WHAT_CHANGED']; ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1.043.x</td>
            <td><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_AREA_SECURITY_GOVERNANCE']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_MILESTONE_1043_WHAT_CHANGED']; ?></td>
          </tr>
          <tr>
            <td>1.042.000</td>
            <td><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_AREA_SECURITY_CONTROLS']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_MILESTONE_1042_WHAT_CHANGED']; ?></td>
          </tr>
          <tr>
            <td>1.041.000</td>
            <td><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_AREA_CONTROL_CLOSURE']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_MILESTONE_1041_WHAT_CHANGED']; ?></td>
          </tr>
          <tr>
            <td>1.037.000</td>
            <td><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_AREA_PRODUCT_MODEL']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_MILESTONE_1037_WHAT_CHANGED']; ?></td>
          </tr>
          <tr>
            <td>1.032.000</td>
            <td><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_AREA_OBSERVABILITY']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_MILESTONE_1032_WHAT_CHANGED']; ?></td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section success">
      <h2><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_GOVERNANCE_PRINCIPLES_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_GOVERNANCE_PRINCIPLES_FACT_1']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_GOVERNANCE_PRINCIPLES_FACT_2']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_GOVERNANCE_PRINCIPLES_FACT_3']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_GOVERNANCE_PRINCIPLES_FACT_4']; ?></li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_FRAMEWORK_BACKEND_EVIDENCE_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><code>docs/v1.changelog.md</code> for detailed per-release technical notes.</li>
        <li><code>docs/security/GEMINI_SECURITY_AUDIT_HANDOFF_AUDITOR_2026-03-23.md</code> for control evidence narrative.</li>
        <li><code>/transparency/security-audit/</code> for public audit posture snapshot.</li>
        <li><code>/transparency/verification-governance/</code> for runtime gates and release governance controls.</li>
      </ul>
    </section>
  </div>
</article>
<?php
require_once HTML.'/footer.php';
