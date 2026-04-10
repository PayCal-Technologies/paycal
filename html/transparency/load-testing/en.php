<?php
/**
 * Public Transparency: Earnings Load Testing
 *
 * PURPOSE: Publish repeatable load/performance benchmark outcomes for
 *          eager vs lazy earnings rendering.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'TRANSPARENCY_LOAD_TESTING_PAGE_TITLE',
  'TRANSPARENCY_LOAD_TESTING_DECK',
  'TRANSPARENCY_LOAD_TESTING_VERIFICATION_METADATA_TITLE',
  'TRANSPARENCY_LOAD_TESTING_EXECUTIVE_SUMMARY_TITLE',
  'TRANSPARENCY_LOAD_TESTING_BENCHMARK_METHOD_TITLE',
  'TRANSPARENCY_LOAD_TESTING_RESULTS_REAL_TITLE',
  'TRANSPARENCY_LOAD_TESTING_RESULTS_SYNTHETIC_TITLE',
  'TRANSPARENCY_LOAD_TESTING_INTERPRETATION_TITLE',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_LOAD_TESTING_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_LOAD_TESTING_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo $i18n['TRANSPARENCY_LOAD_TESTING_PAGE_TITLE']; ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo $i18n['TRANSPARENCY_LOAD_TESTING_PAGE_TITLE']; ?></h1>
    <p class="deck"><?php echo $i18n['TRANSPARENCY_LOAD_TESTING_DECK']; ?></p>
    <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_LOAD_TESTING_VERIFICATION_METADATA_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><strong>Route:</strong> <code>/transparency/load-testing/</code></li>
        <li><strong>Last verified:</strong> <time datetime="2026-03-29">2026-03-29</time></li>
        <li><strong>Benchmark matrix:</strong> 4 scenarios, 10 runs each (40 total page runs).</li>
        <li><strong>Modes compared:</strong> <code>earnings_mode=eager</code> vs <code>earnings_mode=lazy</code>.</li>
        <li><strong>Profiles:</strong> Real user dataset and synthetic 2025/2026 high-volume profile.</li>
      </ul>
    </section>

    <section class="doc-section success">
      <h2><?php echo $i18n['TRANSPARENCY_LOAD_TESTING_EXECUTIVE_SUMMARY_TITLE']; ?></h2>
      <p>Lazy loading delivered a major reduction in initial page completion time for real data and a consistent improvement for synthetic high-volume data.</p>
      <ul class="doc-fact-list">
        <li><strong>Real dataset:</strong> average <code>DOMContentLoaded</code> improved from <strong>8831.64 ms</strong> (eager) to <strong>977.43 ms</strong> (lazy), an <strong>88.93%</strong> reduction.</li>
        <li><strong>Synthetic dataset:</strong> average <code>DOMContentLoaded</code> improved from <strong>629.25 ms</strong> (eager) to <strong>500.46 ms</strong> (lazy), a <strong>20.47%</strong> reduction.</li>
        <li><strong>Section-ready timing:</strong> lazy reduced YTD / Pay Period / Monthly / Daily section readiness by <strong>28.32%</strong> (real) and <strong>18.30%</strong> (synthetic).</li>
        <li><strong>Trade-off:</strong> lazy mode increased API call count (real: 2.10 to 8.00; synthetic: 5.00 to 8.00 average calls per run).</li>
        <li><strong>FCP observation:</strong> first-contentful-paint was slightly slower in lazy mode (+22.56% real, +8.16% synthetic), but total interactive section readiness was faster.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_LOAD_TESTING_BENCHMARK_METHOD_TITLE']; ?></h2>
      <div class="subject-example-cutout" role="note" aria-label="Performance operations use case">
        <h3>Use Case: Detecting Real-World Slowdowns</h3>
        <p>When users report a slower earnings page after a feature rollout, this benchmark method provides a reproducible way to compare eager and lazy behavior across identical datasets before and after the change.</p>
      </div>
      <ul class="doc-fact-list">
        <li>Automation used Playwright Chromium in headless mode against <code>https://dev.paycal.local</code>.</li>
        <li>Authentication used local dev bypass login to ensure realistic user/session execution paths.</li>
        <li>Each run captured navigation timing, paint timing, per-section ready timing, and API request count.</li>
        <li>Metrics are averaged over 10 runs per scenario to reduce single-run noise.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_LOAD_TESTING_RESULTS_REAL_TITLE']; ?></h2>
      <table class="doc-table" aria-label="Real data benchmark results">
        <thead>
          <tr>
            <th scope="col">Metric</th>
            <th scope="col">Eager Avg (ms)</th>
            <th scope="col">Lazy Avg (ms)</th>
            <th scope="col">Delta</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>DOMContentLoaded</td><td>8831.64</td><td>977.43</td><td>-88.93%</td></tr>
          <tr><td>Load Event</td><td>8831.94</td><td>977.83</td><td>-88.93%</td></tr>
          <tr><td>FCP</td><td>594.00</td><td>728.00</td><td>+22.56%</td></tr>
          <tr><td>YTD Ready</td><td>9033.02</td><td>6475.12</td><td>-28.32%</td></tr>
          <tr><td>Pay Periods Ready</td><td>9033.02</td><td>6475.13</td><td>-28.32%</td></tr>
          <tr><td>Monthly Ready</td><td>9033.05</td><td>6475.15</td><td>-28.32%</td></tr>
          <tr><td>Daily Ready</td><td>9033.11</td><td>6475.29</td><td>-28.32%</td></tr>
          <tr><td>API Calls (count)</td><td>2.10</td><td>8.00</td><td>+280.95%</td></tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_LOAD_TESTING_RESULTS_SYNTHETIC_TITLE']; ?></h2>
      <table class="doc-table" aria-label="Synthetic data benchmark results">
        <thead>
          <tr>
            <th scope="col">Metric</th>
            <th scope="col">Eager Avg (ms)</th>
            <th scope="col">Lazy Avg (ms)</th>
            <th scope="col">Delta</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>DOMContentLoaded</td><td>629.25</td><td>500.46</td><td>-20.47%</td></tr>
          <tr><td>Load Event</td><td>629.54</td><td>500.69</td><td>-20.47%</td></tr>
          <tr><td>FCP</td><td>377.60</td><td>408.40</td><td>+8.16%</td></tr>
          <tr><td>YTD Ready</td><td>831.05</td><td>678.95</td><td>-18.30%</td></tr>
          <tr><td>Pay Periods Ready</td><td>831.05</td><td>678.97</td><td>-18.30%</td></tr>
          <tr><td>Monthly Ready</td><td>831.07</td><td>678.99</td><td>-18.30%</td></tr>
          <tr><td>Daily Ready</td><td>831.07</td><td>679.00</td><td>-18.30%</td></tr>
          <tr><td>API Calls (count)</td><td>5.00</td><td>8.00</td><td>+60.00%</td></tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_LOAD_TESTING_INTERPRETATION_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li>Lazy loading significantly improves initial route completion and section readiness under both tested profiles.</li>
        <li>The largest gain appears when user datasets are heavy enough to make server-side eager rendering expensive.</li>
        <li>The architecture shifts work from one large payload to multiple targeted API calls, improving perceived performance but increasing request count.</li>
        <li>This trade-off is intentional and currently favorable for user-perceived responsiveness on <code>/earnings/</code>.</li>
      </ul>
    </section>

  </div>
</article>
<?php
require_once HTML.'/footer.php';
