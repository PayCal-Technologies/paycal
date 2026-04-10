<?php declare(strict_types=1);

use PayCal\Controllers\HealthController;
use PayCal\Domain\Authentication;
use PayCal\Domain\AdminSurface;
use PayCal\Domain\Render;
use PayCal\Domain\Strings;

require_once '../../config.php';

if (function_exists('metrics_index_i18n') === false) {
  function metrics_index_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}
require_once __DIR__ . '/../../src/Controllers/HealthController.php';

// Admin-only access
Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/metrics/');

// Get metrics snapshot
$metrics = HealthController::getHealthSnapshot();
$isError = isset($metrics['error']);
// Type-safe helper functions for PHPStan Level 9 compliance
/**
 * @param array<string, mixed> $arr
 */
function getStringValue(array $arr, string $key, string $default = ''): string {
  return isset($arr[$key]) && is_string($arr[$key]) ? $arr[$key] : $default;
}
/**
 * @param array<string, mixed> $arr
 */
function getIntValue(array $arr, string $key, int $default = 0): int {
  return isset($arr[$key]) && is_int($arr[$key]) ? $arr[$key] : $default;
}
/**
 * @param array<string, mixed> $arr
 */
function getFloatValue(array $arr, string $key, float $default = 0.0): float {
  $val = $arr[$key] ?? $default;
  return is_numeric($val) ? (float)$val : $default;
}
/**
 * @param array<string, mixed> $arr
 * @return array<string, mixed>
*/
function getArrayValue(array $arr, string $key): array {
  return isset($arr[$key]) && is_array($arr[$key]) ? $arr[$key] : [];
}

// Extract sub-arrays for type-safe access
$redis = getArrayValue($metrics, 'redis');
$sessions = getArrayValue($metrics, 'sessions');
$business = getArrayValue($metrics, 'business');
$keys = getArrayValue($metrics, 'keys');
$telemetry = getArrayValue($metrics, 'telemetry');
$billingWebhooks = getArrayValue($metrics, 'billing_webhooks');
$scraperDefense = getArrayValue($metrics, 'scraper_defense');
$contact = getArrayValue($metrics, 'contact');

$pageTitle = metrics_index_i18n('ADMIN_METRICS_DASHBOARD_TITLE') . ' - [PayCal]';
$metaDescription = metrics_index_i18n('ADMIN_METRICS_DASHBOARD_META_DESCRIPTION');

// Cache buster using current date (changes daily for better cache behavior)
$cacheBuster = date('Ymd');
$cspNonce = $_SERVER['CSP_NONCE'] ?? '';

ob_start();
?>
<link rel="stylesheet" href="/css/admin/metrics.css?v=<?= htmlspecialchars($cacheBuster) ?>" nonce="<?= htmlspecialchars($cspNonce) ?>">
<div class="metrics-dashboard">
  <div class="dashboard-header">
    <div>
      <h1>📊 Platform Metrics Dashboard</h1>
      <p><?php echo metrics_index_i18n('ADMIN_METRICS_REALTIME_HEALTH_ANALYTICS'); ?></p>
    </div>
    <div class="status-section">
      <?php if (!$isError): ?>
        <span class="status-badge healthy">● HEALTHY</span>
        <div class="timestamp"><?php echo metrics_index_i18n('ADMIN_METRICS_LAST_UPDATED'); ?>: <?= htmlspecialchars(getStringValue($metrics, 'timestamp', date('Y-m-d H:i:s'))) ?></div>
      <?php else: ?>
        <span class="status-badge error">● ERROR</span>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($isError): ?>
    <div class="error-message">
      <strong><?php echo metrics_index_i18n('ERROR'); ?>:</strong> <?= htmlspecialchars(getStringValue($metrics, 'error', metrics_index_i18n('ADMIN_METRICS_UNKNOWN_ERROR'))) ?>
    </div>
  <?php else: ?>

    <!-- REDIS HEALTH -->
    <div class="metrics-grid">
      <div class="metric-card">
        <h2>🔴 <?php echo metrics_index_i18n('ADMIN_METRICS_REDIS_SERVER'); ?></h2>
        <div class="metric-row">
          <span class="metric-label">Memory Used</span>
          <span class="metric-value"><?= getFloatValue($redis, 'used_memory_mb', 0.0) ?> MB</span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Peak Memory</span>
          <span class="metric-value"><?= getFloatValue($redis, 'used_memory_peak_mb', 0.0) ?> MB</span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Connected Clients</span>
          <span class="metric-value"><?= getIntValue($redis, 'connected_clients', 0) ?></span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Cache Hit Rate</span>
          <span class="metric-value success"><?= getFloatValue($redis, 'hit_rate_percent', 0.0) ?>%</span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Uptime</span>
          <span class="metric-value"><?= getIntValue($redis, 'uptime_in_days', 0) ?> days</span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Version</span>
          <span class="metric-value"><?= getStringValue($redis, 'redis_version', 'unknown') ?></span>
        </div>
      </div>

      <!-- SESSION METRICS -->
      <div class="metric-card">
        <h2>🔐 <?php echo metrics_index_i18n('SESSIONS'); ?></h2>
        <div class="metric-row">
          <span class="metric-label">Active Sessions</span>
          <span class="metric-value success"><?= getIntValue($sessions, 'active_sessions', 0) ?></span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Logins Today</span>
          <span class="metric-value"><?= getIntValue($sessions, 'logins_today', 0) ?></span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Logouts Today</span>
          <span class="metric-value"><?= getIntValue($sessions, 'logouts_today', 0) ?></span>
        </div>
        
        <div class="duration-bars">
          <h3><?php echo metrics_index_i18n('ADMIN_METRICS_SESSION_DURATION_DISTRIBUTION'); ?></h3>
          <?php
          $durationData = [
            '0-5min' => getIntValue($sessions, 'duration_0_5min', 0),
            '5-30min' => getIntValue($sessions, 'duration_5_30min', 0),
            '30-60min' => getIntValue($sessions, 'duration_30_60min', 0),
            '60min+' => getIntValue($sessions, 'duration_60min_plus', 0),
          ];
          $maxDuration = max($durationData) ?: 1;
          
          // Use 5% buckets to keep width styling in CSS (no inline style writes)
          foreach ($durationData as $bucket => $count):
          ?>
            <?php
            $rawPercentage = ($count / $maxDuration) * 100;
            $percentage = (int) (round($rawPercentage / 5) * 5);
            $percentage = max(0, min(100, $percentage));
            ?>
            <div class="duration-bar">
              <div class="duration-bar-label">
                <span><?= htmlspecialchars((string)$bucket) ?></span>
                <span><?= htmlspecialchars((string)$count) ?> <?php echo metrics_index_i18n('SESSIONS'); ?></span>
              </div>
              <div class="duration-bar-track">
                <div class="duration-bar-fill" data-width="<?= $percentage ?>"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- BUSINESS METRICS -->
      <div class="metric-card">
        <h2>📈 <?php echo metrics_index_i18n('ADMIN_DASHBOARD_PLATFORM_METRICS_TITLE'); ?></h2>
        <div class="metric-row">
          <span class="metric-label">Total Users</span>
          <span class="metric-value success"><?= number_format(getIntValue($business, 'total_users', 0)) ?></span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Total Work Entries</span>
          <span class="metric-value"><?= number_format(getIntValue($business, 'total_work_entries', 0)) ?></span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Avg Entries/User</span>
          <span class="metric-value"><?= getFloatValue($business, 'avg_work_entries_per_user', 0.0) ?></span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Median Entries/User</span>
          <span class="metric-value"><?= getFloatValue($business, 'median_work_entries', 0.0) ?></span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Total Sites</span>
          <span class="metric-value"><?= number_format(getIntValue($business, 'total_sites', 0)) ?></span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Active Sites</span>
          <span class="metric-value success"><?= number_format(getIntValue($business, 'active_sites', 0)) ?></span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Archived Sites</span>
          <span class="metric-value"><?= number_format(getIntValue($business, 'archived_sites', 0)) ?></span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Max Entries (Single User)</span>
          <span class="metric-value"><?= number_format(getIntValue($business, 'max_work_entries_single_user', 0)) ?></span>
        </div>
      </div>
    </div>

    <!-- KEY DISTRIBUTION -->
    <div class="metric-card">
      <h2>🔑 <?php echo metrics_index_i18n('ADMIN_METRICS_DATA_DISTRIBUTION'); ?></h2>
      <div class="metrics-grid">
        <?php foreach ($keys ?? [] as $namespace => $count): ?>
          <div class="metric-row">
            <span class="metric-label"><?= htmlspecialchars((string)$namespace) ?>:*</span>
            <span class="metric-value"><?= number_format((float)$count) ?> keys</span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- TELEMETRY EVENTS -->
    <?php if (!empty($telemetry)): ?>
      <div class="metric-card">
        <h2>📡 <?php echo metrics_index_i18n('ADMIN_METRICS_TELEMETRY_EVENTS_TODAY'); ?></h2>
        <div class="metrics-grid">
          <?php foreach ($telemetry as $eventType => $count): ?>
            <div class="metric-row">
              <span class="metric-label"><?= htmlspecialchars((string)$eventType) ?></span>
              <span class="metric-value <?= str_contains($eventType, 'failure') ? 'danger' : 'success' ?>">
                <?= number_format((float)$count) ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($billingWebhooks)): ?>
      <?php
      $billingWebhookOutcomes = getArrayValue($billingWebhooks, 'outcomes');
      $billingWebhookEvents = getArrayValue($billingWebhooks, 'event_types');
      ?>
      <div class="metric-card">
        <h2>💳 <?php echo metrics_index_i18n('ADMIN_METRICS_STRIPE_WEBHOOKS_TODAY'); ?></h2>
        <div class="metric-row">
          <span class="metric-label">Processed</span>
          <span class="metric-value success"><?= number_format(getIntValue($billingWebhookOutcomes, 'processed', 0)) ?></span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Duplicates</span>
          <span class="metric-value"><?= number_format(getIntValue($billingWebhookOutcomes, 'duplicate', 0)) ?></span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Verification Failed</span>
          <span class="metric-value danger"><?= number_format(getIntValue($billingWebhookOutcomes, 'verification_failed', 0)) ?></span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Rejected Events</span>
          <span class="metric-value danger"><?= number_format(getIntValue($billingWebhookOutcomes, 'event_rejected', 0)) ?></span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Empty Payloads</span>
          <span class="metric-value danger"><?= number_format(getIntValue($billingWebhookOutcomes, 'payload_empty', 0)) ?></span>
        </div>
        <div class="metric-row">
          <span class="metric-label">Missing Signatures</span>
          <span class="metric-value danger"><?= number_format(getIntValue($billingWebhookOutcomes, 'signature_missing', 0)) ?></span>
        </div>

        <?php if (!empty($billingWebhookEvents)): ?>
          <div class="metrics-grid">
            <?php foreach ($billingWebhookEvents as $eventType => $summary): ?>
              <?php $eventSummary = is_array($summary) ? $summary : []; ?>
              <div class="metric-row">
                <span class="metric-label"><?= htmlspecialchars((string) $eventType) ?></span>
                <span class="metric-value">
                  <?= number_format(getIntValue($eventSummary, 'processed', 0)) ?> <?php echo metrics_index_i18n('ADMIN_STRIPE_PROCESSED_LOWER'); ?> /
                  <?= number_format(getIntValue($eventSummary, 'duplicate', 0)) ?> <?php echo metrics_index_i18n('ADMIN_STRIPE_DUPLICATE_LOWER'); ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="metric-row">
          <span class="metric-label"><?php echo metrics_index_i18n('ADMIN_METRICS_DEDICATED_DASHBOARD'); ?></span>
          <span class="metric-value"><a href="/admin/stripe/" class="btn btn_secondary"><?php echo metrics_index_i18n('ADMIN_OPEN_STRIPE_DASHBOARD'); ?></a></span>
        </div>
      </div>
    <?php endif; ?>

    <!-- SCRAPER DEFENSE METRICS -->
    <div class="metric-card">
      <h2>🛡️ <?php echo metrics_index_i18n('SCRAPER_DEFENSE'); ?></h2>
      <div class="metric-row">
        <span class="metric-label">Total Attempts</span>
        <span class="metric-value danger"><?= number_format(getIntValue($scraperDefense, 'total_attempts', 0)) ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Avg Attempts / Day</span>
        <span class="metric-value"><?= number_format(getFloatValue($scraperDefense, 'avg_per_day', 0.0), 2) ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Avg Attempts / Week</span>
        <span class="metric-value"><?= number_format(getFloatValue($scraperDefense, 'avg_per_week', 0.0), 2) ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Avg Attempts / Month</span>
        <span class="metric-value"><?= number_format(getFloatValue($scraperDefense, 'avg_per_month', 0.0), 2) ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Avg Attempts / Year</span>
        <span class="metric-value"><?= number_format(getFloatValue($scraperDefense, 'avg_per_year', 0.0), 2) ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Attempts Today</span>
        <span class="metric-value"><?= number_format(getIntValue($scraperDefense, 'attempts_today', 0)) ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Attempts This Week</span>
        <span class="metric-value"><?= number_format(getIntValue($scraperDefense, 'attempts_this_week', 0)) ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Attempts This Month</span>
        <span class="metric-value"><?= number_format(getIntValue($scraperDefense, 'attempts_this_month', 0)) ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Attempts This Year</span>
        <span class="metric-value"><?= number_format(getIntValue($scraperDefense, 'attempts_this_year', 0)) ?></span>
      </div>
    </div>

    <!-- CONTACT SUPPORT HEALTH -->
    <div class="metric-card">
      <h2>✉️ <?php echo metrics_index_i18n('ADMIN_CONTACT_PIPELINE_HEALTH'); ?></h2>
      <div class="metric-row">
        <span class="metric-label">Total Submissions</span>
        <span class="metric-value"><?= number_format(getIntValue($contact, 'total_submissions', 0)) ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Successful Submissions</span>
        <span class="metric-value success"><?= number_format(getIntValue($contact, 'successful_submissions', 0)) ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Failed Submissions</span>
        <span class="metric-value danger"><?= number_format(getIntValue($contact, 'failed_submissions', 0)) ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Today (Total / Success / Failure)</span>
        <span class="metric-value"><?= number_format(getIntValue($contact, 'today_total', 0)) ?> / <?= number_format(getIntValue($contact, 'today_success', 0)) ?> / <?= number_format(getIntValue($contact, 'today_failure', 0)) ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Week Total</span>
        <span class="metric-value"><?= number_format(getIntValue($contact, 'week_total', 0)) ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Month Total</span>
        <span class="metric-value"><?= number_format(getIntValue($contact, 'month_total', 0)) ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Last Submission</span>
        <span class="metric-value"><?= getIntValue($contact, 'last_submission_at', 0) > 0 ? htmlspecialchars(date('Y-m-d H:i:s', getIntValue($contact, 'last_submission_at', 0))) : 'Never' ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Last Failure Outcome</span>
        <span class="metric-value danger"><?= htmlspecialchars(getStringValue($contact, 'last_failure_outcome', 'none')) ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Log Path</span>
        <span class="metric-value"><?= htmlspecialchars(getStringValue($contact, 'log_path', 'unavailable')) ?></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Log Size</span>
        <span class="metric-value"><?= number_format(getIntValue($contact, 'log_size_bytes', 0)) ?> bytes</span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Rotation Policy</span>
        <span class="metric-value">max <?= number_format(getIntValue($contact, 'rotation_max_bytes', 0)) ?> bytes, keep <?= number_format(getIntValue($contact, 'rotation_keep_files', 0)) ?> files</span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Log Write Failures</span>
        <span class="metric-value <?= getIntValue($contact, 'log_write_failures', 0) > 0 ? 'danger' : 'success' ?>"><?= number_format(getIntValue($contact, 'log_write_failures', 0)) ?></span>
      </div>
    </div>

    <!-- TOP NETBLOCKS -->
    <div class="metric-card">
      <h2>🚨 <?php echo metrics_index_i18n('ADMIN_METRICS_TOP_NETBLOCKS'); ?></h2>
      <div class="metrics-grid">
        <?php
          $topNetblocksRaw = $scraperDefense['top_netblocks'] ?? [];
          $topNetblocks = is_array($topNetblocksRaw) ? $topNetblocksRaw : [];
        ?>
        <?php if ([] === $topNetblocks): ?>
          <div class="metric-row">
            <span class="metric-label"><?php echo metrics_index_i18n('ADMIN_METRICS_NO_HOSTILE_NETBLOCK_ACTIVITY'); ?></span>
            <span class="metric-value">0</span>
          </div>
        <?php else: ?>
          <?php foreach ($topNetblocks as $row): ?>
            <?php
              $rowName = is_array($row) && is_string($row['name'] ?? null) ? $row['name'] : metrics_index_i18n('CLI_NETBLOCK_UNKNOWN');
              $rowAttempts = is_array($row) && is_numeric($row['attempts'] ?? null) ? (int)$row['attempts'] : 0;
            ?>
            <div class="metric-row">
              <span class="metric-label"><?= htmlspecialchars($rowName) ?></span>
              <span class="metric-value danger"><?= number_format($rowAttempts) ?></span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- ACTION BUTTONS -->
    <div class="action-buttons">
      <a href="/admin" class="btn btn-secondary">← <?php echo metrics_index_i18n('ADMIN_STRIPE_BACK_TO_ADMIN'); ?></a>
      <a href="/transparency/metrics" target="_blank" class="btn btn-secondary"><?php echo metrics_index_i18n('ADMIN_DASHBOARD_VIEW_PUBLIC_PAGE'); ?></a>
      <button id="refresh-metrics-btn" type="button" class="btn btn-primary">🔄 <?php echo metrics_index_i18n('REFRESH_METRICS'); ?></button>
    </div>

  <?php endif; ?>
  <?= Render::jsScript('admin/metrics') ?>
</div>
<?php
$html = ob_get_clean() ?: '';

echo Render::layout('authenticated', $html, [
  'pageTitle' => $pageTitle,
  'metaDescription' => $metaDescription,
]);
