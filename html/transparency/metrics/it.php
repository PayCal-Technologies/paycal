<?php
/**
 * Public Transparency: Platform Metrics
 *
 * PURPOSE: Full disclosure of aggregate platform health metrics collected by PayCal.
 * AUDIENCE: Public (no authentication required).
 * POLICY: No personal data in telemetry; aggregate-only, bucketed distributions.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'TRANSPARENCY_METRICS_PAGE_TITLE',
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'ADMIN_DASHBOARD_PLATFORM_METRICS_TITLE',
  'TRANSPARENCY_METRICS_DECK',
  'TRANSPARENCY_METRICS_COMMITMENT_TITLE',
  'TRANSPARENCY_METRICS_COMMITMENT_TEXT_1',
  'TRANSPARENCY_METRICS_COMMITMENT_TEXT_2',
  'TRANSPARENCY_METRICS_VERIFICATION_METADATA_TITLE',
  'TRANSPARENCY_METRICS_ROUTE_METADATA_TITLE',
  'TRANSPARENCY_METRICS_KNOWN_LIMITATIONS_TITLE',
  'TRANSPARENCY_METRICS_METADATA_UPDATED_NOTE',
  'TRANSPARENCY_METRICS_TABLE_CATEGORY',
  'TRANSPARENCY_METRICS_TABLE_PERSONAL_DATA',
  'TRANSPARENCY_METRICS_TABLE_RETENTION',
  'TRANSPARENCY_METRICS_TABLE_PURPOSE',
  'TRANSPARENCY_METRICS_YES',
  'TRANSPARENCY_METRICS_NO',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_METRICS_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_METRICS_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
    <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
      <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
      <span class="separator">/</span>
      <span class="current"><?php echo $i18n['ADMIN_DASHBOARD_PLATFORM_METRICS_TITLE']; ?></span>
    </nav>
    <header class="doc-article-header">
      <h1><?php echo $i18n['TRANSPARENCY_METRICS_PAGE_TITLE']; ?></h1>
      <p class="deck"><?php echo $i18n['TRANSPARENCY_METRICS_DECK']; ?></p>
<p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
    </header>

    <div class="doc-article-body">
      <section class="doc-section highlight">
        <h3><?php echo $i18n['TRANSPARENCY_METRICS_COMMITMENT_TITLE']; ?></h3>
        <p><?php echo $i18n['TRANSPARENCY_METRICS_COMMITMENT_TEXT_1']; ?></p>
        <p><?php echo $i18n['TRANSPARENCY_METRICS_COMMITMENT_TEXT_2']; ?></p>
      </section>

      <section class="doc-section highlight">
        <h2><?php echo $i18n['TRANSPARENCY_METRICS_VERIFICATION_METADATA_TITLE']; ?></h2>
        <div class="doc-two-column">
          <div>
            <h3><?php echo $i18n['TRANSPARENCY_METRICS_ROUTE_METADATA_TITLE']; ?></h3>
            <ul class="doc-fact-list">
              <li><strong>Route:</strong> <code>/transparency/metrics/</code></li>
              <li><strong>Last verified:</strong> <time datetime="2026-03-23">2026-03-23</time></li>
              <li><strong>Next review due:</strong> <time datetime="2026-06-23">2026-06-23</time></li>
              <li><strong>Verification scope:</strong> manual content review against current metric key inventory and retention policy.</li>
            </ul>
          </div>
          <div>
            <h3><?php echo $i18n['TRANSPARENCY_METRICS_KNOWN_LIMITATIONS_TITLE']; ?></h3>
            <ul class="doc-fact-list">
              <li>Metric key inventory is manually kept in sync with code; automated key-diff tooling is planned for quarterly reviews.</li>
              <li>Retention values reflect current configuration defaults and may not reflect tenant-specific overrides if introduced in future releases.</li>
            </ul>
          </div>
        </div>
        <p><?php echo $i18n['TRANSPARENCY_METRICS_METADATA_UPDATED_NOTE']; ?></p>
      </section>
          <thead>
            <tr>
              <th><?php echo $i18n['TRANSPARENCY_METRICS_TABLE_CATEGORY']; ?></th>
              <th><?php echo $i18n['TRANSPARENCY_METRICS_TABLE_PERSONAL_DATA']; ?></th>
              <th><?php echo $i18n['TRANSPARENCY_METRICS_TABLE_RETENTION']; ?></th>
              <th><?php echo $i18n['TRANSPARENCY_METRICS_TABLE_PURPOSE']; ?></th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Session lifecycle metrics</td>
              <td><?php echo $i18n['TRANSPARENCY_METRICS_NO']; ?></td>
              <td>30 days raw, 52 weeks rollup, 24 months monthly</td>
              <td>User experience and capacity planning</td>
            </tr>
            <tr>
              <td>Redis health metrics</td>
              <td><?php echo $i18n['TRANSPARENCY_METRICS_NO']; ?></td>
              <td>24 hours raw, 7 days hourly, 4 weeks daily</td>
              <td>Infrastructure reliability</td>
            </tr>
            <tr>
              <td>Business aggregates</td>
              <td><?php echo $i18n['TRANSPARENCY_METRICS_NO']; ?></td>
              <td>30 days daily, 52 weeks weekly, 24 months monthly</td>
              <td>Growth and planning</td>
            </tr>
            <tr>
              <td>Frontend telemetry events</td>
              <td><?php echo $i18n['TRANSPARENCY_METRICS_NO']; ?></td>
              <td>30 days</td>
              <td>Error detection and feature health</td>
            </tr>
            <tr>
              <td>Encryption operation metrics</td>
              <td><?php echo $i18n['TRANSPARENCY_METRICS_NO']; ?></td>
              <td>30 days raw, 52 weeks rollup, 24 months monthly</td>
              <td>Cryptographic reliability</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="doc-section">
        <h2>Session Lifecycle Metrics <span class="doc-badge info">No Personal Data</span></h2>
        <p>We count login/logout totals and session duration ranges to understand usage patterns. We do not track who logged in.</p>
        <p><strong>What:</strong> Daily login events, logout events, and session duration distributions.</p>
        <p><strong>Why:</strong> Detect authentication issues, reduce friction, and improve capacity planning.</p>
        <p><strong>How:</strong> Daily counters and fixed duration buckets.</p>
        <p><strong>Buckets:</strong></p>
        <ul>
          <li><code>0-5min</code> - Quick checks</li>
          <li><code>5-30min</code> - Typical session</li>
          <li><code>30-60min</code> - Extended session</li>
          <li><code>60min+</code> - Long work session</li>
        </ul>
        <p><strong>Retention:</strong> 30 days raw -> 52 weeks rollup -> 24 months monthly -> purge.</p>
        <p><strong>Example Keys:</strong></p>
        <pre class="doc-code">telemetry:auth:login:2026-03-09      -> 247
telemetry:auth:logout:2026-03-09     -> 219
telemetry:session:duration:0-5min    -> 45
telemetry:session:duration:5-30min   -> 128
telemetry:session:duration:30-60min  -> 39
telemetry:session:duration:60min+    -> 7</pre>
        <p><strong>Privacy Guard:</strong> Session hash is destroyed immediately after duration calculation. No user UUIDs are stored in telemetry keys.</p>
        <p><strong>Volume Cap:</strong> Maximum 734 keys/year.</p>
      </section>

      <section class="doc-section">
        <h2>Redis Health Metrics <span class="doc-badge info">No Personal Data</span></h2>
        <p>We monitor Redis as infrastructure health data, not user activity data.</p>
        <p><strong>What:</strong> Memory usage, key counts by namespace, and connection stats.</p>
        <p><strong>Why:</strong> Detect memory leaks, prevent evictions, and watch growth.</p>
        <p><strong>How:</strong> Parse Redis INFO output on a scheduled interval.</p>
        <p><strong>Namespaces:</strong> Max 10 tracked namespaces (hardcoded whitelist).</p>
        <ul>
          <li><code>session:*</code> - Active sessions</li>
          <li><code>lock:*</code> - Distributed locks</li>
          <li><code>cache:*</code> - Application cache</li>
          <li><code>telemetry:*</code> - Metrics storage</li>
          <li><code>ratelimit:*</code> - Rate limiting counters</li>
          <li><code>nonce:*</code> - CSRF tokens</li>
          <li><code>temp:*</code> - Temporary data</li>
          <li><code>queue:*</code> - Job queues</li>
          <li><code>encryption:*</code> - Wrapped keys</li>
          <li><code>feature:*</code> - Feature flags</li>
        </ul>
        <p><strong>Retention:</strong> 24 hours raw -> 7 days hourly -> 4 weeks daily -> purge.</p>
        <p><strong>Example Keys:</strong></p>
        <pre class="doc-code">telemetry:redis:memory:used_mb:2026-03-09:14  -> 247
telemetry:redis:keys:session:2026-03-09:14    -> 342
telemetry:redis:keys:lock:2026-03-09:14       -> 18</pre>
        <p><strong>Privacy Guard:</strong> Namespace counts are aggregated only. Key content is not inspected.</p>
        <p><strong>Volume Cap:</strong> 1,680 keys maximum in the active rolling window.</p>
      </section>

      <section class="doc-section">
        <h2>Business Aggregate Metrics <span class="doc-badge info">No Personal Data</span></h2>
        <p>These are top-level platform totals used for planning, not profiling individuals.</p>
        <p><strong>What:</strong> Total users, active accounts, average work entries.</p>
        <p><strong>Why:</strong> Capacity planning and growth analysis.</p>
        <p><strong>How:</strong> Daily aggregation of database counts.</p>
        <p><strong>Retention:</strong> 30 days daily -> 52 weeks weekly -> 24 months monthly -> purge.</p>
        <p><strong>Example Keys:</strong></p>
        <pre class="doc-code">telemetry:business:users:total:2026-03-09     -> 1247
telemetry:business:users:active:2026-03-09    -> 892
telemetry:business:work:avg_per_user:2026-03  -> 23.4</pre>
        <p><strong>Privacy Guard:</strong> Aggregate-only values. No per-user telemetry records.</p>
        <p><strong>Volume Cap:</strong> 1,095 keys/year.</p>
      </section>

      <section class="doc-section">
        <h2>Frontend Telemetry Events <span class="doc-badge info">No Personal Data</span></h2>
        <p>Event telemetry helps detect client-side failures and feature reliability issues.</p>
        <p><strong>What:</strong> Frontend performance events, error counts, and feature usage events.</p>
        <p><strong>Why:</strong> Identify client issues and monitor product health.</p>
        <p><strong>How:</strong> POST to <code>/api/telemetry/record</code> for approved event types only.</p>
        <p><strong>Telemetry submission limits:</strong></p>
        <ul>
          <li>90 events/minute per client (abuse prevention)</li>
        </ul>
        <p><strong>Retention:</strong> 30 days (TTL enforced on increment).</p>
        <p><strong>Example Event Types:</strong></p>
        <ul>
          <li><code>calendar.load.success</code></li>
          <li><code>calendar.load.failure</code></li>
          <li><code>encryption.dek.unwrap.success</code></li>
          <li><code>encryption.dek.unwrap.failure</code></li>
          <li><code>passkey.login.success</code></li>
          <li><code>passkey.login.failure</code></li>
        </ul>
        <p><strong>Example Keys:</strong></p>
        <pre class="doc-code">telemetry:event:calendar.load.success:2026-03-09    -> 3421
telemetry:event:passkey.login.failure:2026-03-09    -> 17</pre>
        <p><strong>Privacy Guard:</strong> Event types are allowlisted. No arbitrary strings and no user/session identifiers in telemetry keys.</p>
        <p><strong>Volume Cap:</strong> 18,250 keys/year maximum.</p>
      </section>

      <section class="doc-section">
        <h2>Encryption Operation Metrics <span class="doc-badge info">No Personal Data</span></h2>
        <p>Cryptographic operations are monitored as platform reliability signals.</p>
        <p><strong>What:</strong> DEK wrap/unwrap success and failure counters.</p>
        <p><strong>Why:</strong> Detect cryptographic failures and misconfiguration quickly.</p>
        <p><strong>How:</strong> Success/failure counters increment for each operation outcome.</p>
        <p><strong>Retention:</strong> 30 days raw -> 52 weeks rollup -> 24 months monthly -> purge.</p>
        <p><strong>Example Keys:</strong></p>
        <pre class="doc-code">telemetry:encryption:dek:wrap:success:2026-03-09      -> 1203
telemetry:encryption:dek:wrap:failure:2026-03-09      -> 2
telemetry:encryption:dek:unwrap:success:2026-03-09    -> 5847
telemetry:encryption:dek:unwrap:failure:2026-03-09    -> 31</pre>
        <p><strong>Privacy Guard:</strong> Only operation counts are recorded. No key material, ciphertext, or personal identifiers are stored.</p>
        <p><strong>Volume Cap:</strong> 1,460 keys/year.</p>
      </section>

      <section class="doc-section">
        <h2>Retention and Compaction Pipeline</h2>
        <p><strong>Raw data:</strong> Daily counters expire automatically after 30 days.</p>
        <p><strong>Weekly rollups:</strong> Scheduled aggregation to 52-week retention.</p>
        <p><strong>Monthly rollups:</strong> Scheduled aggregation to 24-month retention.</p>
        <p><strong>Purge:</strong> Metrics older than 24 months are deleted.</p>
        <p><strong>Compaction script:</strong> <code>/scripts/compact-metrics.php</code>.</p>
      </section>

      <section class="doc-section success">
        <h2>Enforcement in Code</h2>
        <p>Privacy constraints are validated by contract tests in CI.</p>
        <pre class="doc-code">MetricsPrivacyContractTest::testSessionDurationHasExactlyFourBuckets()
MetricsPrivacyContractTest::testNoUserUUIDsInTelemetryKeys()
MetricsPrivacyContractTest::testRedisNamespacesNeverExceedTen()
MetricsPrivacyContractTest::testAllTelemetryKeysHaveTTL()</pre>
        <p><strong>Guardrails:</strong> Hardcoded namespace/event limits prevent unbounded metric growth.</p>
        <p><strong>Additional rate limits:</strong></p>
        <ul>
          <li>Admin metrics queries: 100 requests/hour</li>
          <li>Public health checks: 600 requests/hour</li>
        </ul>
      </section>

      <section class="doc-section highlight">
        <h3>Access and Verification</h3>
        <p><strong>Metrics Dashboard:</strong> <code>/admin/metrics</code> (authentication and admin role required).</p>
        <p><strong>Public Health Endpoint:</strong> <code>/api/v1/health</code> returns aggregate platform status.</p>
        <p><strong>Last Updated:</strong> March 9, 2026.</p>
      </section>
    </div>
  </article>
<?php
require_once HTML.'/footer.php';
