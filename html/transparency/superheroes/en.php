<?php
/**
 * Public Transparency: Superheroes System Map
 *
 * PURPOSE: Explain the themed "Superheroes" components, why they exist,
 * and where each one is used.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'TRANSPARENCY_SUPERHEROES_PAGE_TITLE',
  'TRANSPARENCY_SUPERHEROES_DECK',
  'TRANSPARENCY_SUPERHEROES_OVERALL_PURPOSE_TITLE',
  'TRANSPARENCY_SUPERHEROES_SYSTEM_BREAKDOWN_TITLE',
  'TRANSPARENCY_SUPERHEROES_WORK_TOGETHER_TITLE',
  'TRANSPARENCY_SUPERHEROES_NAMING_PATTERN_TITLE',
  'TRANSPARENCY_SUPERHEROES_VERIFICATION_ANCHORS_TITLE',
  'TRANSPARENCY_SUPERHEROES_TABLE_ARIA',
  'TRANSPARENCY_SUPERHEROES_TABLE_PRIMARY_ROLE',
  'TRANSPARENCY_SUPERHEROES_TABLE_IMPLEMENTATION',
  'TRANSPARENCY_SUPERHEROES_TABLE_SUPERHERO',
  'TRANSPARENCY_SUPERHEROES_TABLE_USE_CASE',
  'TRANSPARENCY_SUPERHEROES_LAST_UPDATED_LABEL',
  'TRANSPARENCY_SUPERHEROES_MOTTO',
  'TRANSPARENCY_SUPERHEROES_OVERALL_PURPOSE_TEXT_1',
  'TRANSPARENCY_SUPERHEROES_OVERALL_PURPOSE_ITEM_1',
  'TRANSPARENCY_SUPERHEROES_OVERALL_PURPOSE_ITEM_2',
  'TRANSPARENCY_SUPERHEROES_OVERALL_PURPOSE_ITEM_3',
  'TRANSPARENCY_SUPERHEROES_SHADOWTALON_ROLE',
  'TRANSPARENCY_SUPERHEROES_SHADOWTALON_USE_CASE',
  'TRANSPARENCY_SUPERHEROES_GUARDIAN_ROLE',
  'TRANSPARENCY_SUPERHEROES_GUARDIAN_USE_CASE',
  'TRANSPARENCY_SUPERHEROES_PHANTOMWING_ROLE',
  'TRANSPARENCY_SUPERHEROES_PHANTOMWING_USE_CASE',
  'TRANSPARENCY_SUPERHEROES_LENS_ROLE',
  'TRANSPARENCY_SUPERHEROES_EMAILGARUM_ROLE',
  'TRANSPARENCY_SUPERHEROES_EMAILGARUM_USE_CASE',
  'TRANSPARENCY_SUPERHEROES_ECHO_ROLE',
  'TRANSPARENCY_SUPERHEROES_ECHO_USE_CASE',
  'TRANSPARENCY_SUPERHEROES_WORK_TOGETHER_ITEM_1_TEXT',
  'TRANSPARENCY_SUPERHEROES_WORK_TOGETHER_ITEM_2_TEXT',
  'TRANSPARENCY_SUPERHEROES_WORK_TOGETHER_ITEM_3_TEXT',
  'TRANSPARENCY_SUPERHEROES_WORK_TOGETHER_ITEM_4_TEXT',
  'TRANSPARENCY_SUPERHEROES_NAMING_PATTERN_TEXT_1',
  'TRANSPARENCY_SUPERHEROES_NAMING_PATTERN_ITEM_1',
  'TRANSPARENCY_SUPERHEROES_NAMING_PATTERN_ITEM_2',
  'TRANSPARENCY_SUPERHEROES_NAMING_PATTERN_ITEM_3',
  'TRANSPARENCY_SUPERHEROES_VERIFICATION_ITEM_3',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_SUPERHEROES_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_SUPERHEROES_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo $i18n['TRANSPARENCY_SUPERHEROES_PAGE_TITLE']; ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo $i18n['TRANSPARENCY_SUPERHEROES_PAGE_TITLE']; ?></h1>
    <p class="deck"><?php echo $i18n['TRANSPARENCY_SUPERHEROES_DECK']; ?></p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-02">2026-04-02</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_SUPERHEROES_OVERALL_PURPOSE_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_SUPERHEROES_OVERALL_PURPOSE_TEXT_1']; ?></p>
      <ul class="doc-fact-list">
        <li><?php echo $i18n['TRANSPARENCY_SUPERHEROES_OVERALL_PURPOSE_ITEM_1']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_SUPERHEROES_OVERALL_PURPOSE_ITEM_2']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_SUPERHEROES_OVERALL_PURPOSE_ITEM_3']; ?></li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_SUPERHEROES_SYSTEM_BREAKDOWN_TITLE']; ?></h2>
      <table class="doc-table" aria-label="<?php echo $i18n['TRANSPARENCY_SUPERHEROES_TABLE_ARIA']; ?>">
        <thead>
          <tr>
            <th><?php echo $i18n['TRANSPARENCY_SUPERHEROES_TABLE_SUPERHERO']; ?></th>
            <th><?php echo $i18n['TRANSPARENCY_SUPERHEROES_TABLE_PRIMARY_ROLE']; ?></th>
            <th><?php echo $i18n['TRANSPARENCY_SUPERHEROES_TABLE_USE_CASE']; ?></th>
            <th><?php echo $i18n['TRANSPARENCY_SUPERHEROES_TABLE_IMPLEMENTATION']; ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>ShadowTalon</td>
            <td><?php echo $i18n['TRANSPARENCY_SUPERHEROES_SHADOWTALON_ROLE']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_SUPERHEROES_SHADOWTALON_USE_CASE']; ?></td>
            <td><code>html/src/Domain/ShadowTalon.php</code></td>
          </tr>
          <tr>
            <td>Guardian</td>
            <td><?php echo $i18n['TRANSPARENCY_SUPERHEROES_GUARDIAN_ROLE']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_SUPERHEROES_GUARDIAN_USE_CASE']; ?></td>
            <td><code>html/js/guardian.js</code></td>
          </tr>
          <tr>
            <td>Phantom Wing</td>
            <td><?php echo $i18n['TRANSPARENCY_SUPERHEROES_PHANTOMWING_ROLE']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_SUPERHEROES_PHANTOMWING_USE_CASE']; ?></td>
            <td><code>html/js/phantomwing/index.php</code></td>
          </tr>
          <tr>
            <td>Lens</td>
            <td><?php echo $i18n['TRANSPARENCY_SUPERHEROES_LENS_ROLE']; ?></td>
            <td>Collects DEV-only events, counters, and timers; supports debug panels and API payload diagnostics via <code>?lens=1</code>.</td>
            <td><code>html/src/Observability/Lens.php</code></td>
          </tr>
          <tr>
            <td>EmailGarum</td>
            <td><?php echo $i18n['TRANSPARENCY_SUPERHEROES_EMAILGARUM_ROLE']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_SUPERHEROES_EMAILGARUM_USE_CASE']; ?></td>
            <td><code>html/src/Domain/EmailGarum.php</code></td>
          </tr>
          <tr>
            <td>Echo</td>
            <td><?php echo $i18n['TRANSPARENCY_SUPERHEROES_ECHO_ROLE']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_SUPERHEROES_ECHO_USE_CASE']; ?></td>
            <td><code>html/src/Domain/AriaEcho.php</code></td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_SUPERHEROES_WORK_TOGETHER_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><strong>Runtime fault path:</strong> <?php echo $i18n['TRANSPARENCY_SUPERHEROES_WORK_TOGETHER_ITEM_1_TEXT']; ?></li>
        <li><strong>Browser diagnostics path:</strong> <?php echo $i18n['TRANSPARENCY_SUPERHEROES_WORK_TOGETHER_ITEM_2_TEXT']; ?></li>
        <li><strong>Account communication path:</strong> <?php echo $i18n['TRANSPARENCY_SUPERHEROES_WORK_TOGETHER_ITEM_3_TEXT']; ?></li>
        <li><strong>Assistive narration path:</strong> <?php echo $i18n['TRANSPARENCY_SUPERHEROES_WORK_TOGETHER_ITEM_4_TEXT']; ?></li>
      </ul>
      <p><em><?php echo $i18n['TRANSPARENCY_SUPERHEROES_MOTTO']; ?></em></p>
    </section>

    <section class="doc-section success">
      <h2><?php echo $i18n['TRANSPARENCY_SUPERHEROES_NAMING_PATTERN_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_SUPERHEROES_NAMING_PATTERN_TEXT_1']; ?></p>
      <ul class="doc-fact-list">
        <li><?php echo $i18n['TRANSPARENCY_SUPERHEROES_NAMING_PATTERN_ITEM_1']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_SUPERHEROES_NAMING_PATTERN_ITEM_2']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_SUPERHEROES_NAMING_PATTERN_ITEM_3']; ?></li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_SUPERHEROES_VERIFICATION_ANCHORS_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><code>html/tests/Unit/ShadowTalonTest.php</code> verifies fault response safety and route-shape behavior.</li>
        <li><code>html/tests/Unit/EmailTemplateRenderTest.php</code> verifies transactional template rendering across email flows.</li>
        <li><?php echo $i18n['TRANSPARENCY_SUPERHEROES_VERIFICATION_ITEM_3']; ?></li>
      </ul>
      <p><strong><?php echo $i18n['TRANSPARENCY_SUPERHEROES_LAST_UPDATED_LABEL']; ?></strong> March 21, 2026.</p>
    </section>
  </div>
</article>
<?php
require_once HTML.'/footer.php';
