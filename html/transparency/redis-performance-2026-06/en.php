<?php
/**
 * Public Transparency: Redis Performance Upgrade for Reports, Calendars, and Weekly Recalc
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
$pageTitle = 'Faster reports, smoother calendars, same privacy-first design - [PayCal]';
$pageLabel = 'Faster reports, smoother calendars, same privacy-first design';
$pageMetaDescription = 'How PayCal made high-volume calendar views, personal reports, business reports, and weekly recalculation much faster without weakening encryption or access control.';
$pageMetaDescriptionLong = 'Performance upgrades now use maintained lookup indexes for high-traffic paths, keeping the user experience responsive as work history grows while preserving PayCal\'s encrypted work model.';
$pageSocialTitle = 'Faster reports, smoother calendars, same privacy-first design';
$pageOgDescription = $pageMetaDescription;
$pageTwitterTitle = $pageSocialTitle;
$pageTwitterDescription = $pageMetaDescription;

require_once HTML . '/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo $pageLabel; ?></span>
  </nav>

  <header class="doc-article-header">
    <h1>Faster reports, smoother calendars, same privacy-first design</h1>
    <p class="deck">PayCal now handles large calendars, personal reports, business reports, and weekly workflows much faster while keeping encrypted work data and access controls intact.</p>
    <p class="doc-article-meta">Published: <time datetime="2026-06-30">2026-06-30</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Why we changed this</h2>
      <p>
        As histories grow, core flows must remain fast even when data grows into tens of thousands of entries.
        We focused on the hot paths users touch most: opening reports, building monthly calendars, and recalculating weekly totals.
      </p>
      <p>
        This was a speed upgrade, not a privacy tradeoff. Encrypted work fields remain protected, and index data stores only lookup metadata needed for fast retrieval.
      </p>
    </section>

    <section class="doc-section">
      <h2>What changed</h2>
      <p>
        Early versions could work by scanning broad key patterns to find the right records. That was acceptable for early delivery,
        but not reliable at production scale in repeated read paths.
      </p>
      <p>
        The upgrade moves those reads to maintained lookup lists and range indexes. In practice, this changes reads from “scan everything and filter” to “read exactly what matches.”
      </p>
    </section>

    <section class="doc-section">
      <h2>Results</h2>
      <table class="doc-table" aria-label="Redis performance benchmark summary">
        <caption>Performance benchmark summary</caption>
        <thead>
          <tr>
            <th scope="col">Area improved</th>
            <th scope="col">Before</th>
            <th scope="col">After</th>
            <th scope="col">What users notice</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Personal reports</td>
            <td>~2.8s</td>
            <td>~22ms</td>
            <td>Reports open much faster</td>
          </tr>
          <tr>
            <td>Business reports</td>
            <td>~2.9s</td>
            <td>~110ms</td>
            <td>Team reports stay practical at scale</td>
          </tr>
          <tr>
            <td>Calendar build</td>
            <td>~4.9s</td>
            <td>~28ms</td>
            <td>Calendar views feel smoother</td>
          </tr>
          <tr>
            <td>Weekly recalculation</td>
            <td>~33.8s</td>
            <td>~75ms</td>
            <td>Save and recalc loop feels immediate</td>
          </tr>
        </tbody>
      </table>
      <p>
        The biggest gain is practical: less waiting in high-frequency tasks.
      </p>
    </section>

    <section class="doc-section">
      <h2>Privacy and security remains unchanged</h2>
      <ul class="doc-fact-list">
        <li>Encrypted work values are still decrypted only as needed and only after access checks.</li>
        <li>Lookup indexes use identifiers and status metadata, not decrypted work payload.</li>
        <li>Compatibility logic remains in place for historical or mixed states while indexes are repaired.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>What we are still watching</h2>
      <ul class="doc-fact-list">
        <li>Production p95 behavior under very large tenant sizes</li>
        <li>Index growth under high write churn</li>
        <li>Controlled rebuild and compatibility correction workflow for legacy states</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Technical notes</h2>
      <p>
        For engineering detail and the full benchmark methodology, we added two scripts:
      </p>
      <ul>
        <li><code>tools/benchmark_reports_compare.php</code> (personal and business report paths)</li>
        <li><code>tools/work_entry_index_benchmark.php</code> (calendar build and weekly recalc)</li>
      </ul>
      <p>
        Example command used in this tuning work:
      </p>
      <pre><code>php tools/benchmark_reports_compare.php --records=25000 --members=100 --year=2026 --runs=5 --warmup-runs=1 --seed=1 --cleanup=1</code></pre>
    </section>

    <section class="doc-section">
      <h2>Related 2026 milestones</h2>
      <ul>
        <li><code>56411342</code> — Resolve Redis remediation audit drift (2026-06-19)</li>
        <li><code>2e4538f2</code> — Redis compatibility audit record (2026-06-19)</li>
        <li><code>b5d6bffb</code> — Work-entry snapshot hardening (2026-06-19)</li>
        <li><code>771d8986</code> — Remove dead Redis compatibility fallbacks (2026-06-19)</li>
        <li><code>cd8d23b8</code> — Harden business relationship indexes (2026-06-18)</li>
        <li><code>f8b7f96b</code> / <code>db9f796d</code> — Business workspace cache and members/reporting consistency work (2026-06-10/11)</li>
        <li><code>3db2229b</code> / <code>2b3eafb8</code> / <code>f197a008</code> — Performance and cache consistency work in June 2026</li>
      </ul>
    </section>
  </div>
</article>
