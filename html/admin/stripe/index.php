<?php declare(strict_types=1);

use PayCal\Domain\Authentication;
use PayCal\Domain\AdminSurface;
use PayCal\Domain\DataGrid;
use PayCal\Domain\MetricsService;
use PayCal\Domain\Render;
use PayCal\Domain\Strings;

require_once '../../config.php';

$currentPage = 'PAGE_ADMIN';
$pageTitle = 'Stripe Dashboard - [PayCal]';
$pageLabel = 'Stripe Dashboard';

Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/stripe/');

/**
 * @param array<string, mixed> $arr
 * @return array<string, mixed>
 */
function getArrayValue(array $arr, string $key): array {
  return isset($arr[$key]) && is_array($arr[$key]) ? $arr[$key] : [];
}

/**
 * @param array<string, mixed> $arr
 */
function getIntValue(array $arr, string $key, int $default = 0): int {
  return isset($arr[$key]) && is_numeric($arr[$key]) ? (int) $arr[$key] : $default;
}

/**
 * @param array<string, mixed> $arr
 */
function getStringValue(array $arr, string $key, string $default = ''): string {
  return isset($arr[$key]) && is_scalar($arr[$key]) ? (string) $arr[$key] : $default;
}

function formatStripeNumber(int|float $value, int $fractionDigits = 0): string {
  return Strings::formatLocalizedNumber($value, $fractionDigits, $fractionDigits);
}

$billingWebhooks = MetricsService::getBillingWebhookMetrics();
$billingWebhookOutcomes = getArrayValue($billingWebhooks, 'outcomes');
$billingWebhookEvents = getArrayValue($billingWebhooks, 'event_types');
$billingWebhookRecentDays = getArrayValue($billingWebhooks, 'recent_days');
$billingWebhookRecentThirtyDays = getArrayValue($billingWebhooks, 'recent_30_days');
$billingWebhookRollingThirtyTotals = getArrayValue($billingWebhooks, 'rolling_30_totals');
$summaryDate = getStringValue($billingWebhooks, 'date', date('Y-m-d'));

// Prepare data for event types grid
$eventTypesRows = [];
foreach ($billingWebhookEvents as $eventType => $summary) {
  $eventSummary = is_array($summary) ? $summary : [];
  $eventTypesRows[] = [
    'event_type' => (string) $eventType,
    'processed' => formatStripeNumber(getIntValue($eventSummary, 'processed', 0)),
    'duplicate' => formatStripeNumber(getIntValue($eventSummary, 'duplicate', 0)),
  ];
}

// Prepare data for 7-day trend grid
$sevenDayRows = [];
foreach ($billingWebhookRecentDays as $row) {
  $day = is_array($row) ? $row : [];
  $sevenDayRows[] = [
    'date' => getStringValue($day, 'date', ''),
    'processed' => formatStripeNumber(getIntValue($day, 'processed', 0)),
    'duplicate' => formatStripeNumber(getIntValue($day, 'duplicate', 0)),
    'verify_fail' => formatStripeNumber(getIntValue($day, 'verification_failed', 0)),
    'rejected' => formatStripeNumber(getIntValue($day, 'event_rejected', 0)),
  ];
}

// Prepare data for 30-day trend grid
$thirtyDayRows = [];
foreach ($billingWebhookRecentThirtyDays as $row) {
  $day = is_array($row) ? $row : [];
  $thirtyDayRows[] = [
    'date' => getStringValue($day, 'date', ''),
    'processed' => formatStripeNumber(getIntValue($day, 'processed', 0)),
    'duplicate' => formatStripeNumber(getIntValue($day, 'duplicate', 0)),
    'verify_fail' => formatStripeNumber(getIntValue($day, 'verification_failed', 0)),
    'rejected' => formatStripeNumber(getIntValue($day, 'event_rejected', 0)),
  ];
}

// Create DataGrid instances
$eventTypesGrid = new DataGrid([
  'id' => 'stripe-event-types-grid',
  'columns' => [
    ['key' => 'event_type', 'label' => 'Event Type', 'sortable' => false],
    ['key' => 'processed', 'label' => 'Processed', 'sortable' => false],
    ['key' => 'duplicate', 'label' => 'Duplicate', 'sortable' => false],
  ],
  'rows' => $eventTypesRows,
  'meta' => [
    'layout' => 'auto',
    'noChrome' => false,
  ],
]);

$sevenDayGrid = new DataGrid([
  'id' => 'stripe-7day-trend-grid',
  'columns' => [
    ['key' => 'date', 'label' => 'Date', 'sortable' => false],
    ['key' => 'processed', 'label' => 'Processed', 'sortable' => false],
    ['key' => 'duplicate', 'label' => 'Duplicate', 'sortable' => false],
    ['key' => 'verify_fail', 'label' => 'Verify Fail', 'sortable' => false],
    ['key' => 'rejected', 'label' => 'Rejected', 'sortable' => false],
  ],
  'rows' => $sevenDayRows,
  'meta' => [
    'layout' => 'auto',
    'noChrome' => false,
  ],
]);

$thirtyDayGrid = new DataGrid([
  'id' => 'stripe-30day-trend-grid',
  'columns' => [
    ['key' => 'date', 'label' => 'Date', 'sortable' => false],
    ['key' => 'processed', 'label' => 'Processed', 'sortable' => false],
    ['key' => 'duplicate', 'label' => 'Duplicate', 'sortable' => false],
    ['key' => 'verify_fail', 'label' => 'Verify Fail', 'sortable' => false],
    ['key' => 'rejected', 'label' => 'Rejected', 'sortable' => false],
  ],
  'rows' => $thirtyDayRows,
  'meta' => [
    'layout' => 'auto',
    'noChrome' => false,
  ],
]);

require_once HTML . '/header.php';

$cspNonceRaw = $_SERVER['CSP_NONCE'] ?? '';
$cspNonce = is_scalar($cspNonceRaw) ? (string) $cspNonceRaw : '';
echo '<link rel="stylesheet" href="' . htmlspecialchars(Render::cssURL('admin/metrics'), ENT_QUOTES, 'UTF-8') . '" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;
?>
<section class="metrics-dashboard" aria-label="Stripe billing dashboard">
  <div class="metric-card">
    <h1>Stripe Dashboard</h1>
    <p>Admin-only overview of webhook processing health and event coverage for <?= htmlspecialchars($summaryDate, ENT_QUOTES, 'UTF-8') ?>.</p>
    <div class="metric-row">
      <span class="metric-label">Processed</span>
      <span class="metric-value success"><?= formatStripeNumber(getIntValue($billingWebhookOutcomes, 'processed', 0)) ?></span>
    </div>
    <div class="metric-row">
      <span class="metric-label">Duplicates</span>
      <span class="metric-value"><?= formatStripeNumber(getIntValue($billingWebhookOutcomes, 'duplicate', 0)) ?></span>
    </div>
    <div class="metric-row">
      <span class="metric-label">Verification Failed</span>
      <span class="metric-value danger"><?= formatStripeNumber(getIntValue($billingWebhookOutcomes, 'verification_failed', 0)) ?></span>
    </div>
    <div class="metric-row">
      <span class="metric-label">Rejected Events</span>
      <span class="metric-value danger"><?= formatStripeNumber(getIntValue($billingWebhookOutcomes, 'event_rejected', 0)) ?></span>
    </div>
    <div class="metric-row">
      <span class="metric-label">Empty Payloads</span>
      <span class="metric-value danger"><?= formatStripeNumber(getIntValue($billingWebhookOutcomes, 'payload_empty', 0)) ?></span>
    </div>
    <div class="metric-row">
      <span class="metric-label">Missing Signatures</span>
      <span class="metric-value danger"><?= formatStripeNumber(getIntValue($billingWebhookOutcomes, 'signature_missing', 0)) ?></span>
    </div>
    <div class="metric-row">
      <span class="metric-label">Stripe Secret Missing</span>
      <span class="metric-value danger"><?= formatStripeNumber(getIntValue($billingWebhookOutcomes, 'secret_key_missing', 0)) ?></span>
    </div>
    <div class="metric-row">
      <span class="metric-label">Webhook Secret Missing</span>
      <span class="metric-value danger"><?= formatStripeNumber(getIntValue($billingWebhookOutcomes, 'webhook_secret_missing', 0)) ?></span>
    </div>
  </div>

  <div class="metric-card">
    <h2>Webhook Event Types</h2>
    <?php echo $eventTypesGrid->table(); ?>
  </div>

  <div class="metric-card">
    <h2>7-Day Trend</h2>
    <?php echo $sevenDayGrid->table(); ?>
  </div>

  <div class="metric-card">
    <h2>30-Day Trend</h2>
    <p>
      Total processed: <strong><?= formatStripeNumber(getIntValue($billingWebhookRollingThirtyTotals, 'processed', 0)) ?></strong> /
      duplicate: <strong><?= formatStripeNumber(getIntValue($billingWebhookRollingThirtyTotals, 'duplicate', 0)) ?></strong> /
      verification failed: <strong><?= formatStripeNumber(getIntValue($billingWebhookRollingThirtyTotals, 'verification_failed', 0)) ?></strong> /
      rejected: <strong><?= formatStripeNumber(getIntValue($billingWebhookRollingThirtyTotals, 'event_rejected', 0)) ?></strong>
    </p>
    <?php echo $thirtyDayGrid->table(); ?>
  </div>

  <div class="metric-card">
    <h2>Actions</h2>
    <div class="metrics-grid">
      <div class="metric-row">
        <span class="metric-label">Admin Overview</span>
        <span class="metric-value"><a href="/admin/" class="btn btn_secondary">Back to Admin</a></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Platform Metrics</span>
        <span class="metric-value"><a href="/admin/metrics/" class="btn btn_secondary">Open Metrics</a></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Stripe Dashboard</span>
        <span class="metric-value"><a href="https://dashboard.stripe.com/" target="_blank" rel="noopener noreferrer" class="btn btn_secondary">Open Stripe (External)</a></span>
      </div>
      <div class="metric-row">
        <span class="metric-label">Raw Telemetry API</span>
        <span class="metric-value"><a href="/api/v1/billing/telemetry" class="btn btn_primary">View JSON</a></span>
      </div>
    </div>
  </div>
</section>
<?php
require_once HTML . '/footer.php';